<?php if (isset($subscriptionStatus) && $subscriptionStatus === 'past_due'): ?>
            <div class="subscription-notice">
                Your subscription is past due. Please <a href="/subscription">renew your plan</a> to restore full functionality.
            </div>
        <?php endif; ?>
        <?php if ($isImpersonating): ?>
            <div class="impersonation-notice">
                <i class="fas fa-user-secret"></i> You are currently impersonating a Tenant Admin.
            </div>
        <?php endif; ?>
<nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
    <div class="container-fluid">
        <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
            <!-- <li class="nav-item topbar-icon dropdown hidden-caret d-flex d-lg-none">
                <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" 
                    aria-expanded="false" aria-haspopup="true">
                    <i class="fa fa-search"></i>
                </a>
                <ul class="dropdown-menu dropdown-search animated fadeIn">
                    <form class="navbar-left navbar-form nav-search">
                        <div class="input-group">
                            <input type="text" placeholder="Search ..." class="form-control" />
                        </div>
                    </form>
                </ul>
            </li> -->
            <!-- // $isSuperAdmin is available via the master's 'extract' call -->
            <?php //if (isset($isSuperAdmin) && $isSuperAdmin):?>
            <!-- The message icon was here, removed since in-app messaging is not yet implemented -->

            <li class="nav-item topbar-icon dropdown hidden-caret">  
                <?php if (isset($unreadNotificationCount) && $unreadNotificationCount >= 0):?>          
                <a
                    class="nav-link dropdown-toggle"
                    href="#"
                    id="notifDropdown"
                    role="button"
                    data-bs-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false">
                    <i class="fa fa-bell"></i>
                    <span class="notification">
                        <?= $unreadNotificationCount ?>
                    </span>
                </a>
                
                <ul class="dropdown-menu notif-box animated fadeIn" aria-labelledby="notifDropdown">
                    <li>
                        <div class="dropdown-title">
                            You have <?= $unreadNotificationCount ?> new notification
                        </div>
                    </li>
                    <li>
                        <div class="notif-scroll scrollbar-outer">
                            <div class="notif-center">
                                <?php
                                if (!function_exists('time_ago')) {
                                    function time_ago($datetime, $full = false) {
                                        $now = new DateTime;
                                        $ago = new DateTime($datetime);
                                        $diff = $now->diff($ago);
                                
                                        // Manually calculate weeks from the total days
                                        $weeks = floor($diff->d / 7);
                                        $diff->d -= $weeks * 7;
                                
                                        $string = [
                                            'y' => $diff->y,
                                            'm' => $diff->m,
                                            'w' => $weeks,
                                            'd' => $diff->d,
                                            'h' => $diff->h,
                                            'i' => $diff->i,
                                            's' => $diff->s,
                                        ];
                                
                                        $string_map = ['y' => 'year', 'm' => 'month', 'w' => 'week', 'd' => 'day', 'h' => 'hour', 'i' => 'minute', 's' => 'second'];
                                        $result_string = [];
                                
                                        foreach ($string as $key => $value) {
                                            if ($value > 0) {
                                                $result_string[] = $value . ' ' . $string_map[$key] . ($value > 1 ? 's' : '');
                                            }
                                        }
                                
                                        if (!$full) $result_string = array_slice($result_string, 0, 1);
                                
                                        return $result_string ? implode(', ', $result_string) . ' ago' : 'just now';
                                    }
                                }

                                if (!function_exists('get_notification_icon_class')) {
                                    function get_notification_icon_class($type) {
                                        switch ($type) {
                                            case 'payroll_run': return 'fa fa-coins';
                                            case 'employee_added': return 'fa fa-user-plus';
                                            case 'subscription': return 'fa fa-credit-card';
                                            case 'report_generated': return 'fa fa-file-alt';
                                            default: return 'fa fa-bell';
                                        }
                                    }
                                }
                                
                                if (!function_exists('get_notification_color_class')) {
                                    function get_notification_color_class($type) {
                                        switch ($type) {
                                            case 'payroll_run': return 'notif-success';
                                            case 'employee_added': return 'notif-primary';
                                            case 'subscription': return 'notif-warning';
                                            case 'report_generated': return 'notif-info';
                                            default: return 'notif-default';
                                        }
                                    }
                                }

                                if (empty($navbarNotifications)): ?>
                                    <div class="notif-content">
                                        <span class="block text-center">No new notifications</span>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($navbarNotifications as $notification): ?>
                                        <a href="#">
                                            <div class="notif-icon <?= get_notification_color_class($notification['type']) ?>">
                                                <i class="<?= get_notification_icon_class($notification['type']) ?>"></i>
                                            </div>
                                            <div class="notif-content">
                                                <span class="block"><?= htmlspecialchars($notification['title']) ?></span>
                                                <span class="time"><?= time_ago($notification['created_at']) ?></span>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </li>
                    <li>
                        <a class="see-all" href="/notifications"
                            >See all notifications<i class="fa fa-angle-right"></i>
                        </a>
                    </li>
                </ul>
                <?php endif; ?>
            </li>
            <li class="nav-item topbar-icon dropdown hidden-caret">
                <a
                    class="nav-link"
                    data-bs-toggle="dropdown"
                    href="#"
                    aria-expanded="false">
                    <i class="fas fa-layer-group"></i>
                </a>
                <div class="dropdown-menu quick-actions animated fadeIn">
                    <div class="quick-actions-header">
                        <span class="title mb-1">Quick Actions</span>
                        <span class="subtitle op-7">Shortcuts</span>
                    </div>
                    <div class="quick-actions-scroll scrollbar-outer">
                        <div class="quick-actions-items">
                            <?php if (isset($isSuperAdmin) && $isSuperAdmin):?>
                            <div class="row m-0">
                                <?php if ($auth->hasPermission('super:view_tenants')): ?>
                                <a class="col-6 col-md-4 p-0" href="/tenants">
                                    <div class="quick-actions-item">
                                        <div class="avatar-item bg-success rounded-circle">
                                            <i class="fas fa-users-cog"></i>
                                        </div>
                                        <span class="text">Manage Tenants</span>
                                    </div>
                                </a>
                                <?php endif; ?>
                                <?php if ($auth->hasPermission('super:manage_plans')): ?>
                                <a class="col-6 col-md-4 p-0" href="/super/plans">
                                    <div class="quick-actions-item">
                                        <div class="avatar-item bg-danger rounded-circle">
                                            <i class="fas fa-file-invoice"></i>
                                        </div>
                                        <span class="text">Subscription Plans</span>
                                    </div>
                                </a>
                                <?php endif; ?>
                                <?php if ($auth->hasPermission('super:view_reports')): ?>
                                <a class="col-6 col-md-4 p-0" href="/super/reports">
                                    <div class="quick-actions-item">
                                        <div class="avatar-item bg-info rounded-circle">
                                            <i class="fas fa-chart-bar"></i>
                                        </div>
                                        <span class="text">System Reports</span>
                                    </div>
                                </a>
                                <?php endif; ?>
                                <?php if ($auth->hasPermission('super:manage_users')): ?>
                                <a class="col-6 col-md-4 p-0" href="/super/users">
                                    <div class="quick-actions-item">
                                        <div class="avatar-item bg-primary rounded-circle">
                                            <i class="fas fa-user-friends"></i>
                                        </div>
                                        <span class="text">All Users</span>
                                    </div>
                                </a>
                                <?php endif; ?>
                                <?php if ($auth->hasPermission('super:view_audit_logs')): ?>
                                <a class="col-6 col-md-4 p-0" href="/super/audit-logs">
                                    <div class="quick-actions-item">
                                        <div class="avatar-item bg-secondary rounded-circle">
                                            <i class="far fa-eye"></i>
                                        </div>
                                        <span class="text">System Audit Logs</span>
                                    </div>
                                </a>
                                <?php endif; ?>
                                <?php if ($auth->hasPermission('super:manage_settings')): ?>
                                <a class="col-6 col-md-4 p-0" href="/super/settings">
                                    <div class="quick-actions-item">
                                        <div class="avatar-item bg-warning rounded-circle">
                                            <i class="fas fa-cogs"></i>
                                        </div>
                                        <span class="text">Settings</span>
                                    </div>
                                </a>
                                <?php endif; ?>
                            </div>

                            <?php else: ?>

                            <div class="row m-0">
                                <?php if ($auth->hasPermission('employee:create')): ?>
                                <a class="col-6 col-md-4 p-0" href="/employees/create">
                                    <div class="quick-actions-item">
                                        <div class="avatar-item bg-primary rounded-circle">
                                            <i class="fas fa-user-plus"></i>
                                        </div>
                                        <span class="text">Add Employee</span>
                                    </div>
                                </a>
                                <?php endif; ?>
                                <?php if ($auth->hasPermission('payroll:prepare')): ?>
                                <a class="col-6 col-md-4 p-0" href="/payroll">
                                    <div class="quick-actions-item">
                                        <div class="avatar-item bg-info rounded-circle">
                                            <i class="fas fa-coins"></i>
                                        </div>
                                        <span class="text">Run Payroll</span>
                                    </div>
                                </a>
                                <?php endif; ?>
                                <?php if ($auth->hasPermission('payroll:run_reports')): ?>
                                <a class="col-6 col-md-4 p-0" href="/reports">
                                    <div class="quick-actions-item">
                                        <div class="avatar-item bg-dark rounded-circle">
                                            <i class="fas fa-file-alt"></i>
                                        </div>
                                        <span class="text">Statutory Reports</span>
                                    </div>
                                </a>
                                <?php endif; ?>
                                <?php if ($auth->hasPermission('config:manage_departments')): ?>
                                <a class="col-6 col-md-4 p-0" href="/departments">
                                    <div class="quick-actions-item">
                                        <div class="avatar-item bg-secondary rounded-circle">
                                            <i class="fas fa-building"></i>
                                        </div>
                                        <span class="text">Departments</span>
                                    </div>
                                </a>
                                <?php endif; ?>
                                <?php if ($auth->hasPermission('config:manage_positions')): ?>
                                <a class="col-6 col-md-4 p-0" href="/positions">
                                    <div class="quick-actions-item">
                                        <div class="avatar-item bg-danger rounded-circle">
                                            <i class="fas fa-briefcase"></i>
                                        </div>
                                        <span class="text">Positions</span>
                                    </div>
                                </a>
                                <?php endif; ?>
                                <?php if ($auth->hasPermission('tenant:manage_subscription')): ?>
                                <a class="col-6 col-md-4 p-0" href="/subscription">
                                    <div class="quick-actions-item">
                                        <div class="avatar-item bg-warning rounded-circle">
                                            <i class="fas fa-credit-card"></i>
                                        </div>
                                        <span class="text">Manage Subscription</span>
                                    </div>
                                </a>
                                <?php endif; ?>
                                <?php if ($auth->hasPermission('tenant:view_audit_logs')): ?>
                                <a class="col-6 col-md-4 p-0" href="/activity-log">
                                    <div class="quick-actions-item">
                                        <div class="avatar-item bg-success rounded-circle">
                                            <i class="fas fa-history"></i>
                                        </div>
                                        <span class="text">Recent Activity</span>
                                    </div>
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </li>

            <li class="nav-item topbar-user dropdown hidden-caret">
                <a
                    class="dropdown-toggle profile-pic"
                    data-bs-toggle="dropdown"
                    href="#"
                    aria-expanded="false">
                    <div class="avatar-sm">
                        <?php 
                            $profileImageSrc = !empty($userProfileImageUrl) 
                                ? $h($userProfileImageUrl) 
                                : '/assets/images/profiles/placeholder.jpg';
                        ?>
                        <img
                            src="<?= $profileImageSrc ?>"
                            alt="User Profile"
                            class="avatar-img rounded-circle"
                        />
                    </div>
                    <span class="profile-username">
                        <span class="op-7">Hi,</span>
                        <?php if (isset($userFirstName) && $userFirstName !== 'User'): ?>
                            <span class="fw-bold"><?= $h($userFirstName) ?></span>
                        <?php endif; ?>
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-user animated fadeIn">
                    <div class="dropdown-user-scroll scrollbar-outer">
                        <li>
                            <div class="user-box">
                                <div class="avatar-lg">
                                    <img
                                        src="<?= $profileImageSrc ?>"
                                        alt="image profile"
                                        class="avatar-img rounded" />
                                </div>
                                <div class="u-text">
                                    <h4><?= $h($userFirstName) ?></h4>
                                    <p class="text-muted"><?= $h($userEmail) ?></p>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="dropdown-divider"></div>

                            <a class="dropdown-item" href="/my-account/change-password">Change Password</a>

                            <div class="dropdown-divider"></div>

                            <form action="/logout" method="POST" class="logout-form" style="display: inline;">
                                <?= $CsrfToken::field() ?> 
                                
                                <button type="submit" class="btn-logout btn-sm btn-info btn mx-2 px-2">
                                    <i class="fas fa-sign-out-alt"></i> Log Out
                                </button>
                            </form>
                        </li>
                    </div>
                </ul>
            </li>
        </ul>
    </div>
</nav>