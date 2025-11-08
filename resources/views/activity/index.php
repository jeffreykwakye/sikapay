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
                <h4 class="card-title">Recent Tenant Activity</h4>
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
                                <th>Action</th>
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
                                        <td><?= htmlspecialchars($activity['log_message']) ?></td>
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
