# أوامر الصلاحيات

هذا الملف مخصص لتجهيز الصلاحيات بعد النشر أو بعد أي تحديث يضيف أو يعدل صلاحية.

روابط التحقق:

```text
محلي:      http://127.0.0.1:8000/dash/perms/1
استضافة:  https://www.platinumeastern.com/dash/perms/1
```

الرقم `1` هو رقم الدور الرئيسي غالبا. إذا كان دور المدير في الاستضافة له رقم مختلف، استبدله في الرابط وفي الأمر `--role-id`.

## 1. الدخول لمسار المشروع

```bash
cd /home/platinum/public_html
```

## 2. تحديث الكود والحزم

```bash
git pull --ff-only origin main
git submodule sync --recursive
git submodule update --init --recursive
composer install --no-dev --optimize-autoloader
composer dump-autoload -o
php artisan optimize:clear
```

## 3. تشغيل migrations المطلوبة للصلاحيات

```bash
php artisan migrate --force
```

إذا كانت جداول Laratrust مثل `permission_role` غير موجودة:

```bash
php artisan migrate --path=database/migrations/permissions --force
```

تحقق من حالة migrations:

```bash
php artisan migrate:status
```

## 4. الأمر المعتمد لتجهيز كل صلاحيات السيرفر

هذا هو الأمر المفضل بعد أي تحديث صلاحيات. يقوم بمزامنة كل الصلاحيات الظاهرة في شاشة الصلاحيات، ثم يعطيها للدور الرئيسي، ويمكنه ربط أول مدير بهذا الدور.

```bash
php artisan permissions:prepare-server --role-id=1 --role-name=Administrator --attach-first-admin
php artisan permission:cache-reset
```

المهم بعد تنفيذ الأمر أن يظهر في نهاية الخرج:

```text
Final verification:
Screen total: ...
Role selected: ...
Missing selected: 0
```

لا تعتمد على رقم ثابت مثل `1047` أو `1013` لأن الرقم يتغير حسب آخر migrations وتعريفات الصلاحيات الموجودة على السيرفر. المعيار الصحيح هو أن يكون:

```text
Role selected = Screen total
Missing selected = 0
```

إذا كان دور المدير ليس رقمه `1`، عدل الرقم:

```bash
php artisan permissions:prepare-server --role-id=ROLE_ID --role-name=Administrator --attach-first-admin
php artisan permission:cache-reset
```

## 5. التحقق بعد التنفيذ

افتح شاشة صلاحيات الدور:

```text
https://www.platinumeastern.com/dash/perms/1
```

ثم تأكد من التالي:

```text
الصلاحيات الجديدة ظاهرة في الشاشة.
الدور Administrator لديه الصلاحيات المطلوبة.
لا توجد صفحة تظهر 403 لمستخدم المدير.
```

أوامر تحقق مساعدة:

```bash
php artisan route:list | grep dash.perms
php artisan permission:cache-reset
php artisan optimize:clear
```

لا تفصل صلاحيات عقود الاستقدام أو التأجير أو أي قسم آخر بأوامر مستقلة. بعد أي تحديث استخدم الأمر الشامل فقط:

```bash
php artisan permissions:prepare-server --role-id=1 --role-name=Administrator --attach-first-admin
php artisan permission:cache-reset
```

إذا كان إجمالي الاستضافة أقل من المحلي، مثل المحلي يعرض رقما أعلى والاستضافة تعرض رقما أقل، فهذا يعني غالبا أن الاستضافة لم تشغل كل migrations أو لم تحدث الحزم الفرعية. نفذ بالترتيب:

```bash
git status
git submodule status --recursive
git pull --ff-only origin main
git submodule sync --recursive
git submodule update --init --recursive
composer install --no-dev --optimize-autoloader
composer dump-autoload -o
php artisan migrate --force
php artisan migrate:status
php artisan permissions:prepare-server --role-id=1 --role-name=Administrator --attach-first-admin
php artisan permission:cache-reset
php artisan optimize:clear
```

بعدها راجع آخر خرج للأمر. إذا كان `Missing selected` ليس `0`، لا تعتمد على الشاشة قبل معالجة الأسماء الناقصة التي يطبعها الأمر.

## 6. ترتيب مختصر بعد أي تحديث صلاحية

```bash
cd /home/platinum/public_html
git pull --ff-only origin main
git submodule sync --recursive
git submodule update --init --recursive
composer install --no-dev --optimize-autoloader
composer dump-autoload -o
php artisan migrate --force
php artisan permissions:prepare-server --role-id=1 --role-name=Administrator --attach-first-admin
php artisan permission:cache-reset
php artisan optimize:clear
php artisan config:cache
```

بعدها افتح:

```text
https://www.platinumeastern.com/dash/perms/1
```
