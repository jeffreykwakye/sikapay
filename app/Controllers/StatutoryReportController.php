<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Controllers\Controller;
use Jeffrey\Sikapay\Models\PayrollPeriodModel;
use Jeffrey\Sikapay\Models\PayslipModel;
use Jeffrey\Sikapay\Models\TenantProfileModel;
use Jeffrey\Sikapay\Models\PayrollSettingsModel;
use Jeffrey\Sikapay\Core\Auth;
use Jeffrey\Sikapay\Helpers\PayeReportPdfGenerator;
use Jeffrey\Sikapay\Helpers\PayeReportExcelGenerator;
use Jeffrey\Sikapay\Helpers\PayeReportCsvGenerator; // Added
use Jeffrey\Sikapay\Helpers\SsnitReportPdfGenerator;
use Jeffrey\Sikapay\Helpers\SsnitReportExcelGenerator;
use Jeffrey\Sikapay\Helpers\SsnitReportCsvGenerator; // Added
use Jeffrey\Sikapay\Helpers\BankAdvicePdfGenerator;
use Jeffrey\Sikapay\Helpers\BankAdviceExcelGenerator;
use Jeffrey\Sikapay\Helpers\BankAdviceCsvGenerator; // Added
use Jeffrey\Sikapay\Models\SsnitAdviceModel;
use Jeffrey\Sikapay\Models\GraPayeAdviceModel;
use Jeffrey\Sikapay\Models\BankAdviceModel;
use Jeffrey\Sikapay\Models\SubscriptionModel;
use Jeffrey\Sikapay\Models\DepartmentModel;
use Jeffrey\Sikapay\Helpers\Sanitizer;

class StatutoryReportController extends Controller
{
    private PayrollPeriodModel $payrollPeriodModel;
    private PayslipModel $payslipModel;
    protected TenantProfileModel $tenantProfileModel;
    private SsnitAdviceModel $ssnitAdviceModel;
    private GraPayeAdviceModel $graPayeAdviceModel;
    private BankAdviceModel $bankAdviceModel;
    private PayrollSettingsModel $payrollSettingsModel;
    protected SubscriptionModel $subscriptionModel;
    private DepartmentModel $departmentModel; // NEW PROPERTY

    public function __construct()
    {
        $this->payrollPeriodModel = new PayrollPeriodModel();
        $this->payslipModel = new PayslipModel();
        $this->tenantProfileModel = new TenantProfileModel();
        $this->ssnitAdviceModel = new SsnitAdviceModel();
        $this->graPayeAdviceModel = new GraPayeAdviceModel();
        $this->bankAdviceModel = new BankAdviceModel();
        $this->payrollSettingsModel = new PayrollSettingsModel();
        $this->subscriptionModel = new SubscriptionModel();
        $this->departmentModel = new DepartmentModel(); // NEW INSTANTIATION
        parent::__construct();
    }

    public function index(): void
    {
        $periods = $this->payrollPeriodModel->getAllPeriods(Auth::tenantId());
        $subscription = $this->subscriptionModel->getCurrentSubscription(Auth::tenantId());
        
        $this->view('reports/index', [
            'title' => 'Statutory Reports',
            'periods' => $periods,
            'planName' => $subscription['plan_name'] ?? 'Standard'
        ]);
    }

    public function generatePayeReportPdf(int $periodId): void
    {
        $tenantId = Auth::tenantId();
        $period = $this->payrollPeriodModel->getPeriodById($periodId, $tenantId);

        if (!$period || !$period['is_closed']) {
            $this->redirect('/reports');
            return;
        }

        $reportData = $this->graPayeAdviceModel->getAdviceByPeriod($periodId, $tenantId);
        $tenantData = $this->tenantProfileModel->findByTenantId($tenantId);
        $includeCoverLetter = $this->payrollSettingsModel->getSetting($tenantId, 'include_report_cover_letters', 'false') === 'true';

        if (empty($reportData) || empty($tenantData)) {
            $this->redirect('/reports'); // Redirect if no data or tenant info
            return;
        }
        $pdf = new PayeReportPdfGenerator($reportData, $tenantData, $period, $includeCoverLetter);
        $pdfContent = $pdf->generate();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="paye_report_' . $period['period_name'] . '.pdf"');
        echo $pdfContent;
    }
    
