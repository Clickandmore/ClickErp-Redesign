@if (\App\Support\HourlyRentalSettings::isEnabled() && collect(config('hourly_permissions'))->flatten()->unique()->contains(fn ($permission) => AdminGuardHasPerm($permission)))
            <li class="sidebar-module sidebar-module--hourly {{ request()->routeIs('admin.hourly-*') ? '' : 'collapsed' }}">
                <a class="m-link" data-bs-toggle="collapse" data-bs-target="#hourlyRental" href="#" aria-expanded="{{ request()->routeIs('admin.hourly-*') ? 'true' : 'false' }}" aria-controls="hourlyRental">
                    <i class="fa fa-clock"></i>
                    <span class="mx-2">{{ __('التأجير بالساعة') }}</span>
                    <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                </a>
                <ul class="sub-menu collapse {{ request()->routeIs('admin.hourly-*') ? 'show' : '' }}" id="hourlyRental" data-default-open="{{ request()->routeIs('admin.hourly-*') ? 'true' : 'false' }}">
                    @php
                        $hourlyAreasOpen = request()->routeIs('admin.hourly-cities.*', 'admin.hourly-districts.*');
                        $hourlyWorkforceOpen = request()->routeIs('admin.hourly-teams.*', 'admin.hourly-drivers.*', 'admin.hourly-maids.*', 'admin.hourly-sos.*');
                        $hourlyServicesOpen = request()->routeIs('admin.hourly-service-categories.*', 'admin.hourly-category-*', 'admin.hourly-package-tiers.*', 'admin.hourly-service-items.*', 'admin.hourly-packages.*');
                        $hourlyCreateOpen = request()->routeIs('admin.hourly-orders.wizard', 'admin.hourly-orders.create');
                        $hourlyOrdersOpen = (! $hourlyCreateOpen && request()->routeIs('admin.hourly-orders.*')) || request()->routeIs('admin.hourly-visits.*');
                        $hourlyCustomersOpen = request()->routeIs('admin.hourly-customers.*', 'admin.hourly-customer-groups.*');
                    @endphp

                    {{-- الطلبات والتشغيل تظهر أولًا لأنها مهام العمل اليومية. --}}
@if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-orders.index'))
                    <li><a class="m-link {{ ! $hourlyCreateOpen && request()->routeIs('admin.hourly-orders.*') ? 'active' : '' }}" href="{{ route('admin.hourly-orders.index') }}"><i class="fa fa-gauge-high"></i><span class="ms-2">لوحة الطلبات</span></a></li>
@endif
@if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-orders.wizard'))
                    <li><a class="m-link {{ $hourlyCreateOpen ? 'active' : '' }}" href="{{ route('admin.hourly-orders.wizard') }}"><i class="fa fa-cart-plus"></i><span class="ms-2">إنشاء طلب</span></a></li>
@endif
                    @php $hourlyRenewalsDue = rescue(fn () => \App\Http\Controllers\Hourly\RenewalController::dueCount(), 0, false); @endphp
@if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-renewals.index'))
                    <li><a class="m-link {{ request()->routeIs('admin.hourly-renewals.*') ? 'active' : '' }}" href="{{ route('admin.hourly-renewals.index', $hourlyRenewalsDue ? ['status' => 'custom', 'renewal' => 'not_renewed', 'expiry_from' => today()->toDateString(), 'expiry_to' => today()->addDays(max(1, (int) \App\Support\HourlyRentalSettings::get('renewal_reminder_days', 7)))->toDateString()] : []) }}"><i class="fa fa-rotate"></i><span class="ms-2">التجديدات</span>@if($hourlyRenewalsDue)<span class="badge rounded-pill bg-danger ms-2" title="عقود تنتهي قريبًا">{{ $hourlyRenewalsDue }}</span>@endif</a></li>
@endif
                    @if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-visits.index') || \App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-visits.unassigned') || \App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-visits.past') || \App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-visits.latest'))
<li class="collapsed">
                        <a class="m-link" data-bs-toggle="collapse" data-bs-target="#hourlyOrdersVisits" href="#" aria-expanded="{{ $hourlyOrdersOpen ? 'true' : 'false' }}" aria-controls="hourlyOrdersVisits">
                            <i class="fa fa-calendar-check"></i>
                            <span class="mx-2">الزيارات والتشغيل</span>
                            <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                        </a>
                        <ul class="sub-menu collapse {{ $hourlyOrdersOpen ? 'show' : '' }}" id="hourlyOrdersVisits">
