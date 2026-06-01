# أوامر استخراج بيانات السيرة الذاتية

هذا الملف مخصص لتجهيز السيرفر لميزة استخراج بيانات السيرة في:

```text
/admin/maids/create
```

إذا ظهرت الرسالة التالية:

```text
قراءة الملف من السيرة تحتاج تفعيل shell_exec على السيرفر.
```

فهذا يعني أن PHP على السيرفر يمنع تشغيل أوامر النظام، والميزة لن تستطيع قراءة PDF أو الصور حتى يتم تفعيل `shell_exec` وتثبيت أدوات قراءة الملفات.

## 1. تفعيل shell_exec في PHP

افتح إعدادات PHP المستخدمة للموقع، ثم تأكد أن `shell_exec` غير موجودة داخل `disable_functions`.

مثال قبل التعديل:

```ini
disable_functions = exec,passthru,shell_exec,system,proc_open,popen
```

مثال بعد التعديل:

```ini
disable_functions = exec,passthru,system,proc_open,popen
```

بعد التعديل أعد تشغيل PHP أو Apache/Nginx حسب نوع السيرفر.

على سيرفرات cPanel غالبا يتم التعديل من:

```text
WHM > MultiPHP INI Editor
```

أو من:

```text
cPanel > Select PHP Version > Options > disable_functions
```

إذا لم تظهر هذه الخيارات، يجب طلب التفعيل من شركة الاستضافة لأن بعض الاستضافات المشتركة تمنع `shell_exec` نهائيا.

## 2. تثبيت أدوات قراءة PDF والصور

الميزة تستخدم:

```text
pdftotext   قراءة PDF النصي
pdftoppm    تحويل أول صفحة من PDF إلى صورة
tesseract   OCR لقراءة الصور و PDF المصور
imagick     امتداد PHP لمعالجة الصور واستخراج صورة العاملة
ghostscript بديل لتحويل PDF إلى صورة عند الحاجة
```

### Ubuntu / Debian

```bash
sudo apt update
sudo apt install -y poppler-utils tesseract-ocr tesseract-ocr-eng tesseract-ocr-ara imagemagick ghostscript php-imagick
sudo systemctl restart apache2
```

إذا كان السيرفر يستخدم PHP-FPM:

```bash
sudo systemctl restart php-fpm
sudo systemctl restart nginx
```

قد يكون اسم خدمة PHP-FPM محددا بالإصدار، مثال:

```bash
sudo systemctl restart php8.2-fpm
```

### AlmaLinux / Rocky / CentOS

```bash
sudo dnf install -y poppler-utils tesseract tesseract-langpack-eng tesseract-langpack-ara ImageMagick ghostscript php-pecl-imagick
sudo systemctl restart httpd
```

إذا كان السيرفر يستخدم PHP-FPM:

```bash
sudo systemctl restart php-fpm
sudo systemctl restart nginx
```

### cPanel / WHM

نفذ من SSH إذا كانت صلاحية root متاحة:

```bash
yum install -y poppler-utils tesseract tesseract-langpack-eng tesseract-langpack-ara ImageMagick ghostscript
```

ثم فعّل امتداد `imagick` من:

```text
WHM > EasyApache 4
```

أو من:

```text
WHM > Software > Module Installers > PHP PECL > imagick
```

بعدها أعد تشغيل Apache/PHP-FPM من WHM أو SSH.

## 3. التحقق بعد التثبيت

من SSH تحقق من وجود الأوامر:

```bash
command -v pdftotext
command -v pdftoppm
command -v tesseract
command -v gs
php -m | grep -i imagick
php -r "var_dump(function_exists('shell_exec'));"
```

يجب أن تكون نتيجة آخر أمر:

```text
bool(true)
```

وتحقق من لغات Tesseract:

```bash
tesseract --list-langs
```

يجب أن تظهر:

```text
eng
ara
```

## 4. أوامر Laravel بعد تجهيز السيرفر

من مسار المشروع:

```bash
cd /home/platinum/public_html
composer dump-autoload -o
php artisan optimize:clear
php artisan config:cache
```

بعدها جرّب رفع PDF نصي أو صورة واضحة من صفحة:

```text
/admin/maids/create
```

## 5. ملاحظات مهمة

ملفات PDF النصية تحتاج غالبا `pdftotext` فقط.

ملفات PDF المصورة أو صور JPG/PNG تحتاج `tesseract` ويفضل وجود `imagick`.

استخراج صورة العاملة من السيرة يحتاج امتداد PHP `imagick`. إذا لم يكن مثبتا ستستمر قراءة النص، لكن قد لا يتم استخراج الصورة تلقائيا.

إذا كانت الاستضافة مشتركة ولا تسمح بتفعيل `shell_exec`، يجب نقل هذه الميزة إلى VPS أو سيرفر يسمح بتشغيل أوامر النظام.
