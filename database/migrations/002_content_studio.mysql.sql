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
