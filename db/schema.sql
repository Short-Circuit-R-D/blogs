-- Short Circuit Company — Lighting Technical Data CMS
-- Run this once against an empty MySQL database, e.g.:
--   mysql -u youruser -p your_db < schema.sql

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------
-- Admin users
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  name VARCHAR(120) NOT NULL DEFAULT '',
  email VARCHAR(190) DEFAULT NULL,
  phone VARCHAR(40) DEFAULT NULL,
  company VARCHAR(160) DEFAULT NULL,
  title VARCHAR(120) DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  email_verified_at DATETIME DEFAULT NULL,
  invite_token_hash VARCHAR(64) DEFAULT NULL,
  invite_expires_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_admin_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default admin: username "admin" / password "changeme123"
-- CHANGE THIS PASSWORD IMMEDIATELY after your first login (there's no in-app
-- "change password" screen yet — update the hash directly in this table, e.g.
-- via: UPDATE admin_users SET password_hash = '<new hash>' WHERE username = 'admin';
-- Generate a new hash in PHP with: password_hash('yourNewPassword', PASSWORD_DEFAULT)
INSERT INTO admin_users (username, password_hash) VALUES
('admin', '$2b$10$pcF1XBSdq24oqUkRg8yXv..higwn1Gzohru36/T1rWnhL.saPT0p6')
ON DUPLICATE KEY UPDATE username = username;

-- ---------------------------------------------------------------
-- Site users (public accounts) — registration, login, and the
-- new-article email subscription. No separate "subscriptions" table:
-- a user IS a subscriber via is_subscribed, which keeps a single
-- unsubscribe-by-token link working without extra joins.
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  role ENUM('client','employee','leader','admin') NOT NULL DEFAULT 'client',
  is_preapproved TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  password_hash VARCHAR(255) DEFAULT NULL,
  phone VARCHAR(40) DEFAULT NULL,
  profession VARCHAR(40) DEFAULT NULL,
  profession_other VARCHAR(120) DEFAULT NULL,
  company VARCHAR(160) DEFAULT NULL,
  is_subscribed TINYINT(1) NOT NULL DEFAULT 1,
  unsubscribe_token VARCHAR(64) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Per-role permissions cpanel — see migration_006_roles_and_topics.sql
-- for full notes. `admin` is always treated as able to do everything,
-- in code, regardless of what's stored here.
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

-- User-submitted community topics — moderated before going public.
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

-- A user can additionally follow one topic (an article's "tag", e.g. CRI,
-- Comfort, Standard) without subscribing to every new article — publishing
-- a new article with a matching tag emails just that topic's followers.
CREATE TABLE IF NOT EXISTS topic_subscriptions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  topic VARCHAR(60) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_user_topic (user_id, topic),
  CONSTRAINT fk_topic_sub_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------
-- Articles (lighting parameters / standards explainers / guides)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS articles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(80) NOT NULL UNIQUE,
  tag VARCHAR(40) NOT NULL DEFAULT 'Topic',
  icon VARCHAR(40) NOT NULL DEFAULT 'standard',
  image_url VARCHAR(500) DEFAULT NULL,
  title VARCHAR(160) NOT NULL,
  excerpt VARCHAR(300) NOT NULL,
  intro TEXT NOT NULL,
  why_text TEXT NOT NULL,
  physical_text TEXT DEFAULT NULL,
  physio_text TEXT DEFAULT NULL,
  psycho_text TEXT DEFAULT NULL,
  formula_text VARCHAR(255) DEFAULT NULL,
  formula_note VARCHAR(255) DEFAULT NULL,
  simulator_url VARCHAR(255) DEFAULT NULL,
  simulator_label VARCHAR(120) DEFAULT 'Open the full live simulator',
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  notified_at DATETIME DEFAULT NULL,
  view_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FULLTEXT KEY ft_articles_search (title, excerpt, intro, tag)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Comments / discussion on published articles. user_id is set when a
-- logged-in account posts; guest_name is used if we ever allow guests.
CREATE TABLE IF NOT EXISTS article_comments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  article_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED DEFAULT NULL,
  body TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_article_comments (article_id, created_at),
  CONSTRAINT fk_comment_article FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
  CONSTRAINT fk_comment_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Recommended-range table rows shown inside an article (Stage / Environment / Range / Notes)
CREATE TABLE IF NOT EXISTS article_ranges (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  article_id INT UNSIGNED NOT NULL,
  stage_label VARCHAR(80) NOT NULL,
  environment_label VARCHAR(80) NOT NULL,
  range_text VARCHAR(120) NOT NULL,
  notes VARCHAR(255) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------
-- Standards (EN 12464-1, IESNA, BREEAM, WELL, etc.)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS standards (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(60) NOT NULL,
  name VARCHAR(160) NOT NULL,
  region VARCHAR(80) DEFAULT NULL,
  description TEXT NOT NULL,
  official_url VARCHAR(255) DEFAULT NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------
-- Tools carousel (Dialux, Relux, LuxSCale, SChools, XR viewer, ...)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tools (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  description VARCHAR(500) NOT NULL,
  url VARCHAR(255) DEFAULT NULL,
  icon VARCHAR(40) NOT NULL DEFAULT 'standard',
  image_url VARCHAR(500) DEFAULT NULL,
  is_external TINYINT(1) NOT NULL DEFAULT 1,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------
-- Standard terminology matrix — how each parameter is named/aliased
-- across the major frameworks (shown as one comparison table)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS standard_terms (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parameter VARCHAR(60) NOT NULL,
  en_12464 VARCHAR(160) DEFAULT NULL,
  iso_8995 VARCHAR(160) DEFAULT NULL,
  ansi_ies VARCHAR(160) DEFAULT NULL,
  well_v2 VARCHAR(160) DEFAULT NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------
-- Events (LedEXPO 1/2/3, and any future booth/event) + gallery images
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  year YEAR DEFAULT NULL,
  description VARCHAR(500) DEFAULT NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  view_count INT UNSIGNED NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One unique view per device cookie (guest or logged-in) per article/event.
CREATE TABLE IF NOT EXISTS content_views (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  content_type VARCHAR(20) NOT NULL,
  content_id INT UNSIGNED NOT NULL,
  visitor_hash CHAR(64) NOT NULL,
  user_id INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_content_device (content_type, content_id, visitor_hash),
  KEY idx_content_views_content (content_type, content_id),
  KEY idx_content_views_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS event_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id INT UNSIGNED NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  caption VARCHAR(200) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin security audit (page opens + actions). Daily files in storage/logs/;
-- log_file stores only the filename so the printer can join file + DB row.
CREATE TABLE IF NOT EXISTS admin_audit_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  log_file VARCHAR(80) NOT NULL,
  occurred_at DATETIME NOT NULL,
  account VARCHAR(60) NOT NULL DEFAULT 'guest',
  action VARCHAR(60) NOT NULL,
  page VARCHAR(190) NOT NULL DEFAULT '',
  ip VARCHAR(45) NOT NULL DEFAULT '',
  location VARCHAR(160) NOT NULL DEFAULT '',
  device VARCHAR(180) NOT NULL DEFAULT '',
  user_agent VARCHAR(400) NOT NULL DEFAULT '',
  details TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_audit_occurred (occurred_at),
  KEY idx_audit_account (account),
  KEY idx_audit_file (log_file)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Public Contact Us form
CREATE TABLE IF NOT EXISTS contact_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  company VARCHAR(160) DEFAULT NULL,
  message TEXT NOT NULL,
  ip VARCHAR(45) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  emailed_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_contact_created (created_at),
  KEY idx_contact_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------
-- Seed data: the three LedEXPO events (no images yet — add via dashboard)
-- ---------------------------------------------------------------
INSERT INTO events (name, year, description, sort_order) VALUES
('LedEXPO 1', 2023, 'Short Circuit Company booth at LedEXPO — 2023 edition.', 1),
('LedEXPO 2', 2024, 'Short Circuit Company booth at LedEXPO — 2024 edition.', 2),
('LedEXPO 3', 2025, 'Short Circuit Company booth at LedEXPO — 2025 edition.', 3)
ON DUPLICATE KEY UPDATE name = name;

SET FOREIGN_KEY_CHECKS = 1;
