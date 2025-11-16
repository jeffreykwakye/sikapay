document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll('.tab-pane form');
    const flashMessageContainer = document.getElementById('flash-message-container'); // Placeholder for messages
    const resetPermissionsBtn = document.getElementById('reset-permissions-btn'); // Get the new button
    const permissionCheckboxes = document.querySelectorAll('.permission-checkbox'); // Get all permission checkboxes

    if (!forms.length || !flashMessageContainer) {
        console.warn('AJAX form handler: No forms found or flash message container missing.');
        // Continue execution as some pages might not have forms but still need flash messages or other JS
    }

    /**
     * Displays a Bootstrap alert message.
     * @param {string} message - The message content.
     * @param {string} type - The Bootstrap alert type (e.g., 'success', 'danger', 'warning').
     */
    const displayFlashMessage = (message, type) => {
        flashMessageContainer.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="icon-${type === 'success' ? 'check' : 'close'} me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            const alertElement = flashMessageContainer.querySelector('.alert');
            if (alertElement) {
                new bootstrap.Alert(alertElement).close();
            }
        }, 5000);
    };

    forms.forEach(form => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault(); // Prevent default form submission

            const submitButton = form.querySelector('button[type="submit"]');
            const originalButtonText = submitButton ? submitButton.textContent : '';
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Saving...';
            }

            const formData = new FormData(form);
            const actionUrl = form.getAttribute('action');

            try {
                const response = await fetch(actionUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest' // Indicate AJAX request
                    }
                });

                const data = await response.json();

                if (response.ok) { // HTTP status 200-299
                    displayFlashMessage(data.message, 'success');
                    // Optional: Update UI elements if needed, e.g., employee name in header
                    // if (form.id === 'personal-details-form') {
                    //     document.querySelector('.h3').textContent = `Edit Employee: ${formData.get('first_name')} ${formData.get('last_name')}`;
                    // }
                } else { // HTTP status 400, 500, etc.
                    displayFlashMessage(data.message || 'An unknown error occurred.', 'danger');
                }
            } catch (error) {
                console.error('AJAX submission error:', error);
                displayFlashMessage('A network error occurred. Please try again.', 'danger');
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = originalButtonText;
                }
            }
        });
    });

    // Handle Reset Permissions button click
    if (resetPermissionsBtn) {
        const resetPermissionsModal = new bootstrap.Modal(document.getElementById('resetPermissionsModal'));
        const confirmResetPermissionsBtn = document.getElementById('confirm-reset-permissions-btn');

        resetPermissionsBtn.addEventListener('click', () => {
            resetPermissionsModal.show(); // Show the Bootstrap modal
        });

        confirmResetPermissionsBtn.addEventListener('click', async () => {
            resetPermissionsModal.hide(); // Hide the modal immediately

            const userId = resetPermissionsBtn.dataset.userId;
            if (!userId) {
                displayFlashMessage('Error: User ID not found for reset.', 'danger');
                return;
            }

            resetPermissionsBtn.disabled = true;
            const originalButtonText = resetPermissionsBtn.textContent;
            resetPermissionsBtn.textContent = 'Resetting...';

            const csrfToken = document.querySelector('input[name="csrf_token"]').value; // Get CSRF token
            const formData = new FormData();
            formData.append('csrf_token', csrfToken); // Append CSRF token to FormData

            try {
                const response = await fetch(`/employees/${userId}/permissions/reset-to-defaults`, {
                    method: 'POST',
                    body: formData, // Send FormData as body
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest' // Indicate AJAX request
                        // 'Content-Type' is automatically set by FormData
                    }
                });

                const data = await response.json();

                if (response.ok) {
                    displayFlashMessage(data.message, 'success');
                    // Reload the permissions tab to reflect changes
                    location.reload(); 
                } else {
                    displayFlashMessage(data.message || 'Failed to reset permissions.', 'danger');
                }
            } catch (error) {
                console.error('Reset permissions AJAX error:', error);
                displayFlashMessage('A network error occurred during reset. Please try again.', 'danger');
            } finally {
                resetPermissionsBtn.disabled = false;
                resetPermissionsBtn.textContent = originalButtonText;
            }
        });
    }

    // Handle individual permission checkbox changes
    permissionCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', async (event) => {
            const userId = checkbox.dataset.userId;
            const permissionId = checkbox.dataset.permissionId;
            const isAllowed = checkbox.checked ? 1 : 0;
            const isRoleDefault = checkbox.dataset.isRoleDefault === '1'; // Convert string to boolean

            if (!userId || !permissionId) {
                displayFlashMessage('Error: Missing user or permission ID for update.', 'danger');
                return;
            }

            checkbox.disabled = true; // Disable checkbox during AJAX call

            const csrfToken = document.querySelector('input[name="csrf_token"]').value;
            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('is_allowed', isAllowed.toString());
            formData.append('is_role_default', isRoleDefault ? '1' : '0'); // Send as string '1' or '0'

            try {
                const response = await fetch(`/employees/${userId}/permissions/${permissionId}/toggle`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (response.ok) {
                    displayFlashMessage(data.message, 'success');
                    // No page reload needed, UI already reflects the change
                } else {
                    displayFlashMessage(data.message || 'Failed to update permission.', 'danger');
                    checkbox.checked = !checkbox.checked; // Revert checkbox state on error
                }
            } catch (error) {
                console.error('Toggle permission AJAX error:', error);
                displayFlashMessage('A network error occurred. Please try again.', 'danger');
                checkbox.checked = !checkbox.checked; // Revert checkbox state on network error
            } finally {
                checkbox.disabled = false; // Re-enable checkbox
            }
        });
    });
});
