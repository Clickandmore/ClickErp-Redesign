/* ClickERP — التجارة (v1.75، جديد): شاشات نقاط البيع في لوحة التحكم — إدارة الكاشير عن بعد.
   لوحة اليوم · نقاط البيع والأجهزة · ربط جهاز · الكاشيرية · الورديات · فواتير الكاشير · طرق الدفع · العروض · تسويات البطاقات · وحدات ZATCA ومعالج الربط.
   المرجع: COMMERCE_POS_MASTER_PLAN.md §4.4 §4.5 §5.3 §6.4 §7 §8 + موجز التصميم قسم 10. الحالة محفوظة في sessionStorage (cmp.state). */
(window.CERP_EXT=window.CERP_EXT||[]).push(()=>{
const {X,SEC,KV,TAG,SW,ROW,FLD,SEL,INP,TXT,STAT,PAGER,ACTS,HEAD,B,TABS,CHIPS,STEPS,FS}=window.CXH;
const CM=window.CM,D=CM.D;const S={};
const r2=x=>Math.round(x*100)/100;
const pad=n=>String(n).padStart(2,"0");
/* «الآن» الثابت للعيّنة: 20 نوفمبر 2026 14:30 */
const stamp=m=>{const d=new Date(2026,10,20,14,30-m);return `<bdi dir="ltr">${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}</bdi>`};
const agoT=m=>m==null?"—":m<2?"الآن":m<60?`قبل ${m} د`:m<1440?`قبل ${Math.floor(m/60)} س`:`قبل ${Math.floor(m/1440)} يوم`;
const dur=m=>m<60?`${m} دقيقة`:m<1440?`${Math.floor(m/60)} س ${m%60} د`:`${Math.floor(m/1440)} يوم ${Math.floor(m%1440/60)} س`;
const num=v=>`<span class="num">${v}</span>`;
const mono=v=>`<span class="cm-mono">${v}</span>`;
const nm=(arr,id,i)=>{const r=arr.find(x=>x[0]===id);return r?r[i==null?1:i]:id};
const regName=id=>nm(D.registers,id),cashName=id=>nm(D.cashiers,id),brName=id=>nm(D.branches,id);
const brOfReg=id=>nm(D.registers,id,3);
const vis=regId=>CM.allBr()||brOfReg(regId)==="b1";
const sp=(c,t)=>`<span class="cmp-sp ${c}">${t}</span>`;
const acc=s=>{const m=/^(\d+)\s+(.*)$/.exec(s||"");return m?[m[2],m[1]]:[s,""]};
const ccOf=regId=>{const st=D.stores.find(s=>s[0]===nm(D.registers,regId,4));return st?st[3]:""};
const invAcc=regId=>{const st=D.stores.find(s=>s[0]===nm(D.registers,regId,4));return st?acc(st[4]):["المخزون",""]};

/* ---------- الحالة المحفوظة ---------- */
const SK="cmp.state.v2";
const defState=()=>({
 ctx:{shift:"SH-A1-0097",inv:"A2-000413",reg:"r1",dev:"d6",unit:"d6"},
 regs:D.registers.map((r,i)=>({id:r[0],name:r[1],pre:r[2],br:r[3],st:r[4],pl:r[5],box:r[6],pm:i===2?["cash","mada","card"]:["cash","mada","card","apple"],tpl:"إيصال حراري 80 مم — ضريبي مبسّط",neg:i<2?1:0,maxDisc:5,maxShort:i===2?30:20})),
 devs:[
  {id:"d1",n:"آيباد الكاشير 1",reg:"r1",ver:"1.4.2",plat:"iPadOS 17",seen:2,sync:3,unit:"EGS-POS-0001",st:"online",px:214},
  {id:"d2",n:"تابلت قديم — صالة الرياض",reg:"r1",ver:"1.3.8",plat:"Android 11",seen:12960,sync:12960,unit:"EGS-POS-0000",st:"revoked",px:190},
  {id:"d3",n:"Sunmi D3 — كاشير 2",reg:"r2",ver:"1.4.0",plat:"Android 11",seen:845,sync:845,unit:"EGS-POS-0002",st:"unsynced",px:212},
  {id:"d4",n:"Sunmi V2 — جدة",reg:"r3",ver:"1.4.2",plat:"Android 13",seen:1,sync:4,unit:"EGS-POS-0003",st:"online",px:214},
  {id:"d6",n:"جهاز جديد — صالة الرياض",reg:"r1",ver:"—",plat:"—",seen:null,sync:null,unit:"",st:"pairing",px:0}],
 units:{d1:{cert:"production",exp:"2027-09-12",icv:931,env:"إنتاج"},d2:{cert:"revoked",exp:"2027-02-03",icv:640,env:"إنتاج"},d3:{cert:"production",exp:"2026-12-08",icv:421,env:"إنتاج"},d4:{cert:"production",exp:"2027-10-01",icv:588,env:"إنتاج"}},
 sh:{},shr:{},shje:{},shop:{},
 f:{chip:"all",q:"",dev:"all",sc:"all"},
 inv:{},
 cs:D.cashiers.map(c=>({id:c[0],n:c[1],role:c[5]?"sup":"cashier",regs:c[3].slice(),maxDisc:c[4],active:1,pin:c[0]==="c5"?"منذ 12 يوماً":"منذ 3 أيام"})),csSel:"c1",csPin:0,
 pay:D.pay.map((p,i)=>({id:p[0],n:p[1],kind:p[2],acc:p[3],fee:p[4],on:p[0]==="voucher"?0:1})),
 pr:{list:[
  {id:"p1",n:"خصم 10% على الأرز والحبوب",t:"percent",v:10,scope:"cat",it:101,x:0,y:0,br:["b1","b2"],from:"2026-11-01",to:"2026-11-30",on:1},
  {id:"p2",n:"حليب 2 + 1 مجاناً",t:"bxgy",v:0,it:105,x:2,y:1,br:["b1"],from:"2026-11-15",to:"2026-12-15",on:1},
  {id:"p3",n:"خصم 5 ريال على زيت 1.5 لتر",t:"amount",v:5,it:102,x:0,y:0,br:["b1","b2"],from:"2026-12-01",to:"2026-12-07",on:1},
  {id:"p4",n:"يوم التأسيس — 15% على الكل",t:"percent",v:15,scope:"all",it:0,x:0,y:0,br:["b1","b2"],from:"2026-09-20",to:"2026-09-23",on:0}],
  sel:"",draft:null},
 setl:{},setlSel:"",setlIn:{},
 pair:{reg:"r1",name:"",code:"",exp:0,state:"none",dev:""},
 wz:{dev:"d6",gen:0,otp:"",sim:"ok",cur:1,st:["cur","wait","wait","wait","wait","wait"],run:0,err:null,done:0},
 modal:null,dl:0});
const load=()=>{let s=null;try{s=JSON.parse(sessionStorage.getItem(SK))}catch(e){}const d=defState();return s?Object.assign(d,s,{ctx:Object.assign(d.ctx,s.ctx||{})}):d};
const P=load();window.__cmpP=P;
const save=()=>{try{sessionStorage.setItem(SK,JSON.stringify(P))}catch(e){}};
const getReg=id=>P.regs.find(r=>r.id===id)||P.regs[0];
const getDev=id=>P.devs.find(d=>d.id===id);
const devSt=d=>d.st==="revoked"||d.st==="pairing"?d.st:d.seen>720?"unsynced":"online";

/* ---------- الورديات ---------- */
/* [id, النقطة, الجهاز, الكاشير, فُتحت قبل (د), أُقفلت قبل (د), العهدة, المبيعات بطرق الدفع (شاملة الضريبة بعد المرتجعات), إيداع/سحب, خصومات, مرتجعات (بدون ضريبة), فواتير, الحالة, المعدود نقداً] */
const SHIFTS=[
 ["SH-A1-0098","r1","d1","c1",388,null,300,{cash:3120.5,mada:4310.25,card:1980,apple:229.5},[["out",1500,"إيداع جزئي بالبنك",150],["in",200,"فكة من مدير الصالة",300]],120,75,31,"open",null],
 ["SH-A2-0041","r2","d3","c3",3000,null,300,{cash:4880,mada:6120.4},[["out",3000,"إيداع بالبنك",1200]],96.5,0,46,"open",null],
 ["SH-B1-0057","r3","d4","c4",300,null,200,{cash:1640,mada:3210.75,card:760},[],42,18.5,19,"open",null],
 ["SH-A1-0097","r1","d1","c2",1465,980,300,{cash:5840,mada:7265.5,card:2310,apple:640},[["out",4000,"إيداع بنكي نهاية اليوم",990]],210,130,58,"closed",2105],
 ["SH-B1-0056","r3","d4","c5",1500,1000,200,{cash:3215,mada:4880.25,card:1120},[["out",2500,"إيداع بنكي",1010]],64,0,27,"closed",915],
 ["SH-A1-0096","r1","d1","c1",2960,2470,300,{cash:6410.75,mada:8120,card:3055.5,apple:410},[["out",5000,"إيداع بنكي",2480]],180,45,63,"posted",1715.75],
 ["SH-A2-0040","r2","d3","c3",2900,2410,300,{cash:2980,mada:3640},[],55,0,24,"posted",3280]];
const shObj=a=>{const s={id:a[0],reg:a[1],dev:a[2],cash:a[3],open:a[4],close:a[5],float:a[6],rec:a[7],mov:a[8],disc:a[9],ret:a[10],inv:a[11],base:a[12],counted:a[13]};
 s.status=P.sh[s.id]||s.base;
 const ins=s.mov.filter(m=>m[0]==="in").reduce((x,m)=>x+m[1],0),outs=s.mov.filter(m=>m[0]==="out").reduce((x,m)=>x+m[1],0);
 s.ins=ins;s.outs=outs;
 s.expCash=r2(s.float+(s.rec.cash||0)+ins-outs);
 s.T=r2(Object.values(s.rec).reduce((a,b)=>a+b,0));
 s.net=r2(s.T/1.15);s.vat=r2(s.T-s.net);
 s.gross=r2(s.net+s.disc+s.ret);
 s.diff=s.counted==null?null:r2(s.counted-s.expCash);
 s.cogs=r2(s.net*0.71);
 s.maxShort=getReg(s.reg).maxShort;
 s.over=s.diff!=null&&s.diff<0&&-s.diff>s.maxShort;
 return s};
const shifts=()=>SHIFTS.map(shObj);
const getShift=id=>shObj(SHIFTS.find(a=>a[0]===id)||SHIFTS[3]);
const shSt=s=>s.status==="posted"?"s_posted":s.status==="approved"?"approved":s.status;
const PMN={cash:"نقدي",mada:"مدى",card:"فيزا / ماستر",apple:"Apple Pay",credit:"آجل"};
const pmAcc=(k,reg)=>k==="cash"?[reg.box,"1111"]:k==="card"?acc(D.pay[2][3]):k==="credit"?acc(D.accounts.posar):acc(D.pay[1][3]);
/* قيد إقفال الوردية (§5.3): قيد واحد ملخّص + قيد تكلفة واحد */
const shJournal=s=>{const reg=getReg(s.reg),cc=ccOf(s.reg),L=[];
 const cashDeb=r2(s.rec.cash+(s.diff||0));
 L.push([pmAcc("cash",reg)[0],pmAcc("cash",reg)[1],cashDeb,0,cc]);
 ["mada","card","apple","credit"].forEach(k=>{if(s.rec[k]){const a=pmAcc(k,reg);L.push([a[0]+(k==="apple"?" (Apple Pay)":""),a[1],s.rec[k],0,cc])}});
 if(s.diff!=null&&s.diff<0){const a=acc(D.accounts.overshort);L.push([a[0]+" — عجز",a[1],-s.diff,0,cc])}
 if(s.disc){const a=acc(D.accounts.discount);L.push([a[0],a[1],s.disc,0,cc])}
 if(s.ret){const a=acc(D.accounts.returns);L.push([a[0],a[1],s.ret,0,cc])}
 const serv=r2(Math.min(60,s.net*0.01)),goods=r2(s.net+s.disc+s.ret-serv);
 const a1=acc(D.accounts.sales),a2=acc(D.accounts.service),a3=acc(D.accounts.vatOut);
 L.push([a1[0]+" (مجمّعة حسب التصنيف)",a1[1],0,goods,cc]);L.push([a2[0],a2[1],0,serv,cc]);L.push([a3[0],a3[1],0,s.vat,cc]);
 if(s.diff!=null&&s.diff>0){const a=acc(D.accounts.overshort);L.push([a[0]+" — زيادة",a[1],0,s.diff,cc])}
 const c=acc(D.accounts.cogs),iv=invAcc(s.reg);
 const C=[[c[0],c[1],s.cogs,0,cc],[iv[0],iv[1],0,s.cogs,cc]];
 return {main:L,cogs:C}};

/* ---------- الفواتير ---------- */
/* [الرقم, الجهاز, الكاشير, الوردية, قبل (د), البنود [صنف, وحدة, كمية], طرق الدفع, ZATCA, الاستلام, إضافات] */
const INVS=[
 ["A1-000933","d1","c1","SH-A1-0098",3,[[106,0,0.375],[105,0,2]],["cash"],"reported","accepted",{ret:1,orig:"A1-000921"}],
 ["A1-000932","d1","c1","SH-A1-0098",6,[[101,0,1],[102,0,2],[104,0,6]],["mada"],"reported","accepted"],
 ["A1-000931","d1","c1","SH-A1-0098",14,[[106,0,2.35],[105,0,3]],["cash","card"],"reported","accepted"],
 ["A1-000930","d1","c1","SH-A1-0098",27,[[104,1,1],[103,0,1]],["card"],"reported","accepted",{neg:1}],
 ["B1-000588","d4","c4","SH-B1-0057",33,[[107,1,6],[108,1,3]],["card"],"reported","accepted"],
 ["B1-000587","d4","c4","SH-B1-0057",52,[[102,1,1]],["mada"],"reported","accepted"],
 ["B1-000586","d4","c4","SH-B1-0057",300,[[104,1,4],[109,0,1]],["cash"],"reported","accepted",{neg:1}],
 ["A2-000421","d3","c3","SH-A2-0041",846,[[102,0,3],[111,0,2]],["cash"],"unreported","accepted"],
 ["A2-000420","d3","c3","SH-A2-0041",850,[[103,0,2]],["mada"],"unreported","accepted"],
 ["A2-000414","d3","c3","SH-A2-0041",1255,[[101,0,1],[104,0,12]],["mada"],"unreported","accepted"],
 ["A2-000413","d3","c3","SH-A2-0041",1270,[[107,1,2],[102,0,1]],["cash"],"unreported","accepted"],
 ["A2-000411","d3","c3","SH-A2-0041",1290,[[105,1,1]],["cash"],"reported","accepted"],
 ["A1-000929","d1","c2","SH-A1-0097",1015,[[107,1,2]],["mada"],"reported","duplicate"],
 ["A1-000928","d1","c2","SH-A1-0097",1020,[[107,1,3],[108,1,2]],["card"],"unreported","review",{why:"إجمالي الجهاز 436.80 لا يطابق إجمالي السيرفر 436.95 (الفرق 0.15 أكبر من 0.01) — تقريب الضريبة على سطر الكرتون."}],
 ["A1-000927","d1","c2","SH-A1-0097",1050,[[101,1,3]],["cash","mada"],"reported","accepted"],
 ["A1-000926","d1","c2","SH-A1-0097",1100,[[105,1,2],[109,0,1]],["mada"],"z_rejected","accepted",{rej:"BR-KSA-EN16931-08 — الرقم الضريبي للمشتري غير صالح: الفاتورة صادرة كضريبية لمنشأة، والرقم المدخل يتكوّن من 14 خانة بدل 15."}],
 ["A1-000925","d1","c2","SH-A1-0097",1130,[[106,0,1.5],[103,0,1]],["card"],"reported","accepted"]];
const GAPS=[["A2-000412","d3","SH-A2-0041",1262]];
const invObj=a=>{const o=Object.assign({no:a[0],dev:a[1],cash:a[2],sh:a[3],ago:a[4],L:a[5],pay:a[6],z:a[7],ing:a[8]},a[9]||{});
 const ov=P.inv[o.no];if(ov){if(ov.z)o.z=ov.z;if(ov.ing)o.ing=ov.ing}
 o.rows=o.L.map(l=>{const it=D.item(l[0]),u=it.units[l[1]];return {it,u:u[0],q:l[2],p:u[2],t:r2(u[2]*l[2])}});
 o.sub=r2(o.rows.reduce((s,r)=>s+r.t,0));o.total=o.ret?-o.sub:o.sub;o.vat=r2(o.sub*15/115);o.net=r2(o.sub-o.vat);
 o.seq=+o.no.split("-")[1];o.reg=getDev(o.dev).reg;
 o.zs=o.z==="unreported"&&o.ago>=1200?"late":o.z;
 o.pays=o.pay.length===1?[[o.pay[0],o.sub]]:[[o.pay[0],r2(Math.round(o.sub*0.4))],[o.pay[1],r2(o.sub-Math.round(o.sub*0.4))]];
 if(o.ret)o.pays=o.pays.map(p=>[p[0],-p[1]]);
 return o};
const invs=()=>INVS.map(invObj);
const getInv=no=>invObj(INVS.find(a=>a[0]===no)||INVS[10]);
const visInv=i=>vis(i.reg);
const nf2=n=>Number(n).toLocaleString("en-US",{minimumFractionDigits:2,maximumFractionDigits:2});

/* ---------- رموز بصرية: هاش + QR مزيّف ثابت ---------- */
const rnd=seed=>{let h=2166136261;for(const c of String(seed)){h^=c.charCodeAt(0);h=Math.imul(h,16777619)}return()=>{h^=h<<13;h^=h>>>17;h^=h<<5;return((h>>>0)%1000)/1000}};
const HS=(seed,len)=>{const r=rnd(seed),a="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";let s="";for(let i=0;i<(len||44)-1;i++)s+=a[Math.floor(r()*64)];return s+"="};
const QR=(seed,n)=>{n=n||25;const r=rnd(seed);let s=`<svg class="cmp-qr" viewBox="-1 -1 ${n+2} ${n+2}" shape-rendering="crispEdges"><rect x="-1" y="-1" width="${n+2}" height="${n+2}" fill="#fff"/>`;
 const fin=(x,y)=>`<path d="M${x} ${y}h7v7h-7zM${x+1} ${y+1}v5h5v-5z" fill="#16233f" fill-rule="evenodd"/><rect x="${x+2}" y="${y+2}" width="3" height="3" fill="#16233f"/>`;
 for(let y=0;y<n;y++)for(let x=0;x<n;x++){if((x<8&&y<8)||(x>=n-8&&y<8)||(x<8&&y>=n-8))continue;if(r()>.52)s+=`<rect x="${x}" y="${y}" width="1" height="1" fill="#16233f"/>`}
 return s+fin(0,0)+fin(n-7,0)+fin(0,n-7)+"</svg>"};

/* ---------- أجزاء مشتركة ---------- */
const go=(to,html,ctx,cls)=>`<a href="#" class="${cls||""}" data-go="${to}"${ctx?` data-cmp-ctx="${ctx}"`:""}>${html}</a>`;
const btnGo=(cls,ic,txt,to,ctx)=>B(cls,ic,txt,`data-go="${to}"${ctx?` data-cmp-ctx="${ctx}"`:""}`);
const cmpB=(cls,ic,txt,act,extra)=>B(cls,ic,txt,`data-cmp="${act}" ${extra||""}`);
const posLink=()=>`<a class="btn btn-ghost" href="pos.html" target="_blank">${X("tablet-smartphone")}معاينة تطبيق الكاشير</a>`;
const modal=()=>{const m=P.modal;if(!m)return "";
 let body="";
 if(m.t==="revoke"){const d=getDev(m.dev),open=shifts().filter(s=>s.dev===d.id&&s.status==="open").length,unrep=invs().filter(i=>i.dev===d.id&&i.z==="unreported").length;
  body=`<div class="cmp-mh bad">${X("ban")}<div><h3>إلغاء الجهاز «${d.n}»</h3><p>إلغاء نهائي لا رجعة فيه.</p></div></div>
  <div class="cmp-mb"><ul class="cmp-ul"><li>يُحذف رمز الدخول (token) فوراً، ويتوقف الجهاز عن الاتصال بالسيرفر.</li><li><b>الجهاز الملغى لا يعود أبداً.</b> لإعادة استخدام الجهاز نفسه يلزم ربطه من جديد كجهاز جديد برمز ربط جديد.</li><li>الفواتير المرفوعة سابقاً تبقى كما هي، وشهادة ZATCA الخاصة به تُسحب.</li>${open?`<li class="w">لديه ${num(open)} وردية مفتوحة: تأكد من مزامنتها قبل الإلغاء.</li>`:""}${unrep?`<li class="w">لديه ${num(unrep)} فواتير لم تُبلّغ للهيئة: أي فاتورة لم تُرفع من الجهاز بعد لن تصل أبداً.</li>`:""}</ul>
  <label class="cmp-ck"><input type="checkbox" data-cmp-mck ${m.ck?"checked":""}> أفهم أن الإلغاء نهائي ولا يمكن التراجع عنه</label></div>
  <div class="cmp-mf">${cmpB("ghost","","تراجع","mclose")}<button class="btn btn-primary cmp-danger" data-cmp="revokeOk" ${m.ck?"":"disabled"}>${X("ban")}إلغاء الجهاز نهائياً</button></div>`}
 if(m.t==="newreg"){body=`<div class="cmp-mh">${X("monitor-smartphone")}<div><h3>إضافة نقطة بيع</h3><p>نقطة البيع هي الإعدادات المشتركة، وتحتها جهاز أو أكثر.</p></div></div>
  <div class="cmp-mb"><div class="fgrid two" style="padding:0">${FLD("الاسم",`<input data-cmp-mf="name" placeholder="مثال: كاشير 3 — صالة الرياض" value="${m.name||""}">`,1)}${FLD("بادئة الترقيم",`<input data-cmp-mf="pre" dir="ltr" maxlength="3" placeholder="A3" value="${m.pre||""}">`,1,"حرفان أو ثلاثة، تُضاف لرقم كل فاتورة")}${FLD("الفرع",`<select data-cmp-mf="br">${D.branches.map(b=>`<option value="${b[0]}"${m.br===b[0]?" selected":""}>${b[1]}</option>`).join("")}</select>`,1)}</div>${m.err?`<small class="cmp-err">${m.err}</small>`:""}</div>
  <div class="cmp-mf">${cmpB("ghost","","إلغاء","mclose")}${cmpB("primary","plus","إضافة","newregOk")}</div>`}
 if(m.t==="link"){const free=["أحمد الدوسري","نورة الحربي","سلطان العتيبي","ريم القحطاني"];
  body=`<div class="cmp-mh">${X("user-plus")}<div><h3>ربط موظف ككاشير</h3><p>اختر موظفاً من الموارد البشرية. يظهر هنا من لا يملك حساب كاشير بعد.</p></div></div>
  <div class="cmp-mb"><div class="fgrid two" style="padding:0">${FLD("الموظف",`<select data-cmp-mf="emp">${free.map(f=>`<option${m.emp===f?" selected":""}>${f}</option>`).join("")}</select>`,1)}${FLD("الدور",`<select data-cmp-mf="role"><option value="cashier">كاشير</option><option value="sup"${m.role==="sup"?" selected":""}>مشرف وردية</option></select>`,1)}</div>${CM.note("بعد الربط عيّن PIN للموظف من لوحته. لا يستطيع الدخول إلى التطبيق قبل تعيين PIN وتحديد نقطة بيع واحدة على الأقل.","","info")}</div>
  <div class="cmp-mf">${cmpB("ghost","","إلغاء","mclose")}${cmpB("primary","link","ربط الموظف","linkOk")}</div>`}
 return `<div class="cmp-ov" data-cmp="mbg"><div class="cmp-modal" onclick="event.stopPropagation()">${body}</div></div>`};

/* ====================================================================================== */
/* ======================================= لوحة اليوم ==================================== */
/* ====================================================================================== */
const DEVSALES={d1:[9640.25,31],d3:[3980,14],d4:[5610.75,19]};
const cdFmt=ms=>{if(ms<=0)return "انتهت المهلة";const s=Math.floor(ms/1000);return `${pad(Math.floor(s/3600))}:${pad(Math.floor(s%3600/60))}:${pad(s%60)}`};
const stockStats=()=>{const stores=D.stores.filter(s=>CM.allBr()||s[2]==="b1").map(s=>s[0]);
 const brs=CM.allBr()?["b1","b2"]:["b1"],below=[],neg=[],exp=[];
 D.items.filter(i=>i.kind==="stock"&&i.act).forEach(i=>{
  brs.forEach(b=>{const q=D.stores.filter(s=>s[2]===b).reduce((a,s)=>a+(i.qty[s[0]]||0),0);if(i.re&&q<i.re&&!below.find(x=>x.id===i.id))below.push(i)});
  stores.forEach(sid=>{if((i.qty[sid]||0)<0)neg.push([i,sid])});
  if(i.exp&&i.exp<="2026-12-04"&&D.tot(i)>0)exp.push(i)});
 const val=D.items.filter(i=>i.kind==="stock").reduce((a,i)=>a+stores.reduce((b,sid)=>b+Math.max(0,i.qty[sid]||0)*i.cost,0),0);
 return {below,neg,exp,val}};
S.cmDash=()=>{
 if(!P.dl){P.dl=Date.now()+(2*3600+49*60+50)*1000;save()}
 const dv=P.devs.filter(d=>vis(d.reg)&&d.st!=="revoked");
 const act=dv.filter(d=>d.st!=="pairing");
 const sales=act.map(d=>[d,...(DEVSALES[d.id]||[0,0])]);
 const total=sales.reduce((a,x)=>a+x[1],0),cnt=sales.reduce((a,x)=>a+x[2],0);
 const shs=shifts().filter(s=>vis(s.reg)),opens=shs.filter(s=>s.status==="open"),longOpen=opens.filter(s=>s.open>1440),waiting=shs.filter(s=>s.status==="closed"||s.status==="approved");
 const iv=invs().filter(visInv),unrep=iv.filter(i=>i.z==="unreported"&&i.ing!=="review"),oldest=unrep.sort((a,b)=>b.ago-a.ago)[0];
 const rej=iv.filter(i=>i.z==="z_rejected"),rev=iv.filter(i=>i.ing==="review"),late=unrep.filter(i=>i.ago>=1200);
 const gaps=GAPS.filter(g=>vis(getDev(g[1]).reg)),uns=act.filter(d=>devSt(d)==="unsynced"),nopx=act.filter(d=>d.px<214);
 const ss=stockStats();
 const todo=[];
 if(oldest)todo.push(["alarm-clock","r",`أقدم فاتورة لم تُبلّغ مضى عليها <b class="num">${dur(oldest.ago)}</b> — تنتهي المهلة النظامية (24 س) بعد <b class="num" data-cmp-cdt="${P.dl}">${cdFmt(P.dl-Date.now())}</b>`,"cmPosInvoices","f:late"]);
 gaps.forEach(g=>todo.push(["triangle-alert","r",`فجوة ترقيم: الفاتورة <b class="num">${g[0]}</b> لم تصل من «<bdi>${getDev(g[1]).n}</bdi>»`,"cmPosInvoices","f:gap"]));
 uns.forEach(d=>todo.push(["wifi-off","r",`«<bdi>${d.n}</bdi>» لم يتزامن منذ <b class="num">${dur(d.sync)}</b>`,"cmRegisters","reg:"+d.reg]));
 if(rej.length)todo.push(["shield-x","r",`<b class="num">${rej.length}</b> فاتورة مرفوضة من الهيئة تحتاج تصحيحاً`,"cmPosInvoices","f:z_rejected"]);
 if(rev.length)todo.push(["alert-triangle","a",`<b class="num">${rev.length}</b> فاتورة مرفوضة للمراجعة (فرق إجمالي) بانتظار قرار`,"cmPosInvoices","f:review"]);
 longOpen.forEach(s=>todo.push(["unlock","a",`وردية <b class="num">${s.id}</b> مفتوحة منذ ${dur(s.open)} — تحقق من الكاشير`,"cmShiftShow","shift:"+s.id]));
 if(waiting.length)todo.push(["book-check","a",`<b class="num">${waiting.length}</b> ورديات مقفلة بانتظار الاعتماد والترحيل`,"cmShifts","sc:closed"]);
 if(ss.below.length)todo.push(["package-x","n",`<b class="num">${ss.below.length}</b> أصناف تحت حد إعادة الطلب`,"cmStock",""]);
 const rank=a=>a[1]==="r"?0:a[1]==="a"?1:2;todo.sort((a,b)=>rank(a)-rank(b));
 const critical=todo.filter(t=>t[1]==="r").length;
 const gp=r2(total/1.15*0.29);
 const kp=[["trending-up","مبيعات اليوم",CM.m(total),`${num(cnt)} فاتورة · شاملة الضريبة`,"",1],["unlock","ورديات مفتوحة",num(opens.length),longOpen.length?`${num(longOpen.length)} منها مفتوحة أكثر من يوم`:"كلها ضمن اليوم",longOpen.length?"a":"",1],["wifi","أجهزة متصلة",`${num(act.filter(d=>devSt(d)==="online").length)}<small class="cmp-of"> من ${num(act.length)}</small>`,uns.length?`${num(uns.length)} غير متزامن`:"كلها متزامنة",uns.length?"r":"g",1],["shield-check","فواتير لم تُبلّغ",num(unrep.length),oldest?`أقدمها ${dur(oldest.ago)}`:"لا شيء معلّق",unrep.length?"r":"g",1]];
 if(CM.cost()){kp.push(["badge-percent","مجمل ربح اليوم",CM.m(gp),"المبيعات − تكلفة البضاعة (تقديري)","g",1]);kp.push(["warehouse","قيمة المخزون",CM.m(ss.val),"بالتكلفة المرجّحة · المخازن المرئية","",1])}
 const brs=CM.allBr()?D.branches:D.branches.slice(0,1);
 const brSales=brs.map(b=>{const l=sales.filter(x=>brOfReg(x[0].reg)===b[0]);return [b[1],l.reduce((a,x)=>a+x[1],0),l.reduce((a,x)=>a+x[2],0)]});
 const normal=`
 <div class="fx-hero card cmp-hero">
  <div class="fx-hero-t cmp-hero-t"><small>${CM.allBr()?"كل الفروع":"الفرع الرئيسي — الرياض"} · محدّث الآن ${num("14:30")}</small>
   <h2>${critical?`لديك <b class="g num">${critical}</b> أمور عاجلة و<b class="num">${todo.length-critical}</b> للمتابعة اليوم.`:todo.length?`لديك <b class="num">${todo.length}</b> أمور للمتابعة، ولا شيء عاجل.`:"كل شيء على ما يرام: لا توجد أمور معلّقة."}</h2>
   <p>بيع اليوم <b class="num">${nf2(total)}</b> ر.س عبر <b class="num">${cnt}</b> فاتورة على <b class="num">${act.length}</b> أجهزة${oldest?`، وأقدم فاتورة لم تُبلّغ تقترب من مهلة الهيئة.`:"."}</p>
   ${oldest?`<div class="cmp-cd ${late.length?"late":""}"><span>${X("timer")}مهلة التبليغ للفاتورة <span class="num cm-mono">${oldest.no}</span></span><b class="num" data-cmp-cdt="${P.dl}">${cdFmt(P.dl-Date.now())}</b><small>التحذير يبدأ عند 20 ساعة · المهلة النظامية 24 ساعة</small><div class="cmp-bar"><i style="width:${Math.min(100,oldest.ago/14.4)}%"></i><u style="inset-inline-start:83.3%"></u></div></div>`:""}
  </div>
  <div class="fx-todo cmp-todo"><b>ماذا أفعل الآن؟</b>${todo.length?todo.slice(0,7).map(t=>`<a href="#" data-go="${t[3]}"${t[4]?` data-cmp-ctx="${t[4]}"`:""} class="${t[1]}">${X(t[0])}<span>${t[2]}</span>${X("chevron-left")}</a>`).join(""):`<div class="cmp-allok">${X("check-circle-2")}<span>لا توجد مهام معلّقة</span></div>`}</div>
 </div>
 <div class="cmp-kpis">${kp.map(k=>`<div class="card cmp-kpi ${k[4]||""}"><span>${X(k[0])}${k[1]}</span><b>${k[2]}</b><small>${k[3]}</small></div>`).join("")}</div>
 <div class="grid-2">
  <div class="card flush"><div class="card-h pad"><div><h3>مبيعات اليوم لكل فرع</h3><p>شاملة الضريبة · بعد المرتجعات</p></div></div><div class="cmp-brs">${brSales.map(b=>`<div><div class="cmp-brh"><b>${b[0]}</b><span>${CM.m(b[1])}</span></div><div class="cmp-track"><i style="width:${total?Math.round(b[1]/total*100):0}%"></i></div><small>${num(b[2])} فاتورة · ${total?num(Math.round(b[1]/total*100)):0}% من المبيعات</small></div>`).join("")}</div></div>
  <div class="card flush"><div class="card-h pad"><div><h3>الورديات المفتوحة</h3><p>الوردية المفتوحة أكثر من يوم تُعلَّم</p></div>${go("cmShifts","كل الورديات")}</div>
   <div class="cmp-list">${opens.map(s=>`<a href="#" data-go="cmShiftShow" data-cmp-ctx="shift:${s.id}" class="${s.open>1440?"warn":""}"><span class="cmp-li">${X(s.open>1440?"alert-triangle":"unlock")}</span><div><b>${s.id} · ${cashName(s.cash)}</b><small>${regName(s.reg)} · منذ ${dur(s.open)}</small></div><em>${CM.m(s.T)}</em></a>`).join("")||`<p class="cmp-none">لا توجد ورديات مفتوحة.</p>`}</div></div>
 </div>
 <div class="card flush cmp-mt"><div class="card-h pad"><div><h3>الأجهزة ومبيعات اليوم لكل جهاز</h3><p>تُعلَّم الأجهزة غير المتزامنة منذ 12 ساعة أو التي لم تستلم آخر تحديث للأسعار</p></div>${go("cmRegisters","إدارة الأجهزة")}</div><div class="tw"><table><thead><tr><th>الجهاز</th><th>نقطة البيع</th><th>الحالة</th><th>آخر مزامنة</th><th>تحديث الأسعار</th><th>الفواتير</th><th>مبيعات اليوم</th></tr></thead><tbody>
  ${sales.map(x=>{const d=x[0],st=devSt(d);return `<tr class="${st==="unsynced"?"cm-row-bad":d.px<214?"cm-row-warn":""}"><td><b>${d.n}</b><span class="cm-sub">إصدار ${num(d.ver)}</span></td><td>${regName(d.reg)}</td><td>${CM.st(st)}</td><td>${agoT(d.sync)}${st==="unsynced"?`<span class="cm-flag bad">${X("wifi-off")}أكثر من 12 ساعة</span>`:""}</td><td>${d.px>=214?`<span class="cm-flag ok">${X("check")}آخر تحديث ${num("v-214")}</span>`:`<span class="cm-flag bad">${X("circle-alert")}لم يستلم v-214 (لديه v-${d.px})</span>`}</td><td>${num(x[2])}</td><td>${CM.m(x[1])}</td></tr>`}).join("")}
  <tr class="tot"><td colspan="5"><b>الإجمالي</b></td><td><b>${num(cnt)}</b></td><td><b>${CM.m(total)}</b></td></tr></tbody></table></div></div>
 <div class="grid-2 cmp-mt">
  <div class="card flush"><div class="card-h pad"><div><h3>ZATCA وترقيم الفواتير</h3><p>بلاغات الهيئة وفجوات الترقيم من الأجهزة</p></div>${go("cmZatca","وحدات ZATCA")}</div>
   <div class="cmp-rows">
    <a href="#" data-go="cmPosInvoices" data-cmp-ctx="f:unreported"><span>لم تُبلّغ</span><b>${num(unrep.length)}</b></a>
    <a href="#" data-go="cmPosInvoices" data-cmp-ctx="f:late" class="${late.length?"bad":""}"><span>متأخرة (أكثر من 20 ساعة)</span><b>${num(late.length)}</b></a>
    <a href="#" data-go="cmPosInvoices" data-cmp-ctx="f:z_rejected" class="${rej.length?"bad":""}"><span>مرفوضة من الهيئة</span><b>${num(rej.length)}</b></a>
    <a href="#" data-go="cmPosInvoices" data-cmp-ctx="f:review" class="${rev.length?"warn":""}"><span>مرفوضة للمراجعة (فرق إجمالي)</span><b>${num(rev.length)}</b></a>
    <a href="#" data-go="cmPosInvoices" data-cmp-ctx="f:gap" class="${gaps.length?"bad":""}"><span>فجوات الترقيم</span><b>${num(gaps.length)}</b></a>
   </div>${gaps.map(g=>`<div class="cm-note bad cmp-gapn">${X("triangle-alert")}<div>الفاتورة <b class="num cm-mono">${g[0]}</b> مفقودة بين <span class="num cm-mono">A2-000411</span> و<span class="num cm-mono">A2-000413</span> من «${getDev(g[1]).n}». قد تكون لم تُرفع بعد أو حُذفت على الجهاز؛ اطلب مزامنة الجهاز.</div></div>`).join("")}</div>
  <div class="card flush"><div class="card-h pad"><div><h3>المخزون يحتاج انتباهك</h3><p>للمخازن المرئية لك</p></div>${go("cmStock","أرصدة المخزون")}</div>
   <div class="cmp-rows">
    <a href="#" data-go="cmStock" class="${ss.below.length?"warn":""}"><span>تحت حد إعادة الطلب<small>${ss.below.slice(0,3).map(i=>i.n).join(" · ")}</small></span><b>${num(ss.below.length)}</b></a>
    <a href="#" data-go="cmStock" class="${ss.exp.length?"warn":""}"><span>قريبة الانتهاء (خلال 14 يوماً)<small>${ss.exp.map(i=>`${i.n} — ${i.exp}`).join(" · ")||"—"}</small></span><b>${num(ss.exp.length)}</b></a>
    <a href="#" data-go="cmStock" class="${ss.neg.length?"bad":""}"><span>أرصدة سالبة (بيع بدون رصيد)<small>${ss.neg.map(n=>`${n[0].n}: ${CM.q(n[0].qty[n[1]],"")}`).join(" · ")||"—"}</small></span><b>${num(ss.neg.length)}</b></a>
    <a href="#" data-go="cmShifts" data-cmp-ctx="sc:closed"><span>ورديات مقفلة بانتظار الترحيل</span><b>${num(waiting.length)}</b></a>
   </div></div>
 </div>
 <div class="cmp-foot">${X("info")}<span>الكاشير يعمل بدون إنترنت ويرفع فواتيره لاحقاً؛ لذلك قد تظهر فواتير يومٍ متأخرة. تطبيق الكاشير: ${`<a href="pos.html" target="_blank">فتح المعاينة</a>`}.</span></div>`;
 return `${HEAD("لوحة التجارة اليوم","مبيعات نقاط البيع لحظياً، والأمور التي تحتاج تصرفك الآن",posLink()+btnGo("ghost","monitor-smartphone","نقاط البيع والأجهزة","cmRegisters")+btnGo("primary","shield-check","وحدات ZATCA","cmZatca"))}${CM.pvbar()}${CM.view(normal,CM.empty("store","لا توجد نقاط بيع بعد","أضف أول نقطة بيع واربط جهازاً ليظهر هنا بيع اليوم.",btnGo("primary","monitor-smartphone","نقاط البيع والأجهزة","cmRegisters")))}`};

/* ====================================================================================== */
/* ================================ نقاط البيع والأجهزة =================================== */
/* ====================================================================================== */
const TPLS=["إيصال حراري 80 مم — ضريبي مبسّط","إيصال حراري 58 مم — مختصر","فاتورة A4 ضريبية"];
S.cmRegisters=()=>{
 const r=getReg(P.ctx.reg),devs=P.devs.filter(d=>d.reg===r.id),visR=P.regs.filter(x=>vis(x.id));
 const act=P.devs.filter(d=>vis(d.reg)&&d.st!=="revoked"),uns=act.filter(d=>devSt(d)==="unsynced"),pend=act.filter(d=>d.st==="pairing");
 const stores=D.stores.filter(s=>s[2]===r.br);
 const lim=CM.cfg().limits.devices;
 const todo=[];
 uns.forEach(d=>todo.push(`«<bdi>${d.n}</bdi>» لم يتزامن منذ ${dur(d.sync)} — تأكد من اتصاله بالإنترنت أو أعد تشغيل التطبيق.`));
 pend.forEach(d=>todo.push(`«${d.n}» بانتظار الربط: أدخل كود الربط في التطبيق قبل انتهائه.`));
 const settings=`
  <div class="card sec"><div class="sec-h">${X("settings-2")}<b>إعدادات نقطة البيع</b><span class="cm-flag ok" style="margin-inline-start:auto">${X("save")}تُحفظ تلقائياً — تصل للأجهزة عند المزامنة القادمة</span></div>
  <div class="fgrid two">
   ${FLD("اسم نقطة البيع",`<input data-cmp-in="regs.${r.id}.name" value="${r.name}">`,1)}
   ${FLD("بادئة الترقيم",`<input dir="ltr" maxlength="3" data-cmp-in="regs.${r.id}.pre" value="${r.pre}">`,1,`رقم الفاتورة: ${r.pre}-000932 · التسلسل لكل جهاز`)}
   ${FLD("الفرع",`<select data-cmp-in="regs.${r.id}.br" data-cmp-re>${D.branches.map(b=>`<option value="${b[0]}"${r.br===b[0]?" selected":""}>${b[1]}</option>`).join("")}</select>`,1)}
   ${FLD("المخزن (يُخصم منه البيع)",`<select data-cmp-in="regs.${r.id}.st">${stores.map(s=>`<option value="${s[0]}"${r.st===s[0]?" selected":""}>${s[1]}</option>`).join("")}</select>`,1)}
   ${FLD("قائمة الأسعار",`<select data-cmp-in="regs.${r.id}.pl">${["التجزئة","الجملة","موظفون"].map(p=>`<option${r.pl===p?" selected":""}>${p}</option>`).join("")}</select>`)}
   ${FLD("الصندوق النقدي (للقيد)",`<select data-cmp-in="regs.${r.id}.box">${["صندوق كاشير 1","صندوق كاشير 2","صندوق كاشير جدة","صندوق الصالة المشترك"].map(p=>`<option${r.box===p?" selected":""}>${p}</option>`).join("")}</select>`)}
   ${FLD("قالب الإيصال",`<select data-cmp-in="regs.${r.id}.tpl">${TPLS.map(p=>`<option${r.tpl===p?" selected":""}>${p}</option>`).join("")}</select>`)}
  </div>
  <div class="cmp-sub-h">طرق الدفع المسموحة <small>الترتيب يُضبط من «طرق الدفع»</small></div>
  <div class="cmp-pmchips">${P.pay.filter(p=>p.on).map(p=>{const on=r.pm.includes(p.id);const off=p.kind==="credit"&&!CM.on("credit_sales");return `<button class="${on&&!off?"on":""}" ${off?"disabled title=\"البيع الآجل غير مفعّل\"":`data-cmp="pm:${r.id}:${p.id}"`}>${X(on&&!off?"check":"plus")}${p.n}</button>`}).join("")}</div>
  <div class="cmp-sub-h">قواعد الكاشير</div>
  ${ROW("السماح بالبيع بدون رصيد","يُباع حتى لو كان الرصيد صفراً أو سالباً، وتُعلَّم الفاتورة «بيع بدون رصيد» وتظهر في المخزون سالبة",`<span class="sw ${r.neg?"on":""}" data-cmp="neg:${r.id}"></span>`)}
  <div class="fgrid two" style="padding-top:14px">
   ${FLD("أقصى خصم بدون مشرف (%)",`<input type="number" min="0" max="100" data-cmp-in="regs.${r.id}.maxDisc" data-num value="${r.maxDisc}">`,0,"فوقه يطلب الكاشير PIN مشرف")}
   ${FLD("أقصى عجز مقبول في الوردية (ر.س)",`<input type="number" min="0" data-cmp-in="regs.${r.id}.maxShort" data-num value="${r.maxShort}">`,0,"عجز أكبر منه يُلوَّن بالتحذير ويحتاج سبباً عند الاعتماد")}
  </div></div>
  <div class="card flush"><div class="card-h pad"><div><h3>أجهزة «${r.name}»</h3><p>${num(devs.filter(d=>d.st!=="revoked").length)} نشط · الحد المسموح للشركة ${num(lim)} أجهزة (${num(P.devs.filter(d=>d.st!=="revoked").length)} مستخدمة)</p></div>${btnGo("primary sm","plus","إضافة جهاز","cmDevicePair","reg:"+r.id)}</div>
  ${devs.length?`<div class="tw"><table><thead><tr><th>الجهاز</th><th>الحالة</th><th>الإصدار</th><th>آخر ظهور</th><th>آخر مزامنة</th><th>وحدة ZATCA</th><th class="act"></th></tr></thead><tbody>${devs.map(d=>{const st=devSt(d);return `<tr class="${st==="unsynced"?"cm-row-bad":st==="revoked"?"cmp-dim":""}"><td><b>${d.n}</b><span class="cm-sub">${d.plat}${d.px&&st!=="revoked"&&st!=="pairing"&&d.px<214?` · <span class="cm-flag bad">${X("circle-alert")}لم يستلم تحديث الأسعار</span>`:""}</span></td><td>${CM.st(st)}</td><td>${d.ver==="—"?"—":num(d.ver)}</td><td>${agoT(d.seen)}</td><td>${agoT(d.sync)}${st==="unsynced"?`<span class="cm-flag bad">${X("wifi-off")}أكثر من 12 ساعة</span>`:""}</td><td>${d.unit?mono(d.unit):st==="pairing"?`<span class="cm-flag">${X("clock")}بعد الربط</span>`:"—"}</td><td class="cmp-ac">${st==="revoked"?`<span class="cm-sub">لا يعود</span>`:st==="pairing"?cmpB("ghost sm","qr-code","عرض الكود","pairgo:"+d.id):cmpB("ghost sm cmp-red","ban","إلغاء الجهاز","revoke:"+d.id)}</td></tr>`}).join("")}</tbody></table></div>`:`<p class="cmp-none">لا توجد أجهزة على هذه النقطة بعد. اضغط «إضافة جهاز» لتوليد كود ربط.</p>`}</div>`;
 const normal=`
  ${todo.length?CM.note("<b>ماذا أفعل الآن؟</b><br>"+todo.join("<br>"),"warn"):CM.note("<b>ماذا أفعل الآن؟</b> لا شيء عاجل: كل الأجهزة متزامنة.","ok")}
  <div class="cmp-rg"><aside class="cmp-rl">${visR.map(x=>{const n=P.devs.filter(d=>d.reg===x.id&&d.st!=="revoked").length,bad=P.devs.some(d=>d.reg===x.id&&devSt(d)==="unsynced");return `<button class="${x.id===r.id?"on":""}" data-cmp="reg:${x.id}"><span class="cmp-rp">${x.pre}</span><div><b>${x.name}</b><small>${brName(x.br)} · ${num(n)} أجهزة</small></div>${bad?`<i class="cmp-dot bad" title="جهاز غير متزامن"></i>`:""}</button>`}).join("")}${cmpB("ghost sm cmp-addreg","plus","إضافة نقطة بيع","newreg")}</aside><div class="cmp-rm">${settings}</div></div>`;
 return `${HEAD("نقاط البيع والأجهزة","إعدادات كل نقطة بيع وأجهزتها: تتحكم فيها من هنا عن بعد",posLink()+btnGo("primary","plus","إضافة جهاز","cmDevicePair","reg:"+r.id))}${CM.pvbar({st:1})}${CM.view(normal,CM.empty("monitor-smartphone","لا توجد نقاط بيع بعد","نقطة البيع تجمع إعدادات الكاشير (المخزن وقائمة الأسعار والصندوق)، وتحتها الأجهزة التي تعمل بها.",cmpB("primary","plus","إضافة أول نقطة بيع","newreg")))}${modal()}`};

/* ====================================================================================== */
/* ================================== ربط جهاز (كود) ====================================== */
/* ====================================================================================== */
const mkCode=()=>{const a="ABCDEFGHJKLMNPQRSTUVWXYZ23456789";let s="";for(let i=0;i<8;i++)s+=a[Math.floor(Math.random()*a.length)];return s};
S.cmDevicePair=()=>{
 const p=P.pair,reg=getReg(P.ctx.reg);
 if(p.state==="none"||p.reg!==reg.id&&p.state==="none"){p.reg=reg.id}
 const st=p.state==="waiting"&&p.exp<=Date.now()?"expired":p.state;
 const steps=[["قدّم الكود","في التطبيق: «ربط جهاز» ثم امسح الـQR أو اكتب الثمانية خانات"],["صالح 15 دقيقة","بعدها يُلغى تلقائياً ويُولَّد كود جديد"],["مرة واحدة","يتحول الكود لرمز دخول للجهاز ولا يُعرض ثانية"]];
 let main="";
 if(st==="none")main=`<div class="card sec"><div class="sec-h">${X("tablet-smartphone")}<b>تجهيز جهاز جديد</b></div><div class="fgrid two">${FLD("نقطة البيع",`<select data-cmp-pf="reg">${P.regs.filter(x=>vis(x.id)).map(x=>`<option value="${x.id}"${x.id===reg.id?" selected":""}>${x.name}</option>`).join("")}</select>`,1,"يحمل الجهاز إعدادات هذه النقطة")}${FLD("اسم الجهاز",`<input data-cmp-pf="name" placeholder="مثال: آيباد الكاشير 2" value="${p.name||""}">`,1,"يظهر في الورديات والفواتير")}</div>
  <div class="cmp-padbtn">${cmpB("primary","qr-code","إنشاء كود الربط","paygen")}</div></div>`;
 else if(st==="waiting")main=`<div class="card cmp-pair"><div class="cmp-pq">${QR("pair"+p.code,25)}</div><div class="cmp-pc"><small>كود الربط · ${p.name||"جهاز جديد"} · ${regName(p.reg)}</small>
  <div class="cmp-code" dir="ltr">${p.code.slice(0,4)}<i></i>${p.code.slice(4)}</div>
  <div class="cmp-cdw"><span>${CM.st("pairing","بانتظار الجهاز")}</span><span class="cmp-cdt ${p.exp-Date.now()<120000?"low":""}">${X("timer")}<b class="num" data-cmp-cdp="${p.exp}">${cdFmt(p.exp-Date.now()).slice(3)}</b><small>متبقٍ من 15:00</small></span></div>
  <div class="cmp-cdbar"><i data-cmp-cdbar="${p.exp}" style="width:${Math.max(0,Math.min(100,(p.exp-Date.now())/9000))}%"></i></div>
  <div class="cmp-pacts">${cmpB("ghost sm","refresh-cw","كود جديد","paygen")}${cmpB("ghost sm cmp-red","x","إلغاء","paycancel")}</div></div></div>
  <div class="cmp-sim">${X("flask-conical")}<span>للمعاينة فقط (غير موجود في النظام):</span>${cmpB("ghost sm","tablet-smartphone","الجهاز أدخل الكود","paysimok")}${cmpB("ghost sm","timer-off","انتهاء المدة","paysimexp")}</div>`;
 else if(st==="expired")main=`<div class="card cm-empty sm"><span class="cm-empty-i cmp-ei bad">${X("timer-off")}</span><h3>انتهى كود الربط</h3><p>الكود <span class="cm-mono">${p.code}</span> صالح 15 دقيقة فقط ولم يستخدمه أي جهاز. لا يمكن إعادة تفعيله: أنشئ كوداً جديداً.</p><div class="cm-empty-a">${cmpB("primary","refresh-cw","إنشاء كود جديد","paygen")}${cmpB("ghost","","إلغاء","paycancel")}</div></div>`;
 else if(st==="paired"){const d=getDev(p.dev)||{};main=`<div class="card cm-empty sm"><span class="cm-empty-i cmp-ei ok">${X("check-circle-2")}</span><h3>تم ربط الجهاز</h3><p>«${d.n||p.name}» أصبح على نقطة «${regName(p.reg)}». الكود استُهلك ولا يمكن استخدامه مرة أخرى، ورمز دخول الجهاز لا يُعرض.</p>
  ${CM.note("خطوة أخيرة قبل أول بيع: <b>ربط الجهاز بهيئة الزكاة (ZATCA)</b>. بدونها لا يستطيع الجهاز إصدار فواتير ضريبية.","warn")}
  <div class="cm-empty-a">${btnGo("primary","shield-check","ربط الجهاز بالهيئة الآن","cmZatcaWizard","dev:"+p.dev)}${btnGo("ghost","","العودة لنقاط البيع","cmRegisters","reg:"+p.reg)}${cmpB("ghost","plus","ربط جهاز آخر","payreset")}</div></div>`}
 const side=`<div class="cm-side"><div class="card sec"><div class="sec-h">${X("list-checks")}<b>كيف يتم الربط؟</b></div><div class="cmp-how">${steps.map((s,i)=>`<div><span class="num">${i+1}</span><div><b>${s[0]}</b><small>${s[1]}</small></div></div>`).join("")}</div></div>
  ${CM.note(`الحد المسموح ${num(CM.cfg().limits.devices)} أجهزة نشطة، المستخدم منها ${num(P.devs.filter(d=>d.st!=="revoked").length)}. الأجهزة الملغاة لا تُحسب.`,"","gauge")}
  ${CM.note("الكود مثل كلمة مرور لمرة واحدة: لا تشاركه عبر واتساب إلا مع من سيُجهّز الجهاز.","warn","shield-alert")}</div>`;
 return `${HEAD("إضافة جهاز","أنشئ كود ربط ثم أدخله في تطبيق الكاشير على الجهاز الجديد",posLink(),["cmRegisters","نقاط البيع والأجهزة"])}${CM.pvbar({cost:0,br:0,st:0})}<div class="cm-split">${`<div>${main}</div>`}${side}</div>`};

/* ====================================================================================== */
/* ====================================== الكاشيرية ======================================= */
/* ====================================================================================== */
S.cmCashiers=()=>{
 const c=P.cs.find(x=>x.id===P.csSel)||P.cs[0];
 const list=P.cs.filter(x=>x.regs.some(r=>vis(r))||!x.regs.length);
 const noPin=P.cs.filter(x=>!x.pin).length;
 const normal=`
  ${CM.note(noPin?`<b>ماذا أفعل الآن؟</b> ${num(noPin)} من الكاشيرية بلا PIN ولا يستطيعون الدخول: عيّن لهم PIN.`:`<b>ماذا أفعل الآن؟</b> لا شيء عاجل. الـPIN لا يُعرض لأحد بعد حفظه، ويمكنك فقط إعادة تعيينه.`,noPin?"warn":"ok")}
  <div class="cm-split"><div class="card flush"><div class="tw"><table><thead><tr><th>الكاشير</th><th>PIN</th><th>نقاط البيع</th><th>أقصى خصم</th><th>الحالة</th></tr></thead><tbody>
  ${list.map(x=>`<tr class="${x.id===c.id?"cmp-sel":""}" data-cmp="csel:${x.id}" style="cursor:pointer"><td><b>${x.n}</b><span class="cm-sub">${x.role==="sup"?"مشرف وردية":"كاشير"}</span></td><td>${x.pin?`<span class="cm-mono">••••</span><span class="cm-sub">عُيّن ${x.pin}</span>`:`<span class="cm-flag bad">${X("circle-alert")}غير معيّن</span>`}</td><td>${x.regs.map(r=>`<span class="cmp-rtag">${nm(D.registers,r,2)}</span>`).join("")||"—"}</td><td>${num(x.maxDisc)}%</td><td>${x.active?CM.st("active"):CM.st("stopped")}</td></tr>`).join("")}</tbody></table></div>${PAGER(`${list.length} من ${list.length} كاشيرية`)}</div>
  <div class="cm-side"><div class="card sec"><div class="sec-h">${X("user-round-cog")}<b>${c.n}</b><span style="margin-inline-start:auto">${c.active?CM.st("active"):CM.st("stopped")}</span></div>
   <div class="cmp-col">
   ${FLD("الدور",`<select data-cmp-in="cs.${c.id}.role"><option value="cashier"${c.role==="cashier"?" selected":""}>كاشير</option><option value="sup"${c.role==="sup"?" selected":""}>مشرف وردية</option></select>`,0,c.role==="sup"?"يعتمد الخصم فوق الحد ويجيز المرتجعات الكبيرة":"يبيع ويقفل وردية نفسه")}
   <div class="fld"><label>نقاط البيع المسموحة</label><div class="cmp-pmchips">${P.regs.map(r=>`<button class="${c.regs.includes(r.id)?"on":""}" data-cmp="cr:${c.id}:${r.id}">${X(c.regs.includes(r.id)?"check":"plus")}${r.name}</button>`).join("")}</div></div>
   ${FLD("أقصى نسبة خصم (%)",`<input type="number" min="0" max="100" data-cmp-in="cs.${c.id}.maxDisc" data-num value="${c.maxDisc}">`,0,`الحد الأعلى لنقطة البيع بدون مشرف ${num(getReg(c.regs[0]||"r1").maxDisc)}%`)}
   <div class="fld"><label>رمز الدخول PIN</label>${P.csPin?`<div class="cmp-pin"><input type="password" inputmode="numeric" maxlength="6" data-cmp-pin="a" placeholder="PIN جديد (4 – 6 أرقام)" dir="ltr"><input type="password" inputmode="numeric" maxlength="6" data-cmp-pin="b" placeholder="أعد كتابته" dir="ltr">${P.csErr?`<small class="cmp-err">${P.csErr}</small>`:""}<div>${cmpB("primary sm","check","حفظ PIN","pinsave")}${cmpB("ghost sm","","إلغاء","pincancel")}</div></div>`:`<div class="cmp-pinbox"><span class="cm-mono">••••</span><small>${c.pin?`مخزّن مشفّراً (${c.pin}) ولا يمكن عرضه`:"لم يُعيَّن بعد"}</small>${cmpB("ghost sm","key-round",c.pin?"إعادة تعيين PIN":"تعيين PIN","pinopen")}</div>`}<small class="fhint">لا يُعرض الـPIN لأي مستخدم بعد الحفظ، ولا يُرسل نصاً: الجهاز يتحقق من نسخة مجزّأة.</small></div>
   ${ROW("الحساب نشط","الموقوف لا يستطيع الدخول لأي جهاز",`<span class="sw ${c.active?"on":""}" data-cmp="cact:${c.id}"></span>`)}
   </div></div></div></div>`;
 return `${HEAD("الكاشيرية","الموظفون المخوّلون بالبيع على نقاط البيع، وأرقام الدخول وحدود الخصم",btnGo("ghost","users","الموارد البشرية","hrWorkers")+cmpB("primary","user-plus","ربط موظف","linkopen"))}${CM.pvbar({cost:0,st:1})}${CM.view(normal,CM.empty("user-round-check","لا يوجد كاشيرية بعد","اربط موظفاً موجوداً ككاشير أو مشرف وردية، ثم عيّن له PIN ونقاط البيع المسموحة.",cmpB("primary","user-plus","ربط أول موظف","linkopen")))}${modal()}`};

/* ====================================================================================== */
/* ======================================= الورديات ======================================== */
/* ====================================================================================== */
const SHCH=[["all","الكل",""],["open","مفتوحة","n"],["closed","مقفلة — بانتظار الترحيل","a"],["posted","مرحّلة","g"]];
S.cmShifts=()=>{
 const all=shifts().filter(s=>vis(s.reg)),cat=s=>s.status==="approved"?"closed":s.status,f=P.f.sc;
 const rows=all.filter(s=>f==="all"||cat(s)===f);
 const cn=k=>k==="all"?all.length:all.filter(s=>cat(s)===k).length;
 const wait=all.filter(s=>cat(s)==="closed"),lo=all.filter(s=>s.status==="open"&&s.open>1440);
 const normal=`
  ${wait.length||lo.length?CM.note(`<b>ماذا أفعل الآن؟</b> ${wait.length?`${num(wait.length)} ورديات مقفلة تنتظر اعتماد المحاسب وترحيله. `:""}${lo.length?`الوردية ${lo.map(s=>`<span class="cm-mono num">${s.id}</span>`).join("، ")} مفتوحة منذ أكثر من يوم.`:""}`,"warn"):CM.note("<b>ماذا أفعل الآن؟</b> لا توجد ورديات تنتظر إجراء.","ok")}
  <div class="cx-chips">${SHCH.map(c=>`<button class="${f===c[0]?"on":""}" data-cmp="sc:${c[0]}">${c[2]?`<i class="d ${c[2]}"></i>`:""}${c[1]}<b class="num">${cn(c[0])}</b></button>`).join("")}</div>
  <div class="fbar"><div class="fb grow"><label>بحث</label><div class="inp">${X("search")}<input placeholder="رقم الوردية أو الكاشير أو الجهاز…"></div></div>${CM.brSel()}<div class="fb"><label>الفترة</label>${SEL(["آخر 7 أيام","اليوم","هذا الشهر"])}</div></div>
  ${rows.length?`<div class="card flush"><div class="tw"><table><thead><tr><th>الوردية</th><th>الجهاز · النقطة</th><th>الكاشير</th><th>الفتح</th><th>الإقفال</th><th>المبيعات</th><th>الفرق</th><th>الحالة</th></tr></thead><tbody>
  ${rows.map(s=>{const long=s.status==="open"&&s.open>1440;return `<tr class="${s.over?"cm-row-bad":long?"cm-row-warn":""}"><td>${go("cmShiftShow",`<b class="num cm-mono">${s.id}</b>`,"shift:"+s.id)}</td><td>${nm(P.devs.map(d=>[d.id,d.n]),s.dev)}<span class="cm-sub">${regName(s.reg)}</span></td><td>${cashName(s.cash)}</td><td class="num">${stamp(s.open)}${long?`<span class="cm-flag bad">${X("alert-triangle")}منذ ${dur(s.open)}</span>`:""}</td><td class="num">${s.close==null?"—":stamp(s.close)}</td><td>${CM.m(s.T)}</td><td>${s.diff==null?"—":s.diff===0?`<span class="cm-flag ok">${X("check")}مطابق</span>`:`<span class="${s.over?"cmp-over":""}">${CM.m(s.diff)}</span>`}</td><td>${CM.st(shSt(s))}</td></tr>`}).join("")}</tbody></table></div>${PAGER(`عرض ${rows.length} من ${all.length} وردية`)}</div>`:CM.nores()}
  <div class="cx-info">${X("info")}<span>مسار الوردية: مفتوحة (من التطبيق) ← مقفلة (بعد العدّ) ← معتمدة ← مرحّلة. الترحيل ينشئ قيداً واحداً ملخّصاً للوردية وقيد تكلفة واحداً، لا قيداً لكل فاتورة.</span></div>`;
 return `${HEAD("الورديات","ورديات الكاشير على كل نقطة بيع: المتوقع والمعدود والفرق والترحيل المحاسبي",btnGo("ghost","receipt-text","فواتير نقاط البيع","cmPosInvoices")+B("ghost","file-spreadsheet","Excel"))}${CM.pvbar()}${CM.view(normal,CM.empty("clock","لا توجد ورديات بعد","تظهر الوردية هنا بمجرد أن يفتحها كاشير من تطبيق الكاشير.",posLink()))}`};

S.cmShiftShow=()=>{
 const s=getShift(P.ctx.shift),reg=getReg(s.reg),J=shJournal(s),dev=getDev(s.dev),stS=s.status;
 const long=stS==="open"&&s.open>1440,view=P.shop[s.id],reason=P.shr[s.id]||"";
 const flowCur={open:0,closed:1,approved:2,posted:3}[stS];
 const flow=CM.flow([["مفتوحة",stamp(s.open)],["مقفلة",s.close==null?"بانتظار الإقفال":stamp(s.close)],["معتمدة",stS==="approved"||stS==="posted"?"المحاسب":""],["مرحّلة",stS==="posted"?"قيد "+(P.shje[s.id]||["JE-24131"])[0]:""]],stS==="posted"?4:flowCur);
 const pmRows=Object.keys(s.rec).map(k=>{const exp=k==="cash"?s.expCash:s.rec[k],cnt=s.counted==null?null:k==="cash"?s.counted:s.rec[k],df=cnt==null?null:r2(cnt-exp),over=k==="cash"&&s.over;return `<tr class="${over?"cm-row-bad":""}"><td><b>${PMN[k]}</b>${k==="cash"?`<span class="cm-sub">عهدة ${nf2(s.float)} + مبيعات ${nf2(s.rec.cash)}${s.ins?` + إيداع ${nf2(s.ins)}`:""}${s.outs?` − سحب ${nf2(s.outs)}`:""}</span>`:`<span class="cm-sub">من تقرير جهاز الدفع</span>`}</td><td>${CM.m(exp)}</td><td>${cnt==null?`<span class="cm-sub">بعد الإقفال</span>`:CM.m(cnt)}</td><td>${df==null?"—":df===0?`<span class="cm-flag ok">${X("check")}مطابق</span>`:`<span class="${over?"cmp-over":""}">${CM.m(df)}</span>${over?`<span class="cm-sub cmp-over">فوق أقصى عجز</span>`:""}`}</td></tr>`}).join("");
 const jtbl=CM.jprev("قيد 1 — إقفال الوردية (ملخّص)",J.main,`قيد واحد للوردية كلها بدل قيد لكل فاتورة. الإيداع والسحب النقدي لا يدخلان هذا القيد: يُسجَّلان بقيد إيداع الصندوق في البنوك والخزن.`);
 const jcogs=CM.cost()?CM.jprev("قيد 2 — تكلفة البضاعة المباعة (مجمّع)",J.cogs,`بالتكلفة المرجّحة وقت البيع، للمخزن «${nm(D.stores,reg.st)}».`):CM.note("قيد التكلفة مخفي: ليس لديك صلاحية رؤية التكلفة. يُرحَّل مع القيد الأول عند الضغط على الترحيل.","","eye-off");
 const need=s.over&&!reason;
 const acct=stS==="open"?CM.note("الوردية ما زالت مفتوحة: يُقفلها الكاشير من التطبيق بعد عدّ الصندوق، ثم تظهر هنا للاعتماد."+(long?" <b>مفتوحة منذ أكثر من يوم</b>: اتصل بالكاشير أو بمشرف الوردية لإقفالها.":""),long?"warn":"")
  :stS==="posted"?CM.ro("وردية مرحّلة — للقراءة فقط",`<a class="btn btn-ghost sm" href="#" data-go="finEntries">${X("book-open")}عرض القيد ${(P.shje[s.id]||["JE-24131","JE-24132"])[0]}</a>`)
  :`<div class="card sec cmp-acct"><div class="sec-h">${X("book-check")}<b>اعتماد وترحيل</b></div>
   ${!view?`<p class="cmp-p">راجع الفروق ثم اعتمد الوردية وارحّلها. «الاعتماد» يقرّ صحة العدّ، و«الترحيل» ينشئ القيود المحاسبية ولا يُعدَّل بعده.</p><div class="cmp-padbtn">${cmpB("primary","book-open","اعتماد وترحيل — معاينة القيد","shview")}</div>`
   :`<div class="cmp-stp"><span class="${stS==="closed"?"cur":"done"}"><b class="num">1</b>اعتماد</span><i></i><span class="${stS==="approved"?"cur":""}"><b class="num">2</b>ترحيل</span></div>
   ${s.over?`<div class="cm-note bad">${X("octagon-alert")}<div>العجز <b class="num">${nf2(-s.diff)}</b> ر.س أكبر من أقصى عجز مسموح لهذه النقطة (<span class="num">${nf2(s.maxShort)}</span>). اكتب سبب الاعتماد.</div></div><div class="fld"><label>سبب قبول العجز <i class="rq">*</i></label><textarea rows="2" data-cmp-shr="${s.id}" placeholder="مثال: خطأ في الباقي لعميل، تم التنبيه على الكاشير" ${stS!=="closed"?"disabled":""}>${reason}</textarea>${P.shErr===s.id&&need?`<small class="cmp-err">السبب مطلوب لأن العجز فوق الحد.</small>`:""}</div>`:""}
   ${jtbl}${jcogs}
   <div class="cmp-padbtn">${stS==="closed"?cmpB("primary","badge-check","اعتماد الوردية","shapprove:"+s.id):`<span class="cm-flag ok">${X("badge-check")}معتمدة — جاهزة للترحيل</span>`}${cmpB(stS==="approved"?"primary":"ghost","book-check","ترحيل (قيدان)","shpost:"+s.id,stS==="approved"?"":"disabled")}${cmpB("ghost","x","إخفاء المعاينة","shhide")}</div>`}</div>`;
 return `${HEAD(`وردية ${s.id}`,`${regName(s.reg)} · ${cashName(s.cash)} · ${stamp(s.open)}`,B("ghost","printer","طباعة تقرير الوردية")+go("cmPosInvoices",B("ghost","receipt-text","فواتير الوردية"),"f:sh"),["cmShifts","الورديات"])}${CM.pvbar({br:0,st:0})}${flow}
 ${long?CM.note(`هذه الوردية مفتوحة منذ ${dur(s.open)} (أكثر من يوم). الأجهزة المفتوحة طويلاً تتأخر مبيعاتها في القيود.`,"warn"):""}
 <div class="cm-split"><div>
  ${acct?`<div class="cmp-mb16">${acct}</div>`:""}
  <div class="card cmp-hd"><div><small>الجهاز</small><b>${dev?dev.n:s.dev}</b></div><div><small>الكاشير</small><b>${cashName(s.cash)}</b></div><div><small>وقت الفتح</small><b class="num">${stamp(s.open)}</b></div><div><small>وقت الإقفال</small><b class="num">${s.close==null?"—":stamp(s.close)}</b></div><div><small>العهدة الافتتاحية</small><b>${CM.m(s.float)}</b></div><div><small>الحالة</small>${CM.st(shSt(s))}</div></div>
  <div class="card flush cmp-mt"><div class="card-h pad"><div><h3>المتوقع والمعدود لكل طريقة دفع</h3><p>الفرق النقدي فوق «أقصى عجز» (${num(nf2(s.maxShort))} ر.س) يظهر بلون التحذير</p></div></div><div class="tw"><table><thead><tr><th>طريقة الدفع</th><th>المتوقع</th><th>المعدود</th><th>الفرق</th></tr></thead><tbody>${pmRows}<tr class="tot"><td><b>الإجمالي</b></td><td><b>${CM.m(s.expCash+s.T-(s.rec.cash||0))}</b></td><td><b>${s.counted==null?"—":CM.m(s.counted+s.T-(s.rec.cash||0))}</b></td><td><b>${s.diff==null?"—":CM.m(s.diff)}</b></td></tr></tbody></table></div></div>
  <div class="cmp-mt"><div class="card flush"><div class="card-h pad"><div><h3>الإيداع والسحب النقدي</h3><p>حركات الصندوق أثناء الوردية</p></div></div>${s.mov.length?`<div class="tw"><table><thead><tr><th>النوع</th><th>المبلغ</th><th>السبب</th><th>الوقت</th></tr></thead><tbody>${s.mov.map(m=>`<tr><td>${m[0]==="in"?TAG("g","إيداع في الصندوق"):TAG("a","سحب من الصندوق")}</td><td>${CM.m(m[1])}</td><td>${m[2]}</td><td class="num">${stamp(s.open-m[3]>0?m[3]:m[3])}</td></tr>`).join("")}</tbody></table></div>`:`<p class="cmp-none">لا توجد حركات نقدية.</p>`}</div></div>
 </div><div class="cm-side"><div class="card flush"><div class="card-h pad"><div><h3>ملخص الوردية</h3><p>${num(s.inv)} فاتورة</p></div></div><div class="cmp-sum">${[["المبيعات قبل الخصم",CM.m(s.gross)],["الخصومات","− "+CM.m(s.disc)],["المرتجعات","− "+CM.m(s.ret)],["صافي المبيعات (بدون ضريبة)",CM.m(s.net)],["ضريبة القيمة المضافة",CM.m(s.vat)],["الإجمالي المقبوض",CM.m(s.T)]].map((r,i)=>`<div class="${i===3||i===5?"t":""}"><span>${r[0]}</span><b>${r[1]}</b></div>`).join("")}</div></div></div></div>`};

/* ====================================================================================== */
/* ================================== فواتير نقاط البيع ================================== */
/* ====================================================================================== */
const ZKEY={reported:"reported",unreported:"unreported",late:"late",z_rejected:"z_rejected"};
const ICH=[["all","الكل",""],["unreported","لم تُبلّغ","a"],["late","متأخرة","r"],["z_rejected","مرفوضة من الهيئة","r"],["review","مرفوضة للمراجعة","r"],["neg","بيع بدون رصيد","a"],["gap","فجوات الترقيم","r"]];
S.cmPosInvoices=()=>{
 const f=P.f,all=invs().filter(visInv),match=i=>{const c=f.chip;
  return (c==="all"||(c==="unreported"&&(i.zs==="unreported"||i.zs==="late")&&i.ing!=="review")||(c==="late"&&i.zs==="late")||(c==="z_rejected"&&i.z==="z_rejected")||(c==="review"&&i.ing==="review")||(c==="neg"&&i.neg)||(c==="sh"&&i.sh===P.ctx.shift))&&(f.dev==="all"||i.dev===f.dev)&&(!f.q||(i.no+cashName(i.cash)+getDev(i.dev).n).toLowerCase().includes(f.q.toLowerCase()))};
 const cn=k=>k==="gap"?GAPS.length:k==="all"?all.length:all.filter(i=>({unreported:(i.zs==="unreported"||i.zs==="late")&&i.ing!=="review",late:i.zs==="late",z_rejected:i.z==="z_rejected",review:i.ing==="review",neg:!!i.neg})[k]).length;
 const rows=f.chip==="gap"?[]:all.filter(match).sort((a,b)=>a.ago-b.ago);
 const late=all.filter(i=>i.zs==="late").length,rv=cn("review");
 const gaps=f.chip==="gap"||f.chip==="all"?GAPS.filter(g=>vis(getDev(g[1]).reg)):[];
 const normal=`
  ${late||rv||cn("z_rejected")?CM.note(`<b>ماذا أفعل الآن؟</b> ${late?`${num(late)} فواتير تجاوزت 20 ساعة بلا تبليغ: أعد محاولة التبليغ قبل انتهاء مهلة 24 ساعة. `:""}${cn("z_rejected")?`${num(cn("z_rejected"))} فاتورة رفضتها الهيئة: صحّح السبب. `:""}${rv?`${num(rv)} فاتورة مرفوضة للمراجعة: لا تُفقد، وتنتظر قراراً منك.`:""}`,"warn"):CM.note("<b>ماذا أفعل الآن؟</b> كل الفواتير مبلّغة ولا شيء للمراجعة.","ok")}
  <div class="cx-chips">${ICH.map(c=>`<button class="${f.chip===c[0]?"on":""}" data-cmp="ichip:${c[0]}">${c[2]?`<i class="d ${c[2]}"></i>`:""}${c[1]}<b class="num">${cn(c[0])}</b></button>`).join("")}</div>
  <div class="fbar"><div class="fb grow"><label>بحث</label><div class="inp">${X("search")}<input data-cmp-f="q" value="${f.q}" placeholder="رقم الفاتورة أو الجهاز أو الكاشير…"></div></div>
   <div class="fb"><label>الجهاز</label><select data-cmp-f="dev"><option value="all">كل الأجهزة</option>${P.devs.filter(d=>vis(d.reg)&&d.st!=="pairing").map(d=>`<option value="${d.id}"${f.dev===d.id?" selected":""}>${d.n}</option>`).join("")}</select></div>
   <div class="fb"><label>التاريخ</label>${SEL(["آخر 7 أيام","اليوم","أمس"])}</div>${CM.brSel()}</div>
  ${rows.length||gaps.length?`<div class="card flush"><div class="tw"><table><thead><tr><th>الفاتورة</th><th>الوقت</th><th>الجهاز · الكاشير</th><th>الدفع</th><th>الإجمالي</th><th>الاستلام</th><th>ZATCA</th><th class="act"></th></tr></thead><tbody>
  ${gaps.map(g=>`<tr class="cm-row-bad cmp-gaprow"><td><b class="num cm-mono">${g[0]}</b><span class="cm-flag bad">${X("triangle-alert")}فجوة ترقيم</span></td><td colspan="6" style="white-space:normal">لم تصل من «<bdi>${getDev(g[1]).n}</bdi>» بين <span class="cm-mono num">A2-000411</span> و<span class="cm-mono num">A2-000413</span>. اطلب مزامنة الجهاز.</td><td class="cmp-ac">${btnGo("ghost sm","wifi","الأجهزة","cmRegisters","reg:r2")}</td></tr>`).join("")}
  ${rows.map(i=>`<tr class="${i.zs==="late"||i.z==="z_rejected"||i.ing==="review"?"cm-row-bad":i.zs==="unreported"?"cm-row-warn":""}"><td>${go("cmPosInvoiceShow",`<b class="num cm-mono">${i.no}</b>`,"inv:"+i.no)}${i.ret?`<span class="cm-sub">مرتجع على ${i.orig}</span>`:""}${i.neg?`<span class="cm-flag">${X("package-x")}بيع بدون رصيد</span>`:""}</td><td class="num">${stamp(i.ago)}<span class="cm-sub">${agoT(i.ago)}</span></td><td>${getDev(i.dev).n}<span class="cm-sub">${cashName(i.cash)}</span></td><td>${i.pays.map(p=>PMN[p[0]]).join(" + ")}</td><td>${CM.m(i.total)}</td><td>${CM.st(i.ing)}</td><td>${CM.st(i.zs)}${i.zs==="late"?`<span class="cm-sub">${dur(i.ago)} بلا تبليغ</span>`:""}</td><td class="act">${ACTS([["eye","عرض",`data-go="cmPosInvoiceShow" data-cmp-ctx="inv:${i.no}"`],["printer","إعادة طباعة",`data-cmp="reprint:${i.no}"`]])}</td></tr>`).join("")}
  </tbody></table></div>${PAGER(`عرض ${rows.length} من ${all.length} فاتورة`)}</div>`:CM.nores()}
  ${f.chip==="neg"?CM.note("«بيع بدون رصيد»: بيعت الكمية والرصيد صفر أو أقل، فصار رصيد المخزن سالباً. التكلفة تُحسب بآخر متوسط معروف ويُصحَّح الرصيد عند الاستلام أو الجرد.","","info"):""}`;
 return `${HEAD("فواتير نقاط البيع","فواتير الكاشير بعد رفعها من الأجهزة، وحالة تبليغها للهيئة",B("ghost","file-spreadsheet","Excel")+btnGo("ghost","shield-check","وحدات ZATCA","cmZatca"))}${CM.pvbar()}${CM.view(normal,CM.empty("receipt-text","لا توجد فواتير كاشير بعد","تصل الفواتير هنا من أجهزة الكاشير بعد أول عملية بيع.",posLink()))}`};

S.cmPosInvoiceShow=()=>{
 const i=getInv(P.ctx.inv),d=getDev(i.dev),reg=getReg(i.reg),sh=shObj(SHIFTS.find(a=>a[0]===i.sh)||SHIFTS[3]);
 const seq=i.seq,icv=(P.units[i.dev]?P.units[i.dev].icv:900)-(INVS.filter(a=>a[1]===i.dev&&+a[0].split("-")[1]>seq).length),hash=HS(i.no,44),pih=HS(i.dev+"-"+(seq-1),44);
 const flowSteps=[["صدرت على الجهاز",stamp(i.ago)],["رُفعت للسيرفر",i.ing==="review"?"مرفوضة للمراجعة":stamp(i.ago-1)],["أُبلغت للهيئة",i.zs==="reported"?stamp(i.ago-2):i.z==="z_rejected"?"رفضتها الهيئة":"لم تُبلّغ"]];
 const cur=i.z==="reported"?3:i.ing==="review"?1:i.z==="z_rejected"?2:2;
 const flow=CM.flow(flowSteps,cur,i.z==="z_rejected"||i.ing==="review"||i.zs==="late");
 const retry=P.retry===i.no;
 const rej=i.z==="z_rejected"?`<div class="cm-note bad">${X("shield-x")}<div><b>ردّ الهيئة (رفض)</b><div class="cmp-resp"><span class="cm-mono">status: REJECTED · HTTP 400</span><br><span class="cm-mono">${i.rej}</span></div><small>المطلوب: صحّح بيانات العميل أو أصدر إشعاراً دائناً وفاتورة بديلة. «إعادة المحاولة» لن تنجح قبل تصحيح السبب.</small></div></div>`:"";
 const late=i.zs==="late"?CM.note(`مضى على الفاتورة ${dur(i.ago)} بلا تبليغ. المهلة النظامية 24 ساعة، وتتبقى ${dur(1440-i.ago)}.`,"bad","alarm-clock"):"";
 const rv=i.ing==="review"?`<div class="cm-note bad">${X("alert-triangle")}<div><b>مرفوضة للمراجعة — لا تُفقد</b><br>${i.why}<br><small>السيرفر أعاد حساب الإجماليات ورفض الفاتورة لأن الفرق أكبر من 0.01. لا يُنشأ لها قيد ولا تُبلّغ قبل قرارك.</small></div></div><div class="cmp-padbtn">${cmpB("primary","check","قبول بإجمالي السيرفر","revok:"+i.no)}${cmpB("ghost","x","رفض نهائي","revno:"+i.no)}</div>`:"";
 const dup=i.ing==="duplicate"?CM.note("وصلت هذه الفاتورة أكثر من مرة (نفس client_uuid): أُخذت نسخة واحدة وتجاهل السيرفر التكرار. لا إجراء مطلوب.","","copy"):"";
 const ztone=i.zs==="reported"?"":`<div class="cmp-padbtn">${cmpB("primary","send",retry?"جاري التبليغ…":"إعادة محاولة التبليغ","retry:"+i.no,retry?"disabled":"")}</div>`;
 return `${HEAD(`فاتورة ${i.no}`,`${i.ret?"مرتجع":"فاتورة ضريبية مبسّطة"} · ${d.n} · ${cashName(i.cash)} · ${stamp(i.ago)}`,cmpB("ghost","printer","إعادة طباعة","reprint:"+i.no)+B("ghost","share-2","مشاركة"),["cmPosInvoices","فواتير نقاط البيع"])}${CM.pvbar({br:0,st:0})}${flow}${late}${rej}${rv}${dup}
 <div class="cm-split"><div>
  <div class="card flush"><div class="card-h pad"><div><h3>بنود الفاتورة</h3><p>الأسعار شاملة ضريبة 15%${i.neg?` · <span class="cm-flag">${X("package-x")}بيع بدون رصيد</span>`:""}</p></div></div><div class="tw"><table><thead><tr><th>الصنف</th><th>الكمية</th><th>السعر</th><th>الإجمالي</th></tr></thead><tbody>
  ${i.rows.map(r=>`<tr><td><b>${r.it.n}</b><span class="cm-sub num">${r.it.code}</span></td><td>${CM.q(r.q,r.u)}</td><td>${CM.m(r.p)}</td><td>${CM.m(i.ret?-r.t:r.t)}</td></tr>`).join("")}
  <tr class="tot"><td colspan="3"><span class="cm-sub" style="display:inline">الإجمالي قبل الضريبة ${nf2(i.ret?-i.net:i.net)} · الضريبة ${nf2(i.ret?-i.vat:i.vat)}</span></td><td><b>${CM.m(i.total)}</b></td></tr></tbody></table></div></div>
  <div class="card flush cmp-mt"><div class="card-h pad"><div><h3>الدفع</h3></div></div><div class="tw"><table><thead><tr><th>الطريقة</th><th>المبلغ</th></tr></thead><tbody>${i.pays.map(p=>`<tr><td>${PMN[p[0]]}</td><td>${CM.m(p[1])}</td></tr>`).join("")}</tbody></table></div></div>
  <div class="card sec cmp-mt"><div class="sec-h">${X("shield-check")}<b>بيانات ZATCA (للتدقيق)</b></div><div class="cmp-zk"><div><small>ICV — عداد الفواتير للوحدة</small>${mono(icv)}</div><div><small>PIH — هاش الفاتورة السابقة</small>${mono(pih)}</div><div class="w"><small>هاش هذه الفاتورة</small>${mono(hash)}</div><div><small>وحدة الإصدار</small>${mono(d.unit||"—")}</div></div></div>
 </div><div class="cm-side">
  <div class="card sec"><div class="sec-h">${X("activity")}<b>الحالة</b></div><div class="cmp-stl"><div><span>الاستلام من الجهاز</span>${CM.st(i.ing)}</div><div><span>تبليغ الهيئة</span>${CM.st(i.zs)}</div><div><span>الوردية</span>${go("cmShiftShow",`<span class="num cm-mono">${i.sh}</span>`,"shift:"+i.sh)}</div><div><span>الوردية مرحّلة؟</span>${sh.status==="posted"?TAG("g","نعم — بقيد"):TAG("a","لا بعد")}</div></div>${ztone}</div>
  <div class="card sec cmp-qrc"><div class="sec-h">${X("qr-code")}<b>رمز QR (TLV)</b></div>${QR(i.no+hash,25)}<small>اسم البائع · الرقم الضريبي · الوقت · الإجمالي · الضريبة · التوقيع</small></div>
 </div></div>`};

/* ====================================================================================== */
/* ====================================== طرق الدفع ======================================= */
/* ====================================================================================== */
const KINDS=[["cash","نقدي"],["mada","مدى"],["card","بطاقة ائتمان"],["applepay","Apple Pay"],["transfer","تحويل"],["credit","آجل"],["voucher","قسيمة"]];
const PACC=["صندوق نقطة البيع","1131 وسيط مدى","1132 وسيط البطاقات","1124 عملاء نقاط البيع","2135 قسائم مدفوعة مقدماً"];
S.cmPayMethods=()=>{
 const pm=P.pay;
 const normal=`
  ${CM.note("<b>ماذا أفعل الآن؟</b> رتّب الطرق بحسب الأكثر استخداماً: الترتيب هنا هو ترتيب الأزرار في شاشة الدفع بالكاشير. العمولة تُستخدم في تسوية البطاقات، لا في قيد الوردية.","")}
  <div class="card flush"><div class="tw"><table><thead><tr><th style="width:90px">الترتيب</th><th>الاسم</th><th>النوع</th><th>الحساب</th><th style="width:110px">العمولة %</th><th>مفعّلة</th></tr></thead><tbody>
  ${pm.map((p,i)=>{const off=p.kind==="credit"&&!CM.on("credit_sales");return `<tr class="${p.on?"":"cmp-dim"}"><td><div class="cmp-ord"><button data-cmp="pmv:${p.id}:-1" ${i===0?"disabled":""}>${X("chevron-up")}</button><b class="num">${i+1}</b><button data-cmp="pmv:${p.id}:1" ${i===pm.length-1?"disabled":""}>${X("chevron-down")}</button></div></td>
   <td><input class="cmp-in" data-cmp-in="pay.${p.id}.n" value="${p.n}">${off?`<span class="cm-sub">لا يظهر بالكاشير: ميزة «البيع الآجل» غير مفعّلة</span>`:""}</td>
   <td><select class="cmp-in" data-cmp-in="pay.${p.id}.kind">${KINDS.map(k=>`<option value="${k[0]}"${p.kind===k[0]?" selected":""}>${k[1]}</option>`).join("")}</select></td>
   <td><select class="cmp-in" data-cmp-in="pay.${p.id}.acc">${PACC.map(a=>`<option${p.acc===a?" selected":""}>${a}</option>`).join("")}</select></td>
   <td><input class="cmp-in num" type="number" step="0.1" min="0" data-cmp-in="pay.${p.id}.fee" data-num value="${p.fee}" ${p.kind==="cash"?"disabled":""}></td>
   <td><span class="sw ${p.on?"on":""}" data-cmp="pmon:${p.id}"></span></td></tr>`}).join("")}</tbody></table></div>
  <div class="tfoot"><span>اسحب بالأسهم لتغيير الترتيب</span><div>${cmpB("ghost sm","plus","إضافة طريقة دفع","pmadd")} ${cmpB("primary sm","save","حفظ","pmsave")}</div></div></div>
  <div class="grid-2 cmp-mt"><div class="card sec"><div class="sec-h">${X("smartphone")}<b>هكذا تظهر في شاشة الدفع</b></div><div class="cmp-payprev"><small>الإجمالي المستحق</small><b class="num">136.40 <span>ر.س</span></b><div>${pm.filter(p=>p.on&&!(p.kind==="credit"&&!CM.on("credit_sales"))).map((p,i)=>`<span class="${i===0?"first":""}"><em class="num">${i+1}</em>${p.n}</span>`).join("")}</div></div></div>
   <div class="card sec"><div class="sec-h">${X("calculator")}<b>مثال على العمولة</b></div>${(()=>{const m=pm.find(p=>p.kind==="mada")||pm[1];return `<div class="cmp-sum" style="padding:0"><div><span>عملية ${m.n}</span><b>${CM.m(1000)}</b></div><div><span>العمولة ${num(m.fee)}%</span><b>− ${CM.m(m.fee*10)}</b></div><div class="t"><span>الوارد المتوقع للبنك</span><b>${CM.m(1000-m.fee*10)}</b></div></div>`})()}</div></div>`;
 return `${HEAD("طرق الدفع","الطرق المتاحة في الكاشير، وحساب كل طريقة وعمولتها وترتيب أزرارها",btnGo("ghost","landmark","تسويات البطاقات","cmSettle")+cmpB("primary","save","حفظ","pmsave"))}${CM.pvbar({cost:0,br:0})}${CM.view(normal,CM.empty("credit-card","لا توجد طرق دفع","أضف طريقة الدفع الأولى (نقدي) ليستطيع الكاشير البيع.",cmpB("primary","plus","إضافة طريقة","pmadd")))}`};

/* ====================================================================================== */
/* ======================================== العروض ========================================= */
/* ====================================================================================== */
const itn=id=>{const it=id?D.item(id):null;return it?it.n:"كل الأصناف"};
const itu=(id,cap)=>{const it=D.item(id);return it?it.units[0][0]:"حبة"};
const sentence=p=>{if(!p)return "";const b=(p.br||[]).length===2||!(p.br||[]).length?"في كل الفروع":"في "+p.br.map(brName).join(" و");const w=`${p.from?` من ${p.from}`:""}${p.to?` إلى ${p.to}`:""}`;
 const scope=p.scope==="all"?"على كل الأصناف":p.scope==="cat"?`على «${(D.item(p.it).cat||"").split(" ← ").pop()}»`:`على «${itn(p.it)}»`;
 let s="";
 if(p.t==="percent")s=`خصم ${num(+p.v||0)}% ${scope}`;
 else if(p.t==="amount")s=`خصم ${num(nf2(+p.v||0))} ر.س ${scope==="على كل الأصناف"?"على كل فاتورة":`على «${itn(p.it)}»`}`;
 else s=`اشترِ ${num(+p.x||0)} ${itu(p.it)} ${itn(p.it)} واحصل على ${num(+p.y||0)} ${itu(p.it)} ${p.free===0?"بخصم 50%":"مجاناً"}`;
 return `${s}${b?" — ":""}${b}${w?"،"+w:""}.`};
const PRT={percent:["نسبة خصم","percent"],amount:["مبلغ خصم","banknote"],bxgy:["اشترِ X واحصل على Y","gift"]};
S.cmPromos=()=>{
 const dr=P.pr.draft,edit=dr&&P.pr.list.find(x=>x.id===dr.id);
 const pst=p=>!p.on?"stopped":p.to<"2026-11-20"?"expired":p.from>"2026-11-20"?"scheduled":"active";
 const form=dr?`<div class="card sec"><div class="sec-h">${X("wand-sparkles")}<b>${edit?"تعديل العرض":"عرض جديد"}</b></div><div class="cmp-col two">
  ${FLD("اسم العرض",`<input data-cmp-in="pr.draft.n" value="${dr.n}" placeholder="مثال: خصم نهاية الأسبوع">`,1)}
  <div class="fld"><label>نوع القاعدة</label><div class="cmp-seg">${Object.keys(PRT).map(k=>`<button class="${dr.t===k?"on":""}" data-cmp="prt:${k}">${X(PRT[k][1])}${PRT[k][0]}</button>`).join("")}</div></div>
  ${dr.t==="percent"?FLD("نسبة الخصم %",`<input type="number" min="1" max="100" data-cmp-in="pr.draft.v" data-num value="${dr.v}">`,1)+FLD("يُطبّق على",`<select data-cmp-in="pr.draft.scope" data-cmp-re><option value="all"${dr.scope==="all"?" selected":""}>كل الأصناف</option><option value="cat"${dr.scope==="cat"?" selected":""}>تصنيف الصنف المختار</option><option value="item"${dr.scope==="item"?" selected":""}>صنف محدد</option></select>`):""}
  ${dr.t==="percent"&&dr.scope!=="all"?FLD(dr.scope==="cat"?"صنف من التصنيف":"الصنف",`<select data-cmp-in="pr.draft.it" data-num>${D.items.filter(i=>i.kind==="stock").map(i=>`<option value="${i.id}"${+dr.it===i.id?" selected":""}>${i.n}</option>`).join("")}</select>`):""}
  ${dr.t==="amount"?FLD("مبلغ الخصم (ر.س)",`<input type="number" min="0" step="0.5" data-cmp-in="pr.draft.v" data-num value="${dr.v}">`,1)+FLD("على الصنف",`<select data-cmp-in="pr.draft.it" data-num>${D.items.filter(i=>i.kind==="stock").map(i=>`<option value="${i.id}"${+dr.it===i.id?" selected":""}>${i.n}</option>`).join("")}</select>`):""}
  ${dr.t==="bxgy"?`<div class="fgrid two" style="padding:0">${FLD("اشترِ (كمية)",`<input type="number" min="1" data-cmp-in="pr.draft.x" data-num value="${dr.x}">`,1)}${FLD("واحصل على (كمية)",`<input type="number" min="1" data-cmp-in="pr.draft.y" data-num value="${dr.y}">`,1)}</div>${FLD("الصنف",`<select data-cmp-in="pr.draft.it" data-num>${D.items.filter(i=>i.kind==="stock").map(i=>`<option value="${i.id}"${+dr.it===i.id?" selected":""}>${i.n}</option>`).join("")}</select>`,1,"الصنف المشترى هو نفسه المجاني")}`:""}
  <div class="fld"><label>الفروع</label><div class="cmp-pmchips">${D.branches.map(b=>`<button class="${dr.br.includes(b[0])?"on":""}" data-cmp="prb:${b[0]}">${X(dr.br.includes(b[0])?"check":"plus")}${b[1]}</button>`).join("")}</div></div>
  <div class="fgrid two" style="padding:0">${FLD("من تاريخ",`<input type="date" data-cmp-in="pr.draft.from" value="${dr.from}">`,1)}${FLD("إلى تاريخ",`<input type="date" data-cmp-in="pr.draft.to" value="${dr.to}">`,1)}</div>
  <div class="cmp-sent">${X("quote")}<div><small>ما سيراه الكاشير والعميل</small><b id="cmp-sent">${sentence(dr)}</b></div></div>
  ${P.pr.err?`<small class="cmp-err">${P.pr.err}</small>`:""}
  <div class="cmp-padbtn">${cmpB("primary","check","حفظ العرض","prsave")}${cmpB("ghost","","إلغاء","prcancel")}</div></div></div>`
  :`<div class="card sec cm-empty sm"><span class="cm-empty-i">${X("badge-percent")}</span><h3>منشئ العروض</h3><p>اختر عرضاً من القائمة لتعديله، أو أنشئ عرضاً جديداً. تصل العروض للأجهزة مع أول مزامنة.</p><div class="cm-empty-a">${cmpB("primary","plus","عرض جديد","prnew")}</div></div>`;
 const normal=`
  ${CM.note("<b>ماذا أفعل الآن؟</b> العروض تُطبَّق تلقائياً في الكاشير حسب الفرع والتاريخ، ولا تتعارض مع أقصى خصم للكاشير. جمع عرضين على الصنف نفسه لا يتم: يُطبَّق الأعلى خصماً.","")}
  <div class="cmp-stack"><div class="card flush"><div class="tw"><table><thead><tr><th>العرض</th><th>القاعدة</th><th>الفروع</th><th>الفترة</th><th>الحالة</th><th class="act"></th></tr></thead><tbody>
  ${P.pr.list.map(p=>`<tr class="${p.id===(dr&&dr.id)?"cmp-sel":""}"><td><b>${p.n}</b></td><td>${sentence(p).split(" — ")[0]}</td><td>${p.br.map(brName).map(b=>b.split(" — ")[0]).join("، ")}</td><td class="num">${p.from} ← ${p.to}</td><td>${CM.st(pst(p))}</td><td class="act">${ACTS([["pencil","تعديل",`data-cmp="predit:${p.id}"`],["copy","نسخ",`data-cmp="prdup:${p.id}"`]])}</td></tr>`).join("")}</tbody></table></div>${PAGER(`${P.pr.list.length} عروض`)}</div>
  ${form}</div>`;
 return `${HEAD("العروض","خصومات تلقائية في الكاشير: نسبة، مبلغ، أو اشترِ واحصل",cmpB("primary","plus","عرض جديد","prnew"))}${CM.pvbar({cost:0})}${CM.view(normal,CM.empty("badge-percent","لا توجد عروض بعد","أنشئ أول عرض: خصم بنسبة، أو مبلغ ثابت، أو «اشترِ 2 واحصل على 1».",cmpB("primary","plus","عرض جديد","prnew")))}`};

/* ====================================================================================== */
/* ================================== تسويات البطاقات ==================================== */
/* ====================================================================================== */
/* [id, اليوم قبل (أيام), الطريقة, المتوقع من الورديات, الوارد للبنك, عمولة البنك] */
const SETL=[
 ["st1",1,"mada",7265.5,7207.42,58.08],["st2",1,"card",2310,2259.18,50.82],["st3",1,"apple",640,0,0],
 ["st4",2,"mada",8120,8055.04,64.96],["st5",2,"card",3055.5,2988.28,67.22],
 ["st6",3,"mada",3640,3610.88,29.12],["st7",3,"card",1120,1050.0,24.64]];
const setlO=a=>{const m=D.pay.find(p=>p[0]===(a[2]==="apple"?"apple":a[2])),rate=m[4];const ov=P.setl[a[0]];
 const o={id:a[0],day:a[1],k:a[2],exp:a[3],rec:ov?ov.rec:a[4],fee:ov?ov.fee:a[5],rate,m};o.efee=r2(o.exp*rate/100);o.net=r2(o.exp-o.efee);
 o.diff=r2(o.exp-o.rec-o.fee)+0;if(Math.abs(o.diff)<0.005)o.diff=0;o.st=ov&&ov.je?"settled":Math.abs(o.diff)<0.5&&o.rec>0?"matched":"diff";o.je=ov&&ov.je;
 if(a[0]==="st3"&&!ov)o.st="pending";return o};
const dayTxt=d=>d===1?"أمس — 19 نوفمبر":d===2?"18 نوفمبر":"17 نوفمبر";
S.cmSettle=()=>{
 const rows=SETL.map(setlO),sel=rows.find(r=>r.id===P.setlSel);
 const bad=rows.filter(r=>r.st==="diff"),pend=rows.filter(r=>r.st==="pending"),ready=rows.filter(r=>r.st==="matched");
 let panel=`<div class="card sec cm-empty sm"><span class="cm-empty-i">${X("landmark")}</span><h3>اختر يوماً للتسوية</h3><p>اضغط «تسوية» على صف: تظهر معاينة القيد وتتحقق من الفرق قبل الترحيل.</p></div>`;
 if(sel){const I=P.setlIn[sel.id]||{},rec=I.rec!=null?+I.rec:sel.rec,fee=I.fee!=null?+I.fee:sel.fee,df=r2(sel.exp-rec-fee),clr=acc(sel.k==="card"?D.pay[2][3]:D.pay[1][3]),fa=acc(D.accounts.fees);
  const L=[["البنك — الحساب الجاري","1121",rec,0,""],[fa[0],fa[1],fee,0,""]];
  if(Math.abs(df)>=0.005&&I.park)L.push(["فروقات تسوية بطاقات — تحت المراجعة","1139",df>0?df:0,df<0?-df:0,""]);
  L.push([clr[0],clr[1],0,sel.exp,""]);
  const bal=Math.abs(L.reduce((a,l)=>a+(+l[2]||0)-(+l[3]||0),0))<0.005;
  panel=`<div class="card sec"><div class="sec-h">${X("landmark")}<b>تسوية ${sel.m[1]} — ${dayTxt(sel.day)}</b></div>
  ${sel.st==="settled"?CM.ro("تسوية مرحّلة — للقراءة فقط",`<a class="btn btn-ghost sm" href="#" data-go="finEntries">${X("book-open")}عرض القيد ${sel.je}</a>`)
  :`<div class="fgrid two" style="padding:0 0 12px">${FLD("المتوقع من الورديات",`<input value="${nf2(sel.exp)}" disabled>`)}${FLD("الوارد في كشف البنك",`<input type="number" step="0.01" data-cmp-sin="rec" value="${rec}">`,1)}${FLD("العمولة الفعلية (البنك)",`<input type="number" step="0.01" data-cmp-sin="fee" value="${fee}">`,1,`المتوقعة ${nf2(sel.efee)} (${sel.rate}%)`)}${FLD("الفرق",`<input value="${nf2(df)}" disabled class="${Math.abs(df)>=0.5?"cmp-bad":""}">`)}</div>
  ${Math.abs(df)>=0.5?`<div class="cm-note bad">${X("alert-triangle")}<div><b>فرق غير مفسّر ${nf2(df)} ر.س</b><br>المتوقع − الوارد − العمولة ≠ صفر. افحص كشف البنك: عملية معلّقة، أو عمولة أعلى، أو عملية لم تُسجَّل. عدّل المبالغ إن كانت مدخلة خطأً، أو انقل الفرق لحساب «تحت المراجعة».</div></div><label class="cmp-ck"><input type="checkbox" data-cmp-sin="park" ${I.park?"checked":""}> ترحيل الفرق إلى حساب «فروقات تسوية بطاقات — تحت المراجعة»</label>`:""}
  ${CM.jprev("معاينة قيد التسوية",L,"البنك + العمولات من/إلى وسيط "+sel.m[1]+" — حساب «فروقات تحت المراجعة» جديد ويُنشأ عند أول استخدام.")}
  <div class="cmp-padbtn">${cmpB("primary","book-check","ترحيل التسوية","setlpost:"+sel.id,bal&&(Math.abs(df)<0.5||I.park)?"":"disabled")}${cmpB("ghost","x","إغلاق","setlclose")}</div>${!bal||(Math.abs(df)>=0.5&&!I.park)?`<small class="cmp-err">لا يمكن الترحيل: ${!bal?"القيد غير متوازن.":"الفرق غير مفسّر."}</small>`:""}`}</div>`}
 const normal=`
  ${bad.length||pend.length||ready.length?CM.note(`<b>ماذا أفعل الآن؟</b> ${ready.length?`${num(ready.length)} تسويات مطابقة جاهزة للترحيل. `:""}${bad.length?`${num(bad.length)} فرق غير مفسّر يحتاج مراجعة كشف البنك. `:""}${pend.length?`${num(pend.length)} طريقة لم يصل لها إيداع بعد (Apple Pay عادة يتأخر يومين).`:""}`,bad.length?"warn":""):CM.note("<b>ماذا أفعل الآن؟</b> كل التسويات مرحّلة.","ok")}
  <div class="cmp-stack"><div class="card flush"><div class="tw"><table><thead><tr><th>اليوم</th><th>الطريقة</th><th>المتوقع (الورديات)</th><th>الوارد للبنك</th><th>العمولة</th><th>الفرق</th><th>الحالة</th><th class="act"></th></tr></thead><tbody>
  ${rows.map(r=>`<tr class="${r.st==="diff"?"cm-row-bad":r.id===P.setlSel?"cmp-sel":""}"><td>${dayTxt(r.day)}</td><td><b>${r.m[1]}</b><span class="cm-sub">عمولة متوقعة ${num(r.rate)}% = ${nf2(r.efee)}</span></td><td>${CM.m(r.exp)}</td><td>${r.rec?CM.m(r.rec):`<span class="cm-sub">لم يصل بعد</span>`}</td><td>${r.rec?CM.m(r.fee):"—"}</td><td>${r.st==="pending"?"—":Math.abs(r.diff)<0.5?`<span class="cm-flag ok">${X("check")}${nf2(r.diff)}</span>`:`<span class="cmp-over">${CM.m(r.diff)}</span>`}</td><td>${r.st==="pending"?CM.st("unpaid","لم يصل"):r.st==="settled"?CM.st("posted","مرحّلة"):CM.st(r.st)}</td><td class="cmp-ac">${r.st==="settled"?cmpB("ghost sm","eye","عرض","setlsel:"+r.id):r.st==="pending"?"—":cmpB(r.st==="diff"?"primary sm":"ghost sm","landmark","تسوية","setlsel:"+r.id)}</td></tr>`).join("")}</tbody></table></div>${PAGER(`${rows.length} تسويات · آخر 3 أيام`)}</div>
  ${panel}</div>
  <div class="cx-info">${X("info")}<span>المتوقع مأخوذ من ورديات مرحّلة فقط. الفرق = المتوقع − الوارد − العمولة، ويُقبل حتى 0.50 ريال تقريباً.</span></div>`;
 return `${HEAD("تسويات البطاقات","مطابقة مبيعات البطاقات في الورديات مع ما وصل البنك فعلاً بعد العمولة",btnGo("ghost","credit-card","طرق الدفع","cmPayMethods")+B("ghost","upload","استيراد كشف البنك"))}${CM.pvbar({cost:0,br:0})}${CM.view(normal,CM.empty("landmark","لا توجد تسويات بعد","تظهر التسويات هنا بعد ترحيل أول وردية فيها مبيعات بطاقات.",""))}`};

/* ====================================================================================== */
/* ================================== وحدات ZATCA والمعالج ================================= */
/* ====================================================================================== */
const unitRows=()=>P.devs.map(d=>{const u=P.units[d.id];return {d,u,st:d.st==="revoked"?"revoked":u?"active":"pairing"}});
const daysTo=s=>Math.round((new Date(s)-new Date(2026,10,20))/864e5);
S.cmZatca=()=>{
 const rows=unitRows().filter(r=>vis(r.d.reg)||r.d.st==="revoked"&&CM.allBr()),iv=invs();
 const expiring=rows.filter(r=>r.u&&r.st==="active"&&daysTo(r.u.exp)<=30),rej=iv.filter(i=>i.z==="z_rejected"),late=iv.filter(i=>i.zs==="late"),unlinked=rows.filter(r=>r.st==="pairing");
 const alerts=[];
 expiring.forEach(r=>alerts.push(["calendar-clock","a",`شهادة «${r.d.n}» تنتهي بعد <b class="num">${daysTo(r.u.exp)}</b> يوماً (${r.u.exp}) — جدّدها قبل الانتهاء وإلا توقف إصدار الفواتير على الجهاز.`,btnGo("ghost sm","refresh-cw","تجديد الشهادة","cmZatcaWizard","unit:"+r.d.id)]));
 rej.forEach(i=>alerts.push(["shield-x","r",`فاتورة <b class="num cm-mono">${i.no}</b> رفضتها الهيئة.`,go("cmPosInvoices","عرض الفاتورة","f:z_rejected","btn btn-ghost sm")]));
 if(late.length)alerts.push(["alarm-clock","r",`<b class="num">${late.length}</b> فواتير لم تُبلّغ منذ أكثر من 20 ساعة (المهلة 24).`,go("cmPosInvoices","عرضها","f:late","btn btn-ghost sm")]);
 unlinked.forEach(r=>alerts.push(["plug-zap","a",`الجهاز «${r.d.n}» لم يُربط بالهيئة بعد: لا يستطيع إصدار فواتير.`,btnGo("primary sm","shield-check","ربط بالهيئة","cmZatcaWizard","dev:"+r.d.id)]));
 const normal=`
  ${alerts.length?`<div class="card cmp-al"><div class="sec-h">${X("bell-ring")}<b>ماذا أفعل الآن؟</b><small>${num(alerts.length)} تنبيهات</small></div>${alerts.map(a=>`<div class="${a[1]}">${X(a[0])}<span>${a[2]}</span>${a[3]}</div>`).join("")}</div>`:CM.note("<b>ماذا أفعل الآن؟</b> لا توجد تنبيهات: كل الشهادات سارية والفواتير مبلّغة.","ok")}
  <div class="card flush"><div class="tw"><table><thead><tr><th>الوحدة</th><th>الحالة</th><th>الشهادة</th><th>تاريخ الانتهاء</th><th>آخر ICV</th><th></th></tr></thead><tbody>
  <tr class="cmp-sys"><td><b>النظام</b><span class="cm-sub">فواتير لوحة التحكم (B2B) — الوحدة الحالية كما هي بلا تغيير</span></td><td>${CM.st("active")}</td><td>${TAG("g","إنتاج")}</td><td class="num">2027-04-18<span class="cm-sub">بعد ${num(daysTo("2027-04-18"))} يوماً</span></td><td class="num">${num("12,884")}</td><td class="cmp-ac"><span class="cm-flag">${X("lock")}للقراءة فقط</span></td></tr>
  ${rows.map(r=>{const d=r.d,u=r.u,ex=u&&r.st==="active"&&daysTo(u.exp)<=30;return `<tr class="${r.st==="revoked"?"cmp-dim":ex?"cm-row-warn":""}"><td><b>${d.n}</b><span class="cm-sub">${regName(d.reg)}${d.unit?" · "+d.unit:""}</span></td><td>${r.st==="revoked"?CM.st("revoked"):u?CM.st("active"):CM.st("pairing","غير مربوط")}</td><td>${u?(u.cert==="revoked"?TAG("r","مسحوبة"):TAG("g","إنتاج")):"—"}</td><td class="num">${u?u.exp:"—"}${ex?`<span class="cm-flag">${X("calendar-clock")}بعد ${num(daysTo(u.exp))} يوماً</span>`:""}</td><td class="num">${u?num(u.icv):"—"}</td><td>${u?u.env:"—"}</td><td class="cmp-ac">${r.st==="pairing"?btnGo("primary sm","shield-check","ربط بالهيئة","cmZatcaWizard","dev:"+d.id):r.st==="active"?btnGo("ghost sm","refresh-cw","تجديد","cmZatcaWizard","unit:"+d.id):"—"}</td></tr>`}).join("")}</tbody></table></div>${PAGER("وحدة النظام + "+rows.length+" أجهزة")}</div>
  <div class="cx-info">${X("info")}<span>كل جهاز كاشير وحدة إصدار مستقلة بشهادتها وعدّاد فواتيرها (ICV). المفتاح الخاص يُولَّد على الجهاز ولا يغادره. وحدة «النظام» لا تُمسّ.</span></div>`;
 return `${HEAD("وحدات ZATCA","وحدة النظام ثم وحدة لكل جهاز كاشير: الشهادات وانتهاؤها وآخر عدّاد",btnGo("ghost","receipt-text","فواتير نقاط البيع","cmPosInvoices")+btnGo("primary","plus","ربط جهاز بالهيئة","cmZatcaWizard"))}${CM.pvbar({cost:0})}${CM.view(normal,CM.empty("shield-check","لا توجد وحدات أجهزة","اربط جهازاً بنقطة بيع أولاً، ثم اربطه بالهيئة من هنا.",btnGo("primary","monitor-smartphone","نقاط البيع والأجهزة","cmRegisters")))}`};

const WZ=[
 ["توليد رمز التفعيل (OTP)","المسؤول","ادخل بوابة «فاتورة» (fatoora.zatca.gov.sa) بحساب المنشأة ← «إدارة الأجهزة والحلول» ← «إنشاء رمز OTP». الرمز من 6 أرقام صالح <b>ساعة واحدة</b> ويُستخدم مرة واحدة.","الرمز يعمل مرة واحدة فقط. إن انتهت الساعة أو استُخدم، ولّد رمزاً جديداً من البوابة وأعد من هذه الخطوة."],
 ["إدخال الرمز","المسؤول","اكتب الرمز هنا (أو في تطبيق الكاشير). لا يُحفظ الرمز بعد استعماله.","إن أخطأت في الكتابة لا يحدث شيء: أعد كتابته. بعد 3 محاولات خاطئة يُقفل الإدخال دقيقة."],
 ["توليد المفتاح وطلب الشهادة (CSR)","الجهاز","الجهاز يولّد <b>المفتاح الخاص داخله ولا يغادره أبداً</b>، ويرسل للسيرفر طلب شهادة (CSR) فيه اسم الوحدة والرقم الضريبي والفرع.","إن لم يستجب الجهاز (غير متصل) تتوقف الخطوة: شغّل التطبيق واتصل بالإنترنت ثم أعد هذه الخطوة. لا تحتاج رمزاً جديداً."],
 ["شهادة الامتثال (Compliance CSID)","السيرفر والهيئة","السيرفر يرسل الـCSR مع الرمز للهيئة فتعيد شهادة مؤقتة للاختبار. هنا يُستهلك الرمز.","الرمز منتهٍ أو مستخدم → ارجع للخطوة 1 وولّد رمزاً جديداً (ولا يكفي إعادة هذه الخطوة)."],
 ["فحوصات الامتثال","الجهاز والهيئة","الجهاز يوقّع عينات (فاتورة مبسطة، إشعار دائن، إشعار مدين) ويرسلها السيرفر للهيئة لفحص التوقيع والبنية.","إن رُفضت عينة تظهر سببها: صحّح الإعداد (مثلاً الرقم الضريبي) وأعد هذه الخطوة فقط؛ المفتاح والشهادة المؤقتة تبقى."],
 ["شهادة الإنتاج (Production CSID)","الهيئة","بعد نجاح الفحوصات تصدر الهيئة شهادة الإنتاج، فتُحفظ مشفّرة، ويبدأ العدّاد (ICV) من 1.","إن فشل الطلب لأي سبب عابر أعد هذه الخطوة؛ الفحوصات السابقة لا تُعاد."]];
const wzDev=()=>getDev(P.wz.dev)||getDev("d6");
const wzReset=(dev)=>{clearTimeout(window.__cmpWT);P.wz=Object.assign(P.wz,{dev:dev||P.wz.dev,gen:0,otp:"",cur:1,st:["cur","wait","wait","wait","wait","wait"],run:0,err:null,done:0});save()};
S.cmZatcaWizard=()=>{
 const w=P.wz,d=wzDev(),renew=!!P.wzRenew;
 const failIdx=w.st.indexOf("fail"),curIdx=w.done?6:failIdx>=0?failIdx:w.cur-1;
 const WSH=["رمز OTP","إدخال الرمز","المفتاح وCSR","شهادة الامتثال","الفحوصات","شهادة الإنتاج"];
 const flow=CM.flow(WZ.map((x,i)=>[WSH[i],w.st[i]==="done"?"تم":w.st[i]==="fail"?"فشل":w.st[i]==="run"?"جارٍ…":x[1]]),w.done?6:curIdx,failIdx>=0);
 const body=i=>{const stt=w.st[i];if(stt!=="cur"&&stt!=="fail"&&stt!=="run")return "";
  if(i===0)return `<div class="cmp-wact"><a class="btn btn-ghost" href="https://fatoora.zatca.gov.sa" target="_blank">${X("external-link")}فتح بوابة فاتورة</a>${w.gen?`<span class="cm-flag ok">${X("check")}الرمز مولَّد — يبقى صالحاً <b class="num" data-cmp-cdp="${w.gen+3600000}">${cdFmt(w.gen+3600000-Date.now()).slice(3)}</b> تقريباً</span>`:""}${cmpB(w.gen?"ghost":"primary","check",w.gen?"ولّدت رمزاً جديداً":"ولّدتُ الرمز من البوابة","wzgen")}${w.gen?cmpB("primary","arrow-left","التالي: إدخال الرمز","wznext:2"):""}</div>`;
  if(i===1)return `<div class="cmp-wact"><div class="fld" style="min-width:200px"><label>رمز OTP (6 أرقام)</label><input data-cmp-wf="otp" value="${w.otp}" maxlength="6" inputmode="numeric" dir="ltr" placeholder="••••••"></div>
   <div class="fld" style="min-width:230px"><label>سيناريو المعاينة <small>(غير موجود في النظام)</small></label><select data-cmp-wf="sim"><option value="ok"${w.sim==="ok"?" selected":""}>ينجح الربط</option><option value="otp"${w.sim==="otp"?" selected":""}>الرمز منتهي (يفشل في الخطوة 4)</option><option value="chk"${w.sim==="chk"?" selected":""}>فشل فحص الامتثال (الخطوة 5)</option></select></div>
   <div class="cmp-padbtn" style="margin:0">${cmpB("primary","play","بدء الربط","wzstart")}</div></div>${w.err==="otpfmt"?`<small class="cmp-err">اكتب 6 أرقام.</small>`:""}`;
  if(w.run&&stt==="run")return `<div class="cmp-wrun"><i></i><span>جارٍ التنفيذ… لا تغلق الصفحة</span></div>`;
  if(stt==="fail")return "";
  return "";};
 const fail=failIdx>=0?`<div class="cm-note bad">${X("octagon-alert")}<div><b>فشلت الخطوة ${failIdx+1}: ${WZ[failIdx][0]}</b><br>${w.err.msg}<br><b>ما الذي يُعاد؟</b> ${w.err.redo}</div></div><div class="cmp-padbtn">${cmpB("primary","rotate-ccw",w.err.btn,"wzredo")}${cmpB("ghost","","إلغاء المعالج","wzreset")}</div>`:"";
 const ok=w.done?`<div class="card cm-empty sm cmp-wok"><span class="cm-empty-i cmp-ei ok">${X("shield-check")}</span><h3>الجهاز جاهز لإصدار الفواتير</h3><p>«${d.n}» حصل على شهادة الإنتاج (${renew?"تجديد":"وحدة جديدة"} ${mono(d.unit||"EGS-POS-0004")}). يبدأ عدّاد الفواتير من 1، وأي فاتورة يصدرها الجهاز تُبلَّغ للهيئة تلقائياً.</p><div class="cm-empty-a">${btnGo("primary","shield-check","وحدات ZATCA","cmZatca")}${btnGo("ghost","monitor-smartphone","نقاط البيع والأجهزة","cmRegisters","reg:"+d.reg)}${cmpB("ghost","rotate-ccw","ربط جهاز آخر","wzreset")}</div></div>`:"";
 const targets=P.devs.filter(x=>x.st!=="revoked"&&vis(x.reg));
 return `${HEAD("ربط جهاز بهيئة الزكاة (ZATCA)",`${renew?"تجديد شهادة":"تسجيل"} وحدة إصدار للجهاز «${d.n}» — ست خطوات، كل خطوة مشروحة وما يحدث إن فشلت`,"",["cmZatca","وحدات ZATCA"])}${CM.pvbar({cost:0,br:0,st:0})}
 ${w.done?"":`<div class="card sec cmp-wtop"><div class="fld"><label>الجهاز المراد ربطه</label><select data-cmp-wf="dev" data-cmp-re ${w.run||w.cur>1||w.gen?"disabled":""}>${targets.map(x=>`<option value="${x.id}"${x.id===d.id?" selected":""}>${x.n} — ${regName(x.reg)}</option>`).join("")}</select></div>${CM.note("هذه أعلى خطوة مخاطرة في التشغيل: الخطأ فيها يوقف إصدار الفواتير الضريبية للجهاز. اقرأ كل خطوة، ولا تغلق الصفحة أثناء التنفيذ.","warn")}</div>`}
 ${flow}${fail}${ok}
 ${w.done?"":`<div class="cmp-wz">${WZ.map((x,i)=>{const s=w.st[i];return `<div class="cmp-ws ${s}"><span class="cmp-wn">${s==="done"?X("check"):s==="fail"?X("x"):`<b class="num">${i+1}</b>`}</span><div><div class="cmp-wh"><b>${x[0]}</b><em>${x[1]}</em>${s==="done"?CM.st("accepted","تمت"):s==="fail"?CM.st("review","فشلت"):s==="run"?CM.st("pairing","جارية"):""}</div><p>${x[2]}</p><div class="cmp-wf">${X("triangle-alert")}<span><b>إن فشلت:</b> ${x[3]}</span></div>${body(i)}</div></div>`}).join("")}</div>`}`};

/* ====================================================================================== */
const runWz=()=>{clearTimeout(window.__cmpWT);const w=P.wz;if(w.done||w.st.includes("fail"))return;
 const i=w.cur-1;if(i<2)return;
 w.st[i]="run";w.run=1;save();CM.re();
 window.__cmpWT=setTimeout(()=>{
  const bad=(i===3&&w.sim==="otp")?{msg:"ردّت الهيئة: «الرمز منتهي الصلاحية أو مستخدم من قبل» (رمز الخطأ: 400 — OTP expired). الرمز يعمل لمدة ساعة ولمرة واحدة.",redo:"ارجع إلى الخطوة 1 وولّد رمزاً جديداً من بوابة فاتورة، ثم أعد الربط. المفتاح الذي ولّده الجهاز يبقى، فلا تُعاد الخطوة 3.",btn:"العودة للخطوة 1 (رمز جديد)",to:1}
   :(i===4&&w.sim==="chk")?{msg:"رفضت الهيئة عينة «فاتورة مبسطة»: توقيع الفاتورة لا يطابق الشهادة المؤقتة (KSA-12). غالباً اسم الوحدة أو الرقم الضريبي في الجهاز لا يطابق ما في الطلب.",redo:"أعد هذه الخطوة (الفحوصات) فقط بعد مراجعة بيانات الجهاز. لا حاجة لرمز جديد: المفتاح وشهادة الامتثال سليمان.",btn:"إعادة الفحوصات (الخطوة 5)",to:5}:null;
  w.run=0;
  if(bad){w.st[i]="fail";w.err=bad;if(bad.to===5)w.sim="ok"}
  else{w.st[i]="done";if(i===5){w.done=1;const d=wzDev();const old=P.units[d.id];P.units[d.id]={cert:"production",exp:"2027-11-20",icv:old&&P.wzRenew?old.icv:0,env:"إنتاج"};d.unit=d.unit||"EGS-POS-0004";P.wzRenew=0}else{w.cur=i+2;w.st[i+1]="cur"}}
  save();CM.re();if(!bad&&!w.done)runWz()},1100)};

/* ---------- الأحداث ---------- */
const setPath=(path,val)=>{const p=path.split(".");let o=P;
 const map={regs:P.regs,cs:P.cs,pay:P.pay};
 if(map[p[0]]){o=map[p[0]].find(x=>x.id===p[1]);for(let i=2;i<p.length-1;i++)o=o[p[i]];o[p[p.length-1]]=val;return}
 for(let i=0;i<p.length-1;i++)o=o[p[i]];o[p[p.length-1]]=val};
const T0=Date.now();
if(!window.__cmPos){window.__cmPos=1;
 const R=()=>CM.re();
 document.addEventListener("click",e=>{
  const cx=e.target.closest("[data-cmp-ctx]");if(cx){const [k,v]=cx.dataset.cmpCtx.split(":");
   if(k==="f"){P.f.chip=v==="sh"?"sh":v;P.f.q="";P.f.dev="all"}else if(k==="sc")P.f.sc=v;else P.ctx[k]=v;if(k==="dev")P.wz.dev=v;if(k==="unit"){P.wz.dev=v;P.wzRenew=1}if(k==="reg"&&cx.dataset.go==="cmDevicePair"){P.pair.state="none";P.pair.reg=v}
   if(k==="dev"&&cx.dataset.go==="cmZatcaWizard"){P.wzRenew=0;wzReset(v)}if(k==="unit"){wzReset(v);P.wzRenew=1}save()}
 },true);
 document.addEventListener("click",e=>{
  const a=e.target.closest("[data-cmp]");if(!a)return;
  const [k,...r]=a.dataset.cmp.split(":");const x=r[0],y=r[1];if(a.disabled)return;if(k==="mbg"&&e.target!==a)return;e.preventDefault();
  switch(k){
   case "reg":P.ctx.reg=x;save();return R();
   case "pm":{const g=getReg(x),i=g.pm.indexOf(y);i>=0?g.pm.splice(i,1):g.pm.push(y);save();return R()}
   case "neg":{const g=getReg(x);g.neg=g.neg?0:1;save();return R()}
   case "newreg":P.modal={t:"newreg",br:"b1"};return R();
   case "linkopen":P.modal={t:"link",role:"cashier"};return R();
   case "mclose":case "mbg":if(k==="mbg"&&e.target!==a)return;P.modal=null;return R();
   case "revoke":P.modal={t:"revoke",dev:x,ck:0};return R();
   case "revokeOk":{const d=getDev(P.modal.dev);d.st="revoked";if(P.units[d.id])P.units[d.id].cert="revoked";P.modal=null;save();R();return CM.toast("أُلغي الجهاز نهائياً وسُحب رمز دخوله")}
   case "newregOk":{const m=P.modal;if(!m.name||!m.pre){m.err="الاسم والبادئة مطلوبان.";return R()}if(P.regs.some(g=>g.pre===m.pre.toUpperCase())){m.err="هذه البادئة مستخدمة في نقطة بيع أخرى.";return R()}const id="r"+(P.regs.length+1);P.regs.push({id,name:m.name,pre:m.pre.toUpperCase(),br:m.br,st:D.stores.find(s=>s[2]===m.br)[0],pl:"التجزئة",box:"صندوق كاشير 1",pm:["cash","mada","card"],tpl:TPLS[0],neg:0,maxDisc:5,maxShort:20});P.ctx.reg=id;P.modal=null;save();R();return CM.toast("أُضيفت نقطة البيع. أضف لها جهازاً")}
   case "pairgo":P.ctx.reg=getDev(x).reg;P.pair={reg:P.ctx.reg,name:getDev(x).n,code:mkCode(),exp:Date.now()+9e5,state:"waiting",dev:x};save();location.href="cmDevicePair.html";return;
   /* ربط جهاز */
   case "paygen":{if(!P.pair.name&&P.pair.state==="none"){CM.toast("اكتب اسم الجهاز أولاً","alert-circle");return}const old=P.pair.dev;let dv=old&&getDev(old)&&getDev(old).st==="pairing"?getDev(old):null;if(!dv){const id="d"+(P.devs.length+10);dv={id,n:P.pair.name,reg:P.pair.reg,ver:"—",plat:"—",seen:null,sync:null,unit:"",st:"pairing",px:0};P.devs.push(dv)}P.pair=Object.assign(P.pair,{code:mkCode(),exp:Date.now()+9e5,state:"waiting",dev:dv.id});save();return R()}
   case "paycancel":{const dv=getDev(P.pair.dev);if(dv&&dv.st==="pairing"&&dv.id!=="d6")P.devs.splice(P.devs.indexOf(dv),1);P.pair={reg:P.pair.reg,name:"",code:"",exp:0,state:"none",dev:""};save();return R()}
   case "paysimok":{const dv=getDev(P.pair.dev);dv.st="online";dv.ver="1.4.2";dv.plat="Android 13";dv.seen=0;dv.sync=0;dv.px=214;P.pair.state="paired";save();return R()}
   case "paysimexp":P.pair.exp=Date.now()-1;P.pair.state="expired";save();return R();
   case "payreset":P.pair={reg:P.pair.reg,name:"",code:"",exp:0,state:"none",dev:""};save();return R();
   /* كاشيرية */
   case "csel":P.csSel=x;P.csPin=0;P.csErr="";save();return R();
   case "cr":{const c=P.cs.find(z=>z.id===x),i=c.regs.indexOf(y);i>=0?c.regs.splice(i,1):c.regs.push(y);save();return R()}
   case "cact":{const c=P.cs.find(z=>z.id===x);c.active=c.active?0:1;save();return R()}
   case "pinopen":P.csPin=1;P.csErr="";return R();
   case "pincancel":P.csPin=0;P.csErr="";return R();
   case "pinsave":{const pa=document.querySelector("[data-cmp-pin=a]").value,pb=document.querySelector("[data-cmp-pin=b]").value;if(!/^\d{4,6}$/.test(pa)){P.csErr="الـPIN من 4 إلى 6 أرقام.";return R()}if(pa!==pb){P.csErr="الرقمان غير متطابقين.";return R()}P.cs.find(z=>z.id===P.csSel).pin="الآن";P.csPin=0;P.csErr="";save();R();return CM.toast("حُفظ الـPIN مشفّراً ولن يُعرض مرة أخرى","lock")}
   case "linkOk":{const m=P.modal,emp=m.emp||"أحمد الدوسري",id="c"+(P.cs.length+1);P.cs.push({id,n:emp,role:m.role||"cashier",regs:[],maxDisc:5,active:1,pin:""});P.csSel=id;P.modal=null;save();R();return CM.toast("رُبط الموظف ككاشير. عيّن له PIN ونقاط البيع")}
   /* ورديات */
   case "sc":P.f.sc=x;save();return R();
   case "shview":P.shop[P.ctx.shift]=1;save();return R();
   case "shhide":P.shop[P.ctx.shift]=0;save();return R();
   case "shapprove":{const s=getShift(x);if(s.over&&!(P.shr[x]||"").trim()){P.shErr=x;R();return CM.toast("اكتب سبب قبول العجز","alert-circle")}P.sh[x]="approved";P.shErr="";save();R();return CM.toast("اعتُمدت الوردية. يمكنك الآن ترحيلها","badge-check")}
   case "shpost":{P.sh[x]="posted";const n=24131+Object.keys(P.shje).length*2;P.shje[x]=["JE-"+n,"JE-"+(n+1)];save();R();return CM.toast(`رُحّلت الوردية: قيد ${P.shje[x][0]} وقيد تكلفة ${P.shje[x][1]}`,"book-check")}
   /* فواتير */
   case "ichip":P.f.chip=x;save();return R();
   case "reprint":CM.toast(`أُرسلت ${x} إلى الطابعة وسُجّلت «إعادة طباعة» في سجل النشاط`,"printer");return;
   case "retry":{const i=getInv(x);if(i.z==="z_rejected"){P.retry=x;save();R();setTimeout(()=>{P.retry=null;R();CM.toast("رفضت الهيئة المحاولة مجدداً: السبب لم يُصحَّح بعد","shield-x")},1200);return}P.retry=x;save();R();setTimeout(()=>{P.retry=null;P.inv[x]=Object.assign(P.inv[x]||{},{z:"reported"});save();R();CM.toast("أُبلّغت الفاتورة للهيئة بنجاح","shield-check")},1400);return}
   case "revok":P.inv[x]=Object.assign(P.inv[x]||{},{ing:"accepted"});save();R();return CM.toast("قُبلت الفاتورة بإجمالي السيرفر وستُبلَّغ للهيئة","check");
   case "revno":P.inv[x]=Object.assign(P.inv[x]||{},{ing:"accepted"});CM.toast("سُجّل رفض نهائي وأُبلغ الكاشير (معاينة)","x");return;
   /* طرق الدفع */
   case "pmv":{const i=P.pay.findIndex(p=>p.id===x),j=i+ +y;if(j<0||j>=P.pay.length)return;const t=P.pay[i];P.pay[i]=P.pay[j];P.pay[j]=t;save();return R()}
   case "pmon":{const p=P.pay.find(z=>z.id===x);p.on=p.on?0:1;save();return R()}
   case "pmadd":{P.pay.push({id:"m"+(P.pay.length+1),n:"طريقة جديدة",kind:"transfer",acc:PACC[1],fee:0,on:1});save();return R()}
   case "pmsave":save();return CM.toast("حُفظت طرق الدفع وستصل الأجهزة عند المزامنة");
   /* عروض */
   case "prnew":P.pr.draft={id:"",n:"",t:"percent",v:10,scope:"all",it:101,x:2,y:1,br:["b1","b2"],from:"2026-11-21",to:"2026-12-21",on:1};P.pr.err="";return R();
   case "predit":P.pr.draft=JSON.parse(JSON.stringify(P.pr.list.find(p=>p.id===x)));P.pr.err="";return R();
   case "prdup":{const c=JSON.parse(JSON.stringify(P.pr.list.find(p=>p.id===x)));c.id="";c.n+=" (نسخة)";P.pr.draft=c;return R()}
   case "prcancel":P.pr.draft=null;return R();
   case "prt":P.pr.draft.t=x;return R();
   case "prb":{const b=P.pr.draft.br,i=b.indexOf(x);i>=0?b.splice(i,1):b.push(x);return R()}
   case "prsave":{const d=P.pr.draft;if(!d.n.trim()){P.pr.err="اسم العرض مطلوب.";return R()}if(!d.br.length){P.pr.err="اختر فرعاً واحداً على الأقل.";return R()}if(d.to<d.from){P.pr.err="تاريخ النهاية قبل البداية.";return R()}P.pr.err="";if(d.id){P.pr.list[P.pr.list.findIndex(p=>p.id===d.id)]=d}else{d.id="p"+(P.pr.list.length+1);P.pr.list.unshift(d)}P.pr.draft=null;save();R();return CM.toast("حُفظ العرض وسيصل الأجهزة عند المزامنة")}
   /* تسويات */
   case "setlsel":P.setlSel=x;save();return R();
   case "setlclose":P.setlSel="";save();return R();
   case "setlpost":{const I=P.setlIn[x]||{},a=SETL.find(z=>z[0]===x);P.setl[x]={rec:I.rec!=null?+I.rec:a[4],fee:I.fee!=null?+I.fee:a[5],je:"JE-"+(24140+Object.keys(P.setl).length)};save();R();return CM.toast("رُحّلت التسوية بقيد "+P.setl[x].je,"book-check")}
   /* معالج ZATCA */
   case "wzgen":P.wz.gen=Date.now();save();return R();
   case "wznext":P.wz.cur=+x;P.wz.st[0]="done";P.wz.st[1]="cur";save();return R();
   case "wzstart":{const w=P.wz;if(!/^\d{6}$/.test(w.otp)){w.err="otpfmt";return R()}w.err=null;w.st[1]="done";w.cur=3;w.st[2]="cur";save();return runWz()}
   case "wzredo":{const w=P.wz,to=w.err.to;w.err=null;w.run=0;if(to===1){w.gen=0;w.otp="";w.sim="ok";w.st=["cur","wait","wait","wait","wait","wait"];w.cur=1}else{w.st[to-1]="cur";w.cur=to}save();R();return to>=3?runWz():0}
   case "wzreset":wzReset();return R();
  }
 });
 document.addEventListener("input",e=>{
  const t=e.target;
  if(t.dataset.cmpIn){let v=t.type==="checkbox"?t.checked:t.value;if("num" in t.dataset)v=+v;setPath(t.dataset.cmpIn,v);save();const el=document.getElementById("cmp-sent");if(el)el.innerHTML=sentence(P.pr.draft);return}
  if(t.dataset.cmpF==="q"){P.f.q=t.value;save()}
  if(t.dataset.cmpMf!=null&&P.modal)P.modal[t.dataset.cmpMf]=t.value;
  if(t.dataset.cmpShr){P.shr[t.dataset.cmpShr]=t.value;save()}
  if(t.dataset.cmpWf){P.wz[t.dataset.cmpWf]=t.value;save()}
  if(t.dataset.cmpPf){P.pair[t.dataset.cmpPf]=t.value;save()}
  if(t.dataset.cmpSin){const I=P.setlIn[P.setlSel]=P.setlIn[P.setlSel]||{};I[t.dataset.cmpSin]=t.type==="checkbox"?t.checked:t.value;save()}
 });
 document.addEventListener("change",e=>{
  const t=e.target;
  if(t.dataset.cmpMck!=null){P.modal.ck=t.checked;return CM.re()}
  if(t.dataset.cmpF){P.f[t.dataset.cmpF]=t.value;save();return CM.re()}
  if(t.dataset.cmpIn&&(t.dataset.cmpRe!=null)){if(t.dataset.cmpIn.endsWith(".br")){const g=getReg(t.dataset.cmpIn.split(".")[1]);const st=D.stores.find(s=>s[2]===t.value);if(st)g.st=st[0]}save();return CM.re()}
  if(t.dataset.cmpPf==="reg"){P.pair.reg=t.value;P.ctx.reg=t.value;save();return CM.re()}
  if(t.dataset.cmpMf&&P.modal)return CM.re();
  if(t.dataset.cmpWf==="dev"){P.wz.dev=t.value;save();return CM.re()}
  if(t.dataset.cmpWf==="sim"){return}
  if(t.dataset.cmpSin){return CM.re()}
 });
 /* مؤقت واحد للعدّادات الحيّة (لا يتراكم مع إعادة الرسم) */
 setInterval(()=>{
  document.querySelectorAll("[data-cmp-cdt]").forEach(el=>{el.textContent=cdFmt(+el.dataset.cmpCdt-Date.now())});
  document.querySelectorAll("[data-cmp-cdp]").forEach(el=>{const ms=+el.dataset.cmpCdp-Date.now();el.textContent=cdFmt(ms).slice(3)});
  document.querySelectorAll("[data-cmp-cdbar]").forEach(el=>{el.style.width=Math.max(0,Math.min(100,(+el.dataset.cmpCdbar-Date.now())/9000))+"%"});
  const wc=document.querySelector("[data-cmp-cdp]");
  if(window.CERP_PAGE==="cmDevicePair"&&P.pair.state==="waiting"&&P.pair.exp<=Date.now()){P.pair.state="expired";save();CM.re()}
 },1000);
}
Object.assign(T,{cmDash:{ar:"لوحة التجارة اليوم",en:"Commerce today"},cmRegisters:{ar:"نقاط البيع والأجهزة",en:"Registers & devices"},cmDevicePair:{ar:"إضافة جهاز",en:"Pair a device"},cmCashiers:{ar:"الكاشيرية",en:"Cashiers"},cmShifts:{ar:"الورديات",en:"Shifts"},cmShiftShow:{ar:"تفاصيل الوردية",en:"Shift details"},cmPosInvoices:{ar:"فواتير نقاط البيع",en:"POS invoices"},cmPosInvoiceShow:{ar:"فاتورة نقطة بيع",en:"POS invoice"},cmPayMethods:{ar:"طرق الدفع",en:"Payment methods"},cmPromos:{ar:"العروض",en:"Promotions"},cmSettle:{ar:"تسويات البطاقات",en:"Card settlements"},cmZatca:{ar:"وحدات ZATCA",en:"ZATCA units"},cmZatcaWizard:{ar:"ربط جهاز بالهيئة",en:"Pair device with ZATCA"}});
Object.keys(S).forEach(k=>{screens[k]=S[k]});
});
