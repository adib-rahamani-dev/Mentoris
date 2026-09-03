# راه‌اندازی امن Mentoris روی Laragon و MySQL

## فایل‌های اصلی دیتابیس

- ساختار کامل MySQL: `database/migrations/001_core.mysql.sql`
- نسخه تست SQLite: `database/migrations/001_core.sqlite.sql`
- ابزار اجرای migration: `php bin/console migrate`

اطلاعات واقعی کاربران، نشست‌ها، سفارش‌ها و فرم‌ها در Git ذخیره نمی‌شوند. فایل `.env` نیز در `.gitignore` است.

## اجرای محلی

1. در Laragon سرویس‌های **Apache** و **MySQL** را Start کنید.
2. پروژه باید در `C:\laragon\www\Mentoris` باشد.
3. فایل `.env.example` را به `.env` کپی و مقادیر `DB_*` را مطابق MySQL خود تنظیم کنید.
4. کلید برنامه را فقط یک‌بار بسازید:

   ```powershell
   php bin/console key:generate
   ```

5. دیتابیس را بسازید و جداول را اجرا کنید:

   ```powershell
   php bin/console db:create
   php bin/console migrate
   php bin/console db:check
   php bin/console migrate:status
   ```

6. برای تست سریع بدون Virtual Host:

   ```powershell
   php -S 127.0.0.1:8090 -t public public/router.php
   ```

   سپس `http://127.0.0.1:8090` را باز کنید. در Laragon معمولاً `http://mentoris.test` نیز در دسترس است.

## ساخت مدیر کل

1. ابتدا از صفحه `/register` حساب بسازید.
2. سپس همان ایمیل را ارتقا دهید:

   ```powershell
   php bin/console admin:promote your-email@example.com
   ```

3. دوباره وارد شوید و `/admin` را باز کنید. تغییر نقش کاربران فقط از حساب دارای مجوز انجام می‌شود و در `audit_logs` ثبت می‌گردد.

## تنظیم production

- `APP_ENV=production` و `APP_DEBUG=false`
- `APP_URL` برابر دامنه HTTPS واقعی
- `SESSION_DRIVER=database` و `RATE_LIMIT_DRIVER=database`
- یک `APP_KEY` متفاوت و محرمانه
- کاربر MySQL اختصاصی، رمز قوی، دسترسی فقط به دیتابیس Mentoris و TLS (`DB_SSL_CA`) برای دیتابیس راه دور
- Document Root دقیقاً پوشه `public` باشد؛ هیچ‌گاه ریشه پروژه را public نکنید.
- پس از هر deploy، `php bin/console migrate` اجرا شود.
- فایل `.env`، بکاپ‌ها، logها و کلید درگاه پرداخت نباید commit یا داخل `public` قرار گیرند.

## اجرای تست‌ها

```powershell
Get-ChildItem tests -Filter *Test.php | ForEach-Object { php $_.FullName }
```

این تست‌ها مهاجرت، foreign key، SQL injection، رمزنگاری نشست، rate limit، احراز هویت، پرداخت اتمیک، SEO، تم و محتوای عمومی را بررسی می‌کنند.
