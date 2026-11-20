/* ClickERP — التجارة · المخزون (v1.75، جديد): أرصدة المخزون · مستندات المخزون · مستند واحد لكل الأنواع · الجرد وشاشة العدّ · المخازن.
   المرجع: COMMERCE_POS_MASTER_PLAN.md §4.3 · §5.3 · §6.2 + موجز التصميم §3 و§8. الحالة تُحفظ في sessionStorage بمفتاح cmk.* لتنتقل بين الصفحات. */
(window.CERP_EXT=window.CERP_EXT||[]).push(()=>{
const {X,SEC,KV,TAG,SW,ROW,FLD,SEL,INP,TXT,STAT,PAGER,ACTS,HEAD,B,TABS,CHIPS,STEPS,FS}=window.CXH;const CM=window.CM;const S={};
const D=CM.D;
const TODAY="2026-11-20",NOW="2026-11-20 10:42",LOCK="2026-10-31";
const ss=(k,d)=>{try{const v=JSON.parse(sessionStorage.getItem(k));return v==null?d:v}catch(e){return d}};
const sv=(k,v)=>{try{sessionStorage.setItem(k,JSON.stringify(v))}catch(e){}};
const esc=s=>String(s==null?"":s).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;");
const fmt=n=>(Math.round(Number(n)*1e4)/1e4).toLocaleString("en-US",{maximumFractionDigits:4});
const days=d=>Math.round((new Date(d)-new Date(TODAY))/864e5);
const store=id=>D.stores.find(s=>s[0]===id)||D.stores[0];
const stockItems=()=>D.items.filter(i=>i.kind==="stock");
const topCat=i=>i.cat.split(" ← ")[0];
const cats=()=>[...new Set(stockItems().map(topCat))];
const unitF=(it,u)=>{const x=it.units.find(y=>y[0]===u);return x?x[1]:1};
/* مخازن معدّلة من شاشة المخازن تنتقل لباقي الصفحات */
{const sst=ss("cmk.stores",null);if(sst){D.stores.length=0;sst.forEach(s=>D.stores.push(s))}}

/* ---------- الأدوار (معاينة الصلاحيات) ---------- */
const ROLES=[["all","مدير النظام"],["keeper","أمين المخزن"],["mgr","مدير الفرع"],["acct","المحاسب"]];
const PERM={edit:["keeper"],approve:["mgr"],post:["acct"],cancel:["mgr"],receive:["keeper"],reverse:["acct"]};
const PERM_N={edit:"أمين المخزن",approve:"مدير الفرع",post:"المحاسب",cancel:"مدير الفرع",receive:"أمين المخزن في المخزن المستلم",reverse:"المحاسب"};
const role=()=>ss("cmk.role","all");
const can=a=>role()==="all"||PERM[a].includes(role());
const roleBar=()=>`<div class="cmk-role"><span>${X("user-cog")}الصلاحية في المعاينة</span><div class="cm-pv-seg">${ROLES.map(r=>`<button class="${role()===r[0]?"on":""}" data-cmk-act="role" data-v="${r[0]}">${r[1]}</button>`).join("")}</div><small>اعتماد المستند وترحيله إجراءان منفصلان وقد يقوم بهما شخصان</small></div>`;
const PB=(cls,ic,txt,act,v,perm,extra)=>{const ok=!perm||can(perm);return `<button class="btn btn-${cls}" data-cmk-act="${act}" ${v!=null?`data-v="${v}"`:""} ${extra||""} ${ok?"":`disabled title="تحتاج صلاحية: ${PERM_N[perm]}"`}>${ic?X(ic):""}${txt}</button>`};

/* ---------- أنواع المستندات ---------- */
const TY={open:{n:"رصيد افتتاحي",p:"OB",ic:"flag",c:1,d:"إدخال أرصدة بداية المدة بتكلفة الوحدة"},receipt:{n:"استلام",p:"GR",ic:"package-plus",c:1,d:"استلام بضاعة في المخزن بلا فاتورة أو قبلها"},transfer:{n:"تحويل بين مخزنين",p:"TR",ic:"arrow-left-right",d:"المرسل ينشئه والمستلم يؤكد الكمية الفعلية"},count:{n:"جرد",p:"CT",ic:"scan-barcode",d:"يُنشأ من شاشة الجرد بعد العدّ"},adjust:{n:"تسوية",p:"AD",ic:"sliders-horizontal",d:"زيادة أو نقص برصيد المنتج مع سبب إجباري"},waste:{n:"إتلاف",p:"WS",ic:"trash-2",d:"إخراج تالف أو منتهي مع سبب وصورة"}};
const TYK=Object.keys(TY);
const REASONS={adjust:["خطأ إدخال سابق","فرق جرد جزئي","زيادة وُجدت في المخزن","نقص مكتشف","أخرى"],waste:["انتهاء الصلاحية","تلف أثناء التخزين","كسر أثناء النقل","تسرّب أو تلوث","أخرى"]};
const BATCH={101:[["B-2509-1","2026-11-14",.15],["B-2510-2","2027-08-01",.85]],102:[["OL-25-77","2027-01-10",.6],["OL-26-12","2027-09-20",.4]],105:[["MK-1118","2026-11-24",.5],["MK-1120","2026-11-30",.5]],106:[["DT-25-9","2027-02-01",1]],107:[["FB-1021","2027-05-30",1]],108:[["FC-0933","2026-12-05",.45],["FC-1004","2027-04-10",.55]]};

/* ============ أرصدة المخزون ============ */
const SK={store:"all",cat:"all",q:"",st:"all"};
const stockRows=()=>{const bt=CM.on("batches_expiry"),rows=[];
 D.stockOverride=D.stockOverride||null;
 stockItems().forEach(it=>{D.stores.forEach(s=>{const q=(it.qty||{})[s[0]];if(q==null)return;
  const bl=bt&&BATCH[it.id]&&q>0?BATCH[it.id]:null;
  if(!bl){rows.push({it,s,q,tot:q,b:bt&&it.tr==="batch"&&q!==0?"—":"",e:null});return}
  let left=q;bl.forEach((b,i)=>{let bq=i===bl.length-1?left:(it.w?Math.round(q*b[2]*1e3)/1e3:Math.round(q*b[2]));left-=bq;rows.push({it,s,q:bq,tot:q,b:b[0],e:b[1]})})})});
 return rows};
const rowFlags=r=>{const f=[];if(r.q<0)f.push("neg");if(r.q===0)f.push("zero");if(r.tot<r.it.re)f.push("low");if(r.e){const d=days(r.e);if(d<0)f.push("expd");else if(d<=30)f.push("exp")}return f};
const eqTxt=(it,q)=>{if(!CM.on("multi_uom")||it.units.length<2||!q)return"";const aq=Math.abs(q);const u=[...it.units].reverse().find(x=>x[1]>1&&x[1]<=aq);if(!u)return"";const w=Math.floor(aq/u[1]),rem=Math.round((aq-w*u[1])*1e4)/1e4;return `أي ${q<0?"−":""}${fmt(w)} ${u[0]}${rem?` و ${fmt(rem)} ${it.units[0][0]}`:""}`};
const filtRows=(skipSt)=>stockRows().filter(r=>(SK.store==="all"||r.s[0]===SK.store)&&(CM.allBr()||r.s[2]==="b1")&&(SK.cat==="all"||topCat(r.it)===SK.cat)&&(!SK.q||(r.it.n+r.it.code+r.it.units.map(u=>u[3]).join(" ")).includes(SK.q))&&(skipSt||SK.st==="all"||rowFlags(r).some(f=>({low:"low",exp:"exp",neg:"neg",zero:"zero"}[SK.st]===f||(SK.st==="exp"&&f==="expd")))));
S.cmStock=()=>{const all=filtRows(1),rows=filtRows(0),cost=CM.cost(),bt=CM.on("batches_expiry");
 const cnt=k=>all.filter(r=>rowFlags(r).some(f=>f===k||(k==="exp"&&f==="expd"))).length;
 const low=cnt("low"),exp=cnt("exp"),neg=cnt("neg"),zero=cnt("zero");
 const val=all.reduce((a,r)=>a+r.q*r.it.cost,0);
 const chip=(k,t,n,d)=>`<button class="${SK.st===k?"on":""}" data-cmk-act="sst" data-v="${k}">${d?`<i class="d ${d}"></i>`:""}${t}<b class="num">${n}</b></button>`;
 const todo=[];if(neg)todo.push(`<b>${neg}</b> رصيد سالب يحتاج مراجعة (بيع أوفلاين أو خطأ إدخال)`);if(exp)todo.push(`<b>${exp}</b> تشغيلة منتهية أو قريبة الانتهاء`);if(low)todo.push(`<b>${low}</b> سطر تحت حد الطلب`);
 const body=`${todo.length?CM.note(`<b>ماذا أفعل الآن؟</b> ${todo.join(" · ")}`,neg?"bad":"warn"):CM.note("الأرصدة كلها ضمن الحدود.","ok")}
 <div class="cm-kpis cmk-kpis">
  <button class="card cm-kpi cmk-k ${SK.st==="all"?"sel":""}" data-cmk-act="sst" data-v="all"><span>${X("boxes")}سطور الأرصدة</span><b class="num">${all.length}</b><small>منتج × مخزن${bt?" × تشغيلة":""}</small></button>
  <button class="card cm-kpi cmk-k warn ${SK.st==="low"?"sel":""}" data-cmk-act="sst" data-v="low"><span>${X("arrow-down-to-line")}تحت حد الطلب</span><b class="num">${low}</b><small>أقل من الحد في المخزن</small></button>
  ${bt?`<button class="card cm-kpi cmk-k warn ${SK.st==="exp"?"sel":""}" data-cmk-act="sst" data-v="exp"><span>${X("calendar-clock")}قريب الانتهاء</span><b class="num">${exp}</b><small>خلال 30 يوماً أو منتهٍ</small></button>`:""}
  <button class="card cm-kpi cmk-k bad ${SK.st==="neg"?"sel":""}" data-cmk-act="sst" data-v="neg"><span>${X("triangle-alert")}رصيد سالب</span><b class="num">${neg}</b><small>لا يُخفى أبداً</small></button>
  ${cost?`<div class="card cm-kpi"><span>${X("wallet")}قيمة المخزون</span><b class="num">${Number(val).toLocaleString("en-US",{maximumFractionDigits:0})}</b><small>ر.س بمتوسط التكلفة</small></div>`:`<div class="card cm-kpi"><span>${X("store")}المخازن</span><b class="num">${new Set(all.map(r=>r.s[0])).size}</b><small>التكلفة مخفية بدون الصلاحية</small></div>`}
 </div>
 <div class="fbar"><div class="fb grow"><label>بحث أو مسح باركود</label><div class="inp">${X("search")}<input data-cmk-chg="sq" value="${esc(SK.q)}" placeholder="اسم المنتج أو كوده أو الباركود…"></div></div>
  ${CM.brSel()}
  <div class="fb"><label>المخزن</label><select data-cmk-chg="ss"><option value="all">كل المخازن</option>${D.stores.filter(s=>CM.allBr()||s[2]==="b1").map(s=>`<option value="${s[0]}" ${SK.store===s[0]?"selected":""}>${s[1]}</option>`).join("")}</select></div>
  <div class="fb"><label>القسم</label><select data-cmk-chg="sc"><option value="all">كل الأقسام</option>${cats().map(c=>`<option ${SK.cat===c?"selected":""}>${c}</option>`).join("")}</select></div>
  ${SK.q||SK.store!=="all"||SK.cat!=="all"||SK.st!=="all"?`<button class="btn btn-ghost" data-cmk-act="sclr">${X("filter-x")}مسح</button>`:""}</div>
 <div class="cx-chips">${chip("all","الكل",all.length)}${chip("low","تحت حد الطلب",low,"a")}${bt?chip("exp","قريب الانتهاء",exp,"a"):""}${chip("neg","سالب",neg,"r")}${chip("zero","صفر",zero,"n")}</div>
 ${rows.length?`<div class="card flush"><div class="tw"><table class="cmk-tbl"><thead><tr><th>المنتج</th><th>المخزن</th>${bt?`<th>التشغيلة · الصلاحية</th>`:""}<th class="e">الكمية</th><th class="e">حد الطلب</th>${cost?`<th class="e">متوسط التكلفة</th><th class="e">القيمة</th>`:""}<th>الحالة</th></tr></thead><tbody>
 ${rows.map(r=>{const f=rowFlags(r),bad=f.includes("neg")||f.includes("expd"),u=r.it.units[0][0];
  const fl=[f.includes("neg")?`<span class="cm-flag bad">${X("triangle-alert")}سالب</span>`:"",f.includes("zero")?`<span class="cm-flag">${X("circle-off")}نفد</span>`:"",f.includes("low")&&!f.includes("zero")?`<span class="cm-flag">${X("arrow-down-to-line")}تحت الحد</span>`:"",f.includes("expd")?`<span class="cm-flag bad">${X("calendar-x")}منتهي</span>`:f.includes("exp")?`<span class="cm-flag">${X("calendar-clock")}${days(r.e)} يوم</span>`:""].filter(Boolean).join("");
  return `<tr class="${bad?"cm-row-bad":f.length?"cm-row-warn":""}"><td><a class="cm-icell" href="#" data-go="cmItemCard" data-cmk-act="item" data-v="${r.it.id}"><span class="cm-img sm" style="background:${r.it.col}">${X(r.it.ic)}</span><div><b>${r.it.n}</b><small class="num">${r.it.code}</small></div></a></td><td>${r.s[1]}</td>${bt?`<td>${r.b&&r.b!=="—"?`<span class="cm-mono">${r.b}</span><small class="cm-sub num">${r.e?`ينتهي ${r.e}`:""}</small>`:`<span class="cm-hid">${r.b||""}</span>`}</td>`:""}<td class="e">${CM.q(r.q,u)}<small class="cmk-eq">${eqTxt(r.it,r.q)}</small></td><td class="e">${r.it.re?CM.q(r.it.re,u):"—"}</td>${cost?`<td class="e">${CM.c(r.it.cost,r.it.cost)}</td><td class="e">${CM.m(r.q*r.it.cost)}</td>`:""}<td><div class="cmk-fl">${fl||`<span class="cm-flag ok">${X("check")}سليم</span>`}</div></td></tr>`}).join("")}
 </tbody>${cost?`<tfoot><tr><td colspan="${bt?6:5}">إجمالي القيمة (للسطور المعروضة)</td><td class="e">${CM.m(rows.reduce((a,r)=>a+r.q*r.it.cost,0))}</td><td></td></tr></tfoot>`:""}</table></div><div class="tfoot"><span>${rows.length} سطر من ${all.length}${cost?"":" · أعمدة التكلفة والقيمة مخفية لأنك بلا صلاحية رؤية التكلفة"}</span></div></div>`:CM.nores()}`;
 return `${HEAD("الكميات في المخزن","الكمية الحالية لكل منتج حسب المخزن"+(CM.on("batches_expiry")?" والتشغيلة وتاريخ الانتهاء":"")+"، والسالب يظهر دائماً بلون تحذير.",B("ghost","file-spreadsheet","تصدير Excel",'data-cm-toast="جارٍ تجهيز ملف أرصدة المخزون" data-cm-ic="download"')+B("primary","package-plus","استلام جديد",'data-go="cmDocShow" data-cmk-act="newdoc" data-v="receipt"'))}
 ${CM.pvbar()}${CM.view(body,CM.empty("boxes","لا أرصدة بعد","تظهر الأرصدة بعد أول مستند: رصيد افتتاحي أو استلام.",B("primary","flag","رصيد افتتاحي",'data-go="cmDocShow" data-cmk-act="newdoc" data-v="open"')+B("ghost","file-spreadsheet","استيراد من Excel",'data-go="cmImport"')))}`};

/* ============ مستندات المخزون ============ */
const L=(i,u,q,o)=>Object.assign({i,u,q,b:"",c:0,n:"",r:null},o||{});
const seedDocs=()=>[
 {id:"d1",type:"open",no:"OB-0001",date:"2026-11-02",store:"s1",reason:"",status:"posted",by:"فيصل الدوسري",appr:"سلمان العتيبي",entry:"JV-2611-0041",saved:1,lines:[L(101,"كرتون",60,{c:124.8}),L(102,"كرتون",40,{c:165.6}),L(107,"طن",38,{c:1180,b:"FB-1021"})],log:[["2026-11-02 09:10","فيصل الدوسري","أنشأ المستند"],["2026-11-02 11:00","سلمان العتيبي","اعتمد"],["2026-11-02 11:40","هدى الحربي","رحّل القيد JV-2611-0041"]]},
 {id:"d2",type:"receipt",no:"GR-0012",date:"2026-11-12",store:"s1",reason:"",status:"posted",by:"فيصل الدوسري",appr:"سلمان العتيبي",entry:"JV-2611-0067",saved:1,lines:[L(103,"حبة",120,{c:30.5}),L(104,"كرتون",80,{c:22})],log:[["2026-11-12 08:30","فيصل الدوسري","أنشأ المستند"],["2026-11-12 10:00","سلمان العتيبي","اعتمد"],["2026-11-12 10:20","هدى الحربي","رحّل القيد JV-2611-0067"]]},
 {id:"d3",type:"receipt",no:"GR-0014",date:"2026-10-29",store:"s1",reason:"",status:"approved",by:"فيصل الدوسري",appr:"سلمان العتيبي",saved:1,lines:[L(108,"طن",6,{c:1420,b:"FC-1004"})],log:[["2026-10-29 09:00","فيصل الدوسري","أنشأ المستند"],["2026-10-30 14:00","سلمان العتيبي","اعتمد"]]},
 {id:"d4",type:"receipt",no:"GR-0015",date:TODAY,store:"s1",reason:"",status:"draft",by:"فيصل الدوسري",saved:1,lines:[L(102,"كرتون",30,{c:165.6,b:"OL-26-12"}),L(105,"كرتون",20,{c:70.8,b:"MK-1120"})],log:[["2026-11-20 08:15","فيصل الدوسري","أنشأ المسودة"]]},
 {id:"d5",type:"transfer",no:"TR-0007",date:"2026-11-19",store:"s1",to:"s2",reason:"",status:"sent",by:"فيصل الدوسري",appr:"سلمان العتيبي",saved:1,lines:[L(101,"كرتون",5),L(104,"كرتون",10),L(106,"كجم",25.5,{b:"DT-25-9"})],log:[["2026-11-19 15:00","فيصل الدوسري","أنشأ التحويل"],["2026-11-19 15:20","سلمان العتيبي","اعتمد وأرسل"]]},
 {id:"d6",type:"transfer",no:"TR-0006",date:"2026-11-10",store:"s1",to:"s3",reason:"",status:"posted",by:"فيصل الدوسري",appr:"سلمان العتيبي",entry:"JV-2611-0058",saved:1,lines:[L(107,"طن",2,{r:2,b:"FB-1021"})],log:[["2026-11-10 09:00","فيصل الدوسري","أنشأ التحويل"],["2026-11-10 09:30","سلمان العتيبي","اعتمد وأرسل"],["2026-11-11 12:00","محمد الشهري","استلم 2 طن"],["2026-11-11 12:30","هدى الحربي","رحّل القيد JV-2611-0058"]]},
 {id:"d7",type:"adjust",no:"AD-0004",date:"2026-11-18",store:"s2",reason:"نقص مكتشف",status:"approved",by:"ليلى القحطاني",appr:"سلمان العتيبي",saved:1,lines:[L(103,"حبة",-2,{n:"كيسان ممزقان"})],log:[["2026-11-18 16:00","ليلى القحطاني","أنشأ المستند"],["2026-11-19 09:00","سلمان العتيبي","اعتمد"]]},
 {id:"d8",type:"waste",no:"WS-0003",date:TODAY,store:"s2",reason:"انتهاء الصلاحية",status:"draft",by:"ليلى القحطاني",photo:"milk-expired.jpg",saved:1,lines:[L(105,"حبة",12,{b:"MK-1118",n:"منتهية اليوم"})],log:[["2026-11-20 09:40","ليلى القحطاني","أنشأ المسودة"]]},
 {id:"d9",type:"count",no:"CT-0002",date:"2026-11-01",store:"s1",reason:"فرق جرد",status:"posted",by:"فيصل الدوسري",appr:"سلمان العتيبي",entry:"JV-2611-0033",saved:1,lines:[L(103,"حبة",-3),L(106,"كجم",1.5)],log:[["2026-11-01 17:00","فيصل الدوسري","أنهى العدّ"],["2026-11-01 18:00","سلمان العتيبي","اعتمد"],["2026-11-02 09:00","هدى الحربي","رحّل القيد JV-2611-0033"]]},
 {id:"d10",type:"waste",no:"WS-0002",date:"2026-11-05",store:"s2",reason:"تلف أثناء التخزين",status:"cancelled",by:"ليلى القحطاني",saved:1,lines:[L(104,"كرتون",1)],log:[["2026-11-05 10:00","ليلى القحطاني","أنشأ المسودة"],["2026-11-05 11:00","سلمان العتيبي","ألغى المستند"]]}];
const docs=()=>{let d=ss("cmk.docs",null);if(!d){d=seedDocs();sv("cmk.docs",d)}return d};
const saveDocs=d=>sv("cmk.docs",d);
const upsert=d=>{const a=docs(),i=a.findIndex(x=>x.id===d.id);if(i>=0)a[i]=d;else a.unshift(d);saveDocs(a)};
const nextNo=t=>{const p=TY[t].p;const n=Math.max(0,...docs().filter(d=>d.type===t&&d.no).map(d=>+d.no.split("-")[1]||0))+1;return p+"-"+String(n).padStart(4,"0")};
const lineBase=l=>l.q*unitF(D.item(l.i),l.u);
const lineCost=(d,l)=>(d.type==="open"||d.type==="receipt")?(+l.c||0)/unitF(D.item(l.i),l.u):D.item(l.i).cost;
const docVal=d=>d.lines.reduce((a,l)=>a+lineBase(l)*lineCost(d,l),0);
const stName=id=>store(id)[1];
/* حالة المستند → شارة */
const DSTAT=d=>d.status;
/* المسار */
const flowOf=d=>{const t=d.type==="transfer";
 const steps=t?[["مسودة",d.by||""],["معتمد ومرسل",d.appr||"المرسل يعتمد"],["مستلم","المخزن المستلم"],["مرحّل",d.entry||"المحاسب"]]:[["مسودة",d.by||""],["معتمد",d.appr||"مدير الفرع"],["مرحّل",d.entry||"المحاسب"]];
 const ix=t?{draft:0,sent:1,received:2,posted:3,rejected:1,cancelled:0}:{draft:0,approved:1,posted:2,cancelled:0};
 let cur=ix[d.status];if(d.status==="posted")cur=steps.length;
 return CM.flow(steps,cur,d.status==="cancelled"||d.status==="rejected")};
const DL={type:"all",st:"all",menu:0,q:""};
const waitLabel=d=>({draft:"بانتظار الاعتماد",approved:"بانتظار الترحيل",sent:"بانتظار استلام "+(d.to?stName(d.to):""),received:"بانتظار الترحيل"}[d.status]||"");
S.cmDocs=()=>{const all=docs().filter(d=>CM.allBr()||store(d.store)[2]==="b1");
 const byT=all.filter(d=>(DL.type==="all"||d.type===DL.type)&&(!DL.q||(d.no+d.reason+d.lines.map(l=>D.item(l.i).n).join(" ")).includes(DL.q)));
 const rows=byT.filter(d=>DL.st==="all"||(DL.st==="wait"?["approved","sent","received"].includes(d.status):d.status===DL.st));
 const n=k=>byT.filter(d=>k==="wait"?["approved","sent","received"].includes(d.status):d.status===k).length;
 const wrecv=all.filter(d=>d.status==="sent").length,wpost=all.filter(d=>d.status==="approved"||d.status==="received").length,dr=all.filter(d=>d.status==="draft").length;
 const chip=(k,t,c,dd)=>`<button class="${DL.st===k?"on":""}" data-cmk-act="dst" data-v="${k}">${dd?`<i class="d ${dd}"></i>`:""}${t}<b class="num">${c}</b></button>`;
 const todo=[wrecv?`<a href="#" data-cmk-act="dst" data-v="sent"><b>${wrecv}</b> تحويل بانتظار الاستلام</a>`:"",wpost?`<a href="#" data-cmk-act="dst" data-v="wait"><b>${wpost}</b> مستند ينتظر الترحيل</a>`:"",dr?`<a href="#" data-cmk-act="dst" data-v="draft"><b>${dr}</b> مسودة غير معتمدة</a>`:""].filter(Boolean);
 const lockedWait=all.filter(d=>d.status==="approved"&&d.date<=LOCK).length;
 const body=`${CM.note(`<b>ماذا أفعل الآن؟</b> ${todo.join(" · ")||"لا مستندات معلّقة."}${lockedWait?` · <b>${lockedWait}</b> منها تاريخه في فترة مقفلة ولن يُرحَّل قبل تعديل التاريخ`:""}`,wrecv||lockedWait?"warn":"")}
 <div class="cx-chips cmk-types"><button class="${DL.type==="all"?"on":""}" data-cmk-act="dty" data-v="all">كل الأنواع<b class="num">${all.length}</b></button>${TYK.map(k=>`<button class="${DL.type===k?"on":""}" data-cmk-act="dty" data-v="${k}">${X(TY[k].ic)}${TY[k].n}<b class="num">${all.filter(d=>d.type===k).length}</b></button>`).join("")}</div>
 <div class="fbar"><div class="fb grow"><label>بحث</label><div class="inp">${X("search")}<input data-cmk-chg="dq" value="${esc(DL.q)}" placeholder="رقم المستند أو اسم منتج أو سبب…"></div></div>${CM.brSel()}
  <div class="fb"><label>الحالة</label><div class="cx-chips" style="margin:0">${chip("all","الكل",byT.length)}${chip("draft","مسودة",n("draft"),"n")}${chip("wait","بانتظار",n("wait"),"a")}${chip("posted","مرحّل",n("posted"),"g")}${chip("cancelled","ملغى",n("cancelled"),"r")}</div></div></div>
 ${rows.length?`<div class="card flush"><div class="tw"><table class="cmk-tbl"><thead><tr><th>الرقم</th><th>النوع</th><th>التاريخ</th><th>المخزن</th><th class="e">السطور</th>${CM.cost()?`<th class="e">القيمة</th>`:""}<th>الحالة</th><th>أنشأه / اعتمده</th><th></th></tr></thead><tbody>
 ${rows.map(d=>{const lk=d.status==="approved"&&d.date<=LOCK;return `<tr><td><a href="#" class="cmk-no" data-go="cmDocShow" data-cmk-act="open" data-v="${d.id}"><span class="cm-mono">${d.no}</span></a></td><td><span class="cmk-ty">${X(TY[d.type].ic)}${TY[d.type].n}</span></td><td class="num">${d.date}</td><td>${stName(d.store)}${d.to?`<small class="cm-sub">إلى: ${stName(d.to)}</small>`:""}</td><td class="e num">${d.lines.length}</td>${CM.cost()?`<td class="e">${CM.m(docVal(d))}</td>`:""}<td>${CM.st(DSTAT(d))}${waitLabel(d)&&d.status!=="draft"?`<small class="cm-sub">${waitLabel(d)}</small>`:""}${lk?`<small class="cm-sub cmk-lock">${X("lock")}فترة مقفلة</small>`:""}</td><td><small class="cmk-who">${d.by||""}${d.appr?`<br>${d.appr}`:""}</small></td>
  <td><div class="rowacts"><button class="icon-btn pri" title="فتح" data-go="cmDocShow" data-cmk-act="open" data-v="${d.id}">${X("eye")}</button>${d.status==="draft"?`<button class="icon-btn pri" title="اعتماد" data-cmk-act="qa" data-v="${d.id}:approve" ${can("approve")?"":"disabled"}>${X("badge-check")}</button>`:""}${d.status==="approved"||d.status==="received"?`<button class="icon-btn pri" title="ترحيل" data-go="cmDocShow" data-cmk-act="open" data-v="${d.id}:post" ${can("post")?"":"disabled"}>${X("book-check")}</button>`:""}${d.status==="sent"?`<button class="icon-btn pri" title="استلام" data-go="cmDocShow" data-cmk-act="open" data-v="${d.id}" ${can("receive")?"":"disabled"}>${X("package-check")}</button>`:""}${["draft","approved","sent"].includes(d.status)?`<button class="icon-btn pri" title="إلغاء" data-cmk-act="qa" data-v="${d.id}:cancel" ${can("cancel")?"":"disabled"}>${X("x")}</button>`:""}</div></td></tr>`}).join("")}</tbody></table></div><div class="tfoot"><span>${rows.length} مستند</span><span class="cm-legend">المرحّل والملغى لا يُحذفان — التصحيح بمستند عكسي</span></div></div>`:CM.nores()}`;
 return `${HEAD("إدخال وإخراج بضاعة","كل حركات المخزون اليدوية في قائمة واحدة: افتتاحي، استلام، تحويل، جرد، تسوية، إتلاف.",`<div class="cmk-menuw">${B("primary","plus","مستند جديد",'data-cmk-act="menu"')}${DL.menu?`<div class="cmk-menu">${TYK.map(k=>`<a href="#" ${k==="count"?'data-go="cmCount"':`data-go="cmDocShow" data-cmk-act="newdoc" data-v="${k}"`}>${X(TY[k].ic)}<div><b>${TY[k].n}</b><small>${TY[k].d}</small></div></a>`).join("")}</div>`:""}</div>`)}
 ${CM.pvbar({st:1})}${roleBar()}${CM.view(body,CM.empty("file-stack","لا مستندات مخزون بعد","ابدأ برصيد افتتاحي لإدخال أرصدة بداية المدة، أو استلام بضاعة جديدة.",B("primary","flag","رصيد افتتاحي",'data-go="cmDocShow" data-cmk-act="newdoc" data-v="open"')+B("ghost","package-plus","استلام",'data-go="cmDocShow" data-cmk-act="newdoc" data-v="receipt"')))}`};

/* ============ مستند المخزون (نموذج واحد لكل الأنواع) ============ */
let DOC=null,DE={},DUI={cp:0,rej:0,rejReason:"",ack:0};
const WHO={all:"مشرف النظام",keeper:"فيصل الدوسري",mgr:"سلمان العتيبي",acct:"هدى الحربي"};
const newDoc=t=>({id:"n"+Date.now(),type:t,no:"",date:TODAY,store:"s1",to:t==="transfer"?"s2":"",reason:"",status:"draft",by:WHO[role()],lines:[],log:[],saved:0});
const loadDoc=()=>{const cur=ss("cmk.cur",null),key=JSON.stringify(cur);if(DOC&&DOC._k===key)return DOC;
 let d=null;if(cur&&cur.id)d=docs().find(x=>x.id===cur.id);if(d){d=JSON.parse(JSON.stringify(d))}else d=newDoc(cur&&cur.new||"receipt");
 if(cur&&cur.copy){d=newDoc(cur.copy.type);Object.assign(d,{store:cur.copy.store,to:cur.copy.to||"",reason:cur.copy.reason,lines:JSON.parse(JSON.stringify(cur.copy.lines)),copyOf:cur.copy.no});d.lines.forEach(l=>{l.r=null})}
 if(cur&&cur.rev){d=newDoc(cur.rev.type);Object.assign(d,{store:cur.rev.store,to:cur.rev.to||"",reason:"عكس المستند "+cur.rev.no,lines:JSON.parse(JSON.stringify(cur.rev.lines)),revOf:cur.rev.no});d.lines.forEach(l=>{l.r=null;if(d.type==="adjust"||d.type==="count")l.q=-l.q})}
 d._k=key;d._postGo=cur&&cur.post;DE={};DUI={cp:0,rej:0,rejReason:"",ack:0};if(d._postGo&&d.status!=="posted")DUI.cp=1;DOC=d;return d};
const persist=()=>{if(DOC&&DOC.saved){const c=JSON.parse(JSON.stringify(DOC));delete c._k;delete c._postGo;upsert(c)}};
const logd=t=>{DOC.log.push([NOW,WHO[role()],t])};
const editable=d=>d.status==="draft"&&!(d.type==="count");
const avail=(d,l)=>{const it=D.item(l.i);return (it.qty||{})[d.store]||0};
const needBatch=(d,l)=>CM.on("batches_expiry")&&D.item(l.i).tr==="batch";
const validate=d=>{const e={};
 if(!d.store)e.store="اختر المخزن.";
 if(d.type==="transfer"){if(!d.to)e.to="اختر المخزن المستلم.";else if(d.to===d.store)e.to="المخزن المستلم يجب أن يختلف عن المرسل."}
 if((d.type==="adjust"||d.type==="waste")&&!d.reason)e.reason="السبب إجباري في "+(d.type==="adjust"?"التسوية":"الإتلاف")+".";
 if(d.type==="waste"&&!d.photo)e.photo="أرفق صورة للتالف قبل الاعتماد.";
 if(!d.date)e.date="التاريخ مطلوب.";
 if(!d.lines.length)e.lines="أضف منتجاً واحداً على الأقل بمسح الباركود أو البحث أو الاستيراد.";
 d.lines.forEach((l,i)=>{const it=D.item(l.i);
  if(!(+l.q)||(+l.q<0&&d.type!=="adjust"&&d.type!=="count"))e["q"+i]=d.type==="adjust"?"أدخل كمية (موجبة للزيادة وسالبة للنقص).":"الكمية يجب أن تكون أكبر من صفر.";
  else if(["transfer","waste","adjust","count"].includes(d.type)&&(d.type!=="adjust"||l.q<0)&&d.type!=="count"&&!store(d.store)[6]){const a=avail(d,l),need=Math.abs(lineBase(l));if(need>a)e["q"+i]="المتاح في المخزن "+fmt(a)+" "+it.units[0][0]+" فقط."}
  if(needBatch(d,l)&&!l.b)e["b"+i]="رقم التشغيلة مطلوب لهذا المنتج.";
  if((d.type==="open"||d.type==="receipt")&&CM.cost()&&!(+l.c>0))e["c"+i]="أدخل تكلفة الوحدة."});
 return e};
const jl=d=>{const sA=store(d.store)[4],cost=CM.cost(),v=docVal(d);const sp=a=>{const i=a.indexOf(" ");return [a.slice(i+1),a.slice(0,i)]};const cc=store(d.store)[3];
 const row=(a,dr,cr,c)=>[...sp(a),dr,cr,c||""].map((x,i)=>x);
 const R=(a,dr,cr,c)=>{const s=sp(a);return [s[0],s[1],dr,cr,c]};
 if(d.type==="open")return [R(sA,v,0,cc),R("3101 أرصدة افتتاحية — حقوق الملكية",0,v)];
 if(d.type==="receipt")return [R(sA,v,0,cc),R(D.accounts.grni,0,v)];
 if(d.type==="transfer"){const tA=store(d.to)[4];if(tA===sA)return [];return [R(tA,v,0,store(d.to)[3]),R(sA,0,v,cc)]}
 if(d.type==="waste")return [R(D.accounts.waste,v,0,cc),R(sA,0,v)];
 const gain=d.lines.filter(l=>l.q>0).reduce((a,l)=>a+lineBase(l)*lineCost(d,l),0),loss=d.lines.filter(l=>l.q<0).reduce((a,l)=>a+Math.abs(lineBase(l))*lineCost(d,l),0);const o=[];
 if(gain)o.push(R(sA,gain,0,cc),R(D.accounts.variance,0,gain));if(loss)o.push(R(D.accounts.variance,loss,0,cc),R(sA,0,loss));return o};
const lineIn=(d,i,f,type,extra,dis)=>`<input ${type?`type="${type}"`:""} ${extra||""} value="${esc(DOC.lines[i][f]==null?"":DOC.lines[i][f])}" data-cmk-chg="ln" data-i="${i}" data-f="${f}" ${dis?"disabled":""} class="${DE[f[0]+i]?"err":""}">`;
const errT=k=>DE[k]?`<small class="cmk-err">${X("circle-alert")}${DE[k]}</small>`:"";
S.cmDocShow=()=>{const d=loadDoc(),T=TY[d.type],ed=editable(d),cost=CM.cost(),bt=CM.on("batches_expiry"),costCol=(d.type==="open"||d.type==="receipt")&&cost,tr=d.type==="transfer";
 const recvMode=tr&&d.status==="sent"&&can("receive"),showR=tr&&["sent","received","posted","rejected"].includes(d.status);
 const posted=d.status==="posted",locked=d.date<=LOCK;
 const heads=[],ncols=3+(bt?1:0)+(costCol?2:0)+(showR?1:0);
 /* الإجراءات في رأس الصفحة */
 let acts="";
 if(d.status==="draft"&&d.type!=="count")acts=PB("ghost","save","حفظ المسودة","save",null,"edit")+(d.saved?PB("ghost","trash-2","حذف المسودة","del",null,"edit"):"")+PB("primary","badge-check",tr?"اعتماد وإرسال":"اعتماد","approve",null,"approve");
 else if(d.status==="draft"&&d.type==="count")acts=PB("primary","badge-check","اعتماد","approve",null,"approve");
 else if(d.status==="approved"||d.status==="received")acts=PB("ghost","x","إلغاء","cancel",null,"cancel")+PB("primary","book-check","ترحيل","post",null,"post");
 else if(d.status==="sent")acts=PB("ghost","x","إلغاء","cancel",null,"cancel")+PB("ghost","ban","رفض التحويل","rej",null,"receive")+PB("primary","package-check","تأكيد الاستلام","recv",null,"receive");
 /* بطاقة الرأس */
 const dis=ed?"":"disabled";
 const hdr=`<div class="card flush"><div class="sec-h">${X(T.ic)}<b>بيانات المستند</b>${ed&&!d.saved&&!d.copyOf&&!d.revOf?`<div class="cmk-ptype">${["open","receipt","transfer","adjust","waste"].map(k=>`<button class="${d.type===k?"on":""}" data-cmk-act="ptype" data-v="${k}">${TY[k].n}</button>`).join("")}</div>`:""}</div>
  <div class="fgrid">
  ${FLD("النوع",`<input readonly value="${T.n}">`)}
  ${FLD("الرقم",`<input readonly dir="ltr" value="${d.no||"يُولَّد عند الحفظ"}">`)}
  ${FLD("التاريخ",`<input type="date" ${ed||(locked&&["approved","received"].includes(d.status))?"":"disabled"} value="${d.date}" data-cmk-chg="df" data-f="date" class="${DE.date?"err":""}">`+errT("date")+(!posted&&locked?`<small class="cmk-err warn">${X("lock")}التاريخ في فترة مقفلة (حتى ${LOCK}) — لن يقبل الترحيل</small>`:""),1)}
  ${FLD(tr?"المخزن المرسل":"المخزن",`<select ${dis} data-cmk-chg="df" data-f="store" class="${DE.store?"err":""}">${D.stores.filter(s=>CM.allBr()||s[2]==="b1").map(s=>`<option value="${s[0]}" ${d.store===s[0]?"selected":""}>${s[1]}</option>`).join("")}</select>`+errT("store"),1)}
  ${tr?FLD("المخزن المستلم",`<select ${dis} data-cmk-chg="df" data-f="to" class="${DE.to?"err":""}"><option value="">اختر المخزن</option>${D.stores.map(s=>`<option value="${s[0]}" ${d.to===s[0]?"selected":""}>${s[1]}</option>`).join("")}</select>`+errT("to"),1):""}
  ${d.type==="adjust"||d.type==="waste"?FLD("السبب",`<select ${dis} data-cmk-chg="df" data-f="reason" class="${DE.reason?"err":""}"><option value="">اختر السبب</option>${REASONS[d.type].map(r=>`<option ${d.reason===r?"selected":""}>${r}</option>`).join("")}</select>`+errT("reason"),1):FLD("ملاحظات",`<input ${dis} data-cmk-chg="df" data-f="reason" value="${esc(d.reason)}" placeholder="اختياري">`)}
  </div>
  <div class="cmk-who2"><span>${X("user-round")}أنشأه: <b>${d.by||"—"}</b></span><span>${X("badge-check")}اعتمده: <b>${d.appr||"لم يُعتمد بعد"}</b></span>${d.entry?`<span>${X("book-check")}القيد: <a href="#" data-go="finEntries" class="cm-mono">${d.entry}</a></span>`:""}${d.copyOf?`<span>${X("copy")}منسوخ من <b class="cm-mono">${d.copyOf}</b></span>`:""}${d.revOf?`<span>${X("undo-2")}عكس <b class="cm-mono">${d.revOf}</b></span>`:""}</div></div>`;
 /* السطور */
 const scan=ed?`<div class="cmk-scan"><div class="inp">${X("scan-barcode")}<input list="cmk-dl" data-cmk-scan="doc" placeholder="امسح الباركود أو ابحث باسم المنتج أو كوده ثم Enter" autocomplete="off"></div><datalist id="cmk-dl">${stockItems().map(i=>`<option value="${i.code}">${i.n}</option>`).join("")}</datalist>${B("primary","plus","إضافة",'data-cmk-act="addscan"')}${B("ghost","file-spreadsheet","استيراد Excel",'data-cmk-act="import"')}</div>${errT("scan")}${errT("lines")}`:"";
 const lines=`<div class="card flush"><div class="sec-h">${X("list")}<b>السطور</b><span class="cm-chip-new" style="background:#eef1f5;color:#56637a">${d.lines.length}</span>${recvMode?`<small style="margin-inline-start:auto;color:var(--amber);font-weight:700">أدخل الكمية المستلمة فعلياً لكل سطر</small>`:""}</div>${scan}
  ${d.lines.length?`<div class="tw"><table class="cmk-tbl cmk-lines"><thead><tr><th>#</th><th>المنتج</th><th>الوحدة</th><th class="e">${d.type==="adjust"?"الفرق (±)":"الكمية"}</th>${bt?`<th>التشغيلة</th>`:""}${costCol?`<th class="e">تكلفة الوحدة</th><th class="e">الإجمالي</th>`:""}${showR?`<th class="e">المستلم فعلياً</th>`:""}<th>ملاحظات</th><th></th></tr></thead><tbody>
  ${d.lines.map((l,i)=>{const it=D.item(l.i),rowDiff=showR&&l.r!=null&&+l.r!==+l.q;
   return `<tr class="${rowDiff?"cm-row-warn":""}"><td class="num">${i+1}</td><td><div class="cm-icell"><span class="cm-img sm" style="background:${it.col}">${X(it.ic)}</span><div><b>${it.n}</b><small class="num">${it.code}${["transfer","waste","adjust"].includes(d.type)&&d.status==="draft"?` · المتاح ${fmt(avail(d,l))} ${it.units[0][0]}`:""}</small></div></div></td>
   <td>${ed&&it.units.length>1&&CM.on("multi_uom")?`<select data-cmk-chg="ln" data-i="${i}" data-f="u">${it.units.map(u=>`<option ${u[0]===l.u?"selected":""}>${u[0]}</option>`).join("")}</select>`:`<span>${l.u}</span>`}</td>
   <td class="e">${ed?lineIn(d,i,"q","number",'step="any" inputmode="decimal" min="0"'):CM.q(l.q,l.u)}${errT("q"+i)}<small class="cmk-eq">${unitF(it,l.u)>1?`أي ${fmt(lineBase(l))} ${it.units[0][0]}`:""}</small></td>
   ${bt?`<td>${needBatch(d,l)||l.b?(ed?`<input list="cmk-b${it.id}" value="${esc(l.b)}" data-cmk-chg="ln" data-i="${i}" data-f="b" placeholder="رقم التشغيلة" class="cm-mono ${DE["b"+i]?"err":""}"><datalist id="cmk-b${it.id}">${(BATCH[it.id]||[]).map(b=>`<option value="${b[0]}">${b[1]}</option>`).join("")}</datalist>`:`<span class="cm-mono">${l.b||"—"}</span>`)+errT("b"+i):`<span class="cm-hid">—</span>`}</td>`:""}
   ${costCol?`<td class="e">${ed?lineIn(d,i,"c","number",'step="any" inputmode="decimal" min="0"'):CM.c(l.c)}${errT("c"+i)}</td><td class="e">${CM.m(l.q*l.c)}</td>`:""}
   ${showR?`<td class="e">${recvMode?`<input type="number" step="any" min="0" value="${l.r==null?l.q:l.r}" data-cmk-chg="ln" data-i="${i}" data-f="r"><small class="cmk-eq">أُرسل ${fmt(l.q)} ${l.u}</small>`:`${l.r==null?"—":CM.q(l.r,l.u)}${rowDiff?`<small class="cmk-err warn">أُرسل ${fmt(l.q)} ${l.u}</small>`:""}`}</td>`:""}
   <td>${ed?`<input value="${esc(l.n)}" data-cmk-chg="ln" data-i="${i}" data-f="n" placeholder="—">`:`<small>${esc(l.n)||"—"}</small>`}</td><td>${ed?`<button class="icon-btn pri" title="حذف السطر" data-cmk-act="rm" data-v="${i}">${X("trash-2")}</button>`:""}</td></tr>`}).join("")}</tbody>
  <tfoot><tr><td colspan="${4+(bt?1:0)+(costCol?1:0)}">الإجمالي</td>${costCol?`<td class="e">${CM.m(d.lines.reduce((a,l)=>a+l.q*l.c,0))}</td>`:""}<td colspan="${(showR?1:0)+2}"></td></tr></tfoot></table></div>`:`<div class="cmk-emptyl">${X("scan-barcode")}<b>لا منتجات بعد</b><span>امسح باركود المنتج أو ابحث عنه أو استورد ملف Excel.</span></div>`}</div>`;
 /* لوحات */
 const waste=d.type==="waste"?`<div class="card cmk-photo"><div class="cm-sec-h">${X("camera")}<b>صورة التالف</b><small>إجبارية قبل الاعتماد</small></div>${d.photo?`<div class="cmk-ph"><span class="cmk-phi">${X("image")}</span><div><b class="cm-mono">${esc(d.photo)}</b><small>رُفعت بواسطة ${d.by}</small></div>${ed?`<button class="icon-btn pri" data-cmk-act="photo" title="إزالة">${X("x")}</button>`:""}</div>`:`<button class="btn btn-ghost" data-cmk-act="photo" ${ed?"":"disabled"}>${X("upload")}إرفاق صورة</button>`}${errT("photo")}</div>`:"";
 const rejBox=DUI.rej?`<div class="card cmk-pop bad"><div class="cm-sec-h">${X("ban")}<b>رفض التحويل</b><small>يعود المخزون المرسل إلى ${stName(d.store)} ويُسجَّل السبب</small></div>${FLD("سبب الرفض",`<textarea rows="2" data-cmk-chg="rejr" class="${DE.rej?"err":""}" placeholder="مثال: البضاعة تالفة أو غير مطابقة">${esc(DUI.rejReason)}</textarea>`+errT("rej"),1)}<div class="cm-acts">${B("primary","ban","تأكيد الرفض",'data-cmk-act="rejok"')}${B("ghost","","تراجع",'data-cmk-act="rejno"')}</div></div>`:"";
 const jlist=jl(d);
 const jbox=cost?(jlist.length?CM.jprev(posted?"القيد المرحّل "+(d.entry||""):"معاينة القيد قبل الترحيل",jlist,posted?"":"المبالغ بمتوسط التكلفة الحالي للمنتج عند الترحيل."):CM.note("حساب المخزون واحد في المخزنين، فلا قيد على هذا التحويل.","")):CM.note("معاينة القيد تظهر لمن يملك صلاحية رؤية التكلفة. الترحيل ممكن بصلاحية الترحيل.","warn","eye-off");
 const postBox=DUI.cp&&!posted?`<div class="card cmk-pop"><div class="cm-sec-h">${X("book-check")}<b>راجع القيد ثم رحّل</b><small>بعد الترحيل يتحوّل المستند للقراءة فقط</small></div>
  ${locked?CM.note(`<b>لا يمكن الترحيل:</b> التاريخ ${d.date} يقع في فترة محاسبية مقفلة (حتى ${LOCK}). غيّر تاريخ المستند إلى فترة مفتوحة أو اطلب فتح الفترة من المحاسب الرئيسي.`,"bad","lock"):""}
  ${jbox}<div class="cm-acts" style="margin-top:14px">${PB("primary","book-check","تأكيد الترحيل","postok",null,"post",locked?"data-locked=1":"")}${B("ghost","","رجوع",'data-cmk-act="postno"')}</div></div>`:"";
 const roBar=posted?CM.ro("هذا المستند مرحّل ولا يُعدّل ولا يُحذف",`${d.entry?`<button class="btn btn-ghost sm" data-go="finEntries">${X("book-open")}عرض القيد ${d.entry}</button>`:""}${PB("ghost sm","undo-2","عكس","rev",null,"reverse")}<button class="btn btn-ghost sm" data-cmk-act="copy">${X("copy")}نسخ</button>`)
  :d.status==="cancelled"?CM.ro("هذا المستند ملغى",`<button class="btn btn-ghost sm" data-cmk-act="copy">${X("copy")}نسخ</button>`)
  :d.status==="rejected"?CM.ro("رُفض التحويل من المخزن المستلم"+(d.rejReason?": "+esc(d.rejReason):""),`<button class="btn btn-ghost sm" data-cmk-act="copy">${X("copy")}نسخ</button>`)
  :d.status!=="draft"?CM.ro(d.status==="sent"?"التحويل مُرسل وينتظر استلام "+stName(d.to):"المستند معتمد وينتظر الترحيل",`<small style="color:var(--muted)">للتعديل ألغِ الاعتماد بالإلغاء ثم انسخ المستند</small>`):"";
 const nextHint=d.status==="draft"?"التالي: اعتماد "+PERM_N.approve:d.status==="approved"||d.status==="received"?"التالي: ترحيل "+PERM_N.post:d.status==="sent"?"التالي: استلام في "+stName(d.to):"";
 const side=`<div class="cmk-below">${d.status==="posted"||DUI.cp?"":(d.type!=="count"&&d.lines.length?`<div class="card cmk-pvc"><div class="cm-sec-h">${X("book-open")}<b>القيد المتوقع</b></div>${jbox}</div>`:"")}
  ${posted?`<div class="card cmk-pvc"><div class="cm-sec-h">${X("book-check")}<b>القيد</b></div>${jbox}<a href="#" class="cmk-jl" data-go="finEntries">${X("external-link")}عرض القيد ${d.entry||""}</a></div>`:""}
  <div class="card"><div class="cm-sec-h">${X("history")}<b>سجل المستند</b></div><div class="tl">${(d.log.length?d.log:[[NOW,d.by,"مسودة جديدة — لم تُحفظ بعد"]]).slice().reverse().map(x=>`<div class="tli"><span class="dot"></span><div><b>${x[2]}</b><small>${x[1]}</small><small class="num">${x[0]}</small></div></div>`).join("")}</div></div></div>`;
 const title=`${T.n} ${d.no?`<span class="cm-mono" style="font-size:.7em;color:var(--muted)">${d.no}</span>`:"جديد"}`;
 return `${HEAD(title,d.type==="count"?"مستند جرد مُنشأ من شاشة الجرد — الأسطر هي الفروقات فقط.":T.d,`${CM.st(d.status)}${acts}`,["cmDocs","إدخال وإخراج بضاعة"])}
 ${CM.pvbar()}${roleBar()}${flowOf(d)}${nextHint&&!roBar?"":""}${roBar}${DE._top&&!DUI.cp?CM.note(DE._top,"bad"):""}${postBox}${rejBox}
 <div class="cmk-main">${hdr}${waste}${lines}${side}</div>`};

/* ============ الجرد ============ */
const genLines=(sid,scope,seed)=>stockItems().filter(i=>(i.qty||{})[sid]!=null&&(scope==="all"||scope.includes(topCat(i)))).map((it,k)=>{const exp=it.qty[sid];let cnt=null;
 if(seed==="part"&&k%5!==4){cnt=exp;if(k%4===1&&exp>2)cnt=exp-2;if(k%6===2&&exp>0)cnt=exp+(it.w?1.5:1)}
 if(seed==="full"){cnt=exp;if(k%3===0&&exp>3)cnt=exp-3;if(k%5===1&&exp>0)cnt=exp+1}
 return {i:it.id,exp,cnt}});
const seedCounts=()=>[
 {id:"c3",no:"CT-0003",store:"s2",scope:"all",snap:"2026-11-20 08:30",mode:"start",status:"counting",by:"ليلى القحطاني",lines:genLines("s2","all","part")},
 {id:"c2",no:"CT-0002",store:"s1",scope:"all",snap:"2026-11-01 15:00",mode:"start",status:"posted",by:"فيصل الدوسري",appr:"سلمان العتيبي",entry:"JV-2611-0033",lines:genLines("s1","all","full")},
 {id:"c1",no:"CT-0001",store:"s3",scope:["الأعلاف"],snap:"2026-10-05 09:00",mode:"start",status:"posted",by:"محمد الشهري",appr:"سلمان العتيبي",entry:"JV-2610-0120",lines:genLines("s3",["الأعلاف"],"full")}];
const counts=()=>{let c=ss("cmk.counts",null);if(!c){c=seedCounts();sv("cmk.counts",c)}return c};
const saveCounts=c=>sv("cmk.counts",c);
const CST={counting:["open","جاري العدّ"],submitted:["sent","بانتظار الاعتماد"],approved:["approved","معتمد — بانتظار الترحيل"],posted:["posted","مرحّل"]};
const cstat=c=>CM.st(CST[c.status][0],CST[c.status][1]);
const cprog=c=>({n:c.lines.length,done:c.lines.filter(l=>l.cnt!=null).length,diff:c.lines.filter(l=>l.cnt!=null&&l.cnt!==l.exp).length});
const CN={open:0,store:"s1",scope:"all",cats:[],mode:"start",err:""};
S.cmCount=()=>{const all=counts().filter(c=>CM.allBr()||store(c.store)[2]==="b1");
 const live=all.filter(c=>c.status==="counting").length,wa=all.filter(c=>c.status==="submitted").length,wp=all.filter(c=>c.status==="approved").length;
 const panel=CN.open?`<div class="card flush cmk-new"><div class="sec-h">${X("scan-barcode")}<b>جرد جديد</b><button class="icon-btn" style="margin-inline-start:auto" data-cmk-act="cnew" data-v="0">${X("x")}</button></div><div class="fgrid">
  ${FLD("المخزن",`<select data-cmk-chg="cn" data-f="store">${D.stores.filter(s=>CM.allBr()||s[2]==="b1").map(s=>`<option value="${s[0]}" ${CN.store===s[0]?"selected":""}>${s[1]}</option>`).join("")}</select>`,1)}
  ${FLD("النطاق",`<div class="cmk-radio"><label><input type="radio" name="sc" ${CN.scope==="all"?"checked":""} data-cmk-chg="cn" data-f="scope" value="all"> كل المنتجات</label><label><input type="radio" name="sc" ${CN.scope==="sel"?"checked":""} data-cmk-chg="cn" data-f="scope" value="sel"> أقسام محددة</label></div>`,1)}
  ${FLD("المتوقع يُثبَّت",`<select data-cmk-chg="cn" data-f="mode"><option value="start" ${CN.mode==="start"?"selected":""}>عند بدء العدّ (الافتراضي)</option><option value="approve" ${CN.mode==="approve"?"selected":""}>عند الاعتماد</option></select>`,1)}
  ${CN.scope==="sel"?`<div class="fld full"><label>الأقسام <i class="rq">*</i></label><div class="cmk-cats">${cats().map(c=>`<label class="${CN.cats.includes(c)?"on":""}"><input type="checkbox" data-cmk-chg="cn" data-f="cat" value="${c}" ${CN.cats.includes(c)?"checked":""}>${c}</label>`).join("")}</div>${CN.err?`<small class="cmk-err">${X("circle-alert")}${CN.err}</small>`:""}</div>`:""}
  </div><div class="cmk-newf">${CM.note("المخزون يبقى يتحرك أثناء العدّ والكاشير يبيع. الافتراضي أن يُثبَّت المتوقع لحظة البدء، ويُطبَّق الفرق عند الترحيل فوق الرصيد الحالي فلا تضيع مبيعات ما بعد البدء.","")}<div class="cm-acts">${B("primary","play","ابدأ العدّ",'data-go="cmCountRun" data-cmk-act="cstart"')}${B("ghost","file-spreadsheet","استيراد معدود من Excel",'data-cm-toast="يُستورد ملف الكميات بعد بدء الجرد" data-cm-ic="file-spreadsheet"')}</div></div></div>`:"";
 const body=`${CM.note(`<b>ماذا أفعل الآن؟</b> ${[live?`<b>${live}</b> جرد قيد العدّ`:"",wa?`<b>${wa}</b> جرد ينتظر اعتماد مدير الفرع`:"",wp?`<b>${wp}</b> جرد معتمد ينتظر الترحيل`:""].filter(Boolean).join(" · ")||"لا جرد معلّق."}`,wa||wp?"warn":"")}${panel}
 <div class="card flush"><div class="tw"><table class="cmk-tbl"><thead><tr><th>الرقم</th><th>المخزن</th><th>النطاق</th><th>لقطة المتوقع</th><th>التقدم</th><th class="e">الفروقات</th><th>الحالة</th><th></th></tr></thead><tbody>
 ${all.map(c=>{const p=cprog(c);return `<tr><td><a href="#" data-go="cmCountRun" data-cmk-act="copen" data-v="${c.id}"><span class="cm-mono">${c.no}</span></a></td><td>${stName(c.store)}</td><td>${c.scope==="all"?"كل المنتجات":c.scope.join("، ")}</td><td class="num">${c.mode==="start"?c.snap:"عند الاعتماد"}</td><td><div class="cmk-bar"><i style="width:${Math.round(p.done/p.n*100)}%"></i></div><small class="cm-sub num">${p.done} / ${p.n}</small></td><td class="e">${p.diff?`<span class="cm-flag">${X("triangle-alert")}${p.diff}</span>`:`<span class="num">0</span>`}</td><td>${cstat(c)}</td><td><div class="rowacts"><button class="icon-btn pri" title="فتح" data-go="cmCountRun" data-cmk-act="copen" data-v="${c.id}">${X(c.status==="counting"?"scan-barcode":"eye")}</button></div></td></tr>`}).join("")}</tbody></table></div><div class="tfoot"><span>${all.length} جرد</span></div></div>`;
 return `${HEAD("الجرد","عدّ المخزون الفعلي ومقارنته بالمتوقع، ولا يتغير الرصيد إلا بعد الاعتماد والترحيل.",B("primary","plus","جرد جديد",'data-cmk-act="cnew" data-v="1"'))}
 ${CM.pvbar({st:1})}${roleBar()}${CM.view(body,CM.empty("scan-barcode","لا جرد بعد","ابدأ جرداً كاملاً أو جزئياً بالقسم، وعُدّ بمسح الباركود من التابلت.",B("primary","plus","جرد جديد",'data-cmk-act="cnew" data-v="1"')))}`};

/* ============ شاشة العدّ (للجوال أولاً) ============ */
const CR={f:"all",last:null,cp:0,ack:0,err:"",q:""};
const curCount=()=>{const id=ss("cmk.ccur","c3");return counts().find(c=>c.id===id)||counts()[0]};
const lineDiff=l=>l.cnt==null?null:Math.round((l.cnt-l.exp)*1e4)/1e4;
S.cmCountRun=()=>{const c=curCount(),p=cprog(c),live=c.status==="counting",cost=CM.cost(),s=store(c.store);
 const diffs=c.lines.filter(l=>l.cnt!=null&&l.cnt!==l.exp),unc=c.lines.length-p.done;
 const vshort=diffs.reduce((a,l)=>a+Math.min(0,lineDiff(l))*D.item(l.i).cost,0),vover=diffs.reduce((a,l)=>a+Math.max(0,lineDiff(l))*D.item(l.i).cost,0);
 const rows=c.lines.map((l,i)=>({l,i})).filter(x=>CR.f==="all"||(CR.f==="unc"?x.l.cnt==null:x.l.cnt!=null&&x.l.cnt!==x.l.exp)).filter(x=>!CR.q||(D.item(x.l.i).n+D.item(x.l.i).code).includes(CR.q));
 const steps=CM.flow([["جاري العدّ",c.by],["بانتظار الاعتماد","أمين المخزن"],["معتمد",c.appr||"مدير الفرع"],["مرحّل",c.entry||"المحاسب"]],{counting:0,submitted:1,approved:2,posted:4}[c.status]);
 const last=CR.last!=null&&c.lines[CR.last]?(()=>{const l=c.lines[CR.last],it=D.item(l.i);return `<div class="cmk-last"><span class="cm-img" style="background:${it.col}">${X(it.ic)}</span><div><small>آخر منتج مسوح</small><b>${it.n}</b></div><div class="cmk-lc"><span class="num">${fmt(l.cnt)}</span><small>${it.units[0][0]}</small></div></div>`})():"";
 const jlist=(()=>{const sA=s[4],i=sA.indexOf(" ");const R=(a,dr,cr)=>{const j=a.indexOf(" ");return [a.slice(j+1),a.slice(0,j),dr,cr,""]};const o=[];if(vover)o.push(R(sA,vover,0),R(D.accounts.variance,0,vover));if(vshort)o.push(R(D.accounts.variance,-vshort,0),R(sA,0,-vshort));return o})();
 const popPost=CR.cp&&c.status==="approved"?`<div class="card cmk-pop"><div class="cm-sec-h">${X("book-check")}<b>راجع القيد ثم رحّل</b><small>هنا فقط يتغير رصيد المخزن</small></div>${cost?(jlist.length?CM.jprev("معاينة قيد فروقات الجرد",jlist):CM.note("لا فروقات، فلا قيد ولا تغيير في الأرصدة.","ok")):CM.note("معاينة القيد لمن يملك صلاحية رؤية التكلفة.","warn","eye-off")}<div class="cm-acts" style="margin-top:12px">${PB("primary","book-check","تأكيد الترحيل","cpostok",null,"post")}${B("ghost","","رجوع",'data-cmk-act="cpostno"')}</div></div>`:"";
 const bar=live?`${unc?`<label class="cmk-ack ${CR.err?"err":""}"><input type="checkbox" data-cmk-chg="cack" ${CR.ack?"checked":""}>أفهم أن ${unc} منتجاً غير معدود لن يتغير رصيده</label>`:""}${CR.err?`<small class="cmk-err">${X("circle-alert")}${CR.err}</small>`:""}${PB("primary","send","إنهاء العدّ وإرسال للاعتماد","csubmit",null,"edit")}`
  :c.status==="submitted"?`${PB("primary","badge-check","اعتماد الجرد","capprove",null,"approve")}<small>${X("clock")} بانتظار مدير الفرع</small>`
  :c.status==="approved"?`${PB("primary","book-check","ترحيل الجرد","cpost",null,"post")}<small>${X("clock")} معتمد من ${c.appr}، بانتظار المحاسب</small>`:`<a class="btn btn-ghost" href="#" data-go="finEntries">${X("book-open")}عرض القيد ${c.entry||""}</a><a class="btn btn-ghost" href="#" data-go="cmDocShow" data-cmk-act="cdoc">${X("file-text")}مستند الجرد</a>`;
 const list=rows.length?`<div class="cmk-list">${rows.map(({l,i})=>{const it=D.item(l.i),df=lineDiff(l),u=it.units[0][0];
  return `<div class="cmk-cr ${l.cnt==null?"unc":df?"dif":"ok"} ${CR.last===i?"hot":""}"><div class="cmk-ch"><span class="cm-img sm" style="background:${it.col}">${X(it.ic)}</span><div><b>${it.n}</b><small class="num">${it.code}</small></div>${l.cnt==null?`<span class="cm-st cm-s-wait">${X("circle-dashed")}<span>لم يُعدّ</span></span>`:df?`<span class="cm-st ${df<0?"cm-s-bad":"cm-s-wait"}">${X("triangle-alert")}<span>${df<0?"نقص":"زيادة"}</span></span>`:`<span class="cm-st cm-s-done">${X("check")}<span>مطابق</span></span>`}</div>
  <div class="cmk-cg"><div><small>المتوقع</small>${CM.q(l.exp,u)}</div><div class="cmk-step"><small>المعدود</small><div>${live?`<button data-cmk-act="cinc" data-v="${i}:-1" aria-label="إنقاص">${X("minus")}</button>`:""}<input type="number" inputmode="decimal" step="any" min="0" value="${l.cnt==null?"":l.cnt}" placeholder="—" data-cmk-chg="cnt" data-i="${i}" ${live?"":"disabled"}>${live?`<button data-cmk-act="cinc" data-v="${i}:1" aria-label="زيادة">${X("plus")}</button>`:""}</div></div><div><small>الفرق</small>${df==null?`<span class="cm-hid">—</span>`:CM.q(df,u)}${df&&cost?`<small class="cm-sub">${CM.m(df*it.cost)}</small>`:""}</div></div></div>`}).join("")}</div>`:CM.nores();
 return `${HEAD(`جرد <span class="cm-mono">${c.no}</span>`,`${s[1]} · ${c.scope==="all"?"كل المنتجات":c.scope.join("، ")}`,cstat(c),["cmCount","الجرد"])}
 ${CM.pvbar({st:0})}${roleBar()}${steps}
 <div class="cmk-snap">${CM.note(c.mode==="start"?`<b>قرار التصميم — المتوقع مثبّت عند بدء العدّ</b> (لقطة ${c.snap}). المخزون يتحرك أثناء العدّ لأن الكاشير يبيع، فنقارن ما عددته بما كان موجوداً لحظة البدء، وعند الترحيل يُطبَّق الفرق فقط فوق الرصيد الحالي فلا تضيع مبيعات ما بعد اللقطة. البديل «عند الاعتماد» يجعل المتوقع يتغير تحت قدمي العادّ.`:`<b>قرار التصميم — المتوقع يُثبَّت عند الاعتماد.</b> الأرقام المتوقعة تتغير حتى لحظة الاعتماد وقد تُربك العادّ، لذلك لا يُنصح به.`,"")}
  ${live?`<div class="cmk-mode"><label><input type="radio" name="md" value="start" ${c.mode==="start"?"checked":""} data-cmk-chg="cmode"> عند بدء العدّ <small>(الافتراضي)</small></label><label><input type="radio" name="md" value="approve" ${c.mode==="approve"?"checked":""} data-cmk-chg="cmode"> عند الاعتماد</label></div>`:""}</div>
 <div class="cmk-sum card"><div><small>المعدود</small><b class="num">${p.done}<i> / ${p.n}</i></b><div class="cmk-bar"><i style="width:${Math.round(p.done/p.n*100)}%"></i></div></div><div><small>فروقات</small><b class="num ${p.diff?"w":""}">${p.diff}</b></div><div><small>غير معدود</small><b class="num ${unc?"w":""}">${unc}</b></div>${cost?`<div><small>قيمة الفرق</small><b>${CM.m(vover+vshort)}</b></div>`:""}</div>
 ${c.status==="posted"?CM.note("رُحّل الجرد وتغيّرت أرصدة "+p.diff+" منتجاً بحسب الفروقات.","ok"):CM.note("الأرصدة <b>لم تتغير بعد</b>. تتغير فقط بعد اعتماد مدير الفرع ثم الترحيل.","")}
 ${popPost}
 ${live?`<div class="cmk-scanbar"><div class="inp">${X("scan-barcode")}<input data-cmk-scan="count" list="cmk-cdl" inputmode="search" placeholder="امسح الباركود (يزيد المعدود)" autocomplete="off" autofocus></div><datalist id="cmk-cdl">${c.lines.map(l=>`<option value="${D.item(l.i).code}">${D.item(l.i).n}</option>`).join("")}</datalist><button class="btn btn-primary" data-cmk-act="cscan">${X("plus")}</button></div>${CR.err&&CR.err[0]==="!"?`<small class="cmk-err">${X("circle-alert")}${CR.err.slice(1)}</small>`:""}`:""}
 ${last}
 <div class="cx-chips cmk-cf">${[["all","كل المنتجات",c.lines.length],["unc","غير معدودة",unc],["dif","الفروقات فقط",p.diff]].map(x=>`<button class="${CR.f===x[0]?"on":""}" data-cmk-act="cflt" data-v="${x[0]}">${x[1]}<b class="num">${x[2]}</b></button>`).join("")}</div>
 ${list}<div class="cmk-foot">${bar}</div>`};

/* ============ المخازن ============ */
const ACC=["1141 مخزون — المستودع الرئيسي","1142 مخزون — صالة البيع","1143 مخزون — جدة","1144 مخزون — مستودع الأعلاف","114 المخزون|parent","11 الأصول المتداولة|parent"];
const CCS=["CC-110 المستودعات","CC-120 صالة البيع","CC-130 مستودع الأعلاف","CC-210 فرع جدة"];
const SE={edit:null,f:null,err:{}};
const storeStats=sid=>{const its=stockItems().filter(i=>(i.qty||{})[sid]!=null);return {n:its.filter(i=>i.qty[sid]!==0).length,val:its.reduce((a,i)=>a+i.qty[sid]*i.cost,0),low:its.filter(i=>i.qty[sid]<i.re).length,neg:its.filter(i=>i.qty[sid]<0).length}};
S.cmStores=()=>{const list=D.stores.filter(s=>CM.allBr()||s[2]==="b1"),cost=CM.cost();
 const f=SE.f,e=SE.err,form=SE.edit?`<div class="card flush cmk-new"><div class="sec-h">${X("warehouse")}<b>${SE.edit==="new"?"مخزن جديد":"تعديل المخزن"}</b><button class="icon-btn" style="margin-inline-start:auto" data-cmk-act="sx">${X("x")}</button></div><div class="fgrid">
  ${FLD("اسم المخزن",`<input data-cmk-chg="sf" data-f="n" value="${esc(f.n)}" class="${e.n?"err":""}" placeholder="مثال: مستودع الأعلاف">`+(e.n?`<small class="cmk-err">${X("circle-alert")}${e.n}</small>`:""),1)}
  ${FLD("الفرع",`<select data-cmk-chg="sf" data-f="br">${D.branches.map(b=>`<option value="${b[0]}" ${f.br===b[0]?"selected":""}>${b[1]}</option>`).join("")}</select>`,1)}
  ${FLD("مركز التكلفة",`<select data-cmk-chg="sf" data-f="cc">${CCS.map(c=>`<option ${f.cc===c?"selected":""}>${c}</option>`).join("")}</select>`,1)}
  ${FLD("حساب المخزون",`<select data-cmk-chg="sf" data-f="acc" class="${e.acc?"err":""}">${ACC.map(a=>{const p=a.endsWith("|parent"),t=a.replace("|parent","");return `<option value="${t}" ${f.acc===t?"selected":""}>${t}${p?" — رئيسي":""}</option>`}).join("")}</select>`+(e.acc?`<small class="cmk-err">${X("circle-alert")}${e.acc}</small>`:`<small class="fhint">حسابات فرعية فقط — الحساب الرئيسي لا يقبل قيوداً</small>`),1)}
  <div class="fld full cmk-sw"><label class="cmk-swr"><input type="checkbox" data-cmk-chg="sf" data-f="def" ${f.def?"checked":""}><span><b>المخزن الافتراضي للفرع</b><small>يُختار تلقائياً في الفواتير ونقاط البيع</small></span></label><label class="cmk-swr"><input type="checkbox" data-cmk-chg="sf" data-f="neg" ${f.neg?"checked":""}><span><b>السماح بالرصيد السالب</b><small>للبيع الأوفلاين من الكاشير. غير مسموح = تُمنع الفاتورة عند نقص الرصيد</small></span></label></div></div>
  <div class="cmk-newf"><div class="cm-acts">${B("primary","save","حفظ المخزن",'data-cmk-act="ssave"')}${B("ghost","","إلغاء",'data-cmk-act="sx"')}</div><small class="cm-sub">المخزن الذي عليه حركة لا يُحذف، يُوقف فقط.</small></div></div>`:"";
 const bad=list.reduce((a,s)=>a+storeStats(s[0]).neg,0),low=list.reduce((a,s)=>a+storeStats(s[0]).low,0);
 const body=`${bad||low?CM.note(`<b>ماذا أفعل الآن؟</b> ${[bad?`<b>${bad}</b> رصيد سالب في المخازن`:"",low?`<b>${low}</b> منتج تحت حد الطلب`:""].filter(Boolean).join(" · ")} — <a href="#" data-go="cmStock">افتح أرصدة المخزون</a>`,bad?"bad":"warn"):""}${form}
 <div class="cmk-stores">${list.map(s=>{const st=storeStats(s[0]),br=D.branches.find(b=>b[0]===s[2]);return `<div class="card cmk-sc"><div class="cmk-sh"><span class="cmk-si">${X("warehouse")}</span><div><b>${s[1]}</b><small>${br?br[1]:""}</small></div><div class="cmk-tags">${s[5]?`<span class="cm-st cm-s-live">${X("star")}<span>افتراضي</span></span>`:""}</div></div>
  <div class="cmk-sg"><div><small>المنتجات</small><b class="num">${st.n}</b></div><div><small>القيمة</small>${cost?`<b>${CM.m(st.val)}</b>`:`<b class="cm-hid" title="بدون صلاحية رؤية التكلفة">—</b>`}</div><div><small>تحت الحد</small><b class="num ${st.low?"w":""}">${st.low}</b></div><div><small>سالب</small><b class="num ${st.neg?"r":""}">${st.neg}</b></div></div>
  <div class="cmk-sm"><span>${X("network")}${s[3]}</span><span>${X("book")}${s[4]}</span><span>${X(s[6]?"shield-alert":"shield-check")}${s[6]?"يسمح بالسالب":"لا يسمح بالسالب"}</span></div>
  <div class="cmk-sa"><button class="btn btn-ghost sm" data-cmk-act="sedit" data-v="${s[0]}">${X("pencil")}تعديل</button><button class="btn btn-ghost sm" data-go="cmStock" data-cmk-act="sfilt" data-v="${s[0]}">${X("boxes")}الأرصدة</button></div></div>`}).join("")}
  <button class="card cmk-sc cmk-add" data-cmk-act="snew">${X("plus")}<b>مخزن جديد</b><small>${list.length} من ${CM.cfg().limits.stores} مخازن مسموحة</small></button></div>`;
 return `${HEAD("قائمة المخازن","كل مخزن مرتبط بفرع ومركز تكلفة وحساب مخزون خاص به.",B("primary","plus","مخزن جديد",'data-cmk-act="snew"'))}${CM.pvbar({st:1})}${CM.view(body,CM.empty("warehouse","لا مخازن بعد","أضف أول مخزن لتبدأ تسجيل الأرصدة.",B("primary","plus","مخزن جديد",'data-cmk-act="snew"')))}`};

Object.assign(T,{cmStock:{ar:"الكميات في المخزن",en:"Stock balances"},cmDocs:{ar:"إدخال وإخراج بضاعة",en:"Stock documents"},cmDocShow:{ar:"إدخال أو إخراج بضاعة",en:"Stock document"},cmCount:{ar:"الجرد",en:"Stock count"},cmCountRun:{ar:"شاشة العدّ",en:"Counting"},cmStores:{ar:"قائمة المخازن",en:"Stores"}});
Object.keys(S).forEach(k=>{screens[k]=S[k]});

/* ============ التفاعل ============ */
const findItem=v=>{v=String(v||"").trim();if(!v)return null;const lo=v.toLowerCase();
 for(const it of stockItems()){const u=it.units.find(x=>x[3]&&x[3]===v);if(u)return {it,u:u[0]}}
 const it=stockItems().find(i=>i.code.toLowerCase()===lo)||stockItems().find(i=>i.n.includes(v));return it?{it,u:it.units[0][0]}:null};
const addLine=(f,q)=>{const d=DOC,ex=d.lines.find(l=>l.i===f.it.id&&l.u===f.u&&!l.b);if(ex){ex.q=Math.round((+ex.q+(q||1))*1e4)/1e4;return}
 d.lines.push(L(f.it.id,f.u,q||1,{c:(d.type==="open"||d.type==="receipt")?Math.round(f.it.cost*unitF(f.it,f.u)*100)/100:0}))};
const docScan=inp=>{const f=findItem(inp.value);delete DE.scan;delete DE.lines;
 if(!f)DE.scan="لا يوجد منتج بهذا الباركود أو الاسم: «"+esc(inp.value)+"».";else{addLine(f,1);persist()}
 CM.re();const n=document.querySelector("[data-cmk-scan=doc]");if(n)n.focus()};
const cntScan=inp=>{const c=curCount(),v=String(inp.value||"").trim();if(!v)return;
 let hit=null;for(const l of c.lines){const it=D.item(l.i),u=it.units.find(x=>x[3]&&x[3]===v);if(u){hit=[l,unitF(it,u[0])];break}if(it.code.toLowerCase()===v.toLowerCase()||it.n.includes(v)){hit=[l,1];break}}
 if(!hit)CR.err="!لا يوجد منتج بهذا الباركود ضمن نطاق هذا الجرد.";else{CR.err="";hit[0].cnt=Math.round(((hit[0].cnt||0)+hit[1])*1e4)/1e4;CR.last=c.lines.indexOf(hit[0]);const a=counts();a[a.findIndex(x=>x.id===c.id)]=c;saveCounts(a)}
 CM.re();const n=document.querySelector("[data-cmk-scan=count]");if(n)n.focus()};
const saveCount=c=>{const a=counts();a[a.findIndex(x=>x.id===c.id)]=c;saveCounts(a)};
const jvNo=()=>"JV-2611-"+String(Math.floor(70+Math.random()*60)).padStart(4,"0");
const go=p=>{location.href=p+".html"};
const copyOf=d=>({type:d.type,store:d.store,to:d.to,reason:"",lines:d.lines,no:d.no});

if(!window.__cmStock){window.__cmStock=1;
 document.addEventListener("click",e=>{
  const a=e.target.closest("[data-cmk-act]");if(!a)return;
  if(a.disabled){e.preventDefault();e.stopImmediatePropagation();return}
  const act=a.dataset.cmkAct,v=a.dataset.v,nav=a.hasAttribute("data-go");let re=!nav;
  const stop=()=>{e.preventDefault();e.stopImmediatePropagation();re=1};
  if(!nav)e.preventDefault();
  switch(act){
   case"role":sv("cmk.role",v);DOC=null;break;
   case"sst":SK.st=SK.st===v&&v!=="all"?"all":v;break;
   case"sclr":Object.assign(SK,{store:"all",cat:"all",q:"",st:"all"});break;
   case"item":sv("cm.item",+v);sv("cmk.item",+v);break;
   case"sfilt":SK.store=v;break;
   case"dst":DL.st=v;break;case"dty":DL.type=v;break;case"menu":DL.menu=!DL.menu;break;
   case"newdoc":sv("cmk.cur",{new:v});DOC=null;break;
   case"open":{const[id,fl]=v.split(":");sv("cmk.cur",{id,post:fl==="post"});DOC=null;break}
   case"qa":{const[id,k]=v.split(":"),arr=docs(),d=arr.find(x=>x.id===id);if(!d)break;
    if(k==="approve"){d.status=d.type==="transfer"?"sent":"approved";d.appr=WHO[role()];d.log.push([NOW,WHO[role()],d.type==="transfer"?"اعتمد وأرسل":"اعتمد"]);CM.toast("اعتُمد المستند "+d.no)}
    else{d.status="cancelled";d.log.push([NOW,WHO[role()],"ألغى المستند"]);CM.toast("أُلغي المستند "+d.no,"x")}saveDocs(arr);break}
   /* --- مستند --- */
   case"ptype":DOC.type=v;DOC.to=v==="transfer"?(DOC.to||"s2"):"";DOC.reason="";DE={};break;
   case"addscan":{const i=document.querySelector("[data-cmk-scan=doc]");docScan(i||{value:""});re=0;break}
   case"import":["103","107","111"].forEach(id=>addLine({it:D.item(id),u:D.item(id).units[0][0]},10));delete DE.lines;persist();CM.toast("استُوردت 3 منتجات من ملف Excel");break;
   case"rm":DOC.lines.splice(+v,1);DE={};persist();break;
   case"photo":DOC.photo=DOC.photo?"":"waste-"+DOC.no.toLowerCase()+"-photo.jpg";if(DOC.photo==="waste--photo.jpg")DOC.photo="waste-photo.jpg";delete DE.photo;persist();break;
   case"save":{if(!DOC.no)DOC.no=nextNo(DOC.type);DOC.saved=1;logd(DOC.log.length?"حفظ المسودة":"أنشأ المسودة");persist();CM.toast("حُفظت المسودة "+DOC.no);break}
   case"del":{const arr=docs().filter(x=>x.id!==DOC.id);saveDocs(arr);sv("cmk.cur",null);go("cmDocs");re=0;break}
   case"approve":{const er=validate(DOC);if(Object.keys(er).length){DE=er;DE._top="لم يُعتمد المستند. صحّح الحقول المعلّمة بالأحمر.";break}
    if(!DOC.no)DOC.no=nextNo(DOC.type);DOC.saved=1;DOC.status=DOC.type==="transfer"?"sent":"approved";DOC.appr=WHO[role()];DE={};logd(DOC.type==="transfer"?"اعتمد وأرسل إلى "+stName(DOC.to):"اعتمد");persist();CM.toast(DOC.type==="transfer"?"أُرسل التحويل بانتظار استلام "+stName(DOC.to):"اعتُمد المستند، وينتظر الترحيل");break}
   case"post":DUI.cp=1;DE={};break;case"postno":DUI.cp=0;break;
   case"postok":{if(DOC.date<=LOCK){DE={_top:"التاريخ "+DOC.date+" يقع في فترة محاسبية مقفلة. غيّر التاريخ ثم أعد المحاولة."};CM.toast("الفترة المحاسبية مقفلة","lock");break}
    DOC.status="posted";DOC.entry=jvNo();DE={};DUI.cp=0;logd("رحّل القيد "+DOC.entry);persist();CM.toast("رُحّل المستند وأُنشئ القيد "+DOC.entry,"book-check");break}
   case"cancel":DOC.status="cancelled";logd("ألغى المستند");persist();CM.toast("أُلغي المستند","x");break;
   case"recv":{DOC.lines.forEach(l=>{if(l.r==null)l.r=l.q});const df=DOC.lines.filter(l=>+l.r!==+l.q).length;DOC.status="received";logd("استلم"+(df?" مع "+df+" فرق في الكمية":" كامل الكميات"));persist();CM.toast("تم الاستلام"+(df?" مع فروق":""));break}
   case"rej":DUI.rej=1;break;case"rejno":DUI.rej=0;DE={};break;
   case"rejok":{if(!DUI.rejReason.trim()){DE={rej:"اكتب سبب الرفض."};break}DOC.status="rejected";DOC.rejReason=DUI.rejReason;DUI.rej=0;DE={};logd("رفض التحويل: "+DUI.rejReason);persist();CM.toast("رُفض التحويل","x-circle");break}
   case"rev":sv("cmk.cur",{rev:copyOf(DOC)});go("cmDocShow");re=0;break;
   case"copy":sv("cmk.cur",{copy:copyOf(DOC)});go("cmDocShow");re=0;break;
   /* --- جرد --- */
   case"cnew":CN.open=+v;CN.err="";break;
   case"cstart":{if(CN.scope==="sel"&&!CN.cats.length){CN.err="اختر قسماً واحداً على الأقل.";stop();break}
    const arr=counts(),id="c"+Date.now(),scope=CN.scope==="all"?"all":CN.cats.slice(),n=Math.max(...arr.map(c=>+c.no.split("-")[1]))+1;
    arr.unshift({id,no:"CT-"+String(n).padStart(4,"0"),store:CN.store,scope,snap:NOW,mode:CN.mode,status:"counting",by:WHO[role()],lines:genLines(CN.store,scope,"")});saveCounts(arr);sv("cmk.ccur",id);CR.f="all";CR.last=null;break}
   case"copen":sv("cmk.ccur",v);CR.f="all";CR.last=null;CR.cp=0;CR.err="";break;
   case"cflt":CR.f=v;break;
   case"cscan":{const i=document.querySelector("[data-cmk-scan=count]");cntScan(i||{value:""});re=0;break}
   case"cinc":{const[i,dl]=v.split(":"),c=curCount(),l=c.lines[+i];l.cnt=Math.max(0,Math.round(((l.cnt==null?(+dl>0?0:l.exp):l.cnt)+ +dl)*1e4)/1e4);CR.last=+i;saveCount(c);break}
   case"csubmit":{const c=curCount(),unc=c.lines.length-cprog(c).done;if(unc&&!CR.ack){CR.err="أكّد أن المنتجات غير المعدودة لن تتغير أرصدتها.";break}c.status="submitted";saveCount(c);CR.err="";CM.toast("أُرسل الجرد لاعتماد مدير الفرع");break}
   case"capprove":{const c=curCount();c.status="approved";c.appr=WHO[role()];saveCount(c);CM.toast("اعتُمد الجرد وينتظر الترحيل");break}
   case"cpost":CR.cp=1;break;case"cpostno":CR.cp=0;break;
   case"cpostok":{const c=curCount();c.status="posted";c.entry=jvNo();saveCount(c);CR.cp=0;
    const dl=c.lines.filter(l=>l.cnt!=null&&l.cnt!==l.exp).map(l=>L(l.i,D.item(l.i).units[0][0],Math.round((l.cnt-l.exp)*1e4)/1e4));
    const arr=docs();arr.unshift({id:"dc"+c.id,type:"count",no:c.no,date:TODAY,store:c.store,reason:"فرق جرد",status:"posted",by:c.by,appr:c.appr,entry:c.entry,saved:1,lines:dl,log:[[NOW,c.by,"أنهى العدّ"],[NOW,c.appr||"",
 "اعتمد"],[NOW,WHO[role()],"رحّل القيد "+c.entry]]});saveDocs(arr);CM.toast("رُحّل الجرد وتغيّرت الأرصدة","book-check");break}
   case"cdoc":sv("cmk.cur",{id:"dc"+curCount().id});break;
   /* --- مخازن --- */
   case"snew":SE.edit="new";SE.f={n:"",br:"b1",cc:CCS[0],acc:ACC[0],def:0,neg:0};SE.err={};break;
   case"sedit":{const s=store(v);SE.edit=v;SE.f={n:s[1],br:s[2],cc:s[3],acc:s[4],def:!!s[5],neg:!!s[6]};SE.err={};break}
   case"sx":SE.edit=null;break;
   case"ssave":{const f=SE.f,er={};if(!f.n.trim())er.n="اسم المخزن مطلوب.";if(ACC.includes(f.acc+"|parent"))er.acc="هذا حساب رئيسي. اختر حساب مخزون فرعياً.";SE.err=er;if(Object.keys(er).length)break;
    const arr=D.stores.map(s=>s.slice()),row=[SE.edit==="new"?"s"+(arr.length+1):SE.edit,f.n.trim(),f.br,f.cc,f.acc,f.def?1:0,f.neg?1:0];
    if(f.def)arr.forEach(s=>{if(s[2]===f.br)s[5]=0});
    if(SE.edit==="new")arr.push(row);else arr[arr.findIndex(s=>s[0]===SE.edit)]=row;
    D.stores.length=0;arr.forEach(s=>D.stores.push(s));sv("cmk.stores",arr);SE.edit=null;CM.toast("حُفظ المخزن «"+row[1]+"»");break}
   default:re=0;
  }
  if(re)CM.re();
 },true);
 document.addEventListener("change",e=>{const a=e.target.closest("[data-cmk-chg]");if(!a)return;const k=a.dataset.cmkChg,v=a.type==="checkbox"?a.checked:a.value;let re=1;
  switch(k){
   case"sq":SK.q=v.trim();break;case"ss":SK.store=v;break;case"sc":SK.cat=v;break;case"dq":DL.q=v.trim();break;
   case"df":{DOC[a.dataset.f]=v;delete DE[a.dataset.f];delete DE._top;persist();break}
   case"ln":{const l=DOC.lines[+a.dataset.i],f=a.dataset.f;if(f==="q"||f==="c"||f==="r")l[f]=v===""?"":+v;else l[f]=v;
    if(f==="u"&&(DOC.type==="open"||DOC.type==="receipt")){const it=D.item(l.i);l.c=Math.round(it.cost*unitF(it,v)*100)/100}
    delete DE[f[0]+a.dataset.i];persist();break}
   case"rejr":DUI.rejReason=v;re=0;break;
   case"cn":{const f=a.dataset.f;if(f==="cat"){const i=CN.cats.indexOf(a.value);if(i<0)CN.cats.push(a.value);else CN.cats.splice(i,1);CN.err=""}else CN[f]=v;break}
   case"cnt":{const c=curCount(),l=c.lines[+a.dataset.i];l.cnt=v===""?null:Math.max(0,+v);CR.last=+a.dataset.i;saveCount(c);break}
   case"cack":CR.ack=v;CR.err="";break;
   case"cmode":{const c=curCount();c.mode=a.value;saveCount(c);break}
   case"sf":{const f=SE.f[a.dataset.f==="def"||a.dataset.f==="neg"?a.dataset.f:a.dataset.f];SE.f[a.dataset.f]=v;re=a.type==="checkbox"?0:1;if(a.dataset.f==="acc"||a.dataset.f==="n")delete SE.err[a.dataset.f];break}
  }
  if(re)CM.re()});
 document.addEventListener("keydown",e=>{if(e.key!=="Enter")return;const a=e.target.closest("[data-cmk-scan]");if(!a)return;e.preventDefault();a.dataset.cmkScan==="doc"?docScan(a):cntScan(a)},true);
 /* إغلاق قائمة «مستند جديد» عند النقر خارجها */
 document.addEventListener("click",e=>{if(DL.menu&&!e.target.closest(".cmk-menuw")){DL.menu=false;if(window.CERP_PAGE==="cmDocs")CM.re()}});
}
});
