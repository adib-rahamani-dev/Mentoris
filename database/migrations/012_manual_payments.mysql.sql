CREATE TABLE IF NOT EXISTS manual_payments (
 id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
 user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 request_type VARCHAR(16) NOT NULL,
 request_id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 item_slug VARCHAR(190) NOT NULL, item_title VARCHAR(255) NOT NULL,
 part TINYINT UNSIGNED NOT NULL, amount BIGINT UNSIGNED NOT NULL,
 receipt JSON NOT NULL, version INT UNSIGNED NOT NULL DEFAULT 1,
 status VARCHAR(16) NOT NULL DEFAULT 'pending', review_note VARCHAR(1000) NOT NULL DEFAULT '',
 reviewed_by CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL, reviewed_at DATETIME NULL,
 created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
 UNIQUE KEY manual_payment_part(request_type,request_id,part),
 KEY manual_payment_user(user_id,created_at), KEY manual_payment_status(status,created_at),
 CONSTRAINT manual_payment_user_fk FOREIGN KEY(user_id) REFERENCES users(id),
 CONSTRAINT manual_payment_reviewer_fk FOREIGN KEY(reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
