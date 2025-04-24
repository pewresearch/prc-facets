<?php
/**
 * Facets Selected Tokens.
 *
 * @package PRC\Platform\Facets
 */

namespace PRC\Platform\Facets;

/**
 * Block Name:        Facet Selected Tokens
 * Description:       Display a list of selected, active facets as tokens
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Seth Rubenstein
 *
 * @package           prc-platform
 */
class Selected_Tokens {
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
			PRC_FACETS_BLOCKS_DIR . '/build/selected-tokens'
		);
	}
}
