<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام نقل البيانات - Laravel</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .hero {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .hero h1 {
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .hero .lead {
            color: rgba(255,255,255,0.9);
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            background: rgba(255,255,255,0.95);
        }

        .card-header {
            border-radius: 15px 15px 0 0 !important;
            font-weight: 600;
        }

        .btn {
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .step-number {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .workflow-step {
            transition: all 0.3s ease;
        }

        .status-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <!-- Hero -->
                <div class="hero text-center">
                    <h1><i class="fas fa-users me-3"></i>نقل المستخدمين - Laravel</h1>
                    <p class="lead">نقل بسيط ومباشر للمستخدمين حسب SYSTEM_STATUS.md</p>
                    <div class="d-flex justify-content-center gap-3 mt-3">
                        <span class="badge bg-success fs-6">👥 Users فقط</span>
                        <span class="badge bg-info fs-6">📋 حسب SYSTEM_STATUS</span>
                        <span class="badge bg-warning fs-6">🚫 بدون عاملات</span>
                    </div>
                </div>

                <!-- حالة النظام -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-chart-line me-2"></i>حالة النظام</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <div class="status-card p-3 rounded text-white">
                                    <h3 id="newUsersCount">-</h3>
                                    <small>المستخدمين في القاعدة الجديدة</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="status-card p-3 rounded text-white">
                                    <h3 id="oldUsersCount">-</h3>
                                    <small>المستخدمين في القاعدة القديمة</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="status-card p-3 rounded text-white">
                                    <h3 id="missingCount">-</h3>
                                    <small>الأعمدة المفقودة</small>
                                </div>
                            </div>
                        </div>
                        <div class="text-center mt-3">
                            <button type="button" class="btn btn-primary" onclick="checkSystemStatus()">
                                <i class="fas fa-refresh me-2"></i>تحديث الحالة
                            </button>
                        </div>
                    </div>
                </div>

                <!-- نقل المستخدمين فقط -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5><i class="fas fa-users me-2"></i>نقل المستخدمين (حسب SYSTEM_STATUS.md)</h5>
                        <small>نقل المستخدمين فقط بدون العاملات - معالجة ذكية للبيانات</small>
                    </div>
                    <div class="card-body">
                        <!-- الخطوة 1: فحص الأعمدة -->
                        <div class="workflow-step mb-4" id="scanStep">
                            <div class="d-flex align-items-center mb-3">
                                <div class="step-number bg-primary text-white rounded-circle me-3">1</div>
                                <h6 class="mb-0">فحص الأعمدة المفقودة</h6>
                                <span class="badge bg-secondary ms-auto" id="scan-status">في الانتظار</span>
                            </div>
                            <button type="button" class="btn btn-primary w-100" onclick="scanColumns()" id="scanBtn">
                                <i class="fas fa-search me-2"></i>فحص الأعمدة المفقودة
                            </button>
                            <div id="scanResults" class="mt-3" style="display: none;"></div>
                        </div>

                        <!-- الخطوة 2: إضافة الأعمدة المفقودة -->
                        <div class="workflow-step mb-4" id="addColumnsStep" style="opacity: 0.5;">
                            <div class="d-flex align-items-center mb-3">
                                <div class="step-number bg-warning text-white rounded-circle me-3">2</div>
                                <h6 class="mb-0">إضافة الأعمدة المفقودة</h6>
                                <span class="badge bg-secondary ms-auto" id="addColumns-status">مغلق</span>
                            </div>
                            <button type="button" class="btn btn-secondary w-100" onclick="addMissingColumns()" id="addColumnsBtn" disabled>
                                <i class="fas fa-plus me-2"></i>إضافة جميع الأعمدة المفقودة
                            </button>
                            <div id="addColumnsResults" class="mt-3" style="display: none;"></div>
                        </div>

                        <!-- الخطوة 3: ترتيب الجدول -->
                        <div class="workflow-step mb-4" id="orderStep" style="opacity: 0.5;">
                            <div class="d-flex align-items-center mb-3">
                                <div class="step-number bg-info text-white rounded-circle me-3">3</div>
                                <h6 class="mb-0">تطبيق الترتيب المخصص</h6>
                                <span class="badge bg-secondary ms-auto" id="order-status">مغلق</span>
                            </div>
                            <button type="button" class="btn btn-secondary w-100" onclick="applyCustomOrder()" id="orderBtn" disabled>
                                <i class="fas fa-sort me-2"></i>تطبيق الترتيب المخصص
                            </button>
                            <div id="orderResults" class="mt-3" style="display: none;"></div>
                        </div>

                        <!-- الخطوة 4: نقل المستخدمين -->
                        <div class="workflow-step mb-4" id="migrateStep" style="opacity: 0.5;">
                            <div class="d-flex align-items-center mb-3">
                                <div class="step-number bg-success text-white rounded-circle me-3">4</div>
                                <h6 class="mb-0">نقل المستخدمين (بدون عاملات)</h6>
                                <span class="badge bg-secondary ms-auto" id="migrate-status">مغلق</span>
                            </div>
                            <button type="button" class="btn btn-secondary w-100" onclick="migrateAllUsers()" id="migrateBtn" disabled>
                                <i class="fas fa-users me-2"></i>نقل جميع المستخدمين (حسب SYSTEM_STATUS.md)
                            </button>
                            <div id="migrateResults" class="mt-3" style="display: none;"></div>
                        </div>
                    </div>
                </div>

                <!-- رفع Customers (الخطوة التالية) -->
                <div class="card mb-4" id="customersSection" style="display: none;">
                    <div class="card-header bg-warning text-white">
                        <h5><i class="fas fa-upload me-2"></i>الخطوة التالية: رفع جدول Customers</h5>
                        <small>دمج بيانات Customers في جدول Users بناءً على user_id</small>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6>📋 ما سيتم:</h6>
                            <ul class="mb-0">
                                <li><strong>national_identification_number</strong> → national_num</li>
                                <li><strong>other_phone</strong> → phone2</li>
                                <li><strong>job</strong> → job + profession</li>
                                <li><strong>birth_date</strong> → birthdate</li>
                                <li><strong>rooms_number</strong> → rooms_num</li>
                                <li><strong>family_number</strong> → family_num</li>
                                <li><strong>childrens_under_age</strong> → children</li>
                            </ul>
                        </div>

                        <form id="customersForm" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="customers_file" class="form-label">ملف Customers (CSV)</label>
                                <input type="file" class="form-control" id="customers_file" name="customers_file" accept=".csv" required>
                                <div class="form-text">يجب أن يحتوي على عمود user_id للربط</div>
                            </div>

                            <button type="button" class="btn btn-warning w-100" onclick="uploadCustomers()">
                                <i class="fas fa-upload me-2"></i>رفع ودمج بيانات Customers
                            </button>
                        </form>

                        <div id="customersResults" class="mt-3" style="display: none;"></div>
                    </div>
                </div>

                <!-- تفاصيل النقل -->
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h6><i class="fas fa-info-circle me-2"></i>معالجات SYSTEM_STATUS.md</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>✅ البيانات المطابقة:</h6>
                                <ul class="list-unstyled">
                                    <li>• id, name, email, phone</li>
                                    <li>• password, remember_token</li>
                                    <li>• created_at, updated_at</li>
                                    <li>• address, city</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>🔄 التحويلات الذكية:</h6>
                                <ul class="list-unstyled">
                                    <li>• name → first_name + last_name</li>
                                    <li>• banned → is_blocked + status</li>
                                    <li>• whatsapp_phone → phone2</li>
                                    <li>• register_from = 'migration'</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // إعداد CSRF Token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // فحص حالة النظام
        async function checkSystemStatus() {
            try {
                const response = await fetch('/migration/status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('newUsersCount').textContent = data.new_database_users;
                    document.getElementById('oldUsersCount').textContent = data.old_database_users;

                    // فحص الأعمدة أيضاً
                    scanColumns(false);
                } else {
                    alert('خطأ في فحص الحالة: ' + data.message);
                }
            } catch (error) {
                alert('خطأ في الاتصال: ' + error.message);
            }
        }

        // فحص الأعمدة
        async function scanColumns(showAlert = true) {
            const btn = document.getElementById('scanBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري الفحص...';

            document.getElementById('scan-status').textContent = 'قيد الفحص';
            document.getElementById('scan-status').className = 'badge bg-warning ms-auto';

            try {
                const response = await fetch('/migration/scan-columns', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('scan-status').textContent = 'مكتمل';
                    document.getElementById('scan-status').className = 'badge bg-success ms-auto';

                    document.getElementById('missingCount').textContent = data.missing_count;

                    document.getElementById('scanResults').innerHTML = `
                        <div class="alert alert-success">
                            <h6>نتائج الفحص:</h6>
                            <p><strong>أعمدة القاعدة القديمة:</strong> ${data.old_columns.length}</p>
                            <p><strong>أعمدة القاعدة الجديدة:</strong> ${data.new_columns.length}</p>
                            <p><strong>أعمدة مفقودة:</strong> ${data.missing_count}</p>
                            ${data.missing_columns.length > 0 ?
                                '<p><strong>الأعمدة المفقودة:</strong> ' + data.missing_columns.join(', ') + '</p>' :
                                '<p class="text-success">✅ جميع الأعمدة موجودة!</p>'
                            }
                        </div>
                    `;
                    document.getElementById('scanResults').style.display = 'block';

                    // تفعيل الخطوة التالية
                    document.getElementById('addColumnsStep').style.opacity = '1';
                    document.getElementById('addColumnsBtn').disabled = false;
                    document.getElementById('addColumns-status').textContent = 'جاهز';
                    document.getElementById('addColumns-status').className = 'badge bg-primary ms-auto';

                    if (showAlert) {
                        alert(`✅ تم فحص الأعمدة!\n\nالمفقودة: ${data.missing_count} عمود`);
                    }
                } else {
                    document.getElementById('scan-status').textContent = 'خطأ';
                    document.getElementById('scan-status').className = 'badge bg-danger ms-auto';
                    alert('❌ خطأ في فحص الأعمدة: ' + data.message);
                }
            } catch (error) {
                document.getElementById('scan-status').textContent = 'خطأ';
                document.getElementById('scan-status').className = 'badge bg-danger ms-auto';
                alert('خطأ في الاتصال: ' + error.message);
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-search me-2"></i>فحص الأعمدة المفقودة';
        }

        // إضافة الأعمدة المفقودة
        async function addMissingColumns() {
            if (!confirm('هل تريد إضافة جميع الأعمدة المفقودة؟\n\nسيتم إضافة الأعمدة بشكل آمن.')) {
                return;
            }

            const btn = document.getElementById('addColumnsBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>إضافة الأعمدة...';

            document.getElementById('addColumns-status').textContent = 'قيد الإضافة';
            document.getElementById('addColumns-status').className = 'badge bg-warning ms-auto';

            try {
                const response = await fetch('/migration/add-columns', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('addColumns-status').textContent = 'مكتمل';
                    document.getElementById('addColumns-status').className = 'badge bg-success ms-auto';

                    document.getElementById('addColumnsResults').innerHTML = `
                        <div class="alert alert-success">
                            <h6>تم إضافة الأعمدة بنجاح!</h6>
                            <p><strong>أعمدة مُضافة:</strong> ${data.added_count}</p>
                            ${data.added_columns.length > 0 ?
                                '<p><strong>الأعمدة:</strong> ' + data.added_columns.join(', ') + '</p>' : ''
                            }
                        </div>
                    `;
                    document.getElementById('addColumnsResults').style.display = 'block';

                    // تفعيل الخطوة التالية
                    document.getElementById('orderStep').style.opacity = '1';
                    document.getElementById('orderBtn').disabled = false;
                    document.getElementById('order-status').textContent = 'جاهز';
                    document.getElementById('order-status').className = 'badge bg-primary ms-auto';

                    alert(`✅ تم إضافة ${data.added_count} عمود بنجاح!`);
                } else {
                    document.getElementById('addColumns-status').textContent = 'خطأ';
                    document.getElementById('addColumns-status').className = 'badge bg-danger ms-auto';
                    alert('❌ خطأ في إضافة الأعمدة: ' + data.message);
                }
            } catch (error) {
                document.getElementById('addColumns-status').textContent = 'خطأ';
                document.getElementById('addColumns-status').className = 'badge bg-danger ms-auto';
                alert('خطأ في الاتصال: ' + error.message);
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus me-2"></i>إضافة جميع الأعمدة المفقودة';
        }

        // تطبيق الترتيب المخصص
        async function applyCustomOrder() {
            if (!confirm('هل تريد تطبيق الترتيب المخصص للجدول؟\n\nسيتم ترتيب الأعمدة حسب التصميم المطلوب.')) {
                return;
            }

            const btn = document.getElementById('orderBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري الترتيب...';

            document.getElementById('order-status').textContent = 'قيد التطبيق';
            document.getElementById('order-status').className = 'badge bg-warning ms-auto';

            try {
                const response = await fetch('/migration/apply-order', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('order-status').textContent = 'مكتمل';
                    document.getElementById('order-status').className = 'badge bg-success ms-auto';

                    document.getElementById('orderResults').innerHTML = `
                        <div class="alert alert-success">
                            <h6>تم تطبيق الترتيب بنجاح!</h6>
                            <p><strong>خطوات مطبقة:</strong> ${data.applied_steps} من ${data.total_steps}</p>
                        </div>
                    `;
                    document.getElementById('orderResults').style.display = 'block';

                    // تفعيل الخطوة التالية
                    document.getElementById('migrateStep').style.opacity = '1';
                    document.getElementById('migrateBtn').disabled = false;
                    document.getElementById('migrate-status').textContent = 'جاهز';
                    document.getElementById('migrate-status').className = 'badge bg-primary ms-auto';

                    alert(`✅ تم تطبيق الترتيب!\n\nخطوات مطبقة: ${data.applied_steps}/${data.total_steps}`);
                } else {
                    document.getElementById('order-status').textContent = 'خطأ';
                    document.getElementById('order-status').className = 'badge bg-danger ms-auto';
                    alert('❌ خطأ في تطبيق الترتيب: ' + data.message);
                }
            } catch (error) {
                document.getElementById('order-status').textContent = 'خطأ';
                document.getElementById('order-status').className = 'badge bg-danger ms-auto';
                alert('خطأ في الاتصال: ' + error.message);
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sort me-2"></i>تطبيق الترتيب المخصص';
        }

        // نقل المستخدمين
        async function migrateAllUsers() {
            if (!confirm('👥 نقل المستخدمين فقط!\n\nسيتم:\n• نقل جميع المستخدمين (بدون عاملات)\n• تقسيم name → first_name + last_name\n• معالجة حسب SYSTEM_STATUS.md\n• تحديث الموجود وإضافة المفقود\n\nهل تريد المتابعة؟')) {
                return;
            }

            const btn = document.getElementById('migrateBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري نقل المستخدمين...';

            document.getElementById('migrate-status').textContent = 'قيد النقل';
            document.getElementById('migrate-status').className = 'badge bg-warning ms-auto';

            try {
                const response = await fetch('/migration/migrate-all', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('migrate-status').textContent = 'مكتمل';
                    document.getElementById('migrate-status').className = 'badge bg-success ms-auto';

                    document.getElementById('migrateResults').innerHTML = `
                        <div class="alert alert-success">
                            <h6>✅ نقل المستخدمين مكتمل بنجاح!</h6>
                            <p><strong>إجمالي المعالج:</strong> ${data.total_processed} مستخدم</p>
                            <p><strong>مستخدمين جدد:</strong> ${data.inserted_users}</p>
                            <p><strong>مستخدمين محدثين:</strong> ${data.updated_users}</p>
                            <p><strong>تم التخطي:</strong> ${data.skipped_users}</p>
                            <p><strong>أسماء مقسمة:</strong> ${data.names_split}</p>
                        </div>
                    `;
                    document.getElementById('migrateResults').style.display = 'block';

                    // إظهار قسم رفع Customers
                    document.getElementById('customersSection').style.display = 'block';

                    // تحديث الإحصائيات
                    checkSystemStatus();

                    alert(`🎉 نقل المستخدمين مكتمل!\n\n📊 معالج: ${data.total_processed} مستخدم\n➕ جديد: ${data.inserted_users}\n🔄 محدث: ${data.updated_users}\n⏭️ تخطي: ${data.skipped_users}\n📝 أسماء مقسمة: ${data.names_split}\n\n📤 يمكنك الآن رفع ملف Customers أدناه`);
                } else {
                    document.getElementById('migrate-status').textContent = 'خطأ';
                    document.getElementById('migrate-status').className = 'badge bg-danger ms-auto';
                    alert('❌ خطأ في نقل المستخدمين: ' + data.message);
                }
            } catch (error) {
                document.getElementById('migrate-status').textContent = 'خطأ';
                document.getElementById('migrate-status').className = 'badge bg-danger ms-auto';
                alert('خطأ في الاتصال: ' + error.message);
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-users me-2"></i>نقل جميع المستخدمين (حسب SYSTEM_STATUS.md)';
        }

        // رفع ودمج Customers
        async function uploadCustomers() {
            const fileInput = document.getElementById('customers_file');

            if (!fileInput.files[0]) {
                alert('يرجى اختيار ملف Customers أولاً');
                return;
            }

            if (!confirm('📤 رفع ودمج Customers!\n\nسيتم:\n• دمج بيانات Customers في جدول Users\n• ربط بناءً على user_id\n• تطبيق التحويلات المحددة\n\nهل تريد المتابعة؟')) {
                return;
            }

            const formData = new FormData();
            formData.append('customers_file', fileInput.files[0]);

            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري رفع Customers...';

            try {
                const response = await fetch('/migration/upload-customers', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('customersResults').innerHTML = `
                        <div class="alert alert-success">
                            <h6>✅ تم دمج Customers بنجاح!</h6>
                            <p><strong>أسطر معالجة:</strong> ${data.processed_rows}</p>
                            <p><strong>مستخدمين محدثين:</strong> ${data.updated_users}</p>
                            <p><strong>أسطر مُتخطاة:</strong> ${data.skipped_rows}</p>
                        </div>
                    `;
                    document.getElementById('customersResults').style.display = 'block';

                    // تحديث الإحصائيات
                    checkSystemStatus();

                    alert(`🎉 تم دمج Customers بنجاح!\n\n📊 معالج: ${data.processed_rows} سطر\n🔄 محدث: ${data.updated_users} مستخدم\n⏭️ تخطي: ${data.skipped_rows} سطر`);
                } else {
                    document.getElementById('customersResults').innerHTML = `
                        <div class="alert alert-danger">
                            <h6>❌ خطأ في رفع Customers</h6>
                            <p>${data.message}</p>
                        </div>
                    `;
                    document.getElementById('customersResults').style.display = 'block';
                    alert('❌ خطأ في رفع Customers: ' + data.message);
                }
            } catch (error) {
                alert('خطأ في الاتصال: ' + error.message);
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-upload me-2"></i>رفع ودمج بيانات Customers';
        }

        // تحميل حالة النظام عند بدء الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            checkSystemStatus();
        });
    </script>
</body>
</html>
