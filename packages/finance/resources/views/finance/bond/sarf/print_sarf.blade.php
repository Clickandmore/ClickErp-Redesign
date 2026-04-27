<html lang="Ar">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="" />

  <title>{{ __('finance.print_bond_sarf') }}</title>

  <!-- bootstrap -->
  <link rel="stylesheet" href="{{asset('admin')}}/assets/css/bootstrap.rtl.min.css" />
  <!-- Css -->
  <link rel="stylesheet" href="{{asset('admin')}}/assets/css/style.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@100;200;300;400;500;600;700&display=swap"
    rel="stylesheet" />
  <style>
    * {
      box-sizing: border-box;
      padding: 0;
      margin: 0;
      direction: rtl;
      text-align: start;
      font-family: "IBM Plex Sans Arabic", sans-serif;
      color: #000;
    }

    body {
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
      border: 1px solid #000;
      border-top: 20px solid #4c818b;
    }

    header .Logo {
      display: flex;
      align-items: center;
      justify-content: center;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      gap: 15px;
    }

    header .Logo img {
      width: 70px;
    }

    header .Title {
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
    }

    header .Title h1 {
      font-size: 25px;
      line-height: 1.8;
      color: #4c818b;
    }

    header .Title hr {
      border: 1px solid #000;
      width: 100%;
    }

    header .Title h2 {
      font-size: 22px;
      color: #4c818b;
    }

    .main {
      padding: 25px;
      padding-bottom: 15px;
      border-bottom: 10px solid #4c818b;
    }

    .main .Date-Container {
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 20px;
      margin-bottom: 15px;
    }

    .main .Date-Container .coins {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 4px;
    }

    .main .Date-Container .coins .one {
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      gap: 4px;
    }

    .main .Date-Container .coins .one span {
      font-size: 14px;
      font-weight: bold;
    }

    .main .Date-Container .coins .one .input {
      border: 2px solid #000;
      padding: 5px 10px;
    }

    .main .Date-Container .coins .one .input_two {
      border: 2px solid #000;
      padding: 5px 50px;
    }

    .main .Date-Container .Date h3 {
      font-size: 14px;
      margin-bottom: 10px;
    }

    .main .info {
      display: flex;
      justify-content: center;
      flex-direction: column;
      gap: 22px;
      margin-top: 40px;
    }

    .main .info .Data {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .main .info .Data p {
      font-size: 16px;
      font-weight: 500;
    }

    .main .info .Data hr {
      border: 1px solid #eee;
      width: 60%;
    }

    .main .info .Data input {
      width: 25px;
    }

    .Data {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .Data p {
      font-size: 16px;
      font-weight: 500;
    }

    .Data hr {
      border: 1px solid #eee;
      width: 60%;
    }

    .Data input {
      width: 25px;
    }

    /*# sourceMappingURL=style.css.map */
  </style>
</head>

<body>
  <!-- header -->

  <header>
    <div class="Logo">

      <img src="{{ showFile(@$setting_web->logo_header) }}" alt="logo" />

      <div class="Title">
        <h1>سـنـد صــرف</h1>
        <hr />
        <h2>Payment Voucher</h2>

      </div>

    </div>
  </header>

  <main class="main">

    <div class="Date-Container">
      <div class="coins">
        <div class="one">
          <span>H.هـ</span>
          <div class="input">{{ ((double)$bond->value - (int)$bond->value) * 10 }}</div>
        </div>
        <div class="one">
          <span> S.R ريـال</span>
          <div class="input_two">{{ (int)$bond->value }}</div>
        </div>


      </div>
      <div class="Date" style="margin-top: 20px;">
        <h3> التاريخ : {{$bond->date}}</h3>
        <h3 style="margin: 0;"> الموافق ____/ ____/____ 14 هـ</h3>
      </div>

    </div>

    <div class="info">

      <div class="Data">
        <p>إصرفو الي الساده / السيد:  {{ $bond->debitAccount->name }} </p>
        <p>:Pay To M / S </p>
      </div>

      <div class="Data">
        <p>مبلغ وقدره : {{ $bond->value }}</p>
        <p>: Amount </p>
      </div>

      <div class="Data">
        <p> نقد <input /> / كاش <input /> </p>
        <hr style="width: 25%;" />
        <p> Cash / cheque No</p>
        @if ($bond->payment_type != 1 && $bond->payment_type != 5)
            <p>علي بنك : {{ $bond->creditAccount->name }}</p>
        @else
            <hr style="width: 25%;" />
        @endif
        <p>Date</p>
      </div>



      <div class="Data">
        <p>وذلك مقابل : {{ $bond->statement }}</p>
        <p>Being </p>
      </div>

      <hr />

      <div style="display: flex; align-items: center; justify-content: space-between;">

        <div class="Data">
          <p> المحاسب</p>
          <p>.........................</p>
          <p>Accountant </p>
        </div>

        <div class="Data">
          <p>المستلم</p>
          <p>.........................</p>
          <p>Receiver </p>
        </div>


      </div>







    </div>


  </main>

  <script>
    window.print(); // Print the current page

    window.onafterprint = function() {
        window.close(); // Close the current tab
    };
</script>
</body>

</html>
