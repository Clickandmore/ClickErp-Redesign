<div class="row w-100 m-auto">
    @foreach ($maids as $maid)
        <div class="col-12 p-lg-2 pe-0 ps-0 pt-3 pb-3">
            <div class="cv">
                <div class="cv_details">
                    <div class="p-5 justify-content-center align-content-center">
                        <a href="#" class="maidViewData justify-content-center align-content-center"
                            data-id="{{ @$maid->id }}">
                            @if ($maid->image != null)
                                <img src="{{ showFile($maid->image) }}" alt="cv1"
                                    style="border-radius: 50%; object-fit: cover; width: 130px; height: 130px;">
                            @else
                                <img src="{{ $maid->cv_preview ? showFile($maid->cv_preview) : showFile($maid->cv_file) }}"
                                    alt="cv1" style="border-radius: 50%; object-fit: cover; width: 130px; height: 130px;">
                            @endif
                        </a>
                    </div>
                    <div class="info">
                        <ul>
                            <li>
                                <span>{{ helperTrans('web.nationality') }}</span>
                                <h3>{{ $maid->nationality->title }}</h3>
                            </li>
                            <li>
                                <span>{{ helperTrans('web.salary') }}</span>
                                <h3>{{ $maid->salary }} ر.س</h3>
                            </li>
                            <li>
                                <span>{{ helperTrans('web.passport_number') }}</span>
                                <h3>{{ $maid->passport_num }}</h3>
                            </li>

                            <li>
                                <span>{{ helperTrans('web.experience_years') }}</span>
                                <h3>{{ $maid->experience_years }}</h3>
                            </li>
                            <li>
                                <span>{{ helperTrans('web.religion') }} </span>
                                <h3>{{ $maid->religion->title }}</h3>
                            </li>
                            @if(!in_array(2, $job_type_id ?? []))
                                <li>
                                    <span>{{ helperTrans('web.recruitment_price') }}</span>
                                    <h3>{{ $maid->price }} ر.س</h3>
                                </li>
                            @endif
                            @if (@$maid->height)
                                <li>
                                    <span>{{ helperTrans('ingaz.height') }}</span>
                                    <h3>{{ @$maid->height }}</h3>
                                </li>
                            @endif
                            @if (@$maid->weight)
                                <li>
                                    <span>{{ helperTrans('ingaz.weight') }}</span>
                                    <h3>{{ @$maid->weight }}</h3>
                                </li>
                            @endif
                            <li>
                                <span>{{ helperTrans('web.occupation') }}</span>
                                <h3>{{ $type->title }}</h3>
                            </li>
                            @if ($type->languages)
                                <li>
                                    <span>{{ helperTrans('web.languages she talk') }}</span>
                                    <h3>{{ $type->languages }}</h3>
                                </li>
                            @endif

                        </ul>
                    </div>
                </div>

                @if (Auth::check())
                    @if ($type->id == 1)
                        <div class="button">
                            <a href="{{ route('web.maids.show', ['maid' => $maid->id, 'order_type' => 1]) }}">{{ $type->title }}</a>
                        </div>
                    @elseif($type->id == 2)
                        <div class="button">
                            <a href="{{ route('web.maids.show', ['maid' => $maid->id, 'order_type' => 2]) }}">إيجار العمالة</a>
                        </div>
                    @elseif ($type->id == 3)
                        <div class="button">
                            <a href="{{ route('web.maids.show', ['maid' => $maid->id, 'order_type' => 3]) }}">{{ $type->title }}</a>
                        </div>
                    @else
                        <div class="button">
                            <a type="su" href="https://wa.me/" class="whatsapp" target="_blank">
                                نقل خدمات
                                <i class="fa-brands fa-whatsapp"></i>
                            </a>
                        </div>
                    @endif
                @else
                    <div class="button">
                        <a
                            href="{{ route('web.login', ['maid_id' => $maid->id, 'order_type' => $type->id]) }}">{{ $type->title }}</a>
                    </div>
                @endif

            </div>
        </div>
    @endforeach

    @include('web.ingaz.maids.sub.maid_modal_view')


</div>
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="paginations">
                {{ $maids->appends(request()->input())->links('vendor.pagination.custom') }}
            </div>
        </div>
    </div>
</div>