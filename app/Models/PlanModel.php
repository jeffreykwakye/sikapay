<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Log;
use \PDOException;

class PlanModel extends Model
{
    // Flag to bypass tenant scoping (Plans do not have a tenant_id column)
    protected bool $noTenantScope = true; 

    public function __construct()
    {
        parent::__construct('plans');
    }
    
    /**
     * Creates a new plan record.
     * @param array $data
     * @return int The ID of the newly created plan, or 0 on failure.
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO plans (name, price_ghs) VALUES (:name, :price_ghs)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':name' => $data['name'],
                ':price_ghs' => $data['price_ghs'],
            ]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            Log::error("Failed to create plan: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Updates an existing plan record.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE plans SET name = :name, price_ghs = :price_ghs WHERE id = :id";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':name' => $data['name'],
                ':price_ghs' => $data['price_ghs'],
                ':id' => $id,
            ]);
        } catch (PDOException $e) {
            Log::error("Failed to update plan ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Retrieves all features associated with a specific plan.
     * @param int $planId
     * @return array
     */
    public function getPlanFeatures(int $planId): array
    {
        $sql = "SELECT pf.feature_id as id, f.key_name, f.description, pf.value 
                FROM plan_features pf
                JOIN features f ON pf.feature_id = f.id
                WHERE pf.plan_id = :plan_id";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':plan_id' => $planId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to get features for plan ID {$planId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Adds a feature to a plan.
     * @param int $planId
     * @param int $featureId
     * @param string $value
     * @return bool
     */
    public function addFeatureToPlan(int $planId, int $featureId, string $value): bool
    {
        $sql = "INSERT INTO plan_features (plan_id, feature_id, value) VALUES (:plan_id, :feature_id, :value)";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':plan_id' => $planId,
                ':feature_id' => $featureId,
                ':value' => $value,
            ]);
        } catch (PDOException $e) {
            Log::error("Failed to add feature {$featureId} to plan {$planId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Clears all features from a plan.
     * @param int $planId
     * @return bool
     */
    public function clearPlanFeatures(int $planId): bool
    {
        $sql = "DELETE FROM plan_features WHERE plan_id = :plan_id";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':plan_id' => $planId]);
        } catch (PDOException $e) {
            Log::error("Failed to clear features for plan ID {$planId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Checks if a plan is currently in use by any active subscriptions.
     * @param int $planId
     * @return bool
     */
    public function isPlanInUse(int $planId): bool
    {
        $sql = "SELECT COUNT(tenant_id) FROM subscriptions WHERE current_plan_id = :plan_id AND status = 'active'";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':plan_id' => $planId]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            Log::error("Failed to check if plan ID {$planId} is in use: " . $e->getMessage());
            return true; // Fail safe: assume it's in use to prevent accidental deletion
        }
    }

    /**
     * Retrieves the distribution of active subscriptions across different plans.
     * @return array
     */
    public function getPlanDistribution(): array
    {
        $sql = "SELECT 
                    p.name AS plan_name, 
                    COUNT(s.tenant_id) AS subscription_count
                FROM plans p
                LEFT JOIN subscriptions s ON p.id = s.current_plan_id AND s.status = 'active'
                GROUP BY p.name
                ORDER BY subscription_count DESC";
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to get plan distribution. Error: " . $e->getMessage());
            return [];
        }
    }
}