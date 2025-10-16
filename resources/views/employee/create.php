<?php 
// resources/views/employee/create.php (SIMPLIFIED)

$this->title = $title;
$input = $_SESSION['flash_input'] ?? [];
unset($_SESSION['flash_input']);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Quick Add Employee</h1>
    <a href="/employees" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Directory
    </a>
</div>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
<?php unset($_SESSION['flash_error']); endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="/employees">

            <h5 class="mb-3 text-primary"><i class="fas fa-user-plus"></i> Essential Employee Data</h5>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="employee_id" class="form-label">Employee ID <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="employee_id" name="employee_id" value="<?= htmlspecialchars($input['employee_id'] ?? '') ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?= htmlspecialchars($input['first_name'] ?? '') ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?= htmlspecialchars($input['last_name'] ?? '') ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Company Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($input['email'] ?? '') ?>" required>
                    <small class="form-text text-muted">Used for login; temporary password will be set.</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="hire_date" class="form-label">Hire Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="hire_date" name="hire_date" value="<?= htmlspecialchars($input['hire_date'] ?? date('Y-m-d')) ?>" required>
                </div>
            </div>
            
            <hr>

            <h5 class="mb-3 mt-4 text-primary"><i class="fas fa-briefcase"></i> Job & Compensation</h5>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="department_id" class="form-label">Department <span class="text-danger">*</span></label>
                    <select class="form-select" id="department_id" name="department_id" required>
                        <option value="">-- Select Department --</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>" <?= ($input['department_id'] ?? 0) == $dept['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="position_id" class="form-label">Position <span class="text-danger">*</span></label>
                    <select class="form-select" id="position_id" name="position_id" required>
                        <option value="">-- Select Position --</option>
                        <?php if (isset($input['position_id'])): /* Sticky position logic */ ?>
                            <option value="<?= $input['position_id'] ?>" selected>
                                <?= htmlspecialchars($input['position_title'] ?? 'Selected Position') ?>
                            </option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="monthly_base_salary" class="form-label">Monthly Base Salary (GHS) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" class="form-control" id="monthly_base_salary" name="monthly_base_salary" value="<?= htmlspecialchars($input['monthly_base_salary'] ?? '') ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                    <select class="form-select" id="gender" name="gender" required>
                        <?php $selectedGender = $input['gender'] ?? ''; ?>
                        <option value="">-- Select --</option>
                        <option value="Male" <?= $selectedGender === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= $selectedGender === 'Female' ? 'selected' : '' ?>>Female</option>
                        <option value="Other" <?= $selectedGender === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
            </div>
            
            <input type="hidden" name="date_of_birth" value="<?= date('1990-01-01') ?>">
            <input type="hidden" name="marital_status" value="Single">
            <input type="hidden" name="emergency_contact_name" value="N/A - Pending">
            <input type="hidden" name="emergency_contact_phone" value="0000000000">

            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-save"></i> Quick Save Employee</button>
            </div>
        </form>
    </div>
</div>

<script src="/assets/js/employees/fetch-positions.js"></script>