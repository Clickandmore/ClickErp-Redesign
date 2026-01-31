<nav class="navbar mainNav navbar-expand-lg">
        <div class="container-fluid">
    <!-- Logo -->
    @php
        $headerLogo = isset($settings) ? showFile($settings->logo_header) : null;
    @endphp
    <a class="navbar-brand" href="{{route('site.home.index')}}">
        <img class="logo" src="{{ $headerLogo ?? asset('site/assets/images/company_logo.png') }}" alt="Logo"
             onerror="this.onerror=null;this.src='{{ asset('site/assets/images/company_logo.png') }}';"/>
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
                <ul class="navbar-nav align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link active" href="{{route('site.home.index')}}">{{__("ingaz.home")}}</a>
                    </li>
                    <!-- خدماتنا -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#"
                           data-bs-toggle="dropdown">{{__("web.our_services")}}</a>
                        <ul class="dropdown-menu">
                            <li>
                                {{-- <a class="dropdown-item"
                                   href="{{route('site.all-workers.index') }}?type=estqdam"> {{__("ingaz.recruitment_request_title")}}
                                </a> --}}
                                <a class="dropdown-item"
                                   href="{{route('site.all-workers.index') }}"> {{__("ingaz.recruitment_request_title")}}
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item"
                                   href="{{route('site.all-workers.index') }}?type=transfer"> {{__("ingaz.Labor_transfer_services")}}
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item"
                                   href="{{route('site.all-workers.index') }}?type=egar">{{__("ingaz.labor_rental_request")}}
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item"
                                   href="{{route('site.all-workers.index') }}?type=transfer">{{__("ingaz.Labor_transfer_request")}}
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
                        </ul>
                    </li>
                     <!--عن الاستقدام -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#"
                           data-bs-toggle="dropdown">  {{__("ingaz.about_Recruitment")}}
                        </a>
                        <ul class="dropdown-menu">
                            <li class="nav-item">
                                <a class="nav-link"
                                   href="{{route('site.musaned.index')}}">{{__("ingaz.musaned_initiative")}}
                                    <span></span>
                                </a>
                            </li>
                            <li class="nav-item"><a class="nav-link" href="{{route('site.policies.index')}}">
                                    {{__("ingaz.Policies and laws")}}
                                    <span></span>
                                </a>
                            </li>
                            <li>
                                <a class="nav-link" href="{{route('site.anti-traficking.index')}}">
                                    {{__("ingaz.Avoid_human")}}<span></span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!--رحلة الاستقدام -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#"
                           data-bs-toggle="dropdown">{{__("ingaz.recruitment_journey")}}</a>
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
                                    {{__("ingaz.employment_rules_and_regulations")}}
                                </a>
                            </li>
                        </ul>
                    </li>


                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#"
                           data-bs-toggle="dropdown">{{__("ingaz.technical_support")}}</a>
                        <ul class="dropdown-menu">
                            <li class="nav-item">
                                <a class="dropdown-item"
                                   href="{{route('site.contact-us.index')}}">  {{__("ingaz.contact-us")}}</a>
                            </li>
                            <li>
                                <a class="dropdown-item"
                                   href="{{route('site.support.index')}}">{{__("ingaz.support")}}</a>
                            </li>
                        </ul>
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
                    @if(\Illuminate\Support\Facades\Auth::guard('web')->check())
                        <li class="nav-item">
                            <a class="nav-link" href="{{route('site.complaints.index')}}" title="إضافة شكوى">
                                <i class="fa-solid fa-comment-dots me-3 fs-5"></i>
                            </a>
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

                     <!-- resv Btn -->
                        <li class="nav-item d-lg-block">
                            <a class="login-btn" href="{{route('site.all-workers.index')}}">{{__("ingaz.recruitment_request")}}<span></span></a>
                        </li>
                </ul>
            </div>
        </div>
</nav>
<div class="news-ticker" data-aos="fade-up" data-aos-duration="900">
    <div class="news-inner">
        <span class="news-label">
            <i class="fa-solid fa-bell"></i>
            {{ __("ingaz.last_news") }}
        </span>
        <ul class="news-items" aria-live="polite">
            @forelse($fastnews as $fastnew)
                <li style="--i: {{ $loop->index }};">{{ $fastnew->title }}</li>
            @empty
                @foreach(__('web.lastNews') as $index => $item)
                    <li style="--i: {{ $index }};">{{ $item }}</li>
                @endforeach
            @endforelse
        </ul>
    </div>
</div>
