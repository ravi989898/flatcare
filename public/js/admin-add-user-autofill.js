/**
 * "Add Admin" form: selecting an existing society user auto-fills the
 * read-only name/email/phone fields from that <option>'s data attributes.
 * External file (not inline) because the app's CSP has no 'unsafe-inline'
 * for script-src.
 */
document.addEventListener('DOMContentLoaded', function () {
    var select = document.getElementById('user_id');

    if (!select) {
        return;
    }

    function fillUserFields() {
        var selected = select.options[select.selectedIndex];

        document.getElementById('name').value = selected.dataset.name || '';
        document.getElementById('email').value = selected.dataset.email || '';
        document.getElementById('phone').value = selected.dataset.phone || '';
    }

    select.addEventListener('change', fillUserFields);
    fillUserFields();
});
