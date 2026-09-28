const {test}=require('node:test');
const assert=require('node:assert/strict');
const vm=require('node:vm');
const {readFileSync}=require('node:fs');
test('add-worker modal previews decimal and zero fees and posts to the operations URL',async()=>{
    const nodes={},node=id=>nodes[id] ||= {value:'',dataset:{},addEventListener(){},setAttribute(){},replaceChildren(){this.value='';},add(){}};
    const form=node('add-worker-form');form.elements=Object.fromEntries(['visit_ids[]','expected_price','expected_workers'].map(k=>[k,{}]));form.reset=()=>{};form.reportValidity=()=>true;form.getAttribute=()=>'/operations';
    const response={workers:[{id:2,name:'Worker',teams:[{id:1,name:'Team'}],category_ids:null}],price:400,default_fee:400,workers_count:1,team_id:1,category_id:1,categories:[{id:1,name:'Hourly'}],date:'Tomorrow'};
    let postUrl,opened=false,reloaded=false;
    const context={document:{getElementById:node,body:{appendChild(){}}},window:{hourlyAddWorkerConfig:{url:'/visits/VISIT_ID/workers',labels:{choose:'Choose',all:'All'}}},Option:class {},FormData:class {},
        bootstrap:{Modal:{getOrCreateInstance:()=>({show(){opened=true;}})}},location:{reload(){reloaded=true;}},
        fetch:async(url,options)=>{if(options.method==='POST')postUrl=url;return {ok:true,json:async()=>response};}};
    vm.runInNewContext(readFileSync('public/admin/assets/js/hourly-add-worker.js','utf8'),context);
    await context.window.openHourlyAddWorker(8);
    assert.equal(opened,true);assert.equal(node('aw-count').textContent,1);assert.equal(node('aw-new').textContent,'800.00');
    node('aw-fee').value='0';node('aw-fee').oninput();assert.equal(node('aw-new').textContent,'400.00');
    node('aw-fee').value='25.75';node('aw-fee').oninput();assert.equal(node('aw-new').textContent,'425.75');
    await form.onsubmit({preventDefault(){}});assert.equal(postUrl,'/operations');assert.equal(reloaded,true);
});
