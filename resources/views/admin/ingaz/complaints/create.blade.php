@extends('admin.layout.indexs.index')

@section('page-title', $isEdit ? 'تعديل الشكوى #' . $complaint->id : 'إضافة شكوى جديدة')

@section('links')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">الرئيسية</a></li>
    <li class="breadcrumb-item"><a href="{{ route('complaints.index') }}">الشكاوى</a></li>
    <li class="breadcrumb-item active">{{ $isEdit ? 'تعديل الشكوى #' . $complaint->id : 'إضافة شكوى' }}</li>
@endsection

@section('style')
<style>
    .complaint-create { position: relative; max-width: 1240px; margin: 0 auto; }
    .complaint-hero { background: linear-gradient(135deg, #19376d, #2f67b2); color: #fff; border-radius: 18px; padding: 28px; box-shadow: 0 14px 34px rgba(25,55,109,.18); }
    .complaint-hero .hero-icon { width: 54px; height: 54px; display: grid; place-items: center; border-radius: 16px; background: rgba(255,255,255,.14); font-size: 24px; }
    .form-section { background: #fff; border: 1px solid #e8edf4; border-radius: 16px; margin-top: 18px; overflow: visible; box-shadow: 0 6px 22px rgba(31,45,61,.05); }
    .form-section-header { display: flex; gap: 13px; align-items: center; padding: 18px 22px; background: #f8fafc; border-bottom: 1px solid #e8edf4; border-radius: 16px 16px 0 0; }
    .step-number { width: 34px; height: 34px; display: grid; place-items: center; border-radius: 10px; background: #2f67b2; color: #fff; font-weight: 700; }
    .form-section-body { position: relative; padding: 22px; }
    .form-label { color: #344054; font-weight: 700; }
    .required { color: #dc3545; }
    .relation-grid { display: grid; grid-template-columns: repeat(6, minmax(120px,1fr)); gap: 10px; }
    .relation-option { position: relative; display: block; min-width: 0; }
    .relation-option input { position: absolute; inset: 0; z-index: 2; width: 100%; height: 100%; margin: 0; opacity: 0; cursor: pointer; }
    .relation-card { min-height: 96px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; border: 1px solid #dce3ed; border-radius: 13px; background: #fff; color: #475467; cursor: pointer; transition: .2s ease; }
    .relation-card i { font-size: 22px; color: #667085; }
    .relation-option input:checked + .relation-card { border-color: #2f67b2; background: #eef5ff; color: #19376d; box-shadow: 0 0 0 3px rgba(47,103,178,.11); }
    .relation-option input:checked + .relation-card i { color: #2f67b2; }
    .relation-panel { margin-top: 18px; padding: 18px; background: #f8fafc; border: 1px dashed #cfd8e6; border-radius: 13px; }
    .maid-mode-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 10px; }
    .maid-mode-option { position: relative; display: block; margin: 0; }
    .maid-mode-option input { position: absolute; inset: 0; z-index: 2; width: 100%; height: 100%; margin: 0; opacity: 0; cursor: pointer; }
    .maid-mode-card { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border: 1px solid #dce3ed; border-radius: 13px; background: #fff; color: #475467; transition: .2s ease; }
    .maid-mode-card i { width: 38px; height: 38px; flex-shrink: 0; display: grid; place-items: center; border-radius: 10px; background: #f2f4f7; color: #667085; font-size: 16px; }
    .maid-mode-card strong { display: block; color: #344054; }
    .maid-mode-card small { display: block; color: #667085; font-size: 12px; margin-top: 2px; }
    .maid-mode-option input:checked + .maid-mode-card { border-color: #2f67b2; background: #eef5ff; box-shadow: 0 0 0 3px rgba(47,103,178,.11); }
    .maid-mode-option input:checked + .maid-mode-card i { background: #2f67b2; color: #fff; }
    .maid-mode-option input:focus-visible + .maid-mode-card { outline: 2px solid #2f67b2; outline-offset: 2px; }
    .select2-container .select2-selection--single { height: 39px; padding: 0 .75rem; display: flex; align-items: center; border-color: #dee2e6; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { flex: 1; min-width: 0; line-height: 37px; padding: 0 0 0 26px; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { top: 0; bottom: 0; height: auto; left: 8px; right: auto; width: 20px; }
    .select2-container--default .select2-selection--single .select2-selection__clear { margin: 0 0 0 6px; color: #98a2b3; }
    .select2-container { max-width: 100%; }
    .select2-dropdown { z-index: 1055; }
    .footer-actions { position: relative; z-index: 1; display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; padding: 14px; border: 1px solid #e8edf4; border-radius: 14px; background: rgba(255,255,255,.95); box-shadow: 0 8px 30px rgba(31,45,61,.11); backdrop-filter: blur(7px); }
    @media (max-width: 992px) { .relation-grid { grid-template-columns: repeat(3,1fr); } }
    @media (max-width: 576px) { .relation-grid { grid-template-columns: repeat(2,1fr); } .maid-mode-grid { grid-template-columns: 1fr; } .form-section-body { padding: 16px; } }
</style>
@endsection

@section('content')
@if (@AdminGuardHasPerm($isEdit ? \App\PermKeys::COMPLAINT_EDIT : \App\PermKeys::COMPLAINT_CREATE))
<div class="complaint-create">
    <div class="complaint-hero d-flex align-items-center gap-3">
        <div class="hero-icon"><i class="fas fa-headset"></i></div>
        <div>
            <h3 class="mb-1">{{ $isEdit ? 'تعديل الشكوى #' . $complaint->id : 'تسجيل شكوى جديدة' }}</h3>
            <div class="opacity-75">{{ $isEdit ? 'راجع جميع بيانات الشكوى والربط والحالة ثم احفظ التعديلات.' : 'صنّف الشكوى واربطها بالسجل الصحيح، أو أدخل بيانات الطرف يدويًا.' }}</div>
        </div>
    </div>

    <form action="{{ $isEdit ? route('complaints.update', $complaint->id) : route('complaints.store') }}" method="POST" enctype="multipart/form-data" id="complaintForm" class="from-submit-global" novalidate>
        @csrf
        @if($isEdit) @method('PUT') @endif

        <section class="form-section">
            <div class="form-section-header"><span class="step-number">1</span><div><h5 class="mb-0">تصنيف الشكوى</h5><small class="text-muted">حدد نوع الشكوى ودرجة أهميتها</small></div></div>
            <div class="form-section-body"><div class="row">
                <div class="col-lg-6 mb-3">
                    <label class="form-label">تصنيف الشكوى <span class="required">*</span></label>
                    <select name="complaint_type_id" id="complaintType" class="form-select select2 @error('complaint_type_id') is-invalid @enderror" required>
                        <option value="">اختر التصنيف</option>
                        @foreach($complaintTypes as $type)
                            <option value="{{ $type->id }}" data-complainant="{{ $type->complainant_type }}" data-against="{{ $type->against_type }}" {{ old('complaint_type_id', $complaint->complaint_type_id ?? null) == $type->id ? 'selected' : '' }}>
                                {{ $type->name }} @if($type->complainant_type || $type->against_type) — {{ $type->classification_label }} @endif
                            </option>
                        @endforeach
                    </select>
                    @error('complaint_type_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-lg-6 mb-3">
                    <label class="form-label">موضوع الشكوى <span class="required">*</span></label>
                    <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror" value="{{ old('subject', $complaint->subject ?? null) }}" placeholder="ملخص واضح ومختصر للشكوى" required>
                    @error('subject')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-{{ $isEdit ? '4' : '6' }} mb-3">
                    <label class="form-label">الأولوية <span class="required">*</span></label>
                    <select name="priority" class="form-select" required>
                        <option value="low" {{ old('priority', $complaint->priority ?? 'medium') === 'low' ? 'selected' : '' }}>منخفضة</option>
                        <option value="medium" {{ old('priority', $complaint->priority ?? 'medium') === 'medium' ? 'selected' : '' }}>متوسطة</option>
                        <option value="high" {{ old('priority', $complaint->priority ?? 'medium') === 'high' ? 'selected' : '' }}>عالية</option>
                        <option value="urgent" {{ old('priority', $complaint->priority ?? 'medium') === 'urgent' ? 'selected' : '' }}>عاجلة</option>
                    </select>
                </div>
                <div class="col-md-{{ $isEdit ? '4' : '6' }} mb-3">
                    <label class="form-label">الموظف المسؤول</label>
                    <select name="assigned_to" class="form-select select2">
                        <option value="">يحدد لاحقًا</option>
                        @foreach($admins as $admin)<option value="{{ $admin->id }}" {{ old('assigned_to', $complaint->assigned_to ?? null) == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>@endforeach
                    </select>
                </div>
                @if($isEdit)
                    <div class="col-md-4 mb-3">
                        <label class="form-label">حالة الشكوى <span class="required">*</span></label>
                        <select name="status" class="form-select" required>
                            {{-- حالة "مغلقة" أُلغيت واعتُمدت "تم الحل" بدلاً منها؛ الشكاوى المغلقة قديماً تظهر كـ"تم الحل" --}}
                            @php
                                $currentStatus = old('status', $complaint->status === 'closed' ? 'resolved' : $complaint->status);
                            @endphp
                            @foreach(['pending' => 'قيد الانتظار', 'in_progress' => 'قيد المعالجة', 'resolved' => 'تم الحل', 'rejected' => 'مرفوضة'] as $value => $label)
                                <option value="{{ $value }}" {{ $currentStatus === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div></div>
        </section>

        <section class="form-section">
            <div class="form-section-header"><span class="step-number">2</span><div><h5 class="mb-0">بيانات مقدم الشكوى</h5><small class="text-muted">يمكن اختيار عميل مسجل أو كتابة البيانات يدويًا</small></div></div>
            <div class="form-section-body"><div class="row">
                <div class="col-lg-4 mb-3">
                    <label class="form-label">عميل مسجل <span class="text-muted fw-normal">(اختياري)</span></label>
                    <select name="user_id" id="complainantUser" class="form-select select2">
                        <option value="">إدخال يدوي</option>
                        @foreach($users as $user)<option value="{{ $user->id }}" data-name="{{ $user->name }}" data-phone="{{ $user->phone }}" {{ old('user_id', $complaint->user_id ?? null) == $user->id ? 'selected' : '' }}>{{ $user->name }} — {{ $user->phone }}</option>@endforeach
                    </select>
                </div>
                <div class="col-lg-3 mb-3">
                    <label class="form-label">صفة مقدم الشكوى <span class="required">*</span></label>
                    <select name="complainant_type" id="complainantType" class="form-select" required>
                        @foreach($partyTypes as $value => $label)<option value="{{ $value }}" {{ old('complainant_type', $complaint->complainant_type ?? 'client') === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="col-lg-3 mb-3">
                    <label class="form-label">الاسم <span class="required">*</span></label>
                    <input type="text" name="complainant_name" class="form-control @error('complainant_name') is-invalid @enderror" value="{{ old('complainant_name', $complaint->complainant_name ?? null) }}" required>
                    @error('complainant_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-lg-2 mb-3">
                    <label class="form-label">الجوال <span class="required">*</span></label>
                    <input type="tel" name="complainant_phone" class="form-control @error('complainant_phone') is-invalid @enderror" value="{{ old('complainant_phone', $complaint->complainant_phone ?? null) }}" placeholder="05XXXXXXXX" required>
                    <div id="complainantPhoneError" class="text-danger small mt-1" data-field="complainant_phone" aria-live="polite">@error('complainant_phone'){{ $message }}@enderror</div>
                </div>
            </div></div>
        </section>

        <section class="form-section">
            <div class="form-section-header"><span class="step-number">3</span><div><h5 class="mb-0">ربط الشكوى</h5><small class="text-muted">اختر السجل المرتبط أو أضف بيانات المشتكى عليه يدويًا</small></div></div>
            <div class="form-section-body">
                @php
                    $currentRelation = !empty($prefillContractId) ? 'contract' : 'none';
                    if ($isEdit) {
                        $currentRelation = $complaint->relation_type
                            ?: ($complaint->order_id ? 'order'
                                : ($complaint->contract_id ? 'contract'
                                    : ($complaint->recruitment_office_id ? 'office'
                                        : ($complaint->related_user_id ? 'client'
                                            : ($complaint->against_name ? 'manual' : 'none')))));
                    }
                    $selectedRelation = old('relation_type', $currentRelation);
                @endphp
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
                    <select name="order_id" class="form-select select2 relation-input"><option value="">ابحث برقم الطلب أو اسم العميل أو العاملة</option>@foreach($orders as $order)<option value="{{ $order->id }}" {{ old('order_id', $complaint->order_id ?? null) == $order->id ? 'selected' : '' }}>طلب #{{ $order->order_code ?: $order->id }} — {{ $order->user?->name ?: 'بدون عميل' }} @if($order->maid) — {{ $order->maid->name }} @endif</option>@endforeach</select>
                </div>
                <div class="relation-panel" data-relation="contract">
                    <label class="form-label">اختر العقد <span class="required">*</span></label>
                    <select name="contract_id" class="form-select select2 relation-input"><option value="">ابحث برقم العقد أو اسم العميل أو العاملة</option>@foreach($contractOptions as $contractOption)<option value="{{ $contractOption['id'] }}" {{ old('contract_id', $complaint->contract_id ?? $prefillContractId ?? null) == $contractOption['id'] ? 'selected' : '' }}>{{ $contractOption['label'] }}</option>@endforeach</select>
                </div>
                <div class="relation-panel" data-relation="office">
                    <label class="form-label">اختر المكتب الخارجي <span class="required">*</span></label>
                    <select name="recruitment_office_id" class="form-select select2 relation-input"><option value="">ابحث باسم المكتب</option>@foreach($recruitmentOffices as $office)<option value="{{ $office->id }}" {{ old('recruitment_office_id', $complaint->recruitment_office_id ?? null) == $office->id ? 'selected' : '' }}>{{ $office->title ?: 'مكتب #' . $office->id }}</option>@endforeach</select>
                </div>
                <div class="relation-panel" data-relation="client">
                    <label class="form-label">اختر العميل المرتبط <span class="required">*</span></label>
                    <select name="related_user_id" class="form-select select2 relation-input"><option value="">ابحث بالاسم أو الجوال</option>@foreach($users as $user)<option value="{{ $user->id }}" {{ old('related_user_id', $complaint->related_user_id ?? null) == $user->id ? 'selected' : '' }}>{{ $user->name }} — {{ $user->phone }}</option>@endforeach</select>
                </div>
                <div class="relation-panel" data-relation="manual"><div class="row">
                    <div class="col-md-4 mb-3"><label class="form-label">اسم المشتكى عليه <span class="required">*</span></label><input type="text" name="against_name" class="form-control relation-input" value="{{ old('against_name', $complaint->against_name ?? null) }}"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">الجوال / وسيلة التواصل</label><input type="text" name="against_phone" class="form-control relation-input" value="{{ old('against_phone', $complaint->against_phone ?? null) }}"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">رقم أو مرجع خارجي</label><input type="text" name="relation_reference" class="form-control relation-input" value="{{ old('relation_reference', $complaint->relation_reference ?? null) }}"></div>
                </div></div>
                <div class="relation-panel" data-relation="none"><div class="text-muted"><i class="fas fa-info-circle me-2"></i>ستُحفظ الشكوى دون ربط بسجل، ويمكن ربطها لاحقًا.</div></div>
                <div class="mt-3"><label class="form-label">ملاحظات الربط <span class="text-muted fw-normal">(اختياري)</span></label><textarea name="relation_notes" class="form-control" rows="2" placeholder="أي معلومات تساعد في تحديد الطلب أو العقد أو الطرف المرتبط">{{ old('relation_notes', $complaint->relation_notes ?? null) }}</textarea></div>
            </div>
        </section>

        <section class="form-section">
            <div class="form-section-header"><span class="step-number">4</span><div><h5 class="mb-0">تفاصيل الشكوى والمرفقات</h5><small class="text-muted">اكتب وصفًا كاملًا وأرفق المستندات المتاحة</small></div></div>
            <div class="form-section-body"><div class="row">
                <div class="col-12 mb-3"><label class="form-label">تفاصيل الشكوى <span class="required">*</span></label><textarea name="details" class="form-control @error('details') is-invalid @enderror" rows="6" required placeholder="اكتب الوقائع والتواريخ والإجراء المطلوب بوضوح">{{ old('details', $complaint->details ?? $complaint->message ?? null) }}</textarea>@error('details')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
                <div class="col-12 mb-3">
                    <label class="form-label" for="complaint-notes">ملاحظات الشكوى <span class="text-muted fw-normal">(اختياري)</span></label>
                    <textarea id="complaint-notes" name="complaint_notes" class="form-control @error('complaint_notes') is-invalid @enderror" rows="3" maxlength="5000" placeholder="أضف ملاحظات تظهر في جدول الشكاوى">{{ old('complaint_notes', $complaint->complaint_notes ?? null) }}</textarea>
                    @error('complaint_notes')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                @php $maidMode = old('maid_registration_status', $complaint->maid_registration_status ?? 'registered') === 'unregistered' ? 'unregistered' : 'registered'; @endphp
                <div class="col-12 mb-3">
                    <label class="form-label d-block">بيانات العاملة</label>
                    <div class="maid-mode-grid" role="radiogroup" aria-label="بيانات العاملة">
                        <label class="maid-mode-option">
                            <input type="radio" name="maid_registration_status" value="registered" @checked($maidMode === 'registered')>
                            <span class="maid-mode-card"><i class="fas fa-user-check"></i><span><strong>عاملة مسجلة بالنظام</strong><small>ابحث واختر العاملة بالاسم أو رقم الجواز</small></span></span>
                        </label>
                        <label class="maid-mode-option">
                            <input type="radio" name="maid_registration_status" value="unregistered" @checked($maidMode === 'unregistered')>
                            <span class="maid-mode-card"><i class="fas fa-user-edit"></i><span><strong>العاملة غير مسجلة بالنظام</strong><small>أدخل اسم العاملة ورقم جوازها يدويًا</small></span></span>
                        </label>
                    </div>
                </div>
                <div class="col-md-6 mb-3" id="registeredMaidFields">
                    <label class="form-label" for="complaintMaid">البحث عن العاملة بالاسم أو رقم الجواز</label>
                    <select name="maid_id" id="complaintMaid" class="form-select">
                        <option value=""></option>
                        @if(old('maid_id', $complaint->maid_id ?? null))
                            <option value="{{ old('maid_id', $complaint->maid_id ?? null) }}" selected>{{ old('maid_name', $complaint->maid_name ?? '') }} — {{ old('maid_passport', $complaint->maid_passport ?? '') }}</option>
                        @endif
                    </select>
                    <div class="form-text">اختر العاملة من نتائج البحث. يمكن ترك بيانات العاملة فارغة إذا لم تكن الشكوى تخص عاملة.</div>
                </div>
                <div class="col-md-6 mb-3" id="unregisteredMaidFields">
                    <label class="form-label" for="complaintMaidName">اسم العاملة غير المسجلة</label>
                    <input type="text" name="maid_name" id="complaintMaidName" class="form-control" maxlength="255" value="{{ old('maid_name', $complaint->maid_name ?? '') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="complaintMaidPassport">رقم جواز العاملة</label>
                    <input type="text" name="maid_passport" id="complaintMaidPassport" class="form-control" maxlength="50" dir="ltr" autocomplete="off" title="حروف إنجليزية وأرقام فقط، من {{ \App\Support\MaidFieldRules::PASSPORT_MIN_LENGTH }} إلى {{ \App\Support\MaidFieldRules::PASSPORT_MAX_LENGTH }} خانة" value="{{ old('maid_passport', $complaint->maid_passport ?? null) }}">
                    <div class="form-text" id="complaintMaidPassportHelp">الجواز والجنسية يُملآن من سجل العاملة المختارة ولا يمكن تعديلهما هنا.</div>
                    @error('maid_passport')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3"><label class="form-label">جنسية العاملة</label><select name="maid_nationality_id" class="form-select select2"><option value="">غير محدد</option>@foreach($nationalities as $nationality)<option value="{{ $nationality->id }}" {{ old('maid_nationality_id', $complaint->maid_nationality_id ?? null) == $nationality->id ? 'selected' : '' }}>{{ $nationality->sub_title ?? $nationality->title }}</option>@endforeach</select></div>
                @if($isEdit && ($complaint->attachmentFiles->isNotEmpty() || !empty($complaint->attachments)))
                    <div class="col-12 mb-3">
                        <label class="form-label">المرفقات الحالية</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($complaint->attachmentFiles as $attachment)
                                <a href="{{ showFile($attachment->path) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-paperclip me-1"></i>{{ $attachment->display_name ?: $attachment->original_name ?: basename($attachment->path) }}
                                </a>
                            @endforeach
                            @foreach((array) $complaint->attachments as $attachment)
                                <a href="{{ showFile($attachment) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-paperclip me-1"></i>{{ basename($attachment) }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
                <div class="col-12"><label class="form-label">{{ $isEdit ? 'إضافة مرفقات جديدة' : 'المرفقات' }}</label><input type="file" name="attachments[]" class="form-control" multiple accept="image/*,application/pdf,.doc,.docx"><small class="text-muted">صور أو PDF أو Word، بحد أقصى 5MB لكل ملف.</small></div>
            </div></div>
        </section>

        @if(!$isEdit)
            <div class="mt-3" id="shelter-after-save">
                <label class="form-label" for="after_save">بعد حفظ الشكوى</label>
                <select name="after_save" id="after_save" class="form-select">
                    <option value="list">حفظ الشكوى فقط</option>
                    <option value="shelter" {{ old('after_save') === 'shelter' ? 'selected' : '' }}>حفظ وربط الشكوى والانتقال لإدخال الإيواء</option>
                </select>
                <small class="text-muted">اختر العقد المرتبط، ثم أكمل بيانات استلام العاملة في صفحة الإيواء.</small>
            </div>
        @endif
        <div class="footer-actions"><a href="{{ route('complaints.index') }}" class="btn btn-light border">إلغاء</a><button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-2"></i>{{ $isEdit ? 'حفظ التعديلات' : 'حفظ الشكوى' }}</button></div>
    </form>
</div>
@else
    @include('components.no_permissions')
@endif
@endsection

@section('js')
<script>
$(function () {
    const pageLoaded = new Promise(function (resolve) {
        if (document.readyState === 'complete') resolve();
        else window.addEventListener('load', resolve, { once: true });
    });
    $('.select2').each(function () {
        const $select = $(this);

        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }

        const options = {
            dir: 'rtl',
            width: '100%',
            allowClear: true,
            placeholder: 'اختر أو ابحث',
            dropdownParent: $select.closest('.form-section-body')
        };
        if (['contract_id', 'order_id'].includes($select.attr('name'))) {
            const searchUrl = $select.attr('name') === 'order_id'
                ? @json(route('complaints.search-orders'))
                : @json(route('complaints.search-contracts'));
            let initialRequest = null;
            function firstPage() {
                if (!initialRequest) {
                    initialRequest = $.ajax({ url: searchUrl, dataType: 'json', data: { q: '', page: 1 } });
                    initialRequest.fail(function () { initialRequest = null; });
                }
                return initialRequest;
            }
            pageLoaded.then(function () { firstPage(); });
            options.ajax = {
                url: searchUrl,
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term || '', page: params.page || 1 }; },
                processResults: function (data) { return data; },
                transport: function (params, success, failure) {
                    let cancelled = false;
                    let request = null;
                    let shared = false;
                    pageLoaded.then(function () {
                        if (cancelled) return;
                        shared = !params.data.q && Number(params.data.page) === 1;
                        request = shared ? firstPage() : $.ajax(params);
                        request.done(function (data) { if (!cancelled) success(data); });
                        request.fail(function () { if (!cancelled) failure.apply(null, arguments); });
                    });
                    return { abort: function () {
                        cancelled = true;
                        if (request && !shared) request.abort();
                    } };
                }
            };
        }
        $select.select2(options);
    });

    const $maid = $('#complaintMaid');
    const $maidNationality = $('[name="maid_nationality_id"]');
    let selectedMaidIdentity = $maid.val() ? {
        passport: $('#complaintMaidPassport').val(),
        nationality_id: $maidNationality.val()
    } : null;
    $maid.select2({
        dir: 'rtl', width: '100%', allowClear: true,
        placeholder: 'ابحث بالاسم أو رقم الجواز', minimumInputLength: 2,
        ajax: {
            url: @json(route('complaints.search-maids')), dataType: 'json', delay: 250,
            data: function (params) { return { q: params.term || '', page: params.page || 1 }; },
            processResults: function (data) { return data; }
        }
    });
    $maid.on('select2:select', function (event) {
        selectedMaidIdentity = event.params.data;
        $('#complaintMaidPassport').val(selectedMaidIdentity.passport);
        $maidNationality.val(selectedMaidIdentity.nationality_id).trigger('change');
    }).on('select2:clear', function () {
        selectedMaidIdentity = null;
        $('#complaintMaidPassport').val('');
        $maidNationality.val('').trigger('change');
    });
    function refreshMaidMode() {
        const manual = $('[name="maid_registration_status"]:checked').val() === 'unregistered';
        $('#registeredMaidFields').toggle(!manual);
        $maid.prop('disabled', manual);
        $('#unregisteredMaidFields').toggle(manual);
        $('#complaintMaidName').prop('disabled', !manual).prop('required', manual);
        $('#complaintMaidPassport').prop('required', manual).prop('readOnly', !manual);
        // قيد الصيغة على الإدخال اليدوي فقط؛ جوازات العاملات المسجلات تأتي من السجل كما هي
        if (manual) {
            $('#complaintMaidPassport').attr({ pattern: '[A-Za-z0-9]{' + passportRule.min + ',' + passportRule.max + '}', maxlength: passportRule.max });
        } else {
            $('#complaintMaidPassport').removeAttr('pattern').attr('maxlength', 50);
        }
        $maidNationality.prop('disabled', !manual);
        $('#complaintMaidPassportHelp').text(manual
            ? 'أدخل جواز العاملة غير المسجلة (حروف إنجليزية وأرقام فقط) وجنسيتها يدويًا.'
            : 'الجواز والجنسية يُملآن من سجل العاملة المختارة ولا يمكن تعديلهما هنا.');
        if (!manual && selectedMaidIdentity) {
            $('#complaintMaidPassport').val(selectedMaidIdentity.passport);
            $maidNationality.val(selectedMaidIdentity.nationality_id).trigger('change');
        }
    }
    const passportRule = { min: {{ \App\Support\MaidFieldRules::PASSPORT_MIN_LENGTH }}, max: {{ \App\Support\MaidFieldRules::PASSPORT_MAX_LENGTH }} };
    $('#complaintMaidPassport').on('input', function () {
        if (this.readOnly) return;
        const cleaned = this.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
        if (cleaned !== this.value) this.value = cleaned;
    });
    $('[name="maid_registration_status"]').on('change', refreshMaidMode);
    refreshMaidMode();

    function refreshRelationPanels() {
        const type = $('[name="relation_type"]:checked').val();
        $('.relation-panel').each(function () {
            const active = $(this).data('relation') === type;
            $(this).toggle(active).find('.relation-input').prop('disabled', !active);
        });
        $('[name="relation_notes"]').closest('.mt-3').toggle(type !== 'none');
        $('#shelter-after-save').toggle(type === 'contract');
        $('#after_save').prop('disabled', type !== 'contract');
    }
    $('[name="relation_type"]').on('change', refreshRelationPanels);
    refreshRelationPanels();

    const $phone = $('[name="complainant_phone"]');
    $phone.attr('aria-describedby', 'complainantPhoneError');
    function validatePhone() {
        const phone = String($phone.val() || '');
        const valid = /^(05|5)[0-9]{8}$/.test(phone);
        $phone.toggleClass('is-invalid', !valid).attr('aria-invalid', String(!valid));
        $('#complainantPhoneError').text(valid ? '' : @json(helperTrans('ingaz.complaint_phone_invalid')));
        return valid;
    }
    $phone.on('input', validatePhone);

    $('#complainantUser').on('change', function () {
        const option = $(this).find(':selected');
        if (option.val()) {
            $('[name="complainant_name"]').val(option.data('name'));
            $('[name="complainant_phone"]').val(option.data('phone'));
            validatePhone();
            $('#complainantType').val('client');
        }
    });
    @if(!$isEdit)
        $('#complainantUser').trigger('change');
    @endif

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
        valid = validatePhone() && valid;
        if (!valid) {
            event.preventDefault();
            event.stopImmediatePropagation();
            $('html,body').animate({scrollTop: $('.is-invalid').first().offset().top - 120}, 300);
        }
    });
});
</script>
@endsection
