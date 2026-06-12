/**
 * Business Hours - AJAX Live Status Update Engine
 *
 * Polls the status endpoint at configurable intervals and updates DOM elements.
 * Pauses polling when the browser tab is hidden (Page Visibility API).
 */
(function() {
    'use strict';

    var BHLiveUpdate = {
        endpoint: 'index.php?m=business_hours&action=status',
        interval: 60,         // seconds, overridden by server response
        timer: null,
        paused: false,
        lastUpdate: 0,

        init: function(config) {
            config = config || {};
            if (config.interval) this.interval = parseInt(config.interval, 10);
            if (config.endpoint) this.endpoint = config.endpoint;

            this.bindVisibility();
            this.startPolling();
        },

        startPolling: function() {
            var self = this;
            this.timer = setInterval(function() {
                if (!self.paused) {
                    self.fetchStatus();
                }
            }, this.interval * 1000);
        },

        stopPolling: function() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },

        fetchStatus: function() {
            var self = this;
            var xhr = new XMLHttpRequest();
            xhr.open('GET', this.endpoint + '&_=' + Date.now(), true);
            xhr.timeout = 10000;

            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        if (data.success) {
                            self.updateDOM(data);
                            self.lastUpdate = Date.now();

                            // Update interval from server if provided
                            if (data.interval && data.interval !== self.interval) {
                                self.interval = data.interval;
                                self.stopPolling();
                                self.startPolling();
                            }
                        }
                    } catch (e) {
                        // JSON parse error, skip
                    }
                }
            };

            xhr.onerror = function() {
                // Network error — will retry on next interval
            };

            xhr.send();
        },

        updateDOM: function(data) {
            // Update aggregate status elements
            if (data.aggregate) {
                this.updateStatusElements('all', data.aggregate);
            }

            // Update per-department status elements
            if (data.departments) {
                for (var deptId in data.departments) {
                    if (data.departments.hasOwnProperty(deptId)) {
                        var deptData = data.departments[deptId];
                        this.updateStatusElements(deptId, deptData.status);
                    }
                }
            }
        },

        updateStatusElements: function(deptKey, status) {
            // Update status badges
            var badges = document.querySelectorAll('[data-bh-status="' + deptKey + '"]');
            for (var i = 0; i < badges.length; i++) {
                var badge = badges[i];

                // Update text
                var labelEl = badge.querySelector('.bh-status-label');
                if (labelEl) {
                    labelEl.textContent = status.label;
                }

                // Update status classes
                badge.className = badge.className
                    .replace(/bh-status-badge--\w+/g, '')
                    .trim();

                var statusClass = status.is_open ? 'bh-status-badge--online' : 'bh-status-badge--offline';
                if (status.source === 'holiday') statusClass = 'bh-status-badge--holiday';
                badge.classList.add(statusClass);

                // Update dot
                var dot = badge.querySelector('.bh-dot');
                if (dot) {
                    dot.className = 'bh-dot';
                    dot.classList.add(status.is_open ? 'bh-dot--online' : 'bh-dot--offline');
                    if (status.source === 'holiday') dot.classList.add('bh-dot--holiday');
                }
            }

            // Update "today hours" elements
            var hoursEls = document.querySelectorAll('[data-bh-hours="' + deptKey + '"]');
            for (var j = 0; j < hoursEls.length; j++) {
                hoursEls[j].textContent = status.today_hours || 'N/A';
            }

            // Update "next change" elements
            var nextEls = document.querySelectorAll('[data-bh-next="' + deptKey + '"]');
            for (var k = 0; k < nextEls.length; k++) {
                nextEls[k].textContent = status.next_change || '';
            }
        },

        /**
         * Pause polling when tab is hidden, resume when visible
         */
        bindVisibility: function() {
            var self = this;
            if (typeof document.hidden !== 'undefined') {
                document.addEventListener('visibilitychange', function() {
                    if (document.hidden) {
                        self.paused = true;
                    } else {
                        self.paused = false;
                        // Immediate refresh when tab becomes visible
                        if (Date.now() - self.lastUpdate > self.interval * 1000) {
                            self.fetchStatus();
                        }
                    }
                });
            }
        },

        destroy: function() {
            this.stopPolling();
        }
    };

    // Auto-initialize if config element exists
    var configEl = document.getElementById('bh-live-config');
    if (configEl) {
        var config = {};
        if (configEl.getAttribute('data-interval')) {
            config.interval = configEl.getAttribute('data-interval');
        }
        if (configEl.getAttribute('data-endpoint')) {
            config.endpoint = configEl.getAttribute('data-endpoint');
        }
        BHLiveUpdate.init(config);
    }

    window.BHLiveUpdate = BHLiveUpdate;

})();
