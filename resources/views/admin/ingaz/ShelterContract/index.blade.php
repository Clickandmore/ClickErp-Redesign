@extends('admin.layout.indexs.index')

@section("style")
<!-- DataTables CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
@endsection

@section('page-title')
{{helperTrans("ingaz.All Shelter contracts")}}
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::SHELTER_CONTRACT_VIEW))
    <div class="card">
        <div class="card-body">
            <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>{{helperTrans("ingaz.client")}}</th>
                        <th>{{helperTrans("ingaz.phone")}}</th>
                        <th>{{helperTrans("ingaz.maid")}}</th>
                        <th>{{helperTrans("ingaz.status")}}</th>
                        <th>{{helperTrans("ingaz.make back")}}</th>
                        <th>{{helperTrans("ingaz.actions")}}</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>

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
                    <input type="date" id="statusDate" required name="statusDate" class="form-control"
                        value="{{date('Y-m-d')}}">
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
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>

<script>
$(document).ready(function () {
    let columns = [
        {"data": 'id', name: 'id', orderable: false, searchable: false},
        {"data": "user_id", orderable: true, searchable: true},
        {"data": "phone", orderable: true, searchable: true},
        {"data": "maid_id", orderable: false, searchable: false},
        {"data": "status", orderable: true, searchable: true},
        {"data": "back", orderable: true, searchable: true},
        {"data": "actions", orderable: true, searchable: true},
    ];

    $('#dataTableObject').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('ShelterContract.index') }}",
            type: 'GET',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        },
        columns: columns,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json'
        }
    });
});

$('#updateStatusModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var rowId = button.data('row-id');
    var status = button.data('status');
    var modal = $(this);
    modal.find('#rowId').val(rowId);
    modal.find('#status').val(status);
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
@endsection
