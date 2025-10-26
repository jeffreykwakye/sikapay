document.addEventListener('DOMContentLoaded', () => {
    const deptSelect = document.getElementById('department_id');
    const posSelect = document.getElementById('current_position_id');
    const prevSelectedPosId = document.getElementById('prev_selected_position_id').value;
    
    // Find the closest card body to inject the alert
    const formContainer = posSelect.closest('.card-body');

    // Capture all position options for filtering (excluding the placeholder)
    const originalOptions = Array.from(posSelect.options).slice(1);

    /**
     * Displays a temporary Bootstrap alert message.
     * @param {string} message - The message content.
     * @param {string} type - The Bootstrap alert type (e.g., 'warning', 'danger').
     */
    const showAlert = (message, type = 'warning') => {
        const alertHtml = `
            <div id="position-alert" class="alert alert-${type} alert-dismissible fade show mb-3" role="alert">
                <i class="icon-close me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        // Remove any existing alert first
        const existingAlert = document.getElementById('position-alert');
        if (existingAlert) {
            existingAlert.remove();
        }

        // Insert the new alert just before the form content
        formContainer.insertAdjacentHTML('afterbegin', alertHtml);

        // Auto-dismiss the alert after a few seconds
        setTimeout(() => {
            const newAlert = document.getElementById('position-alert');
            if (newAlert) {
                new bootstrap.Alert(newAlert).close();
            }
        }, 5000);
    };

    /**
     * Filters the position dropdown based on the selected department ID.
     * @param {string} selectedDeptId - The ID of the currently selected department.
     * @param {string|null} restoreId - The position ID to try and restore selection to (used on load).
     */
    const filterPositions = (selectedDeptId, restoreId = null) => {
        // Clear current positions, but keep the initial "Select Position" option
        posSelect.innerHTML = '<option value="">Select Position</option>';

        originalOptions.forEach(option => {
            const positionDeptId = option.getAttribute('data-department-id');
            
            // Show position if the position's department ID matches the selected department ID.
            if (positionDeptId === selectedDeptId) {
                posSelect.appendChild(option.cloneNode(true));
            }
        });
        
        // Restore the selection if an ID is provided
        if (restoreId) {
             const newOption = posSelect.querySelector(`option[value="${restoreId}"]`);
             if (newOption) {
                 newOption.selected = true;
             }
        }
        
        // Disable the position select if no department is selected
        posSelect.disabled = !selectedDeptId; 
        posSelect.style.backgroundColor = selectedDeptId ? '' : '#f0f0f0';
    };

    // --- Event Listeners ---

    // 1. Department Change Listener (Main Filtering Logic)
    deptSelect.addEventListener('change', (e) => {
        const selectedDeptId = e.target.value;
        // When department changes, filter positions and clear the previous selection (restoreId is null)
        filterPositions(selectedDeptId, null);
    });

    // 2. Position Select Change Listener (Department Requirement Enforcement)
    posSelect.addEventListener('change', (e) => {
        const selectedDeptId = deptSelect.value;
        const newlySelectedPosId = e.target.value;

        // Only enforce if the user actually tried to select a position
        if (newlySelectedPosId && !selectedDeptId) {
            // Revert the selection back to the placeholder
            e.target.value = ''; 
            
            showAlert('Please select a **Department** first before selecting a Position.', 'danger');
            
            // Focus on the Department field
            deptSelect.focus();
        }
    });

    // --- Initialization ---

    // Initial filter run: Apply filtering based on the department selected by PHP 
    // and try to restore the previously submitted position.
    const initialDeptId = deptSelect.value;
    filterPositions(initialDeptId, prevSelectedPosId);
});