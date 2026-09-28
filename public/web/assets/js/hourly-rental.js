(function () {
    'use strict';
    const byId = id => document.getElementById(id);
    async function request(url, csrf, body) {
        const options = {credentials: 'same-origin', headers: {Accept: 'application/json'}};
        if (body !== undefined) {
            options.method = 'POST';
            options.headers['X-CSRF-TOKEN'] = csrf;
            if (body instanceof FormData) options.body = body;
            else { options.headers['Content-Type'] = 'application/json'; options.body = JSON.stringify(body); }
        }
        const response = await fetch(url, options);
        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
            if (response.status === 401 || response.status === 419) {
                throw new Error('انتهت جلسة الدخول. أعيدي تحميل الصفحة وسجلي الدخول للمتابعة.');
            }
            const failure = new Error(Object.values(result.errors || {}).flat().join(' ') || result.message || 'تعذر إتمام الطلب. حاولي مجددًا.');
            failure.status = response.status;
            failure.errors = result.errors || {};
            throw failure;
        }
        return result;
    }
    const bankForm = byId('hourly-bank-form');
    if (bankForm) bankForm.addEventListener('submit', async event => {
        event.preventDefault();
        const button = bankForm.querySelector('button[type=submit]');
        button.disabled = true;
        try {
            await request(bankForm.action, bankForm.querySelector('[name=_token]').value, new FormData(bankForm));
            window.location.reload();
        } catch (error) { byId('hr-bank-status').textContent = error.message; button.disabled = false; }
    });
    const element = byId('hourly-checkout-config');
    if (!element) return;
    const config = JSON.parse(element.textContent);
    const root = byId('hourly-checkout');
    const addresses = config.addresses;
    let quote = config.data, slots = [], slotVersion = 0, quoteVersion = 0, quoteReady = true, busy = false;
    let currentStep = 0, map, marker, pendingPayload = null;
    let chosenMaids = config.canChooseWorker && config.maid ? [Number(config.maid)] : [], availableMaids = [], dayMaids = [], workerGroups = [];
    function workerChoices() {
        if (!config.canChooseWorker) return;
        const container = byId('hr-worker-cards'); container.replaceChildren();
        const selectedSlot = slots.find(slot => slot.starts_at === byId('hr-time').value);
        availableMaids = dateOnly ? dayMaids : (selectedSlot?.workers || []);
        const status = byId('hr-worker-choice-status');
        if (!availableMaids.length) {
            status.textContent = 'اختاري موعدًا تتوفر فيه عاملات لعرضهن هنا.';
            return;
        }
        chosenMaids = chosenMaids.filter(id => availableMaids.some(maid => Number(maid.id) === id)).slice(0, workers());
        status.textContent = 'تم اختيار ' + chosenMaids.length + ' من ' + workers() + ' عاملات. الاختيار اختياري، ويُستكمل الباقي حسب إعدادات الإسناد.';
        availableMaids.forEach(maid => {
            const card = document.createElement('label'); card.className = 'hr-time-card hr-worker-choice-card';
            const radio = document.createElement('input'); radio.type = 'checkbox'; radio.name = 'hr-selected-workers'; radio.value = maid.id;
            radio.checked = chosenMaids.includes(Number(maid.id));
            const compatible = !dateOnly || !workerGroups.length || workerGroups.some(group => [...chosenMaids, Number(maid.id)].every(id => group.includes(id)));
            radio.disabled = !radio.checked && (chosenMaids.length >= workers() || !compatible);
            const content = document.createElement('span'); content.className = 'hr-time-card-content';
            if (maid.photo) {
                const photo = document.createElement('img'); photo.src = maid.photo; photo.alt = ''; photo.loading = 'lazy'; content.append(photo);
            }
            const name = document.createElement('strong'); name.textContent = maid.name; content.append(name);
            if (maid.nationality) { const nationality = document.createElement('span'); nationality.textContent = maid.nationality; content.append(nationality); }
            radio.addEventListener('change', () => {
                const id = Number(maid.id);
                chosenMaids = radio.checked ? [...new Set([...chosenMaids, id])] : chosenMaids.filter(value => value !== id);
                workerChoices(); error('');
            });
            card.append(radio, content); container.append(card);
        });
    }
    const dateOnly = config.data.booking_config.booking.time_selection === 'date';
    const workers = () => Number(byId('hr-workers').value);
    const quantity = () => Number(byId('hr-quantity')?.value || config.data.pricing.quantity);
    const district = () => Number(byId('hr-district').value);
    const address = () => addresses.find(row => Number(row.id) === Number(byId('hr-address').value));
    const money = value => Number(value).toLocaleString('ar-SA', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ر.س';
    const error = message => { byId('hr-error').textContent = message || ''; byId('hr-error').hidden = !message; };
    const query = (url, values) => url + '?' + new URLSearchParams(values).toString();
    function validAddress() {
        if (!address() || !district()) throw new Error('اختاري عنوانًا محفوظًا وحددي الحي، أو احفظي عنوانًا جديدًا أولًا.');
        if (!byId('hr-address-form').hidden) throw new Error('احفظي العنوان الجديد أو أغلقي نموذج الإضافة لاستخدام العنوان المحفوظ.');
    }
    function validVisit() {
        for (const id of ['hr-workers', 'hr-quantity', 'hr-date']) {
            const input = byId(id);
            if (input && (!input.value || !input.checkValidity())) throw new Error('راجعي عدد العاملات وتاريخ الزيارة والكمية.');
        }
        if (!slots.length || (!dateOnly && !slots.some(slot => slot.starts_at === byId('hr-time').value))) throw new Error('اختاري موعدًا متاحًا للزيارة.');
        if (config.canChooseWorker && (chosenMaids.length > workers() || chosenMaids.some(id => !availableMaids.some(maid => Number(maid.id) === id)))) throw new Error('راجعي العاملات المختارات؛ يجب أن يكنّ متاحات وألا يتجاوز عددهن العدد المطلوب.');
        if (config.canChooseWorker && dateOnly && workerGroups.length && !workerGroups.some(group => chosenMaids.every(id => group.includes(id)))) throw new Error('العاملات المختارات غير متاحات معًا في موعد واحد. راجعي الاختيار.');
        if (!quoteReady) throw new Error('انتظري تحديث السعر، أو أعيدي اختيار عدد العاملات للمحاولة مجددًا.');
    }
    function line(label, value) {
        const row = document.createElement('div'); row.className = 'hr-summary-line';
        const key = document.createElement('span'); key.textContent = label;
        const text = document.createElement('strong'); text.textContent = value;
        row.append(key, text); byId('hr-summary').append(row);
    }
    function summary() {
        byId('hr-summary').replaceChildren();
        line('الباقة', quote.title);
        line('العنوان', address()?.address || '');
        line('الحي', config.districts.find(row => Number(row.id) === district())?.name || '');
        line('عدد العاملات', workers());
        if (config.canChooseWorker) {
            line('العاملات المختارات', availableMaids.filter(maid => chosenMaids.includes(Number(maid.id))).map(maid => maid.name).join('، ') || 'دون اختيار');
            if (chosenMaids.length < workers()) line('المتبقي للإسناد', workers() - chosenMaids.length);
        }
        line('عدد الزيارات / ساعات الزيارة', quote.visits_count + ' / ' + quote.hours_per_visit);
        if (byId('hr-quantity')) line(config.data.booking_config.steppers.find(row => row.key === 'quantity').label, quantity());
        line('الزيارة الأولى', dateOnly ? byId('hr-date').value + ' (يُحدد الوقت حسب التوفر)' : byId('hr-time').value.slice(0, 16));
        for (const [key, field] of Object.entries(config.data.booking_config.fields || {})) {
            const input = byId('hr-option-' + key);
            line(field.label, field.type === 'boolean' ? (input.checked ? 'نعم' : 'لا') : input.options[input.selectedIndex]?.textContent || '');
        }
        for (const row of quote.pricing.presentation.lines) line(row.label, money(row.amount));
        const tax = quote.pricing.presentation.tax_line;
        if (tax) line(tax.label, money(tax.amount));
        line('الإجمالي شامل الضريبة', money(quote.pricing.total_amount));
        if (root.querySelector('[name=hr-payment]:checked')?.value === 'moyasar') line('المطلوب دفعه الآن', money(quote.pricing.charge_now));
    }
    function go(step) {
        if (busy) return;
        error('');
        try {
            if (step > 0) validAddress();
            if (step > 1) { validVisit(); summary(); }
            currentStep = step;
            for (let index = 0; index < 3; index++) {
                byId('hr-panel-' + index).hidden = index !== step;
                byId('hr-tab-' + index).setAttribute('aria-selected', String(index === step));
            }
            byId('hr-tab-' + step).focus();
        } catch (failure) { error(failure.message); }
    }
    root.querySelectorAll('[data-step], [data-next]').forEach(button => button.addEventListener('click', () => go(Number(button.dataset.step ?? button.dataset.next))));
    root.querySelectorAll('[name=hr-payment]').forEach(input => input.addEventListener('change', summary));
    async function refreshSlots() {
        const version = ++slotVersion;
        slots = [];
        dayMaids = []; availableMaids = []; workerGroups = [];
        byId('hr-time').value = '';
        byId('hr-time-cards').replaceChildren();
        workerChoices();
        byId('hr-slot-status').textContent = 'اختاري الحي وتاريخ الزيارة لعرض التوفر.';
        if (!district() || !byId('hr-date').value || !byId('hr-workers').checkValidity()) return;
        byId('hr-slot-status').textContent = 'جارٍ التحقق من الأوقات المتاحة…';
        try {
            const values = {hourly_package_id: config.package, district_id: district(), date: byId('hr-date').value, workers_per_visit: workers()};
            const response = await request(query(config.urls.slots, values));
            if (version !== slotVersion) return;
            slots = response.data;
            dayMaids = response.workers || [];
            workerGroups = response.worker_groups || [];
            workerChoices();
            if (!dateOnly) slots.forEach(slot => {
                const card = document.createElement('label'); card.className = 'hr-time-card';
                const radio = document.createElement('input');
                radio.type = 'radio'; radio.name = 'hr-visit-time'; radio.value = slot.starts_at;
                const content = document.createElement('span'); content.className = 'hr-time-card-content';
                const title = document.createElement('span'); title.textContent = 'وقت الزيارة';
                const time = document.createElement('strong'); time.dir = 'ltr';
                time.textContent = slot.starts_at.slice(11, 16) + ' – ' + slot.ends_at.slice(11, 16);
                content.append(title, time); card.append(radio, content);
                radio.addEventListener('change', () => {
                    if (radio.checked) { byId('hr-time').value = radio.value; workerChoices(); error(''); }
                });
                byId('hr-time-cards').append(card);
            });
            byId('hr-slot-status').textContent = slots.length ? (dateOnly ? 'يتوفر في هذا اليوم العدد المطلوب من العاملات، ويُحدد وقت البداية حسب التوفر.' : 'الأوقات المعروضة يتوفر فيها العدد المطلوب من العاملات. التوقيت: ' + config.data.timezone) : 'لا يتوفر العدد المطلوب من العاملات في هذا اليوم. جربي يومًا آخر أو عددًا أقل من العاملات.';
        } catch (failure) { if (version === slotVersion) { byId('hr-slot-status').textContent = 'تعذر التحقق من التوفر.'; error(failure.message); } }
    }
    async function refreshQuote() {
        const version = ++quoteVersion;
        quoteReady = false;
        if (!byId('hr-workers').checkValidity() || (byId('hr-quantity') && !byId('hr-quantity').checkValidity())) return;
        try {
            const response = await request(query(config.urls.quote, {city_id: config.city, workers: workers(), quantity: quantity()}));
            if (version !== quoteVersion) return;
            quote = response.data; quoteReady = true;
            if (currentStep === 2) summary();
        } catch (failure) { if (version === quoteVersion) error(failure.message); }
    }
    function districtFromText(text) {
        const normalize = value => String(value || '').toLowerCase().replace(/[أإآ]/g, 'ا').replace(/ـ/g, '').replace(/[^\p{L}\p{N}]+/gu, ' ').trim();
        const addressText = ' ' + normalize(text) + ' ';
        const matches = config.districts.filter(row => [row.name, row.name_en].some(name => {
            const normalized = normalize(name).replace(/^(حي|district)\s+/, '');
            return normalized && addressText.includes(' ' + normalized + ' ');
        }));
        return matches.length === 1 ? matches[0] : null;
    }
    function selectSavedAddress() {
        const selected = address();
        const match = config.districts.find(row => Number(row.id) === Number(selected?.hourly_district_id)) || districtFromText((selected?.district || '') + ' ' + (selected?.address || ''));
        byId('hr-district').value = match?.id || '';
        refreshSlots();
    }
    byId('hr-address').addEventListener('change', selectSavedAddress);
    function matchNewAddress() {
        const match = districtFromText(byId('hr-address-text').value);
        byId('hr-district').value = match?.id || '';
        refreshSlots();
    }
    byId('hr-address-text').addEventListener('input', matchNewAddress);
    byId('hr-district').addEventListener('change', refreshSlots);
    byId('hr-date').addEventListener('change', refreshSlots);
    byId('hr-workers').addEventListener('input', () => { refreshSlots(); refreshQuote(); });
    byId('hr-quantity')?.addEventListener('input', refreshQuote);
    byId('hr-new-address').addEventListener('click', () => {
        const form = byId('hr-address-form'); form.hidden = !form.hidden;
        if (!form.hidden) { matchNewAddress(); initMap(); }
        else selectSavedAddress();
    });
    function setLocation(lat, lng) {
        byId('hr-latitude').value = Number(lat).toFixed(7);
        byId('hr-longitude').value = Number(lng).toFixed(7);
        byId('hr-district').value = '';
        refreshSlots();
        if (!map) return;
        if (!marker) { marker = L.marker([lat, lng], {draggable: true}).addTo(map); marker.on('dragend', () => { const point = marker.getLatLng(); setLocation(point.lat, point.lng); }); }
        marker.setLatLng([lat, lng]); map.setView([lat, lng], 16);
    }
    function initMap() {
        if (!window.L) { byId('hr-map').textContent = 'تعذر تحميل الخريطة. يمكنك إدخال الإحداثيات أو استخدام موقعك الحالي.'; return; }
        if (!map) {
            map = L.map('hr-map').setView(config.mapCenter || [24.7136, 46.6753], 10);
            if (window.HourlyMaps) window.HourlyMaps.attach(map);
            else L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution: '© OpenStreetMap contributors'}).addTo(map);
            map.on('click', event => setLocation(event.latlng.lat, event.latlng.lng));
        }
        setTimeout(() => map.invalidateSize(), 50);
    }
    byId('hr-search').addEventListener('click', async () => {
        const button = byId('hr-search'), value = byId('hr-map-search').value.trim();
        if (!value) return;
        button.disabled = true; error('');
        try {
            const result = await request(config.urls.geocode, config.csrf, {query: value});
            if (result.latitude === undefined || result.longitude === undefined) throw new Error('لم يُعثر على الموقع. حدديه على الخريطة.');
            setLocation(result.latitude, result.longitude);
            if (result.address) byId('hr-address-text').value = result.address;
            matchNewAddress();
        } catch (failure) { error(failure.message); } finally { button.disabled = false; }
    });
    byId('hr-locate').addEventListener('click', () => {
        if (!navigator.geolocation) return error('تحديد الموقع غير مدعوم في هذا المتصفح.');
        navigator.geolocation.getCurrentPosition(position => setLocation(position.coords.latitude, position.coords.longitude), () => error('تعذر الوصول إلى موقعك. اختاريه من الخريطة.'), {timeout: 10000});
    });
    byId('hr-address-form').addEventListener('submit', async event => {
        event.preventDefault(); error('');
        const form = event.currentTarget, button = form.querySelector('button[type=submit]'); button.disabled = true;
        try {
            const values = Object.fromEntries(new FormData(form));
            const response = await request(config.urls.address, config.csrf, {...values, city_id: config.city, hourly_package_id: config.package, district_id: district() || null});
            addresses.push(response.data);
            byId('hr-address').append(new Option(response.data.title + ' — ' + response.data.address, response.data.id));
            byId('hr-address').value = response.data.id; form.hidden = true; selectSavedAddress();
        } catch (failure) { error(failure.message); } finally { button.disabled = false; }
    });
    byId('hr-submit').addEventListener('click', async () => {
        if (busy) return;
        error('');
        try {
            validAddress(); validVisit();
            const payment = root.querySelector('[name=hr-payment]:checked')?.value;
            if (!payment) throw new Error('اختاري طريقة الدفع.');
            const payload = {booking_token: config.token, hourly_package_id: config.package, address_id: Number(address().id), district_id: district(), workers_per_visit: workers(), quantity: quantity(), payment_method: payment};
            if (config.canChooseWorker) payload.selected_maid_ids = chosenMaids;
            if (dateOnly) payload.scheduled_dates = [byId('hr-date').value];
            else payload.scheduled_starts = [byId('hr-time').value];
            for (const [key, field] of Object.entries(config.data.booking_config.fields || {})) payload[key] = field.type === 'boolean' ? byId('hr-option-' + key).checked : byId('hr-option-' + key).value;
            // Keep the original payload after an uncertain network response, preventing a second order.
            if (pendingPayload && JSON.stringify(pendingPayload) !== JSON.stringify(payload)) throw new Error('تحققي من صفحة حجوزاتي قبل تغيير طلب سبق إرساله، أو أعيدي نفس الخيارات لإعادة المحاولة.');
            pendingPayload = payload;
            busy = true; byId('hr-submit').disabled = true;
            const result = await request(config.urls.store, config.csrf, payload);
            window.location.assign(result.redirect);
        } catch (failure) {
            if (failure.status === 422) pendingPayload = null;
            busy = false; byId('hr-submit').disabled = false;
            // حُجز الموعد بين العرض والتأكيد: نعود لخطوة الموعد بأوقات محدّثة بدل البقاء في خطوة الدفع
            if (failure.errors?.slot) { go(1); refreshSlots(); }
            error(failure.message);
        }
    });
}());
