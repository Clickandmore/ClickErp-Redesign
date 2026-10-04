/* CV screens — grounded in admin/ingaz/maids/*, simplified UI */
const cvTabsL=[["rec","سير الاستقدام","Recruitment CVs","maids.index","MAIDS_VIEW"],["rent","سير التأجير","Rental CVs","maids.rental.index","MAIDS_RENTAL_VIEW"],["backout","باك أوت","Backed out","noneAvailableMaids","MAIDS_BACKOUT_VIEW"],["pending","بانتظار المراجعة","Pending review","maids.pending-submissions","MAIDS_VIEW"],["deleted","محذوفة","Deleted","maids.deleted_cvs","MAIDS_DELETED_VIEW"]];
const cvRows=[
 ["A-118742","MARY GRACE SANTOS","ماري غرايس سانتوس","الفلبين","Philippines","عاملة منزلية","Domestic worker","PLATINUM BROTHERS","P1234567",32,"available","متاحة","Available","g","17 أغسطس 2026","1,200","rec",1,1,"","مسيحية","Christian"],
 ["A-118741","AKELLO SARAH","أكيلو سارة","أوغندا","Uganda","عاملة منزلية","Domestic worker","Supreme Link Ltd","UG882134",28,"reserved","محجوزة","Reserved","a","16 أغسطس 2026","1,000","rec",1,0,"","مسلمة","Muslim"],
 ["A-118740","RAJESH KUMAR","راجيش كومار","الهند","India","سائق خاص","Private driver","Gulf Bridge Agency","IN553120",41,"available","متاحة","Available","g","14 أغسطس 2026","1,500","rec",0,0,"","هندوسي","Hindu"],
 ["A-118739","HELEN TESFAYE","هيلين تسفاي","إثيوبيا","Ethiopia","طباخة","Cook","Al Noor Manpower","ET119002",35,"in_accommodation","في الإيواء","In housing","n","11 أغسطس 2026","1,300","rent",1,1,"6 أشهر","مسيحية","Christian"],
 ["A-118738","JOSEPHINE MWANGI","جوزفين موانجي","كينيا","Kenya","عاملة منزلية","Domestic worker","Supreme Link Ltd","KE771230",30,"backout","باك أوت","Backed out","r","09 أغسطس 2026","1,100","backout",0,0,"","مسيحية","Christian"],
 ["A-118737","NILUKA PERERA","نيلوكا بيريرا","سريلانكا","Sri Lanka","ممرضة منزلية","Home nurse","PLATINUM BROTHERS","LK330984",37,"contracted","متعاقد عليها","Contracted","n","05 أغسطس 2026","1,800","rec",1,0,"","بوذية","Buddhist"],
 ["A-118736","AMINA HASSAN","أمينة حسن","بنغلاديش","Bangladesh","عاملة نظافة","Cleaner","Gulf Bridge Agency","BD220913",26,"available","متاحة","Available","g","02 أغسطس 2026","900","rec",1,0,"","مسلمة","Muslim"],
 ["A-118735","GRACE ODHIAMBO","غريس أوديامبو","كينيا","Kenya","عاملة منزلية","Domestic worker","Al Noor Manpower","KE118845",33,"pending","بانتظار المراجعة","Pending review","a","29 يوليو 2026","1,000","pending",0,0,"","مسيحية","Christian"],
 ["A-118729","MERCY ATIENO","مرسي أتينو","كينيا","Kenya","عاملة منزلية","Domestic worker","Supreme Link Ltd","KE990321",29,"available","متاحة للتأجير","Available","g","12 يوليو 2026","1,250","rent",1,1,"3 أشهر","مسيحية","Christian"],
 ["A-118728","DEWI LESTARI","ديوي ليستاري","سريلانكا","Sri Lanka","طباخة","Cook","PLATINUM BROTHERS","LK220145",36,"contracted","مؤجّرة حالياً","Currently rented","n","10 يوليو 2026","1,600","rent",1,0,"شهر","بوذية","Buddhist"],
 ["A-118733","FATIMA NOOR","فاطمة نور","الهند","India","عاملة منزلية","Domestic worker","Gulf Bridge Agency","IN774512",31,"in_accommodation","في الإيواء","In housing","n","22 يوليو 2026","1,150","rec",1,0,"","مسلمة","Muslim"],
 ["A-118732","ANITA SILVA","أنيتا سيلفا","الفلبين","Philippines","طباخة","Cook","PLATINUM BROTHERS","P4410233",27,"pinned","مثبّتة","Pinned","n","20 يوليو 2026","1,400","rec",1,1,"","مسيحية","Christian"],
 ["A-118731","BETTY WANJIRU","بيتي وانجيرو","كينيا","Kenya","عاملة منزلية","Domestic worker","Al Noor Manpower","KE550118",34,"suspended","موقوفة","Suspended","r","18 يوليو 2026","1,000","rec",0,0,"","مسلمة","Muslim"],
 ["A-118730","SITI RAHMA","سيتي رحمة","بنغلاديش","Bangladesh","عاملة نظافة","Cleaner","Gulf Bridge Agency","BD660477",38,"deported","مُرحّلة","Deported","n","15 يوليو 2026","950","rec",1,0,"","مسيحية","Christian"],
 ["A-118734","LINA MARQUEZ","لينا ماركيز","الفلبين","Philippines","عاملة منزلية","Domestic worker","PLATINUM BROTHERS","P9930211",29,"deleted","محذوفة","Deleted","r","24 يوليو 2026","1,200","deleted"]];
const cvStatsByTab={
 rec:[["","إجمالي القائمة","List total",163,"n","users"],["available","متاحة","Available",118,"g","check-circle-2"],["reserved","محجوزة","Reserved",22,"a","hand"],["contracted","متعاقد عليها","Contracted",15,"n","file-signature"],["in_accommodation","في الإيواء","In housing",5,"n","warehouse"],["pinned","مثبّتة","Pinned",2,"n","pin"],["suspended","موقوفة","Suspended",1,"r","ban"],["deported","مُرحّلة","Deported",0,"n","plane-takeoff"],1,0,"","مسلمة","Muslim"],
 rent:[["","إجمالي القائمة","List total",13,"n","users"],["available","متاحة للتأجير","Available",4,"g","check-circle-2"],["in_accommodation","في الإيواء","In housing",6,"n","warehouse"],["contracted","مؤجّرة حالياً","Currently rented",3,"n","file-signature"]],
 backout:[["","إجمالي القائمة","List total",12,"r","undo-2"]],
 pending:[["","إجمالي القائمة","List total",14,"a","clock"]],
 deleted:[["","إجمالي القائمة","List total",7,"r","trash-2"]]};
const cvSubs={rec:["163 سيرة استقدام — متاحة 118 · محجوزة 22 · متعاقد عليها 15","163 recruitment CVs — 118 available · 22 reserved · 15 contracted"],rent:["13 سيرة تأجير — متاحة 4 · في الإيواء 6 · مؤجّرة 3","13 rental CVs — 4 available · 6 in housing · 3 rented"],backout:["12 سيرة باك أوت — مرتجعة من المكاتب الخارجية","12 backed-out CVs returned from external offices"],pending:["14 سيرة بانتظار مراجعة الإدارة","14 CVs awaiting management review"],deleted:["7 سير محذوفة — قابلة للاستعادة","7 deleted CVs — restorable"]};
const cvCounts={rec:163,rent:13,backout:12,pending:14,deleted:7};
const cvExtraDefs=[["border","رقم الحدود","Border no."],["pexp","انتهاء الجواز","Passport expiry"],["idn","الهوية وانتهاؤها","ID & expiry"],["marital","الحالة الاجتماعية","Marital & children"],["exp","سنوات الخبرة","Experience"],["langs","اللغات","Languages"],["skills","أول ٣ مهارات","Top 3 skills"],["branch","الفرع","Branch"],["dur","مدة العقد","Duration"],["arr","تاريخ الوصول","Arrival date"],["upd","آخر تحديث للحالة","Status updated"],["link","العقد / العميل","Contract / client"]];
const cvAllSkills=[["الطبخ","Cooking","cooking-pot"],["التنظيف","Cleaning","spray-can"],["العناية بالأطفال","Childcare","baby"],["كبار السن","Elderly care","heart-handshake"],["الكوي","Ironing","shirt"],["الخياطة","Sewing","scissors"],["القيادة","Driving","car"],["ذوي الاحتياجات","Special needs","accessibility"],["الترتيب","Organizing","layout-grid"],["الخبز","Baking","cake"]];
const cvAllLangs=[["العربية","Arabic"],["الإنجليزية","English"],["الفلبينية","Filipino"],["الأردية","Urdu"],["الأمهرية","Amharic"],["السواحيلية","Swahili"]];
const cvLevels=[["مبتدئ","Beginner",30],["جيد","Good",60],["ممتاز","Excellent",90]];
const cvBranches=["الفرع الرئيسي","فرع جدة","فرع الدمام"];
const cvSkillSets=[["الطبخ","العناية بالأطفال","التنظيف"],["التنظيف","الكوي","الخياطة"],["القيادة","الصيانة","التنظيف"],["الطبخ","الخبز","التنظيف"],["كبار السن","التنظيف","الطبخ"]];
const cvExtra=(r,k)=>{const i=cvRows.indexOf(r),y=2029+(i%3);switch(k){
 case "border":return `<span class="num">${4400+i*137}${1000+i*7}</span>`;
 case "pexp":return `<span class="num">${y}-0${1+(i%9)}-1${i%9}</span>`;
 case "idn":return `<span class="num">2${480+i}${100+i}${i%7}${i%5}</span><small>${t("تنتهي","exp")} ${2030+(i%2)}-0${1+(i%8)}</small>`;
 case "marital":return `${t(i%3===0?"عازبة":i%3===1?"متزوجة":"مطلقة",i%3===0?"Single":i%3===1?"Married":"Divorced")}<small>${t("أطفال","Children")}: ${i%4}</small>`;
 case "exp":return `<b>${2+(i%7)} ${t("سنوات","yrs")}</b><small>${i%2?t("خارج المملكة","Abroad"):t("داخل المملكة","In KSA")}</small>`;
 case "langs":return `<div class="lgs">${[[t("العربية","Arabic"),60+(i%4)*10],[t("الإنجليزية","English"),50+(i%5)*10]].map(l=>`<span class="lg">${l[0]}<i style="width:${l[1]}%"></i></span>`).join("")}</div>`;
 case "skills":return `<div class="mbadges row wrap">${cvSkillSets[i%5].map(x=>`<span class="tag">${x}</span>`).join("")}</div>`;
 case "branch":return cvBranches[i%3];
 case "dur":return r[19]||t("24 شهراً","24 months");
 case "arr":return r[10]==="available"?`<span class="empty">${t("لم تصل","Not arrived")}</span>`:`<span class="num">2026-0${1+(i%8)}-1${i%9}</span>`;
 case "upd":return `<span class="num">2026-08-${10+(i%9)}</span><small>${t("بواسطة","by")} ${i%2?t("سارة القحطاني","Sara Q."):t("مشرف النظام","Supervisor")}</small>`;
 case "link":return r[10]==="contracted"||r[16]==="rent"?`<b class="num">CN-2026-0${100+i}</b><small>${t("منيرة الشهري","Munira A.")}</small>`:`<span class="empty">—</span>`;}return "—"};
