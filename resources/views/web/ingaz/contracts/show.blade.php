@extends('web.layout.app')

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
                <div class="col-lg-10 col-12 mb-1">
                    <div class="order_status">
                        <div class="progress_state">
                            <div class="d-flex gap-2 align-items-center">
                                <!-- Add any progress information here if needed -->
                            </div>
                            <h6>
                                <!-- Add any status text here if needed -->
                            </h6>
                        </div>
                    </div>
                </div>
                <div class="col-lg-10 col-12 mb-4 d-flex justify-content-end">
                    @if(!$cancelExists)
                        <a class="btn btn-danger me-1" style="color:#fff" data-bs-toggle="modal"
                            data-bs-target="#cancelContractModal">
                            <i class="fa-light fa-ban"></i> {{ helperTrans('web.cancel_contract') }}
                        </a>
                    @else
                        <a class="btn btn-success me-1" style="color:#fff">
                            {{ helperTrans('web.cancel_contract_requested') }}
                        </a>
                    @endif

                    <button class="complain" data-bs-toggle="modal" data-bs-target="#complain">
                        <i class="fa-light fa-triangle-exclamation"></i>{{helperTrans('web.complaint_or_inquiry')}}
                    </button>
                </div>
                <div class="col-lg-10 col-12 p-2">
                    <div class="cv">
                        <div class="cv_details text-center">
                            <div class="row">
                                @if($data->id)
                                    <div class="col-md-6 mb-3">
                                        <strong>{{helperTrans('web.contract_id')}}:</strong> {{ $data->id }}
                                    </div>
                                @endif
                                @if($info->contract_date)
                                    <div class="col-md-6 mb-3">
                                        <strong>{{helperTrans('web.contract_date')}}:</strong> {{ $info->contract_date }}
                                    </div>
                                @endif
                                @if($info->visa_number)
                                    <div class="col-md-6 mb-3">
                                        <strong>{{helperTrans('web.visa_number')}}:</strong> {{ $info->visa_number }}
                                    </div>
                                @endif
                                {{-- @if($info->visa_hijry_date)
                                <div class="col-md-6 mb-3">
                                    <strong>{{helperTrans('web.visa_hijry_date')}}:</strong> {{ $info->visa_hijry_date
                                    }}
                                </div>
                                @endif --}}
                                @if($info->visa_milad_date)
                                    <div class="col-md-6 mb-3">
                                        <strong>{{helperTrans('web.visa_milad_date')}}:</strong>
                                        {{ $info->visa_milad_date }}
                                    </div>
                                @endif
                                @if($info->working_hours_num)
                                    <div class="col-md-6 mb-3">
                                        <strong>{{helperTrans('web.working_hours_num')}}:</strong>
                                        {{ $info->working_hours_num }}
                                    </div>
                                @endif
                                @if($info->sponsorship_price)
                                    <div class="col-md-6 mb-3">
                                        <strong>{{helperTrans('web.sponsorship_price')}}:</strong>
                                        {{ $info->sponsorship_price }}
                                    </div>
                                @endif
                                @if($info->salary_after_sponsorship)
                                    <div class="col-md-6 mb-3">
                                        <strong>{{helperTrans('web.salary_after_sponsorship')}}:</strong>
                                        {{ $info->salary_after_sponsorship }}
                                    </div>
                                @endif
                                @if($info->monthly_cost)
                                    <div class="col-md-6 mb-3">
                                        <strong>{{helperTrans('web.monthly_cost')}}:</strong> {{ $info->monthly_cost }}
                                    </div>
                                @endif
                                @if($info->contract_duration)
                                    <div class="col-md-6 mb-3">
                                        <strong>{{helperTrans('web.contract_duration')}}:</strong>
                                        {{ $info->contract_duration }} days
                                    </div>
                                @endif
                                @if($info->contract_value)
                                    <div class="col-md-6 mb-3">
                                        <strong>{{helperTrans('web.contract_value')}}:</strong> {{ $info->contract_value }}
                                    </div>
                                @endif
                                {{-- @if($info->ingaz_from)
                                <div class="col-md-6 mb-3">
                                    <strong>{{helperTrans('web.ingaz_from')}}:</strong> {{ $info->ingaz_from }}
                                </div>
                                @endif
                                @if($info->ingaz_to)
                                <div class="col-md-6 mb-3">
                                    <strong>{{helperTrans('web.ingaz_to')}}:</strong> {{ $info->ingaz_to }}
                                </div>
                                @endif --}}
                                @if($info->previous_sponsorship)
                                    <div class="col-md-6 mb-3">
                                        <strong>{{helperTrans('web.previous_sponsorship')}}:</strong>
                                        {{ $info->previous_sponsorship }}
                                    </div>
                                @endif
                                @if($info->msaned_number)
                                    <div class="col-md-6 mb-3">
                                        <strong>{{helperTrans('web.msaned_number')}}:</strong> {{ $info->msaned_number }}
                                    </div>
                                @endif
                                @if($info->tafwed_number)
                                    <div class="col-md-6 mb-3">
                                        <strong>{{helperTrans('web.tafwed_number')}}:</strong> {{ $info->tafwed_number }}
                                    </div>
                                @endif
                                @if($info->maid_date_back)
                                    <div class="col-md-6 mb-3">
                                        <strong>{{helperTrans('web.maid_date_back')}}:</strong> {{ $info->maid_date_back }}
                                    </div>
                                @endif
                                @if($info->salary_final_status)
                                    <div class="col-md-6 mb-3">
                                        <strong>{{helperTrans('web.salary_final_status')}}:</strong>
                                        {{ $info->salary_final_status }}
                                    </div>
                                @endif
                                @if($info->duration_from_enter)
                                    <div class="col-md-6 mb-3">
                                        <strong>{{helperTrans('web.duration_from_enter')}}:</strong>
                                        {{ $info->duration_from_enter }}
                                    </div>
                                @endif
                                @if($info->back_reasons)
                                    <div class="col-md-6 mb-3">
                                        <strong>{{helperTrans('web.back_reasons')}}:</strong> {{ $info->back_reasons }}
                                    </div>
                                @endif
                                @if($info->shelter_id)
                                    <div class="col-md-6 mb-3">
                                        <strong>{{helperTrans('web.shelter_id')}}:</strong> {{ $info->shelter_id }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <div class="row justify-content-center">
                <!-- Additional content if needed -->
            </div>
        </div>
    </section>
</main>

@include('web.ingaz.maids.sub.maid_modal_view')

@push('js')
    {{--
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> --}}
    <script>
        $(document).ready(function () {
            $('#cancelContractForm').off('submit').on('submit', function (event) {
                event.preventDefault();

                var formData = $(this).serialize(); // Serialize the form data
                let submitButton = $(event.originalEvent.submitter);
                let buttonText = submitButton.text();
                submitButton.prop('disabled', true); // Disable the button

                $.ajax({
                    type: 'POST',
                    url: $(this).attr('action'),
                    data: formData,
                    beforeSend: function () {
                        submitButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + buttonText);
                    },
                    complete: function () {
                        submitButton.html(buttonText);
                        submitButton.prop('disabled', false); // Re-enable the button
                    },
                    success: function (response) {
                        // Show alert message
                        alert(response.message);

                        // Reload the page after showing the alert
                        location.reload();
                    },
                    error: function () {
                        // Show error alert
                        alert('An error occurred. Please try again.');

                        // Re-enable the submit button
                        submitButton.prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endpush


<div class="modal fade add_location" id="complain" tabindex="-1" aria-labelledby="complainLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @include('web.ingaz.complaints._form')
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="cancelContractModal" tabindex="-1" aria-labelledby="cancelContractModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelContractModalLabel">@lang('web.cancel_contract')</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="cancelContractForm" action="{{ route('web.contracts.cancel') }}" method="post">
                    @csrf
                    <input type="hidden" name="contract_id" id="contractId" value="{{ $data->id }}">
                    <div class="mb-3">
                        <label for="notes" class="form-label">
                            <i class="fa-light fa-clipboard"></i>
                            @lang('web.notes')
                        </label>
                        <textarea name="notes" id="notes" class="form-control"
                            placeholder="@lang('web.enter_cancel_reason')"></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger">@lang('web.save')</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- JS -->
@endsection

@push('js')
@endpush

@section('js')
@endsection
