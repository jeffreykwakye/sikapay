document.addEventListener('DOMContentLoaded', function () {
    const payslipPeriodsAccordion = document.getElementById('payslipPeriodsAccordion');

    if (payslipPeriodsAccordion) {
        payslipPeriodsAccordion.addEventListener('click', function (e) {
            if (e.target.classList.contains('view-payslips-btn')) {
                const button = e.target;
                const periodId = button.dataset.periodId;
                const payslipsContentDiv = document.getElementById(`payslips-content-${periodId}`);

                payslipsContentDiv.innerHTML = '<p>Loading payslips...</p>'; // Show a loading message

                fetch(`/payroll/payslips/${periodId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            let payslipsHtml = '<div class="payslip-list mt-3"><ul class="list-group"> ';
                            if (data.payslips.length > 0) {
                                data.payslips.forEach(payslip => {
                                    payslipsHtml += `
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>${payslip.first_name} ${payslip.last_name} (${payslip.employee_id})</span>
                                            <a href="/payroll/payslips/download/${payslip.id}" class="btn btn-sm btn-primary"><i class="icon-cloud-download"></i> Download Payslip</a>
                                        </li>
                                    `;
                                });
                                payslipsHtml += '</ul></div>';
                            } else {
                                payslipsHtml = '<p class="text-muted">No payslips found for this period.</p>';
                            }
                            payslipsContentDiv.innerHTML = payslipsHtml;
                        } else {
                            payslipsContentDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching payslips:', error);
                        payslipsContentDiv.innerHTML = '<div class="alert alert-danger">An error occurred while fetching payslips.</div>';
                    });
            }
        });
    }
});