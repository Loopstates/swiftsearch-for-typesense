=== SwiftSearch - Typesense Search for WordPress ===
Contributors: Loopstates
Tags: search, typesense, woocommerce, fast search, instant search
Requires at least: 5.8
Tested up to: 6.7
Stable tag: 1.3.13
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
 
Extremely fast, client-side search for WordPress & WooCommerce powered by Typesense.
 
== Description ==
 
**SwiftSearch** replaces the default WordPress search with a lightning-fast, typo-tolerant search engine powered by [Typesense](https://typesense.org).
Built by [Loopstates](https://loopstates.com).
 
Unlike other solutions that rely on slow, heavy middleware or third-party SaaS dashboards, SwiftSearch runs **100% on your WordPress server** (connecting directly to Typesense) and gives you full control.
 
### Key Features
 
*   **Lightning Fast**: Instant search results via Typesense.
*   **WooCommerce Support**: Index Products, SKUs, Prices, and Stock status.
*   **Typo Tolerance**: Handles misspellings out of the box.
*   **Facets**: Filter by Category, Tag, and Price (Pro).
*   **Zero Middleware**: Connects directly to your Typesense instance.
*   **Clean Admin UX**: A modern, step-by-step wizard to get you started in seconds.
 
### Free Version Features
 
*   Index Posts, Pages, and Products.
*   Manual Indexing.
*   Instant Search Shortcode `[swift_search]`.
*   Incremental Sync (On Save/Delete).
 
### Pro Features
 
*   **Background Indexing**: Queue-based indexing that doesn't slow down your site.
*   **Advanced Facets**: Configure sidebar filters for any taxonomy or custom field.
*   **Multi-language support**: Compatible with WPML.
*   **Priority Support**: Direct access to developers.
 
== Installation ==
 
1.  Upload the plugin files to the `/wp-content/plugins/swift-search-typesense` directory, or install the plugin through the WordPress plugins screen directly.
2.  Activate the plugin through the 'Plugins' screen in WordPress.
3.  Navigate to **SwiftSearch** in the admin menu.
4.  Enter your Typesense API Keys and Host details.
5.  Click "Connect" and then "Index All" to build your search index.
6.  Place the `[swift_search]` shortcode on any page to display the search bar.
 
== Privacy & Compliance ==
 
This plugin is 100% self-contained within your WordPress installation.
- **No External Servers**: The plugin does NOT communicate with any Loopstates servers. All search configuration (synonyms, weights) is stored locally.
- **Typesense**: The plugin connects directly from your visitor's browser to your Typesense node.
- **Licensing**: We use Freemius for license validation.
 
== Known Limitations ==
 
1. **API Keys**: For security, we recommend generating a "Search Only" API key in Typesense for the frontend configuration. Using your Admin API key is possible but not recommended for public-facing sites.
2. **Schema Changes**: Changing "Pro" settings (like enabling Stock Boost) changes the underlying schema. If you upgrade/downgrade, a full Re-Index is required.
3. **Background Indexing**: Indexing runs in the background. Large catalogs may take time to fully sync.
 
== Frequently Asked Questions ==
 
= Do I need a Typesense server? =
Yes, you need a running Typesense instance. You can use [Typesense Cloud](https://cloud.typesense.org) (easiest) or self-host strictly on your own VPS.
 
= Does this work with WooCommerce? =
Yes, SwiftSearch supports WooCommerce products out of the box, indexing titles, prices, SKUs, and thumbnails.
 
= Can I use this for free? =
Yes, the core features are free forever.
 
== Screenshots ==
 
1.  **Connection Wizard**: Easily connect to your Typesense cluster.
2.  **Instant Search**: Beautiful, fast search results on your frontend.
 
== Changelog ==
 
= 1.3.8 =
*   New: Added "Apply to Collections" checkboxes for synonyms. Admins can now select exactly which indices (Posts, Terms, Users) should have synonym expansion enabled.
*   Improvement: Automatic collection discovery from Typesense server for more reliable linking.

= 1.3.7 =
*   Fix: Resolved "Not linked" status in Typesense Cloud Dashboard. Implemented explicit collection-level synonym linking for full v0.30+ compatibility.
*   New: Implemented Global Synonym Sets architecture.

= 1.3.0 =
*   New: Full support for Typesense v0.30.1+ Global Synonym Sets.
*   Fix: Resolved 404 errors during synonym synchronization on modern Cloud clusters.
*   Improvement: Enhanced error reporting in Admin UI.
 
= 1.2.8 =
*   Improvement: Multi-way synonym support for better search expansion.
*   Improvement: Explicit error reporting for synonym synchronization.
 
= 1.2.7 =
*   Fix: Resolved synonym saving and Typesense synchronization logic mismatch.
*   Fix: Prevented 'undefined' string rendering in the admin synonyms list.
 
= 1.2.4 =
*   Fix: 'Advanced Data' custom fields now correctly persist deletions in the database.
*   New: Dynamic Search Results heading based on active post types (e.g. 'Products' vs 'Products & Posts').
*   New: Strict Post-Type filtering in search queries to prevent old content from appearing.
*   UX: Added 'Re-index Required' warning when searchable content types are changed.

= 1.2.3 =
*   Fix: Critical configuration bottleneck in ConfigLoader.php resolved.
*   Improvement: Enhanced Typesense Response logging for diagnostic transparency.
*   Version: Synced core logic with Universal Facet Registration Bridge.

= 1.2.2 =
*   Removed: Self-healing retry logic to ensure facet/schema errors are not hidden.
*   Improved: Concrete console logging for Typesense API errors.
*   Improved: JS array handling for facet configurations.
 
== Changelog ==

= 1.3.13 =
*   Fix: Resolved a critical JavaScript ReferenceError in search results processing.
*   Improved: Stabilized organic match counting for analytics.

= 1.3.12 =
*   Fix: Improved search analytics accuracy by excluding pinned items from organic match counts.
*   Improvement: Added CSS hooks for identifying pinned items in search results.

= 1.3.11 =
*   Fix: Search tracking for zero-result queries (previously skipped, now correctly populates analytics dashboard).
*   UI: Removed redundant synonym path debug buttons to clean up settings dashboard.

= 1.3.10 =
*   Fix: Modernized Typesense v0.30+ Global Synonym linking via collection schema patching.
*   Improvement: Dynamic admin UI for collection management (cached for performance).
*   UI: Fixed layout breakage in Relevance settings tab.

= 1.2.1 =
*   Fix: Resolved an issue where some enabled facets were hidden in the frontend sidebar due to redundant validation logic.
 
= 1.2.0 =
*   New: Universal Facet Registration Bridge.
*   New: Advanced Facet Settings (Target Mapping & Data Types).
*   Fix: Zero-results for numeric/boolean facets by implementing type-safe filtering.
*   Improvement: Unified schema and indexing logic for all WordPress plugins.
 
= 1.0.22 =
*   Fix: Facet configuration freezing on load due to data type mismatch.
*   Fix: Security warning persistence after saving settings.
*   Fix: Improved error handling for PHP-to-JS data serialization.

= 1.0.21 =
*   Fix: Saved Custom Fields button visibility.
*   Improvement: Connection workflow.

= 1.0.0 =
*   Initial release.
