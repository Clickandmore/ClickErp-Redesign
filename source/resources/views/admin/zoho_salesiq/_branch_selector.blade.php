@php
    $branchCollection = $branches ?? collect();
    $selectedBranchId = $currentBranch?->id;
@endphp

@if ($branchCollection->count() > 1)
    <div class="card zoho-panel mb-3">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                @foreach (request()->except('branch_id') as $key => $value)
                    @if (is_scalar($value))
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <div class="col-md-5">
                    <label class="form-label">الشركة / الفرع</label>
                    <select name="branch_id" class="form-select" onchange="this.form.submit()">
                        @foreach ($branchCollection as $branchOption)
                            <option value="{{ $branchOption->id }}" @selected((string) $selectedBranchId === (string) $branchOption->id)>
                                {{ $branchOption->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-7">
                    <small class="text-muted d-block">
                        بيانات الربط المعروضة هنا تخص الشركة/الفرع المحدد، مع استخدام الإعداد العام كاحتياطي عند ترك أي قيمة فارغة.
                    </small>
                </div>
            </form>
        </div>
    </div>
@elseif ($currentBranch)
    <div class="alert alert-light border zoho-panel mb-3">
        إعدادات Zoho المستخدمة: <strong>{{ $currentBranch->name }}</strong>
    </div>
@endif
