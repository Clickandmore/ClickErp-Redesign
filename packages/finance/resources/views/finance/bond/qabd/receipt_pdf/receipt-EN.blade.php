<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nami-Receipt</title>
    <style>
        @import 'https://fonts.googleapis.com/css2?family=Noto+Kufi+Arabic:wght@300;400;500;600;700&display=swap';

        * {
            margin: 0;
            padding: 0;
            line-height: 28px;
            box-sizing: border-box;
            scroll-behavior: smooth;
            -webkit-tap-highlight-color: transparent;
            font-family: "Noto Kufi Arabic", sans-serif;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }


        .container {
            position: relative;
            margin: auto;
            display: flex;
            flex-direction: column;
            gap: 32px;
            /* width: 210mm; */
            padding: 40px
        }

        .container .unerlay {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: -1;
        }

        .container .unerlay img {
            max-width: 50%;
            opacity: 0.1;
        }

        .container .reseipt-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .container .reseipt-header .logo {
            flex: 1;
            text-align: end
        }

        .container .reseipt-header .logo img {
            max-height: 40px;
        }

        .container .reseipt-header h2 {
            color: #000;
            text-transform: uppercase;
            font-size: 16px;
            flex: 1
        }

        .container .reseipt-header h2 span {
            color: #32a94f;
        }

        .container .date {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
            gap: 16px
        }

        .container .date .date-info {
            flex: 1
        }

        .container .date .date-info input {
            background: none;
            outline: none;
            border: none;
            /*width: 36px;*/
        }

        .container .date .date-info input::-webkit-inner-spin-button {
            display: none;
        }

        .container .date .pay {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            /* padding-left: 160px; */
            gap: 8px;
        }

        .container .date .pay h2 {
            font-size: 20px;
        }

        .container .date .pay .line {
            width: 100%;
            height: 4px;
            display: block;
            background-color: #32a94f;
        }

        .container .date .total {
            flex: 1;
            display: flex;
            justify-content: flex-end;
        }

        .container .date .total .price {
            position: relative;
            padding: 12px 24px;
            /* width: 180px; */
            border: 2px solid #000;
            border-radius: 80px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .container .date .price .h {
            position: absolute;
            top: -30px;
            right: 25px;
        }

        /* .container .date .price .sr {
            position: absolute;
            top: 50%;
            left: 20px;
            transform: translateY(-50%);
        } */
        .container .date .price .line {
            width: 2px;
            height: 100%;
            background: #000;
        }

        .container .date .price input {
            height: 100%;
            width: 100px;
            text-align: center;
            outline: none;
            border: none;
            background: none;
        }

        .container .date .price input::-webkit-inner-spin-button {
            appearance: none;
        }

        .container .resciev {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .container .resciev p {
            line-height: 11px;
            white-space: nowrap;
        }

        .container .resciev input {
            /* width: 100%; */
            border: none;
            outline: none;
            background: none;
            /* border-bottom: 1px dashed #000; */
            position: relative;
        }

        .container .resciev input::after {
            content: '';
            width: 100%;
            height: 2px;
            background-color: #000;
            flex: 1
        }

        .container .sign-row {
            display: flex;
            justify-content: space-between;
            text-align: center
        }

        .container .sign-row div {
            display: flex;
            flex-direction: column;
        }

        .container .sign-row h6 {
            font-size: 16px;
            color: #000;
        }

        .container .sign-row input {
            width: 140px;
            outline: none;
            background: none;
            border: none;
            border-bottom: 1px dashed #000;
        }

        .container .footer {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }

        .container .footer a {
            color: #000;
            text-decoration: none;
        }

        .dataLine {
            position: relative;
            display: inline-flex;
            width: 100%;
            gap: 12px;
            align-items: center
        }

        .dataLine::after {
            content: '';
            flex: 1;
            height: 1px;
            width: 100%;
            border-bottom: 1px dashed #000;
        }

        .signImg {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: center;
            justify-content: space-between;
        }

        .signImg img {
            height: 50px;
            object-fit: contain
        }
    </style>
</head>

<body>
<div class="container">
    <div class="unerlay">
        <img src="{{ asset('admin') }}/assets/svg/Frame 36125-2.svg" alt="">
    </div>
    <div class="reseipt-header">
        <h2><span>Nami</span> Software Development</h2>

        <div class="logo">
            <img src="{{ asset('admin') }}/assets/svg/Frame 36125.svg" alt="">
        </div>
    </div>
    @php
        $currency = 'EG';
        $finalValue = $bond->value;
        if ($bond->value == 0) {
            $finalValue = $bond->value_other;
            $currency = 'USD';
        }
    @endphp
    <div class="date">
        <div class="date-info">
            <div class="row">
                <span> Corresponding Date: </span>
                {{-- <h5 class="dataLine">{{ $current_day }} من {{ $current_month }} لعام {{ $current_year }}</h5> --}}
                <input type="text" readonly
                       value="{{ $current_day }} From {{ $current_month }} For Year {{ $current_year }}">
            </div>
        </div>
        <div class="pay">
            <h3>{{ getEnumValue('bond_types', $bond->type) }}</h3>
            {{--                <h3>RECEIPT VOUCHER</h3>--}}
            <div class="line"></div>
        </div>
        <div class="total">
            <div class="price">
                {{--            <label for="h" class="h">هللة h</label> --}}
                {{--            <input type="number" placeholder="0" id="h"> --}}
                {{--            <div class="line"></div> --}}
                <h4>{{ $finalValue }}</h4>
                <label for="Sr" class="sr">{{ $currency}}</label>
                {{-- <input type="text" readonly value="" placeholder="0" id="Sr"> --}}
            </div>
        </div>
    </div>
    <div class="resciev">
        <p>Received From MR. / MIS : </p>
        <h5 class="dataLine">{{ $bond->creditAccount->name }}</h5>
        {{-- <input type="text" readonly value=""> --}}
    </div>
    <div class="resciev">
        <p> THE AMOUNT OF : </p>
        <h5 class="dataLine">({{ $finalValue }}) - {{  $finalValue /*numberToEnglish()*/ }} {{ $currency }} , Just
            nothing else </h5>
        {{-- <input type="text" readonly value=""> --}}
        {{-- <p>:THE AMOUNT OF</p> --}}
    </div>
    @if ($bond->payment_type == \Nami\Finance\Enums\BondPaymentTypies::Check->value)
        <div class="resciev">
            <p> شيك رقم: </p>
            <h5 class="dataLine"></h5>
            {{-- <input type="text"> --}}
            <P>البنك</P>
            <h5 class="dataLine"></h5>
            {{-- <input type="text"> --}}
            <p>:CASH - CHEGUE NO</p>
        </div>
    @endif
    <div class="resciev">
        <p>FOR : </p>
        <h5 class="dataLine">{{ $bond->statement }}</h5>
        {{-- <input type="text" readonly value=""> --}}
    </div>
    <div class="sign-row">
        <div>
            <h6>ACCOUNTANT</h6>
            <h5> Ahmed Mostafa </h5>
            {{-- <input type="text" value="أحمد مصطفى "> --}}
        </div>
        <div class="signImg">
            <h6> Signature </h6>
            <img src="{{ asset('admin') }}/assets/svg/signature.svg" alt="">
        </div>
        <div>
            <h6>RECEIVER</h6>
            <h5> Ahmed Mostafa </h5>
            {{-- <input type="text" value="أحمد مصطفى "> --}}
        </div>
    </div>
    <div class="footer">
        <p>EMAIL: <a href="mailto:Namisoftgroup@gmail.com">Namisoftgroup@gmail.com</a></p>
        <p>MOBILE: <a href="tel:+201099347981">01099347981</a></p>
    </div>
</div>
<script>
    // window.print();
    // window.addEventListener("afterprint", () => {
    //     history.back();
    // });
    window.print();
    const mediaQueryList = window.matchMedia('print');
    mediaQueryList.addListener((mql) => {
        if (!mql.matches) {
            // Print dialog has been closed
            window.onafterprint = function () {
                history.back();
            };
        }
    });
</script>
</body>

</html>
