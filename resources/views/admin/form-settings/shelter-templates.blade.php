<div class="card mt-4" id="shelter-templates">
    <div class="card-body">
        <h4>قوالب الإيواء الإضافية</h4>
        <p class="text-muted">أضف قالب استلام أو تسليم عامًا. يُختار قالب التسليم المفعّل تلقائيًا عند تسليم العاملة دون الحاجة لتعديل عقد الإيواء. عند وجود أكثر من قالب تسليم، يُستخدم أول قالب مفعّل أُضيف، ويمكن اختيار قالب آخر من العقد. الطباعة تستخدم القالب الحالي واسم الشركة من إعدادات النظام. يمكن رفع النموذج الموقّع ضمن مرفقات العقد.</p>
        @if (session('shelter_template_saved'))
            <div class="alert alert-success">تم حفظ القالب. يمكن اختياره في عقد الإيواء.</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        @if (app(\App\Services\ShelterDocumentService::class)->available())
            @php
                $shelterTemplates = \App\Models\ShelterDocumentTemplate::orderBy('company_name')->orderBy('name')->get();
                $canManageShelterTemplates = \App\Support\AdminRoutePermissionMatrix::adminHasAnyPermission(auth('admin')->user(), [\App\PermKeys::SETTINGS_CONTROL]);
            @endphp
            @foreach ($shelterTemplates as $shelterTemplate)
                <details class="border rounded p-3 mb-2">
                    <summary>{{ $shelterTemplate->name }} — {{ $shelterTemplate->company_name }} ({{ $shelterTemplate->active ? 'متاح للاختيار' : 'معطّل للعقود الجديدة' }})</summary>
                    <a class="btn btn-outline-info my-2" target="_blank" rel="noopener" href="{{ route('form-settings.shelter-templates.preview', $shelterTemplate->id) }}">معاينة وطباعة تجريبية</a>
                    @if ($canManageShelterTemplates)
                        @include('admin.form-settings.shelter-template-editor', ['templateValues' => $shelterTemplate->toArray(), 'templateId' => $shelterTemplate->id])
                    @endif
                </details>
            @endforeach
            @if ($canManageShelterTemplates)
                @foreach (\App\Support\ShelterDocumentFields::kinds() as $kind => $label)
                    <details class="border rounded p-3 mb-2">
                        <summary>إضافة قالب {{ $label }} — نموذج عام جاهز للتعديل</summary>
                        @include('admin.form-settings.shelter-template-editor', ['templateValues' => \App\Support\ShelterDocumentFields::starter($kind), 'templateId' => null])
                    </details>
                @endforeach
            @endif
        @else
            <p class="text-muted">ستتاح إدارة القوالب بعد تحديث قاعدة البيانات.</p>
        @endif
    </div>
</div>
