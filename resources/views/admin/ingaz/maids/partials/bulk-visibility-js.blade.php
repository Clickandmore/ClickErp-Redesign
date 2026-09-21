const visibilityForm = $('#bulkVisibilityForm');
const visibilityOffices = $('#visibilityOffices');
const allVisibilityOfficeOptions = visibilityOffices.find('option').clone();
function updateVisibilityOffices() {
    const nationalityId = String($('#visibilityNationality').val() || '');
    const selectedIds = visibilityOffices.val() || [];
    const options = allVisibilityOfficeOptions.filter(function () {
        return !nationalityId || this.dataset.nationalityId === nationalityId;
    }).clone();
    const allowedIds = new Set(options.toArray().map(option => option.value));
    visibilityOffices.empty().append(options).val(selectedIds.filter(id => allowedIds.has(id)));
    // Refresh Select2 without firing a second preview request.
    visibilityOffices.trigger('change.select2');
}
let visibilityToken = null, visibilityRequest = null, visibilitySequence = 0, visibilityBusy = false;
const visibilityError = (xhr) => $('#visibilityError').text(xhr.responseJSON?.message || 'تعذر إتمام العملية. يرجى المحاولة مجددًا.');
function visibilityPreview() {
    if (visibilityBusy) return;
    const sequence = ++visibilitySequence;
    if (visibilityRequest) visibilityRequest.abort();
    visibilityToken = null;
    $('#visibilityApply').prop('disabled', true);
    $('#visibilityError').empty();
    if (!$('#visibilityOffices').val()?.length || !visibilityForm.find('[name="statuses[]"]:checked').length) {
        $('#visibilityCount').text('اختر مكتبًا واحدًا وحالة واحدة على الأقل.');
        return;
    }
    $('#visibilityCount').text('جارٍ حساب العدد…');
    visibilityRequest = $.ajax({
        url: @json(route('maids.visibility-preview')), method: 'POST',
        data: visibilityForm.serialize() + '&_token=' + encodeURIComponent(csrfToken),
        success: function (response) {
            if (sequence !== visibilitySequence) return;
            visibilityToken = response.preview_token;
            $('#visibilityCount').text('عدد السير التي ستتغير فعليًا: ' + response.count);
            $('#visibilityApply').prop('disabled', response.count === 0);
        },
        error: function (xhr, status) {
            if (status !== 'abort' && sequence === visibilitySequence) {
                $('#visibilityCount').text('تعذر حساب العدد.');
                visibilityError(xhr);
            }
        }
    });
}
function visibilityHistory(page = 1) {
    $.get(@json(route('maids.visibility-batches')), {page}).done(function (response) {
        const history = $('#visibilityHistory').empty();
        if (!response.batches.length) history.text('لا توجد عمليات سابقة.');
        response.batches.forEach(function (batch) {
            const row = $('<div class="border rounded p-2 mb-2"></div>').appendTo(history);
            const filters = typeof batch.filters === 'string' ? JSON.parse(batch.filters) : batch.filters;
            const officeNames = (filters.office_ids || []).map(id => allVisibilityOfficeOptions.filter(function () { return this.value === String(id); }).text() || '#' + id);
            const nationality = filters.nationality_id ? $('#visibilityNationality option').filter(function () { return this.value === String(filters.nationality_id); }).text() : 'جميع الجنسيات';
            $('<div></div>').text(batch.created_at + ' — ' + (Number(batch.is_show) ? 'إظهار' : 'إخفاء') + ' — ' + batch.affected_count + ' سيرة').appendTo(row);
            $('<small class="text-muted d-block"></small>').text(nationality + ' | ' + officeNames.join('، ') + ' | ' + filters.statuses.map(s => s === 'available' ? 'متاحة' : 'محجوزة').join('، ')).appendTo(row);
            if (batch.undone_at) {
                $('<small></small>').text('تم التراجع: ' + batch.restored_count + ' سيرة — ' + batch.undone_at).appendTo(row);
            } else if (!Number(batch.is_show) && batch.affected_count > 0) {
                $('<button type="button" class="btn btn-outline-warning btn-sm mt-1 visibility-undo">التراجع عن الإخفاء</button>').data('batch', batch.id).appendTo(row);
            }
        });
        if (response.last_page > 1) {
            const navigation = $('<div class="d-flex gap-2 align-items-center"></div>').appendTo(history);
            $('<button type="button" class="btn btn-sm btn-outline-secondary">السابق</button>').prop('disabled', response.page <= 1).on('click', () => visibilityHistory(response.page - 1)).appendTo(navigation);
            $('<span></span>').text(response.page + ' / ' + response.last_page).appendTo(navigation);
            $('<button type="button" class="btn btn-sm btn-outline-secondary">التالي</button>').prop('disabled', response.page >= response.last_page).on('click', () => visibilityHistory(response.page + 1)).appendTo(navigation);
        }
    }).fail(visibilityError);
}
$('#bulkMaidVisibilityButton').on('click', function () {
    updateVisibilityOffices();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('bulkVisibilityModal')).show();
    if ($.fn.select2 && !$('#visibilityOffices').hasClass('select2-hidden-accessible')) {
        $('#visibilityOffices').select2({width: '100%', dropdownParent: $('#bulkVisibilityModal'), placeholder: 'اختر المكاتب'});
    }
    visibilityPreview();
    visibilityHistory();
});
visibilityForm.on('change', 'input, select', function () {
    if (this.id === 'visibilityNationality') {
        updateVisibilityOffices();
    }
    visibilityPreview();
});
$('#visibilityRefresh').on('click', visibilityPreview);
$('#visibilitySelectAll').on('click', () => $('#visibilityOffices').val($('#visibilityOffices option').map(function () { return this.value; }).get()).trigger('change'));
$('#visibilityClearOffices').on('click', () => $('#visibilityOffices').val([]).trigger('change'));
visibilityForm.on('submit', function (event) {
    event.preventDefault();
    if (!visibilityToken || visibilityBusy) return;
    const data = visibilityForm.serialize() + '&_token=' + encodeURIComponent(csrfToken) + '&preview_token=' + visibilityToken;
    visibilityBusy = true;
    $('#visibilityApply, #bulkVisibilityFields, #visibilityRefresh').prop('disabled', true);
    $.ajax({url: @json(route('maids.bulk-visibility')), method: 'POST', data})
        .done(function (response) {
            table.ajax.reload(null, false);
            Swal.fire({icon: 'success', text: response.message});
            visibilityHistory();
        }).fail(visibilityError).always(function () {
            visibilityBusy = false;
            visibilityToken = null;
            $('#bulkVisibilityFields, #visibilityRefresh').prop('disabled', false);
            $('#visibilityCount').text('اضغط تحديث العدد للحصول على معاينة جديدة.');
        });
});
$('#visibilityHistory').on('click', '.visibility-undo', function () {
    if (visibilityBusy) return;
    const button = $(this), batch = button.data('batch');
    Swal.fire({icon: 'question', text: 'التراجع عن إخفاء هذه الدفعة؟ ستُتخطى السير التي تغيرت أو لم تعد مؤهلة.', showCancelButton: true, confirmButtonText: 'تراجع عن الإخفاء', cancelButtonText: 'إلغاء'}).then(result => {
        if (!result.isConfirmed || visibilityBusy) return;
        visibilityBusy = true;
        $('#visibilityApply, #bulkVisibilityFields, #visibilityRefresh').prop('disabled', true);
        button.prop('disabled', true);
        $.post(@json(route('maids.visibility-batches.undo', ['batch' => '__batch__'])).replace('__batch__', batch), {_token: csrfToken})
            .done(function (response) { table.ajax.reload(null, false); Swal.fire({icon: 'success', text: response.message}); })
            .fail(visibilityError).always(function () {
                visibilityBusy = false;
                $('#bulkVisibilityFields, #visibilityRefresh').prop('disabled', false);
                visibilityHistory();
                visibilityPreview();
            });
    });
});
