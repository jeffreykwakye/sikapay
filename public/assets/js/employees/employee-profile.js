// public/assets/js/employees/employee-profile.js

document.addEventListener('DOMContentLoaded', function () {
    const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
    const errorModalMessage = document.getElementById('errorModalMessage');

    function showError(message) {
        errorModalMessage.textContent = message;
        errorModal.show();
    }

    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
    const successModalMessage = document.getElementById('successModalMessage');

    function showSuccess(message) {
        successModalMessage.textContent = message;
        successModal.show();
    }

    const confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    const confirmationModalMessage = document.getElementById('confirmationModalMessage');
    const confirmActionButton = document.getElementById('confirmActionButton');
    let confirmCallback = null;
    let confirmUrl = null;
    let confirmFormData = null;

    function showConfirmation(message, url, formData, callback) {
        confirmationModalMessage.textContent = message;
        confirmUrl = url;
        confirmFormData = formData;
        confirmCallback = callback;
        confirmationModal.show();
    }

    confirmActionButton.addEventListener('click', function () {
        if (confirmCallback) {
            confirmCallback(confirmUrl, confirmFormData);
        }
        confirmationModal.hide();
    });

    const updateImageForm = document.getElementById('updateImageForm');

    if (updateImageForm) {
        updateImageForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const userId = this.dataset.userId;
            const url = `/employees/${userId}/image`;

            // Disable the submit button and show a loading indicator
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Uploading...';

            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the profile image on the page
                    const avatar = document.querySelector('.avatar-img');
                    avatar.src = data.imageUrl;

                    // Show a success message in the modal
                    showSuccess(data.message);

                    // Close the modal after a short delay
                    setTimeout(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('updateImageModal'));
                        modal.hide();
                    }, 2000);
                } else {
                    // Show an error message in the modal
                    const messageDiv = document.getElementById('updateImageMessage');
                    messageDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('An error occurred while uploading the image.');
            })
            .finally(() => {
                // Re-enable the submit button and restore its original text
                submitButton.disabled = false;
                submitButton.innerHTML = 'Upload Image';
            });
        });
    }

    const uploadFileForm = document.getElementById('uploadFileForm');

    if (uploadFileForm) {
        uploadFileForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const userId = this.dataset.userId;
            const url = `/employees/${userId}/files`;

            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Uploading...';

            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Add the new file to the table
                    const tableBody = document.querySelector('#pills-files tbody');
                    const newRow = document.createElement('tr');
                    newRow.innerHTML = `
                        <td><a href="${data.file.file_path}" target="_blank"><i class="icon-doc"></i> ${data.file.file_name}</a></td>
                        <td>${data.file.file_type}</td>
                        <td>${data.file.uploaded_at}</td>
                        <td><button class="btn btn-danger btn-sm delete-file-btn" data-file-id="${data.file.id}"><i class="icon-trash"></i></button></td>
                    `;
                    tableBody.insertBefore(newRow, tableBody.firstChild);

                    // Show a success message in the modal
                    showSuccess(data.message);

                    // Close the modal after a short delay
                    setTimeout(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('uploadFileModal'));
                        modal.hide();
                        // Clear the form fields
                        uploadFileForm.reset();
                    }, 2000);
                } else {
                    // Show an error message in the modal
                    const messageDiv = document.getElementById('uploadFileMessage');
                    messageDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('An error occurred while uploading the file.');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = 'Upload File';
            });
        });
    }

    const fileTable = document.getElementById('pills-files');
    if (fileTable) {
        fileTable.addEventListener('click', function (e) {
            if (e.target.classList.contains('delete-file-btn')) {
                const button = e.target;
                const fileId = button.dataset.fileId;
                const userId = document.getElementById('updateImageForm').dataset.userId; // Get userId from the form
                const url = `/employees/${userId}/files/${fileId}/delete`;
                const csrfToken = document.querySelector('#pills-files table').dataset.csrfToken;

                const formData = new FormData();
                formData.append('csrf_token', csrfToken);

                showConfirmation('Are you sure you want to delete this file?', url, formData, function () {
                    fetch(url, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove the row from the table
                            button.closest('tr').remove();
                            showSuccess(data.message);
                        } else {
                            showError(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showError('An error occurred while deleting the file.');
                    });
                });
            }
        });
    }

    // --- Payroll Elements Logic (Checkbox-based) ---
    const payrollElementsTbody = document.getElementById('payroll-elements-tbody');
    if (payrollElementsTbody) {
        const table = payrollElementsTbody.closest('table');
        const userId = table.dataset.userId;
        const csrfToken = table.dataset.csrfToken;
        const spinner = document.getElementById('element-assignment-spinner');

        payrollElementsTbody.addEventListener('change', function(e) {
            if (!e.target.classList.contains('payroll-element-toggle')) {
                return;
            }

            const checkbox = e.target;
            const elementId = checkbox.dataset.elementId;
            const defaultAmount = checkbox.dataset.defaultAmount;
            const isAssigning = checkbox.checked;

            const url = isAssigning 
                ? `/employees/${userId}/payroll-elements` 
                : `/employees/${userId}/payroll-elements/${elementId}/unassign`;

            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            if (isAssigning) {
                formData.append('payroll_element_id', elementId);
                formData.append('assigned_amount', defaultAmount);
                formData.append('effective_date', document.getElementById('effective_date_hidden').value);
            }

            spinner.style.display = 'inline-block';
            checkbox.disabled = true;

            fetch(url, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess(data.message);
                } else {
                    showError(data.message || 'An unknown error occurred.');
                    checkbox.checked = !isAssigning; // Revert checkbox on failure
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('An error occurred while updating the payroll element.');
                checkbox.checked = !isAssigning; // Revert checkbox on failure
            })
            .finally(() => {
                spinner.style.display = 'none';
                checkbox.disabled = false;
            });
        });
    }
});