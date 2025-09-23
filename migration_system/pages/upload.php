<?php
/**
 * صفحة رفع الملفات
 * File Upload Page
 */

$table = $_GET['table'] ?? '';
$supported_tables = [
    'users' => [
        'name' => 'المستخدمون',
        'target' => 'users',
        'required_fields' => ['name', 'email', 'phone'],
        'optional_fields' => ['password', 'address', 'city', 'country']
    ],
    'maids' => [
        'name' => 'العاملات/السير الذاتية',
        'target' => 'cvs',
        'required_fields' => ['passport_id', 'salary', 'national_id'],
        'optional_fields' => ['personal_image', 'age_id', 'social_id', 'skills']
    ],
    'orders' => [
        'name' => 'الطلبات',
        'target' => 'orders',
        'required_fields' => ['order_code', 'user_id', 'biography_id'],
        'optional_fields' => ['status', 'order_date']
    ]
];
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <!-- اختيار نوع الجدول -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-table me-2"></i>
                    اختر نوع البيانات المراد نقلها
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($supported_tables as $key => $table_info): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 <?= $table === $key ? 'border-primary' : '' ?>">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <?php
                                    $icons = [
                                        'users' => 'fas fa-users text-primary',
                                        'maids' => 'fas fa-user-tag text-info',
                                        'orders' => 'fas fa-shopping-cart text-success'
                                    ];
                                    ?>
                                    <i class="<?= $icons[$key] ?? 'fas fa-table' ?> fa-3x"></i>
                                </div>
                                <h6 class="card-title"><?= $table_info['name'] ?></h6>
                                <p class="card-text small text-muted">
                                    نقل إلى: <code><?= $table_info['target'] ?></code>
                                </p>
                                <?php if ($table === $key): ?>
                                    <span class="badge bg-primary">محدد</span>
                                <?php else: ?>
                                    <a href="?page=upload&table=<?= $key ?>" class="btn btn-sm btn-outline-primary">
                                        اختيار
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($table) && isset($supported_tables[$table])): ?>
        <?php $table_info = $supported_tables[$table]; ?>

        <!-- معلومات الجدول المحدد -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    معلومات جدول <?= $table_info['name'] ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-success">الحقول المطلوبة:</h6>
                        <ul class="list-unstyled">
                            <?php foreach ($table_info['required_fields'] as $field): ?>
                            <li class="mb-1">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <code><?= $field ?></code>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-info">الحقول الاختيارية:</h6>
                        <ul class="list-unstyled">
                            <?php foreach ($table_info['optional_fields'] as $field): ?>
                            <li class="mb-1">
                                <i class="fas fa-circle text-info me-2"></i>
                                <code><?= $field ?></code>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- رابط تحميل قالب -->
                <div class="alert alert-info mt-3">
                    <i class="fas fa-download me-2"></i>
                    <strong>نصيحة:</strong>
                    <a href="templates/<?= $table ?>.csv" class="alert-link" download>
                        قم بتحميل قالب CSV
                    </a>
                    للتأكد من التنسيق الصحيح للبيانات.
                </div>
            </div>
        </div>

        <!-- نموذج رفع الملف -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-upload me-2"></i>
                    رفع ملف البيانات
                </h5>
            </div>
            <div class="card-body">
                <form id="uploadForm" enctype="multipart/form-data" method="POST">
                    <input type="hidden" name="source_table" value="<?= htmlspecialchars($table) ?>">
                    <input type="hidden" name="target_table" value="<?= htmlspecialchars($table_info['target']) ?>">

                    <!-- منطقة رفع الملف -->
                    <div id="uploadArea" class="upload-area mb-3">
                        <input type="file" id="csvFile" name="csv_file" accept=".csv,.xlsx" class="d-none" required>
                        <div id="uploadContent">
                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                            <h5>اسحب الملف هنا أو انقر للاختيار</h5>
                            <p class="text-muted">الأنواع المدعومة: CSV, XLSX (حد أقصى: 10 MB)</p>
                            <button type="button" id="selectFile" class="btn btn-primary">
                                <i class="fas fa-folder-open me-2"></i>
                                اختيار ملف
                            </button>
                        </div>

                        <!-- معلومات الملف المحدد -->
                        <div id="fileInfo" class="d-none">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-file-csv fa-2x text-success me-3"></i>
                                    <div>
                                        <h6 class="mb-1" id="fileName"></h6>
                                        <small class="text-muted" id="fileSize"></small>
                                    </div>
                                </div>
                                <button type="button" id="removeFile" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- خيارات إضافية -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">اسم الدفعة:</label>
                            <input type="text" name="batch_name" class="form-control"
                                   placeholder="مثال: نقل مستخدمين - <?= date('d/m/Y') ?>"
                                   value="نقل <?= $table_info['name'] ?> - <?= date('d/m/Y H:i') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">حجم الدفعة:</label>
                            <select name="batch_size" class="form-select">
                                <option value="100">100 سجل</option>
                                <option value="500" selected>500 سجل</option>
                                <option value="1000">1000 سجل</option>
                                <option value="2000">2000 سجل</option>
                            </select>
                        </div>
                    </div>

                    <!-- خيارات المعالجة -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="card-title">خيارات المعالجة:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                               name="validate_before_import" id="validateCheck" checked>
                                        <label class="form-check-label" for="validateCheck">
                                            التحقق من البيانات قبل النقل
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                               name="backup_before_import" id="backupCheck" checked>
                                        <label class="form-check-label" for="backupCheck">
                                            إنشاء نسخة احتياطية قبل النقل
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                               name="skip_duplicates" id="skipDuplicates">
                                        <label class="form-check-label" for="skipDuplicates">
                                            تخطي السجلات المكررة
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                               name="auto_rollback" id="autoRollback" checked>
                                        <label class="form-check-label" for="autoRollback">
                                            التراجع التلقائي عند الخطأ
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- أزرار العمل -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="button" id="previewBtn" class="btn btn-outline-info me-md-2" disabled>
                            <i class="fas fa-eye me-2"></i>
                            معاينة البيانات
                        </button>
                        <button type="submit" id="uploadBtn" class="btn btn-success" disabled>
                            <i class="fas fa-upload me-2"></i>
                            بدء النقل
                        </button>
                    </div>

                    <!-- شريط التقدم -->
                    <div id="progressWrapper" class="progress-wrapper">
                        <div class="d-flex justify-content-between mb-2">
                            <span id="progressText">جاري المعالجة...</span>
                            <span id="progressPercent">0%</span>
                        </div>
                        <div class="progress">
                            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                                 role="progressbar" style="width: 0%"></div>
                        </div>
                        <div id="progressDetails" class="mt-2 small text-muted"></div>
                    </div>
                </form>
            </div>
        </div>

        <?php endif; ?>
    </div>
