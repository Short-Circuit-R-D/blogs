-- Unique device views for articles and events.
-- One row per (content, device cookie hash). view_count is the live total.

ALTER TABLE articles
  ADD COLUMN view_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER notified_at;

ALTER TABLE events
  ADD COLUMN view_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER is_published;

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
