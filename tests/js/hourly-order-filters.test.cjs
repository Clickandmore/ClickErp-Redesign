const {test}=require('node:test');
const assert=require('node:assert/strict');
const vm=require('node:vm');
const {readFileSync}=require('node:fs');
function harness(responder) {
    const nodes={},events={},windowEvents={},calls=[],history=[],scrolls=[];
    const node=id=>nodes[id] ||= {id,attributes:{},hidden:true,setAttribute(k,v){this.attributes[k]=v;},replaceWith(next){nodes[id]=next;}};
    node('hourly-order-results');node('hourly-order-summary');
    const location={href:'https://example.test/panel/orders/index/act/all',origin:'https://example.test'};
    const context={document:{getElementById:node,addEventListener(k,fn){events[k]=fn;}},
        window:{location,scrollY:850,scrollX:0,scrollTo:p=>scrolls.push(p),addEventListener(k,fn){windowEvents[k]=fn;},history:{pushState(state,unused,url){history.push(url);location.href=url;}}},
        URL,URLSearchParams,AbortController,FormData:class{constructor(form){this.entries=form.fields;}*[Symbol.iterator](){yield* this.entries;}},
        DOMParser:class{parseFromString(text){return {getElementById:id=>text==='login'?null:{id,text,attributes:{},setAttribute(k,v){this.attributes[k]=v;},replaceWith(next){nodes[id]=next;}}};}},
        fetch:async(url,options)=>{calls.push({url,options});return responder?responder(url,options):{ok:true,text:async()=>url};}};
    vm.runInNewContext(readFileSync('public/admin/assets/js/hourly-order-filters.js','utf8'),context);
    return {node,events,windowEvents,calls,history,scrolls,location,
        async submit(fields){let prevented=false;events.submit({target:{id:'hourly-order-filter',action:'https://example.test/panel/orders/index/act/all#all-orders',fields},preventDefault(){prevented=true;}});await new Promise(setImmediate);return prevented;},
        async click(url){let prevented=false;events.click({button:0,target:{closest:()=>({href:url})},preventDefault(){prevented=true;}});await new Promise(setImmediate);return prevented;}};
}
test('filter updates only results and summary, keeps scroll and stores shareable query without old page',async()=>{
    const h=harness();assert.equal(await h.submit([['search','client'],['status','active'],['page','4']]),true);
    assert.equal(h.calls.length,1);const url=new URL(h.calls[0].url);
    assert.equal(url.searchParams.get('status'),'active');assert.equal(url.searchParams.has('page'),false);
    assert.match(h.node('hourly-order-results').text,/client/);assert.match(h.node('hourly-order-summary').text,/active/);
    assert.equal(h.history.length,1);assert.equal(h.scrolls[0].top,850);
});
test('failed requests preserve previous results and URL and display an error',async()=>{
    const h=harness(async()=>({ok:false,status:500}));const before=h.node('hourly-order-results');
    await h.submit([['search','failed']]);assert.equal(h.node('hourly-order-results'),before);
    assert.equal(h.history.length,0);assert.equal(h.node('hourly-order-filter-status').hidden,false);
    assert.match(h.node('hourly-order-filter-status').className,/alert-danger/);
});
test('a slower earlier response cannot overwrite the latest filters',async()=>{
    let resolveFirst;const h=harness(url=>url.includes('first')?new Promise(resolve=>{resolveFirst=resolve;}):Promise.resolve({ok:true,text:async()=>url}));
    await h.submit([['search','first']]);await h.submit([['search','second']]);
    resolveFirst({ok:true,text:async()=> 'first'});await new Promise(setImmediate);
    assert.match(h.node('hourly-order-results').text,/second/);assert.equal(h.history.length,1);
});
test('pagination, reset and browser back use the same fetch flow without new history on back',async()=>{
    const h=harness();await h.click('https://example.test/panel/orders/index/act/all?status=active&page=2#all-orders');
    assert.equal(new URL(h.calls[0].url).searchParams.get('page'),'2');
    await h.click('https://example.test/admin/hourly/orders#all-orders');assert.equal(h.calls.length,2);
    h.location.href='https://example.test/panel/orders/index/act/all?status=active&page=2#all-orders';
    await h.windowEvents.popstate();assert.equal(h.calls.length,3);assert.equal(h.history.length,2);
});
test('expired session HTML cannot replace the orders table',async()=>{
    const h=harness(async()=>({ok:true,text:async()=> 'login'}));const before=h.node('hourly-order-results');
    await h.submit([['search','test']]);assert.equal(h.node('hourly-order-results'),before);assert.equal(h.history.length,0);
});

test('loading indicator stays visible while fetching and disappears after completion',async()=>{
    let finish;const h=harness(()=>new Promise(resolve=>{finish=resolve;}));
    await h.submit([['status','active']]);
    assert.equal(h.node('hourly-order-filter-loader').hidden,false);
    assert.equal(h.node('hourly-order-results').attributes['aria-busy'],'true');
    finish({ok:true,text:async()=> 'updated'});await new Promise(setImmediate);
    assert.equal(h.node('hourly-order-filter-loader').hidden,true);
    assert.equal(h.node('hourly-order-results').attributes['aria-busy'],'false');
});
