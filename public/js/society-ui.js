/**
 * Small unobtrusive-JS helpers for the Society portal.
 *
 * The app's CSP (see App\Http\Middleware\SecurityHeaders) has no
 * 'unsafe-inline' on script-src, so inline onchange="…"/onclick="…"
 * attributes are silently blocked by the browser. This file replaces them
 * with data-attribute-driven listeners instead — same convention as
 * public/js/admin-dashboard-charts.js.
 */
document.addEventListener('DOMContentLoaded', function () {
    // <input class="js-auto-submit" ...> submits its form when it changes
    // (e.g. the water-readings billing-month picker).
    document.querySelectorAll('.js-auto-submit').forEach(function (el) {
        el.addEventListener('change', function () {
            el.form.submit();
        });
    });

    // Confirmation prompts (data-confirm) are handled globally by
    // public/js/security-ui.js, which every AdminLTE page loads.

    // <button data-action="print" ...> triggers the browser print dialog
    // (invoice/receipt pages use this for their "Print / Save as PDF" button).
    document.querySelectorAll('[data-action="print"]').forEach(function (el) {
        el.addEventListener('click', function () {
            window.print();
        });
    });

    // <button data-add-option-to="options-wrap" ...> appends another
    // "options[]" text input to the target container (poll-creation form).
    document.querySelectorAll('[data-add-option-to]').forEach(function (el) {
        el.addEventListener('click', function () {
            var wrap = document.getElementById(el.getAttribute('data-add-option-to'));

            if (!wrap) {
                return;
            }

            var row = document.createElement('div');
            row.className = 'input-group mb-2';
            row.innerHTML = '<input type="text" name="options[]" class="form-control" placeholder="Option label" maxlength="255" required>';
            wrap.appendChild(row);
        });
    });
});
