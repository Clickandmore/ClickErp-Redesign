<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار رفع الملفات المعقدة - Complex File Upload Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .upload-area {
            border: 2px dashed #007bff;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: linear-gradient(45deg, #f8f9fa 0%, #e9ecef 100%);
        }
        .upload-area:hover {
            border-color: #0056b3;
            background: linear-gradient(45deg, #e3f2fd 0%, #bbdefb 100%);
            transform: translateY(-2px);
        }
        .upload-area.dragover {
            border-color: #28a745;
            background: linear-gradient(45deg, #d4edda 0%, #c3e6cb 100%);
        }
        .file-info {
            background: linear-gradient(45deg, #d4edda 0%, #c3e6cb 100%);
            border: 2px solid #28a745;
            border-radius: 10px;
            padding: 20px;
        }
        .error-box {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }
        .success-box {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }
        .info-box {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }
        .stats-card {
            background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin: 10px 0;
        }
        .debug-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            font-family: monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
        }
        .progress-container {
            display: none;
            margin: 20px 0;
        }
        .btn-group-custom .btn {
            margin: 5px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="text-center mb-4">
                    <h2 class="text-primary">
                        <i class="fas fa-database me-2"></i>
                        اختبار رفع الملفات المعقدة
                    </h2>
                    <p class="text-muted">مصمم خصيصاً للتعامل مع ملفات CSV الكبيرة والمعقدة</p>
                </div>

                <!-- معلومات النظام -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-server me-2"></i>معلومات النظام</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <h6>حد الذاكرة</h6>
                                    <h4 id="memoryLimit">-</h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <h6>حد رفع الملفات</h6>
                                    <h4 id="uploadLimit">-</h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <h6>وقت التنفيذ</h6>
                                    <h4 id="execTime">-</h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <h6>استخدام الذاكرة</h6>
                                    <h4 id="memoryUsage">-</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- نموذج الرفع -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-upload me-2"></i>رفع ملف CSV</h5>
                    </div>
                    <div class="card-body">
                        <form id="uploadForm" enctype="multipart/form-data">
                            <!-- منطقة رفع الملف -->
                            <div id="uploadArea" class="upload-area mb-3">
                                <input type="file" id="csvFile" name="csv_file" accept=".csv" style="display: none;" required>
                                <div id="uploadContent">
                                    <i class="fas fa-cloud-upload-alt fa-4x text-primary mb-3"></i>
                                    <h4>اسحب ملف CSV هنا أو انقر للاختيار</h4>
                                    <p class="text-muted">
                                        <strong>الحد الأقصى:</strong> 100 MB<br>
                                        <strong>الأنواع المدعومة:</strong> CSV
                                    </p>
                                    <button type="button" id="selectFileBtn" class="btn btn-primary btn-lg">
                                        <i class="fas fa-folder-open me-2"></i>
                                        اختيار ملف
                                    </button>
                                </div>

                                <!-- معلومات الملف المحدد -->
                                <div id="fileInfo" class="file-info d-none">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-file-csv fa-3x text-success me-3"></i>
                                                <div>
                                                    <h5 class="mb-1" id="fileName"></h5>
                                                    <div class="text-muted">
                                                        <i class="fas fa-weight-hanging me-1"></i>
                                                        <span id="fileSize"></span>
                                                        <span class="mx-2">|</span>
                                                        <i class="fas fa-file-alt me-1"></i>
                                                        <span id="fileType"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <button type="button" id="removeFile" class="btn btn-outline-danger">
                                                <i class="fas fa-times me-1"></i>
                                                إزالة
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- أزرار العمل -->
                            <div class="btn-group-custom text-center">
                                <button type="button" id="previewBtn" class="btn btn-info btn-lg" disabled>
                                    <i class="fas fa-eye me-2"></i>
                                    معاينة البيانات
                                </button>
                                <button type="button" id="uploadBtn" class="btn btn-success btn-lg" disabled>
                                    <i class="fas fa-upload me-2"></i>
                                    رفع الملف
                                </button>
                                <button type="button" id="analyzeBtn" class="btn btn-warning btn-lg" disabled>
                                    <i class="fas fa-chart-bar me-2"></i>
                                    تحليل البيانات
                                </button>
                            </div>

                            <!-- شريط التقدم -->
                            <div id="progressContainer" class="progress-container">
                                <div class="d-flex justify-content-between mb-2">
                                    <span id="progressText">جاري المعالجة...</span>
                                    <span id="progressPercent">0%</span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                                         role="progressbar" style="width: 0%"></div>
                                </div>
                                <div id="progressDetails" class="mt-2 small text-muted"></div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- منطقة النتائج -->
                <div id="resultsArea"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadSystemInfo();
            initializeUpload();
        });

        function loadSystemInfo() {
            // معلومات افتراضية - يمكن تحديثها من API
            document.getElementById('memoryLimit').textContent = '512M';
            document.getElementById('uploadLimit').textContent = '100M';
            document.getElementById('execTime').textContent = '600s';
            document.getElementById('memoryUsage').textContent = 'تحديث...';

            // تحديث استخدام الذاكرة
            setInterval(updateMemoryUsage, 5000);
        }

        function updateMemoryUsage() {
            fetch('api/system_info.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('memoryUsage').textContent = data.memory_usage;
                    }
                })
                .catch(error => console.log('Memory update failed:', error));
        }

        function initializeUpload() {
            const uploadArea = document.getElementById('uploadArea');
            const csvFile = document.getElementById('csvFile');
            const selectFileBtn = document.getElementById('selectFileBtn');
            const uploadContent = document.getElementById('uploadContent');
            const fileInfo = document.getElementById('fileInfo');
            const fileName = document.getElementById('fileName');
            const fileSize = document.getElementById('fileSize');
            const fileType = document.getElementById('fileType');
            const removeFile = document.getElementById('removeFile');
            const previewBtn = document.getElementById('previewBtn');
            const uploadBtn = document.getElementById('uploadBtn');
            const analyzeBtn = document.getElementById('analyzeBtn');
            const resultsArea = document.getElementById('resultsArea');

            // Event listeners
            uploadArea.addEventListener('click', (e) => {
                if (e.target !== removeFile) csvFile.click();
            });

            selectFileBtn.addEventListener('click', () => csvFile.click());

            csvFile.addEventListener('change', handleFileSelect);
            removeFile.addEventListener('click', handleFileRemove);

            previewBtn.addEventListener('click', () => handleUpload('preview'));
            uploadBtn.addEventListener('click', () => handleUpload('upload'));
            analyzeBtn.addEventListener('click', () => handleUpload('analyze'));

            // Drag & Drop
            uploadArea.addEventListener('dragover', handleDragOver);
            uploadArea.addEventListener('dragleave', handleDragLeave);
            uploadArea.addEventListener('drop', handleDrop);

            function handleFileSelect(e) {
                const file = e.target.files[0];
                if (file) {
                    displayFileInfo(file);
                }
            }

            function handleFileRemove(e) {
                e.stopPropagation();
                csvFile.value = '';
                uploadContent.classList.remove('d-none');
                fileInfo.classList.add('d-none');
                previewBtn.disabled = true;
                uploadBtn.disabled = true;
                analyzeBtn.disabled = true;
                resultsArea.innerHTML = '';
            }

            function handleDragOver(e) {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            }

            function handleDragLeave(e) {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
            }

            function handleDrop(e) {
                e.preventDefault();
                uploadArea.classList.remove('dragover');

                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    csvFile.files = files;
                    displayFileInfo(files[0]);
                }
            }

            function displayFileInfo(file) {
                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
                fileType.textContent = file.type || 'غير محدد';

                uploadContent.classList.add('d-none');
                fileInfo.classList.remove('d-none');

                previewBtn.disabled = false;
                uploadBtn.disabled = false;
                analyzeBtn.disabled = false;
            }

            function handleUpload(action) {
                const file = csvFile.files[0];
                if (!file) {
                    showMessage('يرجى اختيار ملف أولاً', 'error');
                    return;
                }

                const formData = new FormData();
                formData.append('csv_file', file);
                formData.append('action', action);

                showProgress(true);
                showMessage(`جاري ${getActionName(action)} الملف...`, 'info');

                fetch('api/enhanced_upload.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        showProgress(false);

                        if (data.success) {
                            if (action === 'preview') {
                                showPreviewResults(data.data);
                            } else {
                                showUploadResults(data);
                            }
                        } else {
                            showErrorResults(data);
                        }
                    } catch (parseError) {
                        showProgress(false);
                        showRawResponse(text, parseError);
                    }
                })
                .catch(error => {
                    showProgress(false);
                    showMessage('خطأ في الاتصال: ' + error.message, 'error');
                    console.error('Upload error:', error);
                });
            }

            function getActionName(action) {
                const names = {
                    'preview': 'معاينة',
                    'upload': 'رفع',
                    'analyze': 'تحليل'
                };
                return names[action] || action;
            }

            function showPreviewResults(data) {
                let html = '<div class="card mt-4"><div class="card-header bg-success text-white">';
                html += '<h5><i class="fas fa-eye me-2"></i>معاينة البيانات</h5>';
                html += '</div><div class="card-body">';

                // إحصائيات الملف
                html += '<div class="row mb-3">';
                html += '<div class="col-md-3"><div class="stats-card text-center">';
                html += '<h6>إجمالي الصفوف</h6><h3>' + data.total_rows + '</h3></div></div>';
                html += '<div class="col-md-3"><div class="stats-card text-center">';
                html += '<h6>الصفوف الصالحة</h6><h3>' + data.valid_rows + '</h3></div></div>';
                html += '<div class="col-md-3"><div class="stats-card text-center">';
                html += '<h6>عدد الحقول</h6><h3>' + data.parsing_info.total_fields + '</h3></div></div>';
                html += '<div class="col-md-3"><div class="stats-card text-center">';
                html += '<h6>حجم الملف</h6><h3>' + formatFileSize(data.file_info.size) + '</h3></div></div>';
                html += '</div>';

                // العناوين
                html += '<h6 class="text-primary">العناوين المكتشفة (' + data.headers.length + '):</h6>';
                html += '<div class="mb-3 p-3 bg-light border rounded">';
                data.headers.forEach((header, index) => {
                    html += '<span class="badge bg-primary me-1 mb-1">' + (index + 1) + '. ' + escapeHtml(header) + '</span>';
                });
                html += '</div>';

                // عينة البيانات
                if (data.preview && data.preview.length > 0) {
                    html += '<h6 class="text-primary">عينة البيانات (أول ' + data.preview.length + ' صفوف):</h6>';
                    html += '<div class="table-responsive">';
                    html += '<table class="table table-striped table-sm">';

                    // عرض العناوين
                    html += '<thead class="table-dark"><tr>';
                    data.headers.slice(0, 8).forEach(header => { // عرض أول 8 أعمدة فقط
                        html += '<th style="font-size: 11px;">' + escapeHtml(header) + '</th>';
                    });
                    if (data.headers.length > 8) {
                        html += '<th style="font-size: 11px;">... +' + (data.headers.length - 8) + ' حقل</th>';
                    }
                    html += '</tr></thead>';

                    // عرض البيانات
                    html += '<tbody>';
                    data.preview.forEach(row => {
                        html += '<tr>';
                        let cellCount = 0;
                        for (let key in row) {
                            if (cellCount < 8) {
                                let cellValue = row[key] || '';
                                if (cellValue.length > 30) {
                                    cellValue = cellValue.substring(0, 30) + '...';
                                }
                                html += '<td style="font-size: 10px;">' + escapeHtml(cellValue) + '</td>';
                                cellCount++;
                            }
                        }
                        if (Object.keys(row).length > 8) {
                            html += '<td class="text-muted">...</td>';
                        }
                        html += '</tr>';
                    });
                    html += '</tbody>';
                    html += '</table></div>';
                }

                // معلومات التحليل
                if (data.parsing_info) {
                    html += '<div class="info-box">';
                    html += '<h6><i class="fas fa-info-circle me-2"></i>معلومات التحليل:</h6>';
                    html += '<ul class="mb-0">';
                    html += '<li><strong>الترميز:</strong> ' + data.parsing_info.encoding + '</li>';
                    html += '<li><strong>المحدد:</strong> "' + data.parsing_info.delimiter + '"</li>';
                    html += '<li><strong>عدد الحقول:</strong> ' + data.parsing_info.total_fields + '</li>';
                    html += '<li><strong>وجود أخطاء:</strong> ' + (data.parsing_info.has_errors ? 'نعم (' + data.parsing_info.errors_count + ')' : 'لا') + '</li>';
                    html += '</ul></div>';
                }

                html += '</div></div>';
                resultsArea.innerHTML = html;
            }

            function showUploadResults(data) {
                let html = '<div class="card mt-4"><div class="card-header bg-success text-white">';
                html += '<h5><i class="fas fa-check-circle me-2"></i>نتائج الرفع</h5>';
                html += '</div><div class="card-body">';

                html += '<div class="success-box">';
                html += '<h6><i class="fas fa-check-circle me-2"></i>' + data.message + '</h6>';
                html += '<p><strong>مسار الملف:</strong> <code>' + data.file_info.path + '</code></p>';
                html += '<p><strong>الصفوف المعالجة:</strong> ' + data.file_info.processed_rows + ' من أصل ' + data.file_info.total_rows + '</p>';
                html += '</div>';

                // عينة من البيانات المحفوظة
                if (data.file_info.sample_data && data.file_info.sample_data.length > 0) {
                    html += '<h6 class="text-success mt-3">عينة من البيانات المحفوظة:</h6>';
                    html += '<div class="table-responsive">';
                    html += '<table class="table table-striped table-sm">';
                    html += '<thead class="table-success"><tr>';

                    const firstRow = data.file_info.sample_data[0];
                    Object.keys(firstRow).slice(0, 6).forEach(key => {
                        html += '<th>' + escapeHtml(key) + '</th>';
                    });
                    html += '</tr></thead><tbody>';

                    data.file_info.sample_data.forEach(row => {
                        html += '<tr>';
                        Object.values(row).slice(0, 6).forEach(cell => {
                            let cellValue = (cell || '').toString();
                            if (cellValue.length > 25) {
                                cellValue = cellValue.substring(0, 25) + '...';
                            }
                            html += '<td>' + escapeHtml(cellValue) + '</td>';
                        });
                        html += '</tr>';
                    });
                    html += '</tbody></table></div>';
                }

                html += '</div></div>';
                resultsArea.innerHTML = html;
            }

            function showErrorResults(data) {
                let html = '<div class="card mt-4"><div class="card-header bg-danger text-white">';
                html += '<h5><i class="fas fa-exclamation-circle me-2"></i>حدث خطأ في الرفع</h5>';
                html += '</div><div class="card-body">';

                html += '<div class="error-box">';
                html += '<h6><i class="fas fa-times-circle me-2"></i>رسالة الخطأ:</h6>';
                html += '<p class="mb-0">' + escapeHtml(data.message) + '</p>';
                html += '</div>';

                if (data.debug_info) {
                    html += '<h6 class="text-warning mt-3">معلومات التشخيص:</h6>';
                    html += '<div class="debug-info">';
                    html += '<pre>' + JSON.stringify(data.debug_info, null, 2) + '</pre>';
                    html += '</div>';
                }

                html += '</div></div>';
                resultsArea.innerHTML = html;
            }

            function showRawResponse(text, error) {
                let html = '<div class="card mt-4"><div class="card-header bg-warning text-dark">';
                html += '<h5><i class="fas fa-exclamation-triangle me-2"></i>استجابة غير صالحة من الخادم</h5>';
                html += '</div><div class="card-body">';

                html += '<div class="error-box">';
                html += '<h6>خطأ في تحليل الاستجابة:</h6>';
                html += '<p>' + error.message + '</p>';
                html += '</div>';

                html += '<h6 class="text-warning">الاستجابة الخام:</h6>';
                html += '<div class="debug-info">';
                html += '<pre>' + escapeHtml(text) + '</pre>';
                html += '</div>';

                html += '</div></div>';
                resultsArea.innerHTML = html;
            }

            function showProgress(show) {
                const container = document.getElementById('progressContainer');
                const progressBar = document.getElementById('progressBar');
                const progressText = document.getElementById('progressText');
                const progressPercent = document.getElementById('progressPercent');

                if (show) {
                    container.style.display = 'block';
                    progressBar.style.width = '0%';
                    progressPercent.textContent = '0%';

                    // محاكاة تقدم
                    let progress = 0;
                    const interval = setInterval(() => {
                        progress += Math.random() * 10;
                        if (progress > 90) progress = 90;

                        progressBar.style.width = progress + '%';
                        progressPercent.textContent = Math.round(progress) + '%';

                        if (progress >= 90) {
                            clearInterval(interval);
                        }
                    }, 100);

                    setTimeout(() => {
                        clearInterval(interval);
                        progressBar.style.width = '100%';
                        progressPercent.textContent = '100%';
                    }, 2000);
                } else {
                    container.style.display = 'none';
                }
            }

            function showMessage(message, type) {
                const alertClass = {
                    'success': 'alert-success',
                    'error': 'alert-danger',
                    'info': 'alert-info',
                    'warning': 'alert-warning'
                };

                let html = '<div class="alert ' + alertClass[type] + ' alert-dismissible fade show">';
                html += message;
                html += '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                html += '</div>';

                resultsArea.innerHTML = html + resultsArea.innerHTML;
            }

            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        }
    </script>
</body>
</html>
