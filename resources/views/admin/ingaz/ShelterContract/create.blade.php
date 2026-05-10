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
        (!$isEditMode && @AdminGuardHasAnyPerm([\App\PermKeys::SHELTER_CONTRACT_ADDITION_CREATE, \App\PermKeys::SHELTER_CONTRACT_CREATE, \App\PermKeys::SHELTER_CONTRACT_CONTROL])) ||
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
                            <h5 class="card-title mb-2">
                                {{ helperTrans('ingaz.quick_create_contract') }}
                            </h5>
                            <p class="card-text mb-3">إضافة عقد مكتمل مختصر ثم توجيهه مباشرة إلى الإيواء.</p>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createContractModal">
                                    <i class="fas fa-plus me-1"></i>
                                    {{ helperTrans('ingaz.add_completed_contract') }}
                                </button>
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
                                $maidName = optional($contract->info)->manual_maid_name ?: ($contract->maid->name ?? '');
                                $passportNumber = optional($contract->info)->manual_maid_passport_num ?: ($contract->maid->passport_num ?? '');
                                $clientName = $contract->user->name ?? '';
                                $clientNationalId = $contract->user->national_num ?? '';
                                $arrivalDate = optional($contract->info)->arrival_date
                                    ?: optional($contract->maid)->arrival_date
                                    ?: optional($contract->travelHistory)->arrival_date
                                    ?: '';
                                if ($arrivalDate instanceof \Carbon\CarbonInterface) {
                                    $arrivalDate = $arrivalDate->format('Y-m-d');
                                }
                                $warrantyStatus = '';
                                if ($contract->status === \App\Enums\ingaz\ContractStatusEnum::Completed->value) {
                                    $warrantyStatus = ' (مكتمل)';
                                } elseif ($contract->status === \App\Enums\ingaz\ContractStatusEnum::Arrival->value) {
                                    $warrantyStatus = ' (مرحلة الوصول)';
                                } elseif (
                                    $contract->status === \App\Enums\ingaz\ContractStatusEnum::UnderWarranty->value
                                ) {
                                    $warrantyStatus = ' (تحت الضمان)';
                                } elseif ($contract->status === \App\Enums\ingaz\ContractStatusEnum::Closed->value) {
                                    $warrantyStatus = ' (مكتمل)';
                                }
                            @endphp
                            <option value="{{ $contract->id }}"
                                data-passport="{{ $passportNumber }}"
                                data-passport-snapshot="{{ $passportNumber }}"
                                data-maid-name="{{ $maidName }}"
                                data-maid-name-snapshot="{{ $maidName }}"
                                data-maid-nationality="{{ $contract->maid?->nationality_id ?? '' }}"
                                data-client-name="{{ $clientName }}"
                                data-client-national-id="{{ $clientNationalId }}"
                                data-sponsorship-price="{{ optional($contract->info)->sponsorship_price }}"
                                data-receipt-date="{{ optional($contract->info)->receipt_form_date ?? ($arrivalDate ?: ($contract->contract_date ?? '')) }}"
                                data-receipt-text="{{ optional($contract->info)->receipt_form_text }}"
                                data-has-residency="{{ optional($contract->info)->workers_have_residency }}"
                                data-medical-exam="{{ optional($contract->info)->performing_medical_examination }}"
                                data-service-type="{{ optional($contract->info)->worker_service_type }}"
                                data-arrival-date="{{ $arrivalDate }}"
                                data-status="{{ $contract->status }}"
                                {{ $selectedContractId == $contract->id ? 'selected' : '' }}>
                                {{ $contract->desc }}{{ $warrantyStatus }}
                                @if ($maidName)
                                    - {{ $maidName }}
                                @endif
                                @if ($passportNumber)
                                    - {{ $passportNumber }}
                                @endif
                                @if ($clientNationalId)
                                    - {{ $clientNationalId }}
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
                    <select name="salary_final_status" class="form-select form-control select2">
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
                    <select name="wants_to_work" class="form-select form-control select2">
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
                        class="form-select form-control select2">
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
                        class="form-select form-control select2">
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
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">بيانات العميل (بيانات العميل للقراءة فقط)</h6>
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse"
                                data-bs-target="#clientSnapshotCollapse" aria-expanded="false"
                                aria-controls="clientSnapshotCollapse">
                                عرض/إخفاء
                            </button>
                        </div>
                        <div class="card-body collapse" id="clientSnapshotCollapse">
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
                    <div class="card border-secondary">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">نص النموذج</h6>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary" type="button" id="printReceiptFormBtn">
                                    <i class="fas fa-print me-1"></i>طباعة
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#receiptFormTextCollapse" aria-expanded="false"
                                    aria-controls="receiptFormTextCollapse">
                                    عرض/إخفاء
                                </button>
                            </div>
                        </div>
                        <div class="card-body collapse" id="receiptFormTextCollapse">
                            <textarea name="receipt_form_text" id="receipt_form_text" rows="8" class="form-control">{{ old('receipt_form_text', optional($editingInfo)->receipt_form_text ?: $defaultReceiptText) }}</textarea>
                        </div>
                    </div>
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
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label d-block">العميل</label>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="client_type" id="existing_client" value="existing" checked>
                                        <label class="form-check-label" for="existing_client">اختيار عميل</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="client_type" id="new_client" value="new">
                                        <label class="form-check-label" for="new_client">إضافة عميل</label>
                                    </div>
                                </div>
                                <div id="existing_client_section" class="col-12">
                                    <select name="existing_client_id" id="existing_client_id" class="form-select select2-ajax-client" style="width:100%"></select>
                                </div>
                                <div class="col-md-6 new-client-field" style="display:none;">
                                    <label class="form-label">اسم العميل *</label>
                                    <input type="text" name="client_name" class="form-control">
                                </div>
                                <div class="col-md-6 new-client-field" style="display:none;">
                                    <label class="form-label">جوال العميل *</label>
                                    <input type="tel" name="client_phone" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">رقم هوية العميل</label>
                                    <input type="text" name="client_national_num" id="client_national_num" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">تاريخ ميلاد العميل</label>
                                    <input type="date" name="client_birthdate" id="client_birthdate" class="form-control">
                                </div>
                                <div class="col-12 new-client-field" style="display:none;">
                                    <label class="form-label">العنوان</label>
                                    <textarea name="address" class="form-control" rows="2"></textarea>
                                </div>

                                <div class="col-12 border-top pt-3">
                                    <label class="form-label d-block">العاملة</label>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="maid_type" id="existing_maid" value="existing" checked>
                                        <label class="form-check-label" for="existing_maid">اختيار عاملة</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="maid_type" id="new_maid" value="new">
                                        <label class="form-check-label" for="new_maid">إضافة عاملة</label>
                                    </div>
                                </div>
                                <div id="existing_maid_section" class="col-12">
                                    <select name="maid_id" id="maid_id" class="form-select select2-ajax-maid" style="width:100%"></select>
                                </div>
                                <div class="col-md-6 new-maid-field" style="display:none;">
                                    <label class="form-label">اسم العاملة *</label>
                                    <input type="text" name="new_maid_name" class="form-control">
                                </div>
                                <div class="col-md-6 new-maid-field" style="display:none;">
                                    <label class="form-label">رقم الجواز *</label>
                                    <input type="text" name="new_maid_passport" class="form-control">
                                </div>
                                <div class="col-md-6 new-maid-field" style="display:none;">
                                    <label class="form-label">الجنسية *</label>
                                    <select name="new_maid_nationality_id" class="form-select">
                                        <option value="">{{ helperTrans('ingaz.choose') }}</option>
                                        @foreach ($nationalities as $nationality)
                                            <option value="{{ $nationality->id }}">
                                                {{ $nationality->translations->where('locale', app()->getLocale())->first()?->title ?? $nationality->translations->first()?->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 new-maid-field" style="display:none;">
                                    <label class="form-label">الراتب</label>
                                    <input type="number" name="new_maid_salary" class="form-control" min="0" step="0.01">
                                </div>
                                <div class="col-md-3 new-maid-field" style="display:none;">
                                    <label class="form-label">العمر</label>
                                    <input type="number" name="new_maid_age" class="form-control" min="0" step="1">
                                </div>

                                <div class="col-md-4 border-top pt-3">
                                    <label class="form-label">تاريخ العقد *</label>
                                    <input type="date" name="contract_date" class="form-control" required>
                                </div>
                                <div class="col-md-4 border-top pt-3">
                                    <label class="form-label">تاريخ الوصول *</label>
                                    <input type="date" name="arrival_date" class="form-control" required>
                                </div>
                                <div class="col-md-4 border-top pt-3">
                                    <label class="form-label">رقم التأشيرة *</label>
                                    <input type="text" name="visa_number" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">رقم عقد مساند</label>
                                    <input type="text" name="msaned_number" class="form-control">
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

                        $('<option>')
                            .val(contract.id)
                            .text(optionText)
                            .attr({
                                'data-passport': contract.passport_number || '',
                                'data-passport-snapshot': contract.passport_snapshot || contract.passport_number || '',
                                'data-maid-name': contract.maid_name || '',
                                'data-maid-name-snapshot': contract.maid_name_snapshot || contract.maid_name || '',
                                'data-maid-nationality': contract.maid_nationality_snapshot || '',
                                'data-client-name': contract.client_name || '',
                                'data-client-national-id': contract.client_national_id || '',
                                'data-sponsorship-price': contract.sponsorship_price || '',
                                'data-receipt-date': contract.receipt_date || '',
                                'data-receipt-text': contract.receipt_text || '',
                                'data-has-residency': contract.has_residency || '',
                                'data-medical-exam': contract.medical_exam || '',
                                'data-service-type': contract.service_type || '',
                                'data-arrival-date': contract.arrival_date || '',
                                'data-status': contract.status || ''
                            })
                            .appendTo($select);
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
                    const selectedOption = $(this).find('option:selected');
                    const selectedArrivalDate = selectedOption.data('arrivalDate');
                    if (selectedArrivalDate) {
                        $('#arrival_date').val(selectedArrivalDate);
                        calculateDurationFromArrivalDate(selectedArrivalDate);
                    }

                    // البحث عن العقد في البيانات المتاحة
                    @foreach ($contracts as $contract)
                        if (!selectedArrivalDate && {{ $contract->id }} == contractId) {
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

                const initialOption = $('#contract_id').find('option:selected');
                if (initialOption.length && initialOption.val()) {
                    const initialArrivalDate = initialOption.data('arrivalDate');
                    if (initialArrivalDate && !$('#arrival_date').val()) {
                        $('#arrival_date').val(initialArrivalDate);
                    }
                    fillSnapshotFields(initialOption.data(), true);
                }

                calculateDurationFromArrivalDate($('#arrival_date').val());
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
        $(document).on('click', '#printReceiptFormBtn', function() {
            const receiptText = $('<div>').text($('#receipt_form_text').val() || '').html().replace(/\n/g, '<br>');
            const printWindow = window.open('', '_blank', 'width=900,height=700');
            if (!printWindow) {
                return;
            }

            const printHtml = ''
                + '\x3C!doctype html>'
                + '\x3Chtml dir="rtl" lang="ar">'
                + '\x3Chead>'
                + '\x3Cmeta charset="UTF-8">'
                + '\x3Ctitle>نموذج الإيواء\x3C/title>'
                + '\x3Cstyle>'
                + 'body{font-family:Tahoma,Arial,sans-serif;padding:32px;line-height:2;color:#111;}'
                + 'h1{font-size:22px;margin:0 0 24px;text-align:center;}'
                + '.content{font-size:16px;white-space:normal;}'
                + '\x3C/style>'
                + '\x3C/head>'
                + '\x3Cbody>'
                + '\x3Ch1>نموذج الإيواء\x3C/h1>'
                + '\x3Cdiv class="content">' + receiptText + '\x3C/div>'
                + '\x3C/body>'
                + '\x3C/html>';

            printWindow.document.write(printHtml);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
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
                    if (contractData.arrivalDate) {
                        $('#arrival_date').val(contractData.arrivalDate);
                        $('#arrival_date').trigger('change');
                        return;
                    }

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

            function initCompletedContractClientSelect() {
                $('#existing_client_id').select2({
                    dropdownParent: $('#createContractModal'),
                    placeholder: '{{ helperTrans('ingaz.choose') }}',
                    allowClear: true,
                    ajax: {
                        url: '{{ route('Contract.searchClients') }}',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                search: params.term || '',
                                page: params.page || 1
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data.results || [],
                                pagination: data.pagination || {
                                    more: false
                                }
                            };
                        },
                        cache: true
                    },
                    language: {
                        noResults: function() {
                            return "{{ helperTrans('ingaz.no_results_found') }}";
                        },
                        searching: function() {
                            return "{{ helperTrans('ingaz.searching') }}";
                        }
                    }
                });
            }

            function initCompletedContractMaidSelect() {
                $('#maid_id').select2({
                    dropdownParent: $('#createContractModal'),
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
                                results: (data.maids || []).map(function(item) {
                                    return {
                                        id: item.id,
                                        text: item.text,
                                        maid: item.maid || item
                                    };
                                })
                            };
                        },
                        cache: true
                    },
                    language: {
                        noResults: function() {
                            return "{{ helperTrans('ingaz.no_maids_found') }}";
                        },
                        searching: function() {
                            return "{{ helperTrans('ingaz.searching') }}";
                        }
                    }
                });
            }

            initCompletedContractClientSelect();
            initCompletedContractMaidSelect();

            $('#maid_id').on('select2:open', function() {
                if ($(this).find('option').length > 1) {
                    return;
                }

                $.ajax({
                    url: '{{ route('Contract.searchMaids') }}',
                    dataType: 'json',
                    data: {
                        search: ''
                    },
                    success: function(data) {
                        var $select = $('#maid_id');
                        $select.empty().append(new Option('{{ helperTrans('ingaz.choose') }}', '', true, false));

                        (data.maids || []).forEach(function(item) {
                            var option = new Option(item.text, item.id, false, false);
                            $(option).data('data', {
                                id: item.id,
                                text: item.text,
                                maid: item.maid || item
                            });
                            $select.append(option);
                        });
                    }
                });
            });

            // عند اختيار العاملة، عرض التفاصيل
            $('#maid_id').on('select2:select', function(e) {
                var selectedMaid = e.params.data;
                var maid = selectedMaid.maid || ($(selectedMaid.element).data('data') || {}).maid;
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
                var selectedClient = e.params.data;
                var client = $(selectedClient.element).data('data') || selectedClient;
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
                if ($('#existing_client_id').hasClass('select2-hidden-accessible')) {
                    $('#existing_client_id').select2('destroy');
                }
                initCompletedContractClientSelect();

                // استيراد بيانات العميل عند اختياره في المودال
                $('#existing_client_id').off('select2:select').on('select2:select', function(e) {
                    var selectedClient = e.params.data;
                    var client = $(selectedClient.element).data('data') || selectedClient;
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

                if ($('#maid_id').hasClass('select2-hidden-accessible')) {
                    $('#maid_id').select2('destroy');
                }
                initCompletedContractMaidSelect();
            });

            // تبديل نوع العميل
            $('input[name="client_type"]').on('change', function() {
                let clientType = $(this).val();

                if (clientType === 'existing') {
                    $('#existing_client_section').show();
                    $('.new-client-field').hide();

                    // إزالة required من حقول العميل الجديد
                    $('.new-client-field input, .new-client-field select').prop('required', false);
                    $('#existing_client_id').prop('required', false);
                } else {
                    $('#existing_client_section').hide();
                    $('.new-client-field').show();

                    // إضافة required لحقول العميل الجديد
                    $('.new-client-field input[name="client_name"], .new-client-field input[name="client_phone"]')
                        .prop('required', true);
                    $('#existing_client_id').prop('required', false);
                }
            }).filter(':checked').trigger('change');

            $('input[name="maid_type"]').on('change', function() {
                let maidType = $(this).val();

                if (maidType === 'existing') {
                    $('#existing_maid_section').show();
                    $('.new-maid-field').hide();
                    $('.new-maid-field input, .new-maid-field select').prop('required', false);
                    $('#maid_id').prop('required', false);
                } else {
                    $('#existing_maid_section').hide();
                    $('.new-maid-field').show();
                    $('.new-maid-field input[name="new_maid_name"], .new-maid-field input[name="new_maid_passport"], .new-maid-field select[name="new_maid_nationality_id"]')
                        .prop('required', true);
                    $('#maid_id').prop('required', false);
                }
            }).filter(':checked').trigger('change');

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
                    maids.forEach(function(item) {
                        let maid = item.maid || item;
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

                let maidType = $('input[name="maid_type"]:checked').val();
                if (maidType === 'existing' && !$('#maid_id').val()) {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ helperTrans('ingaz.error') }}',
                        text: '{{ helperTrans('ingaz.please_select_maid') }}',
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
                            $('#createContractModal').modal('hide');
                            Swal.fire({
                                icon: 'success',
                                title: '{{ helperTrans('ingaz.success') }}',
                                text: response.message,
                                confirmButtonText: '{{ helperTrans('ingaz.ok') }}'
                            }).then(function() {
                                window.location.href = response.redirect_url || '{{ route('ShelterContract.create') }}';
                            });
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
                $('#existing_client_id, #maid_id').val(null).trigger('change');
                $('#maid_details').hide();
                $('.new-client-field').hide();
                $('.new-maid-field').hide();
                $('#existing_client_section').show();
                $('#existing_maid_section').show();
                $('input[name="client_type"][value="existing"]').prop('checked', true);
                $('input[name="maid_type"][value="existing"]').prop('checked', true);
                $('#maid_search').val('');
            });
        });
    </script>
@endsection
