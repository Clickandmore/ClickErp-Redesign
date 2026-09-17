@extends('admin.layout.indexs.index')

@section('page-title')
    {{ __('why_us.sidebar_title') }}
@endsection

@section('content')
    @if (@AdminGuardHasAnyPerm([\App\PermKeys::WHY_US_CREATE, \App\PermKeys::WHY_US_CONTROL]))
        {!! addButton($createRoute, helperTrans('ingaz.why_us')) !!}
    @endif

    <p class="text-muted small mt-3 mb-2">
        <i class="fa-solid fa-circle-info"></i> {{ __('why_us.hint') }}
    </p>

    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('why_us.sort_order') }}</th>
                <th>{{ __('why_us.icon') }}</th>
                <th>{{ helperTrans('ingaz.title') }}</th>
                <th>{{ helperTrans('ingaz.description') }}</th>
                <th>{{ __('why_us.status') }}</th>
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
                {"data": "icon", orderable: false, searchable: false},
                {"data": "title", orderable: true, searchable: true},
                {"data": "description", orderable: false, searchable: true},
                {"data": "is_active", orderable: true, searchable: false},
                {"data": "actions", orderable: false, searchable: false}
            ];

            showDataTable("{{ $dataTableRoute }}", columns);
        });

        // تبديل حالة البطاقة (مفعّلة / مخفية) من الجدول
        $(document).on('click', '.why-us-toggle', function () {
            $.get($(this).data('url'), function (data) {
                if (!data.success) {
                    return;
                }

                reloadDataTable();
                $.toast({
                    heading: '{{ __('why_us.status') }}',
                    text: data.is_active ? '{{ __('why_us.active') }}' : '{{ __('why_us.inactive') }}',
                    showHideTransition: 'slide',
                    icon: 'success',
                    hideAfter: 2000,
                    position: 'top-right',
                    stack: 1
                });
            });
        });
    </script>
@endsection
