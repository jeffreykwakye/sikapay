-- Migration: create_employee_payroll_details_table

CREATE TABLE IF NOT EXISTS employee_payroll_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tenant_id INT NOT NULL,
    allowance_type VARCHAR(100) NOT NULL, -- e.g., "Housing Allowance", "Transport Allowance"
    amount DECIMAL(15, 2) NOT NULL,
    is_taxable BOOLEAN NOT NULL DEFAULT TRUE,
    effective_date DATE NOT NULL,
    end_date DATE NULL, -- NULL if ongoing
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_employee_allowance (user_id, allowance_type, effective_date)
) ENGINE=INNODB;