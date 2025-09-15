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
                <a href="#!" class="active">تجديد عقد عماله </a>
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

    <section class="contractRenew">
        <div class="container">
            <div class="headTitle text-center">
                <h2>تجديد العقود</h2>
                <p>
                    اجرائات تجديد العقد تستغرق اسبوع من استلام الطلب. <br/>
                    للبدء في اجراءات تجديد عقد العاملة الخاصة بكم الرجاء تعبئة النموذج
                    التالي
                </p>
            </div>

            <div class="row">
                <!-- جزء بيانات العميل -->
                <div class="col-md-4 p-3">
                    <div class="mb-1">
                        <h2>تجديد العقود</h2>
                    </div>

                    <form action="" class="needs-validation" novalidate>
                        <!-- اسم العميل -->
                        <div class="mb-3">
                            <label class="form-label">اسم العميل *</label>
                            <input
                                type="text"
                                class="form-control"
                                placeholder="الاسم بالكامل"
                                required
                            />
                            <div class="invalid-feedback">هذا الحقل ضروري</div>
                        </div>

                        <!-- الهاتف -->
                        <div class="mb-3">
                            <label class="form-label">الهاتف *</label>
                            <input type="tel" class="form-control" required/>
                            <div class="invalid-feedback">هذا الحقل ضروري</div>
                        </div>

                        <!-- العنوان -->
                        <div class="mb-3">
                            <label class="form-label">العنوان *</label>
                            <input type="text" class="form-control" required/>
                        </div>

                        <!-- المدينة -->
                        <div class="mb-3">
                            <label class="form-label">City *</label>
                            <input type="text" class="form-control" required/>
                        </div>

                        <!-- معلومات تهمك -->
                        <div class="alert info mt-4">
                            <h6>معلومات تهمك:</h6>
                            <p>
                                رسوم تجديد العقد 950 ريال سعودي شامل الضريبة في حال تم
                                استقدامها أو نقل خدماتها من الشركة، يتم تحويلها على حساب
                                الشركة في البنك الأهلي.
                            </p>
                            <p>
                                رسوم تجديد العقد 1500 ريال سعودي شامل الضريبة في حال تم
                                استقدامها أو نقل خدماتها من مكتب آخر، يتم تحويلها على حساب
                                الشركة في البنك الأهلي.
                            </p>
                            <p>
                                <strong>صاحب الشركة:</strong> بلاتينيوم الشرقية للاستقدام
                            </p>
                            <p><strong>رقم الحساب:</strong> 09249888000204</p>
                            <p>
                                <strong>رقم الآيبان:</strong> SA08 1000 0009 2498 8800 0204
                            </p>
                        </div>
                    </form>
                </div>

                <!-- جزء بيانات العاملة -->
                <div class="col-md-8 p-3">
                    <div class="mb-1">
                        <h4>بيانات العاملة</h4>
                    </div>
                    <form action="" class="row needs-validation" novalidate>
                        <!-- بيانات أساسية -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">اسم العاملة</label>
                            <input type="text" class="form-control"/>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">هاتف العاملة</label>
                            <input type="tel" class="form-control"/>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">رقم جواز السفر</label>
                            <input type="text" class="form-control"/>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">العنوان</label>
                            <input type="text" class="form-control"/>
                        </div>

                        <!-- بيانات الأقارب -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">اسم أحد الأقارب</label>
                            <input type="text" class="form-control"/>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">عنوان أحد الأقارب</label>
                            <input type="text" class="form-control"/>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">هاتف أحد الأقارب</label>
                            <input type="tel" class="form-control"/>
                        </div>

                        <!-- بيانات العاملة الإضافية -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">جنسية العاملة</label>
                            <select class="form-control">
                                <option disabled selected>الرجاء تحديد اختيار</option>
                                <option>فلبينية</option>
                                <option>إندونيسية</option>
                                <option>أوغندية</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">راتب العاملة الجديد</label>
                            <input type="number" class="form-control"/>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">راتب العاملة القديم</label>
                            <input type="number" class="form-control"/>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">العاملة تم استقدامها من</label>
                            <select class="form-control">
                                <option disabled selected>الرجاء تحديد اختيار</option>
                                <option>الشركة</option>
                                <option>مكتب آخر</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">الحالة الإجتماعية للعاملة</label>
                            <select class="form-control">
                                <option disabled selected>الرجاء تحديد اختيار</option>
                                <option>عزباء</option>
                                <option>متزوجة</option>
                                <option>أرملة</option>
                            </select>
                        </div>

                        <!-- مرفقات الملفات -->
                        <div class="col-md-12">
                            <h5 class="mt-4">المرفقات المطلوبة</h5>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">بطاقة أحوال الكفيل</label>
                            <input type="file" class="form-control"/>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">إقامة العاملة</label>
                            <input type="file" class="form-control"/>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label"
                            >الصفحة الأولى من جواز العاملة</label
                            >
                            <input type="file" class="form-control"/>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">تذاكر السفر</label>
                            <input type="file" class="form-control"/>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label"
                            >التأشيرة المطبوعة في جواز العاملة</label
                            >
                            <input type="file" class="form-control"/>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">تأشيرة الخروج والعودة</label>
                            <input type="file" class="form-control"/>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">العنوان الوطني الخاص بالعميل</label>
                            <input type="file" class="form-control"/>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label"
                            >معلومات العاملة (مكتوبة على ورقة خارجية)</label
                            >
                            <input type="file" class="form-control"/>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">صورة من الحوالة البنكية</label>
                            <input type="file" class="form-control"/>
                        </div>

                        <!-- زر الإرسال -->
                        <div class="col-md-12 text-center mt-4">
                            <button type="submit" class="defaultBtn">
                                إرسال <i class="fas fa-paper-plane ms-2"></i>
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
