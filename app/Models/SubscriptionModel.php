<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Auth;

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
     * @return bool True on successful insert.
     */
    public function recordInitialSubscription(int $tenantId, int $planId, string $status): bool
    {
        // Calculate trial period: Start now, end 30 days from now.
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+30 days')); 
        
        // 1. Insert into 'subscriptions' (Current state)
        // Primary Key is tenant_id, so this acts as an INSERT/UPDATE for the current state.
        $sql = "INSERT INTO subscriptions 
                (tenant_id, current_plan_id, status, start_date, end_date, next_billing_date) 
                VALUES (:tenant_id, :current_plan_id, :status, :start_date, :end_date, :end_date)"; // Next billing is end of trial
        
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            ':tenant_id' => $tenantId,
            ':current_plan_id' => $planId,
            ':status' => $status,
            ':start_date' => $startDate,
            ':end_date' => $endDate,
        ]);

        if ($success) {
            // 2. Log the event in 'subscription_history' (Audit trail)
            $this->logHistory(
                $tenantId, 
                $planId, 
                'New', 
                "Initial 30-day Trial activation. Expires {$endDate}.", 
                null, 
                $startDate, 
                $endDate
            );
        }

        return $success;
    }
    
    /**
     * Logs a history event for the subscription.
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
    }
}