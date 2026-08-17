<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $feedbackBrandName = isset($settings) ? optional($settings->translate(app()->getLocale()))->website_name : null;
        $feedbackBrandName = $feedbackBrandName ?: documentSetting('website_name', config('app.name'));
    @endphp
    <title>تقييم خدمة العملاء - {{ $feedbackBrandName }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .feedback-container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .feedback-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .feedback-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .feedback-body {
            padding: 30px;
        }

        .rating-section {
            margin-bottom: 30px;
        }

        .rating-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }

        .overall-rating {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 30px;
        }

        .rating-option {
            flex: 1;
            min-width: 100px;
            text-align: center;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .rating-option:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .rating-option.selected {
            border-color: #667eea;
            background: #f0f4ff;
        }

        .rating-option i {
            font-size: 30px;
            margin-bottom: 5px;
            display: block;
        }

        .rating-option.very-satisfied i { color: #28a745; }
        .rating-option.satisfied i { color: #17a2b8; }
        .rating-option.neutral i { color: #ffc107; }
        .rating-option.dissatisfied i { color: #fd7e14; }
        .rating-option.very-dissatisfied i { color: #dc3545; }

        .star-rating {
            display: flex;
            gap: 10px;
            font-size: 25px;
            margin-bottom: 15px;
        }

        .star-rating i {
            color: #ddd;
            cursor: pointer;
            transition: all 0.2s;
        }

        .star-rating i:hover,
        .star-rating i.active {
            color: #ffc107;
            transform: scale(1.2);
        }

        .recommend-section {
            display: flex;
            justify-content: space-around;
            gap: 10px;
            margin-bottom: 20px;
        }

        .recommend-option {
            flex: 1;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .recommend-option:hover {
            border-color: #667eea;
        }

        .recommend-option.selected {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 50px;
            font-size: 18px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .success-message {
            text-align: center;
            padding: 50px;
        }

        .success-message i {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
        }

        .expired-message {
            text-align: center;
            padding: 50px;
        }

        .expired-message i {
            font-size: 80px;
            color: #dc3545;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="feedback-container">
        @if($feedback->submitted_at)
            <!-- رسالة شكر إذا تم التقييم مسبقاً -->
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <h2>شكراً لك!</h2>
                <p>لقد قمت بتقييم خدمتنا مسبقاً</p>
                <p>نقدر وقتك وملاحظاتك القيمة</p>
            </div>
        @elseif(!$feedback->canSubmitFeedback())
            <!-- رسالة انتهاء الصلاحية -->
            <div class="expired-message">
                <i class="fas fa-clock"></i>
                <h2>انتهت صلاحية التقييم</h2>
                <p>عذراً، لقد انتهت المدة المتاحة للتقييم</p>
                <p>يمكنك التواصل معنا مباشرة إذا كان لديك أي ملاحظات</p>
            </div>
        @else
            <!-- نموذج التقييم -->
            <div class="feedback-header">
                <h1>تقييم خدمة العملاء</h1>
                <p>رأيك يهمنا لتحسين خدماتنا</p>
                <p>الشكوى رقم: #{{ str_pad($feedback->complaint_id, 6, '0', STR_PAD_LEFT) }}</p>
            </div>

            <div class="feedback-body">
                <form id="feedbackForm" action="{{ route('complaint.feedback.submit') }}" method="POST">
                    @csrf
                    <input type="hidden" name="token" value="{{ $feedback->token }}">

                    <!-- التقييم العام -->
                    <div class="rating-section">
                        <div class="rating-title">كيف تقيم تجربتك بشكل عام؟ *</div>
                        <div class="overall-rating">
                            <div class="rating-option very-satisfied" data-value="very_satisfied">
                                <i class="fas fa-laugh-beam"></i>
                                <span>راضٍ جداً</span>
                            </div>
                            <div class="rating-option satisfied" data-value="satisfied">
                                <i class="fas fa-smile"></i>
                                <span>راضٍ</span>
                            </div>
                            <div class="rating-option neutral" data-value="neutral">
                                <i class="fas fa-meh"></i>
                                <span>محايد</span>
                            </div>
                            <div class="rating-option dissatisfied" data-value="dissatisfied">
                                <i class="fas fa-frown"></i>
                                <span>غير راضٍ</span>
                            </div>
                            <div class="rating-option very-dissatisfied" data-value="very_dissatisfied">
                                <i class="fas fa-angry"></i>
                                <span>غير راضٍ تماماً</span>
                            </div>
                        </div>
                        <input type="hidden" name="overall_rating" id="overall_rating" required>
                    </div>

                    <!-- التقييمات التفصيلية -->
                    <div class="rating-section">
                        <div class="rating-title">قيّم الجوانب التالية:</div>

                        <div class="mb-3">
                            <label>سرعة الاستجابة</label>
                            <div class="star-rating" data-rating="response_speed">
                                <i class="fas fa-star" data-value="1"></i>
                                <i class="fas fa-star" data-value="2"></i>
                                <i class="fas fa-star" data-value="3"></i>
                                <i class="fas fa-star" data-value="4"></i>
                                <i class="fas fa-star" data-value="5"></i>
                            </div>
                            <input type="hidden" name="response_speed" id="response_speed">
                        </div>

                        <div class="mb-3">
                            <label>جودة الحل المقدم</label>
                            <div class="star-rating" data-rating="solution_quality">
                                <i class="fas fa-star" data-value="1"></i>
                                <i class="fas fa-star" data-value="2"></i>
                                <i class="fas fa-star" data-value="3"></i>
                                <i class="fas fa-star" data-value="4"></i>
                                <i class="fas fa-star" data-value="5"></i>
                            </div>
                            <input type="hidden" name="solution_quality" id="solution_quality">
                        </div>

                        <div class="mb-3">
                            <label>احترافية الموظف</label>
                            <div class="star-rating" data-rating="employee_professionalism">
                                <i class="fas fa-star" data-value="1"></i>
                                <i class="fas fa-star" data-value="2"></i>
                                <i class="fas fa-star" data-value="3"></i>
                                <i class="fas fa-star" data-value="4"></i>
                                <i class="fas fa-star" data-value="5"></i>
                            </div>
                            <input type="hidden" name="employee_professionalism" id="employee_professionalism">
                        </div>

                        <div class="mb-3">
                            <label>جودة التواصل</label>
                            <div class="star-rating" data-rating="communication_quality">
                                <i class="fas fa-star" data-value="1"></i>
                                <i class="fas fa-star" data-value="2"></i>
                                <i class="fas fa-star" data-value="3"></i>
                                <i class="fas fa-star" data-value="4"></i>
                                <i class="fas fa-star" data-value="5"></i>
                            </div>
                            <input type="hidden" name="communication_quality" id="communication_quality">
                        </div>
                    </div>

                    <!-- هل تنصح بخدماتنا -->
                    <div class="rating-section">
                        <div class="rating-title">هل تنصح بخدماتنا للآخرين؟</div>
                        <div class="recommend-section">
                            <div class="recommend-option" data-value="yes">
                                <i class="fas fa-thumbs-up"></i> نعم
                            </div>
                            <div class="recommend-option" data-value="maybe">
                                <i class="fas fa-question"></i> ربما
                            </div>
                            <div class="recommend-option" data-value="no">
                                <i class="fas fa-thumbs-down"></i> لا
                            </div>
                        </div>
                        <input type="hidden" name="would_recommend" id="would_recommend">
                    </div>

                    <!-- ملاحظات إضافية -->
                    <div class="rating-section">
                        <div class="rating-title">ملاحظات إضافية (اختياري)</div>
                        <textarea name="comments" class="form-control" rows="4"
                                  placeholder="شاركنا ملاحظاتك أو اقتراحاتك لتحسين خدماتنا..."></textarea>
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane me-2"></i>
                        إرسال التقييم
                    </button>
                </form>
            </div>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // التقييم العام
            $('.rating-option').click(function() {
                $('.rating-option').removeClass('selected');
                $(this).addClass('selected');
                $('#overall_rating').val($(this).data('value'));
            });

            // التقييم بالنجوم
            $('.star-rating i').click(function() {
                var rating = $(this).data('value');
                var container = $(this).parent();
                var inputId = container.data('rating');

                container.find('i').removeClass('active');
                container.find('i').each(function(index) {
                    if (index < rating) {
                        $(this).addClass('active');
                    }
                });

                $('#' + inputId).val(rating);
            });

            // التوصية
            $('.recommend-option').click(function() {
                $('.recommend-option').removeClass('selected');
                $(this).addClass('selected');
                $('#would_recommend').val($(this).data('value'));
            });

            // التحقق من النموذج
            $('#feedbackForm').submit(function(e) {
                if (!$('#overall_rating').val()) {
                    e.preventDefault();
                    alert('الرجاء اختيار التقييم العام');
                    return false;
                }
            });
        });
    </script>
</body>
</html>
