<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نتيجة الدفع</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <style>
        body { background: #f4f8fc; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .result-card { max-width: 420px; width: 100%; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,.1); }
        .icon-circle { width: 72px; height: 72px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1rem; }
    </style>
</head>
<body>
<div class="card result-card p-4 text-center">
    @if($status === 'success')
        <div class="icon-circle bg-success text-white">✓</div>
        <h4 class="text-success">تم الدفع بنجاح</h4>
    @elseif($status === 'failed')
        <div class="icon-circle bg-danger text-white">✕</div>
        <h4 class="text-danger">فشل الدفع</h4>
    @elseif($status === 'pending')
        <div class="icon-circle bg-warning text-dark">⏳</div>
        <h4 class="text-warning">جارٍ المعالجة</h4>
    @else
        <div class="icon-circle bg-secondary text-white">!</div>
        <h4 class="text-secondary">حدث خطأ</h4>
    @endif

    <p class="text-muted mt-2">{{ $message }}</p>
    <small class="text-muted d-block mt-3">يمكنك إغلاق هذه الصفحة.</small>
</div>
</body>
</html>
