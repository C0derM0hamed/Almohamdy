(function () {
    'use strict';

    var LEAVE_MS = 280;
    var NAV_FLAG = 'hm-page-nav';
    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var navigationTimer = null;
    var navigationSequence = 0;
    var activeRequest = null;

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

        // Changing locale changes the whole document: translations, text
        // direction, RTL stylesheet and the server-built sidebar. It must not
        // use the partial-page navigator, otherwise only part of the UI gets
        // refreshed and users need a manual browser refresh.
        if (/^\/lang\/(ar|en)\/?(?:\?.*)?$/.test(href)) {
            return true;
        }

        if (
            link.hasAttribute('download')
            || link.target === '_blank'
            || link.dataset.noTransition === 'true'
            || (
                link.hasAttribute('data-sidebar-brand')
                && (
                    document.body.classList.contains('hm-sidebar-collapsed')
                    || document.documentElement.classList.contains('hm-sidebar-is-collapsed')
                )
            )
        ) {
            return true;
        }

        // Modal / AJAX openers and file downloads must not trigger full-page navigation.
        if (
            link.hasAttribute('data-inq-timeline-modal')
            || link.hasAttribute('data-medical-agreement-timeline')
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

    function isGlobalScript(script, source) {
        if (!source) {
            return script.textContent.indexOf('hideHopeLoader') !== -1;
        }

        var path;

        try {
            path = new URL(source, window.location.href).pathname;
        } catch (error) {
            return true;
        }

        return path === '/assets/js/hope-ui.js'
            || path === '/js/hm-sidebar-toggle.js'
            || path === '/js/hm-number-boxes.js'
            || path === '/js/hm-page-transitions.js'
            || path === '/js/hm-app-navigation.js';
    }

    function isJavaScript(script) {
        var type = (script.getAttribute('type') || '').toLowerCase();

        return !type || type === 'text/javascript' || type === 'application/javascript';
    }

    function loadPageScript(script, baseUrl) {
        var source = script.getAttribute('src') || '';

        if (!isJavaScript(script) || isGlobalScript(script, source)) {
            return Promise.resolve();
        }

        if (source) {
            return new Promise(function (resolve) {
                var replacement = document.createElement('script');

                Array.prototype.forEach.call(script.attributes, function (attribute) {
                    if (attribute.name !== 'src') {
                        replacement.setAttribute(attribute.name, attribute.value);
                    }
                });

                replacement.src = new URL(source, baseUrl).href;
                replacement.async = false;
                replacement.onload = resolve;
                replacement.onerror = resolve;
                document.body.appendChild(replacement);
            });
        }

        // Scripts pushed by Blade pages often wait for DOMContentLoaded. During
        // an in-page navigation that event has already fired, so give them a
        // navigation-specific event instead.
        var code = script.textContent.replace(
            /(document|window)\.addEventListener\(\s*(['"])DOMContentLoaded\2/g,
            '$1.addEventListener($2hm:page-loaded$2'
        );
        var replacement = document.createElement('script');
        replacement.textContent = code;
        document.body.appendChild(replacement);
        replacement.remove();

        return Promise.resolve();
    }

    function runPageScripts(nextDocument, baseUrl) {
        var scripts = Array.prototype.slice.call(nextDocument.body.querySelectorAll('script'));

        return scripts.reduce(function (chain, script) {
            return chain.then(function () {
                return loadPageScript(script, baseUrl);
            });
        }, Promise.resolve()).then(function () {
            document.dispatchEvent(new CustomEvent('hm:page-loaded'));
        });
    }

    function syncPageStyles(nextDocument) {
        var currentStyles = new Set(Array.prototype.map.call(
            document.head.querySelectorAll('link[rel="stylesheet"]'),
            function (link) { return link.href; }
        ));

        nextDocument.head.querySelectorAll('link[rel="stylesheet"]').forEach(function (link) {
            if (!link.href || currentStyles.has(link.href)) {
                return;
            }

            var replacement = link.cloneNode(true);
            replacement.dataset.hmSpaStyle = 'true';
            document.head.appendChild(replacement);
            currentStyles.add(link.href);
        });

        nextDocument.head.querySelectorAll('style').forEach(function (style) {
            var replacement = style.cloneNode(true);
            replacement.dataset.hmSpaStyle = 'true';
            document.head.appendChild(replacement);
        });
    }

    function syncSidebar(nextDocument) {
        var currentSidebar = document.querySelector('#hmAppSidebar');
        var nextSidebar = nextDocument.querySelector('#hmAppSidebar');

        if (!currentSidebar || !nextSidebar) {
            return;
        }

        // The server navigation service owns route-name/prefix matching.
        // Copy its resolved state instead of reducing nested routes to exact
        // URL equality during an in-app page transition.
        var nextActiveHrefs = new Set(
            Array.from(nextSidebar.querySelectorAll('a.nav-link.active[href]'))
                .map(function (link) { return link.getAttribute('href'); })
                .filter(Boolean)
        );

        currentSidebar.querySelectorAll('a.nav-link[href]').forEach(function (link) {
            var active = nextActiveHrefs.has(link.getAttribute('href'));
            link.classList.toggle('active', active);

            if (active && link.getAttribute('href').charAt(0) !== '#') {
                link.setAttribute('aria-current', 'page');
            } else {
                link.removeAttribute('aria-current');
            }
        });

        currentSidebar.querySelectorAll('.sub-nav[id]').forEach(function (group) {
            var nextGroup = nextSidebar.querySelector('#' + CSS.escape(group.id));
            var open = Boolean(nextGroup && nextGroup.classList.contains('show'));
            group.classList.toggle('show', open);
            var toggle = currentSidebar.querySelector('[aria-controls="' + group.id + '"]');

            if (toggle) {
                toggle.classList.toggle('active', open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            }
        });

        if (typeof window.hmSidebarRevealActive === 'function') {
            window.hmSidebarRevealActive();
        }
    }

    function swapPage(nextDocument, url) {
        var currentMain = document.querySelector('main.main-content');
        var nextMain = nextDocument.querySelector('main.main-content');

        if (!currentMain || !nextMain || !nextDocument.querySelector('.hm-page-root')) {
            return false;
        }

        // Keep the fixed sidebar out of the DOM-reflow window. Without this
        // guard Hope UI can paint a half-built mini rail while the main page
        // is being replaced, then leave the wheel trapped in an offcanvas
        // state on the next screen.
        document.documentElement.classList.add('hm-sidebar-preload');
        document.body.classList.remove('hm-sidebar-mobile-open', 'offcanvas-active');

        syncPageStyles(nextDocument);
        currentMain.replaceWith(nextMain);
        document.title = nextDocument.title;
        document.documentElement.lang = nextDocument.documentElement.lang;
        document.documentElement.dir = nextDocument.documentElement.dir;
        document.body.dataset.hmHomeUrl = nextDocument.body.dataset.hmHomeUrl || document.body.dataset.hmHomeUrl || '';
        syncSidebar(nextDocument);

        if (typeof window.hmSidebarToggleInit === 'function') {
            window.hmSidebarToggleInit();
        }

        return true;
    }

    function fetchPage(url, addHistory, sequence) {
        if (activeRequest) {
            activeRequest.abort();
        }

        var controller = new AbortController();
        activeRequest = controller;

        return fetch(url, {
            credentials: 'same-origin',
            signal: controller.signal,
            headers: {
                Accept: 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Unable to load page without a full navigation.');
                }

                return response.text().then(function (html) {
                    return { html: html, url: response.url };
                });
            })
            .then(function (payload) {
                if (sequence !== navigationSequence) {
                    return;
                }

                var nextDocument = new DOMParser().parseFromString(payload.html, 'text/html');

                if (!nextDocument.body.classList.contains('hm-app-body')) {
                    throw new Error('The response is not an application page.');
                }

                var nextUrl = new URL(payload.url || url, window.location.href);

                if (nextUrl.origin !== window.location.origin) {
                    throw new Error('The response left the application origin.');
                }

                if (!swapPage(nextDocument, nextUrl)) {
                    throw new Error('The response does not contain the application shell.');
                }

                if (addHistory) {
                    window.history.pushState({ hmPageNavigation: true }, '', nextUrl.href);
                }

                return runPageScripts(nextDocument, nextUrl).then(function () {
                    applyStaggeredReveal();
                    clearNavigating();
                    document.documentElement.classList.remove('hm-page-instant');
                    document.body.classList.remove('hm-page-leaving');
                    document.body.classList.remove('hm-page-enter-active');
                    window.scrollTo(0, 0);

                    window.requestAnimationFrame(function () {
                        document.body.classList.add('hm-page-enter-active');
                    });
                });
            })
            .finally(function () {
                if (activeRequest === controller) {
                    activeRequest = null;
                }
            });
    }

    function navigateWithTransition(url, addHistory) {
        var sequence = ++navigationSequence;
        markNavigating();

        if (navigationTimer) {
            window.clearTimeout(navigationTimer);
        }

        ensureOverlay();
        document.body.classList.add('hm-page-leaving');
        document.body.classList.remove('hm-page-enter-active');

        var startLoading = function () {
            fetchPage(url, addHistory, sequence).catch(function (error) {
                if (error && error.name === 'AbortError') {
                    return;
                }

                window.location.href = url;
            });
        };

        if (reducedMotion) {
            startLoading();
            return;
        }

        navigationTimer = window.setTimeout(startLoading, LEAVE_MS);
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

    window.addEventListener('popstate', function () {
        navigateWithTransition(window.location.href, false);
    });

    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[href]');

        if (shouldSkipLink(link, event)) {
            return;
        }

        event.preventDefault();
        navigateWithTransition(link.href, true);
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
