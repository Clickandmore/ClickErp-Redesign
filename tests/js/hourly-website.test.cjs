const {test} = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const {readFileSync} = require('node:fs');
const source = readFileSync('public/web/assets/js/hourly-rental.js', 'utf8');

function harness({dateOnly = false, canChooseWorker = false, responder, districts = [{id: 20, name: 'District'}]} = {}) {
    const nodes = {}, calls = [], redirects = [];
    class Node {
        constructor() { this.value = ''; this.children = []; this.listeners = {}; this.hidden = false; this.dataset = {}; this.checked = false; this.textContent = ''; }
        addEventListener(event, callback) { this.listeners[event] = callback; }
        dispatch(event) { return this.listeners[event]?.({currentTarget: this, preventDefault() {}}); }
        append(...rows) { this.children.push(...rows); }
        replaceChildren(...rows) { this.children = rows; this.value = rows[0]?.value || ''; }
        checkValidity() { return true; }
        setAttribute() {}
        focus() {}
        querySelector() { return null; }
        querySelectorAll() { return []; }
    }
    const $ = id => nodes[id] ||= new Node();
    const data = {title: 'Package', visits_count: 2, hours_per_visit: 4, timezone: 'Asia/Riyadh',
        pricing: {workers: 1, quantity: 1, total_amount: 230, charge_now: 115, presentation: {lines: [{label: 'Service', amount: 230}], tax_line: null}},
        booking_config: {booking: {time_selection: dateOnly ? 'date' : 'time'}, fields: {}, steppers: []}};
    const config = {package: 1, city: 1, maid: 7, canChooseWorker, token: 'same-checkout-token', csrf: 'csrf', data,
        addresses: [{id: 10, title: 'Home', address: 'Address', hourly_district_id: 20}], districts,
        urls: {slots: '/slots', quote: '/quote', store: '/store', address: '/address', geocode: '/geocode'}};
    $('hourly-checkout-config').textContent = JSON.stringify(config);
    $('hr-address').value = '10'; $('hr-district').value = '20'; $('hr-date').value = '2030-01-07'; $('hr-workers').value = '1';
    $('hr-address-form').hidden = true;
    const payment = new Node(); payment.value = 'moyasar';
    const next = [0, 1, 2].map(index => Object.assign(new Node(), {dataset: {next: String(index)}}));
    $('hourly-checkout').querySelector = selector => selector.includes('hr-payment') ? payment : null;
    $('hourly-checkout').querySelectorAll = selector => selector.includes('data-step') ? next : [payment];
    const response = (result, status = 200) => ({ok: status < 400, status, json: async () => result});
    const slot = {starts_at: '2030-01-07 08:00:00', ends_at: '2030-01-07 12:00:00', workers: [{id: 7, name: 'First worker'}, {id: 8, name: 'Second worker'}]};
    const context = {document: {getElementById: id => ['hourly-bank-form', 'hr-quantity'].includes(id) ? null : $(id), createElement: () => new Node()},
        window: {location: {assign: url => redirects.push(url)}}, FormData: class {
            constructor(form) { this.entries = form.entries || []; }
            *[Symbol.iterator]() { yield* this.entries; }
        }, URLSearchParams, setTimeout, navigator: {},
        Option: class extends Node { constructor(label, value) { super(); this.textContent = label; this.value = value; } },
        fetch: async (url, options) => {
            calls.push({url, options});
            if (responder) { const custom = await responder(url, options, response); if (custom) return custom; }
            if (url.startsWith('/slots')) return response({data: [slot], workers: dateOnly ? slot.workers : []});
            if (url.startsWith('/quote')) return response({data});
            if (url === '/store') return response({redirect: '/order/1'}, 201);
            throw new Error('Unexpected request ' + url);
        }};
    vm.runInNewContext(source, context);
    return {$, calls, redirects, payment, next, slot, async ready() {
        await $('hr-date').dispatch('change');
        if (!dateOnly) {
            const radio = $('hr-time-cards').children[0].children[0];
            radio.checked = true; radio.dispatch('change');
        }
    }};
}

test('customer can choose a different available worker during checkout and submit her id', async () => {
    const h = harness({canChooseWorker: true}); await h.ready();
    assert.equal(h.$('hr-worker-cards').children.length, 2);
    const first = h.$('hr-worker-cards').children[0].children[0]; first.checked = false; first.dispatch('change');
    const radio = h.$('hr-worker-cards').children[1].children[0];
    radio.checked = true; radio.dispatch('change');
    await h.$('hr-submit').dispatch('click');
    assert.deepEqual(JSON.parse(h.calls.find(row => row.url === '/store').options.body).selected_maid_ids, [8]);
});

