<?php
/**
 * Plugin class.
 *
 * @package    PRC\Platform\Facets
 */

namespace PRC\Platform\Facets;

use WP_Error;

/**
 * Plugin class.
 *
 * @package    PRC\Platform\Facets
 */
class Plugin {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the platform as initialized by hooks.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->version     = '1.0.0';
		$this->plugin_name = 'prc-facets';

		$this->load_dependencies();
		$this->init_dependencies();
	}


	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		// Load plugin loading class.
		require_once plugin_dir_path( __DIR__ ) . '/includes/class-loader.php';

		// Initialize the loader.
		$this->loader = new Loader();

		// Include middleware for FacetWP and ElasticPress.
		require_once plugin_dir_path( __DIR__ ) . '/includes/providers/facet-wp/class-facetwp-middleware.php';
		require_once plugin_dir_path( __DIR__ ) . '/includes/providers/elasticpress/class-elasticpress-middleware.php';
		require_once plugin_dir_path( __DIR__ ) . '/includes/class-rest-api.php';

		require_once PRC_FACETS_DIR . '/build/context-provider/class-context-provider.php';
		require_once PRC_FACETS_DIR . '/build/results-info/class-results-info.php';
		require_once PRC_FACETS_DIR . '/build/search-relevancy/class-search-relevancy.php';
		require_once PRC_FACETS_DIR . '/build/template/class-template.php';
	}

	/**
	 * Initialize the dependencies.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function init_dependencies() {
		new Rest_API( $this->get_loader() );
		new ElasticPress_Middleware( $this->get_loader() );
		// Soft-cutover: keep FacetWP plugins installed but dormant — do not
		// register FacetWP_Middleware. Disable FacetWP main-query engagement.
		$this->loader->add_filter( 'facetwp_is_main_query', $this, 'disable_facetwp_main_query', 10, 2 );

		\wp_register_block_metadata_collection(
			PRC_FACETS_DIR . '/build',
			PRC_FACETS_DIR . '/build/blocks-manifest.php'
		);

		new Context_Provider( $this->get_loader() );
		new Results_Info( $this->get_loader() );
		new Search_Relevancy( $this->get_loader() );
		new Template( $this->get_loader() );

		// Disable WordPress date archives - faceted search handles date filtering instead.
		$this->loader->add_action( 'template_redirect', $this, 'disable_date_archives' );
	}

	/**
	 * Soft-cutover: FacetWP remains installed but must not own the main query.
	 *
	 * @hook facetwp_is_main_query
	 *
	 * @param bool     $is_main_query Whether FacetWP considers this the main query.
	 * @param \WP_Query $query         The query.
	 * @return bool
	 */
	public function disable_facetwp_main_query( $is_main_query, $query ) {
		unset( $is_main_query, $query );
		return false;
	}

	/**
	 * Disable WordPress date archives (month, day only).
	 *
	 * PRC uses faceted search for date-based filtering instead of WordPress's
	 * built-in date archives. This prevents thin content pages and ensures
	 * all date-based navigation goes through the faceted search system.
	 *
	 * Note: Year archives are excluded here because they are redirected to
	 * /publications/?_years=YYYY by prc-platform-core's Permalink_Rewrites class.
	 *
	 * @hook template_redirect
	 * @return void
	 */
	public function disable_date_archives() {
		// Only 404 month and day archives; year archives are redirected by prc-platform-core.
		if ( is_month() || is_day() ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
		}
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    PRC\Platform\Facets\Loader
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
