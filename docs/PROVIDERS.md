# PRC Facets Provider Documentation

This document provides comprehensive documentation for the FacetWP and ElasticPress provider implementations in the PRC Facets plugin.

## Table of Contents

- [Provider Architecture](#provider-architecture)
- [FacetWP Provider](#facetwp-provider)
- [ElasticPress Provider](#elasticpress-provider)
- [Provider Selection Logic](#provider-selection-logic)
- [Cache Management](#cache-management)

## Provider Architecture

The PRC Facets plugin uses a middleware pattern to abstract different faceting providers. Each provider implements a common interface while handling provider-specific functionality.

### Provider Interface

Both providers implement these core methods:

- `get_facets_settings()` - Returns facet configuration for the provider
- `register_facets()` - Registers facets with the provider system
- `process_query()` - Modifies queries to include facet filtering

## FacetWP Provider

### Overview

The FacetWP provider (`class-facetwp-middleware.php`) handles traditional WordPress queries with FacetWP integration.

### Configuration

#### Default Facets Array

```php
public static $facets = array(
    // Categories Facet
    array(
        'name'            => 'categories',
        'label'           => 'Topics',
        'type'            => 'checkboxes',
        'source'          => 'tax/category',
        'hierarchical'    => 'yes',
        'show_expanded'   => 'no',
        'ghosts'          => 'yes',        // Show empty terms
        'preserve_ghosts' => 'no',         // Don't preserve empty terms
        'operator'        => 'or',          // OR logic between selections
        'orderby'         => 'count',       // Order by post count
        'count'           => '50',          // Max items to show
        'soft_limit'      => '5',           // Initial visible items
    ),

    // Research Teams Facet
    array(
        'name'            => 'research_teams',
        'label'           => 'Research Teams',
        'type'            => 'dropdown',
        'source'          => 'tax/research-teams',
        'label_any'       => 'Any',         // Default dropdown label
        'hierarchical'    => 'no',
        'orderby'         => 'count',
        'count'           => '25',
    ),

    // Formats Facet
    array(
        'name'            => 'formats',
        'label'           => 'Formats',
        'type'            => 'checkboxes',
        'source'          => 'tax/formats',
        'hierarchical'    => 'no',
        'operator'        => 'or',
        'orderby'         => 'count',
        'count'           => '-1',          // Show all items
        'soft_limit'      => '5',
    ),

    // Authors Facet
    array(
        'name'            => 'authors',
        'label'           => 'Authors',
        'type'            => 'dropdown',
        'source'          => 'tax/bylines',
        'label_any'       => 'Any',
        'hierarchical'    => 'no',
        'orderby'         => 'count',
        'count'           => '-1',
    ),

    // Time Since Facet
    array(
        'name'      => 'time_since',
        'label'     => 'Time Since',
        'type'      => 'time_since',
        'source'    => 'post_date',
        'label_any' => 'By Date Range',
        'choices'   => "Past Month | -30 days\nPast 6 Months | -180 days\nPast 12 Months | -365 days\nPast 2 Years | -730 days",
    ),

    // Date Range Facet
    array(
        'name'         => 'date_range',
        'label'        => 'Date Range',
        'type'         => 'date_range',
        'source'       => 'post_date',
        'compare_type' => '',
        'fields'       => 'both',
        'format'       => 'Y',
    ),
);
```

### Facet Configuration Parameters

| Parameter         | Description                              | Values                                               |
| ----------------- | ---------------------------------------- | ---------------------------------------------------- |
| `name`            | Unique identifier for the facet          | String (lowercase, no spaces)                        |
| `label`           | Display label for users                  | String                                               |
| `type`            | Facet interface type                     | `checkboxes`, `dropdown`, `date_range`, `time_since` |
| `source`          | Data source for facet                    | `tax/{taxonomy}`, `post_date`, custom field          |
| `hierarchical`    | Enable hierarchy for taxonomies          | `yes`, `no`                                          |
| `show_expanded`   | Show all items initially                 | `yes`, `no`                                          |
| `ghosts`          | Show terms with no posts                 | `yes`, `no`                                          |
| `preserve_ghosts` | Keep empty terms after filtering         | `yes`, `no`                                          |
| `operator`        | Logic between multiple selections        | `or`, `and`                                          |
| `orderby`         | Sort order for facet values              | `count`, `display_value`, `raw_value`                |
| `count`           | Maximum items to display                 | Number or `-1` for all                               |
| `soft_limit`      | Initial visible items (with "Show more") | Number                                               |
| `label_any`       | Default label for dropdowns              | String                                               |
| `choices`         | Predefined choices for time_since        | Pipe-delimited string                                |
| `fields`          | Date fields to show                      | `both`, `start`, `end`                               |
| `format`          | Date display format                      | PHP date format string                               |

### Hooks and Filters

The FacetWP provider uses these hooks:

```php
// Register facets
add_filter('facetwp_facets', [$this, 'register_facets']);

// Modify query for facet filtering
add_filter('facetwp_query_args', [$this, 'modify_query_args'], 10, 2);

// Cache facet results
add_filter('facetwp_cache_lifetime', [$this, 'set_cache_lifetime']);
```

## ElasticPress Provider

### Overview

The ElasticPress provider (`class-elasticpress-middleware.php`) handles Elasticsearch-powered faceting for search pages.

### Configuration

#### Taxonomy Registration

```php
public function register_facets() {
    // Register taxonomies for ElasticPress faceting
    $taxonomies = array(
        'category'       => 'Categories',
        'research-teams' => 'Research Teams',
        'formats'        => 'Formats',
        'bylines'        => 'Authors',
    );

    foreach ($taxonomies as $taxonomy => $label) {
        ep_register_feature_taxonomy($taxonomy, array(
            'facet_label' => $label,
            'facet_type'  => $this->get_facet_type($taxonomy),
        ));
    }
}
```

### ElasticPress Aggregations

The provider automatically creates Elasticsearch aggregations for:

- Taxonomy terms
- Date ranges
- Post meta fields
- Custom fields

### Query Takeover

ElasticPress automatically takes over queries when:

1. User is on a search page
2. Facets are active in the query
3. ElasticPress is enabled and configured

## Provider Selection Logic

### Automatic Provider Detection

```php
function use_ep_facets() {
    $uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw($_SERVER['REQUEST_URI']) : '';

    // Use ElasticPress for search pages
    if (strpos($uri, '/search') !== false) {
        return true;
    }

    // Use FacetWP for all other pages
    return false;
}
```

### Manual Provider Selection

Developers can force a specific provider:

```php
// Force ElasticPress
add_filter('prc_facets_use_elasticpress', '__return_true');

// Force FacetWP
add_filter('prc_facets_use_elasticpress', '__return_false');
```

## Cache Management

### Cache Key Generation

```php
function construct_cache_key($query = array(), $selected = array()) {
    $invalidate = '06/12/2025'; // Manual cache invalidation date

    // Remove pagination from cache key
    $query = array_merge($query, array('paged' => 1));

    // Generate MD5 hash
    return md5(wp_json_encode(array(
        'query'      => $query,
        'selected'   => $selected,
        'invalidate' => $invalidate,
    )));
}
```

### Cache Group Construction

```php
function construct_cache_group() {
    global $wp;

    // Parse current URL
    $url_params = wp_parse_url('/' . add_query_arg(array($_GET), $wp->request . '/'));

    if (!is_array($url_params) || !array_key_exists('path', $url_params)) {
        return false;
    }

    // Remove pagination from cache group
    return preg_replace('/\/page\/[0-9]+/', '', $url_params['path']);
}
```

### Cache Invalidation

Caches are invalidated when:

1. Content is updated (posts, terms)
2. Manual invalidation date is reached
3. Facet configuration changes
4. Provider settings are modified

## Provider API Methods

### FacetWP API Methods

| Method                                         | Description                         | Parameters                    | Returns                  |
| ---------------------------------------------- | ----------------------------------- | ----------------------------- | ------------------------ |
| `get_facets_settings()`                        | Get all facet configurations        | None                          | Array of facet settings  |
| `register_facets($facets)`                     | Register facets with FacetWP        | `$facets` - Array of facets   | Modified facets array    |
| `modify_query_args($query_args, $facet_query)` | Modify WP_Query arguments           | `$query_args`, `$facet_query` | Modified query args      |
| `get_selected_values()`                        | Get currently selected facet values | None                          | Array of selected values |

### ElasticPress API Methods

| Method                         | Description                        | Parameters                 | Returns                 |
| ------------------------------ | ---------------------------------- | -------------------------- | ----------------------- |
| `get_facets_settings()`        | Get ElasticPress facet config      | None                       | Array of facet settings |
| `register_facets()`            | Register taxonomies for faceting   | None                       | Void                    |
| `process_search_query($query)` | Process search queries with facets | `$query` - WP_Query object | Modified query          |
| `get_aggregations()`           | Get Elasticsearch aggregations     | None                       | Array of aggregations   |

## Advanced Configuration

### Custom Facet Sources

```php
// Add custom post meta as facet source
add_filter('facetwp_facet_sources', function($sources) {
    $sources['custom_fields'][] = array(
        'label' => 'Custom Rating',
        'name'  => 'cf/rating',
        'type'  => 'custom_field',
    );
    return $sources;
});
```

### Custom Facet Types

```php
// Register custom facet type
add_filter('facetwp_facet_types', function($types) {
    $types['custom_slider'] = array(
        'label' => 'Custom Slider',
        'class' => 'CustomSliderFacet',
    );
    return $types;
});
```

## Performance Optimization

### Best Practices

1. **Use ElasticPress for large datasets** (>10,000 posts)
2. **Implement proper caching strategies**
3. **Limit facet counts** to reduce query overhead
4. **Use soft limits** for better initial load performance
5. **Index custom fields** properly for ElasticPress

### Query Optimization

```php
// Optimize facet queries
add_filter('facetwp_query_args', function($args) {
    // Reduce fields returned
    $args['fields'] = 'ids';

    // Disable unnecessary queries
    $args['update_post_meta_cache'] = false;
    $args['update_post_term_cache'] = false;

    return $args;
}, 20);
```

## Troubleshooting

### Common Issues

1. **Facets not appearing**
    - Check provider is active
    - Verify facet registration
    - Check template context

2. **Slow performance**
    - Enable object caching
    - Switch to ElasticPress for large datasets
    - Reduce facet counts

3. **Incorrect counts**
    - Clear cache
    - Reindex ElasticPress
    - Check query modifications

### Debug Mode

Enable debug logging:

```php
define('PRC_FACETS_DEBUG', true);
```

This will log:

- Provider selection decisions
- Query modifications
- Cache operations
- Facet registration
