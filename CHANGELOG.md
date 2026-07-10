# Change Log

## Next release

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