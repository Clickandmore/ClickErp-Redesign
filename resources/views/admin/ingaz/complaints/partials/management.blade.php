@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if ($errors->any())<div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
@if (!$complaint->isClosed() && (AdminGuardHasPerm(\App\PermKeys::COMPLAINT_EDIT) || AdminGuardHasPerm(\App\PermKeys::COMPLAINT_CONTROL)))
<section class="detail-section mb-4">
    <div class="detail-section__header"><h5>إدارة الشكوى</h5></div>
    <div class="detail-section__body">
        <div class="d-flex flex-wrap gap-2 mb-3">
            @if (AdminGuardHasPerm(\App\PermKeys::COMPLAINT_EDIT))
                <button type="button" class="btn btn-primary assign-ticket"><i class="fas fa-user-tag me-1"></i>إسناد الشكوى وإخطار الموظف</button>
            @endif
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#complaintLinkForm">{{ $relatedRecord ? 'تغيير ربط الشكوى' : 'ربط الشكوى بعقد أو سجل آخر' }}</button>
        </div>
        <form method="POST" action="{{ route('complaints.link', $complaint) }}" id="complaintLinkForm" class="collapse {{ $errors->has('relation_type') ? 'show' : '' }} border rounded p-3 mb-3">
            @csrf
            <label class="form-label">نوع السجل حسب سياق الشكوى</label>
            <select name="relation_type" id="complaintRelationType" class="form-select mb-3">
                @foreach (['contract' => 'عقد استقدام / إيواء', 'order' => 'طلب استقدام', 'office' => 'وكيل خارجي', 'client' => 'عميل', 'manual' => 'مرجع آخر (مثل عقد تأجير)'] as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <div data-relation-panel="contract"><label class="form-label">البحث عن عقد بالرقم أو العميل أو العاملة</label><select name="contract_id" class="form-select complaint-record-search" data-url="{{ route('complaints.search-contracts') }}"></select></div>
            <div data-relation-panel="order"><label class="form-label">البحث عن طلب بالرقم أو العميل أو العاملة</label><select name="order_id" class="form-select complaint-record-search" data-url="{{ route('complaints.search-orders') }}"></select></div>
            <div data-relation-panel="office"><label class="form-label">الوكيل الخارجي</label><select name="recruitment_office_id" class="form-select"><option value="">اختر المكتب</option>@foreach ($recruitmentOffices as $office)<option value="{{ $office->id }}">{{ $office->title }}</option>@endforeach</select></div>
            <div data-relation-panel="client"><label class="form-label">العميل</label><select name="related_user_id" class="form-select"><option value="">اختر العميل</option>@foreach ($relatedClients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach</select></div>
            <div data-relation-panel="manual"><label class="form-label">المشتكى عليه</label><input name="against_name" class="form-control" maxlength="255"><label class="form-label mt-2">رقم أو وصف المرجع</label><input name="relation_reference" class="form-control" maxlength="255"></div>
            <label class="form-label mt-2">ملاحظات الربط</label><textarea name="relation_notes" class="form-control" maxlength="2000"></textarea>
            <button class="btn btn-primary mt-3">حفظ الربط</button>
        </form>
        <form method="POST" action="{{ route('complaints.schedule', $complaint) }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-5"><label class="form-label">الموعد المستهدف للمعالجة ({{ config('app.timezone') }})</label><input type="datetime-local" name="due_at" class="form-control" value="{{ old('due_at', $complaint->due_at?->format('Y-m-d\TH:i')) }}"><small class="text-muted">اترك الموعد فارغاً لإلغائه.</small></div>
                <div class="col-md-7"><label class="form-label">ملاحظات الإدارة على الموعد</label><textarea name="schedule_notes" class="form-control" rows="2" maxlength="2000">{{ old('schedule_notes', $complaint->schedule_notes) }}</textarea></div>
            </div>
            <button class="btn btn-outline-primary mt-3">حفظ الموعد</button>
        </form>
    </div>
</section>
@endif
@push('js')
<script>
$(function () {
    function updateComplaintRelation() {
        const type = $('#complaintRelationType').val();
        $('[data-relation-panel]').each(function () {
            const active = $(this).data('relation-panel') === type;
            $(this).toggle(active).find(':input').prop('disabled', !active);
        });
    }
    $('.complaint-record-search').each(function () {
        $(this).select2({width: '100%', dir: 'rtl', placeholder: 'ابحث ثم اختر السجل', ajax: {
            url: $(this).data('url'), dataType: 'json', delay: 300,
            data: function (params) { return {q: params.term || '', page: params.page || 1}; },
            processResults: function (data) { return data; }
        }});
    });
    $('#complaintRelationType').on('change', updateComplaintRelation);
    updateComplaintRelation();
});
</script>
@endpush
