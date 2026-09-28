const {test}=require('node:test'),assert=require('node:assert/strict'),vm=require('node:vm'),fs=require('node:fs');

test('free visit options remain optional and unassigned warning is acknowledged before reloading',async()=>{
    const node=()=>({value:'',type:'',dataset:{},events:{},addEventListener(k,f){this.events[k]=f;},querySelectorAll(){return [];},querySelector(){return null;}});
    const nodes={},get=id=>nodes[id]??=node(),form=get('order-operation-form');
    const date=Object.assign(node(),{type:'datetime-local'}),fallback=Object.assign(node(),{type:'checkbox'}),notice=Object.assign(node(),{type:'checkbox'});
    const fields=Object.assign(node(),{dataset:{opFields:'free_visit'},querySelectorAll(){return [date,fallback,notice];}});
    form.elements={driver_role:node(),driver_time:node()};form.getAttribute=()=>'/operations';get('operation-action').value='free_visit';
    const events=[];const swal={async fire(options){events.push(options.icon);return {isConfirmed:true};}};
    vm.runInNewContext(fs.readFileSync('public/admin/assets/js/hourly-order-operations.js','utf8'),{
        document:{getElementById:get,querySelectorAll:s=>s==='[data-op-fields]'?[fields]:[]},
        window:{Swal:swal},Swal:swal,FormData:class{append(){}},
        fetch:async()=>({ok:true,json:async()=>({warning:'أُضيفت بدون تعيين'})}),location:{reload(){events.push('reload');}}
    });
    assert.equal(date.required,true);assert.equal(fallback.required,false);assert.equal(notice.required,false);
    await form.onsubmit({preventDefault(){}});
    assert.deepEqual(events,['warning','warning','reload']);
});
