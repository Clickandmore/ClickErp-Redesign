<div class="workers-list" id="workers-list">
    <div class="row">
        @forelse ($maids as $maid)
            @php
                $jobType = $maid->jobTypes->first();
            @endphp
                <!-- كارد مشترك (ايجار + نقل خدمات) -->
            @if ($jobType && $jobType->id == 1)
                <div class="col-12 col-md-6 p-2 mb-5" data-type="estqdam">
                    <a href="{{ route('site.all-workers.show', $maid->id) }}" class="worker-box-link">
                        <div class="worker-box">
                            <div class="worker-media">
                                <img src="{{ showFile($maid->image) }}" alt="worker"/>
                            </div>
                            <div class="worker-content">
                                <div class="worker-header">
                                    <div class="worker-age">
                                        <span>{{ $maid->age ?? '' }}</span>{{ __('ingaz.year') }}
                                    </div>
                                    @if($maid->st_code || $maid->int_code)
                                        <!-- اول تلت حروف من اسم المكتب والرقم حسب عدد السيرة المستلمة من المكتب  -->
                                        <div class="worker-id">
                                            {{ implode(' / ', array_filter([$maid->st_code ?? null, $maid->int_code ?? null])) }}
                                        </div>
                                    @endif
                                </div>
                                <h4>{{ $maid->name ?? '' }}</h4>
                                <div class="top-ribbon">
                                    <img src="{{asset('site')}}/assets/images/ribbon.png" alt="عرض خاص"/>
                                    <span> {{ __('ingaz.sale') }}</span>
                                </div>
                                <div class="workerInfo">
                                    <p>{{ __('ingaz.nationality') }} :
                                        {{ $maid->nationality->sub_title ?? '' }}</p>
                                    <p>{{ __('ingaz.religion') }} : {{ $maid->religion->title ?? '' }}</p>
                                    @if(!empty($maid->passport_num))
                                        <p>{{ __('ingaz.passport_num') }} : {{ $maid->passport_num }}</p>
                                    @endif
                                    <p>{{ __('ingaz.job') }} :
                                        {{ $maid->jobTitles->first()->title ?? '' }}</p>
                                    <p>{{ __('ingaz.experience') }} :
                                        {{ $maid->experience_status === 'has_experience' ? __('ingaz.has_experience_label') : __('ingaz.no_experience_label') }}
                                        @if($maid->experience_status === 'has_experience' && is_numeric($maid->experience_years) && (float)$maid->experience_years > 0)
                                            - {{ $maid->experience_years }} {{ __('ingaz.year') }}
                                        @endif
                                    </p>
                                    <p>{{ __('ingaz.cost_with_tax') }} : {{ $maid->salary ?? '' }}
                                        {{ __('ingaz.SAR') }}</p>
                                </div>
                                <!-- worker sallery  -->
                                <div class="worker-salary">
                                    <p class="old-salary">
                                        قبل الخصم : <span>7000 ريال</span>
                                    </p>
                                    <p class="new-salary">
                                        الآن : <span>6500 ريال</span>
                                    </p>
                                </div>
                                <p class="guarantee">
                                    {{ __('ingaz.cv_alert') }}
                                </p>
                                <div class="actions">
                                    @if(!empty(trim($maid->cv_file ?? '')))
                                        <a href="{{ showFile($maid->cv_file) }}" class="icon-btn" target="_blank">
                                            <i class="fa-regular fa-file-pdf"></i>
                                        </a>
                                    @endif
                                    @if(!empty($maid->video_link) || (!empty($maid->video) && $maid->video !== '#'))
                                        @php
                                            $videoHref = !empty($maid->video_link) ? $maid->video_link : showFile($maid->video);
                                            $openAsIframe = !empty($maid->video_link);
                                        @endphp
                                        <a href="{{ $videoHref }}" class="icon-btn"
                                           data-fancybox="video{{ $maid->id }}"
                                           @if($openAsIframe) data-type="iframe" @endif
                                        >
                                            <i class="fa-solid fa-video"></i>
                                        </a>
                                    @endif
                                    <a href="{{ route('site.all-workers.show', $maid->id) }}" class="btn action-btn">
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
                    <a href="{{ route('site.packages.show', $maid->id ) }}" class="worker-box-link">
                        <div class="worker-box">
                            <div class="worker-media">
                                <img src="{{ showFile($maid->image) }}" alt="worker"/>
                            </div>
                            <div class="worker-content">
                                <div class="worker-header">
                                    <span>{{ $maid->age ?? '' }}</span>{{ __('ingaz.year') }}
                                    @if($maid->st_code || $maid->int_code)
                                        <div class="worker-id">
                                            {{ implode(' / ', array_filter([$maid->st_code ?? null, $maid->int_code ?? null])) }}
                                        </div>
                                    @endif
                                </div>
                                <h4>{{ $maid->name ?? '' }}</h4>
                                <div class="workerInfo">
                                    <p>{{ __('ingaz.nationality') }} : {{ $maid->nationality->sub_title ?? '' }}</p>
                                    <p>{{ __('ingaz.religion') }} : {{ $maid->religion->title ?? '' }}</p>
                                    @if(!empty($maid->passport_num))
                                        <p>{{ __('ingaz.passport_num') }} : {{ $maid->passport_num }}</p>
                                    @endif
                                    <p>{{ __('ingaz.job') }} :
                                        {{ $maid->jobTitles->first()->title ?? '' }}</p>
                                    <p>{{ __('ingaz.experience') }} :
                                        {{ $maid->experience_status === 'has_experience' ? __('ingaz.has_experience_label') : __('ingaz.no_experience_label') }}
                                        @if($maid->experience_status === 'has_experience' && is_numeric($maid->experience_years) && (float)$maid->experience_years > 0)
                                            - {{ $maid->experience_years }} {{ __('ingaz.year') }}
                                        @endif
                                    </p>
                                    <p>{{ __('ingaz.monthly_rental') }}
                                        : {{ $maid->salary ?? '' }} {{ __('ingaz.SAR') }}
                                    </p>
                                    <p>{{ __('ingaz.contract_duration') }} : {{ $maid->duration ?? '' }}
                                        {{ __('ingaz.month') }}</p>
                                </div>
                                <p class="guarantee">
                                    {{ __('ingaz.cv_alert') }}
                                </p>
                                <div class="actions">
                                    <a data-fancybox="cv2" href="{{ showFile($maid->cv_preview) }}" class="icon-btn">
                                        <i class="fa-regular fa-file-pdf"></i>
                                    </a>
                                    @if(!empty($maid->video_link) || (!empty($maid->video) && $maid->video !== '#'))
                                        @php
                                            $videoHref = !empty($maid->video_link) ? $maid->video_link : showFile($maid->video);
                                            $openAsIframe = !empty($maid->video_link);
                                        @endphp
                                        <a href="{{ $videoHref }}" class="icon-btn"
                                           data-fancybox="video{{ $maid->id }}"
                                           @if($openAsIframe) data-type="iframe" @endif
                                        >
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
                                <img src="{{ showFile($maid->image) }}" alt="worker"/>
                            </div>
                            <div class="worker-content">
                                <div class="worker-header">
                                    <span>{{ $maid->age ?? '' }}</span>{{ __('ingaz.year') }}
                                    @if($maid->st_code || $maid->int_code)
                                        <div class="worker-id">
                                            {{ implode(' / ', array_filter([$maid->st_code ?? null, $maid->int_code ?? null])) }}
                                        </div>
                                    @endif
                                </div>
                                <h4>{{ $maid->name ?? '' }}</h4>
                                <div class="workerInfo">
                                    <p>{{ __('ingaz.nationality') }} :
                                        {{ $maid->nationality->sub_title ?? '' }}</p>
                                    <p>{{ __('ingaz.religion') }} : {{ $maid->religion->title ?? '' }}</p>
                                    @if(!empty($maid->passport_num))
                                        <p>{{ __('ingaz.passport_num') }} : {{ $maid->passport_num }}</p>
                                    @endif
                                    <p>{{ __('ingaz.job') }} :
                                        {{ $maid->jobTitles->first()->title ?? '' }}</p>
                                    <p>{{ __('ingaz.experience') }} :
                                        {{ $maid->experience_status === 'has_experience' ? __('ingaz.has_experience_label') : __('ingaz.no_experience_label') }}
                                        @if($maid->experience_status === 'has_experience' && is_numeric($maid->experience_years) && (float)$maid->experience_years > 0)
                                            - {{ $maid->experience_years }} {{ __('ingaz.year') }}
                                        @endif
                                    </p>
                                    <p>{{ __('ingaz.cost_with_tax') }} : {{ $maid->salary ?? '' }}
                                        {{ __('ingaz.SAR') }}</p>
                                    <p>{{ __('ingaz.transfer_duration') }} : {{ __('ingaz.through') }}
                                        {{ $maid->duration ?? '' }} {{ __('ingaz.day') }}</p>
                                </div>
                                <p class="guarantee">
                                    {{ __('ingaz.cv_alert') }}
                                </p>
                                <div class="actions">
                                    <a data-fancybox="cv2" href="{{ showFile($maid->cv_preview) }}" class="icon-btn">
                                        <i class="fa-regular fa-file-pdf"></i>
                                    </a>
                                    @if(!empty($maid->video) && $maid->video !== '#')
                                        <a href="{{ showFile($maid->video) }}" class="icon-btn"
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
<div class="pagination-outer" id="pagination">
    <div class="pagination-style1">
        {{ $maids->links('vendor.pagination.custom_2') }}
    </div>
</div>
