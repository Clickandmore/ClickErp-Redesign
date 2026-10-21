(function(){
    const dialog=document.getElementById('hca-dialog'),configNode=document.getElementById('hca-config');if(!dialog||!configNode)return;
    const config=JSON.parse(configNode.textContent),form=document.getElementById('hca-form'),$=id=>document.getElementById(id);
    const select=$('hca-worker'),submit=$('hca-submit'),error=$('hca-error');
    let busy=false,loaded=false,request=0;
    const money=value=>Number(value).toFixed(2)+' ر.س';
    const uuid=()=>window.crypto&&crypto.randomUUID?crypto.randomUUID():'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g,c=>{const r=Math.random()*16|0;return (c==='x'?r:(r&3|8)).toString(16);});
    async function read(response){const data=await response.json();if(!response.ok)throw new Error(Object.values(data.errors||{}).flat().join(' ')||data.message||'تعذر إتمام الطلب.');return data;}
    function showError(message){error.textContent=message;error.hidden=!message;}
    function refreshSubmit(){submit.disabled=busy||!loaded||!$('hca-confirmed').checked;}
    async function open(visitId,label){
        const token=++request;loaded=false;busy=false;
        form.reset();form.elements.request_token.value=uuid();
        form.action=config.store.replace('__VISIT__',encodeURIComponent(visitId));
        $('hca-visit').textContent=label||'';
        $('hca-loading').hidden=false;$('hca-content').hidden=true;showError('');refreshSubmit();
        if(!dialog.open)dialog.showModal();
        try{
            const {data}=await read(await fetch(config.preview.replace('__VISIT__',encodeURIComponent(visitId)),{headers:{Accept:'application/json'},credentials:'same-origin'}));
            if(token!==request)return;
            form.elements.expected_workers.value=data.current_workers;form.elements.expected_price.value=data.current_price;form.elements.expected_total.value=data.order_total;
            $('hca-current').textContent=data.current_workers;$('hca-new').textContent=data.new_workers;
            $('hca-fee').textContent='+ '+money(data.additional_fee);$('hca-price').textContent=money(data.new_price);$('hca-total').textContent=money(data.new_order_total);
            select.replaceChildren();select.disabled=!data.can_choose_worker;
            if(data.can_choose_worker)data.workers.forEach(worker=>select.add(new Option(worker.name,worker.id)));
            $('hca-choice').hidden=!data.can_choose_worker;
            $('hca-content').hidden=false;loaded=true;
        }catch(e){if(token===request)showError(e.message);}
        finally{if(token===request){$('hca-loading').hidden=true;refreshSubmit();}}
    }
    document.addEventListener('click',event=>{
        const trigger=event.target.closest('[data-add-worker]');if(!trigger)return;
        event.preventDefault();open(trigger.dataset.addWorker,trigger.dataset.visitLabel);
    });
    dialog.querySelectorAll('[data-hca-close]').forEach(button=>button.addEventListener('click',()=>{if(!busy)dialog.close();}));
    dialog.addEventListener('cancel',event=>{if(busy)event.preventDefault();});
    $('hca-confirmed').addEventListener('change',refreshSubmit);
    form.addEventListener('submit',async event=>{
        event.preventDefault();if(busy||!loaded)return;
        if(!$('hca-confirmed').checked){showError('يرجى تأكيد الموافقة على السعر المعروض.');return;}
        busy=true;refreshSubmit();showError('');
        try{const result=await read(await fetch(form.action,{method:'POST',body:new FormData(form),headers:{Accept:'application/json'},credentials:'same-origin'}));window.location.assign(result.redirect);}
        catch(e){showError(e.message);busy=false;refreshSubmit();}
    });
    // The window opens only from its button; drop the parameter left by the old link-based flow so a reload stays clean.
    const address=new URL(window.location.href);
    if(address.searchParams.has('add_worker_visit')||address.hash==='#hourly-customer-add-worker'){
        address.searchParams.delete('add_worker_visit');if(address.hash==='#hourly-customer-add-worker')address.hash='';
        window.history.replaceState(null,'',address.toString());
    }
})();
