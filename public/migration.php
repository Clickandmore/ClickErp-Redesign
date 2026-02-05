<?php
function readEnvFile(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $vars = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
            $value = trim($value, "\"'");
        }
        $vars[$key] = $value;
    }

    return $vars;
}

$env = readEnvFile(__DIR__ . '/../.env');

$oldDb = [
    'host' => $env['MIGRATION_OLD_DB_HOST'] ?? $env['OLD_DB_HOST'] ?? $env['DB_OLD_HOST'] ?? '127.0.0.1',
    'port' => $env['MIGRATION_OLD_DB_PORT'] ?? $env['OLD_DB_PORT'] ?? $env['DB_OLD_PORT'] ?? '8889',
    'name' => $env['MIGRATION_OLD_DB_NAME'] ?? $env['OLD_DB_NAME'] ?? $env['OLD_DB_DATABASE'] ?? $env['DB_OLD_DATABASE'] ?? 'platinum_old',
    'user' => $env['MIGRATION_OLD_DB_USER'] ?? $env['OLD_DB_USER'] ?? $env['DB_OLD_USERNAME'] ?? 'root',
    'pass' => $env['MIGRATION_OLD_DB_PASS'] ?? $env['OLD_DB_PASS'] ?? $env['DB_OLD_PASSWORD'] ?? 'root',
];

