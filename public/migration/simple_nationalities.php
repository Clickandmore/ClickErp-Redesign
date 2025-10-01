<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>نقل Nationalities - بسيط ومباشر</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 15px;
        }
        .section {
            margin: 25px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-right: 5px solid #007bff;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 25px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover { background: #0056b3; }
        button:disabled { background: #6c757d; cursor: not-allowed; }

        .success { background: #28a745; }
        .danger { background: #dc3545; }
        .warning { background: #ffc107; color: #333; }

        .result {
            margin: 20px 0;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            white-space: pre-wrap;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            max-height: 400px;
            overflow-y: auto;
        }

        .result.success { background: #d4edda; border-color: #28a745; }
        .result.error { background: #f8d7da; border-color: #dc3545; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
        }
        th { background: #f8f9fa; font-weight: bold; }

        .mapping {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }

        .step-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            background: #007bff;
            color: white;
            text-align: center;
            line-height: 30px;
            border-radius: 50%;
            margin-left: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>نقل Nationalities - نظام بسيط ومباشر</h1>

        <!-- معلومات الاتصال -->
        <div class="section">
            <h3><span class="step-number">1</span> معلومات قواعد البيانات</h3>
            <table>
                <tr>
                    <th>القاعدة</th>
                    <th>الاسم</th>
                    <th>الجدول</th>
                    <th>الحالة</th>
                </tr>
                <tr>
                    <td>القديمة</td>
                    <td><strong>platinum</strong></td>
                    <td>nationalities</td>
                    <td id="old_status">...</td>
                </tr>
                <tr>
                    <td>الجديدة</td>
                    <td><strong>platinum_new</strong></td>
                    <td>nationalities + nationalities_translations</td>
                    <td id="new_status">...</td>
                </tr>
            </table>
            <button onclick="checkDatabases()">فحص الاتصال</button>
        </div>

        <!-- تطابق الأعمدة -->
        <div class="section">
            <h3><span class="step-number">2</span> تطابق الأعمدة المحدد</h3>
            <div class="mapping">
                <h4>الجدول الرئيسي (nationalities):</h4>
                <table>
                    <tr>
                        <th>القديم</th>
                        <th>←</th>
                        <th>الجديد</th>
                    </tr>
                    <tr><td>id</td><td>←</td><td>id</td></tr>
                    <tr><td>flag_image</td><td>←</td><td>logo</td></tr>
                    <tr><td>salary</td><td>←</td><td>price</td></tr>
                    <tr><td>sponsor_salary</td><td>←</td><td>service_transportation_cost</td></tr>
                    <tr><td>code</td><td>←</td><td>code</td></tr>
                    <tr><td>key_comment</td><td>←</td><td>key_comment</td></tr>
                    <tr><td>rank</td><td>←</td><td>rank</td></tr>
                    <tr><td>key_code</td><td>←</td><td>key_code</td></tr>
                    <tr><td>status</td><td>←</td><td>status</td></tr>
                    <tr><td>apper_home</td><td>←</td><td>apper_home</td></tr>
                    <tr><td>created_at</td><td>←</td><td>created_at</td></tr>
                    <tr><td>updated_at</td><td>←</td><td>updated_at</td></tr>
                </table>

                <h4>جدول الترجمات (nationalities_translations):</h4>
                <table>
                    <tr>
                        <th>القديم</th>
                        <th>←</th>
                        <th>الجديد</th>
                    </tr>
                    <tr><td>name</td><td>←</td><td>title (ar)</td></tr>
                    <tr><td>name (تحويل)</td><td>←</td><td>title (en)</td></tr>
                    <tr><td>-</td><td>←</td><td>sub_title (فارغ)</td></tr>
                </table>
            </div>
        </div>

        <!-- العمليات -->
        <div class="section">
            <h3><span class="step-number">3</span> عمليات النقل</h3>

            <button onclick="countRecords()" class="warning">عد السجلات</button>
            <button onclick="previewData()">معاينة البيانات</button>
            <button onclick="clearNewTables()" class="danger">مسح الجداول الجديدة</button>
            <button onclick="transferAll()" class="success">نقل كل البيانات</button>
            <button onclick="verifyTransfer()">التحقق من النقل</button>

            <div id="operation_result" class="result" style="display:none;"></div>
        </div>

        <!-- النتائج -->
        <div class="section">
            <h3><span class="step-number">4</span> النتائج النهائية</h3>
            <div id="final_results" class="result" style="display:none;"></div>
        </div>
    </div>

    <script>
        // فحص قواعد البيانات
        function checkDatabases() {
            fetch('simple_nationalities_backend.php?action=check')
                .then(response => response.text())
                .then(result => {
                    document.getElementById('operation_result').style.display = 'block';
                    document.getElementById('operation_result').innerHTML = result;

                    // تحديث الحالات
                    if (result.includes('الاتصال بالقاعدة القديمة: نجح')) {
                        document.getElementById('old_status').innerHTML = '✅ متصل';
                        document.getElementById('old_status').style.color = 'green';
                    }
                    if (result.includes('الاتصال بالقاعدة الجديدة: نجح')) {
                        document.getElementById('new_status').innerHTML = '✅ متصل';
                        document.getElementById('new_status').style.color = 'green';
                    }
                })
                .catch(error => {
                    document.getElementById('operation_result').style.display = 'block';
                    document.getElementById('operation_result').className = 'result error';
                    document.getElementById('operation_result').innerHTML = 'خطأ: ' + error.message;
                });
        }

        // عد السجلات
        function countRecords() {
            fetch('simple_nationalities_backend.php?action=count')
                .then(response => response.text())
                .then(result => {
                    document.getElementById('operation_result').style.display = 'block';
                    document.getElementById('operation_result').innerHTML = result;
                })
                .catch(error => {
                    document.getElementById('operation_result').style.display = 'block';
                    document.getElementById('operation_result').className = 'result error';
                    document.getElementById('operation_result').innerHTML = 'خطأ: ' + error.message;
                });
        }

        // معاينة البيانات
        function previewData() {
            fetch('simple_nationalities_backend.php?action=preview')
                .then(response => response.text())
                .then(result => {
                    document.getElementById('operation_result').style.display = 'block';
                    document.getElementById('operation_result').innerHTML = result;
                })
                .catch(error => {
                    document.getElementById('operation_result').style.display = 'block';
                    document.getElementById('operation_result').className = 'result error';
                    document.getElementById('operation_result').innerHTML = 'خطأ: ' + error.message;
                });
        }

        // مسح الجداول الجديدة
        function clearNewTables() {
            if (!confirm('⚠️ هل أنت متأكد من مسح البيانات في الجداول الجديدة؟')) {
                return;
            }

            fetch('simple_nationalities_backend.php?action=clear')
                .then(response => response.text())
                .then(result => {
                    document.getElementById('operation_result').style.display = 'block';
                    document.getElementById('operation_result').innerHTML = result;
                })
                .catch(error => {
                    document.getElementById('operation_result').style.display = 'block';
                    document.getElementById('operation_result').className = 'result error';
                    document.getElementById('operation_result').innerHTML = 'خطأ: ' + error.message;
                });
        }

        // نقل كل البيانات
        function transferAll() {
            if (!confirm('🚀 هل أنت متأكد من نقل كل البيانات؟')) {
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل...';

            fetch('simple_nationalities_backend.php?action=transfer')
                .then(response => response.text())
                .then(result => {
                    document.getElementById('operation_result').style.display = 'block';
                    document.getElementById('operation_result').innerHTML = result;

                    btn.disabled = false;
                    btn.textContent = 'نقل كل البيانات';
                })
                .catch(error => {
                    document.getElementById('operation_result').style.display = 'block';
                    document.getElementById('operation_result').className = 'result error';
                    document.getElementById('operation_result').innerHTML = 'خطأ: ' + error.message;

                    btn.disabled = false;
                    btn.textContent = 'نقل كل البيانات';
                });
        }

        // التحقق من النقل
        function verifyTransfer() {
            fetch('simple_nationalities_backend.php?action=verify')
                .then(response => response.text())
                .then(result => {
                    document.getElementById('final_results').style.display = 'block';
                    document.getElementById('final_results').innerHTML = result;
                })
                .catch(error => {
                    document.getElementById('final_results').style.display = 'block';
                    document.getElementById('final_results').className = 'result error';
                    document.getElementById('final_results').innerHTML = 'خطأ: ' + error.message;
                });
        }

        // تشغيل تلقائي
        window.onload = function() {
            checkDatabases();
        };
    </script>
</body>
</html>