let SKILLS=["الطبخ","التنظيف"],LANGS={"العربية":70,"الإنجليزية":50},LANGMODE="pct",EXPST="has",CVCOLS=[],CVCOLPICK=false,CVEDIT=null,CVSTAB="over",CVVIEW="rows",CVMENU=null,CVTAB="rec",CVQ="",CVSTATUS="",CVNAT="",CVMORE=false,CVJOB="",CVPIN="",CVVIS="",CVTYPE="rec",CVMODE="short",CVKINGDOM="outside",CVNAMES=false;
const cvNats=[["الفلبين","Philippines"],["أوغندا","Uganda"],["الهند","India"],["إثيوبيا","Ethiopia"],["كينيا","Kenya"],["سريلانكا","Sri Lanka"],["بنغلاديش","Bangladesh"]];
const cvSt=[["available","متاحة","Available"],["reserved","محجوزة","Reserved"],["contracted","متعاقد عليها","Contracted"],["in_accommodation","في الإيواء","In housing"],["backout","باك أوت","Backed out"],["pinned","مثبّتة","Pinned"],["suspended","موقوفة","Suspended"],["deported","مُرحّلة","Deported"]];
const cvJobs=["عاملة منزلية","سائق خاص","طباخة","ممرضة منزلية","عاملة نظافة"];
const cvFiltered=()=>cvRows.filter(r=>r[16]===CVTAB&&(!CVSTATUS||r[10]===CVSTATUS)&&(!CVNAT||r[3]===CVNAT)&&(!CVJOB||r[5]===CVJOB)&&(!CVQ||(r[1]+r[2]+r[8]+r[0]).toLowerCase().includes(CVQ.toLowerCase())));
const cvActItems=[["file-text","نموذج السيرة PDF","CV template PDF","prev"],["printer","طباعة السيرة","Print CV","print"],["undo-2","تسجيل باك أوت","Record backout","backout"],["eye","تبديل الظهور في الموقع","Toggle visibility","vis"],["pin","تثبيت / إلغاء التثبيت","Pin / unpin","pin"],["share-2","مشاركة واتساب","Share on WhatsApp","share"],["copy","نسخ السيرة","Duplicate CV","dup"],["trash-2","حذف السيرة","Delete CV","del"]];
const rowActs=r=>`<div class="rowacts"><button class="icon-btn pri" data-cvshow="${r[0]}" title="${t("عرض التفاصيل","View details")}">${I("eye")}</button><button class="icon-btn pri" data-cvedit="${r[0]}" title="${t("تعديل","Edit")}">${I("pencil")}</button><button class="icon-btn pri" data-cvprev="${r[0]}" title="${t("عرض نموذج السيرة","Preview CV template")}">${I("file-text")}</button><div class="kebab"><button class="icon-btn" data-cvmenu="${r[0]}" title="${t("إجراءات أخرى","More actions")}">${I("more-vertical")}</button><div class="amenu${CVMENU===r[0]?"":" hidden"}">${cvActItems.map(x=>`<button class="${x[3]==="del"?"danger":""}" ${x[3]==="prev"?`data-cvprev="${r[0]}"`:`data-act="${x[3]}:${r[0]}"`}>${I(x[0])}<span>${t(x[1],x[2])}</span></button>`).join("")}</div></div></div>`;
const cvCardGrid=rows=>`<div class="cvcards">${rows.map(r=>`<div class="cvcard"><div class="cc-ph">${r[17]?`${I("user-round")}${r[18]?`<i class="play">${I("play")}</i>`:""}`:I("image-off")}<span class="st tag ${r[13]}"><i></i>${t(r[11],r[12])}</span></div><div class="cc-b"><b>${t(r[2],r[1])}</b><small>${r[1]}</small><div class="cc-k">${[[t("الجنسية","Nationality"),t(r[3],r[4])],[t("المهنة","Occupation"),t(r[5],r[6])],[t("العمر","Age"),r[9]],[CVTAB==="rent"?t("أجرة التأجير","Rental fee"):t("الراتب","Salary"),r[15]]].map(k=>`<div><span>${k[0]}</span><b>${k[1]}</b></div>`).join("")}</div><div class="cc-m"><span class="num">${r[0]}</span><div class="mbadges row">${r[17]?`<span class="mb">${I("image")}</span>`:""}${r[18]?`<span class="mb g">${I("video")}</span>`:""}<span class="mb">${I("file-text")}</span></div></div></div><div class="cc-f">${rowActs(r)}</div></div>`).join("")}</div>`;
let EXPROWS=[["الكويت","عاملة منزلية","2021-03","2023-09"],["الأردن","طباخة","2019-01","2021-02"]];
const expMonths=(a,b)=>{x=(a||"").split("-").map(Number),y=(b||"").split("-").map(Number);if(!x[0]||!y[0])return 0;return Math.max(0,(y[0]-x[0])*12+(y[1]-x[1]))};
const expDur=(a,b)=>{const m=expMonths(a,b);if(!a||!b)return "—";const y=Math.floor(m/12),mm=m%12;return t((y?y+" سنة":"")+(y&&mm?" و ":"")+(mm?mm+" أشهر":"")||"أقل من شهر",(y?y+"y ":"")+(mm?mm+"m":"")||"<1m")};
const expBlock=()=>`  <div class="exp"><div class="exp-h"><b>${t("الخبرات السابقة","Previous experience")}</b><span class="tag n">${t("الإجمالي","Total")}: ${expTotal()}</span><button class="btn btn-ghost" data-expadd>${I("plus")}${t("إضافة خبرة","Add experience")}</button></div>
   <div class="explist">${EXPROWS.length?EXPROWS.map((x,ix)=>`<div class="expitem"><div class="expitem-h"><span class="expn num">${ix+1}</span><b>${t("خبرة","Experience")} ${ix+1}</b><span class="tag ${expDur(x[2],x[3])==="—"?"":"g"}">${t("المدة","Duration")}: ${expDur(x[2],x[3])}</span><button class="icon-btn" data-expdel="${ix}" title="${t("حذف","Delete")}">${I("trash-2")}</button></div><div class="fgrid two"><div class="fld"><label>${t("الدولة","Country")}</label><input type="text" value="${x[0]}" data-exp="${ix}:0" placeholder="${t("مثال: الكويت","e.g. Kuwait")}"></div><div class="fld"><label>${t("المهنة","Occupation")}</label><input type="text" value="${x[1]}" data-exp="${ix}:1" placeholder="${t("مثال: عاملة منزلية","e.g. Domestic worker")}"></div><div class="fld"><label>${t("من (شهر/سنة)","From (month/year)")}</label><input type="month" value="${x[2]}" data-exp="${ix}:2"></div><div class="fld"><label>${t("إلى (شهر/سنة)","To (month/year)")}</label><input type="month" value="${x[3]}" data-exp="${ix}:3"></div></div></div>`).join(""):`<div class="tempty">${I("inbox")}<b>${t("لا خبرات مضافة","No experience added")}</b><small>${t("اضغط «إضافة خبرة»","Press Add experience")}</small></div>`}</div></div>`;
