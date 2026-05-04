<div class="signature-container no-print">
    <div class="signature-title">توقيع الطرف الثاني ({{ data_get($contract, 'user.name', 'العميل') }})</div>

    @php
        $contractSignature = data_get($contract, 'signature');
    @endphp

    <div class="signature-status {{ $contractSignature ? 'signed' : 'not-signed' }}">
        {{ $contractSignature ? 'تم التوقيع على العقد' : 'لم يتم التوقيع بعد' }}
    </div>

    @if($contractSignature)
        <div class="signature-display">
            <img src="{{ asset('storage/signatures/' . $contractSignature) }}" alt="التوقيع المحفوظ">
        </div>
    @elseif(isset($allowSignature) && $allowSignature)
        <div class="signature-canvas-container">
            <canvas id="signatureCanvas" width="400" height="150"></canvas>
        </div>

        <div class="signature-controls">
            <button type="button" class="signature-btn signature-btn-primary" onclick="saveSignature()">
                حفظ التوقيع
            </button>
            <button type="button" class="signature-btn signature-btn-secondary" onclick="clearSignature()">
                مسح التوقيع
            </button>
            <button type="button" class="signature-btn signature-btn-danger" onclick="downloadSignature()">
                تحميل التوقيع
            </button>
        </div>
    @else
        <div class="signature-not-available">
            <p style="color: #666; text-align: center; padding: 20px;">
                التوقيع متاح عندما يكون العقد بانتظار التوقيع
            </p>
        </div>
    @endif
</div>

<div class="signature-section">
    <div class="signature-box">
        <h4>الطرف الأول</h4>
        <div class="signature-line">الاسم: {{ $documentCompanyName }}</div>
        <div class="signature-line">التوقيع: ........................</div>
        <div class="signature-line">التاريخ: {{ \Carbon\Carbon::now()->format('Y/m/d') }}</div>
    </div>

    <div class="signature-box">
        <h4>الطرف الثاني</h4>
        <div class="signature-line">الاسم: {{ data_get($contract, 'user.name', '') }}</div>
        <div class="signature-line">
            التوقيع:
            @if($contractSignature)
                <img src="{{ asset('storage/signatures/' . $contractSignature) }}" style="max-width: 150px; max-height: 60px; vertical-align: middle;">
            @else
                ........................
            @endif
        </div>
        <div class="signature-line">التاريخ: {{ \Carbon\Carbon::now()->format('Y/m/d') }}</div>
    </div>
</div>
