<?php
/**
 * @var string $title
 * @var array $taxBands
 * @var array $monthlyTaxBands
 * @var array $ssnitRate
 * @var array $withholdingTaxRate
 * @var int $selectedYear
 * @var array $availableTaxYears
 * @var callable $h
 * @var string $CsrfToken
 */
$this->title = $title;
?>

<div class="page-header">
    <h3 class="fw-bold mb-3"><?= $h($title) ?></h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/payroll-settings">Statutory Rates Overview</a></li>
    </ul>
</div>

<div class="page-inner">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Current Statutory Rates</div>
                </div>
                <div class="card-body">
                    <!-- SSNIT Rates -->
                    <h5 class="fw-bold mt-3">SSNIT Contribution Rates</h5>
                    <?php if ($ssnitRate): ?>
                        <p>Effective Date: <?= $h(date('M j, Y', strtotime($ssnitRate['effective_date']))) ?></p>
                        <dl class="row">
                            <dt class="col-sm-3">Employee Rate:</dt>
                            <dd class="col-sm-9"><?= $h(number_format($ssnitRate['employee_rate'] * 100, 2)) ?>%</dd>
                            <dt class="col-sm-3">Employer Rate:</dt>
                            <dd class="col-sm-9"><?= $h(number_format($ssnitRate['employer_rate'] * 100, 2)) ?>%</dd>
                            <?php if ($ssnitRate['max_contribution_cap']): ?>
                                <dt class="col-sm-3">Max Contribution Cap:</dt>
                                <dd class="col-sm-9">GHS <?= $h(number_format($ssnitRate['max_contribution_cap'], 2)) ?></dd>
                            <?php endif; ?>
                        </dl>
                    <?php else: ?>
                        <p class="text-muted">No SSNIT rates found.</p>
                    <?php endif; ?>

                    <hr class="my-4">

                    <!-- Withholding Tax Rate -->
                    <h5 class="fw-bold mt-3">Withholding Tax Rates</h5>
                    <?php if (!empty($withholdingTaxRates)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Employment Type</th>
                                        <th>Rate (%)</th>
                                        <th>Effective Date</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($withholdingTaxRates as $rate): ?>
                                        <tr>
                                            <td><?= $h($rate['employment_type']) ?></td>
                                            <td><?= $h(number_format($rate['rate'] * 100, 2)) ?></td>
                                            <td><?= $h(date('M j, Y', strtotime($rate['effective_date']))) ?></td>
                                            <td><?= $h($rate['description']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No Withholding Tax rates found.</p>
                    <?php endif; ?>

                    <hr class="my-4">

                    <!-- PAYE Tax Bands -->
                    <h5 class="fw-bold mt-3">PAYE Tax Bands</h5>
                    <form action="/payroll-settings" method="GET" class="mb-3">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <label for="taxYear" class="form-label">Select Tax Year:</label>
                                <select name="year" id="taxYear" class="form-select" onchange="this.form.submit()">
                                    <?php foreach ($availableTaxYears as $year): ?>
                                        <option value="<?= $h($year) ?>" <?= ($year == $selectedYear) ? 'selected' : '' ?>>
                                            <?= $h($year) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </form>

                    <?php if (!empty($taxBands) || !empty($monthlyTaxBands)): ?>
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Annual Tax Bands (Year <?= $h($selectedYear) ?>)</h6>
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Band Start (GHS)</th>
                                            <th>Band End (GHS)</th>
                                            <th>Rate (%)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($taxBands)): ?>
                                            <?php foreach ($taxBands as $band): ?>
                                                <tr>
                                                    <td><?= $h(number_format($band['band_start'], 2)) ?></td>
                                                    <td><?= $h($band['band_end'] ? number_format($band['band_end'], 2) : 'Above') ?></td>
                                                    <td><?= $h(number_format($band['rate'] * 100, 2)) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="3" class="text-center">No annual tax bands found for <?= $h($selectedYear) ?>.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Monthly Tax Bands (Year <?= $h($selectedYear) ?>)</h6>
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Band Start (GHS)</th>
                                            <th>Band End (GHS)</th>
                                            <th>Rate (%)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($monthlyTaxBands)): ?>
                                            <?php foreach ($monthlyTaxBands as $band): ?>
                                                <tr>
                                                    <td><?= $h(number_format($band['band_start'], 2)) ?></td>
                                                    <td><?= $h($band['band_end'] ? number_format($band['band_end'], 2) : 'Above') ?></td>
                                                    <td><?= $h(number_format($band['rate'] * 100, 2)) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="3" class="text-center">No monthly tax bands found for <?= $h($selectedYear) ?>.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No PAYE tax bands found for the selected year.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>