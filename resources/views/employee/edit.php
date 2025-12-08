<?php 
// resources/views/employee/edit.php

$this->title = $title; // e.g., 'Edit Employee: John Doe'
$e = $employee; // Existing employee data
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Edit Employee: <?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name']) ?></h1>
    <a href="/employees/<?= $e['user_id'] ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Profile
    </a>
</div>

<div id="flash-message-container" class="mb-3"></div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Edit Employee Details</div>
    </div>
    <div class="card-body">
        <ul class="nav nav-pills nav-secondary" id="pills-tab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="pills-personal-tab" data-bs-toggle="pill" href="#pills-personal" role="tab" aria-controls="pills-personal" aria-selected="true">Personal</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="pills-employment-tab" data-bs-toggle="pill" href="#pills-employment" role="tab" aria-controls="pills-employment" aria-selected="false">Employment</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="pills-statutory-tab" data-bs-toggle="pill" href="#pills-statutory" role="tab" aria-controls="pills-statutory" aria-selected="false">Statutory</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="pills-bank-tab" data-bs-toggle="pill" href="#pills-bank" role="tab" aria-controls="pills-bank" aria-selected="false">Bank & Payroll</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="pills-salary-tab" data-bs-toggle="pill" href="#pills-salary" role="tab" aria-controls="pills-salary" aria-selected="false">Salary</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="pills-emergency-tab" data-bs-toggle="pill" href="#pills-emergency" role="tab" aria-controls="pills-emergency" aria-selected="false">Emergency</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="pills-role-tab" data-bs-toggle="pill" href="#pills-role" role="tab" aria-controls="pills-role" aria-selected="false">Role & Status</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="pills-permissions-tab" data-bs-toggle="pill" href="#pills-permissions" role="tab" aria-controls="pills-permissions" aria-selected="false">Permissions</a>
            </li>
        </ul>
        <div class="tab-content mt-2 mb-3" id="pills-tabContent">
            <!-- Personal Details Tab -->
            <div class="tab-pane fade show active" id="pills-personal" role="tabpanel" aria-labelledby="pills-personal-tab">
                <form method="POST" action="/employees/<?= $e['user_id'] ?>/personal">
                    <?php if (isset($CsrfToken)): ?>
                        <input type="hidden" name="csrf_token" value="<?= $CsrfToken::getToken() ?>">
                    <?php endif; ?>
                    <h5 class="mb-3 mt-4">Personal & Account Details</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?= htmlspecialchars($e['first_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="other_name" class="form-label">Other Name(s)</label>
                            <input type="text" class="form-control" id="other_name" name="other_name" value="<?= htmlspecialchars($e['other_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?= htmlspecialchars($e['last_name'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Company Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($e['email'] ?? '') ?>" required>
                            <small class="form-text text-muted">Email cannot be changed.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Personal Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($e['phone'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="date_of_birth" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?= date('Y-m-d', strtotime($e['date_of_birth'])) ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                            <select class="form-select" id="gender" name="gender" required>
                                <?php $genders = ['Male', 'Female', 'Other']; foreach ($genders as $gender): ?>
                                    <option value="<?= $gender ?>" <?= ($e['gender'] ?? '') === $gender ? 'selected' : '' ?>><?= $gender ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="marital_status" class="form-label">Marital Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="marital_status" name="marital_status" required>
                                <?php $maritals = ['Single', 'Married', 'Divorced', 'Widowed']; foreach ($maritals as $status): ?>
                                    <option value="<?= $status ?>" <?= ($e['marital_status'] ?? '') === $status ? 'selected' : '' ?>><?= $status ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="nationality" class="form-label">Nationality</label>
                            <input type="text" class="form-control" id="nationality" name="nationality" value="<?= htmlspecialchars($e['nationality'] ?? 'Ghanaian') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="home_address" class="form-label">Residential Address</label>
                        <textarea class="form-control" id="home_address" name="home_address" rows="2"><?= htmlspecialchars($e['home_address'] ?? '') ?></textarea>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Save Personal Details</button>
                    </div>
                </form>
            </div>

            <!-- Employment Details Tab -->
            <div class="tab-pane fade" id="pills-employment" role="tabpanel" aria-labelledby="pills-employment-tab">
                <form method="POST" action="/employees/<?= $e['user_id'] ?>/employment">
                    <?php if (isset($CsrfToken)): ?>
                        <input type="hidden" name="csrf_token" value="<?= $CsrfToken::getToken() ?>">
                    <?php endif; ?>
                    <h5 class="mb-3 mt-4">Employment Details</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="employee_id" class="form-label">Employee ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="employee_id" name="employee_id" value="<?= htmlspecialchars($e['employee_id'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="hire_date" class="form-label">Hire Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="hire_date" name="hire_date" value="<?= date('Y-m-d', strtotime($e['hire_date'])) ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="employment_type" class="form-label">Employment Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="employment_type" name="employment_type" required>
                                <?php $types = ['Full-Time', 'Part-Time', 'Contract', 'Intern']; foreach ($types as $type): ?>
                                    <option value="<?= $type ?>" <?= ($e['employment_type'] ?? '') === $type ? 'selected' : '' ?>><?= $type ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="department_id" class="form-label">Department <span class="text-danger">*</span></label>
                            <select class="form-select" id="department_id" name="department_id" required>
                                <option value="">-- Select Department --</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['id'] ?>" <?= ($e['department_id'] ?? 0) == $dept['id'] ? 'selected' : '' ?>><?= htmlspecialchars($dept['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="position_id" class="form-label">Position <span class="text-danger">*</span></label>
                            <select class="form-select" id="position_id" name="current_position_id" required>
                                <option value="">-- Select Position --</option>
                                <?php foreach ($positions as $pos): ?>
                                    <option value="<?= $pos['id'] ?>" data-department-id="<?= htmlspecialchars($pos['department_id'] ?? '') ?>" <?= ($e['position_id'] ?? 0) == $pos['id'] ? 'selected' : '' ?>><?= htmlspecialchars($pos['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Change department to update position list.</small>
                        </div>
                    </div>
                     <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="termination_date" class="form-label">Termination Date</label>
                            <input type="date" class="form-control" id="termination_date" name="termination_date" value="<?= !empty($e['termination_date']) ? date('Y-m-d', strtotime($e['termination_date'])) : '' ?>">
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Save Employment Details</button>
                    </div>
                </form>
            </div>

            <!-- Statutory Details Tab -->
            <div class="tab-pane fade" id="pills-statutory" role="tabpanel" aria-labelledby="pills-statutory-tab">
                <form method="POST" action="/employees/<?= $e['user_id'] ?>/statutory">
                    <?php if (isset($CsrfToken)): ?>
                        <input type="hidden" name="csrf_token" value="<?= $CsrfToken::getToken() ?>">
                    <?php endif; ?>
                    <h5 class="mb-3 mt-4">Compliance (Ghana)</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="ssnit_number" class="form-label">SSNIT Number</label>
                            <input type="text" class="form-control" id="ssnit_number" name="ssnit_number" value="<?= htmlspecialchars($e['ssnit_number'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="tin_number" class="form-label">TIN Number</label>
                            <input type="text" class="form-control" id="tin_number" name="tin_number" value="<?= htmlspecialchars($e['tin_number'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="id_card_type" class="form-label">Primary ID Type</label>
                            <select class="form-select" id="id_card_type" name="id_card_type">
                                <?php $id_types = ['Ghana Card', 'Voter ID', 'Passport', 'Other']; foreach ($id_types as $type): ?>
                                    <option value="<?= $type ?>" <?= ($e['id_card_type'] ?? '') === $type ? 'selected' : '' ?>><?= $type ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="id_card_number" class="form-label">ID Card Number</label>
                            <input type="text" class="form-control" id="id_card_number" name="id_card_number" value="<?= htmlspecialchars($e['id_card_number'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Save Statutory Details</button>
                    </div>
                </form>
            </div>

            <!-- Bank & Payroll Tab -->
            <div class="tab-pane fade" id="pills-bank" role="tabpanel" aria-labelledby="pills-bank-tab">
                <form method="POST" action="/employees/<?= $e['user_id'] ?>/bank">
                    <?php if (isset($CsrfToken)): ?>
                        <input type="hidden" name="csrf_token" value="<?= $CsrfToken::getToken() ?>">
                    <?php endif; ?>
                    <h5 class="mb-3 mt-4">Bank & Payroll Details</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-select" id="payment_method" name="payment_method" required>
                                <?php $payments = ['Bank Transfer', 'Mobile Money', 'Cash']; foreach ($payments as $method): ?>
                                    <option value="<?= $method ?>" <?= ($e['payment_method'] ?? '') === $method ? 'selected' : '' ?>><?= $method ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                             <label for="is_payroll_eligible" class="form-label">Payroll Eligibility</label>
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_payroll_eligible" value="0"> <!-- Hidden field for unchecked state -->
                                <input class="form-check-input" type="checkbox" role="switch" id="is_payroll_eligible" name="is_payroll_eligible" value="1" <?= ($e['is_payroll_eligible'] ?? 0) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_payroll_eligible">Eligible for Payroll Runs</label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="bank_name" class="form-label">Bank Name</label>
                            <input type="text" class="form-control" id="bank_name" name="bank_name" value="<?= htmlspecialchars($e['bank_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="bank_branch" class="form-label">Bank Branch</label>
                            <input type="text" class="form-control" id="bank_branch" name="bank_branch" value="<?= htmlspecialchars($e['bank_branch'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="bank_account_name" class="form-label">Account Name</label>
                            <input type="text" class="form-control" id="bank_account_name" name="bank_account_name" value="<?= htmlspecialchars($e['bank_account_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="bank_account_number" class="form-label">Account Number</label>
                            <input type="text" class="form-control" id="bank_account_number" name="bank_account_number" value="<?= htmlspecialchars($e['bank_account_number'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Save Bank Details</button>
                    </div>
                </form>
            </div>

            <!-- Salary Tab -->
            <div class="tab-pane fade" id="pills-salary" role="tabpanel" aria-labelledby="pills-salary-tab">
                <form method="POST" action="/employees/<?= $e['user_id'] ?>/salary">
                    <?php if (isset($CsrfToken)): ?>
                        <input type="hidden" name="csrf_token" value="<?= $CsrfToken::getToken() ?>">
                    <?php endif; ?>
                    <h5 class="mb-3 mt-4">Current Monthly Base Salary</h5>
                    <div class="alert alert-info" role="alert">
                        Current Monthly Base Salary: <strong>GHS <?= number_format($e['current_salary_ghs'] ?? 0, 2) ?></strong>
                    </div>
                    <h5 class="mb-3 mt-4">Update Monthly Base Salary</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="new_salary" class="form-label">New Monthly Base Salary (GHS) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="new_salary" name="new_salary" value="<?= htmlspecialchars($e['current_salary_ghs'] ?? '') ?>" required min="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="effective_date" class="form-label">Effective Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="effective_date" name="effective_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="1"></textarea>
                            <small class="form-text text-muted">Reason for salary change (e.g., Promotion, Annual Review).</small>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Save Salary Details</button>
                    </div>
                </form>
            </div>

            <!-- Emergency Contact Tab -->
            <div class="tab-pane fade" id="pills-emergency" role="tabpanel" aria-labelledby="pills-emergency-tab">
                <form method="POST" action="/employees/<?= $e['user_id'] ?>/emergency">
                    <?php if (isset($CsrfToken)): ?>
                        <input type="hidden" name="csrf_token" value="<?= $CsrfToken::getToken() ?>">
                    <?php endif; ?>
                    <h5 class="mb-3 mt-4">Emergency Contact</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="emergency_contact_name" class="form-label">Emergency Contact Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name" value="<?= htmlspecialchars($e['emergency_contact_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="emergency_contact_phone" class="form-label">Emergency Contact Phone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="emergency_contact_phone" name="emergency_contact_phone" value="<?= htmlspecialchars($e['emergency_contact_phone'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Save Emergency Details</button>
                    </div>
                </form>
            </div>

            <!-- Role & Status Tab -->
            <div class="tab-pane fade" id="pills-role" role="tabpanel" aria-labelledby="pills-role-tab">
                <form method="POST" action="/employees/<?= $e['user_id'] ?>/role">
                    <?php if (isset($CsrfToken)): ?>
                        <input type="hidden" name="csrf_token" value="<?= $CsrfToken::getToken() ?>">
                    <?php endif; ?>
                    <h5 class="mb-3 mt-4">Role & Account Status</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="role_id" class="form-label">Employee Role <span class="text-danger">*</span></label>
                            <select class="form-select" id="role_id" name="role_id" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['id'] ?>" <?= ($e['role_id'] ?? 0) == $role['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $role['name']))) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="is_active" class="form-label">Account Status</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" <?= ($e['is_active'] ?? 0) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">Account is Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Save Role & Status</button>
                    </div>
                </form>
            </div>

            <!-- Permissions Tab -->
            <div class="tab-pane fade" id="pills-permissions" role="tabpanel" aria-labelledby="pills-permissions-tab">
                <h5 class="mb-3 mt-4">Individual Permissions Override</h5>
                <p class="text-muted">Permissions inherited from the role are marked. Check a box to explicitly grant, uncheck to explicitly deny, overriding the role's default.</p>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th style="width: 5%;">Override</th>
                                <th>Permission</th>
                                <th>Description</th>
                                <th style="width: 15%;">Role Default</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allPermissions as $permission):
                                $permissionId = $permission['id'];
                                $isRoleDefault = in_array($permissionId, $rolePermissions);
                                
                                $isChecked = false; // Default to unchecked

                                if (array_key_exists($permissionId, $individualPermissions)) {
                                    // Individual override exists, use its value
                                    $isChecked = $individualPermissions[$permissionId];
                                } else {
                                    // No individual override, use role default
                                    $isChecked = $isRoleDefault;
                                }
                            ?>
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <!-- Hidden field for unchecked state is no longer needed with individual AJAX updates -->
                                            <input class="form-check-input permission-checkbox" type="checkbox" 
                                                   id="perm-<?= $permissionId ?>" 
                                                   data-user-id="<?= $e['user_id'] ?>"
                                                   data-permission-id="<?= $permissionId ?>"
                                                   data-is-role-default="<?= $isRoleDefault ? '1' : '0' ?>"
                                                   <?= $isChecked ? 'checked' : '' ?>>
                                        </div>
                                    </td>
                                    <td><label class="form-check-label" for="perm-<?= $permissionId ?>"><?= htmlspecialchars($permission['key_name']) ?></label></td>
                                    <td><?= htmlspecialchars($permission['description']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $isRoleDefault ? 'success' : 'secondary' ?>">
                                            <?= $isRoleDefault ? 'Allowed' : 'Denied' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end"> <!-- Align reset button to the right -->
                    <button type="button" class="btn btn-warning" id="reset-permissions-btn" data-user-id="<?= $e['user_id'] ?>">Reset to Role Defaults</button>
                </div>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="prev_selected_position_id" value="<?= htmlspecialchars($e['position_id'] ?? '') ?>">

<script src="/assets/js/employees/edit-employee-positions.js"></script>
<script src="/assets/js/employees/employee-form-ajax.js"></script>

<!-- Reset Permissions Confirmation Modal -->
<div class="modal fade" id="resetPermissionsModal" tabindex="-1" aria-labelledby="resetPermissionsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetPermissionsModalLabel">Confirm Reset Permissions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to reset all individual permissions for this employee to their role defaults? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-reset-permissions-btn">Reset Permissions</button>
            </div>
        </div>
    </div>
</div>