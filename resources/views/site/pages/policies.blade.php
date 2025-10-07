@extends('site.layouts.index')
@section('title')
    {{__("ingaz.Policies and laws")}}
@endsection
@section('content')
    <section class="policies-hero">
        <div class="overlay">
            <div class="container">
                <h2>{{ __('ingaz.policies_hero_title') }}</h2>
                <p>{{ __('ingaz.policies_hero_desc') }}</p>
                <div class="buttons">
                    <a href="{{ asset('site/files/employer-rights.pdf') }}" class="btn" download>
                        <i class="fa-solid fa-file-pdf"></i>
                        {{ __('ingaz.employer_rights') }}
                    </a>

                    <a href="{{ asset('site/files/worker-rights.pdf') }}" class="btn" download>
                        <i class="fa-solid fa-file-pdf"></i>
                        {{ __('ingaz.worker_rights') }}
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="guidelines-section">
        <div class="container">
            <h2 class="section-title">{{ __('ingaz.guidelines_title') }}</h2>
            <p class="section-desc">
                {!! __('ingaz.guidelines_desc') !!}
            </p>
            <div class="guidelines-wrapper">

                <!-- السعودية -->
                <div class="country-card">
                    <img src="{{ asset('site/assets/images/countries/saudi_Arabia.webp') }}" alt="Saudi Arabia" class="country-flag" />
                    <h3>{{ __('ingaz.saudi_title') }}</h3>
                    <div class="links">
                        <a href="{{ asset('site/files/saudi-guidelines-ar.pdf') }}" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            {{ __('ingaz.saudi_guidelines_ar') }}
                        </a>
                        <a href="{{ asset('site/files/saudi-instructions-ar.pdf') }}" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            {{ __('ingaz.saudi_instructions_ar') }}
                        </a>
                    </div>
                </div>

                <!-- أثيوبيا (أمهرية) -->
                <div class="country-card">
                    <img src="{{ asset('site/assets/images/countries/Ethiopia-amher.png') }}" alt="Ethiopia" class="country-flag" />
                    <h3>{{ __('ingaz.ethiopia_am_title') }}</h3>
                    <div class="links">
                        <a href="{{ asset('site/files/ethiopia-guidelines-am.pdf') }}" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            {{ __('ingaz.ethiopia_am_guidelines') }}
                        </a>
                        <a href="{{ asset('site/files/ethiopia-instructions-am.pdf') }}" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            {{ __('ingaz.ethiopia_am_instructions') }}
                        </a>
                    </div>
                </div>

                <!-- أثيوبيا (أرومية) -->
                <div class="country-card">
                    <img src="{{ asset('site/assets/images/countries/oromo.png') }}" alt="Ethiopia" class="country-flag" />
                    <h3>{{ __('ingaz.ethiopia_or_title') }}</h3>
                    <div class="links">
                        <a href="{{ asset('site/files/ethiopia-guidelines-am.pdf') }}" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            {{ __('ingaz.ethiopia_or_guidelines') }}
                        </a>
                        <a href="{{ asset('site/files/ethiopia-instructions-am.pdf') }}" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            {{ __('ingaz.ethiopia_or_instructions') }}
                        </a>
                    </div>
                </div>

                <!-- الفلبين -->
                <div class="country-card">
                    <img src="{{ asset('site/assets/images/countries/4.jpeg') }}" alt="Philippines" class="country-flag" />
                    <h3>{{ __('ingaz.philippines_title') }}</h3>
                    <div class="links">
                        <a href="{{ asset('site/files/philippines-guidelines-tl.pdf') }}" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            {{ __('ingaz.philippines_guidelines') }}
                        </a>
                        <a href="{{ asset('site/files/philippines-instructions-tl.pdf') }}" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            {{ __('ingaz.philippines_instructions') }}
                        </a>
                    </div>
                </div>

                <!-- بنغلادش -->
                <div class="country-card">
                    <img src="{{ asset('site/assets/images/countries/3.jpeg') }}" alt="Bangladesh" class="country-flag" />
                    <h3>{{ __('ingaz.bangladesh_title') }}</h3>
                    <div class="links">
                        <a href="{{ asset('site/files/philippines-guidelines-tl.pdf') }}" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            {{ __('ingaz.bangladesh_guidelines') }}
                        </a>
                        <a href="{{ asset('site/files/philippines-instructions-tl.pdf') }}" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            {{ __('ingaz.bangladesh_instructions') }}
                        </a>
                    </div>
                </div>

                <!-- اندونيسيا -->
                <div class="country-card">
                    <img src="{{ asset('site/assets/images/countries/4.jpeg') }}" alt="Indonesia" class="country-flag" />
                    <h3>{{ __('ingaz.indonesia_title') }}</h3>
                    <div class="links">
                        <a href="{{ asset('site/files/philippines-guidelines-tl.pdf') }}" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            {{ __('ingaz.indonesia_guidelines') }}
                        </a>
                        <a href="{{ asset('site/files/philippines-instructions-tl.pdf') }}" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            {{ __('ingaz.indonesia_instructions') }}
                        </a>
                    </div>
                </div>

                <!-- أوغندا -->
                <div class="country-card">
                    <img src="{{ asset('site/assets/images/countries/1.png') }}" alt="Uganda" class="country-flag" />
                    <h3>{{ __('ingaz.uganda_title') }}</h3>
                    <div class="links">
                        <a href="{{ asset('site/files/philippines-guidelines-tl.pdf') }}" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            {{ __('ingaz.uganda_guidelines') }}
                        </a>
                        <a href="{{ asset('site/files/philippines-instructions-tl.pdf') }}" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            {{ __('ingaz.uganda_instructions') }}
                        </a>
                    </div>
                </div>

                <!-- كينيا -->
                <div class="country-card">
                    <img src="{{ asset('site/assets/images/countries/Kenya.png') }}" alt="Kenya" class="country-flag" />
                    <h3>{{ __('ingaz.kenya_title') }}</h3>
                    <div class="links">
                        <a href="{{ asset('site/files/philippines-guidelines-tl.pdf') }}" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            {{ __('ingaz.kenya_guidelines') }}
                        </a>
                        <a href="{{ asset('site/files/philippines-instructions-tl.pdf') }}" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            {{ __('ingaz.kenya_instructions') }}
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </section>

@endsection
@section('js')
@endsection
