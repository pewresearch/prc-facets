<?php
/**
 * WP-CLI: remove leftover FacetWP database artifacts after plugin deletion.
 *
 * @package PRC\Platform\Facets
 */

namespace PRC\Platform\Facets;

use WP_CLI;
use WP_CLI\Utils;
use WPCOM_VIP_CLI_Command;

if ( ! class_exists( 'WPCOM_VIP_CLI_Command' ) ) {
	return;
}

/**
 * FacetWP cleanup commands.
 *
 * ## EXAMPLES
 *
 *     # Preview what would be removed
 *     wp prc facets clean-facetwp
 *
 *     # Delete for real
 *     wp prc facets clean-facetwp --dry-run=false
 */
class CLI_Clean_FacetWP extends WPCOM_VIP_CLI_Command {

	/**
	 * Known FacetWP option names (core + years addon notice).
	 *
	 * @var string[]
	 */
	private const KNOWN_OPTIONS = array(
		'facetwp_settings',
		'facetwp_settings_last_index',
		'facetwp_version',
		'facetwp_last_indexed',
		'facetwp_indexing',
		'facetwp_indexing_data',
		'facetwp_indexing_cancelled',
		'facetwp_license',
		'facetwp_activation',
		'facetwp_updater_response',
		'facetwp_updater_last_checked',
		'YAF_deferred_admin_notices',
	);

	/**
	 * Cron hooks FacetWP / schedule-indexer may have left behind.
	 *
	 * @var string[]
	 */
	private const CRON_HOOKS = array(
		'fwp_scheduled_index',
		'facetwp_indexer_cron',
	);

	/**
	 * Table name suffixes under the current blog prefix.
	 *
	 * @var string[]
	 */
	private const TABLE_SUFFIXES = array(
		'facetwp_index',
		'facetwp_temp',
	);

