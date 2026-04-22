@extends('admin.layout.indexs.index')
@php
    $isEditMode = $isEditMode ?? false;
    $editingContract = $editingContract ?? null;
    $editingInfo = optional($editingContract)->info;
    $selectedContractId = old('contract_id', $selectedContractId ?? optional($editingContract)->id);
    $defaultReceiptText = replaceDocumentPlaceholders((string) documentSetting(
        'shelter_receipt_default_text',
        ' الى [اسم_المكتب] وأمل من المكتب حل مشكلتها وذلك بنقل خدماتها لتعمل عند صاحب عمل آخر او ترحيلها حسب ما يراه المكتب مناسبا، كما اتعهد بعدم عمل خروج نهائي او بلاغ هروب أو اي اجراء يخص العاملة إلا بموافقة من المكتب، علماً أنه في حالة نقل خدمات العاملة يحق لي استرجاع مستحقاتي وفقاً للعقد الموحد والنظام الصادر من وزارة الموارد البشرية والتنمية الاجتماعية ، حيث انه حسب التعميم الصادر من مساند والذي ينص على الزام ارجاع المبالغ المستحقة للعملاء من خلال منصة مساند وليس عن طريق حساب المكتب المستقدم وعلى ان يكون ارجاع المبلغ خلال عشرين يوم عمل من تاريخ تسليم العاملة .'
    ));
    $editingQuickTags = [];
    if ($editingInfo && $editingInfo->shelter_quick_tags) {
        $editingQuickTags = is_array($editingInfo->shelter_quick_tags)
            ? $editingInfo->shelter_quick_tags
            : (json_decode($editingInfo->shelter_quick_tags, true) ?: []);
    }
@endphp
@section('page-title')
    {{ $isEditMode ? helperTrans('ingaz.edit Shelter contract') : helperTrans('ingaz.add Shelter contract') }}
