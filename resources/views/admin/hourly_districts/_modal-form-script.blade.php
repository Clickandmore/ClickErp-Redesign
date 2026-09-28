<script>
/**
 * مودال واحد للإضافة والتعديل.
 * الزر الذي يفتح المودال يحمل data-record (JSON فيه url وقيم الحقول) للتعديل، أو لا يحمله للإضافة.
 * عند فتحه تلقائيًا بعد خطأ تحقق (دون زر) يبقى على الحالة التي رسمها الخادم من old().
 */
window.hourlyModalForm = function (modalId, options) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    const form = modal.querySelector('form');
    const method = form.querySelector('input[name="_method"]');

    function setValue(name, value) {
        const checkbox = form.querySelector('input[type="checkbox"][name="' + name + '"]');
        if (checkbox) { checkbox.checked = Boolean(Number(value)) || value === true; return; }
        const multiple = form.querySelector('select[multiple][name="' + name + '[]"]');
        if (multiple) {
            const selected = (value || []).map(String);
            Array.from(multiple.options).forEach(o => o.selected = selected.includes(o.value));
            multiple.dispatchEvent(new Event('change', {bubbles: true}));
            return;
        }
        const field = form.querySelector('[name="' + name + '"]:not([type="hidden"])');
        if (field) field.value = value ?? '';
    }

    modal.addEventListener('show.bs.modal', event => {
        if (!event.relatedTarget) return;
        const record = event.relatedTarget.dataset.record ? JSON.parse(event.relatedTarget.dataset.record) : null;
        const values = Object.assign({}, options.defaults, record || {});
        Object.keys(values).forEach(name => { if (name !== 'url' && name !== 'id') setValue(name, values[name]); });

        form.action = record ? record.url : options.createAction;
        method.disabled = !record;
        form.querySelector('input[name="_form"]').value = options.key + (record ? '-edit' : '-create');
        form.querySelector('input[name="_record_id"]').value = record ? record.id : '';
        modal.querySelector('[data-modal-title]').textContent = record ? options.editTitle(record) : options.createTitle;
        modal.querySelector('[data-modal-submit]').textContent = record ? 'حفظ التعديلات' : options.createSubmit;
        modal.querySelector('[data-modal-icon]').className = 'fa ' + (record ? 'fa-pen-to-square' : options.icon);

        // مسح آثار محاولة سابقة
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.hms-search input').forEach(el => { el.value = ''; el.dispatchEvent(new Event('input')); });
        modal.querySelectorAll('[data-modal-errors]').forEach(el => el.remove());
        const feedback = modal.querySelector('#lookup-feedback');
        if (feedback) feedback.className = 'alert border-0 rounded-3 d-none';
        const results = modal.querySelector('#location-results');
        if (results) results.replaceChildren();
    });

    if (options.reopen) document.addEventListener('DOMContentLoaded', () => bootstrap.Modal.getOrCreateInstance(modal).show());
};
</script>
