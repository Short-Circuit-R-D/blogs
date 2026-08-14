-- Migration 011: full profile fields on CMS admin_users.
-- Contact Us emails go to each active admin's email.
-- Safe to re-run.

SET NAMES utf8mb4;

ALTER TABLE admin_users MODIFY username VARCHAR(190) NOT NULL;

ALTER TABLE admin_users
  ADD COLUMN IF NOT EXISTS name VARCHAR(120) NOT NULL DEFAULT '' AFTER password_hash,
  ADD COLUMN IF NOT EXISTS email VARCHAR(190) DEFAULT NULL AFTER name,
  ADD COLUMN IF NOT EXISTS phone VARCHAR(40) DEFAULT NULL AFTER email,
  ADD COLUMN IF NOT EXISTS company VARCHAR(160) DEFAULT NULL AFTER phone,
  ADD COLUMN IF NOT EXISTS title VARCHAR(120) DEFAULT NULL AFTER company,
  ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER title,
  ADD COLUMN IF NOT EXISTS updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- If the login username is already an email, copy it into email.
UPDATE admin_users
SET email = username
WHERE (email IS NULL OR email = '')
  AND username LIKE '%@%';
