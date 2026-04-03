# Changelog

All notable changes to the SwiftSearch - Typesense Search for WordPress plugin will be documented in this file.

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
