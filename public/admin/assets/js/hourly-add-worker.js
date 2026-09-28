(() => {
    const modal=document.getElementById('add-worker-modal'),form=document.getElementById('add-worker-form');if(!modal||!form)return;
    document.body.appendChild(modal);
    const cfg=window.hourlyAddWorkerConfig,l=cfg.labels,$=id=>document.getElementById(id),worker=$('aw-worker');
    let data=null,busy=false,generation=0;
    const select2=window.jQuery?.fn?.select2;
    if(select2){window.jQuery(worker).select2({dropdownParent:window.jQuery(modal),width:'100%',dir:cfg.rtl?'rtl':'ltr',placeholder:l.choose});$('aw-search').hidden=true;}
    function filter(){
        const chosen=worker.value,term=$('aw-search').value.trim().toLowerCase(),team=$('aw-team').value,category=$('aw-category').value;
        const rows=(data?.workers||[]).filter(w=>(!team||w.teams.some(t=>String(t.id)===team))&&(!category||!w.category_ids||w.category_ids.map(String).includes(category))&&[w.name,...w.teams.map(t=>t.name)].join(' ').toLowerCase().includes(term));
        worker.replaceChildren(new Option(l.choose,''));rows.forEach(w=>worker.add(new Option([w.name,...w.teams.map(t=>t.name)].join(' — '),w.id)));
        worker.value=rows.some(w=>String(w.id)===chosen)?chosen:'';
        if(select2)window.jQuery(worker).trigger('change');
        $('aw-count').textContent=rows.length;$('aw-status').textContent=rows.length?'':l.empty;$('aw-save').disabled=busy||!rows.length;
    }
    function preview(){$('aw-new').textContent=data?(Math.round((data.price+Number($('aw-fee').value))*100)/100).toFixed(2):'—';}
    $('aw-filter-toggle').onclick=()=>{const hide=!$('aw-filters').hidden;$('aw-filters').hidden=hide;$('aw-filter-toggle').setAttribute('aria-expanded',String(!hide));};
    ['aw-team','aw-category'].forEach(id=>$(id).onchange=filter);$('aw-search').oninput=filter;$('aw-fee').oninput=preview;
    window.openHourlyAddWorker=async id=>{
        if(busy)return;const current=++generation;data=null;form.reset();$('aw-error').hidden=true;$('aw-save').disabled=true;$('aw-count').textContent='0';$('aw-current').textContent='—';$('aw-new').textContent='—';
        worker.replaceChildren(new Option(l.choose,''));if(select2)window.jQuery(worker).trigger('change');
        $('aw-filters').hidden=true;$('aw-filter-toggle').setAttribute('aria-expanded','false');$('aw-visit').textContent=`— #${id}`;$('aw-status').textContent=l.loading;
        form.elements['visit_ids[]'].value=id;bootstrap.Modal.getOrCreateInstance(modal).show();
        try{
            const response=await fetch(cfg.url.replace('VISIT_ID',encodeURIComponent(id)),{headers:{Accept:'application/json'}});const result=await response.json();if(current!==generation)return;if(!response.ok)throw new Error(result.message||l.error);data=result;
            $('aw-visit').textContent=`— #${id} (${data.date||'—'})`;
            const teams=new Map();data.workers.forEach(w=>w.teams.forEach(t=>teams.set(t.id,t.name)));
            $('aw-team').replaceChildren(new Option(l.all,''));teams.forEach((name,key)=>$('aw-team').add(new Option(name,key)));
            $('aw-category').replaceChildren(new Option(l.all,''));data.categories.forEach(c=>$('aw-category').add(new Option(c.name,c.id)));
            if(teams.has(data.team_id))$('aw-team').value=String(data.team_id);$('aw-category').value=String(data.category_id||'');
            form.elements.expected_price.value=data.price;form.elements.expected_workers.value=data.workers_count;
            $('aw-fee').value=data.default_fee.toFixed(2);$('aw-fee').max=9999999-data.price;$('aw-current').textContent=data.price.toFixed(2);preview();filter();
        }catch(error){if(current!==generation)return;$('aw-status').textContent='';$('aw-error').textContent=error.message||l.error;$('aw-error').hidden=false;}
    };
    modal.addEventListener('hide.bs.modal',e=>{if(busy)e.preventDefault();else generation++;});
    form.onsubmit=async e=>{
        e.preventDefault();if(busy||!data||!form.reportValidity())return;busy=true;$('aw-save').disabled=true;$('aw-error').hidden=true;
        try{const response=await fetch(form.getAttribute('action'),{method:'POST',body:new FormData(form),headers:{Accept:'application/json'}});const result=await response.json();if(!response.ok)throw new Error(Object.values(result.errors||{}).flat().join('\n')||result.message||l.error);location.reload();}
        catch(error){$('aw-error').textContent=error.message||l.error;$('aw-error').hidden=false;busy=false;$('aw-save').disabled=false;}
    };
})();
