<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نقل محسن مع جميع الأعمدة وترتيب الجدول</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .hero {
            background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        .upload-area {
            border: 4px dashed #6f42c1;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            background: #f8f7ff;
            transition: all 0.3s;
        }
        .upload-area:hover {
            background: #f0ebff;
            border-color: #5a2d91;
        }
        .column-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 5px 10px;
            margin: 2px;
            display: inline-block;
            font-size: 12px;
        }
        .missing-column {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        .existing-column {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <!-- Hero -->
                <div class="hero text-center">
                    <h1><i class="fas fa-magic me-3"></i>النقل المحسن مع جميع الأعمدة</h1>
                    <p class="lead">نقل جميع الأعمدة من الملف + ترتيب الجدول بشكل منطقي</p>
                    <div class="mt-3">
                        <span class="badge bg-light text-dark fs-6">📊 جميع الأعمدة</span>
                        <span class="badge bg-light text-dark fs-6">📐 ترتيب منطقي</span>
                        <span class="badge bg-light text-dark fs-6">✅ مطابقة كاملة</span>
                    </div>
                </div>

                <!-- تحسينات جديدة -->
                <div class="alert alert-info">
                    <h5><i class="fas fa-star me-2"></i>التحسينات الجديدة:</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary">✅ إضافة جميع الأعمدة:</h6>
                            <ul>
                                <li>user_type, phone_verified_at</li>
                                <li>verification_code, login_code</li>
                                <li>provider_id, referred_by</li>
                                <li>أي عمود آخر في الملف المرفوع</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-success">📐 ترتيب الجدول:</h6>
                            <ul>
                                <li>المعرفات أولاً (id, name, email, phone)</li>
                                <li>البيانات الشخصية (address, city, nationality)</li>
                                <li>الإعدادات (user_type, status, is_blocked)</li>
                                <li>التواريخ في النهاية (created_at, updated_at)</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- رفع الملفات -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-upload me-2"></i>رفع الملفات للتحليل الشامل</h5>
                    </div>
                    <div class="card-body">
                        <div class="upload-area" id="uploadArea">
                            <input type="file" id="fileInput" accept=".csv" multiple style="display: none;">
                            <i class="fas fa-file-upload fa-4x text-primary mb-3"></i>
                            <h3>ارفع ملفات CSV هنا</h3>
                            <p class="text-muted">سيتم تحليل جميع الأعمدة وإضافة المفقود منها</p>
                            <button type="button" class="btn btn-primary btn-lg">
                                <i class="fas fa-folder-open me-2"></i>اختيار ملفات CSV
                            </button>
                        </div>

                        <div id="filesAnalysis" style="display: none;" class="mt-4">
                            <h6 class="text-success">تحليل الملفات:</h6>
                            <div id="filesList"></div>

                            <div class="text-center mt-3">
                                <button type="button" id="analyzeBtn" class="btn btn-warning btn-lg">
                                    <i class="fas fa-search me-2"></i>تحليل جميع الأعمدة
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- تحليل الأعمدة -->
                <div id="columnsAnalysis" style="display: none;" class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5><i class="fas fa-columns me-2"></i>تحليل وترتيب الأعمدة</h5>
                    </div>
                    <div class="card-body">
                        <div id="columnsResults"></div>

                        <div class="text-center mt-3">
                            <button type="button" id="organizeBtn" class="btn btn-info btn-lg">
                                <i class="fas fa-sort me-2"></i>ترتيب الجدول وإضافة الأعمدة
                            </button>
                        </div>
                    </div>
                </div>

                <!-- النقل المحسن -->
                <div id="migrationSection" style="display: none;" class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5><i class="fas fa-rocket me-2"></i>النقل المحسن مع جميع الأعمدة</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success">
                            <h6><i class="fas fa-check-circle me-2"></i>سيتم نقل:</h6>
                            <ul class="mb-0" id="migrationPlan">
                                <!-- سيتم ملؤها ديناميكياً -->
                            </ul>
                        </div>

                        <div class="text-center">
                            <button type="button" id="executeBtn" class="btn btn-success btn-lg">
                                <i class="fas fa-play me-2"></i>تنفيذ النقل مع جميع الأعمدة
                            </button>
                        </div>
                    </div>
                </div>

                <!-- شريط التقدم -->
                <div id="progressSection" style="display: none;" class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h6><i class="fas fa-tasks me-2"></i>تقدم المعالجة المحسنة</h6>
                    </div>
                    <div class="card-body">
                        <div class="progress mb-3" style="height: 20px;">
                            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                                 style="width: 0%"></div>
                        </div>
                        <div id="progressText" class="text-center h6"></div>
                        <div id="progressSteps" class="small text-muted"></div>
                    </div>
                </div>

                <!-- النتائج المحسنة -->
                <div id="resultsArea"></div>
            </div>
        </div>
    </div>

    <script>
        let analyzedFiles = [];
        let columnsAnalysis = null;

        document.addEventListener('DOMContentLoaded', function() {
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('fileInput');

            uploadArea.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', handleFileSelection);

            document.getElementById('analyzeBtn').addEventListener('click', analyzeColumns);
            document.getElementById('organizeBtn').addEventListener('click', organizeTable);
            document.getElementById('executeBtn').addEventListener('click', executeEnhancedMigration);

            setupDragAndDrop();
        });

        function setupDragAndDrop() {
            const uploadArea = document.getElementById('uploadArea');

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            uploadArea.addEventListener('dragover', function() {
                this.style.borderColor = '#5a2d91';
                this.style.background = '#f0ebff';
            });

            uploadArea.addEventListener('dragleave', function() {
                this.style.borderColor = '#6f42c1';
                this.style.background = '#f8f7ff';
            });

            uploadArea.addEventListener('drop', function(e) {
                this.style.borderColor = '#6f42c1';
                this.style.background = '#f8f7ff';

                document.getElementById('fileInput').files = e.dataTransfer.files;
                handleFileSelection();
            });
        }

        async function handleFileSelection() {
            const files = Array.from(document.getElementById('fileInput').files);

            if (files.length === 0) return;

            // رفع وتحليل الملفات
            const formData = new FormData();
            files.forEach((file, index) => {
                formData.append(`file_${index}`, file);
            });
            formData.append('files_count', files.length);
            formData.append('action', 'analyze_files');

            try {
                const response = await fetch('enhanced_processor.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    analyzedFiles = data.files;
                    displayFilesAnalysis(data.files);
                    document.getElementById('filesAnalysis').style.display = 'block';
                } else {
                    showError('خطأ في تحليل الملفات: ' + data.message);
                }
            } catch (error) {
                showError('خطأ في تحليل الملفات: ' + error.message);
            }
        }

        function displayFilesAnalysis(files) {
            let html = '';
            let totalRecords = 0;

            files.forEach(file => {
                totalRecords += file.records_count;

                html += `
                    <div class="card border-primary mb-3">
                        <div class="card-header bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-file-csv text-primary me-2"></i>
                                    <strong>${file.original_name}</strong>
                                </div>
                                <div>
                                    <span class="badge bg-primary">${file.records_count.toLocaleString()} سجل</span>
                                    <span class="badge bg-info">${file.size}</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <h6 class="text-success">الأعمدة الموجودة في الملف (${file.headers.length} عمود):</h6>
                            <div class="mb-2">
                                ${file.headers.map(header => `<span class="column-item">${header}</span>`).join(' ')}
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted"><strong>نوع الجدول:</strong> ${file.table_type}</small>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted"><strong>الترميز:</strong> ${file.encoding || 'UTF-8'}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += `
                <div class="alert alert-primary">
                    <h6>📊 إجمالي الملفات: ${files.length} | إجمالي السجلات: ${totalRecords.toLocaleString()}</h6>
                    <p class="mb-0">سيتم تحليل جميع الأعمدة وإضافة المفقود منها لضمان النقل الكامل</p>
                </div>
            `;

            document.getElementById('filesList').innerHTML = html;
        }

        async function analyzeColumns() {
            if (analyzedFiles.length === 0) {
                alert('يرجى رفع ملفات أولاً');
                return;
            }

            const btn = document.getElementById('analyzeBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري تحليل الأعمدة...';

            try {
                const response = await fetch('enhanced_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'analyze_columns',
                        files: analyzedFiles
                    })
                });

                const data = await response.json();

                if (data.success) {
                    columnsAnalysis = data;
                    displayColumnsAnalysis(data);

                    document.getElementById('columnsAnalysis').style.display = 'block';
                    setTimeout(() => {
                        document.getElementById('columnsAnalysis').scrollIntoView();
                    }, 500);
                } else {
                    showError('خطأ في تحليل الأعمدة: ' + data.message);
                }
            } catch (error) {
                showError('خطأ في تحليل الأعمدة: ' + error.message);
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-search me-2"></i>تحليل جميع الأعمدة';
        }

        function displayColumnsAnalysis(analysis) {
            let html = `
                <div class="alert alert-warning">
                    <h6>📊 نتائج تحليل الأعمدة:</h6>
                    <div class="row text-center">
                        <div class="col-md-3">
                            <strong>${analysis.total_columns_in_files}</strong><br>
                            <small>عمود في الملفات</small>
                        </div>
                        <div class="col-md-3">
                            <strong>${analysis.existing_columns_in_db}</strong><br>
                            <small>عمود موجود في الجدول</small>
                        </div>
                        <div class="col-md-3">
                            <strong>${analysis.missing_columns.length}</strong><br>
                            <small>عمود مفقود</small>
                        </div>
                        <div class="col-md-3">
                            <strong>${analysis.reorganize_needed ? 'نعم' : 'لا'}</strong><br>
                            <small>يحتاج إعادة ترتيب</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-success">✅ الأعمدة الموجودة:</h6>
                        <div style="max-height: 200px; overflow-y: auto; border: 1px solid #dee2e6; padding: 10px; border-radius: 5px;">
                            ${analysis.existing_columns.map(col => `<span class="column-item existing-column">${col}</span>`).join(' ')}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-warning">➕ الأعمدة المفقودة (ستُضاف):</h6>
                        <div style="max-height: 200px; overflow-y: auto; border: 1px solid #dee2e6; padding: 10px; border-radius: 5px;">
                            ${analysis.missing_columns.map(col =>
                                `<span class="column-item missing-column">${col.name} (${col.type})</span>`
                            ).join(' ')}
                        </div>
                    </div>
                </div>

                <div class="mt-3 alert alert-info">
                    <h6>📐 ترتيب الجدول الجديد:</h6>
                    <div class="row">
                        <div class="col-md-3">
                            <strong>المعرفات:</strong><br>
                            <small>id, name, first_name, last_name</small>
                        </div>
                        <div class="col-md-3">
                            <strong>الاتصال:</strong><br>
                            <small>phone, email, phone2</small>
                        </div>
                        <div class="col-md-3">
                            <strong>البيانات الشخصية:</strong><br>
                            <small>address, city, nationality_id</small>
                        </div>
                        <div class="col-md-3">
                            <strong>التواريخ:</strong><br>
                            <small>created_at, updated_at</small>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('columnsResults').innerHTML = html;
        }

        async function organizeTable() {
            const btn = document.getElementById('organizeBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري التنظيم...';

            showProgress();
            updateProgress(30, 'إضافة الأعمدة المفقودة...');

            try {
                const response = await fetch('enhanced_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'organize_table',
                        analysis: columnsAnalysis
                    })
                });

                const data = await response.json();

                if (data.success) {
                    updateProgress(60, 'تم تنظيم الجدول وإضافة الأعمدة');

                    document.getElementById('migrationSection').style.display = 'block';
                    document.getElementById('migrationPlan').innerHTML =
                        data.operations.map(op => `<li>${op}</li>`).join('');

                    setTimeout(() => {
                        document.getElementById('migrationSection').scrollIntoView();
                    }, 500);
                } else {
                    showError('خطأ في تنظيم الجدول: ' + data.message);
                }
            } catch (error) {
                showError('خطأ في تنظيم الجدول: ' + error.message);
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sort me-2"></i>ترتيب الجدول وإضافة الأعمدة';
        }

        async function executeEnhancedMigration() {
            if (!confirm('هل تريد تنفيذ النقل المحسن؟\n\nسيتم:\n• نقل جميع الأعمدة من الملفات\n• ترتيب الجدول منطقياً\n• معالجة البيانات بذكاء\n\nالمتابعة؟')) {
                return;
            }

            const btn = document.getElementById('executeBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري النقل...';

            updateProgress(80, 'تنفيذ النقل المحسن...');

            try {
                const response = await fetch('enhanced_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'execute_enhanced_migration',
                        files: analyzedFiles,
                        analysis: columnsAnalysis
                    })
                });

                const data = await response.json();

                if (data.success) {
                    updateProgress(100, 'تم إكمال النقل المحسن بنجاح!');
                    showEnhancedResults(data.results);
                } else {
                    showError('خطأ في النقل: ' + data.message);
                }
            } catch (error) {
                showError('خطأ في النقل: ' + error.message);
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-play me-2"></i>تنفيذ النقل مع جميع الأعمدة';
        }

        function showEnhancedResults(results) {
            const html = `
                <div class="card" style="background: linear-gradient(45deg, #e8f5e9 0%, #f0f8f0 100%); border: 3px solid #28a745;">
                    <div class="card-header bg-success text-white">
                        <h4><i class="fas fa-trophy me-2"></i>تم النقل المحسن بنجاح!</h4>
                    </div>
                    <div class="card-body">
                        <div class="row text-center mb-4">
                            <div class="col-md-3">
                                <div class="bg-primary text-white p-3 rounded">
                                    <h3>${results.total_records.toLocaleString()}</h3>
                                    <small>سجل منقول</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="bg-success text-white p-3 rounded">
                                    <h3>${results.total_columns}</h3>
                                    <small>عمود مُضاف</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="bg-info text-white p-3 rounded">
                                    <h3>${results.files_processed}</h3>
                                    <small>ملف معالج</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="bg-warning text-white p-3 rounded">
                                    <h3>✓</h3>
                                    <small>جدول مُرتب</small>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-success">
                            <h5>🎉 تم بنجاح:</h5>
                            <ul>
                                ${results.file_details.map(detail =>
                                    `<li>✅ ${detail.file_name}: ${detail.records.toLocaleString()} سجل مع ${detail.columns.toLocaleString()} عمود</li>`
                                ).join('')}
                                <li>✅ إضافة جميع الأعمدة المفقودة: user_type, phone_verified_at, verification_code</li>
                                <li>✅ ترتيب الجدول: المعرفات → البيانات → التواريخ</li>
                                <li>✅ معالجة ذكية: name → first_name + last_name</li>
                            </ul>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary">📊 تفاصيل النقل:</h6>
                                <ul class="small">
                                    ${results.column_details.map(detail => `<li>${detail}</li>`).join('')}
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-success">🔗 التحقق:</h6>
                                <div class="d-grid gap-2">
                                    <a href="verify_enhanced_results.php" target="_blank" class="btn btn-outline-primary">
                                        <i class="fas fa-search me-1"></i>فحص الجدول المُرتب
                                    </a>
                                    <button type="button" onclick="testTableStructure()" class="btn btn-outline-success">
                                        <i class="fas fa-table me-1"></i>عرض بنية الجدول الجديدة
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('resultsArea').innerHTML = html;
        }

        async function testTableStructure() {
            try {
                const response = await fetch('enhanced_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'show_table_structure'})
                });

                const data = await response.json();

                if (data.success) {
                    let message = '📐 بنية الجدول الجديدة المُرتبة:\n\n';

                    data.columns.forEach((col, index) => {
                        message += `${index + 1}. ${col.Field} (${col.Type})\n`;
                    });

                    alert(message);
                } else {
                    alert('خطأ في عرض البنية: ' + data.message);
                }
            } catch (error) {
                alert('خطأ في عرض البنية: ' + error.message);
            }
        }

        function showProgress() {
            document.getElementById('progressSection').style.display = 'block';
            document.getElementById('progressSection').scrollIntoView();
        }

        function updateProgress(percent, text, steps = '') {
            document.getElementById('progressBar').style.width = percent + '%';
            document.getElementById('progressText').textContent = text;
            document.getElementById('progressSteps').textContent = steps;
        }

        function showError(message) {
            const html = `
                <div class="alert alert-danger">
                    <h5>❌ خطأ:</h5>
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
    </script>
</body>
</html>
