/* Orders & bookings — grounded in admin/ingaz/RecruitmentRequest/* (+ RentContract, SponsorshipTransfer) */
const orTypes=[["all","كل الطلبات","All requests","layers"],["rec","طلبات استقدام","Recruitment","file-text"],["rent","طلبات تأجير","Rental","repeat"],["trn","طلبات نقل كفالة","Sponsorship transfer","arrow-left-right"]];
const orSt=[["new","جديد","New","a"],["progress","تحت الإجراء","In progress","n"],["approved","معتمد","Approved","g"],["converted","مُحوّل لعقد","Converted","g"],["rejected","مرفوض","Rejected","r"]];
/* 0 no 1 type 2 job 3 client 4 phone 5 nat 6 date 7 time 8 mins-since 9 status 10 read 11 readBy 12 contract 13 salary 14 age 15 marital 16 religion 17 lang 18 exp 19 visa 20 worker 21 notes 22 branch 23 agent */
const orRows=[
 ["OR-4412","rec","عاملة منزلية","منيرة الشهري","+966 55 214 8890","الفلبين","2026-08-17","09:14",38,"new",0,"—","","1,300",29,"عازبة","مسيحية","الإنجليزية",4,"—","have_experince","تفضل عاملة تتحدث الإنجليزية.","الفرع الرئيسي","سارة القحطاني"],
 ["OR-4411","rent","عاملة منزلية (تأجير 6 أشهر)","شركة نجد للمقاولات","+966 11 448 2200","إندونيسيا","2026-08-17","08:02",110,"progress",1,"مشرف النظام","","1,450",33,"متزوجة","مسلمة","العربية",6,"—","have_experince","سكن مؤمّن من الشركة.","الفرع الرئيسي","خالد العمري"],
 ["OR-4410","trn","نقل كفالة عاملة منزلية","عبدالله الدوسري","+966 53 660 4471","سريلانكا","2026-08-16","19:40",840,"progress",1,"سارة القحطاني","","1,200",35,"مطلقة","بوذية","السنهالية",8,"V-88214","have_experince","العاملة على كفالة صاحب عمل آخر.","فرع جدة","سارة القحطاني"],
 ["OR-4409","rec","طباخة","نورة الحربي","+966 50 771 2280","الهند","2026-08-16","11:25",1320,"approved",1,"مدير الفرع","","1,600",31,"متزوجة","هندوسية","الإنجليزية",7,"—","have_experince","خبرة في المطبخ الهندي.","فرع الدمام","محمد الشهراني"],
 ["OR-4408","rec","عاملة منزلية","هيا العنزي","+966 59 118 2043","كينيا","2026-08-15","14:03",2810,"converted",1,"سارة القحطاني","CN-2026-0112","1,100",26,"عازبة","مسيحية","الإنجليزية",2,"—","new_come","","الفرع الرئيسي","سارة القحطاني"],
 ["OR-4407","rent","سائق خاص (تأجير سنة)","شركة الوفاء الطبية","+966 13 887 9011","بنغلاديش","2026-08-15","09:47",2990,"converted",1,"خالد العمري","CN-2026-0109","1,800",40,"متزوج","مسلم","العربية",12,"V-77420","have_experince","سائق لنقل الكوادر الطبية.","فرع الدمام","خالد العمري"],
 ["OR-4406","trn","نقل كفالة سائق","تركي القحطاني","+966 56 220 7741","باكستان","2026-08-14","16:12",4300,"rejected",1,"مدير الفرع","","1,500",38,"متزوج","مسلم","الأردية",10,"V-66188","have_experince","نقص في مستندات صاحب العمل السابق.","فرع جدة","محمد الشهراني"],
 ["OR-4405","rec","عاملة منزلية","سارة المطيري","+966 54 990 1123","إثيوبيا","2026-08-14","08:30",4760,"progress",0,"—","","1,000",24,"عازبة","مسيحية","الأمهرية",1,"—","new_come","","الفرع الرئيسي","سارة القحطاني"]];
