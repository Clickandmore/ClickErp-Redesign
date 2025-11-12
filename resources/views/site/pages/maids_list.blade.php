<div class="workers-list" id="workers-list">
    <div class="row">
            @forelse ($maids as $maid)
                @php
                    $jobType = $maid->jobTypes->first();
                    $todayDate = now()->toDateString();
                    $offerStarts = optional($maid->offer_starts_at)->format('Y-m-d');
                    $offerExpires = optional($maid->offer_expires_at)->format('Y-m-d');
                    $offerPriceRaw = $maid->offer_price !== null ? (float) $maid->offer_price : null;
                    $salaryRaw = $maid->resolved_salary !== null ? (float) $maid->resolved_salary : null;
                    $priceRaw = $maid->resolved_price !== null ? (float) $maid->resolved_price : null;
                    $offerActive =
                        $offerPriceRaw !== null &&
                        (empty($offerStarts) || $offerStarts <= $todayDate) &&
                        (empty($offerExpires) || $offerExpires >= $todayDate);
                    $offerPriceDisplay = $offerPriceRaw !== null ? number_format($offerPriceRaw, 2) : null;
                    $salaryDisplay = $salaryRaw !== null ? number_format($salaryRaw, 2) : null;
                    $priceDisplay = $priceRaw !== null ? number_format($priceRaw, 2) : null;
                    $cvFile = $maid->cv_file ?? $maid->cv_preview;
                    $cv_code = $maid->cv_code ? $maid->cv_code : null;
                @endphp
                <!-- كارد مشترك (ايجار + نقل خدمات) -->
                @if ($jobType && $jobType->id == 1)
                    <div class="col-12 col-md-6 p-2 mb-5" data-type="estqdam">
                        <a href="{{ route('site.all-workers.show', $maid->id) }}" class="worker-box-link">
                            <div class="worker-box">
                                <div class="worker-media">
                                    <img src="{{ showFile($maid->image) ?? asset('site/assets/images/worker.webp') }}"
                                        alt="worker"
                                        onerror="this.onerror=null;this.src='{{ asset('site/assets/images/worker.webp') }}';" />
                                </div>
                                <div class="worker-content">
                                    <div class="worker-header">
                                        <div class="worker-age">
                                            <span>{{ $maid->age ?? '' }}</span>{{ __('ingaz.year') }}
                                        </div>
                                        <!-- اول تلت حروف من اسم المكتب والرقم حسب عدد السيرة المستلمة من المكتب  -->
                                        {{-- @if ($maid->st_code || $maid->int_code)
                                            <div class="worker-id">
                                                {{ implode(' / ', array_filter([$maid->st_code ?? null, $maid->int_code ?? null])) }}
                                            </div>
                                        @endif --}}
                                        @if (!empty($cv_code))
                                            <p class="cv-code">{{ $cv_code }}</p>
                                        @endif
                                    </div>
                                    <h4>{{ $maid->name ?? '' }}</h4>
                                    @if ($offerActive)
                                        <div class="top-ribbon">
                                            <img src="{{ asset('site') }}/assets/images/ribbon.png" alt="عرض خاص" />
                                            <span>{{ __('ingaz.sale') }}</span>
                                        </div>
                                    @endif
                                    <div class="workerInfo">
                                        <p>{{ __('ingaz.nationality') }} :
                                            {{ $maid->nationality->sub_title ?? '' }}</p>
                                        <p>{{ __('ingaz.religion') }} : {{ $maid->religion->title ?? '' }}</p>
                                        @if (!empty($maid->passport_num))
                                            <p>{{ __('ingaz.passport_num') }} : {{ $maid->passport_num }}</p>
                                        @endif
                                        @if (!empty($cv_code))
                                            <p>{{ __('ingaz.cv_code') }} : {{ $cv_code }}</p>
                                        @endif
                                        <p>{{ __('ingaz.job') }} :
                                            {{ $maid->jobTitles->first()->title ?? '' }}</p>
                                        <p>{{ __('ingaz.experience') }} :
                                            {{ $maid->experience_status === 'has_experience' ? __('ingaz.has_experience_label') : __('ingaz.no_experience_label') }}
                                            @if (
                                                $maid->experience_status === 'has_experience' &&
                                                    is_numeric($maid->experience_years) &&
                                                    (float) $maid->experience_years > 0)
                                                - {{ $maid->experience_years }} {{ __('ingaz.year') }}
                                            @endif
                                        </p>
                                    </div>
                                    <div class="worker-salary">
                                        <p class="monthly-salary text-secondary mb-0">
                                            {{ __('ingaz.month_salary') }} :
                                            <span>{{ $salaryDisplay ?? '--' }}
                                                {{ $salaryDisplay ? __('ingaz.SAR') : '' }}</span>
                                        </p>
                                        <div>
                                            @if ($offerActive && $offerPriceDisplay)
                                                @if ($priceRaw !== null && $offerPriceRaw < $priceRaw && $priceDisplay)
                                                    <p class="old-salary mb-1">
                                                        {{ __('ingaz.before_discount') }} :
                                                        <span>{{ $priceDisplay }} {{ __('ingaz.SAR') }}</span>
                                                    </p>
                                                @endif
                                                <p class="new-salary mb-0">
                                                    {{ __('ingaz.now_price') }} :
                                                    <span>{{ $offerPriceDisplay }} {{ __('ingaz.SAR') }}</span>
                                                </p>
                                            @elseif($priceDisplay)
                                                <p class="new-salary mb-0">
                                                    {{ __('ingaz.cost_with_tax') }} :
                                                    <span>{{ $priceDisplay }} {{ __('ingaz.SAR') }}</span>
                                                </p>
                                            @endif
                                        </div>
                                        @if ($maid->offer_description)
                                            <p class="offer-note text-muted small mt-2 w-100">
                                                <i
                                                    class="fa-regular fa-comment-dots me-1"></i>{{ $maid->offer_description }}
                                            </p>
                                        @endif
                                    </div>
                                    <p class="guarantee">
                                        {{ __('ingaz.cv_alert') }}
                                    </p>
                                    <div class="actions">
                                        <a data-fancybox="cv1" href="{{ showFile($cvFile) }}" class="icon-btn">
                                            <i class="fa-regular fa-file-pdf"></i>
                                        </a>
                                        @if (!empty($maid->video_link) || (!empty($maid->video) && $maid->video !== '#'))
                                            @php
                                                $videoHref = !empty($maid->video_link)
                                                    ? $maid->video_link
                                                    : showFile($maid->video);
                                                $openAsIframe = !empty($maid->video_link);
                                            @endphp
                                            <a href="{{ $videoHref }}" class="icon-btn"
                                                data-fancybox="video{{ $maid->id }}"
                                                @if ($openAsIframe) data-type="iframe" @endif>
                                                <i class="fa-solid fa-video"></i>
                                            </a>
                                        @endif
                                        <a href="{{ route('site.all-workers.show', $maid->id) }}"
                                            class="btn action-btn">
                                            {{ __('ingaz.recruitment_request') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                @elseif ($jobType && $jobType->id == 2)
                    <!-- كارد خاص بالإيجار -->
                    <div class="col-12 col-md-6 p-2 mb-5" data-type="egar">
                        <a href="{{ route('site.packages.show', $maid->id) }}" class="worker-box-link">
                            <div class="worker-box">
                                <div class="worker-media">
                                    <img src="{{ showFile($maid->image) ?? asset('site/assets/images/worker.webp') }}"
                                        alt="worker"
                                        onerror="this.onerror=null;this.src='{{ asset('site/assets/images/worker.webp') }}';" />
                                </div>
                                <div class="worker-content">
                                    <div class="worker-header">
                                        <span>{{ $maid->age ?? '' }}</span>{{ __('ingaz.year') }}
                                        {{-- @if ($maid->st_code || $maid->int_code)
                                            <div class="worker-id">
                                                {{ implode(' / ', array_filter([$maid->st_code ?? null, $maid->int_code ?? null])) }}
                                            </div>
                                        @endif --}}
                                        @if (!empty($cv_code))
                                            <p class="cv-code">{{ $cv_code }}</p>
                                        @endif
                                    </div>
                                    <h4>{{ $maid->name ?? '' }}</h4>
                                    <div class="workerInfo">
                                        <p>{{ __('ingaz.nationality') }} : {{ $maid->nationality->sub_title ?? '' }}
                                        </p>
                                        <p>{{ __('ingaz.religion') }} : {{ $maid->religion->title ?? '' }}</p>
                                        @if (!empty($maid->passport_num))
                                            <p>{{ __('ingaz.passport_num') }} : {{ $maid->passport_num }}</p>
                                        @endif
                                        @if (!empty($cv_code))
                                            <p>{{ __('ingaz.cv_code') }} : {{ $cv_code }}</p>
                                        @endif
                                        <p>{{ __('ingaz.job') }} :
                                            {{ $maid->jobTitles->first()->title ?? '' }}</p>
                                        <p>{{ __('ingaz.experience') }} :
                                            {{ $maid->experience_status === 'has_experience' ? __('ingaz.has_experience_label') : __('ingaz.no_experience_label') }}
                                            @if (
                                                $maid->experience_status === 'has_experience' &&
                                                    is_numeric($maid->experience_years) &&
                                                    (float) $maid->experience_years > 0)
                                                - {{ $maid->experience_years }} {{ __('ingaz.year') }}
                                            @endif
                                        </p>
                                        {{-- <p>{{ __('ingaz.monthly_rental') }}: {{ $maid->resolved_salary ?? '0.00' }} {{ __('ingaz.SAR') }} --}}
                                        <p>{{ __('ingaz.monthly_rental') }}: {{ $maid->price ?? '0.00' }} {{ __('ingaz.SAR') }}
                                        </p>
                                        <p>{{ __('ingaz.contract_duration') }} : {{ $maid->duration ?? '' }}</p>
                                    </div>
                                    <p class="guarantee">
                                        {{ __('ingaz.cv_alert') }}
                                    </p>
                                    <div class="actions">
                                        <a data-fancybox="cv1" href="{{ showFile($cvFile) }}" class="icon-btn">
                                            <i class="fa-regular fa-file-pdf"></i>
                                        </a>
                                        @if (!empty($maid->video_link) || (!empty($maid->video) && $maid->video !== '#'))
                                            @php
                                                $videoHref = !empty($maid->video_link)
                                                    ? $maid->video_link
                                                    : showFile($maid->video);
                                                $openAsIframe = !empty($maid->video_link);
                                            @endphp
                                            <a href="{{ $videoHref }}" class="icon-btn"
                                                data-fancybox="video{{ $maid->id }}"
                                                @if ($openAsIframe) data-type="iframe" @endif>
                                                <i class="fa-solid fa-video"></i>
                                            </a>
                                        @endif
                                        <a href="{{ route('site.packages.show', $maid->id) }}" class="btn action-btn">
                                            {{ __('ingaz.labor_rental_request') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                @elseif ($jobType && $jobType->id == 3)
                    <!-- كارد خاص بالنقل -->
                    <div class="col-12 col-md-6 p-2 mb-5" data-type="transfer">
                        <a href="{{ route('site.immediate-recipts.show', $maid->id) }}" class="worker-box-link">
                            <div class="worker-box">
                                <div class="worker-media">
                                    <img src="{{ showFile($maid->image) ?? asset('site/assets/images/worker.webp') }}"
                                        alt="worker"
                                        onerror="this.onerror=null;this.src='{{ asset('site/assets/images/worker.webp') }}';" />
                                </div>
                                <div class="worker-content">
                                    <div class="worker-header">
                                        <span>{{ $maid->age ?? '' }}</span>{{ __('ingaz.year') }}
                                        {{-- @if ($maid->st_code || $maid->int_code)
                                            <div class="worker-id">
                                                {{ implode(' / ', array_filter([$maid->st_code ?? null, $maid->int_code ?? null])) }}
                                            </div>
                                        @endif --}}
                                        @if (!empty($cv_code))
                                            <p class="cv-code">{{ $cv_code }}</p>
                                        @endif
                                    </div>
                                    <h4>{{ $maid->name ?? '' }}</h4>
                                    <div class="workerInfo">
                                        <p>{{ __('ingaz.nationality') }} :
                                            {{ $maid->nationality->sub_title ?? '' }}</p>
                                        <p>{{ __('ingaz.religion') }} : {{ $maid->religion->title ?? '' }}</p>
                                        @if (!empty($maid->passport_num))
                                            <p>{{ __('ingaz.passport_num') }} : {{ $maid->passport_num }}</p>
                                        @endif
                                        @if (!empty($cv_code))
                                            <p>{{ __('ingaz.cv_code') }} : {{ $cv_code }}</p>
                                        @endif
                                        <p>{{ __('ingaz.job') }} :
                                            {{ $maid->jobTitles->first()->title ?? '' }}</p>
                                        <p>{{ __('ingaz.experience') }} :
                                            {{ $maid->experience_status === 'has_experience' ? __('ingaz.has_experience_label') : __('ingaz.no_experience_label') }}
                                            @if (
                                                $maid->experience_status === 'has_experience' &&
                                                    is_numeric($maid->experience_years) &&
                                                    (float) $maid->experience_years > 0)
                                                - {{ $maid->experience_years }} {{ __('ingaz.year') }}
                                            @endif
                                        </p>
                                        <p>{{ __('ingaz.cost_with_tax') }} : {{ $maid->resolved_price ?? '' }}
                                            {{ __('ingaz.SAR') }}</p>
                                        <p>{{ __('ingaz.transfer_duration') }} : {{ __('ingaz.through') }}
                                            {{ $maid->duration ?? '' }} {{ __('ingaz.day') }}</p>
                                    </div>
                                    <p class="guarantee">
                                        {{ __('ingaz.cv_alert') }}
                                    </p>
                                    <div class="actions">
                                        <a data-fancybox="cv1" href="{{ showFile($cvFile) }}" class="icon-btn">
                                            <i class="fa-regular fa-file-pdf"></i>
                                        </a>
                                        @if (!empty($maid->video_link) || (!empty($maid->video) && $maid->video !== '#'))
                                            @php
                                                $videoHref = !empty($maid->video_link)
                                                    ? $maid->video_link
                                                    : showFile($maid->video);
                                                $openAsIframe = !empty($maid->video_link);
                                            @endphp
                                            <a href="{{ $videoHref }}" class="icon-btn"
                                                data-fancybox="video{{ $maid->id }}"
                                                @if ($openAsIframe) data-type="iframe" @endif>
                                                <i class="fa-solid fa-video"></i>
                                            </a>
                                        @endif
                                        <a href="{{ route('site.immediate-recipts.show', $maid->id) }}"
                                            class="btn action-btn">
                                            {{ __('ingaz.Labor_transfer') }}
                                        </a>
                                    </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endif
        @empty
            <div class="card text-center border-0 shadow-sm p-4 mt-3" style="background: #f8f9fa;">
                <div class="card-body">
                    <i class="fa-solid fa-box-open fa-2x text-muted mb-2"></i>
                    <h6 class="text-muted mb-0">{{__("web.no_data_found")}}</h6>
                </div>
            </div>
        @endforelse
        </div>
    </div>
@if ($maids->isNotEmpty())
    <div class="pagination-outer" id="pagination">
        <div class="pagination-style1">
            {{ $maids->links('vendor.pagination.custom_2') }}
        </div>
    </div>
@endif
