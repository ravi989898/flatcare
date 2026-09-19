// Bootstrap 4 custom-file input doesn't show the chosen filename by default.
// (External file rather than an inline <script>: the CSP has no 'unsafe-inline'.)
document.addEventListener('DOMContentLoaded', function () {
    ['logo', 'icon'].forEach(function (id) {
        var input = document.getElementById(id);

        if (!input) {
            return;
        }

        input.addEventListener('change', function (e) {
            var label = e.target.nextElementSibling;

            if (label) {
                label.textContent = e.target.files.length ? e.target.files[0].name : 'Choose file…';
            }
        });
    });
});
