{{-- دفع ميسر لطلب نقل الخدمات: حالة الفاتورة وإرسال الرابط ونسخه. --}}
@if ($transfer->payment_type === 'moyasar' && \App\Services\SponsorshipTransferMoyasarService::isAvailable())
    @php
        $moyasarStatusLabels = [
            'initiated' => 'بانتظار الدفع', 'paid' => 'مدفوعة', 'failed' => 'فشلت المحاولة',
            'expired' => 'منتهية', 'canceled' => 'ملغاة', 'refunded' => 'مستردة', 'partially_refunded' => 'مستردة جزئياً',
        ];
        $canSendTransferLink = \App\Services\SponsorshipTransferMoyasarService::canSend($transfer)
            && \App\Support\MoyasarSettings::isEnabled()
            && (@AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_ADD_PAYMENT) || @AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_CONTROL));
    @endphp
    <div class="info-row">
        <span class="info-label">فاتورة ميسر:</span>
        <span class="info-value">
            @if ($transfer->moyasar_invoice_id)
                {{ $moyasarStatusLabels[$transfer->moyasar_payment_status] ?? ($transfer->moyasar_payment_status ?: '-') }}
                — {{ number_format((float) $transfer->moyasar_amount, 2) }} ريال
            @else
                لم يُنشأ رابط الدفع بعد
            @endif
        </span>
    </div>
    @if ($transfer->payment_link_sent_at)
        <div class="info-row">
            <span class="info-label">آخر إرسال للرابط:</span>
            <span class="info-value">
                {{ $transfer->payment_link_sent_at->format('Y-m-d H:i') }}
                @if ($transfer->payment_link_sent_channel)
                    ({{ $transfer->payment_link_sent_channel === 'whatsapp' ? 'واتساب' : 'SMS' }})
                @endif
            </span>
        </div>
    @endif
    @if ((float) $transfer->moyasar_refunded_amount > 0)
        <div class="alert alert-warning py-2 mt-2 mb-0">
            المسترد عبر ميسر: {{ number_format((float) $transfer->moyasar_refunded_amount, 2) }} ريال، وسُجلت سندات الصرف المرتبطة به.
        </div>
    @endif
    @if ($canSendTransferLink)
        <div class="d-flex flex-wrap gap-2 mt-2">
            <button type="button" class="btn btn-sm btn-primary send-payment-link"
                data-url="{{ route('SponsorshipTransfer.sendPaymentLink', $transfer->id) }}"
                data-client="{{ $transfer->newSponsor?->name }}"
                data-phone="{{ $transfer->newSponsor?->phone }}">
                <i class="fas fa-paper-plane"></i> {{ \App\Services\PaymentLinkDeliveryService::label($transfer) }}
            </button>
        </div>
    @endif
@endif
