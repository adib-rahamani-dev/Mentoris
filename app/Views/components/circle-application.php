<fieldset class="stack"><legend>سوابق تحصیلی و حرفه‌ای</legend>
<?php foreach(['education'=>'مقطع تحصیلی','university'=>'دانشگاه / مؤسسه','specialization'=>'رشته و گرایش','experience'=>'سابقه کار بالینی','approach'=>'رویکرد درمانی','practice_areas'=>'حوزه فعالیت تخصصی','professional_number'=>'شماره نظام / دانشجویی (اختیاری)'] as $field=>$label): ?>
<label class="form-group"><span class="form-label"><?= e($label) ?><?= $field!=='professional_number'?' *':'' ?></span><input class="form-control" name="<?= e($field) ?>" maxlength="<?= in_array($field,['university','specialization'],true)?160:(in_array($field,['approach','practice_areas'],true)?200:80) ?>" value="<?= e($old[$field]??'') ?>" <?= $field!=='professional_number'?'required':'' ?> placeholder="<?= $field==='experience'?'مثلاً دانشجو / بدون سابقه / ۳ سال':($field==='approach'?'مثلاً ACT، CBT یا در حال آموزش':'') ?>"></label>
<?php endforeach; ?>
</fieldset>
<fieldset class="stack"><legend>رزومه برای بررسی پذیرش</legend>
<?php if(!empty($savedResume)): ?><label class="circle-saved-resume"><input type="checkbox" name="reuse_resume" value="1" checked data-saved-resume="<?= e($savedResume['filename']) ?>"><span>استفاده از رزومه ذخیره‌شده <small><?= e($savedResume['filename']) ?></small></span></label><?php endif; ?>
<label class="form-group"><span class="form-label">فایل رزومه (اختیاری)</span><input class="form-control" type="file" name="resume" accept=".pdf,.docx"><small>بدون رزومه هم می‌توانید درخواست ثبت کنید. PDF یا DOCX، تا ۸ مگابایت؛ فقط تیم پذیرش به فایل دسترسی دارد. انتخاب فایل جدید، رزومه قبلی را برای این درخواست جایگزین می‌کند.</small></label>
<label class="form-group"><span class="form-label">انگیزه حضور / توضیحات تکمیلی (اختیاری)</span><textarea class="form-control" name="motivation" maxlength="1500" rows="3"><?= e($old['motivation']??'') ?></textarea></label>
</fieldset>
