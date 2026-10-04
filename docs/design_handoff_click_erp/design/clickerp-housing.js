/* Housing (Shelter) — grounded in admin/ingaz/ShelterContract/{index,outside,available-for-transfer,deportation-stage,deported,general-shelter,create,show}.blade.php */
const hsTabs=[
 ["inside","داخل الإيواء","Inside shelter","home",64],
 ["outside","خارج الإيواء","Outside shelter","log-out",5],
 ["transfer","متاحة للنقل","Available for transfer","arrow-left-right",13],
 ["depstage","مرحلة الترحيل","Departure stage","plane-takeoff",9],
 ["deported","تم ترحيلها","Deported","check-circle-2",21],
 ["general","الإيواء العام","General shelter","building",90]];
const hsPlaces=["إيواء الرياض — المبنى أ","إيواء الرياض — المبنى ب","إيواء جدة","إيواء الدمام"];
const hsSources=["من المطار","من العميل","من مكتب خارجي","تحويل بين الإيواءات"];
const hsTags=["جاهزة للنقل","تحتاج فحص طبي","بانتظار الإقامة","شكوى مفتوحة","استلام فوري"];
const hsDesire=[["work","ترغب بالعمل","Willing to work","g"],["nowork","لا ترغب بالعمل","Not willing","r"],["undecided","لم تحدد","Undecided","a"]];
const hsWar=[["within_guarantee","داخل الضمان","Within guarantee","g"],["expired_guarantee","خارج الضمان","Expired guarantee","r"]];
const hsOutReasons=[["rental_delivery","تسليم عقد إيجار","Rental delivery","n"],["transfer_delivery","تسليم نقل خدمات","Transfer delivery","n"],["escape_report","بلاغ هروب","Escape report","r"],["other","أخرى","Other",""]];
const hsDepSteps=[["exit_visa","تأشيرة الخروج","Exit visa"],["ticket","التذكرة","Ticket"],["clearance","إخلاء الطرف","Clearance"],["embassy","السفارة","Embassy"],["handover","التسليم للمطار","Airport handover"]];
/* 0 no 1 maidAr 2 maidEn 3 passport 4 client 5 nat 6 job 7 place 8 arrival 9 entry 10 kingdomDays 11 shelterDays 12 warranty 13 desire 14 instant 15 tags 16 iqama 17 medical 18 source 19 branch 20 outReason 21 outAt 22 outDetails 23 depDone 24 depDate 25 depBy */
const hsRows=[
 ["SH-2026-0142","ماريتس دي لا كروز","Marites Dela Cruz","P7741203","منيرة الشهري","الفلبين","عاملة منزلية","إيواء الرياض — المبنى أ","2026-08-16","2026-08-16",3,3,"within_guarantee","work",1,["جاهزة للنقل","استلام فوري"],1,1,"من المطار","الفرع الرئيسي","","","",0,"",""],
 ["SH-2026-0141","كوماري بيريرا","Kumari Perera","P3391077","نورة الحربي","سريلانكا","عاملة منزلية","إيواء الرياض — المبنى أ","2026-08-02","2026-08-04",17,15,"within_guarantee","undecided",0,["تحتاج فحص طبي"],1,0,"من العميل","الفرع الرئيسي","","","",0,"",""],
 ["SH-2026-0140","غريس ناكاتو","Grace Nakato","P8812440","عبدالله الدوسري","أوغندا","عاملة منزلية","إيواء جدة","2026-05-02","2026-07-28",109,22,"expired_guarantee","nowork",0,["شكوى مفتوحة"],1,1,"من العميل","فرع جدة","","","",0,"",""],
 ["SH-2026-0139","سري واهيوني","Sri Wahyuni","P5520914","شركة نجد للمقاولات","إندونيسيا","طباخة","إيواء الدمام","2026-07-21","2026-07-22",29,28,"within_guarantee","work",1,["جاهزة للنقل"],1,1,"من مكتب خارجي","فرع الدمام","","","",0,"",""],
 ["SH-2026-0138","عائشة خان","Ayesha Khan","P6620355","هيا العنزي","بنغلاديش","عاملة منزلية","إيواء الرياض — المبنى ب","2026-04-19","2026-06-11",122,68,"expired_guarantee","nowork",0,["بانتظار الإقامة"],0,1,"من العميل","الفرع الرئيسي","","","",0,"",""],
 ["SH-2026-0137","رينا أستوتي","Rina Astuti","P9903221","فيصل القحطاني","إندونيسيا","طباخة","إيواء الرياض — المبنى أ","2026-06-14","2026-08-01",66,18,"within_guarantee","work",1,["جاهزة للنقل","استلام فوري"],1,1,"تحويل بين الإيواءات","الفرع الرئيسي","","","",0,"",""],
 ["SH-2026-0131","نيلانثي سيلفا","Nilanthi Silva","P4408123","شركة الوفاء الطبية","سريلانكا","ممرضة منزلية","إيواء الدمام","2026-03-11","2026-05-20",161,0,"expired_guarantee","nowork",0,[],1,1,"من العميل","فرع الدمام","rental_delivery","2026-08-10","تم تسليمها بعقد إيجار رقم RC-2026-0044",0,"",""],
 ["SH-2026-0129","جينيفر لوبيز","Jenifer Lopez","P1129884","مؤسسة البيان التجارية","الفلبين","عاملة منزلية","إيواء جدة","2026-01-14","2026-04-02",217,0,"expired_guarantee","nowork",0,[],1,1,"من العميل","فرع جدة","escape_report","2026-08-06","بلاغ هروب رقم 4412 — تم إبلاغ الجهات",0,"",""],
 ["SH-2026-0118","سهام الشاذلي","Siham Alshathli","P2204119","—","كينيا","عاملة منزلية","إيواء الرياض — المبنى ب","2025-12-02","2026-02-14",260,186,"expired_guarantee","nowork",0,["شكوى مفتوحة"],1,1,"من العميل","الفرع الرئيسي","","","",0,"",""],
 ["SH-2026-0110","لاكشمي راج","Lakshmi Raj","P7712004","—","الهند","عاملة منزلية","إيواء جدة","2025-11-08","2026-01-20",284,211,"expired_guarantee","nowork",0,[],1,1,"من العميل","فرع جدة","","","",1,"2026-08-12","سارة القحطاني"]];
const hsDep={"SH-2026-0118":3,"SH-2026-0138":1,"SH-2026-0140":2};
let HSTAB="inside",HSQ="",HSPLACE="",HSWAR="",HSDESIRE="",HSTAG="",HSMORE=false,HSID="SH-2026-0142",HSSTAB="over",HSMENU=null,HSSTATS=true;
const hsIn=r=>!r[20]&&!r[23];
const hsSet=()=>{switch(HSTAB){
 case "outside":return hsRows.filter(r=>r[20]&&!r[23]);
 case "transfer":return hsRows.filter(r=>hsIn(r)&&r[15].includes("جاهزة للنقل"));
 case "depstage":return hsRows.filter(r=>hsDep[r[0]]!=null&&!r[23]);
 case "deported":return hsRows.filter(r=>r[23]);
 case "general":return hsRows;
 default:return hsRows.filter(hsIn)}};
