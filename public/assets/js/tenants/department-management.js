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
            const name = button.getAttribute('data-name');

            // Get references to the modal elements
            const modalTitle = editModal.querySelector('.modal-title');
            const editForm = document.getElementById('editForm');
            const nameInput = document.getElementById('edit_name');

            // Populate the modal
            modalTitle.textContent = 'Edit Department: ' + name;
            editForm.action = '/departments/update/' + id;
            nameInput.value = name;
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
            const name = button.getAttribute('data-name');
            
            // Get references to the dynamic elements
            const deleteForm = document.getElementById('deleteForm');
            const deleteNamePlaceholder = document.getElementById('deleteDepartmentName');

            // Set the form action URL
            deleteForm.action = '/departments/delete/' + id;
            
            // Set the department name in the confirmation message
            deleteNamePlaceholder.textContent = name;
        });
    }
});