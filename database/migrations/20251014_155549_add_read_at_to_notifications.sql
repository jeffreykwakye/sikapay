-- Migration: add_read_at_to_notifications

ALTER TABLE notifications 
ADD COLUMN read_at TIMESTAMP NULL AFTER is_read;