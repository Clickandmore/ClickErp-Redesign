<section class="rentingWorkers">
    <div class="container">
        <div class="bs-stepper vertical">
            <div class="bs-stepper-header">
                <!-- package step btn -->
                <div class="step" data-target="#package">
                    <button type="button" class="step-trigger">
                        <span class="bs-stepper-circle">
                            <img src="{{ asset('web') }}\assets/images/package.png" alt="package">
                        </span>
                        <span class="bs-stepper-label">
                            <span class="bs-stepper-title">{{ helperTrans('web.select_package') }}</span>
                            <span class="bs-stepper-subtitle">{{ helperTrans('web.select_appropriate_package')
                                }}</span>
                        </span>
                    </button>
                </div>

                <!-- location step btn -->
                <div class="step" data-target="#location">
                    <button type="button" class="step-trigger">
                        <span class="bs-stepper-circle">
                            <img src="{{ asset('web') }}\assets/images/location.png" alt="location">
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
                            <img src="{{ asset('web') }}\assets/images/confirm.png" alt="confirm">
                        </span>
                        <span class="bs-stepper-label">
                            <span class="bs-stepper-title">{{ helperTrans('web.confirm_rental_request') }}</span>
                            <span class="bs-stepper-subtitle">
                                {{ helperTrans('web.confirm_and_pay') }}
                            </span>
                        </span>
                    </button>
                </div>
            </div>
            <div class="bs-stepper-content">
                <form id="wizard-property-listing-form" class="TypeTwoForm" onSubmit="return false">
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
                                    model-title="{{ helperTrans('web.add_address') }}">{{
                                    helperTrans('web.add_address') }}
                                </button>
                            </div>
                            <div class="addresses_div row">
                                @foreach ($addresses as $address)
                                <div class="col-lg-6 col-12 p-2">
                                    <label for="l1{{ $address->id }}"
                                        onclick="render_data('address',{{ $address->id }})">
                                        <div class="location" id="address-{{ $address->id }}">
                                            <h6>{{ $address->title }}</h6>
                                            <ul>
                                                <li>
                                                    <i class="fa-light fa-user"></i>{{ $address->name }}
                                                </li>
                                                <li>
                                                    <i class="fa-light fa-phone"></i>
                                                    {{ $address->phone }}
                                                </li>
                                                <li>
                                                    <i class="fa-light fa-location-dot"></i>{{ $address->address }}
                                                </li>
                                            </ul>

                                        </div>
                                        <input type="radio" name="address_id" value="{{ $address->id }}" id="l1{{ $address->id }}">
                                    </label>
                                </div>
                                @endforeach
                            </div>
                            <div class="col-12 d-flex justify-content-end mt-4 p-2">
                                <button class="btn btn-label-secondary btn-prev">
                                    <span class="align-middle d-sm-inline-block d-none">{{
                                        helperTrans('web.previous') }}</span>
                                </button>
                                <button class="btn-next">
                                    <span class="align-middle d-sm-inline-block d-none me-sm-1">{{
                                        helperTrans('web.next') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- package step -->
                    <div id="package" class="content">
                        <div class="row">
                            @foreach ($packages as $package)
                            <div class="col-lg-6 col-12 p-2">
                                <label for="p1{{ $package->id }}" onclick="render_data('package',{{ $package->id }})">
                                    <div class="location" id="package-{{ $package->id }}">
                                        <h6>{{ helperTrans('web.package') }}
                                            <span>{{ $package->days_count }}</span>
                                            {{ helperTrans('web.day') }}
                                        </h6>
                                        <ul>
                                            <li class="d-flex align-items-center justify-content-between">
                                                <p>{{ helperTrans('web.package_price_before_discount') }}
                                                </p>
                                                <span>{{ $package->original_price }} ر.س</span>
                                            </li>
                                            <li class="d-flex align-items-center justify-content-between">
                                                <p>{{ helperTrans('web.discount_value') }}</p>
                                                <span>{{ $package->original_price - $package->price_after_discount
                                                    ?? 0 }}
                                                    ر.س</span>
                                            </li>
                                            <li class="d-flex align-items-center justify-content-between">
                                                <p>{{ helperTrans('web.package_price_after_discount') }}
                                                </p>
                                                <span>{{ $package->price_after_discount }} ر.س</span>
                                            </li>
                                            <li class="d-flex align-items-center justify-content-between">
                                                <p>{{ helperTrans('web.total_with_tax') }}</p>
                                                <span>{{ $package->price_with_tax }} ر.س</span>
                                            </li>
                                        </ul>

                                    </div>
                                    <input type="radio" name="package_id" value="{{ $package->id }}" id="p1{{ $package->id }}">
                                </label>
                            </div>
                            @endforeach
                            <div class="col-12 d-flex justify-content-between mt-4 p-2">
                                {{-- <button class="btn btn-label-secondary btn-prev"> --}}
                                    {{-- <span --}} {{--
                                        class="align-middle d-sm-inline-block d-none">{{helperTrans('web.previous')}}</span>
                                    --}}
                                    {{-- </button> --}}

                                <div class="d-flex p-2" style="border: 2px solid #92beff7c; border-radius: 2px">
                                    <input class="" type="checkbox" name="deliver_maid" id="deliver_maid">
                                    <label class="" for="deliver_maid">
                                        <span>{{ __('web.i_want_maid_delivered') }}</span>
                                    </label>
                                    @push('js')
                                    <script>
                                        $(document).ready(function() {
                                            $('#deliver_maid').change(function() {
                                                if($(this).is(':checked')) {
                                                    $('#details_list_deliver_maid').val(1);
                                                    $('.new_deliver_maid').val(1);
                                                } else {
                                                    $('#details_list_deliver_maid').val(0);
                                                    $('.new_deliver_maid').val(0);
                                                }
                                            });
                                        });
                                    </script>
                                    @endpush
                                </div>

                                <button class="btn btn-primary btn-next">
                                    <span class="align-middle d-sm-inline-block d-none me-sm-1">{{
                                        helperTrans('web.next') }}</span>
                                </button>
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
                                            <a href="#" class="maidViewData justify-content-center align-content-center"
                                                data-id="{{ @$maid->id }}">
                                                @if ($maid->image != null)
                                                <img src="{{ showFile($maid->image) }}" alt="cv1"
                                                    style="border-radius: 50%; object-fit: cover; width: 130px; height: 130px;">
                                                @else
                                                <img src="{{ $maid->cv_preview ? showFile($maid->cv_preview) : showFile($maid->cv_file) }}"
                                                    alt="cv1"
                                                    style="border-radius: 50%; object-fit: cover; width: 130px; height: 130px;">
                                                @endif
                                            </a>
                                        </div>
                                        <div class="info">
                                            <ul>
                                                <li>
                                                    <span>{{ helperTrans('web.nationality') }}</span>
                                                    <h3>{{ $maid->nationality->title }}</h3>
                                                </li>
                                                <li>
                                                    <span>{{ helperTrans('web.salary') }}</span>
                                                    <h3>{{ $maid->salary }} ر.س</h3>
                                                </li>
                                                <li>
                                                    <span>{{ helperTrans('web.passport_number') }}</span>
                                                    <h3>{{ $maid->passport_num }}</h3>
                                                </li>
                                                <li>
                                                    <span>{{ helperTrans('web.religion') }} </span>
                                                    <h3>{{ $maid->religion->title }}</h3>
                                                </li>
                                                {{-- <li>
                                                    <span>{{ helperTrans('web.recruitment_price') }}م</span>
                                                    <h3>{{ $maid->price }} ر.س</h3>
                                                </li> --}}
                                                <li>
                                                    <span>{{ helperTrans('web.occupation') }}</span>
                                                    <h3>{{ $type->title }}</h3>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    {{-- <div class="cv_details"> --}}
                                        {{-- <div class="img"> --}}
                                            {{-- <a href="assets/images/cv1.jpg" data-fancybox="w1"> --}}
                                                {{-- <img src="assets/images/cv1.jpg" alt="cv1"> --}}
                                                {{-- </a> --}}
                                            {{-- </div> --}}
                                        {{-- <div class="info"> --}}
                                            {{-- <ul> --}}
                                                {{-- <li> --}}
                                                    {{-- <span>الجنسية</span> --}}
                                                    {{-- <h3>بنجلاديش</h3> --}}
                                                    {{-- </li> --}}
                                                {{-- <li> --}}
                                                    {{-- <span>الراتب</span> --}}
                                                    {{-- <h3>1000 ر.س</h3> --}}
                                                    {{-- </li> --}}
                                                {{-- <li> --}}
                                                    {{-- <span>رقم الجواز</span> --}}
                                                    {{-- <h3>Ep01889</h3> --}}
                                                    {{-- </li> --}}
                                                {{-- <li> --}}
                                                    {{-- <span>الديانة </span> --}}
                                                    {{-- <h3>مسلمة</h3> --}}
                                                    {{-- </li> --}}
                                                {{-- <li> --}}
                                                    {{-- <span>تكلفة الاستقدام</span> --}}
                                                    {{-- <h3>2864 ر.س</h3> --}}
                                                    {{-- </li> --}}
                                                {{-- <li> --}}
                                                    {{-- <span>الوظيفة</span> --}}
                                                    {{-- <h3>عاملة منزلية</h3> --}}
                                                    {{-- </li> --}}
                                                {{-- </ul> --}}
                                            {{-- </div> --}}
                                        {{-- </div> --}}
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
                                    <a href="{{ route('web.privacyPolicy') }}">{{ helperTrans('web.privacyPolicy')
                                        }}</a>
                                </label>
                            </div>
                            <div class="col-12 d-flex justify-content-between mt-4 p-2">
                                <button class="btn btn-label-secondary btn-prev">
                                    <span class="align-middle d-sm-inline-block d-none">{{
                                        helperTrans('web.previous') }}</span>
                                </button>
                                <button disabled title="برجاء الموافقة على الشروط والأحكام" type="submit"
                                    class="btn btn-primary btn-next"
                                     id="myButtonsave"
                                    data-bs-toggle="modal"
                                    data-bs-target="#payModal"
                                    >
                                    <span class="align-middle d-sm-inline-block d-none me-sm-1">
                                        {{-- {{ helperTrans('web.payment_and_confirm') }} --}}
                                        {{ helperTrans('web.confirmation') }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
</section>


@push('js')
<script>
    $(document).ready(function() {
        // Function to update hidden fields when radio buttons are selected
        function updateHiddenFields() {
            var selectedAddressId = $('input[name="address_id"]:checked').val();
            var selectedPackageId = $('input[name="package_id"]:checked').val();

            $('input[name="details_list[address_id]"]').val(selectedAddressId);
            $('input[name="details_list[package_id]"]').val(selectedPackageId);
        }

        // Trigger updateHiddenFields on radio button change
        $('input[name="address_id"], input[name="package_id"]').change(function() {
            updateHiddenFields();
        });

        // Form submission handling
        // $('.TypeTwoForm').off('submit').on('submit', function(event) {
        //     event.preventDefault();
        //     var formData = $(this).serialize(); // Serialize the form data
        //     let submitButton = $(event.originalEvent.submitter);
        //     let buttonText = submitButton.text();
        //     submitButton.prop('disabled', true); // Disable the button

        //     $.ajax({
        //         type: 'post',
        //         url: '{{ route('web.orders.store') }}',
        //         data: formData,
        //         beforeSend: function () {
        //             submitButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + buttonText);
        //         },
        //         complete: function () {
        //             submitButton.html("{{ helperTrans('web.confirmation') }}");
        //             submitButton.prop('disabled', false); // Re-enable the button
        //         },
        //         success: function (response) {
        //             var data = response.data;

        //             if (response.code == 410) {
        //                 errorToster('{{ __('auth.an error occurred') }}', response.message);
        //                 window.location = '/';
        //                 return;
        //             }

        //             // Redirect for non-bank transfer payments
        //             window.location = "{{ route('web.orders.show', ['order' => ':orderId']) }}"
        //                 .replace(':orderId', data.order.id);

        //         },
        //         error: function (error) {
        //             console.error(error);
        //         }
        //     });
        // });
    });
</script>
@endpush
