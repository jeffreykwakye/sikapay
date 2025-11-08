<?php
// Ensure the user is a tenant_admin to view this dashboard
if ($userRole !== 'tenant_admin') {
    // You can redirect or show a generic view
    echo "<div class='alert alert-danger'>You do not have permission to view this dashboard.</div>";
    return;
}
?>

<div class="page-header">
    <h3 class="fw-bold mb-3">Admin Dashboard</h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/">Dashboard</a></li>
    </ul>
</div>


<!-- KPI CARDS ROW -->
<div class="row">
    <div class="col-lg-3">
        <div class="card card-stats card-round">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-icon"><div class="icon-big text-center icon-primary bubble-shadow-small"><i class="fas fa-users"></i></div></div>
                    <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers"><p class="card-category">Active Employees</p><h4 class="card-title"><?= $activeEmployees ?></h4></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card card-stats card-round">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-icon"><div class="icon-big text-center icon-success bubble-shadow-small"><i class="fas fa-building"></i></div></div>
                    <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers"><p class="card-category">Departments</p><h4 class="card-title"><?= $departmentCount ?></h4></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card card-stats card-round">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-icon"><div class="icon-big text-center icon-info bubble-shadow-small"><i class="fas fa-star"></i></div></div>
                    <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers"><p class="card-category">Current Plan</p><h4 class="card-title"><?= htmlspecialchars($subscriptionPlan ?? 'N/A') ?></h4></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card card-stats card-round">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-icon"><div class="icon-big text-center icon-warning bubble-shadow-small"><i class="fas fa-calendar-alt"></i></div></div>
                    <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers"><p class="card-category">Subscription Ends</p><h4 class="card-title"><?= htmlspecialchars($subscriptionEndDate) ?></h4></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <h4 class="fw-bold mb-3">Last Payroll Run Summary</h4>
    </div>
</div>
<div class="row">
    <div class="col-lg-3">
        <div class="card card-stats card-round">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-icon"><div class="icon-big text-center icon-dark bubble-shadow-small"><i class="fas fa-wallet"></i></div></div>
                    <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers"><p class="card-category">Gross Payroll</p><h4 class="card-title">GHS <?= number_format($grossPayrollLastMonth, 2) ?></h4></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card card-stats card-round">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-icon"><div class="icon-big text-center icon-primary bubble-shadow-small"><i class="fas fa-money-bill-wave"></i></div></div>
                    <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers"><p class="card-category">Net Pay</p><h4 class="card-title">GHS <?= number_format($netPayLastMonth, 2) ?></h4></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card card-stats card-round">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-icon"><div class="icon-big text-center icon-danger bubble-shadow-small"><i class="fas fa-file-invoice-dollar"></i></div></div>
                    <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers"><p class="card-category">Total Tax (PAYE)</p><h4 class="card-title">GHS <?= number_format($payeLastMonth, 2) ?></h4></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card card-stats card-round">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-icon"><div class="icon-big text-center icon-secondary bubble-shadow-small"><i class="fas fa-shield-alt"></i></div></div>
                    <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers"><p class="card-category">SSNIT Cost</p><h4 class="card-title">GHS <?= number_format($ssnitCostLastMonth, 2) ?></h4></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</div>

<!-- CHARTS ROW -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <div class="card-title">Payroll Summary (Last 6 Months)</div>
            </div>
            <div class="card-body"><div class="chart-container"><canvas id="payrollSummaryChart"></canvas></div></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <div class="card-title">Employees by Department</div>
            </div>
            <div class="card-body"><div class="chart-container"><canvas id="departmentDonutChart"></canvas></div></div>
        </div>
    </div>
</div>

<!-- INFORMATION & ACTIVITY ROW -->
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><div class="card-title">Upcoming Anniversaries</div></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped mt-3">
                        <thead><tr><th>Employee</th><th>Anniversary</th><th>Years</th></tr></thead>
                        <tbody>
                            <?php if (empty($upcomingAnniversaries)): ?>
                                <tr><td colspan="3" class="text-center">No upcoming anniversaries in the next 30 days.</td></tr>
                            <?php else: ?>
                                <?php foreach ($upcomingAnniversaries as $employee): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></td>
                                        <td><?= htmlspecialchars(date('M d', strtotime($employee['hire_date']))) ?></td>
                                        <td><?= date('Y') - date('Y', strtotime($employee['hire_date'])) + 1 ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><div class="card-title">New Hires (Last 30 Days)</div></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped mt-3">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Hire Date</th>
                                <th>Department</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($newHires)): ?>
                                <tr><td colspan="3" class="text-center">No new hires in the last 30 days.</td></tr>
                            <?php else: ?>
                                <?php foreach ($newHires as $employee): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></td>
                                        <td><?= htmlspecialchars(date('M d, Y', strtotime($employee['hire_date']))) ?></td>
                                        <td><?= htmlspecialchars($employee['department_name'] ?? 'N/A') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Data container for the dashboard charts -->
<div id="dashboard-chart-data" data-charts='<?= json_encode([
    'payrollSummary' => [
        'labels' => array_column($payrollSummary, 'month'),
        'datasets' => [
            [
                'label' => 'Gross Payroll',
                'data' => array_column($payrollSummary, 'total_gross'),
                'backgroundColor' => 'rgba(78, 115, 223, 0.8)',
                'borderColor' => 'rgba(78, 115, 223, 1)',
                'borderWidth' => 1
            ],
            [
                'label' => 'Net Pay',
                'data' => array_column($payrollSummary, 'total_net'),
                'backgroundColor' => 'rgba(28, 200, 138, 0.8)',
                'borderColor' => 'rgba(28, 200, 138, 1)',
                'borderWidth' => 1
            ],
            [
                'label' => 'PAYE',
                'data' => array_column($payrollSummary, 'total_paye'),
                'backgroundColor' => 'rgba(231, 74, 59, 0.8)',
                'borderColor' => 'rgba(231, 74, 59, 1)',
                'borderWidth' => 1
            ]
        ]
    ],
    'departmentHeadcount' => [
        'labels' => array_column($employeeCountByDepartment, 'department_name'),
        'data' => array_column($employeeCountByDepartment, 'employee_count'),
    ]
]) ?>'></div>

<!-- Load the dedicated dashboard JS -->
<script src="/assets/js/tenants/dashboard.js"></script>