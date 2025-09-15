#!/bin/bash

# =============================================================================
# سكريبت تشغيل النقل الشامل التلقائي
# Automatic Full Migration Runner
# =============================================================================

set -e

# ألوان للنصوص
RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

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

# التأكد من وجود قواعد البيانات
check_databases() {
    print_step "التحقق من قواعد البيانات..."

    # فحص قاعدة erp_operations_new
    if ! /Applications/MAMP/Library/bin/mysql80/bin/mysql -h localhost -P 8889 -u root -proot -e "USE erp_operations_new; SELECT 1;" &>/dev/null; then
        print_error "قاعدة البيانات erp_operations_new غير متوفرة"
        echo "قم بتشغيل الأمر التالي أولاً:"
        echo "/Applications/MAMP/Library/bin/mysql80/bin/mysql -h localhost -P 8889 -u root -proot -e \"CREATE DATABASE erp_operations_new;\""
        echo "/Applications/MAMP/Library/bin/mysql80/bin/mysql -h localhost -P 8889 -u root -proot erp_operations_new < ../database/backUp/erp_operations_back_Up.sql"
        exit 1
    fi

    # فحص قاعدة platinum
    if ! /Applications/MAMP/Library/bin/mysql80/bin/mysql -h localhost -P 8889 -u root -proot -e "USE platinum; SELECT 1;" &>/dev/null; then
        print_error "قاعدة البيانات platinum غير متوفرة"
        echo "قم بتشغيل الأمر التالي أولاً:"
        echo "/Applications/MAMP/Library/bin/mysql80/bin/mysql -h localhost -P 8889 -u root -proot -e \"CREATE DATABASE platinum;\""
        echo "/Applications/MAMP/Library/bin/mysql80/bin/mysql -h localhost -P 8889 -u root -proot platinum < ../database/backUp/platinum_back_up.sql"
        exit 1
    fi

    # فحص قاعدة platinum_migration
    if ! /Applications/MAMP/Library/bin/mysql80/bin/mysql -h localhost -P 8889 -u root -proot -e "USE platinum_migration; SELECT 1;" &>/dev/null; then
        print_warning "قاعدة البيانات platinum_migration غير متوفرة، سيتم إنشاؤها..."
        /Applications/MAMP/Library/bin/mysql80/bin/mysql -h localhost -P 8889 -u root -proot -e "CREATE DATABASE platinum_migration CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        /Applications/MAMP/Library/bin/mysql80/bin/mysql -h localhost -P 8889 -u root -proot platinum_migration < simple_setup.sql
    fi

    print_success "جميع قواعد البيانات متوفرة"
}

# إنشاء نسخة احتياطية
create_backup() {
    print_step "إنشاء نسخة احتياطية من قاعدة platinum..."

    BACKUP_DIR="./backups"
    mkdir -p "$BACKUP_DIR"

    BACKUP_FILE="$BACKUP_DIR/platinum_pre_migration_$(date +%Y_%m_%d_%H_%M_%S).sql"

    /Applications/MAMP/Library/bin/mysql80/bin/mysqldump -h localhost -P 8889 -u root -proot platinum > "$BACKUP_FILE" 2>/dev/null

    if [ $? -eq 0 ]; then
        print_success "تم إنشاء النسخة الاحتياطية: $(basename $BACKUP_FILE)"
    else
        print_error "فشل في إنشاء النسخة الاحتياطية"
        exit 1
    fi
}

# تنفيذ النقل الشامل
execute_migration() {
    print_step "تنفيذ النقل الشامل..."

    # تشغيل سكريبت النقل
    echo "⏳ هذه العملية قد تستغرق وقتاً طويلاً..."
    echo "💡 راقب التقدم في الملف: /tmp/migration_progress.log"
    echo ""

    /Applications/MAMP/Library/bin/mysql80/bin/mysql -h localhost -P 8889 -u root -proot platinum < complete_migration_script.sql > /tmp/migration_progress.log 2>&1

    if [ $? -eq 0 ]; then
        print_success "تم إكمال النقل الشامل"

        # عرض الإحصائيات النهائية
        echo ""
        echo "📊 الإحصائيات النهائية:"
        tail -20 /tmp/migration_progress.log

    else
        print_error "فشل في النقل الشامل"
        echo "راجع السجل: /tmp/migration_progress.log"
        exit 1
    fi
}

