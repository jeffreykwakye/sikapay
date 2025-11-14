-- Migration: create_payslip_detail_tables
-- Creates tables to store the line-item details for each payslip, ensuring full auditability.

-- Table for Allowances applied to a specific payslip
CREATE TABLE IF NOT EXISTS payslip_allowances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payslip_id INT NOT NULL,
    tenant_id INT NOT NULL,
    allowance_name VARCHAR(255) NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    
    FOREIGN KEY (payslip_id) REFERENCES payslips(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=INNODB;

-- Table for Deductions applied to a specific payslip
CREATE TABLE IF NOT EXISTS payslip_deductions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payslip_id INT NOT NULL,
    tenant_id INT NOT NULL,
    deduction_name VARCHAR(255) NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    
    FOREIGN KEY (payslip_id) REFERENCES payslips(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=INNODB;

-- Table for Overtime applied to a specific payslip
CREATE TABLE IF NOT EXISTS payslip_overtimes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payslip_id INT NOT NULL,
    tenant_id INT NOT NULL,
    rate_multiplier DECIMAL(5, 2) NOT NULL COMMENT 'e.g., 1.5 for time-and-a-half',
    hours DECIMAL(5, 2) NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    
    FOREIGN KEY (payslip_id) REFERENCES payslips(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=INNODB;

-- Table for Bonuses applied to a specific payslip
CREATE TABLE IF NOT EXISTS payslip_bonuses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payslip_id INT NOT NULL,
    tenant_id INT NOT NULL,
    bonus_name VARCHAR(255) NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    
    FOREIGN KEY (payslip_id) REFERENCES payslips(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=INNODB;