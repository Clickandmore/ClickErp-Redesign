<div class="sidebar p-2 py-md-3 @@cardClass">
    <div class="container-fluid">

        <div class="title-text d-flex align-items-center mb-4 mt-1">
            <h4 class="sidebar-title mb-0 flex-grow-1"><span class="sm-txt"></span>
                {{-- <span>
                    @if(isset($settings) && isset($settings->website_name))
                        {{$settings->website_name}}
                    @endif
                </span> --}}
            </h4>
            <div class="dropdown morphing scale-right">
                <a class="dropdown-toggle more-icon" href="#" role="button" data-bs-toggle="dropdown"><i
                        class="fa fa-ellipsis-h"></i></a>
                <ul class="dropdown-menu shadow border-0 p-2 mt-2" data-bs-popper="none">
                    <li class="fw-bold px-2">روابط سريعة</li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    {{-- @if (Auth::guard('admin')->user()->hasPermission('الإعدادات-انشاء')) --}}
                    <li><a class="dropdown-item" href="{{route('settings.index')}}" aria-current="page">
                            {{ __('ingaz.settings') }}</a>
                    </li>
                    {{-- @endif --}}
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                </ul>
            </div>
        </div>

        {{-- <div class="create-new py-3 mb-2">
            <div class="d-flex"> --}}
                {{-- <select class="form-select rounded-pill me-1">
                    <option selected>Select Project</option>
                    <option value="1">Luno University</option>
                    <option value="2">Book Manager</option>
                    <option value="3">Luno Sass App</option>
                </select>
                <button class="btn bg-primary text-white rounded-circle" data-bs-toggle="modal"
                    data-bs-target="#CreateNew" type="button"><i class="fa fa-plus"></i></button>--}}
                {{--
            </div>
        </div> --}}

        <div class="main-menu flex-grow-1">
            @php
                $newStatus = \App\Enums\ingaz\ContractStatusEnum::New;
                $msanedStatus = \App\Enums\ingaz\ContractStatusEnum::Msaned;
                $tafwedStatus = \App\Enums\ingaz\ContractStatusEnum::Tafwed;
                $taffezStatus = \App\Enums\ingaz\ContractStatusEnum::Taffez;
                $tazkerStatus = \App\Enums\ingaz\ContractStatusEnum::Tazker;
                $underWarrantyStatus = \App\Enums\ingaz\ContractStatusEnum::UnderWarranty;
                $backStatus = \App\Enums\ingaz\ContractStatusEnum::Back;
                $arrivalStatus = \App\Enums\ingaz\ContractStatusEnum::Arrival;
                $sariStatus = \App\Enums\ingaz\ContractStatusEnum::Sari;
                $closedStatus = \App\Enums\ingaz\ContractStatusEnum::Closed;
            @endphp
            <ul class="menu-list">
                <!-- Dashboard Section -->
                <li class="divider py-2 lh-sm"><span class="small">{{ helperTrans('ingaz.dashboard') }}</span></li>
                @if (@AdminGuardHasPerm(\App\PermKeys::DASHBOARD_VIEW))
                <li>
                    <a class="m-link {{ (Route::currentRouteName() == 'dashboard.index') ? 'active' : '' }}"
                        href="{{route('dashboard.index')}}">
                        <i class="fa fa-home"></i>
                        <span class="ms-2">{{ helperTrans('ingaz.home') }}</span>
                    </a>
                </li>
                @endif

                <!-- Clients Section -->
                <li>
                    <a class="m-link {{ (Route::currentRouteName() == 'clients.index') ? 'active' : '' }}"
                        href="{{route('clients.index')}}">
                        <i class="fa fa-user"></i>
                        <span class="ms-2"> {{ helperTrans('ingaz.clients') }}</span>
                    </a>
                </li>

                <!-- CV Management Section -->
                <li class="collapsed">
                    <a class="m-link" data-bs-toggle="collapse" data-bs-target="#cvManagement" href="#">
                        <i class="fa fa-file-text"></i>
                        <span class="ms-2">{{ helperTrans('ingaz.cv_management') }}</span>
                        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                    </a>
                    <ul class="sub-menu collapse {{ (in_array(Route::currentRouteName(), ['maids.index', 'noneAvailableMaids', 'maids.create'])) ? 'show' : '' }}" id="cvManagement">
                        @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_CREATE))
                        <li>
                            <a class="m-link {{ (Route::currentRouteName() == 'maids.create') ? 'active' : '' }}"
                                href="{{route('maids.create')}}">
                                <i class="fa fa-plus"></i>
                                <span class="ms-2">{{ helperTrans('ingaz.add_cv') }}</span>
                            </a>
                        </li>
                        @endif
                        @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_VIEW))
                        <li>
                            <a class="m-link {{ (Route::currentRouteName() == 'maids.index') ? 'active' : '' }}"
                                href="{{route('maids.index')}}">
                                <i class="fa fa-list"></i>
                                <span class="ms-2">{{ helperTrans('ingaz.cvs') }}</span>
                            </a>
                        </li>
                        <li>
                            <a class="m-link {{ (Route::currentRouteName() == 'noneAvailableMaids') ? 'active' : '' }}"
                                href="{{route('noneAvailableMaids')}}">
                                <i class="fa fa-times-circle"></i>
                                <span class="ms-2">عاملات باك اوت</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </li>

                <!-- Orders Section -->

                <li class="collapsed">
                    <a class="m-link" data-bs-toggle="collapse" data-bs-target="#my_dashboard2" href="#">
                        <i class="fa fa-shopping-cart"></i>
                        <span class="ms-2"> {{ helperTrans('ingaz.orders') }} </span>
                        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                    </a>
                    <ul class="sub-menu collapse {{ (in_array(Route::currentRouteName(), ['orders.current', 'orders.ended'])) ? 'show' : '' }}"
                        id="my_dashboard2">
                        @if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_VIEW))
                            <li>
                                <a class="m-link {{ !request()->input('status') ? 'active' : '' }}"
                                    href="{{ route('orders.index') }}">
                                    <i class="fa fa-arrow-left"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.all orders') }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="m-link {{ request()->input('status') === 'current' ? 'active' : '' }}"
                                    href="{{ route('orders.index', ['status' => 'current']) }}">
                                    <i class="fa fa-arrow-left"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.new orders') }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="m-link {{ request()->input('status') === 'completed' ? 'active' : '' }}"
                                    href="{{ route('orders.index', ['status' => 'completed']) }}">
                                    <i class="fa fa-arrow-left"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.contracted orders') }}</span>
                                </a>
                            </li>
                        @endif
                        @if (@AdminGuardHasPerm(\App\PermKeys::RETURN_REQUEST_VIEW))
                            <li>
                                <a class="m-link {{ Route::currentRouteName() == 'return_requests.index' ? 'active' : '' }}"
                                    href="{{ route('return_requests.index') }}">
                                    <i class="fa fa-times-circle"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.cancelled_orders') }}</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>


                <li class="collapsed">
                    <a class="m-link" data-bs-toggle="collapse" data-bs-target="#contracts" href="#">
                        <i class="fa fa-file"></i>
                        <span class="ms-2"> {{ helperTrans('ingaz.Recruitment contracts') }} </span>
                        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                    </a>
                    <ul class="sub-menu collapse {{ (in_array(Route::currentRouteName(), ['RecruitmentContracts.index'])) ? 'show' : '' }}"
                        id="contracts">
                        @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CREATE))
                            <li>
                                <a class="m-link" href="{{ route('RecruitmentContract.create') }}">
                                    <i class="icon-star"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.add Recruitment contract') }}</span>
                                </a>
                            </li>
                        @endif
                        @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_VIEW))
                            <li>
                                <a class="m-link" href="{{ route('RecruitmentContract.index') }}">
                                    <i class="icon-star"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.All Recruitment contracts') }}</span>
                                    <span class="badge bg-primary">{{ $contractsCount['all'] }}</span>
                                </a>
                            </li>
                            @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_MSANED))
                            <li>
                                <a class="m-link" href="{{ route('RecruitmentContract.index', ['status' => $newStatus->value]) }}">
                                    <i class="icon-star"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.new Recruitment contracts') }}</span>
                                    <span class="badge bg-primary">{{ $contractsCount['new'] }}</span>
                                </a>
                            </li>
                            @endif
                            <li>
                                <a class="m-link" href="{{ route('RecruitmentContract.index', ['status' => 'pending_maid_selection']) }}">
                                    <i class="icon-star"></i>
                                    <span class="ms-2">بانتظار اختيار العاملة</span>
                                    <span class="badge bg-warning">{{ $contractsCount['pending_maid_selection'] ?? 0 }}</span>
                                </a>
                            </li>
                            @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAFWED))
                            <li>
                                <a class="m-link" href="{{ route('RecruitmentContract.index', ['status' => $msanedStatus->value]) }}">
                                    <i class="icon-star"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.msaned') }}</span>
                                    <span class="badge bg-primary">{{ $contractsCount['msaned'] }}</span>
                                </a>
                            </li>
                            @endif
                            @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAFFEZ))
                            <li>
                                <a class="m-link" href="{{ route('RecruitmentContract.index', ['status' => $tafwedStatus->value]) }}">
                                    <i class="icon-star"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.tafwed') }}</span>&nbsp;
                                    <span class="badge bg-primary">{{ $contractsCount['tafwed'] }}</span>
                                </a>
                            </li>
                            @endif
                            <li>
                                <a class="m-link" href="{{ route('RecruitmentContract.index', ['status' => $taffezStatus->value]) }}">
                                    <i class="icon-star"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.taffez') }}</span>&nbsp;
                                    <span class="badge bg-primary">{{ $contractsCount['taffez'] }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="m-link" href="{{ route('RecruitmentContract.index', ['status' => $tazkerStatus->value]) }}">
                                    <i class="icon-star"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.tazker') }}</span>&nbsp;
                                    <span class="badge bg-primary">{{ $contractsCount['tazker'] }}</span>
                                </a>
                            </li>
                            @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_WARRANTY_VIEW))
                            <li>
                                <a class="m-link" href="{{ route('RecruitmentContract.index', ['status' => $underWarrantyStatus->value]) }}">
                                    <i class="icon-star"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.under_warranty') }}</span>&nbsp;
                                    <span class="badge bg-primary">{{ $contractsCount['arrival'] }}</span>
                                </a>
                            </li>
                            @endif
                            @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_VIEW))
                            <li>
                                <a class="m-link" href="{{ route('RecruitmentContract.index', ['status' => $closedStatus->value]) }}">
                                    <i class="icon-star"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.closed') }}</span>&nbsp;
                                    <span class="badge bg-primary">{{ $contractsCount['closed'] }}</span>
                                </a>
                            </li>
                            @endif
                        @endif
                        @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CANCEL_VIEW))
                            <li>
                                <a class="m-link" href="{{ route('cancel_contracts') }}">
                                    <i class="icon-star"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.cancel_contract_requests') }}</span>
                                </a>
                            </li>
                        @endif
                        @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_VIEW_TRAVEL))
                            <li>
                                <a class="m-link" href="{{ route('travel') }}">
                                    <i class="icon-loop"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.travel') }}</span>
                                </a>
                            </li>
                        @endif
                        @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_RETURN_VIEW))
                    <li>
                        <a class="m-link {{ (Route::currentRouteName() == 'RecruitmentContract.index' && request()->input('status') == $backStatus->value) ? 'active' : '' }}"
                            href="{{ route('RecruitmentContract.index', ['status' => $backStatus->value]) }}">
                            <i class="fa fa-undo"></i>
                            <span class="ms-2">{{ helperTrans('ingaz.back') }}</span>
                        </a>
                    </li>
                @endif
                    </ul>
                </li>

                <!-- Rent Contracts -->
                <li class="collapsed">
                    <a class="m-link" data-bs-toggle="collapse" data-bs-target="#rentcontracts" href="#">
                        <i class="icon-note"></i>
                        <span class="ms-2"> {{ helperTrans('ingaz.Rent contracts') }} </span>
                        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                    </a>
                    <ul class="sub-menu collapse {{ (in_array(Route::currentRouteName(), ['RentContract.index'])) ? 'show' : '' }}"
                        id="rentcontracts">
                        @if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CONTROL))
                            <li>
                                <a class="m-link {{ (Route::currentRouteName() == 'viewFullOrderContract') ? 'active' : '' }}"
                                    href="{{route('viewFullOrderContract')}}">
                                    <i class="fa fa-user"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.add Rent contract') }}</span>
                                </a>
                            </li>
                        @endif
                        @if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_VIEW))
                            <li>
                                <a class="m-link" href="{{ route('RentContract.index') }}">
                                    <i class="icon-loop"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.All Rent contracts') }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="m-link" href="{{ route('RentContract.index', ['status' => $sariStatus->value]) }}">
                                    <i class="icon-star"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.sari') }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="m-link" href="{{ route('RentContract.index', ['status' => $closedStatus->value]) }}">
                                    <i class="icon-star"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.closed') }}</span>
                                </a>
                            </li>
                        @endif
                        @if (@AdminGuardHasPerm(\App\PermKeys::CONTRACT_SETTINGS))
                            <li>
                                <a class="m-link {{ (Route::currentRouteName() == 'contract.settings') ? 'active' : '' }}"
                                    href="{{route('contract.settings')}}">
                                    <i class="fa fa-cogs"></i>
                                    <span class="ms-2"> {{ __('admin.contract_settings') }}</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>

                <!-- Shelter Contracts -->
                <li class="collapsed">
                    <a class="m-link" data-bs-toggle="collapse" data-bs-target="#sheltercontracts" href="#">
                        <i class="icon-home"></i>
                        <span class="ms-2"> {{ __('ingaz.shelter_management') }} </span>
                        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                    </a>
                    <ul class="sub-menu collapse {{ (in_array(Route::currentRouteName(), ['ShelterContract.index', 'ShelterContract.availableForTransfer', 'ShelterContract.deportationStage', 'ShelterContract.generalShelter'])) ? 'show' : '' }}"
                        id="sheltercontracts">
                        @if (@AdminGuardHasPerm(\App\PermKeys::SHELTER_CONTRACT_CONTROL))
                            <li>
                                <a class="m-link" href="{{ route('ShelterContract.create') }}">
                                    <i class="icon-star"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.add_shelter') }}</span>
                                </a>
                            </li>
                        @endif
                        @if (@AdminGuardHasPerm(\App\PermKeys::SHELTER_CONTRACT_VIEW))
                            <li>
                                <a class="m-link {{ Route::currentRouteName() == 'ShelterContract.index' ? 'active' : '' }}" href="{{ route('ShelterContract.index') }}">
                                    <i class="icon-loop"></i>
                                    <span class="ms-2"> داخل الإيواء</span>
                                </a>
                            </li>
                            <li>
                                <a class="m-link {{ Route::currentRouteName() == 'ShelterContract.availableForTransfer' ? 'active' : '' }}" href="{{ route('ShelterContract.availableForTransfer') }}">
                                    <i class="fa fa-user"></i>
                                    <span class="ms-2">متاح للنقل</span>
                                </a>
                            </li>
                            <li>
                                <a class="m-link {{ Route::currentRouteName() == 'ShelterContract.deportationStage' ? 'active' : '' }}" href="{{ route('ShelterContract.deportationStage') }}">
                                    <i class="fa fa-plane"></i>
                                    <span class="ms-2">مرحلة الترحيل</span>
                                </a>
                            </li>
                            {{-- <li>
                                <a class="m-link {{ Route::currentRouteName() == 'ShelterContract.generalShelter' ? 'active' : '' }}" href="{{ route('ShelterContract.generalShelter') }}">
                                    <i class="fa fa-home"></i>
                                    <span class="ms-2">داخل الإيواء</span>
                                </a>
                            </li> --}}
                        @endif
                        {{-- @if (@AdminGuardHasPerm(\App\PermKeys::SHELTER_WORKERS_VIEW))
                            <li>
                                <a class="m-link" href="{{ route('shelter_workers') }}">
                                    <i class="icon-loop"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.shelter_workers') }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="m-link" href="{{ route('viewFullShelterWorkerContract') }}">
                                    <i class="icon-loop"></i>
                                    <span class="ms-2">{{ __('admin.viewFullShelterWorkerContract') }}</span>
                                </a>
                            </li>
                        @endif --}}
                        @if (@AdminGuardHasPerm(\App\PermKeys::SHELTER_PLACES_VIEW))
                            <li>
                                <a class="m-link {{ (Route::currentRouteName() == 'shelter-places.index') ? 'active' : '' }}"
                                    href="{{route('shelter-places.index')}}">
                                    <i class="fa fa-building"></i>
                                    <span class="ms-2"> {{ helperTrans('ingaz.shelter-places') }}</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>

                <!-- Complaints Section -->
                @if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_VIEW))
                <li class="collapsed">
                    <a class="m-link" data-bs-toggle="collapse" data-bs-target="#complaintsMenu" href="#">
                        <i class="fa fa-exclamation-triangle"></i>
                        <span class="ms-2">{{ helperTrans('ingaz.complaints_management') }}</span>
                        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                    </a>
                    <ul class="sub-menu collapse {{ (in_array(Route::currentRouteName(), ['complaints.index', 'complaint-types.index'])) ? 'show' : '' }}" id="complaintsMenu">
                        <li>
                            <a class="m-link {{ Route::currentRouteName() == 'complaints.index' ? 'active' : '' }}" href="{{ route('complaints.index') }}">
                                <i class="fa fa-list"></i>
                                <span class="ms-2">{{ helperTrans('ingaz.complaints') }}</span>
                                @if(isset($unreadComplaintsCount) && $unreadComplaintsCount > 0)
                                    <span class="badge bg-primary">{{ $unreadComplaintsCount }}</span>
                                @endif
                            </a>
                        </li>
                        @if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_TYPE_VIEW))
                        <li>
                            <a class="m-link {{ Route::currentRouteName() == 'complaint-types.index' ? 'active' : '' }}" href="{{ route('complaint-types.index') }}">
                                <i class="fa fa-tags"></i>
                                <span class="ms-2">{{ helperTrans('ingaz.complaint_types') }}</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </li>
                @endif

                <!-- Additional Services Section -->

                @if (@AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_VIEW))
                    <li class="collapsed">
                        <a class="m-link" data-bs-toggle="collapse" data-bs-target="#sponsorshipTransfer" href="#">
                            <i class="icon-directions"></i>
                            <span class="ms-2">{{ helperTrans('ingaz.sponsorship_transfer') }}</span>
                            <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                        </a>
                        <ul class="sub-menu collapse {{ (in_array(Route::currentRouteName(), ['SponsorshipTransfer.create', 'SponsorshipTransfer.available', 'SponsorshipTransfer.trial', 'SponsorshipTransfer.failed', 'SponsorshipTransfer.transferred'])) ? 'show' : '' }}" id="sponsorshipTransfer">
                            <li>
                                <a class="m-link {{ (Route::currentRouteName() == 'SponsorshipTransfer.create') ? 'active' : '' }}"
                                    href="{{ route('SponsorshipTransfer.create') }}">
                                    <i class="fa fa-plus"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.add_transfer_request') }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="m-link {{ (Route::currentRouteName() == 'SponsorshipTransfer.available') ? 'active' : '' }}"
                                    href="{{ route('SponsorshipTransfer.create') }}">
                                    <i class="fa fa-check-circle"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.available_for_transfer') }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="m-link {{ (Route::currentRouteName() == 'SponsorshipTransfer.trial') ? 'active' : '' }}"
                                    href="{{ route('SponsorshipTransfer.create') }}">
                                    <i class="fa fa-clock-o"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.trial_period') }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="m-link {{ (Route::currentRouteName() == 'SponsorshipTransfer.failed') ? 'active' : '' }}"
                                    href="{{ route('SponsorshipTransfer.create') }}">
                                    <i class="fa fa-times-circle"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.trial_failed') }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="m-link {{ (Route::currentRouteName() == 'SponsorshipTransfer.transferred') ? 'active' : '' }}"
                                    href="{{ route('SponsorshipTransfer.create') }}">
                                    <i class="fa fa-check"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.transferred') }}</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif

                <!-- Settings Section -->
                {{-- <li class="divider py-2 lh-sm"><span class="small">الإعدادات</span></li> --}}


                <!-- Maids Settings Section -->
                <li class="collapsed">
                    <a class="m-link" data-bs-toggle="collapse" data-bs-target="#my_dashboard3" href="#">
                        <i class="fa fa-cog"></i>
                        <span class="ms-2">{{ helperTrans('ingaz.maids_data_management') }}</span>
                        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                    </a>
                    <ul class="sub-menu collapse {{ (in_array(Route::currentRouteName(), ['job-titles.index', 'packages.index', 'nationalities.index', 'religions.index', 'countries.index'])) ? 'show' : '' }}"
                        id="my_dashboard3">
                        @if (@AdminGuardHasPerm(\App\PermKeys::NATIONALITIES_VIEW))
                            <li>
                                <a class="m-link" href="{{ route('nationalities.index') }}">
                                    <i class="fa fa-arrow-left"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.nationalities') }}</span>
                                </a>
                            </li>
                        @endif
                        @if (@AdminGuardHasPerm(\App\PermKeys::REC_OFFICES_VIEW))
                        <li>
                            <a class="m-link {{ (Route::currentRouteName() == 'recruitment-offices.index') ? 'active' : '' }}"
                                href="{{route('recruitment-offices.index')}}">
                                <i class="fa fa-building"></i>
                                <span class="ms-2"> {{ helperTrans('ingaz.recruitment-offices') }}</span>
                            </a>
                        </li>
                    @endif
                        @if (@AdminGuardHasPerm(\App\PermKeys::JOB_TITLES_VIEW))
                            <li>
                                <a class="m-link" href="{{ route('job-titles.index') }}">
                                    <i class="fa fa-arrow-left"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.job_titles') }}</span>
                                </a>
                            </li>
                        @endif
                        @if (@AdminGuardHasPerm(\App\PermKeys::PACKAGES_VIEW))
                            <li>
                                <a class="m-link" href="{{ route('packages.index') }}">
                                    <i class="fa fa-arrow-left"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.packages') }}</span>
                                </a>
                            </li>
                        @endif

                        @if (@AdminGuardHasPerm(\App\PermKeys::RELIGIONS_VIEW))
                            <li>
                                <a class="m-link" href="{{ route('religions.index') }}">
                                    <i class="fa fa-arrow-left"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.religions') }}</span>
                                </a>
                            </li>
                        @endif
                        @if (@AdminGuardHasPerm(\App\PermKeys::SKILLS_VIEW))
                            <li>
                                <a class="m-link" href="{{ route('skills.index') }}">
                                    <i class="fa fa-arrow-left"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.skills') }}</span>
                                </a>
                            </li>
                        @endif
                        @if (@AdminGuardHasPerm(\App\PermKeys::USERS_VIEW))
                        <li>
                            <a class="m-link {{ (Route::currentRouteName() == 'countries.index') ? 'active' : '' }}" href="{{ route('countries.index') }}">
                                <i class="fa fa-arrow-left"></i>
                                <span class="ms-2">{{ helperTrans('ingaz.countries') }}</span>
                            </a>
                        </li>
                        @endif

                    </ul>
                </li>

                <!-- Staff Management Section -->
                <li class="collapsed">
                    <a class="m-link" data-bs-toggle="collapse" data-bs-target="#staffManagement" href="#">
                        <i class="fa fa-users"></i>
                        <span class="ms-2">{{ helperTrans('ingaz.staff_management') }}</span>
                        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                    </a>
                    <ul class="sub-menu collapse {{ (in_array(Route::currentRouteName(), ['admins.index', 'dash.roles.index', 'dash.activity.log'])) ? 'show' : '' }}" id="staffManagement">
                        @if (@AdminGuardHasPerm(\App\PermKeys::USERS_VIEW))
                            <li>
                                <a class="m-link {{ (Route::currentRouteName() == 'admins.index') ? 'active' : '' }}"
                                    href="{{route('admins.index')}}">
                                    <i class="fa fa-users"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.users') }}</span>
                                </a>
                            </li>
                        @endif
                        @if (@AdminGuardHasPerm(\App\PermKeys::ROLES_VIEW))
                            <li>
                                <a href="{{ route('dash.roles.index') }}"
                                    class="m-link @if (Route::currentRouteName() == 'dash.roles.index') active @endif">
                                    <i class="fa fa-user"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.permissions') }}</span>
                                </a>
                            </li>
                        @endif
                        @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_VIEW))
                        <li>
                            <a class="m-link {{ (Route::currentRouteName() == 'dash.activity.log') ? 'active' : '' }}"
                                href="{{route('dash.activity.log')}}">
                                <i class="fa fa-history"></i>
                                <span class="ms-2">{{ __('admin.activity_log') }}</span>
                            </a>
                        </li>
                    @endif
                    </ul>
                </li>

                <!-- Content Management Section -->
                {{-- <li class="divider py-2 lh-sm"><span class="small">إدارة المحتوى</span></li> --}}

                  <!-- Website Management Section -->
                  <li class="collapsed">
                    <a class="m-link" data-bs-toggle="collapse" data-bs-target="#websiteManagement" href="#">
                        <i class="fa fa-globe"></i>
                        <span class="ms-2">{{ helperTrans('ingaz.website_management') }}</span>
                        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                    </a>
                    <ul class="sub-menu collapse {{ (in_array(Route::currentRouteName(), ['settings.index', 'about-us.index', 'questions.index', 'dash.settings'])) ? 'show' : '' }}" id="websiteManagement">
                        @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_VIEW))
                            <li>
                                <a class="m-link {{ (Route::currentRouteName() == 'settings.index') ? 'active' : '' }}"
                                    href="{{route('settings.index')}}">
                                    <i class="fa fa-cogs"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.website_settings') }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="m-link {{ (Route::currentRouteName() == 'dash.settings') ? 'active' : '' }}"
                                    href="{{route('dash.settings')}}">
                                    <i class="fa fa-cog"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.additional_settings') }}</span>
                                </a>
                            </li>
                        @endif
                        @if (@AdminGuardHasPerm(\App\PermKeys::ABOUT_US_VIEW))
                            <li>
                                <a class="m-link {{ (Route::currentRouteName() == 'about-us.index') ? 'active' : '' }}"
                                    href="{{route('about-us.index')}}">
                                    <i class="fa fa-info"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.about_us') }}</span>
                                </a>
                            </li>
                        @endif

                        @if (@AdminGuardHasPerm(\App\PermKeys::SLIDERS_VIEW))
                        <li>
                            <a class="m-link {{ (Route::currentRouteName() == 'sliders.index') ? 'active' : '' }}"
                                href="{{route('sliders.index')}}">
                                <i class="fa fa-image"></i>
                                <span class="ms-2"> {{ helperTrans('ingaz.sliders') }}</span>
                            </a>
                        </li>
                    @endif

                    @if (@AdminGuardHasPerm(\App\PermKeys::STATISTICS_VIEW))
                    <li>
                        <a class="m-link {{ (Route::currentRouteName() == 'statistics.index') ? 'active' : '' }}"
                            href="{{route('statistics.index')}}">
                            <i class="fa fa-bar-chart"></i>
                            <span class="ms-2"> {{ helperTrans('ingaz.statistics') }}</span>
                        </a>
                    </li>
                    @endif

                    @if (@AdminGuardHasPerm(\App\PermKeys::FAQ_VIEW))
                    <li>
                        <a class="m-link {{ (Route::currentRouteName() == 'questions.index') ? 'active' : '' }}"
                            href="{{route('questions.index')}}">
                            <i class="fa fa-question"></i>
                            <span class="ms-2">{{ helperTrans('ingaz.faq') }}</span>
                        </a>
                    </li>
                @endif
                    </ul>
                </li>

                @if (@AdminGuardHasPerm(\App\PermKeys::NOTIFICATIONS_CONTROL))
                    <li>
                        <a class="m-link {{ (Route::currentRouteName() == 'notifications.index') ? 'active' : '' }}"
                            href="{{route('notifications.index')}}">
                            <i class="fa fa-bell"></i>
                            <span class="ms-2"> {{ helperTrans('ingaz.notifications') }}</span>
                        </a>
                    </li>
                @endif

                @if (@AdminGuardHasPerm(\App\PermKeys::CONTACT_VIEW))
                    <li>
                        <a class="m-link {{ (Route::currentRouteName() == 'contact.index') ? 'active' : '' }}"
                            href="{{route('contact.index')}}">
                            <i class="fa fa-envelope"></i>
                            <span class="ms-2"> {{ helperTrans('ingaz.contact_us') }}</span>
                        </a>
                    </li>
                @endif
            </ul>
        </div>

        {{-- @if (Auth::guard('admin')->user()->hasPermission('الإعدادات-انشاء')) --}}
        <ul class="menu-list nav navbar-nav flex-row text-center menu-footer-link">
            <li class="nav-item flex-fill p-2">
                <a class="d-inline-block w-100 color-400" href="{{route("settings.index")}}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" fill="currentColor" viewBox="0 0 16 16">
                        <path
                            d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z" />
                        <path class="fill-secondary"
                            d="M3 3.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zM3 6a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9A.5.5 0 0 1 3 6zm0 2.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5z" />
                    </svg>
                </a>
            </li>
            <li class="nav-item flex-fill p-2">
                <a class="d-inline-block w-100 color-400" href="{{route("admin.logout")}}" title="sign-out">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M7.5 1v7h1V1h-1z" />
                        <path class="fill-secondary"
                            d="M3 8.812a4.999 4.999 0 0 1 2.578-4.375l-.485-.874A6 6 0 1 0 11 3.616l-.501.865A5 5 0 1 1 3 8.812z" />
                    </svg>
                </a>
            </li>
        </ul>
        {{-- @endif --}}
    </div>
</div>
