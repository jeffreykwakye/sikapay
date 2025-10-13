<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Controllers\Controller;
use Jeffrey\Sikapay\Models\DepartmentModel;

class TestController extends Controller
{
    public function index(): void
    {
        if (!$this->auth->check()) {
            $this->redirect('/login');
        }

        $departmentModel = new DepartmentModel();
        
        // This call to all() will automatically be scoped by the Model.php logic!
        $departments = $departmentModel->all(); 
        
        $data = [
            'title' => 'Tenancy Scoping Test Page',
            'is_admin' => $this->auth->isSuperAdmin(),
            'tenant_id' => $this->auth->tenantId(),
            'departments' => $departments
        ];

        $this->view('test/scope', $data);
    }
}