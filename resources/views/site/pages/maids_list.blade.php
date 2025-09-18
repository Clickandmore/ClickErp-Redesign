<div class="workers-list" id="workers-list">
    <div class="row">
        @foreach ($maids as $maid)
            @php
            $jobType = $maid->jobTypes->first();
            @endphp
            <!-- كارد مشترك (ايجار + نقل خدمات) -->
            @if ($jobType && $jobType->id == 1)
                <div class="col-12 col-md-6 p-2 mb-5" data-type="estqdam">
                    <a href="{{ route('site.all-workers.show', $maid->id) }}" class="worker-box-link">
                        <div class="worker-box">
                            <div class="worker-media">
                                <img src="{{ showFile($maid->image) }}" alt="worker" />
                            </div>
                            <div class="worker-content">
                                <div class="worker-header">
                                    <div class="worker-age">
                                        <span>{{ $maid->age ?? '' }}</span>{{ __('ingaz.year') }}
                                    </div>
                                    <div class="worker-id">{{ $maid->st_code ?? '' }} /
                                        {{ $maid->int_code ?? '' }}</div>
                                </div>
                                <h4>{{ $maid->name ?? '' }}</h4>
                                <div class="workerInfo">
                                    <p>{{ __('ingaz.nationality') }} :
                                        {{ $maid->nationality->title ?? '' }}</p>
                                    <p>{{ __('ingaz.religion') }} : {{ $maid->religion->title ?? '' }}
                                    </p>
                                    <p>{{ __('ingaz.job') }} :
                                        {{ $maid->jobTitles->first()->title ?? '' }}</p>
                                    <p>{{ __('ingaz.experience') }} :
                                        {{ $maid->experience_years != null && $maid->experience_years > 0 ? $maid->experience_years : '' }}
                                        {{ __('ingaz.year') }}</p>
                                    <p>{{ __('ingaz.cost_with_tax') }} : {{ $maid->salary ?? '' }}
                                        {{ __('ingaz.SAR') }}</p>
                                    <p>{{ __('ingaz.rent_duration') }} : {{ __('ingaz.through') }}
                                        {{ $maid->duration ?? '' }} {{ __('ingaz.day') }}</p>
                                </div>
                                <p class="guarantee">
                                    {{ __('ingaz.cv_alert') }}
                                </p>
                                <div class="actions">
                                    <a data-fancybox="cv1" href="{{ showFile($maid->cv_preview) }}" class="icon-btn">
                                        <i class="fa-regular fa-file-pdf"></i>
                                    </a>
                                    <a data-fancybox="video1" href="{{ showFile($maid->video_preview) }}" class="icon-btn">
                                        <i class="fa-solid fa-video"></i>
                                    </a>
                                    <button class="btn">{{ __('ingaz.recruitment') }}</button>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @elseif ($jobType && $jobType->id == 2)
                <!-- كارد خاص بالإيجار -->
                <div class="col-12 col-md-6 p-2 mb-5" data-type="egar">
                    <a href="{{ route('site.all-workers.show', $maid->id) }}" class="worker-box-link">
                        <div class="worker-box">
                            <div class="worker-media">
                                <img src="{{ showFile($maid->image) }}" alt="worker" />
                            </div>
                            <div class="worker-content">
                                <div class="worker-header">
                                    <span>{{ $maid->age ?? '' }}</span>{{ __('ingaz.year') }}
                                    <div class="worker-id">{{ $maid->st_code ?? '' }} /
                                        {{ $maid->int_code ?? '' }}</div>
                                </div>
                                <h4>{{ $maid->name ?? '' }}</h4>
                                <div class="workerInfo">
                                    <p>{{ __('ingaz.nationality') }} : {{ $maid->nationality->title ?? '' }}</p>
                                    <p>{{ __('ingaz.religion') }} : {{ $maid->religion->title ?? '' }}</p>
                                    <p>{{ __('ingaz.job') }} :
                                        {{ $maid->jobTitles->first()->title ?? '' }}</p>
                                    <p>{{ __('ingaz.experience') }} :
                                        {{ $maid->experience_years != null && $maid->experience_years > 0 ? $maid->experience_years : '' }}
                                    </p>
                                    <p>{{ __('ingaz.monthly_rental') }} : {{ $maid->salary ?? '' }} {{ __('ingaz.SAR') }}
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
                                    <a data-fancybox="video2" href="{{ showFile($maid->video_preview) }}" class="icon-btn">
                                        <i class="fa-solid fa-video"></i>
                                    </a>
                                    <button class="btn action-btn">{{ __('ingaz.Labor_rental') }}</button>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @elseif ($jobType && $jobType->id == 3)
                <!-- كارد خاص بالنقل -->
                <div class="col-12 col-md-6 p-2 mb-5" data-type="transfer">
                    <a href="{{ route('site.all-workers.show', $maid->id) }}" class="worker-box-link">
                        <div class="worker-box">
                            <div class="worker-media">
                                <img src="{{ showFile($maid->image) }}" alt="worker" />
                            </div>
                            <div class="worker-content">
                                <div class="worker-header">
                                    <span>{{ $maid->age ?? '' }}</span>{{ __('ingaz.year') }}
                                    <div class="worker-id">{{ $maid->st_code ?? '' }} /
                                        {{ $maid->int_code ?? '' }}</div>
                                </div>
                                <h4>{{ $maid->name ?? '' }}</h4>
                                <div class="workerInfo">
                                    <p>{{ __('ingaz.nationality') }} :
                                        {{ $maid->nationality->title ?? '' }}</p>
                                    <p>{{ __('ingaz.religion') }} : {{ $maid->religion->title ?? '' }}
                                    </p>
                                    <p>{{ __('ingaz.job') }} :
                                        {{ $maid->jobTitles->first()->title ?? '' }}</p>
                                    <p>{{ __('ingaz.experience') }} :
                                        {{ $maid->experience_years != null && $maid->experience_years > 0 ? $maid->experience_years : '' }}
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
                                    <a data-fancybox="video2" href="{{ showFile($maid->video_preview) }}" class="icon-btn">
                                        <i class="fa-solid fa-video"></i>
                                    </a>
                                    <button class="btn action-btn">{{ __('ingaz.Labor_transfer') }}</button>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @else
                <div class="col-12 col-md-6 p-2 mb-5">
                    <div class="worker-box">
                        <h4>{{__('ingaz.no_workers_found')}}</h4>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</div>
<div class="pagination-outer" id="pagination">
    <div class="pagination-style1">
        {{ $maids->links('vendor.pagination.custom_2') }}
    </div>
</div>
