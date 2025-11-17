<?php
$this->title = $title;
?>

<div class="page-header">
    <h3 class="fw-bold mb-3">Statutory & Bank Reports</h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/dashboard">Dashboard</a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="#">Reports</a></li>
    </ul>
</div>

<div class="page-inner">
    <div class="row">
        <div class="col-md-12">
            <?php if (empty($periods)): ?>
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">No Payrolls Found</h4>
                        <p class="card-text">There are no completed payroll periods to display reports for. Please run a payroll to generate reports.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="accordion" id="reportsAccordion">
                    <?php foreach ($periods as $index => $period): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading-<?= $id($period['id']) ?>">
                                <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= $id($period['id']) ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="collapse-<?= $id($period['id']) ?>">
                                    <span class="fw-bold text-primary me-3"><?= $h($period['period_name']) ?></span>
                                    <span class="ms-auto text-muted">
                                        Period: <?= date('M j, Y', strtotime($period['start_date'])) ?> - <?= date('M j, Y', strtotime($period['end_date'])) ?> | 
                                        Status: <?= $period['is_closed'] ? '<span class="badge bg-success text-white">Closed</span>' : '<span class="badge bg-warning text-dark">Open</span>' ?>
                                    </span>
                                </button>
                            </h2>
                            <div id="collapse-<?= $id($period['id']) ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" aria-labelledby="heading-<?= $id($period['id']) ?>" data-bs-parent="#reportsAccordion">
                                <div class="accordion-body">
                                    <?php if (!$period['is_closed']): ?>
                                        <p class="text-muted">Reports will be available once this payroll period is closed.</p>
                                    <?php else: ?>
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Report Name</th>
                                                    <th scope="col">Description</th>
                                                    <th scope="col" class="text-end">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>PAYE Report (GRA)</td>
                                                    <td>Monthly PAYE tax deductions for all employees.</td>
                                                    <td class="text-end">
                                                        <a href="/reports/paye/pdf/<?= $id($period['id']) ?>" class="btn btn-sm btn-primary">PDF</a>
                                                        <a href="/reports/paye/excel/<?= $id($period['id']) ?>" class="btn btn-sm btn-secondary">Excel</a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>SSNIT Report</td>
                                                    <td>Monthly SSNIT contributions for all employees.</td>
                                                    <td class="text-end">
                                                        <a href="/reports/ssnit/pdf/<?= $id($period['id']) ?>" class="btn btn-sm btn-primary">PDF</a>
                                                        <a href="/reports/ssnit/excel/<?= $id($period['id']) ?>" class="btn btn-sm btn-secondary">Excel</a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Bank Advice</td>
                                                    <td>Net salary payments to be made to employees' bank accounts.</td>
                                                    <td class="text-end">
                                                        <a href="/reports/bank-advice/pdf/<?= $id($period['id']) ?>" class="btn btn-sm btn-primary">PDF</a>
                                                        <a href="/reports/bank-advice/excel/<?= $id($period['id']) ?>" class="btn btn-sm btn-secondary">Excel</a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>All Payslips</td>
                                                    <td>A single ZIP file containing all individual employee payslips for this period.</td>
                                                    <td class="text-end">
                                                        <a href="/reports/payslips/zip/<?= $id($period['id']) ?>" class="btn btn-sm btn-success">Download (ZIP)</a>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
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

