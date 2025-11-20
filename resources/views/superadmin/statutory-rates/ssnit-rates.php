<?php
/**
 * @var string $title
 * @var array $ssnitRates
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
        <li class="nav-item"><a href="#">SSNIT Rates</a></li>
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
                    <h4 class="card-title">SSNIT Rates</h4>
                    <button class="btn btn-primary btn-round ms-auto" data-bs-toggle="modal" data-bs-target="#addSsnitRateModal">
                        <i class="fa fa-plus"></i> Add SSNIT Rate
                    </button>
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
                                <th>Max Contribution Cap (GHS)</th>
                                <th>Effective Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ssnitRates)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No SSNIT rates found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($ssnitRates as $rate): ?>
                                    <tr>
                                        <td><?= $h($rate['id']) ?></td>
                                        <td><?= $h(number_format($rate['employee_rate'] * 100, 2)) ?></td>
                                        <td><?= $h(number_format($rate['employer_rate'] * 100, 2)) ?></td>
                                        <td><?= $h(number_format($rate['max_contribution_cap'], 2)) ?></td>
                                        <td><?= $h($rate['effective_date']) ?></td>
                                        <td>
                                            <div class="form-button-action">
                                                <button type="button" data-bs-toggle="modal" data-bs-target="#editSsnitRateModal"
                                                        class="btn btn-link btn-primary btn-lg edit-ssnit-btn"
                                                        data-id="<?= $h($rate['id']) ?>">
                                                    <i class="fa fa-edit"></i>
                                                </button>
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
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add SSNIT Rate Modal -->
<div class="modal fade" id="addSsnitRateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Add New SSNIT Rate</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/super/statutory-rates/ssnit" method="POST">
                <?= $CsrfToken::field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_ssnit_employee_rate" class="form-label">Employee Rate (%) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="add_ssnit_employee_rate" name="employee_rate" step="0.01" min="0" max="100" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_ssnit_employer_rate" class="form-label">Employer Rate (%) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="add_ssnit_employer_rate" name="employer_rate" step="0.01" min="0" max="100" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_ssnit_max_contribution_cap" class="form-label">Max Contribution Cap (GHS) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="add_ssnit_max_contribution_cap" name="max_contribution_cap" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_ssnit_effective_date" class="form-label">Effective Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="add_ssnit_effective_date" name="effective_date" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add SSNIT Rate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit SSNIT Rate Modal -->
<div class="modal fade" id="editSsnitRateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Edit SSNIT Rate</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editSsnitRateForm" method="POST">
                <?= $CsrfToken::field() ?>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_ssnit_id">
                    <div class="mb-3">
                        <label for="edit_ssnit_employee_rate" class="form-label">Employee Rate (%) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="edit_ssnit_employee_rate" name="employee_rate" step="0.01" min="0" max="100" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_ssnit_employer_rate" class="form-label">Employer Rate (%) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="edit_ssnit_employer_rate" name="employer_rate" step="0.01" min="0" max="100" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_ssnit_max_contribution_cap" class="form-label">Max Contribution Cap (GHS) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="edit_ssnit_max_contribution_cap" name="max_contribution_cap" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_ssnit_effective_date" class="form-label">Effective Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="edit_ssnit_effective_date" name="effective_date" required>
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