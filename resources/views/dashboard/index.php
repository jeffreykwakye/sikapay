<div class="page-header">
    <h3 class="fw-bold mb-3"><?= $h($title) ?></h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/dashboard">Dashboard</a></li>
    </ul>
</div>

<?php if ($isSuperAdmin): ?>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Super Admin Dashboard</h4>
                </div>
                <div class="card-body">
                    <p>Welcome, Super Admin! This is the central dashboard for managing tenants and system settings.</p>
                    <a href="/tenants/create" class="btn btn-primary">Create New Tenant</a>
                    <a href="/tenants" class="btn btn-secondary">View All Tenants</a>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- KPI CARDS ROW -->
    <div class="row">
        <div class="col-xl-3">
            <div class="card card-stats card-info card-round">
                <div class="card-body">
                    <div class="row">
                        <div class="col-5">
                            <div class="icon-big text-center">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="col-7 col-stats">
                            <div class="numbers">
                                <p class="card-category">Active Employees</p>
                                <p class="card-title fs-5"><?= $h($activeEmployees) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3">
            <div class="card card-stats card-secondary card-round">
                <div class="card-body">
                    <div class="row">
                        <div class="col-5">
                            <div class="icon-big text-center">
                                <i class="fas fa-building"></i>
                            </div>
                        </div>
                        <div class="col-7 col-stats">
                            <div class="numbers">
                                <p class="card-category">Departments</p>
                                <p class="card-title fs-5"><?= $h($departmentCount) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3">
            <div class="card card-stats card-success card-round">
                <div class="card-body">
                    <div class="row">
                        <div class="col-5">
                            <div class="icon-big text-center">
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <div class="col-7 col-stats">
                            <div class="numbers">
                                <p class="card-category">Current Plan</p>
                                <p class="card-title fs-5"><?= $h($subscriptionPlan ?? 'N/A') ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3">
            <div class="card card-stats card-danger card-round">
                <div class="card-body">
                    <div class="row">
                        <div class="col-5">
                            <div class="icon-big text-center">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                        </div>
                        <div class="col-7 col-stats">
                            <div class="numbers">
                                <p class="card-category">Subscription Ends</p>
                                <p class="card-title fs-5"><?= $h($subscriptionEndDate) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <h4 class="lead mb-3">Last Payroll Run Summary</h4>
        </div>
    </div>

    <div class="row row-card-no-pd">
        <div class="col-sm-6 col-md-6">
            <div class="card card-stats card-round">
                <div class="card-body">
                    <div class="row">
                        <div class="col-5">
                            <div class="icon-big text-center">
                                <i class="icon-wallet text-info"></i>
                            </div>
                        </div>
                        <div class="col-7 col-stats">
                            <div class="numbers">
                                <p class="card-category">Gross Payroll</p>
                                <h4 class="card-title">GH&cent; <?= $h(number_format($grossPayrollLastMonth, 2)) ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-6">
            <div class="card card-stats card-round">
                <div class="card-body">
                    <div class="row">
                        <div class="col-5">
                            <div class="icon-big text-center">
                                <i class="icon-wallet text-success"></i>
                            </div>
                        </div>
                        <div class="col-7 col-stats">
                            <div class="numbers">
                                <p class="card-category">Net Pay</p>
                                <h4 class="card-title">GH&cent; <?= $h(number_format($netPayLastMonth, 2)) ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card card-stats card-round">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-icon">
                            <div class="icon-big text-center icon-danger bubble-shadow-small">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                        </div>
                        <div class="col col-stats ms-3 ms-sm-0">
                            <div class="numbers">
                                <p class="card-category">Total Tax (PAYE)</p>
                                <h4 class="card-title">GHS <?= number_format($payeLastMonth, 2) ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
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
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="payrollSummaryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Employees by Department</div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="departmentDonutChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- INFORMATION & ACTIVITY ROW -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Upcoming Anniversaries</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped mt-3">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Anniversary</th>
                                    <th>Years</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($upcomingAnniversaries)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center">No upcoming anniversaries in the next 30 days.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($upcomingAnniversaries as $employee): ?>
                                        <tr>
                                            <td><?= $h($employee['first_name'] . ' ' . $employee['last_name']) ?></td>
                                            <td><?= $h(date('M d', strtotime($employee['hire_date']))) ?></td>
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
                <div class="card-header">
                    <div class="card-title">New Hires (Last 30 Days)</div>
                </div>
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
                                    <tr>
                                        <td colspan="3" class="text-center">No new hires in the last 30 days.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($newHires as $employee): ?>
                                        <tr>
                                            <td><?= $h($employee['first_name'] . ' ' . $employee['last_name']) ?></td>
                                            <td><?= $h(date('M d, Y', strtotime($employee['hire_date']))) ?></td>
                                            <td><?= $h($employee['department_name'] ?? 'N/A') ?></td>
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
<?php endif; ?>