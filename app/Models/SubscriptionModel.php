<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Auth; 
use Jeffrey\Sikapay\Core\Log;
use \PDOException;

class SubscriptionModel extends Model
{
    // The model defaults to the main subscriptions table
    public function __construct()
    {
        parent::__construct('subscriptions');
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
                (tenant_id, current_plan_id, status, start_date, end_date, next_billing_date) 
                VALUES (:tenant_id, :current_plan_id, :status, :start_date, :end_date, :end_date)";
        
        $success = false;
        try {
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                ':tenant_id' => $tenantId,
                ':current_plan_id' => $planId,
                ':status' => $status,
                ':start_date' => $startDate,
                ':end_date' => $endDate,
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
            $historyId = $this->logHistory(
                $tenantId, 
                $planId, 
                'New', 
                "Initial 30-day Trial activation. Expires {$endDate}.", 
                null, 
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
            // Failure here is an audit concern, so we return 0 but log heavily.
            Log::error("Subscription HISTORY LOG FAILED for Tenant {$tenantId}. Audit trail breach.", [
                'action_type' => $actionType,
                'db_error' => $e->getMessage()
            ]);
            return 0;
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
}