const expTotal=()=>{let m=0;EXPROWS.forEach(r=>{m+=expMonths(r[2],r[3])});return t(Math.floor(m/12)+" سنة و "+(m%12)+" أشهر",Math.floor(m/12)+"y "+(m%12)+"m")};
const cvScreens={
 cvs:()=>{
 const rows=cvFiltered();
 return `
 <div class="page-head"><div><h1>${t("إدارة السير الذاتية","CV management")}</h1><p>${t(cvSubs[CVTAB][0],cvSubs[CVTAB][1])}</p></div>
 <div class="actions"><button class="btn btn-ghost" data-perm="MAIDS_EXPORT">${I("download")}${t("تصدير","Export")}</button><button class="btn btn-ghost">${I("upload")}${t("استيراد جماعي","Bulk import")}</button><button class="btn btn-primary" data-go="cvAdd" data-perm="MAIDS_CREATE">${I("plus")}${t("إضافة سيرة","Add CV")}</button></div></div>
 <div class="seg tabs" data-seg>${cvTabsL.map(x=>`<button class="${x[0]===CVTAB?"on":""}" data-cvtab="${x[0]}" data-perm="${x[4]}">${t(x[1],x[2])}<b class="num">${cvCounts[x[0]]}</b></button>`).join("")}</div>
 <div class="statgrid slim">${(cvStatsByTab[CVTAB]||[]).filter(x=>x[3]>0).map(x=>`<button class="scard slim ${x[0]===CVSTATUS?"on":""}" data-cvstat="${x[0]}"><span class="sbar ${x[4]}"></span><span class="sci ${x[4]}">${I(x[5])}</span><span class="stx"><span class="sct">${t(x[1],x[2])}</span><b class="num">${x[3]}</b></span>${x[0]===CVSTATUS?`<span class="scon">${I("check")}</span>`:""}</button>`).join("")}</div>
 <div class="fbar">
  <div class="fb grow"><label>${t("بحث","Search")}</label><div class="inp">${I("search")}<input data-cvq value="${CVQ}" placeholder="${t("اسم، جواز، رقم عاملة","Name, passport, worker no.")}"></div></div>
  <div class="fb"><label>${t("الحالة","Status")}</label><select data-cvstatus><option value="">${t("كل الحالات","All statuses")}</option>${cvSt.map(s=>`<option value="${s[0]}"${s[0]===CVSTATUS?" selected":""}>${t(s[1],s[2])}</option>`).join("")}</select></div>
  <div class="fb"><label>${t("الجنسية","Nationality")}</label><select data-cvnat><option value="">${t("كل الجنسيات","All nationalities")}</option>${cvNats.map(n=>`<option value="${n[0]}"${n[0]===CVNAT?" selected":""}>${t(n[0],n[1])}</option>`).join("")}</select></div>
  <button class="btn btn-ghost${CVMORE?" on":""}" data-cvmore>${I("sliders-horizontal")}${CVMORE?t("إخفاء الفلاتر","Hide filters"):t("فلاتر أكثر","More filters")}</button>
  ${(CVQ||CVSTATUS||CVNAT||CVJOB||CVPIN||CVVIS)?`<button class="btn btn-ghost" data-cvreset>${I("x")}${t("إزالة الفلاتر","Clear")}</button>`:""}
  <span class="empty" style="margin-inline-start:auto">${t(`${rows.length} نتيجة`,`${rows.length} results`)}</span>
  <div class="colpick"><button class="btn btn-ghost${CVCOLS.length?" on":""}" data-colpick>${I("columns-3")}${t("الأعمدة","Columns")}${CVCOLS.length?`<b class="num">${CVCOLS.length}</b>`:""}</button><div class="cmenu${CVCOLPICK?"":" hidden"}"><div class="cm-h"><b>${t("أعمدة إضافية","Extra columns")}</b><button data-colclear>${t("إفراغ","Clear")}</button></div>${cvExtraDefs.map(c=>`<label class="cm-i"><span class="cbx${CVCOLS.includes(c[0])?" on":""}">${CVCOLS.includes(c[0])?I("check"):""}</span><span>${t(c[1],c[2])}</span><input type="checkbox" data-col="${c[0]}"${CVCOLS.includes(c[0])?" checked":""} hidden></label>`).join("")}</div></div>
  <div class="vtoggle">${[["rows","list","صفوف","Rows"],["cards","layout-grid","بطاقات","Cards"]].map(v=>`<button class="${v[0]===CVVIEW?"on":""}" data-cvview="${v[0]}" title="${t(v[2],v[3])}">${I(v[1])}<span>${t(v[2],v[3])}</span></button>`).join("")}</div>
 </div>
 ${CVMORE?`<div class="filters more">
  <select data-cvjob><option value="">${t("كل المهن","All occupations")}</option>${cvJobs.map(j=>`<option value="${j}"${j===CVJOB?" selected":""}>${j}</option>`).join("")}</select>
  <select><option>${t("كل أنواع الطلب","All request types")}</option><option>${t("طلب مخصص","Custom request")}</option><option>${t("طلب مباشر","Direct request")}</option></select>
  <select><option>${t("كل المكاتب الخارجية","All external offices")}</option><option>PLATINUM BROTHERS</option><option>Supreme Link Ltd</option><option>Al Noor Manpower</option><option>Gulf Bridge Agency</option></select>
  <select data-cvvis><option value="">${t("الظهور: الكل","Visibility: all")}</option><option value="visible"${CVVIS==="visible"?" selected":""}>${t("ظاهرة","Visible")}</option><option value="hidden"${CVVIS==="hidden"?" selected":""}>${t("مخفية","Hidden")}</option></select>
  <select><option>${t("الفيديو: الكل","Video: all")}</option><option>${t("مع فيديو","With video")}</option><option>${t("بدون فيديو","Without video")}</option></select>
  <select data-cvpin><option value="">${t("التثبيت: الكل","Pinned: all")}</option><option value="only"${CVPIN==="only"?" selected":""}>${t("المثبّتة فقط","Pinned only")}</option><option value="unpinned"${CVPIN==="unpinned"?" selected":""}>${t("غير المثبّتة","Unpinned")}</option></select>
  <label class="dt"><span>${t("من","From")}</span><input type="date"></label>
  <label class="dt"><span>${t("إلى","To")}</span><input type="date"></label>
 </div>`:""}
 ${CVVIEW==="cards"?cvCardGrid(rows):""}
 ${CVVIEW==="cards"?"":`<div class="card flush">
  <div class="tw"><table class="grp"><thead><tr><th class="chk"><input type="checkbox"></th><th>${t("الصورة والوسائط","Photo & media")}</th><th>${t("بيانات العاملة","Worker data")}</th><th>${t("معلومات المهنة","Job information")}</th>${CVTAB==="rent"?`<th>${t("بيانات التأجير","Rental data")}</th>`:""}<th>${t("الحالة","Status")}</th><th>${t("تفاصيل الإضافة","Added details")}</th>${CVCOLS.map(k=>{const d=cvExtraDefs.find(x=>x[0]===k);return `<th>${t(d[1],d[2])}</th>`}).join("")}<th class="act">${t("الإجراءات","Actions")}</th></tr></thead><tbody>
  ${rows.length?rows.map(r=>`<tr>
   <td class="chk"><input type="checkbox"></td>
   <td><div class="pcell">${r[17]?`<span class="thumb">${I("user-round")}${r[18]?`<i class="play">${I("play")}</i>`:""}</span>`:`<span class="thumb none">${I("image-off")}</span>`}<div class="mbadges row">${r[17]?`<span class="mb" title="${t("صورة","Photo")}">${I("image")}</span>`:""}${r[18]?`<span class="mb g" title="${t("فيديو","Video")}">${I("video")}</span>`:""}<span class="mb" title="${t("ملف السيرة PDF","CV PDF")}">${I("file-text")}</span></div></div></td>
   <td><div class="gcell"><b class="lg">${t(r[2],r[1])}</b><small>${r[1]}</small><small><b class="num id">${r[0]}</b> · ${t("جواز","P")} <span class="num">${r[8]}</span></small><small>${t(r[3],r[4])} · ${r[9]} ${t("سنة","yrs")} · ${t(r[20],r[21])}</small></div></td>
   <td><div class="gcell"><b>${t(r[5],r[6])}</b><small>${r[7]}</small><small>${t("طلب مخصص","Custom request")}</small>${CVTAB!=="rent"?`<small class="num">${t("الراتب","Salary")}: ${r[15]} ${t("ر.س","SAR")}</small>`:""}</div></td>
   ${CVTAB==="rent"?`<td><div class="gcell"><b class="num">${r[15]} ${t("ر.س/شهر","SAR/mo")}</b><small>${t("مدة التأجير","Rental duration")}: ${r[19]||"—"}</small></div></td>`:""}
   <td><div class="gcell"><span class="st tag ${r[13]}"><i></i>${t(r[11],r[12])}</span>${r[17]?`<small class="ok">${t("ظاهرة في الموقع","Visible on site")}</small>`:`<small class="off">${t("مخفية","Hidden")}</small>`}</div></td>
   <td><div class="gcell"><small class="num">${r[14]}</small><small>${t("أضافها","By")}: ${t("مشرف النظام","Supervisor")}</small></div></td>
   ${CVCOLS.map(k=>`<td><div class="gcell x">${cvExtra(r,k)}</div></td>`).join("")}
   <td class="act">${rowActs(r)}</td></tr>`).join(""):`<tr><td colspan="${8+CVCOLS.length}"><div class="tempty">${I("inbox")}<b>${t("لا نتائج مطابقة","No matching results")}</b><small>${t("جرّب إزالة الفلاتر","Try clearing the filters")}</small></div></td></tr>`}
  </tbody></table></div>
  <div class="tfoot"><span>${t(`عرض ${rows.length} من ${cvCounts[CVTAB]} — الصفحة 1`,`Showing ${rows.length} of ${cvCounts[CVTAB]} — page 1`)}</span><div class="pager"><button>${t("السابق","Prev")}</button><button class="on">1</button><button>2</button><button>${t("التالي","Next")}</button></div></div>
 </div>`}`},
 cvAdd:()=>{
 const rent=CVTYPE==="rent",full=CVMODE==="full";
 const steps=full?[["نوع السيرة والتسكين","Type & housing"],["البيانات الأساسية","Core data"],["الجواز والهوية","Passport & ID"],["الخبرة واللغات والملفات","Experience, languages & files"]]:[["بيانات السيرة المختصرة","Short CV data"]];
 if(rent)steps.push(["بنود التأجير المالية","Rental finance items"]);
 const S=i=>t(steps[i][0],steps[i][1]);
 return `
 <div class="page-head"><div><a class="back" href="#" ${CVEDIT?`data-cvshow="${CVEDIT}"`:'data-go="cvs"'} style="margin:0 0 8px">${I("arrow-right")}<span>${CVEDIT?t("الرجوع للتفاصيل","Back to details"):t("إدارة السير الذاتية","CV management")}</span></a><h1>${CVEDIT?t("تعديل السيرة","Edit CV"):t("إضافة سيرة ذاتية","Add CV")}${CVEDIT?`<span class="tag a hdrtag">${I("pencil")}${t("تعديل","Editing")}</span>`:""}</h1>${CVEDIT?`<p>${(()=>{const r=cvRows.find(x=>x[0]===CVEDIT);return r?`${t(r[2],r[1])} · <span class="num">${r[0]}</span> · ${t("جواز","P")} <span class="num">${r[8]}</span>`:""})()}</p>`:""}</div>
 <div class="actions"><label class="check"><input type="checkbox" data-cvnames ${CVNAMES?"checked":""}><span>${t("عرض أسماء الحقول","Show field names")}</span></label></div></div>
 <div class="cvform">
  <div class="card cvopts">
  <div class="cvbar-g"><span>${t("النوع","Type")}</span><div class="seg">${[["rec","استقدام","Recruitment"],["rent","تأجير","Rental"]].map(x=>`<button class="${x[0]===CVTYPE?"on":""}" data-cvtype="${x[0]}">${t(x[1],x[2])}</button>`).join("")}</div></div>
  <div class="cvbar-g"><span>${t("التفصيل","Detail")}</span><div class="seg">${[["short","مختصرة","Short"],["full","كاملة","Full"]].map(x=>`<button class="${x[0]===CVMODE?"on":""}" data-cvmode="${x[0]}">${t(x[1],x[2])}</button>`).join("")}</div></div>
  ${rent?`<div class="cvbar-g"><span>${t("موقع العاملة","Location")}</span><div class="seg">${[["inside","داخل المملكة","Inside"],["outside","خارج المملكة","Outside"]].map(x=>`<button class="${x[0]===CVKINGDOM?"on":""}" data-cvkingdom="${x[0]}">${t(x[1],x[2])}</button>`).join("")}</div></div>`:""}
  <span class="empty">${full?t("النموذج الكامل — 5 أقسام","Full form — 5 sections"):t("8 حقول فقط ثم يفتح إكمال السيرة","8 fields, then completion page")}</span>
  <div class="steps">${steps.map((s,i)=>`<button class="step ${i===0?"on":""}" data-step="${i}"><span class="num">${i+1}</span><b>${t(s[0],s[1])}</b></button>`).join("")}</div>
  <div class="cvbar-g col"><span>${t("إجراءات السيرة","CV actions")}</span>
   <div class="actrow sm"><div><b>${t("الظهور في الموقع","Visible on site")}</b>${CVNAMES?"<small>maid_visibility</small>":""}</div><span class="sw on"></span></div>
   <div class="actrow sm"><div><b>${t("تثبيت السيرة","Pin CV")}</b>${CVNAMES?"<small>is_pinned</small>":""}</div><span class="sw"></span></div>
   <div class="actrow sm col"><label>${t("الحالة المبدئية","Initial status")}${CVNAMES?" <code>status</code>":""}</label><select class="statsel">${cvSt.map(x=>`<option${x[0]==="available"?" selected":""}>${t(x[1],x[2])}</option>`).join("")}</select></div>
  </div>
  </div>
  <div class="card cvfields">
  ${full?`
  ${fsec(1,S(0))}
  ${fg([...(rent?[["تسكين للإيواء (إنشاء عقد إيواء)","Shelter assignment","create_shelter_contract","select",["لا","نعم — إنشاء عقد إيواء"],0]]:[]),["الفرع","Branch","branch_id","select",cvBranches,0],["نوع الطلب","Request type","request_type","select",["معروفة (متاحة للجميع)","معينة (مخصصة لعميل)"],0]])}
  ${fsec(2,S(1))}
  ${fg([["الاسم بالإنجليزية","Name (EN)","name:en","text","MARY GRACE SANTOS",1],["الجنسية","Nationality","nationality_id","select",cvNats.map(n=>n[0]),1],["المكتب الخارجي","External office","office_id","select",["PLATINUM BROTHERS","Supreme Link Ltd"],0],["المهنة","Occupation","job_id[]","select",["عاملة منزلية","سائق خاص","طباخة"],1],["الجنس","Gender","gender","select",["أنثى","ذكر"],1],["الديانة","Religion","religion_id","select",["مسلمة","مسيحية","هندوسية","بوذية"],0],["الحالة الاجتماعية","Marital status","marital_status:ar","select",["عازبة","متزوجة","أرملة"],1],["تاريخ الميلاد","Birth date","birth_date","date","",1],["العمر","Age","age","ro",t("يُحسب من تاريخ الميلاد","Computed from birth date"),0],["الراتب الشهري","Salary","salary","text","1,200",0],["مدة العقد","Contract duration","duration","text","24 شهراً",0]].concat(rent&&CVKINGDOM==="inside"?[["تاريخ الوصول","Arrival date","arrival_date","date","",0]]:[]))}
  ${fsec(3,S(2))}
  ${fg([["رقم جواز العاملة","Passport no.","passport_num","text","P1234567",1],["رقم الحدود","Border number","border_number","text","",0],["تاريخ إصدار الجواز","Issue date","passport_issue_date","date","",0],["مكان الإصدار","Issue place","passport_issue_place","text","",0],["تاريخ انتهاء الجواز","Expiry date","passport_expiry_date","date","",0],["رقم الهوية","ID number","id_number","text",rent?t("اختياري للتأجير","Optional for rental"):"",0],["تاريخ انتهاء الهوية","ID expiry","id_expiry_date","date","",0]])}
  ${fsec(4,S(3))}
  <div class="fgrid"><div class="fld"><label>${t("حالة الخبرة","Experience status")}<i class="rq">*</i>${CVNAMES?" <code>experience_status</code>":""}</label><div class="seg wide">${[["has","لديها خبرة","Has experience"],["no","بدون خبرة","No experience"]].map(x=>`<button class="${x[0]===EXPST?"on":""}" data-expst="${x[0]}">${t(x[1],x[2])}</button>`).join("")}</div></div></div>
  <div class="msec"><div class="msec-h">${I("sparkles")}<b>${t("المهارات","Skills")}</b>${CVNAMES?"<code>skills[]</code>":""}<span class="empty">${t(`مختار ${SKILLS.length} من ${cvAllSkills.length}`,`${SKILLS.length} of ${cvAllSkills.length} selected`)}</span></div>
   <div class="pickgrid">${cvAllSkills.map(k=>`<button class="pick${SKILLS.includes(k[0])?" on":""}" data-skill="${k[0]}"><span class="cbx">${SKILLS.includes(k[0])?I("check"):""}</span>${I(k[2])}<span>${t(k[0],k[1])}</span></button>`).join("")}</div></div>
  <div class="msec"><div class="msec-h">${I("languages")}<b>${t("اللغات والتقييم","Languages & rating")}</b><span class="empty" style="margin-inline-start:auto">${t("طريقة التقييم","Rating method")}</span><div class="seg sm">${[["pct","نسبة %","Percent"],["lvl","مستوى","Level"]].map(x=>`<button class="${x[0]===LANGMODE?"on":""}" data-langmode="${x[0]}">${t(x[1],x[2])}</button>`).join("")}</div></div>
   <div class="langpick">${cvAllLangs.map(l=>{const on=LANGS[l[0]]!==undefined,v=LANGS[l[0]]||0;return `<div class="lrow${on?" on":""}"><button class="lchk" data-lang="${l[0]}"><span class="cbx">${on?I("check"):""}</span><b>${t(l[0],l[1])}</b></button>${on?(LANGMODE==="pct"?`<div class="lctl"><input type="range" min="0" max="100" step="10" value="${v}" data-langpct="${l[0]}"><b class="num">${v}%</b></div>`:`<div class="lctl"><div class="seg xs">${cvLevels.map(x=>`<button class="${x[2]===v?"on":""}" data-langlvl="${l[0]}:${x[2]}">${t(x[0],x[1])}</button>`).join("")}</div></div>`):`<span class="empty">${t("غير مختارة","Not selected")}</span>`}</div>`}).join("")}</div></div>
  ${EXPST==="has"?expBlock():`<div class="cvnote">${I("info")}<span>${t("لا يظهر جدول الخبرات لأن الحالة «بدون خبرة».","The experience table is hidden because status is “no experience”.")}</span></div>`}
  <div class="msec"><div class="msec-h">${I("clipboard-list")}<b>${t("معلومات إضافية عن العاملة","Additional worker information")}</b></div>
   ${fg([["الطول (سم)","Height (cm)","height","text","165",0],["الوزن (كجم)","Weight (kg)","weight","text","58",0],["عدد الأطفال","Children","children_count","text","2",0],["المستوى التعليمي","Education","education_level","select",["ابتدائي","متوسط","ثانوي","دبلوم","جامعي"],0],["تجيد القراءة والكتابة","Literate","is_literate","select",["نعم","لا"],0],["تدخين","Smoking","smoking","select",["لا","نعم"],0],["حالة صحية تستدعي الذكر","Health notes","health_notes","text",t("لا يوجد","None"),0],["ملاحظات إضافية","Additional notes","additional_notes","text",t("أي ملاحظة تظهر للعميل","Any note shown to the client"),0]])}</div>
  <div class="msec"><div class="msec-h">${I("images")}<b>${t("الوسائط والمرفقات","Media & attachments")}</b><span class="empty">${t("الصورة الشخصية إلزامية — الباقي اختياري","Personal photo required — the rest optional")}</span></div>
   <div class="msgrid up">
    <button class="mtile up"><span class="mimg">${I("user-round")}</span><b>${t("الصورة الشخصية","Personal photo")}${CVNAMES?" <code>image</code>":""}<i class="rq">*</i></b><small>${t("اسحب الصورة أو اضغط للاختيار — JPG · PNG","Drop a photo or click — JPG · PNG")}</small></button>
    <button class="mtile up"><span class="mimg">${I("id-card")}</span><b>${t("صورة الجواز / الصورة الكاملة","Passport / full photo")}${CVNAMES?" <code>cv_file</code>":""}</b><small>${t("JPG · PNG · PDF","JPG · PNG · PDF")}</small></button>
    <button class="mtile up"><span class="mimg red">${I("file-text")}</span><b>${t("ملف السيرة PDF","CV file PDF")}${CVNAMES?" <code>maid_cv_file</code>":""}</b><small>${t("ملف المكتب الخارجي","External office file")}</small></button>
    <button class="mtile up"><span class="mimg">${I("video")}</span><b>${t("الفيديو التعريفي","Intro video")}${CVNAMES?" <code>video</code>":""}</b><small>${t("ملف MP4 أو رابط","MP4 file or link")}</small></button>
   </div>
   ${fg([["رابط الفيديو","Video link","video_link","text","https:// أو iframe",0],["ملاحظات إضافية","Additional notes","additional_notes","text","",0]])}
  </div>

`:`
  ${fsec(1,S(0))}
  ${fg([["الاسم بالإنجليزية","Name (EN)","name:en","text","MARY GRACE SANTOS",0],["الجنس","Gender","gender","select",["أنثى","ذكر"],1],["الجنسية","Nationality","nationality_id","select",cvNats.map(n=>n[0]),1],["الديانة","Religion","religion_id","select",["مسلمة","مسيحية","هندوسية"],1],["المهنة","Occupation","job_id[]","select",["عاملة منزلية","سائق خاص","طباخة"],1],["المكتب الخارجي","External office","office_id","select",["PLATINUM BROTHERS","Supreme Link Ltd"],1],["رقم جواز العاملة","Passport no.","passport_num","text","P1234567",1],["ملف الجواز","Passport file","cv_file","file","PDF · JPG",0]])}
  <div class="msec"><div class="msec-h">${I("images")}<b>${t("الوسائط","Media")}</b><span class="empty">${t("اختيارية في السيرة المختصرة","Optional in the short CV")}</span></div>
   <div class="msgrid up">
    <button class="mtile up"><span class="mimg">${I("user-round")}</span><b>${t("الصورة الشخصية","Personal photo")}${CVNAMES?" <code>image</code>":""}</b><small>JPG · PNG</small></button>
    <button class="mtile up"><span class="mimg red">${I("file-text")}</span><b>${t("ملف الجواز PDF","Passport PDF")}${CVNAMES?" <code>cv_file</code>":""}</b><small>PDF</small></button>
   </div></div>
  <div class="cvnote">${I("info")}<span>${t("يتحقق النظام من تكرار رقم الجواز أثناء الكتابة.","Passport duplication is checked as you type.")}</span></div>`}
  ${rent?`${fsec(steps.length,S(steps.length-1))}
  <div class="rfin">
   <div class="tw"><table><thead><tr><th>${t("البند","Item")}</th><th>${t("المبلغ (ر.س)","Amount (SAR)")}</th><th>${t("التكرار","Recurrence")}</th><th></th></tr></thead><tbody>
   <tr><td>${t("أجرة التأجير الشهرية","Monthly rental fee")}</td><td class="num">1,250</td><td>${t("شهري","Monthly")}</td><td><button class="icon-btn">${I("trash-2")}</button></td></tr>
   <tr><td>${t("تأمين مسترد","Refundable deposit")}</td><td class="num">1,000</td><td>${t("مرة واحدة","One-time")}</td><td><button class="icon-btn">${I("trash-2")}</button></td></tr>
   </tbody></table></div>
   <div class="rfin-f">${I("alert-triangle")}<span>${t("بنود التأجير مطلوبة قبل الحفظ لعاملة التأجير — تُربط بعقد التأجير تلقائياً.","Rental items are required before saving a rental worker — they link to the rental contract automatically.")}</span><button class="btn btn-ghost">${I("plus")}${t("إضافة بند","Add item")}</button></div>
  </div>`:""}
  <div class="cvactions"><button class="btn btn-primary">${I("save")}${t("حفظ السيرة","Save CV")}</button><button class="btn btn-ghost" data-go="cvs">${t("إلغاء","Cancel")}</button><span class="empty">${t("الحقول المعلّمة * إلزامية","* required")}</span></div>
  </div>
 </div>`}
};
function fsec(n,title){return `<div class="fs-h"><span class="fs-n num">${n}</span><b>${title}</b></div>`}
const cvEditVals=()=>{const r=cvRows.find(x=>x[0]===CVEDIT);if(!r)return{};const i=cvRows.indexOf(r);return {"name:en":r[1],"nationality_id":r[3],"office_id":r[7],"job_id[]":r[5],"religion_id":r[20],"salary":r[15],"duration":r[19]||"24 شهراً","passport_num":r[8],"border_number":String(4400+i*137)+String(1000+i*7),"gender":r[5]==="سائق خاص"?"ذكر":"أنثى","marital_status:ar":i%3===0?"عازبة":i%3===1?"متزوجة":"أرملة","request_type":"طلب مخصص","branch_id":cvBranches[i%3]}};
function fg(fs){const EV=CVEDIT?cvEditVals():{};return `<div class="fgrid">${fs.map(f=>{
 const [ar,en,nm,type,val,req]=f;const ev=EV[nm];
 const nmc=CVNAMES?`<code>${nm}</code>`:"";
 let ctl;
 if(type==="select"){const opts=(Array.isArray(val)?val:[val]);const all=ev&&!opts.includes(ev)?[ev].concat(opts):opts;ctl=`<select>${ev?"":`<option value="">${t("اختر","Choose")}</option>`}${all.map(o=>`<option${o===ev?" selected":""}>${o}</option>`).join("")}</select>`}
 else if(type==="file")ctl=`<label class="filebox">${I("upload")}<span>${val||t("اختر ملفاً","Choose a file")}</span><input type="file" hidden></label>`;
 else if(type==="date")ctl=`<input type="date">`;
 else if(type==="ro")ctl=`<input type="text" value="" placeholder="${val}" readonly>`;
 else ctl=`<input type="text" ${ev?`value="${ev}"`:`placeholder="${val||""}"`}${["name:en","passport_num","border_number","salary","phone"].includes(nm)?' dir="ltr"':""}>`;
 return `<div class="fld"><label>${t(ar,en)}${req?'<i class="rq">*</i>':""}${nmc}</label>${ctl}</div>`}).join("")}</div>`}
