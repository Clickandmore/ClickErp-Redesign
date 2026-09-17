@extends('admin.layout.indexs.index')
@section('page-title', 'تسجيل تكلفة التفويض')
@section('content')
<div class="page-body"><div class="container-fluid">
    <h3>تسجيل تكلفة التفويض</h3>
    <p class="text-muted">اختر تفويضًا أو أكثر وأدخل التكلفة الفعلية لكل تفويض. عند التأكيد يُنشأ قيد لكل فاتورة: مدين تكلفة التفويض، ودائن حساب الدفع المختار.</p>
    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
    @endif
    <form id="delegation-cost-form" method="POST" action="{{ route('delegation-costs.store') }}" class="card card-body">
        @csrf
        <h5 class="mb-3">بيانات دفع التكلفة</h5>
        <div class="row g-3 mb-3">
            <div class="col-md-3"><label class="form-label">تاريخ التكلفة</label><input class="form-control" type="date" name="date" required value="{{ old('date', date('Y-m-d')) }}"></div>
            <div class="col-md-3"><label class="form-label">مرجع التحويل أو الصرف</label><input class="form-control" name="reference" maxlength="100" required value="{{ old('reference') }}"></div>
            <div class="col-md-3"><label class="form-label">مصدر الدفع</label><select class="form-select" name="payment_source_type" id="cost-source">
                @foreach (['bank' => 'تحويل بنكي', 'custody' => 'عهدة موظف', 'ingaz' => 'حساب إنجاز'] as $key => $label)
                    <option value="{{ $key }}" @if(old('payment_source_type', 'bank') === $key) selected @endif>{{ $label }}</option>
                @endforeach
            </select></div>
            <div class="col-md-3"><label class="form-label">حساب الدفع</label><select class="form-select" name="payment_account_id" id="cost-account" required>
                <option value="">اختر الحساب</option>
                @foreach ($accounts as $source => $sourceAccounts)
                    @foreach ($sourceAccounts as $account)
                        <option data-source="{{ $source }}" value="{{ $account->id }}" @if(old('payment_account_id') == $account->id && old('payment_source_type', 'bank') === $source) selected @endif>{{ $account->code }} — {{ $account->name }}</option>
                    @endforeach
                @endforeach
            </select></div>
        </div>
        <h5 class="mt-3">اختيار التفويضات وتعديل التكلفة</h5>
        <p class="text-muted">يمكن تعديل مبلغ كل تفويض مباشرة؛ تعديل المبلغ يحدد التفويض تلقائيًا للحفظ.</p>
        <div class="row g-3 mb-3">
            <div class="col-md-5"><label for="cost-search" class="form-label">بحث</label><input type="search" class="form-control" id="cost-search" placeholder="رقم التفويض أو إنجاز أو مساند أو العميل"></div>
            <div class="col-md-3"><label for="cost-sort" class="form-label">ترتيب التفويضات</label><select id="cost-sort" class="form-select"><option value="newest">الأحدث أولًا</option><option value="oldest">الأقدم أولًا</option><option value="client">اسم العميل</option><option value="amount">التكلفة من الأقل للأعلى</option></select></div>
            <div class="col-md-4"><label for="bulk-cost" class="form-label">مبلغ موحد للتفويضات المحددة</label><div class="input-group"><input id="bulk-cost" class="form-control" type="number" min="0.01" max="999999999.99" step="0.01" placeholder="المبلغ بالريال"><button type="button" id="apply-bulk-cost" class="btn btn-outline-primary">تطبيق</button></div></div>
        </div>
        <div class="d-flex gap-2 mb-3"><button type="button" id="select-visible-costs" class="btn btn-sm btn-outline-primary">تحديد نتائج البحث</button><button type="button" id="clear-costs" class="btn btn-sm btn-outline-secondary">إلغاء كل التحديد</button></div>
        <div id="cost-form-warning" class="alert alert-warning" role="alert" hidden></div>
        <div class="table-responsive"><table class="table"><thead><tr><th>اختيار</th><th>التفويض</th><th>مساند</th><th>العميل</th><th>تكلفة التفويض</th></tr></thead><tbody id="cost-rows">
            @forelse ($delegations as $delegation)
                <tr class="cost-row" data-id="{{ $delegation->id }}" data-client="{{ $delegation->user?->name }}">
                    <td><input type="checkbox" class="cost-check" name="delegation_ids[]" value="{{ $delegation->id }}" @if(in_array($delegation->id, old('delegation_ids', $selectedIds))) checked @endif></td>
                    <td><a href="{{ route('ingaz-delegations.show', $delegation) }}">#{{ $delegation->id }}</a> / {{ $delegation->ingaz_number }}</td>
                    <td>{{ $delegation->msaned_contract_number }}</td><td>{{ $delegation->user?->name }}</td>
                    <td><input class="form-control cost-amount" type="number" min="0.01" max="999999999.99" step="0.01" data-name="amounts[{{ $delegation->id }}]" value="{{ old('amounts.'.$delegation->id, $defaultAmount) }}"></td>
                </tr>
            @empty
                <tr><td colspan="5">لا توجد تفويضات بفواتير تنتظر تسجيل التكلفة.</td></tr>
            @endforelse
        </tbody></table></div>
        <div class="bg-light rounded p-3 mt-3"><p>عدد التفويضات: <strong id="cost-count">0</strong> — إجمالي التكلفة: <strong id="cost-total">0.00</strong> ريال</p>
        <label class="mb-3"><input type="checkbox" id="cost-confirmed" name="confirmed" value="1" required> أؤكد صحة التكلفة ومصدر الدفع ومرجع العملية للتفويضات المختارة.</label>
        <div><button id="save-costs" class="btn btn-primary" type="submit" @if($delegations->isEmpty()) disabled @endif>تأكيد وحفظ تكلفة التفويض</button> <a class="btn btn-light" href="{{ route('ingaz-delegations.index') }}">رجوع</a></div>
        </div>
    </form>
