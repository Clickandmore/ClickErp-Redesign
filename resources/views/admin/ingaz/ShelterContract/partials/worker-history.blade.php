<div class="info-grid worker-summary">
    <section class="info-card"><span class="info-label">تاريخ تسجيل العاملة</span><strong class="highlight">{{ $contract->maid?->created_at?->format('Y-m-d') ?? 'غير مسجل' }}</strong></section>
    <section class="info-card"><span class="info-label">التجارب الفاشلة لنقل الخدمات</span><strong class="text-danger">{{ $workerHistory['failed_trials'] }}</strong><small class="text-muted">بحسب طلبات نقل الخدمات المسجلة بالفشل، دون احتساب الطلبات المرفوضة أو الإرجاع كفشل تجربة.</small></section>
    <section class="info-card"><span class="info-label">طلبات نقل الخدمات</span><strong class="highlight">{{ $workerHistory['transfers']->count() }}</strong></section>
    <section class="info-card"><span class="info-label">الشكاوى المفتوحة / الإجمالي</span><strong class="highlight">{{ $workerHistory['open_complaints'] }} / {{ $workerHistory['complaints']->count() }}</strong></section>
</div>
<nav class="d-flex flex-wrap gap-2 mt-3" aria-label="أقسام ملف العاملة">
    <a class="btn btn-outline-primary" href="#worker-details">بيانات العاملة والسكن</a>
    <a class="btn btn-outline-primary" href="#worker-trials">التجارب ونقل الخدمات</a>
    <a class="btn btn-outline-primary" href="#worker-complaints">الشكاوى</a>
    <a class="btn btn-outline-primary" href="#worker-timeline">سجل الحركة</a>
</nav>
