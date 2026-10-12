<?php
declare(strict_types=1);
namespace App\Services;

final class ContentWorkspaceService
{
    public const MODULES = [
        'articles'=>['type'=>'article','label'=>'مقالات','singular'=>'مقاله','icon'=>'book','hint'=>'عنوان و خلاصه را بنویسید؛ برای هر تیتر متن از ## استفاده کنید.','fields'=>['image','image_upload','category','read_time','author','author_slug','credit','key_points','featured']],
        'events'=>['type'=>'event','label'=>'رویدادها','singular'=>'رویداد','icon'=>'calendar','hint'=>'پوستر، زمان، هزینه و وضعیت درخواست حضور را در سه قدم تکمیل کنید.','fields'=>['image','image_upload','category','format','location','location_pending','starts_at','ends_at','display_date','time','price_amount','capacity','line_slug','instructor_slug','content_status','registration_note','highlights','related_mentors','featured']],
        'courses'=>['type'=>'course','label'=>'دوره‌ها','singular'=>'دوره','icon'=>'certificate','hint'=>'معرفی دوره، پوستر، سرفصل‌ها و شیوه پرداخت را با فرم‌های ساده وارد کنید.','fields'=>['image','image_upload','category','duration','schedule','format','price_amount','capacity','line_slug','instructor_slug','content_status','level','registration_mode','payment_options','payment_note','curriculum','faq','audience','related_mentors','featured']],
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