test('date-only worker selection submits the complete selection', async () => {
    const h = harness({canChooseWorker: true, dateOnly: true}); await h.ready();
    const radio = h.$('hr-worker-cards').children[0].children[0];
    radio.checked = true; radio.dispatch('change');
    await h.$('hr-submit').dispatch('click');
    const payload = JSON.parse(h.calls.find(row => row.url === '/store').options.body);
    assert.deepEqual(payload.selected_maid_ids, [7]);
    assert.deepEqual(payload.scheduled_dates, ['2030-01-07']);
});

test('multiple worker selection submits all chosen ids', async () => {
    const h = harness({canChooseWorker: true}); h.$('hr-workers').value = '2'; await h.ready();
    const second = h.$('hr-worker-cards').children[1].children[0]; second.checked = true; second.dispatch('change');
    await h.$('hr-submit').dispatch('click');
    assert.deepEqual(JSON.parse(h.calls.find(row => row.url === '/store').options.body).selected_maid_ids, [7, 8]);
});

test('customer may submit a partial worker selection or no workers', async () => {
    for (const selectNone of [false, true]) {
        const h = harness({canChooseWorker: true}); h.$('hr-workers').value = '2'; await h.ready();
        if (selectNone) { const first = h.$('hr-worker-cards').children[0].children[0]; first.checked = false; first.dispatch('change'); }
        await h.$('hr-submit').dispatch('click');
        assert.deepEqual(JSON.parse(h.calls.find(row => row.url === '/store').options.body).selected_maid_ids, selectNone ? [] : [7]);
    }
});

test('date-only choices disable workers without a common slot and cap the selection', async () => {
    const h = harness({canChooseWorker: true, dateOnly: true, responder(url, options, response) {
        if (url.startsWith('/slots')) return response({data: [{starts_at: '2030-01-07 08:00:00'}],
            workers: [{id: 7, name: 'A'}, {id: 8, name: 'B'}, {id: 9, name: 'C'}, {id: 10, name: 'D'}], worker_groups: [[7, 8], [9, 10]]});
    }});
    h.$('hr-workers').value = '2'; await h.ready();
    assert.equal(h.$('hr-worker-cards').children[2].children[0].disabled, true);
    const second = h.$('hr-worker-cards').children[1].children[0]; second.checked = true; second.dispatch('change');
    assert.equal(h.$('hr-worker-cards').children[2].children[0].disabled, true);
    await h.$('hr-submit').dispatch('click');
    assert.deepEqual(JSON.parse(h.calls.find(row => row.url === '/store').options.body).selected_maid_ids, [7, 8]);
});

test('checkout submits selected address, worker and slot and redirects to the server payment link', async () => {
    const h = harness(); await h.ready(); h.next[2].dispatch('click');
    assert.equal(h.$('hr-panel-2').hidden, false);
    assert.ok(h.$('hr-summary').children.some(row => row.children[0].textContent === 'المطلوب دفعه الآن'));
    await h.$('hr-submit').dispatch('click');
    const call = h.calls.find(row => row.url === '/store'), payload = JSON.parse(call.options.body);
    assert.deepEqual(payload.scheduled_starts, ['2030-01-07 08:00:00']);
    assert.equal(payload.preferred_maid_id, undefined); assert.equal(payload.selected_maid_ids, undefined); assert.equal(payload.address_id, 10); assert.equal(payload.district_id, 20);
    assert.equal(call.options.headers['X-CSRF-TOKEN'], 'csrf');
    assert.equal(payload.bring_materials, undefined); assert.equal(payload.service_type, undefined);
    assert.deepEqual(h.redirects, ['/order/1']);
});

test('hidden time selection submits a date rather than an arbitrary start time', async () => {
    const h = harness({dateOnly: true}); await h.ready();
    await h.$('hr-submit').dispatch('click');
    const payload = JSON.parse(h.calls.find(row => row.url === '/store').options.body);
    assert.deepEqual(payload.scheduled_dates, ['2030-01-07']); assert.equal(payload.scheduled_starts, undefined);
});

test('changing worker count clears the old time and requests updated capacity and price', async () => {
    const h = harness(); await h.ready();
    h.$('hr-workers').value = '2'; h.$('hr-workers').dispatch('input');
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(h.$('hr-time').value, '');
    assert.ok(h.calls.some(row => row.url.startsWith('/slots?') && row.url.includes('workers_per_visit=2')));
    assert.ok(h.calls.some(row => row.url.startsWith('/quote?') && row.url.includes('workers=2')));
    h.$('hr-time').value = h.slot.starts_at;
    await h.$('hr-submit').dispatch('click');
    assert.equal(JSON.parse(h.calls.find(row => row.url === '/store').options.body).workers_per_visit, 2);
});

