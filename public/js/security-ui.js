/**
 * Global unobtrusive-JS helpers that replace inline event-handler attributes.
 *
 * The app's Content-Security-Policy has no 'unsafe-inline' on script-src, so
 * an inline `onclick="…"` / `onsubmit="…"` attribute is silently ignored by
 * the browser — a "return confirm(...)" handler would simply never run and
 * the destructive action would go through without asking. It is also unsafe
 * to interpolate server data into a JS string inside an attribute. Templates
 * use `data-confirm="Message"` instead (the message is a plain, HTML-escaped
 * attribute value) and this file asks before the action proceeds.
 *
 *   <form data-confirm="Delete this?" ...>            asks on submit
 *   <button type="submit" data-confirm="Sure?" ...>   asks on click
 *   <a href="..." data-confirm="Sure?">               asks on click
 *
 * Delegated listeners in the capture phase, so it also covers buttons added
 * later and cannot be bypassed by another handler on the same element.
 */
(function () {
    'use strict';

    document.addEventListener('click', function (event) {
        var el = event.target.closest ? event.target.closest('button[data-confirm], a[data-confirm], input[type="submit"][data-confirm]') : null;

        if (el && !window.confirm(el.getAttribute('data-confirm'))) {
            event.preventDefault();
            event.stopPropagation();
        }
    }, true);

    document.addEventListener('submit', function (event) {
        var form = event.target;

        if (form && form.matches && form.matches('form[data-confirm]') && !window.confirm(form.getAttribute('data-confirm'))) {
            event.preventDefault();
            event.stopPropagation();
        }
    }, true);
}());
