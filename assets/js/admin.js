(function ($) {
    'use strict';

    const SwiftSearchAdmin = {
        init: function () {
            // Setup Ajax Nonce
            $.ajaxSetup({
                headers: {
                    'X-WP-Nonce': swiftSearchConfig.nonce
                }
            });

            this.cacheDOM();
            this.bindEvents();
            this.checkStatus();
            this.checkPlan();
            this.restoreState();
            this.renderCustomFields();
            this.renderFacetsConfig();
        },

        cacheDOM: function () {
            this.$navItems = $('.ss-nav-item');
            this.$views = $('.ss-step-view');
            this.$connectForm = $('#ss-connect-form');
            this.$statusEl = $('#ss-connection-status');
            this.$docCountEl = $('#ss-doc-count');

            // Sync
            this.$syncBtn = $('#ss-sync-btn');
            this.$resetBtn = $('#ss-reset-btn');
            this.$progressCircle = $('.progress-circle');
            this.$syncStatusText = $('.ss-sync-status p');

            // Save Btns
            this.$saveContentBtn = $('#ss-save-content');
            this.$saveRelevanceBtn = $('#ss-save-relevance');
            this.$saveExperienceBtn = $('#ss-save-experience');

            // Pro Gates
            this.$proGates = $('.ss-pro-gate');

            // Override
            this.$overrideToggle = $('#ss-override-default');

            // Shortcode Builder
            this.$scPlaceholder = $('#sc-placeholder');
            this.$scLimit = $('#sc-limit');
            this.$scShowThumbnail = $('#sc-show-thumbnail');
            this.$scShowPrice = $('#sc-show-price');
            this.$scShowExcerpt = $('#sc-show-excerpt');
            this.$scInstantSearch = $('#sc-instant-search');
            this.$scScopePosts = $('#sc-scope-posts');
            this.$scScopeTerms = $('#sc-scope-terms');
            this.$scScopeUsers = $('#sc-scope-users');
            this.$scPreview = $('#sc-preview');
            this.$scCopyBtn = $('#ss-copy-sc');

            // Experience Options
            this.$ssTypoTolerance = $('#ss-typo-tolerance');
            this.$ssSortEnabled = $('#ss-sort-enabled');
            this.$ssMobileBtn = $('#ss-mobile-btn');

            // Relevance
            this.$relevanceRange = $('#ss-relevance-range');
            this.$synonymsInput = $('#ss-synonyms-list');

            // Post Types
            // Post Types
            this.$postTypeInputs = $('input[name="post_types[]"]');

            // Pinning
            this.$pinningSearch = $('#ss-pinning-search');
            this.$pinningResults = $('#ss-pinning-results');
            this.$pinnedListContainer = $('#ss-pinned-list-container');
            this.$pinningEmpty = $('#ss-pinning-empty');
            this.pinnedItems = [];
            this.searchTimeout = null;
        },

        bindEvents: function () {
            // Tab Navigation
            this.$navItems.on('click', this.handleNavClick.bind(this));

            // Forms
            this.$connectForm.on('submit', this.handleConnect.bind(this));

            // Next buttons
            $(document).on('click', '.next-step', this.handleNextStep.bind(this));

            // Sync Actions
            this.$syncBtn.on('click', this.startSync.bind(this));
            this.$resetBtn.on('click', this.resetIndex.bind(this));

            // Override Toggle
            this.$overrideToggle.on('change', this.handleOverrideChange.bind(this));

            // Shortcode Actions
            this.$scPlaceholder.on('input', this.updateShortcodePreview.bind(this));
            this.$scLimit.on('input', this.updateShortcodePreview.bind(this));
            this.$scShowThumbnail.on('change', this.updateShortcodePreview.bind(this));
            this.$scShowPrice.on('change', this.updateShortcodePreview.bind(this));
            this.$scShowExcerpt.on('change', this.updateShortcodePreview.bind(this));
            this.$scInstantSearch.on('change', this.updateShortcodePreview.bind(this));
            this.$scScopeTerms.on('change', this.updateShortcodePreview.bind(this));
            this.$scScopeUsers.on('change', this.updateShortcodePreview.bind(this));
            this.$scCopyBtn.on('click', this.copyShortcode.bind(this));

            // Experience Options
            this.$ssTypoTolerance.on('change', this.handleExperienceChange.bind(this));
            this.$ssSortEnabled.on('change', this.handleExperienceChange.bind(this));
            this.$ssMobileBtn.on('change', this.handleExperienceChange.bind(this));

            // Relevance
            this.$relevanceRange.on('change', this.handleRelevanceChange.bind(this));
            this.$synonymsInput.on('change', this.handleRelevanceChange.bind(this));

            // Save Actions
            this.$saveContentBtn.on('click', this.saveContentSettings.bind(this));
            this.$saveRelevanceBtn.on('click', this.handleRelevanceChange.bind(this));
            this.$saveExperienceBtn.on('click', this.handleExperienceChange.bind(this));
            $('#ss-save-facets').on('click', this.saveFacetsConfig.bind(this));

            // Pinning
            this.$pinningSearch.on('input', this.handlePinningSearch.bind(this));
            this.$pinningResults.on('click', '.ss-autocomplete-item', this.addPinnedItem.bind(this));
            this.$pinnedListContainer.on('click', '.remove-pin', this.removePinnedItem.bind(this));
            // Close autocomplete on click outside
            $(document).on('click', (e) => {
                if (!$(e.target).closest('.ss-pinning-ui').length) {
                    this.$pinningResults.hide();
                }
            });
        },

        init: function () {
            this.cacheDOM();
            this.bindEvents();
            this.checkStatus();
            this.checkPlan();

            // Set Initial State
            // Populate Credentials
            if (swiftSearchConfig.credentials && (swiftSearchConfig.credentials.host || swiftSearchConfig.credentials.api_key)) {
                if (swiftSearchConfig.credentials.host) $('#ts-host').val(swiftSearchConfig.credentials.host);
                if (swiftSearchConfig.credentials.port) $('#ts-port').val(swiftSearchConfig.credentials.port);
                if (swiftSearchConfig.credentials.protocol) $('#ts-protocol').val(swiftSearchConfig.credentials.protocol);
                if (swiftSearchConfig.credentials.api_key) $('#ts-api-key').val(swiftSearchConfig.credentials.api_key);
                if (swiftSearchConfig.credentials.search_key) $('#ts-search-key').val(swiftSearchConfig.credentials.search_key);
            }

            // Set Initial UI State
            this.renderConnectionState(this.$statusEl.hasClass('connected'));

            // Render Content Settings
            this.renderContentSettings();

            // Render Custom Fields (Pro)
            this.renderCustomFields();

            // Set Experience State

            if (swiftSearchConfig.status.overrideDefault) {
                this.$overrideToggle.prop('checked', true);
            }

            // Set Experience State
            if (swiftSearchConfig.experience) {
                this.$ssTypoTolerance.prop('checked', !!swiftSearchConfig.experience.typo_tolerance);
                this.$ssSortEnabled.prop('checked', !!swiftSearchConfig.experience.sort_enabled);
                this.$ssMobileBtn.prop('checked', !!swiftSearchConfig.experience.mobile_btn);

                // New Fields Phase 7
                if (typeof swiftSearchConfig.experience.instant_search !== 'undefined') {
                    $('#ss-instant-search').prop('checked', !!swiftSearchConfig.experience.instant_search);
                } else {
                    $('#ss-instant-search').prop('checked', true); // Default true
                }

                if (swiftSearchConfig.experience.search_scope) {
                    $('#ss-scope-terms').prop('checked', !!swiftSearchConfig.experience.search_scope.terms);
                    $('#ss-scope-users').prop('checked', !!swiftSearchConfig.experience.search_scope.users);
                }
            }

            // Set Relevance State
            if (swiftSearchConfig.relevance) {
                // Post Title Weight
                if (swiftSearchConfig.relevance.weights && swiftSearchConfig.relevance.weights.post_title) {
                    const val = swiftSearchConfig.relevance.weights.post_title * 10;
                    this.$relevanceRange.val(val); // Scale 1-10 to 1-100 UI
                    $('#ss-relevance-val').text(val);
                }

                // Synonyms
                if (swiftSearchConfig.relevance.synonyms) {
                    // Convert array to CSV string for display
                    // Format: [{root: 'foo', synonyms: ['bar', 'baz']}] -> "foo, bar, baz\n"
                    let text = '';
                    swiftSearchConfig.relevance.synonyms.forEach(group => {
                        text += group.root + ', ' + group.synonyms.join(', ') + '\n';
                    });
                    this.$synonymsInput.val(text.trim());
                }
            }

            // Trigger initial shortcode preview
            // Trigger initial shortcode preview
            this.updateShortcodePreview();
        },

        restoreState: function () {
            // Already handled in init, but kept for structure if needed
        },

        renderContentSettings: function () {
            const container = $('#ss-content-settings-container');
            if (!container.length) return;

            const postTypes = swiftSearchConfig.available_post_types || [];
            const savedPostTypes = swiftSearchConfig.indexed_post_types || [];

            const taxonomies = swiftSearchConfig.available_taxonomies || [];
            const savedTaxonomies = swiftSearchConfig.indexed_taxonomies || [];

            const savedUsers = swiftSearchConfig.indexed_users || false;

            console.log('Rendering Content Settings.', { postTypes, taxonomies });
            let html = '';

            // Section: Post Types
            html += '<h4 style="margin: 0 0 10px 0; font-size: 14px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Post Types</h4>';
            html += '<div class="ss-grid-2" style="margin-bottom: 20px;">';
            if (postTypes.length > 0) {
                postTypes.forEach(type => {
                    const isChecked = savedPostTypes.includes(type.name) ? 'checked' : '';
                    html += `<label class="ss-checkbox-card">
                        <input type="checkbox" name="post_types[]" value="${type.name}" ${isChecked}>
                        <div class="info">
                            <span class="title">${type.label || type.name}</span>
                            <span class="meta">${type.description || type.name}</span>
                        </div>
                    </label>`;
                });
            } else {
                html += '<p>No public post types found.</p>';
            }
            html += '</div>';

            // Section: Taxonomies
            html += '<h4 style="margin: 0 0 10px 0; font-size: 14px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Taxonomies</h4>';
            html += '<div class="ss-grid-2" style="margin-bottom: 20px;">';
            if (taxonomies.length > 0) {
                taxonomies.forEach(tax => {
                    const isChecked = savedTaxonomies.includes(tax.name) ? 'checked' : '';
                    html += `<label class="ss-checkbox-card">
                        <input type="checkbox" name="taxonomies[]" value="${tax.name}" ${isChecked}>
                        <div class="info">
                            <span class="title">${tax.label || tax.name}</span>
                            <span class="meta">${tax.description || tax.name}</span>
                        </div>
                    </label>`;
                });
            } else {
                html += '<p>No public taxonomies found.</p>';
            }
            html += '</div>';

            // Section: Users
            html += '<h4 style="margin: 0 0 10px 0; font-size: 14px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Users</h4>';
            html += '<div class="ss-grid-2" style="margin-bottom: 20px;">';
            const userChecked = savedUsers ? 'checked' : '';
            html += `<label class="ss-checkbox-card">
                <input type="checkbox" id="ss-index-users" name="index_users" ${userChecked}>
                <div class="info">
                    <span class="title">Authors & Users</span>
                    <span class="meta">Index public authors for "By Author" searches.</span>
                </div>
            </label>`;
            html += '</div>';

            container.html(html);
        },

        // Helper for Authenticated Requests
        request: function (method, endpoint, data = {}) {
            return $.ajax({
                url: swiftSearchConfig.apiUrl + endpoint,
                method: method,
                data: data,
                headers: {
                    'X-WP-Nonce': swiftSearchConfig.nonce
                }
            });
        },

        saveContentSettings: function (e) {
            e.preventDefault();
            const $btn = this.$saveContentBtn;
            const originalText = $btn.text();
            $btn.prop('disabled', true).text('Saving...');

            const postTypes = [];
            $('input[name="post_types[]"]:checked').each(function () {
                postTypes.push($(this).val());
            });

            const taxonomies = [];
            $('input[name="taxonomies[]"]:checked').each(function () {
                taxonomies.push($(this).val());
            });

            const indexUsers = $('#ss-index-users').is(':checked');

            // Collect Custom Fields (Pro)
            const customFields = {};
            $('#ss-custom-fields-container tbody').each(function () {
                const pt = $(this).data('pt');
                const fields = [];
                $(this).find('tr').each(function () {
                    const row = $(this);
                    const key = row.find('input[name*="[key]"]').val();
                    const name = row.find('input[name*="[name]"]').val();
                    const type = row.find('select[name*="[type]"]').val();
                    const facet = row.find('input[name*="[facet]"]').is(':checked');

                    if (key && name) {
                        fields.push({ key: key.trim(), name: name.trim(), type: type, facet: facet });
                    }
                });
                if (fields.length > 0) {
                    customFields[pt] = fields;
                }
            });

            const payload = {
                post_types: postTypes,
                taxonomies: taxonomies,
                index_users: indexUsers,
                custom_fields: customFields
            };

            this.request('POST', '/settings', payload).done(function (response) {
                if (response.success) {
                    alert('Settings Saved!');
                } else {
                    alert('Failed to save settings.');
                }
            }).always(function () {
                $btn.prop('disabled', false).text(originalText);
            });
        },

        handleRelevanceChange: function () {
            const weightVal = this.$relevanceRange.val();
            const weight = Math.max(1, Math.round(weightVal / 10)); // Scale 100 -> 10

            const lines = this.$synonymsInput.val().split('\n');
            const synonyms = [];

            lines.forEach(line => {
                const parts = line.split(',').map(s => s.trim()).filter(s => s.length > 0);
                if (parts.length > 1) {
                    const root = parts[0];
                    const others = parts.slice(1);
                    synonyms.push({
                        root: root,
                        synonyms: others
                    });
                }
            });

            const payload = {
                relevance_settings: {
                    weights: {
                        post_title: weight,
                        post_content: 2,
                        sku: 4,
                        category: 2,
                        tag: 2
                    },
                    synonyms: synonyms
                }
            };

            this.request('POST', '/settings', payload);
        },

        handleOverrideChange: function (e) {
            const isChecked = $(e.currentTarget).is(':checked');

            this.request('POST', '/settings', {
                override_default: isChecked
            }).done(function (response) {
                if (!response.success) {
                    alert('Failed to save setting.');
                }
            });
        },

        handleExperienceChange: function () {
            const payload = {
                experience_settings: {
                    typo_tolerance: this.$ssTypoTolerance.is(':checked'),
                    sort_enabled: this.$ssSortEnabled.is(':checked'),
                    mobile_btn: this.$ssMobileBtn.is(':checked'),
                    instant_search: $('#ss-instant-search').is(':checked'),
                    search_scope: {
                        posts: true, // Always true
                        terms: $('#ss-scope-terms').is(':checked'),
                        users: $('#ss-scope-users').is(':checked')
                    }
                }
            };

            this.request('POST', '/settings', payload).done(function (response) {
                if (!response.success) {
                    alert('Failed to save settings.');
                }
            });
        },

        // ... Shortcode functions omitted (unchanged) ...
        updateShortcodePreview: function () {
            const placeholder = this.$scPlaceholder.val() || 'Search...';
            const limit = this.$scLimit.val() || 10;
            const thumb = this.$scShowThumbnail.is(':checked') ? 'true' : 'false';
            const price = this.$scShowPrice.is(':checked') ? 'true' : 'false';
            const excerpt = this.$scShowExcerpt.is(':checked') ? 'true' : 'false';

            // Experience Overrides
            const instant = this.$scInstantSearch.is(':checked') ? 'true' : 'false';

            const scopes = [];
            if (this.$scScopePosts.is(':checked')) scopes.push('posts');
            if (this.$scScopeTerms.is(':checked')) scopes.push('terms');
            if (this.$scScopeUsers.is(':checked')) scopes.push('users');
            const scopeStr = scopes.join(',');

            let shortcode = `[swift_search placeholder="${placeholder}" limit="${limit}"`;

            if (thumb === 'false') shortcode += ` show_thumbnail="false"`;
            if (price === 'false') shortcode += ` show_price="false"`;
            if (excerpt === 'true') shortcode += ` show_excerpt="true"`;

            // Always output experience overrides for clarity in builder
            shortcode += ` instant_search="${instant}"`;
            shortcode += ` scope="${scopeStr}"`;

            shortcode += `]`;

            this.$scPreview.text(shortcode);
        },

        copyShortcode: function () {
            const code = this.$scPreview.text();
            const $btn = this.$scCopyBtn;

            navigator.clipboard.writeText(code).then(function () {
                const originalText = $btn.text();
                $btn.text('Copied!');
                setTimeout(() => $btn.text(originalText), 2000);
            });
        },
        // ... (End Shortcode) ...

        handleNavClick: function (e) {
            const $item = $(e.currentTarget);
            const step = $item.data('step');
            this.switchView(step);
        },

        handleNextStep: function (e) {
            const target = $(e.currentTarget).data('target');
            if (target) {
                this.switchView(target);
            }
        },

        switchView: function (stepName) {
            this.$navItems.removeClass('active');
            this.$navItems.filter('[data-step="' + stepName + '"]').addClass('active');

            this.$views.hide();
            $('#view-' + stepName).fadeIn(200);

            if (stepName === 'analytics') {
                this.loadAnalytics();
            } else if (stepName === 'pinning') {
                this.loadPinnedItems();
            }
        },

        loadAnalytics: function () {
            const $topParams = $('#ss-analytics-top tbody');
            const $zeroParams = $('#ss-analytics-zero tbody');

            $topParams.html('<tr><td colspan="3">Loading...</td></tr>');
            $zeroParams.html('<tr><td colspan="2">Loading...</td></tr>');

            this.request('GET', '/analytics').done(function (response) {
                if (response.success) {
                    const top = response.data.top_queries;
                    const zero = response.data.no_results;

                    if (top.length > 0) {
                        let html = '';
                        top.forEach(row => {
                            html += `<tr>
                                <td>${row.query}</td>
                                <td>${row.count}</td>
                                <td>${Math.round(row.avg_hits)}</td>
                            </tr>`;
                        });
                        $topParams.html(html);
                    } else {
                        $topParams.html('<tr><td colspan="3">No data yet.</td></tr>');
                    }

                    if (zero.length > 0) {
                        let html = '';
                        zero.forEach(row => {
                            html += `<tr>
                                <td>${row.query}</td>
                                <td>${row.count}</td>
                            </tr>`;
                        });
                        $zeroParams.html(html);
                    } else {
                        $zeroParams.html('<tr><td colspan="2">No zero-result searches.</td></tr>');
                    }
                }
            });
        },

        loadPinnedItems: function () {
            const self = this;
            this.request('GET', '/pinning/items').done(function (response) {
                if (response.success) {
                    self.pinnedItems = response.data || [];
                    self.renderPinnedList();
                }
            });
        },

        handlePinningSearch: function () {
            const term = this.$pinningSearch.val().trim();
            const self = this;

            clearTimeout(this.searchTimeout);

            if (term.length < 2) {
                this.$pinningResults.hide();
                return;
            }

            this.searchTimeout = setTimeout(function () {
                self.request('POST', '/pinning/search', { term: term }).done(function (response) {
                    if (response.success) {
                        self.renderPinningResults(response.data);
                    }
                });
            }, 300);
        },

        renderPinningResults: function (results) {
            if (!results || results.length === 0) {
                this.$pinningResults.html('<div class="no-results">No products found</div>').show();
                return;
            }

            let html = '<ul>';
            results.forEach(item => {
                html += `<li class="ss-autocomplete-item" data-id="${item.id}" data-title="${item.title}" data-type="${item.type}">
                    <span class="title">${item.title}</span>
                    <span class="type pill">${item.type}</span>
                </li>`;
            });
            html += '</ul>';
            this.$pinningResults.html(html).show();
        },

        addPinnedItem: function (e) {
            const $el = $(e.currentTarget);
            const item = {
                id: $el.data('id'),
                title: $el.data('title'),
                type: $el.data('type')
            };

            if (this.pinnedItems.find(i => i.id == item.id)) {
                this.$pinningSearch.val('');
                this.$pinningResults.hide();
                return;
            }

            this.pinnedItems.push(item);
            this.savePinnedItems();
            this.$pinningSearch.val('');
            this.$pinningResults.hide();
            this.renderPinnedList();
        },

        removePinnedItem: function (e) {
            const id = $(e.currentTarget).data('id');
            this.pinnedItems = this.pinnedItems.filter(i => i.id != id);
            this.savePinnedItems();
            this.renderPinnedList();
        },

        renderPinnedList: function () {
            if (this.pinnedItems.length === 0) {
                this.$pinnedListContainer.hide();
                this.$pinningEmpty.show();
                return;
            }

            this.$pinningEmpty.hide();
            let html = '<div class="ss-pinned-grid">';
            this.pinnedItems.forEach((item, index) => {
                html += `<div class="ss-pinned-card">
                    <span class="position">#${index + 1}</span>
                    <div class="details">
                        <span class="title">${item.title}</span>
                        <span class="type">${item.type}</span>
                    </div>
                    <button type="button" class="remove-pin" data-id="${item.id}">&times;</button>
                </div>`;
            });
            html += '</div>';
            this.$pinnedListContainer.html(html).show();
        },

        savePinnedItems: function () {
            const payload = { items: this.pinnedItems };
            this.request('POST', '/pinning/items', payload);
        },


        renderConnectionState: function (isConnected) {
            const $btn = this.$connectForm.find('button[type="submit"]');
            const $inputs = this.$connectForm.find('input, select');

            if (isConnected) {
                $inputs.prop('disabled', true);
                $btn.removeClass('ss-btn-primary').addClass('ss-btn-danger')
                    .text('Disconnect')
                    .attr('data-action', 'disconnect') // Tag it
                    .prop('disabled', false); // Ensure button is clickable

                // Allow user to copy keys if needed, but not edit
                $inputs.css('cursor', 'default');
            } else {
                $inputs.prop('disabled', false);
                $btn.removeClass('ss-btn-danger').addClass('ss-btn-primary')
                    .text(swiftSearchConfig.texts.save_connect || 'Save & Test Connection')
                    .removeAttr('data-action') // Untag
                    .prop('disabled', false);

                $inputs.css('cursor', 'text');
            }
        },

        handleConnect: function (e) {
            e.preventDefault();
            const $btn = this.$connectForm.find('button[type="submit"]');

            // Check if Disconnect Action
            if ($btn.attr('data-action') === 'disconnect') {
                this.handleDisconnect();
                return;
            }

            const formData = this.$connectForm.serialize();
            const self = this;
            const oldText = $btn.text();

            this.clearConnectionError();
            $btn.prop('disabled', true).text(swiftSearchConfig.texts.connecting);

            // Manual Ajax for Connect to handle Fail cleanly with Nonce
            $.ajax({
                url: swiftSearchConfig.apiUrl + '/connect',
                method: 'POST',
                data: formData,
                headers: { 'X-WP-Nonce': swiftSearchConfig.nonce }
            }).done(function (response) {
                if (response.success) {
                    alert(swiftSearchConfig.texts.success);
                    self.updateStatus(true, response.data.doc_count);
                    self.renderConnectionState(true);
                } else {
                    console.warn('SwiftSearch: Connect Success=False', response);
                    const msg = response.data && response.data.message ? response.data.message : swiftSearchConfig.texts.error;
                    self.showConnectionError(msg);
                    self.updateStatus(false);
                    self.renderConnectionState(false);
                }
            }).fail(function (xhr, status, error) {
                console.error('SwiftSearch: Connect Failed', { status: xhr.status, response: xhr.responseJSON });

                let msg = swiftSearchConfig.texts.error;
                const res = xhr.responseJSON;

                if (res && res.message) {
                    msg = res.message;
                } else if (res && res.data && res.data.message) {
                    msg = res.data.message;
                }

                self.showConnectionError(msg + ' (HTTP ' + xhr.status + ')');
                self.updateStatus(false);
                self.renderConnectionState(false);
            }).always(function () {
                // If connected, renderConnectionState handles the button text/state
                // Only reset if we failed/didn't switch to 'Disconnect' mode
                const isConnected = self.$statusEl.hasClass('connected');
                if (!isConnected) {
                    $btn.prop('disabled', false).text(oldText);
                }
            });
        },

        handleDisconnect: function () {
            if (!confirm('Are you sure you want to disconnect? This will stop search functionality.')) return;

            const self = this;
            const $btn = this.$connectForm.find('button[type="submit"]');
            $btn.prop('disabled', true).text('Disconnecting...');

            this.request('POST', '/disconnect', {}).done(function (response) {
                self.updateStatus(false, 0);
                self.renderConnectionState(false);
                alert('Disconnected.');
                // Clear fields
                $('#ts-host').val('');
                $('#ts-port').val('443');
                $('#ts-api-key').val('');
                $('#ts-search-key').val('');
            }).always(function () {
                // Render state handles enabling inputs
            });
        },

        showConnectionError: function (msg) {
            let $err = $('#ss-connection-error');
            if (!$err.length) {
                $err = $('<div id="ss-connection-error" class="notice notice-error inline" style="margin: 10px 0 0 0;"><p></p></div>');
                this.$connectForm.find('.submit-wrapper, button[type="submit"]').last().after($err);
                if (!$('#ss-connection-error').length) {
                    this.$connectForm.append($err);
                }
            }
            $err.find('p').text(msg);
            $err.show();
        },

        clearConnectionError: function () {
            $('#ss-connection-error').hide();
        },

        checkStatus: function () {
            const self = this;
            this.request('GET', '/status').done(function (response) {
                if (response.success) {
                    self.updateStatus(response.data.connected, response.data.doc_count);
                }
            });
        },

        checkPlan: function () {
            if (swiftSearchConfig.plan && swiftSearchConfig.plan.isPaying) {
                return;
            }

            this.$proGates.each(function () {
                const $el = $(this);
                $el.addClass('ss-feature-disabled');
                const url = swiftSearchConfig.plan.upgradeUrl || '#';
                $el.append(`
					<div class="ss-feature-lock-overlay">
						<a href="${url}" target="_blank" class="ss-lock-btn">Upgrade to PRO</a>
					</div>
				`);
                $el.find('input, select, textarea').prop('disabled', true);
            });
        },

        updateStatus: function (isConnected, docCount) {
            if (isConnected) {
                this.$statusEl.removeClass('disconnected').addClass('connected').text('Connected');
            } else {
                this.$statusEl.removeClass('connected').addClass('disconnected').text('Disconnected');
            }

            this.renderConnectionState(isConnected);

            if (docCount !== undefined) {
                this.$docCountEl.text(docCount);
            }
        },

        startSync: function () {
            if (!confirm(swiftSearchConfig.texts.confirmSync || 'This will index all selected content. Continue?')) return;

            this.$syncBtn.prop('disabled', true);
            this.$resetBtn.prop('disabled', true);
            this.$syncStatusText.text('Initializing...');
            this.updateProgress(0);

            this.syncQueue = ['posts', 'terms', 'users'];
            this.processNextInQueue();
        },

        processNextInQueue: function () {
            if (this.syncQueue.length === 0) {
                this.finishSync();
                return;
            }

            const type = this.syncQueue.shift();
            let label = 'Content';
            if (type === 'posts') label = 'Posts';
            else if (type === 'terms') label = 'Taxonomies';
            else if (type === 'users') label = 'Authors';

            this.$syncStatusText.text(`Indexing ${label}...`);
            this.processBatch(1, type);
        },

        processBatch: function (page, type) {
            const self = this;

            this.request('POST', '/sync/batch', { page: page, type: type }).done(function (response) {
                if (response.success) {
                    const data = response.data;

                    // Progress Calculation is tricky with multiple queues.
                    // Simplified: Just show percentage of current phase
                    const percent = data.total_pages > 0 ? Math.round((data.page / data.total_pages) * 100) : 100;
                    self.updateProgress(percent, type);

                    if (!data.complete) {
                        self.processBatch(data.page + 1, type);
                    } else {
                        // Phase Complete
                        self.processNextInQueue();
                    }
                } else {
                    self.syncError(response.data.message || 'Error processing batch.');
                }
            }).fail(function (xhr) {
                const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Network Error';
                self.syncError(msg);
            });
        },

        finishSync: function () {
            this.$syncStatusText.text('Indexing Complete!');
            this.$syncBtn.prop('disabled', false);
            this.$resetBtn.prop('disabled', false);
            this.checkStatus();

            setTimeout(function () {
                alert('Success: All selected content has been indexed.');
            }, 100);
        },

        updateProgress: function (percent, type) {
            let label = 'Indexing...';
            if (type === 'posts') label = 'Indexing Posts...';
            else if (type === 'terms') label = 'Indexing Taxonomies...';
            else if (type === 'users') label = 'Indexing Authors...';

            this.$progressCircle.text(percent + '%');
            this.$syncStatusText.text(`${label} ${percent}%`);
        },

        syncError: function (msg) {
            this.$syncStatusText.html('<span style="color: #ef4444;">Error: ' + msg + '</span>');
            this.$syncBtn.prop('disabled', false);
            this.$resetBtn.prop('disabled', false);
        },



        resetIndex: function () {
            if (!confirm(swiftSearchConfig.texts.confirmReset || 'Are you sure you want to delete the entire index? This cannot be undone.')) return;

            const self = this;
            this.$resetBtn.prop('disabled', true);
            this.$syncStatusText.text('Resetting...');

            this.request('POST', '/reset', {}).done(function (response) {
                self.$resetBtn.prop('disabled', false);
                self.$syncStatusText.text('Index Cleared.');
                self.updateProgress(0);
                self.checkStatus();
            }).fail(function () {
                self.$syncStatusText.text('Error resetting index.');
                self.$resetBtn.prop('disabled', false);
            });
        },

        // --- Custom Fields (Pro) ---

        renderCustomFields: function () {
            const container = $('#ss-custom-fields-container');
            if (!container.length) return;

            // Get Settings
            const mappings = swiftSearchConfig.custom_fields || {};
            const availableTypes = swiftSearchConfig.available_post_types || [];

            let html = '';

            // Group by Post Type
            availableTypes.forEach(pt => {
                // Only show if selected in Content Settings? Or show all?
                // Better to show all or maybe just checked ones. 
                // Let's iterate all available, but maybe collapse them?
                // For now, simple stacked tables.

                const fields = mappings[pt.name] || [];

                html += `<div class="ss-cf-section" style="margin-bottom: 30px; border: 1px solid #e5e7eb; border-radius: 6px; overflow: hidden;">`;
                html += `<div style="background: #f9fafb; padding: 10px 15px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin:0; font-size: 14px; font-weight: 600;">${pt.label} (${pt.name})</h3>
                    <button type="button" class="button ss-add-field-btn" data-pt="${pt.name}">+ Add Field</button>
                 </div>`;

                html += `<table class="wp-list-table widefat fixed striped" style="border: none; box-shadow: none;">
                    <thead>
                        <tr>
                            <th style="width: 30%;">Meta Key</th>
                            <th style="width: 30%;">Field Name (in Typesense)</th>
                            <th style="width: 15%;">Type</th>
                            <th style="width: 15%;">Facet?</th>
                            <th style="width: 10%;"></th>
                        </tr>
                    </thead>
                    <tbody class="ss-cf-body" data-pt="${pt.name}">`;

                if (fields.length > 0) {
                    fields.forEach((field, index) => {
                        html += this.getCustomFieldRowHtml(pt.name, index, field);
                    });
                } else {
                    html += `<tr class="ss-cf-empty"><td colspan="5">No custom fields mapped.</td></tr>`;
                }

                html += `</tbody></table></div>`;
            });

            container.html(html);
        },

        getCustomFieldRowHtml: function (postType, index, field = {}) {
            return `
                <tr>
                    <td>
                        <input type="text" class="regular-text" style="width: 100%;" 
                            name="custom_fields[${postType}][${index}][key]" 
                            value="${field.key || ''}" placeholder="_sku, event_date...">
                    </td>
                    <td>
                        <input type="text" class="regular-text" style="width: 100%;" 
                            name="custom_fields[${postType}][${index}][name]" 
                            value="${field.name || ''}" placeholder="sku, date...">
                    </td>
                    <td>
                        <select style="width: 100%;" name="custom_fields[${postType}][${index}][type]">
                            <option value="string" ${field.type === 'string' ? 'selected' : ''}>String</option>
                            <option value="int32" ${field.type === 'int32' ? 'selected' : ''}>Integer</option>
                            <option value="float" ${field.type === 'float' ? 'selected' : ''}>Float</option>
                            <option value="bool" ${field.type === 'bool' ? 'selected' : ''}>Boolean</option>
                            <option value="string[]" ${field.type === 'string[]' ? 'selected' : ''}>Array (String)</option>
                        </select>
                    </td>
                    <td>
                        <input type="checkbox" name="custom_fields[${postType}][${index}][facet]" value="1" ${field.facet ? 'checked' : ''}>
                    </td>
                    <td>
                        <button type="button" class="button-link ss-remove-field-btn" style="color: #ef4444;">Remove</button>
                    </td>
                </tr>
            `;
        },

        // --- Faceted Navigation (Pro) ---

        renderFacetsConfig: function () {
            const container = $('#ss-facets-config-container');
            if (!container.length) return;

            const facetsConfig = swiftSearchConfig.facets_config || [];

            // 1. Gather Sources
            let sources = [];

            // Taxonomies
            const taxes = swiftSearchConfig.available_taxonomies || [];
            taxes.forEach(tax => {
                sources.push({
                    source: tax.name,
                    label: tax.label,
                    type: 'taxonomy'
                });
            });

            // Custom Fields (configured & faceted)
            const customFields = swiftSearchConfig.custom_fields || {};
            const uniqueCustomFacets = {};

            if (customFields && typeof customFields === 'object') {
                Object.values(customFields).forEach(fields => {
                    if (Array.isArray(fields)) {
                        fields.forEach(f => {
                            if (f.facet) {
                                if (!uniqueCustomFacets[f.key]) {
                                    uniqueCustomFacets[f.key] = {
                                        source: f.key,
                                        label: f.name, // Use mapped name
                                        type: 'meta'
                                    };
                                }
                            }
                        });
                    }
                });
            }
            Object.values(uniqueCustomFacets).forEach(f => sources.push(f));

            // 2. Render Table
            let html = `<table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align:center;">Enable</th>
                        <th>Source</th>
                        <th>Display Label</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>`;

            if (sources.length === 0) {
                html += `<tr><td colspan="4" style="text-align:center; padding: 20px;">No available facets found. Ensure you have indexed Taxonomies or mapped Custom Fields as a 'Facet'.</td></tr>`;
            } else {
                sources.forEach(src => {
                    // Check saved config
                    const saved = facetsConfig.find(f => f.source === src.source && f.type === src.type);
                    const isEnabled = saved ? saved.enabled : false;
                    const label = (saved && saved.label) ? saved.label : src.label;

                    html += `<tr>
                        <td style="text-align:center;">
                            <input type="checkbox" class="ss-facet-enable" 
                                data-source="${src.source}" data-type="${src.type}"
                                ${isEnabled ? 'checked' : ''}>
                        </td>
                        <td><strong>${src.source}</strong></td>
                        <td>
                            <input type="text" class="regular-text ss-facet-label" 
                                value="${label}" style="width:100%; border: 1px solid #ddd;">
                        </td>
                         <td><span class="ss-badge ${src.type === 'taxonomy' ? 'ss-badge-tax' : 'ss-badge-meta'}">${src.type === 'taxonomy' ? 'Taxonomy' : 'Meta Field'}</span></td>
                    </tr>`;
                });
            }
            html += `</tbody></table>`;

            container.html(html);
        },

        saveFacetsConfig: function () {
            const facets = [];
            $('#ss-facets-config-container tbody tr').each(function () {
                const $row = $(this);
                const $cb = $row.find('.ss-facet-enable');
                if ($cb.length) {
                    facets.push({
                        source: $cb.data('source'),
                        type: $cb.data('type'),
                        label: $row.find('.ss-facet-label').val(),
                        enabled: $cb.is(':checked')
                    });
                }
            });

            const self = this;
            const $btn = $('#ss-save-facets');
            $btn.prop('disabled', true).text('Saving...');

            this.request('POST', '/settings', { facets_config: facets }).done(function (resp) {
                $btn.prop('disabled', false).text('Save Facets');
                alert('Facets Configuration Saved!');
            }).fail(function () {
                $btn.prop('disabled', false).text('Save Facets');
                alert('Error saving facets.');
            });
        }
    };

    $(document).ready(function () {
        // --- Custom Fields Event Binding (Delegation) ---
        const $container = $('#ss-custom-fields-container');

        // Add
        $container.on('click', '.ss-add-field-btn', function (e) {
            e.preventDefault();
            const pt = $(this).data('pt');
            const $tbody = $container.find(`.ss-cf-body[data-pt="${pt}"]`);
            const index = $tbody.children('tr').not('.ss-cf-empty').length + Date.now(); // Ensure unique index for new items (basic timestamp)

            // Remove empty row if exists
            $tbody.find('.ss-cf-empty').remove();

            $tbody.append(SwiftSearchAdmin.getCustomFieldRowHtml(pt, index));
        });

        // Remove
        $container.on('click', '.ss-remove-field-btn', function (e) {
            e.preventDefault();
            $(this).closest('tr').remove();
        });

        // Save Button for Custom Fields
        $('#ss-save-custom-fields').on('click', function (e) {
            e.preventDefault();
            const $btn = $(this);
            const originalText = $btn.text();
            $btn.prop('disabled', true).text('Saving...');

            // Serialize Form Data
            // We need to parse name="custom_fields[pt][idx][key]" manually or use serializeArray
            // But we can just use jQuery serializeArray on the container inputs
            const raw = $container.find('input, select').serializeArray();
            const customFields = {};

            // Helper to build deep object from name
            raw.forEach(item => {
                // name format: custom_fields[post_type][index][prop]
                // Regex to pull parts
                const match = item.name.match(/custom_fields\[(.*?)\]\[(.*?)\]\[(.*?)\]/);
                if (match) {
                    const pt = match[1];
                    const idx = match[2]; // Use as key temporarily
                    const prop = match[3];

                    if (!customFields[pt]) customFields[pt] = {};
                    if (!customFields[pt][idx]) customFields[pt][idx] = {};

                    if (prop === 'facet') {
                        customFields[pt][idx][prop] = true;
                    } else {
                        customFields[pt][idx][prop] = item.value;
                    }
                }
            });

            // Convert object of objects to array of objects
            const finalPayload = {};
            Object.keys(customFields).forEach(pt => {
                finalPayload[pt] = Object.values(customFields[pt]).filter(f => f.key && f.name); // basic validation
            });

            SwiftSearchAdmin.request('POST', '/settings', { custom_fields: finalPayload }).done(function (response) {
                if (response.success) {
                    alert('Custom Fields Saved!');
                } else {
                    alert('Failed to save.');
                }
            }).always(function () {
                $btn.prop('disabled', false).text(originalText);
            });
        });

        SwiftSearchAdmin.init();
    });

})(jQuery);
