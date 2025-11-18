<?php
/**
 * @var string $title
 * @var array $plans
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
        <li class="nav-item"><a href="/super/plans">Plans</a></li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Subscription Plans</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Price (GHS)</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($plans)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No subscription plans found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($plans as $plan): ?>
                                    <tr>
                                        <td><?= $h($plan['id']) ?></td>
                                        <td><?= $h($plan['name']) ?></td>
                                        <td><?= $h(number_format((float)$plan['price_ghs'], 2)) ?></td>
                                        <td><?= $h(date('M j, Y, g:i a', strtotime($plan['created_at']))) ?></td>
                                        <td>
                                            <a href="#" class="btn btn-sm btn-info">Edit</a>
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
