@php
    $delegation = $delegation ?? null;
    $linkedUser = $delegation?->user;
    $linkedMaid = $delegation?->maid;
    $clientSourceType = old('client_source_type', $delegation->client_source_type ?? 'existing_in_program');
    $existingUserId = old('existing_user_id', $clientSourceType === 'existing_in_program' ? ($linkedUser?->id) : null);
    $selectedNationalityId = old('nationality_id', $delegation->nationality_id ?? null);
    $selectedProfessionId = old('profession_id', $delegation->profession_id ?? null);
    $selectedVisaType = old('visa_type', $delegation->visa_type ?? 'electronic_ingaz_delegation');
    $selectedWorkerReligion = old('worker_religion', $delegation->worker_religion ?? 'unspecified');
    $selectedWorkerExperience = old('worker_experience', $delegation->worker_experience ?? 'unspecified');
    $selectedMarketerId = old('marketer_id', $delegation->marketer_id ?? null);
@endphp

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">بيانات تفويض الإنجاز</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">تاريخ تفويض الإنجاز (ميلادي) <span class="text-danger">*</span></label>
                        <input type="date" name="delegation_date_gregorian"
                               value="{{ old('delegation_date_gregorian', optional($delegation?->delegation_date_gregorian)->format('Y-m-d')) }}"
                               class="form-control @error('delegation_date_gregorian') is-invalid @enderror">
                        @error('delegation_date_gregorian')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">تاريخ تفويض الإنجاز (هجري) <span class="text-danger">*</span></label>
                        <input type="text" name="delegation_date_hijri"
                               value="{{ old('delegation_date_hijri', $delegation->delegation_date_hijri ?? '') }}"
                               class="form-control @error('delegation_date_hijri') is-invalid @enderror"
                               placeholder="مثال: 1447/10/14">
                        @error('delegation_date_hijri')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الجنسية <span class="text-danger">*</span></label>
                        <select name="nationality_id" class="form-select @error('nationality_id') is-invalid @enderror">
                            <option value="">اختر الجنسية</option>
                            @foreach ($nationalities as $nationality)
                                <option value="{{ $nationality->id }}" {{ $selectedNationalityId == $nationality->id ? 'selected' : '' }}>
                                    {{ $nationality->sub_title ?? $nationality->title ?? ('#' . $nationality->id) }}
                                </option>
                            @endforeach
                        </select>
                        @error('nationality_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">المهنة <span class="text-danger">*</span></label>
                        <select name="profession_id" class="form-select @error('profession_id') is-invalid @enderror">
                            <option value="">اختر المهنة</option>
                            @foreach ($professions as $profession)
                                <option value="{{ $profession->id }}" {{ $selectedProfessionId == $profession->id ? 'selected' : '' }}>
                                    {{ $profession->title }}
                                </option>
                            @endforeach
                        </select>
                        @error('profession_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">بيانات العميل</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label d-block">نوع العميل <span class="text-danger">*</span></label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input client-source-toggle" type="radio" name="client_source_type"
                                   id="client_source_existing" value="existing_in_program"
                                   {{ $clientSourceType === 'existing_in_program' ? 'checked' : '' }}>
                            <label class="form-check-label" for="client_source_existing">موجود في البرنامج</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input client-source-toggle" type="radio" name="client_source_type"
                                   id="client_source_separate" value="separate_client"
                                   {{ $clientSourceType === 'separate_client' ? 'checked' : '' }}>
                            <label class="form-check-label" for="client_source_separate">عميل منفصل</label>
                        </div>
                        @error('client_source_type')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 existing-client-section">
                        <label class="form-label">العميل من المستخدمين <span class="text-danger">*</span></label>
                        <select name="existing_user_id" id="existing_user_id"
                                class="form-select @error('existing_user_id') is-invalid @enderror">
                            <option value="">اختر العميل</option>
                            @foreach ($existingUsers as $user)
                                <option value="{{ $user->id }}"
                                        data-name="{{ $user->name }}"
                                        data-phone="{{ trim(($user->phone_code ?: '') . ' ' . ($user->phone ?: '')) }}"
                                        data-national="{{ $user->national_num }}"
                                        data-address="{{ $user->address }}"
                                        {{ $existingUserId == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}{{ $user->national_num ? ' - ' . $user->national_num : '' }}{{ $user->phone ? ' - ' . $user->phone : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('existing_user_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div id="existing-client-preview" class="alert alert-light border mt-3 mb-0">
                            <div><strong>الاسم:</strong> <span data-preview="name">-</span></div>
                            <div><strong>الجوال:</strong> <span data-preview="phone">-</span></div>
                            <div><strong>رقم الهوية:</strong> <span data-preview="national">-</span></div>
                            <div><strong>العنوان:</strong> <span data-preview="address">-</span></div>
                        </div>
                    </div>

                    <div class="col-12 separate-client-section">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">اسم العميل <span class="text-danger">*</span></label>
                                <input type="text" name="separate_client_name"
                                       value="{{ old('separate_client_name', $clientSourceType === 'separate_client' ? ($linkedUser?->name ?? '') : '') }}"
                                       class="form-control @error('separate_client_name') is-invalid @enderror">
                                @error('separate_client_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">رقم الهوية <span class="text-danger">*</span></label>
                                <input type="text" name="separate_client_identity_number"
                                       value="{{ old('separate_client_identity_number', $clientSourceType === 'separate_client' ? ($linkedUser?->national_num ?? '') : '') }}"
                                       class="form-control @error('separate_client_identity_number') is-invalid @enderror">
                                @error('separate_client_identity_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">عنوان العميل <span class="text-danger">*</span></label>
                                <input type="text" name="separate_client_address"
                                       value="{{ old('separate_client_address', $clientSourceType === 'separate_client' ? ($linkedUser?->address ?? '') : '') }}"
                                       class="form-control @error('separate_client_address') is-invalid @enderror">
                                @error('separate_client_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">جوال العميل <span class="text-danger">*</span></label>
                                <input type="text" name="separate_client_phone"
                                       value="{{ old('separate_client_phone', $clientSourceType === 'separate_client' ? ($linkedUser?->phone ?? '') : '') }}"
                                       class="form-control @error('separate_client_phone') is-invalid @enderror">
                                @error('separate_client_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">بيانات التأشيرة والعاملة</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label d-block">نوع التأشيرة <span class="text-danger">*</span></label>
                        @foreach ($visaTypeOptions as $value => $label)
                            <div class="form-check">
                                <input class="form-check-input @error('visa_type') is-invalid @enderror" type="radio"
                                       name="visa_type" id="visa_type_{{ $loop->index }}" value="{{ $value }}"
                                       {{ $selectedVisaType === $value ? 'checked' : '' }}>
                                <label class="form-check-label" for="visa_type_{{ $loop->index }}">{{ $label }}</label>
                            </div>
                        @endforeach
                        @error('visa_type')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">اسم العاملة <span class="text-danger">*</span></label>
                        <input type="text" name="worker_name" id="worker_name"
                               value="{{ old('worker_name', $delegation->worker_name ?? $linkedMaid?->name ?? '') }}"
                               class="form-control @error('worker_name') is-invalid @enderror">
                        @error('worker_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">رقم جواز العاملة <span class="text-danger">*</span></label>
                        <input type="text" name="worker_passport_number" id="worker_passport_number"
                               value="{{ old('worker_passport_number', $delegation->worker_passport_number ?? $linkedMaid?->passport_num ?? '') }}"
                               class="form-control @error('worker_passport_number') is-invalid @enderror">
                        @error('worker_passport_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div id="maid-lookup-result" class="small mt-2 text-muted"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ إصدار جواز سفر العاملة</label>
                        <input type="date" name="worker_passport_issue_date"
                               value="{{ old('worker_passport_issue_date', optional($delegation?->worker_passport_issue_date)->format('Y-m-d')) }}"
                               class="form-control @error('worker_passport_issue_date') is-invalid @enderror">
                        @error('worker_passport_issue_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">رقم التأشيرة <span class="text-danger">*</span></label>
                        <input type="text" name="visa_number"
                               value="{{ old('visa_number', $delegation->visa_number ?? '') }}"
                               class="form-control @error('visa_number') is-invalid @enderror">
                        @error('visa_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ التأشيرة</label>
                        <input type="date" name="visa_date"
                               value="{{ old('visa_date', optional($delegation?->visa_date)->format('Y-m-d')) }}"
                               class="form-control @error('visa_date') is-invalid @enderror">
                        @error('visa_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الديانة <span class="text-danger">*</span></label>
                        <select name="worker_religion" id="worker_religion" class="form-select @error('worker_religion') is-invalid @enderror">
                            @foreach ($religionOptions as $value => $label)
                                <option value="{{ $value }}" {{ $selectedWorkerReligion === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('worker_religion')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الخبرة <span class="text-danger">*</span></label>
                        <select name="worker_experience" id="worker_experience" class="form-select @error('worker_experience') is-invalid @enderror">
                            @foreach ($experienceOptions as $value => $label)
                                <option value="{{ $value }}" {{ $selectedWorkerExperience === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('worker_experience')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">المكتب الخارجي</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">اسم المكتب <span class="text-danger">*</span></label>
                        <input type="text" name="external_office_name"
                               value="{{ old('external_office_name', $delegation->external_office_name ?? '') }}"
                               class="form-control @error('external_office_name') is-invalid @enderror">
                        @error('external_office_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">رقم ترخيص المكتب <span class="text-danger">*</span></label>
                        <input type="text" name="external_office_license_number"
                               value="{{ old('external_office_license_number', $delegation->external_office_license_number ?? '') }}"
                               class="form-control @error('external_office_license_number') is-invalid @enderror">
                        @error('external_office_license_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">عنوان المكتب <span class="text-danger">*</span></label>
                        <input type="text" name="external_office_address"
                               value="{{ old('external_office_address', $delegation->external_office_address ?? '') }}"
                               class="form-control @error('external_office_address') is-invalid @enderror">
                        @error('external_office_address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">هاتف المكتب <span class="text-danger">*</span></label>
                        <input type="text" name="external_office_phone"
                               value="{{ old('external_office_phone', $delegation->external_office_phone ?? '') }}"
                               class="form-control @error('external_office_phone') is-invalid @enderror">
                        @error('external_office_phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">بيانات إضافية</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">المسوق <span class="text-danger">*</span></label>
                        <select name="marketer_id" class="form-select @error('marketer_id') is-invalid @enderror">
                            <option value="">اختر المسوق</option>
                            @foreach ($marketers as $marketer)
                                <option value="{{ $marketer->id }}" {{ $selectedMarketerId == $marketer->id ? 'selected' : '' }}>
                                    {{ $marketer->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('marketer_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">رقم إنجاز <span class="text-danger">*</span></label>
                        <input type="text" name="ingaz_number"
                               value="{{ old('ingaz_number', $delegation->ingaz_number ?? '') }}"
                               class="form-control @error('ingaz_number') is-invalid @enderror">
                        @error('ingaz_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">رقم عقد مساند <span class="text-danger">*</span></label>
                        <input type="text" name="msaned_contract_number"
                               value="{{ old('msaned_contract_number', $delegation->msaned_contract_number ?? '') }}"
                               class="form-control @error('msaned_contract_number') is-invalid @enderror">
                        @error('msaned_contract_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظة</label>
                        <textarea name="notes" rows="4"
                                  class="form-control @error('notes') is-invalid @enderror"
                                  placeholder="أضف أي ملاحظات إضافية">{{ old('notes', $delegation->notes ?? '') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">المرفقات</label>
                        <input type="file" name="attachments[]" multiple
                               class="form-control @error('attachments') is-invalid @enderror @error('attachments.*') is-invalid @enderror">
                        @error('attachments')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @error('attachments.*')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        @if ($delegation && !empty($delegation->attachments))
                            <div class="mt-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong>المرفقات الحالية</strong>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" id="replace_attachments" name="replace_attachments">
                                        <label class="form-check-label" for="replace_attachments">
                                            استبدال المرفقات الحالية عند الرفع
                                        </label>
                                    </div>
                                </div>
                                <ul class="list-group">
                                    @foreach ($delegation->attachments as $attachment)
                                        @php
                                            $attachmentPath = is_array($attachment) ? ($attachment['path'] ?? '') : $attachment;
                                            $attachmentName = is_array($attachment) ? ($attachment['name'] ?? basename($attachment['path'] ?? '')) : basename($attachment);
                                        @endphp
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>{{ $attachmentName }}</span>
                                            <a href="{{ Storage::url($attachmentPath) }}"
                                               class="btn btn-sm btn-outline-primary" target="_blank">عرض</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sourceInputs = document.querySelectorAll('.client-source-toggle');
        const existingSection = document.querySelector('.existing-client-section');
        const separateSection = document.querySelector('.separate-client-section');
        const existingUserSelect = document.getElementById('existing_user_id');
        const previewBox = document.getElementById('existing-client-preview');
        const passportInput = document.getElementById('worker_passport_number');
        const workerNameInput = document.getElementById('worker_name');
        const workerReligionInput = document.getElementById('worker_religion');
        const workerExperienceInput = document.getElementById('worker_experience');
        const maidLookupResult = document.getElementById('maid-lookup-result');
        const maidLookupRoute = @json($maidLookupRoute ?? null);

        function updateClientSections() {
            const selected = document.querySelector('.client-source-toggle:checked')?.value || 'existing_in_program';
            const isExisting = selected === 'existing_in_program';

            existingSection.style.display = isExisting ? '' : 'none';
            separateSection.style.display = isExisting ? 'none' : '';
        }

        function updateExistingPreview() {
            if (!existingUserSelect || !previewBox) {
                return;
            }

            const selectedOption = existingUserSelect.options[existingUserSelect.selectedIndex];
            previewBox.querySelector('[data-preview="name"]').textContent = selectedOption?.dataset?.name || '-';
            previewBox.querySelector('[data-preview="phone"]').textContent = selectedOption?.dataset?.phone || '-';
            previewBox.querySelector('[data-preview="national"]').textContent = selectedOption?.dataset?.national || '-';
            previewBox.querySelector('[data-preview="address"]').textContent = selectedOption?.dataset?.address || '-';
        }

        function mapMaidReligion(religionText) {
            const text = (religionText || '').toLowerCase();

            if (text.includes('غير مسلم') || text.includes('non-muslim') || text.includes('not muslim')) {
                return 'non_muslim';
            }

            if (text.includes('مسلم') || text.includes('muslim')) {
                return 'muslim';
            }

            return 'unspecified';
        }

        function mapMaidExperience(statusText) {
            return statusText === 'has_experience' ? 'previously_worked' : 'not_previously_worked';
        }

        async function lookupMaidByPassport() {
            const passport = passportInput?.value?.trim();
            if (!passport || !maidLookupRoute) {
                if (maidLookupResult) {
                    maidLookupResult.textContent = '';
                }
                return;
            }

            try {
                const response = await fetch(`${maidLookupRoute}?passport=${encodeURIComponent(passport)}`);
                const data = await response.json();

                if (!data?.found) {
                    maidLookupResult.textContent = 'لم يتم العثور على عاملة بهذا الجواز. سيتم إنشاء سجل جديد عند الحفظ.';
                    maidLookupResult.className = 'small mt-2 text-muted';
                    return;
                }

                maidLookupResult.textContent = `تم العثور على العاملة: ${data.maid.name || '-'} (ID: ${data.maid.id})`;
                maidLookupResult.className = 'small mt-2 text-success';

                if (data.maid.name && !workerNameInput.value.trim()) {
                    workerNameInput.value = data.maid.name;
                }

                workerReligionInput.value = mapMaidReligion(data.maid.religion);
                workerExperienceInput.value = mapMaidExperience(data.maid.experience_status);
            } catch (error) {
                maidLookupResult.textContent = 'تعذر فحص رقم الجواز حالياً.';
                maidLookupResult.className = 'small mt-2 text-danger';
            }
        }

        sourceInputs.forEach(function (input) {
            input.addEventListener('change', updateClientSections);
        });

        if (existingUserSelect) {
            existingUserSelect.addEventListener('change', updateExistingPreview);
        }

        if (passportInput) {
            passportInput.addEventListener('blur', lookupMaidByPassport);
        }

        updateClientSections();
        updateExistingPreview();
    });
</script>
