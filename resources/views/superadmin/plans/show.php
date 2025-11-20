<?php
/**
 * @var string $title
 * @var array $plan
 * @var array $features
 * @var array $tenants
 * @var float $revenue
 * @var callable $h
 * @var object $CsrfToken
 */
?>

<div class="page-header">
    <h3 class="fw-bold mb-3"><?= $h($title) ?></h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/super/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/super/plans">Subscription Plans</a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="#"><?= $h($plan['name']) ?></a></li>
    </ul>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Plan Details</h4>
            </div>
            <div class="card-body">
                <form action="/super/plans/<?= $h($plan['id']) ?>" method="POST">
                    <?= $CsrfToken::field() ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Plan Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?= $h($plan['name']) ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="price_ghs">Price (GHS/month)</label>
                                <input type="number" class="form-control" id="price_ghs" name="price_ghs" step="0.01" min="0" value="<?= $h($plan['price_ghs']) ?>">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?= $h($plan['description']) ?></textarea>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Update Plan</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Tenants on this Plan</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped mt-3">
                        <thead>
                            <tr>
                                <th>Tenant Name</th>
                                <th>Status</th>
                                <th>Subscribed Since</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($tenants)): ?>
                                <?php foreach ($tenants as $tenant): ?>
                                    <tr>
                                        <td><a href="/tenants/<?= $h($tenant['id']) ?>"><?= $h($tenant['name']) ?></a></td>
                                        <td>
                                            <span class="badge bg-success"><?= $h(ucfirst($tenant['status'])) ?></span>
                                        </td>
                                        <td><?= $h(date('M j, Y', strtotime($tenant['start_date']))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center">No tenants are currently subscribed to this plan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Plan Metrics</h4>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <span>Total Revenue Accrued:</span>
                    <strong>GH&cent; <?= $h(number_format($revenue, 2)) ?></strong>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <span>Number of Tenants:</span>
                    <strong><?= $h(count($tenants)) ?></strong>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Plan Features</h4>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php if (!empty($features)): ?>
                        <?php foreach ($features as $feature): ?>
                            <li class="list-group-item">
                                <?= $h($feature['description']) ?>
                                <?php if (!empty($feature['value']) && $feature['value'] !== 'true'): ?>
                                    : <strong><?= $h($feature['value']) ?></strong>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item">No features associated with this plan.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
