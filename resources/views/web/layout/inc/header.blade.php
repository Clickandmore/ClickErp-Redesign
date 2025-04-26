<!-- header -->

<header>
    <div class="container">
        {{--        <div class="language"> --}}
        {{--            <a href="{{url('/')}}/{{app()->getLocale() == 'ar' ? 'en' :'ar'}}" style="color:white"> --}}
        {{--                {{app()->getLocale() == 'ar' ? 'English' :'Arabic'}} --}}
        {{--                <i class="fa-sharp fa-light fa-language"></i> --}}
        {{--            </a> --}}
        {{--        </div> --}}
        <nav class="navbar">
            <a class="logo" href="{{ route('web.home') }}">
                <img src="@if (isset($settings) && isset($settings->logo_header)) {{ showFile($settings->logo_header) }}
      @else
      {{ url('/') }}/web/assets/images/logoh.svg @endif"
                    alt="">
            </a>
            <ul class="nav-links">
                <li>
                    <a href="{{ route('web.home') }}" class="active">{{ __('web.Home') }}</a>
                </li>
                <li>
                    <div class="dropdown">
                        <button type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ helperTrans('web.about_us') }}
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                            <li><a class="dropdown-item"
                                    href="{{ route('web.rights-users') }}">{{ helperTrans('web.rights_users') }}</a>
                            </li>
                            <li><a class="dropdown-item"
                                    href="{{ route('web.rights-maids') }}">{{ helperTrans('web.rights_maids') }}</a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li>
                    <div class="dropdown">
                        <button type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ helperTrans('web.our_services') }}
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                            @foreach ($jobTypes as $jobType)
                                <li><a class="dropdown-item"
                                        href="{{ route('web.maids.index', ['job_type_id[]' => $jobType->id]) }}">{{ $jobType->title }}</a>
                                </li>
                            @endforeach
                            {{--                            <li><a class="dropdown-item" href="explore.html">إيجار عمالة</a></li> --}}
                            {{--                            <li><a class="dropdown-item" href="explore.html">نقل عمالة</a></li> --}}
                        </ul>
                    </div>
                </li>
                <li>
                    <div class="dropdown">
                        <button type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ helperTrans('web.help_&_support') }}
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                            <li><a class="dropdown-item"
                                    href="{{ route('web.contact-us.index') }}">{{ __('web.Contact_Us') }}</a></li>
                            <li><a class="dropdown-item"
                                    href="{{ route('web.faqs') }}">{{ helperTrans('web.FAQs') }}</a></li>
                        </ul>
                    </div>
                </li>
                <li class="bttns">
                    <a href="{{ route('web.contact-us.index') }}">{{ __('web.Contact_Us') }}</a>
                </li>
                @guest
                    <li class="bttns">
                        <a href="{{ route('web.auth') }}">{{ helperTrans('web.auth') }}</a>
                    </li>
                @endguest
            </ul>
            @guest
                <div class="btns">
                    <a href="{{ route('web.auth') }}">{{ helperTrans('web.login') }}</a>
                    <a href="{{ route('web.auth') }}">{{ helperTrans('web.register') }}</a>
                </div>
            @endguest
            @auth
                <div class="btns">
                    <a href="{{ route('web.profile') }}">
                        <i class="fa-solid fa-user"></i> {{ helperTrans('web.profile') }} </a>
                </div>
            @endauth

            <div class="menu-btn">
                <div class="menu-bar"></div>
            </div>

            @if (\App::getLocale() == 'ar')
                <a class="btn mx-2" href="{{ url('en') }}"> English </a>
            @else
                <a class="btn mx-2" href="{{ url('ar') }}"> عربي </a>
            @endif

        </nav>
    </div>
</header>
