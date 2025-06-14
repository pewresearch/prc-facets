<?php
/**
 * Utility functions.
 *
 * @package    PRC\Platform\Facets
 */

namespace PRC\Platform\Facets;

/**
 * Format a label.
 *
 * @param string $label The label to format.
 * @return string The formatted label.
 */
function format_label( $label ) {
	// If the label is a datetime let's check if its in the years only format and if so, return the year.
	if ( strtotime( $label ) !== false ) {
		return preg_match( '/^\d{4}$/', $label ) ? $label : gmdate( 'Y', strtotime( $label ) );
	}
	// Render any ampersands and such in the label.
	return html_entity_decode( $label );
}

/**
 * Determine if we should be using ElasticPress facets.
 *
 * @return bool True if we should be using ElasticPress facets, false otherwise.
 */
function use_ep_facets() {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( $_SERVER['REQUEST_URI'] ) : '';
	if ( strpos( $uri, '/search' ) !== false ) {
		return true;
	}
	return false;
}

/**
 * Constructs a cache key based on the current query and selected facets.
 *
 * @param array $query The current query.
 * @param array $selected The selected facets.
 * @return string The cache key.
 */
function construct_cache_key( $query = array(), $selected = array() ) {
	$invalidate = '06/12/2025';
	// Remove pagination from the query args.
	$query = array_merge(
		$query,
		array(
			'paged' => 1,
		)
	);
	// Construct an md5 hash of the query and selected facets and a quick invalidation metho.
	return md5(
		wp_json_encode(
			array(
				'query'      => $query,
				'selected'   => $selected,
				'invalidate' => $invalidate,
			)
		)
	);
}

/**
 * Constructs a cache group based on the current URL.
 *
 * @return string|false The cache group, or false if the current URL is not valid.
 */
function construct_cache_group() {
	global $wp;
	// Construct an array of URL parameters from the current request to WP.
	$url_params = wp_parse_url( '/' . add_query_arg( array( $_GET ), $wp->request . '/' ) );
	if ( ! is_array( $url_params ) || ! array_key_exists( 'path', $url_params ) ) {
		return false;
	}
	// Remove pagination from the cache group.
	return preg_replace( '/\/page\/[0-9]+/', '', $url_params['path'] );
}
