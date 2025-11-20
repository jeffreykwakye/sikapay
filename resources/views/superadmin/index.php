<?php
/**
 * @var string $title
 * @var array $stats
 * @var array $charts
 * @var callable $h
 */
?>

<div class="page-header">
    <h3 class="fw-bold mb-3"><?= $h($title) ?></h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/super/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/super/dashboard">Super Admin</a></li>
    </ul>
</div>

<!-- KPI CARDS ROW -->
<div class="row">
    <div class="col-sm-6 col-md-3">
        <div class="card card-stats card-primary card-round">
            <div class="card-body">
                <div class="row">
                    <div class="col-5">
                        <div class="icon-big text-center">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="col-7 col-stats">
                        <div class="numbers">
                            <p class="card-category">Monthly Recurring Revenue</p>
                            <h4 class="card-title">&cent; <?= $h(number_format($stats['mrr'] ?? 0, 2)) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card card-stats card-info card-round">
            <div class="card-body">
                <div class="row">
                    <div class="col-5">
                        <div class="icon-big text-center">
                            <i class="fas fa-building"></i>
                        </div>
                    </div>
                    <div class="col-7 col-stats">
                        <div class="numbers">
                            <p class="card-category">Total Active Tenants</p>
                            <h4 class="card-title"><?= $h($stats['total_tenants'] ?? 0) ?></h4>
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
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="col-7 col-stats">
                        <div class="numbers">
                            <p class="card-category">Active Subscriptions</p>
                            <h4 class="card-title"><?= $h($stats['active_subscriptions'] ?? 0) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card card-stats card-warning card-round">
            <div class="card-body">
                <div class="row">
                    <div class="col-5">
                        <div class="icon-big text-center">
                            <i class="fas fa-user-plus"></i>
                        </div>
                    </div>
                    <div class="col-7 col-stats">
                        <div class="numbers">
                            <p class="card-category">New Tenants (30 Days)</p>
                            <h4 class="card-title"><?= $h($stats['new_tenants_last_30_days'] ?? 0) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CHARTS ROW -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <div class="card-title">Revenue Trend (Last 12 Months)</div>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="revenueTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <div class="card-title">Plan Distribution</div>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="planDistributionChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- NEW TABLES ROW -->
<div class="row">
    <!-- New Tenants Table -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">New Tenants (Last 30 Days)</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped mt-3">
                        <thead>
                            <tr>
                                <th scope="col">Tenant Name</th>
                                <th scope="col">Status</th>
                                <th scope="col">Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($tables['new_tenants'])): ?>
                                <?php foreach ($tables['new_tenants'] as $tenant): ?>
                                    <tr>
                                        <td><a href="/tenants/<?= $h($tenant['id']) ?>"><?= $h($tenant['name']) ?></a></td>
                                        <td>
                                            <?php
                                            $status = $tenant['subscription_status'] ?? 'unknown';
                                            $statusClass = 'secondary';
                                            if ($status === 'active' || $status === 'trial') $statusClass = 'success';
                                            ?>
                                            <span class="badge bg-<?= $statusClass ?>"><?= $h(ucfirst($status)) ?></span>
                                        </td>
                                        <td><?= $h(date('M j, Y', strtotime($tenant['created_at']))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center">No new tenants in the last 30 days.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- At-Risk Subscriptions Table -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">At-Risk Subscriptions</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped mt-3">
                        <thead>
                            <tr>
                                <th scope="col">Tenant Name</th>
                                <th scope="col">Status</th>
                                <th scope="col">End Date / Days Left</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($tables['at_risk_subscriptions'])): ?>
                                <?php foreach ($tables['at_risk_subscriptions'] as $sub): ?>
                                    <tr>
                                        <td><a href="/tenants/<?= $h($sub['tenant_id']) ?>"><?= $h($sub['tenant_name']) ?></a></td>
                                        <td>
                                            <?php
                                            $status = $sub['status'] ?? 'unknown';
                                            $statusClass = ($status === 'past_due') ? 'danger' : 'warning';
                                            ?>
                                            <span class="badge bg-<?= $statusClass ?>"><?= $h(ucfirst(str_replace('_', ' ', $status))) ?></span>
                                        </td>
                                        <td>
                                            <?php if ($status === 'past_due'): ?>
                                                Ended on <?= $h(date('M j, Y', strtotime($sub['end_date']))) ?>
                                            <?php else: ?>
                                                <?= $h($sub['days_left']) ?> days left
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center">No at-risk subscriptions found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Data container for the dashboard charts -->
<div id="superadmin-chart-data" data-charts='<?= json_encode([
    'revenueTrend' => [
        'labels' => array_column($charts['revenue_trend'], 'month'),
        'data' => array_column($charts['revenue_trend'], 'revenue'),
    ],
    'planDistribution' => [
        'labels' => array_column($charts['plan_distribution'], 'plan_name'),
        'data' => array_column($charts['plan_distribution'], 'subscription_count'),
    ]
]) ?>'></div>

<!-- Load the dedicated Super Admin dashboard JS -->
<script src="/assets/js/superadmin/dashboard.js"></script>
