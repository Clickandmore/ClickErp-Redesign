<nav class="navbar mainNav navbar-expand-lg">
    <!-- <div class="container"> -->
    <!-- Logo -->
    <a class="navbar-brand" href="{{route('site.home.index')}}">
        @if(isset($settings))
            <img class="logo" src="{{showFile($settings->logo_header)}}" alt="Logo"/>
        @else
            <img class="logo" src="{{asset('site')}}/assets/images/logo.svg" alt="Logo"/>
        @endif
    </a>

    <!-- Toggle Btn (Mobile) -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#navContent"
            aria-controls="navContent"
            aria-expanded="false"
            aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Nav Items -->
    <div class="collapse navbar-collapse" id="navContent">
        <ul class="navbar-nav ms-auto align-items-lg-center">
            <li class="nav-item">
                <a class="nav-link active" href="{{route('site.home.index')}}">الرئيسية</a>
            </li>
            <!-- خدماتنا -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">خدماتنا</a>
                <ul class="dropdown-menu">
                    <li>
                        <a class="dropdown-item" href="all-workers.html"> طلب استقدام</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="all-workers.html">طلب نقل خدمات</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="all-workers.html">طلب ايجار عمالة</a>
                    </li>

                    <li>
                        <a class="dropdown-item" href="dailyLaborDemand.html"> طلب عمالة مهنية</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="renew-contract.html">تجديد عقد عاملة</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="support.html">مركز المساعدة</a>
                    </li>
                </ul>
            </li>
            <!--سياسات الاستقدام -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">سياسات الاستقدام</a>
                <ul class="dropdown-menu">
                    <li>
                        <a class="dropdown-item" href="visa.html">إصدار التأشيرة</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="select-worker.html">اختيار العمالة</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="all-workers.html"> عقد استقدام</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="arrive-worker.html">وصول العمالة</a>
                    </li>
                </ul>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="musaned.html">مبادرة مساند</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="all-workers.html">
                    طلب استقدام<span></span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{route('site.contact-us.index')}}">تواصل معنا</a>
            </li>
            @if(\Illuminate\Support\Facades\Auth::guard('web')->check())
                <!-- Account -->
                <li class="nav-item dropdown userMenu">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <img class="userImg me-2" src="{{asset('site')}}/assets/images/avatar.webp" alt="User"/>
                        {{__("ingaz.my_account")}}
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="{{route('site.profile.index')}}"><i
                                    class="fa-solid fa-user me-1"></i>
                                {{__("ingaz.recruitment_requests")}}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{route('site.notifications.index')}}"><i
                                    class="fas fa-bell me-1"></i>
                                {{__("ingaz.notifications")}}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{route('site.logout.index')}}"><i
                                    class="fas fa-power-off me-1"></i> {{__("ingaz.logout")}}
                            </a>
                        </li>
                    </ul>
                </li>
            @endif
            <!-- Language -->
            <li class="nav-item dropdown language">
                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                    <i class="fa-solid fa-globe me-2"></i>
                </a>
                <ul class="dropdown-menu">
                    <li>
                        <a class="dropdown-item" href="#"><img src="https://flagcdn.com/w40/sa.png" alt=""/> عربي</a>
                    </li>
                    <li><a class="dropdown-item" href="#"><img src="https://flagcdn.com/w40/us.png" alt=""/>English</a>
                    </li>
                </ul>
            </li>
            @if(!\Illuminate\Support\Facades\Auth::guard('web')->check())
                <!-- Login Btn -->
                <li class="nav-item d-lg-block">
                    <a class="login-btn" href="{{route('site.login.index')}}">تسجيل الدخول<span></span></a>
                </li>
            @endif
        </ul>
    </div>
    <!-- </div> -->
</nav>
