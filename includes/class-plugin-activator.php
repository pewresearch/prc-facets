<?php
/**
 * Plugin activator.
 *
 * @package    PRC\Platform\Facets
 */

namespace PRC\Platform\Facets;

use DEFAULT_TECHNICAL_CONTACT;

/**
 * Plugin activator.
 *
 * @package    PRC\Platform\Facets
 */
class Plugin_Activator {

	public static function activate() {
		flush_rewrite_rules();

		wp_mail(
			DEFAULT_TECHNICAL_CONTACT,
			'PRC Facets Activated',
			'The PRC Facets plugin has been activated on ' . get_site_url()
		);
	}
}
