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

## حلقه درمانگران و صفحه سالن

- عضویت عمومی `/register` و پنل `/dashboard` مستقل از هر رویداد هستند.
- صفحه نشست دوم در `/events/therapists-circle-second` درخواست حضور با نام، موبایل و شهر می‌گیرد. تا زمانی که تاریخ و فرآیند نهایی اعلام نشود، این درخواست بلیت یا ثبت‌نام قطعی محسوب نمی‌شود.
- صفحه بازخورد نشست در `/feedback` است. ورود حاضران به شماره ثبت‌شده و `FEEDBACK_ACCESS_CODE` نیاز دارد؛ برای سازگاری، `LIVE_ACCESS_CODE` قبلی نیز تا زمان تنظیم متغیر جدید پذیرفته می‌شود. کد را فقط در سالن اعلام کنید.
- فرم بازخورد در `/feedback/submit` ثبت می‌شود. دانلود PDF پس از ساخت حساب و تکمیل پروفایل تخصصی باز می‌شود. مدیر حضور واقعی را در `/admin/engagements?type=circle` با وضعیت `attended` تأیید می‌کند؛ سپس گواهی قابل رهگیری در `/my-certificates` صادر می‌شود.
- فایل شیت کارگاه در `output/pdf/anchoring-grace-toolkit.pdf` است. کد QR جدید برای `/feedback` را در `/admin/qr` بسازید، SVG دریافت کنید یا کارت را چاپ کنید. کدهای QR قدیمی که به `/live` اشاره می‌کنند باید جایگزین شوند.
- `/live` برای پروژه پخش زنده مستقل آماده شده است. مقدار `LIVE_STREAM_STATUS=scheduled` را تا شروع برنامه نگه دارید. برای آپارات، `LIVE_STREAM_PROVIDER=aparat` و `LIVE_STREAM_APARAT_USERNAME` را تنظیم کنید؛ برای YouTube Live، `LIVE_STREAM_PROVIDER=youtube` و شناسه ۱۱ نویسه‌ای ویدیو را در `LIVE_STREAM_YOUTUBE_ID` بگذارید. هنگام شروع `LIVE_STREAM_STATUS=live` و بعد از پایان `ended` را تنظیم کنید. صفحه فقط در حالت `live` و با شناسه معتبر پخش‌کننده را نمایش می‌دهد. برای پخش واقعی، حساب سرویس پخش، نرم‌افزار رمزگذار مانند OBS و اتصال اینترنت پایدار لازم است؛ PHP فقط صفحه و تنظیمات را مدیریت می‌کند.
- جدول‌های این جریان در `003_therapist_circle.mysql.sql` هستند. اجرای `php bin/console migrate` به حساب MySQL دارای مجوز `CREATE` نیاز دارد. برنامه تا پیش از اعمال مایگریشن، فرم‌های جدید را فعال نمی‌کند.
- درگاه پرداخت، OTP و پیامک خودکار در این نسخه به این جریان متصل نیستند.
