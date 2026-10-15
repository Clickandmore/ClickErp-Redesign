(() => {
    const modal=document.getElementById('visit-schedule-modal'),form=document.getElementById('visit-schedule-form');if(!modal||!form)return;
    document.body.appendChild(modal);
    const cfg=window.hourlyScheduleConfig,$=id=>document.getElementById(id),pad=n=>String(n).padStart(2,'0');
    const key=d=>`${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
    const parse=value=>{const [y,m,d]=value.split('-').map(Number);return new Date(y,m-1,d,12);};
    const locale=cfg.locale==='ar'?'ar-SA-u-ca-gregory-nu-latn':'en-GB';
    let selected,month,busy=false;
    function preview(){form.elements.starts_at.value=`${selected}T${$('vs-hour').value}:${$('vs-minute').value}`;$('vs-preview').textContent=`${$('vs-hour').value}:${$('vs-minute').value} ${selected.split('-').reverse().join('/')}`;}
    function render(){
        $('vs-month').textContent=new Intl.DateTimeFormat(locale,{month:'long',year:'numeric'}).format(month);$('vs-days').replaceChildren();
        const first=new Date(month.getFullYear(),month.getMonth(),1,12);first.setDate(1-first.getDay());
        for(let i=0;i<42;i++){
            const date=new Date(first);date.setDate(first.getDate()+i);const value=key(date),button=document.createElement('button');button.type='button';button.textContent=date.getDate();button.dataset.date=value;
            button.classList.toggle('outside',date.getMonth()!==month.getMonth());button.classList.toggle('selected',value===selected);button.setAttribute('aria-pressed',String(value===selected));button.setAttribute('aria-label',new Intl.DateTimeFormat(locale,{dateStyle:'full'}).format(date));button.disabled=value<cfg.min || (cfg.max && value>cfg.max);
            button.onclick=()=>{if(busy)return;selected=value;month=new Date(date.getFullYear(),date.getMonth(),1,12);render();preview();};$('vs-days').appendChild(button);
        }
    }
    for(let i=0;i<7;i++){const day=document.createElement('small');day.textContent=new Intl.DateTimeFormat(locale,{weekday:'short'}).format(new Date(2026,0,4+i,12));$('vs-weekdays').appendChild(day);}
    $('vs-prev').onclick=()=>{if(!busy){month.setMonth(month.getMonth()-1);render();}};$('vs-next').onclick=()=>{if(!busy){month.setMonth(month.getMonth()+1);render();}};
    $('vs-hour').onchange=preview;$('vs-minute').onchange=preview;
    window.openHourlySchedule=id=>{if(busy||!cfg.visits[id])return;form.reset();$('vs-error').hidden=true;form.setAttribute('action',cfg.url.replace('VISIT_ID',encodeURIComponent(id)));const original=cfg.visits[id];form.elements.expected_start_at.value=original;selected=original.slice(0,10);month=parse(selected);month.setDate(1);$('vs-hour').value=original.slice(11,13);$('vs-minute').value=original.slice(14,16);render();preview();bootstrap.Modal.getOrCreateInstance(modal).show();};
    modal.addEventListener('hide.bs.modal',e=>{if(busy)e.preventDefault();});
    form.onsubmit=async e=>{e.preventDefault();if(busy)return;busy=true;$('vs-save').disabled=true;$('vs-error').hidden=true;
        try{const response=await fetch(form.getAttribute('action'),{method:'POST',body:new FormData(form),headers:{Accept:'application/json'}});const result=await response.json();if(!response.ok)throw new Error(Object.values(result.errors||{}).flat().join('\n')||result.message||cfg.error);location.reload();}
        catch(error){busy=false;$('vs-save').disabled=false;$('vs-error').textContent=error.message||cfg.error;$('vs-error').hidden=false;}
    };
})();