let CVID="A-118742";
let CVPREV=null;
function openPrev(){
 var r=cvRows.find(function(x){return x[0]===CVPREV})||cvRows[0];var i=cvRows.indexOf(r);
 var ov=document.getElementById("cvPrevOv");
 if(!ov){ov=document.createElement("div");ov.id="cvPrevOv";document.body.appendChild(ov)}
 ov.className="ov";
 var kv=[[t("الجنسية","Nationality"),t(r[3],r[4])],[t("المهنة","Occupation"),t(r[5],r[6])],[t("الديانة","Religion"),t(r[20],r[21])],[t("العمر","Age"),r[9]+" "+t("سنة","yrs")],[t("سنوات الخبرة","Experience"),(2+(i%7))+" "+t("سنوات","yrs")],[t("الراتب الشهري","Monthly salary"),r[15]+" "+t("ر.س","SAR")]];
 var lg=[[t("العربية","Arabic"),60+(i%4)*10],[t("الإنجليزية","English"),50+(i%5)*10]];
 var pd=[[t("الحالة الاجتماعية","Marital status"),i%3===0?t("عازبة","Single"):i%3===1?t("متزوجة","Married"):t("مطلقة","Divorced")],[t("عدد الأطفال","Children"),String(i%4)],[t("الطول","Height"),"165 "+t("سم","cm")],[t("الوزن","Weight"),"58 "+t("كجم","kg")],[t("المستوى التعليمي","Education"),t("ثانوي","Secondary")],[t("مدة العقد","Duration"),r[19]||t("24 شهراً","24 months")]];
 var cts=[["phone","920001234"],["message-circle","+966 55 000 1122"],["globe","clickandmore.sa"]];
 var kvH=kv.map(function(x){return '<div><small>'+x[0]+'</small><b>'+x[1]+'</b></div>'}).join("");
 var lgH=lg.map(function(l){return '<div class="ring" style="--p:'+l[1]+'"><span class="num">'+l[1]+'%</span><small>'+l[0]+'</small></div>'}).join("");
 var pdH=pd.map(function(x){return '<div><small>'+x[0]+'</small><b>'+x[1]+'</b></div>'}).join("");
 var skH=cvSkillSets[i%5].concat([t("الكوي","Ironing")]).map(function(x){return '<span class="skill">'+I("star")+x+'</span>'}).join("");
 var ctH=cts.map(function(x){return '<div class="cline">'+I(x[0])+'<span class="num">'+x[1]+'</span></div>'}).join("");
 ov.innerHTML='<div class="modal cvmodal"><div class="modal-h"><h3>'+t("نموذج السيرة الذاتية","CV template")+'</h3><div class="mh-a"><button class="btn btn-ghost">'+I("printer")+t("طباعة","Print")+'</button><button class="btn btn-red">'+I("file-text")+'PDF</button><button class="icon-btn" data-prevclose>'+I("x")+'</button></div></div>'
  +'<div class="cvtpl"><aside class="tpl-l"><span class="tpl-ph">'+(r[17]?I("user-round"):I("image-off"))+'</span><div class="tpl-k">'+kvH+'</div><div class="tpl-lang"><b>'+t("اللغات","Languages")+'</b><div class="rings">'+lgH+'</div></div></aside>'
  +'<div class="tpl-r"><div class="tpl-hd"><div><b>'+r[1]+'</b><small>'+t("رقم الجواز","Passport")+': <span class="num">'+r[8]+'</span> · '+t("رقم العاملة","Worker no.")+': <span class="num">'+r[0]+'</span></small></div><img src="assets/logo-light-bg.png" alt="Click and More"></div>'
  +'<div class="tpl-sec"><h4>'+t("البيانات الشخصية","Personal data")+'</h4><div class="tpl-grid">'+pdH+'</div></div>'
  +'<div class="tpl-sec"><h4>'+t("المهارات","Skills")+'</h4><div class="skills">'+skH+'</div></div>'
  +'<div class="tpl-sec"><h4>'+t("تواصل معنا","Contact us")+'</h4><div class="tpl-c"><span class="qr">'+I("qr-code")+'</span><div>'+ctH+'</div></div></div></div></div></div>';
 lucide.createIcons({attrs:{"stroke-width":1.8}});
 ov.onclick=function(ev){if(ev.target===ov||ev.target.closest("[data-prevclose]"))ov.classList.add("hidden")};
}
const cvShowStats=r=>[["إجمالي الطلبات","Total orders","clipboard-list","n",r[16]==="rent"?3:6],["الطلبات النشطة","Active orders","list-checks","a",r[16]==="rent"?1:2],["عقود الاستقدام","Recruitment contracts","file-signature","n",r[16]==="rent"?0:2],["العقود النشطة","Active contracts","clipboard-check","n",r[10]==="contracted"?1:0],["سنوات الخبرة","Experience years","graduation-cap","g","4.6"],["متاح من","Available from","calendar-check","",r[10]==="available"?"2026-08-18":"—"]];
const kvs=fs=>`<div class="kvgrid">${fs.map(f=>`<div class="kv"><span>${t(f[0],f[1])}</span><b>${f[2]}</b></div>`).join("")}</div>`;
const sect=(icon,ar,en,inner,id)=>`<div class="card sec"${id?` id="${id}"`:""}><div class="sec-h">${I(icon)}<b>${t(ar,en)}</b></div>${inner}</div>`;
const cvShowTabs=[["over","نظرة عامة","Overview","gauge"],["prof","الملف الشخصي","Profile","user-circle"],["media","الوسائط والمرفقات","Media & files","paperclip"],["orders","الطلبات والعقود","Orders & contracts","file-signature"],["fin","البنود المالية","Financial items","wallet"],["office","المكتب الخارجي","External office","building"],["log","سجل النشاط","Activity log","history"]];
cvScreens.cvShow=()=>{
 const r=cvRows.find(x=>x[0]===CVID)||cvRows[0];const rent=r[16]==="rent";const i=cvRows.indexOf(r);
 const tabs=cvShowTabs.filter(x=>x[0]!=="fin"||rent);
 const body={
  over:()=>`
   ${sect("gauge","ملخص الحالة","Status summary",`<div class="statgrid four">${cvShowStats(r).slice(0,4).map(x=>`<div class="scard st6"><span class="sci ${x[3]}">${I(x[2])}</span><span class="sct">${t(x[0],x[1])}</span><b class="num">${x[4]}</b></div>`).join("")}</div>`)}
   ${sect("id-card","البطاقة السريعة","Quick card",kvs([["الجنسية","Nationality",t(r[3],r[4])],["المهنة","Occupation",t(r[5],r[6])],["العمر","Age",r[9]],["الديانة","Religion",t(r[20],r[21])],["رقم الجواز","Passport",`<span class="num">${r[8]}</span>`],["انتهاء الجواز","Passport expiry",cvExtra(r,"pexp")],["المكتب الخارجي","External office",r[7]],[rent?"أجرة التأجير":"الراتب",rent?"Rental fee":"Salary",`<span class="num">${r[15]}</span>`],["سنوات الخبرة","Experience",cvExtra(r,"exp")],["الفرع","Branch",cvExtra(r,"branch")],["تاريخ الوصول","Arrival",cvExtra(r,"arr")],["آخر تحديث","Last update",cvExtra(r,"upd")]]))}
   ${sect("bell","تنبيهات ومتابعة","Alerts & follow-up",`<div class="alerts">${[[r[10]==="available"?"a":"g",r[10]==="available"?"لم تُحجز بعد":"مرتبطة بعقد نشط",r[10]==="available"?"Not reserved yet":"Linked to an active contract",r[10]==="available"?"معروضة في الموقع منذ 12 يوماً":"CN-2026-0"+(100+i)],["a","الجواز ينتهي بعد أقل من سنة","Passport expires within a year",""],["n","الفيديو التعريفي "+(r[18]?"مرفوع":"غير مرفوع"),"Intro video "+(r[18]?"uploaded":"missing"),""]].map(x=>`<div class="alert ${x[0]}">${I("dot")}<div><b>${t(x[1],x[2])}</b>${x[3]?`<small>${x[3]}</small>`:""}</div></div>`).join("")}</div>`)}`,
  prof:()=>`
   ${sect("user-circle","البيانات الشخصية","Personal data",kvs([["الاسم بالإنجليزية","Name (EN)",r[1]],["الاسم بالعربية","Name (AR)",r[2]],["تاريخ الميلاد","Birth date",`<span class="num">${1994-(i%6)}-0${1+(i%9)}-1${i%9}</span>`],["العمر","Age",r[9]],["الحالة الاجتماعية والأطفال","Marital & children",cvExtra(r,"marital")],["الطول والوزن","Height & weight","165 سم · 58 كجم"],["المستوى التعليمي","Education",t("ثانوي","Secondary")],["الديانة","Religion",t(r[20],r[21])]]))}
   ${sect("passport","الجواز والهوية","Passport & ID",kvs([["رقم الجواز","Passport no.",`<span class="num">${r[8]}</span>`],["رقم الحدود","Border no.",cvExtra(r,"border")],["انتهاء الجواز","Passport expiry",cvExtra(r,"pexp")],["مكان الإصدار","Issue place",t(r[3],r[4])],["الهوية وانتهاؤها","ID & expiry",cvExtra(r,"idn")],["مدة العقد","Duration",cvExtra(r,"dur")]]))}
   ${sect("sparkles","المهارات","Skills",`<div class="skills">${cvSkillSets[i%5].concat([t("الكوي","Ironing"),t("كبار السن","Elderly care")]).map(x=>`<span class="skill">${I("star")}${x}</span>`).join("")}</div>`)}
   ${sect("languages","اللغات","Languages",`<div class="langs one">${[[t("العربية","Arabic"),60+(i%4)*10],[t("الإنجليزية","English"),50+(i%5)*10],[t(r[3],r[4]),100]].map(l=>`<div class="lang-row on"><span class="ln">${l[0]}</span><span class="pct"><i style="width:${l[1]}%"></i></span><b class="num">${l[1]}%</b></div>`).join("")}</div>`)}
   ${sect("clock","سجل الخبرات","Experience timeline",`<div class="tl">${[["2021-03 → 2023-09",t("الكويت","Kuwait"),t("عاملة منزلية · سنتان و 6 أشهر","Domestic worker · 2y 6m")],["2019-01 → 2021-02",t("الأردن","Jordan"),t("طباخة · سنتان وشهر","Cook · 2y 1m")]].map(x=>`<div class="tli"><span class="dot"></span><div><b>${x[1]}</b><small>${x[2]}</small><small class="num">${x[0]}</small></div></div>`).join("")}</div>`)}`,
  media:()=>`
   ${sect("images","الصور والملفات","Photos & files",`<div class="msgrid">
    <button class="mtile${r[17]?"":" empty"}" data-cvprev="${r[0]}"><span class="mimg">${r[17]?I("user-round"):I("image-off")}</span><b>${t("الصورة الشخصية","Personal photo")}</b><small>${r[17]?"image · JPG":t("غير مرفوعة","Not uploaded")}</small></button>
    <button class="mtile${r[17]?"":" empty"}" data-cvprev="${r[0]}"><span class="mimg">${I("id-card")}</span><b>${t("صورة الجواز","Passport photo")}</b><small>${r[17]?"cv_file · JPG":t("غير مرفوعة","Not uploaded")}</small></button>
    <button class="mtile${r[18]?"":" empty"}" data-cvprev="${r[0]}"><span class="mimg${r[18]?" vid":""}">${r[18]?I("play"):I("video-off")}</span><b>${t("الفيديو التعريفي","Intro video")}</b><small>${r[18]?"video · MP4":t("لا يوجد فيديو","No video")}</small></button>
    <button class="mtile pdf" data-cvprev="${r[0]}"><span class="mimg red">${I("file-text")}</span><b>${t("ملف السيرة PDF","CV file PDF")}</b><small>${t("عرض داخل الموقع","Preview inside site")}</small></button>
   </div>`)}
   ${sect("file-text","نموذج السيرة للطباعة والمشاركة","CV template",`<div class="pdfslot"><div class="pdfslot-t"><b>${t("النموذج المعتمد","Approved template")}</b><small>${t("نفس النموذج الذي يُصدَّر PDF ويُشارك عبر واتساب","The same template exported as PDF and shared on WhatsApp")}</small></div><div class="pdfslot-a"><button class="btn btn-primary" data-cvprev="${r[0]}">${I("eye")}${t("عرض النموذج","Open template")}</button><button class="btn btn-ghost">${I("printer")}${t("طباعة","Print")}</button><button class="btn btn-ghost">${I("download")}PDF</button></div></div>`)}
   ${sect("paperclip","المرفقات","Attachments",`
    <div class="att hi"><div class="att-t">${I("file-down")}<div><b>${t("ملف السيرة المحفوظ","Generated CV file")}</b><small>${t("جاهز للتنزيل والمشاركة","Ready to download and share")}</small></div></div><div class="att-a"><button class="btn btn-red">${I("file-text")}${t("تنزيل PDF","Download PDF")}</button><button class="btn btn-green">${I("share-2")}${t("واتساب","WhatsApp")}</button></div></div>
    <div class="att"><div class="att-t">${I("external-link")}<div><b>${t("ملف سيرة المكتب الخارجي","External office CV")}</b><small>maid_cv_file</small></div></div><div class="att-a"><button class="btn btn-ghost" data-cvprev="${r[0]}">${I("eye")}${t("عرض","Preview")}</button><button class="btn btn-ghost">${I("download")}${t("تنزيل","Download")}</button></div></div>
    <div class="upl" data-perm="MAIDS_EDIT"><label>${t("إضافة مرفق (وصف اختياري ثم الملفات)","Add attachment (optional title, then files)")}</label><input type="text" placeholder="${t("مثال: صورة الجواز","e.g. Passport photo")}"><div class="ctl file">${I("upload")}<span>${t("اختر الملفات — يمكن تحديد أكثر من ملف","Select files — multiple allowed")}</span></div><button class="btn btn-primary">${I("upload")}${t("رفع المرفقات","Upload")}</button></div>`)}`,
  orders:()=>`
   ${sect("clipboard-list","سجل الطلبات","Orders history",`<div class="tw"><table><thead><tr><th>#</th><th>${t("الحالة","Status")}</th><th>${t("المهنة","Job type")}</th><th>${t("العميل","Client")}</th><th>${t("تاريخ الإنشاء","Created at")}</th></tr></thead><tbody>
    <tr><td class="num">OR-4412</td><td><span class="tag n">${t("مكتملة","Completed")}</span></td><td>${t(r[5],r[6])}</td><td>${t("منيرة الشهري","Munira Alshehri")}</td><td class="num">2026-08-02 10:14</td></tr>
    <tr><td class="num">OR-4380</td><td><span class="tag a">${t("تحت الإجراء","In progress")}</span></td><td>${t(r[5],r[6])}</td><td>${t("شركة نجد","Najd Co.")}</td><td class="num">2026-07-28 09:02</td></tr>
    </tbody></table></div>`)}
   ${sect("file-signature","سجل العقود","Contracts history",`<div class="tw"><table><thead><tr><th>#</th><th>${t("الحالة","Status")}</th><th>${t("العميل","Client")}</th><th>${t("تاريخ العقد","Contract date")}</th><th>${t("قيمة العقد","Value")}</th></tr></thead><tbody>
    ${r[10]==="contracted"||rent?`<tr><td class="num">CN-2026-0${100+i}</td><td><span class="tag n">${t("تحت الضمان","Under warranty")}</span></td><td>${t("منيرة الشهري","Munira Alshehri")}</td><td class="num">2026-08-04</td><td class="num">18,500.00</td></tr>`:`<tr><td colspan="5"><div class="tempty">${I("inbox")}<b>${t("لا سجلات","No records")}</b></div></td></tr>`}
    </tbody></table></div>`)}`,
  fin:()=>sect("wallet","البنود المالية","Financial items",`<div class="fin3">${[["إجمالي الإيرادات","Total income","7,500","g"],["إجمالي المصروفات","Total expenses","2,150","r"],["الصافي","Net","5,350","n"]].map(x=>`<div class="fin"><small>${t(x[0],x[1])}</small><b class="num ${x[3]}">${x[2]}</b></div>`).join("")}</div>
   <div class="tw"><table><thead><tr><th>${t("النوع","Type")}</th><th>${t("البند","Item")}</th><th>${t("المبلغ","Amount")}</th><th>${t("الحالة","Status")}</th></tr></thead><tbody>
   <tr><td><span class="tag g">${t("إيراد","Income")}</span></td><td>${t("أجرة شهر أغسطس","August rental fee")}</td><td class="num">1,300.00</td><td><span class="tag n">${t("سُدّد 2026-08-05","Settled 2026-08-05")}</span></td></tr>
   <tr><td><span class="tag r">${t("مصروف","Expense")}</span></td><td>${t("تأمين طبي","Medical insurance")}</td><td class="num">450.00</td><td><span class="tag">${t("غير مسدّد","Unsettled")}</span></td></tr>
   </tbody></table></div>`),
  office:()=>sect("building","بيانات المكتب الخارجي","External office data",kvs([["المكتب","Office",r[7]],["الدولة","Country",t(r[3],r[4])],["الخدمة","Service",t("طلب مخصص","Custom request")],["مسؤول التواصل","Contact person",t("قسم التشغيل","Operations desk")],["الهاتف","Phone",'<span class="num">+63 917 000 1122</span>'],["عدد السير من هذا المكتب","CVs from this office",`<span class="num">${18+i}</span>`]])),
  log:()=>sect("history","سجل النشاط","Activity log",`<div class="tl">${[["2026-08-17 09:12",t("أُضيفت السيرة","CV created"),t("مشرف النظام","Supervisor")],["2026-08-17 10:04",t("رُفعت الصورة الشخصية","Photo uploaded"),t("مشرف النظام","Supervisor")],["2026-08-18 08:30",t("تغيّرت الحالة إلى ","Status changed to ")+t(r[11],r[12]),t("سارة القحطاني","Sara Q.")]].map(x=>`<div class="tli"><span class="dot"></span><div><b>${x[1]}</b><small>${x[2]}</small><small class="num">${x[0]}</small></div></div>`).join("")}</div>`)
 };
 return `
 <div class="page-head compact"><div><a class="back" href="#" data-go="cvs" style="margin:0 0 8px">${I("arrow-right")}<span>${t("الرجوع للقائمة","Back to list")}</span></a><h1>${t(r[2],r[1])}</h1><p>${r[0]} · ${t(r[3],r[4])} · ${t(r[5],r[6])}</p></div>
 <div class="actions"><button class="btn btn-primary" data-cvedit="${r[0]}" data-perm="MAIDS_EDIT">${I("pencil")}${t("تعديل السيرة","Edit CV")}</button><button class="btn btn-ghost" data-cvprev="${r[0]}">${I("file-text")}${t("نموذج السيرة","CV template")}</button></div></div>
 <div class="showgrid3">
  <aside class="cvside">
   <div class="card idcard">
    ${r[17]?`<span class="bigph">${I("user-round")}${r[18]?`<i class="play">${I("play")}</i>`:""}</span>`:`<button class="bigph pdf" data-cvprev="${r[0]}">${I("file-text")}<small>PDF</small></button>`}
    <b>${t(r[2],r[1])}</b><small>${r[1]}</small>
    <span class="st tag ${r[13]}"><i></i>${t(r[11],r[12])}</span>
    <div class="idk">${[[t("الجنسية","Nationality"),t(r[3],r[4])],[t("المهنة","Occupation"),t(r[5],r[6])],[t("العمر","Age"),r[9]],[rent?t("أجرة التأجير","Rental fee"):t("الراتب","Salary"),r[15]]].map(x=>`<div><small>${x[0]}</small><b>${x[1]}</b></div>`).join("")}</div>
    <div class="mbadges row">${r[17]?`<span class="mb">${I("image")}</span>`:""}${r[18]?`<span class="mb g">${I("video")}</span>`:""}<span class="mb">${I("file-text")}</span></div>
   </div>
   <div class="card acard">
    <div class="sec-h">${I("settings-2")}<b>${t("إجراءات السيرة","CV actions")}</b></div>
    <div class="actrow"><div><b>${t("الظهور في الموقع","Visible on site")}</b><small>${t(r[17]?"ظاهرة للعملاء":"مخفية عن الموقع",r[17]?"Visible to clients":"Hidden")}</small></div><button class="sw ${r[17]?"on":""}" data-act="vis:${r[0]}"></button></div>
    <div class="actrow"><div><b>${t("تثبيت السيرة","Pin CV")}</b><small>${t(r[19]?"مثبّتة في المقدمة":"غير مثبّتة",r[19]?"Pinned to top":"Not pinned")}</small></div><button class="sw ${r[19]?"on":""}" data-act="pin:${r[0]}"></button></div>
    <div class="actrow col"><label>${t("حالة العاملة","Worker status")}</label><select class="statsel" data-setstatus="${r[0]}">${cvSt.map(x=>`<option value="${x[0]}"${x[0]===r[10]?" selected":""}>${t(x[1],x[2])}</option>`).join("")}</select></div>
    <div class="acts2">${(rent?[["user-plus","توظيف العاملة","Employ worker","employ","green"]]:[]).concat([["share-2","واتساب","WhatsApp","share",""],["printer","طباعة","Print","print",""],["file-text","نموذج PDF","PDF template","prev",""],["undo-2","باك أوت","Backout","backout",""],["copy","نسخ السيرة","Duplicate","dup",""],["trash-2","حذف","Delete","del","danger"]]).map(x=>`<button class="atile ${x[4]==="green"?"green":x[4]==="danger"?"danger":""}" ${x[3]==="prev"?`data-cvprev="${r[0]}"`:`data-act="${x[3]}:${r[0]}"`}>${I(x[0])}<span>${t(x[1],x[2])}</span></button>`).join("")}</div>
   </div>
  </aside>
  <div class="cvmain">
   <div class="vtabs">${tabs.map(x=>`<button class="${x[0]===CVSTAB?"on":""}" data-cvstab="${x[0]}">${I(x[3])}<span>${t(x[1],x[2])}</span></button>`).join("")}</div>
   <div class="tabbody">${(body[CVSTAB]||body.over)()}</div>
  </div>
 </div>`;
};
cvScreens.cvEdit=()=>cvScreens.cvAdd();
Object.assign(T,{cvShow:{ar:"تفاصيل السيرة",en:"CV details"},cvEdit:{ar:"تعديل السيرة",en:"Edit CV"}});
let CVACT=null;
const toast=(m)=>{let el=document.querySelector(".toast");if(!el){el=document.createElement("div");el.className="toast";document.body.appendChild(el)}el.innerHTML=I("check-circle-2")+"<span>"+m+"</span>";el.classList.add("show");clearTimeout(window.__tt);window.__tt=setTimeout(()=>el.classList.remove("show"),2600)};
const actDefs={
 backout:["undo-2","تسجيل باك أوت","Record backout"],
 del:["trash-2","حذف السيرة","Delete CV"],
 dup:["copy","نسخ السيرة","Duplicate CV"],
 share:["share-2","مشاركة السيرة","Share CV"],
 print:["printer","طباعة السيرة","Print CV"],
 employ:["user-plus","توظيف العاملة","Employ worker"]};
