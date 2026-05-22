@php
    $vat = max(0, (float) $obj->price_with_tax - (float) $obj->original_price);
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label text-muted mb-1">اسم الباقة</label>
        <div class="fw-bold">{{ $obj->title ?: '-' }}</div>
    </div>

    <div class="col-md-6">
        <label class="form-label text-muted mb-1">الجنسية</label>
        <div>{{ $obj->nationality?->sub_title ?? $obj->nationality?->title ?? 'جميع الجنسيات' }}</div>
    </div>

    <div class="col-md-4">
        <label class="form-label text-muted mb-1">نوع الإيجار</label>
        <div>{{ $obj->rental_type_label }}</div>
    </div>

    <div class="col-md-4">
        <label class="form-label text-muted mb-1">المدة</label>
        <div>{{ $obj->duration_label }}</div>
    </div>

    <div class="col-md-4">
        <label class="form-label text-muted mb-1">الظهور</label>
        <div>{!! $obj->is_show ? '<span class="badge bg-success">ظاهر</span>' : '<span class="badge bg-secondary">مخفي</span>' !!}</div>
    </div>

    <div class="col-md-3">
        <label class="form-label text-muted mb-1">السعر بدون الضريبة</label>
        <div>{{ number_format((float) $obj->original_price, 2) }}</div>
    </div>

    <div class="col-md-3">
        <label class="form-label text-muted mb-1">الضريبة</label>
        <div>{{ number_format($vat, 2) }}</div>
    </div>

    <div class="col-md-3">
        <label class="form-label text-muted mb-1">الإجمالي بعد الضريبة</label>
        <div>{{ number_format((float) $obj->price_with_tax, 2) }}</div>
    </div>

    <div class="col-md-3">
        <label class="form-label text-muted mb-1">قيمة العرض</label>
        <div>{{ $obj->offer_amount ? number_format((float) $obj->offer_amount, 2) : '-' }}</div>
    </div>

    <div class="col-md-3">
        <label class="form-label text-muted mb-1">حالة العرض</label>
        <div>{!! $obj->offer_is_active ? '<span class="badge bg-success">مفعل</span>' : '<span class="badge bg-secondary">غير مفعل</span>' !!}</div>
    </div>

    <div class="col-md-3">
        <label class="form-label text-muted mb-1">بداية العرض</label>
        <div>{{ $obj->offer_starts_at?->format('Y-m-d') ?? '-' }}</div>
    </div>

    <div class="col-md-3">
        <label class="form-label text-muted mb-1">انتهاء العرض</label>
        <div>{{ $obj->offer_ends_at?->format('Y-m-d') ?? '-' }}</div>
    </div>

    <div class="col-md-3">
        <label class="form-label text-muted mb-1">أيام الباقة</label>
        <div>{{ $obj->days_count }}</div>
    </div>

    <div class="col-md-3">
        <label class="form-label text-muted mb-1">ترتيب الباقة</label>
        <div>{{ $obj->sort_order ?? 0 }}</div>
    </div>

    <div class="col-md-3">
        <label class="form-label text-muted mb-1">ساعات العمل اليومية</label>
        <div>{{ $obj->working_hours_per_day ?: '-' }}</div>
    </div>

    @if($obj->description)
        <div class="col-12">
            <label class="form-label text-muted mb-1">وصف مختصر</label>
            <div>{{ $obj->description }}</div>
        </div>
    @endif

    @if($obj->offer_description)
        <div class="col-12">
            <label class="form-label text-muted mb-1">وصف العرض</label>
            <div>{{ $obj->offer_description }}</div>
        </div>
    @endif

    <div class="col-12 modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{__("buttons.close")}}</button>
    </div>
</div>
