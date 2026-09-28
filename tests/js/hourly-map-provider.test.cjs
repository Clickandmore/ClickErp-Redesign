const {test} = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname,'../../public/admin/assets/js/hourly-map-provider.js'),'utf8');

function environment(provider, failScript = false) {
    const scripts = [];
    const layer = type => ({type, addTo(map) {map.layers.add(this);return this;}});
    const control = () => ({addTo(map) {map.controls.add(this);return this;}});
    const L = {gridLayer:{},tileLayer:url=>layer(url),control,DomUtil:{create:()=>({style:{},setAttribute(){}})}};
    L.control.layers = control;
    const window = {hourlyMapConfig:{provider,key:'test-key',bridgeUrl:'/bridge.js'}};
    const document = {createElement:()=>({}),head:{appendChild(script) {
        scripts.push(script.src);
        queueMicrotask(()=>{
            if(failScript){script.onerror();return;}
            if(script.src.includes('maps.googleapis.com')){window.google={maps:{Map:function(){}}};window.initHourlySharedMaps();}
            else L.gridLayer.googleMutant=options=>layer('google:'+options.type);
            script.onload();
        });
    }}};
    vm.runInNewContext(source,{window,document,L,setTimeout,clearTimeout});
    function map() {
        const polygon = layer('saved-polygon');
        return {polygon,layers:new Set([polygon]),controls:new Set(),handlers:{},
            hasLayer(l){return this.layers.has(l);},removeLayer(l){this.layers.delete(l);},
            removeControl(c){this.controls.delete(c);},on(event,fn){this.handlers[event]=fn;}};
    }
    return {window,scripts,map};
}

test('free maps load no Google scripts and retain existing polygon overlays', async()=>{
    const env=environment('osm'),map=env.map();
    await env.window.HourlyMaps.attach(map,{satellite:true});
    assert.equal(env.scripts.length,0);
    assert.ok(map.layers.has(map.polygon));
    assert.ok([...map.layers].some(l=>l.type.includes('openstreetmap')));
    assert.equal(map.controls.size,1);
});

test('multiple Google maps share script loading; authentication fallback retains polygons',async()=>{
    const env=environment('google'),first=env.map(),second=env.map();
    await Promise.all([env.window.HourlyMaps.attach(first,{satellite:true}),env.window.HourlyMaps.attach(second)]);
    assert.equal(env.scripts.length,2);
    assert.ok([...first.layers].some(l=>l.type==='google:roadmap'));
    assert.ok([...second.layers].some(l=>l.type==='google:roadmap'));
    env.window.gm_authFailure();
    for(const map of [first,second]){
        assert.ok(map.layers.has(map.polygon));
        assert.ok([...map.layers].some(l=>l.type.includes('openstreetmap')));
        assert.ok(![...map.layers].some(l=>l.type.startsWith('google:')));
    }
    const count=first.layers.size;
    await env.window.HourlyMaps.attach(first);
    assert.equal(first.layers.size,count);
});

test('script failure falls back, and removed maps are not modified after loading',async()=>{
    const failed=environment('google',true),map=failed.map();
    await failed.window.HourlyMaps.attach(map);
    assert.ok([...map.layers].some(l=>l.type.includes('openstreetmap')));
    const env=environment('google'),removed=env.map();
    const loading=env.window.HourlyMaps.attach(removed);
    removed.handlers.unload();await loading;
    assert.equal(removed.layers.size,1);
});
