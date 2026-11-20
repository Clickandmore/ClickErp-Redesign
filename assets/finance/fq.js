/* ClickERP — المالية ← عروض الأسعار (v1.77). للنظام العام (خدمي وتجاري).
   المصدر: ClickERP2 packages/finance — SalesDocument (kind=quotation، جدول fn_sales_documents) + SalesDocumentLine + SalesDocumentTemplate + SalesDocumentSettings.
   الحالات من SalesDocument::STATUSES: مسودة، مُرسل للعميل، مقبول، مرفوض، تحوّل لفاتورة. التحويل = SalesDocumentController@convert ← مسودة فاتورة عميل.
   الأشكال: مستند (_document) · عرض فني ومالي (_proposal + الأقسام) · شرائح (_slides). الإعدادات: صلاحية العرض 15 يوماً، الشروط الافتراضية، الختام، الترقيم.
   «جديد» (مش في الكود): «ماذا أفعل الآن»، التذكير بالصلاحية، تسجيل سبب الرفض كقائمة، ومتابعة فتح العميل للرابط. */
(window.CERP_EXT=window.CERP_EXT||[]).push(()=>{
const {X,FLD,SEL,INP,HEAD,B,TABS,PAGER,KV}=window.CXH;
const CM=window.CM,S={};
const esc=s=>String(s==null?"":s).replace(/[&<>"]/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;"}[c]));
const COM=()=>CM.cfg().acct==="commercial";
const QST={draft:["مسودة","draft"],sent:["مُرسل للعميل","wait"],accepted:["مقبول","done"],rejected:["مرفوض","bad"],converted:["تحوّل لفاتورة","done"],expired:["انتهت صلاحيته","void"]};
const qst=s=>{const k={draft:"draft",sent:"approved",accepted:"accepted",rejected:"rejected",converted:"posted",expired:"expired"}[s];return CM.st(k,QST[s][0])};
const SET={title:"عرض الأسعار",valid:15,terms:"الدفعات المالية:\n- 50٪ عند التعميد، و50٪ عند التسليم.\n- التحويل على مصرف الراجحي — شركة الريادة للخدمات.\nالتعميد:\nيُعد عرض السعر ملزماً عند الموافقة عليه، ويتم التعميد كتابياً.\nالأسعار:\nالأسعار شاملة ضريبة القيمة المضافة 15٪ ووفق الكميات المذكورة.",closing:"وتفضلوا بقبول فائق الاحترام والتقدير،\nونسعد بخدمتكم ونتطلع لاعتمادكم الكريم."};
const CLIENTS=[["c1","شركة نجد للمقاولات","300145521900003","الرياض"],["c2","مجمع عيادات الشفاء","310998765400003","الرياض"],["c3","فندق الياسمين","302211445500003","جدة"],["c4","مؤسسة الوفاء التجارية","","الدمام"],["c5","شركة المدار للتجارة","300778899100003","الرياض"]];
const SVC=["عقد صيانة","خدمة لمرة واحدة","اشتراك شهري","توريد وتركيب"];
/* البنود: [الوصف, نوع التعاقد, المدة, الكمية, سعر الوحدة, الخصم, منتج (للتجاري)] */
const L=(d,t,du,q,p,dc,it)=>({d,t,du,q,p,dc:dc||0,it:it||0});
const QS=[
 {id:110,no:"QT-00110",c:"c1",sub:"صيانة وقائية سنوية لأنظمة التكييف — 3 مواقع",date:"2026-11-18",vd:15,st:"sent",cc:"CC-02 مشاريع الصيانة",fmt:"proposal",by:"ريم الدوسري",sent:"2026-11-18 11:20",opened:"2026-11-19 09:05",lines:[L("صيانة وقائية ربع سنوية لوحدات التكييف المركزي","عقد صيانة","12 شهراً",3,14500,1500),L("زيارات طوارئ غير محدودة خلال ساعات العمل","عقد صيانة","12 شهراً",3,3200),L("تقرير فني ربع سنوي بحالة الأجهزة","خدمة لمرة واحدة","",4,450)],secs:[["نطاق العمل","فحص وتنظيف وحدات التكييف المركزي والمنفصل في المواقع الثلاثة، واستبدال الفلاتر، وقياس ضغط الغاز، ورفع تقرير بالحالة."],["الجدول الزمني","أربع زيارات وقائية في السنة، أولها خلال 7 أيام من التعميد."]]},
 {id:109,no:"QT-00109",c:"c2",sub:"تنظيف يومي لمبنى العيادات",date:"2026-11-12",vd:15,st:"accepted",cc:"CC-01 خدمات التنظيف",fmt:"document",by:"خالد المطيري",sent:"2026-11-12 13:00",opened:"2026-11-12 16:40",acc:"2026-11-16",lines:[L("تنظيف يومي للمبنى (6 أيام في الأسبوع) — طاقم 6 أفراد","اشتراك شهري","6 أشهر",6,18500),L("تعقيم شهري للعيادات وغرف الانتظار","اشتراك شهري","6 أشهر",6,2400)],secs:[]},
 {id:108,no:"QT-00108",c:"c3",sub:"تركيب كاميرات مراقبة — 24 كاميرا",date:"2026-11-08",vd:15,st:"sent",cc:"CC-03 الأنظمة الأمنية",fmt:"slides",by:"ريم الدوسري",sent:"2026-11-08 10:15",opened:"",lines:[L("كاميرا مراقبة IP بدقة 4MP مع التمديد","توريد وتركيب","",24,640,0,1),L("جهاز تسجيل شبكي 32 قناة + قرص 8TB","توريد وتركيب","",1,4800,0,1),L("ضمان وصيانة لمدة سنة","عقد صيانة","12 شهراً",1,3600)],secs:[]},
 {id:107,no:"QT-00107",c:"c5",sub:"تنظيف واجهات زجاجية — برجان",date:"2026-10-30",vd:15,st:"converted",cc:"CC-01 خدمات التنظيف",fmt:"document",by:"خالد المطيري",inv:"INV-DRAFT-124",lines:[L("تنظيف واجهات زجاجية بالحبال — البرج A","خدمة لمرة واحدة","",1,22000),L("تنظيف واجهات زجاجية بالحبال — البرج B","خدمة لمرة واحدة","",1,19500,1500)],secs:[]},
 {id:106,no:"QT-00106",c:"c4",sub:"مكافحة حشرات للمستودعات",date:"2026-10-21",vd:15,st:"rejected",cc:"CC-04 مكافحة الحشرات",fmt:"document",by:"ريم الدوسري",why:"السعر أعلى من عرض منافس",lines:[L("رش وقائي شهري للمستودعات (3 مستودعات)","اشتراك شهري","12 شهراً",12,2900)],secs:[]},
 {id:111,no:"",c:"c2",sub:"صيانة مصاعد — مبنى العيادات",date:"2026-11-20",vd:15,st:"draft",cc:"CC-02 مشاريع الصيانة",fmt:"document",by:"ريم الدوسري",lines:[L("صيانة شهرية لمصعدين","عقد صيانة","12 شهراً",12,1150)],secs:[]},
 {id:105,no:"QT-00105",c:"c1",sub:"تنظيف ما بعد التشطيب — فيلا",date:"2026-10-25",vd:15,st:"expired",cc:"CC-01 خدمات التنظيف",fmt:"document",by:"خالد المطيري",sent:"2026-10-25 09:00",opened:"2026-10-25 12:30",lines:[L("تنظيف شامل ما بعد التشطيب","خدمة لمرة واحدة","",1,7800)],secs:[]}];
/* الحالة تُحفظ في الجلسة: نفس العروض في القائمة والعرض والإضافة */
const SK="fq.state.v1";
try{const s=JSON.parse(sessionStorage.getItem(SK));if(s&&Array.isArray(s))s.forEach(x=>{const i=QS.findIndex(q=>q.id===x.id);if(i>-1)QS[i]=x;else QS.unshift(x)})}catch(e){}
const save=()=>{try{sessionStorage.setItem(SK,JSON.stringify(QS))}catch(e){}};
const TODAY=new Date("2026-11-20");
const cli=id=>CLIENTS.find(c=>c[0]===id)||CLIENTS[0];
const ln=l=>{const g=l.q*l.p-l.dc;return {g,tax:g*0.15,t:g*1.15}};
const tot=q=>q.lines.reduce((a,l)=>{const x=ln(l);a.sub+=l.q*l.p;a.dc+=l.dc;a.tax+=x.tax;a.t+=x.t;return a},{sub:0,dc:0,tax:0,t:0});
const exp=q=>{const d=new Date(q.date);d.setDate(d.getDate()+(+q.vd||0));return d};
const left=q=>Math.ceil((exp(q)-TODAY)/864e5);
const fd=d=>`<span class="num" dir="ltr">${d.toISOString().slice(0,10)}</span>`;
const qTabs=on=>TABS([["finQuotes","file-signature","عروض الأسعار",QS.length],["finSales","receipt","الفواتير"]],on);
const open=id=>{try{sessionStorage.setItem("fqId",String(id))}catch(e){}};
const cur=()=>{let id=+new URLSearchParams(location.search).get("q");if(!id)try{id=+sessionStorage.getItem("fqId")}catch(e){}return QS.find(q=>q.id===id)||QS[0]};

/* =========================================================
   القائمة
   ========================================================= */
const F={st:"",q:""};
S.finQuotes=()=>{const all=QS,cnt=k=>all.filter(q=>q.st===k).length;
 const openQ=all.filter(q=>q.st==="sent"),soon=openQ.filter(q=>left(q)<=3&&left(q)>=0),noReply=openQ.filter(q=>!q.opened),accNoInv=all.filter(q=>q.st==="accepted");
 const list=all.filter(q=>(!F.st||q.st===F.st)&&(!F.q||(q.sub+cli(q.c)[1]+q.no).includes(F.q)));
 const won=all.filter(q=>q.st==="accepted"||q.st==="converted"),dec=won.length+cnt("rejected");
 return `${HEAD("عروض الأسعار","عروض للعملاء بالخدمات"+(COM()?" والمنتجات":"")+"، ومتابعتها حتى القبول ثم التحويل إلى فاتورة.",B("ghost","settings-2","إعدادات المستندات",'data-fq-set=""')+B("primary","plus","عرض سعر جديد",'data-fq-new=""'),null)}
 ${qTabs("finQuotes")}
 <div class="fq-attn">${[["alarm-clock","a",`${soon.length} عرض تنتهي صلاحيته خلال 3 أيام`,soon.map(q=>q.no).join("، ")||"لا يوجد","sent"],["mail-question","n",`${noReply.length} عرض مُرسل لم يفتحه العميل`,noReply.map(q=>cli(q.c)[1]).join("، ")||"لا يوجد","sent"],["file-check","g",`${accNoInv.length} عرض مقبول لم يتحول لفاتورة`,accNoInv.map(q=>q.no).join("، ")||"لا يوجد","accepted"]].map(a=>`<button class="fq-at ${a[1]}" data-fq-st="${a[4]}">${X(a[0])}<span><b>${a[2]}</b><small>${a[3]}</small></span>${X("chevron-left")}</button>`).join("")}</div>
 <div class="cm-kpis">${[["file-signature","العروض المفتوحة",CM.m(openQ.reduce((a,q)=>a+tot(q).t,0)),openQ.length+" عروض مُرسلة"],["trophy","نسبة القبول — آخر 30 يوماً",`<span class="num">${dec?Math.round(won.length/dec*100):0}%</span>`,`${won.length} مقبولة من ${dec} قرارات`],["timer","متوسط أيام القرار",'<span class="num">4.2</span> <small class="cur">يوم</small>',"من الإرسال إلى القبول أو الرفض"],["receipt","تحوّل لفواتير هذا الشهر",CM.m(all.filter(q=>q.st==="converted").reduce((a,q)=>a+tot(q).t,0)),cnt("converted")+" عرض"]].map(k=>`<div class="card cm-kpi"><span>${X(k[0])}${k[1]}</span><b>${k[2]}</b><small>${k[3]}</small></div>`).join("")}</div>
 <div class="cx-chips">${[["","الكل",all.length],["draft","مسودة",cnt("draft")],["sent","مُرسل للعميل",cnt("sent")],["accepted","مقبول",cnt("accepted")],["converted","تحوّل لفاتورة",cnt("converted")],["rejected","مرفوض",cnt("rejected")],["expired","انتهت صلاحيته",cnt("expired")]].map(c=>`<button class="${F.st===c[0]?"on":""}" data-fq-st="${c[0]}">${c[1]}<b class="num">${c[2]}</b></button>`).join("")}</div>
 <div class="fbar"><div class="fb grow"><label>بحث</label><div class="inp">${X("search")}<input data-fq-q="" value="${esc(F.q)}" placeholder="رقم العرض أو العميل أو الموضوع…"></div></div><div class="fb"><label>المشروع / مركز التكلفة</label>${SEL(["الكل","CC-01 خدمات التنظيف","CC-02 مشاريع الصيانة","CC-03 الأنظمة الأمنية","CC-04 مكافحة الحشرات"])}</div><div class="fb"><label>أعدّه</label>${SEL(["الكل","ريم الدوسري","خالد المطيري"])}</div></div>
 ${list.length?`<div class="card flush"><div class="tw"><table class="fq-tbl"><thead><tr><th>العرض</th><th>العميل والموضوع</th><th>التاريخ والصلاحية</th><th>الإجمالي</th><th>الحالة</th><th class="act"></th></tr></thead><tbody>
 ${list.map(q=>{const t=tot(q),lf=left(q);return `<tr class="fq-row" data-fq-open="${q.id}"><td>${q.no?`<b class="cm-mono">${q.no}</b>`:"<b>مسودة</b>"}<small class="cm-sub">${{document:"مستند",proposal:"عرض فني ومالي",slides:"شرائح"}[q.fmt]}</small></td>
 <td><b>${cli(q.c)[1]}</b><small class="cm-sub">${q.sub}</small></td>
 <td><span class="num">${q.date}</span><small class="cm-sub">${q.st==="sent"?(lf<0?`<span class="cm-flag bad">انتهت</span>`:lf<=3?`<span class="cm-flag">${X("alarm-clock")}باقي ${lf} أيام</span>`:`صالح حتى ${fd(exp(q))}`):q.st==="expired"?`انتهت ${fd(exp(q))}`:q.st==="accepted"?`قُبل ${q.acc||""}`:""}</small></td>
 <td>${CM.m(t.t)}<small class="cm-sub">منها ضريبة ${CM.n(t.tax)}</small></td>
 <td>${qst(q.st)}${q.st==="sent"?`<small class="cm-sub">${q.opened?"فتحه العميل "+q.opened.slice(5,16):"لم يُفتح بعد"}</small>`:q.st==="converted"?`<small class="cm-sub cm-mono">${q.inv}</small>`:q.st==="rejected"?`<small class="cm-sub">${q.why}</small>`:""}</td>
 <td class="act"><div class="rowacts">${q.st==="accepted"?B("primary sm","receipt","تحويل لفاتورة",`data-fq-act="convert:${q.id}"`):q.st==="draft"?B("ghost sm","send","إرسال",`data-fq-act="send:${q.id}"`):""}<button class="icon-btn pri" title="عرض" data-fq-open="${q.id}">${X("eye")}</button></div></td></tr>`}).join("")}
 </tbody></table></div>${PAGER(`عرض ${list.length} من ${all.length} عرض`)}</div>`:CM.nores()}
 ${F.set?setPanel():""}`};
const setPanel=()=>`<div class="fq-ov" data-fq-ov=""><div class="card fq-dlg"><div class="cm-sec-h">${X("settings-2")}<b>إعدادات مستندات المبيعات</b><div class="cm-acts"><button class="icon-btn" data-fq-set="">${X("x")}</button></div></div>
 <div class="fgrid">${FLD("عنوان عرض السعر",INP("",SET.title))}${FLD("صلاحية العرض (أيام)",INP("","15",1),0,"0 = بدون تاريخ صلاحية")}${FLD("الرقم التالي",INP("","110",1),0,"لا يقل عن آخر رقم مستخدم")}${FLD("عدد خانات الرقم",INP("","5",1),0,"5 ⇒ 00110")}${FLD("الشكل الافتراضي",SEL(["مستند","عرض فني ومالي","شرائح"]))}</div>
 ${FLD("الشروط الافتراضية لعروض الأسعار",`<textarea rows="6">${SET.terms}</textarea>`,0,"",1)}${FLD("الختام (يُطبع في آخر كل مستند)",`<textarea rows="2">${SET.closing}</textarea>`,0,"",1)}
 <div class="cmi-foot"><span></span><div class="cm-acts">${B("ghost","","إلغاء",'data-fq-set=""')}${B("primary","save","حفظ الإعدادات",'data-fq-set="" data-cm-toast="حُفظت إعدادات المستندات"')}</div></div></div></div>`;

/* =========================================================
   عرض سعر جديد / تعديل
   ========================================================= */
let A=null;
const newA=()=>({id:0,no:"",c:"",sub:"",date:"2026-11-20",vd:15,st:"draft",cc:"CC-02 مشاريع الصيانة",fmt:"proposal",by:"مشرف النظام",lines:[L("","عقد صيانة","12 شهراً",1,0)],secs:[["نطاق العمل",""]],terms:SET.terms,err:{}});
const edA=()=>{const q=cur();return Object.assign(JSON.parse(JSON.stringify(q)),{terms:SET.terms,err:{},edit:1})};
const vld=()=>{const e={};if(!A.c)e.c="اختر العميل";if(!A.lines.length||A.lines.every(l=>!l.d))e.lines="أضف بنداً واحداً على الأقل بوصف";A.lines.forEach((l,i)=>{if(l.d&&!(+l.p>0))e["p"+i]="السعر مطلوب"});return e};
const er=k=>A.err[k]?`<small class="cmi-err">${X("alert-circle")}${A.err[k]}</small>`:"";
S.finQuoteAdd=()=>{if(!A){let e=0;try{e=sessionStorage.getItem("fqEdit")==="1";sessionStorage.removeItem("fqEdit")}catch(_){}A=e?edA():newA()}
 const t=tot(A),c=A.c?cli(A.c):null,secs=[["s1","العميل والموضوع"],["s2","البنود"],["s3","العرض الفني"],["s4","الشروط والختام"],["s5","المراجعة قبل الحفظ"]];
 if(A.done)return `${HEAD("تم حفظ العرض","",B("ghost","list","عروض الأسعار",'data-go="finQuotes"'),["finQuotes","عروض الأسعار"])}<div class="card cmi-done">${X("check-circle-2")}<div><h3>${A.done===2?"أُرسل العرض":"حُفظ العرض كمسودة"} <span class="cm-mono">${A.no||"draft-125"}</span></h3><p>${A.done===2?`أُرسل رابط العرض إلى ${c[1]} عبر واتساب والبريد. ستصلك إشعار عند فتحه.`:"يمكنك مراجعته ثم إرساله للعميل."}</p><div class="cm-acts">${B("primary","eye","عرض العرض",`data-fq-open="${A.id}"`)}${B("ghost","plus","عرض جديد",'data-fq-new=""')}</div></div></div>`;
 return `${HEAD(A.edit?`تعديل ${A.no||"المسودة"}`:"عرض سعر جديد","املأ الأقسام بالترتيب، وتتحدث المعاينة والإجماليات مباشرة.",B("ghost","","إلغاء",'data-go="finQuotes"'),["finQuotes","عروض الأسعار"])}
 <div class="cvform"><div class="card cvopts">
  <div class="cvbar-g"><span>شكل المستند</span><div class="seg">${[["document","مستند"],["proposal","عرض فني"],["slides","شرائح"]].map(x=>`<button class="${A.fmt===x[0]?"on":""}" data-fq-fmt="${x[0]}">${x[1]}</button>`).join("")}</div><small class="fhint">${{document:"صفحة A4 بجدول البنود",proposal:"غلاف + أقسام فنية + الجدول المالي",slides:"عرض تقديمي بشرائح"}[A.fmt]}</small></div>
  <div class="steps">${secs.filter(s=>s[0]!=="s3"||A.fmt!=="document").map((s,i)=>`<button class="step${(s[0]==="s1"&&A.c)||(s[0]==="s2"&&A.lines.some(l=>l.d&&l.p>0))?" done":""}" data-fq-step="${s[0]}"><span class="num">${i+1}</span><b>${s[1]}</b></button>`).join("")}</div>
  <div class="cvbar-g col"><span>إجمالي العرض</span><div class="h16-net"><b class="num">${CM.n(t.t)}</b><small>ر.س · قبل الضريبة <b class="num">${CM.n(t.sub-t.dc)}</b> · ضريبة <b class="num">${CM.n(t.tax)}</b></small></div></div>
  ${B("primary","send","حفظ وإرسال للعميل",'data-fq-save="2"')}${B("ghost","save","حفظ كمسودة",'data-fq-save="1"')}
 </div>
 <div class="card cvfields">
  <div class="fs-h" id="fq-s1"><span class="fs-n num">1</span><b>العميل والموضوع</b></div>
  <div class="fgrid">${FLD("العميل",`<select data-fq-f="c" class="${A.err.c?"cmi-bad":""}"><option value="">اختر العميل…</option>${CLIENTS.map(x=>`<option value="${x[0]}"${A.c===x[0]?" selected":""}>${x[1]}</option>`).join("")}</select>${er("c")}`,1,c?(c[2]?`الرقم الضريبي ${c[2]} · ${c[3]}`:`بدون رقم ضريبي · ${c[3]}`):"عميل جديد؟ أضفه من هنا مباشرة")}
   ${FLD("الموضوع",`<div class="inp"><input data-fq-f="sub" value="${esc(A.sub)}" placeholder="مثال: صيانة وقائية سنوية لأنظمة التكييف"></div>`,0,"يظهر في غلاف العرض")}
   ${FLD("التاريخ",`<input type="date" data-fq-f="date" value="${A.date}">`,1)}
   ${FLD("صلاحية العرض (أيام)",`<div class="inp"><input class="num" dir="ltr" data-fq-f="vd" value="${A.vd}"></div>`,0,`صالح حتى ${fd(exp(A))}`)}
   ${FLD("المشروع / مركز التكلفة",SEL([A.cc,"CC-01 خدمات التنظيف","CC-03 الأنظمة الأمنية","عام (بدون مركز)"]))}
   ${FLD("الدفعات",INP("50٪ عند التعميد و50٪ عند التسليم"),0,"تظهر في الجدول المالي")}</div>
  <div class="fs-h" id="fq-s2"><span class="fs-n num">2</span><b>البنود</b></div>
  <div class="fq-lines">
  ${A.lines.map((l,i)=>`<div class="fq-line"><span class="fq-ln num">${i+1}</span><div class="fq-lb">
   <div class="fq-l1">${l.it?`<span class="fq-it">${X("package")}منتج</span>`:""}<div class="inp"><input data-fq-l="${i}:d" value="${esc(l.d)}" placeholder="وصف الخدمة أو البند"></div>${A.lines.length>1?`<button class="icon-btn" title="حذف البند" data-fq-lrm="${i}">${X("trash-2")}</button>`:""}</div>
   <div class="fq-l2"><label><small>نوع التعاقد</small><select data-fq-l="${i}:t">${SVC.map(s=>`<option${s===l.t?" selected":""}>${s}</option>`).join("")}</select></label>
    <label><small>المدة</small><div class="inp"><input data-fq-l="${i}:du" value="${esc(l.du)}" placeholder="—"></div></label>
    <label><small>الكمية</small><div class="inp"><input class="num" dir="ltr" data-fq-l="${i}:q" value="${l.q}"></div></label>
    <label><small>السعر</small><div class="inp${A.err["p"+i]?" cmi-bad":""}"><input class="num" dir="ltr" data-fq-l="${i}:p" value="${l.p||""}" placeholder="0.00"></div></label>
    <label><small>الخصم</small><div class="inp"><input class="num" dir="ltr" data-fq-l="${i}:dc" value="${l.dc||""}" placeholder="0"></div></label>
    <div class="fq-lt"><small>الإجمالي</small><b class="num">${CM.n(ln(l).g)}</b></div></div></div></div>`).join("")}
  <div class="fq-ladd"><button class="cmi-addrow" data-fq-ladd="svc">${X("plus")}بند خدمة</button>${COM()?`<button class="cmi-addrow" data-fq-ladd="item">${X("package")}منتج من المخزون</button>`:""}</div></div>${er("lines")}
  <div class="fq-tot">${[["المجموع",t.sub],["الخصم",-t.dc],["قبل الضريبة",t.sub-t.dc],["القيمة المضافة 15%",t.tax]].map(x=>`<div><span>${x[0]}</span><b>${CM.n(x[1])}</b></div>`).join("")}<div class="big"><span>الإجمالي</span><b>${CM.m(t.t)}</b></div></div>
  ${A.fmt!=="document"?`<div class="fs-h" id="fq-s3"><span class="fs-n num">3</span><b>العرض الفني</b><small class="cmi-muted">اختياري؛ يُطبع كاملاً في «عرض فني ومالي» والشرائح</small></div>
  ${A.secs.map((s,i)=>`<div class="fq-sec"><div class="inp"><input data-fq-s="${i}:0" value="${esc(s[0])}" placeholder="عنوان القسم"></div><textarea rows="3" data-fq-s="${i}:1" placeholder="نص القسم">${esc(s[1])}</textarea><button class="icon-btn" data-fq-srm="${i}">${X("trash-2")}</button></div>`).join("")}
  <button class="cmi-addrow fq-sadd" data-fq-sadd="">${X("plus")}قسم</button>`:""}
  <div class="fs-h" id="fq-s4"><span class="fs-n num">${A.fmt!=="document"?4:3}</span><b>الشروط والختام</b></div>
  ${FLD("الشروط والأحكام",`<textarea rows="7">${esc(A.terms)}</textarea>`,0,"من الإعدادات — تُعدّل لهذا العرض فقط",1)}
  <div class="fs-h" id="fq-s5"><span class="fs-n num">${A.fmt!=="document"?5:4}</span><b>المراجعة قبل الحفظ</b></div>
  <div class="h15-rev"><div class="msec"><div class="msec-h">${X("file-text")}<b>ملخص العرض</b></div><div class="h17-in">${KV([["العميل",c?c[1]:'<span class="h13-err">مطلوب</span>'],["البنود",`<span class="num">${A.lines.filter(l=>l.d).length}</span>`],["الإجمالي",CM.m(t.t)],["صالح حتى",`<span class="num">${fd(exp(A))}</span>`]])}</div></div>
   <div class="msec"><div class="msec-h">${X("scan-search")}<b>الفحص</b></div><div class="h17-in"><div class="fq-chk">${[[!!c,"العميل محدد"],[c&&c[2],c&&c[2]?"للعميل رقم ضريبي — تصدر له فاتورة ضريبية":"العميل بلا رقم ضريبي — فاتورة مبسطة"],[A.lines.every(l=>!l.d||l.p>0),"كل البنود مسعّرة"],[t.dc<=t.sub*0.15,t.dc>t.sub*0.15?"الخصم فوق 15% يحتاج موافقة المدير":"الخصم ضمن الصلاحية"]].map(x=>`<span class="${x[0]?"ok":"warn"}">${X(x[0]?"check-circle-2":"alert-triangle")}${x[1]}</span>`).join("")}</div></div></div>
   <div class="msec"><div class="msec-h">${X("play")}<b>بعد الإرسال</b></div><div class="h17-in"><div class="h8-after">${["يأخذ العرض رقمه التالي (QT-00112)","رابط للعميل يعرض المستند ويقبل أو يرفض","تذكير قبل انتهاء الصلاحية بيومين","لا يُنشأ قيد — القيد مع الفاتورة"].map(x=>`<span>${X("check")}${x}</span>`).join("")}</div></div></div></div>
  <div class="cvactions">${B("primary","send","حفظ وإرسال للعميل",'data-fq-save="2"')}${B("ghost","save","حفظ كمسودة",'data-fq-save="1"')}${B("ghost","","إلغاء",'data-go="finQuotes"')}</div>
 </div></div>`};

/* =========================================================
   تفاصيل العرض
   ========================================================= */
let TAB="doc";
const paper=q=>{const c=cli(q.c),t=tot(q);return `<div class="fq-paper ${q.fmt}">
 <div class="fq-ph"><div class="fq-co"><span class="fq-logo">${X("sparkles")}</span><div><b>${window.CERP_CO}</b><small>س.ت 1010456789 · الرقم الضريبي 310456789000003 · الرياض</small></div></div><div class="fq-pt"><h2>${SET.title}</h2><span class="cm-mono">${q.no||"مسودة"}</span></div></div>
 ${q.fmt!=="document"?`<div class="fq-cover"><small>مقدّم إلى</small><h3>${c[1]}</h3><p>${q.sub}</p></div>`:""}
 <div class="fq-pm"><div><small>العميل</small><b>${c[1]}</b><span>${c[2]?"الرقم الضريبي "+c[2]:""}${c[3]?" · "+c[3]:""}</span></div><div><small>التاريخ</small><b class="num">${q.date}</b><span>صالح حتى ${fd(exp(q))}</span></div><div><small>الموضوع</small><b>${q.sub}</b></div></div>
 ${q.fmt!=="document"&&q.secs.length?q.secs.map(s=>`<div class="fq-psec"><h4>${s[0]}</h4><p>${s[1]}</p></div>`).join(""):""}
 <table class="fq-pt2"><thead><tr><th>#</th><th>الوصف</th><th>نوع التعاقد</th><th>المدة</th><th>الكمية</th><th>السعر</th><th>الإجمالي</th></tr></thead><tbody>${q.lines.map((l,i)=>`<tr><td class="num">${i+1}</td><td>${l.d}</td><td>${l.t}</td><td>${l.du||"—"}</td><td class="num">${l.q}</td><td class="num">${CM.n(l.p)}</td><td class="num">${CM.n(ln(l).g)}</td></tr>`).join("")}</tbody></table>
 <div class="fq-ptot">${[["المجموع",t.sub],["الخصم",-t.dc],["القيمة المضافة 15%",t.tax]].map(x=>`<div><span>${x[0]}</span><b class="num">${CM.n(x[1])}</b></div>`).join("")}<div class="big"><span>الإجمالي شامل الضريبة</span><b class="num">${CM.n(t.t)} ر.س</b></div></div>
 <div class="fq-terms"><h4>الشروط والأحكام</h4><pre>${SET.terms}</pre><p>${SET.closing.replace("\n","<br>")}</p></div></div>`};
S.finQuoteShow=()=>{const q=cur(),c=cli(q.c),t=tot(q),lf=left(q);
 const fl=q.st==="rejected"?[["مسودة",q.by],["مُرسل للعميل",q.sent||""],["مرفوض",q.why||""]]:[["مسودة",q.by],["مُرسل للعميل",q.sent?q.sent.slice(0,10):""],["مقبول",q.acc||""],["تحوّل لفاتورة",q.inv||""]];
 const at={draft:0,sent:1,expired:1,accepted:2,converted:4,rejected:2}[q.st];
 const acts={draft:B("ghost","pencil","تعديل",'data-fq-edit=""')+B("primary","send","إرسال للعميل",`data-fq-act="send:${q.id}"`),
  sent:B("ghost","pencil","تعديل",'data-fq-edit=""')+B("ghost","x","تسجيل الرفض",`data-fq-act="reject:${q.id}"`)+B("primary","check","تسجيل القبول",`data-fq-act="accept:${q.id}"`),
  accepted:B("primary","receipt","تحويل إلى فاتورة",`data-fq-act="convert:${q.id}"`),converted:B("ghost","receipt","فتح الفاتورة",'data-go="finSales"'),
  rejected:B("ghost","copy","نسخ كعرض جديد",`data-fq-act="copy:${q.id}"`),expired:B("ghost","copy","نسخ بتاريخ جديد",`data-fq-act="copy:${q.id}"`)}[q.st];
 const log=[["file-plus","أُنشئ العرض",q.by,q.date]].concat(q.sent?[["send","أُرسل للعميل عبر واتساب والبريد",q.by,q.sent]]:[]).concat(q.opened?[["eye","فتح العميل رابط العرض",c[1],q.opened]]:[]).concat(q.st==="sent"&&!q.opened?[["bell","تذكير تلقائي: لم يُفتح العرض بعد","النظام","2026-11-11 09:00"]]:[]).concat(q.acc?[["check-circle-2","قبل العميل العرض",c[1],q.acc]]:[]).concat(q.why?[["x-circle","رُفض: "+q.why,q.by,"2026-10-27"]]:[]).concat(q.inv?[["receipt","تحوّل إلى مسودة فاتورة "+q.inv,q.by,"2026-11-03"]]:[]);
 return `${HEAD(`${q.no||"مسودة عرض"} <span class="hdrtag">${qst(q.st)}</span>`,`${c[1]} · ${q.sub}`,B("ghost","printer","طباعة / PDF",'data-cm-toast="جُهّز المستند للطباعة"')+B("ghost","share-2","مشاركة الرابط",'data-cm-toast="نُسخ رابط العرض للعميل"')+acts,["finQuotes","عروض الأسعار"])}
 ${CM.flow(fl,at>=4?4:at,q.st==="rejected")}
 ${q.st==="sent"&&lf<=3&&lf>=0?CM.note(`صلاحية العرض تنتهي خلال <b>${lf} أيام</b> (${fd(exp(q))}). أرسل تذكيراً للعميل أو مدّد الصلاحية.`,"warn","alarm-clock"):q.st==="expired"?CM.note(`انتهت صلاحية العرض في ${fd(exp(q))} دون رد. انسخه بتاريخ جديد إذا ما زال العميل مهتماً.`,"","calendar-x"):q.st==="converted"?CM.note(`تحوّل العرض إلى مسودة الفاتورة <b class="cm-mono">${q.inv}</b>. البنود والعميل ومركز التكلفة انتقلت كما هي، والعرض لا يُعدّل بعد التحويل.`,"ok","receipt"):q.st==="accepted"?CM.note("العميل قبل العرض. حوّله إلى فاتورة لإصدارها وتسجيل قيدها.","ok","check-circle-2"):""}
 ${F.ask?`<div class="card fq-ask">${F.ask==="reject"?`<b>سبب الرفض</b><div class="fq-why">${["السعر أعلى من المنافس","تأجيل المشروع","اختار مورداً آخر","لا رد بعد المتابعة","أخرى"].map(r=>`<button data-fq-why="${r}">${r}</button>`).join("")}</div>`:`<b>تحويل العرض إلى فاتورة</b><p>تُنشأ مسودة فاتورة عميل بنفس البنود (draft-125). راجعها ثم أصدرها، وعند الإصدار يُسجّل القيد.</p><div class="cm-acts">${B("ghost","","إلغاء",'data-fq-ask=""')}${B("primary","receipt","إنشاء مسودة الفاتورة",`data-fq-act="doconvert:${q.id}"`)}</div>`}</div>`:""}
 <div class="fq-strip"><div class="card fq-sum"><small>إجمالي العرض</small><b>${CM.m(t.t)}</b><span>قبل الضريبة ${CM.n(t.sub-t.dc)} · ضريبة ${CM.n(t.tax)}</span></div>
  <div class="card fq-kv">${[["العميل",c[1]],["الشكل",{document:"مستند",proposal:"عرض فني ومالي",slides:"شرائح"}[q.fmt]],["مركز التكلفة",q.cc],["صالح حتى",fd(exp(q))],["أعدّه",q.by],["فتحه العميل",q.opened?`<span class="num" dir="ltr">${q.opened}</span>`:"—"]].map(x=>`<div><small>${x[0]}</small><b>${x[1]}</b></div>`).join("")}</div>
  ${q.st==="sent"?`<div class="card fq-follow"><small>المتابعة</small>${B("ghost sm","message-circle","تذكير واتساب",'data-cm-toast="أُرسل تذكير للعميل"')}${B("ghost sm","calendar-plus","تمديد الصلاحية 7 أيام",'data-cm-toast="مُدّدت صلاحية العرض"')}</div>`:""}</div>
 ${TABS([["#doc","file-text","المستند"],["#follow","history","المتابعة",log.length]],"#"+TAB)}
 ${TAB==="doc"?paper(q):`<div class="card"><div class="tl">${log.map(x=>`<div class="tli"><span class="dot"></span><div><b>${x[1]}</b><small>${x[2]}</small><small class="num">${x[3]}</small></div></div>`).join("")}</div></div>`}`};

Object.assign(T,{finQuotes:{ar:"عروض الأسعار",en:"Quotations"},finQuoteAdd:{ar:"عرض سعر جديد",en:"New quotation"},finQuoteShow:{ar:"تفاصيل عرض السعر",en:"Quotation"}});
Object.keys(S).forEach(k=>{screens[k]=S[k]});
if(!window.__fq){window.__fq=1;
 document.addEventListener("click",e=>{const c=s=>e.target.closest(s);let x;
  if(c("[data-fq-new]")){e.preventDefault();A=null;try{sessionStorage.setItem("fqEdit","0")}catch(_){}location.href="finQuoteAdd.html";return}
  if(c("[data-fq-edit]")){e.preventDefault();A=null;try{sessionStorage.setItem("fqEdit","1")}catch(_){}location.href="finQuoteAdd.html";return}
  if((x=c("[data-fq-open]"))&&!c("button:not([data-fq-open])")){e.preventDefault();open(x.dataset.fqOpen);location.href="finQuoteShow.html";return}
  if((x=c("[data-fq-st]"))){F.st=x.dataset.fqSt;CM.re();return}
  if(c("[data-fq-set]")&&(!c("[data-fq-ov]")||c("[data-fq-set]"))){F.set=!F.set;CM.re();return}
  if((x=c("[data-fq-act]"))){e.preventDefault();e.stopPropagation();const[a,id]=x.dataset.fqAct.split(":"),q=QS.find(z=>z.id===+id);if(!q)return;
   if(a==="send"){q.st="sent";q.no=q.no||"QT-00112";q.sent="2026-11-20 14:30";CM.toast("أُرسل العرض "+q.no+" للعميل")}
   else if(a==="accept"){q.st="accepted";q.acc="2026-11-20";CM.toast("سُجّل قبول العميل")}
   else if(a==="reject"){F.ask="reject"}
   else if(a==="convert"){if(window.CERP_PAGE!=="finQuoteShow"){open(q.id);try{sessionStorage.setItem("fqAsk","convert")}catch(_){}location.href="finQuoteShow.html";return}F.ask="convert"}
   else if(a==="doconvert"){q.st="converted";q.inv="draft-125";F.ask="";CM.toast("أُنشئت مسودة الفاتورة draft-125 من العرض","receipt")}
   else if(a==="copy"){const n=JSON.parse(JSON.stringify(q));n.id=Date.now()%100000;n.no="";n.st="draft";n.date="2026-11-20";delete n.sent;delete n.opened;delete n.acc;delete n.why;delete n.inv;QS.unshift(n);open(n.id);save();location.href="finQuoteShow.html";return}
   save();CM.re();return}
  if((x=c("[data-fq-why]"))){const q=cur();q.st="rejected";q.why=x.dataset.fqWhy;F.ask="";save();CM.toast("سُجّل الرفض: "+q.why);CM.re();return}
  if(c("[data-fq-ask]")){F.ask="";CM.re();return}
  if((x=c(".vtabs button"))&&window.CERP_PAGE==="finQuoteShow"){const t=x.textContent.includes("المتابعة")?"follow":"doc";TAB=t;CM.re();return}
  if(!A)return;
  if((x=c("[data-fq-fmt]"))){A.fmt=x.dataset.fqFmt;CM.re();return}
  if((x=c("[data-fq-step]"))){const el=document.getElementById("fq-"+x.dataset.fqStep);if(el)el.scrollIntoView({behavior:"smooth",block:"start"});return}
  if((x=c("[data-fq-ladd]"))){A.lines.push(x.dataset.fqLadd==="item"?L("كاميرا مراقبة IP بدقة 4MP","توريد وتركيب","",1,640,0,1):L("","خدمة لمرة واحدة","",1,0));CM.re();return}
  if((x=c("[data-fq-lrm]"))){A.lines.splice(+x.dataset.fqLrm,1);CM.re();return}
  if(c("[data-fq-sadd]")){A.secs.push(["",""]);CM.re();return}
  if((x=c("[data-fq-srm]"))){A.secs.splice(+x.dataset.fqSrm,1);CM.re();return}
  if((x=c("[data-fq-save]"))){A.err=vld();if(Object.keys(A.err).length){CM.re();CM.toast("صحّح الحقول المعلّمة","alert-circle");return}
   const two=x.dataset.fqSave==="2";const q=JSON.parse(JSON.stringify(A));delete q.err;delete q.edit;delete q.terms;if(!q.id){q.id=112;q.no=two?"QT-00112":""}else if(two&&!q.no)q.no="QT-00112";
   q.st=two?"sent":q.st;if(two)q.sent="2026-11-20 14:30";const i=QS.findIndex(z=>z.id===q.id);if(i>-1)QS[i]=q;else QS.unshift(q);save();A.done=two?2:1;A.id=q.id;A.no=q.no;CM.re();return}
 });
 document.addEventListener("input",e=>{const el=e.target;
  if(el.matches("[data-fq-q]")){F.q=el.value;const p=el.selectionStart;CM.re();const n=document.querySelector("[data-fq-q]");if(n){n.focus();n.setSelectionRange(p,p)}return}
  if(!A)return;
  if(el.matches("[data-fq-f]")){A[el.dataset.fqF]=el.value;if(el.dataset.fqF==="vd"||el.dataset.fqF==="date"){/* الصلاحية تتحدث عند الخروج من الحقل */}return}
  if(el.matches("[data-fq-l]")){const[i,k]=el.dataset.fqL.split(":");const l=A.lines[+i];l[k]=["q","p","dc"].includes(k)?(+el.value||0):el.value;return}
  if(el.matches("[data-fq-s]")){const[i,j]=el.dataset.fqS.split(":").map(Number);A.secs[i][j]=el.value}});
 document.addEventListener("change",e=>{const el=e.target;if(!A)return;
  if(el.matches("[data-fq-f]")){A[el.dataset.fqF]=el.value;if(el.dataset.fqF==="c")delete A.err.c;CM.re();return}
  if(el.matches("[data-fq-l]")){CM.re()}});
 try{if(sessionStorage.getItem("fqAsk")==="convert"&&window.CERP_PAGE==="finQuoteShow"){F.ask="convert";sessionStorage.removeItem("fqAsk")}}catch(_){}
}
});
