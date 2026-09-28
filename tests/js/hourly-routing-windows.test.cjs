const { test } = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const { readFileSync } = require('node:fs');

function setup(windows = []) {
    const nodes = {};
    const node = id => nodes[id] ||= { dataset: {}, events: {}, innerHTML: '', addEventListener(type, handler) { this.events[type] = handler; } };
    node('routing').dataset.windows = '/windows';
    node('rt-data').textContent = JSON.stringify({ drivers: [], unassigned: [], windows, i18n: { windows_empty: 'Empty' },
        query: { date: '2026-09-22', period: 'custom', from: '08:00', to: '12:00', team_id: 4 }, baseUrl: '/routing' });
    const requests = [];
    const location = { href: '' };
    vm.runInNewContext(readFileSync('public/admin/assets/js/hourly-routing.js', 'utf8'), {
        document: { getElementById: node, querySelectorAll: () => [] }, window: {}, location, URLSearchParams,
        bootstrap: { Modal: { getOrCreateInstance: () => ({ show() {} }) } }, alert() {},
        fetch: async (url, options) => {
            const body = JSON.parse(options.body); requests.push(body);
            return { ok: true, json: async () => ({ windows: body.windows, message: 'Saved' }) };
        },
    });
    return { node, requests, location, open: () => node('rt-open-windows').events.click(),
        remove: () => node('rt-windows-list').events.click({ target: { closest: selector => selector === '[data-remove]' ? { dataset: { remove: '0' } } : null } }),
        save: () => node('rt-windows-save').events.click() };
}

test('deleting the last window persists an empty list and clears only its active time filter', async () => {
    const ui = setup([{ name: 'Morning', from: '08:00', to: '12:00' }]);
    ui.open(); ui.remove(); await ui.save(); ui.open();
    assert.deepEqual(ui.requests, [{ windows: [] }]);
    assert.equal(ui.node('rt-windows-list').innerHTML, '<div class="rt-sub">Empty</div>');
    assert.equal(ui.location.href, '/routing?date=2026-09-22&team_id=4');
});

test('opening an empty list does not recreate a deleted window', () => {
    const ui = setup(); ui.open();
    assert.equal(ui.node('rt-windows-list').innerHTML, '<div class="rt-sub">Empty</div>');
    ui.node('rt-window-add').events.click();
    assert.match(ui.node('rt-windows-list').innerHTML, /data-remove="0"/);
});

test('unsaved edits do not mutate saved windows when the dialog reopens', () => {
    const ui = setup([{ name: 'Morning', from: '08:00', to: '12:00' }]); ui.open();
    ui.node('rt-windows-list').events.input({ target: { closest: () => ({ dataset: { i: '0' } }), dataset: { k: 'name' }, value: 'Unsaved' } });
    ui.open();
    assert.match(ui.node('rt-windows-list').innerHTML, /value="Morning"/);
    assert.doesNotMatch(ui.node('rt-windows-list').innerHTML, /Unsaved/);
});
