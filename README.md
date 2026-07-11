# Family Trees List for webtrees

`hh-family-trees-list` is a webtrees 2.2 module that displays a compact overview of the family trees on a website.

It is maintained as a modernized fork of the original **Family-Trees-List** module by iyoua.

## 📌 Purpose

The module adds a configurable block for user and tree home pages. It can show all family trees on the website together with useful summary figures:

- families
- individuals
- events
- surnames

The module respects the global webtrees setting **Show list of family trees**. If this webtrees setting is disabled, the block does not render a family tree list.

## ✨ Features

- five display styles: list, table, card, capsule, and navbar
- sortable table view
- responsive layouts for desktop and mobile screens
- statistics for each family tree
- block configuration for layout, sort order, and displayed fields
- one administratively maintained research purpose per family tree
- custom translations for German, Dutch, Simplified Chinese, and Traditional Chinese

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

In the module settings, an administrator can assign one principal research purpose to each family tree. The available purposes are aligned with the research-purpose sections of `hh_legal_notice`. The purpose is stored as a webtrees tree preference and can be enabled as an optional field in every block.

The module deliberately does not write this category to `HEAD:NOTE`. GEDCOM header notes are unrestricted free text, and `extended_import_export` can use the first such note as the complete GEDBAS description. A future export integration may map the structured purpose explicitly without overwriting existing header notes.

The module also follows the global webtrees setting:

- **Website preferences → Show list of family trees**

If this option is set to **no**, the module suppresses the family tree list.

## 🖼️ Display Styles

The maintained fork keeps the original idea of several compact display styles, but uses a shared responsive stylesheet.

- **List**: compact tiles with statistics
- **Card**: larger cards for more visual separation
- **Capsule**: one row per tree with pill-shaped counters
- **Navbar**: a narrow row layout for dense pages
- **Table**: sortable table with all values

## 🔧 Compatibility

This fork targets webtrees 2.2.x.

## 🤝 Credits

This module is based on the original **Family-Trees-List** module by **iyoua**.

Thanks to the original developer for the idea and the first implementation.

The maintained `hh-family-trees-list` fork includes compatibility, documentation, and design updates by Hermann Hartenthaler with assistance from Codex.

## 📄 License

This module follows the license of the original project.
