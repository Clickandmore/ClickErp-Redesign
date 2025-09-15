#!/bin/bash

# =============================================================================
# سكريبت تشغيل خادم نظام نقل البيانات
# Migration System Server Starter
# =============================================================================

echo "🚀 بدء تشغيل نظام نقل البيانات..."

# إنهاء أي خادم قديم
pkill -f "php -S 127.0.0.1:8002" 2>/dev/null

# الانتقال لمجلد النظام
cd /Applications/MAMP/htdocs/ERP_V/migration_system

# تشغيل الخادم
nohup php -S 127.0.0.1:8002 > /tmp/migration_server.log 2>&1 &

# انتظار قصير للتأكد من بدء التشغيل
sleep 2

# اختبار الخادم
if curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8002/ | grep -q "200"; then
    echo "✅ تم تشغيل الخادم بنجاح!"
    echo ""
    echo "🌐 افتح المتصفح واذهب إلى:"
    echo "   http://127.0.0.1:8002/"
    echo ""
    echo "📋 للتوقف، استخدم:"
    echo "   pkill -f \"php -S 127.0.0.1:8002\""
    echo ""
else
    echo "❌ فشل في تشغيل الخادم"
    echo "   تحقق من ملف السجل: /tmp/migration_server.log"
fi