const closeAct=()=>{const el=document.getElementById("cvActOv");if(el)el.remove()};
const openAct=()=>{let el=document.getElementById("cvActOv");if(!el){el=document.createElement("div");el.id="cvActOv";document.body.appendChild(el)}el.innerHTML=actModal();if(window.lucide)lucide.createIcons({attrs:{"stroke-width":1.8}})};
const actModal=()=>{
 if(!CVACT)return "";
 const [k,id]=CVACT;const r=cvRows.find(x=>x[0]===id)||cvRows[0];const d=actDefs[k];if(!d)return "";
 const who=`<div class="amw">${r[17]?`<span class="thumb sm">${I("user-round")}</span>`:`<span class="thumb sm none">${I("image-off")}</span>`}<div><b>${t(r[2],r[1])}</b><small class="num">${r[0]} · ${r[8]}</small></div></div>`;
 const F=(l,le,c)=>`<div class="fld"><label>${t(l,le)}</label>${c}</div>`;
 const bodies={
  offer:`${F("سعر العرض (ر.س)","Offer price (SAR)",`<input type="text" value="${r[15]}">`)}${F("صالح حتى","Valid until",'<input type="date" value="2026-09-30">')}${F("الظهور","Visibility",`<select><option>${t("لكل العملاء","All clients")}</option><option>${t("عميل محدد","Specific client")}</option></select>`)}${F("ملاحظة","Note",`<input type="text" placeholder="${t("اختياري","Optional")}">`)}`,
  backout:`${F("سبب الباك أوت","Backout reason",`<select><option>${t("رفض السفر","Refused travel")}</option><option>${t("سبب طبي","Medical")}</option><option>${t("قرار المكتب الخارجي","External office decision")}</option></select>`)}${F("تاريخ الباك أوت","Backout date",'<input type="date" value="2026-08-17">')}${F("ملاحظات","Notes",'<input type="text">')}`,
  del:`<p class="ms">${t("سيتم نقل السيرة إلى المحذوفة ويمكن استعادتها لاحقاً.","The CV moves to Deleted and can be restored later.")}</p>`,
  dup:`<p class="ms">${t("تُنسخ كل البيانات ما عدا رقم الجواز والوسائط.","Everything is copied except passport number and media.")}</p>${F("رقم الجواز الجديد","New passport no.",'<input type="text" placeholder="P0000000">')}`,
  share:`${F("رقم الواتساب","WhatsApp number",'<input type="text" placeholder="+9665xxxxxxxx">')}${F("المرفق","Attachment",`<select><option>${t("نموذج السيرة PDF","CV template PDF")}</option><option>${t("رابط السيرة","CV link")}</option></select>`)}`,
  print:`${F("النموذج","Template",`<select><option>${t("نموذج كليك آند مور","Click & More template")}</option><option>${t("نموذج مختصر","Compact")}</option></select>`)}${F("يشمل","Include",`<select><option>${t("كل الأقسام","All sections")}</option><option>${t("بدون بيانات التواصل","Without contact details")}</option></select>`)}`,
  employ:`${F("العميل","Client",`<select><option>${t("منيرة الشهري","Munira Alshehri")}</option><option>${t("شركة نجد","Najd Co.")}</option></select>`)}${F("تاريخ البداية","Start date",'<input type="date" value="2026-08-20">')}${F("مدة التأجير","Rental duration",`<select><option>${t("شهر","1 month")}</option><option>${t("3 أشهر","3 months")}</option><option>${t("6 أشهر","6 months")}</option></select>`)}${F("الأجرة الشهرية (ر.س)","Monthly fee (SAR)",`<input type="text" value="${r[15]}">`)}`};
 const cta={offer:["حفظ العرض","Save offer"],backout:["تسجيل الباك أوت","Record backout"],del:["حذف السيرة","Delete CV"],dup:["إنشاء نسخة","Create copy"],share:["إرسال","Send"],print:["طباعة","Print"],employ:["إنشاء عقد التأجير","Create rental contract"]}[k];
 return `<div class="ov" data-actov><div class="modal am">
  <div class="modal-h"><div class="mh-t"><span class="mi ${k==="del"?"r":""}">${I(d[0])}</span><h3>${t(d[1],d[2])}</h3></div><button class="icon-btn" data-actclose>${I("x")}</button></div>
  ${who}<div class="fgrid two">${bodies[k]||""}</div>
  <div class="modal-f"><button class="btn ${k==="del"?"btn-red":"btn-primary"}" data-actdo>${I(k==="del"?"trash-2":"check")}${t(cta[0],cta[1])}</button><button class="btn btn-ghost" data-actclose>${t("إلغاء","Cancel")}</button></div>
 </div></div>`;
};
document.addEventListener("click",e=>{
 const km=e.target.closest("[data-cvmenu]");if(km){CVMENU=CVMENU===km.dataset.cvmenu?null:km.dataset.cvmenu;render();if(CVMENU)placeMenu(km.dataset.cvmenu);return}
 if(CVMENU&&!e.target.closest(".kebab")&&!e.target.closest(".amenu")){CVMENU=null;killMenus();render()}
 const ab=e.target.closest("[data-act]");
 if(ab){const [k,id]=ab.dataset.act.split(":");const r=cvRows.find(x=>x[0]===id);CVMENU=null;killMenus();
  if(k==="vis"&&r){r[17]=r[17]?0:1;render();toast(t(r[17]?"أصبحت السيرة ظاهرة في الموقع":"أُخفيت السيرة عن الموقع",r[17]?"CV is now visible":"CV hidden"));return}
  if(k==="pin"&&r){r[19]=r[19]?0:1;render();toast(t(r[19]?"تم تثبيت السيرة":"تم إلغاء التثبيت",r[19]?"CV pinned":"CV unpinned"));return}
  CVACT=[k,id];openAct();return}
 if(CVACT){
  if(e.target.closest("[data-actdo]")){const [k,id]=CVACT;const r=cvRows.find(x=>x[0]===id);
   if(k==="del"&&r){r[10]="deleted";r[11]="محذوفة";r[12]="Deleted";r[13]="r";r[16]="deleted"}
   if(k==="backout"&&r){r[10]="backout";r[11]="باك أوت";r[12]="Backed out";r[13]="r";r[16]="backout"}
   if(k==="employ"&&r){r[10]="contracted";r[11]="مؤجّرة حالياً";r[12]="Currently rented";r[13]="n"}
   const msg={backout:"تم تسجيل الباك أوت",del:"تم حذف السيرة",dup:"تم إنشاء نسخة من السيرة",share:"تم إرسال السيرة على واتساب",print:"أُرسلت السيرة للطباعة",employ:"تم إنشاء عقد التأجير"}[k];
   const msgE={backout:"Backout recorded",del:"CV deleted",dup:"CV duplicated",share:"Sent on WhatsApp",print:"Sent to printer",employ:"Rental contract created"}[k];
   CVACT=null;closeAct();if(k==="del"){cur="cvs"}render();toast(t(msg,msgE));return}
  const ov=e.target.closest("[data-actov]");
  if(e.target.closest("[data-actclose]")||e.target===ov){CVACT=null;closeAct();return}
 }
});
document.addEventListener("change",e=>{const ss=e.target.closest("[data-setstatus]");if(ss){const r=cvRows.find(x=>x[0]===ss.dataset.setstatus);const st=cvSt.find(x=>x[0]===ss.value);if(r&&st){r[10]=st[0];r[11]=st[1];r[12]=st[2];r[13]=st[0]==="available"?"g":st[0]==="suspended"||st[0]==="backout"?"r":st[0]==="reserved"?"a":"n";render();toast(t("تم تحديث الحالة إلى "+st[1],"Status updated to "+st[2]))}}});
Object.assign(screens,cvScreens);
Object.assign(T,{cvs:{ar:"السير الذاتية",en:"CVs"},cvAdd:{ar:"إضافة سيرة ذاتية",en:"Add CV"}});
document.addEventListener("input",e=>{const lp=e.target.closest("[data-langpct]");if(lp){LANGS[lp.dataset.langpct]=+lp.value;const b=lp.parentElement.querySelector("b");if(b)b.textContent=lp.value+"%";return}
 const ex=e.target.closest("[data-exp]");if(ex){const[i,j]=ex.dataset.exp.split(":").map(Number);EXPROWS[i][j]=ex.value;const row=ex.closest("tr");if(row)row.children[4].innerHTML="<b>"+expDur(EXPROWS[i][2],EXPROWS[i][3])+"</b>";const tot=document.querySelector(".exp-h .tag");if(tot)tot.textContent=t("الإجمالي","Total")+": "+expTotal()}});
