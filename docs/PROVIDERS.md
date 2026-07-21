# PRC Facets Provider Documentation

ElasticPress (VIP Search) is the sole facets provider for publication listings, taxonomy archives, search, and dataset archives.

## Table of Contents

- [Provider Architecture](#provider-architecture)
- [ElasticPress Provider](#elasticpress-provider)
- [URL scheme](#url-scheme)
- [MySQL fallback](#mysql-fallback)
- [Cache Management](#cache-management)
- [Provider API Methods](#provider-api-methods)
- [Troubleshooting](#troubleshooting)

## Provider Architecture

`ElasticPress_Middleware` is always registered from `class-plugin.php`. The context provider and REST settings always use ElasticPress (`urlKey` = `ep_filter_`).

## ElasticPress Provider

### Overview

The ElasticPress provider (`class-elasticpress-middleware.php`) handles Elasticsearch-powered faceting for listing and search pages. `ElasticPress_Facets_API` normalizes aggregations into the Interactivity API contract.

### Query Takeover

ElasticPress integrates every main query flagged `isPubListingQuery` (publications, taxonomy archives, search) plus a dedicated dataset archive opt-in in `prc-datasets`. Facetable marking uses `ep_is_facetable` when needed.

### ElasticPress Aggregations

The provider creates Elasticsearch aggregations for:

- Taxonomy terms (disjunctive for checkbox OR/AND-across)
- Years (`date_terms.year`)
- Time-since ranges (`past-month`, `past-6-months`, `past-12-months`, `past-2-years`)

## URL scheme

All surfaces use `ep_filter_*` query params. Legacy FacetWP underscore params (`_categories`, `_authors`, `_formats`, `_years`, `_time_since`, etc.) are 301'd in `vip-config/server-redirects.php` before WordPress boots. `_date_range` is stripped without remap.

## MySQL fallback

When ES fails, visibility SQL still applies; facet filters are not re-applied on MySQL. UI renders disabled facet shells (`isDisabled`).

## Cache Management

### Cache Key Generation

```php
function construct_cache_key($query = array(), $selected = array()) {
    $invalidate = '07/20/2026-ep-only'; // Manual cache invalidation date

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
    $url_params = wp_parse_url('/' . add_query_arg(array(), $wp->request . '/'));

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

## Provider API Methods

| Method                         | Description                        | Parameters                 | Returns                 |
| ------------------------------ | ---------------------------------- | -------------------------- | ----------------------- |
| `get_facets_settings()`        | Get ElasticPress facet config      | None                       | Array of facet settings |
| `register_facets()`            | Register taxonomies for faceting   | None                       | Void                    |
| `process_search_query($query)` | Process search queries with facets | `$query` - WP_Query object | Modified query          |
| `get_aggregations()`           | Get Elasticsearch aggregations     | None                       | Array of aggregations   |

## Troubleshooting

### Common Issues

1. **Facets not appearing**
    - Verify ElasticPress / VIP Search is healthy
    - Confirm facet registration and template context

2. **Slow performance**
    - Enable object caching
    - Confirm queries use `ep_integrate`

3. **Incorrect counts**
    - Clear object cache
    - Reindex ElasticPress
    - Check query modifications / disjunctive aggs

4. **Empty or disabled facet sidebar**
    - Likely degraded mode (`elasticsearch_success = false`)
    - Shells stay visible but disabled by design

### Debug Mode

Enable debug logging:

```php
define('PRC_FACETS_DEBUG', true);
```

This will log:

- Query modifications
- Cache operations
- Facet registration
