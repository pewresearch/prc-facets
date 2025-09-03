# PRC Facets

A comprehensive block-based faceting system for PRC Platform that provides powerful filtering capabilities for content discovery. The plugin supports both FacetWP and ElasticPress providers, offering flexible faceted search and filtering functionality for archive and search pages.

## Overview

PRC Facets enables users to filter and refine content through intuitive interface elements like checkboxes, dropdowns, radio buttons, and search inputs. The system automatically detects whether to use FacetWP or ElasticPress based on the context (search pages use ElasticPress, while other pages can use either provider).

## Features

- **Dual Provider Support**: Seamlessly integrates with both FacetWP and ElasticPress
- **Block-Based Architecture**: Gutenberg blocks for easy facet interface construction
- **Multiple Facet Types**: Support for checkboxes, radio buttons, dropdowns, ranges, and search inputs
- **Context-Aware**: Automatically switches between providers based on page context
- **Performance Optimized**: Includes caching mechanisms and query optimization
- **Responsive Design**: Mobile-friendly facet interfaces
- **REST API Integration**: Programmatic access to facet configurations

## Requirements

- WordPress 6.7+
- PHP 8.2+
- PRC Platform Core plugin
- Either FacetWP or ElasticPress plugin (or both)

## Installation

1. Ensure PRC Platform Core is installed and activated
2. Install either FacetWP or ElasticPress (or both)
3. Upload the PRC Facets plugin to your plugins directory
4. Activate the plugin through the WordPress admin

## Architecture

### Core Components

#### Plugin Structure

```
prc-facets/
├── includes/
│   ├── class-plugin.php           # Main plugin class
│   ├── class-rest-api.php         # REST API endpoints
│   ├── utils.php                  # Utility functions
│   └── providers/
│       ├── facet-wp/              # FacetWP integration
│       └── elasticpress/          # ElasticPress integration
├── blocks/
│   └── src/
│       ├── context-provider/      # Facets context provider block
│       ├── template/              # Facet template block
│       ├── results-info/          # Results information block
│       └── search-relevancy/      # Search relevancy toggle block
```

#### Provider System

The plugin uses a middleware pattern to abstract different faceting providers:

- **FacetWP Middleware**: Handles traditional WordPress queries with FacetWP
- **ElasticPress Middleware**: Manages Elasticsearch-powered faceting for search

### Blocks

#### 1. Facets Context Provider (`prc-platform/facets-context-provider`)

**Purpose**: Provides faceting context to child blocks and manages the overall facet state.

**Key Features**:

- Single instance per page (supports.multiple: false)
- Provides context to nested query loops and facet blocks
- Handles client-side navigation with Interactivity API
- Automatically detects template context

**Usage**: Place this block at the top level of your faceted content area.

#### 2. Facet Template (`prc-platform/facet-template`)

**Purpose**: Renders individual facets based on configuration.

**Attributes**:

- `facetName`: The slug/identifier for the facet
- `facetType`: Type of facet interface (checkbox, radio, dropdown, range, search)
- `facetLabel`: Display label for the facet
- `facetLimit`: Maximum number of facet values to display

**Supported Types**:

- **Checkbox**: Multi-select facet values
- **Radio**: Single-select facet values
- **Dropdown**: Compact single-select interface
- **Range**: Numeric range selection
- **Search**: Text-based filtering

#### 3. Results Info (`prc-platform/facets-results-info`)

**Purpose**: Displays information about current search/filter results.

**Features**:

- Shows total number of results
- Displays current page range
- Updates dynamically as filters change
- Supports color and spacing customization

#### 4. Search Relevancy (`prc-platform/facet-search-relevancy`)

**Purpose**: Provides toggle for sorting search results by relevancy vs. date.

**Attributes**:

- `orientation`: Layout orientation (vertical/horizontal)
- `allowedBlocks`: Nested blocks allowed within

## Provider Details

### FacetWP Integration

The FacetWP middleware provides predefined facet configurations for common PRC content types:

```php
// Example facet configuration
array(
    'name'            => 'categories',
    'label'           => 'Topics',
    'type'            => 'checkboxes',
    'source'          => 'tax/category',
    'hierarchical'    => 'yes',
    'operator'        => 'or',
    'orderby'         => 'count',
    'count'           => '50',
)
```

**Default Facets**:

- **Categories**: Hierarchical topic filtering
- **Research Teams**: Department-based filtering
- **Authors**: Content creator filtering
- **Formats**: Content type filtering
- **Date Range**: Publication date filtering

### ElasticPress Integration

The ElasticPress middleware handles search-specific faceting with Elasticsearch aggregations:

**Features**:

- Automatic search query detection
- Enhanced performance for large datasets
- Advanced aggregation support
- Date-based aggregations
- Taxonomy faceting with improved performance

**Query Takeover**: Automatically uses ElasticPress for publication listing queries when facets are active.

## REST API

### Endpoints

#### GET `/wp-json/prc-api/v3/facets/get-settings`

Retrieves facet configuration based on template context.

**Parameters**:

- `templateSlug` (required): Template identifier (e.g., 'archive', 'search')

**Response**: JSON object containing facet configurations and provider information.

## Utility Functions

### `format_label($label)`

Formats facet labels, handling special cases like dates and HTML entities.

### `use_ep_facets()`

Determines whether to use ElasticPress based on current context (returns true for search pages).

### `construct_cache_key($query, $selected)`

Creates cache keys for facet results based on query parameters and selected filters.

## Development

### Building Blocks

```bash
# Install dependencies
npm install

# Build blocks for development
npm run build

# Watch for changes during development
npm run start
```

### Adding Custom Facets

1. **FacetWP**: Add facet configuration to the `$facets` array in `FacetWP_Middleware`
2. **ElasticPress**: Register taxonomies in the `register_facets()` method of `ElasticPress_Middleware`

### Extending Block Functionality

Blocks use the WordPress Interactivity API for client-side functionality. View scripts are located in each block's `view.js` file.

## Caching

The plugin includes intelligent caching mechanisms:

- Query result caching based on facet selections
- Automatic cache invalidation when content changes
- Cache keys incorporate query parameters and selected filters

## Performance Considerations

- **ElasticPress**: Recommended for large datasets and search-heavy sites
- **FacetWP**: Suitable for smaller datasets and traditional WordPress queries
- **Lazy Loading**: Facet values are loaded on-demand to improve initial page load
- **Query Optimization**: Both providers include query optimization for better performance

## Troubleshooting

### Common Issues

1. **Facets not appearing**: Ensure the Context Provider block is present on the page
2. **No results**: Check that the selected provider (FacetWP/ElasticPress) is active and configured
3. **Performance issues**: Consider switching to ElasticPress for large datasets

### Debug Mode

Enable WordPress debug mode and check for relevant log entries in the error log.

## Contributing

1. Follow WordPress coding standards
2. Test with both FacetWP and ElasticPress providers
3. Ensure blocks work with the Interactivity API
4. Update documentation for new features

## License

GPL-2.0-or-later

## Support

For technical support, contact <webdev@pewresearch.org>.
