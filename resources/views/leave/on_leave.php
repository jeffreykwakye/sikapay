<?php
/**
 * @var string $title
 * @var array $onLeaveStaff
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
                                <th>Returning On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($onLeaveStaff)): ?>
                                <tr><td colspan="3" class="text-center">No staff currently on leave.</td></tr>
                            <?php else: ?>
                                <?php foreach ($onLeaveStaff as $staff): ?>
                                    <tr>
                                        <td><?= $h($staff['first_name'] . ' ' . $staff['last_name']) ?></td>
                                        <td><?= $h($staff['leave_type_name']) ?></td>
                                        <td><?= $h(date('M d, Y', strtotime($staff['end_date']))) ?></td>
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