document.addEventListener("click",e=>{
 const sk=e.target.closest("[data-skill]");if(sk){const k=sk.dataset.skill;SKILLS=SKILLS.includes(k)?SKILLS.filter(x=>x!==k):SKILLS.concat(k);render();return}
 const lk=e.target.closest("[data-lang]");if(lk){const k=lk.dataset.lang;if(LANGS[k]!==undefined)delete LANGS[k];else LANGS[k]=LANGMODE==="pct"?70:60;render();return}
 const ll=e.target.closest("[data-langlvl]");if(ll){const[k,v]=ll.dataset.langlvl.split(":");LANGS[k]=+v;render();return}
 const lm=e.target.closest("[data-langmode]");if(lm){LANGMODE=lm.dataset.langmode;render();return}
 const es=e.target.closest("[data-expst]");if(es){EXPST=es.dataset.expst;render();return}
 const ea=e.target.closest("[data-expadd]");if(ea){EXPROWS.push(["","","",""]);render();return}
 const ed2=e.target.closest("[data-expdel]");if(ed2){EXPROWS.splice(+ed2.dataset.expdel,1);render();return}
 const tg=e.target.closest("[data-toggle]");if(tg){tg.classList.toggle("on");return}
 const v=e.target.closest("[data-cvshow]");if(v){CVID=v.dataset.cvshow;CVSTAB="over";CVEDIT=null;cur="cvShow";render();window.scrollTo(0,0);return}
 const gg=e.target.closest("[data-go]");if(gg&&gg.dataset.go!=="cvEdit")CVEDIT=null;
 const ed=e.target.closest("[data-cvedit]");if(ed){CVEDIT=ed.dataset.cvedit;CVID=ed.dataset.cvedit;CVMODE="full";const rr=cvRows.find(x=>x[0]===ed.dataset.cvedit);if(rr)CVTYPE=rr[16]==="rent"?"rent":"rec";cur="cvEdit";render();window.scrollTo(0,0);return}
 const stb=e.target.closest("[data-cvstab]");if(stb){CVSTAB=stb.dataset.cvstab;render();window.scrollTo(0,0);return}
 const pv=e.target.closest("[data-cvprev]");if(pv){CVPREV=pv.dataset.cvprev;openPrev();return}
 const cp=e.target.closest("[data-colpick]");if(cp){CVCOLPICK=!CVCOLPICK;render();return}
 const cc=e.target.closest("[data-colclear]");if(cc){CVCOLS=[];render();return}
 const ci=e.target.closest(".cm-i");if(ci){const k=ci.querySelector("[data-col]").dataset.col;CVCOLS=CVCOLS.includes(k)?CVCOLS.filter(x=>x!==k):CVCOLS.concat(k);CVCOLPICK=true;render();return}
 if(CVCOLPICK&&!e.target.closest(".colpick")){CVCOLPICK=false;render()}
 const vw=e.target.closest("[data-cvview]");if(vw){CVVIEW=vw.dataset.cvview;render();return}
 const tb=e.target.closest("[data-cvtab]");if(tb){CVTAB=tb.dataset.cvtab;CVSTATUS="";render();return}
 const sc=e.target.closest("[data-cvstat]");if(sc){CVSTATUS=sc.dataset.cvstat;render();return}
 const mo=e.target.closest("[data-cvmore]");if(mo){CVMORE=!CVMORE;render();return}
 const rs=e.target.closest("[data-cvreset]");if(rs){CVQ="";CVSTATUS="";CVNAT="";CVJOB="";CVPIN="";CVVIS="";render();return}
 const ty=e.target.closest("[data-cvtype]");if(ty){CVTYPE=ty.dataset.cvtype;render();return}
 const md=e.target.closest("[data-cvmode]");if(md){CVMODE=md.dataset.cvmode;render();return}
 const kd=e.target.closest("[data-cvkingdom]");if(kd){CVKINGDOM=kd.dataset.cvkingdom;render();return}
 const st=e.target.closest("[data-step]");if(st){const secs=document.querySelectorAll(".cvfields .fs-h");const tgt=secs[+st.dataset.step];if(tgt){document.querySelectorAll(".steps .step").forEach(b=>b.classList.toggle("on",b===st));window.scrollTo({top:tgt.getBoundingClientRect().top+window.scrollY-90,behavior:"smooth"})}return}
});
document.addEventListener("change",e=>{
 if(e.target.matches("[data-cvnames]")){CVNAMES=e.target.checked;render();return}
 if(e.target.matches("[data-cvstatus]")){CVSTATUS=e.target.value;render();return}
 if(e.target.matches("[data-cvnat]")){CVNAT=e.target.value;render();return}
 if(e.target.matches("[data-cvjob]")){CVJOB=e.target.value;render();return}
 if(e.target.matches("[data-cvvis]")){CVVIS=e.target.value;render();return}
 if(e.target.matches("[data-cvpin]")){CVPIN=e.target.value;render();return}
});
document.addEventListener("input",e=>{if(e.target.matches("[data-cvq]")){CVQ=e.target.value;const p=e.target.selectionStart;render();const n=document.querySelector("[data-cvq]");if(n){n.focus();n.setSelectionRange(p,p)}}});

