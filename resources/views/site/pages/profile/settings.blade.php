@extends('site.layouts.index')
@section('title')
    {{__("ingaz.settings")}}
@endsection
@section('content')
    <section class="profile">
        <div class="container ">
            <!-- user Header  -->
            @include('site.pages.profile.includes.user_header')
            <!-- /user Header  -->
            <div class="row">
                <!-- links -->
                @include('site.pages.profile.includes.sidebar')
                <!-- content -->
                <div class=" col-md-8 col-lg-9 p-2 profileContent">
                    <div class="editProfile">
                        <div class="head">
                            <h5><i class="fas fa-user me-2"></i> {{__("ingaz.personal_data")}}</h5>
                        </div>
                        <form id="form-settings" method="post"
                              action="{{route('site.settings.update',Auth::guard('web')->user()->id)}}"
                              class="row ">
                            @csrf
                            @method('PUT')
                            <div class="col-12 p-2">
                                <label for="name" class="form-label"> <i
                                        class="fas fa-user me-2"></i> {{__("ingaz.full_name")}} </label>
                                <input type="text" class="form-control" id="name" name="name"
                                       value="{{Auth::guard('web')->user()->name}}">
                            </div>
                            <div class="col-md-12 p-2">
                                <label class="form-label"> <i class="fas fa-city me-2"></i> {{__("ingaz.city")}}
                                </label>
                                <select class="select2WithoutSearch" name="city">
                                    <option value=""
                                            @if(!isset(Auth::guard('web')->user()->city)) selected @endif>{{__("ingaz.choose")}}</option>
                                    @foreach($cities as $city)
                                        <option
                                            value="{{$city->id}}" {{isset(Auth::guard('web')->user()->city) && $city->id == Auth::guard('web')->user()->city ? 'selected' : ''}}> {{app()->getLocale() == 'ar' ? $city->name_ar : $city->name_en}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 p-2">
                                <label for="Phone" class="form-label"> <i
                                        class="fas fa-phone-alt me-2"></i>{{__("ingaz.phone")}}
                                </label>
                                <input type="tel" class="form-control" id="phoneNum" name="phone"
                                       value="{{Auth::guard('web')->user()->phone}}" placeholder="05***********">
                            </div>
                            <div class="col-12 pt-4 p-2 text-center">
                                {!! NoCaptcha::renderJs(app()->getLocale()) !!}
                                {!! NoCaptcha::display() !!}

                                @error('g-recaptcha-response')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <button class="defaultBtn mt-3" type="submit">
                                    {{__("ingaz.confirm")}}
                                    <span></span>
                                </button>
                            </div>
                        </form>
                    </div>
                    <!--  -->
                    <div class="editProfile">
                        <div class="head">
                            <h5><i class="fas fa-key me-2"></i> {{__("ingaz.change_password")}}</h5>
                        </div>
                        <form id="form-change-password" method="post"
                              action="{{route('site.change-password.update',Auth::guard('web')->user()->id)}}"
                              class="row ">
                            @csrf
                            @method('PUT')
                            <div class="col-md-6 p-2">
                                <label for="password" class="form-label"> <i
                                        class="fas fa-key me-2"></i> {{__("ingaz.password")}} </label>
                                <input type="password" class="form-control" name="password" id="password"
                                       placeholder="*****">
                            </div>
                            <div class="col-md-6 p-2">
                                <label for="repetPassword" class="form-label"> <i
                                        class="fas fa-key me-2"></i>{{__("ingaz.confirm_password")}}
                                </label>
                                <input type="password" class="form-control" name="password_confirmation"
                                       id="repetPassword" placeholder="*****">
                            </div>
                            <input type="hidden" name="phone" value="{{Auth::guard('web')->user()->phone}}">
                            {!! NoCaptcha::renderJs(app()->getLocale()) !!}
                            {!! NoCaptcha::display() !!}

                            @error('g-recaptcha-response')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="col-12 pt-4 p-2 text-center">
                                <button class="defaultBtn " type="submit">
                                    {{__("ingaz.confirm")}}
                                    <span></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('js')
    <script>
        $(document).ready(function () {
            $('#form-settings').on('submit', function (e) {
                e.preventDefault();
                let formData = new FormData(this); // <<< مهم
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
                        $('#form-settings')[0].reset();
                        setTimeout(function () {
                            location.reload()
                        }, 1000)
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
            $('#form-change-password').on('submit', function (e) {
                e.preventDefault();
                let formData = new FormData(this); // <<< مهم
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
                        $('#form-change-password')[0].reset();
                        setTimeout(function () {
                            location.reload()
                        }, 1000)
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
