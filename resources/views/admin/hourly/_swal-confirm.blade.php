{{-- تأكيد SweetAlert2 لأي نموذج يحمل data-swal-confirm (العنوان)، مع data-swal-text وdata-swal-icon وdata-swal-ok اختياريًا. --}}
@once
<script>
document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form.matches || !form.matches('form[data-swal-confirm]') || form.dataset.swalConfirmed === '1') return;
    event.preventDefault();
    var submitter = event.submitter, danger = form.dataset.swalIcon === 'warning';
    var proceed = function () { form.dataset.swalConfirmed = '1'; if (form.requestSubmit) form.requestSubmit(submitter); else form.submit(); };
    var fire = window.Swal && Swal.fire ? Swal.fire.bind(Swal) : window.swal;
    if (!fire) { if (window.confirm(form.dataset.swalConfirm)) proceed(); return; }
    fire({icon: form.dataset.swalIcon || 'question', title: form.dataset.swalConfirm, text: form.dataset.swalText || '',
        showCancelButton: true, reverseButtons: true, focusCancel: danger,
        confirmButtonText: form.dataset.swalOk || 'نعم، تأكيد', cancelButtonText: 'تراجع'})
        .then(function (result) { if (result && (result.isConfirmed || result.value)) proceed(); });
}, true);
</script>
@endonce