# فحص النتائج
verify_migration() {
    print_step "فحص نتائج النقل..."

    # فحص عدد الجداول والسجلات
    echo "🔍 فحص البيانات المنقولة:"

    VERIFICATION_SQL="
    SELECT 'المستخدمون' as table_name, COUNT(*) as record_count FROM platinum.users
    UNION ALL
    SELECT 'السير الذاتية', COUNT(*) FROM platinum.cvs
    UNION ALL
    SELECT 'الطلبات', COUNT(*) FROM platinum.orders
    UNION ALL
    SELECT 'البلدان', COUNT(*) FROM platinum.countries
    UNION ALL
    SELECT 'المدن', COUNT(*) FROM platinum.cities;
    "

    /Applications/MAMP/Library/bin/mysql80/bin/mysql -h localhost -P 8889 -u root -proot -e "$VERIFICATION_SQL" 2>/dev/null

    print_success "تم فحص النتائج"
}

# إنشاء تقرير نهائي
generate_report() {
    print_step "إنشاء تقرير النقل النهائي..."

    REPORT_FILE="migration_report_$(date +%Y_%m_%d_%H_%M_%S).txt"

    cat > "$REPORT_FILE" << EOF
=================================================================
تقرير النقل الشامل - Full Migration Report
=================================================================

تاريخ النقل: $(date '+%Y-%m-%d %H:%M:%S')
من: erp_operations_new
إلى: platinum

=================================================================
الإحصائيات:
=================================================================

EOF

    # إضافة إحصائيات تفصيلية
    /Applications/MAMP/Library/bin/mysql80/bin/mysql -h localhost -P 8889 -u root -proot -e "
        SELECT
            'إحصائيات النقل' as report_section,
            table_name,
            migrated_records,
            status
        FROM platinum_migration.full_migration_log
        ORDER BY id;
    " >> "$REPORT_FILE" 2>/dev/null

    echo "" >> "$REPORT_FILE"
    echo "=================================================================" >> "$REPORT_FILE"
    echo "تم إكمال التقرير في: $(date)" >> "$REPORT_FILE"
    echo "=================================================================" >> "$REPORT_FILE"

    print_success "تم إنشاء التقرير: $REPORT_FILE"
}

# وظيفة المساعدة
show_help() {
    echo "استخدام سكريبت النقل الشامل:"
    echo ""
    echo "الأوامر المتاحة:"
    echo "  $0 check     - فحص قواعد البيانات فقط"
    echo "  $0 backup    - إنشاء نسخة احتياطية فقط"
    echo "  $0 migrate   - تنفيذ النقل الشامل"
    echo "  $0 verify    - فحص نتائج النقل"
    echo "  $0 full      - تنفيذ كامل (backup + migrate + verify)"
    echo "  $0 help      - عرض هذه المساعدة"
    echo ""
    echo "مثال:"
    echo "  $0 full      # للنقل الشامل الكامل"
    echo ""
}

# التحكم في العمليات
case "${1:-full}" in
    "check")
        echo "🔍 فحص قواعد البيانات..."
        check_databases
        ;;
    "backup")
        echo "💾 إنشاء نسخة احتياطية..."
        check_databases
        create_backup
        ;;
    "migrate")
        echo "🔄 تنفيذ النقل..."
        check_databases
        execute_migration
        ;;
    "verify")
        echo "✅ فحص النتائج..."
        verify_migration
        ;;
    "full")
        echo "🚀 النقل الشامل الكامل..."
        echo "==========================================="
        echo ""

        check_databases
        create_backup
        execute_migration
        verify_migration
        generate_report

        echo ""
        echo "🎉 تم إكمال النقل الشامل بنجاح!"
        echo "📄 راجع التقرير النهائي في الملف المنشأ"
        echo ""
        ;;
    "help"|"-h"|"--help")
        show_help
        ;;
    *)
        echo "❌ أمر غير معروف: $1"
        echo ""
        show_help
        exit 1
        ;;
esac
