<?php
/**
 * @var string $title
 * @var array $approvedApplications
 * @var callable $h
 */
$this->title = $title;
?>

<div class="page-header">
    <h3 class="fw-bold mb-3"><?= $h($title) ?></h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/leave">Leave Management</a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="#"><?= $h($title) ?></a></li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title"><?= $h($title) ?></h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Leave Type</th>
                                <th>Dates</th>
                                <th>Days</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($approvedApplications)): ?>
                                <tr><td colspan="5" class="text-center">No approved leave applications.</td></tr>
                            <?php else: ?>
                                <?php foreach ($approvedApplications as $app): ?>
                                    <tr>
                                        <td><?= $h($app['first_name'] . ' ' . $app['last_name']) ?></td>
                                        <td><?= $h($app['leave_type_name']) ?></td>
                                        <td><?= $h(date('M d, Y', strtotime($app['start_date']))) ?> - <?= $h(date('M d, Y', strtotime($app['end_date']))) ?></td>
                                        <td><?= $h($app['total_days']) ?></td>
                                        <td><span class="badge bg-success"><?= $h(ucfirst($app['status'])) ?></span></td>
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
