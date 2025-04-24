<?php
/**
 * A PHP based API for interacting with Facets WP data via the REST API.
 *
 * @package    PRC\Platform\Facets
 */

namespace PRC\Platform\Facets;

use ACA\ACF\Editing\Service\Taxonomy;
use Error;
use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;

/**
 * A PHP based API for interacting with Facets WP data via the REST API.
 */
class FacetWP_API {
	/**
	 * Whether to enable caching of the facets data.
	 *
	 * @var bool
	 */
	protected $enable_cache = true;

	/**
	 * The cache key.
	 *
	 * @var string
	 */
	public $cache_key;

	/**
	 * The cache group.
	 *
	 * @var string
	 */
	public $cache_group;

	/**
	 * The selected facets.
	 *
	 * @var array
	 */
	public $selected = array();

	/**
	 * The registered facets.
	 *
	 * @var array
	 */
	public $registered_facets = array();

	/**
	 * The query arguments.
	 *
	 * @var array
	 */
	public $query_args;

	/**
	 * The query ID.
	 *
	 * @var string
	 */
	public $query_id;

	/**
	 * The query.
	 *
	 * @var array
	 */
	public $query;

	/**
	 * The constructor.
	 *
	 * @param array $query The query.
	 */
	public function __construct( $query ) {
		$this->query_args        = $this->construct_query_args( $query );
		$this->registered_facets = $this->get_registered_facets();
		$this->selected          = $this->get_selected();
		$this->cache_key         = construct_cache_key( $query, $this->selected );
		$this->cache_group       = construct_cache_group();
		$this->query             = $this->query();
	}

	/**
	 * Construct the query arguments.
	 *
	 * @param array $query_args The query arguments.
	 * @return array The query arguments.
	 */
	public function construct_query_args( $query_args = array() ) {
		$query_args = \PRC\Platform\Publication_Listing::get_filtered_query_args( $query_args, null );
		$query_args = array_merge(
			$query_args,
			array(
				'paged' => 1,
			)
		);
		// Even though we're not running on search pages we should offload to es if search is ever attempted.
		if ( ! empty( $query_args['s'] ) ) {
			$query_args['es'] = true;
		}
		do_action( 'qm/debug', 'PRC Facets - Query Args:: ' . print_r( $query_args, true ) );
		return $query_args;
	}

	/**
	 * Get the current selection.
	 *
	 * @param string $facet_slug The facet slug.
	 * @return array The current selection.
	 */
	public function get_current_selection( $facet_slug ) {
		$current_url       = home_url( add_query_arg( null, null ) );
		$current_query     = wp_parse_url( $current_url, PHP_URL_QUERY );
		$current_selection = null;
		$facet_slug        = '_' . $facet_slug;
		if ( $current_query ) {
			$query_vars = wp_parse_args( $current_query );
			if ( array_key_exists( $facet_slug, $query_vars ) ) {
				$current_selection = explode( ',', $query_vars[ $facet_slug ] );
			}
		}
		return $current_selection;
	}

	/**
	 * Get the registered facets.
	 *
	 * @return array The registered facets.
	 */
	public function get_registered_facets() {
		$settings = get_option( 'facetwp_settings', false );
		if ( ! $settings ) {
			return array();
		}
		$registered_facets = array();
		$facets            = json_decode( $settings, true )['facets'];
		foreach ( $facets as $facet ) {
			$registered_facets[ $facet['name'] ] = $this->get_current_selection( $facet['name'] );
		}
		return $registered_facets;
	}

	/**
	 * Get the selected facets.
	 *
	 * @return array The selected facets.
	 */
	public function get_selected() {
		$selected_choices = array();
		foreach ( $this->registered_facets as $facet_slug => $selected ) {
			if ( ! empty( $selected ) ) {
				$selected_choices[ $facet_slug ] = $selected;
			}
		}
		return $selected_choices;
	}

	/**
	 * Process a taxonomy facet.
	 *
	 * @param array $facet The facet.
	 * @return array The processed facet.
	 */
	protected function process_taxonomy_facet( $facet ) {
		$selected       = $facet['selected'];
		$facet_slug     = $facet['name'];
		$taxonomy_facet = array(
			'choices'         => array(),
			'expandedChoices' => array(),
			'selected'        => $selected,
			'facetSlug'       => $facet_slug,
		);
		if ( ! array_key_exists( 'choices', $facet ) ) {
			return $taxonomy_facet;
		}
		foreach ( $facet['choices'] as $facet_obj ) {
			if ( ! array_key_exists( 'term_id', $facet_obj ) ) {
				continue;
			}
			$taxonomy_facet['choices'][] = array(
				'count'      => isset( $facet_obj['count'] ) ? $facet_obj['count'] : 0,
				'label'      => format_label( $facet_obj['label'] ),
				'slug'       => $facet_obj['value'],
				'facetSlug'  => $facet_slug,
				'term_id'    => $facet_obj['term_id'],
				'value'      => $facet_obj['value'],
				'isSelected' => in_array( $facet_obj['value'], $selected ),
				'isRequired' => false, // None of these are required.
				'type'       => $facet['type'],
			);
		}
		return $taxonomy_facet;
	}

