-- Communications hub upgrade; select the existing Mentoris database first.
-- Idempotent. Requires installed core users and migrations tables.
SET NAMES utf8mb4;
CREATE TABLE IF NOT EXISTS migrations (migration VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,executed_at VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS comm_accounts (
 channel VARCHAR(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 chat_id VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 display_name VARCHAR(120) NOT NULL DEFAULT '',
 user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL,
 auth_version INT UNSIGNED NULL,
 link_method VARCHAR(16) NOT NULL DEFAULT '',
 state VARCHAR(40) NOT NULL DEFAULT '',
 state_data LONGTEXT NOT NULL,
 notifications_enabled TINYINT(1) NOT NULL DEFAULT 1,
 admin_alerts TINYINT(1) NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
 PRIMARY KEY(channel,chat_id), UNIQUE KEY comm_account_user(channel,user_id),
 CONSTRAINT fk_comm_account_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comm_links (
 token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
 user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 channel VARCHAR(16) NOT NULL, auth_version INT UNSIGNED NOT NULL,
 expires_at DATETIME NOT NULL, used_at DATETIME NULL, created_at DATETIME NOT NULL,
 CONSTRAINT fk_comm_link_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 INDEX idx_comm_link_expiry(expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comm_updates (
 channel VARCHAR(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 update_id BIGINT NOT NULL, payload LONGTEXT NOT NULL,
 status VARCHAR(20) NOT NULL DEFAULT 'queued', attempts INT UNSIGNED NOT NULL DEFAULT 0,
 next_attempt_at DATETIME NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
 PRIMARY KEY(channel,update_id), INDEX idx_comm_updates_queue(status,next_attempt_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comm_outbox (
 id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
 channel VARCHAR(16) NOT NULL, chat_id VARCHAR(32) NOT NULL,
 method VARCHAR(40) NOT NULL, payload LONGTEXT NOT NULL,
 dedupe_key VARCHAR(190) CHARACTER SET ascii COLLATE ascii_bin NULL,
 user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL, auth_version INT UNSIGNED NULL,
 required_permission VARCHAR(64) NOT NULL DEFAULT '',
 status VARCHAR(20) NOT NULL DEFAULT 'queued', attempts INT UNSIGNED NOT NULL DEFAULT 0,
 next_attempt_at DATETIME NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
 UNIQUE KEY comm_outbox_dedupe(channel,chat_id,dedupe_key), INDEX idx_comm_outbox_queue(status,next_attempt_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comm_actions (
 id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
 channel VARCHAR(16) NOT NULL, chat_id VARCHAR(32) NOT NULL,
 user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL, auth_version INT UNSIGNED NOT NULL,
 action VARCHAR(32) NOT NULL, payload LONGTEXT NOT NULL,
 expires_at DATETIME NOT NULL, used_at DATETIME NULL, created_at DATETIME NOT NULL,
 INDEX idx_comm_action_expiry(expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_tickets (
 id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
 user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL,
 channel VARCHAR(16) NOT NULL, chat_id VARCHAR(32) NULL,
 subject VARCHAR(160) NOT NULL, category VARCHAR(32) NOT NULL DEFAULT 'general',
 status VARCHAR(20) NOT NULL DEFAULT 'open',
 created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
 CONSTRAINT fk_support_ticket_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
 INDEX idx_support_ticket_user(user_id,updated_at), INDEX idx_support_ticket_status(status,updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_messages (
 sequence_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
 id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
 ticket_id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 author_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL,
 sender_role VARCHAR(16) NOT NULL, body LONGTEXT NOT NULL,
 source_key VARCHAR(190) CHARACTER SET ascii COLLATE ascii_bin NULL,
 created_at DATETIME NOT NULL,
 CONSTRAINT fk_support_message_ticket FOREIGN KEY(ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
 UNIQUE KEY support_message_source(source_key), INDEX idx_support_messages(ticket_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_attachments (
 id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
 message_id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 channel VARCHAR(16) NOT NULL, file_id VARCHAR(512) NULL, local_name VARCHAR(100) NULL,
 filename VARCHAR(190) NOT NULL, mime_type VARCHAR(100) NOT NULL,
 byte_size INT UNSIGNED NOT NULL, media_kind VARCHAR(16) NOT NULL,
 created_at DATETIME NOT NULL,
 CONSTRAINT fk_support_attachment_message FOREIGN KEY(message_id) REFERENCES support_messages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO migrations (migration,executed_at) VALUES ('010_communications_hub.mysql.sql',UTC_TIMESTAMP());
