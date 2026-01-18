@extends('admin.layout.indexs.index')
@section("style")
@endsection
@section('page-title')
{{helperTrans("ingaz.notifications")}}
@endsection
@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::NOTIFICATIONS_CONTROL))

    <form class="row g-3 from-submit-global" method="post" action="{{route("notifications.store")}}">
        @csrf
        <label for="TextInput" class="form-label">
            {{helperTrans("ingaz.notification type")}}</label>
        <select name="type" class="form-control">
            <option value="normal">اشعار</option>
            <option value="sms">رسالة نصية</option>
        </select>
        @foreach (config('translatable.locales') as $locale)
        <div class="col-6">
            <label for="TextInput" class="form-label">{{helperTrans("ingaz.title")}} ({{helperTrans("ingaz.".$locale)}}
                ) </label>
            <input type="text" name="title[{{ $locale }}]" class="form-control" data-validation="required">
        </div>
        @endforeach
        @foreach (config('translatable.locales') as $locale)
        <div class="col-6">
            <label for="TextInput" class="form-label">{{helperTrans("ingaz.description")}}
                ({{helperTrans("ingaz.".$locale)}}
                ) </label>
            <textarea type="text" name="description[{{ $locale }}]" class="form-control"
                data-validation="required"></textarea>
        </div>
        @endforeach
        <div class="col-12">
            <label for="TextInput" class="form-label">{{helperTrans("ingaz.who")}} </label>
            <select name="all_or_some" id="who" class="form-control" data-validation="required">
                {{-- <option value="all">{{helperTrans('ingaz.all')}}</option> --}}
                <option value="some" selected>{{helperTrans('ingaz.someone')}}</option>
            </select>
        </div>
        <div class="col-12" style="" id="some">
            <label for="TextInput" class="form-label">{{helperTrans("ingaz.appUsers")}} </label>
            <select name="user_id[]" data-placeholder="{{helperTrans('ingaz.choose')}}"
                class="form-control js-example-basic-multiple" multiple data-validation="required">
                <option value="">{{helperTrans('ingaz.choose')}}</option>
                @foreach($appUsers as $user)
                <option value="{{$user->id}}">{{$user->name}}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 modal-footer">
            <button class="btn btn-primary" type="submit">{{__("buttons.save")}}</button>
            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{__("buttons.close")}}</button>
        </div>
    </form>

@else
    @include('components.no_permissions')
@endif

@endsection
@section('js')
<script>
    $(document).ready(function () {
            $('#who').on('change', function () {
                if ($(this).val() === 'some') {
                    $('#some').fadeIn();
                } else {
                    $('#some').fadeOut();
                }
            });
        });
        $(document).ready(function () {
            $('.js-example-basic-multiple').select2();
        });
</script>

@endsection
