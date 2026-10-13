@extends('admin.layout.indexs.index')

@section('page-title')
    إعدادات نظام العمولات
@endsection

@section('page-links')
    <li class="breadcrumb-item"><a href="{{ route('commissions.marketers.index') }}">نظام العمولات</a></li>
    <li class="breadcrumb-item active">الإعدادات</li>
@endsection

@section('css')
<style>
    .commissions-hero {
        overflow: hidden;
        position: relative;
        border-radius: 18px;
        color: #fff;
        background: linear-gradient(135deg, #0f4c81 0%, #087f72 100%);
    }
    .commissions-hero::after {
        content: "";
        position: absolute;
        width: 260px;
        height: 260px;
        left: -90px;
        bottom: -170px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .09);
    }
    .settings-card {
        border: 0;
        border-radius: 16px;
        box-shadow: 0 5px 18px rgba(15, 23, 42, .07);
    }
    .settings-card > .card-header {
        padding: 16px 20px;
        border-bottom: 1px solid #edf0f4;
        border-radius: 16px 16px 0 0;
        background: #fff;
    }
    .settings-card > .card-body {
        padding: 20px;
    }
    .section-icon {
        width: 40px;
        height: 40px;
        display: grid;
        place-items: center;
        flex: 0 0 40px;
        border-radius: 12px;
        font-size: 1rem;
    }
    .section-hint {
        color: #64748b;
        font-size: .82rem;
        line-height: 1.7;
    }
    .field-label {
        margin-bottom: 4px;
        color: #64748b;
        font-size: .75rem;
        font-weight: 600;
    }
    .services-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
        gap: 14px;
    }
    .service-setting {
        height: 100%;
        padding: 16px;
        border: 1px solid #e8edf3;
        border-top: 4px solid var(--service-color);
        border-radius: 14px;
        background: #fbfcfe;
    }
    .service-setting__title {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 14px;
        color: #1e293b;
        font-weight: 700;
    }
    .service-setting__title i {
        color: var(--service-color);
    }
    .value-unit {
        min-width: 52px;
        justify-content: center;
        color: #475569;
        background: #f1f5f9;
        font-size: .8rem;
        font-weight: 700;
    }
    .policy-example {
        height: 100%;
        padding: 14px 16px;
        border: 1px solid #cffafe;
        border-radius: 14px;
        background: #f0fdff;
    }
    .policy-example ul {
        margin: 6px 0 0;
        padding-right: 18px;
        color: #475569;
        font-size: .8rem;
        line-height: 1.8;
    }
    .settings-table {
        margin-bottom: 0;
    }
    .settings-table thead th {
        padding: 10px 14px;
        border-bottom: 1px solid #e8edf3;
        color: #64748b;
        background: #f8fafc;
        font-size: .78rem;
        font-weight: 700;
        white-space: nowrap;
    }
    .settings-table td {
        padding: 10px 14px;
        border-color: #f1f5f9;
        vertical-align: middle;
    }
    .marketer-avatar {
        width: 34px;
        height: 34px;
        display: grid;
        place-items: center;
        flex: 0 0 34px;
        border-radius: 10px;
        color: #075985;
        background: #e0f2fe;
        font-size: .85rem;
        font-weight: 800;
    }
    .accounts-scroll {
        max-height: 460px;
        overflow-y: auto;
        border: 1px solid #edf0f4;
        border-radius: 12px;
    }
    .accounts-scroll thead th {
        position: sticky;
        top: 0;
        z-index: 1;
    }
    .overrides-head {
        padding: 0 12px 6px;
        color: #64748b;
        font-size: .75rem;
        font-weight: 700;
    }
    .override-row {
        align-items: center;
        margin: 0 0 8px !important;
        padding: 10px 12px !important;
        border: 1px solid #e8edf3 !important;
        border-radius: 12px !important;
        background: #fbfcfe;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .override-row:hover {
        border-color: #bfdbfe !important;
        box-shadow: 0 3px 10px rgba(15, 76, 129, .06);
    }
    .overrides-empty {
        display: none;
        padding: 28px 16px;
        border: 1px dashed #cbd5e1;
        border-radius: 14px;
        color: #94a3b8;
        text-align: center;
    }
    .overrides-empty:only-child {
        display: block;
    }
    .settings-actions {
        position: sticky;
        bottom: 0;
        z-index: 5;
        padding: 14px 20px;
        border-radius: 16px;
        background: rgba(255, 255, 255, .96);
        box-shadow: 0 -4px 20px rgba(15, 23, 42, .08);
    }
</style>
@endsection

