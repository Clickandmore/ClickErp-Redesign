async function rentalFreezeDialog() {
    const result = await Swal.fire({
        title: 'تجميد عقد الإيجار',
        html: `<div class="text-start" dir="rtl">
            <label for="rental-freeze-reason" class="form-label">سبب التجميد</label>
            <textarea id="rental-freeze-reason" class="form-control mb-3" maxlength="1000" placeholder="اكتب سبب تجميد العقد"></textarea>
            <label for="rental-freeze-choice" class="form-label">ماذا تريد أن يحدث للعاملة؟</label>
            <select id="rental-freeze-choice" class="form-select">
                <option value="">اختر طريقة التعامل مع العاملة</option>
                <option value="0">إبقاؤها مرتبطة بهذا العقد</option>
                <option value="1">إعادتها للإيواء وإتاحتها للتأجير لعقد آخر</option>
            </select>
            <div class="alert alert-info mt-3 mb-0 small">إذا أعدت العاملة متاحة، يجب عند الاستئناف اختيار عاملة متاحة مطابقة لباقة العقد. يستمر احتساب مدة العقد بنفس المنطق الحالي.</div>
        </div>`,
        icon: 'warning', showCancelButton: true,
        confirmButtonText: 'تجميد العقد', cancelButtonText: 'إلغاء',
        preConfirm: () => {
            const reason = document.getElementById('rental-freeze-reason').value.trim();
            const choice = document.getElementById('rental-freeze-choice').value;
            if (!reason || choice === '') {
                Swal.showValidationMessage(!reason ? 'يرجى كتابة سبب التجميد' : 'يرجى اختيار إبقاء العاملة مرتبطة أو إعادتها متاحة');
                return false;
            }
            return { freeze_reason: reason, release_maid: choice };
        }
    });
    return result.isConfirmed ? result.value : null;
}

async function rentalResumeDialog(optionsUrl) {
    let options;
    try {
        options = await $.getJSON(optionsUrl);
    } catch (xhr) {
        await Swal.fire('تعذر تحميل خيارات الاستئناف', xhr.responseJSON?.message || 'يرجى المحاولة مرة أخرى', 'error');
        return null;
    }
    const container = document.createElement('div');
    container.dir = 'rtl';
    container.className = 'text-start';
    let select;
    if (options.requires_maid) {
        const hint = document.createElement('div');
        hint.className = 'alert alert-info small';
        hint.textContent = options.hint;
        container.appendChild(hint);
        const label = document.createElement('label');
        label.className = 'form-label';
        label.textContent = 'عاملة متاحة لباقة: ' + (options.package || 'غير محددة؛ حدد باقة العقد أولاً');
        select = document.createElement('select');
        select.className = 'form-select';
        select.appendChild(new Option('اختر عاملة متاحة', ''));
        options.maids.forEach(maid => select.appendChild(new Option(maid.name, maid.id)));
        container.append(label, select);
        if (!options.maids.length) {
            const empty = document.createElement('p');
            empty.className = 'text-danger mt-2';
            empty.textContent = 'لا توجد حالياً عاملة متاحة تطابق الباقة. أتح عاملة مناسبة ثم أعد المحاولة.';
            container.appendChild(empty);
        }
    } else {
        container.textContent = 'هل تريد استئناف هذا العقد بالعاملة المرتبطة به؟';
    }
    const result = await Swal.fire({
        title: 'استئناف عقد الإيجار', html: container, icon: 'question',
        showCancelButton: true, confirmButtonText: 'استئناف العقد', cancelButtonText: 'إلغاء',
        showConfirmButton: !options.requires_maid || options.maids.length > 0,
        didOpen: () => {
            if (!select) return;
            $(select).select2({
                dropdownParent: $(Swal.getPopup()),
                width: '100%',
                dir: 'rtl',
                placeholder: 'ابحث عن عاملة متاحة',
                minimumResultsForSearch: 0,
                language: {
                    noResults: () => 'لا توجد عاملة مطابقة للبحث'
                }
            }).on('select2:open.rentalResume', () => {
                $(Swal.getPopup()).find('.select2-search__field').trigger('focus');
            });
        },
        willClose: () => {
            if (select && $(select).data('select2')) {
                $(select).off('.rentalResume').select2('destroy');
            }
        },
        preConfirm: () => {
            if (options.requires_maid && !select.value) {
                Swal.showValidationMessage('يجب اختيار عاملة متاحة مطابقة لباقة العقد');
                return false;
            }
            return options.requires_maid ? { maid_id: select.value } : {};
        }
    });
    return result.isConfirmed ? result.value : null;
}
