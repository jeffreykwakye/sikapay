<?php
/**
 * @var string $title
 * @var array|null $currentPeriod The current active payroll period.
 * @var callable $h Helper function for HTML escaping.
 */

$this->title = $title;

// Fallback for helper if not provided by the master layout
if (!isset($h)) {
    $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>

<div class="page-header">
    <h3 class="fw-bold mb-3">Payroll Management</h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/dashboard">Dashboard</a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="#">Payroll</a></li>
    </ul>
</div>

<?php 
// Display Flash Messages
if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $h($_SESSION['flash_success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php unset($_SESSION['flash_success']); endif; ?>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $h($_SESSION['flash_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php unset($_SESSION['flash_error']); endif; ?>

<?php if (isset($_SESSION['flash_warning'])): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <?= $h($_SESSION['flash_warning']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php unset($_SESSION['flash_warning']); endif; ?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="card-title">Manage Payroll Periods</div>
            </div>
            <div class="card-body">
                <?php if (empty($payrollPeriods)): ?>
                <div class="alert alert-info" role="alert">
                    No payroll periods have been created yet. Click below to create one.
                </div>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createPeriodModal">Create New Payroll Period</button>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Period Name</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Payment Date</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payrollPeriods as $period): ?>
                            <tr>
                                <td><?= $h($period['period_name']) ?></td>
                                <td><?= date('F j, Y', strtotime($period['start_date'])) ?></td>
                                <td><?= date('F j, Y', strtotime($period['end_date'])) ?></td>
                                <td><?= $period['payment_date'] ? date('F j, Y', strtotime($period['payment_date'])) : 'N/A' ?></td>
                                <td>
                                    <span class="badge <?= (bool)$period['is_closed'] ? 'bg-success' : 'bg-warning' ?>">
                                        <?= (bool)$period['is_closed'] ? 'Closed' : 'Open' ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if (!(bool)$period['is_closed']): ?>
                                        <form action="/payroll/run" method="POST" class="d-inline me-2">
                                            <input type="hidden" name="csrf_token" value="<?= $CsrfToken::getToken() ?>">
                                            <input type="hidden" name="payroll_period_id" value="<?= $period['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-primary">Run Payroll</button>
                                        </form>
                                        <form action="/payroll/period/<?= $period['id'] ?>/close" method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= $CsrfToken::getToken() ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to close this payroll period? This action is irreversible and finalizes all payslips and reports.');">Close Period</button>
                                        </form>
                                    <?php else: ?>
                                        <a href="/payroll/payslips?period_id=<?= $period['id'] ?>" class="btn btn-sm btn-info me-2">View Payslips</a>
                                        <a href="/reports?period_id=<?= $period['id'] ?>" class="btn btn-sm btn-secondary">View Reports</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createPeriodModal">Create New Payroll Period</button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Create New Payroll Period -->
<div class="modal fade" id="createPeriodModal" tabindex="-1" aria-labelledby="createPeriodModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="createPeriodForm" action="/payroll/period" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="createPeriodModalLabel">Create New Payroll Period</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $CsrfToken::getToken() ?>">
                    <div class="mb-3">
                        <label for="period_name" class="form-label">Period Name (e.g., October 2025)</label>
                        <input type="text" class="form-control" id="period_name" name="period_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="payment_date" class="form-label">Payment Date</label>
                        <input type="date" class="form-control" id="payment_date" name="payment_date">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Period</button>
                </div>
            </form>
        </div>
    </div>
</div>
