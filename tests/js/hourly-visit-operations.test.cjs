const {test}=require('node:test'),assert=require('node:assert/strict'),vm=require('node:vm'),fs=require('node:fs');

test('bulk targets remain fixed and failed requests retry with the same operation identifier',async()=>{
    const node=()=>({value:'',dataset:{},style:{},events:{},hidden:false,checked:false,disabled:false,classList:{toggle(){}},addEventListener(k,f){this.events[k]=f;},querySelectorAll(){return [];},querySelector(){return null;},closest(){return node();}});
    const ids={},get=id=>ids[id]??=node(),root=get('visit-operations'),form=get('vl-batch-form');
    const boxes=[Object.assign(node(),{value:'11',checked:true}),Object.assign(node(),{value:'12',checked:false})];
    root.dataset.endpoint='/batch';root.querySelectorAll=s=>s==='[data-select-visit]'?boxes:[];
    form.elements={action:Object.assign(node(),{value:'price'}),batch_id:node(),reason:Object.assign(node(),{value:'تصحيح'})};form.reset=()=>{};
    const tile=Object.assign(node(),{dataset:{pickAction:'price',label:'تغيير السعر',icon:'fa-tag'}});
    get('vl-modal').querySelector=s=>s.includes('"price"')?tile:null;
    get('vl-modal').querySelectorAll=s=>s==='[data-pick-action]'?[tile]:[];
    let sequence=0;const requests=[];
    class FormData {constructor(f){this.data={batch_id:f.elements.batch_id.value};}append(k,v){(this.data[k]??=[]).push(v);}set(k,v){this.data[k]=v;}}
    vm.runInNewContext(fs.readFileSync('public/admin/assets/js/hourly-visit-operations.js','utf8'),{
        document:{getElementById:get,querySelector:()=>({content:'session-token'})},crypto:{randomUUID:()=>`operation-${++sequence}`},bootstrap:{Modal:{getOrCreateInstance:()=>({show(){}})}},
        localStorage:{getItem(){return null;}},FormData,window:{alert(){},confirm(){return true;}},fetch:async(url,options)=>{assert.equal(options.headers['X-CSRF-TOKEN'],'session-token');assert.equal(options.credentials,'same-origin');requests.push(options.body.data);return {status:419};}
    });
    get('vl-open-batch').events.click();tile.events.click();boxes[0].checked=false;boxes[1].checked=true;
    await form.events.submit({preventDefault(){}});await form.events.submit({preventDefault(){}});
    assert.equal(requests.length,2);assert.deepEqual(requests[0]['visit_ids[]'],['11']);assert.equal(requests[0].batch_id,requests[1].batch_id);
    assert.equal(get('vl-error').hidden,false);assert.equal(get('vl-submit').disabled,false);
    assert.equal(requests[0]._token,'session-token');
    assert.match(get('vl-error').textContent,/انتهت صلاحية الجلسة/);
    get('vl-open-batch').events.click();assert.notEqual(form.elements.batch_id.value,requests[0].batch_id);
    get('vl-select-all').events.change({target:{checked:true}});assert.ok(boxes.every(b=>b.checked));assert.equal(get('vl-selected').textContent,2);
});
