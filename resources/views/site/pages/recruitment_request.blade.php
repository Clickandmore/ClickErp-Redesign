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
                <a href="{{ route('site.home.index') }}"> {{ __('ingaz.home') }} </a>
            </li>
            <li>
                <a href="{{ route('site.all-workers.index') }}?type=estqdam"> {{ __('ingaz.recruitment_request') }} </a>
            </li>
            <li>
                <a href="#!" class="active"> {{ __('ingaz.special_request') }} </a>
            </li>
        </ul>
        <figure>
            <svg xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" x="0px" y="0px" viewBox="0 0 300 100"
                 style="margin-bottom:-35px;enable-background:new 0 0 300 100" class="injected-svg js-svg-injector"
                 data-parent="#SVGwave1BottomSMShape">
                <g>
                    <path class="wave-bottom-1-sm-0" fill="#fdfdfd" opacity=".8"
                          d="M10.9,63.9c0,0,42.9-34.5,87.5-14.2c77.3,35.1,113.3-2,146.6-4.7C293.7,41,315,61.2,315,61.2v54.4H10.9V63.9z">
                    </path>
                    <path class="wave-bottom-1-sm-0" fill="#fdfdfd" opacity=".6"
                          d="M-55.7,64.6c0,0,42.9-34.5,87.5-14.2c77.3,35.1,113.3-2,146.6-4.7c48.7-4.1,69.9,16.2,69.9,16.2v54.4H-55.7     V64.6z">
                    </path>
                    <path class="wave-bottom-1-sm-0 " fill="#fdfdfd" opacity=".4" fill-opacity="0"
                          d="M23.4,118.3c0,0,48.3-68.9,109.1-68.9c65.9,0,98,67.9,98,67.9v3.7H22.4L23.4,118.3z"></path>
                    <path class="wave-bottom-1-sm-0" fill="#fdfdfd"
                          d="M-54.7,83c0,0,56-45.7,120.3-27.8c81.8,22.7,111.4,6.2,146.6-4.7c53.1-16.4,104,36.9,104,36.9l1.3,36.7l-372-3     L-54.7,83z">
                    </path>
                </g>
            </svg>
        </figure>
    </section>
    <!-- Main Banner  -->
    <!-- ================ recruitment Request ================= -->
    <section class="recruitmentRequest">
        <div class="container">
            <div class="headTitle">
                <h2> {{ __('ingaz.Request to bring in domestic workers') }} </h2>
                <p>
                    {{ __('ingaz.We are pleased to welcome you to explore our services. We strive to provide the best domestic worker recruitment services. To submit a domestic worker recruitment application, please complete all information correctly.') }}
                </p>
            </div>
            <!-- recruitment Request Form -->
            <form action="{{ route('site.recruitment-request.store') }}" class="recruitmentRequestForm"
                  id="recruitmentRequestForm">
                @csrf
                <div class="row justify-content-center">
                    <div class="col-md-4 pb-3 p-2">
                        <!-- formCard -->
                        <div class="formCard">
                            <div class="head">
                                <h5> {{ __('ingaz.Employer data') }} </h5>
                            </div>
                            <div class="row">
                                <div class="col-12 pb-3 p-2">
                                    <label for="name" class="form-label"> <i class="fas fa-user me-2"></i>
                                        {{ __('ingaz.Full name') }}
                                    </label>
                                    <input type="text" class="form-control" id="name"
                                           value="{{ auth()->user()->name }}" disabled
                                           placeholder=" {{ __('ingaz.Full name') }} ">
                                </div>
                                <div class="col-12 pb-3 p-2">
                                    <label for="Phone" class="form-label"> <i class="fas fa-phone-alt me-2"></i>
                                        {{ __('ingaz.phone') }} </label>
                                    <input type="tel" class="form-control" id="phoneNum"
                                           value="{{ auth()->user()->phone }}" disabled placeholder="05***********">
                                </div>
                                {{-- <div class="col-12 pb-3 p-2">
                                        <label for="city" class="form-label"> <i class="fa-solid fa-city me-2"></i>
                                            {{ __('ingaz.city') }}
                                </label>
                                <select name="" id="city" class="select2">
                                    <option value="">{{ __('ingaz.choose') }}</option>
                                    @foreach ($countries as $country)
                                    <option value="{{ $country->id }}"
                                        {{ isset($userCountry) && $userCountry->id == $country->id ? 'selected' : '' }}>
                                        {{ app()->getLocale() == 'ar' ? $country->name_ar : $country->name_en }}
                                    </option>
                                    @endforeach
                                </select>
                            </div> --}}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8 pb-3 p-2">
                        <!-- formCard -->
                        <div class="formCard">
                            <div class="head">
                                <h5> {{ __('ingaz.Domestic worker data') }} </h5>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 pb-3 p-2">
                                    <label for="" class="form-label"> {{ __('ingaz.Visa number') }} </label>
                                    <input type="text" class="form-control" id="" name="visa_number"
                                           placeholder=" {{ __('ingaz.Enter the visa number') }}  ">
                                    <span
                                        class="hint">{{ __('ingaz.If the visa has not been issued yet, you can find out by:') }}
                                <a href="{{route('site.visa.index')}}">{{ __('ingaz.Visa issuance') }} </a> </span>
                                </div>
                                <div class="col-sm-6 pb-3 p-2">
                                    <label for="" class="form-label"> {{ __('ingaz.nationality is required') }}
                                    </label>
                                    <select name="nationality" id="nationality" class="select2">
                                        <option value="" selected disabled> {{ __('ingaz.choose') }}</option>
                                        @foreach ($nationalities as $nationality)
                                            <option value="{{ $nationality->title }}">{{ $nationality->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-6 pb-3 p-2">
                                    <label for="" class="form-label"> {{ __('ingaz.occupation is required') }}
                                    </label>
                                    <select name="job_title" id="job_title" class="select2">
                                        <option value="" selected disabled> {{ __('ingaz.choose') }}</option>
                                        @foreach ($jobTitles as $jobTitle)
                                            <option value="{{ $jobTitle->title }}">{{ $jobTitle->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-4 pb-3 p-2">
                                    <label for="" class="form-label"> {{ __('ingaz.social_status is required') }}
                                    </label>
                                    <select name="social_status" id="social_status" class="select2">
                                        <option value="" selected disabled> {{ __('ingaz.choose') }}</option>
                                        @foreach (App\Enums\Office\MaritalStatusEnum::cases() as $maritalStatus)
                                            <option value="{{ $maritalStatus->value }}"> {{ $maritalStatus->lang() }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-4 pb-3 p-2">
                                    <label for="" class="form-label"> {{ __('ingaz.age is required') }} </label>
                                    <select name="age" id="age" class="select2">
                                        @for ($i = 18; $i <= 60; $i++)
                                            <option value="{{ $i }}">{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-sm-4 pb-3 p-2">
                                    <label for="" class="form-label"> {{ __('ingaz.worker_religion') }} </label>
                                    <select name="religion_id" id="religion_id" class="select2">
                                        <option value="" selected disabled> {{ __('ingaz.choose') }}</option>
                                        @foreach ($religions as $religion)
                                            <option value="{{ $religion->id }}"> {{ $religion->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-6 pb-3 p-2">
                                    <label for="" class="form-label"> {{ __('ingaz.worker_language') }} </label>
                                    <div class="pt-1">
                                        @foreach (App\Enums\Office\LanguagesTypesEnum::statusLang() as $language)
                                            <div class="form-check px-4">
                                                <input class="form-check-input" type="radio" name="language"
                                                       id="language{{ $language->value }}"
                                                       @if ($loop->first) checked @endif value="{{ $language->value }}">
                                                <label class="form-check-label" for="language{{ $language->value }}">
                                                    {{ $language->lang() }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-sm-6 pb-3 p-2">
                                    <label for="" class="form-label"> {{ __('ingaz.worker_status') }} </label>
                                    <div class="pt-1">
                                        <div class="form-check px-4">
                                            <input class="form-check-input" type="radio" name="worker_status"
                                                   id="worker_status1" value="new_come" checked>
                                            <label class="form-check-label" for="worker_status1">
                                                {{ __('ingaz.new_come') }}
                                            </label>
                                        </div>
                                        <div class="form-check px-4">
                                            <input class="form-check-input" type="radio" name="worker_status"
                                                   id="worker_status2" value="have_experince">
                                            <label class="form-check-label" for="worker_status2">
                                                {{ __('ingaz.have_experince') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12 pb-3 p-2">
                                    <label for="" class="form-label"> {{ __('ingaz.Other requirements') }}
                                    </label>
                                    <textarea name="notes" id="notes" class="form-control" rows="5"
                                              placeholder="{{ __('ingaz.Do you have special requirements for the worker?') }}"></textarea>
                                </div>
                            </div>
                            <!-- submit -->
                            <div class="col-12 pt-4 p-2 text-center">
                                {!! NoCaptcha::renderJs() !!}
                                {!! NoCaptcha::display() !!}

                                @error('g-recaptcha-response')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <button class="defaultBtn " type="submit">
                                    {{ __('ingaz.send') }}
                                    <span></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
    <!-- themeColor -->
    <div class="themeColor">
        <input type="color" id="colorInput" class="color-input">
    </div>
@endsection
@section('js')
    <script>
        $(document).ready(function () {
            $('#recruitmentRequestForm').on('submit', function (e) {
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
                        $('#recruitmentRequestForm')[0].reset();
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
                        }
                        else {
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
    </script>
@endsection
