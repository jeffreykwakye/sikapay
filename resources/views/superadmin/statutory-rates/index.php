<?php
/**
 * @var string $title
 * @var array $ssnitRates
 * @var array $withholdingTaxRates
 * @var string|null $success
 * @var string|null $error
 * @var callable $h
 */
?>

<div class="page-header">
    <h3 class="fw-bold mb-3"><?= $h($title) ?></h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/super/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/super/dashboard">Super Admin</a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="#">Statutory Rates</a></li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="icon-check me-2"></i><?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="icon-close me-2"></i><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">SSNIT Rates</h4>
                    <a href="/super/statutory-rates/ssnit/create" class="btn btn-primary btn-round ms-auto">
                        <i class="fa fa-plus"></i> Add SSNIT Rate
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="ssnit_rates_table" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Employee Rate (%)</th>
                                <th>Employer Rate (%)</th>
                                <th>Max Contribution Limit (GHS)</th>
                                <th>Effective Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ssnitRates as $rate): ?>
                                <tr>
                                    <td><?= $h($rate['id']) ?></td>
                                    <td><?= $h(number_format($rate['employee_rate'] * 100, 2)) ?></td>
                                    <td><?= $h(number_format($rate['employer_rate'] * 100, 2)) ?></td>
                                    <td><?= $h(number_format($rate['max_contribution_limit'], 2)) ?></td>
                                    <td><?= $h($rate['effective_date']) ?></td>
                                    <td>
                                        <div class="form-button-action">
                                            <a href="/super/statutory-rates/ssnit/<?= $h($rate['id']) ?>/edit" data-bs-toggle="tooltip" title="Edit" class="btn btn-link btn-primary btn-lg">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <form action="/super/statutory-rates/ssnit/<?= $h($rate['id']) ?>/delete" method="POST" class="d-inline">
                                                <?= $CsrfToken::field() ?>
                                                <button type="submit" data-bs-toggle="tooltip" title="Delete" class="btn btn-link btn-danger" onclick="return confirm('Are you sure you want to delete this SSNIT rate?');">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">Withholding Tax Rates</h4>
                    <a href="/super/statutory-rates/wht/create" class="btn btn-primary btn-round ms-auto">
                        <i class="fa fa-plus"></i> Add WHT Rate
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="wht_rates_table" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Rate (%)</th>
                                <th>Employment Type</th>
                                <th>Description</th>
                                <th>Effective Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($withholdingTaxRates as $rate): ?>
                                <tr>
                                    <td><?= $h($rate['id']) ?></td>
                                    <td><?= $h(number_format($rate['rate'] * 100, 2)) ?></td>
                                    <td><?= $h($rate['employment_type']) ?></td>
                                    <td><?= $h($rate['description']) ?></td>
                                    <td><?= $h($rate['effective_date']) ?></td>
                                    <td>
                                        <div class="form-button-action">
                                            <a href="/super/statutory-rates/wht/<?= $h($rate['id']) ?>/edit" data-bs-toggle="tooltip" title="Edit" class="btn btn-link btn-primary btn-lg">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <form action="/super/statutory-rates/wht/<?= $h($rate['id']) ?>/delete" method="POST" class="d-inline">
                                                <?= $CsrfToken::field() ?>
                                                <button type="submit" data-bs-toggle="tooltip" title="Delete" class="btn btn-link btn-danger" onclick="return confirm('Are you sure you want to delete this Withholding Tax rate?');">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            </form>
                                        </div>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize DataTables for SSNIT Rates
        if (document.getElementById('ssnit_rates_table')) {
            $('#ssnit_rates_table').DataTable({
                "pageLength": 10,
                "columns": [
                    null, // ID
                    null, // Employee Rate
                    null, // Employer Rate
                    null, // Max Contribution Limit
                    null, // Effective Date
                    { "orderable": false } // Actions
                ]
            });
        }

        // Initialize DataTables for Withholding Tax Rates
        if (document.getElementById('wht_rates_table')) {
            $('#wht_rates_table').DataTable({
                "pageLength": 10,
                "columns": [
                    null, // ID
                    null, // Rate
                    null, // Employment Type
                    null, // Description
                    null, // Effective Date
                    { "orderable": false } // Actions
                ]
            });
        }
    });
</script>