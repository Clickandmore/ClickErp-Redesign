@extends('admin.layout.indexs.index')

@section('page-title')
    {{ helperTrans('ingaz.edit_client') }}
@endsection

@section('style')
    @include('admin.ingaz.clients.partials.form-styles')
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_EDIT))
        <div class="client-form-page">
            <div class="client-form-header">
                <div>
                    <h4>{{ helperTrans('ingaz.edit_client') }}</h4>
                    <p>{{ helperTrans('ingaz.client_edit_required_fields_hint') }}</p>
                </div>

                <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-right me-1"></i>
                    {{ helperTrans('ingaz.back') }}
                </a>
            </div>

            {{-- بيانات قديمة لا تطابق قواعد الإدخال الحالية: تُملأ من الـ JS عند فتح الصفحة --}}
            <div id="legacyDataAlert" class="alert alert-warning d-none" role="alert">
                <div class="fw-bold mb-1">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    بعض بيانات هذا العميل مسجلة بصيغة قديمة ويجب تصحيحها قبل الحفظ:
                </div>
                <ul class="mb-0 ps-3" id="legacyDataList"></ul>
            </div>

            <form id="editForm" action="{{ route('clients.update', $client->id) }}" method="POST"
                  class="from-submit-global" novalidate>
                @csrf
                @method('PUT')
                <input type="hidden" name="phone_code" value="+966">

                @include('admin.ingaz.clients.partials.form-fields', ['client' => $client])

                <div class="client-form-actions">
                    <a href="{{ route('clients.index') }}" class="btn btn-light">
                        {{ helperTrans('ingaz.cancel') }}
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>
                        {{ helperTrans('ingaz.save') }}
                    </button>
                </div>
            </form>
        </div>
    @else
        @include('components.no_permissions')
    @endif
@endsection

@section('js')
    @if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_EDIT))
        @include('admin.ingaz.clients.partials.form-scripts', ['client' => $client])

        <script>
            $(document).ready(function () {
                const $form = $('#editForm');

                if ($('#branch_id').length && !$('#branch_id').prop('disabled')) {
                    $('#branch_id').select2({
                        placeholder: "{{ helperTrans('ingaz.choose') }}",
                        allowClear: true,
                        width: '100%'
                    });
                }

                // عند فتح الصفحة: إظهار البيانات القديمة التي لا تطابق القواعد الحالية
                const legacyErrors = window.clientForm.validate();
                const legacyFields = Object.keys(legacyErrors);

                if (legacyFields.length) {
                    const $list = $('#legacyDataList').empty();

                    legacyFields.forEach(function (field) {
                        $('[name="' + field + '"]').addClass('is-invalid');
                        legacyErrors[field].forEach(function (message) {
                            $list.append($('<li>').text(message));
                        });
                    });

                    $('#legacyDataAlert').removeClass('d-none');
                }

                // التحقق قبل الإرسال؛ يوقف معالج الإرسال العام (from-submit-global) عند وجود أخطاء
                $form.on('submit', function (e) {
                    $form.find('.is-invalid').removeClass('is-invalid');

                    const clientFieldErrors = window.clientForm.preSubmitErrors();

                    if (Object.keys(clientFieldErrors).length) {
                        e.preventDefault();
                        e.stopPropagation();
                        showClientFormErrors({ errors: clientFieldErrors });
                    }
                });
            });
        </script>
    @endif
@endsection
