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
        let newSelect = HtmlDebtorAppend.find(".select-unique-option");
        HtmlDebtorAppend.appendTo('.table-body');
        disableNewOptions(newSelect);
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
        let newSelect = HtmlCreditorAppend.find(".select-unique-option");
        HtmlCreditorAppend.appendTo('.table-body');
        disableNewOptions(newSelect);
    });

    $(document).on('change', '.select-unique-option', function() {
        $('option', '.select-unique-option').prop('disabled', false);
        $('.select-unique-option').each(function() {
            var val = this.value;
            $('.select-unique-option').not(this).find('option').filter(function() {
                return this.value === val;
            }).prop('disabled', true);
        });
    }).change();

    function disableNewOptions(newSelect) {
        initConstraintSelect2(newSelect.closest('tr'));
        $('select.select-unique-option').not(newSelect).each(function() {
            if ($("option:selected", this).val() != "") {
                newSelect.find('option[value="' + $("option:selected", this).val() + '"]').attr("disabled", true);
            }
        });
    }

    $(document).on('keyup', ['.debtor', '.creditor'], function(e) {
        e.preventDefault();
        var debtor = $('.debtor');
        var creditor = $('.creditor');
        var creditor_total_value = 0;
        var debtor_total_value = 0;
        debtor.each(function(e) {
            debtor_total_value += parseFloat($(this).val()) || 0;
        })
        $('#debtor_footer_value').val(debtor_total_value);
        creditor.each(function(e) {
            creditor_total_value += parseFloat($(this).val());
        })
        $('#creditor_footer_value').val(creditor_total_value);
        if (debtor_total_value.toFixed(2) == creditor_total_value) {
            $('.debtor').removeClass('input-error');
            $('.creditor').removeClass('input-error');
            $('.error-span').attr('hidden', true);
            var value = $('#debtor_footer_value').val();
            $('.debtor__value').append(
                `<input type="hidden" name="value" value="${value}" class="form-control form-control-lg mb-1">`
            );
        } else {
            $('.debtor').addClass('input-error');
            $('.creditor').addClass('input-error');
            $('.error-span').removeAttr('hidden');
            $('.debtor__value').children().remove();
        }
    });

    $(document).on('click', ['.add_creditor', '.remove_creditor'], function() {
        var debtor = $('.debtor');
        var creditor = $('.creditor');
        var creditor_total_value = 0;
        var debtor_total_value = 0;
        debtor.each(function(e) {
            debtor_total_value += parseFloat($(this).val()) || 0;
        })
        $('#debtor_footer_value').val(debtor_total_value);
        creditor.each(function(e) {
            creditor_total_value += parseFloat($(this).val());
        })
        $('#creditor_footer_value').val(creditor_total_value);
        if (debtor_total_value.toFixed(2) == creditor_total_value) {
            $('.debtor').removeClass('input-error');
            $('.creditor').removeClass('input-error');
            $('.error-span').attr('hidden', true);
            var value = $('#debtor_footer_value').val();
            $('.debtor__value').append(
                `<input type="hidden" name="value" value="${value}" class="form-control form-control-lg mb-1">`
            );
        } else {
            $('.debtor').addClass('input-error');
            $('.creditor').addClass('input-error');
            $('.error-span').removeAttr('hidden');
            $('.debtor__value').children().remove();
        }
    });

    $(document).on('click', '.delete-row', function(e) {
        e.preventDefault();
        let obj = $(this)
        let parentTr = obj.closest("tr");
        let deleteSelect = $('select.select-unique-option option:selected', parentTr);
        let deletedVal = parseInt(deleteSelect.val()) || 0;
        $('select.select-unique-option').each(function() {
            $(this).find('option[value="' + deletedVal + '"]').attr("disabled", false);
        });
        parentTr.remove();
    })
</script>
