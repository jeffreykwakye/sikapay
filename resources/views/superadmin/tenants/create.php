<?php
// Note: This file no longer contains <html>, <head>, or <body> tags.

// The $title variable (used in the master template) should be set here
$title = $h('Create New Tenant'); 
?>

<div class="page-header">
    <h3 class="fw-bold mb-3"><?php echo $h($data['title']); ?></h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home">
            <a href="/">
                <i class="icon-home"></i>
            </a>
        </li>
        <li class="separator">
            <i class="icon-arrow-right"></i>
        </li>
        <li class="nav-item">
            <a href="/">Dashboard</a>
        </li>
        <li class="separator">
            <i class="icon-arrow-right"></i>
        </li>
        <li class="nav-item">
            <a href="/tenants">Tenant Management</a>
        </li>
        <li class="separator">
            <i class="icon-arrow-right"></i>
        </li>
        <li class="nav-item">
            <a href="#"><?php echo $h($data['title']); ?></a>
        </li>
    </ul>
</div>



<?php if (!empty($data['error'])): ?>
    <p style="color: red;"><?= $h($data['error']) ?></p>
<?php endif; ?>


<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title"><?php echo $h($data['title']); ?></h4>
            </div>
            <div class="card-body">

                
                <form method="POST" action="/tenants">
                    <div class="row">

                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-body">

                                    <?= $CsrfToken::field() ?> 
            
                                    <h5>Tenant Details</h5>

                                    <div class="form-group">
                                        <label for="tenant_name">Company Name:</label>
                                        <input type="text" class="form-control" id="tenant_name" name="tenant_name" placeholder="Enter Company's Name" 
                                            value="<?= $h($data['input']['tenant_name'] ?? '') ?>" required/>
                                        <small id="tenant_name_help" class="form-text text-muted">
                                            Please provide your company/business's legal name.
                                        </small>
                                    </div>

                                    <div class="form-group">
                                        <label for="subdomain">Subdomain:</label>
                                        <input type="text" class="form-control" id="subdomain" name="subdomain" placeholder="Enter Subdomain" 
                                            value="<?= $h($data['input']['subdomain'] ?? '') ?>" required/>
                                        <small id="subdomain_help" class="form-text text-muted">
                                            Please set the subdomain.
                                        </small>
                                    </div>

                                    <div class="form-group">
                                        <label for="payroll_flow">Payroll Flow:</label>
                                        <select class="form-select" id="payroll_flow" name="payroll_flow">
                                            <option value="ACCOUNTANT_FINAL">Accountant Final</option>
                                            <option value="ADMIN_FINAL">Admin Final</option>
                                        </select>
                                        <small id="payroll_flow_help" class="form-text text-muted">
                                            Please set who approves final payroll.
                                        </small>
                                    </div>

                                    <h5>Subscription Details</h5>

                                    <div class="form-group">
                                        <label for="plan_id">Select Plan:</label>
                                        <select class="form-select" id="plan_id" name="plan_id">
                                            <option value="">-- Select a Plan --</option>
                                            <?php foreach ($data['plans'] as $plan): ?>
                                                <option value="<?= $h((string)$plan['id']) ?>"
                                                    <?php echo (isset($data['input']['plan_id']) && (int)$data['input']['plan_id'] === $plan['id']) ? 'selected' : ''; ?>
                                                    >
                                                    <?= $h($plan['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small id="plan_id_help" class="form-text text-muted">
                                            Please select a subscription plan for the tenant.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">

                            <div class="card">
                                <div class="card-body">

                                    <h5>Initial Admin User Details</h5>

                                    <div class="form-group">
                                        <label for="admin_fname">First Name:</label>
                                        <input type="text" class="form-control" id="admin_fname" name="admin_fname" placeholder="Enter first name" 
                                            value="<?= $h($data['input']['admin_fname'] ?? '') ?>" required/>
                                        <small id="admin_fname_help" class="form-text text-muted">
                                            Please enter the first name of the administrator.
                                        </small>
                                    </div>

                                    <div class="form-group">
                                        <label for="admin_lname">Last Name:</label>
                                        <input type="text" class="form-control" id="admin_lname" name="admin_lname" placeholder="Enter last name" 
                                            value="<?= $h($data['input']['admin_lname'] ?? '') ?>" required/>
                                        <small id="admin_lname_help" class="form-text text-muted">
                                            Please enter the last name of the administrator.
                                        </small>
                                    </div>

                                    <div class="form-group">
                                        <label for="admin_email">Email:</label>
                                        <input type="text" class="form-control" id="admin_email" name="admin_email" placeholder="Enter email" 
                                            value="<?= $h($data['input']['admin_email'] ?? '') ?>" required/>
                                        <small id="admin_email_help" class="form-text text-muted">
                                            Please enter the email of the administrator.
                                        </small>
                                    </div>

                                    <div class="form-group">
                                        <label for="admin_password">Password:</label>
                                        <input type="text" class="form-control" id="admin_password" name="admin_password" placeholder="Enter password" 
                                            value="<?= $h($data['input']['admin_password'] ?? '') ?>" required/>
                                        <small id="admin_password_help" class="form-text text-muted">
                                            Please enter the password of the administrator.
                                        </small>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>
                    

                    <button type="submit" class="btn btn-primary btn-block">Create Tenant</button>
                </form>
                <!-- <p><a href="/tenants">Back to Tenant List</a></p> -->
            
            </div>
        </div>
    </div>
</div>

