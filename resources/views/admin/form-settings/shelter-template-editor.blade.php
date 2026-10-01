<form method="post" action="{{ route('form-settings.shelter-templates.save', $templateId ? ['id' => $templateId] : []) }}" class="mt-3">
    @csrf
    <input type="hidden" name="kind" value="{{ $templateValues['kind'] }}">
    <p class="text-muted">استخدم [اسم_الشركة] لعرض اسم الشركة من إعدادات النظام تلقائيًا في العنوان أو نص الإقرار.</p>
    <div class="row g-3">
        @foreach (['name' => 'اسم القالب', 'company_name' => 'اسم الشركة في الطباعة (عربي / إنجليزي)', 'title' => 'عنوان النموذج'] as $key => $label)
            <label class="col-md-6">{{ $label }}
                <input class="form-control" name="{{ $key }}" value="{{ $templateValues[$key] }}" required maxlength="255">
            </label>
        @endforeach
        <label class="col-md-6">الحالة
            <select name="active" class="form-select">
                <option value="1" {{ $templateValues['active'] ? 'selected' : '' }}>متاح للاختيار</option>
                <option value="0" {{ !$templateValues['active'] ? 'selected' : '' }}>معطّل للعقود الجديدة</option>
            </select>
        </label>
        <label class="col-12">نص الإقرار
            <textarea name="declaration" class="form-control" rows="3" required maxlength="10000">{{ $templateValues['declaration'] }}</textarea>
        </label>
        <label class="col-12">تذييل إضافي
            <textarea name="footer" class="form-control" rows="2" maxlength="2000">{{ $templateValues['footer'] ?? '' }}</textarea>
        </label>
    </div>
    <button type="submit" class="btn btn-primary mt-3">{{ $templateId ? 'حفظ تعديل القالب' : 'إضافة القالب' }}</button>
</form>
