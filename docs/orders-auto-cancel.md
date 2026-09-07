# تشغيل الإلغاء التلقائي للطلبات

إدارة المدد موجودة في `/admin/notifications-settings/alerts` ويمكن الوصول إليها من رابط «إدارة مدد الطلبات» أعلى الطلبات. القيم الافتراضية: الجديد 24 ساعة، تحت الإجراء 48 ساعة، والتنبيه العاجل قبل ساعتين. الحفظ يعيد حساب المدد للطلبات الحالية في كل الفروع من بداية الحالة، ولا يبدأ مهلة جديدة من وقت الحفظ.

الأمر `orders:cancel-expired` يلغي الطلبات الجديدة فقط وفق نفس موعد عمود التنبيهات، بما فيها القديمة بلا `deadline_at`. يتجاهل الطلبات المرتبطة بعقود. تصبح الحالة `canceled_by_system`، وتتاح العاملة إذا لم ترتبط بطلب نشط أو عقد قائم. لا يلغي طلبات تحت الإجراء. الفاصل الافتراضي للفحص 60 دقيقة ويمكن تغييره من «إلغاء الطلبات تلقائيا كل»؛ الإلغاء يحدث في أول فحص بعد انتهاء المهلة.

## على سيرفر Linux

1. ارفع التعديلات ثم ادخل مجلد المشروع الذي يحتوي على `artisan`:

```bash
cd /path/to/click-erp
php artisan view:clear
php artisan schedule:list
php artisan orders:cancel-expired --dry-run
```

المعاينة لا تعدل الطلبات. تشمل المعالجة الفعلية الطلبات القديمة المنتهية أيضًا. لا توجد migration مطلوبة لهذه التعديلات.

2. اعرف مسار PHP عبر `command -v php`. افتح `crontab -e` بحساب الموقع الذي يستطيع الكتابة في `storage` و`bootstrap/cache`، وأضف السطر التالي مع تعديل المسارات:

```cron
* * * * * cd /path/to/click-erp && /usr/bin/php artisan schedule:run >> /path/to/click-erp/storage/logs/scheduler.log 2>&1
```

إذا كان `schedule:run` موجودًا لنفس المشروع فلا تضفه مرة ثانية. هذا السطر يشغل مجدول التطبيق كله كل دقيقة، وكل مهمة تعمل وفق موعدها المحدد. في cPanel اختر Cron Jobs، واجعل حقول الوقت كلها `*`، وضع الجزء الذي يبدأ بـ `cd` في خانة الأمر.

3. راقب التنفيذ:

```bash
tail -f storage/logs/orders-auto-cancel.log
```

للتنفيذ الفعلي فورًا: `php artisan orders:cancel-expired`. الأمر القديم `orders:handle-expired` أصبح اسمًا متوافقًا لنفس المعالجة ولم يعد مجدولًا منفصلًا. لا حاجة إلى queue worker لهذا الأمر.

مرجع: [Laravel Task Scheduling](https://laravel.com/docs/10.x/scheduling#running-the-scheduler).
