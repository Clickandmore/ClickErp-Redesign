@extends('web.layout.app')

@section('content')

<main>
    <!-- page header -->
    <section class="page_header">
        <h1>{{helperTrans('web.order_details')}}</h1>
        <h6><a href="{{route('web.home')}}">{{helperTrans('web.Home')}}</a> / {{$order->jobType->title}}
            {{$order->order_code}}#
        </h6>
    </section>

    <section class="setting_setion">
        <div class="container">
            <div class="row justify-content-center">
                <!-- *** order states *** -->
                <!-- <div class="col-lg-10 col-12 mb-4">
                        <div class="order_status">
                            <div class="progress_state">
                                <div class="d-flex gap-2 align-items-center">
                                    <div class="icon">
                                        1
                                    </div>
                                    <h4>اختيار العمالة</h4>
                                </div>
                                <h6>
                                    طلب أستقدام رقم <span>44533#</span>
                                </h6>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: 16.67%" aria-valuenow="16.67"
                                    aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                        </div>
                    </div> -->
                <!-- <div class="col-lg-10 col-12 mb-4">
                        <div class="order_status">
                            <div class="progress_state">
                                <div class="d-flex gap-2 align-items-center">
                                    <div class="icon">
                                        2
                                    </div>
                                    <h4>حجز السيره الذاتية</h4>
                                </div>
                                <h6>
                                    طلب أستقدام رقم <span>44533#</span>
                                </h6>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: 33.33%" aria-valuenow="33.33"
                                    aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                        </div>
                    </div> -->
                <!-- <div class="col-lg-10 col-12 mb-4">
                        <div class="order_status">
                            <div class="progress_state">
                                <div class="d-flex gap-2 align-items-center">
                                    <div class="icon">
                                        3
                                    </div>
                                    <h4>اختار احد ممثلى خدمة العملاء</h4>
                                </div>
                                <h6>
                                    طلب أستقدام رقم <span>44533#</span>
                                </h6>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: 50%" aria-valuenow="50"
                                    aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                            <div class="cta">
                                <p>
                                    يرجى التواصل مع أ.محمد فى خلال24 ساعة حتى لا يتم إلغاء حجز
                                    السيرة الذاتية
                                </p>
                                <div class="btns">
                                    <a href="https://wa.me/" class="whatsapp" target="_blank">
                                        <i class="fa-brands fa-whatsapp"></i>
                                    </a>
                                    <a href="tel:" target="_blank">
                                        <i class="fa-light fa-phone"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div> -->
                <!-- <div class="col-lg-10 col-12 mb-4">
                        <div class="order_status">
                            <div class="progress_state">
                                <div class="d-flex gap-2 align-items-center">
                                    <div class="icon">
                                        4
                                    </div>
                                    <h4>ابرام التعاقد</h4>
                                </div>
                                <h6>
                                    طلب أستقدام رقم <span>44533#</span>
                                </h6>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: 66.67%" aria-valuenow="66.67"
                                    aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                        </div>
                    </div> -->
                <!-- <div class="col-lg-10 col-12 mb-4">
                        <div class="order_status">
                            <div class="progress_state">
                                <div class="d-flex gap-2 align-items-center">
                                    <div class="icon">
                                        5
                                    </div>
                                    <h4>اصدار تاشيرة</h4>
                                </div>
                                <h6>
                                    طلب أستقدام رقم <span>44533#</span>
                                </h6>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: 83.33%" aria-valuenow="83.33"
                                    aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                        </div>
                    </div> -->
                <div class="col-lg-10 col-12 mb-1">
                    <div class="order_status">
                        <div class="progress_state">
                            <div class="d-flex gap-2 align-items-center">
                                {{-- <div class="icon">
                                    {{ array_search($order->status, $order_progressbar) +1}}
                                </div> --}}
                                @if(@$returnRequest && @$returnRequest->status == 'new')
                                    <h4>@lang('ingaz.order_status'): <span
                                            class="badge bg-info">{{\App\Helpers\Constant::globalArray['orders_statuses'][$order->status][app()->getLocale()]}}</span>
                                    </h4>
                                    <h4>@lang('admin.order_return_status'): <span
                                            class="badge bg-warning">@if($returnRequest->status == 'new')
                                            @lang('admin.pending_1') @else @lang('admin.done_return') @endif</span>
                                    </h4>
                                @elseif(@$returnRequest && @$returnRequest->status != 'new')
                                    <h4>@lang('admin.order_return_status'): <span
                                            class="badge bg-warning">@if($returnRequest->status == 'new')
                                            @lang('admin.pending_1') @else @lang('admin.done_return') @endif</span>
                                    </h4>
                                @else
                                    <h4>@lang('ingaz.order_status'): <span
                                            class="badge bg-info">{{\App\Helpers\Constant::globalArray['orders_statuses'][$order->status][app()->getLocale()]}}</span>
                                    </h4>
                                @endif

                            </div>
                            <h6>
                                {{$order->jobType->title}} <span>{{$order->order_code}}#</span>
                            </h6>
                        </div>
                        @if(in_array($order->status, $order_progressbar))
                            <div class="progress">
                                <div class="progress-bar" role="progressbar"
                                    style="width: {{$order_progressbar[$order->status]}}%"
                                    aria-valuenow="{{$order_progressbar[$order->status]}}" aria-valuemin="0"
                                    aria-valuemax="100">
                                </div>
                            </div>
                        @endif
                        @if($order->status == \App\Enums\ingaz\OrderEnum::New ->value && $order->jobType->id == 1)
                            <div class="cta">
                                <p>
                                    يرجى التواصل مع أ.{{@$order->details->admin->name}} فى خلال24 ساعة حتى لا يتم إلغاء حجز
                                    السيرة الذاتية
                                </p>
                                <div class="btns">
                                    <a href="https://wa.me/{{@$order->details->admin->phone_code . @$order->details->admin->phone}}"
                                        class="whatsapp" target="_blank">
                                        <i class="fa-brands fa-whatsapp"></i>
                                    </a>
                                    <a href="tel:{{@$order->details->admin->phone_code . @$order->details->admin->phone}}"
                                        target="_blank">
                                        <i class="fa-light fa-phone"></i>
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="col-lg-10 col-12 mb-4 d-flex justify-content-end">
                    @if(!@$travelRequest)
                        <a class="btn btn-info me-1 text-dark" data-order-id="{{@$order->id}}" data-bs-toggle="modal"
                            data-bs-target="#travelHistoryModal">
                            {{ __('admin.travelHistoryModal') }}
                        </a>
                        @include('web.ingaz.orders.sub.travelHistoryModal')
                    @else
                        <span class="btn btn-success me-1">{{ helperTrans('admin.travelRequest_success') }}</span>
                    @endif
                    @if (@$order->payment_type == 'bank_transfer' && @$order->transfers->isEmpty())
                        <a class="btn btn-warning me-1" data-order-id="{{@$order->id}}" data-bs-toggle="modal"
                            data-bs-target="#bankTransferModal">
                            {{ helperTrans('web.uploadBankTransfer') }}
                        </a>
                        @include('web.ingaz.orders.sub.bank_proof_uplaod_modal')
                        @push('js')
                            <script>
                                $(document).ready(function () {
                                    // Ensure only one click handler is attached to the bankTransferSubmitBtn
                                    $('#bankTransferSubmitBtn').off('click').on('click', function () {
                                        var formData = new FormData($('#bankTransferForm')[0]);

                                        // Disable submit button to prevent multiple submissions
                                        $(this).prop('disabled', true);

                                        $.ajax({
                                            type: 'POST',
                                            url: '{{ route('web.bank_transfer.submit') }}',
                                            data: formData,
                                            contentType: false,
                                            processData: false,
                                            success: function (response) {
                                                // Redirect or refresh page on success
                                                window.location.reload();
                                            },
                                            error: function (error) {
                                                console.error('Error submitting bank transfer details:', error);
                                                // Optionally handle errors here

                                                // Re-enable submit button on error
                                                $('#bankTransferSubmitBtn').prop('disabled', false);
                                            }
                                        });
                                    });

                                    // When the bank transfer modal is about to be shown
                                    $('#bankTransferModal').on('show.bs.modal', function (event) {
                                        var button = $(event.relatedTarget); // Button that triggered the modal
                                        var orderId = button.data('order-id'); // Extract order ID from data attribute

                                        // Set the order ID in the modal form
                                        $('#order_id').val(orderId);
                                    });
                                });
                            </script>
                        @endpush
                    @elseif(@$order->payment_type == 'bank_transfer' && @$order->transfers->isNotEmpty())
                        <span class="btn btn-success me-1">{{ helperTrans('web.uploadBankTransfer_success') }}</span>
                    @endif

                    @if(in_array(@$order->status, ['arrived', 'started', 'escape', 'deportation']))
                        {{-- <button class="addLoation addButton btn btn-info me-1" data-bs-toggle="modal"
                            data-bs-target="#addLocation" model-name-id="addLocation"
                            model-route="{{route('web.return_requests.create',['order_id'=>$order->id])}}"
                            model-title="{{helperTrans('web.return_requests')}}">{{helperTrans('web.return_requests')}}
                        </button> --}}
                        @if(!$returnRequest)
                            <button class="btn btn-info me-1" data-bs-toggle="modal" data-bs-target="#returnConditionModal">
                                {{ helperTrans('web.return_requests') }}
                            </button>
                        @else
                            <span class="btn btn-success me-1">{{ helperTrans('admin.return_requested') }}</span>
                        @endif


                    @endif
                    <button class="complain" data-bs-toggle="modal" data-bs-target="#complain">
                        <i class="fa-light fa-triangle-exclamation"></i>{{helperTrans('web.complaint_or_inquiry')}}
                    </button>

                </div>
                <div class="col-lg-10 col-12 p-2">
                    <div class="cv">
                        <div class="cv_details">
                            <div class="img">

                                <a href="#" class="maidViewData" data-id="{{ @$order->maid->id }}">
                                    {{-- <a href="{{showFile($order->maid->cv_file)}}" data-fancybox="w1"> --}}
                                        <img src="{{showFile($order->maid->cv_file)}}" alt="cv1">
                                        {{-- </a> --}}
                                </a>

                            </div>
                            <div class="info">
                                <ul>
                                    <li>
                                        <span>{{helperTrans('web.nationality')}}</span>
                                        <h3>{{$order->maid->nationality->sub_title}}</h3>
                                    </li>
                                    <li>
                                        <span>{{helperTrans('web.salary')}}</span>
                                        <h3>{{$order->maid->salary}} ر.س</h3>
                                    </li>
                                    <li>
                                        <span>{{helperTrans('web.passport_number')}}</span>
                                        <h3>{{$order->maid->passport_num}}</h3>
                                    </li>
                                    <li>
                                        <span>{{helperTrans('web.religion')}} </span>
                                        <h3>{{$order->maid->religion->title}}</h3>
                                    </li>
                                    <li>
                                        <span>{{helperTrans('web.recruitment_price')}}</span>
                                        <h3>{{$order->maid->price}} ر.س</h3>
                                    </li>
                                    <li>
                                        <span>{{helperTrans('web.occupation')}}</span>
                                        <h3>{{$order->maid->jobTitles->pluck('title')->implode(', ')}}</h3>
                                    </li>
                                </ul>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="row justify-content-center">
                @if(isset($order->details->address))
                    <div class="col-lg-5 col-12 p-2">
                        <div class="llocation">
                            <h6>{{$order->details->address->title}}</h6>
                            <ul>
                                <li>
                                    <i class="fa-light fa-user"></i> {{$order->details->address->name}}
                                </li>
                                <li>
                                    <i class="fa-light fa-phone"></i> {{$order->details->address->phone}}
                                </li>
                                <li>
                                    <i class="fa-light fa-location-dot"></i> {{$order->details->address->address}}
                                </li>
                                <hr>
                                <li>
                                    <i class="fa-light fa-car"></i> <span>{{ __('web.i_want_maid_delivered') }}</span>
                                    @if($order->details->deliver_maid == '0')
                                        <span class="badge bg-danger">{{ __('web.no') }}</span>
                                    @elseif($order->details->deliver_maid == '1')
                                        <span class="badge bg-success">{{ __('web.yes') }}</span>
                                    @endif
                                </li>
                            </ul>

                        </div>
                    </div>
                @endif
                @if(isset($order->details->package))

                            <div class="col-lg-5 col-12 p-2">
                                <div class="ppackage">
                                    <h6>{{helperTrans('web.package')}} <span>{{$order->details->package->days_count}}</span>
                                        {{helperTrans('web.day')}}</h6>
                                    <ul>
                                        <li class="d-flex align-items-center justify-content-between">
                                            <p>{{helperTrans('web.package_price_before_discount')}}</p>
                                            <span>{{$order->details->package->original_price}} ر.س</span>
                                        </li>
                                        <li class="d-flex align-items-center justify-content-between">
                                            <p>{{helperTrans('web.discount_value')}}</p>
                                            <span>{{$order->details->package->original_price
                    - $order->details->package->price_after_discount ?? 0 }} ر.س</span>
                                        </li>
                                        <li class="d-flex align-items-center justify-content-between">
                                            <p>{{helperTrans('web.package_price_after_discount')}}</p>
                                            <span>{{$order->details->package->price_after_discount}} ر.س</span>
                                        </li>
                                        <li class="d-flex align-items-center justify-content-between">
                                            <p>{{helperTrans('web.total_with_tax')}}</p>
                                            <span>{{$order->details->package->price_with_tax}} ر.س</span>
                                        </li>
                                    </ul>


                                </div>
                @endif
                </div>
            </div>
        </div>
    </section>
