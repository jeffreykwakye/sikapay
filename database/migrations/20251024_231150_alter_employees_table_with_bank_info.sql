-- Migration: alter_employees_table_with_bank_info

ALTER TABLE employees
ADD COLUMN bank_branch VARCHAR(100) NULL AFTER bank_name,
ADD COLUMN bank_account_name VARCHAR(100) NULL AFTER bank_account_number;