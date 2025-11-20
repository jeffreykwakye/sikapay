<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Core\Auth;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder;
use Jeffrey\Sikapay\Models\SubscriptionModel;
use Jeffrey\Sikapay\Services\NotificationService;
use Jeffrey\Sikapay\Config\AppConfig;

class SubscriptionController extends Controller
{
    protected SubscriptionModel $subscriptionModel;
    protected NotificationService $notificationService;

    public function __construct()
    {
        parent::__construct();
        $this->subscriptionModel = new SubscriptionModel();
        $this->notificationService = new NotificationService();
    }

    /**
     * System-wide check for all tenant subscriptions.
     * This can be triggered by a login event or a future cron job.
     */
    public function checkAllSubscriptions(): void
    {
        Log::info('Starting system-wide subscription check...');
        try {
            $pastDueSubscriptions = $this->subscriptionModel->getExpiredActiveSubscriptions();
            $expiringSoonSubscriptions = $this->subscriptionModel->getExpiringSoonSubscriptions(7);

            $processedCount = 0;
            $notificationCount = 0;

            // Process past-due subscriptions
            foreach ($pastDueSubscriptions as $sub) {
                $tenantId = (int)$sub['tenant_id'];
                $updateResult = $this->subscriptionModel->updateSubscriptionStatus($tenantId, 'past_due');
                if ($updateResult) {
                    $this->notificationService->notifyTenantAdmin(
                        $tenantId,
                        'SUBSCRIPTION_PAST_DUE',
                        'Your SikaPay Subscription is Past Due',
                        "Your subscription for the plan '{$sub['plan_name']}' ended on {$sub['end_date']}. Please renew to continue using SikaPay."
                    );
                    $this->notificationService->notifySuperAdmin(
                        'TENANT_SUBSCRIPTION_PAST_DUE',
                        'Tenant Subscription Past Due',
                        "Subscription for tenant '{$sub['tenant_name']}' is now past due."
                    );
                    $processedCount++;
                }
            }

            // Process expiring-soon subscriptions
            foreach ($expiringSoonSubscriptions as $sub) {
                $tenantId = (int)$sub['tenant_id'];
                // Avoid spamming by checking if a notification was sent in the last day
                if (!$this->notificationService->hasRecentNotification($tenantId, 'SUBSCRIPTION_EXPIRING_SOON', 1)) {
                    $this->notificationService->notifyTenantAdmin(
                        $tenantId,
                        'SUBSCRIPTION_EXPIRING_SOON',
                        'Your SikaPay Subscription is Expiring Soon',
                        "Your subscription for the plan '{$sub['plan_name']}' will expire in {$sub['days_left']} days. Please renew to avoid service interruption."
                    );
                    $notificationCount++;
                }
            }

            Log::info("Finished system-wide subscription check. Processed {$processedCount} past-due subscriptions and sent {$notificationCount} expiration warnings.");

        } catch (\Throwable $e) {
            Log::critical('SUBSCRIPTION_CHECK_FAILURE: checkAllSubscriptions failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
