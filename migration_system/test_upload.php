<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار رفع الملفات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        .upload-area {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
        }
        .upload-area:hover {
            border-color: #007bff;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h3>اختبار رفع الملفات</h3>
                    </div>
                    <div class="card-body">
                        <form id="uploadForm" enctype="multipart/form-data">
                            <div class="upload-area mb-3" onclick="document.getElementById('csvFile').click()">
                                <input type="file" id="csvFile" name="csv_file" accept=".csv" style="display: none;" required>
                                <div id="uploadContent">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                    <h5>انقر لاختيار ملف CSV</h5>
                                    <p class="text-muted">الحد الأقصى: 10 MB</p>
                                </div>
                                <div id="fileInfo" style="display: none;">
                                    <h6 id="fileName"></h6>
                                    <small id="fileSize"></small>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <button type="button" id="previewBtn" class="btn btn-info w-100" disabled>
                                        معاينة البيانات
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <button type="button" id="uploadBtn" class="btn btn-success w-100" disabled>
                                        رفع الملف
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div id="result" class="mt-4"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const csvFile = document.getElementById('csvFile');
        const uploadContent = document.getElementById('uploadContent');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const previewBtn = document.getElementById('previewBtn');
        const uploadBtn = document.getElementById('uploadBtn');
        const result = document.getElementById('result');

        csvFile.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);

                uploadContent.style.display = 'none';
                fileInfo.style.display = 'block';

                previewBtn.disabled = false;
                uploadBtn.disabled = false;
            }
        });

        previewBtn.addEventListener('click', function() {
            uploadFile('preview');
        });

        uploadBtn.addEventListener('click', function() {
            uploadFile('upload');
        });

        function uploadFile(action) {
            const file = csvFile.files[0];
            if (!file) {
                showResult('error', 'يرجى اختيار ملف أولاً');
                return;
            }

            const formData = new FormData();
            formData.append('csv_file', file);
            formData.append('action', action);

            showResult('info', 'جاري ' + (action === 'preview' ? 'معاينة' : 'رفع') + ' الملف...');

            fetch('api/simple_upload.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        if (action === 'preview') {
                            showPreview(data.data);
                        } else {
                            showResult('success', 'تم رفع الملف بنجاح!');
                            console.log('File info:', data.file_info);
                        }
                    } else {
                        showResult('error', 'خطأ: ' + data.message);
                        if (data.debug_info) {
                            console.log('Debug info:', data.debug_info);
                        }
                    }
                } catch (e) {
                    showResult('error', 'استجابة غير صالحة من الخادم: ' + text);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showResult('error', 'حدث خطأ في الاتصال: ' + error.message);
            });
        }

        function showResult(type, message) {
            const alertClass = {
                'success': 'alert-success',
                'error': 'alert-danger',
                'info': 'alert-info',
                'warning': 'alert-warning'
            };

            result.innerHTML = `
                <div class="alert ${alertClass[type]} alert-dismissible fade show">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        }

        function showPreview(data) {
            let html = '<div class="alert alert-success"><h6>معاينة البيانات:</h6>';

            if (data.headers && data.headers.length > 0) {
                html += '<p><strong>العناوين:</strong> ' + data.headers.join(', ') + '</p>';
            }

            if (data.preview && data.preview.length > 0) {
                html += '<div class="table-responsive"><table class="table table-sm table-striped">';
                html += '<thead><tr>';
                data.headers.forEach(header => {
                    html += '<th>' + header + '</th>';
                });
                html += '</tr></thead><tbody>';

                data.preview.forEach(row => {
                    html += '<tr>';
                    Object.values(row).forEach(cell => {
                        html += '<td>' + (cell || '') + '</td>';
                    });
                    html += '</tr>';
                });
                html += '</tbody></table></div>';
            }

            html += '<p><strong>إجمالي الصفوف:</strong> ' + data.total_rows + '</p>';
            html += '</div>';

            result.innerHTML = html;
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
