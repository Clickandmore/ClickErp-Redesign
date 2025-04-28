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
    <form method="POST" action="{{ route('clients.store') }}" id="addForm">
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
                                <input type="text" name="nationality_id" class="form-control">
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

        <div class="row mb-3 col-md-12 text-center mt-5">
            <div class="col-sm-12 text-sm-end text-center mt-10">
                <a href="{{ route('clients.index') }}" class="btn btn-danger">{{ __('admin.back') }}</a>
                <button type="submit" class="btn btn-success">{{ __('admin.confirm') }}</button>
            </div>
        </div>
    </form>
@endsection

@section('js')
    <script>
        $(document).ready(function () {
            $('#addForm').on('submit', function (e) {
                e.preventDefault();
                $.ajax({
                    type: 'POST',
                    url: $(this).attr('action'),
                    data: $(this).serialize(),
                    success: function (response) {
                        if (response.code === 200) {
                            successToster('{{ helperTrans("ingaz.done successfully") }}', response.message);
                            setTimeout(function () {
                                window.location.href = response.url;
                            }, 1000);
                        } else if (response.code === 422) {
                            handleErrorResponce(response)
                        }
                    },
                    error: function (response) {
                        handleErrorResponce(response)
                    }
                });
            });
        });
    </script>
@endsection