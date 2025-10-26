document.addEventListener('DOMContentLoaded', function() {
    
    // ==============================================
    // 1. EDIT MODAL LOGIC
    // ==============================================
    const editModal = document.getElementById('editModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            // Get data from the button's data attributes
            const id = button.getAttribute('data-id');
            const title = button.getAttribute('data-title');
            const departmentId = button.getAttribute('data-department-id'); // Note: This will be '0' or the actual ID string

            // Get references to the modal elements
            const modalTitle = editModal.querySelector('.modal-title');
            const editForm = document.getElementById('editForm');
            const titleInput = document.getElementById('edit_title');
            const departmentSelect = document.getElementById('edit_department_id');

            // Populate the modal
            modalTitle.textContent = 'Edit Position: ' + title;
            editForm.action = '/positions/update/' + id;
            titleInput.value = title;
            
            // Set the correct option as selected in the dropdown
            // Convert departmentId to string for comparison with option values
            departmentSelect.value = departmentId; 
        });
    }

    // ==============================================
    // 2. DELETE MODAL LOGIC
    // ==============================================
    const deleteConfirmModal = document.getElementById('deleteConfirmModal');
    if (deleteConfirmModal) {
        deleteConfirmModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            
            // Get data from the button's data attributes
            const id = button.getAttribute('data-id');
            const title = button.getAttribute('data-title');
            
            // Get references to the dynamic elements
            const deleteForm = document.getElementById('deleteForm');
            const deleteTitlePlaceholder = document.getElementById('deletePositionTitle');

            // Set the form action URL
            deleteForm.action = '/positions/delete/' + id;
            
            // Set the position title in the confirmation message
            deleteTitlePlaceholder.textContent = title;
        });
    }
});