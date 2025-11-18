<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Controllers\Controller;
use Jeffrey\Sikapay\Models\TenantModel;
use Jeffrey\Sikapay\Models\PlanModel;
use Jeffrey\Sikapay\Models\SubscriptionModel;
use Jeffrey\Sikapay\Models\UserModel;
use Jeffrey\Sikapay\Models\RoleModel;
use Jeffrey\Sikapay\Models\AuditModel;
use Jeffrey\Sikapay\Core\Auth;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder; 
use Jeffrey\Sikapay\Models\EmployeeModel;
//  NEW: Import Validator
use Jeffrey\Sikapay\Core\Validator; 
use \Throwable;

class TenantController extends Controller
{
    protected TenantModel $tenantModel;
    protected PlanModel $planModel;
    protected SubscriptionModel $subscriptionModel;
    protected UserModel $userModel;
    protected RoleModel $roleModel;
    protected AuditModel $auditModel;
    protected EmployeeModel $employeeModel;
    
    public function __construct()
    {
        parent::__construct();
        
        try {
            // Model/Service Instantiation Check
            $this->tenantModel = new TenantModel();
            $this->planModel = new PlanModel();
            $this->userModel = new UserModel();
            $this->roleModel = new RoleModel();
            $this->auditModel = new AuditModel();
            $this->subscriptionModel = new SubscriptionModel();
            $this->employeeModel = new EmployeeModel();
        } catch (Throwable $e) {
            // If any model/service fails to initialize (e.g., DB connection issue)
            Log::critical("TenantController failed to initialize models: " . $e->getMessage());
            ErrorResponder::respond(500, "A critical system error occurred during tenant management initialization.");
        }
    }
}