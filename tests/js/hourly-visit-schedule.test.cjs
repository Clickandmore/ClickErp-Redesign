const {test}=require('node:test');const assert=require('node:assert/strict');const vm=require('node:vm');const {readFileSync}=require('node:fs');
test('schedule modal preselects appointment and submits selected calendar date and time',async()=>{
    const make=()=>({value:'',children:[],dataset:{},classList:{toggle(){}},setAttribute(k,v){this[k]=v;},addEventListener(){},appendChild(c){this.children.push(c);},replaceChildren(){this.children=[];}});
    const nodes={},node=id=>nodes[id] ||= make();const form=node('visit-schedule-form');form.elements={starts_at:{},expected_start_at:{}};form.reset=()=>{};form.getAttribute=k=>form[k];
    let opened=false,reloaded=false,posted;
    const context={document:{getElementById:node,createElement:make,body:{appendChild(){}}},window:{hourlyScheduleConfig:{visits:{1:'2026-10-14T08:00'},min:'2026-09-20',max:'2026-11-30',locale:'ar',url:'/visits/VISIT_ID/schedule'}},bootstrap:{Modal:{getOrCreateInstance:()=>({show(){opened=true;}})}},FormData:class{},location:{reload(){reloaded=true;}},fetch:async url=>{posted=url;return {ok:true,json:async()=>({})};}};
    vm.runInNewContext(readFileSync('public/admin/assets/js/hourly-visit-schedule.js','utf8'),context);
    context.window.openHourlySchedule(1);assert.equal(opened,true);assert.equal(node('vs-days').children.length,42);assert.equal(form.elements.starts_at.value,'2026-10-14T08:00');
    node('vs-days').children.find(b=>b.dataset.date==='2026-10-15').onclick();node('vs-hour').value='09';node('vs-minute').value='30';node('vs-minute').onchange();
    assert.equal(form.elements.starts_at.value,'2026-10-15T09:30');assert.equal(node('vs-preview').textContent,'09:30 15/10/2026');
    await form.onsubmit({preventDefault(){}});assert.equal(posted,'/visits/1/schedule');assert.equal(reloaded,true);
});
