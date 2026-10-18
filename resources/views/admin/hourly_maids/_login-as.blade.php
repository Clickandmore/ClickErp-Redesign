@if(AdminGuardHasPerm('hourly_maids_impersonate'))
    @php
        $portalAccount=(new \App\Models\Hourly\HourlyMaidAccount)->setRawAttributes($maid->getAttributes());
        $maidCanEnter=$portalAccount->canLoginVia('web') && !$maid->hourly_is_suspended;
    @endphp
    @if($maidCanEnter)
        <form method="post" action="{{ route('admin.hourly-maids.login-as',$maid) }}" target="_blank" class="d-inline-block m-0">
            @csrf
            <button type="submit" class="{{ $buttonClass ?? 'btn btn-sm btn-outline-secondary' }}" title="دخول كعاملة — فتح بوابة العاملة بحسابها في تبويب جديد" aria-label="دخول بحساب العاملة {{ $maid->name }}"><i class="fa fa-right-to-bracket" aria-hidden="true"></i> دخول كعاملة</button>
        </form>
    @else
        <span tabindex="0" title="الدخول غير متاح: الحساب معطّل أو يسمح بالجوال فقط. يمكن تعديله من ملف العاملة.">
            <button type="button" class="{{ $buttonClass ?? 'btn btn-sm btn-outline-secondary' }}" disabled style="opacity:.55;cursor:not-allowed"><i class="fa fa-right-to-bracket" aria-hidden="true"></i> دخول كعاملة</button>
        </span>
    @endif
@endif