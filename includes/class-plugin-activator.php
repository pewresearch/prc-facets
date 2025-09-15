<?php
/**
 * Plugin activator.
 *
 * @package    PRC\Platform\Facets
 */

namespace PRC\Platform\Facets;

/**
 * Plugin activator.
 *
 * @package    PRC\Platform\Facets
 */
class Plugin_Activator {

	/**
	 * Activate the plugin.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		flush_rewrite_rules(); // phpcs:ignore

		wp_mail(
			DEFAULT_TECHNICAL_CONTACT,
			'PRC Facets Activated',
			'The PRC Facets plugin has been activated on ' . get_site_url()
		);
	}
}