</div>

<!-- Modal معاينة البيانات -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">معاينة البيانات</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="previewContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">جاري التحميل...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                <button type="button" class="btn btn-success" id="proceedImport">
                    متابعة النقل
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('uploadArea');
    const csvFile = document.getElementById('csvFile');
    const selectFile = document.getElementById('selectFile');
    const uploadContent = document.getElementById('uploadContent');
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const removeFile = document.getElementById('removeFile');
    const previewBtn = document.getElementById('previewBtn');
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadForm = document.getElementById('uploadForm');
    const progressWrapper = document.getElementById('progressWrapper');

    // التعامل مع النقر على منطقة الرفع
    uploadArea.addEventListener('click', function(e) {
        if (e.target !== removeFile) {
            csvFile.click();
        }
    });

    selectFile.addEventListener('click', function() {
        csvFile.click();
    });

    // التعامل مع اختيار الملف
    csvFile.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            showFileInfo(file);
        }
    });

    // السحب والإفلات
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('dragover');

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            csvFile.files = files;
            showFileInfo(files[0]);
        }
    });

    function showFileInfo(file) {
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);

        uploadContent.classList.add('d-none');
        fileInfo.classList.remove('d-none');

        previewBtn.disabled = false;
        uploadBtn.disabled = false;
    }

    // إزالة الملف
    removeFile.addEventListener('click', function(e) {
        e.stopPropagation();
        csvFile.value = '';
        uploadContent.classList.remove('d-none');
        fileInfo.classList.add('d-none');
        previewBtn.disabled = true;
        uploadBtn.disabled = true;
    });

    // معاينة البيانات
    previewBtn.addEventListener('click', function() {
        const formData = new FormData();
        formData.append('csv_file', csvFile.files[0]);
        formData.append('action', 'preview');
        formData.append('source_table', document.querySelector('input[name="source_table"]').value);

        fetch('api/upload_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showPreview(data.data);
            } else {
                alert('خطأ: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ في معاينة البيانات');
        });
    });

    // إرسال النموذج
    uploadForm.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!csvFile.files[0]) {
            alert('يرجى اختيار ملف أولاً');
            return;
        }

        const formData = new FormData(uploadForm);
        formData.append('action', 'upload');

        progressWrapper.style.display = 'block';
        uploadBtn.disabled = true;

        fetch('api/upload_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showProgress(data.batch_id);
            } else {
                alert('خطأ: ' + data.message);
                uploadBtn.disabled = false;
                progressWrapper.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ في رفع الملف');
            uploadBtn.disabled = false;
            progressWrapper.style.display = 'none';
        });
    });

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function showPreview(data) {
        let html = '<div class="table-responsive">';
        html += '<table class="table table-striped table-sm">';

        // عرض العناوين
        if (data.headers && data.headers.length > 0) {
            html += '<thead class="table-dark"><tr>';
            data.headers.forEach(header => {
                html += '<th>' + escapeHtml(header) + '</th>';
            });
            html += '</tr></thead>';
        }

        // عرض البيانات (أول 10 صفوف)
        if (data.preview && data.preview.length > 0) {
            html += '<tbody>';
            data.preview.slice(0, 10).forEach(row => {
                html += '<tr>';
                Object.values(row).forEach(cell => {
                    html += '<td>' + escapeHtml(cell || '') + '</td>';
                });
                html += '</tr>';
            });
            html += '</tbody>';
        }

        html += '</table></div>';

        if (data.total_rows > 10) {
            html += '<div class="alert alert-info mt-3">';
            html += '<i class="fas fa-info-circle me-2"></i>';
            html += 'يعرض أول 10 صفوف من إجمالي ' + data.total_rows + ' سجل';
            html += '</div>';
        }

        document.getElementById('previewContent').innerHTML = html;
        new bootstrap.Modal(document.getElementById('previewModal')).show();
    }

    function showProgress(batchId) {
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');
        const progressPercent = document.getElementById('progressPercent');
        const progressDetails = document.getElementById('progressDetails');

        const checkProgress = () => {
            fetch(`api/get_batch_status.php?id=${batchId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const batch = data.batch;
                        const progress = batch.total_records > 0
                            ? Math.round((batch.processed_records / batch.total_records) * 100)
                            : 0;

                        progressBar.style.width = progress + '%';
                        progressPercent.textContent = progress + '%';
                        progressDetails.innerHTML =
                            `معالج: ${batch.processed_records} | ` +
                            `ناجح: ${batch.successful_records} | ` +
                            `فاشل: ${batch.failed_records}`;

                        if (batch.status === 'completed') {
                            progressText.textContent = 'تم النقل بنجاح!';
                            progressBar.classList.remove('progress-bar-animated');
                            progressBar.classList.add('bg-success');

                            setTimeout(() => {
                                window.location.href = '?page=batches&highlight=' + batchId;
                            }, 2000);
                        } else if (batch.status === 'failed') {
                            progressText.textContent = 'فشل في النقل';
                            progressBar.classList.remove('progress-bar-animated');
                            progressBar.classList.add('bg-danger');
                            uploadBtn.disabled = false;
                        } else {
                            setTimeout(checkProgress, 2000);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error checking progress:', error);
                    setTimeout(checkProgress, 5000);
                });
        };

        setTimeout(checkProgress, 1000);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