const hsFiltered=()=>hsSet().filter(r=>(!HSPLACE||r[7]===HSPLACE)&&(!HSWAR||r[12]===HSWAR)&&(!HSDESIRE||r[13]===HSDESIRE)&&(!HSTAG||r[15].includes(HSTAG))&&(!HSQ||(r[0]+r[1]+r[2]+r[3]+r[4]).toLowerCase().includes(HSQ.toLowerCase())));
const hsWarTag=r=>{const w=hsWar.find(x=>x[0]===r[12]);return `<span class="tag ${w[3]}">${t(w[1],w[2])}</span>`};
const hsDesireTag=r=>{const d=hsDesire.find(x=>x[0]===r[13])||hsDesire[2];return `<span class="st tag ${d[3]}"><i></i>${t(d[1],d[2])}</span>`};
const hsOutTag=r=>{const o=hsOutReasons.find(x=>x[0]===r[20])||hsOutReasons[3];return `<span class="tag ${o[3]}">${t(o[1],o[2])}</span>`};
const hsTagChips=r=>r[15].length?`<div class="mbadges row wrap">${r[15].map(x=>`<span class="tag">${x}</span>`).join("")}</div>`:`<span class="empty">—</span>`;
const hsTotal=k=>(hsTabs.find(x=>x[0]===k)||[,,,,0])[4];
const hsStats=()=>{
 const T0=hsTotal(HSTAB);
 switch(HSTAB){
  case "outside":return [["","الإجمالي","Total","log-out","n",T0],["rental_delivery","تسليم إيجار","Rental delivery","repeat","n",2],["transfer_delivery","تسليم نقل خدمات","Transfer delivery","arrow-left-right","n",1],["escape_report","بلاغات الهروب","Escape reports","alert-triangle","r",1],["other","أخرى","Other","more-horizontal","",1]];
  case "transfer":return [["","متاحة للنقل","Available","arrow-left-right","n",T0],["instant","استلام فوري","Instant receipt","zap","g",8],["work","ترغب بالعمل","Willing to work","thumbs-up","g",11],["within_guarantee","داخل الضمان","Within guarantee","shield-check","g",9]];
  case "depstage":return [["","في مرحلة الترحيل","In departure","plane-takeoff","a",T0],["done","أكملت الإجراءات","Steps completed","list-checks","g",3],["late","متعثرة","Stalled","alarm-clock","r",2],["escape","بلاغ هروب","Escape report","alert-triangle","r",1]];
  case "deported":return [["","تم ترحيلها","Deported","check-circle-2","g",T0],["month","هذا الشهر","This month","calendar","n",4],["expired","خارج الضمان","Expired guarantee","shield-off","r",19]];
  case "general":return [["","إجمالي السجلات","All records","building","n",T0],["inside","داخل الإيواء","Inside","home","n",hsTotal("inside")],["outside","خارج الإيواء","Outside","log-out","a",hsTotal("outside")],["deported","مرحّلة","Deported","plane-takeoff","",hsTotal("deported")]];
  default:return [["","داخل الإيواء","Inside shelter","home","n",T0],["instant","متاحة للاستلام الفوري","Instant receipt","zap","g",21],["work","ترغب بالعمل","Willing to work","thumbs-up","g",38],["nowork","لا ترغب بالعمل","Not willing","thumbs-down","r",17],["within_guarantee","داخل الضمان","Within guarantee","shield-check","g",41],["expired_guarantee","خارج الضمان","Expired guarantee","shield-off","r",23],["iqama","لديها إقامة","Has residency","id-card","n",57],["medical","تم الفحص الطبي","Medical done","stethoscope","n",49],["ready","جاهزة للنقل","Ready to move","arrow-left-right","n",13]];
 }};
