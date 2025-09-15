<?php
/**
 * Server-side rendering of the `prc-platform/facets-results-info` block.
 *
 * @package PRC\Platform\Facets
 */

namespace PRC\Platform\Facets;

$block_wrapper_attrs = get_block_wrapper_attributes(
	array(
		'id'                  => wp_unique_id( 'prc-platform-facets-results-info-' ),
		'data-wp-interactive' => wp_json_encode(
			array(
				'namespace' => 'prc-platform/facets-context-provider',
			)
		),
	)
);

echo wp_sprintf(
	'<div %1$s><span data-wp-text="state.resultsText">Displaying 1-10 of ? results</span></div>',
	$block_wrapper_attrs, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
