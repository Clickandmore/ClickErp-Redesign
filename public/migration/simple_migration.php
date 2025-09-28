<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نقل المستخدمين - بسيط ومباشر</title>

    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container {
            max-width: 800px;
        }

        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            background: rgba(255,255,255,0.95);
            margin-bottom: 30px;
        }

        .card-header {
            border-radius: 20px 20px 0 0 !important;
            font-weight: 600;
            padding: 20px;
        }

        .card-body {
            padding: 30px;
        }

        .btn-huge {
            padding: 20px;
            font-size: 1.2rem;
            border-radius: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .btn-huge:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .step-number {
            display: inline-block;
            width: 40px;
            height: 40px;
            line-height: 40px;
            text-align: center;
            border-radius: 50%;
            background: white;
            color: #333;
            font-weight: bold;
            margin-left: 10px;
        }

        .alert {
            border-radius: 15px;
            margin-top: 20px;
        }

        .spinner-border {
            width: 1.5rem;
            height: 1.5rem;
            margin-left: 10px;
        }

        h1 {
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            margin-bottom: 40px;
            text-align: center;
        }

        .status-box {
            background: rgba(255,255,255,0.2);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            color: white;
            text-align: center;
        }

        .status-box h3 {
            margin: 0;
            font-size: 2rem;
        }

        .status-box small {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-users me-3"></i>نقل المستخدمين - بسيط ومباشر</h1>

        <!-- حالة النظام -->
        <div class="row">
            <div class="col-md-4">
                <div class="status-box">
                    <h3 id="oldCount">-</h3>
                    <small>مستخدمين في القديمة</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="status-box">
                    <h3 id="newCount">-</h3>
                    <small>مستخدمين في الجديدة</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="status-box">
                    <h3 id="customersCount">-</h3>
                    <small>customers للدمج</small>
                </div>
            </div>
        </div>

        <!-- الخطوات البسيطة -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-list-ol me-2"></i>3 خطوات بسيطة فقط</h4>
            </div>
            <div class="card-body">

                <!-- الخطوة 1 -->
                <button type="button" class="btn btn-warning btn-huge w-100" onclick="addColumns()" id="btn1">
                    <span class="step-number">1</span>
                    <i class="fas fa-plus-circle me-2"></i>
                    إضافة الأعمدة الناقصة (حسب SYSTEM_STATUS.md)
                </button>
                <div id="result1"></div>

                <!-- الخطوة 2 -->
                <button type="button" class="btn btn-success btn-huge w-100" onclick="migrateUsers()" id="btn2" disabled>
                    <span class="step-number">2</span>
                    <i class="fas fa-users me-2"></i>
                    نقل جميع المستخدمين
                </button>
                <div id="result2"></div>

                <!-- الخطوة 3 -->
                <button type="button" class="btn btn-info btn-huge w-100" onclick="migrateCustomers()" id="btn3" disabled>
                    <span class="step-number">3</span>
                    <i class="fas fa-user-plus me-2"></i>
                    دمج بيانات Customers
                </button>
                <div id="result3"></div>

            </div>
        </div>

        <!-- معلومات التحويلات -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>ما سيتم تنفيذه</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>✅ التحويلات:</h6>
                        <ul>
                            <li>name → first_name + last_name</li>
                            <li>banned → is_blocked + status</li>
                            <li>whatsapp_phone → phone2</li>
                            <li>national_identification_number → national_num</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>📋 من Customers:</h6>
                        <ul>
                            <li>other_phone → phone2</li>
                            <li>job → job + profession</li>
                            <li>birth_date → birthdate</li>
                            <li>rooms_number → rooms_num</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        // فحص الحالة عند التحميل
        checkStatus();

        function checkStatus() {
            fetch('file_processor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=check_system_status'
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('oldCount').textContent = data.old_users_count || '0';
                    document.getElementById('newCount').textContent = data.new_users_count || '0';
                    document.getElementById('customersCount').textContent = data.customers_count || '0';
                }
            })
            .catch(e => console.error(e));
        }

        // الخطوة 1: إضافة الأعمدة
        async function addColumns() {
            const btn = document.getElementById('btn1');
            const result = document.getElementById('result1');

            if (!confirm('هل تريد إضافة جميع الأعمدة الناقصة من SYSTEM_STATUS.md؟')) {
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border"></span> جاري إضافة الأعمدة...';

            try {
                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=add_system_status_columns'
                });

                const data = await response.json();

                if (data.success) {
                    result.innerHTML = `
                        <div class="alert alert-success">
                            <strong>✅ ${data.message}</strong><br>
                            ${data.added_columns.length > 0 ? 'الأعمدة: ' + data.added_columns.join(', ') : 'جميع الأعمدة موجودة مسبقاً'}
                        </div>
                    `;

                    // تفعيل الخطوة التالية
                    document.getElementById('btn2').disabled = false;

                    alert(data.message);
                } else {
                    result.innerHTML = `<div class="alert alert-danger">❌ ${data.message}</div>`;
                }
            } catch (error) {
                result.innerHTML = `<div class="alert alert-danger">❌ خطأ: ${error.message}</div>`;
            }

            btn.disabled = false;
            btn.innerHTML = '<span class="step-number">1</span><i class="fas fa-plus-circle me-2"></i>إضافة الأعمدة الناقصة (حسب SYSTEM_STATUS.md)';
        }

        // الخطوة 2: نقل المستخدمين
        async function migrateUsers() {
            const btn = document.getElementById('btn2');
            const result = document.getElementById('result2');

            if (!confirm('هل تريد نقل جميع المستخدمين من القاعدة القديمة؟\n\nسيتم:\n• نقل جميع المستخدمين\n• تقسيم الأسماء\n• تطبيق التحويلات')) {
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border"></span> جاري نقل المستخدمين...';

            try {
                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=migrate_users_simple'
                });

                const data = await response.json();

                if (data.success) {
                    result.innerHTML = `
                        <div class="alert alert-success">
                            <strong>✅ ${data.message}</strong><br>
                            إجمالي: ${data.total_users} | جديد: ${data.migrated} | محدث: ${data.updated}
                        </div>
                    `;

                    // تفعيل الخطوة التالية
                    document.getElementById('btn3').disabled = false;

                    // تحديث الحالة
                    checkStatus();

                    alert(data.message);
                } else {
                    result.innerHTML = `<div class="alert alert-danger">❌ ${data.message}</div>`;
                }
            } catch (error) {
                result.innerHTML = `<div class="alert alert-danger">❌ خطأ: ${error.message}</div>`;
            }

            btn.disabled = false;
            btn.innerHTML = '<span class="step-number">2</span><i class="fas fa-users me-2"></i>نقل جميع المستخدمين';
        }

        // الخطوة 3: دمج Customers
        async function migrateCustomers() {
            const btn = document.getElementById('btn3');
            const result = document.getElementById('result3');

            if (!confirm('هل تريد دمج بيانات Customers مع المستخدمين؟')) {
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border"></span> جاري دمج Customers...';

            try {
                const response = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=migrate_customers_simple'
                });

                const data = await response.json();

                if (data.success) {
                    result.innerHTML = `
                        <div class="alert alert-success">
                            <strong>✅ ${data.message}</strong><br>
                            ${data.updated_users ? 'محدث: ' + data.updated_users + ' مستخدم' : ''}
                        </div>
                    `;

                    // تحديث الحالة
                    checkStatus();

                    alert(data.message + '\n\n🎉 تمت جميع العمليات بنجاح!');
                } else {
                    result.innerHTML = `<div class="alert alert-danger">❌ ${data.message}</div>`;
                }
            } catch (error) {
                result.innerHTML = `<div class="alert alert-danger">❌ خطأ: ${error.message}</div>`;
            }

            btn.disabled = false;
            btn.innerHTML = '<span class="step-number">3</span><i class="fas fa-user-plus me-2"></i>دمج بيانات Customers';
        }
    </script>
</body>
</html>
