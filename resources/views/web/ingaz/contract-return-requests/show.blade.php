@extends('web.layout.app')

@section('content')
<main>
    <section class="page_header">
        <h1>تفاصيل طلب الاسترجاع</h1>
        <h6><a href="{{route('web.home')}}">الرئيسية</a> / <a href="{{route('web.contract-return-requests.index')}}">طلبات الاسترجاع</a> / تفاصيل الطلب</h6>
    </section>

    <section class="setting_setion">
        <div class="container">
            <div class="row">
                @include('web.layout.inc.profile_aside')

                <div class="col-lg-8 col-12">
                    <div class="return_request_details_card">
                        <div class="card_header">
                            <h3>تفاصيل طلب الاسترجاع #{{ $returnRequest->id }}</h3>
                            <p>معلومات مفصلة عن طلب الاسترجاع</p>
                        </div>

                        <div class="card_body">
                            <!-- معلومات العقد -->
                            <div class="contract_info">
                                <h5>معلومات العقد</h5>
                                <div class="info_grid">
                                    <div class="info_item">
                                        <span class="label">رقم العقد:</span>
                                        <span class="value">#{{ $returnRequest->contract->id }}</span>
                                    </div>
                                    <div class="info_item">
                                        <span class="label">العاملة:</span>
                                        <span class="value">{{ $returnRequest->contract->maid->name ?? 'غير محدد' }}</span>
                                    </div>
                                    <div class="info_item">
                                        <span class="label">حالة العقد:</span>
                                        <span class="value">{{ $returnRequest->contract->status }}</span>
                                    </div>
                                    <div class="info_item">
                                        <span class="label">تاريخ العقد:</span>
                                        <span class="value">{{ $returnRequest->contract->created_at->format('Y-m-d') }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- معلومات طلب الاسترجاع -->
                            <div class="request_info">
                                <h5>معلومات طلب الاسترجاع</h5>
                                <div class="info_grid">
                                    <div class="info_item">
                                        <span class="label">رقم الطلب:</span>
                                        <span class="value">#{{ $returnRequest->id }}</span>
                                    </div>
                                    <div class="info_item">
                                        <span class="label">حالة الطلب:</span>
                                        <span class="value">{!! $returnRequest->status_badge !!}</span>
                                    </div>
                                    <div class="info_item">
                                        <span class="label">تاريخ الطلب:</span>
                                        <span class="value">{{ $returnRequest->created_at->format('Y-m-d H:i') }}</span>
                                    </div>
                                    <div class="info_item">
                                        <span class="label">آخر تحديث:</span>
                                        <span class="value">{{ $returnRequest->updated_at->format('Y-m-d H:i') }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- ملاحظات الطلب -->
                            <div class="notes_section">
                                <h5>ملاحظات الطلب</h5>
                                <div class="notes_content">
                                    <p>{{ $returnRequest->notes }}</p>
                                </div>
                            </div>

                            <!-- رد الإدارة -->
                            @if($returnRequest->admin_notes)
                            <div class="admin_response_section">
                                <h5>رد الإدارة</h5>
                                <div class="response_content">
                                    <p>{{ $returnRequest->admin_notes }}</p>
                                    <div class="response_meta">
                                        <small>
                                            <i class="fa-light fa-user me-1"></i>
                                            تم الرد بواسطة: {{ $returnRequest->processedBy->name ?? 'غير محدد' }}
                                        </small>
                                        <small>
                                            <i class="fa-light fa-calendar me-1"></i>
                                            تاريخ الرد: {{ $returnRequest->processed_at->format('Y-m-d H:i') }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- الإجراءات -->
                            <div class="actions_section">
                                <a href="{{ route('web.contract-return-requests.index') }}" class="btn btn_secondary">
                                    <i class="fa-light fa-arrow-left me-2"></i>
                                    العودة لطلبات الاسترجاع
                                </a>

                                @if($returnRequest->status === 'pending')
                                <button class="btn btn_danger" onclick="cancelRequest({{ $returnRequest->id }})">
                                    <i class="fa-light fa-times me-2"></i>
                                    إلغاء الطلب
                                </button>
                                @endif
                            </div>
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
                                window.location.href = '{{ route("web.contract-return-requests.index") }}';
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
                        window.location.href = '{{ route("web.contract-return-requests.index") }}';
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
.return_request_details_card {
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

.contract_info, .request_info, .notes_section, .admin_response_section {
    background: #f8fafc;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border: 1px solid #e2e8f0;
}

.contract_info h5, .request_info h5, .notes_section h5, .admin_response_section h5 {
    color: #1e293b;
    font-weight: 700;
    margin-bottom: 1rem;
    font-size: 1.1rem;
}

.info_grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.info_item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: white;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.info_item .label {
    color: #64748b;
    font-weight: 600;
    font-size: 0.9rem;
}

.info_item .value {
    color: #1e293b;
    font-weight: 700;
    font-size: 0.9rem;
}

.notes_content, .response_content {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.notes_content p, .response_content p {
    color: #475569;
    line-height: 1.6;
    margin-bottom: 0;
    font-size: 0.95rem;
}

.response_meta {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.response_meta small {
    color: #94a3b8;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.actions_section {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #e2e8f0;
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

.btn_secondary {
    background: #f1f5f9;
    color: #64748b;
    border: 2px solid #e2e8f0;
}

.btn_secondary:hover {
    background: #e2e8f0;
    color: #475569;
    text-decoration: none;
    transform: translateY(-2px);
}

.btn_danger {
    background: #ef4444;
    color: white;
}

.btn_danger:hover {
    background: #dc2626;
    color: white;
    text-decoration: none;
    transform: translateY(-2px);
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

    .info_grid {
        grid-template-columns: 1fr;
    }

    .actions_section {
        flex-direction: column;
    }

    .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
@endsection
