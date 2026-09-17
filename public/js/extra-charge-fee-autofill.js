/**
 * "Raise Extra Charge" form: selecting a fee type auto-fills the Amount
 * field from that fee type's default amount (still editable afterwards).
 * External file (not inline) because the app's CSP has no 'unsafe-inline'
 * for script-src.
 */
document.addEventListener('DOMContentLoaded', function () {
    var feeTypeSelect = document.getElementById('fee_type_id');
    var amountField = document.getElementById('amount');

    if (!feeTypeSelect || !amountField) {
        return;
    }

    feeTypeSelect.addEventListener('change', function () {
        var selected = feeTypeSelect.options[feeTypeSelect.selectedIndex];
        var defaultAmount = selected.dataset.defaultAmount;

        if (defaultAmount) {
            amountField.value = defaultAmount;
        }
    });
});
