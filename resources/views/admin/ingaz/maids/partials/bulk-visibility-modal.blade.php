@if(AdminGuardHasPerm(\App\PermKeys::MAIDS_BULK_VISIBILITY))
<div class="modal fade" id="bulkVisibilityModal" tabindex="-1" aria-labelledby="bulkVisibilityTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkVisibilityTitle">إظهار/إخفاء سير ذاتية</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <form id="bulkVisibilityForm">
                    <fieldset id="bulkVisibilityFields">
                        <div class="mb-3">
                            <label class="form-check form-check-inline"><input class="form-check-input" type="radio" name="is_show" value="1"> إظهار</label>
                            <label class="form-check form-check-inline"><input class="form-check-input" type="radio" name="is_show" value="0" checked> إخفاء</label>
                        </div>
                        <div class="mb-3">
                            <label for="visibilityNationality" class="form-label">الجنسية</label>
                            <select name="nationality_id" id="visibilityNationality" class="form-select">
                                <option value="">جميع الجنسيات</option>
                                @foreach($filters['nationalities'] as $nationality)
                                    <option value="{{ $nationality->id }}">{{ $nationality->sub_title ?? $nationality->title ?? '-' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="visibilityOffices" class="form-label">المكاتب (يمكن اختيار أكثر من مكتب)</label>
                            <div class="mb-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="visibilitySelectAll">تحديد الكل</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="visibilityClearOffices">إلغاء التحديد</button>
                            </div>
                            <select name="office_ids[]" id="visibilityOffices" class="form-select" multiple size="6" required>
                                @foreach($filters['offices'] as $office)
                                    <option value="{{ $office->id }}" data-nationality-id="{{ $office->nationality_id }}">{{ $office->title ?? '-' }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">يمكن تحديد الكل ثم إزالة المكتب المستثنى.</small>
                        </div>
                        <div class="mb-3">
                            <div class="form-label">حالة السيرة</div>
                            <label class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="statuses[]" value="available" checked> متاحة</label>
                            <label class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="statuses[]" value="reserved"> محجوزة</label>
                        </div>
                    </fieldset>
                    <p class="small text-muted">تُستبعد السير المرتبطة بعقود نشطة أو غير المعتمدة. الإظهار لا يتجاوز تعليق المكتب. لن تتغير حالة السيرة أو الحجوزات، ولن تشمل العملية السير التي تُضاف لاحقًا.</p>
                    <div class="alert alert-info" id="visibilityCount" role="status" aria-live="polite">اختر المكاتب لعرض عدد السير التي ستتغير فعليًا.</div>
                    <div class="text-danger mb-2" id="visibilityError" role="alert"></div>
                    <button type="button" class="btn btn-outline-secondary" id="visibilityRefresh">تحديث العدد</button>
                    <button type="submit" class="btn btn-primary" id="visibilityApply" disabled>تنفيذ</button>
                </form>
                <hr>
                <h6>سجل عملياتك</h6>
                <p class="small text-muted">التراجع عن الإخفاء يعيد فقط السير التي أخفتها الدفعة ولم تتغير بعدها وما زالت مؤهلة للظهور. تُتخطى البقية دون تغييرها.</p>
                <div id="visibilityHistory"></div>
            </div>
        </div>
    </div>
</div>
@endif
