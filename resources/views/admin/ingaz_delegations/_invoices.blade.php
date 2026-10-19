@php
    $compact = $compact ?? false;
    $documents = $delegation->invoices->sortByDesc('id');
@endphp
<div class="d-grid gap-3" @if ($compact) style="min-width:280px;max-width:380px;white-space:normal" @endif>
    @forelse ($documents as $document)
        @php
            $notes = $document->recruitmentCreditNotes;
            $invoiceCents = (int) round((float) $document->total_due * 100);
            $creditCents = $notes->sum(fn ($note) => (int) round((float) $note->total * 100));
            $fullyCredited = $notes->isNotEmpty() && $creditCents >= $invoiceCents;
            $status = $fullyCredited ? 'مغطاة بإشعار دائن كامل' : ($creditCents > 0 ? 'إشعار دائن جزئي' : 'بدون إشعار دائن');
            $color = $fullyCredited ? 'secondary' : ($creditCents > 0 ? 'warning' : 'success');
        @endphp
        <div class="border rounded-3 overflow-hidden bg-white" data-invoice-id="{{ $document->id }}">
            <div class="p-3 bg-light d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    @if ($canViewInvoices)
                        <a class="fw-bold text-decoration-none" href="{{ route('invoices.show', $document->id) }}">الفاتورة #{{ $document->id }}</a>
                    @else
                        <strong>الفاتورة #{{ $document->id }}</strong>
                    @endif
                    <small class="d-block text-muted mt-1">{{ optional($document->created_at)->format('Y-m-d') ?: '—' }}</small>
                </div>
                @if ($canViewCreditNotes)
                    <span class="badge bg-{{ $color }} {{ $color === 'warning' ? 'text-dark' : '' }}">{{ $status }}</span>
                @endif
            </div>
            <div class="p-3">
                @if ($canViewInvoices)
                    <div class="d-flex justify-content-between gap-3"><span class="text-muted">قيمة الفاتورة</span><strong>{{ number_format($invoiceCents / 100, 2) }} ريال</strong></div>
                @endif
                @if ($canViewCreditNotes)
                    <div class="d-flex justify-content-between gap-3 mt-2"><span class="text-muted">الإشعارات الدائنة</span><span class="text-danger">{{ number_format($creditCents / 100, 2) }} ريال</span></div>
                    @if ($canViewInvoices)
                        <div class="d-flex justify-content-between gap-3 border-top pt-2 mt-2"><span>الصافي بعد الإشعارات</span><strong>{{ number_format(($invoiceCents - $creditCents) / 100, 2) }} ريال</strong></div>
                    @endif
                    @foreach ($notes->sortByDesc('id') as $note)
                        <div class="border-top pt-2 mt-3">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <strong class="text-danger">إشعار دائن #{{ $note->id }}</strong>
                                <strong>{{ number_format((float) $note->total, 2) }} ريال</strong>
                            </div>
                            <small class="text-muted">{{ optional($note->issue_date)->format('Y-m-d') }} · للفاتورة #{{ $document->id }}</small>
                            @unless ($compact)<div class="mt-1" style="overflow-wrap:anywhere">{{ $note->reason }}</div>@endunless
                            @if ($canPrintCreditNotes)
                                <a class="d-inline-block mt-2 text-danger" href="{{ route('recruitment-credit-notes.download', ['id' => $note->id, 'autoprint' => 1]) }}" target="_blank" rel="noopener"><i class="fa fa-print" aria-hidden="true"></i> طباعة الإشعار #{{ $note->id }}</a>
                            @endif
                            @if (!$compact && $note->constraint_id)
                                <a class="d-inline-block mt-2 ms-2" href="{{ route('finance.add-constraint.show', ['add_constraint' => $note->constraint_id]) }}">القيد المحاسبي</a>
                            @endif
                        </div>
                    @endforeach
                @endif
                @if ($canCreateCreditNotes && !$fullyCredited)
                    <a class="btn btn-sm btn-outline-danger mt-3" href="{{ route('invoices.credit-notes.create', $document->id) }}">إنشاء إشعار دائن للفاتورة #{{ $document->id }}</a>
                @endif
            </div>
        </div>
    @empty
        <div class="text-muted p-3">لا توجد فواتير لهذا التفويض.</div>
    @endforelse
</div>
