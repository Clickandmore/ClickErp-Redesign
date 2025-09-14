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
                                <div class="warning_box">
                                    <i class="fa-light fa-triangle-exclamation"></i>
                                    <div>
                                        <strong>تنبيه مهم:</strong>
                                        <p>سيتم مراجعة طلب الاسترجاع من قبل الإدارة. قد تستغرق المعالجة عدة أيام عمل.</p>
                                    </div>
                                </div>

                                <form id="return-request-form" action="{{ route('web.contract-return-requests.store', $contract->id) }}" method="POST">
                                    @csrf
                                    <div class="form_group">
                                        <label for="notes">
                                            <i class="fa-light fa-message"></i>
                                            ملاحظات طلب الاسترجاع
                                        </label>
                                        <textarea id="notes" name="notes" rows="6" placeholder="اكتب سبب طلب الاسترجاع والتفاصيل المطلوبة..." required></textarea>
                                        <small class="form_text">يرجى كتابة سبب واضح ومفصل لطلب الاسترجاع</small>
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
// التحقق من توفر jQuery
if (typeof jQuery === 'undefined') {
    console.error('jQuery is not loaded!');
    alert('خطأ: jQuery غير متوفر');
} else {
    console.log('jQuery is loaded, version:', jQuery.fn.jquery);
}

$(document).ready(function() {
    console.log('Document ready');
    console.log('Form element:', $('#return-request-form').length);

    if ($('#return-request-form').length === 0) {
        console.error('Form not found!');
        return;
    }

    $('#return-request-form').on('submit', function(e) {
        console.log('Form submit event triggered');
        e.preventDefault();
        console.log('Default prevented');

        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.html('<i class="fa-light fa-spinner fa-spin me-2"></i>جاري الإرسال...');
        submitBtn.prop('disabled', true);

        const formData = $(this).serialize();
        console.log('Form data:', formData);
        console.log('Form action:', $(this).attr('action'));

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('Success response:', response);
                if (response.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'تم إرسال الطلب بنجاح',
                            text: response.message,
                            confirmButtonText: 'عرض طلباتي',
                            showCancelButton: true,
                            cancelButtonText: 'العودة للعقود'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = '{{ route("web.contract-return-requests.index") }}';
                            } else {
                                window.location.href = '{{ route("web.contracts.index") }}';
                            }
                        });
                    } else {
                        alert(response.message);
                        window.location.href = '{{ route("web.contract-return-requests.index") }}';
                    }
                }
            },
            error: function(xhr, status, error) {
                console.log('Error details:', {xhr, status, error});
                console.log('Response text:', xhr.responseText);

                let errorMessage = 'حدث خطأ أثناء إرسال الطلب';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ في الإرسال',
                        text: errorMessage,
                        confirmButtonText: 'حسناً'
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

    console.log('Event handler attached');
});
</script>
@endsection

@section('styles')
<style>
</style>
@endsection
