(function () {
    var loginForm = document.getElementById('hmLoginForm');
    var overlay = document.getElementById('hmAuthVerifyOverlay');
    var passwordInput = document.getElementById('password');
    var passwordToggle = document.getElementById('hmPasswordToggle');
    var usernameInput = document.getElementById('username');
    var usernameLabel = document.getElementById('usernameLabel');
    var modeRadios = document.querySelectorAll('input[name="login_mode"]');

    if (usernameInput && usernameLabel && modeRadios.length) {
        modeRadios.forEach(function (radio) {
            radio.addEventListener('change', function () {
                var isMobile = radio.value === 'mobile' && radio.checked;

                if (!radio.checked) {
                    return;
                }

                if (isMobile) {
                    usernameInput.type = 'tel';
                    usernameInput.inputMode = 'tel';
                    usernameInput.autocomplete = 'tel';
                    usernameInput.placeholder = usernameInput.dataset.placeholderMobile || '';
                    usernameLabel.textContent = usernameInput.dataset.labelMobile || '';
                } else {
                    usernameInput.type = 'text';
                    usernameInput.inputMode = 'text';
                    usernameInput.autocomplete = 'username';
                    usernameInput.placeholder = usernameInput.dataset.placeholderIdentifier || '';
                    usernameLabel.textContent = usernameInput.dataset.labelIdentifier || '';
                }

                usernameInput.value = '';
                usernameInput.focus();
            });
        });

        usernameInput.addEventListener('input', function () {
            var mobileRadio = document.getElementById('loginModeMobile');
            if (mobileRadio && mobileRadio.checked) {
                usernameInput.value = usernameInput.value.replace(/[^\d+]/g, '');
            }
        });
    }

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
