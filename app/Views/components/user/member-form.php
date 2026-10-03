<?php
$member = $member ?? [];
$memberErrors = $memberErrors ?? [];
$memberAdmin = $memberAdmin ?? false;
$memberReadonly = $memberReadonly ?? false;
$memberAction = $memberAdmin ? '/admin/users/'.$user['id'].'/member-profile' : '/profile/member';
$options=\App\Services\MemberProfileService::OPTIONS;
$input=function(string $key,string $label,string $type='text',?int $max=null) use ($member,$memberErrors): void { ?>
<label class="form-group"><span class="form-label"><?= e($label) ?></span><input class="form-control" type="<?= e($type) ?>" name="<?= e($key) ?>" value="<?= e($member[$key] ?? '') ?>" <?= $max ? 'maxlength="'.$max.'"' : '' ?> <?= $key==='client_count' ? 'min="0" max="10000"' : '' ?>><?php if(isset($memberErrors[$key])):?><small class="form-error"><?= e($memberErrors[$key][0]) ?></small><?php endif;?></label>
<?php };
$select=function(string $key,string $label) use ($member,$memberErrors,$options): void { ?>
<label class="form-group"><span class="form-label"><?= e($label) ?></span><select class="form-select" name="<?= e($key) ?>"><option value="">انتخاب کنید</option><?php foreach($options[$key] as $v=>$text):?><option value="<?= e($v) ?>" <?= ($member[$key] ?? '')===$v ? 'selected' : '' ?>><?= e($text) ?></option><?php endforeach;?></select><?php if(isset($memberErrors[$key])):?><small class="form-error"><?= e($memberErrors[$key][0]) ?></small><?php endif;?></label>
<?php };
$textarea=function(string $key,string $label,int $max) use ($member,$memberErrors): void { ?>
<label class="form-group"><span class="form-label"><?= e($label) ?></span><textarea class="form-textarea" name="<?= e($key) ?>" maxlength="<?= $max ?>" rows="3"><?= e($member[$key] ?? '') ?></textarea><?php if(isset($memberErrors[$key])):?><small class="form-error"><?= e($memberErrors[$key][0]) ?></small><?php endif;?></label>
<?php };
?>
<section class="user-panel member-profile"><header><div><span class="eyebrow">پروفایل شما</span><h2>اطلاعات تکمیلی پروفایل</h2><p>هر بخش را در زمان مناسب تکمیل کنید؛ ذخیرهٔ موقت به پرکردن همهٔ فیلدها نیاز ندارد.</p></div><span class="badge <?= !empty($member['completed_at']) ? 'badge--success' : '' ?>"><?= !empty($member['completed_at']) ? 'تکمیل‌شده' : 'در حال تکمیل' ?></span></header>
<?php if(empty($memberEnabled)):?><div class="alert alert--danger">فرم تکمیلی پس از اجرای مایگریشن ۰۰۴ فعال می‌شود.</div><?php else:?>
<?php if($memberErrors):?><div class="alert alert--danger" role="alert"><ul><?php foreach($memberErrors as $messages):?><li><?= e($messages[0]) ?></li><?php endforeach;?></ul></div><?php endif;?>
<?php if(isset($_GET['saved']) || isset($_GET['member_saved'])):?><div class="alert alert--success" role="status">اطلاعات تکمیلی ذخیره شد.</div><?php endif;?>
<form class="stack member-form" method="post" enctype="multipart/form-data" action="<?= e($memberAction) ?>" data-member-form data-state-key="<?= e($user['id']) ?>" novalidate><?= csrf_field() ?>
<fieldset class="member-fieldset" <?= $memberReadonly ? 'disabled' : '' ?>><legend class="sr-only">اطلاعات تکمیلی</legend>
<?php $select('member_type','نوع عضویت *'); ?>
<details class="member-section" open data-member-section="academic"><summary><span>۰۱</span> اطلاعات تحصیلی <small>رشته و دانشگاه برای تکمیل پروفایل</small></summary><div class="member-section__body grid grid--2">
<?php $select('education_status','وضعیت تحصیلی'); $input('field_of_study','رشته تحصیلی','text',160); $select('degree','مقطع تحصیلی'); $input('university','نام دانشگاه / مؤسسه آموزشی','text',160); $input('admission_year','سال ورود','text',4); ?>
<div data-member-condition="graduate"><?php $input('graduation_year','سال فارغ‌التحصیلی','text',4); ?></div>
<?php $input('credential','مدرک / عنوان مدرک تحصیلی','text',160); $input('specialization','گرایش / تخصص','text',160); ?>
</div></details>
<details class="member-section" data-member-condition="therapist"><summary><span>۰۲</span> فعالیت حرفه‌ای <small>ویژهٔ درمانگران</small></summary><div class="member-section__body grid grid--2">
<?php $select('practice_status','وضعیت فعالیت حرفه‌ای'); $input('workplace','محل کار / نام مرکز','text',160); $input('city','شهر محل فعالیت','text',100); $input('job_title','عنوان شغلی','text',120); $input('specialty_fields','حوزه‌های تخصصی (با ویرگول جدا کنید)','text',500); $input('experience','سابقهٔ فعالیت حرفه‌ای','text',100); ?>
<div data-member-condition="active"><?php $input('client_count','تعداد مراجعان','number'); ?></div><div data-member-condition="active"><?php $select('client_period','بازهٔ تعداد مراجعان'); ?></div>
<?php $textarea('professional_notes','توضیحات فعالیت حرفه‌ای',2000); ?>
</div></details>
<details class="member-section"><summary><span>۰۳</span> دوره‌ها و آموزش‌ها <small>ثبت سابقهٔ یادگیری شما</small></summary><div class="member-section__body">
<?php if(isset($memberErrors['training_courses'])):?><p class="form-error"><?= e($memberErrors['training_courses'][0]) ?></p><?php endif;?>
<div data-course-list>
<?php $courses=$member['training_courses'] ?? []; $renderCourse=function(array $course,int|string $index) use ($options): void { ?>
<fieldset class="member-course" data-course-row><legend>دورهٔ آموزشی</legend><div class="grid grid--2">
<?php foreach(['name'=>'نام دوره *','organizer'=>'برگزارکننده / مؤسسه','instructor'=>'مدرس','date'=>'تاریخ یا سال برگزاری','duration'=>'مدت دوره'] as $key=>$label):?><label class="form-group"><span class="form-label"><?= e($label) ?></span><input class="form-control" name="training_courses[<?= e($index) ?>][<?= e($key) ?>]" value="<?= e($course[$key] ?? '') ?>" maxlength="<?= ['name'=>160,'organizer'=>160,'instructor'=>120,'date'=>40,'duration'=>80][$key] ?>" <?= $key==='name' ? 'data-course-name' : '' ?>></label><?php endforeach;?>
<label class="form-group"><span class="form-label">نوع دوره</span><select class="form-select" name="training_courses[<?= e($index) ?>][type]"><option value="">انتخاب کنید</option><?php foreach($options['course_type'] as $v=>$text):?><option value="<?= e($v) ?>" <?= ($course['type'] ?? '')===$v ? 'selected' : '' ?>><?= e($text) ?></option><?php endforeach;?></select></label>
<label class="form-group"><span class="form-label">مدرک / گواهی دریافت کرده‌اید؟</span><select class="form-select" name="training_courses[<?= e($index) ?>][certificate]"><option value="">انتخاب کنید</option><option value="yes" <?= ($course['certificate'] ?? '')==='yes' ? 'selected' : '' ?>>بله</option><option value="no" <?= ($course['certificate'] ?? '')==='no' ? 'selected' : '' ?>>خیر</option></select></label>
</div><button type="button" class="btn btn--ghost btn--sm" data-remove-course>حذف این دوره</button></fieldset>
<?php }; foreach($courses as $index=>$course) $renderCourse($course,$index); ?>
</div><template data-course-template><?php $renderCourse([],'__INDEX__'); ?></template>
<?php if(!$memberReadonly):?><button class="btn btn--secondary btn--sm" type="button" data-add-course>+ افزودن دوره</button><?php endif;?>
<p class="form-hint" data-course-status aria-live="polite"><?= count($courses) ?> دوره ثبت شده؛ حداکثر ۳۰ دوره.</p>
</div></details>
<details class="member-section"><summary><span>۰۴</span> دربارهٔ شما <small>اختیاری</small></summary><div class="member-section__body grid grid--2">
<label class="form-group"><span class="form-label">تصویر پروفایل</span><?php if(!empty($member['avatar_path']) && preg_match('#^images/uploads/avatar-[a-f0-9]+\.(jpg|png|webp)$#',$member['avatar_path'])):?><img class="member-avatar" src="<?= e(asset($member['avatar_path'])) ?>" alt="تصویر پروفایل" width="80" height="80"><?php endif;?><input class="form-control" type="file" name="avatar" accept="image/jpeg,image/png,image/webp"><small class="form-hint">JPG، PNG یا WebP تا ۲ مگابایت</small></label>
<?php $input('professional_url','لینک صفحهٔ حرفه‌ای / وب‌سایت','url',255); $textarea('bio','رزومه / معرفی کوتاه',2000); $input('interests','حوزه‌های مورد علاقه','text',500); ?>
</div></details>
<label class="check member-consent"><input type="checkbox" name="marketing_consent" value="1" <?= !empty($member['marketing_consent']) ? 'checked' : '' ?>><span>مایلم اطلاع‌رسانی دوره‌ها و برنامه‌های آکادمی را با ایمیل دریافت کنم.</span></label>
<?php if($memberAdmin):?><p class="form-hint">پذیرش قوانین: <?= e($member['terms_accepted_at'] ?? 'ثبت نشده') ?> · آخرین ویرایش: <?= e($member['updated_at'] ?? 'ثبت نشده') ?></p><?php endif;?>
<?php if(!$memberReadonly):?><div class="member-form__actions"><span data-save-status role="status" aria-live="polite">اطلاعات را در حدی که مایلید وارد کنید.</span><button class="btn btn--primary" type="submit" name="mode" value="draft">ذخیره اطلاعات</button></div><?php endif;?>
</fieldset></form><?php endif;?></section>
