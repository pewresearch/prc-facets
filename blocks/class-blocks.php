<?php
/**
 * The dataset blocks class.
 *
 * @package PRC\Platform\Facets
 */

namespace PRC\Platform\Facets;

/**
 * The dataset blocks class.
 */
class Blocks {
	/**
	 * Constructor.
	 *
	 * @param object $loader The loader object.
	 */
	public function __construct( $loader ) {
		require_once PRC_FACETS_BLOCKS_DIR . '/build/context-provider/class-context-provider.php';
		require_once PRC_FACETS_BLOCKS_DIR . '/build/results-info/class-results-info.php';
		require_once PRC_FACETS_BLOCKS_DIR . '/build/search-relevancy/class-search-relevancy.php';
		require_once PRC_FACETS_BLOCKS_DIR . '/build/template/class-template.php';

		$this->init( $loader );
	}

	/**
	 * Initialize the class.
	 */
	public function init( $loader ) {
		wp_register_block_metadata_collection(
			PRC_FACETS_BLOCKS_DIR . '/build',
			PRC_FACETS_BLOCKS_DIR . '/build/blocks-manifest.php'
		);

		new Context_Provider( $loader );
		new Results_Info( $loader );
		new Search_Relevancy( $loader );
		new Template( $loader );
	}
}
