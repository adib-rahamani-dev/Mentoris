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

CREATE TABLE IF NOT EXISTS inventory_locks (
    item_type VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    item_id VARCHAR(190) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (item_type, item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS rate_limits (
    key_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    hits INT UNSIGNED NOT NULL,
    reset_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY rate_limits_reset_index (reset_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sessions (
    id_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    payload LONGBLOB NOT NULL,
    last_activity DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    KEY sessions_expiry_index (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
