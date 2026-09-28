<div class="d-flex align-items-center justify-content-between gap-3 p-3 ho-latest-summary">
    <p class="mb-0 text-muted">{{ $description }}</p>
    <a class="btn btn-sm btn-outline-primary rounded-3 text-nowrap" href="{{ route('admin.hourly-orders.index', ['source' => $source]) }}">عرض الكل <i class="fa fa-arrow-left me-1"></i></a>
</div>
<div class="table-responsive">
    <table class="ht-table ho-latest-table">
                <thead><tr>
                    <th>رقم الطلب</th>
                    <th>تاريخ الطلب</th>
                    <th>اسم العميل</th>
                    <th>المدينة / الخدمة</th>
                    <th>المنتج / الموعد</th>
                    <th>الإجمالي</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr></thead>
                <tbody>
                @forelse($latestOrders as $order)
                    @php
                        [$chipClass, $chipIcon] = $statusChip($order->status);
                    @endphp
                    <tr>
                        <td>
                            <a class="ho-order-no" href="{{ route('admin.hourly-orders.show', $order) }}" title="رقم الطلب الكامل: {{ $order->order_number }}">#{{ $shortOrderNumber($order) }}</a>
                            <div class="ho-sub">
                                @if($source === 'admin')
                                    <i class="fa fa-user-tie"></i> موظف الفرع - {{ $order->creator?->name ?: 'غير محدد' }}
                                @else
                                    <i class="fa fa-mobile-screen-button"></i> Web / Mobile Apps
                                @endif
                            </div>
                        </td>
                        <td><span class="ho-ltr">{{ $order->created_at?->format('d/m/Y H:i') ?: '—' }}</span></td>
                        <td>
                            <strong>{{ $order->user?->name ?: 'عميل محذوف' }}</strong>
                            @if($order->user?->phone)<div class="ho-sub ho-ltr">{{ $order->user->phone }}</div>@endif
                        </td>
                        <td>
                            <strong>{{ $order->city?->name_ar ?: $order->city?->name_en ?: '—' }}</strong>
                            <div class="ho-sub">{{ $order->serviceCategory?->name ?: '—' }}{{ $order->district ? ' · '.$order->district : '' }}</div>
                        </td>
                        <td>
                            <strong>{{ $order->package_snapshot['title'] ?? '—' }}</strong>
                            <div class="ho-sub ht-num">{{ rtrim(rtrim(number_format($order->hours_per_visit, 2), '0'), '.') }} ساعات · {{ $order->visits_total }} زيارة · {{ $order->workers_per_visit }} عاملة</div>
                            <div class="ho-sub ho-ltr">{{ $order->starts_at?->format('d/m/Y H:i') ?: '—' }}</div>
                        </td>
                        <td><strong class="ht-num">{{ $money($order->total_amount) }}</strong><div class="ho-sub">الضريبة: {{ $money($order->tax_amount) }}</div></td>
                        <td><span class="ht-chip {{ $chipClass }}"><i class="fa {{ $chipIcon }}"></i> {{ __('hourly.'.$order->status) }}</span></td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-primary ht-icon-btn" href="{{ route('admin.hourly-orders.show', $order) }}" title="تفاصيل الطلب"><i class="fa fa-eye"></i></a>
                            <a class="btn btn-sm btn-outline-secondary ht-icon-btn" href="{{ route('admin.hourly-orders.invoice', $order) }}" target="_blank" title="الفاتورة"><i class="fa fa-file-pdf"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="ht-empty"><i class="fa fa-inbox"></i> ليست هناك طلبات متاحة حاليًا.</div></td></tr>
                @endforelse
                </tbody>
    </table>
</div>
