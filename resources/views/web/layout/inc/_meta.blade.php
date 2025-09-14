
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
  <!-- favicon -->
<link rel="shortcut icon" href="{{ isset($settings) ? showFile($settings->favicon): '' }}" type="image/x-icon">
<!-- *** css *** -->

<meta name="title" content="@if(isset($settings) && isset($settings->website_name))
            {{$settings->website_name}}@endif">
{{--<meta name="description"--}}
{{--      content="اكتشف عالم السيارات بمتجر خدمتى - حيث الجودة تلتقي بالتنوع. احصل على أفضل قطع الغيار والإكسسوارات الرائعة لسيارتك اليوم!">--}}


