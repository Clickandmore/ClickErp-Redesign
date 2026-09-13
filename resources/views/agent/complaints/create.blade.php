@extends('agent.layout')

@php
    $isAr = app()->getLocale() === 'ar';
    $agentOffice = $offices->first();
    $relationType = old('relation_type', 'contract');
    $selectedContractId = old('contract_id', request('contract_id'));
@endphp

@section('page-title', $isAr ? 'شكوى جديدة' : 'New Complaint')

@push('style')
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/select2.min.css">
    <style>
        .agent-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .agent-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .agent-field.full {
            grid-column: 1 / -1;
        }

        .agent-field label {
            font-size: 13px;
            font-weight: 600;
            color: var(--agent-ink);
        }

        .agent-field label .req {
            color: #dc2626;
        }

        .agent-field input,
        .agent-field select,
        .agent-field textarea {
            min-height: 40px;
            padding: 8px 10px;
            border: 1px solid var(--agent-line);
            border-radius: 8px;
            background: #fff;
            color: var(--agent-ink);
            font-family: inherit;
            font-size: 14px;
            width: 100%;
        }

        .agent-field textarea {
            min-height: 120px;
            resize: vertical;
        }

        .agent-field .hint {
            font-size: 12px;
            color: var(--agent-muted);
        }

        .agent-field .error {
            font-size: 12px;
            color: #dc2626;
        }

        .agent-relation-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .agent-relation-tabs label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 14px;
            border: 1px solid var(--agent-line);
            border-radius: 10px;
            background: #fff;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
        }

        .agent-relation-tabs input {
            width: auto;
            min-height: auto;
            margin: 0;
        }

        .agent-relation-tabs label.is-active {
            border-color: var(--agent-primary);
            background: #f0fdfa;
            color: var(--agent-primary-dark);
        }

        .agent-form-actions {
            display: flex;
            gap: 10px;
            margin-top: 18px;
        }

        .agent-form-actions .secondary {
            background: #fff;
            color: var(--agent-ink);
            border: 1px solid var(--agent-line);
        }

        .agent-errors {
            margin-bottom: 14px;
            padding: 12px 14px;
            border-radius: 10px;
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #991b1b;
            font-size: 13px;
        }

        .agent-errors ul {
            margin: 6px 0 0;
            padding-inline-start: 18px;
        }

        .select2-container {
            width: 100% !important;
        }

        .select2-container--default .select2-selection--single {
            height: 40px;
            border: 1px solid var(--agent-line);
            border-radius: 8px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 38px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 38px;
        }

        @media (max-width: 720px) {
            .agent-form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('page-action')
    <a href="{{ route('agent.complaints.index') }}" class="agent-button">
        <i class="fa fa-list"></i>
        <span>{{ $isAr ? 'كل الشكاوى' : 'All Complaints' }}</span>
    </a>
@endsection

@section('content')
    @if ($errors->any())
        <div class="agent-errors">
            <strong>{{ $isAr ? 'يرجى تصحيح الأخطاء التالية:' : 'Please fix the following errors:' }}</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('agent.complaints.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <section class="agent-section">
            <div class="agent-section-header">
                <h3>{{ $isAr ? 'بيانات مُقدّم الشكوى' : 'Complainant Details' }}</h3>
            </div>
            <div class="agent-form-grid">
                <div class="agent-field">
                    <label>{{ $isAr ? 'المكتب' : 'Office' }}</label>
                    @if ($offices->count() > 1)
                        <select name="office_id" required>
                            @foreach ($offices as $office)
                                <option value="{{ $office->id }}" @selected((string) old('office_id') === (string) $office->id)>
                                    {{ $office->title }}
                                </option>
                            @endforeach
                        </select>
                        <span class="hint">
                            {{ $isAr ? 'اختر المكتب صاحب الشكوى.' : 'Pick the office filing this complaint.' }}
                        </span>
                        @error('office_id')<span class="error">{{ $message }}</span>@enderror
                    @else
                        <input type="text" value="{{ $agentOffice?->title ?: ($isAr ? 'مكتب خارجي' : 'External Office') }}" disabled>
                        <span class="hint">
                            {{ $isAr ? 'تُسجَّل الشكوى باسم مكتبك تلقائياً.' : 'The complaint is filed under your office automatically.' }}
                        </span>
                    @endif
                </div>
                <div class="agent-field">
                    <label>{{ $isAr ? 'اسم المسؤول' : 'Contact Name' }} <span class="req">*</span></label>
                    <input type="text" name="complainant_name" required maxlength="255"
                        value="{{ old('complainant_name', $agentOffice?->title) }}">
                    @error('complainant_name')<span class="error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field">
                    <label>{{ $isAr ? 'رقم التواصل' : 'Contact Phone' }}</label>
                    <input type="text" name="complainant_phone" maxlength="50"
                        value="{{ old('complainant_phone', $agentOffice?->phone_office) }}">
                    @error('complainant_phone')<span class="error">{{ $message }}</span>@enderror
                </div>
            </div>
        </section>

        <section class="agent-section">
            <div class="agent-section-header">
                <h3>{{ $isAr ? 'الجهة المشكو بشأنها' : 'Complaint Subject Party' }}</h3>
            </div>

            <div class="agent-relation-tabs" id="relationTabs">
                @php
                    $relationOptions = [
                        'contract' => ['ar' => 'مرتبطة بعقد', 'en' => 'Linked to a contract', 'icon' => 'fa-file-signature'],
                        'manual' => ['ar' => 'إدخال طرف يدوياً', 'en' => 'Enter the party manually', 'icon' => 'fa-keyboard'],
                        'none' => ['ar' => 'بدون ربط', 'en' => 'No link', 'icon' => 'fa-circle-minus'],
                    ];
                @endphp
                @foreach ($relationOptions as $value => $option)
                    <label class="{{ $relationType === $value ? 'is-active' : '' }}" data-relation="{{ $value }}">
                        <input type="radio" name="relation_type" value="{{ $value }}" @checked($relationType === $value)>
                        <i class="fa {{ $option['icon'] }}"></i>
                        <span>{{ $isAr ? $option['ar'] : $option['en'] }}</span>
                    </label>
                @endforeach
            </div>

            <div class="agent-form-grid" style="margin-top: 14px;">
                <div class="agent-field full" data-relation-panel="contract">
                    <label>{{ $isAr ? 'العقد' : 'Contract' }} <span class="req">*</span></label>
                    <select name="contract_id" id="contractSelect">
                        <option value="">{{ $isAr ? 'ابحث برقم العقد، العميل، أو العاملة' : 'Search by contract #, client, or worker' }}</option>
                        @foreach ($contractOptions as $option)
                            <option value="{{ $option['id'] }}" @selected((string) $selectedContractId === (string) $option['id'])>
                                {{ $option['label'] }}
                            </option>
                        @endforeach
                    </select>
                    <span class="hint">
                        {{ $isAr ? 'تظهر عقود مكتبك فقط.' : 'Only your office contracts are listed.' }}
                    </span>
                    @error('contract_id')<span class="error">{{ $message }}</span>@enderror
                </div>

                <div class="agent-field" data-relation-panel="manual">
                    <label>{{ $isAr ? 'اسم الطرف الآخر' : 'Other Party Name' }} <span class="req">*</span></label>
                    <input type="text" name="against_name" maxlength="255" value="{{ old('against_name') }}">
                    @error('against_name')<span class="error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field" data-relation-panel="manual">
                    <label>{{ $isAr ? 'هاتف الطرف الآخر' : 'Other Party Phone' }}</label>
                    <input type="text" name="against_phone" maxlength="50" value="{{ old('against_phone') }}">
                    @error('against_phone')<span class="error">{{ $message }}</span>@enderror
                </div>
            </div>
        </section>

        <section class="agent-section">
            <div class="agent-section-header">
                <h3>{{ $isAr ? 'تفاصيل الشكوى' : 'Complaint Details' }}</h3>
            </div>
            <div class="agent-form-grid">
                <div class="agent-field">
                    <label>{{ $isAr ? 'نوع الشكوى' : 'Complaint Type' }} <span class="req">*</span></label>
                    <select name="complaint_type_id" required>
                        <option value="">{{ $isAr ? 'اختر النوع' : 'Select a type' }}</option>
                        @foreach ($complaintTypes as $type)
                            <option value="{{ $type->id }}" @selected((string) old('complaint_type_id') === (string) $type->id)>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('complaint_type_id')<span class="error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field">
                    <label>{{ $isAr ? 'الأولوية' : 'Priority' }} <span class="req">*</span></label>
                    <select name="priority" required>
                        @foreach (\App\Support\AgentComplaintLabels::priorities() as $key => $label)
                            <option value="{{ $key }}" @selected(old('priority', 'medium') === $key)>
                                {{ $isAr ? $label['ar'] : $label['en'] }}
                            </option>
                        @endforeach
                    </select>
                    @error('priority')<span class="error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field full">
                    <label>{{ $isAr ? 'الموضوع' : 'Subject' }} <span class="req">*</span></label>
                    <input type="text" name="subject" required maxlength="255" value="{{ old('subject') }}">
                    @error('subject')<span class="error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field full">
                    <label>{{ $isAr ? 'التفاصيل' : 'Details' }} <span class="req">*</span></label>
                    <textarea name="details" required>{{ old('details') }}</textarea>
                    @error('details')<span class="error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field full">
                    <label>{{ $isAr ? 'ملاحظات إضافية' : 'Additional Notes' }}</label>
                    <textarea name="relation_notes" style="min-height: 80px;">{{ old('relation_notes') }}</textarea>
                    @error('relation_notes')<span class="error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field full">
                    <label>{{ $isAr ? 'المرفقات' : 'Attachments' }}</label>
                    <input type="file" name="attachments[]" multiple
                        accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx">
                    <span class="hint">
                        {{ $isAr ? 'حتى 10 ملفات، وحجم كل ملف 5 ميغابايت كحد أقصى.' : 'Up to 10 files, 5 MB each.' }}
                    </span>
                    @foreach ($errors->get('attachments.*') as $fileMessages)
                        <span class="error">{{ $fileMessages[0] }}</span>
                    @endforeach
                </div>
            </div>

            <div class="agent-form-actions">
                <button type="submit" class="agent-button">
                    <i class="fa fa-paper-plane"></i>
                    <span>{{ $isAr ? 'إرسال الشكوى' : 'Submit Complaint' }}</span>
                </button>
                <a href="{{ route('agent.complaints.index') }}" class="agent-button secondary">
                    <span>{{ $isAr ? 'إلغاء' : 'Cancel' }}</span>
                </a>
            </div>
        </section>
    </form>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="{{ asset('admin') }}/assets/js/bundle/select2.bundle.js"></script>
    <script>
        $(function () {
            const $contractSelect = $('#contractSelect');

            $contractSelect.select2({
                dir: '{{ $isAr ? 'rtl' : 'ltr' }}',
                placeholder: @json($isAr ? 'ابحث عن العقد' : 'Search for a contract'),
                allowClear: true,
                ajax: {
                    url: @json(route('agent.complaints.search-contracts')),
                    dataType: 'json',
                    delay: 300,
                    data: params => ({ q: params.term, page: params.page || 1 }),
                    processResults: data => ({
                        results: data.results,
                        pagination: data.pagination
                    }),
                    cache: true
                },
                minimumInputLength: 0
            });

            function syncRelationPanels() {
                const relation = $('input[name="relation_type"]:checked').val() || 'none';

                $('#relationTabs label').each(function () {
                    $(this).toggleClass('is-active', $(this).data('relation') === relation);
                });

                $('[data-relation-panel]').each(function () {
                    const visible = $(this).data('relation-panel') === relation;
                    $(this).toggle(visible);
                    // الحقول المخفية تُعطَّل حتى لا تُرسل قيمها مع نوع ربط آخر.
                    $(this).find('input, select, textarea').prop('disabled', !visible);
                });
            }

            $('input[name="relation_type"]').on('change', syncRelationPanels);
            syncRelationPanels();
        });
    </script>
@endpush
