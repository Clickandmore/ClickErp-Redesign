<?php
/**
 * صفحة تحليل مشاكل الرفع
 */
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>🔧 تحليل مشاكل الرفع</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; background: #f8f9fa; }
        .debug-section { background: white; margin: 20px 0; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1><i class="fas fa-bug"></i> تحليل مشاكل الرفع</h1>

        <div class="debug-section">
            <h3>📋 معلومات النظام</h3>
            <ul>
                <li><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></li>
                <li><strong>Upload Max Filesize:</strong> <?php echo ini_get('upload_max_filesize'); ?></li>
                <li><strong>Post Max Size:</strong> <?php echo ini_get('post_max_size'); ?></li>
                <li><strong>Max Execution Time:</strong> <?php echo ini_get('max_execution_time'); ?>s</li>
                <li><strong>Memory Limit:</strong> <?php echo ini_get('memory_limit'); ?></li>
            </ul>
        </div>

        <div class="debug-section">
            <h3>📁 فحص المجلدات</h3>
            <?php
            $directories = [
                __DIR__ . '/exports',
                __DIR__ . '/uploaded_files',
                __DIR__ . '/uploads'
            ];

            foreach ($directories as $dir) {
                $exists = is_dir($dir);
                $writable = $exists && is_writable($dir);
                $status = $exists ? ($writable ? '✅ موجود وقابل للكتابة' : '⚠️ موجود لكن غير قابل للكتابة') : '❌ غير موجود';
                echo "<p><strong>$dir:</strong> $status</p>";
            }
            ?>
        </div>

        <div class="debug-section">
            <h3>🔗 اختبار الروابط</h3>
            <div class="row">
                <div class="col-md-6">
                    <button class="btn btn-info w-100" onclick="testFileProcessor()">
                        اختبار file_processor.php
                    </button>
                </div>
                <div class="col-md-6">
                    <button class="btn btn-success w-100" onclick="testConnection()">
                        اختبار الاتصال بقاعدة البيانات
                    </button>
                </div>
            </div>
            <div id="test-results" class="mt-3"></div>
        </div>

        <div class="debug-section">
            <h3>📤 اختبار الرفع السريع</h3>
            <form id="quickTestForm">
                <div class="row">
                    <div class="col-md-8">
                        <input type="file" class="form-control" id="test-file" accept=".csv">
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-primary w-100" onclick="quickUploadTest()">
                            اختبار رفع سريع
                        </button>
                    </div>
                </div>
            </form>
            <div id="upload-test-results" class="mt-3"></div>
        </div>

        <div class="debug-section">
            <h3>🚀 اختبار التصدير التلقائي</h3>
            <div class="row g-2">
                <div class="col-md-3">
                    <input type="text" id="old_db" class="form-control" placeholder="Old DB" value="platinum">
                </div>
                <div class="col-md-3">
                    <input type="text" id="new_db" class="form-control" placeholder="New DB" value="platinum_new">
                </div>
                <div class="col-md-3">
                    <select id="table_type" class="form-select">
                        <option value="users">users</option>
                        <option value="customers">customers</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-warning w-100" onclick="testAutoExport()">
                        اختبار تصدير تلقائي
                    </button>
                </div>
            </div>
            <div id="auto-export-results" class="mt-3"></div>
        </div>
    </div>

    <script>
        async function testFileProcessor() {
            const resultsDiv = document.getElementById('test-results');
            resultsDiv.innerHTML = '<div class="spinner-border"></div> جاري الاختبار...';

            try {
                const response = await fetch('test_upload.php', { method: 'POST' });
                const data = await response.json();

                resultsDiv.innerHTML = `
                    <div class="alert alert-success">
                        <h6>✅ file_processor.php يعمل!</h6>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    </div>
                `;
            } catch (error) {
                resultsDiv.innerHTML = `<div class="alert alert-danger">❌ خطأ: ${error.message}</div>`;
            }
        }

        async function testConnection() {
            const resultsDiv = document.getElementById('test-results');
            resultsDiv.innerHTML = '<div class="spinner-border"></div> جاري اختبار الاتصال...';

            try {
                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=test_connection&db_host=127.0.0.1&db_port=8889&db_name=platinum_new&db_user=root&db_pass=root'
                });

                const data = await response.json();

                resultsDiv.innerHTML = `
                    <div class="alert ${data.success ? 'alert-success' : 'alert-danger'}">
                        <h6>${data.success ? '✅' : '❌'} نتيجة اختبار الاتصال</h6>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    </div>
                `;
            } catch (error) {
                resultsDiv.innerHTML = `<div class="alert alert-danger">❌ خطأ: ${error.message}</div>`;
            }
        }

        async function quickUploadTest() {
            const fileInput = document.getElementById('test-file');
            const file = fileInput.files[0];
            const resultsDiv = document.getElementById('upload-test-results');

            if (!file) {
                alert('اختر ملف CSV أولاً');
                return;
            }

            resultsDiv.innerHTML = '<div class="spinner-border"></div> جاري اختبار الرفع...';

            const formData = new FormData();
            formData.append('action', 'process_files');
            formData.append('files[]', file);
            formData.append('db_host', '127.0.0.1');
            formData.append('db_port', '8889');
            formData.append('db_name', 'platinum_new');
            formData.append('db_user', 'root');
            formData.append('db_pass', 'root');

            try {
                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                resultsDiv.innerHTML = `
                    <div class="alert ${data.success ? 'alert-success' : 'alert-danger'}">
                        <h6>${data.success ? '✅' : '❌'} نتيجة اختبار الرفع</h6>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    </div>
                `;
            } catch (error) {
                resultsDiv.innerHTML = `<div class="alert alert-danger">❌ خطأ: ${error.message}</div>`;
            }
        }

        async function testAutoExport() {
            const resultsDiv = document.getElementById('auto-export-results');
            const table = document.getElementById('table_type').value;
            const oldDb = document.getElementById('old_db').value;
            const newDb = document.getElementById('new_db').value;

            resultsDiv.innerHTML = '<div class="spinner-border"></div> جاري اختبار التصدير التلقائي...';

            try {
                // Step 1: Export
                const exportResponse = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=export_from_old_db&export_table=${table}&old_db_host=127.0.0.1&old_db_port=8889&old_db_name=${oldDb}&old_db_user=root&old_db_pass=root&db_host=127.0.0.1&db_port=8889&db_name=${newDb}&db_user=root&db_pass=root`
                });

                const exportData = await exportResponse.json();

                if (!exportData.success) {
                    throw new Error('فشل التصدير: ' + exportData.message);
                }

                // Step 2: Process
                const processResponse = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=process_exported_file&exported_file=${exportData.file_name}&table_type=${table}&db_host=127.0.0.1&db_port=8889&db_name=${newDb}&db_user=root&db_pass=root`
                });

                const processData = await processResponse.json();

                resultsDiv.innerHTML = `
                    <div class="alert ${processData.success ? 'alert-success' : 'alert-warning'}">
                        <h6>📊 نتيجة التصدير التلقائي</h6>
                        <p><strong>التصدير:</strong> ${exportData.success ? `✅ ${exportData.record_count} سجل` : '❌ فشل'}</p>
                        <p><strong>المعالجة:</strong> ${processData.success ? `✅ ${processData.processed} سجل` : '❌ فشل'}</p>
                        <details>
                            <summary>تفاصيل كاملة</summary>
                            <pre>التصدير: ${JSON.stringify(exportData, null, 2)}

المعالجة: ${JSON.stringify(processData, null, 2)}</pre>
                        </details>
                    </div>
                `;
            } catch (error) {
                resultsDiv.innerHTML = `<div class="alert alert-danger">❌ خطأ: ${error.message}</div>`;
            }
        }
    </script>
</body>
</html>
