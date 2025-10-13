<div class="page-header">
    <h1><?= htmlspecialchars($title) ?></h1>
</div>

<div class="notifications-container">

    <?php 
    // Display any flash messages (e.g., if marking read failed)
    if (isset($_SESSION['flash_error'])): ?>
        <p class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error']) ?></p>
        <?php unset($_SESSION['flash_error']); 
    endif; 
    ?>

    <?php if (empty($notifications)): ?>
        <div class="alert alert-info">
            <p>You're all caught up! You have no notifications to display.</p>
        </div>
    <?php else: ?>
        
        <ul class="notification-list">
            <?php foreach ($notifications as $notification): 
                $isRead = (bool)$notification['is_read'];
                $readClass = $isRead ? 'notification-read' : 'notification-unread';
            ?>
                <li class="notification-item <?= $readClass ?>">
                    <div class="notification-header">
                        <span class="title">
                            <?= $isRead ? '✅' : '🔔' ?> 
                            <?= htmlspecialchars($notification['title']) ?>
                        </span>
                        <span class="date text-muted">
                            <?= date('M j, Y H:i', strtotime($notification['created_at'])) ?>
                        </span>
                    </div>
                    
                    <p class="notification-body"><?= nl2br(htmlspecialchars($notification['body'] ?? '')) ?></p>
                    
                    <?php if (!$isRead): ?>
                        <form method="POST" action="/notifications/mark-read" class="mark-read-form">
                            <input type="hidden" name="id" value="<?= (int)$notification['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-secondary">Mark as Read</button>
                        </form>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        
    <?php endif; ?>
</div>