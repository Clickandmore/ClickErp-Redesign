# Super Admin — منطقة منفصلة بهوية Click & More

لوحة Super Admin مش جزء من لوحة المكتب: ليها دخول منفصل (`/{locale}/superadmin`)، وقالب منفصل، وبنفس ثيم النظام الأساسي (`assets/clickerp.bundle.css`): خط Cairo، كحلي `#16233f` + أخضر `#7ab648`، قائمة جانبية فاتحة والعنصر النشط أخضر فاتح، وشعار Click & More. عشان كده اتنقلت من جذر المستودع (`sa*.html` جوه قالب المكتب) لفولدر لوحدها هنا.

المصدر في ClickERP:
- القالب: `resources/views/admin/superadmin/layouts/app.blade.php` + `partials/sidebar|topbar|page-header`
- الأنماط: أسماء الكلاسات زي `public/css/dev-super-admin.css`، والألوان والخط من ثيم التصميم (`assets/clickerp.bundle.css`).
- المسارات: `routes/ingaz.php` (مجموعة `dev-superadmin.`) و`routes/hourly.php`.

## الملفات

```
superadmin/
  assets/sa.css      نظام التصميم كله (tokens ← shell ← components ← screens ← login ← responsive)
  assets/sa.js       القائمة الجانبية والشريط العلوي من مصدر واحد (SA_NAV) + تفاعلات المعاينة
  login.html         الدخول
  index.html         نظرة عامة («جديد» — مش موجودة في الكود)
  subscription.html  employees.html  modules.html  designs.html  updates.html  update-form.html
  backups.html  scheduler.html  recovery.html
  test-accounts.html  hourly.html  moyasar.html  invoices.html  invoice-fix.html  mcp.html
```

## شكل أي صفحة

```html
<body data-page="scheduler">
<main class="sa-content">
  <div class="sa-page-header">…العنوان والوصف والأزرار…</div>
  <section class="sa-card">…</section>
</main>
<script src="…lucide…"></script>
<script src="assets/sa.js"></script>
```

الصفحة فيها **المحتوى بس**. `sa.js` بيلفّها بالقائمة الجانبية والشريط العلوي ومسار التنقل، فمفيش تكرار للقائمة في 17 ملف.
المحتوى HTML ثابت (مش متولّد بـ JS)، فبيتنقل لـ Blade نسخ ولصق جوه `@section('content')`.

## إضافة شاشة جديدة
1. انسخ أقرب صفحة ليها (مثلاً `scheduler.html`) وغيّر `data-page` والمحتوى.
2. ضيف سطر في `SA_NAV` في `assets/sa.js` تحت «النظام» أو «أدوات».
   - شاشة فرعية (فورم أو تفاصيل)؟ ضيفها في `SA_PARENT` بدل القائمة عشان تنوّر عنصر الأب.
3. استخدم مكوّنات `sa.css` الموجودة بس — لو احتجت حاجة جديدة ضيفها في قسم 5 أو 6 من الملف، مش `style` جوه الصفحة.

## المكوّنات (أهمها)

| المكوّن | الكلاس |
|---|---|
| رأس الصفحة | `sa-page-header` · `sa-page-title` · `sa-page-desc` · `sa-page-chips` · `sa-back` |
| بطاقة | `sa-card` (+ `--flush` للجداول، `--danger` للعمليات الخطيرة، `--feature sa-pattern` للكحلي) · `sa-card-head` · `sa-card-icon` · `sa-card-title` · `sa-card-desc` · `sa-card-foot` |
| تخطيط | `sa-layout` (محتوى + عمود 340) · `sa-layout-side` · `sa-grid-2/3/4` · `sa-stack` |
| حالة الصفحة في سطر | `sa-health` (+ `--success/--warning/--danger`) |
| مؤشر | `sa-kpi` · `sa-kpi-label` · `sa-kpi-value` · `sa-kpi-sub` |
| شارة | `sa-badge` (+ `--success/--warning/--danger/--info/--brand/--accent/--env`) · `sa-new` لأي حاجة مش في الكود |
| فورم | `sa-form-grid` (`is-one/is-three`) · `sa-field` (`is-wide`) · `sa-input/sa-select/sa-textarea` · `sa-help` |
| إعداد بمفتاح | `sa-setting` + `sa-switch` (`is-on`، `is-locked`، `disabled`) |
| اختيار بكروت | `sa-choices` + `sa-choice` (`is-on`) |
| جدول | `sa-table-wrap` + `sa-table` · `is-end` لعمود الأزرار · `sa-person` |
| عملية حساسة | `sa-confirm` (عبارة + كلمة مرور) · `sa-steps` (رمز ← تأكيد ← تنفيذ) · `data-sa-confirm="…"` على الزر |
| تبويبات | `sa-tabs` + `sa-tab[data-sa-tab]` و`[data-sa-panel]` |
| حفظ لاصق | `sa-savebar` (بيظهر لوحده بعد أول تعديل) |

## قواعد
- كل صفحة فوقها تعليق `<!-- Screen: … -->` فيه اسم المسار والـ Controller والـ Blade المقابل.
- أي حاجة مش موجودة في الكود عليها `<span class="sa-new">جديد</span>`.
- العمليات الخطيرة (استعادة، حذف، تغيير نوع البرنامج، وضع التحديث) دايمًا: كارت `sa-card--danger` أو `sa-confirm` + زر `btn-danger` + `data-sa-confirm`.
- RTL أساسي، والأكواد والمفاتيح والروابط `dir="ltr"` أو `sa-mono`.
