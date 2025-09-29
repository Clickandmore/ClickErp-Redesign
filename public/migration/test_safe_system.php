<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>اختبار النظام الآمن</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f8f9fa;
        }

        .test {
            background: white;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }

        .result {
            background: #f8f9fa;
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            font-family: monospace;
            white-space: pre-wrap;
        }

        .success { border-color: #28a745; background: #d4edda; }
        .error { border-color: #dc3545; background: #f8d7da; }

        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }

        button:hover { background: #0056b3; }
        button:disabled { background: #6c757d; cursor: not-allowed; }
    </style>
</head>
<body>
    <h1>🧪 اختبار النظام الآمن</h1>

    <div class="test">
        <h3>1️⃣ اختبار فحص قواعد البيانات</h3>
        <button onclick="testDatabaseStatus()">فحص الاتصال</button>
        <div id="test1" class="result" style="display:none;"></div>
    </div>

    <div class="test">
        <h3>2️⃣ اختبار تحليل جدول CVs</h3>
        <button onclick="testAnalyzeTable()">تحليل CVs</button>
        <div id="test2" class="result" style="display:none;"></div>
    </div>

    <div class="test">
        <h3>3️⃣ اختبار تصدير جدول</h3>
        <button onclick="testExportTable()">تصدير CVs (عينة)</button>
        <div id="test3" class="result" style="display:none;"></div>
    </div>

    <div class="test">
        <h3>4️⃣ اختبار JSON Parser</h3>
        <button onclick="testJSONParser()">اختبار تحليل JSON</button>
        <div id="test4" class="result" style="display:none;"></div>
    </div>

    <script>
        // معالج JSON آمن (نفس المستخدم في النظام الرئيسي)
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

        async function testDatabaseStatus() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري الاختبار...';

            try {
                const data = await safeFetch('safe_migration_backend.php?action=check_status');

                let resultHTML = '✅ اختبار فحص قواعد البيانات:\n\n';
                resultHTML += JSON.stringify(data, null, 2);

                const resultDiv = document.getElementById('test1');
                resultDiv.className = 'result ' + (data.success ? 'success' : 'error');
                resultDiv.innerHTML = resultHTML;
                resultDiv.style.display = 'block';

            } catch (error) {
                const resultDiv = document.getElementById('test1');
                resultDiv.className = 'result error';
                resultDiv.innerHTML = '❌ فشل الاختبار:\n' + error.message;
                resultDiv.style.display = 'block';
            }

            btn.disabled = false;
            btn.textContent = 'فحص الاتصال';
        }

        async function testAnalyzeTable() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري الاختبار...';

            try {
                const data = await safeFetch('safe_migration_backend.php?action=analyze_table&table=cvs');

                let resultHTML = '✅ اختبار تحليل جدول CVs:\n\n';
                resultHTML += `العدد: ${data.count}\n`;
                resultHTML += `الأعمدة: ${data.columns.length}\n\n`;
                resultHTML += 'أول 5 أعمدة:\n';
                data.columns.slice(0, 5).forEach((col, index) => {
                    resultHTML += `${index + 1}. ${col}\n`;
                });

                const resultDiv = document.getElementById('test2');
                resultDiv.className = 'result ' + (data.success ? 'success' : 'error');
                resultDiv.innerHTML = resultHTML;
                resultDiv.style.display = 'block';

            } catch (error) {
                const resultDiv = document.getElementById('test2');
                resultDiv.className = 'result error';
                resultDiv.innerHTML = '❌ فشل الاختبار:\n' + error.message;
                resultDiv.style.display = 'block';
            }

            btn.disabled = false;
            btn.textContent = 'تحليل CVs';
        }

        async function testExportTable() {
            alert('⚠️ تنبيه: هذا سيقوم بتصدير ملف CSV فعلي.\nالمتابعة؟');

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري التصدير...';

            try {
                const data = await safeFetch('safe_migration_backend.php?action=export_table&table=cvs');

                let resultHTML = '✅ اختبار التصدير:\n\n';
                resultHTML += `السجلات المصدرة: ${data.exported}\n`;
                resultHTML += `اسم الملف: ${data.filename}\n`;
                resultHTML += `حجم الملف: ${data.filesize}\n`;

                const resultDiv = document.getElementById('test3');
                resultDiv.className = 'result ' + (data.success ? 'success' : 'error');
                resultDiv.innerHTML = resultHTML;
                resultDiv.style.display = 'block';

            } catch (error) {
                const resultDiv = document.getElementById('test3');
                resultDiv.className = 'result error';
                resultDiv.innerHTML = '❌ فشل الاختبار:\n' + error.message;
                resultDiv.style.display = 'block';
            }

            btn.disabled = false;
            btn.textContent = 'تصدير CVs (عينة)';
        }

        async function testJSONParser() {
            const btn = event.target;
            btn.disabled = true;

            // اختبار معالج JSON مع نصوص مختلفة
            const testCases = [
                '{"success": true, "message": "تمام"}',
                'Warning: MySQL stuff\n{"success": true}',
                '<br>Notice: Something<br>{"success": false, "error": "خطأ"}',
                'HTML content<div>{"success": true}</div>more html'
            ];

            let resultHTML = '🧪 اختبار معالج JSON:\n\n';

            testCases.forEach((testCase, index) => {
                try {
                    // محاولة معالجة النص
                    let cleanText = testCase;
                    cleanText = cleanText.replace(/(<br\s*\/?>|<[^>]*>)/gi, '');
                    cleanText = cleanText.replace(/Warning:[^\n]*/gi, '');
                    cleanText = cleanText.replace(/Notice:[^\n]*/gi, '');

                    const jsonMatch = cleanText.match(/\{.*\}/s);
                    if (jsonMatch) {
                        cleanText = jsonMatch[0];
                    }

                    const parsed = JSON.parse(cleanText);
                    resultHTML += `✅ اختبار ${index + 1}: نجح\n`;
                    resultHTML += `   النص الأصلي: ${testCase.substring(0, 50)}...\n`;
                    resultHTML += `   النتيجة: ${JSON.stringify(parsed)}\n\n`;
                } catch (e) {
                    resultHTML += `❌ اختبار ${index + 1}: فشل - ${e.message}\n\n`;
                }
            });

            const resultDiv = document.getElementById('test4');
            resultDiv.className = 'result success';
            resultDiv.innerHTML = resultHTML;
            resultDiv.style.display = 'block';

            btn.disabled = false;
            btn.textContent = 'اختبار تحليل JSON';
        }

        // تشغيل تلقائي لاختبار الاتصال
        window.addEventListener('load', function() {
            setTimeout(testDatabaseStatus, 500);
        });
    </script>
</body>
</html>