@if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-visits.index'))
                            <li><a class="m-link {{ request()->routeIs('admin.hourly-visits.index') ? 'active' : '' }}" href="{{ route('admin.hourly-visits.index') }}"><i class="fa fa-calendar-check"></i><span class="ms-2">قائمة الزيارات</span></a></li>
@endif
@if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-visits.unassigned'))
                            <li><a class="m-link {{ request()->routeIs('admin.hourly-visits.unassigned') ? 'active' : '' }}" href="{{ route('admin.hourly-visits.unassigned') }}"><i class="fa fa-user-clock"></i><span class="ms-2">الزيارات غير المخصصة</span></a></li>
@endif
@if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-visits.past'))
                            <li><a class="m-link {{ request()->routeIs('admin.hourly-visits.past') ? 'active' : '' }}" href="{{ route('admin.hourly-visits.past') }}"><i class="fa fa-clock"></i><span class="ms-2">زيارات ماضية غير منتهية</span></a></li>
@endif
@if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-visits.latest'))
                            <li><a class="m-link {{ request()->routeIs('admin.hourly-visits.latest') ? 'active' : '' }}" href="{{ route('admin.hourly-visits.latest') }}"><i class="fa fa-calendar-day"></i><span class="ms-2">آخر الزيارات للطلب</span></a></li>
@endif
                        </ul>
                    </li>
@endif
@if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-schedule.index'))
                    <li><a class="m-link {{ request()->routeIs('admin.hourly-schedule.*') ? 'active' : '' }}" href="{{ route('admin.hourly-schedule.index') }}"><i class="fa fa-table-columns"></i><span class="ms-2">جدول الموظفين</span></a></li>
@endif
@if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-incidents.index'))
                    @php $hourlyIncidentsPending = rescue(fn () => \Illuminate\Support\Facades\Schema::hasTable('hourly_service_incidents') ? \Illuminate\Support\Facades\DB::table('hourly_service_incidents')->where('status', 'pending')->count() : 0, 0, false); @endphp
                    <li><a class="m-link {{ request()->routeIs('admin.hourly-incidents.*') ? 'active' : '' }}" href="{{ route('admin.hourly-incidents.index') }}"><i class="fa fa-notes-medical"></i><span class="ms-2">بلاغات تعذر التنفيذ</span>@if($hourlyIncidentsPending)<span class="badge rounded-pill bg-warning text-dark ms-2" title="بلاغات بانتظار التحقق">{{ $hourlyIncidentsPending }}</span>@endif</a></li>
@endif
@if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-service-time.index'))
<li><a class="m-link {{ request()->routeIs('admin.hourly-service-time.*') ? 'active' : '' }}" href="{{ route('admin.hourly-service-time.index') }}"><i class="fa fa-clock"></i><span class="ms-2">تجاوز مدة الخدمة</span></a></li>
@endif
@if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-early-finishes.index'))
                    @php $hourlyEarlyFinishReview = rescue(fn () => app(\App\Services\Hourly\EarlyFinish::class)->reviewCount(), 0, false); @endphp
                    <li><a class="m-link {{ request()->routeIs('admin.hourly-early-finishes.*') ? 'active' : '' }}" href="{{ route('admin.hourly-early-finishes.index') }}"><i class="fa fa-stopwatch"></i><span class="ms-2">الإنهاء المبكر</span>@if($hourlyEarlyFinishReview)<span class="badge rounded-pill bg-danger ms-2" title="تحتاج قرار الإدارة">{{ $hourlyEarlyFinishReview }}</span>@endif</a></li>
@endif
                    @php $hourlySosOpen = rescue(fn () => \Illuminate\Support\Facades\Schema::hasTable('hourly_worker_sos_alerts') ? \App\Models\Hourly\HourlyWorkerSosAlert::whereIn('status',['triggered','acknowledged'])->count() : 0, 0, false); @endphp
