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
CM.DEF={acct:"commercial",tpl:"grocery",f:CM.flagsOf("grocery"),costing:"avg",since:"2026-11-02",limits:{branches:3,stores:6,devices:4,users:25}};
CM.cfg=()=>{let c=null;try{c=JSON.parse(localStorage.getItem(CK))}catch(e){}return Object.assign({},CM.DEF,c||{},{f:Object.assign({},CM.DEF.f,(c&&c.f)||{})})};
CM.save=c=>{try{localStorage.setItem(CK,JSON.stringify(c))}catch(e){}};
CM.on=k=>{const c=CM.cfg();return c.acct==="commercial"&&c.f[k]!==false&&!!c.f[k]};

/* ---------- معاينة: رؤية التكلفة (FINANCE_COMMERCE_VIEW_COST) والفرع وحالة الشاشة ---------- */
const PV="cerp.commerce.pv";
CM.pv=()=>{let p=null;try{p=JSON.parse(sessionStorage.getItem(PV))}catch(e){}return Object.assign({cost:1,br:"all",st:"normal"},p||{})};
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
/* الأصناف: kind = stock/service/kit/recipe ·
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

/* ---------- أحداث مشتركة ---------- */
CM.re=()=>{const pg=document.querySelector("#app .page"),id=window.CERP_PAGE;if(!pg||!screens[id])return;const y=scrollY;pg.innerHTML=screens[id]();if(window.lucide)lucide.createIcons({attrs:{"stroke-width":1.8}});scrollTo(0,y)};
CM.toast=(m,ic)=>{let el=document.querySelector(".cm-toast");if(!el){el=document.createElement("div");el.className="cm-toast";document.body.appendChild(el)}el.innerHTML=`${X(ic||"check-circle-2")}<span>${m}</span>`;if(window.lucide)lucide.createIcons({attrs:{"stroke-width":1.8}});el.classList.add("on");clearTimeout(el._t);el._t=setTimeout(()=>el.classList.remove("on"),2600)};
if(!window.__cmCore){window.__cmCore=1;
 document.addEventListener("click",e=>{
  const pv=e.target.closest("[data-cm-pv]");if(pv){e.preventDefault();const[k,v]=pv.dataset.cmPv.split(":");CM.setPv(k,k==="cost"?+v:v);CM.re();return}
  const ts=e.target.closest("[data-cm-toast]");if(ts){e.preventDefault();CM.toast(ts.dataset.cmToast,ts.dataset.cmIc);return}
 });
}
});
