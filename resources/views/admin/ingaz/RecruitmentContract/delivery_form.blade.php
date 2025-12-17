<!DOCTYPE html>

<head>
    <!-- <meta content="text/html; charset=utf-8" http-equiv="Content-Type"/> -->
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <title>Report Design</title>
    <style type="text/css">
        * {
            margin: 0;
            padding: 0;
            text-indent: 0;
            width: 100%;
        }

        .container {
            padding-left: 5px;
            padding-right: 5px;
            margin-right: auto;
            margin-left: auto;
            width: 100%;
        }

        h1 {
            color: black;
            font-family: 'DejaVu Sans', 'Roboto', 'Montserrat', 'Open Sans', 'sans-seri';
            font-style: normal;
            font-weight: bold;
            text-decoration: none;
            font-size: 12pt;

        }

        p {
            color: black;
            font-family: "DejaVu Sans", serif;
            font-style: normal;
            font-weight: normal;
            text-decoration: none;
            font-size: 12pt;
            font-family: 'DejaVu Sans', 'Roboto', 'Montserrat', 'Open Sans', 'sans-seri';
        }

        .s1 {
            color: black;
            font-family: "DejaVu Sans", serif;
            font-style: normal;
            font-weight: normal;
            text-decoration: none;
            font-size: 12pt;
            font-family: 'DejaVu Sans', 'Roboto', 'Montserrat', 'Open Sans', 'sans-seri';
        }

        .s2 {
            color: black;
            font-family: "DejaVu Sans", serif;
            font-style: normal;
            font-weight: bold;
            font-size: 12pt;
            vertical-align: 1pt;
            font-family: 'DejaVu Sans', 'Roboto', 'Montserrat', 'Open Sans', 'sans-seri';
        }

        span {
            font-family: 'DejaVu Sans', 'Roboto', 'Montserrat', 'Open Sans', 'sans-seri';
        }
    </style>
</head>

<body>
    {{-- <img height="128" src="{{static_asset('assets_v2')}}/images/logos/Htafrrod.svg" --}}
    {{--     width="810" alt="..." /> --}}

    <div class="container">
        <p style="text-indent: 0pt;text-align: left;"><br /></p>

        <h1 style="padding-top: 5pt;text-indent: 0pt;text-align: center;">نموذج تسليم عاملة منزلية</h1>
        <p style="text-indent: 0pt;text-align: left;"><br /></p>

        <div class="row">
            <p style="text-indent: 0pt;text-align: right;font-weight: bold;" dir="rtl" lang="ar"> اسم العميل :
                {{ $client_name }}</p> <br>
            <p class="col-md-6 text-end" dir="rtl" lang="ar">
                <span> الهاتف / الجوال: المنزل :{{ $client_phone }}</span>
            </p>
            <p class="col-md-6 text-end" dir="rtl" lang="ar">
                <span> السجل المدني / الإقامة : {{ $national_identification_number }}</span>
            </p>

        </div>

        <br>
        <hr>

        <div class="row">
            <p class="col-md-6 text-end" dir="rtl" lang="ar">
                <span> الجنسية :{{ $nationality_id }}</span>
            </p>
            <p class="col-md-6 text-end" dir="rtl" lang="ar">
                رقم جواز السفر / الإقامة : {{ $passport_id }}
            </p>
            <p class="col-md-4 text-end" dir="rtl" lang="ar">
                تاريخ الوصول : {{ !empty($arrive_date) ? date('m/d/Y', strtotime($arrive_date)) : '--' }}
            </p>
        </div>

        <hr>

        <span style="padding-top: 4pt;padding-right: 6pt;text-indent: 0pt;text-align: right;" dir="rtl"
            lang="ar">
            <span class="row">
                <span dir="rtl" lang="ar" class="col-md-6"> اقر انا المستلم : <span style="font-weight: bold;"
                        dir="rtl" lang="ar"> {{ $client_name }}</span></span>
                <span dir="rtl" lang="ar" class="col-md-6"> بطاقة رقم الهوية : <span
                        style="font-weight: bold;" dir="rtl" lang="ar"> {{ $national_identification_number }}
                    </span></span>

                <span dir="rtl" lang="ar">
                    قد استلمت العاملة المنزلية الموضح بياناتها أعلاه وذلك يوم:
                    {{ date('m/d/Y', strtotime(\Carbon\Carbon::now())) }}
                </span>
                <span dir="rtl" lang="ar">
                    وبهذا قد اصبح مكتب / <span style="font-weight: bold;"> مكتب بلاتنيوم الشرقية للاستقدام</span> خالي
                    من المسؤولية منذ استلامي للخادمة .
                </span>

            </span>

            </p>


            <hr>

            <div class="row">
                <p style="padding-top: 2pt;text-indent: 0pt;text-align: justify;font-weight: bold; " dir="rtl"
                    lang="ar">
                    مكتب تفرد للاستقدام
                </p> <br>
                <p dir="rtl" lang="ar" class="col-md-6 text-end">
                    اسم الموظفة : {{ $adminStaff }}
                </p>
                <p dir="rtl" lang="ar" class="col-md-6 text-end">
                    <span> اسم المستلم : {{ $client_name }}</span>
                </p>
        </span></p>
        <p class="col-md-6 text-end" dir="rtl" lang="ar">
            التاريخ : {{ date('m/d/Y', strtotime($arrive_date)) }}
        </p>
        <p class="col-md-6 text-end" dir="rtl" lang="ar">
            <span> العنوان : {{ $client_address }}</span>
        </p>
        <p class="col-md-6 text-end" dir="rtl" lang="ar">التوقيع /</p>
        <p class="col-md-6 text-end" dir="rtl" lang="ar">التوقيع /</p>
    </div>



    </div>


    {{-- <div class="fixed-bottom"> --}}
    {{--    <img    height="80" src="" --}}
    {{--            width="815" alt="..."/> --}}
    {{-- </div> --}}


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous">
    </script>
</body>

</html>
