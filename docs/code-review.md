# Code Review

## Reviewed Scope

The current maintained fork was reviewed after the webtrees 2.2 compatibility update and the responsive layout work.

## Findings Addressed

- The module now respects the global webtrees setting `ALLOW_CHANGE_GEDCOM` ("Show list of family trees"). If the site owner disables the family tree list, the block renders no list.
- Inline CSS was removed from the display views and consolidated into `resources/css/treeslist.css`.
- Repeated IDs in the navbar/table views were removed.
- The table sorting code now uses a block-specific table ID and `data-sort` values, avoiding conflicts when multiple blocks are present on a page.
- Statistic lookups now use safe fallback values, avoiding undefined-index notices when a tree has no matching rows in one of the statistic tables.
- Block configuration values are validated against known layout/sort options before they are saved or used.
- Module metadata now points to the maintained fork.
- The custom PHP translation arrays were replaced by PO/MO catalogs. Core strings are reused through a separate I18N wrapper and remain outside the module catalog.

## Remaining Considerations

- The module still uses direct SQL count queries. This is efficient for the current scope, but very large installations may eventually benefit from cached statistics.
- The original image assets are still used. The current redesign makes them smaller and less visually dominant, but replacing them with webtrees/lucide-style icons could be considered later.
- The block intentionally suppresses the whole family tree list when `ALLOW_CHANGE_GEDCOM` is disabled. If administrators need an in-page warning while configuring blocks, this should be added only for manager/admin contexts to avoid leaking information to visitors.
