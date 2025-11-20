<?php
/**
 * @var string $title
 * @var array $messages
 * @var callable $h
 * @var \Jeffrey\Sikapay\Security\CsrfToken $CsrfToken
 */

$this->title = $title;

if (!isset($h)) {
    $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>

<div class="page-header">
    <h3 class="fw-bold mb-3">Support Center</h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/support">Support</a></li>
    </ul>
</div>

<div class="row">
    <!-- Create New Support Ticket Card -->
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Create a New Support Ticket</h4>
            </div>
            <div class="card-body">
                <p class="card-category">
                    Have an issue or a question? Send us a message and our support team will get back to you.
                </p>
                <form action="/support" method="POST">
                    <?= $CsrfToken::field() ?>
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" required minlength="5" value="<?= $h($_SESSION['flash_input']['subject'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="8" required minlength="20"><?= $h($_SESSION['flash_input']['message'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Message</button>
                </form>
                <?php
                // Clear flash input after displaying it
                if (isset($_SESSION['flash_input'])) {
                    unset($_SESSION['flash_input']);
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Submitted Tickets History Card -->
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Your Submitted Tickets</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($messages)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">You have not submitted any support tickets yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($messages as $message): ?>
                                    <tr>
                                        <td><?= date('M j, Y H:i', strtotime($message['created_at'])) ?></td>
                                        <td><?= $h($message['subject']) ?></td>
                                        <td>
                                            <?php
                                            $statusClass = 'secondary';
                                            if ($message['status'] === 'open' || $message['status'] === 'reopened') {
                                                $statusClass = 'success';
                                            } elseif ($message['status'] === 'in_progress') {
                                                $statusClass = 'warning';
                                            }
                                            ?>
                                            <span class="badge bg-<?= $statusClass ?>"><?= $h(ucfirst(str_replace('_', ' ', $message['status']))) ?></span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#messageModal-<?= $h($message['id']) ?>">
                                                <i class="icon-eye"></i> View
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

<!-- Modals for viewing message details -->
<?php if (!empty($messages)): ?>
    <?php foreach ($messages as $message): ?>
        <div class="modal fade" id="messageModal-<?= $h($message['id']) ?>" tabindex="-1" aria-labelledby="messageModalLabel-<?= $h($message['id']) ?>" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="messageModalLabel-<?= $h($message['id']) ?>"><?= $h($message['subject']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <h6>Your Message:</h6>
                        <p class="border-start border-4 border-primary ps-3 mb-4">
                            <?= nl2br($h($message['message'])) ?>
                            <br>
                            <small class="text-muted">Sent on: <?= date('M j, Y H:i', strtotime($message['created_at'])) ?></small>
                        </p>

                        <?php if (!empty($message['super_admin_response'])): ?>
                            <h6>Support Response:</h6>
                            <div class="alert alert-success">
                                <?= nl2br($h($message['super_admin_response'])) ?>
                            </div>
                        <?php else: ?>
                             <h6>Support Response:</h6>
                            <div class="alert alert-secondary">
                                <p>No response from our support team yet. We will get back to you shortly.</p>
                            </div>
                        <?php endif; ?>

                        <?php if ($message['status'] !== 'closed'): ?>
                            <hr>
                            <h6>Reply to this Ticket:</h6>
                            <form action="/support" method="POST">
                                <?= $CsrfToken::field() ?>
                                <input type="hidden" name="message_id" value="<?= $h($message['id']) ?>">
                                <input type="hidden" name="subject" value="<?= $h($message['subject']) ?>">
                                <div class="mb-3">
                                    <label for="reply_content-<?= $h($message['id']) ?>" class="form-label">Your Message</label>
                                    <textarea class="form-control" id="reply_content-<?= $h($message['id']) ?>" name="reply_content" rows="4" required minlength="5"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Send Reply</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
