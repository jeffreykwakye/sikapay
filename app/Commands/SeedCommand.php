<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Commands;

use Jeffrey\Sikapay\Core\Database;

class SeedCommand
{
    private \PDO $db;
    private const SUPER_ADMIN_ROLE_NAME = 'super_admin';

    public function __construct()
    {
        $this->db = Database::getInstance() ?? throw new \Exception("Database connection is required for seeding commands.");
    }

    public function execute(string $command, array $args): void
    {
        if ($command === 'db:seed') {
            $this->runSeeder();
        } else {
            echo "Unknown seeding command.\n";
        }
    }

    private function runSeeder(): void
    {
        echo "Starting database seeding...\n";
        
        // Configuration Seeding (Idempotent - Uses UPSERT/DELETE on Junction Tables)
        $this->seedRoles(); 
        $this->seedFeatures();
        $this->seedPermissions(); 
        $this->seedPlans(); // Depends on Features
        $this->seedRolePermissions(); // Depends on Roles and Permissions
        $this->seedWithholdingTaxRates(); // NEW: Seed initial withholding tax rates
        
        // System Data Seeding (Idempotent - Uses INSERT IF NOT EXISTS)
        $this->seedInitialAdmin(); 
        
        echo "Seeding finished.\n";
    }
    
    // --- seedRoles(), seedFeatures(), and seedPermissions() methods are unchanged and omitted for brevity ---
    
