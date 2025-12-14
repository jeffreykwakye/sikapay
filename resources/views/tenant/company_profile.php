<?php
// 🔥 CONSISTENCY FIX: Removed 'use Jeffrey\Sikapay\Security\CsrfToken;'
// Relying on the $CsrfToken variable and helper functions $h, $id injected by the Controller::view() method.

// Controller provides: $profile (containing merged data), $successMessage, $errorMessage, $flashWarning.
// We extract the 'profile' data into local variables.
extract($profile);

// Alias the long field name for brevity in the view, if desired.
$tin = $ghana_revenue_authority_tin; 
?>

<div class="page-header">
    <h3 class="fw-bold mb-3">Company Profile & Settings</h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/dashboard">Dashboard</a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="#">Company Profile</a></li>
    </ul>
</div>

<div class="page-inner">
    <?php if ($successMessage): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert"><?= $h($successMessage) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert"><?= $h($errorMessage) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
    <?php endif; ?>
    
    <?php if ($flashWarning): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert"><?= $h($flashWarning) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-12">
            <form action="/company-profile/save" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $CsrfToken::getToken() ?>">

                <!-- Card: General Details -->
                <div class="card">
                    <div class="card-header"><div class="card-title">General Company Details</div></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="legal_name">Company Legal Name</label>
                                    <input type="text" class="form-control" id="legal_name" name="legal_name" value="<?= $h($legal_name) ?>" required maxlength="150">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tin">Tax Identification Number (GRA TIN)</label>
                                    <input type="text" class="form-control" id="tin" name="tin" value="<?= $h($tin) ?>" maxlength="50">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone_number">Company Phone Number</label>
                                    <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?= $h($phone_number) ?>" maxlength="50">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="support_email">Support/Contact Email</label>
                                    <input type="email" class="form-control" id="support_email" name="support_email" value="<?= $h($support_email) ?>" maxlength="100">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="physical_address">Company Physical Address</label>
                            <textarea class="form-control" id="physical_address" name="physical_address" rows="3"><?= $h($physical_address) ?></textarea>
                            <small class="form-text text-muted">This address will appear on official documents.</small>
                        </div>
                    </div>
                </div>

                <!-- Card: Bank & Statutory Office Details -->
                <div class="card">
                    <div class="card-header"><div class="card-title">Bank & Statutory Office Details</div></div>
                    <div class="card-body">
                        <h5 class="fw-bold mt-3">Company Bank Details (for Bank Advice)</h5>
                        <div class="row">
                            <div class="col-md-6"><div class="form-group"><label for="bank_name">Bank Name</label><input type="text" class="form-control" id="bank_name" name="bank_name" value="<?= $h($bank_name) ?>"></div></div>
                            <div class="col-md-6"><div class="form-group"><label for="bank_branch">Bank Branch</label><input type="text" class="form-control" id="bank_branch" name="bank_branch" value="<?= $h($bank_branch) ?>"></div></div>
                        </div>
                        <div class="form-group"><label for="bank_address">Bank Address</label><textarea class="form-control" id="bank_address" name="bank_address" rows="2"><?= $h($bank_address) ?></textarea></div>
                        <hr>
                        <h5 class="fw-bold mt-3">SSNIT Office Details</h5>
                        <div class="form-group"><label for="ssnit_office_name">SSNIT Office Name</label><input type="text" class="form-control" id="ssnit_office_name" name="ssnit_office_name" value="<?= $h($ssnit_office_name) ?>"></div>
                        <div class="form-group"><label for="ssnit_office_address">SSNIT Office Address</label><textarea class="form-control" id="ssnit_office_address" name="ssnit_office_address" rows="2"><?= $h($ssnit_office_address) ?></textarea></div>
                        <hr>
                        <h5 class="fw-bold mt-3">GRA Office Details</h5>
                        <div class="form-group"><label for="gra_office_name">GRA Office Name</label><input type="text" class="form-control" id="gra_office_name" name="gra_office_name" value="<?= $h($gra_office_name) ?>"></div>
                        <div class="form-group"><label for="gra_office_address">GRA Office Address</label><textarea class="form-control" id="gra_office_address" name="gra_office_address" rows="2"><?= $h($gra_office_address) ?></textarea></div>
                    </div>
                </div>

                <!-- Card: Statutory Report Settings -->
                <?php if (in_array($planName, ['Professional', 'Enterprise'])): ?>
                <div class="card">
                    <div class="card-header"><div class="card-title">Statutory Report Settings</div></div>
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include_report_cover_letters" value="true" id="includeCoverLetters" <?= $include_report_cover_letters ? 'checked' : '' ?>>
                            <label class="form-check-label" for="includeCoverLetters">Include Cover Letters in Statutory Reports (SSNIT, PAYE, Bank Advice)</label>
                            <small class="form-text text-muted">When enabled, a formal cover letter will be generated as the first page of each statutory report.</small>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Form Action/Save Button -->
                <div class="card">
                    <div class="card-action">
                        <button type="submit" class="btn btn-primary btn-lg">Save All Settings</button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Separate Form for Logo Upload -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header"><div class="card-title">Company Logo & Branding</div></div>
                <form action="/company-profile/upload-logo" method="POST" enctype="multipart/form-data">
                    <div class="card-body">
                        <input type="hidden" name="csrf_token" value="<?= $CsrfToken::getToken() ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="logo_file">Company Logo (PNG/JPG, Max 2MB)</label>
                                    <input type="file" name="logo_file" id="logo_file" class="form-control" accept="image/png, image/jpeg" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label>Current Logo Preview</label>
                                <div id="logo-preview">
                                    <?php if ($logo_path): ?>
                                    <p><img src="<?= $h($logo_path) ?>" alt="Current Logo" style="height: 50px; border: 1px solid #eee; padding: 2px;"></p>
                                    <?php else: ?>
                                    <p class="text-warning">No logo currently saved.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-action">
                        <button type="submit" class="btn btn-secondary">Upload Logo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/tenants/company-logo-preview.js"></script>