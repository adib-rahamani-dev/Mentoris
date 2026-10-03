<?php
declare(strict_types=1);
namespace App\Services;

final class ContentWorkspaceService
{
    public const MODULES = [
        'articles'=>['type'=>'article','label'=>'مقالات','singular'=>'مقاله','icon'=>'book','hint'=>'عنوان روشن، خلاصهٔ مستقل و متن خواندنی بنویسید. هر پاراگراف را با یک خط خالی جدا کنید.','fields'=>['image','image_upload','category','read_time','author','featured']],
        'events'=>['type'=>'event','label'=>'رویدادها','singular'=>'رویداد','icon'=>'calendar','hint'=>'تاریخ، مکان و وضعیت ثبت‌نام را مشخص کنید. رویدادهای برگزارشده را «تمام‌شده» قرار دهید.','fields'=>['image','image_upload','category','duration','schedule','format','location','starts_at','ends_at','price_amount','capacity','line_slug','instructor_slug','content_status','registration_url','highlights','related_mentors','featured']],
        'courses'=>['type'=>'course','label'=>'دوره‌ها','singular'=>'دوره','icon'=>'certificate','hint'=>'مخاطب، سطح و برنامهٔ یادگیری دوره را توضیح دهید.','fields'=>['image','image_upload','category','duration','schedule','format','price_amount','capacity','line_slug','instructor_slug','content_status','level','registration_url','audience','related_mentors','featured']],
        'programs'=>['type'=>'program','label'=>'برنامه‌ها','singular'=>'برنامه','icon'=>'trending','hint'=>'اهداف، مخاطبان و زمان‌بندی برنامه را در بخش‌های مشخص وارد کنید.','fields'=>['image','image_upload','category','duration','schedule','format','line_slug','target_audience','objectives','related_courses','related_events','related_mentors','featured']],
        'mentors'=>['type'=>'mentor','label'=>'مدرسان','singular'=>'مدرس','icon'=>'user','hint'=>'نام، معرفی، تخصص و تصویر مدرس را وارد کنید.','fields'=>['image','image_upload','category','related_courses','related_events','featured']],
        'academy-lines'=>['type'=>'academy_line','label'=>'لاین‌های آکادمی','singular'=>'لاین آکادمی','icon'=>'brain','hint'=>'پیام اصلی لاین و مسیرهای یادگیری مرتبط را مشخص کنید.','fields'=>['image','image_upload','tone','icon','promise','related_courses','related_events','related_mentors','featured']],
        'specializations'=>['type'=>'specialization','label'=>'تخصص‌ها','singular'=>'تخصص','icon'=>'heart','hint'=>'تخصص و آموزش‌های مرتبط با آن را معرفی کنید.','fields'=>['image','image_upload','category','tone','icon','related_courses','related_events','related_mentors','featured']],
    ];
    public static function path(string $type): string
    {
        foreach(self::MODULES as $slug=>$module) if($module['type']===$type) return '/admin/'.$slug;
        return '/admin/content';
    }
}
