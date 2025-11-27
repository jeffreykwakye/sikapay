document.addEventListener('DOMContentLoaded', function () {
    const leaveTypeModalElement = document.getElementById('leaveTypeModal');
    if (!leaveTypeModalElement) return;

    const leaveTypeModal = new bootstrap.Modal(leaveTypeModalElement);
    const addLeaveTypeBtn = document.getElementById('add-leave-type-btn');

    // --- Add New Leave Type ---
    // Attach click listener to the "Add New Leave Type" button
    if (addLeaveTypeBtn) {
        addLeaveTypeBtn.addEventListener('click', function() {
            const form = document.getElementById('leaveTypeForm');
            form.action = '/leave/types/create'; // Set action for 'Add New'
            form.reset(); // Clear any previous values
            document.getElementById('leaveTypeModalLabel').innerText = 'Add Leave Type';
            document.getElementById('leaveTypeId').value = ''; // Ensure hidden ID is clear
            document.getElementById('is_active').checked = true; // Default active for new entries
            document.getElementById('is_accrued').checked = false; // Default not accrued for new entries

            leaveTypeModal.show();
        });
    }

    // --- Edit Leave Type (using Event Delegation) ---
    // Listen for clicks on the whole document
    document.addEventListener('click', function(event) {
        // Check if the clicked element is an edit button or is inside one
        const editButton = event.target.closest('.edit-leave-type-btn');
        
        if (editButton) {
            const dataset = editButton.dataset;
            const id = dataset.id;
            const name = dataset.name;
            const days = dataset.default_days;
            const isAccrued = dataset.is_accrued === '1';
            const isActive = dataset.is_active === '1';

            const form = document.getElementById('leaveTypeForm');
            form.action = '/leave/types/update/' + id;
            document.getElementById('leaveTypeModalLabel').innerText = 'Edit Leave Type';
            document.getElementById('leaveTypeId').value = id;
            document.getElementById('name').value = name;
            document.getElementById('default_days').value = days;
            document.getElementById('is_accrued').checked = isAccrued;
            document.getElementById('is_active').checked = isActive;
            
            leaveTypeModal.show();
        }
    });

    // --- Modal Reset Logic ---
    // Event listener to reset the modal when it's closed
    leaveTypeModalElement.addEventListener('hidden.bs.modal', function () {
        const form = document.getElementById('leaveTypeForm');
        form.reset();
        document.getElementById('leaveTypeModalLabel').innerText = 'Add Leave Type';
        document.getElementById('leaveTypeId').value = '';
        document.getElementById('is_active').checked = true;
        document.getElementById('is_accrued').checked = false;
    });
});