@php($editingType = $type ?? null)
<div class="row">
    <div class="col-md-6 mb-3"><label class="form-label">اسم التصنيف</label><input type="text" name="name" class="form-control" required value="{{ old('name', $editingType?->name) }}" placeholder="مثال: شكوى عميل ضد عاملة">@error('name')<div class="text-danger small">{{ $message }}</div>@enderror</div>
    <div class="col-md-3 mb-3"><label class="form-label">مقدم الشكوى</label><select name="complainant_type" class="form-select" required><option value="">اختر</option>@foreach($partyTypes as $value => $label)<option value="{{ $value }}" {{ old('complainant_type', $editingType?->complainant_type) === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
    <div class="col-md-3 mb-3"><label class="form-label">المشتكى عليه</label><select name="against_type" class="form-select" required><option value="">اختر</option>@foreach($partyTypes as $value => $label)<option value="{{ $value }}" {{ old('against_type', $editingType?->against_type) === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
    <div class="col-12 mb-3">
        <div class="alert alert-info small mb-0 d-flex gap-2 align-items-start">
            <i class="fas fa-info-circle mt-1"></i>
            <div>
                صفة <strong>مقدم الشكوى</strong> و<strong>المشتكى عليه</strong> تحدد الحقول التي تظهر للموظف عند تسجيل الشكوى بهذا التصنيف:
                العميل يُختار من العملاء أو يُدخل يدويًا، والعاملة تُختار من السير أو تُدخل كعاملة غير مسجلة، والمكتب الخارجي والموظف يُختاران من قوائمهما.
                اختر «أخرى» للطرفين إذا أردت أن يحدد الموظف صفة الطرفين بنفسه.
                تغيير صفة تصنيف مستخدم لا يغيّر الشكاوى المحفوظة به.
            </div>
        </div>
    </div>
    <div class="col-md-9 mb-3"><label class="form-label">الوصف</label><textarea name="description" class="form-control" rows="3">{{ old('description', $editingType?->description) }}</textarea></div>
    <div class="col-md-2 mb-3"><label class="form-label">الترتيب</label><input type="number" min="0" name="sort_order" class="form-control" value="{{ old('sort_order', $editingType?->sort_order ?? 0) }}"></div>
    <div class="col-md-1 mb-3 d-flex align-items-end"><div class="form-check mb-2"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" {{ old('is_active', $editingType?->is_active ?? true) ? 'checked' : '' }}><label class="form-check-label" for="isActive">نشط</label></div></div>
</div>
