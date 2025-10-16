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

<?php 
// Display Flash Messages (e.g., error from a previous failed update)
if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
<?php unset($_SESSION['flash_error']); endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="/employees/<?= $e['user_id'] ?>">
            <input type="hidden" name="_method" value="PUT">

            <h5 class="mb-3 text-primary"><i class="fas fa-user"></i> Personal & Account Details</h5>
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
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($e['email'] ?? '') ?>" required readonly>
                    <small class="form-text text-muted">Email is read-only here.</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="phone" class="form-label">Personal Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($e['phone'] ?? '') ?>">
                </div>
            </div>

            <hr>

            <h5 class="mb-3 mt-4 text-primary"><i class="fas fa-briefcase"></i> Employment Details</h5>
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
                        <?php 
                        $types = ['Full-Time', 'Part-Time', 'Contract', 'Intern'];
                        foreach ($types as $type): ?>
                            <option value="<?= $type ?>" <?= ($e['employment_type'] ?? '') === $type ? 'selected' : '' ?>>
                                <?= $type ?>
                            </option>
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
                            <option value="<?= $dept['id'] ?>" <?= ($e['department_id'] ?? 0) == $dept['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="position_id" class="form-label">Position <span class="text-danger">*</span></label>
                    <select class="form-select" id="position_id" name="position_id" required>
                        <option value="">-- Select Position --</option>
                        <?php 
                        // Positions are pre-loaded based on the employee's current department
                        foreach ($positions as $pos): ?>
                            <option value="<?= $pos['id'] ?>" <?= ($e['position_id'] ?? 0) == $pos['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pos['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">Change department to update position list.</small>
                </div>
            </div>

            <hr>

            <h5 class="mb-3 mt-4 text-primary"><i class="fas fa-money-bill-wave"></i> Payroll & Bank Details</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="monthly_base_salary" class="form-label">Monthly Base Salary (GHS) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" class="form-control" id="monthly_base_salary" name="monthly_base_salary" value="<?= htmlspecialchars($e['current_salary_ghs'] ?? '') ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                    <select class="form-select" id="payment_method" name="payment_method" required>
                        <?php $payments = ['Bank Transfer', 'Mobile Money', 'Cash'];
                        foreach ($payments as $method): ?>
                            <option value="<?= $method ?>" <?= ($e['payment_method'] ?? '') === $method ? 'selected' : '' ?>>
                                <?= $method ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="bank_name" class="form-label">Bank Name</label>
                    <input type="text" class="form-control" id="bank_name" name="bank_name" value="<?= htmlspecialchars($e['bank_name'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="bank_account_number" class="form-label">Account Number</label>
                    <input type="text" class="form-control" id="bank_account_number" name="bank_account_number" value="<?= htmlspecialchars($e['bank_account_number'] ?? '') ?>">
                </div>
            </div>

            <hr>

            <h5 class="mb-3 mt-4 text-primary"><i class="fas fa-gavel"></i> Compliance (Ghana)</h5>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="ssnit_number" class="form-label">SSNIT Number</label>
                    <input type="text" class="form-control" id="ssnit_number" name="ssnit_number" value="<?= htmlspecialchars($e['ssnit_number'] ?? '') ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="tin_number" class="form-label">TIN Number</label>
                    <input type="text" class="form-control" id="tin_number" name="tin_number" value="<?= htmlspecialchars($e['tin_number'] ?? '') ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="id_card_type" class="form-label">Primary ID Type</label>
                    <select class="form-select" id="id_card_type" name="id_card_type">
                        <?php $id_types = ['Ghana Card', 'Voter ID', 'Passport', 'Other'];
                        foreach ($id_types as $type): ?>
                            <option value="<?= $type ?>" <?= ($e['id_card_type'] ?? '') === $type ? 'selected' : '' ?>>
                                <?= $type ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="id_card_number" class="form-label">ID Card Number</label>
                    <input type="text" class="form-control" id="id_card_number" name="id_card_number" value="<?= htmlspecialchars($e['id_card_number'] ?? '') ?>">
                </div>
            </div>
            
            <hr>

            <h5 class="mb-3 mt-4 text-primary"><i class="fas fa-heartbeat"></i> Personal & Emergency</h5>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="date_of_birth" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?= date('Y-m-d', strtotime($e['date_of_birth'])) ?>" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                    <select class="form-select" id="gender" name="gender" required>
                        <?php $genders = ['Male', 'Female', 'Other'];
                        foreach ($genders as $gender): ?>
                            <option value="<?= $gender ?>" <?= ($e['gender'] ?? '') === $gender ? 'selected' : '' ?>>
                                <?= $gender ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="marital_status" class="form-label">Marital Status <span class="text-danger">*</span></label>
                    <select class="form-select" id="marital_status" name="marital_status" required>
                        <?php $maritals = ['Single', 'Married', 'Divorced', 'Widowed'];
                        foreach ($maritals as $status): ?>
                            <option value="<?= $status ?>" <?= ($e['marital_status'] ?? '') === $status ? 'selected' : '' ?>>
                                <?= $status ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="nationality" class="form-label">Nationality</label>
                    <input type="text" class="form-control" id="nationality" name="nationality" value="<?= htmlspecialchars($e['nationality'] ?? 'Ghanaian') ?>">
                </div>
            </div>

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
            <div class="mb-3">
                <label for="home_address" class="form-label">Residential Address</label>
                <textarea class="form-control" id="home_address" name="home_address" rows="2"><?= htmlspecialchars($e['home_address'] ?? '') ?></textarea>
            </div>


            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-sync-alt"></i> Update Employee Profile</button>
            </div>
        </form>
    </div>
</div>

<script src="/assets/js/employees/fetch-positions.js"></script>