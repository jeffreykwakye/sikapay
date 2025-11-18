<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Core\Auth;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder;
use Jeffrey\Sikapay\Core\Validator;
use Jeffrey\Sikapay\Models\PlanModel;
use Jeffrey\Sikapay\Models\FeatureModel; // Assuming a FeatureModel exists
use Jeffrey\Sikapay\Models\SubscriptionModel; // To check plan usage before deletion

class PlanController extends Controller
{
    private PlanModel $planModel;
    private FeatureModel $featureModel;
    private SubscriptionModel $subscriptionModel;

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
     * Display a list of all subscription plans.
     * This method is already handled by SuperAdminController::plans()
     * This controller will handle the CRUD actions.
     */
    // public function index(): void
    // {
    //     // This method is intentionally left empty as SuperAdminController::plans()
    //     // already fetches and displays the list of plans.
    // }

    /**
     * Show the form for creating a new plan.
     */
    public function create(): void
    {
        try {
            $features = $this->featureModel->all(); // Get all available features
            $this->view('superadmin/plans/create', [
                'title' => 'Create New Subscription Plan',
                'features' => $features,
                'error' => $_SESSION['flash_error'] ?? null,
                'input' => $_SESSION['flash_input'] ?? [],
            ]);
            unset($_SESSION['flash_error'], $_SESSION['flash_input']);
        } catch (\Throwable $e) {
            Log::error('Failed to load create plan form: ' . $e->getMessage());
            ErrorResponder::respond(500, 'Could not load the plan creation form.');
        }
    }

