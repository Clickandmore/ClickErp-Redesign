@if (@AdminGuardHasPerm(\App\PermKeys::NOTIFICATIONS_CONTROL))
    <li>
        <a class="m-link {{ request()->routeIs('whatsapp.inbox.*') ? 'active' : '' }}"
            href="{{ route('whatsapp.inbox.index') }}">
            <i class="fa fa-comments text-success"></i>
            <span class="ms-2">محادثات واتساب</span>
        </a>
    </li>
    <li>
        <a class="m-link {{ request()->routeIs('whatsapp.notifications.*') ? 'active' : '' }}"
            href="{{ route('whatsapp.notifications.index') }}">
            <i class="fab fa-whatsapp text-success"></i>
            <span class="ms-2">{{ __('admin.sidebar.send_whatsapp') }}</span>
        </a>
    </li>
    <li>
        <a class="m-link {{ request()->routeIs('messages.*') ? 'active' : '' }}"
            href="{{ route('messages.index') }}">
            <i class="fa fa-inbox"></i>
            <span class="ms-2">{{ __('admin.sidebar.sent_messages') }}</span>
        </a>
    </li>
@endif
