<?php
/**
 * @var string $title
 * @var array $subscriptions
 * @var callable $h
 */
?>

<div class="page-header">
    <h3 class="fw-bold mb-3"><?= $h($title) ?></h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/super/dashboard">Super Admin</a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/super/subscriptions">Subscriptions</a></li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">All Tenant Subscriptions</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Tenant ID</th>
                                <th>Tenant Name</th>
                                <th>Plan Name</th>
                                <th>Status</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($subscriptions)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No subscriptions found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($subscriptions as $sub): ?>
                                    <tr>
                                        <td><?= $h($sub['tenant_id']) ?></td>
                                        <td><?= $h($sub['tenant_name']) ?></td>
                                        <td><span class="badge bg-info"><?= $h($sub['plan_name']) ?></span></td>
                                        <td>
                                            <?php
                                            $statusClass = 'secondary';
                                            if ($sub['status'] === 'active') {
                                                $statusClass = 'success';
                                            } elseif ($sub['status'] === 'cancelled' || $sub['status'] === 'expired') {
                                                $statusClass = 'danger';
                                            }
                                            ?>
                                            <span class="badge bg-<?= $statusClass ?>"><?= $h(ucfirst($sub['status'])) ?></span>
                                        </td>
                                        <td><?= $h(date('M j, Y', strtotime($sub['start_date']))) ?></td>
                                        <td><?= $sub['end_date'] ? $h(date('M j, Y', strtotime($sub['end_date']))) : 'N/A' ?></td>
                                        <td>
                                            <a href="/tenants/<?= $h($sub['tenant_id']) ?>" class="btn btn-sm btn-info">View Tenant</a>
                                        </td>
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
