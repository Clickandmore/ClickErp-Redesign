<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عقد تأجير عاملة منزلية - رقم {{ $contract->contract_number ?? $contract->id }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Amiri', serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            direction: rtl;
            text-align: right;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #2c5aa0;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .company-logo {
            width: 100px;
            height: 100px;
            margin: 0 auto 15px;
        }

        .header h1 {
            font-size: 24px;
            font-weight: bold;
            color: #2c5aa0;
            margin-bottom: 10px;
        }

        .header h2 {
            font-size: 18px;
            color: #666;
            margin-bottom: 5px;
        }

        .contract-number {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
            font-weight: bold;
            font-size: 16px;
        }

        .section {
            margin-bottom: 25px;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }

        .section-header {
            background: #2c5aa0;
            color: white;
            padding: 12px 15px;
            font-weight: bold;
            font-size: 16px;
        }

        .section-content {
            padding: 15px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dotted #ddd;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: bold;
            color: #555;
            min-width: 120px;
        }

        .info-value {
            color: #333;
        }

        .financial-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .financial-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            text-align: center;
        }

        .financial-item h3 {
            font-size: 20px;
            margin-bottom: 5px;
        }

        .financial-item p {
            font-size: 12px;
            opacity: 0.9;
        }

        .terms-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .terms-section h3 {
            color: #2c5aa0;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .terms-list {
            list-style: none;
            padding: 0;
        }

        .terms-list li {
            padding: 8px 0;
            border-bottom: 1px dotted #ddd;
        }

        .terms-list li:before {
            content: "•";
            color: #2c5aa0;
            font-weight: bold;
            margin-left: 10px;
        }

        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
        }

        .signature-block {
            text-align: center;
        }

        .signature-line {
            border-bottom: 2px solid #333;
            width: 200px;
            margin: 40px auto 10px;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }

        @media print {
            body {
                font-size: 12px;
            }
            .container {
                padding: 10px;
            }
            .section {
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company-logo">
                <!-- Company logo would go here -->
                <div style="width: 100px; height: 100px; background: #2c5aa0; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; font-weight: bold;">
                    شعار
                </div>
            </div>
            <h1>شركة الإنجاز للاستقدام</h1>
            <h2>عقد تأجير عاملة منزلية</h2>
        </div>

        <!-- Contract Number -->
        <div class="contract-number">
            رقم العقد: {{ $contract->contract_number ?? $contract->id }}
            &nbsp;&nbsp;|&nbsp;&nbsp;
            تاريخ العقد: {{ $contract->contract_date ? $contract->contract_date->format('Y/m/d') : date('Y/m/d') }}
        </div>

        <!-- Financial Summary -->
        @if($contract->info)
        <div class="financial-summary">
            <div class="financial-grid">
                <div class="financial-item">
                    <h3>{{ number_format($contract->info->monthly_cost ?? 0, 0) }}</h3>
                    <p>التكلفة الشهرية (ريال)</p>
                </div>
                <div class="financial-item">
                    <h3>{{ $contract->info->contract_duration_months ?? 0 }}</h3>
                    <p>مدة العقد (شهر)</p>
                </div>
                <div class="financial-item">
                    <h3>{{ number_format($contract->info->contract_value ?? 0, 0) }}</h3>
                    <p>قيمة العقد الإجمالية (ريال)</p>
                </div>
                <div class="financial-item">
                    <h3>{{ $contract->info->working_hours_per_day ?? 0 }}</h3>
                    <p>ساعات العمل يومياً</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Client Information -->
        <div class="section">
            <div class="section-header">
                <i class="fas fa-user"></i> معلومات العميل (الطرف الأول)
            </div>
            <div class="section-content">
                @if($contract->user)
                <div class="info-grid">
                    <div>
                        <div class="info-item">
                            <span class="info-label">الاسم:</span>
                            <span class="info-value">{{ $contract->user->name }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">رقم الهوية:</span>
                            <span class="info-value">{{ $contract->user->national_num }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">رقم الجوال:</span>
                            <span class="info-value">{{ $contract->user->phone_code }}{{ $contract->user->phone }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">الجنسية:</span>
                            <span class="info-value">{{ $contract->user->nationality->name ?? 'غير محدد' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">المهنة:</span>
                            <span class="info-value">{{ $contract->user->profession ?? 'غير محدد' }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="info-item">
                            <span class="info-label">تاريخ الميلاد:</span>
                            <span class="info-value">{{ $contract->user->birthdate ? $contract->user->birthdate->format('Y/m/d') : 'غير محدد' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">المدينة:</span>
                            <span class="info-value">{{ $contract->user->city }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">العنوان:</span>
                            <span class="info-value">{{ $contract->user->address }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">عدد أفراد الأسرة:</span>
                            <span class="info-value">{{ $contract->user->family_num ?? 'غير محدد' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">الدخل الشهري:</span>
                            <span class="info-value">{{ $contract->user->monthly_income ? number_format($contract->user->monthly_income, 0) . ' ريال' : 'غير محدد' }}</span>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Maid Information -->
        <div class="section">
            <div class="section-header">
                <i class="fas fa-female"></i> معلومات العاملة (الطرف الثاني)
            </div>
            <div class="section-content">
                @if($contract->maid)
                <div class="info-grid">
                    <div>
                        <div class="info-item">
                            <span class="info-label">الاسم:</span>
                            <span class="info-value">{{ $contract->maid->name }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">رقم الجواز:</span>
                            <span class="info-value">{{ $contract->maid->passport_num }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">الجنسية:</span>
                            <span class="info-value">{{ $contract->maid->nationality->translations->first()->title ?? 'غير محدد' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">الديانة:</span>
                            <span class="info-value">{{ $contract->maid->religion->translations->first()->title ?? 'غير محدد' }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="info-item">
                            <span class="info-label">العمر:</span>
                            <span class="info-value">{{ $contract->maid->age }} سنة</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">سنوات الخبرة:</span>
                            <span class="info-value">{{ $contract->maid->experience_years }} سنة</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">الراتب المطلوب:</span>
                            <span class="info-value">{{ number_format($contract->maid->salary, 0) }} ريال</span>
                        </div>
                    </div>
                </div>
                @else
                <p style="text-align: center; color: #666;">لم يتم تحديد عاملة بعد</p>
                @endif
            </div>
        </div>

        <!-- Contract Details -->
        @if($contract->info)
        <div class="section">
            <div class="section-header">
                <i class="fas fa-file-contract"></i> تفاصيل عقد التأجير
            </div>
            <div class="section-content">
                <div class="info-grid">
                    <div>
                        <div class="info-item">
                            <span class="info-label">ساعات العمل يومياً:</span>
                            <span class="info-value">{{ $contract->info->working_hours_per_day }} ساعة</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">أجرة الساعة:</span>
                            <span class="info-value">{{ number_format($contract->info->hourly_rate, 2) }} ريال</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">الراتب الشهري:</span>
                            <span class="info-value">{{ number_format($contract->info->monthly_salary, 0) }} ريال</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">مبلغ نقل الكفالة:</span>
                            <span class="info-value">{{ number_format($contract->info->sponsorship_transfer_amount, 0) }} ريال</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">الراتب بعد نقل الكفالة:</span>
                            <span class="info-value">{{ number_format($contract->info->salary_after_sponsorship, 0) }} ريال</span>
                        </div>
                    </div>
                    <div>
                        <div class="info-item">
                            <span class="info-label">التكلفة الشهرية:</span>
                            <span class="info-value">{{ number_format($contract->info->monthly_cost, 0) }} ريال</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">مدة العقد:</span>
                            <span class="info-value">{{ $contract->info->contract_duration_months }} شهر ({{ $contract->info->contract_duration_days }} يوم)</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">قيمة العقد الإجمالية:</span>
                            <span class="info-value">{{ number_format($contract->info->contract_value, 0) }} ريال</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">الإنجاز من:</span>
                            <span class="info-value">{{ $contract->info->ingaz_from ? $contract->info->ingaz_from->format('Y/m/d') : 'غير محدد' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">الإنجاز إلى:</span>
                            <span class="info-value">{{ $contract->info->ingaz_to ? $contract->info->ingaz_to->format('Y/m/d') : 'غير محدد' }}</span>
                        </div>
                    </div>
                </div>
                @if($contract->info->notes)
                <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                    <strong>ملاحظات:</strong>
                    <p style="margin-top: 5px;">{{ $contract->info->notes }}</p>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Terms and Conditions -->
        <div class="terms-section">
            <h3>شروط وأحكام عقد التأجير</h3>
            <ul class="terms-list">
                <li>يلتزم الطرف الأول بدفع التكلفة الشهرية في موعدها المحدد</li>
                <li>مدة العقد محددة وفقاً للاتفاق المذكور أعلاه</li>
                <li>يحق للطرف الأول إنهاء العقد بإشعار مسبق 30 يوماً</li>
                <li>العاملة ملتزمة بأداء المهام المتفق عليها خلال ساعات العمل المحددة</li>
                <li>يتم تطبيق أحكام نظام العمل السعودي على هذا العقد</li>
                <li>في حالة وجود أي نزاع، يتم الرجوع إلى المحاكم المختصة</li>
                <li>هذا العقد ساري المفعول من تاريخ التوقيع</li>
            </ul>
        </div>

        <!-- Signatures -->
        <div class="signatures">
            <div class="signature-block">
                <h4>الطرف الأول (العميل)</h4>
                <div class="signature-line"></div>
                <p>{{ $contract->user->name ?? '........................' }}</p>
                <p>التوقيع والتاريخ</p>
            </div>
            <div class="signature-block">
                <h4>الطرف الثاني (الشركة)</h4>
                <div class="signature-line"></div>
                <p>شركة الإنجاز للاستقدام</p>
                <p>التوقيع والختم</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>تم إنشاء هذا العقد بتاريخ {{ date('Y/m/d H:i') }}</p>
            <p>شركة الإنجاز للاستقدام - جميع الحقوق محفوظة</p>
        </div>
    </div>

    <script>
        // Auto print when page loads
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
