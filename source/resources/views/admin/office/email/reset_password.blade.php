<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إعادة تعيين كلمة المرور</title>
    <style>
        /* يفضل استخدام CSS داخل الـ style وداخل عناصر inline لأغلب العملاء */
        body {
            margin: 0;
            padding: 0;
            background-color: #f6f8fa;
            font-family: 'Tahoma', Arial, sans-serif;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #4A90E2, #357ABD);
            padding: 25px;
            text-align: center;
            color: #fff;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
            color: #333;
            line-height: 1.8;
            text-align: center;
        }
        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 14px 28px;
            background-color: #4A90E2;
            color: #fff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }
        .btn:hover {
            background-color: #357ABD;
        }
        .footer {
            background: #f0f0f0;
            padding: 15px;
            text-align: center;
            font-size: 13px;
            color: #777;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header">
        <h1>إعادة تعيين كلمة المرور</h1>
    </div>

    <!-- Content -->
    <div class="content">
        <p>مرحبًا،</p>
        <p>تلقينا طلبًا لإعادة تعيين كلمة المرور الخاصة بحسابك. اضغط على الزر أدناه للمتابعة:</p>

        <a href="{{ $resetUrl }}" class="btn">إعادة تعيين كلمة المرور</a>

    </div>

    <!-- Footer -->
    <div class="footer">
        &copy; {{ date('Y') }} {{ config('app.name') }}. جميع الحقوق محفوظة.
    </div>
</div>

</body>
</html>
