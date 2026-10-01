const T={
 dashboard:{ar:"لوحة التحكم",en:"Dashboard"},contracts:{ar:"عقود الاستقدام",en:"Recruitment contracts"},reports:{ar:"مركز التقارير",en:"Reports centre"}
};
const I=(n,c="")=>`<i data-lucide="${n}" class="${c}"></i>`;
const nav=[
 {t:{ar:"عام",en:"General"},items:[
  {id:"dashboard",i:"layout-dashboard",ar:"الرئيسية",en:"Home"},
  {id:"reports",i:"folder-open",ar:"مركز التقارير",en:"Reports centre"}]},
 {t:{ar:"العمليات",en:"Operations"},items:[
  {id:"contracts",i:"file-text",ar:"عقود الاستقدام",en:"Recruitment contracts",b:"115"},
  {id:"orders",i:"shopping-cart",ar:"الطلبات والحجوزات",en:"Orders & bookings",b:"121"},
  {id:"cvs",i:"files",ar:"السير الذاتية",en:"CVs"},
  {id:"rental",i:"repeat",ar:"عقود التأجير",en:"Rental contracts"},
  {id:"housing",i:"home",ar:"إدارة الإيواء",en:"Housing"},
  {id:"transfer",i:"arrow-left-right",ar:"نقل الكفالة",en:"Sponsorship transfer",b:"17"}]},
 {t:{ar:"العملاء",en:"Customers"},items:[
  {id:"clients",i:"users",ar:"جميع العملاء",en:"All clients"},
  {id:"msgs",i:"message-square",ar:"المحادثات والرسائل",en:"Conversations"},
  {id:"claims",i:"alert-triangle",ar:"الشكاوى",en:"Complaints",b:"4"}]},
 {t:{ar:"الإدارة",en:"Administration"},items:[
  {id:"finance",i:"wallet",ar:"المالية",en:"Finance",kids:[
   {id:"fin-constraint",ar:"القيود اليومية",en:"Journal entries"},
   {id:"fin-bonds",ar:"سندات القبض والصرف",en:"Receipt & payment bonds"},
   {id:"fin-sales",ar:"فواتير البيع",en:"Sales invoices"},
   {id:"fin-purchases",ar:"فواتير الشراء",en:"Purchase invoices"},
   {id:"fin-accounts",ar:"شجرة الحسابات",en:"Chart of accounts"},
   {id:"fin-statement",ar:"كشف الحساب",en:"Account statement"},
   {id:"fin-banks",ar:"البنوك والصناديق",en:"Banks & cashboxes"},
   {id:"fin-items",ar:"الأصناف والمخزون",en:"Items & stock"}]},
  {id:"hr",i:"user-cog",ar:"الموارد البشرية",en:"HR",kids:[
   {id:"hr-employees",ar:"الموظفون",en:"Employees"},
   {id:"hr-attendance",ar:"الحضور والانصراف",en:"Attendance"},
   {id:"hr-vacations",ar:"الإجازات والاستئذان",en:"Vacations & permissions"},
   {id:"hr-payroll",ar:"الرواتب والمسيّرات",en:"Salaries & payrolls"},
   {id:"hr-advances",ar:"السلف والمكافآت",en:"Advances & rewards"},
   {id:"hr-sanctions",ar:"الجزاءات",en:"Sanctions"},
   {id:"hr-custody",ar:"العهد والحوافظ",en:"Custodies"},
   {id:"hr-departments",ar:"الأقسام والوظائف",en:"Departments & jobs"}]},
  {id:"settings",i:"settings",ar:"إعدادات النظام",en:"Settings",kids:[
   {id:"set-general",ar:"البيانات العامة",en:"General data"},
   {id:"set-branches",ar:"الفروع",en:"Branches"},
   {id:"set-users",ar:"المستخدمون",en:"Admin users"},
   {id:"set-roles",ar:"الأدوار والصلاحيات",en:"Roles & permissions"},
   {id:"set-lists",ar:"القوائم والتصنيفات",en:"Lists & categories"},
   {id:"set-integrations",ar:"التكاملات",en:"Integrations"}]}]}];
const kpis=[
 {i:"file-text",ar:"عقود الاستقدام",en:"Recruitment contracts",v:115,tags:[["27 قبل الوصول","27 pre-arrival",""],["2 تحت الضمان","2 under warranty","a"]],p:74},
 {i:"shopping-cart",ar:"الطلبات (الحجوزات)",en:"Orders (bookings)",v:121,tags:[["108 مكتملة","108 completed","g"],["7 تحت الإجراء","7 in progress",""]],p:89},
 {i:"files",ar:"السير الذاتية",en:"CVs",v:235,tags:[["163 متاحة","163 available","g"],["142 محجوزة","142 reserved","n"]],p:69,g:1},
 {i:"users",ar:"العملاء",en:"Clients",v:241,tags:[["39 لديهم طلبات","39 with orders","n"],["202 بدون طلبات","202 without","" ]],p:16}];
