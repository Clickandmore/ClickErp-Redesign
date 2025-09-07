@extends('admin.layout.indexs.index')

@section('title')
    تفاصيل طلب الاسترجاع #{{ $returnRequest->id }}
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">تفاصيل طلب الاسترجاع #{{ $returnRequest->id }}</h4>
                        <div class="d-flex gap-2">
                            <a href="{{ route('contract-return-requests.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i>
                                العودة للقائمة
                            </a>
                            @if($returnRequest->status === 'pending')
                            <button class="btn btn-success" onclick="processRequest('approved')">
                                <i class="fa fa-check"></i>
                                موافقة
                            </button>
                            <button class="btn btn-danger" onclick="processRequest('rejected')">
                                <i class="fa fa-times"></i>
                                رفض
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- معلومات الطلب -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">معلومات الطلب</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>رقم الطلب:</strong></td>
                                            <td>#{{ $returnRequest->id }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>الحالة:</strong></td>
                                            <td>{!! $returnRequest->status_badge !!}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>تاريخ الطلب:</strong></td>
                                            <td>{{ $returnRequest->created_at->format('Y-m-d H:i') }}</td>
                                        </tr>
                                        @if($returnRequest->processed_at)
                                        <tr>
                                            <td><strong>تاريخ المعالجة:</strong></td>
                                            <td>{{ $returnRequest->processed_at->format('Y-m-d H:i') }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>تمت المعالجة بواسطة:</strong></td>
                                            <td>{{ $returnRequest->processedBy->name ?? 'غير محدد' }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- معلومات العميل -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">معلومات العميل</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>اسم العميل:</strong></td>
                                            <td>{{ $returnRequest->user->name }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>البريد الإلكتروني:</strong></td>
                                            <td>{{ $returnRequest->user->email }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>رقم الهاتف:</strong></td>
                                            <td>{{ $returnRequest->user->phone_code }}{{ $returnRequest->user->phone }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <!-- معلومات العقد -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">معلومات العقد</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>رقم العقد:</strong></td>
                                            <td>
                                                <a href="{{ route('RecruitmentContract.show', $returnRequest->contract->id) }}" target="_blank">
                                                    #{{ $returnRequest->contract->id }}
                                                </a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>حالة العقد:</strong></td>
                                            <td>{{ $returnRequest->contract->status }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>تاريخ العقد:</strong></td>
                                            <td>{{ $returnRequest->contract->created_at->format('Y-m-d') }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>اسم العاملة:</strong></td>
                                            <td>{{ $returnRequest->contract->maid->name ?? 'غير محدد' }}</td>
                                        </tr>
                                        @if($returnRequest->contract->maid)
                                        <tr>
                                            <td><strong>رقم جواز السفر:</strong></td>
                                            <td>{{ $returnRequest->contract->maid->passport_num ?? 'غير محدد' }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- ملاحظات الطلب -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">ملاحظات الطلب</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        {{ $returnRequest->notes }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($returnRequest->admin_notes)
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">رد الإدارة</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-{{ $returnRequest->status === 'approved' ? 'success' : 'danger' }}">
                                        {{ $returnRequest->admin_notes }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
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
                <h5 class="modal-title" id="processModalTitle">معالجة طلب الاسترجاع</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="process-form">
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

@section('scripts')
<script>
function processRequest(status) {
    console.log('Processing request with status:', status);
    $('#process-status').val(status);

    const statusText = status === 'approved' ? 'موافقة' : 'رفض';
    $('#processModalTitle').text(`${statusText} على طلب الاسترجاع`);

    $('#processModal').modal('show');
}

function submitProcess() {
    const status = $('#process-status').val();
    const adminNotes = $('#admin-notes').val();

    if (!adminNotes.trim()) {
        alert('يرجى كتابة ملاحظات الإدارة');
        return;
    }

    $.ajax({
        url: '{{ route("contract-return-requests.process", $returnRequest->id) }}',
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
                    alert(response.message);
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
                alert(errorMessage);
            }
        }
    });
}
</script>
@endsection

@section('styles')
<style>
.card {
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px 10px 0 0 !important;
}

.table-borderless td {
    padding: 0.5rem 0;
    border: none;
}

.alert {
    border-radius: 8px;
    border: none;
}

.btn {
    border-radius: 8px;
    font-weight: 600;
}

.table-borderless strong {
    color: #495057;
    min-width: 120px;
    display: inline-block;
}
</style>
@endsection
