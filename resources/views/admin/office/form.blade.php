@extends('admin.layout.indexs.index')

@section('style')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .office-form-page {
            padding: 1rem 0 2rem;
        }

        .office-hero {
            border: 0;
            background:
                radial-gradient(circle at top right, rgba(13, 110, 253, 0.10), transparent 36%),
                linear-gradient(135deg, #ffffff 0%, #f7fafc 100%);
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
            border-radius: 1.35rem;
            overflow: hidden;
        }

        .office-kicker {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .4rem .8rem;
            border-radius: 999px;
            background: rgba(13, 110, 253, 0.08);
            color: #0d6efd;
            font-size: .85rem;
            font-weight: 700;
            margin-bottom: .9rem;
        }

        .office-section-card,
        .office-side-card {
            border: 1px solid #edf2f7;
            border-radius: 1.1rem;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .office-section-head {
            padding: 1.15rem 1.35rem;
            border-bottom: 1px solid #eef2f7;
            background: linear-gradient(180deg, #ffffff 0%, #fafcff 100%);
        }

        .office-section-title {
            font-size: 1rem;
            font-weight: 800;
            color: #1f2937;
            margin: 0;
        }

        .office-section-body {
            padding: 1.35rem;
        }

        .office-field {
            margin-bottom: 1rem;
        }

        .office-field label {
            display: flex;
            align-items: center;
            gap: .35rem;
            margin-bottom: .45rem;
            font-size: .92rem;
            font-weight: 700;
            color: #374151;
        }

        .office-required {
            color: #dc3545;
            font-size: .9rem;
        }

        .office-input,
        .office-select {
            min-height: 46px;
            border-radius: .85rem;
            border-color: #d7dee8;
            box-shadow: none;
        }

        .office-input:focus,
        .office-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 .2rem rgba(13, 110, 253, 0.12);
        }

        .office-side-stack {
            display: grid;
            gap: 1rem;
            position: sticky;
            top: 1rem;
        }

        .office-meta-list {
            display: grid;
            gap: .85rem;
        }

        .office-meta-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .85rem 1rem;
            border-radius: .95rem;
            background: #f8fafc;
        }

        .office-meta-label {
            color: #6b7280;
            font-size: .88rem;
            font-weight: 600;
        }

        .office-meta-value {
            color: #111827;
            font-size: .95rem;
            font-weight: 800;
            text-align: left;
        }

        .office-status-pill {
            display: inline-flex;
            align-items: center;
            padding: .45rem .75rem;
            border-radius: 999px;
            font-weight: 700;
            font-size: .82rem;
            background: rgba(25, 135, 84, 0.12);
            color: #198754;
        }

        .office-status-pill.is-inactive {
            background: rgba(220, 53, 69, 0.12);
            color: #dc3545;
        }

        .office-file-link {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            margin-top: .85rem;
            font-weight: 700;
        }

        .office-actions-card .btn {
            min-height: 46px;
            border-radius: .9rem;
            font-weight: 700;
        }

        .select2-container--default .select2-selection--multiple {
            min-height: 46px;
            border-radius: .85rem;
            border-color: #d7dee8;
            padding: .35rem .55rem;
        }

        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: #0d6efd;
            box-shadow: 0 0 0 .2rem rgba(13, 110, 253, 0.12);
        }

        @media (max-width: 1199px) {
            .office-side-stack {
                position: static;
            }
        }
    </style>
@endsection

@section('page-title')
    {{ $office ? helperTrans('ingaz.edit_office') : helperTrans('ingaz.add_office') }}
@endsection

