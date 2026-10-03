-- Mentoris complete current schema | 2026-10-04
-- Import in phpMyAdmin AFTER selecting this site's existing database.
-- Every table is created only if absent. Existing tables and records are preserved.
-- Repeated imports are supported. Existing column definitions are not rewritten.
-- Requires MySQL 5.7+ or a compatible MariaDB with JSON support.
SET NAMES utf8mb4;

-- migrations
CREATE TABLE IF NOT EXISTS migrations (
    migration VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    executed_at VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- users
CREATE TABLE IF NOT EXISTS users (
    id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(191) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(24) NOT NULL DEFAULT '',
    professional_role VARCHAR(120) NOT NULL DEFAULT '',
    bio TEXT NOT NULL,
    account_role VARCHAR(32) NOT NULL DEFAULT 'student',
    status VARCHAR(24) NOT NULL DEFAULT 'active',
    auth_version INT UNSIGNED NOT NULL DEFAULT 1,
    email_verified_at DATETIME NULL,
    last_login_at DATETIME NULL,
    password_changed_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY users_email_unique (email),
    KEY users_role_status_index (account_role, status),
    KEY users_created_at_index (created_at),
    CONSTRAINT users_role_check CHECK (account_role IN ('super_admin','admin','editor','instructor','support','student')),
    CONSTRAINT users_status_check CHECK (status IN ('active','suspended'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- password_reset_tokens
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY password_reset_token_unique (token_hash),
    KEY password_reset_user_expiry_index (user_id, expires_at),
    CONSTRAINT password_reset_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- notifications
CREATE TABLE IF NOT EXISTS notifications (
    id CHAR(16) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    title VARCHAR(190) NOT NULL,
    message TEXT NOT NULL,
    read_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    KEY notifications_user_read_index (user_id, read_at, created_at),
    CONSTRAINT notifications_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- orders
CREATE TABLE IF NOT EXISTS orders (
    id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    order_number VARCHAR(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    customer_name VARCHAR(120) NOT NULL,
    customer_email VARCHAR(191) NOT NULL,
    item_type VARCHAR(32) NOT NULL,
    item_id VARCHAR(190) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    item_title VARCHAR(255) NOT NULL,
    amount BIGINT UNSIGNED NOT NULL,
    currency CHAR(3) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'IRT',
    status VARCHAR(24) NOT NULL DEFAULT 'pending',
    expires_at DATETIME NOT NULL,
    paid_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY orders_number_unique (order_number),
    KEY orders_user_created_index (user_id, created_at),
    KEY orders_inventory_index (item_type, item_id, status, expires_at),
    CONSTRAINT orders_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT orders_status_check CHECK (status IN ('pending','paid','failed','canceled','expired','refunded'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- payment_transactions
CREATE TABLE IF NOT EXISTS payment_transactions (
    id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    order_id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    gateway VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    authority VARCHAR(191) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    amount BIGINT UNSIGNED NOT NULL,
    currency CHAR(3) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'IRT',
    status VARCHAR(24) NOT NULL DEFAULT 'initiated',
    reference_id VARCHAR(191) CHARACTER SET ascii COLLATE ascii_bin NULL,
    message VARCHAR(500) NULL,
    gateway_response JSON NOT NULL,
    verified_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY transactions_authority_unique (authority),
    UNIQUE KEY transactions_reference_unique (reference_id),
    KEY transactions_order_created_index (order_id, created_at),
    CONSTRAINT transactions_order_fk FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT,
    CONSTRAINT transactions_status_check CHECK (status IN ('initiated','verified','failed','canceled','expired','refunded'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- inventory_locks
CREATE TABLE IF NOT EXISTS inventory_locks (
    item_type VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    item_id VARCHAR(190) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (item_type, item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- enrollments
CREATE TABLE IF NOT EXISTS enrollments (
    id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    course_slug VARCHAR(190) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    order_id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NULL,
    status VARCHAR(24) NOT NULL DEFAULT 'active',
    enrolled_at DATETIME NOT NULL,
    UNIQUE KEY enrollments_user_course_unique (user_id, course_slug),
    KEY enrollments_course_status_index (course_slug, status),
    CONSTRAINT enrollments_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT enrollments_order_fk FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    CONSTRAINT enrollments_status_check CHECK (status IN ('active','completed','canceled','refunded'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- event_registrations
CREATE TABLE IF NOT EXISTS event_registrations (
    id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL,
    event_slug VARCHAR(190) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    applicant_name VARCHAR(120) NOT NULL,
    applicant_email VARCHAR(191) NOT NULL,
    applicant_phone VARCHAR(24) NOT NULL DEFAULT '',
    professional_role VARCHAR(120) NOT NULL DEFAULT '',
    status VARCHAR(24) NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY event_registration_unique (event_slug, applicant_email),
    KEY event_registration_status_index (event_slug, status),
    CONSTRAINT event_registration_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- certificates
CREATE TABLE IF NOT EXISTS certificates (
    id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    course_slug VARCHAR(190) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    certificate_number VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    issued_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,
    UNIQUE KEY certificates_number_unique (certificate_number),
    KEY certificates_user_index (user_id, issued_at),
    CONSTRAINT certificates_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- community_memberships
CREATE TABLE IF NOT EXISTS community_memberships (
    id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(191) NOT NULL,
    professional_role VARCHAR(120) NOT NULL DEFAULT '',
    interests TEXT NOT NULL,
    status VARCHAR(24) NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY community_email_unique (email),
    KEY community_status_index (status, created_at),
    CONSTRAINT community_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- contact_messages
CREATE TABLE IF NOT EXISTS contact_messages (
    id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(191) NOT NULL,
    phone VARCHAR(24) NOT NULL DEFAULT '',
    subject VARCHAR(190) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(24) NOT NULL DEFAULT 'new',
    ip_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY contact_status_created_index (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- rate_limits
CREATE TABLE IF NOT EXISTS rate_limits (
    key_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    hits INT UNSIGNED NOT NULL,
    reset_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY rate_limits_reset_index (reset_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- sessions
CREATE TABLE IF NOT EXISTS sessions (
    id_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    payload LONGBLOB NOT NULL,
    last_activity DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    KEY sessions_expiry_index (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- content_entities
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

-- content_translations
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

-- content_relations
CREATE TABLE IF NOT EXISTS content_relations (
    source_id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    target_id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    relation_type VARCHAR(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    PRIMARY KEY (source_id, target_id, relation_type),
    CONSTRAINT content_relation_source_fk FOREIGN KEY (source_id) REFERENCES content_entities(id) ON DELETE CASCADE,
    CONSTRAINT content_relation_target_fk FOREIGN KEY (target_id) REFERENCES content_entities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- audit_logs
CREATE TABLE IF NOT EXISTS audit_logs (
    id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    actor_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL,
    action VARCHAR(100) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    subject_type VARCHAR(80) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    subject_id VARCHAR(190) CHARACTER SET ascii COLLATE ascii_bin NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    created_at DATETIME NOT NULL,
    KEY audit_actor_created_index (actor_id, created_at),
    KEY audit_subject_index (subject_type, subject_id, created_at),
    CONSTRAINT audit_actor_fk FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- therapist_profiles
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

-- event_signups
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

-- event_feedback
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

-- event_certificates
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

-- member_profiles
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

-- telegram_users
CREATE TABLE IF NOT EXISTS telegram_users (
    chat_id BIGINT NOT NULL PRIMARY KEY,
    display_name VARCHAR(120) NOT NULL DEFAULT '',
    username VARCHAR(64) NOT NULL DEFAULT '',
    state VARCHAR(32) NOT NULL DEFAULT '',
    state_data JSON NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- telegram_updates
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

-- telegram_outbox
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

-- telegram_questions
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

-- telegram_event_requests
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

-- resource_downloads
CREATE TABLE IF NOT EXISTS resource_downloads (
    user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    resource_slug VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    downloads INT UNSIGNED NOT NULL DEFAULT 1,
    last_download_at DATETIME NOT NULL,
    PRIMARY KEY (user_id,resource_slug),
    CONSTRAINT fk_resource_download_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Populate profiles only for users without an existing member profile.
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

-- Mark completed migrations without duplicating existing markers.
INSERT IGNORE INTO migrations (migration,executed_at) VALUES
('001_core.mysql.sql',UTC_TIMESTAMP()),
('002_content_studio.mysql.sql',UTC_TIMESTAMP()),
('003_therapist_circle.mysql.sql',UTC_TIMESTAMP()),
('004_member_profiles.mysql.sql',UTC_TIMESTAMP()),
('005_telegram_assistant.mysql.sql',UTC_TIMESTAMP());
