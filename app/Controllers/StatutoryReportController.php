<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Controllers\Controller;
use Jeffrey\Sikapay\Models\PayrollPeriodModel;
use Jeffrey\Sikapay\Models\PayslipModel;
use Jeffrey\Sikapay\Models\TenantProfileModel;
use Jeffrey\Sikapay\Core\Auth;
use Jeffrey\Sikapay\Helpers\PayeReportPdfGenerator;
use Jeffrey\Sikapay\Helpers\PayeReportExcelGenerator;
use Jeffrey\Sikapay\Helpers\SsnitReportPdfGenerator;
use Jeffrey\Sikapay\Helpers\SsnitReportExcelGenerator;

class StatutoryReportController extends Controller
{
    private PayrollPeriodModel $payrollPeriodModel;
    private PayslipModel $payslipModel;
    protected TenantProfileModel $tenantProfileModel;

    public function __construct()
    {
        parent::__construct();
        $this->payrollPeriodModel = new PayrollPeriodModel();
        $this->payslipModel = new PayslipModel();
        $this->tenantProfileModel = new TenantProfileModel();
    }

    public function index(): void
    {
        $this->view('reports/index', ['title' => 'Statutory Reports']);
    }

    public function generatePayeReportPdf(): void
    {
        $tenantId = Auth::tenantId();
        $latestPeriod = $this->payrollPeriodModel->getLatestClosedPeriod($tenantId);

        if (!$latestPeriod) {
            // Handle case where no payroll has been run
            // You might want to redirect with an error message
            return;
        }

        $payslips = $this->payslipModel->getPayslipsByPeriod((int)$latestPeriod['id'], $tenantId);
        $tenantData = $this->tenantProfileModel->findByTenantId($tenantId);

        $reportData = [];
        foreach ($payslips as $payslip) {
            $reportData[] = [
                'employee_id' => $payslip['employee_id'],
                'employee_name' => $payslip['first_name'] . ' ' . $payslip['last_name'],
                'gross_salary' => $payslip['gross_pay'],
                'paye' => $payslip['paye_amount'],
            ];
        }

        $pdf = new PayeReportPdfGenerator($reportData, $tenantData, $latestPeriod);
        $pdfContent = $pdf->generate();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="paye_report.pdf"');
        echo $pdfContent;
    }

    public function generatePayeReportExcel(): void
    {
        $tenantId = Auth::tenantId();
        $latestPeriod = $this->payrollPeriodModel->getLatestClosedPeriod($tenantId);

        if (!$latestPeriod) {
            // Handle case where no payroll has been run
            return;
        }

        $payslips = $this->payslipModel->getPayslipsByPeriod((int)$latestPeriod['id'], $tenantId);
        $tenantData = $this->tenantProfileModel->findByTenantId($tenantId);

        $reportData = [];
        foreach ($payslips as $payslip) {
            $reportData[] = [
                'employee_id' => $payslip['employee_id'],
                'employee_name' => $payslip['first_name'] . ' ' . $payslip['last_name'],
                'gross_salary' => $payslip['gross_pay'],
                'paye' => $payslip['paye_amount'],
            ];
        }

        $excel = new PayeReportExcelGenerator($reportData, $tenantData, $latestPeriod);
        $excelFile = $excel->generate();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="paye_report.xlsx"');
        readfile($excelFile);
        unlink($excelFile);
    }

    public function generateSsnitReportPdf(): void
    {
        $tenantId = Auth::tenantId();
        $latestPeriod = $this->payrollPeriodModel->getLatestClosedPeriod($tenantId);

        if (!$latestPeriod) {
            // Handle case where no payroll has been run
            return;
        }

        $payslips = $this->payslipModel->getPayslipsByPeriod((int)$latestPeriod['id'], $tenantId);
        $tenantData = $this->tenantProfileModel->findByTenantId($tenantId);

        $reportData = [];
        foreach ($payslips as $payslip) {
            $reportData[] = [
                'employee_id' => $payslip['employee_id'],
                'employee_name' => $payslip['first_name'] . ' ' . $payslip['last_name'],
                'gross_salary' => $payslip['gross_pay'],
                'employee_ssnit' => $payslip['employee_ssnit'],
                'employer_ssnit' => $payslip['employer_ssnit'],
            ];
        }

        $pdf = new SsnitReportPdfGenerator($reportData, $tenantData, $latestPeriod);
        $pdfContent = $pdf->generate();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="ssnit_report.pdf"');
        echo $pdfContent;
    }

    public function generateSsnitReportExcel(): void
    {
        $tenantId = Auth::tenantId();
        $latestPeriod = $this->payrollPeriodModel->getLatestClosedPeriod($tenantId);

        if (!$latestPeriod) {
            // Handle case where no payroll has been run
            return;
        }

        $payslips = $this->payslipModel->getPayslipsByPeriod((int)$latestPeriod['id'], $tenantId);
        $tenantData = $this->tenantProfileModel->findByTenantId($tenantId);

        $reportData = [];
        foreach ($payslips as $payslip) {
            $reportData[] = [
                'employee_id' => $payslip['employee_id'],
                'employee_name' => $payslip['first_name'] . ' ' . $payslip['last_name'],
                'gross_salary' => $payslip['gross_pay'],
                'employee_ssnit' => $payslip['ssnit_employee_amount'],
                'employer_ssnit' => $payslip['ssnit_employer_amount'],
            ];
        }

        $excel = new SsnitReportExcelGenerator($reportData, $tenantData, $latestPeriod);
        $excelFile = $excel->generate();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="ssnit_report.xlsx"');
        readfile($excelFile);
        unlink($excelFile);
    }
}
