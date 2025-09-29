<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام النقل البسيط</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            font-size: 24px;
        }

        .section {
            margin: 25px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: #fafafa;
        }

        .section h3 {
            margin-bottom: 15px;
            color: #555;
            font-size: 18px;
        }

        label {
            display: block;
            margin: 10px 0 5px 0;
            font-weight: bold;
            color: #555;
        }

        input, select, button {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }

        button {
            background: #666;
            color: white;
            cursor: pointer;
            font-weight: bold;
            margin: 10px 0;
        }

        button:hover {
            background: #555;
        }

        button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .result {
            margin: 15px 0;
            padding: 15px;
            border-radius: 4px;
            display: none;
        }

        .result.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .result.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .row {
            display: flex;
            gap: 10px;
        }

        .col {
            flex: 1;
        }

        .progress {
            background: #f0f0f0;
            height: 20px;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }

        .progress-bar {
            background: #666;
            height: 100%;
            width: 0%;
            transition: width 0.3s;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>نظام النقل البسيط</h1>

        <!-- إعدادات قاعدة البيانات -->
        <div class="section">
            <h3>إعدادات قاعدة البيانات</h3>
            <div class="row">
                <div class="col">
                    <label>Host:</label>
                    <input type="text" id="db_host" value="127.0.0.1">
                </div>
                <div class="col">
                    <label>Port:</label>
                    <input type="text" id="db_port" value="8889">
                </div>
                <div class="col">
                    <label>Database:</label>
                    <input type="text" id="db_name" value="platinum_new">
                </div>
                <div class="col">
                    <label>Username:</label>
                    <input type="text" id="db_user" value="root">
                </div>
                <div class="col">
                    <label>Password:</label>
                    <input type="password" id="db_pass" value="root">
                </div>
            </div>
            <button onclick="testConnection()">اختبار الاتصال</button>
            <div id="connection_result" class="result"></div>
        </div>

        <!-- النقل الشامل -->
        <div class="section">
            <h3>النقل الشامل (الأسرع)</h3>
            <p>نقل جميع الجداول دفعة واحدة من القاعدة القديمة:</p>
            <input type="text" id="old_database" value="platinum" placeholder="اسم القاعدة القديمة">
            <button onclick="migrateAll()" style="background: #28a745;">نقل جميع البيانات</button>
            <div class="progress" id="progress_bar" style="display: none;">
                <div class="progress-bar" id="progress_fill"></div>
            </div>
            <div id="migrate_all_result" class="result"></div>
        </div>

        <!-- رفع ملفات يدوي -->
        <div class="section">
            <h3>رفع ملفات CSV (يدوي)</h3>

            <label>ملف Users:</label>
            <input type="file" id="users_file" accept=".csv">
            <button onclick="uploadFile('users')">رفع Users</button>

            <label>ملف Customers:</label>
            <input type="file" id="customers_file" accept=".csv">
            <button onclick="uploadFile('customers')">رفع Customers</button>

            <label>ملف Countries:</label>
            <input type="file" id="countries_file" accept=".csv">
            <button onclick="uploadFile('countries')">رفع Countries</button>

            <label>ملف Maids:</label>
            <input type="file" id="maids_file" accept=".csv">
            <button onclick="uploadFile('maids')">رفع Maids</button>

            <div id="upload_result" class="result"></div>
        </div>

        <!-- النقل المنفصل -->
        <div class="section">
            <h3>النقل التلقائي (منفصل)</h3>
            <div class="row">
                <div class="col">
                    <button onclick="autoMigrate('users')">نقل Users</button>
                </div>
                <div class="col">
                    <button onclick="autoMigrate('customers')">نقل Customers</button>
                </div>
                <div class="col">
                    <button onclick="autoMigrate('countries')">نقل Countries</button>
                </div>
                <div class="col">
                    <button onclick="autoMigrateMaids()">نقل Maids</button>
                </div>
            </div>
            <div id="auto_result" class="result"></div>
        </div>
    </div>

    <script>
        // CSRF Token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function getParams() {
            return {
                db_host: document.getElementById('db_host').value,
                db_port: document.getElementById('db_port').value,
                db_name: document.getElementById('db_name').value,
                db_user: document.getElementById('db_user').value,
                db_pass: document.getElementById('db_pass').value
            };
        }

        function getOldParams() {
            return {
                old_db_host: '127.0.0.1',
                old_db_port: '8889',
                old_db_name: document.getElementById('old_database').value,
                old_db_user: 'root',
                old_db_pass: 'root'
            };
        }

        function showResult(id, message, success = true) {
            const element = document.getElementById(id);
            element.className = `result ${success ? 'success' : 'error'}`;
            element.innerHTML = message;
            element.style.display = 'block';
        }

        function updateProgress(percent) {
            const progressBar = document.getElementById('progress_bar');
            const progressFill = document.getElementById('progress_fill');

            if (percent > 0) {
                progressBar.style.display = 'block';
                progressFill.style.width = percent + '%';
            } else {
                progressBar.style.display = 'none';
            }
        }

        async function testConnection() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري الاختبار...';

            try {
                const response = await fetch('/migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: 'action=test_connection&' + new URLSearchParams(getParams())
                });

                // معالجة أفضل لـ JSON response
                const text = await response.text();
                console.log('Raw response:', text);

                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error('خطأ في تحليل الاستجابة من الخادم');
                }

                if (data.success) {
                    showResult('connection_result', `الاتصال ناجح - القاعدة: ${data.database} - المستخدمين: ${data.user_count}`, true);
                } else {
                    showResult('connection_result', `فشل الاتصال: ${data.message}`, false);
                }
            } catch (error) {
                showResult('connection_result', `خطأ: ${error.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = 'اختبار الاتصال';
        }

        async function uploadFile(tableType) {
            const fileInput = document.getElementById(tableType + '_file');
            const file = fileInput.files[0];

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

                Object.entries(getParams()).forEach(([key, value]) => {
                    formData.append(key, value);
                });

                const response = await fetch('/migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: formData
                });

                // معالجة أفضل لـ JSON
                const text = await response.text();
                console.log('Upload response:', text);

                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    showResult('upload_result', `خطأ في تحليل استجابة الخادم: ${text.substring(0, 100)}...`, false);
                    return;
                }

                if (data.success) {
                    showResult('upload_result', `تم رفع ${tableType} بنجاح - معالج: ${data.total_users || data.total_customers || data.processed || 'تم'}`, true);
                } else {
                    showResult('upload_result', `خطأ في رفع ${tableType}: ${data.message}`, false);
                }
            } catch (error) {
                showResult('upload_result', `خطأ: ${error.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = `رفع ${tableType}`;
        }

        async function autoMigrate(table) {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل...';

            try {
                const exportParams = {
                    action: 'export_from_old_db',
                    export_table: table,
                    ...getOldParams(),
                    ...getParams()
                };

                const exportResponse = await fetch('/migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: new URLSearchParams(exportParams)
                });

                const exportText = await exportResponse.text();
                console.log('Export response:', exportText);

                let exportData;
                try {
                    exportData = JSON.parse(exportText);
                } catch (e) {
                    throw new Error('خطأ في تحليل استجابة التصدير');
                }

                if (exportData.success) {
                    // الخطوة الثانية: المعالجة
                    const processParams = {
                        action: 'process_exported_file',
                        exported_file: exportData.file_name,
                        table_type: table,
                        ...getParams()
                    };

                    const processResponse = await fetch('/migration/file_processor.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: new URLSearchParams(processParams)
                    });

                    const processText = await processResponse.text();
                    console.log('Process response:', processText);

                    let processData;
                    try {
                        processData = JSON.parse(processText);
                    } catch (e) {
                        throw new Error('خطأ في تحليل استجابة المعالجة');
                    }

                    if (processData.success) {
                        showResult('auto_result',
                            `${table} مكتمل - تصدير: ${exportData.record_count} سجل - معالجة: ${processData.processed} سجل`,
                            true);
                    } else {
                        showResult('auto_result', `${table}: تم التصدير (${exportData.record_count}) لكن فشل النقل - ${processData.message}`, false);
                    }
                } else {
                    showResult('auto_result', `فشل تصدير ${table}: ${exportData.message}`, false);
                }
            } catch (error) {
                showResult('auto_result', `خطأ في ${table}: ${error.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = `نقل ${table}`;
        }

        async function autoMigrateMaids() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري نقل العاملات...';

            try {
                const response = await fetch('/migration/migrate_cvs_now.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: 'action=migrate&cv_table=cvs&preserve_cv_status=true'
                });

                const text = await response.text();
                console.log('Maids response:', text);

                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error('خطأ في تحليل استجابة العاملات');
                }

                if (data.success) {
                    showResult('auto_result',
                        `العاملات مكتملة - إجمالي: ${data.total_records} - معالج: ${data.processed} سجل`,
                        data.processed > 0);
                } else {
                    showResult('auto_result', `خطأ في العاملات: ${data.message}`, false);
                }
            } catch (error) {
                showResult('auto_result', `خطأ في العاملات: ${error.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = 'نقل Maids';
        }

        async function migrateAll() {
            if (!confirm('نقل جميع البيانات من القاعدة القديمة؟')) return;

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل الشامل...';

            const resultDiv = document.getElementById('migrate_all_result');
            resultDiv.className = 'result success';
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = 'بدء النقل الشامل...';

            updateProgress(10);

            try {
                const tables = ['users', 'customers', 'countries'];
                const results = {};
                let step = 0;

                for (const table of tables) {
                    step++;
                    updateProgress((step / 4) * 100);
                    resultDiv.innerHTML += `<br>خطوة ${step}: نقل ${table}...`;

                    // تصدير
                    const exportParams = {
                        action: 'export_from_old_db',
                        export_table: table,
                        ...getOldParams(),
                        ...getParams()
                    };

                    const exportResponse = await fetch('/migration/file_processor.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: new URLSearchParams(exportParams)
                    });

                    const exportText = await exportResponse.text();
                    const exportData = JSON.parse(exportText);

                    if (exportData.success) {
                        // معالجة
                        const processParams = {
                            action: 'process_exported_file',
                            exported_file: exportData.file_name,
                            table_type: table,
                            ...getParams()
                        };

                        const processResponse = await fetch('/migration/file_processor.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: new URLSearchParams(processParams)
                        });

                        const processText = await processResponse.text();
                        const processData = JSON.parse(processText);

                        results[table] = {
                            exported: exportData.record_count,
                            processed: processData.processed || 0,
                            success: processData.success
                        };
                    } else {
                        results[table] = { exported: 0, processed: 0, success: false };
                    }
                }

                // نقل العاملات
                step++;
                updateProgress(100);
                resultDiv.innerHTML += '<br>خطوة 4: نقل العاملات...';

                const maidsResponse = await fetch('/migration/migrate_cvs_now.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: 'action=migrate&cv_table=cvs&preserve_cv_status=true'
                });

                const maidsText = await maidsResponse.text();
                const maidsData = JSON.parse(maidsText);

                results.maids = {
                    exported: maidsData.total_records || 0,
                    processed: maidsData.processed || 0,
                    success: maidsData.success
                };

                // النتائج النهائية
                let summary = '<br><br><strong>النتائج النهائية:</strong><br>';
                let totalProcessed = 0;

                Object.entries(results).forEach(([table, result]) => {
                    summary += `${table}: تصدير ${result.exported} - معالج ${result.processed}<br>`;
                    totalProcessed += result.processed;
                });

                summary += `<br><strong>إجمالي المعالج:</strong> ${totalProcessed} سجل`;

                resultDiv.innerHTML = 'النقل الشامل مكتمل!' + summary;
                updateProgress(0);

            } catch (error) {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = `خطأ في النقل الشامل: ${error.message}`;
                updateProgress(0);
            }

            btn.disabled = false;
            btn.textContent = 'نقل جميع البيانات';
        }

        // اختبار الاتصال تلقائياً
        window.addEventListener('load', function() {
            setTimeout(testConnection, 1000);
        });
    </script>
</body>
</html>
