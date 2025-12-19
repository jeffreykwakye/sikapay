<?php
/**
 * @var string $title
 * @var array $pendingApplications
 * @var callable $h
 * @var object $CsrfToken
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
                                <th>Total Days</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pendingApplications)): ?>
                                <tr><td colspan="4" class="text-center">No pending leave applications.</td></tr>
                            <?php else: ?>
                                <?php foreach ($pendingApplications as $app): ?>
                                <tr>
                                    <td><?= $h($app['first_name'] . ' ' . $app['last_name']) ?></td>
                                    <td><?= $h($app['leave_type_name']) ?></td>
                                    <td><?= $h($app['total_days']) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info view-details-btn" data-app-id="<?= $h($app['id']) ?>">View</button>
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

<!-- Leave Application Details Modal -->
<div class="modal fade" id="leaveDetailsModal" tabindex="-1" aria-labelledby="leaveDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="leaveDetailsModalLabel">Leave Application Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="modal-loader" class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <div id="modal-content-display" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Employee:</strong> <span id="modal-employee-name"></span></p>
                            <p><strong>Leave Type:</strong> <span id="modal-leave-type"></span></p>
                            <p><strong>Total Days:</strong> <span id="modal-total-days"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Start Date:</strong> <span id="modal-start-date"></span></p>
                            <p><strong>End Date:</strong> <span id="modal-end-date"></span></p>
                            <p><strong>Date Submitted:</strong> <span id="modal-submitted-date"></span></p>
                        </div>
                    </div>
                    <hr>
                    <p><strong>Reason:</strong></p>
                    <p id="modal-reason"></p>
                    <hr>
                    <p><strong>Remaining Balance:</strong> <span id="modal-leave-balance">N/A</span></p>
                </div>
            </div>
            <div class="modal-footer" id="modal-footer-actions">
                <form id="modal-approve-form" method="POST" class="d-inline" style="display: none;">
                    <?= $CsrfToken::field() ?>
                    <button type="submit" class="btn btn-success">Approve</button>
                </form>
                <form id="modal-reject-form" method="POST" class="d-inline" style="display: none;">
                    <?= $CsrfToken::field() ?>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </form>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="modal-close-button">Close</button>
            </div>
        </div>
    </div>
</div>
<script src="/assets/js/leave/management.js"></script>