/* Recruitment contracts — grounded in admin/ingaz/RecruitmentContract/{index,details}.blade.php */
/* real stage keys from RecruitmentContractStageLabels + index filters */
const rcStages=[
 ["pending_maid_selection","بانتظار اختيار العاملة","Pending worker selection","user-search","a",6],
 ["new","جديد","New","file-plus","n",12],
 ["msaned","مساند","Musaned","shield","n",9],
 ["tafwed","تفويض","Delegation","stamp","n",4],
 ["taffez","تفييز","Visa issuing","credit-card","n",4],
 ["tazker","تذكرة","Ticket","plane","a",5],
 ["arrival","وصول","Arrival","plane-landing","g",3],
 ["shelter","إيواء","Shelter","home","n",2],
 ["sari","ساري","Valid","check","g",24],
 ["completed","مكتمل","Completed","check-circle-2","g",19],
 ["back","مرتجع","Returned","undo-2","r",19],
 ["refunded","مسترد","Refunded","wallet","r",3],
 ["accreditation_contracts","عقود الاعتماد","Accreditation contracts","file-check","n",5]];
const rcGroups=[["run","سارية — قيد التنفيذ","Valid — in progress","play-circle",["pending_maid_selection","new","msaned","tafwed","taffez","tazker","accreditation_contracts"]],["post","سارية — بعد الوصول","Valid — after arrival","plane-landing",["arrival","shelter","sari"]],["closed","مغلقة — مكتملة ومرتجعة","Closed — completed & returned","archive",["completed","back","refunded"]]];
const rcValidKeys=["pending_maid_selection","new","msaned","tafwed","taffez","tazker","accreditation_contracts","arrival","shelter","sari"];
const rcStageLabel=k=>{const s=rcStages.find(x=>x[0]===k);return s?t(s[1],s[2]):k};
const rcStageTone=k=>{const s=rcStages.find(x=>x[0]===k);return s?s[4]:""};
const rcNext={pending_maid_selection:"new",new:"msaned",msaned:"tafwed",tafwed:"taffez",taffez:"tazker",tazker:"arrival",arrival:"shelter",shelter:"sari",sari:"completed"};
const rcCount=k=>(rcStages.find(x=>x[0]===k)||[,,,,,0])[5];
const rcTotal=()=>rcStages.reduce((n,x)=>n+x[5],0);
const rcQuick=()=>[["current","العقود السارية","Valid contracts",rcValidKeys.reduce((n,k)=>n+rcCount(k),0)],["arrival_today","وصول اليوم","Arriving today",2],["delayed_arrival","تجاوزت 45 يوماً بدون تذاكر","Over 45 days, no tickets",18]];
const rcWarranty=[["","كل حالات الضمان","All warranty states"],["active","نشط (تحت الضمان)","Active (under warranty)"],["expired","منتهي (خارج الضمان)","Expired (out of warranty)"],["not_applicable","غير قابل للتطبيق","Not applicable"]];
const rcReqTypes=["طلب مخصص","طلب عام","تحويل من طلب","مساند"];
const rcOffices=["Manila Prime Agency","Jakarta Care Agency","Colombo Bridge Agency","Kampala Star Agency"];
const rcMarketers=["سارة القحطاني","خالد المطيري","مشرف النظام"];
/* 0 no 1 client 2 clientType 3 clientPhone 4 maid 5 maidAr 6 nat 7 job 8 passport 9 stage 10 signDate 11 durationDays 12 arrivalDate 13 office 14 marketer 15 branch 16 value 17 paid 18 discount 19 reqType 20 warranty 21 delayDays 22 clientIdNo */
const rcRows=[
 ["CN-2026-0115","منيرة الشهري","person","+966 55 214 8890","Marites Dela Cruz","ماريتس دي لا كروز","الفلبين","عاملة منزلية","P7741203","tazker","2026-06-30",49,"",'Manila Prime Agency',"سارة القحطاني","الفرع الرئيسي",18500,9250,0,"طلب مخصص","not_applicable",49,"1042887331"],
 ["CN-2026-0114","شركة نجد للمقاولات","company","+966 11 448 2200","Sri Wahyuni","سري واهيوني","إندونيسيا","طباخة","P5520914","new","2026-08-02",16,"","Jakarta Care Agency","خالد المطيري","الفرع الرئيسي",17900,5000,500,"طلب عام","not_applicable",16,"3001188220"],
 ["CN-2026-0113","نورة الحربي","person","+966 53 900 1187","Kumari Perera","كوماري بيريرا","سريلانكا","عاملة منزلية","P3391077","arrival","2026-05-18",92,"2026-08-16","Colombo Bridge Agency","سارة القحطاني","فرع الدمام",19200,19200,0,"طلب مخصص","active",0,"1078441209"],
 ["CN-2026-0112","عبدالله الدوسري","person","+966 50 771 3320","Grace Nakato","غريس ناكاتو","أوغندا","عاملة منزلية","P8812440","sari","2026-03-11",160,"2026-05-02","Kampala Star Agency","خالد المطيري","فرع جدة",16800,16800,1000,"تحويل من طلب","active",0,"1099220145"],
 ["CN-2026-0111","شركة الوفاء الطبية","company","+966 13 887 9011","Nilanthi Silva","نيلانثي سيلفا","سريلانكا","ممرضة منزلية","P4408123","msaned","2026-07-21",28,"","Colombo Bridge Agency","مشرف النظام","فرع الدمام",21500,7000,0,"مساند","not_applicable",28,"3009988112"],
 ["CN-2026-0110","هيا العنزي","person","+966 59 118 2043","Ayesha Khan","عائشة خان","بنغلاديش","عاملة منزلية","P6620355","back","2026-02-04",195,"2026-04-19","Jakarta Care Agency","سارة القحطاني","الفرع الرئيسي",15900,15900,0,"طلب عام","expired",0,"1077441200"],
 ["CN-2026-0109","فيصل القحطاني","person","+966 56 330 4471","Rina Astuti","رينا أستوتي","إندونيسيا","طباخة","P9903221","pending_maid_selection","2026-08-10",8,"","","خالد المطيري","الفرع الرئيسي",18500,3000,0,"طلب مخصص","not_applicable",8,"1033441200"],
 ["CN-2026-0108","مؤسسة البيان التجارية","company","+966 12 663 5510","Jenifer Lopez","جينيفر لوبيز","الفلبين","سائق خاص","P1129884","completed","2025-11-02",289,"2026-01-14","Manila Prime Agency","مشرف النظام","فرع جدة",23400,23400,0,"طلب عام","expired",0,"3004411882"]];
let RCGROUP="",RCMENU=null,RCSTATS=false,RCQ="",RCSTAGE="",RCQUICK="",RCWAR="",RCREQ="",RCBRANCH="",RCOFFICE="",RCMORE=false,RCSEL=[],RCID="CN-2026-0115",RCTAB="over";
const rcFiltered=()=>rcRows.filter(r=>
 (!RCSTAGE||r[9]===RCSTAGE)
 &&(!RCGROUP||(rcGroups.find(g=>g[0]===RCGROUP)||[,,,,[]])[4].includes(r[9]))
 &&(!RCQUICK||(RCQUICK==="current"?rcValidKeys.includes(r[9]):RCQUICK==="arrival_today"?r[12]==="2026-08-16":r[21]>45))
 &&(!RCWAR||r[20]===RCWAR)&&(!RCREQ||r[19]===RCREQ)&&(!RCBRANCH||r[15]===RCBRANCH)&&(!RCOFFICE||r[13]===RCOFFICE)
 &&(!RCQ||(r[0]+r[1]+r[4]+r[5]+r[8]).toLowerCase().includes(RCQ.toLowerCase())));
