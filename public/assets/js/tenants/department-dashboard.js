// public/assets/js/tenants/department-dashboard.js

document.addEventListener('DOMContentLoaded', function () {
    // Initialize Datatable for staff list
    $('#department-staff-table').DataTable({
        "pageLength": 5,
        "lengthMenu": [5, 10, 25, 50]
    });

    // Initialize Payroll History Chart
    const historyChartCanvas = document.getElementById('departmentHistoryChart');
    if (historyChartCanvas) {
        const ctx = historyChartCanvas.getContext('2d');
        const labels = JSON.parse(historyChartCanvas.dataset.labels || '[]');
        const grossPayData = JSON.parse(historyChartCanvas.dataset.gross || '[]');
        const netPayData = JSON.parse(historyChartCanvas.dataset.net || '[]');

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

    // Handle "View Reports" button click
    const viewReportsBtn = document.getElementById('viewReportsBtn');
    if (viewReportsBtn) {
        viewReportsBtn.addEventListener('click', function() {
            const periodSelect = document.getElementById('periodSelect');
            const selectedPeriodId = periodSelect.value;
            const departmentId = window.location.pathname.split('/')[2]; // Assumes URL is /departments/{id}/dashboard

            if (selectedPeriodId && departmentId) {
                window.location.href = '/departments/' + departmentId + '/reports/' + selectedPeriodId;
            } else {
                swal("Error", "Please select a payroll period.", "error");
            }
        });
    }
});

