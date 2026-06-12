/**
 * Business Hours - Admin Area JavaScript
 */
(function() {
    'use strict';

    // Dynamic time slot management for schedule forms
    document.addEventListener('DOMContentLoaded', function() {
        initSlotManagement();
    });

    function initSlotManagement() {
        // Add slot buttons
        var addButtons = document.querySelectorAll('.bh-add-slot');
        for (var i = 0; i < addButtons.length; i++) {
            addButtons[i].addEventListener('click', function(e) {
                e.preventDefault();
                var day = this.getAttribute('data-day');
                addSlot(day);
            });
        }

        // Remove slot buttons (event delegation)
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('bh-remove-slot') || e.target.closest('.bh-remove-slot')) {
                e.preventDefault();
                var row = e.target.closest('.bh-slot-row');
                if (row) {
                    row.style.transition = 'opacity 0.2s, transform 0.2s';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(10px)';
                    setTimeout(function() { row.remove(); }, 200);
                }
            }
        });
    }

    function addSlot(day) {
        var container = document.getElementById('bh-day-' + day + '-slots');
        if (!container) return;

        var existingSlots = container.querySelectorAll('.bh-slot-row');
        var idx = existingSlots.length;

        var row = document.createElement('div');
        row.className = 'bh-slot-row';
        row.style.opacity = '0';
        row.style.transform = 'translateX(-10px)';

        row.innerHTML = [
            '<input type="time" name="slots[' + day + '][' + idx + '][open]" value="09:00" class="form-control bh-time-input">',
            '<span class="bh-slot-dash">&mdash;</span>',
            '<input type="time" name="slots[' + day + '][' + idx + '][close]" value="17:00" class="form-control bh-time-input">',
            '<input type="text" name="slots[' + day + '][' + idx + '][label]" class="form-control bh-slot-label" placeholder="Label (optional)">',
            '<button type="button" class="btn btn-xs btn-danger bh-remove-slot"><i class="fas fa-times"></i></button>'
        ].join('');

        container.appendChild(row);

        // Animate in
        requestAnimationFrame(function() {
            row.style.transition = 'opacity 0.3s, transform 0.3s';
            row.style.opacity = '1';
            row.style.transform = 'translateX(0)';
        });
    }

    // Make addSlot globally available for the admin-calendar.js
    window.bhAddSlot = addSlot;

})();
