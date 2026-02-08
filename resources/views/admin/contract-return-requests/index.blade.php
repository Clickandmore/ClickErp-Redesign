@extends('admin.layout.indexs.index')

@section('title')
    طلبات استرجاع العقود
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center gap-3">
                            <h4 class="card-title mb-0">طلبات استرجاع العقود</h4>
                            <div class="d-flex gap-2">
                                <button class="btn btn-info btn-sm text-light" onclick="loadStatistics()">
                                    <i class="fa fa-chart-bar"></i>
                                    الإحصائيات
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Statistics Cards -->
                        <div class="row mb-4" id="statistics-cards" style="display: none;">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h5 class="card-title">إجمالي الطلبات</h5>
                                                <h3 id="total-requests">0</h3>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fa fa-list fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h5 class="card-title">قيد الانتظار</h5>
                                                <h3 id="pending-requests">0</h3>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fa fa-clock fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h5 class="card-title">تمت الموافقة</h5>
                                                <h3 id="approved-requests">0</h3>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fa fa-check fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-danger text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h5 class="card-title">مرفوض</h5>
                                                <h3 id="rejected-requests">0</h3>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fa fa-times fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <select class="form-select" id="status-filter">
                                    <option value="">جميع الحالات</option>
                                    <option value="pending">قيد الانتظار</option>
                                    <option value="approved">تمت الموافقة</option>
                                    <option value="rejected">مرفوض</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="text" class="form-control" id="search-input" placeholder="البحث...">
                            </div>
                        </div>

                        <!-- Table -->
                        <table class="table table-striped table-style" id="return-requests-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>رقم العقد</th>
                                    <th>اسم العميل</th>
                                    <th>اسم العاملة</th>
                                    <th>الملاحظات</th>
                                    <th>الحالة</th>
                                    <th>تاريخ الطلب</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($returnRequests as $request)
                                    <tr>
                                        <td>{{ $request->id }}</td>
                                        <td>
                                            <a href="{{ route('RecruitmentContract.show', $request->contract->id) }}"
                                                target="_blank">
                                                #{{ $request->contract->id }}
                                            </a>
                                        </td>
                                        <td>{{ $request->user->name }}</td>
                                        <td>{{ $request->contract->maid->name ?? 'غير محدد' }}</td>
                                        <td>
                                            <span class="text-truncate d-inline-block" style="max-width: 200px;"
                                                title="{{ $request->notes }}">
                                                {{ Str::limit($request->notes, 50) }}
                                            </span>
                                        </td>
                                        <td data-search="{{ $request->status }}">{!! $request->status_badge !!}</td>
                                        <td>{{ $request->created_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            <div class="btn-group gap-2" role="group">
                                                <a href="{{ route('contract-return-requests.show', $request->id) }}"
                                                    class="btn btn-sm btn-info" title="عرض التفاصيل">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                @if ($request->status === 'pending')
                                                    <button class="btn btn-sm btn-success"
                                                        onclick="processRequest({{ $request->id }}, 'approved')"
                                                        title="موافقة">
                                                        <i class="fa fa-check"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger"
                                                        onclick="processRequest({{ $request->id }}, 'rejected')"
                                                        title="رفض">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">لا توجد طلبات استرجاع</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center">
                            {{ $returnRequests->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Process Request Modal -->
    <div class="modal fade" id="processModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">معالجة طلب الاسترجاع</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="process-form">
                        <input type="hidden" id="request-id">
                        <input type="hidden" id="process-status">

                        <div class="mb-3">
                            <label for="admin-notes" class="form-label">ملاحظات الإدارة</label>
                            <textarea class="form-control" id="admin-notes" rows="4" required
                                placeholder="اكتب رد الإدارة على طلب الاسترجاع..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-primary" onclick="submitProcess()">إرسال</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#return-requests-table').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Arabic.json"
                },
                "pageLength": 25,
                "order": [
                    [0, "desc"]
                ]
            });

            // Status filter
            $('#status-filter').on('change', function() {
                const status = $(this).val();
                const table = $('#return-requests-table').DataTable();

                if (status) {
                    table.column(5).search(status).draw();
                } else {
                    table.column(5).search('').draw();
                }
            });

            // Search functionality
            $('#search-input').on('keyup', function() {
                const table = $('#return-requests-table').DataTable();
                table.search(this.value).draw();
            });
        });

        function loadStatistics() {
            $.get('{{ route('contract-return-requests.statistics') }}')
                .done(function(data) {
                    $('#total-requests').text(data.total);
                    $('#pending-requests').text(data.pending);
                    $('#approved-requests').text(data.approved);
                    $('#rejected-requests').text(data.rejected);
                    $('#statistics-cards').show();
                })
                .fail(function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'حدث خطأ',
                        text: 'حدث خطأ أثناء تحميل الإحصائيات',
                    })
                    // alert('حدث خطأ أثناء تحميل الإحصائيات');
                });
        }

        function processRequest(requestId, status) {
            $('#request-id').val(requestId);
            $('#process-status').val(status);

            const statusText = status === 'approved' ? 'موافقة' : 'رفض';
            $('#processModal .modal-title').text(`${statusText} على طلب الاسترجاع`);

            $('#processModal').modal('show');
        }

        function submitProcess() {
            const requestId = $('#request-id').val();
            const status = $('#process-status').val();
            const adminNotes = $('#admin-notes').val();

            if (!adminNotes.trim()) {
                Swal.fire({
                    icon: 'error',
                    title: 'حدث خطأ',
                    text: 'يرجى كتابة ملاحظات الإدارة',
                })
                // alert('يرجى كتابة ملاحظات الإدارة');
                return;
            }

            $.ajax({
                url: `{{ url('admin/contract-return-requests') }}/${requestId}/process`,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    status: status,
                    admin_notes: adminNotes
                },
                success: function(response) {
                    if (response.success) {
                        $('#processModal').modal('hide');
                        $('#admin-notes').val('');

                        // Show success message
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'تم بنجاح',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'حدث خطأ',
                                text: response.message,
                            })
                            // alert(response.message);
                            location.reload();
                        }
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'حدث خطأ أثناء معالجة الطلب';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: errorMessage
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'حدث خطأ',
                            text: errorMessage,
                        })
                        // alert(errorMessage);
                    }
                }
            });
        }
    </script>
@endsection

@section('style')
    <style>
        .card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }

        .btn-group .btn {
            margin: 0 2px;
        }

        .table th {
            background-color: #f8f9fa;
            border-top: none;
            font-weight: 600;
        }

        .text-truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #statistics-cards .card {
            transition: transform 0.3s ease;
        }

        #statistics-cards .card:hover {
            transform: translateY(-5px);
        }

        .card-header h4.card-title {
            color: #f8f9fa !important;
        }
    </style>
@endsection
