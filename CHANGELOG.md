# Changelog

All notable changes to the SwiftSearch - Typesense Search for WordPress plugin will be documented in this file.

## [1.2.3] - 2026-04-06

### Fixed
- **ConfigLoader Bottleneck**: Resolved a critical issue where `facets_config` and other user settings were being filtered out before reaching the Typesense schema engine.
- **Dynamic Schema Support**: Universal facets now correctly register in the Typesense collection schema during indexing.

### Improved
- **Diagnostic Logging**: Added explicit `Typesense Response:` log to the frontend for easier troubleshooting.

## [1.2.2] - 2026-04-06

### Removed
- **Self-Healing Retry Logic**: Removed the automatic retry-without-facets mechanism to prevent silent failures and ensure that schema/facet issues are reported clearly in the console.

### Improved
- **Error Reporting**: Added explicit console logging for Typesense API errors, including the full error message from the server.
- **JS Robustness**: Added extra validation to ensure `facets_config` is always processed as an array.

## [1.2.1] - 2026-04-06

### Fixed
- **Facet Sidebar Visibility**: Resolved an issue where enabled taxonomies (like Product Categories) were hidden in the frontend if they weren't also selected in the general "Content" synchronization settings.

## [1.2.0] - 2026-04-06

### Added
- **Universal Facet Registration Bridge**: Completely refactored the facet system to support any WordPress plugin (WooCommerce, Events, etc.) and custom meta fields.
- **Advanced Facet Configuration**: New UI in the Admin dashboard to explicitly map WordPress fields to Typesense "Target Fields" and define "Data Types" (String, Integer, Float, Boolean, Array).
- **Type-Aware Indexing**: The backend now dynamically casts meta and taxonomy values to ensure Typesense receives correctly typed data (int, float, bool).

### Fixed
- **Facet 'No Results' Issue**: Resolved a critical bug where numeric (Price, SKU) and boolean (In Stock) facets returned zero products due to incorrect backtick wrapping in Typesense queries.
- **Schema Mismatches**: Unified the schema generation and document indexing logic to prevent 404/400 errors during search.

### Improved
- **Clean Backend Architecture**: Removed hardcoded overrides for WooCommerce and taxonomies, moving to a fully config-driven engine.

## [1.1.0] - 2026-04-03

### Added
- **Premium Bento Grid UI**: Complete overhaul of the search results template with a modern, high-end Bento-style layout.
- **Conditional Facet Sidebar**: The sidebar now automatically hides if no facets are active, allowing results to expand to full-width and removing stagnant white space.
- **Enhanced Shortcode Attributes**: New shortcode attributes for granular control over thumbnails, prices, excerpts, and search scope directly from the page editor.

### Fixed
- **Settings Persistence**: Resolved a critical bug where unchecked options (e.g., all post types unchecked) would not correctly persist to the database.
- **REST API Synchronization**: Fixed a race condition/missing response in the settings endpoint that caused "Saved" alerts to not appear in the Admin UI.
- **Indexing Guardrails**: Tightened both the real-time and bulk indexers to strictly respect the configured post-type whitelist, preventing accidental indexing of unwanted content.
- **Search Result Scope**: Reinforced the frontend search logic to ensure results are strictly filtered by the `post_type` and `scope` parameters.

### Improved
- **Admin UI Polish**: Refined the Pro feature overlays and plan clarity.
- **Version Management**: Transitioned to the 1.1.x versioning scheme for better milestone tracking.

## [1.0.52] - 2026-04-03
- Patch: Version bump for asset cache-busting during UI development.

## [1.0.51] - 2024-04-03
- Initial stable release of the Typesense integration core.
