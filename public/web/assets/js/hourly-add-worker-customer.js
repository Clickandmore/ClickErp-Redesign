(function(){
    const root=document.getElementById('hourly-customer-add-worker');if(!root)return;
    const form=document.getElementById('hca-form'),status=document.getElementById('hca-status'),select=document.getElementById('hca-worker');
    let busy=false;
    async function read(response){const data=await response.json();if(!response.ok)throw new Error(Object.values(data.errors||{}).flat().join(' ')||data.message||'تعذر إتمام الطلب.');return data;}
    async function preview(){
        try{
            const {data}=await read(await fetch(root.dataset.preview,{headers:{Accept:'application/json'},credentials:'same-origin'}));
            form.elements.expected_workers.value=data.current_workers;form.elements.expected_price.value=data.current_price;form.elements.expected_total.value=data.order_total;
            document.getElementById('hca-choice').hidden=!data.can_choose_worker;
            select.replaceChildren();
            if(data.can_choose_worker)data.workers.forEach(worker=>select.add(new Option(worker.name,worker.id)));
            else select.disabled=true;
            document.getElementById('hca-price').textContent='عدد العاملات: '+data.current_workers+' ← '+data.new_workers+' · تكلفة الزيادة: '+data.additional_fee.toFixed(2)+' ر.س · سعر الزيارة الجديد: '+data.new_price.toFixed(2)+' ر.س · إجمالي الطلب الجديد: '+data.new_order_total.toFixed(2)+' ر.س';
            status.textContent='راجع السعر ثم أكد إذا رغبت في الزيادة.';form.hidden=false;
        }catch(error){status.textContent=error.message;}
    }
    form.addEventListener('submit',async event=>{
        event.preventDefault();if(busy||!form.reportValidity())return;busy=true;
        const button=form.querySelector('[type=submit]');button.disabled=true;
        try{const result=await read(await fetch(form.action,{method:'POST',body:new FormData(form),headers:{Accept:'application/json'},credentials:'same-origin'}));window.location.assign(result.redirect);}
        catch(error){status.textContent=error.message;busy=false;button.disabled=false;}
    });
    preview();
})();