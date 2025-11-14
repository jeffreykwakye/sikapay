-- Migration: enhance_payslips_table
-- Adds detailed component columns to the payslips table for a full audit trail.

ALTER TABLE payslips
ADD COLUMN basic_salary DECIMAL(15, 2) NOT NULL AFTER payroll_period_id,
ADD COLUMN total_allowances DECIMAL(15, 2) NOT NULL DEFAULT 0.00 AFTER basic_salary,
ADD COLUMN total_overtime DECIMAL(15, 2) NOT NULL DEFAULT 0.00 AFTER total_allowances,
ADD COLUMN total_bonuses DECIMAL(15, 2) NOT NULL DEFAULT 0.00 AFTER total_overtime,
ADD COLUMN total_taxable_income DECIMAL(15, 2) NOT NULL AFTER gross_pay;