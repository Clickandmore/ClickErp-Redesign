@extends('admin.layout.indexs.index')
@section("style")
@endsection
@section('page-title')
    {{helperTrans("ingaz.change_password")}}
@endsection
{{--@section('page-links')--}}
{{--    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li>--}}
{{--@endsection--}}
@section('content')
    <div id="list-item-2" class="card fieldset border border-muted mt-5">

        <span class="fieldset-tile text-muted bg-body">تغير كلمه المرور</span>
        <form method="post" action="{{route('change-password.update',$admin->id)}}"
              class="from-submit-update-global p-lg-4 p-0" id="">
            @method('put')
            @csrf
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="mb-1">
                                <div class="mb-2 ">
                                    <input type="password" name="new_password" placeholder="كلمه المرور الجديده"
                                           class="form-control form-control-lg password-meter-input">
                                </div>
                                <div class="progress mb-1" style="height: 10px;">
                                    <div class="progress-bar-bar bg-primary-gradient" role="progressbar"
                                         style="width: 0%" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <div class="text-muted small">استخدم 8 أحرف أو أكثر مع مزيج من الأحرف والأرقام
                                    والرموز.
                                </div>
                            </div>
                            <div>
                                <input type="password" class="form-control form-control-lg" name="new_confirm_password"
                                       placeholder="تاكيد كلمه المرور">
                                {{--                                            <span class="text-muted small">Minimum 8 characters</span>--}}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button class="btn btn-lg btn-light me-2" type="reset">الغاء</button>
                    <button class="btn btn-lg btn-primary" type="submit">تعديل كلمه المرور</button>
                </div>
            </div>
        </form>
    </div>
@endsection
@section('js')

@endsection

