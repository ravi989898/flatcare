/**
 * "Add Admin" form: picking a Flat (itself filtered by the chosen Block,
 * see block-flat-filter.js) sets the hidden user_id input and auto-fills
 * the read-only name/email/phone fields from that flat option's resident
 * data attributes. External file (not inline) because the app's CSP has no
 * 'unsafe-inline' for script-src.
 */
document.addEventListener('DOMContentLoaded', function () {
    var select = document.getElementById('flat_select');
    var userIdInput = document.getElementById('user_id');

    if (!select || !userIdInput) {
        return;
    }

    function fillUserFields() {
        var selected = select.options[select.selectedIndex];
        var userId = (selected && selected.dataset.userId) || '';

        userIdInput.value = userId;
        document.getElementById('name').value = (userId && selected.dataset.name) || '';
        document.getElementById('email').value = (userId && selected.dataset.email) || '';
        document.getElementById('phone').value = (userId && selected.dataset.phone) || '';
    }

    select.addEventListener('change', fillUserFields);
    fillUserFields();
});
