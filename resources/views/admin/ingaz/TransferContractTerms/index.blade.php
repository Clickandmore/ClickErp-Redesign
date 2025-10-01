@extends('admin.layout.indexs.index')

@section('title', 'إدارة بنود عقد نقل الكفالة')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <!-- Header -->
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">الرئيسية</a></li>
                            <li class="breadcrumb-item active">بنود عقد نقل الكفالة</li>
                        </ol>
                    </div>
                    <h4 class="page-title">إدارة بنود عقد نقل الكفالة</h4>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">

                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="card-title  m-3">
                        <i class="fas fa-file-contract"></i> بنود العقد
                    </h4>
                    <a href="{{ route('transfer-contract-terms.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> إضافة بند جديد
                    </a>
                </div>
                <div class="table-responsive">
                    <table id="termsTable" class="table table-striped table-bordered dt-responsive nowrap  p-2 mb-3"
                           style="width:100%">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>القسم</th>
                            <th>البند</th>
                            <th>معاينة المحتوى</th>
                            <th>الصفحة</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                        </thead>
                        <tbody>
                        <!-- سيتم تحميل البيانات عبر AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- معاينة المحتوى Modal -->
            <div class="modal fade" id="previewModal" tabindex="-1" role="dialog" aria-labelledby="previewModalLabel"
                 aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="previewModalLabel">معاينة محتوى البند</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="previewContent">
                            <!-- سيتم تحميل المحتوى هنا -->
                        </div>
                    </div>
                </div>
            </div>
            @endsection

            @section('js')
                <script src="{{ asset('admin') }}/assets/js/bundle/dataTables.bundle.js"></script>
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                <script>
                    $(document).ready(function () {
                        // إعداد DataTable
                        var table = $('#termsTable').DataTable({
                            processing: true,
                            serverSide: true,
                            ajax: {
                                url: '{{ route("transfer-contract-terms.index") }}',
                                type: 'GET'
                            },
                            columns: [
                                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                                {data: 'section_info', name: 'section_info', orderable: false},
                                {data: 'term_info', name: 'term_info', orderable: false},
                                {
                                    data: 'content_preview',
                                    name: 'content_preview',
                                    orderable: false,
                                    render: function (data, type, row) {
                                        return '<span class="text-truncate d-inline-block" style="max-width: 200px;">' + data + '</span>';
                                    }
                                },
                                {data: 'page_number', name: 'page_number', orderable: true},
                                {data: 'status', name: 'status', orderable: false, searchable: false},
                                {data: 'actions', name: 'actions', orderable: false, searchable: false}
                            ],
                            language: {
                                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/ar.json'
                            },
                            order: [[4, 'asc']], // ترتيب حسب رقم الصفحة
                            pageLength: 25
                        });

                        // حذف البند
                        window.deleteTerm = function (id) {
                            Swal.fire({
                                title: 'هل أنت متأكد؟',
                                text: 'سيتم حذف هذا البند نهائياً!',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#d33',
                                cancelButtonColor: '#3085d6',
                                confirmButtonText: 'نعم، احذف',
                                cancelButtonText: 'إلغاء'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    $.ajax({
                                        url: '{{ url("admin/transfer-contract-terms") }}/' + id,
                                        type: 'DELETE',
                                        data: {
                                            _token: '{{ csrf_token() }}'
                                        },
                                        success: function (response) {
                                            if (response.success) {
                                                Swal.fire({
                                                    title: 'تم الحذف!',
                                                    text: response.message,
                                                    icon: 'success',
                                                    timer: 2000,
                                                    showConfirmButton: false
                                                });
                                                table.ajax.reload();
                                            }
                                        },
                                        error: function () {
                                            Swal.fire({
                                                title: 'خطأ!',
                                                text: 'حدث خطأ أثناء الحذف',
                                                icon: 'error'
                                            });
                                        }
                                    });
                                }
                            });
                        };

                        // معاينة المحتوى
                        window.previewContent = function (content) {
                            $('#previewContent').html('<div class="p-3">' + content + '</div>');
                            $('#previewModal').modal('show');
                        };
                    });
                </script>
            @endsection

            @section('css')
                <style>
                    .text-truncate {
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    }

                    .btn-group .btn {
                        margin: 0 2px;
                    }

                    .table td {
                        vertical-align: middle;
                    }

                    .badge {
                        font-size: 0.75em;
                    }
                </style>
@endsection
