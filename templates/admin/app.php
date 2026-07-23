<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="swift-search-dashboard" id="swift-search-app">
    <header class="ss-header">
        <div class="ss-logo">
            <img src="<?php echo esc_url(SWIFT_SEARCH_URL . 'assets/images/swift-search-typesense.png'); ?>"
                alt="SwiftSearch" class="ss-brand-logo"
                style="max-height: 40px; margin-right: 12px; vertical-align: middle;">
            <h1 style="display: inline-block; vertical-align: middle; margin: 0;">SwiftSearch for Typesense <span
                    class="ss-version">v<?php echo esc_html(SWIFT_SEARCH_VERSION); ?></span></h1>
        </div>
        <div class="ss-actions" style="display: flex; align-items: center; gap: 15px;">
            <a href="https://docs.loopstates.com/swift-search-typesense/?ref=wp-plugin" target="_blank"
                class="ss-link" style="margin-left: 0;"><?php esc_html_e('Documentation', 'swiftsearch-for-typesense'); ?></a>
            <span class="ss-link-separator" style="color: var(--ss-text-muted); opacity: 0.5;">|</span>
            <a href="https://docs.loopstates.com/swift-search-typesense/support.html?action=contact&ref=wp-plugin" target="_blank"
                class="ss-link" style="margin-left: 0;"><?php esc_html_e('Need Customization?', 'swiftsearch-for-typesense'); ?></a>
        </div>
    </header>

    <div class="ss-container">
        <!-- Sidebar Navigation -->
        <aside class="ss-sidebar">
            <nav class="ss-nav">
                <ul>
                    <li class="ss-nav-item active" data-step="connect">
                        <span class="step-num">1</span>
                        <span class="step-label"><?php esc_html_e('Connect', 'swiftsearch-for-typesense'); ?></span>
                    </li>
                    <li class="ss-nav-item" data-step="content">
                        <span class="step-num">2</span>
                        <span class="step-label"><?php esc_html_e('Content', 'swiftsearch-for-typesense'); ?></span>
                    </li>
                    <li class="ss-nav-item" data-step="relevance">
                        <span class="step-num">3</span>
                        <span class="step-label"><?php esc_html_e('Relevance', 'swiftsearch-for-typesense'); ?></span>
                    </li>
                    <li class="ss-nav-item" data-step="search-ui">
                        <span class="step-num">4</span>
                        <span class="step-label"><?php esc_html_e('Search UI', 'swiftsearch-for-typesense'); ?></span>
                    </li>
                    <li class="ss-nav-item" data-step="styling">
                        <span class="step-num">5</span>
                        <span class="step-label"><?php esc_html_e('Styling & UI', 'swiftsearch-for-typesense'); ?></span>
                    </li>
                    <li class="ss-nav-item" data-step="analytics">
                        <span class="step-num">6</span>
                        <span class="step-label"><?php esc_html_e('Analytics', 'swiftsearch-for-typesense'); ?></span>
                    </li>
                    <li class="ss-nav-item" data-step="pinning">
                        <span class="step-num">7</span>
                        <span class="step-label"><?php esc_html_e('Pinning', 'swiftsearch-for-typesense'); ?></span>
                    </li>
                    <li class="ss-nav-item" data-step="sync">
                        <span class="step-num">8</span>
                        <span class="step-label"><?php esc_html_e('Sync', 'swiftsearch-for-typesense'); ?></span>
                    </li>
                </ul>
            </nav>

            <div class="ss-status-card">
                <h3><?php esc_html_e('System Status', 'swiftsearch-for-typesense'); ?></h3>
                <div class="status-row">
                    <span class="label">Typesense</span>
                    <span class="value disconnected"
                        id="ss-connection-status"><?php esc_html_e('Disconnected', 'swiftsearch-for-typesense'); ?></span>
                </div>
                <div class="status-row">
                    <span class="label">Documents</span>
                    <span class="value" id="ss-doc-count">0</span>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="ss-content">
            <!-- Global Warning Container -->
            <div id="ss-global-notice-container" style="margin-bottom: 20px;"></div>

            <!-- Step 1: Connect -->
            <section class="ss-step-view active" id="view-connect">
                <div class="ss-card">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('Connect to Typesense', 'swiftsearch-for-typesense'); ?></h2>
                        <p><?php esc_html_e('Enter your Typesense server credentials. You can use Typesense Cloud or a self-hosted node.', 'swiftsearch-for-typesense'); ?>
                        </p>
                    </div>
                    <div class="ss-card-body">
                        <form id="ss-connect-form">
                            <div class="ss-form-group">
                                <label for="ts-host"><?php esc_html_e('Host', 'swiftsearch-for-typesense'); ?></label>
                                <input type="text" id="ts-host" name="host" placeholder="e.g. xxx.a1.typesense.net"
                                    required>
                            </div>
                            <div class="ss-form-group-row">
                                <div class="ss-form-group">
                                    <label for="ts-port"><?php esc_html_e('Port', 'swiftsearch-for-typesense'); ?></label>
                                    <input type="number" id="ts-port" name="port" value="443" required>
                                </div>
                                <div class="ss-form-group">
                                    <label
                                        for="ts-protocol"><?php esc_html_e('Protocol', 'swiftsearch-for-typesense'); ?></label>
                                    <select id="ts-protocol" name="protocol">
                                        <option value="https">https</option>
                                        <option value="http">http</option>
                                    </select>
                                </div>
                            </div>
                            <div class="ss-form-group">
                                <label
                                    for="ts-api-key"><?php esc_html_e('Admin API Key', 'swiftsearch-for-typesense'); ?></label>
                                <div class="ss-input-wrapper">
                                    <input type="password" id="ts-api-key" name="api_key" required>
                                    <button type="button" class="ss-input-toggle ss-toggle-api-key" title="<?php esc_attr_e('Toggle Visibility', 'swiftsearch-for-typesense'); ?>">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </button>
                                </div>
                                <p class="description">
                                    <?php esc_html_e('Required for indexing actions.', 'swiftsearch-for-typesense'); ?>
                                </p>
                            </div>
                            <div class="ss-form-group">
                                <label
                                    for="ts-search-key"><?php esc_html_e('Search-Only API Key', 'swiftsearch-for-typesense'); ?></label>
                                <div class="ss-input-wrapper">
                                    <input type="password" id="ts-search-key" name="search_key">
                                    <button type="button" class="ss-input-toggle ss-toggle-api-key" title="<?php esc_attr_e('Toggle Visibility', 'swiftsearch-for-typesense'); ?>">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </button>
                                </div>
                                <p class="description">
                                    <?php esc_html_e('Public key for the frontend search.', 'swiftsearch-for-typesense'); ?>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="ss-form-actions" style="margin-top: 20px;">
                    <button type="button" id="ss-connect-btn"
                        class="ss-btn ss-btn-primary"><?php esc_html_e('Save & Test Connection', 'swiftsearch-for-typesense'); ?></button>
                </div>
            </section>

            <!-- Step 2: Content -->
            <section class="ss-step-view" id="view-content" style="display:none;">
                <div class="ss-card">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('Search Behavior', 'swiftsearch-for-typesense'); ?></h2>
                        <p><?php esc_html_e('Configure how SwiftSearch integrates with your theme.', 'swiftsearch-for-typesense'); ?>
                        </p>
                    </div>
                    <div class="ss-card-body">
                        <div class="ss-form-group-row">
                            <div class="ss-form-group">
                                <label class="ss-checkbox-card" style="width: 100%;">
                                    <input type="checkbox" id="ss-override-default">
                                    <div class="info">
                                        <span
                                            class="title"><?php esc_html_e('Override Default Search', 'swiftsearch-for-typesense'); ?></span>
                                        <span
                                            class="meta"><?php esc_html_e('Automatically replace the native WordPress search form with SwiftSearch.', 'swiftsearch-for-typesense'); ?></span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ss-card">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('Searchable Content', 'swiftsearch-for-typesense'); ?></h2>
                        <p><?php esc_html_e('Configure which content types (Posts, Pages, Products, Custom Types) should be indexed.', 'swiftsearch-for-typesense'); ?>
                        </p>
                    </div>
                    <div class="ss-card-body">
                        <!-- Content Checkboxes will be rendered here -->
                        <!-- Content Checkboxes will be rendered here -->
                        <div class="ss-option-list" id="ss-content-settings-container">
                            <!-- Dynamic Checkboxes -->
                        </div>
                    </div>
                </div>

                <!-- Advanced Data -->
                <div class="ss-card" style="margin-top: 20px;">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('Advanced Data', 'swiftsearch-for-typesense'); ?></h2>
                        <p><?php esc_html_e('Map custom meta fields to Typesense schema (e.g. Price, SKU, Brand).', 'swiftsearch-for-typesense'); ?>
                        </p>
                    </div>
                    <div class="ss-card-body" id="ss-custom-fields-container">
                        <!-- Rendered by JS -->
                        <div style="padding: 20px; text-align: center; color: #6b7280;">
                            <span class="ss-loader"></span> Loading Fields...
                        </div>
                    </div>
                </div>

                <div class="ss-form-actions" style="margin-top: 20px;">
                    <button type="button" class="ss-btn ss-btn-primary"
                        id="ss-save-content"><?php esc_html_e('Save Content Settings', 'swiftsearch-for-typesense'); ?></button>
                </div>
            </section>

            <!-- Step 3: Relevance -->
            <section class="ss-step-view" id="view-relevance" style="display:none;">
                <div class="ss-card">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('Relevance Settings', 'swiftsearch-for-typesense'); ?></h2>
                        <p><?php esc_html_e('Fine-tune search results ranking.', 'swiftsearch-for-typesense'); ?></p>
                    </div>
                    <div class="ss-card-body">
                        <div class="ss-relevance-settings-wrapper">
                            <div class="ss-form-group">
                                <label>Global Relevance Score: <span id="ss-relevance-val" style="font-weight:700;">50</span></label>
                                <input type="range" id="ss-relevance-range" min="1" max="100" value="50" oninput="document.getElementById('ss-relevance-val').innerText = this.value">
                                <p class="description">
                                    <?php esc_html_e('Adjust the base ranking weight for searches. Higher values favor exact matches.', 'swiftsearch-for-typesense'); ?>
                                </p>
                            </div>
                            <div class="ss-form-group">
                                <label>Synonyms</label>
                                <textarea id="ss-synonyms-list" rows="6" class="ss-input" placeholder="jacket, coat, blazer&#10;bag, backpack, tote&#10;trousers, pants"></textarea>
                                <p class="description">
                                    <?php esc_html_e('Enter each group of synonyms on a new line. Comma-separated.', 'swiftsearch-for-typesense'); ?>
                                </p>
                            </div>
                            <div class="ss-form-group" style="margin-top: 15px;">
                                <label><?php esc_html_e('Apply to Collections:', 'swiftsearch-for-typesense'); ?></label>
                                <div id="ss-synonym-collections-container" style="margin-top: 10px;">
                                    <p class="ss-hint" style="font-style: italic; opacity: 0.7;"><?php esc_html_e('Loading active collections...', 'swiftsearch-for-typesense'); ?></p>
                                </div>
                                <p class="ss-hint" style="margin-top: 8px;">
                                    <?php esc_html_e('Select which indices search results should expand with these synonyms.', 'swiftsearch-for-typesense'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="ss-form-actions" style="margin-top: 20px;">
                    <button type="button" class="ss-btn ss-btn-primary" id="ss-save-relevance"><?php esc_html_e('Save Relevance Settings', 'swiftsearch-for-typesense'); ?></button>
                </div>
            </section>

            <!-- Step 4: Search UI -->
            <section class="ss-step-view" id="view-search-ui" style="display:none;">
                <!-- Experience Options -->
                <div class="ss-card">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('Experience Options', 'swiftsearch-for-typesense'); ?></h2>
                    </div>
                    <div class="ss-card-body">
                        <div class="ss-form-group-row">
                            <div class="ss-form-group">
                                <label class="ss-checkbox-card" id="ss-instant-search-card">
                                    <input type="checkbox" id="ss-instant-search" checked>
                                    <div class="info">
                                        <span class="title"><?php esc_html_e('Instant Search', 'swiftsearch-for-typesense'); ?></span>
                                        <span class="meta"><?php esc_html_e('Search as you type. Shows results as the user enters characters.', 'swiftsearch-for-typesense'); ?></span>
                                    </div>
                                </label>
                            </div>
                            <div class="ss-form-group">
                                <label class="ss-checkbox-card">
                                    <input type="checkbox" id="ss-typo-tolerance" checked>
                                    <div class="info">
                                        <span class="title"><?php esc_html_e('Typo Tolerance', 'swiftsearch-for-typesense'); ?></span>
                                        <span class="meta"><?php esc_html_e('Enable fuzzy matching for spelling errors.', 'swiftsearch-for-typesense'); ?></span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="ss-form-group-row">
                            <div class="ss-form-group">
                                <label class="ss-checkbox-card">
                                    <input type="checkbox" id="ss-global-show-thumb" checked>
                                    <div class="info">
                                        <span class="title"><?php esc_html_e('Show Thumbnails', 'swiftsearch-for-typesense'); ?></span>
                                        <span class="meta"><?php esc_html_e('Display product or post images in results.', 'swiftsearch-for-typesense'); ?></span>
                                    </div>
                                </label>
                            </div>
                            <div class="ss-form-group">
                                <label class="ss-checkbox-card">
                                    <input type="checkbox" id="ss-global-show-price" checked>
                                    <div class="info">
                                        <span class="title"><?php esc_html_e('Show Prices', 'swiftsearch-for-typesense'); ?></span>
                                        <span class="meta"><?php esc_html_e('Display WooCommerce product prices.', 'swiftsearch-for-typesense'); ?></span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="ss-form-group-row">
                            <div class="ss-form-group">
                                <label class="ss-checkbox-card">
                                    <input type="checkbox" id="ss-global-show-excerpt">
                                    <div class="info">
                                        <span class="title"><?php esc_html_e('Show Excerpts', 'swiftsearch-for-typesense'); ?></span>
                                        <span class="meta"><?php esc_html_e('Display a short description below titles.', 'swiftsearch-for-typesense'); ?></span>
                                    </div>
                                </label>
                            </div>
                            <div class="ss-form-group">
                                <label><?php esc_html_e('Global Results Limit', 'swiftsearch-for-typesense'); ?></label>
                                <input type="number" id="ss-global-limit" value="10" min="1" max="50">
                                <p class="description"><?php esc_html_e('Default number of items to show per search.', 'swiftsearch-for-typesense'); ?></p>
                            </div>
                        </div>
                        <div class="ss-form-group-row">
                            <div class="ss-form-group">
                                <label style="display:block; margin-bottom: 8px; font-weight: 500;"><?php esc_html_e('Layout Setting', 'swiftsearch-for-typesense'); ?></label>
                                <select id="ss-global-layout" style="width: 100%; max-width: 400px; padding: 8px 12px; border-radius: 6px; border: 1px solid #d1d5db; height: 40px; box-sizing: border-box;">
                                    <option value="overlay"><?php esc_html_e('Search Bar (Floating Overlay / Dropdown)', 'swiftsearch-for-typesense'); ?></option>
                                    <option value="catalog"><?php esc_html_e('Page (Dedicated Catalog / Sticky Sidebar)', 'swiftsearch-for-typesense'); ?></option>
                                </select>
                            </div>
                            <div class="ss-form-group" style="display: flex; flex-direction: column; justify-content: center; padding-top: 24px;">
                                <div style="font-size: 13px; color: var(--ss-text-muted); line-height: 1.5; background: #fcfcfc; padding: 12px; border-radius: 6px; border: 1px solid #eee;">
                                    <strong><?php esc_html_e('Search Bar Mode (Floating Overlay)', 'swiftsearch-for-typesense'); ?></strong>: <?php esc_html_e('Displays results dynamically in a dropdown list right below the input field. Best for quick access from headers.', 'swiftsearch-for-typesense'); ?><br><br>
                                    <strong><?php esc_html_e('Page Mode (Dedicated Catalog)', 'swiftsearch-for-typesense'); ?></strong>: <?php esc_html_e('Loads a full-page search catalog interface with category filters, a sticky sidebar, and pagination. Ideal for a dedicated search results or shop page.', 'swiftsearch-for-typesense'); ?>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Shortcode Builder -->
                <div class="ss-card" style="margin-top: 30px;">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('Shortcode Generator (Overrides)', 'swiftsearch-for-typesense'); ?></h2>
                        <p><?php esc_html_e('Customize individual search bars. Unchecked items will use global defaults.', 'swiftsearch-for-typesense'); ?></p>
                    </div>
                    <div class="ss-card-body">
                        <div class="ss-form-group-row">
                            <div class="ss-form-group">
                                <label><?php esc_html_e('Placeholder Text', 'swiftsearch-for-typesense'); ?></label>
                                <input type="text" id="sc-placeholder" value="Search...">
                            </div>
                            <div class="ss-form-group">
                                <label><?php esc_html_e('Override Results Limit', 'swiftsearch-for-typesense'); ?></label>
                                <input type="number" id="sc-limit" placeholder="Inherit Global">
                            </div>
                        </div>

                        <div class="ss-card-grid">
                            <div class="ss-form-group">
                                <label class="ss-checkbox-card">
                                    <input type="checkbox" id="sc-show-thumb" checked>
                                    <div class="info">
                                        <span class="title"><?php esc_html_e('Show Thumbnail', 'swiftsearch-for-typesense'); ?></span>
                                    </div>
                                </label>
                            </div>
                            <div class="ss-form-group">
                                <label class="ss-checkbox-card">
                                    <input type="checkbox" id="sc-show-price" checked>
                                    <div class="info">
                                        <span class="title"><?php esc_html_e('Show Price', 'swiftsearch-for-typesense'); ?></span>
                                    </div>
                                </label>
                            </div>
                            <div class="ss-form-group">
                                <label class="ss-checkbox-card">
                                    <input type="checkbox" id="sc-show-excerpt">
                                    <div class="info">
                                        <span class="title"><?php esc_html_e('Show Excerpt', 'swiftsearch-for-typesense'); ?></span>
                                    </div>
                                </label>
                            </div>
                            <div class="ss-form-group">
                                <label style="display:block; margin-bottom: 8px; font-weight: 500;"><?php esc_html_e('Layout Override', 'swiftsearch-for-typesense'); ?></label>
                                <select id="sc-layout" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #d1d5db; height: 40px; box-sizing: border-box;">
                                    <option value="default"><?php esc_html_e('Inherit Global Layout', 'swiftsearch-for-typesense'); ?></option>
                                    <option value="overlay"><?php esc_html_e('Floating Search Overlay', 'swiftsearch-for-typesense'); ?></option>
                                    <option value="catalog"><?php esc_html_e('Dedicated Catalog Page', 'swiftsearch-for-typesense'); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="ss-form-group" style="margin-top: 15px;">
                            <label style="display:block; margin-bottom: 8px; font-weight: 500;">Limit Post Types (Optional)</label>
                            <div class="ss-checkbox-list" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; max-height: 150px; overflow-y: auto; padding: 10px; border: 1px solid #eee; border-radius: 4px;">
                                <?php foreach ($data['available_post_types'] as $swift_search_pt): ?>
                                    <label class="ss-checkbox-inline">
                                        <input type="checkbox" class="sc-post-type-selector" value="<?php echo esc_attr($swift_search_pt['name']); ?>">
                                        <?php echo esc_html($swift_search_pt['label']); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="ss-code-preview">
                            <code id="sc-preview">[swift_search]</code>
                            <button type="button" class="ss-btn ss-btn-sm ss-btn-secondary" id="ss-copy-sc">Copy</button>
                        </div>
                    </div>
                </div>

                <!-- Faceted Navigation -->
                <div class="ss-card">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('Faceted Navigation (Sidebar)', 'swiftsearch-for-typesense'); ?></h2>
                        <p><?php esc_html_e('Configure filters for your search results sidebar.', 'swiftsearch-for-typesense'); ?>
                        </p>
                    </div>
                    <div class="ss-card-body">
                        <div id="ss-facets-config-container">
                            <div style="padding: 20px; text-align: center; color: #6b7280;">
                                <span class="ss-loader"></span> Loading Facets...
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ss-form-actions" style="margin-top: 20px;">
                    <button type="button" class="ss-btn ss-btn-primary"
                        id="ss-save-search-ui"><?php esc_html_e('Save Search UI Settings', 'swiftsearch-for-typesense'); ?></button>
                </div>
            </section>
            
            <!-- Step 5: Styling & UI -->
            <section class="ss-step-view" id="view-styling" style="display:none;">
                <div class="ss-card">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('Styling & Customization', 'swiftsearch-for-typesense'); ?></h2>
                        <p><?php esc_html_e('Customize the appearance of your search results. Changes apply to the frontend search interface.', 'swiftsearch-for-typesense'); ?></p>
                    </div>
                    <div class="ss-card-body">
                         <div class="ss-form-group-row" style="gap: 20px;">
                            <div class="ss-form-group" style="flex:1;">
                                <label for="ss-primary-color"><?php esc_html_e('Primary Accent Color', 'swiftsearch-for-typesense'); ?></label>
                                <input type="color" id="ss-primary-color" class="ss-input-color" value="#ff0055">
                                <p class="description"><?php esc_html_e('Used for buttons, icons, and highlights.', 'swiftsearch-for-typesense'); ?></p>
                            </div>
                            <div class="ss-form-group" style="flex:1;">
                                <label for="ss-text-color"><?php esc_html_e('Main Text Color', 'swiftsearch-for-typesense'); ?></label>
                                <input type="color" id="ss-text-color" class="ss-input-color" value="#1f2937">
                                <p class="description"><?php esc_html_e('Color for titles and body text.', 'swiftsearch-for-typesense'); ?></p>
                            </div>
                            <div class="ss-form-group" style="flex:1;">
                                <label for="ss-card-bg"><?php esc_html_e('Card Background', 'swiftsearch-for-typesense'); ?></label>
                                <input type="color" id="ss-card-bg" class="ss-input-color" value="#ffffff">
                                <p class="description"><?php esc_html_e('Background color for result cards.', 'swiftsearch-for-typesense'); ?></p>
                            </div>
                        </div>

                        <div class="ss-form-group" style="margin-top: 20px;">
                            <label for="ss-border-radius"><?php esc_html_e('Border Radius (px)', 'swiftsearch-for-typesense'); ?></label>
                            <input type="number" id="ss-border-radius" class="ss-input" value="16" min="0" max="100">
                            <p class="description"><?php esc_html_e('Global roundness for cards, inputs, and buttons.', 'swiftsearch-for-typesense'); ?></p>
                        </div>


                    </div>
                </div>

                <div class="ss-form-actions" style="margin-top: 20px;">
                    <button type="button" class="ss-btn ss-btn-primary" id="ss-save-styling"><?php esc_html_e('Save Styling Settings', 'swiftsearch-for-typesense'); ?></button>
                </div>
            </section>

            <!-- Step 6: Analytics -->
            <section class="ss-step-view" id="view-analytics" style="display:none;">
                <div class="ss-card">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('Search Analytics', 'swiftsearch-for-typesense'); ?></h2>
                        <p><?php esc_html_e('Insights into what your users are searching for.', 'swiftsearch-for-typesense'); ?>
                        </p>
                    </div>
                    <div class="ss-card-body">
                        <div class="ss-analytics-dashboard" id="ss-analytics-dashboard">
                            <div class="ss-form-group" style="margin-bottom: 2rem; position: relative; height: 300px;">
                                <canvas id="ss-analytics-chart"></canvas>
                            </div>
                            <div class="ss-form-group-row">
                                <div class="ss-form-group" style="flex:1;">
                                    <h3>Top Searches</h3>
                                    <table class="wp-list-table widefat fixed striped" id="ss-analytics-top">
                                        <thead>
                                            <tr>
                                                <th>Query</th>
                                                <th>Count</th>
                                                <th>Last Searched</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="3">Loading...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="ss-form-group" style="flex:1;">
                                    <h3>Zero Result Queries</h3>
                                    <table class="wp-list-table widefat fixed striped" id="ss-analytics-zero">
                                        <thead>
                                            <tr>
                                                <th>Query</th>
                                                <th>Count</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="2">Loading...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Step 6: Results Pinning -->
            <section class="ss-step-view" id="view-pinning" style="display:none;">
                <div class="ss-card">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('Pinned Results', 'swiftsearch-for-typesense'); ?></h2>
                        <p><?php esc_html_e('Manually fix specific products or items to the top of search results.', 'swiftsearch-for-typesense'); ?>
                        </p>
                    </div>
                    <div class="ss-card-body">
                        <div class="ss-pinning-settings-wrapper">
                            <div class="ss-pinning-ui">
                                <div class="ss-form-group" style="position:relative;">
                                    <label><?php esc_html_e('Select Pinned Item', 'swiftsearch-for-typesense'); ?></label>
                                    <input type="text" id="ss-pinning-search" class="ss-input"
                                        placeholder="Type a product or an item name that you want to pin..."
                                        autocomplete="off">
                                    <div id="ss-pinning-results" class="ss-autocomplete-results"></div>
                                </div>

                                <div id="ss-pinned-list-container">
                                    <!-- Populated by JS -->
                                </div>

                                <div class="ss-empty-state" id="ss-pinning-empty"
                                    style="border: 2px dashed #e5e7eb; padding: 40px; text-align: center; border-radius: 8px; color: #6b7280; display:none;">
                                    <h3><?php esc_html_e('No pinned items', 'swiftsearch-for-typesense'); ?></h3>
                                    <p><?php esc_html_e('Search above to pin products to the top of results.', 'swiftsearch-for-typesense'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                </div>
            </div>
            <div class="ss-form-actions" style="margin-top: 20px;">
                <button type="button" class="ss-btn ss-btn-primary"
                    id="ss-save-pinning"><?php esc_html_e('Save Pinned Items', 'swiftsearch-for-typesense'); ?></button>
            </div>
            </section>

            <!-- Step 7: Sync & Usage -->
            <section class="ss-step-view" id="view-sync" style="display:none;">
                <div class="ss-card">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('Index Management', 'swiftsearch-for-typesense'); ?></h2>
                    </div>
                    <div class="ss-card-body">
                        <div class="ss-sync-card-container" style="display: flex; gap: 30px; align-items: center;">
                            <div class="ss-sync-visual" style="flex: 0 0 150px; text-align: center;">
                                <div class="progress-circle" data-percent="0" style="margin: 0 auto 15px;">
                                    <span style="font-size: 24px;">0%</span>
                                </div>
                                <span class="ss-status-badge"
                                    style="background: #e5e7eb; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; color: #4b5563;">Progress</span>
                            </div>
                            <div class="ss-sync-details" style="flex: 1;">
                                <h3 style="margin: 0 0 10px 0; font-size: 16px;">Index Synchronization</h3>
                                <p style="margin: 0 0 20px 0; font-size: 14px; color: #6b7280; line-height: 1.5;">
                                    <?php esc_html_e('Synchronize your WordPress content with Typesense. This process sends all selected content types to your Typesense server. Run this initial sync or after bulk edits.', 'swiftsearch-for-typesense'); ?>
                                </p>
                                <div class="ss-actions-row" style="display: flex; gap: 15px;">
                                    <button type="button" id="ss-sync-btn"
                                        class="ss-btn ss-btn-primary"><?php esc_html_e('Index All Content', 'swiftsearch-for-typesense'); ?></button>
                                    <button type="button" id="ss-reset-btn" class="ss-btn ss-btn-danger ss-btn-outline"
                                        style="background: transparent; border: 1px solid #ef4444; color: #ef4444;"><?php esc_html_e('Delete Index', 'swiftsearch-for-typesense'); ?></button>
                                </div>
                                <p id="ss-sync-msg"
                                    style="margin-top: 15px; font-size: 13px; font-weight: 500; min-height: 20px;">
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- WP-CLI Commands Panel -->
                <div class="ss-card" style="margin-top: 20px;">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('WP-CLI Terminal Commands', 'swiftsearch-for-typesense'); ?></h2>
                        <p><?php esc_html_e('Highly recommended for large catalogs (10,000+ items) or servers with strict security blocks to bypass timeouts and loopback limitations.', 'swiftsearch-for-typesense'); ?> <a href="https://docs.loopstates.com/swift-search-typesense/#cli" target="_blank" style="color: #2271b1; text-decoration: underline; font-weight: 500;"><?php esc_html_e('Learn more here.', 'swiftsearch-for-typesense'); ?></a></p>
                    </div>
                    <div class="ss-card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                            <div>
                                <h4 style="margin: 0 0 8px 0; font-size: 13px; font-weight: 600; color: #374151;"><?php esc_html_e('Run Bulk Indexing', 'swiftsearch-for-typesense'); ?></h4>
                                <p style="margin: 0 0 10px 0; font-size: 12px; color: #6b7280; line-height: 1.4;"><?php esc_html_e('Perform a complete synchronization of all selected content types.', 'swiftsearch-for-typesense'); ?></p>
                                <code style="display: block; padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; font-family: monospace; font-size: 12px; color: #111827;">wp swift-search index</code>
                            </div>
                            <div>
                                <h4 style="margin: 0 0 8px 0; font-size: 13px; font-weight: 600; color: #374151;"><?php esc_html_e('Check Connection & Stats', 'swiftsearch-for-typesense'); ?></h4>
                                <p style="margin: 0 0 10px 0; font-size: 12px; color: #6b7280; line-height: 1.4;"><?php esc_html_e('Verify connectivity to the Typesense cluster and view collection counts.', 'swiftsearch-for-typesense'); ?></p>
                                <code style="display: block; padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; font-family: monospace; font-size: 12px; color: #111827;">wp swift-search status</code>
                            </div>
                            <div>
                                <h4 style="margin: 0 0 8px 0; font-size: 13px; font-weight: 600; color: #374151;"><?php esc_html_e('Reset Search Index', 'swiftsearch-for-typesense'); ?></h4>
                                <p style="margin: 0 0 10px 0; font-size: 12px; color: #6b7280; line-height: 1.4;"><?php esc_html_e('Recreate and clean the search collection schemas on the cluster.', 'swiftsearch-for-typesense'); ?></p>
                                <code style="display: block; padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; font-family: monospace; font-size: 12px; color: #111827;">wp swift-search reset</code>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sync Logs Panel -->
                <div class="ss-card" style="margin-top: 20px;">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('Sync Logs', 'swiftsearch-for-typesense'); ?></h2>
                        <p><?php esc_html_e('Recent errors and status messages.', 'swiftsearch-for-typesense'); ?></p>
                    </div>
                    <div class="ss-card-body">
                        <div id="ss-sync-log-container" class="ss-log-viewer" style="min-height: 100px;">
                            <div class="ss-log-placeholder" style="color: #9ca3af; text-align: center; padding: 20px;">
                                <?php esc_html_e('No errors recorded.', 'swiftsearch-for-typesense'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </main>
    </div>
    <div class="ss-footer"
        style="text-align: center; margin-top: 40px; padding: 20px 20px 40px; color: #6b7280; font-size: 13px; border-top: 1px solid #f3f4f6;">
        <div style="display: flex; flex-direction: column; align-items: center; gap: 10px;">
            <p style="margin: 0;">
                A <a href="https://loopstates.com" target="_blank" style="color: inherit; text-decoration: none; font-weight: 600; color: #632489;">Loopstates</a> Product. SwiftSearch for Typesense v<?php echo esc_html(SWIFT_SEARCH_VERSION); ?>. <?php esc_html_e('Optimized for Typesense v0.30.1+.', 'swiftsearch-for-typesense'); ?>
            </p>
            <p style="margin: 0; color: #4b5563;">
                <?php esc_html_e('For custom integrations, enterprise solutions, or dedicated engineering support, contact', 'swiftsearch-for-typesense'); ?> 
                <a href="mailto:hello@loopstates.com" style="color: #632489; text-decoration: none; font-weight: 500;">hello@loopstates.com</a>
            </p>
            <div style="margin-top: 5px;">
                <img src="<?php echo esc_url(SWIFT_SEARCH_URL . 'assets/images/loopstates.png'); ?>" 
                     alt="Loopstates" 
                     style="height: 30px; width: auto;">
            </div>
        </div>
    </div>
</div>