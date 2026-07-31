(function () {
    'use strict';

    if (!document.body.classList.contains('hm-app-body')) {
        return;
    }

    window.addEventListener('pageshow', function (event) {
        if (!event.persisted) {
            return;
        }

        var path = window.location.pathname;

        if (path === '/otp' || path === '/otp/') {
            window.location.replace(document.querySelector('[data-hm-home-url]')?.dataset.hmHomeUrl || '/dashboard');
        }
    });
})();
