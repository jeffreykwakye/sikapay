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

    // View Employee Profile (show) - Allows viewing own profile (self:view_profile) OR any employee (employee:read)
    // This route handles the final redirect after a successful employee creation.
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
        'permission' => ['PermissionMiddleware', ['employee:update', 'self:update_profile']],
        'handler' => ['EmployeeController', 'update'] // The method handles the PUT logic
    ]],

    // Route for Viewing Employment History
    ['GET', '/employees/{userId:\d+}/history', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'employee:read_all'], 
        'handler' => ['EmployeeController', 'showHistory']
    ]],

    // Route for Uploading/Updating Profile Image
    ['POST', '/employees/{userId:\d+}/image', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'employee:update'], 
        'handler' => ['EmployeeController', 'updateProfileImage']
    ]],

    // Route for Uploading Staff Documents
    ['POST', '/employees/{userId:\d+}/files', [
        'auth' => 'AuthMiddleware', 
        'permission' => ['PermissionMiddleware', 'employee:update'], 
        'handler' => ['EmployeeController', 'uploadStaffFile']
    ]],

    ['POST', '/employees/{userId:\d+}/files/{fileId:\d+}/delete', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'employee:update'],
        'handler' => ['EmployeeController', 'deleteStaffFile']
    ]],

    // Route for Assigning Payroll Elements
    ['POST', '/employees/{userId:\d+}/payroll-elements', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'employee:assign_payroll_elements'],
        'handler' => ['EmployeeController', 'assignPayrollElement']
    ]],

    // Route for Unassigning Payroll Elements
    ['POST', '/employees/{userId:\d+}/payroll-elements/{payrollElementId:\d+}/unassign', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'employee:assign_payroll_elements'],
        'handler' => ['EmployeeController', 'unassignPayrollElement']
    ]],

    // Payroll Routes
    ['GET', '/payroll', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'payroll:manage_rules'],
        'handler' => ['PayrollController', 'index']
    ]],
    ['POST', '/payroll/period', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'payroll:manage_rules'],
        'handler' => ['PayrollController', 'createPeriod']
    ]],
    ['POST', '/payroll/run', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'payroll:prepare'],
        'handler' => ['PayrollController', 'runPayroll']
    ]],
    ['GET', '/payroll/payslips', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'payroll:view_all'],
        'handler' => ['PayrollController', 'viewPayslips']
    ]],
    ['GET', '/payroll/payslips/{periodId:\d+}', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'payroll:view_all'],
        'handler' => ['PayrollController', 'getPayslipsByPeriod']
    ]],
    ['GET', '/payroll/payslips/download/{payslipId:\d+}', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'payroll:view_all'],
        'handler' => ['PayrollController', 'downloadPayslip']
    ]],

    // Statutory Reports Routes
    ['GET', '/reports', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'payroll:view_all'],
        'handler' => ['StatutoryReportController', 'index']
    ]],
    ['GET', '/reports/paye/pdf', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'payroll:view_all'],
        'handler' => ['StatutoryReportController', 'generatePayeReportPdf']
    ]],
    ['GET', '/reports/paye/excel', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'payroll:view_all'],
        'handler' => ['StatutoryReportController', 'generatePayeReportExcel']
    ]],
    ['GET', '/reports/ssnit/pdf', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'payroll:view_all'],
        'handler' => ['StatutoryReportController', 'generateSsnitReportPdf']
    ]],
    ['GET', '/reports/ssnit/excel', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'payroll:view_all'],
        'handler' => ['StatutoryReportController', 'generateSsnitReportExcel']
    ]],

    // NOTE: The API Route for Cascading Dropdown is REMOVED as it's now handled by client-side JS.
 
    // =========================================================
    // Configuration Routes: Company Profile (Protected)
    // =========================================================
    // Display the Company Profile form
    ['GET', '/company-profile', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'tenant:manage_settings'],
        'handler' => ['CompanyProfileController', 'index']
    ]],

    // Process the update for general details (Name, TIN, Address, etc.)
    ['POST', '/company-profile/save', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'tenant:manage_settings'],
        'handler' => ['CompanyProfileController', 'save']
    ]],

    // Process the update for the logo file only
    ['POST', '/company-profile/upload-logo', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'tenant:manage_settings'],
        'handler' => ['CompanyProfileController', 'uploadLogo']
    ]],

    // =========================================================
    // Configuration Routes: Department Management (Protected)
    // =========================================================
    // List/Index Departments
    ['GET', '/departments', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'config:manage_departments'],
        'handler' => ['DepartmentController', 'index']
    ]],

    // Create/Store a new Department
    ['POST', '/departments/store', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'config:manage_departments'],
        'handler' => ['DepartmentController', 'store']
    ]],

    // Update an existing Department by ID
    ['POST', '/departments/update/{id:\d+}', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'config:manage_departments'],
        'handler' => ['DepartmentController', 'update']
    ]],

    // Delete a Department by ID
    ['POST', '/departments/delete/{id:\d+}', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'config:manage_departments'],
        'handler' => ['DepartmentController', 'delete']
    ]],
// -----------------------------------------------------------------
// =========================================================
// Configuration Routes: Position Management (Protected)
// =========================================================
    // List/Index Positions
    ['GET', '/positions', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'config:manage_positions'], // Using the constant defined in the Controller
        'handler' => ['PositionController', 'index']
    ]],

    // Create/Store a new Position
    ['POST', '/positions/store', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'config:manage_positions'],
        'handler' => ['PositionController', 'store']
    ]],

    // Update an existing Position by ID
    ['POST', '/positions/update/{id:\d+}', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'config:manage_positions'],
        'handler' => ['PositionController', 'update']
    ]],

    // Delete a Position by ID
    ['POST', '/positions/delete/{id:\d+}', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'config:manage_positions'],
        'handler' => ['PositionController', 'delete']
    ]],

    // =========================================================
    // Configuration Routes: Payroll Elements Management (Protected)
    // =========================================================
    // List/Index Payroll Elements
    ['GET', '/payroll-elements', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'config:manage_payroll_elements'],
        'handler' => ['AllowanceAndDeductionController', 'index']
    ]],

    // Create/Store a new Payroll Element
    ['POST', '/payroll-elements', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'config:manage_payroll_elements'],
        'handler' => ['AllowanceAndDeductionController', 'store']
    ]],

    // Update an existing Payroll Element by ID
    ['POST', '/payroll-elements/{id:\d+}', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'config:manage_payroll_elements'],
        'handler' => ['AllowanceAndDeductionController', 'update']
    ]],

    // Delete a Payroll Element by ID
    ['POST', '/payroll-elements/{id:\d+}/delete', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'config:manage_payroll_elements'],
        'handler' => ['AllowanceAndDeductionController', 'delete']
    ]],
];