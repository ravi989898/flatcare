/**
 * Client-side validation for every plain-HTML form in the app (Blade +
 * AdminLTE/Bootstrap, full-page POSTs — no SPA framework). This never
 * replaces backend validation (FormRequests + the SafeUploadedFile/
 * IndianMobileNumber rules) — it's the same rules re-applied in the
 * browser so obviously-bad input (wrong file type, malformed phone number,
 * missing required field) is caught before a round trip, matching the
 * server's `mimes:`/`max:`/format checks field-for-field where a form
 * declares them via the data-* attributes below.
 *
 * Uses Bootstrap's standard "custom validation" pattern (already available
 * here via AdminLTE): a form marked `novalidate` is intercepted on submit,
 * `checkValidity()` plus the extra checks below run, and `.was-validated`
 * toggles Bootstrap's `:invalid`/`:valid` styling and `.invalid-feedback`
 * text.
 */

const PHONE_PATTERN = /^[6-9]\d{9}$/;

function humanFileSize(bytes) {
    return `${(bytes / 1024).toFixed(0)} KB`;
}

function fieldFeedback(field) {
    let feedback = field.parentElement.querySelector(':scope > .invalid-feedback[data-js-feedback]');

    if (!feedback) {
        feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.setAttribute('data-js-feedback', '');
        field.insertAdjacentElement('afterend', feedback);
    }

    return feedback;
}

function validatePhoneField(field) {
    if (field.value.trim() === '' && !field.required) {
        return true;
    }

    if (!PHONE_PATTERN.test(field.value.trim())) {
        field.setCustomValidity('Enter a valid 10-digit mobile number.');
        fieldFeedback(field).textContent = 'Enter a valid 10-digit mobile number.';

        return false;
    }

    field.setCustomValidity('');

    return true;
}

function validateFileField(field) {
    field.setCustomValidity('');

    if (!field.files || field.files.length === 0) {
        return true;
    }

    const file = field.files[0];
    const allowedExt = (field.dataset.allowedExt || '')
        .split(',')
        .map((ext) => ext.trim().toLowerCase())
        .filter(Boolean);
    const maxSizeKb = parseInt(field.dataset.maxSizeKb || '', 10);
    const extension = file.name.split('.').pop().toLowerCase();

    if (allowedExt.length > 0 && !allowedExt.includes(extension)) {
        const message = `File must be one of: ${allowedExt.join(', ')}.`;
        field.setCustomValidity(message);
        fieldFeedback(field).textContent = message;

        return false;
    }

    if (!Number.isNaN(maxSizeKb) && file.size > maxSizeKb * 1024) {
        const message = `File must be smaller than ${humanFileSize(maxSizeKb * 1024)} (this file is ${humanFileSize(file.size)}).`;
        field.setCustomValidity(message);
        fieldFeedback(field).textContent = message;

        return false;
    }

    return true;
}

function runCustomValidators(form) {
    let allValid = true;

    form.querySelectorAll('[data-validate="phone"]').forEach((field) => {
        if (!validatePhoneField(field)) {
            allValid = false;
        }
    });

    form.querySelectorAll('[data-validate="file"]').forEach((field) => {
        if (!validateFileField(field)) {
            allValid = false;
        }
    });

    return allValid;
}

function wireForm(form) {
    form.querySelectorAll('[data-validate="phone"]').forEach((field) => {
        field.addEventListener('input', () => validatePhoneField(field));
    });

    form.querySelectorAll('[data-validate="file"]').forEach((field) => {
        field.addEventListener('change', () => validateFileField(field));
    });

    form.addEventListener('submit', (event) => {
        const customValid = runCustomValidators(form);

        if (!form.checkValidity() || !customValid) {
            event.preventDefault();
            event.stopPropagation();
        }

        form.classList.add('was-validated');
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[novalidate]').forEach(wireForm);
});
