# 🪙 موديول إدارة الماليات (Finance Module)

## 🧾 الوصف
موديول **إدارة الماليات** هو مكون داخل النظام مسؤول عن:

- 📌 إدارة القيود اليومية والحسابات.  
- 🧾 إدارة الشيكات (صرف – قبض).  
- 📊 عرض وتحليل التقارير المالية.  
- 🧮 التعامل مع القوائم المالية والميزانيات.  
- 🏦 دعم نظام الحسابات المزدوجة.

يساعد هذا الموديول على تنظيم الدورة المالية للمؤسسة ومتابعة الحركة المالية بدقة واحترافية.

---

## ✨ المميزات
- 💰 **إدارة كاملة للحسابات المالية**.  
- 🧾 **إدارة الشيكات** (صرف / قبض) مع تتبع حالتها.  
- 📊 **تقارير مالية دقيقة وتفصيلية**.  
- 🧮 **نظام حسابات مزدوجة** ودليل حسابات مرن.  
- 📈 **دعم الميزانية وقوائم الدخل الشهرية**.  
- 🌐 **دعم تعدد اللغات** وإمكانية التخصيص.

---

## ⚙️ المتطلبات
- PHP `^8.1`  
- Laravel `^10`  
- Composer  
- MySQL Database

---

## 🚀 خطوات التثبيت

بعد نسخ المشروع أو الموديول داخل مجلد `packages` في مشروع Laravel الخاص بك، اتبع الخطوات التالية:

### 1. إنشاء مجلد `packages` (إذا لم يكن موجودًا)
```bash
mkdir -p packages
```

### 2. نقل مجلد الموديول إلى المسار الصحيح
```bash
mv finance packages
```

---

### 3. تسجيل الـ Service Provider
افتح ملف:
```
config/app.php
```
ثم أضف هذا السطر داخل مصفوفة `providers`:
```php
Nami\Finance\FinanceServiceProvider::class,
```

---

### 4. تعديل الـ Autoload في Composer
افتح ملف:
```
composer.json
```
ثم أضف الأسطر التالية تحت `autoload > psr-4`:
```json
"Nami\\Finance\\": "packages/finance/src/",
"Nami\\Finance\\Database\\Seeders\\": "packages/finance/database/seeders/"
```

---

### 5. تحديث Autoload
قم بتحديث الـ autoload من Composer بالأمر:
```bash
composer dump-autoload
```

---

### 6. تنفيذ المايجريشن
قم بإنشاء الجداول الخاصة بالموديول:
```bash
php artisan migrate
```

---

### 7. تشغيل Seeder الخاص بالموديول
قم بتشغيل Seeder الأساسي:
```bash
php artisan db:seed --class="Nami\Finance\Database\Seeders\FinanceDatabaseSeeder"
```

---

## 🧑‍💻 ملاحظات
- تأكد من أن المجلد في المسار التالي:
  ```
  packages/finance
  ```
- يمكنك تعديل إعدادات الموديول أو توسيعه حسب احتياج مشروعك.
- هذا الموديول مصمم ليكون **مرنًا** وقابلًا للتكامل مع باقي وحدات النظام.

---

## 📄 الترخيص
هذا المشروع مفتوح المصدر ويمكن استخدامه بحرية في المشاريع التجارية أو الشخصية مع الحفاظ على الإشارة إلى المصدر.

---

## 🧭 ملاحظة مهمة
حتى تتمكن من الوصول إلى صفحات الموديول من لوحة التحكم،
يجب إضافة الكود التالي في **ملف الـ Sidebar** الخاص بالمشروع الأصلي:

```php
@php
    $isFinance = request()->is('finance*');
@endphp
<li class="{{ $isFinance ? '' : 'collapsed' }}">
    <a class="m-link" data-bs-toggle="collapse" data-bs-target="#finance" href="#">
        <i class="fa fa-file-text"></i>
        <span class="ms-2">{{ helperTrans('finance') }}</span>
        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
    </a>
    <ul class="sub-menu collapse {{ $isFinance ? 'show' : '' }}" id="finance">
        @include('finance::finance')
    </ul>
</li>
 @include('finance::stock')
 @include('finance::zatca')
```
✍️ بعد توجيهات الاستشاري المهندس أحمد مصطفى
