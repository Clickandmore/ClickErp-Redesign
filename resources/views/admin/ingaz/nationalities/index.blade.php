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
    .nationalities-toolbar {
        display: grid;
        grid-template-columns: minmax(240px, 1fr) repeat(2, minmax(170px, .45fr));
        gap: 12px;
        padding: 16px;
        margin-bottom: 18px;
        border: 1px solid #e8edf5;
        border-radius: 16px;
        background: #f8fafc;
    }
    .nationalities-toolbar .input-group-text,
    .nationalities-toolbar .form-control,
    .nationalities-toolbar .form-select { background-color: #fff; }
    .offer-summary { display: flex; flex-direction: column; gap: 5px; min-width: 190px; }
    .offer-summary strong { color: #9a6700; }
    .offer-summary small { color: #64748b; }
    .offer-summary--empty { color: #94a3b8; }
    .actions-dropdown .dropdown-toggle { min-width: 105px; }
    .actions-dropdown .dropdown-menu { min-width: 235px; }
    #createModal .modal-dialog { max-width: min(1180px, 96vw); height: calc(100vh - 2rem); margin: 1rem auto; }
    #createModal .modal-content { max-height: 100%; }
    #createModal .modal-body { overflow-y: auto; padding: 1.25rem; }
    #createModal .nationality-edit-form .dropify-wrapper { height: 132px; }
    #createModal .nationality-edit-form .form-label { margin-bottom: .35rem; font-weight: 600; color: #475569; }
    #createModal .nationality-form-section { grid-column: 1 / -1; display: flex; align-items: center; gap: 8px; margin-top: 4px; color: #1e3a8a; font-weight: 700; }
    #createModal .nationality-form-section::after { content: ''; height: 1px; background: #e5eaf2; flex: 1; }
    #createModal .nationality-edit-form .modal-footer { position: sticky; bottom: -1.25rem; z-index: 5; background: #fff; border-top: 1px solid #e9ecef; margin: 1rem -1.25rem -1.25rem; padding: 1rem 1.25rem; }
    @media (max-width: 575.98px) {
        .nationality-avatar {
            width: 52px;
            height: 52px;
        }
        .financial-chip {
            width: 100%;
            justify-content: space-between;
        }
        .nationalities-toolbar { grid-template-columns: 1fr; }
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
                        <span class="stats-pill"><i class="fas fa-tags text-warning"></i>{{ number_format($stats['offers']) }} عروض</span>
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
                    <div class="nationalities-toolbar">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="search" id="nationalitySearch" class="form-control" placeholder="ابحث باسم الجنسية">
                        </div>
                        <select id="visibilityFilter" class="form-select" aria-label="فلترة الظهور">
                            <option value="">كل حالات الظهور</option>
                            <option value="visible">الظاهرة فقط</option>
                            <option value="hidden">المخفية فقط</option>
                        </select>
                        <select id="offerFilter" class="form-select" aria-label="فلترة العروض">
                            <option value="">كل حالات العروض</option>
                            <option value="active">العروض النشطة</option>
                            <option value="scheduled">العروض المجدولة</option>
                            <option value="expired">العروض المنتهية</option>
                            <option value="none">بدون عرض</option>
                        </select>
                    </div>
                    <div class="table-responsive">
                        <table id="dataTableObject" class="table table-hover align-middle" style="width:100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('countries.sort_order') }}</th>
                                    <th>{{ helperTrans("ingaz.nationality_name") }}</th>
                                    <th>{{ helperTrans("ingaz.financial_details") }}</th>
                                    <th>عرض الجنسية</th>
                                    <th>{{ helperTrans("ingaz.visibility") }}</th>
                                    <th>{{ helperTrans("ingaz.actions") }}</th>
                                    <th class="d-none"></th>
                                    <th class="d-none"></th>
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

    <div class="modal fade" id="nationalityOfferModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">إدارة عرض الجنسية</h5>
                        <p class="text-muted small mb-0" id="nationalityOfferSubtitle"></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="nationalityOfferForm">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info py-2"><i class="fas fa-circle-info me-1"></i>يظهر العرض تلقائياً لكل عاملة متاحة من هذه الجنسية، مع أولوية العرض الفردي إن وُجد.</div>
                        <div id="nationalityOfferErrors" class="alert alert-danger d-none"></div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="nationalityOfferPrice">سعر العرض</label>
                                <input type="number" min="0" step="0.01" required class="form-control" name="offer_price" id="nationalityOfferPrice">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">مدة العرض</label>
                                <div class="form-control bg-light" id="nationalityOfferDuration">غير محدد</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="nationalityOfferStart">بداية العرض</label>
                                <input type="date" class="form-control" name="offer_starts_at" id="nationalityOfferStart">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="nationalityOfferExpire">نهاية العرض</label>
                                <input type="date" class="form-control" name="offer_expires_at" id="nationalityOfferExpire">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="nationalityOfferDescription">وصف العرض</label>
                                <textarea class="form-control" rows="3" maxlength="500" name="offer_description" id="nationalityOfferDescription"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-danger me-auto d-none" id="clearNationalityOffer"><i class="fas fa-trash-alt me-1"></i>حذف العرض</button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>حفظ وتطبيق العرض</button>
                    </div>
                </form>
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
            {"data": "sort_order", name: "sort_order", orderable: true, searchable: false},
            {"data": "meta", name: "meta", orderable: false, searchable: false},
            {"data": "financials", name: "financials", orderable: false, searchable: false},
            {"data": "offer", name: "offer", orderable: false, searchable: false},
            {"data": "visibility_toggle", name: "visibility_toggle", orderable: false, searchable: false},
            {"data": "actions", name: "actions", orderable: false, searchable: false},
            {"data": "sub_title_plain", name: "sub_title_plain", orderable: false, searchable: true, visible: false},
            {"data": "visibility_key", name: "visibility_key", orderable: false, searchable: true, visible: false},
            {"data": "offer_state_key", name: "offer_state_key", orderable: false, searchable: true, visible: false}
        ];
        showDataTable("{{ $dataTableRoute }}", columns);

        const table = $('#dataTableObject').DataTable();
        table.order([[1, 'asc']]).draw();
        $('#dataTableObject_filter').hide();

        $('#nationalitySearch').on('input', function () { table.search(this.value).draw(); });
        $('#visibilityFilter').on('change', function () { table.column(8).search(this.value ? '^' + this.value + '$' : '', true, false).draw(); });
        $('#offerFilter').on('change', function () { table.column(9).search(this.value ? '^' + this.value + '$' : '', true, false).draw(); });
    });

    let nationalityOfferRoute = null;
    const nationalityOfferModal = new bootstrap.Modal(document.getElementById('nationalityOfferModal'));

    function updateNationalityOfferDuration() {
        const start = $('#nationalityOfferStart').val();
        const end = $('#nationalityOfferExpire').val();
        let label = 'غير محدد';
        if (start && end) {
            const days = Math.floor((new Date(end + 'T00:00:00') - new Date(start + 'T00:00:00')) / 86400000) + 1;
            label = days > 0 ? days + ' يوم' : 'تحقق من التواريخ';
        }
        $('#nationalityOfferDuration').text(label);
    }

    $(document).on('change', '#nationalityOfferStart, #nationalityOfferExpire', updateNationalityOfferDuration);

    $(document).on('click', '.manage-nationality-offer', function () {
        const button = $(this);
        nationalityOfferRoute = button.data('route');
        $('#nationalityOfferSubtitle').text('العرض لجنسية ' + button.data('name'));
        $('#nationalityOfferPrice').val(button.attr('data-offer-price'));
        $('#nationalityOfferStart').val(button.attr('data-offer-start'));
        $('#nationalityOfferExpire').val(button.attr('data-offer-expire'));
        $('#nationalityOfferDescription').val(button.attr('data-offer-description'));
        $('#nationalityOfferErrors').addClass('d-none').empty();
        $('#clearNationalityOffer').toggleClass('d-none', button.attr('data-has-offer') !== '1');
        updateNationalityOfferDuration();
        nationalityOfferModal.show();
    });

    $('#nationalityOfferForm').on('submit', function (event) {
        event.preventDefault();
        saveNationalityOffer($(this).serialize());
    });

    $('#clearNationalityOffer').on('click', function () {
        swal.fire({
            title: 'حذف عرض الجنسية؟',
            text: 'سيتوقف العرض عن العاملات اللاتي يرثن عرض هذه الجنسية.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'حذف العرض',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) saveNationalityOffer({_token: '{{ csrf_token() }}', clear_offer: 1});
        });
    });

    function saveNationalityOffer(payload) {
        const submitButton = $('#nationalityOfferForm button[type="submit"]');
        submitButton.prop('disabled', true);
        $.ajax({
            url: nationalityOfferRoute,
            type: 'POST',
            data: payload,
            success: function (response) {
                nationalityOfferModal.hide();
                successToster('{{ helperTrans('ingaz.done successfully') }}', response.message);
                reloadDataTable();
            },
            error: function (errorObj) {
                const errors = errorObj.responseJSON?.errors;
                if (errors) {
                    $('#nationalityOfferErrors').removeClass('d-none').html(Object.values(errors).flat().join('<br>'));
                } else {
                    handleErrorResponce(errorObj);
                }
            },
            complete: function () { submitButton.prop('disabled', false); }
        });
    }

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
