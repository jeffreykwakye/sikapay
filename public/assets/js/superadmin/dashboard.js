document.addEventListener('DOMContentLoaded', function() {
    const chartDataElement = document.getElementById('superadmin-chart-data');
    if (!chartDataElement) {
        console.error('superadmin-chart-data element not found.');
        return;
    }

    const rawData = chartDataElement.dataset.charts;
    if (!rawData) {
        console.error('No chart data found in superadmin-chart-data element.');
        return;
    }

    let chartsData;
    try {
        chartsData = JSON.parse(rawData);
    } catch (e) {
        console.error('Error parsing chart data:', e);
        return;
    }

    // --- Revenue Trend Chart ---
    const revenueTrendCtx = document.getElementById('revenueTrendChart');
    if (revenueTrendCtx && chartsData.revenueTrend) {
        new Chart(revenueTrendCtx, {
            type: 'line',
            data: {
                labels: chartsData.revenueTrend.labels,
                datasets: [{
                    label: 'Monthly Revenue (GHS)',
                    data: chartsData.revenueTrend.data,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Revenue (GHS)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Month'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': GH₵ ' + context.raw.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            }
                        }
                    }
                }
            }
        });
    }

    // --- Plan Distribution Chart ---
    const planDistributionCtx = document.getElementById('planDistributionChart');
    if (planDistributionCtx && chartsData.planDistribution) {
        const backgroundColors = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#E7E9ED'
        ];
        new Chart(planDistributionCtx, {
            type: 'doughnut',
            data: {
                labels: chartsData.planDistribution.labels,
                datasets: [{
                    data: chartsData.planDistribution.data,
                    backgroundColor: backgroundColors.slice(0, chartsData.planDistribution.labels.length),
                    hoverBackgroundColor: backgroundColors.slice(0, chartsData.planDistribution.labels.length)
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
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
            }
        });
    }
});
