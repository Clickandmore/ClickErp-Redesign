<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>نظام نقل البيانات</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h1 {
            color: #333;
            text-align: center;
            border-bottom: 3px solid #28a745;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }

        .section {
            margin: 30px 0;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 8px;
            border-right: 5px solid #007bff;
        }

        h3 {
            margin-top: 0;
            color: #007bff;
        }

        .buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin: 20px 0;
        }

        button {
            padding: 12px 25px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            color: white;
            font-weight: bold;
        }

        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }

        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }

        .btn-info { background: #17a2b8; }
        .btn-info:hover { background: #138496; }

        button:disabled {
            background: #6c757d !important;
            cursor: not-allowed;
        }

        .result {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
            font-family: monospace;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
            display: none;
        }

        .result.success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }

        .result.error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .stat {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #dee2e6;
        }

        .stat .number {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
        }

        .stat .label {
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>نظام نقل البيانات</h1>

        <!-- إشارة للأنظمة البديلة الناجحة -->
        <a href="/migration/new_migration_system.php" style="color: #ff6b6b; font-weight: bold; font-size: 1.1em;">
                        نظام النقل المحسن
                    </a><br>

        <!-- قسم المستخدمين -->
        <div class="section">
            <h3>1️⃣ نقل المستخدمين والعملاء</h3>

            <!-- خيارات النقل -->
            <div style="margin: 20px 0; padding: 15px; background: white; border-radius: 8px;">
                <label style="display: block; margin-bottom: 15px;">
                    <input type="radio" name="transfer_mode" value="auto" checked onchange="toggleUploadSection()">
                    نقل تلقائي من القاعدة القديمة
                </label>
                <label style="display: block;">
                    <input type="radio" name="transfer_mode" value="file" onchange="toggleUploadSection()">
                    رفع ملف CSV
                </label>

                <!-- قسم رفع الملف -->
                <div id="upload_section" style="display: none; margin-top: 15px; padding: 15px; background: #f0f8ff; border-radius: 5px;">
                    <input type="file" id="csv_file" accept=".csv" style="margin-bottom: 10px;">
                    <div style="font-size: 12px; color: #666;">
                        ملاحظة: يجب أن يحتوي الملف على الأعمدة: id, name, phone, email, country_id
                    </div>
                </div>
            </div>

            <div class="buttons">
                <button onclick="prepareUsersTable()" class="btn-info">إعداد الجدول</button>
                <button onclick="transferUsersSimple()" class="btn-success">نقل المستخدمين</button>
                <button onclick="transferCustomersSimple()" class="btn-success">دمج العملاء</button>
                <button onclick="checkUsers()" class="btn-info">فحص النتيجة</button>
            </div>
            <div id="users_result" class="result"></div>
        </div>

        <!-- قسم البلدان -->
        <div class="section">
            <h3>2️⃣ نقل البلدان</h3>
            <div class="buttons">
                <button onclick="transferCountries()" class="btn-success">نقل البلدان</button>
                <button onclick="checkCountries()" class="btn-info">فحص النتيجة</button>
            </div>
            <div id="countries_result" class="result"></div>
        </div>

        <!-- قسم الجنسيات -->
        <div class="section">
            <h3>3️⃣ نقل الجنسيات (النظام المتقدم)</h3>

            <div style="background: #fff3cd; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                <h6 style="color: #856404; margin-bottom: 10px;">📋 تطابق الأعمدة:</h6>
                <div style="font-size: 13px; color: #856404;">
                    <strong>من platinum.nationalities إلى:</strong><br>
                    • platinum_new.nationalities: id, flag_image→logo, salary→price, sponsor_salary→service_transportation_cost<br>
                    • platinum_new.nationalities_translations: name→title, sub_title (فارغ)<br>
                    <strong>أعمدة إضافية:</strong> code, key_comment, rank, key_code, status, apper_home, expected_duration, period
                </div>
            </div>

            <div class="buttons">
                <button onclick="analyzeNationalitiesStructure()" class="btn-info">تحليل البنية</button>
                <button onclick="transferNationalitiesAdvanced()" class="btn-success">نقل الجنسيات المتقدم</button>
                <button onclick="reorderNationalitiesColumns()" class="btn-info" style="background: #6f42c1;">ترتيب الأعمدة</button>
                <button onclick="checkNationalities()" class="btn-info">فحص النتيجة</button>
            </div>
            <div id="nationalities_result" class="result"></div>
        </div>

        <!-- قسم المهن (Job Titles) -->
        <div class="section">
            <h3>4️⃣ نقل المهن (Job Titles)</h3>
            <div class="buttons">
                <button onclick="transferJobTitles()" class="btn-success">نقل المهن</button>
                <button onclick="checkJobTitles()" class="btn-info">فحص النتيجة</button>
            </div>
            <div id="jobs_result" class="result"></div>
        </div>

        <!-- قسم الأديان (Religions) -->
        <div class="section">
            <h3>5️⃣ نقل الأديان (Religions)</h3>
            <div class="buttons">
                <button onclick="transferReligions()" class="btn-success">نقل الأديان</button>
                <button onclick="checkReligions()" class="btn-info">فحص النتيجة</button>
            </div>
            <div id="religions_result" class="result"></div>
        </div>

        <!-- قسم الحالات الاجتماعية (Social Status) -->
        <div class="section">
            <h3>6️⃣ نقل الحالات الاجتماعية (Social Status)</h3>
            <div class="buttons">
                <button onclick="transferSocialStatus()" class="btn-success">نقل الحالات الاجتماعية</button>
                <button onclick="checkSocialStatus()" class="btn-info">فحص النتيجة</button>
            </div>
            <div id="social_status_result" class="result"></div>
        </div>

        <!-- قسم المهارات (Skills) -->
        <div class="section">
            <h3>7️⃣ نقل المهارات (Skills)</h3>
            <div class="buttons">
                <button onclick="transferSkills()" class="btn-success">نقل المهارات</button>
                <button onclick="checkSkills()" class="btn-info">فحص النتيجة</button>
            </div>
            <div id="skills_result" class="result"></div>
        </div>

        <!-- قسم الخبرات (Experience) -->
        <div class="section">
            <h3>8️⃣ نقل الخبرات (Experience)</h3>
            <div class="buttons">
                <button onclick="transferExperience()" class="btn-success">نقل الخبرات</button>
                <button onclick="checkExperience()" class="btn-info">فحص النتيجة</button>
            </div>
            <div id="experience_result" class="result"></div>
        </div>

        <!-- قسم الأعمار (Ages) -->
        <div class="section">
            <h3>9️⃣ نقل الأعمار (Ages)</h3>
            <div class="buttons">
                <button onclick="transferAges()" class="btn-success">نقل الأعمار</button>
                <button onclick="checkAges()" class="btn-info">فحص النتيجة</button>
            </div>
            <div id="ages_result" class="result"></div>
        </div>

        <!-- قسم اللغات (Languages) -->
        <div class="section">
            <h3>🔟 نقل اللغات (Languages)</h3>
            <div class="buttons">
                <button onclick="transferLanguages()" class="btn-success">نقل اللغات</button>
                <button onclick="checkLanguages()" class="btn-info">فحص النتيجة</button>
            </div>
            <div id="languages_result" class="result"></div>
        </div>

        <!-- قسم السير الذاتية -->
        <div class="section">
            <h3>1️⃣1️⃣ نقل السير الذاتية (CVs → Maids)</h3>
            <div class="buttons">
                <button onclick="transferMaids()" class="btn-success">نقل السير الذاتية</button>
                <button onclick="checkMaids()" class="btn-info">فحص النتيجة</button>
            </div>
            <div id="maids_result" class="result"></div>
        </div>

        <!-- قسم العقود (Contracts) -->
        <div class="section">
            <h3>1️⃣2️⃣ نقل العقود (Contracts)</h3>
            <div class="buttons">
                <button onclick="transferContracts()" class="btn-success">نقل العقود</button>
                <button onclick="checkContracts()" class="btn-info">فحص النتيجة</button>
            </div>
            <div id="contracts_result" class="result"></div>
        </div>

        <!-- قسم أنواع الإيواء (Accommodation Types) -->
        <div class="section">
            <h3>1️⃣3️⃣ أنواع الإيواء (Accommodation Types)</h3>
            <div class="buttons">
                <button onclick="transferAccommodationTypes()" class="btn-success">نقل أنواع الإيواء</button>
                <button onclick="checkAccommodationTypes()" class="btn-info">فحص النتيجة</button>
            </div>
            <div id="accommodation_types_result" class="result"></div>
        </div>

        <!-- Airports -->
        <div class="section">
            <h3>Airports</h3>
            <div class="buttons">
                <button onclick="transferAirports()" class="btn-success">Transfer Airports</button>
                <button onclick="checkAirports()" class="btn-info">Check Airports</button>
            </div>
            <div id="airports_result" class="result"></div>
        </div
        <!-- الإحصائيات -->
        <div class="section">
            <h3>📊 الإحصائيات الحالية</h3>
            <div class="stats" id="stats">
                <div class="stat">
                    <div class="number">-</div>
                    <div class="label">Users</div>
                </div>
                <div class="stat">
                    <div class="number">-</div>
                    <div class="label">Countries</div>
                </div>
                <div class="stat">
                    <div class="number">-</div>
                    <div class="label">Nationalities</div>
                </div>
                <div class="stat">
                    <div class="number">-</div>
                    <div class="label">Translations</div>
                </div>
                <div class="stat">
                    <div class="number">-</div>
                    <div class="label">Job Titles</div>
                </div>
                <div class="stat">
                    <div class="number">-</div>
                    <div class="label">Religions</div>
                </div>
                <div class="stat">
                    <div class="number">-</div>
                    <div class="label">Social Status</div>
                </div>
                <div class="stat">
                    <div class="number">-</div>
                    <div class="label">Skills</div>
                </div>
                <div class="stat">
                    <div class="number">-</div>
                    <div class="label">Experience</div>
                </div>
                <div class="stat">
                    <div class="number">-</div>
                    <div class="label">Ages</div>
                </div>
                <div class="stat">
                    <div class="number">-</div>
                    <div class="label">Languages</div>
                </div>
                <div class="stat">
                    <div class="number">-</div>
                    <div class="label">Maids</div>
                </div>
                <div class="stat">
                    <div class="number">-</div>
                    <div class="label">Contracts</div>
                </div>
                <div class="stat">
                    <div class="number">-</div>
                    <div class="label">Contract Infos</div>
                </div>
                <div class="stat">
                    <div class="number">-</div>
                    <div class="label">Airports</div>
                </div>
                <div class="stat">
                    <div class="number">-</div>
                    <div class="label">Accommodation Types</div>
                </div>
            </div>
            <button onclick="updateStats()" class="btn-info">تحديث الإحصائيات</button>
        </div>
    </div>

    <script>
        // Toggle upload section
        function toggleUploadSection() {
            const mode = document.querySelector('input[name="transfer_mode"]:checked').value;
            document.getElementById('upload_section').style.display = mode === 'file' ? 'block' : 'none';
        }
        
        // إعداد جدول المستخدمين
        function prepareUsersTable() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري الإعداد...';

            fetch('migration/file_processor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=add_system_status_columns'
            })
            .then(response => response.text())
            .then(result => {
                try {
                    const data = JSON.parse(result);
                    showResult('users_result', data.success ? '✅ تم إعداد الجدول' : '❌ ' + data.message, data.success);
                } catch {
                    showResult('users_result', result, false);
                }
                btn.disabled = false;
                btn.textContent = 'إعداد الجدول';
            });
        }

        // نقل المستخدمين (النظام الناجح من simple_migration.php)
        async function transferUsersSimple() {
            const mode = document.querySelector('input[name="transfer_mode"]:checked').value;
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل...';

            try {
                let body = 'action=migrate_users_simple';

                if (mode === 'file') {
                    const file = document.getElementById('csv_file').files[0];
                    if (!file) {
                        alert('الرجاء اختيار ملف CSV');
                        btn.disabled = false;
                        btn.textContent = 'نقل المستخدمين';
                        return;
                    }

                    const formData = new FormData();
                    formData.append('action', 'process_files');
                    formData.append('table_type', 'users');
                    formData.append('file', file);

                    const response = await fetch('migration/file_processor.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();
                    showResult('users_result', data.success ? `✅ تم نقل ${data.processed || 0} مستخدم من الملف` : '❌ ' + data.message, data.success);
                } else {
                    const response = await fetch('migration/file_processor.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: body
                    });

                    const data = await response.json();

                    if (data.success) {
                        showResult('users_result', `✅ ${data.message}\nإجمالي: ${data.total_users || 0} | جديد: ${data.migrated || 0} | محدث: ${data.updated || 0}`, true);
                    } else {
                        showResult('users_result', '❌ ' + data.message, false);
                    }
                }

                updateStats();
            } catch (error) {
                showResult('users_result', '❌ خطأ: ' + error.message, false);
            }

            btn.disabled = false;
            btn.textContent = 'نقل المستخدمين';
        }

        // دمج العملاء (النظام الناجح من simple_migration.php)
        async function transferCustomersSimple() {
            const mode = document.querySelector('input[name="transfer_mode"]:checked').value;
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري الدمج...';

            try {
                let body = 'action=migrate_customers_simple';

                if (mode === 'file') {
                    const file = document.getElementById('csv_file').files[0];
                    if (!file) {
                        // إذا لم يكن هناك ملف، نستخدم النقل التلقائي
                        mode = 'auto';
                    } else {
                        const formData = new FormData();
                        formData.append('action', 'process_files');
                        formData.append('table_type', 'customers');
                        formData.append('file', file);

                        const response = await fetch('migration/file_processor.php', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();
                        showResult('users_result', data.success ? `✅ تم دمج ${data.processed || 0} عميل من الملف` : '❌ ' + data.message, data.success);
                        updateStats();
                        btn.disabled = false;
                        btn.textContent = 'دمج العملاء';
                        return;
                    }
                }

                const response = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: body
                });

                const data = await response.json();

                if (data.success) {
                    showResult('users_result', `✅ ${data.message}\n${data.updated_users ? 'محدث: ' + data.updated_users + ' مستخدم' : ''}`, true);
                } else {
                    showResult('users_result', '❌ ' + data.message, false);
                }

                updateStats();
            } catch (error) {
                showResult('users_result', '❌ خطأ: ' + error.message, false);
            }

            btn.disabled = false;
            btn.textContent = 'دمج العملاء';
        }

        function checkUsers() {
            fetch('migration/file_processor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=execute_sql&sql=' + encodeURIComponent('SELECT COUNT(*) as count FROM users')
            })
            .then(response => response.text())
            .then(result => {
                try {
                    const data = JSON.parse(result);
                    showResult('users_result', `📊 عدد المستخدمين: ${data.result[0].count}`, true);
                } catch {
                    showResult('users_result', result, true);
                }
            });
        }

        // وظائف البلدان
        function clearCountries() {
            if (!confirm('⚠️ هل أنت متأكد من مسح جدول Countries في القاعدة الجديدة؟')) return;

            fetch('migration/file_processor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=execute_sql&sql=' + encodeURIComponent('DELETE FROM countries')
            })
            .then(response => response.text())
            .then(result => {
                showResult('countries_result', 'تم مسح جدول Countries', true);
            });
        }

        function transferCountries() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل...';

            fetch('migration/file_processor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=exportAndUpload&table=countries'
            })
            .then(response => response.text())
            .then(result => {
                try {
                    const data = JSON.parse(result);
                    showResult('countries_result', `✅ تم نقل ${data.processed || 0} دولة`, true);
                } catch {
                    showResult('countries_result', result, true);
                }
                btn.disabled = false;
                btn.textContent = 'نقل البلدان';
            });
        }

        function checkCountries() {
            fetch('migration/file_processor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=execute_sql&sql=' + encodeURIComponent('SELECT COUNT(*) as count FROM countries')
            })
            .then(response => response.text())
            .then(result => {
                try {
                    const data = JSON.parse(result);
                    showResult('countries_result', `📊 عدد البلدان: ${data.result[0].count}`, true);
                } catch {
                    showResult('countries_result', result, true);
                }
            });
        }

        // وظائف الجنسيات المتقدمة
        function analyzeNationalitiesStructure() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري التحليل...';

            fetch('migration/file_processor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=analyze_nationalities_structure'
            })
            .then(response => response.text())
            .then(result => {
                try {
                    const data = JSON.parse(result);
                    if (data.success) {
                        let analysis = `📊 تحليل البنية:\n`;
                        analysis += `القديم: ${data.old_count || 0} جنسية\n`;
                        analysis += `الجديد: ${data.new_count || 0} جنسية\n`;
                        analysis += `الترجمات: ${data.translations_count || 0}`;
                        showResult('nationalities_result', analysis, true);
                    } else {
                        showResult('nationalities_result', '❌ ' + data.message, false);
                    }
                } catch {
                    showResult('nationalities_result', result, false);
                }
                btn.disabled = false;
                btn.textContent = 'تحليل البنية';
            });
        }

        function clearNationalities() {
            if (!confirm('⚠️ هل أنت متأكد من مسح جداول Nationalities في القاعدة الجديدة؟\n\nسيتم مسح:\n- nationalities\n- nationalities_translations')) return;

            fetch('migration/file_processor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=execute_sql&sql=' + encodeURIComponent('SET FOREIGN_KEY_CHECKS=0; DELETE FROM nationalities_translations; DELETE FROM nationalities; SET FOREIGN_KEY_CHECKS=1;')
            })
            .then(response => response.text())
            .then(result => {
                showResult('nationalities_result', 'تم مسح جداول Nationalities', true);
                updateStats();
            });
        }

        function transferNationalitiesAdvanced() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل المتقدم...';

            if (!confirm('🚀 التصدير والرفع التلقائي لجدول nationalities؟\n\nسيتم:\n✅ تصدير من القاعدة القديمة\n✅ معالجة البيانات تلقائياً\n✅ رفع للقاعدة الجديدة\n\nالمتابعة؟')) {
                btn.disabled = false;
                btn.textContent = 'نقل الجنسيات المتقدم';
                return;
            }

            try {
                // خطوة 1: التصدير من القاعدة القديمة
                btn.textContent = 'جاري التصدير...';

                const exportParams = {
                    action: 'export_from_old_db',
                    export_table: 'nationalities',
                    old_db_host: '127.0.0.1',
                    old_db_port: '8889',
                    old_db_name: 'platinum',
                    old_db_user: 'root',
                    old_db_pass: 'root',
                    db_host: '127.0.0.1',
                    db_port: '8889',
                    db_name: 'platinum_new',
                    db_user: 'root',
                    db_pass: 'root'
                };

                const exportResponse = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(exportParams)
                });

                const exportData = await exportResponse.json();

                if (!exportData.success) {
                    throw new Error('فشل التصدير: ' + exportData.message);
                }

                // خطوة 2: معالجة ورفع البيانات
                btn.textContent = 'جاري المعالجة والرفع...';

                const processParams = {
                    action: 'process_exported_file',
                    exported_file: exportData.file_name,
                    table_type: 'nationalities',
                    db_host: '127.0.0.1',
                    db_port: '8889',
                    db_name: 'platinum_new',
                    db_user: 'root',
                    db_pass: 'root'
                };

                const processResponse = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(processParams)
                });

                const processData = await processResponse.json();

                if (processData.success) {
                    let message = `✅ عملية تلقائية ناجحة!\n`;
                    message += `📤 تم تصدير: ${exportData.record_count} سجل\n`;
                    message += `📥 ${processData.message}\n`;
                    message += `📁 الملف: ${exportData.file_name}\n`;
                    message += `💾 الحجم: ${exportData.file_size}`;

                    // إضافة تفاصيل الترجمات إذا كانت متاحة
                    if (processData.details && processData.details.translations_created) {
                        message += `\n🌐 ترجمات: ${processData.details.translations_created}`;
                    }

                    showResult('nationalities_result', message, true);
                    updateStats();
                } else {
                    let message = `⚠️ نجح التصدير لكن فشل الرفع\n`;
                    message += `تم تصدير: ${exportData.record_count} سجل بنجاح\n`;
                    message += `مشكلة الرفع: ${processData.message}`;
                    showResult('nationalities_result', message, false);
                }

            } catch (error) {
                showResult('nationalities_result', '❌ خطأ: ' + error.message, false);
            }

            btn.disabled = false;
            btn.textContent = 'نقل الجنسيات المتقدم';
        }

        function reorderNationalitiesColumns() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري الترتيب...';

            if (!confirm('ترتيب أعمدة جدول nationalities؟\n\nسيتم:\n✅ جعل created_at و updated_at آخر عمودين\n✅ ترتيب باقي الأعمدة منطقياً\n\nالمتابعة؟')) {
                btn.disabled = false;
                btn.textContent = 'ترتيب الأعمدة';
                return;
            }

            fetch('migration/file_processor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=reorder_nationalities_table'
            })
            .then(response => response.text())
            .then(result => {
                try {
                    const data = JSON.parse(result);
                    if (data.success) {
                        showResult('nationalities_result', `✅ ${data.message}\n📋 ترتيب الأعمدة: ${data.columns_order || 'تم'}`, true);
                    } else {
                        showResult('nationalities_result', '❌ ' + data.message, false);
                    }
                } catch {
                    showResult('nationalities_result', result, false);
                }
                btn.disabled = false;
                btn.textContent = 'ترتيب الأعمدة';
            });
        }

        function checkNationalities() {
            fetch('migration/file_processor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=execute_sql&sql=' + encodeURIComponent('SELECT COUNT(*) as count FROM nationalities')
            })
            .then(response => response.text())
            .then(result => {
                try {
                    const data = JSON.parse(result);
                    showResult('nationalities_result', `📊 عدد الجنسيات: ${data.result[0].count}`, true);
                } catch {
                    showResult('nationalities_result', result, true);
                }
            });
        }

        // وظائف المهن Job Titles
        async function transferJobTitles() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري التصدير...';

            try {
                // 1) تصدير من الجدول القديم recruitment_form_occupation
                const exportParams = {
                    action: 'export_from_old_db',
                    export_table: 'recruitment_form_occupation',
                    old_db_host: '127.0.0.1',
                    old_db_port: '8889',
                    old_db_name: 'platinum',
                    old_db_user: 'root',
                    old_db_pass: 'root'
                };

                const exportResponse = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(exportParams)
                });
                const exportData = await exportResponse.json();
                if (!exportData.success) throw new Error(exportData.message || 'فشل التصدير');

                // 2) معالجة ورفع للجدولين job_titles و job_titles_translations
                btn.textContent = 'جاري المعالجة والرفع...';
                const processParams = {
                    action: 'process_exported_file',
                    exported_file: exportData.file_name,
                    table_type: 'job_titles'
                };
                const processResponse = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(processParams)
                });
                const processData = await processResponse.json();

                if (processData.success) {
                    showResult('jobs_result', `✅ تم نقل ${processData.processed || 0} مهنة مع الترجمات`, true);
                } else {
                    showResult('jobs_result', `❌ ${processData.message}`, false);
                }
            } catch (e) {
                showResult('jobs_result', `❌ خطأ: ${e.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = 'نقل المهن';
        }

        function checkJobTitles() {
            fetch('migration/file_processor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=execute_sql&sql=' + encodeURIComponent('SELECT (SELECT COUNT(*) FROM job_titles) jt, (SELECT COUNT(*) FROM job_titles_translations) tr')
            })
            .then(r=>r.json())
            .then(data=>{
                if (data.success && data.result && data.result[0]) {
                    const row = data.result[0];
                    showResult('jobs_result', `📊 Job Titles: ${row.jt || 0} | Translations: ${row.tr || 0}`, true);
                } else {
                    showResult('jobs_result', '❌ فشل الفحص', false);
                }
            })
            .catch(e=>showResult('jobs_result', '❌ ' + e.message, false));
        }

        // وظائف الأديان Religions
        async function transferReligions() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري التصدير...';

            try {
                // 1) تصدير من الجدول القديم recruitment_form_religions
                const exportParams = {
                    action: 'export_from_old_db',
                    export_table: 'recruitment_form_religions',
                    old_db_host: '127.0.0.1',
                    old_db_port: '8889',
                    old_db_name: 'platinum',
                    old_db_user: 'root',
                    old_db_pass: 'root'
                };

                const exportResponse = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(exportParams)
                });
                const exportData = await exportResponse.json();
                if (!exportData.success) throw new Error(exportData.message || 'فشل التصدير');

                // 2) معالجة ورفع للجدولين religions و religions_translations
                btn.textContent = 'جاري المعالجة والرفع...';
                const processParams = {
                    action: 'process_exported_file',
                    exported_file: exportData.file_name,
                    table_type: 'religions'
                };
                const processResponse = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(processParams)
                });
                const processData = await processResponse.json();

                if (processData.success) {
                    showResult('religions_result', `✅ تم نقل ${processData.processed || 0} ديانة مع الترجمات`, true);
                    updateStats();
                } else {
                    showResult('religions_result', `❌ ${processData.message}`, false);
                }
            } catch (e) {
                showResult('religions_result', `❌ خطأ: ${e.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = 'نقل الأديان';
        }

        function checkReligions() {
            fetch('migration/file_processor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=execute_sql&sql=' + encodeURIComponent('SELECT (SELECT COUNT(*) FROM religions) rl, (SELECT COUNT(*) FROM religions_translations) tr')
            })
            .then(r=>r.json())
            .then(data=>{
                if (data.success && data.result && data.result[0]) {
                    const row = data.result[0];
                    showResult('religions_result', `📊 Religions: ${row.rl || 0} | Translations: ${row.tr || 0}`, true);
                } else {
                    showResult('religions_result', '❌ فشل الفحص', false);
                }
            })
            .catch(e=>showResult('religions_result', '❌ ' + e.message, false));
        }

        // وظائف الحالات الاجتماعية Social Status
        async function transferSocialStatus() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري التصدير...';

            try {
                // 1) تصدير من الجدول القديم recruitment_form_social_status
                const exportParams = {
                    action: 'export_from_old_db',
                    export_table: 'recruitment_form_social_status',
                    old_db_host: '127.0.0.1',
                    old_db_port: '8889',
                    old_db_name: 'platinum',
                    old_db_user: 'root',
                    old_db_pass: 'root'
                };

                const exportResponse = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(exportParams)
                });
                const exportData = await exportResponse.json();
                if (!exportData.success) throw new Error(exportData.message || 'فشل التصدير');

                // 2) معالجة ورفع للجدولين social_status و social_status_translations
                btn.textContent = 'جاري المعالجة والرفع...';
                const processParams = {
                    action: 'process_exported_file',
                    exported_file: exportData.file_name,
                    table_type: 'social_status'
                };
                const processResponse = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(processParams)
                });
                const processData = await processResponse.json();

                if (processData.success) {
                    showResult('social_status_result', `✅ تم نقل ${processData.processed || 0} حالة اجتماعية مع الترجمات`, true);
                    updateStats();
                } else {
                    showResult('social_status_result', `❌ ${processData.message}`, false);
                }
            } catch (e) {
                showResult('social_status_result', `❌ خطأ: ${e.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = 'نقل الحالات الاجتماعية';
        }

        function checkSocialStatus() {
            fetch('migration/file_processor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=execute_sql&sql=' + encodeURIComponent('SELECT (SELECT COUNT(*) FROM social_status) ss, (SELECT COUNT(*) FROM social_status_translations) tr')
            })
            .then(r=>r.json())
            .then(data=>{
                if (data.success && data.result && data.result[0]) {
                    const row = data.result[0];
                    showResult('social_status_result', `📊 Social Status: ${row.ss || 0} | Translations: ${row.tr || 0}`, true);
                } else {
                    showResult('social_status_result', '❌ فشل الفحص', false);
                }
            })
            .catch(e=>showResult('social_status_result', '❌ ' + e.message, false));
        }

        // وظائف المهارات Skills
        async function transferSkills() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري التصدير...';

            try {
                // 1) تصدير من الجدول القديم skills
                const exportParams = {
                    action: 'export_from_old_db',
                    export_table: 'skills',
                    old_db_host: '127.0.0.1',
                    old_db_port: '8889',
                    old_db_name: 'platinum',
                    old_db_user: 'root',
                    old_db_pass: 'root'
                };

                const exportResponse = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(exportParams)
                });
                const exportData = await exportResponse.json();
                if (!exportData.success) throw new Error(exportData.message || 'فشل التصدير');

                // 2) معالجة ورفع للجدولين skills و skills_translations
                btn.textContent = 'جاري المعالجة والرفع...';
                const processParams = {
                    action: 'process_exported_file',
                    exported_file: exportData.file_name,
                    table_type: 'skills'
                };
                const processResponse = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(processParams)
                });
                const processData = await processResponse.json();

                if (processData.success) {
                    showResult('skills_result', `✅ تم نقل ${processData.processed || 0} مهارة مع الترجمات`, true);
                    updateStats();
                } else {
                    showResult('skills_result', `❌ ${processData.message}`, false);
                }
            } catch (e) {
                showResult('skills_result', `❌ خطأ: ${e.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = 'نقل المهارات';
        }

        function checkSkills() {
            fetch('migration/file_processor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=execute_sql&sql=' + encodeURIComponent('SELECT (SELECT COUNT(*) FROM skills) sk, (SELECT COUNT(*) FROM skills_translations) tr')
            })
            .then(r=>r.json())
            .then(data=>{
                if (data.success && data.result && data.result[0]) {
                    const row = data.result[0];
                    showResult('skills_result', `📊 Skills: ${row.sk || 0} | Translations: ${row.tr || 0}`, true);
                } else {
                    showResult('skills_result', '❌ فشل الفحص', false);
                }
            })
            .catch(e=>showResult('skills_result', '❌ ' + e.message, false));
        }

        // وظائف الخبرات Experience
        async function transferExperience() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري التصدير...';

            try {
                const exportParams = {
                    action: 'export_from_old_db',
                    export_table: 'recruitment_form_experience',
                    old_db_host: '127.0.0.1',
                    old_db_port: '8889',
                    old_db_name: 'platinum',
                    old_db_user: 'root',
                    old_db_pass: 'root'
                };

                const exportResponse = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(exportParams)
                });
                const exportData = await exportResponse.json();
                if (!exportData.success) throw new Error(exportData.message || 'فشل التصدير');

                btn.textContent = 'جاري المعالجة والرفع...';
                const processParams = {
                    action: 'process_exported_file',
                    exported_file: exportData.file_name,
                    table_type: 'experience'
                };
                const processResponse = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(processParams)
                });
                const processData = await processResponse.json();

                if (processData.success) {
                    showResult('experience_result', `✅ تم نقل ${processData.processed || 0} خبرة مع الترجمات`, true);
                    updateStats();
                } else {
                    showResult('experience_result', `❌ ${processData.message}`, false);
                }
            } catch (e) {
                showResult('experience_result', `❌ خطأ: ${e.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = 'نقل الخبرات';
        }

        function checkExperience() {
            fetch('migration/file_processor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=execute_sql&sql=' + encodeURIComponent('SELECT (SELECT COUNT(*) FROM experience) ex, (SELECT COUNT(*) FROM experience_translations) tr')
            })
            .then(r=>r.json())
            .then(data=>{
                if (data.success && data.result && data.result[0]) {
                    const row = data.result[0];
                    showResult('experience_result', `📊 Experience: ${row.ex || 0} | Translations: ${row.tr || 0}`, true);
                } else {
                    showResult('experience_result', '❌ فشل الفحص', false);
                }
            })
            .catch(e=>showResult('experience_result', '❌ ' + e.message, false));
        }

        // وظائف اللغات Languages
        async function transferLanguages() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري التصدير...';

            try {
                const exportParams = {
                    action: 'export_from_old_db',
                    export_table: 'recruitment_form_languages',
                    old_db_host: '127.0.0.1',
                    old_db_port: '8889',
                    old_db_name: 'platinum',
                    old_db_user: 'root',
                    old_db_pass: 'root'
                };

                const exportResponse = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(exportParams)
                });
                const exportData = await exportResponse.json();
                if (!exportData.success) throw new Error(exportData.message || 'فشل التصدير');

                btn.textContent = 'جاري المعالجة والرفع...';
                const processParams = {
                    action: 'process_exported_file',
                    exported_file: exportData.file_name,
                    table_type: 'languages'
                };
                const processResponse = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(processParams)
                });
                const processData = await processResponse.json();

                if (processData.success) {
                    showResult('languages_result', `✅ تم نقل ${processData.processed || 0} لغة مع الترجمات`, true);
                    updateStats();
                } else {
                    showResult('languages_result', `❌ ${processData.message}`, false);
                }
            } catch (e) {
                showResult('languages_result', `❌ خطأ: ${e.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = 'نقل اللغات';
        }

        function checkLanguages() {
            fetch('migration/file_processor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=execute_sql&sql=' + encodeURIComponent('SELECT (SELECT COUNT(*) FROM languages) lg, (SELECT COUNT(*) FROM languages_translations) tr')
            })
            .then(r=>r.json())
            .then(data=>{
                if (data.success && data.result && data.result[0]) {
                    const row = data.result[0];
                    showResult('languages_result', `📊 Languages: ${row.lg || 0} | Translations: ${row.tr || 0}`, true);
                } else {
                    showResult('languages_result', '❌ فشل الفحص', false);
                }
            })
            .catch(e=>showResult('languages_result', '❌ ' + e.message, false));
        }

        // وظائف الأعمار Ages
        async function transferAges() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري التصدير...';

            try {
                // 1) تصدير من الجدول القديم recruitment_form_ages
                const exportParams = {
                    action: 'export_from_old_db',
                    export_table: 'recruitment_form_ages',
                    old_db_host: '127.0.0.1',
                    old_db_port: '8889',
                    old_db_name: 'platinum',
                    old_db_user: 'root',
                    old_db_pass: 'root'
                };

                const exportResponse = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(exportParams)
                });
                const exportData = await exportResponse.json();
                if (!exportData.success) throw new Error(exportData.message || 'فشل التصدير');

                // 2) معالجة ورفع للجدولين ages و ages_translations
                btn.textContent = 'جاري المعالجة والرفع...';
                const processParams = {
                    action: 'process_exported_file',
                    exported_file: exportData.file_name,
                    table_type: 'ages'
                };
                const processResponse = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(processParams)
                });
                const processData = await processResponse.json();

                if (processData.success) {
                    showResult('ages_result', `✅ تم نقل ${processData.processed || 0} عمر مع الترجمات`, true);
                    updateStats();
                } else {
                    showResult('ages_result', `❌ ${processData.message}`, false);
                }
            } catch (e) {
                showResult('ages_result', `❌ خطأ: ${e.message}`, false);
            }

            btn.disabled = false;
            btn.textContent = 'نقل الأعمار';
        }

        function checkAges() {
            fetch('migration/file_processor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=execute_sql&sql=' + encodeURIComponent('SELECT (SELECT COUNT(*) FROM ages) ag, (SELECT COUNT(*) FROM ages_translations) tr')
            })
            .then(r=>r.json())
            .then(data=>{
                if (data.success && data.result && data.result[0]) {
                    const row = data.result[0];
                    showResult('ages_result', `📊 Ages: ${row.ag || 0} | Translations: ${row.tr || 0}`, true);
                } else {
                    showResult('ages_result', '❌ فشل الفحص', false);
                }
            })
            .catch(e=>showResult('ages_result', '❌ ' + e.message, false));
        }

        // وظائف السير الذاتية
        function clearMaids() {
            if (!confirm('⚠️ هل أنت متأكد من مسح جدول Maids في القاعدة الجديدة؟')) return;

            fetch('migration/file_processor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=execute_sql&sql=' + encodeURIComponent('DELETE FROM maids')
            })
            .then(response => response.text())
            .then(result => {
                showResult('maids_result', 'تم مسح جدول Maids', true);
            });
        }

        function transferMaids() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل...';

            fetch('migration/smart_structure_migration.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=migrate_maids_complete'
            })
            .then(response => response.text())
            .then(result => {
                try {
                    const data = JSON.parse(result);
                    showResult('maids_result', `✅ تم نقل ${data.processed || 0} سيرة ذاتية`, true);
                } catch {
                    showResult('maids_result', result, true);
                }
                btn.disabled = false;
                btn.textContent = 'نقل السير الذاتية';
            });
        }

        function checkMaids() {
            fetch('migration/file_processor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=execute_sql&sql=' + encodeURIComponent('SELECT COUNT(*) as count FROM maids')
            })
            .then(response => response.text())
            .then(result => {
                try {
                    const data = JSON.parse(result);
                    showResult('maids_result', `📊 عدد السير: ${data.result[0].count}`, true);
                } catch {
                    showResult('maids_result', result, true);
                }
            });
        }

        function transferContracts() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل...';

            fetch('migration/file_processor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=migrate_contracts'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showResult(
                        'contracts_result',
                        `✅ ${data.message}\n📦 الإجمالي: ${data.processed || 0}\n🆕 مضاف: ${data.inserted || 0}\n🔁 محدث: ${data.updated || 0}\n➖ بدون تغيير: ${data.unchanged || 0}\n⏭ متجاوز: ${data.skipped || 0}`,
                        true
                    );
                    updateStats();
                } else {
                    showResult('contracts_result', `❌ ${data.message}`, false);
                }
                btn.disabled = false;
                btn.textContent = 'نقل العقود';
            })
            .catch(error => {
                showResult('contracts_result', '❌ خطأ: ' + error.message, false);
                btn.disabled = false;
                btn.textContent = 'نقل العقود';
            });
        }

        function checkContracts() {
            fetch('migration/file_processor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=contracts_summary'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showResult('contracts_result', `📊 العقود: ${data.contracts || 0}
🗂️ معلومات العقود: ${data.contract_infos || 0}`, true);
                } else {
                    showResult('contracts_result', `❌ ${data.message}`, false);
                }
            })
            .catch(error => {
                showResult('contracts_result', '❌ خطأ: ' + error.message, false);
            });
        }

        function transferAccommodationTypes() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل...';

            fetch('migration/file_processor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=migrate_accommodation_types'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showResult('accommodation_types_result', `✅ ${data.message}
📦 الإجمالي: ${data.processed || 0}
🆕 مضاف: ${data.inserted || 0}
🔁 محدث: ${data.updated || 0}
➖ بدون تغيير: ${data.unchanged || 0}
⏭ متجاوز: ${data.skipped || 0}`, true);
                    updateStats();
                } else {
                    showResult('accommodation_types_result', `❌ ${data.message}`, false);
                }
                btn.disabled = false;
                btn.textContent = 'نقل أنواع الإيواء';
            })
            .catch(error => {
                showResult('accommodation_types_result', '❌ خطأ: ' + error.message, false);
                btn.disabled = false;
                btn.textContent = 'نقل أنواع الإيواء';
            });
        }

        function checkAccommodationTypes() {
            fetch('migration/file_processor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=accommodation_types_summary'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showResult('accommodation_types_result', `📊 أنواع الإيواء: ${data.types || 0}`, true);
                } else {
                    showResult('accommodation_types_result', `❌ ${data.message}`, false);
                }
            })
            .catch(error => {
                showResult('accommodation_types_result', '❌ خطأ: ' + error.message, false);
            });
        }


        function transferAirports() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'Transferring...';

            fetch('migration/file_processor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=migrate_airports'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const lines = [
                        'Airports migrated successfully',
                        'Total: ' + (data.processed ?? data.total ?? 0),
                        'Inserted: ' + (data.inserted ?? 0),
                        'Updated: ' + (data.updated ?? 0),
                        'Unchanged: ' + (data.unchanged ?? 0),
                        'Skipped: ' + (data.skipped ?? 0)
                    ];
                    showResult('airports_result', lines.join('\n'), true);
                    updateStats();
                } else {
                    showResult('airports_result', 'Migration failed: ' + (data.message || 'operation could not be completed'), false);
                }
            })
            .catch(error => {
                showResult('airports_result', 'Error: ' + error.message, false);
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = 'Transfer Airports';
            });
        }

        function checkAirports() {
            fetch('migration/file_processor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=airports_summary'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const lines = [
                        'Current airports stats:',
                        'Total: ' + (data.total ?? data.airports ?? 0)
                    ];
                    if (data.message) lines.push(data.message);
                    showResult('airports_result', lines.join('\n'), true);
                } else {
                    showResult('airports_result', 'Failed to fetch stats: ' + (data.message || 'unknown'), false);
                }
            })
            .catch(error => {
                showResult('airports_result', 'Error: ' + error.message, false);
            });
        }

        window.transferContracts = transferContracts;
        window.checkContracts = checkContracts;
        window.transferAccommodationTypes = transferAccommodationTypes;
        window.checkAccommodationTypes = checkAccommodationTypes;\r\n        window.transferAirports = transferAirports;\r\n        window.checkAirports = checkAirports;        // وظائف عامة
        function showResult(elementId, message, isSuccess) {
            const element = document.getElementById(elementId);
            element.style.display = 'block';
            element.className = 'result ' + (isSuccess ? 'success' : 'error');
            element.textContent = message;
        }

        function updateStats() {
            const sql = `
                SELECT
                    (SELECT COUNT(*) FROM users) as users,
                    (SELECT COUNT(*) FROM countries) as countries,
                    (SELECT COUNT(*) FROM nationalities) as nationalities,
                    (SELECT COUNT(*) FROM nationalities_translations) as translations,
                    (SELECT COUNT(*) FROM job_titles) as job_titles,
                    (SELECT COUNT(*) FROM religions) as religions,
                    (SELECT COUNT(*) FROM social_status) as social_status,
                    (SELECT COUNT(*) FROM skills) as skills,
                    (SELECT COUNT(*) FROM experience) as experience,
                    (SELECT COUNT(*) FROM ages) as ages,
                    (SELECT COUNT(*) FROM languages) as languages,
                    (SELECT COUNT(*) FROM maids) as maids,
                    (SELECT COUNT(*) FROM contracts) as contracts,
                    (SELECT COUNT(*) FROM contract_infos) as contract_infos,
                    (SELECT COUNT(*) FROM airports) as airports
            `;

            fetch('migration/file_processor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=execute_sql&sql=' + encodeURIComponent(sql)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.result && data.result[0]) {
                    const stats = data.result[0];
                    const order = [
                        'users',
                        'countries',
                        'nationalities',
                        'translations',
                        'job_titles',
                        'religions',
                        'social_status',
                        'skills',
                        'experience',
                        'ages',
                        'languages',
                        'maids',
                        'contracts',
                        'contract_infos',
                        'airports'
                    ];

                    const numbers = document.querySelectorAll('.stat .number');
                    order.forEach((key, index) => {
                        if (numbers[index]) {
                            numbers[index].textContent = stats[key] || '0';
                        }
                    });
                }
            })
            .catch(err => console.error('Error updating stats:', err));
        }

        // تحديث الإحصائيات عند التحميل
        window.onload = function() {
            updateStats();
        };
    </script>
</body>
</html>










