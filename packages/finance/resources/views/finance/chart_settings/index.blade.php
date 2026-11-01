@extends('click.layout.indexs.index')
@section('page-title', $bladeTitle)
@section('page-links')<li class="breadcrumb-item active">{{ $bladeTitle }}</li>@endsection
@section('style')
<style>
.chart-settings{direction:rtl;color:#183b49;max-width:1100px;margin-inline:auto}
.chart-settings .heading{margin-bottom:20px}
.chart-settings .heading h3{margin:0 0 4px;font-weight:700}
.chart-settings .heading p{margin:0;color:#647985}
.chart-settings .panel{background:#fff;border:1px solid #dce6e9;border-radius:14px;padding:22px;margin-bottom:18px}
.chart-settings .panel h5{margin:0 0 4px;font-weight:700}
.chart-settings .panel>p{margin:0 0 16px;color:#647985;font-size:14px}
.chart-settings .choices{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px}
.chart-settings .choice{display:flex;gap:12px;align-items:flex-start;padding:14px 16px;border:1.5px solid #dce6e9;border-radius:12px;cursor:pointer;margin:0;transition:border-color .15s,background .15s}
.chart-settings .choice:hover{border-color:#9dbfc6}
.chart-settings .choice:has(input:checked){border-color:#194f57;background:#f1f8f7}
.chart-settings .choice input{margin-top:4px;flex:none;width:17px;height:17px;accent-color:#194f57}
.chart-settings .choice strong{display:block;margin-bottom:2px}
.chart-settings .choice small{color:#647985;line-height:1.7;display:block}
.chart-settings .digits-box{margin-top:18px}
.chart-settings .digits-box[hidden]{display:none}
.chart-settings table{width:100%;border-collapse:collapse;font-size:14px}
.chart-settings th,.chart-settings td{border-bottom:1px solid #e6edef;padding:10px 12px;vertical-align:middle;text-align:right}
.chart-settings th{background:#f3f7f8;font-weight:600;white-space:nowrap}
.chart-settings .digit-input{width:90px;text-align:center}
.chart-settings .ltr{direction:ltr;unicode-bidi:embed;font-variant-numeric:tabular-nums;white-space:nowrap}
.chart-settings .length-chip{display:inline-block;background:#f1f4f5;border-radius:6px;padding:2px 8px;margin:2px 0 2px 4px;font-size:12.5px;white-space:nowrap}
.chart-settings .hint{color:#647985;font-size:13px;line-height:1.8;margin:12px 0 0}
.chart-settings .actions{display:flex;justify-content:flex-start}
.chart-settings .actions .btn{min-width:130px}
</style>
@endsection
@section('content')
<div class="chart-settings p-3">
    <div class="heading">
        <h3>{{ $bladeTitle }}</h3>
        <p>طريقة ترقيم حسابات الدليل وطريقة عرض نوعها لهذه الشركة.</p>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <form method="POST" action="{{ route('finance.chart-settings.store') }}" id="chart-settings-form">
        @csrf
        @php $selectedMode = old('numbering_mode', $mode); $selectedDisplay = old('type_display', $typeDisplay); @endphp

        <section class="panel">
            <h5>ترقيم أكواد الحسابات</h5>
            <p>يُطبَّق على الحسابات الجديدة، ولا يغيّر أكواد الحسابات الموجودة.</p>
            <div class="choices">
                <label class="choice">
                    <input type="radio" name="numbering_mode" value="default" {{ $selectedMode === 'default' ? 'checked' : '' }}>
                    <span><strong>الترقيم الافتراضي</strong><small>كود الحساب الفرعي هو كود أبيه × 100 مع تسلسل، فيختلف طول الكود بحسب الأب.</small></span>
                </label>
                <label class="choice">
                    <input type="radio" name="numbering_mode" value="custom" {{ $selectedMode === 'custom' ? 'checked' : '' }}>
                    <span><strong>تحديد عدد الخانات لكل مستوى</strong><small>كل حسابات المستوى الواحد بالطول نفسه الذي تحدده.</small></span>
                </label>
            </div>

            <div class="digits-box" id="digits-box" {{ $selectedMode === 'custom' ? '' : 'hidden' }}>
                @if($mismatched)
                    <div class="alert alert-warning">{{ number_format($mismatched) }} حساباً لا تطابق أكوادها الأطوال المحفوظة. فروعها الجديدة تأخذ الترقيم القديم حتى يُرتَّب الدليل.</div>
                @endif
                <div class="table-responsive"><table>
                    <thead><tr><th>المستوى</th><th>عدد الخانات</th><th>مثال كود</th><th>أقصى عدد فروع للأب</th><th>الأطوال الموجودة الآن</th></tr></thead>
                    <tbody>
                    @for($level = 1; $level <= $maxLevels; $level++)
                        <tr data-level="{{ $level }}">
                            <td>المستوى {{ $level }}</td>
                            <td><input class="form-control digit-input" type="number" min="1" max="18" name="digits[{{ $level }}]" value="{{ old('digits.'.$level, $digits[$level] ?? '') }}" aria-label="عدد خانات المستوى {{ $level }}"></td>
                            <td><span class="ltr" data-sample>—</span></td>
                            <td><span class="ltr" data-capacity>—</span></td>
                            <td>
                                @forelse($current->get($level, collect()) as $row)
                                    <span class="length-chip">{{ $row->len }} خانة: {{ number_format($row->n) }}</span>
                                @empty
                                    <span class="text-muted">لا توجد حسابات</span>
                                @endforelse
                            </td>
                        </tr>
                    @endfor
                    </tbody>
                </table></div>
                <p class="hint">املأ المستويات بالتسلسل، وكل مستوى أطول من الذي قبله. اترك المستويات غير المستخدمة فارغة. لتوحيد أكواد الحسابات الموجودة يلزم ترتيب الدليل بالخطة المعتمدة، فحفظ الأطوال وحده لا يغيّرها.</p>
            </div>
        </section>

        <section class="panel">
            <h5>عرض «النوع» في إدارة الدليل</h5>
            <p>يغيّر ما يظهر في الشاشة والتصدير فقط، ولا يغيّر نوع الحساب الذي يعتمد عليه البرنامج.</p>
            <div class="choices">
                <label class="choice">
                    <input type="radio" name="type_display" value="system" {{ $selectedDisplay === 'system' ? 'checked' : '' }}>
                    <span><strong>نوع الحساب في البرنامج</strong><small>مثل: حساب عادي، عميل، مورد.</small></span>
                </label>
                <label class="choice">
                    <input type="radio" name="type_display" value="{{ \Nami\Finance\Support\ChartSettings::TYPE_DISPLAY_HIERARCHY }}" {{ $selectedDisplay === \Nami\Finance\Support\ChartSettings::TYPE_DISPLAY_HIERARCHY ? 'checked' : '' }}>
                    <span><strong>حسب تسلسل الحساب</strong><small>المستوى الأول والثالث باسمهما، والثاني والرابع وما بعده باسم الحساب الرئيسي. مثال: نوع حساب العميل «العملاء».</small></span>
                </label>
            </div>
        </section>

        <div class="actions"><button class="btn btn-success" type="submit">حفظ الإعدادات</button></div>
    </form>
</div>
@endsection
@section('js')
<script>
(function () {
    var box = document.getElementById('digits-box');
    var rows = Array.prototype.slice.call(box.querySelectorAll('tr[data-level]'));

    function toggleBox() {
        var custom = document.querySelector('input[name="numbering_mode"]:checked').value === 'custom';
        box.hidden = !custom;
        rows.forEach(function (row) { row.querySelector('input').disabled = !custom; });
    }

    // المعاينة تتوقف عند أول مستوى فارغ أو لا يزيد عن سابقه
    function preview() {
        var code = '', previous = 0, valid = true;
        rows.forEach(function (row, index) {
            var length = parseInt(row.querySelector('input').value, 10);
            var sample = row.querySelector('[data-sample]'), capacity = row.querySelector('[data-capacity]');
            valid = valid && length > previous;
            if (!valid) { sample.textContent = '—'; capacity.textContent = '—'; return; }
            var width = length - previous;
            code += width > 1 ? '0'.repeat(width - 1) + '1' : '1';
            sample.textContent = code;
            capacity.textContent = index === 0 ? '—' : (Math.pow(10, width) - 1).toLocaleString('en-US');
            previous = length;
        });
    }

    document.querySelectorAll('input[name="numbering_mode"]').forEach(function (radio) { radio.addEventListener('change', toggleBox); });
    rows.forEach(function (row) { row.querySelector('input').addEventListener('input', preview); });
    toggleBox();
    preview();
})();
</script>
@endsection
