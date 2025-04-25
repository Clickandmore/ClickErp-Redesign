<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="" />
    <!-- favicon -->
{{--    <link rel="icon" type="image/x-icon" href="{{asset('web')}}/images/logo/fav.svg" />--}}
    <!-- title -->
    <title> CV </title>
    <link rel="stylesheet" href="{{asset('web')}}/assets/css/cv3.css">

</head>

<body>

<div class="container" id="widget">

    <div class="topInfo">

        <div class="image">
            <img src="{{showFile($data->cv_file)}}"
                 alt="">
        </div>
        <div class="name">
{{--            <h1> فاطمة أحمد أحمد </h1>--}}

{{--            <h3> {{$data->salary}} </h3>--}}
        </div>
    </div>


    <!-- col -->
    <div class="imageCol">
        <!-- sub -->
        <div class="sub">
            <h5 class="title"> الراتب الشهري: </h5>
            <p class="dis"> {{$data->salary}} ريال </p>
        </div>
        <div class="sub">
            <h5 class="title"> {{helperTrans('web.recruitment_price')}}: </h5>
            <p class="dis"> {{$data->price}} ريال </p>
        </div>
        <!-- sub -->
        <div class="sub">
            <h5 class="title"> الخبرة : </h5>
            <p class="dis">{{$data->experience_years}} </p>
        </div>

        <div class="sub">
            <h5 class="title"> {{helperTrans('ingaz.recruitment office')}} : </h5>
            <p class="dis">{{$data->office ? $data->office->title : ''}} </p>
        </div>
        <!-- languages -->
{{--        <div class="languages">--}}
            <!-- <h5 class="title"> اللغات : </h5> -->

{{--            <div class="Percentages">--}}
{{--                <div class="percentDiv">--}}

{{--                    <div class="percent p75 ">--}}
{{--                        <p class="percentNum">75 <span> % </span> </p>--}}
{{--                        <div class="slice">--}}
{{--                            <div class="bar"></div>--}}
{{--                            <div class="fill"></div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <h5> عربي </h5>--}}
{{--                </div>--}}
{{--                <div class="percentDiv">--}}

{{--                    <div class="percent p100 ">--}}
{{--                        <p class="percentNum"> 100 <span> % </span> </p>--}}
{{--                        <div class="slice">--}}
{{--                            <div class="bar"></div>--}}
{{--                            <div class="fill"></div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <h5> إنجليزي </h5>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
    </div>
    <!-- col -->
    <div class="informationCol">
        <div class="category">
            <h3 class="title"><span> البيانات الشخصية </span></h3>
            <div class="innerInfo">
                <div class="infoRow">
                    <!-- data -->
                    <div class="data">
                        <h6 class=" innerTitle"> {{helperTrans('web.nationality')}} </h6>
                        <h3 class="innerData"> {{$data->nationality->title}} </h3>
                    </div>
                    <!-- data -->
                    <div class="data">
                        <h6 class=" innerTitle"> {{helperTrans('web.age')}} </h6>
                        <h3 class="innerData"> {{$data->age}} </h3>
                    </div>
                    <!-- data -->
                    <div class="data">
                        <h6 class=" innerTitle"> الحالة الاجتماعية </h6>
                        <h3 class="innerData"> {{$data->marital_status}} </h3>
                    </div>
                    <!-- data -->
                </div>
                <div class="infoRow">
                    <!-- data -->
                    <div class="data">
                        <h6 class=" innerTitle"> {{helperTrans('web.religion')}}  </h6>
                        <h3 class="innerData"> {{$data->religion->title}} </h3>
                    </div>
                    <!-- data -->
                    <div class="data">
                        <h6 class=" innerTitle"> {{helperTrans('web.skills')}} </h6>
                        <h5 class="innerData"
                            style="display: flex ; align-items: center; gap: 8px ; flex-wrap: wrap">
                            @foreach($data->jobTitles as $job)
                                <span> {{$job->title}} </span>
                            @endforeach
                        </h5>
                    </div>
                    <!-- data -->
                    <div class="data">
                        <h6 class=" innerTitle"> {{helperTrans('ingaz.languages she talk')}} </h6>
                        <h3 class="innerData"> {{$data->languages}} </h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="category">
            <h3 class="title"><span> تفاصيل جواز السفر </span></h3>
            <div class="passportData">
                <div class="data">
                    <h6> رقم جواز السفر : </h6>
                    <h5> {{$data->passport_num}} </h5>
                </div>
            </div>
        </div>
        <!-- <div class="category">
            <h3 class="title"><span> المهارات </span></h3>
            <div class="passportData">
                <div class="data">
                    <h5> الطبخ </h5>
                    <h5> التنظيف </h5>
                    <h5> رعاية الأطفال </h5>
                </div>
            </div>
        </div> -->
    </div>


</div>
<form id="yourFormId">
    @csrf
<!-- Other form fields -->
</form>

{{--<script src="{{asset('admin')}}/assets/libs/jquery/jquery.min.js"></script>--}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
<script src="https://cdn.rawgit.com/hongru/canvas2image/master/canvas2image.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>

<script>

    $(document).ready(function () {
        html2canvas(widget).then(function (canvas) {
            canvas.toBlob(function (blob) {
                var formData = new FormData();
                formData.append('cv_preview', blob, 'myImage.png');
                formData.append('_token', '{{ csrf_token() }}');
                console.log(formData);
                /*html2canvas(widget).then(function(canvas) {
                            canvas.toBlob(function(blob) {
                                saveAs(blob, 'myImage.png');
                            });
                        });*/

                $.ajax({
                    url: '{{route('maids.update_cv', ['id' => $data->id])}}',
                    type: 'post',
                    data: formData,
                    cache: false,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    contentType: false,
                    processData: false,
                    success: function (response) {
                        console.log(response);
                        window.setTimeout(function () {
                            window.location.href = "{{route('maids.index')}}";
                        }, 2);
                    },
                    error: function (xhr) {
                        console.log(xhr.responseText);
                    }
                });
            });
        });
    });

</script>
</body>

</html>
