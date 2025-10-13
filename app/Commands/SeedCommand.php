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
        
        $this->truncateTables(); // NEW STEP: Wipe all data safely
        
        // Data seeding in correct dependency order
        $this->seedRoles(); 
        $this->seedFeatures();
        $this->seedPlans();
        $this->seedPermissions(); 
        $this->seedRolePermissions(); 
        $this->seedInitialAdmin(); // MUST be last since it inserts users/tenants
        
        echo "Seeding finished.\n";
    }

    /**
     * Wipes all seedable tables in the correct reverse dependency order.
     * This is required to bypass Foreign Key constraints.
     */
    private function truncateTables(): void
    {
        echo " - Truncating existing seed data...\n";
        
        // CRITICAL: Temporarily disable foreign key checks
        $this->db->exec('SET FOREIGN_KEY_CHECKS = 0');
        
        // 1. Wipe Users and Tenants (Highest Dependency)
        $this->db->exec("TRUNCATE TABLE users");
        $this->db->exec("TRUNCATE TABLE user_profiles");
        $this->db->exec("TRUNCATE TABLE user_permissions");
        $this->db->exec("TRUNCATE TABLE tenants");
        $this->db->exec("TRUNCATE TABLE tenant_profiles");

        // 2. Wipe Roles/Permissions/Employment (Mid-level Dependency)
        $this->db->exec("TRUNCATE TABLE roles");
        $this->db->exec("TRUNCATE TABLE role_permissions");
        $this->db->exec("TRUNCATE TABLE permissions");
        $this->db->exec("TRUNCATE TABLE departments");
        $this->db->exec("TRUNCATE TABLE positions");
        $this->db->exec("TRUNCATE TABLE employees");
        $this->db->exec("TRUNCATE TABLE employment_history");

        // 3. Wipe Plans/Features (Lowest Dependency)
        $this->db->exec("TRUNCATE TABLE plan_features");
        $this->db->exec("TRUNCATE TABLE plans");
        $this->db->exec("TRUNCATE TABLE features");
        $this->db->exec("TRUNCATE TABLE subscriptions");
        $this->db->exec("TRUNCATE TABLE subscription_history");
        
        // Re-enable foreign key checks
        $this->db->exec('SET FOREIGN_KEY_CHECKS = 1');
        
        echo " - Data wiped successfully.\n";
    }

    // --- Seeding Methods (Roles, Features, Plans, Permissions, etc.) ---
    
    // NOTE: Remove all TRUNCATE TABLE calls from the individual seed methods 
    // (seedRoles, seedFeatures, seedPlans, seedPermissions) as they are now handled above.
    // Ensure all seed methods still run the ALTER TABLE ... AUTO_INCREMENT = 1 command 
    // if you want the IDs to restart at 1 every time.

    private function seedRoles(): void
    {
        echo " - Seeding Roles...\n";
        $roles = [
            // ID 1: Super Admin 
            ['name' => self::SUPER_ADMIN_ROLE_NAME, 'description' => 'Super Administrator (Full System Access)'], 
            
            // Tenant-specific roles 
            ['name' => 'tenant_admin', 'description' => 'Primary Administrator for a Client/Tenant. Manages billing/settings.'],
            ['name' => 'hr_manager', 'description' => 'Manages employee records, leave, and payroll preparation.'],
            ['name' => 'accountant', 'description' => 'Audits payroll, processes payments, and generates statutory reports.'],
            ['name' => 'employee', 'description' => 'Self-service access only (payslips, leave requests).'],
        ];

        // $this->db->exec("TRUNCATE TABLE roles"); // REMOVED
        $this->db->exec("ALTER TABLE roles AUTO_INCREMENT = 1");
        
        $stmt = $this->db->prepare("INSERT INTO roles (name, description) VALUES (:name, :description)");
        
        foreach ($roles as $role) {
            $stmt->execute($role);
        }
        echo " - Roles seeded successfully. Total: " . count($roles) . " roles.\n";
    }

    private function seedFeatures(): void
    {
        echo " - Seeding Features...\n";
        $features = [
            // ... (rest of features array remains the same) ...
            ['key_name' => 'payroll_basic', 'description' => 'Core monthly payroll calculation and payslip generation.'],
            ['key_name' => 'audit_logs', 'description' => 'Access to the security audit trail.'],
            ['key_name' => 'statutory_reports', 'description' => 'Generation of formal SSNIT/PAYE reports.'],
            ['key_name' => 'employee_limit', 'description' => 'Maximum number of active Employee accounts.'],
            ['key_name' => 'hr_manager_seats', 'description' => 'Maximum number of active HR Manager accounts.'],
            ['key_name' => 'accountant_seats', 'description' => 'Maximum number of active Accountant accounts.'],
            ['key_name' => 'tenant_admin_seats', 'description' => 'Maximum number of active Tenant Admin accounts.'],
        ];

        // $this->db->exec("TRUNCATE TABLE features"); // REMOVED
        $this->db->exec("ALTER TABLE features AUTO_INCREMENT = 1");
        $stmt = $this->db->prepare("INSERT INTO features (key_name, description) VALUES (:key_name, :description)");
        
        foreach ($features as $feature) {
            $stmt->execute($feature);
        }
        echo " - Features seeded successfully.\n";
    }
    
    // ... (Continue to REMOVE TRUNCATE calls from seedPlans and seedPermissions) ...
    
    private function seedPlans(): void
    {
        echo " - Seeding Subscription Plans...\n";
        
        // Plans Data (structure is confirmed correct)
        $plans = [
            'Standard' => [
                'price' => 500.00, 'employee_limit' => 25, 'features' => ['payroll_basic'], 
                'hr_manager_seats' => 1, 'accountant_seats' => 1, 'tenant_admin_seats' => 1
            ],
            'Pro'      => [
                'price' => 1200.00, 'employee_limit' => 100, 'features' => ['payroll_basic', 'audit_logs', 'statutory_reports'], 
                'hr_manager_seats' => 3, 'accountant_seats' => 2, 'tenant_admin_seats' => 2
            ],
            'Enterprise' => [
                'price' => 0.00, 'employee_limit' => 99999, 'features' => ['payroll_basic', 'audit_logs', 'statutory_reports'], 
                'hr_manager_seats' => 99, 'accountant_seats' => 99, 'tenant_admin_seats' => 99
            ],
        ];

        $this->db->exec("ALTER TABLE plans AUTO_INCREMENT = 1");
        
        $planStmt = $this->db->prepare("INSERT INTO plans (name, price_ghs) VALUES (:name, :price)");
        $featureStmt = $this->db->prepare("INSERT INTO plan_features (plan_id, feature_id, value) VALUES (:plan_id, :feature_id, :value)");

        // REVISED MAPPING LOGIC
        $result = $this->db->query("SELECT id, key_name FROM features")->fetchAll(\PDO::FETCH_ASSOC);
        
        // SANITY CHECK: The features table should have 7 rows inserted by seedFeatures()
        if (count($result) < 7) {
             throw new \Exception("CRITICAL SEEDING ERROR: Features table contains only " . count($result) . " rows. Expected 7. Check seedFeatures() or truncation.");
        }
        
        $featureMap = [];
        foreach ($result as $row) {
            $featureMap[$row['key_name']] = (int)$row['id'];
        }
        
        // Second Sanity Check: Ensure the target key exists in the map
        if (!isset($featureMap['employee_limit'])) {
             // This indicates the key_name wasn't in the result set, likely due to a case issue or data corruption.
             throw new \Exception("CRITICAL SEEDING ERROR: 'employee_limit' key not found in feature map.");
        }
        // END REVISED MAPPING LOGIC

        foreach ($plans as $name => $data) {
            $planStmt->execute([':name' => $name, ':price' => $data['price']]);
            $planId = $this->db->lastInsertId();

            $limits = [
                'employee_limit' => (string)$data['employee_limit'],
                'hr_manager_seats' => (string)$data['hr_manager_seats'],
                'accountant_seats' => (string)$data['accountant_seats'],
                'tenant_admin_seats' => (string)$data['tenant_admin_seats'],
            ];

            foreach ($limits as $key => $value) {
                // The error originates here if $featureMap[$key] is null
                $featureId = $featureMap[$key]; 
                
                $featureStmt->execute([':plan_id' => $planId, ':feature_id' => $featureId, ':value' => $value]);
            }

            // Link other named features
            foreach ($data['features'] as $featureName) {
                $featureId = $featureMap[$featureName];
                $featureStmt->execute([':plan_id' => $planId, ':feature_id' => $featureId, ':value' => 'true']);
            }
            echo " - Seeded Plan: {$name}\n";
        }
    }
    
    private function seedPermissions(): void
    {
        echo " - Seeding Permissions...\n";
        $permissions = [
            // ... (rest of permissions array remains the same) ...
            ['key_name' => 'employee:create', 'description' => 'Can add new employee records.'],
            ['key_name' => 'employee:read_all', 'description' => 'Can view all employee profiles.'],
            ['key_name' => 'employee:update', 'description' => 'Can modify employee profile data.'],
            ['key_name' => 'employee:delete', 'description' => 'Can delete employee records.'],
            ['key_name' => 'payroll:prepare', 'description' => 'Can input and calculate monthly payroll data.'],
            ['key_name' => 'payroll:audit', 'description' => 'Can audit and finalize statutory deductions.'],
            ['key_name' => 'payroll:approve', 'description' => 'Can set payroll status to final Approved for payment.'],
            ['key_name' => 'payroll:view_all', 'description' => 'Can view all historic payrolls.'],
            ['key_name' => 'tenant:manage_users', 'description' => 'Can create, edit, and deactivate tenant users (HR/Acc/Emp).'],
            ['key_name' => 'tenant:manage_settings', 'description' => 'Can edit tenant branding and financial settings.'],
            ['key_name' => 'tenant:manage_subscription', 'description' => 'Can change plan, payment details, and billing.'],
            ['key_name' => 'self:view_payslip', 'description' => 'Can view own payslips.'],
            ['key_name' => 'self:manage_leave', 'description' => 'Can request and track own leave.'],
        ];

        // $this->db->exec("TRUNCATE TABLE permissions"); // REMOVED
        $this->db->exec("ALTER TABLE permissions AUTO_INCREMENT = 1");
        
        $stmt = $this->db->prepare("INSERT INTO permissions (key_name, description) VALUES (:key_name, :description)");
        foreach ($permissions as $permission) {
            $stmt->execute($permission);
        }
        echo " - Permissions seeded successfully. Total: " . count($permissions) . " permissions.\n";
    }

    private function seedRolePermissions(): void
    {
        echo " - Linking Roles to Permissions...\n";

        // REVISED MAPPING LOGIC START
        
        // 1. Create Role Map (Ensure correct string keys)
        $roleResult = $this->db->query("SELECT id, name FROM roles")->fetchAll(\PDO::FETCH_ASSOC);
        $roleMap = [];
        foreach ($roleResult as $row) {
            $roleMap[$row['name']] = (int)$row['id'];
        }
        
        // 2. Create Permission Map (Ensure correct string keys)
        $permissionResult = $this->db->query("SELECT id, key_name FROM permissions")->fetchAll(\PDO::FETCH_ASSOC);
        $permissionMap = [];
        foreach ($permissionResult as $row) {
            $permissionMap[$row['key_name']] = (int)$row['id'];
        }

        // 3. SANITY CHECK: Ensure we have the base role IDs and all permission IDs
        if (!isset($roleMap[self::SUPER_ADMIN_ROLE_NAME]) || !isset($permissionMap['payroll:approve'])) {
            throw new \Exception("CRITICAL RBAC ERROR: Failed to map roles or permissions. Check table status.");
        }
        
        // REVISED MAPPING LOGIC END

        $stmt = $this->db->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)");

        // We MUST use the explicit role IDs and permission IDs fetched above.
        $rolePermissions = [
            // Super Admin: FULL ACCESS 
            $roleMap[self::SUPER_ADMIN_ROLE_NAME] => array_values($permissionMap), // Use array_values to get all permission IDs

            // Tenant Admin
            $roleMap['tenant_admin'] => [
                $permissionMap['employee:read_all'], $permissionMap['payroll:view_all'],
                $permissionMap['tenant:manage_users'], $permissionMap['tenant:manage_settings'], $permissionMap['tenant:manage_subscription'],
                $permissionMap['self:view_payslip'], $permissionMap['self:manage_leave']
            ],

            // HR Manager
            $roleMap['hr_manager'] => [
                $permissionMap['employee:create'], $permissionMap['employee:read_all'], $permissionMap['employee:update'],
                $permissionMap['payroll:prepare'], $permissionMap['self:view_payslip'], $permissionMap['self:manage_leave']
            ],

            // Accountant
            $roleMap['accountant'] => [
                $permissionMap['employee:read_all'], $permissionMap['payroll:audit'], $permissionMap['payroll:approve'], 
                $permissionMap['payroll:view_all'], $permissionMap['self:view_payslip'], $permissionMap['self:manage_leave']
            ],

            // Employee: Self-service only
            $roleMap['employee'] => [
                $permissionMap['self:view_payslip'], $permissionMap['self:manage_leave']
            ]
        ];
        
        // The truncation is handled by truncateTables(), so we insert directly.

        foreach ($rolePermissions as $roleId => $permIds) {
            // Check to ensure $roleId is not null, preventing a hidden error.
            if ($roleId === null) {
                continue; 
            }
            foreach ($permIds as $permId) {
                // Check to ensure $permId is not null, which was the original error cause.
                if ($permId === null) {
                    throw new \Exception("CRITICAL RBAC ERROR: Attempting to insert a NULL permission ID for Role ID: {$roleId}.");
                }
                $stmt->execute([':role_id' => $roleId, ':permission_id' => $permId]);
            }
        }
        echo " - Role Permissions seeded successfully.\n";
    }

    
    private function seedInitialAdmin(): void
    {
        echo " - Seeding Initial Tenant and Super Admin User...\n";
        
        // ... (This method remains the same as it handles insertion, not truncation) ...
        // --- 1. Create System Tenant (ID 1) ---
        $tenantName = "SikaPay Internal System Tenant"; 
        $stmt = $this->db->prepare("INSERT INTO tenants (id, name, subscription_status) VALUES (1, :name, 'active') ON DUPLICATE KEY UPDATE name=name");
        $stmt->execute([':name' => $tenantName]);
        $tenantId = 1; 
        
        // --- 2. Create Super Admin User ---
        $email = 'admin@sikapay.local';
        $password = password_hash('password', PASSWORD_DEFAULT); 
        
        $roleId = $this->db->query("SELECT id FROM roles WHERE name='" . self::SUPER_ADMIN_ROLE_NAME . "'")->fetchColumn();
        
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