# Family Trees List for webtrees

`hh-family-trees-list` is a webtrees 2.2 module that displays a compact overview of the family trees on a website.

It is maintained as a modernized fork of the original **Family-Trees-List** module by iyoua.

## 📌 Purpose

The module adds a configurable block that can be placed on a personal **My page** or on the home page of a family tree. It can show all family trees on the website together with useful summary figures:

- families
- individuals
- events
- surnames

The module respects the global webtrees setting **Show list of family trees**. The block is displayed only when this option is set to **yes** under **Control panel → Website preferences**. If it is disabled, visitors and other non-administrators see neither the block nor a hint that other family trees may exist. Administrators see a configuration hint instead.

## ✨ Features

- five display styles: list, table, card, capsule, and navbar
- sortable table view
- responsive layouts for desktop and mobile screens
- statistics for each family tree
- block configuration for layout, sort order, and displayed fields
- one administratively maintained research purpose per family tree
- gettext PO/MO translations for German, Dutch, Simplified Chinese, and Traditional Chinese

## 🧩 Installation

### Installation with the Custom Module Manager

If the module is available through the Custom Module Manager, install it there and enable it in the webtrees control panel.

### Manual installation

1. Download the release ZIP file from GitHub.
2. Extract it into the webtrees `modules_v4` folder.
3. The folder name must be:

   ```text
   hh-family-trees-list
   ```

4. Enable the module in the webtrees control panel.
5. Add the block to the desired home page.

## ⚙️ Configuration

Each block can be configured separately:

- **Layout**: list, table, card, capsule, or navbar
- **Sort order**: internal tree number, oldest first or newest first
- **Displayed fields**: research purpose, families, individuals, events, and surnames; the family-tree name is always shown

Only site administrators can configure the block. Its visibility for other users is controlled independently by the regular webtrees block visibility setting. A block may therefore also be visible to visitors if **Visitors** has been selected there.

In the module settings, an administrator can assign one principal research purpose to each family tree. The available purposes are aligned with the research-purpose sections of `hh_legal_notice`, including a purpose for test trees. The purpose is stored as a webtrees tree preference and can be enabled as an optional field in every block.

Other modules can obtain the translated purpose for a tree through the public method `researchPurpose(Tree $tree): string`. It returns an empty string if no purpose has been assigned. The complete contract and an optional-integration example are documented in [`docs/public-api.md`](docs/public-api.md).

The module deliberately does not write this category to `HEAD:NOTE`. GEDCOM header notes are unrestricted free text, and `extended_import_export` can use the first such note as the complete GEDBAS description. A future export integration may map the structured purpose explicitly without overwriting existing header notes.

The module also follows the global webtrees setting:

- **Website preferences → Show list of family trees**

If this option is set to **no**, the module suppresses the family tree list. Administrators see a warning in the block position that identifies the required setting; all other users see no block.

## 🖼️ Display Styles

The maintained fork keeps the original idea of several compact display styles, but uses a shared responsive stylesheet.

- **List**: compact tiles with statistics
- **Card**: larger cards for more visual separation
- **Capsule**: one row per tree with pill-shaped counters
- **Navbar**: a narrow row layout for dense pages
- **Table**: sortable table with all values

## 🔧 Compatibility

This fork targets webtrees 2.2.x.

## 🌐 Translations

Many thanks to the translators who made the module available in several languages:

- **German:** [Hermann Hartenthaler](https://github.com/hartenthaler)
- **Dutch:** [TheDutchJewel](https://github.com/TheDutchJewel)
- **Simplified and Traditional Chinese:** [果然 (iyoua)](https://github.com/iyoua)

The earlier PHP translation arrays were migrated to gettext PO/MO catalogs without discarding the contributed translations.

## 🤝 Credits

This module is based on the original **Family-Trees-List** module by **iyoua**.

Thanks to the original developer for the idea and the first implementation.

The maintained `hh-family-trees-list` fork includes compatibility, documentation, and design updates by Hermann Hartenthaler with assistance from Codex.

## 📄 License

This module follows the license of the original project.
