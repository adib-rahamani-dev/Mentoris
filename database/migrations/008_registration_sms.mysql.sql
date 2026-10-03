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