const hsMenuItems=[["arrow-left-right","نقل بين الإيواءات","Transfer between shelters"],["zap","تبديل الاستلام الفوري","Toggle instant receipt"],["thumbs-up","تغيير رغبة العمل","Change work desire"],["stethoscope","تسجيل فحص طبي","Record medical exam"],["utensils","تسجيل مصروف إعاشة","Log living expense"],["message-square","تسجيل شكوى","Log complaint"],["plane-takeoff","إدخال مرحلة الترحيل","Move to departure"],["log-out","تسجيل خروج من الإيواء","Record shelter exit"],["file-text","نموذج الاستلام","Receipt form"],["printer","طباعة","Print"]];
const hsActs=r=>`<div class="rowacts"><button class="icon-btn pri" data-hsshow="${r[0]}" title="${t("تفاصيل الإيواء","Housing details")}">${I("eye")}</button><button class="icon-btn pri" data-hsedit="${r[0]}" title="${t("تعديل","Edit")}">${I("pencil")}</button><span class="kebab"><button class="icon-btn" data-hsmenu="${r[0]}" title="${t("المزيد","More")}">${I("more-horizontal")}</button><div class="amenu${HSMENU===r[0]?"":" hidden"}" data-hsmenu-for="${r[0]}">${hsMenuItems.map(x=>`<button data-hsact="${x[0]}:${r[0]}"${x[0]==="log-out"?' class="danger"':""}>${I(x[0])}<span>${t(x[1],x[2])}</span></button>`).join("")}</div></span></div>`;
const hsWorkerCell=r=>`<div class="wcell"><span class="av">${I("user-round")}</span><div><b>${r[1]}</b><small>${r[2]}</small><small class="num">${t("جواز","P")} ${r[3]}</small></div></div>`;
const hsHead=()=>{
 const cols={
  inside:[["العاملة","Worker"],["العميل","Client"],["الجنسية والمهنة","Nationality & job"],["مكان الإيواء","Shelter place"],["الوصول والإدخال","Arrival & entry"],["المدد","Durations"],["ملخص الحالة","Status summary"],["التصنيفات السريعة","Quick tags"],["الاستلام الفوري","Instant receipt"]],
  outside:[["العاملة","Worker"],["سبب الخروج","Exit reason"],["تاريخ الخروج","Exit date"],["تفاصيل البلاغ","Report details"],["مكان الإيواء السابق","Previous shelter"],["المرجع","Reference"]],
  transfer:[["العاملة","Worker"],["العميل","Client"],["الجنسية","Nationality"],["الإيواء","Shelter"],["تاريخ الوصول","Arrival date"],["حالة الضمان","Guarantee"],["رغبة العمل","Work desire"],["الاستلام الفوري","Instant receipt"]],
  depstage:[["العاملة","Worker"],["العميل","Client"],["الجنسية","Nationality"],["الإيواء","Shelter"],["تاريخ الوصول","Arrival date"],["حالة الإجراءات","Procedures status"],["حالة الضمان","Guarantee"]],
  deported:[["العاملة","Worker"],["العميل","Client"],["الجنسية","Nationality"],["تاريخ الترحيل","Departure date"],["نُفِّذ بواسطة","Completed by"],["الحالة","Status"]],
  general:[["العاملة","Worker"],["نوع العاملة","Worker type"],["الجنسية","Nationality"],["مكان الإيواء","Shelter place"],["مصدر الاستلام","Receipt source"],["حالة الضمان","Guarantee"],["الوصول والعودة","Arrival & return"]]}[HSTAB];
 return `<tr><th class="chk"><input type="checkbox"></th><th>${t("رقم عقد الإيواء","Shelter contract")}</th>${cols.map(c=>`<th>${t(c[0],c[1])}</th>`).join("")}<th class="act">${t("إجراءات","Actions")}</th></tr>`;
};
const hsCells=r=>{
 const g=(b,...s)=>`<div class="gcell"><b>${b}</b>${s.filter(Boolean).map(x=>`<small>${x}</small>`).join("")}</div>`;
 const cols={
  inside:[hsWorkerCell(r),g(r[4]==="—"?`<span class="empty">${t("بدون عميل","No client")}</span>`:r[4],r[19]),g(r[5],r[6]),g(r[7],r[18]),g(`<span class="num">${r[8]}</span>`,`${t("إدخال","entry")} ${r[9]}`),g(`<span class="num">${r[11]}</span> ${t("يوم إيواء","d in shelter")}`,`${t("في المملكة","in KSA")} ${r[10]} ${t("يوم","d")}`),`<div class="gcell">${hsWarTag(r)}${hsDesireTag(r)}<small>${r[16]?t("إقامة ✓","Iqama ✓"):t("بلا إقامة","No iqama")} · ${r[17]?t("فحص ✓","Medical ✓"):t("بلا فحص","No medical")}</small></div>`,hsTagChips(r),r[14]?`<span class="tag g">${t("متاحة","Available")}</span>`:`<span class="tag">${t("غير متاحة","Not available")}</span>`],
  outside:[hsWorkerCell(r),hsOutTag(r),`<span class="num">${r[21]}</span>`,`<div class="gcell x" style="white-space:normal;max-width:280px">${r[22]||`<span class="empty">—</span>`}</div>`,r[7],r[20]==="escape_report"?`<button class="lnk">${I("alert-triangle")}<span>${t("بلاغ 4412","Report 4412")}</span></button>`:`<button class="lnk" data-go="contracts">${I("file-signature")}<span class="num">CN-2026-0111</span></button>`],
  transfer:[hsWorkerCell(r),r[4]==="—"?`<span class="empty">—</span>`:r[4],r[5],r[7],`<span class="num">${r[8]}</span>`,hsWarTag(r),hsDesireTag(r),r[14]?`<span class="tag g">${t("نعم","Yes")}</span>`:`<span class="tag">${t("لا","No")}</span>`],
  depstage:[hsWorkerCell(r),r[4]==="—"?`<span class="empty">—</span>`:r[4],r[5],r[7],`<span class="num">${r[8]}</span>`,(()=>{const d=hsDep[r[0]]||0;return `<div class="depcell"><span class="dpb"><i style="width:${d/hsDepSteps.length*100}%"></i></span><small>${t(`${d} من ${hsDepSteps.length} إجراءات`,`${d} of ${hsDepSteps.length} steps`)} · ${t(hsDepSteps[Math.min(d,hsDepSteps.length-1)][1],hsDepSteps[Math.min(d,hsDepSteps.length-1)][2])}</small></div>`})(),hsWarTag(r)],
  deported:[hsWorkerCell(r),r[4]==="—"?`<span class="empty">—</span>`:r[4],r[5],`<span class="num">${r[24]}</span>`,r[25],`<span class="st tag g"><i></i>${t("تم الترحيل","Deported")}</span>`],
  general:[hsWorkerCell(r),g(r[6],r[20]?t("خارج الإيواء","Outside"):r[23]?t("مرحّلة","Deported"):t("داخل الإيواء","Inside")),r[5],r[7],r[18],hsWarTag(r),g(`<span class="num">${r[8]}</span>`,r[21]?`${t("خروج","exit")} ${r[21]}`:t("لم تخرج","Not exited"))]}[HSTAB];
 return `<tr><td class="chk"><input type="checkbox"></td><td><div class="gcell"><b class="num">${r[0]}</b><small>${r[19]}</small></div></td>${cols.map(c=>`<td>${c}</td>`).join("")}<td class="act">${hsActs(r)}</td></tr>`;
};
const housingScreens={
 housing:()=>{
  const rows=hsFiltered(),stats=hsStats(),tab=hsTabs.find(x=>x[0]===HSTAB);
  const colCount=hsHead().split("<th").length-1;
  return `
 <div class="page-head"><div><h1>${t("إدارة الإيواء","Housing management")}</h1><p>${t("عقود الإيواء من الاستلام حتى التسليم أو الترحيل — داخل الإيواء، خارجه، المتاحة للنقل، ومراحل الترحيل.","Shelter contracts from receipt to delivery or departure — inside, outside, available for transfer, and departure stages.")}</p></div>
 <div class="actions"><button class="btn btn-ghost" data-hsexpense>${I("utensils")}${t("مصروف إعاشة","Living expense")}</button><button class="btn btn-ghost">${I("file-spreadsheet")}Excel</button><button class="btn btn-primary" data-go="housingAdd">${I("plus")}${t("إضافة عقد إيواء","Add shelter contract")}</button></div></div>
 <div class="seg tabs">${hsTabs.map(x=>`<button class="${x[0]===HSTAB?"on":""}" data-hstab="${x[0]}">${t(x[1],x[2])}<b class="segb num">${x[4]}</b></button>`).join("")}</div>
 <div class="pipe">${stats.slice(0,4).map(x=>`<button class="pg ${x[4]==="g"?"post":x[4]==="r"?"closed":"run"} ${x[0]&&((x[0]===HSWAR)||(x[0]===HSDESIRE))?"on":""}" data-hsstat="${x[0]}">${I(x[3])}<span class="pg-t">${t(x[1],x[2])}</span><b class="num">${x[5]}</b></button>`).join("")}${stats.length>4?`<button class="pg detail ${HSSTATS?"on":""}" data-hsstats>${I(HSSTATS?"chevron-up":"bar-chart-3")}<span class="pg-t">${t(HSSTATS?"إخفاء التفصيل":"تفصيل الحالة",HSSTATS?"Hide detail":"Status detail")}</span></button>`:""}</div>
 ${stats.length>4&&HSSTATS?`<div class="card sec statwrap"><div class="sec-h">${I("clipboard-list")}<b>${t("تفصيل حالة العاملات داخل الإيواء","Worker status detail")}</b><span class="ms">${t("انقر للتصفية","Click to filter")}</span></div>
  <div class="pipeline"><div class="prow"><span class="prow-t">${t("الضمان والرغبة","Guarantee & desire")}</span><div class="pchips">${stats.slice(4).map(x=>`<button class="pchip ${x[0]===HSWAR||x[0]===HSDESIRE?"on":""}" data-hsstat="${x[0]}">${I(x[3])}<span>${t(x[1],x[2])}</span><b class="num">${x[5]}</b></button>`).join("")}</div></div></div></div>`:""}
 <div class="fbar">
  <div class="fb grow"><label>${t("بحث","Search")}</label><div class="inp">${I("search")}<input data-hsq value="${HSQ}" placeholder="${t("رقم عقد الإيواء، اسم العاملة، الجواز، العميل","Shelter no., worker, passport, client")}"></div></div>
  <div class="fb"><label>${t("مكان الإيواء","Shelter place")}</label><select data-hsplace><option value="">${t("كل الأماكن","All places")}</option>${hsPlaces.map(x=>`<option${HSPLACE===x?" selected":""}>${x}</option>`).join("")}</select></div>
  ${HSTAB==="outside"?`<div class="fb"><label>${t("سبب الخروج","Exit reason")}</label><select><option>${t("الكل","All")}</option>${hsOutReasons.map(x=>`<option>${t(x[1],x[2])}</option>`).join("")}</select></div>`:`<div class="fb"><label>${t("حالة الضمان","Guarantee status")}</label><select data-hswar><option value="">${t("الكل","All")}</option>${hsWar.map(x=>`<option value="${x[0]}"${HSWAR===x[0]?" selected":""}>${t(x[1],x[2])}</option>`).join("")}</select></div>`}
  <div class="fb"><label>${t("الجنسية","Nationality")}</label><select><option>${t("كل الجنسيات","All nationalities")}</option>${["الفلبين","إندونيسيا","سريلانكا","أوغندا","بنغلاديش","الهند","كينيا"].map(x=>`<option>${x}</option>`).join("")}</select></div>
  <button class="btn btn-ghost" data-hsmore>${I(HSMORE?"chevron-up":"sliders-horizontal")}${t(HSMORE?"إخفاء":"فلاتر أكثر",HSMORE?"Hide":"More filters")}</button>
  <span class="empty">${t(`${rows.length} نتيجة`,`${rows.length} results`)}</span>
 </div>
 ${HSMORE?`<div class="fbar second">
  <div class="fb"><label>${t("رغبة العمل","Work desire")}</label><select data-hsdesire><option value="">${t("الكل","All")}</option>${hsDesire.map(x=>`<option value="${x[0]}"${HSDESIRE===x[0]?" selected":""}>${t(x[1],x[2])}</option>`).join("")}</select></div>
  <div class="fb"><label>${t("التصنيف السريع","Quick tag")}</label><select data-hstag><option value="">${t("الكل","All")}</option>${hsTags.map(x=>`<option${HSTAG===x?" selected":""}>${x}</option>`).join("")}</select></div>
  <div class="fb"><label>${t("مصدر الاستلام","Receipt source")}</label><select><option>${t("الكل","All")}</option>${hsSources.map(x=>`<option>${x}</option>`).join("")}</select></div>
  <div class="fb"><label>${t("نوع العاملة","Worker type")}</label><select><option>${t("الكل","All")}</option><option>${t("استقدام","Recruitment")}</option><option>${t("تأجير","Rental")}</option><option>${t("نقل كفالة","Transfer")}</option></select></div>
  <div class="fb"><label>${t("الفرع","Branch")}</label><select><option>${t("كل الفروع","All branches")}</option>${["الفرع الرئيسي","فرع جدة","فرع الدمام"].map(x=>`<option>${x}</option>`).join("")}</select></div>
  <div class="fb"><label>${t("الاستلام الفوري","Instant receipt")}</label><select><option>${t("الكل","All")}</option><option>${t("متاحة","Available")}</option><option>${t("غير متاحة","Not available")}</option></select></div>
  <div class="fb"><label>${t("تاريخ الوصول","Arrival date")}</label><input type="date"></div>
  <div class="fb"><label>${t("تاريخ إدخال الإيواء","Shelter entry date")}</label><input type="date"></div>
 </div>`:""}
 <div class="card flush"><div class="card-h"><div><h3>${t(tab[1],tab[2])}</h3><p>${t("الأعمدة كما في شاشة النظام لهذا التبويب.","Columns match the system screen for this tab.")}</p></div></div>
 <div class="tw"><table class="grp"><thead>${hsHead()}</thead><tbody>
 ${rows.length?rows.map(hsCells).join(""):`<tr><td colspan="${colCount}"><div class="tempty">${I("inbox")}<b>${t("لا نتائج مطابقة","No matching results")}</b><small>${t("جرّب إزالة الفلاتر","Try clearing the filters")}</small></div></td></tr>`}
 </tbody></table></div>
 <div class="tfoot"><span>${t(`عرض ${rows.length} من ${tab[4]} سجل`,`Showing ${rows.length} of ${tab[4]} records`)}</span><div class="pager"><button>${I("chevron-right")}</button><button class="on">1</button><button>2</button><button>${I("chevron-left")}</button></div></div>
 </div>`},
 housingShow:()=>{
  const r=hsRows.find(x=>x[0]===HSID)||hsRows[0];const dep=hsDep[r[0]];
  const tabs=[["over","نظرة عامة","Overview","gauge"],["worker","بيانات العاملة","Worker data","id-card"],["status","الحالة والرغبة","Status & desire","activity"],["expenses","مصاريف الإعاشة","Living expenses","utensils"],["complaints","الشكاوى","Complaints","message-square"],["files","المرفقات","Attachments","paperclip"],["log","السجل","Activity log","history"]];
  if(dep!=null)tabs.splice(3,0,["dep","إجراءات الترحيل","Departure procedures","plane-takeoff"]);
  const body={
   over:()=>`
    ${sect("gauge","ملخص الإيواء","Housing summary",`<div class="ovgrid">${[["مكان الإيواء","Shelter place",r[7],r[18]],["تاريخ الوصول","Arrival date",r[8],t("للمملكة","to KSA")],["تاريخ إدخال الإيواء","Shelter entry",r[9],t("مصدر الاستلام","receipt source")+": "+r[18]],["مدة البقاء في المملكة","Duration in KSA",r[10]+" "+t("يوم","days"),t("من تاريخ الوصول","since arrival")],["مدة الإيواء","Shelter duration",r[11]+" "+t("يوم","days"),t("من تاريخ الإدخال","since entry")],["حالة الضمان","Guarantee",hsWarTag(r),r[19]]].map(x=>`<div class="ovc"><span class="ovl">${t(x[0],x[1])}</span><b class="ovv">${x[2]}</b><small class="ovm">${x[3]}</small></div>`).join("")}</div>`)}
    ${sect("clipboard-check","الجاهزية","Readiness",`<div class="readygrid">${[[r[16],"الإقامة","Residency",r[16]?"سارية":"غير مُصدرة",r[16]?"Valid":"Not issued"],[r[17],"الفحص الطبي","Medical exam",r[17]?"مكتمل":"غير مكتمل",r[17]?"Done":"Pending"],[r[14],"الاستلام الفوري","Instant receipt",r[14]?"متاحة":"غير متاحة",r[14]?"Available":"Not available"],[r[13]==="work","رغبة العمل","Work desire",hsDesire.find(x=>x[0]===r[13])[1],hsDesire.find(x=>x[0]===r[13])[2]]].map(x=>`<div class="ready ${x[0]?"ok":"no"}">${I(x[0]?"check-circle-2":"x-circle")}<div><b>${t(x[1],x[2])}</b><small>${t(x[3],x[4])}</small></div></div>`).join("")}</div>`)}
    ${sect("tags","التصنيفات السريعة","Quick tags",`<div class="skills">${(r[15].length?r[15]:[t("لا تصنيفات","No tags")]).map(x=>`<span class="skill">${I("tag")}${x}</span>`).join("")}</div>`)}`,
   worker:()=>sect("id-card","بيانات العاملة","Worker data",kvs([["الاسم","Name",r[1]],["الاسم بالإنجليزية","Name (EN)",`<span class="num">${r[2]}</span>`],["رقم الجواز","Passport",`<span class="num">${r[3]}</span>`],["الجنسية","Nationality",r[5]],["المهنة","Occupation",r[6]],["العميل","Client",r[4]==="—"?`<span class="empty">${t("بدون عميل","No client")}</span>`:r[4]],["الفرع","Branch",r[19]],["العقد المرتبط","Linked contract",`<button class="lnk" data-go="contracts">${I("file-signature")}<span class="num">CN-2026-0113</span></button>`]])),
   status:()=>`
    ${sect("activity","الحالة الحالية","Current status",kvs([["حالة الضمان","Guarantee",hsWarTag(r)],["رغبة العمل","Work desire",hsDesireTag(r)],["الاستلام الفوري","Instant receipt",r[14]?t("متاحة","Available"):t("غير متاحة","Not available")],["الإقامة","Residency",r[16]?t("نعم","Yes"):t("لا","No")],["الفحص الطبي","Medical exam",r[17]?t("تم","Done"):t("لم يتم","Pending")],["الحالة النهائية للراتب","Salary final status",`<span class="num">1,300</span> ${t("ر.س","SAR")}`]]))}
    ${sect("settings-2","تعديل الحالة","Update status",`<div class="fgrid"><div class="fld"><label>${t("رغبة العمل الجديدة","New work desire")}<code>work_willingness</code></label><select>${hsDesire.map(x=>`<option${x[0]===r[13]?" selected":""}>${t(x[1],x[2])}</option>`).join("")}</select></div>
     <div class="fld"><label>${t("سبب التغيير (اختياري)","Change reason (optional)")}<code>change_reason</code></label><input type="text" placeholder="${t("سبب التغيير","Reason")}"></div>
     <div class="fld"><label>${t("مكان الإيواء","Shelter place")}<code>shelter_id</code></label><select>${hsPlaces.map(x=>`<option${x===r[7]?" selected":""}>${x}</option>`).join("")}</select></div></div>
     <div class="formbar"><button class="btn btn-primary" data-hssave>${I("check")}${t("حفظ التغييرات","Save changes")}</button></div>`)}`,
   dep:()=>sect("plane-takeoff","إجراءات الترحيل","Departure procedures",`<div class="depsteps">${hsDepSteps.map((s,i)=>`<div class="dstep ${i<dep?"done":i===dep?"current":""}"><span class="ds-n">${i<dep?I("check"):`<b class="num">${i+1}</b>`}</span><div><b>${t(s[1],s[2])}</b><small>${i<dep?t("مكتمل · 2026-08-1"+(i+1),"Completed · 2026-08-1"+(i+1)):i===dep?t("قيد التنفيذ","In progress"):t("لم يبدأ","Not started")}</small></div>${i<hsDepSteps.length-1?'<span class="dl"></span>':""}</div>`).join("")}</div>
    <div class="formbar"><button class="btn btn-ghost">${I("printer")}${t("طباعة أمر الترحيل","Print departure order")}</button><button class="btn btn-primary" data-hsdepnext="${r[0]}">${I("arrow-left")}${t("إكمال الإجراء التالي","Complete next step")}</button></div>`),
   expenses:()=>sect("utensils","مصاريف الإعاشة","Living expenses",`<div class="tw"><table><thead><tr><th>${t("التاريخ","Date")}</th><th>${t("البند","Item")}</th><th>${t("إجمالي المصروف","Amount")}</th><th>${t("مصدر الصرف","Payment source")}</th><th>${t("الحساب","Account")}</th><th>${t("ملاحظات","Notes")}</th></tr></thead><tbody>
    ${[["2026-08-15","إعاشة أسبوعية","420.00","الصندوق النقدي","مصروف إعاشة الإيواء","—"],["2026-08-08","إعاشة أسبوعية","420.00","الصندوق النقدي","مصروف إعاشة الإيواء","—"],["2026-08-03","مستلزمات شخصية","180.00","بنك الراجحي","مصروف إعاشة الإيواء","بطلب من العاملة"]].map(x=>`<tr><td class="num">${x[0]}</td><td>${x[1]}</td><td class="num">${x[2]}</td><td>${x[3]}</td><td>${x[4]}</td><td>${x[5]}</td></tr>`).join("")}
    </tbody></table></div><div class="formbar"><button class="btn btn-primary" data-hsexpense>${I("plus")}${t("تسجيل مصروف إعاشة","Log living expense")}</button></div>`),
   complaints:()=>sect("message-square","الشكاوى","Complaints",r[15].includes("شكوى مفتوحة")?`<div class="tw"><table><thead><tr><th>${t("الشكوى","Complaint")}</th><th>${t("النوع","Type")}</th><th>${t("الموضوع","Subject")}</th><th>${t("الحالة","Status")}</th><th>${t("التاريخ","Date")}</th></tr></thead><tbody>
    <tr><td class="num">CMP-2026-0088</td><td>${t("شكوى من العاملة","From worker")}</td><td>${t("طلب تغيير مكان الإيواء","Request to change shelter")}</td><td><span class="st tag a"><i></i>${t("مفتوحة","Open")}</span></td><td class="num">2026-08-12</td></tr>
    </tbody></table></div>`:`<div class="tempty">${I("inbox")}<b>${t("لا شكاوى مسجّلة","No complaints")}</b></div>`),
   files:()=>sect("paperclip","المرفقات","Attachments",`
    ${[["نموذج الاستلام","Receipt form","receipt_"+r[0]+".pdf"],["صورة الجواز","Passport copy","passport_"+r[3]+".jpg"],["تقرير الفحص الطبي","Medical report",r[17]?"medical_report.pdf":""],["صورة الإقامة","Residency copy",r[16]?"iqama.jpg":""]].map(x=>`<div class="att"><div class="att-t">${I("file-text")}<div><b>${t(x[0],x[1])}</b><small>${x[2]||t("غير مرفوع","Not uploaded")}</small></div></div><div class="att-a">${x[2]?`<button class="btn btn-ghost">${I("eye")}${t("عرض","Preview")}</button><button class="btn btn-ghost">${I("download")}${t("تنزيل","Download")}</button>`:`<button class="btn btn-ghost">${I("upload")}${t("رفع","Upload")}</button>`}</div></div>`).join("")}
    <div class="upl"><label>${t("إضافة مرفق","Add attachment")}<code>attachments</code></label><input type="text" placeholder="${t("وصف المرفق","Attachment title")}"><div class="ctl file">${I("upload")}<span>${t("اختر الملفات — يمكن تحديد أكثر من ملف","Select files — multiple allowed")}</span></div><button class="btn btn-primary">${I("upload")}${t("رفع","Upload")}</button></div>`),
   log:()=>sect("history","السجل","Activity log",`<div class="tl">${[[r[9]+" · 09:20",t("إدخال العاملة إلى الإيواء","Worker entered shelter"),r[18]],[r[9]+" · 11:05",t("طباعة نموذج الاستلام","Receipt form printed"),"سارة القحطاني"],["2026-08-12 · 14:30",r[17]?t("تسجيل الفحص الطبي","Medical exam recorded"):t("تحديث التصنيفات السريعة","Quick tags updated"),"مشرف الإيواء"]].map(x=>`<div class="tli"><span class="dot"></span><div><b>${x[1]}</b><small>${x[2]}</small><small class="num">${x[0]}</small></div></div>`).join("")}</div>`)
  };
  return `
 <div class="page-head compact"><div><a class="back" href="#" data-go="housing" style="margin:0 0 8px">${I("arrow-right")}<span>${t("الرجوع لإدارة الإيواء","Back to housing")}</span></a><h1>${r[1]}</h1><p><span class="num">${r[0]}</span> · ${r[5]} · ${r[7]} · ${r[20]?t("خارج الإيواء","Outside shelter"):r[23]?t("تم الترحيل","Deported"):t("داخل الإيواء","Inside shelter")}</p></div>
 <div class="actions"><button class="btn btn-primary" data-hsedit="${r[0]}">${I("pencil")}${t("تعديل عقد الإيواء","Edit shelter contract")}</button><button class="btn btn-ghost">${I("file-text")}${t("نموذج الاستلام","Receipt form")}</button><button class="btn btn-ghost">${I("printer")}${t("طباعة","Print")}</button></div></div>
 <div class="showgrid3">
  <aside class="cvside">
   <div class="card idcard"><span class="bigph">${I("user-round")}</span><b>${r[1]}</b><small class="num">${r[2]}</small>${hsWarTag(r)}
    <div class="idk">${[[t("الجنسية","Nationality"),r[5]],[t("المهنة","Occupation"),r[6]],[t("مدة الإيواء","In shelter"),r[11]+t(" يوم"," d")],[t("في المملكة","In KSA"),r[10]+t(" يوم"," d")]].map(x=>`<div><small>${x[0]}</small><b>${x[1]}</b></div>`).join("")}</div>
   </div>
   <div class="card acard"><div class="sec-h">${I("settings-2")}<b>${t("إجراءات الإيواء","Housing actions")}</b></div>
    <div class="actrow"><div><b>${t("الاستلام الفوري","Instant receipt")}</b><small>is_available_for_instant</small></div><span class="sw ${r[14]?"on":""}" data-hstoggle="instant:${r[0]}"></span></div>
    <div class="actrow"><div><b>${t("لديها إقامة","Has residency")}</b><small>has_iqama</small></div><span class="sw ${r[16]?"on":""}" data-hstoggle="iqama:${r[0]}"></span></div>
    <div class="acts2">${[["arrow-left-right","نقل بين الإيواءات","Transfer shelter",""],["utensils","مصروف إعاشة","Living expense",""],["stethoscope","فحص طبي","Medical exam",""],["message-square","تسجيل شكوى","Log complaint",""],["plane-takeoff","مرحلة الترحيل","Departure stage",""],["log-out","خروج من الإيواء","Shelter exit","danger"]].map(x=>`<button class="atile ${x[3]}" data-hsact="${x[0]}:${r[0]}">${I(x[0])}<span>${t(x[1],x[2])}</span></button>`).join("")}</div>
   </div>
  </aside>
  <div class="cvmain">
   <div class="vtabs">${tabs.map(x=>`<button class="${x[0]===HSSTAB?"on":""}" data-hsstab="${x[0]}">${I(x[3])}<span>${t(x[1],x[2])}</span></button>`).join("")}</div>
   <div class="tabbody">${(body[HSSTAB]||body.over)()}</div>
  </div>
 </div>`},
 housingAdd:()=>{
  const ed=HSEDIT?hsRows.find(x=>x[0]===HSEDIT):null;
  const f=(ar,en,nm,val,type,req)=>{let ctl;
   if(type==="select")ctl=`<select>${(Array.isArray(val)?val:[val]).map((o,i)=>`<option${i===0?" selected":""}>${o}</option>`).join("")}</select>`;
   else if(type==="date")ctl=`<input type="date"${val?` value="${val}"`:""}>`;
   else if(type==="ro")ctl=`<input type="text" value="${val}" readonly dir="ltr">`;
   else ctl=`<input type="${type||"text"}" ${ed&&val?`value="${val}"`:`placeholder="${val||""}"`}${["passport","salary","national_num","phone"].includes(nm)?' dir="ltr"':""}>`;
   return `<div class="fld"><label>${t(ar,en)}${req?' <i class="rq">*</i>':""}<code>${nm}</code></label>${ctl}</div>`};
  const grid=fs=>`<div class="fgrid">${fs.join("")}</div>`;
  return `
 <div class="page-head"><div><a class="back" href="#" data-go="housing" style="margin:0 0 8px">${I("arrow-right")}<span>${t("إدارة الإيواء","Housing management")}</span></a><h1>${ed?t("تعديل عقد الإيواء","Edit shelter contract"):t("إضافة عقد إيواء","Add shelter contract")}${ed?`<span class="tag a hdrtag">${I("pencil")}${t("تعديل","Editing")}</span>`:""}</h1>${ed?`<p><span class="num">${ed[0]}</span> · ${ed[1]} · ${ed[7]}</p>`:`<p>${t("اختر العقد ومكان الإيواء ثم استكمل بيانات الاستلام والحالة.","Pick the contract and shelter place, then complete receipt and status data.")}</p>`}</div>
 <div class="actions"><button class="btn btn-ghost" data-go="housing">${t("إلغاء","Cancel")}</button><button class="btn btn-primary" data-hssave>${I("check")}${ed?t("حفظ التعديلات","Save changes"):t("حفظ عقد الإيواء","Save shelter contract")}</button></div></div>
 <div class="cvform">
  <aside class="cvopts">
   <div class="card"><div class="sec-h">${I("list-checks")}<b>${t("أقسام النموذج","Form sections")}</b></div>
    <div class="steps">${[["h1","العقد ومكان الإيواء","Contract & place"],["h2","بيانات الاستلام","Receipt data"],["h3","الحالة والجاهزية","Status & readiness"],["h4","التصنيفات والمرفقات","Tags & attachments"]].map((x,i)=>`<button class="step" data-hsjump="${x[0]}"><span class="stepn num">${i+1}</span><span>${t(x[1],x[2])}</span></button>`).join("")}</div></div>
   <div class="card"><div class="sec-h">${I("home")}<b>${t("مكان الإيواء","Shelter place")}</b></div>
    <div class="actrow sm col"><label>${t("الإيواء","Shelter")}<code>shelter_id</code></label><select class="statsel">${hsPlaces.map(x=>`<option${ed&&x===ed[7]?" selected":""}>${x}</option>`).join("")}</select></div>
    <div class="actrow sm col"><label>${t("الفرع","Branch")}<code>branch_id</code></label><select class="statsel">${["الفرع الرئيسي","فرع جدة","فرع الدمام"].map(x=>`<option${ed&&x===ed[19]?" selected":""}>${x}</option>`).join("")}</select></div>
   </div>
  </aside>
  <div class="cvfields">
   <div class="card sec" id="h1"><div class="fs-h"><span class="fs-n num">1</span><b>${t("العقد ومكان الإيواء","Contract & shelter place")}</b></div>
    ${grid([f("طريقة البحث","Search method","contract_search_type",[t("برقم العقد","By contract no."),t("باسم العاملة","By worker name"),t("برقم الجواز","By passport")],"select"),
      f("العقد","Contract","contract_id",ed?["CN-2026-0113 — "+ed[1]]:["CN-2026-0115 — ماريتس دي لا كروز","CN-2026-0114 — سري واهيوني","CN-2026-0111 — نيلانثي سيلفا"],"select",1),
      f("مكان الإيواء","Shelter place","shelter_id",ed?[ed[7]].concat(hsPlaces.filter(x=>x!==ed[7])):hsPlaces,"select",1),
      f("مصدر الاستلام","Receipt source","receipt_source",ed?[ed[18]].concat(hsSources.filter(x=>x!==ed[18])):hsSources,"select",1)])}</div>
   <div class="card sec" id="h2"><div class="fs-h"><span class="fs-n num">2</span><b>${t("بيانات الاستلام","Receipt data")}</b></div>
    ${grid([f("تاريخ إدخالها للإيواء","Shelter entry date","maid_date_back",ed?ed[9]:"","date",1),
      f("تاريخ الوصول","Arrival date","arrival_date",ed?ed[8]:"","date",1),
      f("مدة بقائها داخل المملكة","Duration in KSA","duration_from_enter",ed?ed[10]+" يوم":"","ro"),
      f("الحالة النهائية للراتب","Salary final status","salary_final_status","1,300"),
      f("تاريخ نموذج الاستلام","Receipt form date","receipt_form_date","","date"),
      f("رقم جواز السفر","Passport","passport",ed?ed[3]:"PXXXXXXX")])}</div>
   <div class="card sec" id="h3"><div class="fs-h"><span class="fs-n num">3</span><b>${t("الحالة والجاهزية","Status & readiness")}</b></div>
    ${grid([f("رغبة العمل","Work willingness","work_willingness",ed?[hsDesire.find(x=>x[0]===ed[13])[1]].concat(hsDesire.map(x=>x[1]).filter(y=>y!==hsDesire.find(x=>x[0]===ed[13])[1])):hsDesire.map(x=>x[1]),"select"),
      f("هل العاملة لديها إقامة؟","Has residency?","has_iqama",[t("نعم","Yes"),t("لا","No")],"select"),
      f("هل تم عمل الفحص الطبي؟","Medical exam done?","medical_check",[t("نعم","Yes"),t("لا","No")],"select"),
      f("الاستلام الفوري","Instant receipt","is_available_for_instant",[t("متاحة","Available"),t("غير متاحة","Not available")],"select"),
      f("حالة الضمان","Guarantee status","guarantee_status",hsWar.map(x=>x[1]),"select"),
      f("أسباب العودة","Back reasons","back_reasons",[t("لا يوجد","None"),t("عدم الرغبة في العمل","Not willing to work"),t("أسباب صحية","Health reasons"),t("خلاف مع العميل","Dispute with client")],"select")])}</div>
   <div class="card sec" id="h4"><div class="fs-h"><span class="fs-n num">4</span><b>${t("التصنيفات والمرفقات","Tags & attachments")}</b></div>
    <div class="fld full"><label>${t("التصنيفات السريعة","Quick tags")}<code>shelter_quick_tags</code></label>
     <div class="chipset">${hsTags.map(x=>`<button class="chip ${ed&&ed[15].includes(x)?"on":""}">${I("tag")}${x}</button>`).join("")}</div></div>
    <div class="fld full"><label>${t("مرفقات الإيواء","Shelter attachments")}<code>attachments</code></label><div class="ctl file">${I("upload")}<span>${t("اختر الملفات — يمكن تحديد أكثر من ملف","Select files — multiple allowed")}</span></div></div>
    <div class="fld full"><label>${t("ملاحظات","Notes")}<code>notes</code></label><textarea rows="3"></textarea></div></div>
   <div class="formbar"><button class="btn btn-ghost" data-go="housing">${t("إلغاء","Cancel")}</button><button class="btn btn-primary" data-hssave>${I("check")}${ed?t("حفظ التعديلات","Save changes"):t("حفظ عقد الإيواء","Save shelter contract")}</button></div>
  </div>
 </div>`}
};
let HSEDIT=null;
Object.assign(T,{housing:{ar:"إدارة الإيواء",en:"Housing"},housingShow:{ar:"تفاصيل الإيواء",en:"Housing details"},housingAdd:{ar:"عقد إيواء",en:"Shelter contract"}});
Object.assign(screens,housingScreens);
function hsPlace(id){requestAnimationFrame(()=>{const btn=document.querySelector(`[data-hsmenu="${id}"]`),menu=document.querySelector(`[data-hsmenu-for="${id}"]`);if(!btn||!menu)return;const b=btn.getBoundingClientRect();document.body.appendChild(menu);menu.classList.remove("hidden");menu.style.position="fixed";menu.style.zIndex="200";menu.style.insetInlineEnd="auto";const h=menu.offsetHeight,w=menu.offsetWidth;let top=b.bottom+6;if(top+h>innerHeight-8)top=Math.max(8,b.top-h-6);let left=document.body.dir==="rtl"?b.left:b.right-w;left=Math.min(Math.max(8,left),innerWidth-w-8);menu.style.top=top+"px";menu.style.left=left+"px";lucide.createIcons({attrs:{"stroke-width":1.8}})})}
function hsExpenseModal(){let ov=document.getElementById("hsOv");if(!ov){ov=document.createElement("div");ov.id="hsOv";ov.className="ov";document.body.appendChild(ov)}
 ov.innerHTML=`<div class="modal"><div class="modal-h"><div><h3>${t("تسجيل مصروف إعاشة","Log living expense")}</h3><p class="ms">${t("يُسجَّل قيداً مالياً على حساب مصروف الإعاشة.","Recorded as a journal entry on the living-expense account.")}</p></div><button class="icon-btn" data-hsclose>${I("x")}</button></div>
 <div class="modal-b"><div class="fgrid two">
  <div class="fld"><label>${t("التاريخ","Date")}<code>livingExpenseDate</code></label><input type="date" value="2026-08-19"></div>
  <div class="fld"><label>${t("مكان الإيواء","Shelter place")}<code>livingExpenseShelter</code></label><select>${hsPlaces.map(x=>`<option>${x}</option>`).join("")}</select></div>
  <div class="fld"><label>${t("البند","Item")}<code>livingExpenseTitle</code></label><input type="text" placeholder="${t("إعاشة أسبوعية","Weekly living expense")}"></div>
  <div class="fld"><label>${t("إجمالي المصروف","Total amount")}<code>livingExpenseAmount</code></label><input type="text" placeholder="420.00" dir="ltr"></div>
  <div class="fld"><label>${t("العاملة","Worker")}<code>livingExpenseContract</code></label><select><option>${t("كل عاملات الإيواء","All shelter workers")}</option>${hsRows.filter(hsIn).map(r=>`<option>${r[1]} — ${r[0]}</option>`).join("")}</select></div>
  <div class="fld"><label>${t("حساب مصروف الإعاشة","Expense account")}<code>livingExpenseAccount</code></label><select><option>${t("مصروف إعاشة الإيواء","Shelter living expense")}</option><option>${t("مصروفات تشغيلية أخرى","Other operating expenses")}</option></select></div>
  <div class="fld"><label>${t("مصدر الصرف","Payment source")}<code>livingPaymentSourceType</code></label><select><option>${t("صندوق نقدي","Cashbox")}</option><option>${t("حساب بنكي","Bank account")}</option></select></div>
  <div class="fld"><label>${t("حساب الصرف","Payment account")}<code>livingPaymentAccount</code></label><select><option>${t("الصندوق الرئيسي","Main cashbox")}</option><option>${t("بنك الراجحي","Alrajhi Bank")}</option></select></div>
 </div><div class="fld full"><label>${t("ملاحظات","Notes")}<code>livingExpenseNotes</code></label><textarea rows="2"></textarea></div></div>
 <div class="modal-f"><button class="btn btn-ghost" data-hsclose>${t("إلغاء","Cancel")}</button><button class="btn btn-primary" data-hsdone>${I("check")}${t("حفظ المصروف","Save expense")}</button></div></div>`;
 ov.classList.remove("hidden");lucide.createIcons({attrs:{"stroke-width":1.8}})}
