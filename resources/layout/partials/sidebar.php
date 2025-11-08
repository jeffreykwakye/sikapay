<?php
// We no longer need the comment about defining hasPermission, as we rely on $auth

// The $auth object is available via the master's 'extract' call
// The $isSuperAdmin variable is also available.
?>

<div class="sidebar" data-background-color="dark">
    <div class="sidebar-logo">
        <div class="logo-header" data-background-color="dark">
            <a href="/" class="logo">
                <?php if (isset($tenantLogo) && ($subscriptionPlan === 'Professional' || $subscriptionPlan === 'Enterprise')): ?>
                    <img src="<?= htmlspecialchars($tenantLogo) ?>" alt="navbar brand" class="navbar-brand" height="20" />
                <?php else: ?>
                    <img src="/assets/img/kaiadmin/logo_light.svg" alt="navbar brand" class="navbar-brand" height="20" />
                <?php endif; ?>
            </a>
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
        </div>

    <div class="sidebar-wrapper scrollbar scrollbar-inner">
        <div class="sidebar-content">
            <ul class="nav nav-secondary">
                
                <?php if ($auth->hasPermission('self:view_dashboard')): ?>
                <li class="nav-item">
                    <a href="/">
                        <i class="fas fa-home"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                <?php endif; ?>

                <?php if ($isSuperAdmin): ?>
                
                <li class="nav-section">
                    <span class="sidebar-mini-icon">
                        <i class="fa fa-ellipsis-h"></i>
                    </span>
                    <h4 class="text-section">PLATFORM ADMIN</h4>
                </li>

                <?php if ($auth->hasPermission('super:manage_statutory_rates')): ?>
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#plans_subscription">
                        <i class="fas fa-layer-group"></i>
                        <p>Plans & Subscriptions</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="plans_subscription">
                        <ul class="nav nav-collapse">
                            <li><a href="/plans"><span class="sub-item">Plans</span></a></li>
                            <li><a href="/subscriptions"><span class="sub-item">Subscriptions</span></a></li>
                        </ul>
                    </div>
                </li>
                <?php endif; ?>

                <?php if ($auth->hasPermission('super:view_tenants')): ?>
                <li class="nav-item">
                    <a href="/tenants">
                        <i class="fas fa-users"></i>
                        <p>Tenant Management</p>
                    </a>
                </li>
                <?php endif; ?>

                <?php if ($auth->hasPermission('super:manage_statutory_rates')): ?>
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#statutoryRates">
                        <i class="fas fa-money-check"></i>
                        <p>Statutory Rates</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="statutoryRates">
                        <ul class="nav nav-collapse">
                            <li><a href="/paye-rates"><span class="sub-item">PAYE Rates</span></a></li>
                            <li><a href="/ssnit-rates"><span class="sub-item">SSNIT Rates</span></a></li>
                        </ul>
                    </div>
                </li>
                <?php endif; ?>

                <?php if ($auth->isSuperAdmin()): ?>
                <li class="nav-item">
                    <a href="/reports">
                        <i class="fas fa-chart-bar"></i>
                        <p>Platform Reports</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="/audit-logs">
                        <i class="far fa-eye"></i>
                        <p>Platform Audit Logs</p>
                    </a>
                </li>
                <?php endif; ?>

                <li class="nav-section">
                    <span class="sidebar-mini-icon">
                        <i class="fa fa-ellipsis-h"></i>
                    </span>
                    <h4 class="text-section">SHARED ACCESS</h4>
                </li>
                
                <?php endif; // End Super Admin Section ?>


                <?php if (!$isSuperAdmin): ?>
                
                <li class="nav-section">
                    <span class="sidebar-mini-icon">
                        <i class="fa fa-ellipsis-h"></i>
                    </span>
                    <h4 class="text-section">PAYROLL OPERATIONS</h4>
                </li>

                <?php if ($auth->hasPermission('config:manage_departments') || $auth->hasPermission('tenant:manage_settings') || $auth->hasPermission('config:manage_positions')): ?>
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#tenant_setup">
                        <i class="fas fa-cogs"></i>
                        <p>Setup & Config</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="tenant_setup">
                        <ul class="nav nav-collapse">
                            <?php if ($auth->hasPermission('tenant:manage_settings')): ?>
                            <li><a href="/company-profile"><span class="sub-item">Company Profile</span></a></li>
                            <?php endif; ?>
                            <?php if ($auth->hasPermission('config:manage_departments')): ?>
                            <li><a href="/departments"><span class="sub-item">Departments</span></a></li>
                            <?php endif; ?>
                            <?php if ($auth->hasPermission('config:manage_positions')): ?>
                            <li><a href="/positions"><span class="sub-item">Positions</span></a></li>
                            <?php endif; ?>
                            <?php if ($auth->hasPermission('config:manage_payroll_elements')): ?>
                            <li><a href="/payroll-elements"><span class="sub-item">Payroll Elements</span></a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </li>
                <?php endif; ?>

                <?php if ($auth->hasPermission('employee:read_all')): ?>
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#staff_management">
                        <i class="fas fa-users-cog"></i>
                        <p>Staff Management</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="staff_management">
                        <ul class="nav nav-collapse">
                            <?php if ($auth->hasPermission('employee:read_all')): ?>
                            <li><a href="/employees"><span class="sub-item">Staff List</span></a></li>
                            <?php endif; ?>
                            <?php if ($auth->hasPermission('employee:create')): ?>
                            <li><a href="/employees/create"><span class="sub-item">Add New Staff</span></a></li>
                            <?php endif; ?>
                            <li><a href="/active-staff"><span class="sub-item">Active Staff</span></a></li>
                            <li><a href="/inactive-staff"><span class="sub-item">Inactive Staff</span></a></li>
                        </ul>
                    </div>
                </li>
                <?php endif; ?>

                <?php if ($auth->hasPermission('payroll:prepare') || $auth->hasPermission('payroll:view_all')): ?>
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#payroll_management">
                        <i class="fas fa-money-check-alt"></i>
                        <p>Payroll</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="payroll_management">
                        <ul class="nav nav-collapse">
                            <?php if ($auth->hasPermission('payroll:prepare')): ?>
                            <li><a href="/payroll"><span class="sub-item">Prepare Payroll</span></a></li>
                            <?php endif; ?>
                            <?php if ($auth->hasPermission('payroll:view_all')): ?>
                            <li><a href="/payroll/payslips"><span class="sub-item">Payslip History</span></a></li>
                            <li><a href="/reports"><span class="sub-item">Statutory Reports</span></a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </li>
                <?php endif; ?>
                
                <?php if ($auth->hasPermission('leave:approve') || $auth->hasPermission('self:manage_leave')): ?>
                <li class="nav-item">
                    <a href="/leave">
                        <i class="fas fa-calendar-alt"></i>
                        <p>Leave & Time Off</p>
                    </a>
                </li>
                <?php endif; ?>

                <?php if ($auth->hasPermission('tenant:view_audit_logs')): ?>
                <li class="nav-item">
                    <a href="/tenant-audit-logs">
                        <i class="far fa-eye"></i>
                        <p>Audit Logs</p>
                    </a>
                </li>
                <?php endif; ?>

                <?php if ($auth->hasPermission('tenant:manage_subscription')): ?>
                <li class="nav-item">
                    <a href="/subscription">
                        <i class="fas fa-credit-card"></i>
                        <p>Subscription</p>
                    </a>
                </li>
                <?php endif; ?>
                
                <li class="nav-section">
                    <span class="sidebar-mini-icon">
                        <i class="fa fa-ellipsis-h"></i>
                    </span>
                    <h4 class="text-section">SELF-SERVICE</h4>
                </li>
                
                <?php endif; // End Tenant User Section ?>


                <?php if ($auth->hasPermission('self:view_notifications')): ?>
                <li class="nav-item">
                    <a href="/messages">
                        <i class="fas fa-envelope"></i>
                        <p>Messages</p>
                        <span class="badge badge-secondary">1</span>
                    </a>
                </li>
                <?php endif; ?>

                <?php if ($auth->hasPermission('self:update_profile')): ?>
                <li class="nav-item">
                    <a href="/my-account">
                        <i class="fas fa-user-circle"></i>
                        <p>My Account</p>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>