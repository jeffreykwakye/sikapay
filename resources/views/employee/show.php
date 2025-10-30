<?php
/**
 * @var string $title
 * @var array $employee A comprehensive array of employee data.
 * @var array $staffFiles An array of file records for the employee.
 * @var callable $h Helper function for HTML escaping.
 * @var object $CsrfToken Class with a static method getToken().
 */

$this->title = $title;
$e = $employee; // Shorthand

// Determine the profile image URL, with a fallback to a default image.
$profileImageUrl = !empty($e['profile_picture_url']) ? $h($e['profile_picture_url']) : '/assets/img/profile.jpg';
?>

<div class="page-header">
    <h3 class="fw-bold mb-3">Employee Profile: <?= $h($e['first_name'] . ' ' . $e['last_name']) ?></h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/employees">Employees</a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="#"><?= $h($e['first_name'] . ' ' . $e['last_name']) ?></a></li>
    </ul>
</div>

<div class="row">
    <!-- Left Column: Profile Picture and Basic Info -->
    <div class="col-md-4">
        <div class="card card-profile">
            <div class="card-header" style="background-image: url('/assets/img/blogpost.jpg')"></div>
            <div class="card-body">
                <div class="user-profile text-center">
                    <div class="avatar avatar-xl">
                        <img src="<?= $profileImageUrl ?>" alt="..." class="avatar-img rounded-circle" />
                    </div>
                    <h4 class="mb-1 "><?= $h($e['first_name'] . ' ' . $e['last_name']) ?></h4>
                    <p class="mb-2 text-muted"><?= $h($e['position_title'] ?? 'N/A') ?></p>
                    <span class="badge <?= $e['is_active'] ? 'bg-success' : 'bg-danger' ?>">
                        <?= $e['is_active'] ? 'Active' : 'Inactive' ?>
                    </span>
                </div>
            </div>
            <div class="card-footer">
                 <div class="d-grid gap-2">
                 <?php if ($this->auth->can('employee:update')): ?>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#updateImageModal"><i class="icon-camera"></i> Change Picture</button>
                    <a href="/employees/<?= $e['user_id'] ?>/edit" class="btn btn-secondary btn-sm"><i class="icon-pencil"></i> Edit Full Profile</a>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Tabbed Information -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <div class="card-title">Employee Details</div>
            </div>
            <div class="card-body">
                <ul class="nav nav-pills nav-secondary" id="pills-tab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="pills-employment-tab" data-bs-toggle="pill" href="#pills-employment" role="tab" aria-controls="pills-employment" aria-selected="true">Employment</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="pills-personal-tab" data-bs-toggle="pill" href="#pills-personal" role="tab" aria-controls="pills-personal" aria-selected="false">Personal</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="pills-statutory-tab" data-bs-toggle="pill" href="#pills-statutory" role="tab" aria-controls="pills-statutory" aria-selected="false">Statutory</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="pills-files-tab" data-bs-toggle="pill" href="#pills-files" role="tab" aria-controls="pills-files" aria-selected="false">Staff Files</a>
                    </li>
                </ul>
                <div class="tab-content mt-2 mb-3" id="pills-tabContent">
                    <div class="tab-pane fade show active" id="pills-employment" role="tabpanel" aria-labelledby="pills-employment-tab">
                        <h5 class="mb-3">Employment & Payroll</h5>
                        <dl class="row">
                            <dt class="col-sm-4">Employee ID</dt><dd class="col-sm-8"><?= $h($e['employee_id']) ?></dd>
                            <dt class="col-sm-4">Hire Date</dt><dd class="col-sm-8"><?= date('F j, Y', strtotime($e['hire_date'])) ?></dd>
                            <dt class="col-sm-4">Department</dt><dd class="col-sm-8"><?= $h($e['department_name'] ?? 'N/A') ?></dd>
                            <dt class="col-sm-4">Position</dt><dd class="col-sm-8"><?= $h($e['position_title'] ?? 'N/A') ?></dd>
                            <dt class="col-sm-4">Employment Type</dt><dd class="col-sm-8"><?= $h($e['employment_type'] ?? 'N/A') ?></dd>
                            <hr class="my-2">
                            <dt class="col-sm-4">Monthly Salary</dt><dd class="col-sm-8">GHS <?= number_format($e['current_salary_ghs'], 2) ?></dd>
                            <dt class="col-sm-4">Payment Method</dt><dd class="col-sm-8"><?= $h($e['payment_method'] ?? 'N/A') ?></dd>
                            <dt class="col-sm-4">Bank</dt><dd class="col-sm-8"><?= $h($e['bank_name'] ?? 'N/A') ?></dd>
                            <dt class="col-sm-4">Account No</dt><dd class="col-sm-8"><?= $h($e['bank_account_number'] ?? 'N/A') ?></dd>
                        </dl>
                    </div>
                    <div class="tab-pane fade" id="pills-personal" role="tabpanel" aria-labelledby="pills-personal-tab">
                        <h5 class="mb-3">Personal & Contact</h5>
                        <dl class="row">
                            <dt class="col-sm-4">Full Legal Name</dt><dd class="col-sm-8"><?= $h($e['first_name'] . ' ' . ($e['other_name'] ? $e['other_name'] . ' ' : '') . $e['last_name']) ?></dd>
                            <dt class="col-sm-4">Email Address</dt><dd class="col-sm-8"><?= $h($e['email']) ?></dd>
                            <dt class="col-sm-4">Phone Number</dt><dd class="col-sm-8"><?= $h($e['phone'] ?? 'N/A') ?></dd>
                            <dt class="col-sm-4">Gender</dt><dd class="col-sm-8"><?= $h($e['gender']) ?></dd>
                            <dt class="col-sm-4">Date of Birth</dt><dd class="col-sm-8"><?= date('F j, Y', strtotime($e['date_of_birth'])) ?></dd>
                            <dt class="col-sm-4">Marital Status</dt><dd class="col-sm-8"><?= $h($e['marital_status']) ?></dd>
                            <dt class="col-sm-4">Residential Address</dt><dd class="col-sm-8"><?= nl2br($h($e['home_address'] ?? 'N/A')) ?></dd>
                        </dl>
                    </div>
                    <div class="tab-pane fade" id="pills-statutory" role="tabpanel" aria-labelledby="pills-statutory-tab">
                        <h5 class="mb-3">Statutory & Emergency</h5>
                        <dl class="row">
                            <dt class="col-sm-4">SSNIT Number</dt><dd class="col-sm-8"><?= $h($e['ssnit_number'] ?? 'N/A') ?></dd>
                            <dt class="col-sm-4">TIN Number</dt><dd class="col-sm-8"><?= $h($e['tin_number'] ?? 'N/A') ?></dd>
                            <dt class="col-sm-4">ID Card Type</dt><dd class="col-sm-8"><?= $h($e['id_card_type'] ?? 'N/A') ?></dd>
                            <dt class="col-sm-4">ID Card Number</dt><dd class="col-sm-8"><?= $h($e['id_card_number'] ?? 'N/A') ?></dd>
                            <hr class="my-2">
                            <dt class="col-sm-4">Emergency Contact</dt><dd class="col-sm-8"><?= $h($e['emergency_contact_name']) ?></dd>
                            <dt class="col-sm-4">Emergency Phone</dt><dd class="col-sm-8"><?= $h($e['emergency_contact_phone']) ?></dd>
                        </dl>
                    </div>
                    <div class="tab-pane fade" id="pills-files" role="tabpanel" aria-labelledby="pills-files-tab">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Staff Documents</h5>
                            <?php if ($this->auth->can('employee:update')): ?>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadFileModal"><i class="icon-plus"></i> Upload File</button>
                            <?php endif; ?>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>File Name</th>
                                        <th>Type</th>
                                        <th>Date Uploaded</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($staffFiles)): ?>
                                        <tr><td colspan="4" class="text-center text-muted">No files have been uploaded for this employee.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($staffFiles as $file): ?>
                                        <tr>
                                            <td><a href="<?= $h($file['file_path']) ?>" target="_blank"><i class="icon-doc"></i> <?= $h($file['file_name']) ?></a></td>
                                            <td><?= $h($file['file_type']) ?></td>
                                            <td><?= date('M j, Y, g:i a', strtotime($file['uploaded_at'])) ?></td>
                                            <td><button class="btn btn-danger btn-sm"><i class="icon-trash"></i></button></td>
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
    </div>
