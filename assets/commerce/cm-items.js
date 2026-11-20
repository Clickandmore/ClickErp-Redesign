/* ClickERP — التجارة ← الأصناف (v1.75، جديد). الموجز §6 (نموذج الصنف) و§8 (الأصناف، التصنيفات، الوحدات، كارت الصنف) — الخطة §4.1، §4.2، §6.2.
   الجداول: fn_items (+ name_en, base_unit_id, tracking, is_weighted, cogs_account_id, is_active, commerce_kind) · fn_item_units · fn_item_barcodes · fn_item_components · fn_categories (+ الحسابات والضريبة والظهور في الكاشير).
   وراثة الحسابات: الصنف ← التصنيف وأبوه ← CommerceAccountSettings. الحساب لازم يكون نهائي (leaf). */
(window.CERP_EXT=window.CERP_EXT||[]).push(()=>{
const {X,FLD,SEL,INP,HEAD,B,TABS,SW,PAGER}=window.CXH;
const CM=window.CM,D=CM.D,S={};
const esc=s=>String(s==null?"":s).replace(/[&<>"]/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;"}[c]));
const KIND={stock:["مخزني","package","cmi-k-stock"],service:["خدمي","hand-helping","cmi-k-svc"],kit:["مركّب","boxes","cmi-k-kit"],recipe:["وصفة","chef-hat","cmi-k-rec"]};
const kindTag=k=>`<span class="cmi-kind ${KIND[k][2]}">${X(KIND[k][1])}${KIND[k][0]}</span>`;
const img=(it,cls)=>`<span class="cm-img ${cls||""}" style="background:${it.col}">${X(it.ic)}</span>`;
const base=it=>it.units[0][0];
const PL=n=>n===1?"صنف واحد":n===2?"صنفان":n<=10?n+" أصناف":n+" صنفاً";
const below=it=>it.kind==="stock"&&D.tot(it)<it.re;
const negOf=it=>Object.values(it.qty||{}).some(v=>v<0);
const nearExp=it=>!!it.exp;
const iTabs=on=>TABS([["cmItems","package","الأصناف",D.items.length],["cmImport","file-spreadsheet","الاستيراد من Excel"],["cmItemCard","history","كارت الصنف"]],on);
const cTabs=on=>TABS([["cmCats","folder-tree","التصنيفات",CATS.length],["cmUnits","ruler","الوحدات",UNITS.length]],on);

/* =========================================================
   الأصناف — القائمة
   ========================================================= */
const L={q:"",kind:"",chip:"all",sel:{}};
const lFilter=()=>D.items.filter(it=>{
 if(L.kind&&it.kind!==L.kind)return false;
 if(L.chip==="below"&&!below(it))return false;
 if(L.chip==="exp"&&!nearExp(it))return false;
 if(L.chip==="neg"&&!negOf(it))return false;
 if(L.chip==="off"&&it.act)return false;
 if(L.q){const q=L.q.trim();if(!(it.n.includes(q)||it.code.toLowerCase().includes(q.toLowerCase())||it.units.some(u=>u[3]===q)))return false}
 return true});
const qtyCell=it=>{if(it.kind==="service")return `<span class="cm-hid">لا يُخزّن</span>`;if(it.kind==="kit")return `<span class="cm-sub">يُحسب من المكونات</span>`;
 const q=CM.allBr()?D.tot(it):(it.qty.s1||0)+(it.qty.s2||0),u=it.units[1];
 return `${CM.q(q,base(it))}${u&&Math.abs(q)>=u[1]?`<small class="cm-q2">≈ ${Math.floor(q/u[1]).toLocaleString("en-US")} ${u[0]}</small>`:""}${negOf(it)?`<span class="cm-flag bad">${X("alert-triangle")}رصيد سالب في مخزن</span>`:below(it)?`<span class="cm-flag">${X("arrow-down")}تحت حد الطلب (${CM.q(it.re)})</span>`:""}`};
S.cmItems=()=>{const list=lFilter(),all=D.items,nSel=Object.values(L.sel).filter(Boolean).length;
 const cnt={all:all.length,below:all.filter(below).length,exp:all.filter(nearExp).length,neg:all.filter(negOf).length,off:all.filter(x=>!x.act).length};
 const chips=[["all","الكل",""],["below","تحت حد الطلب","a"],["exp","قريب الانتهاء","a"],["neg","رصيد سالب","r"],["off","موقوفة","n"]];
 return `${HEAD("الأصناف",`كل ما تبيعه: البضاعة والخدمات والسلال. البيع والشراء والجرد والكاشير تقرأ من هنا.`,B("ghost","file-spreadsheet","استيراد من Excel",'data-go="cmImport"')+B("ghost","download","تصدير")+B("primary","plus","صنف جديد",'data-cmi-new=""'),null)}
 ${CM.pvbar()}
 ${iTabs("cmItems")}
 <div class="cmi-attn">${[["below","arrow-down-to-line","a",`${PL(cnt.below)} تحت حد الطلب`,"اطلب شراءً قبل نفاد الرصيد"],["exp","calendar-clock","a",`${PL(cnt.exp)} ينتهي خلال 7 أيام`,"حليب طازج — تشغيلة 2611-A"],["neg","alert-triangle","r",`${PL(cnt.neg)} برصيد سالب`,"بيع بلا رصيد من الكاشير — يُسوّى مع الاستلام القادم"]].map(a=>`<button class="cmi-attn-i ${a[2]}${L.chip===a[0]?" on":""}" data-cmi-chip="${a[0]}">${X(a[1])}<span><b>${a[3]}</b><small>${a[4]}</small></span>${X("chevron-left")}</button>`).join("")}</div>
 <div class="cx-chips">${chips.map(c=>`<button class="${L.chip===c[0]?"on":""}" data-cmi-chip="${c[0]}">${c[2]?`<i class="d ${c[2]}"></i>`:""}${c[1]}<b class="num">${cnt[c[0]]}</b></button>`).join("")}</div>
 <div class="fbar"><div class="fb grow"><label>بحث أو مسح باركود</label><div class="inp">${X("scan-barcode")}<input data-cmi-q="" value="${esc(L.q)}" placeholder="الاسم أو الكود أو امسح الباركود مباشرة…"></div></div>
  <div class="fb"><label>التصنيف</label>${SEL(["كل التصنيفات",...CATS.filter(c=>!c.p).map(c=>c.n)])}</div>
  <div class="fb"><label>النوع</label><select data-cmi-kind=""><option value="">كل الأنواع</option>${Object.keys(KIND).filter(k=>k!=="recipe"||CM.on("recipes")).map(k=>`<option value="${k}"${L.kind===k?" selected":""}>${KIND[k][0]}</option>`).join("")}</select></div>
  ${CM.brSel()}</div>
 ${nSel?`<div class="cmi-bulk"><b class="num">${nSel}</b><span>محدد</span>${B("ghost sm","printer","طباعة ملصقات",'data-cm-toast="جُهّزت الملصقات للطباعة"')}${B("ghost sm","pause","إيقاف",'data-cm-toast="أوقفت الأصناف المحددة — تبقى في التقارير"')}${B("ghost sm","download","تصدير المحدد")}<button class="cmi-bulk-x" data-cmi-clr="">${X("x")}إلغاء التحديد</button></div>`:""}
 ${CM.view(list.length?`<div class="card flush"><div class="tw"><table class="cmi-tbl"><thead><tr><th class="cmi-ck"><input type="checkbox" data-cmi-all=""></th><th>الصنف</th><th>التصنيف</th><th>سعر البيع</th><th>الرصيد${CM.allBr()?"":" (فرعك)"}</th>${CM.cost()?"<th>التكلفة والقيمة</th>":""}<th>الحالة</th><th class="act"></th></tr></thead><tbody>
  ${list.map(it=>{const q=D.tot(it);return `<tr class="cmi-row${negOf(it)?" cm-row-bad":below(it)?" cm-row-warn":""}" data-cmi-open="${it.id}"><td class="cmi-ck"><input type="checkbox" data-cmi-sel="${it.id}"${L.sel[it.id]?" checked":""}></td>
  <td><div class="cm-icell">${img(it)}<div><b>${it.n}</b><small><span class="cm-mono">${it.code}</span> · ${kindTag(it.kind)}${it.w?` · <span class="cmi-w">${X("scale")}بالوزن</span>`:""}</small></div></div></td>
  <td><small class="cmi-cat">${it.cat.replace(" ← ","<br>← ")}</small></td>
  <td>${CM.m(it.units[0][2])}<small class="cm-sub">لكل ${base(it)}</small>${it.units.slice(1).map(u=>`<small class="cm-sub">${u[0]}: ${CM.n(u[2])}</small>`).join("")}</td>
  <td>${qtyCell(it)}</td>
  ${CM.cost()?`<td>${it.kind==="service"?"—":`${CM.m(Math.max(q,0)*it.cost)}<small class="cm-sub">متوسط ${CM.c(it.cost)}</small>`}</td>`:""}
  <td>${CM.st(it.act?"active":"stopped")}</td>
  <td class="act"><div class="rowacts"><button class="icon-btn pri" title="كارت الصنف" data-cmi-card="${it.id}">${X("history")}</button><button class="icon-btn pri" title="تعديل" data-cmi-open="${it.id}">${X("pencil")}</button></div></td></tr>`}).join("")}
  </tbody></table></div>${PAGER(`عرض ${list.length} من ${D.items.length} صنف`)}</div>`:CM.nores(),
  CM.empty("package-plus","أضف أول صنف","الأصناف هي أساس البيع والشراء والمخزون والكاشير. ابدأ بصنف واحد، أو استورد قائمتك كاملة من ملف Excel.",B("primary","plus","أضف أول صنف",'data-cmi-new=""')+B("ghost","file-spreadsheet","استيراد من Excel",'data-go="cmImport"')))}`};

/* =========================================================
   نموذج الصنف — ستة تبويبات تظهر حسب النوع والميزات المفعّلة
   ========================================================= */
const CATS=[
 {id:1,n:"البقالة",p:0,acc:{sale:"4101 مبيعات بضاعة",cogs:"5101 تكلفة البضاعة المباعة",inv:""},tax:15,pos:1,ic:"shopping-basket",items:42},
 {id:2,n:"الأرز والحبوب",p:1,acc:{sale:"",cogs:"",inv:""},tax:null,pos:1,ic:"wheat",items:12},
 {id:3,n:"الزيوت",p:1,acc:{sale:"",cogs:"",inv:""},tax:null,pos:1,ic:"droplet",items:8},
 {id:4,n:"السكر",p:1,acc:{sale:"",cogs:"",inv:""},tax:null,pos:1,ic:"package",items:3},
 {id:5,n:"المشروبات",p:0,acc:{sale:"4102 مبيعات مشروبات",cogs:"",inv:""},tax:15,pos:1,ic:"cup-soda",items:26},
 {id:6,n:"المياه",p:5,acc:{sale:"",cogs:"",inv:""},tax:null,pos:1,ic:"glass-water",items:6},
 {id:7,n:"الألبان",p:0,acc:{sale:"",cogs:"",inv:""},tax:15,pos:1,ic:"milk",items:14},
 {id:8,n:"الحليب",p:7,acc:{sale:"",cogs:"",inv:""},tax:null,pos:1,ic:"milk",items:5},
 {id:9,n:"التمور",p:0,acc:{sale:"4103 مبيعات تمور",cogs:"",inv:""},tax:15,pos:1,ic:"apple",items:9},
 {id:10,n:"الأعلاف",p:0,acc:{sale:"4104 مبيعات أعلاف",cogs:"5102 تكلفة الأعلاف المباعة",inv:"1144 مخزون الأعلاف"},tax:15,pos:1,ic:"wheat",items:11},
 {id:11,n:"الشعير",p:10,acc:{sale:"",cogs:"",inv:""},tax:null,pos:1,ic:"wheat",items:4},
 {id:12,n:"الذرة",p:10,acc:{sale:"",cogs:"",inv:""},tax:null,pos:1,ic:"wheat",items:3},
 {id:13,n:"الخدمات",p:0,acc:{sale:"4105 إيرادات خدمات",cogs:"",inv:""},tax:15,pos:1,ic:"hand-helping",items:3},
 {id:14,n:"السلال والهدايا",p:0,acc:{sale:"",cogs:"",inv:""},tax:15,pos:1,ic:"gift",items:4},
 {id:15,n:"منتجات جديدة — بلا إعداد",p:0,acc:{sale:"",cogs:"",inv:""},tax:null,pos:0,ic:"circle-help",items:0}];
const catPath=c=>c.p?`${CATS.find(x=>x.id===c.p).n} ← ${c.n}`:c.n;
const catByPath=p=>CATS.find(c=>catPath(c)===p)||CATS[1];
/* أول قيمة موجودة: التصنيف ← أبوه ← الإعداد العام */
const GLOBAL={sale:D.accounts.sales,cogs:D.accounts.cogs,inv:D.accounts.inv};
const inherit=(cat,k,noGlobal)=>{if(cat.acc[k])return [cat.acc[k],`من التصنيف: ${cat.n}`];const p=CATS.find(x=>x.id===cat.p);if(p&&p.acc[k])return [p.acc[k],`من التصنيف الأب: ${p.n}`];return noGlobal||!GLOBAL[k]?["",""]:[GLOBAL[k],"من الإعداد العام للتجارة"]};
const LEAF=["4101 مبيعات بضاعة","4102 مبيعات مشروبات","4103 مبيعات تمور","4104 مبيعات أعلاف","4105 إيرادات خدمات","5101 تكلفة البضاعة المباعة","5102 تكلفة الأعلاف المباعة","1141 مخزون — المستودع الرئيسي","1142 مخزون — صالة البيع","1144 مخزون الأعلاف"];
const PARENTS=["41 الإيرادات (حساب أب)","51 تكلفة المبيعات (حساب أب)","114 المخزون (حساب أب)"];

let F=null;
const blank=()=>({id:0,kind:"stock",n:"",en:"",code:"GR-0112",cat:"البقالة ← الأرز والحبوب",units:[["حبة",1,"",""]],bc:[[""]],w:0,pos:1,act:1,re:"",tr:"none",lists:[],acc:{sale:null,cogs:null,inv:null},tax:null,comp:[],moved:0});
const fromItem=it=>({id:it.id,kind:it.kind,n:it.n,en:it.en,code:it.code,cat:it.cat,units:it.units.map(u=>[u[0],u[1],u[2],u[3]]),
 bc:it.units.map((u,i)=>u[3]&&!u[3].includes("+")?[u[3]].concat(i===0&&it.id===101?["6281000101008"]:[]):[""]),def:{sale:0,pur:it.units.length>1?1:0},
 w:it.w||0,pos:it.pos,act:it.act,re:it.re,tr:it.tr,lists:it.id===101?[["الجملة","كرتون",158,5],["عملاء المطاعم","حبة",40.5,10]]:[],acc:{sale:null,cogs:null,inv:null},tax:null,comp:(it.comp||[]).map(c=>c.slice()),moved:it.id!==111});
const startForm=()=>{let id=0;try{id=+sessionStorage.getItem("cmItem")||0}catch(e){}const qi=+new URLSearchParams(location.search).get("item");if(qi&&!window.__cmiQ){window.__cmiQ=1;id=qi}F=id?fromItem(D.item(id)):blank();if(!F.def)F.def={sale:0,pur:0};F.tab=(location.hash.match(/tab=(\w+)/)||[])[1]||"basic";F.err={};F.done=0;F.dup=0;F.noacc=0;F.key=id};
const TABDEF=[["basic","info","أساسي"],["units","barcode","الوحدات والباركود"],["prices","tags","الأسعار"],["accounts","book-open","الحسابات"],["stock","boxes","المخزون"],["comp","layers","المكونات"]];
const tabsFor=k=>({stock:["basic","units","prices","accounts","stock"],service:["basic","prices","accounts"],kit:["basic","units","prices","accounts","comp"],recipe:["basic","units","prices","accounts","comp"]})[k];
const fCat=()=>catByPath(F.cat);
const accOf=k=>{const ov=F.acc[k];if(ov!=null)return [ov,"مخصص لهذا الصنف",1];const r=F.noacc&&k==="sale"?["",""]:inherit(F.noacc&&k==="sale"?CATS[14]:fCat(),k,F.noacc&&k==="sale");return [r[0],r[1],0]};
const validate=()=>{const e={};
 if(!F.n.trim())e.n="الاسم بالعربية مطلوب";
 if(!F.code.trim())e.code="الكود مطلوب";
 if(F.dup)e.bc="الباركود 6281000102012 مستخدم في صنف آخر";
 if(!accOf("sale")[0])e.sale="لا يوجد حساب إيراد";
 if(F.acc.sale&&PARENTS.includes(F.acc.sale))e.sale="اختر حساباً نهائياً، لا حساباً أب";
 if((F.kind==="kit"||F.kind==="recipe")&&!F.comp.length)e.comp="أضف مكوناً واحداً على الأقل";
 if(F.units.some((u,i)=>i>0&&!(+u[1]>0)))e.units="معامل كل وحدة يجب أن يكون أكبر من صفر";
 return e};
const tabOfErr={n:"basic",code:"basic",bc:"units",units:"units",sale:"accounts",comp:"comp"};
const errTabs=()=>{const s={};Object.keys(F.err).forEach(k=>s[tabOfErr[k]]=1);return s};
const ferr=k=>F.err[k]?`<small class="cmi-err">${X("alert-circle")}${F.err[k]}</small>`:"";
const fIn=(k,v,ph,ltr,cls)=>`<div class="inp${F.err[k]?" cmi-bad":""}"><input data-cmi-f="${k}" value="${esc(v)}" ${ph?`placeholder="${ph}"`:""} ${ltr?'dir="ltr"':""} class="${cls||""}"></div>`;
const tog=(k,on,label,sub,dis)=>`<button class="cmi-tog${on?" on":""}" data-cmi-tog="${k}"${dis?" disabled":""}>${SW(on)}<span><b>${label}</b>${sub?`<small>${sub}</small>`:""}</span></button>`;

const tBasic=()=>`
 <div class="cmi-sec"><div class="cm-sec-h">${X("shapes")}<b>نوع الصنف</b><small>تغييره يغيّر التبويبات فوراً</small></div>
 <div class="cmi-kinds">${Object.keys(KIND).filter(k=>k!=="recipe"||CM.on("recipes")).map(k=>`<button class="cmi-kc${F.kind===k?" on":""}" data-cmi-kind2="${k}"${F.moved&&F.kind!==k?" disabled title=\"لا يتغير النوع بعد أول حركة\"":""}>${X(KIND[k][1])}<b>${KIND[k][0]}</b><small>${{stock:"كيس أعلاف، علبة مشروب",service:"توصيل، تركيب",kit:"سلة من عدة أصناف",recipe:"ساندويتش يخصم الخبز واللحم"}[k]}</small></button>`).join("")}</div>
 ${F.moved?CM.note("للصنف حركات مخزون وفواتير، لذلك النوع والوحدة الأساسية وطريقة التتبع مقفلة. لا يوجد «حذف»، يوجد «إيقاف».","","lock"):""}</div>
 <div class="cmi-sec"><div class="cm-sec-h">${X("type")}<b>البيانات الأساسية</b></div><div class="fgrid">
  ${FLD("الاسم بالعربية",fIn("n",F.n,"مثال: أرز بسمتي 5 كجم")+ferr("n"),1)}
  ${FLD("الاسم بالإنجليزية",fIn("en",F.en,"Basmati rice 5kg",1),0,"يظهر في الإيصال الإنجليزي والتقارير")}
  ${FLD("الكود",fIn("code",F.code,"",1,"cm-mono")+ferr("code"),1,"فريد. يُقترح تلقائياً من بادئة التصنيف")}
  ${FLD("التصنيف",`<select data-cmi-cat="">${CATS.filter(c=>c.p||!CATS.some(x=>x.p===c.id)).map(c=>`<option${catPath(c)===F.cat?" selected":""}>${catPath(c)}</option>`).join("")}</select>`,1,"يجلب الحسابات والضريبة الافتراضية")}
 </div></div>
 <div class="cmi-sec"><div class="cm-sec-h">${X("image")}<b>الصورة والظهور</b></div><div class="cmi-vis">
  <button class="cmi-up">${F.id?img(D.item(F.id),"lg"):`<span class="cmi-up-i">${X("image-plus")}</span>`}<small>${F.id?"تغيير الصورة":"رفع صورة"}<br>تظهر في شبكة الكاشير</small></button>
  <div class="cmi-togs">
   ${F.kind==="stock"&&CM.on("weighted")?tog("w",F.w,"يُباع بالوزن","يقرأ الوزن من باركود الميزان (يبدأ بـ 21)"):""}
   ${CM.on("pos")?tog("pos",F.pos,"يظهر في الكاشير","في شبكة الأصناف وتحت تصنيفه"):""}
   ${tog("act",F.act,"نشط","الموقوف لا يُباع ويبقى في التقارير")}
  </div></div></div>`;

const tUnits=()=>{const multi=CM.on("multi_uom");
 if(!multi)return `<div class="cmi-sec"><div class="cm-sec-h">${X("ruler")}<b>الوحدة والباركود</b><small>ميزة «الوحدات المتعددة» غير مفعّلة</small></div><div class="fgrid">${FLD("الوحدة",SEL(UNITS.map(u=>u[0])),1)}${FLD("الباركود",`<div class="cmi-bcs">${F.bc[0].map(b=>`<span class="cmi-bc">${X("barcode")}<span class="cm-mono">${b||"—"}</span></span>`).join("")}<button class="cmi-add-bc" data-cmi-addbc="0">${X("plus")}باركود</button></div>`+ferr("bc"))}</div></div>`;
 return `<div class="cmi-sec"><div class="cm-sec-h">${X("ruler")}<b>الوحدات</b><small>الصف الأول هو الوحدة الأساسية، وكل الأرصدة تُحفظ بها</small><div class="cm-acts">${B("ghost sm","printer","طباعة ملصق",'data-cm-toast="جُهّز ملصق الباركود"')}</div></div>
 <div class="cmi-ut"><table><thead><tr><th>الوحدة</th><th>المعامل</th><th>سعر البيع</th><th>افتراضية للبيع</th><th>للشراء</th><th>الباركودات</th><th></th></tr></thead><tbody>
 ${F.units.map((u,i)=>`<tr${i===0?' class="base"':""}><td>${i===0?`<span class="cmi-base">${X(F.moved?"lock":"anchor")}${u[0]}<small>أساسية</small></span>`:`<select data-cmi-u="${i}:0">${UNITS.map(x=>`<option${x[0]===u[0]?" selected":""}>${x[0]}</option>`).join("")}</select>`}</td>
  <td>${i===0?`<span class="cmi-lock num">1</span>`:`<div class="inp sm${F.err.units&&!(+u[1]>0)?" cmi-bad":""}"><input class="num" dir="ltr" data-cmi-u="${i}:1" value="${u[1]}"></div><small class="cm-sub">= ${CM.q(u[1]||0,base(F))}</small>`}</td>
  <td><div class="inp sm"><input class="num" dir="ltr" data-cmi-u="${i}:2" value="${u[2]}"></div></td>
  <td class="c"><input type="radio" name="dsale" data-cmi-def="sale:${i}"${F.def.sale===i?" checked":""}></td>
  <td class="c"><input type="radio" name="dpur" data-cmi-def="pur:${i}"${F.def.pur===i?" checked":""}></td>
  <td><div class="cmi-bcs">${(F.bc[i]||[""]).filter((b,j)=>b||j===0).map((b,j)=>`<span class="cmi-bc${F.dup&&i===0&&j===0?" bad":""}${j===0?" pri":""}" title="${j===0?"الباركود الأساسي":""}">${X(j===0?"star":"barcode")}${b?`<span class="cm-mono">${b}</span>`:"بدون باركود"}</span>`).join("")}<button class="cmi-add-bc" data-cmi-addbc="${i}">${X("plus")}</button></div>
   ${F.dup&&i===0?`<small class="cmi-err">${X("alert-circle")}مستخدم في صنف آخر: <a href="#" data-cmi-open="102">زيت دوار الشمس 1.5 لتر (GR-0102)</a></small>`:""}</td>
  <td>${i>0?`<button class="icon-btn" title="حذف الوحدة" data-cmi-urm="${i}">${X("trash-2")}</button>`:""}</td></tr>`).join("")}
 </tbody></table><button class="cmi-addrow" data-cmi-uadd="">${X("plus")}إضافة وحدة</button></div>
 ${ferr("units")}
 ${CM.note(`<b>مثال أعلاف:</b> الأساسية كجم، الكيس = 50، الطن = 1000. البيع بالكيس يخصم 50 كجم من الرصيد. باركود الحبة غير باركود الكرتون.`)}</div>`};

const tPrices=()=>`<div class="cmi-sec"><div class="cm-sec-h">${X("tag")}<b>أسعار الوحدات</b><small>تُعدّل من تبويب الوحدات</small></div>
 <div class="cmi-pchips">${F.units.map(u=>`<div class="cmi-pc"><small>${u[0]}</small><b>${u[2]?CM.m(u[2]):"—"}</b></div>`).join("")}</div></div>
 ${CM.on("price_lists")?`<div class="cmi-sec"><div class="cm-sec-h">${X("tags")}<b>قوائم الأسعار</b><small>السعر الأعلى أولوية يغلب عند التعارض</small><div class="cm-acts">${B("ghost sm","external-link","كل القوائم",'data-go="cmPrices"')}</div></div>
 ${F.lists.length?`<div class="cmi-ut"><table><thead><tr><th>القائمة</th><th>الوحدة</th><th>السعر</th><th>من كمية</th><th></th></tr></thead><tbody>${F.lists.map((l,i)=>`<tr><td><b>${l[0]}</b></td><td>${l[1]}</td><td>${CM.m(l[2])}</td><td>${CM.q(l[3],l[1])}</td><td><button class="icon-btn" data-cmi-lrm="${i}">${X("trash-2")}</button></td></tr>`).join("")}</tbody></table></div>`:`<p class="cmi-muted">لا توجد أسعار خاصة. يُباع بسعر الوحدة في كل القوائم.</p>`}
 <button class="cmi-addrow" data-cmi-ladd="">${X("plus")}إضافة سعر في قائمة</button></div>`:""}
 <div class="cmi-sec"><div class="cm-sec-h">${X("percent")}<b>الضريبة</b></div>
 <div class="cmi-inh"><div><small>الضريبة الحالية</small><b>${F.tax!=null?F.tax:(fCat().tax!=null?fCat().tax:CATS.find(c=>c.id===fCat().p).tax)}% ضريبة القيمة المضافة</b><em>${F.tax!=null?"مخصصة لهذا الصنف":`موروثة ${fCat().tax!=null?"من التصنيف: "+fCat().n:"من التصنيف الأب: "+CATS.find(c=>c.id===fCat().p).n}`}</em></div>
 ${tog("taxov",F.tax!=null,"تخصيص","")}</div>
 ${F.tax!=null?`<div class="fgrid" style="margin-top:12px">${FLD("نسبة الضريبة",`<select data-cmi-tax="">${[["15","15% — أساسية"],["0","0% — نسبة صفرية"],["ex","معفى"]].map(o=>`<option value="${o[0]}"${String(F.tax)===o[0]?" selected":""}>${o[1]}</option>`).join("")}</select>`)}</div>`:""}</div>`;

const accRow=(k,label,req,hint)=>{const a=accOf(k);return `<div class="cmi-acc${F.err[k]?" bad":""}"><div class="cmi-acc-t"><b>${label}${req?' <i class="rq">*</i>':""}</b><small>${hint}</small></div>
 ${F.acc[k]!=null?`<div class="cmi-acc-v"><div class="inp${F.err[k]?" cmi-bad":""}">${X("search")}<select data-cmi-acc="${k}"><option value="">اختر حساباً نهائياً…</option>${LEAF.concat(PARENTS).map(o=>`<option${o===F.acc[k]?" selected":""}${PARENTS.includes(o)?' class="par"':""}>${o}</option>`).join("")}</select></div><em>مخصص لهذا الصنف</em></div>`
 :`<div class="cmi-acc-v">${a[0]?`<span class="cmi-accn">${X("book-open")}<span class="cm-mono">${a[0].split(" ")[0]}</span> ${a[0].split(" ").slice(1).join(" ")}</span><em>${X("corner-down-left")}${a[1]}</em>`:`<span class="cmi-accn none">${X("circle-off")}لا يوجد حساب</span><em class="bad">التصنيف بلا حساب، والإعداد العام فارغ</em>`}</div>`}
 ${tog("acc_"+k,F.acc[k]!=null,"تخصيص","")}${ferr(k)}</div>`};
const tAccounts=()=>`<div class="cmi-sec"><div class="cm-sec-h">${X("book-open")}<b>الحسابات</b><small>ترتيب الوراثة: الصنف ← التصنيف وأبوه ← الإعداد العام. يُقبل الحساب النهائي فقط.</small></div>
 ${F.noacc?CM.note(`التصنيف «منتجات جديدة — بلا إعداد» ليس له حساب إيراد، والإعداد العام للإيراد فارغ. <b>الحفظ ممنوع</b> حتى تختار حساباً هنا أو تضبطه في <a href="#" data-go="cmSettings">إعدادات المخزون والحسابات</a>.`,"bad"):""}
 <div class="cmi-chain">${[["الصنف",F.acc.sale!=null||F.acc.cogs!=null||F.acc.inv!=null],["التصنيف: "+fCat().n,!!(fCat().acc.sale||fCat().acc.cogs||fCat().acc.inv)],["التصنيف الأب",!!fCat().p],["الإعداد العام",1]].map(x=>`<span class="${x[1]?"on":""}">${x[0]}</span>`).join(X("chevron-left"))}</div>
 ${accRow("sale","حساب الإيراد",1,"يُقيد فيه صافي البيع بدون الضريبة")}
 ${F.kind!=="service"?accRow("cogs","حساب التكلفة",0,"تكلفة البضاعة المباعة بالمتوسط المرجح")+accRow("inv","حساب المخزون",0,"افتراضياً حساب المخزن نفسه"):CM.note("الصنف الخدمي لا يُخزّن، فلا حساب مخزون ولا تكلفة له.","","hand-helping")}</div>`;

const tStock=()=>{const it=F.id?D.item(F.id):null;return `<div class="cmi-sec"><div class="cm-sec-h">${X("sliders-horizontal")}<b>إعداد المخزون</b></div><div class="fgrid">
 ${FLD("حد الطلب",`<div class="inp"><input class="num" dir="ltr" data-cmi-f="re" value="${F.re}"><span class="cmi-suf">${base(F)}</span></div>`,0,"تنبيه في الأرصدة ولوحة اليوم عند النزول تحته")}</div>
 <div class="fld"><label>التتبع ${F.moved?`<span class="cmi-lockt">${X("lock")}مقفل بعد أول حركة</span>`:""}</label><div class="cmi-trk">${[["none","لا شيء","الكمية فقط",1],["batch","تشغيلة وصلاحية","رقم تشغيلة وتاريخ انتهاء",CM.on("batches_expiry")],["serial","رقم تسلسلي","IMEI لكل قطعة",CM.on("serials")]].filter(o=>o[3]).map(o=>`<button class="cmi-tk${F.tr===o[0]?" on":""}" data-cmi-tr="${o[0]}"${F.moved&&F.tr!==o[0]?" disabled":""}><b>${o[1]}</b><small>${o[2]}</small></button>`).join("")}</div></div></div>
 <div class="cmi-sec"><div class="cm-sec-h">${X("warehouse")}<b>الرصيد لكل مخزن</b><small>للقراءة · يتغير بالمستندات فقط</small><div class="cm-acts">${it?B("ghost sm","history","كارت الصنف",`data-cmi-card="${it.id}"`):""}</div></div>
 ${it?`<div class="cmi-ut"><table><thead><tr><th>المخزن</th><th>الكمية</th>${CM.cost()?"<th>متوسط التكلفة</th><th>القيمة</th>":""}</tr></thead><tbody>${D.stores.filter(s=>CM.allBr()||s[2]==="b1").map(s=>{const q=it.qty[s[0]]||0;return `<tr><td><b>${s[1]}</b><small class="cm-sub">${D.branches.find(b=>b[0]===s[2])[1]}</small></td><td>${CM.q(q,base(it))}${q<0?`<small class="cm-flag bad">${X("alert-triangle")}سالب — بيع أوفلاين</small>`:""}</td>${CM.cost()?`<td>${CM.c(it.cost)}</td><td>${CM.m(q*it.cost)}</td>`:""}</tr>`}).join("")}</tbody>
 <tfoot><tr><td>الإجمالي</td><td>${CM.q(CM.allBr()?D.tot(it):(it.qty.s1||0)+(it.qty.s2||0),base(it))}</td>${CM.cost()?`<td></td><td>${CM.m((CM.allBr()?D.tot(it):(it.qty.s1||0)+(it.qty.s2||0))*it.cost)}</td>`:""}</tr></tfoot></table></div>`:`<p class="cmi-muted">الصنف الجديد رصيده صفر. يُدخل الرصيد بمستند «رصيد افتتاحي» أو «استلام» أو فاتورة شراء.</p>`}</div>`};

const tComp=()=>{const tot=F.comp.reduce((a,c)=>a+D.item(c[0]).cost*(c[2]||0)*(c[1]===base(D.item(c[0]))?1:(D.item(c[0]).units.find(u=>u[0]===c[1])||[0,1])[1]),0);
 return `<div class="cmi-sec"><div class="cm-sec-h">${X("layers")}<b>${F.kind==="recipe"?"الوصفة":"المكونات"}</b><small>${F.kind==="recipe"?"البيع يخصم الخامات من المخزون":"بيع السلة يخصم كل مكوّن من مخزنه"}</small></div>
 ${F.comp.length?`<div class="cmi-ut"><table><thead><tr><th>الصنف</th><th>الوحدة</th><th>الكمية</th>${CM.cost()?"<th>التكلفة</th>":""}<th></th></tr></thead><tbody>${F.comp.map((c,i)=>{const it=D.item(c[0]);return `<tr><td><div class="cm-icell">${img(it,"sm")}<div><b>${it.n}</b><small class="cm-mono">${it.code}</small></div></div></td><td>${SEL(it.units.map(u=>u[0]))}</td><td><div class="inp sm"><input class="num" dir="ltr" value="${c[2]}"></div></td>${CM.cost()?`<td>${CM.c(it.cost*c[2]*((it.units.find(u=>u[0]===c[1])||[0,1])[1]))}</td>`:""}<td><button class="icon-btn" data-cmi-crm="${i}">${X("trash-2")}</button></td></tr>`}).join("")}</tbody></table></div>`:`<p class="cmi-muted${F.err.comp?" bad":""}">لا توجد مكونات بعد.</p>`}
 ${ferr("comp")}
 <div class="cmi-cadd"><select data-cmi-cadd=""><option value="">+ إضافة مكوّن…</option>${D.items.filter(x=>x.kind==="stock").map(x=>`<option value="${x.id}">${x.n} — ${x.code}</option>`).join("")}</select></div>
 ${CM.cost()?`<div class="cmi-ctot"><span>التكلفة المحسوبة للوحدة</span><b>${CM.m(tot)}</b>${F.units[0][2]?`<small>هامش متوقع ${CM.n((1-tot/(+F.units[0][2]/1.15))*100,1)}% من سعر ${CM.n(F.units[0][2])} شامل الضريبة</small>`:""}</div>`:""}</div>`};

const TABF={basic:tBasic,units:tUnits,prices:tPrices,accounts:tAccounts,stock:tStock,comp:tComp};
S.cmItemAdd=()=>{if(!F)startForm();
 const tabs=tabsFor(F.kind);if(!tabs.includes(F.tab))F.tab="basic";const et=errTabs(),it=F.id?D.item(F.id):null,edit=!!F.id;
 const q=it&&it.kind==="stock"?D.tot(it):null;
 if(F.done)return `${HEAD(F.done===2?"تم الحفظ — صنف جديد":"تم حفظ الصنف","",B("ghost","list","كل الأصناف",'data-go="cmItems"'),["cmItems","الأصناف"])}
  <div class="card cmi-done">${X("check-circle-2")}<div><h3>${esc(F.n)} <span class="cm-mono">${esc(F.code)}</span></h3><p>حُفظ وسُجّل في سجل النشاط. ${CM.on("pos")&&F.pos?"يصل للكاشير مع المزامنة القادمة، ويسري على الفاتورة التالية.":""}</p>
  <div class="cm-acts">${B("primary","plus","صنف جديد",'data-cmi-new=""')}${F.kind==="stock"?B("ghost","file-plus","رصيد افتتاحي له",'data-go="cmDocShow"'):""}${B("ghost","printer","طباعة ملصق",'data-cm-toast="جُهّز ملصق الباركود"')}</div></div></div>`;
 return `${HEAD(edit?`${esc(it.n)}`:"صنف جديد",edit?"عدّل البيانات، وتظهر الحقول حسب النوع والميزات المفعّلة.":"اختر النوع أولاً، ثم املأ التبويبات. الحقول المعلّمة * إلزامية.",B("ghost","","إلغاء",'data-go="cmItems"')+(edit&&F.moved?B("ghost","pause",F.act?"إيقاف الصنف":"تنشيط الصنف",'data-cmi-tog="act"'):edit?B("ghost","trash-2","حذف",'data-cm-toast="حُذف الصنف — لا حركات عليه"'):"")+B("ghost","save","حفظ وجديد",'data-cmi-save="2"')+B("primary","save","حفظ",'data-cmi-save="1"'),["cmItems","الأصناف"])}
 ${CM.pvbar({br:1,st:0})}
 <div class="cmi-states"><span>${X("flask-conical")}جرّب الحالات:</span>
  <button class="${F.dup?"on":""}" data-cmi-case="dup">باركود مستخدم في صنف آخر</button>
  <button class="${F.noacc?"on":""}" data-cmi-case="noacc">تصنيف بلا حساب إيراد</button>
  <button class="${F.moved?"on":""}" data-cmi-case="moved">صنف له حركات</button>
  <a href="superadmin/commerce.html">${X("toggle-right")}ميزة موقوفة: تُضبط من Super Admin، وحقولها تختفي</a></div>
 <div class="card cmi-hero">${it?img(it,"lg"):`<span class="cm-img lg cmi-ph">${X(KIND[F.kind][1])}</span>`}
  <div class="cmi-hero-t"><h2>${esc(F.n)||'<span class="cmi-muted">اسم الصنف</span>'}</h2><div class="cmi-hero-m"><span class="cm-mono">${esc(F.code)||"—"}</span>${kindTag(F.kind)}${CM.st(F.act?"active":"stopped")}${F.w?`<span class="cmi-w">${X("scale")}بالوزن</span>`:""}<small>${esc(F.cat)}</small></div></div>
  ${q!=null?`<div class="cmi-hero-b"><small>الرصيد الإجمالي</small><b>${CM.q(q,base(it))}</b>${CM.cost()?`<small>بقيمة ${CM.m(q*it.cost)}</small>`:""}</div>`:F.kind==="stock"?`<div class="cmi-hero-b"><small>الرصيد الإجمالي</small><b>${CM.q(0,base(F))}</b><small>يُدخل بمستند افتتاحي</small></div>`:""}</div>
 <div class="cmi-tabs" role="tablist">${tabs.map(id=>{const d=TABDEF.find(x=>x[0]===id);return `<button role="tab" class="${F.tab===id?"on":""}${et[id]?" err":""}" data-cmi-tab="${id}">${X(d[1])}<span>${d[2]}</span>${et[id]?`<i class="cmi-dot" title="فيه خطأ"></i>`:""}</button>`}).join("")}</div>
 <div class="card cmi-body">${TABF[F.tab]()}</div>
 <div class="cmi-foot">${Object.keys(F.err).length?`<span class="cmi-err">${X("alert-circle")}${Object.keys(F.err).length} أخطاء في: ${Object.keys(et).map(t=>TABDEF.find(x=>x[0]===t)[2]).join("، ")}</span>`:`<span class="cmi-muted">${tabs.length} تبويبات لنوع «${KIND[F.kind][0]}»</span>`}
  <div class="cm-acts">${B("ghost","","إلغاء",'data-go="cmItems"')}${B("ghost","save","حفظ وجديد",'data-cmi-save="2"')}${B("primary","save","حفظ",'data-cmi-save="1"')}</div></div>`};

/* =========================================================
   كارت الصنف — كل حركات الصنف بالترتيب الزمني
   ========================================================= */
const CARD={id:101,store:"all"};
/* [التاريخ, المستند, الرقم, الشاشة, المخزن, وارد, صادر, تكلفة الوحدة] */
const MOVES={101:[["2026-11-01","رصيد افتتاحي","ST-OP-0001","cmDocShow","s1",216,0,30.40],["2026-11-04","فاتورة شراء — مؤسسة التوريدات الحديثة","PI-2026-0412","cmPurchaseAdd","s1",120,0,32.80],["2026-11-05","تحويل إلى مخزن صالة البيع","ST-TR-0031","cmDocShow","s1",0,60,31.20],["2026-11-05","تحويل من المستودع الرئيسي","ST-TR-0031","cmDocShow","s2",60,0,31.20],["2026-11-08","وردية كاشير 1 — A1","SH-A1-0207","cmShiftShow","s2",0,14,31.20],["2026-11-11","فاتورة بيع — مطعم الديرة","SI-2026-0188","cmSaleShow","s1",0,40,31.20],["2026-11-14","جرد شهري — فرق","ST-CN-0009","cmCountRun","s2",0,2,31.20],["2026-11-17","وردية كاشير 2 — A2","SH-A2-0211","cmShiftShow","s2",0,8,31.20],["2026-11-19","فاتورة شراء — شركة الأفق","PI-2026-0431","cmPurchaseAdd","s3",88,0,31.20],["2026-11-20","مرتجع مبيعات — مطعم الديرة","SR-2026-0021","cmReturns","s1",4,0,31.20]]};
S.cmItemCard=()=>{let id=CARD.id;try{id=+sessionStorage.getItem("cmCard")||id}catch(e){}const it=D.item(id);
 const mv=(MOVES[it.id]||MOVES[101]).filter(m=>CARD.store==="all"||m[4]===CARD.store);let bal=0,avg=0;
 const rows=mv.map(m=>{const inq=m[5],out=m[6];if(inq){avg=bal+inq?((bal*avg)+(inq*m[7]))/(bal+inq):m[7];bal+=inq}else bal-=out;return m.concat([bal,avg||m[7]])});
 const tin=mv.reduce((a,m)=>a+m[5],0),tout=mv.reduce((a,m)=>a+m[6],0);
 return `${HEAD("كارت الصنف","كل حركات الصنف بالترتيب الزمني، والرصيد والتكلفة بعد كل حركة. كل سطر يفتح مستنده.",B("ghost","printer","طباعة")+B("ghost","download","تصدير"),["cmItems","الأصناف"])}
 ${CM.pvbar({st:0})}
 ${iTabs("cmItemCard")}
 <div class="fbar"><div class="fb grow"><label>الصنف</label><select data-cmi-cardsel="">${D.items.filter(x=>x.kind==="stock").map(x=>`<option value="${x.id}"${x.id===it.id?" selected":""}>${x.n} — ${x.code}</option>`).join("")}</select></div>
  <div class="fb"><label>المخزن</label><select data-cmi-cardst=""><option value="all">كل المخازن</option>${D.stores.filter(s=>CM.allBr()||s[2]==="b1").map(s=>`<option value="${s[0]}"${CARD.store===s[0]?" selected":""}>${s[1]}</option>`).join("")}</select></div>
  <div class="fb"><label>الفترة</label>${SEL(["نوفمبر 2026","أكتوبر 2026","آخر 90 يوماً","مخصصة…"])}</div></div>
 <div class="card cmi-chero">${img(it,"lg")}<div><h2>${it.n}</h2><small><span class="cm-mono">${it.code}</span> · ${it.cat}</small></div>
  <div class="cmi-csum">${[["الوارد",CM.q(tin,base(it))],["الصادر",CM.q(tout,base(it))],["الرصيد آخر الفترة",CM.q(bal,base(it))]].concat(CM.cost()?[["متوسط التكلفة",CM.c(avg)],["القيمة",CM.m(bal*avg)]]:[]).map(x=>`<div><small>${x[0]}</small><b>${x[1]}</b></div>`).join("")}</div></div>
 <div class="card flush"><div class="tw"><table class="cmi-mv"><thead><tr><th>التاريخ</th><th>المستند</th><th>المخزن</th><th>وارد</th><th>صادر</th><th>الرصيد بعدها</th>${CM.cost()?"<th>التكلفة بعدها</th>":""}</tr></thead><tbody>
 ${rows.map(r=>`<tr><td class="num">${r[0]}</td><td><a href="#" data-go="${r[3]}" class="cmi-doc"><b>${r[1]}</b><small class="cm-mono">${r[2]}</small></a></td><td><small>${D.stores.find(s=>s[0]===r[4])[1]}</small></td><td>${r[5]?`<span class="cmi-in" dir="ltr">+${CM.q(r[5])}</span>`:""}</td><td>${r[6]?`<span class="cmi-out" dir="ltr">−${CM.q(r[6])}</span>`:r[5]?"":`<small class="cm-sub">بلا أثر كمّي</small>`}</td><td>${CM.q(r[8],base(it))}</td>${CM.cost()?`<td>${CM.c(r[9])}</td>`:""}</tr>`).join("")}
 </tbody></table></div></div>
 ${CM.note("الحركة لا تُعدّل ولا تُحذف. التصحيح يظهر هنا كحركة عكسية بمستندها، فيبقى تاريخ الصنف كاملاً.","","shield-check")}`};

/* =========================================================
   الاستيراد من Excel — رفع ← معاينة ← تأكيد
   ========================================================= */
const IMP={step:0};
const IMROWS=[[2,"GR-0120","معكرونة 500 جم","البقالة ← الأرز والحبوب","حبة","3.75","6281000120016",""],[3,"GR-0121","طحين أبيض 10 كجم","البقالة ← الأرز والحبوب","حبة","28","6281000121013",""],[4,"BV-0210","عصير برتقال 1 لتر","المشروبات","حبة","6.5","6281000102012","الباركود مستخدم في «زيت دوار الشمس 1.5 لتر»"],[5,"","شاي أسود 100 كيس","المشروبات","حبة","14.25","6281000211015","الكود فارغ"],[6,"FD-0510","نخالة قمح","الأعلاف ← الشعير","كيس 40 كجم","48","",""],[7,"FD-0511","مكعبات علف","الأعلاف ← أخرى","كيس","59","6281000511014","التصنيف «الأعلاف ← أخرى» غير موجود"],[8,"GR-0122","فول مدمس 400 جم","البقالة ← الأرز والحبوب","حبة","abc","6281000122010","السعر ليس رقماً"],[9,"DA-0310","لبن 2 لتر","الألبان ← الحليب","حبة","9.5","6281000310018",""]];
S.cmImport=()=>{const ok=IMROWS.filter(r=>!r[7]),bad=IMROWS.filter(r=>r[7]);
 const steps=[["رفع الملف","قالب Excel الجاهز"],["معاينة الصفوف","الصحيحة والمرفوضة وسببها"],["تأكيد الاستيراد","الصحيحة فقط"]];
 return `${HEAD("استيراد الأصناف من Excel","ارفع الملف، راجع كل صف وسببه، ثم استورد الصحيحة ونزّل المرفوضة لتصحيحها.",B("ghost","download","تحميل القالب"),["cmItems","الأصناف"])}
 ${iTabs("cmImport")}
 ${CM.flow(steps.map(s=>[s[0],s[1]]),IMP.step===3?3:IMP.step)}
 ${IMP.step===0?`<div class="card cmi-drop" data-cmi-imp="1">${X("file-spreadsheet")}<h3>اسحب ملف Excel هنا أو اضغط للاختيار</h3><p>xlsx أو csv حتى 5,000 صف. الأعمدة: الكود، الاسم، الاسم بالإنجليزية، التصنيف، الوحدة، سعر البيع، الباركود، حد الطلب.</p><span class="cm-acts">${B("primary","upload","اختيار ملف",'data-cmi-imp="1"')}${B("ghost","download","تحميل القالب")}</span></div>`
 :IMP.step===1?`<div class="cm-kpis">${[["file-check","الصفوف في الملف",IMROWS.length,""],["check-circle-2","صحيحة",ok.length,"ok"],["x-circle","مرفوضة",bad.length,"bad"]].map(k=>`<div class="card cm-kpi ${k[3]}"><span>${X(k[0])}${k[1]}</span><b class="num">${k[2]}</b></div>`).join("")}</div>
  <div class="card flush"><div class="tw"><table><thead><tr><th>الصف</th><th>الكود</th><th>الاسم</th><th>التصنيف</th><th>الوحدة</th><th>السعر</th><th>الباركود</th><th>النتيجة</th></tr></thead><tbody>${IMROWS.map(r=>`<tr class="${r[7]?"cm-row-bad":""}"><td class="num">${r[0]}</td><td class="cm-mono">${r[1]||"—"}</td><td><b>${r[2]}</b></td><td><small>${r[3]}</small></td><td>${r[4]}</td><td class="num">${r[5]}</td><td class="cm-mono">${r[6]||"—"}</td><td>${r[7]?`<span class="cmi-err">${X("x-circle")}${r[7]}</span>`:CM.st("accepted","صحيح")}</td></tr>`).join("")}</tbody></table></div></div>
  <div class="cmi-foot"><span class="cmi-muted">${bad.length} صفوف مرفوضة لن تُستورد. نزّلها، صحّحها، وارفعها مرة أخرى.</span><div class="cm-acts">${B("ghost","","رجوع",'data-cmi-imp="0"')}${B("ghost","download","تنزيل المرفوضة ("+bad.length+")")}${B("primary","check","استيراد الصحيحة ("+ok.length+")",'data-cmi-imp="3"')}</div></div>`
 :`<div class="card cmi-done">${X("check-circle-2")}<div><h3>استُورد ${ok.length} أصناف</h3><p>أُضيفت بحالة «نشط» وبالحسابات الموروثة من تصنيفاتها. رصيدها صفر حتى تُدخل مستند رصيد افتتاحي.</p><div class="cm-acts">${B("primary","list","عرض الأصناف",'data-go="cmItems"')}${B("ghost","file-plus","مستند رصيد افتتاحي",'data-go="cmDocShow"')}${B("ghost","download","تنزيل المرفوضة ("+bad.length+")")}</div></div></div>`}`};

/* =========================================================
   التصنيفات (شجرة تُرتّب بالسحب) والوحدات
   ========================================================= */
let CSEL=10;
const UNITS=[["حبة","pc",612],["كرتون","ctn",208],["كجم","kg",64],["كيس 50 كجم","bag50",9],["كيس 40 كجم","bag40",6],["طن","t",14],["لتر","L",22],["سلة","bsk",4],["خدمة","svc",3],["كيس","bag",11]];
S.cmCats=()=>{const c=CATS.find(x=>x.id===CSEL)||CATS[0],kids=p=>CATS.filter(x=>x.p===p);
 const node=x=>`<div class="cmc-n${x.id===CSEL?" on":""}" data-cmc-sel="${x.id}" draggable="true"><span class="cmc-drag" title="اسحب للترتيب">${X("grip-vertical")}</span><span class="cmc-ic">${X(x.ic)}</span><b>${x.n}</b><small class="num">${x.items}</small>${!x.acc.sale&&!(CATS.find(p=>p.id===x.p)||{acc:{}}).acc.sale&&x.id===15?`<span class="cm-flag bad">${X("alert-triangle")}بلا حساب إيراد</span>`:""}${x.pos?"":`<span class="cmc-hid" title="لا يظهر في الكاشير">${X("eye-off")}</span>`}</div>${kids(x.id).length?`<div class="cmc-kids">${kids(x.id).map(node).join("")}</div>`:""}`;
 const inh=k=>{const r=inherit(c,k);return r[0]?`<span class="cmi-accn">${X("book-open")}${r[0]}</span><em>${c.acc[k]?"محدد هنا":r[1]}</em>`:`<span class="cmi-accn none">${X("circle-off")}لا يوجد</span>`};
 return `${HEAD("التصنيفات","شجرة الأصناف. الحسابات والضريبة هنا تُورّث لكل صنف تحتها ما لم يُخصص.",B("ghost","folder-plus","تصنيف فرعي")+B("primary","plus","تصنيف رئيسي"),null)}
 ${CM.pvbar({br:0,st:0})}
 ${cTabs("cmCats")}
 <div class="cmc-lay"><div class="card cmc-tree"><div class="cm-sec-h">${X("folder-tree")}<b>الشجرة</b><small>اسحب لتغيير الترتيب أو الأب</small></div>${CATS.filter(x=>!x.p).map(node).join("")}</div>
 <div class="card cmc-form"><div class="cmc-fh"><span class="cmc-ic lg">${X(c.ic)}</span><div><h3>${c.n}</h3><small>${c.p?"تحت: "+CATS.find(x=>x.id===c.p).n:"تصنيف رئيسي"} · <span class="num">${c.items}</span> صنف</small></div><div class="cm-acts">${B("ghost sm","folder-plus","فرعي")}</div></div>
  <div class="fgrid">${FLD("الاسم",INP("",c.n),1)}${FLD("التصنيف الأب",SEL(["— رئيسي —",...CATS.filter(x=>!x.p&&x.id!==c.id).map(x=>x.n)]))}${FLD("بادئة الكود",INP("","GR-",1),0,"تُقترح لأكواد الأصناف الجديدة")}${FLD("الترتيب",INP("","3",1))}</div>
  <div class="cmi-sec"><div class="cm-sec-h">${X("book-open")}<b>الحسابات الافتراضية</b><small>فارغ = يرث من الأب ثم الإعداد العام</small></div>
  ${[["sale","الإيراد"],["cogs","التكلفة"],["inv","المخزون"]].map(a=>`<div class="cmi-acc"><div class="cmi-acc-t"><b>${a[1]}</b></div><div class="cmi-acc-v">${inh(a[0])}</div>${tog("x",!!c.acc[a[0]],"تحديد هنا","")}</div>`).join("")}</div>
  <div class="cmi-sec"><div class="cm-sec-h">${X("percent")}<b>الضريبة والظهور</b></div><div class="fgrid">${FLD("الضريبة الافتراضية",SEL([c.tax!=null?c.tax+"% — أساسية":"موروثة من الأب","15% — أساسية","0% — نسبة صفرية","معفى"]))}
   ${CM.on("restaurant")?FLD("محطة المطبخ",SEL(["بدون","الشاورما","المشروبات","الحلويات"])):""}</div>
   <div class="cmi-vis"><button class="cmi-up"><span class="cmi-up-i">${X("image-plus")}</span><small>صورة التصنيف<br>في شريط الكاشير</small></button><div class="cmi-togs">${CM.on("pos")?tog("x",c.pos,"يظهر في الكاشير","في شريط التصنيفات بالصور"):""}</div></div></div>
  <div class="cmi-foot"><span></span><div class="cm-acts">${B("ghost","","إلغاء")}${B("primary","save","حفظ التصنيف",'data-cm-toast="حُفظ التصنيف — الأصناف التي ترث منه تتأثر فوراً"')}</div></div></div></div>`};
S.cmUnits=()=>`${HEAD("الوحدات","قائمة الوحدات المستخدمة في الأصناف.",B("primary","plus","وحدة جديدة"),null)}
 ${CM.pvbar({br:0,cost:0})}
 ${cTabs("cmUnits")}
 ${CM.note("معامل التحويل لا يُضبط هنا، بل في كل صنف: الكرتون قد يكون 12 حبة في صنف و40 في آخر.","","info")}
 ${CM.view(`<div class="card flush"><div class="tw"><table><thead><tr><th>الوحدة</th><th>الرمز</th><th>مستخدمة في</th><th class="act"></th></tr></thead><tbody>${UNITS.map(u=>`<tr><td><b>${u[0]}</b></td><td class="cm-mono">${u[1]}</td><td><span class="num">${u[2]}</span> صنف</td><td class="act">${window.CXH.ACTS([["pencil","تعديل"]])}</td></tr>`).join("")}</tbody></table></div></div>`,CM.empty("ruler","لا توجد وحدات","أضف الوحدات التي تبيع وتشتري بها: حبة، كرتون، كجم، كيس.",B("primary","plus","وحدة جديدة")))}`;

Object.assign(T,{cmItems:{ar:"الأصناف",en:"Items"},cmItemAdd:{ar:"نموذج الصنف",en:"Item form"},cmItemCard:{ar:"كارت الصنف",en:"Item card"},cmImport:{ar:"استيراد الأصناف",en:"Import items"},cmCats:{ar:"التصنيفات",en:"Categories"},cmUnits:{ar:"الوحدات",en:"Units"}});
Object.keys(S).forEach(k=>{screens[k]=S[k]});

/* ---------- الأحداث ---------- */
if(!window.__cmItems){window.__cmItems=1;
 const setItem=id=>{try{sessionStorage.setItem("cmItem",String(id||0))}catch(e){}};
 document.addEventListener("click",e=>{const c=s=>e.target.closest(s);
  if(c("[data-cmi-new]")){e.preventDefault();setItem(0);F=null;if(window.CERP_PAGE==="cmItemAdd")CM.re();else location.href="cmItemAdd.html";return}
  const card=c("[data-cmi-card]");if(card){e.preventDefault();e.stopPropagation();try{sessionStorage.setItem("cmCard",card.dataset.cmiCard)}catch(_){}if(window.CERP_PAGE==="cmItemCard")CM.re();else location.href="cmItemCard.html";return}
  const op=c("[data-cmi-open]");if(op&&!c("input")){e.preventDefault();setItem(op.dataset.cmiOpen);F=null;if(window.CERP_PAGE==="cmItemAdd")CM.re();else location.href="cmItemAdd.html";return}
  const ch=c("[data-cmi-chip]");if(ch){L.chip=ch.dataset.cmiChip;CM.re();return}
  if(c("[data-cmi-clr]")){L.sel={};CM.re();return}
  const imp=c("[data-cmi-imp]");if(imp){IMP.step=+imp.dataset.cmiImp;CM.re();return}
  const cs=c("[data-cmc-sel]");if(cs){CSEL=+cs.dataset.cmcSel;CM.re();return}
  if(!F)return;
  const tb=c("[data-cmi-tab]");if(tb){F.tab=tb.dataset.cmiTab;CM.re();return}
  const kd=c("[data-cmi-kind2]");if(kd&&!kd.disabled){F.kind=kd.dataset.cmiKind2;if(F.kind!=="stock")F.w=0;CM.re();return}
  const tg=c("[data-cmi-tog]");if(tg&&!tg.disabled){const k=tg.dataset.cmiTog;
   if(k==="taxov")F.tax=F.tax==null?15:null;else if(k.startsWith("acc_")){const a=k.slice(4);F.acc[a]=F.acc[a]==null?"":null;delete F.err[a]}else if(k!=="x")F[k]=F[k]?0:1;
   if(k==="act"&&window.CERP_PAGE==="cmItemAdd"&&F.id)CM.toast(F.act?"الصنف نشط ويُباع":"أُوقف الصنف: لا يُباع ويبقى في التقارير",F.act?"check-circle-2":"pause");CM.re();return}
  const tr=c("[data-cmi-tr]");if(tr&&!tr.disabled){F.tr=tr.dataset.cmiTr;CM.re();return}
  const cs2=c("[data-cmi-case]");if(cs2){const k=cs2.dataset.cmiCase;if(k==="dup"){F.dup=F.dup?0:1;if(F.dup)F.tab="units"}else if(k==="noacc"){F.noacc=F.noacc?0:1;F.acc.sale=null;if(F.noacc)F.tab="accounts"}else F.moved=F.moved?0:1;F.err=Object.keys(F.err).length?validate():{};CM.re();return}
  if(c("[data-cmi-uadd]")){F.units.push(["كرتون","",""," "]);F.bc.push([""]);CM.re();return}
  const ur=c("[data-cmi-urm]");if(ur){const i=+ur.dataset.cmiUrm;F.units.splice(i,1);F.bc.splice(i,1);CM.re();return}
  const ab=c("[data-cmi-addbc]");if(ab){const i=+ab.dataset.cmiAddbc;F.bc[i]=(F.bc[i]||[]).filter(Boolean).concat(["628"+String(Math.floor(1e9+Math.random()*9e9))]);CM.re();return}
  if(c("[data-cmi-ladd]")){F.lists.push(["التجزئة",F.units[0][0],F.units[0][2]||0,1]);CM.re();return}
  const lr=c("[data-cmi-lrm]");if(lr){F.lists.splice(+lr.dataset.cmiLrm,1);CM.re();return}
  const cr=c("[data-cmi-crm]");if(cr){F.comp.splice(+cr.dataset.cmiCrm,1);CM.re();return}
  const sv=c("[data-cmi-save]");if(sv){F.err=validate();if(Object.keys(F.err).length){const et=errTabs();if(!et[F.tab])F.tab=Object.keys(et)[0];CM.re();CM.toast("الحفظ ممنوع: صحّح الأخطاء المعلّمة","alert-circle");return}
   if(sv.dataset.cmiSave==="2"){const n=F.n;setItem(0);startForm();CM.re();CM.toast(`حُفظ «${n}» — نموذج جديد جاهز`);return}F.done=1;CM.re();return}
 });
 document.addEventListener("input",e=>{const el=e.target;
  if(el.matches("[data-cmi-q]")){L.q=el.value;const p=el.selectionStart;CM.re();const n=document.querySelector("[data-cmi-q]");if(n){n.focus();n.setSelectionRange(p,p)}return}
  if(!F)return;
  if(el.matches("[data-cmi-f]")){F[el.dataset.cmiF]=el.value;const hero=document.querySelector(".cmi-hero-t h2");if(el.dataset.cmiF==="n"&&hero)hero.textContent=el.value||"اسم الصنف";return}
  if(el.matches("[data-cmi-u]")){const[i,j]=el.dataset.cmiU.split(":").map(Number);F.units[i][j]=el.value}});
 document.addEventListener("change",e=>{const el=e.target;
  if(el.matches("[data-cmi-kind]")){L.kind=el.value;CM.re();return}
  if(el.matches("[data-cmi-sel]")){L.sel[el.dataset.cmiSel]=el.checked;CM.re();return}
  if(el.matches("[data-cmi-all]")){lFilter().forEach(x=>L.sel[x.id]=el.checked);CM.re();return}
  if(el.matches("[data-cmi-cardsel]")){try{sessionStorage.setItem("cmCard",el.value)}catch(_){}CM.re();return}
  if(el.matches("[data-cmi-cardst]")){CARD.store=el.value;CM.re();return}
  if(!F)return;
  if(el.matches("[data-cmi-cat]")){F.cat=el.value;F.noacc=0;CM.re();return}
  if(el.matches("[data-cmi-u]")){const[i,j]=el.dataset.cmiU.split(":").map(Number);F.units[i][j]=el.value;CM.re();return}
  if(el.matches("[data-cmi-def]")){const[k,i]=el.dataset.cmiDef.split(":");F.def[k]=+i;return}
  if(el.matches("[data-cmi-acc]")){F.acc[el.dataset.cmiAcc]=el.value;if(Object.keys(F.err).length)F.err=validate();CM.re();return}
  if(el.matches("[data-cmi-tax]")){F.tax=el.value;return}
  if(el.matches("[data-cmi-cadd]")&&el.value){const it=D.item(el.value);F.comp.push([it.id,base(it),1]);delete F.err.comp;CM.re();return}});
 /* السحب في شجرة التصنيفات: معاينة فقط */
 document.addEventListener("dragstart",e=>{const n=e.target.closest&&e.target.closest("[data-cmc-sel]");if(n){n.classList.add("drag");e.dataTransfer.effectAllowed="move"}});
 document.addEventListener("dragend",e=>{const n=e.target.closest&&e.target.closest("[data-cmc-sel]");if(n){n.classList.remove("drag");CM.toast("تغيّر ترتيب التصنيفات")}});
 document.addEventListener("dragover",e=>{if(e.target.closest&&e.target.closest(".cmc-tree"))e.preventDefault()});
}
});
