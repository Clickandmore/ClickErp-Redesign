/* The draft lives in this tab's memory. Passwords and customer data are never put in browser storage. */
(() => {
    'use strict';
    const config = window.hourlyBookingConfig;
    const $ = id => document.getElementById(id);
    const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const money = value => Number(value || 0).toLocaleString('en-US', {minimumFractionDigits:2,maximumFractionDigits:2}) + ' ر.س';
    const token = () => '10000000-1000-4000-8000-100000000000'.replace(/[018]/g, c => (Number(c) ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> Number(c) / 4).toString(16));
    const state = {step:1,customer_mode:'existing',address_mode:'existing',customer:null,address:null,category:null,item:null,workers:1,quantity:1,options:{},visits:[],quote:null,coupon:'',allWorkers:false,bookingToken:token()};
    if(config.renewal)state.customer=config.renewal.customer;
    /* التجديد: القيم المنقولة من الطلب القديم مقترحة وقابلة للتعديل؛ التنبيهات تظهر في شريط التجديد أعلى المعالج. */
    const renewal=config.renewal,renewalNotices={};
    const isRenewalItem=()=>Boolean(renewal && state.item && state.item.id===renewal.item_id);
    const preferredIds=()=>Array.from({length:state.workers},(_,w)=>renewal?.preferred_workers?.[w]?.id ?? null);
    function notice(key,text,type='warn'){
        const list=document.getElementById('renewal-notices');if(!list)return;
        if(text)renewalNotices[key]={text,type};else delete renewalNotices[key];
        list.innerHTML=Object.values(renewalNotices).map(n=>`<li class="${n.type==='info'?'is-info':''}"><i class="fa ${n.type==='info'?'fa-circle-info':'fa-triangle-exclamation'}"></i><span>${esc(n.text)}</span></li>`).join('');
        list.hidden=!Object.keys(renewalNotices).length;
    }
    let categories=[],addresses=[],tab='general',month=new Date(new Date().getFullYear(),new Date().getMonth(),1),selectedDate='',slotGeneration=0,quoteGeneration=0,busy=false,map,marker,geocoder;
    let addressCustomerKey=null,savedAddress=null,geocodeGeneration=0;
    // مزود الخرائط من إعدادات الموديول؛ اختيار Google بلا مفتاح يسقط إلى المجاني.
    let provider=config.mapProvider==='google' && config.mapsKey?'google':'osm';
    const RIYADH={lat:24.7136,lng:46.6753};
    const formData = form => Object.fromEntries(new FormData($(form)));
    const fail = error => { $('booking-error').textContent=error.message || String(error); $('booking-error').hidden=false;if(error.duplicate)window.showHourlyDuplicate?.($('booking-error'),error.duplicate); };
    const clearError = () => { $('booking-error').hidden=true; };
    async function api(path,data,method='POST') {
        const response=await fetch(config.base+'/'+path,{method,headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':config.csrf},...(method==='POST'?{body:JSON.stringify(data)}:{})});
        const result=await response.json().catch(()=>({message:'تعذر إتمام الطلب. يرجى المحاولة مجددًا.'}));
        if(!response.ok) throw Object.assign(new Error(result.errors?Object.values(result.errors).flat().join('\n'):result.message || 'تعذر إتمام الطلب'),{duplicate:result.duplicate});
        return result;
    }
    function context() {
        return {customer_mode:state.customer_mode,user_id:state.customer_mode==='existing'?state.customer?.id:null,address_mode:state.address_mode,address_id:state.address_mode==='existing'?state.address?.id:null,address:state.address,hourly_package_id:state.item?.id,workers_per_visit:state.workers,quantity:state.quantity,...state.options,all_workers:state.allWorkers};
    }
    function resetService() { quoteGeneration++;slotGeneration++;state.category=null;state.item=null;state.quantity=1;state.options={};state.visits=[];state.quote=null;state.coupon='';if($('override-price'))$('override-price').checked=false;$('coupon-code').value='';summary(); }
    function summary() {
        const lines=[];
        if(state.customer) lines.push(['العميل',state.customer.name || state.customer.company_name || [state.customer.first_name,state.customer.last_name].join(' ')],['الإيميل',state.customer.email],['رصيد العميل',money(state.customer.hourly_wallet_balance)]);
        if(state.address) lines.push(['العنوان',state.address.address],['المبنى / الوحدة',[state.address.building,state.address.unit].filter(Boolean).join(' / ')]);
        if(state.category) lines.push(['الخدمة',state.category.name]);
        if(state.item) lines.push(['عنصر الخدمة',state.item.title],['عدد الزيارات',state.item.visits],['مقدمو الخدمة',state.workers],['Total Hours',Number(state.item.hours)*state.item.visits*state.workers+' ساعة']);
        if(state.item?.booking_config?.steppers?.some(s=>s.key==='quantity'))lines.push(['الكمية',state.quantity]);
        Object.entries(state.options || {}).forEach(([key,value])=>{
            const field=state.item?.booking_config?.fields?.[key];if(!field)return;
            lines.push([field.label,field.type==='boolean'?(value?'نعم':'لا'):field.options.find(o=>o.value===value)?.label]);
        });
        if(state.visits.length) lines.push(['المواعيد',state.visits.map(v=>v.starts_at).filter(Boolean).join('، ')]);
        $('booking-summary').innerHTML=lines.map(([label,value])=>`<div><dt>${esc(label)}</dt><dd>${esc(value)}</dd></div>`).join('');
        const q=state.quote;
        $('schedule-total').textContent=q?money(q.total/100):'—';
        const pricing=q?.presentation;
        $('price-summary').innerHTML=q?`${(pricing?.lines || [{label:'الخدمة',amount:q.total}]).map(line=>`<p>${esc(line.label)} <b>${money(line.amount/100)}</b></p>`).join('')}${pricing?.tax_line?`<p class="booking-muted">${esc(pricing.tax_line.label)} ${money(pricing.tax_line.amount/100)}</p>`:''}<div>المبلغ الإجمالي</div><div class="total">${money(q.total/100)}</div>${q.price_display==='per_visit'?`<p>قيمة الزيارة الأولى ${money(q.charge_now/100)}</p>`:''}`:'<p class="booking-muted">اختر الخدمة لعرض السعر</p>';
        if(q)$('price-summary').innerHTML+='<p>سياسة الدفع: '+esc(({prepaid:'مقدمًا بالكامل',per_visit:'لكل زيارة',installments:'أقساط بمواعيد محددة'})[q.payment_policy] || (q.price_display==='per_visit'?'لكل زيارة':'حسب الإعداد السابق'))+'</p>';
        $('coupon-box').hidden=state.step!==6;
        $('place-order').hidden=state.step!==6;
        $('place-order').disabled=busy || !q;
        $('total-hours').textContent=state.item?`Total Hours: ${Number(state.item.hours)*state.item.visits*state.workers} ساعة` : '';
    }
    function showStep() {
        document.querySelectorAll('[data-step]').forEach(el=>el.hidden=Number(el.dataset.step)!==state.step);
        document.querySelectorAll('[data-step-label]').forEach(el=>{el.classList.toggle('current',Number(el.dataset.stepLabel)===state.step);el.classList.toggle('done',Number(el.dataset.stepLabel)<state.step);});
        $('booking-back').hidden=state.step===(renewal?2:1);$('booking-next').hidden=state.step===6;summary();
        if(state.step===2 && state.address_mode==='new')initMap();
    }
    async function customers(page=1) {
        if(renewal)return;
        const data=await api('customers?search='+encodeURIComponent($('customer-search').value)+'&page='+page,null,'GET');
        $('customer-results').innerHTML=data.data.length?data.data.map(u=>`<tr><td>${u.id}</td><td>${esc(u.name)}</td><td>${esc(u.email)}</td><td>${esc(u.phone)}</td><td>${money(u.hourly_wallet_balance)}</td><td><button type="button" class="btn btn-sm btn-outline-success" data-customer="${u.id}">${state.customer?.id===u.id?'✓ مختار':'اختر'}</button></td></tr>`).join(''):'<tr><td colspan="6">لا توجد نتائج</td></tr>';
        $('customer-results').querySelectorAll('[data-customer]').forEach(button=>button.onclick=()=>{state.customer=data.data.find(u=>u.id===Number(button.dataset.customer));state.address=null;resetService();customers(page).catch(fail);summary();});
        $('customer-pages').innerHTML=`<button type="button" class="btn btn-sm btn-light" id="customers-prev" ${page===1?'disabled':''}>السابق</button><span>${page} / ${data.last_page}</span><button type="button" class="btn btn-sm btn-light" id="customers-next" ${page===data.last_page?'disabled':''}>التالي</button>`;
        $('customers-prev').onclick=()=>customers(page-1).catch(fail);$('customers-next').onclick=()=>customers(page+1).catch(fail);
    }
    $('search-customers').onclick=()=>customers().catch(fail);
    document.querySelectorAll('[data-customer-type]').forEach(button=>button.onclick=()=>{
        const type=button.dataset.customerType;
        $('customer-type').value=type;
        document.querySelectorAll('[data-customer-type]').forEach(tab=>tab.setAttribute('aria-selected',String(tab===button)));
        document.querySelectorAll('[data-customer-field]').forEach(group=>{
            const active=group.dataset.customerField===type;group.hidden=!active;
            group.querySelectorAll('input,textarea,select').forEach(field=>{field.disabled=!active;field.required=active && field.hasAttribute('data-required');});
        });
        if(state.customer_mode==='new'){state.customer=null;summary();}
    });
    $('customer-search').onkeydown=e=>{if(e.key==='Enter'){e.preventDefault();customers().catch(fail);}};
    document.querySelectorAll('[name=customer_mode]').forEach(radio=>radio.onchange=()=>{if(renewal)return;state.customer_mode=radio.value;state.customer=null;state.address=null;resetService();$('existing-customer').hidden=radio.value==='new';$('new-customer').hidden=radio.value==='existing';});
    function districts() {
        const city=$('new-address').elements.city_id.value;
        $('new-address').elements.hourly_district_id.innerHTML='<option value="">اختر الحي</option>'+config.districts.filter(d=>String(d.city_id)===city).map(d=>`<option value="${d.id}">${esc(d.name)}</option>`).join('');
    }
    $('new-address').elements.city_id.onchange=districts;
    function addressMode(mode) {
        state.address_mode=mode;$('new-address').hidden=mode!=='new';$('saved-addresses').hidden=mode!=='existing';
        if(mode==='existing')state.address=savedAddress;
        $('toggle-address').textContent=mode==='new'?'العناوين المحفوظة':'إضافة عنوان جديد';
        $('toggle-address').hidden=addresses.length===0;
        if(mode==='new') {
            const f=$('new-address').elements;
            if(!f.contact_first_name.value) {const names=(state.customer?.name || '').split(' ');f.contact_first_name.value=state.customer?.first_name || names.shift() || '';f.contact_last_name.value=state.customer?.last_name || names.join(' ');f.contact_phone.value=state.customer?.phone || '';}
            initMap();
        }
    }
    $('toggle-address').onclick=()=>{const mode=state.address_mode==='new'?'existing':'new';state.address=mode==='existing'?savedAddress:null;resetService();addressMode(mode);renderAddresses();};
    function resetAddressCustomer() {
        geocodeGeneration++;addresses=[];savedAddress=null;state.address=null;
        $('new-address').reset();districts();
        if(marker){provider==='google'?marker.setMap(null):map?.removeLayer(marker);marker=null;}
        resetService();
    }
    function renderAddresses() {
        $('saved-addresses').innerHTML=addresses.length?`<p class="booking-muted">عناوين العميل المحفوظة (${addresses.length})</p><div class="table-responsive"><table class="table"><thead><tr><th>#</th><th>المدينة / الحي</th><th>العنوان</th><th>اسم المبنى / الفيلا</th><th>رقم الوحدة</th><th></th></tr></thead><tbody>${addresses.map(a=>`<tr class="${state.address?.id===a.id?'selected':''}"><td>${a.id}</td><td>${esc([a.city_name,a.district].filter(Boolean).join(' / '))}</td><td>${esc(a.address)}</td><td>${esc(a.building)}</td><td>${esc(a.unit)}</td><td><button type="button" class="btn btn-sm btn-outline-primary" data-address="${a.id}" aria-pressed="${state.address?.id===a.id}">${state.address?.id===a.id?'✓ مختار':'اختر'}</button></td></tr>`).join('')}</tbody></table></div>`:'<p class="my-4">لا توجد عناوين محفوظة. أضف عنوانًا جديدًا.</p>';
        $('saved-addresses').querySelectorAll('[data-address]').forEach(button=>button.onclick=()=>{state.address=addresses.find(a=>a.id===Number(button.dataset.address));savedAddress=state.address;resetService();renderAddresses();});
    }
    async function loadCatalog() {
        categories=await api('catalog',context());
        if(renewal && !config.renewalApplied && state.customer?.id===renewal.customer.id){
            config.renewalApplied=true;
            const category=categories.find(c=>c.available && c.items.some(i=>i.id===renewal.item_id));
            if(category){
                state.category=category;state.item=category.items.find(i=>i.id===renewal.item_id);
                // عدد العاملات من الطلب القديم، ولا يقل عن الحد الأدنى للعنصر.
                state.workers=Math.max(Number(state.item.workers),Number(renewal.workers || 0));tab=state.item.special?'special':'general';
                state.quantity=Number(state.item.quantity || 1);state.options={};renderServiceOptions();state.allWorkers=Boolean(renewal.all_workers);$('all-workers').checked=state.allWorkers;
                const proposed=renewal.visits || [];
                state.visits=Array.from({length:Number(state.item.visits)},(_,i)=>({starts_at:proposed[i] || '',worker_ids:preferredIds(),preferred:preferredIds()}));
                $('workers-number').textContent=state.workers;
                notice('dates',proposed.length?`اقترحنا ${state.visits.filter(v=>v.starts_at).length} مواعيد تبدأ ${proposed[0]} بعد انتهاء العقد السابق وبنفس نمطه${renewal.pattern?' ('+renewal.pattern+')':''}. يمكن تعديلها في خطوة التاريخ والوقت.`:'تعذر اقتراح مواعيد من الطلب السابق؛ حدّدها من خطوة التاريخ والوقت.','info');
            } else notice('item','عنصر الخدمة في الطلب السابق غير متاح حاليًا لهذا العنوان؛ اختر الخدمة والعنصر من جديد.');
        }
        $('service-categories').innerHTML=categories.map(c=>`<button type="button" class="booking-option ${state.category?.id===c.id?'selected':''}" data-category="${c.id}" ${c.available?'':'disabled'}><strong>${esc(c.name)}</strong>${c.available?'':`<div class="text-danger mt-2">${esc(c.unavailable_message || config.messages.noServiceArea)}</div>`}</button>`).join('') || '<p>لا توجد فئات خدمة.</p>';
        $('service-categories').querySelectorAll('[data-category]').forEach(button=>button.onclick=()=>{state.category=categories.find(c=>c.id===Number(button.dataset.category));state.item=null;state.quantity=1;state.options={};state.quote=null;state.visits=[];renderItems();loadCatalog().catch(fail);summary();});
    }
    function renderItems() {
        const items=(state.category?.items || []).filter(i=>Boolean(i.special)===(tab==='special'));
        $('service-items').innerHTML=items.map(i=>`<button type="button" class="booking-option ${state.item?.id===i.id?'selected':''}" data-item="${i.id}"><strong>${esc(i.title)}</strong><div class="mt-2">${i.hours} ساعات · ${i.visits} زيارة · ${money(i.price)} ${i.price_display==='per_visit'?'للزيارة':'للباقة'}</div></button>`).join('') || '<p>لا توجد عناصر متاحة في هذا التبويب.</p>';
        $('service-items').querySelectorAll('[data-item]').forEach(button=>button.onclick=()=>{state.item=items.find(i=>i.id===Number(button.dataset.item));state.workers=Number(state.item.workers);state.quantity=Number(state.item.quantity || 1);state.options={};renderServiceOptions();state.visits=Array.from({length:Number(state.item.visits)},()=>({starts_at:'',worker_ids:Array(state.workers).fill(null)}));$('workers-number').textContent=state.workers;state.coupon='';$('coupon-code').value='';if($('override-price'))$('override-price').checked=false;renderItems();refreshQuote().catch(fail);});
    }
    document.querySelectorAll('[data-item-tab]').forEach(button=>button.onclick=()=>{tab=button.dataset.itemTab;renderItems();});
    function renderServiceOptions() {
        const settings=state.item?.booking_config || {},steppers=settings.steppers || [];
        const worker=steppers.find(s=>s.key==='workers'),quantity=steppers.find(s=>s.key==='quantity');
        $('booking-workers-label').textContent=worker?.label || 'عدد مقدمي الخدمة';
        $('booking-quantity-field').hidden=!quantity;
        $('booking-quantity-label').textContent=quantity?.label || 'الكمية';
        $('booking-quantity').min=quantity?.min || 1;$('booking-quantity').max=quantity?.max || 50;
        $('booking-quantity').value=state.quantity;
        steppers.forEach(s=>$('booking-count-fields').appendChild($(s.key==='workers'?'booking-workers-field':'booking-quantity-field')));
        $('booking-service-options').replaceChildren();
        Object.entries(settings.fields || {}).forEach(([key,field])=>{
            state.options[key] ??= field.default;
            const row=document.createElement('div'),label=document.createElement('label');row.className='my-3';
            const input=document.createElement(field.type==='boolean'?'input':'select');input.id='booking-option-'+key;label.htmlFor=input.id;label.textContent=field.label;
            if(field.type==='boolean'){input.type='checkbox';input.checked=Boolean(state.options[key]);input.className='form-check-input ms-2';}
            else {input.className='form-select';(field.options || []).forEach(o=>input.add(new Option(o.label,o.value)));input.value=state.options[key] || '';}
            input.onchange=()=>{state.options[key]=field.type==='boolean'?input.checked:input.value;refreshQuote().catch(fail);};
            row.append(label,input);$('booking-service-options').appendChild(row);
        });
        $('booking-service-notes').replaceChildren();
        (settings.notes || []).forEach(note=>{const p=document.createElement('p');p.textContent=note.text;$('booking-service-notes').appendChild(p);});
        $('booking-auto-assign').hidden=!settings.auto_assign;
    }
    $('booking-quantity').onchange=()=>{if(!$('booking-quantity').reportValidity())return;state.quantity=Number($('booking-quantity').value);refreshQuote().catch(fail);};
    async function refreshQuote() {
        if(!state.item)return;
        const generation=++quoteGeneration;state.quote=null;summary();
        const data={...context(),coupon_code:state.coupon || null};
        if($('override-price')?.checked) data.manual_total=Number($('manual-total').value);
        const quote=await api('quote',data);
        if(generation!==quoteGeneration)return;
        state.quote=quote;summary();if(state.step===6)renderPayments();
        // السعر من التسعير الحالي؛ نقارنه بسعر الزيارة في الطلب القديم وننبّه عند الاختلاف.
        if(isRenewalItem() && renewal.previous_visit_price!=null && !$('override-price')?.checked){
            const now=Math.round(quote.total/Number(state.item.visits))/100,before=Number(renewal.previous_visit_price);
            notice('price',Math.abs(now-before)>=0.01?`السعر تغيّر من ${money(before)} إلى ${money(now)} لكل زيارة (حسب التسعير الحالي).`:null);
        } else notice('price',null);
    }
    function workers(delta) {if(!state.item)return;const stepper=state.item.booking_config?.steppers?.find(s=>s.key==='workers');state.workers=Math.min(stepper?.max || 20,Math.max(Number(state.item.workers),state.workers+delta));$('workers-number').textContent=state.workers;state.visits.forEach(v=>{v.worker_ids=Array(state.workers).fill(null);v.starts_at='';});refreshQuote().catch(fail);}
    $('workers-minus').onclick=()=>workers(-1);$('workers-plus').onclick=()=>workers(1);
    if($('override-price')){$('override-price').onchange=()=>{$('manual-total').hidden=!$('override-price').checked;if($('override-price').checked)$('manual-total').value=state.quote?.total/100 || '';refreshQuote().catch(fail);};$('manual-total').onchange=()=>refreshQuote().catch(fail);}
    function renderCalendar() {
        $('visit-selector').hidden=state.visits.length<=1;
        $('visit-dates').hidden=state.visits.length<=1;
        $('calendar-title').textContent=month.toLocaleDateString('ar-SA-u-nu-latn',{month:'long',year:'numeric',calendar:'gregory'});
        let html=['أحد','إثنين','ثلاثاء','أربعاء','خميس','جمعة','سبت'].map(d=>`<small>${d}</small>`).join('');
        const policy=state.item?.booking_config?.booking || {};
        for(let i=0;i<42;i++){
            const day=new Date(month.getFullYear(),month.getMonth(),1-month.getDay()+i);
            const date=`${day.getFullYear()}-${String(day.getMonth()+1).padStart(2,'0')}-${String(day.getDate()).padStart(2,'0')}`;
            const closed=!state.item?.booking_config?.ignore_closed_days && policy.working_days?.[day.getDay()]?.is_on===false;
            const repeated=policy.allow_multiple_sessions_per_day===false && state.visits.some((v,index)=>index!==Number($('visit-index').value) && v.starts_at?.slice(0,10)===date);
            const disabled=(policy.min_date && date<policy.min_date) || (policy.max_date && date>policy.max_date) || closed || repeated;
            html+=`<button type="button" data-date="${date}" aria-label="${date}" aria-pressed="${selectedDate===date}" class="${selectedDate===date?'selected':''} ${day.getMonth()!==month.getMonth()?'outside-month':''}" ${disabled?'disabled':''}>${day.getDate()}</button>`;
        }
        $('booking-calendar').innerHTML=html;
        $('booking-calendar').querySelectorAll('[data-date]').forEach(button=>button.onclick=()=>{selectedDate=button.dataset.date;month=new Date(Number(selectedDate.slice(0,4)),Number(selectedDate.slice(5,7))-1,1);renderCalendar();loadSlots().catch(fail);});
        $('visit-dates').innerHTML=state.visits.map((v,i)=>`<p>الزيارة ${i+1}: ${esc(v.starts_at || 'لم يحدد الموعد')}</p>`).join('');
    }
    $('previous-month').onclick=()=>{month.setMonth(month.getMonth()-1);renderCalendar();};$('next-month').onclick=()=>{month.setMonth(month.getMonth()+1);renderCalendar();};
    if($('schedule-edit-price'))$('schedule-edit-price').onclick=()=>{state.step=4;showStep();$('override-price').checked=true;$('manual-total').hidden=false;$('manual-total').value=state.quote?.total/100 || '';$('manual-total').focus();};
    $('visit-index').onchange=()=>{slotGeneration++;$('booking-slots').innerHTML='';const v=state.visits[Number($('visit-index').value)];selectedDate=v.starts_at?.slice(0,10) || '';renderCalendar();if(selectedDate)loadSlots().catch(fail);};
    let displayedSlots=null;
    function renderSlots() {
        const snapshot=displayedSlots;
        if(!snapshot || snapshot.generation!==slotGeneration || snapshot.date!==selectedDate || snapshot.index!==Number($('visit-index').value))return;
        const {slots,index}=snapshot;
        const available=slots.filter(s=>s.available);
        $('first-available-slot').disabled=!available.length;
        $('availability-status').textContent=available.length?`${selectedDate} — ${available.length} فترة متاحة. الأخضر متاح والأزرق هو اختيارك.`:'لا توجد فترات متاحة لهذا اليوم. اختر يومًا آخر من التقويم.';
        if(!available.length && slots.length)$('availability-status').textContent=[...new Set(slots.map(s=>s.unavailable_reason).filter(Boolean))].join(' ') || $('availability-status').textContent;
        $('booking-slots').innerHTML=slots.map((slot,i)=>{
            if($('available-slots-only').checked && !slot.available)return '';
            const chosen=state.visits[index].starts_at===slot.starts_at.slice(0,16);
            return `<button type="button" class="btn ${chosen?'btn-primary':slot.available?'slot-available':''}" data-slot="${i}" aria-pressed="${chosen}" ${slot.available?'':'disabled'}><strong>${slot.starts_at.slice(11,16)}</strong><small>حتى ${slot.ends_at.slice(11,16)}</small><small>${chosen?'✓ محدد':slot.available?'متاح':'غير متاح'}</small></button>`;
        }).join('');
        $('booking-slots').querySelectorAll('[data-slot]').forEach(button=>button.onclick=()=>selectSlot(Number(button.dataset.slot)));
    }
    function selectSlot(position) {
        const snapshot=displayedSlots;
        if(!snapshot || snapshot.generation!==slotGeneration || snapshot.date!==selectedDate || snapshot.index!==Number($('visit-index').value))return;
        const slot=snapshot.slots[position];
        if(!slot?.available)return;
        const visit=state.visits[snapshot.index];
        visit.starts_at=slot.starts_at.slice(0,16);visit.workers=slot.workers;visit.worker_ids=Array(state.workers).fill(null);
        renderCalendar();summary();renderSlots();
    }
    $('available-slots-only').onchange=renderSlots;
    $('first-available-slot').onclick=()=>selectSlot(displayedSlots?.slots.findIndex(s=>s.available) ?? -1);
    async function loadSlots() {
        const generation=++slotGeneration,index=Number($('visit-index').value);
        displayedSlots=null;$('first-available-slot').disabled=true;
        $('availability-status').textContent='جارٍ التحقق من التوفر...';$('booking-slots').innerHTML='';
        const slots=await api('availability',{...context(),date:selectedDate});
        if(generation!==slotGeneration)return;
        displayedSlots={slots:slots.sort((a,b)=>a.starts_at.localeCompare(b.starts_at)),index,date:selectedDate,generation};
        renderSlots();
    }
    /* مرة واحدة في التجديد: المواعيد المقترحة غير المتاحة تُفرَّغ مع تنبيه، ليختار الموظف أقرب موعد متاح. */
    async function checkProposed() {
        if(!isRenewalItem() || config.renewalChecked)return;
        config.renewalChecked=true;
        const missing=[],byDate={};
        for(const [i,visit] of state.visits.entries()) {
            if(!visit.starts_at)continue;
            const date=visit.starts_at.slice(0,10);
            byDate[date]??=await api('availability',{...context(),date});
            const slot=byDate[date].find(s=>s.starts_at.slice(0,16)===visit.starts_at);
            if(!slot?.available){missing.push(`الزيارة ${i+1} (${visit.starts_at})`);visit.starts_at='';}
        }
        if(missing.length)notice('dates',`مواعيد مقترحة غير متاحة: ${missing.join('، ')}. اختر لها أقرب موعد متاح من التقويم.`);
        renderCalendar();summary();
    }
    async function assignments() {
        $('worker-assignments').textContent='جارٍ التحقق من التوفر...';
        const replaced=[],unavailable=[],slotsFor={};
        // نفحص كل الزيارات أولًا لنذكر بالاسم كل موعد لم يعد متاحًا (لا أولها فقط)، ونفرّغه ليُعاد اختياره من التقويم.
        for(const [index,visit] of state.visits.entries()) {
            const date=visit.starts_at.slice(0,10);
            slotsFor[date]??=await api('availability',{...context(),date});
            const slot=slotsFor[date].find(s=>s.starts_at.slice(0,16)===visit.starts_at);
            if(!slot?.available)unavailable.push(index);
            else visit.slot=slot;
        }
        if(unavailable.length){
            const list=unavailable.map(i=>`الزيارة ${i+1} (${state.visits[i].starts_at.replace('T',' ')})`).join('، ');
            unavailable.forEach(i=>{state.visits[i].starts_at='';});
            renderCalendar();summary();
            $('worker-assignments').textContent='';
            throw new Error(`${unavailable.length>1?'هذه المواعيد لم تعد متاحة':'هذا الموعد لم يعد متاحًا'}: ${list}. ارجع لخطوة المواعيد واختر لها موعدًا آخر.`);
        }
        for(const [index,visit] of state.visits.entries()) {
            const slot=visit.slot;delete visit.slot;
            visit.workers=slot.workers;
            visit.worker_ids=visit.worker_ids.map(id=>slot.workers.some(w=>w.id===Number(id))?id:null);
            // العاملة المفضلة غير المتاحة: نقترح بديلة متاحة دون إفشال الطلب (مرة واحدة حتى لا نلغي اختيار الموظف لاحقًا).
            if(renewal && visit.preferred && !visit.preferredChecked){
                visit.preferredChecked=true;
                visit.preferred.forEach((id,w)=>{
                    if(!id || visit.worker_ids[w])return;
                    const name=renewal.preferred_workers.find(p=>p.id===id)?.name || ('#'+id);
                    const alternative=slot.workers.find(m=>!visit.worker_ids.includes(m.id));
                    visit.worker_ids[w]=alternative?.id ?? null;
                    replaced.push(`الزيارة ${index+1}: ${name} غير متاحة${alternative?' — اقترحنا '+alternative.name:' — لا توجد بديلة متاحة وستبقى بلا تعيين'}`);
                });
            }
        }
        if(renewal)notice('workers',replaced.length?'العاملة المفضلة غير متاحة في بعض المواعيد: '+replaced.join('؛ ')+'.':null);
        $('visits-total').textContent='مجموع عدد الزيارات: '+state.visits.length;
        const driverOptions=selected=>'<option value="">تخطي التعيين</option>'+config.drivers.map(d=>`<option value="${d.id}" ${Number(selected)===d.id?'selected':''}>${esc(d.name)}</option>`).join('');
        $('worker-assignments').innerHTML=state.visits.map((v,i)=>`<div class="booking-visit"><strong>الزيارة ${i+1} · ${esc(v.starts_at)}</strong>${Array.from({length:state.workers},(_,w)=>`<label>مقدم الخدمة ${w+1}</label><select class="form-select" data-visit="${i}" data-worker="${w}"><option value="">${state.item.booking_config?.auto_assign?'تعيين تلقائي':'تخطي التعيين'}</option>${v.workers.map(m=>`<option value="${m.id}" ${Number(v.worker_ids[w])===m.id?'selected':''}>${esc(m.name)}</option>`).join('')}</select>`).join('')}<div class="booking-fields mt-2"><div><label>سائق التوصيل</label><select class="form-select" data-driver="delivery_driver_id" data-visit="${i}">${driverOptions(v.delivery_driver_id)}</select></div><div><label>سائق الاستلام</label><select class="form-select" data-driver="pickup_driver_id" data-visit="${i}">${driverOptions(v.pickup_driver_id)}</select></div></div></div>`).join('');
        $('worker-assignments').querySelectorAll('[data-worker]').forEach(select=>select.onchange=()=>{state.visits[select.dataset.visit].worker_ids[select.dataset.worker]=select.value?Number(select.value):null;});
        $('worker-assignments').querySelectorAll('[data-driver]').forEach(select=>select.onchange=()=>{state.visits[select.dataset.visit][select.dataset.driver]=select.value?Number(select.value):null;});
    }
    const payments={cash:'نقدي',card_on_delivery:'المحطة (بطاقة)',cash_and_card:'النقدي والمحطة',bank_transfer:'التحويل البنكي',cheque:'شيك',customer_balance:'رصيد العميل'};
    function renderPayments() {
        const methods=state.quote?.payment_methods || [];
        let previous=document.querySelector('[name=payment_method]:checked')?.value;
        // في التجديد تُختار طريقة الدفع السابقة مبدئيًا إن كانت متاحة لهذا العنصر.
        if(!previous && isRenewalItem() && methods.includes(renewal.payment_method))previous=renewal.payment_method;
        $('payment-methods').innerHTML=(state.quote?.payment_methods || []).map((method,i)=>`<label class="booking-option d-block"><input type="radio" name="payment_method" value="${method}" ${previous?previous===method?'checked':'':i===0?'checked':''} ${method==='customer_balance' && (!config.canCollect || Number(state.customer?.hourly_wallet_balance || 0)*100<state.quote.total)?'disabled':''}> ${payments[method]} ${method==='customer_balance'?' · المتاح '+money(state.customer?.hourly_wallet_balance):''}</label>`).join('');
        document.querySelectorAll('[name=payment_method]').forEach(r=>r.onchange=paymentChanged);paymentChanged();
    }
    function paymentChanged() {const method=document.querySelector('[name=payment_method]:checked')?.value;if($('collect-label')){$('collect-label').hidden=!['cash','card_on_delivery','cash_and_card'].includes(method);if($('collect-label').hidden)$('collect-now').checked=false;}$('cash-part').hidden=method!=='cash_and_card' || !$('collect-now')?.checked;}
    if($('collect-now'))$('collect-now').onchange=paymentChanged;
    $('all-workers').onchange=async()=>{state.allWorkers=$('all-workers').checked;try{await assignments();}catch(e){fail(e);}};
    $('apply-coupon').onclick=()=>{state.coupon=$('coupon-code').value.trim().toUpperCase();refreshQuote().catch(fail);};
    $('booking-back').onclick=()=>{if(state.step<=(renewal?2:1))return;clearError();state.step--;showStep();};
    $('booking-next').onclick=async()=>{
        if(busy)return;clearError();busy=true;$('booking-next').disabled=true;
        try {
            if(state.step===1){
                if(state.customer_mode==='new'){if(!$('new-customer').reportValidity())return;state.customer=formData('new-customer');}
                if(!state.customer)throw new Error('اختر العميل أولًا.');
                const key=state.customer_mode==='existing'?'existing:'+state.customer.id:JSON.stringify([state.customer.type,state.customer.first_name,state.customer.last_name,state.customer.company_name,state.customer.email,state.customer.phone]);
                const changed=key!==addressCustomerKey;
                if(changed){resetAddressCustomer();addressCustomerKey=key;}
                addresses=state.customer_mode==='existing'?await api('addresses?user_id='+state.customer.id,null,'GET'):[];
                if(changed && config.renewal && state.customer.id===config.renewal.customer.id){savedAddress=addresses.find(a=>a.id===config.renewal.address_id) || null;state.address=savedAddress;}
                if(savedAddress && !addresses.some(a=>a.id===savedAddress.id)){savedAddress=null;state.address=null;resetService();}
                addressMode(changed?(addresses.length?'existing':'new'):(addresses.length?state.address_mode:'new'));renderAddresses();
            }
            if(state.step===2){if(state.address_mode==='new'){
                const f=$('new-address').elements;
                if(!f.latitude.value || !f.longitude.value){$('address-coordinates').open=true;throw new Error('حدد الموقع على الخريطة أو برابط Google Maps، أو أدخل الإحداثيات يدويًا.');}
                if(!f.latitude.checkValidity() || !f.longitude.checkValidity())$('address-coordinates').open=true;
                if(!$('new-address').reportValidity())return;const nextAddress=formData('new-address');if(JSON.stringify(nextAddress)!==JSON.stringify(state.address))resetService();state.address=nextAddress;
            }if(!state.address)throw new Error('اختر العنوان أولًا.');await loadCatalog();}
            if(state.step===3){if(!state.category)throw new Error('اختر الخدمة أولًا.');renderItems();}
            if(state.step===4){if(!state.item)throw new Error('اختر عنصر الخدمة أولًا.');await refreshQuote();await checkProposed();$('visit-index').innerHTML=state.visits.map((v,i)=>`<option value="${i}">الزيارة ${i+1}</option>`).join('');$('booking-slots').innerHTML='';selectedDate=state.visits[0]?.starts_at?.slice(0,10) || '';if(selectedDate)month=new Date(Number(selectedDate.slice(0,4)),Number(selectedDate.slice(5,7))-1,1);$('availability-status').textContent='اختر يومًا من التقويم لعرض الفترات المتاحة.';renderCalendar();if(selectedDate)await loadSlots();}
            if(state.step===5){if(state.visits.some(v=>!v.starts_at))throw new Error('حدد موعد كل زيارة.');await refreshQuote();await assignments();renderPayments();}
            state.step++;showStep();
        }catch(e){fail(e);}finally{busy=false;$('booking-next').disabled=false;summary();}
    };
    $('place-order').onclick=async()=>{
        if(busy || !state.quote)return;clearError();busy=true;summary();
        try {
            const payment=document.querySelector('[name=payment_method]:checked');
            if(!payment || payment.disabled)throw new Error('اختر طريقة دفع متاحة.');
            const data={...context(),booking_token:state.bookingToken,customer:state.customer_mode==='new'?state.customer:undefined,
                visits:state.visits.map(({workers,preferred,preferredChecked,...visit})=>visit),coupon_code:state.coupon || null,payment_method:payment.value,
                collect_now:Boolean($('collect-now')?.checked),cash_amount:$('cash-amount').value || null,notify_email:$('notify-email').checked,notify_app:$('notify-app').checked,notes:$('order-notes').value};
            if($('override-price')?.checked)data.manual_total=Number($('manual-total').value);
            if(renewal && state.customer_mode==='existing' && state.customer.id===renewal.customer.id)data.renewed_from_order_id=renewal.id;
            const result=await api('store',data);window.location.assign(result.redirect);
        }catch(e){fail(e);busy=false;summary();}
    };
    function markLocation(location) {
        const fields=$('new-address').elements;
        const lat=typeof location.lat==='function'?location.lat():location.lat;
        const lng=typeof location.lng==='function'?location.lng():location.lng;
        fields.latitude.value=lat;fields.longitude.value=lng;
        if(!map)return;
        const point={lat:Number(lat),lng:Number(lng)};
        if(provider==='google') {
            map.setCenter(point);map.setZoom(16);
            if(marker)marker.setPosition(point);
            else {
                marker=new google.maps.Marker({map,position:point,draggable:true});
                marker.addListener('dragend',e=>geocode({location:e.latLng}).catch(fail));
                marker.addListener('click',()=>geocode({location:marker.getPosition()}).catch(fail));
            }
        } else {
            map.setView([point.lat,point.lng],16);
            if(marker)marker.setLatLng([point.lat,point.lng]);
            else {
                marker=L.marker([point.lat,point.lng],{draggable:true}).addTo(map);
                marker.on('dragend',()=>geocode({location:marker.getLatLng()}).catch(fail));
            }
        }
    }
    /* نتيجة موحدة من المزودَين: Google في المتصفح، أو OpenStreetMap عبر الخادم. */
    async function lookup(request) {
        if(provider==='google') {
            if(!geocoder)throw new Error('الخريطة غير مفعلة. يمكنك إدخال الإحداثيات والعنوان يدويًا.');
            const {results}=await geocoder.geocode(request);
            if(!results.length)throw new Error('لم يتم العثور على العنوان.');
            const result=results[0],point=result.geometry.location;
            return {lat:point.lat(),lng:point.lng(),address:result.formatted_address,parts:result.address_components.map(c=>c.long_name)};
        }
        const found=await api('geocode',request.location
            ? {latitude:coordinate(request.location,'lat'),longitude:coordinate(request.location,'lng')}
            : {query:request.address});
        return {lat:found.latitude,lng:found.longitude,address:found.address,parts:found.parts};
    }
    const coordinate=(location,axis)=>typeof location[axis]==='function'?location[axis]():location[axis];
    async function geocode(request) {
        const generation=++geocodeGeneration;
        if(request.location)markLocation(request.location);
        const result=await lookup(request);
        if(generation!==geocodeGeneration)return;
        const fields=$('new-address').elements;
        markLocation(request.location || {lat:result.lat,lng:result.lng});fields.address.value=result.address;
        const parts=result.parts;
        const city=[...fields.city_id.options].find(o=>o.value && parts.includes(o.textContent));
        fields.city_id.value=city?.value || '';districts();
        const normalize=s=>s.replace(/^حي\s+/,'').trim();const district=[...fields.hourly_district_id.options].find(o=>parts.some(p=>normalize(p)===normalize(o.textContent)));if(district)fields.hourly_district_id.value=district.value;
        $('map-hint').textContent=city && district?'📍 تم تحديد الموقع وتعبئة العنوان. راجع التفاصيل قبل المتابعة.':'📍 تم تحديد الموقع. اختر المدينة والحي من القائمة لاستكمال العنوان.';
    }
    function initMap() {
        // الخريطة تُبنى بعد ظهور قسمها فعلًا، وإلا حسبت مقاسها خطأ.
        if(map){provider==='google'?google.maps.event.trigger(map,'resize'):map.invalidateSize();return;}
        if(state.step!==2 || state.address_mode!=='new')return;
        if(provider==='google') {
            if(!window.google?.maps)return;
            map=new google.maps.Map($('booking-map'),{center:RIYADH,zoom:11});
            geocoder=new google.maps.Geocoder();
            map.addListener('click',e=>geocode({location:e.latLng}).catch(fail));
        } else {
            if(!window.L)return;
            map=L.map($('booking-map')).setView([RIYADH.lat,RIYADH.lng],11);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OpenStreetMap',maxZoom:19}).addTo(map);
            map.on('click',e=>geocode({location:{lat:e.latlng.lat,lng:e.latlng.lng}}).catch(fail));
        }
        const f=$('new-address').elements;if(f.latitude.value && f.longitude.value)markLocation({lat:Number(f.latitude.value),lng:Number(f.longitude.value)});
    }
    /* إحداثيات ملصقة كما يعطيها Google Maps: «24.7136, 46.6753» أو «24°42'49.0"N 46°40'31.1"E»، بأرقام عربية أو فاصلة عربية. */
    function parseCoordinates(text) {
        const s=String(text).replace(/[٠-٩]/g,d=>'٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/٫/g,'.').replace(/،/g,',').trim();
        let lat=null,lng=null,m;
        if((m=s.match(/^\(?\s*(-?\d{1,3}\.\d+)\s*[,\s]\s*(-?\d{1,3}\.\d+)\s*\)?$/))){lat=+m[1];lng=+m[2];}
        else if((m=s.match(/^(\d{1,3})\s*°\s*(\d{1,2})\s*['′]\s*(\d{1,2}(?:\.\d+)?)\s*["″]?\s*([NS])[\s,]*(\d{1,3})\s*°\s*(\d{1,2})\s*['′]\s*(\d{1,2}(?:\.\d+)?)\s*["″]?\s*([EW])$/i))) {
            const dms=(d,mi,se,h)=>(+d + mi/60 + se/3600)*(/[SW]/i.test(h)?-1:1);
            lat=dms(m[1],m[2],m[3],m[4]);lng=dms(m[5],m[6],m[7],m[8]);
        }
        return lat!==null && Math.abs(lat)<=90 && Math.abs(lng)<=180 ? {lat,lng} : null;
    }
    async function locateAddress(input) {
        try {
            clearError();input=input.trim();if(!input)throw new Error('أدخل اسم العنوان أو رابط الموقع أولًا.');
            const point=parseCoordinates(input);
            if(point) {await geocode({location:point});return;}
            if(/^https?:/.test(input)) {
                const generation=++geocodeGeneration;
                const point=await api('map-link',{url:input});const location={lat:point.latitude,lng:point.longitude};
                if(generation!==geocodeGeneration)return;
                markLocation(location);
                if(provider==='osm' || geocoder)await geocode({location});
                else $('map-hint').textContent='📍 تم استخراج الإحداثيات من الرابط. أكمل المدينة والحي وتفاصيل العنوان.';
            } else await geocode({address:input});
        }catch(e){fail(e);}
    }
    // إحداثيات مكتوبة يدويًا: تحرّك الخريطة وتعبّئ المدينة والحي كما لو اختير الموقع عليها
    ['address-latitude','address-longitude'].forEach(id=>$(id).addEventListener('change',()=>{
        const f=$('new-address').elements;
        if(f.latitude.value==='' || f.longitude.value==='' || !f.latitude.checkValidity() || !f.longitude.checkValidity())return;
        clearError();geocode({location:{lat:Number(f.latitude.value),lng:Number(f.longitude.value)}}).catch(fail);
    }));
    $('locate-address').onclick=()=>locateAddress($('map-search').value);
    $('locate-map-url').onclick=()=>locateAddress($('map-url').value);
    ['map-search','map-url'].forEach(id=>$(id).onkeydown=e=>{if(e.key==='Enter'){e.preventDefault();locateAddress($(id).value);}});
    function mapUnavailable() {$('map-hint').textContent='الخريطة التفاعلية غير متاحة حاليًا. يمكنك لصق رابط الموقع أو إدخال الإحداثيات وتفاصيل العنوان يدويًا.';$('booking-map').hidden=true;$('address-coordinates').open=true;}
    /* Leaflet وبلاطات OpenStreetMap: بلا مفتاح؛ الترميز الجغرافي يمر عبر الخادم. */
    function loadOsmMap() {
        provider='osm';map=null;marker=null;geocoder=null;
        const style=document.createElement('link');
        style.rel='stylesheet';style.href='https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css';
        document.head.append(style);
        const script=document.createElement('script');
        script.src='https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js';
        script.async=true;script.onload=initMap;script.onerror=mapUnavailable;document.head.append(script);
    }
    /* رفض Google للمفتاح لا يترك الشاشة بلا خريطة: نسقط إلى المجانية مرة واحدة. */
    function googleFailed() {
        if(provider!=='google')return mapUnavailable();
        $('booking-map').innerHTML='';loadOsmMap();
    }
    if(provider==='google') {
        window.initHourlyBookingMap=initMap;window.gm_authFailure=googleFailed;
        const script=document.createElement('script');
        script.src='https://maps.googleapis.com/maps/api/js?key='+encodeURIComponent(config.mapsKey)+'&language=ar&callback=initHourlyBookingMap&loading=async';
        script.async=true;script.onerror=googleFailed;document.head.append(script);
    } else loadOsmMap();
    $('new-customer').onsubmit=e=>e.preventDefault();$('new-address').onsubmit=e=>e.preventDefault();
    districts();showStep();if(renewal)$('booking-next').onclick();else customers().catch(fail);
})();