    public function generatePayeReportCsv(int $periodId): void
    {
        $tenantId = Auth::tenantId();
        $period = $this->payrollPeriodModel->getPeriodById($periodId, $tenantId);

        if (!$period || !$period['is_closed']) {
            $this->redirect('/reports');
            return;
        }

        $reportData = $this->graPayeAdviceModel->getAdviceByPeriod($periodId, $tenantId);
        $tenantData = $this->tenantProfileModel->findByTenantId($tenantId);

        if (empty($reportData) || empty($tenantData)) {
            $this->redirect('/reports'); // Redirect if no data or tenant info
            return;
        }
        $csv = new PayeReportCsvGenerator($reportData, $tenantData, $period);
        $csvContent = $csv->generate();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="paye_report_' . $period['period_name'] . '.csv"');
        echo $csvContent;
    }

    public function generatePayeReportExcel(int $periodId): void
    {
        $tenantId = Auth::tenantId();
        $period = $this->payrollPeriodModel->getPeriodById($periodId, $tenantId);

        if (!$period || !$period['is_closed']) {
            $this->redirect('/reports');
            return;
        }

        $reportData = $this->graPayeAdviceModel->getAdviceByPeriod($periodId, $tenantId);
        $tenantData = $this->tenantProfileModel->findByTenantId($tenantId);

        if (empty($reportData) || empty($tenantData)) {
            $this->redirect('/reports'); // Redirect if no data or tenant info
            return;
        }
        $excel = new PayeReportExcelGenerator($reportData, $tenantData, $period);
        $excelFile = $excel->generate();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="paye_report_' . $period['period_name'] . '.xlsx"');
        readfile($excelFile);
        unlink($excelFile);
    }

    public function generateSsnitReportPdf(int $periodId): void
    {
        $tenantId = Auth::tenantId();
        $period = $this->payrollPeriodModel->getPeriodById($periodId, $tenantId);

        if (!$period || !$period['is_closed']) {
            $this->redirect('/reports');
            return;
        }

        $reportData = $this->ssnitAdviceModel->getAdviceByPeriod($periodId, $tenantId);
        $tenantData = $this->tenantProfileModel->findByTenantId($tenantId);
        $includeCoverLetter = $this->payrollSettingsModel->getSetting($tenantId, 'include_report_cover_letters', 'false') === 'true';

        if (empty($reportData) || empty($tenantData)) {
            $this->redirect('/reports'); // Redirect if no data or tenant info
            return;
        }
        $pdf = new SsnitReportPdfGenerator($reportData, $tenantData, $period, $includeCoverLetter);
        $pdfContent = $pdf->generate();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="ssnit_report_' . $period['period_name'] . '.pdf"');
        echo $pdfContent;
    }
    
    public function generateSsnitReportCsv(int $periodId): void
    {
        $tenantId = Auth::tenantId();
        $period = $this->payrollPeriodModel->getPeriodById($periodId, $tenantId);

        if (!$period || !$period['is_closed']) {
            $this->redirect('/reports');
            return;
        }

        $reportData = $this->ssnitAdviceModel->getAdviceByPeriod($periodId, $tenantId);
        $tenantData = $this->tenantProfileModel->findByTenantId($tenantId);

        if (empty($reportData) || empty($tenantData)) {
            $this->redirect('/reports'); // Redirect if no data or tenant info
            return;
        }
        $csv = new SsnitReportCsvGenerator($reportData, $tenantData, $period);
        $csvContent = $csv->generate();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="ssnit_report_' . $period['period_name'] . '.csv"');
        echo $csvContent;
    }

    public function generateSsnitReportExcel(int $periodId): void
    {
        $tenantId = Auth::tenantId();
        $period = $this->payrollPeriodModel->getPeriodById($periodId, $tenantId);

        if (!$period || !$period['is_closed']) {
            $this->redirect('/reports');
            return;
        }

        $reportData = $this->ssnitAdviceModel->getAdviceByPeriod($periodId, $tenantId);
        $tenantData = $this->tenantProfileModel->findByTenantId($tenantId);

        if (empty($reportData) || empty($tenantData)) {
            $this->redirect('/reports'); // Redirect if no data or tenant info
            return;
        }
        $excel = new SsnitReportExcelGenerator($reportData, $tenantData, $period);
        $excelFile = $excel->generate();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="ssnit_report_' . $period['period_name'] . '.xlsx"');
        readfile($excelFile);
        unlink($excelFile);
    }

