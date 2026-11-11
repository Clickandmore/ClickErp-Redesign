<script>
    $.validate({
        ignore: 'input[type=hidden]',
        modules: 'date, security',
        lang: '{{ \App::currentLocale() }}',
        validateOnEvent: true
    });
    function initConstraintSelect2(scope) {
        $(scope || document).find('.select2').each(function() {
            let select = $(this);
            if (select.data('select2')) {
                select.select2('destroy');
            }

            select
                .removeClass('select2-hidden-accessible')
                .removeAttr('data-select2-id aria-hidden tabindex');
            select.find('option').removeAttr('data-select2-id');

            select.select2({
                width: '100%',
                dir: '{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}',
                dropdownAutoWidth: true,
                placeholder: select.find('option:first').text() || '',
                allowClear: !select.prop('required')
            });
        });
    }

    function setNewConstraintLineBranch(row) {
        var select = row.find('.js-line-branch');
        var headerBranch = String($('.constraint-edit-table').data('header-branch') || '');
        var headerOption = select.find('option[value="' + headerBranch + '"]');
        var branchId = headerOption.data('branch-allowed') == 1
            ? headerBranch
            : String(select.find('option[data-branch-allowed="1"]').first().val() || '');
        select.val(branchId);
    }

    function constraintLineOptions(items, labelKey, emptyText) {
        var options = $('<select>').append($('<option>', { value: '', text: emptyText }));
        (items || []).forEach(function(item) {
            options.append($('<option>', { value: item.id, text: item[labelKey] || item.text || item.name }));
        });
        return options.html();
    }

    initConstraintSelect2(document);
    $(document).on('click', '.add_debtor', function(e) {
        e.preventDefault();
        var HtmlDebtorAppend = $("#debtor_append").clone();
        HtmlDebtorAppend.find('.select2-container').remove();
        HtmlDebtorAppend.removeAttr('id');
        HtmlDebtorAppend.find('.js-constraint-amount, textarea').val('');
        HtmlDebtorAppend.find('select').val('');
        // يبدأ من فرع الرأس إن كان مسموحاً، وإلا أول فرع مسموح للموظف.
        setNewConstraintLineBranch(HtmlDebtorAppend);
        HtmlDebtorAppend.find('.delete-row-btn').addClass('delete-row');
        HtmlDebtorAppend.find('.constraint-old-id').remove();
        HtmlDebtorAppend.appendTo('.table-body');
        initConstraintSelect2(HtmlDebtorAppend);
        updateConstraintTotals();
    });
    $(document).on('click', '.add_creditor', function(e) {
        e.preventDefault();
        var HtmlCreditorAppend = $("#creditor_append").clone();
        HtmlCreditorAppend.find('.select2-container').remove();
        HtmlCreditorAppend.removeAttr('id');
        HtmlCreditorAppend.find('.js-constraint-amount, textarea').val('');
        HtmlCreditorAppend.find('select').val('');
        // يبدأ من فرع الرأس إن كان مسموحاً، وإلا أول فرع مسموح للموظف.
        setNewConstraintLineBranch(HtmlCreditorAppend);
        HtmlCreditorAppend.find('.delete-row-btn').addClass('delete-row');
        HtmlCreditorAppend.find('.constraint-old-id').remove();
        HtmlCreditorAppend.appendTo('.table-body');
        initConstraintSelect2(HtmlCreditorAppend);
        updateConstraintTotals();
    });

    function formatConstraintAmount(value) {
        return Number(value || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function updateConstraintTotals() {
        var debtor = $('.debtor');
        var creditor = $('.creditor');
        var creditor_total_value = 0;
        var debtor_total_value = 0;
        debtor.each(function(e) {
            debtor_total_value += parseConstraintAmount($(this).val());
        })
        $('#debtor_footer_value').val(formatConstraintAmount(debtor_total_value));
        $('#debtor_total_badge').text(formatConstraintAmount(debtor_total_value));
        creditor.each(function(e) {
            creditor_total_value += parseConstraintAmount($(this).val());
        })
        $('#creditor_footer_value').val(formatConstraintAmount(creditor_total_value));
        $('#creditor_total_badge').text(formatConstraintAmount(creditor_total_value));
        if (debtor_total_value > 0 && debtor_total_value.toFixed(2) == creditor_total_value.toFixed(2)) {
            $('.debtor').removeClass('input-error');
            $('.creditor').removeClass('input-error');
            $('.error-span').attr('hidden', true);
            $('.constraint-total-value').val(debtor_total_value.toFixed(2));
            $('#balance_alert').hide();
        } else {
            $('.debtor').addClass('input-error');
            $('.creditor').addClass('input-error');
            $('.error-span').removeAttr('hidden');
            $('.constraint-total-value').val('');
            $('#balance_alert').toggle(debtor_total_value > 0 || creditor_total_value > 0);
        }
    }

    function normalizeConstraintAmount(value) {
        if (value === null || value === undefined) {
            return '';
        }

        var normalized = String(value)
            .replace(/[٠-٩]/g, function(digit) {
                return '٠١٢٣٤٥٦٧٨٩'.indexOf(digit);
            })
            .replace(/٫|،/g, '.')
            .replace(/٬/g, '')
            .replace(/\s+/g, '')
            .replace(/[^0-9.]/g, '');

        var parts = normalized.split('.');
        if (parts.length > 2) {
            normalized = parts.shift() + '.' + parts.join('');
        }

        return normalized;
    }

    function parseConstraintAmount(value) {
        var normalized = normalizeConstraintAmount(value);
        return normalized === '' ? 0 : parseFloat(normalized) || 0;
    }

    $(document).on('input keyup change', '.debtor, .creditor', function(e) {
        $(this).val(normalizeConstraintAmount($(this).val()));
        updateConstraintTotals();
    });

    $(document).on('blur', '.debtor, .creditor', function(e) {
        var parsedValue = parseConstraintAmount($(this).val());
        $(this).val(parsedValue > 0 ? parsedValue.toFixed(2) : '');
        updateConstraintTotals();
    });

    $(document).on('click', '.delete-row', function(e) {
        e.preventDefault();
        let obj = $(this)
        let parentTr = obj.closest("tr");
        parentTr.remove();
        updateConstraintTotals();
    })

    $(document).on('change', '.constraint-edit-table .js-line-branch', function() {
        var branchSelect = $(this);
        var selectedOption = branchSelect.find('option:selected');
        if (selectedOption.data('branch-allowed') != 1) {
            return;
        }

        var row = branchSelect.closest('tr');
        var url = new URL(@json(route('finance.add-constraint.branch-options')), window.location.origin);
        url.searchParams.set('branch_id', branchSelect.val());
        branchSelect.prop('disabled', true);

        $.get(url.toString())
            .done(function(result) {
                row.find('.select-account')
                    .html(constraintLineOptions(result.accounts, 'text', 'اختر الحساب'))
                    .val('').trigger('change.select2');
                row.find('select[name="cost_center_id[]"]')
                    .html(constraintLineOptions(result.cost_centers, 'title', 'بدون مركز تكلفة'))
                    .val('').trigger('change.select2');
            })
            .fail(function(errorObj) {
                handleErrorResponce(errorObj);
            })
            .always(function() {
                branchSelect.prop('disabled', false);
            });
    });

    updateConstraintTotals();
</script>
