{{-- نوافذ ملف العميل: تعديل الرصيد يدويًا، إرسال إشعار، الحذف (مع أسباب المنع). --}}
@php $c = fn (string $key, array $replace = []) => __('hourly_customers.'.$key, $replace); @endphp

@if($canAdjust)
<div class="modal fade om-modal" id="hc-adjust-modal" tabindex="-1" aria-labelledby="hc-adjust-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="POST" action="{{ route('admin.hourly-customers.adjust', $customer->id) }}">
        @csrf
        <div class="modal-header"><span class="om-m-icon"><i class="fa fa-scale-balanced"></i></span><div><h5 id="hc-adjust-title">{{ $c('adjust.title') }}</h5>
            <p>{{ $customer->name }} · {{ $c('deposit.current_balance') }}: {{ number_format((float) $customer->hourly_wallet_balance, 2) }} {{ $c('sar') }}</p></div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ $c('deposit.close') }}"></button></div>
        <div class="modal-body">
            <label>{{ $c('adjust.direction') }}</label>
            <div class="hc-modes mb-3">
                <label><input type="radio" name="direction" value="increase" checked> <span><i class="fa fa-plus" style="color:#0f7a5a"></i> {{ $c('adjust.increase') }}</span></label>
                <label><input type="radio" name="direction" value="decrease"> <span><i class="fa fa-minus" style="color:#c0262d"></i> {{ $c('adjust.decrease') }}</span></label>
            </div>
            <label for="hc-adjust-amount"><i class="fa fa-money-bill"></i> {{ $c('adjust.amount') }}</label>
            <div class="input-group mb-3" dir="ltr"><input class="form-control" type="number" min="0.01" step="0.01" id="hc-adjust-amount" name="amount" required><span class="input-group-text">SAR</span></div>
            <label for="hc-adjust-reason"><i class="fa fa-pen"></i> {{ $c('adjust.reason') }}</label>
            <textarea class="form-control" id="hc-adjust-reason" name="reason" rows="2" minlength="3" maxlength="500" required></textarea>
            <small class="form-text">{{ $c('adjust.hint') }}</small>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ $c('deposit.close') }}</button><button class="btn btn-primary"><i class="fa fa-check"></i> {{ $c('adjust.save') }}</button></div>
    </form></div>
</div>
@endif

@if($canNotify)
@php $language = $profile->language ?: 'ar'; @endphp
<div class="modal fade om-modal" id="hc-notify-modal" tabindex="-1" aria-labelledby="hc-notify-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="POST" action="{{ route('admin.hourly-customers.notify', $customer->id) }}">
        @csrf
        <div class="modal-header"><span class="om-m-icon"><i class="fa fa-bell"></i></span><div><h5 id="hc-notify-title">{{ $c('notify.title') }}</h5><p>{{ $customer->name }}</p></div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ $c('deposit.close') }}"></button></div>
        <div class="modal-body">
            <div class="hc-note mb-3"><i class="fa fa-language mt-1"></i><div>{{ $c('notify.language_hint', ['language' => $c('languages.'.$language)]) }}</div></div>
            <label>{{ $c('notify.channel') }}</label>
            <div class="hc-seg mb-3" role="radiogroup" aria-label="{{ $c('notify.channel') }}">
                @foreach(['app' => 'fa-mobile-screen', 'sms' => 'fa-comment-sms', 'email' => 'fa-envelope'] as $channel => $icon)
                    <label><input type="radio" name="channel" value="{{ $channel }}" {{ $loop->first ? 'checked' : '' }}><span><i class="fa {{ $icon }}"></i> {{ $c('notify.channels.'.$channel) }}</span></label>
                @endforeach
            </div>
            {{-- اتجاه الحقول بلغة العميل المفضلة --}}
            <label for="hc-notify-subject"><i class="fa fa-heading"></i> {{ $c('notify.subject') }}</label>
            <input class="form-control mb-3" id="hc-notify-subject" name="title" maxlength="150" required lang="{{ $language }}" dir="{{ $language === 'ar' ? 'rtl' : 'ltr' }}">
            <label for="hc-notify-body"><i class="fa fa-align-right"></i> {{ $c('notify.body') }}</label>
            <textarea class="form-control" id="hc-notify-body" name="body" rows="4" maxlength="1000" required lang="{{ $language }}" dir="{{ $language === 'ar' ? 'rtl' : 'ltr' }}"></textarea>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ $c('deposit.close') }}</button><button class="btn btn-primary"><i class="fa fa-paper-plane"></i> {{ $c('notify.send') }}</button></div>
    </form></div>
</div>
@endif

@if($canDelete)
<div class="modal fade om-modal" id="hc-delete-modal" tabindex="-1" aria-labelledby="hc-delete-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="POST" action="{{ route('admin.hourly-customers.destroy', $customer->id) }}">
        @csrf @method('DELETE')
        <div class="modal-header" style="--om-accent:#dc2626"><span class="om-m-icon"><i class="fa fa-trash"></i></span><div><h5 id="hc-delete-title">{{ $c('delete.title') }}</h5><p>{{ $customer->name }}</p></div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ $c('deposit.close') }}"></button></div>
        <div class="modal-body">
            @if($deleteBlockers)
                <div class="hc-note is-warn"><i class="fa fa-ban mt-1"></i><div><b>{{ $c('delete.blocked') }}</b><ul class="mb-0 mt-1">@foreach($deleteBlockers as $reason)<li>{{ $reason }}</li>@endforeach</ul></div></div>
            @else
                <p>{{ $c('delete.confirm') }}</p>
                <label for="hc-delete-reason"><i class="fa fa-pen"></i> {{ $c('delete.reason') }}</label>
                <textarea class="form-control" id="hc-delete-reason" name="reason" rows="2" maxlength="500"></textarea>
            @endif
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ $c('deposit.close') }}</button>
            @unless($deleteBlockers)<button class="btn btn-danger"><i class="fa fa-trash"></i> {{ $c('show.delete') }}</button>@endunless</div>
    </form></div>
</div>
@endif
