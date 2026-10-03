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
