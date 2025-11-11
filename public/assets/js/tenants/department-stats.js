document.addEventListener('DOMContentLoaded', function () {
    const payrollChartEl = document.getElementById('departmentPayrollChart');
    const distributionChartEl = document.getElementById('departmentDistributionChart');

    if (!payrollChartEl || !distributionChartEl) {
        return;
    }

    // Read data from data-* attributes
    const chartLabels = JSON.parse(payrollChartEl.dataset.labels || '[]');
    const grossPayData = JSON.parse(payrollChartEl.dataset.grossPay || '[]');
    const employeeCountData = JSON.parse(distributionChartEl.dataset.employeeCounts || '[]');


    // 1. Gross Payroll by Department (Bar Chart)
    new Chart(payrollChartEl.getContext('2d'), {
        type: 'bar',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Gross Pay (GHS)',
                data: grossPayData,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return 'GHS ' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += 'GHS ' + context.parsed.y.toLocaleString();
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });


    // 2. Employee Distribution by Department (Doughnut Chart)
    new Chart(distributionChartEl.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Number of Employees',
                data: employeeCountData,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)',
                    'rgba(199, 199, 199, 0.7)',
                    'rgba(83, 102, 255, 0.7)',
                    'rgba(40, 159, 64, 0.7)',
                    'rgba(210, 99, 132, 0.7)'
                ],
                borderColor: '#ffffff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed !== null) {
                                label += context.parsed.toLocaleString() + ' employees';
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
});
