<?php 
/**
 * @var string $title
 * @var array $departments An array of all departments (id, name).
 * @var array $positions An array of all positions (id, title, department_id).
 * @var callable $h Helper function for HTML escaping.
 * @var object $CsrfToken Class with static method getToken().
 */

// Load flash data for re-populating the form and displaying errors
$input = $_SESSION['flash_input'] ?? [];
$error = $_SESSION['flash_error'] ?? null;
$success = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_input'], $_SESSION['flash_error'], $_SESSION['flash_success']); 

// Helper to safely get selected value
$v = fn($key, $default = '') => $h($input[$key] ?? $default); 

// Fallback for $h if not provided by the main template
if (!isset($h)) {
    $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>

<div class="page-header">
    <h3 class="fw-bold mb-3"><?= $h($title) ?></h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/employees">Employees</a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="#">Create New</a></li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="card-title">New Employee Registration Form</div>
            </div>
            <div class="card-body">
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="icon-check me-2"></i><?= $h($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="icon-close me-2"></i><?= $h($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="/employees" method="POST"> 
                    <?php if (isset($CsrfToken)): ?>
                        <input type="hidden" name="csrf_token" value="<?= $CsrfToken::getToken() ?>">
                    <?php endif; ?>
                    
                    <input type="hidden" id="prev_selected_position_id" value="<?= $v('current_position_id', '') ?>">

                    <h5 class="mt-4 mb-3 border-bottom pb-2 text-primary"><i class="icon-user me-2"></i> Personal & Contact Details</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?= $v('first_name') ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?= $v('last_name') ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="other_name" class="form-label">Other Name(s)</label>
                            <input type="text" class="form-control" id="other_name" name="other_name" value="<?= $v('other_name') ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="email" class="form-label">Email (Login ID) <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= $v('email') ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?= $v('phone') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="date_of_birth" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?= $v('date_of_birth') ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <?php $selectedGender = $v('gender', ''); ?>
                                <option value="Male" <?= $selectedGender === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= $selectedGender === 'Female' ? 'selected' : '' ?>>Female</option>
                                <option value="Other" <?= $selectedGender === 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="marital_status" class="form-label">Marital Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="marital_status" name="marital_status" required>
                                <option value="">Select Status</option>
                                <?php $selectedMarital = $v('marital_status', ''); ?>
                                <option value="Single" <?= $selectedMarital === 'Single' ? 'selected' : '' ?>>Single</option>
                                <option value="Married" <?= $selectedMarital === 'Married' ? 'selected' : '' ?>>Married</option>
                                <option value="Divorced" <?= $selectedMarital === 'Divorced' ? 'selected' : '' ?>>Divorced</option>
                                <option value="Widowed" <?= $selectedMarital === 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                            </select>
                        </div>
                       
                    </div>
                    <div class="row">
                         <div class="col-md-6 mb-3">
                            <label for="nationality" class="form-label">Nationality</label>
                            <input type="text" class="form-control" id="nationality" name="nationality" value="<?= $v('nationality', 'Ghanaian') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="home_address" class="form-label">Home Address</label>
                            <input type="text" class="form-control" id="home_address" name="home_address" value="<?= $v('home_address') ?>">
                        </div>

                    </div>

                    <h5 class="mt-4 mb-3 border-bottom pb-2 text-primary"><i class="icon-briefcase me-2"></i> Employment & Position</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="employee_id" class="form-label">Employee ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="employee_id" name="employee_id" value="<?= $v('employee_id') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="hire_date" class="form-label">Hire Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="hire_date" name="hire_date" value="<?= $v('hire_date', date('Y-m-d')) ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="employment_type" class="form-label">Employment Type</label>
                            <select class="form-select" id="employment_type" name="employment_type">
                                <?php $selectedEmpType = $v('employment_type', 'Full-Time'); ?>
                                <option value="Full-Time" <?= $selectedEmpType === 'Full-Time' ? 'selected' : '' ?>>Full-Time</option>
                                <option value="Part-Time" <?= $selectedEmpType === 'Part-Time' ? 'selected' : '' ?>>Part-Time</option>
                                <option value="Contract" <?= $selectedEmpType === 'Contract' ? 'selected' : '' ?>>Contract</option>
                                <option value="Intern" <?= $selectedEmpType === 'Intern' ? 'selected' : '' ?>>Intern</option>
                                <option value="National-Service" <?= $selectedEmpType === 'National-Service' ? 'selected' : '' ?>>National Service Personnel</option>
                                <option value="Casual-Worker" <?= $selectedEmpType === 'Casual-Worker' ? 'selected' : '' ?>>Casual Worker</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="department_id" class="form-label">Department</label>
                            <select class="form-select" id="department_id" name="department_id">
                                <option value="">Select Department</option>
                                <?php $selectedDept = $v('department_id', 0); ?>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $h($dept['id']) ?>" <?= (int)$selectedDept === (int)$dept['id'] ? 'selected' : '' ?>>
                                        <?= $h($dept['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                  
                        <div class="col-md-4 mb-3">
                            <label for="current_position_id" class="form-label">Position <span class="text-danger">*</span></label>
                            <select class="form-select" id="current_position_id" name="current_position_id" required>
                                <option value="">Select Position</option>
                                <?php // The positions options are rendered here so the JS can read the data-department-id attributes ?>
                                <?php foreach ($positions as $pos): ?>
                                    <option 
                                        value="<?= $h($pos['id']) ?>" 
                                        data-department-id="<?= $h($pos['department_id']) ?>"
                                    >
                                        <?= $h($pos['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Positions can be filtered by selecting a department.</small>
                        </div>
                    </div>
                    
                    <h5 class="mt-4 mb-3 border-bottom pb-2 text-primary"><i class="icon-wallet me-2"></i> Salary & Payment Details</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="monthly_base_salary" class="form-label">Monthly Basic Salary (GHS) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="monthly_base_salary" name="monthly_base_salary" step="0.01" min="0" value="<?= $v('monthly_base_salary') ?>" required>
                            <small class="form-text text-muted">This is the employee's basic salary, before any allowances or deductions.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <select class="form-select" id="payment_method" name="payment_method">
                                <?php $selectedPayment = $v('payment_method', 'Bank Transfer'); ?>
                                <option value="Bank Transfer" <?= $selectedPayment === 'Bank Transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                                <option value="Mobile Money" <?= $selectedPayment === 'Mobile Money' ? 'selected' : '' ?>>Mobile Money</option>
                                <option value="Cash" <?= $selectedPayment === 'Cash' ? 'selected' : '' ?>>Cash</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="bank_name" class="form-label">Bank Name</label>
                            <input type="text" class="form-control" id="bank_name" name="bank_name" value="<?= $v('bank_name') ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="bank_branch" class="form-label">Branch</label>
                            <input type="text" class="form-control" id="bank_branch" name="bank_branch" value="<?= $v('bank_branch') ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="bank_account_number" class="form-label">Account Number</label>
                            <input type="text" class="form-control" id="bank_account_number" name="bank_account_number" value="<?= $v('bank_account_number') ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="bank_account_name" class="form-label">Account Name</label>
                            <input type="text" class="form-control" id="bank_account_name" name="bank_account_name" value="<?= $v('bank_account_name') ?>">
                        </div> 
                    </div>

                    <h5 class="mt-4 mb-3 border-bottom pb-2 text-primary"><i class="icon-docs me-2"></i> Statutory & Emergency</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="ssnit_number" class="form-label">SSNIT Number</label>
                            <input type="text" class="form-control" id="ssnit_number" name="ssnit_number" value="<?= $v('ssnit_number') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="tin_number" class="form-label">TIN Number</label>
                            <input type="text" class="form-control" id="tin_number" name="tin_number" value="<?= $v('tin_number') ?>">
                        </div>
                         <div class="col-md-4 mb-3">
                            <label for="id_card_number" class="form-label">ID Card Number</label>
                            <input type="text" class="form-control" id="id_card_number" name="id_card_number" value="<?= $v('id_card_number') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="id_card_type" class="form-label">ID Card Type</label>
                            <select class="form-select" id="id_card_type" name="id_card_type">
                                <?php $selectedIDType = $v('id_card_type', 'Ghana Card'); ?>
                                <option value="Ghana Card" <?= $selectedIDType === 'Ghana Card' ? 'selected' : '' ?>>Ghana Card</option>
                                <option value="Passport" <?= $selectedIDType === 'Passport' ? 'selected' : '' ?>>Passport</option>
                                <option value="Voter ID" <?= $selectedIDType === 'Voter ID' ? 'selected' : '' ?>>Voter ID</option>
                            </select>
                        </div>
                       
                    </div>

                    <h6 class="mt-4 mb-3 text-info">Emergency Contact</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="emergency_contact_name" class="form-label">Contact Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name" value="<?= $v('emergency_contact_name') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="emergency_contact_phone" class="form-label">Contact Phone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="emergency_contact_phone" name="emergency_contact_phone" value="<?= $v('emergency_contact_phone') ?>" required>
                        </div>
                    </div>
                    
                    <div class="card-action text-end">
                        <button type="submit" class="btn btn-primary"><i class="icon-plus me-2"></i> Create Employee Account</button>
                        <a href="/employees" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/employees/create-employee.js"></script>