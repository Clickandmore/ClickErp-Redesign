/* Rental contracts — grounded in admin/ingaz/RentContract/{index,create,create_final,edit,show,settings,contract-section,client-section,maid-section,trial-details}.blade.php */
const rnTabs=[["all","كل العقود","All contracts","layers",14],["active","نشطة","Active","play-circle",3],["completed","مكتملة","Completed","check-circle-2",7],["closed","مغلقة — متاحة","Closed — available","unlock",2],["moved","تم النقل","Transferred","arrow-left-right",1],["cancelled","ملغية قبل اكتمال المدة","Cancelled early","x-circle",1]];
const rnSt=[["active","نشط","Active","g"],["completed","مكتمل","Completed","n"],["closed","مغلق — متاح","Closed — available","a"],["moved","تم النقل","Transferred","n"],["cancelled","ملغي قبل اكتمال المدة","Cancelled early","r"]];
const rnPackages=[["شهر واحد","1 month",1,30,4500],["ثلاثة أشهر","3 months",3,90,12900],["ستة أشهر","6 months",6,180,24600],["سنة","12 months",12,360,46800]];
const rnPayTypes=["كاش","تحويل بنكي","شبكة (مدى)","رابط دفع","تقسيط"];
const rnAccounts=["الصندوق الرئيسي","بنك الراجحي — 1122","بنك الأهلي — 4408"];
const rnSignSt=[["unsigned","بانتظار التوقيع","Awaiting signature","a"],["client","موقّع من العميل","Signed by client","n"],["both","موقّع من الطرفين","Signed by both parties","g"]];
/* rent_contract_terms: page_number, section_title, section_order, term_number, term_content, term_order, is_active */
const rnTermRows=[
 [1,"أولاً: موضوع العقد",1,1,"يتعهد الطرف الأول بتأجير خدمات العاملة الموضحة بياناتها في هذا العقد للطرف الثاني، لأداء أعمال المهنة المتفق عليها داخل مقر الطرف الثاني المحدد في العقد.",1,1],
 [1,"أولاً: موضوع العقد",1,2,"لا يجوز للطرف الثاني تشغيل العاملة لدى الغير أو في مهنة غير المتفق عليها، ولا نقلها إلى مدينة أخرى دون موافقة كتابية من الطرف الأول.",2,1],
 [1,"ثانياً: مدة العقد وسريانه",2,1,"مدة هذا العقد هي المدة المثبتة في بيانات العقد، وتبدأ من تاريخ التسليم الفعلي للعاملة إلى الطرف الثاني.",1,1],
 [1,"ثانياً: مدة العقد وسريانه",2,2,"لا يُعتبر العقد سارياً إلا بعد سداد الدفعة الأولى وتوقيع الطرفين على جميع صفحات العقد.",2,1],
 [1,"ثانياً: مدة العقد وسريانه",2,3,"يجوز تمديد مدة العقد باتفاق الطرفين، وتُحسب قيمة التمديد على أساس تكلفة اليوم التقريبية المثبتة في العقد، ويُصدر بذلك ملحق وفاتورة إضافية.",3,1],
 [1,"ثالثاً: ساعات العمل والأجر",3,1,"تعمل العاملة عدد الساعات اليومية المحددة في بيانات العقد، ولها فترة راحة لا تقل عن ساعة يومياً وفق الأنظمة.",1,1],
 [1,"ثالثاً: ساعات العمل والأجر",3,2,"ما زاد على ساعات العمل اليومية المتفق عليها يُحسب بأجرة الساعة المثبتة في بيانات العقد، ويُسدد مع الدفعة التالية.",2,1],
 [2,"رابعاً: التزامات الطرف الأول",4,1,"توفير عاملة مؤهلة للمهنة المتفق عليها وسليمة صحياً وفق الفحص الطبي المعتمد.",1,1],
 [2,"رابعاً: التزامات الطرف الأول",4,2,"استبدال العاملة خلال مدة العقد في حال عدم الالتزام أو عدم اللياقة الصحية، وفق ضوابط الشركة ومدة الضمان.",2,1],
 [2,"رابعاً: التزامات الطرف الأول",4,3,"متابعة الإجراءات النظامية للعاملة من إقامة وتأمين طبي وتجديدها في مواعيدها.",3,1],
 [2,"خامساً: التزامات الطرف الثاني",5,1,"توفير سكن ملائم وطعام ورعاية صحية للعاملة، وعدم تكليفها بأعمال شاقة أو خارج نطاق المهنة.",1,1],
 [2,"خامساً: التزامات الطرف الثاني",5,2,"سداد قيمة العقد وفق الجدول المالي المرفق، ويترتب على التأخير في السداد إيقاف الخدمة بعد إشعار الطرف الثاني كتابياً.",2,1],
 [2,"خامساً: التزامات الطرف الثاني",5,3,"الإبلاغ الفوري عن أي حالة تغيب أو هروب أو حالة صحية طارئة تخص العاملة.",3,1],
 [2,"خامساً: التزامات الطرف الثاني",5,4,"عدم الاحتفاظ بجواز العاملة أو وثائقها الشخصية، وتسليمها للطرف الأول عند نهاية العقد.",4,1],
 [3,"سادساً: الإنهاء المبكر والتسوية",6,1,"في حال إنهاء العقد قبل اكتمال المدة يُحسب المستحق على أساس الأيام المستخدمة وتكلفة اليوم التقريبية المثبتة في العقد.",1,1],
 [3,"سادساً: الإنهاء المبكر والتسوية",6,2,"لا تُرد قيمة الأيام غير المستخدمة إلا وفق سياسة الشركة المعتمدة، وتُسوّى المبالغ خلال المدة النظامية من تاريخ الإنهاء.",2,1],
 [3,"سابعاً: نقل الكفالة",7,1,"يجوز تحويل هذا العقد إلى طلب نقل كفالة برغبة الطرفين، وتُحسب قيمة النقل والراتب بعد النقل وفق البيانات المثبتة في العقد.",1,1],
 [3,"ثامناً: أحكام عامة",8,1,"يخضع هذا العقد لأنظمة وزارة الموارد البشرية والتنمية الاجتماعية في المملكة العربية السعودية، وما لم يُنص عليه يُرجع فيه إلى الأنظمة النافذة.",1,1],
 [3,"ثامناً: أحكام عامة",8,2,"في حال نشوء خلاف يُسعى إلى تسويته ودياً، وإلا فيُحال إلى الجهة المختصة بالمنازعات العمالية.",2,1],
 [3,"ثامناً: أحكام عامة",8,3,"حُرِّر هذا العقد من نسختين بيد كل طرف نسخة للعمل بموجبها، ويُعتبر توقيع الطرفين على جميع الصفحات إقراراً بالعلم بكل بنوده.",3,1]];
