-- Migration: rename_gra_paye_gross_salary_to_taxable_income
-- Renames the 'gross_salary' column in the 'gra_paye_advice' table to 'taxable_income'
-- to accurately reflect the data being stored for PAYE advice.

ALTER TABLE gra_paye_advice
CHANGE COLUMN gross_salary taxable_income DECIMAL(15, 2) NOT NULL;