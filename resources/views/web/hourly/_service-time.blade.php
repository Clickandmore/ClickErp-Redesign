@if(!empty($serviceTime['message']))
<div class="hr-notice" role="status" style="margin:12px 0">
    <strong>{{ ($serviceTime['phase']??null)==='ending_soon'?'اقترب انتهاء مدة الخدمة':'متابعة مدة الخدمة' }}</strong>
    <p style="margin:6px 0">{{ $serviceTime['message'] }}</p>
    @if(!empty($serviceTime['expected_end_at']))<small>نهاية المدة المتوقعة: <bdi>{{ \Carbon\Carbon::parse($serviceTime['expected_end_at'])->format('H:i') }}</bdi> — حسب الوصول المسجّل والمدة المعتمدة.</small>@endif
    @if(($serviceTime['phase']??null)==='ending_soon' && !empty($actions['can_request_extra_hour']))
        <p style="margin:8px 0"><button type="button" class="hr-btn secondary sm" data-extra-hour="{{ $visit->id }}" data-visit-number="{{ $visit->visit_number }}">عرض سعر التمديد والموافقة عليه</button></p>
    @endif
</div>
@endif