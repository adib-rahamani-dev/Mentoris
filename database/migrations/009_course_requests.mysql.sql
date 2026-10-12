CREATE TABLE IF NOT EXISTS course_requests (
    id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL,
    course_slug VARCHAR(190) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    name VARCHAR(120) NOT NULL,
    phone VARCHAR(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    payment_plan VARCHAR(20) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'requested',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uniq_course_request_phone (course_slug,phone),
    INDEX idx_course_request_status (status,created_at),
    CONSTRAINT fk_course_request_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
