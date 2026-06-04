@extends('admin.layout.indexs.index')

@section('page-title')
    إرسالية المكتب الخارجي - العقد #{{ $contract->id }}
@endsection

@php
    $statusClass = match ($dispatchState['code']) {
        'completed' => 'success',
        'outdated' => 'warning text-dark',
        'incomplete' => 'danger',
        default => 'secondary',
    };
    $previewUrl = $dispatch ? route('RecruitmentContract.externalOfficeDispatch.print', $contract->id) : null;
    $pdfUrl = $dispatch
        ? route('RecruitmentContract.externalOfficeDispatch.print', ['contract' => $contract->id, 'driver' => 'dompdf', 'download' => 1])
        : null;
@endphp

@section('content')
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <h3 class="mb-1">إرسالية المكتب الخارجي</h3>
                <div class="text-muted">مرتبطة بالعقد رقم #{{ $contract->id }}</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('contracts.details', $contract->id) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-right me-1"></i>
                    الرجوع لتفاصيل العقد
                </a>
                @if ($previewUrl)
                    <a href="{{ $previewUrl }}" class="btn btn-outline-primary" target="_blank" rel="noopener">
                        <i class="fas fa-eye me-1"></i>
                        معاينة
                    </a>
                @endif
                @if ($pdfUrl)
                    <a href="{{ $pdfUrl }}" class="btn btn-primary" target="_blank" rel="noopener">
                        <i class="fas fa-file-pdf me-1"></i>
                        PDF
                    </a>
                @endif
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div>
                        <h5 class="mb-1">حالة الإرسالية</h5>
                        <div class="text-muted small">لا يمكن وصول العقد أو إغلاقه قبل اكتمال هذه الإرسالية.</div>
                    </div>
                    <span class="badge bg-{{ $statusClass }}">{{ $dispatchState['label'] }}</span>
                </div>
                <div class="alert alert-light border mb-0">{{ $dispatchState['message'] }}</div>
            </div>
        </div>

        @if (!$contract->canManageExternalOfficeDispatch())
            <div class="alert alert-warning">
                هذا العقد ليس ضمن العقود الجارية حاليًا، لذلك يمكن عرض الإرسالية فقط بدون تعديل.
            </div>
        @endif

        @if (!empty($sourceErrors))
            <div class="alert alert-danger">
                <div class="fw-semibold mb-2">لا يمكن اعتماد الإرسالية قبل استكمال البيانات التالية:</div>
                <ul class="mb-0 ps-3">
                    @foreach ($sourceErrors as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('RecruitmentContract.externalOfficeDispatch.store', $contract->id) }}">
            @csrf

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h5 class="mb-0">بيانات الإرسالية</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">رقم العقد</label>
                            <input type="text" class="form-control" value="{{ $sourceSnapshot['contract_number'] }}" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">رقم التأشيرة</label>
                            <input type="text" class="form-control" value="{{ $sourceSnapshot['visa_number'] }}" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">اسم الكفيل / العميل</label>
                            <input type="text" class="form-control" value="{{ $sourceSnapshot['sponsor_name'] }}" readonly>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">رقم الهوية</label>
                            <input type="text" class="form-control" value="{{ $sourceSnapshot['sponsor_identity_number'] }}" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">اسم العاملة / المستقدم</label>
                            <input type="text" class="form-control" value="{{ $sourceSnapshot['worker_name'] }}" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">رقم الجواز</label>
                            <input type="text" class="form-control" value="{{ $sourceSnapshot['passport_number'] }}" readonly>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">العمر</label>
                            <input type="text" class="form-control" value="{{ $sourceSnapshot['worker_age'] }}" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">الخبرة</label>
                            <input type="text" class="form-control" value="{{ $sourceSnapshot['worker_experience'] }}" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">الديانة</label>
                            <input type="text" class="form-control" value="{{ $sourceSnapshot['worker_religion'] }}" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">المكتب الخارجي</label>
                            <input type="text" class="form-control" value="{{ $sourceSnapshot['office_name'] }}" readonly>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">تكلفة العقد بالدولار <span class="text-danger">*</span></label>
                            <input type="number" name="office_contract_cost_usd" id="office_contract_cost_usd"
                                class="form-control @error('office_contract_cost_usd') is-invalid @enderror"
                                value="{{ $initialOfficeContractCostUsd }}"
                                step="0.01" min="0.01"
                                {{ $isEditable ? '' : 'disabled' }}>
                            @error('office_contract_cost_usd')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">المقابل بالريال</label>
                            <div class="input-group">
                                <input type="number" id="office_contract_cost_sar" class="form-control"
                                    value="{{ $initialOfficeContractCostSar }}"
                                    step="0.01" min="0.01"
                                    {{ $isEditable ? '' : 'disabled' }}>
                                <span class="input-group-text">ريال</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">محطة الوصول <span class="text-danger">*</span></label>
                            <input type="text" name="arrival_station"
                                class="form-control @error('arrival_station') is-invalid @enderror"
                                value="{{ $initialArrivalStation }}"
                                {{ $isEditable ? '' : 'disabled' }}>
                            @error('arrival_station')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">تاريخ الإرسالية <span class="text-danger">*</span></label>
                            <input type="date" name="dispatch_date"
                                class="form-control @error('dispatch_date') is-invalid @enderror"
                                value="{{ $initialDispatchDate }}"
                                {{ $isEditable ? '' : 'disabled' }}>
                            @error('dispatch_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                @if ($isEditable)
                    <div class="card-footer bg-white d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary" {{ !empty($sourceErrors) ? 'disabled' : '' }}>
                            <i class="fas fa-save me-1"></i>
                            حفظ الإرسالية
                        </button>
                    </div>
                @endif
            </div>
        </form>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const usdInput = document.getElementById('office_contract_cost_usd');
            const sarInput = document.getElementById('office_contract_cost_sar');
            const rate = parseFloat({{ json_encode($usdToSarRate) }});
            let updatingCurrency = false;

            function parseMoney(value) {
                return parseFloat(String(value || '').replace(',', '.'));
            }

            function setConvertedValue(targetInput, value) {
                if (!targetInput) {
                    return;
                }

                targetInput.value = Number.isFinite(value) && value > 0
                    ? value.toFixed(2)
                    : '';
            }

            function updateSarCost() {
                if (updatingCurrency || !Number.isFinite(rate) || rate <= 0) {
                    return;
                }

                updatingCurrency = true;
                setConvertedValue(sarInput, parseMoney(usdInput?.value) * rate);
                updatingCurrency = false;
            }

            function updateUsdCost() {
                if (updatingCurrency || !Number.isFinite(rate) || rate <= 0) {
                    return;
                }

                updatingCurrency = true;
                setConvertedValue(usdInput, parseMoney(sarInput?.value) / rate);
                updatingCurrency = false;
            }

            usdInput?.addEventListener('input', updateSarCost);
            sarInput?.addEventListener('input', updateUsdCost);
        });
    </script>
@endpush
