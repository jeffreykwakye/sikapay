-- Migration: create_advice_tables
-- Creates tables to store immutable snapshots of report data for specific payroll periods.

-- Stores a snapshot of the SSNIT report for a given period
CREATE TABLE IF NOT EXISTS ssnit_advice (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payroll_period_id INT NOT NULL,
    tenant_id INT NOT NULL,
    employee_name VARCHAR(255) NOT NULL,
    basic_salary DECIMAL(15, 2) NOT NULL,
    employee_ssnit DECIMAL(15, 2) NOT NULL,
    employer_ssnit DECIMAL(15, 2) NOT NULL,
    total_ssnit DECIMAL(15, 2) NOT NULL,
    
    FOREIGN KEY (payroll_period_id) REFERENCES payroll_periods(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=INNODB;

-- Stores a snapshot of the PAYE report for a given period
CREATE TABLE IF NOT EXISTS gra_paye_advice (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payroll_period_id INT NOT NULL,
    tenant_id INT NOT NULL,
    employee_name VARCHAR(255) NOT NULL,
    gross_salary DECIMAL(15, 2) NOT NULL,
    paye_amount DECIMAL(15, 2) NOT NULL,
    
    FOREIGN KEY (payroll_period_id) REFERENCES payroll_periods(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=INNODB;

-- Stores a snapshot of the Bank Advice report for a given period
CREATE TABLE IF NOT EXISTS bank_advice (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payroll_period_id INT NOT NULL,
    tenant_id INT NOT NULL,
    employee_name VARCHAR(255) NOT NULL,
    bank_name VARCHAR(100),
    bank_branch VARCHAR(100),
    bank_account_number VARCHAR(50),
    bank_account_name VARCHAR(100),
    net_pay DECIMAL(15, 2) NOT NULL,
    
    FOREIGN KEY (payroll_period_id) REFERENCES payroll_periods(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=INNODB;