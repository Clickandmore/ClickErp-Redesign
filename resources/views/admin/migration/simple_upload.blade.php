@extends('admin.layout.master')

@section('title', 'رفع ونقل البيانات')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-10 mx-auto">
            <!-- Header -->
            <div class="text-center mb-4">
                <h2 class="text-primary">
                    <i class="fas fa-upload me-3"></i>
                    رفع ونقل البيانات - مبسط
                </h2>
                <p class="text-muted">رفع يدوي للملفات ومعالجة ذكية للأعمدة المختلفة</p>
            </div>

            <!-- خطوات العملية -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card text-center step-card" id="step1Card">
                        <div class="card-body">
                            <i class="fas fa-upload fa-2x text-primary mb-3"></i>
                            <h6>1. رفع الملفات</h6>
                            <div id="step1Status">
                                <span class="badge bg-primary">ابدأ هنا</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="card text-center step-card" id="step2Card">
                        <div class="card-body">
                            <i class="fas fa-search fa-2x text-warning mb-3"></i>
                            <h6>2. تحليل البيانات</h6>
                            <div id="step2Status">
                                <span class="badge bg-secondary">انتظار</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="card text-center step-card" id="step3Card">
                        <div class="card-body">
                            <i class="fas fa-columns fa-2x text-info mb-3"></i>
                            <h6>3. معالجة الأعمدة</h6>
                            <div id="step3Status">
                                <span class="badge bg-secondary">انتظار</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="card text-center step-card" id="step4Card">
                        <div class="card-body">
                            <i class="fas fa-check-circle fa-2x text-success mb-3"></i>
                            <h6>4. النقل النهائي</h6>
                            <div id="step4Status">
                                <span class="badge bg-secondary">انتظار</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- منطقة رفع الملفات -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5><i class="fas fa-cloud-upload-alt me-2"></i>رفع الملفات يدوياً</h5>
                </div>
                <div class="card-body">
                    <form id="uploadForm" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-8">
                                <div id="dropZone" class="border border-primary border-3 rounded p-4 text-center"
                                     style="border-style: dashed !important; cursor: pointer; min-height: 200px; display: flex; align-items: center; justify-content: center;">
                                    <div>
                                        <i class="fas fa-cloud-upload-alt fa-4x text-primary mb-3"></i>
                                        <h4>اسحب ملفات CSV هنا</h4>
                                        <p class="text-muted">أو انقر لاختيار الملفات</p>
                                        <input type="file" id="fileInput" name="files[]" accept=".csv" multiple style="display: none;">
                                        <button type="button" onclick="document.getElementById('fileInput').click()" class="btn btn-primary btn-lg">
                                            <i class="fas fa-folder-open me-2"></i>اختيار ملفات CSV
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <h6 class="text-success">ملفات مُوصى برفعها:</h6>
                                <ul class="list-group">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        users.csv
                                        <span class="badge bg-primary">أولوية</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        maids.csv
                                        <span class="badge bg-success">مهم</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        orders.csv
                                        <span class="badge bg-info">اختياري</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        contracts.csv
                                        <span class="badge bg-warning">اختياري</span>
                                    </li>
                                </ul>

                                <div class="mt-3">
                                    <h6 class="text-info">الملفات المرفوعة:</h6>
                                    <div id="uploadedFilesList">
                                        <small class="text-muted">لا توجد ملفات</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-3">
                            <button type="submit" id="uploadBtn" class="btn btn-success btn-lg" disabled>
                                <i class="fas fa-upload me-2"></i>
                                رفع الملفات المحددة
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- منطقة التحليل -->
            <div id="analysisSection" style="display: none;" class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5><i class="fas fa-chart-bar me-2"></i>تحليل الأعمدة والفروق</h5>
                </div>
                <div class="card-body">
                    <button type="button" id="analyzeBtn" class="btn btn-warning btn-lg w-100">
                        <i class="fas fa-search me-2"></i>
                        تحليل الفروق والأعمدة المختلفة
                    </button>
                    <div id="analysisResults" class="mt-3"></div>
                </div>
            </div>

            <!-- منطقة معالجة الأعمدة -->
            <div id="columnsSection" style="display: none;" class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5><i class="fas fa-tools me-2"></i>معالجة الأعمدة المختلفة</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-lightbulb me-2"></i>المعالجة الذكية ستقوم بـ:</h6>
                        <ul class="mb-0">
                            <li><strong>تقسيم:</strong> name → first_name + last_name</li>
                            <li><strong>تنظيف:</strong> phone (إزالة +966 وتوحيد التنسيق)</li>
                            <li><strong>إضافة:</strong> national_num, phone2, birthdate</li>
                            <li><strong>تحويل:</strong> العاملات لسير ذاتية</li>
                        </ul>
                    </div>

                    <button type="button" id="processBtn" class="btn btn-info btn-lg w-100">
                        <i class="fas fa-cogs me-2"></i>
                        تطبيق المعالجة الذكية
                    </button>
                    <div id="processResults" class="mt-3"></div>
                </div>
            </div>

            <!-- منطقة النقل النهائي -->
            <div id="migrationSection" style="display: none;" class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5><i class="fas fa-rocket me-2"></i>النقل النهائي</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <h6><i class="fas fa-shield-alt me-2"></i>ضمانات النقل:</h6>
                        <ul class="mb-0">
                            <li>✅ حفظ جميع البيانات الموجودة</li>
                            <li>✅ دمج ذكي بدون تكرار</li>
                            <li>✅ ربط صحيح للمعرفات</li>
                            <li>✅ نسخة احتياطية تلقائية</li>
                        </ul>
                    </div>

                    <button type="button" id="migrateBtn" class="btn btn-success btn-lg w-100">
                        <i class="fas fa-play me-2"></i>
                        بدء النقل النهائي
                    </button>
                    <div id="finalResults" class="mt-3"></div>
                </div>
            </div>

            <!-- شريط التقدم -->
            <div id="progressBar" style="display: none;" class="card">
                <div class="card-body">
                    <div class="progress mb-3" style="height: 20px;">
                        <div id="progress" class="progress-bar progress-bar-striped progress-bar-animated"
                             style="width: 0%"></div>
                    </div>
                    <div id="progressText" class="text-center h6"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let uploadedFiles = [];

    document.addEventListener('DOMContentLoaded', function() {
        initializeFileUpload();
        setupEventListeners();
    });

    function initializeFileUpload() {
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const uploadForm = document.getElementById('uploadForm');

        // Click to upload
        dropZone.addEventListener('click', () => fileInput.click());

        // File selection
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                updateStep(1, 'success', `${this.files.length} ملف محدد`);
                document.getElementById('uploadBtn').disabled = false;
                displaySelectedFiles(this.files);
            }
        });

        // Form submission
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleFileUpload();
        });

        // Drag and drop
        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('border-success');
        });

        dropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('border-success');
        });

        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('border-success');

            const files = e.dataTransfer.files;
            fileInput.files = files;
            fileInput.dispatchEvent(new Event('change'));
        });
    }

    function setupEventListeners() {
        document.getElementById('analyzeBtn').addEventListener('click', analyzeData);
        document.getElementById('processBtn').addEventListener('click', processColumns);
        document.getElementById('migrateBtn').addEventListener('click', executeMigration);
    }

    function displaySelectedFiles(files) {
        let html = '';
        Array.from(files).forEach(file => {
            html += `
                <div class="mb-1">
                    <i class="fas fa-file-csv text-success me-2"></i>
                    ${file.name} (${formatFileSize(file.size)})
                </div>
            `;
        });

        document.getElementById('uploadedFilesList').innerHTML = html;
    }

    async function handleFileUpload() {
        const formData = new FormData(document.getElementById('uploadForm'));
        const uploadBtn = document.getElementById('uploadBtn');

        uploadBtn.disabled = true;
        uploadBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري الرفع...';

        showProgress();
        updateProgress(25, 'رفع الملفات...');

        try {
            const response = await fetch('/admin/migration/upload-files', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                uploadedFiles = data.files;
                updateStep(1, 'completed', 'تم الرفع');
                updateStep(2, 'active', 'جاهز للتحليل');

                updateProgress(50, 'تم رفع الملفات بنجاح');

                document.getElementById('analysisSection').style.display = 'block';
                setTimeout(() => {
                    document.getElementById('analysisSection').scrollIntoView();
                }, 500);
            } else {
                showError('خطأ في الرفع: ' + data.message);
            }
        } catch (error) {
            showError('خطأ في الرفع: ' + error.message);
        }

        uploadBtn.disabled = false;
        uploadBtn.innerHTML = '<i class="fas fa-upload me-2"></i>رفع الملفات المحددة';
    }

    async function analyzeData() {
        if (uploadedFiles.length === 0) {
            alert('يرجى رفع ملفات أولاً');
            return;
        }

        const btn = document.getElementById('analyzeBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري التحليل...';

        updateProgress(60, 'تحليل الفروق والأعمدة...');

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
                updateStep(2, 'completed', 'تم التحليل');
                updateStep(3, 'active', 'جاهز للمعالجة');

                displayAnalysisResults(data.analysis);

                document.getElementById('columnsSection').style.display = 'block';
                setTimeout(() => {
                    document.getElementById('columnsSection').scrollIntoView();
                }, 500);
            } else {
                showError('خطأ في التحليل: ' + data.message);
            }
        } catch (error) {
            showError('خطأ في التحليل: ' + error.message);
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-search me-2"></i>تحليل الفروق والأعمدة المختلفة';
    }

    function displayAnalysisResults(analysis) {
        const html = `
            <div class="alert alert-warning">
                <h6>📊 نتائج التحليل:</h6>
                <div class="row text-center">
                    <div class="col-md-4">
                        <strong>${analysis.missing_columns}</strong><br>
                        <small>عمود مفقود</small>
                    </div>
                    <div class="col-md-4">
                        <strong>${analysis.different_columns}</strong><br>
                        <small>عمود مختلف</small>
                    </div>
                    <div class="col-md-4">
                        <strong>${analysis.total_records}</strong><br>
                        <small>سجل للنقل</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-danger">الأعمدة المفقودة:</h6>
                    <div class="bg-light p-3 rounded">
                        ${analysis.missing_columns_list.map(col =>
                            `<span class="badge bg-danger me-1">${col.table}.${col.column}</span>`
                        ).join('')}
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="text-warning">الأعمدة المختلفة:</h6>
                    <div class="bg-light p-3 rounded">
                        ${analysis.different_columns_list.map(col =>
                            `<div><code>${col.old_column}</code> → <code>${col.new_columns.join(' + ')}</code></div>`
                        ).join('')}
                    </div>
                </div>
            </div>
        `;

        document.getElementById('analysisResults').innerHTML = html;
    }

    async function processColumns() {
        const btn = document.getElementById('processBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري المعالجة...';

        updateProgress(75, 'معالجة الأعمدة المختلفة...');

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
                updateStep(3, 'completed', 'تم المعالجة');
                updateStep(4, 'active', 'جاهز للنقل');

                displayProcessResults(data);

                document.getElementById('migrationSection').style.display = 'block';
                setTimeout(() => {
                    document.getElementById('migrationSection').scrollIntoView();
                }, 500);
            } else {
                showError('خطأ في المعالجة: ' + data.message);
            }
        } catch (error) {
            showError('خطأ في المعالجة: ' + error.message);
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-cogs me-2"></i>تطبيق المعالجة الذكية';
    }

    function displayProcessResults(data) {
        const html = `
            <div class="alert alert-info">
                <h6>✅ تم معالجة الأعمدة:</h6>
                <ul class="mb-0">
                    <li><strong>أعمدة مُضافة:</strong> ${data.added_columns.length}</li>
                    <li><strong>أعمدة مُحولة:</strong> ${data.converted_columns.length}</li>
                    <li><strong>جداول مُحدثة:</strong> ${data.updated_tables.length}</li>
                </ul>
            </div>
        `;

        document.getElementById('processResults').innerHTML = html;
    }

    async function executeMigration() {
        if (!confirm('هل أنت متأكد من بدء النقل النهائي؟ سيتم دمج جميع البيانات.')) {
            return;
        }

        const btn = document.getElementById('migrateBtn');
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
                updateStep(4, 'completed', 'مكتمل');
                updateProgress(100, 'تم إكمال النقل بنجاح!');
                displayFinalResults(data);
            } else {
                showError('خطأ في النقل: ' + data.message);
            }
        } catch (error) {
            showError('خطأ في النقل: ' + error.message);
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-play me-2"></i>بدء النقل النهائي';
    }

    function displayFinalResults(data) {
        const html = `
            <div class="alert alert-success" style="border: 3px solid #28a745;">
                <h4>🎉 تم إكمال النقل الموحد بنجاح!</h4>
                <div class="row text-center mt-3">
                    <div class="col-md-4">
                        <div class="bg-primary text-white p-3 rounded">
                            <h3>${data.results.total_users.toLocaleString()}</h3>
                            <small>مستخدم موحد</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-success text-white p-3 rounded">
                            <h3>${data.results.total_maids.toLocaleString()}</h3>
                            <small>عاملة مدموجة</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-info text-white p-3 rounded">
                            <h3>${data.results.added_columns}</h3>
                            <small>عمود مُضاف</small>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="text-center">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-primary me-2">
                        <i class="fas fa-users me-1"></i>عرض المستخدمين
                    </a>
                    <a href="/admin/migration/verify-results" class="btn btn-outline-success">
                        <i class="fas fa-chart-bar me-1"></i>فحص النتائج
                    </a>
                </div>
            </div>
        `;

        document.getElementById('finalResults').innerHTML = html;
    }

    // وظائف مساعدة
    function updateStep(step, status, text) {
        const statusElement = document.getElementById(`step${step}Status`);
        const cardElement = document.getElementById(`step${step}Card`);

        const classes = {
            'active': 'bg-primary',
            'completed': 'bg-success',
            'processing': 'bg-warning',
            'error': 'bg-danger'
        };

        statusElement.innerHTML = `<span class="badge ${classes[status] || 'bg-secondary'}">${text}</span>`;

        if (status === 'completed') {
            cardElement.classList.add('border-success');
        } else if (status === 'active') {
            cardElement.classList.add('border-primary');
        }
    }

    function showProgress() {
        document.getElementById('progressBar').style.display = 'block';
    }

    function updateProgress(percent, text) {
        document.getElementById('progress').style.width = percent + '%';
        document.getElementById('progressText').textContent = text;
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

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
</script>
@endpush

<style>
    .step-card {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    .step-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .step-card.border-success {
        background: linear-gradient(45deg, #d4edda 0%, #f8f9fa 100%);
    }
    .step-card.border-primary {
        background: linear-gradient(45deg, #e3f2fd 0%, #f8f9fa 100%);
    }
</style>
@endsection
