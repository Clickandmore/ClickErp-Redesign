# تصدير التصميم إلى Blade — Click ERP

هذا المجلد يُنتَج من مشروع التصميم (`Click ERP Redesign.html`) ويُعاد توليده مع كل تحديث للتصميم.
الهدف: دمج إعادة التصميم في النظام الفعلي **دون تغيير منطق Laravel** (لا Controllers ولا Models ولا استعلامات).

## ما في الحزمة

| الملف | المسار في المشروع الأصلي | الدور |
|---|---|---|
| `public/admin/custom/clickerp-redesign.css` | `public/admin/custom/clickerp-redesign.css` | طبقة إعادة التصميم الكاملة (ألوان، جداول، بطاقات، حقول، أزرار) |
| `resources/views/admin/ingaz/RecruitmentContract/_redesign-stats.blade.php` | نفس المسار | كتلة إحصائيات مراحل العقود بعد إعادة التصميم |

## خطوة التركيب (مرة واحدة)

1. انسخ `clickerp-redesign.css` إلى `public/admin/custom/`.
2. في القالب العام `resources/views/admin/layout/*` أضف السطر **بعد** أنماط النظام:
   ```blade
   <link rel="stylesheet" href="{{ asset('admin/custom/clickerp-redesign.css') }}">
   ```
3. للحصول على خط Cairo أضف في نفس القالب:
   ```blade
   <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
   ```

بهذه الخطوة وحدها تتغيّر **كل** الشاشات: الجداول والفلاتر والبطاقات والأزرار — لأن الطبقة تستهدف نفس أصناف النظام
(`.stat-card`, `.stat-value`, `#dataTableObject`, `.form-control`, `.btn`, `.application-duration-value` …).

## ملاحظة مهمة عن جدول العقود

جدول `#dataTableObject` في `RecruitmentContract/index.blade.php` هو **DataTable من الخادم**؛ محتوى الخلايا يُبنى في PHP
خارج هذه اللقطة. لذلك تصميم الجدول يُسلَّم كطبقة أنماط تستهدف الجدول نفسه — لا يحتاج تعديل أعمدة ولا `@foreach`.
إذا أردتم تجميع البيانات في خلايا معنونة (كما في التصميم: العميل / العاملة / بيانات العقد)، فذلك تغيير في
`render` الأعمدة داخل ملف الـ DataTable في المشروع الأصلي، ونكتبه معكم عند الحاجة.

## تحديث الحزمة

التصميم مستمر، والتصدير قابل للتكرار: بعد أي تعديل في `Click ERP Redesign.html` أُعيد توليد هذا المجلد،
فتُستبدل الملفات كما هي دون لمس منطق النظام. اطلب: «حدّث حزمة Blade».

## ما لا تُغيّره الحزمة

- تعبيرات Blade والمتغيرات وأسماء الحقول.
- الصلاحيات `@AdminGuardHasPerm(\App\NewPermKeys::…)`.
- الترجمات `helperTrans('ingaz.…')`.
- المسارات `route('RecruitmentContract.…')` ومعرّفات الفلاتر (`#statusFilter`, `#globalSearchFilter` …) فتبقى JS تعمل.
