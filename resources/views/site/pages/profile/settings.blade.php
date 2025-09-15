@extends('site.layouts.index')
@section('title')

@endsection
@section('content')
    <section class="profile">
        <div class="container ">
            <!-- user Header  -->
            @include('site.pages.profile.includes.user_header')
            <!-- /user Header  -->
            <div class="row">
                <!-- links -->
                @include('site.pages.profile.includes.sidebar')
                <!-- content -->
                <div class=" col-md-8 col-lg-9 p-2 profileContent">
                    <div class="editProfile">
                        <div class="head">
                            <h5> <i class="fas fa-user me-2"></i> البيانات الشخصية </h5>
                        </div>
                        <form action="" class="row">
                            <div class="col-12 p-2">
                                <label for="name" class="form-label"> <i class="fas fa-user me-2"></i> الاسم كامل </label>
                                <input type="text" class="form-control" id="name" placeholder=" محمود جمال الكومي                  ">
                            </div>
                            <div class="col-md-12 p-2">
                                <label class="form-label"> <i class="fas fa-city me-2"></i> المدينة </label>
                                <select class="select2WithoutSearch">
                                    <option value=""> جدة </option>
                                    <option value=""> الرياض </option>
                                </select>
                            </div>
                            <div class="col-md-6 p-2">
                                <label for="password" class="form-label"> <i class="fas fa-key me-2"></i> الرقم السري </label>
                                <input type="password" class="form-control" id="password" placeholder="*****">
                            </div>
                            <div class="col-md-6 p-2">
                                <label for="repetPassword" class="form-label"> <i class="fas fa-key me-2"></i> تأكيد الرقم السري
                                </label>
                                <input type="password" class="form-control" id="repetPassword" placeholder="*****">
                            </div>
                            <div class="col-12 pt-4 p-2 text-center">
                                <button class="defaultBtn " type="submit">
                                    تأكيد
                                    <span></span>
                                </button>
                            </div>
                        </form>
                    </div>
                    <!--  -->
                    <div class="editProfile">
                        <div class="head">
                            <h5> <i class="fas fa-key me-2"></i> إعدادات الهاتف </h5>
                        </div>
                        <form action="#!" class="row">
                            <div class="col-12 p-2">
                                <label for="Phone" class="form-label"> <i class="fas fa-phone-alt me-2"></i> رقم الهاتف </label>
                                <input type="tel" class="form-control" id="phoneNum" placeholder="05***********">
                            </div>
                            <div class="col-12 pt-4 p-2 text-center">
                                <button class="defaultBtn " type="submit">
                                    تأكيد
                                    <span></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('js')
@endsection
