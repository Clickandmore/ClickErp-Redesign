{{-- WhatsApp-style delivery mark: ✓ sent, ✓✓ delivered, blue ✓✓ read, red failed. --}}
@php
    $tick = [
        'pending' => ['🕓', '#6b7280', 'قيد الإرسال'],
        'queued' => ['🕓', '#6b7280', 'قيد الإرسال'],
        'accepted' => ['✓', '#6b7280', 'أُرسلت'],
        'sent' => ['✓', '#6b7280', 'أُرسلت'],
        'delivered' => ['✓✓', '#6b7280', 'وصلت'],
        'read' => ['✓✓', '#0ea5e9', 'قُرئت'],
        'failed' => ['✕', '#dc2626', 'فشلت'],
    ][$status ?? ''] ?? null;
@endphp
@if ($tick)
    <span title="{{ $tick[2] }}" style="color:{{ $tick[1] }};font-weight:700;letter-spacing:-2px;white-space:nowrap">{{ $tick[0] }}</span>
@endif