$newDb = [
    'host' => $env['MIGRATION_NEW_DB_HOST'] ?? $env['DB_HOST'] ?? '127.0.0.1',
    'port' => $env['MIGRATION_NEW_DB_PORT'] ?? $env['DB_PORT'] ?? '8889',
    'name' => $env['MIGRATION_NEW_DB_NAME'] ?? $env['DB_DATABASE'] ?? 'platinum_new',
    'user' => $env['MIGRATION_NEW_DB_USER'] ?? $env['DB_USERNAME'] ?? 'root',
    'pass' => $env['MIGRATION_NEW_DB_PASS'] ?? $env['DB_PASSWORD'] ?? 'root',
];
?><!DOCTYPE html>
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
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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

        .btn-success {
            background: #28a745;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-danger {
            background: #dc3545;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-info {
            background: #17a2b8;
        }

        .btn-info:hover {
            background: #138496;
        }

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
                <button onclick="syncAdminsFromUsers(this)" class="btn-success">مزامنة المدراء (Users→Admins (Admin))</button>
                <button onclick="syncStaffFromUsers(this)" class="btn-success">مزامنة الطاقم (Users→Admins (Staff))</button>
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

        <!-- ??? ????? (Cities) -->
        <div class="section">
            <h3>3️⃣ المدن (Cities)</h3>
            <div class="buttons">
                <button onclick="transferCities()" class="btn-success">نقل المدن</button>
                <button onclick="checkCities()" class="btn-info">فحص النقل</button>
            </div>
            <div id="cities_result" class="result"></div>
        </div>

        <!-- قسم الجنسيات -->
        <div class="section">
            <h3>3️⃣ نقل الجنسيات (النظام المتقدم)</h3>

            <div style="background: #fff3cd; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                <h6 style="color: #856404; margin-bottom: 10px;">📋 تطابق الأعمدة:</h6>
                <div style="font-size: 13px; color: #856404;">
                    <strong>من <?php echo htmlspecialchars($oldDb['name']); ?>.nationalities إلى:</strong><br>
                    • <?php echo htmlspecialchars($newDb['name']); ?>.nationalities: id, flag_image→logo, salary→price, sponsor_salary→service_transportation_cost<br>
                    • <?php echo htmlspecialchars($newDb['name']); ?>.nationalities_translations: name→title, sub_title (فارغ)<br>
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

        <!-- قسم المكاتب الخارجية (Offices) -->
        <div class="section">
            <h3> 4️⃣ نقل المكاتب الخارجية </h3>

            <div style="background: #e0f7fa; border-radius: 8px; padding: 15px; margin-bottom: 15px; color: #006064;">
                <h6 style="margin-bottom: 10px;">🏢 تطابق الأعمدة (<?php echo htmlspecialchars($oldDb['name']); ?>.offices → <?php echo htmlspecialchars($newDb['name']); ?>.recruitment_offices)</h6>
                <div style="font-size: 13px; line-height: 1.6;">
                    • ban → status (القيمة 0 = Active، القيمة 1 = Dis Active)<br>
                    • office_phone → phone_office<br>
                    • office_commercial_id → license_number<br>
                    • nationality_id → nationality_id (مع التحقق من توفر الجنسية الجديدة)
                </div>
            </div>

            <div class="buttons">
                <button onclick="analyzeOffices(this)" class="btn-info">تحليل المكاتب</button>
                <button onclick="migrateOffices(this)" class="btn-success">نقل المكاتب</button>
                <button onclick="migrateOfficeTranslationsOnly(this)" class="btn-success" style="background:#6f42c1;">نقل ترجمات المكاتب</button>
                <button onclick="checkOffices()" class="btn-info">فحص النتيجة</button>
                <button onclick="exportOfficesCsv()" class="btn-info">تصدير CSV</button>
            </div>
            <div id="offices_result" class="result"></div>
        </div>

        <!-- قسم المهن (Job Titles) -->
        <div class="section">
            <h3>5️⃣ نقل المهن (Job Titles)</h3>
            <div class="buttons">
                <button onclick="transferJobTitles()" class="btn-success">نقل المهن</button>
                <button onclick="checkJobTitles()" class="btn-info">فحص النتيجة</button>
            </div>
            <div id="jobs_result" class="result"></div>
        </div>

        <!-- قسم الأديان (Religions) -->
        <div class="section">
            <h3>6️⃣ نقل الأديان (Religions)</h3>
            <div class="buttons">
                <button onclick="transferReligions()" class="btn-success">نقل الأديان</button>
                <button onclick="checkReligions()" class="btn-info">فحص النتيجة</button>
            </div>
            <div id="religions_result" class="result"></div>
        </div>

        <!-- قسم الحالات الاجتماعية (Social Status) -->
        <div class="section">
            <h3>7️⃣ نقل الحالات الاجتماعية (Social Status)</h3>
            <div class="buttons">
                <button onclick="transferSocialStatus()" class="btn-success">نقل الحالات الاجتماعية</button>
                <button onclick="checkSocialStatus()" class="btn-info">فحص النتيجة</button>
            </div>
            <div id="social_status_result" class="result"></div>
        </div>

        <!-- قسم المهارات (Skills) -->
        <div class="section">
            <h3>8️⃣ نقل المهارات (Skills)</h3>
            <div class="buttons">
                <button onclick="transferSkills()" class="btn-success">نقل المهارات</button>
                <button onclick="checkSkills()" class="btn-info">فحص النتيجة</button>
            </div>
            <div id="skills_result" class="result"></div>
        </div>

        <!-- قسم الخبرات (Experience) -->
        <div class="section">
            <h3>9️⃣ نقل الخبرات (Experience)</h3>
            <div class="buttons">
                <button onclick="transferExperience()" class="btn-success">نقل الخبرات</button>
                <button onclick="checkExperience()" class="btn-info">فحص النتيجة</button>
            </div>
            <div id="experience_result" class="result"></div>
        </div>

        <!-- قسم الأعمار (Ages) -->
        <div class="section">
            <h3>🔟 نقل الأعمار (Ages)</h3>
            <div class="buttons">
                <button onclick="transferAges()" class="btn-success">نقل الأعمار</button>
                <button onclick="checkAges()" class="btn-info">فحص النتيجة</button>
            </div>
            <div id="ages_result" class="result"></div>
        </div>

        <!-- قسم اللغات (Languages) -->
        <div class="section">
            <h3>1️⃣1️⃣ نقل اللغات (Languages)</h3>
            <div class="buttons">
                <button onclick="transferLanguages()" class="btn-success">نقل اللغات</button>
                <button onclick="checkLanguages()" class="btn-info">فحص النتيجة</button>
            </div>
            <div id="languages_result" class="result"></div>
        </div>

        <!-- قسم السير الذاتية -->
        <div class="section">
            <h3>1️⃣2️⃣ نقل السير الذاتية (CVs → Maids)</h3>
            <div class="buttons">
                <button onclick="analyzeMaids(this)" class="btn-info">تحليل السير الذاتية</button>
                <button onclick="transferMaids(this)" class="btn-success">نقل السير الذاتية</button>
                <button onclick="fetchMaidMissingDetails(this)" class="btn-info" style="background:#6f42c1;">عرض النواقص</button>
                <button onclick="checkMaids()" class="btn-info">فحص النتيجة</button>
            </div>
            <div id="maids_result" class="result"></div>
        </div>

        <!-- نقل الحجوزات -> الطلبات (Orders) -->
        <div class="section">
            <h3>1️⃣2️⃣ نقل الحجوزات -> الطلبات (Orders)</h3>
            <div class="buttons">
                <button onclick="transferReservationsToOrders()" class="btn-success">نقل الحجوزات إلى الطلبات</button>
                <button onclick="checkOrders()" class="btn-info">فحص الطلبات</button>
            </div>
            <div id="orders_result" class="result"></div>
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

        <!-- Contract Infos -->
        <div class="section">
            <h3>1️⃣3️⃣ معلومات العقد (Contract Infos)</h3>
            <div class="buttons">
                <button onclick="transferContractInfos()" class="btn-success">نقل معلومات العقد</button>
                <button onclick="checkContractInfos()" class="btn-info">فحص نتيجة معلومات العقد</button>
            </div>
            <div id="contract_infos_result" class="result"></div>
        </div>

        <!-- Contract Logs -->
        <div class="section">
            <h3>1️⃣4️⃣ سجلات العقود (Contract Logs)</h3>
            <div class="buttons">
                <button onclick="transferContractLogs()" class="btn-success">نقل سجلات العقود</button>
                <button onclick="checkContractLogs()" class="btn-info">فحص سجلات العقود</button>
            </div>
            <div id="contract_logs_result" class="result"></div>
        </div>

        <!-- داخل الايواء (cv_previous_sponsor) -->
        <div class="section">
            <h3>1️⃣5️⃣ نقل داخل الإيواء (cv_previous_sponsor → contracts/contract_infos)</h3>
            <div class="buttons">
                <button onclick="transferPreviousSponsor()" class="btn-success">نقل داخل الإيواء</button>
                <button onclick="checkPreviousSponsor()" class="btn-info">فحص النتيجة</button>
            </div>
            <div id="previous_sponsor_result" class="result"></div>
        </div>

        <!-- نقل حجوزات نقل الكفالة (reservation_sponsor) -->
        <div class="section">
            <h3>1️⃣5️⃣ نقل حجوزات نقل الكفالة (reservation_sponsor → sponsorship_transfers)</h3>
            <div class="buttons">
                <button onclick="transferReservationSponsor()" class="btn-success">نقل حجوزات نقل الكفالة</button>
            </div>
            <div id="reservation_sponsor_result" class="result"></div>
        </div>

        <!-- قسم أنواع الإيواء (Accommodation Types) -->
        <div class="section">
            <h3>1️⃣5️⃣ أنواع الإيواء (Accommodation Types)</h3>
            <div class="buttons">
                <button onclick="transferAccommodationTypes()" class="btn-success">نقل أنواع الإيواء</button>
                <button onclick="checkAccommodationTypes()" class="btn-info">فحص النتيجة</button>
            </div>
            <div id="accommodation_types_result" class="result"></div>
        </div>

        <!-- Airports -->
        <div class="section">
            <h3>1️⃣6️⃣ المطارات  )Airports(</h3>
            <div class="buttons">
                <button onclick="transferAirports()" class="btn-success">Transfer Airports</button>
                <button onclick="checkAirports()" class="btn-info">Check Airports</button>
            </div>
            <div id="airports_result" class="result"></div>
        </div>

        <!-- Activity Logs -->
        <div class="section">
            <h3>1️⃣7️⃣ سجلات الأنشطة (Activity Logs)</h3>
            <div class="buttons">
                <button onclick="transferActivityLogs()" class="btn-success">Transfer Activity Logs</button>
                <button onclick="checkActivityLogs()" class="btn-info">Check Activity Logs</button>
            </div>
            <div id="activity_logs_result" class="result"></div>
        </div>

                <!-- Questions -->
        <div class="section">
            <h3>الأسئلة الشائعة (Questions)</h3>
            <div class="buttons">
                <button onclick="transferQuestions()" class="btn-success">Transfer Questions</button>
                <button onclick="checkQuestions()" class="btn-info">Check Questions</button>
            </div>
            <div id="questions_result" class="result"></div>
        </div>
        <!-- Messages -->
        <div class="section">
            <h3>1️⃣8️⃣ الرسائل (Messages)</h3>
            <div class="buttons">
                <button onclick="transferMessages()" class="btn-success">Transfer Messages</button>
                <button onclick="checkMessages()" class="btn-info">Check Messages</button>
            </div>
            <div id="messages_result" class="result"></div>
        </div>


        <!-- Contact Us -->
        <div class="section">
            <h3>1️⃣9️⃣ Contact Us</h3>
            <div class="buttons">
                <button onclick="transferContacts()" class="btn-success">Transfer Contact Us</button>
                <button onclick="checkContacts()" class="btn-info">Check Contacts</button>
            </div>
            <div id="contacts_result" class="result"></div>
        </div>

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
                    <div class="label">Cities</div>
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
                    <div class="label">Offices</div>
                </div>
                <div class="stat">
                    <div class="number">-</div>
                    <div class="label">Office Translations</div>
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
                    <div class="label">Contract Logs</div>
                </div>
                <div class="stat">
                    <div class="number">-</div>
                    <div class="label">Activity Logs</div>
                </div>
                <div class="stat">
                    <div class="number">-</div>
                    <div class="label">Messages</div>
                </div>
                <div class="stat">
                    <div class="number">-</div>
                    <div class="label">Questions</div>
                </div>
                <div class="stat">
                    <div class="number">-</div>
                    <div class="label">Question Translations</div>
                </div>
                <div class="stat">
                    <div class="number">-</div>
                    <div class="label">Contacts</div>
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
        const MIGRATION_DB = <?php echo json_encode([
            'old' => [
                'old_db_host' => $oldDb['host'],
                'old_db_port' => $oldDb['port'],
                'old_db_name' => $oldDb['name'],
                'old_db_user' => $oldDb['user'],
                'old_db_pass' => $oldDb['pass'],
            ],
            'new' => [
                'db_host' => $newDb['host'],
                'db_port' => $newDb['port'],
                'db_name' => $newDb['name'],
                'db_user' => $newDb['user'],
                'db_pass' => $newDb['pass'],
            ],
            'maids' => [
                'db_host' => $newDb['host'],
                'db_port' => $newDb['port'],
                'db_user' => $newDb['user'],
                'db_pass' => $newDb['pass'],
                'old_db' => $oldDb['name'],
                'new_db' => $newDb['name'],
            ],
        ], JSON_UNESCAPED_SLASHES); ?>;

        function withOldDb(params) {
            return Object.assign({}, params, MIGRATION_DB.old, MIGRATION_DB.new);
        }

        function withNewDb(params) {
            return Object.assign({}, params, MIGRATION_DB.new);
        }

        function withMaidsDb(params) {
            return Object.assign({}, params, MIGRATION_DB.maids);
        }
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
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
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
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
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

        async function syncAdminsFromUsers(button) {
            const btn = button instanceof HTMLElement ? button : event.target;
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'جاري المزامنة...';

            try {
                const response = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=migrate_admins_from_users'
                });

                const data = await response.json();

                if (data.success) {
                    let message = data.message || '✅ تم التحديث بنجاح';
                    if (typeof data.inserted !== 'undefined' || typeof data.updated !== 'undefined') {
                        message += `\n➕ جديد: ${data.inserted || 0}\n🔄 محدث: ${data.updated || 0}`;
                    }
                    showResult('users_result', message, true);
                } else {
                    showResult('users_result', '❌ ' + (data.message || 'فشل مزامنة المدراء'), false);
                }

                updateStats();
            } catch (error) {
                showResult('users_result', '❌ خطأ: ' + error.message, false);
            }

            btn.disabled = false;
            btn.textContent = originalText;
        }

        async function syncStaffFromUsers(button) {
            const btn = button instanceof HTMLElement ? button : event.target;
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'جاري المزامنة...';

            try {
                const response = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=migrate_staff_from_users'
                });

                const data = await response.json();

                if (data.success) {
                    let message = data.message || '✅ تم التحديث بنجاح';
                    if (typeof data.inserted !== 'undefined' || typeof data.updated !== 'undefined') {
                        message += `\n➕ جديد: ${data.inserted || 0}\n🔄 محدث: ${data.updated || 0}`;
                    }
                    showResult('users_result', message, true);
                } else {
                    showResult('users_result', '❌ ' + (data.message || 'فشل مزامنة الطاقم'), false);
                }

                updateStats();
            } catch (error) {
                showResult('users_result', '❌ خطأ: ' + error.message, false);
            }

            btn.disabled = false;
            btn.textContent = originalText;
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
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
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
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
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
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
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
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
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
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
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
        function transferCities() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'Transferring...';

            fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=migrate_cities'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const lines = [
                            'Cities migrated successfully',
                            'Total: ' + (data.processed ?? data.total ?? 0),
                            'Inserted: ' + (data.inserted ?? 0),
                            'Updated: ' + (data.updated ?? 0),
                            'Unchanged: ' + (data.unchanged ?? 0),
                            'Skipped: ' + (data.skipped ?? 0)
                        ];
                        if (data.message) {
                            lines.push(data.message);
                        }
                        showResult('cities_result', lines.join('\n'), true);
                        updateStats();
                    } else {
                        showResult('cities_result', 'Migration failed: ' + (data.message || 'operation could not be completed'), false);
                    }
                })
                .catch(error => {
                    showResult('cities_result', 'Error: ' + error.message, false);
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = 'Transfer Cities';
                });
        }

        function checkCities() {
            fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=cities_summary'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const lines = [
                            'Current cities stats:',
                            'Total: ' + (data.total ?? data.cities ?? 0)
                        ];
                        if (data.message) lines.push(data.message);
                        showResult('cities_result', lines.join('\n'), true);
                    } else {
                        showResult('cities_result', 'Failed to fetch stats: ' + (data.message || 'unknown'), false);
                    }
                })
                .catch(error => {
                    showResult('cities_result', 'Error: ' + error.message, false);
                });
        }

        
        function transferQuestions() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = "Transferring...";

            fetch("migration/file_processor.php", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=migrate_questions'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const lines = [
                            'Questions migrated successfully',
                            'Total: ' + (data.processed ?? data.total ?? 0),
                            'Inserted: ' + (data.inserted ?? 0),
                            'Updated: ' + (data.updated ?? 0),
                            'Unchanged: ' + (data.unchanged ?? 0),
                            'Skipped: ' + (data.skipped ?? 0)
                        ];
                        if (data.message) lines.push(data.message);
                        showResult('questions_result', lines.join("\n"), true);
                        updateStats();
                    } else {
                        showResult('questions_result', 'Migration failed: ' + (data.message || 'operation could not be completed'), false);
                    }
                })
                .catch(error => {
                    showResult('questions_result', 'Error: ' + error.message, false);
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = 'Transfer Questions';
                });
        }

        function checkQuestions() {
            fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=questions_summary'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const lines = [
                            'Questions: ' + (data.questions ?? 0),
                            'Translations: ' + (data.question_translations ?? 0)
                        ];
                        if (data.message) lines.push(data.message);
                        showResult('questions_result', lines.join('\n'), true);
                    } else {
                        showResult('questions_result', 'Failed to fetch stats: ' + (data.message || 'unknown'), false);
                    }
                })
                .catch(error => {
                    showResult('questions_result', 'Error: ' + error.message, false);
                });
        }
