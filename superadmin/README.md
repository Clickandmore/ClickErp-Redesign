# Super Admin — منطقة منفصلة بهوية Click & More

لوحة Super Admin مش جزء من لوحة المكتب: ليها دخول منفصل (`/{locale}/superadmin`)، وقالب منفصل، وبنفس ثيم النظام الأساسي (`assets/clickerp.bundle.css`): خط Cairo، كحلي `#16233f` + أخضر `#7ab648`، قائمة جانبية فاتحة والعنصر النشط أخضر فاتح، وشعار Click & More. عشان كده اتنقلت من جذر المستودع (`sa*.html` جوه قالب المكتب) لفولدر لوحدها هنا.

المصدر في ClickERP:
- القالب: `resources/views/admin/superadmin/layouts/app.blade.php` + `partials/sidebar|topbar|page-header`
- الأنماط: أسماء الكلاسات زي `public/css/dev-super-admin.css`، والألوان والخط من ثيم التصميم (`assets/clickerp.bundle.css`).
- المسارات: `routes/ingaz.php` (مجموعة `dev-superadmin.`) و`routes/hourly.php`.

## الملفات

```
superadmin/
  assets/sa.js       بيبني إطار النظام نفسه (rail + side + top) من مصدر واحد (SA_NAV) + تفاعلات المعاينة
  assets/sa.css      مكوّنات شاشات Super Admin بس (sa-*) — كل ألوانها من متغيرات الثيم
  login.html         الدخول — نفس ماركب login.html في الثيم (.lx)
  index.html         نظرة عامة («جديد» — مش موجودة في الكود)
  fleet.html  instance.html  servers.html  deploy.html  incidents.html   مركز التحكم في النسخ («جديد»)
  subscription.html  employees.html  modules.html  designs.html  updates.html  update-form.html
  backups.html  scheduler.html  recovery.html
  test-accounts.html  hourly.html  moyasar.html  invoices.html  invoice-fix.html  mcp.html
```

**الثيم مش متكرر هنا:** كل صفحة بتحمّل `../assets/clickerp.bundle.css` (ثيم النظام كامل: خط Cairo، الألوان، الإطار، الأزرار، الكروت، الدخول)، وبعده `assets/sa.css`. أي تعديل في الثيم بيوصل لـ Super Admin لوحده.

## شكل أي صفحة

```html
<link rel="stylesheet" href="../assets/clickerp.bundle.css">
<link rel="stylesheet" href="assets/sa.css">
…
<body dir="rtl" data-page="scheduler">
<main class="sa-content">
  <div class="sa-page-header">…العنوان والوصف والأزرار…</div>
  <section class="sa-card">…</section>
</main>
<script src="…lucide…"></script>
<script src="assets/sa.js"></script>
```

`sa.js` بيلف المحتوى بـ `#app > aside.side (.rail + .side-head + nav.nav + .side-foot)` و`.main > header.top + main.page` — نفس ماركب شاشات النظام بالظبط.
الـ rail فيه: الرئيسية، النظام، الأدوات — وبيبدّل القائمة الجانبية زي النظام. مكان شعار المكتب شعار Click & More.

## إضافة شاشة جديدة
1. انسخ أقرب صفحة ليها (مثلاً `scheduler.html`) وغيّر `data-page` والمحتوى.
2. ضيف سطر في `SA_NAV` في `assets/sa.js` تحت «النظام» أو «الأدوات» (أو مجموعة جديدة = زر جديد في الـ rail).
   - شاشة فرعية (فورم أو تفاصيل)؟ ضيفها في `SA_PARENT` بدل القائمة.
