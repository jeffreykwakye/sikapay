<?php
// resources/views/departments/reports.php

/**
 * @var string $title
 * @var array $department
 * @var array $payrollPeriod
 * @var string $planName
 * @var object $CsrfToken
 */

$this->title = $title;

// Helper function to check if a report format is available for the current plan
function isFormatAvailable(string $planName, string $format): bool {
    $planMap = [
        'Standard' => ['pdf'],
        'Professional' => ['pdf', 'csv'],
        'Enterprise' => ['pdf', 'csv', 'excel'],
    ];
    return in_array($format, $planMap[$planName] ?? []);
}

$departmentId = $department['id'];
$periodId = $payrollPeriod['id'];

?>

<div class="page-header">
    <h3 class="fw-bold mb-3"><?= $h($title) ?></h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/departments">Departments</a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/departments/<?= $departmentId ?>/dashboard"><?= $h($department['name']) ?> Dashboard</a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><?= $h($title) ?></li>
    </ul>
</div>

<div class="page-inner">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Reports for <?= $h($department['name']) ?> (<?= $h($payrollPeriod['period_name']) ?>)</h4>
        </div>
        <div class="card-body">
            <p>Here you can generate various payroll reports specific to the <strong><?= $h($department['name']) ?></strong> department for the <strong><?= $h($payrollPeriod['period_name']) ?></strong> payroll period.</p>
            <p>Your current plan (<strong><?= $h($planName) ?></strong>) supports PDF, 
                <?php if (isFormatAvailable($planName, 'csv')): ?>CSV, <?php endif; ?>
                <?php if (isFormatAvailable($planName, 'excel')): ?>and Excel <?php endif; ?> formats for statutory reports.</p>
            <hr>

            <!-- Payslip Reports -->
            <h5>Individual Payslips</h5>
            <div class="row mb-4">
                <div class="col-md-6">
                    <p>Generate individual payslips for all staff in this department for the selected period.</p>
                </div>
                <div class="col-md-6 text-end">
                    <a href="/payroll/payslips/department/<?= $departmentId ?>/period/<?= $periodId ?>" class="btn btn-primary me-2">View Payslips</a>
                    <a href="/reports/payslips/zip/department/<?= $departmentId ?>/period/<?= $periodId ?>" class="btn btn-info">Download All (ZIP)</a>
                </div>
            </div>
            <hr>

            <!-- PAYE Report -->
            <h5>PAYE Report</h5>
            <div class="row mb-4">
                <div class="col-md-6">
                    <p>Generate the PAYE (Pay As You Earn) report for the department.</p>
                </div>
                <div class="col-md-6 text-end">
                    <a href="/reports/paye/pdf/department/<?= $departmentId ?>/period/<?= $periodId ?>" class="btn btn-danger me-2">PDF</a>
                    <?php if (isFormatAvailable($planName, 'csv')): ?>
                        <a href="/reports/paye/csv/department/<?= $departmentId ?>/period/<?= $periodId ?>" class="btn btn-secondary me-2">CSV</a>
                    <?php endif; ?>
                    <?php if (isFormatAvailable($planName, 'excel')): ?>
                        <a href="/reports/paye/excel/department/<?= $departmentId ?>/period/<?= $periodId ?>" class="btn btn-success me-2">Excel</a>
                    <?php endif; ?>
                </div>
            </div>
            <hr>

            <!-- SSNIT Report -->
            <h5>SSNIT Report</h5>
            <div class="row mb-4">
                <div class="col-md-6">
                    <p>Generate the SSNIT (Social Security and National Insurance Trust) report for the department.</p>
                </div>
                <div class="col-md-6 text-end">
                    <a href="/reports/ssnit/pdf/department/<?= $departmentId ?>/period/<?= $periodId ?>" class="btn btn-danger me-2">PDF</a>
                    <?php if (isFormatAvailable($planName, 'csv')): ?>
                        <a href="/reports/ssnit/csv/department/<?= $departmentId ?>/period/<?= $periodId ?>" class="btn btn-secondary me-2">CSV</a>
                    <?php endif; ?>
                    <?php if (isFormatAvailable($planName, 'excel')): ?>
                        <a href="/reports/ssnit/excel/department/<?= $departmentId ?>/period/<?= $periodId ?>" class="btn btn-success me-2">Excel</a>
                    <?php endif; ?>
                </div>
            </div>
            <hr>

            <!-- Bank Advice Report -->
            <h5>Bank Advice Report</h5>
            <div class="row mb-4">
                <div class="col-md-6">
                    <p>Generate the Bank Advice report for the department.</p>
                </div>
                <div class="col-md-6 text-end">
                    <a href="/reports/bank-advice/pdf/department/<?= $departmentId ?>/period/<?= $periodId ?>" class="btn btn-danger me-2">PDF</a>
                    <?php if (isFormatAvailable($planName, 'csv')): ?>
                        <a href="/reports/bank-advice/csv/department/<?= $departmentId ?>/period/<?= $periodId ?>" class="btn btn-secondary me-2">CSV</a>
                    <?php endif; ?>
                    <?php if (isFormatAvailable($planName, 'excel')): ?>
                        <a href="/reports/bank-advice/excel/department/<?= $departmentId ?>/period/<?= $periodId ?>" class="btn btn-success me-2">Excel</a>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>
