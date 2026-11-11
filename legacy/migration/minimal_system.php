<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>نظام النقل - مبسط</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: white;
            margin: 40px auto;
            max-width: 600px;
            padding: 20px;
            line-height: 1.6;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 40px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        .form-group {
            margin: 20px 0;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        input, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 14px;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #333;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 16px;
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
            padding: 10px;
            border-radius: 3px;
            display: none;
        }

        .success {
            background: #f0f9ff;
            border-left: 3px solid #0ea5e9;
            color: #0c4a6e;
        }

        .error {
            background: #fef2f2;
            border-left: 3px solid #ef4444;
            color: #7f1d1d;
        }

        .status {
            background: #f7f7f7;
            padding: 10px;
            border-radius: 3px;
            margin: 10px 0;
            font-size: 13px;
            color: #666;
        }

        hr {
            margin: 30px 0;
            border: none;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <h1>نظام النقل</h1>

    <!-- الاتصال -->
    <div class="form-group">
        <label>قاعدة البيانات:</label>
        <input type="text" id="database" value="platinum_new" placeholder="اسم قاعدة البيانات">
        <button onclick="connect()">اتصال</button>
        <div id="connect_status" class="status">اضغط اتصال للتحقق من قاعدة البيانات</div>
    </div>

    <hr>

    <!-- رفع ملف -->
    <div class="form-group">
        <label>رفع ملف CSV:</label>
        <input type="file" id="file_input" accept=".csv">

        <label>نوع الجدول:</label>
        <select id="table_type">
            <option value="users">المستخدمين (Users)</option>
            <option value="customers">العملاء (Customers)</option>
            <option value="countries">البلدان (Countries)</option>
            <option value="maids">العاملات (Maids)</option>
        </select>

        <button onclick="upload()">رفع</button>
        <div id="upload_result" class="result"></div>
    </div>

    <hr>

    <!-- النقل التلقائي -->
    <div class="form-group">
        <label>النقل التلقائي:</label>
        <input type="text" id="old_database" value="platinum" placeholder="اسم القاعدة القديمة">

        <button onclick="autoMigrate('users')">نقل Users تلقائي</button>
        <button onclick="autoMigrate('customers')">نقل Customers تلقائي</button>
        <button onclick="autoMigrate('countries')">نقل Countries تلقائي</button>
        <button onclick="autoMigrateMaids()">نقل Maids تلقائي</button>

        <button onclick="migrateAllTables()" style="background: #666; margin-top: 20px;">نقل جميع الجداول</button>

        <div id="auto_result" class="result"></div>
    </div>

    <script>
        function getParams() {
            return {
                db_host: '127.0.0.1',
                db_port: '8889',
                db_name: document.getElementById('database').value,
                db_user: 'root',
                db_pass: 'root'
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

        async function connect() {
            const btn = event.target;
            const status = document.getElementById('connect_status');

            btn.disabled = true;
            btn.textContent = 'جاري الاتصال...';
            status.textContent = 'جاري فحص الاتصال...';

            try {
                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=test_connection&' + new URLSearchParams(getParams())
                });

                const data = await response.json();

                if (data.success) {
                    status.textContent = `متصل بنجاح - القاعدة: ${data.database} - المستخدمين: ${data.user_count}`;
                    status.style.background = '#f0f9ff';
                } else {
                    status.textContent = `فشل الاتصال: ${data.message}`;
                    status.style.background = '#fef2f2';
                }
            } catch (error) {
                status.textContent = `خطأ: ${error.message}`;
                status.style.background = '#fef2f2';
            }

            btn.disabled = false;
            btn.textContent = 'اتصال';
        }

        async function upload() {
            const file = document.getElementById('file_input').files[0];
            const tableType = document.getElementById('table_type').value;

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

                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showResult('upload_result', `تم رفع ${tableType} بنجاح - معالج: ${data.total_users || data.total_customers || 'غير محدد'}`, true);
                } else {
                    showResult('upload_result', `خطأ في رفع ${tableType}: ${data.message}`, false);
                }
            } catch (error) {
                showResult('upload_result', `خطأ: ${error.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = 'رفع';
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

                const exportResponse = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(exportParams)
                });

                const exportData = await exportResponse.json();

                if (exportData.success) {
                    const processParams = {
                        action: 'process_exported_file',
                        exported_file: exportData.file_name,
                        table_type: table,
                        ...getParams()
                    };

                    const processResponse = await fetch('file_processor.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: new URLSearchParams(processParams)
                    });

                    const processData = await processResponse.json();

                    showResult('auto_result', `${table}: تصدير ${exportData.record_count} - معالجة ${processData.processed}`, processData.success);
                } else {
                    showResult('auto_result', `فشل تصدير ${table}: ${exportData.message}`, false);
                }
            } catch (error) {
                showResult('auto_result', `خطأ في ${table}: ${error.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = `نقل ${table} تلقائي`;
        }

        async function autoMigrateMaids() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري نقل العاملات...';

            try {
                const response = await fetch('migrate_cvs_now.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=migrate&cv_table=cvs&preserve_cv_status=true'
                });

                const data = await response.json();
                showResult('auto_result', `العاملات: ${data.message} (معالج: ${data.processed})`, data.success);
            } catch (error) {
                showResult('auto_result', `خطأ في العاملات: ${error.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = 'نقل Maids تلقائي';
        }

        async function migrateAllTables() {
            if (!confirm('نقل جميع الجداول؟')) return;

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل الشامل...';

            const resultDiv = document.getElementById('auto_result');
            resultDiv.className = 'result success';
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = 'بدء النقل...';

            try {
                // نقل متتالي لجميع الجداول
                const tables = ['users', 'customers', 'countries'];
                const results = {};

                for (const table of tables) {
                    resultDiv.innerHTML += `<br>نقل ${table}...`;

                    const exportParams = {
                        action: 'export_from_old_db',
                        export_table: table,
                        ...getOldParams(),
                        ...getParams()
                    };

                    const exportResponse = await fetch('file_processor.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: new URLSearchParams(exportParams)
                    });

                    const exportData = await exportResponse.json();

                    if (exportData.success) {
                        const processParams = {
                            action: 'process_exported_file',
                            exported_file: exportData.file_name,
                            table_type: table,
                            ...getParams()
                        };

                        const processResponse = await fetch('file_processor.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: new URLSearchParams(processParams)
                        });

                        const processData = await processResponse.json();
                        results[table] = processData.processed || 0;
                    } else {
                        results[table] = 0;
                    }
                }

                // نقل العاملات
                resultDiv.innerHTML += '<br>نقل العاملات...';
                const maidsResponse = await fetch('migrate_cvs_now.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=migrate&cv_table=cvs&preserve_cv_status=true'
                });
                const maidsData = await maidsResponse.json();
                results.maids = maidsData.processed || 0;

                // النتائج النهائية
                const total = Object.values(results).reduce((a, b) => a + b, 0);
                resultDiv.innerHTML = `النقل مكتمل:<br>Users: ${results.users}<br>Customers: ${results.customers}<br>Countries: ${results.countries}<br>Maids: ${results.maids}<br><br>الإجمالي: ${total}`;

            } catch (error) {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = `خطأ: ${error.message}`;
            }

            btn.disabled = false;
            btn.textContent = 'نقل جميع الجداول';
        }

        // اختبار الاتصال تلقائياً
        window.addEventListener('load', function() {
            setTimeout(connect, 500);
        });
    </script>
</body>
</html>