@endsection
@section('content')
    @if (
        (!$isEditMode && @AdminGuardHasAnyPerm([\App\PermKeys::SHELTER_CONTRACT_CREATE, \App\PermKeys::SHELTER_CONTRACT_CONTROL])) ||
        ($isEditMode && @AdminGuardHasAnyPerm([\App\PermKeys::SHELTER_CONTRACT_EDIT, \App\PermKeys::SHELTER_CONTRACT_CONTROL]))
    )
        <script>
            console.log('=== Page Loaded - ShelterContract {{ $isEditMode ? 'Edit' : 'Create' }} ===');
            console.log('jQuery version:', $.fn.jquery);
            console.log('Document ready state:', document.readyState);
        </script>

        @if (!$isEditMode)
            <!-- زر إضافة العقد -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                {{ helperTrans('ingaz.quick_create_contract') }}
                            </h5>
                            <p class="card-text">{{ helperTrans('ingaz.create_completed_contract_description') }}</p>
                            <div class="d-flex gap-2">
                                {{-- <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createContractModal">
                                <i class="fas fa-plus me-1"></i>
                                {{helperTrans("ingaz.create_completed_contract")}}
                            </button> --}}
                                <a href="{{ route('Contract.createCompletedPage') }}" class="btn btn-primary">
                                    <i class="fas fa-external-link-alt me-1"></i>
                                    {{ helperTrans('ingaz.add_completed_contract') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <form class="row g-3 contractForm" method="post"
            action="{{ $isEditMode ? route('ShelterContract.update', $editingContract->id) : route('ShelterContract.store') }}"
            enctype="multipart/form-data">
            @csrf
            @if ($isEditMode)
                @method('PUT')
            @endif
            <div class="row p-3">

                <div class="col-4">
                    <label for="TextInput" class="form-label">{{ helperTrans('ingaz.contracts') }}</label>
                    <select name="contract_id" id="contract_id" class="form-select form-control select2"
                        data-validation="required" style="flex: 1;">
                        <option disabled {{ !$selectedContractId ? 'selected' : '' }}>{{ helperTrans('ingaz.choose') }}
                        </option>
                        @foreach ($contracts ?? [] as $contract)
                            @php
                                $warrantyStatus = '';
                                if ($contract->status === \App\Enums\ingaz\ContractStatusEnum::Completed->value) {
                                    $warrantyStatus = ' (مكتمل)';
                                } elseif (
                                    $contract->status === \App\Enums\ingaz\ContractStatusEnum::UnderWarranty->value
                                ) {
                                    $warrantyStatus = ' (تحت الضمان)';
                                } elseif ($contract->status === \App\Enums\ingaz\ContractStatusEnum::Closed->value) {
                                    $warrantyStatus = ' (مكتمل)';
                                }
                            @endphp
                            <option value="{{ $contract->id }}"
                                data-passport="{{ $contract->maid ? $contract->maid->passport_num : '' }}"
                                data-passport-snapshot="{{ optional($contract->info)->passport_number_snapshot ?? ($contract->maid->passport_num ?? '') }}"
                                data-maid-name="{{ $contract->maid ? $contract->maid->name : '' }}"
                                data-maid-name-snapshot="{{ optional($contract->info)->worker_name_snapshot ?? ($contract->maid->name ?? '') }}"
                                data-maid-nationality="{{ $contract->maid && $contract->maid->nationality ? $contract->maid->nationality->name_ar : '' }}"
                                data-client-name="{{ optional($contract->info)->client_name_snapshot ?? ($contract->user->name ?? '') }}"
                                data-client-national-id="{{ optional($contract->info)->client_national_id ?? ($contract->user->national_num ?? '') }}"
                                data-sponsorship-price="{{ optional($contract->info)->sponsorship_price }}"
                                data-receipt-date="{{ optional($contract->info)->receipt_form_date ?? (optional($contract->info)->arrival_date ?? ($contract->contract_date ?? '')) }}"
                                data-receipt-text="{{ optional($contract->info)->receipt_form_text }}"
                                data-has-residency="{{ optional($contract->info)->workers_have_residency }}"
                                data-medical-exam="{{ optional($contract->info)->performing_medical_examination }}"
                                data-service-type="{{ optional($contract->info)->worker_service_type }}"
                                data-status="{{ $contract->status }}"
                                {{ $selectedContractId == $contract->id ? 'selected' : '' }}>
                                {{ $contract->desc }}{{ $warrantyStatus }} @if ($contract->maid && $contract->maid->passport_num)
                                    - {{ $contract->maid->passport_num }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-4">
                    <label for="TextInput" class="form-label">{{ helperTrans('ingaz.shelter-places') }}</label>
                    <select name="shelter_id" class="form-select form-control select2" data-validation="required">
                        <option disabled {{ old('shelter_id', optional($editingInfo)->shelter_id) ? '' : 'selected' }}>
                            {{ helperTrans('ingaz.choose') }}
                        </option>
                        @foreach ($shelters ?? [] as $shelter)
                            <option value="{{ $shelter->id }}"
                                {{ (string) old('shelter_id', optional($editingInfo)->shelter_id) === (string) $shelter->id ? 'selected' : '' }}>
                                {{ $shelter->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-4">
                    <label for="TextInput" class="form-label">{{ helperTrans('ingaz.receipt_source') }}</label>
                    <select name="receipt_source" class="form-select form-control select2" data-validation="required">
                        <option disabled {{ old('receipt_source', optional($editingInfo)->receipt_source) ? '' : 'selected' }}>
                            {{ helperTrans('ingaz.choose') }}
                        </option>
                        @forelse (__('shelter_worker_statuses.received_by') ?? [] as $k => $name)
                            <option value="{{ $k }}"
                                {{ (string) old('receipt_source', optional($editingInfo)->receipt_source) === (string) $k ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @empty
                        @endforelse
                    </select>
                </div>

                <div class="col-4 mt-4">
                    <label for="TextInput" class="form-label">تاريخ إدخالها للإيواء</label>
                    <input type="date" name="maid_date_back" class="form-control" data-validation="required" readonly
                        value="{{ old('maid_date_back', optional($editingInfo)->maid_date_back) }}">
                    <small class="form-text text-muted">هذا التاريخ يمثل تاريخ مراجعتها وإيداعها داخل الإيواء وسيُعيّن تلقائياً إلى اليوم الحالي.</small>
                </div>

                <div class="col-4 mt-4">
                    <label for="TextInput" class="form-label">{{ helperTrans('ingaz.arrival_date') }}</label>
                    <input type="date" name="arrival_date" id="arrival_date" class="form-control"
                        data-validation="required" value="{{ old('arrival_date', optional($editingInfo)->arrival_date) }}">
                </div>
                <div class="col-4 mt-4">
                    <label for="TextInput" class="form-label">مدة بقائها داخل المملكة</label>
                    <input type="text" name="duration_from_enter" class="form-control" readonly
                        placeholder="سيتم الحساب تلقائياً"
                        value="{{ old('duration_from_enter', optional($editingInfo)->duration_from_enter) }}">
                    <small class="form-text text-muted">تحسب تلقائياً من تاريخ وصولها إلى المملكة وحتى اليوم.</small>
                </div>
                <div class="col-4 mt-4">
                    <label for="TextInput" class="form-label">{{ helperTrans('ingaz.salary_final_status') }}</label>
                    <select name="salary_final_status" class="form-select form-control select2" data-validation="required">
                        <option disabled
                            {{ old('salary_final_status', optional($editingInfo)->salary_final_status) ? '' : 'selected' }}>
                            {{ helperTrans('ingaz.choose') }}
                        </option>
                        <option value="received"
                            {{ old('salary_final_status', optional($editingInfo)->salary_final_status) === 'received' ? 'selected' : '' }}>
                            تم استلام الراتب
                        </option>
                        <option value="not_received"
                            {{ old('salary_final_status', optional($editingInfo)->salary_final_status) === 'not_received' ? 'selected' : '' }}>
                            لم يتم استلام الراتب
                        </option>
                    </select>
                </div>
                <div class="col-4 mt-4">
                    <label for="TextInput" class="form-label">{{ helperTrans('ingaz.work_willingness') }}</label>
                    <select name="wants_to_work" class="form-select form-control select2" data-validation="required">
                        <option disabled {{ old('wants_to_work', optional($editingInfo)->wants_to_work) ? '' : 'selected' }}>
                            {{ helperTrans('ingaz.choose') }}
                        </option>
                        <option value="yes"
                            {{ old('wants_to_work', optional($editingInfo)->wants_to_work) === 'yes' ? 'selected' : '' }}>
                            {{ helperTrans('ingaz.work_willingness_yes') }}
                        </option>
                        <option value="no"
                            {{ old('wants_to_work', optional($editingInfo)->wants_to_work) === 'no' ? 'selected' : '' }}>
                            {{ helperTrans('ingaz.work_willingness_no') }}
                        </option>
                        <option value="unknown"
                            {{ old('wants_to_work', optional($editingInfo)->wants_to_work) === 'unknown' ? 'selected' : '' }}>
                            {{ helperTrans('ingaz.work_willingness_unknown') }}
                        </option>
                    </select>
                </div>

                <div class="col-4 mt-4">
                    <label class="form-label">هل العاملة لديها اقامة؟</label>
                    <select name="workers_have_residency" id="workers_have_residency"
                        class="form-select form-control select2" data-validation="required">
                        <option disabled {{ old('workers_have_residency', optional($editingInfo)->workers_have_residency) ? '' : 'selected' }}>
                            {{ helperTrans('ingaz.choose') }}</option>
                        <option value="yes"
                            {{ old('workers_have_residency', optional($editingInfo)->workers_have_residency) === 'yes' ? 'selected' : '' }}>
                            لديها اقامة
                        </option>
                        <option value="no"
                            {{ old('workers_have_residency', optional($editingInfo)->workers_have_residency) === 'no' ? 'selected' : '' }}>
                            ليس لديها اقامة
                        </option>
                    </select>
                </div>

                <div class="col-4 mt-4">
                    <label class="form-label">هل تم عمل الفحص الطبي؟</label>
                    <select name="performing_medical_examination" id="performing_medical_examination"
                        class="form-select form-control select2" data-validation="required">
                        <option disabled
                            {{ old('performing_medical_examination', optional($editingInfo)->performing_medical_examination) ? '' : 'selected' }}>
                            {{ helperTrans('ingaz.choose') }}</option>
                        <option value="yes"
                            {{ old('performing_medical_examination', optional($editingInfo)->performing_medical_examination) === 'yes' ? 'selected' : '' }}>
                            نعم
                        </option>
                        <option value="no"
                            {{ old('performing_medical_examination', optional($editingInfo)->performing_medical_examination) === 'no' ? 'selected' : '' }}>
                            لا
                        </option>
                    </select>
                </div>

                <div class="col-4 mt-4">
                    <label class="form-label">هل العاملة للتسفير ام لنقل الخدمات؟</label>
                    <select name="worker_service_type" id="worker_service_type" class="form-select form-control select2"
                        data-validation="required">
                        <option disabled
                            {{ old('worker_service_type', optional($editingInfo)->worker_service_type) ? '' : 'selected' }}>
                            {{ helperTrans('ingaz.choose') }}</option>
                        <option value="deportation"
                            {{ old('worker_service_type', optional($editingInfo)->worker_service_type) === 'deportation' ? 'selected' : '' }}>
                            التسفير</option>
                        <option value="transfer"
                            {{ old('worker_service_type', optional($editingInfo)->worker_service_type) === 'transfer' ? 'selected' : '' }}>
                            نقل خدمات</option>
                    </select>
                </div>

                <div class="col-4 mt-4" id="instant_display_wrapper" style="display:none;">
                    <label for="is_available_for_instant"
                        class="form-label">{{ helperTrans('ingaz.display_on_site') }}</label>
                    @php
                        $instantAvailability = old(
                            'is_available_for_instant',
                            $editingInfo ? (int) (bool) $editingInfo->is_available_for_instant : 0,
                        );
                    @endphp
                    <select name="is_available_for_instant" id="is_available_for_instant"
                        class="form-select form-control select2">
                        <option value="0" {{ (string) $instantAvailability === '0' ? 'selected' : '' }}>
                            {{ helperTrans('ingaz.no') }}
                        </option>
                        <option value="1" {{ (string) $instantAvailability === '1' ? 'selected' : '' }}>
                            {{ helperTrans('ingaz.yes') }}
                        </option>
                    </select>
                    <small class="form-text text-muted">{{ helperTrans('ingaz.display_on_site_hint') }}</small>
                </div>

                <div class="col-12 mt-4">
                    <label for="shelter_quick_tags" class="form-label">التصنيفات السريعة</label>
                    <input type="text" name="shelter_quick_tags" id="shelter_quick_tags" class="form-control"
                        list="shelterQuickTagsSuggestions"
                        value="{{ old('shelter_quick_tags', implode(', ', $editingQuickTags)) }}"
                        placeholder="مثال: جاهزة للنقل, جاهزة للإيجار, تحت المتابعة">
                    <datalist id="shelterQuickTagsSuggestions">
                        @foreach ($quickTagSuggestions as $tag)
                            <option value="{{ $tag }}"></option>
                        @endforeach
                    </datalist>
                    <small class="form-text text-muted">افصل بين التصنيفات بفاصلة عربية أو إنجليزية. هذه التصنيفات ستظهر في الإحصائيات والفلاتر السريعة بصفحة الإيواء.</small>
                </div>

                <div class="col-12 mt-4">
                    <div class="card border-secondary">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">بيانات العميل (بيانات العميل للقراءة فقط)</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label">اسم العميل</label>
                                    <input readonly type="text" name="client_name_snapshot" id="client_name_snapshot"
                                        class="form-control"
                                        value="{{ old('client_name_snapshot', optional($editingInfo)->client_name_snapshot ?? optional(optional($editingContract)->user)->name) }}">
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label">رقم الهوية الوطنية</label>
                                    <input readonly type="text" name="client_national_id" id="client_national_id"
                                        class="form-control"
                                        value="{{ old('client_national_id', optional($editingInfo)->client_national_id ?? optional(optional($editingContract)->user)->national_num) }}">
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label">اسم العاملة</label>
                                    <input readonly type="text" name="maid_name_snapshot" id="maid_name_snapshot"
                                        class="form-control"
                                        value="{{ old('maid_name_snapshot', optional($editingInfo)->worker_name_snapshot ?? optional(optional($editingContract)->maid)->name) }}">
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label">الجنسية</label>
                                    {{-- <input type="text" name="maid_nationality_snapshot" id="maid_nationality_snapshot" class="form-control" value="{{ old('maid_nationality_snapshot') }}"> --}}
                                    <select readonly name="maid_nationality_snapshot" id="maid_nationality_snapshot"
                                        class="form-select form-control select2">
                                        <option disabled
                                            {{ !old('maid_nationality_snapshot', optional($editingInfo)->worker_nationality_snapshot) ? 'selected' : '' }}>
                                            {{ helperTrans('ingaz.choose') }}</option>
                                        @foreach ($nationalities as $nationality)
                                            <option value="{{ $nationality->id }}"
                                                {{ (string) old('maid_nationality_snapshot', optional($editingInfo)->worker_nationality_snapshot) === (string) $nationality->id ? 'selected' : '' }}>
                                                {{ $nationality->translations->where('locale', app()->getLocale())->first()?->title ?? $nationality->translations->first()?->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label">سعر نقل الكفالة</label>
                                    <input readonly type="number" step="0.01" name="sponsorship_price"
                                        id="sponsorship_price" class="form-control"
                                        value="{{ old('sponsorship_price', optional($editingInfo)->sponsorship_price) }}">
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label">رقم جواز السفر</label>
                                    <input readonly type="text" name="passport_number_snapshot"
                                        id="passport_number_snapshot" class="form-control"
                                        value="{{ old('passport_number_snapshot', optional($editingInfo)->passport_number_snapshot ?? optional(optional($editingContract)->maid)->passport_num) }}">
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label">تاريخ نموذج الاستلام</label>
                                    <input readonly type="date" name="receipt_form_date" id="receipt_form_date"
                                        class="form-control"
                                        value="{{ old('receipt_form_date', optional($editingInfo)->receipt_form_date) }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 mt-4">
                    <label class="form-label">نص النموذج</label>
                    <textarea name="receipt_form_text" id="receipt_form_text" rows="8" class="form-control">{{ old('receipt_form_text', optional($editingInfo)->receipt_form_text ?: $defaultReceiptText) }}</textarea>
                </div>

                <div class="col-12 mt-4">
                    <label for="TextInput" class="form-label">{{ helperTrans('ingaz.back_reasons') }}</label>
                    <textarea name="back_reasons" rows="4" class="form-control" data-validation="required">{{ old('back_reasons', optional($editingInfo)->back_reasons) }}</textarea>
                </div>

                <div class="col-12 mt-4">
                    <label for="attachments" class="form-label">{{ helperTrans('ingaz.shelter_attachments') }}</label>
                    @php
                        $existingAttachments = [];
                        if ($isEditMode && $editingInfo && $editingInfo->shelter_attachments) {
                            $existingAttachments = is_array($editingInfo->shelter_attachments)
                                ? $editingInfo->shelter_attachments
                                : (json_decode($editingInfo->shelter_attachments, true) ?: []);
                        }
                    @endphp
                    @if ($isEditMode && count($existingAttachments))
                        <div class="mb-2">
                            <small class="form-text d-block">المرفقات الحالية:</small>
                            <ul class="mb-2">
                                @foreach ($existingAttachments as $attachment)
                                    @php
                                        $path = $attachment['path'] ?? null;
                                        $name = $attachment['name'] ?? ($path ? basename($path) : '');
                                    @endphp
                                    @if ($path)
                                        <li>
                                            <a href="{{ asset('storage/' . ltrim($path, '/')) }}" target="_blank">
                                                {{ $name }}
                                            </a>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <input type="file" name="attachments[]" id="attachments" class="form-control" multiple
                        accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                    <small class="form-text text-muted">{{ helperTrans('ingaz.shelter_attachments_hint') }}</small>
                </div>
            </div>

            <div class="col-12 modal-footer">
                <button class="btn btn-primary" type="submit">{{ __('buttons.save') }}</button>
                @if ($isEditMode)
                    <a href="{{ route('ShelterContract.index') }}" class="btn btn-secondary">{{ __('buttons.cancel') }}</a>
                @endif
            </div>
        </form>
    @else
        @include('components.no_permissions')
    @endif

    @if (!$isEditMode)
        <!-- Modal لإنشاء عقد جديد -->
        <div class="modal fade" id="createContractModal" tabindex="-1" aria-labelledby="createContractModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="createContractModalLabel">
                            <i class="fas fa-check-circle me-2"></i>{{ helperTrans('ingaz.create_completed_contract') }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="createContractForm">
                            @csrf
                            <div class="row">
                            <!-- نوع العميل -->
                            <div class="col-12 mb-3">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0"><i
                                                class="fas fa-user me-2"></i>{{ helperTrans('ingaz.client_type') }}</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="client_type"
                                                id="existing_client" value="existing" checked>
                                            <label class="form-check-label" for="existing_client">
                                                <i
                                                    class="fas fa-user-check me-1"></i>{{ helperTrans('ingaz.existing_client') }}
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="client_type"
                                                id="new_client" value="new">
                                            <label class="form-check-label" for="new_client">
                                                <i class="fas fa-user-plus me-1"></i>{{ helperTrans('ingaz.new_client') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- اختيار عميل موجود -->
                            <div id="existing_client_section" class="col-12 mb-3">
                                <label class="form-label">{{ helperTrans('ingaz.select_existing_client') }} *</label>
                                <select name="existing_client_id" id="existing_client_id"
                                    class="form-select select2-ajax-client" style="width:100%"></select>
                            </div>

                            <!-- معلومات العميل الجديد -->
                            <div id="new_client_section" class="col-12 mb-3" style="display: none;">
                                <h6>{{ helperTrans('ingaz.client_information') }}</h6>
                            </div>

                            <div class="col-6 new-client-field" style="display: none;">
                                <label class="form-label">{{ helperTrans('ingaz.client_name') }} *</label>
                                <input type="text" name="client_name" class="form-control">
                            </div>

                            <div class="col-6 new-client-field" style="display: none;">
                                <label class="form-label">{{ helperTrans('ingaz.client_phone') }} *</label>
                                <input type="tel" name="client_phone" class="form-control">
                            </div>

                            <div class="col-6 new-client-field" style="display: none;">
                                <label class="form-label">{{ helperTrans('ingaz.email') }}</label>
                                <input type="email" name="client_email" class="form-control">
                            </div>

                            <div class="col-6 new-client-field" style="display: none;">
                                <label class="form-label">{{ helperTrans('ingaz.nationality') }}</label>
                                <select name="client_nationality_id" class="form-select">
                                    <option value="">{{ helperTrans('ingaz.choose') }}</option>
                                    @foreach (\App\Models\ingaz\Nationality::all() as $nationality)
                                        <option value="{{ $nationality->id }}">{{ $nationality->name_ar }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-6 new-client-field" style="display: none;">
                                <label class="form-label">{{ helperTrans('ingaz.national_num') }}</label>
                                <input type="text" name="client_national_num" id="client_national_num"
                                    class="form-control">
                            </div>

                            <div class="col-6 new-client-field" style="display: none;">
                                <label class="form-label">{{ helperTrans('ingaz.birthdate') }}</label>
                                <input type="date" name="client_birthdate" id="client_birthdate"
                                    class="form-control">
                            </div>

                            <div class="col-6 new-client-field" style="display: none;">
                                <label class="form-label">{{ helperTrans('ingaz.city') }}</label>
                                <input type="text" name="city" class="form-control">
                            </div>

                            <div class="col-12 new-client-field" style="display: none;">
                                <label class="form-label">{{ helperTrans('ingaz.address') }}</label>
                                <textarea name="address" class="form-control" rows="2"></textarea>
                            </div>

                            <!-- معلومات العاملة -->
                            <div class="col-12 mb-3 mt-3">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0"><i
                                                class="fas fa-user-tie me-2"></i>{{ helperTrans('ingaz.maid_information') }}
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-6">
                                                <label class="form-label">{{ helperTrans('ingaz.maid') }} *</label>
                                                <select name="maid_id" id="maid_id"
                                                    class="form-select select2-ajax-maid" required
                                                    style="width:100%"></select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- البحث عن العاملة -->
                            <div class="col-12 mb-3">
                                <label class="form-label">{{ helperTrans('ingaz.search_maid') }}</label>
                                <div class="input-group">
                                    <input type="text" id="maid_search" class="form-control"
                                        placeholder="{{ helperTrans('ingaz.enter_passport_or_name') }}">
                                    <button type="button" class="btn btn-outline-secondary" id="search_maid_btn">
                                        <i class="fa fa-search"></i> {{ helperTrans('ingaz.search') }}
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="clear_maid_search">
                                        <i class="fa fa-times"></i> {{ helperTrans('ingaz.clear') }}
                                    </button>
                                </div>
                            </div>

                            <!-- تفاصيل العاملة المختارة -->
                            <div id="maid_details" class="col-12 mb-3" style="display: none;">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0"><i
                                                class="fas fa-info-circle me-2"></i>{{ helperTrans('ingaz.selected_maid_details') }}
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4 col-sm-6 mb-2">
                                                <strong class="text-primary">{{ helperTrans('ingaz.name') }}:</strong>
                                                <span id="maid_name_display" class="ms-2"></span>
                                            </div>
                                            <div class="col-md-4 col-sm-6 mb-2">
                                                <strong
                                                    class="text-primary">{{ helperTrans('ingaz.passport_num') }}:</strong>
                                                <span id="maid_passport_display" class="ms-2"></span>
                                            </div>
                                            <div class="col-md-4 col-sm-6 mb-2">
                                                <strong
                                                    class="text-primary">{{ helperTrans('ingaz.nationality') }}:</strong>
                                                <span id="maid_nationality_display" class="ms-2"></span>
                                            </div>
                                            <div class="col-md-4 col-sm-6 mb-2">
                                                <strong class="text-primary">{{ helperTrans('ingaz.age') }}:</strong>
                                                <span id="maid_age_display" class="ms-2"></span>
                                            </div>
                                            <div class="col-md-4 col-sm-6 mb-2">
                                                <strong class="text-primary">{{ helperTrans('ingaz.salary') }}:</strong>
                                                <span id="maid_salary_display" class="ms-2"></span>
                                            </div>
                                            <div class="col-md-4 col-sm-6 mb-2">
                                                <strong
                                                    class="text-primary">{{ helperTrans('ingaz.experience_years') }}:</strong>
                                                <span id="maid_experience_display" class="ms-2"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- معلومات العقد -->
                            <div class="col-12 mb-3 mt-3">
                                <div class="card border-warning">
                                    <div class="card-header bg-warning text-dark">
                                        <h6 class="mb-0"><i
                                                class="fas fa-file-contract me-2"></i>{{ helperTrans('ingaz.contract_information') }}
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-6">
                                                <label class="form-label">{{ helperTrans('ingaz.contract_date') }}
                                                    *</label>
                                                <input type="date" name="contract_date" class="form-control" required>
                                            </div>

                                            <div class="col-6">
                                                <label class="form-label">{{ helperTrans('ingaz.contract_duration') }}
                                                    *</label>
                                                <input type="number" name="contract_duration" class="form-control"
                                                    placeholder="{{ helperTrans('ingaz.in_months') }}" required>
                                            </div>

                                            <div class="col-6">
                                                <label class="form-label">{{ helperTrans('ingaz.visa_number') }}
                                                    *</label>
                                                <input type="text" name="visa_number" class="form-control" required>
                                            </div>

                                            <div class="col-6">
                                                <label class="form-label">{{ helperTrans('ingaz.msaned_number') }}
                                                    *</label>
                                                <input type="text" name="msaned_number" class="form-control" required>
                                            </div>

                                            <div class="col-6">
                                                <label
                                                    class="form-label">{{ helperTrans('ingaz.tafwed_number') }}</label>
                                                <input type="text" name="tafwed_number" class="form-control">
                                            </div>

                                            <div class="col-6">
                                                <label
                                                    class="form-label">{{ helperTrans('ingaz.visa_milad_date') }}</label>
                                                <input type="date" name="visa_milad_date" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>{{ helperTrans('ingaz.cancel') }}
                        </button>
                        <button type="button" class="btn btn-success" id="createContractBtn">
                            <span class="spinner-border spinner-border-sm d-none" role="status"
                                aria-hidden="true"></span>
                            <i class="fas fa-check-circle me-1"></i>{{ helperTrans('ingaz.create_completed_contract') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

@endsection
@section('js')
    <script>
        $('.select2').select2({
            placeholder: 'Search for an order...',
            allowClear: true
        });
    </script>

    <script>
        var indexRoute = "{{ route('ShelterContract.index') }}";
        var isEditMode = @json($isEditMode);
    </script>

    <script>
        // Passport search functionality
        $(document).ready(function() {
            // Search button click handler
            $('#search_passport_btn').on('click', function() {
                searchContractsByPassport();
            });

            // Enter key press handler for search input
            $('#passport_search').on('keypress', function(e) {
                if (e.which === 13) { // Enter key
                    searchContractsByPassport();
                }
            });

            // Clear search button click handler
            $('#clear_search_btn').on('click', function() {
                $('#passport_search').val('');
                loadAllContracts();
            });

            // Function to search contracts by passport
            function searchContractsByPassport() {
                let passportNumber = $('#passport_search').val().trim();

                if (!passportNumber) {
                    loadAllContracts();
                    return;
                }

                $.ajax({
                    url: "{{ route('search.contracts.by.passport') }}",
                    type: 'GET',
                    data: {
                        passport_number: passportNumber
                    },
                    beforeSend: function() {
                        $('#search_passport_btn').html(
                            '<i class="fa fa-spinner fa-spin"></i> {{ helperTrans('ingaz.searching') }}'
                            );
                    },
                    success: function(response) {
                        updateContractDropdown(response.contracts);
                        $('#search_passport_btn').html(
                            '<i class="fa fa-search"></i> {{ helperTrans('ingaz.search') }}');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error searching contracts:', error);
                        $('#search_passport_btn').html(
                            '<i class="fa fa-search"></i> {{ helperTrans('ingaz.search') }}');
                        // Show error message
                        alert('{{ helperTrans('ingaz.error_searching_contracts') }}');
                    }
                });
            }

            // Function to update contract dropdown with search results
            function updateContractDropdown(contracts) {
                let $select = $('#contract_id');
                $select.empty();
                $select.append('<option disabled selected>{{ helperTrans('ingaz.choose') }}</option>');

                if (contracts.length === 0) {
                    $select.append('<option disabled>{{ helperTrans('ingaz.no_contracts_found') }}</option>');
                } else {
                    contracts.forEach(function(contract) {
                        let optionText = contract.desc;
                        if (contract.passport_number) {
                            optionText += ' - ' + contract.passport_number;
                        }

                        $select.append(
                            '<option value="' + contract.id + '" ' +
                            'data-passport="' + (contract.passport_number || '') + '" ' +
                            'data-maid-name="' + (contract.maid_name || '') + '">' +
                            optionText +
                            '</option>'
                        );
                    });
                }

                // Reinitialize Select2
                $select.select2('destroy').select2({
                    placeholder: '{{ helperTrans('ingaz.choose') }}',
                    allowClear: true
                });
            }

            // Function to load all contracts (clear search)
            function loadAllContracts() {
                $.ajax({
                    url: "{{ route('ShelterContract.create') }}",
                    type: 'GET',
                    success: function(response) {
                        // Extract the contracts from the response
                        // This is a simplified approach - in a real implementation,
                        // you might want to create a separate endpoint for getting all contracts
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading contracts:', error);
                        location.reload();
                    }
                });
            }
        });
    </script>

    <script>
        // Calculate duration from enter automatically
        $(document).ready(function() {
            console.log('=== Document Ready - ShelterContract Create ===');

            // تهيئة Select2
            $('.select2').select2({
                placeholder: '{{ helperTrans('ingaz.choose') }}',
                allowClear: true
            });

            // عند اختيار العقد، املأ تاريخ الوصول
            $('#contract_id').on('change', function() {
                let contractId = $(this).val();
                console.log('تم اختيار العقد:', contractId);

                if (contractId) {
                    // البحث عن العقد في البيانات المتاحة
                    @foreach ($contracts as $contract)
                        if ({{ $contract->id }} == contractId) {
                            @if ($contract->info && $contract->info->arrival_date)
                                $('#arrival_date').val('{{ $contract->info->arrival_date }}');
                                console.log('تم ملء تاريخ الوصول: {{ $contract->info->arrival_date }}');
                                // حساب المدة من تاريخ الوصول
                                calculateDurationFromArrivalDate('{{ $contract->info->arrival_date }}');
                            @elseif ($contract->maid && $contract->maid->arrival_date)
                                $('#arrival_date').val('{{ $contract->maid->arrival_date }}');
                                console.log(
                                    'تم ملء تاريخ الوصول من العاملة: {{ $contract->maid->arrival_date }}'
                                    );
                                // حساب المدة من تاريخ الوصول
                                calculateDurationFromArrivalDate('{{ $contract->maid->arrival_date }}');
                            @else
                                $('#arrival_date').val('');
                                console.log('لا يوجد تاريخ وصول للعقد');
                                $('input[name="duration_from_enter"]').val('');
                            @endif
                        }
                    @endforeach
                }

                const selectedOption = $(this).find('option:selected');
                if (selectedOption.length) {
                    fillSnapshotFields(selectedOption.data());
                }
            });

            // حساب المدة عند تغيير تاريخ الوصول
            $('#arrival_date').on('change', function() {
                let arrivalDate = $(this).val();
                calculateDurationFromArrivalDate(arrivalDate);
            });

            // دالة حساب المدة من تاريخ الوصول
            function calculateDurationFromArrivalDate(arrivalDate) {
                if (arrivalDate) {
                    let arrivalDateObj = new Date(arrivalDate);
                    let today = new Date();
                    let diffTime = Math.abs(today - arrivalDateObj);
                    let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                    // تنسيق المدة بشكل أوضح
                    let durationText = '';
                    if (diffDays === 0) {
                        durationText = 'اليوم';
                    } else if (diffDays === 1) {
                        durationText = 'يوم واحد';
                    } else if (diffDays < 30) {
                        durationText = diffDays + ' يوم';
                    } else if (diffDays < 365) {
                        let months = Math.floor(diffDays / 30);
                        let remainingDays = diffDays % 30;
                        durationText = months + ' شهر';
                        if (remainingDays > 0) {
                            durationText += ' و ' + remainingDays + ' يوم';
                        }
                    } else {
                        let years = Math.floor(diffDays / 365);
                        let remainingDays = diffDays % 365;
                        durationText = years + ' سنة';
                        if (remainingDays > 0) {
                            let months = Math.floor(remainingDays / 30);
                            durationText += ' و ' + months + ' شهر';
                        }
                    }

                    $('input[name="duration_from_enter"]').val(durationText);

                    // إضافة تنبيه بصري للمستخدم
                    $('.duration-alert').remove();

                    // إضافة لون للحقل
                    $('input[name="duration_from_enter"]').addClass('border-success').removeClass(
                        'border-warning border-danger');
                } else {
                    $('input[name="duration_from_enter"]').val('');
                    $('.duration-alert').remove();
                    $('input[name="duration_from_enter"]').removeClass('border-success border-warning').addClass(
                        'border-danger');
                }
            }

            // تعيين تاريخ الإرجاع في نفس اليوم تلقائياً
            function fillSnapshotFields(data, preserveExisting = false) {
                console.log(data);

                const setValue = (selector, value) => {
                    if (preserveExisting && $(selector).val()) {
                        return;
                    }
                    $(selector).val(value || '');
                };

                setValue('#client_name_snapshot', data.clientName);
                setValue('#client_national_id', data.clientNationalId);
                setValue('#maid_name_snapshot', data.maidNameSnapshot || data.maidName);
                setValue('#maid_nationality_snapshot', data.maidNationality);
                setValue('#passport_number_snapshot', data.passportSnapshot || data.passport);
                setValue('#sponsorship_price', data.sponsorshipPrice);
                setValue('#receipt_form_date', data.receiptDate);

                // if (!preserveExisting || !$('#receipt_form_text').val()) {
                //     $('#receipt_form_text').val(data.receiptText || '');
                // }

                if (data.hasResidency || (!preserveExisting && data.hasResidency === '')) {
                    $('#workers_have_residency').val(data.hasResidency || null).trigger('change');
                } else if (!preserveExisting) {
                    $('#workers_have_residency').val(null).trigger('change');
                }

                if (data.medicalExam || (!preserveExisting && data.medicalExam === '')) {
                    $('#performing_medical_examination').val(data.medicalExam || null).trigger('change');
                } else if (!preserveExisting) {
                    $('#performing_medical_examination').val(null).trigger('change');
                }

                if (data.serviceType || (!preserveExisting && data.serviceType === '')) {
                    $('#worker_service_type').val(data.serviceType || null).trigger('change');
                } else if (!preserveExisting) {
                    $('#worker_service_type').val(null).trigger('change');
                }
            }

            function setReturnDateToToday() {
                let today = new Date();
                let year = today.getFullYear();
                let month = String(today.getMonth() + 1).padStart(2, '0');
                let day = String(today.getDate()).padStart(2, '0');
                let todayString = year + '-' + month + '-' + day;

                $('input[name="maid_date_back"]').val(todayString);
                console.log('تم تعيين تاريخ الإرجاع إلى اليوم:', todayString);
            }

            // تعيين تاريخ الإرجاع عند تحميل الصفحة
            $(document).ready(function() {
                if (!isEditMode || !$('input[name="maid_date_back"]').val()) {
                    setReturnDateToToday();
                }
                toggleInstantDisplayField();
                calculateDurationFromArrivalDate($('#arrival_date').val());

                const initialOption = $('#contract_id').find('option:selected');
                if (initialOption.length && initialOption.val()) {
                    fillSnapshotFields(initialOption.data(), true);
                }
            });

            // تعيين تاريخ الإرجاع عند تغيير العقد
            $('#contract_id').on('change', function() {
                if (!isEditMode) {
                    setReturnDateToToday();
                }
            });

            $('select[name="wants_to_work"]').on('change', function() {
                toggleInstantDisplayField();
            });

            function toggleInstantDisplayField() {
                const wantsValue = $('select[name="wants_to_work"]').val();
                if (wantsValue === 'yes') {
                    $('#instant_display_wrapper').slideDown(200);
                } else {
                    $('#instant_display_wrapper').slideUp(200);
                    $('#is_available_for_instant').val('0').trigger('change');
                }
            }
        });
    </script>

    <script>
        $(document).on('submit', '.contractForm', function(e) {
            e.preventDefault();
            let formData = new FormData(this);
            let url = $(this).attr('action');
            let submitButton = $(e.originalEvent.submitter);
            let buttonText = submitButton.text();
            let modalResult = $("#createModal");
            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    $('#loading-wrapper').fadeIn();
                    submitButton.html(
                        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' +
                        buttonText)
                },
                complete: function() {
                    $('#loading-wrapper').fadeOut();
                },
                success: function(result) {
                    console.log(200)
                    window.location.href = indexRoute;
                    submitButton.html(buttonText)
                    if (modalResult.is(":visible")) {
                        modalResult.modal('toggle');
                    }
                    console.log(modalResult.is(':visible'));
                },
                error: function(errorObj, errorText, errorThrown) {
                    console.log(500)
                    console.log(errorObj)
                    handleErrorResponce(errorObj);
                }
            });
        });
    </script>

    <script>
        // إنشاء عقد جديد
        $(document).ready(function() {
            // تهيئة Select2 العادية للحقول الأخرى
            $('.select2').select2({
                placeholder: '{{ helperTrans('ingaz.choose') }}',
                allowClear: true
            });

            // إضافة event handler للـ contract_id عند التغيير العادي
            $('#contract_id').on('change', function() {
                let contractId = $(this).val();
                if (contractId) {
                    // جلب بيانات العقد من الخيار المحدد
                    let selectedOption = $(this).find('option:selected');
                    let contractData = selectedOption.data();

                    // البحث عن العقد في البيانات المتاحة
                    @foreach ($contracts as $contract)
                        if ({{ $contract->id }} == contractId) {
                            @if ($contract->info && $contract->info->arrival_date)
                                $('#arrival_date').val('{{ $contract->info->arrival_date }}');
                                console.log('تم ملء تاريخ الوصول: {{ $contract->info->arrival_date }}');
                            @elseif ($contract->maid && $contract->maid->arrival_date)
                                $('#arrival_date').val('{{ $contract->maid->arrival_date }}');
                                console.log(
                                    'تم ملء تاريخ الوصول من العاملة: {{ $contract->maid->arrival_date }}'
                                    );
                            @else
                                $('#arrival_date').val('');
                                console.log('لا يوجد تاريخ وصول للعقد');
                            @endif
                        }
                    @endforeach
                }
            });

            // Select2 AJAX للعملاء
            $('#existing_client_id').select2({
                placeholder: '{{ helperTrans('ingaz.choose') }}',
                allowClear: true,
                ajax: {
                    url: '{{ route('Contract.searchClients') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            search: params.term || ''
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results || []
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1,
                language: {
                    noResults: function() {
                        return "{{ helperTrans('ingaz.no_results_found') }}";
                    },
                    searching: function() {
                        return "{{ helperTrans('ingaz.searching') }}";
                    }
                }
            });

            // Select2 AJAX للعاملة
            $('#maid_id').select2({
                placeholder: '{{ helperTrans('ingaz.choose') }}',
                allowClear: true,
                ajax: {
                    url: '{{ route('Contract.searchMaids') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            search: params.term || ''
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.maids.map(function(maid) {
                                return {
                                    id: maid.id,
                                    text: maid.name + ' - ' + maid.passport_num + (maid
                                        .nationality ? ' - ' + maid.nationality : ''),
                                    maid: maid
                                };
                            })
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1,
                language: {
                    noResults: function() {
                        return "{{ helperTrans('ingaz.no_maids_found') }}";
                    },
                    searching: function() {
                        return "{{ helperTrans('ingaz.searching') }}";
                    }
                }
            });

            // عند اختيار العاملة، عرض التفاصيل
            $('#maid_id').on('select2:select', function(e) {
                var maid = e.params.data.maid;
                console.log(maid);

                if (maid) {
                    $('#maid_name_display').text(maid.name);
                    $('#maid_passport_display').text(maid.passport_num);
                    $('#maid_nationality_display').text(maid.nationality || 'غير محدد');
                    $('#maid_age_display').text(maid.age || 'غير محدد');
                    $('#maid_salary_display').text(maid.salary || 'غير محدد');
                    $('#maid_experience_display').text(maid.experience_years || 'غير محدد');
                    $('#maid_details').show();
                }
            });

            $('#maid_id').on('select2:clear', function(e) {
                $('#maid_details').hide();
            });

            // استيراد بيانات العميل عند اختياره
            $('#existing_client_id').on('select2:select', function(e) {
                var client = e.params.data;
                if (client) {
                    // استيراد رقم الهوية
                    if (client.national_num) {
                        $('#client_national_num').val(client.national_num);
                    }

                    // استيراد تاريخ الميلاد
                    if (client.birthdate) {
                        $('#client_birthdate').val(client.birthdate);
                    }
                }
            });

            // إعادة تهيئة Select2 عند فتح المودال
            $('#createContractModal').on('shown.bs.modal', function() {
                $('#existing_client_id').select2('destroy').select2({
                    placeholder: '{{ helperTrans('ingaz.choose') }}',
                    allowClear: true,
                    ajax: {
                        url: '{{ route('Contract.searchClients') }}',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                search: params.term || ''
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data.results || []
                            };
                        },
                        cache: true
                    },
                    minimumInputLength: 1
                });

                // استيراد بيانات العميل عند اختياره في المودال
                $('#existing_client_id').off('select2:select').on('select2:select', function(e) {
                    var client = e.params.data;
                    if (client) {
                        // استيراد رقم الهوية
                        if (client.national_num) {
                            $('#client_national_num').val(client.national_num);
                        }

                        // استيراد تاريخ الميلاد
                        if (client.birthdate) {
                            $('#client_birthdate').val(client.birthdate);
                        }
                    }
                });

                $('#maid_id').select2('destroy').select2({
                    placeholder: '{{ helperTrans('ingaz.choose') }}',
                    allowClear: true,
                    ajax: {
                        url: '{{ route('Contract.searchMaids') }}',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                search: params.term || ''
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data.maids.map(function(maid) {
                                    return {
                                        id: maid.id,
                                        text: maid.name + ' - ' + maid.passport_num + (
                                            maid.nationality ? ' - ' + maid
                                            .nationality : ''),
                                        maid: maid
                                    };
                                })
                            };
                        },
                        cache: true
                    },
                    minimumInputLength: 1
                });
            });

            // تبديل نوع العميل
            $('input[name="client_type"]').on('change', function() {
                let clientType = $(this).val();

                if (clientType === 'existing') {
                    $('#existing_client_section').show();
                    $('.new-client-field').hide();
                    $('#new_client_section').hide();

                    // إزالة required من حقول العميل الجديد
                    $('.new-client-field input, .new-client-field select').prop('required', false);
                    $('#existing_client_id').prop('required', true);
                } else {
                    $('#existing_client_section').hide();
                    $('.new-client-field').show();
                    $('#new_client_section').show();

                    // إضافة required لحقول العميل الجديد
                    $('.new-client-field input[name="client_name"], .new-client-field input[name="client_phone"]')
                        .prop('required', true);
                    $('#existing_client_id').prop('required', false);
                }
            });

            // البحث عن العاملة
            $('#search_maid_btn').on('click', function() {
                searchMaids();
            });

            $('#maid_search').on('keypress', function(e) {
                if (e.which === 13) { // Enter key
                    searchMaids();
                }
            });

            $('#clear_maid_search').on('click', function() {
                $('#maid_search').val('');
                loadAllMaids();
            });

            // دالة البحث عن العمالة
            function searchMaids() {
                let searchTerm = $('#maid_search').val().trim();

                if (!searchTerm) {
                    loadAllMaids();
                    return;
                }

                $.ajax({
                    url: "{{ route('Contract.searchMaids') }}",
                    type: 'GET',
                    data: {
                        search: searchTerm
                    },
                    beforeSend: function() {
                        $('#search_maid_btn').html(
                            '<i class="fa fa-spinner fa-spin"></i> {{ helperTrans('ingaz.searching') }}'
                            );
                    },
                    success: function(response) {
                        updateMaidDropdown(response.maids);
                        $('#search_maid_btn').html(
                            '<i class="fa fa-search"></i> {{ helperTrans('ingaz.search') }}');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error searching maids:', error);
                        $('#search_maid_btn').html(
                            '<i class="fa fa-search"></i> {{ helperTrans('ingaz.search') }}');
                        Swal.fire({
                            icon: 'error',
                            title: '{{ helperTrans('ingaz.error') }}',
                            text: '{{ helperTrans('ingaz.error_searching_maids') }}',
                            confirmButtonText: '{{ helperTrans('ingaz.ok') }}'
                        });
                    }
                });
            }

            // دالة تحديث قائمة العمالة
            function updateMaidDropdown(maids) {
                let $select = $('#maid_id');
                $select.empty();
                $select.append('<option value="">{{ helperTrans('ingaz.choose') }}</option>');

                if (maids.length === 0) {
                    $select.append('<option disabled>{{ helperTrans('ingaz.no_maids_found') }}</option>');
                } else {
                    maids.forEach(function(maid) {
                        let optionText = maid.name + ' - ' + maid.passport_num;
                        if (maid.nationality) {
                            optionText += ' - ' + maid.nationality;
                        }

                        $select.append(
                            '<option value="' + maid.id + '" ' +
                            'data-name="' + maid.name + '" ' +
                            'data-passport="' + maid.passport_num + '" ' +
                            'data-nationality="' + (maid.nationality || '') + '" ' +
                            'data-age="' + maid.age + '" ' +
                            'data-salary="' + maid.salary + '" ' +
                            'data-experience="' + maid.experience_years + '">' +
                            optionText +
                            '</option>'
                        );
                    });
                }

                // إعادة تهيئة Select2
                $select.select2('destroy').select2({
                    placeholder: '{{ helperTrans('ingaz.choose') }}',
                    allowClear: true
                });
            }

            // دالة تحميل جميع العمالة
            function loadAllMaids() {
                $.ajax({
                    url: "{{ route('Contract.getAllMaids') }}",
                    type: 'GET',
                    success: function(response) {
                        updateMaidDropdown(response.maids);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading maids:', error);
                        location.reload();
                    }
                });
            }

            $('#createContractBtn').on('click', function() {
                let form = $('#createContractForm');
                let formData = new FormData(form[0]);
                let button = $(this);
                let originalText = button.text();
                let spinner = button.find('.spinner-border');

                // التحقق من صحة النموذج
                if (!form[0].checkValidity()) {
                    form[0].reportValidity();
                    return;
                }

                // التحقق من نوع العميل
                let clientType = $('input[name="client_type"]:checked').val();
                if (clientType === 'existing' && !$('#existing_client_id').val()) {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ helperTrans('ingaz.error') }}',
                        text: '{{ helperTrans('ingaz.please_select_client') }}',
                        confirmButtonText: '{{ helperTrans('ingaz.ok') }}'
                    });
                    return;
                }

                // إظهار spinner
                button.prop('disabled', true);
                spinner.removeClass('d-none');

                $.ajax({
                    url: "{{ route('Contract.createCompleted') }}",
                    type: 'POST',
                    data: formData,
                    cache: false,
                    contentType: false,
                    processData: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            // إضافة العقد الجديد إلى القائمة
                            let newOption = new Option(
                                response.contract.desc + ' - ' + response.contract.maid
                                .passport_num,
                                response.contract.id,
                                true,
                                true
                            );
                            $('#contract_id').append(newOption).trigger('change');

                            // إغلاق modal
                            $('#createContractModal').modal('hide');

                            // إظهار رسالة نجاح
                            Swal.fire({
                                icon: 'success',
                                title: '{{ helperTrans('ingaz.success') }}',
                                text: response.message,
                                confirmButtonText: '{{ helperTrans('ingaz.ok') }}'
                            });

                            // إعادة تعيين النموذج
                            form[0].reset();
                            $('#maid_details').hide();
                            $('.new-client-field').hide();
                            $('#existing_client_section').show();
                            $('#new_client_section').hide();
                            $('input[name="client_type"][value="existing"]').prop('checked',
                                true);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ helperTrans('ingaz.error') }}',
                                text: response.message,
                                confirmButtonText: '{{ helperTrans('ingaz.ok') }}'
                            });
                        }
                    },
                    error: function(xhr) {
                        let errorMessage =
                            '{{ helperTrans('ingaz.error_creating_contract') }}';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: '{{ helperTrans('ingaz.error') }}',
                            text: errorMessage,
                            confirmButtonText: '{{ helperTrans('ingaz.ok') }}'
                        });
                    },
                    complete: function() {
                        // إخفاء spinner
                        button.prop('disabled', false);
                        spinner.addClass('d-none');
                    }
                });
            });

            // إعادة تعيين النموذج عند إغلاق modal
            $('#createContractModal').on('hidden.bs.modal', function() {
                $('#createContractForm')[0].reset();
                $('#maid_details').hide();
                $('.new-client-field').hide();
                $('#existing_client_section').show();
                $('#new_client_section').hide();
                $('input[name="client_type"][value="existing"]').prop('checked', true);
                $('#maid_search').val('');
                loadAllMaids();
            });
        });
    </script>
@endsection
