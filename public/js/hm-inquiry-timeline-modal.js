(function () {
    'use strict';

    var MODAL_ID = 'inqTimelineModal';
    var OPEN_SELECTOR = '[data-inq-timeline-modal]';
    var BODY_ID = 'inqTimelineModalBody';
    var SUBTITLE_ID = 'inqTimelineModalSubtitle';

    function getModalEl() {
        return document.getElementById(MODAL_ID);
    }

    function getBodyEl() {
        return document.getElementById(BODY_ID);
    }

    function ensureModalInBody(modalEl) {
        if (modalEl && modalEl.parentElement !== document.body) {
            document.body.appendChild(modalEl);
        }
    }

    function setLoading(bodyEl) {
        if (!bodyEl) {
            return;
        }

        bodyEl.innerHTML =
            '<div class="inq-timeline-modal-state" role="status">' +
            '<span class="spinner-border spinner-border-sm text-primary" aria-hidden="true"></span>' +
            '<span>' + (bodyEl.getAttribute('data-loading-label') || 'Loading…') + '</span>' +
            '</div>';
    }

    function setError(bodyEl) {
        if (!bodyEl) {
            return;
        }

        bodyEl.innerHTML =
            '<div class="inq-timeline-modal-state inq-timeline-modal-state--error" role="alert">' +
            '<i class="bi bi-exclamation-triangle" aria-hidden="true"></i>' +
            '<span>' + (bodyEl.getAttribute('data-error-label') || 'Unable to load timeline.') + '</span>' +
            '</div>';
    }

    function openTimelineModal(trigger) {
        var modalEl = getModalEl();
        var bodyEl = getBodyEl();
        var subtitleEl = document.getElementById(SUBTITLE_ID);
        var url = trigger.getAttribute('data-inq-timeline-url') || trigger.getAttribute('href');

        if (!modalEl || !bodyEl || !url) {
            return;
        }

        if (!window.bootstrap || !bootstrap.Modal) {
            window.location.href = url;
            return;
        }

        ensureModalInBody(modalEl);

        if (subtitleEl) {
            subtitleEl.textContent = trigger.getAttribute('data-inq-timeline-subtitle') || '';
        }

        setLoading(bodyEl);

        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();

        var fetchUrl = url + (url.indexOf('?') === -1 ? '?' : '&') + 'modal=1';

        fetch(fetchUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'text/html',
            },
            credentials: 'same-origin',
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Failed to load timeline');
                }

                return response.text();
            })
            .then(function (html) {
                bodyEl.innerHTML = html;
            })
            .catch(function () {
                setError(bodyEl);
            });
    }

    function init() {
        var modalEl = getModalEl();

        if (!modalEl) {
            return;
        }

        ensureModalInBody(modalEl);

        document.addEventListener('click', function (event) {
            var target = event.target instanceof Element
                ? event.target
                : (event.target && event.target.parentElement instanceof Element
                    ? event.target.parentElement
                    : null);

            if (!target) {
                return;
            }

            var trigger = target.closest(OPEN_SELECTOR);

            if (!trigger) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            openTimelineModal(trigger);
        }, true);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
