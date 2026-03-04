<?php
/**
 * Middleware for FacetWP integration.
 *
 * @package PRC\Platform
 */

namespace PRC\Platform\Facets;

use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;

/**
 * FacetWP Middleware.
 */
class FacetWP_Middleware {
	/**
	 * Debug mode flag.
	 *
	 * @var bool
	 */
	private static $debug_mode = null;

	/**
	 * The facets.
	 *
	 * @var array
	 */
	public static $facets = array(
		array(
			'name'            => 'categories',
			'label'           => 'Topics',
			'type'            => 'checkboxes',
			'source'          => 'tax/category',
			'parent_term'     => '',
			'modifier_type'   => 'off',
			'modifier_values' => '',
			'hierarchical'    => 'yes',
			'show_expanded'   => 'no',
			'ghosts'          => 'yes',
			'preserve_ghosts' => 'no',
			'operator'        => 'or',
			'orderby'         => 'count',
			'count'           => '50',
			'soft_limit'      => '5',
		),
		array(
			'name'            => 'research_teams',
			'label'           => 'Research Teams',
			'type'            => 'dropdown',
			'source'          => 'tax/research-teams',
			'label_any'       => 'Any',
			'parent_term'     => '',
			'modifier_type'   => 'off',
			'modifier_values' => '',
			'hierarchical'    => 'no',
			'orderby'         => 'count',
			'count'           => '25',
		),
		array(
			'name'            => 'formats',
			'label'           => 'Formats',
			'type'            => 'checkboxes',
			'source'          => 'tax/formats',
			'parent_term'     => '',
			'modifier_type'   => 'off',
			'modifier_values' => '',
			'hierarchical'    => 'no',
			'show_expanded'   => 'no',
			'ghosts'          => 'yes',
			'preserve_ghosts' => 'no',
			'operator'        => 'or',
			'orderby'         => 'count',
			'count'           => '-1',
			'soft_limit'      => '5',
		),
		array(
			'name'            => 'authors',
			'label'           => 'Authors',
			'type'            => 'dropdown',
			'source'          => 'tax/bylines',
			'label_any'       => 'Any',
			'parent_term'     => '',
			'modifier_type'   => 'off',
			'modifier_values' => '',
			'hierarchical'    => 'no',
			'orderby'         => 'count',
			'count'           => '-1',
		),
		array(
			'name'      => 'time_since',
			'label'     => 'Time Since',
			'type'      => 'time_since',
			'source'    => 'post_date',
			'label_any' => 'By Date Range',
			'choices'   => "Past Month | -30 days\nPast 6 Months | -180 days\nPast 12 Months | -365 days\nPast 2 Years | -730 days",
		),
		array(
			'name'         => 'date_range',
			'label'        => 'Date Range',
			'type'         => 'date_range',
			'source'       => 'post_date',
			'compare_type' => '',
			'fields'       => 'both',
			'format'       => 'Y',
		),
		array(
			'name'      => 'years',
			'label'     => 'Years',
			'type'      => 'yearly',
			'source'    => 'post_date',
			'label_any' => 'Any',
			'orderby'   => 'count',
			'count'     => '75',
		),
		array(
			'name'            => 'regions_countries',
			'label'           => 'Regions & Countries',
			'type'            => 'radio',
			'source'          => 'tax/regions-countries',
			'label_any'       => 'Any',
			'parent_term'     => '',
			'modifier_type'   => 'off',
			'modifier_values' => '',
			'ghosts'          => 'yes',
			'preserve_ghosts' => 'no',
			'orderby'         => 'count',
			'count'           => '-1',
		),
	);

	/**
	 * Check if debug mode is enabled.
	 *
	 * @return bool True if debug mode is enabled.
	 */
	private static function is_debug_mode() {
		if ( null === self::$debug_mode ) {
			self::$debug_mode = defined( 'PRC_FACETS_DEBUG' ) && PRC_FACETS_DEBUG;
		}
		return self::$debug_mode;
	}

	/**
	 * Log debug information.
	 *
	 * @param string $message The message to log.
	 * @param mixed  $data    Optional data to log.
	 */
	private static function debug_log( $message, $data = null ) {
		if ( ! self::is_debug_mode() ) {
			return;
		}

		$log_message = '[PRC Facets - FacetWP] ' . $message;
		if ( null !== $data ) {
			$log_message .= ' | Data: ' . wp_json_encode( $data );
		}

		error_log( $log_message ); // phpcs:ignore
	}

