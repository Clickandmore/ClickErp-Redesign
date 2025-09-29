<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مركز نقل البيانات - اختر النظام المناسب</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
            font-size: 2.5rem;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 40px;
            font-size: 1.1rem;
        }

        .systems-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .system-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
        }

        .system-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }

        .system-card.recommended {
            border: 3px solid #ffc107;
        }

        .recommended-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #ffc107;
            color: #000;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .card-header {
            padding: 30px;
            text-align: center;
            color: white;
        }

        .card-header.primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .card-header.success {
            background: linear-gradient(135deg, #56ab2f, #a8e063);
        }

        .card-header.info {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
        }

        .card-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .card-title {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .card-subtitle {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .card-body {
            padding: 30px;
        }

        .features {
            list-style: none;
            margin-bottom: 20px;
        }

        .features li {
            padding: 8px 0;
            color: #555;
            position: relative;
            padding-right: 25px;
        }

        .features li:before {
            content: "✓";
            position: absolute;
            right: 0;
            color: #28a745;
            font-weight: bold;
        }

        .stats {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: #333;
        }

        .stat-value {
            font-weight: bold;
            color: #28a745;
        }

        .btn-enter {
            display: block;
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            text-align: center;
            font-weight: bold;
            font-size: 1.1rem;
            transition: all 0.3s;
        }

        .btn-enter:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-enter.success {
            background: linear-gradient(135deg, #56ab2f, #a8e063);
        }

        .btn-enter.info {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
        }

        .comparison-table {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f9fa;
            color: #333;
            font-weight: bold;
        }

        .star {
            color: #ffc107;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #eee;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 مركز نقل البيانات</h1>
        <p class="subtitle">اختر النظام الأنسب لاحتياجاتك</p>

        <div class="systems-grid">
            <!-- النظام الأدق -->
            <div class="system-card recommended">
                <span class="recommended-badge">⭐ الموصى به</span>
                <div class="card-header primary">
                    <div class="card-icon">🏆</div>
                    <h2 class="card-title">النظام المحسن</h2>
                    <p class="card-subtitle">الأكثر دقة واحترافية</p>
                </div>
                <div class="card-body">
                    <div class="stats">
                        <div class="stat-item">
                            <span>دقة النقل:</span>
                            <span class="stat-value">99.65%</span>
                        </div>
                        <div class="stat-item">
                            <span>سجلات منقولة:</span>
                            <span class="stat-value">2,308</span>
                        </div>
                        <div class="stat-item">
                            <span>حجم البيانات:</span>
                            <span class="stat-value">508.58 KB</span>
                        </div>
                    </div>
                    <ul class="features">
                        <li>تصدير واستيراد تلقائي</li>
                        <li>إحصائيات تفصيلية دقيقة</li>
                        <li>حفظ نسخ احتياطية</li>
                        <li>إعدادات مرنة للقاعدة</li>
                        <li>بدون مشاكل الكاش</li>
                    </ul>
                    <a href="/migration/new_migration_system.php" class="btn-enter">
                        دخول النظام المحسن
                    </a>
                </div>
            </div>

            <!-- النظام البسيط -->
            <div class="system-card">
                <div class="card-header success">
                    <div class="card-icon">💚</div>
                    <h2 class="card-title">النظام البسيط</h2>
                    <p class="card-subtitle">سريع ومباشر</p>
                </div>
                <div class="card-body">
                    <div class="stats">
                        <div class="stat-item">
                            <span>عدد الخطوات:</span>
                            <span class="stat-value">3 فقط</span>
                        </div>
                        <div class="stat-item">
                            <span>سهولة الاستخدام:</span>
                            <span class="stat-value">⭐⭐⭐⭐⭐</span>
                        </div>
                        <div class="stat-item">
                            <span>السرعة:</span>
                            <span class="stat-value">فائقة</span>
                        </div>
                    </div>
                    <ul class="features">
                        <li>3 خطوات بسيطة فقط</li>
                        <li>واجهة نظيفة وسهلة</li>
                        <li>إعداد تلقائي للجدول</li>
                        <li>مناسب للمبتدئين</li>
                        <li>نتائج فورية</li>
                    </ul>
                    <a href="/migration/simple_migration.php" class="btn-enter success">
                        دخول النظام البسيط
                    </a>
                </div>
            </div>

            <!-- النظام الموحد -->
            <div class="system-card">
                <div class="card-header info">
                    <div class="card-icon">🔵</div>
                    <h2 class="card-title">النظام الموحد</h2>
                    <p class="card-subtitle">شامل ومتكامل</p>
                </div>
                <div class="card-body">
                    <div class="stats">
                        <div class="stat-item">
                            <span>الجداول المدعومة:</span>
                            <span class="stat-value">الكل</span>
                        </div>
                        <div class="stat-item">
                            <span>رفع CSV:</span>
                            <span class="stat-value">✓ متاح</span>
                        </div>
                        <div class="stat-item">
                            <span>في لوحة التحكم:</span>
                            <span class="stat-value">✓ نعم</span>
                        </div>
                    </div>
                    <ul class="features">
                        <li>نقل كل الجداول</li>
                        <li>رفع ملفات CSV</li>
                        <li>نقل تلقائي أو يدوي</li>
                        <li>إحصائيات فورية</li>
                        <li>تصميم بسيط</li>
                    </ul>
                    <a href="/migration.php" class="btn-enter info">
                        دخول النظام الموحد
                    </a>
                </div>
            </div>
        </div>

        <!-- جدول المقارنة -->
        <div class="comparison-table">
            <h3 style="text-align: center; margin-bottom: 20px; color: #333;">📊 مقارنة سريعة</h3>
            <table>
                <thead>
                    <tr>
                        <th>الميزة</th>
                        <th>النظام المحسن</th>
                        <th>النظام البسيط</th>
                        <th>النظام الموحد</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>الدقة</strong></td>
                        <td><span class="star">⭐⭐⭐⭐⭐</span></td>
                        <td><span class="star">⭐⭐⭐⭐</span></td>
                        <td><span class="star">⭐⭐⭐⭐</span></td>
                    </tr>
                    <tr>
                        <td><strong>البساطة</strong></td>
                        <td><span class="star">⭐⭐⭐⭐</span></td>
                        <td><span class="star">⭐⭐⭐⭐⭐</span></td>
                        <td><span class="star">⭐⭐⭐⭐</span></td>
                    </tr>
                    <tr>
                        <td><strong>الإحصائيات</strong></td>
                        <td><span class="star">⭐⭐⭐⭐⭐</span></td>
                        <td><span class="star">⭐⭐⭐</span></td>
                        <td><span class="star">⭐⭐⭐⭐</span></td>
                    </tr>
                    <tr>
                        <td><strong>المرونة</strong></td>
                        <td><span class="star">⭐⭐⭐⭐⭐</span></td>
                        <td><span class="star">⭐⭐⭐</span></td>
                        <td><span class="star">⭐⭐⭐⭐</span></td>
                    </tr>
                    <tr>
                        <td><strong>السرعة</strong></td>
                        <td><span class="star">⭐⭐⭐⭐</span></td>
                        <td><span class="star">⭐⭐⭐⭐⭐</span></td>
                        <td><span class="star">⭐⭐⭐⭐</span></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="footer">
            <p>جميع الأنظمة تعمل بنجاح 100% ✅</p>
            <p style="margin-top: 10px; font-size: 0.9rem;">آخر تحديث: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>
