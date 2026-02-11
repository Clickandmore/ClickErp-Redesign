<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طباعة عقد تأجير - {{ $contract->contract_number ?? 'غير محدد' }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        @media print {
            @page {
                size: A4;
                margin: 15mm;
            }
            .no-print {
                display: none !important;
            }
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .page-break {
                page-break-before: always;
            }
            .signature-controls {
                display: none !important;
            }
        }

        body {
            font-family: 'Arial', 'Tahoma', sans-serif;
            font-size: 12.5pt;
            line-height: 1.5;
            color: #000;
            background: white;
            margin: 0;
            padding: 10px;
        }

        .contract-container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            position: relative;
        }

        .header h1 {
            font-size: 22pt;
            font-weight: bold;
            margin: 8px 0;
        }

        .logo-container {
            position: absolute;
            top: 0;
            right: 20px;
            width: 100px;
            height: 100px;
        }

        .logo-container img {
            width: 100%;
            height: auto;
            object-fit: contain;
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            opacity: 0.05;
            z-index: -1;
            width: 400px;
            height: 400px;
        }

        @media print {
            .watermark {
                opacity: 0.03;
            }
        }

        .contract-info {
            margin-bottom: 20px;
        }

        .contract-body {
            text-align: justify;
            margin: 20px 0;
            line-height: 1.8;
        }

        .contract-body p {
            margin-bottom: 4px;
        }

        .party {
            margin-bottom: 8px;
            line-height: 1.7;
        }

        .party-title {
            font-weight: bold;
            font-size: 14pt;
            margin-bottom: 8px;
        }

        .party div {
            margin-bottom: 5px;
        }

        .terms-section {
            margin: 20px 0;
        }

        .terms-section h3 {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 10px;
            margin-top: 15px;
        }

        .term-item {
            margin-bottom: 10px;
            padding-right: 15px;
            line-height: 1.6;
        }

        .term-number {
            font-weight: bold;
            display: inline-block;
            width: 30px;
        }

        .signatures-section {
            margin-top: 30px;
        }

        .signatures-section div {
            margin-bottom: 8px;
        }

        .print-button {
            position: fixed;
            top: 20px;
            left: 20px;
            padding: 10px 20px;
            background: #2794eb;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            z-index: 1000;
        }

        .print-button:hover {
            background: #1a73c7;
        }

        /* Signature Styles */
        .signature-container {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #f9f9f9;
        }

        .signature-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }

        .signature-canvas-container {
            text-align: center;
            margin: 15px 0;
        }

        #signatureCanvas {
            border: 2px solid #ccc;
            border-radius: 5px;
            background: white;
            cursor: crosshair;
        }

        .signature-controls {
            margin-top: 10px;
            text-align: center;
        }

        .signature-btn {
            margin: 0 5px;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .signature-btn-primary {
            background: #28a745;
            color: white;
        }

        .signature-btn-primary:hover {
            background: #218838;
        }

        .signature-btn-secondary {
            background: #6c757d;
            color: white;
        }

        .signature-btn-secondary:hover {
            background: #5a6268;
        }

        .signature-btn-danger {
            background: #dc3545;
            color: white;
        }

        .signature-btn-danger:hover {
            background: #c82333;
        }

        .signature-display {
            margin-top: 15px;
            text-align: center;
        }

        .signature-display img {
            max-width: 200px;
            max-height: 100px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .signature-status {
            margin-top: 10px;
            padding: 8px;
            border-radius: 4px;
            text-align: center;
            font-weight: bold;
        }

        .signature-status.signed {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .signature-status.not-signed {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Signature Styles */
        .signature-container {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #f9f9f9;
        }

        .signature-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }

        .signature-canvas-container {
            text-align: center;
            margin: 15px 0;
        }

        #signatureCanvas {
            border: 2px solid #ccc;
            border-radius: 5px;
            background: white;
            cursor: crosshair;
        }

        .signature-controls {
            margin-top: 10px;
            text-align: center;
        }

        .signature-btn {
            margin: 0 5px;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .signature-btn-primary {
            background: #28a745;
            color: white;
        }

        .signature-btn-primary:hover {
            background: #218838;
        }

        .signature-btn-secondary {
            background: #6c757d;
            color: white;
        }

        .signature-btn-secondary:hover {
            background: #5a6268;
        }

        .signature-btn-danger {
            background: #dc3545;
            color: white;
        }

        .signature-btn-danger:hover {
            background: #c82333;
        }

        .signature-display {
            margin-top: 15px;
            text-align: center;
        }

        .signature-display img {
            max-width: 200px;
            max-height: 100px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .signature-status {
            margin-top: 10px;
            padding: 8px;
            border-radius: 4px;
            text-align: center;
            font-weight: bold;
        }

        .signature-status.signed {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .signature-status.not-signed {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 12pt;
        }

        table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }

        table tr:first-child td {
            font-weight: bold;
            background-color: #f5f5f5;
        }

        @media print {
            table tr:first-child td {
                background-color: #f9f9f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">طباعة العقد</button>

    <div class="contract-container">
        <!-- Watermark Logo -->
        @php
            $logoPath = public_path('admin/images/logo.png');
            $logoUrl = file_exists($logoPath) ? asset('admin/images/logo.png') : asset('a.png');
        @endphp
        <img src="{{ $logoUrl }}" class="watermark" alt="">

        <!-- الصفحة الأولى -->
        <div class="page-1">
            <div class="header">
                <!-- Logo في أعلى الصفحة -->
                <div class="logo-container">
                    {{-- <img src="{{ $logoUrl }}" alt="شركة بلاتنيوم الشرقية"> --}}
                    <img src="{{ isset($settings) && !empty($settings->logo_header) ? showFile($settings->logo_header) : asset('admin/assets/img/company_logo.png') }}" alt="شركة بلاتنيوم الشرقية">
                </div>
                <h1>عقد</h1>
            </div>

            <div class="contract-info">
                <p style="text-align: center;">الحمد لله وحده وبعد ففي يوم الموافق {{ \Carbon\Carbon::parse($contract->contract_date ?? now())->format('Y/m/d') }} تم الاتفاق بمدينة الرياض بين كل من:</p>
            </div>

            <div class="parties-section">
                <div class="party">
                    <div class="party-title">1- شركة بلاتنيوم الشرقية للاستقدام</div>
                    <div>لاستقدام العمالة المنزلية ترخيص وزارة العمل رقم / {{ $contractSettings['license_number'] ?? '4001772' }} وتاريخ : {{ $contractSettings['license_date'] ?? '2022/10/13' }} م سجل تجاري رقم / {{ $contractSettings['commercial_register'] ?? '1010601958' }}</div>
                    <div>وتاريخ: {{ \Carbon\Carbon::parse($contractSettings['commercial_date'] ?? '1441/03/03')->format('Y/m/d') }} هـ صادر من الرياض</div>
                    <div>وعنوانه الرياض صندوق بريد: الرمز البريدي: مكتب: جوال: البريد الالكتروني</div>
                    <div>ويمثله في التوقيع من هذا العقد المدير بصفته المالك ويشار إليه فيما بعد بـ (الطرف الأول).</div>
                </div>

                <div class="party">
                    <div class="party-title">2- {{ $contract->user->name ?? 'Maram omar alzahrani' }}</div>
                    <div>الجنسية كمايا - بموجب هوية وطنية رقم {{ $contract->user->iqama_number ?? '11' }} وعنوانه جده - حي الصفا - جوهرة الشاكرين {{ $contract->user->address ?? '33' }} الرمز البريدي مكتب جوال</div>
                    <div>ويشار إليه فيما بعد بـ (الطرف الثاني) -------------- فاكس {{ $contractSettings['fax'] ?? '966550316770+' }}</div>
                </div>
            </div>

            <div class="contract-body">
                <p><strong>تمهيد:</strong></p>
                <p>حيث أن الطرف الأول مرخص ويسمح له بتقديم خدمات العمالة المنزلية ولديه الخبرات والإمكانيات اللازمة لتوفير خدمات العمالة المنزلية، وذلك وفق الأنظمة والقرارات والتعليمات المعمول بها في المملكة العربية السعودية وبما لا يتعارض مع أحكام الشريعة الإسلامية، وأحاجة الطرف الثاني لخدمات بعض العمالة المنزلية، فقد اتفق الطرفان وهما بكامل أهليتهما المعتبرة شرعا ونظاما على ما يلي:</p>
            </div>

            <div class="terms-section">
                @if(isset($termsPage1) && $termsPage1->count() > 0)
                    @php $currentSection = '' @endphp
                    @foreach($termsPage1 as $term)
                        @if($currentSection != $term->section_title)
                            <h3>{{ $term->section_title }}</h3>
                            @php $currentSection = $term->section_title @endphp
                        @endif

                        <div class="term-item">
                            @if($term->term_number)
                                <span class="term-number">{{ $term->term_number }}-</span>
                            @endif
                            {{ $term->term_content }}
                        </div>
                    @endforeach
                @else
                    <!-- البنود الافتراضية إذا لم توجد بنود ديناميكية -->
                    <h3>أولاً: موضوع العقد</h3>
                    <div class="term-item">
                        اتفق الطرفان على أن يقوم الطرف الأول بتوفير عاملة منزلية وتقديم خدماته للطرف الثاني: وفقا لما يكلفه به في حدود مجال عمله بمهنة عاملة تأجير، وبيانات العامل هي:
                    </div>
                @endif

                <table>
                    <tr>
                        <td><strong>الجنسية</strong></td>
                        <td><strong>رقم جواز السفر</strong></td>
                        <td><strong>المؤهلات والخبرات</strong></td>
                        <td><strong>ساعات العمل اليومية اليومية</strong></td>
                        <td><strong>مدة الخدمة المطلوبة</strong></td>
                        <td><strong>التكلفة الشهرية</strong></td>
                    </tr>
                    <tr>
                        <td>{{ $contract->maid->nationality->name ?? 'اثيوبيا' }}</td>
                        <td>{{ $contract->maid->passport_num ?? 'EP7081665' }}</td>
                        <td>--------------</td>
                        <td>--------------</td>
                        <td>--------------</td>
                        <td>لا يوجد</td>
                    </tr>
                </table>



            </div>
        </div>

        <!-- الصفحة الثانية -->
        <div class="page-2 page-break">
            @if(isset($termsPage2) && $termsPage2->count() > 0)
                @php $currentSection = '' @endphp
                @foreach($termsPage2 as $term)
                    @if($currentSection != $term->section_title)
                        <h3>{{ $term->section_title }}</h3>
                        @php $currentSection = $term->section_title @endphp
                    @endif

                    <div class="term-item">
                        @if($term->term_number)
                            <span class="term-number">{{ $term->term_number }}-</span>
                        @endif
                        {{ $term->term_content }}
                    </div>
                @endforeach
            @else
                <!-- البنود الافتراضية إذا لم توجد بنود ديناميكية -->
                <h3>ثانياً: مدة العقد</h3>
                <div class="term-item">
                    مدة العقد -------------- يوما على أن تبدأ من -------------- قابلة للتجديد لمدة أو مدد مماثلة بموجب أشعار مكتوب من الطرف الثاني للطرف الأول قبل نهاية
                </div>

                <h3>ثالثاً: قيمة العقد</h3>
                <div class="term-item">
                    <span class="term-number">1-</span>
                    يدفع الطرف الثاني للطرف الأول تكلفة مقابل تقديم خدمات العاملة المنزلية: وفقا للتكلفة الشهرية (------) المعلنة --- في تاريخ التعاقد: وفقا لما يلي:
                </div>
                <div class="term-item">
                    <span class="term-number">3-</span>
                    في حال تجديد هذا العقد، ومرافقة العامل على الاستمرار في العمل لدى الطرف الثاني، يدفع الطرف الثاني للطرف الأول تكلفة مقابل تقديم خدمات العامل المنزلي للمدة الجديدة تعادل التكلفة السابقة.
                </div>

                <h3>رابعا: التزامات الطرف الأول</h3>
                <div class="term-item">
                    <span class="term-number">1-</span>
                    تقديم خدمات العامل المذكور أعلاه للطرف الثاني.
                </div>
                <div class="term-item">
                    <span class="term-number">4-</span>
                    الحصول على موافقة خطية من العامل للعمل لدى الطرف الثاني ونقل خدماته آلية وذلك قبل نقل خدماته للطرف الثاني.
                </div>
            @endif

            <div class="term-item">
                <span class="term-number">5-</span>
                أذا كان العامل بمهنة (سائق) فيجب أن يحمل رخصة قيادة سعودية سارية المفعول.
            </div>

            <h3>خامساً: التزامات الطرف الثاني</h3>

            <div class="term-item">
                <span class="term-number">1-</span>
                سداد التكلفة المتفق عليها بالفقرة (1) من البند (ثالثا) من هذا العقد
            </div>

            <div class="term-item">
                <span class="term-number">2-</span>
                أن يعامل العامل بالاحترام اللائق، وأن يمتنع عن كل قول أو فعل يمس كرامته، ودينه
            </div>

            <div class="term-item">
                <span class="term-number">3-</span>
                تمكين العامل من الوصول إلى أقرب فرع بنك أو جهاز صراف آلي لاستلام مرتبه الشهري ومساعدته لتحويل أية مبالغ لذويه.
            </div>

            <div class="term-item">
                <span class="term-number">4-</span>
                أن يكون مسؤولا عن أي تجاوزات تلحق أضرار بالعامل في مواجهة الطرف الأول والعامل.
            </div>

            <div class="term-item">
                <span class="term-number">5-</span>
                إشعار الطرف الأول بأية مخالفات، أو أخطاء يرتكبها العامل لاتخاذ ما يلزم من إجراءات بحقه.
            </div>

            <div class="term-item">
                <span class="term-number">6-</span>
                عدم تشغيل العامل في أعمال تختلف عن المهنة المرخص له بها سواء بطريقة مباشره، أو غير مباشرة.
            </div>

            <div class="term-item">
                <span class="term-number">7-</span>
                يشترط أذا كانت المهنة العامل (سائق) أن تكون السيارة المسلمة له مؤمن عليها تأمين ضد الغير على الأقل.
            </div>

            <div class="term-item">
                <span class="term-number">8-</span>
                توفير السكن المناسب للعامل.
            </div>

            <div class="term-item">
                <span class="term-number">9-</span>
                أن يؤمن الطرف الثاني للعامل ثلاث وجبات غذاء يوميا.
            </div>

            <div class="term-item">
                <span class="term-number">10-</span>
                إبلاغ الطرف الأول كتابيا في حال تغيب العامل عن العمل أو رفضه العمل خلال (24) ساعة من وقت التغيب أو الرفض العملة مع تسليم جميع مختلفات العامل للطرف الأول، وكذلك في حال عودة العامل يشعر الطرف الثاني الطرف الأول كتابيا بتاريخ العودة محددا ساعة وتاريخ عودة العامل.
            </div>


        </div>

        <!-- الصفحة الثالثة -->
        <div class="page-3 page-break">
            <div class="term-item">
                <span class="term-number">11-</span>
                أعادة العامل عند انتهاء مدة الاختبار أو التقييم خدماته للطرف الأول، وفي حال عدم التزام الطرف الثاني بذلك فأنه يعوض الطرف الأول بما يعادل (150) مائة وخمسون ريال سعودي عن كل يوم تأخير مع قيمة التكلفة اليومية للعامل المقدمة خدماته.
            </div>

            <div class="term-item">
                <span class="term-number">12-</span>
                في حالة إعادة العاملة خلال مدة العقد يتم احتساب معادلة الخصم (150) مائة وخمسون ريال سعودي عن كل يوم عمل به لدى الطرف الثاني ويتم إعادة المبلغ المتبقي.
            </div>
            <h3 >سادسا: أحكام عامه</h3>

            <div class="term-item">
                <span class="term-number">1-</span>
                لايجوز للطرف الثاني القيام بإعادة تقديم خدمات العامل لأي طرف آخر سواء بطريقة مباشره أو غير مباشرة
            </div>

            <div class="term-item">
                <span class="term-number">2-</span>
                يرتبط العامل مع الطرف الأول بعقد عمل منتهي، ينقل الكفالة للطرف الأول بعد انتهاء مدة الاختبار المتفق عليها في العقد، ويعمل تحت إشرافه وإدارته وفقا لنظام العمل.
            </div>

            <div class="term-item">
                <span class="term-number">3-</span>
                يخضع العامل لتعليمات الطرف الثاني، وعليه الحفاظ على أسرار أعماله وأسرار أسرته ويكون للطرف الثاني حق توجيهه حسب مقتضيات العمل أدبية، ووفقا للأنظمة السارية.
            </div>

            <div class="term-item">
                <span class="term-number">4-</span>
                أي شروط أضافية تتعارض مع نظام العمل أو لائحته أو القرارات الصادرة تنفيذا له تعتبر باطله
            </div>

            <h3>سابعا: الخلافات والمنازعات</h3>

            <div class="term-item">
                في حاله حدوث خلاف بين الطرفين يتم اللجوء للمكتب المتعاقد فيه المختص لمحاولة التوصل لحل ودي بين الطرفين قبل النظر فيه من المحكمة المختصة.
            </div>

            <h3>ثامنا: الأخطارات والإشعارات</h3>

            <div class="term-item">
                تتم الأخطارات والإشعارات بين الطرفين كتابة عن طريق البريد المسجل أو البريد الممتاز أو البريد الالكتروني أو عن طريق الرسائل النصية عن طريق الجوال أو الواتساب. وتوجه الأخطارات على معلومات التواصل الموضح لكل من الطرفين في صدر هذا العقد ويلتزم كل طرف بإشعار الطرف الآخر خطيا في حال تغيير لمعلومات التواصل الخاص به ولا اعتبرت معلومات التواصل المدون أعلاه هو المعمول به نظاما دون غيره.
            </div>

            <!-- قسم التوقيعات مع ميزة التوقيع -->
            <div class="signatures-section">
                <div style="margin-top: 40px;">
                    <div>اسم الطرف الأول: شركة بلاتنيوم الشرقية للاستقدام</div>
                    <div>اسم الطرف الثاني: {{ $contract->user->name ?? 'Maram omar alzahrani' }}</div>

                    <!-- قسم التوقيع -->
                    <div class="signature-container no-print">
                        <div class="signature-title">توقيع الطرف الثاني ({{ $contract->user->name ?? 'العميل' }})</div>

                        <!-- حالة التوقيع -->
                        <div class="signature-status {{ $contract->signature ? 'signed' : 'not-signed' }}">
                            @if($contract->signature)
                                ✅ تم التوقيع على العقد
                            @else
                                ❌ لم يتم التوقيع بعد
                            @endif
                        </div>

                        <!-- عرض التوقيع المحفوظ -->
                        @if($contract->signature)
                            <div class="signature-display">
                                <img src="{{ asset('storage/signatures/' . $contract->signature) }}" alt="التوقيع المحفوظ">
                            </div>
                        @else
                            <!-- Canvas للتوقيع -->
                            <div class="signature-canvas-container">
                                <canvas id="signatureCanvas" width="400" height="150"></canvas>
                            </div>

                            <!-- أزرار التحكم -->
                            <div class="signature-controls">
                                <button type="button" class="signature-btn signature-btn-primary" onclick="saveSignature()">
                                    حفظ التوقيع
                                </button>
                                <button type="button" class="signature-btn signature-btn-secondary" onclick="clearSignature()">
                                    مسح التوقيع
                                </button>
                                <button type="button" class="signature-btn signature-btn-danger" onclick="downloadSignature()">
                                    تحميل التوقيع
                                </button>
                            </div>
                        @endif
                    </div>

                    <!-- التوقيعات في الطباعة -->
                    <div style="margin-top: 30px;">
                        <div>توقيع الطرف الأول .................................................................. </div>
                        <div style="margin-top: 15px;">
                            توقيع الطرف الثاني:
                            @if($contract->signature)
                                <img src="{{ asset('storage/signatures/' . $contract->signature) }}" style="max-width: 150px; max-height: 60px; vertical-align: middle;">
                            @else
                                ..................................................................
                            @endif
                        </div>
                        <div style="margin-top: 15px;">الختم .................................................................. </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // متغيرات التوقيع
        let canvas = document.getElementById('signatureCanvas');
        let ctx = canvas.getContext('2d');
        let isDrawing = false;
        let lastX = 0;
        let lastY = 0;

        // إعداد Canvas
        function setupCanvas() {
            if (!canvas) return;

            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';

            // إضافة نص توجيهي
            ctx.fillStyle = '#999';
            ctx.font = '16px Arial';
            ctx.textAlign = 'center';
            ctx.fillText('اضغط هنا للتوقيع', canvas.width / 2, canvas.height / 2);
        }

        // بدء الرسم
        function startDrawing(e) {
            isDrawing = true;
            [lastX, lastY] = [e.offsetX, e.offsetY];
        }

        // الرسم
        function draw(e) {
            if (!isDrawing) return;

            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(e.offsetX, e.offsetY);
            ctx.stroke();

            [lastX, lastY] = [e.offsetX, e.offsetY];
        }

        // إيقاف الرسم
        function stopDrawing() {
            isDrawing = false;
        }

        // مسح التوقيع
        function clearSignature() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            setupCanvas();
        }

        // حفظ التوقيع
        function saveSignature() {
            // التحقق من وجود توقيع
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const hasSignature = imageData.data.some(channel => channel !== 0);

            if (!hasSignature) {
                Swal.fire({
                    title: 'تنبيه!',
                    text: 'يرجى التوقيع أولاً قبل الحفظ',
                    icon: 'warning',
                    confirmButtonText: 'حسناً'
                });
                return;
            }

            // تحويل Canvas إلى صورة
            const signatureImage = canvas.toDataURL('image/png');

            // إرسال التوقيع للخادم
            fetch('{{ route("admin.contracts.save-signature", $contract->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    signature: signatureImage
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'تم الحفظ بنجاح!',
                        text: 'تم حفظ التوقيع على العقد',
                        icon: 'success',
                        confirmButtonText: 'حسناً'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'خطأ!',
                        text: data.message || 'حدث خطأ أثناء حفظ التوقيع',
                        icon: 'error',
                        confirmButtonText: 'حسناً'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'خطأ!',
                    text: 'حدث خطأ أثناء حفظ التوقيع',
                    icon: 'error',
                    confirmButtonText: 'حسناً'
                });
            });
        }

        // تحميل التوقيع
        function downloadSignature() {
            const link = document.createElement('a');
            link.download = 'توقيع_العقد_{{ $contract->contract_number }}.png';
            link.href = canvas.toDataURL();
            link.click();
        }

        // إعداد الأحداث
        if (canvas) {
            setupCanvas();
            canvas.addEventListener('mousedown', startDrawing);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', stopDrawing);
            canvas.addEventListener('mouseout', stopDrawing);

            // دعم اللمس للأجهزة المحمولة
            canvas.addEventListener('touchstart', (e) => {
                e.preventDefault();
                const touch = e.touches[0];
                const rect = canvas.getBoundingClientRect();
                const x = touch.clientX - rect.left;
                const y = touch.clientY - rect.top;
                startDrawing({ offsetX: x, offsetY: y });
            });

            canvas.addEventListener('touchmove', (e) => {
                e.preventDefault();
                const touch = e.touches[0];
                const rect = canvas.getBoundingClientRect();
                const x = touch.clientX - rect.left;
                const y = touch.clientY - rect.top;
                draw({ offsetX: x, offsetY: y });
            });

            canvas.addEventListener('touchend', stopDrawing);
        }
    </script>
</body>
</html>
