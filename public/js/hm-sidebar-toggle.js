(function () {
    'use strict';

    var STORAGE_KEY = 'hm-sidebar-collapsed';
    var PINNED_KEY = 'hm-sidebar-pinned';
    var sidebar = null;
    var toggleButtons = [];
    var resizeBound = false;
    var hoverExpanded = false;
    var revealSequence = 0;

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

    function isPinned() {
        try {
            return window.sessionStorage.getItem(PINNED_KEY) === '1';
        } catch (error) {
            return false;
        }
    }

    function storePinned(pinned) {
        try {
            window.sessionStorage.setItem(PINNED_KEY, pinned ? '1' : '0');
        } catch (error) {
            // Ignore storage errors.
        }
    }

    function updateToggleIcon(collapsed, mobile) {
        if (!toggleButtons.length) {
            return;
        }

        var isRtl = document.documentElement.dir === 'rtl';
        var expandedIcon = isRtl ? 'bi-layout-sidebar-reverse' : 'bi-layout-sidebar';
        var collapsedIcon = isRtl ? 'bi-layout-sidebar-inset-reverse' : 'bi-layout-sidebar-inset';

        toggleButtons.forEach(function (button) {
            var icon = button.querySelector('i');
            if (!icon) return;

            icon.classList.remove(
                'bi-list',
                'bi-layout-sidebar',
                'bi-layout-sidebar-inset',
                'bi-layout-sidebar-reverse',
                'bi-layout-sidebar-inset-reverse'
            );
            // The hamburger is the one menu glyph every user recognises.
            icon.classList.add(mobile ? 'bi-list' : (collapsed ? collapsedIcon : expandedIcon));
        });
    }

    function updateToggleUi() {
        if (!toggleButtons.length) {
            return;
        }

        if (window.innerWidth < 1200) {
            var mobileOpen = document.body.classList.contains('hm-sidebar-mobile-open');
            toggleButtons.forEach(function (button) {
                var showLabel = button.dataset.labelShow || 'Show menu';
                var hideLabel = button.dataset.labelHide || 'Hide menu';
                button.setAttribute('aria-expanded', mobileOpen ? 'true' : 'false');
                button.setAttribute('aria-label', mobileOpen ? hideLabel : showLabel);
                button.classList.toggle('is-collapsed', !mobileOpen);
            });
            updateToggleIcon(!mobileOpen, true);
            return;
        }

        var collapsed = isCollapsed();

        toggleButtons.forEach(function (button) {
            var showLabel = button.dataset.labelShow || 'Show menu';
            var hideLabel = button.dataset.labelHide || 'Hide menu';
            button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            button.setAttribute('aria-pressed', isPinned() ? 'true' : 'false');
            button.setAttribute('aria-label', collapsed ? showLabel : hideLabel);
            button.classList.toggle('is-collapsed', collapsed);
        });
        updateToggleIcon(collapsed);
    }

    function applyCollapsedState(collapsed, persist) {
        if (sidebar) {
            // Hope UI's resize plugin adds its own sidebar-mini/on-resize
            // classes below 1025px. They conflict with this shell's drawer
            // and cause the delayed narrow-rail layout, so keep one owner for
            // sidebar sizing.
            sidebar.classList.remove('sidebar-mini', 'on-resize');
        }

        document.documentElement.classList.toggle('hm-sidebar-is-collapsed', collapsed);
        document.body.classList.toggle('hm-sidebar-collapsed', collapsed);

        if (persist) {
            storeState(collapsed);
        }

        updateToggleUi();

        // The active target changes when the rail is collapsed or expanded.
        // Re-resolve it after every state transition so a collapse cannot
        // retain the expanded child position, and an expansion can reveal
        // the actual active leaf.
        if (sidebar && window.innerWidth >= 1200) {
            revealActiveItem();
        }
    }

    function toggleSidebar() {
        applyCollapsedState(!isCollapsed(), true);
    }

    function handleDesktopToggle() {
        if (hoverExpanded && !isPinned()) {
            // The button was revealed by hover. Keep the temporary expansion
            // instead of interpreting it as a request to collapse.
            hoverExpanded = false;
            storePinned(true);
            applyCollapsedState(false, true);
            return;
        }

        hoverExpanded = false;

        if (isPinned()) {
            // A second click releases the pin and returns to hover mode.
            storePinned(false);
            applyCollapsedState(true, true);
            return;
        }

        if (isCollapsed()) {
            // Clicking the button while it is revealed by hover pins the
            // expanded sidebar across page navigation in this session.
            storePinned(true);
            applyCollapsedState(false, true);
            return;
        }

        storePinned(false);
        applyCollapsedState(true, true);
    }

    function bindHoverExpansion() {
        if (!sidebar || sidebar.dataset.hmSidebarHoverBound === 'true') {
            return;
        }

        sidebar.dataset.hmSidebarHoverBound = 'true';

        sidebar.addEventListener('pointerenter', function (event) {
            // A real touch has no hover state. Keep the normal mobile drawer
            // interaction for touch devices and only use hover for a mouse or
            // another pointing device that can actually leave the sidebar.
            if (event.pointerType && event.pointerType !== 'mouse') {
                return;
            }

            if (isPinned() || !isCollapsed()) {
                return;
            }

            hoverExpanded = true;

            if (window.innerWidth < 1200) {
                applyCollapsedState(false, false);
                document.body.classList.add('hm-sidebar-mobile-open');
                updateToggleUi();
                return;
            }

            // Use the same state transition as the expand button. This keeps
            // labels, submenus, content width, and accessibility state in
            // sync instead of merely painting a wider rail.
            applyCollapsedState(false, false);
            revealActiveItem();
        });

        sidebar.addEventListener('pointerleave', function (event) {
            if (event.pointerType && event.pointerType !== 'mouse') {
                return;
            }

            if (isPinned() || !hoverExpanded) {
                return;
            }

            hoverExpanded = false;

            if (window.innerWidth < 1200) {
                document.body.classList.remove('hm-sidebar-mobile-open');
                updateToggleUi();
                return;
            }

            applyCollapsedState(true, false);
        });

        // `:focus-within` is another expanded presentation of the rail. Keep
        // its active child aligned as soon as keyboard focus opens it, and
        // restore the parent-icon position when focus leaves the sidebar.
        sidebar.addEventListener('focusin', function () {
            if (window.innerWidth >= 1200 && isCollapsed()) {
                revealActiveItem();
            }
        });

        sidebar.addEventListener('focusout', function (event) {
            if (
                window.innerWidth >= 1200
                && isCollapsed()
                && (!event.relatedTarget || !sidebar.contains(event.relatedTarget))
            ) {
                revealActiveItem();
            }
        });
    }

    function bindCollapsedInteractions() {
        document.querySelectorAll('#hmAppSidebar a[data-bs-toggle="collapse"]').forEach(function (link) {
            if (link.dataset.hmSidebarGroupBound === 'true') return;
            link.dataset.hmSidebarGroupBound = 'true';
            link.addEventListener('click', function (event) {
                if (window.innerWidth >= 1200 && isCollapsed()) {
                    event.preventDefault();
                    event.stopImmediatePropagation();

                    var targetId = (this.getAttribute('href') || '').replace(/^#/, '');
                    var subNav = targetId ? document.getElementById(targetId) : null;
                    var firstPage = subNav ? subNav.querySelector('a.nav-link[href]') : null;

                    if (firstPage) {
                        firstPage.click();
                    } else {
                        toggleSidebar();
                    }
                }
            });
        });
    }

    function sidebarIsVisuallyExpanded() {
        if (!sidebar || !isCollapsed()) {
            return true;
        }

        // CSS also opens the rail for keyboard focus. Use the rendered state
        // as the source of truth instead of relying only on pointerenter.
        return sidebar.matches(':hover, :focus-within');
    }

    function scrollTargetIntoView(scrollBody, target) {
        if (!scrollBody || !target || !target.isConnected) {
            return;
        }

        var targetRect = target.getBoundingClientRect();
        var bodyRect = scrollBody.getBoundingClientRect();
        var inset = 8;
        var currentTop = Number(scrollBody.scrollTop) || 0;
        var nextTop = currentTop;

        if (targetRect.top < bodyRect.top + inset) {
            nextTop += targetRect.top - (bodyRect.top + inset);
        } else if (targetRect.bottom > bodyRect.bottom - inset) {
            nextTop += targetRect.bottom - (bodyRect.bottom - inset);
        }

        // Bound the write to the actual scroll range. This prevents the
        // collapsed rail from landing beyond its content and showing an
        // empty gap after a long expanded submenu has disappeared.
        var maxTop = Math.max(0, scrollBody.scrollHeight - scrollBody.clientHeight);
        nextTop = Math.max(0, Math.min(maxTop, nextTop));

        if (Math.abs(nextTop - currentTop) > 0.5) {
            scrollBody.scrollTop = nextTop;
        }
    }

    function revealActiveItem() {
        var sequence = ++revealSequence;

        if (!sidebar) {
            return;
        }

        var activeLeaf = sidebar.querySelector('a.nav-link.active[aria-current="page"]');
        if (!activeLeaf) {
            return;
        }

        // A nested page may sit inside more than one collapsible group.
        // Keep every ancestor open even if Bootstrap or a stale client state
        // tried to restore a closed group after the server rendered it active.
        var parentGroup = activeLeaf.closest('.sub-nav');
        var activeLeafIsGrouped = Boolean(parentGroup);
        var visibleParentToggle = null;
        while (parentGroup) {
            parentGroup.classList.add('show');

            var parentItem = parentGroup.parentElement;
            var toggle = parentItem
                ? parentItem.querySelector(':scope > a.nav-link[data-bs-toggle="collapse"]')
                : null;
            if (toggle) {
                toggle.classList.add('active');
                toggle.setAttribute('aria-expanded', 'true');
                // Keep replacing this with the outermost ancestor. It is the
                // only parent icon that remains visible in the compact rail.
                visibleParentToggle = toggle;
            }
            parentGroup = parentItem
                ? parentItem.closest('.sub-nav')
                : null;
        }

        var scrollBody = sidebar.querySelector('.sidebar-body');
        if (!scrollBody || window.innerWidth < 1200) {
            return;
        }

        // Wait for the width/label transition and submenu layout to settle.
        // A single final scroll write avoids the visible scrollTop stepping
        // that occurs when the active leaf is measured during the expansion.
        var frame = 0;
        var stableFrames = 0;
        var previousLayout = '';
        var settle = function () {
            if (sequence !== revealSequence || !activeLeaf.isConnected) {
                return;
            }

            var compact = isCollapsed() && !sidebarIsVisuallyExpanded();
            // In compact mode the leaf is deliberately display:none. Do not
            // read its rect and never let it influence scrollTop.
            var scrollTarget = compact
                ? (visibleParentToggle || (activeLeafIsGrouped ? null : activeLeaf))
                : activeLeaf;

            if (!scrollTarget || !scrollTarget.isConnected) {
                return;
            }

            var targetRect = scrollTarget.getBoundingClientRect();
            var bodyRect = scrollBody.getBoundingClientRect();
            var targetVisible = targetRect.height > 0 && scrollTarget.getClientRects().length > 0;

            if (!targetVisible || bodyRect.height <= 0) {
                if (frame++ < 30) {
                    window.requestAnimationFrame(settle);
                }
                return;
            }

            var layout = [
                scrollTarget.offsetTop,
                targetRect.height,
                sidebar.offsetWidth,
                scrollBody.clientHeight,
                scrollBody.scrollHeight
            ].join('|');

            stableFrames = layout === previousLayout ? stableFrames + 1 : 0;
            previousLayout = layout;
            frame += 1;

            if (stableFrames < 1 && frame < 30) {
                window.requestAnimationFrame(settle);
                return;
            }

            scrollTargetIntoView(scrollBody, scrollTarget);
        };

        window.requestAnimationFrame(settle);
    }

    function bindToggle() {
        toggleButtons = Array.from(document.querySelectorAll('[data-hm-sidebar-toggle], #hmSidebarToggle'));

        if (!toggleButtons.length) {
            return;
        }

        bindCollapsedInteractions();

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

        toggleButtons.forEach(function (button) {
            if (button.dataset.hmSidebarBound === 'true') return;
            button.dataset.hmSidebarBound = 'true';

            button.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                if (window.innerWidth < 1200) {
                    hoverExpanded = false;
                    document.body.classList.toggle('hm-sidebar-mobile-open');
                    updateToggleUi();
                    return;
                }

                handleDesktopToggle();
            });

            button.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter' && event.key !== ' ') return;
                event.preventDefault();
                hoverExpanded = false;
                if (window.innerWidth < 1200) {
                    document.body.classList.toggle('hm-sidebar-mobile-open');
                    updateToggleUi();
                    return;
                }
                handleDesktopToggle();
            });
        });

        if (!resizeBound) {
            window.addEventListener('resize', function() {
                if (window.innerWidth < 1200) {
                    // The icon rail is desktop-only. On phones/tablets the
                    // sidebar must return to its normal off-canvas drawer so
                    // it cannot squeeze or cover the page content.
                    applyCollapsedState(false, false);
                } else {
                    applyCollapsedState(!isPinned(), false);
                }

                if (window.innerWidth >= 1200 && document.body.classList.contains('hm-sidebar-mobile-open')) {
                    document.body.classList.remove('hm-sidebar-mobile-open');
                }
            });
            resizeBound = true;
        }

        updateToggleUi();
    }

    function init() {
        sidebar = document.querySelector('.sidebar.sidebar-default');
        // Desktop navigation uses a hover-expanding rail.  Do not let an old
        // "expanded" session value disable that behaviour after a deploy or
        // a page transition; mobile keeps its explicit open/close state.
        applyCollapsedState(window.innerWidth >= 1200 && !isPinned(), false);
        bindToggle();
        bindHoverExpansion();
        revealActiveItem();

        // The head has already selected the final responsive state before
        // first paint. Once the body class mirrors it, transition suppression
        // is no longer needed and can be removed synchronously.
        document.documentElement.classList.remove('hm-sidebar-preload');
    }

    window.hmSidebarToggleInit = init;
    window.hmSidebarRevealActive = revealActiveItem;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
