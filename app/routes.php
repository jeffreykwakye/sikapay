<?php
declare(strict_types=1);
// Returns an array of routes in the format: 
// [METHOD, URI, HANDLER] 
// OR [METHOD, URI, ['middleware' => [MiddlewareClass, permission_key], 'handler' => [ControllerClass, method]]]

return [
    // Root Route
    ['GET', '/', ['HomeController', 'index']],
    
    // Login Routes (Unprotected)
    ['GET', '/login', ['LoginController', 'index']],
    ['POST', '/attempt-login', ['LoginController', 'attempt']],
    ['GET', '/logout', ['LoginController', 'logout']],

    // Protected Dashboard Route (Requires base login/access)
    ['GET', '/dashboard', [
        'middleware' => ['PermissionMiddleware', 'self:view_dashboard'], // Assume a general dashboard view permission
        'handler' => ['DashboardController', 'index']
    ]],

    // Tenant Management Routes (Super Admin Only)
    // Requires a Super Admin permission key (e.g., 'tenant:manage_all').
    ['GET', '/tenants', [
        'middleware' => ['PermissionMiddleware', 'tenant:read_all'],
        'handler' => ['TenantController', 'index']
    ]],
    ['GET', '/tenants/create', [
        'middleware' => ['PermissionMiddleware', 'tenant:create'],
        'handler' => ['TenantController', 'create']
    ]],
    ['POST', '/tenants', [
        'middleware' => ['PermissionMiddleware', 'tenant:create'],
        'handler' => ['TenantController', 'store']
    ]],

    // Notification Routes (Requires base login/access)
    ['GET', '/notifications', [
        'middleware' => ['PermissionMiddleware', 'self:view_notifications'], // New permission
        'handler' => ['NotificationController', 'index']
    ]],
    ['POST', '/notifications/mark-read', [
        'middleware' => ['PermissionMiddleware', 'self:manage_notifications'], // New permission
        'handler' => ['NotificationController', 'markRead']
    ]],

    // Scope Test Route (Protected for debugging/testing)
    ['GET', '/test-scope', [
        'middleware' => ['PermissionMiddleware', 'system:test_route'], // New permission for system tools
        'handler' => ['TestController', 'index']
    ]],
];