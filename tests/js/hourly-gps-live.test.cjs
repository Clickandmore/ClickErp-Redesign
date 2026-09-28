const { test } = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const { readFileSync } = require('node:fs');
const source = readFileSync('public/admin/assets/js/hourly-gps.js', 'utf8');

test('refresh reuses markers, updates their location and removes only missing people', () => {
    let created = 0;
    const removed = [];
    const coordinates = (lat, lng) => ({ lat, lng, equals: p => p.lat === lat && p.lng === lng });
    const context = {
        markers: new Map(), points: [], live: false, window: {},
        keyOf: p => p.type + ':' + p.id, icon: p => p.heading, esc: x => x, popup: p => p.name,
        layer: { removeLayer: m => removed.push(m) },
        L: { latLng: coordinates, marker: ([lat, lng]) => {
            created++;
            return { position: coordinates(lat, lng), bindPopup(fn) { this.content = fn; return this; },
                bindTooltip() { return this; }, addTo() {}, getLatLng() { return this.position; },
                setLatLng(p) { this.position = p; }, setIcon() {}, setZIndexOffset() {},
                isPopupOpen: () => true, setPopupContent(fn) { this.content = fn; } };
        } },
    };
    vm.createContext(context);
    vm.runInContext(source.slice(source.indexOf('    function stopMovement'), source.indexOf('    const Fit')), context);
    const person = { id: 1, type: 'driver', lat: 24, lng: 46, name: 'Driver', active: true, heading: 0 };
    context.points = [person]; context.syncMarkers();
    const marker = context.markers.get('driver:1');
    context.points = [{ ...person, lat: 25, heading: 90 }]; context.syncMarkers();
    assert.equal(created, 1);
    assert.equal(context.markers.get('driver:1'), marker);
    assert.equal(marker.position.lat, 25);
    assert.equal(marker.content(), 'Driver');
    context.points = []; context.syncMarkers();
    assert.deepEqual(removed, [marker]);
});

function loader(fetch) {
    let nextTimer = 0;
    const timers = new Map();
    const context = { fetch, AbortController, request: 0, failures: 0, timer: null, controller: null,
        loading: false, realtimePending: false, pollDelay: () => 5000,
        live: true, past: false, fitted: true, trailOf: null, points: [], document: { hidden: false },
        form: { dataset: { url: '/gps/data', refresh: '5' } }, query: () => '',
        $: () => ({ classList: { add() {}, remove() {} } }),
        setTimeout(fn, delay) { timers.set(++nextTimer, { fn, delay }); return nextTimer; },
        clearTimeout(id) { timers.delete(id); },
        syncMarkers() {}, renderStats() {}, renderPeople() {}, toast() {}, t: x => x, fitAll: () => true,
    };
    vm.createContext(context);
    vm.runInContext(source.slice(source.indexOf('    async function load()'), source.indexOf('    function renderStats')), context);
    return { context, timers };
}
const response = data => ({ ok: true, json: async () => data });

test('connection errors retry with backoff and recovery restores five-second updates', async () => {
    const { context, timers } = loader(async () => { throw new Error('offline'); });
    await context.load();
    assert.equal([...timers.values()][0].delay, 10000);
    context.fetch = async () => response({ live: true, past: false, points: [] });
    await context.load();
    assert.equal(timers.size, 1);
    assert.equal([...timers.values()][0].delay, 5000);
    context.document.hidden = true;
    await context.load();
    assert.equal(timers.size, 0);
});

test('a superseded JSON response cannot overwrite newer filters or restart polling', async () => {
    let finishOld;
    let readingJson;
    const started = new Promise(resolve => { readingJson = resolve; });
    const { context, timers } = loader(async () => ({ ok: true, json: () => new Promise(resolve => { finishOld = resolve; readingJson(); }) }));
    const old = context.load();
    await started;
    context.fetch = async () => response({ live: false, past: true, points: [{ id: 'new' }] });
    await context.load();
    finishOld({ live: true, past: false, points: [{ id: 'old' }] });
    await old;
    assert.equal(context.points[0].id, 'new');
    assert.equal(context.live, false);
    assert.equal(timers.size, 0);
});