@section('content')
    @php
        $isEdit = filled($office);
        $selectedJobTypes = collect(
            old('job_title_id', $office?->jobTypes?->pluck('id')->map(fn($id) => (string) $id)->all() ?? []),
        )
            ->map(fn($id) => (string) $id)
            ->all();
        $attachment = $office?->attachment;
        $statusEnum = $isEdit ? \App\Enums\Office\OfficesStatusEnum::tryFrom($office->status) : null;
        $defaultAttachment = $isEdit && filled($attachment?->file) ? showFile($attachment->file) : '';
    @endphp

    <div class="container-fluid office-form-page">
        <div class="office-hero card mb-4">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                    <div>
                        <span class="office-kicker">
                            <i class="fa fa-building"></i>
                            {{ helperTrans('ingaz.offices') }}
                        </span>
                        <h3 class="mb-2">
                            {{ $isEdit ? helperTrans('ingaz.edit_office') : helperTrans('ingaz.add_office') }}
                        </h3>
                        <p class="text-muted mb-0">
                            {{ helperTrans('ingaz.office_information') }} ،
                            {{ helperTrans('ingaz.office_manager_information') }} ،
                            {{ helperTrans('ingaz.bank_account_details') }}
                        </p>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @if ($isEdit)
                            <span class="badge bg-light text-dark border">#{{ $office->id }}</span>
                        @endif

                        @if ($statusEnum)
                            <span
                                class="office-status-pill {{ $statusEnum->value === \App\Enums\Office\OfficesStatusEnum::DisActive->value ? 'is-inactive' : '' }}">
                                {{ $statusEnum->lang() }}
                            </span>
                        @endif

                        <a href="{{ route('offices.index') }}" class="btn btn-outline-secondary">
                            <i class="fa fa-arrow-right ms-1"></i>
                            {{ helperTrans('ingaz.back_to_list') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <form class="from-submit-global"
            action="{{ $isEdit ? route('offices.update', $office->id) : route('offices.store') }}" method="POST"
            enctype="multipart/form-data">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="row g-4">
                <div class="col-12 col-xl-8">
                    <div class="office-section-card card mb-4">
                        <div class="office-section-head">
                            <h5 class="office-section-title">{{ helperTrans('ingaz.office_information') }}</h5>
                        </div>
                        <div class="office-section-body">
                            <div class="row">
                                @foreach (config('translatable.locales') as $locale)
                                    <div class="col-12 col-md-6 office-field">
                                        <label>
                                            {{ helperTrans('ingaz.title') }} ({{ helperTrans('ingaz.' . $locale) }})
                                            <span class="office-required">*</span>
                                        </label>
                                        <input type="text" name="title:{{ $locale }}"
                                            class="form-control office-input"
                                            value="{{ old('title:' . $locale, optional($office?->translate($locale))->title) }}"
                                            data-validation="required">
                                    </div>
                                @endforeach

                                <div class="col-12 col-md-6 office-field">
                                    <label>
                                        {{ helperTrans('ingaz.nationality') }}
                                        <span class="office-required">*</span>
                                    </label>
                                    <select name="nationality_id" class="form-select office-select"
                                        data-validation="required">
                                        <option value="">{{ helperTrans('ingaz.choose') }}</option>
                                        @foreach ($nationalities as $nationality)
                                            <option value="{{ $nationality->id }}"
                                                {{ (string) old('nationality_id', $office?->nationality_id) === (string) $nationality->id ? 'selected' : '' }}>
                                                {{ $nationality->display_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-md-6 office-field">
                                    <label>{{ helperTrans('ingaz.office_type') }}</label>
                                    <select class="form-control office-select select2" name="job_title_id[]" multiple>
                                        @foreach ($jobTypes as $jobType)
                                            <option value="{{ $jobType->id }}"
                                                {{ in_array((string) $jobType->id, $selectedJobTypes, true) ? 'selected' : '' }}>
                                                {{ $jobType->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-md-6 office-field">
                                    <label>{{ helperTrans('ingaz.phone_office') }}</label>
                                    <input type="number" name="phone_office" class="form-control office-input"
                                        value="{{ old('phone_office', $office?->phone_office) }}">
                                </div>

                                <div class="col-12 col-md-6 office-field">
                                    <label>{{ helperTrans('ingaz.license_number') }}</label>
                                    <input type="number" name="license_number" class="form-control office-input"
                                        value="{{ old('license_number', $office?->license_number) }}">
                                </div>

                                <div class="col-12 office-field mb-0">
                                    <label>{{ helperTrans('ingaz.street') }}</label>
                                    <input type="text" name="street" class="form-control office-input"
                                        value="{{ old('street', $office?->street) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="office-section-card card mb-4">
                        <div class="office-section-head">
                            <h5 class="office-section-title">{{ helperTrans('ingaz.office_manager_information') }}</h5>
                        </div>
                        <div class="office-section-body">
                            <div class="row">
                                <div class="col-12 col-md-6 office-field">
                                    <label>{{ helperTrans('ingaz.name') }}</label>
                                    <input type="text" name="manager_name" class="form-control office-input"
                                        value="{{ old('manager_name', $office?->manager_name) }}">
                                </div>

                                <div class="col-12 col-md-6 office-field">
                                    <label>{{ helperTrans('ingaz.phone') }}</label>
                                    <input type="text" name="manager_phone" class="form-control office-input"
                                        value="{{ old('manager_phone', $office?->manager_phone) }}">
                                </div>

                                <div class="col-12 col-md-6 office-field">
                                    <label>{{ helperTrans('ingaz.email') }}</label>
                                    <input type="email" name="manager_email" class="form-control office-input"
                                        value="{{ old('manager_email', $office?->manager_email) }}">
                                </div>

                                <div class="col-12 col-md-6 office-field mb-0">
                                    <label>{{ helperTrans('ingaz.password') }}</label>
                                    <input type="password" name="password" class="form-control office-input">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="office-section-card card">
                        <div class="office-section-head">
                            <h5 class="office-section-title">{{ helperTrans('ingaz.bank_account_details') }}</h5>
                        </div>
                        <div class="office-section-body">
                            <div class="row">
                                <div class="col-12 col-md-6 office-field">
                                    <label>{{ helperTrans('ingaz.bank_address') }}</label>
                                    <input type="text" name="bank_address" class="form-control office-input"
                                        value="{{ old('bank_address', $office?->bank_address) }}">
                                </div>

                                <div class="col-12 col-md-6 office-field">
                                    <label>{{ helperTrans('ingaz.bank_account') }}</label>
                                    <input type="number" name="bank_account" class="form-control office-input"
                                        value="{{ old('bank_account', $office?->bank_account) }}">
                                </div>

                                <div class="col-12 col-md-6 office-field">
                                    <label>{{ helperTrans('ingaz.bank_account_name') }}</label>
                                    <input type="text" name="bank_account_name" class="form-control office-input"
                                        value="{{ old('bank_account_name', $office?->bank_account_name) }}">
                                </div>

                                <div class="col-12 col-md-6 office-field">
                                    <label>{{ helperTrans('ingaz.bank_swift_code') }}</label>
                                    <input type="text" name="bank_swift_code" class="form-control office-input"
                                        value="{{ old('bank_swift_code', $office?->bank_swift_code) }}">
                                </div>

                                <div class="col-12 col-md-6 office-field">
                                    <label>{{ helperTrans('ingaz.bank_iban') }}</label>
                                    <input type="text" name="bank_iban" class="form-control office-input"
                                        value="{{ old('bank_iban', $office?->bank_iban) }}">
                                </div>

                                <div class="col-12 col-md-3 office-field">
                                    <label>{{ helperTrans('ingaz.cost_of_dollar') }}</label>
                                    <input type="number" step="0.01" name="cost_of_dollar"
                                        class="form-control office-input"
                                        value="{{ old('cost_of_dollar', $office?->cost_of_dollar) }}">
                                </div>

                                <div class="col-12 col-md-3 office-field mb-0">
                                    <label>{{ helperTrans('ingaz.cost_of_other') }}</label>
                                    <input type="number" step="0.01" name="cost_of_other"
                                        class="form-control office-input"
                                        value="{{ old('cost_of_other', $office?->cost_of_other) }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-4">
                    <div class="office-side-stack">
                        <div class="office-side-card card">
                            <div class="office-section-head">
                                <h5 class="office-section-title">{{ helperTrans('ingaz.office_details') }}</h5>
                            </div>
                            <div class="office-section-body">
                                <div class="office-meta-list">
                                    <div class="office-meta-item">
                                        <span class="office-meta-label">ID</span>
                                        <span class="office-meta-value">{{ $isEdit ? $office->id : '-' }}</span>
                                    </div>
                                    <div class="office-meta-item">
                                        <span class="office-meta-label">{{ __('finance::finance.account_code') }}</span>
                                        <span class="office-meta-value">
                                            {{ $isEdit ? $office->account?->code ?? 'يُنشأ تلقائياً' : 'يُنشأ تلقائياً تحت 221' }}
                                        </span>
                                    </div>
                                    <div class="office-meta-item">
                                        <span class="office-meta-label">{{ helperTrans('ingaz.linked_cvs') }}</span>
                                        <span
                                            class="office-meta-value">{{ $isEdit ? $office->maids_count ?? 0 : 0 }}</span>
                                    </div>
                                    <div class="office-meta-item">
                                        <span class="office-meta-label">{{ helperTrans('ingaz.created_at') }}</span>
                                        <span
                                            class="office-meta-value">{{ $isEdit ? optional($office->created_at)->format('Y-m-d') : '-' }}</span>
                                    </div>
                                    <div class="office-meta-item">
                                        <span class="office-meta-label">{{ helperTrans('ingaz.updated_at') }}</span>
                                        <span
                                            class="office-meta-value">{{ $isEdit ? optional($office->updated_at)->format('Y-m-d') : '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="office-side-card card">
                            <div class="office-section-head">
                                <h5 class="office-section-title">{{ helperTrans('ingaz.office_attachments') }}</h5>
                            </div>
                            <div class="office-section-body">
                                <div class="office-field">
                                    <label>{{ helperTrans('ingaz.file') }}</label>
                                    <input type="file" class="dropify" name="file"
                                        data-default-file="{{ $defaultAttachment }}">
                                </div>

                                <div class="office-field mb-0">
                                    <label>{{ helperTrans('ingaz.file_name') }}</label>
                                    <input type="text" name="file_name" class="form-control office-input"
                                        value="{{ old('file_name', $attachment?->file_name) }}">
                                </div>

                                @if ($isEdit && filled($attachment?->file))
                                    <a href="{{ $defaultAttachment }}" target="_blank" class="office-file-link">
                                        <i class="fa fa-paperclip"></i>
                                        {{ helperTrans('ingaz.view') }}
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="office-side-card card office-actions-card">
                            <div class="office-section-head">
                                <h5 class="office-section-title">{{ helperTrans('ingaz.actions') }}</h5>
                            </div>
                            <div class="office-section-body d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save ms-1"></i>
                                    {{ helperTrans('ingaz.save') }}
                                </button>

                                @if ($isEdit)
                                    <a href="{{ route('offices.show', $office->id) }}" class="btn btn-outline-primary">
                                        <i class="fa fa-eye ms-1"></i>
                                        {{ helperTrans('ingaz.show') }}
                                    </a>
                                @endif

                                <a href="{{ route('offices.index') }}" class="btn btn-outline-secondary">
                                    <i class="fa fa-list ms-1"></i>
                                    {{ helperTrans('ingaz.back_to_list') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                placeholder: "{{ helperTrans('ingaz.choose') }}",
                allowClear: true,
                width: '100%'
            });
        });
    </script>
@endsection
