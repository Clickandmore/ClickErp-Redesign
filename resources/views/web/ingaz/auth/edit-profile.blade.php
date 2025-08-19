@extends('web.layout.app')

@section('content')
<main>
    <section class="page_header">
        <h1>تعديل الملف الشخصي</h1>
        <h6><a href="{{route('web.home')}}">الرئيسية</a> / <a href="{{route('web.profile')}}">لوحة التحكم</a> / تعديل الملف الشخصي</h6>
    </section>

    <section class="setting_setion">
        <div class="container">
            <div class="row">
                @include('web.layout.inc.profile_aside')

                <div class="col-lg-8 col-12">
                    <div class="edit_profile_card">
                        <div class="card_header">
                            <h3>تعديل الملف الشخصي</h3>
                            <p>قم بتحديث معلوماتك الشخصية</p>
                        </div>

                        <div class="card_body">
                            <form action="{{route('web.update-profile')}}" id="update-profile" method="post" class="profile_form">
                                @csrf
                                <div class="form_group">
                                    <label for="name">
                                        <i class="fa-light fa-user"></i>
                                        الاسم بالكامل
                                    </label>
                                    <input type="text" id="name" name="name" value="{{getGuardedUser('name')}}" placeholder="الاسم" required>
                                </div>

                                <div class="form_group">
                                    <label for="email">
                                        <i class="fa-light fa-envelope"></i>
                                        البريد الإلكتروني
                                    </label>
                                    <input type="email" id="email" name="email" value="{{getGuardedUser('email')}}" placeholder="البريد الإلكتروني" required>
                                </div>

                                <div class="form_group">
                                    <label for="phone">
                                        <i class="fa-light fa-phone"></i>
                                        رقم الهاتف
                                    </label>
                                    <input type="text" id="phone" value="{{getGuardedUser('phone_code').getGuardedUser('phone')}}" readonly>
                                    <small class="form_text">لا يمكن تغيير رقم الهاتف</small>
                                </div>

                                <div class="form_actions">
                                    <a href="{{route('web.profile')}}" class="btn btn_secondary">
                                        <i class="fa-light fa-arrow-left me-2"></i>
                                        العودة للوحة التحكم
                                    </a>
                                    <button type="submit" class="btn btn_primary">
                                        <i class="fa-light fa-save me-2"></i>
                                        تحديث الملف الشخصي
                                    </button>
                                </div>
                            </form>
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
    $('#update-profile').on('submit', function(e) {
        e.preventDefault();

        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.html('<i class="fa-light fa-spinner fa-spin me-2"></i>جاري التحديث...');
        submitBtn.prop('disabled', true);

        $.ajax({
            url: $(this).attr('action'),
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
                            window.location.href = '{{ route("web.profile") }}';
                        });
                    } else {
                        alert(response.message);
                        window.location.href = '{{ route("web.profile") }}';
                    }
                } else {
                    alert(response.message || 'حدث خطأ أثناء تحديث الملف الشخصي');
                }
            },
            error: function(xhr) {
                let errorMessage = 'حدث خطأ أثناء تحديث الملف الشخصي';
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
.edit_profile_card {
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

.profile_form {
    max-width: 600px;
    margin: 0 auto;
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

.form_group input {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: white;
}

.form_group input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    outline: none;
}

.form_group input[readonly] {
    background: #f8fafc;
    color: #64748b;
    cursor: not-allowed;
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