const orStats=[["all","إجمالي الطلبات","Total requests","layers","n",121],["new","جديدة لم تُقرأ","New unread","bell-dot","a",6],["progress","تحت الإجراء","In progress","loader","n",7],["approved","معتمدة بانتظار التحويل","Approved, awaiting conversion","check-circle-2","g",4],["converted","مُحوّلة إلى عقود","Converted to contracts","file-signature","g",108],["rejected","مرفوضة","Rejected","x-circle","r",2]];
let ORTYPE="all",ORQ="",ORSTATUS="",ORID="OR-4412",ORTAB="details";
const orTypeTag=k=>{const m={rec:["استقدام","Recruitment","n"],rent:["تأجير","Rental",""],trn:["نقل كفالة","Transfer","a"]}[k];return `<span class="tag ${m[2]}">${t(m[0],m[1])}</span>`};
const orStTag=k=>{const s=orSt.find(x=>x[0]===k)||orSt[0];return `<span class="st tag ${s[3]}"><i></i>${t(s[1],s[2])}</span>`};
const orDur=m=>{const d=Math.floor(m/1440),h=Math.floor(m%1440/60),mm=m%60;return d?t(`${d} ي ${h} س`,`${d}d ${h}h`):h?t(`${h} س ${mm} د`,`${h}h ${mm}m`):t(`${mm} د`,`${mm}m`)};
const orSla=m=>m<120?"g":m<1440?"a":"r";
const orSlaLbl=m=>m<120?["داخل المهلة","Within SLA"]:m<1440?["يقترب من التجاوز","Nearing breach"]:["تجاوز المهلة","SLA breached"];
const orFiltered=()=>orRows.filter(r=>(ORTYPE==="all"||r[1]===ORTYPE)&&(!ORSTATUS||r[9]===ORSTATUS)&&(!ORQ||(r[0]+r[2]+r[3]+r[4]).toLowerCase().includes(ORQ.toLowerCase())));
const orActs=r=>`<div class="rowacts"><button class="icon-btn pri" data-orshow="${r[0]}" title="${t("عرض الطلب","Open request")}">${I("eye")}</button>${r[12]?`<button class="icon-btn pri" data-go="contracts" title="${t("العقد المرتبط","Linked contract")}">${I("file-signature")}</button>`:`<button class="icon-btn pri" data-orconv="${r[0]}" title="${t("تحويل إلى عقد","Convert to contract")}">${I("file-plus")}</button>`}<button class="icon-btn" title="${t("مشاركة واتساب","Share")}">${I("share-2")}</button></div>`;
const orderScreens={
 orders:()=>{
  const rows=orFiltered();
  return `
 <div class="page-head"><div><h1>${t("الطلبات والحجوزات","Orders & bookings")}</h1><p>${t("الطلب هو أول خطوة — يُقرأ ثم يُعتمد ثم يُحوَّل إلى عقد. العقود شاشة مستقلة ومرتبطة برقم الطلب.","A request is the first step — read, approved, then converted into a contract. Contracts live on their own screen, linked by request number.")}</p></div>
 <div class="actions"><button class="btn btn-ghost">${I("download")}${t("تصدير","Export")}</button><button class="btn btn-ghost" data-go="contracts">${I("file-signature")}${t("العقود المرتبطة","Linked contracts")}</button><button class="btn btn-primary">${I("plus")}${t("طلب جديد","New request")}</button></div></div>
 <div class="flowbar">${[["inbox","الطلب","Request","تُسجَّل رغبة العميل","Client requirement logged",1],["eye","القراءة والتخصيص","Read & assign","يُقرأ ويُخصَّص لموظف","Read and assigned to staff",1],["check-circle-2","الاعتماد","Approval","مطابقة سيرة مناسبة","Matching a suitable CV",1],["file-signature","العقد","Contract","يُصدر عقد مرتبط بالطلب","A contract is issued from the request",0]].map((x,i)=>`<div class="flowstep ${x[5]?"done":""}"><span class="fi">${I(x[0])}</span><div><b>${t(x[1],x[2])}</b><small>${t(x[3],x[4])}</small></div>${i<3?`<span class="farr">${I("chevron-left")}</span>`:""}</div>`).join("")}</div>
 <div class="seg tabs">${orTypes.map(x=>`<button class="${x[0]===ORTYPE?"on":""}" data-ortype="${x[0]}">${t(x[1],x[2])}</button>`).join("")}</div>
 <div class="statgrid six">${orStats.map(x=>`<button class="scard ${x[0]===ORSTATUS||(x[0]==="all"&&!ORSTATUS)?"on":""}" data-orstat="${x[0]}"><span class="sci ${x[4]}">${I(x[3])}</span><span class="sct">${t(x[1],x[2])}</span><b class="num">${x[5]}</b></button>`).join("")}</div>
 <div class="fbar">
  <div class="fb grow"><label>${t("بحث","Search")}</label><div class="inp">${I("search")}<input data-orq value="${ORQ}" placeholder="${t("رقم الطلب، المهنة، العميل، الجوال","Request no., job, client, phone")}"></div></div>
  <div class="fb"><label>${t("الحالة","Status")}</label><select data-orstatus><option value="">${t("كل الحالات","All statuses")}</option>${orSt.map(s=>`<option value="${s[0]}"${s[0]===ORSTATUS?" selected":""}>${t(s[1],s[2])}</option>`).join("")}</select></div>
  <div class="fb"><label>${t("الفرع","Branch")}</label><select><option>${t("كل الفروع","All branches")}</option><option>${t("الفرع الرئيسي","Main branch")}</option><option>${t("فرع جدة","Jeddah")}</option><option>${t("فرع الدمام","Dammam")}</option></select></div>
  <div class="fb"><label>${t("من تاريخ","From date")}</label><input type="date"></div>
  <div class="fb"><label>${t("إلى تاريخ","To date")}</label><input type="date"></div>
  <span class="empty">${t(`${rows.length} نتيجة`,`${rows.length} results`)}</span></div>
 <div class="card flush"><div class="card-h"><div><h3>${t("سجل الطلبات","Requests register")}</h3><p>${t("المؤقّت يحسب الزمن من لحظة تسجيل الطلب حتى الآن — أخضر أقل من ساعتين، برتقالي أقل من يوم، أحمر بعده.","The timer counts from request creation — green under 2h, amber under a day, red beyond.")}</p></div></div>
 <div class="tw"><table class="grp"><thead><tr><th class="chk"><input type="checkbox"></th><th>${t("الطلب","Request")}</th><th>${t("النوع والمهنة","Type & job")}</th><th>${t("العميل","Client")}</th><th>${t("الجنسية المطلوبة","Requested nationality")}</th><th>${t("تاريخ ووقت الطلب","Request date & time")}</th><th>${t("المؤقّت","Timer")}</th><th>${t("الحالة","Status")}</th><th>${t("القراءة","Read")}</th><th>${t("العقد المرتبط","Linked contract")}</th><th class="act">${t("إجراءات","Actions")}</th></tr></thead><tbody>
 ${rows.length?rows.map(r=>`<tr>
  <td class="chk"><input type="checkbox"></td>
  <td><div class="gcell"><b class="num">${r[0]}</b><small>${r[22]}</small></div></td>
  <td><div class="gcell">${orTypeTag(r[1])}<b>${r[2]}</b></div></td>
  <td><div class="gcell"><b>${r[3]}</b><small class="num">${r[4]}</small></div></td>
  <td>${r[5]}</td>
  <td><div class="gcell"><b class="num">${r[6]}</b><small class="num">${r[7]}</small></div></td>
  <td><div class="timer ${orSla(r[8])}" data-since="${r[8]}"><span class="tdot"></span><b class="num">${orDur(r[8])}</b><small>${t(orSlaLbl(r[8])[0],orSlaLbl(r[8])[1])}</small></div></td>
  <td>${orStTag(r[9])}</td>
  <td>${r[10]?`<div class="gcell"><b class="ok">${t("مقروء","Read")}</b><small>${r[11]}</small></div>`:`<span class="tag a">${t("غير مقروء","Unread")}</span>`}</td>
  <td>${r[12]?`<button class="lnk" data-go="contracts">${I("file-signature")}<span class="num">${r[12]}</span></button>`:`<span class="empty">—</span>`}</td>
  <td class="act">${orActs(r)}</td></tr>`).join(""):`<tr><td colspan="11"><div class="tempty">${I("inbox")}<b>${t("لا طلبات مطابقة","No matching requests")}</b><small>${t("جرّب إزالة الفلاتر","Try clearing the filters")}</small></div></td></tr>`}
 </tbody></table></div>
 <div class="tfoot"><span>${t(`عرض ${rows.length} من 121 طلباً`,`Showing ${rows.length} of 121 requests`)}</span><div class="pager"><button>${I("chevron-right")}</button><button class="on">1</button><button>2</button><button>3</button><button>${I("chevron-left")}</button></div></div>
 </div>`},
 orderShow:()=>{
  const r=orRows.find(x=>x[0]===ORID)||orRows[0];
  const kv=fs=>`<div class="kvgrid">${fs.map(f=>`<div class="kv"><span>${t(f[0],f[1])}</span><b>${f[2]}</b></div>`).join("")}</div>`;
  const steps=[["inbox","تسجيل الطلب","Request logged",`${r[6]} · ${r[7]}`,1],["eye","القراءة والتخصيص","Read & assigned",r[10]?`${r[11]}`:t("لم يُقرأ بعد","Not read yet"),r[10]],["check-circle-2","الاعتماد","Approved",["approved","converted"].includes(r[9])?t("تمت الموافقة","Approved"):t("بانتظار الاعتماد","Awaiting approval"),["approved","converted"].includes(r[9])?1:0],["file-signature","تحويل إلى عقد","Converted to contract",r[12]||t("لم يُحوّل","Not converted"),r[12]?1:0]];
  const body={
   details:()=>`
   ${sect("briefcase","تفاصيل الطلب","Request details",kv([["نوع الطلب","Request type",orTypeTag(r[1])],["المهنة المطلوبة","Job title",r[2]],["الجنسية","Nationality",r[5]],["سنوات الخبرة","Experience years",`<span class="num">${r[18]}</span>`],["الراتب المطلوب","Salary",`<span class="num">${r[13]}</span> ${t("ر.س","SAR")}`],["رقم التأشيرة","Visa number",r[19]==="—"?`<span class="empty">—</span>`:`<span class="num">${r[19]}</span>`],["العمر","Age",`<span class="num">${r[14]}</span>`],["الحالة الاجتماعية","Marital status",r[15]],["الديانة","Religion",r[16]],["اللغة","Language",r[17]],["حالة العاملة","Worker status",r[20]==="new_come"?t("قادمة جديدة","New arrival"):t("لديها خبرة","Has experience")],["الفرع","Branch",r[22]]]))}
   ${sect("sticky-note","ملاحظات العميل","Client notes",`<div class="notebox">${r[21]||`<span class="empty">${t("لا ملاحظات","No notes")}</span>`}</div>`)}`,
   client:()=>sect("user","بيانات العميل","Client info",kv([["اسم العميل","Client name",r[3]],["الجوال","Phone",`<span class="num">${r[4]}</span>`],["البريد","Email",'<span class="ltr">client@example.com</span>'],["منذ","Since",orDur(r[8])],["الموظف المسؤول","Assigned agent",r[23]],["الفرع","Branch",r[22]]])),
   match:()=>sect("files","السير المطابقة","Matching CVs",`<div class="tw"><table><thead><tr><th>${t("العاملة","Worker")}</th><th>${t("الجنسية","Nationality")}</th><th>${t("المهنة","Occupation")}</th><th>${t("الراتب","Salary")}</th><th>${t("نسبة المطابقة","Match")}</th><th class="act"></th></tr></thead><tbody>
    ${[["A-118742","Marites Dela Cruz","الفلبين","عاملة منزلية","1,300",92],["A-118710","Sri Wahyuni","إندونيسيا","عاملة منزلية","1,250",78],["A-118688","Kalpana Perera","سريلانكا","عاملة منزلية","1,200",64]].map(x=>`<tr><td><div class="gcell"><b>${x[1]}</b><small class="num">${x[0]}</small></div></td><td>${x[2]}</td><td>${x[3]}</td><td class="num">${x[4]}</td><td><div class="mrate"><span class="track"><i style="width:${x[5]}%"></i></span><b class="num">${x[5]}%</b></div></td><td class="act"><div class="rowacts"><button class="icon-btn pri" data-go="cvs">${I("eye")}</button><button class="icon-btn pri" title="${t("حجز للطلب","Reserve for request")}">${I("bookmark")}</button></div></td></tr>`).join("")}
   </tbody></table></div>`),
   contract:()=>r[12]
    ?sect("file-signature","العقد المرتبط","Linked contract",kv([["رقم العقد","Contract no.",`<span class="num">${r[12]}</span>`],["تاريخ العقد","Contract date",'<span class="num">2026-08-16</span>'],["الحالة","Status",'<span class="st tag n"><i></i>'+t("تحت الضمان","Under warranty")+'</span>'],["قيمة العقد","Value",'<span class="num">18,500</span> '+t("ر.س","SAR")],["العاملة","Worker","Marites Dela Cruz"],["المرحلة","Stage",t("مرحلة الوصول","Arrival stage")]])+`<div class="cvactions"><button class="btn btn-primary" data-go="contracts">${I("external-link")}${t("فتح العقد","Open contract")}</button><button class="btn btn-ghost">${I("printer")}${t("طباعة العقد","Print contract")}</button></div>`)
    :sect("file-plus","تحويل الطلب إلى عقد","Convert to contract",`<div class="convbox"><div><b>${t("لا يوجد عقد مرتبط بهذا الطلب","No contract linked to this request")}</b><small>${t("التحويل ينشئ عقداً جديداً يحمل بيانات الطلب ورقمه، ويبقى الطلب في سجله كمصدر للعقد.","Converting creates a contract carrying the request data and number; the request stays in its register as the contract source.")}</small></div><button class="btn btn-primary" data-orconv="${r[0]}">${I("file-plus")}${t("تحويل إلى عقد","Convert to contract")}</button></div>`),
   log:()=>sect("history","سجل الطلب","Request log",`<div class="tl">${[[`${r[6]} · ${r[7]}`,t("تسجيل الطلب من الموقع","Request logged from the website"),r[3]],[`${r[6]} · ${r[7]}`,t("إشعار لفريق المبيعات","Sales team notified"),t("النظام","System")],r[10]?[`${r[6]} · 10:22`,t("قراءة الطلب وتخصيصه","Request read and assigned"),r[11]]:null,r[12]?["2026-08-16 · 12:40",t("تحويل الطلب إلى عقد","Converted into a contract"),r[23]]:null].filter(Boolean).map(x=>`<div class="tli"><span class="dot"></span><div><b>${x[1]}</b><small>${x[2]}</small><small class="num">${x[0]}</small></div></div>`).join("")}</div>`)
  };
  return `
 <div class="page-head compact"><div><a class="back" href="#" data-go="orders" style="margin:0 0 8px">${I("arrow-right")}<span>${t("الرجوع للطلبات","Back to requests")}</span></a><h1>${t("الطلب","Request")} <span class="num">${r[0]}</span></h1><p>${orTypeTag(r[1])} · ${r[2]} · ${r[3]}</p></div>
 <div class="actions">${r[12]?`<button class="btn btn-ghost" data-go="contracts">${I("file-signature")}${t("العقد المرتبط","Linked contract")}</button>`:`<button class="btn btn-primary" data-orconv="${r[0]}">${I("file-plus")}${t("تحويل إلى عقد","Convert to contract")}</button>`}<button class="btn btn-ghost">${I("printer")}${t("طباعة","Print")}</button></div></div>
 <div class="orsum">
  ${[["hash","رقم الطلب","Request no.",`<span class="num">${r[0]}</span>`],["calendar","تاريخ الطلب","Request date",`<span class="num">${r[6]}</span>`],["clock","وقت الطلب","Request time",`<span class="num">${r[7]}</span>`],["activity","الحالة","Status",orStTag(r[9])]].map(x=>`<div class="orsum-i"><span class="oi">${I(x[0])}</span><div><small>${t(x[1],x[2])}</small><b>${x[3]}</b></div></div>`).join("")}
  <div class="orsum-i timerbig ${orSla(r[8])}"><span class="oi">${I("timer")}</span><div><small>${t("زمن الاستجابة","Response time")}</small><b class="num" data-since="${r[8]}">${orDur(r[8])}</b><small class="sla">${t(orSlaLbl(r[8])[0],orSlaLbl(r[8])[1])}</small></div></div>
 </div>
 <div class="card sec"><div class="sec-h">${I("git-branch")}<b>${t("مسار الطلب حتى العقد","From request to contract")}</b></div>
  <div class="orflow">${steps.map((x,i)=>`<div class="ofs ${x[4]?"done":""}"><span class="fi">${I(x[0])}</span><div><b>${t(x[1],x[2])}</b><small>${x[3]}</small></div>${i<steps.length-1?`<span class="ln"></span>`:""}</div>`).join("")}</div></div>
 <div class="vtabs">${[["details","تفاصيل الطلب","Request details","briefcase"],["client","بيانات العميل","Client info","user"],["match","السير المطابقة","Matching CVs","files"],["contract","العقد المرتبط","Linked contract","file-signature"],["log","سجل الطلب","Request log","history"]].map(x=>`<button class="${x[0]===ORTAB?"on":""}" data-ortab="${x[0]}">${I(x[3])}<span>${t(x[1],x[2])}</span></button>`).join("")}</div>
 <div class="tabbody">${(body[ORTAB]||body.details)()}</div>`}
};
Object.assign(T,{orders:{ar:"الطلبات والحجوزات",en:"Orders & bookings"},orderShow:{ar:"تفاصيل الطلب",en:"Request details"}});
Object.assign(screens,orderScreens);
function orTick(){document.querySelectorAll("[data-since]").forEach(n=>{const m=+n.dataset.since+Math.floor((Date.now()-orT0)/60000);const tgt=n.querySelector("b")||n;tgt.textContent=orDur(m)})}
const orT0=Date.now();
setInterval(orTick,30000);
document.addEventListener("click",e=>{
 const tt=e.target.closest("[data-ortype]");if(tt){ORTYPE=tt.dataset.ortype;ORSTATUS="";render();return}
 const ss=e.target.closest("[data-orstat]");if(ss){const k=ss.dataset.orstat;ORSTATUS=k==="all"?"":(ORSTATUS===k?"":k);render();return}
 const sh=e.target.closest("[data-orshow]");if(sh){ORID=sh.dataset.orshow;ORTAB="details";cur="orderShow";render();window.scrollTo(0,0);return}
 const tb=e.target.closest("[data-ortab]");if(tb){ORTAB=tb.dataset.ortab;render();window.scrollTo(0,0);return}
 const cv=e.target.closest("[data-orconv]");
 if(cv){const r=orRows.find(x=>x[0]===cv.dataset.orconv);let ov=document.getElementById("orOv");if(!ov){ov=document.createElement("div");ov.id="orOv";ov.className="ov";document.body.appendChild(ov)}
  ov.innerHTML=`<div class="modal"><div class="modal-h"><div><h3>${t("تحويل الطلب إلى عقد","Convert request to contract")}</h3><p class="ms">${r[0]} · ${r[2]} · ${r[3]}</p></div><button class="icon-btn" data-orclose>${I("x")}</button></div>
  <div class="modal-b"><div class="fgrid two"><div class="fld"><label>${t("نوع العقد","Contract type")}<code>contract_type</code></label><select><option${r[1]==="rec"?" selected":""}>${t("عقد استقدام","Recruitment contract")}</option><option${r[1]==="rent"?" selected":""}>${t("عقد تأجير","Rental contract")}</option><option${r[1]==="trn"?" selected":""}>${t("نقل كفالة","Sponsorship transfer")}</option></select></div>
  <div class="fld"><label>${t("السيرة المرتبطة","Linked CV")}<code>maid_id</code></label><select><option>Marites Dela Cruz — A-118742</option><option>Sri Wahyuni — A-118710</option></select></div>
  <div class="fld"><label>${t("قيمة العقد (ر.س)","Contract value (SAR)")}</label><input type="text" value="18,500"></div>
  <div class="fld"><label>${t("تاريخ العقد","Contract date")}</label><input type="date" value="2026-08-17"></div></div>
  <p class="pnote">${I("info")}${t("سيبقى الطلب في سجله مربوطاً برقم العقد الجديد، ولن يُحذف.","The request stays in its register linked to the new contract number; it is not deleted.")}</p></div>
  <div class="modal-f"><button class="btn btn-ghost" data-orclose>${t("إلغاء","Cancel")}</button><button class="btn btn-primary" data-ordone="${r[0]}">${I("file-plus")}${t("إنشاء العقد","Create contract")}</button></div></div>`;
  ov.classList.remove("hidden");lucide.createIcons({attrs:{"stroke-width":1.8}});return}
 const oc=e.target.closest("[data-orclose]"),od=e.target.closest("[data-ordone]");
 if(oc||od){const ov=document.getElementById("orOv");if(ov)ov.classList.add("hidden");
  if(od){const r=orRows.find(x=>x[0]===od.dataset.ordone);if(r){r[12]="CN-2026-0"+(113+orRows.filter(x=>x[12]).length);r[9]="converted";r[10]=1}render();clToast("تم إنشاء العقد وربطه بالطلب","Contract created and linked to the request")}return}
});
document.addEventListener("input",e=>{
 if(e.target.matches("[data-orq]")){ORQ=e.target.value;const p=e.target.selectionStart;render();const n=document.querySelector("[data-orq]");if(n){n.focus();n.setSelectionRange(p,p)}}
});
document.addEventListener("change",e=>{
 if(e.target.matches("[data-orstatus]")){ORSTATUS=e.target.value;render()}
});