@if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-sos.index'))
                    <li><a class="m-link {{ request()->routeIs('admin.hourly-sos.*') ? 'active' : '' }}" href="{{ route('admin.hourly-sos.index',['status'=>'open']) }}"><i class="fa fa-triangle-exclamation text-danger"></i><span class="ms-2">نداءات النجدة</span>@if($hourlySosOpen)<span class="badge rounded-pill bg-danger ms-2">{{ $hourlySosOpen }}</span>@endif</a></li>
@endif
@if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-routing.index'))
                    <li><a class="m-link {{ request()->routeIs('admin.hourly-routing.*') ? 'active' : '' }}" href="{{ route('admin.hourly-routing.index') }}"><i class="fa fa-route"></i><span class="ms-2">{{ __('hourly_routing.menu') }}</span></a></li>
@endif
@if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-gps.index'))
                    <li><a class="m-link {{ request()->routeIs('admin.hourly-gps.*') ? 'active' : '' }}" href="{{ route('admin.hourly-gps.index') }}"><i class="fa fa-location-dot"></i><span class="ms-2">{{ __('hourly_routing.gps.menu') }}</span></a></li>
@endif

                    <li class="divider py-2 lh-sm"><span class="small">{{ __('الإعدادات والبيانات') }}</span></li>

                    {{-- إدارة المناطق: المدن وأحياؤها --}}
                    @if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-cities.index') || \App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-districts.index'))
<li class="collapsed">
                        <a class="m-link" data-bs-toggle="collapse" data-bs-target="#hourlyAreas" href="#">
                            <i class="fa fa-map-marked-alt"></i>
                            <span class="mx-2">{{ __('إدارة المناطق') }}</span>
                            <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                        </a>
                        <ul class="sub-menu collapse {{ $hourlyAreasOpen ? 'show' : '' }}" id="hourlyAreas">
@if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-cities.index'))
                            <li><a class="m-link {{ request()->routeIs('admin.hourly-cities.*') ? 'active' : '' }}" href="{{ route('admin.hourly-cities.index') }}"><i class="fa fa-city"></i><span class="ms-2">{{ __('المدن') }}</span></a></li>
@endif
@if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-districts.index'))
                            <li><a class="m-link {{ request()->routeIs('admin.hourly-districts.*') ? 'active' : '' }}" href="{{ route('admin.hourly-districts.index') }}"><i class="fa fa-map-marker-alt"></i><span class="ms-2">{{ __('المناطق') }}</span></a></li>
@endif
                        </ul>
                    </li>
@endif

                    {{-- فريق العمل: الفرق وخريطتها والسائقون والخادمات --}}
                    @if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-teams.index') || \App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-teams.map') || \App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-drivers.index') || \App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-maids.index'))
<li class="collapsed">
                        <a class="m-link" data-bs-toggle="collapse" data-bs-target="#hourlyWorkforce" href="#">
                            <i class="fa fa-people-group"></i>
                            <span class="mx-2">{{ __('فريق العمل') }}</span>
                            <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                        </a>
                        <ul class="sub-menu collapse {{ $hourlyWorkforceOpen ? 'show' : '' }}" id="hourlyWorkforce">
@if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-teams.index'))
                            <li><a class="m-link {{ request()->routeIs('admin.hourly-teams.*') && ! request()->routeIs('admin.hourly-teams.map') ? 'active' : '' }}" href="{{ route('admin.hourly-teams.index') }}"><i class="fa fa-people-group"></i><span class="ms-2">{{ __('الفرق') }}</span></a></li>
@endif
@if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-teams.map'))
                            <li><a class="m-link {{ request()->routeIs('admin.hourly-teams.map') ? 'active' : '' }}" href="{{ route('admin.hourly-teams.map') }}"><i class="fa fa-map-location-dot"></i><span class="ms-2">{{ __('خريطة الفرق') }}</span></a></li>
@endif
@if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-drivers.index'))
                            <li><a class="m-link {{ request()->routeIs('admin.hourly-drivers.*') ? 'active' : '' }}" href="{{ route('admin.hourly-drivers.index') }}"><i class="fa fa-car"></i><span class="ms-2">{{ __('السائقون') }}</span></a></li>
@endif
@if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-maids.index'))
                            <li><a class="m-link {{ request()->routeIs('admin.hourly-maids.*') ? 'active' : '' }}" href="{{ route('admin.hourly-maids.index') }}"><i class="fa fa-users"></i><span class="ms-2">{{ __('الخادمات') }}</span></a></li>
