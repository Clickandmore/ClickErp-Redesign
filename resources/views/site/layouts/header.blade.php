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
                <a class="nav-link active" href="{{route('site.home.index')}}">{{__("ingaz.home")}}</a>
            </li>
            <!-- خدماتنا -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">{{__("ingaz.our_services")}}</a>
                <ul class="dropdown-menu">
                    <li>
                        <a class="dropdown-item"
                           href="{{route('site.all-workers.index')}}"> {{__("ingaz.recruitment_request")}}
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item"
                           href="{{route('site.all-workers.index')}}">{{__("ingaz.request_for_transfer_of_services")}}
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item"
                           href="{{route('site.all-workers.index')}}">{{__("ingaz.labor_rental_request")}}
                        </a>
                    </li>

                    <li>
                        <a class="dropdown-item"
                           href="{{route('site.daily-lebor-demand.index')}}"> {{__("ingaz.request_professional_labor")}}
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item"
                           href="{{route('site.renew-contract.index')}}">{{__("ingaz.renewing_employment_contract")}}
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{route('site.support.index')}}">{{__("ingaz.support")}}</a>
                    </li>
                </ul>
            </li>
            <!--سياسات الاستقدام -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#"
                   data-bs-toggle="dropdown">{{__("ingaz.recruitment_policies")}}</a>
                <ul class="dropdown-menu">
                    <li>
                        <a class="dropdown-item" href="{{route('site.visa.index')}}">
                            {{__("ingaz.visa_issuance")}}
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item"
                           href="{{route('site.select-worker.index')}}">
                            {{__("ingaz.selection_of_workers")}}
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item"
                           href="{{route('site.all-workers.index')}}">
                            {{__("ingaz.recruitment_contract")}}
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item"
                           href="{{route('site.arrival-worker.index')}}">
                            {{__("ingaz.arrival_worker")}}
                        </a>
                    </li>
                </ul>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{route('site.musaned.index')}}">
                    {{__("ingaz.musaned_initiative")}}
                    <span></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{route('site.all-workers.index')}}">
                    {{__("ingaz.recruitment_request")}}
                    <span></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{route('site.contact-us.index')}}">  {{__("ingaz.contact_us")}}</a>
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
                    @if(app()->getLocale() === 'ar')
                        {{-- لو الحالي عربي اعرض رابط النسخة الإنجليزية --}}
                        <li>
                            <a class="dropdown-item"
                               href="{{ LaravelLocalization::getLocalizedURL('en', null, [], true) }}">
                                <img src="https://flagcdn.com/w40/us.png" alt=""> English
                            </a>
                        </li>
                    @else
                        {{-- لو الحالي إنجليزي اعرض رابط النسخة العربية --}}
                        <li>
                            <a class="dropdown-item"
                               href="{{ LaravelLocalization::getLocalizedURL('ar', null, [], true) }}">
                                <img src="https://flagcdn.com/w40/sa.png" alt=""> عربي
                            </a>
                        </li>
                    @endif
                </ul>
            </li>


        @if(!\Illuminate\Support\Facades\Auth::guard('web')->check())
                <!-- Login Btn -->
                <li class="nav-item d-lg-block">
                    <a class="login-btn" href="{{route('site.login.index')}}">{{__("ingaz.login")}}<span></span></a>
                </li>
            @endif
        </ul>
    </div>
    <!-- </div> -->
</nav>
