-- Migration: alter_employees_add_casual_worker_type

ALTER TABLE employees
MODIFY COLUMN employment_type ENUM('Full-Time', 'Part-Time', 'Contract', 'Intern', 'National-Service', 'Casual-Worker') NOT NULL DEFAULT 'Full-Time';