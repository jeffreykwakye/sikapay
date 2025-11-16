document.addEventListener('DOMContentLoaded', () => {
    const deptSelect = document.getElementById('department_id');
    const posSelect = document.getElementById('position_id'); // Note: ID is 'position_id' in edit.php
    const prevSelectedPosId = document.getElementById('prev_selected_position_id')?.value; // Optional chaining for safety
    
    if (!deptSelect || !posSelect) {
        console.error('Missing department_id or position_id selectors for cascading dropdown in edit-employee-positions.js.');
        return;
    }

    // Capture all position options for filtering (excluding the placeholder)
    // We need to clone them because we'll be moving them in and out of the select
    const originalOptions = Array.from(posSelect.options).filter(option => option.value !== "");

    /**
     * Filters the position dropdown based on the selected department ID.
     * @param {string} selectedDeptId - The ID of the currently selected department.
     * @param {string|null} restoreId - The position ID to try and restore selection to (used on load).
     */
    const filterPositions = (selectedDeptId, restoreId = null) => {
        // Clear current positions, but keep the initial "Select Position" option
        posSelect.innerHTML = '<option value="">-- Select Position --</option>';

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

    // --- Initialization ---

    // Initial filter run: Apply filtering based on the department selected by PHP 
    // and try to restore the previously submitted position.
    const initialDeptId = deptSelect.value;
    filterPositions(initialDeptId, prevSelectedPosId);
});
