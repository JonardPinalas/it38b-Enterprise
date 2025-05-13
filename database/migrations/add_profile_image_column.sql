-- Add profile_image column to users table
ALTER TABLE `users` 
ADD COLUMN `profile_image` VARCHAR(255) NULL DEFAULT NULL 
AFTER `last_login`;

-- This will allow storing the path to the user's profile image
COMMIT; 