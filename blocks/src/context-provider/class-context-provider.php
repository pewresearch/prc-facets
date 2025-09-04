<?php
/**
 * Facets Context Provider.
 *
 * @package PRC\Platform\Facets
 */

namespace PRC\Platform\Facets;

/**
 * This class is responsible for providing both a server level and client level context for facets data. This supplies facet data to innerblocks within. The server level context is used to pre-fetch data and the client level context is used to manage the state of the facets.
 */
class Context_Provider {
	/**
	 * The facets data.
	 *
	 * @var array
	 */
	public $facets = false;

	/**
	 * The pagination data.
	 *
	 * @var array
	 */
	public $pagination = false;

	/**
	 * The selected facets.
	 *
	 * @var array
	 */
	public $selected = array();

	/**
	 * The constructor.
	 *
	 * @param string $loader The loader.
	 */
	public function __construct( $loader ) {
		$this->init( $loader );
	}

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $loader The loader.
	 */
	public function init( $loader = null ) {
		if ( null !== $loader ) {
			$loader->add_action( 'init', $this, 'block_init' );
			$loader->add_filter( 'pre_render_block', $this, 'hoist_facet_data_to_pre_render_stage', 10, 3 );
			$loader->add_filter( 'render_block_context', $this, 'add_facet_data_to_context', 10, 2 );
		}
	}

	/**
	 * Register the block.
	 *
	 * @hook init
	 */
	public function block_init() {
		register_block_type_from_metadata(
			PRC_FACETS_BLOCKS_DIR . '/build/context-provider',
			array(
				'render_callback' => array( $this, 'render_block_callback' ),
			)
		);
	}

	/**
	 * Fetch the facets data ONCE and store on server memory.
	 * Later, we'll make this data accessible via block context in the add_facet_data_to_context method.
	 *
	 * @hook pre_render_block
	 *
	 * @param mixed $pre_render The pre render.
	 * @param mixed $parsed_block The parsed block.
	 * @param mixed $parent_block_instance The parent block instance.
	 * @return null
	 */
	public function hoist_facet_data_to_pre_render_stage( $pre_render, $parsed_block, $parent_block_instance ) {
		if ( 'prc-platform/facets-context-provider' === $parsed_block['blockName'] ) {
			global $wp_query;
			if ( use_ep_facets() ) {
				$facets_api       = new ElasticPress_Facets_API( $wp_query->query );
				$this->facets     = $facets_api->get_facets();
				$this->pagination = $facets_api->get_pagination();
				$this->selected   = $facets_api->selected;
			} else {
				$facetwp_api      = new FacetWP_API( $wp_query->query );
				$this->facets     = $facetwp_api->get_facets();
				$this->pagination = $facetwp_api->get_pagination();
				$this->selected   = $facetwp_api->selected;
			}
		}
		return null;
	}

	/**
	 * Construct structured tokens from selected facets.
	 *
	 * @return array
	 */
	public function get_tokens_from_selected_facets() {
		$tokens = array();
		foreach ( $this->selected as $selected_facet => $selected_values ) {
			foreach ( $selected_values as $selected_value ) {
				$tokens[] = array(
					'value' => $selected_facet,
					'slug'  => sanitize_title( $selected_value ),
					'label' => ucwords( str_replace( array( '-', '_' ), ' ', $selected_value ) ),
				);
			}
		}
		return $tokens;
	}

	/**
	 * Get the facet data from server memory and apply it to the block context for the context provider, facet template, and selected tokens blocks.
	 *
	 * @hook render_block_context
	 *
	 * @param mixed $context The context.
	 * @param mixed $parsed_block The parsed block.
	 * @return mixed
	 */
	public function add_facet_data_to_context( $context, $parsed_block ) {
		if ( ! in_array(
			$parsed_block['blockName'],
			array(
				'prc-platform/facets-context-provider',
				'prc-platform/facet-template',
			)
		) ) {
			return $context;
		}

		$context['prc-platform/facets-context-provider'] = array(
			'selected'     => (object) $this->selected,
			'tokens'       => $this->get_tokens_from_selected_facets(),
			'facets'       => $this->facets,
			'pagination'   => $this->pagination,
			'prefetched'   => array(),
			'isProcessing' => false,
			'isDisabled'   => false,
			'urlKey'       => use_ep_facets() ? 'ep_filter_' : '_', // This is the key that is used to store the facet data in the url.
		);

		return $context;
	}

	/**
	 * Render the block callback.
	 *
	 * @param mixed $attributes The attributes.
	 * @param mixed $content The content.
	 * @param mixed $block The block.
	 * @return mixed
	 */
	public function render_block_callback( $attributes, $content, $block ) {
		wp_enqueue_script( 'wp-url' );
		wp_enqueue_script( 'wp-api-fetch' );

		// Add facet data into client memory.
		wp_interactivity_state(
			'prc-platform/facets-context-provider',
			$block->context['prc-platform/facets-context-provider']
		);

		// Find the core/query block and add directives to watch for new facet changes, EP sorting changes, and a class for when facets are processing.
		$tag = new \WP_HTML_Tag_Processor( $content );
		while ( $tag->next_tag() ) {
			if ( $tag->has_class( 'wp-block-query' ) ) {
				$tag->set_attribute( 'data-wp-class--is-processing', 'prc-platform/facets-context-provider::state.isProcessing' );
				$tag->set_attribute( 'data-wp-watch--on-selection', 'prc-platform/facets-context-provider::callbacks.onSelection' );
				$tag->set_attribute( 'data-wp-watch--on-ep-sort-by-update', 'prc-platform/facets-context-provider::callbacks.onEpSortByUpdate' );
			}
			if ( $tag->has_class( 'wp-block-prc-block-tokens-list' ) ) {
				$tag->set_attribute( 'data-wp-class--is-processing', 'prc-platform/facets-context-provider::state.isProcessing' );
			}
			if ( $tag->has_class( 'wp-block-prc-platform-facets-results-info' ) ) {
				$tag->set_attribute( 'data-wp-class--is-processing', 'prc-platform/facets-context-provider::state.isProcessing' );
			}
		}
		$content = $tag->get_updated_html();

		return wp_sprintf(
			'<div %1$s>%2$s</div>',
			get_block_wrapper_attributes(
				array(),
			),
			$content,
		);
	}
}
