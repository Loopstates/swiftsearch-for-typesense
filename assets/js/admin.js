(function ($) {
    'use strict';

    const SwiftSearchAdmin = {
        init: function () {
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
            this.$postTypeInputs = $('input[name="post_types[]"]');
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
        },

        init: function () {
            this.cacheDOM();
            this.bindEvents();
            this.checkStatus();
            this.checkPlan();

            // Set Initial State
            // Indexed Post Types
            if (swiftSearchConfig.indexed_post_types && Array.isArray(swiftSearchConfig.indexed_post_types)) {
                this.$postTypeInputs.prop('checked', false); // Default unchecked
                swiftSearchConfig.indexed_post_types.forEach(type => {
                    $(`input[name="post_types[]"][value="${type}"]`).prop('checked', true);
                });
            }

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

        saveContentSettings: function (e) {
            e.preventDefault();
            const $btn = this.$saveContentBtn;
            const originalText = $btn.text();
            $btn.prop('disabled', true).text('Saving...');

            const postTypes = [];
            $('input[name="post_types[]"]:checked').each(function () {
                postTypes.push($(this).val());
            });

            // We need a way to pass post_types settings. 
            // Currently RestController only accepts them in /settings.

            const payload = {
                post_types: postTypes
            };

            $.post(swiftSearchConfig.apiUrl + '/settings', payload, function (response) {
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
            // Debounce or save immediately? Change event is good for now.

            // Transform UI to Data
            const weightVal = this.$relevanceRange.val();
            const weight = Math.max(1, Math.round(weightVal / 10)); // Scale 100 -> 10

            // Synonyms Parsing: "foo, bar, baz \n a, b" -> structured array
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

            // Prepare Payload
            const payload = {
                relevance_settings: {
                    weights: {
                        post_title: weight,
                        // Default others
                        post_content: 2,
                        sku: 4,
                        category: 2,
                        tag: 2
                    },
                    synonyms: synonyms
                }
            };

            // Save
            $.post(swiftSearchConfig.apiUrl + '/settings', payload, function (response) {
                // SIlent saving or toast
            });
        },

        handleOverrideChange: function (e) {
            const isChecked = $(e.currentTarget).is(':checked');

            // We need a settings endpoint. Re-using /connect for simplicity or assume /settings exists?
            // Let's assume we need to add /settings to RestController. For now, we use a custom action on connect or just separate post.
            // Actually, best to add a specific endpoint. 
            // Since we are iterating, let's POST to /settings endpoint which we will create next.

            $.post(swiftSearchConfig.apiUrl + '/settings', {
                override_default: isChecked
            }, function (response) {
                if (response.success) {
                    // Saved
                } else {
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

            $.post(swiftSearchConfig.apiUrl + '/settings', payload, function (response) {
                if (!response.success) {
                    alert('Failed to save settings.');
                }
            });
        },

        updateShortcodePreview: function () {
            const placeholder = this.$scPlaceholder.val() || 'Search...';
            const limit = this.$scLimit.val() || 10;
            const thumb = this.$scShowThumbnail.is(':checked') ? 'true' : 'false';
            const price = this.$scShowPrice.is(':checked') ? 'true' : 'false';
            const excerpt = this.$scShowExcerpt.is(':checked') ? 'true' : 'false';

            let shortcode = `[swift_search placeholder="${placeholder}" limit="${limit}"`;

            // Only add defaults if they differ? No, be explicit for clarity.
            if (thumb === 'false') shortcode += ` show_thumbnail="false"`;
            if (price === 'false') shortcode += ` show_price="false"`;
            if (excerpt === 'true') shortcode += ` show_excerpt="true"`; // Default is likely hidden/false

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
            // Update Nav
            this.$navItems.removeClass('active');
            this.$navItems.filter('[data-step="' + stepName + '"]').addClass('active');

            // Update View
            this.$views.hide();
            $('#view-' + stepName).fadeIn(200);

            if (stepName === 'analytics') {
                this.loadAnalytics();
            }
        },

        loadAnalytics: function () {
            const self = this;
            const $topParams = $('#ss-analytics-top tbody');
            const $zeroParams = $('#ss-analytics-zero tbody');

            // Set Loading
            $topParams.html('<tr><td colspan="3">Loading...</td></tr>');
            $zeroParams.html('<tr><td colspan="2">Loading...</td></tr>');

            $.get(swiftSearchConfig.apiUrl + '/analytics', function (response) {
                if (response.success) {
                    const top = response.data.top_queries;
                    const zero = response.data.no_results;

                    // Render Top
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

                    // Render Zero
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

        handleConnect: function (e) {
            e.preventDefault();
            const formData = this.$connectForm.serialize();
            const self = this;
            const $btn = this.$connectForm.find('button[type="submit"]');
            const oldText = $btn.text();

            $btn.prop('disabled', true).text(swiftSearchConfig.texts.connecting);

            $.post(swiftSearchConfig.apiUrl + '/connect', formData, function (response) {
                if (response.success) {
                    alert(swiftSearchConfig.texts.success);
                    self.updateStatus(true, response.data.doc_count);
                    // Auto advance to next step after success?
                    // self.switchView( 'content' ); 
                } else {
                    alert(response.data && response.data.message ? response.data.message : swiftSearchConfig.texts.error);
                    self.updateStatus(false);
                }
            }).fail(function () {
                alert(swiftSearchConfig.texts.error);
                self.updateStatus(false);
            }).always(function () {
                $btn.prop('disabled', false).text(oldText);
            });
        },

        checkStatus: function () {
            const self = this;
            $.get(swiftSearchConfig.apiUrl + '/status', function (response) {
                if (response.success) {
                    self.updateStatus(response.data.connected, response.data.doc_count);
                }
            });
        },

        checkPlan: function () {
            if (swiftSearchConfig.plan && swiftSearchConfig.plan.isPaying) {
                return; // Plan is pro, do nothing
            }

            // Lock features
            this.$proGates.each(function () {
                const $el = $(this);
                $el.addClass('ss-feature-disabled');

                // Append Overlay
                const url = swiftSearchConfig.plan.upgradeUrl || '#';
                $el.append(`
					<div class="ss-feature-lock-overlay">
						<a href="${url}" target="_blank" class="ss-lock-btn">Upgrade to PRO</a>
					</div>
				`);

                // Disable inputs inside
                $el.find('input, select, textarea').prop('disabled', true);
            });
        },

        updateStatus: function (isConnected, docCount) {
            if (isConnected) {
                this.$statusEl.removeClass('disconnected').addClass('connected').text('Connected');
            } else {
                this.$statusEl.removeClass('connected').addClass('disconnected').text('Disconnected');
            }

            if (docCount !== undefined) {
                this.$docCountEl.text(docCount);
            }
        },

        // --- Batch Sync Logic ---

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

            $.post(swiftSearchConfig.apiUrl + '/sync/batch', { page: page }, function (response) {
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
                        self.checkStatus(); // Refresh doc count
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

            $.post(swiftSearchConfig.apiUrl + '/reset', {}, function (response) {
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
