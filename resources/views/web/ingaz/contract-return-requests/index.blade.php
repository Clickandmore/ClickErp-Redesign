@extends('web.layout.app')

@section('content')
<main>
    <section class="page_header">
        <h1>طلبات استرجاع العقود</h1>
        <h6><a href="{{route('web.home')}}">الرئيسية</a> / طلبات استرجاع العقود</h6>
    </section>

    <section class="setting_setion">
        <div class="container">
            <div class="row">
                @include('web.layout.inc.profile_aside')

                <div class="col-lg-8 col-12">
                    <div class="return_requests_card">
                        <div class="card_header">
                            <h3>طلبات استرجاع العقود</h3>
                            <p>عرض جميع طلبات الاسترجاع الخاصة بك</p>
                        </div>

                        <div class="card_body">
                            @if($returnRequests->count() > 0)
                                <div class="requests_list">
                                    @foreach($returnRequests as $request)
                                    <div class="request_item">
                                        <div class="request_header">
                                            <div class="request_info">
                                                <h5>طلب استرجاع العقد #{{ $request->contract->id }}</h5>
                                                <p>{{ $request->contract->maid->name ?? 'غير محدد' }}</p>
                                                <small>{{ $request->created_at->format('Y-m-d H:i') }}</small>
                                            </div>
                                            <div class="request_status">
                                                {!! $request->status_badge !!}
                                            </div>
                                        </div>

                                        <div class="request_content">
                                            <div class="notes_section">
                                                <h6>ملاحظات الطلب:</h6>
                                                <p>{{ $request->notes }}</p>
                                            </div>

                                            @if($request->admin_notes)
                                            <div class="admin_notes_section">
                                                <h6>رد الإدارة:</h6>
                                                <p>{{ $request->admin_notes }}</p>
                                                <small>تم الرد بواسطة: {{ $request->processedBy->name ?? 'غير محدد' }} في {{ $request->processed_at->format('Y-m-d H:i') }}</small>
                                            </div>
                                            @endif
                                        </div>

                                        <div class="request_actions">
                                            <a href="{{ route('web.contract-return-requests.show', $request->id) }}" class="btn btn_small btn_primary">
                                                <i class="fa-light fa-eye me-1"></i>
                                                عرض التفاصيل
                                            </a>

                                            @if($request->status === 'pending')
                                            <button class="btn btn_small btn_danger" onclick="cancelRequest({{ $request->id }})">
                                                <i class="fa-light fa-times me-1"></i>
                                                إلغاء الطلب
                                            </button>
                                            @endif
                                        </div>
                                    </div>
                                    @endforeach
                                </div>

                                <div class="pagination_wrapper">
                                    {{ $returnRequests->links() }}
                                </div>
                            @else
                                <div class="no_requests">
                                    <div class="no_requests_icon">
                                        <i class="fa-light fa-inbox"></i>
                                    </div>
                                    <h5>لا توجد طلبات استرجاع</h5>
                                    <p>لم تقم بإرسال أي طلبات استرجاع بعد</p>
                                    <a href="{{ route('web.contracts.index') }}" class="btn btn_primary">
                                        <i class="fa-light fa-arrow-left me-2"></i>
                                        العودة للعقود
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection

@section('scripts')
<script>
function cancelRequest(requestId) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'تأكيد الإلغاء',
            text: 'هل أنت متأكد من إلغاء طلب الاسترجاع؟',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'نعم، إلغاء الطلب',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/return-requests/' + requestId,
                    method: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'تم بنجاح',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'حدث خطأ أثناء إلغاء الطلب';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: errorMessage
                        });
                    }
                });
            }
        });
    } else {
        if (confirm('هل أنت متأكد من إلغاء طلب الاسترجاع؟')) {
            $.ajax({
                url: '/return-requests/' + requestId,
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        location.reload();
                    }
                },
                error: function(xhr) {
                    alert('حدث خطأ أثناء إلغاء الطلب');
                }
            });
        }
    }
}
</script>
@endsection

@section('styles')
<style>
.return_requests_card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    border: 1px solid #e2e8f0;
    overflow: hidden;
}

.card_header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.card_header::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 100%;
    height: 100%;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    opacity: 0.3;
}

.card_header h3 {
    position: relative;
    z-index: 2;
    margin-bottom: 0.5rem;
    font-size: 1.75rem;
    font-weight: 700;
}

.card_header p {
    position: relative;
    z-index: 2;
    margin-bottom: 0;
    opacity: 0.9;
    font-size: 1rem;
}

.card_body {
    padding: 2rem;
}

.requests_list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.request_item {
    background: #f8fafc;
    border-radius: 15px;
    padding: 1.5rem;
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
}

.request_item:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.request_header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e2e8f0;
}

.request_info h5 {
    color: #1e293b;
    font-weight: 700;
    margin-bottom: 0.25rem;
    font-size: 1.1rem;
}

.request_info p {
    color: #64748b;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.request_info small {
    color: #94a3b8;
    font-size: 0.8rem;
}

.request_content {
    margin-bottom: 1rem;
}

.notes_section, .admin_notes_section {
    margin-bottom: 1rem;
}

.notes_section h6, .admin_notes_section h6 {
    color: #1e293b;
    font-weight: 600;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.notes_section p, .admin_notes_section p {
    color: #475569;
    line-height: 1.6;
    font-size: 0.9rem;
    background: white;
    padding: 0.75rem;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.admin_notes_section small {
    color: #94a3b8;
    font-size: 0.75rem;
    display: block;
    margin-top: 0.5rem;
}

.request_actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.btn_small {
    padding: 0.5rem 1rem;
    font-size: 0.85rem;
}

.btn_danger {
    background: #ef4444;
    color: white;
}

.btn_danger:hover {
    background: #dc2626;
    color: white;
    text-decoration: none;
}

.pagination_wrapper {
    margin-top: 2rem;
    display: flex;
    justify-content: center;
}

.no_requests {
    text-align: center;
    padding: 3rem 2rem;
}

.no_requests_icon i {
    font-size: 4rem;
    color: #cbd5e1;
    margin-bottom: 1.5rem;
}

.no_requests h5 {
    color: #475569;
    margin-bottom: 0.75rem;
    font-weight: 700;
    font-size: 1.25rem;
}

.no_requests p {
    color: #64748b;
    margin-bottom: 2rem;
    font-size: 1rem;
}

.btn {
    padding: 0.875rem 2rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border: none;
    cursor: pointer;
}

.btn_primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.btn_primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    color: white;
    text-decoration: none;
}

@media (max-width: 768px) {
    .card_header {
        padding: 1.5rem;
    }

    .card_header h3 {
        font-size: 1.5rem;
    }

    .card_body {
        padding: 1.5rem;
    }

    .request_header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }

    .request_actions {
        flex-direction: column;
    }

    .btn_small {
        width: 100%;
        justify-content: center;
    }
}
</style>
@endsection
