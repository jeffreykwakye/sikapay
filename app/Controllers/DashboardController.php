<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Controllers\Controller;
use Jeffrey\Sikapay\Core\Auth; // Kept for the static check
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder; 
use Jeffrey\Sikapay\Models\PayrollPeriodModel;
use Jeffrey\Sikapay\Models\PayslipModel;
use Jeffrey\Sikapay\Models\EmployeeModel;
use Jeffrey\Sikapay\Models\DepartmentModel;
use Jeffrey\Sikapay\Models\PositionModel;
use Jeffrey\Sikapay\Models\AuditModel;
use \Throwable;

class DashboardController extends Controller
{
    private PayrollPeriodModel $payrollPeriodModel;
    private PayslipModel $payslipModel;
    private EmployeeModel $employeeModel;
    private DepartmentModel $departmentModel;
    private PositionModel $positionModel;
    private AuditModel $auditModel;

    public function __construct()
    {
        parent::__construct();
        $this->payrollPeriodModel = new PayrollPeriodModel();
        $this->payslipModel = new PayslipModel();
        $this->employeeModel = new EmployeeModel();
        $this->departmentModel = new DepartmentModel();
        $this->positionModel = new PositionModel();
        $this->auditModel = new AuditModel();
    }

    /**
     * Displays the primary application dashboard.
     */
    public function index(): void
    {
        try {
            $isSuperAdmin = $this->auth->isSuperAdmin();
            $tenantId = $this->tenantId;

            // Base data structure
            $data = [
                'isSuperAdmin' => $isSuperAdmin,
                'userRole' => $this->auth->getRoleName(),
                'title' => $isSuperAdmin ? 'Super Admin Dashboard' : $this->tenantName . ' Dashboard', 
                'welcomeMessage' => 'Welcome back, ' . ($this->userName['first_name'] ?? 'Admin ').'',
            ];

            if (!$isSuperAdmin && $tenantId) {
                // Initialize all keys to safe defaults
                $data += [
                    'activeEmployees' => 0,
                    'departmentCount' => 0,
                    'grossPayrollLastMonth' => 0.0,
                    'netPayLastMonth' => 0.0,
                    'payeLastMonth' => 0.0,
                    'ssnitCostLastMonth' => 0.0,
                    'nextPayrollDate' => 'Not Set',
                    'subscriptionEndDate' => 'N/A', // Initialize
                    'payrollSummary' => [],
                    'employeeCountByDepartment' => [],
                    'upcomingAnniversaries' => [],
                    'newHires' => [],
                    'recentActivities' => [],
                ];

                // 1. Fetch KPI data
                $data['activeEmployees'] = $this->employeeModel->getEmployeeCount($tenantId);
                $data['departmentCount'] = $this->departmentModel->countAllByTenant();
                
                $latestClosedPeriod = $this->payrollPeriodModel->getLatestClosedPeriod($tenantId);
                if ($latestClosedPeriod) {
                    $kpiData = $this->payslipModel->getAggregatedPayslipData((int)$latestClosedPeriod['id'], $tenantId);
                    $data['grossPayrollLastMonth'] = $kpiData['total_gross_pay'] ?? 0.0;
                    $data['netPayLastMonth'] = $kpiData['total_net_pay'] ?? 0.0;
                    $data['payeLastMonth'] = $kpiData['total_paye'] ?? 0.0;
                    $data['ssnitCostLastMonth'] = $kpiData['total_employer_ssnit'] ?? 0.0;
                }

                $currentPeriod = $this->payrollPeriodModel->getCurrentPeriod($tenantId);
                if ($currentPeriod && !empty($currentPeriod['payment_date'])) {
                    $data['nextPayrollDate'] = date('F j, Y', strtotime($currentPeriod['payment_date']));
                }

                // Fetch Subscription End Date
                $currentSubscription = $this->subscriptionModel->getCurrentSubscription($tenantId);
                if ($currentSubscription && !empty($currentSubscription['end_date'])) {
                    $data['subscriptionEndDate'] = date('M d, Y', strtotime($currentSubscription['end_date']));
                }

                // 2. Fetch Chart Data
                $data['payrollSummary'] = $this->payslipModel->getPayrollHistory($tenantId, 6); // Last 6 months
                $data['employeeCountByDepartment'] = $this->departmentModel->getEmployeeCountPerDepartment($tenantId);

                // 3. Fetch List Data
                $data['upcomingAnniversaries'] = $this->employeeModel->getUpcomingAnniversaries($tenantId, 30); // Next 30 days
                $data['newHires'] = $this->employeeModel->getRecentEmployees($tenantId, 5); // Last 5 new hires
                $data['recentActivities'] = $this->auditModel->getRecentActivity($tenantId, 10); // Last 10 activities
            }

            $this->view('dashboard/index', $data);

        } catch (Throwable $e) {
            $userId = $this->userId > 0 ? (string)$this->userId : 'N/A';
            
            Log::critical("Dashboard Load Failed for User {$userId}.", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'N/A'
            ]);

            ErrorResponder::respond(500, "We could not load your dashboard due to a system error.");
        }
    }
}