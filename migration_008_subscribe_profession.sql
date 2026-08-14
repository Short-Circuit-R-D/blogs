-- Subscriber profile fields used on the subscribe-only form:
-- profession (consultant / engineer / professor / other) and optional company.
-- Safe to re-run.

SET NAMES utf8mb4;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'profession');
SET @sql := IF(@col = 0, 'ALTER TABLE users ADD COLUMN profession VARCHAR(40) DEFAULT NULL AFTER phone', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'profession_other');
SET @sql := IF(@col = 0, 'ALTER TABLE users ADD COLUMN profession_other VARCHAR(120) DEFAULT NULL AFTER profession', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'company');
SET @sql := IF(@col = 0, 'ALTER TABLE users ADD COLUMN company VARCHAR(160) DEFAULT NULL AFTER profession_other', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
