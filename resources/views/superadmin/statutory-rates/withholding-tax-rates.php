<?php
/**
 * @var string $title
 * @var array $withholdingTaxRates
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
        <li class="nav-item"><a href="#">Withholding Tax Rates</a></li>
    </ul>
</div>

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
                    <h4 class="card-title">Withholding Tax Rates</h4>
                    <button class="btn btn-primary btn-round ms-auto" data-bs-toggle="modal" data-bs-target="#addWhtRateModal">
                        <i class="fa fa-plus"></i> Add WHT Rate
                    </button>
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
                            <?php if (empty($withholdingTaxRates)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No Withholding Tax rates found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($withholdingTaxRates as $rate): ?>
                                    <tr>
                                        <td><?= $h($rate['id']) ?></td>
                                        <td><?= $h(number_format($rate['rate'] * 100, 2)) ?></td>
                                        <td><?= $h($rate['employment_type']) ?></td>
                                        <td><?= $h($rate['description']) ?></td>
                                        <td><?= $h($rate['effective_date']) ?></td>
                                        <td>
                                            <div class="form-button-action">
                                                <button type="button" data-bs-toggle="modal" data-bs-target="#editWhtRateModal"
                                                        class="btn btn-link btn-primary btn-lg edit-wht-btn"
                                                        data-id="<?= $h($rate['id']) ?>">
                                                    <i class="fa fa-edit"></i>
                                                </button>
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
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add WHT Rate Modal -->
<div class="modal fade" id="addWhtRateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Add New Withholding Tax Rate</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/super/statutory-rates/wht" method="POST">
                <?= $CsrfToken::field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_wht_rate" class="form-label">Rate (%) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="add_wht_rate" name="rate" step="0.01" min="0" max="100" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_wht_employment_type" class="form-label">Employment Type <span class="text-danger">*</span></label>
                        <select class="form-control" id="add_wht_employment_type" name="employment_type" required>
                            <option value="Permanent">Permanent</option>
                            <option value="Contract">Contract</option>
                            <option value="Casual">Casual</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="add_wht_description" class="form-label">Description</label>
                        <input type="text" class="form-control" id="add_wht_description" name="description" maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label for="add_wht_effective_date" class="form-label">Effective Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="add_wht_effective_date" name="effective_date" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add WHT Rate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit WHT Rate Modal -->
<div class="modal fade" id="editWhtRateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Edit Withholding Tax Rate</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editWhtRateForm" method="POST">
                <?= $CsrfToken::field() ?>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_wht_id">
                    <div class="mb-3">
                        <label for="edit_wht_rate" class="form-label">Rate (%) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="edit_wht_rate" name="rate" step="0.01" min="0" max="100" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_wht_employment_type" class="form-label">Employment Type <span class="text-danger">*</span></label>
                        <select class="form-control" id="edit_wht_employment_type" name="employment_type" required>
                            <option value="Permanent">Permanent</option>
                            <option value="Contract">Contract</option>
                            <option value="Casual">Casual</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_wht_description" class="form-label">Description</label>
                        <input type="text" class="form-control" id="edit_wht_description" name="description" maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label for="edit_wht_effective_date" class="form-label">Effective Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="edit_wht_effective_date" name="effective_date" required>
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

<script src="/assets/js/superadmin/statutory-rates.js"></script>