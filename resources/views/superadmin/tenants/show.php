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
            <a href="/superadmin">Dashboard</a>
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

<div class="row">
    <div class="col-sm-6 col-md-3">
        <div class="card card-stats card-info card-round">
            <div class="card-body">
                <div class="row">
                    <div class="col-5">
                        <div class="icon-big text-center">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="col-7 col-stats">
                        <div class="numbers">
                            <p class="card-category">Total Employees</p>
                            <p class="card-title fs-5"><?php echo $h($data['totalEmployees']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card card-stats card-secondary card-round">
            <div class="card-body">
                <div class="row">
                    <div class="col-5">
                        <div class="icon-big text-center">
                            <i class="fas fa-building"></i>
                        </div>
                    </div>
                    <div class="col-7 col-stats">
                        <div class="numbers">
                            <p class="card-category">Total Departments</p>
                            <p class="card-title fs-5"><?php echo $h($data['totalDepartments']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card card-stats card-primary card-round">
            <div class="card-body">
                <div class="row">
                    <div class="col-5">
                        <div class="icon-big text-center">
                            <i class="fas fa-briefcase"></i>
                        </div>
                    </div>
                    <div class="col-7 col-stats">
                        <div class="numbers">
                            <p class="card-category">Total Positions</p>
                            <p class="card-title fs-5"><?php echo $h($data['totalPositions']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card card-stats card-success card-round">
            <div class="card-body">
                <div class="row">
                    <div class="col-5">
                        <div class="icon-big text-center">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                    <div class="col-7 col-stats">
                        <div class="numbers">
                            <p class="card-category">Last Payroll Run</p>
                            <p class="card-title fs-5"><?php echo $h($data['lastPayrollRunDate']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title"><?php echo $h($data['title']); ?></h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Tenant Details</h5>
                        <p><strong>Tenant ID:</strong> <?php echo $h((string)$data['tenant']['id']); ?></p>
                        <p><strong>Name:</strong> <?php echo $h($data['tenant']['name']); ?></p>
                        <p><strong>Subdomain:</strong> <?php echo $h($data['tenant']['subdomain']); ?></p>
                        <p><strong>Status:</strong> <?php echo $h($data['tenant']['subscription_status']); ?></p>
                        <p><strong>Payroll Flow:</strong> <?php echo $h($data['tenant']['payroll_approval_flow']); ?></p>
                        <p><strong>Created At:</strong> <?php echo $h($data['tenant']['created_at']); ?></p>
                        <p><strong>Updated At:</strong> <?php echo $h($data['tenant']['updated_at']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <h5>Admin User Details</h5>
                        <?php if ($data['adminUser']): ?>
                            <p><strong>Name:</strong> <?php echo $h($data['adminUser']['first_name'] . ' ' . $data['adminUser']['last_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo $h($data['adminUser']['email']); ?></p>
                            <p><strong>Role:</strong> <?php echo $h($data['adminUser']['role_name']); ?></p>
                        <?php else: ?>
                            <p>No admin user found for this tenant.</p>
                        <?php endif; ?>

                        <h5 class="mt-4">Current Subscription</h5>
                        <?php if ($data['subscription']): ?>
                            <p><strong>Plan:</strong> <?php echo $h($data['subscription']['plan_name']); ?></p>
                            <p><strong>Status:</strong> <?php echo $h($data['subscription']['status']); ?></p>
                            <p><strong>Start Date:</strong> <?php echo $h($data['subscription']['start_date']); ?></p>
                            <p><strong>End Date:</strong> <?php echo $h($data['subscription']['end_date']); ?></p>
                        <?php else: ?>
                            <p>No active subscription found for this tenant.</p>
                        <?php endif; ?>
                        
                        <h5 class="mt-4">Subscription Actions</h5>
                        <div class="d-grid gap-2 d-md-block">
                            <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#renewSubscriptionModal">Renew</button>
                            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#upgradeSubscriptionModal">Upgrade</button>
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#downgradeSubscriptionModal">Downgrade</button>
                            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelSubscriptionModal">Cancel</button>
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#sendEmailModal">Send Email</button>
                            <?php if ($isSuperAdmin): ?>
                                <a href="/super/impersonate/<?php echo $h((string)$data['tenant']['id']); ?>" class="btn btn-primary btn-sm">Impersonate Admin</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="/tenants" class="btn btn-primary">Back to Tenants</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Send Email Modal -->
<div class="modal fade" id="sendEmailModal" tabindex="-1" aria-labelledby="sendEmailModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/super/tenants/<?php echo $h((string)$data['tenant']['id']); ?>/send-email" method="POST">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="sendEmailModalLabel">Send Email to <?php echo $h($data['tenant']['name']); ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php echo $CsrfToken::field(); ?>
                    <div class="mb-3">
                        <label for="email_subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="email_subject" name="subject" required>
                    </div>
                    <div class="mb-3">
                        <label for="email_body" class="form-label">Body</label>
                        <textarea class="form-control" id="email_body" name="body" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Send Email</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Subscription History</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($data['subscriptionHistory'])): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Plan</th>
                                    <th>Action Type</th>
                                    <th>Amount Paid (GHS)</th>
                                    <th>Billing Cycle</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['subscriptionHistory'] as $history): ?>
                                    <tr>
                                        <td><?php echo $h(date('M j, Y', strtotime($history['created_at']))); ?></td>
                                        <td><?php echo $h($history['plan_name']); ?></td>
                                        <td><?php echo $h($history['action_type']); ?></td>
                                        <td><?php echo $history['amount_paid'] ? $h(number_format((float)$history['amount_paid'], 2)) : 'N/A'; ?></td>
                                        <td>
                                            <?php if ($history['billing_cycle_start'] && $history['billing_cycle_end']): ?>
                                                <?php echo $h(date('M j, Y', strtotime($history['billing_cycle_start']))) . ' - ' . $h(date('M j, Y', strtotime($history['billing_cycle_end']))); ?>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $h($history['details']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No subscription history found for this tenant.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Subscription Modal -->
<div class="modal fade" id="cancelSubscriptionModal" tabindex="-1" aria-labelledby="cancelSubscriptionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/tenants/<?php echo $h((string)$data['tenant']['id']); ?>/subscription/cancel" method="POST">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="cancelSubscriptionModalLabel">Cancel Subscription</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php echo $CsrfToken::field(); ?>
                    <p>Are you sure you want to cancel the subscription for <strong><?php echo $h($data['tenant']['name']); ?></strong>?</p>
                    <div class="mb-3">
                        <label for="cancellation_reason" class="form-label">Reason for Cancellation</label>
                        <textarea class="form-control" id="cancellation_reason" name="reason" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="cancellation_date" class="form-label">Effective Cancellation Date (Optional)</label>
                        <input type="date" class="form-control" id="cancellation_date" name="cancellation_date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Renew Subscription Modal -->
<div class="modal fade" id="renewSubscriptionModal" tabindex="-1" aria-labelledby="renewSubscriptionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/tenants/<?php echo $h((string)$data['tenant']['id']); ?>/subscription/renew" method="POST">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="renewSubscriptionModalLabel">Renew Subscription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php echo $CsrfToken::field(); ?>
                    <p>Renew subscription for <strong><?php echo $h($data['tenant']['name']); ?></strong> (Current Plan: <?php echo $h($data['subscription']['plan_name'] ?? 'N/A'); ?>).</p>
                    <input type="hidden" name="plan_id" value="<?php echo $h((string)$data['subscription']['current_plan_id'] ?? ''); ?>">
                    <div class="mb-3">
                        <label for="renew_end_date" class="form-label">New End Date</label>
                        <input type="date" class="form-control" id="renew_end_date" name="new_end_date" value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="amount_paid_renew" class="form-label">Amount Paid (GHS)</label>
                        <input type="number" class="form-control" id="amount_paid_renew" name="amount_paid" step="0.01" min="0" value="<?php echo $h($data['subscription']['price_ghs'] ?? 0.00); ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-warning">Confirm Renewal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upgrade Subscription Modal -->
<div class="modal fade" id="upgradeSubscriptionModal" tabindex="-1" aria-labelledby="upgradeSubscriptionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/tenants/<?php echo $h((string)$data['tenant']['id']); ?>/subscription/upgrade" method="POST">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="upgradeSubscriptionModalLabel">Upgrade Subscription</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php echo $CsrfToken::field(); ?>
                    <p>Upgrade subscription for <strong><?php echo $h($data['tenant']['name']); ?></strong> (Current Plan: <?php echo $h($data['subscription']['plan_name'] ?? 'N/A'); ?>).</p>
                    <div class="mb-3">
                        <label for="new_plan_id_upgrade" class="form-label">Select New Plan</label>
                        <select class="form-select" id="new_plan_id_upgrade" name="new_plan_id" required>
                            <option value="">-- Select a Plan --</option>
                            <?php foreach ($data['availablePlans'] as $plan): ?>
                                <?php if ($plan['id'] !== ($data['subscription']['current_plan_id'] ?? null)): ?>
                                    <option value="<?php echo $h((string)$plan['id']); ?>"><?php echo $h($plan['name']); ?> (GHS <?php echo $h(number_format((float)$plan['price_ghs'], 2)); ?>)</option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-info">Confirm Upgrade</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Downgrade Subscription Modal -->
<div class="modal fade" id="downgradeSubscriptionModal" tabindex="-1" aria-labelledby="downgradeSubscriptionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/tenants/<?php echo $h((string)$data['tenant']['id']); ?>/subscription/downgrade" method="POST">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title" id="downgradeSubscriptionModalLabel">Downgrade Subscription</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php echo $CsrfToken::field(); ?>
                    <p>Downgrade subscription for <strong><?php echo $h($data['tenant']['name']); ?></strong> (Current Plan: <?php echo $h($data['subscription']['plan_name'] ?? 'N/A'); ?>).</p>
                    <div class="mb-3">
                        <label for="new_plan_id_downgrade" class="form-label">Select New Plan</label>
                        <select class="form-select" id="new_plan_id_downgrade" name="new_plan_id" required>
                            <option value="">-- Select a Plan --</option>
                            <?php foreach ($data['availablePlans'] as $plan): ?>
                                <?php if ($plan['id'] !== ($data['subscription']['current_plan_id'] ?? null)): ?>
                                    <option value="<?php echo $h((string)$plan['id']); ?>"><?php echo $h($plan['name']); ?> (GHS <?php echo $h(number_format((float)$plan['price_ghs'], 2)); ?>)</option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-secondary">Confirm Downgrade</button>
                </div>
            </form>
        </div>
    </div>
</div>

