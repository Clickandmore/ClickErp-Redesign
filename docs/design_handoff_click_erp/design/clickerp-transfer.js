/* Sponsorship transfer — grounded in admin/ingaz/SponsorshipTransfer/{index,trial,trial-details,transferred,failed,web-requests,create,show,invoice-create,status-overview}.blade.php */
const trTabs=[["all","كل الطلبات","All requests","layers",17],["trial","فترة التجربة","Trial period","hourglass",2],["transferred","تم النقل","Transferred","check-circle-2",6],["failed","فشل النقل","Failed","x-circle",2],["web","طلبات الموقع","Web requests","globe",7]];
const trSt=[["pending","تحت الإجراء","In progress","a"],["trial","فترة التجربة","Trial period","n"],["transferred","تم النقل","Transferred","g"],["failed","فشل النقل","Failed","r"]];
const trWebSt=[["new","جديد","New","a"],["reviewing","تحت المراجعة","Under review","n"],["approved","تم التحويل لطلب","Converted","g"],["rejected","مرفوض","Rejected","r"]];
const trFailReasons=["رفض الكفيل الجديد","عدم استكمال المستندات","انتهاء فترة التجربة دون موافقة","رفض العاملة العمل","مخالفة أنظمة النقل"];
const trPayTypes=["كاش","تحويل بنكي","شبكة (مدى)","تقسيط"];
const trAccounts=["الصندوق الرئيسي","بنك الراجحي — 1122","بنك الأهلي — 4408"];
/* 0 no 1 contract 2 oldSponsor 3 oldDoc 4 newSponsor 5 newDoc 6 newPhone 7 maid 8 maidEn 9 passport 10 nat 11 trialDays 12 trialStart 13 trialEnd 14 remainingDays 15 value 16 paid 17 remaining 18 status 19 created 20 failReason 21 payType 22 payStatus 23 branch 24 agent 25 webSt */
const trRows=[
 ["TR-2026-0031","CN-2026-0113","نورة الحربي","1042887331","سعد المطيري","1078441220","+966 50 771 2280","كوماري بيريرا","Kumari Perera","P3391077","سريلانكا",90,"2026-08-01","2026-10-30",72,14000,7000,7000,"trial","2026-07-28","","تحويل بنكي","جزئي","الفرع الرئيسي","سارة القحطاني",""],
 ["TR-2026-0030","CN-2026-0112","عبدالله الدوسري","1099220145","ريم الغامدي","1055881003","+966 55 903 4417","غريس ناكاتو","Grace Nakato","P8812440","أوغندا",30,"2026-08-10","2026-09-09",21,12500,12500,0,"trial","2026-08-08","","كاش","مسدّد","فرع جدة","خالد العمري",""],
 ["TR-2026-0028","CN-2026-0104","مؤسسة البيان التجارية","3004411882","تركي العتيبي","1066220774","+966 56 220 7741","جينيفر لوبيز","Jenifer Lopez","P1129884","الفلبين",90,"2026-04-02","2026-07-01",-49,15800,15800,0,"transferred","2026-03-28","","تحويل بنكي","مسدّد","فرع جدة","محمد الشهراني",""],
 ["TR-2026-0027","CN-2026-0098","فيصل القحطاني","1033purpose".slice(0,10),"منيرة الشهري","1042887331","+966 55 214 8890","سري واهيوني","Sri Wahyuni","P5520914","إندونيسيا",60,"2026-03-14","2026-05-13",-98,13200,13200,0,"transferred","2026-03-10","","شبكة (مدى)","مسدّد","الفرع الرئيسي","سارة القحطاني",""],
 ["TR-2026-0025","CN-2026-0091","شركة نجد للمقاولات","3001122447","شركة الوفاء الطبية","3009988112","+966 13 887 9011","نيلانثي سيلفا","Nilanthi Silva","P4408123","سريلانكا",90,"2026-02-01","2026-05-02",-139,18000,9000,9000,"failed","2026-01-28","عدم استكمال المستندات","تقسيط","جزئي","فرع الدمام","مشرف النظام",""],
 ["TR-2026-0022","CN-2026-0087","هيا العنزي","1077441200","نواف القحطاني","1088552003","+966 53 660 4471","عائشة خان","Ayesha Khan","P6620355","بنغلاديش",30,"2025-12-05","2026-01-04",-227,11800,4200,7600,"failed","2025-12-01","رفض الكفيل الجديد","كاش","جزئي","الفرع الرئيسي","سارة القحطاني",""],
 ["TR-2026-0033","CN-2026-0115","منيرة الشهري","1042887331","—","—","+966 59 118 2043","ماريتس دي لا كروز","Marites Dela Cruz","P7741203","الفلبين",0,"","",0,14500,0,14500,"pending","2026-08-18","","","غير مسدّد","الفرع الرئيسي","خالد العمري",""],
 ["WR-2026-0044","CN-2026-0110","—","—","لمى السبيعي","1090334221","+966 54 990 1123","لاكشمي راج","Lakshmi Raj","P7712004","الهند",0,"","",0,0,0,0,"pending","2026-08-17","","","",'الفرع الرئيسي',"سارة القحطاني","new"],
 ["WR-2026-0043","CN-2026-0109","—","—","بدر الشمري","1071228840","+966 56 330 4471","رينا أستوتي","Rina Astuti","P9903221","إندونيسيا",0,"","",0,0,0,0,"pending","2026-08-15","","","",'فرع الدمام',"خالد العمري","reviewing"]];
trRows[3][3]="1033441200";
const trTrialSteps=[["request","تسجيل الطلب","Request logged"],["docs","استكمال المستندات","Documents completed"],["trial","بداية فترة التجربة","Trial started"],["confirm","تأكيد النقل","Transfer confirmed"],["invoice","إصدار الفاتورة","Invoice issued"]];
let TRTAB="all",TRQ="",TRSTATUS="",TRPAY="",TRMORE=false,TRID="TR-2026-0031",TRSTAB="over",TRMENU=null;
const trTotal=k=>(trTabs.find(x=>x[0]===k)||[,,,,0])[4];
const trSet=()=>{switch(TRTAB){
 case "trial":return trRows.filter(r=>r[18]==="trial");
 case "transferred":return trRows.filter(r=>r[18]==="transferred");
 case "failed":return trRows.filter(r=>r[18]==="failed");
 case "web":return trRows.filter(r=>r[25]);
 default:return trRows.filter(r=>!r[25])}};
const trFiltered=()=>trSet().filter(r=>(!TRSTATUS||r[18]===TRSTATUS)&&(!TRPAY||r[21]===TRPAY)&&(!TRQ||(r[0]+r[1]+r[2]+r[4]+r[7]+r[9]).toLowerCase().includes(TRQ.toLowerCase())));
const trStTag=r=>{const s=trSt.find(x=>x[0]===r[18])||trSt[0];return `<span class="st tag ${s[3]}"><i></i>${t(s[1],s[2])}</span>`};
const trWebTag=r=>{const s=trWebSt.find(x=>x[0]===r[25])||trWebSt[0];return `<span class="st tag ${s[3]}"><i></i>${t(s[1],s[2])}</span>`};
const trTrialEnd=r=>{if(!r[13])return `<span class="empty">${t("غير محدد","Not set")}</span>`;
 const d=r[14];const badge=d>0?`<span class="tag n">${t(`باقي ${d} يوم`,`${d} days left`)}</span>`:d===0?`<span class="tag a">${t("تنتهي اليوم","Ends today")}</span>`:`<span class="tag ${r[18]==="trial"?"r":""}">${t(`انتهت منذ ${Math.abs(d)} يوم`,`Ended ${Math.abs(d)} days ago`)}</span>`;
 return `<div class="gcell"><b class="num">${r[13]}</b>${badge}</div>`};
