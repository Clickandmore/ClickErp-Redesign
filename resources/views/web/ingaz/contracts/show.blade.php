@extends('web.layout.app')

@section('style')
<style>
    /* تحسين مظهر بادج حالة العقد */
    .status-badge-custom {
        font-weight: 600;
        text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        border-radius: 25px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        padding: 0.5rem 1.5rem;
    }

    .status-badge-custom:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    /* تحسين ألوان البادجات لتكون أكثر وضوحاً */
    .badge.bg-primary {
        background-color: #0d6efd !important;
        color: white !important;
    }

    .badge.bg-success {
        background-color: #198754 !important;
        color: white !important;
    }

    .badge.bg-warning {
        background-color: #ffc107 !important;
        color: #212529 !important;
    }

    .badge.bg-info {
        background-color: #0dcaf0 !important;
        color: white !important;
    }

    .badge.bg-danger {
        background-color: #dc3545 !important;
        color: white !important;
    }

    .badge.bg-secondary {
        background-color: #6c757d !important;
        color: white !important;
    }

    .badge.bg-dark {
        background-color: #212529 !important;
        color: white !important;
    }
</style>
@endsection

@section('content')

<main>
    <!-- page header -->
    <section class="page_header">
        <h1>{{helperTrans('web.contract_details')}}</h1>
        <h6><a href="{{route('web.home')}}">{{helperTrans('web.Home')}}</a> / {{@$data->id}}#</h6>
    </section>

    <section class="setting_setion">
        <div class="container">
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            <div class="row justify-content-center">
                <!-- Contract Status Section -->
                <div class="col-lg-10 col-12 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="order_status">
                                <div class="progress_state text-center">
                                    <div class="d-flex gap-2 align-items-center justify-content-center mb-2">
                                        <div class="status-badge">
                                            <span class="badge bg-primary fs-5 status-badge-custom">{{ getContractStatus($data->status) }}</span>
                                        </div>
                                    </div>
                                    <div class="contract-stage">
                                        <strong>{{ helperTrans('web.current_stage') }}:</strong>
                                        <span class="text-primary">{{ getContractStatus($data->status) }}</span>
                                    </div>
                                    <div class="contract-duration mt-2">
                                        <strong>{{ helperTrans('web.contract_duration') }}:</strong>
                                        <span>{{ $info->duration_from_enter ?? 0 }} {{ helperTrans('web.days') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="col-lg-10 col-12 mb-4">
                    <div class="d-flex justify-content-end gap-2">
                        @if(!$cancelExists)
                            <a class="btn btn-danger" style="color:#fff" data-bs-toggle="modal" data-bs-target="#cancelContractModal">
                                <i class="fa-light fa-ban"></i> {{ helperTrans('web.cancel_contract') }}
                            </a>
                        @else
                            <a class="btn btn-success" style="color:#fff">
                                {{ helperTrans('web.cancel_contract_requested') }}
                            </a>
                        @endif

                        <button class="btn btn-warning complain" data-bs-toggle="modal" data-bs-target="#complain">
                            <i class="fa-light fa-triangle-exclamation"></i> {{helperTrans('web.complaint_or_inquiry')}}
                        </button>
                    </div>
                </div>

                <!-- Contract Details -->
                <div class="col-lg-10 col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">{{ helperTrans('web.contract_details') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Basic Information -->
                                <div class="col-md-6">
                                    <h6 class="mb-3">{{ helperTrans('web.basic_information') }}</h6>
                                    <div class="mb-3">
                                        @if($data->id)
                                            <div class="mb-2">
                                                <strong>{{helperTrans('web.contract_id')}}:</strong> {{ $data->id }}
                                            </div>
                                        @endif
                                        @if($info->contract_date)
                                            <div class="mb-2">
                                                <strong>{{helperTrans('web.contract_date')}}:</strong> {{ \Carbon\Carbon::parse($info->contract_date)->format('Y-m-d') }}
                                            </div>
                                        @endif
                                        @if($info->visa_number)
                                            <div class="mb-2">
                                                <strong>{{helperTrans('web.visa_number')}}:</strong> {{ $info->visa_number }}
                                            </div>
                                        @endif
                                        @if($info->visa_milad_date)
                                            <div class="mb-2">
                                                <strong>{{helperTrans('web.visa_milad_date')}}:</strong> {{ \Carbon\Carbon::parse($info->visa_milad_date)->format('Y-m-d') }}
                                            </div>
                                        @endif
                                        @if($data->maid && $data->maid->passport_num)
                                            <div class="mb-2">
                                                <strong>{{helperTrans('web.passport_number')}}:</strong> {{ $data->maid->passport_num }}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Financial Information -->
                                <div class="col-md-6">
                                    <h6 class="mb-3">{{ helperTrans('web.financial_information') }}</h6>
                                    <div class="mb-3">
                                        @if($info->sponsorship_price)
                                            <div class="mb-2">
                                                <strong>{{helperTrans('web.sponsorship_price')}}:</strong> {{ number_format($info->sponsorship_price, 2) }} {{ helperTrans('web.currency') }}
                                            </div>
                                        @endif
                                        @if($info->salary_after_sponsorship)
                                            <div class="mb-2">
                                                <strong>{{helperTrans('web.salary_after_sponsorship')}}:</strong> {{ number_format($info->salary_after_sponsorship, 2) }} {{ helperTrans('web.currency') }}
                                            </div>
                                        @endif
                                        @if($info->monthly_cost)
                                            <div class="mb-2">
                                                <strong>{{helperTrans('web.monthly_cost')}}:</strong> {{ number_format($info->monthly_cost, 2) }} {{ helperTrans('web.currency') }}
                                            </div>
                                        @endif
                                        @if($info->contract_value)
                                            <div class="mb-2">
                                                <strong>{{helperTrans('web.contract_value')}}:</strong> {{ number_format($info->contract_value, 2) }} {{ helperTrans('web.currency') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Additional Information -->
                                <div class="col-12 mt-4">
                                    <h6 class="mb-3">{{ helperTrans('web.additional_information') }}</h6>
                                    <div class="row">
                                        @if($info->working_hours_num)
                                            <div class="col-md-6 mb-2">
                                                <strong>{{helperTrans('web.working_hours_num')}}:</strong> {{ $info->working_hours_num }} {{ helperTrans('web.hours') }}
                                            </div>
                                        @endif
                                        @if($info->previous_sponsorship)
                                            <div class="col-md-6 mb-2">
                                                <strong>{{helperTrans('web.previous_sponsorship')}}:</strong> {{ $info->previous_sponsorship }}
                                            </div>
                                        @endif
                                        @if($info->msaned_number)
                                            <div class="col-md-6 mb-2">
                                                <strong>{{helperTrans('web.msaned_number')}}:</strong> {{ $info->msaned_number }}
                                            </div>
                                        @endif
                                        @if($info->tafwed_number)
                                            <div class="col-md-6 mb-2">
                                                <strong>{{helperTrans('web.tafwed_number')}}:</strong> {{ $info->tafwed_number }}
                                            </div>
                                        @endif
                                        @if($info->maid_date_back)
                                            <div class="col-md-6 mb-2">
                                                <strong>{{helperTrans('web.maid_date_back')}}:</strong> {{ \Carbon\Carbon::parse($info->maid_date_back)->format('Y-m-d') }}
                                            </div>
                                        @endif
                                        @if($info->salary_final_status)
                                            <div class="col-md-6 mb-2">
                                                <strong>{{helperTrans('web.salary_final_status')}}:</strong> {{ $info->salary_final_status }}
                                            </div>
                                        @endif
                                        @if($info->duration_from_enter)
                                            <div class="col-md-6 mb-2">
                                                <strong>{{helperTrans('web.duration_from_enter')}}:</strong> {{ $info->duration_from_enter }} {{ helperTrans('web.days') }}
                                            </div>
                                        @endif
                                        @if($info->back_reasons)
                                            <div class="col-md-6 mb-2">
                                                <strong>{{helperTrans('web.back_reasons')}}:</strong> {{ $info->back_reasons }}
                                            </div>
                                        @endif
                                        @if($info->shelter_id)
                                            <div class="col-md-6 mb-2">
                                                <strong>{{helperTrans('web.shelter_id')}}:</strong> {{ $info->shelter_id }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

@include('web.ingaz.maids.sub.maid_modal_view')

@push('js')
    <script>
        $(document).ready(function () {
            // تحديث حالة العقد كل 30 ثانية
            setInterval(function() {
                $.ajax({
                    url: window.location.href,
                    method: 'GET',
                    success: function(response) {
                        // تحديث حالة العقد فقط
                        var newStatus = $(response).find('.status-badge .badge').text();
                        var currentStatus = $('.status-badge .badge').text();

                        if (newStatus !== currentStatus) {
                            location.reload();
                        }
                    }
                });
            }, 30000);

            $('#cancelContractForm').off('submit').on('submit', function (event) {
                event.preventDefault();

                var formData = $(this).serialize();
                let submitButton = $(event.originalEvent.submitter);
                let buttonText = submitButton.text();
                submitButton.prop('disabled', true);

                $.ajax({
                    type: 'POST',
                    url: $(this).attr('action'),
                    data: formData,
                    beforeSend: function () {
                        submitButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + buttonText);
                    },
                    complete: function () {
                        submitButton.html(buttonText);
                        submitButton.prop('disabled', false);
                    },
                    success: function (response) {
                        alert(response.message);
                        location.reload();
                    },
                    error: function () {
                        alert('{{ helperTrans("web.error_occurred") }}');
                        submitButton.prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endpush

<!-- Modals -->
<div class="modal fade add_location" id="complain" tabindex="-1" aria-labelledby="complainLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ helperTrans('web.complaint_or_inquiry') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @include('web.ingaz.complaints._form')
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="cancelContractModal" tabindex="-1" aria-labelledby="cancelContractModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelContractModalLabel">{{ helperTrans('web.cancel_contract') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="cancelContractForm" action="{{ route('web.contracts.cancel') }}" method="post">
                    @csrf
                    <input type="hidden" name="contract_id" id="contractId" value="{{ $data->id }}">
                    <div class="mb-3">
                        <label for="notes" class="form-label">
                            <i class="fa-light fa-clipboard"></i>
                            {{ helperTrans('web.notes') }}
                        </label>
                        <textarea name="notes" id="notes" class="form-control" placeholder="{{ helperTrans('web.enter_cancel_reason') }}"></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger">{{ helperTrans('web.save') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('js')
@endpush

@section('js')
@endsection
