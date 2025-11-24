<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Services;

use Jeffrey\Sikapay\Core\Database;
use Jeffrey\Sikapay\Core\Log; // Import Log
use Jeffrey\Sikapay\Core\Auth; // For logging context
use Jeffrey\Sikapay\Models\UserModel; // NEW
use \Throwable; // Catch all runtime exceptions

class SubscriptionService
{
    private \PDO $db;

    public function __construct()
    {
        try {
            // The original check handles the initial critical failure, we just ensure it's logged.
            $this->db = Database::getInstance();
            if ($this->db === null) {
                throw new \RuntimeException("Database connection failed for SubscriptionService.");
            }
        } catch (Throwable $e) {
            // CRITICAL: Database connection failed.
            Log::critical("SubscriptionService failed to connect to database.", [
                'error' => $e->getMessage(),
                'file' => $e->getFile()
            ]);
            // Re-throw the exception for system shutdown.
            throw $e;
        }
    }

// ----------------------------------------------------------------------
// A. BOOLEAN FEATURE GATING (e.g., has access to 'audit_logs')
// ----------------------------------------------------------------------

    /**
     * Checks if a tenant has an active plan that grants access to a specific feature.
     * @param int $tenantId The ID of the tenant.
     * @param string $featureKey The unique key_name of the feature (e.g., 'audit_logs').
     * @return bool True if the tenant has access, false otherwise (safe default).
     */
    public function hasFeature(int $tenantId, string $featureKey): bool
    {
        $sql = "SELECT 
                    pf.value 
                FROM subscriptions s
                JOIN plan_features pf ON s.current_plan_id = pf.plan_id
                JOIN features f ON pf.feature_id = f.id
                WHERE 
                    s.tenant_id = :tenant_id AND 
                    s.status IN ('active', 'trial', 'past_due') AND 
                    f.key_name = :feature_key AND
                    pf.value = 'true'
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':tenant_id' => $tenantId, ':feature_key' => $featureKey]);
            
            // If a row is found where the value is 'true', the tenant has the feature.
            return (bool)$stmt->fetchColumn(); 

        } catch (Throwable $e) {
            // Log failure in critical feature gating logic
            Log::error("Subscription feature check FAILED for Tenant {$tenantId} (Feature: {$featureKey}).", [
                'error' => $e->getMessage(),
                'acting_user_id' => Auth::userId()
            ]);
            // FAIL SAFE: Deny access on error to prevent unauthorized use of paid features.
            return false;
        }
    }

// ----------------------------------------------------------------------
// B. QUANTITATIVE LIMIT CHECKING (e.g., 'employee_limit')
// ----------------------------------------------------------------------

    /**
     * Retrieves the quantitative limit imposed by the tenant's current plan for a specific feature.
     * @param int $tenantId The ID of the tenant.
     * @param string $featureKey The unique key_name of the limit feature (e.g., 'employee_limit').
     * @return int The limit value, or 0 on critical error (safe default).
     */
    public function getFeatureLimit(int $tenantId, string $featureKey): int
    {
        $sql = "SELECT 
                    CAST(pf.value AS UNSIGNED) 
                FROM subscriptions s
                JOIN plan_features pf ON s.current_plan_id = pf.plan_id
                JOIN features f ON pf.feature_id = f.id
                WHERE 
                    s.tenant_id = :tenant_id AND 
                    s.status IN ('active', 'trial', 'past_due') AND 
                    f.key_name = :feature_key
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':tenant_id' => $tenantId, ':feature_key' => $featureKey]);
            
            $limit = $stmt->fetchColumn();

            // Return 0 if no limit is found or if the feature key is missing.
            return $limit !== false ? (int)$limit : 0; 

        } catch (Throwable $e) {
            // Log failure in critical limit checking logic
            Log::error("Subscription limit check FAILED for Tenant {$tenantId} (Limit: {$featureKey}).", [
                'error' => $e->getMessage(),
                'acting_user_id' => Auth::userId()
            ]);
            // FAIL SAFE: Return 0 on error. If the system fails to determine the limit, 
            // the default should be the most restrictive to prevent abuse.
            return 0; 
        }
    }


    /**
     * Retrieves the current active plan name for the tenant.
     */
    public function getCurrentPlanName(int $tenantId): string
    {
        $sql = "
            SELECT p.name 
            FROM subscriptions s
            JOIN plans p ON s.current_plan_id = p.id
            WHERE s.tenant_id = :tenant_id AND s.status IN ('active', 'trial', 'past_due')
        ";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':tenant_id' => $tenantId]);
            
            // Return 'N/A' if the plan name is not found (e.g., tenant not active)
            return $stmt->fetchColumn() ?: 'N/A'; 

        } catch (Throwable $e) {
            // Log failure in simple plan retrieval
            Log::error("Failed to retrieve current plan name for Tenant {$tenantId}.", [
                'error' => $e->getMessage(),
                'acting_user_id' => Auth::userId()
            ]);
            // FAIL SAFE: Return a safe, non-crashing default.
            return 'Subscription Error';
        }
    }

    /**
     * Checks if a new user with a specific role can be added without exceeding the plan's limit.
     * @param int $tenantId The ID of the tenant.
     * @param string $roleName The name of the role (e.g., 'hr_manager', 'accountant').
     * @return bool True if a user can be added, false otherwise.
     */
    public function canAddRoleUser(int $tenantId, string $roleName): bool
    {
        $roleFeatureMap = [
            'hr_manager' => 'hr_manager_seats',
            'accountant' => 'accountant_seats',
            'tenant_admin' => 'tenant_admin_seats',
            'auditor' => 'auditor_seats',
            'employee' => 'employee_limit', // Although this is already checked in EmployeeController, useful here for completeness
        ];

        // If the role is not mapped, it implies no specific limit, so allow by default.
        if (!isset($roleFeatureMap[$roleName])) {
            return true;
        }

        $featureKey = $roleFeatureMap[$roleName];
        $limit = $this->getFeatureLimit($tenantId, $featureKey);
        
        // Lazy load UserModel to count current users in this role
        $userModel = new \Jeffrey\Sikapay\Models\UserModel();
        $currentCount = $userModel->countUsersByRole($tenantId, $roleName);

        // For seat limits, a limit of 0 means 0 seats allowed.
        // The check 'currentCount < limit' will correctly handle this.
        return $currentCount < $limit;
    }

    /**
     * Checks if a tenant's subscription is in a state that allows core actions.
     * @param int $tenantId The ID of the tenant.
     * @return bool True if the subscription is 'active' or 'trial'.
     */
    public function isActionable(int $tenantId): bool
    {
        $sql = "SELECT status FROM subscriptions WHERE tenant_id = :tenant_id";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':tenant_id' => $tenantId]);
            $status = $stmt->fetchColumn();

            return in_array($status, ['active', 'trial']);
        } catch (Throwable $e) {
            Log::error("Subscription actionability check FAILED for Tenant {$tenantId}.", [
                'error' => $e->getMessage(),
                'acting_user_id' => Auth::userId()
            ]);
            // Fail safe: Deny action if status cannot be confirmed.
            return false;
        }
    }
}