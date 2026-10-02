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
