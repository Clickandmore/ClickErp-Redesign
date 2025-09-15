@if (app()->getLocale() == 'ar')
    <link rel="stylesheet" href="{{ asset('web') }}/assets/css/bootstrap.rtl.min.css">
@else
    <link rel="stylesheet" href="{{ asset('web') }}/assets/css/bootstrap.min.css">
@endif

<link rel="stylesheet" href="{{ asset('web') }}/assets/css/main.css">

@if (app()->getLocale() == 'ar')
    <link rel="stylesheet" href="{{ asset('web') }}/assets/css/style.css">
@else
    <link rel="stylesheet" href="{{ asset('web') }}/assets/css/styleEN.css">
@endif

<!-- ملفات CSS الثانوية فقط هي التي يمكن تحميلها بشكل غير متزامن -->
<link rel="stylesheet" href="{{ asset('web') }}/assets/css/bootstrap-select.css" media="print" onload="this.media='all'">
<link rel="stylesheet" href="{{ asset('admin/assets/vendor/toster/css/jquery.toast.css') }}">

<style>
    .toast-success {
        background-color: #1d70b7 !important;
    }

    .jq-icon-success {
        background-color: #1d70b7 !important;
    }
</style>

@stack('css')

@section('css')
@endsection
