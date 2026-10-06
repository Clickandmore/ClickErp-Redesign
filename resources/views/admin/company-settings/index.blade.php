@extends('admin.layout.indexs.index')
@section('page-title', 'إعدادات الشركة')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>إعدادات الشركة</h4>
    <a href="{{ route('RecruitmentContract.company.index') }}" class="btn btn-outline-secondary">عقود الشركة</a>
</div>
@if ($errors->any())
    <div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
@endif
@if (session('company_migration_done'))
    <div class="alert alert-success">تم ترحيل عقود الشركة وأرشفة سجلاتها القديمة. رقم العملية: {{ session('company_migration_done') }}</div>
@endif
@if (!$companyIdentityAvailable)
    <div class="alert alert-info">يلزم تطبيق تحديث قاعدة البيانات لإتاحة إدارة الشركة والترحيل. يستمر الموقع بالسلوك الحالي حتى تهيئة الشركة.</div>
@else
<form class="row g-3 from-submit-global" method="post" action="{{ route('company-settings.update') }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    <fieldset class="col-12" @if(!AdminGuardHasPerm(\App\PermKeys::COMPANY_SETTINGS_UPDATE)) disabled @endif>
    <div class="col-12"><div class="card"><div class="card-body">
        <h5>بيانات الشركة</h5>
        <div class="row g-3">
            @php
                $fields = [
                    'company_name' => ['اسم الشركة', $company?->name ?? $settings->translate('ar')->document_company_name ?? ''],
                    'company_name_en' => ['اسم الشركة بالإنجليزية', $company?->name_en ?? $settings->translate('en')->document_company_name ?? ''],
                    'phone' => ['الهاتف', $company?->phone ?? $settings->phone],
                    'other_phone' => ['الهاتف الإضافي', $company?->phone2 ?? $settings->other_phone],
                    'email' => ['البريد الإلكتروني', $company?->email ?? $settings->email],
                    'company_address' => ['العنوان', $company?->address ?? $settings->translate('ar')->location ?? ''],
                    'company_city' => ['المدينة', $company?->city],
                    'tax_number' => ['الرقم الضريبي', $company?->tax_number ?? $settings->tax_number],
                    'company_registration_number' => ['السجل التجاري', $company?->registration_number],
                    'company_license_number' => ['رقم الترخيص', $company?->license_number ?? $settings->company_license_number],
                    'company_license_expiry_date' => ['تاريخ انتهاء الترخيص', $company?->license_expiry_date?->format('Y-m-d')],
                    'company_representative' => ['ممثل الشركة', $company?->representative],
                ];
            @endphp
            @foreach ($fields as $field => [$label, $value])
                <div class="col-md-6">
                    <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                    <input id="{{ $field }}" name="{{ $field }}" class="form-control" type="{{ $field === 'email' ? 'email' : ($field === 'company_license_expiry_date' ? 'date' : 'text') }}" value="{{ old($field, $value) }}" @if(in_array($field, ['company_name', 'phone'])) required @endif
                        @if ($field === 'company_registration_number')
                            inputmode="numeric" dir="ltr" minlength="10" maxlength="10" pattern="7[0-9]{9}" placeholder="7001234567"
                            title="السجل التجاري يجب أن يتكون من 10 أرقام ويبدأ بالرقم 7."
                        @elseif ($field === 'tax_number')
                            inputmode="numeric" dir="ltr" minlength="15" maxlength="15" pattern="3[0-9]{14}" placeholder="300123456789003"
                            title="الرقم الضريبي يجب أن يتكون من 15 رقمًا ويبدأ بالرقم 3."
                        @endif>
                    @if ($field === 'company_registration_number')
                        <small class="text-muted">10 أرقام تبدأ بالرقم 7، مثل 7001234567.</small>
                    @elseif ($field === 'tax_number')
                        <small class="text-muted">15 رقمًا تبدأ بالرقم 3، مثل 300123456789003. النهاية المعتادة لضريبة القيمة المضافة هي 003.</small>
                    @endif
                </div>
            @endforeach
            <div class="col-md-6">
                <label for="company_account_id" class="form-label">الحساب المحاسبي للشركة</label>
                <select name="company_account_id" id="company_account_id" class="form-control select2">
                    <option value="">اختر حسابًا للعمليات المالية الجديدة</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}" @selected((int) old('company_account_id', $company?->account_id) === (int) $account->id)>{{ $account->code }} — {{ $account->name }}</option>
                    @endforeach
                </select>
                <small class="text-muted">العقود المرحلة تحتفظ بحساباتها التاريخية. الحساب المحدد يُستخدم للعقود الجديدة.</small>
            </div>
            <div class="col-md-6">
                <label for="company_logo" class="form-label">شعار الشركة على العقود والمستندات</label>
                <input id="company_logo" type="file" name="document_logo" class="form-control" accept="image/*">
                @if ($settings->document_logo ?: $settings->logo_header)
                    <img src="{{ showFile($settings->document_logo ?: $settings->logo_header) }}" alt="شعار الشركة" class="mt-2" style="max-height:100px;max-width:100%">
                @endif
            </div>
        </div>
    </div></div></div>
    </fieldset>
    @if (@AdminGuardHasPerm(\App\PermKeys::COMPANY_SETTINGS_UPDATE))
        <div class="col-12 text-end"><button class="btn btn-primary" type="submit">حفظ بيانات الشركة</button></div>
    @endif
