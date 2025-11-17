-- Migration: alter_employees_add_national_service_type

ALTER TABLE employees
MODIFY COLUMN employment_type ENUM('Full-Time', 'Part-Time', 'Contract', 'Intern', 'National-Service') NOT NULL DEFAULT 'Full-Time';
