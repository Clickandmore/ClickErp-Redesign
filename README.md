# ClickERP — حزمة إعادة تصميم لوحة التحكم

هذه الحزمة لقطة مستقلة من واجهات لوحة تحكم ClickERP أُعدّت للعمل على إعادة التصميم في مستودع خاص، من دون ربط نظام الإنتاج أو تضمين منطق التطبيق وقاعدة بياناته.

## المحتوى

- **التصميم المعتمد (في الجذر):** `index.html` (الصفحة الرئيسية) و`login.html` وكل شاشة في ملف `*.html`، مع `assets/` و`states/` و`shots/` و`screens.json` و`CHANGELOG.md`. بيتعرض مباشرة على https://clickandmore.github.io/ClickErp-Redesign/ — شرح الملفات في `design/PAGES.md`. الروابط القديمة تحت `design/pages/` بتحوّل تلقائياً للجذر.
- `design/`: القرارات والبريفات ومرجع الريادة (`design/modules/riyada`).
- `source/resources/views/admin`: واجهات لوحة التحكم الأساسية وقالبها المشترك.
- `source/resources/views/components`: مكوّنات Blade المشتركة التي تعتمد عليها الصفحات.
- `source/resources/views/click`: أجزاء القالب القديم اللازمة لبعض الشاشات.
- `source/resources/views/commissions` و`widgets`: واجهات مشتركة إضافية.
- `source/packages/*/resources/views`: واجهات المالية والموارد البشرية وCRM والصلاحيات.
- `source/**/lang`: النصوص والترجمات العربية والإنجليزية اللازمة لفهم الواجهة.
- `public`: ملفات CSS وJavaScript والخطوط والصور الثابتة المستخدمة في لوحة التحكم.
- `docs`: قواعد العمل وخريطة مجموعات الشاشات.

## مهم

هذه ليست نسخة قابلة لتشغيل نظام ClickERP منفردة. ملفات Blade تحتوي على بيانات ومسارات وصلاحيات ديناميكية مصدرها Laravel. الهدف هو مراجعة البنية والأصناف والنصوص الحقيقية ثم تسليم تعديلات الواجهة إلى فريق البرمجة لدمجها في المشروع الأصلي.

لا تتضمن الحزمة:

- ملف `.env` أو أي أسرار ومفاتيح.
- قاعدة بيانات أو بيانات عملاء.
- Controllers أو Models أو migrations أو routes.
- ملفات مرفوعة من المستخدمين أو محتويات `storage`.
- إعدادات النشر أو اتصالاً بخادم الإنتاج.

## بدء مستودع خاص

نفّذ الأوامر التالية من داخل هذا المجلد بعد إنشاء مستودع GitHub خاص وفارغ:

```bash
git init
git add .
git commit -m "Initial dashboard design snapshot"
git branch -M main
git remote add origin <PRIVATE_REPOSITORY_URL>
git push -u origin main
```

لا تضف ملفات النظام الخلفية أو أي نسخة من `.env` إلى هذا المستودع.

## طريقة العمل المقترحة

1. ابدأ بالقالب العام في `source/resources/views/admin/layout` وملفات النمط في `public/admin/custom` و`public/admin/assets/css`.
2. عدّل مجموعة شاشات واحدة في كل فرع Git مستقل.
3. حافظ على تعبيرات Blade وأسماء الحقول والمسارات كما هي، ما لم يتفق فريق البرمجة على تغييرها.
4. وثّق الحالات البصرية: فارغ، تحميل، خطأ، نجاح، دون صلاحية، وجدول طويل أو شاشة جوال.
5. سلّم كل مجموعة على هيئة Pull Request ليتم دمج التغييرات يدوياً في النظام الفعلي بعد المراجعة.

راجع [قواعد إعادة التصميم](docs/DESIGN-HANDOFF.md) و[خريطة الشاشات](docs/SCREEN-GROUPS.md) قبل البدء.

