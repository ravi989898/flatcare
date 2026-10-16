/**
 * Filters a <select> down to just the options matching the chosen value of
 * another "driver" <select>, without an AJAX round trip — every option is
 * already in the page, each carries a data attribute naming the driver's
 * option it belongs under. Unobtrusive/data-attribute driven for the same
 * CSP reason as public/js/society-ui.js (no inline onchange handlers): the
 * target select declares its driver via data-filtered-by="<id of driver
 * select>", and optionally which attribute to filter on via
 * data-filter-attr="<attr>" (defaults to "data-block-id", e.g. Flat
 * filtered by Block). Chains of more than one level (e.g. Block -> Flat ->
 * User, see society.admins.create) work the same way — each select only
 * needs to know its immediate driver.
 */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('select[data-filtered-by]').forEach(function (targetSelect) {
        var driverSelect = document.getElementById(targetSelect.getAttribute('data-filtered-by'));
        var filterAttr = targetSelect.getAttribute('data-filter-attr') || 'data-block-id';

        if (!driverSelect) {
            return;
        }

        var allOptions = Array.prototype.slice.call(targetSelect.querySelectorAll('option[' + filterAttr + ']'));
        var placeholder = targetSelect.querySelector('option:not([' + filterAttr + '])');

        function applyFilter() {
            var driverValue = driverSelect.value;
            var currentValue = targetSelect.value;
            var currentStillVisible = false;

            allOptions.forEach(function (option) {
                var matches = !driverValue || option.getAttribute(filterAttr) === driverValue;
                option.hidden = !matches;
                option.disabled = !matches;

                if (matches && option.value === currentValue) {
                    currentStillVisible = true;
                }
            });

            if (!currentStillVisible) {
                targetSelect.value = placeholder ? placeholder.value : '';
                // Propagate to any select chained off this one (e.g. a User
                // select filtered by this Flat select) even though this was
                // a programmatic reset, not a real user interaction.
                targetSelect.dispatchEvent(new Event('change'));
            }
        }

        driverSelect.addEventListener('change', applyFilter);

        // On initial load (e.g. after a validation error round-trip that
        // re-selects an option via old()), pre-select the matching driver
        // value so the list isn't filtered down to nothing.
        var preselected = targetSelect.value
            ? targetSelect.querySelector('option[value="' + targetSelect.value + '"]')
            : null;

        if (preselected) {
            driverSelect.value = preselected.getAttribute(filterAttr);
        }

        applyFilter();
    });

    // Copies a data attribute off the chosen <option> into another field,
    // e.g. filling a resident's Phone input from the selected flat's
    // mobile_number (see admin.societies.users.create/edit). Only fires on
    // an explicit change, so it never touches a phone number already saved
    // when the page first loads - but once the user does pick a different
    // flat, the field is fully replaced (cleared if that flat has no
    // mobile_number) rather than left stale from the previous selection.
    document.querySelectorAll('select[data-autofill-target]').forEach(function (sourceSelect) {
        var targetInput = document.getElementById(sourceSelect.getAttribute('data-autofill-target'));
        var attr = sourceSelect.getAttribute('data-autofill-attr');

        if (!targetInput || !attr) {
            return;
        }

        sourceSelect.addEventListener('change', function () {
            var selectedOption = sourceSelect.options[sourceSelect.selectedIndex];
            targetInput.value = selectedOption ? (selectedOption.getAttribute(attr) || '') : '';
        });
    });
});
