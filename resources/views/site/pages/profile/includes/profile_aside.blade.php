
<div class="col-lg-4 col-12 mb-lg-0 mb-4">
    <div class="profile_side_bar">
        <div class="profile_header">
            <div class="profile_avatar">
                <i class="fa-light fa-user-circle"></i>
            </div>
            <div class="profile_info">
                <h4>{{getGuardedUser('name')}}</h4>
                <span class="profile_phone">{{getGuardedUser('phone_code').getGuardedUser('phone')}}</span>
                <span class="profile_email">{{getGuardedUser('email')}}</span>
            </div>
        </div>

        <div class="profile_nav">
            <ul class="nav_links">
                <li class="nav_item {{ (Route::currentRouteName() == 'site.profile.index') ? 'active' : '' }}">
                    <a href="{{route('site.profile.index')}}" class="nav_link">
                        <div class="nav_icon">
                            <i class="fa-light fa-home"></i>
                        </div>
                        <div class="nav_content">
                            <span class="nav_title">{{ helperTrans('web.dashboard') }}</span>
                            <span class="nav_desc">{{ helperTrans('web.dashboard_welcome_message') }}</span>
                        </div>
                    </a>
                </li>

                <li class="nav_item">
                    <a href="{{ route('site.profile.edit', getGuardedUser('id')) }}" class="nav_link">
                        <div class="nav_icon">
                            <i class="fa-light fa-user-pen"></i>
                        </div>
                        <div class="nav_content">
                            <span class="nav_title">{{ helperTrans('web.edit_profile') }}</span>
                            <span class="nav_desc">{{ helperTrans('web.update_profile') }}</span>
                        </div>
                    </a>
                </li>

                <li class="nav_item {{ (Route::currentRouteName() == 'site.notifications.index') ? 'active' : '' }}">
                    <a href="{{route('site.notifications.index')}}" class="nav_link">
                        <div class="nav_icon">
                            <i class="fa-light fa-bell"></i>
                        </div>
                        <div class="nav_content">
                            <span class="nav_title">{{ helperTrans('web.notifications') }}</span>
                            <span class="nav_desc">{{ helperTrans('web.view_notifications') }}</span>
                        </div>
                    </a>
                </li>

                <li class="nav_item {{ (Route::currentRouteName() == 'site.last-orders.index') ? 'active' : '' }}">
                    <a href="{{route('site.last-orders.index')}}" class="nav_link">
                        <div class="nav_icon">
                            <i class="fa-regular fa-list-check"></i>
                        </div>
                        <div class="nav_content">
                            <span class="nav_title">{{ helperTrans('web.my_orders') }}</span>
                            <span class="nav_desc">{{ helperTrans('web.view_my_orders') }}</span>
                        </div>
                    </a>
                </li>

                <li class="nav_item {{ (Route::currentRouteName() == 'site.contracts.index') ? 'active' : '' }}">
                    <a href="{{route('site.contracts.index')}}" class="nav_link">
                        <div class="nav_icon">
                            <i class="fa-regular fa-file-contract"></i>
                        </div>
                        <div class="nav_content">
                            <span class="nav_title">{{ helperTrans('web.my_contracts') }}</span>
                            <span class="nav_desc">{{ helperTrans('web.view_my_contracts') }}</span>
                        </div>
                    </a>
                </li>
                <li class="nav_item {{ (Route::currentRouteName() == 'site.sponsorship-transfers.index') ? 'active' : '' }}">
                    <a href="{{route('site.sponsorship-transfers.index')}}" class="nav_link">
                        <div class="nav_icon">
                            <i class="fa-regular fa-exchange-alt"></i>
                        </div>
                        <div class="nav_content">
                            <span class="nav_title">{{__('ingaz.Sponsorship_transfer_requests')}}</span>
                            <span class="nav_desc">{{__('ingaz.view_sponsorship_transfer_requests')}}</span>
                        </div>
                    </a>
                </li>
                <li class="nav_item {{ (Route::currentRouteName() == 'site.contract-return-requests.index') ? 'active' : '' }}">
                    <a href="{{route('site.contract-return-requests.index')}}" class="nav_link">
                        <div class="nav_icon">
                            <i class="fa-light fa-undo"></i>
                        </div>
                        <div class="nav_content">
                            <span class="nav_title">{{ helperTrans('ingaz.contract_return_requests') }}</span>
                            <span class="nav_desc">{{ helperTrans('ingaz.view_contract_return_requests') }}</span>
                        </div>
                    </a>
                </li>
                <li class="nav_item {{ (Route::currentRouteName() == 'site.complaints.index') ? 'active' : '' }}">
                    <a href="{{route('site.complaints.index')}}" class="nav_link">
                        <div class="nav_icon">
                            <i class="fa-light fa-triangle-exclamation"></i>
                        </div>
                        <div class="nav_content">
                            <span class="nav_title">{{ helperTrans('web.my_complaints') }}</span>
                            <span class="nav_desc">{{ helperTrans('web.view_my_complaints') }}</span>
                        </div>
                    </a>
                </li>




            </ul>
        </div>

        <div class="profile_actions">
            <div class="action_divider">
                <span>{{ helperTrans('web.account_actions') }}</span>
            </div>

            <ul class="action_links">
                <li class="action_item">
                    <a href="{{route('site.logout.index')}}" class="action_link logout_link">
                        <div class="action_icon">
                            <i class="fa-sharp fa-regular fa-arrow-right-from-bracket"></i>
                        </div>
                        <span>{{ helperTrans('web.logout') }}</span>
                    </a>
                </li>

                {{-- <li class="action_item">
                    <a href="{{route('site.deleteAccount')}}" class="action_link delete_link">
                        <div class="action_icon">
                            <i class="fa-regular fa-user-xmark"></i>
                        </div>
                        <span>{{ helperTrans('web.deleteAccount') }}</span>
                    </a>
                </li> --}}
            </ul>
        </div>
    </div>
</div>