	/**
	 * Remove leftover FacetWP options, cron events, and index tables.
	 *
	 * Defaults to dry-run. Pass --dry-run=false to write. Safe to re-run.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run=<bool>]
	 * : Preview without deleting. Default: true.
	 *
	 * ## EXAMPLES
	 *
	 *     # Preview
	 *     wp prc facets clean-facetwp
	 *
	 *     # Delete for real
	 *     wp prc facets clean-facetwp --dry-run=false
	 *
	 * @subcommand clean-facetwp
	 * @synopsis [--dry-run=<bool>]
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function clean_facetwp( $args, $assoc_args ) {
		unset( $args );

		$dry_run = $this->parse_dry_run( $assoc_args );

		WP_CLI::line( $dry_run ? 'Running in dry-run mode.' : "We're doing it live!" );

		$option_rows = $this->discover_options();
		$tables      = $this->discover_tables();
		$cron_hooks  = $this->discover_cron_hooks();

		$this->print_options( $option_rows, $dry_run );
		$this->print_tables( $tables, $dry_run );
		$this->print_cron( $cron_hooks, $dry_run );

		$has_work = ! empty( $option_rows ) || ! empty( $tables ) || ! empty( $cron_hooks );

		if ( ! $has_work ) {
			WP_CLI::success( 'No FacetWP options, tables, or cron events found.' );
			return;
		}

		if ( $dry_run ) {
			WP_CLI::line( '' );
			WP_CLI::line( 'Run with --dry-run=false to delete these artifacts.' );
			return;
		}

		$this->start_bulk_operation();

		$deleted_options = $this->delete_options( $option_rows );
		$cleared_cron    = $this->clear_cron_hooks( $cron_hooks );
		$dropped_tables  = $this->drop_tables( $tables );

		$this->end_bulk_operation();

		WP_CLI::success(
			sprintf(
				'Deleted %d option(s), cleared %d cron hook(s), dropped %d table(s).',
				$deleted_options,
				$cleared_cron,
				$dropped_tables
			)
		);
	}

	/**
	 * Discover FacetWP-related options present in the DB.
	 *
	 * Uses a LIKE scan for facetwp% plus the known years-addon key, then
	 * intersects with the known list so unexpected similarly-named options
	 * are not deleted.
	 *
	 * @return array<int, array{option_name: string, size_bytes: int, autoload: string}>
	 */
	private function discover_options() {
		global $wpdb;

		$known = self::KNOWN_OPTIONS;
		$placeholders = implode( ', ', array_fill( 0, count( $known ), '%s' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, LENGTH(option_value) AS size_bytes, autoload
				FROM {$wpdb->options}
				WHERE option_name IN ({$placeholders})
				ORDER BY size_bytes DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- placeholders built above.
				...$known
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Discover FacetWP tables that exist for the current blog prefix.
	 *
	 * @return array<int, array{table: string, rows: int|null}>
	 */
	private function discover_tables() {
		global $wpdb;

		$found = array();

		foreach ( self::TABLE_SUFFIXES as $suffix ) {
			$table = $wpdb->prefix . $suffix;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$exists = $wpdb->get_var(
				$wpdb->prepare( 'SHOW TABLES LIKE %s', $table )
			);

			if ( $exists !== $table ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$count = $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );

			$found[] = array(
				'table' => $table,
				'rows'  => null !== $count ? (int) $count : null,
			);
		}

		return $found;
	}

	/**
	 * Discover scheduled FacetWP cron hooks still present.
	 *
	 * @return string[]
	 */
	private function discover_cron_hooks() {
		$present = array();

		foreach ( self::CRON_HOOKS as $hook ) {
			if ( wp_next_scheduled( $hook ) ) {
				$present[] = $hook;
			}
		}

		return $present;
	}

	/**
	 * Print discovered options.
	 *
	 * @param array $rows    Option rows.
	 * @param bool  $dry_run Whether this is a dry run.
	 */
	private function print_options( array $rows, $dry_run ) {
		if ( empty( $rows ) ) {
			WP_CLI::line( 'Options: none found.' );
			return;
		}

		$table_rows = array_map(
			static function ( $row ) {
				return array(
					'option_name' => $row['option_name'],
					'size'        => size_format( (int) $row['size_bytes'], 2 ),
					'autoload'    => $row['autoload'],
				);
			},
			$rows
		);

		WP_CLI::line( sprintf( 'Found %d FacetWP option(s)%s:', count( $rows ), $dry_run ? ' (dry-run)' : '' ) );
		Utils\format_items( 'table', $table_rows, array( 'option_name', 'size', 'autoload' ) );
	}

	/**
	 * Print discovered tables.
	 *
	 * @param array $tables  Table info.
	 * @param bool  $dry_run Whether this is a dry run.
	 */
	private function print_tables( array $tables, $dry_run ) {
		if ( empty( $tables ) ) {
			WP_CLI::line( 'Tables: none found.' );
			return;
		}

		$table_rows = array_map(
			static function ( $row ) {
				return array(
					'table' => $row['table'],
					'rows'  => null === $row['rows'] ? 'n/a' : (string) $row['rows'],
				);
			},
			$tables
		);

		WP_CLI::line( sprintf( 'Found %d FacetWP table(s)%s:', count( $tables ), $dry_run ? ' (dry-run)' : '' ) );
		Utils\format_items( 'table', $table_rows, array( 'table', 'rows' ) );
	}

	/**
	 * Print discovered cron hooks.
	 *
	 * @param string[] $hooks   Cron hooks.
	 * @param bool     $dry_run Whether this is a dry run.
	 */
	private function print_cron( array $hooks, $dry_run ) {
		if ( empty( $hooks ) ) {
			WP_CLI::line( 'Cron: none found.' );
			return;
		}

		WP_CLI::line( sprintf( 'Found %d FacetWP cron hook(s)%s:', count( $hooks ), $dry_run ? ' (dry-run)' : '' ) );
		foreach ( $hooks as $hook ) {
			$next = wp_next_scheduled( $hook );
			WP_CLI::line(
				sprintf(
					'  - %s (next: %s)',
					$hook,
					$next ? gmdate( 'c', $next ) : 'unknown'
				)
			);
		}
	}

	/**
	 * Delete discovered options.
	 *
	 * @param array $rows Option rows.
	 * @return int Number deleted.
	 */
	private function delete_options( array $rows ) {
		$deleted = 0;

		foreach ( $rows as $row ) {
			if ( delete_option( $row['option_name'] ) ) {
				++$deleted;
				WP_CLI::log(
					sprintf(
						'Deleted option %s (%s).',
						$row['option_name'],
						size_format( (int) $row['size_bytes'], 2 )
					)
				);
			} else {
				WP_CLI::warning( sprintf( 'Could not delete option %s.', $row['option_name'] ) );
			}
		}

		return $deleted;
	}

	/**
	 * Clear scheduled cron hooks.
	 *
	 * @param string[] $hooks Cron hooks.
	 * @return int Number cleared.
	 */
	private function clear_cron_hooks( array $hooks ) {
		$cleared = 0;

		foreach ( $hooks as $hook ) {
			wp_clear_scheduled_hook( $hook );
			++$cleared;
			WP_CLI::log( sprintf( 'Cleared cron hook %s.', $hook ) );
		}

		return $cleared;
	}

	/**
	 * DROP discovered FacetWP tables via $wpdb (VIP blocks DROP via wp db query).
	 *
	 * @param array $tables Table info.
	 * @return int Number dropped.
	 */
	private function drop_tables( array $tables ) {
		global $wpdb;

		$dropped = 0;

		foreach ( $tables as $row ) {
			$table = $row['table'];
			// Only allow our known suffixes under the current prefix.
			$allowed = array_map(
				static function ( $suffix ) use ( $wpdb ) {
					return $wpdb->prefix . $suffix;
				},
				self::TABLE_SUFFIXES
			);

			if ( ! in_array( $table, $allowed, true ) ) {
				WP_CLI::warning( sprintf( 'Refusing to drop unexpected table %s.', $table ) );
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$result = $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );

			if ( false === $result ) {
				WP_CLI::warning( sprintf( 'Could not drop table %s.', $table ) );
				continue;
			}

			++$dropped;
			WP_CLI::log( sprintf( 'Dropped table %s.', $table ) );
		}

		return $dropped;
	}

	/**
	 * Parse --dry-run from $assoc_args safely.
	 *
	 * WP-CLI passes flag values as strings. Casting (bool) 'false' === true,
	 * so we must compare the string value explicitly.
	 *
	 * @param array $assoc_args Associative arguments.
	 * @return bool
	 */
	private function parse_dry_run( array $assoc_args ): bool {
		if ( ! isset( $assoc_args['dry-run'] ) ) {
			return true;
		}
		if ( 'false' === $assoc_args['dry-run'] ) {
			return false;
		}
		return (bool) $assoc_args['dry-run'];
	}
}
