<div class="col-md-4 col-lg-3 p-1">
    <div class="profileNavCol">
        <a class="{{ Route::currentRouteName() === 'site.profile.index' ? 'active' : '' }}"
           href="{{route('site.profile.index')}}"> <i class="fa-solid fa-user-hair-mullet me-2"></i>
            {{__("ingaz.recruitment_requests")}}
        </a>
        <a class="{{ Route::currentRouteName() === 'site.last-orders.index' ? 'active' : '' }}"
           href="{{route('site.last-orders.index')}}"> <i class="fa-solid fa-user-headset me-2"></i>
            {{__("ingaz.order_history")}}
        </a>
        <a class="{{ Route::currentRouteName() === 'site.chats.index' ? 'active' : '' }}"
           href="{{route('site.chats.index')}}"> <i class="fa-solid fa-comments me-2"></i>
            {{__("ingaz.chat_history")}}
        </a>
        <a class="{{ Route::currentRouteName() === 'site.notifications.index' ? 'active' : '' }}"
           href="{{route('site.notifications.index')}}"> <i class="fas fa-bell me-2"></i>
            {{__("ingaz.notifications")}}
        </a>
        <a href="{{ route('site.settings.index') }}"
           class="{{ Route::currentRouteName() === 'site.settings.index' ? 'active' : '' }}">
            <i class="fas fa-cog me-2"></i>
            {{__("ingaz.account_settings")}}
        </a>
        <a href="{{route('site.logout.index')}}"><i class="fas fa-power-off me-2"></i>
            {{__("ingaz.logout")}}
        </a>
    </div>
</div>
