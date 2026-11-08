{{-- مودال مشاركة السير عبر واتساب.
     الاستخدام: window.openMaidCvShareModal([ids]) أو أي رابط يحمل data-maid-cv-share="id". --}}
<div class="modal fade" id="maidCvShareModal" tabindex="-1" aria-labelledby="maidCvShareModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background:#128C7E">
                <h5 class="modal-title" id="maidCvShareModalLabel">
                    <i class="fab fa-whatsapp me-2"></i>إرسال السير عبر واتساب
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body" id="maidCvShareModalBody"></div>
        </div>
    </div>
</div>
@include('admin.ingaz.maid-cv-shares.partials.form-script')
<script>
window.openMaidCvShareModal = async function (ids) {
    const query = new URLSearchParams();
    ids.forEach(id => query.append('maid_ids[]', id));
    const body = document.getElementById('maidCvShareModalBody');
    body.innerHTML = '<div class="text-center py-5 text-muted"><div class="spinner-border text-success mb-2"></div><div>جارٍ التحميل…</div></div>';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('maidCvShareModal')).show();
    const fail = message => {
        body.innerHTML = '<div class="alert alert-danger m-0"></div>';
        body.firstChild.textContent = message;
    };
    try {
        const response = await fetch(@json(route('maid-cv-shares.create')) + '?' + query.toString(),
            {headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}});
        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.html) {
            return fail(data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'تعذر تحميل نموذج المشاركة.'));
        }
        body.innerHTML = data.html;
        const form = body.querySelector('.cv-share-form');
        form.querySelector('.cv-share-cancel')?.classList.remove('d-none');
        window.initMaidCvShareForm(form);
        form.querySelector('.cv-client-search')?.focus();
    } catch (_) {
        fail('تعذر الاتصال بالخادم.');
    }
};

// روابط المشاركة الفردية (أزرار الصفحة وقوائم صفوف الجدول): تفتح المودال بدل الانتقال
document.addEventListener('click', event => {
    const link = event.target.closest('[data-maid-cv-share]');
    if (!link) return;
    event.preventDefault();
    window.openMaidCvShareModal([link.dataset.maidCvShare]);
});
</script>
