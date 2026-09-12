@extends('admin.layout.indexs.index')

@section('page-title')
    إعدادات نظام العمولات
@endsection

@section('page-links')
    <li class="breadcrumb-item"><a href="{{ route('commissions.marketers.index') }}">نظام العمولات</a></li>
    <li class="breadcrumb-item active">الإعدادات</li>
@endsection

@section('content')

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('commissions.settings.store') }}">
    @csrf

    {{-- الإعدادات العامة لكل نوع خدمة --}}
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0 fw-bold"><i class="fa fa-sliders-h me-2"></i>الإعدادات الافتراضية لكل نوع خدمة</h6>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">تحدد هذه القيم كيفية احتساب العمولة لكل نوع خدمة ما لم يكن هناك استثناء خاص بالموظف.</p>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>نوع الخدمة</th>
                            <th style="width:200px;">طريقة الاحتساب</th>
                            <th style="width:180px;">القيمة (نسبة % أو مبلغ ريال)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($serviceTypeOptions as $type => $label)
                        @php $s = $settings[$type] ?? null; @endphp
                        <tr>
                            <td class="fw-semibold">{{ $label }}</td>
                            <td>
                                <select class="form-select form-select-sm" name="settings[{{ $type }}][method]">
                                    <option value="fixed" {{ (!$s || $s->method === 'fixed') ? 'selected' : '' }}>مبلغ ثابت (ريال)</option>
                                    <option value="percentage" {{ ($s && $s->method === 'percentage') ? 'selected' : '' }}>نسبة مئوية (%)</option>
                                </select>
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm"
                                       name="settings[{{ $type }}][value]"
                                       step="0.01" min="0"
                                       value="{{ $s ? $s->value : 0 }}">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0 fw-bold"><i class="fa fa-link me-2"></i>ربط المسوقين بحسابات الاستحقاق</h6>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                اختر رقم الحساب المالي النهائي لكل مسوق. هذا الربط هو المرجع الأساسي عند إنشاء قيد الاستحقاق. عند تركه تلقائيًا، يُربط الحساب الموجود أو يُنشأ حساب مستقل عند الاعتماد. تشمل القائمة موظفي خدمة العملاء وأصحاب العمليات من جميع أنواع المستخدمين.
            </p>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>المسوق</th>
                            <th>حساب استحقاق العمولة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($marketers as $marketer)
                            <tr>
                                <td class="fw-semibold">{{ $marketer->name }}</td>
                                <td>
                                    <select class="form-select form-select-sm"
                                            name="payable_accounts[{{ $marketer->id }}]">
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
        </div>
    </div>

    {{-- استثناءات الموظفين --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="fa fa-user-edit me-2"></i>استثناءات المسوقين</h6>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addOverrideBtn">
                <i class="fa fa-plus me-1"></i>إضافة استثناء
            </button>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">إذا كان الموظف له نسبة أو مبلغ مختلف، أضفه هنا. يمكن ترك نوع الخدمة فارغاً ليطبق على جميع الخدمات.</p>
            <div id="overridesContainer">
                @foreach($marketers as $marketer)
                    @foreach($marketer->commissionOverrides as $ov)
                    <div class="row g-2 mb-2 override-row border rounded p-2">
                        <input type="hidden" name="overrides[{{ $loop->parent->index }}_{{ $loop->index }}][id]" value="{{ $ov->id }}">
                        <div class="col-md-3">
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
                            <select class="form-select form-select-sm" name="overrides[{{ $loop->parent->index }}_{{ $loop->index }}][method]">
                                <option value="percentage" {{ $ov->method === 'percentage' ? 'selected' : '' }}>نسبة %</option>
                                <option value="fixed" {{ $ov->method === 'fixed' ? 'selected' : '' }}>مبلغ ثابت</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="number" class="form-control form-control-sm" step="0.01" min="0"
                                   name="overrides[{{ $loop->parent->index }}_{{ $loop->index }}][value]"
                                   value="{{ $ov->value }}">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-override">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    @endforeach
                @endforeach
            </div>

            {{-- قالب صف جديد --}}
            <template id="overrideTemplate">
                <div class="row g-2 mb-2 override-row border rounded p-2">
                    <div class="col-md-3">
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
                        <select class="form-select form-select-sm" name="overrides[__IDX__][method]">
                            <option value="fixed" selected>مبلغ ثابت</option>
                            <option value="percentage">نسبة %</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="number" class="form-control form-control-sm" step="0.01" min="0"
                               name="overrides[__IDX__][value]" value="0">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-override">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div class="d-flex gap-3">
        <button type="submit" class="btn btn-primary">
            <i class="fa fa-save me-1"></i>حفظ الإعدادات
        </button>
        <a href="{{ route('commissions.marketers.index') }}" class="btn btn-outline-secondary">إلغاء</a>
    </div>
</form>

@endsection

@section('js')
<script>
let overrideIdx = Date.now();

$('#addOverrideBtn').on('click', function() {
    const tpl = document.getElementById('overrideTemplate').innerHTML
        .replace(/__IDX__/g, overrideIdx++);
    $('#overridesContainer').append(tpl);
});

$(document).on('click', '.remove-override', function() {
    $(this).closest('.override-row').remove();
});
</script>
@endsection
