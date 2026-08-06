# رفع الملفات في السير الذاتية — التخزين المحلي و Cloudflare R2

توثيق كامل لما يحدث عند إنشاء سيرة ذاتية (`/admin/maids/create`) في جزء رفع الملفات،
والفرق بين المسار المحلي القديم و مسار R2، والإعدادات المطلوبة، وكيفية إعادة استخدام
نفس المنطق في أي موديول آخر.

---

## 1. الصورة الكاملة (Flow)

```
resources/views/admin/ingaz/maids/create.blade.php   ← فورم فيه <input type="file">
        │  POST multipart/form-data
        ▼
app/Http/Requests/ingaz/MaidRequest.php              ← التحقق (validation)
        ▼
app/Http/Controllers/ingaz/MaidController@store      ← $request->all() + status/added_by
        ▼
app/Http/Actions/ingaz/MaidAction@storeMaid          ← يستدعي uploadMaidFile() لكل ملف
        ▼
app/Helpers/MainHelper.php :: uploadMaidFile()       ← مجرد wrapper يرجع الـ Service
        ▼
app/Services/ManagedFileStorage@uploadMaidFile       ← القرار: R2 أم محلي؟
        ├── R2 غير جاهز → storage/app/public/…      → يرجع "images/Maid/aB3xY9z1721.pdf"
        └── R2 جاهز     → Bucket على Cloudflare     → يرجع "r2://clickerp/images/Maid/aB3xY9z1721.pdf"
        ▼
عمود في جدول maids (cv_file / image / cv_preview / video) يخزن النص المُرجَع كما هو
```

الفكرة الأساسية: **قيمة العمود في قاعدة البيانات هي المفتاح**. لو بدأت بـ `r2://`
فالملف على Cloudflare، غير كده فهو ملف محلي (نظام قديم). كل دوال العرض/الحذف تقرأ
هذه البادئة وتتصرف تلقائيًا، وبالتالي السجلات القديمة تعمل بدون أي migration.

---

## 2. الملفات المعنية

