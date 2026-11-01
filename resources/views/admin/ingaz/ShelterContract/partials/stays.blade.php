<section class="info-card mt-3 mb-3" id="shelter-stays">
    <div class="section-title"><i class="fas fa-calendar-alt"></i> مدد الإقامة في الإيواء</div>
    <p>إجمالي أيام الإقامة المسجلة: <strong>{{ $shelterStays['total_days'] }} يوم</strong></p>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead><tr><th>تاريخ الدخول</th><th>تاريخ الخروج</th><th>مدة الإقامة</th><th>الحالة</th></tr></thead>
            <tbody>
            @forelse($shelterStays['periods'] as $stay)
                <tr>
                    <td>{{ $stay['entry']?->format('Y-m-d') ?? 'غير مسجل' }}</td>
                    <td>{{ $stay['exit']?->format('Y-m-d') ?? ($stay['ongoing'] ? 'حتى اليوم' : 'غير مسجل') }}</td>
                    <td>{{ $stay['days'] !== null ? $stay['days'].' يوم' : 'غير محددة' }}</td>
                    <td>{{ $stay['ongoing'] ? 'إقامة حالية' : 'إقامة سابقة' }}{{ $stay['estimated'] ? ' — بيانات قديمة تقديرية' : '' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted">لا توجد مدد إقامة مسجلة.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <small class="text-muted">تُحسب الأيام بفرق التواريخ دون احتساب يوم الخروج، وتشمل الإقامة الحالية حتى اليوم.</small>
    @if($shelterStays['incomplete'])
        <p class="text-warning mt-2">السجل القديم غير مكتمل؛ الفترات التقديرية تعتمد على آخر تاريخ دخول متاح، وقد لا تشمل جميع حركات الخروج والعودة السابقة.</p>
    @endif
</section>
