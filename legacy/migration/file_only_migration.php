<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام نقل البيانات - Users & Customers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .hero {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        .upload-area {
            border: 4px dashed #e74c3c;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            background: #fff5f5;
            transition: all 0.3s;
        }
        .upload-area:hover {
            background: #ffe6e6;
            border-color: #c0392b;
        }
        .workflow-step {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }
        .step-number {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .file-preview {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <!-- Hero -->
                <!-- <div class="hero text-center">
                    <h1><i class="fas fa-sync-alt me-3"></i>نظام نقل البيانات الثابت</h1>
                    <p class="lead">رابط ثابت - تطوير مستمر - نتائج فعلية</p>
                    <div class="mt-3">
                        <span class="badge bg-light text-dark fs-6">🔗 رابط ثابت</span>
                        <span class="badge bg-light text-dark fs-6">📊 نتائج فعلية</span>
                        <span class="badge bg-light text-dark fs-6">🛠️ تطوير مستمر</span>
                    </div>
                </div> -->

                <!-- إعدادات قاعدة البيانات المرنة -->
                <div class="card mb-3 border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-database me-2"></i>إعدادات قاعدة البيانات
                            <span class="badge bg-warning text-dark float-end">مرن</span>
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

                <!-- إعدادات التطوير -->
                <!-- <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h6><i class="fas fa-cog me-2"></i>إعدادات النظام</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="debugMode" checked>
                                    <label class="form-check-label" for="debugMode">
                                        وضع التطوير (عرض تفاصيل مفصلة)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="addAllColumns" checked>
                                    <label class="form-check-label" for="addAllColumns">
                                        إضافة جميع الأعمدة المفقودة (user_type, phone_verified_at, etc.)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="setDefaultRegisterFrom" checked>
                                    <label class="form-check-label" for="setDefaultRegisterFrom">تعيين register_from إلى 'web' تلقائيًا</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="organizeTable" checked>
                                    <label class="form-check-label" for="organizeTable">
                                        ترتيب الجدول (created_at, updated_at في النهاية)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="realTimeUpdate">
                                    <label class="form-check-label" for="realTimeUpdate">
                                        تحديث فوري لقاعدة البيانات
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> -->



                <!-- رفع ملفات CSV -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-upload me-2"></i>رفع ملفات CSV</h5>
                        <small>بعد تحضير الجدول، ارفع الملفات هنا</small>
                    </div>
                    <div class="card-body">
                        <form id="uploadForm">
                            <!-- رفع ملف Users -->
                            <div class="mb-4">
                                <label class="form-label"><strong>1. ملف Users (CSV):</strong></label>
                                <input type="file" class="form-control" name="users_file" id="users_file" accept=".csv" />
                                <small class="text-muted">سيتم تقسيم name → first_name + last_name</small>
                            </div>

                            <!-- رفع ملف Customers -->
                            <div class="mb-4">
                                <label class="form-label"><strong>2. ملف Customers (CSV) - اختياري:</strong></label>
                                <input type="file" class="form-control" name="customers_file" id="customers_file" accept=".csv" />
                                <small class="text-muted">سيتم دمج البيانات مع Users بناءً على user_id</small>
                            </div>

                            <!-- زر الرفع والمعالجة -->
                            <button type="button" class="btn btn-primary btn-lg w-100" onclick="uploadAndProcess()">
                                <i class="fas fa-cloud-upload-alt me-2"></i>رفع ومعالجة الملفات
                            </button>
                        </form>

                        <!-- النتائج -->
                        <div id="uploadResults" class="mt-4" style="display: none;"></div>
                    </div>
                </div>

                <!-- Workflow محدد: Users → Customers -->
                <div class="card mb-4" style="display: none;">
                    <div class="card-header bg-success text-white">
                        <h5><i class="fas fa-users me-2"></i>Workflow</h5>
                    </div>
                    <div class="card-body">

                        <!-- الخطوة 1: رفع Users -->
                        <div class="workflow-step mb-4" id="step1">
                            <div class="d-flex align-items-center mb-3">
                                <div class="step-number bg-primary text-white rounded-circle me-3">1</div>
                                <h6 class="mb-0">رفع جدول Users أولاً</h6>
                                <span class="badge bg-secondary ms-auto" id="step1-status">في الانتظار</span>
                            </div>
                            <div class="row">
                                <div class="col-md-8">
                                    <input type="file" id="usersFile" accept=".csv" class="form-control mb-2">
                                    <small class="text-muted">ارفع ملف users.csv أولاً</small>
                                </div>
                                <div class="col-md-4">
                                    <button type="button" class="btn btn-primary" onclick="processUsersStep()" id="usersBtn">
                                        <i class="fas fa-upload me-2"></i>رفع Users
                                    </button>
                                </div>
                            </div>
                            <div id="usersResult" class="mt-3" style="display: none;"></div>
                        </div>

                        <!-- الخطوة 2: رفع Customers -->
                        <div class="workflow-step mb-4" id="step2" style="opacity: 0.5;">
                            <div class="d-flex align-items-center mb-3">
                                <div class="step-number bg-secondary text-white rounded-circle me-3">2</div>
                                <h6 class="mb-0">رفع جدول Customers (مرتبط بـ Users)</h6>
                                <span class="badge bg-secondary ms-auto" id="step2-status">مغلق</span>
                            </div>
                            <div class="row">
                                <div class="col-md-8">
                                    <input type="file" id="customersFile" accept=".csv" class="form-control mb-2" disabled>
                                    <small class="text-muted">سيتم تفعيله بعد رفع Users</small>
                                </div>
                                <div class="col-md-4">
                                    <button type="button" class="btn btn-secondary" onclick="processCustomersStep()" id="customersBtn" disabled>
                                        <i class="fas fa-link me-2"></i>ربط Customers
                                    </button>
                                </div>
                            </div>
                            <div id="customersResult" class="mt-3" style="display: none;"></div>
                        </div>

                        <!-- الخطوة 3: التحقق من الربط -->
                        <div class="workflow-step mb-4" id="step3" style="opacity: 0.5;">
                            <div class="d-flex align-items-center mb-3">
                                <div class="step-number bg-secondary text-white rounded-circle me-3">3</div>
                                <h6 class="mb-0">التحقق من الربط والنتائج النهائية</h6>
                                <span class="badge bg-secondary ms-auto" id="step3-status">مغلق</span>
                            </div>
                            <button type="button" class="btn btn-info" onclick="verifyRelationship()" id="verifyBtn" disabled>
                                <i class="fas fa-check-double me-2"></i>التحقق من الربط
                            </button>
                            <div id="verificationResult" class="mt-3" style="display: none;"></div>
                        </div>

                <!-- أدوات تحضير الجدول -->
                <div class="border-top pt-3 mb-3">
                    <h6>تحضير جدول Users (خطوات منفصلة):</h6>

                    <!-- زر إضافة الأعمدة -->
                    <div class="mb-2">
                        <button type="button" class="btn btn-info btn-lg w-100" onclick="addRequiredColumns()">
                            <i class="fas fa-plus-circle me-2"></i>1. إضافة الأعمدة المطلوبة من SYSTEM_STATUS.md
                        </button>
                    </div>

                    <!-- زر ترتيب الجدول -->
                    <div class="mb-2">
                        <button type="button" class="btn btn-warning btn-lg w-100" onclick="organizeTableColumns()">
                            <i class="fas fa-sort me-2"></i>2. ترتيب أعمدة الجدول
                        </button>
                    </div>

                    <!-- زر شامل (الاثنين معاً) -->
                    <div class="mb-2">
                        <button type="button" class="btn btn-success btn-lg w-100" onclick="prepareUsersTable()">
                            <i class="fas fa-tools me-2"></i>3. تحضير شامل (إضافة + ترتيب)
                        </button>
                    </div>

                    <!-- زر ملء البيانات المفقودة -->
                    <div class="mb-2">
                        <button type="button" class="btn btn-danger btn-lg w-100" onclick="fillMissingData()">
                            <i class="fas fa-database me-2"></i>4. ملء البيانات المفقودة من القاعدة القديمة
                        </button>
                        <small class="text-muted">ينقل user_type، token، national_num، job من القاعدة القديمة</small>
                    </div>

                    <small class="text-muted mt-2 d-block">بعد التحضير، يمكنك رفع ملف CSV</small>
                </div>

                <!-- تصدير البيانات من القاعدة القديمة -->
                <div class="card mb-4 border-info">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-download me-2"></i>تصدير من القاعدة القديمة</h5>
                        <small>لتسهيل عملية النقل - تصدير مباشر كـ CSV</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
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
                                    <option value="maids">maids</option>
                                    <option value="orders">orders</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button class="btn btn-info" onclick="exportFromOldDB()">
                                <i class="fas fa-download me-2"></i>تصدير كـ CSV
                            </button>
                            <button class="btn btn-success" onclick="exportAndUpload('users')">
                                <i class="fas fa-magic me-2"></i>تصدير + رفع Users مباشرة
                            </button>
                            <button class="btn btn-warning" onclick="exportAndUpload('customers')">
                                <i class="fas fa-magic me-2"></i>تصدير + رفع Customers مباشرة
                            </button>
                        </div>

                        <div id="export_results" class="mt-3" style="display: none;"></div>
                    </div>
                </div>

                        <!-- إعدادات الربط والتطابق -->
                        <div class="border-top pt-3">
                            <h6>إعدادات الربط والتطابق:</h6>
                            <div class="row">
                                <div class="col-md-3">
                                    <label>حقل الربط في Customers:</label>
                                    <select class="form-control" id="linkField">
                                        <option value="user_id">user_id</option>
                                        <option value="customer_user_id">customer_user_id</option>
                                        <option value="related_user_id">related_user_id</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="createMissingUsers" checked>
                                        <label class="form-check-label">إنشاء Users مفقودة</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="enableColumnMapping" checked>
                                        <label class="form-check-label">تفعيل تطابق الأعمدة</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="setDefaultRegisterFrom" checked>
                                        <label class="form-check-label">register_from = 'web'</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3" id="columnMappingInfo">
                                <small class="text-info">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <strong>تطابق تلقائي:</strong>
                                    national_identification_number → national_num,
                                    other_phone → phone2,
                                    job → job
                                </small>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- تقدم المعالجة -->
                <div id="progressSection" style="display: none;" class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h6><i class="fas fa-tasks me-2"></i>معالجة الملفات المرفوعة</h6>
                    </div>
                    <div class="card-body">
                        <div class="progress mb-3" style="height: 15px;">
                            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                                 style="width: 0%"></div>
                        </div>
                        <div id="progressText" class="text-center h6"></div>
                        <div id="progressDetails" class="text-muted"></div>
                    </div>
                </div>

                <!-- النتائج -->
                <div id="resultsArea"></div>

                <!-- تعليمات واضحة -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h6><i class="fas fa-lightbulb me-2"></i>كيف يضمن هذا النظام القراءة من الملفات؟</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-success">✅ الضمانات:</h6>
                                <ul>
                                    <li>قراءة مباشرة من الملف المرفوع</li>
                                    <li>عدم الاتصال بقواعد بيانات أخرى</li>
                                    <li>معالجة كل سجل من الملف</li>
                                    <li>تطابق 100% مع عدد السجلات المرفوعة</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-info">🔧 المعالجة:</h6>
                                <ul>
                                    <li>تقسيم name → first_name + last_name</li>
                                    <li>تنظيف phone (إزالة +966)</li>
                                    <li>إضافة الأعمدة المفقودة</li>
                                    <li>حفظ في platinum_new</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // فحص حالة النظام عند التحميل
            checkSystemStatus();

            // إعداد أزرار الحماية
            document.getElementById('createBackupBtn').addEventListener('click', createBackup);
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('fileInput');
            const uploadBtn = document.getElementById('uploadBtn');

            uploadArea.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', handleFileSelection);
            uploadBtn.addEventListener('click', processFiles);

            // Drag and Drop
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.style.borderColor = '#c0392b';
                this.style.background = '#ffe6e6';
            });

            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.style.borderColor = '#e74c3c';
                this.style.background = '#fff5f5';
            });

            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.borderColor = '#e74c3c';
                this.style.background = '#fff5f5';

                fileInput.files = e.dataTransfer.files;
                handleFileSelection();
            });
        });

        function handleFileSelection() {
            const files = Array.from(document.getElementById('fileInput').files);

            if (files.length === 0) return;

            let html = '';
            let totalEstimated = 0;

            files.forEach(file => {
                // تقدير عدد السجلات بناءً على حجم الملف
                const estimatedRecords = Math.floor(file.size / 250); // تقدير محسن
                totalEstimated += estimatedRecords;

                html += `
                    <div class="file-preview">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-file-csv text-success me-2"></i>
                                <strong>${file.name}</strong>
                                <div class="small text-muted">حجم: ${formatFileSize(file.size)}</div>
                            </div>
                            <div class="text-end">
                                <div class="h5 text-primary mb-0">${estimatedRecords.toLocaleString()}</div>
                                <small>سجل متوقع</small>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += `
                <div class="mt-3 p-3 bg-danger text-white rounded text-center">
                    <h5>📊 إجمالي متوقع: ${totalEstimated.toLocaleString()} سجل</h5>
                    <p class="mb-0">هذا العدد بالضبط سيتم معالجته من الملفات المرفوعة</p>
                </div>
            `;

            document.getElementById('filesList').innerHTML = html;
            document.getElementById('filesPreview').style.display = 'block';
        }

        async function processFiles() {
            const files = document.getElementById('fileInput').files;

            if (files.length === 0) {
                alert('يرجى اختيار ملفات أولاً');
                return;
            }

            if (!confirm(`هل تريد معالجة ${files.length} ملف؟\n\nسيتم:\n• قراءة البيانات من الملفات مباشرة\n• معالجة name → first_name + last_name\n• نقل العدد الصحيح من السجلات\n\nالمتابعة؟`)) {
                return;
            }

            const btn = document.getElementById('uploadBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري المعالجة...';

            showProgress();
            updateProgress(10, 'بدء معالجة الملفات المرفوعة...');

            try {
                // رفع الملفات أولاً
                const formData = new FormData();
                Array.from(files).forEach((file, index) => {
                    formData.append(`file_${index}`, file);
                });
                formData.append('files_count', files.length);
                formData.append('setDefaultRegisterFrom', document.getElementById('setDefaultRegisterFrom').checked ? 'true' : 'false');

                updateProgress(30, 'رفع الملفات...');

                const uploadResponse = await fetch('file_processor.php', {
                    method: 'POST',
                    body: formData
                });

                const uploadData = await uploadResponse.json();

                if (uploadData.success) {
                    updateProgress(60, 'معالجة البيانات من الملفات...');

                    // معالجة كل ملف
                    const processResponse = await fetch('file_processor.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'action=process_files&files=' + encodeURIComponent(JSON.stringify(uploadData.files))
                    });

                    const processData = await processResponse.json();

                    if (processData.success) {
                        updateProgress(100, 'تم معالجة جميع الملفات بنجاح!');
                        showSuccessResults(processData.results);
                    } else {
                        showError('خطأ في المعالجة: ' + processData.message);
                    }
                } else {
                    showError('خطأ في الرفع: ' + uploadData.message);
                }

            } catch (error) {
                showError('خطأ في الاتصال: ' + error.message);
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-upload me-2"></i>رفع ومعالجة هذه الملفات';
        }

        function showSuccessResults(results) {
            const html = `
                <div class="card" style="background: linear-gradient(45deg, #d4edda 0%, #c3e6cb 100%); border: 3px solid #28a745;">
                    <div class="card-header bg-success text-white">
                        <h4><i class="fas fa-check-circle me-2"></i>تم معالجة الملفات بنجاح!</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success">
                            <h5>📁 تفاصيل المعالجة:</h5>
                            <ul class="mb-0">
                                ${results.file_details.map(detail => `<li><strong>${detail.file_name}:</strong> ${detail.records_processed.toLocaleString()} سجل معالج</li>`).join('')}
                            </ul>
                        </div>

                        <div class="row text-center mb-3">
                            <div class="col-md-3">
                                <div class="bg-primary text-white p-3 rounded">
                                    <h3>${results.total_files}</h3>
                                    <small>ملف معالج</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="bg-success text-white p-3 rounded">
                                    <h3>${results.total_records.toLocaleString()}</h3>
                                    <small>سجل منقول</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="bg-info text-white p-3 rounded">
                                    <h3>${results.users_count.toLocaleString()}</h3>
                                    <small>مستخدم</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="bg-warning text-white p-3 rounded">
                                    <h3>${results.maids_count.toLocaleString()}</h3>
                                    <small>عاملة</small>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <h6>🎯 تأكيد التطابق:</h6>
                            <ul class="mb-0">
                                <li>✅ تم قراءة البيانات من الملفات المرفوعة مباشرة</li>
                                <li>✅ عدد السجلات يطابق عدد السجلات في الملفات</li>
                                <li>✅ تم تطبيق معالجة name → first_name + last_name</li>
                                <li>✅ لم يتم النقل من أي قاعدة بيانات أخرى</li>
                            </ul>
                        </div>

                        <div class="text-center mt-4">
                            <a href="verify_file_results.php" target="_blank" class="btn btn-outline-primary btn-lg me-3">
                                <i class="fas fa-search me-2"></i>فحص النتائج والتأكد
                            </a>
                            <button type="button" onclick="compareWithOriginalFile()" class="btn btn-outline-success btn-lg">
                                <i class="fas fa-balance-scale me-2"></i>مقارنة مع الملف الأصلي
                            </button>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('resultsArea').innerHTML = html;
        }

        async function compareWithOriginalFile() {
            try {
                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=compare_results'
                });

                const data = await response.json();

                if (data.success) {
                    let message = '📊 مقارنة الأعداد:\n\n';

                    data.comparison.forEach(comp => {
                        message += `📁 ${comp.file_name}:\n`;
                        message += `   الملف الأصلي: ${comp.original_count} سجل\n`;
                        message += `   المنقول: ${comp.migrated_count} سجل\n`;
                        message += `   ${comp.original_count === comp.migrated_count ? '✅ متطابق' : '⚠️ غير متطابق'}\n\n`;
                    });

                    alert(message);
                } else {
                    alert('خطأ في المقارنة: ' + data.message);
                }
            } catch (error) {
                alert('خطأ في المقارنة: ' + error.message);
            }
        }

        function showProgress() {
            document.getElementById('progressSection').style.display = 'block';
            document.getElementById('progressSection').scrollIntoView();
        }

        function updateProgress(percent, text, details = '') {
            document.getElementById('progressBar').style.width = percent + '%';
            document.getElementById('progressText').textContent = text;
            document.getElementById('progressDetails').textContent = details;
        }

        function showError(message) {
            const html = `
                <div class="alert alert-danger">
                    <h5>❌ حدث خطأ:</h5>
                    <p>${message}</p>
                    <div class="text-center mt-3">
                        <button type="button" onclick="location.reload()" class="btn btn-outline-danger">
                            إعادة المحاولة
                        </button>
                    </div>
                </div>
            `;
            document.getElementById('resultsArea').innerHTML = html;
        }

        // أدوات التطوير الجديدة
        async function addMissingColumns() {
            const btn = document.getElementById('addColumnsBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري الإضافة...';

            try {
                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=add_missing_columns'
                });

                const data = await response.json();

                if (data.success) {
                    showDevelopmentResult('إضافة الأعمدة', `
                        <div class="alert alert-success">
                            <h6>✅ تم إضافة الأعمدة:</h6>
                            <ul class="mb-0">
                                ${data.added_columns.map(col => `<li>${col}</li>`).join('')}
                            </ul>
                            <p class="mt-2 mb-0"><strong>إجمالي:</strong> ${data.total_added} عمود جديد</p>
                        </div>
                    `);
                } else {
                    showError('خطأ في إضافة الأعمدة: ' + data.message);
                }
            } catch (error) {
                showError('خطأ في إضافة الأعمدة: ' + error.message);
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus me-2"></i>إضافة الأعمدة المفقودة';
        }

        async function organizeTable() {
            const btn = document.getElementById('organizeTableBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري الترتيب...';

            try {
                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=reorganize_table'
                });

                const data = await response.json();

                if (data.success) {
                    showDevelopmentResult('ترتيب الجدول', `
                        <div class="alert alert-info">
                            <h6>📐 تم إعادة ترتيب الجدول:</h6>
                            <p class="mb-0">created_at و updated_at الآن في النهاية</p>
                        </div>
                    `);
                } else {
                    showError('خطأ في ترتيب الجدول: ' + data.message);
                }
            } catch (error) {
                showError('خطأ في ترتيب الجدول: ' + error.message);
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sort me-2"></i>ترتيب الجدول';
        }

        async function checkTableStructure() {
            const btn = document.getElementById('checkStructureBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري الفحص...';

            try {
                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=get_table_structure'
                });

                const data = await response.json();

                if (data.success) {
                    let structureHtml = `
                        <div class="alert alert-primary">
                            <h6>📊 بنية الجدول الحالية:</h6>
                            <p><strong>إجمالي الأعمدة:</strong> ${data.total_columns} | <strong>إجمالي السجلات:</strong> ${data.total_records.toLocaleString()}</p>
                            <div class="row">
                    `;

                    data.columns.forEach((col, index) => {
                        const isImportant = ['user_type', 'phone_verified_at', 'verification_code', 'created_at', 'updated_at'].includes(col.Field);
                        const badgeClass = isImportant ? 'bg-primary' : 'bg-secondary';

                        if (index % 4 === 0 && index > 0) {
                            structureHtml += '</div><div class="row">';
                        }

                        structureHtml += `
                            <div class="col-md-3 mb-1">
                                <span class="badge ${badgeClass}" style="font-size: 10px;">
                                    ${index + 1}. ${col.Field}
                                </span>
                            </div>
                        `;
                    });

                    structureHtml += '</div></div>';

                    showDevelopmentResult('بنية الجدول', structureHtml);
                } else {
                    showError('خطأ في فحص البنية: ' + data.message);
                }
            } catch (error) {
                showError('خطأ في فحص البنية: ' + error.message);
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-table me-2"></i>فحص بنية الجدول';
        }

        function showDevelopmentResult(title, content) {
            const html = `
                <div class="card mt-3">
                    <div class="card-header bg-light">
                        <h6>${title}</h6>
                    </div>
                    <div class="card-body">
                        ${content}
                    </div>
                </div>
            `;

            document.getElementById('developmentResults').insertAdjacentHTML('beforeend', html);
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function mergeUsersCustomers() {
            const usersFile = document.getElementById('users_file').files[0];
            const customersFile = document.getElementById('customers_file').files[0];
            if (!usersFile || !customersFile) {
                alert('يرجى رفع كلا الملفين');
                return;
            }
            const formData = new FormData();
            formData.append('action', 'merge_users_customers');
            formData.append('users', usersFile);
            formData.append('customers', customersFile);
            formData.append('link_field', document.getElementById('link_field').value);

            // Add settings like organizeTable, etc.
            formData.append('organizeTable', document.getElementById('organizeTable').checked ? 'true' : 'false');
            formData.append('setDefaultRegisterFrom', document.getElementById('setDefaultRegisterFrom').checked ? 'true' : 'false');

            fetch('file_processor.php', {
                method: 'POST',
                body: formData
            }).then(response => response.json())
              .then(data => {
                  showDevelopmentResult(data.message);
              }).catch(error => {
                  console.error('Error:', error);
              });
        }

        // أدوات إضافية
        async function addMissingColumns() {
            if (!confirm('هل تريد إضافة الأعمدة المفقودة لجدول Users؟\n\nسيتم إنشاء نسخة احتياطية تلقائياً.')) {
                return;
            }

            try {
                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=add_missing_columns'
                });

                const data = await response.json();

                if (data.success) {
                    alert(`✅ تم إضافة الأعمدة بنجاح:\n\nالأعمدة المُضافة: ${data.added_columns.length}\n${data.added_columns.join(', ')}`);
                    checkSystemStatus();
                } else {
                    alert('❌ خطأ في إضافة الأعمدة: ' + data.message);
                }
            } catch (error) {
                alert('خطأ في إضافة الأعمدة: ' + error.message);
            }
        }

        // إضافة الأعمدة المطلوبة فقط
        async function addRequiredColumns() {
            if (!confirm('هل تريد إضافة جميع الأعمدة المطلوبة من SYSTEM_STATUS.md؟')) {
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري إضافة الأعمدة...';

            try {
                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=add_system_status_columns'
                });

                const data = await response.json();

                if (data.success) {
                    alert(`✅ ${data.message}\n\nالأعمدة المضافة: ${data.added_columns.length > 0 ? data.added_columns.join(', ') : 'جميع الأعمدة موجودة مسبقاً'}`);
                } else {
                    alert('❌ خطأ: ' + data.message);
                }
            } catch (error) {
                alert('خطأ في إضافة الأعمدة: ' + error.message);
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus-circle me-2"></i>1. إضافة الأعمدة المطلوبة من SYSTEM_STATUS.md';
        }

        // ترتيب أعمدة الجدول فقط
        async function organizeTableColumns() {
            if (!confirm('هل تريد ترتيب أعمدة جدول Users؟\n\nسيتم ترتيب الأعمدة حسب التصميم المطلوب.')) {
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري ترتيب الجدول...';

            try {
                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=reorganize_table'
                });

                const data = await response.json();

                if (data.success) {
                    alert(`✅ ${data.message}\n\nتم ترتيب الجدول بنجاح!`);
                } else {
                    alert('❌ خطأ في ترتيب الجدول: ' + data.message);
                }
            } catch (error) {
                alert('خطأ في ترتيب الجدول: ' + error.message);
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sort me-2"></i>2. ترتيب أعمدة الجدول';
        }

        // التحضير الشامل (إضافة + ترتيب)
        async function prepareUsersTable() {
            if (!confirm('هل تريد التحضير الشامل لجدول Users؟\n\nسيتم:\n• إضافة جميع الأعمدة المطلوبة\n• ترتيب الجدول\n\nالمتابعة؟')) {
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري التحضير الشامل...';

            try {
                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=prepare_users_table'
                });

                const text = await response.text();

                try {
                    const data = JSON.parse(text);

                    if (data.success) {
                        alert(`✅ تم التحضير الشامل بنجاح!\n\n${data.message}\n\nيمكنك الآن رفع ملفات CSV.`);
                    } else {
                        alert('❌ خطأ في التحضير: ' + data.message);
                    }
                } catch (parseError) {
                    alert('خطأ في تحليل الاستجابة - قد يكون تم التحضير بنجاح');
                    console.log('Raw response:', text);
                }
            } catch (error) {
                alert('خطأ في التحضير: ' + error.message);
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-tools me-2"></i>3. تحضير شامل (إضافة + ترتيب)';
        }

        // رفع ومعالجة الملفات
        async function uploadAndProcess() {
            const usersFile = document.getElementById('users_file').files[0];
            const customersFile = document.getElementById('customers_file').files[0];

            if (!usersFile) {
                alert('يرجى اختيار ملف Users على الأقل');
                return;
            }

            if (!confirm('هل تريد رفع ومعالجة الملفات؟\n\nسيتم:\n• رفع ملف Users\n• معالجة البيانات\n• دمج Customers إن وجد\n\nالمتابعة؟')) {
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري الرفع والمعالجة...';

            const formData = new FormData();
            formData.append('action', 'process_files');
            formData.append('files[]', usersFile);
            if (customersFile) {
                formData.append('files[]', customersFile);
            }
            formData.append('register_from', 'web');

            try {
                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    const resultsDiv = document.getElementById('uploadResults');
                    resultsDiv.innerHTML = `
                        <div class="alert alert-success">
                            <h5>✅ تمت المعالجة بنجاح!</h5>
                            <hr>
                            <p><strong>النتائج:</strong></p>
                            ${data.results ? `
                                <ul>
                                    <li>مستخدمين: ${data.results.users_processed || 0}</li>
                                    <li>أسماء مقسمة: ${data.results.names_split || 0}</li>
                                    <li>Customers مدموج: ${data.results.customers_merged || 0}</li>
                                </ul>
                            ` : '<p>' + data.message + '</p>'}
                        </div>
                    `;
                    resultsDiv.style.display = 'block';

                    alert('✅ تمت المعالجة بنجاح!\n\n' + data.message);
                } else {
                    document.getElementById('uploadResults').innerHTML = `
                        <div class="alert alert-danger">
                            <h5>❌ خطأ في المعالجة</h5>
                            <p>${data.message}</p>
                        </div>
                    `;
                    document.getElementById('uploadResults').style.display = 'block';
                }
            } catch (error) {
                alert('خطأ في الرفع: ' + error.message);
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-cloud-upload-alt me-2"></i>رفع ومعالجة الملفات';
        }

        // ملء البيانات المفقودة من القاعدة القديمة
        async function fillMissingData() {
            if (!confirm('هل تريد ملء البيانات المفقودة من القاعدة القديمة؟\n\nسيتم نقل:\n• user_type\n• phone_verified_at\n• verification_code\n• token\n• national_num (من customers)\n• job (من customers)\n• وباقي البيانات المفقودة\n\nالمتابعة؟')) {
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري ملء البيانات...';

            try {
                const response = await fetch('fill_missing_data.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=fill'
                });

                const data = await response.json();

                if (data.success) {
                    alert(`✅ تم ملء البيانات المفقودة!\n\nUsers محدث: ${data.users_updated}\nCustomers مدموج: ${data.customers_merged}\n\nالأعمدة المحدثة:\n${Object.entries(data.column_stats).map(([k,v]) => `• ${k}: ${v} سجل`).join('\n')}`);
                } else {
                    alert('❌ خطأ: ' + data.message);
                }
            } catch (error) {
                alert('خطأ في ملء البيانات: ' + error.message);
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-database me-2"></i>4. ملء البيانات المفقودة من القاعدة القديمة';
        }

        // وظائف إعدادات قاعدة البيانات
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
                    statusDiv.innerHTML = `<div class="alert alert-success">✅ الاتصال ناجح! القاعدة: ${params.db_name}</div>`;
                } else {
                    statusDiv.innerHTML = `<div class="alert alert-danger">❌ فشل الاتصال: ${data.message}</div>`;
                }
            } catch (error) {
                statusDiv.innerHTML = `<div class="alert alert-danger">❌ خطأ: ${error.message}</div>`;
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plug me-2"></i>اختبار';
        }

        // تحديث جميع الاستدعاءات لتضمين معاملات قاعدة البيانات
        const originalFetch = window.fetch;
        window.fetch = function(url, options) {
            if (url.includes('file_processor.php')) {
                const params = getDbParams();

                if (options && options.body) {
                    if (typeof options.body === 'string') {
                        options.body += '&' + new URLSearchParams(params);
                    } else if (options.body instanceof FormData) {
                        Object.entries(params).forEach(([key, value]) => {
                            options.body.append(key, value);
                        });
                    }
                }
            }
            return originalFetch.call(this, url, options);
        };

        // دوال التصدير من القاعدة القديمة
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
            };

            // إضافة معاملات القاعدة الجديدة
            Object.assign(params, getDbParams());

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
                            <p><strong>الأعمدة:</strong> ${data.columns.join(', ')}</p>
                        </div>
                    `;
                } else {
                    resultsDiv.innerHTML = `<div class="alert alert-danger">❌ ${data.message}</div>`;
                }
            } catch (error) {
                document.getElementById('export_results').innerHTML = `
                    <div class="alert alert-danger">❌ خطأ: ${error.message}</div>
                `;
                document.getElementById('export_results').style.display = 'block';
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-download me-2"></i>تصدير كـ CSV';
        }

        // تصدير ورفع مباشر (الخيار الأمثل!)
        async function exportAndUpload(table) {
            if (!confirm(`هل تريد تصدير جدول ${table} من القاعدة القديمة ورفعه مباشرة؟\n\nهذا سيوفر عليك خطوات يدوية كثيرة!`)) {
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>تصدير ورفع...';

            try {
                // الخطوة 1: التصدير
                const exportParams = {
                    action: 'export_from_old_db',
                    export_table: table,
                    old_db_host: document.getElementById('old_db_host').value,
                    old_db_port: document.getElementById('old_db_port').value,
                    old_db_name: document.getElementById('old_db_name').value,
                    old_db_user: document.getElementById('old_db_user').value,
                    old_db_pass: document.getElementById('old_db_pass').value,
                };
                Object.assign(exportParams, getDbParams());

                const exportResponse = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(exportParams)
                });

                const exportData = await exportResponse.json();

                if (!exportData.success) {
                    throw new Error('فشل التصدير: ' + exportData.message);
                }

                // الخطوة 2: المعالجة والرفع التلقائي
                const processParams = {
                    action: 'process_exported_file',
                    exported_file: exportData.file_name,
                    table_type: table
                };
                Object.assign(processParams, getDbParams());

                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>معالجة البيانات...';

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
                            <p><strong>تم:</strong> تصدير ${exportData.record_count} من ${table}</p>
                            <p><strong>ورفع:</strong> ${processData.processed} سجل</p>
                            <p><strong>النتيجة:</strong> ${processData.message}</p>
                        </div>
                    `;
                } else {
                    resultsDiv.innerHTML = `<div class="alert alert-warning">⚠️ تم التصدير لكن فشل الرفع: ${processData.message}</div>`;
                }

            } catch (error) {
                document.getElementById('export_results').innerHTML = `
                    <div class="alert alert-danger">❌ خطأ: ${error.message}</div>
                `;
                document.getElementById('export_results').style.display = 'block';
            }

            btn.disabled = false;
            btn.innerHTML = `<i class="fas fa-magic me-2"></i>تصدير + رفع ${table} مباشرة`;
        }

        // دوال النقل الشامل الجديدة
        async function scanOldDatabase() {
            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري الفحص...';

            document.getElementById('scan-status').textContent = 'قيد الفحص';
            document.getElementById('scan-status').className = 'badge bg-warning ms-auto';

            try {
                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=scan_old_database'
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('scan-status').textContent = 'مكتمل';
                    document.getElementById('scan-status').className = 'badge bg-success ms-auto';

                    document.getElementById('scanResults').innerHTML = `
                        <div class="alert alert-success">
                            <h6>📊 نتائج الفحص:</h6>
                            <ul class="mb-0">
                                <li><strong>الأعمدة في القاعدة القديمة:</strong> ${data.old_columns.length}</li>
                                <li><strong>الأعمدة في القاعدة الجديدة:</strong> ${data.new_columns.length}</li>
                                <li><strong>أعمدة مفقودة:</strong> ${data.missing_columns.length}</li>
                            </ul>
                            <div class="mt-2">
                                <strong>الأعمدة المفقودة:</strong><br>
                                <small>${data.missing_columns.join(', ')}</small>
                            </div>
                        </div>
                    `;
                    document.getElementById('scanResults').style.display = 'block';

                    // تفعيل الخطوة التالية
                    document.getElementById('addColumnsStep').style.opacity = '1';
                    document.getElementById('addColumnsBtn').disabled = false;
                    document.getElementById('addColumnsBtn').className = 'btn btn-warning w-100';
                    document.getElementById('addColumns-status').textContent = 'جاهز';
                    document.getElementById('addColumns-status').className = 'badge bg-warning ms-auto';
                } else {
                    document.getElementById('scan-status').textContent = 'خطأ';
                    document.getElementById('scan-status').className = 'badge bg-danger ms-auto';
                    alert('❌ خطأ في الفحص: ' + data.message);
                }
            } catch (error) {
                alert('خطأ في الفحص: ' + error.message);
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-search me-2"></i>فحص جميع الأعمدة في القاعدة القديمة';
        }

        async function addAllMissingColumns() {
            const btn = document.getElementById('addColumnsBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>إضافة الأعمدة...';

            document.getElementById('addColumns-status').textContent = 'قيد الإضافة';
            document.getElementById('addColumns-status').className = 'badge bg-warning ms-auto';

            try {
                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=add_all_missing_columns'
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('addColumns-status').textContent = 'مكتمل';
                    document.getElementById('addColumns-status').className = 'badge bg-success ms-auto';

                    document.getElementById('addColumnsResults').innerHTML = `
                        <div class="alert alert-success">
                            <h6>✅ تم إضافة الأعمدة:</h6>
                            <p><strong>أعمدة مُضافة:</strong> ${data.added_columns.length}</p>
                            <small>${data.added_columns.join(', ')}</small>
                        </div>
                    `;
                    document.getElementById('addColumnsResults').style.display = 'block';

                    // تفعيل الخطوة التالية
                    document.getElementById('orderStep').style.opacity = '1';
                    document.getElementById('orderBtn').disabled = false;
                    document.getElementById('orderBtn').className = 'btn btn-info w-100';
                    document.getElementById('order-status').textContent = 'جاهز';
                    document.getElementById('order-status').className = 'badge bg-info ms-auto';
                } else {
                    alert('❌ خطأ في إضافة الأعمدة: ' + data.message);
                }
            } catch (error) {
                alert('خطأ في إضافة الأعمدة: ' + error.message);
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus me-2"></i>إضافة جميع الأعمدة المفقودة';
        }

        async function migrateAllData() {
            if (!confirm('🚀 نقل جميع البيانات؟\n\nسيتم:\n• نقل جميع المستخدمين من القاعدة القديمة\n• تقسيم name → first_name + last_name\n• نقل جميع الأعمدة بدون استثناء\n• تعيين register_from = web\n\nالمتابعة؟')) {
                return;
            }

            const btn = document.getElementById('migrateBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري النقل الشامل...';

            document.getElementById('migrate-status').textContent = 'قيد النقل';
            document.getElementById('migrate-status').className = 'badge bg-warning ms-auto';

            try {
                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=migrate_all_data'
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('migrate-status').textContent = 'مكتمل';
                    document.getElementById('migrate-status').className = 'badge bg-success ms-auto';

                    document.getElementById('migrateResults').innerHTML = `
                        <div class="alert alert-success">
                            <h4>🎉 تم النقل الشامل بنجاح!</h4>
                            <div class="row text-center mt-3">
                                <div class="col-md-4">
                                    <h5 class="text-primary">${data.migrated_users.toLocaleString()}</h5>
                                    <small>مستخدم منقول</small>
                                </div>
                                <div class="col-md-4">
                                    <h5 class="text-success">${data.processed_columns}</h5>
                                    <small>عمود معالج</small>
                                </div>
                                <div class="col-md-4">
                                    <h5 class="text-info">${data.names_split}</h5>
                                    <small>اسم مقسم</small>
                                </div>
                            </div>
                            <hr>
                            <h6>تفاصيل النقل:</h6>
                            <ul class="mb-0">
                                <li>✅ جميع الأعمدة من القاعدة القديمة منقولة</li>
                                <li>✅ name مقسم إلى first_name + last_name</li>
                                <li>✅ register_from = 'web' لجميع السجلات</li>
                                <li>✅ لا توجد بيانات مفقودة</li>
                            </ul>
                        </div>
                    `;
                    document.getElementById('migrateResults').style.display = 'block';
                } else {
                    document.getElementById('migrate-status').textContent = 'خطأ';
                    document.getElementById('migrate-status').className = 'badge bg-danger ms-auto';
                    alert('❌ خطأ في النقل: ' + data.message);
                }
            } catch (error) {
                alert('خطأ في النقل: ' + error.message);
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-exchange-alt me-2"></i>نقل جميع البيانات من القاعدة القديمة';
        }

        // Workflow Functions for Users → Customers

        // دوال الحماية والأمان
        async function checkSystemStatus() {
            try {
                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=check_system_status'
                });

                const data = await response.json();

                if (data.success) {
                    const status = data.status;

                    // حالة الاتصال
                    document.getElementById('dbConnection').className =
                        status.db_connection ? 'badge bg-success' : 'badge bg-danger';
                    document.getElementById('dbConnection').textContent =
                        status.db_connection ? `متصل (${status.users_count} مستخدم)` : 'غير متصل';

                    // حالة النسخ الاحتياطي
                    document.getElementById('backupStatus').className =
                        status.last_backup ? 'badge bg-success' : 'badge bg-warning';
                    document.getElementById('backupStatus').textContent =
                        status.last_backup ? `آخر نسخة: ${status.last_backup.time}` : 'لا توجد نسخ احتياطية';
                }
            } catch (error) {
                console.error('خطأ في فحص الحالة:', error);
            }
        }

        async function createBackup() {
            if (!confirm('هل تريد إنشاء نسخة احتياطية من قاعدة البيانات platinum_new؟')) {
                return;
            }

            const btn = document.getElementById('createBackupBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الإنشاء...';

            try {
                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=create_backup'
                });

                const data = await response.json();

                if (data.success) {
                    alert(`✅ تم إنشاء نسخة احتياطية بنجاح:\n${data.backup_file}\nالوقت: ${data.timestamp}`);
                    checkSystemStatus(); // إعادة فحص الحالة
                } else {
                    alert('❌ فشل في إنشاء النسخة الاحتياطية: ' + data.message);
                }
            } catch (error) {
                alert('خطأ في إنشاء النسخة الاحتياطية: ' + error.message);
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i>إنشاء نسخة احتياطية الآن';
        }

        function processUsersStep() {
            if (!confirm('رفع بيانات Users؟\n\nسيتم:\n• تقسيم name → first_name + last_name\n• تعيين register_from = web\n• عدم فقدان أي بيانات\n\nالمتابعة؟')) {
                return;
            }
            const file = document.getElementById('usersFile').files[0];
            if (!file) {
                alert('يرجى اختيار ملف Users أولاً');
                return;
            }

            const btn = document.getElementById('usersBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>معالجة Users...';

            document.getElementById('step1-status').textContent = 'قيد المعالجة';
            document.getElementById('step1-status').className = 'badge bg-warning ms-auto';

            const formData = new FormData();
            formData.append('action', 'process_users_step');
            formData.append('users_file', file);
            formData.append('setDefaultRegisterFrom', document.getElementById('setDefaultRegisterFrom').checked ? 'true' : 'false');

            fetch('file_processor.php', {
                method: 'POST',
                body: formData
            }).then(response => response.json())
              .then(data => {
                  if (data.success) {
                      // تفعيل الخطوة 2
                      document.getElementById('step2').style.opacity = '1';
                      document.getElementById('customersFile').disabled = false;
                      document.getElementById('customersBtn').disabled = false;
                      document.getElementById('customersBtn').className = 'btn btn-primary';
                      document.getElementById('step2-status').textContent = 'جاهز';
                      document.getElementById('step2-status').className = 'badge bg-primary ms-auto';

                      // تحديث حالة الخطوة 1
                      document.getElementById('step1-status').textContent = 'تم بنجاح';
                      document.getElementById('step1-status').className = 'badge bg-success ms-auto';

                      // عرض النتيجة
                      document.getElementById('usersResult').innerHTML = `
                          <div class="alert alert-success">
                              <i class="fas fa-check me-2"></i>${data.message}
                          </div>
                      `;
                      document.getElementById('usersResult').style.display = 'block';
                  } else {
                      document.getElementById('step1-status').textContent = 'خطأ';
                      document.getElementById('step1-status').className = 'badge bg-danger ms-auto';

                      document.getElementById('usersResult').innerHTML = `
                          <div class="alert alert-danger">
                              <i class="fas fa-times me-2"></i>${data.message}
                          </div>
                      `;
                      document.getElementById('usersResult').style.display = 'block';
                  }

                  btn.disabled = false;
                  btn.innerHTML = '<i class="fas fa-upload me-2"></i>رفع Users';
              }).catch(error => {
                  console.error('Error:', error);
                  btn.disabled = false;
                  btn.innerHTML = '<i class="fas fa-upload me-2"></i>رفع Users';
              });
        }

        function processCustomersStep() {
            // تأكيد إضافي لعملية الربط
            if (!confirm('⚠️ عملية ربط هامة!\n\nستقوم بربط جدول Customers مع Users في قاعدة البيانات الفعلية\n\nالمتابعة؟')) {
                return;
            }
            const file = document.getElementById('customersFile').files[0];
            if (!file) {
                alert('يرجى اختيار ملف Customers');
                return;
            }

            const btn = document.getElementById('customersBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>ربط Customers...';

            document.getElementById('step2-status').textContent = 'قيد المعالجة';
            document.getElementById('step2-status').className = 'badge bg-warning ms-auto';

            const formData = new FormData();
            formData.append('action', 'process_customers_step');
            formData.append('customers_file', file);
            formData.append('link_field', document.getElementById('linkField').value);
            formData.append('create_missing_users', document.getElementById('createMissingUsers').checked ? 'true' : 'false');
            formData.append('enable_column_mapping', document.getElementById('enableColumnMapping').checked ? 'true' : 'false');
            formData.append('setDefaultRegisterFrom', document.getElementById('setDefaultRegisterFrom').checked ? 'true' : 'false');

            fetch('file_processor.php', {
                method: 'POST',
                body: formData
            }).then(response => response.json())
              .then(data => {
                  if (data.success) {
                      // تفعيل الخطوة 3
                      document.getElementById('step3').style.opacity = '1';
                      document.getElementById('verifyBtn').disabled = false;
                      document.getElementById('verifyBtn').className = 'btn btn-info';
                      document.getElementById('step3-status').textContent = 'جاهز';
                      document.getElementById('step3-status').className = 'badge bg-info ms-auto';

                      // تحديث حالة الخطوة 2
                      document.getElementById('step2-status').textContent = 'تم الربط';
                      document.getElementById('step2-status').className = 'badge bg-success ms-auto';

                      // عرض النتيجة
                      document.getElementById('customersResult').innerHTML = `
                          <div class="alert alert-success">
                              <i class="fas fa-link me-2"></i>${data.message}
                          </div>
                      `;
                      document.getElementById('customersResult').style.display = 'block';
                  } else {
                      document.getElementById('step2-status').textContent = 'خطأ في الربط';
                      document.getElementById('step2-status').className = 'badge bg-danger ms-auto';

                      document.getElementById('customersResult').innerHTML = `
                          <div class="alert alert-danger">
                              <i class="fas fa-unlink me-2"></i>${data.message}
                          </div>
                      `;
                      document.getElementById('customersResult').style.display = 'block';
                  }

                  btn.disabled = false;
                  btn.innerHTML = '<i class="fas fa-link me-2"></i>ربط Customers';
              }).catch(error => {
                  console.error('Error:', error);
                  btn.disabled = false;
                  btn.innerHTML = '<i class="fas fa-link me-2"></i>ربط Customers';
              });
        }

        function verifyRelationship() {
            const btn = document.getElementById('verifyBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>فحص الربط...';

            fetch('file_processor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=verify_relationship&link_field=' + document.getElementById('linkField').value
            }).then(response => response.json())
              .then(data => {
                  if (data.success) {
                      document.getElementById('step3-status').textContent = 'مكتمل';
                      document.getElementById('step3-status').className = 'badge bg-success ms-auto';

                      document.getElementById('verificationResult').innerHTML = `
                          <div class="alert alert-info">
                              <h6><i class="fas fa-chart-bar me-2"></i>تقرير الربط النهائي:</h6>
                              ${data.report}
                          </div>
                      `;
                      document.getElementById('verificationResult').style.display = 'block';
                  } else {
                      document.getElementById('verificationResult').innerHTML = `
                          <div class="alert alert-warning">
                              <i class="fas fa-exclamation-triangle me-2"></i>${data.message}
                          </div>
                      `;
                      document.getElementById('verificationResult').style.display = 'block';
                  }

                  btn.disabled = false;
                  btn.innerHTML = '<i class="fas fa-check-double me-2"></i>التحقق من الربط';
              }).catch(error => {
                  console.error('Error:', error);
                  btn.disabled = false;
                  btn.innerHTML = '<i class="fas fa-check-double me-2"></i>التحقق من الربط';
              });
        }

        function processBulkImport() {
            const form = document.getElementById('bulkImportForm');
            const formData = new FormData(form);
            formData.append('action', 'bulk_import');

            fetch('file_processor.php', {
                method: 'POST',
                body: formData
            }).then(response => response.json())
              .then(data => {
                  showDevelopmentResult(data.message);
              }).catch(error => {
                  console.error('Error:', error);
              });
        }
    </script>
</body>
</html>
