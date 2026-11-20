/* ClickERP — موديول التجارة (v1.75، جديد): المحاسبة التجارية والمخزون ونقاط البيع.
   المرجع المعتمد: docs/modules/general modula/COMMERCE_POS_MASTER_PLAN.md + «موجز التصميم».
   الملف ده: الأساس المشترك لكل شاشات التجارة — الإعدادات والميزات، بيانات العيّنة، شارات الحالة، تنسيق الأرقام،
   معاينة الصلاحيات (رؤية التكلفة · الفرع) والحالات العامة (فارغة · تحميل · لا نتائج).
   الترتيب في صفحات cm*.html: cm.css ← cm-core.js ← باقي ملفات assets/commerce ← clickerp.bundle.js (الباندل بيشغّل CERP_EXT قبل أول رسم). */
(window.CERP_EXT=window.CERP_EXT||[]).push(()=>{
const {X,B,TAG}=window.CXH;
const CM=window.CM={};

/* ---------- نوع المحاسبة والميزات (CommerceSettings + CommerceTemplates) ---------- */
/* الميزات والقوالب في cm-features.js (مصدر واحد للوحة التحكم وSuper Admin) */
CM.FEAT=window.CM_FEAT;CM.TPL=window.CM_TPL;
const CK="cerp.commerce.v1";
CM.flagsOf=tpl=>{const t=CM.TPL[tpl];const f={};CM.FEAT.forEach(x=>{const v=t&&t.f?t.f[x[0]]:0;f[x[0]]=x[4]==="soon"?false:v===1});return f};
CM.DEF={acct:null,tpl:"grocery",f:CM.flagsOf("grocery"),costing:"avg",since:"2026-11-02",limits:{branches:3,stores:6,devices:4,users:25}};
CM.cfg=()=>{let c=null;try{c=JSON.parse(localStorage.getItem(CK))}catch(e){}return Object.assign({},CM.DEF,c||{},{f:Object.assign({},CM.DEF.f,(c&&c.f)||{})})};
CM.save=c=>{try{localStorage.setItem(CK,JSON.stringify(c))}catch(e){}};
CM.on=k=>{const c=CM.cfg();return c.acct==="commercial"&&c.f[k]!==false&&!!c.f[k]};

/* ---------- معاينة: رؤية التكلفة (FINANCE_COMMERCE_VIEW_COST) والفرع وحالة الشاشة ---------- */
const PV="cerp.commerce.pv";
CM.pv=()=>{let p=null;try{p=JSON.parse(sessionStorage.getItem(PV))}catch(e){}
 /* روابط المطورين: ?state=empty|loading|noresults ، ?cost=0 ، ?branch=1 تفتح الحالة مباشرة */
 const u=new URLSearchParams(location.search),o={},st=u.get("state");
 if(st)o.st=({noresults:"nores",nores:"nores",empty:"empty",loading:"loading",normal:"normal"})[st]||"normal";
 if(u.get("cost")!==null)o.cost=u.get("cost")==="0"?0:1;
 if(u.get("branch")!==null)o.br=u.get("branch")==="1"?"main":"all";
 return Object.assign({cost:1,br:"all",st:"normal"},p||{},o)};
CM.setPv=(k,v)=>{const p=CM.pv();p[k]=v;try{sessionStorage.setItem(PV,JSON.stringify(p))}catch(e){}};
CM.cost=()=>!!CM.pv().cost;
CM.allBr=()=>CM.pv().br==="all";
CM.state=()=>CM.pv().st;

/* ---------- تنسيق الأرقام (الموجز 3.4) ---------- */
const nf=(n,d)=>Number(n).toLocaleString("en-US",{minimumFractionDigits:d,maximumFractionDigits:d});
/* الكمية حتى 4 خانات من غير أصفار زايدة: 2 مش 2.0000 · 0.375 */
CM.q=(n,u)=>{const v=Number(n),s=(Math.round(v*1e4)/1e4).toLocaleString("en-US",{maximumFractionDigits:4});return `<span class="num cm-q${v<0?" neg":""}">${s}</span>${u?` <small class="cm-u">${u}</small>`:""}`};
/* كمية بوحدتين: «2 كرتون · 24 حبة» */
CM.q2=(n,u,base,bu)=>`${CM.q(n,u)}${base!=null?`<small class="cm-q2"> · ${(Math.round(base*1e4)/1e4).toLocaleString("en-US",{maximumFractionDigits:4})} ${bu}</small>`:""}`;
CM.n=(n,d)=>`<span class="num">${nf(n,d==null?2:d)}</span>`;
CM.m=(n,cls)=>`<span class="cm-mw"><span class="num cm-m${cls?" "+cls:""}${Number(n)<0?" neg":""}">${nf(n,2)}</span> <small class="cur">ر.س</small></span>`;
/* التكلفة: خانتين، والقيمة الكاملة (6 خانات) عند المرور. من غير صلاحية رؤية التكلفة: شرطة */
CM.c=(n,full)=>CM.cost()?`<span class="num cm-cost" title="${(full||Number(n)).toFixed(6)} ر.س">${nf(n,2)}</span>`:`<span class="cm-hid" title="بدون صلاحية رؤية التكلفة">—</span>`;

/* ---------- شارات الحالة: لون ثابت لكل معنى في الموديول كله (الموجز 3.3) ----------
   مسودة = رمادي · بانتظار = كهرماني · منتهي/تم = أخضر · مشكلة = أحمر · ملغى = رمادي مشطوب · جاري/متصل = كحلي */
const K={draft:"cm-s-draft",wait:"cm-s-wait",done:"cm-s-done",bad:"cm-s-bad",void:"cm-s-void",live:"cm-s-live"};
CM.ST={
 draft:["مسودة",K.draft,"file-pen"],approved:["معتمد",K.wait,"badge-check"],posted:["مرحّل",K.done,"book-check"],cancelled:["ملغى",K.void,"x"],
 sent:["مرسل — بانتظار الاستلام",K.wait,"send"],received:["مستلم",K.done,"package-check"],rejected:["مرفوض",K.bad,"x-circle"],
 open:["مفتوحة",K.live,"unlock"],closed:["مقفلة — بانتظار الترحيل",K.wait,"lock"],s_posted:["مرحّلة",K.done,"book-check"],
 unreported:["لم تُبلّغ",K.wait,"clock"],late:["متأخرة",K.bad,"alarm-clock"],reported:["مُبلّغة",K.done,"shield-check"],z_rejected:["مرفوضة من الهيئة",K.bad,"shield-x"],
 accepted:["مقبولة",K.done,"check"],duplicate:["مكررة",K.draft,"copy"],review:["مرفوضة للمراجعة",K.bad,"alert-triangle"],
 pairing:["بانتظار الربط",K.wait,"qr-code"],online:["متصل",K.done,"wifi"],unsynced:["غير متزامن",K.bad,"wifi-off"],revoked:["ملغى",K.void,"ban"],
 active:["نشط",K.done,"check"],stopped:["موقوف",K.void,"pause"],paid:["مدفوعة",K.done,"check"],partial:["جزئي",K.wait,"circle-dashed"],unpaid:["غير مدفوعة",K.wait,"clock"],
 not_delivered:["لم تُسلّم",K.wait,"truck"],delivered:["مسلّمة",K.done,"package-check"],confirmed:["مؤكدة",K.done,"check"],
 scheduled:["مجدول",K.live,"calendar-clock"],expired:["منتهي",K.void,"calendar-x"],matched:["مطابق",K.done,"check-check"],diff:["فرق غير مفسّر",K.bad,"alert-triangle"]};
CM.st=(k,txt)=>{const s=CM.ST[k]||[k,K.draft,"circle"];return `<span class="cm-st ${s[1]}">${X(s[2])}<span>${txt||s[0]}</span></span>`};
/* مسار الحالة كخط خطوات أعلى المستند: flow([["مسودة","أنشأه …"],["معتمد",""],["مرحّل",""]], 1) */
CM.flow=(steps,cur,bad)=>`<div class="cm-flow">${steps.map((s,i)=>`<div class="cm-fs${i<cur?" done":i===cur?(bad?" bad":" cur"):""}"><span class="cm-fd">${i<cur?X("check"):i===cur&&bad?X("x"):`<b class="num">${i+1}</b>`}</span><div><b>${s[0]}</b>${s[1]?`<small>${s[1]}</small>`:""}</div></div>`).join(`<i class="cm-fl"></i>`)}</div>`;

/* ---------- أجزاء مشتركة ---------- */
CM.note=(txt,cls,ic)=>`<div class="cm-note ${cls||""}">${X(ic||(cls==="warn"?"alert-triangle":cls==="bad"?"octagon-alert":cls==="ok"?"check-circle-2":"info"))}<div>${txt}</div></div>`;
/* مستند لا يُعدّل: شريط يقول ليه وإيه المتاح (الموجز 3.2) */
CM.ro=(why,acts)=>`<div class="cm-ro">${X("lock")}<div><b>${why}</b><small>المستند المرحّل لا يُعدّل ولا يُحذف. التصحيح بمستند عكسي.</small></div><div class="cm-ro-a">${acts||""}</div></div>`;
/* معاينة القيد قبل الترحيل (الموجز 3.5) — lines: [[الحساب, الكود, مدين, دائن, مركز التكلفة]] */
CM.jprev=(title,lines,foot)=>{const d=lines.reduce((a,l)=>a+(+l[2]||0),0),c=lines.reduce((a,l)=>a+(+l[3]||0),0),ok=Math.abs(d-c)<0.001;
 return `<div class="cm-jv"><div class="cm-jv-h">${X("book-open")}<b>${title||"معاينة القيد"}</b><span class="cm-st ${ok?K.done:K.bad}">${X(ok?"scale":"alert-triangle")}<span>${ok?"متوازن":"غير متوازن"}</span></span></div>
 <table class="cm-jt"><thead><tr><th>الحساب</th><th>مدين</th><th>دائن</th></tr></thead><tbody>${lines.map(l=>`<tr><td><b>${l[0]}</b><small class="num">${l[1]||""}${l[4]?` · ${l[4]}`:""}</small></td><td>${+l[2]?CM.n(l[2]):""}</td><td>${+l[3]?CM.n(l[3]):""}</td></tr>`).join("")}</tbody>
 <tfoot><tr><td>الإجمالي</td><td>${CM.n(d)}</td><td>${CM.n(c)}</td></tr></tfoot></table>${foot?`<small class="cm-jv-f">${foot}</small>`:""}</div>`};
/* الحالات العامة لكل شاشة (الموجز 3.2) */
CM.empty=(ic,title,txt,acts)=>`<div class="card cm-empty"><span class="cm-empty-i">${X(ic)}</span><h3>${title}</h3><p>${txt}</p><div class="cm-empty-a">${acts||""}</div></div>`;
CM.skel=(rows,cols)=>`<div class="card flush cm-skel" aria-busy="true" aria-label="جاري التحميل">${Array.from({length:rows||6}).map(()=>`<div class="cm-sk-r">${Array.from({length:cols||5}).map((_,j)=>`<i style="flex:${j===1?3:1}"></i>`).join("")}</div>`).join("")}</div>`;
CM.nores=()=>`<div class="card cm-empty sm"><span class="cm-empty-i">${X("search-x")}</span><h3>لا نتائج للبحث أو الفلتر</h3><p>جرّب كلمة أخرى أو امسح الفلاتر لعرض كل السجلات.</p><div class="cm-empty-a">${B("ghost","filter-x","مسح الفلاتر",'data-cm-pv="st:normal"')}</div></div>`;
/* لف محتوى الجدول حسب حالة المعاينة */
CM.view=(normal,empty)=>{const s=CM.state();return s==="empty"?(empty||CM.empty("inbox","لا توجد سجلات بعد","ابدأ بإضافة أول سجل.",""))
 :s==="loading"?CM.skel():s==="nores"?CM.nores():normal};
/* شريط المعاينة: يظهر أعلى كل شاشات التجارة (للمعاينة فقط — مش جزء من الشاشة في النظام) */
CM.pvbar=(opts)=>{const p=CM.pv(),o=Object.assign({cost:1,br:1,st:1},opts||{});
 const seg=(k,list)=>`<div class="cm-pv-seg">${list.map(x=>`<button class="${String(p[k])===String(x[0])?"on":""}" data-cm-pv="${k}:${x[0]}">${x[1]}</button>`).join("")}</div>`;
 return `<div class="cm-pv" data-cm-pvbar=""><span class="cm-pv-t">${X("eye")}معاينة</span>
 ${o.cost?`<label>رؤية التكلفة</label>${seg("cost",[[1,"مسموح"],[0,"بدون صلاحية"]])}`:""}
 ${o.br?`<label>المستخدم</label>${seg("br",[["all","الإدارة العامة"],["main","موظف فرع الرياض"]])}`:""}
 ${o.st?`<label>الحالة</label>${seg("st",[["normal","عادية"],["empty","أول استخدام"],["loading","تحميل"],["nores","لا نتائج"]])}`:""}
 <a class="cm-pv-sa" href="superadmin/commerce.html">${X("shield-check")}<span>الميزات: ${(CM.TPL[CM.cfg().tpl]||{}).n||"مخصص"}</span></a></div>`};
/* فلتر الفرع: للإدارة العامة بس، وموظف الفرع يشوف فرعه بشارة */
CM.brSel=()=>CM.allBr()?`<div class="fb"><label>الفرع</label><select><option>كل الفروع</option>${CM.D.branches.map(b=>`<option>${b[1]}</option>`).join("")}</select></div>`:`<div class="fb"><label>الفرع</label><span class="cm-brlock">${X("lock")}الفرع الرئيسي — الرياض</span></div>`;

/* ---------- بيانات العيّنة المشتركة (أسواق شركة الريادة: بقالة + أعلاف) ---------- */
const D=CM.D={};
D.branches=[["b1","الفرع الرئيسي — الرياض"],["b2","فرع جدة"]];
/* [id, الاسم, الفرع, مركز التكلفة, حساب المخزون, افتراضي, يسمح بالسالب] */
D.stores=[["s1","المستودع الرئيسي","b1","CC-110 المستودعات","1141 مخزون — المستودع الرئيسي",1,0],["s2","مخزن صالة البيع","b1","CC-120 صالة البيع","1142 مخزون — صالة البيع",0,1],["s3","مستودع جدة","b2","CC-210 فرع جدة","1143 مخزون — جدة",1,0]];
/* المنتجات: kind = stock/service/kit/recipe ·
   units: [الوحدة, المعامل, سعر البيع, باركود] أول صف = الأساسية · qty: رصيد كل مخزن بالوحدة الأساسية */
D.items=[
 {id:101,code:"GR-0101",n:"أرز بسمتي 5 كجم",en:"Basmati rice 5kg",cat:"البقالة ← الأرز والحبوب",kind:"stock",ic:"wheat",col:"#c8862a",units:[["حبة",1,42.5,"6281000101015"],["كرتون",4,165,"6281000101022"]],cost:31.2,qty:{s1:240,s2:36,s3:88},re:60,tr:"batch",pos:1,act:1,tax:15},
 {id:102,code:"GR-0102",n:"زيت دوار الشمس 1.5 لتر",en:"Sunflower oil 1.5L",cat:"البقالة ← الزيوت",kind:"stock",ic:"droplet",col:"#d9a520",units:[["حبة",1,18.75,"6281000102012"],["كرتون",12,215,"6281000102029"]],cost:13.8,qty:{s1:480,s2:22,s3:140},re:120,tr:"batch",pos:1,act:1,tax:15},
 {id:103,code:"GR-0103",n:"سكر أبيض 10 كجم",en:"White sugar 10kg",cat:"البقالة ← السكر",kind:"stock",ic:"package",col:"#828d9e",units:[["حبة",1,39,"6281000103019"]],cost:30.5,qty:{s1:12,s2:4,s3:30},re:40,tr:"none",pos:1,act:1,tax:15},
 {id:104,code:"BV-0201",n:"مياه شرب 330 مل",en:"Water 330ml",cat:"المشروبات ← المياه",kind:"stock",ic:"glass-water",col:"#2f7ec7",units:[["حبة",1,1,"6281000201014"],["كرتون",40,27,"6281000201021"]],cost:0.55,qty:{s1:9600,s2:-14,s3:3200},re:2000,tr:"none",pos:1,act:1,tax:15},
 {id:105,code:"DA-0301",n:"حليب طازج كامل الدسم 1 لتر",en:"Fresh milk 1L",cat:"الألبان ← الحليب",kind:"stock",ic:"milk",col:"#5f6c80",units:[["حبة",1,7.5,"6281000301011"],["كرتون",12,84,"6281000301028"]],cost:5.9,qty:{s1:0,s2:46,s3:60},re:48,tr:"batch",pos:1,act:1,tax:15,exp:"2026-11-24"},
 {id:106,code:"DT-0401",n:"تمر سكري",en:"Sukkari dates",cat:"التمور",kind:"stock",ic:"apple",col:"#8a5a2b",units:[["كجم",1,32,"21 + الوزن"]],cost:21.4,qty:{s1:340.5,s2:18.375,s3:120},re:50,tr:"batch",pos:1,act:1,tax:15,w:1},
 {id:107,code:"FD-0501",n:"علف شعير",en:"Barley feed",cat:"الأعلاف ← الشعير",kind:"stock",ic:"wheat",col:"#7ab648",units:[["كجم",1,1.6,""],["كيس 50 كجم",50,76,"6281000501015"],["طن",1000,1480,""]],cost:1.18,qty:{s1:38500,s2:1200,s3:12000},re:10000,tr:"batch",pos:1,act:1,tax:15},
 {id:108,code:"FD-0502",n:"علف ذرة مجروشة",en:"Crushed corn feed",cat:"الأعلاف ← الذرة",kind:"stock",ic:"wheat",col:"#b39b2e",units:[["كجم",1,1.9,""],["كيس 40 كجم",40,72,"6281000502012"],["طن",1000,1790,""]],cost:1.42,qty:{s1:6400,s2:0,s3:2200},re:8000,tr:"batch",pos:1,act:1,tax:15},
 {id:109,code:"SV-0901",n:"خدمة توصيل داخل المدينة",en:"City delivery",cat:"الخدمات",kind:"service",ic:"truck",col:"#16233f",units:[["خدمة",1,25,""]],cost:0,qty:{},re:0,tr:"none",pos:1,act:1,tax:15},
 {id:110,code:"KT-0951",n:"سلة رمضان الأساسية",en:"Ramadan basket",cat:"السلال والهدايا",kind:"kit",ic:"gift",col:"#d4453c",units:[["سلة",1,149,"6281000951013"]],cost:112.4,qty:{s1:0,s2:0,s3:0},re:0,tr:"none",pos:1,act:1,tax:15,comp:[[101,"حبة",1],[102,"حبة",1],[103,"حبة",1],[106,"كجم",1]]},
 {id:111,code:"GR-0110",n:"عدس أحمر 1 كجم",en:"Red lentils 1kg",cat:"البقالة ← الأرز والحبوب",kind:"stock",ic:"bean",col:"#b5651d",units:[["حبة",1,8.25,"6281000110017"],["كرتون",10,79,"6281000110024"]],cost:6.1,qty:{s1:0,s2:0,s3:0},re:30,tr:"none",pos:1,act:0,tax:15}];
D.item=id=>D.items.find(x=>x.id===+id)||D.items[0];
D.tot=it=>Object.values(it.qty||{}).reduce((a,b)=>a+b,0);
/* نقاط البيع والأجهزة: [id, الاسم, البادئة, الفرع, المخزن, قائمة الأسعار, الصندوق] */
D.registers=[["r1","كاشير 1 — صالة الرياض","A1","b1","s2","التجزئة","صندوق كاشير 1"],["r2","كاشير 2 — صالة الرياض","A2","b1","s2","التجزئة","صندوق كاشير 2"],["r3","كاشير جدة","B1","b2","s3","التجزئة","صندوق كاشير جدة"]];
/* [id, الاسم, الدور, النقاط, أقصى خصم %, مشرف] */
D.cashiers=[["c1","عبدالرحمن السبيعي","كاشير",["r1","r2"],5,0],["c2","هيفاء الشمري","كاشير",["r1"],5,0],["c3","فيصل القرني","مشرف وردية",["r1","r2"],20,1],["c4","ياسر الغامدي","كاشير",["r3"],5,0],["c5","منى العنزي","مشرف وردية",["r3"],15,1]];
/* طرق الدفع: [id, الاسم, النوع, الحساب, العمولة %] — الترتيب = ترتيب الأزرار في شاشة الدفع */
D.pay=[["cash","نقدي","cash","صندوق نقطة البيع",0],["mada","مدى","mada","1131 وسيط مدى",0.8],["card","فيزا / ماستر","card","1132 وسيط البطاقات",2.2],["apple","Apple Pay","applepay","1131 وسيط مدى",0.8],["credit","آجل","credit","1124 عملاء نقاط البيع",0],["voucher","قسيمة","voucher","2135 قسائم مدفوعة مقدماً",0]];
D.accounts={sales:"4101 مبيعات بضاعة",service:"4105 إيرادات خدمات",cogs:"5101 تكلفة البضاعة المباعة",inv:"1141 مخزون — المستودع الرئيسي",grni:"2121 بضاعة مستلمة لم تُفوتر",variance:"5205 فروقات المخزون",waste:"5206 تالف وهالك",returns:"4111 مردودات المبيعات",discount:"4112 خصم مسموح به",overshort:"5310 عجز وزيادة الصناديق",posar:"1124 عملاء نقاط البيع",fees:"5320 عمولات بنكية",vatOut:"2141 ضريبة القيمة المضافة — المخرجات",vatIn:"1161 ضريبة القيمة المضافة — المدخلات"};


/* ---------- الطباعة (v1.79): نفس قالب الطباعة في المنصة (printov/printsheet + ps-*) ----------
   A4 للفواتير والمستندات، و80 مم للإيصال الحراري وتقرير الوردية. كل زر «طباعة» أو «PDF» في الشاشات دي بيفتح المعاينة،
   و«طباعة» بتطبع المستند بس (من غير القائمة والأزرار). في النظام: نفس قوالب «نماذج الطباعة» (setPrint). */
const nn=(n,d)=>Number(n).toLocaleString("en-US",{minimumFractionDigits:d==null?2:d,maximumFractionDigits:d==null?2:d});
const QR=(seed)=>{let h=0;for(const c of String(seed))h=(h*31+c.charCodeAt(0))>>>0;const n=21,c=[];for(let y=0;y<n;y++)for(let x=0;x<n;x++){const fin=(x<7&&y<7)||(x>13&&y<7)||(x<7&&y>13);let on;if(fin){const a=x>13?x-14:x,b=y>13?y-14:y;on=a===0||a===6||b===0||b===6||(a>1&&a<5&&b>1&&b<5)}else{h=(h*1103515245+12345)>>>0;on=(h>>16)&1}if(on)c.push(`<rect x="${x}" y="${y}" width="1" height="1"/>`)}return `<svg viewBox="0 0 21 21" class="cmp-qr" shape-rendering="crispEdges"><g fill="#16233f">${c.join("")}</g></svg>`};
const BAR=(code)=>{let h=7,x=0,r=[];for(const ch of String(code)){h=(h*33+ch.charCodeAt(0))>>>0;for(let k=0;k<4;k++){const w=1+((h>>(k*3))&1)+((h>>(k*5))&1);if(k%2===0)r.push(`<rect x="${x}" y="0" width="${w}" height="30"/>`);x+=w}}return `<svg viewBox="0 0 ${x} 30" preserveAspectRatio="none" class="cmp-bar"><g fill="#000">${r.join("")}</g></svg>`};
const CO=()=>window.CERP_CO||"شركة الريادة للخدمات";
const HEADP=(title,sub,no,date)=>`<header class="ps-head"><div class="ps-brand"><span class="cmp-logo">${X("store")}</span><div><b>${CO()}</b><small>س.ت 1010456789 · الرقم الضريبي 310456789000003</small><small>الرياض — حي الملقا · 920001234</small></div></div>
 <div class="ps-meta"><h1>${title}</h1><small>${sub||""}</small><div class="ps-nos"><span>الرقم: <b class="num">${no}</b></span><span>التاريخ: <b class="num">${date}</b></span></div></div></header>`;
const FOOTP=`<footer class="ps-foot"><span>${CO()} — طُبع من ClickERP</span><span class="num">${new Date().toISOString().slice(0,16).replace("T"," ")}</span></footer>`;
const TBL=(head,rows,foot)=>`<table class="ps-t"><thead><tr>${head.map(h=>`<th>${h}</th>`).join("")}</tr></thead><tbody>${rows.map(r=>`<tr>${r.map(c=>`<td>${c}</td>`).join("")}</tr>`).join("")}</tbody>${foot?`<tfoot><tr>${foot.map(c=>`<td>${c}</td>`).join("")}</tr></tfoot>`:""}</table>`;
const PARTY=(tag,name,lines)=>`<div class="ps-party"><span class="ps-tag">${tag}</span><b>${name}</b>${lines.map(l=>`<small>${l}</small>`).join("")}</div>`;
const TOT=(rows)=>`<table class="ps-kv cmp-tot">${rows.map((r,i)=>`<tr${i===rows.length-1?' class="big"':""}><th>${r[0]}</th><td class="num">${r[1]}</td></tr>`).join("")}</table>`;
const lines=[[101,"كرتون",10,165],[102,"كرتون",6,215],[107,"كيس 50 كجم",40,76],[109,"خدمة",1,25]];
CM.DOCS={
 sale:()=>{const it=lines.map(l=>{const d=D.item(l[0]),g=l[2]*l[3];return [d.n+`<small class="cm-sub">${d.code}</small>`,`${l[2]} ${l[1]}`,nn(l[3]),nn(g),"15%",nn(g*1.15)]});const sub=lines.reduce((a,l)=>a+l[2]*l[3],0);
  return {t:"فاتورة ضريبية",sz:"a4",h:`${HEADP("فاتورة ضريبية","Tax invoice","SI-2026-0412","2026-11-18")}
  <section class="ps-sec ps-two">${PARTY("البائع",CO(),["الرقم الضريبي 310456789000003","الرياض"])}${PARTY("المشتري","مؤسسة الندى للتموينات",["الرقم الضريبي 300998877600003","الرياض — حي النسيم"])}</section>
  <section class="ps-sec">${TBL(["المنتج","الكمية","سعر الوحدة","المجموع","الضريبة","الإجمالي"],it)}</section>
  <section class="ps-sum"><div class="ps-tot">${TOT([["الإجمالي قبل الضريبة",nn(sub)],["ضريبة القيمة المضافة 15%",nn(sub*.15)],["الإجمالي شامل الضريبة",nn(sub*1.15)+" ر.س"]])}<p class="ps-total">المخزن: المستودع الرئيسي · الدفع: آجل 30 يوماً · المسدد ${nn(1189.36)} ر.س</p></div><div class="ps-qr">${QR("SI-2026-0412")}<small>رمز هيئة الزكاة</small></div></section>
  <section class="ps-sec ps-sign"><div class="ps-two"><div class="ps-sig"><span>المستلم</span><i class="ps-line"></i><small>الاسم والتوقيع</small></div><div class="ps-sig"><span>البائع</span><i class="ps-line"></i><small>الاسم والتوقيع</small></div></div></section>`}},
 purchase:()=>{const L=[[101,"كرتون",30,124.8],[107,"طن",5,1180]];const sub=L.reduce((a,l)=>a+l[2]*l[3],0);
  return {t:"فاتورة شراء",sz:"a4",h:`${HEADP("فاتورة شراء","Purchase invoice","PI-2026-0431","2026-11-19")}
  <section class="ps-sec ps-two">${PARTY("المورد","مؤسسة التوريدات الحديثة",["الرقم الضريبي 300112233400003","فاتورة المورد F-88412"])}${PARTY("الاستلام","المستودع الرئيسي",["استلام كامل","بواسطة أمين المخزن"])}</section>
  <section class="ps-sec">${TBL(["المنتج","الكمية","سعر الوحدة","المجموع"],L.map(l=>{const d=D.item(l[0]);return [d.n,`${l[2]} ${l[1]}`,nn(l[3]),nn(l[2]*l[3])]}))}</section>
  <section class="ps-sum"><div class="ps-tot">${TOT([["قبل الضريبة",nn(sub)],["ضريبة المدخلات 15%",nn(sub*.15)],["الإجمالي",nn(sub*1.15)+" ر.س"]])}</div></section>`}},
 ret:()=>({t:"إشعار دائن — مرتجع",sz:"a4",h:`${HEADP("إشعار دائن","مرتجع مبيعات — مرتبط بالفاتورة SI-2026-0412","RT-2026-0031","2026-11-20")}
  <section class="ps-sec ps-two">${PARTY("العميل","مؤسسة الندى للتموينات",["الرقم الضريبي 300998877600003"])}${PARTY("الفاتورة الأصلية","SI-2026-0412",["2026-11-18"])}</section>
  <section class="ps-sec">${TBL(["المنتج","الكمية المرتجعة","سعر الوحدة","الإجمالي"],[[D.item(101).n,"2 كرتون",nn(165),nn(330)]])}</section>
  <section class="ps-sum"><div class="ps-tot">${TOT([["قبل الضريبة",nn(330)],["الضريبة 15%",nn(49.5)],["إجمالي الإشعار",nn(379.5)+" ر.س"]])}</div><div class="ps-qr">${QR("RT-2026-0031")}<small>رمز هيئة الزكاة</small></div></section>`}),
 stock:()=>({t:"إدخال بضاعة",sz:"a4",h:`${HEADP("إدخال بضاعة إلى المخزن","مستند حركة مخزون — مرحّل","ST-RC-0044","2026-11-19")}
  <section class="ps-sec ps-two">${PARTY("المخزن","المستودع الرئيسي",["الفرع الرئيسي — الرياض"])}${PARTY("السبب","استلام بضاعة بلا فاتورة",["تُربط بفاتورة الشراء عند وصولها"])}</section>
  <section class="ps-sec">${TBL(["المنتج","الكود","الكمية","التشغيلة","ملاحظات"],[[D.item(102).n,D.item(102).code,"24 كرتون","2611-B",""],[D.item(105).n,D.item(105).code,"10 كرتون","2611-A","ينتهي 2026-11-24"],[D.item(108).n,D.item(108).code,"2 طن","",""]])}</section>
  <section class="ps-sec ps-sign"><div class="ps-two"><div class="ps-sig"><span>أمين المخزن</span><i class="ps-line"></i><small>الاسم والتوقيع</small></div><div class="ps-sig"><span>اعتمده</span><i class="ps-line"></i><small>مدير الفرع</small></div></div></section>`}),
 receipt:()=>{const L=[[101,"حبة",2,42.5],[106,"كجم",0.375,32],[102,"حبة",1,18.75],[104,"كرتون",1,27]];const tot=L.reduce((a,l)=>a+l[2]*l[3],0)-8.5;
  return {t:"إيصال الكاشير",sz:"thermal",h:`<div class="cmp-th"><div class="c"><b>${CO()}</b><small>كاشير 2 — صالة الرياض</small><small>الرقم الضريبي 310456789000003</small><b class="tt">فاتورة ضريبية مبسطة</b><small class="num">A2-000153 · 2026-11-20 14:32</small><small>الكاشير: عبدالرحمن السبيعي</small></div>
  <table>${L.map(l=>{const d=D.item(l[0]);return `<tr><td colspan="3">${d.n}</td></tr><tr class="q"><td class="num">${(Math.round(l[2]*1e4)/1e4)} ${l[1]} × ${nn(l[3])}</td><td></td><td class="num">${nn(l[2]*l[3])}</td></tr>`}).join("")}<tr class="q"><td>عرض الأرز — خصم 10%</td><td></td><td class="num">−8.50</td></tr></table>
  <div class="sum"><div><span>الإجمالي</span><b class="num">${nn(tot)}</b></div><div><span>منها ضريبة 15%</span><span class="num">${nn(tot-tot/1.15)}</span></div><div><span>نقدي</span><span class="num">200.00</span></div><div><span>الباقي</span><span class="num">${nn(200-tot)}</span></div></div>
  <div class="c">${QR("A2-000153")}${BAR("A2000153")}<small class="num">A2-000153</small><small>شكراً لزيارتكم — الإرجاع خلال 7 أيام بالإيصال</small></div></div>`}},
 shift:()=>({t:"تقرير Z — إقفال الوردية",sz:"thermal",h:`<div class="cmp-th"><div class="c"><b>${CO()}</b><b class="tt">تقرير Z — إقفال الوردية</b><small class="num">SH-A1-0097 · 2026-11-19</small><small>الكاشير: عبدالرحمن السبيعي · كاشير 1</small></div>
  <table>${[["المبيعات","9,640.25"],["المرتجعات","−210.00"],["الخصومات","−145.50"],["صافي المبيعات","9,284.75"],["عدد الفواتير","31"]].map(r=>`<tr><td>${r[0]}</td><td></td><td class="num">${r[1]}</td></tr>`).join("")}</table>
  <div class="sum">${[["نقدي متوقع","4,380.00"],["نقدي معدود","4,365.00"],["الفرق","−15.00"],["مدى","4,520.75"],["آجل","384.00"]].map(r=>`<div><span>${r[0]}</span><span class="num">${r[1]}</span></div>`).join("")}</div>
  <div class="c"><small>توقيع الكاشير ________</small><small>توقيع المشرف ________</small></div></div>`}),
 labels:()=>({t:"ملصقات الباركود",sz:"a4",h:`${HEADP("ملصقات الباركود","ورقة 3 × 8","LBL-0001","2026-11-20")}<div class="cmp-labels">${D.items.filter(x=>x.kind!=="service").slice(0,9).flatMap(it=>[it,it]).slice(0,24).map(it=>`<div class="cmp-lb"><b>${it.n}</b>${BAR(it.units[0][3]||it.code)}<small class="num">${it.units[0][3]||it.code}</small><b class="num">${nn(it.units[0][2])} ر.س</b></div>`).join("")}</div>`}),
 quote:()=>{const p=document.querySelector(".fq-paper");return {t:"عرض الأسعار",sz:"a4",h:p?`<div class="cmp-fq">${p.outerHTML}</div>`:""}}};
const PAGEDOC={cmSaleShow:"sale",cmSaleAdd:"sale",cmSales:"sale",cmPurchases:"purchase",cmPurchaseAdd:"purchase",cmReturns:"ret",cmReturnAdd:"ret",cmDocShow:"stock",cmDocs:"stock",cmCount:"stock",cmCountRun:"stock",cmPosInvoices:"receipt",cmPosInvoiceShow:"receipt",cmShifts:"shift",cmShiftShow:"shift",cmDash:"receipt",finQuoteShow:"quote",finQuotes:"quote",cmItems:"labels",cmItemAdd:"labels",cmItemCard:"labels"};
CM.print=(kind)=>{const d=(CM.DOCS[kind]||CM.DOCS.sale)();if(!d.h){window.print();return}
 let ov=document.getElementById("cmPrintOv");if(!ov){ov=document.createElement("div");ov.id="cmPrintOv";ov.className="printov cmp-ov";document.body.appendChild(ov)}
 ov.innerHTML=`<div class="printwrap"><div class="printbar"><div class="pb-t">${X("printer")}<div><b>${d.t}</b><small>${d.sz==="thermal"?"طابعة حرارية 80 مم":"ورق A4"} · معاينة قبل الطباعة</small></div></div>
  <div class="pb-a"><button class="btn btn-ghost" data-cmp-close="">${X("x")}إغلاق</button><button class="btn btn-ghost" data-cmp-pdf="">${X("file-down")}PDF</button><button class="btn btn-primary" data-cmp-go="">${X("printer")}طباعة</button></div></div>
  <div class="printscroll"><article class="printsheet${d.sz==="thermal"?" thermal":""}">${d.h}${d.sz==="thermal"?"":FOOTP}</article></div></div>`;
 ov.classList.remove("hidden");document.body.classList.add("printing");if(window.lucide)lucide.createIcons({attrs:{"stroke-width":1.8}})};
if(!window.__cmPrint){window.__cmPrint=1;
 document.addEventListener("click",e=>{
  if(e.target.closest("[data-cmp-close]")){const ov=document.getElementById("cmPrintOv");if(ov)ov.classList.add("hidden");document.body.classList.remove("printing");return}
  if(e.target.closest("[data-cmp-go]")||e.target.closest("[data-cmp-pdf]")){window.print();return}
  const b=e.target.closest("#app .page button, #app .page a.btn");if(!b||b.closest("#cmPrintOv")||b.hasAttribute("data-cm-noprint"))return;
  const tx=(b.textContent||"").trim();if(!/طباعة|PDF|ملصق/.test(tx)||/سجل|إعداد|قالب/.test(tx))return;
  const k=PAGEDOC[window.CERP_PAGE];if(!k)return;e.preventDefault();e.stopImmediatePropagation();CM.print(/ملصق/.test(tx)?"labels":k)},true);
 /* ?print=sale|purchase|ret|stock|receipt|shift|labels|quote — يفتح معاينة الطباعة مباشرة (للروابط والاختبار) */
 const pp=new URLSearchParams(location.search).get("print");if(pp)setTimeout(()=>CM.print(pp),400);
 document.addEventListener("keydown",e=>{if(e.key==="Escape"){const ov=document.getElementById("cmPrintOv");if(ov&&!ov.classList.contains("hidden")){ov.classList.add("hidden");document.body.classList.remove("printing")}}});
}

/* ---------- قواعد التشغيل (v1.83): القرارات اللي كانت مفتوحة في الخطة بقت إعدادات تختارها كل شركة ----------
   تُحفظ مع إعدادات التجارة (CommerceSettings). [المفتاح, العنوان, الشرح, [[القيمة, الاسم, الأثر]], الافتراضي (المقترح في الخطة), المرجع] */
CM.OPS=[
 ["approve","اعتماد قيود المخزون والورديات","القيود الآلية من مستندات المخزون وإقفال الورديات.",[["doc","معتمدة تلقائياً، والاعتماد على المستند","زر «اعتماد وترحيل» على الوردية والجرد هو نقطة الاعتماد الوحيدة، والقيد يظهر في التقارير فوراً."],["entry","تحتاج اعتماد المحاسب","القيد يذهب إلى «قيود بانتظار الاعتماد»، ومبيعات الكاشير لا تظهر في التقارير المالية حتى يعتمدها."]],"doc","D2"],
 ["vat","أسعار البيع","السعر المكتوب على الرف وفي الكاشير وقوائم الأسعار.",[["incl","شاملة الضريبة","السلة والإيصال يعرضان «منها ضريبة 15%». الأنسب للتجزئة."],["excl","غير شاملة الضريبة","الضريبة تُضاف فوق السعر. الأنسب للجملة."]],"incl","13.2"],
 ["blind","العدّ عند إقفال الوردية","هل يرى الكاشير المبلغ المتوقع في الدرج قبل أن يعدّ؟",[["blind","عدّ أعمى","الكاشير يعدّ أولاً ثم يظهر المتوقع والفرق. أدق."],["show","يظهر المتوقع","أسرع، لكن العدّ يتأثر بالرقم المعروض."]],"blind","13.2"],
 ["snap","الكمية المتوقعة في الجرد","المخزون يتحرك أثناء العدّ لأن الكاشير يبيع.",[["start","تُثبّت عند بدء العدّ","المبيعات أثناء العدّ لا تُحسب فرقاً. مناسب للمحلات المفتوحة."],["approve","تُحسب عند الاعتماد","مناسب للمخازن المغلقة أثناء الجرد."]],"start","13.2"],
 ["credit","البيع الآجل فوق حد الائتمان","في الكاشير وفاتورة البيع.",[["block","منع","لا يُكمل البيع حتى يُسدَّد جزء أو يُرفع الحد."],["sup","بموافقة المشرف","رقم المشرف السري، والموافقة تُسجّل على الفاتورة."],["warn","تحذير فقط","يظهر تنبيه ويكمل البيع."]],"sup","13.2"],
 ["b2b","فاتورة ضريبية لعميل منشأة بلا إنترنت","لتطبيق أندرويد (المرحلة 3). نسخة الويب تحتاج اتصالاً دائماً.",[["hold","تُحفظ معلّقة","تُطبع بعد اعتماد الهيئة عند عودة الاتصال."],["simp","تصدر مبسطة","تصدر فاتورة مبسطة فوراً، والضريبية بإشعار لاحق."]],"hold","D6"],
 ["ret","مدة الإرجاع بالإيصال","تظهر في الإيصال وتُتحقق عند المرتجع.",[["7","7 أيام",""],["14","14 يوماً",""],["30","30 يوماً",""],["0","لا إرجاع",""]],"7",""]];
CM.ops=()=>{const c=CM.cfg(),o={};CM.OPS.forEach(x=>o[x[0]]=(c.ops&&c.ops[x[0]])||x[4]);return o};
let OPSDRAFT=null;
window.CM_OPS=()=>{const cur=CM.ops();if(!OPSDRAFT)OPSDRAFT=Object.assign({},cur);const d=OPSDRAFT,chg=CM.OPS.filter(x=>d[x[0]]!==cur[x[0]]);
 return `${CM.note("كانت قرارات مفتوحة في الخطة. الآن كل شركة تختار ما يناسبها، والقيمة الافتراضية هي المقترح في الخطة (معلّمة «موصى به»). التغيير يُسجّل في سجل النشاط ويسري من العملية التالية.","","sliders-horizontal")}
 ${CM.OPS.map(x=>`<div class="card sec cmo-r"><div class="cmo-h"><div><b>${x[1]}</b><small>${x[2]}</small></div>${x[5]?`<span class="cmo-ref">${x[5].startsWith("D")?"قرار "+x[5]:"الموجز §"+x[5]}</span>`:""}</div>
  <div class="cmo-opts">${x[3].map(o=>`<button class="cmo-o${d[x[0]]===o[0]?" on":""}" data-cmo="${x[0]}:${o[0]}"><span class="cmo-dot"></span><span><b>${o[1]}${o[0]===x[4]?' <em>موصى به</em>':""}</b>${o[2]?`<small>${o[2]}</small>`:""}</span></button>`).join("")}</div></div>`).join("")}
 <div class="cvactions">${B("primary","save",chg.length?`حفظ (${chg.length})`:"حفظ","data-cmo-save=\"\""+(chg.length?"":" disabled"))}${chg.length?B("ghost","rotate-ccw","تراجع","data-cmo-reset=\"\""):""}<span class="cmi-muted">${chg.length?chg.map(x=>x[1]).join("، ")+" — لم تُحفظ":"كل القواعد محفوظة"}</span></div>`};
if(!window.__cmOps){window.__cmOps=1;document.addEventListener("click",e=>{let x;
 if((x=e.target.closest("[data-cmo]"))){const[k,v]=x.dataset.cmo.split(":");OPSDRAFT[k]=v;CM.re();return}
 if(e.target.closest("[data-cmo-reset]")){OPSDRAFT=null;CM.re();return}
 if(e.target.closest("[data-cmo-save]")){let c={};try{c=JSON.parse(localStorage.getItem("cerp.commerce.v1"))||{}}catch(_){}c.ops=Object.assign({},OPSDRAFT);CM.save(c);OPSDRAFT=null;CM.re();CM.toast("حُفظت قواعد التشغيل وسُجّلت في سجل النشاط");return}})}
/* ---------- أحداث مشتركة ---------- */
CM.re=()=>{const pg=document.querySelector("#app .page"),id=window.CERP_PAGE;if(!pg||!screens[id])return;const y=scrollY;pg.innerHTML=screens[id]();if(window.lucide)lucide.createIcons({attrs:{"stroke-width":1.8}});scrollTo(0,y)};
CM.toast=(m,ic)=>{let el=document.querySelector(".cm-toast");if(!el){el=document.createElement("div");el.className="cm-toast";document.body.appendChild(el)}el.innerHTML=`${X(ic||"check-circle-2")}<span>${m}</span>`;if(window.lucide)lucide.createIcons({attrs:{"stroke-width":1.8}});el.classList.add("on");clearTimeout(el._t);el._t=setTimeout(()=>el.classList.remove("on"),2600)};
if(!window.__cmCore){window.__cmCore=1;
 document.addEventListener("click",e=>{
  const pv=e.target.closest("[data-cm-pv]");if(pv){e.preventDefault();const[k,v]=pv.dataset.cmPv.split(":");CM.setPv(k,k==="cost"?+v:v);try{const u=new URL(location.href);u.searchParams.delete({st:"state",cost:"cost",br:"branch"}[k]);history.replaceState(null,"",u)}catch(_){}CM.re();return}
  const ts=e.target.closest("[data-cm-toast]");if(ts){e.preventDefault();CM.toast(ts.dataset.cmToast,ts.dataset.cmIc);return}
 });
}
});
