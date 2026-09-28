/* Private GPS invalidations; the data endpoint remains the authority for filters and permissions. */
window.HourlyGpsSocket = function (config, changed, state) {
    if (!config || !window.Pusher) return null;
    const options = { cluster: config.cluster, forceTLS: config.tls, enabledTransports: ['ws', 'wss'],
        channelAuthorization: { endpoint: config.authEndpoint, transport: 'ajax',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } } };
    if (config.host) Object.assign(options, { wsHost: config.host, wsPort: config.port, wssPort: config.port, disableStats: true });
    const client = new Pusher(config.key, options);
    let subscribed = false, pending;
    const notify = () => {
        // Coalesce a burst without postponing it indefinitely on busy fleets.
        if (pending) return;
        pending = setTimeout(() => { pending = null; changed(); }, 250);
    };
    const offline = () => { subscribed = false; clearTimeout(pending); pending = null; state(false); };
    const channel = client.subscribe(config.channel);
    channel.bind('pusher:subscription_succeeded', () => { subscribed = true; state(true); notify(); });
    channel.bind('pusher:subscription_error', offline);
    channel.bind(config.event, () => { if (subscribed) notify(); });
    client.connection.bind('state_change', event => { if (event.current !== 'connected') offline(); });
    client.connection.bind('error', offline);
    return { disconnect() { offline(); client.disconnect(); } };
};
