<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Commands;

use Jeffrey\Sikapay\Core\Database;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Models\SubscriptionModel;
use Jeffrey\Sikapay\Services\NotificationService; // For sending notifications

class CheckSubscriptionsCommand
{
    private \PDO $db;
    private SubscriptionModel $subscriptionModel;
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->db = Database::getInstance() ?? throw new \Exception("Database connection is required for commands.");
        $this->subscriptionModel = new SubscriptionModel();
        $this->notificationService = new NotificationService();
    }

    public function execute(string $command, array $args): void
    {
        if ($command === 'app:check-subscriptions') {
            $this->checkAndExpireSubscriptions();
        } else {
            echo "Unknown command for CheckSubscriptionsCommand.\n";
        }
    }

    private function checkAndExpireSubscriptions(): void
    {
        echo "Checking for expired subscriptions...\n";
        Log::info("CLI: Running subscription check command.");

        try {
            $expiredSubscriptions = $this->subscriptionModel->getExpiredActiveSubscriptions();
            $count = 0;

            foreach ($expiredSubscriptions as $subscription) {
                $this->db->beginTransaction();
                try {
                    // Update subscription status to 'expired'
                    $this->subscriptionModel->updateSubscriptionStatus($subscription['tenant_id'], 'expired');

                    // Log history
                    $this->subscriptionModel->logHistory(
                        (int)$subscription['tenant_id'],
                        (int)$subscription['current_plan_id'],
                        'Expired',
                        "Subscription expired on {$subscription['end_date']}.",
                        null,
                        $subscription['start_date'],
                        $subscription['end_date']
                    );

                    // Notify Tenant Admin
                    $this->notificationService->notifyTenantAdmin(
                        (int)$subscription['tenant_id'],
                        'SUBSCRIPTION_EXPIRED',
                        'Your SikaPay Subscription Has Expired',
                        "Your subscription for the plan '{$subscription['plan_name']}' expired on {$subscription['end_date']}. Please renew to continue using SikaPay."
                    );

                    // Notify Super Admin
                    $this->notificationService->notifySuperAdmin(
                        'SUBSCRIPTION_EXPIRED_TENANT',
                        'Tenant Subscription Expired',
                        "The subscription for tenant '{$subscription['tenant_name']}' (Plan: {$subscription['plan_name']}) expired on {$subscription['end_date']}."
                    );

                    $this->db->commit();
                    $count++;
                    Log::info("CLI: Subscription for Tenant ID {$subscription['tenant_id']} marked as expired.");
                } catch (\Throwable $e) {
                    if ($this->db->inTransaction()) {
                        $this->db->rollBack();
                    }
                    Log::error("CLI: Failed to process expired subscription for Tenant ID {$subscription['tenant_id']}: " . $e->getMessage());
                    echo "Error processing subscription for Tenant ID {$subscription['tenant_id']}: " . $e->getMessage() . "\n";
                }
            }

            echo "Finished checking subscriptions. {$count} subscriptions expired and processed.\n";
            Log::info("CLI: Finished subscription check command. {$count} subscriptions expired.");
        } catch (\Throwable $e) {
            Log::critical("CLI: Critical error during subscription check command: " . $e->getMessage());
            echo "Critical error during subscription check: " . $e->getMessage() . "\n";
        }
    }
}
