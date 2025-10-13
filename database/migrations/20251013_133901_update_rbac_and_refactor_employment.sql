-- Migration: update_rbac_and_refactor_employment

-- RBAC Tables (Granular Permissions)

-- 1. Permissions (The list of all available actions)
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) NOT NULL UNIQUE, -- e.g., 'payroll:create', 'employee:delete'
    description VARCHAR(255)
) ENGINE=INNODB;

-- 2. Role Permissions (Default access for a role)
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=INNODB;

-- 3. User Permissions (Individual overrides/exceptions)
CREATE TABLE IF NOT EXISTS user_permissions (
    user_id INT NOT NULL,
    permission_id INT NOT NULL,
    is_allowed BOOLEAN NOT NULL, -- TRUE to grant, FALSE to deny (override)
    
    PRIMARY KEY (user_id, permission_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=INNODB;

-- Employment Refactoring

-- 4. Departments (Separated from Positions)
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_department (tenant_id, name),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=INNODB;

-- 5. Positions (Linked to the new departments table)
CREATE TABLE IF NOT EXISTS positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    department_id INT NULL,
    title VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_position (tenant_id, title),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=INNODB;

-- 6. Employees (Current Employment Status)
CREATE TABLE IF NOT EXISTS employees (
    user_id INT PRIMARY KEY,
    tenant_id INT NOT NULL,
    employee_id VARCHAR(50) UNIQUE NULL,
    hire_date DATE NOT NULL,
    termination_date DATE NULL,
    current_position_id INT NULL, 
    employment_type ENUM('Full-Time', 'Part-Time', 'Contract', 'Intern') NOT NULL DEFAULT 'Full-Time',
    current_salary_ghs DECIMAL(15, 2) NOT NULL,
    payment_method ENUM('Bank Transfer', 'Cash', 'Mobile Money') NOT NULL DEFAULT 'Bank Transfer',
    bank_name VARCHAR(100) NULL,
    bank_account_number VARCHAR(50) NULL,
    is_payroll_eligible BOOLEAN NOT NULL DEFAULT TRUE,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (current_position_id) REFERENCES positions(id) ON DELETE SET NULL
) ENGINE=INNODB;

-- 7. Employment History (Tracking promotions, demotions, salary changes)
CREATE TABLE IF NOT EXISTS employment_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tenant_id INT NOT NULL,
    effective_date DATE NOT NULL,
    record_type ENUM('Hired', 'Promotion', 'Demotion', 'Transfer', 'Salary Change', 'Termination') NOT NULL,
    old_salary DECIMAL(15, 2) NULL,
    new_salary DECIMAL(15, 2) NULL,
    notes TEXT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=INNODB;