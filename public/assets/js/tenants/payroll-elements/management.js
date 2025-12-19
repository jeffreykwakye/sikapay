document.addEventListener('DOMContentLoaded', function() {
    
    // Handle View Details Modal Data Population
    const viewElementModal = document.getElementById('viewElementModal');
    if (viewElementModal) {
        viewElementModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const elementId = button.getAttribute('data-id');

            // --- Show a loading state ---
            const fields = [
                'name', 'category', 'amount-type', 'default-amount', 
                'calculation-base', 'is-taxable', 'is-ssnit-chargeable', 
                'is-recurring', 'description'
            ];
            fields.forEach(field => {
                const el = document.getElementById(`view-${field}`);
                if (el) el.textContent = 'Loading...';
            });

            // --- Fetch data from API ---
            fetch(`/api/payroll-elements/${elementId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('API Response:', data); // Log the received data for debugging

                    // --- Helper functions for formatting ---
                    const formatText = (text) => text ? text.charAt(0).toUpperCase() + text.slice(1).replace(/_/g, ' ') : 'N/A';
                    const formatYesNo = (value) => (value == 1 || value === true) ? 'Yes' : 'No';
                    const formatAmount = (amount, amountType) => {
                        let formatted = amount ? parseFloat(amount).toFixed(2) : '0.00';
                        if (amountType === 'percentage') {
                            formatted += '%';
                        }
                        return formatted;
                    };

                    // --- Populate modal with fetched data ---
                    document.getElementById('view-name').textContent = data.name || 'N/A';
                    document.getElementById('view-category').textContent = formatText(data.category);
                    document.getElementById('view-amount-type').textContent = formatText(data.amount_type);
                    document.getElementById('view-default-amount').textContent = formatAmount(data.default_amount, data.amount_type);
                    document.getElementById('view-calculation-base').textContent = formatText(data.calculation_base);
                    document.getElementById('view-is-taxable').textContent = formatYesNo(data.is_taxable);
                    document.getElementById('view-is-ssnit-chargeable').textContent = formatYesNo(data.is_ssnit_chargeable);
                    document.getElementById('view-is-recurring').textContent = formatYesNo(data.is_recurring);
                    document.getElementById('view-description').textContent = data.description || 'No description provided.';
                })
                .catch(error => {
                    console.error('Error fetching payroll element details:', error);
                    document.getElementById('view-name').textContent = 'Error loading details.';
                    fields.slice(1).forEach(field => { // Clear other fields on error
                        const el = document.getElementById(`view-${field}`);
                        if (el) el.textContent = '';
                    });
                });
        });
    }

    // Handle Edit Modal Data Population
    var editModal = document.getElementById('editModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget; // Button that triggered the modal
            var id = button.getAttribute('data-id');
            var name = button.getAttribute('data-name');
            var category = button.getAttribute('data-category');
            var amountType = button.getAttribute('data-amount-type');
            var defaultAmount = button.getAttribute('data-default-amount');
            var calculationBase = button.getAttribute('data-calculation-base');
            var isTaxable = button.getAttribute('data-is-taxable');
            var isSsnitChargeable = button.getAttribute('data-is-ssnit-chargeable');
            var isRecurring = button.getAttribute('data-is-recurring');
            var description = button.getAttribute('data-description');

            var modalTitle = editModal.querySelector('.modal-title');
            var editForm = editModal.querySelector('#editForm');

            modalTitle.textContent = 'Edit ' + name;
            editForm.action = '/payroll-elements/' + id; // Set form action for update

            editForm.querySelector('#edit_name').value = name;
            editForm.querySelector('#edit_category').value = category;
            editForm.querySelector('#edit_amount_type').value = amountType;
            editForm.querySelector('#edit_default_amount').value = defaultAmount;
            editForm.querySelector('#edit_calculation_base').value = calculationBase;
            editForm.querySelector('#edit_is_taxable').checked = (isTaxable === '1');
            editForm.querySelector('#edit_is_ssnit_chargeable').checked = (isSsnitChargeable === '1');
            editForm.querySelector('#edit_is_recurring').checked = (isRecurring === '1');
            editForm.querySelector('#edit_description').value = description;

            // Toggle calculation base visibility based on amount type
            var calculationBaseGroup = editForm.querySelector('#edit_calculation_base').closest('.mb-3');
            if (amountType === 'percentage') {
                calculationBaseGroup.style.display = 'block';
            } else {
                calculationBaseGroup.style.display = 'none';
            }
        });
    }


    // Handle Delete Modal Data Population
    var deleteConfirmModal = document.getElementById('deleteConfirmModal');
    if (deleteConfirmModal) {
        deleteConfirmModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget; // Button that triggered the modal
            var id = button.getAttribute('data-id');
            var name = button.getAttribute('data-name');

            var deleteElementName = deleteConfirmModal.querySelector('#deleteElementName');
            var deleteForm = deleteConfirmModal.querySelector('#deleteForm');

            deleteElementName.textContent = name;
            deleteForm.action = '/payroll-elements/' + id + '/delete'; // Set form action for delete
        });
    }


    // Toggle calculation base visibility on create form
    var createAmountType = document.getElementById('create_amount_type');
    if (createAmountType) {
        var createCalculationBaseGroup = document.getElementById('create_calculation_base').closest('.mb-3');
        createAmountType.addEventListener('change', function() {
            if (this.value === 'percentage') {
                createCalculationBaseGroup.style.display = 'block';
            } else {
                createCalculationBaseGroup.style.display = 'none';
            }
        });
        // Initial state for create form
        if (createAmountType.value === 'percentage') {
            createCalculationBaseGroup.style.display = 'block';
        } else {
            createCalculationBaseGroup.style.display = 'none';
        }
    }
});