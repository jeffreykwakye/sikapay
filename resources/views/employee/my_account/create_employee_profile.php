<?php
/**
 * @var string $title
 * @var array $currentUser The user data for the currently logged-in user.
 * @var array $departments List of all departments.
 * @var array $positions List of all positions.
 * @var object $CsrfToken Class with a static method getToken().
 * @var callable $h Helper function for HTML escaping.
 */
$this->title = $title;
$u = $currentUser; // Shorthand for current user data
?>

<div class="page-header">
    <h3 class="fw-bold mb-3"><?= $h($title) ?></h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/my-account">My Account</a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item">Create Employee Profile</li>
    </ul>
</div>

<div class="page-inner">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Complete Your Employee Profile</div>
                </div>
                <div class="card-body">
                    <p class="text-muted">It looks like you don't have an employee profile yet. Please fill out the details below to create it. Your personal information has been pre-filled from your user account.</p>
                    <form method="POST" action="/my-account/create-employee-profile">
                        <?php if (isset($CsrfToken)): ?>
                            <input type="hidden" name="csrf_token" value="<?= $CsrfToken::getToken() ?>">
                        <?php endif; ?>
                        <input type="hidden" name="user_id" value="<?= $h($u['id']) ?>">

                        <h5 class="mb-3 mt-4">Personal Details</h5>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?= $h($u['first_name'] ?? '') ?>" required readonly>
                            </div>
                            <div class="col-md-4 mb-3">
                                                                <label for="other_name" class="form-label">Other Name(s)</label>
                                                                 <input type="text" class="form-control" id="other_name" name="other_name" value="<?= $h($u['other_name'] ?? '') ?>">
                                                             </div>
                                                             <div class="col-md-4 mb-3">
                                                                 <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                                                 <input type="text" class="form-control" id="last_name" name="last_name" value="<?= $h($u['last_name'] ?? '') ?>" required readonly>
                                                             </div>
                                                         </div>
                                                         <div class="row">
                                                             <div class="col-md-6 mb-3">
                                                                 <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                                                 <input type="email" class="form-control" id="email" name="email" value="<?= $h($u['email'] ?? '') ?>" required readonly>
                                                             </div>
                                                             <div class="col-md-6 mb-3">
                                                                 <label for="phone" class="form-label">Personal Phone</label>
                                                                 <input type="text" class="form-control" id="phone" name="phone" value="<?= $h($u['phone'] ?? '') ?>">                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="date_of_birth" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                                <select class="form-select" id="gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="marital_status" class="form-label">Marital Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="marital_status" name="marital_status" required>
                                    <option value="">Select Status</option>
                                    <option value="Single">Single</option>
                                    <option value="Married">Married</option>
                                    <option value="Divorced">Divorced</option>
                                    <option value="Widowed">Widowed</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="nationality" class="form-label">Nationality</label>
                                <input type="text" class="form-control" id="nationality" name="nationality" value="Ghanaian">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="home_address" class="form-label">Residential Address</label>
                            <textarea class="form-control" id="home_address" name="home_address" rows="2"></textarea>
                        </div>

                        <h5 class="mb-3 mt-4">Employment Details</h5>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="employee_id" class="form-label">Employee ID <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="employee_id" name="employee_id" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="hire_date" class="form-label">Hire Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="hire_date" name="hire_date" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="employment_type" class="form-label">Employment Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="employment_type" name="employment_type" required>
                                    <option value="">Select Type</option>
                                    <option value="Full-Time">Full-Time</option>
                                    <option value="Part-Time">Part-Time</option>
                                    <option value="Contract">Contract</option>
                                    <option value="Intern">Intern</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="department_id" class="form-label">Department <span class="text-danger">*</span></label>
                                <select class="form-select" id="department_id" name="department_id" required>
                                    <option value="">-- Select Department --</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= $h($dept['id']) ?>"><?= $h($dept['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="position_id" class="form-label">Position <span class="text-danger">*</span></label>
                                <select class="form-select" id="position_id" name="current_position_id" required>
                                    <option value="">-- Select Position --</option>
                                    <?php foreach ($positions as $pos): ?>
                                        <option value="<?= $h($pos['id']) ?>" data-department-id="<?= $h($pos['department_id'] ?? '') ?>"><?= $h($pos['title']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">Select department first to filter positions.</small>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="monthly_base_salary" class="form-label">Monthly Base Salary (GHS) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" id="monthly_base_salary" name="monthly_base_salary" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select class="form-select" id="payment_method" name="payment_method" required>
                                    <option value="">Select Method</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Mobile Money">Mobile Money</option>
                                    <option value="Cash">Cash</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="bank_name" class="form-label">Bank Name</label>
                                <input type="text" class="form-control" id="bank_name" name="bank_name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="bank_branch" class="form-label">Bank Branch</label>
                                <input type="text" class="form-control" id="bank_branch" name="bank_branch">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="bank_account_name" class="form-label">Account Name</label>
                                <input type="text" class="form-control" id="bank_account_name" name="bank_account_name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="bank_account_number" class="form-label">Account Number</label>
                                <input type="text" class="form-control" id="bank_account_number" name="bank_account_number">
                            </div>
                        </div>

                        <h5 class="mb-3 mt-4">Statutory Details</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="ssnit_number" class="form-label">SSNIT Number</label>
                                <input type="text" class="form-control" id="ssnit_number" name="ssnit_number">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="tin_number" class="form-label">TIN Number</label>
                                <input type="text" class="form-control" id="tin_number" name="tin_number">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="id_card_type" class="form-label">Primary ID Type</label>
                                <select class="form-select" id="id_card_type" name="id_card_type">
                                    <option value="">Select ID Type</option>
                                    <option value="Ghana Card">Ghana Card</option>
                                    <option value="Voter ID">Voter ID</option>
                                    <option value="Passport">Passport</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="id_card_number" class="form-label">ID Card Number</label>
                                <input type="text" class="form-control" id="id_card_number" name="id_card_number">
                            </div>
                        </div>

                        <h5 class="mb-3 mt-4">Emergency Contact</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="emergency_contact_name" class="form-label">Emergency Contact Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="emergency_contact_phone" class="form-label">Emergency Contact Phone <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="emergency_contact_phone" name="emergency_contact_phone" required>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">Create My Employee Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/employees/edit-employee-positions.js"></script>
