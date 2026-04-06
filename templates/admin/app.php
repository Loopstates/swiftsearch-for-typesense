<div class="swift-search-dashboard" id="swift-search-app">
    <header class="ss-header">
        <div class="ss-logo">
            <img src="<?php echo esc_url(SWIFT_SEARCH_URL . 'assets/images/swift-search-typesense.png'); ?>"
                alt="SwiftSearch" class="ss-brand-logo"
                style="max-height: 40px; margin-right: 12px; vertical-align: middle;">
            <h1 style="display: inline-block; vertical-align: middle; margin: 0;">SwiftSearch Typesense <span
                    class="ss-version">v<?php echo esc_html(SWIFT_SEARCH_VERSION); ?></span></h1>
        </div>
        <div class="ss-actions">
            <a href="https://loopstates.com/docs/swift-search" target="_blank"
                class="ss-link"><?php esc_html_e('Documentation', 'swift-search-typesense'); ?></a>
            <a href="mailto:hello@loopstates.com" target="_blank"
                class="ss-link"><?php esc_html_e('Support', 'swift-search-typesense'); ?></a>
        </div>
    </header>

    <div class="ss-container">
        <!-- Sidebar Navigation -->
        <aside class="ss-sidebar">
            <nav class="ss-nav">
                <ul>
                    <li class="ss-nav-item active" data-step="connect">
                        <span class="step-num">1</span>
                        <span class="step-label"><?php esc_html_e('Connect', 'swift-search-typesense'); ?></span>
                    </li>
                    <li class="ss-nav-item" data-step="content">
                        <span class="step-num">2</span>
                        <span class="step-label"><?php esc_html_e('Content', 'swift-search-typesense'); ?></span>
                    </li>
                    <li class="ss-nav-item" data-step="relevance">
                        <span class="step-num">3</span>
                        <span class="step-label"><?php esc_html_e('Relevance', 'swift-search-typesense'); ?></span>
                    </li>
                    <li class="ss-nav-item" data-step="search-ui">
                        <span class="step-num">4</span>
                        <span class="step-label"><?php esc_html_e('Search UI', 'swift-search-typesense'); ?></span>
                    </li>
                    <li class="ss-nav-item" data-step="styling">
                        <span class="step-num">5</span>
                        <span class="step-label"><?php esc_html_e('Styling & UI', 'swift-search-typesense'); ?></span>
                    </li>
                    <li class="ss-nav-item" data-step="analytics">
                        <span class="step-num">6</span>
                        <span class="step-label"><?php esc_html_e('Analytics', 'swift-search-typesense'); ?></span>
                    </li>
                    <li class="ss-nav-item" data-step="pinning">
                        <span class="step-num">7</span>
                        <span class="step-label"><?php esc_html_e('Pinning', 'swift-search-typesense'); ?></span>
                    </li>
                    <li class="ss-nav-item" data-step="sync">
                        <span class="step-num">8</span>
                        <span class="step-label"><?php esc_html_e('Sync', 'swift-search-typesense'); ?></span>
                    </li>
                </ul>
            </nav>

            <div class="ss-status-card">
                <h3><?php esc_html_e('System Status', 'swift-search-typesense'); ?></h3>
                <div class="status-row">
                    <span class="label">Typesense</span>
                    <span class="value disconnected"
                        id="ss-connection-status"><?php esc_html_e('Disconnected', 'swift-search-typesense'); ?></span>
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
                        <h2><?php esc_html_e('Connect to Typesense', 'swift-search-typesense'); ?></h2>
                        <p><?php esc_html_e('Enter your Typesense server credentials. You can use Typesense Cloud or a self-hosted node.', 'swift-search-typesense'); ?>
                        </p>
                    </div>
                    <div class="ss-card-body">
                        <form id="ss-connect-form">
                            <div class="ss-form-group">
                                <label for="ts-host"><?php esc_html_e('Host', 'swift-search-typesense'); ?></label>
                                <input type="text" id="ts-host" name="host" placeholder="e.g. xxx.a1.typesense.net"
                                    required>
                            </div>
                            <div class="ss-form-group-row">
                                <div class="ss-form-group">
                                    <label for="ts-port"><?php esc_html_e('Port', 'swift-search-typesense'); ?></label>
                                    <input type="number" id="ts-port" name="port" value="443" required>
                                </div>
                                <div class="ss-form-group">
                                    <label
                                        for="ts-protocol"><?php esc_html_e('Protocol', 'swift-search-typesense'); ?></label>
                                    <select id="ts-protocol" name="protocol">
                                        <option value="https">https</option>
                                        <option value="http">http</option>
                                    </select>
                                </div>
                            </div>
                            <div class="ss-form-group">
                                <label
                                    for="ts-api-key"><?php esc_html_e('Admin API Key', 'swift-search-typesense'); ?></label>
                                <input type="password" id="ts-api-key" name="api_key" required>
                                <p class="description">
                                    <?php esc_html_e('Required for indexing actions.', 'swift-search-typesense'); ?>
                                </p>
                            </div>
                            <div class="ss-form-group">
                                <label
                                    for="ts-search-key"><?php esc_html_e('Search-Only API Key', 'swift-search-typesense'); ?></label>
                                <input type="text" id="ts-search-key" name="search_key">
                                <p class="description">
                                    <?php esc_html_e('Public key for the frontend search.', 'swift-search-typesense'); ?>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="ss-form-actions" style="margin-top: 20px;">
                    <button type="button" id="ss-connect-btn"
                        class="ss-btn ss-btn-primary"><?php esc_html_e('Save & Test Connection', 'swift-search-typesense'); ?></button>
                </div>
            </section>

            <!-- Step 2: Content -->
            <section class="ss-step-view" id="view-content" style="display:none;">
                <div class="ss-card">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('Search Behavior', 'swift-search-typesense'); ?></h2>
                        <p><?php esc_html_e('Configure how SwiftSearch integrates with your theme.', 'swift-search-typesense'); ?>
                        </p>
                    </div>
                    <div class="ss-card-body">
                        <div class="ss-form-group-row">
                            <div class="ss-form-group">
                                <label class="ss-checkbox-card" style="width: 100%;">
                                    <input type="checkbox" id="ss-override-default">
                                    <div class="info">
                                        <span
                                            class="title"><?php esc_html_e('Override Default Search', 'swift-search-typesense'); ?></span>
                                        <span
                                            class="meta"><?php esc_html_e('Automatically replace the native WordPress search form with SwiftSearch.', 'swift-search-typesense'); ?></span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ss-card">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('Searchable Content', 'swift-search-typesense'); ?></h2>
                        <p><?php esc_html_e('Configure which content types (Posts, Pages, Products, Custom Types) should be indexed.', 'swift-search-typesense'); ?>
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

                <!-- Advanced Data (Pro) -->
                <div class="ss-card ss-pro-gate" style="margin-top: 20px;">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('Advanced Data', 'swift-search-typesense'); ?> <span
                                class="ss-pro-badge">PRO</span></h2>
                        <p><?php esc_html_e('Map custom meta fields to Typesense schema (e.g. Price, SKU, Brand).', 'swift-search-typesense'); ?>
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
                        id="ss-save-content"><?php esc_html_e('Save Content Settings', 'swift-search-typesense'); ?></button>
                </div>
            </section>

            <!-- Step 3: Relevance (Pro) -->
            <section class="ss-step-view" id="view-relevance" style="display:none;">
                <div class="ss-card">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('Relevance Settings', 'swift-search-typesense'); ?> <span class="ss-pro-badge">PRO</span></h2>
                        <p><?php esc_html_e('Fine-tune search results ranking.', 'swift-search-typesense'); ?></p>
                    </div>
                    <div class="ss-card-body">
                        <div class="ss-pro-gate" data-feature="relevance">
                            <div class="ss-form-group">
                                <label>Global Relevance Score: <span id="ss-relevance-val" style="font-weight:700;">50</span></label>
                                <input type="range" id="ss-relevance-range" min="1" max="100" value="50" oninput="document.getElementById('ss-relevance-val').innerText = this.value">
                                <p class="description">
                                    <?php esc_html_e('Adjust the base ranking weight for searches. Higher values favor exact matches.', 'swift-search-typesense'); ?>
                                </p>
                            </div>
                            <div class="ss-form-group">
                                <label>Synonyms</label>
                                <textarea id="ss-synonyms-list" rows="6" class="ss-input" placeholder="jacket, coat, blazer&#10;bag, backpack, tote&#10;trousers, pants"></textarea>
                                <p class="description">
                                    <?php esc_html_e('Enter each group of synonyms on a new line. Comma-separated.', 'swift-search-typesense'); ?>
                                </p>
                            </div>
                            <div class="ss-form-group" style="margin-top: 15px;">
                                <label><?php esc_html_e('Apply to Collections:', 'swift-search-typesense'); ?></label>
                                <div id="ss-synonym-collections-container" style="margin-top: 10px;">
                                    <p class="ss-hint" style="font-style: italic; opacity: 0.7;"><?php esc_html_e('Loading active collections...', 'swift-search-typesense'); ?></p>
                                </div>
                                <p class="ss-hint" style="margin-top: 8px;">
                                    <?php esc_html_e('Select which indices search results should expand with these synonyms.', 'swift-search-typesense'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="ss-form-actions" style="margin-top: 20px;">
                    <button type="button" class="ss-btn ss-btn-primary" id="ss-save-relevance"><?php esc_html_e('Save Relevance Settings', 'swift-search-typesense'); ?></button>
                </div>
            </section>

            <!-- Step 4: Search UI -->
            <section class="ss-step-view" id="view-search-ui" style="display:none;">
                <!-- Shortcode Builder -->
                <div class="ss-card">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('Shortcode Builder', 'swift-search-typesense'); ?></h2>
                        <p><?php esc_html_e('Customize and generate your search shortcode.', 'swift-search-typesense'); ?>
                        </p>
                    </div>
                    <div class="ss-card-body">
                        <div class="ss-form-group-row">
                            <div class="ss-form-group">
                                <label>Placeholder Text</label>
                                <input type="text" id="sc-placeholder" class="ss-input" value="Search...">
                            </div>
                            <div class="ss-form-group">
                                <label>Results Limit</label>
                                <input type="number" id="sc-limit" class="ss-input" value="10" min="1" max="50">
                            </div>
                        </div>
                        <div class="ss-form-group-row">
                            <div class="ss-form-group">
                                <label class="ss-checkbox-card">
                                    <input type="checkbox" id="sc-show-thumbnail" checked>
                                    <div class="info"><span class="title">Show Thumbnail</span></div>
                                </label>
                            </div>
                            <div class="ss-form-group">
                                <label class="ss-checkbox-card">
                                    <input type="checkbox" id="sc-show-price" checked>
                                    <div class="info"><span class="title">Show Price</span></div>
                                </label>
                            </div>
                            <div class="ss-form-group">
                                <label class="ss-checkbox-card">
                                    <input type="checkbox" id="sc-show-excerpt">
                                    <div class="info"><span class="title">Show Excerpt</span></div>
                                </label>
                            </div>
                        </div>
                        <!-- Experience Overrides -->
                        <div class="ss-form-group-row"
                            style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px;">
                            <div class="ss-form-group">
                                <label class="ss-checkbox-card">
                                    <input type="checkbox" id="sc-instant-search" checked>
                                    <div class="info"><span class="title">Instant Search</span></div>
                                </label>
                            </div>
                        </div>
                        <div class="ss-form-group" style="margin-top: 10px;">
                            <label style="display:block; margin-bottom: 8px; font-weight: 500;">Search Scope
                                Override</label>
                            <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                                <label><input type="checkbox" id="sc-scope-posts" checked disabled> Posts</label>
                                <label><input type="checkbox" id="sc-scope-terms" checked> Taxonomies</label>
                                <label><input type="checkbox" id="sc-scope-users"> Users</label>
                            </div>

                            <label style="display:block; margin-bottom: 8px; font-weight: 500;">Limit Post Types
                                (Optional)</label>
                            <div class="ss-checkbox-list"
                                style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; max-height: 150px; overflow-y: auto; padding: 10px; border: 1px solid #eee; border-radius: 4px;">
                                <?php foreach ($data['available_post_types'] as $pt): ?>
                                    <label class="ss-checkbox-inline">
                                        <input type="checkbox" class="sc-post-type-selector"
                                            value="<?php echo esc_attr($pt['name']); ?>">
                                        <?php echo esc_html($pt['label']); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <p class="ss-hint">
                                <?php esc_html_e('Leave all unchecked to search all indexed post types.', 'swift-search-typesense'); ?>
                            </p>
                        </div>
                        <div class="ss-code-preview">
                            <code id="sc-preview">[swift_search placeholder="Search..." limit="10"]</code>
                            <button type="button" class="ss-btn ss-btn-sm ss-btn-secondary"
                                id="ss-copy-sc">Copy</button>
                        </div>
                    </div>
                </div>

                <!-- Features (Toggles) -->
                <div class="ss-card">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('Experience Options', 'swift-search-typesense'); ?></h2>
                    </div>
                    <div class="ss-card-body">
                        <div class="ss-form-group-row">
                            <div class="ss-form-group">
                                <label class="ss-checkbox-card">
                                    <input type="checkbox" id="ss-typo-tolerance" checked>
                                    <div class="info">
                                        <span class="title">Typo Tolerance</span>
                                        <span class="meta">Show "Did you mean?" suggestions.</span>
                                    </div>
                                </label>
                            </div>
                            <div class="ss-form-group">
                                <label class="ss-checkbox-card">
                                    <input type="checkbox" id="ss-sort-enabled">
                                    <div class="info">
                                        <span class="title">Sort Dropdown</span>
                                        <span class="meta">Allow users to sort by Date/Relevance.</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <div class="ss-form-group-row">
                            <div class="ss-form-group">
                                <label class="ss-checkbox-card">
                                    <input type="checkbox" id="ss-mobile-btn">
                                    <div class="info">
                                        <span class="title">Floating Mobile Button</span>
                                        <span class="meta">Sticky search icon on mobile devices.</span>
                                    </div>
                                </label>
                            </div>
                            <div class="ss-form-group">
                                <label class="ss-checkbox-card">
                                    <input type="checkbox" id="ss-instant-search" checked>
                                    <div class="info">
                                        <span class="title">Instant Search</span>
                                        <span class="meta">Search as you type (Autocomplete).</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                </div>
            </div>

                <!-- Faceted Navigation (Pro) -->
                <div class="ss-card ss-pro-gate">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('Faceted Navigation (Sidebar)', 'swift-search-typesense'); ?> <span
                                class="ss-pro-badge">PRO</span></h2>
                        <p><?php esc_html_e('Configure filters for your search results sidebar.', 'swift-search-typesense'); ?>
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
                        id="ss-save-search-ui"><?php esc_html_e('Save Search UI Settings', 'swift-search-typesense'); ?></button>
                </div>
            </section>
            
            <!-- Step 5: Styling & UI -->
            <section class="ss-step-view" id="view-styling" style="display:none;">
                <div class="ss-card">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('Styling & Customization', 'swift-search-typesense'); ?></h2>
                        <p><?php esc_html_e('Customize the appearance of your search results. Changes apply to the frontend search interface.', 'swift-search-typesense'); ?></p>
                    </div>
                    <div class="ss-card-body">
                         <div class="ss-form-group-row" style="gap: 20px;">
                            <div class="ss-form-group" style="flex:1;">
                                <label for="ss-primary-color"><?php esc_html_e('Primary Accent Color', 'swift-search-typesense'); ?></label>
                                <input type="color" id="ss-primary-color" class="ss-input-color" value="#ff0055">
                                <p class="description"><?php esc_html_e('Used for buttons, icons, and highlights.', 'swift-search-typesense'); ?></p>
                            </div>
                            <div class="ss-form-group" style="flex:1;">
                                <label for="ss-text-color"><?php esc_html_e('Main Text Color', 'swift-search-typesense'); ?></label>
                                <input type="color" id="ss-text-color" class="ss-input-color" value="#1f2937">
                                <p class="description"><?php esc_html_e('Color for titles and body text.', 'swift-search-typesense'); ?></p>
                            </div>
                            <div class="ss-form-group" style="flex:1;">
                                <label for="ss-card-bg"><?php esc_html_e('Card Background', 'swift-search-typesense'); ?></label>
                                <input type="color" id="ss-card-bg" class="ss-input-color" value="#ffffff">
                                <p class="description"><?php esc_html_e('Background color for result cards.', 'swift-search-typesense'); ?></p>
                            </div>
                        </div>

                        <div class="ss-form-group" style="margin-top: 20px;">
                            <label for="ss-border-radius"><?php esc_html_e('Border Radius (px)', 'swift-search-typesense'); ?></label>
                            <input type="number" id="ss-border-radius" class="ss-input" value="16" min="0" max="100">
                            <p class="description"><?php esc_html_e('Global roundness for cards, inputs, and buttons.', 'swift-search-typesense'); ?></p>
                        </div>

                        <div class="ss-form-group" style="margin-top: 30px;">
                            <label for="ss-custom-css"><?php esc_html_e('Custom CSS', 'swift-search-typesense'); ?></label>
                            <p class="description" style="margin-bottom: 10px;"><?php esc_html_e('Add raw CSS to override any part of the search UI. Scope your rules to .ss-wrapper for best results.', 'swift-search-typesense'); ?></p>
                            <textarea id="ss-custom-css" rows="10" class="ss-input" style="font-family: monospace; font-size: 13px;" placeholder=".ss-card { border: 1px solid #eee; }"></textarea>
                        </div>
                    </div>
                </div>

                <div class="ss-form-actions" style="margin-top: 20px;">
                    <button type="button" class="ss-btn ss-btn-primary" id="ss-save-styling"><?php esc_html_e('Save Styling Settings', 'swift-search-typesense'); ?></button>
                </div>
            </section>

            <!-- Step 6: Analytics (Pro) -->
            <section class="ss-step-view" id="view-analytics" style="display:none;">
                <div class="ss-card ss-pro-gate">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('Search Analytics', 'swift-search-typesense'); ?> <span
                                class="ss-pro-badge">PRO</span></h2>
                        <p><?php esc_html_e('Insights into what your users are searching for.', 'swift-search-typesense'); ?>
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

            <!-- Step 6: Results Pinning (Pro) -->
            <section class="ss-step-view" id="view-pinning" style="display:none;">
                <div class="ss-card">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('Pinned Results', 'swift-search-typesense'); ?> <span
                                class="ss-pro-badge">PRO</span></h2>
                        <p><?php esc_html_e('Manually fix specific products or items to the top of search results.', 'swift-search-typesense'); ?>
                        </p>
                    </div>
                    <div class="ss-card-body">
                        <div class="ss-pro-gate" data-feature="pinning">
                            <div class="ss-pinning-ui">
                                <div class="ss-form-group" style="position:relative;">
                                    <label><?php esc_html_e('Select Pinned Item', 'swift-search-typesense'); ?></label>
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
                                    <h3><?php esc_html_e('No pinned items', 'swift-search-typesense'); ?></h3>
                                    <p><?php esc_html_e('Search above to pin products to the top of results.', 'swift-search-typesense'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                </div>
            </div>
            <div class="ss-form-actions" style="margin-top: 20px;">
                <button type="button" class="ss-btn ss-btn-primary"
                    id="ss-save-pinning"><?php esc_html_e('Save Pinned Items', 'swift-search-typesense'); ?></button>
            </div>
            </section>

            <!-- Step 7: Sync & Usage -->
            <section class="ss-step-view" id="view-sync" style="display:none;">
                <div class="ss-card">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('Index Management', 'swift-search-typesense'); ?></h2>
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
                                    <?php esc_html_e('Synchronize your WordPress content with Typesense. This process sends all selected content types to your Typesense server. Run this initial sync or after bulk edits.', 'swift-search-typesense'); ?>
                                </p>
                                <div class="ss-actions-row" style="display: flex; gap: 15px;">
                                    <button type="button" id="ss-sync-btn"
                                        class="ss-btn ss-btn-primary"><?php esc_html_e('Index All Content', 'swift-search-typesense'); ?></button>
                                    <button type="button" id="ss-reset-btn" class="ss-btn ss-btn-danger ss-btn-outline"
                                        style="background: transparent; border: 1px solid #ef4444; color: #ef4444;"><?php esc_html_e('Delete Index', 'swift-search-typesense'); ?></button>
                                </div>
                                <p id="ss-sync-msg"
                                    style="margin-top: 15px; font-size: 13px; font-weight: 500; min-height: 20px;">
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sync Logs Panel -->
                <div class="ss-card" style="margin-top: 20px;">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('Sync Logs', 'swift-search-typesense'); ?></h2>
                        <p><?php esc_html_e('Recent errors and status messages.', 'swift-search-typesense'); ?></p>
                    </div>
                    <div class="ss-card-body">
                        <div id="ss-sync-log-container" class="ss-log-viewer" style="min-height: 100px;">
                            <div class="ss-log-placeholder" style="color: #9ca3af; text-align: center; padding: 20px;">
                                <?php esc_html_e('No errors recorded.', 'swift-search-typesense'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </main>
    </div>
    <div class="ss-footer"
        style="text-align: center; margin-top: 30px; padding: 20px; color: #6b7280; font-size: 13px;">
        <p>A <a href="https://loopstates.com" target="_blank"
                style="color: inherit; text-decoration: none; font-weight: 500; color: #632489;">Loopstates</a> Product. SwiftSearch
            Typesense
            v<?php echo esc_html(SWIFT_SEARCH_VERSION); ?>. <?php esc_html_e('Optimized for Typesense v0.30.1+.', 'swift-search-typesense'); ?></p>
    </div>
</div>