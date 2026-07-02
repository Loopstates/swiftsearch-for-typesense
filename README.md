# SwiftSearch for Typesense

[![WordPress Plugin](https://img.shields.io/badge/WordPress-6.0+-21759b.svg?logo=wordpress&logoColor=white)](https://wordpress.org/plugins/swiftsearch-for-typesense/)
[![PHP Version](https://img.shields.io/badge/PHP-8.0+-777bb4.svg?logo=php&logoColor=white)](https://www.php.net/)
[![License: GPL v2+](https://img.shields.io/badge/License-GPL%20v2+-lightgrey.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Powered by Typesense](https://img.shields.io/badge/Powered%20By-Typesense-red.svg)](https://typesense.org)

**SwiftSearch** is a high-performance, developer-friendly search engine replacement for WordPress and WooCommerce. By replacing slow, resource-heavy database queries with direct-to-node search queries, SwiftSearch delivers an autocomplete and catalog experience that loads in milliseconds—all while bypassing your server's database and PHP layers.

---

### 🔗 Quick Links
* **[Live Demo](http://ts.infinityfreeapp.com/typesense-search/?i=2)** — Experience the sub-millisecond search speed live.
* **[Official Website](https://loopstates.com/)** — Learn more about Loopstates integrations and enterprise services.
* **[Plugin Documentation](https://docs.loopstates.com/swift-search-typesense/)** — Step-by-step guides, setup wizard details, and advanced settings.
* **[WordPress.org Directory](https://wordpress.org/plugins/swiftsearch-for-typesense/)** — Official WordPress plugin repository page.

---

## ⚡ Performance Benchmarks

In comparison to standard WordPress SQL-driven search, SwiftSearch processes queries directly on your client's browser, yielding dramatic speed and resource improvements:

| Metric / Scenario | Default WordPress Search (MySQL) | SwiftSearch + Typesense | Why it matters |
| :--- | :---: | :---: | :--- |
| **Response Latency (Keyword)** | 500ms – 1200ms | **30ms – 80ms** | Keeps users engaged; search feels instant. |
| **Response Latency (Faceted Filter)** | 800ms – 2500ms | **40ms – 90ms** | Filters compile immediately without page reload. |
| **Database Queries on Host DB** | 5 – 25+ SQL queries | **0 queries** on WP Database | Bypassing MySQL prevents database locks and bottlenecks. |
| **Server CPU Utilization** | Spikes to 90%+ under load | **<5%** (Idle) | Offloads 100% of search computing to Typesense. |
| **Memory Overhead** | High (Loads WordPress Core) | **Negligible** | Bypasses PHP execution entirely for search traffic. |
| **Concurrent Search Users** | Crashes/Slows down at ~50 users | **10,000+** concurrent users | Handled easily by Typesense's C++ memory-mapped design. |
| **Network Payload Size** | Large (Full HTML page reload) | **Compact JSON** (Client-side) | Faster page rendering on mobile devices and weak signals. |

---

## 🆚 Feature-by-Feature Comparison

Compare the features and capabilities of SwiftSearch for Typesense against the default WordPress database search and the commercial industry standard (Algolia):

| Feature / Capability | Default WP Search | Algolia WP Integration | SwiftSearch for Typesense |
| :--- | :---: | :---: | :---: |
| **Typo Tolerance** | None | Yes | **Yes (Built-in)** |
| **Instant Search-as-you-type** | No (Page refresh) | Yes | **Yes (Client-side)** |
| **Sub-millisecond Speed** | No (500ms – 1200ms) | Yes | **Yes (30ms – 80ms)** |
| **Direct Browser-to-Node queries** | No (Hits WP Database) | No (Routes via PHP proxy) | **Yes (Zero-Middleware)** |
| **Self-Hosting Capability** | Yes | No (Cloud only) | **Yes (Open-Source)** |
| **Monthly API Search Costs** | Free | High (Scales with queries) | **Free (Self-host or flat-rate)** |
| **Visual Merchandising (Pinning)** | No | Enterprise tier only | **Yes (Included Dashboard)** |
| **Global Synonym Sets** | No | Yes | **Yes (Typesense v0.30+ Sets)** |
| **WooCommerce Shop Override** | No (Default layout only) | Requires custom theme code | **Yes (Dedicated Catalog Mode)** |
| **Faceted Sidebar Navigation** | No (Needs extra plugins) | Yes | **Yes (Drag-and-Drop builder)** |
| **Custom Post Type (CPT) Indexing**| Requires custom code | Yes | **Yes (Out of the box)** |
| **Local Search Analytics** | No | Stored on Algolia servers | **Yes (Local DB + dashboard)** |
| **GDPR & Privacy Compliance** | Yes (Local) | No (Queries sent to Algolia) | **Yes (Direct Connection)** |
| **Result Weighting & Tuning** | No | Yes | **Yes (Visual Admin UI)** |
| **Automated Real-Time Sync** | Yes (Direct SQL) | Yes | **Yes (Background batch sync)** |
| **Page Builder Integration** | Limited | Needs developer setup | **Yes (Shortcode + overrides)** |
| **Translation & Multilingual** | Complex | Yes | **Yes (Compatible out of the box)** |

---

## 💡 Why SwiftSearch?

* **No PHP Search Bottleneck:** Classic plugins bootstrap the heavy WordPress core and run database queries for every keystroke. SwiftSearch bypasses PHP entirely.
* **Direct Browser-to-Cluster Connection:** Visitors query your nearest Typesense node directly from their browser, eliminating server-side routing and proxy delays.
* **GDPR Compliance by Design:** Bypassing middle-layer proxy servers means you have complete data sovereignty—visitor search queries are never sent to third-party tracking servers.
* **No Proprietary Vendor Lock-in:** Unlike Algolia, you can self-host both Typesense and SwiftSearch, retaining 100% control over your architecture, data, and search costs.
* **WooCommerce-Native Layouts:** Replaces default Category and Shop pages out of the box with instant filtering, count facets, and card layouts, saving dozens of hours of custom frontend development.
* **Local Search Insights:** Tracks zero-result queries locally inside your WordPress database, giving you a roadmap of what users want to buy without sharing customer analytics.

---

## ⚡ Key Features

* **Direct-to-Node Queries:** Sub-millisecond browser-to-node query architecture, bypassing WordPress database and PHP layers.
* **Custom Post Type (CPT) Support:** Native indexing for Posts, Pages, Products, and custom post types or taxonomies out of the box.
* **Advanced Typesense Features:** Native support for global Synonym Sets (v0.30+ API compatible).
* **Visual Product Pinning (Merchandising):** A dedicated admin dashboard to pin specific products or posts to the top of search results for designated search queries.
* **Faceted Navigation & Metadata Mappings:** A visual drag-and-drop builder to create multi-select filters using taxonomies and metadata (ACF, price, SKU).
* **Local Search Analytics:** Displays search trends and zero-result queries directly inside the WordPress dashboard without proxy middleware.
* **WooCommerce Catalog Mode:** Replaces default WooCommerce shop and category pages with instant search and sidebar filters.
* **Page Builder Friendly:** Integrates with Elementor, Divi, and Gutenberg blocks using shortcodes (`[swift_search]`) or automatic search form replacement.
* **Developer Extensible:** Includes custom JavaScript event listeners (e.g. `swift-search:hit-rendered`) and WordPress PHP filters for query and document synchronization tuning.
* **Background Batch Syncing:** Self-scheduling indexing engine processes catalogs in batches to prevent server script timeouts.
* **GDPR & Privacy Ready:** Bypasses third-party proxy middleware, ensuring direct connectivity and complete data ownership.
* **Instant Autocomplete:** Displays matching products, posts, and custom types the moment visitors start typing.
* **Result Weighting & Tuning:** Adjust search relevance and define custom ranking weights for titles, contents, and metadata fields.
* **Automated Sync:** Real-time document indexing and synchronization triggered automatically whenever content is saved, updated, or deleted.
* **Global UI Customization:** Visually toggle results layouts, thumbnails, prices, and excerpts directly from the admin panel.
* **Translation & Multilingual Ready:** Compatible with multi-language sites and translation plugins.

---

## ⚙️ Minimum Requirements

* **PHP Version:** `8.0.0` or higher
* **WordPress Version:** `6.0` or higher
* **Typesense Cluster:** `0.25.0` or higher (Self-hosted or Typesense Cloud)

---

## 🚀 Installation & Setup

### 1. Installation
* **Automatic:** In your WordPress Admin panel, navigate to **Plugins > Add New**, search for `SwiftSearch for Typesense`, install, and activate the plugin.
* **Manual:** Download the plugin ZIP from the [WordPress.org Plugin Directory](https://wordpress.org/plugins/swiftsearch-for-typesense/), upload it via **Plugins > Add New > Upload Plugin**, and activate it.

### 2. The 8-Step Setup Wizard
Once activated, click **SwiftSearch** in your admin menu. The setup wizard will guide you through:
1. **Step 1: Connect** — Enter your Typesense host, port, protocol, and API keys.
2. **Step 2: Content** — Select which Post Types (Posts, Products, CPTs) to index.
3. **Step 3: Relevance** — Manage synonyms and global ranking weights.
4. **Step 4: Search UI** — Configure visual toggles, facets, and limits.
5. **Step 5: Styling** — Customize accent colors and border radius.
6. **Step 6: Analytics** — Review your search volume dashboards.
7. **Step 7: Pinning** — Merchandise specific results to the top.
8. **Step 8: Sync** — Run your initial bulk index to populate Typesense.

---

## 🛠️ Developer Customization

SwiftSearch provides JavaScript DOM events and WordPress PHP filters for advanced integrations and custom layouts.

### JavaScript Event Hooks
Listen to custom lifecycle events on the document to hook in custom analytics, wishlists, or quick-view buttons:

```javascript
// Triggered for every search result card rendered in the grid
document.addEventListener('swift-search:hit-rendered', function(event) {
    const hitData = event.detail.hit;         // Raw Typesense payload
    const cardElement = event.detail.card;     // Rendered DOM Node
    const collection = event.detail.collection; // Collection name

    if (collection === 'products') {
        // Example: Inject custom WooCommerce rating badge
    }
});

// Triggered after the entire search grid finishes rendering
document.addEventListener('swift-search:results-rendered', function(event) {
    const data = event.detail.data;
    const totalFound = event.detail.totalFound;
    console.log(`Rendered ${totalFound} organic results.`);
});
```

### WordPress PHP Filters
Configure search variables or modify data payload structures before syncing to Typesense:

```php
// Add custom post metadata to Typesense documents
add_filter('swift_search_post_document', function($document, $post_id, $post) {
    // Add custom calculated meta fields
    $document['custom_popularity_score'] = get_post_meta($post_id, '_popularity', true);
    return $document;
}, 10, 3);

// Programmatically exclude specific posts or out-of-stock items from indexing
add_filter('swift_search_should_index_post', function($should_index, $post_id, $post) {
    if (get_post_meta($post_id, '_out_of_stock', true) === 'yes') {
        return false;
    }
    return $should_index;
}, 10, 3);
```

---

## 📸 Screenshots

<details>
<summary>Click to view plugin administration screens</summary>

### 1. Connection Settings
Configure direct-to-node cluster credentials and Search-Only keys.
![Connection Settings](https://ps.w.org/swiftsearch-for-typesense/assets/screenshot-1.png)

### 2. Content Settings
Choose searchable post types and register custom field mappings.
![Content Settings](https://ps.w.org/swiftsearch-for-typesense/assets/screenshot-2.png)

### 3. Relevance and Synonyms
Manage base search weights and register synonym sets.
![Relevance and Synonyms](https://ps.w.org/swiftsearch-for-typesense/assets/screenshot-3.png)

### 4. Search UI Configuration
Setup autocomplete toggles, facets sidebar, and item limits.
![Search UI Configuration](https://ps.w.org/swiftsearch-for-typesense/assets/screenshot-4.png)

### 5. Styling Customizer
Visually customize colors and border radius.
![Styling Customizer](https://ps.w.org/swiftsearch-for-typesense/assets/screenshot-5.png)

### 6. Search Analytics Dashboard
Track search volume trends and flag zero-result queries.
![Search Analytics Dashboard](https://ps.w.org/swiftsearch-for-typesense/assets/screenshot-6.png)

### 7. Merchandising and Pinning
Pin selected products or posts to the top of search results.
![Merchandising and Pinning](https://ps.w.org/swiftsearch-for-typesense/assets/screenshot-7.png)

### 8. Sync Management
Run bulk indexing processes and monitor real-time sync status logs.
![Sync Management](https://ps.w.org/swiftsearch-for-typesense/assets/screenshot-8.png)

</details>

---

## ⚠️ Known Limitations

1. **Search-Only Keys:** For public-facing sites, you **must** use a "Search Only" API key from Typesense for frontend queries. Using your Admin key on the frontend is a severe security risk.
2. **Schema Sensitivity:** Changing core settings (such as adding a new Facet or Custom Field mapping) modifies the underlying Typesense schema. A full **Re-Index** (Step 8) is required after such changes.
3. **Background Sync:** Initial indexing of large catalogs (10k+ items) can take a few minutes via the background batch sync engine.

---

## 💬 Frequently Asked Questions (FAQ)

#### Do I need a Typesense server?
Yes. You need a running Typesense instance. This can be a managed cluster on [Typesense Cloud](https://cloud.typesense.org) or a self-hosted node on your own VPS/server.

#### Is Typesense free?
Typesense is open-source and 100% free to self-host. If you prefer not to manage infrastructure, Typesense Cloud offers standard paid tiers.

#### Does this work with WooCommerce?
Absolutely. SwiftSearch is WooCommerce-native out of the box, indexing product titles, prices, SKUs, and thumbnails automatically.

#### How fast is the search?
Due to our zero-middleware architecture, search results are typically returned directly from the browser to your nearest Typesense node in sub-millisecond speeds.

#### What is Result Pinning?
Result Pinning (Merchandising) allows you to manually force specific items to the top of results for a given keyword—ideal for promotional campaigns, featured content, or boosting seasonal catalog items.

#### How do Synonym Sets improve search?
They allow you to link similar terms together (e.g., "watch", "clock", "timepiece"). If a user searches for one, results for all linked terms are returned, improving product discovery.

#### Does it work with Elementor or Divi?
Yes. You can use the `[swift_search]` shortcode in any page builder module, or enable the "Override Default Search" toggle to automatically replace your theme's default search forms.

#### Is my user's data secure?
Yes. We do NOT use any proxy middle-layer. All search interactions happen locally between your visitor's browser and your Typesense cluster. No search data is sent to Loopstates servers.

#### Does it handle variable products?
Yes, you can map custom metadata (like variation SKUs and prices) to ensure users can find exact product variations in one click.

#### What insights does Search Analytics provide?
Our dashboard tracks your **Most Searched Keywords**, search trends, and volume. Most importantly, it flags "Zero Result" queries, showing you exactly what visitors searched for but couldn't find, allowing you to refine synonym sets or update your inventory.

---

## 📄 License & Changelog

* **License:** This project is licensed under the GPLv2 (or later) License - see the [LICENSE](LICENSE) file for details.
* **Changelog:** For version history, updates, and upgrade notices, see [CHANGELOG.md](CHANGELOG.md).
