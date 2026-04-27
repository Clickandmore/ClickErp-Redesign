@if (@AdminGuardHasPerm(\App\PermKeys::CRM_MODULE))
{{-- @if (in_array('crm.notifications-read', $user_permission_list)) --}}

{{-- @endif --}}
<li class="collapsed">
    <a class="m-link
    {{ in_array(Route::currentRouteName(), [
        'crm.categories.index',
        'crm.social_contacts.index',
        'crm.clients.index',
    ]) &&
    (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
        ? 'active'
        : '' }}"
        data-bs-toggle="collapse" data-bs-target="#double-account-statement" href="#">

        <i class="fa-solid fa-users"></i>
        <span class="ms-2">{{ __('crm::crm.clients') }}</span>
        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
    </a>

    <ul class="sub-menu collapse
                                     {{ in_array(Route::currentRouteName(), [
                                         'crm.categories.index',
                                         'crm.social_contacts.index',
                                         'crm.clients.index',
                                     ]) &&
                                     (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
                                         ? 'show'
                                         : '' }}"
        id="double-account-statement">
        {{--        @if (in_array('crm.categories-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'crm.categories.index' ? 'active' : '' }}"
                href="{{ route('crm.categories.index') }}">
                <i class="fa-solid fa-location-dot"></i>
                <span class="ms-2">{{ __('crm::crm.categories') }}</span>
            </a>
        </li>
        {{--        @endif --}}

        {{--        @if (in_array('crm.social_contacts-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'crm.social_contacts.index' ? 'active' : '' }}"
                href="{{ route('crm.social_contacts.index') }}">
                <i class="fa-solid fa-share-nodes"></i>
                <span class="ms-2">{{ __('crm::crm.social_contacts') }}</span>
            </a>
        </li>
        {{--        @endif --}}

        {{--        @if (in_array('crm.clients-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'crm.clients.index' ? 'active' : '' }}"
                href="{{ route('crm.clients.index') }}">
                <i class="fa-solid fa-users"></i>
                <span class="ms-2">{{ __('crm::crm.clients') }}</span>
            </a>
        </li>
        {{--        @endif --}}


    </ul>
</li>
{{-- @if (in_array('crm.price-offers-read', $user_permission_list)) --}}
{{-- <li>
    <a class="m-link {{ Route::currentRouteName() == 'crm.price-offers.index' ? 'active' : '' }}"
        href="{{ route('crm.price-offers.index') }}">
        <i class="fa-solid fa-file-invoice-dollar"></i>
        <span class="ms-2">{{ __('crm::crm.price-offers') }}</span>
    </a>

</li> --}}

{{-- @endif --}}
<li class="collapsed">
    <a class="m-link
    {{ in_array(Route::currentRouteName(), ['crm.attachment_categories.index', 'crm.client_attachments.index']) &&
    (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
        ? 'active'
        : '' }}"
        data-bs-toggle="collapse" data-bs-target="#client_attachments" href="#">

        <i class="fa-solid fa-paperclip"></i>
        <span class="ms-2">{{ __('crm::crm.client_attachments') }}</span>
        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
    </a>

    <ul class="sub-menu collapse
                                     {{ in_array(Route::currentRouteName(), ['crm.attachment_categories.index', 'crm.client_attachments.index']) &&
                                     (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
                                         ? 'show'
                                         : '' }}"
        id="client_attachments">
        {{-- @if (in_array('crm.attachment_categories-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'crm.attachment_categories.index' ? 'active' : '' }}"
                href="{{ route('crm.attachment_categories.index') }}">
                <i class="bi bi-folder-fill"></i>
                <span class="ms-2">{{ __('crm::crm.attachment_categories') }}</span>
            </a>
        </li>
        {{-- @endif --}}

        {{-- @if (in_array('crm.client_attachments-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'crm.client_attachments.index' ? 'active' : '' }}"
                href="{{ route('crm.client_attachments.index') }}">
                <i class="bi bi-paperclip"></i>
                <span class="ms-2">{{ __('crm::crm.client_attachments') }}</span>
            </a>
        </li>
        {{-- @endif --}}

    </ul>
</li>
<li class="collapsed">
    <a class="m-link
    {{ in_array(Route::currentRouteName(), [
        'crm.follow_clients_categories.index',
        'crm.follow-clients.index',
        'crm.my-followups.index',
    ]) &&
    (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
        ? 'active'
        : '' }}"
        data-bs-toggle="collapse" data-bs-target="#follow_clients_categories" href="#">

        <i class="fa-solid fa-user-check"></i>
        <span class="ms-2">{{ __('crm::crm.follow-clients') }}</span>
        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
    </a>

    <ul class="sub-menu collapse
                                     {{ in_array(Route::currentRouteName(), [
                                         'crm.follow_clients_categories.index',
                                         'crm.follow-clients.index',
                                         'crm.my-followups.index',
                                     ]) &&
                                     (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
                                         ? 'show'
                                         : '' }}"
        id="follow_clients_categories">
        {{-- @if (in_array('crm.follow_clients_categories-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'crm.follow_clients_categories.index' ? 'active' : '' }}"
                href="{{ route('crm.follow_clients_categories.index') }}">
                <i class="bi bi-folder-fill"></i>
                <span class="ms-2">{{ __('crm::crm.follow_clients_categories') }}</span>
            </a>
        </li>
        {{-- @endif --}}
        {{-- @if (in_array('crm.follow-clients-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'crm.follow-clients.index' ? 'active' : '' }}"
                href="{{ route('crm.follow-clients.index') }}">
                <i class="bi bi-people-fill"></i>
                <span class="ms-2">{{ __('crm::crm.follow-clients') }}</span>
            </a>
        </li>
        {{-- @endif --}}
        {{-- @if (in_array('crm.my-followups-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'crm.my-followups.index' ? 'active' : '' }}"
                href="{{ route('crm.my-followups.index') }}">
                <i class="bi bi-clock-history"></i>
                <span class="ms-2">{{ __('crm::crm.my-followups') }}</span>
            </a>
        </li>
        {{-- @endif --}}
    </ul>
</li>
<li>
    <a class="m-link {{ Route::currentRouteName() == 'crm.notifications.index' ? 'active' : '' }}"
        href="{{ route('crm.notifications.index') }}">
        <i class="far fa-bell"></i>
        <span class="ms-2">{{ __('crm::crm.notifications') }}</span>
    </a>

</li>
{{-- @if (in_array('crm.polls-read', $user_permission_list)) --}}
<li>
    <a class="m-link {{ Route::currentRouteName() == 'crm.polls.index' ? 'active' : '' }}"
        href="{{ route('crm.polls.index') }}">
        <i class="far fa-question-circle"></i>
        <span class="ms-2"> {{ __('crm::crm.polls') }}</span>
    </a>
</li>
{{-- @endif --}}
{{-- @if (in_array('crm.areas-read', $user_permission_list)) --}}
<li>
    <a class="m-link {{ Route::currentRouteName() == 'crm.areas.index' ? 'active' : '' }}"
        href="{{ route('crm.areas.index') }}">
        <i class="bi bi-geo-alt-fill"></i>
        <span class="ms-2">{{ __('crm::crm.areas') }}</span>
    </a>

</li>

{{-- @endif --}}
@endif
