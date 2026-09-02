@extends('admin.layout.indexs.index')

@section('page-title', 'مراجعة واعتماد العمولة')

@section('page-links')
    <li class="breadcrumb-item"><a href="{{ route('commissions.marketers.index') }}">نظام العمولات</a></li>
    <li class="breadcrumb-item"><a href="{{ route('commissions.marketers.show', $admin->id) }}">{{ $admin->name }}</a></li>
    <li class="breadcrumb-item active">تحديد العمولة</li>
@endsection

@section('content')
<style>
    .step-pill { padding: .55rem 1rem; border-radius: 999px; background: #e2e8f0; color: #64748b; }
    .step-pill.active { background: #075985; color: #fff; }
    .review-card { border: 0; border-radius: 14px; box-shadow: 0 4px 18px rgba(15,23,42,.07); }
    .commission-total { background: linear-gradient(135deg, #ecfdf5, #d1fae5); border: 1px solid #86efac; }
</style>

<div class="d-flex flex-wrap gap-2 mb-4">
    <span class="step-pill"><strong>1</strong> اختيار العمليات</span>
    <span class="step-pill active"><strong>2</strong> تحديد ومراجعة العمولة</span>
    <span class="step-pill"><strong>3</strong> إضافة إلى الرصيد</span>
    <span class="step-pill"><strong>4</strong> سند الصرف</span>
</div>

@php
    $bulkDefault = $preview['items'][0] ?? null;
    $bulkDefaultValue = ($bulkDefault['method'] ?? null) === 'fixed'
        ? ($bulkDefault['rate_or_amount'] ?? 0)
        : 0;
@endphp
<form method="POST" action="{{ route('commissions.marketers.approve', $admin->id) }}" id="approveForm" autocomplete="off">
@csrf
<div id="approvalError" class="alert alert-danger d-none mb-3" role="alert"></div>
<div class="card review-card mb-3">
    <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div><h5 class="mb-1 fw-bold">تفاصيل عمولة {{ $admin->name }}</h5><div class="text-muted small">يمكن تعديل طريقة وقيمة كل عملية بصورة مستقلة.</div></div>
        <div class="d-flex flex-wrap align-items-end gap-2 bg-light rounded p-2">
            <div><label class="form-label small mb-1">تطبيق موحّد</label><select id="bulkMethod" class="form-select form-select-sm" autocomplete="off"><option value="fixed" selected>مبلغ ثابت</option><option value="percentage">نسبة %</option></select></div>
            <div><label class="form-label small mb-1">القيمة</label><input id="bulkValue" type="number" min="0" step=".01" class="form-control form-control-sm" value="{{ $bulkDefaultValue }}" style="width:120px"></div>
            <button type="button" id="applyBulk" class="btn btn-sm btn-outline-primary">تطبيق على الكل</button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr>
                <th>#</th><th>المصدر / الرقم</th><th>رقم الطلب</th><th>الخدمة</th><th>العميل</th><th>الحالة</th><th>قيمة الأساس</th><th style="width:150px">الطريقة</th><th style="width:140px">النسبة / المبلغ</th><th>العمولة</th>
            </tr></thead>
            <tbody>
            @foreach($preview['items'] as $i => $item)
                @php $op = $item['operation']; @endphp
                <tr class="commission-row" data-base="{{ $item['base_amount'] }}">
                    <td>{{ $i + 1 }}</td>
                    <td><strong>{{ $op['source_label'] }}</strong><div class="small text-muted">#{{ $op['reference'] }}</div></td>
                    <td>{{ $op['order_reference'] }}</td>
                    <td>{{ \App\Models\commissions\CommissionSetting::serviceTypeOptions()[$item['service_type']] ?? $item['service_type'] }}</td>
                    <td>{{ $op['client_name'] }}</td><td>{{ $op['status'] }}</td>
                    <td class="text-end base-cell">{{ number_format($item['base_amount'], 2) }}</td>
                    <td>
                        <input type="hidden" name="operations[]" value="{{ $op['key'] }}">
                        <select name="methods[]" class="form-select form-select-sm method-input">
                            <option value="percentage" @selected($item['method'] === 'percentage')>نسبة مئوية</option>
                            <option value="fixed" @selected($item['method'] === 'fixed')>مبلغ ثابت</option>
                        </select>
                    </td>
                    <td><input type="number" name="values[]" class="form-control form-control-sm value-input" min="0" step=".01" value="{{ $item['rate_or_amount'] }}" required></td>
                    <td class="text-end fw-bold text-success"><span class="row-commission">{{ number_format($item['commission_amount'], 2) }}</span> ر.س</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot class="table-light"><tr><td colspan="6" class="text-end fw-bold">الإجمالي</td><td class="text-end fw-bold">{{ number_format($preview['total_base'], 2) }}</td><td colspan="2"></td><td class="text-end fw-bold text-success"><span id="tableTotal">{{ number_format($preview['total_commission'], 2) }}</span> ر.س</td></tr></tfoot>
        </table>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card review-card h-100"><div class="card-body">
            <label class="form-label fw-bold">ملاحظات دفعة العمولة</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="ملاحظات اختيارية تظهر في سجل الدفعة...">{{ old('notes') }}</textarea>
            <div class="alert alert-info small mt-3 mb-0"><i class="fa fa-info-circle me-1"></i> بعد الاعتماد تُقفل العمليات، ويُضاف الإجمالي إلى رصيد الموظف، ويُنشأ قيد الاستحقاق تلقائيًا على حسابات عمولات المسوقين.</div>
        </div></div>
    </div>
    <div class="col-lg-4">
        <div class="card review-card commission-total h-100"><div class="card-body text-center d-flex flex-column justify-content-center">
            <div class="text-muted">إجمالي العمولة المعتمدة</div>
            <div class="display-6 fw-bold text-success"><span id="grandTotal">{{ number_format($preview['total_commission'], 2) }}</span> <small class="fs-6">ر.س</small></div>
            <div class="small text-muted mb-3">{{ $preview['total_requests'] }} عملية محددة</div>
            <button class="btn btn-success fw-bold"><i class="fa fa-check-circle me-1"></i> اعتماد وإضافة إلى الرصيد</button>
            <a href="{{ route('commissions.marketers.show', $admin->id) }}" class="btn btn-link text-secondary">العودة وتعديل الاختيار</a>
        </div></div>
    </div>
</div>
</form>
@endsection

@section('js')
<script>
$(function () {
    // Some browsers restore the previous select value when returning to the page.
    $('#bulkMethod').val('fixed');
    $('#bulkValue').val(@json($bulkDefaultValue));
    $('.method-input').val('fixed');

    function recalculate() {
        let total = 0;
        $('.commission-row').each(function () {
            const base = Number($(this).data('base') || 0);
            const method = $(this).find('.method-input').val();
            const value = Number($(this).find('.value-input').val() || 0);
            const commission = method === 'percentage' ? Math.round(base * value) / 100 : Math.round(value * 100) / 100;
            $(this).find('.row-commission').text(commission.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}));
            total += commission;
        });
        const formatted = total.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
        $('#tableTotal, #grandTotal').text(formatted);
        return total;
    }
    $(document).on('input change', '.method-input, .value-input', recalculate);
    $('#applyBulk').on('click', function () {
        $('.method-input').val($('#bulkMethod').val());
        $('.value-input').val($('#bulkValue').val());
        recalculate();
    });
    $('#approveForm').on('submit', function (event) {
        event.preventDefault();
        const form = this;
        const $button = $(form).find('button.btn-success').first();
        const $error = $('#approvalError');
        $error.addClass('d-none').empty();

        let valid = true;
        let hasHighPercentage = false;
        $('.commission-row').each(function () {
            const method = $(this).find('.method-input').val();
            const value = Number($(this).find('.value-input').val());
            if (method === 'percentage' && value > 100) valid = false;
            if (method === 'percentage' && value >= 50) hasHighPercentage = true;
        });
        if (!valid) {
            event.preventDefault();
            alert('نسبة العمولة لا يمكن أن تتجاوز 100%.');
            return;
        }

        const total = recalculate();
        const formatted = total.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
        const warning = hasHighPercentage
            ? '\n\nتنبيه شديد: توجد نسبة عمولة 50% أو أكثر. تأكد أنك لم تقصد «مبلغًا ثابتًا».'
            : '';

        if (!confirm(`سيتم اعتماد وإضافة ${formatted} ر.س إلى رصيد الموظف وإنشاء قيد الاستحقاق.${warning}\n\nهل تريد المتابعة؟`)) {
            event.preventDefault();
            return;
        }

        const originalButtonHtml = $button.html();
        $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> جارٍ الاعتماد...');

        $.ajax({
            url: form.action,
            method: 'POST',
            data: $(form).serialize(),
            headers: { 'Accept': 'application/json' },
        }).done(function (response) {
            window.location.href = response.redirect;
        }).fail(function (xhr) {
            const errors = xhr.responseJSON && xhr.responseJSON.errors
                ? Object.values(xhr.responseJSON.errors).flat()
                : [];
            const messages = errors.length
                ? errors
                : [(xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'تعذر اعتماد العمولة بسبب خطأ غير متوقع. حاول مرة أخرى أو تواصل مع الدعم الفني.')];

            $error.empty().append($('<strong>').text('لم يتم اعتماد العمولة:'));
            messages.forEach(function (message) {
                $error.append($('<div>').text(message));
            });
            $error.removeClass('d-none');
            window.scrollTo({ top: Math.max(0, $error.offset().top - 120), behavior: 'smooth' });
        }).always(function () {
            $button.prop('disabled', false).html(originalButtonHtml);
        });
    });

    // قد يعيد المتصفح قيم الحقول من محاولة سابقة؛ لذلك يجب ألا نعتمد الإجمالي
    // المطبوع من الخادم قبل مطابقته مع القيم الفعلية الظاهرة في النموذج.
    recalculate();
});
</script>
@endsection
