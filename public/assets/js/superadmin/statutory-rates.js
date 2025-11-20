document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTables for SSNIT Rates
    if (document.getElementById('ssnit_rates_table')) {
        $('#ssnit_rates_table').DataTable({
            "pageLength": 10,
            "columns": [
                null, // ID
                null, // Employee Rate
                null, // Employer Rate
                null, // Max Contribution Limit
                null, // Effective Date
                { "orderable": false } // Actions
            ]
        });
    }

    // Initialize DataTables for Withholding Tax Rates
    if (document.getElementById('wht_rates_table')) {
        $('#wht_rates_table').DataTable({
            "pageLength": 10,
            "columns": [
                null, // ID
                null, // Rate
                null, // Employment Type
                null, // Description
                null, // Effective Date
                { "orderable": false } // Actions
            ]
        });
    }

    // Handle Edit SSNIT Rate Modal
    const editSsnitRateModal = document.getElementById('editSsnitRateModal');
    editSsnitRateModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget; // Button that triggered the modal
        const rateId = button.getAttribute('data-id');

        fetch(`/api/statutory-rates/ssnit/${rateId}`)
            .then(response => response.json())
            .then(data => {
                const form = editSsnitRateModal.querySelector('#editSsnitRateForm');
                form.action = `/super/statutory-rates/ssnit/${rateId}`;

                editSsnitRateModal.querySelector('#edit_ssnit_id').value = data.id;
                editSsnitRateModal.querySelector('#edit_ssnit_employee_rate').value = (data.employee_rate * 100).toFixed(2);
                editSsnitRateModal.querySelector('#edit_ssnit_employer_rate').value = (data.employer_rate * 100).toFixed(2);
                editSsnitRateModal.querySelector('#edit_ssnit_max_contribution_limit').value = data.max_contribution_limit;
                editSsnitRateModal.querySelector('#edit_ssnit_effective_date').value = data.effective_date;
            })
            .catch(error => console.error('Error fetching SSNIT rate details:', error));
    });

    // Handle Edit WHT Rate Modal
    const editWhtRateModal = document.getElementById('editWhtRateModal');
    editWhtRateModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget; // Button that triggered the modal
        const rateId = button.getAttribute('data-id');

        fetch(`/api/statutory-rates/wht/${rateId}`)
            .then(response => response.json())
            .then(data => {
                const form = editWhtRateModal.querySelector('#editWhtRateForm');
                form.action = `/super/statutory-rates/wht/${rateId}`;

                editWhtRateModal.querySelector('#edit_wht_id').value = data.id;
                editWhtRateModal.querySelector('#edit_wht_rate').value = (data.rate * 100).toFixed(2);
                editWhtRateModal.querySelector('#edit_wht_employment_type').value = data.employment_type;
                editWhtRateModal.querySelector('#edit_wht_description').value = data.description;
                editWhtRateModal.querySelector('#edit_wht_effective_date').value = data.effective_date;
            })
            .catch(error => console.error('Error fetching WHT rate details:', error));
    });
});
