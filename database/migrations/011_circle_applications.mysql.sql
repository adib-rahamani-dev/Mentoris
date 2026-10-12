CREATE TABLE IF NOT EXISTS circle_applications (
    signup_id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    details JSON NOT NULL,
    resume JSON NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT circle_applications_signup_fk FOREIGN KEY (signup_id) REFERENCES event_signups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
