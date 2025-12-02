-- Migration: add_is_paid_to_leave_types_table
ALTER TABLE leave_types ADD COLUMN is_paid TINYINT(1) DEFAULT 0 NULL;