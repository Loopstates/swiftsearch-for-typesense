(function ($) {
    'use strict';

    const SwiftSearchAdmin = {
        init: function () {
            this.cacheDOM();
            this.bindEvents();
            this.checkStatus();
            this.checkPlan();
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

            // Pro Gates
            this.$proGates = $('.ss-pro-gate');
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
