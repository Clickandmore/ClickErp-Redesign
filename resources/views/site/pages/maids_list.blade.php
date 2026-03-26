<div class="workers-list" id="workers-list">
    <div class="row">
        {{-- @dd($maids[1]->job_types) --}}
        {{-- @dd($maids) --}}
        @forelse ($maids as $maid)
            @php
                $currentType = request('type') ?? 'all';
                $typeToJobTypeId = [
                    'estqdam' => 1,
                    'egar' => 2,
                    'transfer' => 3,
                ];
                $targetJobTypeId = $typeToJobTypeId[$currentType] ?? null;
                $todayDate = now()->toDateString();
                $offerStartsRaw = $maid->getRawOriginal('offer_starts_at');
                $offerExpiresRaw = $maid->getRawOriginal('offer_expires_at');
                $offerStarts =
                    is_string($offerStartsRaw) && $offerStartsRaw !== '' ? substr($offerStartsRaw, 0, 10) : null;
                $offerExpires =
                    is_string($offerExpiresRaw) && $offerExpiresRaw !== '' ? substr($offerExpiresRaw, 0, 10) : null;

                $offerPriceRaw = $maid->offer_price !== null ? (float) $maid->offer_price : null;
                $salaryRaw = $maid->resolved_salary !== null ? (float) $maid->resolved_salary : null;
                $priceRaw = $maid->resolved_price !== null ? (float) $maid->resolved_price : null;

                $offerStartsOk = empty($offerStarts) || $offerStarts <= $todayDate;
                $offerExpiresOk = empty($offerExpires) || $offerExpires >= $todayDate;
                $offerActive = $offerPriceRaw !== null && $offerStartsOk && $offerExpiresOk;

                /* الحساب دايمًا يكون بالقيم الخام */
                $salaryAfterOfferRaw = $offerActive && $salaryRaw !== null ? $salaryRaw - $offerPriceRaw : null;

                /* التنسيق للعرض فقط */
                $offerPriceDisplay = $offerPriceRaw !== null ? formatMoney($offerPriceRaw) : null;
                $salaryDisplay = $salaryRaw !== null ? formatMoney($salaryRaw) : null;
                $priceDisplay = $priceRaw !== null ? formatMoney($priceRaw) : null;
                $salaryAfterOffer = $salaryAfterOfferRaw !== null ? formatMoney($salaryAfterOfferRaw) : null;
                $cvFile = $maid->cv_file ?? $maid->cv_preview;
                $cv_code = $maid->cv_code ? $maid->cv_code : null;
                $listingJobTypes = $maid->site_listing_job_types ?? $maid->jobTypes;
            @endphp
            <!-- كارد مشترك (ايجار + نقل خدمات) -->
            @foreach ($listingJobTypes as $jobType)
                @if ($currentType !== 'all' && $targetJobTypeId && $jobType->id !== $targetJobTypeId)
                    @continue
                @endif
                @if ($jobType->id == 1)
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
                                        @else
                                            @if ($maid->recruitmentOffice)
                                                @php
                                                    $officeTitle = optional($maid->recruitmentOffice)->title ?? '';
                                                    $officeShort = \Illuminate\Support\Str::limit($officeTitle, 3, '');
                                                @endphp
                                                @php
                                                    $officeMaidCount = $maid->recruitmentOffice?->maids()->count() ?? 0;
                                                @endphp
                                                <p class="cv-code">
                                                    <span>{{ $officeMaidCount }}</span>
                                                    <span>/</span>
                                                    <span>{{ $officeShort }}</span>
                                                </p>
                                            @endif
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
                                            <span>{{ $maid->nationality?->sub_title ?? '' }}</span>
                                        </p>
                                        <p>{{ __('ingaz.religion') }} :
                                            <span>{{ $maid->religion?->title ?? '' }}</span>
                                        </p>
                                        @if (!empty($maid->passport_num))
                                            <p>{{ __('ingaz.passport_num') }} : <span>{{ $maid->passport_num }}</span>
                                            </p>
                                        @endif
                                        <p>{{ __('ingaz.job') }} :
                                            <span>{{ $maid->jobTitles->first()?->title ?? '' }}</span>
                                        </p>

                                        @php
                                            $result = null;
                                            if ($maid->experience_status === 'has_experience') {
                                                $result = format_experience($maid->experience_years);
                                            }
                                        @endphp

                                        <p>{{ __('ingaz.experience') }} :
                                            <span>
                                                {{ $maid->experience_status === 'has_experience' ? __('ingaz.has_experience_label') : __('ingaz.no_experience_label') }}
                                                @if (isset($result))
                                                    - <span class="text-wrap">{{ $result }}</span>
                                                @endif
                                            </span>
                                        </p>
                                        <p class="service-type">{{ __('ingaz.service_type') }} :
                                            <span>{{ __('ingaz.recruitment') }}</span>
                                        </p>
                                    </div>
                                    <div class="worker-salary">
                                        <p class="monthly-salary mb-0">
                                            {{ __('ingaz.month_salary') }} :
                                            <span>{{ $salaryDisplay ?? '--' }}
                                                {{ $salaryDisplay ? __('ingaz.SAR') : '' }}</span>
                                        </p>
                                        @if ($offerActive && $offerPriceDisplay)
                                            @if ($priceRaw !== null && $offerPriceRaw < $priceRaw && $priceDisplay)
                                                <p class="old-salary mb-1">
                                                    {{ __('ingaz.before_discount') }} :
                                                    <span>{{ $salaryDisplay }} {{ __('ingaz.SAR') }}</span>
                                                </p>
                                            @endif
                                            <p class="new-salary mb-0">
                                                {{ __('ingaz.now_price') }} :
                                                <span>{{ $salaryAfterOffer }} {{ __('ingaz.SAR') }}</span>
                                            </p>
                                        @elseif($priceDisplay)
                                            <p class="new-salary mb-0">
                                                {{ __('ingaz.cost_with_tax') }} :
                                                <span>{{ $priceDisplay }} {{ __('ingaz.SAR') }}</span>
                                            </p>
                                        @endif
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
                                            {{-- {{ __('ingaz.recruitment_request') }} --}}
                                            {{ __('ingaz.cv_details') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                @elseif ($jobType->id == 2)
                    <!-- كارد خاص بالإيجار -->
                    <div class="col-12 col-md-6 p-2 mb-5" data-type="egar">
                        {{-- <a href="{{ route('site.packages.show', $maid->id) }}" class="worker-box-link"> --}}
                        <a href="{{ route('site.all-workers.show', $maid->id) }}?type=egar" class="worker-box-link">
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
                                            <span>{{ $maid->nationality?->sub_title ?? '' }}</span>
                                        </p>
                                        <p>{{ __('ingaz.religion') }} :
                                            <span>{{ $maid->religion?->title ?? '' }}</span>
                                        </p>
                                        @if (!empty($maid->passport_num))
                                            <p>{{ __('ingaz.passport_num') }} : <span>{{ $maid->passport_num }}</span>
                                            </p>
                                        @endif
                                        <p>{{ __('ingaz.job') }} :
                                            <span>{{ $maid->jobTitles->first()?->title ?? '' }}</span>
                                        </p>
                                        @php
                                            $result = null;
                                            if ($maid->experience_status === 'has_experience') {
                                                $result = format_experience($maid->experience_years);
                                            }
                                        @endphp

                                        <p>{{ __('ingaz.experience') }} :
                                            <span>
                                                {{ $maid->experience_status === 'has_experience' ? __('ingaz.has_experience_label') : __('ingaz.no_experience_label') }}
                                                @if ($result)
                                                    - <span class="text-wrap">{{ $result }}</span>
                                                @endif
                                            </span>
                                        </p>
                                        {{-- <p>{{ __('ingaz.monthly_rental') }}: {{ $maid->resolved_salary ?? '0.00' }} {{ __('ingaz.SAR') }} --}}
                                        <p>{{ __('ingaz.monthly_rental') }}: <span>
                                                {{ formatMoney($maid->price ?? 0) }}
                                                {{ __('ingaz.SAR') }}
                                            </span>
                                        </p>
                                        <p>{{ __('ingaz.contract_duration') }} :
                                            <span>{{ $maid->duration ?? '' }}</span>
                                        </p>
                                        <p class="service-type">{{ __('ingaz.service_type') }} :
                                            <span>{{ __('ingaz.labor_hire') }}</span>
                                        </p>
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
                                        {{-- <a href="{{ route('site.packages.show', $maid->id) }}" class="btn action-btn">
                                        {{ __('ingaz.labor_rental_request') }}
                                    </a> --}}
                                        <a href="{{ route('site.all-workers.show', $maid->id) }}?type=egar"
                                            class="btn action-btn">
                                            {{ __('ingaz.labor_rental_request') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                @elseif ($jobType->id == 3)
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
                                        @else
                                            @if ($maid->recruitmentOffice)
                                                @php
                                                    $officeTitle = optional($maid->recruitmentOffice)->title ?? '';
                                                    $officeShort = \Illuminate\Support\Str::limit($officeTitle, 3, '');
                                                @endphp
                                                @php
                                                    $officeMaidCount = $maid->recruitmentOffice?->maids()->count() ?? 0;
                                                @endphp
                                                <p class="cv-code">
                                                    <span>{{ $officeMaidCount }}</span>
                                                    <span>/</span>
                                                    <span>{{ $officeShort }}</span>
                                                </p>
                                            @endif
                                        @endif
                                    </div>
                                    <h4>{{ $maid->name ?? '' }}</h4>
                                    <div class="workerInfo">
                                        <p>{{ __('ingaz.nationality') }} :
                                            <span>{{ $maid->nationality?->sub_title ?? '' }}</span>
                                        </p>
                                        <p>{{ __('ingaz.religion') }} :
                                            <span>{{ $maid->religion?->title ?? '' }}</span>
                                        </p>
                                        @if (!empty($maid->passport_num))
                                            <p>{{ __('ingaz.passport_num') }} : <span>{{ $maid->passport_num }}</span>
                                            </p>
                                        @endif
                                        <p>{{ __('ingaz.job') }} :
                                            <span>{{ $maid->jobTitles->first()?->title ?? '' }}</span>
                                        </p>

                                        @php
                                            $result = null;
                                            if ($maid->experience_status === 'has_experience') {
                                                $result = format_experience($maid->experience_years);
                                            }
                                        @endphp

                                        <p>{{ __('ingaz.experience') }} :
                                            <span>
                                                {{ $maid->experience_status === 'has_experience' ? __('ingaz.has_experience_label') : __('ingaz.no_experience_label') }}
                                                @if ($result)
                                                    - <span class="text-wrap">{{ $result }}</span>
                                                @endif
                                            </span>
                                        </p>
                                        <p>{{ __('ingaz.cost_with_tax') }} :
                                            <span>
                                                {{ $maid->resolved_price !== null ? formatMoney($maid->resolved_price) : '' }}
                                                {{ __('ingaz.SAR') }}
                                            </span>
                                        </p>
                                        <p>{{ __('ingaz.transfer_duration') }} :
                                            <span>
                                                {{ __('ingaz.through') }}
                                                {{ $maid->duration ?? '' }} {{ __('ingaz.day') }}
                                            </span>
                                        </p>
                                        <p class="service-type">{{ __('ingaz.service_type') }} :
                                            <span>{{ __('ingaz.immediate_delivery') }}</span>
                                        </p>
                                    </div>
                                    <p class="guarantee">
                                        {{ __('ingaz.cv_alert') }}
                                    </p>
                                    <div class="actions">
                                        <a data-fancybox="cv1" href="{{ showFile($cvFile) }}" class="icon-btn">
                                            <i class="fa-regular fa-file-pdf"></i>
                                        </a>
                                        @if (!empty($maid->video_link) || !empty($maid->video))
                                            @php
                                                $videoHref = !empty($maid->video_link)
                                                    ? $maid->video_link
                                                    : showFile($maid->video);
                                            @endphp

                                            <a href="{{ $videoHref }}" class="icon-btn"
                                                data-fancybox="video{{ $maid->id }}">
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
            @endforeach
        @empty
            <div class="card text-center border-0 shadow-sm p-4 mt-3" style="background: #f8f9fa;">
                <div class="card-body">
                    <i class="fa-solid fa-box-open fa-2x text-muted mb-2"></i>
                    <h6 class="text-muted mb-0">{{ __('web.no_data_found') }}</h6>
                </div>
            </div>
        @endforelse
    </div>
</div>
@if ($maids->isNotEmpty())
    <div class="pagination-outer" id="pagination">
        <div class="pagination-style1">
            {{ $maids->appends(request()->query())->links('vendor.pagination.custom_2') }} </div>
    </div>
@endif
