@php($bookingOptions = $order->package_snapshot['booking_options'] ?? [])
@if($bookingOptions)
<style>
    .bo-card { background: #fff; border: 1px solid #e6e9f2; border-radius: 14px; padding: .9rem 1rem; margin: 1rem 0; box-shadow: 0 2px 12px rgba(99, 102, 241, .05); }
    .bo-title { display: flex; align-items: center; gap: .5rem; font-size: .92rem; font-weight: 800; color: #1e293b; margin-bottom: .75rem; }
    .bo-title i { color: #6563ed; }
    .bo-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: .6rem; }
    .bo-item { display: flex; align-items: center; gap: .65rem; background: #f8f9fc; border: 1px solid #eef0f6; border-radius: 11px; padding: .6rem .75rem; }
    .bo-icon { flex: 0 0 34px; width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; background: #ede9fe; color: #5b21b6; }
    .bo-label { display: block; font-size: .74rem; color: #64748b; }
    .bo-value { display: block; font-size: .9rem; font-weight: 700; color: #1e293b; }
    .bo-yes { color: #15803d; }
    .bo-no { color: #94a3b8; }
</style>
<div class="bo-card">
    <div class="bo-title"><i class="fa fa-sliders" aria-hidden="true"></i> {{ __('خيارات الخدمة') }}</div>
    <div class="bo-grid">
        @if(array_key_exists('bring_materials', $bookingOptions))
            <div class="bo-item">
                <span class="bo-icon" aria-hidden="true"><i class="fa fa-spray-can-sparkles"></i></span>
                <span>
                    <span class="bo-label">{{ __('إحضار مواد من الشركة') }}</span>
                    <span class="bo-value {{ $bookingOptions['bring_materials'] ? 'bo-yes' : 'bo-no' }}">
                        <i class="fa {{ $bookingOptions['bring_materials'] ? 'fa-circle-check' : 'fa-circle-xmark' }}" aria-hidden="true"></i>
                        {{ $bookingOptions['bring_materials'] ? __('نعم') : __('لا') }}
                    </span>
                </span>
            </div>
        @endif
        @if(isset($bookingOptions['service_type']))
            <div class="bo-item">
                <span class="bo-icon" aria-hidden="true"><i class="fa {{ $bookingOptions['service_type'] === 'company' ? 'fa-building' : 'fa-house' }}"></i></span>
                <span>
                    <span class="bo-label">{{ __('نوع الخدمة') }}</span>
                    <span class="bo-value">{{ $bookingOptions['service_type'] === 'company' ? __('شركة') : __('منزل') }}</span>
                </span>
            </div>
        @endif
        <div class="bo-item">
            <span class="bo-icon" aria-hidden="true"><i class="fa fa-layer-group"></i></span>
            <span>
                <span class="bo-label">{{ __('الكمية') }}</span>
                <span class="bo-value">{{ $order->package_snapshot['quantity'] ?? 1 }}</span>
            </span>
        </div>
    </div>
</div>
@endif
