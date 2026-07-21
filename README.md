# PRC Facets

Faceted search and filtering for PRC Platform archive and search pages, powered by ElasticPress (VIP Search).

## Overview

PRC Facets provides the filtering layer for publication listing and search pages. ElasticPress is the sole facets provider. The `facets-context-provider` block fetches and distributes facet state server-side, then hands off to the Interactivity API for client-side navigation without full page reloads.

## Architecture

**PHP (server):** `ElasticPress_Middleware` owns `ep_integrate` for pub-listing queries, taxonomy OR/AND filter rewriting, and custom `years` / `time_since` aggregations. `ElasticPress_Facets_API` normalizes aggregations into the Interactivity contract. `Context_Provider` always uses the EP API with `urlKey` `ep_filter_`.

**JS (client):** Blocks under `src/` drive selection, URL construction (`ep_filter_*`), and result refresh via the Interactivity API.

**Legacy URLs:** `plugins/prc-facets/vip-config/server-redirects.php` (loaded from root `vip-config` before WP boots) 301s legacy underscore params (`_categories`, `_authors`, etc.) to `ep_filter_*`. `_date_range` is stripped without remap.

## Key files

| Path | Role |
| --- | --- |
| `includes/utils.php` | Cache key/group helpers |
| `includes/providers/elasticpress/` | Middleware + Facets API |
| `vip-config/server-redirects.php` | Pre-WP legacy param redirects |
| `src/context-provider/` | Always EP; exposes `isDisabled` for degraded shells |
| `src/template/` | Facet UI + clear/`ep_filter_` URL actions |

## Registered facets (ElasticPress)

| Slug | Type | Notes |
| --- | --- | --- |
| `category` | checkbox | Topics; OR within taxonomy |
| `formats` | checkbox | OR within taxonomy |
| `regions-countries` | radio | |
| `bylines` | dropdown | Authors |
| `research-teams` | dropdown | |
| `years` | dropdown | `date_terms.year` aggregation |
| `time_since` | radio | `past-month`, `past-6-months`, `past-12-months`, `past-2-years` |

### Checkbox OR / AND-across

Checkbox facets use **OR within** a taxonomy and **AND across** taxonomies (e.g. `ep_filter_formats=report,short-read` ∪ formats, AND `ep_filter_regions-countries=russia`). Taxonomy aggregations are **disjunctive** (each agg omits its own taxonomy filter) so sibling options keep useful counts. Checkbox UI allows **ghosts** (options remain clickable at count `0`).

## MySQL / ES failure semantics

When ElasticPress falls back to MySQL (`elasticsearch_success = false`):

- Page load and `_post_visibility` SQL exclusion still apply.
- Active `ep_filter_*` facet filters are **not** re-applied on MySQL.
- Facet UI renders disabled shells (`isDisabled` / `.is-degraded`) so controls are not interactive.

## Caching

Object-cache TTL remains ~30 minutes. Cache invalidation uses a dated `invalidate` string plus a `-ep-v2` group suffix (`construct_cache_key` / Facets API).

## Debugging

Define `PRC_FACETS_DEBUG` to log provider decisions and query modifications under `[PRC Facets - ElasticPress]`. Client: `window.prcFacetsDebug = true`.

## FacetWP database cleanup

After FacetWP plugins are removed from the deploy artifact, leftover options, cron hooks, and index tables can be cleared with:

```bash
# Preview (default)
wp prc-facets clean-facetwp

# Delete options, clear cron, DROP facetwp_index / facetwp_temp
wp prc-facets clean-facetwp --dry-run=false
```

On VIP multisite, loop sites:

```bash
vip @pewresearch.<env> -- wp site list --field=url | while read -r url; do
  echo "=== $url ==="
  vip @pewresearch.<env> -- wp prc-facets clean-facetwp --url="$url"
done

# Then write:
vip @pewresearch.<env> -- wp site list --field=url | while read -r url; do
  vip @pewresearch.<env> -- wp prc-facets clean-facetwp --dry-run=false --url="$url"
done
```

Deactivate any remaining FacetWP plugin stubs first if they still appear in `active_plugins`. Run alpha before production. Table drops use `$wpdb` because VIP blocks `DROP TABLE` via `wp db query`.

## Troubleshooting

### Years-only or time_since-only filter does nothing

Custom filters append to `post_filter.bool.must` independently of taxonomy grouping. Confirm `ep_filter_years` / `ep_filter_time_since` appear in the URL and that the query is `ep_integrate`.

### Cannot select a second Formats value

Checkbox multi-select requires OR-within post_filter grouping and disjunctive aggs (or ghosts). Confirm `ep_filter_formats` lists comma-separated slugs and sibling checkboxes are not `disabled`.

### Empty or disabled facet sidebar

Likely degraded mode (ES failure). Check VIP Search health and whether `elasticsearch_success` is false on the query. Shells stay visible but disabled by design.

### Legacy `_categories=` bookmarks

Should 301 to `ep_filter_category=` via vip-config redirects. If not, confirm `prc-facets/vip-config/server-redirects.php` is allowlisted in root `vip-config/server-redirects.php`.

## Related

- [docs/PROVIDERS.md](docs/PROVIDERS.md)
- [docs/BLOCKS.md](docs/BLOCKS.md)
- ElasticPress / VIP Search documentation
