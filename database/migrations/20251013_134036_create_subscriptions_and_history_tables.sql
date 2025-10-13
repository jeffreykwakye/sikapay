-- Migration: create_subscriptions_and_history_tables

-- 1. Subscriptions (Current active subscription for a tenant)
CREATE TABLE IF NOT EXISTS subscriptions (
    tenant_id INT PRIMARY KEY,
    current_plan_id INT NOT NULL,
    status ENUM('active', 'past_due', 'on_hold', 'cancelled') NOT NULL DEFAULT 'active',
    start_date DATE NOT NULL,
    end_date DATE NULL, -- For fixed-term plans
    last_payment_date DATE NULL,
    next_billing_date DATE NULL,
    employee_count_at_billing INT DEFAULT 0,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (current_plan_id) REFERENCES plans(id) ON DELETE RESTRICT
) ENGINE=INNODB;

-- 2. Subscription History (Invoices and changes)
CREATE TABLE IF NOT EXISTS subscription_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    plan_id INT NOT NULL,
    action_type ENUM('New', 'Upgrade', 'Downgrade', 'Renewal', 'Cancellation', 'Invoice') NOT NULL,
    amount_paid DECIMAL(10, 2) NULL,
    billing_cycle_start DATE NULL,
    billing_cycle_end DATE NULL,
    details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE RESTRICT
) ENGINE=INNODB;