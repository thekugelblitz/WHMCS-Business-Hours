/**
 * Business Hours - Client Area Widget JavaScript
 */
(function() {
    'use strict';

    var BHWidgets = {
        initialized: false,
        clientTimezone: null,

        init: function() {
            if (this.initialized) return;
            this.initialized = true;

            // Detect client timezone
            try {
                this.clientTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
            } catch (e) {
                this.clientTimezone = null;
            }

            this.initCountdowns();
            this.initBannerClose();
            this.initFloatingToggle();
            this.trackInitialViews();
        },

        /**
         * Initialize countdown timers
         */
        initCountdowns: function() {
            var countdowns = document.querySelectorAll('[data-bh-countdown]');
            if (countdowns.length === 0) return;

            var self = this;
            setInterval(function() {
                for (var i = 0; i < countdowns.length; i++) {
                    var el = countdowns[i];
                    var seconds = parseInt(el.getAttribute('data-bh-countdown'), 10);
                    if (isNaN(seconds) || seconds <= 0) {
                        el.textContent = 'now';
                        continue;
                    }
                    seconds--;
                    el.setAttribute('data-bh-countdown', seconds);
                    el.textContent = self.formatCountdown(seconds);
                }
            }, 1000);
        },

        /**
         * Format seconds into a human-readable countdown
         */
        formatCountdown: function(seconds) {
            if (seconds <= 0) return 'now';

            var h = Math.floor(seconds / 3600);
            var m = Math.floor((seconds % 3600) / 60);
            var s = seconds % 60;

            var parts = [];
            if (h > 0) parts.push(h + 'h');
            if (m > 0) parts.push(m + 'm');
            if (parts.length === 0) parts.push(s + 's');

            return parts.join(' ');
        },

        /**
         * Initialize banner close button
         */
        initBannerClose: function() {
            var closeBtn = document.querySelector('.bh-banner__close');
            if (!closeBtn) return;

            closeBtn.addEventListener('click', function() {
                var banner = this.closest('.bh-banner');
                if (banner) {
                    banner.style.transition = 'transform 0.3s ease, opacity 0.3s ease';
                    banner.style.transform = 'translateY(-100%)';
                    banner.style.opacity = '0';
                    setTimeout(function() {
                        banner.style.display = 'none';
                    }, 300);

                    // Remember dismissal for this session
                    try {
                        sessionStorage.setItem('bh_banner_dismissed', '1');
                    } catch (e) {}
                }
            });

            // Check if already dismissed
            try {
                if (sessionStorage.getItem('bh_banner_dismissed') === '1') {
                    var banner = document.querySelector('.bh-banner');
                    if (banner) banner.style.display = 'none';
                }
            } catch (e) {}
        },

        /**
         * Initialize floating indicator click toggle
         */
        initFloatingToggle: function() {
            var floating = document.querySelector('.bh-floating');
            if (!floating) return;

            floating.addEventListener('click', function() {
                // Navigate to the full schedule page
                var url = floating.getAttribute('data-bh-url');
                if (url) {
                    window.location.href = url;
                }
            });
        },

        /**
         * Track initial widget views (fire-and-forget)
         */
        trackInitialViews: function() {
            var widgets = document.querySelectorAll('[data-bh-widget]');
            for (var i = 0; i < widgets.length; i++) {
                var widgetType = widgets[i].getAttribute('data-bh-widget');
                var deptId = widgets[i].getAttribute('data-bh-department') || null;
                this.trackView(widgetType, deptId);
            }
        },

        /**
         * Send an analytics tracking beacon
         */
        trackView: function(widgetType, departmentId) {
            try {
                var params = 'widget=' + encodeURIComponent(widgetType);
                if (departmentId) params += '&department_id=' + departmentId;
                params += '&page=' + encodeURIComponent(window.location.pathname);

                // Use sendBeacon for non-blocking request
                if (navigator.sendBeacon) {
                    navigator.sendBeacon('index.php?m=business_hours&action=track&' + params);
                } else {
                    var img = new Image();
                    img.src = 'index.php?m=business_hours&action=track&' + params + '&_=' + Date.now();
                }
            } catch (e) {
                // Analytics should never break the page
            }
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() { BHWidgets.init(); });
    } else {
        BHWidgets.init();
    }

    // Expose globally for live-update.js
    window.BHWidgets = BHWidgets;

})();
