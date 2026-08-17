@extends('admin.layout.indexs.index')


@section('page-title')
{{ __('admin.settings') }}
@endsection


@section('css')
<style>
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

    .ck-content {
        height: 450px !important;
    }
</style>
@endsection


@section('content')

@if (@AdminGuardHasPerm(\App\PermKeys::CONTRACT_SETTINGS))

    <form class="row" action="{{ route('new_settings.store') }}" method="post" enctype="multipart/form-data">
        @csrf

        <h3 class="my-5">
            {{ __('admin.contract_settings') }}
        </h3>
        <hr>

        <div class="col-md-12 mb-1">
            <textarea id="editor" name="contract_template">{{ @$data['contract_template'] }}</textarea>
        </div>

        <div class="row mb-3">
            <div class="col-sm-12 mt-10 text-end">
                <button type="submit" class="btn btn-success">{{ __('admin.confirm') }}</button>
            </div>
        </div>

    </form>

    <div class="row table-responsive">
        <table class="table cu-cus text-center">
            <thead>
                <tr>
                    <th>@lang('admin.desc')</th>
                    <th>@lang('admin.code')</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>@lang('admin.app_name')</td>
                    <td dir="ltr">@app_name</td>
                </tr>
                <tr>
                    <td>@lang('admin.todays_date')</td>
                    <td dir="ltr">@todays_date</td>
                </tr>
                <tr>
                    <td>@lang('admin.client_name')</td>
                    <td dir="ltr">@client_name</td>
                </tr>
                <tr>
                    <td>@lang('admin.client_nationality')</td>
                    <td dir="ltr">@client_nationality</td>
                </tr>
                <tr>
                    <td>@lang('admin.client_national_number')</td>
                    <td dir="ltr">@client_national_number</td>
                </tr>
                <tr>
                    <td>@lang('admin.client_address')</td>
                    <td dir="ltr">@client_address</td>
                </tr>
                <tr>
                    <td>@lang('admin.client_phone_code')</td>
                    <td dir="ltr">@client_phone_code</td>
                </tr>
                <tr>
                    <td>@lang('admin.client_phone_number')</td>
                    <td dir="ltr">@client_phone_number</td>
                </tr>
                <tr>
                    <td>@lang('admin.maid_nationality')</td>
                    <td dir="ltr">@maid_nationality</td>
                </tr>
                <tr>
                    <td>@lang('admin.maid_passport_num')</td>
                    <td dir="ltr">@maid_passport_num</td>
                </tr>
                <tr>
                    <td>@lang('admin.maid_experience_years')</td>
                    <td dir="ltr">@maid_experience_years</td>
                </tr>
                <tr>
                    <td>@lang('admin.contract_working_hours_num')</td>
                    <td dir="ltr">@contract_working_hours_num</td>
                </tr>
                <tr>
                    <td>@lang('admin.contract_duration')</td>
                    <td dir="ltr">@contract_contract_duration</td>
                </tr>
                <tr>
                    <td>@lang('admin.contract_monthly_cost')</td>
                    <td dir="ltr">@contract_monthly_cost</td>
                </tr>
                <tr>
                    <td>@lang('admin.contract_date')</td>
                    <td dir="ltr">@contract_date</td>
                </tr>
                <tr>
                    <td>@lang('admin.contract_expire_date')</td>
                    <td dir="ltr">@contract_expire_date</td>
                </tr>
            </tbody>
        </table>

    </div>

@else
    @include('components.no_permissions')
@endif


@endsection

@section('js')
@endsection


@include('components.sweetalert')
@include('components.ckeditor')
