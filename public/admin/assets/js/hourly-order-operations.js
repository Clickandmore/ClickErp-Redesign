(() => {
    const form=document.getElementById('order-operation-form');if(!form)return;
    const select=document.getElementById('operation-action'),error=document.getElementById('operation-error');
    let busy=false;
    let driverVersion=0,driversReady=false;
    let workerVersion=0,workersReady=false,workerRequired=0,workerSnapshot=[];
    async function refreshWorkers(){
        const version=++workerVersion,selected=boxes().filter(b=>b.checked);
        const choices=document.getElementById('operation-worker-choices'),context=document.getElementById('operation-workers-context');
        choices.replaceChildren();workersReady=false;workerSnapshot=[];workerRequired=0;
        if(select.value!=='assign_workers')return;
        context.textContent='جارٍ تحميل العاملات المتاحات…';
        if(selected.length!==1){context.textContent='اختر زيارة واحدة لتحديد عاملاتها يدويًا.';return;}
        try{
            const response=await fetch(form.dataset.workersUrl.replace('VISIT_ID',selected[0].value)+'?selection=1',{headers:{Accept:'application/json'}});
            const data=await response.json();
            if(version!==workerVersion || select.value!=='assign_workers')return;
            if(!response.ok)throw new Error(Object.values(data.errors||{}).flat().join('\n')||data.message||'تعذر تحميل العاملات');
            workerSnapshot=data.selected;workerRequired=data.required;
            context.textContent='الموعد: '+data.starts_at+' — '+data.ends_at+' · المطلوب: '+data.required+' عاملة';
            data.workers.forEach(worker=>{
                const label=document.createElement('label'),input=document.createElement('input');
                input.type='checkbox';input.name='worker_ids[]';input.value=worker.id;
                input.checked=data.selected.map(String).includes(String(worker.id));
                label.append(input,document.createTextNode(' '+worker.name+' (#'+worker.id+')'));choices.appendChild(label);
            });
            if(!data.workers.length)context.textContent+=' · لا توجد عاملات متاحات.';
            if(data.excluded_workers?.length){
                const details=document.createElement('details'),summary=document.createElement('summary');
                summary.textContent='العاملات غير المتاحات وأسباب الاستبعاد ('+data.excluded_workers.length+')';details.appendChild(summary);
                data.excluded_workers.forEach(worker=>{const row=document.createElement('p');row.textContent=worker.name+' (#'+worker.id+'): '+worker.reasons.join(' · ');details.appendChild(row);});choices.appendChild(details);
            }
            workersReady=true;
        }catch(ex){if(version===workerVersion){error.textContent=ex.message||'تعذر تحميل العاملات';error.hidden=false;context.textContent='تعذر تحميل القائمة. أعد فتح اختيار العاملات.';}}
    }
    async function refreshDrivers(){
        const version=++driverVersion,field=form.elements.driver_id,previous=field.value;
        driversReady=false;field.replaceChildren(new Option('جارٍ تحميل السائقين المتاحين…',''));field.disabled=true;
        if(select.value!=='assign_driver')return;
        const selected=boxes().filter(b=>b.checked),time=form.elements.driver_time.value;
        if(!selected.length || !time){field.replaceChildren(new Option('حدّد الزيارات ووقت المهمة',''));return;}
        const query=new URLSearchParams({driver_role:form.elements.driver_role.value,driver_time:time});
        selected.forEach(box=>query.append('visit_ids[]',box.value));
        try{
            const response=await fetch(form.dataset.driversUrl+'?'+query,{headers:{Accept:'application/json'}});
            const result=await response.json();
            if(version!==driverVersion || select.value!=='assign_driver')return;
            if(!response.ok)throw new Error(result.message||'تعذر تحميل السائقين');
            field.replaceChildren(new Option(result.drivers.length?'اختر سائقًا متاحًا':'لا يوجد سائق متاح لهذا الوقت والزيارات',''));
            result.drivers.forEach(driver=>field.appendChild(new Option(driver.name,driver.id)));
            if(result.drivers.some(driver=>String(driver.id)===previous))field.value=previous;
            field.disabled=!result.drivers.length;driversReady=!!result.drivers.length;
        }catch(ex){if(version===driverVersion){field.replaceChildren(new Option('تعذر تحميل السائقين؛ أعد اختيار الوقت',''));error.textContent=ex.message;error.hidden=false;}}
    }
    const boxes=()=>[...document.querySelectorAll('[data-visit-select]')];
    const count=()=>{document.getElementById('selected-visits-count').textContent=boxes().filter(b=>b.checked).length+' زيارة محددة';};
    function driverSchedule(){
        const selected=boxes().filter(b=>b.checked),pickup=form.elements.driver_role.value==='pickup';
        const summary=document.getElementById('driver-visit-schedule');
        summary.replaceChildren();
        selected.forEach(box=>{
            const line=document.createElement('div');
            line.textContent='الزيارة '+(box.dataset.number||box.value)+' (مرجع #'+box.value+') — بداية عمل العاملة: '+(box.dataset.start||'لم يحدد موعد')+' — نهاية العمل: '+(box.dataset.end||'لم يحدد موعد');
            summary.appendChild(line);
        });
        if(!selected.length)summary.textContent='اختر زيارة لعرض موعد عمل العاملة.';
        const times=selected.map(box=>{
            const saved=(pickup?box.dataset.pickup:box.dataset.delivery)||'';
            const time=saved.match(/(?:^|[T ])(\d{2}:\d{2})/);
            return time ? time[1] : (pickup?box.dataset.end:box.dataset.start)?.slice(11,16)||'';
        });
        form.elements.driver_time.value=times.length && times.every(time=>time===times[0]) ? times[0] : '';
        document.getElementById('driver-time-label').textContent=pickup?'وقت الاستلام':'وقت التوصيل';
        refreshDrivers();
    }
    form.elements.driver_role.addEventListener('change',driverSchedule);
    form.elements.driver_time.addEventListener('change',refreshDrivers);
    form.elements.driver_time.addEventListener('input',refreshDrivers);
    function fields(){
        const whole=['cancel_order','payment_method','free_visit'].includes(select.value);
        document.getElementById('operation-target').textContent=whole?'هذا الإجراء يخص الطلب بالكامل.':'يطبق الإجراء على '+boxes().filter(b=>b.checked).length+' زيارة محددة.';
        document.querySelectorAll('[data-op-fields]').forEach(group=>{const active=group.dataset.opFields.split(' ').includes(select.value);group.hidden=!active;group.querySelectorAll('input,select').forEach(input=>{input.disabled=!active;input.required=active&&input.type!=='checkbox';});});
        error.hidden=true;
        if(select.value==='assign_driver')driverSchedule();
        if(select.value==='assign_workers')refreshWorkers();
    }
    boxes().forEach(b=>b.addEventListener('change',()=>{count();if(select.value==='assign_driver')driverSchedule();if(select.value==='assign_workers')refreshWorkers();}));
    document.getElementById('select-all-visits').onchange=e=>{boxes().forEach(b=>b.checked=e.target.checked);count();if(select.value==='assign_driver')driverSchedule();if(select.value==='assign_workers')refreshWorkers();};
    const modal=document.getElementById('order-operation-modal'),title=document.getElementById('operation-modal-title'),icon=document.getElementById('operation-modal-icon');
    // المودال مُنشأ بجوار شريط إجراءات الزيارات داخل تبويب التفاصيل. انقله إلى body
    // حتى لا يرث display:none عند فتح الطلب مباشرة على تبويب آخر مثل سجل الدفعات.
    if(modal.parentElement!==document.body)document.body.appendChild(modal);
    // خطوتان داخل المودال: شبكة اختيار الإجراء ثم حقوله؛ العنوان والأيقونة يتبعان الإجراء المختار.
    function step(name){
        modal.dataset.opStep=name;
        const tile=modal.querySelector('[data-pick-action="'+select.value+'"]');
        title.textContent=name==='form'&&tile?tile.dataset.label:'الإجراءات';
        icon.className='fa '+(name==='form'&&tile?tile.dataset.icon:'fa-gears');
        fields();
    }
    modal.querySelectorAll('[data-pick-action]').forEach(tile=>tile.onclick=()=>{select.value=tile.dataset.pickAction;step('form');});
    document.getElementById('operation-back').onclick=()=>step('pick');
    document.getElementById('open-operations-picker').onclick=()=>{if(busy)return;step('pick');bootstrap.Modal.getOrCreateInstance(modal).show();};
    document.querySelectorAll('[data-order-action]').forEach(button=>button.onclick=()=>{
        if(busy)return;
        if(button.dataset.visit && button.dataset.orderAction==='start_time' && window.openHourlySchedule){window.openHourlySchedule(button.dataset.visit);return;}
        if(button.dataset.visit && button.dataset.orderAction==='team_leader' && window.openHourlyTeamLeader){window.openHourlyTeamLeader(button.dataset.visit);return;}
        if(button.dataset.visit && button.dataset.orderAction==='add_worker' && window.openHourlyAddWorker){window.openHourlyAddWorker(button.dataset.visit);return;}
        if(button.dataset.visit && ['price','duration'].includes(button.dataset.orderAction) && window.openHourlyVisitEditor){window.openHourlyVisitEditor(button.dataset.orderAction,button.dataset.visit);return;}
        if(button.dataset.visit)boxes().forEach(b=>b.checked=b.value===button.dataset.visit);
        select.value=button.dataset.orderAction;count();step('form');
        if(button.dataset.worker)form.elements.worker_id.value=button.dataset.worker;
        bootstrap.Modal.getOrCreateInstance(modal).show();
    });
    form.onsubmit=async e=>{
        e.preventDefault();if(busy)return;
        if(select.value==='assign_driver' && (!driversReady || !form.elements.driver_id.value)){error.textContent='اختر سائقًا متاحًا بعد تحديد وقت المهمة.';error.hidden=false;return;}
        const selected=boxes().filter(b=>b.checked);
        if(select.value==='assign_workers'){
            const checked=form.querySelectorAll('#operation-worker-choices input:checked');
            if(!workersReady || selected.length!==1 || !checked.length || checked.length>workerRequired){error.textContent='اختر من العاملات المتاحات لزيارة واحدة دون تجاوز العدد المطلوب ('+workerRequired+').';error.hidden=false;return;}
        }
        if(!['cancel_order','payment_method','free_visit'].includes(select.value) && !selected.length){error.textContent='اختر زيارة واحدة على الأقل.';error.hidden=false;return;}
        if(['cancel_order','cancel_visit','complete','free_visit'].includes(select.value)){
            const danger=select.value!=='complete',text='سيُطبَّق «'+title.textContent+'» ولا يمكن التراجع عنه تلقائيًا.';
            const ok=window.Swal
                ? (await Swal.fire({icon:danger?'warning':'question',title:'تأكيد التنفيذ؟',text,showCancelButton:true,reverseButtons:true,focusCancel:danger,confirmButtonText:'نعم، نفّذ',cancelButtonText:'تراجع',customClass:{popup:'om-swal',confirmButton:danger?'is-danger':''}})).isConfirmed
                : confirm(text);
            if(!ok)return;
        }
        busy=true;document.getElementById('operation-save').disabled=true;error.hidden=true;
        const body=new FormData(form);selected.forEach(b=>body.append('visit_ids[]',b.value));
        if(select.value==='assign_workers')workerSnapshot.forEach(id=>body.append('expected_worker_ids[]',id));
        try{
            const response=await fetch(form.getAttribute('action'),{method:'POST',body,headers:{Accept:'application/json'}});
            const result=await response.json();if(!response.ok)throw new Error(Object.values(result.errors||{}).flat().join('\n')||result.message);
            if(result.warning){
                if(window.Swal)await Swal.fire({icon:'warning',title:'تمت إضافة الزيارة بدون تعيين',text:result.warning,confirmButtonText:'حسنًا',customClass:{popup:'om-swal'}});
                else window.alert(result.warning);
            }
            location.reload();
        }catch(ex){error.textContent=ex.message || 'تعذر تنفيذ الإجراء';error.hidden=false;busy=false;document.getElementById('operation-save').disabled=false;}
    };
    count();fields();
})();
