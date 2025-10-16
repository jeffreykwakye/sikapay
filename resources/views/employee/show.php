<?php 
// resources/views/employee/show.php

$this->title = $title; // e.g., 'Employee Profile: John Doe'
$e = $employee; // Shorthand for the employee array
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3"><?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name']) ?></h1>
    <a href="/employees" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Directory
    </a>
</div>

<?php 
// Display Flash Messages (e.g., success message from quick-create or edit)
if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
<?php unset($_SESSION['flash_success']); endif; ?>

<?php if (
    empty($e['ssnit_number']) || empty($e['tin_number']) || empty($e['home_address']) || 
    $e['emergency_contact_name'] === 'N/A - Pending'
): ?>
    <div class="alert alert-warning d-flex justify-content-between align-items-center" role="alert">
        <div>
            <i class="fas fa-exclamation-triangle"></i> **Profile Incomplete:** Please fill in all mandatory compliance, address, and personal details.
        </div>
        <?php if ($this->auth->can('employee:update')): ?>
            <a href="/employees/<?= $e['user_id'] ?>/edit" class="btn btn-warning btn-sm">
                Complete Profile
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white"><i class="fas fa-briefcase"></i> Employment Details</div>
            <div class="card-body">
                <p><strong>Employee ID:</strong> <?= htmlspecialchars($e['employee_id']) ?></p>
                <p><strong>Status:</strong> 
                    <span class="badge <?= $e['is_active'] ? 'bg-success' : 'bg-danger' ?>">
                        <?= $e['is_active'] ? 'Active' : 'Inactive' ?>
                    </span>
                </p>
                <p><strong>Hire Date:</strong> <?= date('F j, Y', strtotime($e['hire_date'])) ?></p>
                <p><strong>Department:</strong> <?= htmlspecialchars($e['department_name'] ?? 'N/A') ?></p>
                <p><strong>Position:</strong> <?= htmlspecialchars($e['position_title'] ?? 'N/A') ?></p>
                <p><strong>Employment Type:</strong> <?= htmlspecialchars($e['employment_type'] ?? 'N/A') ?></p>
            </div>
            <div class="card-footer text-end">
                 <?php if ($this->auth->can('employee:update')): ?>
                    <a href="/employees/<?= $e['user_id'] ?>/edit" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-edit"></i> Edit Employment
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white"><i class="fas fa-money-bill-wave"></i> Payroll Details</div>
            <div class="card-body">
                <p><strong>Monthly Salary (GHS):</strong> <?= number_format($e['current_salary_ghs'], 2) ?></p>
                <p><strong>Payment Method:</strong> <?= htmlspecialchars($e['payment_method'] ?? 'N/A') ?></p>
                <p><strong>Bank:</strong> <?= htmlspecialchars($e['bank_name'] ?? 'N/A') ?></p>
                <p><strong>Account No:</strong> <?= htmlspecialchars($e['bank_account_number'] ?? 'N/A') ?></p>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-dark text-white"><i class="fas fa-user-circle"></i> Personal & Contact</div>
            <div class="card-body">
                <p><strong>Full Name:</strong> <?= htmlspecialchars($e['first_name'] . ' ' . ($e['other_name'] ? $e['other_name'] . ' ' : '') . $e['last_name']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($e['email']) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($e['phone'] ?? 'N/A') ?></p>
                <p><strong>Gender:</strong> <?= htmlspecialchars($e['gender']) ?></p>
                <p><strong>Date of Birth:</strong> <?= date('F j, Y', strtotime($e['date_of_birth'])) ?></p>
                <p><strong>Marital Status:</strong> <?= htmlspecialchars($e['marital_status']) ?></p>
                <p><strong>Residential Address:</strong> <?= nl2br(htmlspecialchars($e['home_address'] ?? 'N/A')) ?></p>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-danger text-white"><i class="fas fa-heartbeat"></i> Emergency & Compliance</div>
            <div class="card-body">
                <p><strong>SSNIT Number:</strong> <?= htmlspecialchars($e['ssnit_number'] ?? 'N/A') ?></p>
                <p><strong>TIN Number:</strong> <?= htmlspecialchars($e['tin_number'] ?? 'N/A') ?></p>
                <p><strong>ID Type:</strong> <?= htmlspecialchars($e['id_card_type'] ?? 'N/A') ?></p>
                <p><strong>ID Number:</strong> <?= htmlspecialchars($e['id_card_number'] ?? 'N/A') ?></p>
                <hr>
                <p><strong>Emergency Contact Name:</strong> <?= htmlspecialchars($e['emergency_contact_name']) ?></p>
                <p><strong>Emergency Contact Phone:</strong> <?= htmlspecialchars($e['emergency_contact_phone']) ?></p>
            </div>
        </div>
    </div>
</div>