<?php
declare(strict_types=1);
// Returns an array of routes in the format: 
// [METHOD, URI, HANDLER] 
// OR [METHOD, URI, ['auth' => 'AuthMiddleware', 'permission' => [MiddlewareClass, permission_key], 'handler' => [ControllerClass, method]]]

return [
    // Root Route (Public)
    ['GET', '/', ['HomeController', 'index']],
    
    // Login Routes (Public)
    ['GET', '/login', ['LoginController', 'index']],
    ['POST', '/attempt-login', ['LoginController', 'attempt']],
    
    // Logout should be a POST request to prevent CSRF logout attacks.
    ['POST', '/logout', ['LoginController', 'logout']], 

    // =========================================================
    // Protected Routes (All require 'auth' => 'AuthMiddleware')
    // =========================================================

    // Protected Dashboard Route
    ['GET', '/dashboard', [
        'auth' => 'AuthMiddleware', 
        'permission' => ['PermissionMiddleware', 'self:view_dashboard'],
        'handler' => ['DashboardController', 'index']
    ]],

    // Tenant Management Routes (Super Admin Only)
    ['GET', '/tenants', [
        'auth' => 'AuthMiddleware', 
        'permission' => ['PermissionMiddleware', 'tenant:read_all'],
        'handler' => ['TenantController', 'index']
    ]],

    ['GET', '/tenants/create', [
        'auth' => 'AuthMiddleware', 
        'permission' => ['PermissionMiddleware', 'tenant:create'],
        'handler' => ['TenantController', 'create']
    ]],

    ['POST', '/tenants', [
        'auth' => 'AuthMiddleware', 
        'permission' => ['PermissionMiddleware', 'tenant:create'],
        'handler' => ['TenantController', 'store']
    ]],

    // Notification Routes 
    ['GET', '/notifications', [
        'auth' => 'AuthMiddleware', 
        'permission' => ['PermissionMiddleware', 'self:view_notifications'], 
        'handler' => ['NotificationController', 'index']
    ]],
    ['POST', '/notifications/mark-read', [
        'auth' => 'AuthMiddleware', 
        'permission' => ['PermissionMiddleware', 'self:manage_notifications'], 
        'handler' => ['NotificationController', 'markRead']
    ]],

    // Scope Test Route (Protected for debugging/testing)
    ['GET', '/test-scope', [
        'auth' => 'AuthMiddleware', 
        'permission' => ['PermissionMiddleware', 'system:test_route'], 
        'handler' => ['TestController', 'index']
    ]],


    // =========================================================
    // Employee Management Routes (Protected)
    // =========================================================
    // Index/List Page
    ['GET', '/employees', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'employee:read_all'],
        'handler' => ['EmployeeController', 'index']
    ]],

    // Quick Create Form
    ['GET', '/employees/create', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'employee:create'],
        'handler' => ['EmployeeController', 'create']
    ]],

    // Store/Process Quick Create
    ['POST', '/employees', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'employee:create'],
        'handler' => ['EmployeeController', 'store']
    ]],

    // View Employee Profile (show)
    ['GET', '/employees/{userId:\d+}', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'employee:read_all'],
        'handler' => ['EmployeeController', 'show']
    ]],

    // Edit Employee Profile Form
    ['GET', '/employees/{userId:\d+}/edit', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'employee:update'],
        'handler' => ['EmployeeController', 'edit']
    ]],
    
    // Process Update (PUT spoofed via POST)
    ['POST', '/employees/{userId:\d+}', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'employee:update'],
        'handler' => ['EmployeeController', 'update'] // The method handles the PUT logic
    ]],


    // API Route for Cascading Dropdown (Requires login for security, no specific RBAC needed)
    [
        'GET', '/api/positions', [
            'auth' => 'AuthMiddleware', 
            'permission' => ['PermissionMiddleware', 'employee:list'], 
            'handler' => ['EmployeeController', 'getPositionsByDepartment']
        ]
    ],

];