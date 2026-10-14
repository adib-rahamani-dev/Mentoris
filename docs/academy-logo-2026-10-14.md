# لوگوی سایت منتوریس

نشان مخصوص سایت از تصویر رباتِ ارائه‌شده توسط کاربر ساخته شد. نشانه M و برگ‌های رشد حفظ شدند؛ قاب ربات، آنتن، نشان تلگرام و نوشته Telegram Bot از نسخه سایت حذف شدند. نام Mentoris Academy در سایت با متن واقعی کنار تصویر نمایش داده می‌شود و داخل تصویر کوچک قرار نگرفته است.

## فایل‌ها و مصرف

- اصلی شفاف: `public/assets/images/brand/mentoris-academy-mark-source-v1.png`
- نشان ۱۲۸ پیکسلی WebP با حجم حدود ۶ کیلوبایت برای سربرگ، فوتر و مدیریت.
- PNG در اندازه‌های ۴۸، ۱۸۰، ۱۹۲ و ۵۱۲ برای آیکون مرورگر، Apple touch، manifest و لوگوی Organization در داده ساختاریافته.
- در زمینه تیره، نشان روی سطح کرم کوچک قرار می‌گیرد تا خوانا باشد. متن کنار نشان همچنان زنده و دسترس‌پذیر است.
- این تغییر SQL ندارد. فایل ربات اصلی تغییری نکرده و تنظیم عکس ربات در تلگرام تغییر داده نشده است.

## بررسی نمایش

صفحه عمومی در موبایل ۳۹۰ پیکسل و دسکتاپ ۱۳۶۵ پیکسل، سربرگ و فوتر در زمینه تیره و صفحه بررسی درخواست در مدیریت بررسی شدند. فایل‌ها بارگذاری شدند و سرریز افقی یا خطای JavaScript دیده نشد. شفافیت PNG اصلی و نسخه‌ها بررسی شد. ساختار manifest و لوگوی ۵۱۲ پیکسلی Organization نیز به فایل‌های واقعی جدید اشاره می‌کنند. داشبورد آماری روی دیتابیس آزمایشی SQLite از توابع MySQL پشتیبانی نمی‌کند؛ بررسی لوگوی مدیریت روی صفحه درخواست انجام شد.

## ابزار و پرامپت نهایی

ابزار داخلی `image_gen` در حالت ویرایش، با پس‌زمینه شفاف استفاده شد. نسخه‌های کوچک با حفظ کانال آلفا برای مصرف وب آماده شدند.

> Use case: precise-object-edit / logo-brand. Edit target: the attached Mentoris Telegram bot emblem. Create the primary WEBSITE emblem for Mentoris Academy derived from this logo. Preserve the recognizable bold symmetrical capital M geometry and the two-leaf botanical growth motif inside its lower center. Remove the entire robot head outline, ear/headphone shapes, antenna, gold antenna dot, outer circular border, Telegram paper-plane badge, and all lettering. The result is ONLY a refined freestanding M with botanical leaves, no text. Website palette: solid deep forest green #244C41 for the M, muted warm antique gold #B7964A for the two leaves/stem. Clean flat vector-like shapes, crisp polished geometry, balanced leaf negative spaces and carefully aligned baselines. Strong simple silhouette readable at 32 and 48 pixels. Square composition with the mark filling approximately 82 percent of the canvas and consistent modest transparent padding. Genuinely transparent alpha background. No white background, no mockup, no shadows, no gradients, no texture, no decorative frame. This is a human professional academy brand, not a bot icon. Output one finished production emblem.
