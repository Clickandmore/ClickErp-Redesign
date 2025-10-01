<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام النقل البسيط</title>
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

        <!-- رفع Users -->
        <div class="section">
            <h3>نقل المستخدمين</h3>
            <label>ملف Users (CSV):</label>
            <input type="file" id="users_file" accept=".csv">
            <button onclick="uploadUsers()">رفع المستخدمين</button>
            <div id="users_result" class="result"></div>
        </div>

        <!-- رفع Customers -->
        <div class="section">
            <h3>نقل العملاء</h3>
            <label>ملف Customers (CSV):</label>
            <input type="file" id="customers_file" accept=".csv">
            <button onclick="uploadCustomers()">رفع العملاء</button>
            <div id="customers_result" class="result"></div>
        </div>

        <!-- رفع Countries -->
        <div class="section">
            <h3>نقل البلدان</h3>
            <label>ملف Countries (CSV):</label>
            <input type="file" id="countries_file" accept=".csv">
            <button onclick="uploadCountries()">رفع البلدان</button>
            <div id="countries_result" class="result"></div>
        </div>

        <!-- رفع Maids -->
        <div class="section">
            <h3>نقل العاملات</h3>
            <label>ملف Maids/CV (CSV):</label>
            <input type="file" id="maids_file" accept=".csv">
            <button onclick="uploadMaids()">رفع العاملات</button>
            <div id="maids_result" class="result"></div>
        </div>

        <!-- النقل الشامل -->
        <div class="section">
            <h3>النقل الشامل (مُوصى)</h3>
            <p>نقل جميع الجداول دفعة واحدة من القاعدة القديمة:</p>
            <button onclick="migrateAll()" style="background: #28a745;">نقل جميع البيانات (Users + Customers + Countries + Maids)</button>
            <div id="migrate_all_result" class="result"></div>
        </div>

        <!-- النقل التلقائي -->
        <div class="section">
            <h3>النقل التلقائي (منفصل)</h3>
            <div class="row">
                <div class="col">
                    <label>Host القديم:</label>
                    <input type="text" id="old_host" value="127.0.0.1">
                </div>
                <div class="col">
                    <label>Port القديم:</label>
                    <input type="text" id="old_port" value="8889">
                </div>
                <div class="col">
                    <label>Database القديم:</label>
                    <input type="text" id="old_db" value="platinum">
                </div>
            </div>

            <div class="row">
                <div class="col">
                    <button onclick="autoMigrateUsers()">نقل Users تلقائي</button>
                </div>
                <div class="col">
                    <button onclick="autoMigrateCustomers()">نقل Customers تلقائي</button>
                </div>
                <div class="col">
                    <button onclick="autoMigrateCountries()">نقل Countries تلقائي</button>
                </div>
                <div class="col">
                    <button onclick="autoMigrateMaids()">نقل Maids تلقائي</button>
                </div>
            </div>
            <div id="auto_result" class="result"></div>
        </div>
    </div>

    <script>
        function getDbParams() {
            return {
                db_host: document.getElementById('db_host').value,
                db_port: document.getElementById('db_port').value,
                db_name: document.getElementById('db_name').value,
                db_user: document.getElementById('db_user').value,
                db_pass: document.getElementById('db_pass').value
            };
        }

        function getOldDbParams() {
            return {
                old_db_host: document.getElementById('old_host').value,
                old_db_port: document.getElementById('old_port').value,
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

        async function testConnection() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري الاختبار...';

            try {
                const params = getDbParams();
                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=test_connection&' + new URLSearchParams(params)
                });

                const data = await response.json();

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

        async function uploadUsers() {
            const file = document.getElementById('users_file').files[0];
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

                Object.entries(getDbParams()).forEach(([key, value]) => {
                    formData.append(key, value);
                });

                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showResult('users_result', `تم رفع المستخدمين - المعالج: ${data.total_users || 0}`, true);
                } else {
                    showResult('users_result', `خطأ: ${data.message}`, false);
                }
            } catch (error) {
                showResult('users_result', `خطأ: ${error.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = 'رفع المستخدمين';
        }

        async function uploadCustomers() {
            const file = document.getElementById('customers_file').files[0];
            if (!file) {
                alert('اختر ملف أولاً');
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري الرفع...';

            // نفس منطق uploadUsers مع customers
            showResult('customers_result', 'قيد التطوير - استخدم النقل التلقائي', true);

            btn.disabled = false;
            btn.textContent = 'رفع العملاء';
        }

        async function uploadCountries() {
            const file = document.getElementById('countries_file').files[0];
            if (!file) {
                alert('اختر ملف أولاً');
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري الرفع...';

            showResult('countries_result', 'قيد التطوير - استخدم النقل التلقائي', true);

            btn.disabled = false;
            btn.textContent = 'رفع البلدان';
        }

        async function uploadMaids() {
            const file = document.getElementById('maids_file').files[0];
            if (!file) {
                alert('اختر ملف أولاً');
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري الرفع...';

            showResult('maids_result', 'قيد التطوير - استخدم النقل التلقائي', true);

            btn.disabled = false;
            btn.textContent = 'رفع العاملات';
        }

        async function autoMigrateUsers() {
            if (!confirm('نقل المستخدمين تلقائياً من القاعدة القديمة؟')) return;

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل...';

            try {
                const params = {
                    action: 'export_from_old_db',
                    export_table: 'users',
                    ...getOldDbParams(),
                    ...getDbParams()
                };

                const exportResponse = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(params)
                });

                const exportData = await exportResponse.json();

                if (exportData.success) {
                    const processParams = {
                        action: 'process_exported_file',
                        exported_file: exportData.file_name,
                        table_type: 'users',
                        ...getDbParams()
                    };

                    const processResponse = await fetch('file_processor.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: new URLSearchParams(processParams)
                    });

                    const processData = await processResponse.json();

                    if (processData.success) {
                        showResult('auto_result', `تم نقل المستخدمين - تصدير: ${exportData.record_count} - معالجة: ${processData.processed}`, true);
                    } else {
                        showResult('auto_result', `تم التصدير لكن فشل النقل: ${processData.message}`, false);
                    }
                } else {
                    showResult('auto_result', `فشل التصدير: ${exportData.message}`, false);
                }
            } catch (error) {
                showResult('auto_result', `خطأ: ${error.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = 'نقل Users تلقائي';
        }

        async function autoMigrateCustomers() {
            if (!confirm('نقل العملاء تلقائياً؟')) return;

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل...';

            try {
                const params = {
                    action: 'export_from_old_db',
                    export_table: 'customers',
                    ...getOldDbParams(),
                    ...getDbParams()
                };

                const exportResponse = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(params)
                });

                const exportData = await exportResponse.json();

                if (exportData.success) {
                    const processParams = {
                        action: 'process_exported_file',
                        exported_file: exportData.file_name,
                        table_type: 'customers',
                        ...getDbParams()
                    };

                    const processResponse = await fetch('file_processor.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: new URLSearchParams(processParams)
                    });

                    const processData = await processResponse.json();

                    if (processData.success) {
                        showResult('auto_result', `تم دمج العملاء - تصدير: ${exportData.record_count} - دمج: ${processData.processed}`, true);
                    } else {
                        showResult('auto_result', `تم التصدير لكن فشل الدمج: ${processData.message}`, false);
                    }
                } else {
                    showResult('auto_result', `فشل تصدير العملاء: ${exportData.message}`, false);
                }
            } catch (error) {
                showResult('auto_result', `خطأ: ${error.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = 'نقل Customers تلقائي';
        }

        async function autoMigrateCountries() {
            if (!confirm('نقل البلدان تلقائياً؟')) return;
            performAutoMigration('countries', event.target);
        }

        async function autoMigrateMaids() {
            if (!confirm('نقل العاملات تلقائياً؟')) return;

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل...';

            try {
                const response = await fetch('migrate_cvs_now.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=migrate&cv_table=cvs&preserve_cv_status=true'
                });

                const data = await response.json();

                if (data.success && data.processed > 0) {
                    showResult('auto_result', `تم نقل العاملات - المعالج: ${data.processed} من ${data.total_records}`, true);
                } else {
                    showResult('auto_result', `نقل العاملات: ${data.message} (معالج: ${data.processed})`, data.processed > 0);
                }
            } catch (error) {
                showResult('auto_result', `خطأ في نقل العاملات: ${error.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = 'نقل Maids تلقائي';
        }

        async function performAutoMigration(table, btn) {
            btn.disabled = true;
            btn.textContent = 'جاري النقل...';

            try {
                const params = {
                    action: 'export_from_old_db',
                    export_table: table,
                    ...getOldDbParams(),
                    ...getDbParams()
                };

                const exportResponse = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(params)
                });

                const exportData = await exportResponse.json();

                if (exportData.success) {
                    const processParams = {
                        action: 'process_exported_file',
                        exported_file: exportData.file_name,
                        table_type: table,
                        ...getDbParams()
                    };

                    const processResponse = await fetch('file_processor.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: new URLSearchParams(processParams)
                    });

                    const processData = await processResponse.json();

                    if (processData.success) {
                        showResult('auto_result', `تم نقل ${table} - تصدير: ${exportData.record_count} - معالجة: ${processData.processed}`, true);
                    } else {
                        showResult('auto_result', `${table}: تم التصدير لكن فشل النقل - ${processData.message}`, false);
                    }
                } else {
                    showResult('auto_result', `فشل تصدير ${table}: ${exportData.message}`, false);
                }
            } catch (error) {
                showResult('auto_result', `خطأ في ${table}: ${error.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = `نقل ${table} تلقائي`;
        }

        async function migrateAll() {
            if (!confirm('نقل جميع البيانات؟\n\nسيتم:\n- Users (2,300+)\n- Customers (2,100+)\n- Countries (250+)\n- Maids (2,100+)\n\nالمتابعة؟')) {
                return;
            }

            const btn = event.target;
            const resultDiv = document.getElementById('migrate_all_result');

            btn.disabled = true;
            btn.textContent = 'جاري النقل الشامل...';
            resultDiv.className = 'result success';
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = 'بدء النقل الشامل...';

            const results = {};

            try {
                // Users
                btn.textContent = 'نقل Users...';
                resultDiv.innerHTML += '<br>جاري نقل Users...';
                results.users = await performMigration('users');

                // Customers
                btn.textContent = 'نقل Customers...';
                resultDiv.innerHTML += '<br>جاري نقل Customers...';
                results.customers = await performMigration('customers');

                // Countries
                btn.textContent = 'نقل Countries...';
                resultDiv.innerHTML += '<br>جاري نقل Countries...';
                results.countries = await performMigration('countries');

                // Maids
                btn.textContent = 'نقل Maids...';
                resultDiv.innerHTML += '<br>جاري نقل Maids...';
                const maidsResponse = await fetch('migrate_cvs_now.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=migrate&cv_table=cvs&preserve_cv_status=true'
                });
                results.maids = await maidsResponse.json();

                // النتائج النهائية
                let summary = '<br><br><strong>النتائج النهائية:</strong><br>';
                summary += `Users: ${results.users.processed || 0}<br>`;
                summary += `Customers: ${results.customers.processed || 0}<br>`;
                summary += `Countries: ${results.countries.processed || 0}<br>`;
                summary += `Maids: ${results.maids.processed || 0}<br>`;
                summary += `<br><strong>الإجمالي:</strong> ${(results.users.processed || 0) + (results.customers.processed || 0) + (results.countries.processed || 0) + (results.maids.processed || 0)}`;

                resultDiv.innerHTML = 'النقل الشامل مكتمل!' + summary;

            } catch (error) {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = `خطأ في النقل الشامل: ${error.message}`;
            }

            btn.disabled = false;
            btn.textContent = 'نقل جميع البيانات (Users + Customers + Countries + Maids)';
        }

        async function performMigration(table) {
            const exportParams = {
                action: 'export_from_old_db',
                export_table: table,
                ...getOldDbParams(),
                ...getDbParams()
            };

            const exportResponse = await fetch('file_processor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams(exportParams)
            });

            const exportData = await exportResponse.json();

            if (!exportData.success) {
                throw new Error(`فشل تصدير ${table}: ${exportData.message}`);
            }

            const processParams = {
                action: 'process_exported_file',
                exported_file: exportData.file_name,
                table_type: table,
                ...getDbParams()
            };

            const processResponse = await fetch('file_processor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams(processParams)
            });

            const processData = await processResponse.json();

            if (!processData.success) {
                throw new Error(`فشل معالجة ${table}: ${processData.message}`);
            }

            return processData;
        }

        // اختبار الاتصال عند تحميل الصفحة
        window.addEventListener('load', function() {
            setTimeout(testConnection, 1000);
        });
    </script>
</body>
</html>
