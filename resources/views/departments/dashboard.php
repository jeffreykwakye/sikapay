<?php
// Variables provided: $title, $department, $staff, $payrollHistory, $periods, $h (helper)

$staffCount = count($staff ?? []);

// Prepare data for the chart
$historyLabels = json_encode(array_column($payrollHistory, 'month'));
$historyGrossPay = json_encode(array_column($payrollHistory, 'total_gross'));
$historyNetPay = json_encode(array_column($payrollHistory, 'total_net'));

?>

<div class="page-header">
    <h3 class="fw-bold mb-3"><?= $h($title) ?></h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/departments">Departments</a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><?= $h($department['name']) ?></li>
    </ul>
</div>

<div class="page-inner">
    <!-- KPI Cards -->
    <div class="row">
        <div class="col-sm-6 col-md-3">
            <div class="card card-stats card-round">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-icon">
                            <div class="icon-big text-center icon-primary bubble-shadow-small">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="col col-stats ms-3 ms-sm-0">
                            <div class="numbers">
                                <p class="card-category">Total Staff</p>
                                <h4 class="card-title"><?= $staffCount ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Add other KPI cards here later -->
    </div>

    <!-- Payroll History and Staff List Row -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Department Payroll History (Last 6 Months)</div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="departmentHistoryChart"
                                data-labels='<?= $h($historyLabels) ?>'
                                data-gross='<?= $h($historyGrossPay) ?>'
                                data-net='<?= $h($historyNetPay) ?>'
                        ></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Payslip Reports</h4>
                </div>
                <div class="card-body">
                    <p>Select a payroll period to view or download payslips for this department.</p>
                    <div class="form-group">
                        <label for="periodSelect">Payroll Period</label>
                        <select class="form-select" id="periodSelect">
                            <?php foreach ($periods as $period): ?>
                                <?php if($period['is_closed']): ?>
                                <option value="<?= $period['id'] ?>"><?= $h($period['period_name']) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-primary w-100 mt-3" id="viewReportsBtn">View Reports</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Staff List Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Staff in <?= $h($department['name']) ?></h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="department-staff-table" class="display table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Employee ID</th>
                                    <th>Position</th>
                                    <th>Email</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($staff as $employee): ?>
                                <tr>
                                    <td><?= $h($employee['first_name'] . ' ' . $employee['last_name']) ?></td>
                                    <td><?= $h($employee['employee_id']) ?></td>
                                    <td><?= $h($employee['position_title']) ?></td>
                                    <td><?= $h($employee['email']) ?></td>
                                    <td>
                                        <a href="/employees/<?= $employee['user_id'] ?>" class="btn btn-sm btn-info">View Profile</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Load specific JS for this page -->
<script src="/assets/js/tenants/department-dashboard.js"></script>