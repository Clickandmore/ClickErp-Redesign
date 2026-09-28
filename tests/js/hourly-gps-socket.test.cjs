const { test } = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const { readFileSync } = require('node:fs');

test('private subscription authorizes with CSRF, coalesces bursts, and reports disconnects', () => {
    const channelEvents = {}, connectionEvents = {}, timers = new Map(), states = [];
    let options, changed = 0, next = 0, closed = false;
    function Pusher(key, opts) {
        assert.equal(key, 'public-key'); options = opts;
        this.connection = { bind: (event, callback) => { connectionEvents[event] = callback; } };
        this.subscribe = name => { assert.equal(name, 'private-hourly-gps'); return { bind: (event, callback) => { channelEvents[event] = callback; } }; };
        this.disconnect = () => { closed = true; };
    }
    const context = { window: { Pusher }, Pusher, document: { querySelector: () => ({ content: 'csrf' }) },
        setTimeout: fn => { timers.set(++next, fn); return next; }, clearTimeout: id => timers.delete(id) };
    vm.runInNewContext(readFileSync('public/admin/assets/js/hourly-gps-socket.js', 'utf8'), context);
    const socket = context.window.HourlyGpsSocket({ key: 'public-key', cluster: 'eu', tls: true,
        authEndpoint: '/admin/hourly/gps-tracking/socket-auth', channel: 'private-hourly-gps', event: 'locations.changed' },
        () => changed++, value => states.push(value));
    assert.equal(options.channelAuthorization.headers['X-CSRF-TOKEN'], 'csrf');
    channelEvents['locations.changed'](); assert.equal(timers.size, 0);
    channelEvents['pusher:subscription_succeeded']();
    for (let i = 0; i < 100; i++) channelEvents['locations.changed']();
    assert.equal(timers.size, 1);
    const callback = [...timers.values()][0]; timers.clear(); callback(); assert.equal(changed, 1);
    connectionEvents.state_change({ current: 'unavailable' });
    assert.deepEqual(states, [true, false]);
    channelEvents['pusher:subscription_succeeded']();
    socket.disconnect(); assert.equal(timers.size, 0); assert.equal(closed, true);
});
