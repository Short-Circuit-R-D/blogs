-- Short Circuit Company — Lighting Technical Data CMS
-- Migration 007: optional phone on public accounts, subscribe-only
-- users (nullable password_hash), and article discussion comments.
--
-- Safe to re-run. Skips ADD COLUMN if `phone` is already on `users`.

SET NAMES utf8mb4;

ALTER TABLE users
  MODIFY password_hash VARCHAR(255) DEFAULT NULL;

-- Add `phone` only when it is missing (avoids #1060 Duplicate column).
SET @phone_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'users'
    AND COLUMN_NAME = 'phone'
);
SET @sql := IF(
  @phone_exists = 0,
  'ALTER TABLE users ADD COLUMN phone VARCHAR(40) DEFAULT NULL AFTER password_hash',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

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
