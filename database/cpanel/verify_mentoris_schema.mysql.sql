-- Read-only verification of the latest Mentoris schema (2026-10-16).
SET NAMES utf8mb4;

SELECT expected.table_name,
       CASE WHEN actual.table_name IS NULL THEN 'MISSING' ELSE 'OK' END AS status
FROM (
    SELECT 'migrations' AS table_name
    UNION ALL SELECT 'users'
    UNION ALL SELECT 'password_reset_tokens'
    UNION ALL SELECT 'notifications'
    UNION ALL SELECT 'orders'
    UNION ALL SELECT 'payment_transactions'
    UNION ALL SELECT 'inventory_locks'
    UNION ALL SELECT 'enrollments'
    UNION ALL SELECT 'event_registrations'
    UNION ALL SELECT 'certificates'
    UNION ALL SELECT 'community_memberships'
    UNION ALL SELECT 'contact_messages'
    UNION ALL SELECT 'rate_limits'
    UNION ALL SELECT 'sessions'
    UNION ALL SELECT 'content_entities'
    UNION ALL SELECT 'content_translations'
    UNION ALL SELECT 'content_relations'
    UNION ALL SELECT 'audit_logs'
    UNION ALL SELECT 'therapist_profiles'
    UNION ALL SELECT 'event_signups'
    UNION ALL SELECT 'event_feedback'
    UNION ALL SELECT 'event_certificates'
    UNION ALL SELECT 'member_profiles'
    UNION ALL SELECT 'telegram_users'
    UNION ALL SELECT 'telegram_updates'
    UNION ALL SELECT 'telegram_outbox'
    UNION ALL SELECT 'telegram_questions'
    UNION ALL SELECT 'telegram_event_requests'
    UNION ALL SELECT 'resource_downloads'
    UNION ALL SELECT 'survey_responses'
    UNION ALL SELECT 'feedback_windows'
    UNION ALL SELECT 'phone_verifications'
    UNION ALL SELECT 'sms_challenges'
    UNION ALL SELECT 'sms_deliveries'
    UNION ALL SELECT 'registration_sms_challenges'
    UNION ALL SELECT 'course_requests'
    UNION ALL SELECT 'support_attachments'
    UNION ALL SELECT 'circle_applications'
    UNION ALL SELECT 'manual_payments'
    UNION ALL SELECT 'discount_codes'
    UNION ALL SELECT 'discount_redemptions'
    UNION ALL SELECT 'payment_refunds'
    UNION ALL SELECT 'support_messages'
    UNION ALL SELECT 'support_tickets'
    UNION ALL SELECT 'comm_actions'
    UNION ALL SELECT 'comm_outbox'
    UNION ALL SELECT 'comm_updates'
    UNION ALL SELECT 'comm_links'
    UNION ALL SELECT 'comm_accounts'
) AS expected
LEFT JOIN information_schema.tables AS actual
    ON actual.table_schema = DATABASE()
   AND actual.table_name = expected.table_name
ORDER BY expected.table_name;

SELECT COUNT(*) AS installed_mentoris_tables,
       49 AS expected_mentoris_tables,
       CASE WHEN COUNT(*) = 49 THEN 'SCHEMA_OK' ELSE 'SCHEMA_INCOMPLETE' END AS result
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name IN (
      'migrations',
      'users',
      'password_reset_tokens',
      'notifications',
      'orders',
      'payment_transactions',
      'inventory_locks',
      'enrollments',
      'event_registrations',
      'certificates',
      'community_memberships',
      'contact_messages',
      'rate_limits',
      'sessions',
      'content_entities',
      'content_translations',
      'content_relations',
      'audit_logs',
      'therapist_profiles',
      'event_signups',
      'event_feedback',
      'event_certificates',
      'member_profiles',
      'telegram_users',
      'telegram_updates',
      'telegram_outbox',
      'telegram_questions',
      'telegram_event_requests',
      'resource_downloads',
      'survey_responses',
      'feedback_windows',
      'phone_verifications',
      'sms_challenges',
      'sms_deliveries',
      'registration_sms_challenges',
      'course_requests',
      'support_attachments',
      'circle_applications',
      'manual_payments',
      'discount_codes',
      'discount_redemptions',
      'payment_refunds',
      'support_messages',
      'support_tickets',
      'comm_actions',
      'comm_outbox',
      'comm_updates',
      'comm_links',
      'comm_accounts'
  );
