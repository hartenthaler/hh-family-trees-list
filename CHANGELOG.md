# Change Log

## 2.2.6.2

- Restricted block configuration to site administrators while retaining the regular per-block visibility rules for its presentation.
- Added an administrator-only warning when the global **Show list of family trees** setting prevents the block from being displayed; visitors and other users continue to receive no tree-list information.
- Clarified in the README that the block can be placed on a personal **My page** or a family-tree home page and may be shown to visitors according to its block visibility setting.
- Added release and download badges and updated the README overview to describe the available information without presenting an incomplete field list.
- Standardized the PHP namespace as `Hartenthaler\Webtrees\Module\FamilyTreesList` and moved the internationalization helper into its dedicated sub-namespace.
- Replaced the legacy PHP translation arrays with gettext PO/MO catalogs and kept webtrees core strings outside the module catalog through `MoreI18N`.
- Added translation credits for German, Dutch, Simplified Chinese, and Traditional Chinese.
- Added a per-tree research-purpose setting based on the purpose categories used by `hh_legal_notice`.
- Added the research purpose `Test` and the public `researchPurpose(Tree $tree)` integration method.
- Documented the public research-purpose API, including optional module integration and the broader meaning of genealogical test data.
- Added per-block field selection for research purpose, families, individuals, events, and surnames while keeping the family-tree name mandatory.
- Preserved the existing set of statistics as the default for existing and new blocks; the new research-purpose field is opt-in.
- Documented why the structured purpose is stored as a tree preference rather than overwriting a GEDCOM `HEAD:NOTE`.
- Renamed the maintained fork to `hh-family-trees-list`.
- Respect the global webtrees setting **Show list of family trees** (`ALLOW_CHANGE_GEDCOM`); if the setting is disabled, the block does not render a family tree list.
- Modernized all display variants with shared CSS, responsive layouts, fewer inline styles, and more robust handling of missing statistics.
- Added a reviewed README structure with installation notes, usage, credits, and maintenance information.
- Updated module metadata and version to `2.2.6.1`.

## 2.2.6.0

- Updated the module for compatibility with webtrees 2.2.6.
- Replaced the deprecated `app()` helper with the webtrees registry container.
- Replaced the undefined block context constant with the explicit webtrees block context.
- Improved English user interface texts and clarified that sorting uses the internal tree number.
- Documented the required lowercase module folder name `family-trees-list`.
- Fixed responsive layouts on narrow screens, especially clipped numbers in the capsule display.
