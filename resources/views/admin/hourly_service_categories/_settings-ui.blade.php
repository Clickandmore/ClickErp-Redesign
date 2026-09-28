<style>
    #tab-settings { scroll-behavior: smooth; }
    #tab-settings .cs-overview { display: flex; align-items: center; gap: 1rem; padding: 1.25rem 1.35rem; margin-bottom: 1rem; border: 1px solid #dfe4ff; border-radius: 18px; background: linear-gradient(135deg, #f5f7ff 0%, #fff 62%, #f0fdfa 100%); }
    #tab-settings .cs-overview-icon { width: 50px; height: 50px; flex: 0 0 50px; display: grid; place-items: center; border-radius: 15px; color: #fff; background: linear-gradient(135deg, #6861ee, #4f46e5); box-shadow: 0 8px 22px rgba(79,70,229,.22); font-size: 1.15rem; }
    #tab-settings .cs-overview-copy { min-width: 0; }
    #tab-settings .cs-overview-copy h4 { margin: 0; color: var(--ht-ink); font-size: 1.18rem; font-weight: 800; }
    #tab-settings .cs-overview-copy p { margin: .25rem 0 0; color: var(--ht-muted); font-size: .84rem; }
    #tab-settings .cs-overview-stats { margin-inline-start: auto; display: flex; gap: .5rem; flex-wrap: wrap; justify-content: flex-end; }
    #tab-settings .cs-status-pill { display: inline-flex; align-items: center; gap: .4rem; padding: .42rem .7rem; border: 1px solid #e2e8f0; border-radius: 999px; background: #fff; color: #64748b; font-size: .76rem; font-weight: 700; white-space: nowrap; }
    #tab-settings .cs-status-pill.is-custom { border-color: #c7d2fe; color: #4338ca; background: #eef2ff; }

    #tab-settings .cs-layout { display: grid; grid-template-columns: 220px minmax(0,1fr); gap: 1rem; align-items: start; }
    #tab-settings .cs-sidebar { position: sticky; top: 92px; padding: .8rem; border: 1px solid var(--ht-line); border-radius: 16px; background: #fff; box-shadow: 0 8px 24px rgba(15,23,42,.05); }
    #tab-settings .cs-sidebar > p { margin: .15rem .55rem .55rem; color: #94a3b8; font-size: .7rem; font-weight: 800; }
    #tab-settings .cs-sidebar nav { display: grid; gap: .2rem; }
    #tab-settings .cs-sidebar a { display: flex; align-items: center; gap: .65rem; padding: .58rem .65rem; border-radius: 10px; color: #64748b; text-decoration: none; font-size: .8rem; font-weight: 650; transition: background .15s,color .15s; }
    #tab-settings .cs-sidebar a i { width: 18px; color: #94a3b8; text-align: center; }
    #tab-settings .cs-sidebar a:hover, #tab-settings .cs-sidebar a.is-active { color: #4338ca; background: #eef2ff; }
    #tab-settings .cs-sidebar a:hover i, #tab-settings .cs-sidebar a.is-active i { color: #6861ee; }
    #tab-settings .cs-content { min-width: 0; }
    #tab-settings .ht-card { border: 1px solid #e8ecf2; box-shadow: 0 8px 24px rgba(15,23,42,.045); scroll-margin-top: 95px; }
    #tab-settings .ht-card > .ht-head { padding: .9rem 1rem; }
    #tab-settings .ht-card > .ht-head .ht-head-icon { width: 38px; height: 38px; flex-basis: 38px; border-radius: 11px; background: #eef2ff; color: #4f46e5; }
    #tab-settings .ht-body { padding: .25rem 1rem .4rem; }
    #tab-settings .sc-presentation .ht-body { padding: 1rem; }
    #tab-settings .cs-inheritance-note { display: flex; gap: .7rem; align-items: flex-start; padding: .8rem 1rem; margin-bottom: 1rem; border: 1px solid #bae6fd; border-radius: 13px; color: #075985; background: #f0f9ff; }
    #tab-settings .cs-inheritance-note > i { margin-top: .15rem; }
    #tab-settings .cs-inheritance-note strong, #tab-settings .cs-inheritance-note span { display: block; }
    #tab-settings .cs-inheritance-note strong { margin-bottom: .12rem; font-size: .82rem; }
    #tab-settings .cs-inheritance-note span { font-size: .78rem; line-height: 1.65; }
    #tab-settings .cs-sections-grid { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 1rem; }
    #tab-settings .cs-section-card { margin-bottom: 0; }
    #tab-settings .cs-section-card:last-child:nth-child(odd) { grid-column: 1 / -1; }
    #tab-settings .cs-grid { display: block; }
    #tab-settings .cs-grid > .cs-field { display: grid; grid-template-columns: minmax(150px,1fr) minmax(170px,46%); gap: .35rem 1rem; align-items: center; min-height: 66px; padding: .75rem 0; border-bottom: 1px solid #edf0f7; }
    #tab-settings .cs-grid > .cs-field:last-child { border-bottom: 0; }
    #tab-settings .cs-field > .form-label { margin: 0; color: var(--ht-ink); font-size: .84rem; font-weight: 650; line-height: 1.45; }
    #tab-settings .cs-field > .cs-help { grid-column: 1; margin: -.12rem 0 0; color: var(--ht-muted); font-size: .72rem; line-height: 1.5; }
    #tab-settings .cs-field .form-control, #tab-settings .cs-field .form-select { min-height: 40px; border-radius: 9px; font-size: .82rem; }
    #tab-settings .cs-field .input-group-text { max-width: 145px; overflow: hidden; text-overflow: ellipsis; background: #f8fafc; color: #64748b; }
    #tab-settings .cs-field .row { grid-column: 2; }
    #tab-settings .cs-field .row .form-label, #tab-settings .cs-field .row label { font-size: .7rem; }

    .cs-switch-control { display: flex; align-items: center; justify-content: space-between; gap: .6rem; flex-wrap: wrap; }
    .cs-switch-control .cs-inherit { display: inline-flex; align-items: center; gap: .25rem; margin: 0; padding: .28rem .48rem; border-radius: 7px; background: #f8fafc; color: var(--ht-muted); font-size: .68rem; cursor: pointer; }
    .cs-switch-control .cs-inherit:has(input:checked) { color: #475569; background: #eef2f7; }
    .cs-switch-control .cs-inherit input { margin: 0; }

    #tab-settings #cs-steppers, #tab-settings #cs-working-days { margin-top: 1rem; }
    #tab-settings .cs-stepper { display: grid; grid-template-columns: 28px minmax(115px,.8fr) minmax(150px,1.15fr) minmax(190px,1.25fr) 72px 72px minmax(100px,.8fr) 36px; gap: .65rem; align-items: end; padding: .8rem; margin-bottom: .65rem; border: 1px solid var(--ht-line); border-radius: 13px; background: #fff; }
    #tab-settings .cs-stepper.is-dragging { opacity: .5; }
    #tab-settings .cs-stepper .cs-handle { align-self: center; color: #94a3b8; cursor: grab; text-align: center; }
    #tab-settings .cs-stepper-field > span { display: block; margin-bottom: .28rem; color: var(--ht-muted); font-size: .68rem; font-weight: 650; }
    #tab-settings .cs-stepper-channels { display: flex; gap: .45rem .7rem; flex-wrap: wrap; min-height: 31px; align-items: center; }
    #tab-settings .cs-stepper-check { min-height: 31px; display: flex; align-items: center; }

    #tab-settings .cs-days { display: grid; grid-template-columns: repeat(auto-fit,minmax(205px,1fr)); gap: .7rem; padding: .75rem 0; }
    #tab-settings .cs-day-card { padding: .8rem; border: 1px solid var(--ht-line); border-radius: 13px; background: #fff; transition: border-color .15s,background .15s; }
    #tab-settings .cs-day-card[data-day-on="true"] { border-color: #bbf7d0; background: #f0fdf4; }
    #tab-settings .cs-day-card[data-day-on="false"] { border-color: #fecaca; background: #fff7f7; }
    #tab-settings .cs-day-head { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .65rem; }
    #tab-settings .cs-day-head strong { color: var(--ht-ink); font-size: .88rem; }
    #tab-settings .cs-day-head span, #tab-settings .cs-day-card > label { color: var(--ht-muted); font-size: .7rem; }
    #tab-settings .cs-day-toggle { min-height: 38px; margin-bottom: .65rem; }
    #tab-settings .cs-day-card > label { display: block; margin: 0; }
    #tab-settings .cs-day-card > label input { margin-top: .3rem; }
    #tab-settings .cs-save-bar .ht-foot { justify-content: space-between; }
    #tab-settings .cs-save-bar .ht-foot strong, #tab-settings .cs-save-bar .ht-foot small { display: block; }
    #tab-settings .cs-save-bar .ht-foot strong { color: var(--ht-ink); font-size: .86rem; }
    #tab-settings .cs-save-bar .ht-foot small { margin-top: .15rem; color: var(--ht-muted); font-size: .72rem; }
    #tab-settings .cs-save-bar.is-dirty .ht-foot { background: #fffbeb; }

    .sc-icon-library { display: grid; grid-template-columns: repeat(auto-fill,minmax(55px,1fr)); gap: .5rem; max-height: 220px; overflow: auto; padding: .5rem; border: 1px solid var(--ht-line); border-radius: 10px; }
    .sc-icon-library button { min-height: 48px; font-size: 1.2rem; }
    .sc-icon-library [hidden] { display: none; }

    @media(max-width:1199px) {
        #tab-settings .cs-layout { grid-template-columns: 1fr; }
        #tab-settings .cs-sidebar { position: static; overflow-x: auto; padding: .45rem; }
        #tab-settings .cs-sidebar > p { display: none; }
        #tab-settings .cs-sidebar nav { display: flex; width: max-content; }
        #tab-settings .cs-sidebar a { white-space: nowrap; }
        #tab-settings .cs-stepper { grid-template-columns: 28px repeat(3,minmax(120px,1fr)); }
    }
    @media(max-width:767px) {
        #tab-settings .cs-overview { align-items: flex-start; flex-wrap: wrap; padding: 1rem; }
        #tab-settings .cs-overview-stats { width: 100%; margin-inline-start: 0; justify-content: flex-start; }
        #tab-settings .cs-sections-grid { grid-template-columns: 1fr; }
        #tab-settings .cs-section-card:last-child:nth-child(odd) { grid-column: auto; }
        #tab-settings .cs-grid > .cs-field { grid-template-columns: 1fr; gap: .45rem; }
        #tab-settings .cs-field > .cs-help, #tab-settings .cs-field .row { grid-column: 1; }
        #tab-settings .cs-stepper { grid-template-columns: 26px 1fr; align-items: center; }
        #tab-settings .cs-stepper > :not(.cs-handle) { grid-column: 2; }
        #tab-settings .cs-stepper > [data-stepper-remove] { grid-column: 1 / -1; width: 100%; }
        #tab-settings .cs-save-bar .ht-foot { align-items: stretch; }
        #tab-settings .cs-save-bar .btn { width: 100%; }
    }
</style>
@push('category-js')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('#tab-settings select').forEach(select => {
        const values=[...select.options].map(o=>o.value);
        if(values.length!==3 || !values.includes('inherit') || !values.includes('1') || !values.includes('0')) return;
        const wrapper=document.createElement('div');wrapper.className='cs-switch-control';
        const toggle=document.createElement('button');toggle.type='button';toggle.className='ht-toggle';toggle.setAttribute('role','switch');toggle.disabled=select.disabled;toggle.innerHTML='<span></span>';
        const label=document.querySelector('label[for="'+select.id+'"]');
        toggle.setAttribute('aria-label',select.getAttribute('aria-label') || label?.textContent || select.closest('.cs-field')?.querySelector('.form-label')?.textContent || @json(__('hourly.status')));
        const inheritLabel=document.createElement('label');inheritLabel.className='cs-inherit';
        const inherit=document.createElement('input');inherit.type='checkbox';inherit.disabled=select.disabled;
        inheritLabel.append(inherit,document.createTextNode(' '+select.options[0].text));
        wrapper.append(toggle,inheritLabel);select.insertAdjacentElement('afterend',wrapper);select.hidden=true;select.style.display='none';
        const sync=()=>{
            inherit.checked=select.value==='inherit';
            const on=select.value==='1' || (inherit.checked && select.dataset.inheritedOn==='1');
            toggle.setAttribute('aria-checked',String(on));toggle.querySelector('span').textContent=on?'✓':'';
            const card=select.closest('.cs-day-card');if(card) card.dataset.dayOn=String(on);
        };
        toggle.addEventListener('click',()=>{select.value=toggle.getAttribute('aria-checked')==='true'?'0':'1';select.dispatchEvent(new Event('change',{bubbles:true}));});
        inherit.addEventListener('change',()=>{select.value=inherit.checked?'inherit':(toggle.getAttribute('aria-checked')==='true'?'1':'0');select.dispatchEvent(new Event('change',{bubbles:true}));});
        select.addEventListener('change',sync);sync();
    });

    const links=[...document.querySelectorAll('#tab-settings .cs-sidebar a')];
    const targets=links.map(link=>document.querySelector(link.getAttribute('href'))).filter(Boolean);
    links.forEach(link=>link.addEventListener('click',event=>{
        const target=document.querySelector(link.getAttribute('href'));
        if(!target) return;
        event.preventDefault();
        target.scrollIntoView({behavior:'smooth',block:'start'});
        links.forEach(item=>item.classList.toggle('is-active',item===link));
    }));
    if ('IntersectionObserver' in window) {
        const observer=new IntersectionObserver(entries=>entries.forEach(entry=>{
            if(!entry.isIntersecting) return;
            links.forEach(link=>link.classList.toggle('is-active',link.getAttribute('href')==='#'+entry.target.id));
        }),{rootMargin:'-20% 0px -70% 0px'});
        targets.forEach(target=>observer.observe(target));
    }

    const form=document.querySelector('#tab-settings [data-settings-form]');
    const saveBar=form?.querySelector('.cs-save-bar');
    form?.addEventListener('input',()=>saveBar?.classList.add('is-dirty'));
    form?.addEventListener('change',()=>saveBar?.classList.add('is-dirty'));
});
</script>
@endpush
