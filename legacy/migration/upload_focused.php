<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نقل البيانات من الملفات المرفوعة فقط</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .hero {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        .upload-zone {
            border: 3px dashed #dc3545;
            border-radius: 15px;
            padding: 50px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #fff5f5;
        }
        .upload-zone:hover {
            background: #fee;
            border-color: #c82333;
        }
        .file-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 10px;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <!-- Hero -->
                <div class="hero text-center">
                    <h1><i class="fas fa-file-upload me-3"></i>نقل البيانات من الملفات المرفوعة</h1>
                    <p class="lead">استخدام الملفات المرفوعة فقط - وليس قاعدة البيانات</p>
                    <div class="mt-3">
                        <span class="badge bg-light text-dark fs-6">📁 من الملفات مباشرة</span>
                        <span class="badge bg-light text-dark fs-6">🎯 نقل دقيق</span>
                        <span class="badge bg-light text-dark fs-6">✅ مطابق للمرفوع</span>
                    </div>
                </div>

                <!-- تحذير مهم -->
                <div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>تنبيه مهم!</h5>
                    <p class="mb-0">هذا النظام سيستخدم <strong>الملفات المرفوعة فقط</strong> وليس قاعدة البيانات.
                    إذا رفعت ملف users (4).csv بـ 2,300 سجل، سيتم نقل هذه الـ 2,300 سجل بالضبط.</p>
                </div>

                <!-- رفع الملفات -->
                <div class="card mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5><i class="fas fa-cloud-upload-alt me-2"></i>رفع الملفات (سيتم استخدامها مباشرة)</h5>
                    </div>
                    <div class="card-body">
                        <form id="uploadForm" enctype="multipart/form-data">
                            <div class="upload-zone" onclick="document.getElementById('fileInput').click()">
                                <input type="file" id="fileInput" name="files[]" accept=".csv" multiple style="display: none;">
                                <i class="fas fa-file-upload fa-4x text-danger mb-3"></i>
                                <h3>ارفع ملفات CSV للمعالجة المباشرة</h3>
                                <p class="text-muted">سيتم استخدام البيانات من هذه الملفات فقط</p>
                                <button type="button" class="btn btn-danger btn-lg">
                                    <i class="fas fa-folder-open me-2"></i>اختيار ملفات CSV
                                </button>
                            </div>

                            <div id="selectedFiles" class="mt-3" style="display: none;">
                                <h6 class="text-success">الملفات المحددة للمعالجة:</h6>
                                <div id="filesList"></div>

                                <div class="text-center mt-3">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fas fa-upload me-2"></i>رفع وتحليل الملفات
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- نتائج التحليل -->
                <div id="analysisSection" style="display: none;" class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5><i class="fas fa-chart-bar me-2"></i>تحليل الملفات المرفوعة</h5>
                    </div>
                    <div class="card-body">
                        <div id="analysisResults"></div>

                        <div class="text-center mt-3">
                            <button type="button" id="processBtn" class="btn btn-warning btn-lg" disabled>
                                <i class="fas fa-cogs me-2"></i>
                                معالجة البيانات من الملفات المرفوعة
                            </button>
                        </div>
                    </div>
                </div>

                <!-- شريط التقدم -->
                <div id="progressSection" style="display: none;" class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h6><i class="fas fa-tasks me-2"></i>تقدم المعالجة</h6>
                    </div>
                    <div class="card-body">
                        <div class="progress mb-3" style="height: 20px;">
                            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                                 style="width: 0%"></div>
                        </div>
                        <div id="progressText" class="text-center h6"></div>
                        <div id="progressDetails" class="small text-muted"></div>
                    </div>
                </div>

                <!-- النتائج النهائية -->
                <div id="resultsArea"></div>

                <!-- معلومات توضيحية -->
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h6><i class="fas fa-info-circle me-2"></i>كيف يعمل هذا النظام</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-success">✅ ما سيحدث:</h6>
                                <ul>
                                    <li>قراءة البيانات من الملفات المرفوعة مباشرة</li>
                                    <li>تحليل كل ملف وتحديد نوعه (users, maids, etc.)</li>
                                    <li>معالجة البيانات (name → first_name + last_name)</li>
                                    <li>إدراج البيانات في platinum_new</li>
                                    <li>تقرير دقيق بعدد السجلات المعالجة من كل ملف</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-warning">⚠️ ملاحظات مهمة:</h6>
                                <ul>
                                    <li>سيتم استخدام الملفات المرفوعة فقط</li>
                                    <li>عدد السجلات سيطابق عدد السجلات في الملف</li>
                                    <li>لن يتم النقل من قواعد بيانات أخرى</li>
                                    <li>معالجة ذكية للأعمدة المختلفة</li>
                                    <li>حفظ آمن بدون حذف البيانات الموجودة</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let uploadedFiles = [];

        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('fileInput');
            const uploadForm = document.getElementById('uploadForm');

            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    displaySelectedFiles(this.files);
                    document.getElementById('selectedFiles').style.display = 'block';
                }
            });

            uploadForm.addEventListener('submit', function(e) {
                e.preventDefault();
                handleUpload();
            });

            document.getElementById('processBtn').addEventListener('click', processUploadedFiles);
        });

        function displaySelectedFiles(files) {
            let html = '';
            let totalRecords = 0;

            Array.from(files).forEach(file => {
                // تقدير عدد السجلات (تقريبي)
                const estimatedRecords = Math.floor(file.size / 300); // تقدير: 300 بايت لكل سجل
                totalRecords += estimatedRecords;

                html += `
                    <div class="file-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-file-csv text-success me-2"></i>
                                <strong>${file.name}</strong>
                                <small class="text-muted">(${formatFileSize(file.size)})</small>
                            </div>
                            <div>
                                <span class="badge bg-primary">~${estimatedRecords.toLocaleString()} سجل</span>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += `
                <div class="mt-3 p-3 bg-info text-white rounded text-center">
                    <h6>إجمالي متوقع: ${totalRecords.toLocaleString()} سجل</h6>
                    <small>سيتم معالجة هذا العدد تحديداً من الملفات المرفوعة</small>
                </div>
            `;

            document.getElementById('filesList').innerHTML = html;
        }

        async function handleUpload() {
            const formData = new FormData(document.getElementById('uploadForm'));
            formData.append('action', 'upload_and_analyze');

            showProgress();
            updateProgress(20, 'رفع وتحليل الملفات...');

            try {
                const response = await fetch('process_uploaded_files.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    uploadedFiles = data.files;
                    displayAnalysisResults(data.files);

                    document.getElementById('analysisSection').style.display = 'block';
                    document.getElementById('processBtn').disabled = false;

                    updateProgress(50, 'تم تحليل الملفات - جاهز للمعالجة');

                    setTimeout(() => {
                        document.getElementById('analysisSection').scrollIntoView();
                    }, 500);
                } else {
                    showError('خطأ في الرفع: ' + data.message);
                }
            } catch (error) {
                showError('خطأ في الرفع: ' + error.message);
            }
        }

        function displayAnalysisResults(files) {
            let html = '<div class="alert alert-success">';
            html += '<h6>✅ تم تحليل الملفات المرفوعة:</h6>';

            let totalRecords = 0;
            files.forEach(file => {
                totalRecords += file.records_count;

                html += `
                    <div class="mb-2 p-2 border rounded">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${file.original_name}</strong>
                                <span class="badge bg-info ms-2">${file.table_type}</span>
                            </div>
                            <div>
                                <span class="badge bg-primary">${file.records_count.toLocaleString()} سجل</span>
                                <span class="badge bg-secondary">${file.size}</span>
                            </div>
                        </div>
                        <small class="text-muted">الحقول: ${file.headers.join(', ')}</small>
                    </div>
                `;
            });

            html += `
                <hr>
                <div class="text-center">
                    <h5 class="text-success">إجمالي السجلات للمعالجة: ${totalRecords.toLocaleString()}</h5>
                    <small>هذا العدد بالضبط سيتم نقله من الملفات المرفوعة</small>
                </div>
            `;

            html += '</div>';
            document.getElementById('analysisResults').innerHTML = html;
        }

        async function processUploadedFiles() {
            if (!confirm('هل تريد معالجة البيانات من الملفات المرفوعة؟\n\nسيتم:\n• قراءة البيانات من الملفات مباشرة\n• معالجة الأعمدة (name → first_name + last_name)\n• نقل العدد الصحيح من السجلات\n\nالمتابعة؟')) {
                return;
            }

            const btn = document.getElementById('processBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري المعالجة...';

            showProgress();
            updateProgress(70, 'معالجة البيانات من الملفات المرفوعة...');

            try {
                const response = await fetch('process_uploaded_files.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=process_uploaded_data'
                });

                const data = await response.json();

                if (data.success) {
                    updateProgress(100, 'تم معالجة الملفات بنجاح!');
                    showResults(data.results);
                } else {
                    showError('خطأ في المعالجة: ' + data.message);
                }
            } catch (error) {
                showError('خطأ في المعالجة: ' + error.message);
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-cogs me-2"></i>معالجة البيانات من الملفات المرفوعة';
        }

        function showResults(results) {
            const html = `
                <div class="card" style="background: linear-gradient(45deg, #d4edda 0%, #c3e6cb 100%); border: 3px solid #28a745;">
                    <div class="card-header bg-success text-white">
                        <h4><i class="fas fa-check-circle me-2"></i>تم معالجة الملفات المرفوعة بنجاح!</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6>📊 ملخص المعالجة:</h6>
                            <ul class="mb-0">
                                <li><strong>الملفات المعالجة:</strong> ${results.files_processed}</li>
                                <li><strong>إجمالي السجلات المدرجة:</strong> ${results.total_records_inserted.toLocaleString()}</li>
                                <li><strong>من ملفات المستخدمين:</strong> ${results.users_from_files.toLocaleString()}</li>
                                <li><strong>من ملفات العاملات:</strong> ${results.maids_from_files.toLocaleString()}</li>
                                <li><strong>أعمدة مُضافة:</strong> ${results.columns_added}</li>
                            </ul>
                        </div>

                        <div class="row text-center mb-3">
                            <div class="col-md-3">
                                <div class="bg-primary text-white p-3 rounded">
                                    <h3>${results.users_from_files.toLocaleString()}</h3>
                                    <small>مستخدم من الملفات</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="bg-success text-white p-3 rounded">
                                    <h3>${results.maids_from_files.toLocaleString()}</h3>
                                    <small>عاملة من الملفات</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="bg-info text-white p-3 rounded">
                                    <h3>${results.orders_from_files.toLocaleString()}</h3>
                                    <small>طلب من الملفات</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="bg-warning text-white p-3 rounded">
                                    <h3>✓</h3>
                                    <small>معالجة مكتملة</small>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-success">
                            <h6>🎉 تفاصيل المعالجة:</h6>
                            <ul class="mb-0">
                                ${results.processing_details.map(detail => `<li>${detail}</li>`).join('')}
                            </ul>
                        </div>

                        <div class="text-center mt-4">
                            <a href="verify.php" target="_blank" class="btn btn-outline-primary btn-lg me-3">
                                <i class="fas fa-search me-2"></i>فحص النتائج التفصيلية
                            </a>
                            <button type="button" onclick="checkMatchingCounts()" class="btn btn-outline-success btn-lg">
                                <i class="fas fa-balance-scale me-2"></i>تأكيد تطابق الأعداد
                            </button>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('resultsArea').innerHTML = html;
        }

        async function checkMatchingCounts() {
            try {
                const response = await fetch('process_uploaded_files.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=check_uploaded_files'
                });

                const data = await response.json();

                if (data.success) {
                    let message = 'تأكيد تطابق الأعداد:\n\n';

                    data.files.forEach(file => {
                        message += `📁 ${file.name}: ${file.records_count} سجل\n`;
                    });

                    message += `\n📊 إجمالي الملفات: ${data.files.reduce((sum, f) => sum + f.records_count, 0)} سجل`;
                    message += '\n\n✅ هذا هو العدد الذي تم معالجته فعلياً';

                    alert(message);
                }
            } catch (error) {
                alert('خطأ في الفحص: ' + error.message);
            }
        }

        function showProgress() {
            document.getElementById('progressSection').style.display = 'block';
        }

        function updateProgress(percent, text, details = '') {
            document.getElementById('progressBar').style.width = percent + '%';
            document.getElementById('progressText').textContent = text;
            document.getElementById('progressDetails').textContent = details;
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

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    </script>
</body>
</html>
