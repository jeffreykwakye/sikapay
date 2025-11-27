<?php
/**
 * @var string $title
 * @var bool $isApprover
 * @var array $pendingApplications
 * @var array $myApplications
 * @var array $myBalances
 * @var array $leaveTypes
 * @var callable $h
 * @var object $CsrfToken
 */
$this->title = $title;
?>

<div class="page-header">
    <h3 class="fw-bold mb-3">Leave Management</h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/leave">Leave Management</a></li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-pills nav-secondary" id="leave-pills-tab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="pills-apply-tab" data-bs-toggle="pill" href="#pills-apply" role="tab" aria-controls="pills-apply" aria-selected="true">Apply & View My Leave</a>
                    </li>
                    <?php if ($isApprover): ?>
                    <li class="nav-item">
                        <a class="nav-link" id="pills-approvals-tab" data-bs-toggle="pill" href="#pills-approvals" role="tab" aria-controls="pills-approvals" aria-selected="false">Pending Approvals</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <div class="tab-content mt-2 mb-3" id="leave-pills-tabContent">
                    <div class="tab-pane fade show active" id="pills-apply" role="tabpanel" aria-labelledby="pills-apply-tab">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="card">
                                    <div class="card-header"><h4 class="card-title">Apply for Leave</h4></div>
                                    <div class="card-body">
                                        <form action="/leave/apply" method="POST">
                                            <?= $CsrfToken::field() ?>
                                            <div class="mb-3">
                                                <label for="leave_type_id" class="form-label">Leave Type</label>
                                                <select class="form-select" id="leave_type_id" name="leave_type_id" required>
                                                    <option value="">Select a leave type...</option>
                                                    <?php foreach ($leaveTypes as $type): ?>
                                                        <option value="<?= $h($type['id']) ?>"><?= $h($type['name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="start_date" class="form-label">Start Date</label>
                                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="end_date" class="form-label">End Date</label>
                                                <input type="date" class="form-control" id="end_date" name="end_date" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="reason" class="form-label">Reason (Optional)</label>
                                                <textarea class="form-control" id="reason" name="reason" rows="3"></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Submit Application</button>
                                        </form>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header"><h4 class="card-title">My Leave Balances</h4></div>
                                    <div class="card-body">
                                        <ul class="list-group list-group-flush">
                                            <?php if (empty($myBalances)): ?>
                                                <li class="list-group-item">No leave balances found.</li>
                                            <?php else: ?>
                                                <?php foreach ($myBalances as $balance): ?>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <?= $h($balance['leave_type_name']) ?>
                                                        <span class="badge bg-primary rounded-pill"><?= $h($balance['balance']) ?> days</span>
                                                    </li>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <div class="card">
                                    <div class="card-header"><h4 class="card-title">My Application History</h4></div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Type</th>
                                                        <th>Dates</th>
                                                        <th>Days</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                <?php if (empty($myApplications)): ?>
                                                    <tr><td colspan="4" class="text-center">You have not submitted any leave applications.</td></tr>
                                                <?php else: ?>
                                                    <?php foreach ($myApplications as $app): ?>
                                                    <tr>
                                                        <td><?= $h($app['leave_type_name']) ?></td>
                                                        <td><?= $h(date('M d, Y', strtotime($app['start_date']))) ?> - <?= $h(date('M d, Y', strtotime($app['end_date']))) ?></td>
                                                        <td><?= $h($app['total_days']) ?></td>
                                                        <td><span class="badge bg-info"><?= $h(ucfirst($app['status'])) ?></span></td>
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
                    </div>
                    <?php if ($isApprover): ?>
                    <div class="tab-pane fade" id="pills-approvals" role="tabpanel" aria-labelledby="pills-approvals-tab">
                        <div class="card">
                            <div class="card-header"><h4 class="card-title">Pending Leave Approvals</h4></div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Employee</th>
                                                <th>Leave Type</th>
                                                <th>Dates</th>
                                                <th>Days</th>
                                                <th>Reason</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($pendingApplications)): ?>
                                                <tr><td colspan="6" class="text-center">No pending leave applications.</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($pendingApplications as $app): ?>
                                                <tr>
                                                    <td><?= $h($app['first_name'] . ' ' . $app['last_name']) ?></td>
                                                    <td><?= $h($app['leave_type_name']) ?></td>
                                                    <td><?= $h(date('M d, Y', strtotime($app['start_date']))) ?> - <?= $h(date('M d, Y', strtotime($app['end_date']))) ?></td>
                                                    <td><?= $h($app['total_days']) ?></td>
                                                    <td><?= $h($app['reason']) ?></td>
                                                    <td>
                                                        <form action="/leave/approve/<?= $h($app['id']) ?>" method="POST" class="d-inline">
                                                            <?= $CsrfToken::field() ?>
                                                            <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                                        </form>
                                                        <form action="/leave/reject/<?= $h($app['id']) ?>" method="POST" class="d-inline">
                                                            <?= $CsrfToken::field() ?>
                                                            <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                                        </form>
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
