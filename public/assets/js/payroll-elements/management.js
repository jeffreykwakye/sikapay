document.addEventListener('DOMContentLoaded', function() {
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