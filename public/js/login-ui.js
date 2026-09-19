// Show/hide password on the sign-in pages. External file: the CSP forbids inline JS.
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = document.getElementById(button.getAttribute('data-toggle-password'));
            var icon = button.querySelector('i');

            if (!input) {
                return;
            }

            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');

            if (icon) {
                icon.className = show ? 'bi bi-eye' : 'bi bi-eye-slash';
            }
        });
    });
});
