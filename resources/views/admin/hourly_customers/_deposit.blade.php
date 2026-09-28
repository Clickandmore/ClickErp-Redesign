{{-- «ADD BALANCE»: رصيد في المحفظة بلا ربط، أو دفعة على طلب محدد (عبر التحصيل القائم). --}}
@php $c = fn (string $key, array $replace = []) => __('hourly_customers.'.$key, $replace); @endphp
<div class="modal fade om-modal" id="hc-deposit-modal" tabindex="-1" aria-labelledby="hc-deposit-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered"><form class="modal-content" method="POST" action="{{ route('admin.hourly-customers.deposit', $customer->id) }}" enctype="multipart/form-data" id="hc-deposit-form">
        @csrf
        <div class="modal-header"><span class="om-m-icon"><i class="fa fa-wallet"></i></span><div><h5 id="hc-deposit-title">{{ $c('deposit.title') }}</h5><p>{{ $customer->name }} · {{ $c('deposit.current_balance') }}: {{ number_format((float) $customer->hourly_wallet_balance, 2) }} {{ $c('sar') }}</p></div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ $c('deposit.close') }}"></button></div>
        <div class="modal-body">
            <fieldset class="mb-3"><legend><i class="fa fa-link"></i> {{ $c('deposit.options') }}</legend>
                <div class="hc-modes">
                    <label><input type="radio" name="mode" value="wallet" checked> <span>{{ $c('deposit.mode_wallet') }}<small>{{ $c('deposit.wallet_hint') }}</small></span></label>
                    <label><input type="radio" name="mode" value="order" {{ $payableOrders->isEmpty() ? 'disabled' : '' }}> <span>{{ $c('deposit.mode_order') }}<small>{{ $payableOrders->isEmpty() ? $c('deposit.no_orders') : $c('deposit.order_hint') }}</small></span></label>
                </div>
            </fieldset>
            <div class="row g-3">
                <div class="col-md-6" data-deposit-order hidden>
                    <label for="hc-deposit-order"><i class="fa fa-receipt"></i> {{ $c('deposit.order') }}</label>
                    <select class="form-select" id="hc-deposit-order" name="order_id">
                        @foreach($payableOrders as $order)<option value="{{ $order['id'] }}" data-remaining="{{ $order['remaining'] }}">{{ $order['label'] }} · {{ $c('deposit.remaining', ['amount' => number_format($order['remaining'], 2)]) }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="hc-collector-type"><i class="fa fa-hand-holding-dollar"></i> {{ $c('deposit.collected_by') }}</label>
                    <div class="input-group">
                        <select class="form-select" id="hc-collector-type" name="collected_by_type" style="max-width:40%">
                            <option value="admin">{{ $c('deposit.collector_admin') }}</option><option value="maid">{{ $c('deposit.collector_maid') }}</option>
                        </select>
                        <select class="form-select" name="collected_by_id" data-collector="admin" required>
                            @foreach($collectors['admin'] ?? [] as $id => $name)<option value="{{ $id }}" {{ (int) $id === (int) auth('admin')->id() ? 'selected' : '' }}>{{ $name }}</option>@endforeach
                        </select>
                        <select class="form-select" name="collected_by_id" data-collector="maid" disabled hidden>
                            @foreach($collectors['maid'] ?? [] as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="hc-amount"><i class="fa fa-money-bill"></i> {{ $c('deposit.amount') }}</label>
                    <div class="input-group" dir="ltr"><input class="form-control" type="number" min="0.01" step="0.01" id="hc-amount" name="amount" required><span class="input-group-text">SAR</span></div>
                </div>
                <div class="col-md-4">
                    <label for="hc-vat"><i class="fa fa-percent"></i> {{ $c('deposit.vat') }}</label>
                    <input class="form-control" type="number" min="0" max="100" step="0.01" id="hc-vat" name="vat_rate" value="15" required>
                </div>
                <div class="col-md-4">
                    <label for="hc-method"><i class="fa fa-credit-card"></i> {{ $c('deposit.payment_method') }}</label>
                    <select class="form-select" id="hc-method" name="payment_method" required>
                        <option value="">{{ $c('form.choose') }}</option>
                        @foreach(\App\Services\Hourly\CustomerDirectory::PAYMENT_METHODS as $method)<option value="{{ $method }}">{{ $c('methods.'.$method) }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label><i class="fa fa-calendar-plus"></i> {{ $c('deposit.entered_at') }}</label>
                    <input class="form-control" value="{{ now()->format('Y-m-d H:i') }}" readonly>
                </div>
                <div class="col-md-6">
                    <label for="hc-paid-at"><i class="fa fa-calendar-check"></i> {{ $c('deposit.paid_at') }}</label>
                    <input class="form-control" type="datetime-local" id="hc-paid-at" name="paid_at" value="{{ now()->format('Y-m-d\TH:i') }}" max="{{ now()->format('Y-m-d\TH:i') }}" required>
                </div>
                <div class="col-md-6">
                    <label for="hc-description"><i class="fa fa-pen"></i> {{ $c('deposit.description') }} <small id="hc-description-count">(0/500)</small></label>
                    <textarea class="form-control" id="hc-description" name="description" rows="3" maxlength="500"></textarea>
                    <small class="form-text">{{ $c('deposit.description_hint') }}</small>
                </div>
                <div class="col-md-6">
                    <label for="hc-attachment"><i class="fa fa-paperclip"></i> {{ $c('deposit.attachment') }}</label>
                    <input class="form-control" type="file" id="hc-attachment" name="attachment" accept=".jpg,.jpeg,.png,.pdf">
                    <small class="form-text">{{ $c('deposit.attachment_hint') }}</small>
                </div>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ $c('deposit.close') }}</button><button class="btn btn-primary"><i class="fa fa-floppy-disk"></i> {{ $c('deposit.save') }}</button></div>
    </form></div>
</div>