const trMoney=n=>`<span class="num">${n.toLocaleString("en")}</span>`;
const trStats=()=>{const T0=trTotal(TRTAB);switch(TRTAB){
 case "trial":return [["","في فترة التجربة","In trial","hourglass","n",T0],["ending","تنتهي هذا الأسبوع","Ending this week","alarm-clock","a",1],["overdue","تجاوزت المدة","Overdue","x-circle","r",0],["unpaid","عليها متبقي","With balance","wallet","a",1]];
 case "transferred":return [["","تم النقل","Transferred","check-circle-2","g",T0],["month","هذا الشهر","This month","calendar","n",1],["value","إجمالي المبالغ","Total value","banknote","n",88400],["sponsors","كفلاء جدد","New sponsors","users","n",6]];
 case "failed":return [["","فشل النقل","Failed","x-circle","r",T0],["docs","مستندات ناقصة","Missing documents","file-x","a",1],["refused","رفض الكفيل","Sponsor refused","user-x","r",1],["due","مبالغ للتسوية","Amounts to settle","wallet","a",13200]];
 case "web":return [["","طلبات الموقع","Web requests","globe","n",T0],["new","جديدة","New","bell-dot","a",3],["reviewing","تحت المراجعة","Under review","search","n",3],["approved","تم تحويلها لطلب","Converted","check-circle-2","g",1]];
 default:return [["","إجمالي الطلبات","Total requests","layers","n",T0],["pending","تحت الإجراء","In progress","loader","a",7],["trial","فترة التجربة","Trial period","hourglass","n",2],["transferred","تم النقل","Transferred","check-circle-2","g",6],["failed","فشل النقل","Failed","x-circle","r",2],["value","إجمالي قيم العقود","Contracts value","banknote","n",214800],["paid","المحصّل","Collected","check-circle-2","g",148200],["remaining","المتبقي","Remaining","alarm-clock","r",66600]]}};
const trMenuItems=r=>r[25]
 ?[["eye","عرض الطلب","Open request"],["file-plus","تحويل إلى طلب نقل","Convert to transfer"],["x-circle","رفض الطلب","Reject request"]]
 :[["check-circle-2","تأكيد النقل","Confirm transfer"],["x-circle","إلغاء النقل وإعادته للإيواء","Cancel and return to shelter"],["receipt","إصدار فاتورة","Create invoice"],["wallet","تسوية مالية","Financial settlement"],["hourglass","بدء فترة التجربة","Start trial"],["printer","طباعة الطلب","Print request"]];
