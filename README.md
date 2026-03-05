# PRC Facets

Faceted search and filtering for PRC Platform archive and search pages, built on FacetWP and ElasticPress.

## Overview

PRC Facets provides the filtering layer for publication listing and search pages across the platform. It abstracts two provider backends — FacetWP for standard archive queries, ElasticPress for full-text search — behind a unified block-based interface. The `facets-context-provider` block fetches and distributes facet state server-side, then hands off to the Interactivity API for client-side navigation without page reloads.

The plugin also disables WordPress month/day date archives, redirecting date-based navigation into the faceted system instead.

### Dependencies

- **Upstream**: `prc-platform-core`, `prc-publication-listing` (query args), FacetWP (plugin), ElasticPress (plugin)
- **Downstream**: Any template using publication listing blocks with facet filtering (archive, search templates in the block theme)

## Architecture

The plugin has two distinct layers:

**PHP (server)**: Two middleware classes wrap FacetWP and ElasticPress hooks. At render time, `Context_Provider` calls the appropriate provider API, fetches facet data + pagination, and injects it into block context and `wp_interactivity_state`. The `Rest_API` class exposes facet configuration to the editor so blocks can read available facets by template.

**JS (client)**: Four blocks built with `@wordpress/scripts`. The context provider block's view script drives client-side state using the Interactivity API — watching for selection changes, triggering FacetWP or EP refetch, and managing `isProcessing` state across the query, results info, and tokens list.

Provider selection is URL-based: requests to `/search*` use ElasticPress; everything else uses FacetWP. This is enforced in both `utils.php::use_ep_facets()` and `Rest_API::restfully_get_facet_settings()`.

### Key Files

| Path | Purpose |
|------|---------|
| `prc-facets.php` | Plugin bootstrap, constants, activation hooks |
| `includes/class-plugin.php` | Wires all dependencies and registers the `template_redirect` hook that 404s month/day archives |
| `includes/class-rest-api.php` | `GET /wp-json/prc-api/v3/facets/get-settings` — returns provider-specific facet config to the editor |
| `includes/utils.php` | `use_ep_facets()`, `construct_cache_key()`, `construct_cache_group()`, `format_label()` |
| `includes/providers/facet-wp/class-facetwp-middleware.php` | Registers hardcoded facets with FacetWP, short-circuits EP on FacetWP queries, restricts hierarchical facet depth to parent terms only |
| `includes/providers/facet-wp/class-facetwp-api.php` | PHP client that calls `/facetwp/v1/fetch` internally, caches results (60 min), parses facet choices into a normalized shape |
| `includes/providers/elasticpress/class-elasticpress-middleware.php` | Registers facet taxonomies with EP, rewrites ES bool queries for correct OR-within/AND-between behavior, adds year aggregations |
| `includes/providers/elasticpress/class-elasticpress-facets-api.php` | PHP client for reading EP aggregation data from `$GLOBALS['ep_facet_aggs']` |
| `build/context-provider/class-context-provider.php` | Fetches facet data at `pre_render_block`, injects into block context and `wp_interactivity_state`, patches `data-wp-*` directives onto the query and results blocks |
| `build/template/class-template.php` | Registers `prc-platform/facet-template` — renders individual facets based on `facetName`/`facetType` attributes |
| `build/results-info/class-results-info.php` | Registers `prc-platform/facets-results-info` — server-renders result count/range, updates reactively via Interactivity API |
| `build/search-relevancy/class-search-relevancy.php` | Registers `prc-platform/facet-search-relevancy` — toggle for sorting search results by relevance vs. date (`ep_sort__by_date` query var) |

## Blocks

| Block | Name | Description |
|-------|------|-------------|
| Facets Context Provider | `prc-platform/facets-context-provider` | One-per-page wrapper. Fetches all facet data server-side and distributes it to child blocks via context and Interactivity API state. Must be the ancestor of all other facet blocks. |
| Facet Template | `prc-platform/facet-template` | Renders a single facet by slug. Attributes: `facetName`, `facetType` (checkbox/radio/dropdown/range/search), `facetLabel`, `facetLimit`. Must be a descendant of the context provider. |
| Facets Results Info | `prc-platform/facets-results-info` | Displays total results and page range. Updates reactively. Single-instance, no attributes. |
| Facet Search Relevancy | `prc-platform/facet-search-relevancy` | Relevance vs. date sort toggle for search pages only. Uses `ep_sort__by_date` query var and EP's `ep_set_sort` hook. |

## Registered FacetWP Facets

Facets are hardcoded in `FacetWP_Middleware::$facets` — they are not configured through the FacetWP admin UI. The registered set:

| Name | Label | Type | Source |
|------|-------|------|--------|
| `categories` | Topics | checkboxes | `tax/category` (hierarchical, depth 0 only) |
| `research_teams` | Research Teams | dropdown | `tax/research-teams` |
| `formats` | Formats | checkboxes | `tax/formats` |
| `authors` | Authors | dropdown | `tax/bylines` |
| `time_since` | Time Since | time_since (→ radio) | `post_date` |
| `date_range` | Date Range | date_range | `post_date` |
| `years` | Years | yearly (→ dropdown) | `post_date` |
| `regions_countries` | Regions & Countries | radio | `tax/regions-countries` |

## Hooks & Filters

