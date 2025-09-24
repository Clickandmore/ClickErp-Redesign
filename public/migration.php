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

    <!-- النقل التلقائي -->
    <div class="box">
        <h3>النقل التلقائي (مُوصى)</h3>
        <label>القاعدة القديمة:</label>
        <input type="text" id="old_db" value="platinum">

        <button onclick="migrateTable('users')" style="background: #007bff;">نقل المستخدمين تلقائياً</button>
        <button onclick="migrateTable('customers')" style="background: #28a745;">دمج العملاء تلقائياً</button>
        <button onclick="migrateTable('countries')" style="background: #ffc107; color: #333;">نقل البلدان تلقائياً</button>
        <button onclick="migrateMaids()" style="background: #dc3545;">نقل العاملات تلقائياً</button>

        <button onclick="migrateAllTables()" style="background: #6f42c1; margin-top: 15px;">نقل جميع الجداول (شامل)</button>

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

        // اختبار تلقائي
        window.addEventListener('load', function() {
            setTimeout(connect, 500);
        });
    </script>
</body>
</html>
