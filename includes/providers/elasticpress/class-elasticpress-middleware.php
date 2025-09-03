<?php
/**
 * Middleware for ElasticPress integration.
 *
 * @package PRC\Platform
 */

namespace PRC\Platform\Facets;

/**
 * Middleware for ElasticPress integration.
 */
class ElasticPress_Middleware {
	/**
	 * The ElasticPress Facets class.
	 *
	 * @var \ElasticPress\Feature\Facets\Facets
	 */
	protected $ep_facets;

	/**
	 * Constructor.
	 *
	 * @param object $loader The loader object.
	 */
	public function __construct( $loader ) {
		require_once plugin_dir_path( __FILE__ ) . 'class-elasticpress-facets-api.php';

		// ElasticPress.
		if ( class_exists( '\ElasticPress\Feature\Facets\Facets' ) ) {
			$this->ep_facets = new \ElasticPress\Feature\Facets\Facets();
			$loader->add_action( 'pre_get_posts', $this, 'take_over_pub_listing_queries', 5, 1 );
			$loader->add_filter( 'ep_facet_include_taxonomies', $this, 'register_facets' );
			$loader->add_filter( 'ep_post_formatted_args', $this, 'add_filters_to_query', 10, 3 );
			$loader->add_filter( 'ep_formatted_args', $this, 'add_date_aggregations', 10, 3 );
			$loader->add_filter( 'ep_valid_response', $this, 'include_date_aggregation_in_response', 19, 4 );
			$loader->add_filter( 'ep_facet_taxonomies_size', $this, 'set_facet_taxonomies_size', 10, 2 );
			$loader->add_filter( 'ep_set_sort', $this, 'sort_ep_by_date', 20, 2 );
			$loader->add_filter( 'prc_platform_rewrite_query_vars', $this, 'register_query_vars' );
		}
	}

	/**
	 * Enforce ElasticPress integration for pub listing queries.
	 * This allows facets to work on non search queries.
	 *
	 * @hook pre_get_posts
	 * @param WP_Query $query The query.
	 */
	public function take_over_pub_listing_queries( $query ) {
		if ( $query->get( 'isPubListingQuery' ) && $query->is_search() ) {
			$query->set( 'ep_integrate', true );
		}
	}

	/**
	 * Set the size of the taxonomy facets, how many records to return.
	 *
	 * @hook ep_facet_taxonomies_size
	 * @param int    $size The size.
	 * @param string $taxonomy The taxonomy.
	 * @return int $size The size.
	 */
	public function set_facet_taxonomies_size( $size, $taxonomy ) {
		$size = 100; // We don't really have that many terms to return for formats or categories, but bylines is a different story. That said, this should return the highest counts first so this works out in the end.
		return $size;
	}

	/**
	 * Get the facet UI type for a given taxonomy.
	 *
	 * @param string $facet_slug The facet slug.
	 * @return string The facet type.
	 */
	public static function get_facet_type( $facet_slug ) {
		switch ( $facet_slug ) {
			case 'category':
			case 'formats':
				return 'checkbox';
			case 'bylines':
			case 'research-teams':
				return 'dropdown';
			case 'regions-countries':
				return 'radio';
			case 'years':
				return 'dropdown';
			default:
				return 'checkbox';
		}
	}

	/**
	 * Add taxonomy aggregations to ElasticPress.
	 *
	 * @hook ep_facet_include_taxonomies
	 * @param array $taxonomies The taxonomies.
	 * @return array $taxonomies The taxonomies.
	 */
	public static function get_facets_settings() {
		$to_return = array();

		$category = get_taxonomy( 'category' );
		if ( $category ) {
			$category->facet_type  = self::get_facet_type( 'category' );
			$to_return['category'] = $category;
		}

		$formats = get_taxonomy( 'formats' );
		if ( $formats ) {
			$formats->facet_type  = self::get_facet_type( 'formats' );
			$to_return['formats'] = $formats;
		}

		$bylines = get_taxonomy( 'bylines' );
		if ( $bylines ) {
			$bylines->facet_type  = self::get_facet_type( 'bylines' );
			$to_return['bylines'] = $bylines;
		}

		$research_teams = get_taxonomy( 'research-teams' );
		if ( $research_teams ) {
			$research_teams->facet_type  = self::get_facet_type( 'research-teams' );
			$to_return['research-teams'] = $research_teams;
		}

		$regions_countries = get_taxonomy( 'regions-countries' );
		if ( $regions_countries ) {
			$regions_countries->facet_type  = self::get_facet_type( 'regions-countries' );
			$to_return['regions-countries'] = $regions_countries;
		}

		$to_return['years'] = (object) array(
			'name'       => 'years',
			'label'      => 'Years',
			'facet_type' => self::get_facet_type( 'years' ),
		);

		return $to_return;
	}

	/**
	 * Register the facets with ElasticPress query.
	 *
	 * @param array $taxonomies The taxonomies.
	 * @return array $taxonomies The taxonomies.
	 */
	public function register_facets( $taxonomies ) {
		return self::get_facets_settings();
	}

