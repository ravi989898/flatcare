// Re-opens the "Start free trial" modal after the form was submitted, so the
// visitor sees the validation errors or the thank-you message. It lives in
// an external file because the CSP has no 'unsafe-inline'; the page decides
// whether to open it by emitting data-open="1" on this script's own tag.
(function () {
    'use strict';

    var script = document.currentScript;

    if (!script || script.getAttribute('data-open') !== '1') {
        return;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('trialInquiryModal');

        if (modal && window.bootstrap) {
            new window.bootstrap.Modal(modal).show();
        }
    });
}());
