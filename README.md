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

ساختار پایه MySQL در `database/migrations/001_core.mysql.sql` قرار دارد. ارتقای امن Content Studio برای دیتابیس‌های قدیمی در `database/migrations/002_content_studio.mysql.sql` است. مایگریشن‌ها افزایشی‌اند، رمز یا اطلاعات کاربری ندارند و داده‌های موجود را پاک نمی‌کنند.

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
- `/admin/users/new` ساخت حساب مدرس، مدیر، پشتیبان یا کاربر
- `/admin/users/{id}` پرونده، دسترسی، سوابق و اعلان مستقیم کاربر
- `/admin/orders` سفارش‌ها، تراکنش‌ها و ثبت‌نام‌های متصل
- `/admin/engagements` پیام‌ها، ثبت‌نام رویداد و درخواست عضویت جامعه
- `/admin/content` استودیوی محتوای چهارزبانه و چرخه انتشار
- `/admin/analytics` آمار
- `/admin/audit` گزارش تغییرات حساس مدیران
- `/admin/system` وضعیت دیتابیس، نشست‌ها، Rate Limit و امنیت اجرا

هویت بصری جدید در `public/assets/css/brand-2026.css` قرار دارد و تصاویر اصلی در `public/assets/images` نگهداری می‌شوند. حالت روشن، حالت پیش‌فرض است و انتخاب روشن/تیره کاربر در مرورگر حفظ می‌شود.

آیکون‌ها به‌صورت SVG داخلی، بدون فونت آیکون یا وابستگی CDN، در تابع امن `icon()` تعریف شده‌اند. Session فایل نیز مستقل از تنظیمات XAMPP/Laragon در `storage/sessions` ذخیره می‌شود؛ در صورت نیاز می‌توان مسیر را با `SESSION_FILE_PATH` تغییر داد.

اطلاعات احراز هویت، نشست‌ها، درخواست‌ها، سفارش‌ها و پرداخت‌ها فقط در MySQL ذخیره می‌شوند.

استودیوی محتوا پروفایل مدرس، مقاله، دوره، رویداد، برنامه، تخصص و لاین آکادمی را مدیریت می‌کند. تصویر JPEG/PNG/WebP را می‌توان مستقیم از فرم بارگذاری کرد؛ فایل با نام تصادفی و پس از بررسی MIME، حجم و ابعاد در `public/assets/images/uploads` ذخیره می‌شود.

## انتشار

- روی Apache/Nginx، Document Root باید دقیقاً `public` باشد.
- روی Vercel، اجرای PHP از `api/index.php` انجام می‌شود؛ دیتابیس باید MySQL راه‌دور باشد و فضای فایل Vercel برای آپلود دائمی مناسب نیست.
- پیش از انتشار واقعی، `APP_ENV=production`، `APP_DEBUG=false`، `APP_URL` دامنه HTTPS و یک `APP_KEY` تصادفی تنظیم شود.
- سپس `php bin/console migrate` و `php bin/console migrate:status` اجرا شود.
- نقشه ثابت در `/sitemap.xml` و نقشه محتوای منتشرشده در `/sitemap-content.xml` در دسترس است.
