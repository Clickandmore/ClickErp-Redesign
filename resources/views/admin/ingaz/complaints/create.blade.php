@extends('admin.layout.indexs.index')

@section('page-title', 'إضافة شكوى جديدة')

@section('links')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">الرئيسية</a></li>
    <li class="breadcrumb-item"><a href="{{ route('complaints.index') }}">الشكاوى</a></li>
    <li class="breadcrumb-item active">إضافة شكوى</li>
@endsection

@section('style')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .complaint-create { max-width: 1240px; margin: 0 auto; }
    .complaint-hero { background: linear-gradient(135deg, #19376d, #2f67b2); color: #fff; border-radius: 18px; padding: 28px; box-shadow: 0 14px 34px rgba(25,55,109,.18); }
    .complaint-hero .hero-icon { width: 54px; height: 54px; display: grid; place-items: center; border-radius: 16px; background: rgba(255,255,255,.14); font-size: 24px; }
    .form-section { background: #fff; border: 1px solid #e8edf4; border-radius: 16px; margin-top: 18px; overflow: hidden; box-shadow: 0 6px 22px rgba(31,45,61,.05); }
    .form-section-header { display: flex; gap: 13px; align-items: center; padding: 18px 22px; background: #f8fafc; border-bottom: 1px solid #e8edf4; }
    .step-number { width: 34px; height: 34px; display: grid; place-items: center; border-radius: 10px; background: #2f67b2; color: #fff; font-weight: 700; }
    .form-section-body { padding: 22px; }
    .form-label { color: #344054; font-weight: 700; }
    .required { color: #dc3545; }
    .relation-grid { display: grid; grid-template-columns: repeat(6, minmax(120px,1fr)); gap: 10px; }
    .relation-option input { position: absolute; opacity: 0; pointer-events: none; }
    .relation-card { min-height: 96px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; border: 1px solid #dce3ed; border-radius: 13px; background: #fff; color: #475467; cursor: pointer; transition: .2s ease; }
    .relation-card i { font-size: 22px; color: #667085; }
    .relation-option input:checked + .relation-card { border-color: #2f67b2; background: #eef5ff; color: #19376d; box-shadow: 0 0 0 3px rgba(47,103,178,.11); }
    .relation-option input:checked + .relation-card i { color: #2f67b2; }
    .relation-panel { margin-top: 18px; padding: 18px; background: #f8fafc; border: 1px dashed #cfd8e6; border-radius: 13px; }
    .select2-container .select2-selection--single { height: 39px; border-color: #dee2e6; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 37px; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 37px; }
    .footer-actions { position: sticky; bottom: 12px; z-index: 5; display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; padding: 14px; border: 1px solid #e8edf4; border-radius: 14px; background: rgba(255,255,255,.95); box-shadow: 0 8px 30px rgba(31,45,61,.11); backdrop-filter: blur(7px); }
    @media (max-width: 992px) { .relation-grid { grid-template-columns: repeat(3,1fr); } }
    @media (max-width: 576px) { .relation-grid { grid-template-columns: repeat(2,1fr); } .form-section-body { padding: 16px; } }
</style>
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_CREATE))
<div class="complaint-create">
    <div class="complaint-hero d-flex align-items-center gap-3">
        <div class="hero-icon"><i class="fas fa-headset"></i></div>
        <div>
            <h3 class="mb-1">تسجيل شكوى جديدة</h3>
            <div class="opacity-75">صنّف الشكوى واربطها بالسجل الصحيح، أو أدخل بيانات الطرف يدويًا.</div>
        </div>
    </div>

    <form action="{{ route('complaints.store') }}" method="POST" enctype="multipart/form-data" id="complaintForm" class="from-submit-global" novalidate>
        @csrf

        <section class="form-section">
            <div class="form-section-header"><span class="step-number">1</span><div><h5 class="mb-0">تصنيف الشكوى</h5><small class="text-muted">حدد نوع الشكوى ودرجة أهميتها</small></div></div>
            <div class="form-section-body"><div class="row">
                <div class="col-lg-6 mb-3">
                    <label class="form-label">تصنيف الشكوى <span class="required">*</span></label>
                    <select name="complaint_type_id" id="complaintType" class="form-select select2 @error('complaint_type_id') is-invalid @enderror" required>
                        <option value="">اختر التصنيف</option>
                        @foreach($complaintTypes as $type)
                            <option value="{{ $type->id }}" data-complainant="{{ $type->complainant_type }}" data-against="{{ $type->against_type }}" {{ old('complaint_type_id') == $type->id ? 'selected' : '' }}>
                                {{ $type->name }} @if($type->complainant_type || $type->against_type) — {{ $type->classification_label }} @endif
                            </option>
                        @endforeach
                    </select>
                    @error('complaint_type_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-lg-6 mb-3">
                    <label class="form-label">موضوع الشكوى <span class="required">*</span></label>
                    <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror" value="{{ old('subject') }}" placeholder="ملخص واضح ومختصر للشكوى" required>
                    @error('subject')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">الأولوية <span class="required">*</span></label>
                    <select name="priority" class="form-select" required>
                        <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>منخفضة</option>
                        <option value="medium" {{ old('priority', 'medium') === 'medium' ? 'selected' : '' }}>متوسطة</option>
                        <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>عالية</option>
                        <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>عاجلة</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">الموظف المسؤول</label>
                    <select name="assigned_to" class="form-select select2">
                        <option value="">يحدد لاحقًا</option>
                        @foreach($admins as $admin)<option value="{{ $admin->id }}" {{ old('assigned_to') == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>@endforeach
                    </select>
                </div>
            </div></div>
        </section>

        <section class="form-section">
            <div class="form-section-header"><span class="step-number">2</span><div><h5 class="mb-0">بيانات مقدم الشكوى</h5><small class="text-muted">يمكن اختيار عميل مسجل أو كتابة البيانات يدويًا</small></div></div>
            <div class="form-section-body"><div class="row">
                <div class="col-lg-4 mb-3">
                    <label class="form-label">عميل مسجل <span class="text-muted fw-normal">(اختياري)</span></label>
                    <select name="user_id" id="complainantUser" class="form-select select2">
                        <option value="">إدخال يدوي</option>
                        @foreach($users as $user)<option value="{{ $user->id }}" data-name="{{ $user->name }}" data-phone="{{ $user->phone }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }} — {{ $user->phone }}</option>@endforeach
                    </select>
                </div>
                <div class="col-lg-3 mb-3">
                    <label class="form-label">صفة مقدم الشكوى <span class="required">*</span></label>
                    <select name="complainant_type" id="complainantType" class="form-select" required>
                        @foreach($partyTypes as $value => $label)<option value="{{ $value }}" {{ old('complainant_type', 'client') === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="col-lg-3 mb-3">
                    <label class="form-label">الاسم <span class="required">*</span></label>
                    <input type="text" name="complainant_name" class="form-control @error('complainant_name') is-invalid @enderror" value="{{ old('complainant_name') }}" required>
                    @error('complainant_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-lg-2 mb-3">
                    <label class="form-label">الجوال <span class="required">*</span></label>
                    <input type="tel" name="complainant_phone" class="form-control @error('complainant_phone') is-invalid @enderror" value="{{ old('complainant_phone') }}" placeholder="05XXXXXXXX" required>
                    @error('complainant_phone')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div></div>
        </section>

        <section class="form-section">
            <div class="form-section-header"><span class="step-number">3</span><div><h5 class="mb-0">ربط الشكوى</h5><small class="text-muted">اختر السجل المرتبط أو أضف بيانات المشتكى عليه يدويًا</small></div></div>
            <div class="form-section-body">
                @php($selectedRelation = old('relation_type', 'none'))
                <div class="relation-grid">
                    @foreach([
                        'order' => ['fa-shopping-cart','طلب'], 'contract' => ['fa-file-contract','عقد'],
                        'office' => ['fa-building','مكتب خارجي'], 'client' => ['fa-user-tie','عميل'],
                        'manual' => ['fa-keyboard','إدخال يدوي'], 'none' => ['fa-ban','بدون ربط']
                    ] as $value => $meta)
                        <label class="relation-option"><input type="radio" name="relation_type" value="{{ $value }}" {{ $selectedRelation === $value ? 'checked' : '' }}><span class="relation-card"><i class="fas {{ $meta[0] }}"></i><strong>{{ $meta[1] }}</strong></span></label>
                    @endforeach
                </div>

                <div class="relation-panel" data-relation="order">
                    <label class="form-label">اختر الطلب <span class="required">*</span></label>
                    <select name="order_id" class="form-select select2 relation-input"><option value="">ابحث برقم الطلب أو اسم العميل</option>@foreach($orders as $order)<option value="{{ $order->id }}" {{ old('order_id') == $order->id ? 'selected' : '' }}>طلب #{{ $order->order_code ?: $order->id }} — {{ $order->user?->name ?: 'بدون عميل' }}</option>@endforeach</select>
                </div>
                <div class="relation-panel" data-relation="contract">
                    <label class="form-label">اختر العقد <span class="required">*</span></label>
                    <select name="contract_id" class="form-select select2 relation-input"><option value="">ابحث برقم العقد أو اسم العميل</option>@foreach($contracts as $contract)<option value="{{ $contract->id }}" {{ old('contract_id') == $contract->id ? 'selected' : '' }}>عقد #{{ $contract->id }} — {{ $contract->user?->name ?: 'بدون عميل' }} @if($contract->maid) — {{ $contract->maid->name }} @endif</option>@endforeach</select>
                </div>
                <div class="relation-panel" data-relation="office">
                    <label class="form-label">اختر المكتب الخارجي <span class="required">*</span></label>
                    <select name="recruitment_office_id" class="form-select select2 relation-input"><option value="">ابحث باسم المكتب</option>@foreach($recruitmentOffices as $office)<option value="{{ $office->id }}" {{ old('recruitment_office_id') == $office->id ? 'selected' : '' }}>{{ $office->title ?: 'مكتب #' . $office->id }}</option>@endforeach</select>
                </div>
                <div class="relation-panel" data-relation="client">
                    <label class="form-label">اختر العميل المرتبط <span class="required">*</span></label>
                    <select name="related_user_id" class="form-select select2 relation-input"><option value="">ابحث بالاسم أو الجوال</option>@foreach($users as $user)<option value="{{ $user->id }}" {{ old('related_user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }} — {{ $user->phone }}</option>@endforeach</select>
                </div>
                <div class="relation-panel" data-relation="manual"><div class="row">
                    <div class="col-md-4 mb-3"><label class="form-label">اسم المشتكى عليه <span class="required">*</span></label><input type="text" name="against_name" class="form-control relation-input" value="{{ old('against_name') }}"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">الجوال / وسيلة التواصل</label><input type="text" name="against_phone" class="form-control relation-input" value="{{ old('against_phone') }}"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">رقم أو مرجع خارجي</label><input type="text" name="relation_reference" class="form-control relation-input" value="{{ old('relation_reference') }}"></div>
                </div></div>
                <div class="relation-panel" data-relation="none"><div class="text-muted"><i class="fas fa-info-circle me-2"></i>ستُحفظ الشكوى دون ربط بسجل، ويمكن ربطها لاحقًا.</div></div>
                <div class="mt-3"><label class="form-label">ملاحظات الربط <span class="text-muted fw-normal">(اختياري)</span></label><textarea name="relation_notes" class="form-control" rows="2" placeholder="أي معلومات تساعد في تحديد الطلب أو العقد أو الطرف المرتبط">{{ old('relation_notes') }}</textarea></div>
            </div>
        </section>

        <section class="form-section">
            <div class="form-section-header"><span class="step-number">4</span><div><h5 class="mb-0">تفاصيل الشكوى والمرفقات</h5><small class="text-muted">اكتب وصفًا كاملًا وأرفق المستندات المتاحة</small></div></div>
            <div class="form-section-body"><div class="row">
                <div class="col-12 mb-3"><label class="form-label">تفاصيل الشكوى <span class="required">*</span></label><textarea name="details" class="form-control @error('details') is-invalid @enderror" rows="6" required placeholder="اكتب الوقائع والتواريخ والإجراء المطلوب بوضوح">{{ old('details') }}</textarea>@error('details')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
                <div class="col-md-6 mb-3"><label class="form-label">رقم جواز العاملة</label><input type="text" name="maid_passport" class="form-control" value="{{ old('maid_passport') }}"></div>
                <div class="col-md-6 mb-3"><label class="form-label">جنسية العاملة</label><select name="maid_nationality_id" class="form-select select2"><option value="">غير محدد</option>@foreach($nationalities as $nationality)<option value="{{ $nationality->id }}" {{ old('maid_nationality_id') == $nationality->id ? 'selected' : '' }}>{{ $nationality->title }}</option>@endforeach</select></div>
                <div class="col-12"><label class="form-label">المرفقات</label><input type="file" name="attachments[]" class="form-control" multiple accept="image/*,application/pdf,.doc,.docx"><small class="text-muted">صور أو PDF أو Word، بحد أقصى 5MB لكل ملف.</small></div>
            </div></div>
        </section>

        <div class="footer-actions"><a href="{{ route('complaints.index') }}" class="btn btn-light border">إلغاء</a><button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-2"></i>حفظ الشكوى</button></div>
    </form>
</div>
@else
    @include('components.no_permissions')
@endif
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function () {
    $('.select2').select2({ dir: 'rtl', width: '100%', allowClear: true, placeholder: 'اختر أو ابحث' });

    function refreshRelationPanels() {
        const type = $('[name="relation_type"]:checked').val();
        $('.relation-panel').each(function () {
            const active = $(this).data('relation') === type;
            $(this).toggle(active).find('.relation-input').prop('disabled', !active);
        });
        $('[name="relation_notes"]').closest('.mt-3').toggle(type !== 'none');
    }
    $('[name="relation_type"]').on('change', refreshRelationPanels);
    refreshRelationPanels();

    $('#complainantUser').on('change', function () {
        const option = $(this).find(':selected');
        if (option.val()) {
            $('[name="complainant_name"]').val(option.data('name'));
            $('[name="complainant_phone"]').val(option.data('phone'));
            $('#complainantType').val('client');
        }
    }).trigger('change');

    $('#complaintType').on('change', function () {
        const option = $(this).find(':selected');
        if (option.data('complainant')) $('#complainantType').val(option.data('complainant'));
    });

    $('#complaintForm').on('submit', function (event) {
        let valid = true;
        $(this).find('[required]:enabled').each(function () {
            const empty = !String($(this).val() || '').trim();
            $(this).toggleClass('is-invalid', empty);
            valid = valid && !empty;
        });
        const phone = $('[name="complainant_phone"]').val();
        if (phone && !/^(05|5)[0-9]{8}$/.test(phone)) {
            $('[name="complainant_phone"]').addClass('is-invalid');
            valid = false;
        }
        if (!valid) {
            event.preventDefault();
            event.stopImmediatePropagation();
            $('html,body').animate({scrollTop: $('.is-invalid').first().offset().top - 120}, 300);
        }
    });
});
</script>
@endsection
