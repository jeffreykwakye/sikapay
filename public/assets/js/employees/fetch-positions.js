document.addEventListener('DOMContentLoaded', function () {
    const departmentSelect = document.getElementById('department_id');
    const positionSelect = document.getElementById('position_id');

    if (!departmentSelect || !positionSelect) {
        console.error('Missing department_id or position_id selectors for cascading dropdown.');
        return;
    }

    /**
     * Fetches positions from the API based on the selected department ID.
     */
    function fetchPositions() {
        const departmentId = departmentSelect.value;

        // Clear existing options
        positionSelect.innerHTML = '<option value="">-- Select Position --</option>';

        if (!departmentId) {
            return; // Nothing to fetch if no department is selected
        }

        const apiUrl = '/api/positions?department_id=' + departmentId;

        fetch(apiUrl)
            .then(response => {
                // Handle non-200 HTTP responses (e.g., 401 Unauthorized)
                if (!response.ok) {
                    // This error will be caught by the .catch() block
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.positions && Array.isArray(data.positions)) {
                    data.positions.forEach(position => {
                        const option = document.createElement('option');
                        option.value = position.id;
                        option.textContent = position.title;
                        positionSelect.appendChild(option);
                    });
                } else {
                    console.warn('API returned unexpected data format for positions.');
                }
            })
            .catch(error => {
                console.error('Error fetching positions:', error);
                // Optionally show a user-facing error message here
                // alert('Failed to load positions: ' + error.message);
            });
    }

    // Attach event listener to the department dropdown
    departmentSelect.addEventListener('change', fetchPositions);

    // Initial check (in case the user navigates back or for edit pages)
    // if (departmentSelect.value) {
    //     fetchPositions();
    // }
});