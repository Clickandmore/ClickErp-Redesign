const {test}=require('node:test'),assert=require('node:assert/strict'),vm=require('node:vm'),fs=require('node:fs');
test('driver assignment shows visit dates and selects delivery or pickup time without stale bulk values',async()=>{
    const node=()=>({dataset:{},children:[],value:'',hidden:false,events:{},classList:{},addEventListener(k,fn){this.events[k]=fn;},querySelectorAll(){return [];},querySelector(){return null;},replaceChildren(){this.children=[];},appendChild(n){this.children.push(n);}});
    const ids={},get=id=>ids[id]??=node();
    const boxes=[{...node(),checked:true,value:'1',dataset:{start:'2026-09-21 08:00',end:'2026-09-21 12:00',delivery:'2026-09-21 07:45:00',pickup:''}},
        {...node(),checked:false,value:'2',dataset:{start:'2026-09-22 09:00',end:'2026-09-22 13:00'}}];
    const form=get('order-operation-form');form.elements={driver_id:node(),driver_role:node(),driver_time:node()};form.elements.driver_role.value='delivery';
    get('operation-action').value='assign_driver';
    vm.runInNewContext(fs.readFileSync('public/admin/assets/js/hourly-order-operations.js','utf8'),{Option:function(text,value){this.textContent=text;this.value=value;},URLSearchParams,fetch:async()=>({ok:true,json:async()=>({drivers:[]})}),document:{getElementById:get,createElement:node,querySelectorAll:s=>s==='[data-visit-select]'?boxes:[]}});
    assert.equal(form.elements.driver_time.value,'07:45');
    assert.match(get('driver-visit-schedule').children[0].textContent,/2026-09-21 08:00/);
    form.elements.driver_role.value='pickup';form.elements.driver_role.events.change();assert.equal(form.elements.driver_time.value,'12:00');
    boxes[1].checked=true;form.elements.driver_role.events.change();assert.equal(form.elements.driver_time.value,'');assert.equal(get('driver-visit-schedule').children.length,2);
    boxes[0].checked=false;form.elements.driver_role.value='delivery';form.elements.driver_role.events.change();assert.equal(form.elements.driver_time.value,'09:00');
    boxes[1].dataset.start='';form.elements.driver_role.events.change();assert.equal(form.elements.driver_time.value,'');assert.match(get('driver-visit-schedule').children[0].textContent,/لم يحدد موعد/);
    await new Promise(resolve=>setImmediate(resolve));
});