const trActs=r=>`<div class="rowacts"><button class="icon-btn pri" data-trshow="${r[0]}" title="${t("عرض التفاصيل","View details")}">${I("eye")}</button>${r[25]?"":`<button class="icon-btn pri" data-tredit="${r[0]}" title="${t("تعديل","Edit")}">${I("pencil")}</button>`}<span class="kebab"><button class="icon-btn" data-trmenu="${r[0]}" title="${t("الإجراءات","Actions")}">${I("more-horizontal")}</button><div class="amenu${TRMENU===r[0]?"":" hidden"}" data-trmenu-for="${r[0]}">${trMenuItems(r).map(x=>`<button data-tract="${x[0]}:${r[0]}"${x[0]==="x-circle"?' class="danger"':""}>${I(x[0])}<span>${t(x[1],x[2])}</span></button>`).join("")}</div></span></div>`;
const trSponsorCell=(name,doc,phone)=>name==="—"?`<span class="empty">${t("غير محدد","Not set")}</span>`:`<div class="gcell"><b>${name}</b><small class="num">${doc}</small>${phone?`<small class="num">${phone}</small>`:""}</div>`;
const trMaidCell=r=>`<div class="wcell"><span class="av">${I("user-round")}</span><div><b>${r[7]}</b><small>${r[8]}</small><small class="num">${t("جواز","P")} ${r[9]} · ${r[10]}</small></div></div>`;
const trHead=()=>{
 const cols={
  all:[["رقم العقد","Contract"],["الكفيل القديم","Old sponsor"],["الكفيل الجديد","New sponsor"],["بيانات العاملة","Worker data"],["فترة التجربة","Trial days"],["بداية التجربة","Trial start"],["نهاية التجربة","Trial end"],["قيمة العقد","Contract value"],["المدفوع","Paid"],["المتبقي","Remaining"],["الحالة","Status"],["تاريخ الإنشاء","Created at"]],
  trial:[["رقم العقد","Contract"],["الكفيل القديم","Old sponsor"],["الكفيل الجديد","New sponsor"],["بيانات العاملة","Worker data"],["فترة التجربة","Trial period"],["نهاية فترة التجربة","Trial end"],["المبلغ المتبقي","Remaining"],["الحالة","Status"]],
  transferred:[["رقم العقد","Contract"],["الكفيل الجديد","New sponsor"],["بيانات العاملة","Worker data"],["تاريخ النقل","Transfer date"],["قيمة العقد","Contract value"],["المحصّل","Collected"],["الفرع والموظف","Branch & agent"],["الحالة","Status"]],
  failed:[["رقم العقد","Contract"],["الكفيل الجديد","New sponsor"],["بيانات العاملة","Worker data"],["سبب الفشل","Failure reason"],["قيمة العقد","Contract value"],["المتبقي للتسوية","To settle"],["تاريخ الطلب","Request date"],["الحالة","Status"]],
  web:[["الطلب","Order"],["بيانات العميل","Client info"],["بيانات العاملة","Worker data"],["الموظف المسؤول","Assigned admin"],["تاريخ الطلب","Request date"],["الحالة","Status"]]}[TRTAB];
 return `<tr><th class="chk"><input type="checkbox"></th><th>${t("رقم الطلب","Request no.")}</th>${cols.map(c=>`<th>${t(c[0],c[1])}</th>`).join("")}<th class="act">${t("الإجراءات","Actions")}</th></tr>`;
};
const trCells=r=>{
 const g=(b,...s)=>`<div class="gcell"><b>${b}</b>${s.filter(Boolean).map(x=>`<small>${x}</small>`).join("")}</div>`;
 const contract=`<button class="lnk" data-go="contracts">${I("file-signature")}<span class="num">${r[1]}</span></button>`;
 const cols={
  all:[contract,trSponsorCell(r[2],r[3]),trSponsorCell(r[4],r[5],r[6]),trMaidCell(r),r[11]?g(`<span class="num">${r[11]}</span> ${t("يوم","d")}`):`<span class="empty">—</span>`,r[12]?`<span class="num">${r[12]}</span>`:`<span class="empty">—</span>`,trTrialEnd(r),trMoney(r[15]),trMoney(r[16]),`<span class="num${r[17]?" due":""}">${r[17].toLocaleString("en")}</span>`,trStTag(r),`<span class="num">${r[19]}</span>`],
  trial:[contract,trSponsorCell(r[2],r[3]),trSponsorCell(r[4],r[5],r[6]),trMaidCell(r),g(`<span class="num">${r[11]}</span> ${t("يوم","days")}`,`${t("بدأت","started")} ${r[12]}`),trTrialEnd(r),`<span class="num${r[17]?" due":""}">${r[17].toLocaleString("en")}</span>`,trStTag(r)],
  transferred:[contract,trSponsorCell(r[4],r[5],r[6]),trMaidCell(r),`<span class="num">${r[13]}</span>`,trMoney(r[15]),trMoney(r[16]),g(r[23],r[24]),trStTag(r)],
  failed:[contract,trSponsorCell(r[4],r[5],r[6]),trMaidCell(r),`<div class="gcell x" style="white-space:normal;max-width:220px"><span class="tag r">${r[20]}</span></div>`,trMoney(r[15]),`<span class="num${r[17]?" due":""}">${r[17].toLocaleString("en")}</span>`,`<span class="num">${r[19]}</span>`,trStTag(r)],
  web:[g(`<span class="num">${r[1]}</span>`,t("طلب من الموقع","From website")),trSponsorCell(r[4],r[5],r[6]),trMaidCell(r),g(r[24],r[23]),`<span class="num">${r[19]}</span>`,trWebTag(r)]}[TRTAB];
 return `<tr><td class="chk"><input type="checkbox"></td><td><div class="gcell"><b class="num">${r[0]}</b><small>${r[23]}</small></div></td>${cols.map(c=>`<td>${c}</td>`).join("")}<td class="act">${trActs(r)}</td></tr>`;
};
const transferScreens={
 transfer:()=>{
  const rows=trFiltered(),stats=trStats(),tab=trTabs.find(x=>x[0]===TRTAB);
  const colCount=trHead().split("<th").length-1;
  return `
 <div class="page-head"><div><h1>${t("نقل الكفالة","Sponsorship transfer")}</h1><p>${t("نقل العاملة من كفيل إلى آخر — من الطلب إلى فترة التجربة ثم تأكيد النقل أو تسجيل الفشل والتسوية.","Transferring a worker between sponsors — from request to trial, then confirmation or failure and settlement.")}</p></div>
 <div class="actions"><button class="btn btn-ghost" data-go="transferOverview">${I("bar-chart-3")}${t("نظرة عامة على الحالات","Status overview")}</button><button class="btn btn-ghost">${I("file-spreadsheet")}Excel</button><button class="btn btn-primary" data-go="transferAdd">${I("plus")}${t("طلب نقل كفالة","New transfer")}</button></div></div>
 <div class="seg tabs">${trTabs.map(x=>`<button class="${x[0]===TRTAB?"on":""}" data-trtab="${x[0]}">${t(x[1],x[2])}<b class="segb num">${x[4]}</b></button>`).join("")}</div>
 <div class="pipe">${stats.slice(0,4).map(x=>`<button class="pg ${x[4]==="g"?"post":x[4]==="r"?"closed":"run"} ${x[0]&&x[0]===TRSTATUS?"on":""}" data-trstat="${x[0]}">${I(x[3])}<span class="pg-t">${t(x[1],x[2])}</span><b class="num">${x[5].toLocaleString("en")}</b></button>`).join("")}</div>
 ${stats.length>4?`<div class="pipe">${stats.slice(4).map(x=>`<button class="pg ${x[4]==="g"?"post":x[4]==="r"?"closed":"run"} ${x[0]&&x[0]===TRSTATUS?"on":""}" data-trstat="${x[0]}">${I(x[3])}<span class="pg-t">${t(x[1],x[2])}</span><b class="num">${x[5].toLocaleString("en")}</b></button>`).join("")}</div>`:""}
 <div class="fbar">
  <div class="fb grow"><label>${t("بحث","Search")}</label><div class="inp">${I("search")}<input data-trq value="${TRQ}" placeholder="${t("رقم الطلب، رقم العقد، الكفيل، العاملة، الجواز","Request no., contract, sponsor, worker, passport")}"></div></div>
  <div class="fb"><label>${t("الحالة","Status")}</label><select data-trstatus><option value="">${t("كل الحالات","All statuses")}</option>${trSt.map(s=>`<option value="${s[0]}"${TRSTATUS===s[0]?" selected":""}>${t(s[1],s[2])}</option>`).join("")}</select></div>
  <div class="fb"><label>${t("أيام التجربة","Trial days")}</label><select><option>${t("الكل","All")}</option><option>30</option><option>60</option><option>90</option></select></div>
  <div class="fb"><label>${t("من تاريخ","Date from")}</label><input type="date"></div>
  <button class="btn btn-ghost" data-trmore>${I(TRMORE?"chevron-up":"sliders-horizontal")}${t(TRMORE?"إخفاء":"فلاتر أكثر",TRMORE?"Hide":"More filters")}</button>
  <span class="empty">${t(`${rows.length} نتيجة`,`${rows.length} results`)}</span>
 </div>
 ${TRMORE?`<div class="fbar second">
  <div class="fb"><label>${t("الكفيل القديم","Old sponsor")}</label><input type="text" placeholder="${t("اسم أو رقم هوية","Name or ID")}"></div>
  <div class="fb"><label>${t("الكفيل الجديد","New sponsor")}</label><input type="text" placeholder="${t("اسم أو رقم هوية","Name or ID")}"></div>
  <div class="fb"><label>${t("رقم العقد","Contract number")}</label><input type="text" placeholder="CN-2026-XXXX" dir="ltr"></div>
  <div class="fb"><label>${t("طريقة الدفع","Payment method")}</label><select data-trpay><option value="">${t("الكل","All")}</option>${trPayTypes.map(x=>`<option${TRPAY===x?" selected":""}>${x}</option>`).join("")}</select></div>
  <div class="fb"><label>${t("قيمة العقد من","Value from")}</label><input type="text" placeholder="0" dir="ltr"></div>
  <div class="fb"><label>${t("قيمة العقد إلى","Value to")}</label><input type="text" placeholder="20,000" dir="ltr"></div>
  <div class="fb"><label>${t("سبب الفشل","Failure reason")}</label><select><option>${t("الكل","All")}</option>${trFailReasons.map(x=>`<option>${x}</option>`).join("")}</select></div>
  <div class="fb"><label>${t("إلى تاريخ","Date to")}</label><input type="date"></div>
 </div>`:""}
 <div class="card flush"><div class="card-h"><div><h3>${t(tab[1],tab[2])}</h3><p>${t("الأعمدة كما في شاشة النظام لهذا التبويب.","Columns match the system screen for this tab.")}</p></div></div>
 <div class="tw"><table class="grp"><thead>${trHead()}</thead><tbody>
 ${rows.length?rows.map(trCells).join(""):`<tr><td colspan="${colCount}"><div class="tempty">${I("inbox")}<b>${t("لا طلبات مطابقة","No matching requests")}</b><small>${t("جرّب إزالة الفلاتر","Try clearing the filters")}</small></div></td></tr>`}
 </tbody></table></div>
 <div class="tfoot"><span>${t(`عرض ${rows.length} من ${tab[4]} طلب`,`Showing ${rows.length} of ${tab[4]} requests`)}</span><div class="pager"><button>${I("chevron-right")}</button><button class="on">1</button><button>2</button><button>${I("chevron-left")}</button></div></div>
 </div>`},
 transferShow:()=>{
  const r=trRows.find(x=>x[0]===TRID)||trRows[0];
  const step=r[18]==="transferred"?5:r[18]==="trial"?3:r[18]==="failed"?2:1;
  const tabs=[["over","نظرة عامة","Overview","gauge"],["sponsors","الكفيلان","Sponsors","users"],["maid","بيانات العاملة","Worker data","id-card"],["trial","فترة التجربة","Trial period","hourglass"],["finance","المالية والفاتورة","Finance & invoice","wallet"],["files","المرفقات","Attachments","paperclip"],["log","السجل","Activity log","history"]];
  const body={
   over:()=>`
    ${sect("gauge","ملخص الطلب","Request summary",`<div class="ovgrid">${[["رقم الطلب","Request no.",`<span class="num">${r[0]}</span>`,r[23]],["العقد المرتبط","Linked contract",`<span class="num">${r[1]}</span>`,t("عقد استقدام","Recruitment contract")],["الحالة","Status",trStTag(r),r[19]],["قيمة العقد","Contract value",trMoney(r[15]),t("ر.س","SAR")],["المدفوع","Paid",trMoney(r[16]),r[22]||t("غير محدد","Not set")],["المتبقي","Remaining",`<span class="num${r[17]?" due":""}">${r[17].toLocaleString("en")}</span>`,t("ر.س","SAR")]].map(x=>`<div class="ovc"><span class="ovl">${t(x[0],x[1])}</span><b class="ovv">${x[2]}</b><small class="ovm">${x[3]}</small></div>`).join("")}</div>`)}
    ${sect("git-branch","مسار النقل","Transfer path",`<div class="depsteps">${trTrialSteps.map((s,i)=>`<div class="dstep ${i<step?"done":i===step?"current":""}"><span class="ds-n">${i<step?I("check"):`<b class="num">${i+1}</b>`}</span><div><b>${t(s[1],s[2])}</b><small>${i<step?t("مكتمل","Completed"):i===step?(r[18]==="failed"?`<span class="bad">${t("توقف: ","Stopped: ")}${r[20]}</span>`:t("قيد التنفيذ","In progress")):t("لم يبدأ","Not started")}</small></div>${i<trTrialSteps.length-1?'<span class="dl"></span>':""}</div>`).join("")}</div>`)}
    ${r[18]==="failed"?sect("x-circle","سبب الفشل والتسوية","Failure & settlement",`<div class="convbox"><div><b>${r[20]}</b><small>${t("المتبقي للتسوية","Amount to settle")}: <span class="num">${r[17].toLocaleString("en")}</span> ${t("ر.س","SAR")}</small></div><button class="btn btn-primary" data-trsettle="${r[0]}">${I("wallet")}${t("تسوية مالية","Financial settlement")}</button></div>`):""}`,
   sponsors:()=>`
    ${sect("user-x","الكفيل القديم","Old sponsor",kvs([["الاسم","Name",r[2]],["رقم الهوية/السجل","ID / registry",`<span class="num">${r[3]}</span>`],["العقد","Contract",`<span class="num">${r[1]}</span>`],["الفرع","Branch",r[23]]]))}
    ${sect("user-check","الكفيل الجديد","New sponsor",r[4]==="—"?`<div class="tempty">${I("user-plus")}<b>${t("لم يُحدد الكفيل الجديد","New sponsor not set")}</b><small>${t("يُختار من العملاء أو يُضاف عميل جديد","Pick an existing client or add a new one")}</small></div>`:kvs([["الاسم","Name",r[4]],["رقم الهوية/السجل","ID / registry",`<span class="num">${r[5]}</span>`],["رقم الجوال","Phone",`<span class="num">${r[6]}</span>`],["الموظف المسؤول","Agent",r[24]]]))}`,
   maid:()=>sect("id-card","بيانات العاملة","Worker data",kvs([["الاسم","Name",r[7]],["الاسم بالإنجليزية","Name (EN)",`<span class="num">${r[8]}</span>`],["رقم الجواز","Passport",`<span class="num">${r[9]}</span>`],["الجنسية","Nationality",r[10]],["العقد","Contract",`<span class="num">${r[1]}</span>`],["السيرة الذاتية","CV",`<button class="lnk" data-go="cvs">${I("files")}<span>${t("فتح السيرة","Open CV")}</span></button>`]])),
   trial:()=>r[12]
    ?`${sect("hourglass","فترة التجربة","Trial period",kvs([["عدد أيام العمل التجريبي","Trial days",`<span class="num">${r[11]}</span> ${t("يوم","days")}`],["تاريخ البداية","Start date",`<span class="num">${r[12]}</span>`],["تاريخ النهاية","End date",`<span class="num">${r[13]}</span>`],["المتبقي","Days left",r[14]>0?`<span class="num">${r[14]}</span> ${t("يوم","days")}`:r[14]===0?t("تنتهي اليوم","Ends today"):t(`انتهت منذ ${Math.abs(r[14])} يوم`,`Ended ${Math.abs(r[14])} days ago`)]]))}
     ${sect("check-circle-2","إجراءات نهاية التجربة","End-of-trial actions",`<div class="convbox"><div><b>${t("عند انتهاء فترة التجربة","At the end of the trial")}</b><small>${t("يُؤكَّد النقل نهائياً أو يُلغى ويعود العقد إلى الإيواء.","Either confirm the transfer or cancel it and return the contract to the shelter.")}</small></div><div class="actions"><button class="btn btn-ghost danger" data-tract="x-circle:${r[0]}">${I("x-circle")}${t("إلغاء النقل","Cancel transfer")}</button><button class="btn btn-primary" data-tract="check-circle-2:${r[0]}">${I("check-circle-2")}${t("تأكيد النقل","Confirm transfer")}</button></div></div>`)}`
    :sect("hourglass","فترة التجربة","Trial period",`<div class="tempty">${I("hourglass")}<b>${t("لم تبدأ فترة التجربة","Trial not started")}</b><small>${t("تبدأ بعد استكمال بيانات الكفيل الجديد والمستندات.","Starts after the new sponsor data and documents are completed.")}</small></div>`),
   finance:()=>`
    ${sect("wallet","القيم المالية","Financial values",`<div class="ovgrid four">${[["قيمة العقد","Contract value",r[15].toLocaleString("en"),t("ر.س","SAR"),1],["المبلغ المدفوع","Paid amount",r[16].toLocaleString("en"),t("ر.س","SAR"),1],["المبلغ المتبقي","Remaining",r[17].toLocaleString("en"),t("ر.س","SAR"),1],["طريقة الدفع","Payment method",r[21]||`<span class="empty">${t("غير محددة","Not set")}</span>`,r[22]||t("حالة الدفع غير محددة","Payment status not set"),0]].map(x=>`<div class="ovc"><span class="ovl">${t(x[0],x[1])}</span><b class="ovv${x[4]?" num":""}">${x[2]}</b><small class="ovm">${x[3]}</small></div>`).join("")}</div>`)}
    ${sect("receipt","الفاتورة","Invoice",r[18]==="transferred"?`<div class="tw"><table><thead><tr><th>${t("رقم الفاتورة","Invoice no.")}</th><th>${t("تاريخ الفاتورة","Invoice date")}</th><th>${t("وصف الفاتورة","Description")}</th><th>${t("الخصم","Discount")}</th><th>${t("الإجمالي","Total")}</th></tr></thead><tbody>
     <tr><td class="num">INV-TR-${r[0].slice(-4)}</td><td class="num">${r[13]}</td><td>${t("نقل كفالة عاملة منزلية","Domestic worker sponsorship transfer")}</td><td class="num">0</td><td class="num">${r[15].toLocaleString("en")}</td></tr>
    </tbody></table></div>`:`<div class="convbox"><div><b>${t("لا توجد فاتورة بعد","No invoice yet")}</b><small>${t("تُصدر الفاتورة بعد تأكيد النقل.","The invoice is issued after the transfer is confirmed.")}</small></div><button class="btn btn-primary" data-trinvoice="${r[0]}">${I("receipt")}${t("إصدار فاتورة","Create invoice")}</button></div>`)}
    ${sect("landmark","بيانات السداد","Payment details",kvs([["طريقة الدفع","Payment method",r[21]||"—"],["حالة الدفع","Payment status",r[22]||"—"],["البنك / حساب السداد","Bank / account",trAccounts[1]],["إيصال الدفع","Payment receipt",r[16]?`<button class="lnk">${I("file-text")}<span>receipt_${r[0]}.pdf</span></button>`:`<span class="empty">${t("غير مرفوع","Not uploaded")}</span>`]]))}`,
   files:()=>sect("paperclip","المرفقات","Attachments",`
    ${[["إيصال الدفع","Payment receipt",r[16]?"receipt_"+r[0]+".pdf":""],["هوية الكفيل الجديد","New sponsor ID",r[4]!=="—"?"id_"+r[5]+".jpg":""],["نموذج الموافقة","Consent form","consent_form.pdf"],["مرفق الآيبان","IBAN attachment",r[18]==="failed"?"iban.pdf":""]].map(x=>`<div class="att"><div class="att-t">${I("file-text")}<div><b>${t(x[0],x[1])}</b><small>${x[2]||t("غير مرفوع","Not uploaded")}</small></div></div><div class="att-a">${x[2]?`<button class="btn btn-ghost">${I("eye")}${t("عرض","Preview")}</button><button class="btn btn-ghost">${I("download")}${t("تنزيل","Download")}</button>`:`<button class="btn btn-ghost">${I("upload")}${t("رفع","Upload")}</button>`}</div></div>`).join("")}
    <div class="upl"><label>${t("مرفقات إضافية","Additional attachments")}<code>attachments</code></label><div class="ctl file">${I("upload")}<span>${t("اختر الملفات — يمكن تحديد أكثر من ملف","Select files — multiple allowed")}</span></div><button class="btn btn-primary">${I("upload")}${t("رفع","Upload")}</button></div>`),
   log:()=>sect("history","السجل","Activity log",`<div class="tl">${[[r[19],t("تسجيل طلب نقل الكفالة","Transfer request logged"),r[24]],r[12]?[r[12],t("بداية فترة التجربة","Trial period started"),r[24]]:null,r[18]==="transferred"?[r[13],t("تأكيد النقل وإصدار الفاتورة","Transfer confirmed and invoice issued"),r[24]]:null,r[18]==="failed"?[r[13],t("تسجيل فشل النقل: ","Transfer failed: ")+r[20],r[24]]:null].filter(Boolean).map(x=>`<div class="tli"><span class="dot"></span><div><b>${x[1]}</b><small>${x[2]}</small><small class="num">${x[0]}</small></div></div>`).join("")}</div>`)
  };
  return `
 <div class="page-head compact"><div><a class="back" href="#" data-go="transfer" style="margin:0 0 8px">${I("arrow-right")}<span>${t("الرجوع لنقل الكفالة","Back to transfers")}</span></a><h1>${r[0]}</h1><p>${r[7]} · ${r[10]} · ${t("من","from")} ${r[2]} ${t("إلى","to")} ${r[4]==="—"?t("كفيل غير محدد","unset sponsor"):r[4]}</p></div>
 <div class="actions">${r[18]==="trial"?`<button class="btn btn-primary" data-tract="check-circle-2:${r[0]}">${I("check-circle-2")}${t("تأكيد النقل","Confirm transfer")}</button>`:r[18]==="pending"?`<button class="btn btn-primary" data-tract="hourglass:${r[0]}">${I("hourglass")}${t("بدء فترة التجربة","Start trial")}</button>`:`<button class="btn btn-primary" data-tredit="${r[0]}">${I("pencil")}${t("تعديل الطلب","Edit request")}</button>`}<button class="btn btn-ghost">${I("printer")}${t("طباعة","Print")}</button></div></div>
 <div class="showgrid3">
  <aside class="cvside">
   <div class="card idcard"><span class="bigph">${I("arrow-left-right")}</span><b>${r[7]}</b><small class="num">${r[9]}</small>${trStTag(r)}
    <div class="idk">${[[t("العقد","Contract"),`<span class="num">${r[1]}</span>`],[t("أيام التجربة","Trial days"),r[11]||"—"],[t("قيمة العقد","Value"),r[15].toLocaleString("en")],[t("المتبقي","Remaining"),r[17].toLocaleString("en")]].map(x=>`<div><small>${x[0]}</small><b>${x[1]}</b></div>`).join("")}</div>
   </div>
   <div class="card acard"><div class="sec-h">${I("settings-2")}<b>${t("إجراءات الطلب","Request actions")}</b></div>
    <div class="acts2">${[["check-circle-2","تأكيد النقل","Confirm transfer",""],["hourglass","بدء فترة التجربة","Start trial",""],["receipt","إصدار فاتورة","Create invoice",""],["wallet","تسوية مالية","Settlement",""],["printer","طباعة الطلب","Print",""],["x-circle","إلغاء النقل","Cancel transfer","danger"]].map(x=>`<button class="atile ${x[3]}" data-tract="${x[0]}:${r[0]}">${I(x[0])}<span>${t(x[1],x[2])}</span></button>`).join("")}</div>
   </div>
  </aside>
  <div class="cvmain">
   <div class="vtabs">${tabs.map(x=>`<button class="${x[0]===TRSTAB?"on":""}" data-trstab="${x[0]}">${I(x[3])}<span>${t(x[1],x[2])}</span></button>`).join("")}</div>
   <div class="tabbody">${(body[TRSTAB]||body.over)()}</div>
  </div>
 </div>`},
 transferAdd:()=>{
  const ed=TREDIT?trRows.find(x=>x[0]===TREDIT):null;
  const f=(ar,en,nm,val,type,req)=>{let ctl;
   if(type==="select")ctl=`<select>${(Array.isArray(val)?val:[val]).map((o,i)=>`<option${i===0?" selected":""}>${o}</option>`).join("")}</select>`;
   else if(type==="date")ctl=`<input type="date"${val?` value="${val}"`:""}>`;
   else if(type==="ro")ctl=`<input type="text" value="${val}" readonly dir="ltr">`;
   else ctl=`<input type="${type||"text"}" ${ed&&val?`value="${val}"`:`placeholder="${val||""}"`}${["client_phone","national_num","contract_value","paid_amount","remaining_amount","iban"].includes(nm)?' dir="ltr"':""}>`;
   return `<div class="fld"><label>${t(ar,en)}${req?' <i class="rq">*</i>':""}<code>${nm}</code></label>${ctl}</div>`};
  const grid=fs=>`<div class="fgrid">${fs.join("")}</div>`;
  return `
 <div class="page-head"><div><a class="back" href="#" data-go="transfer" style="margin:0 0 8px">${I("arrow-right")}<span>${t("نقل الكفالة","Sponsorship transfer")}</span></a><h1>${ed?t("تعديل طلب النقل","Edit transfer request"):t("طلب نقل كفالة","New transfer request")}${ed?`<span class="tag a hdrtag">${I("pencil")}${t("تعديل","Editing")}</span>`:""}</h1>${ed?`<p><span class="num">${ed[0]}</span> · ${ed[7]} · ${ed[1]}</p>`:`<p>${t("اختر العقد المتاح للنقل، ثم الكفيل الجديد وبيانات التجربة والسداد.","Pick a transferable contract, then the new sponsor, trial and payment data.")}</p>`}</div>
 <div class="actions"><button class="btn btn-ghost" data-go="transfer">${t("إلغاء","Cancel")}</button><button class="btn btn-primary" data-trsave>${I("check")}${ed?t("حفظ التعديلات","Save changes"):t("حفظ الطلب","Save request")}</button></div></div>
 <div class="cvform">
  <aside class="cvopts">
   <div class="card"><div class="sec-h">${I("list-checks")}<b>${t("أقسام النموذج","Form sections")}</b></div>
    <div class="steps">${[["t1","العقد المتاح للنقل","Transferable contract"],["t2","الكفيل الجديد","New sponsor"],["t3","العمل التجريبي والقيمة","Trial & value"],["t4","السداد والمرفقات","Payment & files"]].map((x,i)=>`<button class="step" data-trjump="${x[0]}"><span class="stepn num">${i+1}</span><span>${t(x[1],x[2])}</span></button>`).join("")}</div></div>
   <div class="card"><div class="sec-h">${I("users")}<b>${t("نوع الكفيل الجديد","New sponsor type")}</b></div>
    <div class="modes">${[["existing","user-check","عميل قائم","Existing client"],["new","user-plus","عميل جديد","New client"]].map(x=>`<button class="mode ${TRNEWTYPE===x[0]?"on":""}" data-trnewtype="${x[0]}">${I(x[1])}<span>${t(x[2],x[3])}</span></button>`).join("")}</div></div>
   <div class="card"><div class="sec-h">${I("hourglass")}<b>${t("فترة التجربة","Trial period")}</b></div>
    <div class="actrow sm col"><label>${t("عدد الأيام","Days")}<code>trial_days</code></label><select class="statsel">${["30","60","90"].map(x=>`<option${ed&&String(ed[11])===x?" selected":""}>${x}</option>`).join("")}</select></div>
    <div class="actrow sm col"><label>${t("الفرع","Branch")}<code>branch_id</code></label><select class="statsel">${["الفرع الرئيسي","فرع جدة","فرع الدمام"].map(x=>`<option${ed&&x===ed[23]?" selected":""}>${x}</option>`).join("")}</select></div></div>
  </aside>
  <div class="cvfields">
   <div class="card sec" id="t1"><div class="fs-h"><span class="fs-n num">1</span><b>${t("اختيار العقد المتاح للنقل","Select transferable contract")}</b></div>
    ${grid([f("العقد المتاح للنقل","Transferable contract","contract_id",ed?[ed[1]+" — "+ed[7]]:["CN-2026-0115 — ماريتس دي لا كروز","CN-2026-0113 — كوماري بيريرا","CN-2026-0110 — لاكشمي راج"],"select",1),
      f("الكفيل القديم","Old sponsor","old_sponsor",ed?ed[2]:"يُقرأ من العقد","ro"),
      f("العاملة","Worker","maid_id",ed?ed[7]:"يُقرأ من العقد","ro"),
      f("رقم الجواز","Passport","passport",ed?ed[9]:"يُقرأ من العقد","ro")])}
    <p class="pnote">${I("info")}${t("العقود المتاحة للنقل هي العقود السارية داخل الإيواء أو تحت الضمان.","Transferable contracts are valid contracts in shelter or under warranty.")}</p></div>
   <div class="card sec" id="t2"><div class="fs-h"><span class="fs-n num">2</span><b>${t("العميل / الكفيل الجديد","New client / sponsor")}</b></div>
    ${TRNEWTYPE==="existing"
     ?grid([f("اختيار الكفيل الجديد","Select new sponsor","new_sponsor_id",ed&&ed[4]!=="—"?[ed[4]+" — "+ed[5]]:["سعد المطيري — 1078441220","ريم الغامدي — 1055881003","تركي العتيبي — 1066220774"],"select",1)])
     :grid([f("اسم العميل","Client name","client_name",ed&&ed[4]!=="—"?ed[4]:"الاسم الثلاثي","",1),f("رقم الجوال","Phone","client_phone",ed?ed[6]:"+966 5X XXX XXXX","",1),f("رقم الهوية","National ID","national_num",ed&&ed[5]!=="—"?ed[5]:"1XXXXXXXXX")])}</div>
   <div class="card sec" id="t3"><div class="fs-h"><span class="fs-n num">3</span><b>${t("العمل التجريبي وقيمة العقد","Trial work & contract value")}</b></div>
    ${grid([f("عدد أيام العمل التجريبي","Trial days","trial_days",["30","60","90"],"select",1),
      f("تاريخ بداية العمل التجريبي","Trial start date","trial_started_at",ed?ed[12]:"","date",1),
      f("قيمة العقد","Contract value","contract_value",ed?ed[15].toLocaleString("en"):"14,000","",1),
      f("المبلغ المدفوع","Paid amount","paid_amount",ed?ed[16].toLocaleString("en"):"0","",1),
      f("المبلغ المتبقي","Remaining amount","remaining_amount",ed?ed[17].toLocaleString("en"):"0","ro")])}</div>
   <div class="card sec" id="t4"><div class="fs-h"><span class="fs-n num">4</span><b>${t("السداد والمرفقات","Payment & attachments")}</b></div>
    ${grid([f("طريقة الدفع","Payment method","payment_type",ed&&ed[21]?[ed[21]].concat(trPayTypes.filter(x=>x!==ed[21])):trPayTypes,"select",1),
      f("حالة الدفع","Payment status","payment_status",[t("مسدّد","Settled"),t("جزئي","Partial"),t("غير مسدّد","Unpaid")],"select",1),
      f("البنك / حساب السداد","Bank / receipt account","receipt_account_id",trAccounts,"select",1)])}
    <div class="fld full"><label>${t("إيصال الدفع","Payment receipt")}<code>payment_receipt</code></label><div class="ctl file">${I("upload")}<span>${t("اختر الملف","Select file")}</span></div></div>
    <div class="fld full"><label>${t("مرفقات إضافية","Additional attachments")}<code>attachments</code></label><div class="ctl file">${I("upload")}<span>${t("اختر الملفات — يمكن تحديد أكثر من ملف","Select files — multiple allowed")}</span></div></div>
    <div class="fld full"><label>${t("ملاحظات إضافية","Additional notes")}<code>notes</code></label><textarea rows="3"></textarea></div></div>
   <div class="formbar"><button class="btn btn-ghost" data-go="transfer">${t("إلغاء","Cancel")}</button><button class="btn btn-primary" data-trsave>${I("check")}${ed?t("حفظ التعديلات","Save changes"):t("حفظ الطلب","Save request")}</button></div>
  </div>
 </div>`},
 transferOverview:()=>{
  const trBreak={pending:[7,88600,42200,46400],trial:[2,26500,19500,7000],transferred:[6,79700,79700,0],failed:[2,20000,6800,13200]};
  const byStatus=trSt.map(s=>[s,trBreak[s[0]][0]]);
  const total=trTotal("all");
  return `
 <div class="page-head"><div><a class="back" href="#" data-go="transfer" style="margin:0 0 8px">${I("arrow-right")}<span>${t("نقل الكفالة","Sponsorship transfer")}</span></a><h1>${t("نظرة عامة على الحالات","Status overview")}</h1><p>${t("توزيع طلبات نقل الكفالة على الحالات، وأسباب الفشل، والمؤشرات المالية.","Distribution of transfer requests across statuses, failure reasons and financial indicators.")}</p></div>
 <div class="actions"><button class="btn btn-ghost">${I("file-spreadsheet")}Excel</button><button class="btn btn-ghost">${I("printer")}${t("طباعة","Print")}</button></div></div>
 <div class="pipe">${[["إجمالي الطلبات","Total requests","layers",17,"run"],["نسبة النقل الناجح","Success rate","trending-up","35%","post"],["نسبة الفشل","Failure rate","trending-down","12%","closed"],["متوسط أيام التجربة","Avg trial days","hourglass",65,"run"]].map(x=>`<div class="pg ${x[4]}">${I(x[2])}<span class="pg-t">${t(x[0],x[1])}</span><b class="num">${x[3]}</b></div>`).join("")}</div>
 <div class="grid-2">
  ${sect("pie-chart","التوزيع حسب الحالة","By status",`<div style="padding:0 18px 18px">${byStatus.map(([s,n])=>`<div class="hbar"><span class="t">${t(s[1],s[2])}</span><span class="track"><i style="width:${total?n/total*100:0}%"></i></span><span class="n num">${n}</span></div>`).join("")}</div>`)}
  ${sect("x-circle","أسباب الفشل","Failure reasons",`<div style="padding:0 18px 18px">${[[trFailReasons[1],1],[trFailReasons[0],1],[trFailReasons[2],0],[trFailReasons[3],0],[trFailReasons[4],0]].map(x=>`<div class="hbar"><span class="t">${x[0]}</span><span class="track"><i style="width:${x[1]*50}%"></i></span><span class="n num">${x[1]}</span></div>`).join("")}</div>`)}
 </div>
 ${sect("wallet","المؤشرات المالية","Financial indicators",`<div class="ovgrid four">${[["إجمالي قيم العقود","Contracts value","214,800"],["المحصّل","Collected","148,200"],["المتبقي","Remaining","66,600"],["مبالغ للتسوية (فشل)","To settle (failed)","13,200"]].map(x=>`<div class="ovc"><span class="ovl">${t(x[0],x[1])}</span><b class="ovv num">${x[2]}</b><small class="ovm">${t("ر.س","SAR")}</small></div>`).join("")}</div>`)}
 ${sect("table","الطلبات حسب الحالة","Requests by status",`<div class="tw"><table><thead><tr><th>${t("الحالة","Status")}</th><th>${t("عدد الطلبات","Requests")}</th><th>${t("قيمة العقود","Contracts value")}</th><th>${t("المحصّل","Collected")}</th><th>${t("المتبقي","Remaining")}</th></tr></thead><tbody>
 ${byStatus.map(([s])=>{const b=trBreak[s[0]];return `<tr><td><span class="st tag ${s[3]}"><i></i>${t(s[1],s[2])}</span></td><td class="num">${b[0]}</td><td class="num">${b[1].toLocaleString("en")}</td><td class="num">${b[2].toLocaleString("en")}</td><td class="num">${b[3].toLocaleString("en")}</td></tr>`}).join("")}
 </tbody></table></div>`)}`}
};
let TREDIT=null,TRNEWTYPE="existing";
Object.assign(T,{transfer:{ar:"نقل الكفالة",en:"Sponsorship transfer"},transferShow:{ar:"تفاصيل طلب النقل",en:"Transfer details"},transferAdd:{ar:"طلب نقل كفالة",en:"Transfer request"},transferOverview:{ar:"نظرة عامة على الحالات",en:"Status overview"}});
Object.assign(screens,transferScreens);
function trPlace(id){requestAnimationFrame(()=>{const btn=document.querySelector(`[data-trmenu="${id}"]`),menu=document.querySelector(`[data-trmenu-for="${id}"]`);if(!btn||!menu)return;const b=btn.getBoundingClientRect();document.body.appendChild(menu);menu.classList.remove("hidden");menu.style.position="fixed";menu.style.zIndex="200";menu.style.insetInlineEnd="auto";const h=menu.offsetHeight,w=menu.offsetWidth;let top=b.bottom+6;if(top+h>innerHeight-8)top=Math.max(8,b.top-h-6);let left=document.body.dir==="rtl"?b.left:b.right-w;left=Math.min(Math.max(8,left),innerWidth-w-8);menu.style.top=top+"px";menu.style.left=left+"px";lucide.createIcons({attrs:{"stroke-width":1.8}})})}
function trModal(kind,r){let ov=document.getElementById("trOv");if(!ov){ov=document.createElement("div");ov.id="trOv";ov.className="ov";document.body.appendChild(ov)}
 const body=kind==="settle"
  ?`<div class="fgrid two">
    <div class="fld"><label>${t("نوع التسوية","Settlement type")}<code>settlement_type</code></label><select><option>${t("استرجاع مبلغ للعميل","Refund to client")}</option><option>${t("تصفير المتبقي","Zero out remaining")}</option><option>${t("تحويل الرصيد لعقد آخر","Move balance to another contract")}</option></select></div>
    <div class="fld"><label>${t("مبلغ التسوية","Settlement amount")}<code>settlement_amount</code></label><input type="text" value="${r[17].toLocaleString("en")}" dir="ltr"></div>
    <div class="fld"><label>${t("البنك / حساب الصرف","Bank / payment account")} <i class="rq">*</i><code>payment_account_id</code></label><select>${trAccounts.map(x=>`<option>${x}</option>`).join("")}</select></div>
    <div class="fld"><label>${t("آيبان العميل","Client IBAN")} <i class="rq">*</i><code>iban</code></label><input type="text" placeholder="SA00 0000 0000 0000 0000 0000" dir="ltr"></div>
    <div class="fld"><label>${t("مبلغ تصفير المتبقي","Zero-out amount")}<code>zero_amount</code></label><input type="text" placeholder="0" dir="ltr"></div>
    <div class="fld"><label>${t("سبب الفشل","Failure reason")}<code>failure_reason</code></label><select>${trFailReasons.map(x=>`<option${x===r[20]?" selected":""}>${x}</option>`).join("")}</select></div>
   </div><div class="fld full"><label>${t("مرفق الآيبان","IBAN attachment")} <i class="rq">*</i><code>iban_attachment</code></label><div class="ctl file">${I("upload")}<span>${t("اختر الملف","Select file")}</span></div></div>`
  :`<div class="fgrid two">
    <div class="fld"><label>${t("تاريخ الفاتورة","Invoice date")}<code>invoice_date</code></label><input type="date" value="2026-08-19"></div>
    <div class="fld"><label>${t("وصف الفاتورة","Invoice description")}<code>invoice_description</code></label><input type="text" value="${t("نقل كفالة عاملة منزلية","Domestic worker sponsorship transfer")}"></div>
    <div class="fld"><label>${t("قيمة العقد","Contract value")}<code>contract_value</code></label><input type="text" value="${r[15].toLocaleString("en")}" readonly dir="ltr"></div>
    <div class="fld"><label>${t("الخصم","Discount")}<code>discount</code></label><input type="text" placeholder="0" dir="ltr"></div>
   </div><p class="pnote">${I("info")}${t("تُرحَّل الفاتورة تلقائياً إلى فواتير العقود والقيود المالية.","The invoice is posted to contract invoices and journal entries automatically.")}</p>`;
 ov.innerHTML=`<div class="modal"><div class="modal-h"><div><h3>${kind==="settle"?t("تسوية مالية","Financial settlement"):t("إصدار فاتورة نقل كفالة","Create transfer invoice")}</h3><p class="ms">${r[0]} · ${r[7]} · ${r[1]}</p></div><button class="icon-btn" data-trclose>${I("x")}</button></div>
 <div class="modal-b">${body}</div>
 <div class="modal-f"><button class="btn btn-ghost" data-trclose>${t("إلغاء","Cancel")}</button><button class="btn btn-primary" data-trdone="${kind}">${I("check")}${kind==="settle"?t("تنفيذ التسوية","Apply settlement"):t("إصدار الفاتورة","Issue invoice")}</button></div></div>`;
 ov.classList.remove("hidden");lucide.createIcons({attrs:{"stroke-width":1.8}})}
