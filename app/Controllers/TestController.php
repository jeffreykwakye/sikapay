<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Controllers\Controller;
use Jeffrey\Sikapay\Helpers\BankAdviceExcelGenerator;
use Jeffrey\Sikapay\Helpers\BankAdvicePdfGenerator;
use Jeffrey\Sikapay\Helpers\PayeReportExcelGenerator;
use Jeffrey\Sikapay\Helpers\PayeReportPdfGenerator;
use Jeffrey\Sikapay\Helpers\PayslipPdfGenerator;
use Jeffrey\Sikapay\Helpers\SsnitReportExcelGenerator;
use Jeffrey\Sikapay\Helpers\SsnitReportPdfGenerator;
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

    public function payslipSample(): void
    {
        // Manual sample data for payslip generation
        $payslipData = [
            'basic_salary' => 3000.00,
            'employee_ssnit' => 165.00,
            'paye' => 120.50,
            'gross_pay' => 3200.00,
            'total_deductions' => 285.50,
            'net_pay' => 2914.50,
            'employer_ssnit' => 390.00,
            'total_taxable_income' => 2835.00, // Example value

            'detailed_allowances' => [
                // ['name' => 'Housing Allowance', 'amount' => 150.00],
                // ['name' => 'Transport Allowance', 'amount' => 50.00],
            ],
            'detailed_deductions' => [
                // ['name' => 'Loan Repayment', 'amount' => 50.00],
                // ['name' => 'Provident Fund', 'amount' => 50.00],
            ],
            // Overtime and Bonuses could be added here if needed
        ];

        $employeeData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'employee_id' => 'EMP001',
            'position_title' => 'Software Engineer',
            'department_name' => 'IT Department',
            'ssnit_number' => 'E1234567890',
            'tin_number' => 'P123456789', // Example TIN
            'employment_type' => 'Full-Time',
        ];

        $tenantData = [
            'legal_name' => 'Sample Company Ltd.',
            'physical_address' => '123 Business Avenue, Accra',
            'tin' => 'C001234567',
            'logo_path' => '/assets/images/profiles/placeholder.jpg', // Placeholder logo
            'support_email' => 'help@optbrain.com', 
            'phone_number' => '+233 20 123 4567',
        ];

        $payrollPeriodData = [
            'period_name' => 'November 2025',
            'start_date' => '2025-11-01',
            'end_date' => '2025-11-30',
            'payment_date' => '2025-12-05',
        ];

        // Create PDF
        $pdf = new PayslipPdfGenerator($payslipData, $employeeData, $tenantData, $payrollPeriodData);
        $pdfContent = $pdf->generatePayslip();

        // Output PDF to browser
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="sample_payslip.pdf"');
        echo $pdfContent;
        exit;
    }

    public function payePdfSample(): void
    {
        $tenantData = [
            'legal_name' => 'Sample Company Ltd.',
            'physical_address' => '123 Business Avenue, Accra',
            'tin' => 'C001234567',
            'logo_path' => '/assets/images/profiles/placeholder.jpg', // Placeholder logo
            'support_email' => 'help@optbrain.com',
            'phone_number' => '+233 20 123 4567',
        ];

        $payrollPeriodData = [
            'period_name' => 'November 2025',
            'start_date' => '2025-11-01',
            'end_date' => '2025-11-30',
            'payment_date' => '2025-12-05',
        ];

        $payeReportData = [
            [
                'employee_name' => 'John Doe',
                'tin_number' => 'P123456789',
                'taxable_income' => 2835.00,
                'paye_amount' => 120.50,
            ],
            [
                'employee_name' => 'Jane Smith',
                'tin_number' => 'P987654321',
                'taxable_income' => 4500.00,
                'paye_amount' => 450.00,
            ],
            [
                'employee_name' => 'Peter Jones',
                'tin_number' => 'P112233445',
                'taxable_income' => 1500.00,
                'paye_amount' => 0.00, // Example for someone below tax threshold
            ],
        ];

        $pdf = new PayeReportPdfGenerator($payeReportData, $tenantData, $payrollPeriodData);
        $pdfContent = $pdf->generate();

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="sample_paye_report.pdf"');
        echo $pdfContent;
        exit;
    }

    public function ssnitPdfSample(): void
    {
        $tenantData = [
            'legal_name' => 'Sample Company Ltd.',
            'physical_address' => '123 Business Avenue, Accra',
            'tin' => 'C001234567',
            'logo_path' => '/assets/images/profiles/placeholder.jpg', // Placeholder logo
            'support_email' => 'help@optbrain.com',
            'phone_number' => '+233 20 123 4567',
        ];

        $payrollPeriodData = [
            'period_name' => 'November 2025',
            'start_date' => '2025-11-01',
            'end_date' => '2025-11-30',
            'payment_date' => '2025-12-05',
        ];

        $ssnitReportData = [
            [
                'employee_name' => 'John Doe',
                'ssnit_number' => 'E1234567890',
                'basic_salary' => 3000.00,
                'employee_ssnit' => 165.00,
                'employer_ssnit' => 390.00,
                'total_ssnit' => 555.00,
            ],
            [
                'employee_name' => 'Jane Smith',
                'ssnit_number' => 'E9876543210',
                'basic_salary' => 4500.00,
                'employee_ssnit' => 247.50,
                'employer_ssnit' => 585.00,
                'total_ssnit' => 832.50,
            ],
            [
                'employee_name' => 'Peter Jones',
                'ssnit_number' => 'E1122334455',
                'basic_salary' => 1500.00,
                'employee_ssnit' => 82.50,
                'employer_ssnit' => 195.00,
                'total_ssnit' => 277.50,
            ],
        ];

        $pdf = new SsnitReportPdfGenerator($ssnitReportData, $tenantData, $payrollPeriodData);
        $pdfContent = $pdf->generate();

        header('Content-Type: application/pdf');
        echo $pdfContent;
        exit;
    }

    public function bankAdvicePdfSample(): void
    {
        $tenantData = [
            'legal_name' => 'Sample Company Ltd.',
            'physical_address' => '123 Business Avenue, Accra',
            'tin' => 'C001234567',
            'logo_path' => '/assets/images/profiles/placeholder.jpg', // Placeholder logo
            'support_email' => 'help@optbrain.com',
            'phone_number' => '+233 20 123 4567',
        ];

        $payrollPeriodData = [
            'period_name' => 'November 2025',
            'start_date' => '2025-11-01',
            'end_date' => '2025-11-30',
            'payment_date' => '2025-12-05',
        ];

        $bankAdviceReportData = [
            [
                'employee_name' => 'John Doe',
                'bank_name' => 'Ghana Commercial Bank',
                'bank_branch' => 'High Street',
                'bank_account_number' => '00112233445566',
                'bank_account_name' => 'John Doe',
                'net_pay' => 2914.50,
            ],
            [
                'employee_name' => 'Jane Smith',
                'bank_name' => 'Zenith Bank',
                'bank_branch' => 'Ring Road',
                'bank_account_number' => '00998877665544',
                'bank_account_name' => 'Jane Smith',
                'net_pay' => 4252.50,
            ],
            [
                'employee_name' => 'Peter Jones',
                'bank_name' => 'Ecobank',
                'bank_branch' => 'Independence Ave',
                'bank_account_number' => '00554433221100',
                'bank_account_name' => 'Peter Jones',
                'net_pay' => 1500.00,
            ],
        ];

        $pdf = new BankAdvicePdfGenerator($bankAdviceReportData, $tenantData, $payrollPeriodData);
        $pdfContent = $pdf->generate();

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="sample_bank_advice_report.pdf"');
        echo $pdfContent;
        exit;
    }

    public function payeExcelSample(): void
    {
        $tenantData = [
            'legal_name' => 'Sample Company Ltd.',
            'physical_address' => '123 Business Avenue, Accra',
            'tin' => 'C001234567',
            'logo_path' => '/assets/images/profiles/placeholder.jpg',
            'support_email' => 'help@optbrain.com',
            'phone_number' => '+233 20 123 4567',
        ];

        $payrollPeriodData = [
            'period_name' => 'November 2025',
            'start_date' => '2025-11-01',
            'end_date' => '2025-11-30',
            'payment_date' => '2025-12-05',
        ];

        $payeReportData = [
            [
                'employee_name' => 'John Doe',
                'tin_number' => 'P123456789',
                'taxable_income' => 2835.00,
                'paye_amount' => 120.50,
            ],
            [
                'employee_name' => 'Jane Smith',
                'tin_number' => 'P987654321',
                'taxable_income' => 4500.00,
                'paye_amount' => 450.00,
            ],
            [
                'employee_name' => 'Peter Jones',
                'tin_number' => 'P112233445',
                'taxable_income' => 1500.00,
                'paye_amount' => 0.00,
            ],
        ];

        $generator = new PayeReportExcelGenerator($payeReportData, $tenantData, $payrollPeriodData);
        $filePath = $generator->generate();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="sample_paye_report.xlsx"');
        readfile($filePath);
        unlink($filePath); // Delete the temporary file
        exit;
    }

    public function ssnitExcelSample(): void
    {
        $tenantData = [
            'legal_name' => 'Sample Company Ltd.',
            'physical_address' => '123 Business Avenue, Accra',
            'tin' => 'C001234567',
            'logo_path' => '/assets/images/profiles/placeholder.jpg',
            'support_email' => 'help@optbrain.com',
            'phone_number' => '+233 20 123 4567',
        ];

        $payrollPeriodData = [
            'period_name' => 'November 2025',
            'start_date' => '2025-11-01',
            'end_date' => '2025-11-30',
            'payment_date' => '2025-12-05',
        ];

        $ssnitReportData = [
            [
                'employee_name' => 'John Doe',
                'ssnit_number' => 'E1234567890',
                'basic_salary' => 3000.00,
                'employee_ssnit' => 165.00,
                'employer_ssnit' => 390.00,
                'total_ssnit' => 555.00,
            ],
            [
                'employee_name' => 'Jane Smith',
                'ssnit_number' => 'E9876543210',
                'basic_salary' => 4500.00,
                'employee_ssnit' => 247.50,
                'employer_ssnit' => 585.00,
                'total_ssnit' => 832.50,
            ],
            [
                'employee_name' => 'Peter Jones',
                'ssnit_number' => 'E1122334455',
                'basic_salary' => 1500.00,
                'employee_ssnit' => 82.50,
                'employer_ssnit' => 195.00,
                'total_ssnit' => 277.50,
            ],
        ];

        $generator = new SsnitReportExcelGenerator($ssnitReportData, $tenantData, $payrollPeriodData);
        $filePath = $generator->generate();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="sample_ssnit_report.xlsx"');
        readfile($filePath);
        unlink($filePath); // Delete the temporary file
        exit;
    }

    public function bankAdviceExcelSample(): void
    {
        $tenantData = [
            'legal_name' => 'Sample Company Ltd.',
            'physical_address' => '123 Business Avenue, Accra',
            'tin' => 'C001234567',
            'logo_path' => '/assets/images/profiles/placeholder.jpg',
            'support_email' => 'help@optbrain.com',
            'phone_number' => '+233 20 123 4567',
        ];

        $payrollPeriodData = [
            'period_name' => 'November 2025',
            'start_date' => '2025-11-01',
            'end_date' => '2025-11-30',
            'payment_date' => '2025-12-05',
        ];

        $bankAdviceReportData = [
            [
                'employee_name' => 'John Doe',
                'bank_name' => 'Ghana Commercial Bank',
                'bank_branch' => 'High Street',
                'bank_account_number' => '00112233445566',
                'bank_account_name' => 'John Doe',
                'net_pay' => 2914.50,
            ],
            [
                'employee_name' => 'Jane Smith',
                'bank_name' => 'Zenith Bank',
                'bank_branch' => 'Ring Road',
                'bank_account_number' => '00998877665544',
                'bank_account_name' => 'Jane Smith',
                'net_pay' => 4252.50,
            ],
            [
                'employee_name' => 'Peter Jones',
                'bank_name' => 'Ecobank',
                'bank_branch' => 'Independence Ave',
                'bank_account_number' => '00554433221100',
                'bank_account_name' => 'Peter Jones',
                'net_pay' => 1500.00,
            ],
        ];

        $generator = new BankAdviceExcelGenerator($bankAdviceReportData, $tenantData, $payrollPeriodData);
        $filePath = $generator->generate();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="sample_bank_advice_report.xlsx"');
        readfile($filePath);
        unlink($filePath); // Delete the temporary file
        exit;
    }
}