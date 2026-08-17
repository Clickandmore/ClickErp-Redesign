@php
    $canDisplaySponsorshipTransfer = $canViewSponsorshipTransfer ?? AdminGuardHasAnyPerm([
        \App\PermKeys::SPONSOR_TRANSFER_VIEW,
        \App\PermKeys::SPONSOR_TRANSFER_WEB_REQUESTS_VIEW,
        \App\PermKeys::SPONSOR_TRANSFER_CREATE,
        \App\PermKeys::SPONSOR_TRANSFER_EDIT,
        \App\PermKeys::SPONSOR_TRANSFER_DELETE,
    ]);
    $isSponsorshipTransferOpen = in_array(Route::currentRouteName(), [
        'SponsorshipTransfer.create',
        'SponsorshipTransfer.index',
        'SponsorshipTransfer.webRequests',
        'ShelterContract.availableForTransfer',
        'SponsorshipTransfer.trial',
        'SponsorshipTransfer.failed',
        'SponsorshipTransfer.transferred',
        'transfer-contract-terms.index',
        'transfer-contract-terms.create',
        'transfer-contract-terms.edit',
    ]);
@endphp

@if ($canDisplaySponsorshipTransfer)
    <li class="{{ $isSponsorshipTransferOpen ? '' : 'collapsed' }}">
        <a class="m-link" data-bs-toggle="collapse" data-bs-target="#sponsorshipTransfer" href="#">
            <i class="icon-directions"></i>
            <span class="ms-2">{{ helperTrans('ingaz.sponsorship_transfer') }}</span>
            <span class="arrow fa fa-angle-left ms-auto text-end"></span>
        </a>
        <ul class="sub-menu collapse {{ $isSponsorshipTransferOpen ? 'show' : '' }}" id="sponsorshipTransfer">
            @if (@AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_VIEW))
                <li>
                    <a class="m-link {{ Route::currentRouteName() == 'SponsorshipTransfer.index' ? 'active' : '' }}"
                        href="{{ route('SponsorshipTransfer.index') }}">
                        <i class="fa fa-list"></i>
                        <span class="ms-2">{{ helperTrans('ingaz.sponsorship_transfer_requests') }}</span>
                    </a>
                </li>
            @endif
            @if (@AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_WEB_REQUESTS_VIEW))
                <li>
                    <a class="m-link {{ Route::currentRouteName() == 'SponsorshipTransfer.webRequests' ? 'active' : '' }}"
                        href="{{ route('SponsorshipTransfer.webRequests') }}">
                        <i class="fa fa-globe"></i>
                        <span class="ms-2">{{ __('admin.sidebar.website_requests') }}</span>
                    </a>
                </li>
            @endif
            @if (@AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_CREATE))
                <li>
                    <a class="m-link {{ Route::currentRouteName() == 'SponsorshipTransfer.create' ? 'active' : '' }}"
                        href="{{ route('SponsorshipTransfer.create') }}">
                        <i class="fa fa-plus"></i>
                        <span class="ms-2">{{ helperTrans('ingaz.add_transfer_request') }}</span>
                    </a>
                </li>
            @endif
            @if (@AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_VIEW))
                <li>
                    <a class="m-link {{ Route::currentRouteName() == 'ShelterContract.availableForTransfer' ? 'active' : '' }}"
                        href="{{ route('ShelterContract.availableForTransfer') }}">
                        <i class="fa fa-check-circle"></i>
                        <span class="ms-2">{{ helperTrans('ingaz.available_for_transfer') }}</span>
                    </a>
                </li>
                <li>
                    <a class="m-link {{ Route::currentRouteName() == 'SponsorshipTransfer.trial' ? 'active' : '' }}"
                        href="{{ route('SponsorshipTransfer.trial') }}">
                        <i class="fa fa-clock-o"></i>
                        <span class="ms-2">{{ helperTrans('ingaz.trial_period') }}</span>
                    </a>
                </li>
                <li>
                    <a class="m-link {{ Route::currentRouteName() == 'SponsorshipTransfer.failed' ? 'active' : '' }}"
                        href="{{ route('SponsorshipTransfer.failed') }}">
                        <i class="fa fa-times-circle"></i>
                        <span class="ms-2">{{ helperTrans('ingaz.trial_failed') }}</span>
                    </a>
                </li>
                <li>
                    <a class="m-link {{ Route::currentRouteName() == 'SponsorshipTransfer.transferred' ? 'active' : '' }}"
                        href="{{ route('SponsorshipTransfer.transferred') }}">
                        <i class="fa fa-check"></i>
                        <span class="ms-2">{{ helperTrans('ingaz.transferred') }}</span>
                    </a>
                </li>
                <li>
                    <a class="m-link {{ in_array(Route::currentRouteName(), ['transfer-contract-terms.index', 'transfer-contract-terms.create', 'transfer-contract-terms.edit']) ? 'active' : '' }}"
                        href="{{ route('transfer-contract-terms.index') }}">
                        <i class="fa fa-file-text"></i>
                        <span class="ms-2">{{ __('admin.sidebar.transfer_contract_terms') }}</span>
                    </a>
                </li>
            @endif
        </ul>
    </li>
@endif
