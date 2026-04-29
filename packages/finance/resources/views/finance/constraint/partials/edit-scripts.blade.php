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

    initConstraintSelect2(document);
    $(document).on('click', '.add_debtor', function(e) {
        e.preventDefault();
        var HtmlDebtorAppend = $("#debtor_append").clone();
        HtmlDebtorAppend.find('.select2-container').remove();
        HtmlDebtorAppend.removeAttr('id');
        HtmlDebtorAppend.find('input[type="number"], textarea').val('');
        HtmlDebtorAppend.find('select').val('');
        HtmlDebtorAppend.find('i').addClass('delete-row');
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
        HtmlCreditorAppend.find('input[type="number"], textarea').val('');
        HtmlCreditorAppend.find('select').val('');
        HtmlCreditorAppend.find('i').addClass('delete-row');
        HtmlCreditorAppend.find('.constraint-old-id').remove();
        HtmlCreditorAppend.appendTo('.table-body');
        initConstraintSelect2(HtmlCreditorAppend);
        updateConstraintTotals();
    });

    function updateConstraintTotals() {
        var debtor = $('.debtor');
        var creditor = $('.creditor');
        var creditor_total_value = 0;
        var debtor_total_value = 0;
        debtor.each(function(e) {
            debtor_total_value += parseFloat($(this).val()) || 0;
        })
        $('#debtor_footer_value').val(debtor_total_value.toFixed(2));
        creditor.each(function(e) {
            creditor_total_value += parseFloat($(this).val()) || 0;
        })
        $('#creditor_footer_value').val(creditor_total_value.toFixed(2));
        if (debtor_total_value > 0 && debtor_total_value.toFixed(2) == creditor_total_value.toFixed(2)) {
            $('.debtor').removeClass('input-error');
            $('.creditor').removeClass('input-error');
            $('.error-span').attr('hidden', true);
            $('.constraint-total-value').val(debtor_total_value.toFixed(2));
        } else {
            $('.debtor').addClass('input-error');
            $('.creditor').addClass('input-error');
            $('.error-span').removeAttr('hidden');
            $('.constraint-total-value').val('');
        }
    }

    $(document).on('input keyup change', '.debtor, .creditor', function(e) {
        updateConstraintTotals();
    });

    $(document).on('click', '.delete-row', function(e) {
        e.preventDefault();
        let obj = $(this)
        let parentTr = obj.closest("tr");
        parentTr.remove();
        updateConstraintTotals();
    })

    updateConstraintTotals();
</script>
