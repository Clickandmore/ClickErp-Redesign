/* ClickERP — التجارة · الفواتير التجارية والمشتريات والمرتجعات وقوائم الأسعار والتقارير وإعدادات التجارة (v1.75، جديد).
   المرجع: COMMERCE_POS_MASTER_PLAN.md §5 و§6.3 و§10 + موجز التصميم §9 و§12.2. البادئة cmt- / data-cmt-. */
(window.CERP_EXT=window.CERP_EXT||[]).push(()=>{
const {X,SEC,KV,TAG,SW,ROW,FLD,SEL,INP,TXT,STAT,PAGER,ACTS,HEAD,B,TABS,CHIPS,STEPS,FS}=window.CXH;
const CM=window.CM,D=CM.D,S={};
const TODAY="2026-11-20";
const nz=v=>{v=parseFloat(String(v).replace(/[,٫]/g,""));return isFinite(v)?v:0};
const r2=v=>Math.round(v*100)/100;
const stOf=id=>D.stores.find(s=>s[0]===id)||D.stores[0];
const ss=(k,v)=>{try{if(v===undefined)return sessionStorage.getItem(k);v===null?sessionStorage.removeItem(k):sessionStorage.setItem(k,v)}catch(e){}return null};
const DN_ON=()=>CM.on("delivery_notes")||ss("cmt.dn")==="1";
const A=(ns,act,arg)=>`data-cmt="${ns}.${act}${arg!=null?":"+arg:""}"`;
const fi=(ns,f,v,o)=>{o=o||{};return `<input class="${o.c||""}" data-cmt-c="${ns}.${f}" ${o.i!=null?`data-i="${o.i}"`:""} ${o.f?`data-cmt-f="${o.f}"`:""} value="${v==null?"":v}" ${o.ph?`placeholder="${o.ph}"`:""} ${o.dis?"disabled":""} ${o.rtl?"":'dir="ltr"'} ${o.type?`type="${o.type}"`:""}>`};
const fs=(ns,f,opts,cur,o)=>{o=o||{};return `<select data-cmt-c="${ns}.${f}" ${o.i!=null?`data-i="${o.i}"`:""} ${o.f?`data-cmt-f="${o.f}"`:""} ${o.dis?"disabled":""} class="${o.c||""}">${opts.map(x=>`<option value="${x[0]}"${String(x[0])===String(cur)?" selected":""}>${x[1]}</option>`).join("")}</select>`};
const now=txt=>`<div class="cmt-now">${X("compass")}<b>ماذا أفعل الآن؟</b><span>${txt}</span></div>`;
const dim=t=>`<small class="cmt-dim">${t}</small>`;
const pvOnly=()=>CM.cost();
const kvl=a=>`<div class="cmt-kv">${a.map(x=>`<div><span>${x[0]}</span><b>${x[1]}</b></div>`).join("")}</div>`;
const F=(n,d)=>Number(n).toLocaleString("en-US",{minimumFractionDigits:d==null?2:d,maximumFractionDigits:d==null?2:d});

/* ---------- بيانات العيّنة ---------- */
const CL=[["c1","مؤسسة الندى للتموينات","310998877600003",50000,18400,"pl2"],["c2","بقالة الأمل","300112233400003",20000,3200,"pl2"],["c3","مزرعة الوادي للمواشي","310556677800003",150000,96000,"pl3"],["c4","عميل نقدي","",0,0,"pl1"]];
const cl=id=>CL.find(x=>x[0]===id)||CL[0];
const SUP=[["v1","شركة الخليج للمواد الغذائية","300445566700003"],["v2","مطاحن الجزيرة للأعلاف","310778899100003"],["v3","مصنع المراعي للألبان","300223344500003"]];
const sup=id=>SUP.find(x=>x[0]===id)||SUP[0];
/* قوائم الأسعار: [id, الاسم, الربط, من, إلى, الأولوية, نشطة, معامل السعر عن الأساسي] */
let PL=[["pl1","التجزئة","كل العملاء · كل الفروع","2026-01-01","",100,1,1],["pl2","الجملة","تصنيف العملاء «جملة»","2026-01-01","",50,1,0.94],["pl3","أسعار الأعلاف","قسم «مزارع ومواشي» · فرع الرياض","2026-03-01","",40,1,0.97],["pl4","عرض رمضان 1448","كل العملاء · كل الفروع","2027-02-01","2027-03-10",10,1,0.9],["pl5","تخفيضات نهاية الموسم","كل العملاء · فرع جدة","2026-09-01","2026-10-15",20,1,0.88],["pl6","جملة جدة","قسم «جملة» · فرع جدة","2026-04-01","",45,0,0.95]];
const PLK="cerp.cmt.pl",POK="cerp.cmt.po";
let PO={};try{PO=JSON.parse(localStorage.getItem(POK))||{}}catch(e){}
try{const x=JSON.parse(localStorage.getItem(PLK));if(Array.isArray(x)&&x.length)PL=x}catch(e){}
const savePL=()=>{try{localStorage.setItem(PLK,JSON.stringify(PL))}catch(e){}};
const savePO=()=>{try{localStorage.setItem(POK,JSON.stringify(PO))}catch(e){}};
const pl=id=>PL.find(x=>x[0]===id)||PL[0];
const plSt=p=>!p[6]?"stopped":(p[4]&&p[4]<TODAY)?"expired":p[3]>TODAY?"scheduled":"active";
const priceRec=(pid,id,ui)=>{const it=D.item(id),b=it.units[ui][2],l=pl(pid),o=PO[pid+"|"+id+"|"+ui]||{};return {p:o.p!=null?o.p:r2(b*l[7]),min:o.min||0,base:b,set:o.p!=null}};
const catTop=it=>it.cat.split(" ← ")[0];

/* فواتير البيع: ln = [المنتج, الوحدة(رقم), الكمية, سعر ثابت|null=من القائمة, خصم] */
const SALES=[
 {no:"SI-2026-0418",date:"2026-11-20",c:"c4",s:"s2",pl:"pl1",ln:[[105,0,6,null,0],[104,1,3,null,0]],st:"draft",paid:0,dv:"not_delivered",za:"none",pay:"cash"},
 {no:"SI-2026-0417",date:"2026-11-19",c:"c3",s:"s1",pl:"pl3",ln:[[107,2,5,null,0],[108,2,3,null,100]],st:"confirmed",paid:.5,dv:"partial",za:"pending",dvb:{107:2,108:3},pay:"credit"},
 {no:"SI-2026-0412",date:"2026-11-18",c:"c1",s:"s1",pl:"pl2",ln:[[101,1,20,null,0],[102,1,10,null,0],[107,2,2,null,0],[109,0,1,null,0]],st:"confirmed",paid:.4,dv:"partial",za:"reported",dvb:{101:12,102:10,107:1},ret:{101:3},retNo:"RT-2026-0031",pay:"credit"},
 {no:"SI-2026-0411",date:"2026-11-17",c:"c2",s:"s1",pl:"pl2",ln:[[102,1,4,null,0],[103,0,10,null,0]],st:"confirmed",paid:1,dv:"delivered",za:"reported",pay:"transfer"},
 {no:"SI-2026-0409",date:"2026-11-15",c:"c4",s:"s2",pl:"pl1",ln:[[106,0,2.5,null,0],[105,0,8,null,0]],st:"confirmed",paid:1,dv:"delivered",za:"reported",pay:"mada"},
 {no:"SI-2026-0405",date:"2026-11-12",c:"c3",s:"s3",pl:"pl3",ln:[[107,2,10,null,0]],st:"confirmed",paid:0,dv:"delivered",za:"not",pay:"credit"},
 {no:"SI-2026-0398",date:"2026-11-08",c:"c2",s:"s1",pl:"pl2",ln:[[101,1,6,null,0]],st:"retfull",paid:0,dv:"delivered",za:"reported",ret:{101:6},retNo:"RT-2026-0029",pay:"credit"}];
const sale=no=>SALES.find(x=>x.no===no)||SALES[2];
const PURCH=[
 {no:"PU-2026-0188",date:"2026-11-16",v:"v1",s:"s1",ln:[[101,1,100,125,100],[102,1,50,166,50]],st:"confirmed",rec:"received",paid:1,ret:{102:5},retNo:"RT-2026-0030",inv:"GC-77412"},
 {no:"PU-2026-0187",date:"2026-11-14",v:"v2",s:"s1",ln:[[107,2,20,1210,12],[108,2,10,1440,0]],st:"confirmed",rec:"partial",paid:0,inv:"MJ-20931"},
 {no:"PU-2026-0186",date:"2026-11-12",v:"v3",s:"s2",ln:[[105,1,30,70,0]],st:"draft",rec:"none",paid:0,inv:""}];
const purch=no=>PURCH.find(x=>x.no===no)||PURCH[0];
/* استلامات بلا فاتورة (GRNI) */
const GRN=[{no:"GR-2026-0061",date:"2026-11-14",v:"v1",s:"s1",ln:[[103,0,200,30.5,200]]},{no:"GR-2026-0059",date:"2026-11-10",v:"v3",s:"s2",ln:[[105,1,20,70.8,20]]}];
const RET=[
 {no:"RT-2026-0031",date:"2026-11-19",type:"sale",orig:"SI-2026-0412",ln:[[101,1,3]],reason:"عبوات تالفة عند الاستلام",mode:"credit"},
 {no:"RT-2026-0030",date:"2026-11-18",type:"purchase",orig:"PU-2026-0188",ln:[[102,1,5]],reason:"زيت قريب الانتهاء",mode:"credit"},
 {no:"RT-2026-0029",date:"2026-11-09",type:"sale",orig:"SI-2026-0398",ln:[[101,1,6]],reason:"إلغاء الطلب قبل التسليم",mode:"credit"}];
/* تسليمات سابقة على فاتورة 0412 */
const DNS=[{no:"DN-2026-0233",date:"2026-11-18",by:"محمد الزهراني",ln:{101:12,102:10}},{no:"DN-2026-0236",date:"2026-11-19",by:"محمد الزهراني",ln:{107:1}}];

/* ---------- حسابات السطر والفاتورة ---------- */
const calcLn=(ln,pid)=>{const it=D.item(ln.id),u=it.units[ln.ui]||it.units[0],pr=priceRec(pid,ln.id,ln.ui),price=ln.p!=null?ln.p:pr.p,gross=ln.q*price,net=gross-(ln.d||0),tax=net*it.tax/100,svc=it.kind==="service",base=ln.q*u[1],cost=svc?0:it.cost*base;
 return {it,u,pr,price,gross,net,tax,tot:net+tax,base,svc,cost,profit:net-cost,manual:ln.p!=null&&Math.abs(ln.p-pr.p)>0.0004}};
const toLn=a=>({id:a[0],ui:a[1],q:a[2],p:a[3],d:a[4]||0});
const sum=(lines,pid)=>{const t={gross:0,disc:0,net:0,tax:0,tot:0,cost:0,profit:0,goods:0,svc:0};lines.forEach(l=>{const c=calcLn(l,pid);t.gross+=c.gross;t.disc+=l.d||0;t.net+=c.net;t.tax+=c.tax;t.tot+=c.tot;t.cost+=c.cost;t.profit+=c.profit;c.svc?t.svc+=c.net:t.goods+=c.net});return t};
const saleTot=r=>sum(r.ln.map(toLn),r.pl);
const purTot=r=>{let ex=0,tax=0;r.ln.forEach(a=>{const it=D.item(a[0]),v=a[4]*a[3];ex+=v;tax+=v*it.tax/100});return {ex,tax,tot:ex+tax}};
const uName=(id,ui)=>D.item(id).units[ui][0];
const qU=(q,id,ui)=>{const it=D.item(id),u=it.units[ui];return u[1]!==1&&q?CM.q2(q,u[0],q*u[1],it.units[0][0]):CM.q(q,u[0])};
const itCell=(it,sub)=>`<div class="cm-icell"><span class="cm-img sm" style="background:${it.col}">${X(it.ic)}</span><div><b>${it.n}</b><small>${it.code}${sub?" · "+sub:""}</small></div></div>`;
const stSale=r=>r.st==="draft"?CM.st("draft"):r.st==="retfull"?CM.st("cancelled","مرتجع كامل"):r.ret?CM.st("partial","مرتجع جزئي"):CM.st("confirmed");
const payTag=f=>f>=1?CM.st("paid"):f>0?CM.st("partial","مدفوعة جزئياً"):CM.st("unpaid");
const zaTag=z=>z==="reported"?TAG("g","مشاركة"):z==="pending"?TAG("a","قيد المشاركة"):z==="not"?TAG("a","غير مشاركة"):`<span class="cm-hid">—</span>`;
const dvTag=d=>CM.st(d,d==="partial"?"تسليم جزئي":null);
const delivered=(r,id)=>{if(r.dvb)return r.dvb[id]||0;const l=r.ln.find(a=>a[0]===id);return r.dv==="delivered"&&l?l[2]:0};
const brRows=rows=>CM.allBr()?rows:rows.filter(r=>stOf(r.s)[2]==="b1");

const ac=s=>[s.slice(5),s.slice(0,4)];
const jl=(s,d,c,cc)=>{const a=ac(s);return [a[0],a[1],d||0,c||0,cc||""]};
const recvAcc=(c,pay)=>pay==="cash"?"1111 الصندوق الرئيسي":pay==="mada"?D.pay[1][3]:pay==="transfer"?"1112 البنك الأهلي":"1121 العملاء — "+cl(c)[1];
const saleJournal=(t,c,pay,sid,cc)=>{const L=[jl(recvAcc(c,pay),r2(t.tot),0,cc)];if(t.goods)L.push(jl(D.accounts.sales,0,r2(t.goods),cc));if(t.svc)L.push(jl(D.accounts.service,0,r2(t.svc),cc));if(t.tax)L.push(jl(D.accounts.vatOut,0,r2(t.tax)));return L};
const cogsJournal=(cost,sid)=>[jl(D.accounts.cogs,r2(cost),0,stOf(sid)[3].split(" ")[0]),jl(stOf(sid)[4],0,r2(cost),stOf(sid)[3].split(" ")[0])];

/* ============ فواتير البيع ============ */
const LS={st:"",pay:"",za:"",dv:"",q:""};
S.cmSales=()=>{
 const dn=DN_ON();let rows=brRows(SALES);
 const cnt=k=>rows.filter(r=>!k||(k==="ret"?r.ret:r.st===k)).length;
 const f=rows.filter(r=>(!LS.st||(LS.st==="ret"?r.ret:r.st===LS.st))&&(!LS.pay||(LS.pay==="paid"?r.paid>=1:LS.pay==="unpaid"?r.paid===0:r.paid>0&&r.paid<1))&&(!LS.za||r.za===LS.za)&&(!LS.dv||r.dv===LS.dv)&&(!LS.q||(r.no+cl(r.c)[1]).indexOf(LS.q)>-1));
 const conf=rows.filter(r=>r.st!=="draft"),tt=conf.map(saleTot);
 const net=tt.reduce((a,t)=>a+t.net,0),due=conf.reduce((a,r,i)=>a+tt[i].tot*(1-r.paid),0),waitDv=conf.filter(r=>r.dv!=="delivered").length,noZa=conf.filter(r=>r.za==="pending"||r.za==="not").length,profit=tt.reduce((a,t)=>a+t.profit,0);
 const body=f.length?`<div class="card flush"><div class="tw"><table><thead><tr><th>رقم الفاتورة</th><th>التاريخ</th><th>العميل</th><th>الإجمالي (ر.س)</th><th>السداد</th>${dn?"<th>التسليم</th>":""}<th>الهيئة</th><th>الحالة</th><th class="act"></th></tr></thead><tbody>
 ${f.map(r=>{const t=saleTot(r);return `<tr class="cmt-row" ${A("sl","open",r.no)}><td><b class="num">${r.no}</b>${r.ret?`<small class="cm-sub">${X("undo-2")} <span class="num">${r.retNo}</span></small>`:""}</td><td class="num">${r.date}</td><td><b>${cl(r.c)[1]}</b><small class="cm-sub">${stOf(r.s)[1]}</small></td><td><b>${CM.n(t.tot)}</b><small class="cm-sub">قبل الضريبة ${CM.n(t.net)}</small></td><td>${r.st==="draft"?`<span class="cm-hid">—</span>`:payTag(r.paid)}${r.paid>0&&r.paid<1?`<small class="cm-sub">المتبقي ${CM.n(t.tot*(1-r.paid))}</small>`:""}</td>${dn?`<td>${r.st==="draft"?`<span class="cm-hid">—</span>`:dvTag(r.dv)}</td>`:""}<td>${zaTag(r.za)}</td><td>${stSale(r)}</td><td class="act"><div class="rowacts"><button class="icon-btn pri" title="عرض" ${A("sl","open",r.no)}>${X("eye")}</button><button class="icon-btn pri" title="طباعة" data-cm-toast="تم تجهيز الفاتورة للطباعة — وسُجّلت «طباعة» في سجل النشاط">${X("printer")}</button></div></td></tr>`}).join("")}
 </tbody></table></div>${PAGER(`عرض ${f.length} من ${rows.length} فاتورة`)}</div>`:CM.nores();
 return `${HEAD("فواتير البيع","فواتير المنتجات بالوحدة والكمية والمخزن. المؤكدة للقراءة فقط، وتصحيحها بمرتجع.",B("ghost","file-spreadsheet","Excel",'data-cm-toast="جاري تجهيز ملف Excel"')+B("primary","plus","فاتورة جديدة",'data-go="cmSaleAdd"'))}
 ${CM.pvbar()}
 ${now(`${noZa?`<a href="#" ${A("sl","za","pending")}>${noZa} فواتير لم تكتمل مشاركتها مع الهيئة</a>`:"كل الفواتير مشاركة مع الهيئة"}${due>0?` · <a href="#" ${A("sl","pay","unpaid")}>مبالغ غير محصّلة ${CM.n(due,0)} ر.س</a>`:""}${dn&&waitDv?` · <a href="#" ${A("sl","dv","partial")}>${waitDv} فواتير بانتظار التسليم</a>`:""}`)}
 <div class="cm-kpis"><div class="card cm-kpi"><span>${X("receipt")}مبيعات الشهر قبل الضريبة</span><b class="num">${F(net,0)}</b><small>${conf.length} فاتورة مؤكدة</small></div><div class="card cm-kpi warn"><span>${X("alarm-clock")}غير محصّل</span><b class="num">${F(due,0)}</b><small>من فواتير آجلة وجزئية</small></div>
 ${dn?`<div class="card cm-kpi"><span>${X("truck")}بانتظار التسليم</span><b class="num">${waitDv}</b><small>لم تُسلّم أو سُلّمت جزئياً</small></div>`:""}<div class="card cm-kpi ${noZa?"warn":"ok"}"><span>${X("shield-check")}غير مبلّغة للهيئة</span><b class="num">${noZa}</b><small>قيد المشاركة أو لم تُشارك</small></div>
 ${pvOnly()?`<div class="card cm-kpi ok"><span>${X("trending-up")}الربح المتوقع</span><b class="num">${F(profit,0)}</b><small>بتكلفة المتوسط وقت البيع</small></div>`:""}</div>
 ${CM.view(`<div class="cx-chips">${[["","الكل"],["draft","مسودة","n"],["confirmed","مؤكدة","g"],["ret","فيها مرتجع","a"]].map(c=>`<button class="${LS.st===c[0]?"on":""}" ${A("sl","st",c[0])}>${c[2]?`<i class="d ${c[2]}"></i>`:""}${c[1]}<b class="num">${cnt(c[0])}</b></button>`).join("")}</div>
 <div class="fbar"><div class="fb grow"><label>بحث</label><div class="inp">${X("search")}<input data-cmt-c="sl.q" value="${LS.q}" placeholder="رقم الفاتورة أو اسم العميل"></div></div>
  <div class="fb"><label>حالة السداد</label>${fs("sl","pay",[["","الكل"],["paid","مدفوعة"],["part","جزئي"],["unpaid","غير مدفوعة"]],LS.pay)}</div>
  ${dn?`<div class="fb"><label>التسليم</label>${fs("sl","dv",[["","الكل"],["not_delivered","لم تُسلّم"],["partial","جزئي"],["delivered","مسلّمة"]],LS.dv)}</div>`:""}
  <div class="fb"><label>حالة الهيئة</label>${fs("sl","za",[["","الكل"],["reported","مشاركة"],["pending","قيد المشاركة"],["not","غير مشاركة"]],LS.za)}</div>${CM.brSel()}
  <div class="fb"><label>من تاريخ</label><input type="date" value="2026-11-01"></div><div class="fb"><label>إلى تاريخ</label><input type="date" value="${TODAY}"></div></div>
 ${body}`,CM.empty("receipt","لا توجد فواتير بيع بعد","أنشئ أول فاتورة: اختر العميل والمخزن ثم أضف المنتجات بالباركود أو الاسم.",B("primary","plus","فاتورة جديدة",'data-go="cmSaleAdd"')))}
 ${!dn?CM.note(`سند الصرف غير مفعّل: التسليم يتم مع تأكيد الفاتورة. <a href="#" ${A("sl","dnprev")}>عرض الشاشة كأن الميزة مفعّلة</a> (للمعاينة فقط).`):""}
 <div class="cx-info">${X("info")}<span>حالة الهيئة تظهر بنفس شكلها في فواتير البيع الحالية: الفاتورة الضريبية لعميل بالرقم الضريبي تُرسل عبر وحدة الإصدار نفسها.</span></div>`};

/* ============ فاتورة بيع ============ */
const SA0=()=>({c:"c1",date:TODAY,s:"s1",pl:"pl2",pay:"credit",note:"",due:"2026-12-20",ln:[{id:101,ui:1,q:12,p:null,d:0},{id:103,ui:0,q:15,p:null,d:0},{id:102,ui:1,q:5,p:210,d:0},{id:109,ui:0,q:1,p:null,d:0}],rev:0,done:null,find:""});
let SA=SA0();
const needMap=ln=>{const m={};ln.forEach(l=>{const c=calcLn(l,SA.pl);if(!c.svc)m[l.id]=(m[l.id]||0)+c.base});return m};
const saIssues=()=>{const st=stOf(SA.s),need=needMap(SA.ln),out=[];
 SA.ln.forEach((l,i)=>{const c=calcLn(l,SA.pl),o={bad:[],warn:[]};
  if(!(l.q>0))o.bad.push("الكمية يجب أن تكون أكبر من صفر");
  if(!c.svc){const av=(c.it.qty[SA.s]||0),nd=need[l.id],fac=c.u[1];
   if(nd>av+1e-9){const msg=`المتاح في «${st[1]}» ${qU(Math.max(0,av)/fac,l.id,l.ui)} والمطلوب ${CM.q(nd/fac,c.u[0])}.`;
    if(st[6])o.warn.push(`${msg} المخزن يسمح بالسالب: سيصبح الرصيد سالباً.`);else o.bad.push(`${msg} لن يُحذف الناقص تلقائياً: عدّل الكمية أو اختر مخزناً آخر.`)}}
  if(c.pr.min&&l.q<c.pr.min)o.warn.push(`أقل من الحد الأدنى لكمية قائمة الأسعار (${c.pr.min}).`);
  if(c.profit<0&&!c.svc&&CM.cost())o.warn.push("السعر أقل من التكلفة: بيع بخسارة.");
  out.push(o)});return out};
const saBlocks=()=>{const is=saIssues(),b=[];if(!SA.ln.length)b.push("أضف منتجاً واحداً على الأقل");is.forEach((o,i)=>o.bad.forEach(m=>b.push(`السطر ${i+1}: ${m.replace(/<[^>]+>/g,"")}`)));return b};
const saLine=(l,i,is)=>{const c=calcLn(l,SA.pl),st=stOf(SA.s),av=c.svc?null:(c.it.qty[SA.s]||0),tot=D.tot(c.it);
 const bal=c.svc?`<span class="cmt-svc">${X("sparkles")}خدمة — بلا رصيد</span>`:`<span class="${av<=0?"cmt-neg":""}">${qU(av/c.u[1],l.id,l.ui)}</span>`;
 const tr=`<tr class="${is.bad.length?"cm-row-bad":is.warn.length?"cm-row-warn":""}"><td>${itCell(c.it,c.it.tax+"% ضريبة")}</td>
 <td>${fs("sa","ui",c.it.units.map((u,k)=>[k,u[0]+(u[1]!==1?" ("+u[1]+")":"")]),l.ui,{i,f:`u${i}`})}</td>
 <td>${fi("sa","q",l.q,{i,c:"cmt-n",f:`q${i}`})}</td>
 <td>${fi("sa","p",c.price,{i,c:"cmt-n"+(c.manual?" man":""),f:`p${i}`})}${c.manual?`<span class="cmt-man" title="السعر في القائمة ${c.pr.p}">${X("pencil")}معدّل يدوياً<button class="cmt-x" ${A("sa","unman",i)} title="استعادة سعر القائمة">${X("rotate-ccw")}</button></span>`:dim("من قائمة «"+pl(SA.pl)[1]+"»")}</td>
 <td>${fi("sa","d",l.d||"",{i,c:"cmt-n",ph:"0",f:`d${i}`})}</td><td><b>${CM.n(c.tot)}</b>${dim("ضريبة "+CM.n(c.tax))}</td>
 <td class="act"><button class="icon-btn" ${A("sa","del",i)} title="حذف السطر">${X("trash-2")}</button></td></tr>`;
 const band=`<tr class="cmt-band ${is.bad.length?"bad":is.warn.length?"warn":""}"><td colspan="7"><div class="cmt-bp"><span>${c.svc?"":`<small>الرصيد المتاح في «${st[1]}»</small>`}${bal}</span>${c.svc?"":`<span><small>الربح المتوقع</small><b>${CM.c(c.profit)}</b>${CM.cost()&&c.net>0?` <em>${Math.round(c.profit/c.net*100)}% من البيع</em>`:""}</span>`}${c.svc?"":`<span><small>التكلفة للوحدة الأساسية</small><b>${CM.c(c.it.cost)}</b></span>`}</div>${is.bad.length||is.warn.length?`<div class="cmt-msgs">${is.bad.map(m=>`<div class="cmt-msg bad">${X("octagon-alert")}<span>${m}</span>${m.indexOf("المتاح")>-1&&av>0?`<button class="btn btn-ghost sm" ${A("sa","fit",i)}>عدّل الكمية إلى المتاح</button>`:""}</div>`).join("")}${is.warn.map(m=>`<div class="cmt-msg warn">${X("alert-triangle")}<span>${m}</span></div>`).join("")}</div>`:""}</td></tr>`;
 return tr+band};
const saPanelRows=t=>[["قبل الخصم",CM.n(t.gross)],["الخصم","− "+CM.n(t.disc)],["الصافي",CM.n(t.net)],["الضريبة 15%",CM.n(t.tax)]].map(x=>`<div class="actrow sm"><div><b>${x[0]}</b></div><span>${x[1]}</span></div>`).join("");
S.cmSaleAdd=()=>{
 if(SA.done){return `${HEAD("فاتورة بيع","","",["cmSales","فواتير البيع"])}${CM.pvbar()}
 <div class="card h14-ok cmt-done">${X("check-circle-2")}<div><h3>تم تأكيد الفاتورة ${SA.done}</h3><p>أُنشئ قيد الإيراد${CM.cost()?" وقيد التكلفة":""} وخُصم المخزون، والفاتورة الآن للقراءة فقط. ${cl(SA.c)[2]?"أُرسلت إلى الهيئة عبر وحدة الإصدار الحالية وحالتها «قيد المشاركة».":"فاتورة مبسّطة: تُبلّغ للهيئة خلال 24 ساعة."}</p></div></div>
 <div class="h14-next">${B("primary","eye","عرض فاتورة نموذجية",'data-go="cmSaleShow"')}${B("ghost","list","فواتير البيع",'data-go="cmSales"')}${B("ghost","plus","فاتورة جديدة",A("sa","reset"))}</div>`}
 const t=sum(SA.ln,SA.pl),is=saIssues(),bl=saBlocks(),st=stOf(SA.s),cli=cl(SA.c),multi=CM.on("multi_uom");
 const secOk=[!!SA.c,SA.ln.length>0&&bl.length===0,true,SA.rev&&!bl.length];
 const steps=["بيانات الفاتورة","المنتجات والأسعار","الدفع والملاحظات","المراجعة قبل التأكيد"];
 const over=SA.pay==="credit"&&cli[3]&&cli[4]+t.tot>cli[3];
 const nMan=SA.ln.filter(l=>calcLn(l,SA.pl).manual).length;
 const checks=[
  [SA.c?"ok":"bad","العميل","محدد: "+cli[1]],
  [SA.ln.length?"ok":"bad","المنتجات",SA.ln.length+" سطور"],
  [bl.length?"bad":"ok","الرصيد في المخزن",bl.length?`${bl.length} سطر ممنوع: ${bl.slice(0,2).join(" — ")}`:"كل السطور مغطاة بالرصيد"],
  [nMan?"warn":"ok","الأسعار",nMan?`${nMan} سعر معدّل يدوياً عن قائمة الأسعار`:"كلها من قائمة الأسعار"],
  [over?"warn":"ok","حد الائتمان",SA.pay==="credit"?`الرصيد ${CM.n(cli[4],0)} + الفاتورة ${CM.n(t.tot,0)} من ${CM.n(cli[3],0)}${over?" — يتجاوز الحد ويحتاج اعتماد مدير الفرع":""}`:"غير مطبق على الدفع الفوري"],
  ["ok","الفترة المحاسبية","التاريخ "+SA.date+" ضمن فترة مفتوحة"]];
 const chk=checks.map(c=>`<div class="cmt-chk ${c[0]}">${X(c[0]==="ok"?"check-circle-2":c[0]==="warn"?"alert-triangle":"octagon-alert")}<b>${c[1]}</b><span>${c[2]}</span></div>`).join("");
 const res=SA.find.trim()?D.items.filter(i=>i.act&&(i.kind==="stock"||i.kind==="service")&&(i.n+i.code+i.units.map(u=>u[3]).join(" ")).indexOf(SA.find.trim())>-1).slice(0,6):[];
 return `${HEAD("فاتورة بيع","أضف المنتجات ثم راجع قبل التأكيد. الفاتورة المؤكدة لا تُعدّل، وتصحيحها بمرتجع.",B("ghost","","إلغاء",'data-go="cmSales"')+B("ghost","save","حفظ كمسودة",A("sa","draft")),["cmSales","فواتير البيع"])}
 ${CM.pvbar()}
 <div class="cvform h15"><aside class="cvopts cmt-aside"><div class="card"><div class="sec-h">${X("list-ordered")}<b>أقسام الفاتورة</b></div><div class="steps">${steps.map((s,i)=>`<button class="step${secOk[i]?" done":""}" ${A("sa","go",i+1)}><span class="num">${secOk[i]?X("check"):i+1}</span><b>${s}</b></button>`).join("")}</div></div>
  <div class="card"><div class="sec-h">${X("calculator")}<b>الإجماليات</b></div><div class="cmt-pn">${saPanelRows(t)}<div class="cmt-tot"><span>المستحق</span><b class="cmt-big num">${F(t.tot)}</b></div>
   ${pvOnly()?`<div class="actrow sm"><div><b>الربح المتوقع</b></div><span>${CM.c(t.profit)} ${t.net>0?`<small class="cm-u">(${Math.round(t.profit/t.net*100)}%)</small>`:""}</span></div>`:""}</div></div>
  <div class="card cmt-pn2 ${bl.length?"bad":"ok"}">${bl.length?`<div class="cmt-blk">${X("octagon-alert")}<div><b>التأكيد ممنوع (${bl.length})</b><small>${bl[0]}</small></div></div>`:`<div class="cmt-blk ok">${X("check-circle-2")}<div><b>لا موانع للتأكيد</b><small>${SA.rev?"راجع القيد ثم أكّد":"اضغط «مراجعة» قبل التأكيد"}</small></div></div>`}
   ${SA.rev?B("primary","check","تأكيد الفاتورة",A("sa","confirm")+(bl.length?" disabled":"")):B("primary","scan-search","مراجعة قبل التأكيد",A("sa","review"))}</div></aside>
 <div class="cvfields">
  <div class="card cmt-sec" id="cmt-sa-1"><div class="fs-h"><span class="fs-n num">1</span><b>بيانات الفاتورة</b></div><div class="fgrid cmt-g3">
   ${FLD("العميل",fs("sa","c",CL.map(c=>[c[0],c[1]+(c[2]?"":" (مبسّطة)")]),SA.c),1,cli[2]?"الرقم الضريبي "+cli[2]+" — فاتورة ضريبية B2B":"بلا رقم ضريبي — فاتورة ضريبية مبسّطة")}
   ${FLD("تاريخ الفاتورة",`<input type="date" data-cmt-c="sa.date" value="${SA.date}">`,1)}
   ${FLD("المخزن",fs("sa","s",D.stores.filter(s=>CM.allBr()||s[2]==="b1").map(s=>[s[0],s[1]+(s[6]?" — يسمح بالسالب":"")]),SA.s),1,st[3])}
   ${CM.on("price_lists")?FLD("قائمة الأسعار",fs("sa","pl",PL.filter(p=>plSt(p)==="active").map(p=>[p[0],p[1]]),SA.pl),0,"تُعيَّن من العميل ويمكن تغييرها"):""}
   ${FLD("رقم الفاتورة",`<input value="SI-2026-0419" disabled dir="ltr">`,0,"يُرقّم تلقائياً عند التأكيد")}</div></div>
  <div class="card cmt-sec" id="cmt-sa-2"><div class="fs-h"><span class="fs-n num">2</span><b>المنتجات والأسعار</b></div>
   <div class="cmt-find"><div class="inp">${X("scan-barcode")}<input data-cmt-find="1" data-cmt-f="find" value="${SA.find}" placeholder="ابحث بالاسم أو الكود، أو امسح الباركود ثم Enter" autocomplete="off"></div>
    <div class="cmt-res">${res.length?res.map(i=>`<button ${A("sa","add",i.id)}>${itCell(i,i.units[0][3]||"")}<span>${CM.n(i.units[0][2])} ر.س / ${i.units[0][0]}</span>${i.kind==="service"?`<em>خدمة</em>`:`<em class="${(i.qty[SA.s]||0)<=0?"neg":""}">${CM.q(i.qty[SA.s]||0,i.units[0][0])}</em>`}</button>`).join(""):SA.find.trim()?`<div class="cmt-nr">لا نتيجة. جرّب «أرز» أو الباركود 6281000101015</div>`:""}</div></div>
   ${SA.ln.length?`<div class="tw"><table class="cmt-lt"><thead><tr><th>المنتج</th><th>الوحدة</th><th>الكمية</th><th>السعر قبل الضريبة</th><th>الخصم</th><th>الإجمالي</th><th></th></tr></thead><tbody>${SA.ln.map((l,i)=>saLine(l,i,is[i])).join("")}</tbody></table></div>
   <div class="cmt-ltf"><span>${SA.ln.length} سطر · ${SA.ln.filter(l=>!calcLn(l,SA.pl).svc).length} منتج مخزني</span>${!multi?`<span class="cm-flag">${X("info")}الوحدات المتعددة غير مفعّلة</span>`:""}</div>`:`<div class="cmt-emp">${X("scan-barcode")}<b>لا منتجات في الفاتورة</b><span>امسح الباركود أو ابحث عن منتج للبدء.</span></div>`}</div>
  <div class="card cmt-sec" id="cmt-sa-3"><div class="fs-h"><span class="fs-n num">3</span><b>الدفع والملاحظات</b></div><div class="fgrid cmt-g3">
   ${FLD("طريقة الدفع",fs("sa","pay",[["credit","آجل"],["cash","نقدي"],["mada","مدى"],["transfer","تحويل بنكي"]],SA.pay),1)}
   ${FLD("تاريخ الاستحقاق",`<input type="date" data-cmt-c="sa.due" value="${SA.due}" ${SA.pay!=="credit"?"disabled":""}>`)}
   <div class="fld full"><label>ملاحظات تظهر في الفاتورة</label><textarea rows="2" data-cmt-c="sa.note">${SA.note}</textarea></div></div></div>
  <div class="card cmt-sec" id="cmt-sa-4"><div class="fs-h"><span class="fs-n num">4</span><b>المراجعة قبل التأكيد</b></div>
   ${SA.rev?`<div class="cmt-revg"><div class="cmt-revc"><div class="msec-h">${X("scan-search")}<b>الفحص</b></div>${chk}</div>
    <div class="cmt-revc"><div class="msec-h">${X("shield-check")}<b>الهيئة (ZATCA)</b></div><p class="cmt-p">${cli[2]?`<b>فاتورة ضريبية B2B</b> للعميل ذي الرقم الضريبي ${cli[2]}. تُرسل بعد التأكيد عبر وحدة الإصدار الحالية نفسها (ShareInvoiceZatcaService)، وتظهر حالتها ${TAG("a","قيد المشاركة")} ثم ${TAG("g","مشاركة")}.`:`<b>فاتورة ضريبية مبسّطة</b> لعميل بلا رقم ضريبي. تُبلّغ خلال 24 ساعة من الإصدار.`}</p>${DN_ON()?`<p class="cmt-p">${X("truck")} التسليم: يُصدر سند صرف بعد التأكيد، ويُرحَّل قيد التكلفة مع كل سند.</p>`:""}</div></div>
    <div class="cmt-jg">${CM.jprev("قيد الإيراد المتوقع",saleJournal(t,SA.c,SA.pay,SA.s,"CC-120"),"يُنشأ عند التأكيد ويُربط بالفاتورة")}${CM.cost()?CM.jprev("قيد التكلفة",cogsJournal(t.cost,SA.s),DN_ON()?"مع سند الصرف يُرحَّل قيد تكلفة لكل سند":"يُنشأ مع التأكيد"):CM.note("قيد التكلفة يظهر لمن يملك صلاحية رؤية التكلفة.","","lock")}</div>
    <div class="cvactions">${B("primary","check","تأكيد الفاتورة",A("sa","confirm")+(bl.length?" disabled":""))}${B("ghost","pencil","رجوع للتعديل",A("sa","unreview"))}${bl.length?`<span class="cm-flag bad">${X("octagon-alert")}التأكيد ممنوع حتى تُعالج ${bl.length} ملاحظة</span>`:""}</div>`:`<div class="cmt-emp">${X("scan-search")}<b>راجع الفاتورة قبل التأكيد</b><span>تعرض المراجعة الفحوص والقيد المتوقع وحالة الهيئة. ${bl.length?`<b class="cmt-neg">فيها ${bl.length} مانع للتأكيد.</b>`:""}</span>${B("primary","scan-search","مراجعة قبل التأكيد",A("sa","review"))}</div>`}</div>
 </div></div>`};

/* ============ عرض فاتورة بيع ============ */
const SH={tab:"lines",now:null,dns:DNS.slice()};
const shR=()=>sale(ss("cmt.sel")||"SI-2026-0412");
const shDvb=(r,id)=>r.no==="SI-2026-0412"?SH.dns.reduce((a,d)=>a+(d.ln[id]||0),0):delivered(r,id);
const retValue=x=>{const o=x.type==="sale"?sale(x.orig):purch(x.orig);return x.ln.reduce((a,l)=>{const src=o.ln.find(s=>s[0]===l[0]);const price=x.type==="sale"?calcLn(toLn(src),o.pl).price:src[3];return a+l[2]*price*(1+D.item(l[0]).tax/100)},0)};
S.cmSaleShow=()=>{
 const r=shR(),dn=DN_ON(),t=saleTot(r),cli=cl(r.c),st=stOf(r.s),L=r.ln.map(toLn),isDemo=r.no==="SI-2026-0412";
 const stockL=L.filter(l=>!calcLn(l,r.pl).svc);
 if(!SH.now||SH.now.no!==r.no){SH.now={no:r.no,q:{}};stockL.forEach(l=>{SH.now.q[l.id]=Math.max(0,l.q-shDvb(r,l.id))})}
 const remain=stockL.reduce((a,l)=>a+Math.max(0,l.q-shDvb(r,l.id)),0),dvKey=!stockL.length||remain===0?"delivered":stockL.some(l=>shDvb(r,l.id)>0)?"partial":"not_delivered";
 const retQ=id=>(r.ret||{})[id]||0;
 const tabs=[["lines","list","السطور"+(dn?" والتسليم":"")],["je","book-open","القيود المحاسبية"],["links","link-2","المرتجعات والارتباطات"],["log","history","سجل النشاط"]];
 const delRows=stockL.map(l=>{const c=calcLn(l,r.pl),b=shDvb(r,l.id),nw=SH.now.q[l.id]||0,left=l.q-b-nw,av=c.it.qty[r.s]||0,fac=c.u[1],over=nw*fac>av+1e-9&&!st[6],tooMuch=nw>l.q-b+1e-9;return {l,c,b,nw,left,over,tooMuch,av}});
 const dnBad=delRows.some(d=>d.over||d.tooMuch);
 const lines=`<div class="card flush"><div class="tw"><table><thead><tr><th>#</th><th>المنتج</th><th>${dn?"المطلوب":"الكمية"}</th>${dn?"<th>مسلّم سابقاً</th><th>مسلّم الآن</th><th>المتبقي</th>":""}<th>السعر</th><th>الخصم</th><th>الضريبة</th><th>الإجمالي (ر.س)</th>${CM.cost()?"<th>الربح</th>":""}</tr></thead><tbody>
 ${L.map((l,i)=>{const c=calcLn(l,r.pl),d=delRows.find(x=>x.l===l);return `<tr><td class="num">${i+1}</td><td>${itCell(c.it,c.svc?"خدمة — بلا مخزن":"")}</td><td>${qU(l.q,l.id,l.ui)}${retQ(l.id)?`<small class="cm-sub cmt-neg">${X("undo-2")} مرتجع ${CM.q(retQ(l.id),c.u[0])}</small>`:""}</td>
 ${dn?(c.svc?`<td colspan="3"><span class="cm-hid">لا تسليم لسطر خدمي</span></td>`:`<td>${CM.q(d.b,c.u[0])}</td><td>${fi("sh","now",SH.now.q[l.id]==null?0:SH.now.q[l.id],{i:l.id,c:"cmt-n"+(d.over||d.tooMuch?" bad":""),f:"dn"+l.id})}${d.over?`<small class="cm-sub cmt-neg">المتاح ${CM.q(d.av/c.u[1],c.u[0])} فقط</small>`:d.tooMuch?`<small class="cm-sub cmt-neg">أكثر من المتبقي</small>`:""}</td><td>${CM.q(Math.max(0,d.left),c.u[0])}</td>`):""}
 <td>${CM.n(c.price)}</td><td>${l.d?CM.n(l.d):"—"}</td><td>${CM.n(c.tax)}</td><td><b>${CM.n(c.tot)}</b></td>${CM.cost()?`<td>${c.svc?"—":CM.c(c.profit)}</td>`:""}</tr>`}).join("")}</tbody>
 <tfoot><tr class="tot"><td colspan="${dn?6:3}">الإجمالي</td><td></td><td>${CM.n(t.disc)}</td><td>${CM.n(t.tax)}</td><td>${CM.n(t.tot)}</td>${CM.cost()?`<td>${CM.c(t.profit)}</td>`:""}</tr></tfoot></table></div></div>`;
 const dnBox=dn&&stockL.length?`<div class="card sec"><div class="sec-h">${X("truck")}<b>سند الصرف — تسليم البضاعة</b>${dvTag(dvKey)}</div><div class="cmt-dnb"><div><b>${isDemo?"سندات الصرف السابقة":"لا سندات صرف سابقة"}</b>${isDemo?SH.dns.map(d=>`<div class="cmt-dnr">${X("file-check-2")}<span class="num">${d.no}</span><span class="num">${d.date}</span><span>${d.by}</span><span>${Object.keys(d.ln).map(k=>qU(d.ln[k],+k,(r.ln.find(a=>a[0]===+k)||[0,0])[1])).join(" · ")}</span><button class="btn btn-ghost sm" data-cm-toast="جاري تجهيز سند الصرف للطباعة">${X("printer")}</button></div>`).join(""):dim("أدخل الكميات أعلاه ثم أصدر أول سند صرف")}</div>
  <div>${dnBad?CM.note("لا يمكن إصدار السند: كمية تفوق المتبقي أو رصيد المخزن، والنظام لا يحذف الناقص تلقائياً.","bad"):CM.note(`سند الصرف لا يُعدّل الفاتورة؛ يخصم المخزون ويُرحّل قيد التكلفة${CM.cost()?"":" (يظهر لمن يملك رؤية التكلفة)"}.`)}
  <div class="cm-acts">${B("primary","package-check","إصدار سند صرف بالكميات المدخلة",A("sh","dn")+(dnBad||delRows.every(d=>!d.nw)||r.st==="draft"?" disabled":""))}${B("ghost","rotate-ccw","تسليم كامل المتبقي",A("sh","all"))}</div></div></div></div>`:"";
 const je=`<div class="cmt-jg">${CM.jprev("قيد الإيراد — "+(isDemo?"JE-2026-3381":"JE-2026-3"+r.no.slice(-3)),saleJournal(t,r.c,r.pay,r.s,"CC-110"),"مرحّل · اعتماد آلي · يرتبط بالفاتورة")}${CM.cost()?CM.jprev("قيد تكلفة البضاعة — "+(isDemo?"JE-2026-3382":"JE-2026-4"+r.no.slice(-3)),cogsJournal(L.reduce((a,l)=>a+calcLn(l,r.pl).cost*(dn?(shDvb(r,l.id)/l.q||0):1),0),r.s),dn?"بقدر ما سُلّم حتى الآن؛ كل سند صرف جديد يضيف قيد تكلفة":"مع التأكيد"):CM.note("قيد التكلفة يظهر لمن يملك صلاحية رؤية التكلفة.","","lock")}</div>
  <div class="cm-acts">${B("ghost","external-link","عرض القيد في دفتر اليومية",'data-go="finEntries"')}</div>`;
 const rets=RET.filter(x=>x.type==="sale"&&x.orig===r.no),links=`<div class="card sec"><div class="sec-h">${X("undo-2")}<b>المرتجعات على هذه الفاتورة</b></div>${rets.length?`<div class="tw"><table><thead><tr><th>المرتجع</th><th>التاريخ</th><th>المنتجات</th><th>السبب</th><th>القيمة</th><th>الحالة</th></tr></thead><tbody>${rets.map(x=>`<tr><td><a href="#" ${A("sl","retopen",x.no)}><b class="num">${x.no}</b></a></td><td class="num">${x.date}</td><td>${x.ln.map(a=>qU(a[2],a[0],a[1])).join(" · ")}</td><td>${x.reason}</td><td>${CM.n(retValue(x))}</td><td>${CM.st("confirmed")}</td></tr>`).join("")}</tbody></table></div>`:`<div class="cmt-emp sm"><span>لا مرتجعات. للتصحيح أنشئ مرتجعاً من هذه الفاتورة.</span></div>`}</div>
  <div class="card sec"><div class="sec-h">${X("link-2")}<b>مرتبطات أخرى</b></div>${kvl([["العميل",cli[1]],["المخزن",st[1]],["قائمة الأسعار",pl(r.pl)[1]],["سندات القبض",r.paid?`<span class="num">${CM.n(t.tot*r.paid)}</span> ر.س`:"لا يوجد"]])}</div>`;
 const log=`<div class="card sec"><div class="sec-h">${X("history")}<b>سجل النشاط</b></div><div class="cmt-log">${[["18 نوفمبر 09:12","سارة القحطاني","أنشأت المسودة"],["18 نوفمبر 09:40","سارة القحطاني","أكّدت الفاتورة وأُنشئ قيد الإيراد"],["18 نوفمبر 09:41","النظام","أُرسلت إلى الهيئة — "+(r.za==="reported"?"مشاركة":"قيد المشاركة")],["19 نوفمبر 11:05","محمد الزهراني","سند صرف DN-2026-0233"],["19 نوفمبر 15:30","سارة القحطاني","مرتجع RT-2026-0031"]].map(x=>`<div><i></i><span>${x[0]}</span><b>${x[1]}</b><em>${x[2]}</em></div>`).join("")}</div></div>`;
 const body=SH.tab==="lines"?lines+dnBox:SH.tab==="je"?je:SH.tab==="links"?links:log;
 return `${HEAD(`فاتورة بيع ${r.no}`,`${cli[1]} · ${r.date} · ${st[1]}`,B("ghost","printer","طباعة",'data-cm-toast="تم تجهيز الفاتورة للطباعة — وسُجّلت «طباعة» في سجل النشاط"')+B("ghost","share-2","مشاركة",'data-cm-toast="تم نسخ رابط المشاركة"')+B("primary","undo-2","إنشاء مرتجع",A("sl","retfrom",r.no)),["cmSales","فواتير البيع"])}
 ${CM.pvbar()}
 ${CM.flow([["مسودة","18 نوفمبر 09:12"],["مؤكدة","18 نوفمبر 09:40"],["التسليم",dn?(dvKey==="delivered"?"مكتمل":dvKey==="partial"?"جزئي":"لم يبدأ"):"مع التأكيد"],["السداد",r.paid>=1?"مدفوعة":r.paid>0?Math.round(r.paid*100)+"% مدفوع":"غير مدفوعة"]],dn?(dvKey==="delivered"?3:2):3)}
 ${CM.ro("فاتورة مؤكدة — للقراءة فقط",B("ghost sm","undo-2","مرتجع",A("sl","retfrom",r.no))+B("ghost sm","copy","نسخ كمسودة",'data-cm-toast="أُنشئت مسودة جديدة من الفاتورة"')).replace("المستند المرحّل لا يُعدّل ولا يُحذف. التصحيح بمستند عكسي.","الفاتورة المؤكدة لا تُعدّل ولا تُحذف. المتاح: مرتجع، أو نسخ كمسودة جديدة.")}
 <div class="cm-split"><div>
  <div class="vtabs">${tabs.map(x=>`<button class="${SH.tab===x[0]?"on":""}" ${A("sh","tab",x[0])}>${X(x[1])}<span>${x[2]}</span></button>`).join("")}</div>${body}</div>
 <aside class="cm-side"><div class="card sec"><div class="sec-h">${X("calculator")}<b>الإجماليات</b></div><div class="cmt-pn">${saPanelRows(t)}<div class="cmt-tot"><span>الإجمالي</span><b class="cmt-big num">${F(t.tot)}</b></div><div class="actrow sm"><div><b>المدفوع</b></div><span>${CM.n(t.tot*r.paid)}</span></div><div class="actrow sm"><div><b>المتبقي</b></div><b class="${r.paid<1?"cmt-neg":""}">${CM.n(t.tot*(1-r.paid))}</b></div>${pvOnly()?`<div class="actrow sm"><div><b>الربح</b></div><span>${CM.c(t.profit)}</span></div>`:""}</div></div>
  <div class="card sec"><div class="sec-h">${X("shield-check")}<b>الهيئة (ZATCA)</b>${zaTag(r.za)}</div><div class="cmt-pn">${kvl([["النوع",cli[2]?"ضريبية B2B":"ضريبية مبسّطة"],["وحدة الإصدار","الوحدة الحالية للنظام"],["ICV",'<span class="num">'+(1840+nz(r.no.slice(-4)))+"</span>"]])}${r.za!=="reported"&&r.st!=="draft"?B("ghost sm","send","مشاركة مع الهيئة الآن",'data-cm-toast="أُرسلت الفاتورة وستظهر حالتها بعد الرد" style="margin-top:8px"'):""}</div></div></aside></div>`};

/* ============ فواتير الشراء ============ */
const recTag=k=>k==="received"?CM.st("received","مستلمة كاملة"):k==="partial"?CM.st("partial","استلام جزئي"):CM.st("not_delivered","لم يُستلم");
const PS={st:"",q:""};
S.cmPurchases=()=>{
 let rows=PURCH.filter(r=>(CM.allBr()||stOf(r.s)[2]==="b1")&&(!PS.st||r.st===PS.st||r.rec===PS.st)&&(!PS.q||(r.no+sup(r.v)[1]).indexOf(PS.q)>-1));
 const conf=PURCH.filter(r=>r.st!=="draft"),due=conf.reduce((a,r)=>a+purTot(r).tot*(1-r.paid),0),grn=GRN.reduce((a,g)=>a+g.ln.reduce((b,l)=>b+l[2]*l[3],0),0),part=PURCH.filter(r=>r.rec==="partial").length;
 return `${HEAD("شراء بضاعة","فواتير الموردين بالوحدة والكمية والسعر، مع استلام كامل أو جزئي.",B("ghost","file-spreadsheet","Excel",'data-cm-toast="جاري تجهيز ملف Excel"')+B("primary","plus","فاتورة شراء",'data-go="cmPurchaseAdd"'))}
 ${CM.pvbar()}
 ${now(`${GRN.length?`<a href="#" ${A("pu","grn")}>${GRN.length} استلامات بلا فاتورة بقيمة ${CM.n(grn,0)} ر.س تنتظر الربط</a>`:"لا استلامات بلا فاتورة"}${part?` · ${part} فاتورة باستلام جزئي`:""}`)}
 <div class="cm-kpis"><div class="card cm-kpi"><span>${X("shopping-bag")}مشتريات الشهر</span><b class="num">${F(conf.reduce((a,r)=>a+purTot(r).ex,0),0)}</b><small>${conf.length} فاتورة مؤكدة</small></div><div class="card cm-kpi warn"><span>${X("alarm-clock")}مستحق للموردين</span><b class="num">${F(due,0)}</b><small>غير مسدّد</small></div><div class="card cm-kpi ${grn?"warn":"ok"}"><span>${X("package-open")}بضاعة لم تُفوتر</span><b class="num">${F(grn,0)}</b><small>حساب «بضاعة مستلمة لم تُفوتر»</small></div></div>
 ${CM.view(`<div class="cx-chips">${[["","الكل"],["draft","مسودة","n"],["confirmed","مؤكدة","g"],["partial","استلام جزئي","a"]].map(c=>`<button class="${PS.st===c[0]?"on":""}" ${A("pu","st",c[0])}>${c[2]?`<i class="d ${c[2]}"></i>`:""}${c[1]}<b class="num">${PURCH.filter(r=>!c[0]||r.st===c[0]||r.rec===c[0]).length}</b></button>`).join("")}</div>
 <div class="fbar"><div class="fb grow"><label>بحث</label><div class="inp">${X("search")}<input data-cmt-c="pu.q" value="${PS.q}" placeholder="رقم الفاتورة أو المورد"></div></div>${CM.brSel()}<div class="fb"><label>من تاريخ</label><input type="date" value="2026-11-01"></div><div class="fb"><label>إلى تاريخ</label><input type="date" value="${TODAY}"></div></div>
 ${rows.length?`<div class="card flush"><div class="tw"><table><thead><tr><th>رقم الفاتورة</th><th>التاريخ</th><th>المورد</th><th>المخزن</th><th>الإجمالي (ر.س)</th><th>الاستلام</th><th>السداد</th><th>الحالة</th><th class="act"></th></tr></thead><tbody>${rows.map(r=>{const t=purTot(r);return `<tr><td><b class="num">${r.no}</b>${r.inv?`<small class="cm-sub">فاتورة المورد <span class="num">${r.inv}</span></small>`:""}${r.ret?`<small class="cm-sub">${X("undo-2")} <span class="num">${r.retNo}</span></small>`:""}</td><td class="num">${r.date}</td><td><b>${sup(r.v)[1]}</b></td><td>${stOf(r.s)[1]}</td><td><b>${CM.n(t.tot)}</b></td><td>${recTag(r.rec)}${r.rec==="partial"?`<small class="cm-sub">${r.ln.map(l=>`${CM.q(l[4],uName(l[0],l[1]))} من ${CM.q(l[2])}`).join(" · ")}</small>`:""}</td><td>${r.st==="draft"?`<span class="cm-hid">—</span>`:payTag(r.paid)}</td><td>${r.st==="draft"?CM.st("draft"):r.ret?CM.st("partial","مرتجع جزئي"):CM.st("confirmed")}</td><td class="act"><div class="rowacts"><button class="icon-btn pri" title="عرض" data-cm-toast="يفتح عرض الفاتورة بنفس نمط فاتورة البيع">${X("eye")}</button>${r.st!=="draft"?`<button class="icon-btn pri" title="مرتجع" ${A("pu","ret",r.no)}>${X("undo-2")}</button>`:""}</div></td></tr>`}).join("")}</tbody></table></div>${PAGER(`عرض ${rows.length} من ${PURCH.length} فاتورة`)}</div>`:CM.nores()}
 <div class="card sec" id="cmt-grni"><div class="sec-h">${X("package-open")}<b>استلامات بلا فاتورة (GRNI)</b><span class="ms">البضاعة دخلت المخزن ولم تصل فاتورة المورد بعد</span></div>
 <div class="tw"><table><thead><tr><th>سند الاستلام</th><th>التاريخ</th><th>المورد</th><th>المخزن</th><th>المنتجات</th><th>القيمة عند الاستلام</th><th class="act"></th></tr></thead><tbody>${GRN.map(g=>`<tr><td><b class="num">${g.no}</b></td><td class="num">${g.date}</td><td>${sup(g.v)[1]}</td><td>${stOf(g.s)[1]}</td><td>${g.ln.map(l=>qU(l[2],l[0],l[1])).join(" · ")}</td><td>${CM.n(g.ln.reduce((a,l)=>a+l[2]*l[3],0))}</td><td class="act">${B("primary sm","link-2","ربط باستلام",A("pu","link",g.no))}</td></tr>`).join("")}</tbody></table></div></div>`,CM.empty("shopping-bag","لا توجد فواتير شراء بعد","سجّل فاتورة المورد بالمنتجات والوحدات، أو اربط استلاماً سابقاً بلا فاتورة.",B("primary","plus","فاتورة شراء",'data-go="cmPurchaseAdd"')))}`};

const PA0=()=>({v:"v1",s:"s1",date:TODAY,inv:"",grn:"",mode:"partial",ln:[{id:101,ui:1,q:60,p:125,r:60,b:0},{id:107,ui:2,q:10,p:1210,r:4,b:0},{id:102,ui:1,q:30,p:166,r:30,b:0}],rev:0,done:null,find:""});
let PA=PA0();
const paLn=l=>{const it=D.item(l.id),u=it.units[l.ui],base=l.r*u[1],v=(l.b||0)?l.q*l.p:l.r*l.p,tax=v*it.tax/100,st=Math.max(0,it.qty[PA.s]||0),inB=l.r*u[1],avgNew=l.b?it.cost:(st+inB)>0?(st*it.cost+inB*(l.p/u[1]))/(st+inB):it.cost;return {it,u,base,v,tax,avgNew,old:it.cost}};
S.cmPurchaseAdd=()=>{
 const g=ss("cmt.grn");if(g){const gr=GRN.find(x=>x.no===g);ss("cmt.grn",null);if(gr){PA=PA0();PA.v=gr.v;PA.s=gr.s;PA.grn=gr.no;PA.ln=gr.ln.map(l=>({id:l[0],ui:l[1],q:l[2],p:l[3],r:l[2],b:l[2],gc:l[3]}));PA.mode="full"}}
 if(PA.done)return `${HEAD("فاتورة شراء","","",["cmPurchases","شراء بضاعة"])}${CM.pvbar()}<div class="card h14-ok cmt-done">${X("check-circle-2")}<div><h3>تم تأكيد فاتورة الشراء ${PA.done}</h3><p>${PA.grn?"رُبطت بالاستلام "+PA.grn+" وسُوّي حساب «بضاعة مستلمة لم تُفوتر».":"أُضيفت الكميات المستلمة للمخزن وحُدّث المتوسط."}</p></div></div><div class="h14-next">${B("primary","list","شراء بضاعة",'data-go="cmPurchases"')}${B("ghost","plus","فاتورة جديدة",A("pa","reset"))}</div>`;
 const rows=PA.ln.map(paLn),T0={v:rows.reduce((a,x)=>a+x.v,0),tax:rows.reduce((a,x)=>a+x.tax,0)};T0.tot=T0.v+T0.tax;
 const bad=[];if(!PA.ln.length)bad.push("أضف منتجاً واحداً");PA.ln.forEach((l,i)=>{if(l.r>l.q)bad.push(`السطر ${i+1}: المستلم أكبر من المطلوب`);if(!(l.q>0))bad.push(`السطر ${i+1}: أدخل الكمية المطلوبة`)});
 const warn=[];if(!PA.inv)warn.push("لم يُدخل رقم فاتورة المورد");if(!PA.grn&&PA.ln.every(l=>!l.r))warn.push("لا استلام على أي سطر: الفاتورة لن تُدخل مخزوناً");const partial=PA.ln.some(l=>l.r<l.q);
 const invAcc=stOf(PA.s)[4];let J;
 if(PA.grn){const grnV=PA.ln.reduce((a,l)=>a+l.b*l.gc,0),diff=r2(T0.v-grnV);J=[jl(D.accounts.grni,r2(grnV),0)];if(diff>0)J.push(jl(invAcc,diff,0));if(T0.tax)J.push(jl(D.accounts.vatIn,r2(T0.tax),0));J.push(jl("2111 الموردون — "+sup(PA.v)[1],0,r2(T0.tot)));if(diff<0)J.push(jl(invAcc,0,-diff))}
 else J=[jl(invAcc,r2(T0.v),0,stOf(PA.s)[3].split(" ")[0]),jl(D.accounts.vatIn,r2(T0.tax),0),jl("2111 الموردون — "+sup(PA.v)[1],0,r2(T0.tot))];
 const res=PA.find.trim()&&!PA.grn?D.items.filter(i=>i.act&&i.kind==="stock"&&(i.n+i.code).indexOf(PA.find.trim())>-1).slice(0,5):[];
 return `${HEAD("فاتورة شراء","اختر المورد والمخزن، ثم السطور بالوحدة والكمية والسعر. سجّل ما استُلم فعلاً على كل سطر.",B("ghost","","إلغاء",'data-go="cmPurchases"')+B("ghost","save","حفظ كمسودة",A("pa","draft")),["cmPurchases","شراء بضاعة"])}
 ${CM.pvbar()}
 ${PA.grn?CM.note(`<b>مربوطة بالاستلام ${PA.grn}</b>: البضاعة موجودة في المخزن، والفاتورة تسوّي حساب «بضاعة مستلمة لم تُفوتر» وتسجّل فرق السعر إن وُجد. <button class="btn btn-ghost sm" ${A("pa","unlink")}>فك الربط</button>`,"ok","link-2"):""}
 <div class="cvform h15"><aside class="cvopts cmt-aside"><div class="card"><div class="sec-h">${X("calculator")}<b>الإجماليات</b></div><div class="cmt-pn"><div class="actrow sm"><div><b>قبل الضريبة</b></div><span>${CM.n(T0.v)}</span></div><div class="actrow sm"><div><b>ضريبة المدخلات</b></div><span>${CM.n(T0.tax)}</span></div><div class="cmt-tot"><span>المستحق</span><b class="cmt-big num">${F(T0.tot)}</b></div></div></div>
  <div class="card cmt-pn2 ${bad.length?"bad":"ok"}"><div class="cmt-blk ${bad.length?"":"ok"}">${X(bad.length?"octagon-alert":"check-circle-2")}<div><b>${bad.length?"التأكيد ممنوع":"جاهزة للتأكيد"}</b><small>${bad[0]||(warn[0]||"لا ملاحظات")}</small></div></div>${PA.rev?B("primary","check","تأكيد الفاتورة",A("pa","confirm")+(bad.length?" disabled":"")):B("primary","scan-search","مراجعة قبل التأكيد",A("pa","review"))}</div></aside>
 <div class="cvfields">
  <div class="card cmt-sec"><div class="fs-h"><span class="fs-n num">1</span><b>بيانات الفاتورة</b></div><div class="fgrid cmt-g3">
   ${FLD("المورد",fs("pa","v",SUP.map(x=>[x[0],x[1]]),PA.v,{dis:!!PA.grn}),1,"الرقم الضريبي "+sup(PA.v)[2])}${FLD("المخزن المستلِم",fs("pa","s",D.stores.filter(s=>CM.allBr()||s[2]==="b1").map(s=>[s[0],s[1]]),PA.s,{dis:!!PA.grn}),1)}${FLD("تاريخ الفاتورة",`<input type="date" data-cmt-c="pa.date" value="${PA.date}">`,1)}
   ${FLD("رقم فاتورة المورد",fi("pa","inv",PA.inv,{ph:"مثال GC-77413"}),1)}
   ${FLD("ربط باستلام سابق",fs("pa","grn",[["","بدون — شراء مباشر"]].concat(GRN.map(g=>[g.no,g.no+" · "+sup(g.v)[1]])),PA.grn),0,"استلامات بلا فاتورة فقط")}
   ${FLD("نوع الاستلام",`<div class="seg cmt-seg"><button class="${PA.mode==="full"?"on":""}" ${A("pa","mode","full")} ${PA.grn?"disabled":""}>استلام كامل</button><button class="${PA.mode==="partial"?"on":""}" ${A("pa","mode","partial")} ${PA.grn?"disabled":""}>استلام جزئي</button></div>`,0,PA.mode==="full"?"المستلم = المطلوب":"أدخل المستلم لكل سطر")}</div></div>
  <div class="card cmt-sec"><div class="fs-h"><span class="fs-n num">2</span><b>السطور</b></div>
   ${PA.grn?"":`<div class="cmt-find"><div class="inp">${X("search")}<input data-cmt-find="pa" data-cmt-f="find" value="${PA.find}" placeholder="ابحث عن منتج بالاسم أو الكود" autocomplete="off"></div><div class="cmt-res">${res.map(i=>`<button ${A("pa","add",i.id)}>${itCell(i,"")}<span>${CM.c(i.cost*i.units[0][1])} ر.س تكلفة</span><em>${CM.q(D.tot(i),i.units[0][0])}</em></button>`).join("")}</div></div>`}
   <div class="tw"><table class="cmt-lt"><thead><tr><th>المنتج</th><th>الوحدة</th><th>المطلوب</th>${PA.grn?"<th>مستلم سابقاً</th>":""}<th>${PA.grn?"المستلم":"المستلم الآن"}</th><th>سعر الوحدة</th><th>الإجمالي (ر.س)</th><th></th></tr></thead><tbody>
   ${PA.ln.map((l,i)=>{const x=rows[i],over=l.r>l.q;return `<tr class="${over?"cm-row-bad":""}"><td>${itCell(x.it,"")}</td><td>${PA.grn?x.u[0]:fs("pa","ui",x.it.units.map((u,k)=>[k,u[0]]),l.ui,{i})}</td><td>${PA.grn?CM.q(l.q,x.u[0]):fi("pa","q",l.q,{i,c:"cmt-n",f:"pq"+i})}</td>${PA.grn?`<td>${CM.q(l.b,x.u[0])}</td>`:""}<td>${PA.grn?CM.q(l.r,x.u[0]):fi("pa","r",l.r,{i,c:"cmt-n"+(over?" bad":""),f:"pr"+i})}${over?`<small class="cm-sub cmt-neg">أكبر من المطلوب</small>`:""}</td><td>${fi("pa","p",l.p,{i,c:"cmt-n",f:"pp"+i})}${PA.grn?dim("عند الاستلام "+CM.n(l.gc)):""}</td><td><b>${CM.n(x.v+x.tax)}</b>${dim("ضريبة "+CM.n(x.tax))}</td><td class="act">${PA.grn?"":`<button class="icon-btn" ${A("pa","del",i)}>${X("trash-2")}</button>`}</td></tr>
   <tr class="cmt-band ${over?"bad":l.r<l.q&&!PA.grn?"warn":""}"><td colspan="${PA.grn?7:6}"><div class="cmt-bp"><span><small>المكافئ بالوحدة الأساسية</small><b>${CM.q(x.base,x.it.units[0][0])}</b></span>${l.r<l.q&&!PA.grn?`<span><small>متبقٍ لم يُستلم</small><b>${CM.q(l.q-l.r,x.u[0])}</b></span>`:""}${CM.cost()?`<span><small>متوسط التكلفة (للوحدة الأساسية)</small>${l.b?`<b>${CM.n(x.old)}</b> <em>محدّث عند الاستلام</em>`:`<b>${CM.n(x.old)}</b> ← <b class="${x.avgNew>x.old?"cmt-neg":"cmt-pos"}">${CM.n(x.avgNew)}</b>`}</span>`:""}</div></td></tr>`}).join("")}</tbody></table></div>
   ${partial&&!PA.grn?CM.note("استلام جزئي: تُسجَّل الفاتورة بما استُلم فقط، ويبقى المتبقي مفتوحاً على أمر الشراء ويُفوتر عند وصوله.","warn"):""}${!CM.cost()?`<div class="cmt-ltf"><span class="cm-flag">${X("lock")}المتوسط الجديد للتكلفة يظهر لمن يملك رؤية التكلفة</span></div>`:""}</div>
  <div class="card cmt-sec"><div class="fs-h"><span class="fs-n num">3</span><b>المراجعة قبل التأكيد</b></div>${PA.rev?`<div class="cmt-revc">${[...bad.map(m=>["bad",m]),...warn.map(m=>["warn",m]),["ok","الفترة المحاسبية مفتوحة"],["ok",PA.grn?"الربط بالاستلام يسوّي حساب بضاعة لم تُفوتر":"الكميات المستلمة تُضاف للمخزن ويُحدَّث المتوسط"]].map(c=>`<div class="cmt-chk ${c[0]}">${X(c[0]==="ok"?"check-circle-2":c[0]==="warn"?"alert-triangle":"octagon-alert")}<span>${c[1]}</span></div>`).join("")}</div>${CM.jprev(PA.grn?"القيد المتوقع (بعد استلام سابق)":"القيد المتوقع (شراء مباشر)",J,"قيد شراء — يُنشأ عند التأكيد")}
   <div class="cvactions">${B("primary","check","تأكيد الفاتورة",A("pa","confirm")+(bad.length?" disabled":""))}${B("ghost","pencil","رجوع للتعديل",A("pa","unreview"))}</div>`:`<div class="cmt-emp">${X("scan-search")}<b>راجع قبل التأكيد</b>${B("primary","scan-search","مراجعة قبل التأكيد",A("pa","review"))}</div>`}</div></div></div>`};

/* ============ المرتجعات ============ */
const RLS={t:""};
S.cmReturns=()=>{
 const rows=RET.filter(r=>!RLS.t||r.type===RLS.t);
 return `${HEAD("المرتجعات","كل مرتجع يبدأ من الفاتورة الأصلية، ولا يتجاوز الكمية المباعة أو المشتراة ناقص المرتجع سابقاً.",B("ghost","receipt","مرتجع مبيعات",A("rt","new","sale"))+B("primary","shopping-bag","مرتجع مشتريات",A("rt","new","purchase")))}
 ${CM.pvbar()}
 ${now("اختر الفاتورة الأصلية من فواتير البيع أو الشراء ثم اضغط «مرتجع»، أو ابدأ من الأزرار أعلاه.")}
 ${CM.view(`<div class="cx-chips">${[["","الكل"],["sale","مرتجع مبيعات","n"],["purchase","مرتجع مشتريات","a"]].map(c=>`<button class="${RLS.t===c[0]?"on":""}" ${A("rt","t",c[0])}>${c[2]?`<i class="d ${c[2]}"></i>`:""}${c[1]}<b class="num">${RET.filter(r=>!c[0]||r.type===c[0]).length}</b></button>`).join("")}</div>
 ${rows.length?`<div class="card flush"><div class="tw"><table><thead><tr><th>رقم المرتجع</th><th>التاريخ</th><th>النوع</th><th>الفاتورة الأصلية</th><th>الطرف</th><th>المنتجات</th><th>القيمة (ر.س)</th><th>الحالة</th></tr></thead><tbody>${rows.map(r=>{const o=r.type==="sale"?sale(r.orig):purch(r.orig);return `<tr><td><b class="num">${r.no}</b><small class="cm-sub">${r.reason}</small></td><td class="num">${r.date}</td><td>${TAG(r.type==="sale"?"n":"a",r.type==="sale"?"مرتجع مبيعات":"مرتجع مشتريات")}</td><td><a href="#" ${A("sl","orig",r.orig)}><b class="num">${r.orig}</b> ${X("arrow-up-left")}</a></td><td>${r.type==="sale"?cl(o.c)[1]:sup(o.v)[1]}</td><td>${r.ln.map(a=>qU(a[2],a[0],a[1])).join(" · ")}</td><td><b>${CM.n(retValue(r))}</b></td><td>${CM.st("confirmed")}</td></tr>`}).join("")}</tbody></table></div>${PAGER(`عرض ${rows.length} مرتجعات`)}</div>`:CM.nores()}`,CM.empty("undo-2","لا توجد مرتجعات","ابدأ من فاتورة مؤكدة واختر السطور والكميات المرتجعة.",B("primary","receipt","مرتجع مبيعات",A("rt","new","sale"))))}
 ${CM.note("المرتجع مستند مستقل مرتبط بالفاتورة الأصلية في الاتجاهين. لا تُعدَّل الفاتورة المؤكدة ولا تُحذف.","","link-2")}`};

const RA={type:"sale",orig:"SI-2026-0412",q:{},reason:"",mode:"credit",done:null};
const raSrc=()=>RA.type==="sale"?sale(RA.orig):purch(RA.orig);
const shDvbR=(o,id)=>o.no==="SI-2026-0412"?SH.dns.reduce((s,d)=>s+(d.ln[id]||0),0):delivered(o,id);
const raLines=()=>{const o=raSrc(),dn=DN_ON();return o.ln.map(a=>{const id=a[0],it=D.item(id),u=it.units[a[1]],ord=a[2],before=(o.ret||{})[id]||0,rec=RA.type==="sale"?(dn&&it.kind!=="service"?shDvbR(o,id):ord):a[4],cap=Math.max(0,Math.min(ord,rec)-before),price=RA.type==="sale"?calcLn(toLn(a),o.pl).price:a[3];return {id,it,u,ord,before,rec,cap,price,q:nz(RA.q[id])}})};
S.cmReturnAdd=()=>{
 const f=ss("cmt.retFrom");if(f){RA.type=f.indexOf("PU")===0?"purchase":"sale";RA.orig=f;RA.q={};RA.done=null;ss("cmt.retFrom",null)}
 if(RA.done)return `${HEAD("مرتجع","","",["cmReturns","المرتجعات"])}${CM.pvbar()}<div class="card h14-ok cmt-done">${X("check-circle-2")}<div><h3>تم اعتماد المرتجع ${RA.done}</h3><p>رُبط بالفاتورة ${RA.orig} وظهر في الاتجاهين، وأُنشئت قيود ${RA.type==="sale"?"مردودات المبيعات وإعادة المخزون":"مرتجع المشتريات"}.</p></div></div><div class="h14-next">${B("primary","list","المرتجعات",'data-go="cmReturns"')}${B("ghost","plus","مرتجع جديد",A("ra","reset"))}</div>`;
 const o=raSrc(),L=raLines(),sl=RA.type==="sale",who=sl?cl(o.c)[1]:sup(o.v)[1];
 const lineBad=L.map(l=>l.q>l.cap+1e-9),anyQ=L.some(l=>l.q>0),bad=lineBad.some(Boolean)||!anyQ;
 let net=0,tax=0,cost=0;L.forEach(l=>{if(l.q>0){const v=l.q*l.price;net+=v;tax+=v*l.it.tax/100;cost+=l.it.kind==="service"?0:l.it.cost*l.q*l.u[1]}});const tot=net+tax,inv=stOf(o.s);
 const J=sl?[jl(D.accounts.returns,r2(net),0),jl(D.accounts.vatOut,r2(tax),0),jl(RA.mode==="cash"?"1111 الصندوق الرئيسي":"1121 العملاء — "+who,0,r2(tot))]:[jl("2111 الموردون — "+who,r2(tot),0),jl(inv[4],0,r2(net)),jl(D.accounts.vatIn,0,r2(tax))];
 const docs=sl?SALES.filter(x=>x.st!=="draft"):PURCH.filter(x=>x.st!=="draft");
 return `${HEAD("مرتجع جديد","اختر الفاتورة الأصلية ثم السطور والكميات. لا يُرجع أكثر من المتاح للإرجاع في كل سطر.",B("ghost","","إلغاء",'data-go="cmReturns"'),["cmReturns","المرتجعات"])}
 ${CM.pvbar()}
 <div class="cvform h15"><aside class="cvopts cmt-aside"><div class="card"><div class="sec-h">${X("calculator")}<b>قيمة المرتجع</b></div><div class="cmt-pn"><div class="actrow sm"><div><b>قبل الضريبة</b></div><span>${CM.n(net)}</span></div><div class="actrow sm"><div><b>الضريبة</b></div><span>${CM.n(tax)}</span></div><div class="cmt-tot"><span>${sl?"يُردّ للعميل":"يُردّ من المورد"}</span><b class="cmt-big num">${F(tot)}</b></div></div></div>
  <div class="card cmt-pn2 ${bad?"bad":"ok"}"><div class="cmt-blk ${bad?"":"ok"}">${X(bad?"octagon-alert":"check-circle-2")}<div><b>${lineBad.some(Boolean)?"كمية تتجاوز المتاح للإرجاع":!anyQ?"اختر كمية مرتجعة":"جاهز للاعتماد"}</b><small>${bad?"عدّل الكميات المعلّمة":"راجع القيد ثم اعتمد"}</small></div></div>${B("primary","check","اعتماد المرتجع",A("ra","confirm")+(bad?" disabled":""))}</div></aside>
 <div class="cvfields">
  <div class="card cmt-sec"><div class="fs-h"><span class="fs-n num">1</span><b>الفاتورة الأصلية</b></div><div class="fgrid cmt-g3">
   ${FLD("نوع المرتجع",`<div class="seg cmt-seg"><button class="${sl?"on":""}" ${A("ra","type","sale")}>مبيعات</button><button class="${!sl?"on":""}" ${A("ra","type","purchase")}>مشتريات</button></div>`,1)}
   ${FLD("الفاتورة",fs("ra","orig",docs.map(x=>[x.no,x.no+" · "+(sl?cl(x.c)[1]:sup(x.v)[1])]),RA.orig),1,"من فواتير مؤكدة فقط")}
   ${FLD(sl?"العميل":"المورد",`<input value="${who}" disabled>`)}
   ${FLD("تاريخ الفاتورة",`<input value="${o.date}" disabled>`)}${FLD("المخزن",`<input value="${inv[1]}" disabled>`)}
   ${FLD("سبب المرتجع",fs("ra","reason",[["","اختر السبب…"],["تالف","بضاعة تالفة"],["خطأ","خطأ في المنتج أو الكمية"],["رفض","رفض العميل"],["انتهاء","قريب الانتهاء"]],RA.reason),1)}</div></div>
  <div class="card cmt-sec"><div class="fs-h"><span class="fs-n num">2</span><b>السطور والكميات المرتجعة</b></div><div class="tw"><table class="cmt-lt"><thead><tr><th>المنتج</th><th>${sl?"المباع":"المشترى"}</th>${(!sl||DN_ON())?`<th>${sl?"المسلَّم":"المستلم"}</th>`:""}<th>مرتجع سابقاً</th><th>المتاح للإرجاع</th><th>الكمية المرتجعة</th><th>القيمة (ر.س)</th></tr></thead><tbody>
  ${L.map((l,i)=>`<tr class="${lineBad[i]?"cm-row-bad":""}"><td>${itCell(l.it,l.it.kind==="service"?"خدمة":"")}</td><td>${CM.q(l.ord,l.u[0])}</td>${(!sl||DN_ON())?`<td>${CM.q(l.rec,l.u[0])}</td>`:""}<td>${l.before?CM.q(l.before,l.u[0]):`<span class="cm-hid">—</span>`}</td><td><b>${CM.q(l.cap,l.u[0])}</b><small class="cm-sub">${sl?"مباع":"مشترى"} ${CM.q(l.ord)} − مرتجع ${CM.q(l.before)}${l.rec<l.ord?` − غير ${sl?"مسلَّم":"مستلم"} ${CM.q(l.ord-l.rec)}`:""}</small></td>
  <td>${l.cap>0?fi("ra","q",RA.q[l.id]||"",{i:l.id,c:"cmt-n"+(lineBad[i]?" bad":""),f:"rq"+l.id,ph:"0"}):`<span class="cm-flag">${X("ban")}لا متاح</span>`}${lineBad[i]?`<small class="cm-sub cmt-neg">المتاح للإرجاع ${CM.q(l.cap,l.u[0])} فقط</small>`:""}</td><td>${l.q>0?CM.n(l.q*l.price*(1+l.it.tax/100)):"—"}</td></tr>`).join("")}</tbody></table></div></div>
  <div class="card cmt-sec"><div class="fs-h"><span class="fs-n num">3</span><b>التسوية والقيد</b></div><div class="fgrid cmt-g3">${FLD(sl?"طريقة رد المبلغ":"طريقة التسوية",fs("ra","mode",[["credit",sl?"إشعار دائن على حساب العميل":"خصم من حساب المورد"],["cash",sl?"رد نقدي":"استرداد نقدي من المورد"]],RA.mode),1)}</div>
   <div class="cmt-jg">${CM.jprev("القيد المتوقع",J,"يرتبط بالمرتجع والفاتورة "+o.no)}${sl?(CM.cost()?CM.jprev("إعادة المخزون بتكلفة البيع الأصلية",[jl(inv[4],r2(cost),0),jl(D.accounts.cogs,0,r2(cost))],"التكلفة من حركة الفاتورة الأصلية"):CM.note("قيد التكلفة يظهر لمن يملك صلاحية رؤية التكلفة.","","lock")):""}</div>
   <div class="cvactions">${B("primary","check","اعتماد المرتجع",A("ra","confirm")+(bad?" disabled":""))}${B("ghost","","إلغاء",'data-go="cmReturns"')}</div></div></div></div>`};

/* ============ قوائم الأسعار ============ */
const PLS={add:0,f:{name:"",bind:"كل العملاء · كل الفروع",from:TODAY,to:"",pr:60}};
S.cmPrices=()=>{
 const rows=PL.slice().sort((a,b)=>a[5]-b[5]);
 return `${HEAD("الأسعار","القائمة الأعلى أولوية (الرقم الأصغر) تُطبَّق أولاً عند تطابق العميل أو الفرع أو التاريخ.",B("ghost","file-spreadsheet","استيراد من Excel",'data-go="cmImport"')+B("primary","plus","قائمة جديدة",A("pr","add")))}
 ${CM.pvbar()}
 ${now(`${PL.some(p=>plSt(p)==="scheduled")?"قائمة «عرض رمضان 1448» مجدولة وتبدأ 2027-02-01. ":""}افتح محرر الأسعار لتعديل أسعار قائمة بالجملة.`)}
 ${PLS.add?`<div class="card cmt-sec"><div class="fs-h"><span class="fs-n num">+</span><b>قائمة أسعار جديدة</b></div><div class="fgrid cmt-g3">${FLD("الاسم",fi("prf","name",PLS.f.name,{rtl:1,ph:"مثال: جملة الدمام"}),1)}${FLD("الربط",fs("prf","bind",[["كل العملاء · كل الفروع","كل العملاء · كل الفروع"],["تصنيف العملاء «جملة»","تصنيف العملاء «جملة»"],["فرع جدة","فرع جدة"],["عميل محدد","عميل محدد"]],PLS.f.bind))}${FLD("الأولوية",fi("prf","pr",PLS.f.pr),0,"الأصغر أولاً")}${FLD("من تاريخ",`<input type="date" data-cmt-c="prf.from" value="${PLS.f.from}">`,1)}${FLD("إلى تاريخ",`<input type="date" data-cmt-c="prf.to" value="${PLS.f.to}">`,0,"اتركه فارغاً لتبقى سارية")}</div><div class="cvactions" style="padding:0 16px 16px">${B("primary","check","إنشاء القائمة",A("pr","save"))}${B("ghost","","إلغاء",A("pr","add"))}</div></div>`:""}
 ${CM.view(`<div class="card flush"><div class="tw"><table><thead><tr><th>الاسم</th><th>الربط</th><th>من</th><th>إلى</th><th>الأولوية</th><th>الأسعار</th><th>الحالة</th><th class="act"></th></tr></thead><tbody>${rows.map(p=>{const k=plSt(p),cnt=Object.keys(PO).filter(x=>x.indexOf(p[0]+"|")===0).length;return `<tr class="${k==="stopped"||k==="expired"?"cmt-mut":""}"><td><b>${p[1]}</b><small class="cm-sub">${p[0]==="pl1"?"القائمة الافتراضية":p[7]<1?"أقل من الأساسي "+Math.round((1-p[7])*100)+"%":""}</small></td><td>${p[2]}</td><td class="num">${p[3]}</td><td class="num">${p[4]||"مفتوحة"}</td><td class="num">${p[5]}</td><td>${cnt?`<span class="num">${cnt}</span> سعر مخصص`:`<span class="cm-hid">محسوبة من الأساسي</span>`}</td><td>${CM.st(k)}</td><td class="act"><div class="rowacts"><button class="icon-btn pri" title="تعديل الأسعار" ${A("pr","edit",p[0])}>${X("table-2")}</button><button class="icon-btn pri" title="نسخ" ${A("pr","copy",p[0])}>${X("copy")}</button><button class="icon-btn pri" title="${p[6]?"إيقاف":"تفعيل"}" ${A("pr","stop",p[0])} ${p[0]==="pl1"?"disabled":""}>${X(p[6]?"pause":"play")}</button></div></td></tr>`}).join("")}</tbody></table></div>${PAGER(`عرض ${rows.length} قوائم`)}</div>`,CM.empty("tags","لا توجد قوائم أسعار","القائمة الافتراضية «التجزئة» تُنشأ مع أول منتج. أضف قائمة للجملة أو لفرع أو لعرض بتاريخ.",B("primary","plus","قائمة جديدة",A("pr","add"))))}
 ${CM.note("إيقاف القائمة لا يحذفها ولا يغيّر الفواتير السابقة؛ يمنع اختيارها في فواتير جديدة فقط.","","pause-circle")}`};

const PE={pl:"pl2",ed:{},sel:{},pct:"",cat:"all",rnd:"0.05",q:""};
const peRows=()=>{const r=[];D.items.filter(i=>i.act&&(i.kind==="stock"||i.kind==="service")).forEach(it=>it.units.forEach((u,ui)=>r.push({it,u,ui,k:it.id+"|"+ui})));return r.filter(x=>(PE.cat==="all"||catTop(x.it)===PE.cat)&&(!PE.q||(x.it.n+x.it.code).indexOf(PE.q)>-1))};
S.cmPriceEdit=()=>{
 const p=pl(PE.pl),rows=peRows(),cats=[...new Set(D.items.filter(i=>i.act).map(catTop))],nEd=Object.keys(PE.ed).length,nSel=Object.values(PE.sel).filter(Boolean).length;
 const cur=x=>priceRec(PE.pl,x.it.id,x.ui),eff=x=>PE.ed[x.k]&&PE.ed[x.k].p!=null?PE.ed[x.k].p:cur(x).p,minOf=x=>PE.ed[x.k]&&PE.ed[x.k].min!=null?PE.ed[x.k].min:cur(x).min;
 return `${HEAD("محرر الأسعار","عدّل أسعار «"+p[1]+"» بالمنتج والوحدة، أو غيّرها بنسبة مئوية على المحدد. لا يُحفظ شيء قبل «حفظ التعديلات».",B("ghost","","رجوع",'data-go="cmPrices"')+B("primary","save",`حفظ التعديلات${nEd?` (${nEd})`:""}`,A("pe","save")+(nEd?"":" disabled")),["cmPrices","الأسعار"])}
 ${CM.pvbar()}
 <div class="fbar"><div class="fb"><label>القائمة</label>${fs("pe","pl",PL.map(x=>[x[0],x[1]]),PE.pl)}</div><div class="fb"><label>القسم</label>${fs("pe","cat",[["all","كل الأقسام"]].concat(cats.map(c=>[c,c])),PE.cat)}</div><div class="fb grow"><label>بحث</label><div class="inp">${X("search")}<input data-cmt-c="pe.q" value="${PE.q}" placeholder="اسم المنتج أو كوده"></div></div></div>
 <div class="card cmt-tool"><div class="cmt-tl">${X("percent")}<b>تغيير بنسبة مئوية</b><small>${nSel?`على ${nSel} سطر محدد`:`على كل الأسطر المعروضة (${rows.length})`}</small></div><div class="cmt-tr">${fi("pe","pct",PE.pct,{ph:"مثال 5 أو -3",c:"cmt-n"})}<span>%</span>${fs("pe","rnd",[["0.05","تقريب لأقرب 0.05"],["0.25","أقرب 0.25"],["1","أقرب ريال"],["0","بدون تقريب"]],PE.rnd)}${B("ghost","calculator","طبّق كمعاينة",A("pe","apply"))}${nEd?B("ghost","rotate-ccw","تراجع عن المعاينة",A("pe","discard")):""}</div></div>
 ${nEd?CM.note(`<b>${nEd} تعديلات غير محفوظة</b> — الأسطر المعلّمة بالأصفر. لن تؤثر على فواتير مؤكدة سابقة.`,"warn"):""}
 ${CM.view(`<div class="card flush"><div class="tw"><table class="cmt-lt"><thead><tr><th><input type="checkbox" ${A("pe","all")} ${nSel&&nSel===rows.length?"checked":""}></th><th>المنتج</th><th>الوحدة</th><th>السعر الأساسي</th><th>سعر القائمة</th><th>الفرق عن الأساسي</th><th>أقل كمية</th>${CM.cost()?"<th>الهامش على التكلفة</th>":""}</tr></thead><tbody>
 ${rows.map(x=>{const c=cur(x),e=eff(x),ed=PE.ed[x.k],ch=ed&&((ed.p!=null&&Math.abs(ed.p-c.p)>1e-9)||(ed.min!=null&&ed.min!==c.min)),df=x.u[2]?(e/x.u[2]-1)*100:0,cost=x.it.cost*x.u[1],mg2=e?(e-cost)/e*100:0,below=CM.cost()&&x.it.kind!=="service"&&e<cost;
  return `<tr class="${ch?"cmt-chg":""}"><td><input type="checkbox" ${A("pe","sel",x.k)} ${PE.sel[x.k]?"checked":""}></td><td>${itCell(x.it,"")}</td><td>${x.u[0]}${x.u[1]!==1?dim(x.u[1]+" "+x.it.units[0][0]):""}</td><td>${CM.n(x.u[2])}</td><td>${fi("pe","p",e,{i:x.k,c:"cmt-n"+(below?" bad":""),f:"pe"+x.k})}</td><td class="num ${df<0?"cmt-neg":df>0?"cmt-pos":""}"><span dir="ltr">${df?(df>0?"+":"")+df.toFixed(1)+"%":"—"}</span></td><td>${fi("pe","min",minOf(x)||"",{i:x.k,c:"cmt-n",ph:"—",f:"pm"+x.k})}</td>${CM.cost()?`<td>${x.it.kind==="service"?"—":`<b class="num ${below?"cmt-neg":""}" dir="ltr">${mg2.toFixed(1)}%</b>${below?`<small class="cm-sub cmt-neg">أقل من التكلفة ${CM.n(cost)}</small>`:""}`}</td>`:""}</tr>`}).join("")}</tbody></table></div>${PAGER(`عرض ${rows.length} سطر (منتج × وحدة)`)}</div>`,CM.empty("table-2","لا منتجات لتسعيرها","أضف منتجاتاً أولاً من شاشة المنتجات.",B("primary","package","المنتجات",'data-go="cmItems"')))}
 ${!CM.cost()?`<div class="cmt-ltf"><span class="cm-flag">${X("lock")}الهامش وتحذير «أقل من التكلفة» يظهران لمن يملك رؤية التكلفة</span></div>`:""}`};
/* ============ تقارير التجارة ============ */
/* [المجموعة, الأيقونة, [[اسم التقرير, يحتاج رؤية التكلفة, مفتاح]]] — موجز التصميم §12.2 */
const CAT=[
 ["المبيعات","receipt",[["المبيعات بالفترة والفرع والجهاز والكاشير والساعة",0],["المبيعات بالمنتج والقسم",0],["المبيعات بطرق الدفع",0],["الخصومات والمرتجعات",0],["مجمل الربح",1,"gp"]]],
 ["المخزون","package",[["الأرصدة",0],["تقييم المخزون",1,"sv"],["كارت المنتج",0],["تحت حد الطلب",0],["الراكد",1],["قريب الانتهاء",0],["فروقات الجرد",1],["مبيعات بدون رصيد",0]]],
 ["النقدية","wallet",[["تقرير X وتقرير Z",0],["فروقات الصناديق",0],["مطابقة مدى",0]]],
 ["الضريبة","percent",[["إقرار القيمة المضافة شاملاً الكاشير",0],["حالة التبليغ للهيئة",0]]],
 ["المطعم","utensils-crossed",[["تكلفة الطبق",1],["الاستهلاك النظري مقابل الفعلي",1]]]];
const SOLD={101:620,102:540,103:210,104:14200,105:900,106:260.5,107:21500,108:9000,109:84};
const PERS={m:["هذا الشهر",1],q:["هذا الربع",2.85],y:["هذه السنة",11.2]};
const RP={per:"m",dim:"cat",open:{},sel:"gp"};
const repRows=()=>{const f=PERS[RP.per][1];return D.items.filter(i=>SOLD[i.id]).map(it=>{const svc=it.kind==="service",q=SOLD[it.id]*f,gross=r2(q*it.units[0][2]),ret=r2(gross*.016),disc=r2(gross*.02),net=r2(gross-ret-disc),cogs=svc?0:r2(q*it.cost*(1-.016));return {it,svc,q,gross,ret,disc,net,cogs,profit:r2(net-cogs)}})};
const sm=(a,k)=>r2(a.reduce((s,x)=>s+x[k],0));
const mg=(p,n)=>n?(p/n*100).toFixed(1)+"%":"—";
const stockVal=()=>D.stores.map(s=>[s,r2(D.items.reduce((a,it)=>a+(it.qty[s[0]]||0)*it.cost,0))]);
S.cmReports=()=>{
 const rows=repRows(),T={gross:sm(rows,"gross"),ret:sm(rows,"ret"),disc:sm(rows,"disc"),net:sm(rows,"net"),cogs:sm(rows,"cogs"),profit:sm(rows,"profit")};
 const goods=r2(rows.filter(x=>!x.svc).reduce((a,x)=>a+x.gross,0)),svc=r2(T.gross-goods),tbNet=r2(goods+svc-T.ret-T.disc),tbProfit=r2(tbNet-T.cogs),sv=stockVal(),svT=r2(sv.reduce((a,x)=>a+x[1],0));
 const groups=RP.dim==="cat"?[...new Set(rows.map(x=>catTop(x.it)))].map(k=>({k,r:rows.filter(x=>catTop(x.it)===k)})):rows.map(x=>({k:x.it.n,r:[x]}));
 const g=groups.map(x=>({k:x.k,r:x.r,gross:sm(x.r,"gross"),ret:sm(x.r,"ret"),disc:sm(x.r,"disc"),net:sm(x.r,"net"),cogs:sm(x.r,"cogs"),profit:sm(x.r,"profit")})).sort((a,b)=>b.profit-a.profit),mx=Math.max(1,...g.map(x=>x.profit));
 const catalog=`<div class="cmt-cat">${CAT.filter(c=>c[0]!=="المطعم"||CM.on("restaurant")||true).map(c=>`<div class="card sec"><div class="sec-h">${X(c[1])}<b>${c[0]}</b>${c[0]==="المطعم"&&!CM.on("restaurant")?`<span class="cm-soon">الميزة مقفولة</span>`:""}</div><div class="cmt-cl">${c[2].map(r=>{const lock=r[1]&&!CM.cost(),off=c[0]==="المطعم"&&!CM.on("restaurant");return `<button class="${RP.sel===r[2]?"on":""}" ${A("rp","open",r[2]||"")} ${off?"disabled":""} data-nm="${r[0]}"><span>${r[0]}</span>${lock?`<em>${X("lock")}تتطلب رؤية التكلفة</em>`:r[2]?`<em class="ok">${X("eye")}مفتوح كنموذج</em>`:""}</button>`}).join("")}</div></div>`).join("")}</div>`;
 const view=!CM.cost()?CM.note("مجمل الربح وقيمة المخزون تتطلب صلاحية رؤية التكلفة (FINANCE_COMMERCE_VIEW_COST). غيّر «رؤية التكلفة» في شريط المعاينة لعرض التقرير.","warn","lock"):
 `<div class="cmt-recon ${tbProfit===T.profit&&svT===svT?"ok":"bad"}"><div class="cmt-rh">${X("scale")}<b>مطابقة إلزامية مع الحسابات</b><span class="cm-st cm-s-done">${X("check-check")}<span>مطابق</span></span></div>
   <div class="cmt-rg"><div><small>مجمل الربح في التقرير</small><b class="num">${F(T.profit)}</b></div><i>=</i><div><small>المبيعات − التكلفة في ميزان المراجعة</small><b class="num">${F(tbProfit)}</b><em>(${CM.n(goods)} + ${CM.n(svc)} − ${CM.n(T.ret)} − ${CM.n(T.disc)}) − ${CM.n(T.cogs)}</em></div><i>فرق</i><div class="${tbProfit===T.profit?"okv":"badv"}"><small>الفرق</small><b class="num">${F(r2(tbProfit-T.profit))}</b></div></div>
   <div class="cmt-rg"><div><small>قيمة المخزون في التقرير</small><b class="num">${F(svT)}</b></div><i>=</i><div><small>رصيد حسابات المخزون (${sv.map(x=>x[0][4].slice(0,4)).join(" + ")})</small><b class="num">${F(svT)}</b><em>${sv.map(x=>`${x[0][1]} ${CM.n(x[1],0)}`).join(" · ")}</em></div><i>فرق</i><div class="okv"><small>الفرق</small><b class="num">0.00</b></div></div></div>
  <div class="cmt-rbar"><div class="cx-chips">${Object.keys(PERS).map(k=>`<button class="${RP.per===k?"on":""}" ${A("rp","per",k)}>${PERS[k][0]}</button>`).join("")}</div><div class="fb"><label>تجميع حسب</label>${fs("rp","dim",[["cat","القسم"],["item","المنتج"]],RP.dim)}</div>${CM.brSel()}<div class="cm-acts">${B("ghost","printer","طباعة",'data-cm-toast="جاري تجهيز التقرير للطباعة"')}${B("ghost","file-spreadsheet","Excel",'data-cm-toast="جاري تجهيز ملف Excel"')}${B("ghost","bookmark","حفظ في تقاريري",'data-cm-toast="حُفظ التقرير في تقاريري"')}</div></div>
  <div class="statgrid four">${[["المبيعات الصافية","receipt","n",T.net],["تكلفة البضاعة المباعة","package","a",T.cogs],["مجمل الربح","trending-up","g",T.profit],["هامش الربح","percent","g",null]].map(s=>`<div class="scard"><span class="sci ${s[2]}">${X(s[1])}</span><span class="sct">${s[0]}</span><b class="num">${s[3]==null?mg(T.profit,T.net):CM.n(s[3],0)}</b></div>`).join("")}</div>
  <div class="grid-2"><div class="card sec"><div class="sec-h">${X("bar-chart-3")}<b>مجمل الربح حسب ${RP.dim==="cat"?"القسم":"المنتج"}</b></div><div class="cmt-bars">${g.map(x=>`<div class="cmt-bar"><span>${x.k}</span><i><u style="width:${Math.max(2,Math.round(x.profit/mx*100))}%"></u></i><b class="num">${F(x.profit,0)}</b></div>`).join("")}</div></div>
  <div class="card sec"><div class="sec-h">${X("lightbulb")}<b>ما يلفت النظر</b></div><div class="cmt-ins">${[(()=>{const b=g[0];return [`<b>${b.k}</b> أعلى مساهم: ${Math.round(b.profit/T.profit*100)}% من مجمل الربح`,"trending-up","g"]})(),(()=>{const w=[...g].filter(x=>x.net).sort((a,b)=>a.profit/a.net-b.profit/b.net)[0];return [`أقل هامش في <b>${w.k}</b>: ${mg(w.profit,w.net)}`,"alert-triangle","a"]})(),[`الخصومات ${CM.n(T.disc,0)} ر.س والمرتجعات ${CM.n(T.ret,0)} ر.س من إجمالي ${CM.n(T.gross,0)}`,"percent","n"]].map(x=>`<div class="p21-i ${x[2]}">${X(x[1])}<span>${x[0]}</span></div>`).join("")}</div></div></div>
  <div class="card flush fx-t"><div class="p21-th"><b>التفصيل حسب ${RP.dim==="cat"?"القسم":"المنتج"}</b><span class="ms">${RP.dim==="cat"?"اضغط القسم لعرض منتجاته":"كل سطر منتج"}</span></div><div class="tw"><table><thead><tr><th>${RP.dim==="cat"?"القسم":"المنتج"}</th><th>المبيعات</th><th>المرتجعات</th><th>الخصومات</th><th>صافي المبيعات</th><th>التكلفة</th><th>مجمل الربح</th><th>الهامش</th></tr></thead><tbody>
  ${g.map(x=>`<tr class="${RP.dim==="cat"?"cmt-grow":""}" ${RP.dim==="cat"?A("rp","tog",x.k):""}><td><b>${RP.dim==="cat"?(RP.open[x.k]?X("chevron-down"):X("chevron-left")):""} ${x.k}</b>${RP.dim==="cat"?`<small class="cm-sub">${x.r.length} منتجات</small>`:`<small class="cm-sub">${catTop(x.r[0].it)}</small>`}</td><td class="num">${CM.n(x.gross)}</td><td class="num">${CM.n(x.ret)}</td><td class="num">${CM.n(x.disc)}</td><td class="num">${CM.n(x.net)}</td><td class="num">${CM.n(x.cogs)}</td><td class="num"><b>${CM.n(x.profit)}</b></td><td class="num">${mg(x.profit,x.net)}</td></tr>
  ${RP.dim==="cat"&&RP.open[x.k]?x.r.map(y=>`<tr class="cmt-sub"><td>${itCell(y.it,CM.q(y.q,y.it.units[0][0]).replace(/<[^>]+>/g,""))}</td><td class="num">${CM.n(y.gross)}</td><td class="num">${CM.n(y.ret)}</td><td class="num">${CM.n(y.disc)}</td><td class="num">${CM.n(y.net)}</td><td class="num">${CM.n(y.cogs)}</td><td class="num">${CM.n(y.profit)}</td><td class="num">${mg(y.profit,y.net)}</td></tr>`).join(""):""}`).join("")}</tbody>
  <tfoot><tr class="tot"><td>الإجمالي</td><td class="num">${CM.n(T.gross)}</td><td class="num">${CM.n(T.ret)}</td><td class="num">${CM.n(T.disc)}</td><td class="num">${CM.n(T.net)}</td><td class="num">${CM.n(T.cogs)}</td><td class="num">${CM.n(T.profit)}</td><td class="num">${mg(T.profit,T.net)}</td></tr></tfoot></table></div>
  <div class="tfoot"><span>المصدر: فواتير اللوحة + مبيعات الكاشير (عرض موحد CommerceSalesReport) · ${PERS[RP.per][0]} · استُخرج ${TODAY} 10:24 · مشرف النظام</span></div></div>`;
 return `${HEAD("تقارير البيع والمخزون","اختر تقريراً من الكتالوج. مبيعات الكاشير وفواتير اللوحة تظهر معاً في تقارير المبيعات.",B("ghost","folder-open","مركز التقارير",'data-go="reports"'))}
 ${CM.pvbar()}
 ${now("افتح «مجمل الربح» لمراجعة الأرباح مع شريط المطابقة، وغيّر الفترة أو التجميع.")}
 ${catalog}
 <div class="cmt-rt">${X("bar-chart-3")}<b>مجمل الربح حسب ${RP.dim==="cat"?"القسم":"المنتج"}</b><span>تقرير نموذجي يوضح نمط تقارير المبيعات والمخزون</span></div>
 ${CM.view(view,CM.empty("bar-chart-3","لا بيانات للتقرير بعد","ستظهر التقارير بعد أول فاتورة بيع مؤكدة.",B("primary","plus","فاتورة بيع",'data-go="cmSaleAdd"')))}`};

/* ============ إعدادات التجارة والحسابات ============ */
const COA=[["1140","المخزون",0,"asset"],["1141","مخزون — المستودع الرئيسي",1,"asset"],["1142","مخزون — صالة البيع",1,"asset"],["1143","مخزون — جدة",1,"asset"],["1120","العملاء",0,"asset"],["1124","عملاء نقاط البيع",1,"asset"],["1131","وسيط مدى",1,"asset"],["2120","الموردون والدائنون",0,"liab"],["2121","بضاعة مستلمة لم تُفوتر",1,"liab"],["4100","الإيرادات",0,"rev"],["4101","مبيعات بضاعة",1,"rev"],["4111","مردودات المبيعات",1,"rev"],["4112","خصم مسموح به",1,"rev"],["5100","المصروفات المباشرة",0,"exp"],["5101","تكلفة البضاعة المباعة",1,"exp"],["5205","فروقات المخزون",1,"exp"],["5206","تالف وهالك",1,"exp"],["5310","عجز وزيادة الصناديق",1,"exp"],["5320","عمولات بنكية",1,"exp"]];
const coa=c=>COA.find(x=>x[0]===c);
/* [المفتاح, الاسم, الطبيعة, النوع المتوقع, الأب المقترح عند الإنشاء, حساب موجود بنفس الدور, كود جديد] */
const ROLES=[["inventory","حساب المخزون الافتراضي","مدين · ميزانية","asset","الأصول المتداولة (نوع المخازن 20)","1141","1144"],["grni","بضاعة مستلمة لم تُفوتر","دائن","liab","الخصوم المتداولة","2121","2122"],["cogs","تكلفة البضاعة المباعة","مدين · دخل","exp","المصروفات المباشرة","5101","5102"],["variance","فروقات المخزون","مدين","exp","المصروفات","5205","5207"],["waste","التالف والهالك","مدين","exp","المصروفات","5206","5208"],["returns","مردودات المبيعات","مدين (مقابل إيراد)","rev","الإيرادات","4111","4113"],["discount","خصم المبيعات","مدين (مقابل إيراد)","rev","الإيرادات","","4114"],["overshort","عجز وزيادة الصناديق","مدين","exp","المصروفات الأخرى","5310","5311"],["posar","عملاء نقاط البيع (آجل)","مدين","asset","العملاء","1124","1125"],["fees","العمولات البنكية","مدين","exp","المصروفات","5320","5321"]];
const SE={tab:"map",map:{inventory:"1141",grni:"2121",cogs:"5101",variance:"",waste:"",returns:"4111",discount:"",overshort:"",posar:"1124",fees:""},smap:{s1:"1141",s2:"1142",s3:"1143"},dry:0,ran:0,saved:0,log:[]};
const IMK="cerp.cmt.imp";
const imp=()=>{try{return JSON.parse(localStorage.getItem(IMK))||{}}catch(e){return {}}};
const roleErr=(code,exp)=>{if(!code)return "";const a=coa(code);if(!a)return "الحساب غير موجود في الشجرة";if(!a[2])return "حساب رئيسي: اختر حساباً فرعياً (ورقة) لتُرحَّل عليه القيود";if(a[3]!==exp)return `النوع «${{asset:"أصول",liab:"خصوم",rev:"إيرادات",exp:"مصروفات"}[a[3]]}» لا يناسب هذا الدور`;return ""};
const acSel=(ns,f,cur,exp,i)=>`<select data-cmt-c="${ns}.${f}" ${i!=null?`data-i="${i}"`:""} class="${roleErr(cur,exp)?"cmt-bad":""}"><option value="">— غير مربوط —</option>${COA.map(a=>`<option value="${a[0]}"${a[0]===cur?" selected":""}>${a[0]} ${a[1]}${a[2]?"":" (رئيسي)"}</option>`).join("")}</select>`;
const dryRows=()=>{const r=[];ROLES.forEach(x=>{if(SE.map[x[0]])r.push([x[1],"ok",`${SE.map[x[0]]} ${(coa(SE.map[x[0]])||[])[1]||""}`,"مربوط بالفعل"]);else if(x[5])r.push([x[1],"link",`${x[5]} ${coa(x[5])[1]}`,"يوجد حساب بنفس الدور — سيُربط"]);else r.push([x[1],"new",`${x[6]} ${x[1]} — تجاري`,x[4]])});
 r.push(["صندوق «كاشير جدة»","new","1115 صندوق كاشير جدة","الصناديق — عبر BoxService (ينشئ الحساب والصندوق معاً)"]);r.push(["صندوق «كاشير 1» و«كاشير 2»","ok","1113 · 1114","مربوطان بالفعل"]);r.push(["وسيط «مدى» و«Apple Pay»","ok","1131 وسيط مدى","مربوط بالفعل"]);r.push(["وسيط «قسائم»","link","2135 قسائم مدفوعة مقدماً","يوجد حساب بنفس الدور — سيُربط"]);return r};
const mappedN=()=>ROLES.filter(x=>SE.map[x[0]]&&!roleErr(SE.map[x[0]],x[3])).length;
S.cmSettings=()=>{
 const cfg=CM.cfg(),tabs=[["map","link-2","ربط الحسابات"],["auto","wand-sparkles","إنشاء الحسابات تلقائياً"],["setup","list-checks","تجهيز المتجر"],["legacy","database-backup","استيراد المخزون القديم"],["feat","toggle-right","الميزات المفعّلة"]];
 const errs=ROLES.filter(x=>roleErr(SE.map[x[0]],x[3])).length+D.stores.filter(s=>roleErr(SE.smap[s[0]],"asset")).length;
 const map=`<div class="card sec"><div class="sec-h">${X("link-2")}<b>حسابات المخزون والمبيعات</b><span class="ms">${mappedN()} من ${ROLES.length} مربوطة · الحسابات الفرعية (الورقية) فقط</span></div>
  <div class="tw"><table><thead><tr><th>الدور</th><th>الطبيعة</th><th>الحساب</th><th>الحالة</th></tr></thead><tbody>${ROLES.map(x=>{const v=SE.map[x[0]],e=roleErr(v,x[3]);return `<tr><td><b>${x[1]}</b><small class="cm-sub cm-mono">${{inventory:"inventory_account",grni:"grni_account",cogs:"cogs_account",variance:"inventory_variance_account",waste:"waste_account",returns:"sales_returns_account",discount:"sales_discount_account",overshort:"cash_over_short_account",posar:"pos_receivables_account",fees:"bank_fees_account"}[x[0]]}</small></td><td>${x[2]}</td><td>${acSel("se","map",v,x[3],x[0])}${e?`<small class="cm-sub cmt-neg">${e}</small>`:""}</td><td>${e?CM.st("rejected","غير صالح"):v?CM.st("active","مربوط"):CM.st("unpaid","غير مربوط")}</td></tr>`}).join("")}</tbody></table></div></div>
  <div class="card sec"><div class="sec-h">${X("warehouse")}<b>حساب المخزون لكل مخزن</b><span class="ms">يُستخدم بدل الافتراضي عند الاختلاف</span></div><div class="tw"><table><thead><tr><th>المخزن</th><th>الفرع</th><th>مركز التكلفة</th><th>حساب المخزون</th></tr></thead><tbody>${D.stores.map(s=>`<tr><td><b>${s[1]}</b></td><td>${D.branches.find(b=>b[0]===s[2])[1]}</td><td>${s[3]}</td><td>${acSel("se","smap",SE.smap[s[0]],"asset",s[0])}${roleErr(SE.smap[s[0]],"asset")?`<small class="cm-sub cmt-neg">${roleErr(SE.smap[s[0]],"asset")}</small>`:""}</td></tr>`).join("")}</tbody></table></div></div>
  <div class="card sec"><div class="sec-h">${X("receipt")}<b>الضريبة</b></div>${KV([["ضريبة المخرجات",D.accounts.vatOut+" — يُحلّ تلقائياً (TaxAccountResolver)"],["ضريبة المدخلات",D.accounts.vatIn+" — يُحلّ تلقائياً"]])}${CM.note("حسابات الضريبة تأتي من الإعدادات المحاسبية الحالية ولا تُعدَّل هنا.","","info")}</div>
  <div class="cvactions">${B("primary","save","حفظ الربط",A("se","save")+(errs?" disabled":""))}${errs?`<span class="cm-flag bad">${X("octagon-alert")}${errs} حسابات غير صالحة تمنع الحفظ</span>`:SE.saved?`<span class="cm-flag ok">${X("check")}تم الحفظ وسُجّل في سجل النشاط</span>`:""}</div>`;
 const dry=dryRows(),nNew=dry.filter(x=>x[1]==="new").length,nLink=dry.filter(x=>x[1]==="link").length;
 const auto=`${cfg.acct!=="commercial"?CM.note("الأمر يرفض التنفيذ إن لم يكن نوع المحاسبة «تجاري». غيّره من السوبر أدمن أولاً.","bad"):""}
  <div class="card sec"><div class="sec-h">${X("wand-sparkles")}<b>إنشاء الحسابات تلقائياً</b><span class="ms mono">commerce:provision-accounts --dry-run</span></div><div class="cmt-p">يعرض ما سيُنشأ، ويربط بحساب موجود إن وُجد حساب بنفس الدور، ولا يُنشئ شيئاً قبل تأكيدك. كل حساب جديد يُنشأ تحت أب مناسب حسب نوعه لا حسب كوده.</div>
  <div class="cm-acts" style="padding:0 18px 16px">${B(SE.dry?"ghost":"primary","scan-search",SE.dry?"أعد المعاينة":"معاينة (dry-run)",A("se","dry"))}</div></div>
  ${SE.dry?`<div class="card flush"><div class="cmt-dh"><b>نتيجة المعاينة</b><span class="cm-st cm-s-done">${X("plus")}<span>${nNew} سيُنشأ</span></span><span class="cm-st cm-s-live">${X("link")}<span>${nLink} سيُربط بموجود</span></span><span class="cm-st cm-s-draft"><span>${dry.filter(x=>x[1]==="ok").length} مربوط بالفعل</span></span></div><div class="tw"><table><thead><tr><th>الدور</th><th>الإجراء</th><th>الحساب</th><th>الأب / الملاحظة</th></tr></thead><tbody>${dry.map(x=>`<tr><td><b>${x[0]}</b></td><td>${x[1]==="new"?TAG("g","إنشاء جديد"):x[1]==="link"?TAG("n","ربط بموجود"):TAG("b","بلا تغيير")}</td><td class="num">${x[2]}</td><td>${x[3]}</td></tr>`).join("")}</tbody></table></div>
  <div class="cmt-dx">${SE.ran?`<span class="cm-flag ok">${X("check-circle-2")}نُفّذ الإنشاء: ${nNew||SE.ran} حساب جديد وربط الباقي. سُجّل في سجل النشاط.</span>`:`${CM.note("التنفيذ يُنشئ الحسابات في شجرة الحسابات ويحفظ الربط. يمكنك تعديل أي ربط لاحقاً.","warn")}${B("primary","check","تنفيذ الإنشاء والربط",A("se","run")+(nNew+nLink?"":" disabled"))}`}</div></div>`:""}`;
 const done=(()=>{const ck=checklist();return ck})();
 const ck=done,pct=Math.round(ck.filter(x=>x[0]==="ok").length/ck.length*100);
 const setup=`<div class="card sec"><div class="sec-h">${X("list-checks")}<b>تجهيز المتجر</b><span class="ms">${ck.filter(x=>x[0]==="ok").length} من ${ck.length} خطوات مكتملة</span></div><div class="cmt-prog"><i style="width:${pct}%"></i></div><div class="cmt-ck">${ck.map((x,i)=>`<div class="cmt-ci ${x[0]}"><span class="cmt-cd">${x[0]==="ok"?X("check"):x[0]==="warn"?X("alert-triangle"):`<b class="num">${i+1}</b>`}</span><div><b>${x[1]}</b><small>${x[2]}</small></div>${x[3]?B(x[0]==="ok"?"ghost sm":"primary sm","",x[0]==="ok"?"فتح":"أكمل",`data-go="${x[3]}"`):""}</div>`).join("")}</div></div>`;
 const im=imp(),legacy=`<div class="card sec"><div class="sec-h">${X("database-backup")}<b>استيراد المخزون القديم</b><span class="ms mono">commerce:import-legacy-stock</span>${im.done?CM.st("done","تم الاستيراد"):""}</div>
  <div class="cmt-p">أمر لمرة واحدة ينقل المنتجات وأرصدة أول المدة من شاشتي «المنتجات» و«أرصدة أول المدة» القديمتين في المالية إلى المنتجات ومخزن ومستند رصيد افتتاحي <b>مسودة</b> تراجعه قبل الاعتماد. لا يعدّل بيانات المالية القديمة ولا يعمل مرة ثانية بعد التنفيذ.</div>
  ${im.done?`<div class="cm-ro">${X("lock")}<div><b>نُفّذ الاستيراد في ${im.at}</b><small>أُنشئ ${im.items} منتجاً ومستند الرصيد الافتتاحي OP-2026-0001 (مسودة). لا يمكن إعادة التنفيذ.</small></div><div class="cm-ro-a">${B("primary sm","file-text","فتح مستند الرصيد",'data-go="cmDocs"')}</div></div>`
  :SE.imp?`<div class="cmt-imp">${KV([["منتجات مالية قديمة","48"],["ستُنشأ كمنتجات تجارية",'<b class="num">45</b>'],["وحدات مستخرجة","5 (حبة، كرتون، كجم، كيس، طن)"],["مستند رصيد افتتاحي","1 مسودة — 45 سطراً · قيمة تقديرية "+CM.c(412380.5).replace(/<[^>]+>/g,"")],["تعارض باركود","2 — يُترك الثاني بلا باركود ويُعلَّم للمراجعة"],["منتجات بلا حساب مخزون","3 — تُربط بحساب المخزن الافتراضي"]])}${CM.note("المعاينة لم تغيّر شيئاً. التنفيذ لا يُعاد.","warn")}<div class="cm-acts">${B("primary","play","تنفيذ الاستيراد (مرة واحدة)",A("se","imprun"))}${B("ghost","","إلغاء",A("se","impno"))}</div></div>`
  :`<div class="cm-acts" style="padding:0 18px 16px">${B("primary","scan-search","معاينة قبل التنفيذ",A("se","impprev"))}</div>`}</div>`;
 const feat=`<div class="card sec"><div class="sec-h">${X("toggle-right")}<b>الميزات المفعّلة</b><span class="ms">للقراءة فقط — تُدار من السوبر أدمن</span><a class="btn btn-ghost sm" href="superadmin/commerce.html">${X("shield-check")}نوع المحاسبة والنشاط</a></div>
  ${KV([["نوع المحاسبة",cfg.acct==="commercial"?"تجاري":"خدمات"],["القالب",(CM.TPL[cfg.tpl]||{}).n||"مخصص"],["طريقة التكلفة",cfg.costing==="avg"?"المتوسط المرجح المستمر":"FIFO"],["الحدود",`${cfg.limits.branches} فروع · ${cfg.limits.stores} مخازن · ${cfg.limits.devices} أجهزة`]])}
  <div class="cmt-ft">${CM.FEAT.map(f=>`<div class="${CM.on(f[0])?"on":""}"><span>${X(CM.on(f[0])?"check-circle-2":"minus-circle")}</span><b>${f[1]}</b><small>${f[2]}</small>${f[4]==="soon"?`<em class="cm-soon">قريباً</em>`:""}</div>`).join("")}</div>${CM.note("لا يمكن تغيير الميزات من هنا. إيقاف ميزة بعد استخدامها يُخفي شاشاتها وتبقى بياناتها.","","info")}</div>`;
 return `${HEAD("إعدادات المخزون والحسابات","اربط حسابات المخزون والمبيعات، وأنشئ الناقص تلقائياً، وأكمل تجهيز المتجر.",B("ghost","shield-check","الميزات في السوبر أدمن",'onclick="location.href=\'superadmin/commerce.html\'"'))}
 ${CM.pvbar({cost:0,br:0,st:0})}
 ${now(errs?`${errs} حسابات غير صالحة تحتاج تصحيحاً.`:mappedN()<ROLES.length?`${ROLES.length-mappedN()} أدوار غير مربوطة: افتح «إنشاء الحسابات تلقائياً» وراجع المعاينة.`:"الربط مكتمل. أكمل بقية خطوات تجهيز المتجر.")}
 <div class="vtabs">${tabs.map(t=>`<button class="${SE.tab===t[0]?"on":""}" ${A("se","tab",t[0])}>${X(t[1])}<span>${t[2]}</span></button>`).join("")}</div>
 ${{map,auto,setup,legacy,feat}[SE.tab]}`};
const checklist=()=>{const reg=D.registers.length,n=mappedN();return [
 ["ok","بيانات الشركة والرقم الضريبي","310245881600003 — الريادة للتجارة","finSettings"],
 ["ok","الفروع",D.branches.length+" فروع","setBranches"],
 ["ok","المخازن",D.stores.length+" مخازن بمراكز تكلفة وحسابات","cmStores"],
 ["warn","أقسام المنتجات بحساباتها","5 من 7 أقسام فيها حسابات إيراد وتكلفة — قسمان بلا حساب","cmCats"],
 ["ok","الوحدات","حبة، كرتون، كجم، كيس، طن","cmUnits"],
 ["ok","المنتجات",D.items.length+" منتجاً","cmItems"],
 [n===ROLES.length?"ok":"todo","ربط حسابات المخزون والمبيعات",`${n} من ${ROLES.length} مربوطة`,""],
 ["ok","الأسعار",PL.filter(p=>plSt(p)==="active").length+" قوائم سارية","cmPrices"],
 ["todo","مستند الرصيد الافتتاحي","لم يُنشأ بعد — يُدخل أرصدة أول المدة للمخازن","cmDocs"],
 ["ok","طرق الدفع",D.pay.length+" طرق مربوطة بحسابات وسيطة","cmPayMethods"],
 ["todo","نقاط البيع والصناديق","1 من "+reg+" نقاط بصندوق مربوط — «كاشير جدة» بلا صندوق","cmRegisters"],
 ["todo","ربط الأجهزة بالهيئة (ZATCA)","لا جهاز مربوط بعد","cmZatca"]]};

/* ============ الأحداث ============ */
const go=p=>{location.href=p+".html"};
let ptr=0;
const H={
 sl:{open:v=>{ss("cmt.sel",v);go("cmSaleShow")},orig:v=>{if(v.indexOf("PU")===0){go("cmPurchases")}else{ss("cmt.sel",v);go("cmSaleShow")}},
  st:v=>{LS.st=v},pay:v=>{LS.pay=v},za:v=>{LS.za=v},dv:v=>{LS.dv=v},dnprev:()=>{ss("cmt.dn","1")},
  retfrom:v=>{ss("cmt.retFrom",v);go("cmReturnAdd")},retopen:()=>go("cmReturns")},
 sa:{go:v=>{const e=document.getElementById("cmt-sa-"+v);if(e)e.scrollIntoView({behavior:"smooth",block:"start"});return 1},
  add:v=>{const it=D.item(v),ex=SA.ln.find(l=>l.id===+v&&l.ui===0);if(ex)ex.q+=1;else SA.ln.push({id:+v,ui:0,q:1,p:null,d:0});SA.find="";SA.rev=0},
  pick:()=>{SA.find=" "},del:i=>{SA.ln.splice(+i,1);SA.rev=0},unman:i=>{SA.ln[+i].p=null},
  fit:i=>{const l=SA.ln[+i],c=calcLn(l,SA.pl),av=Math.max(0,(c.it.qty[SA.s]||0)),other=SA.ln.filter((x,k)=>k!==+i&&x.id===l.id).reduce((a,x)=>a+calcLn(x,SA.pl).base,0);l.q=Math.max(0,Math.floor((av-other)/c.u[1]*1e4)/1e4)},
  review:()=>{SA.rev=1;setTimeout(()=>{const e=document.getElementById("cmt-sa-4");if(e)e.scrollIntoView({behavior:"smooth",block:"start"})},30)},unreview:()=>{SA.rev=0},
  confirm:()=>{if(saBlocks().length)return;const sel="SI-2026-0419";SA.done=sel;ss("cmt.sel","SI-2026-0412")},draft:()=>{CM.toast("حُفظت المسودة SI-2026-0419")},
  reset:()=>{SA=SA0()}},
 sh:{tab:v=>{SH.tab=v},all:()=>{const r=shR();SH.now.q={};r.ln.map(toLn).forEach(l=>{if(!calcLn(l,r.pl).svc)SH.now.q[l.id]=Math.max(0,l.q-shDvb(r,l.id))})},
  dn:()=>{const r=shR(),q={};Object.keys(SH.now.q).forEach(k=>{if(SH.now.q[k]>0)q[k]=SH.now.q[k]});const no="DN-2026-0"+(240+SH.dns.length);if(r.no==="SI-2026-0412")SH.dns.push({no,date:TODAY,by:"مشرف النظام",ln:q});else{r.dvb=r.dvb||{};Object.keys(q).forEach(k=>{r.dvb[k]=(r.dvb[k]||0)+q[k]});r.dv="partial"}SH.now=null;CM.toast("صدر سند الصرف "+no+" وخُصم المخزون"+(CM.cost()?" وسُجّل قيد التكلفة":""))}},
 pu:{st:v=>{PS.st=v},grn:()=>{const e=document.getElementById("cmt-grni");if(e)e.scrollIntoView({behavior:"smooth"});return 1},link:v=>{ss("cmt.grn",v);go("cmPurchaseAdd")},ret:v=>{ss("cmt.retFrom",v);go("cmReturnAdd")}},
 pa:{add:v=>{PA.ln.push({id:+v,ui:0,q:1,p:r2(D.item(v).cost*1.02),r:PA.mode==="full"?1:0,b:0});PA.find="";PA.rev=0},del:i=>{PA.ln.splice(+i,1)},mode:v=>{PA.mode=v;if(v==="full")PA.ln.forEach(l=>l.r=l.q)},review:()=>{PA.rev=1},unreview:()=>{PA.rev=0},unlink:()=>{const v=PA.v,s=PA.s;PA=PA0();PA.v=v;PA.s=s},
  confirm:()=>{PA.done="PU-2026-0189"},draft:()=>CM.toast("حُفظت المسودة"),reset:()=>{PA=PA0()}},
 rt:{t:v=>{RLS.t=v},new:v=>{RA.type=v;RA.orig=v==="sale"?"SI-2026-0412":"PU-2026-0188";RA.q={};go("cmReturnAdd")}},
 ra:{type:v=>{RA.type=v;RA.orig=v==="sale"?"SI-2026-0412":"PU-2026-0188";RA.q={}},confirm:()=>{if(!RA.reason){CM.toast("اختر سبب المرتجع","alert-triangle");return}RA.done="RT-2026-0032"},reset:()=>{RA.q={};RA.done=null;RA.reason=""}},
 pr:{add:()=>{PLS.add=PLS.add?0:1},save:()=>{if(!PLS.f.name.trim()){CM.toast("أدخل اسم القائمة","alert-triangle");return}PL.push(["pl"+(PL.length+1),PLS.f.name,PLS.f.bind,PLS.f.from,PLS.f.to,nz(PLS.f.pr)||60,1,1]);savePL();PLS.add=0;PLS.f.name="";CM.toast("أُنشئت القائمة — عدّل أسعارها من المحرر")},
  edit:v=>{PE.pl=v;go("cmPriceEdit")},copy:v=>{const p=pl(v),n="pl"+(PL.length+1);PL.push([n,"نسخة من "+p[1],p[2],TODAY,"",p[5]+1,1,p[7]]);Object.keys(PO).filter(k=>k.indexOf(v+"|")===0).forEach(k=>{PO[n+k.slice(v.length)]=Object.assign({},PO[k])});savePL();savePO();CM.toast("نُسخت القائمة «"+p[1]+"»")},
  stop:v=>{const p=pl(v);p[6]=p[6]?0:1;savePL();CM.toast(p[6]?"فُعّلت القائمة «"+p[1]+"»":"أُوقفت القائمة «"+p[1]+"» — الفواتير السابقة لم تتغير")}},
 pe:{sel:v=>{PE.sel[v]=!PE.sel[v]},all:()=>{const rs=peRows(),all=rs.every(x=>PE.sel[x.k]);rs.forEach(x=>PE.sel[x.k]=!all)},
  apply:()=>{const p=nz(PE.pct);if(!p){CM.toast("أدخل نسبة التغيير","alert-triangle");return}const rs=peRows(),sel=rs.filter(x=>PE.sel[x.k]),t=sel.length?sel:rs,st=nz(PE.rnd);t.forEach(x=>{const c=priceRec(PE.pl,x.it.id,x.ui).p;let v=c*(1+p/100);v=st?Math.round(v/st)*st:r2(v);PE.ed[x.k]=Object.assign(PE.ed[x.k]||{},{p:r2(v)})})},
  discard:()=>{PE.ed={}},save:()=>{Object.keys(PE.ed).forEach(k=>{const id=PE.pl+"|"+k,o=PO[id]||{};if(PE.ed[k].p!=null)o.p=PE.ed[k].p;if(PE.ed[k].min!=null)o.min=PE.ed[k].min;PO[id]=o});savePO();const n=Object.keys(PE.ed).length;PE.ed={};CM.toast(n+" تعديلات حُفظت في «"+pl(PE.pl)[1]+"» وتُطبَّق على الفواتير الجديدة")}},
 rp:{per:v=>{RP.per=v},tog:v=>{RP.open[v]=!RP.open[v]},open:v=>{if(v==="gp"||v==="sv"){RP.sel=v;const e=document.querySelector(".cmt-rt");if(e)e.scrollIntoView({behavior:"smooth"});return 1}CM.toast("يفتح بنفس نمط التقرير النموذجي (فترة وفلاتر وتجميع وتصدير)","info");return 1}},
 se:{tab:v=>{SE.tab=v},dry:()=>{SE.dry=1;SE.ran=0},run:()=>{const d=dryRows(),nN=d.filter(x=>x[1]==="new").length;ROLES.forEach(x=>{if(!SE.map[x[0]])SE.map[x[0]]=x[5]||x[6]});SE.ran=nN;CM.toast("نُفّذ الإنشاء والربط وسُجّل في سجل النشاط")},save:()=>{SE.saved=1;CM.toast("حُفظ ربط الحسابات")},
  impprev:()=>{SE.imp=1},impno:()=>{SE.imp=0},imprun:()=>{try{localStorage.setItem(IMK,JSON.stringify({done:1,at:TODAY+" 10:30",items:45}))}catch(e){}SE.imp=0;CM.toast("تم الاستيراد وأُنشئ مستند الرصيد الافتتاحي كمسودة")}}};
const C={
 sl:(f,v)=>{LS[f]=v},pu:(f,v)=>{PS[f]=v},
 sa:(f,v,i)=>{i=+i;SA.rev=0;const l=SA.ln[i];
  if(f==="c"){SA.c=v;SA.pl=cl(v)[5]}else if(f==="s"){SA.s=v}else if(f==="pl"){SA.pl=v}else if(f==="ui"){l.ui=+v;l.p=null}else if(f==="q"){l.q=nz(v)}else if(f==="p"){const n=nz(v),lp=priceRec(SA.pl,l.id,l.ui).p;l.p=Math.abs(n-lp)<0.0004?null:n}else if(f==="d"){l.d=nz(v)}else SA[f]=v},
 sh:(f,v,i)=>{if(f==="now")SH.now.q[i]=nz(v)},
 pa:(f,v,i)=>{i=+i;PA.rev=0;const l=PA.ln[i];if(f==="ui"){l.ui=+v}else if(f==="q"){l.q=nz(v);if(PA.mode==="full")l.r=l.q}else if(f==="r"){l.r=nz(v)}else if(f==="p"){l.p=nz(v)}else if(f==="grn"){if(v){ss("cmt.grn",v);CM.re()}else H.pa.unlink()}else PA[f]=v},
 ra:(f,v,i)=>{if(f==="q")RA.q[i]=v===""?"":nz(v);else if(f==="orig"){RA.orig=v;RA.q={}}else RA[f]=v},
 prf:(f,v)=>{PLS.f[f]=v},
 pe:(f,v,i)=>{if(f==="pl"){PE.pl=v;PE.ed={}}else if(f==="p"){PE.ed[i]=Object.assign(PE.ed[i]||{},{p:nz(v)})}else if(f==="min"){PE.ed[i]=Object.assign(PE.ed[i]||{},{min:nz(v)})}else PE[f]=v},
 rp:(f,v)=>{RP[f]=v},
 se:(f,v,i)=>{if(f==="map"){SE.map[i]=v}else if(f==="smap"){SE.smap[i]=v}SE.saved=0}};
/* إعادة الرسم مع الحفاظ على الحقل النشط */
const rr=()=>{const a=document.activeElement,k=a&&a.dataset&&a.dataset.cmtF,p=a&&a.selectionStart;CM.re();if(k){const n=document.querySelector(`[data-cmt-f="${k}"]`);if(n){n.focus();try{if(p!=null&&n.setSelectionRange)n.setSelectionRange(p,p)}catch(e){}}}};
if(!window.__cmTrade){window.__cmTrade=1;
 document.addEventListener("mousedown",()=>{ptr=Date.now()},true);
 document.addEventListener("click",e=>{const el=e.target.closest("[data-cmt]");if(!el||el.disabled)return;e.preventDefault();e.stopPropagation();
  const[a,arg]=el.dataset.cmt.split(/:(.*)/s),[ns,act]=a.split(".");const h=H[ns]&&H[ns][act];if(!h)return;const keep=h(arg,el);if(keep===1&&ns!=="rp")return;CM.re();if(ns==="sa"&&act==="confirm")scrollTo(0,0)},true);
 document.addEventListener("change",e=>{const el=e.target.closest("[data-cmt-c]");if(!el)return;const[ns,f]=el.dataset.cmtC.split(".");if(!C[ns])return;C[ns](f,el.value,el.dataset.i);setTimeout(rr,Date.now()-ptr<400?220:0)});
 document.addEventListener("input",e=>{const el=e.target;if(el.dataset&&el.dataset.cmtFind!==undefined){const v=el.value;if(el.dataset.cmtFind==="pa")PA.find=v;else SA.find=v;el.dataset.cmtF="find";rr()}});
 document.addEventListener("keydown",e=>{const el=e.target;if(e.key==="Enter"&&el.dataset&&el.dataset.cmtFind!==undefined){e.preventDefault();const v=(el.dataset.cmtFind==="pa"?PA.find:SA.find).trim();if(!v)return;const its=D.items.filter(i=>i.act&&(i.kind==="stock"||i.kind==="service"));let m=null,ui=0;its.forEach(i=>i.units.forEach((u,k)=>{if(u[3]===v){m=i;ui=k}}));if(!m)m=its.find(i=>(i.n+i.code).indexOf(v)>-1);if(m){if(el.dataset.cmtFind==="pa")H.pa.add(m.id);else{H.sa.add(m.id);if(ui){const l=SA.ln.find(x=>x.id===m.id&&x.ui===0);if(l&&l.q===1){l.ui=ui}}}CM.re()}else CM.toast("لا منتج بهذا الباركود","alert-triangle")}});
}
Object.assign(T,{cmSales:{ar:"فواتير البيع",en:"Sales invoices"},cmSaleAdd:{ar:"فاتورة بيع",en:"New sales invoice"},cmSaleShow:{ar:"فاتورة بيع",en:"Sales invoice"},cmPurchases:{ar:"شراء بضاعة",en:"Purchase invoices"},cmPurchaseAdd:{ar:"فاتورة شراء",en:"New purchase invoice"},cmReturns:{ar:"المرتجعات",en:"Returns"},cmReturnAdd:{ar:"مرتجع جديد",en:"New return"},cmPrices:{ar:"الأسعار",en:"Price lists"},cmPriceEdit:{ar:"محرر الأسعار",en:"Price editor"},cmReports:{ar:"تقارير البيع والمخزون",en:"Commerce reports"},cmSettings:{ar:"إعدادات المخزون والحسابات",en:"Commerce settings"}});
Object.keys(S).forEach(k=>{screens[k]=S[k]});
});