    private function seedWithholdingTaxRates(): void
    {
        echo " - Seeding/Updating Withholding Tax Rates...\n";
        $rates = [
            ['effective_date' => '2025-01-01', 'rate' => 0.0750, 'employment_type' => 'Contract', 'description' => 'Standard Withholding Tax Rate for Services (Resident)'],
            ['effective_date' => '2025-01-01', 'rate' => 0.0500, 'employment_type' => 'Casual-Worker', 'description' => 'Withholding Tax Rate for Casual Workers'],
        ];

        $stmt = $this->db->prepare("
            INSERT INTO withholding_tax_rates (effective_date, rate, employment_type, description) VALUES (:effective_date, :rate, :employment_type, :description)
            ON DUPLICATE KEY UPDATE rate = VALUES(rate), description = VALUES(description)
        ");
        
        foreach ($rates as $rate) {
            $stmt->execute($rate);
        }
        echo " - Withholding Tax Rates ensured successfully.\n";
    }
    
    private function seedRoles(): void
    {
        echo " - Seeding/Updating Roles...\n";
        $roles = [
            ['name' => self::SUPER_ADMIN_ROLE_NAME, 'description' => 'Super Administrator (Full System Access)'], 
            ['name' => 'tenant_admin', 'description' => 'Primary Administrator for a Client/Tenant. Manages billing/settings.'],
            ['name' => 'hr_manager', 'description' => 'Manages employee records, leave, and payroll preparation.'],
            ['name' => 'accountant', 'description' => 'Audits payroll, processes payments, and generates statutory reports.'],
            ['name' => 'employee', 'description' => 'Self-service access only (payslips, leave requests).'],
            ['name' => 'auditor', 'description' => 'Executive or Auditor role with view-only access to most data.'], 
        ];

        $stmt = $this->db->prepare("INSERT IGNORE INTO roles (name, description) VALUES (:name, :description)");
        
        foreach ($roles as $role) {
            $stmt->execute($role);
        }
        echo " - Roles ensured successfully. Total: " . count($roles) . " roles.\n";
    }

    private function seedFeatures(): void
    {
        echo " - Seeding/Updating Features...\n";
        $features = [
            ['key_name' => 'payroll_basic', 'description' => 'Core monthly payroll calculation and payslip generation.'],
            ['key_name' => 'audit_logs', 'description' => 'Access to the security audit trail.'],
            ['key_name' => 'statutory_reports', 'description' => 'Generation of formal SSNIT/PAYE reports.'],
            ['key_name' => 'report_export_download', 'description' => 'Allows downloading and exporting of all payroll and statutory reports.'],
            ['key_name' => 'employee_limit', 'description' => 'Maximum number of active Employee accounts.'],
            ['key_name' => 'hr_manager_seats', 'description' => 'Maximum number of active HR Manager accounts.'],
            ['key_name' => 'accountant_seats', 'description' => 'Maximum number of active Accountant accounts.'],
            ['key_name' => 'tenant_admin_seats', 'description' => 'Maximum number of active Tenant Admin accounts.'],
            ['key_name' => 'auditor_seats', 'description' => 'Maximum number of active Auditor accounts.'], 
        ];

        $stmt = $this->db->prepare("INSERT IGNORE INTO features (key_name, description) VALUES (:key_name, :description)");
        
        foreach ($features as $feature) {
            $stmt->execute($feature);
        }
        echo " - Features ensured successfully.\n";
    }

    private function seedPermissions(): void
    {
        echo " - Seeding/Updating Permissions...\n";
        $permissions = [
            // --- SELF-SERVICE (8 Permissions) --- // Note: Count increased by 1
            ['key_name' => 'self:view_dashboard', 'description' => 'Can access the main user dashboard.'],
            ['key_name' => 'self:view_profile', 'description' => 'Can view own personal and employment profile data.'], // ADDED
            ['key_name' => 'self:view_payslip', 'description' => 'Can view own payslips.'],
            ['key_name' => 'self:manage_leave', 'description' => 'Can request and track own leave.'],
            ['key_name' => 'self:update_profile', 'description' => 'Can update own password only (no personal data modification).'], 
            ['key_name' => 'self:view_docs', 'description' => 'Can view personal documents (e.g., contract, payslips).'],
            ['key_name' => 'self:manage_loan', 'description' => 'Can submit and track own loan applications.'], 
            ['key_name' => 'self:view_notifications', 'description' => 'Can view and dismiss system notifications.'], 

            // --- EMPLOYEE MANAGEMENT (6 Permissions) ---
            ['key_name' => 'employee:create', 'description' => 'Can add new employee records.'],
            ['key_name' => 'employee:update', 'description' => 'Can modify ALL employee profile data, including resetting password, salary, position, and bank.'], 
            ['key_name' => 'employee:read_all', 'description' => 'Can view all employee profiles.'],
            ['key_name' => 'employee:delete', 'description' => 'Can delete or deactivate employee records.'],
            ['key_name' => 'employee:manage_docs', 'description' => 'Can upload, view, and manage employee documents.'],
            ['key_name' => 'employee:manage_contracts', 'description' => 'Can create/edit employment contract details.'],
            ['key_name' => 'employee:assign_payroll_elements', 'description' => 'Can assign custom payroll allowances and deductions to employees.'],

            // --- PAYROLL MANAGEMENT (6 Permissions) ---
            ['key_name' => 'payroll:manage_rules', 'description' => 'Can modify tenant-specific payroll items (bonuses, allowances, deductions, Tier 3 rates).'],
            ['key_name' => 'payroll:prepare', 'description' => 'Can input data and calculate the monthly payroll draft.'],
            ['key_name' => 'payroll:audit', 'description' => 'Can review calculated payroll and approve the audit step.'],
            ['key_name' => 'payroll:approve', 'description' => 'Can set payroll status to final Approved for payment.'],
            ['key_name' => 'payroll:view_all', 'description' => 'Can view all historic and current payrolls.'],
            ['key_name' => 'payroll:run_reports', 'description' => 'Can generate statutory reports (e.g., SSNIT/PAYE).'],
            
            // --- LOAN MANAGEMENT (2 Permissions) ---
            ['key_name' => 'loan:manage_applications', 'description' => 'Can create, review, and process employee loan requests.'],
            ['key_name' => 'loan:approve', 'description' => 'Can grant final financial approval for employee loans.'],

            // --- TENANT ADMINISTRATION (5 Permissions) ---
            ['key_name' => 'tenant:manage_users', 'description' => 'Can create, edit, and deactivate tenant users (HR/Acc/Emp).'],
            ['key_name' => 'tenant:manage_settings', 'description' => 'Can edit tenant branding and financial settings.'],
            ['key_name' => 'tenant:manage_subscription', 'description' => 'Can view plan, manage payment details, and change billing plans.'],
            ['key_name' => 'tenant:view_audit_logs', 'description' => 'Can access the tenant-specific security audit trail.'],
            ['key_name' => 'tenant:configure_roles', 'description' => 'Can assign or modify role permissions.'],
            ['key_name' => 'tenant:send_support_message', 'description' => 'Can send support messages to the super admin.'],

            // --- CONFIGURATION MANAGEMENT (3 Permissions) ---
            ['key_name' => 'config:manage_departments', 'description' => 'Can create, edit, and delete company departments.'],
            ['key_name' => 'config:manage_positions', 'description' => 'Can create, edit, and delete company job titles/positions.'],
            ['key_name' => 'config:manage_payroll_elements', 'description' => 'Can create, edit, and delete custom payroll allowances and deductions.'],
            ['key_name' => 'config:manage_payroll_settings', 'description' => 'Can manage tenant-wide payroll settings like withholding tax rate.'],

            // --- LEAVE MANAGEMENT (1 Permission) ---
            ['key_name' => 'leave:approve', 'description' => 'Can approve or reject employee leave/time-off requests.'],

            // --- SUPER ADMINISTRATION (4 Permissions) ---
            ['key_name' => 'super:manage_statutory_rates', 'description' => 'Can globally configure SSNIT, Income Tax, and other mandatory rates.'],
            ['key_name' => 'super:view_tenants', 'description' => 'Can view a list of all client tenants.'],
            ['key_name' => 'super:create_tenant', 'description' => 'Can provision new client tenants.'],
            ['key_name' => 'super:impersonate', 'description' => 'Can temporarily log in as any tenant user.'],
            ['key_name' => 'super:manage_plans', 'description' => 'Can create, edit, and delete subscription plans.'],
            ['key_name' => 'super:view_reports', 'description' => 'Can view system-wide reports.'],
            ['key_name' => 'super:manage_users', 'description' => 'Can manage all users across all tenants.'],
            ['key_name' => 'super:view_audit_logs', 'description' => 'Can view system-wide audit logs.'],
            ['key_name' => 'super:manage_settings', 'description' => 'Can manage system-wide settings.'],
        ];

        $stmt = $this->db->prepare("INSERT IGNORE INTO permissions (key_name, description) VALUES (:key_name, :description)");
        foreach ($permissions as $permission) {
            $stmt->execute($permission);
        }
        echo " - Permissions ensured successfully. Total: " . count($permissions) . " permissions.\n"; 
    }

    private function seedPlans(): void
    {
        echo " - Seeding/Updating Subscription Plans...\n";
        
        $plansConfig = [
            'Standard' => [
                'price' => 500.00, 
                'employee_limit' => 25, 
                'features' => ['payroll_basic'], // REMOVED 'auditor_seats'
                'hr_manager_seats' => 0, 
                'accountant_seats' => 0, 
                'tenant_admin_seats' => 1,
                'auditor_seats' => 1 
            ],
            'Professional' => [
                'price' => 1200.00, 
                'employee_limit' => 100, 
                'features' => ['payroll_basic', 'audit_logs', 'statutory_reports', 'report_export_download'], // REMOVED 'auditor_seats'
                'hr_manager_seats' => 3, 
                'accountant_seats' => 2, 
                'tenant_admin_seats' => 2,
                'auditor_seats' => 2 
            ],
            'Enterprise' => [
                'price' => 0.00, 
                'employee_limit' => 99999, 
                'features' => ['payroll_basic', 'audit_logs', 'statutory_reports', 'report_export_download'], // REMOVED 'auditor_seats'
                'hr_manager_seats' => 99, 
                'accountant_seats' => 99, 
                'tenant_admin_seats' => 99,
                'auditor_seats' => 99
            ],
        ];

        // 1. Get Feature Map
        $result = $this->db->query("SELECT id, key_name FROM features")->fetchAll(\PDO::FETCH_ASSOC);
        $featureMap = [];
        foreach ($result as $row) {
            $featureMap[$row['key_name']] = (int)$row['id'];
        }

        // 2. Prepare UPSERT statements for plans
        $planStmt = $this->db->prepare("
            INSERT INTO plans (name, price_ghs) VALUES (:name, :price)
            ON DUPLICATE KEY UPDATE price_ghs = :price
        ");
        
        $deleteFeatureStmt = $this->db->prepare("DELETE FROM plan_features WHERE plan_id = :plan_id");
        $insertFeatureStmt = $this->db->prepare("INSERT INTO plan_features (plan_id, feature_id, value) VALUES (:plan_id, :feature_id, :value)");

        // 3. Process Plans and Features
        foreach ($plansConfig as $name => $data) {
            // UPSERT the plan itself (by unique name)
            $planStmt->execute([':name' => $name, ':price' => $data['price']]);

            // Get the plan ID (whether inserted or existing)
            $planId = $this->db->query("SELECT id FROM plans WHERE name = '{$name}'")->fetchColumn();
            
            // Delete ALL existing features for this plan to ensure a clean update (Idempotency)
            $deleteFeatureStmt->execute([':plan_id' => $planId]);

            // Define all feature limits (Quantitative limits)
            $limits = [
                'employee_limit' => (string)$data['employee_limit'],
                'hr_manager_seats' => (string)$data['hr_manager_seats'],
                'accountant_seats' => (string)$data['accountant_seats'],
                'tenant_admin_seats' => (string)$data['tenant_admin_seats'],
                'auditor_seats' => (string)$data['auditor_seats'], // INSERTED AS A LIMIT VALUE HERE
            ];

            // Insert Limits
            foreach ($limits as $key => $value) {
                $featureId = $featureMap[$key]; 
                $insertFeatureStmt->execute([':plan_id' => $planId, ':feature_id' => $featureId, ':value' => $value]);
            }

            // Insert Boolean Features (auditor_seats is NO LONGER here, preventing duplication)
            foreach ($data['features'] as $featureName) {
                $featureId = $featureMap[$featureName];
                $insertFeatureStmt->execute([':plan_id' => $planId, ':feature_id' => $featureId, ':value' => 'true']);
            }
            echo " - Ensured Plan: {$name}\n";
        }
    }
    
    private function seedRolePermissions(): void
    {
        echo " - Linking/Updating Role Permissions...\n";

        // 1. Get Maps
        $roleResult = $this->db->query("SELECT id, name FROM roles")->fetchAll(\PDO::FETCH_ASSOC);
        $roleMap = array_column($roleResult, 'id', 'name');
        
        $permissionResult = $this->db->query("SELECT id, key_name FROM permissions")->fetchAll(\PDO::FETCH_ASSOC);
        $permissionMap = array_column($permissionResult, 'id', 'key_name');

        if (!isset($roleMap[self::SUPER_ADMIN_ROLE_NAME]) || count($permissionMap) < 33) {
            throw new \Exception("CRITICAL RBAC ERROR: Failed to map roles or permissions. Check table status.");
        }
        
        // --- IDEMPOTENT OVERWRITE ---
        $this->db->exec('SET FOREIGN_KEY_CHECKS = 0');
        // Use DELETE FROM to reliably clear all existing mappings
        $this->db->exec("DELETE FROM role_permissions"); 
        $this->db->exec('SET FOREIGN_KEY_CHECKS = 1');
        // --- END IDEMPOTENT OVERWRITE ---
        
        $stmt = $this->db->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)");

        $rolePermissions = [
            // Super Admin: FULL ACCESS (All 33 permissions)
            $roleMap[self::SUPER_ADMIN_ROLE_NAME] => array_values($permissionMap), 

            // Tenant Admin: Full operational control
            $roleMap['tenant_admin'] => [
                // SELF (7)
                $permissionMap['self:view_dashboard'], $permissionMap['self:update_profile'], 
                $permissionMap['self:view_payslip'], $permissionMap['self:manage_leave'],
                $permissionMap['self:view_docs'], $permissionMap['self:manage_loan'],
                $permissionMap['self:view_notifications'],
                $permissionMap['self:view_profile'], // ADDED
                // EMPLOYEE (7)
                $permissionMap['employee:create'], $permissionMap['employee:read_all'], 
                $permissionMap['employee:update'], $permissionMap['employee:delete'],
                $permissionMap['employee:manage_docs'], $permissionMap['employee:manage_contracts'],
                $permissionMap['employee:assign_payroll_elements'],
                // PAYROLL (6)
                $permissionMap['payroll:manage_rules'], $permissionMap['payroll:prepare'],
                $permissionMap['payroll:audit'], $permissionMap['payroll:approve'], 
                $permissionMap['payroll:view_all'], $permissionMap['payroll:run_reports'],
                // LOAN MANAGEMENT (2)
                $permissionMap['loan:manage_applications'], $permissionMap['loan:approve'], 
                // TENANT (5)
                $permissionMap['tenant:manage_users'], $permissionMap['tenant:manage_settings'], 
                $permissionMap['tenant:manage_subscription'], $permissionMap['tenant:view_audit_logs'],
                $permissionMap['tenant:configure_roles'],
                $permissionMap['tenant:send_support_message'],
                // CONFIG & LEAVE (4)
                $permissionMap['config:manage_departments'],
                $permissionMap['config:manage_positions'],
                $permissionMap['config:manage_payroll_elements'],
                $permissionMap['config:manage_payroll_settings'],
                $permissionMap['leave:approve'],
            ],

            // HR Manager:
            $roleMap['hr_manager'] => [
                // SELF (7)
                $permissionMap['self:view_dashboard'], $permissionMap['self:update_profile'], 
                $permissionMap['self:view_payslip'], $permissionMap['self:manage_leave'],
                $permissionMap['self:view_docs'], $permissionMap['self:manage_loan'],
                $permissionMap['self:view_notifications'],
                $permissionMap['self:view_profile'], // ADDED
                // EMPLOYEE (6)
                $permissionMap['employee:create'], $permissionMap['employee:read_all'], 
                $permissionMap['employee:update'], $permissionMap['employee:manage_docs'],
                $permissionMap['employee:manage_contracts'],
                $permissionMap['employee:assign_payroll_elements'],
                // PAYROLL (3)
                $permissionMap['payroll:prepare'],
                $permissionMap['payroll:view_all'],
                $permissionMap['payroll:run_reports'],
                // LOAN MANAGEMENT (1)
                $permissionMap['loan:manage_applications'],
                // CONFIG & LEAVE (3)
                $permissionMap['config:manage_departments'],
                $permissionMap['config:manage_positions'],
                $permissionMap['leave:approve'],
            ],

            // Accountant:
            $roleMap['accountant'] => [
                // SELF (7)
                $permissionMap['self:view_dashboard'], $permissionMap['self:update_profile'], 
                $permissionMap['self:view_payslip'], $permissionMap['self:manage_leave'],
                $permissionMap['self:view_docs'], $permissionMap['self:manage_loan'],
                $permissionMap['self:view_notifications'],
                $permissionMap['self:view_profile'], // ADDED
                // EMPLOYEE (1)
                $permissionMap['employee:read_all'], 
                // PAYROLL (5)
                $permissionMap['payroll:manage_rules'], 
                $permissionMap['payroll:audit'], 
                $permissionMap['payroll:approve'], 
                $permissionMap['payroll:view_all'],
                $permissionMap['payroll:run_reports'],
                // LOAN MANAGEMENT (1)
                $permissionMap['loan:approve'],
                // CONFIG (2)
                $permissionMap['config:manage_payroll_elements'],
                $permissionMap['config:manage_payroll_settings'],
            ],

            // Employee:
            $roleMap['employee'] => [
                $permissionMap['self:view_dashboard'], 
                $permissionMap['self:view_profile'], // ADDED
                $permissionMap['self:update_profile'], 
                $permissionMap['self:view_payslip'], 
                $permissionMap['self:manage_leave'],
                $permissionMap['self:view_docs'], 
                $permissionMap['self:manage_loan'],
                $permissionMap['self:view_notifications'],
            ],
            
            // Auditor:
            $roleMap['auditor'] => [ 
                $permissionMap['self:view_dashboard'], $permissionMap['self:update_profile'], 
                $permissionMap['self:view_payslip'], $permissionMap['self:view_docs'],
                $permissionMap['self:view_notifications'],
                $permissionMap['self:view_profile'], // ADDED
                $permissionMap['employee:read_all'], 
                $permissionMap['payroll:view_all'],
                $permissionMap['payroll:run_reports'],
                $permissionMap['tenant:view_audit_logs'],
            ],
        ];

        foreach ($rolePermissions as $roleId => $permIds) {
            if ($roleId === null) { continue; }
            foreach ($permIds as $permId) {
                if ($permId === null) {
                    throw new \Exception("CRITICAL RBAC ERROR: Attempting to insert a NULL permission ID for Role ID: {$roleId}. Check permissions config.");
                }
                $stmt->execute([':role_id' => $roleId, ':permission_id' => $permId]);
            }
        }
        echo " - Role Permissions ensured successfully.\n";
    }

    private function seedInitialAdmin(): void
    {
        echo " - Seeding Initial Tenant and Super Admin User...\n";
        
        // --- 1. Create System Tenant (ID 1) ---
        $tenantName = "SikaPay Internal System Tenant"; 
        $stmt = $this->db->prepare("
            INSERT INTO tenants (id, name, subscription_status) VALUES (1, :name, 'active') 
            ON DUPLICATE KEY UPDATE name=name
        ");
        $stmt->execute([':name' => $tenantName]);
        $tenantId = 1; 
        
        // --- 2. Create Super Admin User ---
        $email = 'admin@sikapay.local';
        $password = password_hash('password', PASSWORD_DEFAULT); 
        
        $roleId = $this->db->query("SELECT id FROM roles WHERE name='" . self::SUPER_ADMIN_ROLE_NAME . "'")->fetchColumn();
        
        // Check if user exists before attempting to insert
        $userExists = $this->db->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $userExists->execute([':email' => $email]);

        if ((int)$userExists->fetchColumn() === 0) {
             $stmt = $this->db->prepare("INSERT INTO users 
                 (tenant_id, role_id, email, password, first_name, last_name, is_active) 
                 VALUES (:tenant_id, :role_id, :email, :password, :first_name, :last_name, 1)");
                 
             $stmt->execute([
                ':tenant_id' => $tenantId,
                ':role_id' => $roleId,
                ':email' => $email,
                ':password' => $password,
                ':first_name' => 'System',
                ':last_name' => 'Admin',
             ]);

             echo " - Super Admin User '{$email}' created (Password: 'password').\n";
        } else {
             echo " - Super Admin User '{$email}' already exists.\n";
        }
        echo " - System Tenant (ID: {$tenantId}) ensured.\n";
    }
}