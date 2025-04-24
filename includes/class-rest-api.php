<?php
/**
 * REST API class.
 *
 * @package    PRC\Platform\Facets
 */

namespace PRC\Platform\Facets;

use WP_REST_Request;

/**
 * Register REST API endpoints for facet templating.
 */
class Rest_API {
	/**
	 * Constructor.
	 *
	 * @param Loader $loader The loader.
	 */
	public function __construct( $loader ) {
		$loader->add_filter( 'prc_api_endpoints', $this, 'register_endpoints' );
	}

	/**
	 * Register REST API endpoints for facet templating.
	 *
	 * @hook prc_api_endpoints
	 * @param array $endpoints
	 * @return array $endpoints
	 */
	public function register_endpoints( $endpoints ) {
		$settings = array(
			'route'               => '/facets/get-settings',
			'methods'             => 'GET',
			'callback'            => array( $this, 'restfully_get_facet_settings' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'templateSlug' => array(
					'description' => 'The slug of the site-editor template. This is used to determine which facets middleware should be enabled.',
					'type'        => 'string',
					'required'    => true,
					'default'     => 'archive',
				),
			),
		);
		array_push( $endpoints, $settings );
		return $endpoints;
	}

	/**
	 * Get the facet settings for the required provider based on template.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return array The facet settings.
	 */
	public function restfully_get_facet_settings( WP_REST_Request $request ) {
		$tempalte_slug = $request->get_param( 'templateSlug' );
		if ( str_contains( $tempalte_slug, 'search' ) ) {
			return ElasticPress_Middleware::get_facets_settings();
		} else {
			return FacetWP_Middleware::get_facets_settings();
		}
	}
}
