/* ClickERP — تطبيق الكاشير (نموذج تفاعلي عالي الدقة). ملف مستقل: لا يعتمد على cm-core.
   المنطق هنا نموذج للمطورين: الحالة S، الحسابات calc()، الشاشات R.*، الأوامر A.* */
(()=>{"use strict";
const $=(s,r=document)=>r.querySelector(s);
const I=(n,c="")=>`<i data-lucide="${n}" class="pos-i ${c}"></i>`;
const r2=x=>Math.round((x+Number.EPSILON)*100)/100;
const fm=x=>r2(x).toLocaleString("en-US",{minimumFractionDigits:2,maximumFractionDigits:2});
const pad=n=>String(n).padStart(6,"0");
const SAR='<small class="pos-sar">ر.س</small>';
const money=x=>`<span class="num">${fm(x)}</span>${SAR}`;

/* ---------- بيانات العيّنة (نسخة من CM.D في cm-core.js ليتطابق الرقم مع لوحة التحكم) ---------- */
const REG={id:"r2",name:"كاشير 2 — صالة الرياض",prefix:"A2",store:"صالة البيع",maxDisc:5};
const CASHIERS=[{id:"c1",n:"عبدالرحمن السبيعي",role:"كاشير",pin:"1111",col:"#2f7ec7",sup:0},{id:"c3",n:"فيصل القرني",role:"مشرف وردية",pin:"2468",col:"#7ab648",sup:1}];
const SUP=CASHIERS[1];
/* الكمية = رصيد مخزن صالة البيع s2 بالوحدة الأساسية · units: [الوحدة, المعامل, السعر شامل الضريبة, الباركود] */
const ITEMS=[
{id:101,code:"GR-0101",n:"أرز بسمتي 5 كجم",cat:"البقالة",ic:"wheat",col:"#c8862a",units:[["حبة",1,42.5,"6281000101015"],["كرتون",4,165,"6281000101022"]],qty:36},
{id:102,code:"GR-0102",n:"زيت دوار الشمس 1.5 لتر",cat:"البقالة",ic:"droplet",col:"#d9a520",units:[["حبة",1,18.75,"6281000102012"],["كرتون",12,215,"6281000102029"]],qty:22},
{id:103,code:"GR-0103",n:"سكر أبيض 10 كجم",cat:"البقالة",ic:"package",col:"#828d9e",units:[["حبة",1,39,"6281000103019"]],qty:4},
{id:104,code:"BV-0201",n:"مياه شرب 330 مل",cat:"المشروبات",ic:"glass-water",col:"#2f7ec7",units:[["حبة",1,1,"6281000201014"],["كرتون",40,27,"6281000201021"]],qty:-14},
{id:105,code:"DA-0301",n:"حليب طازج كامل الدسم 1 لتر",cat:"الألبان",ic:"milk",col:"#5f6c80",units:[["حبة",1,7.5,"6281000301011"],["كرتون",12,84,"6281000301028"]],qty:46},
{id:106,code:"DT-0401",n:"تمر سكري",cat:"التمور",ic:"cherry",col:"#8a5a2b",units:[["كجم",1,32,"21 + الوزن"]],qty:18.375,w:1},
{id:107,code:"FD-0501",n:"علف شعير",cat:"الأعلاف",ic:"wheat",col:"#7ab648",units:[["كجم",1,1.6,""],["كيس 50 كجم",50,76,"6281000501015"],["طن",1000,1480,""]],qty:1200},
{id:108,code:"FD-0502",n:"علف ذرة مجروشة",cat:"الأعلاف",ic:"wheat",col:"#b39b2e",units:[["كجم",1,1.9,""],["كيس 40 كجم",40,72,"6281000502012"],["طن",1000,1790,""]],qty:0},
{id:109,code:"SV-0901",n:"خدمة توصيل داخل المدينة",cat:"الخدمات",ic:"truck",col:"#16233f",units:[["خدمة",1,25,""]],qty:null},
{id:110,code:"KT-0951",n:"سلة رمضان الأساسية",cat:"السلال والهدايا",ic:"gift",col:"#d4453c",units:[["سلة",1,149,"6281000951013"]],qty:null}];
const CATS=[["البقالة","shopping-basket"],["المشروبات","cup-soda"],["الألبان","milk"],["التمور","cherry"],["الأعلاف","wheat"],["الخدمات","truck"],["السلال والهدايا","gift"]];
const item=id=>ITEMS.find(x=>x.id===+id);
/* عروض تلقائية: تُحسب من الجهاز بلا إنترنت (نسخة من قائمة العروض المتزامنة) */
const PROMO={101:{min:2,pct:10,n:"عرض الأرز"},105:{min:3,pct:8,n:"عرض الحليب الطازج"}};
const CUSTS=[{id:0,n:"عميل نقدي",cash:1},
{id:1,n:"شركة الأفق للتجارة",vat:"300123456700003",limit:20000,used:7350,ph:"0551234567"},
{id:2,n:"مؤسسة النخبة للمقاولات",vat:"",limit:5000,used:4620,ph:"0507654321"},
{id:3,n:"محمد العتيبي",vat:"",limit:2000,used:0,ph:"0533344556"}];
const PAY=[["cash","نقدي","banknote"],["mada","مدى","credit-card"],["card","فيزا / ماستر","credit-card"],["apple","Apple Pay","smartphone"],["transfer","تحويل","arrow-left-right"],["credit","آجل","clock"],["voucher","قسيمة","ticket"]];
const PN=Object.fromEntries(PAY.map(p=>[p[0],p[1]]));
const DENOMS=[500,200,100,50,10,5,1,0.5];
const PAIR_OK="K7M29QXA";
const SCALE_CODE="2100106003750";

/* ---------- الحالة ---------- */
const S={ptab:"items",mode:"web",screen:"pair",online:true,stale:false,printerOff:false,stockMode:"warn",blind:true,newPrices:false,priceApplied:false,
 cashier:null,cart:[],cust:CUSTS[0],seq:153,uid:1,pending:3,lastSync:"10:42",modal:null,msg:null,q:"",cat:"الكل",catalog:null,
 prices:{},held:[],sentimentPrev:false,
 pair:{code:"",state:"",prog:0},login:{sel:null,pin:"",tries:0,err:"",locked:false},
 shift:{open:false,float:500,openedAt:"08:12",sales:{cash:2436.75,mada:3812.4,card:1245,apple:320.5,transfer:0,credit:890,voucher:0},cashIn:200,cashOut:150,refCash:40,inv:41,prev:false},
 floatStr:"500",pay:{m:"cash",lines:[],amt:"",ref:"",b2b:null},last:null,print:"idle",drawer:false,
 ret:{code:"",inv:null,qty:{},err:"",method:"cash",done:null},
 cashio:{type:"in",amt:"",reason:"",err:""},close:{step:1,counts:{},other:{},blind:true},
 sync:{busy:false,rejected:[{no:"A2-000149",dev:96.5,srv:98.25,t:"أمس 17:40"}],update:false,done:null},
 printer:"built",scanner:"kbd",scale:"usb",drawerCfg:"printer",
 pv:{open:false},rest:{table:null,type:"dine",cart:[]},hold:0};
S.held=[
 {id:"h1",t:"11:26",cust:CUSTS[0],lines:[[101,0,2],[104,1,1]]},
 {id:"h2",t:"11:09",cust:CUSTS[1],lines:[[107,1,4],[102,1,1]]},
 {id:"h3",t:"10:51",cust:CUSTS[3],lines:[[105,0,6],[103,0,1],[110,0,1]]}];
S.held.forEach(h=>h.lines=h.lines.map(([id,u,q])=>mkLine(id,u,q)));

function mkLine(id,u,q,w){return{uid:S.uid++,id,u,q,w:!!w,disc:0,appr:null,low:false}}
const up=(it,u)=>(S.prices[it.id]&&S.prices[it.id][u]!=null)?S.prices[it.id][u]:it.units[u][2];
function lineCalc(l){const it=item(l.id),gross=r2(l.q*up(it,l.u));const pr=PROMO[l.id];
 const promo=(pr&&l.u===0&&!l.w&&l.q>=pr.min)?r2(gross*pr.pct/100):0;
 const man=r2((gross-promo)*l.disc/100);return{gross,promo,man,total:r2(gross-promo-man),pr:promo?pr:null}}
function calc(cart=S.cart){let g=0,p=0,m=0;cart.forEach(l=>{const c=lineCalc(l);g+=c.gross;p+=c.promo;m+=c.man});
 const disc=r2(p+m),total=r2(g-disc),vat=r2(total*15/115);return{gross:r2(g),promo:r2(p),man:r2(m),disc,total,vat,pre:r2(total-vat),n:cart.length}}
const paidSum=()=>r2(S.pay.lines.reduce((a,l)=>a+l.amt,0));
const baseQty=(id)=>S.cart.filter(l=>l.id===id).reduce((a,l)=>a+l.q*item(id).units[l.u][1],0);
const needsB2B=()=>!!S.cust.vat;

/* ---------- مكوّنات مشتركة ---------- */
const R={};
const numpad=(a,extra="")=>`<div class="pos-np" dir="ltr">${["7","8","9","4","5","6","1","2","3",".","0","del"].map(k=>`<button class="pos-key" data-a="${a}" data-k="${k}">${k==="del"?I("delete"):k}</button>`).join("")}${extra}</div>`;
const pinpad=(a)=>`<div class="pos-np pos-pinpad" dir="ltr">${["1","2","3","4","5","6","7","8","9","clr","0","del"].map(k=>`<button class="pos-key" data-a="${a}" data-k="${k}">${k==="del"?I("delete"):k==="clr"?"مسح":k}</button>`).join("")}</div>`;
const dots=(n,err)=>`<div class="pos-dots ${err?"err":""}">${[0,1,2,3].map(i=>`<i class="${i<n?"on":""}"></i>`).join("")}</div>`;
const bar=(title,back="sale",right="")=>`<header class="pos-bar"><button class="pos-btn pos-ghost" data-a="go" data-s="${back}">${I("chevron-right")}<span>رجوع</span></button><h1>${title}</h1><div class="pos-grow"></div>${right}<span class="pos-chip pos-cashier">${I("user-round")}${S.cashier?S.cashier.n:"—"}</span></header>`;
const alertBox=(t,x,ic)=>`<div class="pos-alert pos-${t}">${I(ic||(t==="err"?"circle-alert":t==="warn"?"triangle-alert":t==="ok"?"circle-check":"info"))}<div>${x}</div></div>`;
const avatar=(c,sz="")=>`<span class="pos-av ${sz}" style="--c:${c.col}">${c.n.split(" ")[0].slice(0,1)}${(c.n.split(" ")[1]||"").slice(0,0)}</span>`;
const brand=()=>`<div class="pos-brand"><span class="pos-logo">${I("store")}</span><div><b>ClickERP كاشير</b><small>${S.screen==="pair"?"جهاز غير مرتبط":REG.name}</small></div></div>`;

/* ---------- ربط الجهاز ---------- */
const KEYS="1234567890ABCDEFGHJKLMNPQRSTUVWXYZ".split("");
R.pair=()=>{const p=S.pair,st=p.state;
 let body="";
 if(st==="revoked")body=`<div class="pos-card pos-center pos-narrow">${I("shield-off","pos-big err")}<h2>تم إلغاء ربط هذا الجهاز</h2><p>ألغى المسؤول ربط الجهاز من لوحة التحكم، ولا يمكن البيع عليه. سيحتفظ الجهاز بالفواتير غير المرفوعة (3) حتى يُعاد ربطه.</p><button class="pos-btn pos-navy pos-lg" data-a="pairState" data-v="">ربط جهاز جديد</button></div>`;
 else if(st==="download")body=`<div class="pos-card pos-narrow"><h2>تجهيز الجهاز لأول مرة</h2><p class="pos-muted">يتم تحميل بيانات ${REG.name} على الجهاز لتعمل المبيعات بدون إنترنت.</p>
  <div class="pos-prog"><i style="width:${p.prog}%"></i></div><div class="pos-prog-n num">${Math.round(p.prog)}%</div>
  ${[["المنتجات والوحدات والباركود",1248,20],["الأسعار وقوائم العروض",342,45],["العملاء وحدود الائتمان",96,60],["صور المنتجات",1190,90],["الكاشيرية والصلاحيات",5,100]].map(([n,c,t])=>`<div class="pos-dl ${p.prog>=t?"done":""}">${p.prog>=t?I("circle-check"):I("loader")}<span>${n}</span><b class="num">${c}</b></div>`).join("")}
  ${p.prog>=100?`<button class="pos-btn pos-green pos-lg" data-a="go" data-s="login">${I("check")}جاهز — متابعة للدخول</button>`:`<small class="pos-muted">لا تغلق التطبيق أثناء التحميل. يمكن استئنافه لو انقطع الاتصال.</small>`}</div>`;
 else if(!S.online)body=`<div class="pos-card pos-center pos-narrow">${I("wifi-off","pos-big warn")}<h2>الربط يحتاج اتصالاً بالإنترنت</h2><p>الربط الأول يتم مرة واحدة فقط ويحتاج اتصالاً. بعد الربط يعمل الجهاز بدون إنترنت. تأكد من شبكة Wi-Fi ثم أعد المحاولة.</p><button class="pos-btn pos-navy pos-lg" data-a="toggleNet">${I("refresh-cw")}إعادة المحاولة</button></div>`;
 else{const msg=st==="expired"?alertBox("err","انتهت صلاحية الكود (يصلح 15 دقيقة فقط). ولّد كودًا جديدًا من لوحة التحكم ← نقاط البيع ← الأجهزة."):st==="wrong"?alertBox("err","الكود غير صحيح. تأكد من الأحرف المشابهة (0 و O) وأعد الإدخال."):"";
  body=`<div class="pos-pair"><div class="pos-card pos-qr"><h3>${I("scan-line")}امسح رمز QR</h3><div class="pos-cam"><i class="pos-scanline"></i><span></span><span></span><span></span><span></span></div><p class="pos-muted">من لوحة التحكم ← نقاط البيع ← الأجهزة ← «ربط جهاز جديد»</p></div>
  <div class="pos-card pos-code"><h3>${I("keyboard")}أو أدخل الكود (8 خانات)</h3>
  <div class="pos-cells" dir="ltr">${[0,1,2,3,4,5,6,7].map(i=>`<b class="${i===p.code.length?"cur":""}">${p.code[i]||""}</b>`).join("")}</div>${msg}
  <div class="pos-kb" dir="ltr">${KEYS.map(k=>`<button class="pos-key" data-a="pairKey" data-k="${k}">${k}</button>`).join("")}<button class="pos-key" data-a="pairKey" data-k="del">${I("delete")}</button></div>
  <button class="pos-btn pos-green pos-lg" data-a="pairGo" ${p.code.length<8?"disabled":""}>ربط الجهاز</button><small class="pos-muted">كود التجربة: ${PAIR_OK}</small></div></div>`}
 return`<div class="pos-plain">${brand()}<div class="pos-plainbody">${body}${installCard()}</div></div>`};

/* ---------- الدخول ---------- */
R.login=()=>{const L=S.login,sel=CASHIERS.find(c=>c.id===L.sel);
 return`<div class="pos-plain">${brand()}<div class="pos-plainbody"><div class="pos-login">
 <div class="pos-card"><h2>من يعمل على الكاشير؟</h2><p class="pos-muted">الكاشيرية المسموح لهم على «${REG.name}»</p>
 <div class="pos-people">${CASHIERS.map(c=>`<button class="pos-person ${L.sel===c.id?"on":""}" data-a="pick" data-id="${c.id}">${avatar(c,"lg")}<b>${c.n}</b><small>${c.role}</small></button>`).join("")}</div>
 <div class="pos-offnote">${I(S.online?"wifi":"wifi-off")}الدخول يعمل بدون إنترنت</div></div>
 <div class="pos-card pos-pincard">${sel?`<div class="pos-pinhd">${avatar(sel)}<div><b>${sel.n}</b><small>أدخل الرمز السري (4 أرقام)</small></div></div>${dots(L.pin.length,L.err)}
  <div class="pos-pinmsg ${L.locked?"lock":""}">${L.locked?`${I("lock")}تم قفل الدخول بعد 5 محاولات خاطئة. ينتظر 5 دقائق أو يفتحه المشرف.`:L.err||"&nbsp;"}</div>
  ${L.locked?`<button class="pos-btn pos-navy pos-lg" data-a="unlockSup">${I("key-round")}فتح بواسطة المشرف</button>`:pinpad("pinKey")}`:`<div class="pos-center pos-empty">${I("user-round-check","pos-big")}<p>اختر اسمك لإدخال الرمز</p></div>`}</div>
 </div></div></div>`};

/* ---------- فتح وردية ---------- */
R.shift=()=>{const sh=S.shift;
 const prev=sh.prev?`<div class="pos-card pos-prevshift">${alertBox("warn","<b>توجد وردية سابقة ما زالت مفتوحة على هذا الجهاز</b><br>فتحتها هيفاء الشمري أمس 08:12 — 14 فاتورة، لم تُقفل. لا يمكن فتح وردية جديدة قبل معالجتها.","triangle-alert")}
 <div class="pos-row2"><button class="pos-btn pos-navy pos-lg" data-a="contPrev">${I("play")}متابعة الوردية السابقة</button><button class="pos-btn pos-ghost pos-lg" data-a="closePrev">${I("lock-keyhole")}إقفالها أولاً</button></div></div>`:"";
 return`<div class="pos-plain">${brand()}<div class="pos-plainbody"><div class="pos-card pos-shiftbox">
 <div class="pos-pinhd">${avatar(S.cashier||CASHIERS[0])}<div><b>${(S.cashier||CASHIERS[0]).n}</b><small>${REG.name} · ${new Date().toLocaleDateString("ar-SA",{weekday:"long"})}</small></div></div>
 ${sh.prev?prev:`<h2>فتح وردية جديدة</h2><p class="pos-muted">أدخل العهدة الافتتاحية (النقد الموجود في الدرج الآن)</p>
 <div class="pos-split"><div><div class="pos-bigfield"><small>العهدة الافتتاحية</small><b class="num">${S.floatStr||"0"}</b>${SAR}</div>
 <div class="pos-quick">${[200,300,500,1000].map(v=>`<button class="pos-btn pos-ghost" data-a="floatSet" data-v="${v}"><span class="num">${v}</span></button>`).join("")}</div>
 <button class="pos-btn pos-green pos-xl" data-a="openShift" ${(+S.floatStr)<0?"disabled":""}>${I("play")}فتح الوردية</button></div>${numpad("floatKey")}</div>`}
 </div></div></div>`};

/* ---------- الغلاف: بنر 20 ساعة + شريط المزامنة الدائم ---------- */
function syncBar(){const off=!S.online,st=S.stale;
 return`<footer class="pos-sync ${off?"off":""} ${st?"stale":""}" data-a="go" data-s="sync">
 <span class="pos-sdot"></span><b>${off?(WEB()?"غير متصل — البيع متوقف حتى يعود الاتصال":"غير متصل — البيع مستمر"):"متصل"}</b>
 ${WEB()?`<span class="pos-sep"></span>${I("globe")}<span>تطبيق ويب — كل فاتورة تُبلّغ للهيئة فوراً</span>`:`<span class="pos-sep"></span>${I("upload-cloud")}<span>غير مرفوع: <b class="num">${S.pending}</b> فاتورة</span>`}
 <span class="pos-sep"></span>${I("refresh-cw")}<span>آخر مزامنة <b class="num">${S.stale?"أمس 14:05":S.lastSync}</b></span>
 ${S.sync.rejected.length?`<span class="pos-sep"></span><span class="pos-tag err">${I("octagon-alert")}مرفوضة: ${S.sync.rejected.length}</span>`:""}
 ${S.sync.update?`<span class="pos-tag amber">${I("download")}تحديث مطلوب</span>`:""}
 <span class="pos-grow"></span><span class="pos-ver num">${REG.prefix} · v1.0.3</span><span class="pos-pvgap"></span></footer>`}
function warn20(){return !WEB()&&S.stale&&!["pair","login","shift"].includes(S.screen)?`<div class="pos-warn20">${I("triangle-alert")}<div><b>مرّت 20 ساعة دون رفع الفواتير إلى الهيئة.</b> يجب إبلاغ الهيئة بكل فاتورة خلال 24 ساعة من إصدارها. وصّل الجهاز بالإنترنت وزامن الآن.</div><button class="pos-btn pos-white" data-a="syncNow">${I("refresh-cw")}مزامنة الآن</button></div>`:""}

/* v1.80 — نسخة الويب أولاً: الكاشير يشتغل من المتصفح ويتثبّت على الجهاز كتطبيق (PWA). الفاتورة تصدر من السيرفر وتُبلّغ للهيئة بوحدة النظام،
   فالبيع يحتاج إنترنت؛ والسلة محفوظة على الجهاز لو الاتصال انقطع. تطبيق أندرويد (المرحلة 3) هو اللي يبيع بدون إنترنت ويوقّع من الجهاز. */
const WEB=()=>S.mode==="web";
let INSTALL=null;window.addEventListener("beforeinstallprompt",e=>{e.preventDefault();INSTALL=e;try{render()}catch(_){}});
const STANDALONE=()=>matchMedia("(display-mode: standalone)").matches||navigator.standalone===true;
const IOS=/iphone|ipad|ipod/i.test(navigator.userAgent);
function installCard(){if(!WEB()||STANDALONE())return "";return `<div class="pos-install">${I("download")}<div><b>ثبّت الكاشير على هذا الجهاز</b><small>${IOS?"من Safari: زر المشاركة ← «إضافة إلى الشاشة الرئيسية».":"يفتح بملء الشاشة من أيقونة على الشاشة الرئيسية، من غير شريط المتصفح."}</small></div>${IOS?"":`<button class="pos-btn pos-navy" data-a="install">${I("monitor-down")}تثبيت</button>`}</div>`}
function webOffBox(){return WEB()&&!S.online?`<div class="pos-weboff">${I("wifi-off")}<div><b>انقطع الإنترنت — نسخة الويب لا تصدر فواتير بدون اتصال.</b><small>السلة محفوظة على الجهاز، وتقدر تكمّل الدفع أول ما يرجع الاتصال. تطبيق أندرويد (قريباً) يبيع بدون إنترنت.</small></div></div>`:""}
/* ---------- البيع ---------- */
function lineHTML(l){const it=item(l.id),c=lineCalc(l),u=it.units[l.u];
 const unit=it.units.length>1?`<button class="pos-unit" data-a="unit" data-u="${l.uid}">${u[0]}${I("repeat-2")}</button>`:`<span class="pos-unit fixed">${u[0]}</span>`;
 const qty=l.w?`<button class="pos-wt" data-a="reweigh" data-u="${l.uid}">${I("scale")}<b class="num">${l.q.toFixed(3)}</b> كجم</button>`
 :`<div class="pos-step"><button data-a="qm" data-u="${l.uid}" aria-label="إنقاص">${I("minus")}</button><button class="pos-qv num" data-a="qnum" data-u="${l.uid}">${l.q}</button><button data-a="qp" data-u="${l.uid}" aria-label="زيادة">${I("plus")}</button></div>`;
 const disc=l.disc?`<button class="pos-dbtn on" data-a="disc" data-u="${l.uid}">${I("percent")}<b class="num">${l.disc}%</b></button>`:`<button class="pos-dbtn" data-a="disc" data-u="${l.uid}">${I("percent")}خصم</button>`;
 return`<div class="pos-line ${S.flash===l.uid?"flash":""}" id="ln${l.uid}"><div class="pos-l1"><b>${it.n}</b><span class="pos-lt">${money(c.total)}</span><button class="pos-del" data-a="del" data-u="${l.uid}" aria-label="حذف">${I("trash-2")}</button></div>
 <div class="pos-l2">${unit}${qty}<span class="pos-up">× <span class="num">${fm(up(it,l.u))}</span></span><span class="pos-grow"></span>${disc}</div>
 ${c.pr?`<div class="pos-badge promo">${I("tag")}<span>${c.pr.n} — خصم ${c.pr.pct}%</span><b class="num">−${fm(c.promo)}</b></div>`:""}
 ${l.appr?`<div class="pos-badge appr">${I("shield-check")}<span>خصم ${l.disc}% بموافقة المشرف ${l.appr}</span><b class="num">−${fm(c.man)}</b></div>`:""}
 ${l.low?`<div class="pos-badge low">${I("triangle-alert")}<span>الرصيد أقل من الكمية (المتاح ${fmq(it.qty/it.units[l.u][1])} ${u[0]}) — يستمر البيع</span></div>`:""}</div>`}
const fmq=x=>(Math.round(x*1000)/1000).toString();
function searchRes(){const q=S.q.trim();if(!q)return"";
 const rs=ITEMS.filter(i=>i.n.includes(q)||i.code.toLowerCase().includes(q.toLowerCase())||i.units.some(u=>u[3]&&u[3].includes(q))).slice(0,7);
 return`<div class="pos-sres">${rs.length?rs.map(i=>`<button class="pos-sr" data-a="add" data-id="${i.id}" data-close="1">${tile(i,"sm")}<div><b>${i.n}</b><small>${i.code} · ${i.qty==null?"خدمة":"الرصيد "+fmq(i.qty)}</small></div><span class="pos-price">${money(up(i,0))}</span></button>`).join(""):`<div class="pos-nores">${I("search-x")}<b>لا نتائج لـ «${q}»</b><small>جرّب جزءًا من الاسم أو الكود</small></div>`}</div>`}
const tile=(i,sz="")=>`<span class="pos-tile ${sz}" style="--c:${i.col}">${I(i.ic)}</span>`;
function itemCard(i){const out=i.qty!=null&&i.qty<=0,pr=PROMO[i.id];
 return`<button class="pos-item ${out?"out":""}" data-a="tap" data-id="${i.id}">${tile(i)}<b>${i.n}</b><div class="pos-ip"><span class="pos-price">${money(up(i,0))}</span>${i.w?`<small>/ كجم</small>`:""}</div>${out?`<em class="pos-ib red">${i.qty<0?"رصيد سالب":"نفد"}</em>`:pr?`<em class="pos-ib green">عرض</em>`:i.w?`<em class="pos-ib navy">${I("scale")}ميزان</em>`:""}</button>`}
R.sale=()=>{const c=calc(),empty=!S.cart.length;
 const cats=[["الكل","layout-grid"],...CATS];
 const list=S.cat==="الكل"?ITEMS:ITEMS.filter(i=>i.cat===S.cat);
 return`<div class="pos-sale">
 <header class="pos-top">
  <div class="pos-who">${avatar(S.cashier||CASHIERS[0])}<div><b>${(S.cashier||CASHIERS[0]).n}</b><small>${REG.name}</small></div></div>
  <label class="pos-search">${I("search")}<input id="pos-q" placeholder="ابحث بالاسم أو الكود…" value="${S.q.replace(/"/g,"&quot;")}" autocomplete="off" data-model="q"><button class="pos-x" data-a="qclear" ${S.q?"":"hidden"}>${I("x")}</button></label>
  <button class="pos-btn pos-navy pos-sq" data-a="scanbtn" aria-label="باركود">${I("scan-barcode")}<span>باركود</span></button>
  <button class="pos-cust ${S.cust.cash?"":"on"}" data-a="custm">${I(S.cust.cash?"user-round":"building-2")}<div><small>العميل</small><b>${S.cust.n}</b></div>${S.cust.vat?`<span class="pos-tag green">رقم ضريبي</span>`:""}</button>
  <button class="pos-btn pos-ghost pos-sq" data-a="menu" aria-label="القائمة">${I("menu")}${S.held.length?`<i class="pos-count">${S.held.length}</i>`:""}</button>
 </header>
 <nav class="pos-ptabs" aria-label="عرض"><button class="${S.ptab!=="cart"?"on":""}" data-a="ptab" data-v="items">${I("layout-grid")}المنتجات</button><button class="${S.ptab==="cart"?"on":""}" data-a="ptab" data-v="cart">${I("receipt")}الفاتورة <b class="num">${c.n}</b>${c.n?`<span class="num">${fm(c.total)}</span>`:""}</button></nav>
 <div class="pos-main" data-ptab="${S.ptab==="cart"?"cart":"items"}">
  <section class="pos-catalog"><div id="pos-msg" class="pos-msg"></div><div id="pos-sresw">${searchRes()}</div>
   <nav class="pos-cats">${cats.map(([n,ic])=>`<button class="pos-cat ${S.cat===n?"on":""}" data-a="cat" data-c="${n}"><span>${I(ic)}</span>${n}</button>`).join("")}</nav>
   <div class="pos-grid">${list.map(itemCard).join("")}</div></section>
  <aside class="pos-cart">
   <div class="pos-cart-h"><h2>الفاتورة <small class="num">${REG.prefix}-${pad(S.seq)}</small></h2><span class="pos-chip">${c.n} منتجات</span><div class="pos-grow"></div>${empty?"":`<button class="pos-btn pos-text" data-a="clearcart">${I("trash")}إفراغ</button>`}</div>
   ${S.newPrices&&!S.priceApplied?`<div class="pos-note amber">${I("tag")}<span>وصلت أسعار جديدة بالمزامنة (3 منتجات) — تسري على <b>الفاتورة التالية</b>، لا على هذه السلة.</span></div>`:""}
   <div class="pos-lines" id="pos-lines">${empty?`<div class="pos-cart-empty">${I("scan-barcode","pos-big")}<b>السلة فارغة</b><p>امسح باركود المنتج أو المس منتجًا من القائمة لإضافته</p></div>`:S.cart.map(lineHTML).join("")}</div>
   <div class="pos-foot">${webOffBox()}
    <div class="pos-sums"><div><span>المجموع</span><b class="num">${fm(c.gross)}</b></div><div class="${c.disc?"disc":""}"><span>الخصم</span><b class="num">${c.disc?"−":""}${fm(c.disc)}</b></div><div><span>ضريبة 15% <i>(مشمولة)</i></span><b class="num">${fm(c.vat)}</b></div></div>
    <div class="pos-grand"><span>الإجمالي</span><strong>${money(c.total)}</strong></div>
    <div class="pos-acts"><button class="pos-btn pos-ghost pos-hold" data-a="hold" ${empty?"disabled":""}>${I("pause")}<span>تعليق</span></button><button class="pos-btn pos-green pos-paybtn" data-a="topay" ${empty||(WEB()&&!S.online)?"disabled":""}><span>${WEB()&&!S.online?"بانتظار الاتصال":"الدفع"}</span>${I("chevron-left")}</button></div>
   </div></aside></div></div>`};

/* ---------- الدفع ---------- */
function payPanel(m,rem){const P=S.pay,cu=S.cust,amtShow=P.amt!==""?P.amt:fm(rem).replace(/,/g,"");
 const amtBox=`<div class="pos-bigfield sm"><small>${m==="cash"?"المبلغ المستلم":"مبلغ هذه الدفعة"}</small><b class="num">${m==="cash"?(P.amt||"—"):amtShow}</b>${SAR}</div>`;
 if(m==="cash")return`<div class="pos-pp"><div>${amtBox}<div class="pos-quick pos-q5"><button class="pos-btn pos-navy" data-a="quick" data-v="exact" ${rem<=0?"disabled":""}>المبلغ بالضبط<small class="num">${fm(rem)}</small></button>${[50,100,200,500].map(v=>`<button class="pos-btn pos-ghost" data-a="quick" data-v="${v}"><span class="num">${v}</span></button>`).join("")}</div><button class="pos-btn pos-navy pos-lg" data-a="addpay" ${rem<=0||!P.amt?"disabled":""}>${I("plus")}إضافة الدفعة</button></div>${numpad("payKey")}</div>`;
 if(m==="credit"){if(cu.cash)return`<div class="pos-pp one"><div>${alertBox("warn","<b>الدفع الآجل يحتاج عميلاً محدداً</b><br>العميل الحالي «عميل نقدي». اختر عميلاً له حد ائتمان.","user-round-x")}<button class="pos-btn pos-navy pos-lg" data-a="custm">${I("user-round-search")}اختيار عميل</button></div></div>`;
  const left=cu.limit-cu.used,afford=Math.min(rem,left);
  return`<div class="pos-pp"><div><div class="pos-credit"><div><small>العميل</small><b>${cu.n}</b></div><div><small>حد الائتمان</small><b class="num">${fm(cu.limit)}</b></div><div><small>المستخدم</small><b class="num">${fm(cu.used)}</b></div><div class="${left<rem?"low":""}"><small>المتبقي من الحد</small><b class="num">${fm(left)}</b></div></div>
  ${left<rem?alertBox("err",`المبلغ المطلوب (${fm(rem)}) أكبر من المتبقي من الحد (${fm(left)}). اقسم الدفع أو اطلب موافقة المدير.`):""}${amtBox}<button class="pos-btn pos-navy pos-lg" data-a="addpay" ${rem<=0||afford<=0?"disabled":""}>${I("plus")}تسجيل على حساب العميل</button></div>${numpad("payKey")}</div>`}
 const ph=m==="transfer"?"رقم الحوالة (اختياري)":m==="voucher"?"رقم القسيمة":"رقم العملية (اختياري)";
 return`<div class="pos-pp"><div>${["mada","card","apple"].includes(m)?`<div class="pos-note">${I("info")}<span>أدخل المبلغ على جهاز الدفع المنفصل، ثم أكّد هنا بعد نجاح العملية.</span></div>`:""}${amtBox}
 <label class="pos-field"><small>${ph}</small><input value="${P.ref}" data-model="pay.ref" placeholder="${m==="voucher"?"VCH-":"مثال: 482913"}"></label>
 <button class="pos-btn pos-navy pos-lg" data-a="addpay" ${rem<=0?"disabled":""}>${I("check")}${m==="voucher"?"استخدام القسيمة":"تأكيد نجاح العملية"}</button></div>${numpad("payKey")}</div>`}
R.pay=()=>{const c=calc(),P=S.pay,paid=paidSum(),rem=r2(Math.max(0,c.total-paid)),chg=r2(Math.max(0,paid-c.total)),cu=S.cust;
 const b2b=needsB2B(),b2bOk=!b2b||S.online||P.b2b;
 const can=P.lines.length&&rem<=0&&b2bOk;
 const b2bBox=!b2b?"":S.online?alertBox("ok","<b>فاتورة ضريبية B2B</b> — سيُرسل الجهاز طلب اعتماد فوري للهيئة عند الإتمام قبل التسليم.","file-check"):
 `<div class="pos-b2b"><div class="pos-b2bh">${I("wifi-off")}<b>العميل برقم ضريبي والجهاز غير متصل — الاعتماد الفوري غير ممكن. اختر:</b></div><div class="pos-row2">
 <button class="pos-opt ${P.b2b==="pending"?"on":""}" data-a="b2b" data-v="pending">${I("clock-arrow-up")}<div><b>حفظ كفاتورة معلّقة وطباعتها لاحقاً</b><small>تُعتمد عند عودة الاتصال، ويستلم العميل نسخة ضريبية بعدها</small></div></button>
 <button class="pos-opt ${P.b2b==="simplified"?"on":""}" data-a="b2b" data-v="simplified">${I("receipt")}<div><b>إصدار فاتورة مبسطة الآن</b><small>تُسلَّم فوراً، وتُصدر الضريبية للعميل بإشعار لاحق</small></div></button></div></div>`;
 return`<div class="pos-payscr">
 <section class="pos-paysum"><button class="pos-btn pos-ghost" data-a="go" data-s="sale">${I("chevron-right")}رجوع للسلة</button>
  <div class="pos-due"><small>المبلغ المستحق</small><strong>${money(c.total)}</strong></div>
  <div class="pos-brk"><div><span>قبل الضريبة</span><b class="num">${fm(c.pre)}</b></div><div><span>ضريبة 15%</span><b class="num">${fm(c.vat)}</b></div><div><span>الخصم</span><b class="num">${c.disc?"−":""}${fm(c.disc)}</b></div></div>
  <button class="pos-cust wide ${cu.cash?"":"on"}" data-a="custm">${I(cu.cash?"user-round":"building-2")}<div><small>العميل</small><b>${cu.n}</b>${cu.vat?`<small class="num">الرقم الضريبي ${cu.vat}</small>`:""}</div><span class="pos-grow"></span><span class="pos-link">تغيير</span></button>
  <div class="pos-plist">${P.lines.length?P.lines.map((l,i)=>`<div class="pos-pl">${I((PAY.find(p=>p[0]===l.m)||[])[2]||"banknote")}<div><b>${PN[l.m]}</b>${l.ref?`<small class="num">${l.ref}</small>`:""}</div><span class="pos-grow"></span><b class="num">${fm(l.amt)}</b><button data-a="rmpay" data-i="${i}" aria-label="حذف">${I("x")}</button></div>`).join(""):`<div class="pos-muted pos-pl0">لم تُسجَّل دفعات بعد</div>`}</div>
  <div class="pos-remain ${rem>0?"":"ok"}">${rem>0?`<span>المتبقي</span><b>${money(rem)}</b>`:chg>0?`<span>الباقي للعميل</span><b class="chg">${money(chg)}</b>`:`<span>المبلغ مغطّى</span><b>${I("circle-check")}</b>`}</div></section>
 <section class="pos-paymain"><div class="pos-methods">${PAY.map(([id,n,ic])=>`<button class="pos-method ${P.m===id?"on":""}" data-a="method" data-m="${id}">${I(ic)}<span>${n}</span></button>`).join("")}</div>
  <div class="pos-panel">${payPanel(P.m,rem)}</div>${b2bBox}
  <div class="pos-payfoot"><button class="pos-btn pos-green pos-xl" data-a="complete" ${can?"":"disabled"}>${I("check-check")}إتمام ${b2b&&!S.online&&P.b2b==="pending"?"وحفظ معلّقة":""}</button></div></section></div>`};

/* ---------- الإيصال (حراري 80 مم) ---------- */
function hashN(s){let h=0;for(const c of s)h=(h*31+c.charCodeAt(0))>>>0;return h||1}
function qrSVG(seed){let h=hashN(seed);const rnd=()=>{h^=h<<13;h>>>=0;h^=h>>>17;h^=h<<5;h>>>=0;return h/4294967296},N=29;let d="";
 const fin=(x,y)=>x<8&&y<8||x>N-9&&y<8||x<8&&y>N-9;
 for(let y=0;y<N;y++)for(let x=0;x<N;x++){let on;if(fin(x,y)){const lx=x>N-9?x-(N-7):x,ly=y>N-9?y-(N-7):y;on=lx>=0&&lx<7&&ly>=0&&ly<7&&(lx===0||lx===6||ly===0||ly===6||(lx>=2&&lx<=4&&ly>=2&&ly<=4))}else on=rnd()>.52;if(on)d+=`M${x} ${y}h1v1h-1z`}
 return`<svg viewBox="0 0 ${N} ${N}" shape-rendering="crispEdges"><path d="${d}" fill="#000"/></svg>`}
function barSVG(s){let x=0,r="";for(const ch of "S"+s+"E"){const c=ch.charCodeAt(0);for(let k=0;k<6;k++){const w=1+((c>>k)&1)+(k%3===0?1:0);if(k%2===0)r+=`<rect x="${x}" y="0" width="${w}" height="40"/>`;x+=w}}return`<svg viewBox="0 0 ${x} 40" preserveAspectRatio="none">${r}</svg>`}
function receiptHTML(v){const c=v.c,b2b=v.mode!=="simp";
 return`<div class="pos-paper"><div class="pos-pc"><b class="pos-co">شركة الريادة للخدمات</b><div>فرع الرياض — صالة البيع</div><div class="num">الرقم الضريبي 310123456700003</div><div class="num">س.ت 1010456789 · هاتف 920001234</div></div>
 <div class="pos-ptitle">${v.mode==="pending"?"إيصال مؤقت — فاتورة ضريبية بانتظار الاعتماد":b2b?"فاتورة ضريبية":"فاتورة ضريبية مبسطة"}<small>${v.mode==="pending"?"Provisional receipt":b2b?"Tax Invoice":"Simplified Tax Invoice"}</small></div>
 <div class="pos-pm"><span>رقم الفاتورة</span><b class="num">${v.no}</b><span>التاريخ</span><b class="num">${v.t}</b><span>الكاشير</span><b>${v.cashier}</b><span>الجهاز</span><b>${REG.prefix} · ${REG.name}</b>${b2b?`<span>العميل</span><b>${v.cust.n}</b><span>الرقم الضريبي</span><b class="num">${v.cust.vat}</b>`:""}</div>
 <table class="pos-pt"><thead><tr><th>المنتج</th><th>الكمية</th><th>السعر</th><th>الإجمالي</th></tr></thead><tbody>${v.lines.map(l=>`<tr><td>${l.n}${l.promo?`<small>${l.promo}</small>`:""}${l.disc?`<small>خصم ${l.disc}%</small>`:""}</td><td class="num">${l.w?l.q.toFixed(3)+" كجم":l.q+" "+l.unit}</td><td class="num">${fm(l.price)}</td><td class="num">${fm(l.total)}</td></tr>`).join("")}</tbody></table>
 <div class="pos-ptot"><div><span>الإجمالي قبل الضريبة</span><b class="num">${fm(c.pre)}</b></div><div><span>الخصم</span><b class="num">${fm(c.disc)}</b></div><div><span>ضريبة القيمة المضافة 15%</span><b class="num">${fm(c.vat)}</b></div><div class="g"><span>الإجمالي شامل الضريبة</span><b class="num">${fm(c.total)}</b></div></div>
 <div class="pos-ptot">${v.pays.map(p=>`<div><span>${PN[p.m]}</span><b class="num">${fm(p.amt)}</b></div>`).join("")}${v.chg?`<div><span>الباقي</span><b class="num">${fm(v.chg)}</b></div>`:""}</div>
 ${v.mode==="pending"?`<div class="pos-pnote">هذه الفاتورة محفوظة وستُعتمد من هيئة الزكاة والضريبة والجمارك عند عودة الاتصال، ثم تُطبع النسخة الضريبية.</div>`:`<div class="pos-qrw"><div class="pos-qr2">${qrSVG(v.no)}</div><small>رمز هيئة الزكاة والضريبة والجمارك</small></div>`}
 ${v.mode==="simp"&&v.note?`<div class="pos-pnote">${v.note}</div>`:""}
 <div class="pos-bcw"><div class="pos-bc">${barSVG(v.no)}</div><b class="num">${v.no}</b><small>امسح الباركود عند الإرجاع</small></div>
 <div class="pos-pc small">شكراً لتسوقكم معنا<br>الاسترجاع خلال 7 أيام بالفاتورة الأصلية</div></div>`}
R.receipt=()=>{const v=S.last,pr=S.print;if(!v)return"";
 const fail=pr==="fail";
 return`<div class="pos-rcscr"><section class="pos-rcl"><div class="pos-paperwrap">${receiptHTML(v)}</div></section>
 <section class="pos-rcr">
  <div class="pos-done"><span class="pos-okc">${I("check")}</span><div><h2>تم إصدار الفاتورة</h2><b class="num">${v.no}</b></div><div class="pos-grow"></div><div class="pos-dtot">${money(v.c.total)}</div></div>
  ${fail?alertBox("err","<b>الفاتورة صدرت وحُفظت برقم "+v.no+"</b> — لكن الطابعة غير متصلة فلم تُطبع. لا حاجة لإعادة البيع. اختر ما تريد.","printer"):pr==="printing"?alertBox("info","جارٍ الطباعة على "+(S.printer==="built"?"الطابعة المدمجة":"الطابعة")+"…","loader"):alertBox("ok","تمت الطباعة"+(v.mode==="pending"?" (إيصال مؤقت)":""),"printer-check")}
  ${v.drawer?`<div class="pos-drawer">${I("archive-restore")}<div><b>فُتح درج النقد</b><small>دفعة نقدية — الباقي للعميل <span class="num">${fm(v.chg)}</span> ر.س</small></div></div>`:""}
  ${v.mode==="pending"?alertBox("warn","فاتورة B2B محفوظة <b>معلّقة للاعتماد</b>. ستُرسل تلقائياً للهيئة عند عودة الاتصال ثم تُطبع نسختها الضريبية.","clock-arrow-up"):v.mode==="simp"&&v.note?alertBox("info",v.note,"receipt"):""}
  ${v.mode==="b2b"?alertBox("ok","اعتمدت الهيئة الفاتورة فورياً (Clearance) — يمكن تسليمها للعميل.","file-check"):""}
  <div class="pos-rcbtns">${fail?`<button class="pos-btn pos-navy pos-lg" data-a="retryPrint">${I("refresh-cw")}إعادة المحاولة</button><button class="pos-btn pos-ghost pos-lg" data-a="wa">${I("message-circle")}واتساب</button><button class="pos-btn pos-green pos-xl" data-a="newInv">${I("skip-forward")}تخطي — فاتورة جديدة</button>`
  :`<button class="pos-btn pos-green pos-xxl" data-a="newInv">${I("plus")}فاتورة جديدة</button><div class="pos-row2"><button class="pos-btn pos-ghost pos-lg" data-a="reprint">${I("printer")}إعادة طباعة</button><button class="pos-btn pos-ghost pos-lg" data-a="wa">${I("message-circle")}إرسال واتساب</button></div>`}</div>
  <div class="pos-muted pos-small">${S.pending} فاتورة بانتظار الرفع · الفاتورة محفوظة على الجهاز وسترفع تلقائياً</div></section></div>`};

/* ---------- المعلقة ---------- */
R.held=()=>{const rows=S.held.map(h=>{const c=calc(h.lines);return`<div class="pos-hcard"><div class="pos-hh"><span class="pos-chip">${I("clock")}<span class="num">${h.t}</span></span><b>${h.cust.n}</b><div class="pos-grow"></div><b class="pos-hamt">${money(c.total)}</b></div>
 <div class="pos-hl">${h.lines.map(l=>`<span>${item(l.id).n}${l.w?"":` × ${l.q}`}</span>`).join("")}</div>
 <div class="pos-row2"><button class="pos-btn pos-navy" data-a="recall" data-id="${h.id}">${I("rotate-ccw")}استدعاء</button><button class="pos-btn pos-ghost pos-red" data-a="delheld" data-id="${h.id}">${I("trash-2")}حذف</button></div></div>`}).join("");
 return`<div class="pos-sub">${bar("الفواتير المعلقة")}<div class="pos-subbody">${S.held.length?`<div class="pos-hgrid">${rows}</div>`:`<div class="pos-center pos-empty">${I("pause-circle","pos-big")}<h2>لا توجد فواتير معلقة</h2><p>اضغط «تعليق» في شاشة البيع لحفظ سلة والعودة لها لاحقاً</p></div>`}</div></div>`};

/* ---------- المرتجع ---------- */
const INV_DB={"A2-000152":{no:"A2-000152",t:"اليوم 10:14",pay:"مدى",lines:[{n:"أرز بسمتي 5 كجم",unit:"حبة",q:2,price:38.25,id:101},{n:"زيت دوار الشمس 1.5 لتر",unit:"حبة",q:1,price:18.75,id:102},{n:"تمر سكري",unit:"كجم",q:0.375,price:32,id:106,w:1},{n:"مياه شرب 330 مل",unit:"حبة",q:6,price:1,id:104}]}};
R.ret=()=>{const T=S.ret,inv=T.inv;
 let body;
 if(T.done)body=`<div class="pos-card pos-center pos-narrow">${I("circle-check","pos-big ok")}<h2>تم تنفيذ المرتجع</h2><p>إشعار دائن <b class="num">A2-R-000012</b> بمبلغ <b class="num">${fm(T.done)}</b> ر.س — ${T.method==="cash"?"خرجت من درج النقد":"عُكست على مدى"}. سُجّل المشرف ${SUP.n}.</p><button class="pos-btn pos-green pos-lg" data-a="go" data-s="sale">العودة للبيع</button></div>`;
 else if(!inv)body=`<div class="pos-card pos-narrow pos-center">${I("scan-barcode","pos-big")}<h2>امسح باركود الإيصال الأصلي</h2><p class="pos-muted">يعمل الماسح مباشرة، أو اكتب رقم الفاتورة</p>
  <label class="pos-field wide"><input id="pos-rq" value="${T.code}" placeholder="A2-000152" dir="ltr" data-model="ret.code" autocomplete="off"></label>
  ${T.err==="nf"?alertBox("warn","<b>لم يجد الجهاز هذه الفاتورة.</b> يبدو أنها صدرت من جهاز آخر (B1) ولم تُرفع للسيرفر بعد. انتظر مزامنة ذلك الجهاز أو ارجع للإيصال الورقي والمشرف.","search-x"):""}
  <button class="pos-btn pos-navy pos-lg" data-a="findInv">${I("search")}بحث</button><div class="pos-quick pos-demo"><span class="pos-tag">تجربة</span><button class="pos-btn pos-ghost" data-a="findInv" data-v="A2-000152">A2-000152</button><button class="pos-btn pos-ghost" data-a="findInv" data-v="B1-000077">B1-000077 (جهاز آخر)</button></div></div>`;
 else{const tot=inv.lines.reduce((a,l,i)=>a+(T.qty[i]||0)*l.price,0);
  body=`<div class="pos-retgrid"><div class="pos-card"><div class="pos-pinhd"><span class="pos-okc sm">${I("receipt")}</span><div><b class="num">${inv.no}</b><small>${inv.t} · دُفعت بـ ${inv.pay}</small></div></div>
  ${T.err==="over"?alertBox("err","الكمية المرتجعة أكبر من المباعة. الحد الأقصى هو الكمية في الفاتورة الأصلية.","circle-x"):""}
  ${inv.lines.map((l,i)=>`<div class="pos-rl"><div><b>${l.n}</b><small>المباع: <span class="num">${l.w?l.q.toFixed(3):l.q}</span> ${l.unit} × <span class="num">${fm(l.price)}</span></small></div><div class="pos-grow"></div>
   ${l.w?`<button class="pos-btn ${T.qty[i]?"pos-navy":"pos-ghost"}" data-a="rlw" data-i="${i}">${T.qty[i]?"سيُرجع كاملاً":"إرجاع كامل الوزن"}</button>`:`<div class="pos-step"><button data-a="rlq" data-i="${i}" data-d="-1">${I("minus")}</button><b class="pos-qv num">${T.qty[i]||0}</b><button data-a="rlq" data-i="${i}" data-d="1">${I("plus")}</button></div>`}<b class="pos-rlt num">${fm((T.qty[i]||0)*l.price)}</b></div>`).join("")}</div>
  <div class="pos-card pos-retside"><h3>طريقة الإرجاع</h3>${[["cash","نقداً من الدرج","banknote"],["orig","عكس الدفع الأصلي ("+inv.pay+")","credit-card"]].map(([k,n,ic])=>`<button class="pos-opt ${T.method===k?"on":""}" data-a="rmethod" data-v="${k}">${I(ic)}<b>${n}</b></button>`).join("")}
  <div class="pos-remain"><span>مبلغ الإرجاع</span><b>${money(tot)}</b></div>${alertBox("info","المرتجع يحتاج رمز المشرف. يُسجَّل إشعار دائن ويعود المنتج للمخزون.","shield-check")}
  <button class="pos-btn pos-green pos-xl" data-a="doRet" ${tot>0?"":"disabled"}>${I("shield-check")}تنفيذ المرتجع</button></div></div>`}
 return`<div class="pos-sub">${bar("مرتجع")}<div class="pos-subbody">${body}</div></div>`};

/* ---------- إيداع / سحب ---------- */
const expectedCash=()=>{const s=S.shift;return r2(s.float+s.sales.cash+s.cashIn-s.cashOut-s.refCash)};
R.cashio=()=>{const T=S.cashio,out=T.type==="out",amt=+T.amt||0,exp=expectedCash(),over=out&&amt>exp;
 return`<div class="pos-sub">${bar("إيداع / سحب نقد")}<div class="pos-subbody"><div class="pos-card pos-cashbox">
 <div class="pos-seg"><button class="${!out?"on":""}" data-a="cioType" data-v="in">${I("arrow-down-to-line")}إيداع في الدرج</button><button class="${out?"on":""}" data-a="cioType" data-v="out">${I("arrow-up-from-line")}سحب من الدرج</button></div>
 <div class="pos-split"><div><div class="pos-bigfield"><small>المبلغ</small><b class="num">${T.amt||"0"}</b>${SAR}</div><div class="pos-muted pos-small">النقد المتوقع في الدرج الآن: <b class="num">${fm(exp)}</b> ر.س</div>
 ${over?alertBox("err",`السحب (${fm(amt)}) أكبر من النقد المتوقع في الدرج (${fm(exp)}). يحتاج موافقة المشرف.`):""}
 <h4>السبب</h4><div class="pos-quick pos-wrapq">${(out?["مصروف نثرية","دفع لمورد","إيداع بنكي","تسليم للمشرف"]:["فكّة إضافية","إيداع من المشرف","تصحيح"]).map(r=>`<button class="pos-btn ${T.reason===r?"pos-navy":"pos-ghost"}" data-a="cioReason" data-v="${r}">${r}</button>`).join("")}</div>
 <button class="pos-btn ${over?"pos-amber":"pos-green"} pos-xl" data-a="cioSave" ${amt>0&&T.reason?"":"disabled"}>${over?I("shield-check")+"اعتماد المشرف والمتابعة":I("check")+"تسجيل "+(out?"السحب":"الإيداع")}</button></div>${numpad("cioKey")}</div></div></div></div>`};

/* ---------- إقفال الوردية ---------- */
function xz(kind){const s=S.shift,sales=s.sales,tot=Object.values(sales).reduce((a,b)=>a+b,0),exp=expectedCash();
 return`<div class="pos-paper"><div class="pos-pc"><b class="pos-co">تقرير ${kind} — ${kind==="Z"?"إقفال وردية":"منتصف الوردية"}</b><div>${REG.name}</div><div class="num">${new Date().toLocaleDateString("en-GB")} · فتحت ${s.openedAt}</div><div>${S.cashier?S.cashier.n:CASHIERS[0].n}</div></div>
 <div class="pos-ptot"><div><span>عدد الفواتير</span><b class="num">${s.inv}</b></div><div><span>العهدة الافتتاحية</span><b class="num">${fm(s.float)}</b></div>${PAY.filter(p=>sales[p[0]]).map(p=>`<div><span>مبيعات ${p[1]}</span><b class="num">${fm(sales[p[0]])}</b></div>`).join("")}<div class="g"><span>إجمالي المبيعات</span><b class="num">${fm(tot)}</b></div></div>
 <div class="pos-ptot"><div><span>إيداع نقد</span><b class="num">${fm(s.cashIn)}</b></div><div><span>سحب نقد</span><b class="num">${fm(s.cashOut)}</b></div><div><span>مرتجعات نقدية</span><b class="num">${fm(s.refCash)}</b></div><div class="g"><span>النقد المتوقع في الدرج</span><b class="num">${fm(exp)}</b></div></div>
 <div class="pos-ptot"><div><span>ضريبة المخرجات</span><b class="num">${fm(tot*15/115)}</b></div><div><span>فواتير غير مرفوعة</span><b class="num">${S.pending}</b></div></div></div>`}
const countedCash=()=>r2(DENOMS.reduce((a,d)=>a+d*(S.close.counts[d]||0),0));
R.close=()=>{const T=S.close,exp=expectedCash(),cc=countedCash();
 const steps=["عدّ النقد","المعدود لباقي الطرق","تقرير Z"];
 const stepper=`<div class="pos-steps">${steps.map((s,i)=>`<div class="${T.step===i+1?"on":T.step>i+1?"done":""}"><b>${T.step>i+1?I("check"):i+1}</b>${s}</div>`).join("")}</div>`;
 const warnU=S.pending?alertBox("info",`لديك <b class="num">${S.pending}</b> فواتير لم تُرفع بعد — <b>لا تمنع الإقفال</b>، ستُرفع تلقائياً عند الاتصال.`,"upload-cloud"):"";
 let body="";
 if(T.step===1)body=`<div class="pos-countgrid"><div class="pos-card"><h3>عدد الأوراق والعملات لكل فئة</h3><div class="pos-dn">${DENOMS.map(d=>`<div class="pos-dnr"><b class="num">${d<1?d.toFixed(2):d}</b><small>${d>=5?"ورقة":"عملة"}</small><div class="pos-step"><button data-a="dn" data-d="${d}" data-k="-1">${I("minus")}</button><button class="pos-qv num" data-a="dnnum" data-d="${d}">${T.counts[d]||0}</button><button data-a="dn" data-d="${d}" data-k="1">${I("plus")}</button></div><span class="pos-dns num">${fm(d*(T.counts[d]||0))}</span></div>`).join("")}</div></div>
 <div class="pos-card pos-ctot"><small>إجمالي النقد المعدود</small><strong>${money(cc)}</strong>${T.blind?alertBox("info","<b>عدّ أعمى:</b> المبلغ المتوقع مخفي حتى تنتهي من العدّ، لتجنّب التعديل على الرقم. (قرار مفتوح: هل يرى الكاشير المتوقع؟)","eye-off"):`<div class="pos-expv"><span>المتوقع في الدرج</span><b class="num">${fm(exp)}</b><span>الفرق</span><b class="num ${cc-exp<0?"neg":""}">${fm(cc-exp)}</b></div>`}<button class="pos-btn pos-green pos-xl" data-a="cstep" data-v="2" ${cc>0?"":"disabled"}>التالي: باقي الطرق${I("chevron-left")}</button></div></div>`;
 if(T.step===2){const oth=["mada","card","apple","transfer"].filter(k=>S.shift.sales[k]||k==="transfer").slice(0,4);
  body=`<div class="pos-countgrid"><div class="pos-card"><h3>المعدود لباقي طرق الدفع (من كشف جهاز الدفع)</h3>${oth.map(k=>`<div class="pos-ot"><b>${PN[k]}</b><button class="pos-bigfield sm btn" data-a="otnum" data-k="${k}"><b class="num">${T.other[k]!=null?fm(T.other[k]):"اضغط للإدخال"}</b>${SAR}</button>${T.blind?"":`<small class="pos-muted">المتوقع <span class="num">${fm(S.shift.sales[k])}</span></small>`}</div>`).join("")}<div class="pos-muted pos-small">الآجل والقسائم تُراجع من لوحة التحكم ولا تُعدّ هنا.</div></div>
  <div class="pos-card pos-ctot"><small>النقد المعدود</small><strong>${money(cc)}</strong>${warnU}<button class="pos-btn pos-green pos-xl" data-a="cstep" data-v="3">إظهار تقرير Z ${I("chevron-left")}</button><button class="pos-btn pos-ghost" data-a="cstep" data-v="1">${I("chevron-right")}رجوع للعدّ</button></div></div>`}
 if(T.step===3){const rows=[["نقدي",exp,cc],...["mada","card","apple"].map(k=>[PN[k],S.shift.sales[k],T.other[k]!=null?T.other[k]:S.shift.sales[k]])];
  body=`<div class="pos-countgrid"><div class="pos-card"><h3>الفروقات بعد العدّ</h3><table class="pos-dt"><thead><tr><th>الطريقة</th><th>المتوقع</th><th>المعدود</th><th>الفرق</th></tr></thead><tbody>${rows.map(([n,e,c])=>{const d=r2(c-e);return`<tr><td><b>${n}</b></td><td class="num">${fm(e)}</td><td class="num">${fm(c)}</td><td class="num ${d?(d<0?"neg":"pos"):""}">${d>0?"+":""}${fm(d)}</td></tr>`}).join("")}</tbody></table>${cc!==exp?alertBox("warn",`فرق النقد <b class="num">${fm(cc-exp)}</b> ر.س سيُسجَّل في «عجز وزيادة الصناديق» ويُرسل للمشرف.`):""}${warnU}
  <div class="pos-row2"><button class="pos-btn pos-navy pos-lg" data-a="printZ">${I("printer")}طباعة تقرير Z</button><button class="pos-btn pos-green pos-lg" data-a="finishShift">${I("lock-keyhole")}إنهاء الوردية والخروج</button></div></div><div class="pos-paperwrap pos-sm">${xz("Z")}</div></div>`}
 return`<div class="pos-sub">${bar("إقفال الوردية",S.shift.prev?"shift":"sale",`<button class="pos-btn pos-ghost" data-a="xrep">${I("file-bar-chart")}تقرير X</button>`)}<div class="pos-subbody wide">${stepper}${body}</div></div>`};

/* ---------- المزامنة ---------- */
R.sync=()=>{const sy=S.sync,off=!S.online;
 return`<div class="pos-sub">${bar("حالة المزامنة","sale")}<div class="pos-subbody"><div class="pos-syncgrid">
 <div class="pos-stat ${S.pending?"amber":""}">${I("upload-cloud")}<small>فواتير لم تُرفع</small><b class="num">${S.pending}</b></div>
 <div class="pos-stat ${S.stale?"red":""}">${I("refresh-cw")}<small>آخر مزامنة ناجحة</small><b class="num">${S.stale?"أمس 14:05":S.lastSync}</b></div>
 <div class="pos-stat ${sy.rejected.length?"red":""}">${I("octagon-alert")}<small>مرفوضة من السيرفر</small><b class="num">${sy.rejected.length}</b></div>
 <div class="pos-stat ${off?"amber":"green"}">${I(off?"wifi-off":"wifi")}<small>الاتصال</small><b>${off?"غير متصل":"متصل"}</b></div></div>
 ${S.stale?alertBox("err","<b>مرّت 20 ساعة دون رفع.</b> الفواتير يجب أن تُبلَّغ للهيئة خلال 24 ساعة. زامن الآن.","triangle-alert"):""}
 ${sy.update?alertBox("warn","<b>يتطلب هذا الإصدار تحديثاً (1.0.3 → 1.1.0).</b> المزامنة ستتوقف بعد 3 أيام. البيع مستمر، وتحديث التطبيق لا يمسح الفواتير غير المرفوعة.","download"):""}
 ${sy.done?alertBox("ok",sy.done,"circle-check"):""}
 <div class="pos-syncmain"><div class="pos-card"><h3>فواتير مرفوضة من السيرفر</h3>${sy.rejected.length?sy.rejected.map(r=>`<div class="pos-rej"><div><b class="num">${r.no}</b><small>${r.t}</small></div><div class="pos-rejd">${I("scale")}<span>اختلاف في الإجمالي: الجهاز <b class="num">${fm(r.dev)}</b> — السيرفر <b class="num">${fm(r.srv)}</b></span></div><button class="pos-btn pos-ghost" data-a="rejSend">${I("send")}إرسال للمراجعة</button></div>`).join(""):`<div class="pos-muted pos-pl0">لا توجد فواتير مرفوضة</div>`}<p class="pos-muted pos-small">لا تُحذف الفاتورة المرفوضة (صدرت للعميل). يراجعها مدير الفرع من لوحة التحكم.</p></div>
 <div class="pos-card pos-syncact"><button class="pos-btn pos-green pos-xl" data-a="syncNow" ${sy.busy?"disabled":""}>${I(sy.busy?"loader":"refresh-cw",sy.busy?"spin":"")}${sy.busy?"جارٍ المزامنة…":"مزامنة الآن"}</button>${off?alertBox("warn","الجهاز غير متصل. سيزامن تلقائياً عند عودة الاتصال، والبيع مستمر.","wifi-off"):""}
 <button class="pos-btn pos-ghost pos-lg" data-a="${sy.update?"doUpdate":"checkUpdate"}">${I("download")}${sy.update?"تحديث التطبيق الآن":"البحث عن تحديث"}</button></div></div></div></div>`};

/* ---------- الإعدادات ---------- */
R.settings=()=>{const opt=(k,v,cur,ic,n,sub,st)=>`<button class="pos-opt ${cur===v?"on":""}" data-a="cfg" data-k="${k}" data-v="${v}">${I(ic)}<div><b>${n}</b><small>${sub}</small></div>${st&&st[1]?`<span class="pos-tag ${st[0]}">${st[1]}</span>`:""}</button>`;
 return`<div class="pos-sub">${bar("الإعدادات","sale")}<div class="pos-subbody wide">${installCard()}${WEB()?`<div class="pos-webmode">${I("globe")}<div><b>يعمل الآن كتطبيق ويب</b><small>الطباعة من نافذة طباعة الجهاز على الطابعة الحرارية (USB أو شبكة أو بلوتوث)، والماسح يعمل كلوحة مفاتيح، والدرج يُفتح عبر الطابعة. الفواتير تُبلّغ للهيئة فوراً من السيرفر.</small></div></div>`:""}<div class="pos-setgrid">
 <div class="pos-card"><h3>${I("printer")}الطابعة الحرارية 80 مم</h3>${opt("printer","built",S.printer,"smartphone",WEB()?"طابعة الجهاز (من المتصفح)":"مدمجة في الجهاز",WEB()?"تختار الطابعة من نافذة الطباعة مرة واحدة":"الأسرع وبدون إعداد",["green","متصلة"])}${opt("printer","usb",S.printer,"usb","USB","Epson TM-T20III",["green","متصلة"])}${opt("printer","net",S.printer,"network","شبكة (IP)","192.168.1.40 : 9100",["amber","لم تُختبر"])}${opt("printer","bt",S.printer,"bluetooth","بلوتوث","لا توجد أجهزة مقترنة",["",""])}
  <button class="pos-btn pos-navy pos-lg" data-a="testPage">${I("file-text")}طباعة صفحة تجربة</button></div>
 <div class="pos-col"><div class="pos-card"><h3>${I("scan-barcode")}ماسح الباركود</h3>${opt("scanner","kbd",S.scanner,"keyboard","ماسح USB / بلوتوث (كلوحة مفاتيح)","لا يحتاج حقلاً محدداً",["green","يعمل"])}${opt("scanner","cam",S.scanner,"camera","كاميرا الجهاز","للطوارئ فقط",null)}<label class="pos-field"><small>جرّب المسح الآن</small><input data-model="" id="pos-scantest" placeholder="امسح أي باركود…"></label></div>
 <div class="pos-card"><h3>${I("scale")}الميزان</h3>${opt("scale","usb",S.scale,"usb","ميزان USB / سيريال","قراءة الوزن الحية",["green","0.000 كجم"])}${opt("scale","none",S.scale,"scale","بدون ميزان","المنتجات الموزونة بالباركود فقط",null)}</div>
 <div class="pos-card"><h3>${I("archive-restore")}درج النقد</h3>${opt("drawerCfg","printer",S.drawerCfg,"printer","يُفتح عبر الطابعة","مع كل دفعة نقدية","")}<button class="pos-btn pos-ghost pos-lg" data-a="testDrawer">${I("lock-open")}فتح الدرج للتجربة</button></div></div></div></div></div>`};

/* ---------- المطعم (المرحلة 5) ---------- */
const TABLES=[[1,"empty",4],[2,"busy",4,"84.50","12 د"],[3,"pay",2,"137.00","41 د"],[4,"empty",6],[5,"busy",2,"46.00","5 د"],[6,"empty",4],[7,"pay",8,"512.25","58 د"],[8,"busy",4,"98.75","23 د"],[9,"empty",2],[10,"empty",4]];
const MENU=[["برجر لحم","beef",28,["بدون بصل","جبن زيادة","حار"]],["دجاج مشوي","drumstick",34,["بدون صلصة","أرز زيادة"]],["بيتزا مارجريتا","pizza",30,["حجم كبير","جبن زيادة"]],["عصير برتقال","cup-soda",12,["بدون سكر","ثلج"]],["سلطة","salad",15,["بدون صلصة"]],["قهوة عربية","coffee",10,[]]];
R.rest=()=>{const R_=S.rest,t=R_.table,tb=TABLES.find(x=>x[0]===t),tot=R_.cart.reduce((a,l)=>a+l.p*l.q,0);
 return`<div class="pos-sub">${bar("المطعم","sale",`<span class="pos-tag amber">المرحلة 5</span>`)}<div class="pos-restgrid"><section class="pos-card"><div class="pos-seg">${[["dine","داخل المطعم","armchair"],["take","سفري","shopping-bag"],["deliv","توصيل","bike"]].map(([k,n,ic])=>`<button class="${R_.type===k?"on":""}" data-a="rtype" data-v="${k}">${I(ic)}${n}</button>`).join("")}</div>
  ${R_.type==="dine"?`<div class="pos-legend"><span class="e">فارغة</span><span class="b">مشغولة</span><span class="p">بانتظار الدفع</span></div><div class="pos-tables">${TABLES.map(([n,st,seats,amt,tm])=>`<button class="pos-tbl ${st} ${t===n?"sel":""}" data-a="rtable" data-t="${n}"><b class="num">${n}</b><small>${seats} مقاعد</small>${amt?`<em class="num">${amt}</em><small>${tm}</small>`:""}</button>`).join("")}</div>`:`<div class="pos-muted pos-pl0">${R_.type==="take"?"طلب سفري برقم تسلسلي":"طلب توصيل — العنوان واسم العميل مطلوبان"}</div>`}
  <h4>القائمة — المس منتجاً وأضف التعديلات</h4><div class="pos-menu">${MENU.map(([n,ic,p],i)=>`<button class="pos-item" data-a="radd" data-i="${i}">${tile({ic,col:"#c8862a"})}<b>${n}</b><span class="pos-price">${money(p)}</span></button>`).join("")}</div></section>
  <aside class="pos-card pos-restside"><h3>${R_.type==="dine"?(tb?`طاولة ${tb[0]}`:"اختر طاولة"):R_.type==="take"?"طلب سفري #118":"توصيل #119"}</h3>${R_.cart.length?R_.cart.map((l,i)=>`<div class="pos-rl"><div><b>${l.n} × <span class="num">${l.q}</span></b><div class="pos-mods">${MENU[l.i][3].map(m=>`<button class="pos-mod ${l.mods.includes(m)?"on":""}" data-a="rmod" data-i="${i}" data-m="${m}">${m}</button>`).join("")}</div></div><div class="pos-grow"></div><b class="num">${fm(l.p*l.q)}</b></div>`).join(""):`<div class="pos-cart-empty">${I("utensils","pos-big")}<p>أضف منتجاتاً من القائمة</p></div>`}
  <div class="pos-grand"><span>الإجمالي</span><strong>${money(tot)}</strong></div><div class="pos-row2"><button class="pos-btn pos-navy pos-lg" data-a="kitchen" ${R_.cart.length?"":"disabled"}>${I("chef-hat")}إرسال للمطبخ</button><button class="pos-btn pos-green pos-lg" data-a="go" data-s="sale">${I("credit-card")}دفع</button></div></aside></div></div>`};

/* ---------- النوافذ (modal) ---------- */
function modalHTML(m){
 const hd=(t,sub)=>`<div class="pos-mh"><div><h2>${t}</h2>${sub?`<p>${sub}</p>`:""}</div><button class="pos-btn pos-ghost pos-sq" data-a="mclose" aria-label="إغلاق">${I("x")}</button></div>`;
 switch(m.type){
 case"sup":return`<div class="pos-mod2 pos-sm">${hd("موافقة المشرف",m.why)}<div class="pos-pinhd">${avatar(SUP)}<div><b>${SUP.n}</b><small>أدخل رمز المشرف (4 أرقام)</small></div></div>${dots(m.pin.length,m.err)}<div class="pos-pinmsg">${m.err||"&nbsp;"}</div>${pinpad("supKey")}</div>`;
 case"num":return`<div class="pos-mod2 pos-sm">${hd(m.title,m.sub)}<div class="pos-bigfield"><b class="num">${m.val||"0"}</b><small>${m.unit||""}</small></div>${numpad("mnum")}<button class="pos-btn pos-green pos-xl" data-a="mok">تأكيد</button></div>`;
 case"weigh":{const it=item(m.id);return`<div class="pos-mod2 pos-sm">${hd("وزن المنتج",it.n+" — "+fm(up(it,0))+" ر.س / كجم")}<div class="pos-bigfield">${I("scale")}<b class="num">${m.val||"0.000"}</b><small>كجم</small></div><div class="pos-quick"><button class="pos-btn pos-navy" data-a="readScale">${I("scale")}قراءة الميزان</button><span class="pos-muted">الإجمالي: <b class="num">${fm((+m.val||0)*up(it,0))}</b> ر.س</span></div>${numpad("mnum")}<button class="pos-btn pos-green pos-xl" data-a="mok" ${+m.val>0?"":"disabled"}>إضافة للسلة</button></div>`}
 case"disc":{const l=S.cart.find(x=>x.uid===m.uid),it=item(l.id);return`<div class="pos-mod2 pos-sm">${hd("خصم على السطر",it.n)}<div class="pos-bigfield"><b class="num">${m.val||"0"}</b><small>%</small></div><div class="pos-quick pos-q5">${[2,5,10,15,20].map(v=>`<button class="pos-btn pos-ghost" data-a="dchip" data-v="${v}"><span class="num">${v}%</span></button>`).join("")}</div>${alertBox("info",`حدّ خصم الكاشير ${REG.maxDisc}%. الأعلى يحتاج رمز المشرف.`)}${numpad("mnum")}<div class="pos-row2"><button class="pos-btn pos-ghost pos-lg" data-a="dclear">إلغاء الخصم</button><button class="pos-btn pos-green pos-lg" data-a="mok" ${+m.val>0&&+m.val<=100?"":"disabled"}>تطبيق${+m.val>REG.maxDisc?" (مشرف)":""}</button></div></div>`}
 case"cust":return`<div class="pos-mod2">${hd("اختيار العميل","العملاء محفوظون على الجهاز ويعملون بدون إنترنت")}<div class="pos-clist">${CUSTS.map(c=>`<button class="pos-cr ${S.cust.id===c.id?"on":""}" data-a="pickcust" data-id="${c.id}">${I(c.cash?"user-round":"building-2")}<div><b>${c.n}</b><small>${c.cash?"بيع نقدي بدون بيانات":(c.vat?"رقم ضريبي "+c.vat+" · ":"")+"المتبقي من الحد "+fm(c.limit-c.used)}</small></div>${c.vat?`<span class="pos-tag green">B2B</span>`:""}</button>`).join("")}</div></div>`;
 case"menu":return`<div class="pos-pop">${[["held","pause-circle","الفواتير المعلقة",S.held.length],["ret","undo-2","مرتجع"],["cashio","arrow-left-right","إيداع / سحب نقد"],["xrep","file-bar-chart","تقرير X"],["sync","refresh-cw","حالة المزامنة",S.pending],["settings","settings","الإعدادات"],["rest","utensils","وضع المطعم","5"],["close","lock-keyhole","إقفال الوردية"],["logout","log-out","تبديل الكاشير"]].map(([k,ic,n,c])=>`<button data-a="mgo" data-s="${k}">${I(ic)}<span>${n}</span>${c?`<i class="pos-count ${c==="5"?"ph":""}">${c==="5"?"المرحلة 5":c}</i>`:""}</button>`).join("")}</div>`;
 case"recall":return`<div class="pos-mod2 pos-sm">${hd("السلة الحالية غير فارغة",`تحتوي ${S.cart.length} منتجات بقيمة ${fm(calc().total)} ر.س`)}<div class="pos-stack"><button class="pos-btn pos-navy pos-lg" data-a="recallHold" data-id="${m.id}">${I("pause")}تعليق الحالية ثم استدعاء</button><button class="pos-btn pos-ghost pos-lg pos-red" data-a="recallReplace" data-id="${m.id}">${I("trash-2")}حذف الحالية واستدعاء</button><button class="pos-btn pos-ghost pos-lg" data-a="mclose">إلغاء</button></div></div>`;
 case"scan":return`<div class="pos-mod2 pos-sm">${hd("مسح باركود","الماسح يعمل مباشرة في شاشة البيع، وهذا للتجربة")}<div class="pos-cam sm"><i class="pos-scanline"></i></div><label class="pos-field wide"><input id="pos-sc" dir="ltr" placeholder="اكتب الباركود ثم Enter" autocomplete="off"></label><div class="pos-stack">${[["6281000101015","أرز بسمتي (حبة)"],["6281000101022","أرز بسمتي (كرتون)"],[SCALE_CODE,"تمر سكري 0.375 كجم (ميزان)"],["6281000999999","باركود غير معروف"]].map(([c,n])=>`<button class="pos-btn pos-ghost pos-between" data-a="fakeScan" data-v="${c}"><span>${n}</span><span class="num pos-muted">${c}</span></button>`).join("")}</div></div>`;
 case"xrep":return`<div class="pos-mod2 pos-sm">${hd("تقرير X","منتصف الوردية — لا يُقفل الوردية")}<div class="pos-paperwrap pos-sm2">${xz("X")}</div><button class="pos-btn pos-navy pos-lg" data-a="printX">${I("printer")}طباعة</button></div>`;
 case"testpage":return`<div class="pos-mod2 pos-sm">${hd("صفحة التجربة","أُرسلت إلى الطابعة")}<div class="pos-paperwrap pos-sm2"><div class="pos-paper"><div class="pos-pc"><b class="pos-co">صفحة تجربة الطابعة</b><div>80 مم · UTF-8 عربي</div></div><div class="pos-pm"><span>الجهاز</span><b>${REG.prefix}</b><span>الاتصال</span><b>${S.printer==="built"?"مدمجة":S.printer==="usb"?"USB":S.printer==="net"?"شبكة":"بلوتوث"}</b></div><div class="pos-bc" style="height:36px">${barSVG("TEST-PRINT")}</div><div class="pos-pc small">123456789 ABC أبجد هوز<br>تمت الطباعة بنجاح</div></div></div></div>`}
 return""}
function renderModal(){const el=$("#pos-modal");if(!el)return;const m=S.modal;
 el.innerHTML=m?`<div class="pos-ovl ${m.type==="menu"?"clear":""}" data-a="mclose" data-bg="1">${modalHTML(m)}</div>`:"";if(window.lucide)lucide.createIcons();
 if(m&&m.type==="scan"){const i=$("#pos-sc");i&&i.focus()}}
const openModal=m=>{S.modal=m;renderModal()};
const closeModal=()=>{S.modal=null;renderModal()};
const sup=(why,cb)=>openModal({type:"sup",why,cb,pin:"",err:""});

/* ---------- معاينة (للتصميم فقط) ---------- */
const SCREENS=[["pair","ربط الجهاز"],["login","الدخول"],["shift","فتح وردية"],["sale","البيع"],["pay","الدفع"],["receipt","الإيصال"],["held","المعلقة"],["ret","المرتجع"],["cashio","إيداع / سحب"],["close","إقفال الوردية"],["sync","حالة المزامنة"],["settings","الإعدادات"],["rest","المطعم (م5)"]];
function pvHTML(){const t=(k,n,v)=>`<button class="pos-tg ${v?"on":""}" data-a="pvt" data-k="${k}"><i></i>${n}</button>`;
 return`<button class="pos-pvbtn" data-a="pvopen">${I("eye")}معاينة</button>${S.pv.open?`<aside class="pos-pv"><div class="pos-pvh"><b>${I("eye")}معاينة — للتصميم فقط</b><button data-a="pvopen" aria-label="إغلاق">${I("x")}</button></div><div class="pos-pvb">
 <small>الانتقال لشاشة</small><div class="pos-pvs">${SCREENS.map(([k,n])=>`<button class="${S.screen===k?"on":""}" data-a="pvgo" data-s="${k}">${n}</button>`).join("")}</div>
 <small>نوع التشغيل</small><div class="pos-pvs"><button class="${WEB()?"on":""}" data-a="pvmode" data-v="web">تطبيق ويب (الآن)</button><button class="${WEB()?"":"on"}" data-a="pvmode" data-v="android">أندرويد (المرحلة 3)</button></div>
 <small>الحالات</small><div class="pos-pvt">${t("online","متصل بالإنترنت",S.online)}${t("stale","تحذير 20 ساعة",S.stale)}${t("printerOff","الطابعة غير متصلة",S.printerOff)}${t("stockBlock","منع البيع عند نقص الرصيد",S.stockMode==="block")}${t("newPrices","وصلت أسعار جديدة",S.newPrices)}${t("blind","عدّ أعمى عند الإقفال",S.blind)}${t("b2b","عميل برقم ضريبي (B2B)",!!S.cust.vat)}${t("update","تحديث التطبيق مطلوب",S.sync.update)}${t("prev","وردية سابقة مفتوحة",S.shift.prev)}</div>
 <small>اختبار الباركود</small><div class="pos-pvs">${[["6281000101015","أرز"],[SCALE_CODE,"ميزان 0.375"],["6281000999999","مجهول"],["6281000102012","زيت"]].map(([c,n])=>`<button data-a="fakeScan" data-v="${c}">${n}</button>`).join("")}</div>
 <small>قرارات مفتوحة</small><div class="pos-pvn"><b>الأسعار شاملة الضريبة (افتراضي):</b> سعر الرف 15% مشمولة، والسلة تعرض جزء الضريبة. القرار النهائي مفتوح (القسم 13).<br><b>العدّ الأعمى:</b> افتراضي، والمتوقع يظهر بعد العدّ.<br><b>B2B دون اتصال:</b> الحالتان مصمّمتان (D6).<br><b>رموز التجربة:</b> كاشير 1111 · مشرف 2468 · كود الربط ${PAIR_OK}</div></div></aside>`:""}`}

/* ---------- العرض ---------- */
let audio;function beep(f=1180,d=.07){try{audio=audio||new (window.AudioContext||window.webkitAudioContext)();const o=audio.createOscillator(),g=audio.createGain();o.frequency.value=f;o.type="square";g.gain.value=.06;o.connect(g);g.connect(audio.destination);o.start();o.stop(audio.currentTime+d)}catch(e){}}
let msgT,toastT;
function renderMsg(){const el=$("#pos-msg");if(!el)return;const m=S.msg;el.innerHTML=m?`<div class="pos-m ${m.t}">${I(m.t==="ok"?"scan-barcode":m.t==="err"?"circle-x":"triangle-alert")}<span>${m.x}</span><button data-a="msgx" aria-label="إغلاق">${I("x")}</button></div>`:"";if(window.lucide)lucide.createIcons()}
function msg(t,x,ms=3800){S.msg={t,x};renderMsg();clearTimeout(msgT);msgT=setTimeout(()=>{S.msg=null;renderMsg()},ms)}
function toast(x,t="ok"){let el=$("#pos-toast");if(!el)return;el.className="pos-toast on "+t;el.innerHTML=I(t==="err"?"circle-x":"circle-check")+"<span>"+x+"</span>";if(window.lucide)lucide.createIcons();clearTimeout(toastT);toastT=setTimeout(()=>el.classList.remove("on"),2600)}
function render(){const root=$("#pos-root");
 root.innerHTML=`<div class="pos-shell ${S.stale?"is-stale":""}">${warn20()}<main class="pos-stage" data-screen="${S.screen}">${(R[S.screen]||R.sale)()}</main>${syncBar()}</div><div id="pos-modal"></div><div id="pos-toast" class="pos-toast"></div>${pvHTML()}`;
 if(window.lucide)lucide.createIcons();renderMsg();renderModal();
 if(S.flash){const e=$("#ln"+S.flash);e&&e.scrollIntoView({block:"nearest"});S.flash=null}
 else{const l=$("#pos-lines");if(l&&S._sc)l.scrollTop=l.scrollHeight}S._sc=false}
const go=s=>{S.screen=s;S.modal=null;S.q="";if(s==="pay"){}render()};
const now=()=>{const d=new Date();return String(d.getHours()).padStart(2,"0")+":"+String(d.getMinutes()).padStart(2,"0")};

/* ---------- منطق البيع ---------- */
function addItem(id,o={}){const it=item(id),u=o.u||0,w=o.w;
 const need=baseQty(id)+(w?w:it.units[u][1]);let low=false;
 if(it.qty!=null&&need>it.qty){if(S.stockMode==="block"){beep(260,.25);msg("err",`الرصيد غير كافٍ لـ «${it.n}» — المتاح ${fmq(Math.max(0,it.qty))} فقط. هذه الصالة تمنع البيع بدون رصيد.`);return false}low=true}
 let l=!w&&S.cart.find(x=>x.id===id&&x.u===u&&!x.w);
 if(l)l.q++;else{l=mkLine(id,u,w||1,!!w);S.cart.push(l)}
 if(low)l.low=true;S.flash=l.uid;S._sc=true;beep();
 if(!o.silent){if(low)msg("warn",`تمت إضافة «${it.n}» — الرصيد المتاح (${fmq(Math.max(0,it.qty))}) أقل من الكمية، البيع مستمر.`);else if(o.scan)msg("ok",`تم المسح: <b>${it.n}</b>${w?` — ${w.toFixed(3)} كجم`:""}`,2200)}
 return true}
function scan(code){code=String(code).trim();if(!code)return;let r;
 if(/^21\d{11}$/.test(code)){const it=item(+code.slice(2,7)),wt=+code.slice(7,12)/1000;if(it&&it.w){S.q="";addItem(it.id,{w:wt,scan:1});render();return}}
 for(const it of ITEMS)for(let u=0;u<it.units.length;u++)if(it.units[u][3]===code||it.code===code.toUpperCase()&&u===0){r=[it,u];break}
 if(r){S.q="";addItem(r[0].id,{u:r[1],scan:1});render()}
 else{beep(300,.2);S.q=code;S.msg=null;render();msg("warn",`الباركود <b class="num">${code}</b> غير معروف — ابحث عن المنتج بالاسم. (لم يتوقف العمل)`,6000);const i=$("#pos-q");i&&i.focus()}}
function holdCart(){if(!S.cart.length)return;S.held.unshift({id:"h"+Date.now(),t:now(),cust:S.cust,lines:S.cart.map(l=>({...l}))});S.cart=[];S.cust=CUSTS[0]}
function loadHeld(id){const h=S.held.find(x=>x.id===id);S.cart=h.lines.map(l=>({...l,uid:S.uid++}));S.cust=h.cust;S.held=S.held.filter(x=>x!==h);S.modal=null;go("sale")}
function complete(){const c=calc(),P=S.pay,paid=paidSum(),chg=r2(Math.max(0,paid-c.total)),b2b=needsB2B();
 const mode=!b2b?"simp":S.online?"b2b":P.b2b==="pending"?"pending":"simp";
 const no=REG.prefix+"-"+pad(S.seq);
 const lines=S.cart.map(l=>{const it=item(l.id),k=lineCalc(l);return{n:it.n,unit:it.units[l.u][0],q:l.q,w:l.w,price:up(it,l.u),total:k.total,promo:k.pr&&k.pr.n,disc:l.disc}});
 const cashPaid=P.lines.filter(l=>l.m==="cash").reduce((a,l)=>a+l.amt,0);
 S.last={no,t:new Date().toLocaleDateString("en-GB")+" "+now(),cust:S.cust,lines,c,pays:P.lines.map(l=>({...l})),chg,mode,cashier:S.cashier?S.cashier.n:CASHIERS[0].n,
  drawer:cashPaid>0,note:b2b&&mode==="simp"?"فاتورة مبسطة — الفاتورة الضريبية الخاصة بـ "+S.cust.n+" تُصدر لاحقاً بإشعار عند عودة الاتصال.":""};
 P.lines.forEach(l=>{S.shift.sales[l.m]=r2((S.shift.sales[l.m]||0)+l.amt-(l.m==="cash"?chg:0))});
 S.shift.inv++;S.seq++;S.pending++;S.cart=[];S.cust=CUSTS[0];
 if(S.newPrices&&!S.priceApplied){S.prices={101:[44,172],102:[19.5],105:[8]};S.priceApplied=true}
 S.print="printing";go("receipt");
 clearTimeout(S._pt);S._pt=setTimeout(()=>{S.print=S.printerOff?"fail":"done";if(S.screen==="receipt")render()},1300)}
function addPay(amt,m){const P=S.pay,c=calc(),rem=r2(Math.max(0,c.total-paidSum()));m=m||P.m;
 if(m!=="cash")amt=Math.min(amt,rem);
 if(m==="credit")amt=Math.min(amt,S.cust.limit-S.cust.used);
 if(!(amt>0)){return}
 const ex=P.lines.find(l=>l.m===m&&m==="cash");if(ex)ex.amt=r2(ex.amt+amt);else P.lines.push({m,amt:r2(amt),ref:P.ref.trim()});
 P.amt="";P.ref="";render()}
const typeNum=(str,k,dec=2)=>{if(k==="del")return str.slice(0,-1);if(k==="clr")return"";if(k==="."){return str.includes(".")?str:(str||"0")+"."}
 if(str.includes(".")&&str.split(".")[1].length>=dec)return str;if(str==="0")return k;return(str+k).slice(0,9)};
const seedCart=()=>{S.cart=[];S.uid=100;[[101,0,2],[106,0,0,.375],[102,0,1],[104,1,1],[103,0,1]].forEach(([id,u,q,w])=>{const l=mkLine(id,u,w||q,!!w);S.cart.push(l)});S.cart[4].disc=10;S.cart[4].appr=SUP.n};

/* ---------- الأوامر ---------- */
const A={
 go:d=>go(d.s),mgo:d=>{const s=d.s;S.modal=null;if(s==="xrep")return openModal({type:"xrep"});if(s==="logout"){S.cashier=null;S.login={sel:null,pin:"",tries:0,err:"",locked:false};return go("login")}
  if(s==="close"){S.close={step:1,counts:{},other:{},blind:S.blind}}go(s)},
 pvopen:()=>{S.pv.open=!S.pv.open;render()},
 pvgo:d=>{const s=d.s;if(["sale","pay","receipt"].includes(s)&&!S.cart.length&&s!=="receipt")seedCart();if(s!=="pair"&&s!=="login"){S.cashier=S.cashier||CASHIERS[0];if(s!=="shift")S.shift.open=true}
  if(s==="receipt"&&!S.last){seedCart();S.pay.lines=[{m:"cash",amt:200,ref:""}];complete();S.print="done";clearTimeout(S._pt)}
  if(s==="pay"){S.pay={m:"cash",lines:[],amt:"",ref:"",b2b:null}}if(s==="close")S.close={step:1,counts:{},other:{},blind:S.blind};
  if(s==="ret")S.ret={code:"",inv:null,qty:{},err:"",method:"cash",done:null};if(s==="login")S.login={sel:null,pin:"",tries:0,err:"",locked:false};if(s==="pair")S.pair={code:"",state:"",prog:0};
  go(s)},
 pvmode:d=>{S.mode=d.v;render()},
 ptab:d=>{S.ptab=d.v;render();scrollTo(0,0)},
 pvt:d=>{const k=d.k;if(k==="online")S.online=!S.online;if(k==="stale")S.stale=!S.stale;if(k==="printerOff")S.printerOff=!S.printerOff;if(k==="stockBlock")S.stockMode=S.stockMode==="block"?"warn":"block";
  if(k==="newPrices"){S.newPrices=!S.newPrices;S.priceApplied=false;S.prices={}}if(k==="blind"){S.blind=!S.blind;S.close.blind=S.blind}if(k==="b2b")S.cust=S.cust.vat?CUSTS[0]:CUSTS[1];
  if(k==="update")S.sync.update=!S.sync.update;if(k==="prev")S.shift.prev=!S.shift.prev;render()},
 toggleNet:()=>{S.online=!S.online;render()},
 install:()=>{if(INSTALL){INSTALL.prompt();INSTALL.userChoice.then(()=>{INSTALL=null;render()})}else toast("افتح الصفحة من المتصفح على https لتظهر نافذة التثبيت","err")},
 /* ربط */
 pairKey:d=>{const p=S.pair;if(d.k==="del")p.code=p.code.slice(0,-1);else if(p.code.length<8)p.code+=d.k;p.state="";render()},
 pairState:d=>{S.pair={code:"",state:d.v,prog:0};render()},
 pairGo:()=>{const p=S.pair;if(p.code==="EXPIRED1"){p.state="expired";p.code="";return render()}if(p.code==="REVOKED1"){p.state="revoked";return render()}
  if(p.code!==PAIR_OK){p.state="wrong";p.code="";return render()}p.state="download";p.prog=0;clearInterval(S._dl);S._dl=setInterval(()=>{if(S.pair.hold)return;S.pair.prog=Math.min(100,S.pair.prog+4);if(S.screen==="pair")render();if(S.pair.prog>=100)clearInterval(S._dl)},180);render()},
 /* دخول */
 pick:d=>{S.login={sel:d.id,pin:"",tries:0,err:"",locked:false};render()},
 pinKey:d=>{const L=S.login;if(L.locked)return;L.err="";if(d.k==="del")L.pin=L.pin.slice(0,-1);else if(d.k==="clr")L.pin="";else if(L.pin.length<4)L.pin+=d.k;render();
  if(L.pin.length===4)setTimeout(()=>{const c=CASHIERS.find(x=>x.id===L.sel);if(L.pin===c.pin){S.cashier=c;S.login.pin="";go(S.shift.open?"sale":"shift")}else{L.tries++;L.pin="";if(L.tries>=5)L.locked=true;else L.err=`الرمز غير صحيح — تبقّى ${5-L.tries} محاولات`;render()}},160)},
 unlockSup:()=>sup("لفتح حساب الكاشير بعد 5 محاولات خاطئة",()=>{S.login.locked=false;S.login.tries=0;S.login.err="";render()}),
 /* وردية */
 floatKey:d=>{S.floatStr=typeNum(S.floatStr,d.k);render()},floatSet:d=>{S.floatStr=d.v;render()},
 openShift:()=>{S.shift.open=true;S.shift.float=+S.floatStr||0;S.shift.openedAt=now();S.shift.sales={cash:0,mada:0,card:0,apple:0,transfer:0,credit:0,voucher:0};S.shift.cashIn=0;S.shift.cashOut=0;S.shift.refCash=0;S.shift.inv=0;go("sale")},
 contPrev:()=>{S.shift.prev=false;S.shift.open=true;go("sale")},closePrev:()=>{S.close={step:1,counts:{},other:{},blind:S.blind};go("close")},
 /* بيع */
 tap:d=>{const it=item(d.id);if(it.w)openModal({type:"weigh",id:it.id,val:"",cb:v=>{addItem(it.id,{w:v});closeModal();render()}});else{addItem(it.id);render()}},
 add:d=>{if(d.close)S.q="";if(item(d.id).w)return A.tap({id:d.id});addItem(+d.id);render()},
 cat:d=>{S.cat=d.c;render()},qclear:()=>{S.q="";render()},msgx:()=>{S.msg=null;renderMsg()},
 qp:d=>{const l=S.cart.find(x=>x.uid==d.u);if(l){const ok=addItem(l.id,{u:l.u,silent:0});if(ok===false)return}render()},
 qm:d=>{const l=S.cart.find(x=>x.uid==d.u);if(!l)return;if(l.q>1)l.q--;else S.cart=S.cart.filter(x=>x!==l);render()},
 del:d=>{S.cart=S.cart.filter(x=>x.uid!=d.u);render()},
 unit:d=>{const l=S.cart.find(x=>x.uid==d.u),it=item(l.id);l.u=(l.u+1)%it.units.length;const ex=S.cart.find(x=>x!==l&&x.id===l.id&&x.u===l.u&&!x.w);if(ex){ex.q+=l.q;S.cart=S.cart.filter(x=>x!==l)}render()},
 qnum:d=>{const l=S.cart.find(x=>x.uid==d.u);openModal({type:"num",title:"الكمية",sub:item(l.id).n,unit:item(l.id).units[l.u][0],val:"",cb:v=>{const q=Math.floor(+v);if(q>=1){l.q=q;l.low=false;const it=item(l.id);if(it.qty!=null&&baseQty(l.id)>it.qty){if(S.stockMode==="block"){l.q=1;msg("err",`الرصيد غير كافٍ — المتاح ${fmq(Math.max(0,it.qty))}.`)}else{l.low=true}}}closeModal();render()}})},
 reweigh:d=>{const l=S.cart.find(x=>x.uid==d.u);openModal({type:"weigh",id:l.id,val:l.q.toFixed(3),cb:v=>{l.q=v;closeModal();render()}})},
 readScale:()=>{S.modal.val="0.420";renderModal()},
 disc:d=>openModal({type:"disc",uid:+d.u,val:""}),
 dchip:d=>{S.modal.val=d.v;renderModal()},
 dclear:()=>{const l=S.cart.find(x=>x.uid===S.modal.uid);l.disc=0;l.appr=null;closeModal();render()},
 custm:()=>openModal({type:"cust"}),
 pickcust:d=>{S.cust=CUSTS.find(c=>c.id==d.id);S.pay.b2b=null;closeModal();render()},
 menu:()=>openModal({type:"menu"}),scanbtn:()=>openModal({type:"scan"}),
 fakeScan:d=>{const wasModal=S.modal;S.modal=null;if(S.screen!=="sale"){S.cashier=S.cashier||CASHIERS[0];S.screen="sale"}scan(d.v)},
 clearcart:()=>{S.cart=[];render()},
 hold:()=>{holdCart();render();msg("ok",`عُلّقت الفاتورة — يمكن استدعاؤها من القائمة ← المعلقة (${S.held.length})`,2800)},
 topay:()=>{if(!S.cart.length)return;if(WEB()&&!S.online){toast("نسخة الويب تحتاج إنترنت لإصدار الفاتورة — السلة محفوظة","err");return}S.pay={m:"cash",lines:[],amt:"",ref:"",b2b:null};go("pay")},
 recall:d=>{if(S.cart.length)openModal({type:"recall",id:d.id});else loadHeld(d.id)},
 recallHold:d=>{holdCart();loadHeld(d.id)},recallReplace:d=>{S.cart=[];loadHeld(d.id)},
 delheld:d=>{S.held=S.held.filter(h=>h.id!==d.id);render()},
 /* نوافذ */
 mclose:(d,e,t)=>{if(d.bg&&e.target!==t)return;closeModal()},
 mnum:d=>{S.modal.val=typeNum(S.modal.val,d.k,S.modal.type==="weigh"?3:2);renderModal()},
 mok:()=>{const m=S.modal;if(m.type==="disc"){const l=S.cart.find(x=>x.uid===m.uid),p=+m.val;if(!(p>0&&p<=100))return;
   if(p>REG.maxDisc){sup(`خصم ${p}% أعلى من حد الكاشير (${REG.maxDisc}%)`,()=>{l.disc=p;l.appr=SUP.n;closeModal();render();toast("سُجّلت موافقة المشرف على السطر")})}else{l.disc=p;l.appr=null;closeModal();render()}return}
  const v=m.type==="weigh"?+m.val:m.val;if(m.cb)m.cb(v)},
 supKey:d=>{const m=S.modal;if(d.k==="del")m.pin=m.pin.slice(0,-1);else if(d.k==="clr")m.pin="";else if(m.pin.length<4)m.pin+=d.k;m.err="";renderModal();
  if(m.pin.length===4)setTimeout(()=>{if(m.pin===SUP.pin){const cb=m.cb;closeModal();cb&&cb()}else{m.pin="";m.err="رمز المشرف غير صحيح";renderModal()}},160)},
 /* دفع */
 method:d=>{S.pay.m=d.m;S.pay.amt="";S.pay.ref="";render()},
 payKey:d=>{S.pay.amt=typeNum(S.pay.amt,d.k);render()},
 quick:d=>{const c=calc(),rem=r2(Math.max(0,c.total-paidSum()));addPay(d.v==="exact"?rem:+d.v,"cash")},
 addpay:()=>{const P=S.pay,c=calc(),rem=r2(Math.max(0,c.total-paidSum()));addPay(P.amt!==""?parseFloat(P.amt)||0:rem)},
 rmpay:d=>{S.pay.lines.splice(+d.i,1);render()},b2b:d=>{S.pay.b2b=d.v;render()},complete:()=>complete(),
 /* إيصال */
 newInv:()=>{S.pay={m:"cash",lines:[],amt:"",ref:"",b2b:null};go("sale")},
 reprint:()=>{S.print="printing";render();setTimeout(()=>{S.print=S.printerOff?"fail":"done";render();if(!S.printerOff)window.print()},900)}, /* في المتصفح: طباعة الإيصال على الطابعة الحرارية من نافذة الطباعة */
 retryPrint:()=>{S.print="printing";render();setTimeout(()=>{S.print=S.printerOff?"fail":"done";render();if(S.printerOff)toast("الطابعة ما زالت غير متصلة","err")},900)},
 wa:()=>toast("أُرسلت نسخة الفاتورة عبر واتساب"),
 /* مرتجع */
 findInv:d=>{const code=(d.v||S.ret.code||"").trim().toUpperCase();S.ret.code=code;const inv=INV_DB[code];if(inv){S.ret.inv=inv;S.ret.qty={};S.ret.err=""}else S.ret.err="nf";render()},
 rlq:d=>{const T=S.ret,l=T.inv.lines[d.i],q=(T.qty[d.i]||0)+ +d.d;if(q>l.q){T.err="over";return render()}T.err="";T.qty[d.i]=Math.max(0,q);render()},
 rlw:d=>{const T=S.ret,l=T.inv.lines[d.i];T.qty[d.i]=T.qty[d.i]?0:l.q;T.err="";render()},
 rmethod:d=>{S.ret.method=d.v;render()},
 doRet:()=>sup("المرتجع يحتاج موافقة المشرف",()=>{const T=S.ret,tot=T.inv.lines.reduce((a,l,i)=>a+(T.qty[i]||0)*l.price,0);T.done=r2(tot);if(T.method==="cash")S.shift.refCash=r2(S.shift.refCash+tot);render()}),
 /* إيداع/سحب */
 cioType:d=>{S.cashio={type:d.v,amt:"",reason:"",err:""};render()},cioKey:d=>{S.cashio.amt=typeNum(S.cashio.amt,d.k);render()},cioReason:d=>{S.cashio.reason=d.v;render()},
 cioSave:()=>{const T=S.cashio,amt=+T.amt,out=T.type==="out",commit=()=>{if(out)S.shift.cashOut=r2(S.shift.cashOut+amt);else S.shift.cashIn=r2(S.shift.cashIn+amt);S.cashio={type:"in",amt:"",reason:"",err:""};go("sale");toast((out?"سُجّل سحب ":"سُجّل إيداع ")+fm(amt)+" ر.س")};
  if(out&&amt>expectedCash())sup("سحب أكبر من النقد المتوقع في الدرج",commit);else commit()},
 /* إقفال */
 dn:d=>{const T=S.close;T.counts[d.d]=Math.max(0,(T.counts[d.d]||0)+ +d.k);render()},
 dnnum:d=>openModal({type:"num",title:"عدد الأوراق",sub:"فئة "+d.d+" ر.س",unit:"",val:"",cb:v=>{S.close.counts[d.d]=Math.floor(+v)||0;closeModal();render()}}),
 otnum:d=>openModal({type:"num",title:"المعدود — "+PN[d.k],sub:"من كشف جهاز الدفع",unit:"ر.س",val:"",cb:v=>{S.close.other[d.k]=+v||0;closeModal();render()}}),
 cstep:d=>{S.close.step=+d.v;render()},xrep:()=>openModal({type:"xrep"}),
 printZ:()=>toast("أُرسل تقرير Z للطابعة"),printX:()=>toast("أُرسل تقرير X للطابعة"),
 finishShift:()=>{S.shift.open=false;S.cashier=null;S.login={sel:null,pin:"",tries:0,err:"",locked:false};go("login");toast("أُقفلت الوردية — سيُرفع التقرير مع أول مزامنة")},
 /* مزامنة */
 syncNow:()=>{if(!S.online){toast("لا يوجد اتصال — ستتم المزامنة تلقائياً عند عودته","err");return}S.sync.busy=true;S.sync.done=null;render();
  setTimeout(()=>{S.sync.busy=false;S.pending=0;S.stale=false;S.lastSync=now();S.sync.done="تمت المزامنة: رُفعت الفواتير وحُدّثت الأسعار والمنتجات.";render()},1500)},
 rejSend:()=>toast("أُرسلت الفاتورة لمدير الفرع للمراجعة"),checkUpdate:()=>toast("أنت على آخر إصدار (1.0.3)"),doUpdate:()=>toast("بدأ تنزيل التحديث… سيُحفظ كل شيء"),
 /* إعدادات */
 cfg:d=>{S[d.k]=d.v;render()},testPage:()=>openModal({type:"testpage"}),testDrawer:()=>toast("فُتح درج النقد"),
 /* مطعم */
 rtype:d=>{S.rest.type=d.v;render()},rtable:d=>{S.rest.table=+d.t;render()},
 radd:d=>{const i=+d.i,m=MENU[i],ex=S.rest.cart.find(l=>l.i===i);if(ex)ex.q++;else S.rest.cart.push({i,n:m[0],p:m[2],q:1,mods:[]});render()},
 rmod:d=>{const l=S.rest.cart[+d.i],k=l.mods.indexOf(d.m);if(k<0)l.mods.push(d.m);else l.mods.splice(k,1);render()},
 kitchen:()=>{S.rest.cart=[];render();toast("أُرسل الطلب للمطبخ — طُبعت تذكرة التحضير")}
};

/* ---------- الأحداث ---------- */
document.addEventListener("click",e=>{const t=e.target.closest("[data-a]");if(!t)return;const a=A[t.dataset.a];if(a){if(t.dataset.a!=="mclose"&&t.disabled)return;a(t.dataset,e,t)}});
document.addEventListener("input",e=>{const t=e.target;if(!t.dataset||t.dataset.model===undefined)return;const p=t.dataset.model;
 if(p==="q"){S.q=t.value;const w=$("#pos-sresw");if(w){w.innerHTML=searchRes();if(window.lucide)lucide.createIcons()}const x=$(".pos-x");if(x)x.hidden=!S.q}
 else if(p==="pay.ref")S.pay.ref=t.value;else if(p==="ret.code")S.ret.code=t.value});
let buf="",bufT;
document.addEventListener("keydown",e=>{const t=e.target,inp=t.tagName==="INPUT";
 if(e.key==="Enter"&&inp){if(t.id==="pos-q"&&/^\d{6,}$/.test(t.value.trim())){e.preventDefault();const c=t.value.trim();S.q="";scan(c)}
  else if(t.id==="pos-sc"){const c=t.value.trim();S.modal=null;scan(c);render()}else if(t.id==="pos-rq"){A.findInv({v:t.value})}
  else if(t.id==="pos-scantest"){toast("قرأ الماسح: "+t.value);t.value=""}return}
 if(inp||S.modal||e.ctrlKey||e.metaKey||e.altKey)return;
 if(S.screen==="sale"||S.screen==="rest"){if(/^\d$/.test(e.key)){buf+=e.key;clearTimeout(bufT);bufT=setTimeout(()=>buf="",900)}
  else if(e.key==="Enter"){if(buf.length>=4){scan(buf);S.screen!=="sale"&&0}buf=""}}});

/* ---------- البدء ---------- */
function init(){const q=new URLSearchParams(location.search),h=(location.hash||"").replace("#","")||"pair",f=k=>q.get(k);
 if(f("app")==="android")S.mode="android";if(f("offline"))S.online=false;if(f("stale")){S.stale=true;S.pending=9}if(f("printer")==="off")S.printerOff=true;if(f("stock")==="block")S.stockMode="block";
 if(f("price")){S.newPrices=true}if(f("update"))S.sync.update=true;if(f("blind")==="0"){S.blind=false}
 if(f("sync")==="update")S.sync.update=true;if(f("sync")==="rejected"){}
 if(!["pair","login"].includes(h)){S.cashier=CASHIERS[0];S.shift.open=h!=="shift"}
 if(h==="shift"){S.cashier=CASHIERS[0];S.shift.open=false;if(f("prev"))S.shift.prev=true}
 if(h==="login"){S.login.sel=f("login")?"c1":f("sel")||null;if(f("login")==="wrong"){S.login.tries=2;S.login.err="الرمز غير صحيح — تبقّى 3 محاولات"}if(f("login")==="locked"){S.login.tries=5;S.login.locked=true}}
 if(h==="pair"){const p=f("pair");if(p){S.pair.state=p==="offline"?"":p;if(p==="offline")S.online=false;if(p==="download"){S.pair.prog=62;S.pair.hold=true}if(p==="code")S.pair.code="K7M2"}}
 if(["sale","pay","receipt","held"].includes(h)&&!f("empty"))seedCart();
 if(f("cust"))S.cust=CUSTS.find(c=>c.id==f("cust"))||S.cust;if(f("b2b"))S.cust=CUSTS[1];
 if(f("scale")){S.screen="sale";addItem(106,{w:.375,silent:1})}
 if(f("low")){addItem(103,{silent:1});addItem(103,{silent:1});addItem(103,{silent:1});addItem(103,{silent:1});addItem(103,{silent:1});S.cart.find(l=>l.id===103).low=true}
 if(h==="pay"){S.pay={m:f("method")||"cash",lines:[],amt:f("amt")||"",ref:"",b2b:f("b2bopt")||null};if(f("split")){S.pay.lines=[{m:"cash",amt:50,ref:""},{m:"mada",amt:60,ref:"482913"}]}if(f("done")){S.pay.lines=[{m:"cash",amt:Math.ceil(calc().total/50)*50,ref:""}]}}
 if(h==="receipt"){S.pay.lines=[{m:S.cust.vat?"mada":"cash",amt:S.cust.vat?calc().total:Math.ceil(calc().total/50)*50,ref:""}];complete();clearTimeout(S._pt);S.print=S.printerOff?"fail":(f("printing")?"printing":"done");}
 if(h==="held"){}
 if(h==="sale"&&f("recall")){openModal({type:"recall",id:S.held[0].id})}
 if(h==="ret"){if(f("rt")){S.ret.code=f("rt")==="notfound"?"B1-000077":"A2-000152";A.findInv({});if(f("rt")==="over"){S.ret.qty={0:1};A.rlq({i:0,d:"3"})}if(f("rt")==="found"){S.ret.qty={0:1,3:2}}}}
 if(h==="cashio"){if(f("cash")==="over"){S.cashio={type:"out",amt:"3500",reason:"إيداع بنكي",err:""}}else if(f("cash")==="out"){S.cashio.type="out"}}
 if(h==="close"){S.close={step:+f("step")||1,counts:{500:2,200:4,100:6,50:9,10:9,5:1,1:1,.5:1},other:{mada:3812.4,card:1235,apple:320.5},blind:S.blind};if(f("step")==="1"||!f("step"))S.close.counts={500:2,200:3,100:5,50:6,10:4,5:2,1:3}}
 if(h==="rest"){S.rest.table=2;S.rest.cart=[{i:0,n:MENU[0][0],p:28,q:2,mods:["بدون بصل"]},{i:3,n:MENU[3][0],p:12,q:2,mods:[]}]}
 S.screen=({ret:"ret",return:"ret"}[h])||h;if(!R[S.screen])S.screen="pair";
 if(f("unknown")){S.screen="sale";render();scan("6281000999999");return}
 if(f("sup")){S.screen="sale";S.cart[4]&&(S.cart[4].disc=0,S.cart[4].appr=null);render();openModal({type:"disc",uid:S.cart[4].uid,val:"12"});A.mok();return}
 if(f("disc")){render();openModal({type:"disc",uid:S.cart[0].uid,val:"10"});return}
 if(f("custm")){render();openModal({type:"cust"});return}
 if(f("menu")){render();openModal({type:"menu"});return}
 if(f("xrep")){render();openModal({type:"xrep"});return}
 if(f("weigh")){render();A.tap({id:106});S.modal.val="0.375";renderModal();return}
 if(f("flash")){render();return}
 if(f("pv"))S.pv.open=true;
 render();
 if(h==="sale"&&f("search")){S.q=f("search");render();}
 if(f("msg")==="stock"){S.stockMode="block";addItem(108,{u:0})}
 if(h==="sale"&&f("msg")==="warn"){S.stockMode="warn";addItem(103,{});}
}
init();
})();
