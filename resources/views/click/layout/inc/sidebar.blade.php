<div class="sidebar p-2 py-md-3 @@cardClass">
    <div class="container-fluid p-0">

        <div class="title-text d-flex align-items-center mb-4 mt-1">
            <h4 class="sidebar-title mb-0 flex-grow-1"><span class="sm-txt"></span>
                {{-- <span>
                    @if (isset($settings) && isset($settings->website_name))
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
                    <li><a class="dropdown-item" href="{{ route('settings.index') }}" aria-current="page">
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
            data-bs-target="#CreateNew" type="button"><i class="fa fa-plus"></i></button> --}}
        {{--
    </div>
</div> --}}

        <div class="main-menu flex-grow-1">
@php
    $contractsCount = SystemModuleEnabled('operations')
        ? app(\App\Services\RecruitmentContractSidebarCountService::class)->counts(request())
        : [];
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
                $completedStatus = \App\Enums\ingaz\ContractStatusEnum::Completed;

                $canViewCrmModule = @AdminGuardHasPerm(\App\PermKeys::CRM_MODULE);
                $canViewHrModule = @AdminGuardCanViewPackageModule('hr');
                $canViewFinanceModule = @AdminGuardCanViewPackageModule('finance');

                $canViewCrm = $canViewCrmModule || @AdminGuardHasAnyPerm([\App\PermKeys::CLIENTS_VIEW, \App\PermKeys::CLIENTS_CREATE, \App\PermKeys::CLIENTS_EDIT, \App\PermKeys::CLIENTS_DELETE, \App\PermKeys::CLIENTS_EXPORT, \App\PermKeys::CLIENTS_SHOW, \App\PermKeys::CLIENTS_SETTINGS]);
                $canManageRentContractTerms = @AdminGuardHasAnyPerm([\App\PermKeys::RENT_CONTRACT_TERMS_VIEW, \App\PermKeys::RENT_CONTRACT_TERMS_CREATE, \App\PermKeys::RENT_CONTRACT_TERMS_EDIT, \App\PermKeys::RENT_CONTRACT_TERMS_DELETE, \App\PermKeys::RENT_CONTRACT_VIEW, \App\PermKeys::RENT_CONTRACT_CREATE, \App\PermKeys::RENT_CONTRACT_EDIT, \App\PermKeys::RENT_CONTRACT_DELETE, \App\PermKeys::RENT_CONTRACT_CONTROL, \App\PermKeys::CONTRACT_SETTINGS]);
                $canViewRentContracts = @AdminGuardHasAnyPerm([\App\PermKeys::RENT_CONTRACT_VIEW, \App\PermKeys::RENT_CONTRACT_CREATE, \App\PermKeys::RENT_CONTRACT_EDIT, \App\PermKeys::RENT_CONTRACT_DELETE, \App\PermKeys::RENT_CONTRACT_CONTROL, \App\PermKeys::RENT_CONTRACT_TERMS_VIEW, \App\PermKeys::RENT_CONTRACT_TERMS_CREATE, \App\PermKeys::RENT_CONTRACT_TERMS_EDIT, \App\PermKeys::RENT_CONTRACT_TERMS_DELETE, \App\PermKeys::CONTRACT_SETTINGS, \App\PermKeys::RENTAL_ORDERS_VIEW]);
                $canViewShelterContracts = @AdminGuardHasAnyPerm([\App\PermKeys::SHELTER_CONTRACT_VIEW, \App\PermKeys::SHELTER_CONTRACT_CREATE, \App\PermKeys::SHELTER_CONTRACT_CONTROL, \App\PermKeys::SHELTER_CONTRACT_ADDITION_CREATE, \App\PermKeys::SHELTER_CONTRACT_INSIDE_VIEW, \App\PermKeys::SHELTER_CONTRACT_AVAILABLE_FOR_TRANSFER_VIEW, \App\PermKeys::SHELTER_CONTRACT_DEPORTATION_STAGE_VIEW, \App\PermKeys::SHELTER_CONTRACT_DEPORTED_VIEW, \App\PermKeys::SHELTER_PLACES_VIEW, \App\PermKeys::SHELTER_PLACES_CREATE, \App\PermKeys::SHELTER_PLACES_EDIT, \App\PermKeys::SHELTER_PLACES_DELETE, \App\PermKeys::SHELTER_PLACES_CONTROL]);
                $canViewLogistics = @AdminGuardHasAnyPerm([\App\PermKeys::TRAVEL_VIEW, \App\PermKeys::TRAVEL_CREATE, \App\PermKeys::TRAVEL_EDIT, \App\PermKeys::TRAVEL_DELETE, \App\PermKeys::TRAVEL_CONTROL]);
                $canViewFinancialRequests = @AdminGuardHasAnyPerm([\App\PermKeys::FINANCIAL_REQUESTS_VIEW, \App\PermKeys::FINANCIAL_REQUESTS_CREATE, \App\PermKeys::FINANCIAL_REQUESTS_DELETE, \App\PermKeys::FINANCIAL_REQUESTS_CHANGE_STATUS, \App\PermKeys::FINANCIAL_REQUESTS_APPROVE, \App\PermKeys::FINANCIAL_REQUESTS_REQUEST_CLOSING, \App\PermKeys::FINANCIAL_REQUESTS_CLOSE, \App\PermKeys::FINANCIAL_REQUESTS_CONTROL]);
                $canViewOperationalCommissions = @SystemModuleEnabled('commissions') && @AdminGuardHasAnyPerm([\App\PermKeys::COMMISSIONS_VIEW, \App\PermKeys::COMMISSIONS_CALCULATE, \App\PermKeys::COMMISSIONS_SETTINGS]);
            @endphp
            <ul class="menu-list">
                <!-- Dashboard Section -->
                <li class="divider py-2 lh-sm"><span class="small">{{ helperTrans('ingaz.dashboard') }}</span></li>
                @if (@AdminGuardCanAccessDashboard())
                    <li>
                        <a class="m-link {{ Route::currentRouteName() == 'dashboard.index' ? 'active' : '' }}"
                            href="{{ \App\Support\SystemModuleManager::defaultDashboardUrl() }}">
                            <i class="fa fa-home me-2"></i>
                            <span class="ms-2">{{ helperTrans('ingaz.home') }}</span>
                        </a>
                    </li>
                @endif
                @php
                    $supportedLocales = array_keys(config('laravellocalization.supportedLocales', []));
                    $firstSegment = request()->segment(1);
                    $moduleSegment = in_array($firstSegment, $supportedLocales, true)
                        ? request()->segment(2)
                        : $firstSegment;

                    $isHr = $moduleSegment === 'hr';
                    $isFinance = $moduleSegment === 'finance';
                    $isZatcka = $moduleSegment === 'zatca';
                    $isCrm = $moduleSegment === 'crm';
                    $isDashboardHome = Route::currentRouteName() === 'dashboard.index';
                    $isOfficeManagementRoute =
                        request()->routeIs('offices.*') ||
                        request()->routeIs('offices-file.*') ||
                        request()->routeIs('admin.agent-imports.*');
                    $isOperationsManagementOpen =
                        $isDashboardHome ||
                        $isOfficeManagementRoute ||
                        request()->routeIs('commissions.marketers.index') ||
                        request()->routeIs('commissions.marketers.show') ||
                        request()->routeIs('commissions.marketers.preview') ||
                        request()->routeIs('commissions.marketers.approve') ||
                        request()->routeIs('commissions.settings.*');
                @endphp
                @if (@SystemModuleEnabled('operations'))
                <li class="{{ $isOperationsManagementOpen ? '' : 'collapsed' }}">
                    <a class="m-link" data-bs-toggle="collapse" data-bs-target="#Operations_Management" href="#">
                        <i class="fa fa-tasks me-2"></i>
                        <span class="ms-2">{{ helperTrans('ingaz.Operations_Management') }}</span>
                        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                    </a>
                    <ul class="sub-menu collapse {{ $isOperationsManagementOpen ? 'show' : '' }}"
                        id="Operations_Management" data-default-open="{{ $isOperationsManagementOpen ? 'true' : 'false' }}">
                        <!-- CV Management Section -->
                        <li class="collapsed">
                            <a class="m-link" data-bs-toggle="collapse" data-bs-target="#cvManagement" href="#">
                                <i class="fa fa-file-text"></i>
                                <span class="ms-2">{{ helperTrans('ingaz.cv_management') }}</span>
                                <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                            </a>
                            <ul class="sub-menu collapse {{ in_array(Route::currentRouteName(), ['maids.index', 'noneAvailableMaids', 'maids.deleted_cvs', 'maids.create', 'maids.rental.index', 'maids.rental.create']) ? 'show' : '' }}"
                                id="cvManagement">
                                @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_CREATE))
                                    <li>
                                        <a class="m-link {{ Route::currentRouteName() == 'maids.create' ? 'active' : '' }}"
                                            href="{{ route('maids.create') }}">
                                            <i class="fa fa-plus"></i>
                                            <span class="ms-2">{{ helperTrans('ingaz.add_cv') }}</span>
                                        </a>
                                    </li>
                                @endif
                                @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_VIEW))
                                    <li>
                                        <a class="m-link {{ Route::currentRouteName() == 'maids.index' ? 'active' : '' }}"
                                            href="{{ route('maids.index') }}">
                                            <i class="fa fa-list"></i>
                                            <span class="ms-2">{{ helperTrans('ingaz.cvs') }}</span>
                                        </a>
                                    </li>
                                @endif
                                @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_RENTAL_VIEW))
                                    <li>
                                        <a class="m-link {{ in_array(Route::currentRouteName(), ['maids.rental.index', 'maids.rental.create']) ? 'active' : '' }}"
                                            href="{{ route('maids.rental.index') }}">
                                            <i class="fas fa-history"></i>
                                            <span class="ms-2">سير عاملات الإيجار</span>
                                        </a>
                                    </li>
                                @endif
                                @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_BACKOUT_VIEW))
                                    <li>
                                        <a class="m-link {{ Route::currentRouteName() == 'noneAvailableMaids' ? 'active' : '' }}"
                                            href="{{ route('noneAvailableMaids') }}">
                                            <i class="fa fa-times-circle"></i>
                                            <span class="ms-2">عاملات باك اوت</span>
                                        </a>
                                    </li>
                                @endif
                                @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_DELETED_VIEW))
                                    <li>
                                        <a class="m-link {{ Route::currentRouteName() == 'maids.deleted_cvs' ? 'active' : '' }}"
                                            href="{{ route('maids.deleted_cvs') }}">
                                            <i class="fa fa-trash"></i>
                                            <span class="ms-2">{{ helperTrans('ingaz.deleted_cvs') }}</span>
                                        </a>
                                    </li>
                                @endif

                            </ul>
                        </li>

                        <!-- Orders Section -->
                        <li class="collapsed">
                            <a class="m-link" data-bs-toggle="collapse" data-bs-target="#my_dashboard2" href="#">
                                <i class="fa fa-shopping-cart"></i>
                                <span class="ms-2">{{ helperTrans('ingaz.orders') }} </span>
                                <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                            </a>
                            <ul class="sub-menu collapse {{ in_array(Route::currentRouteName(), ['orders.index', 'return_requests.index', 'orders.current', 'orders.ended', 'professional_application.index', 'recruitment_requests.index', 'renew-contracts.index', 'known-service-requests.index', 'contact.index', 'contact.show']) ? 'show' : '' }}"
                                id="my_dashboard2">
                                @if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_VIEW))
                                    <li>
                                        <a class="m-link {{ Route::currentRouteName() == 'orders.index' && !request()->input('status') ? 'active' : '' }}"
                                            href="{{ route('orders.index') }}">
                                            <i class="fa fa-arrow-left"></i>
                                            <span class="ms-2">{{ helperTrans('ingaz.all orders') }}</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="m-link {{ Route::currentRouteName() == 'orders.index' && request()->input('status') === 'current' ? 'active' : '' }}"
                                            href="{{ route('orders.index', ['status' => 'current']) }}">
                                            <i class="fa fa-arrow-left"></i>
                                            <span class="ms-2">{{ helperTrans('ingaz.new orders') }}</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="m-link {{ Route::currentRouteName() == 'orders.index' && request()->input('status') === 'completed' ? 'active' : '' }}"
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
                                @if (@AdminGuardHasPerm(\App\PermKeys::PROFESSIONAL_APPLICATION_VIEW))
                                    <li>
                                        <a class="m-link {{ Route::currentRouteName() == 'professional_application.index' ? 'active' : '' }}"
                                            href="{{ route('professional_application.index') }}">
                                            <i class="fa fa-envelope"></i>
                                            <span class="ms-2">
                                                {{ helperTrans('ingaz.request_professional_labor') }}</span>
                                        </a>
                                    </li>
                                @endif
                                @if (@AdminGuardHasPerm(\App\PermKeys::RECRUITMENT_REQUESTS_VIEW))
                                    <li>
                                        <a class="m-link {{ Route::currentRouteName() == 'recruitment_requests.index' ? 'active' : '' }}"
                                            href="{{ route('recruitment_requests.index') }}">
                                            <i class="fa fa-envelope"></i>
                                            <span class="ms-2">طلبات خاصة</span>
                                        </a>
                                    </li>
                                @endif
                                @if (@AdminGuardHasPerm(\App\PermKeys::RENEW_CONTRACTS_VIEW))
                                    <li>
                                        <a class="m-link {{ Route::currentRouteName() == 'renew-contracts.index' ? 'active' : '' }}"
                                            href="{{ route('renew-contracts.index') }}">
                                            <i class="fa fa-envelope"></i>
                                            <span class="ms-2"> {{ helperTrans('ingaz.renew-contracts') }}</span>
                                        </a>
                                    </li>
                                @endif
                                @if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_VIEW))
                                    <li>
                                        <a class="m-link {{ Route::currentRouteName() == 'known-service-requests.index' ? 'active' : '' }}"
                                            href="{{ route('known-service-requests.index') }}">
                                            <i class="fa fa-envelope"></i>
                                            <span class="ms-2">طلبات معروفة </span>
                                        </a>
                                    </li>
                                @endif
                                @if (@AdminGuardHasPerm(\App\PermKeys::CONTACT_VIEW))
                                    <li>
                                        <a class="m-link {{ in_array(Route::currentRouteName(), ['contact.index', 'contact.show']) ? 'active' : '' }}"
                                            href="{{ route('contact.index') }}">
                                            <i class="fa fa-comments"></i>
                                            <span class="ms-2">طلبات التواصل</span>
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
                            <ul class="sub-menu collapse {{ in_array(Route::currentRouteName(), ['RecruitmentContracts.index']) ? 'show' : '' }}"
                                id="contracts">
                                @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CREATE))
                                    <li>
                                        <a class="m-link" href="{{ route('RecruitmentContract.create') }}">
                                            <i class="icon-star"></i>
                                            <span
                                                class="ms-2">{{ helperTrans('ingaz.add Recruitment contract') }}</span>
                                        </a>
                                    </li>
                                @endif
                                @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_VIEW))
                                    <li>
                                        <a class="m-link" href="{{ route('RecruitmentContract.index') }}">
                                            <i class="fa fa-list"></i>
                                            <span
                                                class="ms-2">{{ helperTrans('ingaz.All Recruitment contracts') }}</span>&nbsp;&nbsp;
                                            <span class="badge bg-primary ms-2">{{ $contractsCount['all'] }}</span>
                                        </a>
                                    </li>
                                    @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_MSANED))
                                        <li>
                                            <a class="m-link"
                                                href="{{ route('RecruitmentContract.index', ['status' => $newStatus->value]) }}">
                                                <i class="fa fa-plus"></i>
                                                <span
                                                    class="ms-2">{{ helperTrans('ingaz.new Recruitment contracts') }}</span>&nbsp;
                                                <span
                                                    class="badge bg-primary ms-2">{{ $contractsCount['new'] }}</span>
                                            </a>
                                        </li>
                                    @endif
                                    <li>
                                        <a class="m-link"
                                            href="{{ route('RecruitmentContract.index', ['status' => 'pending_maid_selection']) }}">
                                            <i class="fa fa-clock-o"></i>
                                            <span class="ms-2">بانتظار اختيار العاملة</span>&nbsp;
                                            <span
                                                class="badge bg-warning ms-2">{{ $contractsCount['pending_maid_selection'] ?? 0 }}</span>
                                        </a>
                                    </li>
                                    @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAFWED))
                                        <li>
                                            <a class="m-link"
                                                href="{{ route('RecruitmentContract.index', ['status' => $msanedStatus->value]) }}">
                                                <i class="fa fa-plus"></i>
                                                <span
                                                    class="ms-2">{{ helperTrans('ingaz.msaned') }}</span>&nbsp;&nbsp;
                                                <span
                                                    class="badge bg-primary ms-2">{{ $contractsCount['msaned'] }}</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAFFEZ))
                                        <li>
                                            <a class="m-link"
                                                href="{{ route('RecruitmentContract.index', ['status' => $tafwedStatus->value]) }}">
                                                <i class="fa fa-clock-o"></i>
                                                <span
                                                    class="ms-2">{{ helperTrans('ingaz.tafwed') }}</span>&nbsp;&nbsp;
                                                <span
                                                    class="badge bg-primary ms-2">{{ $contractsCount['tafwed'] }}</span>
                                            </a>
                                        </li>
                                    @endif
                                    <li>
                                        <a class="m-link"
                                            href="{{ route('RecruitmentContract.index', ['status' => $taffezStatus->value]) }}">
                                            <i class="fa fa-clock-o"></i>
                                            <span class="ms-2">{{ helperTrans('ingaz.taffez') }}</span>&nbsp;&nbsp;
                                            <span class="badge bg-primary ms-2">{{ $contractsCount['taffez'] }}</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="m-link"
                                            href="{{ route('RecruitmentContract.index', ['status' => $tazkerStatus->value]) }}">
                                            <i class="fa fa-clock-o"></i>
                                            <span class="ms-2">{{ helperTrans('ingaz.tazker') }}</span>&nbsp;&nbsp;
                                            <span class="badge bg-primary">{{ $contractsCount['tazker'] }}</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="m-link"
                                            href="{{ route('RecruitmentContract.index', ['status' => 'completed', 'warranty_status' => 'active']) }}">
                                            <i class="fa fa-shield"></i>
                                            <span class="ms-2"> تحت الضمان</span>&nbsp;&nbsp;
                                            <span
                                                class="badge bg-warning">{{ $contractsCount['under_warranty'] }}</span>
                                        </a>
                                    </li>
                                    @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_VIEW))
                                        <li>
                                            <a class="m-link"
                                                href="{{ route('RecruitmentContract.index', ['status' => $backStatus->value]) }}">
                                                <i class="fa fa-undo"></i>
                                                <span
                                                    class="ms-2">{{ helperTrans('ingaz.back_contracts') }}</span>&nbsp;
                                                <span class="badge bg-danger">{{ $contractsCount['back'] }}</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="m-link"
                                                href="{{ route('RecruitmentContract.index', ['status' => 'completed_stage']) }}">
                                                <i class="fa fa-check-circle"></i>
                                                <span class="ms-2">عقود مكتملة</span>&nbsp;&nbsp;
                                                <span
                                                    class="badge bg-primary ms-2">{{ $contractsCount['under_warranty'] ?? 0 }}</span>
                                            </a>
                                        </li>
                                    @endif
                                @endif

                            </ul>
                        </li>

                        <!-- Rent Contracts -->
                        @if ($canViewRentContracts)
                            <li class="collapsed">
                                <a class="m-link" data-bs-toggle="collapse" data-bs-target="#rentcontracts"
                                    href="#">
                                    <i class="fas fa-handshake"></i>
                                    <span class="ms-2"> {{ helperTrans('ingaz.Rent contracts') }} </span>
                                    <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                                </a>
                                <ul class="sub-menu collapse {{ in_array(Route::currentRouteName(), ['RentContract.index', 'RentContract.create', 'RentContract.show', 'RentContract.edit', 'rent-contract-terms.index', 'rent-contract-terms.create', 'rent-contract-terms.edit', 'rent-contract.settings']) ? 'show' : '' }}"
                                    id="rentcontracts">
                                    @if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CREATE))
                                        <li>
                                            <a class="m-link {{ Route::currentRouteName() == 'RentContract.create' ? 'active' : '' }}"
                                                href="{{ route('RentContract.create') }}">
                                                <i class="fas fa-plus"></i>
                                                <span
                                                    class="ms-2">{{ helperTrans('ingaz.add Rent contract') }}</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (@AdminGuardHasPerm(\App\PermKeys::RENTAL_ORDERS_VIEW))
                                        <li>
                                            <a class="m-link {{ Route::currentRouteName() == 'rental-orders.index' ? 'active' : '' }}"
                                                href="{{ route('rental-orders.index') }}">
                                                <i class="fas fa-shopping-cart"></i>
                                                <span class="ms-2">طلبات الإيجار</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_VIEW))
                                        <li>
                                            <a class="m-link {{ Route::currentRouteName() == 'RentContract.index' && !request('status') ? 'active' : '' }}"
                                                href="{{ route('RentContract.index') }}">
                                                <i class="fas fa-list"></i>
                                                <span
                                                    class="ms-2">{{ helperTrans('ingaz.All Rent contracts') }}</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="m-link {{ Route::currentRouteName() == 'RentContract.index' && request('status') == 'new' ? 'active' : '' }}"
                                                href="{{ route('RentContract.index', ['status' => 'new']) }}">
                                                <i class="fas fa-file-plus text-primary"></i>
                                                <span class="ms-2">العقود الجديدة</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="m-link {{ Route::currentRouteName() == 'RentContract.index' && request('status') == 'active' ? 'active' : '' }}"
                                                href="{{ route('RentContract.index', ['status' => 'active']) }}">
                                                <i class="fas fa-check-circle text-success"></i>
                                                <span class="ms-2">العقود النشطة</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="m-link {{ Route::currentRouteName() == 'RentContract.index' && request('status') == 'completed' ? 'active' : '' }}"
                                                href="{{ route('RentContract.index', ['status' => 'completed']) }}">
                                                <i class="fas fa-flag-checkered text-info"></i>
                                                <span class="ms-2">العقود المكتملة</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="m-link {{ Route::currentRouteName() == 'RentContract.index' && request('status') == 'cancelled' ? 'active' : '' }}"
                                                href="{{ route('RentContract.index', ['status' => 'cancelled']) }}">
                                                <i class="fas fa-times-circle text-danger"></i>
                                                <span class="ms-2">العقود الملغية</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (@AdminGuardHasPerm(\App\PermKeys::CONTRACT_SETTINGS))
                                        <li>
                                            <a class="m-link {{ Route::currentRouteName() == 'rent-contract.settings' ? 'active' : '' }}"
                                                href="{{ route('rent-contract.settings') }}">
                                                <i class="fas fa-cog"></i>
                                                <span
                                                    class="ms-2">{{ helperTrans('ingaz.contract_settings') }}</span>
                                            </a>
                                        </li>
                                    @endif

                                    @if ($canManageRentContractTerms)
                                        <li>
                                            <a class="m-link {{ in_array(Route::currentRouteName(), ['rent-contract-terms.index', 'rent-contract-terms.create', 'rent-contract-terms.edit']) ? 'active' : '' }}"
                                                href="{{ route('rent-contract-terms.index') }}">
                                                <i class="fa fa-file-contract"></i>
                                                <span class="ms-2">بنود عقد الإيجار</span>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                        <!-- Shelter Contracts -->
                        @if ($canViewShelterContracts)
                            <li class="collapsed">
                                <a class="m-link" data-bs-toggle="collapse" data-bs-target="#sheltercontracts"
                                    href="#">
                                    <i class="icon-home"></i>
                                    <span class="ms-2"> {{ __('ingaz.shelter_management') }} </span>
                                    <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                                </a>
                                <ul class="sub-menu collapse {{ in_array(Route::currentRouteName(), ['ShelterContract.index', 'ShelterContract.create', 'ShelterContract.edit', 'ShelterContract.availableForTransfer', 'ShelterContract.deportationStage', 'ShelterContract.generalShelter', 'ShelterContract.outside', 'ShelterContract.deported', 'shelter-places.index', 'shelter-places.create', 'shelter-places.edit']) ? 'show' : '' }}"
                                    id="sheltercontracts">
                                    @if (
                                        @AdminGuardHasAnyPerm([
                                            \App\PermKeys::SHELTER_CONTRACT_ADDITION_CREATE,
                                            \App\PermKeys::SHELTER_CONTRACT_CREATE,
                                            \App\PermKeys::SHELTER_CONTRACT_CONTROL,
                                        ]))
                                        <li>
                                            <a class="m-link {{ request()->routeIs('ShelterContract.create') ? 'active' : '' }}"
                                                href="{{ route('ShelterContract.create') }}">
                                                <i class="icon-star"></i>
                                                <span class="ms-2">{{ helperTrans('ingaz.add_shelter') }}</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (
                                        @AdminGuardHasAnyPerm([
                                            \App\PermKeys::SHELTER_CONTRACT_INSIDE_VIEW,
                                            \App\PermKeys::SHELTER_CONTRACT_VIEW,
                                            \App\PermKeys::SHELTER_CONTRACT_CONTROL,
                                        ]))
                                        <li>
                                            <a class="m-link {{ Route::currentRouteName() == 'ShelterContract.index' ? 'active' : '' }}"
                                                href="{{ route('ShelterContract.index') }}">
                                                <i class="icon-loop"></i>
                                                <span class="ms-2"> داخل الإيواء</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (
                                        @AdminGuardHasAnyPerm([
                                            \App\PermKeys::SHELTER_CONTRACT_INSIDE_VIEW,
                                            \App\PermKeys::SHELTER_CONTRACT_VIEW,
                                            \App\PermKeys::SHELTER_CONTRACT_CONTROL,
                                        ]))
                                        <li>
                                            <a class="m-link {{ Route::currentRouteName() == 'ShelterContract.outside' ? 'active' : '' }}"
                                                href="{{ route('ShelterContract.outside') }}">
                                                <i class="fas fa-sign-out-alt"></i>
                                                <span class="ms-2"> خارج الإيواء</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (
                                        @AdminGuardHasAnyPerm([
                                            \App\PermKeys::SHELTER_CONTRACT_AVAILABLE_FOR_TRANSFER_VIEW,
                                            \App\PermKeys::SHELTER_CONTRACT_VIEW,
                                            \App\PermKeys::SHELTER_CONTRACT_CONTROL,
                                        ]))
                                        <li>
                                            <a class="m-link {{ Route::currentRouteName() == 'ShelterContract.availableForTransfer' ? 'active' : '' }}"
                                                href="{{ route('ShelterContract.availableForTransfer') }}">
                                                <i class="fa fa-user"></i>
                                                <span class="ms-2">متاح للنقل</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (
                                        @AdminGuardHasAnyPerm([
                                            \App\PermKeys::SHELTER_CONTRACT_DEPORTATION_STAGE_VIEW,
                                            \App\PermKeys::SHELTER_CONTRACT_VIEW,
                                            \App\PermKeys::SHELTER_CONTRACT_CONTROL,
                                        ]))
                                        <li>
                                            <a class="m-link {{ Route::currentRouteName() == 'ShelterContract.deportationStage' ? 'active' : '' }}"
                                                href="{{ route('ShelterContract.deportationStage') }}">
                                                <i class="fa fa-plane"></i>
                                                <span class="ms-2">مرحلة الترحيل</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (
                                        @AdminGuardHasAnyPerm([
                                            \App\PermKeys::SHELTER_CONTRACT_DEPORTED_VIEW,
                                            \App\PermKeys::SHELTER_CONTRACT_VIEW,
                                            \App\PermKeys::SHELTER_CONTRACT_CONTROL,
                                        ]))
                                        <li>
                                            <a class="m-link {{ Route::currentRouteName() == 'ShelterContract.deported' ? 'active' : '' }}"
                                                href="{{ route('ShelterContract.deported') }}">
                                                <i class="fa fa-check-circle"></i>
                                                <span class="ms-2">تم الترحيل</span>
                                            </a>
                                        </li>
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
                                    @if (
                                        @AdminGuardHasAnyPerm([
                                            \App\PermKeys::SHELTER_PLACES_VIEW,
                                            \App\PermKeys::SHELTER_PLACES_CREATE,
                                            \App\PermKeys::SHELTER_PLACES_EDIT,
                                            \App\PermKeys::SHELTER_PLACES_DELETE,
                                            \App\PermKeys::SHELTER_PLACES_CONTROL,
                                        ]))
                                        <li>
                                            <a class="m-link {{ request()->routeIs('shelter-places.index') || request()->routeIs('shelter-places.create') || request()->routeIs('shelter-places.edit') ? 'active' : '' }}"
                                                href="{{ route('shelter-places.index') }}">
                                                <i class="fa fa-building"></i>
                                                <span class="ms-2">
                                                    {{ helperTrans('ingaz.shelter-places') }}</span>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                        @include('admin.layout.inc.sidebar_sponsorship_transfer_menu')

                        <!-- Logistics Services Section -->
                        @if ($canViewLogistics)
                            <li class="collapsed">
                                <a class="m-link" data-bs-toggle="collapse" data-bs-target="#logisticsMenu"
                                    href="#">
                                    <i class="fa fa-truck"></i>
                                    <span class="ms-2">الخدمات اللوجستية</span>
                                    <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                                </a>
                                <ul class="sub-menu collapse {{ in_array(Route::currentRouteName(), ['DeportationTravel.index', 'DeportationTravel.create', 'DeportationTravel.edit', 'travel', 'travel.control', 'travel.edit']) ? 'show' : '' }}"
                                    id="logisticsMenu">
                                    @if (@AdminGuardHasPerm(\App\PermKeys::TRAVEL_VIEW))
                                        <li>
                                            <a class="m-link {{ in_array(Route::currentRouteName(), ['DeportationTravel.index', 'DeportationTravel.create', 'DeportationTravel.edit']) ? 'active' : '' }}"
                                                href="{{ route('DeportationTravel.index') }}">
                                                <i class="fa fa-plane-departure"></i>
                                                <span class="ms-2">رحلات الترحيل</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="m-link {{ in_array(Route::currentRouteName(), ['travel', 'travel.control', 'travel.edit']) ? 'active' : '' }}"
                                                href="{{ route('travel') }}">
                                                <i class="fa fa-plane-arrival"></i>
                                                <span class="ms-2">رحلات الاستقدام</span>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                        <!-- Complaints Section -->
                        @if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_VIEW))
                            <li class="collapsed">
                                <a class="m-link" data-bs-toggle="collapse" data-bs-target="#complaintsMenu"
                                    href="#">
                                    <i class="fa fa-exclamation-triangle"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.complaints_management') }}</span>
                                    <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                                </a>
                                <ul class="sub-menu collapse {{ in_array(Route::currentRouteName(), ['complaints.index', 'complaints.create', 'complaint-types.index']) ? 'show' : '' }}"
                                    id="complaintsMenu">
                                    <li>
                                        <a class="m-link {{ Route::currentRouteName() == 'complaints.index' ? 'active' : '' }}"
                                            href="{{ route('complaints.index') }}">
                                            <i class="fa fa-list"></i>
                                            <span class="ms-2">{{ helperTrans('ingaz.complaints') }}</span>
                                            @if (isset($unreadComplaintsCount) && $unreadComplaintsCount > 0)
                                                <span class="badge bg-primary">{{ $unreadComplaintsCount }}</span>
                                            @endif
                                        </a>
                                    </li>
                                    @if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_CREATE))
                                        <li>
                                            <a class="m-link {{ Route::currentRouteName() == 'complaints.create' ? 'active' : '' }}"
                                                href="{{ route('complaints.create') }}">
                                                <i class="fa fa-plus-circle"></i>
                                                <span class="ms-2">إضافة شكوى جديدة</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_TYPE_VIEW))
                                        <li>
                                            <a class="m-link {{ Route::currentRouteName() == 'complaint-types.index' ? 'active' : '' }}"
                                                href="{{ route('complaint-types.index') }}">
                                                <i class="fa fa-tags"></i>
                                                <span
                                                    class="ms-2">{{ helperTrans('ingaz.complaint_types') }}</span>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif
                        @include('admin.layout.inc.sidebar_external_agents_menu')
                        @if ($canViewFinancialRequests)
                            <li class="collapsed">
                                <a class="m-link" data-bs-toggle="collapse" data-bs-target="#financial_requests_menu_click" href="#">
                                    <i class="fa fa-money-bill-wave"></i>
                                        <span class="ms-2">{{ __('financial_requests.manage') }}</span>
                                    <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                                </a>
                                <ul class="sub-menu collapse {{ request()->routeIs('financial-requests.*') ? 'show' : '' }}"
                                    id="financial_requests_menu_click">
                                    @if (@AdminGuardHasAnyPerm([\App\PermKeys::FINANCIAL_REQUESTS_CREATE, \App\PermKeys::FINANCIAL_REQUESTS_CONTROL]))
                                        <li>
                                            <a class="m-link {{ request()->routeIs('financial-requests.create') ? 'active' : '' }}"
                                                href="{{ route('financial-requests.create') }}">
                                                <i class="fa fa-plus"></i>
                                                <span class="ms-2">{{ __('financial_requests.new') }}</span>
                                            </a>
                                        </li>
                                    @endif
                                    <li>
                                        <a class="m-link {{ request()->routeIs('financial-requests.index') && !request('scope') ? 'active' : '' }}"
                                            href="{{ route('financial-requests.index') }}">
                                            <i class="fa fa-arrow-left"></i>
                                                <span class="ms-2">{{ __('financial_requests.scopes.all') }}</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="m-link {{ request()->routeIs('financial-requests.index') && request('scope') === 'incomplete' ? 'active' : '' }}"
                                            href="{{ route('financial-requests.index', ['scope' => 'incomplete']) }}">
                                            <i class="fa fa-arrow-left"></i>
                                                <span class="ms-2">{{ __('financial_requests.scopes.incomplete') }}</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="m-link {{ request()->routeIs('financial-requests.index') && request('scope') === 'paid' ? 'active' : '' }}"
                                            href="{{ route('financial-requests.index', ['scope' => 'paid']) }}">
                                            <i class="fa fa-arrow-left"></i>
                                                <span class="ms-2">{{ __('financial_requests.scopes.paid') }}</span>
                                        </a>
                                    </li>
                                    @if (@AdminGuardHasAnyPerm([\App\PermKeys::FINANCIAL_REQUESTS_DELETE, \App\PermKeys::FINANCIAL_REQUESTS_CONTROL]))
                                        <li>
                                            <a class="m-link {{ request()->routeIs('financial-requests.index') && request('scope') === 'deleted' ? 'active' : '' }}"
                                                href="{{ route('financial-requests.index', ['scope' => 'deleted']) }}">
                                                <i class="fa fa-trash"></i>
                                                <span class="ms-2">{{ __('financial_requests.scopes.deleted') }}</span>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif
                        @include('commissions.partials.operations_sidebar')
                        <!-- طلبات استرجاع العقود -->
                        {{--                        <li class="collapsed"> --}}
                        {{--                            <a class="m-link" data-bs-toggle="collapse" data-bs-target="#returnRequestsMenu" href="#"> --}}
                        {{--                                <i class="fa fa-undo"></i> --}}
                        {{--                                <span class="ms-2">طلبات استرجاع العقود</span> --}}
                        {{--                                <span class="arrow fa fa-angle-left ms-auto text-end"></span> --}}
                        {{--                            </a> --}}
                        {{--                            <ul class="sub-menu collapse {{ (in_array(Route::currentRouteName(), ['contract-return-requests.index', 'contract-return-requests.show'])) ? 'show' : '' }}" --}}
                        {{--                                id="returnRequestsMenu"> --}}
                        {{--                                --}}
                        {{--                            </ul> --}}
                        {{--                        </li> --}}
                        <!-- Settings Section -->
                        {{-- <li class="divider py-2 lh-sm"><span class="small">الإعدادات</span></li> --}}
                        <!-- Maids Settings Section -->
                        <!-- Staff Management Section -->
                        <!-- Content Management Section -->
                        {{-- <li class="divider py-2 lh-sm"><span class="small">إدارة المحتوى</span></li> --}}
                        <!-- Website Management Section -->
                    </ul>
                </li>
                @endif

                @if ($canViewCrm)
                    <li class="{{ $isCrm || request()->routeIs('admin.sms-templates.*') ? '' : 'collapsed' }}">
                        <a class="m-link" data-bs-toggle="collapse" data-bs-target="#crm" href="#">
                            <i class="fa-regular fa-file-lines me-2"></i>
                            <span class="ms-2">{{ __('ingaz.crm') }}</span>
                            <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                        </a>
                        <ul class="sub-menu collapse {{ $isCrm || request()->routeIs('admin.sms-templates.*') ? 'show' : '' }}"
                            id="crm">
                            <li>
                                <a class="m-link {{ Route::currentRouteName() == 'clients.index' ? 'active' : '' }}"
                                    href="{{ route('clients.index') }}">
                                    <i class="fa fa-user"></i>
                                    <span class="ms-2"> {{ helperTrans('ingaz.clients') }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="m-link {{ request()->routeIs('admin.sms-templates.*') ? 'active' : '' }}"
                                    href="{{ route('admin.sms-templates.index') }}">
                                    <i class="fa fa-sms"></i>
                                    <span class="ms-2">قوالب الرسائل النصية</span>
                                </a>
                            </li>
                            @include('crm::crm')
                        </ul>
                    </li>
                @endif
                @php
                    $supportedLocales = array_keys(config('laravellocalization.supportedLocales', []));
                    $firstSegment = request()->segment(1);
                    $moduleSegment = in_array($firstSegment, $supportedLocales, true)
                        ? request()->segment(2)
                        : $firstSegment;
                    $isHr = $moduleSegment === 'hr';
                @endphp
                <li class="{{ $isHr ? '' : 'collapsed' }}">
                    <a class="m-link" data-bs-toggle="collapse" data-bs-target="#hr" href="#">
                        <i class="fa fa-user-tie me-2"></i>
                        <span class="ms-2">{{ __('ingaz.hr') }}</span>
                        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                    </a>
                    <ul class="sub-menu collapse {{ $isHr ? 'show' : '' }}" id="hr">
                        @include('admin.layout.inc.hr_sidebar_links')
                    </ul>
                </li>
                @php
                    $isFinance =
                        request()->is('finance*') ||
                        request()->routeIs('commissions.finance.*') ||
                        request()->routeIs('commissions.marketers.payments.*');
                @endphp
                <li class="{{ $isFinance ? '' : 'collapsed' }}">
                    <a class="m-link" data-bs-toggle="collapse" data-bs-target="#finance" href="#">
                        <i class="fa fa-dollar-sign me-2"></i>
                        <span class="ms-2">{{ __('ingaz.finance') }}</span>
                        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                    </a>
                    <ul class="sub-menu collapse {{ $isFinance ? 'show' : '' }}" id="finance">
                        @include('finance::finance')
                    </ul>
                </li>
                {{--                @include('finance::stock') --}}
                @include('finance::zatca')
                @include('access-control::menu-group')
                @php
                    $activeSettingsSection = request()->route('section');
                    $isDashboardSettings =
                        request()->routeIs('settings.section') && $activeSettingsSection === 'dashboard';
                    $isWebsiteSettingsRoute =
                        request()->routeIs('settings.index') ||
                        (request()->routeIs('settings.section') && !$isDashboardSettings) ||
                        (request()->routeIs('settings.*') && !request()->routeIs('settings.section'));
                    $websiteManagementOpen =
                        $isWebsiteSettingsRoute ||
                        request()->routeIs('about-us.*') ||
                        request()->routeIs('questions.*') ||
                        request()->routeIs('dash.settings*');
                    $isMoyasarSettingsRoute = request()->routeIs('moyasar-settings.*');
                    $settingsOperationOpen = $isDashboardSettings || $websiteManagementOpen || $isMoyasarSettingsRoute;
                @endphp
                <li class="{{ $settingsOperationOpen ? '' : 'collapsed' }}">
                    <a class="m-link" data-bs-toggle="collapse" data-bs-target="#settingsOperation" href="#">
                        <i class="fa fa-cog fa-lg"></i>
                        <span class="ms-2">اعدادات النظام</span>
                        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                    </a>
                    <ul class="sub-menu collapse {{ $settingsOperationOpen ? 'show' : '' }}" id="settingsOperation">
                        @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_VIEW))
                            <li>
                                <a class="m-link {{ $isDashboardSettings ? 'active' : '' }}"
                                    href="{{ route('settings.section', 'dashboard') }}">
                                    <i class="fa fa-gauge"></i>
                                    <span class="ms-2">{{ helperTrans('ingaz.dashboard_settings') }}</span>
                                </a>
                            </li>
                        @endif
                        @if (@AdminGuardHasPerm(\App\PermKeys::MOYASAR_SETTINGS))
                            <li>
                                <a class="m-link {{ $isMoyasarSettingsRoute ? 'active' : '' }}"
                                    href="{{ route('moyasar-settings.edit') }}">
                                    <i class="fa fa-credit-card"></i>
                                    <span class="ms-2">إعدادات ميسر</span>
                                </a>
                            </li>
                        @endif

                        <li class="{{ $websiteManagementOpen ? '' : 'collapsed' }}">
                            <a class="m-link" data-bs-toggle="collapse" data-bs-target="#websiteManagement"
                                href="#">
                                <i class="fa fa-globe"></i>
                                <span class="ms-2">{{ helperTrans('ingaz.website_management') }}</span>
                                <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                            </a>

                            <ul class="sub-menu collapse {{ $websiteManagementOpen ? 'show' : '' }}"
                                id="websiteManagement">
                                @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_VIEW))
                                    <li>
                                        <a class="m-link {{ $isWebsiteSettingsRoute || request()->routeIs('dash.settings*') ? 'active' : '' }}"
                                            href="{{ route('settings.section', 'general') }}">
                                            <i class="fa fa-cogs"></i>
                                            <span class="ms-2">{{ helperTrans('ingaz.website_settings') }}</span>
                                        </a>
                                    </li>
                                @endif
                                <li class="collapsed">
                                    <a class="m-link" data-bs-toggle="collapse" data-bs-target="#home_settings"
                                        href="#">
                                        <i class="fa fa-cog"></i>
                                        <span class="ms-2">{{ helperTrans('web.home_settings') }}</span>
                                        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                                    </a>

                                    <ul class="sub-menu collapse" id="home_settings">

                                        <li>
                                            <a class="m-link" href="{{ route('fast-news.index') }}">
                                                <i class="fa fa-newspaper"></i>
                                                <span class="ms-2">{{ __('web.fast_news') }}</span>
                                            </a>
                                        </li>

                                        @if (@AdminGuardHasPerm(\App\PermKeys::SLIDERS_VIEW))
                                            <li>
                                                <a class="m-link {{ Route::currentRouteName() == 'sliders.index' ? 'active' : '' }}"
                                                    href="{{ route('sliders.index') }}">
                                                    <i class="fa fa-images"></i>
                                                    <span class="ms-2">{{ helperTrans('ingaz.sliders') }}</span>
                                                </a>
                                            </li>
                                        @endif

                                        <li>
                                            <a class="m-link" href="{{ route('what-do.index') }}">
                                                <i class="fa fa-puzzle-piece"></i>
                                                <span class="ms-2">{{ __('web.what_do') }}</span>
                                            </a>
                                        </li>

                                        @if (@AdminGuardHasAnyPerm([\App\PermKeys::WHY_CHOOSE_US_VIEW, \App\PermKeys::WHY_CHOOSE_US_CONTROL]))
                                            <li>
                                                <a class="m-link {{ request()->routeIs('why-choose-us.*') ? 'active' : '' }}"
                                                    href="{{ route('why-choose-us.edit') }}">
                                                    <i class="fa fa-circle-check"></i>
                                                    <span class="ms-2">{{ __('why_choose_us.sidebar_title') }}</span>
                                                </a>
                                            </li>
                                        @endif

                                        @if (@AdminGuardHasAnyPerm([\App\PermKeys::WHY_US_VIEW, \App\PermKeys::WHY_US_CONTROL]))
                                            <li>
                                                <a class="m-link {{ request()->routeIs('why-us.*') ? 'active' : '' }}"
                                                    href="{{ route('why-us.index') }}">
                                                    <i class="fa fa-star"></i>
                                                    <span class="ms-2">{{ __('why_us.sidebar_title') }}</span>
                                                </a>
                                            </li>
                                        @endif

                                        @if (@AdminGuardHasAnyPerm([\App\PermKeys::FIND_US_VIEW, \App\PermKeys::FIND_US_CONTROL]))
                                            <li>
                                                <a class="m-link {{ request()->routeIs('find-us.*') ? 'active' : '' }}"
                                                    href="{{ route('find-us.index') }}">
                                                    <i class="fa fa-map-location-dot"></i>
                                                    <span class="ms-2">{{ __('find_us.title') }}</span>
                                                </a>
                                            </li>
                                        @endif

                                        @if (@AdminGuardHasPerm(\App\PermKeys::ABOUT_US_VIEW))
                                            <li>
                                                <a class="m-link {{ Route::currentRouteName() == 'about-us.index' ? 'active' : '' }}"
                                                    href="{{ route('about-us.index') }}">
                                                    <i class="fa fa-circle-info"></i>
                                                    <span class="ms-2">{{ __('web.about_us') }}</span>
                                                </a>
                                            </li>
                                        @endif

                                        @if (@AdminGuardHasPerm(\App\PermKeys::FAQ_VIEW))
                                            <li>
                                                <a class="m-link {{ Route::currentRouteName() == 'features.index' ? 'active' : '' }}"
                                                    href="{{ route('features.index') }}">
                                                    <i class="fa fa-star"></i>
                                                    <span class="ms-2">{{ helperTrans('ingaz.features') }}</span>
                                                </a>
                                            </li>
                                        @endif

                                        @if (@AdminGuardHasPerm(\App\PermKeys::NATIONALITIES_VIEW))
                                            <li>
                                                <a class="m-link" href="{{ route('nationalities.index') }}">
                                                    <i class="fa fa-globe"></i>
                                                    <span
                                                        class="ms-2">{{ helperTrans('ingaz.nationalities') }}</span>
                                                </a>
                                            </li>
                                        @endif

                                        @if (@AdminGuardHasPerm(\App\PermKeys::FAQ_VIEW))
                                            <li>
                                                <a class="m-link {{ Route::currentRouteName() == 'services.index' ? 'active' : '' }}"
                                                    href="{{ route('services.index') }}">
                                                    <i class="fa fa-tools"></i>
                                                    <span class="ms-2">{{ helperTrans('ingaz.services') }}</span>
                                                </a>
                                            </li>
                                        @endif

                                        @if (@AdminGuardHasPerm(\App\PermKeys::STATISTICS_VIEW))
                                            <li>
                                                <a class="m-link {{ Route::currentRouteName() == 'statistics.index' ? 'active' : '' }}"
                                                    href="{{ route('statistics.index') }}">
                                                    <i class="fa fa-chart-bar"></i>
                                                    <span class="ms-2">{{ helperTrans('ingaz.statistics') }}</span>
                                                </a>
                                            </li>
                                        @endif

                                        <li>
                                            <a class="m-link" href="{{ route('steps.index') }}">
                                                <i class="fa fa-list-ol"></i>
                                                <span class="ms-2">{{ helperTrans('ingaz.steps') }}</span>
                                            </a>
                                        </li>

                                        @if (@AdminGuardHasAnyPerm([\App\PermKeys::RECRUITMENT_REQUIREMENTS_VIEW, \App\PermKeys::RECRUITMENT_REQUIREMENTS_CONTROL]))
                                            <li>
                                                <a class="m-link {{ request()->routeIs('requiremens.*') ? 'active' : '' }}"
                                                    href="{{ route('requiremens.index') }}">
                                                    <i class="fa fa-list-check"></i>
                                                    <span class="ms-2">
                                                        {{ helperTrans('api.Recruitment requirements') }}
                                                    </span>
                                                </a>
                                            </li>
                                        @endif

                                        <li>
                                            <a class="m-link {{ Route::currentRouteName() == 'partenrs.index' ? 'active' : '' }}"
                                                href="{{ route('partenrs.index') }}">
                                                <i class="fa fa-handshake"></i>
                                                <span class="ms-2">{{ helperTrans('ingaz.partenrs') }}</span>
                                            </a>
                                        </li>

                                        @if (@AdminGuardHasPerm(\App\PermKeys::CONTACT_VIEW))
                                            <li>
                                                <a class="m-link {{ in_array(Route::currentRouteName(), ['contact.index', 'contact.show']) ? 'active' : '' }}"
                                                    href="{{ route('contact.index') }}">
                                                    <i class="fa fa-envelope"></i>
                                                    <span class="ms-2">{{ helperTrans('ingaz.contact_us') }}</span>
                                                </a>
                                            </li>
                                        @endif

                                    </ul>
                                </li>
                                <li>
                                    <a class="m-link" href="{{ route('istiqdam.index') }}">
                                        <i class="fa fa-building"></i>
                                        <span class="ms-2">منصة الاستقدام</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="m-link {{ Request::is('migration/*') || Request::is('migration.php') ? 'active' : '' }}"
                                        href="/migration/new_migration_system.php">
                                        <i class="fa fa-exchange"></i>
                                        <span class="ms-2">نظام نقل البيانات</span>
                                        <span class="badge bg-success ms-2">محدث</span>
                                    </a>
                                </li>

                                {{--                            @if (@AdminGuardHasPerm(\App\PermKeys::STATISTICS_VIEW)) --}}
                                <li>
                                    <a class="m-link {{ Route::currentRouteName() == 'select-workers.index' ? 'active' : '' }}"
                                        href="{{ route('select-workers.index') }}">
                                        <i class="fa fa-bar-chart"></i>
                                        <span class="ms-2"> {{ helperTrans('ingaz.select_workers') }}</span>
                                    </a>
                                </li>
                                {{--                        @endif --}}
                                {{--                            @if (@AdminGuardHasPerm(\App\PermKeys::STATISTICS_VIEW)) --}}
                                <li>
                                    <a class="m-link {{ Route::currentRouteName() == 'select-workers-settings.index' ? 'active' : '' }}"
                                        href="{{ route('select-workers-settings.index') }}">
                                        <i class="fa fa-bar-chart"></i>
                                        <span class="ms-2">
                                            {{ helperTrans('ingaz.select_workers_settings') }}</span>
                                    </a>
                                </li>
                                {{--                        @endif --}}

                                @if (@AdminGuardHasPerm(\App\PermKeys::FAQ_VIEW))
                                    <li>
                                        <a class="m-link {{ Route::currentRouteName() == 'questions.index' ? 'active' : '' }}"
                                            href="{{ route('questions.index') }}">
                                            <i class="fa fa-question"></i>
                                            <span class="ms-2">{{ helperTrans('ingaz.faq') }}</span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                        <li class="{{ in_array(Route::currentRouteName(), ['job-titles.index', 'packages.index', 'nationalities.index', 'religions.index', 'skills.index', 'countries.index', 'cities.index', 'airports.index', 'airports.create', 'airports.edit', 'social-statuses.index']) ? '' : 'collapsed' }}">
                            <a class="m-link" data-bs-toggle="collapse" data-bs-target="#my_dashboard3"
                                href="#">
                                <i class="fa fa-cog"></i>
                                <span class="ms-2">{{ helperTrans('ingaz.recruitment-settings') }}</span>
                                <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                            </a>
                            <ul class="sub-menu collapse {{ in_array(Route::currentRouteName(), ['job-titles.index', 'packages.index', 'nationalities.index', 'religions.index', 'skills.index', 'countries.index', 'cities.index', 'airports.index', 'airports.create', 'airports.edit', 'social-statuses.index']) ? 'show' : '' }}"
                                id="my_dashboard3">
                                @if (@AdminGuardHasPerm(\App\PermKeys::JOB_TITLES_VIEW))
                                    <li>
                                        <a class="m-link" href="{{ route('job-titles.index') }}">
                                            <i class="fa fa-arrow-left"></i>
                                            <span class="ms-2">{{ helperTrans('ingaz.job_titles') }}</span>
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
                                @if (@AdminGuardHasPerm(\App\PermKeys::COUNTRIES_VIEW))
                                    <li>
                                        <a class="m-link {{ Route::currentRouteName() == 'countries.index' ? 'active' : '' }}"
                                            href="{{ route('countries.index') }}">
                                            <i class="fa fa-globe"></i>
                                            <span class="ms-2">{{ helperTrans('ingaz.countries') }}</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="m-link {{ Route::currentRouteName() == 'cities.index' ? 'active' : '' }}"
                                            href="{{ route('cities.index') }}">
                                            <i class="fa fa-city"></i>
                                            <span class="ms-2">المدن</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="m-link {{ request()->routeIs('airports.*') ? 'active' : '' }}"
                                            href="{{ route('airports.index') }}">
                                            <i class="fa fa-plane"></i>
                                            <span class="ms-2">المطارات</span>
                                        </a>
                                    </li>
                                    {{--                            <li> --}}
                                    {{--                                <a class="m-link {{ (Route::currentRouteName() == 'countries.index') ? 'active' : '' }}" --}}
                                    {{--                                   href="{{ route('countries.index') }}"> --}}
                                    {{--                                    <i class="fa fa-arrow-left"></i> --}}
                                    {{--                                    <span class="ms-2">{{ helperTrans('ingaz.countries') }}</span> --}}
                                    {{--                                </a> --}}
                                    {{--                            </li> --}}
                                @endif
                                @if (@AdminGuardHasPerm(\App\PermKeys::SOCIAL_STATUS_VIEW))
                                    <li>
                                        <a class="m-link" href="{{ route('social-statuses.index') }}"
                                            target="_blank">
                                            <i class="fa fa-heart"></i>
                                            <span class="ms-2">الحالات الاجتماعية</span>
                                            <span class="badge bg-success ms-2" style="font-size: 10px;">محدث</span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                        @if (@AdminGuardHasPerm(\App\PermKeys::PACKAGES_VIEW))
                            <li>
                                <a class="m-link" href="{{ route('packages.index') }}">
                                    {{-- <i class="fa fa-arrow-left"></i> --}}
                                    <span class="ms-2">{{ helperTrans('ingaz.packages') }}</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
                <!-- Clients Section -->
            </ul>
        </div>

        {{-- @if (Auth::guard('admin')->user()->hasPermission('الإعدادات-انشاء')) --}}
        <ul class="menu-list nav navbar-nav flex-row text-center menu-footer-link">
            <li class="nav-item flex-fill p-2">
                <a class="d-inline-block w-100 color-400" href="{{ route('settings.index') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" fill="currentColor" viewBox="0 0 16 16">
                        <path
                            d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z" />
                        <path class="fill-secondary"
                            d="M3 3.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zM3 6a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9A.5.5 0 0 1 3 6zm0 2.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5z" />
                    </svg>
                </a>
            </li>
            <li class="nav-item flex-fill p-2">
                <a class="d-inline-block w-100 color-400" href="{{ route('admin.logout') }}" title="sign-out">
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // تحسين انتقالات القوائم المنسدلة
        const collapseElements = document.querySelectorAll('.collapse');
        collapseElements.forEach(element => {
            element.addEventListener('show.bs.collapse', function() {
                const arrow = this.previousElementSibling.querySelector('.arrow');
                if (arrow) {
                    arrow.style.transform = 'rotate(0deg)';
                }
            });

            element.addEventListener('hide.bs.collapse', function() {
                const arrow = this.previousElementSibling.querySelector('.arrow');
                if (arrow) {
                    arrow.style.transform = 'rotate(-90deg)';
                }
            });
        });

        // إضافة تأثير نبض للعناصر النشطة
        const activeLinks = document.querySelectorAll('.m-link.active');
        activeLinks.forEach(link => {
            link.style.animation = 'pulse 2s infinite';
        });

        // تحسين التمرير للعنصر النشط
        const activeElement = document.querySelector('.menu-list .m-link.active');
        if (activeElement) {
            setTimeout(() => {
                activeElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }, 300);
        }
    });

    // إضافة تأثير pulse
    const style = document.createElement('style');
    style.textContent = `
    @keyframes pulse {
        0% {
            box-shadow: 0 2px 4px rgba(78, 115, 223, 0.25);
        }
        50% {
            box-shadow: 0 2px 8px rgba(78, 115, 223, 0.4);
        }
        100% {
            box-shadow: 0 2px 4px rgba(78, 115, 223, 0.25);
        }
    }
`;
    document.head.appendChild(style);
</script>
