<header class="main-header">
    <div class="logo">SikaPay</div>
    
    <?php 
    // Include the separate navbar partial
    require __DIR__ . '/navbar.php'; 
    ?>
    
    <div class="user-controls">
        <a href="/notifications" class="notification-icon">
            🔔 
            <?php 
            // $unreadNotificationCount is available via the master's 'extract' call
            if (isset($unreadNotificationCount) && $unreadNotificationCount > 0): 
            ?>
                <span class="badge badge-danger"><?= $unreadNotificationCount ?></span>
            <?php endif; ?>
        </a>
        <span class="user-info">Hello, User (ID: <?= $userId ?? 'N/A' ?>)</span>
        <a href="/logout" class="btn-logout">Log Out</a>
    </div>
</header>