</div></div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const source = document.getElementById('cost-source'), account = document.getElementById('cost-account');
    function updateAccounts() {
        Array.from(account.options).forEach(option => {
            const excluded = option.dataset.source && option.dataset.source !== source.value;
            option.hidden = !!excluded; option.disabled = !!excluded;
            if (excluded && option.selected) account.value = '';
        });
    }
    const form = document.getElementById('delegation-cost-form');
    const rows = Array.from(document.querySelectorAll('.cost-row'));
    const confirmed = document.getElementById('cost-confirmed');
    const warning = document.getElementById('cost-form-warning');
    function warn(message) { warning.textContent = message; warning.hidden = !message; }
    function updateTotal() {
        let cents = 0, count = 0;
        rows.forEach(row => {
            const selected = row.querySelector('.cost-check').checked, amount = row.querySelector('.cost-amount');
            amount.required = selected;
            if (selected) amount.name = amount.dataset.name;
            else amount.removeAttribute('name');
            row.classList.toggle('table-primary', selected);
            if (selected) { count++; cents += Math.round((Number(amount.value) || 0) * 100); }
        });
        document.getElementById('cost-count').textContent = count;
        document.getElementById('cost-total').textContent = (cents / 100).toFixed(2);
        document.getElementById('save-costs').disabled = count === 0 || count > 200;
        warn(count > 200 ? 'الحد الأقصى 200 تفويض في العملية الواحدة.' : '');
    }
    function changed() { confirmed.checked = false; updateTotal(); }
    source.addEventListener('change', updateAccounts);
    form.addEventListener('input', event => {
        if (event.target === confirmed || ['cost-search', 'cost-sort', 'bulk-cost'].includes(event.target.id)) return;
        if (event.target.classList.contains('cost-amount')) event.target.closest('.cost-row').querySelector('.cost-check').checked = true;
        changed();
    });
    form.addEventListener('change', event => {
        if (event.target === source || event.target === account) changed();
    });
    document.getElementById('apply-bulk-cost').addEventListener('click', function () {
        const bulk = document.getElementById('bulk-cost');
        if (!bulk.value || !bulk.checkValidity()) { warn('أدخل مبلغًا صحيحًا أكبر من صفر وبحد أقصى منزلتين عشريتين.'); return; }
        const selected = rows.filter(row => row.querySelector('.cost-check').checked);
        if (!selected.length) { warn('حدد التفويضات أولًا لتطبيق المبلغ عليها.'); return; }
        selected.forEach(row => row.querySelector('.cost-amount').value = bulk.value);
        changed();
    });
    document.getElementById('select-visible-costs').addEventListener('click', function () {
        rows.filter(row => !row.hidden).forEach(row => row.querySelector('.cost-check').checked = true);
        changed();
    });
    document.getElementById('clear-costs').addEventListener('click', function () {
        rows.forEach(row => row.querySelector('.cost-check').checked = false);
        changed();
    });
    document.getElementById('cost-search').addEventListener('input', function () {
        rows.forEach(row => row.hidden = !row.textContent.toLowerCase().includes(this.value.trim().toLowerCase()));
    });
    document.getElementById('cost-sort').addEventListener('change', function () {
        const mode = this.value;
        rows.sort((a, b) => {
            if (mode === 'client') return a.dataset.client.localeCompare(b.dataset.client, 'ar') || Number(a.dataset.id) - Number(b.dataset.id);
            if (mode === 'amount') return Number(a.querySelector('.cost-amount').value) - Number(b.querySelector('.cost-amount').value) || Number(a.dataset.id) - Number(b.dataset.id);
            return mode === 'oldest' ? Number(a.dataset.id) - Number(b.dataset.id) : Number(b.dataset.id) - Number(a.dataset.id);
        }).forEach(row => document.getElementById('cost-rows').appendChild(row));
    });
    form.addEventListener('submit', function (event) {
        const count = rows.filter(row => row.querySelector('.cost-check').checked).length;
        if (!count || count > 200) { event.preventDefault(); warn('اختر من تفويض واحد إلى 200 تفويض.'); return; }
        document.getElementById('save-costs').disabled = true;
        document.getElementById('save-costs').textContent = 'جارٍ تسجيل التكلفة…';
    });
    updateAccounts(); updateTotal();
});
</script>
@endsection
