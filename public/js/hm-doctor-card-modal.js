(function () {
    'use strict';

    var MODAL_ID = 'hmDoctorDetailModal';
    var BODY_ID = 'hmDoctorDetailModalBody';
    var BODY_OPEN_CLASS = 'hm-clinician-modal-open';
    var CLOSE_SELECTOR = '[data-hm-clinician-modal-close], .hm-clinician-popup-card__close-btn';

    function resolveClickTarget(event) {
        var target = event.target;

        if (target instanceof Element) {
            return target;
        }

        if (target && target.parentElement instanceof Element) {
            return target.parentElement;
        }

        return null;
    }

    function getModal() {
        return document.getElementById(MODAL_ID);
    }

    function getBody() {
        return document.getElementById(BODY_ID);
    }

    function ensureModalInBody(modalEl) {
        if (modalEl && modalEl.parentElement !== document.body) {
            document.body.appendChild(modalEl);
        }
    }

    function isOpen() {
        var modalEl = getModal();

        return modalEl !== null && !modalEl.hidden;
    }

    function bindCloseButtons(root) {
        root.querySelectorAll(CLOSE_SELECTOR).forEach(function (button) {
            if (button.dataset.hmModalCloseBound === '1') {
                return;
            }

            button.dataset.hmModalCloseBound = '1';
            button.addEventListener('click', function (event) {
                event.preventDefault();
                hideModal();
            });
        });
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
        var bodyEl = getBody();

        if (!modalEl) {
            return;
        }

        modalEl.hidden = true;
        modalEl.setAttribute('aria-hidden', 'true');
        document.body.classList.remove(BODY_OPEN_CLASS);

        if (bodyEl) {
            bodyEl.innerHTML = '';
        }
    }

    function buildEmptyPopup(emptyMessage, closeLabel, title) {
        var modalTitle = title || closeLabel;

        return (
            '<article class="hm-clinician-popup-card hm-clinician-popup-card--detail">' +
                '<div class="hm-clinician-popup-card__accent" aria-hidden="true"></div>' +
                '<header class="hm-hope-modal__header hm-clinician-popup-card__head">' +
                    '<div class="hm-hope-modal__header-main hm-clinician-popup-card__head-copy">' +
                        '<h3 class="hm-hope-modal__title hm-clinician-popup-card__title">' + modalTitle + '</h3>' +
                    '</div>' +
                    '<button type="button" class="hm-hope-modal__close hm-clinician-popup-card__close-icon" data-hm-clinician-modal-close aria-label="' + closeLabel + '">' +
                        '<i class="bi bi-x-lg" aria-hidden="true"></i>' +
                    '</button>' +
                '</header>' +
                '<div class="hm-clinician-popup-card__body">' +
                    '<p class="hm-clinician-modal__empty">' + emptyMessage + '</p>' +
                '</div>' +
                '<footer class="hm-hope-modal__footer hm-clinician-popup-card__footer">' +
                    '<button type="button" class="btn btn-primary hm-clinician-popup-card__close-btn" data-hm-clinician-modal-close>' +
                        closeLabel +
                    '</button>' +
                '</footer>' +
            '</article>'
        );
    }

    function openDoctorModal(button) {
        var modalEl = getModal();
        var bodyEl = getBody();

        if (!modalEl || !bodyEl) {
            return;
        }

        var targetId = button.getAttribute('data-modal-target');
        var source = targetId ? document.getElementById(targetId) : null;

        if (!source) {
            return;
        }

        var content = source.innerHTML.trim();
        var emptyMessage = button.getAttribute('data-modal-empty') || '';
        var closeLabel = button.getAttribute('data-modal-close-label') || 'Close';
        var modalTitle = button.getAttribute('data-modal-title') || '';

        bodyEl.innerHTML = content !== '' ? content : buildEmptyPopup(emptyMessage, closeLabel, modalTitle);

        var closeButtons = bodyEl.querySelectorAll('.hm-clinician-popup-card__close-btn');
        closeButtons.forEach(function (closeButton) {
            if (!closeButton.textContent.trim()) {
                closeButton.textContent = closeLabel;
            }
        });

        bindCloseButtons(bodyEl);
        showModal();
    }

    function initDoctorCardModals() {
        var modalEl = getModal();
        var bodyEl = getBody();

        if (!modalEl || !bodyEl) {
            return;
        }

        ensureModalInBody(modalEl);

        document.addEventListener('click', function (event) {
            var clickTarget = resolveClickTarget(event);

            if (!clickTarget) {
                return;
            }

            var trigger = clickTarget.closest('[data-hm-doctor-modal]');

            if (trigger) {
                event.preventDefault();
                event.stopPropagation();
                openDoctorModal(trigger);
                return;
            }

            if (isOpen() && clickTarget.closest('[data-hm-clinician-modal-close]')) {
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
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDoctorCardModals);
    } else {
        initDoctorCardModals();
    }
})();
