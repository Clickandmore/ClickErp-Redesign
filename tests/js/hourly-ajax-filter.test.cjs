const {test}=require('node:test');
const assert=require('node:assert/strict');
const vm=require('node:vm');
const {readFileSync}=require('node:fs');
function field(name,value,extra={}){return {name,value,type:'text',disabled:false,multiple:false,hidden:false,closest:()=>extra.inHidden?{}:null,...extra};}
function harness(responder,fields){
    const nodes={},events={},windowEvents={},calls=[],history=[],updates=[];
    const root={id:'rn-results',dataset:{ajaxLoader:'rn-loader',ajaxStatus:'rn-status'},attributes:{},setAttribute(k,v){this.attributes[k]=v;},contains:()=>true,replaceWith(next){nodes['rn-results']=next;}};
    nodes['rn-results']=root;nodes['rn-loader']={hidden:true};nodes['rn-status']={hidden:true};
    const form={elements:fields,matches:sel=>sel.includes('data-ajax-form'),getAttribute:()=>'https://example.test/admin/hourly/renewals'};
    const location={href:'https://example.test/admin/hourly/renewals',origin:'https://example.test'};
    const context={
        document:{querySelectorAll:()=>[root],getElementById:id=>nodes[id],addEventListener(k,fn){events[k]=fn;},dispatchEvent:e=>updates.push(e)},
        window:{location,scrollY:400,scrollX:0,scrollTo(){},addEventListener(k,fn){windowEvents[k]=fn;},history:{pushState(s,u,url){history.push(url);location.href=url;}}},
        URL,URLSearchParams,AbortController,CustomEvent:class{constructor(type,init){this.type=type;this.detail=init.detail;}},
        DOMParser:class{parseFromString(text){return {getElementById:id=>text==='login'?null:{id,text,attributes:{},setAttribute(k,v){this.attributes[k]=v;},contains:()=>true,replaceWith(next){nodes[id]=next;}}};}},
        fetch:async(url,options)=>{calls.push(url);return responder?responder(url,options):{ok:true,text:async()=>url};}};
    vm.runInNewContext(readFileSync('public/admin/assets/js/hourly-ajax-filter.js','utf8'),context);
    return {nodes,events,windowEvents,calls,history,updates,
        async submit(){let prevented=false;events.submit({target:form,preventDefault(){prevented=true;}});await new Promise(setImmediate);return prevented;}};
}
test('submits without reload, skipping empty fields and fields inside hidden panels',async()=>{
    const h=harness(null,[field('order_number',''),field('status','d5',{type:'radio',checked:true}),field('status','custom',{type:'radio',checked:false}),
        field('expiry_from','2026-10-01',{inHidden:true}),field('team_ids[]','',{multiple:true,selectedOptions:[{value:'3'},{value:'none'}]})]);
    assert.equal(await h.submit(),true);
    const url=new URL(h.calls[0]);
    assert.equal(url.searchParams.get('status'),'d5');assert.equal(url.searchParams.has('order_number'),false);
    assert.equal(url.searchParams.has('expiry_from'),false);assert.deepEqual(url.searchParams.getAll('team_ids[]'),['3','none']);
    assert.match(h.nodes['rn-results'].text,/status=d5/);assert.equal(h.history.length,1);assert.equal(h.updates[0].type,'hx-filter:updated');
});
test('loader stays visible while fetching and the region is marked busy',async()=>{
    let finish;const h=harness(()=>new Promise(resolve=>{finish=resolve;}),[field('status','today',{type:'radio',checked:true})]);
    await h.submit();assert.equal(h.nodes['rn-loader'].hidden,false);assert.equal(h.nodes['rn-results'].attributes['aria-busy'],'true');
    finish({ok:true,text:async()=>'done'});await new Promise(setImmediate);
    assert.equal(h.nodes['rn-loader'].hidden,true);assert.equal(h.nodes['rn-results'].attributes['aria-busy'],'false');
});
test('failed or expired-session responses keep current results and show an error',async()=>{
    for(const responder of [async()=>({ok:false,status:500}),async()=>({ok:true,text:async()=>'login'})]){
        const h=harness(responder,[]);const before=h.nodes['rn-results'];
        await h.submit();assert.equal(h.nodes['rn-results'],before);assert.equal(h.history.length,0);
        assert.equal(h.nodes['rn-status'].hidden,false);assert.match(h.nodes['rn-status'].className,/alert-danger/);
    }
});
test('multi-select applies once when its panel closes, not on every checkbox change',async()=>{
    const fields=[field('team_ids[]','',{multiple:true,selectedOptions:[{value:'3'}]})];
    const h=harness(null,fields);const form={elements:fields,matches:()=>true,getAttribute:()=>'https://example.test/admin/hourly/renewals'};
    const select={...fields[0],form,matches:sel=>sel.includes('data-auto-apply')};
    h.events.change({type:'change',target:select});await new Promise(setImmediate);assert.equal(h.calls.length,0);
    h.events['hms:commit']({type:'hms:commit',target:select});await new Promise(setImmediate);
    assert.equal(h.calls.length,1);assert.deepEqual(new URL(h.calls[0]).searchParams.getAll('team_ids[]'),['3']);
});
test('a slower earlier response cannot overwrite the latest filters',async()=>{
    let resolveFirst,first=true;const fields=[field('order_number','A')];
    const h=harness(()=>{if(first){first=false;return new Promise(resolve=>{resolveFirst=resolve;});}return Promise.resolve({ok:true,text:async()=>'second'});},fields);
    await h.submit();fields[0].value='B';await h.submit();
    resolveFirst({ok:true,text:async()=>'first'});await new Promise(setImmediate);
    assert.equal(h.nodes['rn-results'].text,'second');assert.equal(h.history.length,1);
});
