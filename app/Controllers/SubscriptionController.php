<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Core\Auth;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder;
use Jeffrey\Sikapay\Models\SubscriptionModel;
use Jeffrey\Sikapay\Services\NotificationService;
use Jeffrey\Sikapay\Config\AppConfig;
use Jeffrey\Sikapay\Models\PlanModel;

class SubscriptionController extends Controller
{
    protected SubscriptionModel $subscriptionModel;
    protected NotificationService $notificationService;
    protected PlanModel $planModel;

    public function __construct()
    {
        parent::__construct();
        $this->subscriptionModel = new SubscriptionModel();
        $this->notificationService = new NotificationService();
        $this->planModel = new PlanModel();
    }

    public function index(): void
    {
        $this->checkPermission('tenant:manage_subscription');

        try {
            $tenantId = $this->tenantId;

            $subscription = $this->subscriptionModel->getCurrentSubscription($tenantId);
            if (!$subscription) {
                // This case should ideally not happen for a logged-in tenant admin,
                // but it's good practice to handle it.
                $this->view('subscription/index', [
                    'title' => 'My Subscription',
                    'subscription' => null,
                    'plan' => null,
                    'features' => [],
                    'history' => [],
                ]);
                return;
            }

            $planId = (int)$subscription['current_plan_id'];
            $plan = $this->planModel->find($planId);
            $features = $this->planModel->getPlanFeatures($planId);
            $history = $this->subscriptionModel->getHistoryForTenant($tenantId);

            $this->view('subscription/index', [
                'title' => 'My Subscription',
                'subscription' => $subscription,
                'plan' => $plan,
                'features' => $features,
                'history' => $history,
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to load subscription page for tenant ' . $this->tenantId . ': ' . $e->getMessage());
            ErrorResponder::respond(500, 'Could not load subscription details.');
        }
    }

    public function howToPay(): void
    {
        $this->checkPermission('tenant:manage_subscription');

        $this->view('subscription/how_to_pay', [
            'title' => 'How to Pay'
        ]);
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
