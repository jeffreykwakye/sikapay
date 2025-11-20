<?php
/**
 * @var string $title
 * @var array $annualTaxBands
 * @var array $monthlyTaxBands
 * @var int $selectedYear
 * @var array $availableTaxYears
 * @var callable $h
 * @var object $CsrfToken
 */
$this->title = $title;
?>

<div class="page-header">
    <h3 class="fw-bold mb-3"><?= $h($title) ?></h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/super/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/super/dashboard">Super Admin</a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="#">PAYE Tax Bands</a></li>
    </ul>
</div>

<div class="page-inner">
    <div class="row">
        <div class="col-md-12">
            <?php if (isset($_SESSION['flash_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="icon-check me-2"></i><?= $h($_SESSION['flash_success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['flash_success']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['flash_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="icon-close me-2"></i><?= $h($_SESSION['flash_error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <h4 class="card-title">Manage PAYE Tax Bands</h4>
                        <button class="btn btn-primary btn-round ms-auto" data-bs-toggle="modal" data-bs-target="#addTaxBandModal">
                            <i class="fa fa-plus"></i> Add Tax Band
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form action="/super/statutory-rates/paye" method="GET" class="mb-3">
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

                    <?php if (!empty($annualTaxBands) || !empty($monthlyTaxBands)): ?>
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Annual Tax Bands (Year <?= $h($selectedYear) ?>)</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Band Start (GHS)</th>
                                                <th>Band End (GHS)</th>
                                                <th>Rate (%)</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($annualTaxBands)): ?>
                                                <?php foreach ($annualTaxBands as $band): ?>
                                                    <tr>
                                                        <td><?= $h($band['id']) ?></td>
                                                        <td><?= $h(number_format($band['band_start'], 2)) ?></td>
                                                        <td><?= $h($band['band_end'] ? number_format($band['band_end'], 2) : 'Above') ?></td>
                                                        <td><?= $h(number_format($band['rate'] * 100, 2)) ?></td>
                                                        <td>
                                                            <div class="form-button-action">
                                                                <button type="button" data-bs-toggle="modal" data-bs-target="#editTaxBandModal"
                                                                        class="btn btn-link btn-primary btn-lg edit-taxband-btn"
                                                                        data-id="<?= $h($band['id']) ?>"
                                                                        data-tax-year="<?= $h($band['tax_year']) ?>"
                                                                        data-band-start="<?= $h($band['band_start']) ?>"
                                                                        data-band-end="<?= $h($band['band_end']) ?>"
                                                                        data-rate="<?= $h($band['rate']) ?>"
                                                                        data-is-annual="1">
                                                                    <i class="fa fa-edit"></i>
                                                                </button>
                                                                <form action="/super/statutory-rates/paye/<?= $h($band['id']) ?>/delete" method="POST" class="d-inline">
                                                                    <?= $CsrfToken::field() ?>
                                                                    <button type="submit" data-bs-toggle="tooltip" title="Delete" class="btn btn-link btn-danger" onclick="return confirm('Are you sure you want to delete this tax band?');">
                                                                        <i class="fa fa-times"></i>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr><td colspan="5" class="text-center">No annual tax bands found for <?= $h($selectedYear) ?>.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Monthly Tax Bands (Year <?= $h($selectedYear) ?>)</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Band Start (GHS)</th>
                                                <th>Band End (GHS)</th>
                                                <th>Rate (%)</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($monthlyTaxBands)): ?>
                                                <?php foreach ($monthlyTaxBands as $band): ?>
                                                    <tr>
                                                        <td><?= $h($band['id']) ?></td>
                                                        <td><?= $h(number_format($band['band_start'], 2)) ?></td>
                                                        <td><?= $h($band['band_end'] ? number_format($band['band_end'], 2) : 'Above') ?></td>
                                                        <td><?= $h(number_format($band['rate'] * 100, 2)) ?></td>
                                                        <td>
                                                            <div class="form-button-action">
                                                                <button type="button" data-bs-toggle="modal" data-bs-target="#editTaxBandModal"
                                                                        class="btn btn-link btn-primary btn-lg edit-taxband-btn"
                                                                        data-id="<?= $h($band['id']) ?>"
                                                                        data-tax-year="<?= $h($band['tax_year']) ?>"
                                                                        data-band-start="<?= $h($band['band_start']) ?>"
                                                                        data-band-end="<?= $h($band['band_end']) ?>"
                                                                        data-rate="<?= $h($band['rate']) ?>"
                                                                        data-is-annual="0">
                                                                    <i class="fa fa-edit"></i>
                                                                </button>
                                                                <form action="/super/statutory-rates/paye/<?= $h($band['id']) ?>/delete" method="POST" class="d-inline">
                                                                    <?= $CsrfToken::field() ?>
                                                                    <button type="submit" data-bs-toggle="tooltip" title="Delete" class="btn btn-link btn-danger" onclick="return confirm('Are you sure you want to delete this tax band?');">
                                                                        <i class="fa fa-times"></i>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr><td colspan="5" class="text-center">No monthly tax bands found for <?= $h($selectedYear) ?>.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
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

<!-- Add Tax Band Modal -->
<div class="modal fade" id="addTaxBandModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Add New Tax Band</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/super/statutory-rates/paye" method="POST">
                <?= $CsrfToken::field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_tax_year" class="form-label">Tax Year <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="add_tax_year" name="tax_year" min="1900" value="<?= $h(date('Y')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_band_start" class="form-label">Band Start (GHS) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="add_band_start" name="band_start" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_band_end" class="form-label">Band End (GHS)</label>
                        <input type="number" class="form-control" id="add_band_end" name="band_end" step="0.01" min="0">
                        <small class="form-text text-muted">Leave blank for the highest band.</small>
                    </div>
                    <div class="mb-3">
                        <label for="add_rate" class="form-label">Rate (%) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="add_rate" name="rate" step="0.0001" min="0" max="100" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_is_annual" class="form-label">Band Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="add_is_annual" name="is_annual" required>
                            <option value="1">Annual</option>
                            <option value="0">Monthly</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Tax Band</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Tax Band Modal -->
<div class="modal fade" id="editTaxBandModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Edit Tax Band</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editTaxBandForm" method="POST">
                <?= $CsrfToken::field() ?>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_tax_band_id">
                    <div class="mb-3">
                        <label for="edit_tax_year" class="form-label">Tax Year <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="edit_tax_year" name="tax_year" min="1900" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_band_start" class="form-label">Band Start (GHS) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="edit_band_start" name="band_start" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_band_end" class="form-label">Band End (GHS)</label>
                        <input type="number" class="form-control" id="edit_band_end" name="band_end" step="0.01" min="0">
                        <small class="form-text text-muted">Leave blank for the highest band.</small>
                    </div>
                    <div class="mb-3">
                        <label for="edit_rate" class="form-label">Rate (%) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="edit_rate" name="rate" step="0.0001" min="0" max="100" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_is_annual" class="form-label">Band Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="edit_is_annual" name="is_annual" required>
                            <option value="1">Annual</option>
                            <option value="0">Monthly</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteTaxBandConfirmModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deleteTaxBandForm" method="POST">
                <?= $CsrfToken::field() ?>
                <div class="modal-body">
                    <p>Are you sure you want to delete this tax band?</p>
                    <p class="fw-bold text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/assets/js/superadmin/paye-tax-bands.js"></script>