const rcDays=n=>t(n+" يوماً",n+" days");
const rcStageChip=r=>`<span class="st tag ${rcStageTone(r[9])}"><i></i>${rcStageLabel(r[9])}</span>`;
const rcWarTag=r=>r[20]==="active"?`<span class="tag g">${t("تحت الضمان","Under warranty")}</span>`:r[20]==="expired"?`<span class="tag">${t("خارج الضمان","Out of warranty")}</span>`:`<span class="tag">${t("غير قابل للتطبيق","N/A")}</span>`;
const rcMenuItems=r=>[["file-text","نموذج التسليم","Delivery form"],["send","إرسال للمكتب الخارجي","Dispatch to office"],["ticket","إصدار تذكرة","Issue ticket"],["plane-landing","تأكيد الوصول","Confirm arrival"],["home","إدخال الإيواء","Move to shelter"],["receipt","الفواتير","Invoices"],["printer","طباعة العقد","Print contract"],["undo-2","تسجيل إرجاع","Record return"],["wallet","تسجيل استرداد","Record refund"]];
const fl=(icon,label,val,cls)=>`<div class="fline${cls?" "+cls:""}">${icon?I(icon):'<i class="fdot"></i>'}<span class="fl-l">${label}</span><span class="fl-v">${val}</span></div>`;
const flHead=(icon,main,sub)=>`<div class="fhead">${I(icon)}<div><b>${main}</b>${sub?`<small>${sub}</small>`:""}</div></div>`;
const rcActs=r=>`<div class="rowacts"><button class="icon-btn pri" data-rcshow="${r[0]}" title="${t("تفاصيل العقد","Contract details")}">${I("eye")}</button><button class="icon-btn pri" data-rcedit="${r[0]}" title="${t("تعديل العقد","Edit contract")}">${I("pencil")}</button><span class="kebab"><button class="icon-btn" data-rcmenu="${r[0]}" title="${t("المزيد","More")}">${I("more-horizontal")}</button><div class="amenu${RCMENU===r[0]?"":" hidden"}" data-rcmenu-for="${r[0]}">${rcMenuItems(r).map(x=>`<button data-rcact="${x[0]}:${r[0]}"${x[0]==="undo-2"||x[0]==="wallet"?' class="danger"':""}>${I(x[0])}<span>${t(x[1],x[2])}</span></button>`).join("")}</div></span></div>`;
const rcScreens={
 contracts:()=>{
  const rows=rcFiltered();
  const groups={};RCSEL.forEach(id=>{const r=rcRows.find(x=>x[0]===id);if(!r)return;groups[r[9]]=(groups[r[9]]||0)+1});
  return `
 <div class="page-head"><div><h1>${t("عقود الاستقدام","Recruitment contracts")}</h1><p>${t(`${rcTotal()} عقداً — موزعة على مراحل النظام من اختيار العاملة حتى الإغلاق أو الإرجاع.`,`${rcTotal()} contracts across the system stages, from worker selection to closing or return.`)}</p></div>
 <div class="actions"><button class="btn btn-ghost">${I("file-text")}${t("تصدير PDF","Export PDF")}</button><button class="btn btn-ghost">${I("file-spreadsheet")}${t("تصدير Excel","Export Excel")}</button><button class="btn btn-primary" data-go="contractAdd" data-perm="CONTRACTS_CREATE">${I("plus")}${t("إضافة عقد استقدام","Add contract")}</button></div></div>
 <div class="rcq">${rcQuick().map(q=>`<button class="qf ${RCQUICK===q[0]?"on":""}" data-rcquick="${q[0]}">${I(q[0]==="current"?"check-circle-2":q[0]==="arrival_today"?"plane-landing":"alarm-clock")}<span>${t(q[1],q[2])}</span><b class="num">${q[3]}</b></button>`).join("")}${RCQUICK?`<button class="qf clear" data-rcquick="">${I("x")}<span>${t("إلغاء الفلتر السريع","Clear quick filter")}</span></button>`:""}</div>
 <div class="pipe">
  ${rcGroups.map(g=>{const items=g[4].map(k=>rcStages.find(x=>x[0]===k)).filter(Boolean);const sum=items.reduce((n,x)=>n+x[5],0);const on=RCGROUP===g[0]||g[4].includes(RCSTAGE);
   const pct=Math.round(sum/rcTotal()*100);
   return `<button class="pg ${g[0]} ${on?"on":""}" data-rcgroup="${g[0]}"><span class="pg-i">${I(g[3])}</span><span class="pg-b"><span class="pg-t">${t(g[1],g[2])}</span><span class="pg-m"><b class="num">${sum}</b><small class="num">${pct}%</small></span><span class="pg-bar"><i style="width:${pct}%"></i></span></span></button>`}).join("")}
  <button class="pg detail ${RCSTATS?"on":""}" data-rcstats>${I(RCSTATS?"chevron-up":"bar-chart-3")}<span class="pg-t">${t(RCSTATS?"إخفاء تفصيل المراحل":"تفصيل المراحل",RCSTATS?"Hide stage detail":"Stage detail")}</span></button>
 </div>
 ${RCSTATS?`<div class="card sec statwrap"><div class="sec-h">${I("git-branch")}<b>${t("تفصيل المراحل","Stage detail")}</b><span class="ms">${t("انقر المرحلة لتصفية الجدول","Click a stage to filter")}</span></div>
  <div class="pipeline">${rcGroups.map(g=>{const items=g[4].map(k=>rcStages.find(x=>x[0]===k)).filter(Boolean);
   return `<div class="prow ${g[0]}"><span class="prow-t">${t(g[1],g[2])}</span><div class="pchips">${items.map(sx=>`<button class="pchip ${RCSTAGE===sx[0]?"on":""}" data-rcstage="${sx[0]}">${I(sx[3])}<span>${t(sx[1],sx[2])}</span><b class="num">${sx[5]}</b></button>`).join("")}</div></div>`}).join("")}</div></div>`:""}
 <div class="fbar">
  <div class="fb grow"><label>${t("بحث","Search")}</label><div class="inp">${I("search")}<input data-rcq value="${RCQ}" placeholder="${t("رقم العقد، العميل، العاملة، الجواز","Contract no., client, worker, passport")}"></div></div>
  <div class="fb"><label>${t("المرحلة","Stage")}</label><select data-rcstagesel><option value="">${t("كل المراحل","All stages")}</option>${rcStages.map(s=>`<option value="${s[0]}"${RCSTAGE===s[0]?" selected":""}>${t(s[1],s[2])}</option>`).join("")}</select></div>
  <div class="fb"><label>${t("الضمان","Warranty")}</label><select data-rcwar>${rcWarranty.map(w=>`<option value="${w[0]}"${RCWAR===w[0]?" selected":""}>${t(w[1],w[2])}</option>`).join("")}</select></div>
  <div class="fb"><label>${t("نوع الطلب","Request type")}</label><select data-rcreq><option value="">${t("كل الأنواع","All types")}</option>${rcReqTypes.map(x=>`<option${RCREQ===x?" selected":""}>${x}</option>`).join("")}</select></div>
  <button class="btn btn-ghost" data-rcmore>${I(RCMORE?"chevron-up":"sliders-horizontal")}${t(RCMORE?"إخفاء":"فلاتر أكثر",RCMORE?"Hide":"More filters")}</button>
 </div>
 ${RCMORE?`<div class="fbar second">
  <div class="fb"><label>${t("مكتب الاستقدام","Recruitment office")}</label><select data-rcoffice><option value="">${t("كل المكاتب","All offices")}</option>${rcOffices.map(x=>`<option${RCOFFICE===x?" selected":""}>${x}</option>`).join("")}</select></div>
  <div class="fb"><label>${t("الفرع","Branch")}</label><select data-rcbranch><option value="">${t("كل الفروع","All branches")}</option>${["الفرع الرئيسي","فرع جدة","فرع الدمام"].map(x=>`<option${RCBRANCH===x?" selected":""}>${x}</option>`).join("")}</select></div>
  <div class="fb"><label>${t("المسوّق","Marketer")}</label><select><option>${t("الكل","Anyone")}</option>${rcMarketers.map(x=>`<option>${x}</option>`).join("")}</select></div>
  <div class="fb"><label>${t("من تاريخ التعاقد","Signed from")}</label><input type="date"></div>
  <div class="fb"><label>${t("إلى تاريخ التعاقد","Signed to")}</label><input type="date"></div>
 </div>`:""}
 ${RCSEL.length?`<div class="bulkbar"><div class="bb-t">${I("layers")}<b>${t(`محدّد: ${RCSEL.length} عقد`,`${RCSEL.length} selected`)}</b></div>
  <div class="bb-badges">${Object.keys(groups).map(k=>`<span class="bstage">${rcStageLabel(k)}${I("arrow-left")}${rcNext[k]?rcStageLabel(rcNext[k]):t("لا مرحلة تالية","No next stage")}<b class="num">${groups[k]}</b></span>`).join("")}</div>
  <div class="bb-a"><button class="btn btn-ghost" data-rcclear>${t("إلغاء التحديد","Clear")}</button><button class="btn btn-primary" data-rcbulk>${I("arrow-left-right")}${t("نقل المرحلة","Advance stage")}</button></div></div>`:""}
 <div class="card flush">
 <div class="tw"><table class="grp"><thead><tr><th class="chk"><input type="checkbox" data-rcall></th>
  <th>${t("بيانات العميل","Client data")}</th><th>${t("العاملة","Worker")}</th><th>${t("بيانات العقد","Contract data")}</th><th>${t("المدة","Duration")}</th><th>${t("بيانات الوصول","Arrival data")}</th><th>${t("مكتب الاستقدام","Recruitment office")}</th><th>${t("المسوّق والفرع","Marketer & branch")}</th><th>${t("الحالة","Status")}</th><th>${t("تحديث المرحلة","Update stage")}</th><th class="act">${t("إجراءات","Actions")}</th></tr></thead><tbody>
 ${rows.length?rows.map(r=>`<tr class="stripe s-${rcStageTone(r[9])||"n"} ${RCSEL.includes(r[0])?"selrow":""}">
  <td class="chk"><input type="checkbox" data-rcpick="${r[0]}"${RCSEL.includes(r[0])?" checked":""}></td>
  <td><div class="fcell">${flHead(r[2]==="company"?"building-2":"user-round",r[1],r[2]==="company"?t("شركة","Company"):t("فرد","Individual"))}${fl("phone",t("الجوال","Phone"),`<span class="num ltr">${r[3]}</span>`)}${fl("id-card",r[2]==="company"?t("السجل التجاري","Registry no."):t("رقم الهوية","ID no."),`<span class="num ltr">${r[22]}</span>`)}</div></td>
  <td><div class="fcell">${flHead("user-round",t(r[5],r[4]),r[4])}${fl("flag",t("الجنسية","Nationality"),r[6])}${fl("briefcase",t("المهنة","Occupation"),r[7])}${fl("book-user",t("الجواز","Passport"),`<span class="num">${r[8]}</span>`)}</div></td>
  <td><div class="fcell">${flHead("file-signature",`<span class="num">${r[0]}</span>`)}${fl("calendar",t("تاريخ التوقيع","Signed on"),`<span class="num">${r[10]}</span>`)}${fl("banknote",t("قيمة العقد","Value"),`<span class="money num">${r[16].toLocaleString("en")}</span> <small class="cur">${t("ر.س","SAR")}</small>`,"money")}${fl("clipboard-list",t("نوع الطلب","Request type"),r[19])}</div></td>
  <td><div class="fcell">${fl("timer",t("في النظام","In system"),`<b class="num">${r[11]}</b> ${t("يوم","d")}`)}${fl("calendar-clock",t("مدة التعاقد","Term"),t("24 شهراً","24 months"))}${r[21]>45?fl("alert-triangle",t("تنبيه","Alert"),t("تجاوزت 45 يوماً بدون تذاكر","Over 45 days, no tickets"),"warn"):""}</div></td>
  <td><div class="fcell">${fl("plane-landing",t("تاريخ الوصول","Arrival date"),r[12]?`<span class="num">${r[12]}</span>`:`<span class="empty">${t("لم تصل","Not arrived")}</span>`,r[12]?"ok":"")}${fl("ticket",t("التذكرة","Ticket"),r[12]?t("مؤكدة","Confirmed"):t("بانتظار التأكيد","Awaiting confirmation"),r[12]?"ok":"wait")}${fl("shield-check",t("الضمان","Warranty"),rcWarTag(r))}</div></td>
  <td><div class="fcell">${fl("building-2",t("المكتب","Office"),r[13]||`<span class="empty">${t("لم يُحدد","Not assigned")}</span>`)}${fl("flag",t("الدولة","Country"),r[6])}${fl("send",t("الإرسالية","Dispatch"),r[13]?t("مرسلة","Sent"):t("لم تُرسل","Not sent"),r[13]?"ok":"wait")}</div></td>
  <td><div class="fcell">${fl("user-cog",t("المسوّق","Marketer"),r[14])}${fl("building",t("الفرع","Branch"),r[15])}${fl("clock",t("آخر تحديث","Last update"),`<span class="num">2026-08-2${r[0].slice(-1)}</span>`)}</div></td>
  <td>${rcStageChip(r)}</td>
  <td><button class="btn btn-ghost sm" data-rcadv="${r[0]}"${rcNext[r[9]]?"":" disabled"}>${I("arrow-left")}${rcNext[r[9]]?rcStageLabel(rcNext[r[9]]):t("نهائي","Final")}</button></td>
  <td class="act">${rcActs(r)}</td></tr>`).join(""):`<tr><td colspan="11"><div class="tempty">${I("inbox")}<b>${t("لا نتائج مطابقة","No matching results")}</b><small>${t("جرّب إزالة الفلاتر","Try clearing the filters")}</small></div></td></tr>`}
 </tbody></table></div>
 <div class="tfoot"><span>${t(`عرض ${rows.length} من ${rcTotal()} عقداً`,`Showing ${rows.length} of ${rcTotal()} contracts`)}</span><div class="pager"><button>${I("chevron-right")}</button><button class="on">1</button><button>2</button><button>3</button><button>${I("chevron-left")}</button></div></div>
 </div>`}
};
/* ---------- contract details ---------- */
const rcTabs=[["over","نظرة عامة","Overview","gauge"],["timeline","مراحل العقد","Contract stages","git-branch"],["client","بيانات العميل","Client data","user-circle"],["maid","بيانات العاملة","Worker data","id-card"],["finance","المالية والفواتير","Finance & invoices","wallet"],["files","المرفقات","Attachments","paperclip"],["log","السجل","Activity log","history"]];
const rcTimeline=r=>{
 const order=["pending_maid_selection","new","msaned","tafwed","taffez","tazker","arrival","shelter","sari","completed"];
 const idx=order.indexOf(r[9]);
 const dates=["2026-06-18","2026-06-30","2026-07-06","2026-07-14","2026-07-22","2026-08-02","2026-08-16","2026-08-17","2026-08-18","—"];
 const durs=[[12,"12 يوماً","12 days"],[6,"6 أيام","6 days"],[8,"8 أيام","8 days"],[8,"8 أيام","8 days"],[11,"11 يوماً","11 days"],[14,"14 يوماً","14 days"],[1,"يوم واحد","1 day"],[1,"يوم واحد","1 day"],[0,"—","—"],[0,"—","—"]];
 return order.slice(0,Math.max(idx+1,1)).map((k,i)=>({k,cur:i===idx,start:dates[i],end:i===idx?"—":dates[i+1]||"—",dur:t(durs[i][1],durs[i][2]),by:i%2?"سارة القحطاني":"مشرف النظام",note:i===idx?t("المرحلة الحالية قيد التنفيذ.","Current stage in progress."):"",inf:i===0}));
};
rcScreens.contractShow=()=>{
 const r=rcRows.find(x=>x[0]===RCID)||rcRows[0];
 const tl=rcTimeline(r);const cur2=tl.find(x=>x.cur)||tl[tl.length-1];
 const longest=tl.reduce((a,b)=>(parseInt(b.dur)||0)>(parseInt(a.dur)||0)?b:a,tl[0]);
 const rem=r[16]-r[17];
 const body={
  over:()=>`
   ${sect("gauge","نظرة عامة على العقد","Contract overview",`<div class="ovgrid">${[
     [r[2]==="company"?"العميل (شركة)":"العميل (فرد)",r[2]==="company"?"Client (company)":"Client (individual)",r[1],r[3]],
     ["إجمالي مدة العقد في النظام","Total duration in system",rcDays(r[11]),t("من تاريخ التوقيع "+r[10],"since "+r[10])],
     ["المرحلة الحالية","Current stage",rcStageLabel(r[9]),cur2?cur2.dur:""],
     ["تاريخ الوصول","Arrival date",r[12]||t("لم تصل","Not arrived"),r[12]?t("مؤكد","Confirmed"):t("بانتظار التذكرة","Awaiting ticket")],
     ["مدة التعاقد","Contract term",t("24 شهراً","24 months"),r[19]],
     ["الضمان","Warranty",r[20]==="active"?t("تحت الضمان","Under warranty"):r[20]==="expired"?t("خارج الضمان","Out of warranty"):t("غير قابل للتطبيق","N/A"),r[15]]]
    .map(x=>`<div class="ovc"><span class="ovl">${t(x[0],x[1])}</span><b class="ovv">${x[2]}</b><small class="ovm">${x[3]}</small></div>`).join("")}</div>`)}
   ${sect("git-branch","المرحلة الحالية","Current stage",`<div class="curstage"><div class="cs-t"><span class="tag ${rcStageTone(r[9])} big">${rcStageLabel(r[9])}</span><small>${t("بدأت في","started")} <span class="num">${cur2.start}</span> · ${cur2.dur}</small></div>${rcNext[r[9]]?`<button class="btn btn-primary" data-rcadv="${r[0]}">${I("arrow-left")}${t("نقل إلى","Advance to")} ${rcStageLabel(rcNext[r[9]])}</button>`:`<span class="empty">${t("لا توجد مرحلة تالية","No next stage")}</span>`}</div>`)}
   ${sect("wallet","الملخص المالي","Financial summary",`<div class="fin3">${[["قيمة العقد","Contract value",r[16].toLocaleString("en"),"n"],["المدفوع","Paid",r[17].toLocaleString("en"),"g"],["المتبقي","Remaining",rem.toLocaleString("en"),rem?"r":"g"]].map(x=>`<div class="fin"><small>${t(x[0],x[1])}</small><b class="num ${x[3]}">${x[2]}</b></div>`).join("")}</div>`)}`,
  timeline:()=>`
   ${sect("bar-chart-3","إحصاءات المراحل","Stage statistics",`<div class="ovgrid three">${[["أطول مرحلة","Longest stage",rcStageLabel(longest.k),longest.dur],["المرحلة الحالية","Current stage",rcStageLabel(r[9]),cur2.dur],["عدد المراحل المنجزة","Stages completed",String(tl.length-1),t("من 10 مراحل","of 10 stages")]].map(x=>`<div class="ovc"><span class="ovl">${t(x[0],x[1])}</span><b class="ovv">${x[2]}</b><small class="ovm">${x[3]}</small></div>`).join("")}</div>`)}
   ${sect("git-branch","خط زمني للمراحل","Stage timeline",`<div class="stimeline">${tl.map((s,i)=>`<div class="sitem ${s.cur?"current":""}">
    <div class="si-h"><span class="si-n num">${i+1}</span><div class="si-name"><b>${rcStageLabel(s.k)}</b>${s.cur?`<span class="tag a">${t("المرحلة الحالية","Current")}</span>`:""}</div><span class="si-dur">${s.dur}</span></div>
    <div class="si-dates"><div class="si-d"><small>${t("بداية المرحلة","Stage start")}</small><b class="num">${s.start}</b></div><div class="si-d"><small>${t("نهاية المرحلة","Stage end")}</small><b class="num">${s.end}</b></div></div>
    <div class="si-meta"><span class="si-by">${I("user-round")}${t("نُفِّذت بواسطة","Performed by")} <strong>${s.by}</strong></span>${s.inf?`<span class="si-inf">${I("info")}${t("مستنتجة من سجل النشاط","Inferred from activity log")}</span>`:""}</div>
    ${s.note?`<div class="si-note">${s.note}</div>`:""}</div>`).join("")}</div>`)}`,
  client:()=>sect("user-circle","بيانات العميل","Client data",kvs([["اسم العميل","Client name",r[1]],["نوع العميل","Client type",r[2]==="company"?t("شركة","Company"):t("فرد","Individual")],["الجوال","Phone",`<span class="num">${r[3]}</span>`],[r[2]==="company"?"الرقم الضريبي":"رقم الهوية",r[2]==="company"?"Tax number":"National ID",'<span class="num">1042887331</span>'],["نوع الطلب","Request type",r[19]],["الفرع","Branch",r[15]],["المسوّق","Marketer",r[14]],["عدد أفراد الأسرة","Family members",r[2]==="company"?"—":'<span class="num">5</span>'],["عدد الأطفال","Children",r[2]==="company"?"—":'<span class="num">3</span>']])),
  maid:()=>sect("id-card","بيانات العاملة","Worker data",kvs([["الاسم","Name",t(r[5],r[4])],["الاسم بالإنجليزية","Name (EN)",r[4]],["رقم الجواز","Passport",`<span class="num">${r[8]}</span>`],["الجنسية","Nationality",r[6]],["المهنة","Occupation",r[7]],["الديانة","Religion",t("مسيحية","Christian")],["الراتب","Salary",`<span class="num">1,300</span> ${t("ر.س","SAR")}`],["سنوات الخبرة","Experience",'<span class="num">4</span>'],["العمر","Age",'<span class="num">31</span>'],["مكتب الاستقدام","Recruitment office",r[13]||t("لم يُحدد","Not assigned")]])),
  finance:()=>`
   ${sect("wallet","قيم العقد","Contract values",`<div class="ovgrid four">${[["قيمة العقد","Contract value",r[16].toLocaleString("en"),t("ر.س","SAR")],["قيمة الخصم","Discount",r[18].toLocaleString("en"),t("ر.س","SAR")],["المدفوع للعميل","Paid",r[17].toLocaleString("en"),t("ر.س","SAR")],["عدد الأيام","Days",String(r[11]),t("في النظام","in system")]].map(x=>`<div class="ovc"><span class="ovl">${t(x[0],x[1])}</span><b class="ovv num">${x[2]}</b><small class="ovm">${x[3]}</small></div>`).join("")}</div>`)}
   ${sect("receipt","الفواتير","Invoices",`<div class="tw"><table><thead><tr><th>${t("رقم الفاتورة","Invoice no.")}</th><th>${t("إجمالي الفاتورة","Invoice total")}</th><th>${t("الضريبة","Tax")}</th><th>${t("المدفوع","Paid")}</th><th>${t("المتبقي","Remaining")}</th><th>${t("التاريخ","Date")}</th></tr></thead><tbody>
    <tr><td><b class="num">INV-99${r[0].slice(-2)}</b></td><td class="num">${r[16].toLocaleString("en")}</td><td class="num">${Math.round(r[16]*.15).toLocaleString("en")}</td><td class="num">${r[17].toLocaleString("en")}</td><td class="num${rem?" due":""}">${rem.toLocaleString("en")}</td><td class="num">${r[10]}</td></tr>
   </tbody></table></div>`)}`,
  files:()=>sect("paperclip","المرفقات","Attachments",`
   ${[["contract","مستند عقد","Contract document","contract_"+r[0]+".pdf"],["invoice","فاتورة","Invoice","invoice_"+r[0]+".pdf"],["financial","معاملة مالية","Financial transaction","payment_receipt.pdf"],["operational","إجراء تشغيلي","Operational action","visa_stamp.jpg"]].map(x=>`<div class="att"><div class="att-t">${I("file-text")}<div><b>${t(x[1],x[2])}</b><small>${x[3]}</small></div></div><div class="att-a"><span class="tag">${x[0]}</span><button class="btn btn-ghost">${I("eye")}${t("عرض","Preview")}</button><button class="btn btn-ghost">${I("download")}${t("تنزيل","Download")}</button></div></div>`).join("")}
   <div class="upl" data-perm="CONTRACTS_EDIT"><label>${t("إضافة مرفق","Add attachment")}</label><select><option>${t("اختر التصنيف","Choose category")}</option><option>${t("مستند عقد","Contract document")}</option><option>${t("فاتورة","Invoice")}</option><option>${t("معاملة مالية","Financial")}</option><option>${t("إجراء تشغيلي","Operational")}</option><option>${t("أخرى","Other")}</option></select><div class="ctl file">${I("upload")}<span>${t("اختر الملفات","Select files")}</span></div><button class="btn btn-primary">${I("upload")}${t("رفع","Upload")}</button></div>`),
  log:()=>sect("history","سجل النشاط","Activity log",`<div class="tl">${tl.slice().reverse().map(s=>`<div class="tli"><span class="dot"></span><div><b>${t("انتقل إلى مرحلة","Moved to stage")} ${rcStageLabel(s.k)}</b><small>${s.by}</small><small class="num">${s.start}</small></div></div>`).join("")}</div>`)
 };
 return `
 <div class="page-head compact"><div><a class="back" href="#" data-go="contracts" style="margin:0 0 8px">${I("arrow-right")}<span>${t("الرجوع لقائمة العقود","Back to contracts")}</span></a><h1>${r[0]}</h1><p>${r[1]} · ${t(r[5],r[4])} · ${r[6]} · ${rcStageLabel(r[9])}</p></div>
 <div class="actions"><button class="btn btn-primary" data-rcedit="${r[0]}">${I("pencil")}${t("تعديل العقد","Edit contract")}</button><button class="btn btn-ghost">${I("file-text")}${t("نموذج التسليم","Delivery form")}</button><button class="btn btn-ghost">${I("printer")}${t("طباعة","Print")}</button></div></div>
 <div class="showgrid3">
  <aside class="cvside">
   <div class="card idcard">
    <span class="bigph ${r[2]}">${I(r[2]==="company"?"building-2":"user-round")}</span>
    <b>${r[1]}</b><small class="num">${r[3]}</small>
    ${rcStageChip(r)}
    <div class="idk">${[[t("قيمة العقد","Value"),r[16].toLocaleString("en")],[t("المتبقي","Remaining"),rem.toLocaleString("en")],[t("المدة","Duration"),r[11]],[t("الفرع","Branch"),r[15]]].map(x=>`<div><small>${x[0]}</small><b>${x[1]}</b></div>`).join("")}</div>
   </div>
   <div class="card acard">
    <div class="sec-h">${I("settings-2")}<b>${t("إجراءات العقد","Contract actions")}</b></div>
    <div class="acts2">${[["arrow-left","نقل المرحلة","Advance stage",""],["plane-landing","تأكيد الوصول","Confirm arrival",""],["ticket","إصدار تذكرة","Issue ticket",""],["home","إدخال الإيواء","Move to shelter",""],["file-text","نموذج التسليم","Delivery form",""],["printer","طباعة العقد","Print",""],["undo-2","تسجيل إرجاع","Record return","danger"],["wallet","تسجيل استرداد","Record refund","danger"]].map(x=>`<button class="atile ${x[3]}"${x[0]==="arrow-left"?` data-rcadv="${r[0]}"`:""}>${I(x[0])}<span>${t(x[1],x[2])}</span></button>`).join("")}</div>
   </div>
  </aside>
  <div class="cvmain">
   <div class="vtabs">${rcTabs.map(x=>`<button class="${x[0]===RCTAB?"on":""}" data-rctab="${x[0]}">${I(x[3])}<span>${t(x[1],x[2])}</span></button>`).join("")}</div>
   <div class="tabbody">${(body[RCTAB]||body.over)()}</div>
  </div>
 </div>`;
};
Object.assign(T,{contracts:{ar:"عقود الاستقدام",en:"Recruitment contracts"},contractShow:{ar:"تفاصيل العقد",en:"Contract details"}});
Object.assign(screens,rcScreens);
function rcPlace(id){requestAnimationFrame(()=>{const btn=document.querySelector(`[data-rcmenu="${id}"]`),menu=document.querySelector(`[data-rcmenu-for="${id}"]`);if(!btn||!menu)return;const b=btn.getBoundingClientRect();document.body.appendChild(menu);menu.classList.remove("hidden");menu.style.position="fixed";menu.style.zIndex="200";menu.style.insetInlineEnd="auto";const h=menu.offsetHeight,w=menu.offsetWidth;let top=b.bottom+6;if(top+h>innerHeight-8)top=Math.max(8,b.top-h-6);let left=document.body.dir==="rtl"?b.left:b.right-w;left=Math.min(Math.max(8,left),innerWidth-w-8);menu.style.top=top+"px";menu.style.left=left+"px";lucide.createIcons({attrs:{"stroke-width":1.8}})})}
document.addEventListener("click",e=>{
 const rm=e.target.closest("[data-rcmenu]");if(rm){const id=rm.dataset.rcmenu;RCMENU=RCMENU===id?null:id;render();if(RCMENU)rcPlace(RCMENU);return}
 const ra=e.target.closest("[data-rcact]");
 if(ra){const [k,id]=ra.dataset.rcact.split(":");const r=rcRows.find(x=>x[0]===id);RCMENU=null;clKillMenus();
  if(k==="receipt"){RCID=id;RCTAB="finance";cur="contractShow";render();window.scrollTo(0,0);return}
  const nm=(rcMenuItems(r).find(x=>x[0]===k)||["","إجراء","Action"]);
  clToast(t(nm[1],nm[2])+" — "+r[0],t(nm[1],nm[2])+" — "+r[0]);return}
 if(RCMENU&&!e.target.closest(".kebab")&&!e.target.closest(".amenu")){RCMENU=null;clKillMenus();render()}
 const gp=e.target.closest("[data-rcgroup]");if(gp){const g=rcGroups.find(x=>x[0]===gp.dataset.rcgroup);RCGROUP=RCGROUP===gp.dataset.rcgroup?"":gp.dataset.rcgroup;RCSTAGE="";RCQUICK="";void g;render();return}
 const tg=e.target.closest("[data-rcstats]");if(tg){RCSTATS=!RCSTATS;render();return}
 const q=e.target.closest("[data-rcquick]");if(q){RCQUICK=q.dataset.rcquick;RCSTAGE="";render();return}
 const sg=e.target.closest("[data-rcstage]");if(sg){RCSTAGE=RCSTAGE===sg.dataset.rcstage?"":sg.dataset.rcstage;RCQUICK="";RCGROUP="";render();return}
 const mf=e.target.closest("[data-rcmore]");if(mf){RCMORE=!RCMORE;render();return}
 const sh=e.target.closest("[data-rcshow]");if(sh){RCID=sh.dataset.rcshow;RCTAB="over";cur="contractShow";render();window.scrollTo(0,0);return}
 const tb=e.target.closest("[data-rctab]");if(tb){RCTAB=tb.dataset.rctab;render();window.scrollTo(0,0);return}
 const cl=e.target.closest("[data-rcclear]");if(cl){RCSEL=[];render();return}
 const av=e.target.closest("[data-rcadv]");if(av){const r=rcRows.find(x=>x[0]===av.dataset.rcadv);const n=rcNext[r[9]];if(!n)return;r[9]=n;if(n==="arrival")r[12]="2026-08-18";render();clToast("تم نقل العقد إلى مرحلة "+rcStageLabel(n),"Contract advanced to "+rcStageLabel(n));return}
 const bk=e.target.closest("[data-rcbulk]");if(bk){let n=0;RCSEL.forEach(id=>{const r=rcRows.find(x=>x[0]===id);const nx=r&&rcNext[r[9]];if(nx){r[9]=nx;n++}});RCSEL=[];render();clToast(`تم نقل ${n>2&&n<11?n+" عقود":n+" عقداً"} للمرحلة التالية`,`${n} contracts advanced`);return}
});
document.addEventListener("change",e=>{
 if(e.target.matches("[data-rcpick]")){const id=e.target.dataset.rcpick;RCSEL=e.target.checked?RCSEL.concat(id):RCSEL.filter(x=>x!==id);render()}
 if(e.target.matches("[data-rcall]")){RCSEL=e.target.checked?rcFiltered().map(r=>r[0]):[];render()}
 if(e.target.matches("[data-rcstagesel]")){RCSTAGE=e.target.value;RCQUICK="";render()}
 if(e.target.matches("[data-rcwar]")){RCWAR=e.target.value;render()}
 if(e.target.matches("[data-rcreq]")){RCREQ=e.target.value;render()}
 if(e.target.matches("[data-rcoffice]")){RCOFFICE=e.target.value;render()}
 if(e.target.matches("[data-rcbranch]")){RCBRANCH=e.target.value;render()}
});
document.addEventListener("input",e=>{
 if(e.target.matches("[data-rcq]")){RCQ=e.target.value;const p=e.target.selectionStart;render();const n=document.querySelector("[data-rcq]");if(n){n.focus();n.setSelectionRange(p,p)}}
});

