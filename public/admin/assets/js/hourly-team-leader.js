(() => {
    const modal=document.getElementById('team-leader-modal'),form=document.getElementById('team-leader-form');if(!modal||!form)return;
    document.body.appendChild(modal);
    const cfg=window.hourlyTeamLeaderConfig,l=cfg.labels,$=id=>document.getElementById(id);
    let visit,page=1,last=1,sort='name',direction='asc',generation=0,busy=false,timer;
    async function load(initial=false){
        const current=++generation;$('tl-save').disabled=true;$('tl-prev').disabled=true;$('tl-next').disabled=true;$('tl-rows').replaceChildren();$('tl-status').textContent=l.loading;$('tl-error').hidden=true;
        const params=new URLSearchParams({all_teams:$('tl-all').checked?'1':'0',page,per_page:$('tl-size').value,search:$('tl-search').value,sort,direction});
        try{
            const response=await fetch(cfg.url.replace('VISIT_ID',encodeURIComponent(visit))+'?'+params,{headers:{Accept:'application/json'}});const result=await response.json();if(current!==generation)return;if(!response.ok)throw new Error(result.message||l.error);
            const rows=result.leaders;last=rows.last_page;$('tl-count').textContent=rows.total;
            if(initial)form.elements.leader_id.value=result.selected_id||'';
            if(!rows.data.some(r=>String(r.id)===form.elements.leader_id.value))form.elements.leader_id.value='';
            rows.data.forEach(leader=>{
                const tr=document.createElement('tr');[leader.id,leader.name].forEach(value=>{const td=document.createElement('td');td.textContent=value;tr.appendChild(td);});
                const td=document.createElement('td'),radio=document.createElement('input');radio.type='radio';radio.name='leader_choice';radio.value=leader.id;radio.checked=String(leader.id)===form.elements.leader_id.value;radio.setAttribute('aria-label',l.action+' '+leader.name);
                radio.onchange=()=>{form.elements.leader_id.value=leader.id;$('tl-save').disabled=busy;};td.appendChild(radio);tr.appendChild(td);$('tl-rows').appendChild(tr);
            });
            if(!rows.data.length){const tr=document.createElement('tr'),td=document.createElement('td');td.colSpan=3;td.className='text-center text-muted';td.textContent=l.empty;tr.appendChild(td);$('tl-rows').appendChild(tr);}
            $('tl-status').textContent=`${rows.from||0}–${rows.to||0} / ${rows.total} · ${l.results}`;$('tl-prev').disabled=page<=1;$('tl-next').disabled=page>=last;$('tl-save').disabled=busy||!form.elements.leader_id.value;
        }catch(error){if(current!==generation)return;$('tl-count').textContent='0';$('tl-status').textContent='';$('tl-error').textContent=error.message||l.error;$('tl-error').hidden=false;}
    }
    function reset(){if(busy)return;form.elements.leader_id.value='';page=1;load();}
    $('tl-all').onchange=reset;$('tl-size').onchange=reset;
    $('tl-search').oninput=()=>{clearTimeout(timer);generation++;form.elements.leader_id.value='';$('tl-save').disabled=true;$('tl-rows').replaceChildren();timer=setTimeout(reset,250);};
    $('tl-prev').onclick=()=>{if(!busy&&page>1){page--;load();}};$('tl-next').onclick=()=>{if(!busy&&page<last){page++;load();}};
    modal.querySelectorAll('[data-tl-sort]').forEach(button=>button.onclick=()=>{if(busy)return;direction=sort===button.dataset.tlSort&&direction==='asc'?'desc':'asc';sort=button.dataset.tlSort;reset();});
    window.openHourlyTeamLeader=id=>{if(busy)return;clearTimeout(timer);form.reset();visit=id;page=1;sort='name';direction='asc';form.elements['visit_ids[]'].value=id;form.elements.leader_id.value='';bootstrap.Modal.getOrCreateInstance(modal).show();load(true);};
    modal.addEventListener('hide.bs.modal',e=>{if(busy)e.preventDefault();else{clearTimeout(timer);generation++;}});
    form.onsubmit=async e=>{
        e.preventDefault();if(busy||!form.elements.leader_id.value||!form.reportValidity())return;busy=true;$('tl-save').disabled=true;$('tl-error').hidden=true;
        try{const response=await fetch(form.getAttribute('action'),{method:'POST',body:new FormData(form),headers:{Accept:'application/json'}});const result=await response.json();if(!response.ok)throw new Error(Object.values(result.errors||{}).flat().join('\n')||result.message||l.error);location.reload();}
        catch(error){busy=false;$('tl-save').disabled=false;$('tl-error').textContent=error.message||l.error;$('tl-error').hidden=false;}
    };
})();
