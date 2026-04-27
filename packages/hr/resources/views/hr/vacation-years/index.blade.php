@extends('click.layout.indexs.index')
@section("style")
    {{--    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">--}}
    {{--    <link rel="stylesheet" href="{{asset("admin")}}/assets/erp/tree_collapse/tree-co.css?v=2">--}}
    <link rel="stylesheet" href="{{asset("admin")}}/assets/vendor/tree_collapse/tree-co.css">

@endsection
@section('page-title')
    {{$bladeTitle}}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{$bladeTitle}}</li>
@endsection
{{--@section('up-card')--}}
{{--@endsection--}}
@section('content')
    @include('hr::hr._nav_tabs')

    {!! addButton($createRoute , $addButtonText) !!}

    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>{{ __('hr::hr.SL') }}</th>
            <th>{{ __('hr::hr.title') }}</th>
            <th>{{ __('hr::hr.from_date') }}</th>
            <th>{{ __('hr::hr.to_date') }}</th>
            <th>{{ __('hr::hr.actions') }}</th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>
@endsection
@section('js')
    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>

    <script>
        $(document).ready(function () {
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {"data": "title", orderable: false, searchable: true},
                {"data": "from_date", orderable: false, searchable: true},
                {"data": "to_date", orderable: false, searchable: true},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{ $dataTableRoute }}", columns);
        });

        $(document).on('click', '.status-toggle', function () {
            var id = $(this).data('id');
            var status = $(this).data('status');
            var actionText = status == 1 ? '{{ __("hr::hr.activate") }}' : '{{ __("hr::hr.deactivate") }}';
            // alert([id,status,actionText])

            Swal.fire({
                title: '{{ __("hr::hr.Are you sure?") }}',
                text: '{{ __("hr::hr.You are about to ") }}' + actionText + '{{ __("hr::hr. this item.") }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '{{ __("hr::hr.Yes, ")  }}' + actionText,
                cancelButtonText: '{{ __("hr::hr.Cancel")  }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route("year-status.show", ":id") }}'.replace(':id', id),
                        method: 'PUT',
                        data: {
                            _token: '{{ csrf_token() }}',
                            status: status
                        },
                        success: function (response) {
                            Swal.fire({
                                // title: '{{ __("hr::hr.Updated!") }}',
                                text: '{{ __("hr::hr.Status has been updated successfully.") }}',
                                icon: 'success',
                                timer: 3000,
                                showConfirmButton: false
                            });
                            $('#dataTableObject').DataTable().ajax.reload();
                        },
                        error: function (errorObj, errorText, errorThrown) {
                            console.log(500);
                            handleErrorResponce(errorObj);
                        }
                    });
                }
            });
        });

    </script>
    @if(request()->has('year'))
        @if(request()->has('year'))
            <script>
                window.onload = function() {
                    var buttons = document.getElementsByClassName('addButton');
                    for (var i = 0; i < buttons.length; i++) {
                        buttons[i].click();
                    }
                }
            </script>
        @endif
    @endif

@endsection
