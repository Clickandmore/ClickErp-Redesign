@extends('admin.layout.indexs.index')

@section('page-title')
    {{ __('admin.recruitment_contract_stages') }}
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_VIEW))
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h3 class="h4 mb-1">
                        <i class="fas fa-tags text-primary me-2"></i>
                        {{ __('admin.recruitment_contract_stages') }}
                    </h3>
                    <div class="text-muted">{{ __('admin.recruitment_contract_stages_hint') }}</div>
                </div>
                <a href="{{ route('RecruitmentContract.index', ['status' => 'current']) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-right me-1"></i>
                    {{ __('admin.back_to_contracts') }}
                </a>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    {{ __('admin.recruitment_contract_stages_validation_error') }}
                </div>
            @endif

            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">{{ __('admin.recruitment_contract_stages') }}</h5>
                </div>
                <form method="POST" action="{{ route('recruitment-contract-stages.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 180px;">{{ __('admin.stage_code') }}</th>
                                        <th>{{ __('admin.stage_name_ar') }}</th>
                                        <th>{{ __('admin.stage_name_en') }}</th>
                                        <th style="width: 220px;">{{ __('admin.default_value') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($labels as $status => $locales)
                                        <tr>
                                            <td>
                                                <code>{{ $status }}</code>
                                            </td>
                                            <td>
                                                <input type="text" name="labels[{{ $status }}][ar]"
                                                    class="form-control @error("labels.$status.ar") is-invalid @enderror"
                                                    value="{{ old("labels.$status.ar", $locales['ar'] ?? '') }}"
                                                    maxlength="100">
                                            </td>
                                            <td>
                                                <input type="text" name="labels[{{ $status }}][en]"
                                                    class="form-control @error("labels.$status.en") is-invalid @enderror"
                                                    value="{{ old("labels.$status.en", $locales['en'] ?? '') }}"
                                                    maxlength="100">
                                            </td>
                                            <td class="text-muted small">
                                                {{ $defaults[$status]['ar'] ?? '' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3">{{ __('admin.recruitment_order_statuses') }}</h5>
                        <div class="text-muted mb-3">{{ __('admin.recruitment_order_statuses_hint') }}</div>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 180px;">{{ __('admin.status_code') }}</th>
                                        <th>{{ __('admin.status_name_ar') }}</th>
                                        <th>{{ __('admin.status_name_en') }}</th>
                                        <th style="width: 220px;">{{ __('admin.default_value') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($orderLabels as $status => $locales)
                                        <tr>
                                            <td>
                                                <code>{{ $status }}</code>
                                            </td>
                                            <td>
                                                <input type="text" name="order_labels[{{ $status }}][ar]"
                                                    class="form-control @error("order_labels.$status.ar") is-invalid @enderror"
                                                    value="{{ old("order_labels.$status.ar", $locales['ar'] ?? '') }}"
                                                    maxlength="100">
                                            </td>
                                            <td>
                                                <input type="text" name="order_labels[{{ $status }}][en]"
                                                    class="form-control @error("order_labels.$status.en") is-invalid @enderror"
                                                    value="{{ old("order_labels.$status.en", $locales['en'] ?? '') }}"
                                                    maxlength="100">
                                            </td>
                                            <td class="text-muted small">
                                                {{ $orderDefaults[$status]['ar'] ?? '' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer d-flex flex-wrap justify-content-between gap-2">
                        @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_CONTROL))
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                {{ __('admin.save_contract_stages') }}
                            </button>
                            <button type="submit" name="reset_defaults" value="1" class="btn btn-outline-danger">
                                <i class="fas fa-rotate-left me-1"></i>
                                {{ __('admin.restore_defaults') }}
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
