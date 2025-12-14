<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Controllers\Controller;
use Jeffrey\Sikapay\Models\PayrollPeriodModel;
use Jeffrey\Sikapay\Models\PayslipModel;
use Jeffrey\Sikapay\Models\TenantProfileModel;
use Jeffrey\Sikapay\Models\PayrollSettingsModel; // Added
use Jeffrey\Sikapay\Core\Auth;
use Jeffrey\Sikapay\Helpers\PayeReportPdfGenerator;
use Jeffrey\Sikapay\Helpers\PayeReportExcelGenerator;
use Jeffrey\Sikapay\Helpers\SsnitReportPdfGenerator;
use Jeffrey\Sikapay\Helpers\SsnitReportExcelGenerator;
use Jeffrey\Sikapay\Helpers\BankAdvicePdfGenerator;
use Jeffrey\Sikapay\Helpers\BankAdviceExcelGenerator;
use Jeffrey\Sikapay\Models\SsnitAdviceModel;
use Jeffrey\Sikapay\Models\GraPayeAdviceModel;
use Jeffrey\Sikapay\Models\BankAdviceModel;

class StatutoryReportController extends Controller
{
    private PayrollPeriodModel $payrollPeriodModel;
    private PayslipModel $payslipModel;
    protected TenantProfileModel $tenantProfileModel;
    private SsnitAdviceModel $ssnitAdviceModel;
    private GraPayeAdviceModel $graPayeAdviceModel;
    private BankAdviceModel $bankAdviceModel;
    private PayrollSettingsModel $payrollSettingsModel; // Added

    public function __construct()
    {
        parent::__construct();
        $this->payrollPeriodModel = new PayrollPeriodModel();
        $this->payslipModel = new PayslipModel();
        $this->tenantProfileModel = new TenantProfileModel();
        $this->ssnitAdviceModel = new SsnitAdviceModel();
        $this->graPayeAdviceModel = new GraPayeAdviceModel();
        $this->bankAdviceModel = new BankAdviceModel();
        $this->payrollSettingsModel = new PayrollSettingsModel(); // Instantiated
    }

    public function index(): void
    {
        $periods = $this->payrollPeriodModel->getAllPeriods(Auth::tenantId());
        $this->view('reports/index', [
            'title' => 'Statutory Reports',
            'periods' => $periods
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


        $pdf = new PayeReportPdfGenerator($reportData, $tenantData, $period, $includeCoverLetter);
        $pdfContent = $pdf->generate();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="paye_report_' . $period['period_name'] . '.pdf"');
        echo $pdfContent;
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

        $pdf = new SsnitReportPdfGenerator($reportData, $tenantData, $period, $includeCoverLetter);
        $pdfContent = $pdf->generate();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="ssnit_report_' . $period['period_name'] . '.pdf"');
        echo $pdfContent;
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
            // Redirect or show an error if data is not available
            $this->redirect('/reports');
            return;
        }

        $pdf = new BankAdvicePdfGenerator($reportData, $tenantData, $period, $includeCoverLetter);
        $pdfContent = $pdf->generate();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="bank_advice_' . $period['period_name'] . '.pdf"');
        echo $pdfContent;
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
            // Redirect or show an error if data is not available
            $this->redirect('/reports');
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
            // Handle case with no payslips for the period
            // Maybe redirect with a flash message
            $this->redirect('/reports');
            return;
        }

        $zip = new \ZipArchive();
        $zipFileName = tempnam(sys_get_temp_dir(), 'payslips_') . '.zip';

        if ($zip->open($zipFileName, \ZipArchive::CREATE) !== TRUE) {
            // Handle error
            // Log this error
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
}