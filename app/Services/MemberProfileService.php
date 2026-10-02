<?php
declare(strict_types=1);
namespace App\Services;

use App\Repositories\CircleRepository;

final class MemberProfileService
{
    public const OPTIONS = [
        'member_type'=>['student'=>'دانشجو','therapist'=>'درمانگر','other'=>'سایر'],
        'education_status'=>['student'=>'دانشجو','graduate'=>'فارغ‌التحصیل'],
        'degree'=>['bachelor'=>'کارشناسی','master'=>'کارشناسی ارشد','phd'=>'دکتری','other'=>'سایر'],
        'practice_status'=>['active'=>'در حال فعالیت','intern'=>'کارآموز / کارورز','inactive'=>'فعلاً فعالیت حرفه‌ای ندارم'],
        'client_period'=>['month'=>'در ماه','week'=>'در هفته'],
        'course_type'=>['inperson'=>'حضوری','online'=>'آنلاین','workshop'=>'کارگاه','specialized'=>'دوره تخصصی','other'=>'سایر'],
    ];
    public const TEXT_FIELDS = ['field_of_study'=>160,'university'=>160,'credential'=>160,'specialization'=>160,'workplace'=>160,'city'=>100,'job_title'=>120,'specialty_fields'=>500,'experience'=>100,'professional_notes'=>2000,'bio'=>2000,'interests'=>500,'professional_url'=>255];
    public static function validate(array $input, bool $complete = false): array
    {
        $data=[]; $errors=[];
        foreach (self::TEXT_FIELDS as $key=>$max) {
            $value=$input[$key] ?? '';
            if (!is_string($value) || mb_strlen($value)>$max) { $errors[$key]=['مقدار واردشده بیش از حد مجاز یا نامعتبر است.']; $value=''; }
            $data[$key]=trim($value);
        }
        foreach (['member_type','education_status','degree','practice_status','client_period'] as $key) {
            $value=$input[$key] ?? '';
            if (!is_string($value) || ($value!=='' && !isset(self::OPTIONS[$key][$value]))) { $errors[$key]=['گزینه معتبر انتخاب کنید.']; $value=''; }
            $data[$key]=$value;
        }
        if ($data['member_type']==='') $errors['member_type']=['نوع عضویت را انتخاب کنید.'];
        foreach (['admission_year','graduation_year'] as $key) {
            $raw=$input[$key] ?? ''; $value=is_string($raw) ? CircleRepository::phone($raw) : 'invalid';
            if ($value!=='' && (!preg_match('/^\d{4}$/',$value) || (int)$value<1300 || (int)$value>2200)) $errors[$key]=['سال چهاررقمی معتبر وارد کنید.'];
            $data[$key]=$value;
        }
        $raw=$input['client_count'] ?? ''; $data['client_count']=is_scalar($raw) ? CircleRepository::phone((string)$raw) : 'invalid';
        if ($data['client_count']!=='' && (!ctype_digit($data['client_count']) || (int)$data['client_count']>10000)) $errors['client_count']=['تعداد معتبر از صفر تا ۱۰۰۰۰ وارد کنید.'];
        if ($data['professional_url']!=='' && (!filter_var($data['professional_url'], FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($data['professional_url'], PHP_URL_SCHEME)),['https','http'],true))) $errors['professional_url']=['لینک کامل با https یا http وارد کنید.'];
        if ($data['education_status']!=='graduate') { $data['graduation_year']=''; unset($errors['graduation_year']); }
        if ($data['member_type']!=='therapist') foreach (['practice_status','workplace','city','job_title','specialty_fields','experience','professional_notes','client_count','client_period'] as $key) { $data[$key]=''; unset($errors[$key]); }
        if ($data['practice_status']!=='active') { $data['client_count']=''; $data['client_period']=''; unset($errors['client_count'],$errors['client_period']); }
        if ($data['graduation_year']!=='' && $data['admission_year']!=='' && (int)$data['graduation_year']<(int)$data['admission_year']) $errors['graduation_year']=['سال فارغ‌التحصیلی نباید قبل از سال ورود باشد.'];
        if ($complete && in_array($data['member_type'],['student','therapist'],true)) foreach (['field_of_study','university'] as $key) if ($data[$key]==='') $errors[$key]=['برای تکمیل پروفایل این فیلد را وارد کنید.'];
        $courses=$input['training_courses'] ?? []; $data['training_courses']=[];
        if (!is_array($courses) || count($courses)>30) $errors['training_courses']=['حداکثر ۳۰ دوره می‌توانید ثبت کنید.'];
        else foreach ($courses as $course) {
            if (!is_array($course)) { $errors['training_courses']=['اطلاعات دوره معتبر نیست.']; continue; }
            $row=[];
            foreach (['name'=>160,'organizer'=>160,'instructor'=>120,'date'=>40,'duration'=>80] as $key=>$max) {
                $value=$course[$key] ?? '';
                if (!is_string($value) || mb_strlen($value)>$max) { $errors['training_courses']=['اطلاعات دوره بیش از حد مجاز یا نامعتبر است.']; $value=''; }
                $row[$key]=trim($value);
            }
            $type=$course['type'] ?? ''; $certificate=$course['certificate'] ?? '';
            if (!is_string($type) || ($type!=='' && !isset(self::OPTIONS['course_type'][$type])) || !in_array($certificate,['','yes','no'],true)) $errors['training_courses']=['نوع دوره یا وضعیت گواهی معتبر نیست.'];
            $row['type']=is_string($type) ? $type : ''; $row['certificate']=is_string($certificate) ? $certificate : '';
            if (count(array_filter($row,fn($v)=>$v!==''))===0) continue;
            if ($row['name']==='') $errors['training_courses']=['نام هر دوره را وارد کنید یا ردیف خالی را حذف کنید.'];
            $data['training_courses'][]=$row;
        }
        $data['marketing_consent']=($input['marketing_consent'] ?? '')==='1';
        return [$data,$errors];
    }
}