test('changing the date clears the previous selection and stale availability responses cannot replace it', async () => {
    let resolveOld;
    const h = harness({responder: (url, options, response) => {
        if (url.includes('date=2030-01-07')) return new Promise(resolve => { resolveOld = () => resolve(response({data: [{starts_at: '2030-01-07 08:00:00', ends_at: '2030-01-07 12:00:00'}]})); });
        if (url.includes('date=2030-01-08')) return response({data: []});
    }});
    const old = h.$('hr-date').dispatch('change');
    h.$('hr-date').value = '2030-01-08'; await h.$('hr-date').dispatch('change');
    resolveOld(); await old;
    assert.equal(h.$('hr-time-cards').children.length, 0);
    assert.equal(h.$('hr-time').value, '');
    await h.$('hr-submit').dispatch('click');
    assert.equal(h.calls.some(row => row.url === '/store'), false);
});

test('validation errors allow correction while uncertain retries keep the same booking token', async () => {
    let attempts = 0;
    const h = harness({responder: (url, options, response) => {
        if (url === '/store' && ++attempts === 1) return response({errors: {date: ['Unavailable']}}, 422);
    }});
    await h.ready(); await h.$('hr-submit').dispatch('click');
    h.payment.value = 'cash'; await h.$('hr-submit').dispatch('click');
    const payloads = h.calls.filter(row => row.url === '/store').map(row => JSON.parse(row.options.body));
    assert.equal(payloads.length, 2); assert.equal(payloads[0].booking_token, payloads[1].booking_token);
    assert.equal(payloads[1].payment_method, 'cash'); assert.deepEqual(h.redirects, ['/order/1']);
});

test('a lost response cannot create a different order with changed checkout details', async () => {
    let attempts = 0;
    const h = harness({responder: url => { if (url === '/store' && ++attempts === 1) throw new Error('Network lost'); }});
    await h.ready(); await h.$('hr-submit').dispatch('click');
    h.payment.value = 'cash'; await h.$('hr-submit').dispatch('click');
    assert.equal(h.calls.filter(row => row.url === '/store').length, 1);
    h.payment.value = 'moyasar'; await h.$('hr-submit').dispatch('click');
    assert.equal(h.calls.filter(row => row.url === '/store').length, 2);
    assert.deepEqual(h.redirects, ['/order/1']);
});

test('new address can be saved without choosing a district and selects the server-resolved district', async () => {
    const h = harness({responder: (url, options, response) => {
        if (url === '/address') return response({data: {id: 11, title: 'Office', address: 'New address', hourly_district_id: 20}}, 201);
    }});
    h.$('hr-district').value = '';
    const form = h.$('hr-address-form'); form.hidden = false;
    form.entries = [['title', 'Office'], ['address', 'New address'], ['latitude', '24.7'], ['longitude', '46.7']];
    form.querySelector = () => ({disabled: false});
    await form.dispatch('submit');
    const payload = JSON.parse(h.calls.find(row => row.url === '/address').options.body);
    assert.equal(payload.district_id, null); assert.equal(payload.hourly_package_id, 1);
    assert.equal(h.$('hr-address').value, 11); assert.equal(h.$('hr-district').value, 20); assert.equal(form.hidden, true);
    await h.ready(); await h.$('hr-submit').dispatch('click');
    assert.equal(JSON.parse(h.calls.find(row => row.url === '/store').options.body).address_id, 11);
});

test('full Arabic address selects its district and clears stale or ambiguous matches', () => {
    const h = harness({districts: [{id: 20, name: 'حي السويدي'}, {id: 21, name: 'الملز'}]});
    h.$('hr-address-text').value = 'الرياض - حي السويدي - منتزه السويدي العام';
    h.$('hr-address-text').dispatch('input'); assert.equal(h.$('hr-district').value, 20);
    h.$('hr-address-text').value = 'عنوان غير معروف';
    h.$('hr-address-text').dispatch('input'); assert.equal(h.$('hr-district').value, '');
    h.$('hr-address-text').value = 'حي السويدي والملز';
    h.$('hr-address-text').dispatch('input');
    // Whole words only: an address mentioning both districts must not guess one.
    h.$('hr-address-text').value = 'حي السويدي - الملز';
    h.$('hr-address-text').dispatch('input'); assert.equal(h.$('hr-district').value, '');
});
