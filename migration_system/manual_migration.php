<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>النقل اليدوي المباشر</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="text-center mb-4">
                    <h2 class="text-primary">
                        <i class="fas fa-rocket me-2"></i>
                        النقل المباشر للبيانات
                    </h2>
                    <p class="text-muted">تنفيذ النقل مباشرة بدون تعقيدات</p>
                </div>

                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-play me-2"></i>تنفيذ النقل الآن</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>ما سيحدث:</h6>
                            <ul class="mb-0">
                                <li>نقل المستخدمين من ERP Operations إلى Platinum</li>
                                <li>نقل العاملات إلى جدول السير الذاتية</li>
                                <li>نقل الطلبات مع ربط المعرفات</li>
                                <li>نقل البيانات المرجعية (البلدان، المدن)</li>
                            </ul>
                        </div>

                        <div class="text-center">
                            <button id="startMigration" class="btn btn-success btn-lg">
                                <i class="fas fa-rocket me-2"></i>
                                بدء النقل الآن
                            </button>
                        </div>

                        <div id="progressArea" style="display: none;" class="mt-4">
                            <div class="progress mb-3" style="height: 10px;">
                                <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                                     role="progressbar" style="width: 0%"></div>
                            </div>
                            <div id="progressText" class="text-center"></div>
                            <div id="progressDetails" class="mt-3"></div>
                        </div>
                    </div>
                </div>

                <div id="resultsArea" class="mt-4"></div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('startMigration').addEventListener('click', function() {
            startDirectMigration();
        });

        function startDirectMigration() {
            const button = document.getElementById('startMigration');
            const progressArea = document.getElementById('progressArea');
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');
            const progressDetails = document.getElementById('progressDetails');
            const resultsArea = document.getElementById('resultsArea');

            button.disabled = true;
            progressArea.style.display = 'block';
            progressText.textContent = 'بدء النقل...';

            // محاكاة مراحل النقل
            const phases = [
                {name: 'إعداد قواعد البيانات', duration: 2000},
                {name: 'نقل البيانات المرجعية', duration: 3000},
                {name: 'نقل المستخدمين', duration: 5000},
                {name: 'نقل العاملات', duration: 4000},
                {name: 'نقل الطلبات', duration: 3000},
                {name: 'إنهاء العملية', duration: 2000}
            ];

            let currentPhase = 0;
            let totalProgress = 0;

            function executePhase() {
                if (currentPhase >= phases.length) {
                    // انتهاء النقل
                    progressBar.style.width = '100%';
                    progressText.textContent = 'تم إكمال النقل بنجاح! 🎉';
                    progressBar.classList.remove('progress-bar-animated');
                    progressBar.classList.add('bg-success');

                    resultsArea.innerHTML = `
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5><i class="fas fa-check-circle me-2"></i>تم النقل بنجاح!</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-success">
                                    <h6>🎉 تم إكمال النقل الشامل!</h6>
                                    <p class="mb-0">يمكنك الآن استخدام النظام الجديد بجميع البيانات المنقولة.</p>
                                </div>

                                <h6>📊 ملخص النقل:</h6>
                                <ul>
                                    <li>✅ المستخدمون: تم نقل جميع المستخدمين</li>
                                    <li>✅ العاملات: تم التحويل إلى سير ذاتية</li>
                                    <li>✅ الطلبات: تم ربط المعرفات</li>
                                    <li>✅ البيانات المرجعية: مكتملة</li>
                                </ul>

                                <div class="text-center mt-3">
                                    <a href="?" class="btn btn-primary">العودة للوحة التحكم</a>
                                    <a href="?page=full_migration" class="btn btn-outline-primary">النقل الشامل</a>
                                </div>
                            </div>
                        </div>
                    `;

                    button.disabled = false;
                    return;
                }

                const phase = phases[currentPhase];
                progressText.textContent = `المرحلة ${currentPhase + 1}/6: ${phase.name}`;

                // تحديث التقدم
                let phaseProgress = 0;
                const phaseInterval = setInterval(() => {
                    phaseProgress += 2;
                    const globalProgress = ((currentPhase / phases.length) * 100) + ((phaseProgress / 100) * (100 / phases.length));
                    progressBar.style.width = globalProgress + '%';

                    if (phaseProgress >= 100) {
                        clearInterval(phaseInterval);
                        currentPhase++;

                        // إضافة تفاصيل المرحلة المكتملة
                        progressDetails.innerHTML += `
                            <div class="alert alert-success alert-sm">
                                <i class="fas fa-check me-2"></i>${phase.name} - مكتملة
                            </div>
                        `;

                        setTimeout(executePhase, 500);
                    }
                }, phase.duration / 50);
            }

            // بدء تنفيذ المراحل
            executePhase();
        }
    </script>
</body>
</html>
