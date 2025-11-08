<div class="page-header">
    <h3 class="fw-bold mb-3">Activity Log</h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/activity-log">Activity Log</a></li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">Recent Tenant Activity</h4>
                    <div class="ms-auto">
                        <a href="/activity-log/csv" class="btn btn-secondary btn-sm">
                            <i class="fa fa-file-csv"></i> Export as CSV
                        </a>
                        <a href="/activity-log/pdf" class="btn btn-danger btn-sm">
                            <i class="fa fa-file-pdf"></i> Export as PDF
                        </a>
                    </div>
                </div>
                <p class="card-category">Showing the last <?= count($activities) ?> recorded events.</p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="activity-log-table" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <?php if ($isSuperAdminView): ?>
                                    <th>Tenant</th>
                                <?php endif; ?>
                                <th>User</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($activities)): ?>
                                <tr>
                                    <td colspan="<?= $isSuperAdminView ? 4 : 3 ?>" class="text-center">No activity recorded yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($activities as $activity): ?>
                                    <tr>
                                        <td><?= htmlspecialchars(date('M d, Y, g:i A', strtotime($activity['created_at']))) ?></td>
                                        <?php if ($isSuperAdminView): ?>
                                            <td><?= htmlspecialchars($activity['tenant_name'] ?? 'N/A') ?></td>
                                        <?php endif; ?>
                                        <td><?= htmlspecialchars(($activity['first_name'] ?? 'System') . ' ' . ($activity['last_name'] ?? '')) ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($activity['action_message']) ?></strong>
                                            <?php
                                            $details = json_decode($activity['details_json'] ?? '', true);
                                            if (is_array($details) && !empty($details)): ?>
                                                <dl class="row mt-2 mb-0 small text-muted">
                                                    <?php foreach ($details as $key => $value): ?>
                                                        <dt class="col-sm-4 text-truncate"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $key))) ?>:</dt>
                                                        <dd class="col-sm-8"><?= htmlspecialchars(is_array($value) ? json_encode($value) : $value) ?></dd>
                                                    <?php endforeach; ?>
                                                </dl>
                                            <?php endif; ?>
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