/* ---------- add contract + invoices ---------- */
let RCADDTAB="order",RCADDCLIENT="person";
rcScreens.contractAdd=()=>{
 const f=(ar,en,nm,type,val,req)=>{let ctl;
  if(type==="select")ctl=`<select><option value="">${t("اختر","Choose")}</option>${(Array.isArray(val)?val:[val]).map(o=>`<option>${o}</option>`).join("")}</select>`;
  else if(type==="date")ctl='<input type="date">';
  else ctl=`<input type="${type||"text"}" placeholder="${val||""}"${rcLtr(nm)?' dir="ltr"':""}>`;
  return `<div class="fld"><label>${t(ar,en)}${req?' <i class="rq">*</i>':""}<code>${nm}</code></label>${ctl}</div>`};
 const grid=fs=>`<div class="fgrid">${fs.join("")}</div>`;
 const comp=RCADDCLIENT==="company";
 return `
 <div class="page-head"><div><a class="back" href="#" data-go="contracts" style="margin:0 0 8px">${I("arrow-right")}<span>${t("عقود الاستقدام","Recruitment contracts")}</span></a><h1>${t("إضافة عقد استقدام","Add recruitment contract")}</h1><p>${t("ابدأ من طلب قائم أو أنشئ حساب عميل جديد — كما في النظام.","Start from an existing order or create a new client account, as in the system.")}</p></div>
 <div class="actions"><button class="btn btn-ghost" data-go="contracts">${t("إلغاء","Cancel")}</button><button class="btn btn-primary">${I("check")}${t("تأكيد وحفظ العقد","Confirm & save")}</button></div></div>
 <div class="seg tabs">${[["order","من طلب قائم","From an order"],["new","حساب عميل جديد","New client account"]].map(x=>`<button class="${RCADDTAB===x[0]?"on":""}" data-rcaddtab="${x[0]}">${t(x[1],x[2])}</button>`).join("")}</div>
 <div class="cvform">
  <aside class="cvopts">
   <div class="card"><div class="sec-h">${I("list-checks")}<b>${t("أقسام العقد","Contract sections")}</b></div>
    <div class="steps">${[["c1",RCADDTAB==="order"?"اختيار الطلب":"معلومات العميل",RCADDTAB==="order"?"Select order":"Client info"],["c2","بيانات العاملة","Worker data"],["c3","بيانات العقد","Contract data"],["c4","التواريخ والتأشيرة","Dates & visa"]].map((x,i)=>`<button class="step" data-rcjump="${x[0]}"><span class="stepn num">${i+1}</span><span>${t(x[1],x[2])}</span></button>`).join("")}</div></div>
   ${RCADDTAB==="new"?`<div class="card"><div class="sec-h">${I("users")}<b>${t("نوع العميل","Client type")}</b></div>
    <div class="modes">${[["person","user","فرد","Person"],["company","building-2","شركة","Company"]].map(x=>`<button class="mode ${RCADDCLIENT===x[0]?"on":""}" data-rcaddclient="${x[0]}">${I(x[1])}<span>${t(x[2],x[3])}</span></button>`).join("")}</div></div>`:""}
   <div class="card"><div class="sec-h">${I("git-branch")}<b>${t("المرحلة المبدئية","Initial stage")}</b></div>
    <div class="actrow sm col"><label>${t("المرحلة","Stage")}<code>status</code></label><select class="statsel">${rcStages.slice(0,3).map(x=>`<option>${t(x[1],x[2])}</option>`).join("")}</select></div>
    <div class="actrow sm col"><label>${t("الفرع","Branch")}<code>branch_id</code></label><select class="statsel">${["الفرع الرئيسي","فرع جدة","فرع الدمام"].map(x=>`<option>${x}</option>`).join("")}</select></div></div>
  </aside>
  <div class="cvfields">
   <div class="card sec" id="c1"><div class="fs-h"><span class="fs-n num">1</span><b>${RCADDTAB==="order"?t("اختيار الطلب","Select order"):t("معلومات العميل","Client info")}</b></div>
    ${RCADDTAB==="order"?grid([f("الطلب","Order","order_id","select",["OR-4412 — منيرة الشهري","OR-4380 — شركة نجد للمقاولات","OR-4331 — نورة الحربي"],1),f("نوع الطلب","Request type","request_type","select",rcReqTypes),f("المسوّق","Marketer","marketer_id","select",rcMarketers)])
     :grid([f("نوع العميل","Client type","client_type","select",[t("فرد","Person"),t("شركة","Company")],1),f("اسم العميل","Client name","client_name","text",comp?"اسم المنشأة":"الاسم الثلاثي",1),f("جوال العميل","Client phone","client_phone","text","+966 5X XXX XXXX",1),f("الجنسية","Nationality","nationality_id","select",["سعودي","مصري","سوري","يمني"],1),f("البريد الإلكتروني","Email","email","email","name@example.com"),f(comp?"الرقم الضريبي":"رقم الهوية",comp?"Tax number":"National ID",comp?"tax_number":"national_num","text",comp?"3XXXXXXXXXXXXX3":"1XXXXXXXXX",1),f("رقم الرخصة","Licence number","license_number","text","4030XXXXXX"),f("تاريخ الميلاد","Birth date","birthdate","date","",1),f("العنوان","Address","address","text","الحي والشارع"),f("المدينة","City","city","select",["الرياض","جدة","الدمام"]),f("عدد أفراد الأسرة","Family number","family_number","number","0"),f("عدد الأطفال (تحت 12)","Children","children","number","0"),f("المهنة","Job","job","text","المهنة الحالية")])}</div>
   <div class="card sec" id="c2"><div class="fs-h"><span class="fs-n num">2</span><b>${t("بيانات العاملة","Worker data")}</b></div>
    ${grid([f("العاملة","Worker","maid_id","select",["Marites Dela Cruz — P7741203","Sri Wahyuni — P5520914","Kumari Perera — P3391077"],1),f("رقم الجواز","Passport","maid_passport","text","PXXXXXXX"),f("الجنسية","Nationality","nationality","select",["الفلبين","إندونيسيا","سريلانكا","أوغندا"]),f("الديانة","Religion","religion","select",["مسلمة","مسيحية","أخرى"]),f("المهنة","Occupation","maid_job","select",["عاملة منزلية","طباخة","ممرضة منزلية","سائق خاص"]),f("الراتب","Salary","salary","text","1,300"),f("سنوات الخبرة","Experience years","experience_years","number","0"),f("العمر","Age","age","number","0"),f("مكتب الاستقدام","Recruitment office","office_id","select",rcOffices)])}</div>
   <div class="card sec" id="c3"><div class="fs-h"><span class="fs-n num">3</span><b>${t("بيانات العقد","Contract data")}</b></div>
    ${grid([f("قيمة العقد","Contract value","contract_value","text","18,500",1),f("قيمة الخصم","Discount","discount","text","0"),f("المدفوع مقدماً","Paid upfront","paid","text","0"),f("مدة العقد (شهر)","Term (months)","duration","select",["24","12","6"]),f("طريقة السداد","Payment method","payment_method","select",["كاش","تحويل بنكي","تقسيط"]),f("الضمان","Warranty","warranty","select",[t("غير قابل للتطبيق","N/A"),t("نشط (تحت الضمان)","Active")])])}</div>
   <div class="card sec" id="c4"><div class="fs-h"><span class="fs-n num">4</span><b>${t("التواريخ والتأشيرة","Dates & visa")}</b></div>
    ${grid([f("تاريخ العقد","Contract date","contract_date","date","",1),f("تاريخ التأشيرة","Visa date","visa_date_is","date"),f("تاريخ التأشيرة الهجري","Visa Hijri date","visa_hijry_date","text","1447/02/15"),f("تاريخ الوصول المتوقع","Expected arrival","expected_arrival","date")])}
    <div class="fld full"><label>${t("ملاحظات العقد","Contract notes")}<code>notes</code></label><textarea rows="3" placeholder="${t("ملاحظات داخلية","Internal notes")}"></textarea></div></div>
   <div class="formbar"><button class="btn btn-ghost" data-go="contracts">${t("إلغاء","Cancel")}</button><button class="btn btn-primary">${I("check")}${t("تأكيد وحفظ العقد","Confirm & save")}</button></div>
  </div>
 </div>`;
};
const rcInvRows=[
 ["INV-9921","CN-2026-0115","منيرة الشهري",18500,2775,0,9250,"جزئي","Partial","a","2026-06-30"],
 ["INV-9920","CN-2026-0114","شركة نجد للمقاولات",17900,2685,500,5000,"جزئي","Partial","a","2026-08-02"],
 ["INV-9919","CN-2026-0113","نورة الحربي",19200,2880,0,19200,"مسدّد","Settled","g","2026-05-18"],
 ["INV-9918","CN-2026-0112","عبدالله الدوسري",16800,2520,1000,16800,"مسدّد","Settled","g","2026-03-11"],
 ["INV-9917","CN-2026-0111","شركة الوفاء الطبية",21500,3225,0,7000,"جزئي","Partial","a","2026-07-21"],
 ["INV-9916","CN-2026-0110","هيا العنزي",15900,2385,0,0,"غير مسدّد","Unpaid","r","2026-02-04"]];
