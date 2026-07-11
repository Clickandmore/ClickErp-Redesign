@extends('admin.layout.indexs.index')

@section('page-title')
    العروض
@endsection

@section('content')
    @if (@AdminGuardHasAnyPerm([\App\PermKeys::OFFERS_VIEW, \App\PermKeys::OFFERS_CONTROL]))
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            @if (@AdminGuardHasAnyPerm([\App\PermKeys::OFFERS_CREATE, \App\PermKeys::OFFERS_CONTROL]))
                {!! addButton(route('offers.create'), 'عرض') !!}
            @endif

            <div class="d-flex gap-2">
                <input type="text" id="offer-search-title" class="form-control" placeholder="البحث حسب العنوان">
                <button type="button" id="offer-search-btn" class="btn btn-primary">بحث</button>
                <button type="button" id="offer-reset-btn" class="btn btn-outline-secondary">إعادة</button>
            </div>
        </div>

        <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
            <thead>
                <tr>
                    <th>#</th>
                    <th>الصورة</th>
                    <th>العرض</th>
                    <th>النوع</th>
                    <th>الأسعار</th>
                    <th>الترتيب</th>
                    <th>الحالة</th>
                    <th>{{ helperTrans('ingaz.actions') }}</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    @else
        @include('components.no_permissions')
    @endif
@endsection

@section('js')
    <script>
        $(document).ready(function () {
            let columns = [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'image', name: 'image', orderable: false, searchable: false },
                { data: 'title_block', name: 'title', orderable: true, searchable: true },
                { data: 'type_block', name: 'type', orderable: false, searchable: false },
                { data: 'prices', name: 'price_after_discount', orderable: false, searchable: false },
                { data: 'sort_order', name: 'sort_order', orderable: true, searchable: false },
                { data: 'status_toggle', name: 'is_active', orderable: false, searchable: false },
                { data: 'actions', orderable: false, searchable: false }
            ];

            let table = $('#dataTableObject').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('offers.index') }}",
                    data: function (data) {
                        data.search_title = $('#offer-search-title').val();
                    }
                },
                columns: columns
            });

            $('#offer-search-btn').on('click', function () {
                table.ajax.reload();
            });

            $('#offer-search-title').on('keyup', function (event) {
                if (event.key === 'Enter') {
                    table.ajax.reload();
                }
            });

            $('#offer-reset-btn').on('click', function () {
                $('#offer-search-title').val('');
                table.ajax.reload();
            });

            $(document).on('click', '.offer-toggle-status', function () {
                $.post($(this).data('route'), {_token: "{{ csrf_token() }}"}, function () {
                    table.ajax.reload(null, false);
                });
            });
        });
    </script>
@endsection
