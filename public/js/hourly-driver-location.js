(() => {
    const element = document.getElementById('driver-location-config');
    if (!element) return;
    const config = JSON.parse(element.textContent), status = document.getElementById('driver-location-status');
    const panel = document.getElementById('driver-location'), pill = document.getElementById('driver-location-pill');
    const pillLabels = {active: 'مفعّلة', waiting: 'بانتظار الإذن', error: 'متوقفة'};
    const report = (text, state) => {
        status.textContent = text;
        if (panel && state) panel.dataset.state = state;
        if (pill && pillLabels[state]) pill.textContent = pillLabels[state];
    };
    let timer, stopped = false, busy = false;
    async function send() {
        if (busy || stopped || !config.enabled || !config.visit) return;
        clearTimeout(timer);
        if (!window.isSecureContext || !navigator.geolocation) {
            report('إرسال الموقع يتطلب HTTPS ومتصفحًا يدعم تحديد الموقع.', 'error'); return;
        }
        busy = true;
        try {
            const position = await new Promise((resolve, reject) => navigator.geolocation.getCurrentPosition(resolve, reject, {enableHighAccuracy: true, timeout: 15000, maximumAge: 0}));
            if (stopped) return;
            const response = await fetch(config.url, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': config.csrf},
                body: JSON.stringify({lat: position.coords.latitude, lng: position.coords.longitude, accuracy: position.coords.accuracy, visit_id: config.visit, device_type: 'driver_web'})});
            const result = await response.json();
            if (!response.ok) { if ([401,403,419].includes(response.status)) stopped = true; throw new Error(result.message || 'تعذر إرسال الموقع.'); }
            report('مشاركة الموقع مفعّلة — آخر إرسال: ' + new Date().toLocaleTimeString('ar-SA'), 'active');
        } catch (error) {
            if (error.code === 1) { stopped = true; report('تم رفض إذن الموقع. فعّله من إعدادات المتصفح ثم أعد المحاولة.', 'error'); }
            else report(error.code ? 'تعذر تحديد الموقع. تحقق من GPS؛ سنعيد المحاولة.' : (error.message || 'تعذر إرسال الموقع.'), 'error');
        } finally {
            busy = false;
            if (!stopped) timer = setTimeout(send, Math.max(5, config.interval || 10) * 1000);
        }
    }
    document.getElementById('driver-location-retry').addEventListener('click', () => { stopped = false; report('جارٍ طلب إذن الموقع…', 'waiting'); send(); });
    window.addEventListener('pagehide', () => { stopped = true; clearTimeout(timer); });
    window.addEventListener('pageshow', () => { stopped = false; send(); });
    send();
})();