function analyzeNationalitiesStructure() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري التحليل...';

            fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
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
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=execute_sql&sql=' + encodeURIComponent('SET FOREIGN_KEY_CHECKS=0; DELETE FROM nationalities_translations; DELETE FROM nationalities; SET FOREIGN_KEY_CHECKS=1;')
                })
                .then(response => response.text())
                .then(result => {
                    showResult('nationalities_result', 'تم مسح جداول Nationalities', true);
                    updateStats();
                });
        }

        async function transferNationalitiesAdvanced() {
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

                const exportParams = withOldDb({
                    action: 'export_from_old_db',
                    export_table: 'nationalities'
                });

                const exportResponse = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams(exportParams)
                });

                const exportData = await exportResponse.json();

                if (!exportData.success) {
                    throw new Error('فشل التصدير: ' + exportData.message);
                }

                // خطوة 2: معالجة ورفع البيانات
                btn.textContent = 'جاري المعالجة والرفع...';

                const processParams = withNewDb({
                    action: 'process_exported_file',
                    exported_file: exportData.file_name,
                    table_type: 'nationalities'
                });

                const processResponse = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
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
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
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
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
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

        // وظائف المكاتب الخارجية
        function analyzeOffices(button) {
            const btn = button instanceof HTMLElement ? button : null;
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'جاري التحليل...';
            }

            const params = new URLSearchParams(withMaidsDb({
                action: 'analyze'
            }));

            fetch('migration/migrate_offices.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: params.toString()
                })
                .then(response => response.text())
                .then(result => {
                    try {
                        const data = JSON.parse(result);
                        if (data.success) {
                            const lines = [];
                            const oldStats = data.old_stats || {};
                            const newStats = data.new_stats || {};
                            lines.push(`📦 القاعدة القديمة: ${oldStats.total || 0} مكتب (نشط: ${oldStats.active || 0}, موقوف: ${oldStats.banned || 0})`);
                            lines.push(`🆕 القاعدة الجديدة: ${newStats.total || 0} مكتب (نشط: ${newStats.active || 0}, متوقف: ${newStats.inactive || 0})`);
                            lines.push(`🆔 معرفات مفقودة: ${data.missing_office_ids ? data.missing_office_ids.length : 0}`);
                            lines.push(`🌍 مطابقات الجنسيات: ${data.nationality_mapping_resolved || 0}`);

                            if (data.unresolved_nationalities && data.unresolved_nationalities.length) {
                                const sample = data.unresolved_nationalities.slice(0, 5).map(item => `${item.old_id}${item.name ? ` (${item.name})` : ''}`).join(', ');
                                const remaining = data.unresolved_nationalities.length - Math.min(5, data.unresolved_nationalities.length);
                                lines.push(`⚠️ جنسيات لم يتم إيجادها: ${sample}${remaining > 0 ? ` ... (+${remaining})` : ''}`);
                            }

                            if (data.translation_orphans && data.translation_orphans.length) {
                                lines.push(`📝 ترجمات دون أصل: ${data.translation_orphans.length}`);
                            }

                            showResult('offices_result', lines.join('\n'), true);
                        } else {
                            showResult('offices_result', '❌ ' + (data.message || 'فشل التحليل'), false);
                        }
                    } catch (e) {
                        showResult('offices_result', result, false);
                    }

                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = 'تحليل المكاتب';
                    }
                })
                .catch(error => {
                    showResult('offices_result', '❌ خطأ: ' + error.message, false);
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = 'تحليل المكاتب';
                    }
                });
        }

        function migrateOffices(button) {
            const btn = button instanceof HTMLElement ? button : null;
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'جاري النقل...';
            }

            const params = new URLSearchParams(withMaidsDb({
                action: 'migrate',
                chunk: '500'
            }));

            fetch('migration/migrate_offices.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: params.toString()
                })
                .then(response => response.text())
                .then(result => {
                    try {
                        const data = JSON.parse(result);
                        if (data.success) {
                            const stats = data.stats || {};
                            let message = `✅ ${data.message || 'تم نقل المكاتب بنجاح'}\n`;
                            message += `📦 إجمالي المصدر: ${stats.total_source_rows || 0}\n`;
                            message += `⚙️ تمت معالجتها: ${stats.processed || 0}\n`;
                            message += `🆕 مضافة: ${stats.inserted || 0}\n`;
                            message += `♻️ محدثة: ${stats.updated || 0}\n`;
                            message += `⏭ متخطاة: ${stats.skipped || 0}`;
                            if (typeof data.nationality_mapping_count === 'number') {
                                message += `\n🧭 مطابقات الجنسيات المستخدمة: ${data.nationality_mapping_count}`;
                            }

                            if (Array.isArray(data.unmapped_nationalities) && data.unmapped_nationalities.length) {
                                const sample = data.unmapped_nationalities.slice(0, 5).map(item => `${item.old_id}${item.name ? ` (${item.name})` : ''}`).join(', ');
                                const remaining = data.unmapped_nationalities.length - Math.min(5, data.unmapped_nationalities.length);
                                message += `\n⚠️ جنسيات غير مطابقة: ${sample}${remaining > 0 ? ` ... (+${remaining})` : ''}`;
                            }

                            if (data.profiles_migration) {
                                const profiles = data.profiles_migration;
                                message += `\n🗂 ملفات المكتب: المصدر ${profiles.total_source_rows || 0} | مضافة ${profiles.inserted || 0} | محدثة ${profiles.updated || 0}`;
                                if (profiles.skills_links !== undefined) {
                                    message += ` | روابط المهارات ${profiles.skills_links}`;
                                }
                            }

                            if (data.errors && data.errors.length) {
                                const firstError = data.errors[0];
                                message += `\n⚠️ أول خطأ: ${(firstError.error || '')}`;
                            }

                            showResult('offices_result', message, true);
                            updateStats();
                        } else {
                            showResult('offices_result', '❌ ' + (data.message || 'فشل النقل'), false);
                        }
                    } catch (e) {
                        showResult('offices_result', result, false);
                    }

                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = 'نقل المكاتب';
                    }
                })
                .catch(error => {
                    showResult('offices_result', '❌ خطأ: ' + error.message, false);
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = 'نقل المكاتب';
                    }
                });
        }

        function checkOffices() {
            const sql = `
                SELECT
                    (SELECT COUNT(*) FROM recruitment_offices) AS offices,
                    (SELECT COUNT(*) FROM recruitment_office_translations) AS translations
            `;

            fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=execute_sql&sql=' + encodeURIComponent(sql)
                })
                .then(response => response.text())
                .then(result => {
                    try {
                        const data = JSON.parse(result);
                        if (data.success && data.result && data.result[0]) {
                            const row = data.result[0];
                            showResult('offices_result', `📊 مكاتب جديدة: ${row.offices || 0} | ترجمات: ${row.translations || 0}`, true);
                        } else {
                            showResult('offices_result', '❌ فشل التحقق من النتائج', false);
                        }
                    } catch (e) {
                        showResult('offices_result', result, false);
                    }
                })
                .catch(error => showResult('offices_result', '❌ خطأ: ' + error.message, false));
        }

        function migrateOfficeTranslationsOnly(button) {
            const btn = button instanceof HTMLElement ? button : null;
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'جاري الترحيل...';
            }

            fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=migrate_office_translations'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const lines = [
                            data.message || 'تم نقل الترجمات بنجاح',
                            `إجمالي: ${data.total || 0}`,
                            `تمت الإضافة: ${data.inserted || 0}`,
                            `تم التحديث: ${data.updated || 0}`,
                            `تم التخطي: ${data.skipped || 0}`
                        ];
                        showResult('offices_result', lines.join('\n'), true);
                        updateStats();
                    } else {
                        showResult('offices_result', 'فشل نقل الترجمات: ' + (data.message || 'غير معروف'), false);
                    }
                })
                .catch(error => showResult('offices_result', 'خطأ: ' + error.message, false))
                .finally(() => {
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = 'نقل ترجمات المكاتب';
                    }
                });
        }
        function exportOfficesCsv() {
            const params = new URLSearchParams(withMaidsDb({
                action: 'export_csv'
            }));

            window.open('migration/migrate_offices.php?' + params.toString(), '_blank');
        }

        // قسم المهن Job Titles
        async function transferJobTitles() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل...';

            try {
                const response = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=migrate_job_titles'
                });
                const data = await response.json();

                if (data.success) {
                    const lines = [
                        data.message || 'تم نقل المهن بنجاح',
                        `إجمالي: ${data.total || 0}`,
                        `تمت الإضافة: ${data.inserted || 0}`,
                        `تم التحديث: ${data.updated || 0}`
                    ];
                    showResult('jobs_result', lines.join('\n'), true);
                    updateStats();
                } else {
                    showResult('jobs_result', `فشل النقل: ${data.message || 'غير معروف'}`, false);
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
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=execute_sql&sql=' + encodeURIComponent('SELECT (SELECT COUNT(*) FROM job_titles) jt, (SELECT COUNT(*) FROM job_titles_translations) tr')
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.result && data.result[0]) {
                        const row = data.result[0];
                        showResult('jobs_result', `📊 Job Titles: ${row.jt || 0} | Translations: ${row.tr || 0}`, true);
                    } else {
                        showResult('jobs_result', '❌ فشل الفحص', false);
                    }
                })
                .catch(e => showResult('jobs_result', '❌ ' + e.message, false));
        }

        // قسم الأديان Religions
        async function transferReligions() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل...';

            try {
                const response = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=migrate_religions'
                });
                const data = await response.json();

                if (data.success) {
                    const lines = [
                        data.message || 'تم نقل الأديان بنجاح',
                        `إجمالي: ${data.total || 0}`,
                        `تمت الإضافة: ${data.inserted || 0}`,
                        `تم التحديث: ${data.updated || 0}`
                    ];
                    showResult('religions_result', lines.join('\n'), true);
                    updateStats();
                } else {
                    showResult('religions_result', `فشل النقل: ${data.message || 'غير معروف'}`, false);
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
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=execute_sql&sql=' + encodeURIComponent('SELECT (SELECT COUNT(*) FROM religions) rl, (SELECT COUNT(*) FROM religions_translations) tr')
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.result && data.result[0]) {
                        const row = data.result[0];
                        showResult('religions_result', `📊 Religions: ${row.rl || 0} | Translations: ${row.tr || 0}`, true);
                    } else {
                        showResult('religions_result', '❌ فشل الفحص', false);
                    }
                })
                .catch(e => showResult('religions_result', '❌ ' + e.message, false));
        }

        // وظائف الحالات الاجتماعية Social Status
        // قسم الحالات الاجتماعية Social Status
        async function transferSocialStatus() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل...';

            try {
                const response = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=migrate_social_status'
                });
                const data = await response.json();

                if (data.success) {
                    const lines = [
                        data.message || 'تم نقل الحالات الاجتماعية بنجاح',
                        `إجمالي: ${data.total || 0}`,
                        `تمت الإضافة: ${data.inserted || 0}`,
                        `تم التحديث: ${data.updated || 0}`
                    ];
                    showResult('social_status_result', lines.join('\n'), true);
                    updateStats();
                } else {
                    showResult('social_status_result', `فشل النقل: ${data.message || 'غير معروف'}`, false);
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
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=execute_sql&sql=' + encodeURIComponent('SELECT (SELECT COUNT(*) FROM social_status) ss, (SELECT COUNT(*) FROM social_status_translations) tr')
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.result && data.result[0]) {
                        const row = data.result[0];
                        showResult('social_status_result', `📊 Social Status: ${row.ss || 0} | Translations: ${row.tr || 0}`, true);
                    } else {
                        showResult('social_status_result', '❌ فشل الفحص', false);
                    }
                })
                .catch(e => showResult('social_status_result', '❌ ' + e.message, false));
        }

        // وظائف المهارات Skills
        // قسم المهارات Skills
        async function transferSkills() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل...';

            try {
                const response = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=migrate_skills'
                });
                const data = await response.json();

                if (data.success) {
                    const lines = [
                        data.message || 'تم نقل المهارات بنجاح',
                        `إجمالي: ${data.total || 0}`,
                        `تمت الإضافة: ${data.inserted || 0}`,
                        `تم التحديث: ${data.updated || 0}`
                    ];
                    showResult('skills_result', lines.join('\n'), true);
                    updateStats();
                } else {
                    showResult('skills_result', `فشل النقل: ${data.message || 'غير معروف'}`, false);
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
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=execute_sql&sql=' + encodeURIComponent('SELECT (SELECT COUNT(*) FROM skills) sk, (SELECT COUNT(*) FROM skills_translations) tr')
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.result && data.result[0]) {
                        const row = data.result[0];
                        showResult('skills_result', `📊 Skills: ${row.sk || 0} | Translations: ${row.tr || 0}`, true);
                    } else {
                        showResult('skills_result', '❌ فشل الفحص', false);
                    }
                })
                .catch(e => showResult('skills_result', '❌ ' + e.message, false));
        }

        // وظائف الخبرات Experience
        // قسم الخبرات Experience
        async function transferExperience() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل...';

            try {
                const response = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=migrate_experience'
                });
                const data = await response.json();

                if (data.success) {
                    const lines = [
                        data.message || 'تم نقل الخبرات بنجاح',
                        `إجمالي: ${data.total || 0}`,
                        `تمت الإضافة: ${data.inserted || 0}`,
                        `تم التحديث: ${data.updated || 0}`
                    ];
                    showResult('experience_result', lines.join('\n'), true);
                    updateStats();
                } else {
                    showResult('experience_result', `فشل النقل: ${data.message || 'غير معروف'}`, false);
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
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=execute_sql&sql=' + encodeURIComponent('SELECT (SELECT COUNT(*) FROM experience) ex, (SELECT COUNT(*) FROM experience_translations) tr')
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.result && data.result[0]) {
                        const row = data.result[0];
                        showResult('experience_result', `📊 Experience: ${row.ex || 0} | Translations: ${row.tr || 0}`, true);
                    } else {
                        showResult('experience_result', '❌ فشل الفحص', false);
                    }
                })
                .catch(e => showResult('experience_result', '❌ ' + e.message, false));
        }

        // وظائف اللغات Languages
        // قسم اللغات Languages
        async function transferLanguages() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل...';

            try {
                const response = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=migrate_languages'
                });
                const data = await response.json();

                if (data.success) {
                    const lines = [
                        data.message || 'تم نقل اللغات بنجاح',
                        `إجمالي: ${data.total || 0}`,
                        `تمت الإضافة: ${data.inserted || 0}`,
                        `تم التحديث: ${data.updated || 0}`
                    ];
                    showResult('languages_result', lines.join('\n'), true);
                    updateStats();
                } else {
                    showResult('languages_result', `فشل النقل: ${data.message || 'غير معروف'}`, false);
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
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=execute_sql&sql=' + encodeURIComponent('SELECT (SELECT COUNT(*) FROM languages) lg, (SELECT COUNT(*) FROM languages_translations) tr')
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.result && data.result[0]) {
                        const row = data.result[0];
                        showResult('languages_result', `📊 Languages: ${row.lg || 0} | Translations: ${row.tr || 0}`, true);
                    } else {
                        showResult('languages_result', '❌ فشل الفحص', false);
                    }
                })
                .catch(e => showResult('languages_result', '❌ ' + e.message, false));
        }

        // وظائف الأعمار Ages
        // قسم الأعمار Ages
        async function transferAges() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل...';

            try {
                const response = await fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=migrate_ages'
                });
                const data = await response.json();

                if (data.success) {
                    const lines = [
                        data.message || 'تم نقل الأعمار بنجاح',
                        `إجمالي: ${data.total || 0}`,
                        `تمت الإضافة: ${data.inserted || 0}`,
                        `تم التحديث: ${data.updated || 0}`
                    ];
                    showResult('ages_result', lines.join('\n'), true);
                    updateStats();
                } else {
                    showResult('ages_result', `فشل النقل: ${data.message || 'غير معروف'}`, false);
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
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=execute_sql&sql=' + encodeURIComponent('SELECT (SELECT COUNT(*) FROM ages) ag, (SELECT COUNT(*) FROM ages_translations) tr')
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.result && data.result[0]) {
                        const row = data.result[0];
                        showResult('ages_result', `📊 Ages: ${row.ag || 0} | Translations: ${row.tr || 0}`, true);
                    } else {
                        showResult('ages_result', '❌ فشل الفحص', false);
                    }
                })
                .catch(e => showResult('ages_result', '❌ ' + e.message, false));
        }

        // وظائف السير الذاتية
        function clearMaids() {
            if (!confirm('⚠️ هل أنت متأكد من مسح جدول Maids في القاعدة الجديدة؟')) return;

            fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=execute_sql&sql=' + encodeURIComponent('DELETE FROM maids')
                })
                .then(response => response.text())
                .then(result => {
                    showResult('maids_result', 'تم مسح جدول Maids', true);
                });
        }

        function analyzeMaids(button) {
            const btn = button instanceof HTMLElement ? button : null;
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'جاري التحليل...';
            }

            const params = new URLSearchParams(withMaidsDb({
                action: 'analyze'
            }));

            fetch('migration/migrate_maids.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: params.toString()
                })
                .then(response => response.text())
                .then(result => {
                    try {
                        const data = JSON.parse(result);
                        if (data.success) {
                            let message = `📦 cvs القديمة: ${data.stats?.old_total || 0}\n`;
                            message += `🆕 maids الحالية: ${data.stats?.new_total || 0}`;

                            if (Array.isArray(data.status_breakdown)) {
                                const mapStatus = {
                                    0: 'متاحة',
                                    1: 'محجوزة',
                                    2: 'باك آوت',
                                    3: 'معلقة',
                                    4: 'تم الترحيل',
                                    5: 'هروب',
                                };
                                message += '\n📊 توزيع الحالات:';
                                data.status_breakdown.forEach(row => {
                                    const label = mapStatus[row.cv_status] || row.cv_status;
                                    message += `\n- ${label}: ${row.total}`;
                                });
                            }

                            if (data.missing_nationalities?.length) {
                                message += `\n⚠️ جنسيات مفقودة (${data.missing_nationalities.length}): ${data.missing_nationalities.slice(0, 10).join(', ')}`;
                            }

                            if (data.missing_religions?.length) {
                                message += `\n⚠️ ديانات مفقودة (${data.missing_religions.length}): ${data.missing_religions.join(', ')}`;
                            }

                            if (data.unmapped_occupations?.length) {
                                message += `\n⚠️ مهن غير معروفة (${data.unmapped_occupations.length}): ${data.unmapped_occupations.slice(0, 10).join(', ')}`;
                            }

                            if (data.unmatched_offices?.length) {
                                message += `\n⚠️ مكاتب لم يتم مطابقتها (${data.unmatched_offices.length}): ${data.unmatched_offices.slice(0, 10).join(', ')}`;
                            }

                            if (data.unmapped_languages?.length) {
                                message += `\n⚠️ لغات غير معروفة (${data.unmapped_languages.length}): ${data.unmapped_languages.slice(0, 10).join(', ')}`;
                            }

                            showResult('maids_result', message, true);
                        } else {
                            showResult('maids_result', '❌ ' + (data.message || 'فشل التحليل'), false);
                        }
                    } catch (error) {
                        showResult('maids_result', result, false);
                    }

                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = 'تحليل السير الذاتية';
                    }
                })
                .catch(error => {
                    showResult('maids_result', '❌ خطأ: ' + error.message, false);
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = 'تحليل السير الذاتية';
                    }
                });
        }

        function transferMaids(button) {
            const btn = button instanceof HTMLElement ? button : null;
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'جاري النقل...';
            }

            const params = new URLSearchParams(withMaidsDb({
                action: 'migrate'
            }));

            fetch('migration/migrate_maids.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: params.toString()
                })
                .then(response => response.text())
                .then(result => {
                    try {
                        const data = JSON.parse(result);
                        if (data.success) {
                            const stats = data.stats || {};
                            let message = `✅ ${data.message || 'تم نقل السير الذاتية.'}`;
                            message += `\n📦 إجمالي المصدر: ${stats.total_source_rows || 0}`;
                            message += `\n⚙️ تمت معالجتها: ${stats.processed || 0}`;
                            message += `\n🆕 مضافة: ${stats.inserted || 0}`;
                            message += `\n♻️ محدثة: ${stats.updated || 0}`;
                            message += `\n⏭ متخطاة: ${stats.skipped || 0}`;

                            if (data.profiles_migration) {
                                const profiles = data.profiles_migration;
                                message += `\n🗂 ملفات المكتب: المصدر ${profiles.total_source_rows || 0} | مضافة ${profiles.inserted || 0} | محدثة ${profiles.updated || 0}`;
                                message += ` | روابط المهارات ${profiles.skills_links || 0}`;
                                message += ` | روابط الوظائف ${profiles.job_title_links || 0}`;
                            }

                            if (Array.isArray(data.errors) && data.errors.length) {
                                const sample = data.errors.slice(0, 5).map(err => `#${err.id}: ${err.reason}`).join('\n');
                                message += `\n⚠️ أخطاء (${data.errors.length}):\n${sample}`;
                            }

                            if (Array.isArray(data.warnings) && data.warnings.length) {
                                const sampleWarnings = data.warnings.slice(0, 5).map(warn => `#${warn.id}: ${warn.message}`).join('\n');
                                message += `\nℹ️ تحذيرات (${data.warnings.length}):\n${sampleWarnings}`;
                            }

                            if (data.defaults_used) {
                                const nat = data.defaults_used.nationality || 0;
                                const rel = data.defaults_used.religion || 0;
                                if (nat || rel) {
                                    message += `\n🧭 تم استخدام القيم الافتراضية: جنسية ${nat} مرة/مرات، ديانة ${rel} مرة/مرات`;
                                }
                            }

                            showResult('maids_result', message, true);
                            updateStats();
                        } else {
                            showResult('maids_result', '❌ ' + (data.message || 'فشل النقل'), false);
                        }
                    } catch (error) {
                        showResult('maids_result', result, false);
                    }

                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = 'نقل السير الذاتية';
                    }
                })
                .catch(error => {
                    showResult('maids_result', '❌ خطأ: ' + error.message, false);
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = 'نقل السير الذاتية';
                    }
                });
        }

        function fetchMaidMissingDetails(button) {
            const btn = button instanceof HTMLElement ? button : null;
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'جاري التحميل...';
            }

            const params = new URLSearchParams(withMaidsDb({
                action: 'missing_details'
            }));

            fetch('migration/migrate_maids.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: params.toString()
                })
                .then(response => response.text())
                .then(result => {
                    try {
                        const data = JSON.parse(result);
                        if (data.success && data.details) {
                            const details = data.details;
                            const lines = [];
                            lines.push('🔍 تفاصيل النواقص:');

                            if (details.missing_nationalities?.length) {
                                lines.push(`⚠️ جنسيات مفقودة (${details.missing_nationalities.length}): ${details.missing_nationalities.join(', ')}`);
                            }

                            if (details.missing_religions?.length) {
                                lines.push(`⚠️ ديانات مفقودة (${details.missing_religions.length}): ${details.missing_religions.join(', ')}`);
                            }

                            if (details.null_nationalities?.count) {
                                const sample = (details.null_nationalities.samples || []).join(', ');
                                lines.push(`⚠️ سجلات بدون جنسية (${details.null_nationalities.count}): ${sample}`);
                            }

                            if (details.null_religions?.count) {
                                const sample = (details.null_religions.samples || []).join(', ');
                                lines.push(`⚠️ سجلات بدون ديانة (${details.null_religions.count}): ${sample}`);
                            }

                            if (details.unmapped_occupations?.length) {
                                lines.push(`⚠️ مهن غير معروفة (${details.unmapped_occupations.length}): ${details.unmapped_occupations.join(', ')}`);
                            }

                            if (details.unmatched_offices?.length) {
                                lines.push(`⚠️ مكاتب لم يتم مطابقتها (${details.unmatched_offices.length}): ${details.unmatched_offices.slice(0, 20).join(', ')}`);
                            }

                            if (details.unmapped_languages?.length) {
                                lines.push(`⚠️ لغات غير معروفة (${details.unmapped_languages.length}): ${details.unmapped_languages.join(', ')}`);
                            }

                            if (lines.length === 1) {
                                lines.push('لا توجد نواقص في البيانات المرجعية.');
                            }

                            showResult('maids_result', lines.join('\n'), true);
                        } else {
                            showResult('maids_result', '❌ فشل استخراج النواقص', false);
                        }
                    } catch (error) {
                        showResult('maids_result', result, false);
                    }

                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = 'عرض النواقص';
                    }
                })
                .catch(error => {
                    showResult('maids_result', '❌ خطأ: ' + error.message, false);
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = 'عرض النواقص';
                    }
                });
        }

        function checkMaids() {
            fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
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

        function transferReservationsToOrders() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل...';

            fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=migrate_reservations_to_orders'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const lines = [
                            'تم نقل الحجوزات إلى الطلبات بنجاح',
                            'إجمالي السجلات: ' + (data.total ?? data.processed ?? 0),
                            'تم الإدخال: ' + (data.inserted ?? 0),
                            'تم التحديث: ' + (data.updated ?? 0),
                            'تم التجاهل: ' + (data.skipped ?? 0)
                        ];
                        showResult('orders_result', lines.join('\n'), true);
                        updateStats();
                    } else {
                        showResult('orders_result', 'فشلت عملية النقل: ' + (data.message || 'لم تكتمل العملية'), false);
                    }
                })
                .catch(error => {
                    showResult('orders_result', 'خطأ: ' + error.message, false);
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = 'نقل الحجوزات إلى الطلبات';
                });
        }

        function checkOrders() {
            fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=execute_sql&sql=' + encodeURIComponent('SELECT COUNT(*) as count FROM orders')
                })
                .then(response => response.text())
                .then(result => {
                    try {
                        const data = JSON.parse(result);
                        const count = data?.result?.[0]?.count ?? data?.result?.[0]?.total ?? 0;
                        showResult('orders_result', `إجمالي الطلبات: ${count}`, true);
                    } catch {
                        showResult('orders_result', result, true);
                    }
                })
                .catch(error => {
                    showResult('orders_result', 'خطأ: ' + error.message, false);
                });
        }

        function transferContracts() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل...';

            fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
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
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=contracts_summary'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showResult('contracts_result', `📊 العقود: ${data.contracts || 0} 🗂️ معلومات العقود: ${data.contract_infos || 0}`, true);
                    } else {
                        showResult('contracts_result', `❌ ${data.message}`, false);
                    }
                })
                .catch(error => {
                    showResult('contracts_result', '❌ خطأ: ' + error.message, false);
                });
        }



        function transferContractInfos() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل...';

            fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=migrate_contract_infos'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showResult(
                            'contract_infos_result',
                            `✅ ${data.message}
البيانات المعالجة: ${data.processed || 0}
البيانات المدخلة: ${data.inserted || 0}
البيانات المحدثة: ${data.updated || 0}
البيانات المتجاوزة: ${data.skipped || 0}`,
                            true
                        );
                        updateStats();
                    } else {
                        showResult('contract_infos_result', `? ${data.message}`, false);
                    }
                    btn.disabled = false;
                    btn.textContent = 'Transfer Contract Infos';
                })
                .catch(error => {
                    showResult('contract_infos_result', '? ???: ' + error.message, false);
                    btn.disabled = false;
                    btn.textContent = 'Transfer Contract Infos';
                });
        }

        function checkContractInfos() {
            fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=contract_infos_summary'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showResult('contract_infos_result', `البيانات: ${data.contract_infos || 0}`, true);
                    } else {
                        showResult('contract_infos_result', `❌ ${data.message}`, false);
                    }
                })
                .catch(error => {
                    showResult('contract_infos_result', `❌ ${error.message}`, false);
                });
        }


        function transferContractLogs() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'Transferring...';

            fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=migrate_contract_logs'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const lines = [
                            'Contract logs migrated successfully',
                            'Total: ' + (data.processed ?? data.total ?? 0),
                            'Inserted: ' + (data.inserted ?? 0),
                            'Updated: ' + (data.updated ?? 0),
                            'Unchanged: ' + (data.unchanged ?? 0),
                            'Skipped: ' + (data.skipped ?? 0)
                        ];
                        showResult('contract_logs_result', lines.join('\n'), true);
                        updateStats();
                    } else {
                        showResult('contract_logs_result', 'Migration failed: ' + (data.message || 'operation could not be completed'), false);
                    }
                    btn.disabled = false;
                    btn.textContent = 'Transfer Contract Logs';
                })
                .catch(error => {
                    showResult('contract_logs_result', 'Error: ' + error.message, false);
                    btn.disabled = false;
                    btn.textContent = 'Transfer Contract Logs';
                });
        }

        function checkContractLogs() {
            fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=contract_logs_summary'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const lines = [
                            'Current contract logs stats:',
                            'Total: ' + (data.total ?? data.contract_logs ?? 0)
                        ];
                        if (data.message) {
                            lines.push(data.message);
                        }
                        showResult('contract_logs_result', lines.join('\n'), true);
                    } else {
                        showResult('contract_logs_result', 'Failed to fetch stats: ' + (data.message || 'unknown'), false);
                    }
                })
                .catch(error => {
                    showResult('contract_logs_result', 'Error: ' + error.message, false);
                });
        }

        function transferActivityLogs() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'Transferring...';

            fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=migrate_activity_logs'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const lines = [
                            'Activity logs migrated successfully',
                            'Total: ' + (data.processed ?? data.total ?? 0),
                            'Inserted: ' + (data.inserted ?? 0),
                            'Updated: ' + (data.updated ?? 0),
                            'Unchanged: ' + (data.unchanged ?? 0),
                            'Skipped: ' + (data.skipped ?? 0)
                        ];
                        showResult('activity_logs_result', lines.join('\n'), true);
                        updateStats();
                    } else {
                        showResult('activity_logs_result', 'Migration failed: ' + (data.message || 'operation could not be completed'), false);
                    }
                    btn.disabled = false;
                    btn.textContent = 'Transfer Activity Logs';
                })
                .catch(error => {
                    showResult('activity_logs_result', 'Error: ' + error.message, false);
                    btn.disabled = false;
                    btn.textContent = 'Transfer Activity Logs';
                });
        }

        function checkActivityLogs() {
            fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=activity_logs_summary'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const lines = [
                            'Current activity logs stats:',
                            'Total: ' + (data.total ?? data.activity_logs ?? 0)
                        ];
                        if (data.message) {
                            lines.push(data.message);
                        }
                        showResult('activity_logs_result', lines.join('\n'), true);
                    } else {
                        showResult('activity_logs_result', 'Failed to fetch stats: ' + (data.message || 'unknown'), false);
                    }
                })
                .catch(error => {
                    showResult('activity_logs_result', 'Error: ' + error.message, false);
                });
        }


        function transferMessages() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'Transferring...';

            fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=migrate_messages'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const lines = [
                            'Messages migrated successfully',
                            'Total: ' + (data.processed ?? data.total ?? 0),
                            'Inserted: ' + (data.inserted ?? 0),
                            'Updated: ' + (data.updated ?? 0),
                            'Unchanged: ' + (data.unchanged ?? 0),
                            'Skipped: ' + (data.skipped ?? 0)
                        ];
                        showResult('messages_result', lines.join('\n'), true);
                        updateStats();
                    } else {
                        showResult('messages_result', 'Migration failed: ' + (data.message || 'operation could not be completed'), false);
                    }
                    btn.disabled = false;
                    btn.textContent = 'Transfer Messages';
                })
                .catch(error => {
                    showResult('messages_result', 'Error: ' + error.message, false);
                    btn.disabled = false;
                    btn.textContent = 'Transfer Messages';
                });
        }

        function checkMessages() {
            fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=messages_summary'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const lines = [
                            'Current messages stats:',
                            'Total: ' + (data.total ?? data.messages ?? 0)
                        ];
                        if (data.message) {
                            lines.push(data.message);
                        }
                        showResult('messages_result', lines.join('\n'), true);
                    } else {
                        showResult('messages_result', 'Failed to fetch stats: ' + (data.message || 'unknown'), false);
                    }
                })
                .catch(error => {
                    showResult('messages_result', 'Error: ' + error.message, false);
                });
        }


        function transferContacts() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'Transferring...';

            fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=migrate_contacts'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const lines = [
                            'Contacts migrated successfully',
                            'Total: ' + (data.processed ?? data.total ?? 0),
                            'Inserted: ' + (data.inserted ?? 0),
                            'Updated: ' + (data.updated ?? 0),
                            'Unchanged: ' + (data.unchanged ?? 0),
                            'Skipped: ' + (data.skipped ?? 0)
                        ];
                        showResult('contacts_result', lines.join('\n'), true);
                        updateStats();
                    } else {
                        showResult('contacts_result', 'Migration failed: ' + (data.message || 'operation could not be completed'), false);
                    }
                })
                .catch(error => {
                    showResult('contacts_result', 'Error: ' + error.message, false);
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = 'Transfer Contact Us';
                });
        }

        function checkContacts() {
            fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=contacts_summary'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const lines = [
                            'Current contacts stats:',
                            'Total: ' + (data.total ?? data.contacts ?? 0)
                        ];
                        if (data.message) lines.push(data.message);
                        showResult('contacts_result', lines.join('\n'), true);
                    } else {
                        showResult('contacts_result', 'Failed to fetch stats: ' + (data.message || 'unknown'), false);
                    }
                })
                .catch(error => {
                    showResult('contacts_result', 'Error: ' + error.message, false);
                });
        }

        function transferPreviousSponsor() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل...';

            fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=migrate_previous_sponsor'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const lines = [
                            data.message || 'تم نقل داخل الإيواء بنجاح',
                            'الإجمالي: ' + (data.processed ?? 0),
                            'مضاف: ' + (data.inserted ?? 0),
                            'محدث: ' + (data.updated ?? 0),
                            'متجاوز: ' + (data.skipped ?? 0)
                        ];
                        showResult('previous_sponsor_result', lines.join('\n'), true);
                        updateStats();
                    } else {
                        showResult('previous_sponsor_result', 'فشل النقل: ' + (data.message || 'غير معروف'), false);
                    }
                })
                .catch(error => {
                    showResult('previous_sponsor_result', 'خطأ: ' + error.message, false);
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = 'نقل داخل الإيواء';
                });
        }

        function transferReservationSponsor() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل...';

            fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=migrate_reservation_sponsor'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const lines = [
                            data.message || 'تم نقل حجوزات نقل الكفالة بنجاح',
                            'الإجمالي: ' + (data.processed ?? 0),
                            'مضاف: ' + (data.inserted ?? 0),
                            'محدث: ' + (data.updated ?? 0),
                            'متجاوز: ' + (data.skipped ?? 0)
                        ];
                        showResult('reservation_sponsor_result', lines.join('\n'), true);
                        updateStats();
                    } else {
                        showResult('reservation_sponsor_result', 'فشل النقل: ' + (data.message || 'غير معروف'), false);
                    }
                })
                .catch(error => {
                    showResult('reservation_sponsor_result', 'خطأ: ' + error.message, false);
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = 'نقل حجوزات نقل الكفالة';
                });
        }

        function checkPreviousSponsor() {
            fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=previous_sponsor_summary'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const lines = [
                            'إجمالي القديم: ' + (data.old_total ?? 0),
                            'عقود الإيواء الجديدة: ' + (data.new_shelter_contracts ?? 0),
                            data.message || ''
                        ].filter(Boolean);
                        showResult('previous_sponsor_result', lines.join('\n'), true);
                    } else {
                        showResult('previous_sponsor_result', 'فشل الفحص: ' + (data.message || 'غير معروف'), false);
                    }
                })
                .catch(error => {
                    showResult('previous_sponsor_result', 'خطأ: ' + error.message, false);
                });
        }

        function transferAccommodationTypes() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'جاري النقل...';

            fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
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
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
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
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
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
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
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

        window.transferReservationsToOrders = transferReservationsToOrders;
        window.checkOrders = checkOrders;
        window.transferContracts = transferContracts;
        window.transferPreviousSponsor = transferPreviousSponsor;
        window.transferReservationSponsor = transferReservationSponsor;
        window.checkContracts = checkContracts;
        window.checkPreviousSponsor = checkPreviousSponsor;
        window.transferContractInfos = transferContractInfos;
        window.checkContractInfos = checkContractInfos;
        window.transferContractLogs = transferContractLogs;
        window.checkContractLogs = checkContractLogs;
        window.transferActivityLogs = transferActivityLogs;        
        window.checkActivityLogs = checkActivityLogs;
        window.transferMessages = transferMessages;
        window.checkMessages = checkMessages;
        window.transferQuestions = transferQuestions;
        window.checkQuestions = checkQuestions;
        // window.transferMessages = transferMessages;
        // window.checkMessages = checkMessages;
        window.transferCities = transferCities;
        window.checkCities = checkCities;
        window.transferContacts = transferContacts;
        window.checkContacts = checkContacts;
        window.transferAccommodationTypes = transferAccommodationTypes;
        window.checkAccommodationTypes = checkAccommodationTypes;
        window.transferAirports = transferAirports;
        window.checkAirports = checkAirports; // وظائف عامة
        function showResult(elementId, message, isSuccess) {
            const element = document.getElementById(elementId);
            element.style.display = 'block';
            element.className = 'result ' + (isSuccess ? 'success' : 'error');
            const safeMessage = message
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\n/g, '<br>');
            element.innerHTML = safeMessage;
        }

        function updateStats() {
            const sql = `
                SELECT
                    (SELECT COUNT(*) FROM users) as users,
                    (SELECT COUNT(*) FROM countries) as countries,
                    (SELECT COUNT(*) FROM cities) as cities,
                    (SELECT COUNT(*) FROM nationalities) as nationalities,
                    (SELECT COUNT(*) FROM nationalities_translations) as translations,
                    (SELECT COUNT(*) FROM recruitment_offices) as offices,
                    (SELECT COUNT(*) FROM recruitment_office_translations) as office_translations,
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
                    (SELECT COUNT(*) FROM contract_status_logs) as contract_logs,
                    (SELECT COUNT(*) FROM activity_log) as activity_logs,
                    (SELECT COUNT(*) FROM messages) as messages,
                    (SELECT COUNT(*) FROM questions) as questions,
                    (SELECT COUNT(*) FROM questions_translations) as question_translations,
                    (SELECT COUNT(*) FROM contacts) as contacts,
                    (SELECT COUNT(*) FROM accommodation_types) as accommodation_types,
                    (SELECT COUNT(*) FROM airports) as airports
            `;
            fetch('migration/file_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=execute_sql&sql=' + encodeURIComponent(sql)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.result && data.result[0]) {
                        const stats = data.result[0];
                        const order = [
                            'users',
                            'countries',
                            'cities',
                            'nationalities',
                            'translations',
                            'offices',
                            'office_translations',
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
                            'contract_logs',
                            'activity_logs',
                            'messages',
                            'questions',
                            'question_translations',
                            'contacts',
                            'airports',
                            'accommodation_types'
                        ];

                        // let numbers = document.querySelectorAll('.stat .number');
                        // order.forEach((key, index) => {
                        //     if (numbers[index]) {
                        //         numbers[index].textContent = stats[key] || '0';
                        //     }
                        // });

                        let numbers = document.querySelectorAll('.stat .number');
                        if (numbers.length >= 25) {
                            numbers[0].textContent = stats.users || '0';
                            numbers[1].textContent = stats.countries || '0';
                            numbers[2].textContent = stats.cities || '0';
                            numbers[3].textContent = stats.nationalities || '0';
                            numbers[4].textContent = stats.translations || '0';
                            numbers[5].textContent = stats.offices || '0';
                            numbers[6].textContent = stats.office_translations || '0';
                            numbers[7].textContent = stats.job_titles || '0';
                            numbers[8].textContent = stats.religions || '0';
                            numbers[9].textContent = stats.social_status || '0';
                            numbers[10].textContent = stats.skills || '0';
                            numbers[11].textContent = stats.experience || '0';
                            numbers[12].textContent = stats.ages || '0';
                            numbers[13].textContent = stats.languages || '0';
                            numbers[14].textContent = stats.maids || '0';
                            numbers[15].textContent = stats.contracts || '0';
                            numbers[16].textContent = stats.contract_infos || '0';
                            numbers[17].textContent = stats.contract_logs || '0';
                            numbers[18].textContent = stats.activity_logs || '0';
                            numbers[19].textContent = stats.messages || '0';
                            numbers[20].textContent = stats.questions || '0';
                            numbers[21].textContent = stats.question_translations || '0';
                            numbers[22].textContent = stats.contacts || '0';
                            numbers[23].textContent = stats.airports || '0';
                            numbers[24].textContent = stats.accommodation_types || '0';
                        }
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









