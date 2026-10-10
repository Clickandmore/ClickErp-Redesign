@extends('admin.layout.indexs.index')

@section('title')
    {{ helperTrans('ingaz.add_client') }}
@endsection

@section('links')
    <li class="breadcrumb-item active"><a href="javascript:void(0)">{{ helperTrans('ingaz.add_client') }}</a></li>
@endsection

@section('page-title')
    {{ helperTrans('ingaz.add_client') }}
@endsection

@section('style')
    @include('admin.ingaz.clients.partials.form-styles')
@endsection

@section('content')
    <div class="client-form-page">
        <div class="client-form-header">
            <div>
                <h4>{{ helperTrans('ingaz.add_client') }}</h4>
                <p>{{ helperTrans('ingaz.client_required_fields_hint') }}</p>
            </div>

            <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right me-1"></i>
                {{ helperTrans('ingaz.back') }}
            </a>
        </div>

        <form id="addForm" method="POST" action="{{ route('clients.store') }}" novalidate>
            @csrf
            <input type="hidden" name="phone_code" value="+966">

            @include('admin.ingaz.clients.partials.form-fields', ['client' => null])

            <div class="client-form-actions">
                <a href="{{ route('clients.index') }}" class="btn btn-light">
                    {{ helperTrans('ingaz.cancel') }}
                </a>
                <button type="submit" id="submitBtn" class="btn btn-success">
                    <i class="fas fa-save me-1"></i>
                    {{ __('admin.confirm') }}
                </button>
            </div>
        </form>
    </div>
@endsection

@section('js')
    @include('admin.ingaz.clients.partials.form-scripts', ['client' => null])

    <script>
        $(document).ready(function () {
            $('#addForm').on('submit', function (e) {
                e.preventDefault();

                const $form = $(this);
                const $btn = $('#submitBtn');
                const originalHtml = $btn.html();

                $form.find('.is-invalid').removeClass('is-invalid');

                const clientFieldErrors = window.clientForm.preSubmitErrors();
                if (Object.keys(clientFieldErrors).length) {
                    showClientFormErrors({ errors: clientFieldErrors });
                    return;
                }

                $.ajax({
                    type: 'POST',
                    url: $form.attr('action'),
                    data: $form.serialize(),
                    beforeSend: function () {
                        $btn.prop('disabled', true).html(
                            '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> {{ __("ingaz.sending") }}'
                        );
                    },
                    success: function (response) {
                        if (response.code === 200) {
                            successToster('{{ helperTrans("ingaz.done successfully") }}', response.message);
                            setTimeout(function () {
                                window.location.href = response.url;
                            }, 1000);
                            return;
                        }

                        if (response.code === 422) {
                            showClientFormErrors(response);
                        }
                    },
                    error: function (response) {
                        showClientFormErrors(response);
                    },
                    complete: function () {
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                });
            });
        });
    </script>
@endsection
