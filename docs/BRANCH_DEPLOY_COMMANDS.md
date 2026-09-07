# أوامر نشر تحديث الفروع

> نفّذ الأوامر من مجلد المشروع على سيرفر العميل، وبعد أخذ نسخة احتياطية من قاعدة البيانات.

## 1. نسخة احتياطية

```bash
mysqldump -u DB_USER -p DB_NAME > branches-before-deploy.sql
```

## 2. وضع الصيانة وتحديث قاعدة البيانات

```bash
php artisan down
php artisan migrate --force
php artisan optimize:clear
```

## 3. تجهيز حسابات ومراكز تكلفة الفروع

```bash
php artisan branches:provision-finance --dry-run
php artisan branches:provision-finance
```

## 4. فحص وإسناد السجلات القديمة القابلة للاشتقاق

```bash
php artisan branches:audit
php artisan branches:backfill --dry-run
php artisan branches:backfill
php artisan branches:audit
```

## 5. إنهاء وضع الصيانة

```bash
php artisan up
```

## أوامر فروع الموارد البشرية

هذه هي الأوامر المطلوبة لتحديث فروع الموارد البشرية عند تنفيذها ضمن عملية الرفع الكاملة:

```bash
php artisan migrate --force
php artisan branches:backfill --dry-run --table=hr_employee
php artisan branches:backfill --table=hr_employee
php artisan branches:backfill
php artisan branches:audit
```

للمراجعة التفصيلية قبل الإسناد:

```bash
php artisan branches:backfill --dry-run --table=hr_payrolls
php artisan branches:backfill --dry-run --table=hr_tasks
php artisan branches:audit --section=data --details
```

> أمر `branches:backfill` يُسنِد العلاقات القابلة للاشتقاق فقط. مسيرات الرواتب والمهام القديمة التي بلا فرع لا تُسنَد تلقائياً؛ راجعها أولاً لأن السجل القديم قد يحتوي موظفين من أكثر من فرع.

## اختياري — بعد مراجعة نتيجة `dry-run` فقط

الأوامر التالية تُسند السجلات التي تعذّر تحديد فرعها إلى **الفرع الرئيسي**. لا تُنفّذها على قاعدة العميل قبل مراجعة الأعداد والتأكد أن هذا هو الفرع الصحيح:

```bash
php artisan branches:backfill --fallback-main --table=users --dry-run
php artisan branches:backfill --fallback-main --table=complaints --dry-run
php artisan branches:backfill --fallback-main --table=commission_batch_items --dry-run
```

بعد اعتماد النتيجة، تُعاد الأوامر نفسها بدون `--dry-run` وبالترتيب نفسه:

```bash
php artisan branches:backfill --fallback-main --table=users
php artisan branches:backfill --fallback-main --table=complaints
php artisan branches:backfill --fallback-main --table=commission_batch_items
php artisan branches:audit
```

> لا تشغّل `php artisan branches:backfill --fallback-main` بدون `--table`؛ لأنه قد يُسنِد عدداً كبيراً من سجلات النظام إلى الفرع الرئيسي.
