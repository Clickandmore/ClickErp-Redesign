@extends('admin.layout.master')

@section('title', 'نظام نقل البيانات الموحد')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="text-primary">
                        <i class="fas fa-sync-alt me-2"></i>
                        نظام نقل البيانات الموحد
                    </h2>
                    <p class="text-muted">دمج البيانات من ERP Operations إلى Platinum مع معالجة الأعمدة المختلفة</p>
                </div>
                <div>
                    <span class="badge bg-info fs-6">3 قواعد بيانات</span>
                    <span class="badge bg-success fs-6">معالجة ذكية</span>
                </div>
            </div>

            <!-- حالة قواعد البيانات -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6><i class="fas fa-database me-2"></i>ERP Operations (المصدر)</h6>
                        </div>
                        <div class="card-body" id="erpStatus">
                            <div class="text-center">
                                <div class="spinner-border spinner-border-sm"></div>
                                جاري الفحص...
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h6><i class="fas fa-database me-2"></i>Platinum القديمة</h6>
                        </div>
                        <div class="card-body" id="platinumOldStatus">
                            <div class="text-center">
                                <div class="spinner-border spinner-border-sm"></div>
                                جاري الفحص...
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h6><i class="fas fa-database me-2"></i>Platinum الجديدة (الهدف)</h6>
                        </div>
                        <div class="card-body" id="platinumNewStatus">
                            <div class="text-center">
                                <div class="spinner-border spinner-border-sm"></div>
                                جاري الفحص...
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- المرحلة 1: الرفع اليدوي -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5><i class="fas fa-upload me-2"></i>المرحلة 1: الرفع اليدوي للملفات</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="border rounded p-4 text-center" style="border-style: dashed !important; cursor: pointer;" onclick="document.getElementById('csvFile').click();">
                                <input type="file" id="csvFile" accept=".csv" style="display: none;" multiple>
                                <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                                <h5>اسحب ملفات CSV هنا</h5>
                                <p class="text-muted">أو انقر لاختيار الملفات</p>
                                <button type="button" class="btn btn-primary">
                                    <i class="fas fa-folder-open me-2"></i>اختيار ملفات
                                </button>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div id="uploadedFiles">
                                <h6 class="text-success">الملفات المرفوعة:</h6>
                                <div id="filesList" class="small">
                                    <p class="text-muted">لا توجد ملفات مرفوعة بعد</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- المرحلة 2: تحليل الأعمدة والفروق -->
            <div class="card mb-4" id="analysisCard" style="display: none;">
                <div class="card-header bg-warning text-dark">
                    <h5><i class="fas fa-search me-2"></i>المرحلة 2: تحليل الفروق والأعمدة</h5>
                </div>
                <div class="card-body">
                    <button type="button" id="analyzeBtn" class="btn btn-warning btn-lg w-100 mb-3">
                        <i class="fas fa-chart-bar me-2"></i>
                        تحليل الفروق بين قواعد البيانات
                    </button>
                    <div id="analysisResults"></div>
                </div>
            </div>

            <!-- المرحلة 3: معالجة الأعمدة المختلفة -->
            <div class="card mb-4" id="columnsCard" style="display: none;">
                <div class="card-header bg-primary text-white">
                    <h5><i class="fas fa-columns me-2"></i>المرحلة 3: معالجة الأعمدة المختلفة</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>معالجة الأعمدة المختلفة:</h6>
                        <ul class="mb-0">
                            <li><strong>name</strong> (القديمة) → <strong>first_name + last_name</strong> (الجديدة)</li>
                            <li><strong>phone</strong> (القديمة) → <strong>phone</strong> (منظف ومحدث)</li>
                            <li><strong>address</strong> (القديمة) → <strong>address + city</strong> (مقسم)</li>
                            <li>إضافة أعمدة جديدة: <strong>national_num, phone2, birthdate</strong></li>
                        </ul>
                    </div>

                    <button type="button" id="processColumnsBtn" class="btn btn-primary btn-lg w-100" disabled>
                        <i class="fas fa-cogs me-2"></i>
                        معالجة وإضافة الأعمدة المفقودة
                    </button>
                    <div id="columnsResults" class="mt-3"></div>
                </div>
            </div>

            <!-- المرحلة 4: النقل والدمج -->
            <div class="card mb-4" id="migrationCard" style="display: none;">
                <div class="card-header bg-success text-white">
                    <h5><i class="fas fa-exchange-alt me-2"></i>المرحلة 4: النقل والدمج النهائي</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <h6><i class="fas fa-shield-alt me-2"></i>ضمانات الأمان:</h6>
                        <ul class="mb-0">
                            <li>✅ عدم حذف أي بيانات موجودة</li>
                            <li>✅ دمج ذكي بدون تكرار</li>
                            <li>✅ معالجة الأعمدة المختلفة تلقائياً</li>
                            <li>✅ نسخة احتياطية قبل البدء</li>
                        </ul>
                    </div>

                    <button type="button" id="executeMigrationBtn" class="btn btn-success btn-lg w-100" disabled>
                        <i class="fas fa-rocket me-2"></i>
                        تنفيذ النقل والدمج النهائي
                    </button>
                    <div id="migrationResults" class="mt-3"></div>
                </div>
            </div>

            <!-- شريط التقدم العام -->
            <div id="progressSection" style="display: none;" class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h6><i class="fas fa-tasks me-2"></i>تقدم العملية</h6>
                </div>
                <div class="card-body">
                    <div class="progress mb-3" style="height: 15px;">
                        <div id="overallProgress" class="progress-bar progress-bar-striped progress-bar-animated"
                             role="progressbar" style="width: 0%"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <div id="progressText" class="h6"></div>
                            <div id="progressDetails" class="text-muted"></div>
                        </div>
                        <div class="col-md-4 text-end">
                            <div id="progressStats" class="small"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- منطقة النتائج النهائية -->
            <div id="finalResults"></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let uploadedFiles = [];
    let analysisData = null;

    document.addEventListener('DOMContentLoaded', function() {
        loadDatabaseStatus();

        // إعداد رفع الملفات
        const csvFile = document.getElementById('csvFile');
        csvFile.addEventListener('change', handleFileUpload);

        // الأزرار
        document.getElementById('analyzeBtn').addEventListener('click', analyzeData);
        document.getElementById('processColumnsBtn').addEventListener('click', processColumns);
        document.getElementById('executeMigrationBtn').addEventListener('click', executeMigration);
    });

    async function loadDatabaseStatus() {
        try {
            const response = await fetch('/admin/migration/check-databases', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const data = await response.json();

            if (data.success) {
                updateDatabaseStatus(data.databases);
            } else {
                showError('خطأ في فحص قواعد البيانات: ' + data.message);
            }
        } catch (error) {
            showError('خطأ في الاتصال: ' + error.message);
        }
    }

    function updateDatabaseStatus(databases) {
        // ERP Operations
        if (databases.erp_operations) {
            document.getElementById('erpStatus').innerHTML = `
                <div class="text-center">
                    <h4 class="text-primary">${databases.erp_operations.records_count.toLocaleString()}</h4>
                    <small>سجل في ${databases.erp_operations.tables_count} جدول</small>
                    <div class="mt-2">
                        <span class="badge bg-success">متصل</span>
                    </div>
                </div>
            `;
        }

        // Platinum Old
        if (databases.platinum_old) {
            document.getElementById('platinumOldStatus').innerHTML = `
                <div class="text-center">
                    <h4 class="text-warning">${databases.platinum_old.records_count.toLocaleString()}</h4>
                    <small>سجل في ${databases.platinum_old.tables_count} جدول</small>
                    <div class="mt-2">
                        <span class="badge bg-success">متصل</span>
                    </div>
                </div>
            `;
        }

        // Platinum New
        if (databases.platinum_new) {
            document.getElementById('platinumNewStatus').innerHTML = `
                <div class="text-center">
                    <h4 class="text-success">${databases.platinum_new.records_count.toLocaleString()}</h4>
                    <small>سجل في ${databases.platinum_new.tables_count} جدول</small>
                    <div class="mt-2">
                        <span class="badge bg-primary">جاهز للنقل</span>
                    </div>
                </div>
            `;
        }
    }

    async function handleFileUpload(event) {
        const files = Array.from(event.target.files);
        const formData = new FormData();

        files.forEach((file, index) => {
            formData.append(`files[${index}]`, file);
        });

        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

        try {
            showProgress();
            updateProgress(20, 'جاري رفع الملفات...');

            const response = await fetch('/admin/migration/upload-files', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                uploadedFiles = data.files;
                displayUploadedFiles(data.files);
                updateProgress(50, 'تم رفع الملفات بنجاح');

                // إظهار خطوة التحليل
                document.getElementById('analysisCard').style.display = 'block';
                setTimeout(() => {
                    document.getElementById('analysisCard').scrollIntoView();
                }, 500);
            } else {
                showError('خطأ في رفع الملفات: ' + data.message);
            }
        } catch (error) {
            showError('خطأ في رفع الملفات: ' + error.message);
        }
    }

    function displayUploadedFiles(files) {
        let html = '';
        files.forEach(file => {
            html += `
                <div class="mb-2 p-2 bg-light rounded">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-file-csv text-success me-2"></i>
                            <strong>${file.original_name}</strong>
                        </div>
                        <div>
                            <span class="badge bg-primary">${file.records_count} سجل</span>
                            <span class="badge bg-info">${file.size}</span>
                        </div>
                    </div>
                </div>
            `;
        });

        document.getElementById('filesList').innerHTML = html;
    }

    async function analyzeData() {
        if (uploadedFiles.length === 0) {
            alert('يرجى رفع ملفات أولاً');
            return;
        }

        const btn = document.getElementById('analyzeBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري التحليل...';

        try {
            const response = await fetch('/admin/migration/analyze', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({files: uploadedFiles})
            });

            const data = await response.json();

            if (data.success) {
                analysisData = data;
                displayAnalysisResults(data.analysis);

                // إظهار خطوة معالجة الأعمدة
                document.getElementById('columnsCard').style.display = 'block';
                document.getElementById('processColumnsBtn').disabled = false;

                setTimeout(() => {
                    document.getElementById('columnsCard').scrollIntoView();
                }, 500);
            } else {
                showError('خطأ في التحليل: ' + data.message);
            }
        } catch (error) {
            showError('خطأ في التحليل: ' + error.message);
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-chart-bar me-2"></i>تحليل الفروق بين قواعد البيانات';
    }

    function displayAnalysisResults(analysis) {
        const html = `
            <div class="alert alert-success">
                <h6>✅ تم التحليل بنجاح</h6>
                <div class="row text-center mt-3">
                    <div class="col-md-3">
                        <div class="bg-primary text-white p-2 rounded">
                            <strong>${analysis.missing_columns}</strong><br><small>عمود مفقود</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="bg-warning text-white p-2 rounded">
                            <strong>${analysis.different_columns}</strong><br><small>عمود مختلف</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="bg-info text-white p-2 rounded">
                            <strong>${analysis.total_records}</strong><br><small>سجل للنقل</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="bg-success text-white p-2 rounded">
                            <strong>${analysis.estimated_time}</strong><br><small>دقيقة (تقدير)</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-danger">❌ الأعمدة المفقودة:</h6>
                    <ul class="small">
                        ${analysis.missing_columns_list.map(col => `<li><code>${col.table}.${col.column}</code> - ${col.type}</li>`).join('')}
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6 class="text-warning">🔄 الأعمدة المختلفة:</h6>
                    <ul class="small">
                        ${analysis.different_columns_list.map(col => `<li><code>${col.old_column}</code> → <code>${col.new_columns.join(' + ')}</code></li>`).join('')}
                    </ul>
                </div>
            </div>
        `;

        document.getElementById('analysisResults').innerHTML = html;
    }

    async function processColumns() {
        const btn = document.getElementById('processColumnsBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري المعالجة...';

        updateProgress(60, 'معالجة الأعمدة المختلفة...');

        try {
            const response = await fetch('/admin/migration/process-columns', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({analysis: analysisData})
            });

            const data = await response.json();

            if (data.success) {
                displayColumnsResults(data);

                // إظهار خطوة النقل
                document.getElementById('migrationCard').style.display = 'block';
                document.getElementById('executeMigrationBtn').disabled = false;

                updateProgress(80, 'تم معالجة الأعمدة - جاهز للنقل');

                setTimeout(() => {
                    document.getElementById('migrationCard').scrollIntoView();
                }, 500);
            } else {
                showError('خطأ في معالجة الأعمدة: ' + data.message);
            }
        } catch (error) {
            showError('خطأ في معالجة الأعمدة: ' + error.message);
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-cogs me-2"></i>معالجة وإضافة الأعمدة المفقودة';
    }

    function displayColumnsResults(data) {
        const html = `
            <div class="alert alert-success">
                <h6>✅ تم معالجة الأعمدة بنجاح</h6>
                <p><strong>الأعمدة المُضافة:</strong> ${data.added_columns.length}</p>
                <p><strong>الأعمدة المُحولة:</strong> ${data.converted_columns.length}</p>
                <p><strong>الجداول المُحدثة:</strong> ${data.updated_tables.length}</p>
            </div>
        `;

        document.getElementById('columnsResults').innerHTML = html;
    }

    async function executeMigration() {
        if (!confirm('هل أنت متأكد من بدء النقل النهائي؟ سيتم دمج جميع البيانات بأمان.')) {
            return;
        }

        const btn = document.getElementById('executeMigrationBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري النقل...';

        updateProgress(90, 'تنفيذ النقل النهائي...');

        try {
            const response = await fetch('/admin/migration/execute-final', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    files: uploadedFiles,
                    analysis: analysisData
                })
            });

            const data = await response.json();

            if (data.success) {
                updateProgress(100, 'تم إكمال النقل بنجاح!');
                displayFinalResults(data);
            } else {
                showError('خطأ في النقل: ' + data.message);
            }
        } catch (error) {
            showError('خطأ في النقل: ' + error.message);
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-rocket me-2"></i>تنفيذ النقل والدمج النهائي';
    }

    function displayFinalResults(data) {
        const html = `
            <div class="card" style="background: linear-gradient(45deg, #d4edda 0%, #c3e6cb 100%); border: 3px solid #28a745;">
                <div class="card-header bg-success text-white">
                    <h4><i class="fas fa-trophy me-2"></i>تم إكمال النقل الموحد بنجاح!</h4>
                </div>
                <div class="card-body">
                    <div class="row text-center mb-4">
                        <div class="col-md-3">
                            <div class="bg-primary text-white p-3 rounded">
                                <h3>${data.results.total_users.toLocaleString()}</h3>
                                <small>مستخدم موحد</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="bg-success text-white p-3 rounded">
                                <h3>${data.results.total_maids.toLocaleString()}</h3>
                                <small>عاملة مدموجة</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="bg-info text-white p-3 rounded">
                                <h3>${data.results.added_columns}</h3>
                                <small>عمود مُضاف</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="bg-warning text-white p-3 rounded">
                                <h3>${data.results.converted_columns}</h3>
                                <small>عمود مُحول</small>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-success">
                        <h5>🎉 تم بنجاح:</h5>
                        <ul>
                            <li>✅ دمج ${data.results.erp_users} مستخدم من ERP Operations</li>
                            <li>✅ دمج ${data.results.platinum_users} مستخدم من Platinum القديمة</li>
                            <li>✅ معالجة الأعمدة المختلفة (name → first_name + last_name)</li>
                            <li>✅ إضافة الأعمدة المفقودة (national_num, phone2, birthdate)</li>
                            <li>✅ ربط جميع المعرفات بين الجداول</li>
                            <li>✅ حفظ جميع البيانات الموجودة</li>
                        </ul>
                    </div>

                    <div class="text-center mt-4">
                        <a href="/admin/migration/verify-results" class="btn btn-outline-primary btn-lg me-3">
                            <i class="fas fa-search me-2"></i>فحص النتائج التفصيلية
                        </a>
                        <a href="/admin/users" class="btn btn-outline-success btn-lg">
                            <i class="fas fa-users me-2"></i>عرض المستخدمين المدموجين
                        </a>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('finalResults').innerHTML = html;
        document.getElementById('finalResults').scrollIntoView();
    }

    function showProgress() {
        document.getElementById('progressSection').style.display = 'block';
    }

    function updateProgress(percent, text, details = '', stats = '') {
        document.getElementById('overallProgress').style.width = percent + '%';
        document.getElementById('progressText').textContent = text;
        document.getElementById('progressDetails').textContent = details;
        document.getElementById('progressStats').textContent = stats;
    }

    function showError(message) {
        const html = `
            <div class="alert alert-danger alert-dismissible fade show">
                <strong>خطأ:</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        document.getElementById('finalResults').insertAdjacentHTML('beforeend', html);
    }
</script>
@endpush
@endsection