function killMenus(){document.querySelectorAll("body > .amenu").forEach(n=>n.remove());document.querySelectorAll(".amenu").forEach(n=>n.classList.add("hidden"))}
function placeMenu(id){
 requestAnimationFrame(()=>{
  const btn=document.querySelector(`[data-cvmenu="${id}"]`),m=document.querySelector(".amenu:not(.hidden)");if(!btn||!m)return;
  if(m.parentElement!==document.body)document.body.appendChild(m);
  const b=btn.getBoundingClientRect(),pad=10;
  m.style.maxHeight=(innerHeight-2*pad)+"px";
  const mh=Math.min(m.offsetHeight,innerHeight-2*pad),mw=m.offsetWidth;
  let top=b.bottom+6;if(top+mh>innerHeight-pad)top=b.top-mh-6;
  top=Math.min(Math.max(pad,top),Math.max(pad,innerHeight-mh-pad));
  let left=b.right-mw;left=Math.min(Math.max(pad,left),Math.max(pad,innerWidth-mw-pad));
  m.style.top=top+"px";m.style.left=left+"px";
 });
}

addEventListener("scroll",()=>{if(CVMENU)placeMenu(CVMENU)},true);
addEventListener("resize",()=>{if(CVMENU)placeMenu(CVMENU)});

(function(){const orig=window.render;window.render=function(){killMenus();return orig.apply(this,arguments)}})();
