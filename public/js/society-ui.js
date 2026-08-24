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

    // <button data-confirm="Are you sure?" ...> asks before its click goes
    // through (Archive/Cancel/Remove-style destructive actions).
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!window.confirm(el.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    // <button data-action="print" ...> triggers the browser print dialog
    // (invoice/receipt pages use this for their "Print / Save as PDF" button).
    document.querySelectorAll('[data-action="print"]').forEach(function (el) {
        el.addEventListener('click', function () {
            window.print();
        });
    });
});
