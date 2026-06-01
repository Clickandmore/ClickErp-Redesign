<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- Primary Meta Tags -->
     @if(isset($settings)&&isset($settings->website_name))
    <title> {{$settings->website_name}}</title>
    <link rel="icon" href="{{ showFile($settings->favicon) }}" type="image/png" />
    @else
    <title>{{ documentSetting('website_name', config('app.name')) }}</title>
    @endif
    <meta name="title" content="{{ isset($settings) && isset($settings->website_name) ? $settings->website_name : documentSetting('website_name', config('app.name')) }}" />
    <meta name="description" content="" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Favicon -->

     <!-- bootstrap -->
    <link rel="stylesheet" href="{{ asset('cv') }}/css/bootstrap.rtl.min.css" />
    <!-- fancybox -->
    <link rel="stylesheet" href="{{ asset('cv') }}/css/fancybox.css" />
    <!-- aos -->
    <link rel="stylesheet" href="{{ asset('cv') }}/css/aos.css" />
    <!-- swiper -->
    <link rel="stylesheet" href="{{ asset('cv') }}/css/swiper-bundle.min.css" />
    <!-- Custom style  -->
    <link rel="stylesheet" href="{{ asset('cv') }}/css/style.css?v=12" />

    <style>
       .passportNumbers {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .passportNumbers span {
            display: inline-block;
            min-width: 100px; /* تحديد عرض أدنى */
            white-space: normal; /* علشان النص يظهر بشكل طبيعي */
            overflow: hidden;
            text-overflow: ellipsis; /* إظهار نقاط (...) لو النص طويل */
        }
    </style>
</head>

<body>
    <!-- ================  header ================= -->
    <!--(((((((((((((((((((((((()))))))))))))))))))))))-->
    <!--((((((((((((((((((( content )))))))))))))))))))-->
    <!--(((((((((((((((((((((((()))))))))))))))))))))))-->
    <content id="widget">
        <!-- cv page -->
        <div class="cvPage">
            <div class="shape1"></div>
            <!-- col -->
            <div class="imageCol">
                <div class="image">
                    <img @if ($obj->image)
                        src="{{ showFile($obj->image) }}"
                        @else
                        src="{{ asset('cv') }}/img/fav.svg"
                    @endif  alt="" />
                </div>
                <div class="mainInfo">
                    <!-- sub -->
                    <div class="sub">
                        <h5 class="title">{{ helperTrans('ingaz.nationality') }}</h5>
                        <p class="dis">{{ $obj->nationality ? $obj->nationality->title : '' }}</p>
                    </div>
                    <!-- sub -->
                    <div class="sub">
                        <h5 class="title">{{ helperTrans('ingaz.job') }} :</h5>
                        <p class="dis">{{ $obj->jobTitles ? $obj->jobTitles->first()->title : '' }}</p>
                    </div>
                    <!-- sub -->
                    <div class="sub">
                        <h5 class="title">{{ helperTrans('ingaz.religion_id') }} :</h5>
                        <p class="dis">{{ $obj->religion ? $obj->religion->title : '' }}</p>
                    </div>
                    <!-- sub -->
                    <div class="sub">
                        <h5 class="title">{{ helperTrans('ingaz.age') }} :</h5>
                        <p class="dis">{{ Carbon\Carbon::parse($obj->birthdate)->age }}</p>
                    </div>
                    <!-- sub -->
                    <div class="sub">
                        <h5 class="title">{{ helperTrans('ingaz.experience_years') }} :</h5>
                        <p class="dis">{{ $obj->experience_years != null && $obj->experience_years > 0 ? $obj->experience_years : '' }}</p>
                    </div>
                    <!-- sub -->
                    <div class="sub">
                        <h5 class="title">{{ helperTrans('ingaz.monthly_salary') }} :</h5>
                        <p class="dis">{{ $obj->salary }}</p>
                    </div>
                    <!-- languages -->
     <div class="languages">
                        <h5 class="title">{{ helperTrans('ingaz.languages she talk') }} :</h5>
                        <div class="Percentages">
                            <div class="percentDiv">
                                <div class="percent p75">
                                    <p class="percentNum">75 <span> % </span></p>
                                    <div class="slice">
                                        <div class="bar"></div>
                                        <div class="fill"></div>
                                    </div>
                                </div>
                                <h5>{{ helperTrans('ingaz.arabic_language') }}</h5>
                            </div>
                            <div class="percentDiv">
                                <div class="percent p100">
                                    <p class="percentNum">100 <span> % </span></p>
                                    <div class="slice">
                                        <div class="bar"></div>
                                        <div class="fill"></div>
                                    </div>
                                </div>
                                <h5>{{ helperTrans('ingaz.english_language') }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- col -->
            <div class="informationCol">
                <div class="head">
                    <div class="name">
                        <div class="data">
                            <p class="innerTitle">{{ helperTrans('ingaz.worker_name') }} :</p>
                            <h3 class="innerData">{{ $obj->name }}</h3>
                        </div>
                        <div class="data">
                            <p class="innerTitle">{{ helperTrans('ingaz.passport_num') }} :</p>
                            <h6 class="innerData">{{ $obj->passport_num }}</h6>
                        </div>

                    </div>
                    <span class="logo">
                        @if (isset($settings->id) && $settings->logo_header)
                            <img src="{{ showFile($settings->logo_header) }}" alt="logo_header" />

                        @endif

                        <h4 class="experiance">
                            <svg width="24" height="24" viewBox="0 0 511.999 511.999">
                                <g>
                                    <path
                                        d="m452.71 157.937-133.741-12.404L265.843 22.17c-3.72-8.638-15.967-8.638-19.686 0l-53.126 123.362L59.29 157.937c-9.365.868-13.149 12.516-6.084 18.723l100.908 88.646-29.531 131.029c-2.068 9.175 7.841 16.373 15.927 11.572L256 339.331l115.49 68.576c8.087 4.802 17.994-2.397 15.927-11.572l-29.532-131.029 100.909-88.646c7.065-6.207 3.28-17.855-6.084-18.723z"
                                        fill="#FFFFFF" data-original="#ffdc64"></path>
                                    <path
                                        d="M119.278 17.923c6.818 9.47 26.062 50.14 37.064 73.842 1.73 3.726-2.945 7.092-5.93 4.269-18.987-17.952-51.452-49.104-58.27-58.575-5.395-7.493-3.694-17.941 3.8-23.336 7.493-5.395 17.941-3.693 23.336 3.8zM392.722 17.923c-6.818 9.47-26.062 50.14-37.064 73.842-1.73 3.726 2.945 7.092 5.93 4.269 18.987-17.952 51.451-49.105 58.27-58.575 5.395-7.493 3.694-17.941-3.8-23.336-7.493-5.395-17.941-3.693-23.336 3.8zM500.461 295.629c-11.094-3.618-55.689-9.595-81.612-12.875-4.075-.516-5.861 4.961-2.266 6.947 22.873 12.635 62.416 34.099 73.51 37.717 8.778 2.863 18.215-1.932 21.078-10.711 2.863-8.779-1.932-18.215-10.71-21.078zM11.539 295.629c11.094-3.618 55.689-9.595 81.612-12.875 4.075-.516 5.861 4.961 2.266 6.947-22.873 12.635-62.416 34.099-73.51 37.717-8.778 2.863-18.215-1.932-21.078-10.711s1.932-18.215 10.71-21.078zM239.794 484.31c0-11.669 8.145-55.919 13.065-81.582.773-4.034 6.534-4.034 7.307 0 4.92 25.663 13.065 69.913 13.065 81.582 0 9.233-7.485 16.718-16.718 16.718-9.234.001-16.719-7.485-16.719-16.718z"
                                        fill="#FFFFFF" data-original="#fff082"></path>
                                    <path
                                        d="m285.161 67.03-19.319-44.86c-3.72-8.638-15.967-8.638-19.686 0L193.03 145.532 59.29 157.937c-9.365.868-13.149 12.516-6.084 18.723l100.908 88.646-29.531 131.029c-2.068 9.175 7.841 16.373 15.927 11.572l15.371-9.127c25.199-163.12 96.041-282.862 129.28-331.75z"
                                        fill="#E7E7E7" data-original="#ffc850"></path>
                                </g>
                            </svg>
                            {{ helperTrans('ingaz.experience_makes_difference') }}
                        </h4>
                    </span>
                </div>
                <div class="category">
                    <h5 class="title"><span>{{ helperTrans('ingaz.personal_data') }}</span></h5>
                    <div class="infoRow">
                        <!-- data -->
                        <div class="data">
                            <p class="innerTitle">{{ helperTrans('ingaz.marital_status') }}</p>
                            <h6 class="innerData">{{ @$obj->status }}</h6>
                        </div>
                        <!-- data -->
                        <div class="data">
                            <p class="innerTitle">{{ helperTrans('ingaz.childrens_count') }}</p>
                            <h6 class="innerData">{{ @$obj->childrens_count }}</h6>
                        </div>
                        <!-- data -->
                        <div class="data">
                            <p class="innerTitle">{{ helperTrans('ingaz.height') }}</p>
                            <h6 class="innerData">{{ $obj->height }}</h6>
                        </div>
                        <!-- data -->
                        <div class="data">
                            <p class="innerTitle">{{ helperTrans('ingaz.weight') }}</p>
                            <h6 class="innerData">{{ $obj->weight }}</h6>
                        </div>
                        <!-- data -->
                        <div class="data">
                            <p class="innerTitle">{{ helperTrans('ingaz.education_level') }}</p>
                            <h6 class="innerData">{{ @$obj->education }}</h6>
                        </div>
                    </div>
                </div>
                <div class="category">
                    <h5 class="title"><span>{{ helperTrans('ingaz.skills') }}</span></h5>
                    <div class="passportData">
                        <table style="text-align: center">
                            <tr>
                                @foreach ($skills as $skill)
                                    <td>{{ @$skill->title }}</td>
                                @endforeach
                            </tr>

                        </table>
                    </div>
                </div>

                <div class="category">
                    <h5 class="title"><span>{{ helperTrans('ingaz.contact_us_section') }}</span></h5>
                    <div class="passportData">
                        <div class="info_social">
                            @if (isset($settings->id) && isset($settings->whatsapp_qrcode) && isset($settings->net_qrcode))
                                <img src="{{ showFile($settings->whatsapp_qrcode) }}" alt="WhatsApp QR Code" />
                                {{-- <img src="{{ showFile($settings->net_qrcode) }}" alt="Net QR Code" /> --}}
                            @else
                                <img src="{{ asset('cv/img/qr1.png') }}" alt="Default QR Code 1" />
                                {{-- <img src="{{ asset('cv/img/qr2.png') }}" alt="Default QR Code 2" /> --}}
                            @endif

                            <!-- <div class="experienceYears"><span> 20 </span> سنه خبرة</div> -->
                            @if (isset($settings->id) && isset($settings->experience_image))
                                <img style="margin-right: auto;"  src="{{ showFile($settings->experience_image) }}" alt="experience_image" />
                            @else
                                <img style="margin-right: auto;" src="{{ asset('cv') }}/img/experience.svg"
                                alt="" />
                            @endif


                        </div>
                        <div class="socialMedia">

                            <a href="#!" target="_blank">

                                <!-- call -->
                                <svg width="24" height="24" viewBox="0 0 512 512">
                                    <g>
                                        <circle cx="256" cy="256" r="256" style="" fill="#2196F3"
                                            data-original="#2196f3"></circle>
                                        <path
                                            d="M384 308.928c-27.616 0-53.952-6.016-78.24-17.888a16.267 16.267 0 0 0-12.256-.736 16.138 16.138 0 0 0-9.184 8.16l-11.52 23.84c-34.56-19.84-63.232-48.544-83.104-83.104l23.872-11.52c3.84-1.856 6.752-5.152 8.16-9.184 1.376-4.032 1.12-8.448-.736-12.256-11.904-24.256-17.92-50.592-17.92-78.24 0-8.832-7.168-16-16-16H128c-8.832 0-16 7.168-16 16 0 149.984 122.016 272 272 272 8.832 0 16-7.168 16-16v-59.072c0-8.832-7.168-16-16-16z"
                                            style="" fill="#FAFAFA" data-original="#fafafa"></path>
                                    </g>
                                </svg>
                                                                {{@$settings->phone}}

                             </a>
                            <a href="#!" target="_blank">
                                <!-- whatsapp -->
                                <svg width="24" height="24" viewBox="0 0 176 176">
                                    <g>
                                        <g data-name="Layer 2">
                                            <g data-name="09.whatsapp">
                                                <circle cx="88" cy="88" r="88" fill="#29A71A"
                                                    opacity="1" data-original="#29a71a"></circle>
                                                <g fill="#FFFFFF">
                                                    <path
                                                        d="M126.8 49.2a54.57 54.57 0 0 0-87.42 63.13l-5.79 28.11a2.08 2.08 0 0 0 .33 1.63 2.11 2.11 0 0 0 2.24.87l27.55-6.53A54.56 54.56 0 0 0 126.8 49.2zm-8.59 68.56a42.74 42.74 0 0 1-49.22 8l-3.84-1.9-16.89 4 .05-.21 3.5-17-1.88-3.71a42.72 42.72 0 0 1 7.86-49.59 42.73 42.73 0 0 1 60.42 0 2.28 2.28 0 0 0 .22.22 42.72 42.72 0 0 1-.22 60.19z"
                                                        fill="#FFFFFF" opacity="1" data-original="#ffffff"></path>
                                                    <path
                                                        d="M116.71 105.29c-2.07 3.26-5.34 7.25-9.45 8.24-7.2 1.74-18.25.06-32-12.76l-.17-.15C63 89.41 59.86 80.08 60.62 72.68c.42-4.2 3.92-8 6.87-10.48a3.93 3.93 0 0 1 6.15 1.41l4.45 10a3.91 3.91 0 0 1-.49 4l-2.25 2.92a3.87 3.87 0 0 0-.35 4.32c1.26 2.21 4.28 5.46 7.63 8.47 3.76 3.4 7.93 6.51 10.57 7.57a3.82 3.82 0 0 0 4.19-.88l2.61-2.63a4 4 0 0 1 3.9-1l10.57 3a4 4 0 0 1 2.24 5.91z"
                                                        fill="#FFFFFF" opacity="1" data-original="#ffffff"></path>
                                                </g>
                                            </g>
                                        </g>
                                    </g>
                                </svg>
                                                                {{@$settings->whatsapp}}

                             </a>
                            <a href="#!" target="_blank">
                                <!-- location -->
                                <svg width="24" height="24" viewBox="0 0 512 512">
                                    <g>
                                        <g data-name="Layer 2">
                                            <circle cx="256" cy="256" r="256" fill="#FF2147"
                                                opacity="1" data-original="#ff2147"></circle>
                                            <path fill="#FFFFFF"
                                                d="M256 116a111.31 111.31 0 0 0-111.31 111.31c0 40.08 47.3 109.64 80.23 153.23a39 39 0 0 0 62.16 0C320 337 367.31 267.39 367.31 227.31A111.31 111.31 0 0 0 256 116zm0 142.78a39.57 39.57 0 1 1 39.57-39.57A39.58 39.58 0 0 1 256 258.78z"
                                                opacity="1" data-original="#ffffff"></path>
                                        </g>
                                    </g>
                                </svg>
                                {{@$settings->location}}
                             </a>
                            <a href="#!" target="_blank">
                                <!-- snapchat -->
                                <svg width="24" height="24" viewBox="0 0 512 512">
                                    <g>
                                        <circle cx="256" cy="256" r="256" fill="#FFC91B" opacity="1"
                                            data-original="#ffc91b"></circle>
                                        <path fill="#FFFFFF"
                                            d="M416 332.102a22.225 22.225 0 0 1-9.251 17.833 39.71 39.71 0 0 1-11.287 5.818 141.913 141.913 0 0 1-19.433 4.625 45.365 45.365 0 0 1-1.396 6.895 24.177 24.177 0 0 1-13.818 16.233 31.052 31.052 0 0 1-12.073 2.065c-2.909 0-6.138-.233-9.484-.465-3.345-.233-7.302-.553-10.996-.553a32.008 32.008 0 0 0-12.916 2.065c-4.131 1.92-8.553 4.509-13.207 7.215-12.945 7.622-27.607 16.349-46.138 16.349s-33.193-8.727-46.138-16.262c-4.655-2.705-9.076-5.295-13.207-7.215a31.994 31.994 0 0 0-12.916-2.065c-3.695 0-7.389.262-10.996.553s-6.516.465-9.484.465a31.095 31.095 0 0 1-12.073-2.065 24.117 24.117 0 0 1-13.818-16.233 45.365 45.365 0 0 1-1.396-6.895 140.969 140.969 0 0 1-19.433-4.625 39.71 39.71 0 0 1-11.287-5.818 22.226 22.226 0 0 1-9.251-17.92 19.81 19.81 0 0 1 11.869-17.92 65.376 65.376 0 0 1 8.727-3.258c12.393-3.985 30.691-9.833 40.495-40.378l-14.865-5.818c-12.632-4.703-19.867-17.995-16.96-31.156a24.38 24.38 0 0 1 8.727-13.964 24.814 24.814 0 0 1 15.127-5.207 24.55 24.55 0 0 1 5.644.669l5.004 1.164c.233-5.818.349-11.956.349-18.24a94.873 94.873 0 0 1 7.651-37.149 97.221 97.221 0 0 1 50.967-51.229 93.098 93.098 0 0 1 37.265-7.651 93.88 93.88 0 0 1 37.004 7.651 97.748 97.748 0 0 1 51.055 51.055 94.517 94.517 0 0 1 7.68 37.149c0 6.284 0 12.393.349 18.24l5.004-1.164a24.466 24.466 0 0 1 20.771 4.538 24.38 24.38 0 0 1 8.873 13.964c3.005 13.219-4.249 26.619-16.96 31.331l-14.865 5.818c9.92 30.487 28.218 36.305 40.553 40.233a67 67 0 0 1 8.727 3.258A19.78 19.78 0 0 1 416 332.102z"
                                            opacity="1" data-original="#ffffff"></path>
                                    </g>
                                </svg>
                                                                {{@$settings->snapchat}}

                             </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </content>

    {{-- <div class="col-md-4">
        <label for="cv" class="form-label">{{ __("almaged.cv") }}</label>
        <img src="" id="cv-preview" alt="" style="width: 200px;height: 200px">
        <input type="hidden" name="cv" id="cv" value="">
    </div> --}}

    <!--(((((((((((((((((((((((()))))))))))))))))))))))-->
    <!--((((((((((((((((( / content )))))))))))))))))))-->
    <!--(((((((((((((((((((((((()))))))))))))))))))))))-->
    <!-- ================ Footer ================= -->
    <!-- ================ /Footer ================= -->
    <!--////////////////////////////////////////////////////////////////////////////////-->
    <!--////////////////////////////////////////////////////////////////////////////////-->
    <!--////////////////////////////////////////////////////////////////////////////////-->
    <!--/////////////////////////////JavaScript/////////////////////////////////////////-->
    <!--////////////////////////////////////////////////////////////////////////////////-->
    <!--////////////////////////////////////////////////////////////////////////////////-->
    <!--////////////////////////////////////////////////////////////////////////////////-->
    <!-- jquery -->
    <script src="{{ asset('cv') }}/js/jquery.min.js"></script>
    <!-- popper -->
    <script src="{{ asset('cv') }}/js/popper.min.js"></script>
    <!-- bootstrap -->
    <script src="{{ asset('cv') }}/js/bootstrap.min.js"></script>
    <!-- aos -->
    <script src="{{ asset('cv') }}/js/aos.js"></script>
    <!-- fancybox -->
    <script src="{{ asset('cv') }}/js/fancybox.js"></script>
    <!-- counter up -->
    <script src="{{ asset('cv') }}/js/counterup2.min.js"></script>
    <!-- swiper -->
    <script src="{{ asset('cv') }}/js/swiper-bundle.min.js"></script>
    <!-- custom -->
    <script src="{{ asset('cv') }}/js/custom.js?v=11"></script>

<<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>

<script>
$(document).ready(function () {
    (async function () {
        var targetDiv = document.getElementById('widget');

        function waitForImages() {
            return new Promise(resolve => {
                const images = document.querySelectorAll('img');
                let loaded = 0;
                if (images.length === 0) resolve();
                images.forEach(img => {
                    if (img.complete) {
                        loaded++;
                        if (loaded === images.length) resolve();
                    } else {
                        img.onload = img.onerror = () => {
                            loaded++;
                            if (loaded === images.length) resolve();
                        };
                    }
                });
            });
        }

        await waitForImages();

        // استخدام html2canvas
        html2canvas(targetDiv, {useCORS: true, logging: true}).then(canvas => {
            canvas.toBlob(function(blob){
                var formData = new FormData();
                formData.append('cv', blob, 'myImage.png');

                $.ajax({
                    url: '{{ route("cv-convert.store", ["cvId" => $obj->id]) }}',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        setTimeout(() => {
                            window.location.href = "{{ route('maids.index') }}";
                        }, 2000);
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                    }
                });
            });
        }).catch(err => console.error('html2canvas error:', err));
    })();
});
</script>


</body>

</html>
