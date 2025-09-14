#!/bin/bash

# =============================================================================
# تشغيل خادم نظام النقل مع إعدادات محسنة
# Enhanced Migration System Server Starter
# =============================================================================

echo "🚀 بدء تشغيل نظام نقل البيانات..."

# إنهاء أي خادم قديم
pkill -f "php -S 127.0.0.1:8002" 2>/dev/null || true

# الانتقال لمجلد النظام
cd /Applications/MAMP/htdocs/ERP_V/migration_system

# إنشاء المجلدات المطلوبة
mkdir -p uploads/$(date +%Y/%m/%d)
mkdir -p logs

# ضبط الصلاحيات
chmod -R 777 uploads/
chmod -R 777 logs/

# تشغيل الخادم مع إعدادات محسنة
nohup php -S 127.0.0.1:8002 \
    -d upload_max_filesize=50M \
    -d post_max_size=50M \
    -d memory_limit=256M \
    -d max_execution_time=300 \
    -d max_input_time=300 \
    > /tmp/migration_server.log 2>&1 &

# انتظار للتأكد من بدء التشغيل
sleep 3

# اختبار الخادم
if curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8002/ | grep -q "200"; then
    echo "✅ تم تشغيل الخادم بنجاح!"
    echo ""
    echo "🌐 الروابط المتاحة:"
    echo "   📊 لوحة التحكم: http://127.0.0.1:8002/"
    echo "   📤 رفع الملفات: http://127.0.0.1:8002/?page=upload"
    echo "   🧪 اختبار الرفع: http://127.0.0.1:8002/test_upload.php"
    echo ""
    echo "📋 إعدادات الخادم:"
    echo "   - حد رفع الملفات: 50MB"
    echo "   - حد الذاكرة: 256MB"
    echo "   - مهلة التنفيذ: 300 ثانية"
    echo ""
    echo "🔧 للإيقاف، استخدم:"
    echo "   pkill -f \"php -S 127.0.0.1:8002\""
    echo ""
    echo "📄 مراقبة السجل:"
    echo "   tail -f /tmp/migration_server.log"
    echo ""
else
    echo "❌ فشل في تشغيل الخادم"
    echo "   تحقق من ملف السجل: /tmp/migration_server.log"
    echo ""
    echo "📋 السجل الأخير:"
    tail -10 /tmp/migration_server.log 2>/dev/null || echo "   لا توجد سجلات"
fi
