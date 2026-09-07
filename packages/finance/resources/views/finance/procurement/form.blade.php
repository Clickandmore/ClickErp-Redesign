@extends('click.layout.indexs.index')
@section('page-title', $doc->kind === 'request' ? 'طلب مشتريات' : 'فاتورة مشتريات')
@section('content')
    <div class="container-fluid" dir="rtl">
        <h3>{{ $doc->exists ? 'تعديل' : 'إنشاء' }} {{ $doc->kind === 'request' ? 'طلب مشتريات' : 'فاتورة مشتريات' }}
            {{ $doc->number }}</h3>
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="POST"
            action="{{ $doc->exists ? route('finance.procurement.update', $doc->id) : route('finance.procurement.store') }}">
            @csrf @if ($doc->exists)
                @method('PUT')
            @endif
            <input type="hidden" name="kind" value="{{ $doc->kind }}">
            <div class="card card-body mb-3">
                <div class="row g-3">
                    <div class="col-md-4"><label>المورد *</label><select name="supplier_id" class="form-select" required>
                            <option value="">اختر المورد</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}"
                                    {{ old('supplier_id', $doc->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}</option>
                            @endforeach
                        </select>
                        <a href="{{ route('finance.suppliers.index') }}" target="_blank">إضافة / إدارة الموردين</a>
                    </div>
                    <div class="col-md-4"><label>تاريخ المستند *</label><input type="date" name="date"
                            class="form-control" required value="{{ old('date', $doc->date) }}"></div>
                    <div class="col-md-4"><label>تاريخ الاستحقاق</label><input type="date" name="due_date"
                            class="form-control" value="{{ old('due_date', $doc->due_date) }}"></div>
                    <div class="col-md-4"><label>رقم فاتورة المورد / المرجع</label><input name="supplier_reference"
                            class="form-control" maxlength="255"
                            value="{{ old('supplier_reference', $doc->supplier_reference) }}"></div>
                    <div class="col-md-4"><label>الفرع</label><select name="branch_id" class="form-select"
                            {{ $doc->exists ? 'disabled' : '' }}>
                            <option value="">فرع المستخدم</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}"
                                    {{ old('branch_id', $doc->branch_id ?? auth('admin')->user()->branch_id) == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name_ar }}</option>
                            @endforeach
                        </select>
                        @if ($doc->exists)
                            <input type="hidden" name="branch_id" value="{{ $doc->branch_id }}">
                        @endif
                    </div>
                    <div class="col-md-4"><label>مركز التكلفة</label><select name="cost_center_id" class="form-select">
                            <option value="">بدون</option>
                            @foreach ($centers as $center)
                                <option value="{{ $center->id }}"
                                    {{ old('cost_center_id', $doc->cost_center_id) == $center->id ? 'selected' : '' }}>
                                    {{ $center->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><label>رقم عقد الاستقدام (اختياري)</label><input type="number" min="1"
                            name="contract_id" class="form-control"
                            value="{{ old('contract_id', $doc->contract_id) }}"><small class="text-muted">اتركه فارغاً
                            للمشتريات العامة؛ يُستخدم فقط إذا كانت المشتريات تخص عقداً معيناً.</small></div>
                    <div class="col-md-4"><label>حساب ضريبة المشتريات</label><select name="tax_account_id"
                            class="form-select">
                            <option value="">اختر عند وجود ضريبة</option>
                            @foreach ($taxAccounts as $account)
                                <option value="{{ $account->id }}"
                                    {{ old('tax_account_id', $doc->tax_account_id) == $account->id ? 'selected' : '' }}>
                                    {{ $account->code }} — {{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="card card-body mb-3">
                <h5>بنود الخدمات والمستلزمات</h5>
                <p class="text-muted">أدخل السعر قبل الضريبة والخصم بقيمة ثابتة لكل بند. اختر الحساب المناسب لطبيعة المصروف.
                    الضريبة لا تُفرض تلقائياً.</p>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="min-width:180px">البند</th>
                                <th style="min-width:220px">حساب المصروف / التكلفة</th>
                                <th>الكمية</th>
                                <th>سعر الوحدة</th>
                                <th>الخصم (ر.س)</th>
                                <th>الضريبة %</th>
                                <th>الإجمالي</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="purchase-lines">
                            @php($lines = old('lines', $doc->exists ? $doc->lines->toArray() : [['description' => '', 'account_id' => '', 'quantity' => 1, 'unit_price' => 0, 'discount' => 0, 'tax_rate' => 0]]))
                            @foreach ($lines as $i => $line)
                                <tr>
                                    <td><input class="form-control" name="lines[{{ $i }}][description]"
                                            value="{{ $line['description'] }}" maxlength="500" required></td>
                                    <td><select class="form-select" name="lines[{{ $i }}][account_id]" required>
                                            <option value="">اختر الحساب</option>
                                            @foreach ($accounts as $account)
                                                <option value="{{ $account->id }}"
                                                    {{ $line['account_id'] == $account->id ? 'selected' : '' }}>
                                                    {{ $account->code }} — {{ $account->name }}</option>
                                            @endforeach
                                        </select></td>
                                    @foreach (['quantity' => [0.001, 0.001, 1000000], 'unit_price' => [0, 0.01, 100000000], 'discount' => [0, 0.01, 100000000], 'tax_rate' => [0, 0.01, 100]] as $field => $limits)
                                        <td><input style="min-width:95px" type="number" class="form-control"
                                                data-field="{{ $field }}"
                                                name="lines[{{ $i }}][{{ $field }}]"
                                                min="{{ $limits[0] }}" step="{{ $limits[1] }}"
                                                max="{{ $limits[2] }}" value="{{ $line[$field] }}" required></td>
                                    @endforeach
                                    <td class="line-total text-nowrap"></td>
                                    <td><button type="button" class="btn btn-outline-danger remove-line"
                                            aria-label="حذف البند">×</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div><button type="button" id="add-line" class="btn btn-outline-primary align-self-start">+ إضافة
                    بند</button>
                <div id="purchase-totals" class="text-end fw-bold mt-3" aria-live="polite"></div>
            </div>
            <div class="card card-body mb-3"><label>ملاحظات / سبب الشراء</label>
                <textarea name="notes" class="form-control" rows="3" maxlength="5000">{{ old('notes', $doc->notes) }}</textarea>
            </div>
            <button class="btn btn-primary" type="submit">حفظ كمسودة</button> <a class="btn btn-light"
                href="{{ route('finance.procurement.index') }}">رجوع</a>
        </form>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const body = document.getElementById('purchase-lines');
            let index = Math.max(...Array.from(body.querySelectorAll('input[name]')).map(el => Number(el.name.match(
                /\[(\d+)\]/)[1]))) + 1;
            const template = body.firstElementChild.cloneNode(true);

            function calculate() {
                let netTotal = 0,
                    taxTotal = 0;
                body.querySelectorAll('tr').forEach(row => {
                    const get = f => Number(row.querySelector('[data-field="' + f + '"]').value) || 0;
                    const gross = Math.round(get('quantity') * get('unit_price') * 100),
                        discount = Math.round(get('discount') * 100),
                        net = Math.max(0, gross - discount),
                        tax = Math.round(net * get('tax_rate') / 100);
                    netTotal += net;
                    taxTotal += tax;
                    row.querySelector('.line-total').textContent = ((net + tax) / 100).toFixed(2);
                });
                document.getElementById('purchase-totals').textContent = 'الصافي: ' + (netTotal / 100).toFixed(2) +
                    ' | الضريبة: ' + (taxTotal / 100).toFixed(2) + ' | الإجمالي: ' + ((netTotal + taxTotal) / 100)
                    .toFixed(2) + ' ر.س';
            }
            body.addEventListener('input', calculate);
            body.addEventListener('click', e => {
                if (e.target.closest('.remove-line') && body.children.length > 1) {
                    e.target.closest('tr').remove();
                    calculate();
                }
            });
            document.getElementById('add-line').addEventListener('click', () => {
                if (body.children.length >= 100) return;
                const row = template.cloneNode(true);
                row.querySelectorAll('[name]').forEach(el => {
                    el.name = el.name.replace(/lines\[\d+\]/, 'lines[' + index + ']');
                    el.value = el.dataset.field === 'quantity' ? 1 : (el.type === 'number' ? 0 :
                    '');
                });
                index++;
                body.appendChild(row);
                calculate();
            });
            calculate();
        });
    </script>
@endsection