const rnTermPages=[1,2,3];
const rnTermSections=p=>{const out=[];rnTermRows.filter(x=>x[0]===p&&x[6]).sort((a,b)=>a[2]-b[2]||a[5]-b[5]).forEach(x=>{let g=out.find(o=>o.title===x[1]);if(!g){g={title:x[1],order:x[2],items:[]};out.push(g)}g.items.push(x)});return out};
/* 0 no 1 client 2 clientType 3 phone 4 nationalId 5 city 6 maid 7 maidEn 8 passport 9 nat 10 job 11 pkg 12 months 13 days 14 start 15 end 16 usedDays 17 remainDays 18 hours 19 hourly 20 monthlySalary 21 monthlyCost 22 total 23 paid 24 remaining 25 status 26 sign 27 marketer 28 branch 29 payType 30 payStatus 31 extensions 32 transferAmount 33 salaryAfter 34 contractDate */
const rnRows=[
 ["RC-2026-0044","منيرة الشهري","person","+966 55 214 8890","1042887331","الرياض","ماريتس دي لا كروز","Marites Dela Cruz","P7741203","الفلبين","عاملة منزلية","ثلاثة أشهر",3,90,"2026-07-01","2026-09-28",50,40,8,45,1300,4300,12900,6450,6450,"active","both","سارة القحطاني","الفرع الرئيسي","تحويل بنكي","جزئي",1,9000,1500,"2026-06-28"],
 ["RC-2026-0043","شركة نجد للمقاولات","company","+966 11 448 2200","3001122447","الرياض","سري واهيوني","Sri Wahyuni","P5520914","إندونيسيا","طباخة","ستة أشهر",6,180,"2026-05-10","2026-11-06",102,78,10,50,1450,4100,24600,24600,0,"active","both","خالد العمري","الفرع الرئيسي","تحويل بنكي","مسدّد",0,0,0,"2026-05-08"],
 ["RC-2026-0041","نورة الحربي","person","+966 53 900 1187","1055881003","الدمام","كوماري بيريرا","Kumari Perera","P3391077","سريلانكا","عاملة منزلية","شهر واحد",1,30,"2026-06-01","2026-06-30",30,0,8,45,1200,4500,4500,4500,0,"completed","both","سارة القحطاني","فرع الدمام","كاش","مسدّد",0,0,0,"2026-05-30"],
 ["RC-2026-0040","عبدالله الدوسري","person","+966 50 771 3320","1099220145","جدة","غريس ناكاتو","Grace Nakato","P8812440","أوغندا","عاملة منزلية","ثلاثة أشهر",3,90,"2026-03-05","2026-06-02",90,0,8,45,1100,4300,12900,12900,0,"completed","both","خالد العمري","فرع جدة","شبكة (مدى)","مسدّد",0,0,0,"2026-03-02"],
 ["RC-2026-0038","هيا العنزي","person","+966 59 118 2043","1077441200","الرياض","عائشة خان","Ayesha Khan","P6620355","بنغلاديش","عاملة منزلية","شهر واحد",1,30,"2026-04-02","2026-05-01",30,0,8,45,1000,4500,4500,4500,0,"closed","both","سارة القحطاني","الفرع الرئيسي","كاش","مسدّد",0,0,0,"2026-04-01"],
 ["RC-2026-0036","مؤسسة البيان التجارية","company","+966 12 663 5510","3004411882","جدة","جينيفر لوبيز","Jenifer Lopez","P1129884","الفلبين","سائق خاص","سنة",12,360,"2025-09-01","2026-08-26",352,8,10,55,1800,3900,46800,44000,2800,"moved","both","محمد الشهراني","فرع جدة","تقسيط","جزئي",2,15800,2200,"2025-08-28"],
 ["RC-2026-0034","فيصل القحطاني","person","+966 56 330 4471","1033441200","الرياض","رينا أستوتي","Rina Astuti","P9903221","إندونيسيا","طباخة","ثلاثة أشهر",3,90,"2026-06-15","2026-09-12",34,0,8,50,1400,4300,12900,4300,8600,"cancelled","client","خالد العمري","الفرع الرئيسي","رابط دفع","جزئي",0,0,0,"2026-06-12"],
 ["RC-2026-0033","شركة الوفاء الطبية","company","+966 13 887 9011","3009988112","الدمام","نيلانثي سيلفا","Nilanthi Silva","P4408123","سريلانكا","ممرضة منزلية","ستة أشهر",6,180,"2026-08-15","2027-02-11",4,176,12,60,2100,4100,24600,8200,16400,"active","unsigned","مشرف النظام","فرع الدمام","تحويل بنكي","جزئي",0,0,0,"2026-08-13"]];
const rnInv=[
 ["INV-RC-0044","RC-2026-0044","منيرة الشهري","2026-06-28",12900,1935,0,6450,"جزئي","Partial","a"],
 ["INV-RC-0043","RC-2026-0043","شركة نجد للمقاولات","2026-05-08",24600,3690,0,24600,"مسدّد","Settled","g"],
 ["INV-RC-0041","RC-2026-0041","نورة الحربي","2026-05-30",4500,675,0,4500,"مسدّد","Settled","g"],
 ["INV-RC-0034","RC-2026-0034","فيصل القحطاني","2026-06-12",12900,1935,500,4300,"جزئي","Partial","a"],
 ["INV-RC-0033","RC-2026-0033","شركة الوفاء الطبية","2026-08-13",24600,3690,0,8200,"جزئي","Partial","a"]];
let RNTAB="all",RNQ="",RNSTATUS="",RNPKG="",RNMORE=false,RNID="RC-2026-0044",RNSTAB="over",RNMENU=null,RNEDIT=null,RNADDTAB="existing",RNPRINT=null;
const rnTotal=k=>(rnTabs.find(x=>x[0]===k)||[,,,,0])[4];
const rnSet=()=>RNTAB==="all"?rnRows:rnRows.filter(r=>r[25]===RNTAB);
const rnFiltered=()=>rnSet().filter(r=>(!RNSTATUS||r[25]===RNSTATUS)&&(!RNPKG||r[11]===RNPKG)&&(!RNQ||(r[0]+r[1]+r[6]+r[8]+r[3]).toLowerCase().includes(RNQ.toLowerCase())));
const rnStTag=r=>{const s=rnSt.find(x=>x[0]===r[25])||rnSt[0];return `<span class="st tag ${s[3]}"><i></i>${t(s[1],s[2])}</span>`};
const rnSignTag=r=>{const s=rnSignSt.find(x=>x[0]===r[26])||rnSignSt[0];return `<span class="tag ${s[3]}">${I(r[26]==="both"?"check-circle-2":r[26]==="client"?"pen-line":"clock")}${t(s[1],s[2])}</span>`};
const rnMoney=n=>`<span class="num">${n.toLocaleString("en")}</span>`;
const rnDayCost=r=>Math.round(r[22]/r[13]);
const rnRemainCell=r=>{if(r[25]!=="active")return `<span class="empty">—</span>`;
 const d=r[17];const tone=d>30?"n":d>7?"a":"r";
 return `<div class="gcell"><b class="num">${d}</b><span class="tag ${tone}">${t(d>30?"ضمن المدة":d>7?"تقترب النهاية":"تنتهي قريباً",d>30?"Within term":d>7?"Nearing end":"Ending soon")}</span></div>`};
const rnStats=()=>{const T0=rnTotal(RNTAB);switch(RNTAB){
 case "active":return [["","العقود النشطة","Active contracts","play-circle","g",T0],["ending","تنتهي خلال 30 يوماً","Ending in 30 days","alarm-clock","a",1],["unsigned","بانتظار التوقيع","Awaiting signature","pen-line","a",1],["due","مبالغ مستحقة","Amounts due","wallet","r",22850]];
 case "completed":return [["","العقود المكتملة","Completed","check-circle-2","n",T0],["month","انتهت هذا الشهر","Ended this month","calendar","n",2],["renewed","جُدِّدت","Renewed","repeat","g",3],["value","إجمالي القيم","Total value","banknote","n",76500]];
 case "closed":return [["","مغلقة — متاحة","Closed — available","unlock","a",T0],["ready","جاهزة لعقد جديد","Ready to re-rent","arrow-left-right","g",2],["shelter","عادت للإيواء","Back to shelter","home","n",2]];
 case "moved":return [["","تم النقل","Transferred","arrow-left-right","n",T0],["value","قيمة النقل","Transfer value","banknote","n",15800],["due","متبقٍ للتسوية","To settle","alarm-clock","r",2800]];
 case "cancelled":return [["","ملغية قبل اكتمال المدة","Cancelled early","x-circle","r",T0],["used","الأيام المستخدمة","Used days","calendar-check","n",34],["refund","مبالغ للتسوية","To settle","wallet","a",8600]];
 default:return [["","إجمالي عقود التأجير","All rental contracts","layers","n",T0],["active","نشطة","Active","play-circle","g",rnTotal("active")],["completed","مكتملة","Completed","check-circle-2","n",rnTotal("completed")],["closed","مغلقة — متاحة","Closed — available","unlock","a",rnTotal("closed")],["moved","تم النقل","Transferred","arrow-left-right","n",rnTotal("moved")],["cancelled","ملغية قبل اكتمال المدة","Cancelled early","x-circle","r",rnTotal("cancelled")],["value","إجمالي قيم العقود","Contracts value","banknote","n",221400],["remaining","المتبقي","Remaining","alarm-clock","r",42850]]}};
