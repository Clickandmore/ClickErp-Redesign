const {test}=require('node:test');
const assert=require('node:assert/strict');
const {readFileSync}=require('node:fs');
const vm=require('node:vm');
const source=readFileSync('public/admin/assets/js/hourly-operational-notes.js','utf8');

test('opens modal with a default audience even without a checked radio or randomUUID',()=>{
    let click,opened=false;
    const admin={value:'admin',checked:false,dataset:{hint:'Admin only'},addEventListener(){}};
    const modal={addEventListener(){}};
    const form={elements:{visit_id:{},note_token:{}},reset(){},addEventListener(){},
        querySelector:selector=>selector.endsWith(':checked')?null:admin,querySelectorAll:()=>[admin]};
    const nodes={'operational-note-modal':modal,'operational-note-form':form,'on-error':{},'on-save':{},'on-hint':{}};
    const button={dataset:{operationalNote:'42'},addEventListener:(event,handler)=>click=handler};
    vm.runInNewContext(source,{
        document:{currentScript:{dataset:{}},body:{appendChild(){}},getElementById:id=>nodes[id],querySelectorAll:()=>[button]},
        crypto:{getRandomValues:bytes=>bytes.fill(7)},
        bootstrap:{Modal:{getOrCreateInstance:()=>({show(){opened=true;}})}}
    });
    click();
    assert.equal(opened,true);
    assert.equal(admin.checked,true);
    assert.equal(form.elements.visit_id.value,'42');
    assert.match(form.elements.note_token.value,/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/);
});
