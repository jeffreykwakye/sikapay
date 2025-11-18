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
                            <h4 class="card-title">GH&cent; <?= $h(number_format($stats['mrr'] ?? 0, 2)) ?></h4>
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