3. الأزرار من الثيم: `btn btn-primary` / `btn btn-ghost` / `btn btn-red` / `sm` / `icon-btn`. المكوّنات من `sa.css`.

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
| عملية حساسة | `sa-confirm` (عبارة + كلمة مرور) · `sa-steps` (رمز ← تأكيد ← تنفيذ) · `btn btn-red` + `data-sa-confirm="…"` على الزر |
| تبويبات | `sa-tabs` + `sa-tab[data-sa-tab]` و`[data-sa-panel]` |
| حفظ لاصق | `sa-savebar` (بيظهر لوحده بعد أول تعديل) |

## قواعد
- كل صفحة فوقها تعليق `<!-- Screen: … -->` فيه اسم المسار والـ Controller والـ Blade المقابل.
- أي حاجة مش موجودة في الكود عليها `<span class="sa-new">جديد</span>`.
- العمليات الخطيرة (استعادة، حذف، تغيير نوع البرنامج، وضع التحديث) دايمًا: كارت `sa-card--danger` أو `sa-confirm` + زر `btn btn-red` + `data-sa-confirm`.
- RTL أساسي، والأكواد والمفاتيح والروابط `dir="ltr"` أو `sa-mono`.

## مركز التحكم في النسخ («جديد»)

12 عميل على كود واحد · كل عميل بقاعدة بيانات لوحده · كل عميلين على سيرفر. الشاشات في مجموعة «النسخ» في الـ rail:

| الشاشة | الملف | بتعمل إيه |
|---|---|---|
| مركز التحكم | `fleet.html` | كل النسخ متجمعة بالسيرفر: الحالة، الإصدار، المتصلين، الأخطاء · اللي محتاج تصرّف · ربط نسخة جديدة |
| تفاصيل نسخة | `instance.html` | الحالة والفحوصات وشريط التشغيل 30 يوم · قاعدة البيانات · الكود وسجل التحديثات · الأخطاء متجمعة · إحصائيات العميل · دخول كـ Super Admin |
| السيرفرات وقواعد البيانات | `servers.html` | CPU/RAM/مساحة لكل سيرفر · حجم واتصال ورقع ونسخ كل قاعدة |
| تحديث الكود | `deploy.html` | نشر إصدار على دفعات (اختبار ← 3 عملاء ← الباقي) · 8 خطوات لكل نسخة · رجوع تلقائي |
| الأعطال والتنبيهات | `incidents.html` | المفتوح والسجل · خطأ متكرر في أكتر من نسخة · مين يتبلغ وإمتى يعتبر عطل |

**المطلوب في الكود عشان الشاشات دي تشتغل** (مقترح للمطورين):
1. **النسخة المركزية (HQ):** نسخة Click & More نفسها. بتطلع مفتاح لكل نسخة عميل — نفس `SystemUpdateClient::issue()` الموجود، ويتزوّد عليه: الدومين، السيرفر، اسم القاعدة.
2. **النبض:** كل نسخة عميل بتبعت كل دقيقة (من الـ Scheduler) `POST /api/control-center/heartbeat` بنفس المفتاح: الإصدار والـ commit، حالة القاعدة والرقع المعلقة، نبض الـ Scheduler، آخر نسخة احتياطية، عدد الأخطاء من `laravel.log`، المتصلين، CPU/RAM/المساحة، وأرقام بسيطة (عقود، سير، عملاء).
3. **الوقوع:** لو النبض اتأخر أكتر من 3 دقايق، المركز بيعتبرها واقعة. وبيعمل كمان فحص HTTP خارجي للدومين كل دقيقة، عشان يعرف الفرق بين «الموقع واقع» و«الـ Scheduler بس واقف».
4. **الأوامر** (تحديث الكود، تشغيل الرقع، نسخة احتياطية، إعادة تشغيل): المركز بيحط الأمر في طابور، والنسخة بتسحبه مع النبض وتنفذه وترجع النتيجة. كده المركز مش محتاج يدخل على السيرفرات بـ SSH.
5. **الدخول على نسخة:** رابط موقّع صالح 15 دقيقة لمرة واحدة، بيفتح لوحة Super Admin بتاعة النسخة ويتسجل في الطرفين.