let RCINVQ="",RCINVST="";
rcScreens.invoices=()=>{
 const rows=rcInvRows.filter(r=>(!RCINVST||r[7]===RCINVST)&&(!RCINVQ||(r[0]+r[1]+r[2]).toLowerCase().includes(RCINVQ.toLowerCase())));
 const sum=k=>rcInvRows.reduce((n,r)=>n+r[k],0);
 const due=rcInvRows.reduce((n,r)=>n+(r[3]-r[5]-r[6]),0);
 return `
 <div class="page-head"><div><h1>${t("فواتير العقود","Contract invoices")}</h1><p>${t("فواتير عقود الاستقدام — الإجمالي والضريبة والخصم والمدفوع والمتبقي.","Recruitment contract invoices — total, tax, discount, paid and remaining.")}</p></div>
 <div class="actions"><button class="btn btn-ghost">${I("printer")}${t("طباعة","Print")}</button><button class="btn btn-ghost">${I("file-spreadsheet")}Excel</button><button class="btn btn-primary">${I("plus")}${t("إنشاء فاتورة","New invoice")}</button></div></div>
 <div class="statgrid">${[["إجمالي الفواتير","Total invoiced",sum(3).toLocaleString("en"),"receipt","n"],["الضريبة","Tax",sum(4).toLocaleString("en"),"percent","n"],["المحصّل","Collected",sum(6).toLocaleString("en"),"check-circle-2","g"],["المتبقي","Remaining",due.toLocaleString("en"),"alarm-clock","r"]].map(x=>`<div class="scard"><span class="sci ${x[4]}">${I(x[3])}</span><span class="sct">${t(x[0],x[1])}</span><b class="num">${x[2]}</b></div>`).join("")}</div>
 <div class="fbar">
  <div class="fb grow"><label>${t("بحث","Search")}</label><div class="inp">${I("search")}<input data-rcinvq value="${RCINVQ}" placeholder="${t("رقم الفاتورة، رقم العقد، العميل","Invoice no., contract no., client")}"></div></div>
  <div class="fb"><label>${t("حالة السداد","Payment status")}</label><select data-rcinvst><option value="">${t("كل الحالات","All statuses")}</option>${["مسدّد","جزئي","غير مسدّد"].map(x=>`<option${RCINVST===x?" selected":""}>${x}</option>`).join("")}</select></div>
  <div class="fb"><label>${t("من تاريخ","From date")}</label><input type="date"></div>
  <div class="fb"><label>${t("إلى تاريخ","To date")}</label><input type="date"></div>
 </div>
 <div class="card flush"><div class="tw"><table class="grp"><thead><tr><th>${t("رقم الفاتورة","Invoice no.")}</th><th>${t("العقد والعميل","Contract & client")}</th><th>${t("إجمالي الفاتورة","Invoice total")}</th><th>${t("الضريبة","Tax")}</th><th>${t("الخصم","Discount")}</th><th>${t("المدفوع","Paid")}</th><th>${t("المتبقي","Remaining")}</th><th>${t("الحالة","Status")}</th><th class="act">${t("إجراءات","Actions")}</th></tr></thead><tbody>
 ${rows.length?rows.map(r=>{const rem=r[3]-r[5]-r[6];return `<tr>
  <td><div class="gcell"><b class="num">${r[0]}</b><small class="num">${r[10]}</small></div></td>
  <td><div class="gcell"><b class="num">${r[1]}</b><small>${r[2]}</small></div></td>
  <td class="num">${r[3].toLocaleString("en")}</td><td class="num">${r[4].toLocaleString("en")}</td><td class="num">${r[5].toLocaleString("en")}</td><td class="num">${r[6].toLocaleString("en")}</td>
  <td class="num${rem?" due":""}">${rem.toLocaleString("en")}</td>
  <td><span class="st tag ${r[9]}"><i></i>${t(r[7],r[8])}</span></td>
  <td class="act"><div class="rowacts"><button class="icon-btn pri" data-rcshow="${r[1]}" title="${t("العقد","Contract")}">${I("eye")}</button><button class="icon-btn pri" title="${t("طباعة","Print")}">${I("printer")}</button><button class="icon-btn pri" title="${t("تسجيل دفعة","Record payment")}">${I("wallet")}</button></div></td></tr>`}).join(""):`<tr><td colspan="9"><div class="tempty">${I("inbox")}<b>${t("لا نتائج مطابقة","No matching results")}</b></div></td></tr>`}
 </tbody></table></div>
 <div class="tfoot"><span>${t(`عرض ${rows.length} من ${rcInvRows.length} ${rcInvRows.length>2&&rcInvRows.length<11?"فواتير":"فاتورة"}`,`Showing ${rows.length} of ${rcInvRows.length} invoices`)}</span></div></div>`;
};
Object.assign(T,{contractAdd:{ar:"إضافة عقد استقدام",en:"Add contract"},invoices:{ar:"فواتير العقود",en:"Contract invoices"}});
Object.assign(screens,rcScreens);
(function(){const g=nav.find(x=>x.t.ar==="العمليات");if(g&&!g.items.some(i=>i.id==="invoices"))g.items.splice(1,0,{id:"invoices",i:"receipt",ar:"فواتير العقود",en:"Contract invoices",b:"6"});})();
document.addEventListener("click",e=>{
 const at=e.target.closest("[data-rcaddtab]");if(at){RCADDTAB=at.dataset.rcaddtab;render();return}
 const ac=e.target.closest("[data-rcaddclient]");if(ac){RCADDCLIENT=ac.dataset.rcaddclient;render();return}
 const jp=e.target.closest("[data-rcjump]");if(jp){const el=document.getElementById(jp.dataset.rcjump);if(el)window.scrollTo({top:el.offsetTop-90,behavior:"smooth"});return}
});
document.addEventListener("input",e=>{if(e.target.matches("[data-rcinvq]")){RCINVQ=e.target.value;const p=e.target.selectionStart;render();const n=document.querySelector("[data-rcinvq]");if(n){n.focus();n.setSelectionRange(p,p)}}});
document.addEventListener("change",e=>{if(e.target.matches("[data-rcinvst]")){RCINVST=e.target.value;render()}});

