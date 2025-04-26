@extends('web.layout.app')

@section('content')
    <script src="https://www.google.com/recaptcha/api.js?hl=ar" async defer></script>

    <!-- content -->
    <main>
        <!-- *** hero section -->
        <section class="hero" style="z-index: 2">
            <div class="video_wrapper">
                <video src="{{ asset('web') }}/assets/images/recruitment.mp4" autoplay muted loop playsinline></video>
            </div>
            <div class="container">
                <div class="text">
                    <h1>{{ helperTrans('web.welcome') }}</h1>
                    <p>
                        {{ helperTrans('web.unique_experience') }}
                    </p>
                </div>

                <div class="find_worker">
                    <form id="searchForm" action="{{ route('web.maids.index') }}">
                        <div class="field">
                            <label for="job_type_id"><i class="fa-light fa-hourglass-start"></i> نوع الخدمة</label>
                            <select id="job_type_id" name="job_type_id[]" class="selectpicker" data-style="btn-default"
                                required>
                                @foreach ($job_types as $job_type)
                                    <option value="{{ $job_type->id }}">{{ $job_type->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field">
                            <label for="job"><i class="fa-light fa-briefcase"></i> الوظيفة</label>
                            <select id="job" name="job_title_id[]" class="selectpicker" data-style="btn-default">
                                <option value="">{{ helperTrans('web.all_jobs') }}</option>
                                @foreach ($job_titles as $job_title)
                                    <option value="{{ $job_title->id }}">{{ $job_title->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field">
                            <label for="nationality"><i class="fa-sharp fa-regular fa-flag"></i> الجنسية</label>
                            <select id="nationality" name="nationality_id" class="selectpicker" data-style="btn-default">
                                <option value="">{{ helperTrans('web.all_nationalities') }}</option>
                                @foreach ($nationalities as $nationality)
                                    <option value="{{ $nationality->id }}">{{ $nationality->sub_title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button class="recruit" type="submit">{{ helperTrans('web.choose_manpower') }}</button>
                    </form>
                </div>
                @push('js')
                    <script>
                        document.getElementById('searchForm').addEventListener('submit', function (event) {
                            // Get all form elements
                            const form = event.target;
                            const inputs = form.querySelectorAll('select, input');

                            // Loop through inputs and remove those without value
                            inputs.forEach(input => {
                                if (!input.value) {
                                    input.name = ''; // Remove name attribute so it won't be submitted
                                }
                            });
                        });
                    </script>
                @endpush

            </div>
        </section>

        <!-- *** about section -->
        <section class="about">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6 col-12 mb-lg-0 mb-4">
                        <div class="about_text">
                            @foreach ($about_us as $item)
                                <h2>
                                    <span><i class="{{ $icons[$loop->index] }}"></i></span>
                                    {{ $item->title }}
                                </h2>
                                <p>
                                    {!! $item->description !!}
                                </p>
                            @endforeach


                        </div>
                    </div>
                    <div class="col-lg-6 col-12 position-relative">
                        <div class="img">
                            <img src="{{ @$settings->about_image ? showFile($settings->about_image) : asset('web') . '/assets/images/s1.jpeg' }}"
                                alt="">
                            <div class="bun">
                                <h6>
                                    {{ @$settings->about_us_sub_title }}
                                </h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- *** services *** -->
        <section class="popular_service">
            <h2> {{ helperTrans('web.top_our_services') }}</h2>
            <div class="container">
                <div class="row">
                    @foreach ($services as $service)
                        <div class="col-md-{{ $loop->iteration <= 3 ? 4 : 6 }} col-6 p-2 text-center">
                            <div class="service">
                                <img src="{{ showFile($service->image) }}" alt="">
                                <h6>{{ $service->title }}</h6>
                                <p>{{ $service->description }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        <!-- *** recruitment countries -->
        <section class="countries">
            <div class="title">
                <h2>{{ helperTrans('web.recruitment_countries') }}</h2>
                <p>{{ helperTrans('web.choose_recruitment_country') }}</p>
            </div>
            <div class="container">
                <div class="row justify-content-center">
                    @foreach ($nationalities as $country)
                        <div class="col-lg-3 col-md-4 col-6 p-2">
                            <div class="country">
                                <div class="img">
                                    <img src="{{ showFile($country->logo) }}" alt="">
                                </div>
                                <h3>{{ $country->title }}</h3>
                                <a href="{{ route('web.maids.index', ['nationality_id' => $country->id]) }}">
                                    {{ helperTrans('web.request_manpower_now') }}
                                    <i class="fa-regular fa-arrow-left-long"></i>
                                </a>
                            </div>
                        </div>
                    @endforeach


                </div>
            </div>
        </section>
        <!-- *** statistics *** -->
        <section class="statistics">
            <h2>{{ helperTrans('web.proud_statistics') }}</h2>
            <div class="container">
                <div class="row">
                    @foreach ($statistics as $statistic)
                                <div class="col-lg-4 col-md-4 col-6 p-2">
                                    <div class="statics">
                                        <div class="icon">
                                            <i class="{{ $statistic->image }}"></i>
                                        </div>
                                        <h3><span data-goal="{{ $statistic->counter_num }}" class="num">0</span>{{
                        $statistic->counter_unit }}+</h3>
                                        <h4>{{ $statistic->title }}</h4>
                                    </div>
                                </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Recruitment Services --}}
        <section class="recruitment_services">
            <div class="container">
                <div class="row">
                    <div class="title">
                        <h2>{{ helperTrans('web.recruitment_services') }}</h2>
                        <p>{{ helperTrans('web.recruitment_services_desc') }}</p>
                    </div>
                    <!-- Slider main container -->
                    <div class="swiper swiper-services  mv-3">
                        <!-- Additional required wrapper -->
                        <div class="swiper-wrapper">
                            <!-- Slides -->
                            <div class="swiper-slide"
                                style="background-image: url('{{ asset('web/assets/images/steps/services.png') }}')">
                                <h3>@lang('web.recruitment_services_1')</h3>
                                <p>@lang('web.recruitment_services_1_desc')</p>
                            </div>
                            <div class="swiper-slide"
                                style="background-image: url('{{ asset('web/assets/images/steps/choose.png') }}')">
                                <h3>@lang('web.recruitment_services_2')</h3>
                                <p>@lang('web.recruitment_services_2_desc')</p>
                            </div>
                            <div class="swiper-slide"
                                style="background-image: url('{{ asset('web/assets/images/steps/contract.png') }}')">
                                <h3>@lang('web.recruitment_services_3')</h3>
                                <p>@lang('web.recruitment_services_3_desc')</p>
                            </div>
                            <div class="swiper-slide"
                                style="background-image: url('{{ asset('web/assets/images/steps/terms.png') }}')">
                                <h3>@lang('web.recruitment_services_4')</h3>
                                <p>@lang('web.recruitment_services_4_desc')</p>
                            </div>
                            <div class="swiper-slide"
                                style="background-image: url('{{ asset('web/assets/images/steps/arrive.png') }}')">
                                <h3>@lang('web.recruitment_services_5')</h3>
                                <p>@lang('web.recruitment_services_5_desc')</p>
                            </div>
                        </div>
                        <!-- If we need pagination -->
                        {{-- <div class="swiper-pagination"></div> --}}
                        <!-- If we need navigation buttons -->
                        {{-- <div class="swiper-button-prev"></div>
                        <div class="swiper-button-next"></div> --}}
                        <!-- If we need scrollbar -->
                        {{-- <div class="swiper-scrollbar"></div> --}}
                    </div>
                </div>
            </div>
        </section>

        <!-- *** how we work section *** -->
        {{-- <section class="howitworks">
            <div class="title">
                <h2>{{ helperTrans('web.recruitment_steps') }}</h2>
                <p>{{ helperTrans('web.learn_recruitment_steps') }}</p>
            </div>
            <div class="container">
                <div class="allSteps">
                    @foreach ($steps as $step)
                    <div class="step">
                        <div class="icon">
                            <img src="{{ showFile($step->icon) }}" alt="">
                        </div>
                        <div class="text">
                            <h6>
                                {{ $step->title }}
                            </h6>
                        </div>
                        <div class="step_num">
                            <span>
                                {{ $loop->iteration }}
                            </span>
                        </div>
                    </div>
                    @endforeach


                </div>
            </div>
        </section> --}}
    </main>
@if($show_popup == 1)
    <!-- Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: transparent; border: none;">
                <!-- Close Button -->
                <button type="button" class="close position-absolute custom-close-btn"
                    style="right: 10px; top: 10px; z-index: 1;" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <!-- Image with Link -->
                <a href="{{ route('web.contact-us.index') }}" target="_blank">
                    <img src="{{ $popup_image ? showFile($popup_image) : asset('web/assets/images/ussus-popup.png') }}" class="img-fluid" alt="Popup Image">
                </a>
            </div>
        </div>
    </div>

    @push('js')
    <script>
        $(document).ready(function() {
            $('#imageModal').modal('show');
        });
    </script>
    @endpush
@endif
    <!-- footer -->
@endsection

@push('css')
    <style>
        #imageModal .custom-close-btn {
            width: 30px;
            height: 30px;
            background-color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            z-index: 999;

        }

        .custom-close-btn span {
            color: black;
            font-size: 20px;
            line-height: 1;
            margin: -4px 0 0;
        }

        .custom-close-btn:hover {
            background-color: #f8f9fa;
        }
    </style>
@endpush

@section('js')
    <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit&hl=ar" async defer></script>

    <script>
        function toggleActiveClass(element) {
            element.classList.toggle('active');
        }

        $(document).ready(function () {
            $('#form_contact').submit(function (e) {
                e.preventDefault();
                var form = document.getElementById('form_contact');
                var isValidForm = form.checkValidity();
                var capcha = document.getElementById("g-recaptcha-response").value;

                if (!isValidForm || capcha === "") {
                    if (!isValidForm) {
                        $("#alert_div").html('<div class="alert alert-danger">\n' +
                            '  <strong>خطأ!</strong>  تأكد من ادخال جميع البيانات  \n' +
                            '</div>');
                    } else {

                        $("#alert_div").html('<div class="alert alert-danger">\n' +
                            '  <strong>خطأ!</strong>  تأكد من اختيار انك ليس انسان الي  \n' +
                            '</div>');
                    }

                } else {
                    // alert('ddd');
                    const formData = new FormData(this);
                    const url = $(this).attr('action');
                    const submitButton = $(document.activeElement);
                    const buttonText = submitButton.text();
                    $.ajax({
                        url: url,
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function (response) {
                            $("#form_contact")[0].reset();
                            // console.log(response);
                            $("#alert_div").html('');
                            Swal.fire('تم ارسال البيانات بنجاج', '', 'success');
                        },
                        error: function (xhr, status, error) {
                            let errors = xhr.responseJSON.errors;
                            let errorMessage = '';
                            $.each(errors, function (key, value) {
                                errorMessage += value[0] + '<br>';
                            });
                            Swal.fire({
                                icon: 'error',
                                title: 'Validation Error',
                                html: errorMessage,
                            });
                        }
                    });
                }
            });
            $('#age').change(function (e) {
                var selectedOption = $(this).find(':selected');

                // Retrieve data attributes from the selected option
                var ageFrom = selectedOption.data('age-from');
                var ageTo = selectedOption.data('age-to');

                // Set the values of hidden inputs
                $('#age_from').val(ageFrom);
                $('#age_to').val(ageTo);

            });
        });
    </script>

    <script>
        $(document).ready(function () {
            $('#imageModal').modal('show');
            $('.custom-close-btn').click(function () {
                $('#imageModal').modal('hide');
            })
        });
    </script>
@endsection

@include('web.ingaz.plugins.swiper')