const rnMenuItems=[["printer","طباعة العقد","Print contract"],["receipt","طباعة الفاتورة","Print invoice"],["banknote","طباعة سند القبض","Print receipt bond"],["pen-line","توقيع العقد","Sign contract"],["calendar-plus","تمديد العقد","Extend contract"],["arrow-left-right","تحويل إلى نقل كفالة","Convert to transfer"],["check-circle-2","إغلاق العقد","Close contract"],["x-circle","إلغاء قبل اكتمال المدة","Cancel early"]];
const rnActs=r=>`<div class="rowacts"><button class="icon-btn pri" data-rnshow="${r[0]}" title="${t("تفاصيل العقد","Contract details")}">${I("eye")}</button><button class="icon-btn pri" data-rnedit="${r[0]}" title="${t("تعديل","Edit")}">${I("pencil")}</button><button class="icon-btn pri" data-rnprint="contract:${r[0]}" title="${t("طباعة العقد","Print contract")}">${I("printer")}</button><span class="kebab"><button class="icon-btn" data-rnmenu="${r[0]}" title="${t("المزيد","More")}">${I("more-horizontal")}</button><div class="amenu${RNMENU===r[0]?"":" hidden"}" data-rnmenu-for="${r[0]}">${rnMenuItems.map(x=>`<button data-rnact="${x[0]}:${r[0]}"${x[0]==="x-circle"?' class="danger"':""}>${I(x[0])}<span>${t(x[1],x[2])}</span></button>`).join("")}</div></span></div>`;
const rentalScreens={
 rental:()=>{
  const rows=rnFiltered(),stats=rnStats(),tab=rnTabs.find(x=>x[0]===RNTAB);
  return `
 <div class="page-head"><div><h1>${t("عقود التأجير","Rental contracts")}</h1><p>${t("تأجير العاملات بالباقات — من إنشاء العقد وتوقيعه وطباعته إلى الفواتير والتمديد والإغلاق أو النقل.","Package-based worker rental — from contract creation, signing and printing to invoices, extension, closing or transfer.")}</p></div>
 <div class="actions"><button class="btn btn-ghost" data-go="rentalSettings">${I("settings-2")}${t("الباقات والإعدادات","Packages & settings")}</button><button class="btn btn-ghost" data-go="rentalInvoices">${I("receipt")}${t("فواتير التأجير","Rental invoices")}</button><button class="btn btn-primary" data-go="rentalAdd">${I("plus")}${t("عقد تأجير جديد","New rental contract")}</button></div></div>
 <div class="seg tabs">${rnTabs.map(x=>`<button class="${x[0]===RNTAB?"on":""}" data-rntab="${x[0]}">${t(x[1],x[2])}<b class="segb num">${x[4]}</b></button>`).join("")}</div>
 <div class="pipe">${stats.slice(0,4).map(x=>`<button class="pg ${x[4]==="g"?"post":x[4]==="r"?"closed":"run"} ${x[0]&&x[0]===RNSTATUS?"on":""}" data-rnstat="${x[0]}">${I(x[3])}<span class="pg-t">${t(x[1],x[2])}</span><b class="num">${x[5].toLocaleString("en")}</b></button>`).join("")}</div>
 ${stats.length>4?`<div class="pipe">${stats.slice(4).map(x=>`<button class="pg ${x[4]==="g"?"post":x[4]==="r"?"closed":"run"} ${x[0]&&x[0]===RNSTATUS?"on":""}" data-rnstat="${x[0]}">${I(x[3])}<span class="pg-t">${t(x[1],x[2])}</span><b class="num">${x[5].toLocaleString("en")}</b></button>`).join("")}</div>`:""}
 <div class="fbar">
  <div class="fb grow"><label>${t("بحث","Search")}</label><div class="inp">${I("search")}<input data-rnq value="${RNQ}" placeholder="${t("رقم العقد، العميل، العاملة، الجواز، الجوال","Contract no., client, worker, passport, phone")}"></div></div>
  <div class="fb"><label>${t("الحالة","Status")}</label><select data-rnstatus><option value="">${t("كل الحالات","All statuses")}</option>${rnSt.map(s=>`<option value="${s[0]}"${RNSTATUS===s[0]?" selected":""}>${t(s[1],s[2])}</option>`).join("")}</select></div>
  <div class="fb"><label>${t("الباقة","Package")}</label><select data-rnpkg><option value="">${t("كل الباقات","All packages")}</option>${rnPackages.map(p=>`<option${RNPKG===p[0]?" selected":""}>${p[0]}</option>`).join("")}</select></div>
  <div class="fb"><label>${t("الفرع","Branch")}</label><select><option>${t("كل الفروع","All branches")}</option>${["الفرع الرئيسي","فرع جدة","فرع الدمام"].map(x=>`<option>${x}</option>`).join("")}</select></div>
  <button class="btn btn-ghost" data-rnmore>${I(RNMORE?"chevron-up":"sliders-horizontal")}${t(RNMORE?"إخفاء":"فلاتر أكثر",RNMORE?"Hide":"More filters")}</button>
  <span class="empty">${t(`${rows.length} نتيجة`,`${rows.length} results`)}</span>
 </div>
 ${RNMORE?`<div class="fbar second">
  <div class="fb"><label>${t("حالة التوقيع","Signature status")}</label><select><option>${t("الكل","All")}</option>${rnSignSt.map(x=>`<option>${t(x[1],x[2])}</option>`).join("")}</select></div>
  <div class="fb"><label>${t("حالة الدفع","Payment status")}</label><select><option>${t("الكل","All")}</option><option>${t("مسدّد","Settled")}</option><option>${t("جزئي","Partial")}</option><option>${t("غير مسدّد","Unpaid")}</option></select></div>
  <div class="fb"><label>${t("طريقة الدفع","Payment method")}</label><select><option>${t("الكل","All")}</option>${rnPayTypes.map(x=>`<option>${x}</option>`).join("")}</select></div>
  <div class="fb"><label>${t("المسوّق","Marketer")}</label><select><option>${t("الكل","Anyone")}</option>${["سارة القحطاني","خالد العمري","محمد الشهراني","مشرف النظام"].map(x=>`<option>${x}</option>`).join("")}</select></div>
  <div class="fb"><label>${t("من تاريخ العقد","Contract date from")}</label><input type="date"></div>
  <div class="fb"><label>${t("إلى تاريخ العقد","Contract date to")}</label><input type="date"></div>
  <div class="fb"><label>${t("نوع العميل","Client type")}</label><select><option>${t("الكل","All")}</option><option>${t("فرد","Individual")}</option><option>${t("شركة","Company")}</option></select></div>
  <div class="fb"><label>${t("عدد مرات التمديد","Extensions")}</label><select><option>${t("الكل","All")}</option><option>0</option><option>1</option><option>2+</option></select></div>
 </div>`:""}
 <div class="card flush"><div class="card-h"><div><h3>${t(tab[1],tab[2])}</h3><p>${t("الأعمدة كما في شاشة النظام: العميل، العاملة، معلومات العقد، المسوّق والفرع، الحالة، الأيام المتبقية.","Columns as in the system screen: client, worker, contract info, marketer & branch, status, remaining days.")}</p></div></div>
 <div class="tw"><table class="grp"><thead><tr><th class="chk"><input type="checkbox"></th><th>${t("رقم العقد","Contract no.")}</th><th>${t("العميل","Client")}</th><th>${t("العاملة","Worker")}</th><th>${t("معلومات العقد","Contract info")}</th><th>${t("القيمة والسداد","Value & payment")}</th><th>${t("المسوّق والفرع","Marketer & branch")}</th><th>${t("التوقيع","Signature")}</th><th>${t("الحالة","Status")}</th><th>${t("الأيام المتبقية","Remaining days")}</th><th class="act">${t("الإجراءات","Actions")}</th></tr></thead><tbody>
 ${rows.length?rows.map(r=>`<tr>
  <td class="chk"><input type="checkbox"></td>
  <td><div class="gcell"><b class="num">${r[0]}</b><small class="num">${r[34]}</small></div></td>
  <td><div class="wcell"><span class="av ${r[2]}">${I(r[2]==="company"?"building-2":"user-round")}</span><div><b>${r[1]}</b><small class="num">${r[3]}</small><small>${r[5]}</small></div></div></td>
  <td><div class="gcell"><b>${r[6]}</b><small>${r[9]} · ${r[10]}</small><small class="num">${t("جواز","P")} ${r[8]}</small></div></td>
  <td><div class="gcell"><b>${r[11]}</b><small class="num">${r[14]} → ${r[15]}</small><small>${r[18]} ${t("ساعة/يوم","h/day")} · ${t("أجرة الساعة","hourly")} ${r[19]}</small></div></td>
  <td><div class="gcell"><b class="num">${r[22].toLocaleString("en")}</b><small class="num">${t("مدفوع","paid")} ${r[23].toLocaleString("en")}</small><small class="num${r[24]?" bad":""}">${t("متبقٍ","due")} ${r[24].toLocaleString("en")}</small></div></td>
  <td><div class="gcell"><b>${r[27]}</b><small>${r[28]}</small></div></td>
  <td>${rnSignTag(r)}</td>
  <td>${rnStTag(r)}</td>
  <td>${rnRemainCell(r)}</td>
  <td class="act">${rnActs(r)}</td></tr>`).join(""):`<tr><td colspan="11"><div class="tempty">${I("inbox")}<b>${t("لا عقود مطابقة","No matching contracts")}</b><small>${t("جرّب إزالة الفلاتر","Try clearing the filters")}</small></div></td></tr>`}
 </tbody></table></div>
 <div class="tfoot"><span>${t(`عرض ${rows.length} من ${tab[4]} عقد`,`Showing ${rows.length} of ${tab[4]} contracts`)}</span><div class="pager"><button>${I("chevron-right")}</button><button class="on">1</button><button>${I("chevron-left")}</button></div></div>
 </div>`},
 rentalShow:()=>{
  const r=rnRows.find(x=>x[0]===RNID)||rnRows[0];
  const pct=Math.round(r[16]/r[13]*100);
  const tabs=[["over","نظرة عامة","Overview","gauge"],["client","بيانات العميل","Client data","user-circle"],["maid","بيانات العاملة","Worker data","id-card"],["terms","بنود العقد","Contract terms","scroll-text"],["finance","المالية والفواتير","Finance & invoices","wallet"],["ext","التمديد والتجديد","Extensions","calendar-plus"],["files","المرفقات والتوقيع","Files & signature","pen-line"],["log","السجل","Activity log","history"]];
  const body={
   over:()=>`
    ${sect("gauge","ملخص العقد","Contract summary",`<div class="ovgrid">${[["الباقة","Package",r[11],`${r[12]} ${t("شهر","months")} · ${r[13]} ${t("يوم","days")}`],["بداية العقد","Contract start",`<span class="num">${r[14]}</span>`,t("تاريخ العقد","contract date")+" "+r[34]],["نهاية العقد","Contract end",`<span class="num">${r[15]}</span>`,r[25]==="active"?t(`باقي ${r[17]} يوم`,`${r[17]} days left`):t("منتهية","ended")],["الأيام المستخدمة","Used days",`<span class="num">${r[16]}</span>`,t(`من ${r[13]} يوم`,`of ${r[13]} days`)],["تكلفة اليوم التقريبية","Approx. day cost",`<span class="num">${rnDayCost(r)}</span>`,t("ر.س","SAR")],["عدد مرات التمديد","Extensions",`<span class="num">${r[31]}</span>`,r[31]?t("آخر تمديد 30 يوماً","last extension 30 days"):t("لا تمديد","none")]].map(x=>`<div class="ovc"><span class="ovl">${t(x[0],x[1])}</span><b class="ovv">${x[2]}</b><small class="ovm">${x[3]}</small></div>`).join("")}</div>`)}
    ${sect("activity","تقدّم المدة","Term progress",`<div style="padding:18px"><div class="progwrap"><span class="progbar"><i style="width:${pct}%"></i></span><b class="num">${pct}%</b></div>
     <div class="proglegend"><span>${t("الأيام المستخدمة","Used")}: <b class="num">${r[16]}</b></span><span>${t("المتبقية","Remaining")}: <b class="num">${r[17]}</b></span><span>${t("الإجمالي","Total")}: <b class="num">${r[13]}</b></span></div></div>`)}
    ${sect("wallet","الملخص المالي","Financial summary",`<div class="fin3">${[["قيمة العقد","Contract value",r[22].toLocaleString("en"),"n"],["المدفوع","Paid",r[23].toLocaleString("en"),"g"],["المتبقي","Remaining",r[24].toLocaleString("en"),r[24]?"r":"g"]].map(x=>`<div class="fin"><small>${t(x[0],x[1])}</small><b class="num ${x[3]}">${x[2]}</b></div>`).join("")}</div>`)}`,
   client:()=>sect("user-circle","بيانات العميل","Client data",kvs([["اسم العميل","Client name",r[1]],["نوع العميل","Client type",r[2]==="company"?t("شركة","Company"):t("فرد","Individual")],["رقم الجوال","Phone",`<span class="num">${r[3]}</span>`],[r[2]==="company"?"الرقم الضريبي":"رقم الهوية",r[2]==="company"?"Tax number":"National ID",`<span class="num">${r[4]}</span>`],["المدينة","City",r[5]],["العنوان","Address",t("حي الملقا، طريق أنس بن مالك","Al Malqa, Anas bin Malik Rd")],["المهنة","Profession",r[2]==="company"?t("نشاط تجاري","Commercial activity"):t("موظف حكومي","Government employee")],["الدخل الشهري","Monthly income",r[2]==="company"?`<span class="empty">—</span>`:`<span class="num">18,000</span> ${t("ر.س","SAR")}`],["عدد أفراد الأسرة","Family members",r[2]==="company"?`<span class="empty">—</span>`:`<span class="num">5</span>`],["عدد الأطفال","Children",r[2]==="company"?`<span class="empty">—</span>`:`<span class="num">3</span>`]])),
   maid:()=>sect("id-card","بيانات العاملة","Worker data",kvs([["الاسم","Name",r[6]],["الاسم بالإنجليزية","Name (EN)",`<span class="num">${r[7]}</span>`],["رقم الجواز","Passport",`<span class="num">${r[8]}</span>`],["الجنسية","Nationality",r[9]],["المهنة","Occupation",r[10]],["الديانة","Religion",t("مسيحية","Christian")],["سنوات الخبرة","Experience",`<span class="num">4</span>`],["العمر","Age",`<span class="num">31</span>`],["السيرة الذاتية","CV",`<button class="lnk" data-go="cvs">${I("files")}<span>${t("فتح السيرة","Open CV")}</span></button>`]])),
   terms:()=>`
    ${sect("scroll-text","بنود العقد المالية والتشغيلية","Financial & operational terms",kvs([["ساعات العمل اليومية","Working hours per day",`<span class="num">${r[18]}</span> ${t("ساعة","hours")}`],["أجرة الساعة","Hourly rate",`<span class="num">${r[19]}</span> ${t("ر.س","SAR")}`],["راتب العاملة الشهري","Worker monthly salary",`<span class="num">${r[20]}</span> ${t("ر.س","SAR")}`],["التكلفة الشهرية","Monthly cost",`<span class="num">${r[21]}</span> ${t("ر.س","SAR")}`],["مبلغ نقل الكفالة","Sponsorship transfer amount",r[32]?`<span class="num">${r[32].toLocaleString("en")}</span> ${t("ر.س","SAR")}`:`<span class="empty">${t("لا ينطبق","N/A")}</span>`],["الراتب بعد نقل الكفالة","Salary after transfer",r[33]?`<span class="num">${r[33].toLocaleString("en")}</span> ${t("ر.س","SAR")}`:`<span class="empty">${t("لا ينطبق","N/A")}</span>`],["مدة العقد","Contract duration",`<span class="num">${r[12]}</span> ${t("شهر","months")} · <span class="num">${r[13]}</span> ${t("يوم","days")}`],["الإنجاز من / إلى","Ingaz from / to",`<span class="num">${r[14]} → ${r[15]}</span>`]]))}
    ${sect("file-text","الشروط والأحكام","Terms and conditions",`${rnTermPages.map(p=>`<div class="termpage"><div class="tp-h">${I("file-text")}<b>${t(`بنود الصفحة ${["الأولى","الثانية","الثالثة"][p-1]}`,`Page ${p} terms`)}</b><span class="tp-n num">${rnTermRows.filter(x=>x[0]===p&&x[6]).length} ${t("بند","terms")}</span></div>${rnTermSections(p).map(g=>`<div class="termsec"><h4>${g.title}</h4><div class="terms">${g.items.map(x=>`<div class="term"><span class="tn num">${x[3]}</span><p>${x[4]}</p></div>`).join("")}</div></div>`).join("")}</div>`).join("")}
     <div class="formbar"><button class="btn btn-ghost" data-go="rentalTerms">${I("pencil")}${t("تعديل بنود العقد","Edit contract terms")}</button><button class="btn btn-primary" data-rnprint="contract:${r[0]}">${I("printer")}${t("طباعة العقد كاملاً","Print full contract")}</button></div>`)}`,
   finance:()=>{
    const inv=rnInv.find(x=>x[1]===r[0]);
    return `
    ${sect("wallet","القيم المالية","Financial values",`<div class="ovgrid four">${[["قيمة العقد","Contract value",r[22].toLocaleString("en"),t("ر.س","SAR")],["المدفوع","Paid",r[23].toLocaleString("en"),t("ر.س","SAR")],["المتبقي","Remaining",r[24].toLocaleString("en"),t("ر.س","SAR")],["تكلفة اليوم","Day cost",String(rnDayCost(r)),t("ر.س","SAR")]].map(x=>`<div class="ovc"><span class="ovl">${t(x[0],x[1])}</span><b class="ovv num">${x[2]}</b><small class="ovm">${x[3]}</small></div>`).join("")}</div>`)}
    ${sect("landmark","بيانات السداد","Payment details",kvs([["طريقة الدفع","Payment method",r[29],r[30]],["حالة الدفع","Payment status",r[30]],["البنك / حساب السداد","Bank / receipt account",rnAccounts[1]],["إيصال الدفع","Payment receipt",r[23]?`<button class="lnk" data-rnprint="bond:${r[0]}">${I("banknote")}<span>${t("سند القبض","Receipt bond")}</span></button>`:`<span class="empty">${t("غير مرفوع","Not uploaded")}</span>`]]))}
    ${sect("receipt","الفاتورة","Invoice",inv?`<div class="tw"><table><thead><tr><th>${t("رقم الفاتورة","Invoice no.")}</th><th>${t("التاريخ","Date")}</th><th>${t("الإجمالي","Total")}</th><th>${t("الضريبة","VAT")}</th><th>${t("الخصم","Discount")}</th><th>${t("المدفوع","Paid")}</th><th>${t("المتبقي","Remaining")}</th><th>${t("الحالة","Status")}</th><th class="act"></th></tr></thead><tbody>
     <tr><td><b class="num">${inv[0]}</b></td><td class="num">${inv[3]}</td><td class="num">${inv[4].toLocaleString("en")}</td><td class="num">${inv[5].toLocaleString("en")}</td><td class="num">${inv[6].toLocaleString("en")}</td><td class="num">${inv[7].toLocaleString("en")}</td><td class="num${inv[4]-inv[6]-inv[7]?" due":""}">${(inv[4]-inv[6]-inv[7]).toLocaleString("en")}</td><td><span class="st tag ${inv[10]}"><i></i>${t(inv[8],inv[9])}</span></td>
     <td class="act"><div class="rowacts"><button class="icon-btn pri" data-rnprint="invoice:${r[0]}" title="${t("طباعة الفاتورة","Print invoice")}">${I("printer")}</button><button class="icon-btn pri" data-rnprint="bond:${r[0]}" title="${t("سند القبض","Receipt bond")}">${I("banknote")}</button></div></td></tr>
    </tbody></table></div>`:`<div class="convbox"><div><b>${t("لا توجد فاتورة","No invoice")}</b><small>${t("تُصدر الفاتورة عند حفظ العقد وسداد الدفعة الأولى.","The invoice is issued when the contract is saved and the first payment is received.")}</small></div><button class="btn btn-primary">${I("receipt")}${t("إصدار فاتورة","Create invoice")}</button></div>`)}`},
   ext:()=>`
    ${sect("calendar-plus","إحصاءات التمديد","Extension stats",`<div class="ovgrid four">${[["بداية العقد","Contract start",r[14]],["نهاية العقد الحالية","Current end",r[15]],["مدة العقد الحالية","Current duration",`${r[13]} ${t("يوم","d")}`],["الأيام المستخدمة","Used days",String(r[16])],["الأيام المتبقية / التأخير","Remaining / overdue",String(r[17])],["قيمة العقد الحالية","Current value",r[22].toLocaleString("en")],["تكلفة اليوم التقريبية","Approx. day cost",String(rnDayCost(r))],["عدد مرات التمديد","Extensions",String(r[31])]].map(x=>`<div class="ovc"><span class="ovl">${t(x[0],x[1])}</span><b class="ovv num">${x[2]}</b></div>`).join("")}</div>`)}
    ${sect("history","سجل التمديدات","Extension history",r[31]?`<div class="tw"><table><thead><tr><th>#</th><th>${t("أيام التمديد","Extension days")}</th><th>${t("النهاية قبل","End before")}</th><th>${t("النهاية بعد","End after")}</th><th>${t("التكلفة","Cost")}</th><th>${t("بواسطة","By")}</th></tr></thead><tbody>
     ${Array.from({length:r[31]}).map((_,i)=>`<tr><td class="num">${i+1}</td><td class="num">30</td><td class="num">2026-0${8-r[31]+i}-28</td><td class="num">2026-0${9-r[31]+i}-27</td><td class="num">${(rnDayCost(r)*30).toLocaleString("en")}</td><td>${r[27]}</td></tr>`).join("")}
    </tbody></table></div>`:`<div class="tempty">${I("calendar-plus")}<b>${t("لم يُمدَّد هذا العقد","This contract was never extended")}</b></div>`)}
    <div class="formbar"><button class="btn btn-primary" data-rnact="calendar-plus:${r[0]}">${I("calendar-plus")}${t("تمديد العقد","Extend contract")}</button></div>`,
   files:()=>`
    ${sect("pen-line","توقيع العقد","Contract signature",`<div class="signgrid">
     <div class="signbox ${["client","both"].includes(r[26])?"done":""}"><span class="sh">${I("user-round")}</span><b>${t("الطرف الثاني (العميل)","Second party (client)")}</b><small>${r[1]}</small>${["client","both"].includes(r[26])?`<span class="tag g">${I("check-circle-2")}${t("موقّع في","Signed")} ${r[34]}</span>`:`<span class="tag a">${I("clock")}${t("بانتظار التوقيع","Awaiting signature")}</span>`}</div>
     <div class="signbox ${r[26]==="both"?"done":""}"><span class="sh">${I("building-2")}</span><b>${t("الطرف الأول (الشركة)","First party (company)")}</b><small>${t("كليك آند مور","Click & More")}</small>${r[26]==="both"?`<span class="tag g">${I("check-circle-2")}${t("موقّع ومُعتمد","Signed and approved")}</span>`:`<span class="tag a">${I("clock")}${t("بانتظار الاعتماد","Awaiting approval")}</span>`}</div>
    </div>
    <div class="formbar"><button class="btn btn-ghost" data-rnprint="contract:${r[0]}">${I("printer")}${t("طباعة نسخة للتوقيع","Print for signing")}</button>${r[26]!=="both"?`<button class="btn btn-primary" data-rnact="pen-line:${r[0]}">${I("pen-line")}${t("تسجيل التوقيع","Record signature")}</button>`:""}</div>`)}
    ${sect("paperclip","المرفقات","Attachments",`
     ${[["نسخة العقد الموقّعة","Signed contract copy",r[26]==="both"?"contract_"+r[0]+"_signed.pdf":""],["إيصال الدفع","Payment receipt",r[23]?"receipt_"+r[0]+".pdf":""],["هوية العميل","Client ID","id_"+r[4]+".jpg"],["جواز العاملة","Worker passport","passport_"+r[8]+".jpg"]].map(x=>`<div class="att"><div class="att-t">${I("file-text")}<div><b>${t(x[0],x[1])}</b><small>${x[2]||t("غير مرفوع","Not uploaded")}</small></div></div><div class="att-a">${x[2]?`<button class="btn btn-ghost">${I("eye")}${t("عرض","Preview")}</button><button class="btn btn-ghost">${I("download")}${t("تنزيل","Download")}</button>`:`<button class="btn btn-ghost">${I("upload")}${t("رفع","Upload")}</button>`}</div></div>`).join("")}`)}`,
   log:()=>sect("history","السجل","Activity log",`<div class="tl">${[[r[34],t("إنشاء عقد التأجير","Rental contract created"),r[27]],[r[34],t("إصدار الفاتورة وسند القبض","Invoice and receipt bond issued"),r[27]],["client both".includes(r[26])?r[34]:"",r[26]==="unsigned"?"":t("توقيع العقد من العميل","Contract signed by client"),r[1]],[r[14],t("بداية سريان العقد","Contract term started"),t("النظام","System")],r[25]==="cancelled"?[r[15],t("إلغاء العقد قبل اكتمال المدة","Contract cancelled early"),r[27]]:null,r[25]==="moved"?[r[15],t("تحويل العقد إلى نقل كفالة","Converted to sponsorship transfer"),r[27]]:null].filter(x=>x&&x[1]).map(x=>`<div class="tli"><span class="dot"></span><div><b>${x[1]}</b><small>${x[2]}</small>${x[0]?`<small class="num">${x[0]}</small>`:""}</div></div>`).join("")}</div>`)
  };
  return `
 <div class="page-head compact"><div><a class="back" href="#" data-go="rental" style="margin:0 0 8px">${I("arrow-right")}<span>${t("الرجوع لعقود التأجير","Back to rentals")}</span></a><h1>${r[0]}</h1><p>${r[1]} · ${r[6]} · ${r[11]} · ${rnSt.find(x=>x[0]===r[25])[1]}</p></div>
 <div class="actions"><button class="btn btn-ghost" data-rnprint="invoice:${r[0]}">${I("receipt")}${t("الفاتورة","Invoice")}</button><button class="btn btn-ghost" data-rnprint="contract:${r[0]}">${I("printer")}${t("طباعة العقد","Print contract")}</button><button class="btn btn-primary" data-rnedit="${r[0]}">${I("pencil")}${t("تعديل العقد","Edit contract")}</button></div></div>
 <div class="showgrid3">
  <aside class="cvside">
   <div class="card idcard"><span class="bigph">${I("repeat")}</span><b>${r[1]}</b><small>${r[6]}</small>${rnStTag(r)}${rnSignTag(r)}
    <div class="idk">${[[t("الباقة","Package"),r[11]],[t("المتبقي أيام","Days left"),r[25]==="active"?r[17]:"—"],[t("القيمة","Value"),r[22].toLocaleString("en")],[t("المستحق","Due"),r[24].toLocaleString("en")]].map(x=>`<div><small>${x[0]}</small><b>${x[1]}</b></div>`).join("")}</div>
   </div>
   <div class="card acard"><div class="sec-h">${I("settings-2")}<b>${t("إجراءات العقد","Contract actions")}</b></div>
    <div class="acts2">${[["printer","طباعة العقد","Print contract",""],["receipt","طباعة الفاتورة","Print invoice",""],["banknote","سند القبض","Receipt bond",""],["pen-line","توقيع العقد","Sign contract",""],["calendar-plus","تمديد العقد","Extend",""],["arrow-left-right","نقل كفالة","To transfer",""],["check-circle-2","إغلاق العقد","Close",""],["x-circle","إلغاء قبل المدة","Cancel early","danger"]].map(x=>`<button class="atile ${x[3]}" data-rn${["printer","receipt","banknote"].includes(x[0])?`print="${x[0]==="printer"?"contract":x[0]==="receipt"?"invoice":"bond"}:${r[0]}"`:`act="${x[0]}:${r[0]}"`}>${I(x[0])}<span>${t(x[1],x[2])}</span></button>`).join("")}</div>
   </div>
  </aside>
  <div class="cvmain">
   <div class="vtabs">${tabs.map(x=>`<button class="${x[0]===RNSTAB?"on":""}" data-rnstab="${x[0]}">${I(x[3])}<span>${t(x[1],x[2])}</span></button>`).join("")}</div>
   <div class="tabbody">${(body[RNSTAB]||body.over)()}</div>
  </div>
 </div>`},
 rentalAdd:()=>{
  const ed=RNEDIT?rnRows.find(x=>x[0]===RNEDIT):null;
  const f=(ar,en,nm,val,type,req)=>{let ctl;
   if(type==="select")ctl=`<select>${(Array.isArray(val)?val:[val]).map((o,i)=>`<option${i===0?" selected":""}>${o}</option>`).join("")}</select>`;
   else if(type==="date")ctl=`<input type="date"${val?` value="${val}"`:""}>`;
   else if(type==="ro")ctl=`<input type="text" value="${val}" readonly dir="ltr">`;
   else ctl=`<input type="${type||"text"}" ${ed&&val?`value="${val}"`:`placeholder="${val||""}"`}${["client_phone","client_national_num","monthly_salary","hourly_rate","monthly_cost","paid_amount","remaining_amount","total_cost","period_cost","sponsorship_transfer_amount","salary_after_sponsorship","monthly_income"].includes(nm)?' dir="ltr"':""}>`;
   return `<div class="fld"><label>${t(ar,en)}${req?' <i class="rq">*</i>':""}<code>${nm}</code></label>${ctl}</div>`};
  const grid=fs=>`<div class="fgrid">${fs.join("")}</div>`;
  return `
 <div class="page-head"><div><a class="back" href="#" data-go="rental" style="margin:0 0 8px">${I("arrow-right")}<span>${t("عقود التأجير","Rental contracts")}</span></a><h1>${ed?t("تعديل عقد التأجير","Edit rental contract"):t("عقد تأجير جديد","New rental contract")}${ed?`<span class="tag a hdrtag">${I("pencil")}${t("تعديل","Editing")}</span>`:""}</h1>${ed?`<p><span class="num">${ed[0]}</span> · ${ed[1]} · ${ed[6]}</p>`:`<p>${t("اختر العميل والعاملة والباقة، ثم أكمل بنود العقد والسداد — وتُصدر الفاتورة وسند القبض عند الحفظ.","Pick client, worker and package, then complete terms and payment — invoice and receipt bond are issued on save.")}</p>`}</div>
 <div class="actions"><button class="btn btn-ghost" data-go="rental">${t("إلغاء","Cancel")}</button><button class="btn btn-primary" data-rnsave>${I("check")}${ed?t("حفظ التعديلات","Save changes"):t("حفظ وإصدار الفاتورة","Save & issue invoice")}</button></div></div>
 ${ed?"":`<div class="seg tabs">${[["existing","عميل قائم","Existing client"],["new","عميل جديد","New client"]].map(x=>`<button class="${RNADDTAB===x[0]?"on":""}" data-rnaddtab="${x[0]}">${t(x[1],x[2])}</button>`).join("")}</div>`}
 <div class="cvform">
  <aside class="cvopts">
   <div class="card"><div class="sec-h">${I("list-checks")}<b>${t("أقسام العقد","Contract sections")}</b></div>
    <div class="steps">${[["r1","العميل","Client"],["r2","العاملة","Worker"],["r3","الباقة والمدة","Package & term"],["r4","بنود العقد","Contract terms"],["r5","السداد والمرفقات","Payment & files"]].map((x,i)=>`<button class="step" data-rnjump="${x[0]}"><span class="stepn num">${i+1}</span><span>${t(x[1],x[2])}</span></button>`).join("")}</div></div>
   <div class="card"><div class="sec-h">${I("package")}<b>${t("الباقة","Package")}</b></div>
    <div class="pkgs">${rnPackages.map(p=>`<button class="pkg ${(ed?ed[11]:"ثلاثة أشهر")===p[0]?"on":""}"><b>${p[0]}</b><small>${p[3]} ${t("يوم","days")}</small><span class="num">${p[4].toLocaleString("en")}</span></button>`).join("")}</div></div>
   <div class="card"><div class="sec-h">${I("settings-2")}<b>${t("الفرع والمسوّق","Branch & marketer")}</b></div>
    <div class="actrow sm col"><label>${t("الفرع","Branch")}<code>branch_id</code></label><select class="statsel">${["الفرع الرئيسي","فرع جدة","فرع الدمام"].map(x=>`<option${ed&&x===ed[28]?" selected":""}>${x}</option>`).join("")}</select></div>
    <div class="actrow sm col"><label>${t("المسوّق","Marketer")}<code>marketer_id</code></label><select class="statsel">${["سارة القحطاني","خالد العمري","محمد الشهراني","مشرف النظام"].map(x=>`<option${ed&&x===ed[27]?" selected":""}>${x}</option>`).join("")}</select></div></div>
  </aside>
  <div class="cvfields">
   <div class="card sec" id="r1"><div class="fs-h"><span class="fs-n num">1</span><b>${t("بيانات العميل","Client data")}</b></div>
    ${(!ed&&RNADDTAB==="existing")
     ?grid([f("اختر عميلاً قائماً","Select existing client","new_existing_client_id",["منيرة الشهري — 1042887331","شركة نجد للمقاولات — 3001122447","نورة الحربي — 1055881003"],"select",1),f("نوع العميل","Client type","client_type",[t("فرد","Individual"),t("شركة","Company")],"select"),f("المدينة","City","client_city",["الرياض","جدة","الدمام"],"select")])
     :grid([f("نوع العميل","Client type","client_type",ed&&ed[2]==="company"?[t("شركة","Company"),t("فرد","Individual")]:[t("فرد","Individual"),t("شركة","Company")],"select",1),
       f("اسم العميل","Client name","client_name",ed?ed[1]:"الاسم الثلاثي","",1),f("رقم الجوال","Phone","client_phone",ed?ed[3]:"+966 5X XXX XXXX","",1),
       f("رقم جوال إضافي","Additional phone","client_phone_2","+966 5X XXX XXXX",""),f("الجنسية","Nationality","client_nationality_id",["سعودي","مصري","سوري","يمني"],"select",1),
       f("رقم الهوية","National ID","client_national_num",ed?ed[4]:"1XXXXXXXXX","",1),f("رقم الرخصة","Licence number","license_number",""),
       f("البريد الإلكتروني","Email","client_email","name@example.com","email"),f("تاريخ الميلاد","Birth date","client_birthdate","1988-04-12","date",1),
       f("المدينة","City","client_city",ed?[ed[5]].concat(["الرياض","جدة","الدمام"].filter(x=>x!==ed[5])):["الرياض","جدة","الدمام"],"select",1),
       f("العنوان التفصيلي","Address","client_address","الحي والشارع"),f("عدد الغرف","Rooms","rooms","0"),
       f("عدد أفراد الأسرة","Family number","family_num","0"),f("عدد الأطفال (تحت 12)","Children","children","0"),
       f("المهنة","Profession","profession","المهنة الحالية","",1),f("الدخل الشهري","Monthly income","monthly_income","18,000","",1)])}</div>
   <div class="card sec" id="r2"><div class="fs-h"><span class="fs-n num">2</span><b>${t("اختيار العاملة","Select worker")}</b></div>
    ${grid([f("اختر عاملة","Select worker","maid_id",ed?[ed[6]+" — "+ed[8]]:["ماريتس دي لا كروز — P7741203","سري واهيوني — P5520914","كوماري بيريرا — P3391077"],"select",1),
      f("رقم الجواز","Passport","maid_passport",ed?ed[8]:"يُقرأ من السيرة","ro"),
      f("الجنسية","Nationality","nationality",ed?ed[9]:"يُقرأ من السيرة","ro"),
      f("المهنة","Occupation","maid_job",ed?[ed[10]].concat(["عاملة منزلية","طباخة","ممرضة منزلية","سائق خاص"].filter(x=>x!==ed[10])):["عاملة منزلية","طباخة","ممرضة منزلية","سائق خاص"],"select"),
      f("الديانة","Religion","religion",["مسيحية","مسلمة","أخرى"],"select"),
      f("نوع الطلب","Request type","request_type",["طلب مخصص","طلب عام"],"select"),
      f("سنوات الخبرة","Experience years","experience_years","4"),f("العمر","Age","age","31")])}</div>
   <div class="card sec" id="r3"><div class="fs-h"><span class="fs-n num">3</span><b>${t("الباقة والمدة","Package & term")}</b></div>
    ${grid([f("اختر الباقة","Select package","package_id",ed?[ed[11]].concat(rnPackages.map(p=>p[0]).filter(x=>x!==ed[11])):rnPackages.map(p=>p[0]),"select",1),
      f("تاريخ العقد","Contract date","contract_date",ed?ed[34]:"","date",1),
      f("تكلفة الفترة","Period cost","period_cost",ed?ed[22].toLocaleString("en"):"12,900","",1),
      f("مدة العقد بالشهور للحساب","Duration (months)","contract_duration_months",ed?String(ed[12]):"3","",1),
      f("مدة العقد بالأيام","Duration (days)","contract_duration_days",ed?String(ed[13]):"90"),
      f("تكلفة العقد الإجمالية","Total contract cost","total_cost",ed?ed[22].toLocaleString("en"):"12,900","ro"),
      f("الإنجاز من","Ingaz from","ingaz_from",ed?ed[14]:"","date",1),
      f("الإنجاز إلى","Ingaz to","ingaz_to",ed?ed[15]:"","date")])}</div>
   <div class="card sec" id="r4"><div class="fs-h"><span class="fs-n num">4</span><b>${t("بنود العقد التشغيلية","Operational terms")}</b></div>
    ${grid([f("ساعات العمل اليومية","Working hours per day","working_hours_per_day",ed?String(ed[18]):"8","",1),
      f("أجرة الساعة (ريال)","Hourly rate (SAR)","hourly_rate",ed?String(ed[19]):"45","",1),
      f("الراتب الشهري (ريال)","Monthly salary (SAR)","monthly_salary",ed?ed[20].toLocaleString("en"):"1,300","",1),
      f("التكلفة الشهرية (ريال)","Monthly cost (SAR)","monthly_cost",ed?ed[21].toLocaleString("en"):"4,300","",1),
      f("نوع التأجير","Rental type","rent_type",["تأجير شهري","تأجير بالساعة","تأجير موسمي"],"select"),
      f("نقل الكفالة","Sponsorship transfer","has_transfer",[t("لا","No"),t("نعم","Yes")],"select"),
      f("مبلغ نقل الكفالة (ريال)","Transfer amount (SAR)","sponsorship_transfer_amount",ed&&ed[32]?ed[32].toLocaleString("en"):"0"),
      f("الراتب بعد نقل الكفالة (ريال)","Salary after transfer (SAR)","salary_after_sponsorship",ed&&ed[33]?ed[33].toLocaleString("en"):"0")])}
    <div class="fld full"><label>${t("ملاحظات إضافية","Additional notes")}<code>notes</code></label><textarea rows="3"></textarea></div></div>
   <div class="card sec" id="r5"><div class="fs-h"><span class="fs-n num">5</span><b>${t("السداد والمرفقات","Payment & attachments")}</b></div>
    ${grid([f("طريقة الدفع","Payment method","payment_type",ed?[ed[29]].concat(rnPayTypes.filter(x=>x!==ed[29])):rnPayTypes,"select",1),
      f("حالة الدفع","Payment status","payment_status",ed?[ed[30]].concat([t("مسدّد","Settled"),t("جزئي","Partial"),t("غير مسدّد","Unpaid")].filter(x=>x!==ed[30])):[t("مسدّد","Settled"),t("جزئي","Partial"),t("غير مسدّد","Unpaid")],"select",1),
      f("المبلغ المدفوع","Paid amount","paid_amount",ed?ed[23].toLocaleString("en"):"0"),
      f("البنك / حساب السداد","Bank / receipt account","receipt_account_id",rnAccounts,"select",1),
      f("المبلغ المتبقي","Remaining amount","remaining_amount",ed?ed[24].toLocaleString("en"):"0","ro")])}
    <div class="fld full"><label>${t("إيصال الدفع","Payment receipt")}<code>payment_receipt</code></label><div class="ctl file">${I("upload")}<span>${t("اختر الملف","Select file")}</span></div></div>
    <p class="pnote">${I("info")}${t("عند الحفظ تُفتح تلقائياً نسخة العقد للطباعة والفاتورة وسند القبض.","On save, the contract, invoice and receipt bond print views open automatically.")}</p></div>
   <div class="formbar"><button class="btn btn-ghost" data-go="rental">${t("إلغاء","Cancel")}</button><button class="btn btn-primary" data-rnsave>${I("check")}${ed?t("حفظ التعديلات","Save changes"):t("حفظ وإصدار الفاتورة","Save & issue invoice")}</button></div>
  </div>
 </div>`},
 rentalInvoices:()=>{
  const sum=k=>rnInv.reduce((n,x)=>n+x[k],0);
  const due=rnInv.reduce((n,x)=>n+(x[4]-x[6]-x[7]),0);
  return `
 <div class="page-head"><div><a class="back" href="#" data-go="rental" style="margin:0 0 8px">${I("arrow-right")}<span>${t("عقود التأجير","Rental contracts")}</span></a><h1>${t("فواتير التأجير","Rental invoices")}</h1><p>${t("فواتير عقود التأجير مع الضريبة والخصم والمدفوع والمتبقي، وطباعة الفاتورة وسند القبض.","Rental invoices with VAT, discount, paid and remaining, plus invoice and receipt-bond printing.")}</p></div>
 <div class="actions"><button class="btn btn-ghost">${I("file-spreadsheet")}Excel</button><button class="btn btn-primary">${I("plus")}${t("إنشاء فاتورة","New invoice")}</button></div></div>
 <div class="statgrid">${[["إجمالي الفواتير","Total invoiced",sum(4).toLocaleString("en"),"receipt","n"],["الضريبة (15%)","VAT (15%)",sum(5).toLocaleString("en"),"percent","n"],["المحصّل","Collected",sum(7).toLocaleString("en"),"check-circle-2","g"],["المتبقي","Remaining",due.toLocaleString("en"),"alarm-clock","r"]].map(x=>`<div class="scard"><span class="sci ${x[4]}">${I(x[3])}</span><span class="sct">${t(x[0],x[1])}</span><b class="num">${x[2]}</b></div>`).join("")}</div>
 <div class="card flush"><div class="tw"><table class="grp"><thead><tr><th>${t("رقم الفاتورة","Invoice no.")}</th><th>${t("العقد والعميل","Contract & client")}</th><th>${t("التاريخ","Date")}</th><th>${t("الإجمالي","Total")}</th><th>${t("الضريبة","VAT")}</th><th>${t("الخصم","Discount")}</th><th>${t("المدفوع","Paid")}</th><th>${t("المتبقي","Remaining")}</th><th>${t("الحالة","Status")}</th><th class="act">${t("الإجراءات","Actions")}</th></tr></thead><tbody>
 ${rnInv.map(x=>{const rem=x[4]-x[6]-x[7];return `<tr>
  <td><b class="num">${x[0]}</b></td>
  <td><div class="gcell"><b class="num">${x[1]}</b><small>${x[2]}</small></div></td>
  <td class="num">${x[3]}</td><td class="num">${x[4].toLocaleString("en")}</td><td class="num">${x[5].toLocaleString("en")}</td><td class="num">${x[6].toLocaleString("en")}</td><td class="num">${x[7].toLocaleString("en")}</td>
  <td class="num${rem?" due":""}">${rem.toLocaleString("en")}</td>
  <td><span class="st tag ${x[10]}"><i></i>${t(x[8],x[9])}</span></td>
  <td class="act"><div class="rowacts"><button class="icon-btn pri" data-rnprint="invoice:${x[1]}" title="${t("طباعة الفاتورة","Print invoice")}">${I("printer")}</button><button class="icon-btn pri" data-rnprint="bond:${x[1]}" title="${t("سند القبض","Receipt bond")}">${I("banknote")}</button><button class="icon-btn pri" data-rnshow="${x[1]}" title="${t("العقد","Contract")}">${I("eye")}</button></div></td></tr>`}).join("")}
 </tbody></table></div>
 <div class="tfoot"><span>${t(`عرض ${rnInv.length} من ${rnInv.length} فواتير`,`Showing ${rnInv.length} of ${rnInv.length} invoices`)}</span></div></div>`},
 rentalSettings:()=>`
 <div class="page-head"><div><a class="back" href="#" data-go="rental" style="margin:0 0 8px">${I("arrow-right")}<span>${t("عقود التأجير","Rental contracts")}</span></a><h1>${t("باقات التأجير والإعدادات","Rental packages & settings")}</h1><p>${t("الباقات المعتمدة ومدتها وقيمتها، وإعدادات الطباعة والتوقيع والضريبة.","Approved packages with duration and price, plus print, signature and VAT settings.")}</p></div>
 <div class="actions"><button class="btn btn-primary">${I("plus")}${t("إضافة باقة","Add package")}</button></div></div>
 ${sect("package","الباقات","Packages",`<div class="tw"><table><thead><tr><th>${t("الباقة","Package")}</th><th>${t("المدة بالشهور","Months")}</th><th>${t("المدة بالأيام","Days")}</th><th>${t("القيمة","Price")}</th><th>${t("تكلفة اليوم","Day cost")}</th><th>${t("الحالة","Status")}</th><th class="act"></th></tr></thead><tbody>
 ${rnPackages.map(p=>`<tr><td><b>${p[0]}</b></td><td class="num">${p[2]}</td><td class="num">${p[3]}</td><td class="num">${p[4].toLocaleString("en")}</td><td class="num">${Math.round(p[4]/p[3])}</td><td><span class="st tag g"><i></i>${t("مفعّلة","Active")}</span></td><td class="act"><div class="rowacts"><button class="icon-btn pri">${I("pencil")}</button><button class="icon-btn">${I("trash-2")}</button></div></td></tr>`).join("")}
 </tbody></table></div>`)}
 ${sect("settings-2","إعدادات العقد والطباعة","Contract & print settings",`<div class="fgrid">
  <div class="fld"><label>${t("نسبة الضريبة","VAT rate")}<code>vat_rate</code></label><input type="text" value="15%" dir="ltr"></div>
  <div class="fld"><label>${t("ساعات العمل الافتراضية","Default working hours")}<code>default_hours</code></label><input type="text" value="8" dir="ltr"></div>
  <div class="fld"><label>${t("أجرة الساعة الافتراضية","Default hourly rate")}<code>default_hourly_rate</code></label><input type="text" value="45" dir="ltr"></div>
  <div class="fld"><label>${t("إظهار الشعار في الطباعة","Show logo when printing")}<code>print_logo</code></label><select><option>${t("نعم","Yes")}</option><option>${t("لا","No")}</option></select></div>
  <div class="fld"><label>${t("التوقيع الإلكتروني","Electronic signature")}<code>e_signature</code></label><select><option>${t("مفعّل","Enabled")}</option><option>${t("معطّل","Disabled")}</option></select></div>
  <div class="fld"><label>${t("فتح الطباعة بعد الحفظ","Open print after save")}<code>auto_print</code></label><select><option>${t("العقد والفاتورة وسند القبض","Contract, invoice and bond")}</option><option>${t("العقد فقط","Contract only")}</option><option>${t("لا شيء","None")}</option></select></div>
 </div>`)}
 ${sect("scroll-text","بنود عقد التأجير المطبوع","Printed contract terms",`<div class="convbox"><div><b>${t("البنود مخزّنة كسجلات على ثلاث صفحات","Terms are stored as records across three pages")}</b><small>${t(`${rnTermRows.length} بنداً في ${rnTermPages.length} صفحات — لكل بند عنوان قسم ورقم وترتيب وحالة تفعيل.`,`${rnTermRows.length} terms across ${rnTermPages.length} pages — each with a section title, number, order and active state.`)}</small></div><button class="btn btn-primary" data-go="rentalTerms">${I("pencil")}${t("تعديل بنود العقد","Edit contract terms")}</button></div>`)}`
};
let RNTERMPAGE=1;
rentalScreens.rentalTerms=()=>`
 <div class="page-head"><div><a class="back" href="#" data-go="rentalSettings" style="margin:0 0 8px">${I("arrow-right")}<span>${t("باقات التأجير والإعدادات","Rental packages & settings")}</span></a><h1>${t("تعديل بنود عقد التأجير","Edit rental contract terms")}</h1><p>${t("البنود المطبوعة على العقد — ثلاث صفحات، كل بند له عنوان قسم ورقم ومحتوى وترتيب وحالة تفعيل.","Printed contract terms — three pages; each term has a section title, number, content, order and active state.")}</p></div>
 <div class="actions"><button class="btn btn-ghost">${I("download")}${t("استيراد البنود الافتراضية","Import default terms")}</button><button class="btn btn-primary" data-rntermsave>${I("check")}${t("حفظ التغييرات","Save changes")}</button></div></div>
 <div class="seg tabs">${rnTermPages.map(p=>`<button class="${RNTERMPAGE===p?"on":""}" data-rntermpage="${p}">${t(`الصفحة ${["الأولى","الثانية","الثالثة"][p-1]}`,`Page ${p}`)}<b class="segb num">${rnTermRows.filter(x=>x[0]===p).length}</b></button>`).join("")}</div>
 ${sect("scroll-text",`بنود الصفحة ${["الأولى","الثانية","الثالثة"][RNTERMPAGE-1]}`,`Page ${RNTERMPAGE} terms`,`<div class="termedit">${rnTermRows.filter(x=>x[0]===RNTERMPAGE).map((x,i)=>`<div class="termrow">
  <div class="tr-g"><label>${t("عنوان القسم","Section title")}<code>section_title</code></label><input type="text" value="${x[1]}"></div>
  <div class="tr-s"><label>${t("رقم البند","Term no.")}<code>term_number</code></label><input type="text" value="${x[3]}" dir="ltr"></div>
  <div class="tr-c"><label>${t("محتوى البند","Term content")}<code>term_content</code></label><textarea rows="3">${x[4]}</textarea></div>
  <div class="tr-s"><label>${t("الترتيب","Order")}<code>term_order</code></label><input type="text" value="${x[5]}" dir="ltr"></div>
  <div class="tr-s"><label>${t("تفعيل","Active")}<code>is_active</code></label><span class="sw ${x[6]?"on":""}"></span></div>
  <button class="icon-btn danger" title="${t("حذف البند","Delete term")}">${I("trash-2")}</button>
 </div>`).join("")}</div>
 <div class="formbar"><button class="btn btn-ghost">${I("plus")}${t("إضافة بند جديد","Add new term")}</button><button class="btn btn-primary" data-rntermsave>${I("check")}${t("حفظ التغييرات","Save changes")}</button></div>`)}`;
Object.assign(T,{rentalTerms:{ar:"بنود عقد التأجير",en:"Contract terms"}});
Object.assign(T,{rental:{ar:"عقود التأجير",en:"Rental contracts"},rentalShow:{ar:"تفاصيل عقد التأجير",en:"Rental details"},rentalAdd:{ar:"عقد تأجير",en:"Rental contract"},rentalInvoices:{ar:"فواتير التأجير",en:"Rental invoices"},rentalSettings:{ar:"باقات التأجير",en:"Rental packages"}});
Object.assign(screens,rentalScreens);
(function(){const g=nav.find(x=>x.t.ar==="العمليات");if(g){const it=g.items.find(i=>i.id==="rental");if(it)it.b="13"}})();
