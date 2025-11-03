-- Migration: create_employee_payroll_details_table

CREATE TABLE IF NOT EXISTS employee_payroll_details ( 
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tenant_id INT NOT NULL,
    payroll_element_id INT NOT NULL,
    assigned_amount DECIMAL(15, 2) NOT NULL,
    effective_date DATE NOT NULL,
    end_date DATE NULL, -- NULL if ongoing
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (payroll_element_id) REFERENCES tenant_payroll_elements(id) ON DELETE RESTRICT,
    UNIQUE KEY unique_employee_payroll_element (user_id, payroll_element_id)
) ENGINE=INNODB;