</form>
@if ($company && @AdminGuardHasPerm(\App\PermKeys::COMPANY_SETTINGS_MIGRATION_PREVIEW))
<div class="card mt-4"><div class="card-body">
    <h5>ترحيل عقود الشركة القديمة</h5>
    <p>اختر سجلات العملاء التي تمثل هذه الشركة فقط. تُنقل ملكية عقودها إلى الشركة وتُؤرشف السجلات، مع حفظ الفواتير والحسابات والطلبات ونقل الخدمات بتاريخها الأصلي.</p>
    <form method="post" action="{{ route('company-settings.migration.preview') }}">
        @csrf
        <label for="source_user_ids" class="form-label">سجلات الشركة القديمة</label>
        <select id="source_user_ids" name="source_user_ids[]" class="form-control select2" multiple required>
            @foreach ($companyClients as $client)
                <option value="{{ $client->id }}" @selected(in_array($client->id, $migrationPreview['source_user_ids'] ?? []))>#{{ $client->id }} — {{ $client->name }} — {{ $client->phone }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-outline-primary mt-3">معاينة الترحيل</button>
    </form>
    @if ($migrationPreview)
        <div class="border rounded p-3 mt-3">
            <h6>معاينة: {{ count($migrationPreview['contracts']) }} عقد</h6>
            @foreach ($migrationPreview['sources'] as $source)
                <p>#{{ $source['id'] }} — {{ $source['name'] }} — الحساب التاريخي: {{ $source['account_id'] ?: 'غير محدد' }}</p>
            @endforeach
            <p>العقود: {{ implode('، ', array_column($migrationPreview['contracts'], 'id')) ?: 'لا توجد عقود' }}</p>
            <p>عمليات نقل الخدمات التي كانت الشركة فيها كفيلًا سابقًا: {{ count($migrationPreview['transfers']) }}. تُربط هوية الكفيل السابق بالشركة مع إبقاء العميل الحالي وبيانات العملية.</p>
            @foreach ($migrationPreview['related'] as $kind => $count)
                <div>{{ ['orders' => 'الطلبات المحفوظة', 'rental_contracts' => 'عقود الإيجار المحفوظة', 'travel_histories' => 'السفر', 'invoices' => 'الفواتير المباشرة', 'contract_invoices' => 'فواتير العقود', 'sponsorship_transfers' => 'نقل الخدمات', 'journal_lines' => 'سطور القيود المحفوظة', 'bonds' => 'السندات المحفوظة'][$kind] ?? $kind }}: {{ $count }}</div>
            @endforeach
            @if (@AdminGuardHasPerm(\App\PermKeys::COMPANY_SETTINGS_MIGRATION_EXECUTE))
            <form method="post" action="{{ route('company-settings.migration.execute') }}" class="mt-3">
                @csrf
                <input type="hidden" name="fingerprint" value="{{ $migrationPreview['fingerprint'] }}">
                <label><input type="checkbox" name="confirm_migration" value="1" required> راجعت السجلات والعقود وأؤكد أنها تخص الشركة.</label>
                <button type="submit" class="btn btn-primary d-block mt-3">تنفيذ الترحيل وأرشفة السجلات القديمة</button>
            </form>
            @endif
        </div>
    @endif
</div></div>
@endif
@if ($migrationHistory->isNotEmpty())
    <div class="card mt-4"><div class="card-body"><h5>سجل الترحيل</h5>
        @foreach ($migrationHistory as $migration)
            <p>العملية #{{ $migration->id }} — {{ $migration->created_at }} — السجلات: {{ implode('، ', json_decode($migration->source_user_ids, true)) }} — العقود: {{ count(json_decode($migration->contract_ids, true)) }}</p>
        @endforeach
    </div></div>
@endif
@endif
@endsection

@section('js')
<script>
    $(function () {
        $('#company_account_id, #source_user_ids').each(function () {
            var field = $(this);
            if (field.hasClass('select2-hidden-accessible')) {
                field.select2('destroy');
            }
            field.select2({
                width: '100%',
                dir: document.documentElement.dir || 'rtl',
                dropdownParent: field.parent(),
                minimumResultsForSearch: 0,
                language: {
                    noResults: function () { return 'لا توجد نتائج'; },
                    searching: function () { return 'جارٍ البحث…'; }
                }
            });
        });
    });
</script>
@endsection
