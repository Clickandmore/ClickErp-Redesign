@extends('admin.layout.indexs.index')
@section("style")
<!-- DataTables CSS CDN -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.css">
@endsection

@section('page-title')
    {{helperTrans("ingaz.cancel_contracts")}}
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::CANCEL_CONTRACT_VIEW))

    <div class="">
        <div class="row">
            <div class="col-md-12">
                <table id="cancelContractsTable" class="table table-bordered">
                    <thead>
                        <tr>
                            <th>{{ __('ingaz.id') }}</th>
                            <th>{{ __('ingaz.client_name') }}</th>
                            <th>{{ __('ingaz.contract_id') }}</th>
                            <th>{{ __('ingaz.contract') }}</th>
                            <th>{{ __('ingaz.contract_date') }}</th>
                            <th>{{ __('ingaz.contract_duration') }}</th>
                            <th>{{ __('ingaz.notes') }}</th>
                            <th>{{ __('ingaz.created_at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $cancelContract)
                            <tr>
                                <td>{{ @$cancelContract->id }}</td>
                                <td>{{ @$cancelContract->user->name }}</td>
                                <td>{{ @$cancelContract->contract->id }}</td>
                                <td>{{ @$cancelContract->contract->status }}</td>
                                <td>{{ @getContractInfo($cancelContract->contract->id)->contract_date }}</td>
                                <td>{{ @getContractInfo($cancelContract->contract->id)->contract_duration }}</td>
                                <td>{{ @$cancelContract->notes }}</td>
                                <td>{{ @$cancelContract->created_at }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@else
    @include('components.no_permissions')
@endif

@endsection

@section('js')
<!-- jQuery CDN -->
{{-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> --}}
<!-- DataTables JS CDN -->
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.js"></script>
<script>
    $(document).ready(function() {
        $('#cancelContractsTable').DataTable();
    });
</script>
@endsection