	/**
	 * Register the query vars.
	 *
	 * @hook prc_platform_rewrite_query_vars
	 * @param array $qvars The query vars.
	 * @return array $qvars The query vars.
	 */
	public function register_query_vars( $qvars ) {
		$qvars[] = 'ep_sort__by_date';
		$qvars[] = 'ep_filter_years';
		return $qvars;
	}

	/**
	 * Sort ElasticPress results by date.
	 *
	 * @hook ep_set_sort
	 * @param array  $sort The sort.
	 * @param string $order The order.
	 * @return array $sort The sort.
	 */
	public function sort_ep_by_date( $sort, $order ) {
		if ( ! get_query_var( 'ep_sort__by_date' ) ) {
			return $sort;
		}
		$sort = array(
			array(
				'post_date' => array(
					'order' => $order,
				),
			),
		);
		return $sort;
	}

	/**
	 * Restructure post_filter taxonomy statements to be more scoped. This matches OR inside taxonomy groups, and AND between taxonomy groups.
	 * This filter is especially formatted for elasticsearch queries.
	 *
	 * @hook ep_post_formatted_args
	 *
	 * @param array    $args The args.
	 * @param array    $query_args The query args.
	 * @param WP_Query $wp_query The WP query.
	 * @return array $args The args.
	 */
	public function add_filters_to_query( $args, $query_args, $wp_query ) {
		$years_filter = get_query_var( 'ep_filter_years' );
		$post_filter  = $args['post_filter'];

		// The taxonomy "should" statements that need to be restructured.
		if ( ! isset( $post_filter['bool']['must'][0]['bool']['should'] ) ) {
			return $args;
		}
		// Some sanity checks.

		$x = $post_filter['bool']['must'][0]['bool']['should'];
		if ( ! isset( $x ) ) {
			return $args;
		}
		if ( count( $x ) <= 1 ) {
			return $args;
		}
		$new = array();
		// Restructure the should statements so that they are grouped by taxonomy.
		foreach ( $x as $item ) {
			// Get the key of the first item in the ['terms'] array of item.
			$key = isset( $item['terms'] ) ? key( $item['terms'] ) : null;
			if ( null === $key ) {
				continue;
			}
			do_action( 'qm/debug', 'ElasticPress_Middleware::add_filters_to_query::key:' . print_r( $key, true ) );
			if ( 'terms.years.slug' === $key ) {
				$item = array(
					'term' => array(
						'date_terms.year' => $years_filter,
					),
				);
			}

			$new[] = array(
				'bool' => array(
					'should' => $item,
				),
			);
		}

		// Remove the old should statements.
		unset( $post_filter['bool']['must'][0]['bool']['should'] );
		// Add the new structured must/should statements.
		$post_filter['bool']['must'][0]['bool']['must'] = $new;

		do_action( 'qm/debug', 'ElasticPress_Middleware::add_filters_to_query::after post_filter:' . print_r( $post_filter, true ) );


		$args['post_filter'] = $post_filter;


		return $args;
	}

	/**
	 * This adds the date aggregation to the EP query.
	 *
	 * @hook ep_formatted_args
	 *
	 * @param array    $formatted_args The formatted args.
	 * @param array    $args The args.
	 * @param WP_Query $wp_query The WP query.
	 * @return array $formatted_args The formatted args.
	 */
	public function add_date_aggregations( $formatted_args, $args, $wp_query ) {
		do_action( 'qm/debug', 'ElasticPress_Middleware::add_date_aggregations::formatted_args:' . print_r( $formatted_args['post_filter'], true ) );
		// Add years aggregation.
		$formatted_args['aggs']['date_histogram'] = array(
			'filter' => $formatted_args['post_filter'],
			'aggs'   => array(
				'years' => array(
					'terms' => array(
						'field' => 'date_terms.year',
						'order' => array( '_key' => 'desc' ),
					),
				),
			),
		);
		return $formatted_args;
	}

	/**
	 * Based on https://github.com/Automattic/ElasticPress/blob/2675125bd32c08aa397e581d447de796010605b5/includes%2Fclasses%2FFeature%2FFacets%2FFacets.php#L361-L399
	 * Hacky. Save aggregation data for later in global space.
	 *
	 * @hook ep_valid_response
	 * @param  array $response ES response.
	 * @param  array $query Prepared Elasticsearch query.
	 * @param  array $query_args Current WP Query arguments.
	 * @param  mixed $query_object Could be WP_Query, WP_User_Query, etc.
	 * @since  2.5
	 */
	public function include_date_aggregation_in_response( $response, $query, $query_args, $query_object ) {
		if ( empty( $query_object ) || 'WP_Query' !== get_class( $query_object ) || ! $this->ep_facets->is_facetable( $query_object ) ) {
			return $response;
		}

		if ( isset( $response['aggregations']['date_histogram']['years']['buckets'] ) ) {
			$years = $response['aggregations']['date_histogram']['years']['buckets'] ?? array();

			$GLOBALS['ep_facet_aggs']['years'] = array();

			foreach ( $years as $bucket ) {
				$GLOBALS['ep_facet_aggs']['years'][ $bucket['key'] ] = $bucket['doc_count'];
			}
		}

		return $response;
	}
}