@section('content')
@php
    $serviceStyles = [
        'electronic_ingaz_delegation' => ['color' => '#64748b', 'icon' => 'fa-file-signature'],
        'recruitment' => ['color' => '#2563eb', 'icon' => 'fa-plane-arrival'],
        'service_transfer' => ['color' => '#d97706', 'icon' => 'fa-exchange-alt'],
        'rental' => ['color' => '#0891b2', 'icon' => 'fa-file-contract'],
    ];
    $overridesCount = $marketers->sum(fn ($marketer) => $marketer->commissionOverrides->count());
    $linkedAccountsCount = $marketers->whereNotNull('commission_payable_account_id')->count();
    $rentalSetting = $commissionSettings['rental'] ?? null;
@endphp

<div class="commissions-hero p-4 mb-4">
    <div class="position-relative d-flex flex-wrap justify-content-between align-items-center gap-3" style="z-index:1">
        <div>
            <div class="small opacity-75 mb-1">التشغيل ← نظام العمولات</div>
            <h3 class="mb-2 fw-bold">قواعد ونسب العمولات</h3>
            <div class="opacity-75">القيم الافتراضية لكل خدمة، وسياسة خصم التأجير، وحسابات استحقاق المسوقين واستثناءاتهم.</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <span class="badge bg-white text-dark px-3 py-2">
                <i class="fa fa-users me-1 text-primary"></i> {{ $marketers->count() }} مسوق
            </span>
            <span class="badge bg-white text-dark px-3 py-2">
                <i class="fa fa-link me-1 text-success"></i> {{ $linkedAccountsCount }} حساب مربوط
            </span>
            <span class="badge bg-white text-dark px-3 py-2">
                <i class="fa fa-user-edit me-1 text-warning"></i> {{ $overridesCount }} استثناء
            </span>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success border-0 shadow-sm"><i class="fa fa-check-circle me-1"></i> {{ session('success') }}</div>
@endif

