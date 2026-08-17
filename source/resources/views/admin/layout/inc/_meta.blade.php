<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="description" content="">
<meta name="keyword" content="">
<meta name="csrf-token" content="{{ csrf_token() }}">

@php
    $favicon =
        isset($settings) && !empty($settings->favicon)
            ? showFile($settings->favicon)
                : asset('admin/assets/img/fav.png');
@endphp

<link rel="icon" href="{{ $favicon }}" type="image/x-icon">
