@extends('admin.layout.indexs.index')

@section('page-title')
    المدونة والمقالات
@endsection

@section('content')
    @if (@AdminGuardHasAnyPerm([\App\PermKeys::BLOGS_VIEW, \App\PermKeys::BLOGS_CONTROL]))
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            @if (@AdminGuardHasAnyPerm([\App\PermKeys::BLOGS_CREATE, \App\PermKeys::BLOGS_CONTROL]))
                {!! addButton(route('blogs.create'), 'مقال') !!}
            @endif

            <div class="d-flex gap-2">
                <input type="text" id="blog-search-title" class="form-control" placeholder="البحث حسب العنوان">
                <button type="button" id="blog-search-btn" class="btn btn-primary">بحث</button>
                <button type="button" id="blog-reset-btn" class="btn btn-outline-secondary">إعادة</button>
            </div>
        </div>

        <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
            <thead>
                <tr>
                    <th>#</th>
                    <th>الصورة</th>
                    <th>المقال</th>
                    <th>بيانات</th>
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
                { data: 'title_block', name: 'slug', orderable: false, searchable: true },
                { data: 'meta_block', name: 'category', orderable: false, searchable: false },
                { data: 'sort_order', name: 'sort_order', orderable: true, searchable: false },
                { data: 'status_toggle', name: 'is_active', orderable: false, searchable: false },
                { data: 'actions', orderable: false, searchable: false }
            ];

            let table = $('#dataTableObject').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('blogs.index') }}",
                    data: function (data) {
                        data.search_title = $('#blog-search-title').val();
                    }
                },
                columns: columns
            });

            $('#blog-search-btn').on('click', function () {
                table.ajax.reload();
            });

            $('#blog-search-title').on('keyup', function (event) {
                if (event.key === 'Enter') {
                    table.ajax.reload();
                }
            });

            $('#blog-reset-btn').on('click', function () {
                $('#blog-search-title').val('');
                table.ajax.reload();
            });

            $(document).on('click', '.blog-toggle-status', function () {
                $.post($(this).data('route'), {_token: "{{ csrf_token() }}"}, function () {
                    table.ajax.reload(null, false);
                });
            });
        });
    </script>
@endsection