| Hook | Type | Description |
|------|------|-------------|
| `prc_api_endpoints` | filter | Adds the `/facets/get-settings` endpoint to the platform REST API |
| `facetwp_facets` | filter | Replaces FacetWP's facet list with `FacetWP_Middleware::$facets` |
| `facetwp_is_main_query` | filter | Disables FacetWP on search pages (EP takes over instead) |
| `facetwp_api_can_access` | filter | Opens FacetWP REST API to unauthenticated requests |
| `facetwp_indexer_query_args` | filter | Merges platform pub-listing query args into the FacetWP indexer query; also fires `prc_platform__facetwp_indexer_query_args` for downstream customization |
| `facetwp_index_row` | filter | Skips indexing child terms for `categories`/`topics` facets (depth > 0) |
| `pre_get_posts` (priority 1000) | filter | Short-circuits ElasticPress (`ep_integrate = false`) when FacetWP is running the query |
| `pre_get_posts` (priority 5) | action | Forces `ep_integrate = true` on pub-listing search queries so EP facets apply |
| `ep_facet_include_taxonomies` | filter | Registers the taxonomy set for EP faceting |
| `ep_post_formatted_args` | filter | Restructures ES `post_filter` bool queries: OR within a taxonomy, AND between taxonomies |
| `ep_formatted_args` | filter | Adds a `date_terms.year` date histogram aggregation to every EP query |
| `ep_valid_response` | filter | Extracts year bucket data from the ES response into `$GLOBALS['ep_facet_aggs']['years']` |
| `ep_facet_taxonomies_size` | filter | Sets taxonomy aggregation size to 100 across all facets |
| `ep_set_sort` | filter | Overrides EP sort to `post_date` when `ep_sort__by_date` query var is present |
| `prc_platform_rewrite_query_vars` | filter | Registers `ep_sort__by_date` and `ep_filter_years` as allowed query vars |
| `pre_render_block` | filter | Fetches facet data once before rendering the context provider block |
| `render_block_context` | filter | Injects facet data into block context for `facets-context-provider` and `facet-template` |
| `template_redirect` | action | Returns 404 for month and day archives (`is_month()`, `is_day()`) |

## REST API

### `GET /wp-json/prc-api/v3/facets/get-settings`

Returns facet configuration for a given template. Used by the editor to populate facet name options in block controls.

**Parameters**:
- `templateSlug` (string, required, default: `"archive"`) — The site-editor template slug. If it contains `"search"`, returns ElasticPress settings; otherwise returns FacetWP settings.

**Response**: For FacetWP, an array of facet config objects keyed by slug (read from `facetwp_settings` option). For ElasticPress, an array of WP taxonomy objects augmented with a `facet_type` property.

## Caching

FacetWP facet results are cached in the WordPress object cache for 60 minutes. Cache key: MD5 of the query args + selected facets + a hardcoded invalidation date. Cache group: the current URL path with pagination stripped.

Cache is skipped entirely for Googlebot, unauthenticated requests on non-primary blog IDs, and pagination beyond page 100.

To force cache invalidation, update the `$invalidate` date string in `includes/utils.php::construct_cache_key()`.

## Debugging

Define `PRC_FACETS_DEBUG` to enable verbose logging to the PHP error log:

```php
define( 'PRC_FACETS_DEBUG', true );
```

Both middleware classes log provider selection decisions, query modifications, facet registration, and aggregation data under `[PRC Facets - FacetWP]` and `[PRC Facets - ElasticPress]` prefixes.

## Local Development

```bash
npm run build -w @prc/facets
npm run start -w @prc/facets
```

Tests use Playwright against a `wp-env` environment:

```bash
npm run test -w @prc/facets
```

## Troubleshooting

### Facets show no results on archive pages

**Symptom**: Filter UI renders but all counts are 0 or facets are empty.  
**Cause**: FacetWP has not indexed the content, or the `prc-publication-listing` query args filter is excluding posts from the index.  
**Fix**: Re-run the FacetWP indexer from Settings → FacetWP → Re-index. Check `prc_platform__facetwp_indexer_query_args` to confirm the indexer is querying the right post types.

### ElasticPress and FacetWP conflict on the same query

**Symptom**: Search page filters do nothing, or archive page filters trigger ES queries.  
**Cause**: Provider selection is URL-based — pages with `/search` in the path use EP; all others use FacetWP. A misconfigured permalink or template can cause the wrong provider to activate.  
**Fix**: Verify the URL matches expectations. Enable `PRC_FACETS_DEBUG` and check error logs for `[PRC Facets - FacetWP] Short-circuiting ElasticPress` or `[PRC Facets - ElasticPress] Taking over publication listing query`.

### Year filter has no effect on search results

**Symptom**: Selecting a year in the search filter doesn't narrow results.  
**Cause**: The `ep_filter_years` query var must be registered and the ES query must pass through `add_filters_to_query`. If the query doesn't have a `post_filter.bool.must[0].bool.should` structure, the filter is skipped.  
**Fix**: Confirm `prc_platform_rewrite_query_vars` is firing and that `ep_filter_years` is set in the URL. Enable debug mode to inspect `add_filters_to_query` output.

### Category facet shows subcategories

**Symptom**: Child category terms appear in the Topics facet.  
**Cause**: FacetWP is indexing all term depths. The `restrict_facet_row_depth` hook only applies to facets named `topics`, `topic`, `categories`, or `category`.  
**Fix**: Confirm the facet `name` in `FacetWP_Middleware::$facets` matches one of those strings.

## Related Docs

- [`docs/PROVIDERS.md`](docs/PROVIDERS.md) — extended provider configuration reference
- [`docs/BLOCKS.md`](docs/BLOCKS.md) — block usage and InnerBlocks patterns
- [FacetWP documentation](https://facetwp.com/documentation/)
- [ElasticPress documentation](https://elasticpress.io/documentation/)
- `prc-publication-listing` — source of `Query::get_filtered_query_args()` used by both middleware classes
