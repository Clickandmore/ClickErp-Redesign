@extends('web.layout.app')

@section('content')
<main>
    <section class="page_header">
        <h1>طلب استرجاع العقد</h1>
        <h6><a href="{{route('web.home')}}">الرئيسية</a> / <a href="{{route('web.contracts.index')}}">عقودي</a> / طلب استرجاع العقد</h6>
    </section>

    <section class="setting_setion">
        <div class="container">
            <div class="row">
                @include('web.layout.inc.profile_aside')

                <div class="col-lg-8 col-12">
                    <div class="return_request_card">
                        <div class="card_header">
                            <h3>طلب استرجاع العقد #{{ $contract->id }}</h3>
                            <p>قم بإضافة ملاحظاتك لطلب الاسترجاع</p>
                        </div>

                        <div class="card_body">
                            <!-- معلومات العقد -->
                            <div class="contract_info">
                                <h5>معلومات العقد</h5>
                                <div class="info_grid">
                                    <div class="info_item">
                                        <span class="label">رقم العقد:</span>
                                        <span class="value">#{{ $contract->id }}</span>
                                    </div>
                                    <div class="info_item">
                                        <span class="label">العاملة:</span>
                                        <span class="value">{{ $contract->maid->name ?? 'غير محدد' }}</span>
                                    </div>
                                    <div class="info_item">
                                        <span class="label">حالة العقد:</span>
                                        <span class="value">{{ $contract->status }}</span>
                                    </div>
                                    <div class="info_item">
                                        <span class="label">تاريخ العقد:</span>
                                        <span class="value">{{ $contract->created_at->format('Y-m-d') }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- نموذج طلب الاسترجاع -->
                            <div class="return_form">
                                <h5>طلب الاسترجاع</h5>
                                <form id="return-request-form">
                                    @csrf
                                    <div class="form_group">
                                        <label for="notes">
                                            <i class="fa-light fa-message"></i>
                                            ملاحظات طلب الاسترجاع
                                        </label>
                                        <textarea id="notes" name="notes" rows="6" placeholder="اكتب سبب طلب الاسترجاع والتفاصيل المطلوبة..." required></textarea>
                                        <small class="form_text">يرجى كتابة سبب واضح لطلب الاسترجاع</small>
                                    </div>

                                    <div class="form_actions">
                                        <a href="{{ route('web.contracts.index') }}" class="btn btn_secondary">
                                            <i class="fa-light fa-arrow-left me-2"></i>
                                            العودة للعقود
                                        </a>
                                        <button type="submit" class="btn btn_primary">
                                            <i class="fa-light fa-paper-plane me-2"></i>
                                            إرسال طلب الاسترجاع
                                        </button>
                                    </div>
                                </form>
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
$(document).ready(function() {
    $('#return-request-form').on('submit', function(e) {
        e.preventDefault();

        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.html('<i class="fa-light fa-spinner fa-spin me-2"></i>جاري الإرسال...');
        submitBtn.prop('disabled', true);

        $.ajax({
            url: '{{ route("web.contract-return-requests.store", $contract->id) }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'تم بنجاح',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = '{{ route("web.contract-return-requests.index") }}';
                        });
                    } else {
                        alert(response.message);
                        window.location.href = '{{ route("web.contract-return-requests.index") }}';
                    }
                }
            },
            error: function(xhr) {
                let errorMessage = 'حدث خطأ أثناء إرسال الطلب';
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
            },
            complete: function() {
                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
            }
        });
    });
});
</script>
@endsection

@section('styles')
<style>
.return_request_card {
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

.contract_info {
    background: #f8fafc;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: 1px solid #e2e8f0;
}

.contract_info h5 {
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

.return_form h5 {
    color: #1e293b;
    font-weight: 700;
    margin-bottom: 1rem;
    font-size: 1.1rem;
}

.form_group {
    margin-bottom: 1.5rem;
}

.form_group label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #1e293b;
    font-size: 0.95rem;
}

.form_group textarea {
    width: 100%;
    padding: 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: white;
    resize: vertical;
    min-height: 120px;
}

.form_group textarea:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    outline: none;
}

.form_text {
    display: block;
    margin-top: 0.25rem;
    color: #64748b;
    font-size: 0.8rem;
    font-style: italic;
}

.form_actions {
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

    .form_actions {
        flex-direction: column;
    }

    .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
@endsection
