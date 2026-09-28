{{-- تبويب طرق الدفع. يتطلب: $paymentMatrix, $canEdit --}}
@php $channels = \App\Http\Controllers\admin\HourlyCategoryPaymentController::CHANNELS; @endphp
<form method="POST" action="{{ route('admin.hourly-category-payments.update', $category) }}">
    @csrf @method('PUT')
    <div class="ht-card">
        <div class="ht-head">
            <div class="ht-head-icon"><i class="fa fa-credit-card"></i></div>
            <div><h5>{{ __('hourly.tab_payments') }}</h5><p>{{ __('hourly_category_ui.payment_hint') }}</p></div>
        </div>
        <div class="ht-body">
            <div class="table-responsive">
                <table class="ht-table">
                    <thead>
                        <tr>
                            <th>{{ __('hourly_category_ui.payment_method') }}</th>
                            @foreach ($channels as $channel)<th class="text-center">{{ __('hourly.channel_'.$channel) }}</th>@endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($paymentMatrix as $method => $flags)
                            <tr>
                                <td><strong>{{ __('hourly.'.$method) }}</strong> <code class="small text-muted">{{ $method }}</code></td>
                                @foreach ($channels as $channel)
                                    <td class="text-center">
                                        <div class="form-check form-switch d-inline-block m-0">
                                            <input type="hidden" name="methods[{{ $method }}][{{ $channel }}]" value="0">
                                            <input class="form-check-input" type="checkbox" role="switch" name="methods[{{ $method }}][{{ $channel }}]" value="1" {{ $flags[$channel] ? 'checked' : '' }} {{ $canEdit && ($channel !== 'web_app' || in_array($method, \App\Services\Hourly\HourlyBookingRules::PAYMENT_METHODS)) ? '' : 'disabled' }} aria-label="{{ __('hourly.'.$method) }} — {{ __('hourly.channel_'.$channel) }}">
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="small text-muted mt-3 mb-0"><i class="fa fa-circle-info ms-1"></i> {{ __('hourly_category_ui.payment_channels') }}</p>
        </div>
        @if ($canEdit)
            <div class="ht-foot"><button class="btn btn-primary rounded-3 px-4"><i class="fa fa-floppy-disk ms-1"></i> {{ __('hourly.save') }}</button></div>
        @endif
    </div>
</form>
