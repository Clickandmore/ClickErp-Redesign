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
                           href="{{route('site.all-workers.index') }}?type=estqdam"> {{__("ingaz.recruitment_request")}}
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
                            {{__("ingaz.employment_rules_and_regulations")}}
                        </a>
                    </li>
                </ul>
            </li>
              <!--عن الاستقدام -->
            <li class="nav-item dropdown">
              <a
                class="nav-link dropdown-toggle"
                href="#"
                data-bs-toggle="dropdown"
                >  {{__("ingaz.about_Recruitment")}}
                </a
              >
              <ul class="dropdown-menu">
                <li class="nav-item">
                <a class="nav-link" 
                href="{{route('site.musaned.index')}}"
                >
                    {{__("ingaz.musaned_initiative")}}
                    <span></span>
                </a>                
            </li>
                <li class="nav-item">
                      <a class="nav-link"
                     href="{{route('site.musaned.index')}}">
                    {{__("ingaz.arrival_worker")}}
                    <span></span>
                </a>
                </li>
                <li> 
                   <a class="nav-link"  href="{{route('site.musaned.index')}}">
                    {{__("ingaz.Avoid_human")}}
                    <span></span>
                </a>
                </li>
              </ul>
            </li>
           
            <li class="nav-item">
                <a class="nav-link" href="{{route('site.all-workers.index')}}">
                    {{__("ingaz.recruitment_request")}}
                    <span></span>
                </a>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#"
                   data-bs-toggle="dropdown">{{__("ingaz.contact_us")}}</a>
                <ul class="dropdown-menu">
                    <li class="nav-item">
                        <a class="dropdown-item" href="{{route('site.contact-us.index')}}">  {{__("ingaz.contact_us")}}</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{route('site.support.index')}}">{{__("ingaz.support")}}</a>
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
<div class="news-ticker">
    <marquee id="news-marquee">
        <i class="fa-solid fa-bell mx-2"></i>
        {{__("ingaz.last_news")}}
        @forelse($fastnews as $fastnew)
            <span>|</span>
            {{ $fastnew->title }}
        @empty
            : إطلاق باقات جديدة لاستقدام العمالة المنزلية
            <span>|</span> وصول دفعات جديدة من العمالة الماهرة من الفلبين والهند
            <span>|</span> عروض خاصة للعملاء الجدد بمناسبة افتتاح الفرع الجديد
            <span>|</span> توفير كوادر طبية وتمريضية مؤهلة بعقود ميسّرة
            <span>|</span> استقدام سائقين مهنيين بخبرة عالية لجميع أنحاء المملكة
            <span>|</span> بدء برنامج متابعة وضمان ما بعد الاستقدام
            <span>|</span> توفير عاملات منزليات مدرّبات وفق أعلى المعايير
            <span>|</span> توقيع اتفاقيات جديدة مع مكاتب استقدام خارجية معتمدة
            <span>|</span> دعم خاص للعائلات في شهر رمضان بخدمات استقدام سريعة
        @endforelse

    </marquee>
</div>
