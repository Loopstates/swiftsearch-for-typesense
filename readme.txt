=== SwiftSearch for Typesense ===
Contributors: loopstates
Donate link: https://loopstates.com
Tags: search, typesense, woocommerce, fast search, instant search
Requires at least: 6.0
Tested up to: 6.9
Stable tag: 1.4.3
Requires PHP: 8.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
 
Extremely fast, client-side search for WordPress & WooCommerce powered by Typesense. Sub-50ms latency and direct browser-to-node search clusters.

== Description ==

**SwiftSearch** replaces the slow, native WordPress search with a blazing-fast, typo-tolerant engine powered by [Typesense](https://typesense.org). 

Built by [Loopstates](https://loopstates.com), SwiftSearch is architected for maximum performance and privacy. Unlike other solutions that use heavy middleware, SwiftSearch connects **Directly from your visitors' browser to your Typesense cluster**. This "Zero-Middleware" approach results in sub-50ms search latency while keeping your user data secure.

**Official Documentation**: [https://docs.loopstates.com/swift-search-typesense/](https://docs.loopstates.com/swift-search-typesense/)


### Key Features (Free Forever)

*   **Instant Search**: Blazing fast search-as-you-type results.
*   **Typo Tolerance**: Native Typesense fuzzy matching handles misspellings out of the box.
*   **Background Indexing**: High-reliability "Chain Reaction" sync handles thousands of items without timing out.
*   **Automated Sync**: Real-time indexing when you Save, Update, or Delete any content.
*   **Unlimited CPT Support**: Index Posts, Pages, Products, and all Custom Post Types.
*   **Styling Customizer**: Dedicated visual controls for Primary colors, Text colors, and Card backgrounds.
*   **Global UI Toggles**: Instantly show or hide Thumbnails, Prices, and Excerpts globally.
*   **Developer Ready**: Built-in Custom CSS support and granular Shortcode overrides.
*   **WooCommerce Global Search**: Fully optimized for product titles, prices, and imagery.

### Pro Features (Merchandising & Relevance)

*   **Facet Configurator**: A dedicated visual builder to create and manage multi-select sidebar filters for taxonomies and metadata.
*   **Advanced Metadata Indexing**: Search and filter by any Custom Field (Price, SKU, Brand, etc.).
*   **Merchandising (Pinning)**: Manually fix specific items to the top of results for a curated user experience.
*   **Result Weighting**: fine-tune field importance (e.g., prioritize SKU hits over Content).
*   **Global Synonym Sets**: Integrated modern synonym architecture (Typesense v0.30+).
*   **Interactive Search Analytics**: Detailed dashboard for tracking popular searches and identifying "Zero Result" gaps.
*   **Priority Ecosystem Support**: Direct access to Loopstates developers for complex integrations.
 
== Installation ==

1.  Standard installation: Upload `swift-search-typesense` to your `/wp-content/plugins/` directory or install via the WordPress 'Plugins' screen.
2.  Activate the plugin and navigate to **SwiftSearch** in your admin menu.
3.  Follow our **8-Step Setup Wizard** in the sidebar:
    *   **Step 1: Connect** - Enter your Typesense host, port, and API keys.
    *   **Step 2: Content** - select your Post Types (Posts, Products, etc.) and enable the global search override.
    *   **Step 3: Relevance (Pro)** - Manage synonyms and global ranking scores.
    *   **Step 4: Search UI** - Configure your instant search behavior and visual result toggles.
    *   **Step 5: Styling** - Pick your accent colors and set your custom CSS.
    *   **Step 6: Analytics (Pro)** - Review your search trend dashboard.
    *   **Step 7: Pinning (Pro)** - Merchandise specific results to the top.
    *   **Step 8: Sync** - Perform your initial bulk index to build the Typesense collection.

== Privacy & Compliance ==

SwiftSearch is designed with a "Privacy First" architecture:
- **Zero Middleware**: We do NOT use any proxy or Loopstates-hosted servers. Search queries go directly from the user's browser to **your** Typesense cluster.
- **Self-Contained**: All configuration, analytics data, and result pinning rules are stored locally in your WordPress database.
- **GDPR Ready**: Since you control the Typesense node, you maintain full ownership of the search logs and user data flow.

**Third-Party Data Disclosure**:
This plugin uses [Freemius](https://freemius.com) for license management, optional telemetry, and automated updates. By registering your license, you agree to the [Freemius Privacy Policy](https://freemius.com/privacy). 
Note: Search queries and user interaction data are NEVER sent to Freemius or Loopstates; they stay strictly between the browser and your Typesense server.


== Known Limitations ==

1. **Search-Only Keys**: For public-facing sites, we strongly recommend using a "Search Only" API key from Typesense for frontend queries. Using your Admin key on the frontend is a security risk.
2. **Schema Sensitivity**: Changing core settings (like adding a new Facet or Custom Field) modifies the underlying Typesense schema. A full **Re-Index** (Step 8) is required after such changes.
3. **Background Sync**: Initial indexing of large catalogs (10k+ items) can take a few minutes via the Chain Reaction sync engine.

== Frequently Asked Questions ==

= Do I need a Typesense server? =
Yes. You need a running Typesense instance. This can be [Typesense Cloud](https://cloud.typesense.org) or a self-hosted node on your own VPS.

= Is Typesense free? =
Typesense is open-source and free to self-host. If you prefer a managed solution, Typesense Cloud offers a paid tier with global clusters.

= Does this work with WooCommerce? =
Absolutely. SwiftSearch is WooCommerce-native, indexing product titles, prices, SKUs, and thumbnails automatically.

= How fast is the search? =
Because of our "Zero-Middleware" architecture, search results are typically returned in **under 50ms**, directly from the browser to your nearest Typesense node.

= What is Result Pinning (Pro)? =
Result Pinning (Merchandising) allows you to manually force specific items to the top of results for a given keyword—perfect for boosting sales of specific products or featured content.

= How do Synonym Sets (Pro) improve search? =
They allow you to link similar terms together (e.g., "watch", "clock", "timepiece"). If a user searches for one, results for all are returned, significantly improving discovery.

= Is the Background Indexing free? =
Yes! Our high-reliability "Chain Reaction" sync engine is included in the free version to ensure every Loopstates user has a stable search experience.

= What is the Facet Configurator (Pro)? =
It's a professional-grade visual builder found in Step 4 that allows you to easily create and manage multi-select sidebar filters for categories, tags, and custom meta.

= Does it work with Elementor or Divi? =
Yes. You can use the `[swift_search]` shortcode in any page builder module, or enable the "Override Default" toggle to automatically replace your theme's search form.

= Is my user's data secure? =
Yes. We do NOT use a proxy server. All search interactions happen locally between your visitor and your Typesense cluster. No search data is sent to Loopstates.

= Does it handle variable products? =
In the Pro version, you can map custom metadata (like variation SKUs and prices) to ensure users can find exact product variations in one click.

= What insights does Search Analytics (Pro) provide? =
Track your **Most Searched Keywords** in real-time. Our professional dashboard provides a complete landscape of user trends, keyword volume, and search behavior. It also identifies "Zero Result" queries, giving you a full roadmap to optimize your product catalog and content strategy.


== Screenshots ==

1.  **Connection Wizard**: Direct connection setup for Typesense clusters.
2.  **Instant Search UI**: Blazing fast results with typo tolerance.
3.  **Facet Configurator**: Professional visual builder for search filters.
 
== Changelog ==
 
= 1.4.3 =
* Security: Complete SQL hardening by removing all variable interpolation from $wpdb->prepare statements.
* Security: Implemented mandatory nonce verification and strict input sanitization for all processes.
* Compliance: Prefixed all internal hooks and variables to ensures zero collisions.
* Compliance: Replaced legacy strip_tags() with wp_strip_all_tags() and fixed deprecated parameters.
* Maintenance: Truncated short description to <150 characters as per repository requirements.

= 1.4.2 =
* Fix: Resolved a "ReferenceError: resolve is not defined" in the frontend search script that caused search clusters to fail during user input.
* Improved: Hardened frontend fetch logic to gracefully handle network timeouts and server errors.

= 1.4.1 =
* Fix: Resolved a critical JavaScript syntax error in the admin dashboard that caused the "Connect" button to fail silently.
* Improved: Enhanced REST API error reporting for Typesense connection handshakes to provide localized, actionable feedback.
* Stability: Properly scoped variables and added robust XHR error parsing to prevent UI lockups.

= 1.4.0 =
* Security: Hardened all database interactions with $wpdb->prepare() for SQL injection prevention.
* Security: Implemented wp_unslash() and safe input handling for background indexing processes.
* Compliance: Replaced legacy strip_tags() with wp_strip_all_tags().
* Compliance: Removed redundant textdomain loading to follow WP.org central translation policy.
* Maintenance: Updated wp_count_terms() and date functions for WordPress 6.7 compatibility.
 
= 1.3.20 =
* Security: Hardened Pro feature enforcement with server-side validation for Synonyms, Weights, and Facets.
* Security: Implemented final frontend data masking to prevent Pro configuration leakage on Free versions.
* Security: Added Indexer protection to block premium metadata extraction for non-licensed users.
* Performance: Optimized license checking with static caching in Gatekeeper.
 
= 1.3.19 =
* Fix: Corrected accidental "faded" disabled style from the "Show Excerpts" setting.
 
= 1.3.18 =
* Fix: Resolved critical "undefined" JavaScript error when loading the admin dashboard.
* Maintenance: Removed orphaned references to legacy shortcode overrides.
 
= 1.3.17 =
* UX: Reordered Search UI tab to place Experience Options at the top.
* Feature: Fully implemented Frontend visibility toggles for Thumbnails, Prices, and Excerpts.
* Roadmap: Added "Coming Soon" placeholders for Sort Results and Floating Mobile Button.
* Fix: Cleaned up redundant Instant Search override from Shortcode Generator.
 
= 1.3.16 =
* Refactor: Reorganized Experience settings to prioritize Global defaults over overrides.
* Feature: Moved Thumbnail, Price, and Excerpt toggles to Global Experience Options.
* Fix: Consolidate Shortcode Generator to only output attributes for explicit overrides.
* Fix: Resolved a structural JavaScript error in `admin.js` that was breaking state restoration.
 
= 1.3.15 =
* UI Optimization: Consolidated redundant "Instant Search" setting in the admin dashboard.
* Roadmap: Labeled Sort and Mobile experience options as "Coming Soon" to accurately reflect current implementation status.
* Improved UX: Corrected feature descriptions and added visual states for disabled roadmap settings.
 
= 1.3.14 =
* Fix: Resolved a critical JavaScript ReferenceError in search results processing.
* Improved: Stabilized organic match counting for analytics.
 
= 1.3.12 =
* Fix: Improved search analytics accuracy by excluding pinned items from organic match counts.
* Improvement: Added CSS hooks for identifying pinned items in search results.
 
= 1.3.11 =
* Fix: Search tracking for zero-result queries (previously skipped, now correctly populates analytics dashboard).
* UI: Removed redundant synonym path debug buttons to clean up settings dashboard.
 
= 1.3.10 =
* Fix: Modernized Typesense v0.30+ Global Synonym linking via collection schema patching.
* Improvement: Dynamic admin UI for collection management (cached for performance).
* UI: Fixed layout breakage in Relevance settings tab.
 
= 1.3.0 =
* New: Full support for Typesense v0.30.1+ Global Synonym Sets.
* Fix: Resolved 404 errors during synonym synchronization on modern Cloud clusters.
* Improvement: Enhanced error reporting in Admin UI.
* Improvement: Multi-way synonym support for better search expansion.
 
= 1.2.7 =
* Fix: Resolved synonym saving and Typesense synchronization logic mismatch.
* Fix: Prevented 'undefined' string rendering in the admin synonyms list.
 
= 1.2.4 =
* Fix: 'Advanced Data' custom fields now correctly persist deletions in the database.
* New: Dynamic Search Results heading based on active post types (e.g. 'Products' vs 'Products & Posts').
* New: Strict Post-Type filtering in search queries to prevent old content from appearing.
* UX: Added 'Re-index Required' warning when searchable content types are changed.
 
= 1.2.3 =
* Fix: Critical configuration bottleneck in ConfigLoader.php resolved.
* Improvement: Enhanced Typesense Response logging for diagnostic transparency.
* Version: Synced core logic with Universal Facet Registration Bridge.
 
= 1.2.1 =
* Fix: Resolved an issue where some enabled facets were hidden in the frontend sidebar due to redundant validation logic.
 
= 1.2.0 =
* New: Universal Facet Registration Bridge.
* New: Advanced Facet Settings (Target Mapping & Data Types).
* Fix: Zero-results for numeric/boolean facets by implementing type-safe filtering.
* Improvement: Unified schema and indexing logic for all WordPress plugins.
 
= 1.0.22 =
* Fix: Facet configuration freezing on load due to data type mismatch.
* Fix: Security warning persistence after saving settings.
* Fix: Improved error handling for PHP-to-JS data serialization.
 
= 1.0.21 =
* Fix: Saved Custom Fields button visibility.
* Improvement: Connection workflow.
 
= 1.0.0 =
* Initial release.
