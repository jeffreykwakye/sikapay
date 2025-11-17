-- Migration: alter_withholding_tax_rates_add_employment_type

-- Add the new column
ALTER TABLE withholding_tax_rates
ADD COLUMN employment_type ENUM('Full-Time', 'Part-Time', 'Contract', 'Intern', 'National-Service', 'Casual-Worker') NOT NULL AFTER rate;

-- Drop the old unique key
ALTER TABLE withholding_tax_rates
DROP INDEX unique_wht_rate;

-- Add the new composite unique key
ALTER TABLE withholding_tax_rates
ADD UNIQUE KEY unique_wht_rate_by_type (effective_date, employment_type);