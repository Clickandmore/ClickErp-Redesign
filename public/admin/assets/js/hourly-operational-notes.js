(() => {
    const fallback=document.currentScript?.dataset.error;
    const modal=document.getElementById('operational-note-modal'),form=document.getElementById('operational-note-form');
    if(!modal || !form)return;
    document.body.appendChild(modal);
    const visit=form.elements.visit_id,error=document.getElementById('on-error'),save=document.getElementById('on-save');let busy=false;
    function audience(){const selected=form.querySelector('[name=audience]:checked') || form.querySelector('[name=audience][value=admin]');if(!selected)return;selected.checked=true;document.getElementById('on-hint').textContent=selected.dataset.hint;visit.disabled=selected.value==='address';if(visit.disabled)visit.value='';}
    function noteToken(){
        if(typeof crypto.randomUUID==='function')return crypto.randomUUID();
        const bytes=crypto.getRandomValues(new Uint8Array(16));bytes[6]=(bytes[6]&15)|64;bytes[8]=(bytes[8]&63)|128;
        const hex=Array.from(bytes,b=>b.toString(16).padStart(2,'0')).join('');
        return `${hex.slice(0,8)}-${hex.slice(8,12)}-${hex.slice(12,16)}-${hex.slice(16,20)}-${hex.slice(20)}`;
    }
    form.querySelectorAll('[name=audience]').forEach(input=>input.addEventListener('change',audience));
    document.querySelectorAll('[data-operational-note]').forEach(button=>button.addEventListener('click',()=>{
        if(busy)return;form.reset();error.hidden=true;visit.value=button.dataset.operationalNote;audience();
        form.elements.note_token.value=noteToken();bootstrap.Modal.getOrCreateInstance(modal).show();
    }));
    modal.addEventListener('hide.bs.modal',e=>{if(busy)e.preventDefault();});
    modal.addEventListener('shown.bs.modal',()=>form.elements.note.focus());
    form.addEventListener('submit',async e=>{
        e.preventDefault();if(busy)return;form.elements.note.value=form.elements.note.value.trim();if(!form.reportValidity())return;
        busy=true;save.disabled=true;error.hidden=true;
        try{const response=await fetch(form.action,{method:'POST',body:new FormData(form),headers:{Accept:'application/json'}});const data=await response.json();if(!response.ok)throw new Error(Object.values(data.errors||{}).flat().join('\n')||data.message||fallback);location.reload();}
        catch(ex){error.textContent=ex.message||fallback;error.hidden=false;busy=false;save.disabled=false;}
    });
})();
