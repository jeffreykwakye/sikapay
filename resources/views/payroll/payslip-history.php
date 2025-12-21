<?php
/**
 * @var string $title
 * @var array $payrollPeriods An array of all payroll periods for the tenant.
 * @var callable $h Helper function for HTML escaping.
 */

$this->title = $title;

// Fallback for helper if not provided by the master layout
if (!isset($h)) {
    $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>

<div class="page-header">
    <h3 class="fw-bold mb-3">Payslip History</h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/dashboard">Dashboard</a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="#">Payslips</a></li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="card-title">Generated Payslips by Period</div>
            </div>
            <div class="card-body">
                <?php if (empty($payrollPeriods)): ?>
                <div class="alert alert-info" role="alert">
                    No payroll periods have been processed yet.
                </div>
                <?php else: ?>
                <div class="accordion" id="payslipPeriodsAccordion">
                    <?php foreach ($payrollPeriods as $period): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading<?= $period['id'] ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $period['id'] ?>" aria-expanded="false" aria-controls="collapse<?= $period['id'] ?>">
                                <?= $h($period['period_name']) ?> (<?= date('F Y', strtotime($period['start_date'])) ?>)
                                <span class="badge <?= $period['is_closed'] ? 'bg-success' : 'bg-warning' ?> ms-2">
                                    <?= $period['is_closed'] ? 'Closed' : 'Open' ?>
                                </span>
                            </button>
                        </h2>
                        <div id="collapse<?= $period['id'] ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $period['id'] ?>" data-bs-parent="#payslipPeriodsAccordion">
                            <div class="accordion-body">
                                <?php if (isset($payslips)): ?>
                                    <?php if (!empty($payslips)): ?>
                                        <div class="payslip-list mt-3">
                                            <ul class="list-group">
                                                <?php foreach ($payslips as $payslip): ?>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <span><?= $h($payslip['first_name'] . ' ' . $payslip['last_name']) ?> (<?= $h($payslip['employee_id']) ?>)</span>
                                                        <a href="/payroll/payslips/download/<?= $payslip['id'] ?>" class="btn btn-sm btn-primary"><i class="icon-cloud-download"></i> Download Payslip</a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">No payslips found for this department in this period.</p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p>Click 'View Payslips' to load available payslips for this period.</p>
                                    <button class="btn btn-sm btn-primary view-payslips-btn" data-period-id="<?= $period['id'] ?>">View Payslips</button>
                                    <div id="payslips-content-<?= $period['id'] ?>" class="mt-3"></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script src="/assets/js/employees/payslips.js"></script>
