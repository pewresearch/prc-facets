<?php
// This file is generated. Do not modify it manually.
return array(
	'context-provider' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'prc-platform/facets-context-provider',
		'version' => '1.0.0',
		'title' => 'Facets Context Provider',
		'description' => 'Handles passing facets context to query blocks and facet UI blocks.',
		'category' => 'theme',
		'keywords' => array(
			'facets',
			'query',
			'loop'
		),
		'supports' => array(
			'anchor' => false,
			'html' => false,
			'reusable' => false,
			'multiple' => false,
			'interactivity' => array(
				'clientNavigation' => true
			)
		),
		'usesContext' => array(
			'postType',
			'templateSlug',
			'previewPostType',
			'facetsContextProvider'
		),
		'textdomain' => 'prc-facets-context-provider',
		'editorScript' => 'file:./index.js',
		'viewScriptModule' => 'file:./view.js',
		'style' => 'file:./style-index.css'
	),
	'results-info' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'prc-platform/facets-results-info',
		'version' => '1.0.0',
		'title' => 'Facets Results Info',
		'category' => 'theme',
		'description' => 'Display the number of results and the range of results being displayed.',
		'attributes' => array(
			
		),
		'supports' => array(
			'anchor' => true,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'color' => array(
				'text' => true,
				'link' => true
			),
			'spacing' => array(
				'blockGap' => true,
				'margin' => array(
					'top',
					'bottom'
				),
				'padding' => true,
				'__experimentalDefaultControls' => array(
					'padding' => true
				)
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true,
					'__experimentalFontFamily' => true
				)
			),
			'interactivity' => array(
				'clientNavigation' => true
			)
		),
		'usesContext' => array(
			'postType',
			'templateSlug',
			'previewPostType',
			'facetsContextProvider'
		),
		'textdomain' => 'facets-pager',
		'editorScript' => 'file:./index.js',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php',
		'viewScriptModule' => 'file:./view.js'
	),
	'search-relevancy' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'prc-platform/facet-search-relevancy',
		'version' => '0.1.0',
		'title' => 'Facet Search Relevancy',
		'category' => 'theme',
		'description' => 'Toggle search sorting by relevancy or by datetime',
		'attributes' => array(
			'allowedBlocks' => array(
				'type' => 'array',
				'default' => array(
					'prc-block/form-input-checkbox'
				)
			),
			'orientation' => array(
				'type' => 'string',
				'default' => 'vertical'
			)
		),
		'supports' => array(
			'anchor' => true,
			'html' => false,
			'multiple' => false,
			'spacing' => array(
				'blockGap' => true,
				'margin' => array(
					'top',
					'bottom'
				),
				'padding' => true,
				'__experimentalDefaultControls' => array(
					'padding' => true
				)
			),
			'typography' => array(
				'fontSize' => true,
				'__experimentalFontFamily' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true,
					'__experimentalFontFamily' => true
				)
			),
			'interactivity' => true
		),
		'textdomain' => 'facet-search-relevancy',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php',
		'viewScriptModule' => 'file:./view.js'
	),
	'select-field' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'prc-platform/facet-select-field',
		'version' => '2.0.0',
		'title' => 'Facet Select Field',
		'category' => 'widgets',
		'description' => 'Create a dropdown component with a list of options.',
		'attributes' => array(
			'placeholder' => array(
				'type' => 'string',
				'default' => 'Select an option'
			),
			'disabled' => array(
				'type' => 'boolean',
				'default' => false
			),
			'backgroundColor' => array(
				'type' => 'string',
				'default' => 'ui-white'
			),
			'textColor' => array(
				'type' => 'string',
				'default' => 'ui-black'
			)
		),
		'supports' => array(
			'anchor' => true,
			'html' => false,
			'reusable' => true,
			'inserter' => false,
			'__experimentalBorder' => array(
				'color' => true,
				'width' => true,
				'radius' => true
			),
			'color' => array(
				'gradients' => false
			),
			'spacing' => array(
				'padding' => true,
				'margin' => true
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true
			),
			'interactivity' => true
		),
		'usesContext' => array(
			'prc-facets/template/facetType',
			'prc-facets/template/facetName',
			'prc-facets/template/facetLabel'
		),
		'styles' => array(
			array(
				'name' => 'default',
				'label' => 'Default',
				'isDefault' => true
			)
		),
		'parent' => array(
			'prc-block/form-field'
		),
		'textdomain' => 'form-input-select',
		'editorScript' => 'file:./index.js',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	),
	'selected-tokens' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'prc-platform/facets-selected-tokens',
		'version' => '0.1.0',
		'title' => 'Facets Selected Tokens',
		'category' => 'theme',
		'description' => 'Display a list of selected facets styled as tokens.',
		'attributes' => array(
			'orientation' => array(
				'type' => 'string',
				'default' => 'vertical'
			),
			'tokenBorderColor' => array(
				'type' => 'string',
				'default' => 'ui-gray-light'
			),
			'tokenBackgroundColor' => array(
				'type' => 'string',
				'default' => 'ui-gray-very-light'
			)
		),
		'supports' => array(
			'anchor' => true,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'color' => array(
				'text' => true,
				'link' => true
			),
			'spacing' => array(
				'blockGap' => true,
				'margin' => array(
					'top',
					'bottom'
				),
				'padding' => true,
				'__experimentalDefaultControls' => array(
					'padding' => true
				)
			),
			'typography' => array(
				'fontSize' => true,
				'__experimentalFontFamily' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true,
					'__experimentalFontFamily' => true
				)
			),
			'interactivity' => array(
				'clientNavigation' => true
			)
		),
		'usesContext' => array(
			'postType',
			'templateSlug',
			'previewPostType',
			'facetsContextProvider'
		),
		'textdomain' => 'selected-tokens',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php',
		'viewScriptModule' => 'file:./view.js'
	),
	'template' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'prc-platform/facet-template',
		'version' => '2.0.0',
		'title' => 'Facet Template',
		'category' => 'theme',
		'description' => 'Display a facet given its slug and type as a block',
		'attributes' => array(
			'facetName' => array(
				'type' => 'string',
				'default' => ''
			),
			'facetType' => array(
				'type' => 'string',
				'enum' => array(
					'checkbox',
					'radio',
					'dropdown',
					'range',
					'search'
				)
			),
			'facetLabel' => array(
				'type' => 'string'
			),
			'facetLimit' => array(
				'type' => 'number',
				'default' => 10
			),
			'isInteractive' => array(
				'type' => 'boolean',
				'default' => true
			),
			'interactiveNamespace' => array(
				'type' => 'string',
				'default' => 'prc-platform/facets-context-provider'
			)
		),
		'supports' => array(
			'anchor' => true,
			'html' => false,
			'spacing' => array(
				'blockGap' => true,
				'margin' => array(
					'top',
					'bottom'
				),
				'padding' => true,
				'__experimentalDefaultControls' => array(
					'padding' => true
				)
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true,
					'__experimentalFontFamily' => true
				)
			),
			'interactivity' => true
		),
		'selectors' => array(
			'root' => '.wp-block-prc-platform-facet-template',
			'typography' => 'h5'
		),
		'usesContext' => array(
			'postType',
			'templateSlug',
			'previewPostType',
			'facetsContextProvider'
		),
		'providesContext' => array(
			'prc-facets/template/facetType' => 'facetType',
			'prc-facets/template/facetName' => 'facetName',
			'prc-facets/template/facetLabel' => 'facetLabel'
		),
		'styles' => array(
			array(
				'name' => 'default',
				'label' => 'Default',
				'isDefault' => true
			),
			array(
				'name' => 'no-label',
				'label' => 'No Label'
			)
		),
		'ancestor' => array(
			'prc-platform/facets-context-provider'
		),
		'textdomain' => 'facet-template',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'viewScriptModule' => 'file:./view.js'
	)
);
