@extends('admin.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
    <style>
        .maids-grid {
            max-height: 400px;
            overflow-y: auto;
        }

        .maid-card {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fff;
        }

        .maid-card:hover {
            border-color: #007bff;
            box-shadow: 0 4px 8px rgba(0,123,255,0.1);
            transform: translateY(-2px);
        }

        .maid-card.selected {
            border-color: #28a745;
            background-color: #f8fff9;
            box-shadow: 0 4px 8px rgba(40,167,69,0.2);
        }

        .maid-card .maid-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e9ecef;
        }

        .maid-card .maid-info h6 {
            margin-bottom: 5px;
            color: #333;
        }

        .maid-card .maid-info p {
            margin-bottom: 3px;
            font-size: 0.9em;
            color: #666;
        }

        .maid-card .maid-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #28a745;
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        .maid-card .maid-status {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
        }

        .maid-card .maid-status.available {
            background: #d4edda;
            color: #155724;
        }

        .maid-card .maid-status.reserved {
            background: #fff3cd;
            color: #856404;
        }

        .filters-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .search-highlight {
            background-color: #fff3cd;
            padding: 1px 3px;
            border-radius: 3px;
        }
    </style>
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

            // التحقق من وجود معرف العقد
            if (!rowId) {
                Swal.fire('خطأ', 'معرف العقد غير صحيح', 'error');
                return;
            }

            $.ajax({
                url: '{{ route('RecruitmentContract.show', ':rowId') }}'.replace(':rowId', rowId),
                method: 'GET',
                data: {
                    status: status,
                    date: statusDate,
                },
                success: function (response) {
                    console.log('AJAX request successful');
                    if (response.success) {
                        Swal.fire('نجح', response.message, 'success');
                    } else {
                        Swal.fire('خطأ', response.message, 'error');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX request failed:', error);
                    var errorMessage = 'حدث خطأ أثناء تحديث حالة العقد';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    Swal.fire('خطأ', errorMessage, 'error');
                },
                complete: function () {
                    $('#dataTableObject').DataTable().ajax.reload();
                    $('#updateStatusModal').modal('hide');
                }
            });
        });

        $(document).on('click', '.backContract', function () {
            var contractId = $(this).data('row-id'); // تغيير من data('id') إلى data('row-id')
            $('#contract_id').val(contractId);
            $('#confirmBackModal').modal('show');
        });

        $('#confirmBackBtn').click(function () {
            var rowId = $('#contract_id').val();

            // التحقق من وجود معرف العقد
            if (!rowId) {
                Swal.fire('خطأ', 'معرف العقد غير صحيح', 'error');
                return;
            }

            $.ajax({
                url: '{{ route('RecruitmentContract.back', ':rowId') }}'.replace(':rowId', rowId),
                method: 'GET',
                success: function (response) {
                    console.log('AJAX request successful');
                    if (response.success) {
                        Swal.fire('نجح', response.message, 'success');
                    } else {
                        Swal.fire('خطأ', response.message, 'error');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX request failed:', error);
                    var errorMessage = 'حدث خطأ أثناء إرجاع العقد';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    Swal.fire('خطأ', errorMessage, 'error');
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
