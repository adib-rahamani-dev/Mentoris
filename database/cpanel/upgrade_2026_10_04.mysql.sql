-- Additive upgrade for an existing Mentoris database (001 required).
-- Import into the database selected for this site. No data is deleted.
CREATE TABLE IF NOT EXISTS migrations (migration VARCHAR(255) PRIMARY KEY, executed_at VARCHAR(32) NOT NULL);

-- Forward-compatible content studio migration.
-- This is intentionally separate from 001 so databases created before the
-- content studio was introduced can be upgraded without reinitializing data.

CREATE TABLE IF NOT EXISTS content_entities (
    id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    entity_type VARCHAR(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    slug VARCHAR(190) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    status VARCHAR(24) NOT NULL DEFAULT 'draft',
    sort_order INT NOT NULL DEFAULT 0,
    author_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL,
    published_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY content_type_slug_unique (entity_type, slug),
    KEY content_status_sort_index (entity_type, status, sort_order),
    CONSTRAINT content_author_fk FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_translations (
    id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    entity_id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    locale VARCHAR(10) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255) NULL,
    excerpt TEXT NULL,
    body LONGTEXT NULL,
    metadata JSON NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY content_entity_locale_unique (entity_id, locale),
    CONSTRAINT content_translation_entity_fk FOREIGN KEY (entity_id) REFERENCES content_entities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_relations (
    source_id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    target_id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    relation_type VARCHAR(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    PRIMARY KEY (source_id, target_id, relation_type),
    CONSTRAINT content_relation_source_fk FOREIGN KEY (source_id) REFERENCES content_entities(id) ON DELETE CASCADE,
    CONSTRAINT content_relation_target_fk FOREIGN KEY (target_id) REFERENCES content_entities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT IGNORE INTO migrations (migration,executed_at) VALUES ('002_content_studio.mysql.sql',UTC_TIMESTAMP());

-- Independent member profiles and event participation. No payment or SMS state is implied.
CREATE TABLE IF NOT EXISTS therapist_profiles (
    user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    latin_name VARCHAR(120) NOT NULL DEFAULT '',
    national_id_ciphertext VARBINARY(96) NULL,
    professional_number VARCHAR(80) NOT NULL DEFAULT '',
    education_level VARCHAR(40) NOT NULL DEFAULT '',
    specialization VARCHAR(40) NOT NULL DEFAULT '',
    university VARCHAR(160) NOT NULL DEFAULT '',
    approaches JSON NULL,
    practice_areas JSON NULL,
    experience VARCHAR(40) NOT NULL DEFAULT '',
    social_link VARCHAR(255) NOT NULL DEFAULT '',
    updated_at DATETIME NOT NULL,
    CONSTRAINT therapist_profiles_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS event_signups (
    id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL,
    event_slug VARCHAR(190) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    name VARCHAR(120) NOT NULL,
    phone VARCHAR(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    city VARCHAR(80) NOT NULL,
    status VARCHAR(24) NOT NULL DEFAULT 'requested',
    attended_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY event_signups_event_phone_unique (event_slug, phone),
    KEY event_signups_user_index (user_id, created_at),
    CONSTRAINT event_signups_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS event_feedback (
    id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    signup_id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    content_rating TINYINT UNSIGNED NOT NULL,
    hosting_rating TINYINT UNSIGNED NOT NULL,
    challenge VARCHAR(40) NOT NULL,
    comment TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY event_feedback_signup_unique (signup_id),
    CONSTRAINT event_feedback_signup_fk FOREIGN KEY (signup_id) REFERENCES event_signups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS event_certificates (
    id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    signup_id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    certificate_number VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    issued_at DATETIME NOT NULL,
    UNIQUE KEY event_certificates_signup_unique (signup_id),
    UNIQUE KEY event_certificates_number_unique (certificate_number),
    KEY event_certificates_user_index (user_id, issued_at),
    CONSTRAINT event_certificates_signup_fk FOREIGN KEY (signup_id) REFERENCES event_signups(id) ON DELETE RESTRICT,
    CONSTRAINT event_certificates_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT IGNORE INTO migrations (migration,executed_at) VALUES ('003_therapist_circle.mysql.sql',UTC_TIMESTAMP());

CREATE TABLE IF NOT EXISTS member_profiles (
    user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
    member_type VARCHAR(20) NOT NULL DEFAULT 'other',
    education_status VARCHAR(20) NOT NULL DEFAULT '',
    field_of_study VARCHAR(160) NOT NULL DEFAULT '',
    degree VARCHAR(20) NOT NULL DEFAULT '',
    university VARCHAR(160) NOT NULL DEFAULT '',
    city VARCHAR(100) NOT NULL DEFAULT '',
    practice_status VARCHAR(20) NOT NULL DEFAULT '',
    specialty_fields VARCHAR(500) NOT NULL DEFAULT '',
    details JSON NOT NULL,
    training_courses JSON NOT NULL,
    marketing_consent TINYINT(1) NOT NULL DEFAULT 0,
    terms_accepted_at DATETIME NULL,
    completed_at DATETIME NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_member_type_degree (member_type, degree),
    INDEX idx_member_city_practice (city, practice_status),
    INDEX idx_member_field (field_of_study),
    INDEX idx_member_university (university),
    CONSTRAINT fk_member_profile_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Preserve existing academic information; do not infer consent or mark profiles complete.
INSERT IGNORE INTO member_profiles
    (user_id,member_type,education_status,field_of_study,degree,university,city,practice_status,specialty_fields,details,training_courses,marketing_consent,terms_accepted_at,completed_at,updated_at)
SELECT u.id,
    CASE WHEN u.professional_role LIKE '%دانشجو%' OR p.education_level IN ('masters_student','phd_student') THEN 'student'
         WHEN u.professional_role LIKE '%درمانگر%' OR u.professional_role LIKE '%روان‌شناس%' OR u.professional_role LIKE '%روانشناس%' THEN 'therapist'
         ELSE 'other' END,
    CASE WHEN p.education_level IN ('masters_student','phd_student') THEN 'student'
         WHEN p.education_level IN ('masters','phd') THEN 'graduate' ELSE '' END,
    '',
    CASE WHEN p.education_level IN ('masters_student','masters') THEN 'master'
         WHEN p.education_level IN ('phd_student','phd') THEN 'phd' ELSE '' END,
    COALESCE(p.university,''),'','','',
    JSON_OBJECT('bio',COALESCE(u.bio,''),'professional_url',COALESCE(p.social_link,''),
        'specialization',CASE p.specialization WHEN 'clinical' THEN 'بالینی' WHEN 'health' THEN 'سلامت' WHEN 'general' THEN 'عمومی' WHEN 'counseling' THEN 'مشاوره' WHEN 'other' THEN 'سایر' ELSE '' END),
    JSON_ARRAY(),0,NULL,NULL,UTC_TIMESTAMP()
FROM users u LEFT JOIN therapist_profiles p ON p.user_id=u.id;


INSERT IGNORE INTO migrations (migration,executed_at) VALUES ('004_member_profiles.mysql.sql',UTC_TIMESTAMP());

-- Telegram assistant and free-resource download metrics
CREATE TABLE IF NOT EXISTS telegram_users (
    chat_id BIGINT NOT NULL PRIMARY KEY,
    display_name VARCHAR(120) NOT NULL DEFAULT '',
    username VARCHAR(64) NOT NULL DEFAULT '',
    state VARCHAR(32) NOT NULL DEFAULT '',
    state_data JSON NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS telegram_updates (
    update_id BIGINT NOT NULL PRIMARY KEY,
    payload JSON NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'queued',
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    next_attempt_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_telegram_update_queue (status,next_attempt_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS telegram_outbox (
    id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
    method VARCHAR(40) NOT NULL,
    payload JSON NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'queued',
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    next_attempt_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_telegram_outbox_queue (status,next_attempt_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS telegram_questions (
    id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
    chat_id BIGINT NOT NULL,
    update_id BIGINT NOT NULL UNIQUE,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_telegram_question_status (status,created_at),
    CONSTRAINT fk_telegram_question_chat FOREIGN KEY (chat_id) REFERENCES telegram_users(chat_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS telegram_event_requests (
    id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
    chat_id BIGINT NOT NULL,
    event_slug VARCHAR(190) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    name VARCHAR(120) NOT NULL,
    phone VARCHAR(16) NOT NULL,
    city VARCHAR(80) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'requested',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY telegram_event_chat_unique (chat_id,event_slug),
    INDEX idx_telegram_event_requests (status,created_at),
    CONSTRAINT fk_telegram_event_chat FOREIGN KEY (chat_id) REFERENCES telegram_users(chat_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS resource_downloads (
    user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    resource_slug VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    downloads INT UNSIGNED NOT NULL DEFAULT 1,
    last_download_at DATETIME NOT NULL,
    PRIMARY KEY (user_id,resource_slug),
    CONSTRAINT fk_resource_download_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO migrations (migration,executed_at) VALUES ('005_telegram_assistant.mysql.sql',UTC_TIMESTAMP());
