<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Core\ErrorResponder;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\Validator;
use Jeffrey\Sikapay\Models\FeatureModel;
use Jeffrey\Sikapay\Models\PlanModel;
use Jeffrey\Sikapay\Models\SubscriptionModel;

class PlanController extends Controller
{
    protected PlanModel $planModel;
    protected FeatureModel $featureModel;
    protected SubscriptionModel $subscriptionModel;

    public function __construct()
    {
        parent::__construct();
        // Ensure only Super Admins can access this controller
        $this->checkPermission('super:manage_plans');

        $this->planModel = new PlanModel();
        $this->featureModel = new FeatureModel();
        $this->subscriptionModel = new SubscriptionModel();
    }

    /**
     * Display the specified plan.
     */
    public function show(int $id): void
    {
        try {
            $plan = $this->planModel->find($id);
            if (!$plan) {
                ErrorResponder::respond(404, 'Plan not found.');
                return;
            }

            $features = $this->planModel->getPlanFeatures($id);
            $tenants = $this->subscriptionModel->getTenantsByPlan($id);
            $revenue = $this->subscriptionModel->getRevenueByPlan($id);

            $this->view('superadmin/plans/show', [
                'title' => 'Plan Details: ' . $plan['name'],
                'plan' => $plan,
                'features' => $features,
                'tenants' => $tenants,
                'revenue' => $revenue,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to load plan details for ID ' . $id . ': ' . $e->getMessage());
            ErrorResponder::respond(500, 'Could not load plan details.');
        }
    }

    /**
     * Update the specified plan in storage.
     */
    public function update(int $id): void
    {
        $validator = new Validator($_POST);
        $validator->validate([
            'name' => 'required|min:3|max:100',
            'description' => 'optional',
            'price_ghs' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            $_SESSION['flash_error'] = 'Plan update failed: ' . implode('<br>', $validator->errors());
            $this->redirect('/super/plans/' . $id);
            return;
        }

        try {
            $planData = [
                'name' => $validator->get('name'),
                'description' => $validator->get('description'),
                'price_ghs' => $validator->get('price_ghs', 'float'),
            ];
            $this->planModel->update($id, $planData);

            $_SESSION['flash_success'] = 'Plan "' . $planData['name'] . '" updated successfully.';
            $this->redirect('/super/plans/' . $id);
        } catch (\Throwable $e) {
            Log::error('Failed to update plan ID ' . $id . ': ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error updating plan: ' . $e->getMessage();
            $this->redirect('/super/plans/' . $id);
        }
    }
}
