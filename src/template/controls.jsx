/**
 * External Dependencies
 */

/**
 * WordPress Dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment, useState, useEffect, useMemo } from '@wordpress/element';
import { BlockControls, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	__experimentalNumberControl as NumberControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { createBlocksFromInnerBlocksTemplate } from '@wordpress/blocks';

/**
 * Internal Dependencies
 */

const getTemplateForType = (type, name) => {
	const defaultAttrs = {
		interactiveNamespace: 'prc-platform/facets-context-provider',
	};
	const label = `${name
		.replace(/_/g, ' ')
		.replace(/\w\S*/g, (w) =>
			w.replace(/^\w/, (c) => c.toUpperCase())
		)} Value`;
	switch (type) {
		case 'checkbox':
			return [
				[
					'prc-block/form-input-checkbox',
					{
						type: 'checkbox',
						label,
						interactiveSubsumption: true,
						...defaultAttrs,
					},
				],
			];
		case 'dropdown':
			return [
				[
					'prc-block/form-input-select',
					{
						placeholder: label,
						displayLabel: false,
						metadata: {
							name,
						},
						interactiveSubsumption: true,
						...defaultAttrs,
					},
				],
			];
		case 'range':
			return [
				[
					'prc-block/form-input-select',
					{
						placeholder: label,
						displayLabel: false,
						metadata: {
							name,
						},
						interactiveSubsumption: true,
						...defaultAttrs,
					},
				],
				[
					'prc-block/form-input-select',
					{
						placeholder: label,
						displayLabel: false,
						metadata: {
							name,
						},
						interactiveSubsumption: true,
						...defaultAttrs,
					},
				],
			];
		case 'search':
			return [
				[
					'prc-block/form-input-text',
					{
						type: 'text',
						label,
						interactiveSubsumption: true,
						...defaultAttrs,
					},
				],
			];
		default:
			// Default to Radio
			return [
				[
					'prc-block/form-input-checkbox',
					{
						type: 'radio',
						label,
						interactiveSubsumption: true,
						...defaultAttrs,
					},
				],
			];
	}
};

export default function Controls({
	attributes,
	setAttributes,
	context,
	clientId,
}) {
	const { replaceInnerBlocks } = useDispatch('core/block-editor');

	const { facetName, facetLabel, facetType, facetLimit } = attributes;

	const { facetsContextProvider } = context;

	const options = useMemo(() => {
		if (!facetsContextProvider) {
			return [
				{
					label: 'No Facets Found',
					value: '',
				},
			];
		}
		// console.log('facetsContextProvider', facetsContextProvider);
		const newOptions = [
			{
				label: 'Select a Facet',
				value: '',
			},
		];
		Object.keys(facetsContextProvider).forEach((facetKey) => {
			newOptions.push({
				label: facetsContextProvider[facetKey].label,
				value: facetsContextProvider[facetKey].name,
			});
		});
		return newOptions;
	}, [facetsContextProvider]);

	return (
		<InspectorControls>
			<PanelBody title="Facet Template">
				<div>
					<SelectControl
						label="Facet"
						help="Select a facet registered with the PRC Platform. Updating this will reset the template and any style changes."
						options={options}
						value={facetName}
						onChange={(value) => {
							const name = value;
							console.log("FACET SELECTED:", facetsContextProvider[name]);
							const { type, label } = facetsContextProvider[name];
							setAttributes({
								facetName: name,
								facetType: type,
								facetLabel: label,
							});
							const defaultTemplate = getTemplateForType(
								type,
								name
							);
							replaceInnerBlocks(
								clientId,
								createBlocksFromInnerBlocksTemplate(
									defaultTemplate
								),
								false
							);
						}}
					/>
					<NumberControl
						label="Limit"
						help="Number of choices to display. Additional choices will be hidden behind a 'More' button."
						value={facetLimit}
						onChange={(value) =>
							setAttributes({ facetLimit: value })
						}
					/>
				</div>
			</PanelBody>
		</InspectorControls>
	);
}
