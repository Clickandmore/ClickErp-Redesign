@php($bookingOptions = $order->package_snapshot['booking_options'] ?? [])
@if($bookingOptions)
<div class="alert alert-light border my-3">
    <strong>{{ __('خيارات الخدمة') }}</strong>
    @if(array_key_exists('bring_materials', $bookingOptions))
        <div>{{ __('إحضار مواد من الشركة') }}: {{ $bookingOptions['bring_materials'] ? __('نعم') : __('لا') }}</div>
    @endif
    @if(isset($bookingOptions['service_type']))
        <div>{{ __('نوع الخدمة') }}: {{ $bookingOptions['service_type'] === 'company' ? __('شركة') : __('منزل') }}</div>
    @endif
    <div>{{ __('الكمية') }}: {{ $order->package_snapshot['quantity'] ?? 1 }}</div>
</div>
@endif
