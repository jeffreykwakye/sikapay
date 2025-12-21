-- Migration: Add user_id to advice tables for departmental report filtering

-- Add user_id to ssnit_advice
ALTER TABLE `ssnit_advice`
ADD COLUMN `user_id` INT NULL AFTER `tenant_id`,
ADD INDEX `idx_ssnit_advice_user_id` (`user_id`);

-- Add user_id to gra_paye_advice
ALTER TABLE `gra_paye_advice`
ADD COLUMN `user_id` INT NULL AFTER `tenant_id`,
ADD INDEX `idx_gra_paye_advice_user_id` (`user_id`);

-- Add user_id to bank_advice
ALTER TABLE `bank_advice`
ADD COLUMN `user_id` INT NULL AFTER `tenant_id`,
ADD INDEX `idx_bank_advice_user_id` (`user_id`);

-- Add foreign key constraints
-- Note: We use ON DELETE SET NULL to preserve historical advice records even if a user is deleted.
ALTER TABLE `ssnit_advice`
ADD CONSTRAINT `fk_ssnit_advice_user`
FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;

ALTER TABLE `gra_paye_advice`
ADD CONSTRAINT `fk_gra_paye_advice_user`
FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;

ALTER TABLE `bank_advice`
ADD CONSTRAINT `fk_bank_advice_user`
FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;