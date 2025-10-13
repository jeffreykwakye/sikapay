-- Migration: create_profiles_tables

-- 1. Tenant Profiles (Branding & Contact Details for Payslips/Reports)
CREATE TABLE IF NOT EXISTS tenant_profiles (
    tenant_id INT PRIMARY KEY,
    legal_name VARCHAR(255) NOT NULL,
    logo_path VARCHAR(255) NULL,
    phone_number VARCHAR(50) NULL,
    support_email VARCHAR(255) NULL,
    physical_address TEXT NULL,
    ghana_revenue_authority_tin VARCHAR(50) NULL,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=INNODB;

-- 2. User Profiles (Personal & Compliance Data)
CREATE TABLE IF NOT EXISTS user_profiles (
    user_id INT PRIMARY KEY,
    date_of_birth DATE NOT NULL,
    nationality VARCHAR(100) NOT NULL DEFAULT 'Ghanaian',
    marital_status ENUM('Single', 'Married', 'Divorced', 'Widowed') NOT NULL DEFAULT 'Single',
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    home_address TEXT NULL,
    ssnit_number VARCHAR(50) UNIQUE NULL,
    tin_number VARCHAR(50) UNIQUE NULL,
    id_card_type ENUM('Ghana Card', 'Voter ID', 'Passport') NOT NULL DEFAULT 'Ghana Card',
    id_card_number VARCHAR(100) UNIQUE NULL,
    emergency_contact_name VARCHAR(255) NOT NULL,
    emergency_contact_phone VARCHAR(50) NOT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=INNODB;