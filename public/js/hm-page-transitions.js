(function () {
    'use strict';

    var LEAVE_MS = 280;
    var NAV_FLAG = 'hm-page-nav';
    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function ensureOverlay() {
        var overlay = document.querySelector('.hm-page-overlay');

        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'hm-page-overlay';
            overlay.setAttribute('aria-hidden', 'true');
            overlay.innerHTML =
                '<span class="hm-page-overlay__veil" aria-hidden="true"></span>' +
                '<span class="hm-page-overlay__bar" aria-hidden="true"></span>';
            document.body.appendChild(overlay);
        }

        return overlay;
    }

    function shouldSkipLink(link, event) {
        if (document.body.classList.contains('hm-auth-body')) {
            return true;
        }

        if (!link || link.tagName !== 'A') {
            return true;
        }

        var href = link.getAttribute('href');

        if (!href || href === '#' || href.indexOf('javascript:') === 0) {
            return true;
        }

        if (link.hasAttribute('download') || link.target === '_blank' || link.dataset.noTransition === 'true') {
            return true;
        }

        // Modal / AJAX openers and file downloads must not trigger full-page navigation.
        if (
            link.hasAttribute('data-inq-timeline-modal')
            || link.hasAttribute('data-inq-status-modal')
            || link.classList.contains('inq-pdf-link')
            || link.hasAttribute('data-gc-receipt-modal')
            || link.hasAttribute('data-hm-doctor-modal')
            || link.hasAttribute('data-cp-timeline-open')
        ) {
            return true;
        }

        if (event && (
            event.defaultPrevented
            || event.button !== 0
            || event.metaKey
            || event.ctrlKey
            || event.shiftKey
            || event.altKey
        )) {
            return true;
        }

        if (href.charAt(0) === '#') {
            return true;
        }

        var url;

        try {
            url = new URL(link.href, window.location.href);
        } catch (error) {
            return true;
        }

        if (url.origin !== window.location.origin) {
            return true;
        }

        return url.pathname === window.location.pathname && url.search === window.location.search;
    }

    function markNavigating() {
        try {
            sessionStorage.setItem(NAV_FLAG, '1');
        } catch (error) {
            /* ignore */
        }
    }

    function wasNavigating() {
        try {
            return sessionStorage.getItem(NAV_FLAG) === '1';
        } catch (error) {
            return false;
        }
    }

    function clearNavigating() {
        try {
            sessionStorage.removeItem(NAV_FLAG);
        } catch (error) {
            /* ignore */
        }
    }

    function resetTransitionState(showImmediately) {
        document.body.classList.remove('hm-page-leaving');

        if (showImmediately) {
            document.documentElement.classList.add('hm-page-instant');
            document.body.classList.add('hm-page-enter-active');
            clearNavigating();
            return;
        }

        document.body.classList.add('hm-page-enter-active');
    }

    function navigateWithTransition(url) {
        if (reducedMotion) {
            markNavigating();
            window.location.href = url;
            return;
        }

        markNavigating();
        ensureOverlay();
        document.body.classList.add('hm-page-leaving');
        document.body.classList.remove('hm-page-enter-active');

        window.setTimeout(function () {
            window.location.href = url;
        }, LEAVE_MS);
    }

    function staggerSelectorList() {
        return [
            '.hm-hope-dashboard .hm-hope-welcome',
            '.hm-hope-dashboard .row.g-3 > [class*="col-"]',
            '.hm-wan .wan-stat',
            '.hm-wan .wan-panel',
            '.hm-wan .wan-head',
            '.hm-wan .wan-breadcrumb--bar',
            '.hm-hs .hs-dash-card',
            '.hm-hs .hs-page-hero',
            '.hm-hs .hs-filter-card',
            '.hm-hs .hs-list-panel',
            '.hm-es .es-dash-card',
            '.hm-es .hs-page-hero',
            '.hm-dd .dd-breadcrumb--bar',
            '.hm-dd .dd-panel',
            '.hm-cp .cp-breadcrumb--bar',
            '.hm-cp .cp-hero',
            '.hm-inq .inq-stat-card',
            '.hm-inq .hs-page-hero',
            '.hm-inq .hs-filter-card',
            '.hm-inq .hs-list-panel',
            '.hm-sl .sl-breadcrumb--bar',
            '.hm-sl .hs-page-hero',
            '.hm-sl .sl-card-grid > *',
            '.hm-dda .dda-breadcrumb--bar',
            '.hm-dda .dda-page-hero',
            '.hm-dda .dda-panel',
            '.hm-el .el-breadcrumb--bar',
            '.hm-el .el-page-hero',
            '.hm-el .hm-leave-stat-row > *',
        ];
    }

    function applyStaggeredReveal() {
        if (reducedMotion || !wasNavigating()) {
            return;
        }

        var seen = new Set();
        var nodes = [];

        staggerSelectorList().forEach(function (selector) {
            document.querySelectorAll(selector).forEach(function (node) {
                if (!seen.has(node)) {
                    seen.add(node);
                    nodes.push(node);
                }
            });
        });

        nodes.forEach(function (node, index) {
            node.classList.add('hm-reveal');
            node.style.setProperty('--hm-stagger', String(Math.min(index, 10)));
        });
    }

    function playEnterAnimation() {
        ensureOverlay();
        clearNavigating();

        if (reducedMotion || document.documentElement.classList.contains('hm-page-instant')) {
            resetTransitionState(true);
            return;
        }

        document.body.classList.remove('hm-page-leaving');
        document.body.classList.remove('hm-page-enter-active');

        window.requestAnimationFrame(function () {
            window.requestAnimationFrame(function () {
                document.body.classList.add('hm-page-enter-active');
            });
        });

        applyStaggeredReveal();
    }

    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[href]');

        if (shouldSkipLink(link, event)) {
            return;
        }

        event.preventDefault();
        navigateWithTransition(link.href);
    }, true);

    window.addEventListener('pagehide', function (event) {
        if (!event.persisted) {
            return;
        }

        resetTransitionState(true);
    });

    window.addEventListener('pageshow', function (event) {
        if (!event.persisted) {
            return;
        }

        resetTransitionState(true);
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', playEnterAnimation);
    } else {
        playEnterAnimation();
    }
})();
