<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function heartbeat() {
        fetch(@json(route('visitor-chat.heartbeat')), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                current_url: window.location.href,
                page_title: document.title,
                referrer: document.referrer,
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || '',
                screen_width: window.screen.width,
                screen_height: window.screen.height
            })
        }).catch(function () {});
    }

    heartbeat();
    window.setInterval(heartbeat, 20000);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) heartbeat();
    });
});
</script>
