@extends('click.layout.indexs.index')
@section('page-title', $bladeTitle)
@section('page-links')<li class="breadcrumb-item"><a href="{{ route('finance.cash-movements.index') }}">تقرير حركة صندوق اليومية</a></li><li class="breadcrumb-item active">{{ $bladeTitle }}</li>@endsection
@section('style')
<style>
.ref-lines{direction:rtl;color:#183b49}.ref-lines .panel{padding:18px;background:#fff;border:1px solid #dce6e9;border-radius:12px;margin-bottom:18px}.ref-lines table{width:100%;border-collapse:collapse;font-size:14px}.ref-lines th,.ref-lines td{border:1px solid #e0e8eb;padding:8px 10px;vertical-align:middle}.ref-lines th{background:#e8f4f0}.ref-lines .missing{background:#fdecea}.ref-lines .order-btn{padding:2px 8px}.ref-lines .code{direction:ltr;white-space:nowrap;font-variant-numeric:tabular-nums}
</style>
@endsection
@section('content')
<div class="ref-lines p-3">
<div class="panel">
    <h4 class="mb-1">{{ $bladeTitle }}</h4>
    <div class="text-muted">كل بند مربوط برقم الحساب، فيبقى على حسابه إذا نُقل الحساب أو تغيّر كوده. يشمل البند الحساب وكل فروعه.</div>
    <a class="btn btn-outline-secondary btn-sm mt-2" href="{{ route('finance.cash-movements.index') }}">عرض التقرير</a>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

@if(!$linked)
<div class="panel">
    <p>التقرير يعمل الآن من ملف الإعدادات بأكواد الحسابات. اربط البنود بحساباتها مرة واحدة لتتمكن من إدارتها هنا، ولتبقى صحيحة بعد أي إعادة ترتيب للدليل.</p>
    <form method="POST" action="{{ route('finance.daily-cash-reference-lines.store') }}">@csrf<input type="hidden" name="link" value="1"><button class="btn btn-success" type="submit">ربط البنود بحساباتها</button></form>
</div>
@else
@foreach($groups as $group)
@php $rows = $lines->get($group, collect())->values(); @endphp
<div class="panel">
    <h5>{{ $groupLabels[$group] ?? $group }} <small class="text-muted">({{ $rows->count() }})</small></h5>
    <div class="table-responsive"><table>
        <thead><tr><th style="width:60px">#</th><th style="width:90px">الترتيب</th><th>التسمية في التقرير</th><th>الحساب المربوط</th><th style="width:170px"></th></tr></thead>
        <tbody>
        @foreach($rows as $i => $line)
        <tr class="{{ $line->account_code === null ? 'missing' : '' }}">
            <td>{{ $i + 1 }}</td>
            <td>
                <form class="d-inline" method="POST" action="{{ route('finance.daily-cash-reference-lines.update', $line->id) }}">@csrf @method('PUT')<input type="hidden" name="direction" value="up"><button class="btn btn-light btn-sm order-btn" {{ $i === 0 ? 'disabled' : '' }} title="أعلى">▲</button></form>
                <form class="d-inline" method="POST" action="{{ route('finance.daily-cash-reference-lines.update', $line->id) }}">@csrf @method('PUT')<input type="hidden" name="direction" value="down"><button class="btn btn-light btn-sm order-btn" {{ $i === $rows->count() - 1 ? 'disabled' : '' }} title="أسفل">▼</button></form>
            </td>
            <td colspan="2">
                <form class="row g-2 align-items-center" method="POST" action="{{ route('finance.daily-cash-reference-lines.update', $line->id) }}">@csrf @method('PUT')
                    <div class="col-md-5"><input class="form-control form-control-sm" name="label" value="{{ $line->label }}" required maxlength="255"></div>
                    <div class="col-md-5"><select class="form-control form-control-sm account-select" name="account_id" required>
                        @if($line->account_code === null)<option value="">— غير مربوط —</option>@else<option value="{{ $line->account_id }}" selected>{{ $line->account_code }} — {{ $line->account_name }}</option>@endif
                    </select></div>
                    <div class="col-md-2"><button class="btn btn-success btn-sm" type="submit">حفظ</button></div>
                </form>
            </td>
            <td>
                <form method="POST" action="{{ route('finance.daily-cash-reference-lines.destroy', $line->id) }}" onsubmit="return confirm('حذف البند «{{ addslashes($line->label) }}» من التقرير؟');">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm" type="submit">حذف</button></form>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table></div>
    <form class="row g-2 align-items-center mt-2" method="POST" action="{{ route('finance.daily-cash-reference-lines.store') }}">@csrf
        <input type="hidden" name="group_key" value="{{ $group }}">
        <div class="col-md-5"><select class="form-control form-control-sm account-select" name="account_id" required><option value="">اختر حساباً لإضافته…</option></select></div>
        <div class="col-md-5"><input class="form-control form-control-sm" name="label" placeholder="التسمية (اختياري — اسم الحساب افتراضياً)" maxlength="255"></div>
        <div class="col-md-2"><button class="btn btn-outline-success btn-sm" type="submit">إضافة بند</button></div>
    </form>
</div>
@endforeach
@endif
</div>
@endsection
@section('js')
<script>
// قائمة الحسابات مرة واحدة بدل تكرارها في كل بند
const refAccounts = @json($accounts->map(fn ($a) => ['id' => $a->id, 'text' => $a->code . ' — ' . $a->name])->values());
// تُملأ القائمة عند أول استخدام فقط حتى تبقى الصفحة خفيفة
function fillAccounts(select) {
    if (select.dataset.filled) return;
    select.dataset.filled = '1';
    const selected = select.value;
    select.innerHTML = '';
    [{ id: '', text: 'اختر حساباً…' }].concat(refAccounts).forEach(function (a) {
        select.add(new Option(a.text, a.id, false, String(a.id) === String(selected)));
    });
    if (window.jQuery && $.fn.select2) $(select).select2({ width: '100%' }).select2('open');
}
document.querySelectorAll('.account-select').forEach(function (select) {
    ['mousedown', 'focus', 'keydown'].forEach(function (e) { select.addEventListener(e, function () { fillAccounts(select); }); });
});
</script>
@endsection
