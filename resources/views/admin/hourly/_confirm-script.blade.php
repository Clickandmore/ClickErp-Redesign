{{-- تأكيد SweetAlert لأي نموذج (أو زر إرسال) يحمل data-confirm="النص" (اختياريًا data-confirm-title و data-confirm-button و data-confirm-danger="0") --}}
@once
<script>
window.hourlyConfirm = function (options) {
    const danger = options.danger !== false;
    if (!window.Swal) return Promise.resolve(window.confirm(options.text || options.title));
    return Swal.fire({
        title: options.title || 'تأكيد الإجراء',
        text: options.text || '',
        icon: danger ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonText: options.button || 'نعم، متابعة',
        cancelButtonText: 'إلغاء',
        confirmButtonColor: danger ? '#dc2626' : '#0f5c58',
        cancelButtonColor: '#64748b',
        reverseButtons: true,
        focusCancel: danger,
    }).then(result => result.isConfirmed);
};
document.addEventListener('submit', function (event) {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.dataset.confirmed === '1') return;
    const submitter = event.submitter;
    const source = submitter && submitter.dataset.confirm ? submitter.dataset : form.dataset;
    if (!source.confirm) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    hourlyConfirm({
        title: source.confirmTitle,
        text: source.confirm,
        button: source.confirmButton,
        danger: source.confirmDanger !== '0',
    }).then(function (ok) {
        if (!ok) return;
        form.dataset.confirmed = '1';
        if (form.requestSubmit) {
            form.requestSubmit(submitter && submitter.form === form ? submitter : undefined);
            delete form.dataset.confirmed;
            return;
        }
        if (submitter && submitter.name) {
            const hidden = document.createElement('input');
            hidden.type = 'hidden'; hidden.name = submitter.name; hidden.value = submitter.value;
            form.appendChild(hidden);
        }
        form.submit();
    });
}, true);
</script>
@endonce
