<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8"/>
    <title>Report Design</title>
    <style type="text/css">

        @font-face {
            font-family: 'DejaVu Sans';
            src: url({{ storage_path('fonts/DejaVuSans.ttf') }}) format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        * {
            margin: 0;
            padding: 0;
            text-indent: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', 'Roboto', 'Montserrat', 'Open Sans', sans-serif;
            padding: 20px;
            direction: rtl;
        }

        .container {
            padding-left: 5px;
            padding-right: 5px;
            margin-right: auto;
            margin-left: auto;
            max-width: 1200px;
        }

        h1 {
            color: black;
            font-style: normal;
            font-weight: bold;
            text-decoration: none;
            font-size: 12pt;
            padding-top: 5pt;
            text-align: center;
        }

        p {
            color: black;
            font-style: normal;
            font-weight: normal;
            text-decoration: none;
            font-size: 12pt;
            margin-bottom: 10px;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -5px;
            margin-left: -5px;
        }

        .col-md-6 {
            flex: 0 0 50%;
            max-width: 50%;
            padding-right: 5px;
            padding-left: 5px;
        }

        .col-md-4 {
            flex: 0 0 33.333333%;
            max-width: 33.333333%;
            padding-right: 5px;
            padding-left: 5px;
        }

        .text-end {
            text-align: right;
        }

        hr {
            margin: 15px 0;
            border: 0;
            border-top: 1px solid #dee2e6;
        }

        .bold {
            font-weight: bold;
        }

        span {
            font-family: 'DejaVu Sans', 'Roboto', 'Montserrat', 'Open Sans', sans-serif;
        }

        br {
            display: block;
            content: "";
            margin: 5px 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .col-md-6, .col-md-4 {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <p><br/></p>
        
        <h1>نموذج تسليم عاملة منزلية</h1>
        <p><br/></p>
        
        <div class="row">
            <p class="bold text-end" style="width: 100%;">اسم العميل : {{$client_name}}</p>
            <br>
            <p class="col-md-6 text-end">
                <span>الهاتف / الجوال: المنزل : {{$client_phone}}</span>
            </p>
            <p class="col-md-6 text-end">
                <span>السجل المدني / الإقامة : {{$national_identification_number}}</span>
            </p>
        </div>
        
        <br>
        <hr>
        
        <div class="row">
            <p class="col-md-6 text-end">
                <span>الجنسية : {{$nationality_id}}</span>
            </p>
            <p class="col-md-6 text-end">
                رقم جواز السفر / الإقامة : {{$passport_id}}
            </p>
            <p class="col-md-4 text-end">
                تاريخ الوصول : {{(!empty($arrive_date)) ? date('m/d/Y', strtotime($arrive_date)) : "--"}}
            </p>
        </div>
        
        <hr>
        
        <span style="padding-top: 4pt; padding-right: 6pt;">
            <span class="row">
                <span class="col-md-6">اقر انا المستلم : <span class="bold">{{$client_name}}</span></span>
                <span class="col-md-6">بطاقة رقم الهوية : <span class="bold">{{$national_identification_number}}</span></span>
                
                <span style="width: 100%;">
                    قد استلمت العاملة المنزلية الموضح بياناتها أعلاه وذلك يوم: {{date('m/d/Y', strtotime(\Carbon\Carbon::now()))}}
                </span>
                <span style="width: 100%;">
                    وبهذا قد اصبح مكتب / <span class="bold">مكتب بلاتنيوم الشرقية للاستقدام</span> خالي من المسؤولية منذ استلامي للخادمة.
                </span>
            </span>
        </span>
        
        <hr>
        
        <div class="row">
            <p class="bold text-end" style="width: 100%;">مكتب تفرد للاستقدام</p>
            <br>
            <p class="col-md-6 text-end">
                اسم الموظفة : {{$adminStaff}}
            </p>
            <p class="col-md-6 text-end">
                <span>اسم المستلم : {{$client_name}}</span>
            </p>
            <p class="col-md-6 text-end">
                التاريخ : {{date('m/d/Y', strtotime($arrive_date))}}
            </p>
            <p class="col-md-6 text-end">
                <span>العنوان : {{$client_address}}</span>
            </p>
            <p class="col-md-6 text-end">التوقيع /</p>
            <p class="col-md-6 text-end">التوقيع /</p>
        </div>
    </div>
</body>
</html>