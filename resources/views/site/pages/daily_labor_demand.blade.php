@extends('site.layouts.index')
@section('title')
@endsection
@section('content')
    <!-- Main Banner  -->
    <section class="mainBanner">
        <button onclick="goBack()" class="Back">
            <i class="fas fa-angle-left"></i>
        </button>
        <ul>
            <li>
                <a href="index.html"> الرئيسية </a>
            </li>
            <li>
                <a href="index.html#services">الخدمات </a>
            </li>
            <li>
                <a href="#!" class="active">طلب عمالة مهنية </a>
            </li>
        </ul>
        <figure>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                preserveAspectRatio="none"
                x="0px"
                y="0px"
                viewBox="0 0 300 100"
                style="margin-bottom: -35px; enable-background: new 0 0 300 100"
                class="injected-svg js-svg-injector"
                data-parent="#SVGwave1BottomSMShape"
            >
                <g>
                    <path
                        class="wave-bottom-1-sm-0"
                        fill="#fdfdfd"
                        opacity=".8"
                        d="M10.9,63.9c0,0,42.9-34.5,87.5-14.2c77.3,35.1,113.3-2,146.6-4.7C293.7,41,315,61.2,315,61.2v54.4H10.9V63.9z"
                    ></path>
                    <path
                        class="wave-bottom-1-sm-0"
                        fill="#fdfdfd"
                        opacity=".6"
                        d="M-55.7,64.6c0,0,42.9-34.5,87.5-14.2c77.3,35.1,113.3-2,146.6-4.7c48.7-4.1,69.9,16.2,69.9,16.2v54.4H-55.7     V64.6z"
                    ></path>
                    <path
                        class="wave-bottom-1-sm-0"
                        fill="#fdfdfd"
                        opacity=".4"
                        fill-opacity="0"
                        d="M23.4,118.3c0,0,48.3-68.9,109.1-68.9c65.9,0,98,67.9,98,67.9v3.7H22.4L23.4,118.3z"
                    ></path>
                    <path
                        class="wave-bottom-1-sm-0"
                        fill="#fdfdfd"
                        d="M-54.7,83c0,0,56-45.7,120.3-27.8c81.8,22.7,111.4,6.2,146.6-4.7c53.1-16.4,104,36.9,104,36.9l1.3,36.7l-372-3     L-54.7,83z"
                    ></path>
                </g>
            </svg>
        </figure>
    </section>
    <section class="dailyForm">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-10 fadeInUp">
                    <div class="headTitle text-center">
                        <h2>طلب العمالة المهنية</h2>
                        <p>
                            نحن قادرون على جلب أفضل أنواع العمالة المهنية من مختلف ،
                            وجميع العمالة الفائضة من العمل للطبيب.
                        </p>
                    </div>

                    <!-- الفورم -->
                    <form action="" class="row needs-validation" novalidate>
                        <!-- اسم المنشأة -->
                        <div class="col-md-12 p-2">
                            <label class="form-label">
                                <i class="fas fa-building me-2"></i> اسم المنشأة *
                            </label>
                            <input type="text" class="form-control" required/>
                            <div class="invalid-feedback">هذا الحقل ضروري</div>
                        </div>

                        <!-- رقم التواصل -->
                        <div class="col-md-6 p-2">
                            <label class="form-label">
                                <i class="fas fa-phone me-2"></i> رقم التواصل *
                            </label>
                            <input type="tel" class="form-control" required/>
                            <div class="invalid-feedback">هذا الحقل ضروري</div>
                        </div>

                        <!-- عدد العمال المطلوبين -->
                        <div class="col-md-6 p-2">
                            <label class="form-label">
                                <i class="fas fa-users me-2"></i> عدد العمال المطلوبين *
                            </label>
                            <input type="number" min="1" class="form-control" required/>
                            <div class="invalid-feedback">هذا الحقل ضروري</div>
                        </div>

                        <!-- نوع العمالة المطلوبة -->
                        <div class="col-md-6 p-2">
                            <label class="form-label">
                                <i class="fas fa-briefcase me-2"></i> نوع العمالة المطلوبة *
                            </label>
                            <select class="form-control" required>
                                <option value="" disabled selected>اختر نوع العمالة</option>
                                <option value="طبيب">طبيب</option>
                                <option value="مهندس">مهندس</option>
                                <option value="فني">فني</option>
                                <option value="عامل">عامل</option>
                            </select>
                            <div class="invalid-feedback">هذا الحقل ضروري</div>
                        </div>

                        <!-- رقم التأشيرة -->
                        <div class="col-md-6 p-2">
                            <label class="form-label">
                                <i class="fas fa-passport me-2"></i> رقم التأشيرة *
                            </label>
                            <input type="text" class="form-control" required/>
                            <div class="invalid-feedback">هذا الحقل ضروري</div>
                        </div>

                        <!-- متطلبات أخرى -->
                        <div class="col-md-12 p-2">
                            <label class="form-label">
                                <i class="fas fa-feather-alt me-2"></i> متطلبات أخرى
                            </label>
                            <textarea class="form-control" rows="4"></textarea>
                        </div>

                        <!-- زر الإرسال -->
                        <div class="col-md-12 text-center p-2">
                            <button type="submit" class="defaultBtn">
                                إرسال الطلب <i class="fas fa-paper-plane ms-2"></i>
                                <span></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('js')
@endsection
