document.addEventListener('DOMContentLoaded', function () {
    const viewElementModal = document.getElementById('viewElementModal');

    if (viewElementModal) {
        viewElementModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const elementId = button.getAttribute('data-id');

            // Show a loading state
            document.getElementById('view-name').textContent = 'Loading...';
            // Clear previous data
            document.getElementById('view-category').textContent = '';
            document.getElementById('view-amount-type').textContent = '';
            document.getElementById('view-default-amount').textContent = '';
            document.getElementById('view-calculation-base').textContent = '';
            document.getElementById('view-is-taxable').textContent = '';
            document.getElementById('view-is-ssnit-chargeable').textContent = '';
            document.getElementById('view-is-recurring').textContent = '';
            document.getElementById('view-description').textContent = '';

            fetch(`/api/payroll-elements/${elementId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    // Helper to format text
                    const formatText = (text) => text ? text.charAt(0).toUpperCase() + text.slice(1) : 'N/A';
                    const formatYesNo = (value) => value == 1 ? 'Yes' : 'No';

                    document.getElementById('view-name').textContent = data.name || 'N/A';
                    document.getElementById('view-category').textContent = formatText(data.category);
                    document.getElementById('view-amount-type').textContent = formatText(data.amount_type);
                    document.getElementById('view-default-amount').textContent = data.default_amount ? parseFloat(data.default_amount).toFixed(2) : 'N/A';
                    document.getElementById('view-calculation-base').textContent = data.calculation_base ? data.calculation_base.replace('_', ' ') : 'N/A';
                    document.getElementById('view-is-taxable').textContent = formatYesNo(data.is_taxable);
                    document.getElementById('view-is-ssnit-chargeable').textContent = formatYesNo(data.is_ssnit_chargeable);
                    document.getElementById('view-is-recurring').textContent = formatYesNo(data.is_recurring);
                    document.getElementById('view-description').textContent = data.description || 'No description provided.';
                })
                .catch(error => {
                    console.error('Error fetching payroll element details:', error);
                    document.getElementById('view-name').textContent = 'Error loading details.';
                });
        });
    }
});
