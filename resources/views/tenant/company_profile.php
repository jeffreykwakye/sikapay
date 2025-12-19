<?php
// Controller provides: $profile (containing merged data), $successMessage, $errorMessage, $flashWarning.
// We extract the 'profile' data into local variables.
if (is_array($profile)) {
    extract($profile);
} else {
    // This scenario should ideally not happen due to controller logic,
    // but defensive programming for static analysis and runtime safety.
    $profile = []; // Ensure it's an array for extract
    extract($profile); // Extract empty array to define variables as null or empty.
}

// Alias the long field name for brevity in the view, if desired.
$tin = $ghana_revenue_authority_tin ?? '';
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
                    <div class="card-header"><div class="card-title">Tax & Statutory Information</div></div>
                    <div class="card-body">
                        <h5 class="fw-bold mt-3">GRA (Ghana Revenue Authority) Details</h5>
                        <div class="form-group">
                            <label for="tin">Tax Identification Number (GRA TIN)</label>
                            <input type="text" class="form-control" id="tin" name="tin" value="<?= $h($tin ?? '') ?>" maxlength="50">
                        </div>
                        <div class="form-group"><label for="gra_office_name">GRA Office Name</label><input type="text" class="form-control" id="gra_office_name" name="gra_office_name" value="<?= $h($gra_office_name ?? '') ?>"></div>
                        <div class="form-group"><label for="gra_office_address">GRA Office Address</label><textarea class="form-control" id="gra_office_address" name="gra_office_address" rows="2"><?= $h($gra_office_address ?? '') ?></textarea></div>
                        <div class="form-group">
                            <label for="gra_report_recipient_name">GRA Report Recipient Name</label>
                            <input type="text" class="form-control" id="gra_report_recipient_name" name="gra_report_recipient_name" value="<?= $h($gra_report_recipient_name ?? '') ?>" maxlength="255">
                            <small class="form-text text-muted">e.g., "The Commissioner" or a specific person's name for GRA Report cover letters.</small>
                        </div>
                        <hr>
                        <h5 class="fw-bold mt-3">SSNIT (Social Security and National Insurance Trust) Details</h5>
                        <div class="form-group"><label for="ssnit_office_name">SSNIT Office Name</label><input type="text" class="form-control" id="ssnit_office_name" name="ssnit_office_name" value="<?= $h($ssnit_office_name ?? '') ?>"></div>
                        <div class="form-group"><label for="ssnit_office_address">SSNIT Office Address</label><textarea class="form-control" id="ssnit_office_address" name="ssnit_office_address" rows="2"><?= $h($ssnit_office_address ?? '') ?></textarea></div>
                        <div class="form-group">
                            <label for="ssnit_report_recipient_name">SSNIT Report Recipient Name</label>
                            <input type="text" class="form-control" id="ssnit_report_recipient_name" name="ssnit_report_recipient_name" value="<?= $h($ssnit_report_recipient_name ?? '') ?>" maxlength="255">
                            <small class="form-text text-muted">e.g., "The Director-General" or a specific person's name for SSNIT Report cover letters.</small>
                        </div>
                        <hr>
                        <h5 class="fw-bold mt-3">Company Bank Details (for Bank Advice)</h5>
                        <div class="row">
                            <div class="col-md-6"><div class="form-group"><label for="bank_name">Bank Name</label><input type="text" class="form-control" id="bank_name" name="bank_name" value="<?= $h($bank_name ?? '') ?>"></div></div>
                            <div class="col-md-6"><div class="form-group"><label for="bank_branch">Bank Branch</label><input type="text" class="form-control" id="bank_branch" name="bank_branch" value="<?= $h($bank_branch ?? '') ?>"></div></div>
                        </div>
                        <div class="form-group"><label for="bank_address">Bank Address</label><textarea class="form-control" id="bank_address" name="bank_address" rows="2"><?= $h($bank_address ?? '') ?></textarea></div>
                        <div class="form-group">
                            <label for="bank_advice_recipient_name">Bank Advice Recipient Name</label>
                            <input type="text" class="form-control" id="bank_advice_recipient_name" name="bank_advice_recipient_name" value="<?= $h($bank_advice_recipient_name ?? '') ?>" maxlength="255">
                            <small class="form-text text-muted">e.g., "The Manager" or a specific person's name for Bank Advice cover letters.</small>
                        </div>
                        <hr>
                        <h5 class="fw-bold mt-3">Cover Letter Signatory Details</h5>
                        <div class="form-group">
                            <label for="authorized_signatory_name">Authorized Signatory Name</label>
                            <input type="text" class="form-control" id="authorized_signatory_name" name="authorized_signatory_name" value="<?= $h($authorized_signatory_name ?? '') ?>" maxlength="255">
                            <small class="form-text text-muted">The name that will appear as the signatory on all statutory report cover letters.</small>
                        </div>
                        <div class="form-group">
                            <label for="authorized_signatory_title">Authorized Signatory Title/Position</label>
                            <input type="text" class="form-control" id="authorized_signatory_title" name="authorized_signatory_title" value="<?= $h($authorized_signatory_title ?? '') ?>" maxlength="255">
                            <small class="form-text text-muted">e.g., Chief Executive Officer, Human Resources Manager.</small>
                        </div>
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
        
        <!-- Separate Card for Logo Upload -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header"><div class="card-title">Company Logo & Branding</div></div>
                <div class="card-body">
                    <form id="logo-form" action="/company-profile/upload-logo" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $CsrfToken::getToken() ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="logo_file">Company Logo (PNG/JPG, Max 2MB)</label>
                                    <input type="file" name="logo_file" id="logo_file" class="form-control" accept="image/png, image/jpeg">
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
                        <div class="card-action">
                            <button type="submit" class="btn btn-secondary">Upload Logo</button>
                            <?php if ($logo_path): ?>
                                <!-- Button to trigger Bootstrap Modal for confirmation -->
                                <button type="button" id="remove-logo-btn" class="btn btn-danger ms-2" data-bs-toggle="modal" data-bs-target="#confirmRemoveLogoModal">Remove Logo</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Modal for Remove Logo Confirmation -->
<div class="modal fade" id="confirmRemoveLogoModal" tabindex="-1" aria-labelledby="confirmRemoveLogoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmRemoveLogoModalLabel">Confirm Logo Removal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to remove the company logo? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirm-remove-btn" class="btn btn-danger">Remove Logo</button>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/tenants/company-logo-preview.js"></script>
<script src="/assets/js/tenants/company-profile.js"></script>