	/**
	 * Get the facet type.
	 *
	 * @param array $facet The facet.
	 * @return string The facet type.
	 */
	public static function get_facet_type( $facet ) {
		$facet_type = $facet['type'];
		switch ( $facet_type ) {
			case 'checkboxes':
				$facet_type = 'checkbox';
				break;
			case 'dropdown':
				$facet_type = 'dropdown';
				break;
			case 'radio':
				$facet_type = 'radio';
				break;
			case 'range':
				$facet_type = 'range';
				break;
			case 'time_since':
				$facet_type = 'radio';
				break;
			case 'yearly':
				$facet_type = 'dropdown';
				break;
			default:
				$facet_type = 'radio';
				break;
		}
		return $facet_type;
	}

	/**
	 * Process a non-taxonomy facet.
	 *
	 * @param array $facet The facet.
	 * @return array The processed facet.
	 */
	protected function process_non_taxonomy_facet( $facet ) {
		$facet['type']  = $this->get_facet_type( $facet );
		$selected       = $facet['selected'];
		$facet_slug     = $facet['name'];
		$standard_facet = array(
			'choices'         => array(),
			'expandedChoices' => array(),
			'selected'        => $selected,
			'facetSlug'       => $facet_slug,
		);
		if ( ! array_key_exists( 'choices', $facet ) ) {
			return $standard_facet;
		}
		foreach ( $facet['choices'] as $facet_obj ) {
			$standard_facet['choices'][] = array(
				'count'      => isset( $facet_obj['count'] ) ? $facet_obj['count'] : 0,
				'label'      => format_label( $facet_obj['label'] ),
				'slug'       => $facet_obj['value'],
				'facetSlug'  => $facet_slug,
				'value'      => $facet_obj['value'],
				'isSelected' => in_array( $facet_obj['value'], $selected ),
				'isRequired' => false, // None of these are required.
				'type'       => $facet['type'],
			);
		}
		return $standard_facet;
	}

	/**
	 * Parse the facets.
	 *
	 * @param array $facets The facets.
	 * @return array The parsed facets.
	 */
	protected function parse_facets( $facets ) {
		$tmp_facets = array();
		foreach ( $facets as $facet_slug => $data ) {
			if ( taxonomy_exists( $facet_slug ) ) {
				$tmp_facets[ $facet_slug ] = $this->process_taxonomy_facet( $data );
			} else {
				$tmp_facets[ $facet_slug ] = $this->process_non_taxonomy_facet( $data );
			}
		}
		return $tmp_facets;
	}

	/**
	 * Query the facets.
	 *
	 * @return array The facets.
	 */
	public function query() {
		// If we are not on the primary site, return an empty array.
		// We do not allow other sites to query facets.
		// If we can't validate a cache group, and this is an outside user, then we'll failover as well.
		$failover = false === $this->cache_group && ! is_user_logged_in();
		if ( is_paged() && 100 < get_query_var( 'paged' ) ) {
			$failover = true;
		}
		if ( 1 === get_current_blog_id() ) {
			$failover = true;
		}
		if ( is_search() || $failover ) {
			// @TODO: Experiment with creating a ElasticPress Facets data provider that matches this format... just needs to transform arguments...
			return array(
				'facets'     => array(),
				'query_args' => array(),
				'settings'   => array(
					'first_load' => true,
				),
			);
		}

		// If cache is enabled, check if we have a cached version of the facets.
		if ( $this->enable_cache ) {
			$cached_facets = wp_cache_get( $this->cache_key, $this->cache_group );
			if ( false !== $cached_facets ) {
				return $cached_facets;
			}
		}

		$args = array(
			'facets'     => $this->registered_facets,
			'query_args' => $this->query_args,
			'settings'   => array(
				'first_load' => true,
			),
		);

		// Build FacetWP rest request.
		$request = new \WP_REST_Request( 'POST', '/facetwp/v1/fetch' );
		$request->set_param( 'data', wp_json_encode( $args ) );

		// Send request.
		$response = rest_do_request( $request );
		$server   = rest_get_server();
		$data     = $server->response_to_data( $response, false );

		// Get the facets data and parse it...
		$data['facets'] = $this->parse_facets( $data['facets'] );

		if ( ! is_preview() || ! empty( $data['facets'] ) ) {
			// If cache is enabled, cache the facets for 5 minutes.
			if ( $this->enable_cache ) {
				wp_cache_set(
					$this->cache_key,
					$data,
					$this->cache_group,
					30 * MINUTE_IN_SECONDS
				);
			}
		}

		return $data;
	}

	/**
	 * Get the facets.
	 *
	 * @return array The facets.
	 */
	public function get_facets() {
		return $this->query['facets'];
	}

	/**
	 * Get the pagination.
	 *
	 * @return array The pagination.
	 */
	public function get_pagination() {
		return $this->query['pager'];
	}
}
