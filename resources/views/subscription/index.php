<?php
/**
 * @var string $title
 * @var array|null $subscription
 * @var array|null $plan
 * @var array $features
 * @var array $history
 * @var callable $h
 */

$this->title = $title;

if (!isset($h)) {
    $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>

<div class="page-header">
    <h3 class="fw-bold mb-3">My Subscription</h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="#">Subscription</a></li>
    </ul>
</div>

<div class="row">
    <?php if (!$subscription): ?>
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="alert alert-warning text-center" role="alert">
                        <h4 class="alert-heading">No Subscription Found!</h4>
                        <p>It appears you do not have an active subscription. Please contact support for assistance.</p>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Current Plan Details Card -->
        <div class="col-md-5">
            <div class="card card-primary bg-primary-gradient">
                <div class="card-body">
                    <h4 class="mt-3 b-b1 pb-2 mb-4 fw-bold text-white">Current Plan</h4>
                    <div class="d-flex justify-content-between">
                        <h2 class="text-white"><?= $h($plan['name']) ?></h2>
                        <h2 class="text-white">GHS <?= $h(number_format((float)$plan['price_ghs'], 2)) ?></h2>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <p class="text-white mb-0">Per Month</p>
                    </div>
                    <hr class="my-3" style="border-color: rgba(255,255,255,0.5);">
                    <div class="d-flex justify-content-between">
                        <p class="text-white mb-0">Status:</p>
                        <p class="text-white fw-bold mb-0 text-uppercase"><?= $h($subscription['status']) ?></p>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <p class="text-white mb-0">Start Date:</p>
                        <p class="text-white fw-bold mb-0"><?= date('F j, Y', strtotime($subscription['start_date'])) ?></p>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <p class="text-white mb-0">Next Billing Date:</p>
                        <p class="text-white fw-bold mb-0"><?= date('F j, Y', strtotime($subscription['next_billing_date'])) ?></p>
                    </div>
                    <div class="mt-4 text-center">
                        <a href="/subscription/how-to-pay" class="btn btn-light btn-rounded fw-bold">
                            <i class="fas fa-money-check-alt"></i> How to Renew/Manage
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Plan Features Card -->
        <div class="col-md-7">
            <div class="card">
                <div class="card-header"><h4 class="card-title">Plan Features</h4></div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($features as $feature): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <?= $h($feature['description']) ?>
                                </div>
                                <span class="badge bg-secondary rounded-pill"><?= $h($feature['value']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Subscription History Card -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Subscription History</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Action</th>
                                    <th>Plan</th>
                                    <th>Amount Paid (GHS)</th>
                                    <th>Billing Cycle</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($history)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No subscription history found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($history as $item): ?>
                                        <tr>
                                            <td><?= date('M j, Y H:i', strtotime($item['created_at'])) ?></td>
                                            <td>
                                                <span class="badge bg-info"><?= $h($item['action_type']) ?></span>
                                            </td>
                                            <td><?= $h($item['plan_name']) ?></td>
                                            <td><?= $item['amount_paid'] ? $h(number_format((float)$item['amount_paid'], 2)) : 'N/A' ?></td>
                                            <td>
                                                <?php if ($item['billing_cycle_start'] && $item['billing_cycle_end']): ?>
                                                    <?= date('M j, Y', strtotime($item['billing_cycle_start'])) ?> - <?= date('M j, Y', strtotime($item['billing_cycle_end'])) ?>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $h($item['details']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
