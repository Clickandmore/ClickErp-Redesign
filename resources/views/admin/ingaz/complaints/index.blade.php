@extends('admin.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
    <style>
        .truncate-text {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            direction: rtl;
        }
        .show-all-btn {
            background: none;
            border: none;
            color: #007bff;
            cursor: pointer;
            padding: 0;
            font-size: 12px;
            margin-right: 5px;
        }
        .show-all-btn:hover {
            text-decoration: underline;
        }
        .modal-content {
            max-height: 80vh;
            overflow-y: auto;
        }
        .full-text {
            white-space: pre-wrap;
            word-wrap: break-word;
            direction: rtl;
        }
        .text-container {
            display: flex;
            align-items: center;
            gap: 5px;
            direction: rtl;
        }
    </style>
@endsection
@section('page-title')
    {{helperTrans("ingaz.complaints")}}
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_VIEW))

    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>#</th>
            <th>{{helperTrans("ingaz.name")}}</th>
            <th>{{helperTrans("ingaz.subject")}}</th>
            <th>{{helperTrans("ingaz.message")}}</th>
            <th>{{helperTrans("ingaz.response")}}</th>
            <th>{{helperTrans("ingaz.status")}}</th>
            <th>{{helperTrans("ingaz.actions")}}</th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>

@else
    @include('components.no_permissions')
@endif

<!-- Response Modal -->
<div class="modal fade" id="responseModal" tabindex="-1" aria-labelledby="responseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="responseModalLabel">{{helperTrans("ingaz.response_to_complaint")}}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="responseForm">
                    @csrf
                    <input type="hidden" name="complaint_id" id="complaint_id">
                    <div class="mb-3">
                        <label for="response" class="form-label">{{helperTrans("ingaz.response")}}</label>
                        <textarea class="form-control" id="response" name="response" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">{{helperTrans("ingaz.status")}}</label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="pending">{{helperTrans("ingaz.pending")}}</option>
                            <option value="resolved">{{helperTrans("ingaz.resolved")}}</option>
                            <option value="rejected">{{helperTrans("ingaz.rejected")}}</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{helperTrans("ingaz.close")}}</button>
                <button type="button" class="btn btn-primary" id="saveResponse">{{helperTrans("ingaz.save")}}</button>
            </div>
        </div>
    </div>
</div>

<!-- Full Text Modal -->
<div class="modal fade" id="fullTextModal" tabindex="-1" aria-labelledby="fullTextModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fullTextModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="fullTextContent" class="full-text"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{helperTrans("ingaz.close")}}</button>
            </div>
        </div>
    </div>
</div>

@endsection
@section('js')

    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function () {
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {"data": "user_id", orderable: true, searchable: true},
                {"data": "address", orderable: true, searchable: true,
                    render: function(data) {
                        if (!data) return '';
                        let html = '<div class="text-container">';
                        html += '<div class="truncate-text">' + data + '</div>';
                        if (data.length > 30) {
                            html += '<button class="show-all-btn" title="عرض الكل" onclick="showFullText(\'subject\', \'' + data.replace(/'/g, "\\'").replace(/"/g, '&quot;') + '\')"><i class="fa fa-eye"></i></button>';
                        }
                        html += '</div>';
                        return html;
                    }
                },
                {"data": "message", orderable: true, searchable: true,
                    render: function(data) {
                        if (!data) return '';
                        let html = '<div class="text-container">';
                        html += '<div class="truncate-text">' + data + '</div>';
                        if (data.length > 30) {
                            html += '<button class="show-all-btn" title="عرض الكل" onclick="showFullText(\'message\', \'' + data.replace(/'/g, "\\'").replace(/"/g, '&quot;') + '\')"><i class="fa fa-eye"></i></button>';
                        }
                        html += '</div>';
                        return html;
                    }
                },
                {"data": "response", orderable: true, searchable: true},
                {"data": "status", orderable: true, searchable: true},
                {"data": "actions", orderable: false, searchable: false}
            ];

            $('#dataTableObject').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{route('complaints.index')}}",
                    type: 'GET',
                    error: function(xhr, error, thrown) {
                        console.log('DataTables error:', error, thrown);
                        toastr.error('حدث خطأ أثناء تحميل البيانات');
                    }
                },
                columns: columns,
                language: {
                    url: "{{asset('admin/assets/js/bundle/ar.json')}}"
                },
                order: [[0, 'desc']],
                createdRow: function(row, data, dataIndex) {
                    $(row).find('td').css('direction', 'rtl');
                }
            });

            // Handle response button click
            $(document).on('click', '.response-btn', function() {
                var complaintId = $(this).data('id');
                var response = $(this).data('response');
                var status = $(this).data('status');

                $('#complaint_id').val(complaintId);
                $('#response').val(response);
                $('#status').val(status);

                $('#responseModal').modal('show');
            });

            // Handle save response
            $('#saveResponse').click(function() {
                var formData = $('#responseForm').serialize();

                $.ajax({
                    url: "{{route('complaints.response')}}",
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if(response.code === 200) {
                            $('#responseModal').modal('hide');
                            toastr.success(response.message);
                            $('#dataTableObject').DataTable().ajax.reload(null, false);
                            // Reset the form
                            $('#responseForm')[0].reset();
                        } else {
                            toastr.error(response.message || 'حدث خطأ أثناء حفظ الرد');
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON.message || 'حدث خطأ أثناء حفظ الرد');
                    }
                });
            });
        });

        function showFullText(type, text) {
            $('#fullTextModalLabel').text(type === 'subject' ? 'الموضوع' : 'الرسالة');
            $('#fullTextContent').text(text);
            $('#fullTextModal').modal('show');
        }
    </script>
@endsection
