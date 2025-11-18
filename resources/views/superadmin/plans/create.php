<?php
/**
 * @var string $title
 * @var array $features
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
        <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/super/dashboard">Super Admin</a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/super/plans">Plans</a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="#">Create</a></li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Plan Details</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="icon-close me-2"></i><?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="/super/plans" method="POST">
                    <?= $CsrfToken::field() ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Plan Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= $v('name') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="price_ghs" class="form-label">Price (GHS/month) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="price_ghs" name="price_ghs" step="0.01" min="0" value="<?= $v('price_ghs') ?>" required>
                        </div>
                    </div>

                    <h5 class="mt-4 mb-3 border-bottom pb-2 text-primary">Limits</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="employee_limit" class="form-label">Employee Limit <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="employee_limit" name="employee_limit" min="0" value="<?= $v('employee_limit') ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="hr_manager_seats" class="form-label">HR Manager Seats <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="hr_manager_seats" name="hr_manager_seats" min="0" value="<?= $v('hr_manager_seats') ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="accountant_seats" class="form-label">Accountant Seats <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="accountant_seats" name="accountant_seats" min="0" value="<?= $v('accountant_seats') ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="tenant_admin_seats" class="form-label">Tenant Admin Seats <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="tenant_admin_seats" name="tenant_admin_seats" min="0" value="<?= $v('tenant_admin_seats') ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="auditor_seats" class="form-label">Auditor Seats <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="auditor_seats" name="auditor_seats" min="0" value="<?= $v('auditor_seats') ?>" required>
                        </div>
                    </div>

                    <h5 class="mt-4 mb-3 border-bottom pb-2 text-primary">Features</h5>
                    <div class="row">
                        <?php foreach ($features as $feature): ?>
                            <?php 
                                // Skip limit-based features as they are handled above
                                if (in_array($feature['key_name'], ['employee_limit', 'hr_manager_seats', 'accountant_seats', 'tenant_admin_seats', 'auditor_seats'])) {
                                    continue;
                                }
                                $isChecked = in_array($feature['id'], $input['features'] ?? []);
                            ?>
                            <div class="col-md-4 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="features[]" value="<?= $h($feature['id']) ?>" id="feature_<?= $h($feature['id']) ?>" <?= $isChecked ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="feature_<?= $h($feature['id']) ?>">
                                        <?= $h($feature['description']) ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="card-action text-end">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> Create Plan</button>
                        <a href="/super/plans" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
