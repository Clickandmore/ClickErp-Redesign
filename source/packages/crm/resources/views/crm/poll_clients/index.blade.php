@extends('click.layout.indexs.index')
@section('style')
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/dataTables.min.css">
    <style>

    </style>
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')

    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead class="thead-inverse">
        <tr>
            <th>{{ __('crm.SL') }}</th>
            <th>{{ __("crm.name") }} </th>
            <th>{{ __("crm.phone") }} </th>

            <th>{{ __('crm.show-sent-polls') }}</th>
            {{--                            <th>{{ __('crm.actions') }}</th>--}}
        </tr>
        </thead>
        <tbody>

        </tbody>

    </table>


    <div class="modal fade" id="sendPollModal" tabindex="-1" role="dialog" aria-labelledby="sendPollModalLabel"
         aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sendPollModalLabel">{{__('crm.send-poll')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="sendPollForm" action="{{url('crm/sendPoll/store')}}" method="POST">
                    <div class="modal-body">
                        @csrf
                        <input type="hidden" id="userIdInput" name="user_id">
                        <label>اختار الاستبيان</label>
                        <select name="poll_id" class="form-control">
                            @foreach($polls as $poll)
                                <option value="{{$poll->id}}">{{$poll->title}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                                data-dismiss="modal">{{__('buttons.close')}}</button>
                        <button type="submit" class="btn btn-success">{{__('crm.share')}} <i
                                class="fab fa-whatsapp"></i></button>
                    </div>
                </form>

            </div>
        </div>
    </div>

@endsection
@section('js')
    <script>
        $(document).ready(function () {
            $(document).on('click', '.send-poll-btn', function (e) {
                var userId = $(this).data('user-id');
                $('#userIdInput').val(userId);
            });
        });
    </script>

    <script src="{{ asset('admin') }}/assets/js/bundle/dataTables.bundle.js"></script>

    <script>
        $(document).ready(function () {
            let columns = [{
                "data": 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },
                {
                    "data": "name",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "phone",
                    orderable: false,
                    searchable: false
                }, {
                    "data": "show-sent-polls",
                    orderable: false,
                    searchable: false
                }
                // ,
                // {
                //     "data": "actions",
                //     orderable: false,
                //     searchable: false
                // }
            ];
            showDataTable("{{ $dataTableRoute }}", columns);
        });
    </script>
    <script>
        $(document).on('click', '.update-status', function (e) {
            var purchaseId = $(this).data('id');
            var url = "{{ route('crm.clients.show', ':id') }}";
            url = url.replace(':id', purchaseId);
            $.ajax({
                type: 'GET',
                url: url,
                success: function (data) {
                    if (data.success) {
                        $.toast({
                            heading: '{{ __("crm.success") }}',
                            text: '{{ __("crm.successfuly done") }}',
                            showHideTransition: 'slide',
                            icon: 'success',
                            hideAfter: 2000,
                            position: 'top-right',
                            stack: 1
                        });

                        setTimeout(function () {
                            $('#dataTableObject').DataTable().ajax.reload();
                        }, 1500);
                    } else {
                        alert('فشل في تحديث الحالة.');
                    }
                },
                error: function () {
                    alert('حدث خطأ أثناء التحديث.');
                }
            });
        });
    </script>
@endsection
