-- Migration: add_unique_index_to_login_attempts_login
ALTER TABLE login_attempts ADD UNIQUE INDEX `email_ip_address` (`email`, `ip_address`);