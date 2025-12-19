-- Migration: add_cover_letter_details_to_tenant_profiles

ALTER TABLE tenant_profiles
ADD COLUMN authorized_signatory_name VARCHAR(255) NULL AFTER gra_office_address,
ADD COLUMN bank_advice_recipient_name VARCHAR(255) NULL AFTER authorized_signatory_name,
ADD COLUMN ssnit_report_recipient_name VARCHAR(255) NULL AFTER bank_advice_recipient_name,
ADD COLUMN gra_report_recipient_name VARCHAR(255) NULL AFTER ssnit_report_recipient_name;