@if($errors->any())
<div class="alert alert-danger border-0 shadow-sm">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- سياسة خصم عمولات التأجير (نموذج مستقل) --}}
<form method="POST" action="{{ route('commissions.settings.store') }}">
    @csrf
    <div class="card settings-card mb-4">
        <div class="card-header d-flex align-items-center gap-3">
            <span class="section-icon bg-info bg-opacity-10 text-info"><i class="fa fa-undo"></i></span>
            <div>
                <h6 class="mb-1 fw-bold">إعدادات خصم عمولات التأجير</h6>
                <div class="section-hint">تحدد طريقة تسوية العمولة عند إنهاء عقد التأجير، وتُطبق على العمولة المعتمدة للموظف سواء كانت افتراضية أو استثناءً خاصًا به.</div>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-stretch">
                <div class="col-lg-5 d-flex flex-column">
                    <label for="rentalReturnPolicy" class="field-label">طريقة خصم العمولة عند إنهاء العقد</label>
                    <select id="rentalReturnPolicy" class="form-select" name="rental_return_policy">
                        <option value="full" @selected(old('rental_return_policy', $rentalSetting?->rental_return_policy ?? 'full') === 'full')>خصم العمولة كاملة</option>
                        <option value="remaining_days" @selected(old('rental_return_policy', $rentalSetting?->rental_return_policy ?? 'full') === 'remaining_days')>خصم نصيب المدة غير المنفذة فقط</option>
                    </select>
                    <div class="mt-auto pt-3">
                        <button type="submit" class="btn btn-info text-white">
                            <i class="fa fa-save me-1"></i>حفظ إعدادات الخصم
                        </button>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="policy-example">
                        <div class="small fw-bold text-info-emphasis"><i class="fa fa-lightbulb me-1"></i> كيف تعمل السياسة</div>
                        <ul>
                            <li>مثال: عقد 30 يومًا بعمولة 30 ريالًا، نُفذ منه 15 يومًا؛ يُخصم 15 ريالًا فقط. يوم الإنهاء محسوب ضمن الأيام المنفذة.</li>
                            <li>تُحفظ السياسة مع العمولات الجديدة. العمولات القديمة التي لم تُحفظ لها سياسة تتبع الإعداد الحالي عند التسوية. التسويات المعتمدة سابقًا لا تتغير.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<form method="POST" action="{{ route('commissions.settings.store') }}">
    @csrf

    {{-- الإعدادات العامة لكل نوع خدمة --}}
    <div class="card settings-card mb-4">
        <div class="card-header d-flex align-items-center gap-3">
            <span class="section-icon bg-primary bg-opacity-10 text-primary"><i class="fa fa-sliders-h"></i></span>
            <div>
                <h6 class="mb-1 fw-bold">الإعدادات الافتراضية لكل نوع خدمة</h6>
                <div class="section-hint">تحدد هذه القيم كيفية احتساب العمولة لكل نوع خدمة ما لم يكن هناك استثناء خاص بالموظف.</div>
            </div>
        </div>
        <div class="card-body">
            <div class="services-grid">
                @foreach($serviceTypeOptions as $type => $label)
                @php
                    $s = $commissionSettings[$type] ?? null;
                    $style = $serviceStyles[$type] ?? ['color' => '#0f4c81', 'icon' => 'fa-briefcase'];
                @endphp
                <div class="service-setting" style="--service-color: {{ $style['color'] }}">
                    <div class="service-setting__title">
                        <i class="fa {{ $style['icon'] }}"></i>
                        <span>{{ $label }}</span>
                    </div>
                    <div class="mb-3">
                        <label class="field-label d-block">طريقة الاحتساب</label>
                        <select class="form-select form-select-sm method-select" name="settings[{{ $type }}][method]">
                            <option value="fixed" {{ (!$s || $s->method === 'fixed') ? 'selected' : '' }}>مبلغ ثابت (ريال)</option>
                            <option value="percentage" {{ ($s && $s->method === 'percentage') ? 'selected' : '' }}>نسبة مئوية (%)</option>
                        </select>
                    </div>
                    <div>
                        <label class="field-label d-block">القيمة</label>
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control"
                                   name="settings[{{ $type }}][value]"
                                   step="0.01" min="0"
                                   value="{{ $s ? $s->value : 0 }}">
                            <span class="input-group-text value-unit"></span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- حسابات الاستحقاق --}}
    <div class="card settings-card mb-4">
        <div class="card-header d-flex align-items-center gap-3">
            <span class="section-icon bg-success bg-opacity-10 text-success"><i class="fa fa-link"></i></span>
            <div>
                <h6 class="mb-1 fw-bold">ربط المسوقين بحسابات الاستحقاق</h6>
                <div class="section-hint">
                    اختر رقم الحساب المالي النهائي لكل مسوق. هذا الربط هو المرجع الأساسي عند إنشاء قيد الاستحقاق. عند تركه تلقائيًا، يُربط الحساب الموجود أو يُنشأ حساب مستقل عند الاعتماد. تشمل القائمة موظفي خدمة العملاء وأصحاب العمليات من جميع أنواع المستخدمين.
                </div>
            </div>
        </div>
        <div class="card-body">
            @if($payableAccountError)
                <div class="alert alert-warning border-0">{{ $payableAccountError }} يمكن حفظ إعدادات العمولات الأخرى بعد إنشاء هذا الحساب.</div>
            @endif
            @if($marketers->isEmpty())
                <div class="text-center text-muted py-4">لا يوجد مسوقون نشطون حاليًا.</div>
            @else
            <div class="table-responsive accounts-scroll">
                <table class="table settings-table">
                    <thead>
                        <tr>
                            <th style="width:40%">المسوق</th>
                            <th>حساب استحقاق العمولة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($marketers as $marketer)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="marketer-avatar">{{ mb_substr($marketer->name, 0, 1) }}</span>
                                        <span class="fw-semibold">{{ $marketer->name }}</span>
                                    </div>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm"
                                            name="payable_accounts[{{ $marketer->id }}]" {{ $payableAccountError ? 'disabled' : '' }}>
                                        <option value="">تلقائي — ربط أو إنشاء حساب مستقل عند الاعتماد</option>
                                        @foreach($marketerPayableAccounts as $account)
                                            <option value="{{ $account->id }}"
                                                {{ (int) old('payable_accounts.' . $marketer->id, $marketer->commission_payable_account_id) === (int) $account->id ? 'selected' : '' }}>
                                                {{ $account->code }} — {{ $account->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    {{-- استثناءات الموظفين --}}
    <div class="card settings-card mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <span class="section-icon bg-warning bg-opacity-10 text-warning"><i class="fa fa-user-edit"></i></span>
                <div>
                    <h6 class="mb-1 fw-bold">استثناءات المسوقين</h6>
                    <div class="section-hint">إذا كان الموظف له نسبة أو مبلغ مختلف، أضفه هنا. يمكن ترك نوع الخدمة فارغاً ليطبق على جميع الخدمات.</div>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-primary" id="addOverrideBtn">
                <i class="fa fa-plus me-1"></i>إضافة استثناء
            </button>
        </div>
        <div class="card-body">
            <div class="row g-2 overrides-head d-none d-md-flex">
                <div class="col-md-4">المسوق</div>
                <div class="col-md-3">نوع الخدمة</div>
                <div class="col-md-2">طريقة الاحتساب</div>
                <div class="col-md-2">القيمة</div>
                <div class="col-md-1"></div>
            </div>
            <div id="overridesContainer">
                @foreach($marketers as $marketer)
                    @foreach($marketer->commissionOverrides as $ov)
                    <div class="row g-2 mb-2 override-row border rounded p-2">
                        <input type="hidden" name="overrides[{{ $loop->parent->index }}_{{ $loop->index }}][id]" value="{{ $ov->id }}">
                        <div class="col-md-4">
                            <select class="form-select form-select-sm" name="overrides[{{ $loop->parent->index }}_{{ $loop->index }}][admin_id]">
                                @foreach($marketers as $m)
                                    <option value="{{ $m->id }}" {{ $m->id == $ov->admin_id ? 'selected' : '' }}>{{ $m->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" name="overrides[{{ $loop->parent->index }}_{{ $loop->index }}][service_type]">
                                <option value="">كل الخدمات</option>
                                @foreach($serviceTypeOptions as $t => $l)
                                    <option value="{{ $t }}" {{ $ov->service_type === $t ? 'selected' : '' }}>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select form-select-sm method-select" name="overrides[{{ $loop->parent->index }}_{{ $loop->index }}][method]">
                                <option value="percentage" {{ $ov->method === 'percentage' ? 'selected' : '' }}>نسبة %</option>
                                <option value="fixed" {{ $ov->method === 'fixed' ? 'selected' : '' }}>مبلغ ثابت</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control" step="0.01" min="0"
                                       name="overrides[{{ $loop->parent->index }}_{{ $loop->index }}][value]"
                                       value="{{ $ov->value }}">
                                <span class="input-group-text value-unit"></span>
                            </div>
                        </div>
                        <div class="col-md-1 text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-override" title="حذف الاستثناء">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    @endforeach
                @endforeach
                <div class="overrides-empty">
                    <i class="fa fa-user-check fa-2x mb-2 d-block"></i>
                    لا توجد استثناءات — يُطبَّق الإعداد الافتراضي على جميع المسوقين.
                </div>
            </div>

            {{-- قالب صف جديد --}}
            <template id="overrideTemplate">
                <div class="row g-2 mb-2 override-row border rounded p-2">
                    <div class="col-md-4">
                        <select class="form-select form-select-sm" name="overrides[__IDX__][admin_id]">
                            @foreach($marketers as $m)
                                <option value="{{ $m->id }}">{{ $m->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" name="overrides[__IDX__][service_type]">
                            <option value="">كل الخدمات</option>
                            @foreach($serviceTypeOptions as $t => $l)
                                <option value="{{ $t }}">{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm method-select" name="overrides[__IDX__][method]">
                            <option value="fixed" selected>مبلغ ثابت</option>
                            <option value="percentage">نسبة %</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control" step="0.01" min="0"
                                   name="overrides[__IDX__][value]" value="0">
                            <span class="input-group-text value-unit"></span>
                        </div>
                    </div>
                    <div class="col-md-1 text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-override" title="حذف الاستثناء">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div class="settings-actions d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div class="section-hint"><i class="fa fa-info-circle me-1"></i> يحفظ هذا الزر القيم الافتراضية وحسابات الاستحقاق والاستثناءات معًا.</div>
        <div class="d-flex gap-2">
            <a href="{{ route('commissions.marketers.index') }}" class="btn btn-outline-secondary">إلغاء</a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="fa fa-save me-1"></i>حفظ الإعدادات
            </button>
        </div>
    </div>
</form>

@endsection

@section('js')
<script>
let overrideIdx = Date.now();

function syncValueUnit(select) {
    const unit = $(select).val() === 'percentage' ? '%' : 'ريال';
    $(select).closest('.service-setting, .override-row').find('.value-unit').text(unit);
}

$('.method-select').each(function() { syncValueUnit(this); });

$(document).on('change', '.method-select', function() {
    syncValueUnit(this);
});

$('#addOverrideBtn').on('click', function() {
    const tpl = document.getElementById('overrideTemplate').innerHTML
        .replace(/__IDX__/g, overrideIdx++).trim();
    const $row = $(tpl);
    $('#overridesContainer .overrides-empty').before($row);
    syncValueUnit($row.find('.method-select')[0]);
});

$(document).on('click', '.remove-override', function() {
    $(this).closest('.override-row').remove();
});
</script>
@endsection
