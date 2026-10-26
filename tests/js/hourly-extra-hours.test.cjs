const {test}=require('node:test');
const assert=require('node:assert/strict');
const vm=require('node:vm');
const {readFileSync}=require('node:fs');
function harness(fail=false) {
    const nodes={},calls=[];
    const node=id=>nodes[id] ||= {value:'1',textContent:'',hidden:false,disabled:false,dataset:{},listeners:{},
        addEventListener(k,fn){this.listeners[k]=fn;},showModal(){this.open=true;}};
    const button=node('button'); button.dataset={extraHour:'9',visitNumber:'1'};
    node('hourly-extra-config').textContent=JSON.stringify({csrf:'csrf',urls:{extraHourQuote:'/visits/__VISIT__/extra-hour',extraHour:'/visits/__VISIT__/extra-hour'}});
    let reloads=0;
    vm.runInNewContext(readFileSync('public/web/assets/js/hourly-extra-hours.js','utf8'),{
        document:{getElementById:id=>['hourly-bank-form','hourly-checkout-config'].includes(id)?null:node(id),querySelectorAll:s=>s==='[data-extra-hour]'?[button]:[]},
        window:{crypto:{randomUUID:()=> 'same-token'},location:{reload(){reloads++;}}},FormData:class{},
        fetch:async(url,options)=>{
            calls.push({url,options});
            return {ok:!fail,status:fail?422:200,json:async()=>fail?{message:'غير متاح'}:{data:{hours:2,amount:120,ends_at:'2030-01-07 14:00',planned_hours:6}}};
        }});
    return {node,calls,button,reloads:()=>reloads,async open(){button.listeners.click();await new Promise(setImmediate);}};
}
test('extra hours previews cost before confirmation and submits a stable request token',async()=>{
    const h=harness();await h.open();
    assert.match(h.node('hr-extra-summary').textContent,/120.00/);
    assert.match(h.node('hr-extra-summary').textContent,/14:00/);
    assert.equal(h.node('hr-extra-confirm').disabled,false);
    assert.equal(h.calls.length,1);
    await h.node('hr-extra-confirm').listeners.click();
    assert.deepEqual(JSON.parse(h.calls[1].options.body),{hours:2,request_token:'same-token'});
    assert.equal(h.calls[1].options.headers['X-CSRF-TOKEN'],'csrf');
    assert.equal(h.reloads(),1);
});
test('unavailable extension displays the reason and prevents confirmation',async()=>{
    const h=harness(true);await h.open();
    assert.equal(h.node('hr-extra-confirm').disabled,true);
    assert.equal(h.node('hr-extra-error').textContent,'غير متاح');
    await h.node('hr-extra-confirm').listeners.click();
    assert.equal(h.calls.length,1);
});
