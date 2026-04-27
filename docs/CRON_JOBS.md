# أوامر Cron Job

هذا الملف مخصص لتشغيل Laravel Scheduler على الاستضافة والتحقق من المهام المجدولة.

## 1. أمر cron المطلوب في الاستضافة

أضف هذا السطر في Cron Jobs من لوحة الاستضافة:

```cron
* * * * * cd /home/platinum/public_html && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

إذا كان مسار PHP مختلفا في الاستضافة، استخدم المسار الصحيح. للتحقق:

```bash
which php
php -v
```

مثال شائع إذا كان PHP داخل cPanel:

```cron
* * * * * cd /home/platinum/public_html && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

## 2. التحقق اليدوي من Laravel Scheduler

من مسار المشروع:

```bash
cd /home/platinum/public_html
php artisan schedule:list
php artisan schedule:run
```

إذا ظهر خطأ `class not found` أو تغييرات غير محدثة:

```bash
composer dump-autoload -o
php artisan optimize:clear
php artisan config:cache
```

## 3. المهام المجدولة حاليا

المهام معرفة في:

```text
app/Console/Kernel.php
```

القائمة الحالية:

```text
contracts:update-warranty-status
يعمل يوميا حسب إعداد warranty_status_update_time، والقيمة الافتراضية 01:00.

contracts:initialize-warranty
يعمل كل عدد ساعات حسب warranty_initialize_interval_hours، والقيمة الافتراضية كل 6 ساعات.

update-orders-timer
يتابع الطلبات المتأخرة والعاجلة حسب orders_timer_interval_minutes، والقيمة الافتراضية كل 30 دقيقة.

check-old-orders
يتحقق شهريا من الطلبات القديمة المعلقة حسب old_pending_orders_days، والقيمة الافتراضية 30 يوم.

update-maids-availability
يعمل يوميا الساعة 02:00 لتحديث توفر العاملات التي انتهت عقودهن.

log:clear --keep=30
يعمل أسبوعيا يوم الأحد الساعة 03:00 لتنظيف السجلات القديمة.

optimize-database
يعمل أسبوعيا يوم الأحد الساعة 04:00 لتحسين جداول contracts و orders و maids و users.

orders:cancel-expired
يعمل حسب orders_auto_cancel_interval_minutes، والقيمة الافتراضية كل 60 دقيقة.

orders:handle-expired
يعمل حسب orders_expired_handler_interval_minutes، والقيمة الافتراضية كل 5 دقائق.
```

## 4. إعدادات التحكم في مواعيد الأتمتة

القيم الافتراضية معرفة في:

```text
app/Support/AutomationSettings.php
```

المفاتيح المستخدمة:

```text
warranty_period_days                         90
order_expiration_hours                       24
order_new_deadline_hours                     24
order_under_process_deadline_hours           48
order_urgent_threshold_hours                 2
old_pending_orders_days                      30
warranty_status_update_time                  01:00
warranty_initialize_interval_hours           6
orders_timer_interval_minutes                30
orders_expired_handler_interval_minutes      5
orders_auto_cancel_interval_minutes          60
```

هذه القيم تقرأ من جدول الإعدادات قبل الرجوع للقيم الافتراضية.

## 5. أوامر تشغيل فردية عند الحاجة

يمكن تشغيل الأوامر يدويا للتحقق:

```bash
php artisan contracts:update-warranty-status
php artisan contracts:initialize-warranty
php artisan orders:cancel-expired
php artisan orders:handle-expired
php artisan log:clear --keep=30
```

أوامر الطلبات الإضافية المسجلة في المشروع:

```bash
php artisan orders:update-expiration
php artisan orders:update-timer
php artisan orders:timer-cron
php artisan orders:test-expired
```

## 6. ملفات السجل

تحديث حالة الضمان يكتب في:

```text
storage/logs/warranty-update.log
```

سجل Laravel العام:

```text
storage/logs/laravel.log
```

للتحقق السريع:

```bash
tail -n 100 storage/logs/laravel.log
tail -n 100 storage/logs/warranty-update.log
```

## 7. ترتيب مختصر بعد تحديث الكرون

```bash
cd /home/platinum/public_html
git pull --ff-only origin main
composer install --no-dev --optimize-autoloader
composer dump-autoload -o
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan schedule:list
php artisan schedule:run
```

تأكد أن Cron Job في لوحة الاستضافة ما زال بهذا الشكل:

```cron
* * * * * cd /home/platinum/public_html && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```
