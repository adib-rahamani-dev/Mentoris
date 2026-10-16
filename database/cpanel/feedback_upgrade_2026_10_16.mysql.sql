-- Mentoris survey upgrade 015 | 2026-10-16
-- Select the EXISTING Mentoris database in phpMyAdmin, then import.
-- Requires the previous schema through migration 014. No data is deleted.
-- Re-running is safe: existing tables are not recreated, rows are not duplicated.
-- Re-running deliberately restores the third-circle window to 2026-10-16 18:00-23:00 Tehran.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS feedback_windows (
    event_slug VARCHAR(190) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    updated_by CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_feedback_window_editor FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS survey_responses (
    id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
    event_slug VARCHAR(190) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    response_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    identity_mode VARCHAR(12) NOT NULL,
    display_name VARCHAR(120) NOT NULL DEFAULT '',
    user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL,
    content_rating TINYINT UNSIGNED NOT NULL,
    hosting_rating TINYINT UNSIGNED NOT NULL,
    challenge VARCHAR(40) NOT NULL DEFAULT 'none',
    comment TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY survey_response_once (event_slug,response_key),
    KEY survey_event_time (event_slug,created_at),
    CONSTRAINT survey_response_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 24 Mehr 1405, 18:00–23:00 Asia/Tehran (UTC+03:30).
INSERT INTO feedback_windows (event_slug,starts_at,ends_at,enabled,updated_by,updated_at)
VALUES ('therapists-circle-third','2026-10-16 14:30:00','2026-10-16 19:30:00',1,NULL,UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE starts_at=VALUES(starts_at),ends_at=VALUES(ends_at),enabled=1,updated_at=VALUES(updated_at);

UPDATE content_translations ct JOIN content_entities ce ON ce.id=ct.entity_id
SET ct.metadata=JSON_SET(COALESCE(ct.metadata,JSON_OBJECT()),'$.content_status','completed','$.registration_mode','closed'),ct.updated_at=UTC_TIMESTAMP()
WHERE ce.entity_type='event' AND ce.slug='therapists-circle-tabriz';

INSERT IGNORE INTO migrations (migration,executed_at) VALUES ('015_survey_responses.mysql.sql',UTC_TIMESTAMP());
