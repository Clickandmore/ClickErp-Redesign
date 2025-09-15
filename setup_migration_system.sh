#!/bin/bash

# =============================================================================
# سكريبت تثبيت وإعداد نظام نقل البيانات
# Data Migration System Installation Script
# =============================================================================

set -e

echo "🚀 بدء تثبيت نظام نقل البيانات..."

# متغيرات الإعداد
DB_HOST="localhost"
DB_PORT="8889"
DB_USER="root"
DB_PASS="root"
DB_NAME="platinum_migration"
SYSTEM_PATH="/Applications/MAMP/htdocs/ERP_V/migration_system"

# ألوان للنصوص
RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

print_step() {
    echo -e "${BLUE}[خطوة]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[نجح]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[تحذير]${NC} $1"
}

print_error() {
    echo -e "${RED}[خطأ]${NC} $1"
}

# التحقق من متطلبات النظام
check_requirements() {
    print_step "التحقق من متطلبات النظام..."

    # التحقق من PHP
    if ! command -v php &> /dev/null; then
        print_error "PHP غير مثبت. يرجى تثبيت PHP 7.4 أو أحدث."
        exit 1
    fi

    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    print_success "PHP الإصدار: $PHP_VERSION"

    # التحقق من MySQL
    if ! command -v mysql &> /dev/null; then
        print_error "MySQL غير مثبت. يرجى تثبيت MySQL 5.7 أو أحدث."
        exit 1
    fi

    print_success "MySQL متوفر"

    # التحقق من امتدادات PHP المطلوبة
    REQUIRED_EXTENSIONS=("pdo" "pdo_mysql" "json" "mbstring" "fileinfo")
    for ext in "${REQUIRED_EXTENSIONS[@]}"; do
        if php -m | grep -q "^$ext$"; then
            print_success "امتداد PHP: $ext ✓"
        else
            print_error "امتداد PHP المطلوب غير متوفر: $ext"
            exit 1
        fi
    done
}

