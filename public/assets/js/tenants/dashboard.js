document.addEventListener('DOMContentLoaded', function () {
    // Check if Chart is defined
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded. Please make sure it is included before this script.');
        return;
    }

    const chartDataEl = document.getElementById('dashboard-chart-data');
    if (!chartDataEl) {
        console.error('Dashboard data container not found.');
        return;
    }

    const dashboardData = JSON.parse(chartDataEl.dataset.charts);
    if (typeof dashboardData === 'undefined') {
        console.error('Dashboard data is not available or is invalid.');
        return;
    }

    // 1. Payroll Summary Bar Chart
    var payrollSummaryCtx = document.getElementById('payrollSummaryChart').getContext('2d');
    if (payrollSummaryCtx) {
        new Chart(payrollSummaryCtx, {
            type: 'bar',
            data: dashboardData.payrollSummary,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value, index, values) {
                                return 'GHS ' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += 'GHS ' + context.parsed.y.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Department Headcount Donut Chart
    var departmentDonutCtx = document.getElementById('departmentDonutChart').getContext('2d');
    if (departmentDonutCtx) {
        new Chart(departmentDonutCtx, {
            type: 'doughnut',
            data: {
                labels: dashboardData.departmentHeadcount.labels,
                datasets: [{
                    data: dashboardData.departmentHeadcount.data,
                    backgroundColor: [
                        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', 
                        '#858796', '#5a5c69', '#f8f9fc', '#a9b6d7', '#71ddb2'
                    ],
                    hoverBackgroundColor: [
                        '#2e59d9', '#17a673', '#2c9faf', '#f4b619', '#e02d1b',
                        '#60616f', '#3e3f48', '#d4d5d8', '#8c9ac8', '#4bc99e'
                    ],
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed !== null) {
                                    label += context.parsed;
                                }
                                return label;
                            }
                        }
                    }
                }
            },
        });
    }
});
