document.addEventListener('DOMContentLoaded', function() {
    const leaveDetailsModal = new bootstrap.Modal(document.getElementById('leaveDetailsModal'));
    const modalLoader = document.getElementById('modal-loader');
    const modalContentDisplay = document.getElementById('modal-content-display');
    const modalFooterActions = document.getElementById('modal-footer-actions');
    const modalApproveForm = document.getElementById('modal-approve-form');
    const modalRejectForm = document.getElementById('modal-reject-form');
    const modalMarkReturnedForm = document.getElementById('modal-mark-returned-form');

    // Function to handle AJAX form submission
    function handleFormSubmission(event) {
        event.preventDefault(); // Prevent default form submission
        const form = event.target;
        const url = form.action;
        const formData = new FormData(form);

        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest' // Identify as AJAX request
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Display success message
                const flashContainer = document.getElementById('flash-message-container');
                if (flashContainer) {
                    flashContainer.innerHTML = `<div class="alert alert-success alert-dismissible fade show" role="alert">${data.message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
                } else {
                    alert(data.message); // Fallback if flash container not found
                }
                leaveDetailsModal.hide(); // Hide the modal after action
                location.reload(); // Reload page to update table
            } else {
                // Display error message
                const flashContainer = document.getElementById('flash-message-container');
                if (flashContainer) {
                    flashContainer.innerHTML = `<div class="alert alert-danger alert-dismissible fade show" role="alert">${data.message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
                } else {
                    alert(data.message); // Fallback
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const flashContainer = document.getElementById('flash-message-container');
            if (flashContainer) {
                flashContainer.innerHTML = `<div class="alert alert-danger alert-dismissible fade show" role="alert">An unexpected error occurred.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
            } else {
                alert('An unexpected error occurred.');
            }
        });
    }

    // Attach event listeners to forms for Approve/Reject/Mark as Returned (if they exist)
    if (modalApproveForm) modalApproveForm.addEventListener('submit', handleFormSubmission);
    if (modalRejectForm) modalRejectForm.addEventListener('submit', handleFormSubmission);
    if (modalMarkReturnedForm) modalMarkReturnedForm.addEventListener('submit', handleFormSubmission);


    document.querySelectorAll('.view-details-btn').forEach(button => {
        button.addEventListener('click', function() {
            const appId = this.dataset.appId;
            
            // Reset modal state
            modalContentDisplay.style.display = 'none';
            modalLoader.style.display = 'block';
            if (modalApproveForm) modalApproveForm.style.display = 'none';
            if (modalRejectForm) modalRejectForm.style.display = 'none';
            if (modalMarkReturnedForm) modalMarkReturnedForm.style.display = 'none';
            if (modalFooterActions) modalFooterActions.style.display = 'flex'; // Ensure footer is visible for close button
            
            leaveDetailsModal.show();

            // Fetch data
            fetch(`/api/leave/application/${appId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const app = data.data;
                        document.getElementById('modal-employee-name').textContent = app.employee_name;
                        document.getElementById('modal-leave-type').textContent = app.leave_type_name;
                        document.getElementById('modal-total-days').textContent = app.total_days;
                        document.getElementById('modal-start-date').textContent = app.start_date;
                        document.getElementById('modal-end-date').textContent = app.end_date;
                        document.getElementById('modal-submitted-date').textContent = app.submitted_date;
                        document.getElementById('modal-reason').textContent = app.reason || 'No reason provided.';
                        document.getElementById('modal-leave-balance').textContent = app.remaining_balance + ' days';

                        // Show/hide action buttons based on status
                        if (app.status === 'pending') {
                            if (modalApproveForm) {
                                modalApproveForm.action = `/leave/approve/${app.id}`;
                                modalApproveForm.style.display = 'inline';
                            }
                            if (modalRejectForm) {
                                modalRejectForm.action = `/leave/reject/${app.id}`;
                                modalRejectForm.style.display = 'inline';
                            }
                        } else if (app.status === 'approved') {
                            // Only show Mark as Returned for approved leaves
                            if (modalMarkReturnedForm) {
                                modalMarkReturnedForm.action = `/api/leave/returned/${app.id}`;
                                modalMarkReturnedForm.style.display = 'inline';
                            }
                        }
                        // For 'returned' or 'rejected' status, all action forms remain hidden.

                    } else {
                        modalContentDisplay.innerHTML = `<div class="alert alert-danger">${data.message || 'Failed to load details.'}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Error fetching leave application details:', error);
                    modalContentDisplay.innerHTML = `<div class="alert alert-danger">Error: ${error.message}. Could not load details.</div>`;
                })
                .finally(() => {
                    modalLoader.style.display = 'none';
                    modalContentDisplay.style.display = 'block';
                });
        });
    });

    // Handle form submissions from the table rows for "Mark as Returned"
    document.querySelectorAll('.mark-returned-form').forEach(form => {
        form.addEventListener('submit', handleFormSubmission);
    });
});