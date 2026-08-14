-- Short Circuit Company — Lighting Technical Data CMS
-- Migration 006: account roles, a role-permissions control panel, and a
-- moderated "community topics" feature (logged-in users can submit a
-- topic; publishing requires acceptance unless their role — or the user
-- individually — is marked to auto-publish).
--
-- Roles (fixed, 4 tiers):
--   client    — public/customer account (default on signup)
--   employee  — SC employee account
--   leader    — SC team leader — can optionally be granted moderation
--   admin     — full admin / high board — always the top tier, always
--               has every permission (enforced in code, not just data)
--
-- Run this once against your existing database:
--   mysql -u youruser -p your_db < migration_006_roles_and_topics.sql
--
-- Safe to run on a fresh database too (schema.sql now includes all of
-- this) — every statement below is written to be skip-if-exists.

SET NAMES utf8mb4;

-- ---------------------------------------------------------------
-- Roles on the public `users` table. `admin_users` (the CMS dashboard
-- login) remains separate and is always treated as the top "admin" /
-- high-board tier — it does not need a role column.
-- ---------------------------------------------------------------
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS role ENUM('client','employee','leader','admin') NOT NULL DEFAULT 'client' AFTER email,
  ADD COLUMN IF NOT EXISTS is_preapproved TINYINT(1) NOT NULL DEFAULT 0 AFTER role,
  ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER is_preapproved;

-- ---------------------------------------------------------------
-- Role permissions — the "cpanel" high admins use to change what each
-- role can do, without touching code. One row per role.
--   can_post_topics    → role is allowed to submit a new community topic
--   can_moderate_topics → role can see the moderation queue and
--                          accept/reject topics submitted by others
--                          (leaders can be switched on/off here; admin
--                          is always treated as able to moderate)
--   auto_publish_topics → topics submitted by this role skip the
--                          acceptance queue and go live immediately
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS role_permissions (
  role VARCHAR(20) NOT NULL PRIMARY KEY,
  can_post_topics TINYINT(1) NOT NULL DEFAULT 0,
  can_moderate_topics TINYINT(1) NOT NULL DEFAULT 0,
  auto_publish_topics TINYINT(1) NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO role_permissions (role, can_post_topics, can_moderate_topics, auto_publish_topics) VALUES
('client',   1, 0, 0),
('employee', 1, 0, 0),
('leader',   1, 0, 0),
('admin',    1, 1, 1)
ON DUPLICATE KEY UPDATE role = role;

-- ---------------------------------------------------------------
-- Community topics — user-submitted posts, moderated before they go
-- public. Distinct from `articles` (admin-authored guides) and from
-- an article's `tag` (still called "topic" elsewhere in this app —
-- these are separate concepts).
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS discussion_topics (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(160) NOT NULL,
  slug VARCHAR(190) NOT NULL,
  category VARCHAR(60) NOT NULL DEFAULT 'General',
  body TEXT NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  reject_reason VARCHAR(300) DEFAULT NULL,
  decided_by VARCHAR(120) DEFAULT NULL,
  decided_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_topic_slug (slug),
  KEY idx_status (status),
  KEY idx_user (user_id),
  CONSTRAINT fk_topic_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