@endif
                        </ul>
                    </li>
@endif

                    {{-- الخدمات والباقات: فئات الخدمات ثم باقات الخدمات ثم أنماط التكرار --}}
                    @if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-service-categories.index') || \App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-service-items.index') || \App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-package-tiers.index'))
<li class="collapsed">
                        <a class="m-link" data-bs-toggle="collapse" data-bs-target="#hourlyServices" href="#">
                            <i class="fa fa-layer-group"></i>
                            <span class="mx-2">{{ __('الخدمات والباقات') }}</span>
                            <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                        </a>
                        <ul class="sub-menu collapse {{ $hourlyServicesOpen ? 'show' : '' }}" id="hourlyServices">
@if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-service-categories.index'))
                            <li><a class="m-link {{ request()->routeIs('admin.hourly-service-categories.*', 'admin.hourly-category-*') ? 'active' : '' }}" href="{{ route('admin.hourly-service-categories.index') }}"><i class="fa fa-shapes"></i><span class="ms-2">{{ __('hourly.categories') }}</span></a></li>
@endif
@if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-service-items.index'))
                            <li><a class="m-link {{ request()->routeIs('admin.hourly-service-items.*', 'admin.hourly-packages.*') ? 'active' : '' }}" href="{{ route('admin.hourly-service-items.index') }}"><i class="fa fa-box-open"></i><span class="ms-2">{{ __('hourly.service_packages') }}</span></a></li>
@endif
@if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-package-tiers.index'))
                            <li><a class="m-link {{ request()->routeIs('admin.hourly-package-tiers.*') ? 'active' : '' }}" href="{{ route('admin.hourly-package-tiers.index') }}"><i class="fa fa-repeat"></i><span class="ms-2">{{ __('hourly.package_tiers') }}</span></a></li>
@endif
                        </ul>
                    </li>
@endif

                    {{-- العملاء: نفس جدول العملاء لكل الأنظمة، بصفحات وبيانات خاصة بالساعات --}}
                    @if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-customers.index') || \App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-customers.create') || \App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-customer-groups.index') || \App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-customers.duplicates'))
<li class="collapsed">
                        <a class="m-link" data-bs-toggle="collapse" data-bs-target="#hourlyCustomers" href="#" aria-expanded="{{ $hourlyCustomersOpen ? 'true' : 'false' }}" aria-controls="hourlyCustomers">
                            <i class="fa fa-address-book"></i>
                            <span class="mx-2">{{ __('hourly_customers.menu') }}</span>
                            <span class="arrow fa fa-angle-left ms-auto text-end"></span>
                        </a>
                        <ul class="sub-menu collapse {{ $hourlyCustomersOpen ? 'show' : '' }}" id="hourlyCustomers">
@if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-customers.index'))
                            <li><a class="m-link {{ request()->routeIs('admin.hourly-customers.index', 'admin.hourly-customers.show') ? 'active' : '' }}" href="{{ route('admin.hourly-customers.index') }}"><i class="fa fa-users"></i><span class="ms-2">{{ __('hourly_customers.menu_list') }}</span></a></li>
@endif
@if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-customers.create'))
                            <li><a class="m-link {{ request()->routeIs('admin.hourly-customers.create') ? 'active' : '' }}" href="{{ route('admin.hourly-customers.create') }}"><i class="fa fa-user-plus"></i><span class="ms-2">{{ __('hourly_customers.menu_create') }}</span></a></li>
@endif
@if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-customer-groups.index'))
                            <li><a class="m-link {{ request()->routeIs('admin.hourly-customer-groups.*') ? 'active' : '' }}" href="{{ route('admin.hourly-customer-groups.index') }}"><i class="fa fa-users-rectangle"></i><span class="ms-2">{{ __('hourly_customers.menu_groups') }}</span></a></li>
@endif
@if (\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-customers.duplicates'))
                            <li><a class="m-link {{ request()->routeIs('admin.hourly-customers.duplicates', 'admin.hourly-customers.merge-form') ? 'active' : '' }}" href="{{ route('admin.hourly-customers.duplicates') }}"><i class="fa fa-clone"></i><span class="ms-2">{{ __('hourly_customers.menu_duplicates') }}</span></a></li>
@endif
                        </ul>
                    </li>
@endif

                </ul>
            </li>

@endif