    /**
     * Store a newly created plan in storage.
     */
    public function store(): void
    {
        $validator = new Validator($_POST);
        $validator->validate([
            'name' => 'required|min:3|max:100',
            'price_ghs' => 'required|numeric|min:0',
            'employee_limit' => 'required|int|min:0',
            'hr_manager_seats' => 'required|int|min:0',
            'accountant_seats' => 'required|int|min:0',
            'tenant_admin_seats' => 'required|int|min:0',
            'auditor_seats' => 'required|int|min:0',
            'features' => 'optional|array', // Array of feature IDs
        ]);

        if ($validator->fails()) {
            $_SESSION['flash_error'] = 'Plan creation failed: ' . implode('<br>', $validator->errors());
            $_SESSION['flash_input'] = $validator->all();
            $this->redirect('/super/plans/create');
            return;
        }

        $db = $this->planModel->getDB();
        try {
            $db->beginTransaction();

            $planData = [
                'name' => $validator->get('name'),
                'price_ghs' => $validator->get('price_ghs', 'float'),
            ];
            $planId = $this->planModel->create($planData);

            if (!$planId) {
                throw new \Exception('Failed to create plan record.');
            }

            // Save features and limits
            $features = $validator->get('features', 'array', []);
            $limits = [
                'employee_limit' => (string)$validator->get('employee_limit', 'int'),
                'hr_manager_seats' => (string)$validator->get('hr_manager_seats', 'int'),
                'accountant_seats' => (string)$validator->get('accountant_seats', 'int'),
                'tenant_admin_seats' => (string)$validator->get('tenant_admin_seats', 'int'),
                'auditor_seats' => (string)$validator->get('auditor_seats', 'int'),
            ];

            // Get feature map (id => key_name)
            $allFeatures = $this->featureModel->all();
            $featureMap = array_column($allFeatures, 'id', 'key_name');

            foreach ($limits as $key_name => $value) {
                if (isset($featureMap[$key_name])) {
                    $this->planModel->addFeatureToPlan($planId, $featureMap[$key_name], $value);
                }
            }

            foreach ($features as $featureId) {
                $this->planModel->addFeatureToPlan($planId, (int)$featureId, 'true');
            }

            $db->commit();
            $_SESSION['flash_success'] = 'Plan "' . $planData['name'] . '" created successfully.';
            $this->redirect('/super/plans');
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Log::error('Failed to store new plan: ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error creating plan: ' . $e->getMessage();
            $_SESSION['flash_input'] = $validator->all();
            $this->redirect('/super/plans/create');
        }
    }

    /**
     * Show the form for editing the specified plan.
     */
    public function edit(int $id): void
    {
        try {
            $plan = $this->planModel->find($id);
            if (!$plan) {
                ErrorResponder::respond(404, 'Plan not found.');
                return;
            }

            $allFeatures = $this->featureModel->all();
            $planFeatures = $this->planModel->getPlanFeatures($id);

            // Extract limits from planFeatures
            $limits = [];
            foreach ($planFeatures as $pf) {
                if (in_array($pf['key_name'], ['employee_limit', 'hr_manager_seats', 'accountant_seats', 'tenant_admin_seats', 'auditor_seats'])) {
                    $limits[$pf['key_name']] = $pf['value'];
                }
            }

            $this->view('superadmin/plans/edit', [
                'title' => 'Edit Subscription Plan: ' . $plan['name'],
                'plan' => $plan,
                'allFeatures' => $allFeatures,
                'planFeatures' => array_column($planFeatures, 'id'), // Just IDs for easy checking
                'limits' => $limits,
                'error' => $_SESSION['flash_error'] ?? null,
                'input' => $_SESSION['flash_input'] ?? [],
            ]);
            unset($_SESSION['flash_error'], $_SESSION['flash_input']);
        } catch (\Throwable $e) {
            Log::error('Failed to load edit plan form for ID ' . $id . ': ' . $e->getMessage());
            ErrorResponder::respond(500, 'Could not load the plan edit form.');
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
            'price_ghs' => 'required|numeric|min:0',
            'employee_limit' => 'required|int|min:0',
            'hr_manager_seats' => 'required|int|min:0',
            'accountant_seats' => 'required|int|min:0',
            'tenant_admin_seats' => 'required|int|min:0',
            'auditor_seats' => 'required|int|min:0',
            'features' => 'optional|array',
        ]);

        if ($validator->fails()) {
            $_SESSION['flash_error'] = 'Plan update failed: ' . implode('<br>', $validator->errors());
            $_SESSION['flash_input'] = $validator->all();
            $this->redirect('/super/plans/' . $id . '/edit');
            return;
        }

        $db = $this->planModel->getDB();
        try {
            $db->beginTransaction();

            $planData = [
                'name' => $validator->get('name'),
                'price_ghs' => $validator->get('price_ghs', 'float'),
            ];
            $this->planModel->update($id, $planData);

            // Update features and limits
            $this->planModel->clearPlanFeatures($id); // Clear existing features
            $features = $validator->get('features', 'array', []);
            $limits = [
                'employee_limit' => (string)$validator->get('employee_limit', 'int'),
                'hr_manager_seats' => (string)$validator->get('hr_manager_seats', 'int'),
                'accountant_seats' => (string)$validator->get('accountant_seats', 'int'),
                'tenant_admin_seats' => (string)$validator->get('tenant_admin_seats', 'int'),
                'auditor_seats' => (string)$validator->get('auditor_seats', 'int'),
            ];

            $allFeatures = $this->featureModel->all();
            $featureMap = array_column($allFeatures, 'id', 'key_name');

            foreach ($limits as $key_name => $value) {
                if (isset($featureMap[$key_name])) {
                    $this->planModel->addFeatureToPlan($id, $featureMap[$key_name], $value);
                }
            }

            foreach ($features as $featureId) {
                $this->planModel->addFeatureToPlan($id, (int)$featureId, 'true');
            }

            $db->commit();
            $_SESSION['flash_success'] = 'Plan "' . $planData['name'] . '" updated successfully.';
            $this->redirect('/super/plans');
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Log::error('Failed to update plan ID ' . $id . ': ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error updating plan: ' . $e->getMessage();
            $_SESSION['flash_input'] = $validator->all();
            $this->redirect('/super/plans/' . $id . '/edit');
        }
    }

    /**
     * Remove the specified plan from storage.
     */
    public function delete(int $id): void
    {
        // Check if any subscriptions are using this plan
        if ($this->subscriptionModel->isPlanInUse($id)) {
            $_SESSION['flash_error'] = 'Cannot delete plan: It is currently in use by active subscriptions.';
            $this->redirect('/super/plans');
            return;
        }

        $db = $this->planModel->getDB();
        try {
            $db->beginTransaction();
            $this->planModel->clearPlanFeatures($id); // Clear associated features first
            $this->planModel->delete($id);
            $db->commit();
            $_SESSION['flash_success'] = 'Plan deleted successfully.';
            $this->redirect('/super/plans');
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Log::error('Failed to delete plan ID ' . $id . ': ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error deleting plan: ' . $e->getMessage();
            $this->redirect('/super/plans');
        }
    }
}
