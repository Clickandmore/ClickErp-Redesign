@extends('admin.layout.indexs.index')

@section('style')
<style>
    .nationality-avatar {
        width: 64px;
        height: 64px;
        border-radius: 16px;
        object-fit: cover;
        border: 1px solid #e8ecef;
        background-color: #f8fafc;
    }
    .financial-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 12px;
        background-color: #f7f9fc;
        border: 1px solid #edf1f7;
        font-size: 0.83rem;
    }
    .financial-chip i {
        color: #2563eb;
    }
    .nationality-card .badge {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .stats-pill {
        border-radius: 999px;
        padding: 6px 14px;
        border: 1px solid #e5ebf4;
        background: #f9fbff;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .stats-pill i {
        color: #2563eb;
    }
    @media (max-width: 575.98px) {
        .nationality-avatar {
            width: 52px;
            height: 52px;
        }
        .financial-chip {
            width: 100%;
            justify-content: space-between;
        }
    }
</style>
@endsection

@section('page-title')
    {{ helperTrans("ingaz.nationalities") }}
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::NATIONALITIES_VIEW))
    <div class="page-body">
        <div class="container-fluid">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <div>
                    <h3 class="h3 mb-1 text-gray-800">
                        <i class="fas fa-flag-checkered text-primary me-2"></i>{{ helperTrans('ingaz.nationalities_overview') }}
                    </h3>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="stats-pill"><i class="fas fa-globe"></i>{{ helperTrans('ingaz.nationalities_total', ['count' => number_format($stats['total'])]) }}</span>
                        <span class="stats-pill"><i class="fas fa-eye text-success"></i>{{ helperTrans('ingaz.nationalities_visible', ['count' => number_format($stats['visible'])]) }}</span>
                        <span class="stats-pill"><i class="fas fa-eye-slash text-danger"></i>{{ helperTrans('ingaz.nationalities_hidden', ['count' => number_format($stats['hidden'])]) }}</span>
                    </div>
                </div>
                @if (@AdminGuardHasPerm(\App\PermKeys::NATIONALITIES_CONTROL))
                    <a href="{{ $createRoute }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i>{{ helperTrans("ingaz.add_nationality") }}
                    </a>
                @endif
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="dataTableObject" class="table table-hover align-middle" style="width:100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ helperTrans("ingaz.nationality_name") }}</th>
                                    <th>{{ helperTrans("ingaz.financial_details") }}</th>
                                    <th>{{ helperTrans("ingaz.visibility") }}</th>
                                    <th>{{ helperTrans("ingaz.actions") }}</th>
                                    <th class="d-none"></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    @include('components.no_permissions')
@endif
@endsection

@section('js')
<script>
    $(document).ready(function () {
        let columns = [
            {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {"data": "meta", name: "meta", orderable: false, searchable: false},
            {"data": "financials", name: "financials", orderable: false, searchable: false},
            {"data": "visibility_toggle", name: "visibility_toggle", orderable: false, searchable: false},
            {"data": "actions", name: "actions", orderable: false, searchable: false},
            {"data": "sub_title_plain", name: "sub_title_plain", orderable: true, searchable: true, visible: false}
        ];
        showDataTable("{{ $dataTableRoute }}", columns);

        $('#dataTableObject').on('init.dt', function () {
            $(this).DataTable().order([[5, 'asc']]).draw();
        });
    });

    $(document).on('click', '.update-status', function () {
        var rowId = $(this).data('id');
        var url = "{{ route('nationalities.show', ':id') }}";
        url = url.replace(':id', rowId);

        $.ajax({
            type: 'GET',
            url: url,
            success: function (data) {
                if (data.success) {
                    reloadDataTable();
                    $.toast({
                        heading: "{{ helperTrans('ingaz.done successfully') }}",
                        text: "{{ helperTrans('ingaz.status_updated_successfully') }}",
                        showHideTransition: 'slide',
                        icon: 'success',
                        hideAfter: 2000,
                        position: 'top-right',
                        stack: 1
                    });
                } else {
                    alert('فشل في تحديث الحالة.');
                }
            },
            error: function () {
                alert('فشل في تحديث الحالة.');
            }
        });
    });

    $(document).on('click', '.generate-nationality-accounts', function () {
        let button = $(this);
        let route = button.data('route');
        let nationalityName = button.data('name') || 'هذه الجنسية';
        let originalHtml = button.html();

        swal.fire({
            title: 'تأكيد إنشاء الحسابات',
            text: 'سيتم إنشاء حسابات الشجرة المالية تلقائياً للجنسية: ' + nationalityName,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'تنفيذ',
            cancelButtonText: 'إلغاء',
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

            $.ajax({
                url: route,
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function (response) {
                    successToster('{{ helperTrans('ingaz.done successfully') }}', response.message);
                    reloadDataTable();
                },
                error: function (errorObj) {
                    if (errorObj.responseJSON && errorObj.responseJSON.message) {
                        errorToster('{{ __("auth.an error occurred") }}', errorObj.responseJSON.message);
                        return;
                    }
                    handleErrorResponce(errorObj);
                },
                complete: function () {
                    button.prop('disabled', false).html(originalHtml);
                }
            });
        });
    });
</script>
@endsection
