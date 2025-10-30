-- Migration: create_ssnit_rates_table

CREATE TABLE IF NOT EXISTS ssnit_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    effective_date DATE NOT NULL,
    employee_rate DECIMAL(5, 3) NOT NULL, -- e.g., 0.055 for 5.5%
    employer_rate DECIMAL(5, 2) NOT NULL, -- e.g., 0.13 for 13%
    max_contribution_cap DECIMAL(15, 2) NULL, -- Optional, if there's a cap
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_ssnit_rate (effective_date)
) ENGINE=INNODB;