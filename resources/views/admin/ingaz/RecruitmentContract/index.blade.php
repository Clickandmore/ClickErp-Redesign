@extends('admin.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{helperTrans("ingaz.All Recruitment contracts")}}
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_VIEW))
    <div class="row mb-3">
        @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_EXPORT))
        <div class="col-sm-2">
            <a href="{{ route('RecruitmentContract.export.pdf') }}" class="btn btn-danger" target="_blank">
                <i class="fa fa-file-pdf"></i> {{helperTrans("ingaz.export_pdf")}}
            </a>
        </div>
        <div class="col-sm-2">
            <a href="{{ route('RecruitmentContract.export.excel') }}" class="btn btn-success">
                <i class="fa fa-file-excel"></i> {{helperTrans("ingaz.export_excel")}}
            </a>
        </div>
        @endif
    </div>

    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>ID</th>
            <th>{{helperTrans("ingaz.client")}}</th>
            <th>{{helperTrans("ingaz.phone")}}</th>
            <th>{{helperTrans("ingaz.maid")}}</th>
            <th>{{helperTrans("ingaz.status")}}</th>
            <th>{{helperTrans("ingaz.updateStatus")}}</th>
            <th>{{helperTrans("ingaz.make back")}}</th>
            <th>{{helperTrans("ingaz.the contract")}}</th>
            <th>{{helperTrans("ingaz.actions")}}</th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>

    <!-- Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1" role="dialog" aria-labelledby="updateStatusModalLabel"
         aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">تحديث الحالة</h5>
                    <button type="button" class="close btn" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="rowId" name="rowId">
                    <input type="hidden" id="status" name="status">
                    <label for="statusDate">تاريخ الحالة:</label>
                    <input type="date" id="statusDate" required name="statusDate" class="form-control" value="{{date('Y-m-d')}}">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                    <button type="button" class="btn btn-primary" id="saveStatusBtn">حفظ</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="confirmBackModal" tabindex="-1" role="dialog" aria-labelledby="updateStatusModalLabel"
         aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">ارجاع العقد</h5>
                    <button type="button" class="close btn" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="contract_id" name="id">
                    <label for="statusDate">هل انت متأكد من ارجاع العقد ؟</label>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                    <button type="button" class="btn btn-primary" id="confirmBackBtn">حفظ</button>
                </div>
            </div>
        </div>
    </div>

@else
    @include('components.no_permissions')
@endif

@endsection
@section('js')

    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function () {
            let columns = [
                {"data": 'id', name: 'id', orderable: false, searchable: false},
                {"data": "user_id", orderable: true, searchable: true},
                {"data": "phone", orderable: true, searchable: true},
                {"data": "maid_id", orderable: false, searchable: false},
                {"data": "status", orderable: true, searchable: true},
                {"data": "updateStatus", orderable: true, searchable: true},
                {"data": "back", orderable: true, searchable: true},
                {"data": "show_details", orderable: true, searchable: true},
                {"data": "actions", orderable: true, searchable: true},
            ];
            @php
                $status = request()->input('status');
                $url = route('RecruitmentContract.index', ['status' => $status]);
            @endphp
            const url = "{{ $url }}";
            showDataTable(url, columns);
        });
    </script>

    <script>
        $('#updateStatusModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var rowId = button.data('row-id');
            var status = button.data('status');
            var date = button.data('date');
            var modal = $(this);
            modal.find('#rowId').val(rowId);
            modal.find('#status').val(status);
            modal.find('#statusDate').val(date);
        });

        $('#saveStatusBtn').click(function () {
            var rowId = $('#rowId').val();
            var status = $('#status').val();
            var statusDate = $('#statusDate').val();

            $.ajax({
                url: '{{ route('RecruitmentContract.show', ':rowId') }}'.replace(':rowId', rowId),
                method: 'GET',
                data: {
                    status: status,
                    date: statusDate,
                },
                success: function (response) {
                    console.log('AJAX request successful');
                },
                error: function (xhr, status, error) {
                    console.error('AJAX request failed:', error);
                },
                complete: function () {
                    $('#dataTableObject').DataTable().ajax.reload();
                    $('#updateStatusModal').modal('hide');
                }
            });
        });

        $(document).on('click', '.backContract', function () {
            var contractId = $(this).data('id');
            $('#contract_id').val(contractId);
            $('#confirmBackModal').modal('show');
        });

        $('#confirmBackBtn').click(function () {
            var rowId = $('#contract_id').val();
            $.ajax({
                url: '{{ route('RecruitmentContract.back', ':rowId') }}'.replace(':rowId', rowId),
                method: 'GET',
                success: function (response) {
                    console.log('AJAX request successful');
                },
                error: function (xhr, status, error) {
                    console.error('AJAX request failed:', error);
                },
                complete: function () {
                    $('#dataTableObject').DataTable().ajax.reload();
                    $('#confirmBackModal').modal('hide');
                }
            });
        });
    </script>

    <script>
    $(document).ready(function() {
        // Handle complete trip button click
        $(document).on('click', '.complete-trip-btn', function() {
            var tripId = $(this).data('trip-id');
            var button = $(this);

            console.log('Complete trip button clicked for trip ID:', tripId);

            Swal.fire({
                title: '{{ __("admin.confirm") }}',
                text: '{{ __("admin.mark_trip_completed_confirmation") }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '{{ __("admin.confirm") }}',
                cancelButtonText: '{{ __("admin.cancel") }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    console.log('Sending complete trip request for trip ID:', tripId);

                    $.ajax({
                        url: '/admin/travel/complete/' + tripId,
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            console.log('Complete trip response:', response);

                            if (response.success) {
                                // Update the status badge
                                button.closest('td').find('.badge').removeClass('bg-warning').addClass('bg-success').text('{{ __("admin.trip_completed") }}');
                                // Remove the complete button
                                button.remove();
                                // Show success message
                                Swal.fire('{{ __("admin.success") }}', response.message, 'success');
                                // Reload the table to reflect the new contract status
                                $('#dataTableObject').DataTable().ajax.reload(null, false);

                                // If we're on the tazker status page, redirect to under warranty page
                                if (window.location.href.includes('status=tazker')) {
                                    setTimeout(function() {
                                        window.location.href = '{{ route("RecruitmentContract.index", ["status" => "under_warranty"]) }}';
                                    }, 1500);
                                }
                            } else {
                                Swal.fire('{{ __("admin.error") }}', response.message, 'error');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Complete trip error:', {
                                status: status,
                                error: error,
                                response: xhr.responseText
                            });
                            Swal.fire('{{ __("admin.error") }}', '{{ __("admin.something_went_wrong") }}', 'error');
                        }
                    });
                }
            });
        });
    });
    </script>
@endsection
