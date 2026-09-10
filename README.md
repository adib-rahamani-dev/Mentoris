# Mentoris Academy

وب‌سایت و پنل اختصاصی Mentoris با PHP 8.1+ و MySQL 8.

## اجرای محلی

Laragon را اجرا کنید و Apache و MySQL را روشن نگه دارید. آدرس اصلی محلی:

```text
http://mentoris.test
```

برای اجرای مستقیم PHP:

```powershell
php -S 127.0.0.1:8090 -t public public/router.php
```

فایل محرمانه `.env` عمداً در Git قرار نمی‌گیرد. Document Root وب‌سرور باید همیشه پوشه `public` باشد.

## دیتابیس

ساختار MySQL در `database/migrations/001_core.mysql.sql` قرار دارد. این فایل فاقد رمز و اطلاعات کاربری است و برای انتقال دیتابیس به هاست نگهداری می‌شود.

فرمان‌های مدیریتی فقط از ترمینال سرور قابل اجرا هستند:

```powershell
php bin/console db:check
php bin/console migrate:status
php bin/console migrate
php bin/console admin:promote email@example.com
```

## پنل‌ها

- `/login` ورود
- `/dashboard` پنل کاربر
- `/admin` مدیریت
- `/admin/users` کاربران و نقش‌ها
- `/admin/content` وضعیت محتوا
- `/admin/analytics` آمار

اطلاعات احراز هویت، نشست‌ها، درخواست‌ها، سفارش‌ها و پرداخت‌ها فقط در MySQL ذخیره می‌شوند.
