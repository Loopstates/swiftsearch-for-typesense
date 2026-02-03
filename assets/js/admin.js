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

            // Set Experience State

            if (swiftSearchConfig.status.overrideDefault) {
                this.$overrideToggle.prop('checked', true);
            }

            // Set Experience State
            if (swiftSearchConfig.experience) {
                this.$ssTypoTolerance.prop('checked', !!swiftSearchConfig.experience.typo_tolerance);
                this.$ssSortEnabled.prop('checked', !!swiftSearchConfig.experience.sort_enabled);
                this.$ssMobileBtn.prop('checked', !!swiftSearchConfig.experience.mobile_btn);
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

            const available = swiftSearchConfig.available_post_types || [];
            const saved = swiftSearchConfig.indexed_post_types || [];

            console.log('Rendering Content Settings. Available:', available);
            let html = '';

            available.forEach(type => {
                if (!type || !type.name) return;

                const isChecked = saved.includes(type.name) ? 'checked' : '';
                html += `<label class="ss-checkbox-card">
                    <input type="checkbox" name="post_types[]" value="${type.name}" ${isChecked}>
                    <div class="info">
                        <span class="title">${type.label || type.name}</span>
                        <span class="meta">${type.description || type.name}</span>
                    </div>
                </label>`;
            });

            if (html === '') {
                html = '<p>No public post types found.</p>';
            }

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

            const payload = {
                post_types: postTypes
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
                    mobile_btn: this.$ssMobileBtn.is(':checked')
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

            let shortcode = `[swift_search placeholder="${placeholder}" limit="${limit}"`;

            if (thumb === 'false') shortcode += ` show_thumbnail="false"`;
            if (price === 'false') shortcode += ` show_price="false"`;
            if (excerpt === 'true') shortcode += ` show_excerpt="true"`;

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
            if (!confirm('This will index all posts/products. Continue?')) return;

            const self = this;
            this.$syncBtn.prop('disabled', true);
            this.$resetBtn.prop('disabled', true);
            this.$syncStatusText.text('Initializing...');

            this.processBatch(1);
        },

        processBatch: function (page) {
            const self = this;

            this.request('POST', '/sync/batch', { page: page }).done(function (response) {
                if (response.success) {
                    const data = response.data;
                    const percent = Math.round((data.page / data.total_pages) * 100);

                    self.updateProgress(percent);

                    if (!data.complete) {
                        self.processBatch(data.page + 1);
                    } else {
                        self.$syncStatusText.text('Indexing Complete!');
                        self.$syncBtn.prop('disabled', false);
                        self.$resetBtn.prop('disabled', false);
                        self.checkStatus();
                        alert('Indexing Complete!');
                    }
                } else {
                    self.syncError('Error processing batch.');
                }
            }).fail(function () {
                self.syncError('Network Error.');
            });
        },

        syncError: function (msg) {
            this.$syncStatusText.text('Error: ' + msg);
            this.$syncBtn.prop('disabled', false);
            this.$resetBtn.prop('disabled', false);
        },

        updateProgress: function (percent) {
            this.$progressCircle.text(percent + '%');
            this.$syncStatusText.text('Indexing... ' + percent + '%');
        },

        resetIndex: function () {
            if (!confirm('Are you sure you want to delete the entire index? This cannot be undone.')) return;

            const self = this;
            this.$resetBtn.prop('disabled', true);
            this.$syncStatusText.text('Resetting...');

            this.request('POST', '/reset', {}).done(function (response) {
                self.$resetBtn.prop('disabled', false);
                self.$syncStatusText.text('Index Cleared.');
                self.updateProgress(0);
                self.checkStatus();
                alert('Index has been reset.');
            }).fail(function () {
                alert('Error resetting index.');
                self.$resetBtn.prop('disabled', false);
            });
        }
    };

    $(document).ready(function () {
        SwiftSearchAdmin.init();
    });

})(jQuery);
