@extends('admin.layout.indexs.index')
@section('page-title')
{{helperTrans("ingaz.edit_client")}}
@endsection
@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_VIEW))
    <form action="{{ route('clients.update', $client->id) }}" method="POST" class="row from-submit-global">
        @csrf
        @method('PUT')

        <div class="col-6">
            <label for="name" class="form-label">{{helperTrans("ingaz.name")}}</label>
            <input type="text" id="name" name="name" class="form-control" value="{{ $client->name }}" required>
        </div>

        <div class="col-6">
            <label for="phone_code" class="form-label">{{helperTrans("ingaz.phone_code")}}</label>
            <input type="text" id="phone_code" name="phone_code" class="form-control" value="{{ $client->phone_code }}" required>
        </div>

        <div class="col-6">
            <label for="phone" class="form-label">{{helperTrans("ingaz.phone")}}</label>
            <input type="text" id="phone" name="phone" class="form-control" value="{{ $client->phone }}" required>
        </div>

        <div class="col-6">
            <label for="email" class="form-label">{{helperTrans("ingaz.email")}}</label>
            <input type="email" id="email" name="email" class="form-control" value="{{ $client->email }}">
        </div>

        <div class="col-6">
            <label for="nationality_id" class="form-label">{{helperTrans("ingaz.nationality_id")}}</label>
            <select name="nationality_id" id="nationality_id" class="form-control">
                <option value="">{{ helperTrans('ingaz.choose') }}</option>
                @foreach($countries as $country)
                    <option value="{{ $country->id }}" @if($client->nationality_id == $country->id) selected @endif>{{ $country->name_ar }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-6">
            <label for="national_num" class="form-label">{{helperTrans("ingaz.national_num")}}</label>
            <input type="text" id="national_num" name="national_num" class="form-control" value="{{ $client->national_num }}">
        </div>

        <div class="col-6">
            <label for="birthdate" class="form-label">{{helperTrans("ingaz.birthdate")}}</label>
            <input type="date" id="birthdate" name="birthdate" class="form-control" value="{{ $client->birthdate }}">
        </div>

        <div class="col-6">
            <label for="phone2" class="form-label">{{helperTrans("ingaz.phone2")}}</label>
            <input type="text" id="phone2" name="phone2" class="form-control" value="{{ $client->phone2 }}">
        </div>

        <div class="col-6">
            <label for="address" class="form-label">{{helperTrans("ingaz.address")}}</label>
            <input type="text" id="address" name="address" class="form-control" value="{{ $client->address }}">
        </div>

        <div class="col-6">
            <label for="city" class="form-label">{{helperTrans("ingaz.city")}}</label>
            <input type="text" id="city" name="city" class="form-control" value="{{ $client->city }}">
        </div>

        <div class="col-6">
            <label for="rooms_num" class="form-label">{{helperTrans("ingaz.rooms_num")}}</label>
            <input type="number" id="rooms_num" name="rooms_num" class="form-control" value="{{ $client->rooms_num }}">
        </div>

        <div class="col-6">
            <label for="family_num" class="form-label">{{helperTrans("ingaz.family_num")}}</label>
            <input type="number" id="family_num" name="family_num" class="form-control" value="{{ $client->family_num }}">
        </div>

        <div class="col-6">
            <label for="children" class="form-label">{{helperTrans("ingaz.children")}}</label>
            <input type="number" id="children" name="children" class="form-control" value="{{ $client->children }}">
        </div>

        <div class="col-6">
            <label for="job" class="form-label">{{helperTrans("ingaz.job")}}</label>
            <input type="text" id="job" name="job" class="form-control" value="{{ $client->job }}">
        </div>

        <div class="col-12">
            <label for="is_blocked" class="form-label">{{helperTrans("ingaz.is_blocked")}}</label>
            <select id="is_blocked" name="is_blocked" class="form-select form-control">
                <option value="0" @if($client->is_blocked == 0) selected @endif>{{helperTrans("ingaz.active")}}</option>
                <option value="1" @if($client->is_blocked == 1) selected @endif>{{helperTrans("ingaz.blocked")}}</option>
            </select>
        </div>

        <div class="col-12 mt-3">
            <button type="submit" class="btn btn-primary">{{helperTrans("ingaz.save")}}</button>
            <a href="{{ route('clients.index') }}" class="btn btn-secondary">{{helperTrans("ingaz.cancel")}}</a>
        </div>
    </form>
@else
    @include('components.no_permissions')
@endif
@endsection

@section('js')
<script>
    $(document).ready(function() {
        $('.select2').select2();
    });
</script>
@endsection
