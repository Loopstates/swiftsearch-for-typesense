# Changelog

All notable changes to the SwiftSearch - Typesense Search for WordPress plugin will be documented in this file.
 
## [1.3.8] - 2026-04-06

### Added
- **Selective Synonym Linking**: Added "Apply to Collections" checkboxes to Relevance settings. Admins can now manually select which indices (Posts, Terms, Users) should have synonym expansion active.
- **Auto-Discovery**: Implemented automatic collection discovery to ensure synonym links are only attempted on valid, existing Typesense collections.

## [1.3.7] - 2026-04-06

### Fixed
- **Dashboard Synchronization**: Resolved the "Not linked" status in Typesense Cloud by implementing explicit collection-level synonym linking (PUT /collections/{name}/synonyms/{id}).
- **Architecture**: Completed migration to the Global Synonym Sets architecture for full v0.30+ compatibility.

## [1.3.0] - 2026-04-06
 
### Added
- **Typesense v0.30.1+ Compatibility**: Implemented support for **Global Synonym Sets**. This resolves the 404 errors encountered on modern Typesense Cloud clusters by using the new global `/synonyms` endpoint.
- **Dynamic Synonym IDs**: Added a unique prefix (`ss-synonym-`) to synonym set IDs to ensure no collisions in shared or multi-site environments.
 
### Fixed
- **Sync Failure**: Resolved the 404 "Not Found" error during synonym synchronization by switching from legacy collection-level endpoints to the modern global API.
- **Admin Feedback**: Added detailed alert messages for synonym synchronization failures.
 
## [1.2.8] - 2026-04-06
 
### Improved
- **Synonym Logic**: Switched to multi-way (equivalent) synonyms by default for better search expansion (e.g., searching "tote" will now correctly find "bag").
- **Sync Reliability**: Added explicit error reporting for synonym synchronization with Typesense.
 
### Fixed
- **Search Expansion**: Fixed an issue where synonyms were being sent as one-way rules, preventing complete results for related terms.
 
## [1.2.7] - 2026-04-06
 
### Fixed
- **Synonym Saving**: Resolved a structural mismatch between the Admin UI and the REST API that prevented synonyms from saving and syncing to Typesense.
- **UI Rendering**: Fixed a bug where "undefined" was prepended to synonym groups in the Admin textarea when groups lacked a root word.

## [1.2.4] - 2026-04-06

### Added
- UX: 'Re-index Required' warning in Searchable Content settings when post types are changed.
- Logic: Automatic fallback to `indexed_post_types` in search frontend if no explicit scope is defined.

### Fixed
- Bug: 'Advanced Data' custom fields not persisting when removed in the UI (RestController logic fix).
- Bug: Hardcoded 'Products & Posts' heading in search results; now dynamically updates based on active post types.
- Bug: 'Sample Page' appearing when only Products were selected; implemented strict `post_type` filtering in Typesense queries.
- Bug: Corrected sanitization for boolean `facet` values in custom field settings.

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
