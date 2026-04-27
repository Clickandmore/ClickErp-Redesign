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
@section('content')
    {!! addButton($createRoute , $addButtonText) !!}

    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>{{ __('finance::finance.SL') }}</th>
            <th>{{ __('finance::finance.title') }}</th>
            <th>{{ __('finance::finance.from_date') }}</th>
            <th>{{ __('finance::finance.to_date') }}</th>
            <th>{{ __('finance::finance.actions') }}</th>
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
            var actionText = status == 1 ? '{{ __("finance::finance.activate") }}' : '{{ __("finance::finance.deactivate") }}';
            // alert([id,status,actionText])

            Swal.fire({
                title: '{{ __("finance::finance.Are you sure?") }}',
                text: '{{ __("finance::finance.You are about to ") }}' + actionText + '{{ __("finance::finance. this item.") }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '{{ __("finance::finance.Yes, ")  }}' + actionText,
                cancelButtonText: '{{ __("finance::finance.Cancel")  }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route("finance.year-status.show", ":id") }}'.replace(':id', id),
                        method: 'PUT',
                        data: {
                            _token: '{{ csrf_token() }}',
                            status: status
                        },
                        success: function (response) {
                            Swal.fire({
                                // title: '{{ __("finance::finance.Updated!") }}',
                                text: '{{ __("finance::finance.Status has been updated successfully.") }}',
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
