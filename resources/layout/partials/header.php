<header class="main-header">
    <div class="logo">SikaPay</div>
    
    <?php 
    require __DIR__ . '/navbar.php'; 
    ?>
    
    <div class="user-controls">
        <a href="/notifications" class="notification-icon">
            🔔 
            <?php 
            if (isset($unreadNotificationCount) && $unreadNotificationCount > 0): 
            ?>
                <span class="badge badge-danger"><?= $unreadNotificationCount ?></span>
            <?php endif; ?>
        </a>
        <?php if (isset($userFirstName) && $userFirstName !== 'User'): ?>
            <span class="user-info">
                Welcome, **<?= htmlspecialchars($userFirstName) ?>**! 
            </span>
        <?php endif; ?>
        <a href="/logout" class="btn-logout">Log Out</a>
    </div>
</header>