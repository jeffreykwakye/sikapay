-- Migration: alter_user_profiles_add_profile_image

ALTER TABLE user_profiles
ADD COLUMN profile_picture_url VARCHAR(255) NULL AFTER emergency_contact_phone;