    public function generateBankAdvicePdf(int $periodId): void
    {
        $tenantId = Auth::tenantId();
        $period = $this->payrollPeriodModel->getPeriodById($periodId, $tenantId);

        if (!$period || !$period['is_closed']) {
            $this->redirect('/reports');
            return;
        }

        $reportData = $this->bankAdviceModel->getAdviceByPeriod($periodId, $tenantId);
        $tenantData = $this->tenantProfileModel->findByTenantId($tenantId);
        $includeCoverLetter = $this->payrollSettingsModel->getSetting($tenantId, 'include_report_cover_letters', 'false') === 'true';

        if (empty($reportData) || empty($tenantData)) {
            $this->redirect('/reports');
            return;
        }

        $pdf = new BankAdvicePdfGenerator($reportData, $tenantData, $period, $includeCoverLetter);
        $pdfContent = $pdf->generate();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="bank_advice_' . $period['period_name'] . '.pdf"');
        echo $pdfContent;
    }
    
    public function generateBankAdviceCsv(int $periodId): void
    {
        $tenantId = Auth::tenantId();
        $period = $this->payrollPeriodModel->getPeriodById($periodId, $tenantId);

        if (!$period || !$period['is_closed']) {
            $this->redirect('/reports');
            return;
        }

        $reportData = $this->bankAdviceModel->getAdviceByPeriod($periodId, $tenantId);
        $tenantData = $this->tenantProfileModel->findByTenantId($tenantId);

        if (empty($reportData) || empty($tenantData)) {
            $this->redirect('/reports'); // Redirect if no data or tenant info
            return;
        }
        $csv = new BankAdviceCsvGenerator($reportData, $tenantData, $period);
        $csvContent = $csv->generate();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="bank_advice_' . $period['period_name'] . '.csv"');
        echo $csvContent;
    }

    public function generateBankAdviceExcel(int $periodId): void
    {
        $tenantId = Auth::tenantId();
        $period = $this->payrollPeriodModel->getPeriodById($periodId, $tenantId);

        if (!$period || !$period['is_closed']) {
            $this->redirect('/reports');
            return;
        }

        $reportData = $this->bankAdviceModel->getAdviceByPeriod($periodId, $tenantId);
        $tenantData = $this->tenantProfileModel->findByTenantId($tenantId);

        if (empty($reportData) || empty($tenantData)) {
            $this->redirect('/reports'); // Redirect if no data or tenant info
            return;
        }
        $excel = new BankAdviceExcelGenerator($reportData, $tenantData, $period);
        $excelFile = $excel->generate();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="bank_advice_' . $period['period_name'] . '.xlsx"');
        readfile($excelFile);
        unlink($excelFile);
    }

