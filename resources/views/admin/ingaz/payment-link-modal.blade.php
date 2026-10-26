{{-- نافذة إرسال رابط دفع ميسر: أي زر .send-payment-link يحمل data-url وdata-client وdata-phone يفتحها.
     القنوات تظهر بحسب إعدادات الاتصال: SMS حين تكون الرسائل النصية مفعلة، وواتساب حين يكون مزوده مهيأً. --}}
@php
    $paymentLinkChannels = \App\Services\PaymentLinkDeliveryService::availableChannels();
    $paymentLinkHasMessaging = $paymentLinkChannels['sms'] || $paymentLinkChannels['whatsapp'];
    $paymentLinkChannelCount = count(array_filter($paymentLinkChannels));
@endphp
<style>
    #paymentLinkDeliveryModal {
        --plm-navy: var(--ck-navy, #16233f);
        --plm-navy-soft: var(--ck-navy-soft, #eef1f7);
        --plm-green: var(--ck-green, #7ab648);
        --plm-green-d: var(--ck-green-d, #5f9434);
        --plm-green-soft: var(--ck-green-soft, #f0f7e8);
        --plm-ink: var(--ck-ink, #0f1a2e);
        --plm-muted: var(--ck-muted, #7b879a);
        --plm-line: var(--ck-line, #e3e8f0);
        --plm-bg: var(--ck-bg, #f6f8fb);
        --plm-r: var(--ck-r, 14px);
        --plm-r-s: var(--ck-r-s, 10px);
        --plm-h: var(--ck-ctl-h, 42px);
    }
    #paymentLinkDeliveryModal .modal-content { border: 1px solid var(--plm-line); border-radius: var(--plm-r); overflow: hidden; box-shadow: 0 24px 60px rgba(15, 26, 46, .18); }
    #paymentLinkDeliveryModal .plm-header { background: var(--plm-navy); color: #fff; border: 0; padding: 1rem 1.25rem; position: relative; }
    #paymentLinkDeliveryModal .plm-header::after { content: ""; position: absolute; inset-inline: 0; bottom: 0; height: 3px; background: var(--plm-green); }
    #paymentLinkDeliveryModal .plm-header .modal-title { display: flex; align-items: center; gap: .65rem; font-weight: 700; font-size: 1rem; color: #fff; margin: 0; }
    #paymentLinkDeliveryModal .plm-header-icon { width: 36px; height: 36px; border-radius: var(--plm-r-s); background: rgba(122, 182, 72, .18); color: #b9dd98; display: inline-flex; align-items: center; justify-content: center; }
    #paymentLinkDeliveryModal .plm-header .btn-close { filter: invert(1) grayscale(100%) brightness(200%); opacity: .8; margin: 0; }
    #paymentLinkDeliveryModal .modal-body { padding: 1.25rem; background: #fff; }
    #paymentLinkDeliveryModal .plm-recipient { display: flex; align-items: center; gap: .75rem; padding: .7rem .9rem; border-radius: var(--plm-r-s); background: var(--plm-bg); border: 1px solid var(--plm-line); margin-bottom: 1rem; }
    #paymentLinkDeliveryModal .plm-avatar { width: 38px; height: 38px; flex-shrink: 0; border-radius: 50%; background: var(--plm-navy-soft); color: var(--plm-navy); display: inline-flex; align-items: center; justify-content: center; }
    #paymentLinkDeliveryModal .plm-recipient small { display: block; color: var(--plm-muted); font-size: .76rem; }
    #paymentLinkDeliveryModal .plm-recipient strong { color: var(--plm-ink); word-break: break-word; font-size: .92rem; }
    #paymentLinkDeliveryModal .form-label { font-weight: 600; font-size: .86rem; color: var(--plm-ink); margin-bottom: .4rem; }
    #paymentLinkDeliveryModal .input-group-text { background: var(--plm-bg); color: var(--plm-muted); border-color: var(--plm-line); }
    #paymentLinkDeliveryModal .form-control { min-height: var(--plm-h); border-color: var(--plm-line); border-radius: var(--plm-r-s); }
    #paymentLinkDeliveryModal .input-group .form-control { border-start-start-radius: 0; border-end-start-radius: 0; }
    #paymentLinkDeliveryModal .input-group .input-group-text { border-radius: var(--plm-r-s) 0 0 var(--plm-r-s); }
    #paymentLinkDeliveryModal .form-control:focus { border-color: var(--plm-green); box-shadow: 0 0 0 .2rem rgba(122, 182, 72, .18); }
    #paymentLinkDeliveryModal .form-text { color: var(--plm-muted); font-size: .78rem; }
    #paymentLinkDeliveryModal .plm-channels { display: grid; grid-template-columns: repeat({{ max($paymentLinkChannelCount, 1) }}, minmax(0, 1fr)); gap: .6rem; }
    #paymentLinkDeliveryModal .plm-channel { position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .4rem; min-height: 92px; padding: .7rem .5rem;
        border: 1px solid var(--plm-line); border-radius: var(--plm-r-s); background: #fff; color: var(--plm-ink); font-weight: 600; font-size: .84rem; line-height: 1.3; text-align: center;
        transition: border-color .15s, box-shadow .15s, background .15s; }
    #paymentLinkDeliveryModal .plm-channel i { font-size: 1.4rem; color: var(--plm-navy); }
    #paymentLinkDeliveryModal .plm-channel-whatsapp i { color: #25a35a; }
    #paymentLinkDeliveryModal .plm-channel:hover:not(:disabled), #paymentLinkDeliveryModal .plm-channel:focus-visible { border-color: var(--plm-green); box-shadow: 0 4px 14px rgba(15, 26, 46, .07); outline: 0; }
    #paymentLinkDeliveryModal .plm-channel:disabled { opacity: .55; cursor: not-allowed; }
    #paymentLinkDeliveryModal .plm-channel.active { border: 2px solid var(--plm-green); background: var(--plm-green-soft); color: var(--plm-green-d); }
    #paymentLinkDeliveryModal .plm-channel.active::after { content: '\f00c'; font-family: 'Font Awesome 6 Free', 'Font Awesome 5 Free'; font-weight: 900; position: absolute; top: 6px; inset-inline-end: 8px;
        width: 18px; height: 18px; border-radius: 50%; background: var(--plm-green); color: #fff; font-size: .62rem; display: grid; place-items: center; }
    #paymentLinkDeliveryModal .plm-off { margin-top: .6rem; padding: .55rem .8rem; border-radius: var(--plm-r-s); background: var(--plm-bg); border: 1px dashed var(--plm-line); color: var(--plm-muted); font-size: .78rem; line-height: 1.7; }
    #paymentLinkDeliveryModal .plm-off i { color: var(--ck-amber, #c98a1a); margin-inline-end: .3rem; }
    #paymentLinkDeliveryModal .plm-footer { border-top: 1px solid var(--plm-line); padding: .8rem 1.25rem; gap: .5rem; background: var(--plm-bg); }
    #paymentLinkDeliveryModal .plm-footer .btn { min-height: var(--plm-h); min-width: 110px; border-radius: var(--plm-r-s); font-weight: 600; }
    #paymentLinkDeliveryModal .plm-confirm { background: var(--plm-green); border-color: var(--plm-green); color: #fff; }
    #paymentLinkDeliveryModal .plm-confirm:hover:not(:disabled) { background: var(--plm-green-d); border-color: var(--plm-green-d); color: #fff; }
    #paymentLinkDeliveryModal .plm-confirm:disabled { background: #b9c3d0; border-color: #b9c3d0; }
    #paymentLinkDeliveryModal .plm-feedback:empty { display: none; }
    #paymentLinkDeliveryModal .plm-feedback { white-space: pre-line; margin: 1rem 0 0; padding: .6rem .9rem; border-radius: var(--plm-r-s); font-size: .86rem; }
    @media (max-width: 420px) {
        #paymentLinkDeliveryModal .plm-channels { grid-template-columns: 1fr; }
        #paymentLinkDeliveryModal .plm-channel { flex-direction: row; min-height: 0; }
        #paymentLinkDeliveryModal .plm-channel i { font-size: 1.15rem; }
    }
</style>

<div class="modal fade" id="paymentLinkDeliveryModal" tabindex="-1" aria-labelledby="paymentLinkDeliveryTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header plm-header">
            <h5 class="modal-title">
                <span class="plm-header-icon"><i class="fas fa-link"></i></span>
                <span id="paymentLinkDeliveryTitle">إرسال رابط الدفع</span>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
        </div>
        <div class="modal-body">
            <div class="plm-recipient">
                <span class="plm-avatar"><i class="fas fa-user"></i></span>
                <div>
                    <small>العميل المستلم</small>
                    <strong id="paymentLinkRecipient"></strong>
                </div>
            </div>
            @if ($paymentLinkHasMessaging)
                <div class="mb-3">
                    <label for="paymentLinkPhone" class="form-label">رقم الجوال المستلم</label>
                    <div class="input-group" dir="ltr">
                        <span class="input-group-text"><i class="fas fa-mobile-alt"></i></span>
                        <input type="tel" id="paymentLinkPhone" class="form-control" maxlength="30" autocomplete="tel" aria-describedby="paymentLinkPhoneHelp">
                    </div>
                    <div id="paymentLinkPhoneHelp" class="form-text">يمكنك تعديل الرقم قبل الإرسال. لا يُنشأ رابط جديد إن كان للسجل رابط صالح.</div>
                </div>
            @endif
            <label class="form-label d-block">طريقة الإرسال</label>
            <div class="plm-channels">
                @if ($paymentLinkChannels['sms'])
                    <button type="button" class="plm-channel plm-channel-sms payment-link-channel" data-channel="sms" aria-pressed="false"><i class="fas fa-sms"></i><span>رسالة نصية SMS</span></button>
                @endif
                @if ($paymentLinkChannels['whatsapp'])
                    <button type="button" class="plm-channel plm-channel-whatsapp payment-link-channel" data-channel="whatsapp" aria-pressed="false"><i class="fab fa-whatsapp"></i><span>واتساب</span></button>
                @endif
                <button type="button" class="plm-channel plm-channel-copy payment-link-channel" data-channel="copy" aria-pressed="false"><i class="fas fa-copy"></i><span>نسخ الرابط</span></button>
            </div>
            @if (!$paymentLinkChannels['sms'] || !$paymentLinkChannels['whatsapp'])
                <div class="plm-off">
                    <i class="fas fa-info-circle"></i>
                    @if (!$paymentLinkHasMessaging)
                        الرسائل النصية وواتساب غير مفعّلين في إعدادات الاتصال؛ انسخ الرابط وأرسله للعميل يدوياً.
                    @elseif (!$paymentLinkChannels['sms'])
                        إرسال الرسائل النصية غير مفعّل في إعدادات الاتصال، لذلك لا تظهر قناة SMS.
                    @else
                        بوابة واتساب غير مهيأة في إعدادات الاتصال، لذلك لا تظهر قناة واتساب.
                    @endif
                </div>
            @endif
            <div id="paymentLinkDeliveryFeedback" class="plm-feedback" role="status"></div>
            <textarea id="paymentLinkCopyFallback" class="form-control mt-2 d-none" dir="ltr" rows="2" readonly aria-label="رابط الدفع"></textarea>
        </div>
        <div class="modal-footer plm-footer">
            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">إلغاء</button>
            <button type="button" id="paymentLinkConfirm" class="btn plm-confirm" disabled><i class="fas fa-check me-1"></i> <span>اختر طريقة الإرسال</span></button>
        </div>
    </div></div>
</div>

<script>
$(function () {
    let deliveryUrl = '';
    let deliveryBusy = false;
    let selectedChannel = null;
    const confirmLabels = {sms: 'تأكيد الإرسال عبر SMS', whatsapp: 'تأكيد الإرسال عبر الواتساب', copy: 'تأكيد نسخ الرابط'};
    const $phone = $('#paymentLinkPhone');

    function setFeedback(state, text) {
        const types = {success: 'alert-success', error: 'alert-danger', info: 'alert-info', warning: 'alert-warning'};
        $('#paymentLinkDeliveryFeedback').attr('data-state', state)
            .attr('class', 'plm-feedback alert ' + types[state]).text(text);
    }

    function selectChannel(channel) {
        selectedChannel = channel;
        $('.payment-link-channel').each(function () {
            const active = $(this).attr('data-channel') === channel;
            $(this).toggleClass('active', active).attr('aria-pressed', active ? 'true' : 'false');
        });
        $('#paymentLinkConfirm').prop('disabled', !channel)
            .find('span').text(channel ? confirmLabels[channel] : 'اختر طريقة الإرسال');
    }

    function showLink(url) {
        $('#paymentLinkCopyFallback').removeClass('d-none').val(url || '');
    }

    function reloadTables() {
        if (!$.fn.dataTable) return;
        $('table.dataTable').each(function () {
            if ($.fn.dataTable.isDataTable(this)) $(this).DataTable().ajax.reload(null, false);
        });
    }

    $(document).on('click', '.send-payment-link', function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();
        if (deliveryBusy) return;
        deliveryUrl = $(this).attr('data-url');
        $('#paymentLinkDeliveryTitle').text($(this).text().trim() || 'إرسال رابط الدفع');
        $('#paymentLinkRecipient').text($(this).attr('data-client') || 'العميل');
        $phone.val($(this).attr('data-phone') || '').prop('disabled', false);
        $('#paymentLinkDeliveryFeedback').attr('data-state', '').attr('class', 'plm-feedback').empty();
        $('#paymentLinkCopyFallback').addClass('d-none').val('');
        $('.payment-link-channel').prop('disabled', false);
        // قناة واحدة متاحة (النسخ فقط) تُختار مباشرة.
        const channels = $('.payment-link-channel');
        selectChannel(channels.length === 1 ? channels.first().attr('data-channel') : null);
        $('#paymentLinkDeliveryModal').modal('show');
    });

    $(document).on('click', '.payment-link-channel', function () {
        if (deliveryBusy || !deliveryUrl) return;
        selectChannel($(this).attr('data-channel'));
        $('#paymentLinkDeliveryFeedback').attr('data-state', '').attr('class', 'plm-feedback').empty();
    });

    $(document).on('click', '#paymentLinkConfirm', function () {
        if (deliveryBusy || !deliveryUrl || !selectedChannel) return;
        const channel = selectedChannel;
        const phone = ($phone.val() || '').trim();
        if (channel !== 'copy' && !phone) {
            setFeedback('error', 'أدخل رقم الجوال المستلم.');
            $phone.trigger('focus');
            return;
        }
        deliveryBusy = true;
        $phone.prop('disabled', true);
        $('.payment-link-channel, #paymentLinkConfirm').prop('disabled', true);
        setFeedback('info', 'جاري تجهيز رابط الدفع...');
        $.ajax({
            url: deliveryUrl, method: 'POST',
            data: {channel: channel, phone: channel === 'copy' ? null : phone, _token: '{{ csrf_token() }}'},
            success: async function (response) {
                if (channel === 'copy') {
                    showLink(response.payment_url);
                    const input = $('#paymentLinkCopyFallback')[0];
                    try {
                        if (navigator.clipboard && window.isSecureContext) {
                            await navigator.clipboard.writeText(response.payment_url);
                        } else {
                            input.focus(); input.select();
                            if (!document.execCommand('copy')) throw new Error('copy');
                        }
                        setFeedback('success', 'تم نسخ رابط الدفع.');
                    } catch (error) {
                        input.focus(); input.select();
                        setFeedback('warning', 'حدد الرابط وانسخه يدويًا.');
                    }
                } else if (response.message_sent) {
                    setFeedback('success', response.message || 'تم إرسال رابط الدفع.');
                    $('.send-payment-link').filter(function () { return $(this).attr('data-url') === deliveryUrl; })
                        .html('<i class="fas fa-paper-plane"></i> إعادة إرسال رابط الدفع');
                } else {
                    // أُنشئ الرابط وتعذر الإرسال: يبقى قابلاً للنسخ والإرسال يدويًا.
                    setFeedback('warning', response.message || 'تعذر إرسال الرسالة للعميل.');
                    showLink(response.payment_url);
                }
                reloadTables();
            },
            error: function (xhr) {
                const response = xhr.responseJSON || {};
                const errors = response.errors ? Object.values(response.errors).flat() : [];
                setFeedback('error', errors[0] || response.message || 'تعذر إرسال الرابط. حاول مجددًا.');
                if (response.payment_url) showLink(response.payment_url);
            },
            complete: function () {
                deliveryBusy = false;
                $phone.prop('disabled', false);
                // بعد نجاح الإرسال تبقى أزرار الإرسال معطلة حتى تُغلق النافذة، لتفادي رسالة مكررة.
                if ($('#paymentLinkDeliveryFeedback').attr('data-state') !== 'success' || channel === 'copy') {
                    $('.payment-link-channel, #paymentLinkConfirm').prop('disabled', false);
                }
            }
        });
    });
});
</script>
