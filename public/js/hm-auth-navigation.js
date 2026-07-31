(function () {
    'use strict';

    if (!document.body.classList.contains('hm-auth-body')) {
        return;
    }

    window.addEventListener('pageshow', function (event) {
        if (!event.persisted) {
            return;
        }

        var path = window.location.pathname;

        if (path === '/otp' || path === '/otp/' || path === '/' || path === '/login') {
            window.location.reload();
        }
    });
})();