# إنشاء قاعدة البيانات
create_database() {
    print_step "إنشاء قاعدة البيانات..."

    # محاولة الاتصال بـ MySQL
    if ! mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" -e "SELECT 1;" &> /dev/null; then
        print_error "فشل الاتصال بـ MySQL. تأكد من إعدادات الاتصال."
        exit 1
    fi

    # إنشاء قاعدة البيانات
    mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" -e "
        CREATE DATABASE IF NOT EXISTS \`$DB_NAME\`
        CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    "

    print_success "تم إنشاء قاعدة البيانات: $DB_NAME"

    # تشغيل سكريبت الإعداد
    if [ -f "database_setup.sql" ]; then
        print_step "تشغيل سكريبت إعداد قاعدة البيانات..."
        mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < database_setup.sql
        print_success "تم تشغيل سكريبت الإعداد بنجاح"
    else
        print_warning "ملف database_setup.sql غير موجود"
    fi
}

# إعداد الملفات والمجلدات
setup_files() {
    print_step "إعداد الملفات والمجلدات..."

    # إنشاء المجلدات المطلوبة
    DIRECTORIES=("uploads" "logs" "uploads/$(date +%Y)" "uploads/$(date +%Y)/$(date +%m)" "uploads/$(date +%Y)/$(date +%m)/$(date +%d)")

    for dir in "${DIRECTORIES[@]}"; do
        if [ ! -d "$SYSTEM_PATH/$dir" ]; then
            mkdir -p "$SYSTEM_PATH/$dir"
            print_success "تم إنشاء المجلد: $dir"
        fi
    done

    # ضبط الصلاحيات
    print_step "ضبط صلاحيات الملفات..."
    chmod -R 755 "$SYSTEM_PATH"
    chmod -R 777 "$SYSTEM_PATH/uploads"
    chmod -R 777 "$SYSTEM_PATH/logs"

    print_success "تم ضبط صلاحيات الملفات"
}

# تحديث إعدادات قاعدة البيانات
update_config() {
    print_step "تحديث إعدادات قاعدة البيانات..."

    CONFIG_FILE="$SYSTEM_PATH/config/database.php"

    if [ -f "$CONFIG_FILE" ]; then
        # إنشاء نسخة احتياطية من الإعدادات
        cp "$CONFIG_FILE" "$CONFIG_FILE.backup.$(date +%Y%m%d_%H%M%S)"

        # تحديث إعدادات الاتصال (يمكن تخصيصها حسب الحاجة)
        sed -i.bak "s/'host' => '[^']*'/'host' => '$DB_HOST'/g" "$CONFIG_FILE"
        sed -i.bak "s/'port' => [0-9]*/'port' => $DB_PORT/g" "$CONFIG_FILE"
        sed -i.bak "s/'username' => '[^']*'/'username' => '$DB_USER'/g" "$CONFIG_FILE"
        sed -i.bak "s/'password' => '[^']*'/'password' => '$DB_PASS'/g" "$CONFIG_FILE"
        sed -i.bak "s/'database' => 'platinum_migration'/'database' => '$DB_NAME'/g" "$CONFIG_FILE"

        rm "$CONFIG_FILE.bak"
        print_success "تم تحديث إعدادات قاعدة البيانات"
    else
        print_warning "ملف الإعدادات غير موجود: $CONFIG_FILE"
    fi
}

# اختبار النظام
test_system() {
    print_step "اختبار النظام..."

    # اختبار اتصال قاعدة البيانات
    TEST_SCRIPT="
    <?php
    require_once '$SYSTEM_PATH/config/database.php';
    try {
        \$db = Database::getInstance();
        \$result = \$db->fetch('SELECT COUNT(*) as count FROM migration_batches');
        echo 'SUCCESS: Database connection working. Batches count: ' . \$result['count'] . PHP_EOL;
    } catch (Exception \$e) {
        echo 'ERROR: ' . \$e->getMessage() . PHP_EOL;
        exit(1);
    }
    ?>
    "

    echo "$TEST_SCRIPT" | php

    if [ $? -eq 0 ]; then
        print_success "اختبار قاعدة البيانات نجح"
    else
        print_error "اختبار قاعدة البيانات فشل"
        exit 1
    fi
}

# إنشاء ملف .htaccess للأمان
create_htaccess() {
    print_step "إنشاء ملف .htaccess للأمان..."

    HTACCESS_CONTENT="
# حماية ملفات النظام
<FilesMatch \"\.(sql|log|md)$\">
    Require all denied
</FilesMatch>

# حماية مجلد config
<Directory \"config\">
    Require all denied
</Directory>

# حماية مجلد logs
<Directory \"logs\">
    Require all denied
</Directory>

# إعدادات PHP
php_value upload_max_filesize 50M
php_value post_max_size 50M
php_value memory_limit 256M
php_value max_execution_time 300

# إعادة كتابة عناوين URL
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?path=\$1 [QSA,L]
"

    echo "$HTACCESS_CONTENT" > "$SYSTEM_PATH/.htaccess"
    print_success "تم إنشاء ملف .htaccess"
}

# إنشاء مهمة cron للصيانة
create_cron_job() {
    print_step "إعداد مهمة صيانة تلقائية..."

    CRON_SCRIPT="$SYSTEM_PATH/cron_maintenance.php"

    CRON_CONTENT="<?php
/**
 * مهمة صيانة تلقائية للنظام
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

try {
    \$db = Database::getInstance();

    // حذف البيانات المؤقتة القديمة (أكثر من 7 أيام)
    \$deleted_temp = \$db->query(\"
        DELETE FROM temp_import_data
        WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
    \")->rowCount();

    // حذف الملفات المرفوعة القديمة
    \$upload_dir = __DIR__ . '/uploads';
    \$deleted_files = 0;

    \$iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(\$upload_dir)
    );

    foreach (\$iterator as \$file) {
        if (\$file->isFile() && time() - \$file->getMTime() > (7 * 24 * 60 * 60)) {
            unlink(\$file->getPathname());
            \$deleted_files++;
        }
    }

    // تسجيل النشاط
    error_log(\"[CRON] Maintenance completed: {\$deleted_temp} temp records deleted, {\$deleted_files} old files removed\");

    echo \"Maintenance completed successfully\\n\";

} catch (Exception \$e) {
    error_log(\"[CRON] Maintenance error: \" . \$e->getMessage());
    echo \"Maintenance failed: \" . \$e->getMessage() . \"\\n\";
    exit(1);
}
?>"

    echo "$CRON_CONTENT" > "$CRON_SCRIPT"
    chmod +x "$CRON_SCRIPT"

    print_success "تم إنشاء سكريبت الصيانة: $CRON_SCRIPT"
    print_warning "لإضافة مهمة cron يدوياً، استخدم: 0 2 * * * /usr/bin/php $CRON_SCRIPT"
}

# تشغيل جميع خطوات التثبيت
main() {
    echo "======================================="
    echo "   نظام نقل البيانات - سكريبت التثبيت"
    echo "======================================="

    check_requirements
    create_database
    setup_files
    update_config
    create_htaccess
    create_cron_job
    test_system

    echo ""
    echo "======================================="
    print_success "تم تثبيت النظام بنجاح! 🎉"
    echo "======================================="
    echo ""
    echo "📍 معلومات النظام:"
    echo "   - المسار: $SYSTEM_PATH"
    echo "   - قاعدة البيانات: $DB_NAME"
    echo "   - رابط النظام: http://localhost/ERP_V/migration_system/"
    echo ""
    echo "📋 الخطوات التالية:"
    echo "   1. تأكد من تشغيل MAMP/XAMPP"
    echo "   2. افتح المتصفح وانتقل للرابط أعلاه"
    echo "   3. ابدأ بنقل البيانات من صفحة 'رفع الملفات'"
    echo ""
    echo "📖 للمساعدة، راجع ملف README.md"
    echo ""
}

# تشغيل التثبيت
main "$@"
