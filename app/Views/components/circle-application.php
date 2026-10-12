<fieldset class="stack"><legend>سوابق تحصیلی و حرفه‌ای</legend>
<?php foreach(['education'=>'مقطع تحصیلی','university'=>'دانشگاه / مؤسسه','specialization'=>'رشته و گرایش','experience'=>'سابقه کار بالینی','approach'=>'رویکرد درمانی','practice_areas'=>'حوزه فعالیت تخصصی','professional_number'=>'شماره نظام / دانشجویی (اختیاری)'] as $field=>$label): ?>
<label class="form-group"><span class="form-label"><?= e($label) ?><?= $field!=='professional_number'?' *':'' ?></span><input class="form-control" name="<?= e($field) ?>" maxlength="<?= in_array($field,['university','specialization'],true)?160:(in_array($field,['approach','practice_areas'],true)?200:80) ?>" value="<?= e($old[$field]??'') ?>" <?= $field!=='professional_number'?'required':'' ?> placeholder="<?= $field==='experience'?'مثلاً دانشجو / بدون سابقه / ۳ سال':($field==='approach'?'مثلاً ACT، CBT یا در حال آموزش':'') ?>"></label>
<?php endforeach; ?>
</fieldset>
<fieldset class="stack"><legend>رزومه برای بررسی پذیرش</legend>
<label class="form-group"><span class="form-label">فایل رزومه *</span><input class="form-control" type="file" name="resume" accept=".pdf,.docx" required><small>PDF یا DOCX، حداکثر ۸ مگابایت. فقط تیم بررسی پذیرش به فایل دسترسی دارد. پس از خطای فرم، فایل را دوباره انتخاب کنید.</small></label>
<label class="form-group"><span class="form-label">انگیزه حضور / توضیحات تکمیلی (اختیاری)</span><textarea class="form-control" name="motivation" maxlength="1500" rows="3"><?= e($old['motivation']??'') ?></textarea></label>
</fieldset>
