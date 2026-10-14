CREATE TABLE IF NOT EXISTS payment_refunds (
 id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
 payment_id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
 user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 amount BIGINT UNSIGNED NOT NULL,
 status VARCHAR(16) NOT NULL DEFAULT 'pending',
 reason VARCHAR(255) NOT NULL,
 transfer_reference VARCHAR(120) NOT NULL DEFAULT '',
 note VARCHAR(1000) NOT NULL DEFAULT '',
 completed_by CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL,
 completed_at DATETIME NULL,
 created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
 KEY refund_status(status,created_at), KEY refund_user(user_id,created_at),
 CONSTRAINT refund_payment_fk FOREIGN KEY(payment_id) REFERENCES manual_payments(id),
 CONSTRAINT refund_user_fk FOREIGN KEY(user_id) REFERENCES users(id),
 CONSTRAINT refund_manager_fk FOREIGN KEY(completed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
