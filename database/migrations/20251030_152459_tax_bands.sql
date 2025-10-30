-- Migration: create_tax_bands_table

CREATE TABLE IF NOT EXISTS tax_bands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tax_year YEAR NOT NULL,
    band_start DECIMAL(15, 2) NOT NULL,
    band_end DECIMAL(15, 2) NULL, -- NULL for the highest band
    rate DECIMAL(5, 2) NOT NULL, -- e.g., 0.05 for 5%
    is_annual BOOLEAN NOT NULL DEFAULT TRUE, -- TRUE for annual, FALSE for monthly
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_band (tax_year, band_start, is_annual)
) ENGINE=INNODB;