/* GoogleMutant 0.15.0 preserves Leaflet's existing markers and polygon editing.
 * Upstream: https://gitlab.com/IvanSanchez/Leaflet.GridLayer.GoogleMutant/
 */
(() => {
    'use strict';
    if (window.HourlyMaps) return;
    const config = window.hourlyMapConfig || {};
    const mounts = new WeakMap(), googleMaps = new Set();
    let loading, authFailed = false;

    function script(url) {
        return new Promise((resolve, reject) => {
            const element = document.createElement('script');
            const timeout = setTimeout(() => reject(new Error('Map script timed out')), 20000);
            element.src = url; element.async = true;
            element.onload = () => { clearTimeout(timeout); resolve(); };
            element.onerror = () => { clearTimeout(timeout); reject(new Error('Map script unavailable')); };
            document.head.appendChild(element);
        });
    }

    function googleReady() {
        if (!loading) loading = (async () => {
            if (!window.google?.maps?.Map) {
                await new Promise((resolve, reject) => {
                    const timeout = setTimeout(() => reject(new Error('Google Maps timed out')), 20000);
                    window.initHourlySharedMaps = () => { clearTimeout(timeout); resolve(); };
                    script('https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(config.key)
                        + '&language=' + encodeURIComponent(config.language || 'ar')
                        + '&loading=async&callback=initHourlySharedMaps').catch(error => { clearTimeout(timeout); reject(error); });
                });
            }
            if (authFailed) throw new Error('Google Maps authentication failed');
            if (!L.gridLayer.googleMutant) await script(config.bridgeUrl);
        })();
        return loading;
    }

    const previousAuthFailure = window.gm_authFailure;
    window.gm_authFailure = () => {
        authFailed = true;
        googleMaps.forEach(entry => entry.fallback());
        if (typeof previousAuthFailure === 'function') previousAuthFailure();
    };

    function attach(map, options = {}) {
        if (mounts.has(map)) return mounts.get(map);
        let layers = [], control, notice, disposed = false, fallbackUsed = false;
        function clear() {
            if (control) { map.removeControl(control); control = null; }
            layers.forEach(layer => { if (map.hasLayer(layer)) map.removeLayer(layer); });
            layers = [];
        }
        // options.base = 'satellite' يبدأ بالقمر الصناعي، options.labels يترجم أسماء الطبقتين،
        // و options.onBaseChange(name) يُستدعى عند تبديل المستخدم حتى تحفظ الصفحة اختياره.
        const labels = Object.assign({streets:'خريطة', satellite:'قمر صناعي'}, options.labels || {});
        let satelliteLayer = null;
        function install(google) {
            if (disposed) return;
            clear();
            const streets = google ? L.gridLayer.googleMutant({type:'roadmap',maxZoom:19})
                : L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:19,attribution:'&copy; OpenStreetMap contributors'});
            layers.push(streets);
            satelliteLayer = null;
            if (options.satellite) {
                satelliteLayer = google ? L.gridLayer.googleMutant({type:'hybrid',maxZoom:19})
                    : L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {maxZoom:19,attribution:'Tiles &copy; Esri'});
                layers.push(satelliteLayer);
                control = L.control.layers({[labels.streets]:streets,[labels.satellite]:satelliteLayer},null,{position:options.controlPosition || 'topleft',collapsed:false}).addTo(map);
            }
            (options.base === 'satellite' && satelliteLayer ? satelliteLayer : streets).addTo(map);
        }
        if (typeof options.onBaseChange === 'function') {
            map.on('baselayerchange', event => options.onBaseChange(event.layer === satelliteLayer ? 'satellite' : 'streets'));
        }
        const entry = {fallback() {
            if (disposed || fallbackUsed) return;
            fallbackUsed = true; install(false);
            notice = L.control({position:'bottomleft'});
            notice.onAdd = () => {
                const box = L.DomUtil.create('div');
                box.textContent = 'تعذر تحميل Google Maps؛ تُعرض الخريطة المجانية مؤقتًا.';
                box.setAttribute('role','status');
                box.style.cssText = 'background:white;color:#92400e;padding:8px;max-width:260px;border-radius:6px;direction:rtl';
                return box;
            };
            notice.addTo(map);
        }};
        map.on('unload',() => { disposed = true; googleMaps.delete(entry); });
        const ready = (async () => {
            if (config.provider !== 'google' || !config.key) { install(false); return; }
            googleMaps.add(entry);
            try {
                await googleReady();
                if (authFailed) { entry.fallback(); return; }
                if (!fallbackUsed) install(true);
            } catch (_) { entry.fallback(); }
        })();
        mounts.set(map,ready);
        return ready;
    }
    window.HourlyMaps = {attach};
})();
