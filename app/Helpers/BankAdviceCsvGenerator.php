<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Helpers;

use Jeffrey\Sikapay\Core\Log;
use \Exception;

class BankAdviceCsvGenerator
{
    private array $reportData;
    private array $tenantData;
    private array $payrollPeriodData;

    public function __construct(array $reportData, array $tenantData, array $payrollPeriodData)
    {
        $this->reportData = $reportData;
        $this->tenantData = $tenantData;
        $this->payrollPeriodData = $payrollPeriodData;
    }

    public function generate(): string
    {
        try {
            $stream = fopen('php://memory', 'w');

            // Header Section
            fputcsv($stream, ['Bank Advice Report']);
            fputcsv($stream, [$this->tenantData['legal_name']]);
            fputcsv($stream, ['Period: ' . $this->payrollPeriodData['period_name']]);
            fputcsv($stream, ['Generated on: ' . date('Y-m-d H:i:s')]);
            fputcsv($stream, []); // Blank line

            // Summary Section
            $totalNetPay = array_sum(array_column($this->reportData, 'net_pay'));
            $employeeCount = count($this->reportData);

            fputcsv($stream, ['Summary']);
            fputcsv($stream, ['Total Employees', $employeeCount]);
            fputcsv($stream, ['Total Net Pay (GHS)', number_format($totalNetPay, 2)]);
            fputcsv($stream, []); // Blank line

            // Data Table Header
            fputcsv($stream, [
                'Employee Name',
                'Bank',
                'Branch',
                'Account Number',
                'Account Name',
                'Net Salary (GHS)'
            ]);

            // Data Table Body
            foreach ($this->reportData as $row) {
                fputcsv($stream, [
                    $row['employee_name'],
                    $row['bank_name'],
                    $row['bank_branch'],
                    "'" . $row['bank_account_number'],
                    $row['bank_account_name'],
                    number_format((float)$row['net_pay'], 2)
                ]);
            }

            // Total Row
            fputcsv($stream, [
                'TOTAL NET PAY',
                '',
                '',
                '',
                '',
                number_format($totalNetPay, 2)
            ]);

            rewind($stream);
            $csvContent = stream_get_contents($stream);
            fclose($stream);

            return $csvContent;
        } catch (Exception $e) {
            Log::error("Failed to generate Bank Advice CSV report. Error: " . $e->getMessage());
            return ''; // Return empty string on failure
        }
    }
}
