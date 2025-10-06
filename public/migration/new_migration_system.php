<?php
// نظام النقل الجديد والمحسن - بدون مشاكل الكاش
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚀 نظام النقل المحسن - بدون كاش</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            background: rgba(255,255,255,0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            margin-top: 20px;
            margin-bottom: 20px;
            padding: 0;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .main-content {
            padding: 30px;
        }
        .card {
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border-radius: 10px;
        }
        .btn-custom {
            border-radius: 8px;
            padding: 15px;
            font-size: 1.1em;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        .alert-success {
            border-left: 5px solid #28a745;
            background: #d4edda;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-sync-alt me-3"></i>نظام النقل الموحد</h1>
            <p class="mb-0">الإصدار المستقر - محدث بتاريخ <?php echo date('Y-m-d H:i'); ?></p>
        </div>

        <div class="main-content">
            <!-- تأكيد التحديث -->
            <div class="alert alert-success text-center mb-4">
                <h4><i class="fas fa-check-circle me-2"></i>النظام محدث ومستقر</h4>
                <p>تم إنشاء هذه الصفحة لتجنب مشاكل الكاش في المتصفح</p>
            </div>

            <!-- إعدادات قاعدة البيانات المرنة -->
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-database me-2"></i>إعدادات قاعدة البيانات المرنة
                        <span class="badge bg-warning text-dark float-end">NEW!</span>
                    </h5>
                </div>
                <div class="card-body bg-light">
                    <div class="row g-2">
                        <div class="col-md-2">
                            <label class="form-label small">Host:</label>
                            <input type="text" id="db_host" class="form-control" value="127.0.0.1">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label small">Port:</label>
                            <input type="text" id="db_port" class="form-control" value="8889">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Database:</label>
                            <select id="db_name" class="form-select" onchange="dbChanged()">
                                <option value="platinum_new">platinum_new</option>
                                <option value="platinum">platinum</option>
                                <option value="erp">erp</option>
                                <option value="erp_new">erp_new</option>
                                <option value="custom">أخرى...</option>
                            </select>
                            <input type="text" id="db_name_custom" class="form-control mt-2" placeholder="اسم قاعدة البيانات" style="display:none;">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Username:</label>
                            <input type="text" id="db_user" class="form-control" value="root">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Password:</label>
                            <input type="password" id="db_pass" class="form-control" value="root">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-success w-100" onclick="testConnection()">
                                <i class="fas fa-plug me-2"></i>اختبار
                            </button>
                        </div>
                    </div>
                    <div id="connection_status" class="mt-2"></div>
                </div>
            </div>

            <!-- أدوات تحضير الجدول -->
            <div class="card border-warning">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="fas fa-tools me-2"></i>أدوات تحضير الجدول</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <button type="button" class="btn btn-secondary btn-custom w-100" onclick="checkTableStructure()">
                                <i class="fas fa-search me-2"></i>فحص ترتيب الأعمدة
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-dark btn-custom w-100" onclick="debugFileProcessing()">
                                <i class="fas fa-bug me-2"></i>تحليل الملف
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-info btn-custom w-100" onclick="addRequiredColumns()">
                                <i class="fas fa-plus-circle me-2"></i>إضافة الأعمدة
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-warning btn-custom w-100" onclick="organizeTableColumns()">
                                <i class="fas fa-sort me-2"></i>ترتيب الأعمدة
                            </button>
                        </div>
                    </div>
                    <div class="row g-2 mt-2">
                        <div class="col-md-6">
                            <button type="button" class="btn btn-success btn-custom w-100" onclick="prepareUsersTable()">
                                <i class="fas fa-magic me-2"></i>تحضير شامل (إضافة + ترتيب)
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button type="button" class="btn btn-danger btn-custom w-100" onclick="fillMissingData()">
                                <i class="fas fa-database me-2"></i>ملء البيانات المفقودة
                            </button>
                        </div>
                    </div>

                    <!-- نتائج فحص البنية -->
                    <div id="structure_results" class="mt-3" style="display: none;"></div>
                </div>
            </div>

            <!-- التصدير والرفع التلقائي -->
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-rocket me-2"></i>التصدير والرفع التلقائي (الأسرع!)
                        <span class="badge bg-danger float-end">HOT!</span>
                    </h5>
                </div>
                <div class="card-body">
                    <!-- إعدادات القاعدة القديمة -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label">إعدادات القاعدة القديمة:</label>
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <input type="text" id="old_db_host" class="form-control form-control-sm" placeholder="Host" value="127.0.0.1">
                                </div>
                                <div class="col-md-2">
                                    <input type="text" id="old_db_port" class="form-control form-control-sm" placeholder="Port" value="8889">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" id="old_db_name" class="form-control form-control-sm" placeholder="Database" value="platinum">
                                </div>
                                <div class="col-md-2">
                                    <input type="text" id="old_db_user" class="form-control form-control-sm" placeholder="User" value="root">
                                </div>
                                <div class="col-md-2">
                                    <input type="password" id="old_db_pass" class="form-control form-control-sm" placeholder="Pass" value="root">
                                </div>
                            </div>
                        </div>
                            <div class="col-md-4">
                                <label class="form-label">الجدول للتصدير:</label>
                                <select id="export_table" class="form-select form-select-sm">
                                    <option value="users">users</option>
                                    <option value="customers">customers</option>
                                    <option value="countries">countries</option>
                                    <option value="cvs">cvs (السير الذاتية)</option>
                                    <option value="maids">maids (العاملات)</option>
                                    <option value="languages">languages (اللغات)</option>
                                    <option value="professions">professions (المهن)</option>
                                    <option value="religions">religions (الديانات)</option>
                                    <option value="nationalities">nationalities (الجنسيات)</option>
                                    <option value="orders">orders</option>
                                </select>
                            </div>
                    </div>

                    <!-- الأزرار الذكية -->
                    <div class="d-flex gap-2">
                        <button class="btn btn-info btn-custom flex-fill" onclick="exportFromOldDB()">
                            <i class="fas fa-download me-2"></i>تصدير كـ CSV فقط
                        </button>
                        <button class="btn btn-success btn-custom flex-fill" onclick="exportAndUpload('users')">
                            <i class="fas fa-users me-2"></i>تصدير + رفع Users (الكل)
                        </button>
                        <button class="btn btn-warning btn-custom flex-fill" onclick="exportAndUpload('customers')">
                            <i class="fas fa-user-plus me-2"></i>تصدير + دمج Customers
                        </button>
                    </div>

                    <!-- زر شامل للعمليتين -->
                    <div class="mt-2">
                        <button class="btn btn-danger btn-custom w-100" onclick="exportAndUploadBoth()">
                            <i class="fas fa-sync me-2"></i>عملية شاملة: Users + Customers (مرة واحدة)
                        </button>
                    </div>

                    <div id="export_results" class="mt-3" style="display: none;"></div>
                </div>
            </div>

            <!-- نقل جدول Countries -->
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-globe me-2"></i>نقل جدول Countries
                        <span class="badge bg-primary float-end">جديد</span>
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">جدول countries مرتبط مع users ويحتاج نقل كما هو إلى القاعدة الجديدة</p>

                    <div class="row g-2">
                        <div class="col-md-4">
                            <button class="btn btn-primary btn-custom w-100" onclick="exportAndUpload('countries')">
                                <i class="fas fa-globe me-2"></i>تصدير + رفع Countries
                            </button>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-info btn-custom w-100" onclick="checkCountriesTable()">
                                <i class="fas fa-search me-2"></i>فحص جدول Countries
                            </button>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-warning btn-custom w-100" onclick="prepareCountriesTable()">
                                <i class="fas fa-tools me-2"></i>تحضير جدول Countries
                            </button>
                        </div>
                    </div>

                    <div id="countries_results" class="mt-3" style="display: none;"></div>
                </div>
            </div>

            <!-- نقل جداول CV/Maids والجداول المرتبطة -->
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-id-card me-2"></i>نقل CV/Maids والجداول المرتبطة
                        <span class="badge bg-danger float-end">متقدم</span>
                        <a href="cv_migration_guide.html" target="_blank" class="btn btn-sm btn-outline-light float-end me-2">
                            <i class="fas fa-book me-1"></i>الدليل
                        </a>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6>تحليل الجداول المرتبطة</h6>
                        <p>قبل النقل نحتاج لفحص:</p>
                        <ul class="mb-2">
                            <li>جدول cvs/maids وحالات cv_status</li>
                            <li>nationalities و religions (الجداول المرجعية)</li>
                            <li>التحويلات والترجمات</li>
                            <li>العلاقات بين الجداول (national_id → nationality_id)</li>
                        </ul>

                        <div class="mt-2 p-2 bg-warning bg-opacity-25 rounded">
                            <strong>cv_status المهم:</strong>
                            <span class="badge bg-secondary me-1">0=غير محجوز</span>
                            <span class="badge bg-primary me-1">1=محجوز</span>
                            <span class="badge bg-info me-1">2=باك آوت</span>
                            <span class="badge bg-warning me-1">3=معلق</span>
                            <span class="badge bg-success me-1">4=تم الترحيل</span>
                            <span class="badge bg-danger me-1">5=هروب</span>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <button class="btn btn-dark btn-custom w-100" onclick="findCVTables()">
                                <i class="fas fa-search me-2"></i>اكتشاف جداول CV
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-info btn-custom w-100" onclick="analyzeMaidsStructure()">
                                <i class="fas fa-table me-2"></i>تحليل الهيكل
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-warning btn-custom w-100" onclick="prepareRelatedTables()">
                                <i class="fas fa-cogs me-2"></i>تحضير الجداول
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-success btn-custom w-100" onclick="migrateAllCVTables()">
                                <i class="fas fa-rocket me-2"></i>نقل شامل
                            </button>
                        </div>
                    </div>

                    <!-- خيارات متقدمة -->
                    <details class="mb-3">
                        <summary class="btn btn-outline-secondary btn-sm">خيارات متقدمة</summary>
                        <div class="mt-2">
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <label class="form-label small">اسم جدول CV في القديم:</label>
                                    <input type="text" id="old_cv_table" class="form-control form-control-sm" value="cvs" placeholder="cvs, maids, employees">
                                    <small class="text-info">استخدم "اكتشاف جداول CV" أولاً</small>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">معالجة cv_status:</label>
                                    <select id="cv_status_mapping" class="form-select form-select-sm">
                                        <option value="preserve">الحفاظ على القيم كما هي</option>
                                        <option value="convert_to_available">تحويل إلى available (boolean)</option>
                                        <option value="map_to_status">تحويل إلى status</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">معالجة الـ IDs:</label>
                                    <select id="id_mapping_strategy" class="form-select form-select-sm">
                                        <option value="preserve">الحفاظ على الـ IDs</option>
                                        <option value="auto_increment">إعادة ترقيم تلقائي</option>
                                        <option value="custom_mapping">تحويل مخصص</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">معالجة الأعمدة المفقودة:</label>
                                    <select id="missing_columns_strategy" class="form-select form-select-sm">
                                        <option value="skip">تجاهل</option>
                                        <option value="add_auto">إضافة تلقائي</option>
                                        <option value="ask_user">السؤال قبل الإضافة</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </details>

                    <div id="maids_analysis_results" class="mt-3" style="display: none;"></div>
                </div>
            </div>

            <!-- رفع الملفات اليدوي (النظام القديم) -->
            <div class="card border-secondary">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-upload me-2"></i>رفع الملفات اليدوي (للطوارئ فقط)</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">ملف Users (CSV):</label>
                            <input type="file" class="form-control" name="users_file" id="users_file" accept=".csv" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ملف Customers (CSV) - اختياري:</label>
                            <input type="file" class="form-control" name="customers_file" id="customers_file" accept=".csv" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="button" class="btn btn-primary btn-custom w-100" onclick="uploadAndProcess()">
                            <i class="fas fa-cloud-upload-alt me-2"></i>رفع ومعالجة الملفات
                        </button>
                    </div>
                    <div id="upload_results" class="mt-3" style="display: none;"></div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript functions (محسن)

        function dbChanged() {
            const select = document.getElementById('db_name');
            const customInput = document.getElementById('db_name_custom');

            if (select.value === 'custom') {
                customInput.style.display = 'block';
                customInput.focus();
            } else {
                customInput.style.display = 'none';
            }
        }

        function getDbParams() {
            const dbName = document.getElementById('db_name');
            const actualDbName = dbName.value === 'custom'
                ? document.getElementById('db_name_custom').value
                : dbName.value;

            return {
                db_host: document.getElementById('db_host').value,
                db_port: document.getElementById('db_port').value,
                db_name: actualDbName,
                db_user: document.getElementById('db_user').value,
                db_pass: document.getElementById('db_pass').value
            };
        }

        async function testConnection() {
            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            const params = getDbParams();
            const statusDiv = document.getElementById('connection_status');

            try {
                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=test_connection&' + new URLSearchParams(params)
                });

                const data = await response.json();

                if (data.success) {
                    statusDiv.innerHTML = `<div class="alert alert-success">الاتصال ناجح! القاعدة: ${params.db_name} | الجداول: ${data.table_count} | المستخدمين: ${data.user_count}</div>`;
                } else {
                    statusDiv.innerHTML = `<div class="alert alert-danger">فشل الاتصال: ${data.message}</div>`;
                }
            } catch (error) {
                statusDiv.innerHTML = `<div class="alert alert-danger">❌ خطأ: ${error.message}</div>`;
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plug me-2"></i>اختبار';
        }

        async function addRequiredColumns() {
            if (!confirm('هل تريد إضافة جميع الأعمدة المطلوبة؟')) return;

            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري الإضافة...';

            try {
                const params = getDbParams();
                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=add_system_status_columns&' + new URLSearchParams(params)
                });

                const data = await response.json();
                alert(data.success ? 'تم بنجاح: ' + data.message : 'خطأ: ' + data.message);
            } catch (error) {
                alert('❌ خطأ: ' + error.message);
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus-circle me-2"></i>1. إضافة الأعمدة المطلوبة';
        }

        async function checkTableStructure() {
            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري الفحص...';

            try {
                const params = getDbParams();
                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=check_table_structure&' + new URLSearchParams(params)
                });

                const data = await response.json();

                const resultsDiv = document.getElementById('structure_results');
                resultsDiv.style.display = 'block';

                if (data.success) {
                    const alertClass = data.is_correct_order ? 'alert-success' : 'alert-warning';
                    let columnsHTML = data.current_order.slice(0, 10).map((col, i) =>
                        `<span class="badge bg-secondary me-1">${i+1}. ${col}</span>`
                    ).join('');

                    if (data.current_order.length > 10) {
                        columnsHTML += `<span class="badge bg-info">... و ${data.current_order.length - 10} أعمدة أخرى</span>`;
                    }

                    resultsDiv.innerHTML = `
                        <div class="alert ${alertClass}">
                            <h6>${data.message}</h6>
                            <p><strong>إجمالي الأعمدة:</strong> ${data.total_columns}</p>
                            <p><strong>created_at في الموضع:</strong> ${data.created_at_position}</p>
                            <p><strong>updated_at في الموضع:</strong> ${data.updated_at_position}</p>

                            ${data.order_issues.length > 0 ? `
                                <p><strong>المشاكل:</strong></p>
                                <ul>${data.order_issues.map(issue => `<li>${issue}</li>`).join('')}</ul>
                            ` : ''}

                            <details class="mt-2">
                                <summary><strong>ترتيب الأعمدة الحالي (أول 10)</strong></summary>
                                <div class="mt-2">${columnsHTML}</div>
                            </details>
                        </div>
                    `;
                } else {
                    resultsDiv.innerHTML = `<div class="alert alert-danger">❌ ${data.message}</div>`;
                }
            } catch (error) {
                document.getElementById('structure_results').innerHTML = `<div class="alert alert-danger">❌ خطأ: ${error.message}</div>`;
                document.getElementById('structure_results').style.display = 'block';
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-search me-2"></i>فحص ترتيب الأعمدة';
        }

        async function debugFileProcessing() {
            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري التحليل...';

            try {
                const params = getDbParams();
                params.action = 'debug_file_processing';
                params.debug_file = 'users_exported_2025-09-18_04-56-57.csv'; // الملف المشكل

                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(params)
                });

                const data = await response.json();

                const resultsDiv = document.getElementById('structure_results');
                resultsDiv.style.display = 'block';

                if (data.success) {
                    resultsDiv.innerHTML = `
                        <div class="alert alert-info">
                            <h6>🔍 تحليل مشاكل المعالجة</h6>
                            <p><strong>الملف:</strong> ${data.file_name} (${data.file_size})</p>
                            <p><strong>الأسطر:</strong> ${data.total_lines} سطر</p>
                            <p><strong>أعمدة CSV:</strong> ${data.headers_count}</p>
                            <p><strong>أعمدة الجدول:</strong> ${data.table_columns_count}</p>
                            <p><strong>أعمدة مشتركة:</strong> ${data.common_columns.length}</p>
                            <p><strong>أعمدة مفقودة في الجدول:</strong> ${data.missing_in_table.length}</p>

                            <details class="mt-2">
                                <summary><strong>الأعمدة المفقودة (سبب المشكلة!)</strong></summary>
                                <div class="mt-2">
                                    ${data.missing_in_table.map(col => `<span class="badge bg-danger me-1">${col}</span>`).join('')}
                                </div>
                            </details>

                            <details class="mt-2">
                                <summary><strong>عينة البيانات (أول 3 سجلات)</strong></summary>
                                <div class="mt-2">
                                    ${data.sample_data.slice(0, 3).map(sample => `
                                        <div class="border p-2 mb-2">
                                            <p><strong>سجل ${sample.row_number}:</strong></p>
                                            <p>ID: ${sample.id} | Name: ${sample.name} | Phone: ${sample.phone}</p>
                                            <p>يمر الفحص الأساسي: ${sample.basic_check ? '✅' : '❌'}</p>
                                        </div>
                                    `).join('')}
                                </div>
                            </details>
                        </div>
                    `;
                } else {
                    resultsDiv.innerHTML = `<div class="alert alert-danger">❌ ${data.message}</div>`;
                }
            } catch (error) {
                document.getElementById('structure_results').innerHTML = `<div class="alert alert-danger">❌ خطأ: ${error.message}</div>`;
                document.getElementById('structure_results').style.display = 'block';
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-bug me-2"></i>تحليل الملف';
        }

        async function organizeTableColumns() {
            if (!confirm('🔧 ترتيب الأعمدة المحسن\n\nسيتم:\n✅ ترتيب جميع الأعمدة بالتسلسل الصحيح\n✅ وضع created_at و updated_at في النهاية\n✅ الحفاظ على جميع البيانات\n\nالمتابعة؟')) return;

            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري الترتيب...';

            try {
                const params = getDbParams();
                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=reorganize_table&' + new URLSearchParams(params)
                });

                const data = await response.json();

                if (data.success) {
                    alert('✅ ' + data.message + '\n\n📋 تفاصيل:\n' +
                          `• أعمدة مضافة: ${data.added_columns?.length || 0}\n` +
                          `• خطوات ترتيب: ${data.applied_steps || 0}`);

                    // إعادة فحص البنية لعرض النتيجة
                    setTimeout(() => checkTableStructure(), 1000);
                } else {
                    alert('❌ ' + data.message);
                }
            } catch (error) {
                alert('❌ خطأ: ' + error.message);
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sort me-2"></i>ترتيب الأعمدة (محسن)';
        }

        async function prepareUsersTable() {
            if (!confirm('هل تريد التحضير الشامل (إضافة + ترتيب)؟')) return;

            await addRequiredColumns();
            setTimeout(() => organizeTableColumns(), 1000);
        }

        async function fillMissingData() {
            alert('⚠️ هذه الوظيفة قيد التطوير. استخدم التصدير التلقائي بدلاً من ذلك.');
        }

        async function exportFromOldDB() {
            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري التصدير...';

            const params = {
                action: 'export_from_old_db',
                export_table: document.getElementById('export_table').value,
                old_db_host: document.getElementById('old_db_host').value,
                old_db_port: document.getElementById('old_db_port').value,
                old_db_name: document.getElementById('old_db_name').value,
                old_db_user: document.getElementById('old_db_user').value,
                old_db_pass: document.getElementById('old_db_pass').value,
                ...getDbParams()
            };

            try {
                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(params)
                });

                const data = await response.json();

                const resultsDiv = document.getElementById('export_results');
                resultsDiv.style.display = 'block';

                if (data.success) {
                    resultsDiv.innerHTML = `
                        <div class="alert alert-success">
                            <h6>✅ تم التصدير بنجاح!</h6>
                            <p><strong>الملف:</strong> ${data.file_name}</p>
                            <p><strong>السجلات:</strong> ${data.record_count}</p>
                            <p><strong>الحجم:</strong> ${data.file_size}</p>
                        </div>
                    `;
                } else {
                    resultsDiv.innerHTML = `<div class="alert alert-danger">❌ ${data.message}</div>`;
                }
            } catch (error) {
                document.getElementById('export_results').innerHTML = `<div class="alert alert-danger">❌ خطأ: ${error.message}</div>`;
                document.getElementById('export_results').style.display = 'block';
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-download me-2"></i>تصدير كـ CSV';
        }

        async function exportAndUpload(table) {
            if (!confirm(`🚀 التصدير والرفع التلقائي لجدول ${table}؟\n\nسيتم:\n✅ تصدير من القاعدة القديمة\n✅ معالجة البيانات تلقائياً\n✅ رفع للقاعدة الجديدة\n\nالمتابعة؟`)) {
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>تصدير ورفع...';

            try {
                // Step 1: Export
                const exportParams = {
                    action: 'export_from_old_db',
                    export_table: table,
                    old_db_host: document.getElementById('old_db_host').value,
                    old_db_port: document.getElementById('old_db_port').value,
                    old_db_name: document.getElementById('old_db_name').value,
                    old_db_user: document.getElementById('old_db_user').value,
                    old_db_pass: document.getElementById('old_db_pass').value,
                    ...getDbParams()
                };

                const exportResponse = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(exportParams)
                });

                const exportData = await exportResponse.json();

                if (!exportData.success) {
                    throw new Error('فشل التصدير: ' + exportData.message);
                }

                // Step 2: Process and Upload
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>معالجة البيانات...';

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

                const resultsDiv = document.getElementById('export_results');
                resultsDiv.style.display = 'block';

                if (processData.success) {
                    resultsDiv.innerHTML = `
                        <div class="alert alert-success">
                            <h5>🎉 عملية تلقائية ناجحة!</h5>
                            <p><strong>📤 تم تصدير:</strong> ${exportData.record_count} سجل من ${table}</p>
                            <p><strong>📥 تم رفع:</strong> ${processData.processed} سجل</p>
                            <p><strong>📊 النتيجة:</strong> ${processData.message}</p>
                            <p><strong>📁 الملف:</strong> ${exportData.file_name}</p>
                            <p><strong>💾 الحجم:</strong> ${exportData.file_size}</p>
                        </div>
                    `;
                } else {
                    resultsDiv.innerHTML = `
                        <div class="alert alert-warning">
                            <h6>⚠️ نجح التصدير لكن فشل الرفع</h6>
                            <p><strong>تم تصدير:</strong> ${exportData.record_count} سجل بنجاح</p>
                            <p><strong>مشكلة الرفع:</strong> ${processData.message}</p>
                            <details class="mt-2">
                                <summary>تفاصيل المشكلة</summary>
                                <pre>${JSON.stringify(processData, null, 2)}</pre>
                            </details>
                        </div>
                    `;
                }

            } catch (error) {
                document.getElementById('export_results').innerHTML = `<div class="alert alert-danger">❌ خطأ: ${error.message}</div>`;
                document.getElementById('export_results').style.display = 'block';
            }

            btn.disabled = false;
            btn.innerHTML = `<i class="fas fa-magic me-2"></i>تصدير + رفع ${table} مباشرة`;
        }

        // وظائف جدول Countries
        async function checkCountriesTable() {
            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري الفحص...';

            try {
                const params = getDbParams();
                params.action = 'check_countries_table';

                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(params)
                });

                const data = await response.json();

                const resultsDiv = document.getElementById('countries_results');
                resultsDiv.style.display = 'block';

                if (data.success) {
                    resultsDiv.innerHTML = `
                        <div class="alert alert-info">
                            <h6>فحص جدول Countries</h6>
                            <p><strong>الجدول موجود:</strong> ${data.table_exists ? 'نعم' : 'لا'}</p>
                            <p><strong>عدد السجلات:</strong> ${data.record_count || 0}</p>
                            <p><strong>الأعمدة:</strong> ${data.columns ? data.columns.join(', ') : 'غير متاح'}</p>
                            ${data.table_exists ? '' : '<p class="text-warning">سيتم إنشاء الجدول تلقائياً عند النقل</p>'}
                        </div>
                    `;
                } else {
                    resultsDiv.innerHTML = `<div class="alert alert-warning">${data.message}</div>`;
                }
            } catch (error) {
                document.getElementById('countries_results').innerHTML = `<div class="alert alert-danger">خطأ: ${error.message}</div>`;
                document.getElementById('countries_results').style.display = 'block';
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-search me-2"></i>فحص جدول Countries';
        }

        async function prepareCountriesTable() {
            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري التحضير...';

            try {
                const params = getDbParams();
                params.action = 'prepare_countries_table';

                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(params)
                });

                const data = await response.json();

                if (data.success) {
                    alert('تم تحضير جدول Countries بنجاح!\n\n' + data.message);
                    // إعادة فحص الجدول
                    setTimeout(() => checkCountriesTable(), 1000);
                } else {
                    alert('خطأ في تحضير جدول Countries: ' + data.message);
                }
            } catch (error) {
                alert('خطأ: ' + error.message);
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-tools me-2"></i>تحضير جدول Countries';
        }

        // وظائف CV/Maids
        async function findCVTables() {
            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري البحث...';

            try {
                const response = await fetch('find_cv_tables.php');
                const data = await response.json();

                const resultsDiv = document.getElementById('maids_analysis_results');
                resultsDiv.style.display = 'block';

                if (data.success) {
                    let potentialTablesHTML = '';
                    if (data.potential_cv_tables && data.potential_cv_tables.length > 0) {
                        potentialTablesHTML = data.potential_cv_tables.map(table => `
                            <tr onclick="selectCVTable('${table.table}')" style="cursor:pointer;" class="table-hover">
                                <td><strong>${table.table}</strong></td>
                                <td>${table.count}</td>
                                <td>${table.likelihood}</td>
                                <td>${table.cv_related_columns.join(', ') || 'لا توجد'}</td>
                            </tr>
                        `).join('');
                    } else {
                        potentialTablesHTML = '<tr><td colspan="4" class="text-center text-muted">لم يتم العثور على جداول CV محتملة</td></tr>';
                    }

                    let relatedTablesHTML = '';
                    if (data.related_tables && data.related_tables.length > 0) {
                        relatedTablesHTML = data.related_tables.map(table => `
                            <tr>
                                <td><strong>${table.table}</strong></td>
                                <td>${table.count}</td>
                                <td>${table.type}</td>
                                <td>${table.columns.length}</td>
                            </tr>
                        `).join('');
                    }

                    resultsDiv.innerHTML = `
                        <div class="alert alert-success">
                            <h6>البحث مكتمل - ${data.total_tables} جدول في القاعدة</h6>

                            <h6 class="mt-3">الجداول المحتملة لـ CV:</h6>

                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr><th>الجدول</th><th>السجلات</th><th>الاحتمالية</th><th>الأعمدة المرتبطة</th></tr>
                                    </thead>
                                    <tbody>${potentialTablesHTML}</tbody>
                                </table>


                            ${data.top_candidate ? `
                                <div class="alert alert-info mt-3">
                                    <h6>التوصية:</h6>
                                    <p>${data.recommendation}</p>
                                    <button class="btn btn-sm btn-primary" onclick="selectCVTable('${data.top_candidate.table}')">
                                        اختيار ${data.top_candidate.table}
                                    </button>
                                </div>
                            ` : ''}

                            ${relatedTablesHTML ? `
                                <h6 class="mt-3">الجداول المرتبطة:</h6>

                                    <table class="table table-sm">
                                        <thead><tr><th>الجدول</th><th>السجلات</th><th>النوع</th><th>الأعمدة</th></tr></thead>
                                        <tbody>${relatedTablesHTML}</tbody>
                                    </table>

                            ` : ''}
                        </div>
                    `;
                } else {
                    resultsDiv.innerHTML = `<div class="alert alert-danger">خطأ: ${data.message}</div>`;
                }
            } catch (error) {
                document.getElementById('maids_analysis_results').innerHTML = `<div class="alert alert-danger">خطأ: ${error.message}</div>`;
                document.getElementById('maids_analysis_results').style.display = 'block';
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-search me-2"></i>اكتشاف جداول CV';
        }

        function selectCVTable(tableName) {
            document.getElementById('old_cv_table').value = tableName;
            alert(`تم تحديد جدول CV: ${tableName}\n\nيمكنك الآن المتابعة مع "تحليل الهيكل" أو "نقل شامل"`);
        }

        async function analyzeMaidsStructure() {
            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري التحليل...';

            try {
                const response = await fetch('analyze_maids_structure.php');
                const data = await response.json();

                const resultsDiv = document.getElementById('maids_analysis_results');
                resultsDiv.style.display = 'block';

                if (data.success) {
                    let tablesHTML = '';
                    Object.entries(data.tables_analysis).forEach(([table, analysis]) => {
                        const oldStatus = analysis.old_exists ? `موجود (${analysis.old_count} سجل)` : 'غير موجود';
                        const newStatus = analysis.new_exists ? `موجود (${analysis.new_count} سجل)` : 'غير موجود';

                        tablesHTML += `
                            <tr>
                                <td><strong>${table}</strong></td>
                                <td>${oldStatus}</td>
                                <td>${newStatus}</td>
                                <td>${analysis.old_columns?.length || 0}</td>
                                <td>${analysis.new_columns?.length || 0}</td>
                            </tr>
                        `;
                    });

                    let cvStatusHTML = '';
                    if (data.cv_status_breakdown && Array.isArray(data.cv_status_breakdown)) {
                        cvStatusHTML = data.cv_status_breakdown.map(status =>
                            `<tr><td>${status.cv_status}</td><td>${status.status_name}</td><td>${status.count}</td></tr>`
                        ).join('');
                    }

                    resultsDiv.innerHTML = `
                        <div class="alert alert-success">
                            <h6>تحليل مكتمل - ${data.timestamp}</h6>

                            <h6 class="mt-3">الجداول:</h6>

                                <table class="table table-sm">
                                    <thead>
                                        <tr><th>الجدول</th><th>القديم</th><th>الجديد</th><th>أعمدة قديمة</th><th>أعمدة جديدة</th></tr>
                                    </thead>
                                    <tbody>${tablesHTML}</tbody>
                                </table>


                            ${cvStatusHTML ? `
                                <h6 class="mt-3">حالات CV:</h6>
                                    <table class="table table-sm">
                                        <thead><tr><th>الرقم</th><th>الحالة</th><th>العدد</th></tr></thead>
                                        <tbody>${cvStatusHTML}</tbody>
                                    </table>
                            ` : '<p class="text-warning">لم يتم العثور على بيانات cv_status</p>'}

                            <div class="mt-3">
                                <h6>استراتيجية النقل المقترحة:</h6>
                                <ol>
                                    ${data.migration_strategy ? Object.values(data.migration_strategy).map(step => `<li>${step}</li>`).join('') : ''}
                                </ol>
                            </div>
                        </div>
                    `;
                } else {
                    resultsDiv.innerHTML = `<div class="alert alert-danger">خطأ: ${data.message}</div>`;
                }
            } catch (error) {
                document.getElementById('maids_analysis_results').innerHTML = `<div class="alert alert-danger">خطأ: ${error.message}</div>`;
                document.getElementById('maids_analysis_results').style.display = 'block';
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-search me-2"></i>تحليل هيكل CV/Maids';
        }

        async function prepareRelatedTables() {
            if (!confirm('تحضير الجداول المرتبطة\n\nسيتم:\n- فحص nationalities, religions\n- إنشاء الجداول المفقودة\n- تحضير العلاقات\n\nالمتابعة؟')) return;

            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري التحضير...';

            try {
                const params = getDbParams();
                params.action = 'prepare_related_tables';

                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(params)
                });

                const data = await response.json();

                if (data.success) {
                    alert('تم تحضير الجداول المرتبطة بنجاح!\n\n' + data.message);
                    // إعادة تشغيل التحليل
                    setTimeout(() => analyzeMaidsStructure(), 1000);
                } else {
                    alert('خطأ في تحضير الجداول: ' + data.message);
                }
            } catch (error) {
                alert('خطأ: ' + error.message);
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-cogs me-2"></i>تحضير الجداول المرتبطة';
        }

        async function migrateAllCVTables() {
            if (!confirm('نقل CV شامل\n\nسيتم نقل جميع الجداول المرتبطة:\n- nationalities + translations\n- religions + translations  \n- maids/cv مع معالجة cv_status\n\nهذا يحتاج دقة عالية!\n\nالمتابعة؟')) return;

            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>نقل شامل...';

            const resultsDiv = document.getElementById('maids_analysis_results');
            resultsDiv.style.display = 'block';
            resultsDiv.innerHTML = '<div class="alert alert-info">بدء النقل الشامل لجداول CV...</div>';

            try {
                const cvTable = document.getElementById('old_cv_table').value;
                const cvStatusMapping = document.getElementById('cv_status_mapping').value;
                const idMappingStrategy = document.getElementById('id_mapping_strategy').value;

                const params = {
                    action: 'migrate_cv_comprehensive',
                    old_cv_table: cvTable,
                    cv_status_mapping: cvStatusMapping,
                    id_mapping_strategy: idMappingStrategy,
                    old_db_host: document.getElementById('old_db_host').value,
                    old_db_port: document.getElementById('old_db_port').value,
                    old_db_name: document.getElementById('old_db_name').value,
                    old_db_user: document.getElementById('old_db_user').value,
                    old_db_pass: document.getElementById('old_db_pass').value,
                    ...getDbParams()
                };

                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(params)
                });

                const data = await response.json();

                if (data.success) {
                    resultsDiv.innerHTML = `
                        <div class="alert alert-success">
                            <h5>النقل الشامل لـ CV مكتمل!</h5>
                            <div class="mt-3">
                                ${data.results ? Object.entries(data.results).map(([table, result]) => `
                                    <p><strong>${table}:</strong> ${result.processed || 0} سجل</p>
                                `).join('') : ''}
                            </div>
                            <p class="mt-3"><strong>النتيجة:</strong> ${data.message}</p>
                        </div>
                    `;
                } else {
                    resultsDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <h6>خطأ في النقل الشامل</h6>
                            <p>${data.message}</p>
                        </div>
                    `;
                }

            } catch (error) {
                resultsDiv.innerHTML = `<div class="alert alert-danger">خطأ: ${error.message}</div>`;
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-rocket me-2"></i>نقل CV شامل';
        }

        // عملية شاملة: Users + Customers
        async function exportAndUploadBoth() {
            if (!confirm('العملية الشاملة\n\nسيتم:\n- تصدير 2300+ Users من القاعدة القديمة\n- معالجة ورفع جميع Users\n- تصدير جدول Customers\n- دمج Customers في Users\n\nالمتابعة؟')) {
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>عملية شاملة...';

            const resultsDiv = document.getElementById('export_results');
            resultsDiv.style.display = 'block';
            resultsDiv.innerHTML = '<div class="alert alert-info">بدء العملية الشاملة...</div>';

            try {
                // Step 1: Users
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>خطوة 1: تصدير Users...';

                const usersResult = await performExportAndUpload('users');

                if (!usersResult.success) {
                    throw new Error('فشل في معالجة Users: ' + usersResult.message);
                }

                // Step 2: Customers
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>خطوة 2: تصدير Customers...';

                const customersResult = await performExportAndUpload('customers');

                // النتيجة النهائية
                resultsDiv.innerHTML = `
                    <div class="alert alert-success">
                        <h5>العملية الشاملة مكتملة بنجاح!</h5>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h6>Users:</h6>
                                <p>تصدير: ${usersResult.exportData?.record_count || 0} سجل</p>
                                <p>معالجة: ${usersResult.processData?.processed || 0} سجل</p>
                            </div>
                            <div class="col-md-6">
                                <h6>Customers:</h6>
                                <p>تصدير: ${customersResult.exportData?.record_count || 0} سجل</p>
                                <p>دمج: ${customersResult.processData?.processed || 0} في Users</p>
                            </div>
                        </div>

                        <p class="mt-3"><strong>النتيجة:</strong> تم نقل ودمج جميع البيانات بنجاح!</p>
                    </div>
                `;

            } catch (error) {
                resultsDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h6>❌ خطأ في العملية الشاملة</h6>
                        <p>${error.message}</p>
                    </div>
                `;
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync me-2"></i>عملية شاملة: Users + Customers (مرة واحدة)';
        }

        // دالة مساعدة للتصدير والرفع
        async function performExportAndUpload(table) {
            // Step 1: Export
            const exportParams = {
                action: 'export_from_old_db',
                export_table: table,
                old_db_host: document.getElementById('old_db_host').value,
                old_db_port: document.getElementById('old_db_port').value,
                old_db_name: document.getElementById('old_db_name').value,
                old_db_user: document.getElementById('old_db_user').value,
                old_db_pass: document.getElementById('old_db_pass').value,
                ...getDbParams()
            };

            const exportResponse = await fetch('file_processor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams(exportParams)
            });

            const exportData = await exportResponse.json();

            if (!exportData.success) {
                throw new Error(`فشل تصدير ${table}: ` + exportData.message);
            }

            // Step 2: Process
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

            return {
                success: processData.success,
                exportData: exportData,
                processData: processData,
                message: processData.message
            };
        }

        async function uploadAndProcess() {
            const usersFile = document.getElementById('users_file').files[0];
            const customersFile = document.getElementById('customers_file').files[0];

            if (!usersFile) {
                alert('يرجى اختيار ملف Users على الأقل');
                return;
            }

            if (!confirm('رفع ومعالجة الملفات؟\n\nسيتم:\n✅ رفع الملفات\n✅ معالجة البيانات\n✅ تطبيق التحويلات\n\nالمتابعة؟')) return;

            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري الرفع...';

            const formData = new FormData();
            formData.append('action', 'process_files');
            formData.append('files[]', usersFile);
            if (customersFile) {
                formData.append('files[]', customersFile);
            }

            // Add DB params
            const dbParams = getDbParams();
            Object.entries(dbParams).forEach(([key, value]) => {
                formData.append(key, value);
            });

            try {
                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();

                const resultsDiv = document.getElementById('upload_results');
                resultsDiv.style.display = 'block';

                if (data.success) {
                    resultsDiv.innerHTML = `
                        <div class="alert alert-success">
                            <h6>✅ تم الرفع والمعالجة بنجاح!</h6>
                            <p><strong>المعالج:</strong></p>
                            <ul>
                                ${data.files_processed ? data.files_processed.map(file =>
                                    `<li><strong>${file.name}:</strong> ${file.processed} سجل (${file.type})</li>`
                                ).join('') : ''}
                            </ul>
                            <p><strong>الإجمالي:</strong> Users: ${data.total_users || 0} | Customers: ${data.total_customers || 0}</p>
                            <p>${data.message}</p>
                        </div>
                    `;
                } else {
                    resultsDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <h6>❌ خطأ في المعالجة</h6>
                            <p>${data.message}</p>
                            <details class="mt-2">
                                <summary>تفاصيل الخطأ</summary>
                                <pre>${JSON.stringify(data, null, 2)}</pre>
                            </details>
                        </div>
                    `;
                }
            } catch (error) {
                const resultsDiv = document.getElementById('upload_results');
                resultsDiv.style.display = 'block';
                resultsDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h6>❌ خطأ في الاتصال</h6>
                        <p><strong>الخطأ:</strong> ${error.message}</p>
                        <p><strong>التفاصيل:</strong> تحقق من أن الخادم يعمل وأن ملفات PHP تعمل بشكل صحيح</p>
                    </div>
                `;
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-cloud-upload-alt me-2"></i>رفع ومعالجة الملفات';
        }

        // Test connection on page load
        window.addEventListener('load', () => {
            setTimeout(testConnection, 1000);
        });

    </script>
</body>
</html>
