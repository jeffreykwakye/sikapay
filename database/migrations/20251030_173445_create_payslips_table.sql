-- Migration: create_payslips_table

CREATE TABLE IF NOT EXISTS payslips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tenant_id INT NOT NULL,
    payroll_period_id INT NOT NULL,
    gross_pay DECIMAL(15, 2) NOT NULL,
    total_deductions DECIMAL(15, 2) NOT NULL,
    net_pay DECIMAL(15, 2) NOT NULL,
    paye_amount DECIMAL(15, 2) NOT NULL,
    ssnit_employee_amount DECIMAL(15, 2) NOT NULL,
    ssnit_employer_amount DECIMAL(15, 2) NOT NULL,
    payslip_path VARCHAR(255) NULL, -- Path to the generated PDF payslip
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (payroll_period_id) REFERENCES payroll_periods(id) ON DELETE CASCADE,
    UNIQUE KEY unique_payslip (user_id, payroll_period_id)
) ENGINE=INNODB;