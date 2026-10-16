-- Mentoris complete current schema | 2026-10-16 (49 tables)
-- Import in phpMyAdmin AFTER selecting this site's existing database.
-- Every table is created only if absent. No tables or records are deleted.
-- Intentional updates: first circle completed; third circle survey on 2026-10-16 18:00-23:00 Tehran.
-- Reimport resets this specific survey window; subsequent edits can be made in /admin/feedback.
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

CREATE TABLE IF NOT EXISTS feedback_windows (
    event_slug VARCHAR(190) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    updated_by CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_feedback_window_editor FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- No existing user data or columns are changed.
CREATE TABLE IF NOT EXISTS phone_verifications (
    user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
    phone VARCHAR(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
    verified_at DATETIME NOT NULL,
    CONSTRAINT fk_verified_phone_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sms_challenges (
    user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
    challenge_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    phone_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    code_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    expires_at DATETIME NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    status VARCHAR(16) NOT NULL,
    sent_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_sms_challenge_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sms_deliveries (
    id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
    user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL,
    phone_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    kind VARCHAR(32) NOT NULL DEFAULT 'phone_otp',
    status VARCHAR(16) NOT NULL,
    provider_message_id BIGINT UNSIGNED NULL,
    provider_code INT NULL,
    cost DECIMAL(14,4) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_sms_created (created_at),
    CONSTRAINT fk_sms_delivery_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mark completed migrations without duplicating existing markers.
INSERT IGNORE INTO migrations (migration,executed_at) VALUES
('001_core.mysql.sql',UTC_TIMESTAMP()),
('002_content_studio.mysql.sql',UTC_TIMESTAMP()),
('003_therapist_circle.mysql.sql',UTC_TIMESTAMP()),
('004_member_profiles.mysql.sql',UTC_TIMESTAMP()),
('005_telegram_assistant.mysql.sql',UTC_TIMESTAMP()),
('006_feedback_windows.mysql.sql',UTC_TIMESTAMP()),
('007_sms_verification.mysql.sql',UTC_TIMESTAMP());

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

INSERT IGNORE INTO migrations (migration,executed_at) VALUES ('008_registration_sms.mysql.sql',UTC_TIMESTAMP());

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

INSERT IGNORE INTO migrations (migration,executed_at) VALUES ('009_course_requests.mysql.sql',UTC_TIMESTAMP());
-- Mentoris content release 2026-10-12. Requires migration 002.
-- Repeatable: existing content and administrator edits are preserved.
SET NAMES utf8mb4;
START TRANSACTION;

-- maryam-haghani
INSERT IGNORE INTO content_entities (id,entity_type,slug,status,sort_order,author_id,published_at,created_at,updated_at) VALUES ('1c568cda7850bc67d167cc595c41599b',CONVERT(0x6d656e746f72 USING utf8mb4),CONVERT(0x6d617279616d2d68616768616e69 USING utf8mb4),'published',0,NULL,UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP());
INSERT IGNORE INTO content_translations (id,entity_id,locale,title,subtitle,excerpt,body,metadata,created_at,updated_at) SELECT SUBSTRING(SHA2(CONCAT(ce.id,'|fa'),256),1,32),ce.id,'fa',CONVERT(0xd8afdaa9d8aad8b120d985d8b1db8cd98520d8add982d8a7d986db8c USING utf8mb4),CONVERT(0xd8a8d986db8cd8a7d986e2808cdaafd8b0d8a7d8b120d98820d985d8afdb8cd8b120d8a2daa9d8a7d8afd985db8c20d985d986d8aad988d8b1db8cd8b3 USING utf8mb4),CONVERT(0xd9beda98d988d987d8b4daafd8b120d98820d985d8afd8b1d8b320d8add988d8b2d98720d8b1d988d8a7d986e2808cd8b4d986d8a7d8b3db8c20d8a8d8a720d8aad985d8b1daa9d8b220d8a8d8b120d9bedb8cd988d986d8af20d8afd8a7d986d8b420d8b9d984d985db8cd88c20d9beda98d988d987d8b4d88c20d8b9d984d988d98520d8b4d986d8a7d8aed8aadb8c20d98820d8a2d985d988d8b2d8b420d8aad8aed8b5d8b5db8c2e USING utf8mb4),CONVERT(0xd8afdaa9d8aad8b120d985d8b1db8cd98520d8add982d8a7d986db8c20d9beda98d988d987d8b4daafd8b120d98820d985d8afd8b1d8b320d8add988d8b2d98720d8b1d988d8a7d986e2808cd8b4d986d8a7d8b3db8c20d8a7d8b3d8aa20daa9d98720d985d8b3db8cd8b120d8add8b1d981d987e2808cd8a7db8c20d8aed988d8af20d8b1d8a720d8afd8b120d986d982d8b7d98720d8aad984d8a7d982db8c20d8b1d988d8a7d986e2808cd8b4d986d8a7d8b3db8cd88c20d9beda98d988d987d8b4d88c20d8b9d984d988d98520d8b4d986d8a7d8aed8aadb8c20d98820d8a2d985d988d8b2d8b420d8aad8aed8b5d8b5db8c20d8afd986d8a8d8a7d98420daa9d8b1d8afd98720d8a7d8b3d8aa2e0a0ad8a7d98820d8afd8b120d8b7d988d98420d985d8b3db8cd8b120d8afd8a7d986d8b4daafd8a7d987db8c20d98820d8add8b1d981d987e2808cd8a7db8c20d8aed988d8af20d8afd8b120d8add988d8b2d987e2808cd987d8a7db8cdb8c20d8a7d8b220d8acd985d984d98720d9beda98d988d987d8b4e2808cd987d8a7db8c20d8b1d988d8a7d986e2808cd8b4d986d8a7d8aed8aadb8cd88c20daa9d8a7d8b120d8a8d8a720d8b1d988d8b4e2808cd987d8a7db8c20daa9d985db8c20d98820d8b4d986d8a7d8aed8aadb8cd88c20d986d988d8b1d988d985d8afd988d984d8a7d8b3db8cd988d98620d98820d8aad988d8b3d8b9d98720d985d987d8a7d8b1d8aae2808cd987d8a7db8c20d8aad8aed8b5d8b5db8c20d981d8b9d8a7d984db8cd8aa20d8afd8a7d8b4d8aad98720d98820d987d985d988d8a7d8b1d98720d8a8d8b120d8a7d8b1d8aad8a8d8a7d8b720d985db8cd8a7d98620d8afd8a7d986d8b420d8b9d984d985db8c20d98820daa9d8a7d8b1d8a8d8b1d8af20d8add8b1d981d987e2808cd8a7db8c20d8aad8a3daa9db8cd8af20d8afd8a7d8b4d8aad98720d8a7d8b3d8aa2e0a0ad8a7db8cd8afd98720d8b4daa9d984e2808cdaafdb8cd8b1db8c20d985d986d8aad988d8b1db8cd8b320d8a7d8b220db8cdaa920d8afd8bad8afd8bad98720d8b3d8a7d8afd98720d8a7d985d8a720d8acd8afdb8c20d8a2d8bad8a7d8b220d8b4d8af3a20da86daafd988d986d98720d985db8ce2808cd8aad988d8a7d98620d985d8b3db8cd8b120d8b1d8b4d8af20db8cdaa920d985d8aad8aed8b5d8b520d8b1d988d8a7d986e2808cd8b4d986d8a7d8b3db8c20d8b1d8a720d8a7d8b220d8a2d985d988d8b2d8b4e2808cd987d8a7db8c20d9bed8b1d8a7daa9d986d8afd98720d981d8b1d8a7d8aad8b120d8a8d8b1d8af20d98820d8a8d98720d985d8b3db8cd8b1db8c20d985d8b3d8aad985d8b1d88c20d8b9d984d985db8c20d98820d8add8b1d981d987e2808cd8a7db8c20d8aad8a8d8afdb8cd98420daa9d8b1d8afd89f0a0ad985d986d8aad988d8b1db8cd8b320d8add8a7d8b5d98420d987d985db8cd98620d8afd8bad8afd8bad98720d98820d8aad984d8a7d8b4db8c20d8a8d8b1d8a7db8c20d8a7db8cd8acd8a7d8af20d8a8d8b3d8aad8b1db8c20d8aad8a7d8b2d98720d8a8d8b1d8a7db8c20db8cd8a7d8afdaafdb8cd8b1db8cd88c20d8a7d8b1d8aad8a8d8a7d8b7d88c20d8b1d8b4d8af20d98820d8aad988d8b3d8b9d98720d8add8b1d981d987e2808cd8a7db8c20d985d8aad8aed8b5d8b5d8a7d98620d8b1d988d8a7d986e2808cd8b4d986d8a7d8b3db8c20d98820d8b3d984d8a7d985d8aa20d8b1d988d8a7d98620d8a7d8b3d8aa2e USING utf8mb4),CONVERT(0x7b22696d616765223a22696d616765735c2f666f756e6465722d6d617279616d2d68616768616e692d76312e706e67222c22746f6e65223a2273616765227d USING utf8mb4),UTC_TIMESTAMP(),UTC_TIMESTAMP() FROM content_entities ce WHERE ce.entity_type=CONVERT(0x6d656e746f72 USING utf8mb4) AND ce.slug=CONVERT(0x6d617279616d2d68616768616e69 USING utf8mb4);

-- ali-feizi
INSERT IGNORE INTO content_entities (id,entity_type,slug,status,sort_order,author_id,published_at,created_at,updated_at) VALUES ('e4a39860a35e8e54c43e05fb8e99cf30',CONVERT(0x6d656e746f72 USING utf8mb4),CONVERT(0x616c692d6665697a69 USING utf8mb4),'published',1,NULL,UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP());
INSERT IGNORE INTO content_translations (id,entity_id,locale,title,subtitle,excerpt,body,metadata,created_at,updated_at) SELECT SUBSTRING(SHA2(CONCAT(ce.id,'|fa'),256),1,32),ce.id,'fa',CONVERT(0xd8afdaa9d8aad8b120d8b9d984db8c20d981db8cd8b6db8c USING utf8mb4),CONVERT(0xd8b1d988d8a7d986e2808cd8b4d986d8a7d8b320d8b3d984d8a7d985d8aad88c20d8b1d988d8a7d986e2808cd8afd8b1d985d8a7d986daafd8b120d98820d985d8afd8b1d8b3 USING utf8mb4),CONVERT(0xd8aad985d8b1db8cd98620d8b9d8a7d985d8afd8a7d986d987d88c20d8afd8b1d985d8a7d98620d985d8a8d8aad986db8c20d8a8d8b120d9bed8b0db8cd8b1d8b420d98820d8aad8b9d987d8af20d98820d9bedb8cd988d986d8af20d9beda98d988d987d8b420d8a8d8a720d8b9d985d98420d8a8d8a7d984db8cd986db8c2e USING utf8mb4),CONVERT(0xd8afdaa9d8aad8b120d8b9d984db8c20d981db8cd8b6db8c20d8b1d988d8a7d986e2808cd8b4d986d8a7d8b320d8b3d984d8a7d985d8aad88c20d8b1d988d8a7d986e2808cd8afd8b1d985d8a7d986daafd8b120d98820d985d8afd8b1d8b320d8b1d988d8a7d986e2808cd8b4d986d8a7d8b3db8c20d8a7d8b3d8aa2e20d8a8d8aed8b420d8b9d985d8afd98720d981d8b9d8a7d984db8cd8aa20d8add8b1d981d987e2808cd8a7db8c20d8a7d98820d8a8d8b120d8b1d988d8a7d986e2808cd8afd8b1d985d8a7d986db8cd88c20d8a2d985d988d8b2d8b420d8afd8b1d985d8a7d986daafd8b1d8a7d98620d98820d8a8d8b1d8b1d8b3db8c20d8b9d984d985db8c20d8b9d988d8a7d985d98420d985d8a4d8abd8b120d8a8d8b120d8a7d8abd8b1d8a8d8aed8b4db8c20d8afd8b1d985d8a7d98620d98820d8aad8a8d8add8b120d8add8b1d981d987e2808cd8a7db8c20d8afd8b1d985d8a7d986daafd8b120d985d8aad985d8b1daa9d8b220d8a7d8b3d8aa2e0a0ad8a7d98820d8afd8a7d986d8b4e2808cd8a2d985d988d8aed8aad98720d8afdaa9d8aad8b1db8c20d8b1d988d8a7d986e2808cd8b4d986d8a7d8b3db8c20d8b3d984d8a7d985d8aa20d8a7d8b220d8afd8a7d986d8b4daafd8a7d98720d8aad987d8b1d8a7d98620d8a7d8b3d8aa20d98820d8a8db8cd8b420d8a7d8b220db8cdaa920d8afd987d98720d8b3d8a7d8a8d982d98720d981d8b9d8a7d984db8cd8aa20d8a8d8a7d984db8cd986db8c20d8afd8a7d8b1d8af2e20d8afd8b120d8b2d985db8cd986d98720d8afd8b1d985d8a7d98620d985d8a8d8aad986db8c20d8a8d8b120d9bed8b0db8cd8b1d8b420d98820d8aad8b9d987d8afd88c20d8b1d988db8cdaa9d8b1d8afd987d8a7db8c20d981d8b1d8a7db8cd986d8afd985d8add988d8b1d88c20d8afd8b1d985d8a7d98620d8a2daafd8a7d987db8ce2808cdb8cd8a7d981d8aad98720d8a7d8b220d8a8d8a7d8b2d8aed988d8b1d8af20d98820d8aad985d8b1db8cd98620d8b9d8a7d985d8afd8a7d986d98720d8a8d98720d985d8b7d8a7d984d8b9d98720d98820d8a2d985d988d8b2d8b420d8aad8aed8b5d8b5db8c20d9bed8b1d8afd8a7d8aed8aad98720d98820da86d986d8afdb8cd98620d8a7d8abd8b120d8aad8aed8b5d8b5db8c20d8b1d988d8a7d986e2808cd8afd8b1d985d8a7d986db8c20d8b1d8a720d8a8d98720d981d8a7d8b1d8b3db8c20d8aad8b1d8acd985d98720daa9d8b1d8afd98720d8a7d8b3d8aa2e0a0ad8afd8b120daa9d8a7d8b120d8a2d985d988d8b2d8b4db8c20d8a7d988d88c20d9bedb8cd988d986d8af20d9beda98d988d987d8b420d8a8d8a720d8b9d985d98420d8a8d8a7d984db8cd986db8cd88c20d8aad981daa9d8b120d986d982d8a7d8afd8a7d986d98720d98820d8a7db8cd98620d9bed8b1d8b3d8b420d8acd8a7db8cdaafd8a7d98720d988db8cda98d987e2808cd8a7db8c20d8afd8a7d8b1d8af3a20d8afd8b1d985d8a7d986daafd8b120d985d8a4d8abd8b120da86d98720daa9d8a7d8b1db8c20d8b1d8a720d985d8aad981d8a7d988d8aa20d8a7d986d8acd8a7d98520d985db8ce2808cd8afd987d8afd89f USING utf8mb4),CONVERT(0x7b22696d616765223a22696d616765735c2f616c692d6665697a692d76312e6a7067222c22746f6e65223a2273616765222c2272656c617465645f636f7572736573223a5b2270737963686f746865726170792d6d6173746572792d64656c696265726174652d7072616374696365225d7d USING utf8mb4),UTC_TIMESTAMP(),UTC_TIMESTAMP() FROM content_entities ce WHERE ce.entity_type=CONVERT(0x6d656e746f72 USING utf8mb4) AND ce.slug=CONVERT(0x616c692d6665697a69 USING utf8mb4);

-- hanieh-haghani
INSERT IGNORE INTO content_entities (id,entity_type,slug,status,sort_order,author_id,published_at,created_at,updated_at) VALUES ('293d4457facdc99bf8dc9f9054497def',CONVERT(0x6d656e746f72 USING utf8mb4),CONVERT(0x68616e6965682d68616768616e69 USING utf8mb4),'published',2,NULL,UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP());
INSERT IGNORE INTO content_translations (id,entity_id,locale,title,subtitle,excerpt,body,metadata,created_at,updated_at) SELECT SUBSTRING(SHA2(CONCAT(ce.id,'|fa'),256),1,32),ce.id,'fa',CONVERT(0xd987d8a7d986db8cd98720d8add982d8a7d986db8c USING utf8mb4),CONVERT(0xd9beda98d988d987d8b4daafd8b120d98820d8afd8a7d986d8b4d8acd988db8c20d8afdaa9d8aad8b1db8c20d8b1d988d8a7d986e2808cd8b4d986d8a7d8b3db8c20d8aad8b1d8a8db8cd8aadb8c USING utf8mb4),CONVERT(0xd985d8b4d8a7d988d8b1d98720d8aad8add8b5db8cd984db8c20d8a8d8a720d8b1d988db8cdaa9d8b1d8af2041435420d98820d8a2d985d988d8b2d8b420d8b1d988d8b4e2808cd987d8a7db8c20d9beda98d988d987d8b420daa9db8cd981db8c2e USING utf8mb4),CONVERT(0xd987d8a7d986db8cd98720d8add982d8a7d986db8c20d9beda98d988d987d8b4daafd8b120d8add988d8b2d98720d8b1d988d8a7d986e2808cd8b4d986d8a7d8b3db8c20d8aad8b1d8a8db8cd8aadb8c20d8a7d8b3d8aa2e20d8a7d98820daa9d8a7d8b1d8b4d986d8a7d8b3db8c20d8a7d8b1d8b4d8af20d8aed988d8af20d8b1d8a720d8afd8b120d8afd8a7d986d8b4daafd8a7d98720d8b4d987db8cd8af20d8a8d987d8b4d8aadb8c20daafd8b0d8b1d8a7d986d8afd98720d98820d8a8d987e2808cd8b9d986d988d8a7d98620d8afd8a7d986d8b4d8acd988db8c20d8afdaa9d8aad8b1db8c20d8afd8b120d8afd8a7d986d8b4daafd8a7d98720d8a7d984d8b2d987d8b1d8a720d8a8d98720d9beda98d988d987d8b420d98820d981d8b9d8a7d984db8cd8aa20d8afd8a7d986d8b4daafd8a7d987db8c20d985d8b4d8bad988d98420d8a7d8b3d8aa2e0a0ad8aad985d8b1daa9d8b220d8add8b1d981d987e2808cd8a7db8c20d8a7d98820d8a8d8b120d9bedb8cd988d986d8af20d8afd8a7d986d8b420d8b1d988d8a7d986e2808cd8b4d986d8a7d8b3db8c20d8a8d8a720d986db8cd8a7d8b2d987d8a7db8c20d8a2d985d988d8b2d8b4db8c20d8a7d8b3d8aa2e20d8afd8b120d985d8b4d8a7d988d8b1d98720d8aad8add8b5db8cd984db8c20d98820d8a7d986daafdb8cd8b2d8b4db8cd88c20d8a7d8b220d8b1d988db8cdaa9d8b1d8af20d8afd8b1d985d8a7d98620d985d8a8d8aad986db8c20d8a8d8b120d9bed8b0db8cd8b1d8b420d98820d8aad8b9d987d8af20284143542920d8a8d8b1d8a7db8c20d8b4d986d8a7d8aed8aa20da86d8a7d984d8b4e2808cd987d8a7db8c20d8afd8b1d988d986db8c20d98820d8b1d988d8b4d986e2808cd8aad8b120d8b4d8afd98620d985d8b3db8cd8b120d8aad8add8b5db8cd984db8c20d985d8b1d8a7d8acd8b9d8a7d98620d8a7d8b3d8aad981d8a7d8afd98720d985db8ce2808cdaa9d986d8af2e0a0ad8aad988d8b3d8b9d98720d98820d8a2d985d988d8b2d8b420d8b1d988d8b4e2808cd987d8a7db8c20d9beda98d988d987d8b420daa9db8cd981db8cd88c20d8a8d8aed8b420d8afdb8cdaafd8b1db8c20d8a7d8b220d8afd8bad8afd8bad987e2808cd987d8a720d98820d981d8b9d8a7d984db8cd8aae2808cd987d8a7db8c20d8b9d984d985db8c20d8a7d988d8b3d8aa2e USING utf8mb4),CONVERT(0x7b22696d616765223a22696d616765735c2f68616e6965682d68616768616e692d76312e6a7067222c22746f6e65223a22626c7565227d USING utf8mb4),UTC_TIMESTAMP(),UTC_TIMESTAMP() FROM content_entities ce WHERE ce.entity_type=CONVERT(0x6d656e746f72 USING utf8mb4) AND ce.slug=CONVERT(0x68616e6965682d68616768616e69 USING utf8mb4);

-- therapists-circle-tabriz
INSERT IGNORE INTO content_entities (id,entity_type,slug,status,sort_order,author_id,published_at,created_at,updated_at) VALUES ('93cb5465dd8f0e76660df3d6d362619b',CONVERT(0x6576656e74 USING utf8mb4),CONVERT(0x746865726170697374732d636972636c652d74616272697a USING utf8mb4),'published',20,NULL,UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP());
INSERT IGNORE INTO content_translations (id,entity_id,locale,title,subtitle,excerpt,body,metadata,created_at,updated_at) SELECT SUBSTRING(SHA2(CONCAT(ce.id,'|fa'),256),1,32),ce.id,'fa',CONVERT(0xd8add984d982d98720d8afd8b1d985d8a7d986daafd8b1d8a7d986207c20d8a7d988d984db8cd98620daafd8b1d8afd987d985d8a7db8cdb8c20d8aad8aed8b5d8b5db8c20d985d986d8aad988d8b1db8cd8b3 USING utf8mb4),CONVERT(0xdaafd8b1d8afd987d985d8a7db8cdb8c20d8aad8aed8b5d8b5db8c USING utf8mb4),CONVERT(0xd8a2d8bad8a7d8b220d8add984d982d987e2808cd987d8a7db8c20d8aad8aed8b5d8b5db8c20d985d986d8aad988d8b1db8cd8b3d89b20daafd981d8aae2808cd988daafd988d88c20d8aad8a8d8a7d8afd98420d8aad8acd8b1d8a8d98720d98820d8a7d8b1d8aad8a8d8a7d8b720d8add8b1d981d987e2808cd8a7db8c20d8afd8b120d8aad8a8d8b1db8cd8b22e USING utf8mb4),CONVERT(0xd986d8aed8b3d8aadb8cd98620d8add984d982d98720d8afd8b1d985d8a7d986daafd8b1d8a7d98620d985d986d8aad988d8b1db8cd8b320d8a8d8a720d987d8afd98120d8a7db8cd8acd8a7d8af20d981d8b6d8a7db8cdb8c20d8a8d8b1d8a7db8c20daafd981d8aae2808cd988daafd988db8c20d8add8b1d981d987e2808cd8a7db8c20d98820d8a7d8b1d8aad8a8d8a7d8b720d985db8cd8a7d98620d987d985daa9d8a7d8b1d8a7d98620d8a8d8b1daafd8b2d8a7d8b120d8b4d8af2e USING utf8mb4),CONVERT(0x7b22696d616765223a22696d616765735c2f746865726170697374732d636972636c652d706f737465722d76312e6a7067222c226576656e745f737461747573223a22636f6d706c65746564222c226d6f6465223a226f66666c696e65222c22646973706c61795f64617465223a22d8acd985d8b9d98720dbb2dbb720d8b4d987d8b1db8cd988d8b120dbb1dbb4dbb0dbb5222c227374617274735f6174223a22323032362d30392d31385431383a30303a30302b30333a3330222c22656e64735f6174223a22323032362d30392d31385432303a30303a30302b30333a3330222c22646179223a22dbb2dbb7222c226d6f6e7468223a22d8b4d987d8b1db8cd988d8b1222c2274696d65223a22dbb1dbb820d8aad8a720dbb2dbb0222c226c6f636174696f6e223a22d8aad8a8d8b1db8cd8b2222c226c696e655f736c7567223a227468657261706973742d646576656c6f706d656e74222c22696e7374727563746f725f736c7567223a226d617279616d2d68616768616e69227d USING utf8mb4),UTC_TIMESTAMP(),UTC_TIMESTAMP() FROM content_entities ce WHERE ce.entity_type=CONVERT(0x6576656e74 USING utf8mb4) AND ce.slug=CONVERT(0x746865726170697374732d636972636c652d74616272697a USING utf8mb4);

-- therapists-circle-second
INSERT IGNORE INTO content_entities (id,entity_type,slug,status,sort_order,author_id,published_at,created_at,updated_at) VALUES ('20fc3b6c84516780ef6412e29d168c37',CONVERT(0x6576656e74 USING utf8mb4),CONVERT(0x746865726170697374732d636972636c652d7365636f6e64 USING utf8mb4),'published',10,NULL,UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP());
INSERT IGNORE INTO content_translations (id,entity_id,locale,title,subtitle,excerpt,body,metadata,created_at,updated_at) SELECT SUBSTRING(SHA2(CONCAT(ce.id,'|fa'),256),1,32),ce.id,'fa',CONVERT(0xd8afd988d985db8cd98620d8add984d982d98720d8afd8b1d985d8a7d986daafd8b1d8a7d98620d985d986d8aad988d8b1db8cd8b3 USING utf8mb4),CONVERT(0xd984d986daafd8b1daafd8a7d98720d8a7d985d98620d8afd8b1d985d8a7d986daafd8b1 USING utf8mb4),CONVERT(0xdaafd981d8aae2808cd988daafd988db8cdb8c20d8afd8b1d8a8d8a7d8b1d98720d8aad985d8b1db8cd98620d8b9d8a7d985d8afd8a7d986d987d88c20d984d986daafd8b1d8a7d986d8afd8a7d8b2db8c20d98820d985d8b1d8a7d982d8a8d8aa20d8a7d8b220d8afd8b1d985d8a7d986daafd8b120d8afd8b120d985d8b3db8cd8b120d8a8d8a7d984db8cd986db8c2e USING utf8mb4),CONVERT(0xd986d8b4d8b3d8aa20d8afd988d98520d8add984d982d98720d8afd8b1d985d8a7d986daafd8b1d8a7d98620d8a8d8a720d985d8add988d8b120d8aad985d8b1db8cd98620d8b9d8a7d985d8afd8a7d986d987d88c20d984d986daafd8b1d8a7d986d8afd8a7d8b2db8c20d98820da86d8a7d984d8b4e2808cd987d8a7db8c20d988d8a7d982d8b9db8c20daa9d8a7d8b120d8a8d8a7d984db8cd986db8c20d8a8d8b1daafd8b2d8a7d8b120d8b4d8af2e20d8abd8a8d8aae2808cd986d8a7d98520d8a7db8cd98620d986d8b4d8b3d8aa20d9bed8a7db8cd8a7d98620db8cd8a7d981d8aad98720d8a7d8b3d8aa2e USING utf8mb4),CONVERT(0x7b22696d616765223a22696d616765735c2f746865726170697374732d636972636c652d7365636f6e642d76312e6a7067222c226576656e745f737461747573223a22636f6d706c65746564222c226d6f6465223a226f66666c696e65222c22646973706c61795f64617465223a22d8a8d8b1daafd8b2d8a7d8b120d8b4d8afd987222c2274696d65223a22d8b3d8a7d8b9d8aa20dbb1dbb8222c226c6f636174696f6e223a22d8a8d8a7d8b4daafd8a7d98720d981d8b1d987d986daafdb8cd8a7d98620d8aad8a8d8b1db8cd8b2222c226c696e655f736c7567223a227468657261706973742d646576656c6f706d656e74222c22696e7374727563746f725f736c7567223a226d617279616d2d68616768616e69222c22686967686c6967687473223a5b22d8aad985d8b1db8cd98620d8b9d8a7d985d8afd8a7d986d98720d8afd8b120daa9d8a7d8b120d8a8d8a7d984db8cd986db8c222c22d984d986daafd8b1d8a7d986d8afd8a7d8b2db8c20d98820d985d8afd984204752414345222c22daafd981d8aae2808cd988daafd988db8c20d8add8b1d981d987e2808cd8a7db8c20d985db8cd8a7d98620d987d985daa9d8a7d8b1d8a7d986225d7d USING utf8mb4),UTC_TIMESTAMP(),UTC_TIMESTAMP() FROM content_entities ce WHERE ce.entity_type=CONVERT(0x6576656e74 USING utf8mb4) AND ce.slug=CONVERT(0x746865726170697374732d636972636c652d7365636f6e64 USING utf8mb4);
UPDATE content_translations ct JOIN content_entities ce ON ce.id=ct.entity_id SET ct.metadata=JSON_SET(COALESCE(ct.metadata,JSON_OBJECT()),'$.image',CONVERT(0x696d616765732f746865726170697374732d636972636c652d7365636f6e642d76312e6a7067 USING utf8mb4)),ct.updated_at=UTC_TIMESTAMP() WHERE ce.entity_type=CONVERT(0x6576656e74 USING utf8mb4) AND ce.slug=CONVERT(0x746865726170697374732d636972636c652d7365636f6e64 USING utf8mb4) AND ct.locale='fa' AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(ct.metadata,'$.image')),'')='';

-- therapists-circle-third
INSERT IGNORE INTO content_entities (id,entity_type,slug,status,sort_order,author_id,published_at,created_at,updated_at) VALUES ('e62ff77469398d48762d9b1d43deb1a4',CONVERT(0x6576656e74 USING utf8mb4),CONVERT(0x746865726170697374732d636972636c652d7468697264 USING utf8mb4),'published',-10,NULL,UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP());
INSERT IGNORE INTO content_translations (id,entity_id,locale,title,subtitle,excerpt,body,metadata,created_at,updated_at) SELECT SUBSTRING(SHA2(CONCAT(ce.id,'|fa'),256),1,32),ce.id,'fa',CONVERT(0xd8b3d988d985db8cd98620d8add984d982d98720d8afd8b1d985d8a7d986daafd8b1d8a7d98620d985d986d8aad988d8b1db8cd8b3 USING utf8mb4),CONVERT(0xd8afd8b1d985d8a7d986daafd8b120d8afd8b1d985d8a7d986daafd8b1d8a7d986 USING utf8mb4),CONVERT(0xd985d8a720d8a8d8b1d8a7db8c20d8a8d987d8b2db8cd8b3d8aadb8c20d98820d985d8b1d8a7d982d8a8d8aa20d8a7d8b220d8aed988d8af20da86d98720d985db8ce2808cdaa9d986db8cd985d89f20daafd981d8aae2808cd988daafd988db8cdb8c20d8afd8b1d8a8d8a7d8b1d98720d985d8b1d8a7d982d8a8d8aa20d8a7d8b220d8afd8b1d985d8a7d986daafd8b1d88c20d985d8b1d8b2d987d8a7db8c20d8add8b1d981d987e2808cd8a7db8c20d98820d987d985d8b1d8a7d987db8c20d987d985daa9d8a7d8b1d8a7d9862e USING utf8mb4),CONVERT(0xd8afd8b120d8b3d988d985db8cd98620d8add984d982d98720d8afd8b1d985d8a7d986daafd8b1d8a7d98620d985d986d8aad988d8b1db8cd8b3d88c20d8afd8b1d8a8d8a7d8b1d98720d8aad8acd8b1d8a8d987e2808cd987d8a7db8c20d988d8a7d982d8b9db8c20d985d8b1d8a7d982d8a8d8aa20d8a7d8b220d8aed988d8af20d98820d8a8d987d8b2db8cd8b3d8aadb8c20d8afd8b120d985d8b3db8cd8b120d8afd8b1d985d8a7d986daafd8b1db8c20daafd981d8aae2808cd988daafd98820d985db8ce2808cdaa9d986db8cd9852e20d8a7db8cd98620d986d8b4d8b3d8aa20d981d8b6d8a7db8cdb8c20d8a8d8b1d8a7db8c20d8b4d986db8cd8afd98620d8aad8acd8b1d8a8d98720d987d985daa9d8a7d8b1d8a7d986d88c20d8aad8a3d985d98420d8afd8b1d8a8d8a7d8b1d98720d986db8cd8a7d8b2d987d8a7db8c20d8add8b1d981d987e2808cd8a7db8c20d98820d8b4daa9d984e2808cd8afd8a7d8afd98620d8a8d98720d8a7d8b1d8aad8a8d8a7d8b7e2808cd987d8a7db8c20d8add985d8a7db8cd8aadb8c20d8a7d8b3d8aa2e20d8afd8b1d8aed988d8a7d8b3d8aae2808cd987d8a720d8a8d8b1d8b1d8b3db8c20d985db8ce2808cd8b4d988d986d8af20d98820d8abd8a8d8aae2808cd986d8a7d98520d9bed8b320d8a7d8b220d8aad8a3db8cdb8cd8af20d8aadb8cd98520d985d986d8aad988d8b1db8cd8b320d986d987d8a7db8cdb8c20d8aed988d8a7d987d8af20d8b4d8af2e USING utf8mb4),CONVERT(0x7b22696d616765223a22696d616765735c2f746865726170697374732d636972636c652d74686972642d76312e6a7067222c226576656e745f737461747573223a22726567697374726174696f6e2d6f70656e222c226d6f6465223a226f66666c696e65222c22666f726d6174223a226f66666c696e65222c22646973706c61795f64617465223a22d8acd985d8b9d98720dbb2dbb420d985d987d8b120dbb1dbb4dbb0dbb5222c227374617274735f6174223a22323032362d31302d31365431373a30303a30302b30333a3330222c22656e64735f6174223a22323032362d31302d31365431393a30303a30302b30333a3330222c22646179223a22dbb2dbb4222c226d6f6e7468223a22d985d987d8b1222c2274696d65223a22dbb1dbb720d8aad8a720dbb1dbb9222c226c6f636174696f6e223a22d9bed8b320d8a7d8b220d8aad8a3db8cdb8cd8af20d8abd8a8d8aae2808cd986d8a7d98520d8a7d8b7d984d8a7d8b920d8afd8a7d8afd98720d8aed988d8a7d987d8af20d8b4d8af222c226c6f636174696f6e5f70656e64696e67223a747275652c2270726963655f616d6f756e74223a3630303030302c226361706163697479223a302c226c696e655f736c7567223a227468657261706973742d646576656c6f706d656e74222c22696e7374727563746f725f736c7567223a226d617279616d2d68616768616e69222c22726567697374726174696f6e5f6e6f7465223a22d987d8b2db8cd986d98720d8add8b6d988d8b120dbb6dbb0dbb0d9acdbb0dbb0dbb020d8aad988d985d8a7d98620d8a7d8b3d8aa2e20d8abd8a8d8aa20d981d8b1d98520d8a8d98720d985d8b9d986d8a7db8c20d8aad8a3db8cdb8cd8af20db8cd8a720d9bed8b1d8afd8a7d8aed8aa20d986db8cd8b3d8aad89b20d8afd8b1d8aed988d8a7d8b3d8aa20d8b4d985d8a720d8a8d8b1d8b1d8b3db8c20d985db8ce2808cd8b4d988d8af20d98820d9bed8b320d8a7d8b220d8aad8a3db8cdb8cd8afd88c20d8acd8b2d8a6db8cd8a7d8aa20d8aadaa9d985db8cd98420d8abd8a8d8aae2808cd986d8a7d98520d98820d985daa9d8a7d98620d8a7d8b9d984d8a7d98520d8aed988d8a7d987d8af20d8b4d8af2e222c22686967686c6967687473223a5b22d8a8d987d8b2db8cd8b3d8aadb8c20d98820d985d8b1d8a7d982d8a8d8aa20d8a7d8b220d8aed988d8afd99020d8afd8b1d985d8a7d986daafd8b1222c22daafd981d8aae2808cd988daafd988db8c20d8add8b1d981d987e2808cd8a7db8c20d98820d8aad8a8d8a7d8afd98420d8aad8acd8b1d8a8d987222c22d8abd8a8d8aa20d8afd8b1d8aed988d8a7d8b3d8aad88c20d8a8d8b1d8b1d8b3db8c20d98820d8aad8a3db8cdb8cd8af20d8aadb8cd98520d985d986d8aad988d8b1db8cd8b3225d2c22726567697374726174696f6e5f75726c223a22222c226665617475726564223a747275657d USING utf8mb4),UTC_TIMESTAMP(),UTC_TIMESTAMP() FROM content_entities ce WHERE ce.entity_type=CONVERT(0x6576656e74 USING utf8mb4) AND ce.slug=CONVERT(0x746865726170697374732d636972636c652d7468697264 USING utf8mb4);

-- psychotherapy-mastery-deliberate-practice
INSERT IGNORE INTO content_entities (id,entity_type,slug,status,sort_order,author_id,published_at,created_at,updated_at) VALUES ('dca1ff29fe87eeef5cdcb2ea370d51c3',CONVERT(0x636f75727365 USING utf8mb4),CONVERT(0x70737963686f746865726170792d6d6173746572792d64656c696265726174652d7072616374696365 USING utf8mb4),'published',-10,NULL,UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP());
INSERT IGNORE INTO content_translations (id,entity_id,locale,title,subtitle,excerpt,body,metadata,created_at,updated_at) SELECT SUBSTRING(SHA2(CONCAT(ce.id,'|fa'),256),1,32),ce.id,'fa',CONVERT(0xd8aad8a8d8add8b120d8afd8b120d8b1d988d8a7d986e2808cd8afd8b1d985d8a7d986db8c3a20d8aad985d8b1db8cd98620d8b9d8a7d985d8afd8a7d986d98720d8a8d8b1d8a7db8c20d8aad988d8b3d8b9d98720d985d987d8a7d8b1d8aae2808cd987d8a7db8c20d8afd8b1d985d8a7d986daafd8b1 USING utf8mb4),CONVERT(0xd8a7d8b220d8aad8acd8b1d8a8d98720d8aad8a720d985d987d8a7d8b1d8aa20d982d8a7d8a8d984e2808cd8aadaa9d8b1d8a7d8b1 USING utf8mb4),CONVERT(0xdaa9d8a7d8b1daafd8a7d987db8c20daa9d8a7d8b1d8a8d8b1d8afdb8c20d8a8d8b1d8a7db8c20d8b3d986d8acd8b420d8b9d985d984daa9d8b1d8afd88c20d8b7d8b1d8a7d8addb8c20d8aad985d8b1db8cd986d88c20d8a7d8b3d8aad981d8a7d8afd98720d8a7d8b220d8a8d8a7d8b2d8aed988d8b1d8af20d98820d8b3d8a7d8aed8aad98620d8a8d8b1d986d8a7d985d98720d8b1d8b4d8af20d8add8b1d981d987e2808cd8a7db8c20d8afd8b1d985d8a7d986daafd8b12e USING utf8mb4),CONVERT(0xd8afd8b120d8a7db8cd98620daa9d8a7d8b1daafd8a7d987d88c20d985d8b3db8cd8b120d8b1d8b4d8af20d8add8b1d981d987e2808cd8a7db8c20d8b1d8a720d8a7d8b220d8b4d986d8a7d8aed8aa20d8b9d985d984daa9d8b1d8af20d981d8b9d984db8c20d8aad8a720d8b7d8b1d8a7d8addb8c20d8aad985d8b1db8cd986e2808cd987d8a7db8c20d985d8b4d8aed8b520d98820d8a7d986d8aad982d8a7d98420d985d987d8a7d8b1d8aa20d8a8d98720d8acd984d8b3d8a7d8aa20d988d8a7d982d8b9db8c20d8afd986d8a8d8a7d98420d985db8ce2808cdaa9d986db8cd9852e20d8a2d985d988d8b2d8b420d985d981d987d988d985db8c20d8afd8b120daa9d986d8a7d8b120d8aad985d8b1db8cd986d88c20d985d8b4d8a7d987d8afd98720d98820d8a8d8a7d8b2d8aed988d8b1d8af20d982d8b1d8a7d8b120d985db8ce2808cdaafdb8cd8b1d8af20d8aad8a720d987d8b120d8b4d8b1daa9d8aae2808cdaa9d986d986d8afd98720d8a8d8aad988d8a7d986d8af20d8a8d8b1d986d8a7d985d98720d8b4d8aed8b5db8cd99020d982d8a7d8a8d98420d8a8d8a7d8b2d986daafd8b1db8c20d8a8d8b1d8a7db8c20d8aad988d8b3d8b9d98720d985d987d8a7d8b1d8aae2808cd987d8a7db8c20d8aed988d8af20d8b7d8b1d8a7d8addb8c20daa9d986d8af2e USING utf8mb4),CONVERT(0x7b22696d616765223a22696d616765735c2f64656c696265726174652d70726163746963652d776f726b73686f702d76312e6a7067222c2263617465676f7279223a22daa9d8a7d8b1daafd8a7d98720d8aad8aed8b5d8b5db8c20d8b1d988d8a7d986e2808cd8afd8b1d985d8a7d986db8c222c22636f757273655f737461747573223a22616374697665222c22726567697374726174696f6e5f6d6f6465223a2272657175657374222c22666f726d6174223a22d8add8b6d988d8b1db8c20d98820d8a2d986d984d8a7db8cd9862028d987db8cd8a8d8b1db8cd8afdb8c29222c2270726963655f616d6f756e74223a31353030303030302c227061796d656e745f6f7074696f6e73223a5b2263617368222c22696e7374616c6c6d656e7473225d2c227061796d656e745f6e6f7465223a22d8a7d985daa9d8a7d98620d9bed8b1d8afd8a7d8aed8aa20d986d982d8afdb8c20d98820d8a7d982d8b3d8a7d8b7db8c20d988d8acd988d8af20d8afd8a7d8b1d8af2e20d8aad8b9d8afd8a7d8af20d8a7d982d8b3d8a7d8b720d98820d8b2d985d8a7d98620d9bed8b1d8afd8a7d8aed8aa20d9bed8b320d8a7d8b220d987d985d8a7d987d986daafdb8c20d8a8d8a720d8aadb8cd98520d985d986d8aad988d8b1db8cd8b320d8a7d8b9d984d8a7d98520d985db8ce2808cd8b4d988d8af2e222c226475726174696f6e223a22d9bed8b320d8a7d8b220d987d985d8a7d987d986daafdb8c20d8a7d8b9d984d8a7d98520d985db8ce2808cd8b4d988d8af222c227363686564756c65223a22d8b2d985d8a7d98620d8b4d8b1d988d8b920d98820d8acd984d8b3d8a7d8aa20d8a7d8b9d984d8a7d98520d8aed988d8a7d987d8af20d8b4d8af222c226c6576656c223a22d8aad988d8b3d8b9d98720d8add8b1d981d987e2808cd8a7db8c20d8afd8b1d985d8a7d986daafd8b1222c226361706163697479223a302c226365727469666963617465223a22d8b4d8b1d8a7db8cd8b720d8b5d8afd988d8b120daafd988d8a7d987db8c20d9bed8b320d8a7d8b220d986d987d8a7db8cdb8ce2808cd8b4d8afd98620d8a8d8b1d986d8a7d985d98720d8afd988d8b1d98720d8a7d8b9d984d8a7d98520d985db8ce2808cd8b4d988d8af2e222c2263657274696669636174655f636f6e6669726d6564223a66616c73652c226c696e655f736c7567223a227468657261706973742d646576656c6f706d656e74222c22696e7374727563746f725f736c7567223a22616c692d6665697a69222c2272656c617465645f6d656e746f7273223a5b226d617279616d2d68616768616e69222c22616c692d6665697a69225d2c2261756469656e6365223a5b22d8b1d988d8a7d986e2808cd8afd8b1d985d8a7d986daafd8b1d8a7d98620d98820d8b1d988d8a7d986e2808cd8b4d986d8a7d8b3d8a7d98620d8b9d984d8a7d982d987e2808cd985d986d8af20d8a8d98720d8aad988d8b3d8b9d98720d985d987d8a7d8b1d8aa20d8a8d8a7d984db8cd986db8c222c22d8afd8a7d986d8b4d8acd988db8cd8a7d98620d8b1d988d8a7d986e2808cd8b4d986d8a7d8b3db8c20d98820d985d8b4d8a7d988d8b1d98720d8afd8b120d985d8b3db8cd8b120db8cd8a7d8afdaafdb8cd8b1db8c20d8add8b1d981d987e2808cd8a7db8c225d2c22637572726963756c756d223a5b7b227469746c65223a22d8aad8a8d8add8b120d8afd8b120d8b1d988d8a7d986e2808cd8afd8b1d985d8a7d986db8c20d98820d8b9d984d98520d8aad988d8b3d8b9d98720d985d987d8a7d8b1d8aa20d8afd8b1d985d8a7d986daafd8b1222c226465736372697074696f6e223a22d8a8d8b1d8b1d8b3db8c20d985db8ce2808cdaa9d986db8cd98520d8aad8a8d8add8b120d8add8b1d981d987e2808cd8a7db8c20da86daafd988d986d98720d8b4daa9d98420d985db8ce2808cdaafdb8cd8b1d8af20d98820da86d8b1d8a720d8aad8acd8b1d8a8d987d88c20d8b3d8a7d8a8d982d98720daa9d8a7d8b120db8cd8a720d8b4d8b1daa9d8aa20d8afd8b120d8afd988d8b1d987e2808cd987d8a7db8c20d8a2d985d988d8b2d8b4db8c20d8a8d987e2808cd8aad986d987d8a7db8cdb8c20d8a7d981d8b2d8a7db8cd8b420d8a7d8abd8b1d8a8d8aed8b4db8c20d8b1d8a720d8aad8b6d985db8cd98620d986d985db8ce2808cdaa9d986d8af2e20db8cd8a7d981d8aad987e2808cd987d8a7db8c20d9beda98d988d987d8b4db8c20d8afd8b1d8a8d8a7d8b1d98720d8aad981d8a7d988d8aa20d985db8cd8a7d98620d8afd8b1d985d8a7d986daafd8b1d8a7d98620d98820d985d8b3db8cd8b120d8b1d8b4d8af20d8add8b1d981d987e2808cd8a7db8c20d8b1d8a720d985d8b1d988d8b120d985db8ce2808cdaa9d986db8cd9852e222c226475726174696f6e223a22227d2c7b227469746c65223a22d8aad985d8b1db8cd98620d8b9d8a7d985d8afd8a7d986d987d89b20d981d8b1d8a7d8aad8b120d8a7d8b220d8aad8acd8b1d8a8d98720d98820d8a2d985d988d8b2d8b420d985d8b9d985d988d984222c226465736372697074696f6e223a22d8aad8b9d8b1db8cd98120d98820d985d8a4d984d981d987e2808cd987d8a7db8c20d8aad985d8b1db8cd98620d8b9d8a7d985d8afd8a7d986d98720d8b1d8a720d8a8d8b1d8b1d8b3db8c20d98820d8a2d98620d8b1d8a720d8a7d8b220d985d8b7d8a7d984d8b9d987d88c20d985d8b4d8a7d987d8afd98720d8afd8b1d985d8a7d986d88c20d986d982d8b4e2808cd8a2d981d8b1db8cd986db8c20d985d8b9d985d988d98420d98820d8aadaa9d8b1d8a7d8b120d8b3d8a7d8afd98720d985d987d8a7d8b1d8aa20d8aad981daa9db8cdaa920d985db8ce2808cdaa9d986db8cd9852e20d8acd8a7db8cdaafd8a7d98720d8a7db8cd98620d986d988d8b920d8aad985d8b1db8cd98620d8afd8b120daa9d986d8a7d8b120d8a2d985d988d8b2d8b420d98820d8b3d988d9bed8b1d988db8cda98d98620d8b1d988d8b4d98620d985db8ce2808cd8b4d988d8af2e222c226475726174696f6e223a22227d2c7b227469746c65223a22d8b3d986d8acd8b420d8b9d985d984daa9d8b1d8af20d98820d8b3d8a7d8aed8aad98620d8aed8b720d9bed8a7db8cd987222c226465736372697074696f6e223a22d8a8d8a720d8a7d8b3d8aad981d8a7d8afd98720d8a7d8b220d8afd8a7d8afd987e2808cd987d8a7db8c20d9bedb8cd8a7d985d8af20d8afd8b1d985d8a7d986d88c20d8a8d8a7d8b2d8aed988d8b1d8af20d985d8b1d8a7d8acd8b9d88c20d8a7d8b1d8b2db8cd8a7d8a8db8c20d8a7d8aad8add8a7d8af20d8afd8b1d985d8a7d986db8c20d98820d985d8b4d8a7d987d8afd98720d8acd984d8b3d8a7d8aad88c20d8aad8b5d988db8cd8b1db8c20d988d8a7d982d8b9e2808cd8a8db8cd986d8a7d986d987e2808cd8aad8b120d8a7d8b220d8b9d985d984daa9d8b1d8af20d981d8b9d984db8c20d8aed988d8af20d985db8ce2808cd8b3d8a7d8b2db8cd9852e222c226475726174696f6e223a22227d2c7b227469746c65223a22d986d982d8a7d8b720d982d988d8aad88c20d985d8add8afd988d8afdb8cd8aae2808cd987d8a720d98820d984d8a8d987e2808cd987d8a7db8c20d8b1d8b4d8af222c226465736372697074696f6e223a22d8add988d8b2d987e2808cd987d8a7db8cdb8c20d8b1d8a720d8b4d986d8a7d8b3d8a7db8cdb8c20d985db8ce2808cdaa9d986db8cd98520daa9d98720d8a8db8cd8b4d8aad8b1db8cd98620d8b8d8b1d981db8cd8aa20d8b1d8b4d8af20d8afd8b120d8a2d986d987d8a720d988d8acd988d8af20d8afd8a7d8b1d8afd89b20d8aad8a720d8aad985d8b1db8cd98620d8a8d8b120d8a7d8b3d8a7d8b320d986db8cd8a7d8b220d988d8a7d982d8b9db8c20d8afd8b1d985d8a7d986daafd8b120d8b7d8b1d8a7d8addb8c20d8b4d988d8afd88c20d986d98720d8b5d8b1d981d8a7d98b20d8b9d984d8a7d982d98720db8cd8a720d985d988d8b6d988d8b9d8a7d8aa20d8b1d8a7db8cd8ac20d8a2d985d988d8b2d8b4db8c2e222c226475726174696f6e223a22227d2c7b227469746c65223a22d8a7d8b220d987d8afd98120daa9d984db8c20d8aad8a720d985d987d8a7d8b1d8aa20d8aad985d8b1db8cd986e2808cd9bed8b0db8cd8b1222c226465736372697074696f6e223a22d987d8afd981e2808cd987d8a7db8cdb8c20d985d8a7d986d986d8af20c2abd987d985d8afd984e2808cd8aad8b120d8b4d8afd986c2bb20d8b1d8a720d8a8d98720d8b1d981d8aad8a7d8b1d987d8a7db8c20daa9d988da86daa9e2808cd8aad8b1d88c20d985d8b4d8a7d987d8afd987e2808cd9bed8b0db8cd8b120d98820d982d8a7d8a8d98420d8aad985d8b1db8cd98620d8aad8a8d8afdb8cd98420d985db8ce2808cdaa9d986db8cd98520d98820d985d8b9db8cd8a7d8b120d8b1d988d8b4d98620d985d988d981d982db8cd8aa20d985db8ce2808cd8b3d8a7d8b2db8cd9852e222c226475726174696f6e223a22227d2c7b227469746c65223a22d8b7d8b1d8a7d8addb8c20d8aad985d8b1db8cd98620d98820d8aad986d8b8db8cd98520d8afd8b4d988d8a7d8b1db8c222c226465736372697074696f6e223a22d8a8d8b1d8a7db8c20db8cdaa920d985d987d8a7d8b1d8aa20d985d8b4d8aed8b5d88c20d8aad985d8b1db8cd98620d8b9d985d984db8c20d8b7d8b1d8a7d8addb8c20d985db8ce2808cdaa9d986db8cd9852e20d8b3d8b7d8ad20d8afd8b4d988d8a7d8b1db8cd88c20d985d8add8b1daa9d88c20d8aadaa9d8b1d8a7d8b120d98820d986d988d8b920d8a8d8a7d8b2d8aed988d8b1d8af20d8b1d8a720d8b7d988d8b1db8c20d8aad986d8b8db8cd98520d985db8ce2808cdaa9d986db8cd98520daa9d98720d8aad985d8b1db8cd98620da86d8a7d984d8b4e2808cd8a8d8b1d8a7d986daafdb8cd8b220d98820d982d8a7d8a8d98420d8a7d986d8acd8a7d98520d8a8d8a7d8b4d8af2e222c226475726174696f6e223a22227d2c7b227469746c65223a22d985d987d8a7d8b1d8aae2808cd987d8a7db8c20d8a8db8cd986e2808cd981d8b1d8afdb8c20d98820d981d8b1d8a7db8cd986d8afdb8c222c226465736372697074696f6e223a22d9bed8a7d8b3d8ae20d987d985d8afd984d8a7d986d987d88c20d8b1d988d8b4d986e2808cd8b3d8a7d8b2db8c20d8aad8acd8b1d8a8d98720d985d8b1d8a7d8acd8b9d88c20daa9d8a7d8b120d8a8d8a720d987db8cd8acd8a7d986d88c20d8add981d8b820d8aad985d8b1daa9d8b220d8afd8b1d985d8a7d986db8c20d98820d8a7db8cd8acd8a7d8af20d987d985daa9d8a7d8b1db8c20d8b1d8a720d8afd8b120d985d988d982d8b9db8cd8aae2808cd987d8a7db8c20d8aad985d8b1db8cd986db8c20d8a8d98720daa9d8a7d8b120d985db8ce2808cdaafdb8cd8b1db8cd9852e222c226475726174696f6e223a22227d2c7b227469746c65223a22d985d988d982d8b9db8cd8aae2808cd987d8a7db8c20d8afd8b4d988d8a7d8b120d98820daafd8b3d8b3d8aa20d8a7d8aad8add8a7d8af222c226465736372697074696f6e223a22d8b3daa9d988d8aa20d8b7d988d984d8a7d986db8cd88c20d8aed8b4d985d88c20d985d8aed8a7d984d981d8aad88c20d986d8a7d8a7d985db8cd8afdb8c20d8a7d8b220d8afd8b1d985d8a7d98620d98820d8a8db8cd8a7d98620d986d8a7d8b1d8b6d8a7db8cd8aadb8c20d8b1d8a720d8aad985d8b1db8cd98620d985db8ce2808cdaa9d986db8cd98520d98820d8a8d98720d8b4db8cd988d98720d9bed8a7d8b3d8ae20d8afd8b1d985d8a7d986daafd8b120d8afd8b120d8a7db8cd98620d984d8add8b8d987e2808cd987d8a720d8aad988d8acd98720d985db8ce2808cdaa9d986db8cd9852e222c226475726174696f6e223a22227d2c7b227469746c65223a22d8a7d986d8b9d8b7d8a7d981e2808cd9bed8b0db8cd8b1db8c20d98820d9bed8a7d8b3d8aee2808cd8afd987db8c20d8afd8b1d985d8a7d986daafd8b1222c226465736372697074696f6e223a22d8a8d98720d8acd8a7db8c20d8acd8b3d8aae2808cd988d8acd988db8c20db8cdaa920d9bed8a7d8b3d8ae20d8abd8a7d8a8d8aad88c20db8cd8a7d8af20d985db8ce2808cdaafdb8cd8b1db8cd98520d9bed8a7d8b3d8ae20d8aed988d8af20d8b1d8a720d8a8d8a720d988db8cda98daafdb8ce2808cd987d8a7db8c20d985d8b1d8a7d8acd8b9d88c20d8b4d8b1d8a7db8cd8b720d8acd984d8b3d98720d98820d8a8d8a7d8b2d8aed988d8b1d8af20d984d8add8b8d987e2808cd8a7db8c20d987d985d8a7d987d986daaf20daa9d986db8cd9852e222c226475726174696f6e223a22227d2c7b227469746c65223a22d988d8a7daa9d986d8b4e2808cd987d8a7db8c20d8b4d8aed8b5db8c20d98820d8b8d8b1d981db8cd8aa20d8b1d988d8a7d986e2808cd8b4d986d8a7d8aed8aadb8c222c226465736372697074696f6e223a22d8a7d8b6d8b7d8b1d8a7d8a8d88c20d8aed8b4d985d88c20d8b3d8b1d8afd8b1daafd985db8cd88c20d8a7d8add8b3d8a7d8b320d986d8a7daa9d8a7d8b1d8a2d985d8afdb8c20d98820d985db8cd98420d8a8d98720d8a7d8acd8aad986d8a7d8a820d8b1d8a720d985db8ce2808cd8b4d986d8a7d8b3db8cd98520d98820d8add981d8b820d8a7d8b1d8aad8a8d8a7d8b720d8add8b1d981d987e2808cd8a7db8c20d8a8d8a720d985d8b1d8a7d8acd8b920d8b1d8a720d8afd8b120d8add8b6d988d8b120d8a7db8cd98620d988d8a7daa9d986d8b4e2808cd987d8a720d8aad985d8b1db8cd98620d985db8ce2808cdaa9d986db8cd9852e222c226475726174696f6e223a22227d2c7b227469746c65223a22d8a8d8a7d8b2d8aed988d8b1d8afd88c20d985d8b4d8a7d987d8afd98720d98820d8b3d988d9bed8b1d988db8cda98d98620d985d8a4d8abd8b1222c226465736372697074696f6e223a22d8a7d8b220d985d8b4d8a7d987d8afd98720d8b9d985d984daa9d8b1d8af20d98820d8a8d8a7d8b2d8aed988d8b1d8af20d987d985d8aad8a7db8cd8a7d98620d98820d8b3d988d9bed8b1d988d8a7db8cd8b2d8b120d8a8d8b1d8a7db8c20db8cd8a7d8afdaafdb8cd8b1db8c20d8a7d8b3d8aad981d8a7d8afd98720d985db8ce2808cdaa9d986db8cd9852e20d8b6d8a8d8b720db8cd8a720d8a8d987e2808cd8a7d8b4d8aad8b1d8a7daa9e2808cdaafd8b0d8a7d8b1db8c20d986d985d988d986d98720d8acd984d8b3d98720d8aad986d987d8a720d8a8d8a720d8b1d8b6d8a7db8cd8aa20d8a2daafd8a7d987d8a7d986d98720d98820d8add981d8a7d8b8d8aa20d8a7d8b220d985d8add8b1d985d8a7d986daafdb8c20d985d8b1d8a7d8acd8b920d8a7d986d8acd8a7d98520d985db8ce2808cd8b4d988d8af2e222c226475726174696f6e223a22227d2c7b227469746c65223a22d8a7d986d8aad982d8a7d98420d985d987d8a7d8b1d8aa20d8a8d98720d8acd984d8b3d8a7d8aa20d988d8a7d982d8b9db8c222c226465736372697074696f6e223a22d8a8d8b1d8b1d8b3db8c20d985db8ce2808cdaa9d986db8cd98520da86daafd988d986d98720d8aad8badb8cdb8cd8b120d8b9d985d984daa9d8b1d8af20d8afd8b120d985d8addb8cd8b720d8aad985d8b1db8cd98620d8b1d8a720d8afd8b120d8acd984d8b3d8a7d8aa20d988d8a7d982d8b9db8c20d986db8cd8b220d985d8b4d8a7d987d8afd98720d98820d8a7d8b1d8b2db8cd8a7d8a8db8c20daa9d986db8cd985d89b20d987d985d8b1d8a7d98720d8a8d8a720d8a8d8a7d8b2d986daafd8b1db8c20d8aad985d8b1db8cd98620d8a8d8b120d8a7d8b3d8a7d8b320d8afd8a7d8afd987e2808cd987d8a7db8c20d8aad8a7d8b2d9872e222c226475726174696f6e223a22227d2c7b227469746c65223a22d8a8d8b1d986d8a7d985d98720d8b4d8aed8b5db8c20d8b1d8b4d8af20d8add8b1d981d987e2808cd8a7db8c222c226465736372697074696f6e223a22d8afd8b120d9bed8a7db8cd8a7d986d88c20d987d8afd981e2808cd987d8a7db8c20db8cd8a7d8afdaafdb8cd8b1db8cd88c20d985d987d8a7d8b1d8aae2808cd987d8a7db8c20d985d988d8b1d8af20d8aad985d8b1db8cd986d88c20d985d986d8a7d8a8d8b920d8a8d8a7d8b2d8aed988d8b1d8afd88c20d8b4db8cd988d98720d8b3d986d8acd8b420d9bedb8cd8b4d8b1d981d8aa20d98820d8b2d985d8a7d98620d8a8d8a7d8b2d986daafd8b1db8c20d8b1d8a720d8afd8b120db8cdaa920d8a8d8b1d986d8a7d985d98720d8b4d8aed8b5db8c20d98820d8a8d984d986d8afd985d8afd8aa20daa9d986d8a7d8b120d987d98520d985db8ce2808cdaafd8b0d8a7d8b1db8cd9852e222c226475726174696f6e223a22227d5d2c22666171223a5b7b227175657374696f6e223a22d987d8b2db8cd986d98720d98820d8b4db8cd988d98720d9bed8b1d8afd8a7d8aed8aa20da86daafd988d986d98720d8a7d8b3d8aad89f222c22616e73776572223a22d985d8a8d984d8ba20daa9d98420d8afd988d8b1d98720dbb1dbb520d985db8cd984db8cd988d98620d8aad988d985d8a7d98620d8a7d8b3d8aa2e20d9bed8b1d8afd8a7d8aed8aa20d986d982d8afdb8c20d98820d8a7d982d8b3d8a7d8b7db8c20d982d8a7d8a8d98420d8a7d986d8aad8aed8a7d8a820d8a7d8b3d8aad89b20d8acd8b2d8a6db8cd8a7d8aa20d8a7d982d8b3d8a7d8b720d9bed8b320d8a7d8b220d987d985d8a7d987d986daafdb8c20d8a7d8b9d984d8a7d98520d985db8ce2808cd8b4d988d8af2e227d2c7b227175657374696f6e223a22d8afd988d8b1d98720d8add8b6d988d8b1db8c20d8a7d8b3d8aa20db8cd8a720d8a2d986d984d8a7db8cd986d89f222c22616e73776572223a22daa9d8a7d8b1daafd8a7d98720d8a8d987e2808cd8b5d988d8b1d8aa20d987db8cd8a8d8b1db8cd8afdb8cd88c20d8a8d8a720d8a7d985daa9d8a7d98620d8add8b6d988d8b120d8add8b6d988d8b1db8c20d98820d8a2d986d984d8a7db8cd986d88c20d8a8d8b1d986d8a7d985d987e2808cd8b1db8cd8b2db8c20d8b4d8afd98720d8a7d8b3d8aa2e20d8acd8b2d8a6db8cd8a7d8aa20d8a7d8acd8b1d8a720d9bed8b320d8a7d8b220d987d985d8a7d987d986daafdb8c20d8a7d8b9d984d8a7d98520d985db8ce2808cd8b4d988d8af2e227d2c7b227175657374696f6e223a22d8abd8a8d8aa20d8afd8b1d8aed988d8a7d8b3d8aa20d8a8d98720d985d8b9d986d8a7db8c20d8abd8a8d8aae2808cd986d8a7d98520d982d8b7d8b9db8c20d8a7d8b3d8aad89f222c22616e73776572223a22d8aedb8cd8b12e20d8aadb8cd98520d985d986d8aad988d8b1db8cd8b320d8afd8b1d8aed988d8a7d8b3d8aa20d8b1d8a720d8a8d8b1d8b1d8b3db8c20d985db8ce2808cdaa9d986d8af20d98820d8a8d8b1d8a7db8c20d987d985d8a7d987d986daafdb8cd88c20d8a7d8b9d984d8a7d98520d8a8d8b1d986d8a7d985d98720d98820d8b4d8b1d8a7db8cd8b720d9bed8b1d8afd8a7d8aed8aa20d8a8d8a720d8b4d985d8a720d8a7d8b1d8aad8a8d8a7d8b720d985db8ce2808cdaafdb8cd8b1d8af2e227d5d2c226665617475726564223a747275657d USING utf8mb4),UTC_TIMESTAMP(),UTC_TIMESTAMP() FROM content_entities ce WHERE ce.entity_type=CONVERT(0x636f75727365 USING utf8mb4) AND ce.slug=CONVERT(0x70737963686f746865726170792d6d6173746572792d64656c696265726174652d7072616374696365 USING utf8mb4);

-- deliberate-practice-first-step-mastery
INSERT IGNORE INTO content_entities (id,entity_type,slug,status,sort_order,author_id,published_at,created_at,updated_at) VALUES ('7a53386dfa52d2c277eb060ea0c3ff3a',CONVERT(0x61727469636c65 USING utf8mb4),CONVERT(0x64656c696265726174652d70726163746963652d66697273742d737465702d6d617374657279 USING utf8mb4),'published',-20,NULL,UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP());
INSERT IGNORE INTO content_translations (id,entity_id,locale,title,subtitle,excerpt,body,metadata,created_at,updated_at) SELECT SUBSTRING(SHA2(CONCAT(ce.id,'|fa'),256),1,32),ce.id,'fa',CONVERT(0xd8aad985d8b1db8cd98620d8b9d8a7d985d8afd8a7d986d98720d8afd8b120d8b1d988d8a7d986e2808cd8afd8b1d985d8a7d986db8cd89b20d8a7d988d984db8cd98620daafd8a7d98520d8a8d98720d8b3d988db8c20d8aad8a8d8add8b120d8add8b1d981d987e2808cd8a7db8c USING utf8mb4),CONVERT(0xd8a7d8b220d8aad8acd8b1d8a8d98720d8a8d8a7d984db8cd986db8c20d8aad8a720d8aad985d8b1db8cd98620d987d8afd981d985d986d8af USING utf8mb4),CONVERT(0xd8aad985d8b1db8cd98620d8b9d8a7d985d8afd8a7d986d98720da86db8cd8b3d8aa20d98820da86daafd988d986d98720d8a8d98720d8b1d8b4d8af20d985d987d8a7d8b1d8aa20d8afd8b1d985d8a7d986daafd8b120daa9d985daa920d985db8ce2808cdaa9d986d8afd89f20d8a8d8a720d986d982d8b420d8a8d8a7d8b2d8aed988d8b1d8afd88c20d8aad981d8a7d988d8aa20d8aad8acd8b1d8a8d98720d98820d8aad985d8b1db8cd986d88c20d98820db8cdaa920d986d985d988d986d98720d8b9d985d984db8c20d8a8d8b1d8a7db8c20d8b4d8b1d988d8b920d8a2d8b4d986d8a720d8b4d988db8cd8af2e USING utf8mb4),CONVERT(0xd8aad8acd8b1d8a8d98720d8a8d8a7d984db8cd986db8c20d8a7d8b1d8b2d8b4d985d986d8af20d8a7d8b3d8aad89b20d8a7d985d8a720d8a7d8b220daa9d8acd8a720d985db8ce2808cd981d987d985db8cd98520d8aad8acd8b1d8a8d98720d8a7d985d8b1d988d8b2d88c20d985d987d8a7d8b1d8aa20d981d8b1d8afd8a7db8c20d985d8a720d8b1d8a720d8a8d987d8aad8b120daa9d8b1d8afd98720d8a7d8b3d8aad89f20d8aad985d8b1db8cd98620d8b9d8a7d985d8afd8a7d986d98720d8afd8b9d988d8aadb8c20d8a7d8b3d8aa20d8a8d98720d8afd982db8cd982e2808cd8aad8b120d8afdb8cd8afd98620d8b9d985d984daa9d8b1d8afd88c20d8a7d986d8aad8aed8a7d8a820db8cdaa920d985d987d8a7d8b1d8aa20d985d8b4d8aed8b520d98820d8aad985d8b1db8cd98620d8a2d98620d8a8d8a720d8a8d8a7d8b2d8aed988d8b1d8afd89b20d985d8b3db8cd8b1db8c20daa9d98720d8b1d8b4d8af20d8add8b1d981d987e2808cd8a7db8c20d8b1d8a720d8a7d8b220db8cdaa920d8a2d8b1d8b2d98820d8a8d98720db8cdaa920d8a8d8b1d986d8a7d985d98720d982d8a7d8a8d98420d8a8d8b1d8b1d8b3db8c20d8aad8a8d8afdb8cd98420d985db8ce2808cdaa9d986d8af2e0a0a232320d8a7d8a8d8b1d8afd8b1d985d8a7d986daafd8b120db8cd8b9d986db8c20da86d987d89f0ad8afd8b120d986d988d8b4d8aad987e2808cd987d8a7db8c20d8a7d8b3daa9d8a7d8aa20d8afdb8c2e20d985db8cd984d8b120d98820d987d985daa9d8a7d8b1d8a7d986d88c20c2abd8a7d8a8d8b1d8afd8b1d985d8a7d986daafd8b1c2bb20db8cd8a7205375706572736872696e6b20d8a8d98720d8afd8b1d985d8a7d986daafd8b1db8c20d8a7d8b4d8a7d8b1d98720d8afd8a7d8b1d8af20daa9d98720d8afd8b120d8b3d986d8acd8b420d986d8b8d8a7d985e2808cd985d986d8af20d9bedb8cd8a7d985d8afd987d8a7db8c20d8afd8b1d985d8a7d986d88c20d986d8aad8a7db8cd8ac20d8a8d987d8aad8b1db8c20d8a7d8b220d987d985d8aad8a7db8cd8a7d98620d8aed988d8af20d8a8d98720d8afd8b3d8aa20d985db8ce2808cd8a2d988d8b1d8af2e20d8a7db8cd98620d8b9d986d988d8a7d98620d8a8d98720d8b4d987d8b1d8aad88c20d8aad8b9d8afd8a7d8af20d8afd988d8b1d987e2808cd987d8a7db8c20daafd8b0d8b1d8a7d986d8afd987e2808cd8b4d8afd98720db8cd8a720d8aad8b9d984d98220d8a8d98720db8cdaa920d8b1d988db8cdaa9d8b1d8af20d8aed8a7d8b520d988d8a7d8a8d8b3d8aad98720d986db8cd8b3d8aad89b20d8a8d8add8ab20d8afd8b1d8a8d8a7d8b1d98720d8b9d985d984daa9d8b1d8af20d8a8d8a7d984db8cd986db8cd99020d982d8a7d8a8d98420d8a7d986d8afd8a7d8b2d987e2808cdaafdb8cd8b1db8c20d8a7d8b3d8aa2e205bdbb15d0a0ada86d986db8cd98620d985d982d8a7db8cd8b3d987e2808cd8a7db8c20d8a8d98720d8afd8a7d8afd987e2808cd987d8a7db8c20daa9d8a7d981db8cd88c20d8aad988d8acd98720d8a8d98720d8aad981d8a7d988d8aa20d985d8b1d8a7d8acd8b9d8a7d98620d98820d8b4db8cd988d98720d985d986d8a7d8b3d8a820d8a7d986d8afd8a7d8b2d987e2808cdaafdb8cd8b1db8c20d986db8cd8a7d8b220d8afd8a7d8b1d8af2e20db8cdaa920d8acd984d8b3d98720d985d988d981d98220db8cd8a720d8b1d8b6d8a7db8cd8aa20d8b4d8aed8b5db8c20d8afd8b1d985d8a7d986daafd8b1d88c20d8a8d987e2808cd8aad986d987d8a7db8cdb8c20d8a8d8b1d8a7db8c20d982d8b6d8a7d988d8aa20d8afd8b1d8a8d8a7d8b1d98720d8a7d8abd8b1d8a8d8aed8b4db8c20d9bed8a7db8cd8afd8a7d8b120daa9d8a7d981db8c20d986db8cd8b3d8aa2e0a0a232320da86d8b1d8a720d8b3d8a7d8a8d982d98720daa9d8a7d8b120d8a8d987e2808cd8aad986d987d8a7db8cdb8c20d9bed8a7d8b3d8ae20d986d985db8ce2808cd8afd987d8afd89f0ad985d985daa9d98620d8a7d8b3d8aa20d8b3d8a7d984e2808cd987d8a720db8cdaa920daa9d8a7d8b120d8b1d8a720d8a7d986d8acd8a7d98520d8afd987db8cd985d88c20d8a8d8afd988d98620d8a7db8cd986daa9d98720d8afd982db8cd982d8a7d98b20d8a8d8afd8a7d986db8cd98520d8afd8b120daa9d8afd8a7d98520d8a8d8aed8b420d8a8d987d8aad8b120d8b4d8afd987e2808cd8a7db8cd9852e20d8aad8acd8b1d8a8d98720d981d8b1d8b5d8aa20db8cd8a7d8afdaafdb8cd8b1db8c20d8a7db8cd8acd8a7d8af20d985db8ce2808cdaa9d986d8afd89b20daa9db8cd981db8cd8aa20d8a8d8a7d8b2d8aed988d8b1d8af20d98820d8b4db8cd988d98720d8a7d8b3d8aad981d8a7d8afd98720d8a7d8b220d8a7db8cd98620d981d8b1d8b5d8aa20d986db8cd8b220d8a7d987d985db8cd8aa20d8afd8a7d8b1d8af2e0a0ad9beda98d988d987d8b4e2808cd987d8a7db8c20d985d8b1d8a8d988d8b720d8a8d98720d8aad981d8a7d988d8aa20d985db8cd8a7d98620d8afd8b1d985d8a7d986daafd8b1d8a7d986d88c20d9bed8b1d8b3d8b420d985d987d985db8c20d9bedb8cd8b420d8b1d988db8c20d985d8a720d985db8ce2808cdaafd8b0d8a7d8b1d986d8af3a20d8a8d8b1d8a7db8c20d8b4d986d8a7d8aed8aa20d8b9d985d984daa9d8b1d8af20d8aed988d8afd88c20da86d98720d8afd8a7d8afd987e2808cd8a7db8c20d981d8b1d8a7d8aad8b120d8a7d8b220d8a8d8b1d8afd8a7d8b4d8aa20d8b4d8aed8b5db8c20d8afd8a7d8b1db8cd985d89f20d8a8d8a7d8b2d8aed988d8b1d8af20d985d8b1d8a7d8acd8b9d88c20d985d8b4d8a7d987d8afd98720d8b9d985d984daa9d8b1d8af20d98820d9bedb8cdaafdb8cd8b1db8c20d9bedb8cd8a7d985d8afd987d8a7db8c20d8afd8b1d985d8a7d98620d985db8ce2808cd8aad988d8a7d986d986d8af20d8a8d8aed8b4db8c20d8a7d8b220d8a7db8cd98620d8aad8b5d988db8cd8b120d8b1d8a720d8b1d988d8b4d98620daa9d986d986d8af2e20d985d8b7d8a7d984d8b9d98720da86d8a7d98820d98820d987d985daa9d8a7d8b1d8a7d98620d986db8cd8b220d8a7d8b1d8aad8a8d8a7d8b720d985db8cd8a7d98620d981d8b9d8a7d984db8cd8aae2808cd987d8a7db8c20d8aad985d8b1db8cd986db8c20d98820d8a7d8abd8b1d8a8d8aed8b4db8c20d8afd8b1d985d8a7d986daafd8b1d8a7d98620d8b1d8a720d8a8d8b1d8b1d8b3db8c20daa9d8b1d8afd98720d8a7d8b3d8aad89b20d8a7db8cd98620d8a7d8b1d8aad8a8d8a7d8b720d8b1d8a720d986d8a8d8a7db8cd8af20d8aad8b6d985db8cd98620d986d8aadb8cd8acd98720db8cd8a720d8a7d8abd8a8d8a7d8aa20d8b1d8a7d8a8d8b7d98720d8b9d984d991db8c20d8a8d8b1d8a7db8c20d987d8b120d981d8b1d8af20d8afd8a7d986d8b3d8aa2e205bdbb25d0a0a232320d8aad985d8b1db8cd98620d8b9d8a7d985d8afd8a7d986d98720d8afd982db8cd982d8a7d98b20da86db8cd8b3d8aad89f0ad8afd8b120da86d8a7d8b1da86d988d8a820d8a7d8b1db8cdaa9d8b3d988d986d88c20d8aad985d8b1db8cd98620d8b9d8a7d985d8afd8a7d986d98720d981d8b9d8a7d984db8cd8aadb8c20d987d8afd981d985d986d8af20d8a8d8b1d8a7db8c20d8a8d987d8a8d988d8af20db8cdaa920d985d8a4d984d981d98720d985d8b4d8aed8b520d8b9d985d984daa9d8b1d8af20d8a7d8b3d8aa3a20d987d8afd98120d8b1d988d8b4d98620d8afd8a7d8b1d8afd88c20d8b3d8b7d8ad20d8afd8b4d988d8a7d8b1db8c20d8a8d8a720d8aad988d8a7d98620d981d8b9d984db8c20d985d8aad986d8a7d8b3d8a820d8a7d8b3d8aad88c20d8a8d8a7d8b2d8aed988d8b1d8af20d981d8b1d8a7d987d98520d985db8ce2808cdaa9d986d8af20d98820d8a8d98720d8a7d8b5d984d8a7d8ad20d98820d8aadaa9d8b1d8a7d8b120d981d8b1d8b5d8aa20d985db8ce2808cd8afd987d8af2e20d985d8b7d8a7d984d8b9d98720d98820d8aadaa9d8b1d8a7d8b120d8b1d988d8b2d985d8b1d98720d985db8ce2808cd8aad988d8a7d986d986d8af20d985d981db8cd8af20d8a8d8a7d8b4d986d8afd89b20d8a7d985d8a720d987d8b120d981d8b9d8a7d984db8cd8aa20d8a2d985d988d8b2d8b4db8c20d8a8d8a720d8a7db8cd98620d8aad8b9d8b1db8cd98120db8cdaa9d8b3d8a7d98620d986db8cd8b3d8aa2e205bdbb35d0a0ad8afd8b120d8b1d988d8a7d986e2808cd8afd8b1d985d8a7d986db8cd88c20d987d8afd981db8c20d985d8a7d986d986d8af20c2abd987d985d8afd984e2808cd8aad8b120d8b4d8afd986c2bb20d8a8d8b3db8cd8a7d8b120daa9d984db8c20d8a7d8b3d8aa2e20d985db8ce2808cd8aad988d8a7d98620d8a2d98620d8b1d8a720d8a8d98720d985d987d8a7d8b1d8aadb8c20d982d8a7d8a8d98420d985d8b4d8a7d987d8afd98720d8aad8a8d8afdb8cd98420daa9d8b1d8af3a20c2abd9bedb8cd8b420d8a7d8b220d985d8b7d8b1d8ade2808cdaa9d8b1d8afd98620d8b1d8a7d987e2808cd8add984d88c20d8aad8acd8b1d8a8d98720d987db8cd8acd8a7d986db8c20d985d8b1d8a7d8acd8b920d8b1d8a720d8afd8b120db8cdaa920d8acd985d984d98720d8b1d988d8b4d98620d98820d8badb8cd8b1d982d8b6d8a7d988d8aadb8c20d8a8d8a7d8b2d8aad8a7d8a820d8a8d8afd987d9852ec2bb20d8a7db8cd98620d8b5d988d8b1d8aae2808cd8a8d986d8afdb8c20d8a7d985daa9d8a7d98620d8aad985d8b1db8cd98620d98820d8afd8b1db8cd8a7d981d8aa20d8a8d8a7d8b2d8aed988d8b1d8af20d985d8b4d8aed8b520d8b1d8a720d981d8b1d8a7d987d98520d985db8ce2808cdaa9d986d8af2e0a0a232320d8a7d981d8b3d8a7d986d98720d8afd987e2808cd987d8b2d8a7d8b120d8b3d8a7d8b9d8aa20d8b1d8a720daa9d986d8a7d8b120d8a8daafd8b0d8a7d8b1db8cd9850ad9beda98d988d987d8b420d985d8b4d987d988d8b120d8a7d8b1db8cdaa9d8b3d988d98620d8afd8b1d8a8d8a7d8b1d98720d986d988d8a7d8b2d986d8afdaafd8a7d986d88c20d8a8d98720d8aad981d8a7d988d8aa20d981d8b9d8a7d984db8cd8aae2808cd987d8a7db8c20d8aad985d8b1db8cd986db8c20d8afd8b120daafd8b1d988d987e2808cd987d8a7db8c20d985d988d8b1d8af20d985d8b7d8a7d984d8b9d98720d8aad988d8acd98720d8afd8a7d8b4d8aa2e20d8b9d8afd8af20d8afd987e2808cd987d8b2d8a7d8b120d8b3d8a7d8b9d8aad88c20d986d8b3d8aed98720d8abd8a7d8a8d8aa20d98820d987d985daafd8a7d986db8c20d8a8d8b1d8a7db8c20d8b1d8b3db8cd8afd98620d8a8d98720d8aad8a8d8add8b120d986db8cd8b3d8aa2e20d8add988d8b2d98720d981d8b9d8a7d984db8cd8aad88c20daa9db8cd981db8cd8aa20d8aad985d8b1db8cd986d88c20d8b4d8b1d8a7db8cd8b720db8cd8a7d8afdaafdb8cd8b1db8c20d98820d8b4db8cd988d98720d8b3d986d8acd8b420d8a7d987d985db8cd8aa20d8afd8a7d8b1d986d8af2e20d8a8d8b1d8a7db8c20db8cdaa920d8afd8b1d985d8a7d986daafd8b1d88c20d8b4d985d8b1d8afd98620d8b3d8a7d8b9d8aae2808cd987d8a7db8c20d8add8b6d988d8b120d8afd8b120d8a7d8aad8a7d98220d8afd8b1d985d8a7d98620d8a8d8a720d8b3d986d8acd8b420d8aad985d8b1db8cd98620d987d8afd981d985d986d8af20db8cdaa920d985d987d8a7d8b1d8aa20d8a8d8b1d8a7d8a8d8b120d986db8cd8b3d8aa2e205bdbb35d0a0a232320d8a8d8a7d8b2d8aed988d8b1d8afd89b20d8add984d982d98720d8a7d8aad8b5d8a7d98420d8aad985d8b1db8cd98620d8a8d98720db8cd8a7d8afdaafdb8cd8b1db8c0ad8a8d8a7d8b2d8aed988d8b1d8af20d8b2d985d8a7d986db8c20d8a8d8b1d8a7db8c20d8aad985d8b1db8cd98620d985d981db8cd8afd8aad8b120d8a7d8b3d8aa20daa9d98720d8a8d98720d8b1d981d8aad8a7d8b1db8c20d985d8b4d8aed8b520d8a7d8b4d8a7d8b1d98720daa9d986d8af20d98820d982d8afd98520d8a8d8b9d8afdb8c20d8b1d8a720d8b1d988d8b4d986e2808cd8aad8b120d8b3d8a7d8b2d8af2e20d8acd985d984d987e2808cd8a7db8c20d985d8a7d986d986d8af20c2abd8aed988d8a820d8a8d988d8afc2bb20d8acd987d8aa20daa9d985db8c20d8a8d98720d8aad985d8b1db8cd98620d985db8ce2808cd8afd987d8afd89b20d8afd8b120d985d982d8a7d8a8d984d88c20c2abd9bed8b320d8a7d8b220d8a8db8cd8a7d98620d986d8a7d8a7d985db8cd8afdb8c20d985d8b1d8a7d8acd8b9d88c20d8b3d8b1db8cd8b920d8b3d8b1d8a7d8ba20d8aad988d8b6db8cd8ad20d8aadaa9d986db8cdaa920d8b1d981d8aadb8cd89b20db8cdaa920d8a8d8a7d8b120d8a8d8a720d985daa9d8ab20d98820d8a8d8a7d8b2d8aad8a7d8a820d8a7d8add8b3d8a7d8b320d9bed8a7d8b3d8ae20d8a8d8afd987c2bb20d8a8d98720db8cdaa920d8b1d981d8aad8a7d8b120d8aad985d8b1db8cd986e2808cd9bed8b0db8cd8b120d8a7d8b4d8a7d8b1d98720d985db8ce2808cdaa9d986d8af2e0a0ad985d8b4d8a7d987d8afd98720db8cdaa920d986d982d8b4e2808cd8a2d981d8b1db8cd986db8c20d8b3d8a7d8aed8aadaafdb8c20db8cd8a720daafd981d8aae2808cd988daafd98820d8a8d8a720d987d985d8aad8a7db8cd8a7d986d88c20d981d8b1d8b5d8aa20d8b4d8b1d988d8b920daa9d985e2808cd987d8b2db8cd986d987e2808cd8a7db8c20d8a7d8b3d8aa2e20d8a7d8b3d8aad981d8a7d8afd98720d8a7d8b220d8b6d8a8d8b720d8acd984d8b3d98720d988d8a7d982d8b9db8c20d8a8d98720d8b1d8b6d8a7db8cd8aa20d8a2daafd8a7d987d8a7d986d987d88c20d8add981d8b820d985d8add8b1d985d8a7d986daafdb8c20d98820d8b1d8b9d8a7db8cd8aa20d982d988d8a7d8b9d8af20d8add8b1d981d987e2808cd8a7db8c20d986db8cd8a7d8b220d8afd8a7d8b1d8af2e20d8aad985d8b1db8cd98620d8acd8a7db8cdaafd8b2db8cd98620d985d8b3d8a6d988d984db8cd8aa20d8a8d8a7d984db8cd986db8c20db8cd8a720d8b3d988d9bed8b1d988db8cda98d98620d985d8aad986d8a7d8b3d8a820d8a8d8a720d986db8cd8a7d8b220d8afd8b1d985d8a7d986daafd8b120d986d985db8ce2808cd8b4d988d8af2e0a0a232320db8cdaa920d986d985d988d986d9873a20d9bed8a7d8b3d8ae20d8a8d98720c2abd8a7db8cd98620d8acd984d8b3d8a7d8aa20d981d8a7db8cd8afd987e2808cd8a7db8c20d986d8afd8a7d8b1d8afc2bb0ad981d8b1d8b620daa9d986db8cd8af20d985d8b1d8a7d8acd8b920d985db8ce2808cdaafd988db8cd8af3a20c2abd981daa9d8b120d986d985db8ce2808cdaa9d986d98520d8a7db8cd98620d8acd984d8b3d8a7d8aa20d8a8d98720d985d98620daa9d985daa920daa9d986d8af2ec2bb20d987d8afd98120d8aad985d8b1db8cd98620d985db8ce2808cd8aad988d8a7d986d8af20d8aad8add985d98420d8a8d8a7d8b2d8aed988d8b1d8af20d985d986d981db8c20d98820d8a2d8bad8a7d8b220daafd981d8aae2808cd988daafd988db8c20d985d8b4d8aad8b1daa920d8afd8b1d8a8d8a7d8b1d98720d8a2d98620d8a8d8a7d8b4d8af2e20d8afd8b120db8cdaa920d986d982d8b4e2808cd8a2d981d8b1db8cd986db8cd88c20d9bed8a7d8b3d8aee2808cd987d8a7db8c20d985d8aed8aad984d98120d8b1d8a720d8a7d985d8aad8add8a7d98620daa9d986db8cd8af3a20d8a8d8a7d8b2d8aad8a7d8a820d986d8a7d8a7d985db8cd8afdb8cd88c20d9bed8b1d8b3db8cd8afd98620d8afd8b1d8a8d8a7d8b1d98720d8a8d8aed8b4e2808cd987d8a7db8c20d986d8a7daa9d8a7d8b1d8a2d985d8af20d8acd984d8b3d98720d98820d8afd8b9d988d8aa20d8a8d98720d8a8d8a7d8b2d986daafd8b1db8c20d987d8afd981e2808cd987d8a72e0a0ad987d985d8aad8a7db8c20d8b4d985d8a720d985db8ce2808cd8aad988d8a7d986d8af20d8a8d8b1d8b1d8b3db8c20daa9d986d8af3a20d8a2db8cd8a720d9bed8a7d8b3d8ae20d8add8a7d984d8aa20d8afd981d8a7d8b9db8c20d8afd8a7d8b4d8aad89f20d8a2db8cd8a720d8aad8acd8b1d8a8d98720d985d8b1d8a7d8acd8b920d8b4d986db8cd8afd98720d8b4d8afd89f20d8a2db8cd8a720d9bed8b1d8b3d8b420d8a8d8b9d8afdb8c20d8b1d988d8b4d98620d98820d8a8d8a7d8b220d8a8d988d8afd89f20d985d8b9db8cd8a7d8b120d8aad985d8b1db8cd986d88c20d8add981d8b820daafd981d8aae2808cd988daafd98820d98820d987d985daa9d8a7d8b1db8c20d8a7d8b3d8aad89b20d8acd985d984d98720d986d985d988d986d987d88c20d9bed8b1d988d8aadaa9d98420db8cd8a720d9bed8a7d8b3d8ae20d985d986d8a7d8b3d8a820d8a8d8b1d8a7db8c20d987d985d98720d985d8b1d8a7d8acd8b9d8a7d98620d986db8cd8b3d8aa2e0a0a232320d8a7d988d984db8cd98620d982d8afd98520d8b4d985d8a720d8afd8b120d8a7db8cd98620d987d981d8aad9870adb8cdaa920d985d988d982d8b9db8cd8aa20d8afd8b4d988d8a7d8b120d8aadaa9d8b1d8a7d8b1d8b4d988d986d8afd98720d8b1d8a720d8a7d986d8aad8aed8a7d8a820daa9d986db8cd8af2e20d8a2d98620d8b1d8a720d8a8d98720db8cdaa920d8b1d981d8aad8a7d8b120daa9d988da86daa920d98820d982d8a7d8a8d98420d985d8b4d8a7d987d8afd98720d8aad8a8d8afdb8cd98420daa9d986db8cd8af2e20db8cdaa920d986d985d988d986d98720d8b3d8a7d8aed8aadaafdb8c20d8a8d8b1d8a7db8c20d8aad985d8b1db8cd98620d8a8d8b3d8a7d8b2db8cd8af20d98820d8a7d8b220d987d985d8aad8a7db8c20d985d986d8a7d8b3d8a820d8a8d8a7d8b2d8aed988d8b1d8af20d985d8b4d8aed8b520d8a8daafdb8cd8b1db8cd8af2e20d9bed8a7d8b3d8ae20d8b1d8a720d8a7d8b5d984d8a7d8ad20daa9d986db8cd8afd88c20d8afd988d8a8d8a7d8b1d98720d8aad985d8b1db8cd98620daa9d986db8cd8af20d98820d8afd8b120d8b2d985d8a7d98620d985d8b4d8aed8b5db8c20d9bedb8cd8b4d8b1d981d8aa20d8aed988d8af20d8b1d8a720d985d8b1d988d8b120daa9d986db8cd8af2e0a0ad8a8d8b1d8a7db8c20d985d8abd8a7d984d88c20d8a8d98720d8acd8a7db8c20d987d8afd98120c2abd8afd8b1d985d8a7d986daafd8b120d8a8d987d8aad8b1db8c20d8b4d988d985c2bbd88c20d8a8d986d988db8cd8b3db8cd8af3a20c2abd8a7db8cd98620d987d981d8aad987d88c20d8afd8b120d986d982d8b4e2808cd8a2d981d8b1db8cd986db8cd99020d985d988d8a7d8acd987d98720d8a8d8a720d986d8a7d8b1d8b6d8a7db8cd8aadb8cd88c20d9bedb8cd8b420d8a7d8b220d8aad988d8b6db8cd8ad20d8afd8a7d8afd98620db8cdaa920d9bed8b1d8b3d8b420d8a8d8a7d8b220d8afd8b1d8a8d8a7d8b1d98720d8aad8acd8b1d8a8d98720d985d8b1d8a7d8acd8b920d985d8b7d8b1d8ad20d985db8ce2808cdaa9d986d9852ec2bb20d8a7db8cd98620d987d8afd98120d8b1d8a720d985db8ce2808cd8aad988d8a7d98620d8afdb8cd8afd88c20d8aad985d8b1db8cd98620daa9d8b1d8af20d98820d8a8d8a7d8b2d986daafd8b1db8c20daa9d8b1d8af2e0a0a232320d8aad8a8d8add8b120d8add8b1d981d987e2808cd8a7db8c20db8cdaa920d985d8b3db8cd8b120d982d8a7d8a8d98420d8a8d8a7d8b2d986daafd8b1db8c20d8a7d8b3d8aa0ad8aad985d8b1db8cd98620d8b9d8a7d985d8afd8a7d986d98720d988d8b9d8afd98720d8aad8a8d8afdb8cd98420d8b3d8b1db8cd8b920d987d8b120d981d8b1d8af20d8a8d98720d8a7d8a8d8b1d8afd8b1d985d8a7d986daafd8b120d986db8cd8b3d8aa2e20d8a7d8b1d8b2d8b420d8a2d98620d8afd8b120d8afd982db8cd982e2808cd8aad8b120daa9d8b1d8afd98620d9bed8b1d8b3d8b420d8b1d8b4d8af20d8a7d8b3d8aa3a20da86d98720d985d987d8a7d8b1d8aadb8cd88c20d8a8d8a720da86d98720d8aad985d8b1db8cd986db8cd88c20d8a8d8a720daa9d8afd8a7d98520d8a8d8a7d8b2d8aed988d8b1d8af20d98820d8a8d8a720da86d98720d986d8b4d8a7d986d987e2808cd8a7db8c20d8a7d8b220d9bedb8cd8b4d8b1d981d8aad89f20d9bed8a7d8b3d8aee2808cd987d8a720d8afd8b120d8b7d988d98420d985d8b3db8cd8b120d8aad8badb8cdb8cd8b120d985db8ce2808cdaa9d986d986d8af20d98820d987d985db8cd98620d8a8d8a7d8b2d986daafd8b1db8cd88c20d8a8d8aed8b4db8c20d8a7d8b220db8cd8a7d8afdaafdb8cd8b1db8c20d8add8b1d981d987e2808cd8a7db8c20d8a7d8b3d8aa2e0a0ad8a7db8cd98620db8cd8a7d8afd8afd8a7d8b4d8aa20d8a8d8a720d986d8a7d98520d8afdaa9d8aad8b120d8b9d984db8c20d981db8cd8b6db8c20d98820d8a8d8a720d8aad987db8cd98720d98820d8aad8afd988db8cd98620d8aadb8cd98520d8b9d984d985db8c20d8a2daa9d8a7d8afd985db8c20d985d986d8aad988d8b1db8cd8b3d88c20d8a8d8b120d9bed8a7db8cd98720d985d8aad98620d8a7d8b1d8a7d8a6d987e2808cd8b4d8afd98720d98820d985d986d8a7d8a8d8b920d8b2db8cd8b120d8aad986d8b8db8cd98520d8b4d8afd98720d8a7d8b3d8aa2e USING utf8mb4),CONVERT(0x7b22696d616765223a22696d616765735c2f64656c696265726174652d70726163746963652d6a6f75726e616c2d76312e6a7067222c2263617465676f7279223a22d8b1d8b4d8af20d8add8b1d981d987e2808cd8a7db8c20d8afd8b1d985d8a7d986daafd8b1222c22726561645f74696d65223a22dbb820d8afd982db8cd982d987222c22617574686f72223a22d8afdaa9d8aad8b120d8b9d984db8c20d981db8cd8b6db8c222c22617574686f725f736c7567223a22616c692d6665697a69222c22637265646974223a22d8aad987db8cd98720d98820d8aad8afd988db8cd9863a20d8aadb8cd98520d8b9d984d985db8c20d8a2daa9d8a7d8afd985db8c20d985d986d8aad988d8b1db8cd8b3222c226b65795f706f696e7473223a5b22d8aad8acd8b1d8a8d987d88c20d981d8b1d8b5d8aa20d8b1d8b4d8af20d8a7d8b3d8aad89b20d8a8d8a7d8b2d8aed988d8b1d8af20d98820d8aad985d8b1db8cd98620d987d8afd981d985d986d8af20d8a8d98720d8a2d98620d8acd987d8aa20d985db8ce2808cd8afd987d986d8af2e222c22d985d987d8a7d8b1d8aa20d8b1d8a720daa9d988da86daa9d88c20d985d8b4d8a7d987d8afd987e2808cd9bed8b0db8cd8b120d98820d982d8a7d8a8d98420d8aadaa9d8b1d8a7d8b120d8aad8b9d8b1db8cd98120daa9d986db8cd8af2e222c22d8b9d8afd8af20d8afd987e2808cd987d8b2d8a7d8b120d8b3d8a7d8b9d8aa20d982d8a7d986d988d98620d987d985daafd8a7d986db8c20db8cd8a720d8aad8b6d985db8cd98620d8aad8a8d8add8b120d986db8cd8b3d8aa2e225d2c227265666572656e636573223a5b5b22d985db8cd984d8b120d98820d987d985daa9d8a7d8b1d8a7d986d89b20d986d988d8b4d8aad987e2808cd987d8a7db8c20d8a7d988d984db8cd98720d8afd8b1d8a8d8a7d8b1d98720d8a7d8a8d8b1d8afd8b1d985d8a7d986daafd8b1d8a7d986222c2268747470733a5c2f5c2f7777772e73636f7474646d696c6c65722e636f6d5c2f77702d636f6e74656e745c2f75706c6f6164735c2f323031345c2f30365c2f5375706572736872696e6b732d467265652d5265706f72742d312e706466225d2c5b2243686f7720657420616c2e20283230313529d89b20d8aad985d8b1db8cd98620d8b9d8a7d985d8afd8a7d986d98720d98820d8a7d8abd8b1d8a8d8aed8b4db8c20d8b1d988d8a7d986e2808cd8afd8b1d985d8a7d986daafd8b1d8a7d986222c2268747470733a5c2f5c2f7075626d65642e6e6362692e6e6c6d2e6e69682e676f765c2f32363330313432355c2f225d2c5b224572696373736f6e20283230313929d89b20d8aad8b9d8b1db8cd98120d8aad985d8b1db8cd98620d8b9d8a7d985d8afd8a7d986d98720d98820d8aad981d8b3db8cd8b120d9beda98d988d987d8b4e2808cd987d8a7db8c20d8aad8a8d8add8b1222c2268747470733a5c2f5c2f706d632e6e6362692e6e6c6d2e6e69682e676f765c2f61727469636c65735c2f504d43363832343431315c2f225d5d2c2272656c617465645f636f75727365223a2270737963686f746865726170792d6d6173746572792d64656c696265726174652d7072616374696365222c226665617475726564223a747275657d USING utf8mb4),UTC_TIMESTAMP(),UTC_TIMESTAMP() FROM content_entities ce WHERE ce.entity_type=CONVERT(0x61727469636c65 USING utf8mb4) AND ce.slug=CONVERT(0x64656c696265726174652d70726163746963652d66697273742d737465702d6d617374657279 USING utf8mb4);

COMMIT;

-- Shared messaging and support (010)
CREATE TABLE IF NOT EXISTS comm_accounts (
 channel VARCHAR(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 chat_id VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 display_name VARCHAR(120) NOT NULL DEFAULT '',
 user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL,
 auth_version INT UNSIGNED NULL,
 link_method VARCHAR(16) NOT NULL DEFAULT '',
 state VARCHAR(40) NOT NULL DEFAULT '',
 state_data LONGTEXT NOT NULL,
 notifications_enabled TINYINT(1) NOT NULL DEFAULT 1,
 admin_alerts TINYINT(1) NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
 PRIMARY KEY(channel,chat_id), UNIQUE KEY comm_account_user(channel,user_id),
 CONSTRAINT fk_comm_account_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comm_links (
 token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
 user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 channel VARCHAR(16) NOT NULL, auth_version INT UNSIGNED NOT NULL,
 expires_at DATETIME NOT NULL, used_at DATETIME NULL, created_at DATETIME NOT NULL,
 CONSTRAINT fk_comm_link_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 INDEX idx_comm_link_expiry(expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comm_updates (
 channel VARCHAR(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 update_id BIGINT NOT NULL, payload LONGTEXT NOT NULL,
 status VARCHAR(20) NOT NULL DEFAULT 'queued', attempts INT UNSIGNED NOT NULL DEFAULT 0,
 next_attempt_at DATETIME NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
 PRIMARY KEY(channel,update_id), INDEX idx_comm_updates_queue(status,next_attempt_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comm_outbox (
 id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
 channel VARCHAR(16) NOT NULL, chat_id VARCHAR(32) NOT NULL,
 method VARCHAR(40) NOT NULL, payload LONGTEXT NOT NULL,
 dedupe_key VARCHAR(190) CHARACTER SET ascii COLLATE ascii_bin NULL,
 user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL, auth_version INT UNSIGNED NULL,
 required_permission VARCHAR(64) NOT NULL DEFAULT '',
 status VARCHAR(20) NOT NULL DEFAULT 'queued', attempts INT UNSIGNED NOT NULL DEFAULT 0,
 next_attempt_at DATETIME NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
 UNIQUE KEY comm_outbox_dedupe(channel,chat_id,dedupe_key), INDEX idx_comm_outbox_queue(status,next_attempt_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comm_actions (
 id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
 channel VARCHAR(16) NOT NULL, chat_id VARCHAR(32) NOT NULL,
 user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL, auth_version INT UNSIGNED NOT NULL,
 action VARCHAR(32) NOT NULL, payload LONGTEXT NOT NULL,
 expires_at DATETIME NOT NULL, used_at DATETIME NULL, created_at DATETIME NOT NULL,
 INDEX idx_comm_action_expiry(expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_tickets (
 id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
 user_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL,
 channel VARCHAR(16) NOT NULL, chat_id VARCHAR(32) NULL,
 subject VARCHAR(160) NOT NULL, category VARCHAR(32) NOT NULL DEFAULT 'general',
 status VARCHAR(20) NOT NULL DEFAULT 'open',
 created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
 CONSTRAINT fk_support_ticket_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
 INDEX idx_support_ticket_user(user_id,updated_at), INDEX idx_support_ticket_status(status,updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_messages (
 sequence_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
 id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
 ticket_id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 author_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL,
 sender_role VARCHAR(16) NOT NULL, body LONGTEXT NOT NULL,
 source_key VARCHAR(190) CHARACTER SET ascii COLLATE ascii_bin NULL,
 created_at DATETIME NOT NULL,
 CONSTRAINT fk_support_message_ticket FOREIGN KEY(ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
 UNIQUE KEY support_message_source(source_key), INDEX idx_support_messages(ticket_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_attachments (
 id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
 message_id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 channel VARCHAR(16) NOT NULL, file_id VARCHAR(512) NULL, local_name VARCHAR(100) NULL,
 filename VARCHAR(190) NOT NULL, mime_type VARCHAR(100) NOT NULL,
 byte_size INT UNSIGNED NOT NULL, media_kind VARCHAR(16) NOT NULL,
 created_at DATETIME NOT NULL,
 CONSTRAINT fk_support_attachment_message FOREIGN KEY(message_id) REFERENCES support_messages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO migrations (migration,executed_at) VALUES ('010_communications_hub.mysql.sql',UTC_TIMESTAMP());
CREATE TABLE IF NOT EXISTS circle_applications (
    signup_id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    details JSON NOT NULL,
    resume JSON NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT circle_applications_signup_fk FOREIGN KEY (signup_id) REFERENCES event_signups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO migrations (migration,executed_at) VALUES ('011_circle_applications.mysql.sql',UTC_TIMESTAMP());

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

INSERT IGNORE INTO migrations (migration,executed_at) VALUES ('012_manual_payments.mysql.sql',UTC_TIMESTAMP());

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
INSERT IGNORE INTO migrations (migration,executed_at) VALUES ('013_discount_codes.mysql.sql',UTC_TIMESTAMP());

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

INSERT IGNORE INTO migrations (migration,executed_at) VALUES ('014_payment_refunds.mysql.sql',UTC_TIMESTAMP());

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