</main>

@include('web.ingaz.maids.sub.maid_modal_view')

<!-- add rate for products -->
<div class="modal fade" id="storeRateModal" aria-labelledby="storeRateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLgLabel">{{__('web.rate_store')}}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

            </div>
        </div>
    </div>
</div>

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

{{-- New MODAL FOR RETURN CONTITIONS --}}
<div class="modal fade" id="returnConditionModal" tabindex="-1" aria-labelledby="returnConditionModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="returnConditionModalLabel">{{ helperTrans('web.return_requests') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Return Conditions Step -->
                <div id="returnConditionsStep">
                    <div>
                        <p>{!! @$settings->translate($locale)->return_conditions !!}</p>
                    </div>
                    <div class="form-group mt-2">
                        <label>
                            <input type="checkbox" id="acceptConditions"> @lang('ingaz.accept_return_conditions')
                        </label>
                    </div>
                    <button class="btn btn-primary" onclick="proceedToForm()">@lang('ingaz.confirm')</button>
                </div>

                <!-- Form Step (Initially Hidden) -->
                <div id="formStep" style="display: none;">
                    <form class="form-wrap from-submit-global" action="{{ route('web.return_requests.store') }}"
                        method="post" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="maid_id" value="{{ $order->maid_id }}">
                        <input type="hidden" name="order_id" value="{{ $order->id }}">
                        <div class="col-lg-12 p-2">
                            <div class="form-group">
                                <label for="details">
                                    <i class="fa-regular fa-file"></i>
                                    {{ helperTrans('web.file report') }}
                                </label>
                                <input type="file" name="file" class="dropify form-control" data-max-file-size="10M" />
                            </div>
                        </div>
                        <div class="col-lg-12 p-2">
                            <div class="form-group">
                                <label for="details">
                                    <i class="fa-regular fa-comment-dots"></i>
                                    {{ helperTrans('web.notes') }}
                                </label>
                                <textarea name="notes" class="form-control" required id="details"
                                    placeholder="{{ helperTrans('web.notes') }}"></textarea>
                            </div>
                        </div>
                        <div class="col-lg-12 p-2">
                            <div class="form-group mb-0">
                                <button type="submit" class="btn btn-primary">
                                    {{ helperTrans('web.save') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


@push('js')
    <script>
        function showReturnConditions() {
            // Reset the modal content
            // Reset the modal content
            document.getElementById('returnConditionsStep').style.display = 'block';
            document.getElementById('formStep').style.display = 'none';
        }

        function proceedToForm() {
            if (document.getElementById('acceptConditions').checked) {
                // Proceed to show the form
                document.getElementById('returnConditionsStep').style.display = 'none';
                document.getElementById('formStep').style.display = 'block';
            } else {
                // Optionally show a message or take other actions if conditions are not accepted
                alert('Please accept the return conditions.');
                // Close the modal if conditions are not accepted
                $('#returnConditionModal').modal('hide');
            }
        }
    </script>

@endpush

<!-- JS -->
@endsection

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Store modal


        // Products modal
        // const productsRadioInputs = document.querySelectorAll('#productsRateModal .star-rating input');
        // const productsLabels = document.querySelectorAll('#productsRateModal .star-rating label');
        //
        // productsRadioInputs.forEach((input, index) => {
        //     input.addEventListener('click', () => {
        //         productsLabels.forEach((label) => {
        //             label.classList.remove('active');
        //         });
        //
        //         for (let i = 0; i <= index; i++) {
        //             productsLabels[i].classList.add('active');
        //         }
        //     });
        // });
    });

    // var productsRateModal = document.getElementById('productsRateModal');
    //
    //


</script>

@endsection
