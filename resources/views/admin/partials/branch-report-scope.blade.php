{{-- الفرع الذي تغطيه أرقام التقرير المعروض، للشاشة والطباعة والتصدير. --}}
@php
    $reportBranchLabel = app(\App\Services\BranchAccessService::class)->reportBranchLabel();
    $reportBranchTag = $scopeTag ?? 'span';
@endphp

@if ($reportBranchLabel)
    <{{ $reportBranchTag }} class="{{ $scopeClass ?? '' }}">
        <strong>{{ __('financial_requests.branch') }}:</strong> {{ $reportBranchLabel }}
    </{{ $reportBranchTag }}>
@endif