	/**
	 * Initialize FacetWP Class
	 *
	 * @param mixed $loader The loader.
	 */
	public function __construct( $loader ) {
		require_once plugin_dir_path( __FILE__ ) . 'class-facetwp-api.php';
		// FacetWP.
		$loader->add_filter( 'facetwp_is_main_query', $this, 'facetwp_is_main_query', 10, 2 );
		$loader->add_filter( 'facetwp_api_can_access', $this, 'allow_facetwp_api_access' );
		$loader->add_filter( 'facetwp_indexer_query_args', $this, 'filter_facetwp_indexer_args', 10, 1 );
		$loader->add_filter( 'facetwp_index_row', $this, 'restrict_facet_row_depth', 10, 1 );
		$loader->add_filter( 'facetwp_facets', $this, 'register_facets', 10, 1 );
		$loader->add_filter( 'pre_get_posts', $this, 'shortcircuit_ep_filtering', 1000, 1 );
		$loader->add_filter( 'facetwp_query_args', $this, 'log_query_modifications', 10, 2 );
		$loader->add_action( 'facetwp_init', $this, 'log_facet_init' );

		self::debug_log( 'FacetWP Middleware initialized' );
	}

	/**
	 * Short circuit ElasticPress filtering.
	 * When filtering by facetwp we do not want ElasticPress to engage.
	 *
	 * @hook pre_get_posts
	 *
	 * @param WP_Query $query The query.
	 */
	public function shortcircuit_ep_filtering( $query ) {
		if ( true === $query->get( 'facetwp' ) ) {
			self::debug_log( 'Short-circuiting ElasticPress for FacetWP query' );
			$query->set( 'ep_facet', false );
			$query->set( 'ep_integrate', false );
			$query->set( 'aggs', array() );
		}
	}

	/**
	 * Determine if FacetWP should be used for the main query.
	 *
	 * @param bool     $is_main_query Whether the query is the main query.
	 * @param WP_Query $query         The query.
	 * @return bool
	 */
	public function facetwp_is_main_query( $is_main_query, $query ) {
		// Short circuit if we're on a search results page for now.
		if ( $query->is_search() ) {
			self::debug_log( 'Disabling FacetWP for search query - using ElasticPress instead' );
			$is_main_query = false;
		}
		return $is_main_query;
	}

	/**
	 * Allow FacetWP rest api access
	 *
	 * @hook facetwp_api_can_access
	 *
	 * @param bool $boolean Whether to allow access.
	 * @return bool
	 */
	public function allow_facetwp_api_access( $boolean ) {
		return true;
	}

	/**
	 * Get the facets settings.
	 *
	 * @return array The facets settings.
	 */
	public static function get_facets_settings() {
		self::debug_log( 'Getting FacetWP facets settings' );

		$settings = get_option( 'facetwp_settings', false );
		$settings = json_decode( $settings, true );
		$facets   = array_key_exists( 'facets', $settings ) ? $settings['facets'] : array();

		self::debug_log( 'Found facets', array_keys( $facets ) );

		foreach ( $facets as $facet_slug => $facet ) {
			$facet['facet_type']   = FacetWP_API::get_facet_type( $facet );
			$facets[ $facet_slug ] = $facet;
		}
		return $facets;
	}

	/**
	 * Log FacetWP initialization.
	 *
	 * @hook facetwp_init
	 */
	public function log_facet_init() {
		self::debug_log(
			'FacetWP initialized',
			array(
				'page_type' => is_search() ? 'search' : 'archive',
				'url'       => $_SERVER['REQUEST_URI'] ?? '', // phpcs:ignore
			)
		);
	}

	/**
	 * Log query modifications.
	 *
	 * @hook facetwp_query_args
	 *
	 * @param array  $query_args The query arguments.
	 * @param object $class The FacetWP class instance.
	 * @return array
	 */
	public function log_query_modifications( $query_args, $class ) {
		if ( self::is_debug_mode() ) {
			$selected = isset( $class->facets ) ? $class->facets : array();
			self::debug_log(
				'Query modifications',
				array(
					'selected_facets' => $selected,
					'query_args'      => $query_args,
					'cache_key'       => construct_cache_key( $query_args, $selected ),
				)
			);
		}
		return $query_args;
	}

	/**
	 * Manually register FacetWP facets
	 *
	 * @hook facetwp_facets
	 *
	 * @param mixed $facets The facets.
	 * @return mixed
	 */
	public function register_facets( $facets ) {
		self::debug_log( 'Registering facets', array_column( self::$facets, 'name' ) );
		return self::$facets;
	}

	/**
	 * Use default platform pub listing query args.
	 *
	 * @hook facetwp_indexer_query_args
	 *
	 * @param mixed $args The args.
	 * @return mixed
	 */
	public function filter_facetwp_indexer_args( $args ) {
		$query_defaults = \PRC\Platform\Publication_Listing\Query::get_filtered_query_args( $args, null );
		$query_defaults = apply_filters( 'prc_platform__facetwp_indexer_query_args', $query_defaults );
		return array_merge( $args, $query_defaults );
	}

	/**
	 * Limit topic, categories, and other hierarchical facets to depth 0; only returning parent terms.
	 *
	 * @hook facetwp_index_row
	 *
	 * @param mixed $params The params.
	 * @return mixed
	 */
	public function restrict_facet_row_depth( $params ) {
		if ( in_array(
			$params['facet_name'],
			array(
				'topics',
				'topic',
				'categories',
				'category',
			)
		) ) {
			if ( $params['depth'] > 0 ) {
				// Don't index this row.
				$params['facet_value'] = '';
			}
		}
		return $params;
	}
}
