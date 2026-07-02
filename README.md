# SwiftSearch for Typesense

[![WordPress Plugin](https://img.shields.io/badge/WordPress-6.0+-21759b.svg?logo=wordpress&logoColor=white)](https://wordpress.org/plugins/swiftsearch-for-typesense/)
[![PHP Version](https://img.shields.io/badge/PHP-8.0+-777bb4.svg?logo=php&logoColor=white)](https://www.php.net/)
[![License: GPL v2+](https://img.shields.io/badge/License-GPL%20v2+-lightgrey.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Powered by Typesense](https://img.shields.io/badge/Powered%20By-Typesense-red.svg)](https://typesense.org)

**SwiftSearch** is a high-performance, developer-friendly search engine replacement for WordPress and WooCommerce. By replacing slow, resource-heavy database queries with direct-to-node search queries, SwiftSearch delivers an autocomplete and catalog experience that loads in milliseconds—all while bypassing your server's database and PHP layers.

---

### 🔗 Quick Links
* **[Official Website](https://loopstates.com/)** — Learn more about Loopstates integrations and enterprise services.
* **[Plugin Documentation](https://docs.loopstates.com/swift-search-typesense/)** — Step-by-step guides, setup wizard details, and advanced settings.
* **[WordPress.org Directory](https://wordpress.org/plugins/swiftsearch-for-typesense/)** — Official WordPress plugin repository page.

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

## 📄 License & Changelog

* **License:** This project is licensed under the GPLv2 (or later) License - see the [LICENSE](LICENSE) file for details.
* **Changelog:** For version history, updates, and upgrade notices, see [CHANGELOG.md](CHANGELOG.md).
