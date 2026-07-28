@extends('admin.layout.indexs.index')

@section('page-title')
    {{ helperTrans('ingaz.requiremens') }}
@endsection

@section('content')
    @if (@AdminGuardHasAnyPerm([\App\PermKeys::RECRUITMENT_REQUIREMENTS_CREATE, \App\PermKeys::RECRUITMENT_REQUIREMENTS_CONTROL]))
        {!! addButton(route('requiremens.create'), helperTrans('ingaz.requiremens')) !!}
    @endif

    <p></p>
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('recruitment_requirements.sort_order') }}</th>
                <th>{{ helperTrans('ingaz.title') }}</th>
                <th>{{ helperTrans('ingaz.requirement_type') }}</th>
                <th>{{ __('recruitment_requirements.points_count') }}</th>
                <th>{{ __('recruitment_requirements.status') }}</th>
                <th>{{ helperTrans('ingaz.actions') }}</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
@endsection

@section('js')
    <script>
        $(document).ready(function () {
            const columns = [
                {"data": "DT_RowIndex", name: "DT_RowIndex", orderable: false, searchable: false},
                {"data": "sort_order", orderable: true, searchable: false},
                {"data": "title", orderable: true, searchable: true},
                {"data": "type", orderable: true, searchable: true},
                {"data": "items_count", orderable: true, searchable: false},
                {"data": "is_active", orderable: true, searchable: false},
                {"data": "actions", orderable: false, searchable: false}
            ];

            showDataTable("{{ route('requiremens.index') }}", columns);
        });
    </script>
@endsection
