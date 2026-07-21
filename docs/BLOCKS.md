# PRC Facets Blocks Documentation

Comprehensive documentation for all Gutenberg blocks in the PRC Facets plugin.

## Table of Contents

- [Block Overview](#block-overview)
- [Facets Context Provider Block](#facets-context-provider-block)
- [Facet Template Block](#facet-template-block)
- [Results Info Block](#results-info-block)
- [Search Relevancy Block](#search-relevancy-block)
- [Block Interactions](#block-interactions)
- [Styling and Customization](#styling-and-customization)

## Block Overview

The PRC Facets plugin provides four specialized Gutenberg blocks that work together to create a complete faceting interface:

| Block Name              | Purpose                                    | Version |
| ----------------------- | ------------------------------------------ | ------- |
| Facets Context Provider | Manages facet state and context            | v1.0.0  |
| Facet Template          | Renders individual facet interfaces        | v2.5.0  |
| Results Info            | Displays result counts and pagination info | v1.0.0  |
| Search Relevancy        | Toggle between relevancy and date sorting  | v0.1.0  |

## Facets Context Provider Block

### Block Registration

- **Name**: `prc-platform/facets-context-provider`
- **Category**: `theme`
- **Version**: `1.0.0`

### Description

The Context Provider block is the parent container that manages facet state and provides context to all child facet blocks. It must be present on the page for facets to function.

### Attributes

This block has no user-configurable attributes. It automatically detects context from the page template.

### Context Usage

**Uses Context:**

- `postType` - Current post type being queried
- `templateSlug` - Current template identifier
- `previewPostType` - Post type in preview mode
- `facetsContextProvider` - Parent context if nested

**Provides Context:**

- Facet state and configuration to all child blocks
- Selected facet values
- Query parameters
- Provider state (ElasticPress; `isDisabled` when ES degraded)

### Supports

```json
{
	"anchor": false,
	"html": false,
	"reusable": false,
	"multiple": false, // Only one instance per page
	"interactivity": {
		"clientNavigation": true
	}
}
```

### Usage Example

```html
<!-- wp:prc-platform/facets-context-provider -->
<!-- wp:prc-platform/facet-template /-->
<!-- wp:query /-->
<!-- wp:prc-platform/facets-results-info /-->
<!-- /wp:prc-platform/facets-context-provider -->
```

### JavaScript API

The block uses WordPress Interactivity API for client-side state management.

## Facet Template Block

### Block Registration

- **Name**: `prc-platform/facet-template`
- **Category**: `theme`
- **Version**: `2.5.0`

### Description

Renders individual facet interfaces based on configuration. Supports multiple facet types with customizable appearance and behavior.

### Attributes

| Attribute              | Type   | Default                                  | Description                                                                 |
| ---------------------- | ------ | ---------------------------------------- | --------------------------------------------------------------------------- |
| `facetName`            | string | `""`                                     | Unique identifier for the facet (e.g., 'categories', 'authors')             |
| `facetType`            | string | -                                        | Type of facet interface: `checkbox`, `radio`, `dropdown`, `range`, `search` |
| `facetLabel`           | string | -                                        | Display label for the facet                                                 |
| `facetLimit`           | number | `10`                                     | Maximum number of facet values to display initially                         |
| `interactiveNamespace` | string | `"prc-platform/facets-context-provider"` | Namespace for Interactivity API                                             |

### Facet Types

#### Checkbox Facet

Multiple selection with checkboxes:

```html
<!-- wp:prc-platform/facet-template {
  "facetName":"categories",
  "facetType":"checkbox",
  "facetLabel":"Topics",
  "facetLimit":20
} /-->
```

#### Radio Facet

Single selection with radio buttons:

```html
<!-- wp:prc-platform/facet-template {
  "facetName":"sort_order",
  "facetType":"radio",
  "facetLabel":"Sort By"
} /-->
```

#### Dropdown Facet

Compact single selection:

```html
<!-- wp:prc-platform/facet-template {
  "facetName":"research_teams",
  "facetType":"dropdown",
  "facetLabel":"Research Teams"
} /-->
```

#### Range Facet

Numeric or date range selection:

```html
<!-- wp:prc-platform/facet-template {
  "facetName":"date_range",
  "facetType":"range",
  "facetLabel":"Date Range"
} /-->
```

#### Search Facet

Text-based filtering:

```html
<!-- wp:prc-platform/facet-template {
  "facetName":"keyword",
  "facetType":"search",
  "facetLabel":"Search Keywords"
} /-->
```

### Supports

```json
{
	"anchor": true,
	"html": false,
	"interactivity": true,
	"spacing": {
		"blockGap": true,
		"margin": ["top", "bottom"],
		"padding": true
	},
	"__experimentalBorder": {
		"color": true,
		"width": true,
		"radius": true
	},
	"typography": {
		"fontSize": true,
		"lineHeight": true,
		"__experimentalFontFamily": true,
		"__experimentalFontWeight": true
	}
}
```

### Styles

Two predefined styles:

1. **Default** - Shows facet label
2. **No Label** - Hides the facet label

### Context Requirements

Must be nested within `prc-platform/facets-context-provider` block.

### CSS Selectors

```css
.wp-block-prc-platform-facet-template {
	/* Root element */
}
.wp-block-prc-platform-facet-template h5 {
	/* Typography target */
}
```

## Results Info Block

### Block Registration

- **Name**: `prc-platform/facets-results-info`
- **Category**: `theme`
- **Version**: `1.0.0`

### Description

Displays information about current search/filter results including total count and pagination details.

### Display Format

```
Showing 1-10 of 245 results
```

### Dynamic Updates

Automatically updates when:

- Facets are selected/deselected
- Page changes
- New search is performed

### Supports

```json
{
	"anchor": true,
	"color": {
		"text": true,
		"background": true
	},
	"spacing": {
		"margin": true,
		"padding": true
	}
}
```

### Usage Example

```html
<!-- wp:prc-platform/facets-results-info {} /-->
```

## Search Relevancy Block

### Block Registration

- **Name**: `prc-platform/facet-search-relevancy`
- **Category**: `theme`
- **Version**: `0.1.0`

### Description

Provides a toggle control for sorting search results by relevancy or date.

### Attributes

| Attribute       | Type   | Default        | Description                                    |
| --------------- | ------ | -------------- | ---------------------------------------------- |
| `orientation`   | string | `"horizontal"` | Layout orientation: `vertical` or `horizontal` |
| `allowedBlocks` | array  | `[]`           | Blocks allowed within the relevancy toggle     |

### Supports

```json
{
	"anchor": true,
	"spacing": {
		"margin": true,
		"padding": true
	}
}
```

### Usage Example

```html
<!-- wp:prc-platform/facet-search-relevancy {} /-->
```

### Client-Side Behavior

Uses Interactivity API to:

- Toggle between sort modes without page reload
- Update URL parameters
- Trigger result refresh

## Block Interactions

### Data Flow

```
Context Provider
    ├── Facet Template(s)
    │   └── Renders facet UI
    ├── Query Block
    │   └── Displays filtered results
    └── Results Info
        └── Shows result statistics
```

### Event Handling

#### Facet Selection

1. User selects facet value
2. Facet Template updates local state
3. Context Provider receives update
4. Query re-runs with new parameters
5. Results Info updates counts
6. core/query refreshes results

#### URL Synchronization

- Facet selections are reflected in URL parameters
- Browser back/forward navigation works
- Shareable filtered URLs

### Performance Optimization

1. **Lazy Loading**: Facet values load on-demand
2. **Debounced Search**: Search facets wait for user to stop typing
3. **Virtual Scrolling**: Large facet lists use virtual scrolling
4. **Request Caching**: Duplicate requests are cached

## Troubleshooting

### Common Issues

1. **Facets not appearing**
    - Ensure Context Provider block is present
    - Check facetName matches registered facet
    - Verify provider is active

2. **Facets not updating results**
    - Check Query block is within Context Provider
    - Verify JavaScript is loading correctly
    - Check browser console for errors

3. **Styling issues**
    - Check theme compatibility
    - Verify CSS is loading
    - Use browser inspector to debug

### Debug Mode

Enable debug output:

```javascript
window.prcFacetsDebug = true;
```

This logs:

- Facet state changes
- API requests
- Rendering operations
- Performance metrics
