-- Migration: add_ssnit_number_to_ssnit_advice_table
-- Adds the ssnit_number column to the ssnit_advice table.

ALTER TABLE ssnit_advice
ADD COLUMN ssnit_number VARCHAR(50) NULL AFTER employee_name;