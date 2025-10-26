<!-- <header class="main-header">
    <div class="logo">SikaPay</div>
    
    <?php 
    //require __DIR__ . '/navbar.php'; 
    ?>
    
    <div class="user-controls">
        <a href="/notifications" class="notification-icon">
            
            <?php 
            //if (isset($unreadNotificationCount) && $unreadNotificationCount > 0): 
            ?>
                <span class="badge badge-danger"><//?= $unreadNotificationCount ?></span>
            <?php //endif; ?>
        </a>
        <?php //if (isset($userFirstName) && $userFirstName !== 'User'): ?>
            <span class="user-info">
                Welcome, **<//?= $h($userFirstName) ?>**! </span>
        <?php //endif; ?>
        
        <form action="/logout" method="POST" class="logout-form" style="display: inline;">
            <//?= $CsrfToken::field() ?> 
            
            <button type="submit" class="btn-logout">
                Log Out
            </button>
        </form>
    </div>
</header> -->


<div class="logo-header" data-background-color="dark">
    <a href="/" class="logo">SikaPay</a>
    <div class="nav-toggle">
        <button class="btn btn-toggle toggle-sidebar">
            <i class="gg-menu-right"></i>
        </button>
        <button class="btn btn-toggle sidenav-toggler">
            <i class="gg-menu-left"></i>
        </button>
    </div>
    <button class="topbar-toggler more">
        <i class="gg-more-vertical-alt"></i>
    </button>
</div>