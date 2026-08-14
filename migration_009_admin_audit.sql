-- Migration 009: admin security audit log (page opens + actions).
-- Daily log files live in storage/logs/; this table stores the same
-- event and the log filename so the printer can join file + database.
-- Safe to re-run.

SET NAMES utf8mb4;

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
