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
