/**
 * Submits the "Forgot Password" modal via fetch so resetting an admin's
 * password doesn't reload the page — the result (success or validation
 * error) is shown inline in the modal instead of a redirect or a native
 * alert(). External file (not inline) because the app's CSP has no
 * 'unsafe-inline' for script-src.
 */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form.js-ajax-reset-password').forEach(function (form) {
        var feedback = form.querySelector('[data-js-feedback]');
        var submitButton = form.querySelector('button[type="submit"]');

        function showFeedback(message, type) {
            feedback.textContent = message;
            feedback.classList.remove('d-none', 'alert-danger', 'alert-success');
            feedback.classList.add('alert-' + type);
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            feedback.classList.add('d-none');
            submitButton.disabled = true;

            fetch(form.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(form),
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        return { ok: response.ok, data: data };
                    });
                })
                .then(function (result) {
                    submitButton.disabled = false;

                    if (!result.ok) {
                        var messages = result.data.errors
                            ? Object.values(result.data.errors).flat().join(' ')
                            : result.data.message || 'Something went wrong.';
                        showFeedback(messages, 'danger');

                        return;
                    }

                    form.reset();
                    showFeedback(result.data.message || 'Password reset successfully', 'success');
                    setTimeout(function () {
                        window.location.reload();
                    }, 2000);
                })
                .catch(function () {
                    submitButton.disabled = false;
                    showFeedback('Something went wrong. Please try again.', 'danger');
                });
        });

        var modalEl = form.closest('.modal');

        if (modalEl && window.jQuery) {
            window.jQuery(modalEl).on('hidden.bs.modal', function () {
                feedback.classList.add('d-none');
                form.reset();
            });
        }
    });
});
