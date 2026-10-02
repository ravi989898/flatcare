/**
 * Filters a "Flat" <select> down to just the flats in the chosen "Block",
 * without an AJAX round trip — every flat is already in the page, each
 * <option> just carries a data-block-id attribute. Unobtrusive/data-attribute
 * driven for the same CSP reason as public/js/society-ui.js (no inline
 * onchange handlers): the target select declares which block-select drives
 * it via data-filtered-by="<id of the block select>".
 */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('select[data-filtered-by]').forEach(function (targetSelect) {
        var blockSelect = document.getElementById(targetSelect.getAttribute('data-filtered-by'));

        if (!blockSelect) {
            return;
        }

        var allOptions = Array.prototype.slice.call(targetSelect.querySelectorAll('option[data-block-id]'));
        var placeholder = targetSelect.querySelector('option:not([data-block-id])');

        function applyFilter() {
            var blockId = blockSelect.value;
            var currentValue = targetSelect.value;
            var currentStillVisible = false;

            allOptions.forEach(function (option) {
                var matches = !blockId || option.getAttribute('data-block-id') === blockId;
                option.hidden = !matches;
                option.disabled = !matches;

                if (matches && option.value === currentValue) {
                    currentStillVisible = true;
                }
            });

            if (!currentStillVisible) {
                targetSelect.value = placeholder ? placeholder.value : '';
            }
        }

        blockSelect.addEventListener('change', applyFilter);

        // On initial load (e.g. after a validation error round-trip that
        // re-selects a flat via old()), pre-select the matching block so the
        // flat list isn't filtered down to nothing.
        var preselected = targetSelect.value
            ? targetSelect.querySelector('option[value="' + targetSelect.value + '"]')
            : null;

        if (preselected) {
            blockSelect.value = preselected.getAttribute('data-block-id');
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
