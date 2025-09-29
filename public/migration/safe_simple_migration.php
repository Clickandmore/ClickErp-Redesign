<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>نظام النقل الآمن المبسط</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .step {
            background: white;
            margin: 20px 0;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-right: 5px solid #007bff;
        }

        .step h3 {
            color: #007bff;
            margin-top: 0;
        }

        .btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 5px;
        }

        .btn:hover { background: #0056b3; }
        .btn:disabled { background: #6c757d; cursor: not-allowed; }
        .btn-success { background: #28a745; }
        .btn-warning { background: #ffc107; color: #333; }
        .btn-danger { background: #dc3545; }
        .btn-info { background: #17a2b8; }

        .result {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
            font-family: monospace;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
        }

        .success { border-color: #28a745; background: #d4edda; }
        .error { border-color: #dc3545; background: #f8d7da; }
        .warning { border-color: #ffc107; background: #fff3cd; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 14px;
        }

        th, td {
            padding: 8px;
            text-align: center;
            border: 1px solid #ddd;
        }

        th { background: #f8f9fa; font-weight: bold; }

        .progress {
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin: 15px 0;
            height: 25px;
        }

        .progress-bar {
            background: #007bff;
            height: 100%;
            line-height: 25px;
            text-align: center;
            color: white;
            transition: width 0.3s;
        }

        .file-drop {
            border: 2px dashed #007bff;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            background: #f8f9fa;
            cursor: pointer;
            margin: 15px 0;
        }

        .file-drop:hover { background: #e9ecef; }

        .highlight {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛡️ نظام النقل الآمن المبسط</h1>
        <p><strong>الفلسفة:</strong> نقل تدريجي مع فحص كل خطوة والتأكد من كل سجل</p>

        <div class="highlight">
            <h3>📋 خطة العمل المبسطة:</h3>
            <ol>
                <li><strong>تصدير البيانات:</strong> CSV منفصل لكل جدول</li>
                <li><strong>فحص البيانات:</strong> تحليل وتطابق الأعمدة</li>
                <li><strong>النقل التدريجي:</strong> جدول واحد في كل مرة</li>
                <li><strong>التحقق:</strong> مقارنة العدد والبيانات</li>
                <li><strong>التأكيد:</strong> راجع كل خطوة قبل المتابعة</li>
            </ol>
        </div>

        <!-- الخطوة 1: حالة قاعدة البيانات -->
        <div class="step">
            <h3>1️⃣ فحص حالة قواعد البيانات</h3>
            <p>التأكد من الاتصال وعدد السجلات الحالي</p>
            <button onclick="checkDatabaseStatus()" class="btn">فحص قواعد البيانات</button>
            <div id="db_status" class="result" style="display:none;"></div>
        </div>

        <!-- الخطوة 2: تحليل الجداول -->
        <div class="step">
            <h3>2️⃣ تحليل الجداول المطلوبة</h3>
            <p>فحص الجداول المتاحة وتحديد الأولويات</p>

            <table>
                <thead>
                    <tr>
                        <th>الجدول</th>
                        <th>الأولوية</th>
                        <th>الحالة</th>
                        <th>إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>users</strong></td>
                        <td>✅ مكتمل</td>
                        <td>2,297 سجل</td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td><strong>cvs → maids</strong></td>
                        <td>🔥 عالية</td>
                        <td>تحتاج نقل</td>
                        <td><button onclick="analyzeTable('cvs')" class="btn btn-info">تحليل</button></td>
                    </tr>
                    <tr>
                        <td><strong>contracts</strong></td>
                        <td>🔥 عالية</td>
                        <td>تحتاج فحص</td>
                        <td><button onclick="analyzeTable('contracts')" class="btn btn-info">تحليل</button></td>
                    </tr>
                    <tr>
                        <td><strong>requests</strong></td>
                        <td>⚠️ متوسطة</td>
                        <td>تحتاج فحص</td>
                        <td><button onclick="analyzeTable('requests')" class="btn btn-info">تحليل</button></td>
                    </tr>
                </tbody>
            </table>

            <div id="table_analysis" class="result" style="display:none;"></div>
        </div>

        <!-- الخطوة 3: تصدير البيانات -->
        <div class="step">
            <h3>3️⃣ تصدير البيانات (طريقة آمنة)</h3>
            <p>تصدير كل جدول إلى CSV منفصل مع فحص العدد</p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <button onclick="exportTable('cvs')" class="btn btn-success">تصدير CVs</button>
                <button onclick="exportTable('contracts')" class="btn btn-success">تصدير العقود</button>
                <button onclick="exportTable('requests')" class="btn btn-success">تصدير الطلبات</button>
                <button onclick="exportTable('nationalities')" class="btn btn-success">تصدير الجنسيات</button>
            </div>

            <div id="export_results" class="result" style="display:none;"></div>
        </div>

        <!-- الخطوة 4: فحص البيانات -->
        <div class="step">
            <h3>4️⃣ فحص البيانات المصدرة</h3>
            <p>رفع ملف CSV وفحص محتوياته قبل النقل</p>

            <div class="file-drop" onclick="document.getElementById('csv_file').click()">
                <strong>📁 اختر ملف CSV للفحص</strong><br>
                <small>اضغط هنا لاختيار الملف</small>
                <input type="file" id="csv_file" accept=".csv" style="display:none;" onchange="analyzeCSV(this)">
            </div>

            <div id="csv_analysis" class="result" style="display:none;"></div>
        </div>

        <!-- الخطوة 5: تطابق الأعمدة -->
        <div class="step">
            <h3>5️⃣ تطابق الأعمدة</h3>
            <p>ربط أعمدة الجدول القديم بالجديد</p>

            <select id="source_table" onchange="showColumnMapping()">
                <option value="">اختر الجدول المصدر</option>
                <option value="cvs">CVs → Maids</option>
                <option value="contracts">Contracts</option>
                <option value="requests">Requests</option>
            </select>

            <div id="column_mapping" style="display:none;">
                <h4>تطابق الأعمدة:</h4>
                <div id="mapping_form"></div>
                <button onclick="saveColumnMapping()" class="btn btn-warning">حفظ التطابق</button>
            </div>
        </div>

        <!-- الخطوة 6: النقل التدريجي -->
        <div class="step">
            <h3>6️⃣ النقل التدريجي الآمن</h3>
            <p>نقل البيانات على دفعات صغيرة مع التحقق</p>

            <div style="margin: 15px 0;">
                <label>حجم الدفعة:</label>
                <select id="batch_size">
                    <option value="50">50 سجل</option>
                    <option value="100" selected>100 سجل</option>
                    <option value="200">200 سجل</option>
                    <option value="500">500 سجل</option>
                </select>
            </div>

            <button onclick="startBatchMigration()" class="btn btn-danger">بدء النقل التدريجي</button>
            <button onclick="pauseMigration()" class="btn btn-warning" disabled>إيقاف مؤقت</button>

            <div class="progress">
                <div id="migration_progress" class="progress-bar" style="width: 0%;">0%</div>
            </div>

            <div id="migration_log" class="result" style="display:none;"></div>
        </div>

        <!-- الخطوة 7: التحقق النهائي -->
        <div class="step">
            <h3>7️⃣ التحقق النهائي</h3>
            <p>مقارنة البيانات والتأكد من اكتمال النقل</p>

            <button onclick="verifyMigration()" class="btn btn-info">فحص النتيجة النهائية</button>
            <button onclick="generateReport()" class="btn btn-success">إنشاء تقرير مفصل</button>

            <div id="verification_results" class="result" style="display:none;"></div>
        </div>
    </div>

    <script>
        let migrationState = {
            currentTable: null,
            currentBatch: 0,
            totalBatches: 0,
            isPaused: false,
            columnMappings: {}
        };

        // معالج JSON آمن
        async function safeFetch(url, options = {}) {
            try {
                const response = await fetch(url, options);
                const text = await response.text();

                console.log('Raw response:', text);

                // تنظيف الاستجابة من HTML/PHP errors
                let cleanText = text;

                // إزالة PHP/HTML errors
                cleanText = cleanText.replace(/(<br\s*\/?>|<[^>]*>)/gi, '');
                cleanText = cleanText.replace(/Warning:[^\n]*/gi, '');
                cleanText = cleanText.replace(/Notice:[^\n]*/gi, '');
                cleanText = cleanText.replace(/Fatal error:[^\n]*/gi, '');

                // البحث عن JSON صالح
                const jsonMatch = cleanText.match(/\{.*\}/s);
                if (jsonMatch) {
                    cleanText = jsonMatch[0];
                } else {
                    throw new Error('لم يتم العثور على JSON صالح في الاستجابة');
                }

                console.log('Clean JSON:', cleanText);

                try {
                    return JSON.parse(cleanText);
                } catch (e) {
                    throw new Error('خطأ في تحليل JSON: ' + e.message + '\nالنص: ' + cleanText);
                }

            } catch (error) {
                console.error('Fetch error:', error);
                throw new Error('خطأ في الشبكة أو تحليل البيانات: ' + error.message);
            }
        }

        async function checkDatabaseStatus() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري الفحص...';

            try {
                const data = await safeFetch('migration/safe_migration_backend.php?action=check_status');

                let resultHTML = '<strong>حالة قواعد البيانات:</strong>\n\n';

                if (data.success) {
                    resultHTML += '✅ الاتصال بالقواعد: نجح\n\n';
                    resultHTML += '<strong>القاعدة القديمة (platinum):</strong>\n';
                    Object.entries(data.old_db).forEach(([table, count]) => {
                        resultHTML += `- ${table}: ${count} سجل\n`;
                    });

                    resultHTML += '\n<strong>القاعدة الجديدة (platinum_new):</strong>\n';
                    Object.entries(data.new_db).forEach(([table, count]) => {
                        resultHTML += `- ${table}: ${count} سجل\n`;
                    });
                } else {
                    resultHTML += '❌ خطأ: ' + data.message;
                }

                const resultDiv = document.getElementById('db_status');
                resultDiv.className = 'result ' + (data.success ? 'success' : 'error');
                resultDiv.innerHTML = resultHTML;
                resultDiv.style.display = 'block';

            } catch (error) {
                const resultDiv = document.getElementById('db_status');
                resultDiv.className = 'result error';
                resultDiv.innerHTML = '❌ خطأ: ' + error.message;
                resultDiv.style.display = 'block';
            }

            btn.disabled = false;
            btn.textContent = 'فحص قواعد البيانات';
        }

        async function analyzeTable(tableName) {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري التحليل...';

            try {
                const data = await safeFetch(`migration/safe_migration_backend.php?action=analyze_table&table=${tableName}`);

                let resultHTML = `<strong>تحليل جدول ${tableName}:</strong>\n\n`;

                if (data.success) {
                    resultHTML += `📊 العدد: ${data.count} سجل\n`;
                    resultHTML += `📋 الأعمدة: ${data.columns.length} عمود\n\n`;
                    resultHTML += '<strong>الأعمدة:</strong>\n';
                    data.columns.forEach(col => {
                        resultHTML += `- ${col}\n`;
                    });

                    if (data.sample_data) {
                        resultHTML += '\n<strong>عينة من البيانات:</strong>\n';
                        data.sample_data.forEach((row, index) => {
                            resultHTML += `\nصف ${index + 1}:\n`;
                            Object.entries(row).forEach(([key, value]) => {
                                resultHTML += `  ${key}: ${value}\n`;
                            });
                        });
                    }
                } else {
                    resultHTML += '❌ خطأ: ' + data.message;
                }

                const resultDiv = document.getElementById('table_analysis');
                resultDiv.className = 'result ' + (data.success ? 'success' : 'error');
                resultDiv.innerHTML = resultHTML;
                resultDiv.style.display = 'block';

            } catch (error) {
                const resultDiv = document.getElementById('table_analysis');
                resultDiv.className = 'result error';
                resultDiv.innerHTML = '❌ خطأ: ' + error.message;
                resultDiv.style.display = 'block';
            }

            btn.disabled = false;
            btn.textContent = 'تحليل';
        }

        async function exportTable(tableName) {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري التصدير...';

            try {
                const data = await safeFetch(`migration/safe_migration_backend.php?action=export_table&table=${tableName}`);

                let resultHTML = `<strong>تصدير ${tableName}:</strong>\n\n`;

                if (data.success) {
                    resultHTML += `✅ تم التصدير: ${data.exported} سجل\n`;
                    resultHTML += `📁 الملف: ${data.filename}\n`;
                    resultHTML += `📊 الحجم: ${data.filesize}\n`;

                    // إضافة رابط التحميل
                    const downloadLink = `migration/exports/${data.filename}`;
                    resultHTML += `\n<a href="${downloadLink}" download style="background:#007bff;color:white;padding:8px 15px;text-decoration:none;border-radius:5px;">💾 تحميل الملف</a>`;
                } else {
                    resultHTML += '❌ خطأ: ' + data.message;
                }

                const resultDiv = document.getElementById('export_results');
                resultDiv.className = 'result ' + (data.success ? 'success' : 'error');
                resultDiv.innerHTML = resultHTML;
                resultDiv.style.display = 'block';

            } catch (error) {
                const resultDiv = document.getElementById('export_results');
                resultDiv.className = 'result error';
                resultDiv.innerHTML = '❌ خطأ: ' + error.message;
                resultDiv.style.display = 'block';
            }

            btn.disabled = false;
            btn.textContent = `تصدير ${tableName}`;
        }

        function analyzeCSV(input) {
            const file = input.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                const csv = e.target.result;
                const lines = csv.split('\n');
                const headers = lines[0].split(',');

                let resultHTML = `<strong>تحليل الملف: ${file.name}</strong>\n\n`;
                resultHTML += `📊 عدد الصفوف: ${lines.length - 1}\n`;
                resultHTML += `📋 عدد الأعمدة: ${headers.length}\n\n`;

                resultHTML += '<strong>الأعمدة:</strong>\n';
                headers.forEach((header, index) => {
                    resultHTML += `${index + 1}. ${header.trim()}\n`;
                });

                // عينة من البيانات
                if (lines.length > 1) {
                    resultHTML += '\n<strong>عينة من البيانات:</strong>\n';
                    for (let i = 1; i <= Math.min(3, lines.length - 1); i++) {
                        const values = lines[i].split(',');
                        resultHTML += `\nصف ${i}:\n`;
                        headers.forEach((header, index) => {
                            resultHTML += `  ${header.trim()}: ${values[index] || 'فارغ'}\n`;
                        });
                    }
                }

                const resultDiv = document.getElementById('csv_analysis');
                resultDiv.className = 'result success';
                resultDiv.innerHTML = resultHTML;
                resultDiv.style.display = 'block';
            };
            reader.readAsText(file);
        }

        function showColumnMapping() {
            const sourceTable = document.getElementById('source_table').value;
            const mappingDiv = document.getElementById('column_mapping');

            if (!sourceTable) {
                mappingDiv.style.display = 'none';
                return;
            }

            // عرض نموذج تطابق الأعمدة
            let mappingHTML = '<table style="width:100%;">';
            mappingHTML += '<tr><th>العمود القديم</th><th>العمود الجديد</th><th>تحويل</th></tr>';

            if (sourceTable === 'cvs') {
                const cvColumns = ['id', 'cvs_name', 'national_id', 'religion_id', 'age_id', 'experience', 'cv_status'];
                const maidColumns = ['id', 'name', 'nationality_id', 'religion_id', 'age', 'experience_years', 'available', 'status'];

                cvColumns.forEach((oldCol, index) => {
                    mappingHTML += `<tr>`;
                    mappingHTML += `<td><strong>${oldCol}</strong></td>`;
                    mappingHTML += `<td><select id="map_${oldCol}">`;
                    mappingHTML += `<option value="">-- اختر --</option>`;
                    maidColumns.forEach(newCol => {
                        const selected = index < maidColumns.length && maidColumns[index] === newCol ? 'selected' : '';
                        mappingHTML += `<option value="${newCol}" ${selected}>${newCol}</option>`;
                    });
                    mappingHTML += `</select></td>`;
                    mappingHTML += `<td><input type="text" id="transform_${oldCol}" placeholder="تحويل (اختياري)" style="width:100%;"></td>`;
                    mappingHTML += `</tr>`;
                });
            }

            mappingHTML += '</table>';

            document.getElementById('mapping_form').innerHTML = mappingHTML;
            mappingDiv.style.display = 'block';
        }

        function saveColumnMapping() {
            const sourceTable = document.getElementById('source_table').value;
            migrationState.columnMappings[sourceTable] = {};

            // حفظ التطابق
            const selects = document.querySelectorAll('[id^="map_"]');
            selects.forEach(select => {
                const oldColumn = select.id.replace('map_', '');
                const newColumn = select.value;
                const transform = document.getElementById(`transform_${oldColumn}`).value;

                if (newColumn) {
                    migrationState.columnMappings[sourceTable][oldColumn] = {
                        target: newColumn,
                        transform: transform || null
                    };
                }
            });

            alert('تم حفظ تطابق الأعمدة بنجاح!');
        }

        async function startBatchMigration() {
            // تنفيذ النقل التدريجي
            alert('سيتم تنفيذ النقل التدريجي...');
        }

        function pauseMigration() {
            migrationState.isPaused = !migrationState.isPaused;
            const btn = event.target;
            btn.textContent = migrationState.isPaused ? 'استئناف' : 'إيقاف مؤقت';
        }

        async function verifyMigration() {
            alert('فحص النتيجة النهائية...');
        }

        async function generateReport() {
            alert('إنشاء تقرير مفصل...');
        }

        // تشغيل تلقائي
        window.addEventListener('load', function() {
            setTimeout(checkDatabaseStatus, 1000);
        });
    </script>
</body>
</html>