    public function downloadAllPayslipsAsZip(int $periodId): void
    {
        $tenantId = Auth::tenantId();
        $period = $this->payrollPeriodModel->getPeriodById($periodId, $tenantId);

        if (!$period || !$period['is_closed']) {
            $this->redirect('/reports');
            return;
        }

        $payslips = $this->payslipModel->getPayslipsByPeriod($periodId, $tenantId);

        if (empty($payslips)) {
            $this->redirect('/reports');
            return;
        }

        $zip = new \ZipArchive();
        $zipFileName = tempnam(sys_get_temp_dir(), 'payslips_') . '.zip';

        if ($zip->open($zipFileName, \ZipArchive::CREATE) !== TRUE) {
            $this->redirect('/reports');
            return;
        }

        $publicPath = dirname(__DIR__, 2) . '/public';

        foreach ($payslips as $payslip) {
            if (!empty($payslip['payslip_path'])) {
                $filePath = $publicPath . '/' . $payslip['payslip_path'];
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, basename($filePath));
                }
            }
        }

        $zip->close();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="payslips_' . $period['period_name'] . '.zip"');
        header('Content-Length: ' . filesize($zipFileName));

        readfile($zipFileName);
        unlink($zipFileName);
    }
    
    // ==========================================================
    // DEPARTMENT-SPECIFIC REPORTS
    // ==========================================================

    public function downloadAllPayslipsAsZipForDepartment(string $departmentId, string $periodId): void
    {
        $tenantId = Auth::tenantId();
        $deptId = (int)\Jeffrey\Sikapay\Helpers\Sanitizer::text($departmentId);
        $pId = (int)\Jeffrey\Sikapay\Helpers\Sanitizer::text($periodId);

        // Validate Department
        $department = $this->departmentModel->find($deptId);
        if (!$department || (int)$department['tenant_id'] !== $tenantId) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId);
            return;
        }

        // Validate Period
        $period = $this->payrollPeriodModel->getPeriodById($pId, $tenantId);
        if (!$period || !$period['is_closed']) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId);
            return;
        }

        $payslips = $this->payslipModel->getPayslipsByDepartmentAndPeriod($tenantId, $deptId, $pId);

        if (empty($payslips)) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId);
            return;
        }

        $zip = new \ZipArchive();
        $zipFileName = tempnam(sys_get_temp_dir(), 'payslips_') . '.zip';

        if ($zip->open($zipFileName, \ZipArchive::CREATE) !== TRUE) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId);
            return;
        }

        $publicPath = dirname(__DIR__, 2) . '/public';

        foreach ($payslips as $payslip) {
            if (!empty($payslip['payslip_path'])) {
                $filePath = $publicPath . '/' . $payslip['payslip_path'];
                if (file_exists($filePath)) {
                    // Rename payslip file for better organization within the zip
                    $employeeName = $payslip['first_name'] . ' ' . $payslip['last_name'];
                    $zip->addFile($filePath, "{$department['name']}/{$employeeName}_payslip_{$period['period_name']}.pdf");
                }
            }
        }

        $zip->close();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="payslips_' . str_replace(' ', '_', $department['name']) . '_' . $period['period_name'] . '.zip"');
        header('Content-Length: ' . filesize($zipFileName));

        readfile($zipFileName);
        unlink($zipFileName);
    }

    public function generatePayeReportPdfForDepartment(string $departmentId, string $periodId): void
    {
        $tenantId = Auth::tenantId();
        $deptId = (int)\Jeffrey\Sikapay\Helpers\Sanitizer::text($departmentId);
        $pId = (int)\Jeffrey\Sikapay\Helpers\Sanitizer::text($periodId);

        $department = $this->departmentModel->find($deptId);
        if (!$department || (int)$department['tenant_id'] !== $tenantId) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId);
            return;
        }

        $period = $this->payrollPeriodModel->getPeriodById($pId, $tenantId);
        if (!$period || !$period['is_closed']) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId);
            return;
        }

        // --- Fetch data for the department ---
        $reportData = $this->graPayeAdviceModel->getAdviceByDepartmentAndPeriod($tenantId, $deptId, $pId);
        $tenantData = $this->tenantProfileModel->findByTenantId($tenantId);
        $includeCoverLetter = false;

        if (empty($reportData) || empty($tenantData)) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId); // Redirect if no data or tenant info
            return;
        }
        $pdf = new PayeReportPdfGenerator($reportData, $tenantData, $period, $includeCoverLetter, $department);
        $pdfContent = $pdf->generate();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="paye_report_' . str_replace(' ', '_', $department['name']) . '_' . $period['period_name'] . '.pdf"');
        echo $pdfContent;
    }

    public function generatePayeReportCsvForDepartment(string $departmentId, string $periodId): void
    {
        $tenantId = Auth::tenantId();
        $deptId = (int)\Jeffrey\Sikapay\Helpers\Sanitizer::text($departmentId);
        $pId = (int)\Jeffrey\Sikapay\Helpers\Sanitizer::text($periodId);

        $department = $this->departmentModel->find($deptId);
        if (!$department || (int)$department['tenant_id'] !== $tenantId) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId);
            return;
        }

        $period = $this->payrollPeriodModel->getPeriodById($pId, $tenantId);
        if (!$period || !$period['is_closed']) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId);
            return;
        }

        $reportData = $this->graPayeAdviceModel->getAdviceByDepartmentAndPeriod($tenantId, $deptId, $pId);
        $tenantData = $this->tenantProfileModel->findByTenantId($tenantId);

        if (empty($reportData) || empty($tenantData)) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId); // Redirect if no data or tenant info
            return;
        }
        $csv = new PayeReportCsvGenerator($reportData, $tenantData, $period);
        $csvContent = $csv->generate();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="paye_report_' . str_replace(' ', '_', $department['name']) . '_' . $period['period_name'] . '.csv"');
        echo $csvContent;
    }

    public function generatePayeReportExcelForDepartment(string $departmentId, string $periodId): void
    {
        $tenantId = Auth::tenantId();
        $deptId = (int)\Jeffrey\Sikapay\Helpers\Sanitizer::text($departmentId);
        $pId = (int)\Jeffrey\Sikapay\Helpers\Sanitizer::text($periodId);

        $department = $this->departmentModel->find($deptId);
        if (!$department || (int)$department['tenant_id'] !== $tenantId) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId);
            return;
        }

        $period = $this->payrollPeriodModel->getPeriodById($pId, $tenantId);
        if (!$period || !$period['is_closed']) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId);
            return;
        }

        $reportData = $this->graPayeAdviceModel->getAdviceByDepartmentAndPeriod($tenantId, $deptId, $pId);
        $tenantData = $this->tenantProfileModel->findByTenantId($tenantId);

        if (empty($reportData) || empty($tenantData)) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId); // Redirect if no data or tenant info
            return;
        }
        $excel = new PayeReportExcelGenerator($reportData, $tenantData, $period);
        $excelFile = $excel->generate();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="paye_report_' . str_replace(' ', '_', $department['name']) . '_' . $period['period_name'] . '.xlsx"');
        readfile($excelFile);
        unlink($excelFile);
    }
    
    public function generateSsnitReportPdfForDepartment(string $departmentId, string $periodId): void
    {
        $tenantId = Auth::tenantId();
        $deptId = (int)\Jeffrey\Sikapay\Helpers\Sanitizer::text($departmentId);
        $pId = (int)\Jeffrey\Sikapay\Helpers\Sanitizer::text($periodId);

        $department = $this->departmentModel->find($deptId);
        if (!$department || (int)$department['tenant_id'] !== $tenantId) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId);
            return;
        }

        $period = $this->payrollPeriodModel->getPeriodById($pId, $tenantId);
        if (!$period || !$period['is_closed']) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId);
            return;
        }

        $reportData = $this->ssnitAdviceModel->getAdviceByDepartmentAndPeriod($tenantId, $deptId, $pId);
        $tenantData = $this->tenantProfileModel->findByTenantId($tenantId);
        $includeCoverLetter = false;

        if (empty($reportData) || empty($tenantData)) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId); // Redirect if no data or tenant info
            return;
        }
        $pdf = new SsnitReportPdfGenerator($reportData, $tenantData, $period, $includeCoverLetter, $department);
        $pdfContent = $pdf->generate();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="ssnit_report_' . str_replace(' ', '_', $department['name']) . '_' . $period['period_name'] . '.pdf"');
        echo $pdfContent;
    }
    
    public function generateSsnitReportCsvForDepartment(string $departmentId, string $periodId): void
    {
        $tenantId = Auth::tenantId();
        $deptId = (int)\Jeffrey\Sikapay\Helpers\Sanitizer::text($departmentId);
        $pId = (int)\Jeffrey\Sikapay\Helpers\Sanitizer::text($periodId);

        $department = $this->departmentModel->find($deptId);
        if (!$department || (int)$department['tenant_id'] !== $tenantId) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId);
            return;
        }

        $period = $this->payrollPeriodModel->getPeriodById($pId, $tenantId);
        if (!$period || !$period['is_closed']) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId);
            return;
        }

        $reportData = $this->ssnitAdviceModel->getAdviceByDepartmentAndPeriod($tenantId, $deptId, $pId);
        $tenantData = $this->tenantProfileModel->findByTenantId($tenantId);

        if (empty($reportData) || empty($tenantData)) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId); // Redirect if no data or tenant info
            return;
        }
        $csv = new SsnitReportCsvGenerator($reportData, $tenantData, $period);
        $csvContent = $csv->generate();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="ssnit_report_' . str_replace(' ', '_', $department['name']) . '_' . $period['period_name'] . '.csv"');
        echo $csvContent;
    }

    public function generateSsnitReportExcelForDepartment(string $departmentId, string $periodId): void
    {
        $tenantId = Auth::tenantId();
        $deptId = (int)\Jeffrey\Sikapay\Helpers\Sanitizer::text($departmentId);
        $pId = (int)\Jeffrey\Sikapay\Helpers\Sanitizer::text($periodId);

        $department = $this->departmentModel->find($deptId);
        if (!$department || (int)$department['tenant_id'] !== $tenantId) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId);
            return;
        }

        $period = $this->payrollPeriodModel->getPeriodById($pId, $tenantId);
        if (!$period || !$period['is_closed']) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId);
            return;
        }

        $reportData = $this->ssnitAdviceModel->getAdviceByDepartmentAndPeriod($tenantId, $deptId, $pId);
        $tenantData = $this->tenantProfileModel->findByTenantId($tenantId);

        if (empty($reportData) || empty($tenantData)) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId); // Redirect if no data or tenant info
            return;
        }
        $excel = new SsnitReportExcelGenerator($reportData, $tenantData, $period);
        $excelFile = $excel->generate();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="ssnit_report_' . str_replace(' ', '_', $department['name']) . '_' . $period['period_name'] . '.xlsx"');
        readfile($excelFile);
        unlink($excelFile);
    }

    public function generateBankAdvicePdfForDepartment(string $departmentId, string $periodId): void
    {
        $tenantId = Auth::tenantId();
        $deptId = (int)\Jeffrey\Sikapay\Helpers\Sanitizer::text($departmentId);
        $pId = (int)\Jeffrey\Sikapay\Helpers\Sanitizer::text($periodId);

        $department = $this->departmentModel->find($deptId);
        if (!$department || (int)$department['tenant_id'] !== $tenantId) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId);
            return;
        }

        $period = $this->payrollPeriodModel->getPeriodById($pId, $tenantId);
        if (!$period || !$period['is_closed']) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId);
            return;
        }

        $reportData = $this->bankAdviceModel->getAdviceByDepartmentAndPeriod($tenantId, $deptId, $pId);
        $tenantData = $this->tenantProfileModel->findByTenantId($tenantId);
        $includeCoverLetter = false;

        if (empty($reportData) || empty($tenantData)) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId);
            return;
        }

        $pdf = new BankAdvicePdfGenerator($reportData, $tenantData, $period, $includeCoverLetter, $department);
        $pdfContent = $pdf->generate();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="bank_advice_' . str_replace(' ', '_', $department['name']) . '_' . $period['period_name'] . '.pdf"');
        echo $pdfContent;
    }
    
    public function generateBankAdviceCsvForDepartment(string $departmentId, string $periodId): void
    {
        $tenantId = Auth::tenantId();
        $deptId = (int)\Jeffrey\Sikapay\Helpers\Sanitizer::text($departmentId);
        $pId = (int)\Jeffrey\Sikapay\Helpers\Sanitizer::text($periodId);

        $department = $this->departmentModel->find($deptId);
        if (!$department || (int)$department['tenant_id'] !== $tenantId) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId);
            return;
        }

        $period = $this->payrollPeriodModel->getPeriodById($pId, $tenantId);
        if (!$period || !$period['is_closed']) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId);
            return;
        }

        $reportData = $this->bankAdviceModel->getAdviceByDepartmentAndPeriod($tenantId, $deptId, $pId);
        $tenantData = $this->tenantProfileModel->findByTenantId($tenantId);

        if (empty($reportData) || empty($tenantData)) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId); // Redirect if no data or tenant info
            return;
        }
        $csv = new BankAdviceCsvGenerator($reportData, $tenantData, $period);
        $csvContent = $csv->generate();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="bank_advice_' . str_replace(' ', '_', $department['name']) . '_' . $period['period_name'] . '.csv"');
        echo $csvContent;
    }

    public function generateBankAdviceExcelForDepartment(string $departmentId, string $periodId): void
    {
        $tenantId = Auth::tenantId();
        $deptId = (int)\Jeffrey\Sikapay\Helpers\Sanitizer::text($departmentId);
        $pId = (int)\Jeffrey\Sikapay\Helpers\Sanitizer::text($periodId);

        $department = $this->departmentModel->find($deptId);
        if (!$department || (int)$department['tenant_id'] !== $tenantId) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId);
            return;
        }

        $period = $this->payrollPeriodModel->getPeriodById($pId, $tenantId);
        if (!$period || !$period['is_closed']) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId);
            return;
        }

        $reportData = $this->bankAdviceModel->getAdviceByDepartmentAndPeriod($tenantId, $deptId, $pId);
        $tenantData = $this->tenantProfileModel->findByTenantId($tenantId);

        if (empty($reportData) || empty($tenantData)) {
            $this->redirect('/departments/' . $deptId . '/reports/' . $pId); // Redirect if no data or tenant info
            return;
        }
        $excel = new BankAdviceExcelGenerator($reportData, $tenantData, $period);
        $excelFile = $excel->generate();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="bank_advice_' . str_replace(' ', '_', $department['name']) . '_' . $period['period_name'] . '.xlsx"');
        readfile($excelFile);
        unlink($excelFile);
    }
}