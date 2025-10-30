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
                <div class="card-title">Current Payroll Period</div>
            </div>
            <div class="card-body">
                <?php if ($currentPeriod): ?>
                    <p><strong>Period Name:</strong> <?= $h($currentPeriod['period_name']) ?></p>
                    <p><strong>Start Date:</strong> <?= date('F j, Y', strtotime($currentPeriod['start_date'])) ?></p>
                    <p><strong>End Date:</strong> <?= date('F j, Y', strtotime($currentPeriod['end_date'])) ?></p>
                    <p><strong>Payment Date:</strong> <?= $currentPeriod['payment_date'] ? date('F j, Y', strtotime($currentPeriod['payment_date'])) : 'N/A' ?></p>
                    <p><strong>Status:</strong> <?= $currentPeriod['is_closed'] ? 'Closed' : 'Open' ?></p>
                    <form action="/payroll/run" method="POST" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= $CsrfToken::getToken() ?>">
                        <input type="hidden" name="payroll_period_id" value="<?= $currentPeriod['id'] ?>">
                        <button type="submit" class="btn btn-primary">Run Payroll for this Period</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning" role="alert">
                        No active payroll period found. Please configure a new payroll period.
                    </div>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createPeriodModal">Create New Payroll Period</button>
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