const stages=[["عقود جديدة","New contracts","أول مرحلة تشغيلية","First operational stage",10,26],["مرحلة الوصول","Arrival stage","تذاكر مؤكدة في الطريق","Confirmed tickets",0,0],["تحت الضمان","Under warranty","حسب تاريخ انتهاء الضمان","By warranty end date",2,5],["مكتملة","Completed","وصول + مكتمل","Arrived + completed",8,21],["متأخرة بالوصول","Late arrival","تجاوزت 60 يوماً بدون تذاكر","Over 60 days, no tickets",18,47]];
const months=["يناير","فبراير","مارس","أبريل","مايو","يونيو","يوليو","أغسطس","سبتمبر","أكتوبر","نوفمبر","ديسمبر"];
const monthsEn=["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
const d26=[3,7,3,15,19,8,13,2,1,1,43,0],d25=[5,4,9,6,11,14,7,5,3,6,12,8];
const rows=[
 ["CN-2026-0115","خالد العتيبي","Khalid Alotaibi","عاملة منزلية","Domestic worker","الفلبين","Philippines","نشط","Active","g","17 أغسطس 2026"],
 ["CN-2026-0114","شركة نجد للمقاولات","Najd Contracting","سائق خاص","Private driver","الهند","India","تحت الإجراء","In progress","a","16 أغسطس 2026"],
 ["CN-2026-0113","منيرة الشهري","Munira Alshehri","عاملة منزلية","Domestic worker","كينيا","Kenya","مكتمل","Completed","n","14 أغسطس 2026"],
 ["CN-2026-0112","عبدالله الحربي","Abdullah Alharbi","ممرض منزلي","Home nurse","الفلبين","Philippines","متأخر","Delayed","r","11 أغسطس 2026"],
 ["CN-2026-0111","مؤسسة الوفاء","Alwafa Est.","عامل نظافة","Cleaner","بنغلاديش","Bangladesh","نشط","Active","g","09 أغسطس 2026"],
 ["CN-2026-0110","سارة القحطاني","Sara Alqahtani","طباخة","Cook","سريلانكا","Sri Lanka","تحت الضمان","Under warranty","a","05 أغسطس 2026"],
 ["CN-2026-0109","فهد الدوسري","Fahad Aldosari","سائق خاص","Private driver","أوغندا","Uganda","مكتمل","Completed","n","02 أغسطس 2026"],
 ["CN-2026-0108","شركة الأفق","Alufuq Co.","عاملة منزلية","Domestic worker","إثيوبيا","Ethiopia","نشط","Active","g","29 يوليو 2026"]];
const jobs=[["عاملة منزلية","Domestic worker",107],["سائق خاص","Private driver",38],["ممرض منزلي","Home nurse",21],["طباخة","Cook",14],["عامل نظافة","Cleaner",9],["غير محدد","Unspecified",8]];
const notifs=[
 ["r","alert-triangle","عقد متأخر بالوصول","Contract late on arrival","العقد CN-2026-0112 تجاوز 60 يوماً دون تأكيد تذكرة.","CN-2026-0112 passed 60 days with no ticket confirmation.","قبل 12 دقيقة","12 minutes ago",1],
 ["a","message-square","شكوى بدون رد","Unanswered complaint","شكوى العميل منيرة الشهري مفتوحة منذ 3 أيام.","Munira Alshehri’s complaint has been open for 3 days.","قبل ساعة","1 hour ago",1],
 ["g","check-circle-2","وصول مؤكد","Arrival confirmed","وصلت العاملة الخاصة بالعقد CN-2026-0109 إلى الرياض.","Worker for CN-2026-0109 arrived in Riyadh.","قبل 3 ساعات","3 hours ago",1],
 ["n","wallet","دفعة مستحقة","Payment due","فاتورة بقيمة 12,400 ر.س تستحق غداً للعميل شركة نجد.","A 12,400 SAR invoice for Najd Contracting is due tomorrow.","أمس","Yesterday",0],
 ["n","upload","سير ذاتية جديدة","New CVs uploaded","أضاف الوكيل في الفلبين 14 سيرة ذاتية للمراجعة.","The Philippines agent added 14 CVs for review.","أمس","Yesterday",0]];
const notifList=()=>notifs.map(n=>`<div class="nt ${n[8]?"unread":""}"><span class="ni ${n[0]}">${I(n[1])}</span><div><b>${t(n[2],n[3])}</b><p>${t(n[4],n[5])}</p><time>${t(n[6],n[7])}</time></div>${n[8]?'<span class="udot"></span>':""}</div>`).join("");
const banner=()=>`<div class="banner" data-banner>${I("wrench")}<span><b>${t("تحديث مجدول للنظام","Scheduled system update")}</b> — ${t("الجمعة 21 أغسطس، 11:00م – 1:00ص. قد يتوقف إصدار العقود مؤقتاً.","Friday 21 August, 11:00pm – 1:00am. Contract issuing may pause briefly.")}</span><button class="x" data-banner-x>${I("x")}</button></div>`;
let L="ar",ROLE="staff";
const ROLES={
 agent:{ph:"agent@example.com",lab:{ar:"اسم المستخدم أو البريد الإلكتروني",en:"Username or email"},
  rl:{ar:"دخول الوكلاء الخارجيين",en:"External agent access"},ic:"globe",
  alt:{ar:"الرجوع إلى دخول الموظفين",en:"Back to employee sign-in"},
  t:{ar:"مساحة عمل الوكلاء الخارجيين",en:"External agents workspace"},
  s:{ar:"إدارة السير الذاتية والترشيحات ومتابعة مراحل العقد حتى الوصول.",en:"Manage CVs, nominations and contract stages through arrival."}},
 staff:{ph:"name@clickerp.sa",lab:{ar:"اسم المستخدم أو البريد الإلكتروني",en:"Username or email"},
  rl:{ar:"دخول الموظفين والإدارة",en:"Employee & admin access"},ic:"building-2",
  alt:{ar:"دخول الوكلاء الخارجيين",en:"External agent sign-in"},
  t:{ar:"منصة واحدة لإدارة العقود والعمليات والعملاء",en:"One platform for contracts, operations and clients"},
  s:{ar:"متابعة لحظية لمراحل العقود، الطلبات، الإيواء والمالية من مكان واحد.",en:"Live tracking of contract stages, orders, housing and finance in one place."}}};
const t=(a,e)=>L==="ar"?a:e;
/* ---------- chart ---------- */
function chart(w=880,h=280){
 const pad={t:14,r:8,b:34,l:34},max=48,gw=(w-pad.l-pad.r)/12,bw=Math.min(16,gw/3.4),g=[];
 for(let v=0;v<=max;v+=12){const y=pad.t+(1-v/max)*(h-pad.t-pad.b);g.push(`<line x1="${pad.l}" x2="${w-pad.r}" y1="${y}" y2="${y}" stroke="#eef1f6"/><text x="${pad.l-8}" y="${y+4}" text-anchor="end" font-size="11" fill="#9aa4b4">${v}</text>`)}
 const bars=months.map((m,i)=>{
  const x=pad.l+i*gw+gw/2,base=h-pad.b,sc=v=>(v/max)*(h-pad.t-pad.b);
  const b1=sc(d25[i]),b2=sc(d26[i]);
  return `<rect x="${x-bw-2}" y="${base-b1}" width="${bw}" height="${b1}" rx="3" fill="#c9d3e2"/><rect x="${x+2}" y="${base-b2}" width="${bw}" height="${b2}" rx="3" fill="#16233f"/><text x="${x}" y="${h-12}" text-anchor="middle" font-size="11" fill="#8b95a6">${L==="ar"?m.slice(0,5):monthsEn[i]}</text>`}).join("");
 return `<svg viewBox="0 0 ${w} ${h}" style="width:100%;height:280px" dir="ltr">${g.join("")}${bars}</svg>`;
}
/* ---------- screens ---------- */
const kpiCards=()=>kpis.map(k=>`<div class="card kpi"><div class="kl">${I(k.i)}<span>${t(k.ar,k.en)}</span></div><div class="kv num">${k.v}</div><div class="kb">${k.tags.map(x=>`<span class="tag ${x[2]}">${t(x[0],x[1])}</span>`).join("")}</div><div class="bar"><i class="${k.g?"g":""}" style="width:${k.p}%"></i></div></div>`).join("");
const stageList=()=>stages.map(s=>`<div class="stage-row"><div class="sr"><b>${t(s[0],s[1])}</b><span class="v num">${s[4]}</span></div><div class="sr"><small>${t(s[2],s[3])}</small><small class="num">${s[5]}%</small></div><div class="bar"><i style="width:${s[5]}%"></i></div></div>`).join("");
const table=(n=6,foot=false)=>`<div class="tw"><table><thead><tr><th>${t("رقم العقد","Contract")}</th><th>${t("العميل","Client")}</th><th>${t("المهنة","Occupation")}</th><th>${t("الدولة","Country")}</th><th>${t("الحالة","Status")}</th><th>${t("آخر تحديث","Last update")}</th><th></th></tr></thead><tbody>${rows.slice(0,n).map(r=>`<tr><td><b class="num">${r[0]}</b></td><td><div class="who"><span class="av">${t(r[1],r[2]).trim()[0]}</span><div><b>${t(r[1],r[2])}</b></div></div></td><td>${t(r[3],r[4])}</td><td>${t(r[5],r[6])}</td><td><span class="st tag ${r[9]}"><i></i>${t(r[7],r[8])}</span></td><td class="num">${r[10]}</td><td><button class="icon-btn">${I("more-horizontal")}</button></td></tr>`).join("")}</tbody></table></div>${foot?`<div class="tfoot"><span>${t("عرض 1–8 من 115 عقد","Showing 1–8 of 115")}</span><div class="pager"><button>${t("السابق","Prev")}</button><button class="on">1</button><button>2</button><button>3</button><button>${t("التالي","Next")}</button></div></div>`:""}`;
const skel=(h=200)=>`<div class="skel" style="height:${h}px"><span>${t("جارٍ التحميل…","Loading…")}</span></div>`;
const exec=[
 ["file-text","عقود تحت التشغيل","Contracts in progress",38,"18 متأخرة بالوصول","18 late on arrival","r"],
 ["shopping-cart","طلبات تحت الإجراء","Orders in progress",13,"108 مكتملة هذا الشهر","108 completed this month","g"],
 ["wallet","مستحقات لم تُحصّل","Uncollected receivables","184,600","7 فواتير تجاوزت الموعد","7 invoices overdue","a"],
 ["alert-triangle","بنود تحتاج قراراً","Items needing a decision",4,"منذ 3 أيام فأكثر","3+ days waiting","r"]];
const reports=[
 ["register","file-text","سجل العقود التفصيلي","Detailed contract register","كل العقود مع المرحلة والعميل والمهنة والدولة.","Every contract with stage, client, occupation and country.","ثقيل · يُحسب عند الطلب","Heavy · on demand","العمليات والإدارة","Operations & management"],
 ["volume","trending-up","حركة العقود الشهرية","Monthly contract volume","مقارنة سنوية مع متوسط مدة الإنجاز.","Year-over-year with average completion time.","متوسط · لقطة يومية","Medium · daily snapshot","الإدارة","Management"],
 ["people","users","توزيع الجنسيات والمهن","Nationalities & occupations","السير المتاحة والمحجوزة حسب الدولة والمهنة.","Available and reserved CVs by country and occupation.","خفيف · لقطة محفوظة","Light · cached","العمليات","Operations"],
 ["offices","building-2","مخزون المكاتب الخارجية","External office stock","22 مكتباً مع متوسط مدة الوصول ونسبة الإرجاع.","22 offices with average arrival time and return rate.","ثقيل · يُحسب عند الطلب","Heavy · on demand","العمليات","Operations"],
 ["finance","wallet","الحركة المالية والذمم","Finance & receivables","القيود والفواتير والمستحق حسب العميل.","Entries, invoices and balances per client.","ثقيل · يُحسب عند الطلب","Heavy · on demand","المحاسبة فقط","Accounting only"],
 ["housing","home","حركة الإيواء والترحيل","Housing & departures","الداخل والخارج والجاهز للنقل خلال الفترة.","In, out and ready-to-move during the period.","خفيف · لقطة محفوظة","Light · cached","العمليات","Operations"],
 ["trips","plane","الرحلات (استقدام وترحيل)","Trips (arrivals & departures)","78 رحلة استقدام و 21 ترحيل مع حالة كل رحلة.","78 arrival and 21 departure trips with each trip’s status.","متوسط · لقطة يومية","Medium · daily snapshot","العمليات","Operations"],
 ["types","layers","العقود حسب النوع","Contracts by type","استقدام، تأجير، إيواء، ونقل كفالة في جدول واحد.","Recruitment, rental, housing and transfer side by side.","خفيف · لقطة محفوظة","Light · cached","الإدارة","Management"],
 ["ops","activity","مؤشرات التشغيل","Operational indicators","مراحل ما قبل الوصول وما بعده بنسبها.","Pre-arrival and post-arrival stages with shares.","متوسط · لقطة يومية","Medium · daily snapshot","العمليات","Operations"]];
const nats=[["الفلبين","Philippines",170],["أوغندا","Uganda",37],["إثيوبيا","Ethiopia",10],["بنغلاديش","Bangladesh",7],["الهند","India",6]];
const offices=[["PLATINUM BROTHERS INT’L MANPOWER AGENCY INC","الفلبين","Philippines",158,125,26,5,2,42],["Supreme Link Ltd","أوغندا","Uganda",38,28,7,1,2,1],["Al Noor Manpower","إثيوبيا","Ethiopia",22,14,6,2,0,31],["Gulf Bridge Agency","بنغلاديش","Bangladesh",12,9,2,1,0,27]];
const fin=[["#292","سند صرف استرجاع طلب نقل كفالة","Sponsorship transfer refund","نواف القحطاني","Nawaf Alqahtani","4,200","a","بانتظار الاعتماد","Pending approval"],["#291","سند قبض طلب نقل كفالة","Transfer receipt","سهام الشاذلي","Siham Alshathli","9,800","a","بانتظار الاعتماد","Pending approval"],["#289","إيراد طلب استقدام رقم 597","Recruitment order revenue","نايف القحطاني","Nayef Alqahtani","12,400","g","مُرحّل","Posted"],["#287","فاتورة إيجار شهري","Monthly rental invoice","شركة نجد","Najd Co.","6,150","r","متأخرة","Overdue"]];
const donut=(data,total)=>{const c=["#16233f","#7ab648","#4a6fa5","#c9d3e2","#a8bf8c"];let o=0;const r=54,C=2*Math.PI*r;return `<div class="donut-wrap"><svg viewBox="0 0 160 160" width="170" height="170">${data.map((d,i)=>{const f=d[2]/total,seg=`<circle cx="80" cy="80" r="${r}" fill="none" stroke="${c[i%5]}" stroke-width="24" stroke-dasharray="${f*C} ${C}" stroke-dashoffset="${-o*C}" transform="rotate(-90 80 80)"/>`;o+=f;return seg}).join("")}</svg><div class="donut-key">${data.map((d,i)=>`<div><i style="background:${c[i%5]}"></i><span>${t(d[0],d[1])}</span><b class="num">${d[2]}</b></div>`).join("")}</div></div>`};
const personas={
 owner:{ar:"المالك / رئيس مجلس الإدارة",en:"Owner / board chair",i:"crown",
  h:{ar:"صورة الشركة هذا الشهر",en:"Company position this month"},
  s:{ar:"أرقام عامة تختصر أداء الشركة دون تفاصيل تشغيلية.",en:"Company-level numbers without operational detail."},
  k:[["wallet","إيرادات الشهر","Month revenue","412,800","+18% عن الشهر الماضي","+18% vs last month","g"],["file-text","عقود موقّعة","Signed contracts",70,"115 إجمالي 2026","115 total in 2026","n"],["users","عملاء نشطون","Active clients",241,"39 لديهم طلبات جارية","39 with live orders","n"],["alert-triangle","مستحقات لم تُحصّل","Uncollected","184,600","7 فواتير متأخرة","7 overdue invoices","r"]],
  a:[["r","trending-down","نسبة الإلغاء 23%","Cancellation rate 23%","11 عقداً ملغى من 115","11 cancelled out of 115","تحليل","Analyse"],["a","clock","متوسط الإنجاز 42 يوماً","Average completion 42 days","أعلى من المستهدف بـ 9 أيام","9 days above target","تفاصيل","Details"],["n","building-2","3 مكاتب تغطي 82% من المخزون","3 offices cover 82% of stock","تركز يستحق المراجعة","Concentration worth reviewing","مراجعة","Review"]],
  r:["volume","finance","types"]},
 sales:{ar:"المبيعات",en:"Sales",i:"shopping-cart",
  h:{ar:"طلباتك وحجوزاتك",en:"Your orders and bookings"},
  s:{ar:"ما يخص محفظتك فقط — طلبات، حجوزات، ومتابعة عملاء.",en:"Only your portfolio — orders, bookings and client follow-up."},
  k:[["shopping-cart","طلباتي الجارية","My live orders",13,"7 تحت الإجراء","7 in progress","a"],["check-circle-2","مكتملة هذا الشهر","Completed this month",108,"من أصل 121 طلباً","of 121 orders","g"],["user-plus","عملاء جدد","New clients",6,"خلال 30 يوماً","in the last 30 days","n"],["phone","متابعات مطلوبة","Follow-ups due",9,"202 عميل بدون طلبات","202 clients with no orders","a"]],
  a:[["a","clock","7 طلبات تحت الإجراء","7 orders in progress","تجاوزت 5 أيام بلا تحديث","Over 5 days without an update","متابعة","Follow up"],["n","file-signature","6 طلبات جديدة","6 new orders","بانتظار ربطها بعقد","Waiting to be linked to a contract","فتح","Open"],["r","message-square","2 شكوى من عملائك","2 complaints from your clients","بدون رد منذ 3 أيام","Unanswered for 3 days","الرد","Reply"]],
  r:["register","types","people"]},
 foreign:{ar:"العلاقات الخارجية",en:"External relations",i:"globe",
  h:{ar:"المكاتب والسير والرحلات",en:"Offices, CVs and trips"},
  s:{ar:"متابعة المكاتب الخارجية والسير الواردة ومواعيد الوصول.",en:"External offices, incoming CVs and arrival dates."},
  k:[["building-2","مكاتب متعاقدة","Contracted offices",22,"3 مكاتب تغطي 82%","3 offices cover 82%","n"],["files","سير متاحة","Available CVs",163,"142 محجوزة · 235 إجمالي","142 reserved · 235 total","g"],["plane","رحلات استقدام جارية","Arrivals in progress",26,"52 مكتملة · 2 قادمة","52 completed · 2 upcoming","n"],["alert-triangle","متأخرة بالوصول","Late on arrival",18,"تجاوزت 60 يوماً","Past 60 days","r"]],
  a:[["r","alert-triangle","18 عقداً بلا تذاكر","18 contracts without tickets","موزعة على 4 مكاتب","Across 4 offices","مراسلة","Contact"],["a","package","5 سير مرتجعة من PLATINUM","5 CVs returned from PLATINUM","متوسط الوصول 42 يوماً","Average arrival 42 days","مراجعة","Review"],["n","upload","14 سيرة جديدة بانتظار المراجعة","14 new CVs awaiting review","من وكيل الفلبين","From the Philippines agent","فتح","Open"]],
  r:["offices","trips","people"]},
 ops:{ar:"مشرف العمليات",en:"Operations supervisor",i:"activity",
  h:{ar:"تشغيل اليوم",en:"Today’s operations"},
  s:{ar:"مراحل العقود والإيواء وما يحتاج إجراءً الآن.",en:"Contract stages, housing and what needs action now."},
  k:[["file-text","عقود تحت التشغيل","Contracts in progress",38,"18 متأخرة بالوصول","18 late on arrival","r"],["home","داخل الإيواء","In housing",64,"13 جاهز للنقل","13 ready to move","n"],["plane","مرحلة الترحيل","Departure stage",9,"6 رحلات جارية","6 trips in progress","a"],["alert-triangle","بنود تحتاج إجراءً","Items needing action",4,"منذ 3 أيام فأكثر","3+ days waiting","r"]],
  a:[["r","alert-triangle","18 عقداً متأخر بالوصول","18 contracts late on arrival","تجاوزت 60 يوماً بدون تذاكر","Over 60 days without tickets","مراجعة","Review"],["a","message-square","4 شكاوى مفتوحة","4 open complaints","اثنتان بدون رد منذ 3 أيام","Two unanswered for 3 days","الرد","Reply"],["a","plane","9 عمالة في مرحلة الترحيل","9 workers in departure stage","تحتاج تأكيد حجز","Booking confirmation needed","تأكيد","Confirm"],["n","key","2 تفويض إنجاز تحت الإجراء","2 delegations in progress","بانتظار اعتماد المشرف","Awaiting supervisor approval","اعتماد","Approve"]],
  r:["ops","housing","register"]}};
const reportsView=()=>`
 <div class="rep-grid">${reports.map(r=>`<div class="card rep"><div class="rep-h"><span class="si">${I(r[1])}</span><div><b>${t(r[2],r[3])}</b><small>${t(r[4],r[5])}</small></div></div><div class="rep-meta"><span>${I("clock")}${t(r[6],r[7])}</span><span>${I("shield")}${t(r[8],r[9])}</span></div><div class="rep-f"><button class="btn btn-ghost" data-report="${r[0]}">${I("eye")}${t("عرض","View")}</button><div class="exp"><button title="Excel">${I("file-spreadsheet")}</button><button title="PDF">${I("file-text")}</button><button title="CSV">${I("file-down")}</button></div></div></div>`).join("")}</div>
 <div class="card sched"><div class="card-h"><div><h3>${t("التقارير المجدولة","Scheduled reports")}</h3><p>${t("تُرسل تلقائياً إلى بريد الإدارة دون فتح النظام","Emailed to management automatically")}</p></div><button class="btn btn-ghost">${I("plus")}${t("جدولة جديدة","New schedule")}</button></div>
  ${[["ملخص تنفيذي أسبوعي","Weekly executive summary","كل أحد 7:00ص · PDF","Every Sunday 7:00am · PDF","الإدارة التنفيذية","Executive team"],["حركة العقود الشهرية","Monthly contract volume","أول كل شهر · Excel","1st of each month · Excel","مدير العمليات","Operations manager"],["الذمم والفواتير المستحقة","Receivables & due invoices","كل خميس · Excel","Every Thursday · Excel","المحاسبة","Accounting"]].map(s=>`<div class="attn-row"><span class="ni n">${I("mail")}</span><div><b>${t(s[0],s[1])}</b><small>${t(s[2],s[3])} · ${t(s[4],s[5])}</small></div><button class="btn btn-ghost">${t("تعديل","Edit")}</button></div>`).join("")}
 </div>`;
const gcards=[["file-text","جميع عقود الاستقدام","All recruitment contracts",115,"قبل الوصول 27 · مرحلة الوصول 0 · تحت الضمان 2","Pre-arrival 27 · arrival 0 · warranty 2"],["shopping-cart","طلبات الاستقدام (الحجوزات)","Recruitment orders (bookings)",121,"جديدة 6 · تحت الإجراء 7 · مكتملة 108 · ملغاة 0","New 6 · in progress 7 · completed 108 · cancelled 0"],["home","عقود الإيجار","Rental contracts",13,"نشطة 2 · موقعة 2 · مرسلة 1 · مكتملة 7 · تم التسليم 0","Active 2 · signed 2 · sent 1 · completed 7 · delivered 0"],["users","سير عاملات الإيجار","Rental worker CVs",13,"متاحة 0 · في سير إيجار نشط 6","Available 0 · in active rental 6"],["files","السير الذاتية","CVs",235,"متاحة 163 · محجوزة 142","Available 163 · reserved 142"],["users","العملاء","Clients",241,"لديهم طلبات 39 · بدون طلبات 202","With orders 39 · without 202"],["arrow-left-right","طلبات نقل الكفالة","Sponsorship transfers",17,"فترة التجربة 2 · تم النقل 0","Trial 2 · transferred 0"],["headphones","الشكاوى","Complaints",4,"مفتوحة 2 · مغلقة 2","Open 2 · closed 2"],["file-signature","تفاويض إنجاز","Delegations",2,"تحت الإجراء 2 · تم التنفيذ 0","In progress 2 · executed 0"],["home","داخل الإيواء","In housing",64,"خارج 5 · جاهز للنقل 13 · مرحلة الترحيل 9 · تم الترحيل 5 · أماكن 2","Out 5 · ready 13 · departure 9 · departed 5 · places 2"],["plane-landing","رحلات الاستقدام","Arrival trips",78,"جارية 26 · مكتملة 52 · القادمة 2","In progress 26 · completed 52 · upcoming 2"],["plane-takeoff","رحلات الترحيل","Departure trips",21,"جارية 6 · مكتملة 15 · ملغاة 0","In progress 6 · completed 15 · cancelled 0"]];
const acts=[["file-text","إدارة العقود","Contracts",[["عقد #555 – أبو عبدالله","Contract #555 – Abu Abdullah","عقد مكتمل","Completed","g","منذ 4 أيام","4 days ago"],["عقد #583 – سهير محمد","Contract #583 – Suhair Mohamed","عقد إيواء","Housing contract","n","منذ أسبوع","A week ago"],["عقد #597 – نايف القحطاني","Contract #597 – Nayef Alqahtani","عقد إيواء","Housing contract","n","منذ أسبوع","A week ago"],["عقد #596 – سارة أحمد محمد","Contract #596 – Sara Ahmed","عقود جديدة","New contract","a","منذ أسبوع","A week ago"]]],["plane","إدارة الرحلات","Trips",[["رحلة ترحيل #131 – سهام الشاذلي","Departure #131 – Siham","مجدول","Scheduled","a","منذ أسبوع","A week ago"],["رحلة استقدام #130 – Rental","Arrival #130 – Rental","مكتمل","Completed","g","منذ أسبوع","A week ago"],["رحلة استقدام #120 – سهام الشاذلي","Arrival #120 – Siham","مكتمل","Completed","g","منذ أسبوع","A week ago"],["رحلة استقدام #129 – sara","Arrival #129 – sara","مكتمل","Completed","g","منذ أسبوعين","2 weeks ago"]]],["wallet","الإدارة المالية","Finance",[["قيد #292 – سند صرف استرجاع نقل كفالة","Entry #292 – transfer refund","بانتظار الاعتماد","Pending","a","منذ أسبوع","A week ago"],["قيد #293 – فشل التجربة، رجوع العاملة للإيواء #29","Entry #293 – trial failed, worker returned","بانتظار الاعتماد","Pending","a","منذ أسبوع","A week ago"],["قيد #291 – سند قبض طلب نقل كفالة","Entry #291 – transfer receipt","بانتظار الاعتماد","Pending","a","منذ أسبوع","A week ago"],["قيد #289 – إيراد طلب استقدام 597، فاتورة 65","Entry #289 – order revenue 597","بانتظار الاعتماد","Pending","a","منذ أسبوع","A week ago"]]],["bell","آخر الإشعارات","Notifications",[["شكوى جديدة من بدر صالح الهويريني","New complaint from Badr Alhwairini","غير مقروء","Unread","r","منذ شهر","A month ago"],["شكوى جديدة من مصطفى يحي محمد","New complaint from Mustafa Yahya","مقروء","Read","n","منذ 3 أشهر","3 months ago"]]]];
const staff=[["Mohameed","Mohameed",12,"تفاويض إنجاز: 0 · تم التنفيذ: 0 · تحت الإجراء: 0 · جارية: 1 · مكتملة: 0 · مرتجع: 4","Delegations 0 · executed 0 · in progress 0 · running 1 · done 0 · returned 4",12],["علي ناصر","Ali Nasser",7,"تفاويض إنجاز: 2 · تحت الإجراء: 2 · جارية: 4 · مكتملة: 0 · مرتجع: 1","Delegations 2 · in progress 2 · running 4 · done 0 · returned 1",9]];
const reminders=[["17 أغسطس","17 Aug","اليوم — لا مواعيد","Today — no appointments","n"],["15 أغسطس 8:20م","15 Aug 8:20pm","ترحيل – سهام الشاذلي","Departure – Siham Alshathli","r"],["8 أغسطس 8:06م","8 Aug 8:06pm","وصول – Rental","Arrival – Rental","g"],["1 أغسطس 5:57م","1 Aug 5:57pm","وصول – عاملة تجريبية","Arrival – trial worker","g"],["30 يوليو 5:52ص","30 Jul 5:52am","وصول – KIMBERLY","Arrival – KIMBERLY","g"]];
const analyticsView=()=>`
 <div class="an-note">${I("info")}<span>${t("جميع مؤشرات النظام في مكان واحد — 9 مواعيد قادمة · آخر تحديث 02:24","Every system indicator in one place — 9 upcoming appointments · updated 02:24")}</span></div>
 <div class="gcards">${gcards.map(g=>`<div class="card gc"><div class="kl">${I(g[0])}<span>${t(g[1],g[2])}</span></div><div class="kv num">${g[3]}</div><small>${t(g[4],g[5])}</small></div>`).join("")}</div>
 <div class="grid-2">
  <div class="card"><div class="card-h"><div><h3>${t("حركة العقود خلال سنتين","Contract volume, two years")}</h3><p>${t("مقارنة شهرية 2025 و 2026","Monthly comparison 2025 vs 2026")}</p></div><div class="legend"><span><i style="background:#c9d3e2"></i>2025</span><span><i style="background:#16233f"></i>2026</span></div></div>${chart()}</div>
  <div class="card"><div class="card-h"><div><h3>${t("العقود حسب المهنة","Contracts by occupation")}</h3><p>${t("المعروف 107 · غير محدد 8 · المهن المرصودة 3","Known 107 · unspecified 8 · tracked occupations 3")}</p></div></div>${jobs.map(j=>`<div class="hbar"><span class="t">${t(j[0],j[1])}</span><span class="track"><i style="width:${j[2]/107*100}%"></i></span><span class="n num">${j[2]}</span></div>`).join("")}</div>
 </div>
 <div class="grid-2">
  <div class="card" data-perm="DASHBOARD_CONTRACTS_OVERVIEW"><div class="card-h"><div><h3>${t("مراحل العقود","Contract stages")}</h3><p>${t("38 عقداً قيد التتبع","38 contracts tracked")}</p></div><span class="tag n num">38</span></div><div class="stage">${stageList()}</div></div>
  <div class="card" data-perm="DASHBOARD_OPERATIONS_OVERVIEW"><div class="card-h"><div><h3>${t("مؤشرات التشغيل السريعة","Quick operational indicators")}</h3><p>${t("ما قبل الوصول وما بعده","Pre- and post-arrival")}</p></div><a href="#" class="btn btn-ghost" data-report="ops">${t("التقرير","Report")}</a></div>
   ${[["عقود جديدة","New contracts",10,21],["عقود مساند","Musaned contracts",7,15],["التفويض","Delegation",3,6],["التفيز","Visa issuing",3,6],["التذكرة","Ticket",4,8],["تحت الضمان","Under warranty",2,4],["عقود مكتملة","Completed",8,17],["العقود الملغاة","Cancelled",11,23]].map(x=>`<div class="hbar"><span class="t">${t(x[0],x[1])}</span><span class="track"><i style="width:${x[3]}%"></i></span><span class="n num">${x[2]}</span></div>`).join("")}</div>
 </div>
 <div class="grid-2">
  <div class="card" data-perm="DASHBOARD_NATIONALITIES_STATISTICS"><div class="card-h"><div><h3>${t("أبرز الجنسيات المتاحة","Available nationalities")}</h3><p>${t("إجمالي 230 عاملة","230 workers in total")}</p></div></div>${donut(nats,230)}</div>
  <div class="card" data-perm="DASHBOARD_ORDERS_TYPES"><div class="card-h"><div><h3>${t("الطلبات حسب الجنس","Orders by gender")}</h3><p>${t("إجمالي 123 طلباً","123 orders")}</p></div></div>${donut([["أنثى","Female",118],["غير محدد","Unspecified",5],["ذكر","Male",0]],123)}</div>
 </div>
 <div class="card flush" data-perm="DASHBOARD_OFFICES_STATISTICS"><div class="card-h"><div><h3>${t("المخزون لكل مكتب","Stock per office")}</h3><p>${t("22 مكتباً خارجياً — أعلى 4","22 external offices — top 4")}</p></div><a href="#" class="btn btn-ghost" data-report="offices">${t("التقرير الكامل","Full report")}</a></div>
  <div class="tw"><table><thead><tr><th>${t("المكتب","Office")}</th><th>${t("الدولة","Country")}</th><th>${t("الإجمالي","Total")}</th><th>${t("متاحة","Available")}</th><th>${t("أخرى","Other")}</th><th>${t("مرتجع","Returned")}</th><th>${t("باك أوت","Backed out")}</th><th>${t("متوسط الوصول","Avg arrival")}</th></tr></thead><tbody>${offices.map(o=>`<tr><td><b>${o[0]}</b></td><td>${t(o[1],o[2])}</td><td class="num">${o[3]}</td><td class="num">${o[4]}</td><td class="num">${o[5]}</td><td class="num">${o[6]}</td><td class="num">${o[7]}</td><td class="num">${o[8]} ${t("يوم","d")}</td></tr>`).join("")}</tbody></table></div></div>
 <div class="an-sub">${t("آخر الأنشطة — مقسمة حسب الإدارة ومرتبة من الأحدث","Latest activity — grouped by department, newest first")}</div>
 <div class="grid-4" data-perm="DASHBOARD_RECENT_ACTIVITIES">${acts.map(a=>`<div class="card flush act"><div class="act-h"><span class="ni n">${I(a[0])}</span><div><b>${t(a[1],a[2])}</b><small>${a[3].length} ${t("أنشطة حديثة","recent")}</small></div></div>${a[3].map(x=>`<div class="act-row"><div><b>${t(x[0],x[1])}</b><small>${t(x[5],x[6])}</small></div><span class="tag ${x[4]}">${t(x[2],x[3])}</span></div>`).join("")}</div>`).join("")}</div>
 <div class="grid-2">
  <div class="card flush" data-perm="DASHBOARD_CALENDAR_EVENTS"><div class="card-h"><div><h3>${t("مواعيد وتذكيرات هامة","Key appointments & reminders")}</h3><p>${t("9 مواعيد — أغسطس 2026","9 appointments — August 2026")}</p></div></div>
   ${reminders.map(r=>`<div class="attn-row"><span class="ni ${r[4]}">${I("calendar")}</span><div><b>${t(r[2],r[3])}</b><small>${t(r[0],r[1])}</small></div></div>`).join("")}</div>
  <div class="card flush" data-perm="DASHBOARD_EMPLOYEE_PERFORMANCE"><div class="card-h"><div><h3>${t("تفاويض إنجاز وعقود الموظفين","Staff delegations & contracts")}</h3><p>${t("طاقم العمل — العقود 19 · تفاويض إنجاز 2 · لشطون 2","Team — 19 contracts · 2 delegations")}</p></div></div>
   ${staff.map(x=>`<div class="attn-row"><span class="ni n">${I("user-round")}</span><div><b>${t(x[0],x[1])} — ${t("العقود","contracts")}: ${x[2]}</b><small>${t(x[3],x[4])}</small></div><span class="tag n num">${x[5]}</span></div>`).join("")}</div>
 </div>`;
const screens={
 dashboard:()=>{
  const p=personas[PERSONA];
  return `
 ${bannerOn?banner():""}
 <div class="page-head"><div><h1>${t(p.h.ar,p.h.en)}</h1><p>${t(p.s.ar,p.s.en)}</p></div>
 <div class="actions"><span class="fresh">${I("refresh-cw")}<span>${t("محدّث 02:24 · لقطة محفوظة","Updated 02:24 · cached snapshot")}</span></span><div class="seg" data-seg>${[["اليوم","Today"],["الأسبوع","Week"],["الشهر","Month"]].map((x,i)=>`<button class="${i===2?"on":""}">${t(x[0],x[1])}</button>`).join("")}</div><a href="#" data-go="reports" class="btn btn-ghost">${I("bar-chart-3")}${t("مركز التقارير","Reports centre")}</a></div></div>
 <div class="persona-bar">${I("user-round")}<span>${t("العرض حسب الدور:","View as:")}</span><div class="seg">${Object.keys(personas).map(k=>`<button class="${k===PERSONA?"on":""}" data-persona="${k}">${t(personas[k].ar,personas[k].en)}</button>`).join("")}</div></div>
<div class="atd" data-perm="DASHBOARD_VIEW">
  <div class="atd-now"><span class="atd-time num" data-clock>--:--:--</span><span class="atd-date" data-cdate></span></div>
  <div class="atd-facts">
   <div class="atd-f"><span class="atd-i g">${I("log-in")}</span><div><small>${t("تسجيل الحضور","Check-in")}</small><b class="num">07:58 ص</b></div></div>
   <div class="atd-f"><span class="atd-i">${I("timer")}</span><div><small>${t("مدة الجلسة","Session")}</small><b class="num" data-session>02:06:32</b></div></div>
   <div class="atd-f"><span class="atd-i">${I("map-pin")}</span><div><small>${t("الموقع","Location")}</small><b>${t("الفرع الرئيسي · الرياض","Main branch · Riyadh")}</b></div></div>
  </div>
  <div class="atd-act"><span class="atd-badge">${I("shield-check")}${t("حاضر · موثّق بالموقع","Present · location verified")}</span><button class="btn btn-out">${I("log-out")}${t("تسجيل الانصراف","Check out")}</button></div>
 </div>
 <div class="grid-kpi">${p.k.map(k=>`<div class="card kpi"><div class="kl"><span class="ni n">${I(k[0])}</span><span>${t(k[1],k[2])}</span></div><div class="kv num">${k[3]}</div><div class="kb"><span class="tag ${k[6]}">${t(k[4],k[5])}</span></div></div>`).join("")}</div>
 <div class="dash2">
  <div class="card flush attn" data-perm="DASHBOARD_OPERATIONS_OVERVIEW"><div class="card-h"><div><h3>${t("يحتاج قرارك","Needs your decision")}</h3><p>${t("بنود متوقفة على إجراء منك","Items blocked on your action")}</p></div><span class="tag r num">${p.a.length}</span></div>
  ${p.a.map(x=>`<div class="attn-row"><span class="ni ${x[0]}">${I(x[1])}</span><div><b>${t(x[2],x[3])}</b><small>${t(x[4],x[5])}</small></div><button class="btn btn-ghost">${t(x[6],x[7])}</button></div>`).join("")}
 </div>
 <div class="card flush"><div class="card-h"><div><h3>${t("تقارير تخص دورك","Reports for your role")}</h3><p>${t("التفاصيل الكاملة داخل مركز التقارير","Full detail lives in the reports centre")}</p></div><a href="#" data-go="reports" class="btn btn-ghost">${t("الكل","All")}</a></div>
  ${p.r.map(id=>{const r=reports.find(x=>x[0]===id);return `<div class="attn-row"><span class="ni n">${I(r[1])}</span><div><b>${t(r[2],r[3])}</b><small>${t(r[4],r[5])}</small></div><button class="btn btn-ghost" data-report="${id}">${t("عرض","View")}</button></div>`}).join("")}
  </div>
 </div>
 <div class="card lazy" data-lazy="trend" data-perm="DASHBOARD_ORDERS_STATISTICS"><div class="card-h"><div><h3>${t("اتجاه العقود","Contract trend")}</h3><p>${t("مقارنة شهرية 2025 و 2026 — يُحمّل عند الوصول إليه","2025 vs 2026 — loaded when scrolled into view")}</p></div><div class="legend"><span><i style="background:#c9d3e2"></i>2025</span><span><i style="background:#16233f"></i>2026</span></div></div><div data-lazy-slot>${skel(280)}</div></div>
`},
 reports:()=>`
 <div class="page-head"><div><h1>${t("مركز التقارير","Reports centre")}</h1><p>${t("اختر التقرير والفترة، ثم اعرضه أو صدّره. لا يُحسب شيء قبل الطلب.","Pick a report and period, then view or export. Nothing is computed until you ask.")}</p></div>
 <div class="actions"><div class="seg" data-seg><button class="${REPTAB==="analytics"?"on":""}" data-reptab="analytics">${t("التحليلات","Analytics")}</button><button class="${REPTAB==="reports"?"on":""}" data-reptab="reports">${t("التقارير","Reports")}</button></div><button class="btn btn-ghost">${I("calendar-clock")}${t("جدولة تقرير دوري","Schedule report")}</button></div></div>
 <div class="filters"><div class="inp">${I("search")}<input placeholder="${t("ابحث في التقارير…","Search reports…")}"></div>
 <select><option>${t("الفترة: هذا الشهر","Period: this month")}</option><option>${t("الربع الحالي","This quarter")}</option><option>${t("سنة 2026","Year 2026")}</option><option>${t("مدة مخصصة","Custom range")}</option></select>
 <select><option>${t("كل الفروع","All branches")}</option><option>${t("الفرع الرئيسي","Main branch")}</option></select>
 <span class="empty" style="margin-inline-start:auto">${t("التصدير يتبع الفلاتر المطبّقة","Export follows the applied filters")}</span></div>
 ${REPTAB==="analytics"?analyticsView():reportsView()}`,
 report:()=>{
  const r=reports.find(x=>x[0]===curReport)||reports[0];
  const head=`<div class="page-head"><div><a class="back" href="#" data-go="reports" style="margin:0 0 8px">${I("arrow-right")}<span>${t("مركز التقارير","Reports centre")}</span></a><h1>${t(r[2],r[3])}</h1><p>${t(r[4],r[5])} · ${t("الفترة: هذا الشهر · كل الفروع","Period: this month · all branches")}</p></div>
  <div class="actions"><span class="fresh">${I("clock")}<span>${t("حُسِب الآن · 1.2 ثانية","Computed now · 1.2s")}</span></span><button class="btn btn-ghost">${I("file-spreadsheet")}Excel</button><button class="btn btn-ghost">${I("file-text")}PDF</button><button class="btn btn-primary">${I("calendar-clock")}${t("جدولة","Schedule")}</button></div></div>`;
  const body={
   register:()=>`<div class="grid-kpi">${[["إجمالي العقود","Total contracts",115],["عقود 2026","2026 contracts",70],["مكتملة","Completed",8],["ملغاة / مرتجعة","Cancelled / returned",11]].map(k=>`<div class="card kpi"><div class="kl"><span>${t(k[0],k[1])}</span></div><div class="kv num">${k[2]}</div></div>`).join("")}</div>
    <div class="grid-2"><div class="card"><div class="card-h"><div><h3>${t("مراحل العقود","Contract stages")}</h3><p>${t("تدفق العقود حسب المرحلة","Pipeline by stage")}</p></div><span class="tag n num">38</span></div><div class="stage">${stageList()}</div></div>
    <div class="card"><div class="card-h"><div><h3>${t("العقود حسب المهنة","By occupation")}</h3></div></div>${jobs.map(j=>`<div class="hbar"><span class="t">${t(j[0],j[1])}</span><span class="track"><i style="width:${j[2]/107*100}%"></i></span><span class="n num">${j[2]}</span></div>`).join("")}</div></div>
    <div class="card flush"><div class="card-h"><div><h3>${t("سجل العقود","Contract rows")}</h3><p>${t("الصفوف المطابقة للفلاتر","Rows matching the filters")}</p></div></div>${table(8,true)}</div>`,
   volume:()=>`<div class="grid-kpi">${[["أعلى شهر","Peak month","نوفمبر","November"],["متوسط شهري 2026","2026 monthly avg","9.6","9.6"],["متوسط مدة الإنجاز","Avg completion","42 يوماً","42 days"],["نمو عن 2025","Growth vs 2025","+18%","+18%"]].map(k=>`<div class="card kpi"><div class="kl"><span>${t(k[0],k[1])}</span></div><div class="kv" style="font-size:26px">${t(k[2],k[3])}</div></div>`).join("")}</div>
    <div class="card"><div class="card-h"><div><h3>${t("حركة العقود الشهرية","Monthly volume")}</h3><p>${t("مقارنة 2025 و 2026","2025 vs 2026")}</p></div><div class="legend"><span><i style="background:#c9d3e2"></i>2025</span><span><i style="background:#16233f"></i>2026</span></div></div>${chart()}</div>`,
   people:()=>`<div class="grid-2"><div class="card"><div class="card-h"><div><h3>${t("أبرز الجنسيات المتاحة","Available nationalities")}</h3><p>${t("إجمالي 230 عاملة","230 workers in total")}</p></div></div>${donut(nats,230)}</div>
    <div class="card"><div class="card-h"><div><h3>${t("الطلبات حسب المهنة","Orders by occupation")}</h3><p>${t("إجمالي 123 طلباً","123 orders")}</p></div></div>${jobs.map(j=>`<div class="hbar"><span class="t">${t(j[0],j[1])}</span><span class="track"><i style="width:${j[2]/107*100}%"></i></span><span class="n num">${j[2]}</span></div>`).join("")}</div></div>
    <div class="card"><div class="card-h"><div><h3>${t("حالة السير الذاتية","CV status")}</h3></div></div><div class="grid-3">${[["متاحة","Available",163,"g"],["محجوزة","Reserved",142,"n"],["مرتجعة","Returned",12,"r"]].map(x=>`<div class="mini"><span class="tag ${x[3]}">${t(x[0],x[1])}</span><b class="num">${x[2]}</b></div>`).join("")}</div></div>`,
   offices:()=>`<div class="card flush"><div class="card-h"><div><h3>${t("مخزون كل مكتب","Stock per office")}</h3><p>${t("22 مكتباً — أعلى 4 معروضة","22 offices — top 4 shown")}</p></div></div>
    <div class="tw"><table><thead><tr><th>${t("المكتب","Office")}</th><th>${t("الدولة","Country")}</th><th>${t("الإجمالي","Total")}</th><th>${t("متاحة","Available")}</th><th>${t("أخرى","Other")}</th><th>${t("مرتجع","Returned")}</th><th>${t("باك أوت","Backed out")}</th><th>${t("متوسط الوصول","Avg arrival")}</th></tr></thead><tbody>${offices.map(o=>`<tr><td><b>${o[0]}</b></td><td>${t(o[1],o[2])}</td><td class="num">${o[3]}</td><td class="num">${o[4]}</td><td class="num">${o[5]}</td><td class="num">${o[6]}</td><td class="num">${o[7]}</td><td class="num">${o[8]} ${t("يوم","d")}</td></tr>`).join("")}</tbody></table></div></div>`,
   finance:()=>`<div class="grid-kpi">${[["إيرادات الشهر","Month revenue","412,800"],["محصّل","Collected","228,200"],["مستحق لم يُحصّل","Outstanding","184,600"],["قيود بانتظار الاعتماد","Entries pending","3"]].map(k=>`<div class="card kpi"><div class="kl"><span>${t(k[0],k[1])}</span></div><div class="kv num" style="font-size:28px">${k[2]}</div></div>`).join("")}</div>
    <div class="card flush"><div class="card-h"><div><h3>${t("القيود والفواتير","Entries & invoices")}</h3></div></div><div class="tw"><table><thead><tr><th>${t("القيد","Entry")}</th><th>${t("البيان","Description")}</th><th>${t("العميل","Client")}</th><th>${t("المبلغ (ر.س)","Amount (SAR)")}</th><th>${t("الحالة","Status")}</th></tr></thead><tbody>${fin.map(f=>`<tr><td><b class="num">${f[0]}</b></td><td>${t(f[1],f[2])}</td><td>${t(f[3],f[4])}</td><td class="num">${f[5]}</td><td><span class="st tag ${f[6]}"><i></i>${t(f[7],f[8])}</span></td></tr>`).join("")}</tbody></table></div></div>`,
   housing:()=>`<div class="grid-kpi">${[["داخل الإيواء","In housing",64],["خارج الإيواء","Out of housing",5],["جاهز للنقل","Ready to move",13],["مرحلة الترحيل","Departure stage",9]].map(k=>`<div class="card kpi"><div class="kl"><span>${t(k[0],k[1])}</span></div><div class="kv num">${k[2]}</div></div>`).join("")}</div>
    <div class="grid-2"><div class="card"><div class="card-h"><div><h3>${t("حركة الخروج للإيجار والنقل","Exit for rental & transport")}</h3></div></div>${[["تم الترحيل","Departed",5,64],["جاهز للنقل","Ready to move",13,64],["أماكن شاغرة","Vacant places",2,64],["خارج الإيواء","Out of housing",5,64]].map(x=>`<div class="hbar"><span class="t">${t(x[0],x[1])}</span><span class="track"><i style="width:${x[2]/x[3]*100}%"></i></span><span class="n num">${x[2]}</span></div>`).join("")}</div>
    <div class="card"><div class="card-h"><div><h3>${t("عقود الإيجار","Rental contracts")}</h3><p>${t("13 عقداً · 13 سيرة عاملات إيجار","13 contracts · 13 rental CVs")}</p></div></div><div class="grid-3">${[["نشطة","Active",2,"g"],["موقعة","Signed",2,"n"],["مرسلة","Sent",1,"n"],["مكتملة","Completed",7,"n"],["تم التسليم","Delivered",0,""],["سير إيجار نشط","Active rental CVs",6,"g"]].map(x=>`<div class="mini"><span class="tag ${x[3]}">${t(x[0],x[1])}</span><b class="num">${x[2]}</b></div>`).join("")}</div></div></div>`,
   trips:()=>`<div class="grid-kpi">${[["رحلات الاستقدام","Arrival trips",78],["رحلات الترحيل","Departure trips",21],["قادمة هذا الأسبوع","Upcoming this week",2],["ملغاة","Cancelled",0]].map(k=>`<div class="card kpi"><div class="kl"><span>${t(k[0],k[1])}</span></div><div class="kv num">${k[2]}</div></div>`).join("")}</div>
    <div class="grid-2"><div class="card"><div class="card-h"><div><h3>${t("رحلات الاستقدام","Arrival trips")}</h3><p>${t("إجمالي 78 رحلة","78 trips in total")}</p></div></div>${[["مكتملة","Completed",52,78],["جارية","In progress",26,78],["القادمة","Upcoming",2,78]].map(x=>`<div class="hbar"><span class="t">${t(x[0],x[1])}</span><span class="track"><i style="width:${x[2]/x[3]*100}%"></i></span><span class="n num">${x[2]}</span></div>`).join("")}</div>
    <div class="card"><div class="card-h"><div><h3>${t("رحلات الترحيل","Departure trips")}</h3><p>${t("إجمالي 21 رحلة","21 trips in total")}</p></div></div>${[["مكتملة","Completed",15,21],["جارية","In progress",6,21],["ملغاة","Cancelled",0,21]].map(x=>`<div class="hbar"><span class="t">${t(x[0],x[1])}</span><span class="track"><i style="width:${x[2]/x[3]*100}%"></i></span><span class="n num">${x[2]}</span></div>`).join("")}</div></div>
    <div class="card flush"><div class="card-h"><div><h3>${t("أحدث الرحلات","Latest trips")}</h3></div></div><div class="tw"><table><thead><tr><th>${t("الرحلة","Trip")}</th><th>${t("النوع","Type")}</th><th>${t("العاملة / العميل","Worker / client")}</th><th>${t("الحالة","Status")}</th></tr></thead><tbody>${[["#131","ترحيل","Departure","سهام الشاذلي","Siham Alshathli","مجدول","Scheduled","a"],["#130","استقدام (إيجار)","Arrival (rental)","Rental","Rental","مكتمل","Completed","g"],["#120","استقدام","Arrival","سهام الشاذلي","Siham Alshathli","مكتمل","Completed","g"],["#129","استقدام","Arrival","sara","sara","مكتمل","Completed","g"]].map(x=>`<tr><td><b class="num">${x[0]}</b></td><td>${t(x[1],x[2])}</td><td>${t(x[3],x[4])}</td><td><span class="st tag ${x[7]}"><i></i>${t(x[5],x[6])}</span></td></tr>`).join("")}</tbody></table></div></div>`,
   types:()=>`<div class="grid-kpi">${[["عقود استقدام","Recruitment",115],["عقود تأجير","Rental",13],["طلبات نقل كفالة","Sponsorship transfer",17],["طلبات (حجوزات)","Orders (bookings)",121]].map(k=>`<div class="card kpi"><div class="kl"><span>${t(k[0],k[1])}</span></div><div class="kv num">${k[2]}</div></div>`).join("")}</div>
    <div class="card flush"><div class="card-h"><div><h3>${t("تفصيل كل نوع","Breakdown per type")}</h3><p>${t("الحالات الفرعية كما تظهر في صفحة كل وحدة","Sub-statuses as shown on each module page")}</p></div></div>
    <div class="tw"><table><thead><tr><th>${t("النوع","Type")}</th><th>${t("الإجمالي","Total")}</th><th>${t("التفصيل","Breakdown")}</th></tr></thead><tbody>${[["عقود الاستقدام","Recruitment contracts",115,"قبل الوصول 27 · مرحلة الوصول 0 · تحت الضمان 2 · مكتملة 8 · ملغاة 11","Pre-arrival 27 · arrival 0 · warranty 2 · completed 8 · cancelled 11"],["طلبات الاستقدام","Recruitment orders",121,"جديدة 6 · تحت الإجراء 7 · مكتملة 108 · ملغاة 0","New 6 · in progress 7 · completed 108 · cancelled 0"],["عقود الإيجار","Rental contracts",13,"نشطة 2 · موقعة 2 · مرسلة 1 · مكتملة 7 · تم التسليم 0","Active 2 · signed 2 · sent 1 · completed 7 · delivered 0"],["نقل الكفالة","Sponsorship transfer",17,"فترة التجربة 2 · تم النقل 0 · تحت الإجراء 15","Trial 2 · transferred 0 · in progress 15"],["الإيواء","Housing",64,"داخل 64 · خارج 5 · جاهز للنقل 13 · مرحلة الترحيل 9","In 64 · out 5 · ready 13 · departure 9"]].map(x=>`<tr><td><b>${t(x[0],x[1])}</b></td><td class="num">${x[2]}</td><td style="white-space:normal">${t(x[3],x[4])}</td></tr>`).join("")}</tbody></table></div></div>`,
   ops:()=>`<div class="grid-2"><div class="card"><div class="card-h"><div><h3>${t("مراحل ما قبل الوصول","Pre-arrival stages")}</h3><p>${t("سير التنفيذ من الاعتماد حتى التذاكر","From approval to tickets")}</p></div></div>${[["عقود جديدة","New contracts",10,21],["عقود مساند","Musaned contracts",7,15],["التفويض","Delegation",3,6],["التفيز","Visa issuing",3,6]].map(x=>`<div class="hbar"><span class="t">${t(x[0],x[1])}</span><span class="track"><i style="width:${x[3]}%"></i></span><span class="n num">${x[2]}</span></div>`).join("")}</div>
    <div class="card"><div class="card-h"><div><h3>${t("مراحل ما بعد الوصول","Post-arrival stages")}</h3><p>${t("الضمان والإغلاق وإعادة المعالجة","Warranty, closing and reprocessing")}</p></div></div>${[["التذكرة","Ticket",4,8],["تحت الضمان","Under warranty",2,4],["عقود مكتملة","Completed",8,17],["العقود الملغاة","Cancelled",11,23],["مسترد","Refunded",0,0]].map(x=>`<div class="hbar"><span class="t">${t(x[0],x[1])}</span><span class="track"><i style="width:${x[3]}%"></i></span><span class="n num">${x[2]}</span></div>`).join("")}</div></div>
    <div class="card flush attn"><div class="card-h"><div><h3>${t("متابعة عاجلة","Urgent follow-up")}</h3></div></div>${[["r","alert-triangle","المتأخرة بالوصول","Late on arrival","18 عقداً تجاوز 60 يوماً بدون تذاكر","18 contracts past 60 days without tickets"],["a","plane","مرحلة الوصول","Arrival stage","4 تذاكر جاهزة بانتظار التأكيد","4 tickets ready awaiting confirmation"],["n","hourglass","عقود منتهية (أقدم من عامين)","Contracts older than two years","0 عقد · تحت الضمان 2 · مكتملة 8","0 contracts · warranty 2 · completed 8"]].map(x=>`<div class="attn-row"><span class="ni ${x[0]}">${I(x[1])}</span><div><b>${t(x[2],x[3])}</b><small>${t(x[4],x[5])}</small></div></div>`).join("")}</div>`
  };
  return head+(body[r[0]]||body.register)();
 },
 contracts:()=>`
 <div class="page-head"><div><h1>${t("عقود الاستقدام","Recruitment contracts")}</h1><p>${t("115 عقداً مسجلاً — 27 سارية، 11 مرتجعة","115 contracts — 27 active, 11 returned")}</p></div>
 <div class="actions"><button class="btn btn-ghost">${I("filter")}${t("فلترة متقدمة","Advanced filter")}</button><button class="btn btn-ghost">${I("download")}${t("تصدير","Export")}</button><button class="btn btn-primary">${I("plus")}${t("إضافة عقد","Add contract")}</button></div></div>
 <div class="filters"><div class="inp">${I("search")}<input placeholder="${t("ابحث برقم العقد أو اسم العميل","Search by contract no. or client")}"></div>
 <select><option>${t("كل الحالات","All statuses")}</option><option>${t("نشط","Active")}</option><option>${t("مكتمل","Completed")}</option><option>${t("متأخر","Delayed")}</option></select>
 <select><option>${t("كل المهن","All occupations")}</option><option>${t("عاملة منزلية","Domestic worker")}</option><option>${t("سائق خاص","Private driver")}</option></select>
 <select><option>${t("كل الدول","All countries")}</option><option>${t("الفلبين","Philippines")}</option><option>${t("الهند","India")}</option></select>
 <span class="empty" style="margin-inline-start:auto">${t("8 نتائج من 115","8 of 115 results")}</span></div>
 <div class="card flush">${table(8,true)}</div>`
};
/* ---------- shell render ---------- */
let NAVQ="",NAVEXP=null;
const navItemHtml=(it,cur)=>{
 if(!it.kids)return `<a href="#" data-go="${it.id}" class="${it.id===cur?"on":""}">${I(it.i)}<span>${t(it.ar,it.en)}</span>${it.b?`<span class="badge num">${it.b}</span>`:""}</a>`;
 const kids=it.kids.filter(k=>k.sec?!NAVQ.trim():(!NAVQ.trim()||(k.ar+k.en).toLowerCase().includes(NAVQ.trim().toLowerCase())));
 const here=it.id===cur||it.kids.some(k=>k.id===cur);
 const open=NAVEXP===it.id||here||(NAVQ.trim()&&kids.length);
 return `<div class="nav-exp ${open?"open":""}">
  <button class="nav-eh ${here?"on":""}" data-navexp="${it.id}">${I(it.i)}<span>${t(it.ar,it.en)}</span>${I("chevron-down","chev")}</button>
  <div class="nav-kids">${(kids.length?kids:it.kids).map(k=>k.sec?`<div class="nav-kid-sec">${t(k.ar,k.en)}</div>`:`<a href="#" data-go="${k.id}" class="${k.id===cur?"on":""}"><i class="kd"></i><span>${t(k.ar,k.en)}</span></a>`).join("")}</div>
 </div>`;
};
function renderNav(cur){
 const q=NAVQ.trim().toLowerCase();
 const match=it=>!q||(it.ar+it.en).toLowerCase().includes(q)||(it.kids||[]).some(k=>(k.ar+k.en).toLowerCase().includes(q));
 const groups=nav.map(g=>({g,items:g.items.filter(match)})).filter(x=>x.items.length);
 if(!groups.length)return `<div class="nav-empty">${t("لا نتائج","No matches")}</div>`;
 return groups.map(({g,items})=>`<div class="nav-group-t">${t(g.t.ar,g.t.en)}</div>${items.map(it=>navItemHtml(it,cur)).join("")}`).join("");
}
let cur="dashboard",bannerOn=false,curReport="register",PERSONA="owner",REPTAB="analytics";
function render(){
 const app=document.getElementById("app");
 document.body.dir=L==="ar"?"rtl":"ltr";
 app.querySelector(".nav").innerHTML=renderNav(cur);
 const title=T[cur]||(cur==="report"?{ar:"تقرير",en:"Report"}:{ar:"قريباً",en:"Coming soon"});
 app.querySelector("[data-title]").textContent=t(title.ar,title.en);
 app.querySelector("[data-crumb]").textContent=t("كليك ERP / الفرع الرئيسي","Click ERP / Main branch");
 app.querySelector(".page").innerHTML=(screens[cur]||(()=>`<div class="card"><h3>${t("هذه الشاشة ضمن النطاق القادم","This screen is out of the current scope")}</h3><p class="empty">${t("الوحدات المصمَّمة حتى الآن: الرئيسية والتقارير، السير الذاتية، الطلبات، عقود الاستقدام والفواتير، عقود التأجير، الإيواء، نقل الكفالة، والعملاء. باقي الوحدات ضمن المراحل القادمة.","Designed so far: home & reports, CVs, orders, recruitment contracts & invoices, rentals, housing, sponsorship transfer and clients. Remaining modules come in later rounds.")}</p></div>`))();
 app.querySelectorAll("[data-ar]").forEach(e=>e.textContent=t(e.dataset.ar,e.dataset.en));
 app.querySelectorAll("[data-ar-ph]").forEach(e=>e.placeholder=t(e.dataset.arPh,e.dataset.enPh));
 lazyInit();
 clockInit();
 document.querySelectorAll(".lang button").forEach(b=>b.classList.toggle("on",b.dataset.l===L));
 lucide.createIcons({attrs:{"stroke-width":1.8}});
}
function clockInit(){
 const el=document.querySelector("[data-clock]"),top=document.querySelector("[data-topclock]");
 const d=document.querySelector("[data-cdate]"),ses=document.querySelector("[data-session]");
 const start=Date.now()-(2*3600+6*60+32)*1000;
 const pad=n=>String(n).padStart(2,"0");
 const tick=()=>{const n=new Date();const s2=pad(n.getHours())+":"+pad(n.getMinutes())+":"+pad(n.getSeconds());if(top)top.textContent=s2;if(!el)return;el.textContent=pad(n.getHours())+":"+pad(n.getMinutes())+":"+pad(n.getSeconds());
  if(d)d.textContent=n.toLocaleDateString(L==="ar"?"ar-EG-u-nu-latn-ca-gregory":"en-GB",{weekday:"long",day:"numeric",month:"long",year:"numeric"});
  if(ses){const e=Math.floor((Date.now()-start)/1000);ses.textContent=pad(Math.floor(e/3600))+":"+pad(Math.floor(e%3600/60))+":"+pad(e%60)}};
 tick();clearInterval(window.__clk);window.__clk=setInterval(tick,1000);
}
function lazyInit(){
 const els=document.querySelectorAll("[data-lazy]");
 if(!els.length)return;
 const io=new IntersectionObserver(es=>{es.forEach(en=>{if(!en.isIntersecting)return;const el=en.target;io.unobserve(el);setTimeout(()=>{const slot=el.querySelector("[data-lazy-slot]");if(slot)slot.innerHTML=chart();el.classList.add("loaded");lucide.createIcons({attrs:{"stroke-width":1.8}})},700)})},{rootMargin:"120px"});
 els.forEach(e=>io.observe(e));
}
document.addEventListener("click",e=>{
 const _m=document.getElementById("appsMenu");
 if(_m&&!_m.classList.contains("hidden")&&!e.target.closest("[data-menu]"))_m.classList.add("hidden");
 const rt=e.target.closest("[data-reptab]");
 if(rt){REPTAB=rt.dataset.reptab;render();return}
 const pb=e.target.closest("[data-persona]");
 if(pb){PERSONA=pb.dataset.persona;render();window.scrollTo(0,0);return}
 const rep=e.target.closest("[data-report]");
 if(rep){curReport=rep.dataset.report;cur="report";render();window.scrollTo(0,0);return}
 const go=e.target.closest("[data-go]");
 if(go){e.preventDefault();cur=go.dataset.go;render();window.scrollTo(0,0);return}
 if(e.target.closest("[data-banner-x]")){bannerOn=false;const b=document.querySelector("[data-banner]");if(b)b.remove();return}
 if(e.target.closest("[data-notif]")){document.querySelector("[data-notif-ov]").classList.remove("hidden");document.querySelector("[data-notif-list]").innerHTML=notifList();lucide.createIcons({attrs:{"stroke-width":1.8}});return}
 const nov=document.querySelector("[data-notif-ov]");
 if(nov&&!nov.classList.contains("hidden")&&(e.target.closest("[data-notif-close]")||e.target===nov)){nov.classList.add("hidden");return}
 if(e.target.closest("[data-whatsnew]")){document.querySelector("[data-wn-ov]").classList.remove("hidden");lucide.createIcons({attrs:{"stroke-width":1.8}});return}
 const wov=document.querySelector("[data-wn-ov]");
 if(wov&&!wov.classList.contains("hidden")&&(e.target.closest("[data-wn-close]")||e.target===wov)){wov.classList.add("hidden");return}
 const rb=e.target.closest("[data-role-toggle]");
 if(rb){ROLE=ROLE==="staff"?"agent":"staff";renderLogin();return}
 if(e.target.closest("[data-forgot]")){e.preventDefault();document.querySelectorAll("[data-signin]").forEach(x=>x.classList.add("hidden"));document.querySelector("[data-reset]").classList.remove("hidden");renderLogin();return}
 if(e.target.closest("[data-back]")){e.preventDefault();document.querySelectorAll("[data-signin]").forEach(x=>x.classList.remove("hidden"));document.querySelector("[data-reset]").classList.add("hidden");renderLogin();return}
 if(e.target.closest("[data-support]")){e.preventDefault();document.querySelector("[data-sup-modal]").classList.remove("hidden");lucide.createIcons({attrs:{"stroke-width":1.8}});return}
 const ov=document.querySelector("[data-sup-modal]");
 if(ov&&!ov.classList.contains("hidden")&&(e.target.closest("[data-sup-close]")||e.target===ov)){ov.classList.add("hidden");return}
 const lb=e.target.closest(".lang button");
 if(lb){L=lb.dataset.l;document.body.dir=L==="ar"?"rtl":"ltr";render();renderLogin();return}
 const sg=e.target.closest("[data-seg] button");
 if(sg){sg.parentElement.querySelectorAll("button").forEach(b=>b.classList.remove("on"));sg.classList.add("on");return}
 const nx=e.target.closest("[data-navexp]");
 if(nx){const ap=document.getElementById("app");if(ap.classList.contains("collapsed"))ap.classList.remove("collapsed");NAVEXP=NAVEXP===nx.dataset.navexp?"":nx.dataset.navexp;ap.querySelector(".nav").innerHTML=renderNav(cur);lucide.createIcons({attrs:{"stroke-width":1.8}});return}
 if(e.target.closest("[data-collapse]")){const ap=document.getElementById("app");if(innerWidth<=900)ap.classList.toggle("navopen");else ap.classList.toggle("collapsed");return}
 if(innerWidth<=900){const ap=document.getElementById("app");if(ap.classList.contains("navopen")&&(e.target.closest(".side .nav a")||!e.target.closest(".side")))ap.classList.remove("navopen")}
 const mb=e.target.closest("[data-menu]");
 const m=document.getElementById("appsMenu");
 if(mb){m.classList.toggle("hidden");lucide.createIcons();return}
 if(e.target.closest("[data-login]")){document.getElementById("login").classList.add("hidden");document.getElementById("app").classList.remove("hidden");render();return}
 if(e.target.closest("[data-logout]")){document.getElementById("app").classList.add("hidden");document.getElementById("login").classList.remove("hidden");renderLogin();}
});
function renderLogin(){
 document.body.dir=L==="ar"?"rtl":"ltr";
 document.querySelectorAll("#login [data-ar]").forEach(e=>e.textContent=t(e.dataset.ar,e.dataset.en));
 document.querySelectorAll("#login [data-ar-ph]").forEach(e=>e.placeholder=t(e.dataset.arPh,e.dataset.enPh));
 const r=ROLES[ROLE];
 const inp=document.querySelector("[data-user-input]");
 if(inp){inp.placeholder=r.ph;inp.closest(".field").querySelector("label").textContent=t(r.lab.ar,r.lab.en)}
 const at=document.querySelector("[data-art-t]"),as=document.querySelector("[data-art-s]");
 const rl=document.querySelector("[data-rl]"),alt=document.querySelector("[data-alt-txt]");
 if(rl)rl.innerHTML=`<i data-lucide="${r.ic}"></i><span>${t(r.rl.ar,r.rl.en)}</span>`;
 if(alt)alt.textContent=t(r.alt.ar,r.alt.en);
 if(at)at.textContent=t(r.t.ar,r.t.en);
 if(as)as.textContent=t(r.s.ar,r.s.en);
 document.querySelectorAll(".lang button").forEach(b=>b.classList.toggle("on",b.dataset.l===L));
 lucide.createIcons({attrs:{"stroke-width":1.8}});
}
renderLogin();

document.addEventListener("input",e=>{if(e.target.closest(".side-search")){NAVQ=e.target.value;const nv=document.querySelector("#app .nav");if(nv){nv.innerHTML=renderNav(cur);lucide.createIcons({attrs:{"stroke-width":1.8}})}}});
