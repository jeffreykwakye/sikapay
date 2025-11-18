<?php
/**
 * @var string $title
 * @var array $input
 * @var string|null $error
 * @var callable $h
 * @var object $CsrfToken
 */

$v = fn($key, $default = '') => $h($input[$key] ?? $default);
?>

<div class="page-header">
    <h3 class="fw-bold mb-3"><?= $h($title) ?></h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/super/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/super/dashboard">Super Admin</a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/super/statutory-rates">Statutory Rates</a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="#">Create Withholding Tax Rate</a></li>
    </ul>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">New Withholding Tax Rate Details</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="icon-close me-2"></i><?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="/super/statutory-rates/wht" method="POST">
                    <?= $CsrfToken::field() ?>

                    <div class="mb-3">
                        <label for="rate" class="form-label">Rate (%) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="rate" name="rate" step="0.0001" min="0" max="1" value="<?= $v('rate') ?>" required>
                        <small class="form-text text-muted">Enter as a decimal (e.g., 0.05 for 5%)</small>
                    </div>
                    <div class="mb-3">
                        <label for="employment_type" class="form-label">Employment Type <span class="text-danger">*</span></label>
                        <select class="form-control" id="employment_type" name="employment_type" required>
                            <option value="">Select Type</option>
                            <option value="Contract" <?= $v('employment_type') == 'Contract' ? 'selected' : '' ?>>Contract</option>
                            <option value="Casual-Worker" <?= $v('employment_type') == 'Casual-Worker' ? 'selected' : '' ?>>Casual Worker</option>
                            <!-- Add other types as needed -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <input type="text" class="form-control" id="description" name="description" value="<?= $v('description') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="effective_date" class="form-label">Effective Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="effective_date" name="effective_date" value="<?= $v('effective_date') ?>" required>
                    </div>

                    <div class="card-action text-end">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> Create WHT Rate</button>
                        <a href="/super/statutory-rates" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
