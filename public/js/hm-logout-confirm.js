(function () {
    'use strict';

    function initLogoutConfirmation() {
        var form = document.querySelector('[data-hm-logout-form]');
        var dialog = document.querySelector('[data-hm-logout-dialog]');

        if (!form || !dialog) {
            return;
        }

        var submitButton = dialog.querySelector('[data-hm-logout-submit]');
        var cancelButtons = dialog.querySelectorAll('[data-hm-logout-cancel]');
        var lastFocusedElement = null;
        var isSubmitting = false;
        var closeTimer = null;

        function isOpen() {
            return dialog.classList.contains('is-visible');
        }

        function openDialog(event) {
            if (event) {
                event.preventDefault();
            }

            if (isSubmitting || isOpen()) {
                return;
            }

            lastFocusedElement = document.activeElement;
            window.clearTimeout(closeTimer);
            dialog.hidden = false;
            document.body.classList.add('hm-logout-confirm-open');

            window.requestAnimationFrame(function () {
                dialog.classList.add('is-visible');
                submitButton.focus();
            });
        }

        function closeDialog() {
            if (!isOpen()) {
                return;
            }

            dialog.classList.remove('is-visible');
            document.body.classList.remove('hm-logout-confirm-open');
            closeTimer = window.setTimeout(function () {
                dialog.hidden = true;
            }, 220);

            if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
                lastFocusedElement.focus();
            }
        }

        form.addEventListener('submit', openDialog);

        cancelButtons.forEach(function (button) {
            button.addEventListener('click', closeDialog);
        });

        submitButton.addEventListener('click', function () {
            if (isSubmitting) {
                return;
            }

            isSubmitting = true;
            submitButton.disabled = true;
            form.submit();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && isOpen()) {
                event.preventDefault();
                closeDialog();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLogoutConfirmation);
    } else {
        initLogoutConfirmation();
    }
})();
