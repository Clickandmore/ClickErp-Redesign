function clearMaidFormValidationErrors() {
    var form = $('#maidForm');
    form.find('.is-invalid').removeClass('is-invalid');
    form.find('.select2-selection.border-danger').removeClass('border-danger');
    form.find('.maid-server-error').remove();
    $('#maidFormErrors').addClass('d-none');
    $('#maidFormErrorsList').empty();
}

function maidValidationInputNames(errorKey) {
    var names = [errorKey];
    var parts = String(errorKey).split('.');

    if (parts.length > 1) {
        var bracketName = parts.shift();
        var arrayName = bracketName;
        parts.forEach(function(part) {
            bracketName += part === '*' ? '[]' : '[' + part + ']';
            arrayName += part === '*' || /^\d+$/.test(part) ? '[]' : '[' + part + ']';
        });
        names.push(bracketName);
        names.push(arrayName);
    }

    names.push(String(errorKey).replace(/\.\*$/, '[]'));
    return names;
}

function showMaidFormValidationErrors(errors, fallbackMessage) {
    clearMaidFormValidationErrors();

    var form = $('#maidForm');
    var list = $('#maidFormErrorsList');
    var messages = [];
    errors = errors && Object.keys(errors).length ? errors : {
        form: [fallbackMessage || 'تعذر حفظ السيرة بسبب خطأ غير متوقع. يرجى المحاولة مرة أخرى.']
    };

    Object.keys(errors).forEach(function(errorKey) {
        var fieldMessages = Array.isArray(errors[errorKey]) ? errors[errorKey] : [errors[errorKey]];
        var cleanMessages = fieldMessages.filter(Boolean);

        cleanMessages.forEach(function(message) {
            messages.push(String(message));
            $('<li>').text(message).appendTo(list);
        });

        if (errorKey === 'form') {
            return;
        }

        var acceptedNames = maidValidationInputNames(errorKey);
        var field = form.find('[name]').filter(function() {
            return acceptedNames.indexOf(this.name) !== -1;
        }).first();

        if (!field.length) {
            return;
        }

        field.addClass('is-invalid');
        var feedback = $('<div class="invalid-feedback d-block maid-server-error"></div>')
            .text(cleanMessages.join(' '));
        var select2 = field.next('.select2');

        if (select2.length) {
            select2.after(feedback);
            select2.find('.select2-selection').addClass('border-danger');
        } else {
            field.after(feedback);
        }
    });

    $('#maidFormErrors').removeClass('d-none');
    var firstInvalid = form.find('.is-invalid').first();
    var scrollTarget = firstInvalid.length ? firstInvalid : $('#maidFormErrors');

    if (scrollTarget.length) {
        $('html, body').animate({ scrollTop: Math.max(0, scrollTarget.offset().top - 140) }, 300);
    }

    return messages;
}

function validateMaidVideoFile() {
    var input = document.getElementById('maid_video');
    var file = input && input.files ? input.files[0] : null;
    var maxBytes = {{ \App\Support\MaidFieldRules::VIDEO_MAX_KB }} * 1024;

    if (!file || file.size <= maxBytes) {
        return [];
    }

    return [@json(__('ingaz.maid_video_too_large'))];
}

function maidAjaxErrorMessage(xhr, fallbackMessage) {
    var response = xhr.responseJSON || {};

    // رسائل الرفع تذكر الفيديو فقط إذا كان مرفقًا فعلًا، حتى لا يُطلب حذف فيديو غير موجود
    var videoInput = document.getElementById('maid_video');
    var hasVideo = !!(videoInput && videoInput.files && videoInput.files.length);

    if (xhr.status === 413) {
        return hasVideo ? @json(__('ingaz.maid_upload_request_too_large')) : @json(__('ingaz.maid_upload_files_too_large'));
    }

    if (xhr.status === 419) {
        return @json(__('ingaz.maid_session_expired'));
    }

    if (xhr.status === 0) {
        return @json(__('ingaz.maid_upload_connection_failed'));
    }

    if (xhr.status >= 500) {
        return hasVideo ? @json(__('ingaz.maid_server_save_failed')) : @json(__('ingaz.maid_server_save_failed_generic'));
    }

    if (response.message) {
        return response.message;
    }

    return fallbackMessage || @json(__('ingaz.maid_save_failed'));
}

$('#maidForm').on('input change', '.is-invalid', function() {
    $(this).removeClass('is-invalid').next('.maid-server-error').remove();
    $(this).next('.select2').find('.select2-selection').removeClass('border-danger');
});
