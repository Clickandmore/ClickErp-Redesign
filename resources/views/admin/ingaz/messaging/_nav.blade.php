{{-- شريط تنقل موحد لصفحات «الرسائل والتواصل»؛ كل رابط يظهر فقط لمن يملك صلاحيته. --}}
@php
    $messagingLinks = [
        ['route' => 'whatsapp.inbox.index', 'active' => 'whatsapp.inbox.*', 'icon' => 'fa fa-comments', 'label' => __('messaging.whatsapp_inbox'), 'area' => \App\Support\MessagingPermissions::SEND],
        ['route' => 'whatsapp.notifications.index', 'active' => 'whatsapp.notifications.*', 'icon' => 'fab fa-whatsapp', 'label' => __('messaging.send_whatsapp'), 'area' => \App\Support\MessagingPermissions::SEND],
        ['route' => 'notifications.index', 'active' => 'notifications.index', 'icon' => 'fa fa-sms', 'label' => __('messaging.send_sms'), 'area' => \App\Support\MessagingPermissions::SEND],
        ['route' => 'messages.index', 'active' => 'messages.*', 'icon' => 'fa fa-history', 'label' => __('messaging.log'), 'area' => \App\Support\MessagingPermissions::LOG],
        ['route' => 'admin.sms-templates.index', 'active' => 'admin.sms-templates.*', 'icon' => 'fa fa-layer-group', 'label' => __('messaging.templates'), 'area' => \App\Support\MessagingPermissions::TEMPLATES],
        ['route' => 'notifications.settings.alerts', 'active' => 'notifications.settings.alerts', 'icon' => 'fa fa-bell', 'label' => __('messaging.alerts'), 'area' => \App\Support\MessagingPermissions::ALERTS],
        ['route' => 'notifications.settings', 'active' => 'notifications.settings', 'icon' => 'fa fa-plug', 'label' => __('messaging.integrations'), 'area' => \App\Support\MessagingPermissions::INTEGRATIONS],
        ['route' => 'dash.firebase-settings.index', 'active' => 'dash.firebase-settings.*', 'icon' => 'fa fa-mobile-alt', 'label' => __('messaging.firebase'), 'area' => \App\Support\MessagingPermissions::INTEGRATIONS],
    ];
@endphp
<nav class="messaging-nav d-flex flex-wrap gap-2 mb-3" aria-label="{{ __('messaging.module') }}">
    @foreach ($messagingLinks as $link)
        @if (\App\Support\MessagingPermissions::allows($link['area']))
            <a href="{{ route($link['route']) }}"
                class="btn btn-sm {{ request()->routeIs($link['active']) ? 'btn-primary' : 'btn-outline-secondary' }}">
                <i class="{{ $link['icon'] }} me-1"></i>{{ $link['label'] }}
            </a>
        @endif
    @endforeach
</nav>
