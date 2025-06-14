/**
 * WordPress Dependencies
 */
import {
	store,
	getContext,
	getServerState,
	withSyncEvent,
	getElement,
} from '@wordpress/interactivity';

/**
 * Internal Dependencies
 */

function formatCount(count) {
	return 250 <= count ? '250+' : count;
}

function getPropertyFromObjects(property, searchValue, values) {
	const choice = values.find((c) => c.value === searchValue);
	return choice ? choice[property] : null;
}

const { state, actions } = store('prc-platform/facets-context-provider', {
	state: {
		get choices() {
			const context = getContext();
			const { facetSlug, limit, facetType } = context;
			if (!facetSlug) {
				return [];
			}
			if (!state.facets[facetSlug]) {
				return [];
			}
			if ('dropdown' === facetType) {
				console.log(
					'construct dropdown choices for ',
					facetSlug,
					state.facets[facetSlug],
					{
						...context,
					}
				);
				return [...state.facets[facetSlug].choices].sort((a, b) => {
					// Check if both values are numbers
					const aNum = Number(a.label);
					const bNum = Number(b.label);
					if (!isNaN(aNum) && !isNaN(bNum)) {
						return bNum - aNum; // Reversed the order
					}
					// Otherwise sort alphabetically in reverse
					return b.label.localeCompare(a.label); // Reversed the order
				});
			}
			const choices = state.facets[facetSlug].choices;
			// Sort choices to put selected ones first and then by count
			const sortedChoices = [...choices].sort((a, b) => {
				const aSelected = state.facets[facetSlug].selected.includes(
					a.value
				);
				const bSelected = state.facets[facetSlug].selected.includes(
					b.value
				);
				// First sort by selection status
				if (aSelected !== bSelected) {
					return bSelected - aSelected; // Selected items come first
				}
				// Then sort by count
				return b.count - a.count;
			});
			return sortedChoices.slice(0, limit);
		},
		get expandedChoices() {
			const context = getContext();
			const { facetSlug } = context;
			if (!state.facets[facetSlug]) {
				return [];
			}
			const choices = state.facets[facetSlug].choices;
			// Filter out both selected choices and choices already in the main list
			const filteredChoices = choices.filter((choice) => {
				const isSelected = state.facets[facetSlug].selected.includes(
					choice.value
				);
				const isInMainList = state.choices.includes(choice);
				return !isSelected && !isInMainList;
			});
			// Sort remaining choices by count
			return filteredChoices.sort((a, b) => b.count - a.count);
		},
		get hasChoices() {
			const context = getContext();
			const { facetSlug } = context;
			const { facets } = state;
			return facets[facetSlug].choices.length > 0;
		},
		get hasSelections() {
			const context = getContext();
			const { facetSlug } = context;
			const { facets } = state;
			return facets[facetSlug].selected.length > 0;
		},
		get hasExpandedChoices() {
			return state.expandedChoices && state.expandedChoices.length > 0;
		},
		get isExpanded() {
			const context = getContext();
			const { expanded } = context;
			return expanded;
		},
		// Input Elements State:
		get isInputChecked() {
			const context = getContext();
			const { choice, facetSlug } = context;
			const { value } = choice;
			const { facets } = state;
			return facets[facetSlug].selected.includes(value);
		},
		get isInputDisabled() {
			const context = getContext();
			const { choice } = context;
			const { value, label, facetSlug } = choice;
			const count = getPropertyFromObjects(
				'count',
				value,
				state.facets[facetSlug].choices
			);
			if (count === 0) {
				return true;
			}
			return false;
		},
		get isInputError() {
			return false;
		},
		get isInputSuccess() {
			return false;
		},
		get isInputRequired() {
			return false;
		},
		get isInputProcessing() {
			return false;
		},
		get isInputHidden() {
			return false;
		},
		get isInputReadOnly() {
			return false;
		},
		get inputName() {
			const context = getContext();
			const { choice } = context;
			return choice.slug;
		},
		get inputLabel() {
			const context = getContext();
			const { choice } = context;
			const { value, label, facetSlug } = choice;
			const count = getPropertyFromObjects(
				'count',
				value,
				state.facets[facetSlug].choices
			);
			return `${label} (${formatCount(count)})`;
		},
		get inputId() {
			const context = getContext();
			const { choice } = context;
			const { slug, term_id } = choice;
			return `facet_${slug}_${term_id}`;
		},
		get inputValue() {
			const context = getContext();
			const { choice } = context;
			return choice.value;
		},
		get inputPlaceholder() {
			const context = getContext();
			const { placeholder, facetType, facetSlug } = context;
			let _placeholder = `Select ${placeholder}`;
			if (
				'dropdown' === facetType &&
				state.facets[facetSlug].selected.length > 0
			) {
				_placeholder = state.facets[facetSlug].selected[0]
					.replace(/-/g, ' ')
					.replace(/\b\w/g, (l) => l.toUpperCase());
			}
			return _placeholder;
		},
		get isSelected() {
			const context = getContext();
			const { choice } = context;
			const { tokens } = state;
			const { value } = choice;
			return tokens.includes(value);
		},
		/**
		 * For Form-Input-Select Exclusively:
		 */
		get isOpen() {
			const { expanded } = getContext();
			return expanded;
		},
		get inputOptions() {
			const context = getContext();
			const { facetSlug, facetType, searchTerm } = context;
			const { choices } = getServerState().facets[facetSlug];
			let newChoices = choices;

			// Filter choices based on search term.
			if (searchTerm && searchTerm.length > 0 && choices) {
				const filteredChoices = choices.filter((choice) =>
					choice.label
						.toLowerCase()
						.includes(searchTerm.toLowerCase())
				);
				if (filteredChoices.length > 0) {
					newChoices = filteredChoices;
				} else {
					newChoices = choices;
				}
			}
			// Return the choices as an array of objects with the following properties:
			// - value: The value of the choice.
			// - label: The label of the choice.
			return newChoices.map((choice) => {
				return {
					value: choice.value,
					label: `${choice.label} (${formatCount(choice.count)})`,
				};
			});
		},
		get hasValue() {
			const context = getContext();
			const { facetSlug } = context;
			const { selected } = state.facets[facetSlug];
			return selected.length > 0;
		},
		get hasClearIcon() {
			return false;
		},
		get activeIndex() {
			const context = getContext();
			const { activeIndex } = context;
			return activeIndex || 0;
		},
	},
	actions: {
		/**
		 * When clicking on the clear button, clear the facet from the selections.
		 */
		clearFacet: () => {
			const { facetSlug } = getContext();
			actions.onClear(facetSlug);
		},
		*onInputMouseEnter() {
			const context = getContext();
			const { facetSlug, value, selected } = context;
			const currentSelected = selected || [];
			const nextSelected = { ...currentSelected, [value]: value };
			const nextUrl = actions.constructNewUrl(nextSelected);
			// Prefetch the possible next url.
			yield actions.prefetch(nextUrl);
		},
		onInputMouseLeave: withSyncEvent((event) => {
			// console.log('onInputMouseLeave', event);
		}),
		onInputFocus: withSyncEvent((event) => {
			const context = getContext();
			const { facetType } = context;
			if ('dropdown' === facetType) {
				context.expanded = !context.expanded;
			}
		}),
		onInputBlur: withSyncEvent((event) => {
			// By default this runs on the on-blur directive on the input element
			// but we also use it as a shortcut to close the listbox on click,

			// Because the on-blur event fires before the click event
			// we need to slow things down a bit, 150 ms should do it...
			let isRunning = false;
			if (!isRunning) {
				isRunning = true;
				const context = getContext();
				const { facetType } = context;
				if ('dropdown' === facetType) {
					setTimeout(() => {
						context.expanded = false;
						isRunning = false;
					}, 150);
				}
			}
		}),
		onInputKeyDown: withSyncEvent((event) => {
			event.preventDefault();
			const context = getContext();
			if ('dropdown' !== context.facetType) {
				return;
			}
			if (event.key === 'Enter') {
				const { activeIndex, facetSlug } = context;
				const { inputOptions, selected } = state;
				const { value } = inputOptions[activeIndex];

				selected[facetSlug] = [value];

				setTimeout(() => {
					context.expanded = false;
				}, 150);
			}
		}),
		onInputKeyUp: withSyncEvent((event) => {
			const context = getContext();
			const { expanded, facetType } = context;
			if ('dropdown' !== facetType) {
				return;
			}
			// Update the search term.
			context.searchTerm = event.target.value;

			if (!expanded) {
				context.expanded = true;
			}
			if (event.keyCode === 40 && event.key === 'ArrowDown') {
				actions.moveThroughChoices(1, event.target);
				return;
			}
			if (event.keyCode === 38 && event.key === 'ArrowUp') {
				actions.moveThroughChoices(-1, event.target);
				return;
			}

			// if escape key, close the listbox
			if (event.key === 'Escape') {
				context.expanded = false;
			}
		}),
		moveThroughChoices: (direction, ref) => {
			const { inputOptions } = state;
			const { activeIndex } = getContext();

			// Determine next active index.
			let nextActive = null;
			if (activeIndex === null || isNaN(activeIndex)) {
				nextActive = 0;
			} else {
				nextActive = activeIndex + direction;
			}
			if (nextActive < 0) {
				nextActive = inputOptions.length - 1;
			}
			if (nextActive >= inputOptions.length) {
				nextActive = 0;
			}

			// Get the next active value.
			const nextActiveValue = inputOptions[nextActive].value;
			// And then scroll the listbox to the active item.
			const listbox = ref.parentElement.parentElement.querySelector(
				'.wp-block-prc-block-form-input-select__list'
			);
			const activeItem = listbox.querySelector(
				`[data-ref-value="${nextActiveValue}"]`
			);
			if (activeItem) {
				// Remove the active class from the previous active item.
				const previousActive = listbox.querySelector('.is-selected');
				if (previousActive) {
					previousActive.classList.remove('is-selected');
				}
				activeItem.classList.add('is-selected');
				activeItem.scrollIntoView({
					block: 'nearest',
				});
			}

			getContext().activeIndex = nextActive;
		},
		onInputOptionClick: withSyncEvent((event) => {
			const context = getContext();
			const { facetSlug, choice } = context;
			const { selected } = state;
			const { value } = choice;
			selected[facetSlug] = [value];
			console.log('onInputOptionClick', { ...context }, value, selected);
		}),
		onInputCheckboxClick: withSyncEvent((event) => {
			const context = getContext();
			const { choice, facetSlug } = context;
			const { selected } = state;
			const { value, type } = choice;
			if (state.isInputDisabled) {
				return;
			}
			if (!selected[facetSlug] || selected[facetSlug].length === 0) {
				selected[facetSlug] = [value];
			} else if (selected[facetSlug].includes(value)) {
				selected[facetSlug] = selected[facetSlug].filter(
					(item) => item !== value
				);
			} else if ('radio' === type) {
				selected[facetSlug] = [value];
			} else {
				selected[facetSlug] = [...selected[facetSlug], value];
			}
		}),
		/**
		 * When clicking on the facet expanded button, toggle the expanded state.
		 */
		onExpand: () => {
			const context = getContext();
			context.expanded = !context.expanded;
		},
		onCollapse: () => {
			// By default this runs on the on-blur directive on the input element
			// but we also use it as a shortcut to close the listbox on click,
			// Because the on-blur event fires before the click event
			// we need to slow things down a bit, 150 ms should do it...
			const context = getContext();
			let isRunning = false;
			if (!isRunning) {
				isRunning = true;
				setTimeout(() => {
					context.expanded = false;
					isRunning = false;
				}, 150);
			}
		},
	},
	callbacks: {
		/**
		 * When the facet is expanded, update the label to be either More or Less.
		 */
		onExpand: () => {
			const context = getContext();
			const { expanded } = context;
			if (expanded) {
				context.expandedLabel = '- Less';
			} else {
				context.expandedLabel = '+ More';
			}
		},
	},
});
