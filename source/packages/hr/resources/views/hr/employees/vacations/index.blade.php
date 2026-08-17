@extends('click.layout.indexs.index')
@section('style')
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{ __('hr::hr.employeeVacations') }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ __('finance.clients') }}</li>
@endsection
@section('content')

    {!! addButton($createRoute , $addButtonText,"btn-success",false) !!}

    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>{{ __('hr::hr.SL') }}</th>
            <th>{{ __('hr::hr.the kind of holiday') }}</th>
            <th>{{ __('hr::hr.Balance') }} </th>
            <th>{{ __('hr::hr.From the date of') }}</th>
            <th>{{ __('hr::hr.To date') }}</th>
            <th>{{ __('hr::hr.Date difference') }}</th>
            <th>{{ __('hr::hr.comments') }} </th>
            <th>{{ __('hr::hr.Status') }}</th>
            <th>{{ __('hr::hr.return') }}</th>
            <th class="notPrintable">{{ __('hr::hr.file') }}</th>
            <th class="notPrintable">{{ __('hr::hr.actions') }}</th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>

@endsection
@section('js')
    @include('hr::hr.employees.vacations._js')
@endsection
