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

        var showLabel = toggleBtn.dataset.labelShow || 'Show menu';
        var hideLabel = toggleBtn.dataset.labelHide || 'Hide menu';

        if (window.innerWidth < 1200) {
            var mobileOpen = document.body.classList.contains('hm-sidebar-mobile-open');
            toggleBtn.setAttribute('aria-expanded', mobileOpen ? 'true' : 'false');
            toggleBtn.setAttribute('aria-label', mobileOpen ? hideLabel : showLabel);
            toggleBtn.classList.toggle('is-collapsed', !mobileOpen);
            return;
        }

        var collapsed = isCollapsed();

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

        // Add backdrop element if it doesn't exist
        var backdrop = document.querySelector('.hm-sidebar-mobile-backdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'hm-sidebar-mobile-backdrop';
            document.body.appendChild(backdrop);
            
            backdrop.addEventListener('click', function() {
                document.body.classList.remove('hm-sidebar-mobile-open');
                updateToggleUi();
            });
        }

        toggleBtn.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            
            if (window.innerWidth < 1200) {
                document.body.classList.toggle('hm-sidebar-mobile-open');
                updateToggleUi();
                return;
            }

            toggleSidebar();
        });

        toggleBtn.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                if (window.innerWidth < 1200) {
                    document.body.classList.toggle('hm-sidebar-mobile-open');
                    updateToggleUi();
                    return;
                }
                toggleSidebar();
            }
        });
        
        window.addEventListener('resize', function() {
            updateToggleUi();
            if (window.innerWidth >= 1200 && document.body.classList.contains('hm-sidebar-mobile-open')) {
                document.body.classList.remove('hm-sidebar-mobile-open');
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
