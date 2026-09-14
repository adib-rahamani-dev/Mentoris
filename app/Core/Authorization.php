<?php

declare(strict_types=1);

namespace App\Core;

final class Authorization
{
    public const ROLES = [
        'super_admin' => 'مدیر کل',
        'admin' => 'مدیر',
        'editor' => 'مدیر محتوا',
        'instructor' => 'مدرس',
        'support' => 'پشتیبان',
        'student' => 'کاربر',
    ];

    private const PERMISSIONS = [
        'super_admin' => ['*'],
        'admin' => ['admin.access', 'analytics.view', 'users.view', 'users.manage', 'content.view', 'content.manage', 'orders.view', 'engagements.view', 'engagements.manage', 'notifications.manage', 'audit.view', 'system.view', 'settings.manage'],
        'editor' => ['admin.access', 'analytics.view', 'content.view', 'content.manage'],
        'instructor' => ['admin.access', 'analytics.view', 'content.view'],
        'support' => ['admin.access', 'analytics.view', 'users.view', 'engagements.view', 'engagements.manage', 'notifications.manage'],
        'student' => [],
    ];

    public static function role(array $user): string
    {
        $role = (string) ($user['account_role'] ?? 'student');
        return array_key_exists($role, self::ROLES) ? $role : 'student';
    }

    public static function can(array $user, string $permission): bool
    {
        if (($user['status'] ?? 'active') !== 'active') return false;
        $permissions = self::PERMISSIONS[self::role($user)] ?? [];
        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    public static function roleLabel(string $role): string
    {
        return self::ROLES[$role] ?? self::ROLES['student'];
    }

    public static function permissionsForRole(string $role): array
    {
        return self::PERMISSIONS[$role] ?? [];
    }
}