| الملف | الدور |
|---|---|
| [app/Services/ManagedFileStorage.php](app/Services/ManagedFileStorage.php) | قلب المنظومة: رفع/عرض/حذف/فحص/بث الملف على أي من التخزينين |
| [app/Support/R2StorageSettings.php](app/Support/R2StorageSettings.php) | قراءة/حفظ/تشفير إعدادات R2 من جدول `new_settings` + بناء إعداد الـ disk |
| [app/Helpers/MainHelper.php:390](app/Helpers/MainHelper.php#L390) | `uploadMaidFile()` — اختصار للاستدعاء من أي مكان |
| [app/Helpers/MainHelper.php:364](app/Helpers/MainHelper.php#L364) | `showFile()` — يرجّع URL صالح للعرض من أي تخزين |
| [app/Helpers/MainHelper.php:343](app/Helpers/MainHelper.php#L343) | `deleteFile()` — يحذف من R2 أو من القرص المحلي |
| [app/Http/Controllers/ingaz/StorageSettingsController.php](app/Http/Controllers/ingaz/StorageSettingsController.php) | شاشة الإعدادات + زر اختبار الاتصال |
| [resources/views/admin/ingaz/storage-settings/edit.blade.php](resources/views/admin/ingaz/storage-settings/edit.blade.php) | واجهة الإعدادات |
| [tests/Unit/R2StorageSettingsTest.php](tests/Unit/R2StorageSettingsTest.php) | اختبارات `isReady` / `diskConfig` / تمييز المسارات |

---

## 3. ما يحدث بالتفصيل عند إنشاء سيرة ذاتية

### 3.1 الفورم

الحقول في [create.blade.php:383-389](resources/views/admin/ingaz/maids/create.blade.php#L383-L389):

```blade
<input type="file" name="image"   id="maid_image" class="dropify" required accept="image/*">
<input type="file" name="cv_file" id="cv_file"    class="dropify" accept=".pdf,image/*">
```

وفي شاشة التعديل يوجد بالإضافة لهما `cv_preview` و `video`.
الفورم لازم يكون `enctype="multipart/form-data"`.

### 3.2 الـ Action

في [MaidAction.php:46-61](app/Http/Actions/ingaz/MaidAction.php#L46-L61) — لاحظ شرط
`instanceof UploadedFile` حتى لا يُعاد الرفع عند إرسال قيمة نصية قديمة:

```php
$this->fileFolder = 'images/Maid/';   // من الـ constructor

if (isset($data['cv_file']) && $data['cv_file'] instanceof \Illuminate\Http\UploadedFile) {
    $data['cv_file'] = uploadMaidFile($data['cv_file'], $this->fileFolder);
}
// ونفس الشيء لـ image / cv_preview / video
```

وفي `updateMaid` نفس المنطق مسبوقًا بـ `deleteFile($maid->cv_file)` لحذف الملف القديم
قبل رفع الجديد ([MaidAction.php:1319-1338](app/Http/Actions/ingaz/MaidAction.php#L1319-L1338)).

### 3.3 خدمة التخزين — نقطة القرار

[ManagedFileStorage.php:16-31](app/Services/ManagedFileStorage.php#L16-L31):

```php
public function uploadMaidFile(UploadedFile $file, string $directory): string
{
    $directory = trim($directory, '/');
    $extension = strtolower((string) $file->getClientOriginalExtension());
    $filename  = Str::random(10) . time() . ($extension !== '' ? '.' . $extension : '');

    if (!R2StorageSettings::isReady()) {
        return $file->storeAs($directory, $filename, 'public');   // ← المسار المحلي
    }

    $settings = R2StorageSettings::all();
    $path = trim($settings['root_prefix'] . '/' . $directory . '/' . $filename, '/');
    $this->r2Disk($settings)->putFileAs(dirname($path), $file, basename($path));

    return self::R2_PREFIX . $path;                               // ← "r2://…"
}
```

نقاط مهمة:

- **اسم الملف عشوائي دائمًا** (`Str::random(10) . time()`) — لا يُستعمل اسم المستخدم
  الأصلي، وهذا يمنع Path Traversal ويمنع تصادم الأسماء.
- **الامتداد فقط** هو ما يُؤخذ من الملف الأصلي.
- **لا يوجد ضغط صور** هنا (على عكس `uploadFile()` القديمة).
- إذا كان R2 غير مفعّل أو غير مُتحقق منه، يعود النظام تلقائيًا للتخزين المحلي —
  أي أن تعطيل R2 لا يكسر شيئًا، فقط الملفات الجديدة تصير محلية.

### 3.4 المسار المحلي (الطريقة العادية)

- `$file->storeAs('images/Maid', 'xxx.pdf', 'public')`
- القرص `public` معرّف في [config/filesystems.php](config/filesystems.php):
  `root = storage_path('app/public')`, `url = APP_URL/storage`
- الملف الفعلي: `storage/app/public/images/Maid/xxx.pdf`
- القيمة المخزنة في DB: `images/Maid/xxx.pdf`
- **شرط العرض:** `php artisan storage:link` (يصنع `public/storage → storage/app/public`).
- العرض عبر `showFile()` الذي يتحقق `file_exists(public_path('storage/'.$path))`
  ويرجع `null` لو الملف مفقود، فلا تظهر صور مكسورة.

### 3.5 مسار R2

- القيمة المخزنة: `r2://<root_prefix>/<directory>/<filename>` مثال:
  `r2://clickerp/images/Maid/aB3xY9z11721.pdf`
- الـ disk لا يوجد في `config/filesystems.php` — يُبنى **في وقت التشغيل**
  ([ManagedFileStorage.php:138-150](app/Services/ManagedFileStorage.php#L138-L150)):

```php
private function r2Disk(?array $settings = null)
{
    $config = R2StorageSettings::diskConfig($settings);
    $hash   = hash('sha256', serialize($config));

    if (self::$configuredDiskHash !== $hash) {     // إعادة البناء فقط عند تغير الإعدادات
        config(['filesystems.disks.r2_runtime' => $config]);
        Storage::forgetDisk('r2_runtime');
        self::$configuredDiskHash = $hash;
    }

    return Storage::disk('r2_runtime');
}
```

وإعداد الـ disk نفسه ([R2StorageSettings::diskConfig](app/Support/R2StorageSettings.php#L91-L106)):

```php
[
    'driver'   => 's3',        // R2 متوافق مع S3 API
    'key'      => access_key_id,
    'secret'   => secret_access_key,
    'region'   => 'auto',      // R2 دائمًا auto
    'bucket'   => bucket,
    'endpoint' => https://<account_id>.r2.cloudflarestorage.com,
    'url'      => public_url,
    'use_path_style_endpoint' => bool,
    'throw'    => true,
]
```

سبب البناء في وقت التشغيل: الإعدادات تأتي من قاعدة البيانات (شاشة إدارية) وليس من
`.env`، فلا يمكن كتابتها في ملف الكونفج، ويجب أن تُحدَّث فورًا بعد الحفظ.

---

## 4. العرض والحذف والمعاينة

### 4.1 العرض — `showFile($path)`

```php
if (str_starts_with($path, 'r2://')) {
    return app(ManagedFileStorage::class)->url($path);
}
// وإلا: asset('/storage/'.$path) بشرط وجود الملف فعليًا
```

وداخل `ManagedFileStorage::url()`:

1. لو مُعرَّف `public_url` (نطاق `r2.dev` أو دومين مخصص) → رابط مباشر دائم:
   `https://files.example.com/clickerp/images/Maid/xxx.pdf`
2. لو غير مُعرَّف → **Presigned URL مؤقت صالح 30 دقيقة** عبر `temporaryUrl()`.
3. أي استثناء → `report($exception)` و `null` (لا يكسر الصفحة).

> نتيجة عملية: يمكنك ترك الـ Bucket **خاصًا تمامًا** وعدم تعريف `public_url`، وسيعمل
> النظام بروابط موقّعة مؤقتة. أما لو تريد روابط ثابتة/قابلة للتخزين المؤقت (CDN)
> فعرّف `public_url` واجعل الـ Bucket public أو ضع أمامه دومين مخصص.

### 4.2 الحذف — `deleteFile($path)`

- `r2://…` → `ManagedFileStorage::delete()` (يحذف من الـ Bucket).
- غير ذلك → المسار القديم `storage_path('/app/public/' . $path)` عبر `File::delete()`
  (`Constant::filesPath = "/app/public/"`).

### 4.3 معاينة PDF داخل النظام

بدل كشف رابط الملف مباشرة، يوجد بث (streaming) عبر السيرفر
([MaidController.php:1320-1348](app/Http/Controllers/ingaz/MaidController.php#L1320-L1348)):

```php
Route::get(... 'maids.cv.preview.data');   // → $storage->inlineResponse($maid->cv_file, ...)
```

و `inlineResponse()` تعمل:

- `abort_unless($this->exists($storedPath), 404)`
- تفتح `readStream()` من القرص الصحيح (R2 أو محلي) وتمرره بـ `fpassthru`
- ترويسات: `Content-Type: application/pdf`, `Content-Disposition: inline`,
  `X-Content-Type-Options: nosniff`, `Cache-Control: private, max-age=3600`

هذا يجعل المعاينة تمر بصلاحيات النظام حتى لو كان الـ Bucket خاصًا.

---

## 5. الإعدادات المطلوبة

### 5.1 على جانب Cloudflare

1. أنشئ **R2 Bucket** (مثلاً `clickerp-files`).
2. من R2 → **Manage API Tokens** أنشئ توكن بصلاحية **Object Read & Write** على هذا الـ Bucket
   واحفظ `Access Key ID` و `Secret Access Key` (الـ Secret يظهر مرة واحدة).
3. انسخ **S3 API Endpoint**: `https://<ACCOUNT_ID>.r2.cloudflarestorage.com`
   (بدون اسم الـ Bucket في النهاية).
4. اختياري — للوصول العام: فعّل `r2.dev` subdomain أو اربط **Custom Domain**،
   وضع الناتج في حقل «النطاق العام». اتركه فارغًا لو تريد روابط موقّعة مؤقتة فقط.

### 5.2 داخل النظام — `/admin/storage-settings`

الصلاحيات: العرض يحتاج `SETTINGS_VIEW` أو `SETTINGS_CONTROL`، والحفظ/الاختبار يحتاج
`SETTINGS_CONTROL`. المسارات في [routes/ingaz.php:508-510](routes/ingaz.php#L508-L510).

| الحقل | المفتاح | ملاحظات / Validation |
|---|---|---|
| تفعيل R2 | `enabled` | boolean |
| Endpoint | `endpoint` | **required**, `url`, max 500 |
| Bucket | `bucket` | **required**, 3–63 حرف, `^[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?$`, ممنوع `..` |
| بادئة المسار | `root_prefix` | `^[A-Za-z0-9_\-\/]*$`, الافتراضي `clickerp` |
| Access Key ID | `access_key_id` | **required** |
| Secret Access Key | `secret_access_key` | اتركه فارغًا للإبقاء على القديم؛ مطلوب أول مرة |
| النطاق العام | `public_url` | `url`, اختياري |
| Path style endpoint | `use_path_style_endpoint` | boolean (اتركه مغلقًا مع R2 عادةً) |

**التخزين:** كل الإعدادات صف واحد في جدول `new_settings` بالمفتاح `r2_storage` كـ JSON،
و `secret_access_key` يُخزن مشفّرًا بصيغة `encrypted:<Crypt::encryptString(...)>`
ويُفك عند القراءة. لذلك **لا تُنقل الإعدادات بين بيئات مختلفة الـ `APP_KEY`**، وإلا
سيفشل فك التشفير وسيصبح المفتاح فارغًا.

### 5.3 دورة «التحقق» (verified_at) — مهمة جدًا

```php
public static function isReady(?array $settings = null): bool
{
    return (bool) $settings['enabled']
        && filled($settings['verified_at'])   // ← لا يكفي التفعيل
        && filled($settings['endpoint'])
        && filled($settings['bucket'])
        && filled($settings['access_key_id'])
        && filled($settings['secret_access_key']);
}
```

- بعد الحفظ يجب الضغط على **اختبار الاتصال**؛ الاختبار يكتب ملفًا نصيًا في
  `<root_prefix>/connection-tests/<uuid>.txt`، يقرأه ويقارنه، ثم يحذفه. عند النجاح
  يُستدعى `markVerified()` فيُسجَّل `verified_at`.
- أي تعديل على حقول الاتصال (endpoint / bucket / key / secret / root_prefix / path style)
  يصفّر `verified_at` تلقائيًا ⇒ يعود الرفع للتخزين المحلي حتى تعيد الاختبار.
  هذا مقصود: يمنع رفع ملفات إلى إعداد خاطئ.

### 5.4 المتطلبات التقنية

- الحزمة `league/flysystem-aws-s3-v3: ^1.0` موجودة بالفعل في `composer.json` — لا شيء إضافي.
- للمسار المحلي: `php artisan storage:link` وصلاحيات كتابة على `storage/app/public`.
- لا حاجة لأي متغيرات `.env` لـ R2 (كل شيء من قاعدة البيانات). متغيرات `AWS_*` في
  `config/filesystems.php` تخص قرص `s3` منفصل ولا علاقة لها بالمنظومة هذه.
- الحد الأقصى لحجم الرفع يظل محكومًا بـ `upload_max_filesize` و `post_max_size` في PHP
  و `client_max_body_size` في Nginx — خصوصًا مع الفيديو.

---

## 6. إعادة استخدام المنطق في موديول آخر

المنظومة غير مرتبطة بالعاملات إطلاقًا (الاسم `uploadMaidFile` تاريخي فقط). الاستخدام:

### 6.1 الرفع

```php
use App\Services\ManagedFileStorage;

// إمّا عبر الهيلبر:
$path = uploadMaidFile($request->file('contract_pdf'), 'files/Contracts/');

// أو عبر حقن الخدمة (أفضل للاختبارات):
public function __construct(private ManagedFileStorage $storage) {}
$path = $this->storage->uploadMaidFile($request->file('contract_pdf'), 'files/Contracts/');

$model->update(['contract_pdf' => $path]);   // خزّن النص كما هو
```

### 6.2 العرض

```blade
@if ($url = showFile($model->contract_pdf))
    <a href="{{ $url }}" target="_blank">تحميل الملف</a>
@endif
```

### 6.3 الحذف / الاستبدال

```php
if ($request->hasFile('contract_pdf')) {
    deleteFile($model->contract_pdf);                                   // القديم
    $model->contract_pdf = uploadMaidFile($request->file('contract_pdf'), 'files/Contracts/');
}
```

### 6.4 المعاينة الآمنة (اختياري)

```php
public function preview($id, ManagedFileStorage $storage)
{
    $model = MyModel::findOrFail($id);
    abort_unless($storage->exists($model->contract_pdf), 404);

    return $storage->inlineResponse($model->contract_pdf, "contract-{$model->id}.pdf", 'application/pdf');
}
```

### 6.5 قواعد يجب الالتزام بها

0. **في الـ API استخدم `apiFileUrl()` وليس `showFile()`** — راجع القسم 6.6.
1. **لا تبنِ الرابط يدويًا** بـ `asset('storage/'.$path)` — سيكسر ملفات R2. استخدم
   `showFile()` أو `ManagedFileStorage::url()` دائمًا.
2. **لا تستخدم `Storage::disk('public')->delete()` مباشرة** — استخدم `deleteFile()`.
3. **طول العمود**: مسار R2 = `r2://` + prefix + مجلد + اسم 20 حرفًا + امتداد.
   `VARCHAR(255)` كافٍ، لكن انتبه لو الأعمدة عندك أقصر.
4. **تحقق من نوع الملف قبل الاستدعاء** (`instanceof UploadedFile`) — التوقيع
   `uploadMaidFile(UploadedFile $file, string $path)` يرمي `TypeError` مع النصوص.
5. **الـ validation ليس داخل الخدمة** — الخدمة لا تفحص MIME ولا الحجم. ضع
   `'file|mimes:pdf,jpg,jpeg,png|max:5120'` في الـ FormRequest بنفسك
   (كما في [StoreRapidCv.php:38](app/Http/Requests/StoreRapidCv.php#L38)).
6. **حجم الملفات الكبيرة**: `putFileAs` يرفع بشكل متزامن داخل الـ request؛ للفيديوهات
   الكبيرة فكّر في نقل الرفع إلى Job في الطابور.

### 6.6 عقد الملفات في الـ API (الموقع الخارجي والتطبيق)

الداشبورد تعرض عبر Blade فيمرّ كل شيء بـ `showFile()`. أما الـ API فكثير من نقاطه ترجّع
**الموديل نفسه** (لا Resource)، فكان العمود يخرج بقيمته المخزنة — ومع R2 تصبح
`r2://clickerp/...` وهي ليست رابطًا يفتحه أي كلاينت.

الحل طبقتان:

| الطبقة | الملف | الدور |
|---|---|---|
| هيلبر | [`apiFileUrl()`](app/Helpers/MainHelper.php#L354) | R2 → رابط مطلق، محلي → المسار النسبي كما هو |
| Trait | [`ResolvesMediaUrls`](app/Models/Concerns/ResolvesMediaUrls.php) | يطبّق الهيلبر على أعمدة الملفات عند `toArray()` |

```php
class Nationality extends Model
{
    use \App\Models\Concerns\ResolvesMediaUrls;

    protected array $mediaUrlColumns = ['logo'];
}
```

الموديلات المشمولة حاليًا: `Maid`, `Nationality`, `Slider`, `Service`, `AboutUs`, `Offer`,
`Client`, `WhatDo`, `Feature`. أي موديل جديد تُعرض ملفاته عبر الـ API يحتاج نفس السطرين.

> انتبه: `Statistic.image` و `Step.icon` و `WhyUs.icon` و `WhyChooseUsFeature.icon` هي
> **كلاسات أيقونات FontAwesome** لا ملفات، فلا تضف إليها الـ trait.

نقاط انتباه في التصميم:

- التدخل في **`toArray()` فقط** وليس `attributesToArray()`. السبب: قراءة الخاصية مباشرة
  (`$maid->cv_file`) يعتمد عليها الرفع والحذف والبث، وسجل النشاط (Spatie ActivityLog) يقرأ
  `attributesToArray()` — ولو حُوّلت هناك لصارت الروابط الموقّعة المؤقتة تُسجَّل كتغيير في
  كل حفظ.
- **الملفات المحلية لا تتغير قيمتها إطلاقًا** — يخرج المسار النسبي كما هو، فلا يتأثر أي
  كلاينت قائم.
- `apiFileUrl()` يحتفظ بكاش داخل الطلب الواحد لتفادي نداء `exists()` المتكرر إلى R2
  (الصفحة الرئيسية وحدها تكرر نفس الشعار عشرات المرات)، ويُعطَّل الكاش تلقائيًا عندما يكون
  `public_url` فارغًا لأن الروابط الموقّعة تنتهي بعد 30 دقيقة.

الاختبار: [tests/Feature/Models/ApiMediaUrlTest.php](tests/Feature/Models/ApiMediaUrlTest.php).

---

## 7. الفرق بين `uploadFile()` القديمة و `uploadMaidFile()` الجديدة

| | `uploadFile()` (MainHelper:299) | `uploadMaidFile()` (الجديد) |
|---|---|---|
| الوجهة | محلي فقط — `storage/app/public` | R2 عند الجاهزية، وإلا محلي |
| الآلية | `mkdir` يدوي + `$image->move()` | `Filesystem` API (`storeAs` / `putFileAs`) |
| ضغط الصور | يدعم تحويل إلى WebP بـ `Image::make()` | لا يوجد ضغط |
| القيمة المرجعة | `images/Maid/xxx.pdf` | نفسها، أو `r2://…` |
| Type safety | يقبل أي شيء | يفرض `UploadedFile` |
| logging | يسجّل كل خطوة | صامت |

> لو الموديول عندك يحتاج **ضغط الصور**، فحاليًا هذه الميزة موجودة فقط في `uploadFile()`
> القديمة. إما تضغط الصورة قبل تمريرها، أو تُضاف خطوة ضغط داخل `ManagedFileStorage`.

---

## 8. أشياء لم تُنقل بعد إلى R2 (نقاط انتباه)

- **مرفقات العاملة** `storeAttachments()` في
  [MaidController.php:1367-1371](app/Http/Controllers/ingaz/MaidController.php#L1367-L1371)
  ما زالت تستخدم `storeAs(..., 'public')` مباشرة — محلية دائمًا.
- **الملفات القديمة** المرفوعة قبل تفعيل R2 تبقى محلية؛ لا يوجد أمر ترحيل. تعمل بشكل
  طبيعي لأن `showFile()` تفرّق بينهما، لكن لا تحذف `storage/app/public` بعد تفعيل R2.
- **النسخ الاحتياطي**: بعد التفعيل صار لديك مصدران للملفات (المحلي القديم + R2).

---

## 9. Checklist سريع للتشغيل

```
[ ] php artisan storage:link            (للمسار المحلي)
[ ] إنشاء R2 Bucket + API Token (Object Read & Write)
[ ] /admin/storage-settings → إدخال Endpoint / Bucket / Keys / root_prefix
[ ] حفظ ثم الضغط على «اختبار الاتصال» حتى يظهر verified_at
[ ] تفعيل enabled + اختبار الاتصال (الترتيب لا يهم، لكن التحقق بعد آخر تعديل إلزامي)
[ ] رفع سيرة تجريبية والتأكد أن القيمة في DB تبدأ بـ r2://
[ ] فتح معاينة الـ PDF للتأكد من inlineResponse
[ ] (اختياري) ضبط public_url لو أردت روابط دائمة بدل الموقّعة المؤقتة
```
