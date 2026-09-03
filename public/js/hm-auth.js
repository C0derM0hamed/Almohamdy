(function () {
    var loginForm = document.getElementById('hmLoginForm');
    var overlay = document.getElementById('hmAuthVerifyOverlay');
    var passwordInput = document.getElementById('password');
    var passwordToggle = document.getElementById('hmPasswordToggle');
    var usernameInput = document.getElementById('username');

    if (passwordToggle && passwordInput) {
        passwordToggle.addEventListener('click', function () {
            var isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            var icon = passwordToggle.querySelector('i');
            if (icon) {
                icon.classList.toggle('bi-eye', !isPassword);
                icon.classList.toggle('bi-eye-slash', isPassword);
            }
        });
    }

    if (!loginForm || !overlay) {
        return;
    }

    loginForm.addEventListener('submit', function () {
        overlay.hidden = false;
        overlay.setAttribute('aria-hidden', 'false');
    });
})();
