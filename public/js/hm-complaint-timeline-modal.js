(function () {
    'use strict';

    var MODAL_ID = 'cpComplaintTimelineModal';
    var OPEN_SELECTOR = '[data-cp-timeline-open]';
    var CLOSE_SELECTOR = '[data-cp-timeline-close]';
    var BODY_OPEN_CLASS = 'cp-timeline-modal-open';

    function getModal() {
        return document.getElementById(MODAL_ID);
    }

    function isOpen() {
        var modalEl = getModal();

        return modalEl !== null && !modalEl.hidden;
    }

    function ensureModalInBody(modalEl) {
        if (modalEl && modalEl.parentElement !== document.body) {
            document.body.appendChild(modalEl);
        }
    }

    function showModal() {
        var modalEl = getModal();

        if (!modalEl) {
            return;
        }

        ensureModalInBody(modalEl);
        modalEl.hidden = false;
        modalEl.setAttribute('aria-hidden', 'false');
        document.body.classList.add(BODY_OPEN_CLASS);
    }

    function hideModal() {
        var modalEl = getModal();

        if (!modalEl) {
            return;
        }

        modalEl.hidden = true;
        modalEl.setAttribute('aria-hidden', 'true');
        document.body.classList.remove(BODY_OPEN_CLASS);

        if (window.location.search.indexOf('timeline=1') !== -1) {
            var url = new URL(window.location.href);
            url.searchParams.delete('timeline');
            window.history.replaceState({}, '', url.pathname + url.search + url.hash);
        }
    }

    function shouldOpenFromQuery() {
        return new URLSearchParams(window.location.search).get('timeline') === '1';
    }

    function initComplaintTimelineModal() {
        var modalEl = getModal();

        if (!modalEl) {
            return;
        }

        ensureModalInBody(modalEl);

        document.addEventListener('click', function (event) {
            var target = event.target instanceof Element
                ? event.target
                : (event.target && event.target.parentElement instanceof Element ? event.target.parentElement : null);

            if (!target) {
                return;
            }

            if (target.closest(OPEN_SELECTOR)) {
                event.preventDefault();
                showModal();
                return;
            }

            if (isOpen() && target.closest(CLOSE_SELECTOR)) {
                event.preventDefault();
                hideModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && isOpen()) {
                event.preventDefault();
                hideModal();
            }
        });

        if (shouldOpenFromQuery()) {
            showModal();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initComplaintTimelineModal);
    } else {
        initComplaintTimelineModal();
    }
})();
