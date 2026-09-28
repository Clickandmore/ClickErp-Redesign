(() => {
    const modal=document.getElementById('visit-edit-modal'),form=document.getElementById('visit-edit-form');
    if(!modal || !form)return;
    document.body.appendChild(modal);
    const config=window.hourlyVisitEditorData,labels=config.labels,$=id=>document.getElementById(id);
    let visit,minutes,busy=false;
    const time=value=>`${Math.floor(value/60)} ${labels.hour}${value%60 ? ` ${value%60} ${labels.minute}`:''}`;
    function total(){
        const amount=Number(form.elements.duration_price_adjustment.value);
        $('ve-total').textContent=(visit.price+amount).toFixed(2);
    }
    function duration(){
        const delta=minutes-Math.round(visit.hours*60);
        form.elements.hours.value=minutes/60;$('ve-time').textContent=time(minutes);
        $('ve-plus').disabled=minutes>=1440;$('ve-minus').disabled=minutes<=30;
        $('ve-extra').hidden=delta===0;
        $('ve-delta').textContent=delta ? `${delta>0?'↑':'↓'} ${Math.abs(delta)} ${delta>0?labels.added:labels.removed}`:'';
        const adjustment=form.elements.duration_price_adjustment;
        adjustment.value=(Math.round(visit.price*delta/(visit.hours*60)*100)/100).toFixed(2);
        adjustment.min=(-visit.price).toFixed(2);adjustment.max=(9999999-visit.price).toFixed(2);
        ['duration_price_adjustment','ignore_hour_caps','ignore_break'].forEach(name=>form.elements[name].disabled=delta===0);
        adjustment.required=delta!==0;
        total();
    }
    window.openHourlyVisitEditor=(action,id)=>{
        if(busy || !config.visits[id])return;
        visit=config.visits[id];minutes=Math.round(visit.hours*60);
        form.reset();$('ve-error').hidden=true;modal.classList.toggle('is-duration',action==='duration');
        $('ve-title').textContent=labels[action+'_title'];form.elements.action.value=action;form.elements['visit_ids[]'].value=id;
        form.elements.expected_price.value=visit.price;form.elements.expected_hours.value=visit.hours;
        Object.entries({...visit,hours:time(minutes)}).forEach(([key,value])=>{const element=modal.querySelector(`[data-ve-summary="${key}"]`);if(element)element.textContent=value;});
        $('ve-current').value=visit.price.toFixed(2);$('ve-rate').textContent=(visit.price/visit.hours).toFixed(2);
        $('ve-price-fields').hidden=action!=='price';$('ve-duration-fields').hidden=action!=='duration';
        form.elements.price.disabled=action!=='price';form.elements.price.required=action==='price';
        form.elements.hours.disabled=action!=='duration';
        duration();
        bootstrap.Modal.getOrCreateInstance(modal).show();
    };
    $('ve-plus').onclick=()=>{if(!busy){minutes=Math.min(1440,minutes+15);duration();}};
    $('ve-minus').onclick=()=>{if(!busy){minutes=Math.max(30,minutes-15);duration();}};
    form.elements.duration_price_adjustment.oninput=total;
    modal.addEventListener('hide.bs.modal',e=>{if(busy)e.preventDefault();});
    form.onsubmit=async event=>{
        event.preventDefault();if(busy || !form.reportValidity())return;
        busy=true;$('ve-save').disabled=true;$('ve-error').hidden=true;
        try{
            const response=await fetch(form.getAttribute('action'),{method:'POST',body:new FormData(form),headers:{Accept:'application/json'}});
            const data=await response.json();if(!response.ok)throw new Error(Object.values(data.errors||{}).flat().join('\n')||data.message||labels.error);
            location.reload();
        }catch(error){$('ve-error').textContent=error.message||labels.error;$('ve-error').hidden=false;busy=false;$('ve-save').disabled=false;}
    };
})();
