<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>فاتورة ضريبية #{{ $obj->code }}</title>
    @include('finance::finance.bill_sales._document-styles')
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            background: #eef1f4;
        }

        .print-toolbar {
            width: min(1180px, calc(100% - 32px));
            margin: 14px auto;
            display: flex;
            justify-content: center;
        }

        .print-toolbar button {
            border: 0;
            border-radius: 8px;
            padding: 10px 24px;
            background: #147d70;
            color: #fff;
            font: 700 15px "DejaVu Sans", Arial, sans-serif;
            cursor: pointer;
        }

        @media print {
            html,
            body {
                background: #fff;
            }

            .print-toolbar {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="print-toolbar">
        <button type="button" onclick="window.print()">طباعة الفاتورة</button>
    </div>

    @include('finance::finance.bill_sales._document')

    <script>
        window.addEventListener('load', function() {
            window.print();
        });
    </script>
</body>
</html>
