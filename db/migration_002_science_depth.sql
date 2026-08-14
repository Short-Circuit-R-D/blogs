-- Migration 002 — "The Science of Light" content depth
-- Run this on an EXISTING database (one already set up with schema.sql).
-- Safe to run once; re-running will error harmlessly on duplicate columns.
--
--   mysql -u youruser -p your_db < migration_002_science_depth.sql

SET NAMES utf8mb4;

ALTER TABLE articles
  ADD COLUMN physical_text TEXT DEFAULT NULL AFTER why_text,
  ADD COLUMN physio_text   TEXT DEFAULT NULL AFTER physical_text,
  ADD COLUMN psycho_text   TEXT DEFAULT NULL AFTER physio_text,
  ADD COLUMN formula_text  VARCHAR(255) DEFAULT NULL AFTER psycho_text,
  ADD COLUMN formula_note  VARCHAR(255) DEFAULT NULL AFTER formula_text;

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

-- Optional: after this runs, load seed_content.sql's new INSERT blocks
-- (or migration_002_seed.sql, if you're not re-running the full seed)
-- to fill in the new columns for the existing articles + the terminology
-- matrix rows.
