@php
    // @json في Laravel 8 يقسم وسائطه على الفواصل، فالمصفوفة المكتوبة داخله تكسر القالب؛ تُحسب هنا وتُمرَّر متغيرًا.
    $hourlyPaymentNames = collect(['cash', 'card_on_delivery', 'bank_transfer', 'moyasar'])
        ->mapWithKeys(fn ($m) => [$m => __('hourly.' . $m)]);
@endphp
<script>
(()=>{
const product=document.querySelector('[name="hourly_package_id"]'),form=product.closest('form'),channel=form.elements.payment_channel,district=form.elements.district_id,address=form.elements.address_id,workers=form.elements.workers_per_visit;
const extras=document.getElementById('hourly-steppers'),notes=document.getElementById('hourly-order-notes'),error=document.getElementById('hourly-config-error'),quote=document.getElementById('hourly-quote'),submit=form.querySelector('button[type="submit"]')||form.querySelector('button:not([type])');let config=null,version=0;
const paymentNames=@json($hourlyPaymentNames);
const base=@json(url('/admin/hourly/service-items'));
function districts(){const city=address.selectedOptions[0]?.dataset.city;district.replaceChildren(new Option('اختر الحي',''));(config?.districts||[]).filter(d=>String(d.city_id)===String(city)).forEach(d=>district.add(new Option(d.name,d.id)));workerChoices();}
function workerChoices(){const select=form.elements.preferred_maid_id;select.replaceChildren(new Option('بدون تفضيل',''));(config?.worker_choices||[]).filter(w=>String(w.district_id)===district.value).forEach(w=>select.add(new Option(w.name,w.id)));}
async function load(){const v=++version;++pricing;submit.disabled=true;error.hidden=true;quote.hidden=true;config=null;try{const response=await fetch(base+'/'+product.value+'/booking-config?payment_channel='+channel.value+(address.selectedOptions[0]?.dataset.city?'&city_id='+address.selectedOptions[0].dataset.city:''),{headers:{Accept:'application/json'}});const result=await response.json();if(v!==version)return;if(!response.ok)throw Error(result.message||'تعذر تحميل إعدادات الخدمة');config=result.data;
const attributeBox=document.getElementById('hourly-product-attributes');attributeBox.replaceChildren();form.elements.attribute_id.value=config.attribute_id||'';
(config.attributes||[]).forEach(group=>{const label=document.createElement('label'),select=document.createElement('select');label.textContent=group.name;select.className='form-select mb-3';select.add(new Option('اختر الخيار',''));group.options.forEach(option=>option.items.forEach(item=>{const entry=new Option(option.name+' — '+item.name+' — '+item.total+' ر.س',item.id);entry.selected=String(item.id)===product.value;select.add(entry);}));select.addEventListener('change',()=>{if(!select.value)return;product.value=select.value;load();});attributeBox.append(label,select);});
extras.replaceChildren();workers.min=config.workers_per_visit;workers.value=config.workers_per_visit;workers.max=20;
config.steppers.forEach(s=>{if(s.key==='workers'){workers.min=Math.max(s.min,config.workers_per_visit);workers.max=s.max;workers.value=workers.min;return;}const label=document.createElement('label'),input=document.createElement('input');label.textContent=s.label;input.name='quantity';input.type='number';input.min=s.min;input.max=s.max;input.value=s.min;input.required=true;input.className='form-control mb-3';extras.append(label,input);});
form.elements.payment_method.replaceChildren();config.payment_methods.forEach(m=>form.elements.payment_method.add(new Option(paymentNames[m]||m,m)));
notes.replaceChildren();config.notes.forEach(n=>{const p=document.createElement('p');p.textContent=n.text;notes.append(p);});notes.hidden=!config.notes.length;
document.getElementById('hourly-worker-choice').hidden=!config.customer_worker_selection;form.elements.preferred_maid_id.disabled=!config.customer_worker_selection;
districts();submit.disabled=!config.payment_methods.length;await price();
}catch(e){if(v===version){error.textContent=e.message;error.hidden=false;submit.disabled=true;}}}
let pricing=0;async function price(){if(!config)return;const v=++pricing;submit.disabled=true;const params=new URLSearchParams({workers:workers.value||config.workers_per_visit,quantity:form.elements.quantity?.value||1,payment_channel:channel.value});try{const response=await fetch(base+'/'+product.value+'/booking-config?'+params,{headers:{Accept:'application/json'}}),r=await response.json();if(v!==pricing)return;if(!response.ok)throw Error(Object.values(r.errors||{}).flat().join('، ')||r.message);const q=r.data.quote;quote.textContent='إجمالي الباقة: '+(q.total/100).toFixed(2)+' ر.س'+(config.display.show_tax_separately?' — الضريبة: '+(q.tax/100).toFixed(2)+' ر.س':'')+(q.price_display==='per_visit'?' — المستحق إلكترونيًا الآن: '+(q.charge_now/100).toFixed(2)+' ر.س':'');quote.hidden=false;error.hidden=true;submit.disabled=!config.payment_methods.length;}catch(e){if(v===pricing){error.textContent=e.message;error.hidden=false;submit.disabled=true;}}}
product.addEventListener('change',load);channel.addEventListener('change',load);address.addEventListener('change',load);district.addEventListener('change',workerChoices);workers.addEventListener('change',price);extras.addEventListener('change',price);if(product.value)load();
})();
</script>
