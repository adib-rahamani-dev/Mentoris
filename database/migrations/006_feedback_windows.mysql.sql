CREATE TABLE IF NOT EXISTS feedback_windows (
    event_slug VARCHAR(190) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    updated_by CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_feedback_window_editor FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
