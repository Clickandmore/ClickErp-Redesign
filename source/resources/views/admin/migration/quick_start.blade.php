<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام النقل الموحد </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .hero {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        .step-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }
        .step-card:hover {
            transform: translateY(-3px);
            border-color: #007bff;
        }
        .step-card.active {
            border-color: #28a745;
            background: linear-gradient(45deg, #d4edda 0%, #f8f9fa 100%);
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <!-- Hero -->
                <div class="hero text-center">
                    <h1><i class="fas fa-sync-alt me-3"></i>نظام النقل الموحد</h1>
                   
                </div>

                <!-- حالة قواعد البيانات -->
                <div class="row mb-4" id="databasesStatus">
                    <div class="col-md-12 text-center">
                        <div class="spinner-border text-primary"></div>
                        <p>جاري فحص قواعد البيانات...</p>
                    </div>
                </div>

                <!-- الخطوات -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card step-card h-100" onclick="showUploadSection()">
                            <div class="card-body text-center">
                                <i class="fas fa-upload fa-3x text-primary mb-3"></i>
                                <h5>1. رفع الملفات يدوياً</h5>
                                <p class="text-muted">رفع ملفات CSV من ERP Operations</p>
                                <span class="badge bg-primary">ابدأ هنا</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <div class="card step-card h-100" onclick="processData()">
                            <div class="card-body text-center">
                                <i class="fas fa-cogs fa-3x text-warning mb-3"></i>
                                <h5>2. معالجة الأعمدة</h5>
                                <p class="text-muted">معالجة name → first_name + last_name</p>
                                <span class="badge bg-warning">معالجة ذكية</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <div class="card step-card h-100" onclick="executeMigration()">
                            <div class="card-body text-center">
                                <i class="fas fa-rocket fa-3x text-success mb-3"></i>
                                <h5>3. النقل النهائي</h5>
                                <p class="text-muted">دمج جميع البيانات بأمان</p>
                                <span class="badge bg-success">نقل آمن</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- منطقة رفع الملفات -->
                <div id="uploadSection" style="display: none;" class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-cloud-upload-alt me-2"></i>رفع الملفات</h5>
                    </div>
                    <div class="card-body">
                        <form id="uploadForm" enctype="multipart/form-data">
                            <div class="mb-3">
                                <input type="file" id="csvFiles" name="files[]" class="form-control" accept=".csv" multiple required>
                                <div class="form-text">اختر ملفات CSV (users, maids, orders, etc.)</div>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-upload me-2"></i>رفع الملفات
                                </button>
                            </div>
                        </form>
                        <div id="uploadResults" class="mt-3"></div>
                    </div>
                </div>

                <!-- النقل السريع -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5><i class="fas fa-bolt me-2"></i>النقل السريع (مُوصى)</h5>
                    </div>
                    <div class="card-body text-center">
                        <p>تنفيذ جميع خطوات النقل تلقائياً مع معالجة الأعمدة المختلفة</p>
                        <button type="button" id="quickMigrateBtn" class="btn btn-success btn-lg">
                            <i class="fas fa-magic me-2"></i>
                            تنفيذ النقل السريع الآن
                        </button>
                    </div>
                </div>

                <!-- منطقة النتائج -->
                <div id="resultsArea"></div>

                <!-- معلومات مهمة -->
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h6><i class="fas fa-info-circle me-2"></i>معلومات مهمة</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-success">✅ ما تم حله:</h6>
                                <ul>
                                    <li>مشكلة الجداول الموجودة مسبقاً</li>
                                    <li>دمج النظام في المشروع الأساسي</li>
                                    <li>معالجة الأعمدة المختلفة (name → first_name + last_name)</li>
                                    <li>إضافة الأعمدة المفقودة تلقائياً</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-info">🛡️ ضمانات الأمان:</h6>
                                <ul>
                                    <li>عدم حذف أي بيانات موجودة</li>
                                    <li>دمج ذكي بدون تكرار</li>
                                    <li>نسخة احتياطية تلقائية</li>
                                    <li>معالجة ذكية للأخطاء</li>
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
            loadDatabasesStatus();

            document.getElementById('uploadForm').addEventListener('submit', handleUpload);
            document.getElementById('quickMigrateBtn').addEventListener('click', executeQuickMigration);
        });

        async function loadDatabasesStatus() {
            try {
                const response = await fetch('/admin/migration/check-databases', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    displayDatabasesStatus(data.databases);
                } else {
                    showError('خطأ في فحص قواعد البيانات');
                }
            } catch (error) {
                showError('خطأ في الاتصال: ' + error.message);
            }
        }

        function displayDatabasesStatus(databases) {
            let html = '<div class="row">';

            for (const [key, db] of Object.entries(databases)) {
                const statusColor = db.status === 'connected' ? 'success' : 'danger';
                const dbNames = {
                    'erp_operations': 'ERP Operations',
                    'platinum_old': 'Platinum القديمة',
                    'platinum_new': 'Platinum الجديدة'
                };

                html += `
                    <div class="col-md-4 mb-3">
                        <div class="card border-${statusColor}">
                            <div class="card-body text-center">
                                <h6>${dbNames[key]}</h6>
                                <div class="h4 text-${statusColor}">${db.records_count.toLocaleString()}</div>
                                <small>سجل في ${db.tables_count} جدول</small>
                                <div class="mt-2">
                                    <span class="badge bg-${statusColor}">${db.status === 'connected' ? 'متصل' : 'غير متصل'}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }

            html += '</div>';
            document.getElementById('databasesStatus').innerHTML = html;
        }

        function showUploadSection() {
            document.getElementById('uploadSection').style.display = 'block';
            document.getElementById('uploadSection').scrollIntoView();
        }

        async function handleUpload(e) {
            e.preventDefault();

            const formData = new FormData(e.target);
            formData.append('_token', '{{ csrf_token() }}');

            try {
                const response = await fetch('/admin/migration/upload-files', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    let html = '<div class="alert alert-success"><h6>تم رفع الملفات بنجاح:</h6><ul>';
                    data.files.forEach(file => {
                        html += `<li><strong>${file.original_name}</strong> - ${file.records_count} سجل (${file.size})</li>`;
                    });
                    html += '</ul></div>';

                    document.getElementById('uploadResults').innerHTML = html;
                } else {
                    showError('خطأ في الرفع: ' + data.message);
                }
            } catch (error) {
                showError('خطأ في الرفع: ' + error.message);
            }
        }

        async function executeQuickMigration() {
            if (!confirm('هل تريد بدء النقل السريع؟ سيتم دمج جميع البيانات بأمان.')) {
                return;
            }

            const btn = document.getElementById('quickMigrateBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري النقل...';

            try {
                const response = await fetch('/admin/migration/quick-migrate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    showSuccess(data);
                } else {
                    showError('خطأ في النقل: ' + data.message);
                }
            } catch (error) {
                showError('خطأ في النقل: ' + error.message);
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-magic me-2"></i>تنفيذ النقل السريع الآن';
        }

        function showSuccess(data) {
            const html = `
                <div class="card" style="background: linear-gradient(45deg, #d4edda 0%, #c3e6cb 100%); border: 3px solid #28a745;">
                    <div class="card-header bg-success text-white">
                        <h4><i class="fas fa-check-circle me-2"></i>تم النقل بنجاح!</h4>
                    </div>
                    <div class="card-body">
                        <div class="row text-center mb-3">
                            <div class="col-md-4">
                                <div class="bg-primary text-white p-3 rounded">
                                    <h3>${data.results.users_migrated}</h3>
                                    <small>مستخدم منقول</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="bg-success text-white p-3 rounded">
                                    <h3>${data.results.maids_migrated}</h3>
                                    <small>عاملة مدموجة</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="bg-info text-white p-3 rounded">
                                    <h3>✓</h3>
                                    <small>معالجة الأعمدة</small>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-success">
                            <h6>🎉 تم بنجاح:</h6>
                            <ul class="mb-0">
                                <li>✅ نقل ${data.results.users_migrated} مستخدم من ERP Operations</li>
                                <li>✅ دمج ${data.results.maids_migrated} عاملة</li>
                                <li>✅ معالجة الأعمدة (name → first_name + last_name)</li>
                                <li>✅ إضافة الأعمدة المفقودة (national_num, phone2, birthdate)</li>
                                <li>✅ جميع البيانات محفوظة في platinum_new</li>
                            </ul>
                        </div>

                        <div class="text-center mt-4">
                            <button type="button" onclick="verifyResults()" class="btn btn-outline-primary me-2">
                                <i class="fas fa-search me-1"></i>فحص النتائج
                            </button>
                            <a href="/admin/dashboard" class="btn btn-outline-success">
                                <i class="fas fa-home me-1"></i>العودة للوحة التحكم
                            </a>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('resultsArea').innerHTML = html;
        }

        async function verifyResults() {
            try {
                // فحص سريع للنتائج
                const verification = await fetch('/admin/migration/check-databases', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await verification.json();

                if (data.success) {
                    const platinumNew = data.databases.platinum_new;
                    alert(`✅ النتائج:\n- الجداول: ${platinumNew.tables_count}\n- السجلات: ${platinumNew.records_count}\n- الحالة: ${platinumNew.status}`);
                }
            } catch (error) {
                alert('خطأ في الفحص: ' + error.message);
            }
        }

        function showUploadSection() {
            document.getElementById('uploadSection').style.display = 'block';
            document.getElementById('uploadSection').scrollIntoView();
        }

        function showError(message) {
            const html = `
                <div class="alert alert-danger">
                    <strong>خطأ:</strong> ${message}
                    <div class="mt-2">
                        <button type="button" onclick="location.reload()" class="btn btn-sm btn-outline-danger">
                            إعادة المحاولة
                        </button>
                    </div>
                </div>
            `;
            document.getElementById('resultsArea').innerHTML = html;
        }
    </script>
</body>
</html>
