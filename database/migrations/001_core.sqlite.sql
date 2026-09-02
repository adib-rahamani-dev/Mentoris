CREATE TABLE users (id TEXT PRIMARY KEY, name TEXT NOT NULL, email TEXT NOT NULL COLLATE NOCASE UNIQUE, password_hash TEXT NOT NULL, phone TEXT NOT NULL DEFAULT '', professional_role TEXT NOT NULL DEFAULT '', bio TEXT NOT NULL DEFAULT '', account_role TEXT NOT NULL DEFAULT 'student' CHECK (account_role IN ('super_admin','admin','editor','instructor','support','student')), status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active','suspended')), auth_version INTEGER NOT NULL DEFAULT 1, email_verified_at TEXT NULL, last_login_at TEXT NULL, password_changed_at TEXT NULL, created_at TEXT NOT NULL, updated_at TEXT NOT NULL);
CREATE INDEX users_role_status_index ON users(account_role, status);
CREATE INDEX users_created_at_index ON users(created_at);

CREATE TABLE password_reset_tokens (id TEXT PRIMARY KEY, user_id TEXT NOT NULL, token_hash TEXT NOT NULL UNIQUE, expires_at TEXT NOT NULL, used_at TEXT NULL, created_at TEXT NOT NULL, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE);
CREATE INDEX password_reset_user_expiry_index ON password_reset_tokens(user_id, expires_at);

CREATE TABLE notifications (id TEXT PRIMARY KEY, user_id TEXT NOT NULL, title TEXT NOT NULL, message TEXT NOT NULL, read_at TEXT NULL, created_at TEXT NOT NULL, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE);
CREATE INDEX notifications_user_read_index ON notifications(user_id, read_at, created_at);

CREATE TABLE orders (id TEXT PRIMARY KEY, order_number TEXT NOT NULL UNIQUE, user_id TEXT NOT NULL, customer_name TEXT NOT NULL, customer_email TEXT NOT NULL, item_type TEXT NOT NULL, item_id TEXT NOT NULL, item_title TEXT NOT NULL, amount INTEGER NOT NULL CHECK (amount >= 0), currency TEXT NOT NULL DEFAULT 'IRT', status TEXT NOT NULL DEFAULT 'pending', expires_at TEXT NOT NULL, paid_at TEXT NULL, created_at TEXT NOT NULL, updated_at TEXT NOT NULL, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT);
CREATE INDEX orders_user_created_index ON orders(user_id, created_at);
CREATE INDEX orders_inventory_index ON orders(item_type, item_id, status, expires_at);

CREATE TABLE payment_transactions (id TEXT PRIMARY KEY, order_id TEXT NOT NULL, gateway TEXT NOT NULL, authority TEXT NOT NULL UNIQUE, amount INTEGER NOT NULL CHECK (amount >= 0), currency TEXT NOT NULL DEFAULT 'IRT', status TEXT NOT NULL DEFAULT 'initiated', reference_id TEXT NULL UNIQUE, message TEXT NULL, gateway_response TEXT NOT NULL, verified_at TEXT NULL, created_at TEXT NOT NULL, updated_at TEXT NOT NULL, FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT);
CREATE INDEX transactions_order_created_index ON payment_transactions(order_id, created_at);

CREATE TABLE inventory_locks (item_type TEXT NOT NULL, item_id TEXT NOT NULL, updated_at TEXT NOT NULL, PRIMARY KEY (item_type, item_id));
CREATE TABLE enrollments (id TEXT PRIMARY KEY, user_id TEXT NOT NULL, course_slug TEXT NOT NULL, order_id TEXT NULL, status TEXT NOT NULL DEFAULT 'active', enrolled_at TEXT NOT NULL, UNIQUE (user_id, course_slug), FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL);
CREATE INDEX enrollments_course_status_index ON enrollments(course_slug, status);

CREATE TABLE event_registrations (id TEXT PRIMARY KEY, user_id TEXT NULL, event_slug TEXT NOT NULL, applicant_name TEXT NOT NULL, applicant_email TEXT NOT NULL COLLATE NOCASE, applicant_phone TEXT NOT NULL DEFAULT '', professional_role TEXT NOT NULL DEFAULT '', status TEXT NOT NULL DEFAULT 'pending', created_at TEXT NOT NULL, updated_at TEXT NOT NULL, UNIQUE (event_slug, applicant_email), FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL);
CREATE TABLE certificates (id TEXT PRIMARY KEY, user_id TEXT NOT NULL, course_slug TEXT NOT NULL, certificate_number TEXT NOT NULL UNIQUE, issued_at TEXT NOT NULL, revoked_at TEXT NULL, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT);
CREATE TABLE community_memberships (id TEXT PRIMARY KEY, user_id TEXT NULL, name TEXT NOT NULL, email TEXT NOT NULL COLLATE NOCASE UNIQUE, professional_role TEXT NOT NULL DEFAULT '', interests TEXT NOT NULL DEFAULT '', status TEXT NOT NULL DEFAULT 'pending', created_at TEXT NOT NULL, updated_at TEXT NOT NULL, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL);
CREATE TABLE contact_messages (id TEXT PRIMARY KEY, name TEXT NOT NULL, email TEXT NOT NULL, phone TEXT NOT NULL DEFAULT '', subject TEXT NOT NULL, message TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'new', ip_hash TEXT NOT NULL, created_at TEXT NOT NULL, updated_at TEXT NOT NULL);

CREATE TABLE rate_limits (key_hash TEXT PRIMARY KEY, hits INTEGER NOT NULL, reset_at TEXT NOT NULL, updated_at TEXT NOT NULL);
CREATE INDEX rate_limits_reset_index ON rate_limits(reset_at);
CREATE TABLE sessions (id_hash TEXT PRIMARY KEY, payload BLOB NOT NULL, last_activity TEXT NOT NULL, expires_at TEXT NOT NULL);
CREATE INDEX sessions_expiry_index ON sessions(expires_at);

CREATE TABLE content_entities (id TEXT PRIMARY KEY, entity_type TEXT NOT NULL, slug TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'draft', sort_order INTEGER NOT NULL DEFAULT 0, author_id TEXT NULL, published_at TEXT NULL, created_at TEXT NOT NULL, updated_at TEXT NOT NULL, UNIQUE (entity_type, slug), FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL);
CREATE INDEX content_status_sort_index ON content_entities(entity_type, status, sort_order);
CREATE TABLE content_translations (id TEXT PRIMARY KEY, entity_id TEXT NOT NULL, locale TEXT NOT NULL, title TEXT NOT NULL, subtitle TEXT NULL, excerpt TEXT NULL, body TEXT NULL, metadata TEXT NOT NULL, created_at TEXT NOT NULL, updated_at TEXT NOT NULL, UNIQUE (entity_id, locale), FOREIGN KEY (entity_id) REFERENCES content_entities(id) ON DELETE CASCADE);
CREATE TABLE content_relations (source_id TEXT NOT NULL, target_id TEXT NOT NULL, relation_type TEXT NOT NULL, sort_order INTEGER NOT NULL DEFAULT 0, PRIMARY KEY (source_id, target_id, relation_type), FOREIGN KEY (source_id) REFERENCES content_entities(id) ON DELETE CASCADE, FOREIGN KEY (target_id) REFERENCES content_entities(id) ON DELETE CASCADE);
CREATE TABLE audit_logs (id TEXT PRIMARY KEY, actor_id TEXT NULL, action TEXT NOT NULL, subject_type TEXT NOT NULL, subject_id TEXT NULL, old_values TEXT NULL, new_values TEXT NULL, ip_hash TEXT NOT NULL, created_at TEXT NOT NULL, FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL);
CREATE INDEX audit_actor_created_index ON audit_logs(actor_id, created_at);
CREATE INDEX audit_subject_index ON audit_logs(subject_type, subject_id, created_at);
