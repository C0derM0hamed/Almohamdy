(function () {
    'use strict';

    var STORAGE_KEY = 'hm-sidebar-collapsed';
    var sidebar = null;
    var toggleBtn = null;

    function isCollapsed() {
        return document.body.classList.contains('hm-sidebar-collapsed');
    }

    function readStoredState() {
        try {
            return window.sessionStorage.getItem(STORAGE_KEY) === '1';
        } catch (error) {
            return false;
        }
    }

    function storeState(collapsed) {
        try {
            window.sessionStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
        } catch (error) {
            // Ignore storage errors.
        }
    }

    function updateToggleUi() {
        if (!toggleBtn) {
            return;
        }

        var collapsed = isCollapsed();
        var showLabel = toggleBtn.dataset.labelShow || 'Show menu';
        var hideLabel = toggleBtn.dataset.labelHide || 'Hide menu';

        toggleBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        toggleBtn.setAttribute('aria-label', collapsed ? showLabel : hideLabel);
        toggleBtn.classList.toggle('is-collapsed', collapsed);
    }

    function applyCollapsedState(collapsed, persist) {
        document.documentElement.classList.toggle('hm-sidebar-is-collapsed', collapsed);
        document.body.classList.toggle('hm-sidebar-collapsed', collapsed);

        if (persist) {
            storeState(collapsed);
        }

        updateToggleUi();
    }

    function toggleSidebar() {
        applyCollapsedState(!isCollapsed(), true);
    }

    function bindToggle() {
        toggleBtn = document.getElementById('hmSidebarToggle');

        if (!toggleBtn) {
            return;
        }

        toggleBtn.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            toggleSidebar();
        });

        toggleBtn.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                toggleSidebar();
            }
        });

        updateToggleUi();
    }

    function init() {
        sidebar = document.querySelector('.sidebar.sidebar-default');
        applyCollapsedState(readStoredState(), false);
        bindToggle();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
