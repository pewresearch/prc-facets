<?php
/**
 * Plugin deactivator.
 *
 * @package    PRC\Platform\Facets
 */

namespace PRC\Platform\Facets;

use DEFAULT_TECHNICAL_CONTACT;

/**
 * Plugin deactivator.
 *
 * @package    PRC\Platform\Facets
 */
class Plugin_Deactivator {

	public static function deactivate() {
		flush_rewrite_rules();

		wp_mail(
			DEFAULT_TECHNICAL_CONTACT,
			'PRC Facets Deactivated',
			'The PRC Facets plugin has been deactivated on ' . get_site_url()
		);
	}
}
