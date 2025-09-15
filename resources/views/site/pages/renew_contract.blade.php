@extends('site.layouts.index')
@section('title')
@endsection
@section('content')
    <!-- Main Banner  -->
    <section class="mainBanner">
        <button onclick="goBack()" class="Back">
            <i class="fas fa-angle-left"></i>
        </button>
        <ul>
            <li>
                <a href="{{route('site.home.index')}}"> {{__("ingaz.home")}} </a>
            </li>
            <li>
                <a href="{{route('site.home.index')}}#services">{{__("ingaz.services")}} </a>
            </li>
            <li>
                <a href="#!" class="active">{{__("ingaz.renewing_a_worker_contract")}}  </a>
            </li>
        </ul>
        <figure>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                preserveAspectRatio="none"
                x="0px"
                y="0px"
                viewBox="0 0 300 100"
                style="margin-bottom: -35px; enable-background: new 0 0 300 100"
                class="injected-svg js-svg-injector"
                data-parent="#SVGwave1BottomSMShape">
                <g>
                    <path
                        class="wave-bottom-1-sm-0"
                        fill="#fdfdfd"
                        opacity=".8"
                        d="M10.9,63.9c0,0,42.9-34.5,87.5-14.2c77.3,35.1,113.3-2,146.6-4.7C293.7,41,315,61.2,315,61.2v54.4H10.9V63.9z"
                    ></path>
                    <path
                        class="wave-bottom-1-sm-0"
                        fill="#fdfdfd"
                        opacity=".6"
                        d="M-55.7,64.6c0,0,42.9-34.5,87.5-14.2c77.3,35.1,113.3-2,146.6-4.7c48.7-4.1,69.9,16.2,69.9,16.2v54.4H-55.7     V64.6z"
                    ></path>
                    <path
                        class="wave-bottom-1-sm-0"
                        fill="#fdfdfd"
                        opacity=".4"
                        fill-opacity="0"
                        d="M23.4,118.3c0,0,48.3-68.9,109.1-68.9c65.9,0,98,67.9,98,67.9v3.7H22.4L23.4,118.3z"
                    ></path>
                    <path
                        class="wave-bottom-1-sm-0"
                        fill="#fdfdfd"
                        d="M-54.7,83c0,0,56-45.7,120.3-27.8c81.8,22.7,111.4,6.2,146.6-4.7c53.1-16.4,104,36.9,104,36.9l1.3,36.7l-372-3     L-54.7,83z"
                    ></path>
                </g>
            </svg>
        </figure>
    </section>

    <section class="contractRenew">
        <div class="container">
            <div class="headTitle text-center">
                <h2>{{__("ingaz.contract_renewal")}}</h2>
                <p>
                    {{__("ingaz.contract_renewal_text")}}
                </p>
            </div>

            <div class="row">
                <!-- جزء بيانات العميل -->
                <div class="col-md-4 p-3">
                    <div class="mb-1">
                        <h2>{{__("ingaz.contract_renewal")}}</h2>
                    </div>
                    <form id="form-renew-contract" method="post" action="{{route('site.renew-contract.store')}}"
                          class="needs-validation" novalidate>
                        <!-- اسم العميل -->
                        <div class="mb-3">
                            <label class="form-label">{{__("ingaz.client_name")}} *</label>
                            <input type="text" class="form-control" name="client_name" placeholder=""/>
                            <div class="invalid-feedback">{{__("ingaz.this_field_is_required")}}</div>
                        </div>

                        <!-- الهاتف -->
                        <div class="mb-3">
                            <label class="form-label">{{__("ingaz.client_phone")}} *</label>
                            <input type="tel" class="form-control" name="client_phone"/>
                            <div class="invalid-feedback">{{__("ingaz.this_field_is_required")}}</div>
                        </div>

                        <!-- العنوان -->
                        <div class="mb-3">
                            <label class="form-label">{{__("ingaz.client_address")}} *</label>
                            <input type="text" class="form-control" name="client_address"/>
                        </div>

                        <!-- المدينة -->
                        <div class="mb-3">
                            <label class="form-label">{{__("ingaz.city")}} *</label>
                            <input type="text" class="form-control" name="city"/>
                        </div>

                        <!-- معلومات تهمك -->
                        <div class="alert info mt-4">
                            <h6>{{__("ingaz.information_of_interest_to_you")}}:</h6>
                            <p>
                                {{__("ingaz.information_of_interest_to_you_text_1")}}
                            </p>
                            <p>
                                {{__("ingaz.information_of_interest_to_you_text_2")}}
                            </p>
                            <p>
                                <strong>:{{__("ingaz.company_owner")}} </strong> بلاتينيوم الشرقية للاستقدام
                            </p>
                            <p><strong>:{{__("ingaz.account_number")}}</strong> 09249888000204</p>
                            <p>
                                <strong>:{{__("ingaz.IBAN_number")}}</strong> SA08 1000 0009 2498 8800 0204
                            </p>
                        </div>

                </div>

                <!-- جزء بيانات العاملة -->
                <form class="col-md-8 p-3">
                    <div class="mb-1">
                        <h4>{{__("ingaz.worker_data")}}</h4>
                    </div>

                    <!-- بيانات أساسية -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{__("ingaz.worker_name")}}</label>
                        <input type="text" class="form-control" name="worker_name"/>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{__("ingaz.worker_phone")}}</label>
                        <input type="tel" class="form-control" name="worker_phone"/>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{__("ingaz.worker_address")}}</label>
                        <input type="text" class="form-control" name="worker_address"/>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{__("ingaz.passport_number")}}</label>
                        <input type="text" class="form-control" name="passport_number"/>
                    </div>

                    <!-- بيانات الأقارب -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{__("ingaz.name_of_relative")}}</label>
                        <input type="text" class="form-control" name="name_of_relative"/>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{__("ingaz.address_of_relative")}}</label>
                        <input type="text" class="form-control" name="address_of_relative"/>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{__("ingaz.phone_of_relative")}}</label>
                        <input type="tel" class="form-control" name="phone_of_relative"/>
                    </div>

                    <!-- بيانات العاملة الإضافية -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{__("ingaz.worker_nationality")}}</label>
                        <select class="form-control" name="nationality_id">
                            <option disabled selected>{{__("ingaz.please_select_an_option")}}</option>
                            @foreach($nationalities as $nationality)
                                <option value="{{$nationality->id}}">{{$nationality->title}}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{__("ingaz.worker_new_salary")}}</label>
                        <input type="number" class="form-control" name="worker_new_salary"/>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{__("ingaz.worker_old_salary")}}</label>
                        <input type="number" class="form-control" name="worker_old_salary"/>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{__("ingaz.the_worker_was_brought_in_from")}}</label>
                        <select class="form-control" name="worker_brought_from">
                            <option disabled selected>{{__("ingaz.please_select_an_option")}}</option>
                            <option value="company">{{__("ingaz.company")}}</option>
                            <option value="another_office">{{__("ingaz.another_office")}}</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{__("ingaz.social_status_of_the_worker")}}</label>
                        <select class="form-control" name="worker_marital_status">
                            <option disabled selected>{{__("ingaz.please_select_an_option")}}</option>
                            @foreach($maritalStatus as $maritalState)
                                <option
                                    value="{{$maritalState->value}}">{{\App\Enums\Office\MaritalStatusEnum::tryFrom($maritalState->value)->lang()}}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- مرفقات الملفات -->
                    <div class="col-md-12">
                        <h5 class="mt-4">{{__("ingaz.required_files")}}</h5>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{__("ingaz.Sponsor_Civil_Status_Card")}}</label>
                        <input type="file" class="form-control" name="sponsor_card"/>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{__("ingaz.worker_residence")}}</label>
                        <input type="file" class="form-control" name="worker_residence"/>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{__("ingaz.the_first_page_of_the_worker_passport")}}</label>
                        <input type="file" class="form-control" name="first_page_of_the_worker_passport"/>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{__("ingaz.travel_tickets")}}</label>
                        <input type="file" class="form-control" name="travel_tickets"/>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{__("ingaz.the_visa_printed_in_the_worker_passport")}}</label>
                        <input type="file" class="form-control" name="visa_printed_in_the_worker_passport"/>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{__("ingaz.exit_and_re_entry_visa")}}</label>
                        <input type="file" class="form-control" name="exit_and_re_entry_visa"/>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{__("ingaz.customer_national_address")}}</label>
                        <input type="file" class="form-control" name="customer_national_address"/>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label
                            class="form-label">{{__("ingaz.worker_information_written_on_an_external_sheet")}}</label>
                        <input type="file" class="form-control" name="worker_information"/>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{__("ingaz.image_of_bank_transfer")}}</label>
                        <input type="file" class="form-control" name="image_of_bank_transfer"/>
                    </div>

                    <!-- زر الإرسال -->
                    <div class="col-md-12 text-center mt-4">
                        <button type="submit" class="defaultBtn">
                            {{__("ingaz.send")}} <i class="fas fa-paper-plane ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
@section('js')
    <script>
        $(document).ready(function () {
            $('#form-renew-contract').on('submit', function (e) {
                e.preventDefault();
                alert('sd')
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
                        $('#form-daily-lebor-demand')[0].reset();
                    },
                    error: function (xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            let errorMessages = '';
                            for (let key in errors) {
                                errorMessages += `<p style="margin: 0 0 5px; color: red; text-align: center;">${errors[key][0]}</p>`;
                            }
                            Swal.fire({
                                icon: 'error',
                                title: '{{ __("ingaz.error") }}',
                                html: `<div>${errorMessages}</div>`,
                            });
                        } else {
                            Swal.fire('{{ __("ingaz.error") }}', '{{ __("ingaz.error_from_server") }}', 'error');
                        }
                    }

                });
            })
        })
    </script>
@endsection
