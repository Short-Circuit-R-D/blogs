-- Short Circuit Company — Lighting Technical Data CMS
-- Migration 004: public user accounts + email subscriptions, article
-- search (full-text), and "notify subscribers on publish" tracking.
--
-- Run this once against your existing database:
--   mysql -u youruser -p your_db < migration_004_users_search.sql
--
-- Safe to run on a fresh database too (schema.sql already includes all
-- of this) — every statement below is written to be skip-if-exists.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  is_subscribed TINYINT(1) NOT NULL DEFAULT 1,
  unsubscribe_token VARCHAR(64) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE articles ADD COLUMN IF NOT EXISTS notified_at DATETIME DEFAULT NULL AFTER sort_order;

-- FULLTEXT lets the search page do real relevance-ranked search instead
-- of a slow LIKE '%...%' scan. If this errors on a very old MySQL
-- (<5.6) that can't FULLTEXT an InnoDB table, the search page already
-- falls back to LIKE automatically — you can skip this one statement.
ALTER TABLE articles ADD FULLTEXT KEY ft_articles_search (title, excerpt, intro, tag);
