-- Migration 012: CMS admin email confirmation (one-time invite link).
-- Safe to re-run via ensureAdminUsersSchema() in PHP.

SET NAMES utf8mb4;

ALTER TABLE admin_users
  ADD COLUMN email_verified_at DATETIME DEFAULT NULL AFTER is_active,
  ADD COLUMN invite_token_hash VARCHAR(64) DEFAULT NULL AFTER email_verified_at,
  ADD COLUMN invite_expires_at DATETIME DEFAULT NULL AFTER invite_token_hash;

UPDATE admin_users
SET email_verified_at = COALESCE(email_verified_at, created_at)
WHERE email_verified_at IS NULL
  AND (invite_token_hash IS NULL OR invite_token_hash = '');
