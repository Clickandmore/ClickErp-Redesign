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

                    <div class="notifications">
                        <ul>
                            <li class="notification">
                                <h6 class="notificationTitle"> عنوان الاشعار </h6>
                                <p> هو ببساطة نص شكلي (بمعنى أن الغاية هي الشكل وليس المحتوى) ويُستخدم في صناعات المطابع
                                    ودور النشر.
                                    كان لوريم إيبسوم ولايزال المعيار للنص الشكلي منذ القرن الخامس عشر عندما قامت مطبعة
                                    مجهولة برص مجموعة
                                    من الأحرف بشكل عشوائي أخذتها من نص، </p>
                                <div class=" date">
                                    <div class=" me-2"><i class="fas fa-calendar-alt me-2"></i> 20 / 10 / 2022 </span>
                                    </div>
                                    <div><i class="far fa-clock me-2"></i> 10:45 pm </span> </div>
                                </div>
                            </li>
                            <li class="notification">
                                <h6 class="notificationTitle"> عنوان الاشعار </h6>
                                <p> هو ببساطة نص شكلي (بمعنى أن الغاية هي الشكل وليس المحتوى) ويُستخدم في صناعات المطابع
                                    ودور النشر.
                                    كان لوريم إيبسوم ولايزال المعيار للنص الشكلي منذ القرن الخامس عشر عندما قامت مطبعة
                                    مجهولة برص مجموعة
                                    من الأحرف بشكل عشوائي أخذتها من نص، </p>
                                <div class=" date">
                                    <div class=" me-2"><i class="fas fa-calendar-alt me-2"></i> 20 / 10 / 2022 </span>
                                    </div>
                                    <div><i class="far fa-clock me-2"></i> 10:45 pm </span> </div>
                                </div>
                            </li>
                            <li class="notification">
                                <h6 class="notificationTitle"> عنوان الاشعار </h6>
                                <p> هو ببساطة نص شكلي (بمعنى أن الغاية هي الشكل وليس المحتوى) ويُستخدم في صناعات المطابع
                                    ودور النشر.
                                    كان لوريم إيبسوم ولايزال المعيار للنص الشكلي منذ القرن الخامس عشر عندما قامت مطبعة
                                    مجهولة برص مجموعة
                                    من الأحرف بشكل عشوائي أخذتها من نص، </p>
                                <div class=" date">
                                    <div class=" me-2"><i class="fas fa-calendar-alt me-2"></i> 20 / 10 / 2022 </span>
                                    </div>
                                    <div><i class="far fa-clock me-2"></i> 10:45 pm </span> </div>
                                </div>
                            </li>

                        </ul>
                    </div>

                </div>
            </div>
        </div>
    </section>
@endsection
@section('js')
@endsection
