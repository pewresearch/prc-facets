<?php
/**
 * Plugin deactivator.
 *
 * @package    PRC\Platform\Facets
 */

namespace PRC\Platform\Facets;

/**
 * Plugin deactivator.
 *
 * @package    PRC\Platform\Facets
 */
class Plugin_Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		flush_rewrite_rules(); // phpcs:ignore

		wp_mail(
			DEFAULT_TECHNICAL_CONTACT,
			'PRC Facets Deactivated',
			'The PRC Facets plugin has been deactivated on ' . get_site_url()
		);
	}
}
