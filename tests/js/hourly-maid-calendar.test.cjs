const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const {pathToFileURL}=require('node:url');
const {spawnSync}=require('node:child_process');
const os=require('node:os');

const browser=process.env.CHROME_BIN || [
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    '/usr/bin/chromium', '/usr/bin/google-chrome',
].find(file=>fs.existsSync(file));

test('maid calendar keeps aligned tables across repeated month/week switches', {skip:!browser}, ()=>{
    const temp=fs.mkdtempSync(path.join(os.tmpdir(),'maid-calendar-'));
    try {
        const asset=file=>pathToFileURL(path.resolve('public',file)).href;
        let template=fs.readFileSync('resources/views/admin/hourly_maids/_calendar.blade.php','utf8');
        template=template.replace(/\{\{--[\s\S]*?--\}\}/g,'')
            .replace(/@foreach[\s\S]*?@endforeach/g,'')
            .replace(/\{\{ route\([^\n]+?\}\}/g,'https://calendar.test/feed')
            .replace(/@json\(asset\('([^']+)'\)\)/g,(_,file)=>JSON.stringify(asset(file)));
        const html=`<!doctype html><html dir="rtl"><head><meta charset="utf-8">
            <link rel="stylesheet" href="${asset('admin/custom/style.css')}">
            <link rel="stylesheet" href="${asset('admin/custom/style-rtl.css')}">
            <style>body{margin:0;width:1000px;--line:#e6ecf3;--soft:#f6f9fc;--ink:#16212e}[hidden]{display:none!important}</style>
            <script>
            window.errors=[];window.addEventListener('error',e=>errors.push(e.message));
            window.fetch=async()=>({ok:true,json:async()=>({events:[],days:{}})});
            </script></head><body>${template}
            <pre id="result">pending</pre><script>
            const results=[];
            const pause=()=>new Promise(resolve=>setTimeout(resolve,180));
            async function run(){
                for(let i=0;i<30 && !document.querySelector('.fc-dayGridMonth-view');i++)await pause();
                for(const view of ['dayGridMonth','timeGridWeek','dayGridMonth','timeGridWeek','dayGridMonth']){
                    document.querySelector('[data-view="'+view+'"]').click();await pause();
                    const el=document.querySelector('.fc-'+view+'-view');
                    const head=document.querySelector('.fc-col-header').getBoundingClientRect();
                    const body=document.querySelector(view==='timeGridWeek'?'.fc-timegrid-body':'.fc-daygrid-body').getBoundingClientRect();
                    results.push({view,visible:!!el && el.getBoundingClientRect().height>0,
                        active:document.querySelector('[data-view="'+view+'"]').getAttribute('aria-pressed'),
                        margins:[...document.querySelectorAll('#mp-cal-el table')].map(t=>getComputedStyle(t).marginTop),
                        headGap:Math.abs(head.bottom-body.top),widthGap:Math.abs(head.width-body.width)});
                }
                document.getElementById('result').textContent=JSON.stringify({results,errors});
            }
            run().catch(e=>document.getElementById('result').textContent=JSON.stringify({error:String(e)}));
            </script></body></html>`;
        const file=path.join(temp,'calendar.html');fs.writeFileSync(file,html);
        const run=spawnSync(browser,['--headless','--disable-gpu','--no-sandbox','--allow-file-access-from-files',
            '--no-first-run','--user-data-dir='+path.join(temp,'profile'),'--virtual-time-budget=10000','--dump-dom',pathToFileURL(file).href],
            {encoding:'utf8',timeout:30000,maxBuffer:8*1024*1024,windowsHide:true});
        assert.equal(run.status,0,run.stderr || String(run.error));
        const match=run.stdout.match(/<pre id="result">([^<]+)<\/pre>/);
        assert.ok(match,'Browser did not produce calendar results');
        const result=JSON.parse(match[1]);assert.equal(result.error,undefined);
        assert.deepEqual(result.errors,[]);assert.equal(result.results.length,5);
        for(const sample of result.results){
            assert.equal(sample.visible,true,sample.view);assert.equal(sample.active,'true');
            assert.ok(sample.margins.every(m=>m==='0px'),JSON.stringify(sample));
            assert.ok(sample.widthGap<=3,JSON.stringify(sample));
            if(sample.view==='dayGridMonth')assert.ok(sample.headGap<=3,JSON.stringify(sample));
        }
    } finally {
        // Only remove the dedicated directory created by this test.
        fs.rmSync(temp,{recursive:true,force:true,maxRetries:5,retryDelay:100});
    }
});
