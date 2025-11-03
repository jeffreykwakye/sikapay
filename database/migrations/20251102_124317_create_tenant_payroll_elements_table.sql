-- Migration: create_tenant_payroll_elements_table

CREATE TABLE IF NOT EXISTS tenant_payroll_elements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    category ENUM('allowance', 'deduction') NOT NULL,
    amount_type ENUM('fixed', 'percentage') NOT NULL,
    default_amount DECIMAL(15, 2) NOT NULL,
    calculation_base ENUM('gross_salary', 'basic_salary', 'net_salary') NULL, -- NULL if amount_type is fixed
    is_taxable BOOLEAN NOT NULL DEFAULT TRUE,
    is_ssnit_chargeable BOOLEAN NOT NULL DEFAULT TRUE,
    is_recurring BOOLEAN NOT NULL DEFAULT FALSE,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_tenant_payroll_element (tenant_id, name)
) ENGINE=INNODB;