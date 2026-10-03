-- Idempotent SMS registration upgrade. Import into this site's selected database.
-- Existing records are preserved. Requires existing users/rate_limits tables.
SET NAMES utf8mb4;
CREATE TABLE IF NOT EXISTS migrations (migration VARCHAR(255) PRIMARY KEY, executed_at VARCHAR(32) NOT NULL);
-- No existing user data or columns are changed.
CREATE TABLE IF NOT EXISTS phone_verifications (
    user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
    phone VARCHAR(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
    verified_at DATETIME NOT NULL,
    CONSTRAINT fk_verified_phone_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sms_challenges (
    user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
    challenge_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    phone_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    code_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    expires_at DATETIME NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    status VARCHAR(16) NOT NULL,
    sent_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_sms_challenge_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sms_deliveries (
    id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
    user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL,
    phone_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    kind VARCHAR(32) NOT NULL DEFAULT 'phone_otp',
    status VARCHAR(16) NOT NULL,
    provider_message_id BIGINT UNSIGNED NULL,
    provider_code INT NULL,
    cost DECIMAL(14,4) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_sms_created (created_at),
    CONSTRAINT fk_sms_delivery_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Only pending registration codes; no existing user data is changed.
CREATE TABLE IF NOT EXISTS registration_sms_challenges (
    session_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
    phone_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    challenge_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    code_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    expires_at DATETIME NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    status VARCHAR(16) NOT NULL,
    sent_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_registration_sms_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO migrations (migration,executed_at) VALUES ('007_sms_verification.mysql.sql',UTC_TIMESTAMP()),('008_registration_sms.mysql.sql',UTC_TIMESTAMP());
