/**
 * Listing pages (resources/views/components/fc): filter selects and the
 * rows-per-page picker submit their form as soon as they change, and the
 * search box submits shortly after the user stops typing, then gets its
 * focus back on the reloaded page. External file because the CSP blocks
 * inline scripts. Loaded on every AdminLTE page (config adminlte.plugins).
 */
document.addEventListener('DOMContentLoaded', function () {
    var REFOCUS_KEY = 'fc-refocus';

    document.querySelectorAll('form.js-fc-filter').forEach(function (form) {
        form.querySelectorAll('select, input[type="date"], input[type="month"]').forEach(function (el) {
            el.addEventListener('change', function () {
                submit(form);
            });
        });

        var search = form.querySelector('input[data-fc-search]');
        if (!search) {
            return;
        }

        var timer = null;
        var last = search.value;
        search.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () {
                if (search.value.trim() === last.trim()) {
                    return;
                }
                try { sessionStorage.setItem(REFOCUS_KEY, search.name); } catch (e) {}
                submit(form);
            }, 600);
        });
    });

    // Put the caret back in the search box the user was typing in.
    try {
        var name = sessionStorage.getItem(REFOCUS_KEY);
        if (name) {
            sessionStorage.removeItem(REFOCUS_KEY);
            var input = document.querySelector('form.js-fc-filter input[data-fc-search][name="' + name + '"]');
            if (input) {
                input.focus();
                var end = input.value.length;
                input.setSelectionRange(end, end);
            }
        }
    } catch (e) {}

    function submit(form) {
        // Any filter change starts again from page 1.
        var page = form.querySelector('input[name="page"]');
        if (page) {
            page.remove();
        }
        form.submit();
    }
});
