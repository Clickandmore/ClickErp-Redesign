@extends('site.layouts.index')
@section('title')
    {{ __('ingaz.cv_details') }}
@endsection
<style>
    .location:hover {
        box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
    }

    .location:hover .delete-btn,
    .location:hover .edit-btn {
        display: block !important;
    }

    .delete-btn,
    .edit-btn {
        display: none;
        position: absolute;
        top: 8px;
        background: #fff;
        border: none;
        border-radius: 50%;
        padding: 6px;
        cursor: pointer;
        transition: 0.3s;
        box-shadow: 0 0 4px rgba(0, 0, 0, 0.15);
    }

    /* ✅ لو الصفحة RTL (عربية): خلي الأزرار في اليسار */
    [dir="rtl"] .delete-btn {
        left: 8px;
    }

    [dir="rtl"] .edit-btn {
        left: 45px;
    }

    /* ✅ لو الصفحة LTR (إنجليزية): خلي الأزرار في اليمين */
    [dir="ltr"] .delete-btn {
        right: 8px;
    }

    [dir="ltr"] .edit-btn {
        right: 45px;
    }

    .delete-btn:hover {
        background: rgba(255, 0, 0, 0.1);
        transform: scale(1.1);
    }

    .edit-btn:hover {
        background: rgba(0, 123, 255, 0.1);
        transform: scale(1.1);
    }

