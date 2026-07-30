# توليد السير الذاتية (PDF) — تطابق التنسيق بين اللوكال والسيرفر

## المشكلة الجذرية

قالب السيرة الذاتية يُخزَّن كـ Blade في قاعدة البيانات ويستخدم CSS حديث (flexbox، grid،
`linear-gradient`، `border-radius`). محرك واحد فقط يرسم هذا بشكل صحيح: **Chromium**.

`MaidCvPdfGenerator` كان يبحث عن Chrome في مسارات ثابتة، فإن لم يجده **يسقط بصمت** إلى mPDF.
النتيجة:

| البيئة | Chrome موجود؟ | المحرك المستخدم | النتيجة |
| --- | --- | --- | --- |
| لوكال (Windows) | نعم، `C:\Program Files\Google\Chrome\...` | Chromium | التنسيق صحيح |
| السيرفر (Linux) | لا | mPDF | التنسيق منهار |

mPDF لا يدعم flexbox ولا grid ولا التدرجات اللونية، فتنهار البطاقات وتتداخل النصوص.

## الحل

1. **لا يوجد سقوط صامت.** إن تعذّر تشغيل Chromium يُرمى خطأ عربي واضح ولا يُولَّد ملف بتنسيق مختلف.
2. **Chromium في كل بيئة**، بما فيها الاستضافة المشتركة، عبر نسخة داخل المشروع.
3. **أمر تشخيص** يخبرك بالسبب الدقيق على السيرفر بدل التخمين.

## خطوات النشر على السيرفر (cPanel)

```bash
cd ~/public_html          # مجلد المشروع
php artisan cv:install-browser
php artisan cv:doctor
```

`cv:install-browser` ينزّل Chrome for Testing إلى `storage/app/cv-browser/<version>/`
(حوالي 400 ميجابايت بعد فك الضغط) ويكتشفه النظام تلقائيًا — لا حاجة لضبط `CV_PDF_BROWSER_PATH`.

`cv:doctor` يفحص ويطبع النتيجة:

- توفّر `proc_open` (كثيرًا ما تكون معطّلة في `disable_functions` على الاستضافة المشتركة)
- إمكانية الكتابة في `storage/app`
- هل `CV_PDF_ALLOW_MPDF_FALLBACK` مفعّل (وهو سبب اختلاف التنسيق)
- مسار Chromium وإصداره
- **مكتبات النظام الناقصة** عبر `ldd` — وهي أشهر سبب لفشل Chromium على cPanel
- اختبار توليد PDF فعلي بصفحة flexbox

بعد أي نشر جديد:

```bash
php artisan config:clear && php artisan config:cache
```

## أنماط التشغيل (مهم على cPanel)

CloudLinux/CageFS والحاويات التي تمنع user namespaces تقتل عملية **zygote** الخاصة بـ Chromium،
فينهار بإشارة SIGTRAP (`signal "5"`) رغم أن `chrome --version` يعمل بشكل سليم. لذلك يجرّب
المولّد أنماط تشغيل متدرجة، كل واحد يعطّل طبقة عزل إضافية، حتى ينجح أحدها:

| النمط | الخيارات المضافة |
| --- | --- |
| `default` | لا شيء (الأسرع) |
| `no-zygote` | `--no-zygote` |
| `single-process` | `--no-zygote --single-process` |
| `legacy-headless` | `--headless` القديم + `--no-zygote --single-process` |

`cv:doctor` يجرّبها جميعًا ويطبع النمط العامل. ثبّته في `.env` لتوفير المحاولات الفاشلة في كل توليد:

```env
CV_PDF_BROWSER_PROFILE=no-zygote
```

## إن فشل التشغيل المحلي على الاستضافة

على استضافة مشتركة قد تصطدم بأحد هذه الحواجز ولا تملك صلاحية حلّها:

- `proc_open` معطّلة في `php.ini`
- مجلد `home` مركّب بخيار `noexec`
- مكتبات ناقصة مثل `libnss3` أو `libgbm` أو `libatk-1.0`
- منع إنشاء العمليات الفرعية بكل الأنماط أعلاه

`cv:doctor` سيحدد أيها بالضبط. عندها إمّا تطلب من الدعم الفني معالجته، أو تشغّل الرسم عن بُعد —
وهو **ما زال Chromium**، لذلك يبقى التنسيق مطابقًا تمامًا:

```env
CV_PDF_REMOTE_DRIVER=gotenberg     # أو browserless
CV_PDF_REMOTE_URL=https://gotenberg.example.com
CV_PDF_REMOTE_TOKEN=
```

عند ضبط `CV_PDF_REMOTE_DRIVER` يُستخدم الرسم عن بُعد وتُتجاهَل النسخة المحلية.

## متغيرات البيئة

| المتغير | الافتراضي | الوصف |
| --- | --- | --- |
| `CV_PDF_BROWSER_PATH` | (فارغ) | مسار Chrome صريح. اتركه فارغًا للاكتشاف التلقائي. |
| `CV_PDF_BROWSER_PROFILE` | (فارغ) | تثبيت نمط التشغيل. اتركه فارغًا للتجربة التلقائية. |
| `CV_PDF_VIRTUAL_TIME_BUDGET` | `15000` | مهلة (ms) لتحميل الصور والخطوط قبل الطباعة. ارفعها إن ظهرت صور ناقصة. |
| `CV_PDF_TIMEOUT` | `120` | أقصى مدة (ثانية) لعملية Chromium. |
| `CV_PDF_ALLOW_MPDF_FALLBACK` | `false` | **أبقِه `false`.** تفعيله يعيد المشكلة الأصلية. |
| `CV_PDF_REMOTE_DRIVER` | (فارغ) | `gotenberg` أو `browserless`. |
| `CV_PDF_REMOTE_URL` | (فارغ) | رابط خدمة الرسم. |

## ملاحظات

- الخطوط العربية تأتي من القالب نفسه. إن كان القالب يعتمد على خط مثبَّت على النظام فلن يجده
  السيرفر. استخدم `@font-face` برابط أو `data:` URI داخل القالب لضمان التطابق.
- الصور المشار إليها بروابط مطلقة يجب أن تكون قابلة للوصول من السيرفر نفسه. إن كان السيرفر
  لا يستطيع مناداة نطاقه الخاص فستظهر الصور فارغة رغم صحة الرابط في المتصفح.
- `storage/app/cv-browser` مستثنى من git عبر `storage/app/.gitignore`، لذا نفّذ
  `cv:install-browser` مرة واحدة على كل سيرفر.
