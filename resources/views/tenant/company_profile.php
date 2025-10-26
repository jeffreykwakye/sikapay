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
            <div class="card">
                <div class="card-header"><div class="card-title">General Company Details</div></div>
                <form action="/company-profile/save" method="POST">
                    <div class="card-body">
                        <input type="hidden" name="csrf_token" value="<?= $CsrfToken::getToken() ?>">
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
                                    <small class="form-text text-muted">Required for official government forms and reports (e.g., Ghana Revenue Authority).</small>
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
                            <small class="form-text text-muted">This address will appear on payslips and official documents.</small>
                        </div>
                    </div>
                    <div class="card-action">
                        <button type="submit" class="btn btn-success">Save General Details</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="col-md-12">
            <div class="card">
                <div class="card-header"><div class="card-title">Company Logo & Branding</div></div>
                <form action="/company-profile/upload-logo" method="POST" enctype="multipart/form-data">
                    <div class="card-body">
                        <input type="hidden" name="csrf_token" value="<?= $CsrfToken::getToken() ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="logo_file">Company Logo (PNG/JPG, Max 1MB)</label>
                                    <input 
                                        type="file" 
                                        name="logo_file" 
                                        id="logo_file" 
                                        class="form-control" 
                                        accept="image/png, image/jpeg" 
                                        required 
                                        data-current-logo-path="<?= $h($logo_path) ?>"
                                    >
                                    <small class="form-text text-muted">Upload a clear logo for payslips and reports (recommended height ~50px).</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label>Current/New Logo Preview</label>
                                
                                <div id="logo-preview">
                                    <?php if ($logo_path): ?>
                                    <p><img src="<?= $h($logo_path) ?>" alt="Current Logo" style="height: 50px; border: 1px solid #eee; padding: 2px;"></p>
                                    <small class="form-text text-muted">This is the logo currently saved in the system.</small>
                                    <?php else: ?>
                                    <p class="text-warning">No logo currently saved.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-action">
                        <button type="submit" class="btn btn-primary">Upload/Update Logo</button>
                    </div>
                </form>
            </div>
        </div>

        
    </div>
</div>

<script src="/assets/js/tenants/company-logo-preview.js"></script>