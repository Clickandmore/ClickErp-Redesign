const {test}=require('node:test');
const assert=require('node:assert/strict');
const vm=require('node:vm');
const {readFileSync}=require('node:fs');
const {webcrypto}=require('node:crypto');

async function openWizard(renewal) {
    const nodes={};
    const element=()=>({value:'',hidden:false,innerHTML:'',textContent:'',dataset:{},classList:{toggle(){}},
        append(){},appendChild(){},querySelectorAll(){return[];},reset(){},reportValidity(){return true;}});
    const get=id=>nodes[id]??=element();
    get('new-address').elements=new Proxy({}, {get:(fields,key)=>fields[key]??=element()});
    const steps=Array.from({length:6},(_,i)=>Object.assign(element(),{dataset:{step:String(i+1)}}));
    const mode={value:'new'};
    const requests=[];
    const config={base:'/panel/add-order',csrf:'test',districts:[],renewal};
    const context={window:{hourlyBookingConfig:config},crypto:webcrypto,console,Uint8Array,
        document:{getElementById:get,createElement:element,head:element(),
            querySelectorAll:selector=>selector==='[data-step]'?steps:selector==='[name=customer_mode]'?[mode]:[]},
        fetch:async(url)=>{requests.push(url);return {ok:true,json:async()=>url.includes('/addresses?')
            ?[{id:7,address:'Saved address',city_name:'Riyadh',district:'District'}]:{data:[],last_page:1}};}};
    vm.runInNewContext(readFileSync('public/admin/assets/js/hourly-booking.js','utf8'),context);
    await new Promise(resolve=>setImmediate(resolve));
    return {get,steps,requests,mode};
}
test('renewal opens the same customer address without a customer search or change step',async()=>{
    const h=await openWizard({id:40,customer:{id:19,name:'Original customer'},address_id:7});
    assert.deepEqual(h.requests,['/panel/add-order/addresses?user_id=19']);
    assert.equal(h.steps[1].hidden,false);
    assert.equal(h.get('booking-back').hidden,true);
    assert.match(h.get('booking-summary').innerHTML,/Original customer/);
    assert.match(h.get('booking-summary').innerHTML,/Saved address/);
    h.mode.onchange();
    await h.get('search-customers').onclick();
    h.get('booking-back').onclick();
    assert.equal(h.steps[1].hidden,false);
    assert.equal(h.requests.length,1);
    assert.match(h.get('booking-summary').innerHTML,/Original customer/);
});
test('ordinary booking still opens customer selection',async()=>{
    const h=await openWizard(null);
    assert.match(h.requests[0],/\/customers\?/);
    assert.equal(h.steps[0].hidden,false);
    assert.equal(h.steps[1].hidden,true);
});