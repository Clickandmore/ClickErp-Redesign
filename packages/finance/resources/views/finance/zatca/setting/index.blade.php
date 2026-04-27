@extends('click.layout.indexs.index')
@section("style")

@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')

    <div class="card">
        <div class="card-body">
            <div class="row row-deck">

                <div class="col-lg-8 col-md-12">
                    <form class="row g-3 from-submit-global" method="post"
                          action="{{$storeRoute}}"
                          enctype="multipart/form-data">
                        <div class="row">
                            <h4 class="mt-4 mt-lg-0"><strong>{{__('finance::zatca.update zatca setting')}}</strong></h4>

                            <div class="my-3">
                                <span class="text-muted ms-3">{{__('finance::zatca.sure zatca setting')}}</span>
                            </div>

                            <div class="col-md-6">
                                <div class="my-2">
                                    <label for="TextInput"
                                           class="form-label">{{ trans('finance::zatca.company name') }}</label>
                                    <fieldset class="form-icon-group right-icon position-relative">
                                        <div class="form-icon position-absolute">
                                            <i class="far fa-home"></i>
                                        </div>
                                        <input type="text"
                                               name="VAT_name"
                                               value="{{ isset($zatca_setting) ? $zatca_setting->VAT_name : '' }}"
                                               class="form-control">
                                    </fieldset>
                                </div>
                                <div class="my-2">
                                    <label for="TextInput"
                                           class="form-label">{{ trans('finance::zatca.CRN_number') }}</label>
                                    <fieldset class="form-icon-group right-icon position-relative">
                                        <div class="form-icon position-absolute">
                                            <i class="far fa-file-contract"></i>
                                        </div>
                                        <input type="text"
                                               name="CRN_number"
                                               value="{{ isset($zatca_setting) ? $zatca_setting->CRN_number : '' }}"
                                               class="form-control">
                                    </fieldset>
                                </div>
                                <div class="my-2">
                                    <label for="TextInput"
                                           class="form-label">{{ trans('finance::zatca.city_subdivision') }}</label>
                                    <fieldset class="form-icon-group right-icon position-relative">
                                        <div class="form-icon position-absolute">
                                            <i class="far fa-map-marker-alt"></i>
                                        </div>
                                        <input type="text"
                                               name="city_subdivision"
                                               value="{{ isset($zatca_setting) ? $zatca_setting->city_subdivision : '' }}"
                                               class="form-control">
                                    </fieldset>
                                </div>

                                <div class="my-2">
                                    <label for="TextInput"
                                           class="form-label">{{ trans('finance::zatca.street name') }}</label>
                                    <fieldset class="form-icon-group right-icon position-relative">
                                        <div class="form-icon position-absolute">
                                            <i class="far fa-map-marker-alt"></i>
                                        </div>
                                        <input type="text"
                                               name="street"
                                               value="{{ isset($zatca_setting) ? $zatca_setting->street : '' }}"
                                               class="form-control">
                                    </fieldset>
                                </div>
                                <div class="my-2">
                                    <label for="TextInput"
                                           class="form-label">{{ trans('finance::zatca.postal_zone') }}</label>
                                    <fieldset class="form-icon-group right-icon position-relative">
                                        <div class="form-icon position-absolute">
                                            <i class="far fa-bars"></i>
                                        </div>
                                        <input type="text"
                                               name="postal_zone"
                                               value="{{ isset($zatca_setting) ? $zatca_setting->postal_zone : '' }}"
                                               class="form-control">
                                    </fieldset>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="my-2">
                                    <label for="TextInput"
                                           class="form-label">{{ trans('finance::zatca.VAT_number') }} {{ trans('finance::zatca.VAT_number example') }}</label>
                                    <fieldset class="form-icon-group right-icon position-relative">
                                        <div class="form-icon position-absolute">
                                            <i class="far fa-file"></i>
                                        </div>
                                        <input type="text"
                                               name="VAT_number"
                                               value="{{ isset($zatca_setting) ? $zatca_setting->VAT_number : '' }}"
                                               class="form-control">
                                    </fieldset>
                                </div>
                                <div class="my-2">
                                    <label for="TextInput"
                                           class="form-label">{{ trans('finance::zatca.city') }}</label>
                                    <fieldset class="form-icon-group right-icon position-relative">
                                        <div class="form-icon position-absolute">
                                            <i class="far fa-map-marker-alt"></i>
                                        </div>
                                        <input type="text"
                                               name="city"
                                               value="{{ isset($zatca_setting) ? $zatca_setting->city : '' }}"
                                               class="form-control">
                                    </fieldset>
                                </div>
                                <div class="my-2">
                                    <label for="TextInput"
                                           class="form-label">{{ trans('finance::zatca.building number') }}</label>
                                    <fieldset class="form-icon-group right-icon position-relative">
                                        <div class="form-icon position-absolute">
                                            <i class="far fa-building"></i>
                                        </div>
                                        <input type="text"
                                               name="building"
                                               value="{{ isset($zatca_setting) ? $zatca_setting->building : '' }}"
                                               class="form-control">
                                    </fieldset>
                                </div>
                                <div class="my-2">
                                    <label for="TextInput"
                                           class="form-label">{{ trans('finance::zatca.branch_name') }}</label>
                                    <fieldset class="form-icon-group right-icon position-relative">
                                        <div class="form-icon position-absolute">
                                            <i class="far fa-buildings"></i>
                                        </div>
                                        <input type="text"
                                               name="branch_name"
                                               value="{{ isset($zatca_setting) ? $zatca_setting->branch_name : '' }}"
                                               class="form-control">
                                    </fieldset>
                                </div>
                                <div class="my-2">
                                    <label for="TextInput"
                                           class="form-label">{{ trans('finance::zatca.OTP') }}</label>
                                    <fieldset class="form-icon-group right-icon position-relative">
                                        <div class="form-icon position-absolute">
                                            <i class="far fa-lock"></i>
                                        </div>
                                        <input type="text"
                                               name="OTP"
                                               value="{{ isset($zatca_setting) ? $zatca_setting->OTP : '' }}"
                                               class="form-control">
                                    </fieldset>
                                </div>

                            </div>
                            <div class="col-md-12 mt-3">
                                <div class="form-check">
                                    <input type="radio"
                                           class="form-check-input"
                                           id="developerMode"
                                           name="production"
                                           value="0"
                                        {{ isset($zatca_setting) && $zatca_setting->production == 0 ? 'checked' : '' }}>
                                    <label class="form-check-label" for="developerMode">
                                        {{ trans('finance::zatca.developer mode') }}
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input type="radio"
                                           class="form-check-input"
                                           id="simulationMode"
                                           name="production"
                                           value="2"
                                        {{ isset($zatca_setting) && $zatca_setting->production == 2 ? 'checked' : '' }}>
                                    <label class="form-check-label" for="simulationMode">
                                        {{ trans('finance::zatca.simulation mode') }}
                                        ({{ trans('finance::zatca.should get OTP') }})
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input type="radio"
                                           class="form-check-input"
                                           id="productionMode"
                                           name="production"
                                           value="1"
                                        {{ isset($zatca_setting) && $zatca_setting->production == 1 ? 'checked' : '' }}>
                                    <label class="form-check-label" for="productionMode">
                                        {{ trans('finance::zatca.live mode') }}
                                        ({{ trans('finance::zatca.should get OTP') }})
                                    </label>
                                </div>
                            </div>
                            <div class="d-flex mt-3 mb-3">
                                <button class="btn btn-primary mx-2" type="submit"><i class="fa fa-repeat me-1"></i>
                                    @if($zatca_setting->csr)
                                        {{__('finance::zatca.renew csr')}}
                                    @else
                                        {{__('finance::zatca.Create csr')}}
                                    @endif
                                </button>
                            </div>
                            <div class="p-3 bg-light border rounded">
                                @if($zatca_setting->csr_expire_date)
                                    <div class="d-flex align-items-center">
                                        <i class="far fa-calendar-alt text-primary me-2"></i>
                                        <p class="mb-0">
                                            <span>{{__('finance::zatca.date end of csr')}} :</span>
                                            <span class="fw-bolder">
                                                {{ $zatca_setting->csr_expire_date }}
                                            </span>
                                        </p>
                                    </div>
                                    @php
                                        $remainingDays = \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($zatca_setting->csr_expire_date), false);
                                    @endphp
                                    <div class="mt-2 d-flex align-items-center">
                                        @if($remainingDays > 0)
                                            <i class="far fa-hourglass-half text-warning me-2"></i>
                                            <span class="text-success">{{__('finance::zatca.remaining days')}}: {{ $remainingDays }} {{__('finance::zatca.day')}}</span>
                                        @else
                                            <i class="fas fa-exclamation-circle text-danger me-2"></i>
                                            <span class="text-danger">{{__('finance::zatca.expired csr')}}</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-danger">
                                          {{__('finance::zatca.no csr was not created')}}
                                            <i class="fa fa-times-circle"></i>
                                    </span>
                                @endif
                            </div>

                        </div>
                    </form>
                </div>
                <div class="col-lg-4 col-md-12">
                    <img src="{{asset('admin/assets/ill.svg')}}" style="filter: none" alt="" class="img-fluid">
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')

@endsection
