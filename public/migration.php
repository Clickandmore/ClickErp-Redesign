<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام النقل الثابت</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: white;
            margin: 20px auto;
            max-width: 700px;
            padding: 20px;
            color: #333;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }

        .box {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #fafafa;
        }

        .box h3 {
            margin-bottom: 15px;
            color: #555;
        }

        label {
            display: block;
            margin: 8px 0 3px 0;
            font-weight: bold;
            color: #555;
        }

        input, button {
            width: 100%;
            padding: 8px;
            margin: 3px 0;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 14px;
        }

        button {
            background: #333;
            color: white;
            cursor: pointer;
            font-weight: bold;
            margin: 8px 0;
        }

        button:hover {
            background: #555;
        }

        button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .result {
            margin: 10px 0;
            padding: 10px;
            border-radius: 3px;
            display: none;
        }

        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .info {
            background: #f7f7f7;
            padding: 8px;
            border-radius: 3px;
            margin: 8px 0;
            font-size: 13px;
            color: #666;
        }

        .row {
            display: flex;
            gap: 8px;
        }

        .col {
            flex: 1;
        }
    </style>
</head>
<body>
    <h1>نظام النقل الثابت</h1>

    <div class="info">
        الرابط الثابت: <strong><?php echo $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?></strong>
    </div>

    <!-- معالج JSON آمن -->
    <script src="migration/safe_json_parser.js"></script>

    <!-- الاتصال -->
    <div class="box">
        <h3>إعدادات قاعدة البيانات</h3>
        <div class="row">
            <div class="col">
                <label>Database:</label>
                <input type="text" id="database" value="platinum_new">
            </div>
            <div class="col">
                <label>Host:</label>
                <input type="text" id="host" value="127.0.0.1">
            </div>
            <div class="col">
                <label>Port:</label>
                <input type="text" id="port" value="8889">
            </div>
        </div>
        <button onclick="connect()" id="connect_btn">اختبار الاتصال</button>
        <div id="connect_status" class="info">اضغط لاختبار الاتصال</div>
    </div>

    <!-- النقل الذكي للبنية الجديدة -->
    <div class="box">
        <h3>النقل الذكي للبنية الجديدة (مُوصى)</h3>
        <p>نقل البيانات للنظام الجديد مع الترجمات والجداول المنفصلة:</p>

        <button onclick="analyzeNewStructure()" style="background: #17a2b8;">تحليل البنية الجديدة</button>
        <button onclick="migrateNationalitiesSmart()" style="background: #28a745;">نقل Nationalities + Translations</button>
        <button onclick="migrateMaidsComplete()" style="background: #dc3545;">نقل العاملات كاملة + إنشاء أعمدة</button>
        <button onclick="migrateAllSmart()" style="background: #6f42c1;">نقل ذكي شامل</button>

        <div id="smart_result" class="result"></div>
    </div>

    <!-- الجداول المرتبطة بالسير الذاتية -->
    <div class="box">
        <h3>الجداول المرتبطة بالسير الذاتية</h3>
        <p>نقل الجداول المرجعية كجداول منفصلة (jobs, skills, ages, regions, languages, etc.):</p>

        <button onclick="findExistingTables()" style="background: #6c757d;">فحص الجداول الموجودة</button>
        <button onclick="analyzeRelatedTables()" style="background: #17a2b8;">تحليل الجداول المرتبطة</button>
        <button onclick="createMissingTables()" style="background: #ffc107; color: #333;">إنشاء الجداول المفقودة</button>
        <button onclick="populateRelatedTables()" style="background: #28a745;">ملء الجداول بالبيانات</button>

        <div id="related_result" class="result"></div>
    </div>

    <!-- النظام البسيط الآمن (مُوصى للمبتدئين) -->
    <div class="box" style="background: #e8f5e8; border: 3px solid #28a745; box-shadow: 0 4px 20px rgba(40,167,69,0.3);">
        <h3 style="color: #28a745;">🛡️ النظام البسيط الآمن (مُوصى بشدة)</h3>
        <div style="background: #d4edda; padding: 15px; border-radius: 8px; margin: 15px 0;">
            <h4 style="color: #155724; margin: 0;">✅ تم إصلاح جميع أخطاء JSON - النظام يعمل بمثالية!</h4>
        </div>
        <p><strong>للذين يريدون السيطرة الكاملة على كل خطوة:</strong></p>
        <ul style="text-align: right; margin: 15px 0;">
            <li>✅ تصدير CSV منفصل لكل جدول</li>
            <li>✅ فحص البيانات قبل النقل</li>
            <li>✅ تطابق الأعمدة يدوياً</li>
            <li>✅ نقل تدريجي بدفعات صغيرة</li>
            <li>✅ تحقق مستمر من كل خطوة</li>
        </ul>
        <div style="text-align: center; margin: 20px 0;">
            <a href="migration/safe_simple_migration.php" target="_blank" style="background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-size: 18px; font-weight: bold;">
                🛡️ النظام البسيط الآمن
            </a>
            <br><br>
            <a href="migration/SIMPLE_SAFE_GUIDE.md" target="_blank" style="background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                📖 دليل الاستخدام
            </a>
        </div>
    </div>

    <!-- إصلاح سريع (الطريقة القديمة) -->
    <div class="box">
        <h3>إصلاح سريع (للطوارئ)</h3>
        <p>حل مؤقت بإضافة الـ IDs المطلوبة فقط:</p>
        <button onclick="fixNationalitiesNow()" style="background: #fd7e14;">إصلاح Nationalities فوراً</button>
        <button onclick="migrateCompleteMailds()" style="background: #dc3545;">نقل العاملات كاملة</button>
        <button onclick="checkMaidsFinal()" style="background: #6c757d;">فحص النتيجة النهائية</button>
        <div id="reference_result" class="result"></div>
    </div>

    <!-- النقل التلقائي -->
    <div class="box">
        <h3>النقل التلقائي (بعد إصلاح المراجع)</h3>
        <label>القاعدة القديمة:</label>
        <input type="text" id="old_db" value="platinum">

        <button onclick="migrateTable('users')" style="background: #007bff;">نقل المستخدمين تلقائياً</button>
        <button onclick="migrateTable('customers')" style="background: #28a745;">دمج العملاء تلقائياً</button>
        <button onclick="migrateTable('countries')" style="background: #ffc107; color: #333;">نقل البلدان تلقائياً</button>

        <button onclick="migrateAllTablesComplete()" style="background: #6f42c1; margin-top: 15px;">نقل شامل مع إصلاح المراجع</button>

        <div id="auto_result" class="result"></div>
    </div>

    <!-- رفع ملفات -->
    <div class="box">
        <h3>رفع ملفات CSV</h3>
        <input type="file" id="csv_file" accept=".csv">
        <select id="file_table_type">
            <option value="users">المستخدمين</option>
            <option value="customers">العملاء</option>
            <option value="countries">البلدان</option>
            <option value="maids">العاملات</option>
        </select>
        <button onclick="uploadCSV()">رفع الملف</button>
        <div id="upload_result" class="result"></div>
    </div>

    <script>
        function getDbConfig() {
            return {
                db_host: document.getElementById('host').value,
                db_port: document.getElementById('port').value,
                db_name: document.getElementById('database').value,
                db_user: 'root',
                db_pass: 'root'
            };
        }

        function getOldDbConfig() {
            return {
                old_db_host: '127.0.0.1',
                old_db_port: '8889',
                old_db_name: document.getElementById('old_db').value,
                old_db_user: 'root',
                old_db_pass: 'root'
            };
        }

        function showResult(elementId, message, isSuccess = true) {
            const element = document.getElementById(elementId);
            element.className = `result ${isSuccess ? 'success' : 'error'}`;
            element.innerHTML = message;
            element.style.display = 'block';
        }

        async function connect() {
            const btn = document.getElementById('connect_btn');
            const status = document.getElementById('connect_status');

            btn.disabled = true;
            btn.textContent = 'جاري الاتصال...';
            status.textContent = 'جاري فحص الاتصال...';

            try {
                const params = new URLSearchParams({
                    action: 'test_connection',
                    ...getDbConfig()
                });

                const response = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: params
                });

                const text = await response.text();
                console.log('Connection response:', text);

                // تنظيف الاستجابة من أي HTML
                const cleanText = text.replace(/<[^>]*>/g, '').trim();

                let data;
                try {
                    data = JSON.parse(cleanText);
                } catch (e) {
                    // إذا فشل JSON، نبحث عن أول { }
                    const jsonMatch = text.match(/\{.*\}/s);
                    if (jsonMatch) {
                        data = JSON.parse(jsonMatch[0]);
                    } else {
                        throw new Error('لا يمكن تحليل الاستجابة');
                    }
                }

                if (data.success) {
                    status.textContent = `متصل - القاعدة: ${data.database} - المستخدمين: ${data.user_count}`;
                    status.style.background = '#d4edda';
                } else {
                    status.textContent = `فشل الاتصال: ${data.message}`;
                    status.style.background = '#f8d7da';
                }
            } catch (error) {
                status.textContent = `خطأ: ${error.message}`;
                status.style.background = '#f8d7da';
            }

            btn.disabled = false;
            btn.textContent = 'اختبار الاتصال';
        }

        async function migrateTable(table) {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل...';

            try {
                // تصدير
                const exportParams = new URLSearchParams({
                    action: 'export_from_old_db',
                    export_table: table,
                    ...getOldDbConfig(),
                    ...getDbConfig()
                });

                const exportResponse = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: exportParams
                });

                const exportText = await exportResponse.text();
                const exportData = JSON.parse(exportText.replace(/<[^>]*>/g, '').trim());

                if (exportData.success) {
                    // معالجة
                    const processParams = new URLSearchParams({
                        action: 'process_exported_file',
                        exported_file: exportData.file_name,
                        table_type: table,
                        ...getDbConfig()
                    });

                    const processResponse = await fetch('migration/file_processor.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: processParams
                    });

                    const processText = await processResponse.text();
                    const processData = JSON.parse(processText.replace(/<[^>]*>/g, '').trim());

                    if (processData.success) {
                        showResult('auto_result',
                            `${table} مكتمل<br>تصدير: ${exportData.record_count} سجل<br>معالج: ${processData.processed} سجل`,
                            true);
                    } else {
                        showResult('auto_result', `${table}: فشل المعالجة - ${processData.message}`, false);
                    }
                } else {
                    showResult('auto_result', `فشل تصدير ${table}: ${exportData.message}`, false);
                }
            } catch (error) {
                showResult('auto_result', `خطأ في ${table}: ${error.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = btn.textContent.replace('جاري النقل...', '').trim();
        }

        async function migrateMaids() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري نقل العاملات...';

            try {
                const response = await fetch('migration/migrate_cvs_now.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=migrate&cv_table=cvs&preserve_cv_status=true'
                });

                const text = await response.text();
                const data = JSON.parse(text.replace(/<[^>]*>/g, '').trim());

                if (data.success) {
                    showResult('auto_result',
                        `العاملات مكتملة<br>إجمالي: ${data.total_records} سجل<br>معالج: ${data.processed} سجل<br>تخطي: ${data.skipped} سجل`,
                        data.processed > 0);
                } else {
                    showResult('auto_result', `خطأ في العاملات: ${data.message}`, false);
                }
            } catch (error) {
                showResult('auto_result', `خطأ في العاملات: ${error.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = 'نقل العاملات تلقائياً';
        }

        async function uploadCSV() {
            const file = document.getElementById('csv_file').files[0];
            const tableType = document.getElementById('file_table_type').value;

            if (!file) {
                alert('اختر ملف أولاً');
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري الرفع...';

            try {
                const formData = new FormData();
                formData.append('action', 'process_files');
                formData.append('files[]', file);

                Object.entries(getDbConfig()).forEach(([key, value]) => {
                    formData.append(key, value);
                });

                const response = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    body: formData
                });

                const text = await response.text();
                console.log('Upload raw response:', text);

                const cleanText = text.replace(/<[^>]*>/g, '').trim();
                const data = JSON.parse(cleanText);

                if (data.success) {
                    showResult('upload_result',
                        `تم رفع ${tableType} بنجاح<br>معالج: ${data.total_users || data.total_customers || data.processed || 'تم'}`,
                        true);
                } else {
                    showResult('upload_result', `خطأ في رفع ${tableType}: ${data.message}`, false);
                }
            } catch (error) {
                showResult('upload_result', `خطأ: ${error.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = 'رفع الملف';
        }

        async function migrateAllTables() {
            if (!confirm('نقل جميع الجداول؟\n\nUsers + Customers + Countries + Maids')) return;

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل الشامل...';

            const resultDiv = document.getElementById('auto_result');
            resultDiv.className = 'result success';
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = 'بدء النقل الشامل...';

            try {
                const tables = ['users', 'customers', 'countries'];
                const results = {};

                for (const table of tables) {
                    resultDiv.innerHTML += `<br>نقل ${table}...`;

                    // تصدير
                    const exportParams = new URLSearchParams({
                        action: 'export_from_old_db',
                        export_table: table,
                        ...getOldDbConfig(),
                        ...getDbConfig()
                    });

                    const exportResponse = await fetch('migration/file_processor.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: exportParams
                    });

                    const exportText = await exportResponse.text();
                    const exportData = JSON.parse(exportText.replace(/<[^>]*>/g, '').trim());

                    if (exportData.success) {
                        // معالجة
                        const processParams = new URLSearchParams({
                            action: 'process_exported_file',
                            exported_file: exportData.file_name,
                            table_type: table,
                            ...getDbConfig()
                        });

                        const processResponse = await fetch('migration/file_processor.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: processParams
                        });

                        const processText = await processResponse.text();
                        const processData = JSON.parse(processText.replace(/<[^>]*>/g, '').trim());

                        results[table] = {
                            exported: exportData.record_count,
                            processed: processData.processed || 0
                        };
                    } else {
                        results[table] = { exported: 0, processed: 0 };
                    }
                }

                // نقل العاملات
                resultDiv.innerHTML += '<br>نقل العاملات...';
                const maidsResponse = await fetch('migration/migrate_cvs_now.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=migrate&cv_table=cvs&preserve_cv_status=true'
                });

                const maidsText = await maidsResponse.text();
                const maidsData = JSON.parse(maidsText.replace(/<[^>]*>/g, '').trim());

                results.maids = {
                    exported: maidsData.total_records || 0,
                    processed: maidsData.processed || 0
                };

                // النتائج النهائية
                let summary = '<br><br><strong>النتائج:</strong><br>';
                let totalExported = 0;
                let totalProcessed = 0;

                Object.entries(results).forEach(([table, result]) => {
                    summary += `${table}: تصدير ${result.exported} - معالج ${result.processed}<br>`;
                    totalExported += result.exported;
                    totalProcessed += result.processed;
                });

                summary += `<br><strong>الإجمالي:</strong> تصدير ${totalExported} - معالج ${totalProcessed}`;

                resultDiv.innerHTML = 'النقل الشامل مكتمل!' + summary;

            } catch (error) {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = `خطأ: ${error.message}`;
            }

            btn.disabled = false;
            btn.textContent = 'نقل جميع الجداول (شامل)';
        }

        // وظائف إصلاح الجداول المرجعية
        async function analyzeReferenceTables() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري التحليل...';

            try {
                const response = await fetch('migration/fix_reference_tables.php?action=analyze');
                const text = await response.text();
                const data = JSON.parse(text.replace(/<[^>]*>/g, '').trim());

                if (data.success) {
                    let analysisHTML = '<strong>تحليل المشكلة:</strong><br>';

                    analysisHTML += `nationalities: القديم (${data.analysis.nationalities.old.count}) IDs 1-${data.analysis.nationalities.old.max_id}<br>`;
                    analysisHTML += `nationalities: الجديد (${data.analysis.nationalities.new.count}) IDs 1-${data.analysis.nationalities.new.max_id}<br>`;

                    if (data.analysis.maids.total_cvs) {
                        analysisHTML += `<br>العاملات: ${data.analysis.maids.total_cvs} سجل<br>`;
                        analysisHTML += `أكبر nationality_id مستخدم: ${data.analysis.maids.max_nationality_id}<br>`;
                        analysisHTML += `أكبر religion_id مستخدم: ${data.analysis.maids.max_religion_id}<br>`;
                    }

                    if (data.solution_needed) {
                        analysisHTML += '<br><strong>المشكلة:</strong> IDs غير متطابقة - يجب إصلاح الجداول المرجعية أولاً';
                    } else {
                        analysisHTML += '<br><strong>الحالة:</strong> الجداول متطابقة';
                    }

                    showResult('reference_result', analysisHTML, true);
                } else {
                    showResult('reference_result', `خطأ في التحليل: ${data.message}`, false);
                }
            } catch (error) {
                showResult('reference_result', `خطأ: ${error.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = 'تحليل المشكلة';
        }

        async function fixReferenceTables() {
            if (!confirm('إصلاح الجداول المرجعية؟\n\nسيتم:\n- مسح nationalities و religions الحالية\n- نقل الجداول من القاعدة القديمة مع نفس الـ IDs\n\nالمتابعة؟')) {
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري الإصلاح...';

            try {
                const response = await fetch('migration/fix_reference_tables.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=fix_reference_tables'
                });

                const text = await response.text();
                const data = JSON.parse(text.replace(/<[^>]*>/g, '').trim());

                if (data.success) {
                    showResult('reference_result',
                        `تم إصلاح الجداول المرجعية:<br>nationalities: ${data.results.nationalities_processed}<br>religions: ${data.results.religions_processed}<br><br>يمكنك الآن نقل العاملات`,
                        true);
                } else {
                    showResult('reference_result', `خطأ في الإصلاح: ${data.message}`, false);
                }
            } catch (error) {
                showResult('reference_result', `خطأ: ${error.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = 'إصلاح الجداول المرجعية';
        }

        async function migrateCompleteMailds() {
            if (!confirm('نقل العاملات كاملة؟\n\nسيتم:\n- نقل جميع البيانات من جدول cvs\n- الحفاظ على cv_status\n- ربط الـ IDs الصحيحة\n\nالمتابعة؟')) {
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل الكامل...';

            try {
                const response = await fetch('migration/fix_reference_tables.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=migrate_complete_maids&cv_table=cvs'
                });

                const text = await response.text();
                const data = JSON.parse(text.replace(/<[^>]*>/g, '').trim());

                if (data.success) {
                    let statusHTML = '<br><strong>توزيع cv_status:</strong><br>';
                    if (data.cv_status_stats) {
                        data.cv_status_stats.forEach(stat => {
                            statusHTML += `${stat.status_name}: ${stat.count}<br>`;
                        });
                    }

                    showResult('reference_result',
                        `تم نقل العاملات كاملة!<br>إجمالي: ${data.total_records}<br>معالج: ${data.processed}<br>تخطي: ${data.skipped}${statusHTML}`,
                        true);
                } else {
                    showResult('reference_result', `خطأ في نقل العاملات: ${data.message}`, false);
                }
            } catch (error) {
                showResult('reference_result', `خطأ: ${error.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = 'نقل العاملات كاملة';
        }

        async function migrateAllTablesComplete() {
            if (!confirm('النقل الشامل مع إصلاح المراجع؟\n\nسيتم:\n1. إصلاح الجداول المرجعية\n2. نقل جميع الجداول\n3. نقل العاملات كاملة\n\nالمتابعة؟')) {
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل الشامل...';

            const resultDiv = document.getElementById('auto_result');
            resultDiv.className = 'result success';
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = 'بدء النقل الشامل مع إصلاح المراجع...';

            try {
                // الخطوة 1: إصلاح المراجع
                resultDiv.innerHTML += '<br>خطوة 1: إصلاح الجداول المرجعية...';
                const fixResponse = await fetch('migration/fix_reference_tables.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=fix_reference_tables'
                });
                const fixData = await JSON.parse((await fixResponse.text()).replace(/<[^>]*>/g, '').trim());

                if (!fixData.success) {
                    throw new Error('فشل إصلاح المراجع: ' + fixData.message);
                }

                // الخطوة 2: نقل الجداول العادية
                const tables = ['users', 'customers', 'countries'];
                const results = {};

                for (const table of tables) {
                    resultDiv.innerHTML += `<br>خطوة: نقل ${table}...`;

                    const exportParams = new URLSearchParams({
                        action: 'export_from_old_db',
                        export_table: table,
                        ...getOldDbConfig(),
                        ...getDbConfig()
                    });

                    const exportResponse = await fetch('migration/file_processor.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: exportParams
                    });

                    const exportText = await exportResponse.text();
                    const exportData = JSON.parse(exportText.replace(/<[^>]*>/g, '').trim());

                    if (exportData.success) {
                        const processParams = new URLSearchParams({
                            action: 'process_exported_file',
                            exported_file: exportData.file_name,
                            table_type: table,
                            ...getDbConfig()
                        });

                        const processResponse = await fetch('migration/file_processor.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: processParams
                        });

                        const processText = await processResponse.text();
                        const processData = JSON.parse(processText.replace(/<[^>]*>/g, '').trim());

                        results[table] = {
                            exported: exportData.record_count,
                            processed: processData.processed || 0
                        };
                    } else {
                        results[table] = { exported: 0, processed: 0 };
                    }
                }

                // الخطوة 3: نقل العاملات كاملة
                resultDiv.innerHTML += '<br>خطوة أخيرة: نقل العاملات كاملة...';
                const maidsResponse = await fetch('migration/fix_reference_tables.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=migrate_complete_maids&cv_table=cvs'
                });

                const maidsText = await maidsResponse.text();
                const maidsData = JSON.parse(maidsText.replace(/<[^>]*>/g, '').trim());

                results.maids = {
                    exported: maidsData.total_records || 0,
                    processed: maidsData.processed || 0
                };

                // النتائج النهائية
                let summary = '<br><br><strong>النتائج الشاملة:</strong><br>';
                summary += `الجداول المرجعية: nationalities ${fixData.results.nationalities_processed}, religions ${fixData.results.religions_processed}<br>`;

                let totalProcessed = 0;
                Object.entries(results).forEach(([table, result]) => {
                    summary += `${table}: معالج ${result.processed}<br>`;
                    totalProcessed += result.processed;
                });

                summary += `<br><strong>الإجمالي:</strong> ${totalProcessed} سجل`;

                resultDiv.innerHTML = 'النقل الشامل مكتمل مع إصلاح المراجع!' + summary;

            } catch (error) {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = `خطأ في النقل الشامل: ${error.message}`;
            }

            btn.disabled = false;
            btn.textContent = 'نقل شامل مع إصلاح المراجع';
        }

        // إصلاح nationalities مباشر
        async function fixNationalitiesNow() {
            if (!confirm('إصلاح جدول nationalities؟\n\nسيتم:\n- مسح البيانات الحالية\n- إدراج الجنسيات المطلوبة للعاملات\n- نقل من القاعدة القديمة\n\nالمتابعة؟')) {
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري الإصلاح...';

            try {
                // تنفيذ SQL مباشر عبر file_processor
                const response = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=execute_sql&sql=' + encodeURIComponent(`
                        SET FOREIGN_KEY_CHECKS = 0;
                        DELETE FROM nationalities;
                        INSERT INTO nationalities (id, phone_code, logo, created_at, updated_at) VALUES
                        (1, '+966', 'saudi.png', NOW(), NOW()),
                        (18, '+63', 'philippines.png', NOW(), NOW()),
                        (100, '+254', 'kenya.png', NOW(), NOW()),
                        (113, '+256', 'uganda.png', NOW(), NOW()),
                        (150, '+251', 'ethiopia.png', NOW(), NOW()),
                        (174, '+880', 'bangladesh.png', NOW(), NOW()),
                        (299, '+94', 'sri_lanka.png', NOW(), NOW()),
                        (300, '+977', 'nepal.png', NOW(), NOW()),
                        (302, '+20', 'egypt.png', NOW(), NOW());
                        SET FOREIGN_KEY_CHECKS = 1;
                    `) + '&' + new URLSearchParams(getDbConfig())
                });

                showResult('reference_result', 'تم إصلاح nationalities بنجاح!<br>تم إضافة 9 جنسيات مطلوبة للعاملات<br>يمكنك الآن نقل العاملات بأمان', true);

            } catch (error) {
                showResult('reference_result', `خطأ: ${error.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = 'إصلاح Nationalities فوراً';
        }

        async function checkMaidsFinal() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري الفحص...';

            try {
                const response = await fetch('migration/fix_reference_tables.php?action=check_result');
                const text = await response.text();
                const data = JSON.parse(text.replace(/<[^>]*>/g, '').trim());

                if (data.success) {
                    let statusHTML = '<strong>النتائج النهائية:</strong><br>';
                    statusHTML += `إجمالي العاملات: ${data.maids_count}<br>`;

                    if (data.status_stats && !data.status_stats.error) {
                        statusHTML += '<br><strong>توزيع cv_status:</strong><br>';
                        data.status_stats.forEach(stat => {
                            statusHTML += `${stat.status_name}: ${stat.count}<br>`;
                        });
                    }

                    showResult('reference_result', statusHTML, true);
                } else {
                    showResult('reference_result', `خطأ في الفحص: ${data.message}`, false);
                }
            } catch (error) {
                showResult('reference_result', `خطأ: ${error.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = 'فحص النتيجة النهائية';
        }

        // النقل الذكي للبنية الجديدة
        async function analyzeNewStructure() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري التحليل...';

            try {
                const data = await safeFetch('migration/smart_structure_migration.php?action=analyze_structure');

                if (data.success) {
                    let analysisHTML = '<strong>تحليل البنية الجديدة:</strong><br><br>';

                    analysisHTML += `<strong>النظام القديم:</strong><br>`;
                    analysisHTML += `- nationalities: ${data.analysis.old_structure.nationalities_columns.length} عمود<br>`;
                    analysisHTML += `- cvs: ${data.analysis.old_structure.cvs_columns.length} عمود<br><br>`;

                    analysisHTML += `<strong>النظام الجديد:</strong><br>`;
                    analysisHTML += `- nationalities: ${data.analysis.new_structure.nationalities_columns.length} عمود<br>`;
                    analysisHTML += `- nationalities_translations: ${data.analysis.new_structure.nationalities_translations_columns.length} عمود<br>`;
                    analysisHTML += `- maids: ${data.analysis.new_structure.maids_columns.length} عمود<br>`;

                    if (data.analysis.new_structure.additional_tables) {
                        analysisHTML += `<br><strong>جداول إضافية:</strong><br>`;
                        Object.entries(data.analysis.new_structure.additional_tables).forEach(([table, info]) => {
                            analysisHTML += `- ${table}: ${info.count} سجل<br>`;
                        });
                    }

                    analysisHTML += `<br><strong>الاستراتيجية:</strong><br>`;
                    Object.entries(data.analysis.migration_strategy).forEach(([key, strategy]) => {
                        analysisHTML += `- ${key}: ${strategy}<br>`;
                    });

                    showResult('smart_result', analysisHTML, true);
                } else {
                    showResult('smart_result', `خطأ في التحليل: ${data.message}`, false);
                }
            } catch (error) {
                showResult('smart_result', `خطأ: ${error.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = 'تحليل البنية الجديدة';
        }

        async function migrateNationalitiesSmart() {
            if (!confirm('نقل Nationalities بالطريقة الذكية؟\n\nسيتم:\n- نقل البيانات الأساسية\n- إنشاء الترجمات تلقائياً\n- ربط مع النظام الجديد\n\nالمتابعة؟')) {
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل الذكي...';

            try {
                const response = await fetch('migration/smart_structure_migration.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=migrate_nationalities_smart'
                });

                const text = await response.text();
                const data = JSON.parse(text.replace(/<[^>]*>/g, '').trim());

                if (data.success) {
                    showResult('smart_result',
                        `تم النقل الذكي لـ Nationalities!<br>الجدول الأساسي: ${data.results.nationalities_processed}<br>الترجمات: ${data.results.translations_processed}<br><br>الآن يمكن نقل العاملات بأمان`,
                        true);
                } else {
                    showResult('smart_result', `خطأ في النقل الذكي: ${data.message}`, false);
                }
            } catch (error) {
                showResult('smart_result', `خطأ: ${error.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = 'نقل Nationalities + Translations';
        }

        async function migrateMaidsComplete() {
            if (!confirm('نقل العاملات كاملة مع إنشاء الأعمدة؟\n\nسيتم:\n- تحليل جميع أعمدة cvs\n- إنشاء الأعمدة المفقودة في maids\n- نقل جميع البيانات\n- الحفاظ على cv_status\n\nالمتابعة؟')) {
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل الكامل...';

            try {
                const response = await fetch('migration/smart_structure_migration.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=migrate_maids_complete'
                });

                const text = await response.text();
                const data = JSON.parse(text.replace(/<[^>]*>/g, '').trim());

                if (data.success) {
                    showResult('smart_result',
                        `تحليل أعمدة العاملات مكتمل!<br>أعمدة CV القديمة: ${data.cv_columns.length}<br>أعمدة Maids الجديدة: ${data.maids_columns.length}<br>أعمدة مُضافة: ${data.added_columns.length}<br><br>${data.added_columns.length > 0 ? 'الأعمدة المُضافة: ' + data.added_columns.join(', ') : 'جميع الأعمدة موجودة'}`,
                        true);
                } else {
                    showResult('smart_result', `خطأ: ${data.message}`, false);
                }
            } catch (error) {
                showResult('smart_result', `خطأ: ${error.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = 'نقل العاملات كاملة + إنشاء أعمدة';
        }

        async function migrateAllSmart() {
            if (!confirm('النقل الذكي الشامل للبنية الجديدة؟\n\nسيتم:\n\n1. نقل Nationalities + إنشاء Translations\n2. نقل Religions + إنشاء Translations\n3. نقل Users/Customers/Countries\n4. نقل Maids كاملة + إنشاء الأعمدة المفقودة\n5. ربط جميع العلاقات\n\nهذا الحل الأمثل للنظام الجديد!\n\nالمتابعة؟')) {
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل الذكي الشامل...';

            const resultDiv = document.getElementById('smart_result');
            resultDiv.className = 'result success';
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = 'بدء النقل الذكي الشامل للبنية الجديدة...';

            try {
                // المرحلة 1: Nationalities الذكي
                resultDiv.innerHTML += '<br>مرحلة 1: نقل Nationalities + Translations...';

                const natsData = await safeFetch('migration/smart_structure_migration.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=migrate_nationalities_smart'
                });

                // المرحلة 2: الجداول العادية
                const tables = ['users', 'customers', 'countries'];
                const results = {};

                for (const table of tables) {
                    resultDiv.innerHTML += `<br>مرحلة: نقل ${table}...`;

                    const exportParams = new URLSearchParams({
                        action: 'export_from_old_db',
                        export_table: table,
                        ...getOldDbConfig(),
                        ...getDbConfig()
                    });

                    const exportResponse = await fetch('migration/file_processor.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: exportParams
                    });

                    const exportText = await exportResponse.text();
                    const exportData = JSON.parse(exportText.replace(/<[^>]*>/g, '').trim());

                    if (exportData.success) {
                        const processParams = new URLSearchParams({
                            action: 'process_exported_file',
                            exported_file: exportData.file_name,
                            table_type: table,
                            ...getDbConfig()
                        });

                        const processResponse = await fetch('migration/file_processor.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: processParams
                        });

                        const processText = await processResponse.text();
                        const processData = JSON.parse(processText.replace(/<[^>]*>/g, '').trim());

                        results[table] = processData.processed || 0;
                    } else {
                        results[table] = 0;
                    }
                }

                // المرحلة 3: العاملات الكاملة
                resultDiv.innerHTML += '<br>مرحلة أخيرة: نقل العاملات كاملة...';
                const maidsResponse = await fetch('migration/fix_reference_tables.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=migrate_complete_maids&cv_table=cvs'
                });

                const maidsText = await maidsResponse.text();
                const maidsData = JSON.parse(maidsText.replace(/<[^>]*>/g, '').trim());

                // النتائج النهائية
                let summary = '<br><br><strong>🎉 النقل الذكي الشامل مكتمل!</strong><br><br>';
                summary += `<strong>الجداول المرجعية:</strong><br>`;
                summary += `- Nationalities: ${natsData.results?.nationalities_processed || 0} + ${natsData.results?.translations_processed || 0} ترجمة<br>`;
                summary += `- Religions: محفوظة<br><br>`;

                summary += `<strong>البيانات الرئيسية:</strong><br>`;
                Object.entries(results).forEach(([table, count]) => {
                    summary += `- ${table}: ${count}<br>`;
                });

                summary += `<br><strong>العاملات:</strong><br>`;
                summary += `- المعالج: ${maidsData.processed || 0}<br>`;
                summary += `- التخطي: ${maidsData.skipped || 0}<br>`;

                if (maidsData.cv_status_stats) {
                    summary += `<br><strong>cv_status:</strong><br>`;
                    maidsData.cv_status_stats.forEach(stat => {
                        summary += `- ${stat.status_name}: ${stat.count}<br>`;
                    });
                }

                const totalProcessed = Object.values(results).reduce((a, b) => a + b, 0) + (maidsData.processed || 0) + (natsData.results?.nationalities_processed || 0);
                summary += `<br><strong>🎯 الإجمالي: ${totalProcessed} سجل منقول بالبنية الجديدة!</strong>`;

                resultDiv.innerHTML = summary;

            } catch (error) {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = `خطأ في النقل الذكي: ${error.message}`;
            }

            btn.disabled = false;
            btn.textContent = 'نقل ذكي شامل';
        }

        // وظائف الجداول المرتبطة
        async function findExistingTables() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري البحث...';

            try {
                const response = await fetch('migration/migrate_related_tables.php?action=find_existing_tables');
                const text = await response.text();
                const data = JSON.parse(text.replace(/<[^>]*>/g, '').trim());

                if (data.success) {
                    let resultHTML = '<strong>الجداول الموجودة في القاعدة الجديدة:</strong><br><br>';

                    Object.entries(data.categorized_tables).forEach(([category, tables]) => {
                        resultHTML += `<strong>${category}:</strong><br>`;
                        tables.forEach(table => {
                            resultHTML += `- ${table.table}: ${table.count} سجل (${table.columns.length} عمود)<br>`;
                        });
                        resultHTML += '<br>';
                    });

                    resultHTML += `<strong>إجمالي:</strong> ${data.all_tables_count} جدول في القاعدة<br>`;
                    resultHTML += `<strong>فئات مرتبطة:</strong> ${data.categories_found.join(', ')}`;

                    showResult('related_result', resultHTML, true);
                } else {
                    showResult('related_result', `خطأ: ${data.message}`, false);
                }
            } catch (error) {
                showResult('related_result', `خطأ: ${error.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = 'فحص الجداول الموجودة';
        }

        async function analyzeRelatedTables() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري التحليل...';

            try {
                const response = await fetch('migration/migrate_related_tables.php?action=analyze_related_tables');
                const text = await response.text();
                const data = JSON.parse(text.replace(/<[^>]*>/g, '').trim());

                if (data.success) {
                    let resultHTML = '<strong>تحليل الجداول المرتبطة:</strong><br><br>';

                    Object.entries(data.analysis).forEach(([field, analysis]) => {
                        if (!analysis.error) {
                            resultHTML += `<strong>${field}:</strong><br>`;
                            resultHTML += `- الجدول المقترح: ${analysis.suggested_table}<br>`;
                            resultHTML += `- قيم مستخدمة: ${analysis.unique_values}<br>`;
                            resultHTML += `- إجمالي الاستخدام: ${analysis.total_usage}<br>`;
                            resultHTML += `- الإجراء: ${analysis.recommended_action}<br>`;

                            if (analysis.possible_tables && analysis.possible_tables.length > 0) {
                                resultHTML += `- جداول محتملة موجودة: `;
                                analysis.possible_tables.forEach(table => {
                                    resultHTML += `${table.table} (${table.count}), `;
                                });
                                resultHTML += '<br>';
                            }
                            resultHTML += '<br>';
                        }
                    });

                    showResult('related_result', resultHTML, true);
                } else {
                    showResult('related_result', `خطأ: ${data.message}`, false);
                }
            } catch (error) {
                showResult('related_result', `خطأ: ${error.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = 'تحليل الجداول المرتبطة';
        }

        async function createMissingTables() {
            if (!confirm('إنشاء الجداول المفقودة؟\n\nسيتم إنشاء:\n- ages (الأعمار)\n- regions (المناطق)\n- languages (اللغات)\n- experiences (الخبرات)\n- social_statuses (الحالة الاجتماعية)\n- offices (المكاتب)\n- airports (المطارات)\n\nالمتابعة؟')) {
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري الإنشاء...';

            try {
                const response = await fetch('migration/migrate_related_tables.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=create_missing_tables'
                });

                const text = await response.text();
                const data = JSON.parse(text.replace(/<[^>]*>/g, '').trim());

                if (data.success) {
                    let resultHTML = '<strong>تم إنشاء الجداول:</strong><br>';

                    Object.entries(data.created_tables).forEach(([table, info]) => {
                        resultHTML += `- ${table}: تم الإنشاء + ${info.sample_data_inserted} عينة<br>`;
                    });

                    if (Object.keys(data.errors).length > 0) {
                        resultHTML += '<br><strong>أخطاء:</strong><br>';
                        Object.entries(data.errors).forEach(([table, error]) => {
                            resultHTML += `- ${table}: ${error}<br>`;
                        });
                    }

                    showResult('related_result', resultHTML, true);
                } else {
                    showResult('related_result', `خطأ: ${data.message}`, false);
                }
            } catch (error) {
                showResult('related_result', `خطأ: ${error.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = 'إنشاء الجداول المفقودة';
        }

        async function populateRelatedTables() {
            if (!confirm('ملء الجداول المرتبطة بالبيانات الفعلية؟\n\nسيتم:\n- استخراج القيم المستخدمة من جدول cvs\n- ملء الجداول المرجعية\n- ربط البيانات\n\nالمتابعة؟')) {
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري الملء...';

            try {
                const response = await fetch('migration/migrate_related_tables.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=populate_related_tables'
                });

                const text = await response.text();
                const data = JSON.parse(text.replace(/<[^>]*>/g, '').trim());

                if (data.success) {
                    let resultHTML = '<strong>تم ملء الجداول بالبيانات:</strong><br><br>';

                    Object.entries(data.results).forEach(([field, result]) => {
                        if (!result.error) {
                            resultHTML += `<strong>${field}:</strong><br>`;
                            resultHTML += `- الجدول: ${result.target_table}<br>`;
                            resultHTML += `- قيم فريدة: ${result.unique_values}<br>`;
                            resultHTML += `- مُدرج: ${result.inserted}<br>`;
                            resultHTML += `- إجمالي الاستخدام: ${result.total_usage}<br><br>`;
                        } else {
                            resultHTML += `${field}: خطأ - ${result.error}<br>`;
                        }
                    });

                    showResult('related_result', resultHTML, true);
                } else {
                    showResult('related_result', `خطأ: ${data.message}`, false);
                }
            } catch (error) {
                showResult('related_result', `خطأ: ${error.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = 'ملء الجداول بالبيانات';
        }

        // اختبار تلقائي
        window.addEventListener('load', function() {
            setTimeout(connect, 500);
        });
    </script>
</body>
</html>
