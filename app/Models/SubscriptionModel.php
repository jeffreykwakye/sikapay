<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Auth; 
use Jeffrey\Sikapay\Core\Log;
use \PDOException;

class SubscriptionModel extends Model
{
    private PlanModel $planModel;
    private EmployeeModel $employeeModel;

    // The model defaults to the main subscriptions table
    public function __construct()
    {
        parent::__construct('subscriptions');
        $this->planModel = new PlanModel();
        $this->employeeModel = new EmployeeModel();
    }

    
    /**
     * Records the initial subscription state (e.g., Trial) and logs it.
     * @param int $tenantId The ID of the new tenant.
     * @param int $planId The ID of the chosen plan.
     * @param string $status The initial status (e.g., 'active').
     * @return bool True on successful insert AND history log.
     */
    public function recordInitialSubscription(int $tenantId, int $planId, string $status): bool
    {
        // Calculate trial period: Start now, end 30 days from now.
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+30 days')); 
        
        // 1. Insert into 'subscriptions' (Current state)
        $sql = "INSERT INTO subscriptions 
                (tenant_id, current_plan_id, status, start_date, end_date, next_billing_date, employee_count_at_billing) 
                VALUES (:tenant_id, :current_plan_id, :status, :start_date, :end_date, :end_date, :employee_count)";
        
        $success = false;
        try {
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                ':tenant_id' => $tenantId,
                ':current_plan_id' => $planId,
                ':status' => $status,
                ':start_date' => $startDate,
                ':end_date' => $endDate,
                ':employee_count' => 1, // Default to 1 for the new admin employee
            ]);
        } catch (PDOException $e) {
            // Log failure in current subscription state recording (FATAL to tenant setup)
            Log::critical("Subscription STATE RECORDING FAILED for new Tenant {$tenantId}. Error: " . $e->getMessage(), [
                'plan_id' => $planId,
                'status' => $status
            ]);
            // Re-throw exception: If this fails, the tenant account is unusable.
            throw $e;
        }

        // 2. Log the event in 'subscription_history' (Audit trail)
        if ($success) {
            $plan = $this->planModel->find($planId); // Fetch plan details
            if (!$plan) {
                throw new \Exception("Plan with ID {$planId} not found during initial subscription recording.");
            }
            $historyId = $this->logHistory(
                $tenantId, 
                $planId, 
                'New', 
                "Initial subscription activated for plan '{$plan['name']}'. Expires {$endDate}.", // Modified detail
                (float)$plan['price_ghs'], // Modified amount
                $startDate, 
                $endDate
            );
            // If history logging fails, it's an error but we don't return false for the main operation.
            if ($historyId === 0) {
                 Log::error("WARNING: Initial subscription history log FAILED for Tenant {$tenantId}. Audit trail compromised.");
            }
        }

        return $success;
    }
    
    
    /**
     * Logs a history event for the subscription.
     * @return int The ID of the inserted history record, or 0 on failure.
     */
    public function logHistory(
        int $tenantId, 
        int $planId, 
        string $actionType, 
        string $details, 
        ?float $amountPaid, 
        ?string $cycleStart, 
        ?string $cycleEnd
    ): int
    {
        $sql = "INSERT INTO subscription_history 
                (tenant_id, plan_id, action_type, amount_paid, billing_cycle_start, billing_cycle_end, details) 
                VALUES (:tenant_id, :plan_id, :action_type, :amount_paid, :billing_cycle_start, :billing_cycle_end, :details)";
                
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':tenant_id' => $tenantId,
                ':plan_id' => $planId,
                ':action_type' => $actionType,
                ':amount_paid' => $amountPaid,
                ':billing_cycle_start' => $cycleStart,
                ':billing_cycle_end' => $cycleEnd,
                ':details' => $details,
            ]);
            
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            // Log failure in subscription history audit trail
            // Failure here is an audit concern, so we log heavily and re-throw.
            Log::error("Subscription HISTORY LOG FAILED for Tenant {$tenantId}. Audit trail breach.", [
                'action_type' => $actionType,
                'db_error' => $e->getMessage()
            ]);
            throw $e; // Re-throw to allow transaction rollback
        }
    }

    /**
     * Retrieves the current subscription for a tenant, including the plan name.
     *
     * @param int $tenantId The ID of the tenant.
     * @return array|null The subscription details, or null if not found.
     */
    public function getCurrentSubscription(int $tenantId): ?array
    {
        $sql = "SELECT 
                    s.*, 
                    p.name AS plan_name
                FROM subscriptions s
                JOIN plans p ON s.current_plan_id = p.id
                WHERE s.tenant_id = :tenant_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':tenant_id' => $tenantId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            Log::error("Failed to retrieve current subscription for tenant {$tenantId}. Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Counts the total number of active subscriptions in the system.
     * @return int
     */
    public function countActiveSubscriptions(): int
    {
        $sql = "SELECT COUNT(tenant_id) FROM {$this->table} WHERE status = 'active'";
        try {
            $stmt = $this->db->query($sql);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            Log::error("Failed to count active subscriptions. Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Retrieves all subscriptions with tenant and plan names.
     * @return array
     */
    public function getAllSubscriptionsWithTenantAndPlan(): array
    {
        $sql = "SELECT 
                    s.status, s.start_date, s.end_date,
                    t.id as tenant_id, t.name as tenant_name,
                    p.name as plan_name
                FROM {$this->table} s
                JOIN tenants t ON s.tenant_id = t.id
                JOIN plans p ON s.current_plan_id = p.id
                ORDER BY t.name ASC";
        
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve all subscriptions with tenant and plan data. Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves all active subscriptions that have passed their end_date.
     * Includes tenant and plan names for notification purposes.
     * @return array
     */
    public function getExpiredActiveSubscriptions(): array
    {
        $sql = "SELECT 
                    s.tenant_id, s.current_plan_id, s.status, s.start_date, s.end_date,
                    t.name as tenant_name,
                    p.name as plan_name
                FROM {$this->table} s
                JOIN tenants t ON s.tenant_id = t.id
                JOIN plans p ON s.current_plan_id = p.id
                WHERE s.status = 'active' AND s.end_date < CURDATE()";
        
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve expired active subscriptions. Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves all active subscriptions that are expiring within a given number of days.
     * @param int $days The number of days to look ahead for expiring subscriptions.
     * @return array
     */
    public function getExpiringSoonSubscriptions(int $days): array
    {
        $sql = "SELECT 
                    s.tenant_id, s.current_plan_id, s.end_date,
                    t.name as tenant_name,
                    p.name as plan_name,
                    DATEDIFF(s.end_date, CURDATE()) as days_left
                FROM {$this->table} s
                JOIN tenants t ON s.tenant_id = t.id
                JOIN plans p ON s.current_plan_id = p.id
                WHERE s.status = 'active' 
                AND s.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':days' => $days]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve expiring soon subscriptions. Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves all subscriptions that are 'past_due' or expiring within X days.
     * @param int $days The number of days to look ahead for expiring subscriptions.
     * @return array
     */
    public function getAtRiskSubscriptions(int $days = 7): array
    {
        $sql = "SELECT 
                    s.tenant_id, s.status, s.end_date,
                    t.name as tenant_name,
                    p.name as plan_name,
                    DATEDIFF(s.end_date, CURDATE()) as days_left
                FROM {$this->table} s
                JOIN tenants t ON s.tenant_id = t.id
                JOIN plans p ON s.current_plan_id = p.id
                WHERE s.status = 'past_due' 
                OR (s.status = 'active' AND s.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY))
                ORDER BY s.end_date ASC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':days' => $days]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve at-risk subscriptions. Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Updates the status of a subscription for a given tenant.
     * @param int $tenantId
     * @param string $status
     * @return bool
     */
    public function updateSubscriptionStatus(int $tenantId, string $status): bool
    {
        $sql = "UPDATE {$this->table} SET status = :status WHERE tenant_id = :tenant_id";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':status' => $status,
                ':tenant_id' => $tenantId,
            ]);
        } catch (PDOException $e) {
            Log::error("Failed to update subscription status for tenant ID {$tenantId} to {$status}. Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculates the Monthly Recurring Revenue (MRR) from all active subscriptions.
     * @return float
     */
    public function calculateMRR(): float
    {
        $sql = "SELECT SUM(p.price_ghs) 
                FROM {$this->table} s
                JOIN plans p ON s.current_plan_id = p.id
                WHERE s.status = 'active'";
        try {
            $stmt = $this->db->query($sql);
            return (float)$stmt->fetchColumn();
        } catch (PDOException $e) {
            Log::error("Failed to calculate MRR. Error: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Retrieves the monthly revenue trend for the last X months.
     * For simplicity, this sums the price_ghs of subscriptions that were active
     * at any point during the month, or started in that month.
     * A more robust solution would track actual payments.
     * @param int $months The number of months to retrieve data for.
     * @return array
     */
    public function getRevenueTrend(int $months = 12): array
    {
        $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $monthStart = date('Y-m-01', strtotime("-{$i} months"));
            $monthEnd = date('Y-m-t', strtotime("-{$i} months"));

            $sql = "SELECT SUM(p.price_ghs) 
                    FROM {$this->table} s
                    JOIN plans p ON s.current_plan_id = p.id
                    WHERE s.status = 'active' 
                    AND s.start_date <= :month_end 
                    AND (s.end_date IS NULL OR s.end_date >= :month_start)";
            
            try {
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':month_start' => $monthStart,
                    ':month_end' => $monthEnd
                ]);
                $revenue = (float)$stmt->fetchColumn();
                $data[] = [
                    'month' => date('M Y', strtotime($monthStart)),
                    'revenue' => $revenue
                ];
            } catch (PDOException $e) {
                Log::error("Failed to get revenue trend for month {$month}. Error: " . $e->getMessage());
                $data[] = ['month' => date('M Y', strtotime($monthStart)), 'revenue' => 0.0];
            }
        }
        return $data;
    }

    /**
     * Retrieves all tenants subscribed to a specific plan.
     * @param int $planId The ID of the plan.
     * @return array
     */
    public function getTenantsByPlan(int $planId): array
    {
        $sql = "SELECT t.id, t.name, t.subdomain, s.status, s.start_date, s.end_date
                FROM {$this->table} s
                JOIN tenants t ON s.tenant_id = t.id
                WHERE s.current_plan_id = :plan_id
                ORDER BY t.name ASC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':plan_id' => $planId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve tenants for plan ID {$planId}. Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calculates the total revenue accrued from a specific plan.
     * @param int $planId The ID of the plan.
     * @return float
     */
    public function getRevenueByPlan(int $planId): float
    {
        $sql = "SELECT SUM(p.price_ghs) 
                FROM subscription_history sh
                JOIN plans p ON sh.plan_id = p.id
                WHERE sh.plan_id = :plan_id AND sh.action_type = 'Renewal'";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':plan_id' => $planId]);
            return (float)$stmt->fetchColumn();
        } catch (PDOException $e) {
            Log::error("Failed to calculate revenue for plan ID {$planId}. Error: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Retrieves the subscription history for a given tenant.
     *
     * @param int $tenantId The ID of the tenant.
     * @return array An array of subscription history records.
     */
    public function getHistoryForTenant(int $tenantId): array
    {
        $sql = "SELECT 
                    sh.*, 
                    p.name AS plan_name
                FROM subscription_history sh
                JOIN plans p ON sh.plan_id = p.id
                WHERE sh.tenant_id = :tenant_id
                ORDER BY sh.created_at DESC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':tenant_id' => $tenantId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve subscription history for tenant {$tenantId}. Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cancels a tenant's subscription.
     *
     * @param int $tenantId The ID of the tenant.
     * @param string $reason The reason for cancellation.
     * @param string|null $cancellationDate The date of cancellation (defaults to today).
     * @return bool True on success, false otherwise.
     */
    public function cancelSubscription(int $tenantId, string $reason, ?string $cancellationDate = null): bool
    {
        $cancellationDate = $cancellationDate ?? date('Y-m-d');
        $this->db->beginTransaction();
        try {
            // Update the main subscriptions table
            $sql = "UPDATE {$this->table} SET status = 'cancelled', end_date = :cancellation_date WHERE tenant_id = :tenant_id";
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                ':cancellation_date' => $cancellationDate,
                ':tenant_id' => $tenantId,
            ]);

            if ($success) {
                // Log to history
                $currentSubscription = $this->getCurrentSubscription($tenantId);
                $this->logHistory(
                    $tenantId,
                    (int)$currentSubscription['current_plan_id'],
                    'Cancellation',
                    "Subscription cancelled. Reason: {$reason}.",
                    null,
                    $currentSubscription['start_date'],
                    $cancellationDate
                );
            }
            $this->db->commit();
            return $success;
        } catch (PDOException $e) {
            $this->db->rollBack();
            Log::error("Failed to cancel subscription for tenant {$tenantId}. Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Renews a tenant's subscription.
     *
     * @param int $tenantId The ID of the tenant.
     * @param int $planId The ID of the plan being renewed (should be current).
     * @param string $newEndDate The new end date for the subscription.
     * @param float $amountPaid The amount paid for renewal.
     * @return bool True on success, false otherwise.
     */
    public function renewSubscription(int $tenantId, int $planId, string $newEndDate, float $amountPaid): bool
    {
        $this->db->beginTransaction();
        try {
            $employeeCount = $this->employeeModel->getEmployeeCount($tenantId);

            // Update the main subscriptions table with explicit placeholders
            $sql = "UPDATE {$this->table} SET 
                        current_plan_id = :plan_id,
                        status = 'active', 
                        end_date = :end_date, 
                        next_billing_date = :next_billing_date, 
                        last_payment_date = CURDATE(),
                        employee_count_at_billing = :employee_count
                    WHERE tenant_id = :tenant_id";
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                ':plan_id' => $planId,
                ':end_date' => $newEndDate,
                ':next_billing_date' => $newEndDate,
                ':tenant_id' => $tenantId,
                ':employee_count' => $employeeCount,
            ]);

            if ($success) {
                // Log to history
                $currentSubscription = $this->getCurrentSubscription($tenantId);
                $this->logHistory(
                    $tenantId,
                    $planId,
                    'Renewal',
                    "Subscription renewed for plan '{$currentSubscription['plan_name']}'. New end date: {$newEndDate}.",
                    $amountPaid,
                    date('Y-m-d'), 
                    $newEndDate
                );
            }
            $this->db->commit();
            return $success;
        } catch (PDOException $e) {
            $this->db->rollBack();
            // Add more context to the log
            Log::error("Failed to renew subscription for tenant {$tenantId}. Error: " . $e->getMessage(), [
                'tenant_id' => $tenantId,
                'plan_id' => $planId,
                'new_end_date' => $newEndDate
            ]);
            return false;
        }
    }

    /**
     * Upgrades a tenant's subscription to a new plan.
     *
     * @param int $tenantId The ID of the tenant.
     * @param int $newPlanId The ID of the new plan.
     * @return bool True on success, false otherwise.
     */
    public function upgradeSubscription(int $tenantId, int $newPlanId, float $amountPaid): bool
    {
        $this->db->beginTransaction();
        try {
            $currentSubscription = $this->getCurrentSubscription($tenantId);
            if (!$currentSubscription) {
                throw new \Exception("No active subscription found for tenant {$tenantId}.");
            }
            $oldPlanId = (int)$currentSubscription['current_plan_id'];
            $employeeCount = $this->employeeModel->getEmployeeCount($tenantId);

            // Update the main subscriptions table
            $sql = "UPDATE {$this->table} SET 
                        current_plan_id = :new_plan_id, 
                        status = 'active', 
                        updated_at = NOW(),
                        employee_count_at_billing = :employee_count
                    WHERE tenant_id = :tenant_id";
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                ':new_plan_id' => $newPlanId,
                ':tenant_id' => $tenantId,
                ':employee_count' => $employeeCount,
            ]);

            if ($success) {
                // Log to history
                $this->logHistory(
                    $tenantId,
                    $newPlanId,
                    'Upgrade',
                    "Subscription upgraded from plan ID {$oldPlanId} to plan ID {$newPlanId}.",
                    $amountPaid,
                    date('Y-m-d'),
                    $currentSubscription['end_date']
                );
            }
            $this->db->commit();
            return $success;
        } catch (PDOException $e) {
            $this->db->rollBack();
            Log::error("Failed to upgrade subscription for tenant {$tenantId} to plan {$newPlanId}. Error: " . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            $this->db->rollBack();
            Log::error("Upgrade subscription failed for tenant {$tenantId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Downgrades a tenant's subscription to a new plan.
     *
     * @param int $tenantId The ID of the tenant.
     * @param int $newPlanId The ID of the new plan.
     * @return bool True on success, false otherwise.
     */
    public function downgradeSubscription(int $tenantId, int $newPlanId, float $amountPaid): bool
    {
        $this->db->beginTransaction();
        try {
            $currentSubscription = $this->getCurrentSubscription($tenantId);
            if (!$currentSubscription) {
                throw new \Exception("No active subscription found for tenant {$tenantId}.");
            }
            $oldPlanId = (int)$currentSubscription['current_plan_id'];
            $employeeCount = $this->employeeModel->getEmployeeCount($tenantId);

            // Update the main subscriptions table
            $sql = "UPDATE {$this->table} SET 
                        current_plan_id = :new_plan_id, 
                        status = 'active', 
                        updated_at = NOW(),
                        employee_count_at_billing = :employee_count
                    WHERE tenant_id = :tenant_id";
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                ':new_plan_id' => $newPlanId,
                ':tenant_id' => $tenantId,
                ':employee_count' => $employeeCount,
            ]);

            if ($success) {
                // Log to history
                $this->logHistory(
                    $tenantId,
                    $newPlanId,
                    'Downgrade',
                    "Subscription downgraded from plan ID {$oldPlanId} to plan ID {$newPlanId}.",
                    $amountPaid,
                    date('Y-m-d'),
                    $currentSubscription['end_date']
                );
            }
            $this->db->commit();
            return $success;
        } catch (PDOException $e) {
            $this->db->rollBack();
            Log::error("Failed to downgrade subscription for tenant {$tenantId} to plan {$newPlanId}. Error: " . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            $this->db->rollBack();
            Log::error("Downgrade subscription failed for tenant {$tenantId}: " . $e->getMessage());
            return false;
        }
    }


}