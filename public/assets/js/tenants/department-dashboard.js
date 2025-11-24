// public/assets/js/tenants/department-dashboard.js

document.addEventListener('DOMContentLoaded', function () {
    // Initialize Datatable for staff list
    $('#department-staff-table').DataTable({
        "pageLength": 5,
        "lengthMenu": [5, 10, 25, 50]
    });

    // Initialize Payroll History Chart
    var historyChartCanvas = document.getElementById('departmentHistoryChart');
    if (historyChartCanvas) {
        var ctx = historyChartCanvas.getContext('2d');
        var labels = JSON.parse(historyChartCanvas.dataset.labels || '[]');
        var grossPayData = JSON.parse(historyChartCanvas.dataset.gross || '[]');
        var netPayData = JSON.parse(historyChartCanvas.dataset.net || '[]');

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Total Gross Pay',
                        backgroundColor: '#1d7af3',
                        borderColor: '#1d7af3',
                        data: grossPayData,
                    },
                    {
                        label: 'Total Net Pay',
                        backgroundColor: '#59d05d',
                        borderColor: '#59d05d',
                        data: netPayData,
                    }
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return 'GHS ' + value.toLocaleString();
                            }
                        }
                    }
                }
            },
        });
    }

    // Handle "View Payslips" button click
    var viewPayslipsBtn = document.getElementById('viewPayslipsBtn');
    if (viewPayslipsBtn) {
        viewPayslipsBtn.addEventListener('click', function() {
            var periodSelect = document.getElementById('periodSelect');
            var selectedPeriodId = periodSelect.value;
            var departmentId = window.location.pathname.split('/')[2]; // Assumes URL is /departments/{id}/dashboard

            if (selectedPeriodId && departmentId) {
                // Placeholder using SweetAlert
                swal("Coming Soon!", "This feature will show payslips for Department ID: " + departmentId + " and Period ID: " + selectedPeriodId, "info");
                // window.location.href = '/departments/' + departmentId + '/payslips/' + selectedPeriodId;
            } else {
                swal("Error", "Please select a payroll period.", "error");
            }
        });
    }
});
