-- Short Circuit Company — Lighting Technical Data CMS
-- Migration 005: topic-level subscriptions. A user can follow one topic
-- (an article's "tag" — CRI, Comfort, Standard, etc.) instead of every
-- new article; publishing/notifying an article with a matching tag emails
-- just that topic's followers, in addition to the site-wide subscribers.
--
-- Run this once against your existing database:
--   mysql -u youruser -p your_db < migration_005_topic_subscriptions.sql
--
-- Requires migration_004_users_search.sql (the users table) to have run
-- first. Safe to run on a fresh database too (schema.sql already has this).

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS topic_subscriptions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  topic VARCHAR(60) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_user_topic (user_id, topic),
  CONSTRAINT fk_topic_sub_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