</div>


<!-- Modal: Update Profile Image -->
<div class="modal fade" id="updateImageModal" tabindex="-1" aria-labelledby="updateImageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/employees/<?= $e['user_id'] ?>/image" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateImageModalLabel">Update Profile Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $CsrfToken::getToken() ?>">
                    <div class="mb-3">
                        <label for="profile_image" class="form-label">Select new image (JPG, PNG)</label>
                        <input class="form-control" type="file" id="profile_image" name="profile_image" accept=".jpg,.jpeg,.png" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload Image</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Upload Staff File -->
<div class="modal fade" id="uploadFileModal" tabindex="-1" aria-labelledby="uploadFileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/employees/<?= $e['user_id'] ?>/files" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadFileModalLabel">Upload Staff Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $CsrfToken::getToken() ?>">
                    <div class="mb-3">
                        <label for="staff_file" class="form-label">Select file (PDF, DOC, DOCX, JPG, PNG)</label>
                        <input class="form-control" type="file" id="staff_file" name="staff_file" required>
                    </div>
                    <div class="mb-3">
                        <label for="file_type" class="form-label">File Type</label>
                        <select class="form-select" id="file_type" name="file_type" required>
                            <option value="Contract">Contract</option>
                            <option value="Certification">Certification</option>
                            <option value="ID Card">ID Card</option>
                            <option value="Tax Document">Tax Document</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="file_description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="file_description" name="file_description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload File</button>
                </div>
            </form>
        </div>
    </div>
</div>
