<?php
/**
 * @var string $title
 * @var array $employee A comprehensive array of employee data for the logged-in user.
 * @var object $auth The Auth object for checking permissions.
 * @var callable $h Helper function for HTML escaping.
 */
$this->title = $title;
$e = $employee; // Shorthand for employee data
?>

<div class="page-header">
    <h3 class="fw-bold mb-3">My Account: <?= $h($e['first_name'] . ' ' . $e['last_name']) ?></h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/my-account">My Account</a></li>
    </ul>
</div>

<div class="page-inner">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <ul class="nav nav-pills nav-secondary" id="pills-tab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="pills-profile-tab" data-bs-toggle="pill" href="#pills-profile" role="tab" aria-controls="pills-profile" aria-selected="true">My Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="pills-payslips-tab" data-bs-toggle="pill" href="#pills-payslips" role="tab" aria-controls="pills-payslips" aria-selected="false">Payslips</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="pills-leave-tab" data-bs-toggle="pill" href="#pills-leave" role="tab" aria-controls="pills-leave" aria-selected="false">Leave</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="pills-overtime-tab" data-bs-toggle="pill" href="#pills-overtime" role="tab" aria-controls="pills-overtime" aria-selected="false">Overtime</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="pills-complaints-tab" data-bs-toggle="pill" href="#pills-complaints" role="tab" aria-controls="pills-complaints" aria-selected="false">Complaints</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="pills-loans-tab" data-bs-toggle="pill" href="#pills-loans" role="tab" aria-controls="pills-loans" aria-selected="false">Loans</a>
                        </li>
                    </ul>
                    <div class="tab-content mt-2 mb-3" id="pills-tabContent">
                        <!-- My Profile Tab Content -->
                        <div class="tab-pane fade show active" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab">
                            <div class="row mb-4 align-items-center">
                                <div class="col-md-2 text-center">
                                    <img src="<?= $h($e['profile_image_url'] ?? '/assets/images/profiles/placeholder.jpg') ?>" alt="Profile Image" class="img-fluid rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
                                </div>
                                <div class="col-md-10">
                                    <h4 class="fw-bold">Personal & Employment Details</h4>
                                    <p class="text-muted mb-0">This information is for your reference. To request changes, please contact your HR administrator.</p>
                                </div>
                            </div>

                            <!-- Personal Information -->
                            <div class="card mt-4">
                                <div class="card-header"><h5 class="card-title mb-0">Personal Information</h5></div>
                                <div class="card-body">
                                    <dl class="row">
                                        <dt class="col-sm-3">Full Name:</dt>
                                        <dd class="col-sm-9"><?= $h($e['first_name'] . ' ' . $e['other_name'] . ' ' . $e['last_name']) ?></dd>
                                        <dt class="col-sm-3">Email:</dt>
                                        <dd class="col-sm-9"><?= $h($e['email']) ?></dd>
                                        <dt class="col-sm-3">Phone:</dt>
                                        <dd class="col-sm-9"><?= $h($e['phone'] ?? 'N/A') ?></dd>
                                        <dt class="col-sm-3">Date of Birth:</dt>
                                        <dd class="col-sm-9"><?= $h(date('M j, Y', strtotime($e['date_of_birth']))) ?></dd>
                                        <dt class="col-sm-3">Gender:</dt>
                                        <dd class="col-sm-9"><?= $h($e['gender']) ?></dd>
                                        <dt class="col-sm-3">Marital Status:</dt>
                                        <dd class="col-sm-9"><?= $h($e['marital_status']) ?></dd>
                                        <dt class="col-sm-3">Nationality:</dt>
                                        <dd class="col-sm-9"><?= $h($e['nationality']) ?></dd>
                                        <dt class="col-sm-3">Address:</dt>
                                        <dd class="col-sm-9"><?= $h($e['home_address'] ?? 'N/A') ?></dd>
                                    </dl>
                                </div>
                            </div>

                            <!-- Employment Information -->
                            <div class="card mt-4">
                                <div class="card-header"><h5 class="card-title mb-0">Employment Information</h5></div>
                                <div class="card-body">
                                    <dl class="row">
                                        <dt class="col-sm-3">Employee ID:</dt>
                                        <dd class="col-sm-9"><?= $h($e['employee_id']) ?></dd>
                                        <dt class="col-sm-3">Department:</dt>
                                        <dd class="col-sm-9"><?= $h($e['department_name']) ?></dd>
                                        <dt class="col-sm-3">Position:</dt>
                                        <dd class="col-sm-9"><?= $h($e['position_title']) ?></dd>
                                        <dt class="col-sm-3">Hire Date:</dt>
                                        <dd class="col-sm-9"><?= $h(date('M j, Y', strtotime($e['hire_date']))) ?></dd>
                                        <dt class="col-sm-3">Employment Type:</dt>
                                        <dd class="col-sm-9"><?= $h($e['employment_type']) ?></dd>
                                    </dl>
                                </div>
                            </div>

                            <!-- Statutory Information -->
                            <div class="card mt-4">
                                <div class="card-header"><h5 class="card-title mb-0">Statutory Information</h5></div>
                                <div class="card-body">
                                    <dl class="row">
                                        <dt class="col-sm-3">SSNIT Number:</dt>
                                        <dd class="col-sm-9"><?= $h($e['ssnit_number'] ?? 'N/A') ?></dd>
                                        <dt class="col-sm-3">TIN Number:</dt>
                                        <dd class="col-sm-9"><?= $h($e['tin_number'] ?? 'N/A') ?></dd>
                                        <dt class="col-sm-3">ID Card Type:</dt>
                                        <dd class="col-sm-9"><?= $h($e['id_card_type'] ?? 'N/A') ?></dd>
                                        <dt class="col-sm-3">ID Card Number:</dt>
                                        <dd class="col-sm-9"><?= $h($e['id_card_number'] ?? 'N/A') ?></dd>
                                    </dl>
                                </div>
                            </div>

                            <!-- Emergency Contact Information -->
                            <div class="card mt-4">
                                <div class="card-header"><h5 class="card-title mb-0">Emergency Contact</h5></div>
                                <div class="card-body">
                                    <dl class="row">
                                        <dt class="col-sm-3">Contact Name:</dt>
                                        <dd class="col-sm-9"><?= $h($e['emergency_contact_name'] ?? 'N/A') ?></dd>
                                        <dt class="col-sm-3">Contact Phone:</dt>
                                        <dd class="col-sm-9"><?= $h($e['emergency_contact_phone'] ?? 'N/A') ?></dd>
                                    </dl>
                                </div>
                            </div>

                            <!-- Salary Information -->
                            <div class="card mt-4">
                                <div class="card-header"><h5 class="card-title mb-0">Salary & Payment Information</h5></div>
                                <div class="card-body">
                                    <dl class="row">
                                        <dt class="col-sm-3">Monthly Base Salary:</dt>
                                        <dd class="col-sm-9">GHS <?= $h(number_format($e['current_salary_ghs'], 2)) ?></dd>
                                        <dt class="col-sm-3">Payment Method:</dt>
                                        <dd class="col-sm-9"><?= $h($e['payment_method']) ?></dd>
                                        <dt class="col-sm-3">Bank Account:</dt>
                                        <dd class="col-sm-9"><?= $h($e['bank_account_name'] ?? 'N/A') ?> (<?= $h($e['bank_account_number'] ?? 'N/A') ?>)</dd>
                                    </dl>
                                    <hr>
                                    <h6>Allowances & Deductions</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Allowances</strong>
                                            <ul class="list-group list-group-flush">
                                                <?php 
                                                $hasAllowances = false;
                                                foreach ($assignedPayrollElements as $element) {
                                                    if ($element['category'] === 'allowance') {
                                                        $hasAllowances = true;
                                                        echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
                                                        echo $h($element['name']);
                                                        echo '<span class="badge bg-success rounded-pill">GHS ' . $h(number_format($element['assigned_amount'], 2)) . '</span>';
                                                        echo '</li>';
                                                    }
                                                }
                                                if (!$hasAllowances) {
                                                    echo '<li class="list-group-item">No allowances assigned.</li>';
                                                }
                                                ?>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Deductions</strong>
                                            <ul class="list-group list-group-flush">
                                                <?php 
                                                $hasDeductions = false;
                                                foreach ($assignedPayrollElements as $element) {
                                                    if ($element['category'] === 'deduction') {
                                                        $hasDeductions = true;
                                                        echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
                                                        echo $h($element['name']);
                                                        echo '<span class="badge bg-danger rounded-pill">GHS ' . $h(number_format($element['assigned_amount'], 2)) . '</span>';
                                                        echo '</li>';
                                                    }
                                                }
                                                if (!$hasDeductions) {
                                                    echo '<li class="list-group-item">No deductions assigned.</li>';
                                                }
                                                ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payslips Tab Content -->
                        <div class="tab-pane fade" id="pills-payslips" role="tabpanel" aria-labelledby="pills-payslips-tab">
                            <h5 class="mb-3 mt-4">My Payslips</h5>
                            <p class="text-muted">View and download your payslips for past payroll periods.</p>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Payroll Period</th>
                                            <th>Gross Pay</th>
                                            <th>Net Pay</th>
                                            <th>Generated On</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($payslips)): ?>
                                            <?php foreach ($payslips as $payslip): ?>
                                                <tr>
                                                    <td><?= $h($payslip['period_name']) ?> (<?= $h(date('M Y', strtotime($payslip['start_date']))) ?>)</td>
                                                    <td>GHS <?= $h(number_format($payslip['gross_pay'], 2)) ?></td>
                                                    <td>GHS <?= $h(number_format($payslip['net_pay'], 2)) ?></td>
                                                    <td><?= $h(date('M j, Y', strtotime($payslip['generated_at']))) ?></td>
                                                    <td>
                                                        <?php if (!empty($payslip['payslip_path'])): ?>
                                                            <a href="<?= $h($payslip['payslip_path']) ?>" target="_blank" class="btn btn-sm btn-primary">
                                                                <i class="fa fa-download"></i> Download
                                                            </a>
                                                        <?php else: ?>
                                                            N/A
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No payslips found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Leave Tab Content -->
                        <div class="tab-pane fade" id="pills-leave" role="tabpanel" aria-labelledby="pills-leave-tab">
                            <h5 class="mb-3 mt-4">Leave & Time Off</h5>
                            <p class="text-muted">Apply for leave and track your leave balances and requests.</p>
                            <!-- Leave content will be loaded here -->
                        </div>

                        <!-- Overtime Tab Content -->
                        <div class="tab-pane fade" id="pills-overtime" role="tabpanel" aria-labelledby="pills-overtime-tab">
                            <h5 class="mb-3 mt-4">Overtime Assignments</h5>
                            <p class="text-muted">View your assigned overtime hours and details.</p>
                            <!-- Overtime content will be loaded here -->
                        </div>

                        <!-- Complaints Tab Content -->
                        <div class="tab-pane fade" id="pills-complaints" role="tabpanel" aria-labelledby="pills-complaints-tab">
                            <h5 class="mb-3 mt-4">File a Complaint</h5>
                            <p class="text-muted">Submit a complaint or grievance to HR.</p>
                            <!-- Complaints form/content will be loaded here -->
                        </div>

                        <!-- Loans Tab Content -->
                        <div class="tab-pane fade" id="pills-loans" role="tabpanel" aria-labelledby="pills-loans-tab">
                            <h5 class="mb-3 mt-4">Loan Applications</h5>
                            <p class="text-muted">Apply for company loans and track your application status.</p>
                            <!-- Loans content will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>