document.addEventListener("click",e=>{
 const tb=e.target.closest("[data-trtab]");if(tb){TRTAB=tb.dataset.trtab;TRSTATUS="";render();return}
 const st=e.target.closest("[data-trstat]");if(st){const k=st.dataset.trstat;TRSTATUS=trSt.some(x=>x[0]===k)?(TRSTATUS===k?"":k):"";render();return}
 const mf=e.target.closest("[data-trmore]");if(mf){TRMORE=!TRMORE;render();return}
 const sh=e.target.closest("[data-trshow]");if(sh){TRID=sh.dataset.trshow;TRSTAB="over";cur="transferShow";render();window.scrollTo(0,0);return}
 const stb=e.target.closest("[data-trstab]");if(stb){TRSTAB=stb.dataset.trstab;render();window.scrollTo(0,0);return}
 const ed=e.target.closest("[data-tredit]");if(ed){TREDIT=ed.dataset.tredit;cur="transferAdd";render();window.scrollTo(0,0);return}
 const nt=e.target.closest("[data-trnewtype]");if(nt){TRNEWTYPE=nt.dataset.trnewtype;render();return}
 const jp=e.target.closest("[data-trjump]");if(jp){const el=document.getElementById(jp.dataset.trjump);if(el)window.scrollTo({top:el.offsetTop-90,behavior:"smooth"});return}
 const sv=e.target.closest("[data-trsave]");if(sv){const back=TREDIT;TREDIT=null;if(back){TRID=back;cur="transferShow"}else cur="transfer";render();window.scrollTo(0,0);clToast("تم حفظ طلب نقل الكفالة","Transfer request saved");return}
 const inv=e.target.closest("[data-trinvoice]");if(inv){trModal("invoice",trRows.find(x=>x[0]===inv.dataset.trinvoice));return}
 const stl=e.target.closest("[data-trsettle]");if(stl){trModal("settle",trRows.find(x=>x[0]===stl.dataset.trsettle));return}
 const mn=e.target.closest("[data-trmenu]");if(mn){const id=mn.dataset.trmenu;TRMENU=TRMENU===id?null:id;render();if(TRMENU)trPlace(TRMENU);return}
 const ac=e.target.closest("[data-tract]");
 if(ac){const [k,id]=ac.dataset.tract.split(":");const r=trRows.find(x=>x[0]===id);TRMENU=null;clKillMenus();
  if(k==="receipt"){trModal("invoice",r);return}
  if(k==="wallet"){trModal("settle",r);return}
  if(k==="check-circle-2"&&!r[25]){r[18]="transferred";r[13]=r[13]||"2026-08-19";render();clToast("تم تأكيد نقل الكفالة","Sponsorship transfer confirmed");return}
  if(k==="hourglass"&&!r[25]){r[18]="trial";r[11]=r[11]||30;r[12]=r[12]||"2026-08-19";r[13]=r[13]||"2026-09-18";r[14]=30;render();clToast("بدأت فترة التجربة","Trial period started");return}
  if(k==="x-circle"&&!r[25]){r[18]="failed";r[20]=r[20]||trFailReasons[0];render();clToast("تم إلغاء النقل وإعادة العقد إلى الإيواء","Transfer cancelled, contract returned to shelter");return}
  if(k==="file-plus"&&r[25]){r[25]="approved";render();clToast("تم تحويل طلب الموقع إلى طلب نقل","Web request converted to a transfer request");return}
  if(k==="x-circle"&&r[25]){r[25]="rejected";render();clToast("تم رفض طلب الموقع","Web request rejected");return}
  if(k==="eye"){TRID=id;TRSTAB="over";cur="transferShow";render();window.scrollTo(0,0);return}
  clToast("تم تنفيذ الإجراء","Action completed");return}
 const cl=e.target.closest("[data-trclose]"),dn=e.target.closest("[data-trdone]");
 if(cl||dn){const ov=document.getElementById("trOv");if(ov)ov.classList.add("hidden");if(dn)clToast(dn.dataset.trdone==="settle"?"تم تنفيذ التسوية المالية":"تم إصدار الفاتورة",dn.dataset.trdone==="settle"?"Settlement applied":"Invoice issued");return}
 if(TRMENU&&!e.target.closest(".kebab")&&!e.target.closest(".amenu")){TRMENU=null;clKillMenus();render()}
 const gg=e.target.closest("[data-go]");if(gg&&gg.dataset.go!=="transferAdd")TREDIT=null;
});
document.addEventListener("input",e=>{if(e.target.matches("[data-trq]")){TRQ=e.target.value;const p=e.target.selectionStart;render();const n=document.querySelector("[data-trq]");if(n){n.focus();n.setSelectionRange(p,p)}}});
document.addEventListener("change",e=>{
 if(e.target.matches("[data-trstatus]")){TRSTATUS=e.target.value;render()}
 if(e.target.matches("[data-trpay]")){TRPAY=e.target.value;render()}
});