</style>
@section('content')
    <section class="page_header">
        <h1>{{ $type->title }}</h1>
        <h6><a href="{{ route('site.home.index') }}">{{ helperTrans('web.Home') }}</a> / {{ $type->title }}</h6>
    </section>
    <!-- cv page -->
    <div class="rentingWorkersNew">
        <section class="rentingWorkers">
            <div class="container">
                <div class="bs-stepper vertical">
                    <div class="bs-stepper-header">
                        <!-- package step btn -->
                        <div class="step" data-target="#package">
                            <button type="button" class="step-trigger">
                                <span class="bs-stepper-circle">
                                    <img src="{{ asset('site') }}\assets/images/package.png" alt="package">
                                </span>
                                <span class="bs-stepper-label">
                                    <span class="bs-stepper-title">{{ helperTrans('web.select_package') }}</span>
                                    <span
                                        class="bs-stepper-subtitle">{{ helperTrans('web.select_appropriate_package') }}</span>
                                </span>
                            </button>
                        </div>
                        <!-- location step btn -->
                        <div class="step" data-target="#location">
                            <button type="button" class="step-trigger">
                                <span class="bs-stepper-circle">
                                    <img src="{{ asset('site') }}\assets/images/location.png" alt="location">
                                </span>
                                <span class="bs-stepper-label">
                                    <span class="bs-stepper-title">{{ helperTrans('web.address') }}</span>
                                    <span class="bs-stepper-subtitle">{{ helperTrans('web.select_address') }}</span>
                                </span>
                            </button>
                        </div>

                        <!-- confirm step btn -->
                        <div class="step" data-target="#confirm">
                            <button type="button" class="step-trigger">
                                <span class="bs-stepper-circle">
                                    <img src="{{ asset('site') }}\assets/images/confirm.png" alt="confirm">
                                </span>
                                <span class="bs-stepper-label">
                                    <span
                                        class="bs-stepper-title">{{ helperTrans('web.confirm_rental_request') }}</span>
                                    <span class="bs-stepper-subtitle">
                                        {{ helperTrans('web.confirm_and_pay') }}
                                    </span>
                                </span>
                            </button>
                        </div>
                    </div>
                    <div class="bs-stepper-content">
                        <form id="wizard-property-listing-form" method="post"
                              action="{{route('site.eagar-request.store')}}"
                              class="TypeTwoForm" onSubmit="return false">
                            @csrf
                            <input type="hidden" name="job_type_id" value="2">
                            <input type="hidden" name="payment_method" value="none">
                            <input type="hidden" name="maid_id" value="{{ $maid->id }}">
                            <input type="hidden" name="details_list[address_id]" value="">
                            <input type="hidden" name="details_list[package_id]" value="">
                            <input type="hidden" name="details_list[deliver_maid]" value="0" class="new_deliver_maid">
                            <!-- location step -->
                            <div id="location" class="content">
                                <div class="row">
                                    <div class="col-12 p-2 d-flex justify-content-end">
                                        <button class="addLoation addButton" data-bs-toggle="modal"
                                                data-bs-target="#addLocation" model-name-id="addLocation"
                                                model-route="{{ route('web.addresses.create') }}"
                                                model-title="{{ helperTrans('web.add_address') }}">{{ helperTrans('web.add_address') }}
                                        </button>
                                    </div>
                                    <div class="addresses_div row">
                                        <div class="row g-3 user_address">
                                            @foreach ($addresses as $address)
                                                <div class="col-6">
                                                    <label for="l1{{ $address->id }}" class="location-card w-100" style="position: relative; display: block;">
                                                        <div class="location" id="address-{{ $address->id }}"
                                                             style="position: relative; border: 1px solid #ddd; border-radius: 10px; padding: 15px; transition: 0.3s;">
                                                            <h6>{{ $address->title }}</h6>
                                                            <ul style="list-style: none; padding: 0; margin: 0;">
                                                                <li><i class="fa-light fa-user"></i> {{ $address->name }}</li>
                                                                <li><i class="fa-light fa-phone"></i> {{ $address->phone }}</li>
                                                                <li><i class="fa-light fa-location-dot"></i> {{ $address->address }}</li>
                                                            </ul>

                                                            <!-- 🗑️ حذف -->
                                                            <button type="button" class="delete-btn" data-id="{{ $address->id }}" title="{{ __('web.delete_address') }}">
                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="red">
                                                                    <path d="M9 3v1H4v2h16V4h-5V3H9zm1 5v9h2V8h-2zm4 0v9h2V8h-2zM7 8v9h2V8H7z"/>
                                                                </svg>
                                                            </button>

                                                            <!-- ✏️ تعديل -->
                                                            <button type="button" class="edit-btn"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#addLocation"
                                                                    model-route="{{ route('site.user-addresses.edit', $address->id) }}">
                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="#007bff">
                                                                    <path d="M3 17.25V21h3.75l11-11.03-3.75-3.75L3 17.25zm14.71-10.04a1.003 1.003 0 0 0 0-1.42l-1.5-1.5a1.003 1.003 0 0 0-1.42 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                                                                </svg>
                                                            </button>
                                                        </div>

                                                        <input type="radio" name="address_id" value="{{ $address->id }}" id="l1{{ $address->id }}">
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>

                                        <!-- خيار إدخال العنوان يدوياً -->
                                        <div class="col-lg-6 col-12 p-2">
                                            <label for="manual_address" onclick="showManualAddressInput()">
                                                <div class="location" id="manual-address-option"
                                                     style="border: 2px dashed #007bff;">
                                                    <h6>
                                                        <i class="fa-light fa-edit me-2"></i>{{__("ingaz.Enter the address manually")}}
                                                    </h6>
                                                    <ul>
                                                        <li>
                                                            <i class="fa-light fa-info-circle"></i>{{__("ingaz.Enter the address yourself")}}
                                                        </li>
                                                    </ul>
                                                </div>
                                                <input type="radio" name="address_id" value="manual"
                                                       id="manual_address">
                                            </label>
                                        </div>

                                        <!-- خيار تخطي العنوان -->
                                        <div class="col-lg-6 col-12 p-2">
                                            <label for="skip_address" onclick="skipAddress()">
                                                <div class="location" id="skip-address-option"
                                                     style="border: 2px dashed #6c757d;">
                                                    <h6>
                                                        <i class="fa-light fa-forward me-2"></i>{{__("ingaz.Skip Address")}}
                                                    </h6>
                                                    <ul>
                                                        <li>
                                                            <i class="fa-light fa-info-circle"></i>ً{{__("ingaz.I will decide on the title laters")}}
                                                        </li>
                                                    </ul>
                                                </div>
                                                <input type="radio" name="address_id" value="skip"
                                                       id="skip_address">
                                            </label>
                                        </div>
                                    </div>

                                    <!-- حقل إدخال العنوان يدوياً (مخفي) -->
                                    {{--                                    <div id="manual_address_input" class="row mt-3" >--}}
                                    {{--                                        <div class="col-12">--}}
                                    {{--                                            <div class="card">--}}
                                    {{--                                                <div class="card-header">--}}
                                    {{--                                                    <h6><i class="fa-light fa-edit me-2"></i>إدخال العنوان يدوياً</h6>--}}
                                    {{--                                                </div>--}}
                                    {{--                                                <div class="card-body">--}}
                                    {{--                                                    <form id="form-of-user-address" method="post"--}}
                                    {{--                                                          action="{{route('site.user-addresses.store')}}">--}}
                                    {{--                                                        @csrf--}}
                                    {{--                                                        <div class="row">--}}
                                    {{--                                                            <div class="col-md-6 mb-3">--}}
                                    {{--                                                                <label class="form-label">المدينة <span--}}
                                    {{--                                                                        class="text-danger">*</span></label>--}}
                                    {{--                                                                <select class="form-control" id="manual_city"--}}
                                    {{--                                                                        name="country_id">--}}
                                    {{--                                                                    <option value="">اختر المدينة</option>--}}
                                    {{--                                                                    @foreach($countries as $country)--}}
                                    {{--                                                                        <option--}}
                                    {{--                                                                            value="{{$country->id}}">{{app()->getLocale()=='ar' ? $country->name_ar : $country->name_en}}</option>--}}
                                    {{--                                                                    @endforeach--}}
                                    {{--                                                                </select>--}}
                                    {{--                                                            </div>--}}
                                    {{--                                                            <div class="col-md-6 mb-3">--}}
                                    {{--                                                                <label class="form-label">العنوان التفصيلي <span--}}
                                    {{--                                                                        class="text-danger">*</span></label>--}}
                                    {{--                                                                <textarea class="form-control" id="manual_address_text"--}}
                                    {{--                                                                          name="address" rows="3"--}}
                                    {{--                                                                          placeholder="أدخل العنوان التفصيلي..."></textarea>--}}
                                    {{--                                                            </div>--}}
                                    {{--                                                            <div class="col-md-6 mb-3">--}}
                                    {{--                                                                <label class="form-label">رقم الجوال</label>--}}
                                    {{--                                                                <input type="tel" class="form-control" id="manual_phone"--}}
                                    {{--                                                                       name="phone" placeholder="رقم الجوال للتواصل">--}}
                                    {{--                                                            </div>--}}
                                    {{--                                                            <div class="col-md-6 mb-3">--}}
                                    {{--                                                                <label class="form-label">اسم المستلم</label>--}}
                                    {{--                                                                <input type="text" class="form-control" id="manual_name"--}}
                                    {{--                                                                       name="name" placeholder="اسم المستلم">--}}
                                    {{--                                                            </div>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                        <div class="text-end">--}}
                                    {{--                                                            <button type="button" class="btn btn-secondary"--}}
                                    {{--                                                                    onclick="hideManualAddressInput()">--}}
                                    {{--                                                                <i class="fa-light fa-times me-2"></i>إلغاء--}}
                                    {{--                                                            </button>--}}
                                    {{--                                                            <button type="submit" class="btn btn-primary">--}}
                                    {{--                                                                <i class="fa-light fa-check me-2"></i>تأكيد العنوان--}}
                                    {{--                                                            </button>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                    </form>--}}
                                    {{--                                                </div>--}}
                                    {{--                                            </div>--}}
                                    {{--                                        </div>--}}
                                    {{--                                    </div>--}}
                                    <div class="col-12 d-flex justify-content-end mt-4 p-2">
                                        <button class="btn btn-label-secondary btn-prev">
                                            <span
                                                class="align-middle d-sm-inline-block d-none">{{ helperTrans('web.previous') }}</span>
                                        </button>
                                        <button class="btn-next">
                                            <span
                                                class="align-middle d-sm-inline-block d-none me-sm-1">{{ helperTrans('web.next') }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <!-- package step -->
                            <div id="package" class="content">
                                <div class="row">
                                    @forelse ($packages as $package)
                                        <div class="col-lg-6 col-12 p-2">
                                            <label for="p1{{ $package->id }}"
                                                   onclick="render_data('package',{{ $package->id }})">
                                                <div class="location" id="package-{{ $package->id }}">
                                                    <h6>{{ helperTrans('web.package') }}
                                                        <span>{{ $package->days_count }}</span>
                                                        {{ helperTrans('web.day') }}
                                                    </h6>
                                                    <ul>
                                                        <li class="d-flex align-items-center justify-content-between">
                                                            <p>{{ helperTrans('web.package_price_before_discount') }}
                                                            </p>
                                                            <span>{{ $package->original_price }} {{ helperTrans('ingaz.r_s') }}</span>
                                                        </li>
                                                        <li class="d-flex align-items-center justify-content-between">
                                                            <p>{{ helperTrans('web.discount_value') }}</p>
                                                            <span>{{ $package->original_price - $package->price_after_discount ?? 0 }}
                                                                {{ helperTrans('ingaz.r_s') }}</span>
                                                        </li>
                                                        <li class="d-flex align-items-center justify-content-between">
                                                            <p>{{ helperTrans('web.package_price_after_discount') }}
                                                            </p>
                                                            <span>{{ $package->price_after_discount }} {{ helperTrans('ingaz.r_s') }}</span>
                                                        </li>
                                                        <li class="d-flex align-items-center justify-content-between">
                                                            <p>{{ helperTrans('web.total_with_tax') }}</p>
                                                            <span>{{ $package->price_with_tax }} {{ helperTrans('ingaz.r_s') }}</span>
                                                        </li>
                                                    </ul>

                                                </div>
                                                <input type="radio" name="package_id" value="{{ $package->id }}"
                                                       id="p1{{ $package->id }}">
                                            </label>
                                        </div>
                                    @empty
                                        <div class="col-lg-6 col-12 p-2">
                                            {{__("ingaz.No_packages_to_show")}}
                                        </div>
                                    @endforelse
                                    <div class="col-12 d-flex justify-content-between mt-4 p-2">
                                        {{-- <button class="btn btn-label-secondary btn-prev"> --}}
                                        {{-- <span --}} {{--
                                        class="align-middle d-sm-inline-block d-none">{{helperTrans('web.previous')}}</span>
                                    --}}
                                        {{-- </button> --}}
                                        @if(isset($package) )
                                            <div class="d-flex p-2"
                                                 style="border: 2px solid #92beff7c; border-radius: 2px">
                                                <input class="" type="checkbox" name="deliver_maid" id="deliver_maid">
                                                <label class="" for="deliver_maid">
                                                    <span>{{ __('web.i_want_maid_delivered') }}</span>
                                                </label>
                                            </div>

                                            <button class="btn btn-primary btn-next">
                                            <span
                                                class="align-middle d-sm-inline-block d-none me-sm-1">{{ helperTrans('web.next') }}</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <!-- confirm step -->
                            <div id="confirm" class="content">
                                <div class="row">
                                    <div class="col-12 p-2">
                                        <div class="cv">
                                            <div class="cv_details">
                                                <div class="p-5 justify-content-center align-content-center">
                                                    <a href="#"
                                                       class="maidViewData justify-content-center align-content-center"
                                                       data-id="{{ @$maid->id }}">
                                                        @if ($maid->image != null)
                                                            <img src="{{ showFile($maid->image) }}" alt="cv1"
                                                                 style="border-radius: 50%; object-fit: cover; width: 130px; height: 130px;">
                                                        @else
                                                            <img
                                                                src="{{ $maid->cv_file ? showFile($maid->cv_file) : showFile($maid->cv_file) }}"
                                                                alt="cv1"
                                                                style="border-radius: 50%; object-fit: cover; width: 130px; height: 130px;">
                                                        @endif
                                                    </a>
                                                </div>
                                                <div class="info">
                                                    <ul>
                                                        <li>
                                                            <span>{{ helperTrans('web.nationality') }}</span>
                                                            <h3>{{ $maid->nationality->title  ?? '-'}}</h3>
                                                        </li>
                                                        <li>
                                                            <span>{{ helperTrans('web.salary') }}</span>
                                                            <h3>{{ $maid->salary }}{{ helperTrans('ingaz.r_s') }}</h3>
                                                        </li>
                                                        <li>
                                                            <span>{{ helperTrans('web.passport_number') }}</span>
                                                            <h3>{{ $maid->passport_num }}</h3>
                                                        </li>
                                                        <li>
                                                            <span>{{ helperTrans('web.religion') }} </span>
                                                            <h3>{{ $maid->religion->title  ?? '-'}}</h3>
                                                        </li>
                                                        {{-- <li>
                                                    <span>{{ helperTrans('web.recruitment_price') }}م</span>
                                                    <h3>{{ $maid->price }} {{ helperTrans('ingaz.r_s') }}</h3>
                                                </li> --}}
                                                        <li>
                                                            <span>{{ helperTrans('web.occupation') }}</span>
                                                            <h3>{{ $type->title }}</h3>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-12 p-2">
                                        <div class="llocation" id="llocation-l">

                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-12 p-2">
                                        <div class="ppackage" id="ppackage-p">

                                        </div>
                                    </div>
                                    <div class="col-12 p-2 d-flex gap-1">
                                        <input type="checkbox" class="check" required="required" name="accept"
                                               id="acceptCheckbox">
                                        <label for="accept" class="accept">{{ helperTrans('web.approve') }}
                                            <a
                                                href="{{ route('web.privacyPolicy') }}">{{ helperTrans('web.privacyPolicy') }}</a>
                                        </label>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between mt-4 p-2">
                                        <button class="btn btn-label-secondary btn-prev">
                                            <span
                                                class="align-middle d-sm-inline-block d-none">{{ helperTrans('web.previous') }}</span>
                                        </button>
                                        @auth
                                            <button disabled title="برجاء الموافقة على الشروط والأحكام" type="submit"
                                                    class="btn btn-primary btn-next" id="myButtonsave"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#payModal">
                                                <span class="align-middle d-sm-inline-block d-none me-sm-1">
                                                    {{-- {{ helperTrans('web.payment_and_confirm') }} --}}
                                                    {{ helperTrans('web.confirmation') }}
                                                </span>
                                            </button>
                                        @endauth
                                        @guest
                                            <a href="#" class="btns save-order-and-login"
                                               style="
                                                                                        display: block;
                                                                                        text-align: center;
                                                                                        width: 100%;
                                                                                        border: none;
                                                                                        background: none;
                                                                                        outline: none;
                                                                                        background: #1d70b7;
                                                                                        color: #fff;
                                                                                        padding: 8px;
                                                                                        border-radius: 4px;
                                                                                        transition: all 0.4s ease-in-out;
                                                                                    ">
                                                تسجيل الدخول لإكمال الطلب
                                            </a>
                                        @endguest
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <div class="modal fade add_location" id="addLocation" tabindex="-1" aria-labelledby="addLocationLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form class="form-wrap form-add-new-address" action="{{ route('site.user-addresses.store') }}"
                          method="post">
                        @csrf
                        <div class="row">
                            <div class="col-lg-6 col-12 p-2">
                                <div class="form-group">
                                    <label for="locationName">
                                        <i class="fa-light fa-map-pin"></i>
                                        {{ helperTrans('web.address_name') }}
                                    </label>
                                    <input id="locationName" name="title" type="text"
                                           placeholder="{{ helperTrans('web.house_example') }}">
                                </div>
                            </div>
                            <div class="col-lg-6 col-12 p-2">
                                <div class="form-group">
                                    <label for="reciever">
                                        <i class="fa-light fa-user"></i>
                                        {{ helperTrans('web.recipient_name') }}
                                    </label>
                                    <input id="reciever" name="name" type="text"
                                           placeholder="{{ helperTrans('web.write_full_name') }}">
                                </div>
                            </div>
                            <div class="col-lg-6 col-12 p-2">
                                <div class="form-group">
                                    <label for="phone">
                                        <i class="fa-light fa-phone"></i>
                                        {{ helperTrans('web.phone') }}
                                    </label>
                                    <input id="phone" name="phone" type="tel" placeholder="+996 0XXXXXXXX">
                                </div>
                            </div>
                            <div class="col-lg-6 col-12 p-2">
                                <div class="form-group">
                                    <label for="address">
                                        <i class="fa-light fa-location-dot"></i>

                                        {{ helperTrans('web.address') }}
                                    </label>
                                    <input id="address" name="address" type="text"
                                           placeholder="{{ helperTrans('web.example_city_name') }}">
                                </div>
                            </div>
                            <div class="col-lg-12 p-2">
                                <div class="form-group">
                                    <label for="details">
                                        <i class="fa-regular fa-comment-dots"></i>
                                        {{ helperTrans('web.additional_details') }}
                                    </label>
                                    <textarea name="notes" id="details"
                                              placeholder="{{ helperTrans('web.write_additional_details') }}"></textarea>
                                </div>
                            </div>
                            <div class="col-lg-12 p-2">
                                <div class="form-group mb-0">
                                    <button type="submit">
                                        {{ helperTrans('web.save') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script
        src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-3gJwYpM9hJpO3VKF6FmCw3taOQGEEQWQl86p3Hc48P0="
        crossorigin="anonymous"></script>
    <script>
        $(document).ready(function () {

            console.log("ahmed")
            console.log($('#form-of-user-address'))
            console.log("ahmed")

            $('.form-add-new-address').on('submit', function (e) {
                e.preventDefault();
                let formData = new FormData(this);
                let url = $(this).attr('action');
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        Toastify({
                            text: response.message,
                            duration: 4000,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)",
                            stopOnFocus: true
                        }).showToast();
                        $('.form-add-new-address')[0].reset();
                        $('.user_address').html(response.data.html);
                        $('#addLocation').modal('hide');

                    },
                    error: function (xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            let errorMessages = '';
                            for (let key in errors) {
                                errorMessages +=
                                    `<p style="margin: 0 0 5px; color: red; text-align: center;">${errors[key][0]}</p>`;
                            }
                            Swal.fire({
                                icon: 'error',
                                title: '{{ __('ingaz.error ') }}',
                                html: `<div>${errorMessages}</div>`,
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ __("ingaz.error") }}',
                                text: '{{ __("ingaz.error_from_server") }}'
                            });

                        }
                    }

                });
            })
            $('#wizard-property-listing-form').on('submit', function (e) {
                e.preventDefault();
                let formData = new FormData(this);
                let url = $(this).attr('action');
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        Toastify({
                            text: response.message,
                            duration: 4000,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)",
                            stopOnFocus: true
                        }).showToast();
                        setTimeout(function () {
                            location.href = response.data.redirect;
                        }, 500);

                    },
                    error: function (xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            let errorMessages = '';
                            for (let key in errors) {
                                errorMessages +=
                                    `<p style="margin: 0 0 5px; color: red; text-align: center;">${errors[key][0]}</p>`;
                            }
                            Swal.fire({
                                icon: 'error',
                                title: '{{ __('ingaz.error ') }}',
                                html: `<div>${errorMessages}</div>`,
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ __("ingaz.error") }}',
                                text: '{{ __("ingaz.error_from_server") }}'
                            });

                        }
                    }

                });
            })
        })


        document.addEventListener('DOMContentLoaded', function () {
            console.log("Type 2 order page loaded (simple solution)");

            // حفظ URL الحالي في localStorage
            const currentUrl = window.location.href;
            localStorage.setItem('returnUrl', currentUrl);
            console.log('Current URL saved:', currentUrl);

            // البحث عن زر تسجيل الدخول
            const saveButton = document.querySelector('.save-order-and-login');
            if (saveButton) {
                console.log('Save button found');

                saveButton.addEventListener('click', function (e) {
                    e.preventDefault();
                    console.log('Save order button clicked');

                    try {
                        // جمع بيانات الطلب
                        const form = document.querySelector('.TypeTwoForm');
                        const formData = new FormData(form);
                        const formDataString = new URLSearchParams(formData).toString();

                        const orderData = {
                            maid_id: document.querySelector('input[name="maid_id"]')?.value || '',
                            job_type_id: document.querySelector('input[name="job_type_id"]')?.value ||
                                '',
                            order_type: document.querySelector('input[name="order_type"]')?.value || '',
                            payment_method: document.querySelector('input[name="payment_method"]')
                                ?.value || '',
                            package_id: document.querySelector('input[name="package_id"]:checked')
                                ?.value || '',
                            deliver_maid: document.querySelector('input[name="deliver_maid"]')
                                ?.checked ? 1 : 0,
                            form_data: formDataString,
                            return_url: currentUrl
                        };

                        console.log('Order data to save:', orderData);

                        // حفظ البيانات في localStorage
                        localStorage.setItem('pendingOrder', JSON.stringify(orderData));
                        console.log('Data saved successfully');

                        // التوجيه إلى صفحة تسجيل الدخول
                        window.location.href = "{{ route('web.auth') }}?redirect=order_confirmation";
                    } catch (error) {
                        console.error('Error saving data:', error);
                        // في حالة الخطأ، التوجيه مباشرة
                        window.location.href = "{{ route('web.auth') }}?redirect=order_confirmation";
                    }
                });
            } else {
                console.log('Save button not found');
            }
        });

        // التحقق من وجود العناصر
        console.log('Checking elements...');
        console.log('Save order button exists:', $('.save-order-and-login').length);
        console.log('Form exists:', $('.TypeTwoForm').length);
        console.log('Maid ID input exists:', $('input[name="maid_id"]').length);

        // حفظ بيانات الطلب قبل التوجيه إلى تسجيل الدخول
        $('.save-order-and-login').on('click', function (e) {
            e.preventDefault();
            console.log('Save order button clicked (Type 2)');

            // جمع بيانات الطلب
            var formData = $('.TypeTwoForm').serialize();
            console.log('Form data (Type 2):', formData);

            var orderData = {
                maid_id: $('input[name="maid_id"]').val(),
                job_type_id: $('input[name="job_type_id"]').val(),
                order_type: $('input[name="order_type"]').val(),
                payment_method: $('input[name="payment_method"]').val(),
                package_id: $('input[name="package_id"]:checked').val(),
                deliver_maid: $('input[name="deliver_maid"]').is(':checked') ? 1 : 0,
                form_data: formData
            };

            console.log('Order data to save (Type 2):', orderData);

            // حفظ البيانات في localStorage
            try {
                localStorage.setItem('pendingOrder', JSON.stringify(orderData));
                console.log('Data saved to localStorage successfully (Type 2)');
                console.log('Verification - localStorage now contains:', localStorage.getItem('pendingOrder'));
            } catch (error) {
                console.error('Error saving to localStorage (Type 2):', error);
            }

            // التوجيه إلى صفحة تسجيل الدخول مع معامل إضافي
            console.log('Redirecting to login page (Type 2)...');
            window.location.href = '{{ route('web.auth') }}?redirect=order_confirmation';
        });


        // دالة إظهار حقل إدخال العنوان يدوياً
        function showManualAddressInput() {
            // $('#manual_address_input').show();
            // $('#manual-address-option').css('border', '2px solid #007bff');
            // $('#skip-address-option').css('border', '2px dashed #6c757d');
            $('#addLocation').modal('show');

        }

        // دالة إخفاء حقل إدخال العنوان يدوياً
        function hideManualAddressInput() {
            $('#manual_address_input').hide();
            $('#manual-address-option').css('border', '2px dashed #007bff');
            $('#manual_address').prop('checked', false);
        }

        // دالة تخطي العنوان
        function skipAddress() {
            $('#manual_address_input').hide();
            $('#manual-address-option').css('border', '2px dashed #007bff');
            $('#skip-address-option').css('border', '2px solid #6c757d');
            $('#manual_address').prop('checked', false);
            $('#skip_address').prop('checked', true);

            // تعيين قيمة فارغة للعنوان (null بدلاً من skip)
            $('input[name="details_list[address_id]"]').val('');
            $('#llocation-l').html(
                '<div class="alert alert-info"><i class="fa-light fa-info-circle me-2"></i>تم تخطي اختيار العنوان</div>'
            );

            // إزالة أي validation errors
            $('.error-message').remove();
            $('.alert-danger').remove();
        }

        // دالة تأكيد العنوان اليدوي
        // function confirmManualAddress() {
        //     let city = $('#manual_city').val();
        //     let address = $('#manual_address_text').val();
        //     let phone = $('#manual_phone').val();
        //     let name = $('#manual_name').val();
        //
        //     if (!city) {
        //         Swal.fire({icon: 'warning', text: 'يرجى إدخال المدينة'});
        //         return;
        //     }
        //
        //     // عرض العنوان
        //     let manualAddressHtml = `
        //             <div class="alert alert-success">
        //                 <h6><i class="fa-light fa-edit me-2"></i>العنوان اليدوي</h6>
        //                 <ul class="mb-0">
        //                     <li><i class="fa-light fa-user"></i>${name || 'غير محدد'}</li>
        //                     <li><i class="fa-light fa-phone"></i>${phone || 'غير محدد'}</li>
        //                     <li><i class="fa-light fa-location-dot"></i>${city} - ${address}</li>
        //                 </ul>
        //             </div>`;
        //     $('#llocation-l').html(manualAddressHtml);
        //
        //     let form = $('#form-of-user-address');
        //     let formData = new FormData(form[0]);
        //     let url = form.attr('action');
        //     let method = form.attr('method');
        //     console.log(url)
        //     console.log(method)
        //
        //     $.ajax({
        //         url: url,
        //         type: method,
        //         data: formData,
        //         processData: false,
        //         contentType: false,
        //         success: function (response) {
        //             Toastify({
        //                 text: response.message,
        //                 duration: 4000,
        //                 gravity: "top",
        //                 position: "right",
        //                 backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)",
        //                 stopOnFocus: true
        //             }).showToast();
        //             form[0].reset();
        //             hideManualAddressInput();
        //         },
        //         error: function (xhr) {
        //             if (xhr.status === 422) {
        //                 let errors = xhr.responseJSON.errors;
        //                 let errorMessages = '';
        //                 for (let key in errors) {
        //                     errorMessages += `<p style="margin:0 0 5px;color:red;text-align:center;">${errors[key][0]}</p>`;
        //                 }
        //                 Swal.fire({
        //                     icon: 'error',
        //                     title: 'خطأ في الإدخال',
        //                     html: `<div>${errorMessages}</div>`,
        //                 });
        //             } else {
        //                 Swal.fire({
        //                     icon: 'error',
        //                     title: 'خطأ',
        //                     text: 'حدث خطأ من الخادم'
        //                 });
        //             }
        //         }
        //     });
        // }

        // إضافة مستمع لراديو العناوين
        $('input[name="address_id"]').change(function () {
            var value = $(this).val();

            if (value === 'manual') {
                showManualAddressInput();
            } else if (value === 'skip') {
                skipAddress();
            } else {
                hideManualAddressInput();
                $('#skip-address-option').css('border', '2px dashed #6c757d');

                // تعيين العنوان المحدد
                $('#details_list_address_id').val(value);
            }
        });
    </script>
    <script>
        $('#closeMod').click(function () {
            window.location = '/';
        });


        $(document).ready(function () {
            // استعادة بيانات الطلب المحفوظة
            @if (session('restore_order_data'))
            var restoreData = @json(session('restore_order_data'));
            console.log('Restoring order data:', restoreData);

            // استعادة البيانات في النموذج
            if (restoreData.maid_id) {
                $("input[name='maid_id']").val(restoreData.maid_id);
            }
            if (restoreData.job_type_id) {
                $("input[name='job_type_id']").val(restoreData.job_type_id);
            }
            if (restoreData.order_type) {
                $("input[name='order_type']").val(restoreData.order_type);
            }
            if (restoreData.payment_method) {
                $("input[name='payment_method']").val(restoreData.payment_method);
            }
            if (restoreData.admin_id) {
                $("input[name='details_list[admin_id]'][value='" + restoreData.admin_id + "']").prop('checked',
                    true);
            }
            if (restoreData.address_id) {
                $("input[name='details_list[address_id]']").val(restoreData.address_id);
            }
            if (restoreData.package_id) {
                $("input[name='details_list[package_id]']").val(restoreData.package_id);
            }

            // إظهار رسالة للمستخدم
            Swal.fire({
                title: 'تم استعادة بيانات الطلب',
                text: 'تم استعادة بيانات الطلب المحفوظة مسبقاً',
                icon: 'success',
                timer: 3000,
                showConfirmButton: false
            });
            @endif

            $('.newPayFormClass').off('submit').on('submit', function (event) {
                event.preventDefault();
                var formData = $(this).serialize(); // Serialize the form data
                let submitButton = $(event.originalEvent.submitter);
                let buttonText = submitButton.text();
                submitButton.prop('disabled', true); // Disable the button

                var selectedAgentId = $('.agents input[type="radio"]:checked').val();
                // Update the hidden input value with the selected agent's ID
                $('input[name="details_list[admin_id]"]').val(selectedAgentId);


                // التحقق من تسجيل الدخول قبل إرسال الطلب
                if (!{{ auth()->check() ? 'true' : 'false' }}) {
                    // إذا لم يكن مسجل دخول، اعرض رسالة تطلب تسجيل الدخول
                    Swal.fire({
                        title: 'تسجيل الدخول مطلوب',
                        text: 'يجب تسجيل الدخول لتأكيد طلب التأجير',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'تسجيل الدخول',
                        cancelButtonText: 'إلغاء',
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // توجيه المستخدم لصفحة تسجيل الدخول
                            window.location.href = '{{ route('web.auth') }}';
                        }
                    });
                    // إعادة تفعيل الزر
                    submitButton.prop('disabled', false);
                    submitButton.html(buttonText);
                    return;
                }

                $.ajax({
                    type: 'post',
                    url: '{{ route('web.orders.store') }}',
                    data: formData,
                    beforeSend: function () {
                        submitButton.html(
                            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' +
                            buttonText);
                    },
                    complete: function () {
                        submitButton.html("{{ helperTrans('web.confirmation') }}");
                        submitButton.prop('disabled', false); // Re-enable the button
                    },
                    success: function (response) {
                        console.log('Response:', response);
                        var data = response.data;

                        if (response.code == 410) {
                            errorToster('{{ __('auth.an error occurred') }}', response
                                .message);
                            window.location = '/';
                            return;
                        }

                        // إظهار رسالة نجاح
                        Swal.fire({
                            title: 'تم تأكيد الطلب بنجاح!',
                            text: 'سيتم التواصل معك قريباً لتأكيد التفاصيل',
                            icon: 'success',
                            confirmButtonText: 'حسناً',
                            confirmButtonColor: '#28a745'
                        }).then((result) => {
                            // التوجيه لصفحة الطلب أو الصفحة الرئيسية
                            if (data && data.order && data.order.id) {
                                window.location =
                                    "{{ route('web.orders.show', ['order' => ':orderId']) }}"
                                        .replace(':orderId', data.order.id);
                            } else {
                                window.location = "{{ route('web.orders.index') }}";
                            }
                        });
                    },
                    error: function (xhr, status, error) {
                        console.error('Error:', xhr.responseText);

                        // إعادة تفعيل الزر
                        submitButton.prop('disabled', false);
                        submitButton.html(buttonText);

                        // إظهار رسالة خطأ
                        var errorMessage = 'حدث خطأ أثناء تأكيد الطلب';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.status === 401) {
                            errorMessage = 'يجب تسجيل الدخول لتأكيد الطلب';
                        } else if (xhr.status === 422) {
                            errorMessage = 'بيانات الطلب غير صحيحة';
                        }

                        Swal.fire({
                            title: 'خطأ!',
                            text: errorMessage,
                            icon: 'error',
                            confirmButtonText: 'حسناً',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                });
            });
        });

        var firstPage = null;
        var secondPage = null;
        var currentStep = 1;

        $(document).ready(function () {
            const isLoggedIn = @json(auth()->check());
            var stepper = new Stepper($('.bs-stepper')[0]);
            $('.btn-next').on('click', function () {
                if (!isLoggedIn) {
                    const currentUrl = window.location.href;
                    fetch("{{ route('site.store.intended.url') }}", {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": "{{ csrf_token() }}",
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({redirect_url: currentUrl})
                    }).then(() => {
                        window.location.href = "{{ route('site.login.index') }}";
                    });

                    return;
                }

                if (stepper._currentIndex === 1) {
                    if ((firstPage == null || firstPage === '' || firstPage === undefined) && $(
                        'input[name="address_id"]:checked').val() !== 'skip' && $(
                        'input[name="address_id"]:checked').val() !== 'manual') {
                        infoToster('{{ helperTrans('auth.an error occurred') }}',
                            "{{ helperTrans('web.address_required') }}");
                        return true;
                    }
                } else if (stepper._currentIndex === 0) {
                    if (secondPage == null) {
                        infoToster('{{ helperTrans('auth.an error occurred') }}',
                            "{{ helperTrans('web.package_required') }}");

                        return true;
                    }
                }
                console.log(stepper._steps);
                stepper.next();
                currentStep++;
                if (stepper._currentIndex === 2 && currentStep == 3) {
                    infoToster('', "{{ helperTrans('web.terms_required') }}");
                }

            });
            $('.btn-prev').on('click', function () {
                currentStep--;
                stepper.previous();
            });


        });

        function render_data(type, id) {
            var elementId = type == 'package' ? 'package-' + id : 'address-' + id;
            var myElementHtml = document.getElementById('' + elementId).innerHTML;
            if (type == 'package') {
                secondPage = id;
                document.getElementById('ppackage-p').innerHTML = myElementHtml;
                document.getElementById('details_list_package_id').value = id;

            } else {
                firstPage = id;
                document.getElementById('llocation-l').innerHTML = myElementHtml;
                $('input[name="details_list[address_id]"]').val(id);

                // إعادة تعيين حدود الخيارات الجديدة
                if (typeof $('#manual-address-option') !== 'undefined') {
                    $('#manual-address-option').css('border', '2px dashed #007bff');
                    $('#skip-address-option').css('border', '2px dashed #6c757d');
                }
            }
        }

        // دالة تخطي العنوان
        function skipAddress() {
            firstPage = 'skip';
            $('input[name="details_list[address_id]"]').val('');
            document.getElementById('llocation-l').innerHTML =
                '<div class="alert alert-info"><i class="fa-light fa-info-circle me-2"></i>تم تخطي اختيار العنوان</div>';

            // إعادة تعيين حدود الخيارات
            if (typeof $('#manual-address-option') !== 'undefined') {
                $('#manual-address-option').css('border', '2px dashed #007bff');
                $('#skip-address-option').css('border', '2px solid #6c757d');
            }
        }

        // دالة إدخال العنوان يدوياً
        function manualAddress() {
            firstPage = 'manual';
            $('input[name="details_list[address_id]"]').val('');
            // سيتم إضافة العنوان اليدوي لاحقاً
        }

        // Get references to the checkbox and button
        var myCheckbox = document.getElementById('acceptCheckbox');
        var myButton = document.getElementById('myButtonsave');

        // Attach event listener to the checkbox
        myCheckbox.addEventListener('change', function () {
            // Enable or disable the button based on the checkbox state
            myButton.disabled = !this.checked;
        });
    </script>
    <script>
        $(document).on('change', 'input[name="package_id"]', function () {
            var selectedId = $(this).val();
            $('input[name="details_list[package_id]"]').val(selectedId);
        });
    </script>
    <script>
        $(document).ready(function () {
            const createAction = "{{ route('web.addresses.store') }}";

            // فتح المودال للتعديل
            $(document).on('click', '.edit-btn', function () {
                const route = $(this).attr('model-route');
                const modal = $('#addLocation');

                modal.find('input, select, textarea').val('');
                modal.addClass('loading');

                $.ajax({
                    url: route,
                    type: 'GET',
                    success: function (res) {
                        modal.removeClass('loading');
                        if (res?.data) {
                            const address = res.data;

                            modal.find('input[name="title"]').val(address.title);
                            modal.find('input[name="name"]').val(address.name);
                            modal.find('input[name="phone"]').val(address.phone);
                            modal.find('input[name="address"]').val(address.address);
                            modal.find('textarea[name="notes"]').val(address.notes);
                            modal.find('select[name="city_id"]').val(address.city_id).change();
                            modal.find('input[name="id"]').val(address.id);

                            const form = modal.find('form');
                            form.attr('action', '/user-addresses/' + address.id);

                            if (!form.find('input[name="_method"]').length) {
                                form.append('<input type="hidden" name="_method" value="PUT">');
                            }

                            modal.find('.modal-title').text('تعديل العنوان');
                            modal.modal('show');
                        }
                    },
                    error: function () {
                        modal.removeClass('loading');
                        console.error('حدث خطأ أثناء تحميل البيانات');
                    }
                });
            });

            // إعادة الفورم للوضع الأصلي عند غلق المودال
            $('#addLocation').on('hidden.bs.modal', function () {
                const form = $(this).find('form');
                form.attr('action', createAction);
                form.attr('method', 'POST');
                form.find('input[name="_method"]').remove();
                form.find('input, select, textarea').val('');
                $(this).find('.modal-title').text('إضافة عنوان جديد');
            });

            // إرسال الفورم ب AJAX
            $(document).one('submit', '#addLocation form').on('submit', '#addLocation form', function (e) {
                e.preventDefault();
                const form = $(this);
                const url = form.attr('action');
                const method = form.find('input[name="_method"]').val() || form.attr('method');

                $.ajax({
                    url: url,
                    type: method,
                    data: form.serialize(),
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (res) {
                        if (res?.data?.html) {
                            $('.user_address').html(res.data.html); // إعادة ريندر لجميع العناوين
                        }
                        $('#addLocation').modal('hide');
                    },
                    error: function () {
                        console.error('حدث خطأ أثناء الحفظ');
                    }
                });
            });

            // حذف العنوان
            $(document).on('click', '.delete-btn', function () {
                const id = $(this).data('id');
                if (!confirm('هل أنت متأكد من الحذف؟')) return;

                $.ajax({
                    url: '/user-addresses/' + id,
                    type: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(res) {
                        if (res?.data?.html) {
                            $('.user_address').html(res.data.html); // إعادة ريندر بعد الحذف
                        }
                    },
                    error: function() {
                        console.error('حدث خطأ أثناء الحذف');
                    }
                });
            });
        });
    </script>


@endsection
