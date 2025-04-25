<!DOCTYPE html>
<html>

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>{{ env('APP_NAME') }} | #{{ @$data->id }}</title>
    <meta name="_token" content="{!! csrf_token() !!}" />

    <link rel="stylesheet" type="text/css" href="{{ asset('plugins/print/print.css') }}">

    @if (App::getLocale() == 'ar')
        <link rel="stylesheet" type="text/css" href="{{ asset('plugins/print/print-rtl.css') }}">
    @endif

    <style type="text/css">
        body {
            background: #fff;
            color: #000;
            direction: rtl;
        }

        table .cu-cus {
            border-collapse: collapse;
        }

        .cu-cus td,
        .cu-cus th,
        .cu-cus td {
            border: 1px solid #d3d3d3 !important;
        }

        .table tfoot tr:last-child,
        .table tbody tr:last-child {
            border-bottom: 1px solid #d3d3d3 !important;
        }

        .table tr:first-child,
        .table th:first-child,
        .table td:first-child {
            padding-right: 10px !important;
        }

        @media print {
            .print-frame {
                height: 100vh;
            }
        }
    </style>
</head>

<body dir="rlt">
    <div class="print-frame text-right">
        <div class="container print-font">
            <div class="row PrintArea">
                <div class="row">
                    <div class="row col-md-12">
                        {!! @$outputContentString !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        window.onload = function() {
            window.print();
        }
    </script>
</body>

</html>
