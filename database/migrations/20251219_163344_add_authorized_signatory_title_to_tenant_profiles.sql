-- Migration: add_authorized_signatory_title_to_tenant_profiles

ALTER TABLE tenant_profiles
ADD COLUMN authorized_signatory_title VARCHAR(255) NULL AFTER authorized_signatory_name;