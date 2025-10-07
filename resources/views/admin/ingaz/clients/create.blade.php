@extends('admin.layout.indexs.index')
@section('title')
    {{ helperTrans('ingaz.add_client') }}
@endsection
@section('links')
    <li class="breadcrumb-item active"><a href="javascript:void(0)">{{ helperTrans('ingaz.add_client') }}</a></li>
@endsection
@section('page-title')
    {{helperTrans("ingaz.clients")}}
@endsection

@section('content')
    <form  id="addForm" method="POST" action="{{ route('clients.store') }}">
        @csrf
        <div class="row">
            <div class="card mg-b-20">
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">{{ helperTrans('ingaz.name') }}</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">{{ helperTrans('ingaz.phone') }}</label>
                                <input type="text" name="phone" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">{{ helperTrans('ingaz.email') }}</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">{{ helperTrans('ingaz.password') }}</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">{{ helperTrans('ingaz.nationality') }}</label>
                                <select name="nationality_id" class="form-control">
                                    <option value="">{{ helperTrans('ingaz.choose') }}</option>
                                    @foreach($countries as $country)
                                        <option value="{{ $country->id }}">{{ $country->name_ar }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">{{ helperTrans('ingaz.national_num') }}</label>
                                <input type="text" name="national_num" class="form-control">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">{{ helperTrans('ingaz.birthdate') }}</label>
                                <input type="date" name="birthdate" class="form-control">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">{{ helperTrans('ingaz.phone2') }}</label>
                                <input type="text" name="phone2" class="form-control">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label">{{ helperTrans('ingaz.address') }}</label>
                                <input type="text" name="address" class="form-control">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">{{ helperTrans('ingaz.city') }}</label>
                                <input type="text" name="city" class="form-control">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">{{ helperTrans('ingaz.rooms_num') }}</label>
                                <input type="number" name="rooms_num" class="form-control">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">{{ helperTrans('ingaz.family_num') }}</label>
                                <input type="number" name="family_num" class="form-control">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">{{ helperTrans('ingaz.children') }}</label>
                                <input type="number" name="children" class="form-control">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">{{ helperTrans('ingaz.job') }}</label>
                                <input type="text" name="job" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" id="submitBtn" class="btn btn-success mt-3">
            {{ __('admin.confirm') }}
        </button>

    </form>
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function () {
            $('#addForm').on('submit', function (e) {
                e.preventDefault();

                let $btn = $('#submitBtn');
                let originalHtml = $btn.html(); // نحفظ الشكل الأصلي

                $.ajax({
                    type: 'POST',
                    url: $(this).attr('action'),
                    data: $(this).serialize(),
                    beforeSend: function () {
                        $btn.prop('disabled', true).html(
                            '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> {{ __("ingaz.sending") }}'
                        );
                    },
                    success: function (response) {
                        if (response.code === 200) {
                            successToster('{{ helperTrans("ingaz.done successfully") }}', response.message);
                            setTimeout(function () {
                                window.location.href = response.url;
                            }, 1000);
                        } else if (response.code === 422) {
                            handleErrorResponce(response);
                        }
                    },
                    error: function (response) {
                        handleErrorResponce(response);
                    },
                    complete: function () {
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                });
            });
        });

        function handleErrorResponce(response) {
            if (response.responseJSON && response.responseJSON.errors) {
                let errors = response.responseJSON.errors;
                let messages = [];
                Object.keys(errors).forEach(function(key) {
                    messages.push(errors[key][0]);
                });
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ في البيانات',
                    html: messages.join('<br>'),
                    confirmButtonText: 'حسناً'
                });
            } else if (response.responseJSON && response.responseJSON.message) {
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: response.responseJSON.message,
                    confirmButtonText: 'حسناً'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ غير متوقع',
                    text: 'حدث خطأ غير متوقع. حاول مرة أخرى.',
                    confirmButtonText: 'حسناً'
                });
            }
        }
    </script>
@endsection
