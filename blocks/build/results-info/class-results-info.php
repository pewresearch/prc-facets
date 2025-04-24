<?php
/**
 * Facets Results Info.
 *
 * @package PRC\Platform\Facets
 */

namespace PRC\Platform\Facets;

/**
 * Block Name:        Facet Results info
 * Description:       Display the number of results and the range of results being displayed
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Seth Rubenstein
 *
 * @package           prc-platform
 */

class Results_Info {
	public function __construct( $loader ) {
		$this->init( $loader );
	}

	public function init( $loader = null ) {
		if ( null !== $loader ) {
			$loader->add_action( 'init', $this, 'block_init' );
		}
	}

	/**
	 * @hook init
	 * @return void
	 */
	public function block_init() {
		register_block_type_from_metadata(
			PRC_FACETS_BLOCKS_DIR . '/build/results-info'
		);
	}
}
