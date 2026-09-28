{{-- حقول الفريق: تُستخدم في صفحة الإضافة/التعديل وفي مودال قائمة الفرق. تتطلب: $team --}}
@php
    $teamActive = (bool) old('is_active', $team->is_active ?? true);
@endphp
<div class="mb-3">
    <label class="form-label" for="name">اسم الفريق <span class="text-danger">*</span></label>
    <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $team->name) }}" maxlength="150" required placeholder="مثال: فريق شمال الرياض">
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="description">الوصف</label>
    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" maxlength="2000" placeholder="نطاق عمل الفريق أو أي ملاحظات تنظيمية">{{ old('description', $team->description) }}</textarea>
    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="ht-switch form-switch">
    <input type="hidden" name="is_active" value="0">
    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ $teamActive ? 'checked' : '' }}>
    <div>
        <label for="is_active">الفريق نشط</label>
        <small>الفريق المتوقف لا يُسند إليه سائقون جدد.</small>
    </div>
</div>
