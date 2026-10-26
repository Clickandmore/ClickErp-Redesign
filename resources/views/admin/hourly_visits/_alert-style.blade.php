{{-- أنماط تنبيهات التشغيل أعلى صفحات الزيارات (الفائتة، والمتأخرة في العودة للإيواء). --}}
@once
@push('css')
<style>
    .va { --va-tone: #e5484d; --va-tint: #fdf2f2; --va-ring: #f7cdcf; --va-ink: #9b1c20; margin: 0 .75rem 1rem; background: #fff; border: 1px solid var(--va-ring); border-inline-start: 5px solid var(--va-tone); border-radius: 16px; box-shadow: 0 8px 24px rgba(20, 80, 127, .06); overflow: hidden; direction: rtl; }
    .va.is-warn { --va-tone: #e08a17; --va-tint: #fff8ec; --va-ring: #f7ddb2; --va-ink: #8a5806; }
    .va-head { display: flex; align-items: flex-start; gap: .85rem; padding: .95rem 1.15rem; background: linear-gradient(180deg, var(--va-tint), #fff); }
    .va-icon { width: 42px; height: 42px; flex: 0 0 42px; border-radius: 12px; display: grid; place-items: center; background: var(--va-tone); color: #fff; font-size: 1.05rem; box-shadow: 0 6px 16px color-mix(in srgb, var(--va-tone) 35%, transparent); }
    .va-text { flex: 1; min-width: 0; }
    .va-text h6 { margin: 0; font-size: .98rem; font-weight: 700; color: var(--va-ink); display: flex; align-items: center; gap: .45rem; flex-wrap: wrap; }
    .va-count { background: var(--va-tone); color: #fff; border-radius: 99px; padding: .05rem .6rem; font-size: .8rem; font-variant-numeric: tabular-nums; }
    .va-text p { margin: .25rem 0 0; font-size: .82rem; color: #5b6878; }
    .va-all { flex: 0 0 auto; align-self: center; display: inline-flex; align-items: center; gap: .35rem; border: 1px solid var(--va-ring); background: #fff; color: var(--va-ink); border-radius: 10px; padding: .4rem .8rem; font-size: .8rem; font-weight: 700; text-decoration: none; white-space: nowrap; }
    .va-all:hover { background: var(--va-tint); color: var(--va-ink); }
    .va-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: .55rem; padding: .85rem 1.15rem 1rem; border-top: 1px solid var(--va-ring); }
    .va-item { display: flex; align-items: center; gap: .65rem; padding: .55rem .7rem; border: 1px solid #e6ecf3; border-radius: 12px; background: #fff; }
    .va-item:hover { border-color: var(--va-ring); background: #fffdfd; }
    .va-item[hidden] { display: none; }
    .va-item-main { flex: 1; min-width: 0; }
    .va-item-main b { display: block; font-size: .84rem; color: #16212e; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .va-item-main span { display: block; font-size: .74rem; color: #6b7a8c; font-variant-numeric: tabular-nums; }
    .va-item-main .va-ago { color: var(--va-ink); font-weight: 600; }
    .va-item-main .badge { margin-top: .2rem; font-size: .68rem; white-space: normal; text-align: start; }
    .va-go { flex: 0 0 auto; display: inline-flex; align-items: center; gap: .3rem; background: var(--va-tone); color: #fff; border-radius: 9px; padding: .35rem .65rem; font-size: .76rem; font-weight: 700; text-decoration: none; white-space: nowrap; }
    .va-go:hover { color: #fff; filter: brightness(.94); }
    .va-more { grid-column: 1 / -1; justify-self: start; border: 0; background: none; color: var(--va-ink); font-size: .8rem; font-weight: 700; padding: .2rem 0; }
    .va-more:hover { text-decoration: underline; }
    @media (max-width: 575px) { .va-head { flex-wrap: wrap; } .va-all { margin-inline-start: auto; } }
</style>
@endpush
@endonce
