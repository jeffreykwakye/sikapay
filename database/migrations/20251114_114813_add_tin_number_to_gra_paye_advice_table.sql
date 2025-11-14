-- Migration: add_tin_number_to_gra_paye_advice_table
-- Adds the tin_number column to the gra_paye_advice table.

ALTER TABLE gra_paye_advice
ADD COLUMN tin_number VARCHAR(50) NULL AFTER employee_name;