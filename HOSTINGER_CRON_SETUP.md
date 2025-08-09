# 📝 دليل إعداد Cron Jobs على Hostinger

## 🎯 الخطوات:

### 1️⃣ **الدخول إلى لوحة تحكم Hostinger (hPanel)**
1. سجل دخولك إلى حسابك في Hostinger
2. اذهب إلى **hPanel** → **Advanced** → **Cron Jobs**

### 2️⃣ **إضافة Cron Job الرئيسي لـ Laravel**

في صفحة Cron Jobs، أضف المهمة التالية:

**Command:**
```bash
/usr/bin/php /home/u681103841/domains/yourdomain.com/public_html/artisan schedule:run >> /dev/null 2>&1
```

**أو إذا كان المسار مختلف:**
```bash
/usr/bin/php8.2 /home/u681103841/public_html/artisan schedule:run >> /dev/null 2>&1
```

**Frequency:** اختر **Every Minute (****)**

### 3️⃣ **التحقق من إصدار PHP الصحيح**

تأكد من استخدام إصدار PHP الصحيح:
```bash
# للتحقق من المسار الصحيح لـ PHP
which php
# أو
/usr/bin/php -v
/usr/bin/php8.1 -v
/usr/bin/php8.2 -v
```

### 4️⃣ **البديل: إضافة كل مهمة منفصلة**

إذا لم يعمل Laravel Scheduler، يمكنك إضافة كل مهمة منفصلة:

#### **تحديث حالة الضمان (يومياً 1:00 AM)**
```bash
/usr/bin/php /home/u681103841/public_html/artisan contracts:update-warranty-status
```
Frequency: **Daily at 01:00**

#### **تهيئة الضمان (كل 6 ساعات)**
```bash
/usr/bin/php /home/u681103841/public_html/artisan contracts:initialize-warranty
```
Frequency: **Every 6 hours**

## 📊 **جدول المهام المجدولة:**

| المهمة | التكرار | الوقت | الأمر |
|--------|---------|-------|-------|
| **Laravel Scheduler** | كل دقيقة | * * * * * | `schedule:run` |
| **تحديث الضمان** | يومياً | 0 1 * * * | `contracts:update-warranty-status` |
| **تهيئة الضمان** | كل 6 ساعات | 0 */6 * * * | `contracts:initialize-warranty` |

## 🔧 **حل المشاكل الشائعة:**

### **1. إذا لم يعمل Cron Job:**

أضف هذا في بداية الأمر لحفظ السجلات:
```bash
/usr/bin/php /home/u681103841/public_html/artisan schedule:run >> /home/u681103841/public_html/storage/logs/cron.log 2>&1
```

### **2. تحديد المسار الصحيح:**

في Hostinger، المسارات عادة تكون:
- **PHP Path:** `/usr/bin/php` أو `/usr/bin/php8.2`
- **Project Path:** `/home/u681103841/public_html/` أو `/home/u681103841/domains/yourdomain.com/public_html/`

### **3. التحقق من الصلاحيات:**

تأكد من أن ملف artisan قابل للتنفيذ:
```bash
chmod +x /home/u681103841/public_html/artisan
```

## 📋 **قائمة المهام المجدولة في النظام:**

### **تحديثات الضمان:**
- ✅ تحديث حالة الضمان يومياً (1:00 AM)
- ✅ تهيئة الضمان للعقود الجديدة (كل 6 ساعات)

### **تحديثات الطلبات:**
- ✅ تحديث تايمر الطلبات (كل 30 دقيقة)
- ✅ فحص الطلبات القديمة (شهرياً)

### **تحديثات العاملات:**
- ✅ تحديث حالة توفر العاملات (يومياً 2:00 AM)

### **صيانة النظام:**
- ✅ تنظيف السجلات القديمة (أسبوعياً)
- ✅ تحسين قاعدة البيانات (أسبوعياً)

## 🚀 **اختبار Cron Jobs:**

### **1. تشغيل يدوي للاختبار:**
```bash
cd /home/u681103841/public_html
php artisan schedule:run
```

### **2. عرض المهام المجدولة:**
```bash
php artisan schedule:list
```

### **3. مراقبة السجلات:**
```bash
tail -f /home/u681103841/public_html/storage/logs/laravel.log
tail -f /home/u681103841/public_html/storage/logs/warranty-update.log
```

## 📌 **ملاحظات مهمة لـ Hostinger:**

1. **الحد الأقصى:** Hostinger قد يحد من عدد Cron Jobs (عادة 5-10 حسب الخطة)
2. **التكرار:** الحد الأدنى للتكرار قد يكون كل 5 دقائق في بعض الخطط
3. **الموارد:** تأكد من عدم استهلاك موارد كثيرة لتجنب التعليق

## 💡 **نصائح للأداء:**

1. استخدم `withoutOverlapping()` لمنع تشغيل نفس المهمة مرتين
2. استخدم `runInBackground()` للمهام الطويلة
3. احفظ السجلات للمراقبة
4. راقب استخدام الموارد من لوحة التحكم

---

**بعد الإعداد، يجب أن تعمل جميع المهام المجدولة تلقائياً!** ✅
