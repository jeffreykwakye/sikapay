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

    // =========================================================
    // Super Admin Routes
    // =========================================================
    ['GET', '/super/dashboard', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'super_admin'], // Simple role check
        'handler' => ['SuperAdminController', 'index']
    ]],
    ['GET', '/super/plans', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'super_admin'],
        'handler' => ['SuperAdminController', 'plans']
    ]],
    ['GET', '/super/subscriptions', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'super_admin'],
        'handler' => ['SuperAdminController', 'subscriptions']
    ]],
    ['GET', '/super/reports', [ // NEW
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'super:view_reports'], // Assuming a permission for viewing reports
        'handler' => ['SuperAdminController', 'reports']
    ]],

    // Plan Management Routes (Super Admin Only)
    ['GET', '/super/plans/{id:\d+}', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'super:manage_plans'],
        'handler' => ['PlanController', 'show']
    ]],
    ['POST', '/super/plans/{id:\d+}', [ // Using POST for update for simplicity
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'super:manage_plans'],
        'handler' => ['PlanController', 'update']
    ]],

    // Tenant Management Routes (Super Admin Only)
    ['GET', '/tenants', [
        'auth' => 'AuthMiddleware', 
        'permission' => ['PermissionMiddleware', 'super:view_tenants'],
        'handler' => ['SuperAdminController', 'tenantsIndex']
    ]],
    ['GET', '/tenants/create', [
        'auth' => 'AuthMiddleware', 
        'permission' => ['PermissionMiddleware', 'super:create_tenant'],
        'handler' => ['SuperAdminController', 'tenantsCreate']
    ]],
    ['POST', '/tenants', [
        'auth' => 'AuthMiddleware', 
        'permission' => ['PermissionMiddleware', 'super:create_tenant'],
        'handler' => ['SuperAdminController', 'tenantsStore']
    ]],
    ['GET', '/tenants/{id:\d+}', [ // NEW
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'super:view_tenants'],
        'handler' => ['SuperAdminController', 'tenantsShow']
    ]],
    ['POST', '/tenants/{id:\d+}/subscription/cancel', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'super:manage_subscriptions'],
        'handler' => ['SuperAdminController', 'cancelTenantSubscription']
    ]],
    ['POST', '/tenants/{id:\d+}/subscription/renew', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'super:manage_subscriptions'],
        'handler' => ['SuperAdminController', 'renewTenantSubscription']
    ]],
    ['POST', '/tenants/{id:\d+}/subscription/upgrade', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'super:manage_subscriptions'],
        'handler' => ['SuperAdminController', 'upgradeTenantSubscription']
    ]],
    ['POST', '/tenants/{id:\d+}/subscription/downgrade', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'super:manage_subscriptions'],
        'handler' => ['SuperAdminController', 'downgradeTenantSubscription']
    ]],

    ['POST', '/super/tenants/{tenantId}/send-email', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'super:manage_tenants'],
        'handler' => ['SuperAdminController', 'sendEmailToTenant']
    ]],

    // Impersonation Routes (Super Admin Only)
    ['GET', '/super/impersonate/{tenantId:\d+}', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'super:impersonate_tenant_admin'],
        'handler' => ['SuperAdminController', 'impersonateTenantAdmin']
    ]],

    // Statutory Rates Management Routes (Super Admin Only)
    ['GET', '/super/statutory-rates/paye', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'super:manage_statutory_rates'],
        'handler' => ['StatutoryRateController', 'payeTaxBandsIndex']
    ]],
    ['POST', '/super/statutory-rates/paye', [ // NEW Store Tax Band
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'super:manage_statutory_rates'],
        'handler' => ['StatutoryRateController', 'storeTaxBand']
    ]],
    ['POST', '/super/statutory-rates/paye/{id:\d+}', [ // NEW Update Tax Band
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'super:manage_statutory_rates'],
        'handler' => ['StatutoryRateController', 'updateTaxBand']
    ]],
    ['POST', '/super/statutory-rates/paye/{id:\d+}/delete', [ // NEW Delete Tax Band
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'super:manage_statutory_rates'],
        'handler' => ['StatutoryRateController', 'deleteTaxBand']
    ]],
    ['GET', '/api/statutory-rates/paye/{id:\d+}', [ // NEW API Get Tax Band Details
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'super:manage_statutory_rates'],
        'handler' => ['StatutoryRateController', 'getTaxBandDetails']
    ]],
    ['GET', '/super/statutory-rates/ssnit', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'super:manage_statutory_rates'],
        'handler' => ['StatutoryRateController', 'ssnitRatesIndex']
    ]],
    ['GET', '/super/statutory-rates/wht', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'super:manage_statutory_rates'],
        'handler' => ['StatutoryRateController', 'withholdingTaxRatesIndex']
    ]],
    ['POST', '/super/statutory-rates/ssnit', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'super:manage_statutory_rates'],
        'handler' => ['StatutoryRateController', 'storeSsnitRate']
    ]],
    ['POST', '/super/statutory-rates/ssnit/{id:\d+}', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'super:manage_statutory_rates'],
        'handler' => ['StatutoryRateController', 'updateSsnitRate']
    ]],
    ['POST', '/super/statutory-rates/ssnit/{id:\d+}/delete', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'super:manage_statutory_rates'],
        'handler' => ['StatutoryRateController', 'deleteSsnitRate']
    ]],
    // API Route for fetching SSNIT rate details (for modals)
    ['GET', '/api/statutory-rates/ssnit/{id:\d+}', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'super:manage_statutory_rates'],
        'handler' => ['StatutoryRateController', 'getSsnitRateDetails']
    ]],
    ['POST', '/super/statutory-rates/wht', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'super:manage_statutory_rates'],
        'handler' => ['StatutoryRateController', 'storeWithholdingTaxRate']
    ]],
    ['POST', '/super/statutory-rates/wht/{id:\d+}', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'super:manage_statutory_rates'],
        'handler' => ['StatutoryRateController', 'updateWithholdingTaxRate']
    ]],
    ['POST', '/super/statutory-rates/wht/{id:\d+}/delete', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'super:manage_statutory_rates'],
        'handler' => ['StatutoryRateController', 'deleteWithholdingTaxRate']
    ]],
    // API Route for fetching WHT rate details (for modals)
    ['GET', '/api/statutory-rates/wht/{id:\d+}', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'super:manage_statutory_rates'],
        'handler' => ['StatutoryRateController', 'getWithholdingTaxRateDetails']
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
    ['GET', '/test/payslip-sample', [ // New route for sample payslip
        'auth' => 'AuthMiddleware', 
        'handler' => ['TestController', 'payslipSample']
    ]],
    ['GET', '/test/paye-pdf-sample', [ // New route for sample PAYE PDF
        'auth' => 'AuthMiddleware', 
        'handler' => ['TestController', 'payePdfSample']
    ]],
    ['GET', '/test/ssnit-pdf-sample', [ // New route for sample SSNIT PDF
        'auth' => 'AuthMiddleware', 
        'handler' => ['TestController', 'ssnitPdfSample']
    ]],
    ['GET', '/test/bank-advice-pdf-sample', [ // New route for sample Bank Advice PDF
        'auth' => 'AuthMiddleware', 
        'handler' => ['TestController', 'bankAdvicePdfSample']
    ]],
    ['GET', '/test/paye-excel-sample', [ // New route for sample PAYE Excel
        'auth' => 'AuthMiddleware', 
        'handler' => ['TestController', 'payeExcelSample']
    ]],
    ['GET', '/test/ssnit-excel-sample', [ // New route for sample SSNIT Excel
        'auth' => 'AuthMiddleware', 
        'handler' => ['TestController', 'ssnitExcelSample']
    ]],
    ['GET', '/test/bank-advice-excel-sample', [ // New route for sample Bank Advice Excel
        'auth' => 'AuthMiddleware', 
        'handler' => ['TestController', 'bankAdviceExcelSample']
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

    // Active Staff
    ['GET', '/active-staff', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'employee:read_all'],
        'handler' => ['EmployeeController', 'activeStaff']
    ]],

    // Inactive Staff
    ['GET', '/inactive-staff', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'employee:read_all'],
        'handler' => ['EmployeeController', 'inactiveStaff']
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
    
    // Specific Update Routes for Employee Edit Page
    ['POST', '/employees/{userId:\d+}/personal', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'employee:update'],
        'handler' => ['EmployeeController', 'updatePersonalData']
    ]],
    ['POST', '/employees/{userId:\d+}/employment', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'employee:update'],
        'handler' => ['EmployeeController', 'updateEmploymentData']
    ]],
    ['POST', '/employees/{userId:\d+}/statutory', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'employee:update'],
        'handler' => ['EmployeeController', 'updateStatutoryData']
    ]],
    ['POST', '/employees/{userId:\d+}/bank', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'employee:update'],
        'handler' => ['EmployeeController', 'updateBankData']
    ]],
    ['POST', '/employees/{userId:\d+}/salary', [ // NEW Route for Salary Update
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'employee:update'], // Using general employee update permission
        'handler' => ['EmployeeController', 'updateSalary']
    ]],
    ['POST', '/employees/{userId:\d+}/emergency', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'employee:update'],
        'handler' => ['EmployeeController', 'updateEmergencyContactData']
    ]],
    ['POST', '/employees/{userId:\d+}/role', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'employee:update'],
        'handler' => ['EmployeeController', 'updateRoleAndPermissions']
    ]],
    ['POST', '/employees/{userId:\d+}/permissions', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'tenant:configure_roles'],
        'handler' => ['EmployeeController', 'updateIndividualPermissions']
    ]],
    ['POST', '/employees/{userId:\d+}/permissions/reset-to-defaults', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'tenant:configure_roles'], // Same permission as updating individual permissions
        'handler' => ['EmployeeController', 'resetPermissionsToDefaults']
    ]],
    ['POST', '/employees/{userId:\d+}/permissions/{permissionId:\d+}/toggle', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'tenant:configure_roles'],
        'handler' => ['EmployeeController', 'toggleIndividualPermission']
    ]],

    // Note: Salary and Role updates are handled via modals on the profile view page.

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

    // Route for securely downloading a staff file
    ['GET', '/employees/{userId:\d+}/files/{fileId:\d+}/download', [
        'auth' => 'AuthMiddleware',
        // No specific permission here, as the controller method handles ownership/admin checks
        'handler' => ['EmployeeController', 'downloadStaffFile']
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
        'handler' => ['PayrollController', 'downloadPayslipForAdmin']
    ]],


    // Statutory Reports Routes
    ['GET', '/reports', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'payroll:view_all'],
        'handler' => ['StatutoryReportController', 'index']
    ]],
    ['GET', '/reports/paye/pdf/{periodId:\d+}', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'payroll:view_all'],
        'handler' => ['StatutoryReportController', 'generatePayeReportPdf']
    ]],
    ['GET', '/reports/paye/excel/{periodId:\d+}', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'payroll:view_all'],
        'handler' => ['StatutoryReportController', 'generatePayeReportExcel']
    ]],
    ['GET', '/reports/paye/csv/{periodId:\d+}', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'payroll:view_all'],
        'handler' => ['StatutoryReportController', 'generatePayeReportCsv']
    ]],
    ['GET', '/reports/ssnit/pdf/{periodId:\d+}', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'payroll:view_all'],
        'handler' => ['StatutoryReportController', 'generateSsnitReportPdf']
    ]],
    ['GET', '/reports/ssnit/excel/{periodId:\d+}', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'payroll:view_all'],
        'handler' => ['StatutoryReportController', 'generateSsnitReportExcel']
    ]],
    ['GET', '/reports/ssnit/csv/{periodId:\d+}', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'payroll:view_all'],
        'handler' => ['StatutoryReportController', 'generateSsnitReportCsv']
    ]],
    // New Bank Advice Routes
    ['GET', '/reports/bank-advice/pdf/{periodId:\d+}', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'payroll:view_all'],
        'handler' => ['StatutoryReportController', 'generateBankAdvicePdf']
    ]],
    ['GET', '/reports/bank-advice/excel/{periodId:\d+}', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'payroll:view_all'],
        'handler' => ['StatutoryReportController', 'generateBankAdviceExcel']
    ]],
    ['GET', '/reports/bank-advice/csv/{periodId:\d+}', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'payroll:view_all'],
        'handler' => ['StatutoryReportController', 'generateBankAdviceCsv']
    ]],
    // New route for downloading all payslips as a ZIP
    ['GET', '/reports/payslips/zip/{periodId:\d+}', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'payroll:view_all'],
        'handler' => ['StatutoryReportController', 'downloadAllPayslipsAsZip']
    ]],

    // =========================================================
    // Leave Management Routes (Protected)
    // =========================================================
    ['GET', '/leave', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'leave:approve'], // Main dashboard for approvers
        'handler' => ['LeaveController', 'index']
    ]],
    ['GET', '/leave/pending', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'leave:approve'],
        'handler' => ['LeaveController', 'pending']
    ]],
    ['GET', '/leave/approved', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'leave:approve'],
        'handler' => ['LeaveController', 'approved']
    ]],
    ['GET', '/leave/on-leave', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'leave:approve'],
        'handler' => ['LeaveController', 'onLeave']
    ]],
    ['GET', '/leave/returning', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'leave:approve'],
        'handler' => ['LeaveController', 'returning']
    ]],

    ['GET', '/leave/types', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'leave:manage_types'],
        'handler' => ['LeaveController', 'manageTypes']
    ]],
    ['POST', '/leave/types/create', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'leave:manage_types'],
        'handler' => ['LeaveController', 'createType']
    ]],
    ['POST', '/leave/types/update/{id:\d+}', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'leave:manage_types'],
        'handler' => ['LeaveController', 'updateType']
    ]],
    ['POST', '/leave/types/delete/{id:\d+}', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'leave:manage_types'],
        'handler' => ['LeaveController', 'deleteType']
    ]],

        ['POST', '/leave/approve/{id:\d+}', [
            'auth' => 'AuthMiddleware',
            'permission' => ['PermissionMiddleware', 'leave:approve'],
            'handler' => ['LeaveController', 'approveLeave']
        ]],
            ['POST', '/leave/reject/{id:\d+}', [
                'auth' => 'AuthMiddleware',
                'permission' => ['PermissionMiddleware', 'leave:approve'],
                'handler' => ['LeaveController', 'rejectLeave']
            ]],
        
            // API route to get details for a single leave application
                ['GET', '/api/leave/application/{id:\d+}', [
                    'auth' => 'AuthMiddleware',
                    'permission' => ['PermissionMiddleware', 'leave:approve'],
                    'handler' => ['LeaveController', 'getLeaveApplicationDetails']
                ]],
            
                // API route to mark a leave application as returned
                ['POST', '/api/leave/returned/{id:\d+}', [
                    'auth' => 'AuthMiddleware',
                    'permission' => ['PermissionMiddleware', 'leave:approve'], // Approvers can mark as returned
                    'handler' => ['LeaveController', 'markAsReturned']
                ]],        // NOTE: The API Route for Cascading Dropdown is REMOVED as it's now handled by client-side JS.
     
        // API Route for fetching positions by department
        ['GET', '/api/positions', [
            'auth' => 'AuthMiddleware',
            'handler' => ['EmployeeController', 'getPositionsByDepartment']
        ]],
     
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
    
        // Process the removal of the logo file
        ['POST', '/company-profile/remove-logo', [
            'auth' => 'AuthMiddleware',
            'permission' => ['PermissionMiddleware', 'tenant:manage_settings'],
            'handler' => ['CompanyProfileController', 'removeLogo']
        ]],
    
        // =========================================================
        // Tenant Activity Log
        // =========================================================
        ['GET', '/activity-log', [
            'auth' => 'AuthMiddleware',
            'permission' => ['PermissionMiddleware', 'tenant:view_audit_logs'],
            'handler' => ['ActivityLogController', 'index']
        ]],
        ['GET', '/activity-log/csv', [
            'auth' => 'AuthMiddleware',
            'permission' => ['PermissionMiddleware', 'tenant:view_audit_logs'],
            'handler' => ['ActivityLogController', 'exportCsv']
        ]],
        ['GET', '/activity-log/pdf', [
            'auth' => 'AuthMiddleware',
            'permission' => ['PermissionMiddleware', 'tenant:view_audit_logs'],
            'handler' => ['ActivityLogController', 'exportPdf']
        ]],
    
        // =========================================================
        // Employee Self-Service Portal Routes (Protected)
        // =========================================================
        ['GET', '/my-account', [
            'auth' => 'AuthMiddleware',
            'permission' => ['PermissionMiddleware', 'self:view_profile'],
            'handler' => ['EmployeeController', 'myAccountIndex']
        ]],
        ['POST', '/my-account/leave/apply', [
            'auth' => 'AuthMiddleware',
            'permission' => ['PermissionMiddleware', 'self:apply_leave'],
            'handler' => ['EmployeeController', 'applyForLeave']
        ]],
        ['POST', '/my-account/create-employee-profile', [
            'auth' => 'AuthMiddleware',
            'permission' => ['PermissionMiddleware', 'self:view_profile'], // User must be able to view their profile to create it
            'handler' => ['EmployeeController', 'storeMyEmployeeProfile']
        ]],
        ['GET', '/my-account/change-password', [
            'auth' => 'AuthMiddleware',
            'permission' => ['PermissionMiddleware', 'self:update_profile'],
            'handler' => ['UserController', 'changePassword']
        ]],
        ['POST', '/my-account/change-password', [
            'auth' => 'AuthMiddleware',
            'permission' => ['PermissionMiddleware', 'self:update_profile'],
            'handler' => ['UserController', 'changePassword']
        ]],
    
        // Route for securely downloading an employee's payslip
        ['GET', '/my-account/payslips/{payslipId:\d+}/download', [
            'auth' => 'AuthMiddleware',
            'permission' => ['PermissionMiddleware', 'self:view_payslip'],
            'handler' => ['PayrollController', 'downloadMyPayslip']
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

    ['GET', '/departments/{id:\d+}/dashboard', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'config:manage_departments'],
        'handler' => ['DepartmentController', 'dashboard']
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

    // API route to get details for a single element (for the view modal)
    ['GET', '/api/payroll-elements/{id:\d+}', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'config:manage_payroll_elements'],
        'handler' => ['AllowanceAndDeductionController', 'getElementDetails']
    ]],

    // =========================================================
    // Configuration Routes: Payroll Settings (Protected)
    // =========================================================
    ['GET', '/payroll-settings', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'config:manage_payroll_settings'],
        'handler' => ['PayrollSettingsController', 'index']
    ]],
    ['POST', '/payroll-settings', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'config:manage_payroll_settings'],
        'handler' => ['PayrollSettingsController', 'save']
    ]],

    // =========================================================
    // Tenant Subscription
    // =========================================================
    ['GET', '/subscription', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'tenant:manage_subscription'],
        'handler' => ['SubscriptionController', 'index']
    ]],
    ['GET', '/subscription/how-to-pay', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'tenant:manage_subscription'], // Same permission as viewing subscription
        'handler' => ['SubscriptionController', 'howToPay']
    ]],

    // =========================================================
    // Tenant Support Routes
    // =========================================================
    ['GET', '/support', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'tenant:send_support_message'],
        'handler' => ['SupportController', 'index']
    ]],
    ['POST', '/support', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'tenant:send_support_message'],
        'handler' => ['SupportController', 'store']
    ]],
    ['POST', '/support/{id:\d+}/respond', [
        'auth' => 'AuthMiddleware',
        'permission' => ['PermissionMiddleware', 'super_admin'], // Only Super Admin can respond
        'handler' => ['SupportController', 'respond']
    ]],
];