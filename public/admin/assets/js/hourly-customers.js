/* عملاء الساعات: الفلاتر، مفتاح التحقق، نموذج العميل (فرد/شركة والعمر)، إضافة رصيد، وعنوان بالخريطة.
 * النصوص من #hc-data بلغة الواجهة. */
(() => {
    'use strict';
    const root = document.getElementById('hourly-customers');
    if (!root) return;
    const i18n = JSON.parse(document.getElementById('hc-data')?.textContent || '{"i18n":{}}').i18n;
    const t = (key, params = {}) => Object.entries(params).reduce((text, [k, v]) => text.split(':' + k).join(v), i18n[key] ?? key);
    const $ = id => document.getElementById(id);

    function notify(options) {
        if (window.Swal) return Swal.fire(Object.assign({ confirmButtonText: t('ok') }, options, { customClass: { popup: 'om-swal' } }));
        alert(options.text || options.title);
    }

    // الحي يتبع المدينة المختارة (القائمة وفلتر القائمة ونموذج العنوان).
    function linkDistricts(citySelect, districtSelect) {
        if (!citySelect || !districtSelect) return;
        const sync = () => {
            districtSelect.querySelectorAll('option[data-city]').forEach(option => {
                option.hidden = !!citySelect.value && option.dataset.city !== citySelect.value;
            });
            if (districtSelect.selectedOptions[0]?.hidden) districtSelect.value = '';
        };
        citySelect.addEventListener('change', sync);
        sync();
    }
    linkDistricts($('hc-city_id'), $('hc-district_id'));

    // عدد الصفوف في الصفحة.
    $('hc-per-page')?.addEventListener('change', e => {
        const form = $('hc-filters');
        form.querySelector('input[name="per_page"]').value = e.target.value;
        form.submit();
    });

    // مفتاح التحقق في القائمة والملف.
    document.querySelectorAll('[data-verify]').forEach(box => box.addEventListener('change', async () => {
        const checked = box.checked;
        box.disabled = true;
        try {
            const response = await fetch(box.dataset.verify, { method: 'PUT', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': root.dataset.csrf }, body: JSON.stringify({ verified: checked }) });
            if (!response.ok) throw new Error();
        } catch (_) {
            box.checked = !checked;
            notify({ icon: 'error', title: t('failed'), text: t('error') });
        } finally {
            box.disabled = false;
        }
    }));

    // نسخ البريد والجوال.
    document.querySelectorAll('[data-copy]').forEach(button => button.addEventListener('click', async () => {
        try { await navigator.clipboard.writeText(button.dataset.copy); button.title = t('copied'); button.innerHTML = '<i class="fa fa-check"></i>'; } catch (_) { /* المتصفح يمنع النسخ */ }
        setTimeout(() => { button.innerHTML = '<i class="fa fa-copy"></i>'; }, 1500);
    }));

    // نموذج العميل: حقول الفرد أو الشركة، والعمر من تاريخ الميلاد.
    const form = $('hc-customer-form');
    if (form) {
        const applyType = () => {
            const type = form.querySelector('[data-customer-type]:checked')?.value || 'individual';
            form.querySelectorAll('[data-only]').forEach(field => {
                const on = field.dataset.only === type;
                field.hidden = !on;
                field.querySelectorAll('input, select, textarea').forEach(input => { input.disabled = !on || input.closest('fieldset')?.disabled; });
            });
        };
        form.querySelectorAll('[data-customer-type]').forEach(radio => radio.addEventListener('change', applyType));
        applyType();
        const birth = $('hc-birthdate'), age = $('hc-age');
        birth?.addEventListener('change', () => {
            if (!birth.value) { age.textContent = '—'; return; }
            const b = new Date(birth.value), now = new Date();
            let years = now.getFullYear() - b.getFullYear();
            if (now.getMonth() < b.getMonth() || (now.getMonth() === b.getMonth() && now.getDate() < b.getDate())) years--;
            age.textContent = years >= 0 ? t('age', { age: years }) : '—';
        });
    }

    // إضافة رصيد: الطلب يظهر مع «دفعة على طلب»، ومن استلم المبلغ موظف أو عاملة.
    const deposit = $('hc-deposit-form');
    if (deposit) {
        const orderBox = deposit.querySelector('[data-deposit-order]');
        const amount = $('hc-amount');
        const syncMode = () => {
            const order = deposit.querySelector('input[name="mode"]:checked')?.value === 'order';
            orderBox.hidden = !order;
            $('hc-deposit-order').disabled = !order;
            const remaining = order ? $('hc-deposit-order').selectedOptions[0]?.dataset.remaining : null;
            if (remaining) amount.max = remaining; else amount.removeAttribute('max');
        };
        deposit.querySelectorAll('input[name="mode"]').forEach(radio => radio.addEventListener('change', syncMode));
        $('hc-deposit-order')?.addEventListener('change', syncMode);
        syncMode();
        $('hc-collector-type').addEventListener('change', e => {
            deposit.querySelectorAll('[data-collector]').forEach(select => {
                const on = select.dataset.collector === e.target.value;
                select.hidden = !on; select.disabled = !on;
            });
        });
        const description = $('hc-description');
        description.addEventListener('input', () => { $('hc-description-count').textContent = '(' + description.value.length + '/500)'; });
    }

    // عنوان العميل: إضافة/تعديل مع الموقع على الخريطة.
    const addressModal = $('hc-address-modal');
    if (addressModal) {
        const addressForm = $('hc-address-form');
        const city = $('hc-a-city'), district = $('hc-a-district'), lat = $('hc-a-lat'), lng = $('hc-a-lng');
        let map, marker, current = null;
        linkDistricts(city, district);

        const place = (latitude, longitude, zoom) => {
            if (!map || !Number.isFinite(latitude) || !Number.isFinite(longitude)) return;
            if (!marker) {
                marker = L.marker([latitude, longitude], { draggable: true }).addTo(map);
                marker.on('dragend', () => { const p = marker.getLatLng(); lat.value = p.lat.toFixed(7); lng.value = p.lng.toFixed(7); });
            } else marker.setLatLng([latitude, longitude]);
            map.setView([latitude, longitude], zoom || Math.max(map.getZoom(), 15));
        };
        const open = data => {
            current = data;
            addressForm.reset();
            const edit = !!data;
            addressForm.action = edit ? data.url : addressForm.dataset.store;
            $('hc-address-method').value = edit ? 'PUT' : 'POST';
            const title = $('hc-address-title');
            title.textContent = edit ? title.dataset.edit : title.dataset.add;
            if (edit) {
                ['city_id', 'hourly_district_id', 'address', 'building', 'unit', 'contact_first_name', 'contact_last_name', 'contact_phone', 'contact_phone2', 'latitude', 'longitude']
                    .forEach(key => { const input = addressForm.elements[key]; if (input) input.value = data[key] ?? ''; });
            }
            city.dispatchEvent(new Event('change'));
            if (edit) district.value = data.hourly_district_id ?? '';
            bootstrap.Modal.getOrCreateInstance(addressModal).show();
        };
        document.querySelector('[data-address-new]')?.addEventListener('click', () => open(null));
        document.querySelectorAll('[data-address-edit]').forEach(button => button.addEventListener('click', () => open(JSON.parse(button.dataset.addressEdit))));

        addressModal.addEventListener('shown.bs.modal', () => {
            if (!map) {
                map = L.map('hc-address-map').setView([24.7136, 46.6753], 11);
                HourlyMaps.attach(map);
                map.on('click', e => { lat.value = e.latlng.lat.toFixed(7); lng.value = e.latlng.lng.toFixed(7); place(e.latlng.lat, e.latlng.lng); });
            }
            map.invalidateSize();
            if (current && current.latitude !== null) place(Number(current.latitude), Number(current.longitude), 16);
            else if (marker) { marker.remove(); marker = null; }
        });
        city.addEventListener('change', () => {
            const option = city.selectedOptions[0];
            if (map && !lat.value && option?.dataset.lat) map.setView([Number(option.dataset.lat), Number(option.dataset.lng)], 12);
        });
        [lat, lng].forEach(input => input.addEventListener('change', () => place(Number(lat.value), Number(lng.value))));
    }

    // تلميحات أيقونة الطلبات المكتملة.
    if (window.bootstrap?.Tooltip) root.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => bootstrap.Tooltip.getOrCreateInstance(el));

    // تأكيد قبل الإرسال (حذف المجموعة).
    const confirmSubmit = (form, text) => form.addEventListener('submit', e => {
        if (form.dataset.confirmed) return;
        e.preventDefault();
        const go = () => { form.dataset.confirmed = '1'; form.submit(); };
        if (window.Swal) Swal.fire({ icon: 'warning', text, showCancelButton: true, confirmButtonText: t('ok'), cancelButtonText: t('cancel'), customClass: { popup: 'om-swal' } }).then(r => { if (r.isConfirmed) go(); });
        else if (confirm(text)) go();
    });
    document.querySelectorAll('[data-confirm-group-delete]').forEach(form => confirmSubmit(form, t('delete_group')));

    // مجموعات العملاء: نافذة واحدة للإضافة والتعديل.
    const groupModal = $('hc-group-modal');
    if (groupModal) {
        const groupForm = $('hc-group-form');
        document.querySelectorAll('[data-group-edit]').forEach(button => button.addEventListener('click', () => {
            const data = JSON.parse(button.dataset.groupEdit);
            groupForm.reset();
            groupForm.action = data.action;
            groupForm.elements._method.value = data.method;
            $('hc-group-title').textContent = data.title;
            $('hc-group-name').value = data.name ?? '';
            $('hc-group-description').value = data.description ?? '';
            $('hc-group-discount').value = data.discount_percent ?? '';
            $('hc-group-active').checked = !!data.is_active;
            bootstrap.Modal.getOrCreateInstance(groupModal).show();
        }));
    }

    // الحسابات المكررة: حسابان على الأقل قبل فتح شاشة الدمج.
    document.querySelectorAll('[data-merge-select]').forEach(form => form.addEventListener('submit', e => {
        if (form.querySelectorAll('input[name="ids[]"]:checked').length < 2) {
            e.preventDefault();
            notify({ icon: 'warning', text: t('select_two') });
        }
    }));

    // شاشة الدمج: الحساب الأساسي لا يُدمج في نفسه.
    const mergeForm = $('hc-merge-form');
    if (mergeForm) {
        const sync = () => {
            const primary = mergeForm.querySelector('input[name="primary_id"]:checked')?.value;
            mergeForm.querySelectorAll('input[name="merge_ids[]"]').forEach(box => {
                const isPrimary = box.value === primary;
                if (isPrimary) box.checked = false;
                else if (box.disabled) box.checked = true;
                box.disabled = isPrimary;
            });
        };
        mergeForm.querySelectorAll('input[name="primary_id"]').forEach(radio => radio.addEventListener('change', sync));
        mergeForm.addEventListener('submit', e => {
            if (!mergeForm.querySelector('input[name="merge_ids[]"]:checked')) { e.preventDefault(); notify({ icon: 'warning', text: t('select_two') }); }
        });
        sync();
    }
})();
