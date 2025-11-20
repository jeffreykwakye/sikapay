<?php
/**
 * @var string $title
 * @var array $messages
 * @var callable $h
 */

$this->title = $title;

if (!isset($h)) {
    $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>

<div class="page-header">
    <h3 class="fw-bold mb-3">Manage Support Tickets</h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="#">Support</a></li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">All Support Tickets</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="basic-datatables" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tenant</th>
                                <th>Sender</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($messages)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No support tickets found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($messages as $message): ?>
                                    <tr>
                                        <td><?= $h($message['id']) ?></td>
                                        <td><?= $h($message['tenant_name']) ?></td>
                                        <td><?= $h($message['first_name'] . ' ' . $message['last_name']) ?></td>
                                        <td><?= $h($message['subject']) ?></td>
                                        <td>
                                            <?php
                                            $statusClass = 'secondary';
                                            if ($message['status'] === 'open' || $message['status'] === 'reopened') {
                                                $statusClass = 'danger'; // Super admin sees these as priority
                                            } elseif ($message['status'] === 'in_progress') {
                                                $statusClass = 'warning';
                                            } elseif ($message['status'] === 'closed') {
                                                $statusClass = 'success';
                                            }
                                            ?>
                                            <span class="badge bg-<?= $statusClass ?>"><?= $h(ucfirst(str_replace('_', ' ', $message['status']))) ?></span>
                                        </td>
                                        <td><?= date('M j, Y H:i', strtotime($message['updated_at'])) ?></td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#messageModal-<?= $h($message['id']) ?>">
                                                <i class="icon-eye"></i> View/Respond
                                            </button>
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

<!-- Modals for viewing message details and responding -->
<?php if (!empty($messages)): ?>
    <?php foreach ($messages as $message): ?>
        <div class="modal fade" id="messageModal-<?= $h($message['id']) ?>" tabindex="-1" aria-labelledby="messageModalLabel-<?= $h($message['id']) ?>" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="messageModalLabel-<?= $h($message['id']) ?>">Ticket #<?= $h($message['id']) ?>: <?= $h($message['subject']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Tenant:</strong> <?= $h($message['tenant_name']) ?></p>
                        <p><strong>Sender:</strong> <?= $h($message['first_name'] . ' ' . $message['last_name']) ?> (<?= $h($message['email']) ?>)</p>
                        <p><strong>Status:</strong> <span class="badge bg-<?= $statusClass ?>"><?= $h(ucfirst(str_replace('_', ' ', $message['status']))) ?></span></p>
                        <p><strong>Submitted:</strong> <?= date('M j, Y H:i', strtotime($message['created_at'])) ?></p>
                        <hr>
                        <h6>Tenant's Message:</h6>
                        <p class="border-start border-4 border-primary ps-3 mb-4">
                            <?= nl2br($h($message['message'])) ?>
                        </p>

                        <h6>Support Response:</h6>
                        <form action="/support/<?= $h($message['id']) ?>/respond" method="POST">
                            <?= \Jeffrey\Sikapay\Security\CsrfToken::field() ?>
                            <div class="mb-3">
                                <label for="super_admin_response-<?= $h($message['id']) ?>" class="form-label">Your Response</label>
                                <textarea class="form-control" id="super_admin_response-<?= $h($message['id']) ?>" name="super_admin_response" rows="5" required minlength="10"><?= $h($message['super_admin_response'] ?? '') ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="status-<?= $h($message['id']) ?>" class="form-label">Update Status</label>
                                <select class="form-select" id="status-<?= $h($message['id']) ?>" name="status" required>
                                    <option value="open" <?= ($message['status'] === 'open') ? 'selected' : '' ?>>Open</option>
                                    <option value="in_progress" <?= ($message['status'] === 'in_progress') ? 'selected' : '' ?>>In Progress</option>
                                    <option value="closed" <?= ($message['status'] === 'closed') ? 'selected' : '' ?>>Closed</option>
                                    <option value="reopened" <?= ($message['status'] === 'reopened') ? 'selected' : '' ?>>Reopened</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit Response</button>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
