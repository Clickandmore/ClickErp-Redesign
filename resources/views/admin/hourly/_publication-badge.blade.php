@once
<style>
.hourly-publication{position:relative;display:inline-flex;align-items:center;gap:5px;padding:5px 9px;border-radius:20px;font-size:11px;font-weight:600;line-height:1.5;cursor:help;background:#e8f6ed;color:#166534;white-space:nowrap}
.hourly-publication.is-hidden{background:#fff0ed;color:#9f3020}
.hourly-publication:focus-visible{outline:2px solid #2563eb;outline-offset:2px}
/* fixed حتى لا يقصّه overflow الجداول ولا تغطيه الصفوف التالية؛ موضعه يُحسب بالسكربت أدناه */
.hourly-publication-tip{display:none;position:fixed;z-index:2000;width:300px;max-width:calc(100vw - 24px);max-height:60vh;overflow:auto;background:#172436;color:white;padding:12px 15px;border-radius:10px;box-shadow:0 10px 30px rgba(15,23,42,.3);white-space:normal;font-size:12px;line-height:1.8;text-align:start;font-weight:400;pointer-events:none}
.hourly-publication-tip.is-open{display:block}
.ht-card.has-publication{overflow:visible}.ht-card.has-publication>.ht-head{border-radius:18px 18px 0 0}
.hourly-publication-tip span{display:block}
.hourly-publication-tip span+span{margin-top:4px;padding-top:4px;border-top:1px solid rgba(255,255,255,.12)}
</style>
<script>
(() => {
    // يُنقل التلميح إلى body أثناء ظهوره ثم يعود إلى الشارة، فلا يتأثر بأي transform أو overflow في الجدول
    const gap = 8, edge = 12;
    let open = null;
    const close = () => {
        if (!open) return;
        open.tip.classList.remove('is-open');
        open.badge.appendChild(open.tip);
        open = null;
    };
    const place = badge => {
        if (open?.badge === badge) return;
        close();
        const tip = badge.querySelector('.hourly-publication-tip');
        if (!tip || !tip.children.length) return;
        tip.setAttribute('dir', getComputedStyle(badge).direction);
        document.body.appendChild(tip);
        tip.classList.add('is-open');
        open = { badge, tip };
        const r = badge.getBoundingClientRect(), t = tip.getBoundingClientRect();
        let left = tip.dir === 'rtl' ? r.right - t.width : r.left;
        left = Math.min(Math.max(left, edge), window.innerWidth - t.width - edge);
        // تحت الشارة إن اتسعت المساحة، وإلا فوقها
        const top = r.bottom + gap + t.height <= window.innerHeight - edge ? r.bottom + gap : Math.max(edge, r.top - gap - t.height);
        tip.style.left = left + 'px';
        tip.style.top = top + 'px';
    };
    const badgeOf = e => e.target.closest && e.target.closest('.hourly-publication');
    document.addEventListener('mouseover', e => { const b = badgeOf(e); if (b) place(b); });
    document.addEventListener('mouseout', e => { const b = badgeOf(e); if (b && !b.contains(e.relatedTarget) && document.activeElement !== b) close(); });
    document.addEventListener('focusin', e => { const b = badgeOf(e); if (b) place(b); });
    document.addEventListener('focusout', e => { if (badgeOf(e)) close(); });
    window.addEventListener('scroll', close, true);
})();
</script>
@endonce
<span class="hourly-publication {{ $report['visible'] ? '' : 'is-hidden' }}" tabindex="0" aria-describedby="{{ $badgeId }}" data-publication-key="{{ $badgeId }}" data-website-visible="{{ $report['visible'] ? '1' : '0' }}">
    <i class="fa {{ $report['visible'] ? 'fa-eye' : 'fa-eye-slash' }}" aria-hidden="true"></i>
    <span data-publication-label>{{ $report['visible'] ? 'يظهر في الموقع' : 'لا يظهر في الموقع' }}</span>
    <span class="hourly-publication-tip" role="tooltip" id="{{ $badgeId }}">@foreach($report['reasons'] as $reason)<span>{{ $reason }}</span>@endforeach</span>
</span>
