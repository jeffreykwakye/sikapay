-- Migration: add_statutory_details_to_tenant_profiles

ALTER TABLE tenant_profiles
ADD COLUMN bank_name VARCHAR(255) NULL AFTER ghana_revenue_authority_tin,
ADD COLUMN bank_branch VARCHAR(255) NULL AFTER bank_name,
ADD COLUMN bank_address TEXT NULL AFTER bank_branch,
ADD COLUMN ssnit_office_name VARCHAR(255) NULL AFTER bank_address,
ADD COLUMN ssnit_office_address TEXT NULL AFTER ssnit_office_name,
ADD COLUMN gra_office_name VARCHAR(255) NULL AFTER ssnit_office_address,
ADD COLUMN gra_office_address TEXT NULL AFTER gra_office_name;