<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $feedbackBrandName = isset($settings) ? optional($settings->translate(app()->getLocale()))->website_name : null;
        $feedbackBrandName = $feedbackBrandName ?: 'بلاتنيوم الشرقية للاستقدام';
    @endphp
    <title>شكراً لك - {{ $feedbackBrandName }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .success-container {
            max-width: 500px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 50px;
            text-align: center;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
            }
            70% {
                box-shadow: 0 0 0 20px rgba(40, 167, 69, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
            }
        }

        .success-icon i {
            font-size: 50px;
            color: white;
        }

        h1 {
            color: #333;
            font-size: 32px;
            margin-bottom: 20px;
        }

        .message {
            color: #666;
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .rating-summary {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .rating-summary h4 {
            color: #495057;
            font-size: 18px;
            margin-bottom: 15px;
        }

        .rating-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .rating-item:last-child {
            border-bottom: none;
        }

        .rating-label {
            color: #6c757d;
            font-size: 14px;
        }

        .rating-value {
            color: #333;
            font-weight: 600;
        }

        .stars {
            color: #ffc107;
        }

        .btn-home {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .contact-info {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #e9ecef;
            color: #6c757d;
            font-size: 14px;
        }

        .contact-info i {
            color: #667eea;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>

        <h1>شكراً لك!</h1>

        <div class="message">
            نقدر وقتك في تقييم خدماتنا.<br>
            رأيك يساعدنا على تحسين جودة الخدمة المقدمة لعملائنا الكرام.
        </div>

        @if($feedback)
        <div class="rating-summary">
            <h4>ملخص تقييمك</h4>

            <div class="rating-item">
                <span class="rating-label">التقييم العام:</span>
                <span class="rating-value">{{ $feedback->overall_rating_text }}</span>
            </div>

            @if($feedback->response_speed)
            <div class="rating-item">
                <span class="rating-label">سرعة الاستجابة:</span>
                <span class="rating-value stars">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= $feedback->response_speed)
                            <i class="fas fa-star"></i>
                        @else
                            <i class="far fa-star"></i>
                        @endif
                    @endfor
                </span>
            </div>
            @endif

            @if($feedback->solution_quality)
            <div class="rating-item">
                <span class="rating-label">جودة الحل:</span>
                <span class="rating-value stars">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= $feedback->solution_quality)
                            <i class="fas fa-star"></i>
                        @else
                            <i class="far fa-star"></i>
                        @endif
                    @endfor
                </span>
            </div>
            @endif

            @if($feedback->would_recommend)
            <div class="rating-item">
                <span class="rating-label">التوصية بخدماتنا:</span>
                <span class="rating-value">
                    @if($feedback->would_recommend == 'yes')
                        <i class="fas fa-thumbs-up text-success"></i> نعم
                    @elseif($feedback->would_recommend == 'maybe')
                        <i class="fas fa-question text-warning"></i> ربما
                    @else
                        <i class="fas fa-thumbs-down text-danger"></i> لا
                    @endif
                </span>
            </div>
            @endif
        </div>
        @endif

        <a href="https://ussusalenjaz.sa" class="btn-home">
            <i class="fas fa-home me-2"></i>
            العودة للموقع الرئيسي
        </a>

        <div class="contact-info">
            <p>إذا كان لديك أي استفسارات إضافية، لا تتردد في التواصل معنا</p>
            <p>
                <i class="fas fa-phone"></i> 920033477
                <span class="mx-2">|</span>
                <i class="fas fa-envelope"></i> info@ussusalenjaz.sa
            </p>
        </div>
    </div>
</body>
</html>
