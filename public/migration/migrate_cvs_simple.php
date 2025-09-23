<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>نقل العاملات - مُبسط</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; background: #f8f9fa; }
        .container { max-width: 800px; }
        .card { margin: 20px 0; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .btn-custom { padding: 15px; font-size: 1.1em; margin: 10px 0; }
        .status-badge { padding: 5px 10px; border-radius: 15px; margin: 2px; }
        .result-box { background: #e8f5e9; padding: 15px; border-radius: 8px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center mb-4">نقل العاملات (CV) - مُبسط</h1>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5>الخطوة 1: تحضير الجداول</h5>
            </div>
            <div class="card-body">
                <button class="btn btn-warning btn-custom w-100" onclick="prepareTablesForCV()">
                    <i class="fas fa-cogs me-2"></i>تحضير الجداول (nationalities + religions + maids)
                </button>
                <div id="prepare-result"></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-success text-white">
                <h5>الخطوة 2: نقل العاملات</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6>حالات cv_status:</h6>
                    <div class="mt-2">
                        <span class="status-badge bg-secondary text-white">0 = غير محجوز</span>
                        <span class="status-badge bg-primary text-white">1 = محجوز</span>
                        <span class="status-badge bg-info text-white">2 = باك آوت</span>
                        <span class="status-badge bg-warning">3 = معلق</span>
                        <span class="status-badge bg-success text-white">4 = تم الترحيل</span>
                        <span class="status-badge bg-danger text-white">5 = هروب</span>
                    </div>
                </div>

                <button class="btn btn-success btn-custom w-100" onclick="migrateCVs()" id="migrateCVBtn">
                    <i class="fas fa-rocket me-2"></i>نقل جميع العاملات (2,173 سجل CV)
                </button>
                <div id="migrate-result"></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-info text-white">
                <h5>الخطوة 3: التحقق من النتائج</h5>
            </div>
            <div class="card-body">
                <button class="btn btn-info btn-custom w-100" onclick="checkResults()">
                    <i class="fas fa-chart-bar me-2"></i>فحص النتائج النهائية
                </button>
                <div id="check-result"></div>
            </div>
        </div>
    </div>

    <script>
        async function prepareTablesForCV() {
            const btn = event.target;
            const resultDiv = document.getElementById('prepare-result');

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري التحضير...';
            resultDiv.innerHTML = '<div class="alert alert-info">جاري تحضير الجداول...</div>';

            try {
                // تحضير جدول maids
                const response1 = await fetch('migrate_cvs_now.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=prepare'
                });
                const data1 = await response1.json();

                // تصدير ونقل nationalities
                const response2 = await fetch('file_processor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=export_from_old_db&export_table=nationalities&old_db_host=127.0.0.1&old_db_port=8889&old_db_name=platinum&old_db_user=root&old_db_pass=root&db_host=127.0.0.1&db_port=8889&db_name=platinum_new&db_user=root&db_pass=root'
                });
                const export2 = await response2.json();

                if (export2.success) {
                    const response3 = await fetch('file_processor.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `action=process_exported_file&exported_file=${export2.file_name}&table_type=nationalities&db_host=127.0.0.1&db_port=8889&db_name=platinum_new&db_user=root&db_pass=root`
                    });
                    const process3 = await response3.json();
                }

                resultDiv.innerHTML = `
                    <div class="result-box">
                        <h6>تم التحضير!</h6>
                        <p>جدول maids: ${data1.message}</p>
                        <p>جدول nationalities: ${export2.success ? export2.record_count + ' سجل تم تصديره' : 'خطأ'}</p>
                        <p>يمكنك الآن المتابعة للخطوة التالية</p>
                    </div>
                `;

            } catch (error) {
                resultDiv.innerHTML = `<div class="alert alert-danger">خطأ: ${error.message}</div>`;
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-cogs me-2"></i>تحضير الجداول (nationalities + religions + maids)';
        }

        async function migrateCVs() {
            if (!confirm('نقل 2,173 عاملة من جدول cvs؟\n\nسيتم:\n- نقل جميع البيانات\n- الحفاظ على cv_status\n- معالجة العلاقات\n\nالمتابعة؟')) {
                return;
            }

            const btn = document.getElementById('migrateCVBtn');
            const resultDiv = document.getElementById('migrate-result');

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري نقل العاملات...';
            resultDiv.innerHTML = '<div class="alert alert-info">جاري النقل...</div>';

            try {
                const response = await fetch('migrate_cvs_now.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=migrate&cv_table=cvs&preserve_cv_status=true'
                });

                const data = await response.json();

                if (data.success && data.processed > 0) {
                    resultDiv.innerHTML = `
                        <div class="result-box">
                            <h6>تم نقل العاملات بنجاح!</h6>
                            <p><strong>إجمالي:</strong> ${data.total_records} سجل</p>
                            <p><strong>منقول:</strong> ${data.processed} سجل</p>
                            <p><strong>متخطى:</strong> ${data.skipped} سجل</p>

                            <h6 class="mt-3">توزيع cv_status:</h6>
                            ${Object.entries(data.cv_status_conversions).map(([status, count]) => {
                                const statusNames = {0: 'غير محجوز', 1: 'محجوز', 2: 'باك آوت', 3: 'معلق', 4: 'تم الترحيل', 5: 'هروب'};
                                return `<span class="badge bg-secondary me-1">${statusNames[status] || status}: ${count}</span>`;
                            }).join('')}
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-warning">
                            <h6>لم يتم نقل أي سجل!</h6>
                            <p><strong>المعالج:</strong> ${data.processed || 0}</p>
                            <p><strong>المتخطى:</strong> ${data.skipped || 0}</p>

                            ${data.debug_info && data.debug_info.length > 0 ? `
                                <details class="mt-2">
                                    <summary>تفاصيل الأخطاء</summary>
                                    <pre>${JSON.stringify(data.debug_info.slice(0, 2), null, 2)}</pre>
                                </details>
                            ` : ''}
                        </div>
                    `;
                }

            } catch (error) {
                resultDiv.innerHTML = `<div class="alert alert-danger">خطأ في الاتصال: ${error.message}</div>`;
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-rocket me-2"></i>نقل جميع العاملات (2,173 سجل CV)';
        }

        async function checkResults() {
            const btn = event.target;
            const resultDiv = document.getElementById('check-result');

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري الفحص...';

            try {
                const response = await fetch('migrate_cvs_now.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=check_result'
                });

                const data = await response.json();

                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="result-box">
                            <h6>النتائج النهائية</h6>
                            <p><strong>إجمالي العاملات:</strong> ${data.maids_count}</p>

                            ${data.status_stats && !data.status_stats.error ? `
                                <h6 class="mt-3">توزيع cv_status:</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead><tr><th>cv_status</th><th>العدد</th><th>available</th><th>status</th></tr></thead>
                                        <tbody>
                                            ${data.status_stats.map(stat => `
                                                <tr>
                                                    <td>${stat.cv_status}</td>
                                                    <td>${stat.count}</td>
                                                    <td>${stat.available}</td>
                                                    <td>${stat.status}</td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                            ` : '<p class="text-warning">لا توجد بيانات cv_status</p>'}
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `<div class="alert alert-danger">خطأ: ${data.message}</div>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="alert alert-danger">خطأ: ${error.message}</div>`;
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-chart-bar me-2"></i>فحص النتائج النهائية';
        }

        // فحص تلقائي عند تحميل الصفحة
        window.addEventListener('load', function() {
            // فحص حالة النظام تلقائياً
            fetch('migrate_cvs_now.php?action=analyze')
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.old_cvs.exists) {
                        const info = document.createElement('div');
                        info.className = 'alert alert-info';
                        info.innerHTML = `
                            <h6>معلومات الجدول:</h6>
                            <p>تم اكتشاف جدول cvs: ${data.old_cvs.count} سجل</p>
                            <p>جدول maids الجديد: ${data.new_maids.exists ? 'موجود' : 'غير موجود'}</p>
                        `;
                        document.querySelector('.container').insertBefore(info, document.querySelector('.card'));
                    }
                })
                .catch(e => console.log('تم تخطي الفحص التلقائي'));
        });
    </script>
</body>
</html>

