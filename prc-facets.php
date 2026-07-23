<?php
/**
 * PRC Facets
 *
 * @package           PRC_Facets
 * @author            Seth Rubenstein
 * @copyright         2024 Pew Research Center
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       PRC Facets
 * Plugin URI:        https://github.com/pewresearch/prc-facets
 * Description:       PRC Facets is a module for the PRC Platform that offers advanced faceted search and filtering capabilities. It uses ElasticPress (VIP Search) with form-input-* blocks from the PRC Block Library as user interface components.
 * Version:           1.0.0
 * Requires at least: 6.7
 * Requires PHP:      8.2
 * Author:            Seth Rubenstein
 * Author URI:        https://pewresearch.org
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       prc-facets
 * Requires Plugins:  prc-scripts, prc-publication-listing
 */

namespace PRC\Platform\Facets;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
define( 'PRC_FACETS_FILE', __FILE__ );
define( 'PRC_FACETS_DIR', __DIR__ );
define( 'PRC_FACETS_VERSION', '1.0.0' );

/**
 * Helper utilities
 */
require plugin_dir_path( __FILE__ ) . 'includes/utils.php';

/**
 * The core plugin class that is used to define the hooks that initialize the various components.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-plugin.php';

/**
 * Optional CLI utilities (WP-CLI only).
 */
if ( defined( 'WP_CLI' ) && class_exists( '\WP_CLI' ) ) {
	require plugin_dir_path( __FILE__ ) . 'includes/cli/class-cli-clean-facetwp.php';
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_prc_facets() {
	$plugin = new Plugin();
	$plugin->run();

	if ( defined( 'WP_CLI' ) && class_exists( '\WP_CLI' ) ) {
		if ( class_exists( '\PRC\Platform\Facets\CLI_Clean_FacetWP' ) ) {
			\WP_CLI::add_command( 'prc facets', new CLI_Clean_FacetWP() );
		}
	}
}
run_prc_facets();