const rcLtrNames=["phone","client_phone","full_name_en","maid_name","maid_passport","passport","national_num","tax_number","license_number","salary","contract_value","discount","paid","contract_number","visa_hijry_date","identity_date","border_number","account"];
const rcLtr=nm=>rcLtrNames.includes(nm);
/* ---------- edit contract (edit.blade.php: client / worker / contract sections) ---------- */
let RCEDIT=null;
rcScreens.contractEdit=()=>{
 const r=rcRows.find(x=>x[0]===RCEDIT)||rcRows[0];const comp=r[2]==="company";
 const f=(ar,en,nm,val,type,req)=>{let ctl;
  if(type==="select")ctl=`<select>${(Array.isArray(val)?val:[val]).map((o,i)=>`<option${i===0?" selected":""}>${o}</option>`).join("")}</select>`;
  else if(type==="date")ctl=`<input type="date" value="${val||""}">`;
  else if(type==="ro")ctl=`<input type="text" value="${val}" readonly${rcLtr(nm)?' dir="ltr"':""}>`;
  else ctl=`<input type="text" value="${val==null?"":val}"${rcLtr(nm)?' dir="ltr"':""}>`;
  return `<div class="fld"><label>${t(ar,en)}${req?' <i class="rq">*</i>':""}<code>${nm}</code></label>${ctl}</div>`};
 const grid=fs=>`<div class="fgrid">${fs.join("")}</div>`;
 return `
 <div class="page-head"><div><a class="back" href="#" data-rcshow="${r[0]}" style="margin:0 0 8px">${I("arrow-right")}<span>${t("الرجوع لتفاصيل العقد","Back to contract")}</span></a><h1>${t("تعديل العقد","Edit contract")}<span class="tag a hdrtag">${I("pencil")}${t("تعديل","Editing")}</span></h1><p><span class="num">${r[0]}</span> · ${r[1]} · ${t(r[5],r[4])} · ${rcStageLabel(r[9])}</p></div>
 <div class="actions"><button class="btn btn-ghost" data-rcshow="${r[0]}">${t("إلغاء","Cancel")}</button><button class="btn btn-primary" data-rcsave="${r[0]}">${I("check")}${t("حفظ التعديلات","Save changes")}</button></div></div>
 <div class="cvform">
  <aside class="cvopts">
   <div class="card"><div class="sec-h">${I("list-checks")}<b>${t("أقسام التعديل","Sections")}</b></div>
    <div class="steps">${[["e1","معلومات العميل","Client info"],["e2","بيانات العامل/ة","Worker data"],["e3","بيانات العقد","Contract data"],["e4","التأشيرة والتواريخ","Visa & dates"]].map((x,i)=>`<button class="step" data-rcjump="${x[0]}"><span class="stepn num">${i+1}</span><span>${t(x[1],x[2])}</span></button>`).join("")}</div></div>
   <div class="card"><div class="sec-h">${I("git-branch")}<b>${t("المرحلة والفرع","Stage & branch")}</b></div>
    <div class="actrow sm col"><label>${t("المرحلة الحالية","Current stage")}<code>status</code></label><select class="statsel" data-rcstageedit="${r[0]}">${rcStages.map(x=>`<option value="${x[0]}"${x[0]===r[9]?" selected":""}>${t(x[1],x[2])}</option>`).join("")}</select></div>
    <div class="actrow sm col"><label>${t("الفرع","Branch")}<code>branch_id</code></label><select class="statsel">${["الفرع الرئيسي","فرع جدة","فرع الدمام"].map(x=>`<option${x===r[15]?" selected":""}>${x}</option>`).join("")}</select></div>
    <div class="actrow sm col"><label>${t("المسوّق","Marketer")}<code>marketer_id</code></label><select class="statsel">${rcMarketers.map(x=>`<option${x===r[14]?" selected":""}>${x}</option>`).join("")}</select></div>
   </div>
  </aside>
  <div class="cvfields">
   <div class="card sec" id="e1"><div class="fs-h"><span class="fs-n num">1</span><b>${t("معلومات العميل","Client info")}</b></div>
    ${grid([f("نوع العميل","Client type","client_type",comp?[t("شركة","Company"),t("فرد","Person")]:[t("فرد","Person"),t("شركة","Company")],"select",1),
      f("اسم العميل","Client name","client_name",r[1],"",1),f("جوال العميل","Client phone","client_phone",r[3],"",1),
      f("الجنسية","Nationality","nationality_id",["سعودي","مصري","سوري","يمني"],"select"),f("البريد الإلكتروني","Email","email","","",0),
      f(comp?"الرقم الضريبي":"رقم الهوية",comp?"Tax number":"National ID",comp?"tax_number":"national_num","1042887331","",1),
      f("رقم الرخصة","Licence number","license_number",comp?"4030118822":"","",0),f("تاريخ الميلاد","Birth date","birthdate","1988-04-12","date"),
      f("العنوان","Address","address","حي الملقا، طريق أنس بن مالك"),f("المدينة","City","city",["الرياض","جدة","الدمام"],"select"),
      f("عدد أفراد الأسرة","Family number","family_number",comp?"":"5"),f("عدد الأطفال (تحت 12)","Children","children",comp?"":"3"),f("المهنة","Job","job",comp?"نشاط تجاري":"موظف حكومي")])}</div>
   <div class="card sec" id="e2"><div class="fs-h"><span class="fs-n num">2</span><b>${t("بيانات العامل/ة","Worker data")}</b></div>
    ${grid([f("اسم العاملة","Worker name","maid_name",r[4],"ro"),f("رقم الجواز","Passport","maid_passport",r[8],"ro"),
      f("الجنسية","Nationality","nationality",r[6],"ro"),f("الديانة","Religion","religion",["مسيحية","مسلمة","أخرى"],"select"),
      f("المهنة","Occupation","maid_job",[r[7],"عاملة منزلية","طباخة","سائق خاص"],"select"),f("الراتب","Salary","salary","1,300"),
      f("سنوات الخبرة","Experience years","experience_years","4"),f("العمر","Age","age","31"),
      f("مكتب الاستقدام","Recruitment office","office_id",r[13]?[r[13]].concat(rcOffices.filter(x=>x!==r[13])):rcOffices,"select")])}
    <p class="pnote">${t("بيانات العاملة الأساسية تُقرأ من السيرة الذاتية ولا تُعدَّل من العقد.","Core worker data comes from the CV and is not editable from the contract.")}</p></div>
   <div class="card sec" id="e3"><div class="fs-h"><span class="fs-n num">3</span><b>${t("بيانات العقد","Contract data")}</b></div>
    ${grid([f("رقم العقد","Contract no.","contract_number",r[0],"ro"),f("قيمة العقد","Contract value","contract_value",r[16].toLocaleString("en"),"",1),
      f("قيمة الخصم","Discount","discount",r[18].toLocaleString("en")),f("المدفوع","Paid","paid",r[17].toLocaleString("en")),
      f("مدة العقد (شهر)","Term (months)","duration",["24","12","6"],"select"),f("نوع الطلب","Request type","request_type",[r[19]].concat(rcReqTypes.filter(x=>x!==r[19])),"select"),
      f("الضمان","Warranty","warranty",rcWarranty.filter(w=>w[0]).map(w=>t(w[1],w[2])),"select")])}</div>
   <div class="card sec" id="e4"><div class="fs-h"><span class="fs-n num">4</span><b>${t("التأشيرة والتواريخ","Visa & dates")}</b></div>
    ${grid([f("تاريخ العقد","Contract date","contract_date",r[10],"date",1),f("تاريخ التأشيرة","Visa date","visa_date_is","","date"),
      f("تاريخ التأشيرة الهجري","Visa Hijri date","visa_hijry_date","1447/02/15"),f("تاريخ الوصول","Arrival date","arrival_date",r[12],"date")])}
    <div class="fld full"><label>${t("ملاحظات العقد","Contract notes")}<code>notes</code></label><textarea rows="3"></textarea></div></div>
   <div class="formbar"><button class="btn btn-ghost" data-rcshow="${r[0]}">${t("إلغاء","Cancel")}</button><button class="btn btn-primary" data-rcsave="${r[0]}">${I("check")}${t("حفظ التعديلات","Save changes")}</button></div>
  </div>
 </div>`;
};
Object.assign(T,{contractEdit:{ar:"تعديل العقد",en:"Edit contract"}});
Object.assign(screens,rcScreens);
document.addEventListener("click",e=>{
 const ed=e.target.closest("[data-rcedit]");if(ed){RCEDIT=ed.dataset.rcedit;cur="contractEdit";render();window.scrollTo(0,0);return}
 const sv=e.target.closest("[data-rcsave]");if(sv){RCID=sv.dataset.rcsave;cur="contractShow";RCTAB="over";render();window.scrollTo(0,0);clToast("تم حفظ تعديلات العقد","Contract changes saved");return}
});
document.addEventListener("change",e=>{
 const se=e.target.closest("[data-rcstageedit]");
 if(se){const r=rcRows.find(x=>x[0]===se.dataset.rcstageedit);if(r){r[9]=e.target.value;clToast("تم تحديث مرحلة العقد","Contract stage updated")}}
});