document.addEventListener("click",e=>{
 const tb=e.target.closest("[data-hstab]");if(tb){HSTAB=tb.dataset.hstab;HSWAR="";HSDESIRE="";HSTAG="";render();return}
 const st=e.target.closest("[data-hsstat]");if(st){const k=st.dataset.hsstat;if(!k){HSWAR="";HSDESIRE=""}else if(hsWar.some(x=>x[0]===k))HSWAR=HSWAR===k?"":k;else if(hsDesire.some(x=>x[0]===k))HSDESIRE=HSDESIRE===k?"":k;render();return}
 const sg=e.target.closest("[data-hsstats]");if(sg){HSSTATS=!HSSTATS;render();return}
 const mf=e.target.closest("[data-hsmore]");if(mf){HSMORE=!HSMORE;render();return}
 const sh=e.target.closest("[data-hsshow]");if(sh){HSID=sh.dataset.hsshow;HSSTAB="over";cur="housingShow";render();window.scrollTo(0,0);return}
 const stb=e.target.closest("[data-hsstab]");if(stb){HSSTAB=stb.dataset.hsstab;render();window.scrollTo(0,0);return}
 const ed=e.target.closest("[data-hsedit]");if(ed){HSEDIT=ed.dataset.hsedit;cur="housingAdd";render();window.scrollTo(0,0);return}
 const jp=e.target.closest("[data-hsjump]");if(jp){const el=document.getElementById(jp.dataset.hsjump);if(el)window.scrollTo({top:el.offsetTop-90,behavior:"smooth"});return}
 const sv=e.target.closest("[data-hssave]");if(sv){const back=HSEDIT;HSEDIT=null;if(back){HSID=back;cur="housingShow"}else cur="housing";render();window.scrollTo(0,0);clToast("تم حفظ بيانات الإيواء","Housing data saved");return}
 const dn=e.target.closest("[data-hsdepnext]");if(dn){const id=dn.dataset.hsdepnext;hsDep[id]=Math.min((hsDep[id]||0)+1,hsDepSteps.length);render();clToast("تم إكمال الإجراء","Step completed");return}
 const tg=e.target.closest("[data-hstoggle]");if(tg){const [k,id]=tg.dataset.hstoggle.split(":");const r=hsRows.find(x=>x[0]===id);if(r){if(k==="instant")r[14]=r[14]?0:1;if(k==="iqama")r[16]=r[16]?0:1;render();clToast("تم تحديث البيانات","Updated")}return}
 const mn=e.target.closest("[data-hsmenu]");if(mn){const id=mn.dataset.hsmenu;HSMENU=HSMENU===id?null:id;render();if(HSMENU)hsPlace(HSMENU);return}
 const ac=e.target.closest("[data-hsact]");
 if(ac){const [k,id]=ac.dataset.hsact.split(":");HSMENU=null;clKillMenus();
  if(k==="utensils"){hsExpenseModal();return}
  if(k==="plane-takeoff"){if(hsDep[id]==null)hsDep[id]=0;HSID=id;HSSTAB="dep";cur="housingShow";render();window.scrollTo(0,0);clToast("تم إدخال العاملة في مرحلة الترحيل","Moved to departure stage");return}
  if(k==="zap"){const r=hsRows.find(x=>x[0]===id);if(r){r[14]=r[14]?0:1;render();clToast(r[14]?"تم تفعيل الاستلام الفوري":"تم إلغاء الاستلام الفوري",r[14]?"Instant receipt enabled":"Instant receipt disabled")}return}
  const nm=hsMenuItems.find(x=>x[0]===k)||["","إجراء","Action"];clToast(t(nm[1],nm[2])+" — "+id,t(nm[1],nm[2])+" — "+id);return}
 if(e.target.closest("[data-hsexpense]")){hsExpenseModal();return}
 const cl=e.target.closest("[data-hsclose]"),done=e.target.closest("[data-hsdone]");
 if(cl||done){const ov=document.getElementById("hsOv");if(ov)ov.classList.add("hidden");if(done)clToast("تم تسجيل مصروف الإعاشة","Living expense saved");return}
 if(HSMENU&&!e.target.closest(".kebab")&&!e.target.closest(".amenu")){HSMENU=null;clKillMenus();render()}
 const gg=e.target.closest("[data-go]");if(gg&&gg.dataset.go!=="housingAdd")HSEDIT=null;
});
document.addEventListener("input",e=>{if(e.target.matches("[data-hsq]")){HSQ=e.target.value;const p=e.target.selectionStart;render();const n=document.querySelector("[data-hsq]");if(n){n.focus();n.setSelectionRange(p,p)}}});
document.addEventListener("change",e=>{
 if(e.target.matches("[data-hsplace]")){HSPLACE=e.target.value;render()}
 if(e.target.matches("[data-hswar]")){HSWAR=e.target.value;render()}
 if(e.target.matches("[data-hsdesire]")){HSDESIRE=e.target.value;render()}
 if(e.target.matches("[data-hstag]")){HSTAG=e.target.value;render()}
});
