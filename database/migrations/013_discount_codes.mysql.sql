CREATE TABLE IF NOT EXISTS discount_codes (
 id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
 code VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
 discount_type VARCHAR(16) NOT NULL,
 value BIGINT UNSIGNED NOT NULL,
 applies_to VARCHAR(16) NOT NULL DEFAULT 'all',
 item_slug VARCHAR(190) NULL,
 max_uses INT UNSIGNED NULL,
 used_count INT UNSIGNED NOT NULL DEFAULT 0,
 starts_at DATETIME NULL, ends_at DATETIME NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_by CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL,
 created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
 CONSTRAINT discount_creator_fk FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS discount_redemptions (
 id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
 code_id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 request_type VARCHAR(16) NOT NULL,
 request_id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 code VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 original_amount BIGINT UNSIGNED NOT NULL,
 discount_amount BIGINT UNSIGNED NOT NULL,
 final_amount BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL,
 UNIQUE KEY discount_request(request_type,request_id),
 UNIQUE KEY discount_member(code_id,user_id),
 CONSTRAINT discount_redemption_code_fk FOREIGN KEY(code_id) REFERENCES discount_codes(id),
 CONSTRAINT discount_redemption_user_fk FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
