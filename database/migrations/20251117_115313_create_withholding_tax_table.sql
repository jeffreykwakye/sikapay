-- Migration: create_withholding_tax_table

CREATE TABLE IF NOT EXISTS withholding_tax_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    effective_date DATE NOT NULL,
    rate DECIMAL(5, 4) NOT NULL, -- e.g., 0.0750 for 7.5%
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_wht_rate (effective_date)
) ENGINE=INNODB;