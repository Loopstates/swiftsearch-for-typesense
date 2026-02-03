<div class="swift-search-dashboard" id="swift-search-app">
    <header class="ss-header">
        <div class="ss-logo">
            <img src="<?php echo esc_url(SWIFT_SEARCH_URL . 'assets/images/loopstates.png'); ?>" alt="Loopstates"
                class="ss-brand-logo" style="max-height: 40px; margin-right: 12px; vertical-align: middle;">
            <h1 style="display: inline-block; vertical-align: middle; margin: 0;">SwiftSearch <span
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
                    <li class="ss-nav-item" data-step="analytics">
                        <span class="step-num">5</span>
                        <span class="step-label"><?php esc_html_e('Analytics', 'swift-search-typesense'); ?></span>
                    </li>
                    <li class="ss-nav-item" data-step="pinning">
                        <span class="step-num">6</span>
                        <span class="step-label"><?php esc_html_e('Pinning', 'swift-search-typesense'); ?></span>
                    </li>
                    <li class="ss-nav-item" data-step="sync">
                        <span class="step-num">7</span>
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

                            <div class="ss-form-actions">
                                <button type="submit"
                                    class="ss-btn ss-btn-primary"><?php esc_html_e('Save & Test Connection', 'swift-search-typesense'); ?></button>
                            </div>
                        </form>
                    </div>
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
                        <p><?php esc_html_e('Select which post types to index.', 'swift-search-typesense'); ?></p>
                    </div>
                    <div class="ss-card-body">
                        <!-- Content Checkboxes will be rendered here -->
                        <div class="ss-option-list">
                            <label class="ss-checkbox-card">
                                <input type="checkbox" name="post_types[]" value="post" checked>
                                <div class="info">
                                    <span class="title">Posts</span>
                                    <span class="meta">Standard blog posts</span>
                                </div>
                            </label>
                            <label class="ss-checkbox-card">
                                <input type="checkbox" name="post_types[]" value="page" checked>
                                <div class="info">
                                    <span class="title">Pages</span>
                                    <span class="meta">Static pages</span>
                                </div>
                            </label>
                            <label class="ss-checkbox-card">
                                <input type="checkbox" name="post_types[]" value="product">
                                <div class="info">
                                    <span class="title">Products</span>
                                    <span class="meta">WooCommerce Products</span>
                                </div>
                            </label>
                        </div>
                        <div class="ss-form-actions">
                            <button type="button" class="ss-btn ss-btn-primary next-step"
                                data-target="relevance"><?php esc_html_e('Next: Relevance', 'swift-search-typesense'); ?></button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Step 3: Relevance (Pro) -->
            <section class="ss-step-view" id="view-relevance" style="display:none;">
                <div class="ss-card">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('Relevance Settings', 'swift-search-typesense'); ?> <span
                                class="ss-pro-badge">PRO</span></h2>
                        <p><?php esc_html_e('Fine-tune search results ranking.', 'swift-search-typesense'); ?></p>
                    </div>
                    <div class="ss-card-body">
                        <div class="ss-pro-gate" data-feature="relevance">
                            <div class="ss-form-group">
                                <label>Global Relevance Score</label>
                                <input type="range" id="ss-relevance-range" min="1" max="100" value="50">
                            </div>
                            <div class="ss-form-group">
                                <label>Synonyms</label>
                                <textarea id="ss-synonyms-list" rows="4" class="ss-input"
                                    placeholder="jacket, coat, blazer"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="ss-form-actions">
                        <button type="button" class="ss-btn ss-btn-primary next-step"
                            data-target="search-ui"><?php esc_html_e('Next: Search UI', 'swift-search-typesense'); ?></button>
                    </div>
                </div>
            </section>

            <!-- Step 4: Search UI -->
            <section class="ss-step-view" id="view-search-ui" style="display:none;">
                <!-- Shortcode Builder -->
                <div class="ss-card">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('Shortcode Builder', 'swift-search-typesense'); ?></h2>
                        <p><?php esc_html_e('Customize and generate your search shortcode.', 'swift-search-typesense'); ?></p>
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
                        </div>
                    </div>
                    <div class="ss-form-actions">
                        <button type="button" class="ss-btn ss-btn-primary next-step"
                            data-target="analytics"><?php esc_html_e('Next: Analytics', 'swift-search-typesense'); ?></button>
                    </div>
                </div>
            </section>

             <!-- Step 5: Analytics (Pro) -->
            <section class="ss-step-view" id="view-analytics" style="display:none;">
                <div class="ss-card">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('Search Analytics', 'swift-search-typesense'); ?> <span class="ss-pro-badge">PRO</span></h2>
                        <p><?php esc_html_e('Insights into what your users are searching for.', 'swift-search-typesense'); ?></p>
                    </div>
                    <div class="ss-card-body">
                         <div class="ss-pro-gate" data-feature="analytics">
                            <div class="ss-empty-state">
                                <h3>Analytics Dashboard</h3>
                                <p>Track top searches and "0 result" queries here.</p>
                            </div>
                        </div>
                    </div>
                    <div class="ss-form-actions">
                        <button type="button" class="ss-btn ss-btn-primary next-step"
                            data-target="pinning"><?php esc_html_e('Next: Results Pinning', 'swift-search-typesense'); ?></button>
                    </div>
                </div>
            </section>

             <!-- Step 6: Results Pinning (Pro) -->
            <section class="ss-step-view" id="view-pinning" style="display:none;">
                <div class="ss-card">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('Pinned Results', 'swift-search-typesense'); ?> <span class="ss-pro-badge">PRO</span></h2>
                        <p><?php esc_html_e('Manually fix specific products to the top of search results.', 'swift-search-typesense'); ?></p>
                    </div>
                    <div class="ss-card-body">
                         <div class="ss-pro-gate" data-feature="pinning">
                            <div class="ss-empty-state">
                                <h3>Results Pinning</h3>
                                <p>Drag and drop products to pin them for specific queries.</p>
                            </div>
                        </div>
                    </div>
                    <div class="ss-form-actions">
                        <button type="button" class="ss-btn ss-btn-primary next-step"
                            data-target="sync"><?php esc_html_e('Next: Sync', 'swift-search-typesense'); ?></button>
                    </div>
                </div>
            </section>

            <!-- Step 7: Sync & Usage -->
            <section class="ss-step-view" id="view-sync" style="display:none;">
                <div class="ss-card">
                    <div class="ss-card-header">
                        <h2><?php esc_html_e('Index Management', 'swift-search-typesense'); ?></h2>
                    </div>
                    <div class="ss-card-body center-align">
                        <div class="ss-sync-status">
                            <div class="progress-circle" data-percent="0">0%</div>
                            <p>Ready to index.</p>
                        </div>
                        <div class="ss-actions-row">
                            <button type="button" id="ss-sync-btn"
                                class="ss-btn ss-btn-primary"><?php esc_html_e('Index All Content', 'swift-search-typesense'); ?></button>
                            <button type="button" id="ss-reset-btn"
                                class="ss-btn ss-btn-danger"><?php esc_html_e('Delete Index & Reset', 'swift-search-typesense'); ?></button>
                        </div>
                    </div>
                </div>
            </section>

        </main>
    </div>
</div>