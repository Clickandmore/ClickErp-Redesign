
/* ===== clickerp.js ===== */
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

/* ===== clickerp-cv.js ===== */
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

/* ===== clickerp-crm.js ===== */
/* CRM — grounded in admin/ingaz/clients/* and packages/crm/resources/views/crm/* */
const clTabsL=[["all","كل العملاء","All clients"],["person","أفراد","Individuals"],["company","شركات","Companies"],["potential","عملاء محتملون","Potential"],["blocked","محظورون","Blocked"]];
const clSt=[["active","نشط","Active","g"],["potential","محتمل","Potential","a"],["inactive","غير نشط","Inactive",""],["blocked","محظور","Blocked","r"]];
const clCats=["عميل مميز","عميل عادي","عميل شركات","جهة حكومية"];
const clCities=["الرياض","جدة","الدمام","مكة المكرمة","المدينة المنورة","أبها"];
const clBranches=["الفرع الرئيسي","فرع جدة","فرع الدمام"];
/* 0 no 1 nameAr 2 nameEn 3 type 4 phone 5 email 6 city 7 category 8 status 9 statusAr 10 statusEn 11 tone 12 contracts 13 orders 14 dueSar 15 lastAct 16 added 17 branch 18 nationalNum 19 account 20 addedBy */
const clRows=[
 ["CL-1042","منيرة الشهري","Munira Alshehri","person","+966 55 214 8890","munira@example.com","الرياض","عميل مميز","active","نشط","Active","g",3,5,"4,200","اليوم · 09:12","2024-03-11","الفرع الرئيسي","1042887331","AC-4410","سارة القحطاني"],
 ["CL-1041","شركة نجد للمقاولات","Najd Contracting Co.","company","+966 11 448 2200","info@najd.sa","الرياض","عميل شركات","active","نشط","Active","g",7,12,"18,400","أمس · 16:40","2023-11-02","الفرع الرئيسي","7014229900","AC-4409","مشرف النظام"],
 ["CL-1040","عبدالله الدوسري","Abdullah Aldosari","person","+966 50 771 3320","—","جدة","عميل عادي","potential","محتمل","Potential","a",0,1,"0","قبل 3 أيام","2026-08-04","فرع جدة","1099231447","AC-4408","خالد المطيري"],
 ["CL-1039","نورة الحربي","Noura Alharbi","person","+966 53 900 1187","noura.h@example.com","الدمام","عميل مميز","active","نشط","Active","g",2,3,"1,150","قبل أسبوع","2025-06-19","فرع الدمام","1055412208","AC-4407","سارة القحطاني"],
 ["CL-1038","مؤسسة البيان التجارية","Albayan Trading Est.","company","+966 12 663 5510","ops@albayan.sa","جدة","عميل شركات","inactive","غير نشط","Inactive","",4,4,"0","قبل شهرين","2023-02-27","فرع جدة","7008841122","AC-4406","مشرف النظام"],
 ["CL-1037","فيصل القحطاني","Faisal Alqahtani","person","+966 56 330 4471","—","الرياض","عميل عادي","potential","محتمل","Potential","a",0,2,"0","قبل 5 أيام","2026-07-30","الفرع الرئيسي","1077553390","AC-4405","خالد المطيري"],
 ["CL-1036","هيا العنزي","Haya Alanazi","person","+966 59 118 2043","haya@example.com","مكة المكرمة","عميل عادي","blocked","محظور","Blocked","r",1,1,"3,900","قبل شهر","2024-09-14","الفرع الرئيسي","1088327744","AC-4404","سارة القحطاني"],
 ["CL-1035","شركة الوفاء الطبية","Alwafa Medical Co.","company","+966 13 887 9011","hr@alwafa.sa","الدمام","عميل شركات","active","نشط","Active","g",6,9,"7,600","قبل 4 أيام","2022-12-08","فرع الدمام","7003318876","AC-4403","مشرف النظام"]];
const clStats=[
 ["all","إجمالي العملاء","Total clients","users","n",241],
 ["active","عملاء نشطون","Active clients","user-check","g",184],
 ["potential","عملاء محتملون","Potential clients","user-plus","a",39],
 ["orders","لديهم طلبات","With orders","clipboard-list","n",39],
 ["noorders","بدون طلبات","Without orders","clipboard-x","",202],
 ["due","مستحقات قائمة","Outstanding dues","wallet","a","184,600"],
 ["followups","متابعات مفتوحة","Open follow-ups","phone-call","a",26],
 ["blocked","محظورون","Blocked","ban","r",5]];
let CLTAB="all",CLQ="",CLSTATUS="",CLCITY="",CLCAT="",CLID="CL-1042",CLSTAB="over",CLEDIT=null,CLMENU=null,CLVIEW="rows",CLMORE=false;
const clFiltered=()=>clRows.filter(r=>(CLTAB==="all"||(CLTAB==="potential"?r[8]==="potential":CLTAB==="blocked"?r[8]==="blocked":r[3]===CLTAB))
 &&(!CLSTATUS||r[8]===CLSTATUS)&&(!CLCITY||r[6]===CLCITY)&&(!CLCAT||r[7]===CLCAT)
 &&(!CLQ||(r[0]+r[1]+r[2]+r[4]+r[18]).toLowerCase().includes(CLQ.toLowerCase())));
const clTypeTag=r=>r[3]==="company"?`<span class="tag n"><i data-lucide="building-2"></i>${t("شركة","Company")}</span>`:`<span class="tag"><i data-lucide="user"></i>${t("فرد","Individual")}</span>`;
const clActs=r=>`<div class="rowacts"><button class="icon-btn pri" data-clshow="${r[0]}" title="${t("عرض الملف","Open profile")}">${I("eye")}</button><button class="icon-btn pri" data-cledit="${r[0]}" title="${t("تعديل","Edit")}">${I("pencil")}</button><span class="kebab"><button class="icon-btn" data-clmenu="${r[0]}" title="${t("المزيد","More")}">${I("more-horizontal")}</button><div class="amenu${CLMENU===r[0]?"":" hidden"}" data-clmenu-for="${r[0]}">${[["phone-call","تسجيل متابعة","Log follow-up"],["file-plus","إنشاء طلب","New order"],["message-square","إرسال رسالة","Send message"],["paperclip","إضافة مرفق","Add attachment"],["list-checks","إرسال استبيان","Send poll"],["printer","طباعة الملف","Print profile"],[r[8]==="blocked"?"unlock":"ban",r[8]==="blocked"?"رفع الحظر":"حظر العميل",r[8]==="blocked"?"Unblock":"Block"],["trash-2","حذف العميل","Delete"]].map(x=>`<button data-clact="${x[0]}:${r[0]}"${x[0]==="trash-2"||x[0]==="ban"?' class="danger"':""}>${I(x[0])}<span>${t(x[1],x[2])}</span></button>`).join("")}</div></span></div>`;
const crmScreens={
 clients:()=>{
  const rows=clFiltered();
  return `
 <div class="page-head"><div><h1>${t("إدارة العملاء","Client management")}</h1><p>${t("241 عميلاً — أفراد وشركات، مع المتابعات والمستحقات.","241 clients — individuals and companies, with follow-ups, offers and dues.")}</p></div>
 <div class="actions"><button class="btn btn-ghost">${I("upload")}${t("استيراد","Import")}</button><button class="btn btn-ghost" data-go="polls">${I("list-checks")}${t("الاستبيانات","Polls")}</button><button class="btn btn-ghost">${I("download")}${t("تصدير","Export")}</button><button class="btn btn-primary" data-go="clientAdd" data-perm="CLIENTS_CREATE">${I("user-plus")}${t("إضافة عميل","Add client")}</button></div></div>
 <div class="seg tabs">${clTabsL.map(x=>`<button class="${x[0]===CLTAB?"on":""}" data-cltab="${x[0]}">${t(x[1],x[2])}</button>`).join("")}</div>
 <div class="statgrid">${clStats.map(x=>`<button class="scard ${x[0]===CLSTATUS||(x[0]==="all"&&!CLSTATUS)?"on":""}" data-clstat="${x[0]}"><span class="sci ${x[4]}">${I(x[3])}</span><span class="sct">${t(x[1],x[2])}</span><b class="num">${x[5]}</b></button>`).join("")}</div>
 <div class="fbar">
  <div class="fb grow"><label>${t("بحث","Search")}</label><div class="inp">${I("search")}<input data-clq value="${CLQ}" placeholder="${t("اسم، رقم عميل، جوال، رقم هوية","Name, client no., mobile, ID")}"></div></div>
  <div class="fb"><label>${t("الحالة","Status")}</label><select data-clstatus><option value="">${t("كل الحالات","All statuses")}</option>${clSt.map(s=>`<option value="${s[0]}"${s[0]===CLSTATUS?" selected":""}>${t(s[1],s[2])}</option>`).join("")}</select></div>
  <div class="fb"><label>${t("المدينة","City")}</label><select data-clcity><option value="">${t("كل المدن","All cities")}</option>${clCities.map(c=>`<option${c===CLCITY?" selected":""}>${c}</option>`).join("")}</select></div>
  <div class="fb"><label>${t("التصنيف","Category")}</label><select data-clcat><option value="">${t("كل التصنيفات","All categories")}</option>${clCats.map(c=>`<option${c===CLCAT?" selected":""}>${c}</option>`).join("")}</select></div>
  <button class="btn btn-ghost" data-clmore>${I(CLMORE?"chevron-up":"sliders-horizontal")}${t(CLMORE?"إخفاء":"فلاتر أكثر",CLMORE?"Hide":"More filters")}</button>
  <div class="vtoggle"><button class="${CLVIEW==="rows"?"on":""}" data-clview="rows" title="${t("صفوف","Rows")}">${I("list")}</button><button class="${CLVIEW==="cards"?"on":""}" data-clview="cards" title="${t("بطاقات","Cards")}">${I("layout-grid")}</button></div>
 </div>
 ${CLMORE?`<div class="fbar second">
  <div class="fb"><label>${t("الفرع","Branch")}</label><select><option>${t("كل الفروع","All branches")}</option>${clBranches.map(b=>`<option>${b}</option>`).join("")}</select></div>
  <div class="fb"><label>${t("أضيف بواسطة","Added by")}</label><select><option>${t("الكل","Anyone")}</option><option>سارة القحطاني</option><option>خالد المطيري</option><option>مشرف النظام</option></select></div>
  <div class="fb"><label>${t("لديه عقود","Has contracts")}</label><select><option>${t("الكل","Any")}</option><option>${t("نعم","Yes")}</option><option>${t("لا","No")}</option></select></div>
  <div class="fb"><label>${t("مستحقات","Has dues")}</label><select><option>${t("الكل","Any")}</option><option>${t("مستحقات قائمة","Outstanding")}</option><option>${t("مسدّد بالكامل","Fully settled")}</option></select></div>
  <div class="fb"><label>${t("من تاريخ","From date")}</label><input type="date"></div>
  <div class="fb"><label>${t("إلى تاريخ","To date")}</label><input type="date"></div>
 </div>`:""}
 <div class="card flush">
 ${CLVIEW==="cards"?`<div class="clcards">${rows.length?rows.map(r=>`<div class="clcard"><div class="clcard-h"><span class="av ${r[3]}">${I(r[3]==="company"?"building-2":"user-round")}</span><div><b>${t(r[1],r[2])}</b><small class="num">${r[0]} · ${r[4]}</small></div><span class="st tag ${r[11]}"><i></i>${t(r[9],r[10])}</span></div>
  <div class="clcard-k">${[[t("العقود","Contracts"),r[12]],[t("الطلبات","Orders"),r[13]],[t("المستحق","Due"),r[14]],[t("المدينة","City"),r[6]]].map(x=>`<div><small>${x[0]}</small><b class="num">${x[1]}</b></div>`).join("")}</div>
  <div class="clcard-f"><span class="empty">${I("clock")}${r[15]}</span>${clActs(r)}</div></div>`).join(""):`<div class="tempty">${I("inbox")}<b>${t("لا نتائج مطابقة","No matching results")}</b></div>`}</div>`
 :`<div class="tw"><table class="grp"><thead><tr><th class="chk"><input type="checkbox"></th><th>${t("العميل","Client")}</th><th>${t("بيانات التواصل","Contact data")}</th><th>${t("النشاط","Activity")}</th><th>${t("المستحقات","Dues")}</th><th>${t("آخر نشاط","Last activity")}</th><th>${t("الحالة","Status")}</th><th class="act">${t("إجراءات","Actions")}</th></tr></thead><tbody>
 ${rows.length?rows.map(r=>`<tr>
  <td class="chk"><input type="checkbox"></td>
  <td><div class="wcell"><span class="av ${r[3]}">${I(r[3]==="company"?"building-2":"user-round")}</span><div><b>${t(r[1],r[2])}</b><small>${r[2]}</small><small class="num">${r[0]} · ${r[19]}</small></div></div></td>
  <td><div class="gcell"><b class="num">${r[4]}</b><small>${r[5]}</small><small>${r[6]} · ${r[17]}</small></div></td>
  <td><div class="gcell"><b>${t("عقود","Contracts")}: <span class="num">${r[12]}</span></b><small>${t("طلبات","Orders")}: <span class="num">${r[13]}</span></small><small>${clTypeTag(r)}</small></div></td>
  <td><div class="gcell"><b class="num${r[14]==="0"?"":" due"}">${r[14]} ${t("ر.س","SAR")}</b><small>${r[7]}</small></div></td>
  <td><div class="gcell"><b>${r[15]}</b><small>${t("أضيف","Added")} <span class="num">${r[16]}</span></small><small>${r[20]}</small></div></td>
  <td><span class="st tag ${r[11]}"><i></i>${t(r[9],r[10])}</span></td>
  <td class="act">${clActs(r)}</td></tr>`).join(""):`<tr><td colspan="8"><div class="tempty">${I("inbox")}<b>${t("لا نتائج مطابقة","No matching results")}</b><small>${t("جرّب إزالة الفلاتر","Try clearing the filters")}</small></div></td></tr>`}
 </tbody></table></div>`}
 <div class="tfoot"><span>${t(`عرض ${rows.length} من 241 عميلاً`,`Showing ${rows.length} of 241 clients`)}</span><div class="pager"><button>${I("chevron-right")}</button><button class="on">1</button><button>2</button><button>3</button><button>${I("chevron-left")}</button></div></div>
 </div>`},
 followups:()=>`
 <div class="page-head"><div><h1>${t("متابعة العملاء","Client follow-ups")}</h1><p>${t("26 متابعة مفتوحة — مكالمات وزيارات ورسائل مرتبطة بالعملاء.","26 open follow-ups — calls, visits and messages linked to clients.")}</p></div>
 <div class="actions"><button class="btn btn-ghost">${I("printer")}${t("طباعة","Print")}</button><button class="btn btn-primary">${I("plus")}${t("متابعة جديدة","New follow-up")}</button></div></div>
 <div class="statgrid">${[["متابعات اليوم","Today","calendar-check","n",8],["متأخرة","Overdue","alarm-clock","r",5],["هذا الأسبوع","This week","calendar","a",26],["مغلقة هذا الشهر","Closed this month","check-circle-2","g",64]].map(x=>`<div class="scard"><span class="sci ${x[3]}">${I(x[2])}</span><span class="sct">${t(x[0],x[1])}</span><b class="num">${x[4]}</b></div>`).join("")}</div>
 <div class="fbar">
  <div class="fb grow"><label>${t("العميل","Client")}</label><select><option>${t("كل العملاء","All clients")}</option>${clRows.map(r=>`<option>${r[1]}</option>`).join("")}</select></div>
  <div class="fb"><label>${t("الموظف","User")}</label><select><option>${t("الكل","Anyone")}</option><option>سارة القحطاني</option><option>خالد المطيري</option></select></div>
  <div class="fb"><label>${t("التصنيف","Category")}</label><select><option>${t("كل التصنيفات","All categories")}</option><option>${t("مكالمة","Call")}</option><option>${t("زيارة","Visit")}</option><option>${t("واتساب","WhatsApp")}</option></select></div>
  <div class="fb"><label>${t("من تاريخ","From date")}</label><input type="date"></div>
  <div class="fb"><label>${t("إلى تاريخ","To date")}</label><input type="date"></div>
 </div>
 <div class="card flush"><div class="tw"><table><thead><tr><th>#</th><th>${t("العميل","Client")}</th><th>${t("الموظف","User")}</th><th>${t("التصنيف","Category")}</th><th>${t("الملاحظة","Note")}</th><th>${t("التاريخ","Date")}</th><th>${t("الحالة","Status")}</th><th></th></tr></thead><tbody>
 ${[["FU-318","منيرة الشهري","سارة القحطاني","مكالمة","Call","تأكيد موعد وصول العاملة","Confirming worker arrival date","2026-08-17 09:12","مفتوحة","Open","a"],
   ["FU-317","شركة نجد للمقاولات","خالد المطيري","زيارة","Visit","عرض باقة عقود سنوية","Presented an annual contracts package","2026-08-16 13:40","مغلقة","Closed","g"],
   ["FU-316","عبدالله الدوسري","سارة القحطاني","واتساب","WhatsApp","إرسال عرض سعر مبدئي","Sent an initial price offer","2026-08-14 11:05","متأخرة","Overdue","r"],
   ["FU-315","نورة الحربي","خالد المطيري","مكالمة","Call","استفسار عن نقل الكفالة","Sponsorship transfer enquiry","2026-08-11 15:22","مغلقة","Closed","g"]]
  .map(x=>`<tr><td><b class="num">${x[0]}</b></td><td>${x[1]}</td><td>${x[2]}</td><td><span class="tag">${t(x[3],x[4])}</span></td><td style="white-space:normal">${t(x[5],x[6])}</td><td class="num">${x[7]}</td><td><span class="st tag ${x[10]}"><i></i>${t(x[8],x[9])}</span></td><td><div class="rowacts"><button class="icon-btn pri">${I("eye")}</button><button class="icon-btn pri">${I("pencil")}</button></div></td></tr>`).join("")}
 </tbody></table></div></div>`,
 polls:()=>`
 <div class="page-head"><div><h1>${t("الاستبيانات","Polls")}</h1><p>${t("الاستبيانات وأسئلتها، والعملاء الذين أُرسلت لهم.","Polls with their questions, and the clients they were sent to.")}</p></div>
 <div class="actions"><button class="btn btn-ghost">${I("help-circle")}${t("بنك الأسئلة","Questions")}</button><button class="btn btn-primary">${I("plus")}${t("استبيان جديد","New poll")}</button></div></div>
 <div class="card flush"><div class="card-h"><div><h3>${t("قائمة الاستبيانات","Polls list")}</h3></div></div>
 <div class="tw"><table><thead><tr><th>#</th><th>${t("العنوان","Title")}</th><th>${t("الأسئلة","Questions")}</th><th>${t("عرض الكل","Show all")}</th><th>${t("العملاء","Clients")}</th><th>${t("النتيجة","Result")}</th><th class="act">${t("إجراءات","Actions")}</th></tr></thead><tbody>
 ${[[1,"رضا ما بعد التعاقد","Post-contract satisfaction",6,42,28],[2,"تقييم عروض الأسعار","Price offer feedback",4,19,11],[3,"جودة خدمة الإيواء","Housing service quality",5,23,17]]
  .map(x=>`<tr><td class="num">${x[0]}</td><td><b>${t(x[1],x[2])}</b></td><td><button class="lnk">${I("list-checks")}${x[3]} ${t("أسئلة","questions")}</button></td><td><button class="lnk">${I("external-link")}${t("عرض الكل","Show all")}</button></td><td class="num">${x[4]}</td><td><button class="lnk">${I("bar-chart-3")}${x[5]} ${t("ردّاً","responses")}</button></td><td class="act"><div class="rowacts"><button class="icon-btn pri">${I("send")}</button><button class="icon-btn pri">${I("pencil")}</button><button class="icon-btn">${I("trash-2")}</button></div></td></tr>`).join("")}
 </tbody></table></div></div>
 <div class="grid-2">
  <div class="card sec"><div class="sec-h">${I("help-circle")}<b>${t("أسئلة: رضا ما بعد التعاقد","Questions: Post-contract satisfaction")}</b></div>
   <div class="qlist">${[["choices","هل تم التواصل معك خلال 24 ساعة؟","Were you contacted within 24 hours?",["نعم","لا","جزئياً"]],
     ["choices","كيف تقيّم سرعة إنهاء الإجراءات؟","How do you rate the processing speed?",["ممتازة","جيدة","ضعيفة"]],
     ["essay","ما الذي تقترح تحسينه؟","What would you suggest improving?",null]]
    .map((q,i)=>`<div class="qitem"><div class="qitem-h"><span class="qn num">${i+1}</span><b>${t(q[1],q[2])}</b><span class="tag ${q[0]==="essay"?"":"n"}">${t(q[0]==="essay"?"مقالي":"اختيارات",q[0]==="essay"?"Essay":"Choices")}</span></div>${q[3]?`<div class="qans">${q[3].map(a2=>`<span class="ans">${a2}</span>`).join("")}</div>`:`<div class="qans"><span class="empty">${t("إجابة نصية حرة","Free text answer")}</span></div>`}</div>`).join("")}</div>
   <p class="pnote">${t("نوع السؤال في النظام: اختيارات (بإجابات محددة) أو مقالي.","Question type in the system: choices (with defined answers) or essay.")}</p></div>
  <div class="card sec"><div class="sec-h">${I("users")}<b>${t("إرسال الاستبيان للعملاء","Send poll to clients")}</b></div>
   <div class="tw"><table><thead><tr><th>#</th><th>${t("الاسم","Name")}</th><th>${t("الجوال","Phone")}</th><th>${t("الاستبيانات المُرسلة","Sent polls")}</th><th></th></tr></thead><tbody>
   ${clRows.slice(0,5).map((r,i)=>`<tr><td class="num">${i+1}</td><td><b>${t(r[1],r[2])}</b></td><td class="num">${r[4]}</td><td><button class="lnk">${I("eye")}${i%2?1:2} ${t("مُرسل","sent")}</button></td><td><button class="btn btn-green" data-sendpoll="${r[0]}">${I("share-2")}${t("إرسال واتساب","Share")}</button></td></tr>`).join("")}
   </tbody></table></div></div>
 </div>`
};
const visTabs=[["live","الزوار المباشرون","Live visitors","radio"],["history","سجل الزيارات","Visits history","history"],["msgs","إدارة الرسائل","Conversations","inbox"],["codes","أكواد التتبع","Tracking codes","code"]];
let VISTAB="live";
const visLive=[
 ["زائر #4821","هاتف · Chrome","صفحة العروض — /offers","00:04:12",2,1,"iOS"],
 ["زائر #4820","حاسب · Edge","الرئيسية — /","00:11:36",0,1,"Windows"],
 ["منيرة الشهري","هاتف · Safari","تفاصيل عاملة — /maids/118742","00:02:47",4,1,"iOS"],
 ["زائر #4818","حاسب · Chrome","تواصل معنا — /contact","00:19:05",1,1,"macOS"],
 ["زائر #4817","هاتف · Chrome","الأسعار — /pricing","00:00:52",0,1,"Android"]];
const visHist=[
 ["زائر #4790","مسجل","Registered",0,"حاسب · Windows · Chrome","إعلان جوجل","Google Ads","102.44.18.9",14,"أمس · 21:40"],
 ["عبدالله الدوسري","مسجل","Registered",1,"هاتف · iOS · Safari","دخول مباشر","Direct","91.72.140.22",9,"أمس · 18:12"],
 ["زائر #4788","مجهول","Anonymous",0,"هاتف · Android · Chrome","انستقرام","Instagram","188.55.9.140",3,"قبل يومين · 12:03"],
 ["زائر #4785","مجهول","Anonymous",0,"حاسب · macOS · Safari","بحث جوجل","Google Search","37.19.220.5",6,"قبل 3 أيام · 09:55"]];
const visMsgs=[
 ["محادثة #612","clickandmore.sa","مفتوحة","Open","a","هل يوجد عاملة فلبينية متاحة؟","الزائر","Visitor",5,"قبل 6 دقائق"],
 ["محادثة #611","clickandmore.sa","مُجابة","Answered","g","تم إرسال العرض على واتساب","الموظف","Agent",8,"قبل ساعة"],
 ["محادثة #609","hr.clickandmore.sa","مغلقة","Closed","","شكراً لكم","الزائر","Visitor",12,"أمس · 20:15"]];
const visSites=[["الموقع الرئيسي","clickandmore.sa",1240,"يعمل","Active","g"],["بوابة التوظيف","hr.clickandmore.sa",318,"يعمل","Active","g"],["صفحة الحملات","promo.clickandmore.sa",76,"متوقف","Paused","a"]];
crmScreens.visitors=()=>{
 const body={
  live:()=>`
  <div class="statgrid">${[["متصلون الآن","Online now","radio","g",9],["رسائل غير مقروءة","Unread messages","mail","a",7],["متوسط مدة الجلسة","Avg session","timer","n","06:24"],["زوار اليوم","Today\u2019s visitors","users","n",148]].map(x=>`<div class="scard"><span class="sci ${x[3]}">${I(x[2])}</span><span class="sct">${t(x[0],x[1])}</span><b class="num">${x[4]}</b></div>`).join("")}</div>
  <div class="card flush"><div class="card-h"><div><h3>${t("الزوار المتصلون","Connected visitors")}</h3><p>${t("مرتّبون حسب مدة الجلسة — الأعمدة كما في شاشة النظام.","Sorted by session length — columns as in the system screen.")}</p></div><div class="actions"><button class="btn btn-ghost">${I("volume-2")}${t("تفعيل صوت الدخول","Entry sound")}</button><button class="btn btn-ghost">${I("refresh-cw")}${t("تحديث","Refresh")}</button></div></div>
  <div class="tw"><table><thead><tr><th>${t("الزائر","Visitor")}</th><th>${t("الجهاز","Device")}</th><th>${t("الصفحة الحالية","Current page")}</th><th>${t("مدة الجلسة","Session")}</th><th>${t("الرسائل","Messages")}</th><th class="act"></th></tr></thead><tbody>
  ${visLive.map(v=>`<tr><td><div class="wcell"><span class="pulse"></span><div><b>${v[0]}</b><small>${v[6]}</small></div></div></td><td>${v[1]}</td><td><span class="url">${v[2]}</span></td><td class="num">${v[3]}</td><td>${v[4]?`<span class="tag a num">${v[4]}</span>`:`<span class="empty">—</span>`}</td><td class="act"><div class="rowacts"><button class="icon-btn pri" data-visshow title="${t("التفاصيل","Details")}">${I("eye")}</button><button class="icon-btn pri" data-visshow title="${t("بدء محادثة","Start chat")}">${I("message-circle")}</button></div></td></tr>`).join("")}
  </tbody></table></div></div>`,
  history:()=>`
  <div class="card flush"><div class="card-h"><div><h3>${t("سجل الزيارات","Visits history")}</h3><p>${t("الزوار السابقون بمصادرهم وعدد الصفحات وآخر ظهور.","Past visitors with source, page count and last seen.")}</p></div><div class="actions"><button class="btn btn-ghost">${I("download")}Excel</button></div></div>
  <div class="tw"><table><thead><tr><th>${t("الزائر","Visitor")}</th><th>${t("الحالة","Status")}</th><th>${t("الجهاز","Device")}</th><th>${t("المصدر","Source")}</th><th>IP</th><th>${t("الصفحات","Pages")}</th><th>${t("آخر زيارة","Last seen")}</th><th class="act"></th></tr></thead><tbody>
  ${visHist.map(v=>`<tr><td><div><b>${v[0]}</b><small class="cm">${t(v[1],v[2])}</small></div></td><td><span class="st tag ${v[3]?"g":""}"><i></i>${t(v[3]?"متصل":"غير متصل",v[3]?"Online":"Offline")}</span></td><td>${v[4]}</td><td>${t(v[5],v[6])}</td><td class="num ltr">${v[7]}</td><td><span class="tag n num">${v[8]}</span></td><td class="num">${v[9]}</td><td class="act"><div class="rowacts"><button class="icon-btn pri" data-visshow>${I("eye")}</button></div></td></tr>`).join("")}
  </tbody></table></div></div>`,
  msgs:()=>`
  <div class="card flush"><div class="card-h"><div><h3>${t("إدارة الرسائل","Conversations")}</h3><p>${t("محادثات الزوار عبر مواقع الشركة.","Visitor conversations across company sites.")}</p></div></div>
  <div class="tw"><table><thead><tr><th>${t("المحادثة","Conversation")}</th><th>${t("الموقع","Site")}</th><th>${t("الحالة","Status")}</th><th>${t("آخر رسالة","Last message")}</th><th>${t("مرسل الرسالة","Sent by")}</th><th>${t("الرسائل","Messages")}</th><th>${t("آخر تحديث","Updated")}</th><th class="act"></th></tr></thead><tbody>
  ${visMsgs.map(v=>`<tr><td><b>${v[0]}</b></td><td class="ltr">${v[1]}</td><td><span class="st tag ${v[4]}"><i></i>${t(v[2],v[3])}</span></td><td style="white-space:normal">${v[5]}</td><td>${t(v[6],v[7])}</td><td class="num">${v[8]}</td><td class="num">${v[9]}</td><td class="act"><div class="rowacts"><button class="icon-btn pri" data-visshow>${I("message-circle")}</button></div></td></tr>`).join("")}
  </tbody></table></div></div>`,
  codes:()=>`
  <div class="card sec"><div class="sec-h">${I("code")}<b>${t("أكواد التتبع","Tracking codes")}</b></div>
   <div class="tw"><table><thead><tr><th>${t("الموقع","Site")}</th><th>${t("النطاق","Domain")}</th><th>${t("زوار مسجّلون","Recorded visitors")}</th><th>${t("الحالة","Status")}</th><th class="act"></th></tr></thead><tbody>
   ${visSites.map(v=>`<tr><td><b>${v[0]}</b></td><td class="ltr">${v[1]}</td><td class="num">${v[2].toLocaleString("en")}</td><td><span class="st tag ${v[5]}"><i></i>${t(v[3],v[4])}</span></td><td class="act"><div class="rowacts"><button class="icon-btn pri" title="${t("نسخ الكود","Copy code")}">${I("copy")}</button><button class="icon-btn pri">${I("pencil")}</button><button class="icon-btn">${I("trash-2")}</button></div></td></tr>`).join("")}
   </tbody></table></div>
   <div class="codebox"><div class="codebox-h"><b>${t("كود التتبع للموقع الرئيسي","Tracking snippet — main site")}</b><button class="btn btn-ghost">${I("copy")}${t("نسخ","Copy")}</button></div><pre class="ltr">&lt;script src="https://clickerp.sa/visitor.js" data-site="clickandmore" async&gt;&lt;/script&gt;</pre></div>
   <p class="pnote">${t("يُضاف الكود قبل إغلاق وسم body في كل صفحة يُراد تتبعها.","Add the snippet before the closing body tag on every page to track.")}</p></div>`
 };
 return `
 <div class="page-head"><div><h1>${t("زوار الموقع","Site visitors")}</h1><p>${t("مراقبة حركة الزوار وبدء المحادثات، مع سجل الزيارات وأكواد التتبع.","Live visitor monitoring and chats, with visit history and tracking codes.")}</p></div>
 <div class="actions"><button class="btn btn-ghost">${I("settings-2")}${t("إعدادات الدردشة","Chat settings")}</button><button class="btn btn-primary">${I("plus")}${t("إضافة موقع","Add site")}</button></div></div>
 <div class="vtabs">${visTabs.map(x=>`<button class="${x[0]===VISTAB?"on":""}" data-vistab="${x[0]}">${I(x[3])}<span>${t(x[1],x[2])}</span></button>`).join("")}</div>
 ${(body[VISTAB]||body.live)()}`;
};
const arcRows=[
 ["CL-0912","سعد المطيري","Saad Almutairi","person","+966 50 771 2280","الرياض","طلب العميل","Client request",2,"2026-05-14","سارة القحطاني"],
 ["CL-0908","مؤسسة الخليج","Gulf Est.","company","+966 12 660 1188","جدة","بيانات مكررة","Duplicate record",5,"2026-04-30","مشرف النظام"],
 ["CL-0901","ريم الغامدي","Reem Alghamdi","person","+966 55 903 4417","أبها","غير نشط أكثر من سنة","Inactive over a year",1,"2026-03-22","سارة القحطاني"],
 ["CL-0894","تركي العتيبي","Turki Alotaibi","person","+966 56 220 7741","الدمام","مخالفة شروط التعامل","Terms violation",0,"2026-02-08","مدير الفرع"]];
crmScreens.clArchive=()=>`
 <div class="page-head"><div><h1>${t("أرشيف العملاء","Client archive")}</h1><p>${t("عملاء مؤرشفون — لا يظهرون في القوائم ولا يمكن ربطهم بعقود جديدة حتى الاستعادة.","Archived clients — hidden from lists and not linkable to new contracts until restored.")}</p></div>
 <div class="actions"><button class="btn btn-ghost">${I("download")}${t("تصدير","Export")}</button></div></div>
 <div class="statgrid">${[["إجمالي المؤرشفين","Archived total","archive","n",12],["بطلب العميل","By client request","user-minus","",4],["بيانات مكررة","Duplicate records","copy","a",3],["مخالفات","Violations","ban","r",2]].map(x=>`<div class="scard"><span class="sci ${x[3]}">${I(x[2])}</span><span class="sct">${t(x[0],x[1])}</span><b class="num">${x[4]}</b></div>`).join("")}</div>
 <div class="fbar"><div class="fb grow"><label>${t("بحث","Search")}</label><div class="inp">${I("search")}<input placeholder="${t("اسم، رقم عميل، جوال","Name, client no., phone")}"></div></div>
 <div class="fb"><label>${t("سبب الأرشفة","Archive reason")}</label><select><option>${t("كل الأسباب","All reasons")}</option><option>${t("طلب العميل","Client request")}</option><option>${t("بيانات مكررة","Duplicate record")}</option><option>${t("غير نشط","Inactive")}</option><option>${t("مخالفة","Violation")}</option></select></div>
 <div class="fb"><label>${t("من تاريخ","From date")}</label><input type="date"></div><div class="fb"><label>${t("إلى تاريخ","To date")}</label><input type="date"></div></div>
 <div class="card flush"><div class="card-h"><div><h3>${t("العملاء المؤرشفون","Archived clients")}</h3><p>${t("الاستعادة تُرجع العميل وسجل عقوده كما كان.","Restoring returns the client and their contract history.")}</p></div></div>
 <div class="tw"><table><thead><tr><th>${t("العميل","Client")}</th><th>${t("النوع","Type")}</th><th>${t("الجوال","Phone")}</th><th>${t("المدينة","City")}</th><th>${t("سبب الأرشفة","Reason")}</th><th>${t("عقود سابقة","Past contracts")}</th><th>${t("تاريخ الأرشفة","Archived on")}</th><th>${t("بواسطة","By")}</th><th class="act">${t("إجراءات","Actions")}</th></tr></thead><tbody>
 ${arcRows.map(r=>`<tr><td><div><b>${t(r[1],r[2])}</b><small class="cm num">${r[0]}</small></div></td><td>${r[3]==="company"?`<span class="tag n">${t("شركة","Company")}</span>`:`<span class="tag">${t("فرد","Individual")}</span>`}</td><td class="num">${r[4]}</td><td>${r[5]}</td><td>${t(r[6],r[7])}</td><td class="num">${r[8]}</td><td class="num">${r[9]}</td><td>${r[10]}</td><td class="act"><div class="rowacts"><button class="icon-btn pri" data-clshow="${r[0]}" title="${t("عرض الملف","Open profile")}">${I("eye")}</button><button class="icon-btn pri" data-arcrestore="${r[0]}" title="${t("استعادة","Restore")}">${I("undo-2")}</button><button class="icon-btn" title="${t("حذف نهائي","Delete permanently")}">${I("trash-2")}</button></div></td></tr>`).join("")}
 </tbody></table></div>
 <div class="tfoot"><span>${t("عرض 4 من 12 عميلاً مؤرشفاً","Showing 4 of 12 archived clients")}</span><div class="pager"><button>${I("chevron-right")}</button><button class="on">1</button><button>2</button><button>3</button><button>${I("chevron-left")}</button></div></div>
 </div>`;
/* ---------- client profile ---------- */
const clShowTabs=[["over","نظرة عامة","Overview","gauge"],["data","البيانات الكاملة","Full data","id-card"],["contracts","العقود","Contracts","file-signature"],["orders","الطلبات","Orders","clipboard-list"],["invoices","الفواتير والمالية","Invoices & finance","wallet"],["followups","المتابعات","Follow-ups","phone-call"],["files","المرفقات","Attachments","paperclip"],["log","سجل النشاط","Activity log","history"]];
crmScreens.clientShow=()=>{
 const r=clRows.find(x=>x[0]===CLID)||clRows[0];const comp=r[3]==="company";
 const body={
  over:()=>`
   ${sect("gauge","ملخص العميل","Client summary",`<div class="statgrid four">${[["العقود","Contracts","file-signature","n",r[12]],["الطلبات","Orders","clipboard-list","n",r[13]],["المستحق","Outstanding",'wallet',r[14]==="0"?"g":"a",r[14]],["متابعات مفتوحة","Open follow-ups","phone-call","a",r[8]==="active"?2:0]].map(x=>`<div class="scard"><span class="sci ${x[3]}">${I(x[2])}</span><span class="sct">${t(x[0],x[1])}</span><b class="num">${x[4]}</b></div>`).join("")}</div>`)}
   ${sect("id-card","البطاقة السريعة","Quick card",kvs([["رقم العميل","Client no.",`<span class="num">${r[0]}</span>`],["نوع العميل","Client type",comp?t("شركة","Company"):t("فرد","Individual")],["الجوال","Mobile",`<span class="num">${r[4]}</span>`],["البريد الإلكتروني","Email",r[5]],["المدينة","City",r[6]],["الفرع","Branch",r[17]],["التصنيف","Category",r[7]],["رقم الحساب","Account no.",`<span class="num">${r[19]}</span>`],[comp?"الرقم الضريبي":"رقم الهوية",comp?"Tax number":"National ID",`<span class="num">${r[18]}</span>`],["أضيف بواسطة","Added by",r[20]],["تاريخ الإضافة","Added on",`<span class="num">${r[16]}</span>`],["آخر نشاط","Last activity",r[15]]]))}
   ${sect("bell","تنبيهات ومتابعة","Alerts & follow-up",`<div class="alerts">${[
     [r[14]==="0"?"g":"a",r[14]==="0"?"لا مستحقات قائمة":"مستحقات قائمة "+r[14]+" ر.س",r[14]==="0"?"No outstanding dues":"Outstanding dues "+r[14]+" SAR",r[14]==="0"?"":"فاتورتان تجاوزتا الموعد"],
     [r[8]==="blocked"?"r":"n",r[8]==="blocked"?"العميل محظور من التعاقد":"العميل مؤهل للتعاقد",r[8]==="blocked"?"Client is blocked":"Client is eligible",r[8]==="blocked"?"يمكن رفع الحظر من الإجراءات":""],
     ["a","متابعة مجدولة","Scheduled follow-up","مكالمة تأكيد يوم 20 أغسطس"]].map(x=>`<div class="alert ${x[0]}">${I("dot")}<div><b>${t(x[1],x[2])}</b>${x[3]?`<small>${x[3]}</small>`:""}</div></div>`).join("")}</div>`)}`,
  data:()=>`
   ${sect("user-circle","البيانات الأساسية","Core data",kvs([["الاسم","Name",t(r[1],r[2])],["الاسم بالإنجليزية","Full name (EN)",r[2]],["نوع العميل","Client type",comp?t("شركة","Company"):t("فرد","Individual")],["الجوال","Phone",`<span class="num">${r[4]}</span>`],["البريد الإلكتروني","Email",r[5]],["الجنسية","Nationality",t("سعودي","Saudi")],["تاريخ الميلاد","Birth date",comp?"—":'<span class="num">1988-04-12</span>'],["الحالة الاجتماعية","Marital status",comp?"—":t("متزوج","Married")],["المهنة","Job",comp?t("نشاط تجاري","Commercial activity"):t("موظف حكومي","Government employee")],["جهة العمل","Workplace",comp?r[2]:t("وزارة الصحة","Ministry of Health")]]))}
   ${sect("shield-check","الهوية والتراخيص","Identity & licences",kvs([[comp?"الرقم الضريبي":"رقم الهوية",comp?"Tax number":"National ID",`<span class="num">${r[18]}</span>`],["نوع الهوية","Identity type",comp?t("سجل تجاري","Commercial register"):t("هوية وطنية","National ID")],["تاريخ الهوية","Identity date",'<span class="num">1446-03-18</span>'],["رقم الرخصة","Licence number",comp?'<span class="num">4030118822</span>':"—"],["انتهاء الإقامة","Residence expiry",comp?"—":'<span class="num">2028-01-30</span>'],["التصنيف المالي","Client category",r[7]]]))}
   ${sect("map-pin","العنوان","Address",kvs([["المدينة","City",r[6]],["العنوان","Address",t("حي الملقا، طريق أنس بن مالك","Al Malqa, Anas Bin Malik Rd")],["صندوق البريد","PO box",'<span class="num">28841</span>'],["الرمز البريدي","Postal code",'<span class="num">13524</span>'],["الفرع التابع له","Branch",r[17]]]))}
   ${comp?"":sect("home","بيانات المنزل والأسرة","Household data",kvs([["عدد الغرف","Rooms",'<span class="num">6</span>'],["عدد أفراد الأسرة","Family members",'<span class="num">5</span>'],["عدد الأطفال","Children",'<span class="num">3</span>']]))}
   ${sect("sticky-note","ملاحظات","Notes",`<p class="pnote">${t("عميل متعاون، يفضل التواصل عبر الواتساب في الفترة المسائية.","Cooperative client; prefers WhatsApp contact in the evening.")}</p>`)}`,
  contracts:()=>`
   ${sect("file-signature","عقود الاستقدام","Recruitment contracts",`<div class="tw"><table><thead><tr><th>${t("رقم العقد","Contract no.")}</th><th>${t("العاملة","Worker")}</th><th>${t("الحالة","Status")}</th><th>${t("تاريخ العقد","Date")}</th><th>${t("القيمة","Value")}</th><th></th></tr></thead><tbody>
    ${r[12]?[["CN-2026-0112","Marites Dela Cruz","تحت الضمان","Under warranty","a","2026-08-04","18,500"],["CN-2025-0908","Sri Wahyuni","مكتمل","Completed","g","2025-09-12","17,900"]].slice(0,Math.max(1,Math.min(2,r[12]))).map(x=>`<tr><td><b class="num">${x[0]}</b></td><td>${x[1]}</td><td><span class="st tag ${x[4]}"><i></i>${t(x[2],x[3])}</span></td><td class="num">${x[5]}</td><td class="num">${x[6]}</td><td><div class="rowacts"><button class="icon-btn pri">${I("eye")}</button></div></td></tr>`).join(""):`<tr><td colspan="6"><div class="tempty">${I("inbox")}<b>${t("لا عقود","No contracts")}</b></div></td></tr>`}
   </tbody></table></div>`)}
   ${sect("arrow-left-right","عقود نقل الكفالة","Sponsorship transfer",`<div class="tw"><table><thead><tr><th>${t("رقم العقد","Contract no.")}</th><th>${t("الحالة","Status")}</th><th>${t("الكفيل السابق","Old sponsor")}</th><th>${t("التاريخ","Date")}</th><th>${t("القيمة","Value")}</th></tr></thead><tbody>
    ${comp?`<tr><td><b class="num">TR-2026-0044</b></td><td><span class="st tag a"><i></i>${t("تحت الإجراء","In progress")}</span></td><td>${t("مؤسسة الرواد","Alruwad Est.")}</td><td class="num">2026-07-21</td><td class="num">6,400</td></tr>`:`<tr><td colspan="5"><div class="tempty">${I("inbox")}<b>${t("لا سجلات","No records")}</b></div></td></tr>`}
   </tbody></table></div>`)}`,
  orders:()=>sect("clipboard-list","الطلبات والحجوزات","Orders & bookings",`<div class="tw"><table><thead><tr><th>#</th><th>${t("المهنة","Occupation")}</th><th>${t("الجنسية","Nationality")}</th><th>${t("الحالة","Status")}</th><th>${t("تاريخ الإنشاء","Created")}</th><th></th></tr></thead><tbody>
   ${[["OR-4412","عاملة منزلية","Domestic worker","الفلبين","Philippines","مكتملة","Completed","g","2026-08-02"],["OR-4380","طباخة","Cook","إندونيسيا","Indonesia","تحت الإجراء","In progress","a","2026-07-28"],["OR-4331","سائق خاص","Private driver","الهند","India","ملغاة","Cancelled","r","2026-06-14"]].slice(0,Math.max(1,Math.min(3,r[13]))).map(x=>`<tr><td><b class="num">${x[0]}</b></td><td>${t(x[1],x[2])}</td><td>${t(x[3],x[4])}</td><td><span class="st tag ${x[7]}"><i></i>${t(x[5],x[6])}</span></td><td class="num">${x[8]}</td><td><div class="rowacts"><button class="icon-btn pri">${I("eye")}</button></div></td></tr>`).join("")}
  </tbody></table></div>`),
  invoices:()=>`
   ${sect("wallet","الملخص المالي","Financial summary",`<div class="fin3">${[["إجمالي الفواتير","Total invoiced","36,900","n"],["المحصّل","Collected",(r[14]==="0"?"36,900":"32,700"),"g"],["المستحق","Outstanding",r[14],r[14]==="0"?"g":"r"]].map(x=>`<div class="fin"><small>${t(x[0],x[1])}</small><b class="num ${x[3]}">${x[2]}</b></div>`).join("")}</div>`)}
   ${sect("receipt","الفواتير","Invoices",`<div class="tw"><table><thead><tr><th>${t("رقم الفاتورة","Invoice no.")}</th><th>${t("رقم العقد","Contract no.")}</th><th>${t("الإجمالي","Total")}</th><th>${t("الضريبة","Tax")}</th><th>${t("الخصم","Discount")}</th><th>${t("المستحق","Amount due")}</th><th>${t("التاريخ","Created at")}</th></tr></thead><tbody>
    ${[["INV-9921","CN-2026-0112","18,500","2,775","0",r[14],"2026-08-04"],["INV-9840","CN-2025-0908","17,900","2,685","500","0","2025-09-12"]].map(x=>`<tr><td><b class="num">${x[0]}</b></td><td class="num">${x[1]}</td><td class="num">${x[2]}</td><td class="num">${x[3]}</td><td class="num">${x[4]}</td><td class="num${x[5]==="0"?"":" due"}">${x[5]}</td><td class="num">${x[6]}</td></tr>`).join("")}
   </tbody></table></div>`)}`,
  followups:()=>sect("phone-call","سجل المتابعات","Follow-up history",`<div class="tl">${[["2026-08-17 09:12",t("مكالمة — تأكيد موعد الوصول","Call — arrival date confirmation"),"سارة القحطاني"],["2026-08-10 14:03",t("واتساب — إرسال عرض السعر","WhatsApp — price offer sent"),"خالد المطيري"],["2026-07-29 11:20",t("زيارة — توقيع العقد","Visit — contract signing"),"سارة القحطاني"]].map(x=>`<div class="tli"><span class="dot"></span><div><b>${x[1]}</b><small>${x[2]}</small><small class="num">${x[0]}</small></div></div>`).join("")}</div>`),
  files:()=>sect("paperclip","المرفقات","Attachments",`
   <div class="att hi"><div class="att-t">${I("file-text")}<div><b>${t("صورة الهوية الوطنية","National ID copy")}</b><small>id_copy.pdf · 420 KB</small></div></div><div class="att-a"><button class="btn btn-ghost">${I("eye")}${t("عرض","Preview")}</button><button class="btn btn-ghost">${I("download")}${t("تنزيل","Download")}</button></div></div>
   <div class="att"><div class="att-t">${I("file-signature")}<div><b>${t("العقد الموقّع","Signed contract")}</b><small>contract_0112.pdf · 1.2 MB</small></div></div><div class="att-a"><button class="btn btn-ghost">${I("eye")}${t("عرض","Preview")}</button><button class="btn btn-ghost">${I("download")}${t("تنزيل","Download")}</button></div></div>
   <div class="upl" data-perm="CLIENTS_EDIT"><label>${t("إضافة مرفق (تصنيف ثم الملفات)","Add attachment (category, then files)")}</label><select><option>${t("اختر التصنيف","Choose category")}</option><option>${t("هوية","Identity")}</option><option>${t("عقد","Contract")}</option><option>${t("سند قبض","Receipt")}</option></select><div class="ctl file">${I("upload")}<span>${t("اختر الملفات — يمكن تحديد أكثر من ملف","Select files — multiple allowed")}</span></div><button class="btn btn-primary">${I("upload")}${t("رفع المرفقات","Upload")}</button></div>`),
  log:()=>sect("history","سجل النشاط","Activity log",`<div class="tl">${[["2026-08-17 09:12",t("تسجيل متابعة جديدة","Follow-up logged"),"سارة القحطاني"],["2026-08-04 10:44",t("إنشاء عقد استقدام","Recruitment contract created"),"مشرف النظام"],[r[16]+" 08:02",t("أُضيف العميل","Client created"),r[20]]].map(x=>`<div class="tli"><span class="dot"></span><div><b>${x[1]}</b><small>${x[2]}</small><small class="num">${x[0]}</small></div></div>`).join("")}</div>`)
 };
 return `
 <div class="page-head compact"><div><a class="back" href="#" data-go="clients" style="margin:0 0 8px">${I("arrow-right")}<span>${t("الرجوع لقائمة العملاء","Back to clients")}</span></a><h1>${t(r[1],r[2])}</h1><p>${r[0]} · ${comp?t("شركة","Company"):t("فرد","Individual")} · ${r[6]} · ${r[7]}</p></div>
 <div class="actions"><button class="btn btn-primary" data-cledit="${r[0]}" data-perm="CLIENTS_EDIT">${I("pencil")}${t("تعديل العميل","Edit client")}</button><button class="btn btn-ghost">${I("printer")}${t("طباعة الملف","Print profile")}</button></div></div>
 <div class="showgrid3">
  <aside class="cvside">
   <div class="card idcard">
    <span class="bigph ${r[3]}">${I(comp?"building-2":"user-round")}</span>
    <b>${t(r[1],r[2])}</b><small class="num">${r[4]}</small>
    <span class="st tag ${r[11]}"><i></i>${t(r[9],r[10])}</span>
    <div class="idk">${[[t("العقود","Contracts"),r[12]],[t("الطلبات","Orders"),r[13]],[t("المستحق","Due"),r[14]],[t("الفرع","Branch"),r[17]]].map(x=>`<div><small>${x[0]}</small><b>${x[1]}</b></div>`).join("")}</div>
   </div>
   <div class="card acard">
    <div class="sec-h">${I("settings-2")}<b>${t("إجراءات العميل","Client actions")}</b></div>
    <div class="acts2">${[["phone-call","تسجيل متابعة","Log follow-up",""],["file-plus","إنشاء طلب","New order",""],["message-square","إرسال رسالة","Send message",""],["list-checks","إرسال استبيان","Send poll",""],["paperclip","إضافة مرفق","Add attachment",""],["printer","طباعة الملف","Print",""],[r[8]==="blocked"?"unlock":"ban",r[8]==="blocked"?"رفع الحظر":"حظر العميل",r[8]==="blocked"?"Unblock":"Block","danger"]].map(x=>`<button class="atile ${x[3]}" data-clact="${x[0]}:${r[0]}">${I(x[0])}<span>${t(x[1],x[2])}</span></button>`).join("")}</div>
   </div>
  </aside>
  <div class="cvmain">
   <div class="vtabs">${clShowTabs.map(x=>`<button class="${x[0]===CLSTAB?"on":""}" data-clstab="${x[0]}">${I(x[3])}<span>${t(x[1],x[2])}</span></button>`).join("")}</div>
   <div class="tabbody">${(body[CLSTAB]||body.over)()}</div>
  </div>
 </div>`;
};
/* ---------- add / edit client ---------- */
const clEditVals=()=>{const r=clRows.find(x=>x[0]===CLEDIT);if(!r)return{};return{name:r[1],full_name_en:r[2],phone:r[4],email:r[5],city:r[6],client_category_id:r[7],branch_id:r[17],national_num:r[18],account:r[19]}};
let CLTYPE="person";
crmScreens.clientAdd=()=>{
 const ed=!!CLEDIT,V=ed?clEditVals():{},comp=CLTYPE==="company";
 const f=(ar,en,nm,type,val)=>{const v=V[nm];
  let ctl;
  if(type==="select")ctl=`<select>${v?"":`<option value="">${t("اختر","Choose")}</option>`}${((Array.isArray(val)?val:[val]).includes(v)||!v?(Array.isArray(val)?val:[val]):[v].concat(val)).map(o=>`<option${o===v?" selected":""}>${o}</option>`).join("")}</select>`;
  else if(type==="date")ctl=`<input type="date">`;
  else if(type==="area")ctl=`<textarea rows="3" placeholder="${val||""}"></textarea>`;
  else ctl=`<input type="${type||"text"}" ${v?`value="${v}"`:`placeholder="${val||""}"`}${["phone","full_name_en","national_num","tax_number","license_number","account","identity_date"].includes(nm)?' dir="ltr"':""}>`;
  return `<div class="fld"><label>${t(ar,en)}<code>${nm}</code></label>${ctl}</div>`};
 const grid=fs=>`<div class="fgrid">${fs.join("")}</div>`;
 return `
 <div class="page-head"><div><a class="back" href="#" ${ed?`data-clshow="${CLEDIT}"`:'data-go="clients"'} style="margin:0 0 8px">${I("arrow-right")}<span>${ed?t("الرجوع للملف","Back to profile"):t("إدارة العملاء","Client management")}</span></a><h1>${ed?t("تعديل العميل","Edit client"):t("إضافة عميل","Add client")}${ed?`<span class="tag a hdrtag">${I("pencil")}${t("تعديل","Editing")}</span>`:""}</h1>${ed?`<p>${t(clRows.find(x=>x[0]===CLEDIT)[1],clRows.find(x=>x[0]===CLEDIT)[2])} · <span class="num">${CLEDIT}</span></p>`:`<p>${t("الحقول المطلوبة: الاسم والجوال ونوع العميل.","Required: name, phone and client type.")}</p>`}</div>
 <div class="actions"><button class="btn btn-ghost" data-go="clients">${t("إلغاء","Cancel")}</button><button class="btn btn-primary">${I("check")}${ed?t("حفظ التعديلات","Save changes"):t("حفظ العميل","Save client")}</button></div></div>
 <div class="cvform">
  <aside class="cvopts">
   <div class="card">
    <div class="sec-h">${I("users")}<b>${t("نوع العميل","Client type")}</b></div>
    <div class="modes">${[["person","user","فرد","Individual"],["company","building-2","شركة / منشأة","Company"]].map(x=>`<button class="mode ${CLTYPE===x[0]?"on":""}" data-cltype="${x[0]}">${I(x[1])}<span>${t(x[2],x[3])}</span></button>`).join("")}</div>
    <div class="actrow sm col"><label>${t("الحالة","Status")}<code>status</code></label><select class="statsel">${clSt.map(x=>`<option>${t(x[1],x[2])}</option>`).join("")}</select></div>
    <div class="actrow sm col"><label>${t("الفرع","Branch")}<code>branch_id</code></label><select class="statsel">${clBranches.map(b=>`<option>${b}</option>`).join("")}</select></div>
   </div>
   <div class="card">
    <div class="sec-h">${I("list-checks")}<b>${t("أقسام النموذج","Form sections")}</b></div>
    <div class="steps">${[["s1","البيانات الأساسية","Core data"],["s2",comp?"بيانات المنشأة":"الهوية والإقامة",comp?"Company data":"Identity"],["s3","العنوان","Address"],comp?null:["s4","الأسرة والسكن","Household"],["s5","التصنيف والملاحظات","Category & notes"]].filter(Boolean).map((x,i)=>`<button class="step" data-cljump="${x[0]}"><span class="stepn num">${i+1}</span><span>${t(x[1],x[2])}</span></button>`).join("")}</div>
   </div>
  </aside>
  <div class="cvfields">
   <div class="card sec" id="s1"><div class="fs-h"><span class="fs-n num">1</span><b>${t("البيانات الأساسية","Core data")}</b></div>
    ${grid([f("الاسم","Name","name","text",comp?"اسم المنشأة":"الاسم الثلاثي"),f("الاسم بالإنجليزية","Full name (EN)","full_name_en","text","Full name"),f("الجوال","Phone","phone","text","+966 5X XXX XXXX"),f("البريد الإلكتروني","Email","email","email","name@example.com"),f("الجنسية","Nationality","nationality_id","select",["سعودي","مصري","سوري","يمني","أخرى"]),f("تاريخ الميلاد","Birth date","birthdate","date")])}</div>
   <div class="card sec" id="s2"><div class="fs-h"><span class="fs-n num">2</span><b>${comp?t("بيانات المنشأة","Company data"):t("الهوية والإقامة","Identity & residence")}</b></div>
    ${grid(comp?[f("الرقم الضريبي","Tax number","tax_number","text","3XXXXXXXXXXXXX3"),f("رقم السجل / الرخصة","Licence number","license_number","text","4030XXXXXX"),f("جهة العمل / النشاط","Workplace","workplace","text","النشاط التجاري"),f("مسؤول التواصل","Contact person","job","text","اسم المسؤول")]
     :[f("رقم الهوية","National ID","national_num","text","1XXXXXXXXX"),f("نوع الهوية","Identity type","identity_type","select",["هوية وطنية","إقامة","جواز سفر"]),f("تاريخ الهوية","Identity date","identity_date","text","هجري أو ميلادي"),f("انتهاء الإقامة","Residence expiry","residence_expiry_date","date"),f("الحالة الاجتماعية","Marital status","marital_status","select",["أعزب","متزوج","مطلق","أرمل"]),f("المهنة","Job","job","text","المهنة الحالية"),f("جهة العمل","Workplace","workplace","text","اسم جهة العمل")])}</div>
   <div class="card sec" id="s3"><div class="fs-h"><span class="fs-n num">3</span><b>${t("العنوان","Address")}</b></div>
    ${grid([f("المدينة","City","city","select",clCities),f("العنوان","Address","address","text","الحي والشارع"),f("صندوق البريد","PO box","po_box","text","28841"),f("الرمز البريدي","Postal code","postal_code","text","13524")])}</div>
   ${comp?"":`<div class="card sec" id="s4"><div class="fs-h"><span class="fs-n num">4</span><b>${t("الأسرة والسكن","Household")}</b></div>
    ${grid([f("عدد الغرف","Rooms","rooms_num","number","0"),f("عدد أفراد الأسرة","Family members","family_num","number","0"),f("عدد الأطفال","Children","children","number","0")])}</div>`}
   <div class="card sec" id="s5"><div class="fs-h"><span class="fs-n num">${comp?4:5}</span><b>${t("التصنيف والملاحظات","Category & notes")}</b></div>
    ${grid([f("تصنيف العميل","Client category","client_category_id","select",clCats),f("رقم الحساب المحاسبي","Account number","account","text","AC-XXXX")])}
    <div class="fld full"><label>${t("ملاحظات","Notes")}<code>notes</code></label><textarea rows="4" placeholder="${t("ملاحظات داخلية عن العميل","Internal notes about the client")}"></textarea></div>
   </div>
   <div class="formbar"><button class="btn btn-ghost" data-go="clients">${t("إلغاء","Cancel")}</button><button class="btn btn-primary">${I("check")}${ed?t("حفظ التعديلات","Save changes"):t("حفظ العميل","Save client")}</button></div>
  </div>
 </div>`;
};
Object.assign(T,{clients:{ar:"إدارة العملاء",en:"Clients"},clientShow:{ar:"ملف العميل",en:"Client profile"},clientAdd:{ar:"إضافة عميل",en:"Add client"},followups:{ar:"متابعة العملاء",en:"Follow-ups"},polls:{ar:"الاستبيانات",en:"Polls"},visitors:{ar:"زوار الموقع",en:"Site visitors"},clArchive:{ar:"أرشيف العملاء",en:"Client archive"}});
Object.assign(screens,crmScreens);
/* nav: expand the customers group */
(function(){const g=nav.find(x=>x.t.ar==="العملاء");if(!g)return;
 g.items=[{id:"clients",i:"users",ar:"جميع العملاء",en:"All clients",b:"241"},
  {id:"followups",i:"phone-call",ar:"متابعة العملاء",en:"Follow-ups",b:"26"},
  {id:"visitors",i:"radar",ar:"زوار الموقع",en:"Site visitors",b:"9"},
  {id:"clArchive",i:"archive",ar:"أرشيف العملاء",en:"Client archive",b:"12"},
  {id:"msgs",i:"message-square",ar:"المحادثات والرسائل",en:"Conversations"},
  {id:"claims",i:"alert-triangle",ar:"الشكاوى",en:"Complaints",b:"4"}];})();
function clToast(ar,en){let n=document.querySelector(".toast");if(!n){n=document.createElement("div");n.className="toast";document.body.appendChild(n)}n.innerHTML=`<i data-lucide="check-circle-2"></i><span>${t(ar,en)}</span>`;n.classList.add("show");lucide.createIcons({attrs:{"stroke-width":1.8}});clearTimeout(window.__clt);window.__clt=setTimeout(()=>n.classList.remove("show"),2600)}
function clKillMenus(){document.querySelectorAll("body > .amenu").forEach(n=>n.remove());document.querySelectorAll(".amenu").forEach(n=>n.classList.add("hidden"))}
function clPlaceMenu(id){requestAnimationFrame(()=>{
 const btn=document.querySelector(`[data-clmenu="${id}"]`),menu=document.querySelector(`[data-clmenu-for="${id}"]`);
 if(!btn||!menu)return;const b=btn.getBoundingClientRect();
 document.body.appendChild(menu);menu.classList.remove("hidden");
 menu.style.position="fixed";menu.style.zIndex="200";menu.style.insetInlineEnd="auto";
 const h=menu.offsetHeight,w=menu.offsetWidth;
 let top=b.bottom+6;if(top+h>innerHeight-8)top=Math.max(8,b.top-h-6);
 let left=document.body.dir==="rtl"?b.left:b.right-w;
 left=Math.min(Math.max(8,left),innerWidth-w-8);
 menu.style.top=top+"px";menu.style.left=left+"px";
 lucide.createIcons({attrs:{"stroke-width":1.8}});});}
document.addEventListener("click",e=>{
 const tb=e.target.closest("[data-cltab]");if(tb){CLTAB=tb.dataset.cltab;CLSTATUS="";render();return}
 const sc=e.target.closest("[data-clstat]");if(sc){const k=sc.dataset.clstat;CLSTATUS=["active","potential","blocked"].includes(k)?(CLSTATUS===k?"":k):"";render();return}
 const vw=e.target.closest("[data-clview]");if(vw){CLVIEW=vw.dataset.clview;render();return}
 const mf=e.target.closest("[data-clmore]");if(mf){CLMORE=!CLMORE;render();return}
 const sh=e.target.closest("[data-clshow]");if(sh){e.preventDefault();CLID=sh.dataset.clshow;CLSTAB="over";CLEDIT=null;cur="clientShow";render();window.scrollTo(0,0);return}
 const ed=e.target.closest("[data-cledit]");if(ed){CLEDIT=ed.dataset.cledit;CLID=ed.dataset.cledit;CLTYPE=(clRows.find(x=>x[0]===CLEDIT)||{})[3]||"person";cur="clientAdd";render();window.scrollTo(0,0);return}
 const st=e.target.closest("[data-clstab]");if(st){CLSTAB=st.dataset.clstab;render();window.scrollTo(0,0);return}
 const ct=e.target.closest("[data-cltype]");if(ct){CLTYPE=ct.dataset.cltype;render();return}
 const jp=e.target.closest("[data-cljump]");if(jp){const el=document.getElementById(jp.dataset.cljump);if(el)el.scrollIntoView?window.scrollTo({top:el.offsetTop-90,behavior:"smooth"}):0;return}
 const km=e.target.closest("[data-clmenu]");if(km){const id=km.dataset.clmenu;CLMENU=CLMENU===id?null:id;render();if(CLMENU)clPlaceMenu(CLMENU);return}
 const vt=e.target.closest("[data-vistab]");if(vt){VISTAB=vt.dataset.vistab;render();window.scrollTo(0,0);return}
 const ar=e.target.closest("[data-arcrestore]");if(ar){clToast("تمت استعادة العميل من الأرشيف","Client restored from archive");return}
 const sp=e.target.closest("[data-sendpoll]");
 if(sp){const r=clRows.find(x=>x[0]===sp.dataset.sendpoll);let ov=document.getElementById("clActOv");if(!ov){ov=document.createElement("div");ov.id="clActOv";ov.className="ov";document.body.appendChild(ov)}
  ov.innerHTML=`<div class="modal"><div class="modal-h"><div><h3>${t("إرسال استبيان","Send poll")}</h3><p class="ms">${t(r[1],r[2])} · <span class="num">${r[4]}</span></p></div><button class="icon-btn" data-clactclose>${I("x")}</button></div><div class="modal-b"><div class="fld full"><label>${t("اختر الاستبيان","Choose the poll")}<code>poll_id</code></label><select><option>${t("رضا ما بعد التعاقد","Post-contract satisfaction")}</option><option>${t("تقييم عروض الأسعار","Price offer feedback")}</option><option>${t("جودة خدمة الإيواء","Housing service quality")}</option></select></div></div><div class="modal-f"><button class="btn btn-ghost" data-clactclose>${t("إغلاق","Close")}</button><button class="btn btn-green" data-clactdone>${I("share-2")}${t("مشاركة عبر واتساب","Share on WhatsApp")}</button></div></div>`;
  ov.classList.remove("hidden");lucide.createIcons({attrs:{"stroke-width":1.8}});return}
 const ab=e.target.closest("[data-clact]");
 if(ab){const [k,id]=ab.dataset.clact.split(":");const r=clRows.find(x=>x[0]===id);CLMENU=null;clKillMenus();
  if(k==="ban"||k==="unlock"){const on=r[8]==="blocked";r[8]=on?"active":"blocked";r[9]=on?"نشط":"محظور";r[10]=on?"Active":"Blocked";r[11]=on?"g":"r";render();clToast(on?"تم رفع الحظر عن العميل":"تم حظر العميل","Client "+(on?"unblocked":"blocked"));return}
  const names={"phone-call":["تسجيل متابعة جديدة","Log a follow-up"],"file-plus":["إنشاء طلب جديد","Create order"],"message-square":["إرسال رسالة","Send message"],paperclip:["إضافة مرفق","Add attachment"],"list-checks":["إرسال استبيان","Send poll"],printer:["طباعة ملف العميل","Print client profile"],"trash-2":["حذف العميل","Delete client"]};
  const n=names[k]||["إجراء","Action"];
  let ov=document.getElementById("clActOv");if(!ov){ov=document.createElement("div");ov.id="clActOv";ov.className="ov";document.body.appendChild(ov)}
  const forms={"phone-call":`<div class="fgrid"><div class="fld"><label>${t("التصنيف","Category")}</label><select><option>${t("مكالمة","Call")}</option><option>${t("زيارة","Visit")}</option><option>${t("واتساب","WhatsApp")}</option></select></div><div class="fld"><label>${t("التاريخ","Date")}</label><input type="date"></div></div><div class="fld full"><label>${t("الملاحظة","Note")}</label><textarea rows="3"></textarea></div>`,
   "message-square":`<div class="fld full"><label>${t("القناة","Channel")}</label><select><option>${t("واتساب","WhatsApp")}</option><option>${t("رسالة نصية","SMS")}</option><option>${t("بريد إلكتروني","Email")}</option></select></div><div class="fld full"><label>${t("النص","Message")}</label><textarea rows="4"></textarea></div>`,
   "trash-2":`<p class="pnote danger">${t("سيُحذف ملف العميل وكل ما يرتبط به من متابعات ومرفقات. لا يمكن التراجع.","The client profile and all linked follow-ups and attachments will be deleted. This cannot be undone.")}</p>`};
  ov.innerHTML=`<div class="modal"><div class="modal-h"><div><h3>${t(n[0],n[1])}</h3><p class="ms">${t(r[1],r[2])} · <span class="num">${r[0]}</span></p></div><button class="icon-btn" data-clactclose>${I("x")}</button></div><div class="modal-b">${forms[k]||`<p class="pnote">${t("سيتم تنفيذ الإجراء على هذا العميل.","This action will be applied to the client.")}</p>`}</div><div class="modal-f"><button class="btn btn-ghost" data-clactclose>${t("إلغاء","Cancel")}</button><button class="btn ${k==="trash-2"?"btn-red":"btn-primary"}" data-clactdone>${t(k==="trash-2"?"تأكيد الحذف":"تأكيد","Confirm")}</button></div></div>`;
  ov.classList.remove("hidden");lucide.createIcons({attrs:{"stroke-width":1.8}});return}
 const ac=e.target.closest("[data-clactclose]"),ad=e.target.closest("[data-clactdone]");
 if(ac||ad){const ov=document.getElementById("clActOv");if(ov)ov.classList.add("hidden");if(ad)clToast("تم تنفيذ الإجراء","Action completed");return}
 if(CLMENU&&!e.target.closest(".kebab")&&!e.target.closest(".amenu")){CLMENU=null;clKillMenus();render()}
 const gg=e.target.closest("[data-go]");if(gg&&gg.dataset.go!=="clientAdd")CLEDIT=null;
});
document.addEventListener("input",e=>{
 if(e.target.matches("[data-clq]")){CLQ=e.target.value;const p=e.target.selectionStart;render();const n=document.querySelector("[data-clq]");if(n){n.focus();n.setSelectionRange(p,p)}}
});
document.addEventListener("change",e=>{
 if(e.target.matches("[data-clstatus]")){CLSTATUS=e.target.value;render()}
 if(e.target.matches("[data-clcity]")){CLCITY=e.target.value;render()}
 if(e.target.matches("[data-clcat]")){CLCAT=e.target.value;render()}
});

/* ===== clickerp-contracts.js ===== */
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

/* ===== clickerp-housing.js ===== */
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

/* ===== clickerp-transfer.js ===== */
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

/* ===== clickerp-finance.js ===== */
/* Finance — accounts tree, journal entries, bonds
   grounded in packages/finance/resources/views/finance/{account,constraint,bond}/… */
const acType=[["assets","أصول","Assets"],["liabilities","خصوم","Liabilities"],["equity","حقوق ملكية","Equity"],["revenue","إيرادات","Revenue"],["expenses","مصروفات","Expenses"],["clients","عملاء","Clients"],["suppliers","موردون","Suppliers"]];
/* [code, ar, en, side(debtor|creditor), level, debtorVal, creditorVal, opType(2 leaf-locked|3 editable), typeKey, kids[]] */
const acTree=[
 ["1","الأصول","Assets","debtor",1,0,0,2,"assets",[
  ["11","الأصول المتداولة","Current assets","debtor",2,0,0,2,"assets",[
   ["1101","الصندوق الرئيسي","Main cashbox","debtor",3,842500,611300,3,"assets",[]],
   ["1102","البنوك","Banks","debtor",3,0,0,2,"assets",[
    ["110201","بنك الراجحي — 1122","Alrajhi — 1122","debtor",4,1284000,948700,3,"assets",[]],
    ["110202","بنك الأهلي — 4408","AlAhli — 4408","debtor",4,612400,498100,3,"assets",[]]]],
   ["1103","العملاء","Clients","debtor",3,0,0,2,"clients",[
    ["110301","عملاء الاستقدام","Recruitment clients","debtor",4,1842300,1204800,3,"clients",[]],
    ["110302","عملاء التأجير","Rental clients","debtor",4,684200,512900,3,"clients",[]],
    ["110303","عملاء نقل الكفالة","Transfer clients","debtor",4,214800,148200,3,"clients",[]]]],
   ["1104","مصروفات مقدمة","Prepaid expenses","debtor",3,96400,41200,3,"assets",[]]]],
  ["12","الأصول الثابتة","Fixed assets","debtor",2,0,0,2,"assets",[
   ["1201","مباني ومقار الإيواء","Buildings & shelters","debtor",3,2400000,180000,3,"assets",[]],
   ["1202","سيارات النقل","Transport vehicles","debtor",3,860000,142000,3,"assets",[]],
   ["1203","أجهزة وأثاث","Equipment & furniture","debtor",3,318000,64000,3,"assets",[]]]]]],
 ["2","الخصوم","Liabilities","creditor",1,0,0,2,"liabilities",[
  ["21","الخصوم المتداولة","Current liabilities","creditor",2,0,0,2,"liabilities",[
   ["2101","الموردون والمكاتب الخارجية","Suppliers & external offices","creditor",3,0,0,2,"suppliers",[
    ["210101","Manila Prime Agency","Manila Prime Agency","creditor",4,318400,642800,3,"suppliers",[]],
    ["210102","Jakarta Care Agency","Jakarta Care Agency","creditor",4,204100,486200,3,"suppliers",[]],
    ["210103","Colombo Bridge Agency","Colombo Bridge Agency","creditor",4,142900,331500,3,"suppliers",[]]]],
   ["2102","ضريبة القيمة المضافة","VAT payable","creditor",3,184200,412600,3,"liabilities",[]],
   ["2103","رواتب مستحقة","Accrued salaries","creditor",3,148000,286400,3,"liabilities",[]]]]]],
 ["3","حقوق الملكية","Equity","creditor",1,0,0,2,"equity",[
  ["31","رأس المال","Capital","creditor",2,0,3000000,3,"equity",[]],
  ["32","الأرباح المبقاة","Retained earnings","creditor",2,142000,934500,3,"equity",[]]]],
 ["4","الإيرادات","Revenue","creditor",1,0,0,2,"revenue",[
  ["41","إيرادات الاستقدام","Recruitment revenue","creditor",2,0,0,2,"revenue",[
   ["4101","عقود الاستقدام","Recruitment contracts","creditor",3,42000,2148600,3,"revenue",[]],
   ["4102","عقود التأجير","Rental contracts","creditor",3,18400,684200,3,"revenue",[]],
   ["4103","نقل الكفالة","Sponsorship transfer","creditor",3,6600,214800,3,"revenue",[]]]],
  ["42","إيرادات أخرى","Other revenue","creditor",2,0,48200,3,"revenue",[]]]],
 ["5","المصروفات","Expenses","debtor",1,0,0,2,"expenses",[
  ["51","مصروفات تشغيلية","Operating expenses","debtor",2,0,0,2,"expenses",[
   ["5101","مصروف إعاشة الإيواء","Shelter living expense","debtor",3,284600,12400,3,"expenses",[]],
   ["5102","تذاكر وسفر","Tickets & travel","debtor",3,412800,18600,3,"expenses",[]],
   ["5103","تأشيرات ورسوم حكومية","Visas & government fees","debtor",3,684200,24800,3,"expenses",[]],
   ["5104","فحوصات طبية","Medical exams","debtor",3,142000,6200,3,"expenses",[]]]],
  ["52","مصروفات إدارية","Administrative expenses","debtor",2,0,0,2,"expenses",[
   ["5201","رواتب وأجور","Salaries & wages","debtor",3,1284000,42000,3,"expenses",[]],
   ["5202","إيجارات","Rents","debtor",3,384000,0,3,"expenses",[]],
   ["5203","اتصالات وأنظمة","Telecom & systems","debtor",3,96400,4200,3,"expenses",[]]]]]]];
let ACQ="",ACLEVEL="",ACROOT="",ACOPEN={},ACSEL=null;
const acFlat=(nodes=acTree,out=[],parent=null)=>{nodes.forEach(n=>{out.push({n,parent});acFlat(n[9],out,n)});return out};
const acBalance=n=>{let d=n[5],c=n[6];const walk=k=>k.forEach(x=>{d+=x[5];c+=x[6];walk(x[9])});walk(n[9]);return n[3]==="debtor"?d-c:c-d};
const acSums=n=>{let d=n[5],c=n[6];const walk=k=>k.forEach(x=>{d+=x[5];c+=x[6];walk(x[9])});walk(n[9]);return [d,c]};
const acMoney=v=>`<span class="num">${(v<0?"-":"")+Math.abs(v).toLocaleString("en",{minimumFractionDigits:2,maximumFractionDigits:2})}</span>`;
const acMatch=n=>{const q=ACQ.trim().toLowerCase();
 const self=(!q||(n[0]+n[1]+n[2]).toLowerCase().includes(q))&&(!ACLEVEL||String(n[4])===ACLEVEL);
 return self||n[9].some(acMatch)};
const acNode=(n,depth)=>{
 const has=n[9].length,open=ACOPEN[n[0]]!==undefined?ACOPEN[n[0]]:(n[8]!=="clients"&&depth<3);
 const kids=n[9].filter(acMatch);
 const [d,c]=acSums(n),bal=acBalance(n);
 return `<div class="acnode ${ACSEL===n[0]?"sel":""}" data-aclevel="${n[4]}">
  <div class="acrow" style="--d:${depth}">
   ${has?`<button class="actog" data-actoggle="${n[0]}">${I(open?"chevron-down":"chevron-left")}</button>`:'<span class="actog dot"></span>'}
   <span class="acicon ${has?"folder":"leaf"}">${I(has?(open?"folder-open":"folder"):"file-text")}</span>
   <span class="aclvl num">${n[4]}</span>
   <button class="accode num" data-acstatement="${n[0]}" title="${t("فتح كشف الحساب","Open account statement")}">${n[0]}</button>
   <button class="acname" data-acsel="${n[0]}">${t(n[1],n[2])}</button>
   <span class="acside ${n[3]}">${t(n[3]==="debtor"?"مدين":"دائن",n[3]==="debtor"?"Debtor":"Creditor")}</span>
   <span class="actrail">
   <span class="acdc"><small>${t("مدين","Dr")}</small>${acMoney(d)}<small>${t("دائن","Cr")}</small>${acMoney(c)}</span>
   <span class="acbal ${bal<0?"neg":""}">${t("الرصيد","Balance")} ${acMoney(bal)}</span>
   <span class="acacts">
    ${n[7]!==2?`<button class="icon-btn pri" title="${t("إضافة حساب فرعي","Add sub-account")}" data-acadd="${n[0]}">${I("plus")}</button>`:""}
    ${n[7]===3?`<button class="icon-btn pri" title="${t("تعديل","Edit")}" data-acedit="${n[0]}">${I("pencil")}</button><button class="icon-btn" title="${t("حذف","Delete")}" data-acdel="${n[0]}">${I("trash-2")}</button>`:""}
    <button class="icon-btn" title="${t("كشف مطبوع","Printed statement")}" data-acprint="${n[0]}">${I("printer")}</button>
   </span>
   </span>
  </div>
  ${has&&open?`<div class="acbranch">${kids.map(k=>acNode(k,depth+1)).join("")}</div>`:""}
 </div>`};
const financeScreens={
 finAccounts:()=>{
  const roots=acTree.filter(n=>(!ACROOT||n[0]===ACROOT)).filter(acMatch);
  const all=acFlat();
  const totals=acTree.reduce((a,n)=>{const [d,c]=acSums(n);return [a[0]+d,a[1]+c]},[0,0]);
  return `
 <div class="page-head"><div><h1>${t("شجرة الحسابات","Chart of accounts")}</h1><p>${t("الدليل المحاسبي بمستوياته — الرصيد يُجمَّع من الحسابات الفرعية، والكود يفتح كشف الحساب.","The accounting guide by level — balances roll up from sub-accounts, and the code opens the account statement.")}</p></div>
 <div class="actions"><button class="btn btn-ghost" data-go="finReports">${I("folder-open")}${t("التقارير المالية","Financial reports")}</button><button class="btn btn-ghost">${I("printer")}${t("طباعة الدليل","Print guide")}</button><button class="btn btn-primary" data-fiaddent="account" data-acadd="root">${I("plus")}${t("إضافة حساب","Add account")}</button></div></div>
 <div class="pipe">
  ${[["إجمالي الحسابات","Total accounts","list-tree",all.length,"run"],["حسابات رئيسية","Parent accounts","folder",all.filter(x=>x.n[9].length).length,"run"],["حسابات فرعية (ترحيل)","Posting accounts","file-text",all.filter(x=>!x.n[9].length).length,"post"],["أعلى مستوى","Deepest level","layers",Math.max(...all.map(x=>x.n[4])),"run"]].map(x=>`<div class="pg ${x[4]}"><span class="pg-i">${I(x[2])}</span><span class="pg-b"><span class="pg-t">${t(x[0],x[1])}</span><span class="pg-m"><b class="num">${x[3]}</b></span></span></div>`).join("")}
 </div>
 <div class="fbar">
  <div class="fb grow"><label>${t("بحث","Search")}</label><div class="inp">${I("search")}<input data-acq value="${ACQ}" placeholder="${t("كود الحساب أو اسمه","Account code or name")}"></div></div>
  <div class="fb"><label>${t("المستوى","Level")}</label><select data-aclevelf><option value="">${t("كل المستويات","All levels")}</option>${[1,2,3,4].map(l=>`<option value="${l}"${ACLEVEL===String(l)?" selected":""}>${t("المستوى "+l,"Level "+l)}</option>`).join("")}</select></div>
  <div class="fb"><label>${t("الحساب الرئيسي","Root account")}</label><select data-acroot><option value="">${t("كل الحسابات","All accounts")}</option>${acTree.map(n=>`<option value="${n[0]}"${ACROOT===n[0]?" selected":""}>${n[0]} / ${t(n[1],n[2])}</option>`).join("")}</select></div>
  <button class="btn btn-ghost" data-acexpand>${I("unfold-vertical")}${t("فتح الكل","Expand all")}</button>
  <button class="btn btn-ghost" data-accollapse>${I("fold-vertical")}${t("طي الكل","Collapse all")}</button>
 </div>
 <div class="card flush">
  <div class="card-h"><div><h3>${t("الدليل المحاسبي","Accounting guide")}</h3><p>${t("مدين بالأحمر، دائن بالأزرق — كما في النظام.","Debtor in red, creditor in blue — as in the system.")}</p></div>
   <div class="acsum"><span>${t("إجمالي المدين","Total debit")} ${acMoney(totals[0])}</span><span>${t("إجمالي الدائن","Total credit")} ${acMoney(totals[1])}</span></div></div>
  <div class="actree grouped">${roots.length?roots.map(n=>{const [d,c]=acSums(n);const bal=acBalance(n);const share=Math.round(Math.abs(bal)/3500000*100);
   const tone=n[8]==="revenue"?"post":n[8]==="expenses"?"closed":"run";
   return `<section class="acgroup ${tone}">
    <header class="acg-h">
     <span class="acg-i">${I({assets:"wallet",liabilities:"scale",equity:"landmark",revenue:"trending-up",expenses:"trending-down"}[n[8]]||"folder")}</span>
     <span class="acg-t"><b>${n[0]} · ${t(n[1],n[2])}</b><small>${t("المستوى الأول","Level one")} · ${n[9].length} ${t("مجموعة فرعية","sub-groups")}</small></span>
     <span class="acg-m"><span class="acside ${n[3]}">${t(n[3]==="debtor"?"مدين":"دائن",n[3]==="debtor"?"Debtor":"Creditor")}</span><span class="acg-b">${t("الرصيد","Balance")} ${acMoney(bal)}</span><span class="acg-bar"><i style="width:${Math.min(100,share)}%"></i></span></span>
    </header>
    <div class="acg-body">${n[9].filter(acMatch).map(k=>acNode(k,0)).join("")||`<div class="acempty">${t("لا حسابات فرعية مطابقة","No matching sub-accounts")}</div>`}</div>
   </section>`}).join(""):`<div class="tempty">${I("inbox")}<b>${t("لا حسابات مطابقة","No matching accounts")}</b></div>`}</div>
 </div>`},
 finEntries:()=>{
  const rows=[
   ["JV-2026-0412","2026-08-19",t("قيد إعاشة الإيواء — أغسطس","Shelter living expense — August"),"5101 / 1101",420,420,"posted",t("مشرف الإيواء","Shelter supervisor")],
   ["JV-2026-0411","2026-08-18",t("فاتورة عقد CN-2026-0113","Invoice for CN-2026-0113"),"110301 / 4101",19200,19200,"posted",t("قسم المالية","Finance")],
   ["JV-2026-0410","2026-08-18",t("سداد مستحقات مكتب مانيلا","Payment to Manila office"),"210101 / 110201",64200,64200,"posted",t("قسم المالية","Finance")],
   ["JV-2026-0409","2026-08-17",t("ضريبة القيمة المضافة — فواتير أغسطس","VAT — August invoices"),"2102 / 110301",41260,41260,"draft",t("قسم المالية","Finance")],
   ["JV-2026-0408","2026-08-16",t("تذاكر سفر عاملات","Worker travel tickets"),"5102 / 1101",8400,8400,"posted",t("قسم العمليات","Operations")]];
  return `
 <div class="page-head"><div><h1>${t("القيود اليومية","Journal entries")}</h1><p>${t("القيود المزدوجة — كل قيد متوازن بين المدين والدائن.","Double-entry journal — every entry balances debit and credit.")}</p></div>
 <div class="actions"><button class="btn btn-ghost">${I("file-spreadsheet")}Excel</button><button class="btn btn-ghost" data-go="finReports">${I("folder-open")}${t("التقارير","Reports")}</button><button class="btn btn-primary" data-fiaddent="entry">${I("plus")}${t("إضافة قيد","Add entry")}</button></div></div>
 <div class="pipe">${[["قيود هذا الشهر","Entries this month","book",128,"run"],["مرحّلة","Posted","check-circle-2",119,"post"],["مسودة","Draft","file-pen",6,"run"],["بانتظار الاعتماد","Awaiting approval","clock",3,"closed"]].map(x=>`<div class="pg ${x[4]}"><span class="pg-i">${I(x[2])}</span><span class="pg-b"><span class="pg-t">${t(x[0],x[1])}</span><span class="pg-m"><b class="num">${x[3]}</b></span></span></div>`).join("")}</div>
 <div class="fbar">
  <div class="fb grow"><label>${t("بحث","Search")}</label><div class="inp">${I("search")}<input placeholder="${t("رقم القيد أو البيان أو الحساب","Entry no., description or account")}"></div></div>
  <div class="fb"><label>${t("الحالة","Status")}</label><select><option>${t("الكل","All")}</option><option>${t("مرحّل","Posted")}</option><option>${t("مسودة","Draft")}</option></select></div>
  <div class="fb"><label>${t("من تاريخ","From")}</label><input type="date"></div>
  <div class="fb"><label>${t("إلى تاريخ","To")}</label><input type="date"></div>
 </div>
 <div class="card flush"><div class="tw"><table class="grp"><thead><tr><th>${t("رقم القيد","Entry no.")}</th><th>${t("التاريخ","Date")}</th><th>${t("البيان","Description")}</th><th>${t("الحسابات","Accounts")}</th><th>${t("مدين","Debit")}</th><th>${t("دائن","Credit")}</th><th>${t("الحالة","Status")}</th><th>${t("بواسطة","By")}</th><th class="act">${t("إجراءات","Actions")}</th></tr></thead><tbody>
 ${rows.map(r=>`<tr><td><b class="num">${r[0]}</b></td><td class="num">${r[1]}</td><td style="white-space:normal">${r[2]}</td><td class="num">${r[3]}</td><td class="num">${r[4].toLocaleString("en",{minimumFractionDigits:2})}</td><td class="num">${r[5].toLocaleString("en",{minimumFractionDigits:2})}</td><td><span class="st tag ${r[6]==="posted"?"g":"a"}"><i></i>${r[6]==="posted"?t("مرحّل","Posted"):t("مسودة","Draft")}</span></td><td>${r[7]}</td><td class="act"><div class="rowacts">${fiEye("entry",r[0],"finEntries",[[t("رقم القيد","Entry no."),r[0]],[t("التاريخ","Date"),r[1]],[t("البيان","Description"),r[2]],[t("الحسابات","Accounts"),r[3]],[t("مدين","Debit"),r[4].toLocaleString("en",{minimumFractionDigits:2})],[t("دائن","Credit"),r[5].toLocaleString("en",{minimumFractionDigits:2})],[t("الحالة","Status"),r[6]==="posted"?t("مرحّل","Posted"):t("مسودة","Draft")],[t("بواسطة","By"),r[7]]],r[3])}<button class="icon-btn pri" data-fiaddent="entry">${I("pencil")}</button><button class="icon-btn">${I("printer")}</button></div></td></tr>`).join("")}
 </tbody></table></div><div class="tfoot"><span>${t("عرض 5 من 128 قيداً","Showing 5 of 128 entries")}</span></div></div>`},
 finBonds:()=>{
  const rows=[
   ["RB-2026-0221","qabd","2026-08-19",t("منيرة الشهري","Munira Alshehri"),"110301",9250,t("الصندوق الرئيسي","Main cashbox"),"posted"],
   ["RB-2026-0220","qabd","2026-08-18",t("شركة نجد للمقاولات","Najd Contracting"),"110301",5000,t("بنك الراجحي — 1122","Alrajhi — 1122"),"posted"],
   ["PB-2026-0118","sarf","2026-08-18",t("Manila Prime Agency","Manila Prime Agency"),"210101",64200,t("بنك الراجحي — 1122","Alrajhi — 1122"),"posted"],
   ["PB-2026-0117","sarf","2026-08-17",t("مصروف إعاشة الإيواء","Shelter living expense"),"5101",420,t("الصندوق الرئيسي","Main cashbox"),"posted"],
   ["RB-2026-0219","qabd","2026-08-16",t("عبدالله الدوسري","Abdullah Aldosari"),"110303",7000,t("شبكة (مدى)","Mada network"),"draft"]];
  return `
 <div class="page-head"><div><h1>${t("سندات القبض والصرف","Receipt & payment bonds")}</h1><p>${t("سندات القبض تُقيَّد على الصناديق والبنوك، وسندات الصرف على الموردين والمصروفات.","Receipt bonds post to cashboxes and banks; payment bonds to suppliers and expenses.")}</p></div>
 <div class="actions"><button class="btn btn-ghost">${I("printer")}${t("طباعة","Print")}</button><button class="btn btn-green" data-fiaddent="bond">${I("plus")}${t("سند قبض","Receipt bond")}</button><button class="btn btn-primary" data-fiaddent="bond">${I("plus")}${t("سند صرف","Payment bond")}</button></div></div>
 <div class="pipe">${[["إجمالي القبض — أغسطس","Receipts — August","arrow-down-left","318,400","post"],["إجمالي الصرف — أغسطس","Payments — August","arrow-up-right","204,180","closed"],["الصافي","Net","scale","114,220","run"],["سندات مسودة","Draft bonds","file-pen","4","run"]].map(x=>`<div class="pg ${x[4]}"><span class="pg-i">${I(x[2])}</span><span class="pg-b"><span class="pg-t">${t(x[0],x[1])}</span><span class="pg-m"><b class="num">${x[3]}</b></span></span></div>`).join("")}</div>
 <div class="fbar">
  <div class="fb grow"><label>${t("بحث","Search")}</label><div class="inp">${I("search")}<input placeholder="${t("رقم السند أو الطرف أو الحساب","Bond no., party or account")}"></div></div>
  <div class="fb"><label>${t("النوع","Type")}</label><select><option>${t("الكل","All")}</option><option>${t("سند قبض","Receipt")}</option><option>${t("سند صرف","Payment")}</option></select></div>
  <div class="fb"><label>${t("الصندوق / البنك","Cashbox / bank")}</label><select><option>${t("الكل","All")}</option><option>${t("الصندوق الرئيسي","Main cashbox")}</option><option>${t("بنك الراجحي — 1122","Alrajhi — 1122")}</option><option>${t("بنك الأهلي — 4408","AlAhli — 4408")}</option></select></div>
  <div class="fb"><label>${t("من تاريخ","From")}</label><input type="date"></div>
 </div>
 <div class="card flush"><div class="tw"><table class="grp"><thead><tr><th>${t("رقم السند","Bond no.")}</th><th>${t("النوع","Type")}</th><th>${t("التاريخ","Date")}</th><th>${t("الطرف","Party")}</th><th>${t("الحساب","Account")}</th><th>${t("المبلغ","Amount")}</th><th>${t("مصدر/جهة الصرف","Source")}</th><th>${t("الحالة","Status")}</th><th class="act">${t("إجراءات","Actions")}</th></tr></thead><tbody>
 ${rows.map(r=>`<tr><td><b class="num">${r[0]}</b></td><td>${r[1]==="qabd"?`<span class="tag g">${t("قبض","Receipt")}</span>`:`<span class="tag r">${t("صرف","Payment")}</span>`}</td><td class="num">${r[2]}</td><td>${r[3]}</td><td class="num">${r[4]}</td><td class="num">${r[5].toLocaleString("en",{minimumFractionDigits:2})}</td><td>${r[6]}</td><td><span class="st tag ${r[7]==="posted"?"g":"a"}"><i></i>${r[7]==="posted"?t("مرحّل","Posted"):t("مسودة","Draft")}</span></td><td class="act"><div class="rowacts">${fiEye("bond",r[0],"finBonds",[[t("رقم السند","Bond no."),r[0]],[t("النوع","Type"),r[1]==="qabd"?t("قبض","Receipt"):t("صرف","Payment")],[t("التاريخ","Date"),r[2]],[t("الطرف","Party"),r[3]],[t("الحساب","Account"),r[4]],[t("المبلغ","Amount"),r[5].toLocaleString("en",{minimumFractionDigits:2})],[t("المصدر","Source"),r[6]]],r[3])}<button class="icon-btn pri">${I("printer")}</button><button class="icon-btn">${I("more-horizontal")}</button></div></td></tr>`).join("")}
 </tbody></table></div><div class="tfoot"><span>${t("عرض 5 من 339 سنداً","Showing 5 of 339 bonds")}</span></div></div>`}
};
Object.assign(T,{finAccounts:{ar:"شجرة الحسابات",en:"Chart of accounts"},finEntries:{ar:"القيود اليومية",en:"Journal entries"},finBonds:{ar:"سندات القبض والصرف",en:"Receipt & payment bonds"}});
Object.assign(screens,financeScreens);
document.addEventListener("click",e=>{
 const tg=e.target.closest("[data-actoggle]");if(tg){const k=tg.dataset.actoggle;const cur2=ACOPEN[k];const node=acFlat().find(x=>x.n[0]===k);const def=node?(node.n[8]!=="clients"&&node.n[4]<4):true;ACOPEN[k]=cur2===undefined?!def:!cur2;render();return}
 const sel=e.target.closest("[data-acsel]");if(sel){ACSEL=ACSEL===sel.dataset.acsel?null:sel.dataset.acsel;render();return}
 const ex=e.target.closest("[data-acexpand]");if(ex){acFlat().forEach(x=>{if(x.n[9].length)ACOPEN[x.n[0]]=true});render();return}
 const co=e.target.closest("[data-accollapse]");if(co){acFlat().forEach(x=>{if(x.n[9].length)ACOPEN[x.n[0]]=false});render();return}
 const stm=e.target.closest("[data-acstatement]");if(stm){FRACC=stm.dataset.acstatement;FRID="account_statement";cur="finReport";render();window.scrollTo(0,0);return}
 const pr=e.target.closest("[data-acprint]");if(pr){clToast("جارٍ تحضير كشف الحساب للطباعة","Preparing printed statement");return}
 const ad=e.target.closest("[data-acadd]");if(ad){clToast(ad.dataset.acadd==="root"?"إضافة حساب رئيسي":"إضافة حساب فرعي تحت "+ad.dataset.acadd,"Add account");return}
 const ed2=e.target.closest("[data-acedit]");if(ed2){clToast("تعديل الحساب "+ed2.dataset.acedit,"Edit account");return}
 const dl=e.target.closest("[data-acdel]");if(dl){clToast("لا يمكن حذف حساب عليه قيود","Accounts with entries cannot be deleted");return}
});
document.addEventListener("input",e=>{if(e.target.matches("[data-acq]")){ACQ=e.target.value;const p=e.target.selectionStart;render();const n=document.querySelector("[data-acq]");if(n){n.focus();n.setSelectionRange(p,p)}}});
document.addEventListener("change",e=>{
 if(e.target.matches("[data-aclevelf]")){ACLEVEL=e.target.value;render()}
 if(e.target.matches("[data-acroot]")){ACROOT=e.target.value;render()}
});

/* ===== clickerp-finance-reports.js ===== */
/* Financial reports — catalog + shared report renderer
   grounded in packages/finance/resources/views/finance/* report folders */
const frGroups=[
 ["accounts","تقارير الحسابات","Account reports","list-tree"],
 ["balances","الموازين والقوائم","Trial balance & statements","scale"],
 ["cash","النقدية والبنوك","Cash & banks","landmark"],
 ["cost","مراكز التكلفة","Cost centres","target"],
 ["sales","المبيعات والمخزون","Sales & inventory","shopping-cart"],
 ["closing","الإقفال والأصول","Closing & assets","archive"]];
/* id, group, ar, en, icon, blade folder, filters, columns, rows, totals cols */
const frReports=[
 ["account_statement","accounts","كشف الحساب","Account statement","file-text","account_statement",["account","from","to","type","other"]],
 ["double_account_statement","accounts","كشف حساب مزدوج","Double account statement","files","double_account_statment",["account","account2","from","to"]],
 ["debtor_accounts","accounts","الحسابات المدينة","Debtor accounts","arrow-down-left","debtor_accounts",["from","to","level"]],
 ["creditor_accounts","accounts","الحسابات الدائنة","Creditor accounts","arrow-up-right","creditor_accounts",["from","to","level"]],
 ["accounts_debtor_report","accounts","تقرير أرصدة العملاء المدينة","Client debit balances","users","accounts_debtor_report",["from","to"]],
 ["accounts_creditor_report","accounts","تقرير أرصدة الموردين الدائنة","Supplier credit balances","truck","accounts_creditor_report",["from","to"]],
 ["balances","balances","ميزان المراجعة","Trial balance","scale","balances",["from","to","level"]],
 ["audit_budget","balances","الميزانية العمومية","Balance sheet","building-2","audit_budget",["to","level"]],
 ["audit_budget_monthly","balances","الميزانية الشهرية","Monthly balance sheet","calendar-range","audit_budget_monthly",["year"]],
 ["income_list","balances","قائمة الدخل","Income statement","trending-up","income_list",["from","to"]],
 ["account_balance_differed_normal","balances","أرصدة الحسابات المخالفة لطبيعتها","Accounts against their nature","alert-triangle","account_balance_differed_normal",["to","level"]],
 ["bank_account_statement","cash","كشف الحساب البنكي","Bank account statement","landmark","bank_account_statement",["bank","from","to"]],
 ["cash_movements","cash","حركة النقدية","Cash movements","banknote","account_statement/cash_movements",["box","from","to"]],
 ["catch_checks","cash","الشيكات الواردة","Received cheques","receipt","catch-checks",["from","to","status"]],
 ["exchange_checks","cash","الشيكات الصادرة","Issued cheques","receipt","exchange-checks",["from","to","status"]],
 ["cost_centers","cost","تقرير مراكز التكلفة","Cost centres report","target","cost_centers",["center","from","to"]],
 ["account_cost_centers","cost","الحسابات حسب مركز التكلفة","Accounts by cost centre","git-merge","account_cost_centers",["center","from","to"]],
 ["bill_sales_report","sales","تقرير فواتير البيع","Sales invoices report","file-spreadsheet","bill_sales_report",["from","to","client"]],
 ["item_sale_movement","sales","حركة بيع الأصناف","Item sales movement","arrow-left-right","item_sale_movement",["item","from","to"]],
 ["item_purchase_movement","sales","حركة شراء الأصناف","Item purchase movement","arrow-right-left","item_purchase_movement",["item","from","to"]],
 ["item_total_sale","sales","إجمالي مبيعات الأصناف","Item total sales","bar-chart-3","item_total_sale",["from","to"]],
 ["group_depreciation","closing","إهلاك مجموعات الأصول","Asset group depreciation","trending-down","group_depreciation",["year"]],
 ["fiscal_year_closing","closing","إقفال السنة المالية","Fiscal year closing","archive","fiscal_year_closing",["year"]]];
let FRQ="",FRGROUP="",FRID="account_statement",FRACC="110301",FRFROM="2026-08-01",FRTO="2026-08-19",FRTYPE="both",FROTHER="yes";
const frLeafAccounts=()=>acFlat().filter(x=>!x.n[9].length).map(x=>x.n);
const frMoney=(v,d=2)=>`<span class="num">${(v<0?"-":"")+Math.abs(v).toLocaleString("en",{minimumFractionDigits:d,maximumFractionDigits:d})}</span>`;
const frFilter=(k)=>{
 const box=(lab,ctl,grow)=>`<div class="fb${grow?" grow":""}"><label>${lab}</label>${ctl}</div>`;
 switch(k){
  case "account":return box(t("الحساب","Account"),`<select data-fracc>${frLeafAccounts().map(n=>`<option value="${n[0]}"${FRACC===n[0]?" selected":""}>${n[0]} / ${t(n[1],n[2])}</option>`).join("")}</select>`,1);
  case "account2":return box(t("الحساب المقابل","Counter account"),`<select>${frLeafAccounts().map(n=>`<option>${n[0]} / ${t(n[1],n[2])}</option>`).join("")}</select>`,1);
  case "from":return box(t("من تاريخ","From date"),`<input type="date" data-frfrom value="${FRFROM}">`);
  case "to":return box(t("إلى تاريخ","To date"),`<input type="date" data-frto value="${FRTO}">`);
  case "year":return box(t("السنة المالية","Fiscal year"),`<select><option>2026</option><option>2025</option></select>`);
  case "level":return box(t("المستوى","Level"),`<select><option>${t("كل المستويات","All levels")}</option>${[1,2,3,4].map(l=>`<option>${t("المستوى "+l,"Level "+l)}</option>`).join("")}</select>`);
  case "type":return box(t("النوع","Type"),`<select data-frtype><option value="both"${FRTYPE==="both"?" selected":""}>${t("مدين ودائن","Debit & credit")}</option><option value="debtor"${FRTYPE==="debtor"?" selected":""}>${t("مدين فقط","Debit only")}</option><option value="creditor"${FRTYPE==="creditor"?" selected":""}>${t("دائن فقط","Credit only")}</option></select>`);
  case "other":return box(t("إظهار الحساب المقابل","Show counter account"),`<select data-frother><option value="yes"${FROTHER==="yes"?" selected":""}>${t("نعم","Yes")}</option><option value="no"${FROTHER==="no"?" selected":""}>${t("لا","No")}</option></select>`);
  case "bank":return box(t("الحساب البنكي","Bank account"),`<select><option>${t("بنك الراجحي — 1122","Alrajhi — 1122")}</option><option>${t("بنك الأهلي — 4408","AlAhli — 4408")}</option></select>`,1);
  case "box":return box(t("الصندوق","Cashbox"),`<select><option>${t("الصندوق الرئيسي","Main cashbox")}</option><option>${t("صندوق فرع جدة","Jeddah branch box")}</option></select>`,1);
  case "center":return box(t("مركز التكلفة","Cost centre"),`<select><option>${t("كل المراكز","All centres")}</option><option>${t("الاستقدام","Recruitment")}</option><option>${t("التأجير","Rental")}</option><option>${t("الإيواء","Shelter")}</option><option>${t("الإدارة","Administration")}</option></select>`,1);
  case "client":return box(t("العميل","Client"),`<select><option>${t("كل العملاء","All clients")}</option><option>${t("منيرة الشهري","Munira Alshehri")}</option><option>${t("شركة نجد للمقاولات","Najd Contracting")}</option></select>`,1);
  case "item":return box(t("الصنف","Item"),`<select><option>${t("كل الأصناف","All items")}</option><option>${t("مستلزمات إيواء","Shelter supplies")}</option><option>${t("قرطاسية","Stationery")}</option></select>`,1);
  case "status":return box(t("الحالة","Status"),`<select><option>${t("الكل","All")}</option><option>${t("محصّل","Collected")}</option><option>${t("مرفوض","Refused")}</option><option>${t("قيد التحصيل","Pending")}</option></select>`);
  default:return ""}};
/* report bodies */
const frBody={
 account_statement:()=>{
  const acc=frLeafAccounts().find(n=>n[0]===FRACC)||frLeafAccounts()[0];
  const rows=[["2026-08-01",t("رصيد افتتاحي","Opening balance"),"—",0,0,148200],["2026-08-03",t("فاتورة عقد CN-2026-0113","Invoice CN-2026-0113"),"4101",19200,0,167400],["2026-08-06",t("سند قبض RB-2026-0219","Receipt RB-2026-0219"),"1101",0,7000,160400],["2026-08-11",t("فاتورة عقد CN-2026-0115","Invoice CN-2026-0115"),"4101",18500,0,178900],["2026-08-14",t("سند قبض RB-2026-0220","Receipt RB-2026-0220"),"110201",0,5000,173900],["2026-08-18",t("إشعار خصم","Discount note"),"4102",0,500,173400],["2026-08-19",t("سند قبض RB-2026-0221","Receipt RB-2026-0221"),"1101",0,9250,164150]];
  const d=rows.reduce((a,r)=>a+r[3],0),c=rows.reduce((a,r)=>a+r[4],0);
  return `${frCard(acc)}
  <div class="card flush"><div class="tw"><table class="grp rep"><thead><tr><th>${t("التاريخ","Date")}</th><th>${t("البيان","Description")}</th>${FROTHER==="yes"?`<th>${t("الحساب المقابل","Counter account")}</th>`:""}<th>${t("مدين","Debit")}</th><th>${t("دائن","Credit")}</th><th>${t("الرصيد","Balance")}</th></tr></thead><tbody>
   ${rows.filter(r=>FRTYPE==="both"||(FRTYPE==="debtor"?r[3]:r[4])||r[1].includes("افتتاحي")).map(r=>`<tr><td class="num">${r[0]}</td><td style="white-space:normal">${r[1]}</td>${FROTHER==="yes"?`<td class="num">${r[2]}</td>`:""}<td>${r[3]?frMoney(r[3]):`<span class="empty">—</span>`}</td><td>${r[4]?frMoney(r[4]):`<span class="empty">—</span>`}</td><td><b>${frMoney(r[5])}</b></td></tr>`).join("")}
  </tbody><tfoot><tr><th colspan="${FROTHER==="yes"?3:2}">${t("الإجمالي","Total")}</th><th>${frMoney(d)}</th><th>${frMoney(c)}</th><th>${frMoney(rows[rows.length-1][5])}</th></tr></tfoot></table></div></div>`},
 balances:()=>{
  const rows=acFlat().filter(x=>x.n[4]<=3).map(x=>{const [d,c]=acSums(x.n);return [x.n[0],t(x.n[1],x.n[2]),x.n[4],d,c,x.n[3]==="debtor"?d-c:c-d,x.n[3]]}).filter(r=>r[3]||r[4]);
  const td=rows.filter(r=>r[2]===1).reduce((a,r)=>a+r[3],0),tc=rows.filter(r=>r[2]===1).reduce((a,r)=>a+r[4],0);
  return `<div class="card flush"><div class="card-h"><div><h3>${t("ميزان المراجعة","Trial balance")}</h3><p>${t("الأرصدة حتى المستوى الثالث — المدين يساوي الدائن عند التوازن.","Balances to level three — debit equals credit when balanced.")}</p></div>${td===tc?`<span class="tag g">${I("check")}${t("الميزان متوازن","Balanced")}</span>`:`<span class="tag r">${t("فرق","Difference")} ${frMoney(td-tc)}</span>`}</div>
  <div class="tw"><table class="grp rep"><thead><tr><th>${t("الكود","Code")}</th><th>${t("الحساب","Account")}</th><th>${t("المستوى","Level")}</th><th>${t("مدين","Debit")}</th><th>${t("دائن","Credit")}</th><th>${t("الرصيد","Balance")}</th><th>${t("الطبيعة","Nature")}</th></tr></thead><tbody>
  ${rows.map(r=>`<tr class="lvl-${r[2]}"><td class="num">${r[0]}</td><td><b>${r[1]}</b></td><td class="num">${r[2]}</td><td>${frMoney(r[3])}</td><td>${frMoney(r[4])}</td><td><b class="${r[5]<0?"due":""}">${frMoney(r[5])}</b></td><td><span class="acside ${r[6]}">${t(r[6]==="debtor"?"مدين":"دائن",r[6]==="debtor"?"Debtor":"Creditor")}</span></td></tr>`).join("")}
  </tbody><tfoot><tr><th colspan="3">${t("إجمالي المستوى الأول","Level-one total")}</th><th>${frMoney(td)}</th><th>${frMoney(tc)}</th><th colspan="2">${td===tc?t("متوازن","Balanced"):frMoney(td-tc)}</th></tr></tfoot></table></div></div>`},
 income_list:()=>{
  const rev=[["4101",t("عقود الاستقدام","Recruitment contracts"),2106600],["4102",t("عقود التأجير","Rental contracts"),665800],["4103",t("نقل الكفالة","Sponsorship transfer"),208200],["42",t("إيرادات أخرى","Other revenue"),48200]];
  const exp=[["5101",t("مصروف إعاشة الإيواء","Shelter living expense"),272200],["5102",t("تذاكر وسفر","Tickets & travel"),394200],["5103",t("تأشيرات ورسوم","Visas & fees"),659400],["5104",t("فحوصات طبية","Medical exams"),135800],["5201",t("رواتب وأجور","Salaries & wages"),942000],["5202",t("إيجارات","Rents"),384000],["5203",t("اتصالات وأنظمة","Telecom & systems"),92200]];
  const tr=rev.reduce((a,r)=>a+r[2],0),te=exp.reduce((a,r)=>a+r[2],0),net=tr-te;
  return `<div class="pipe">${[["إجمالي الإيرادات","Total revenue","trending-up",tr,"post"],["إجمالي المصروفات","Total expenses","trending-down",te,"closed"],[net>=0?"صافي الربح":"صافي الخسارة",net>=0?"Net profit":"Net loss","scale",net,net>=0?"post":"closed"],["هامش الصافي","Net margin","percent",Math.round(net/tr*100)+"%","run"]].map(x=>`<div class="pg ${x[4]}"><span class="pg-i">${I(x[2])}</span><span class="pg-b"><span class="pg-t">${t(x[0],x[1])}</span><span class="pg-m"><b class="num">${typeof x[3]==="number"?x[3].toLocaleString("en"):x[3]}</b></span></span></div>`).join("")}</div>
  <div class="grid-2">
   <div class="card flush"><div class="card-h"><div><h3>${t("الإيرادات","Revenue")}</h3></div></div><div class="tw"><table class="rep"><thead><tr><th>${t("الكود","Code")}</th><th>${t("البند","Item")}</th><th>${t("القيمة","Amount")}</th><th>%</th></tr></thead><tbody>${rev.map(r=>`<tr><td class="num">${r[0]}</td><td>${r[1]}</td><td>${frMoney(r[2],0)}</td><td class="num">${Math.round(r[2]/tr*100)}%</td></tr>`).join("")}</tbody><tfoot><tr><th colspan="2">${t("الإجمالي","Total")}</th><th>${frMoney(tr,0)}</th><th>100%</th></tr></tfoot></table></div></div>
   <div class="card flush"><div class="card-h"><div><h3>${t("المصروفات","Expenses")}</h3></div></div><div class="tw"><table class="rep"><thead><tr><th>${t("الكود","Code")}</th><th>${t("البند","Item")}</th><th>${t("القيمة","Amount")}</th><th>%</th></tr></thead><tbody>${exp.map(r=>`<tr><td class="num">${r[0]}</td><td>${r[1]}</td><td>${frMoney(r[2],0)}</td><td class="num">${Math.round(r[2]/te*100)}%</td></tr>`).join("")}</tbody><tfoot><tr><th colspan="2">${t("الإجمالي","Total")}</th><th>${frMoney(te,0)}</th><th>100%</th></tr></tfoot></table></div></div>
  </div>
  <div class="card netcard ${net>=0?"pos":"neg"}"><div><b>${t(net>=0?"صافي الربح للفترة":"صافي الخسارة للفترة",net>=0?"Net profit for the period":"Net loss for the period")}</b><small>${FRFROM} → ${FRTO}</small></div><span class="netval">${frMoney(net,0)} <small>${t("ر.س","SAR")}</small></span></div>`},
 debtor_accounts:()=>frSimple([["الكود","Code"],["الحساب","Account"],["مدين","Debit"],["دائن","Credit"],["الرصيد المدين","Debit balance"]],acFlat().filter(x=>!x.n[9].length&&x.n[3]==="debtor").map(x=>{const [d,c]=acSums(x.n);return [x.n[0],t(x.n[1],x.n[2]),frMoney(d),frMoney(c),`<b>${frMoney(d-c)}</b>`]}),4),
 creditor_accounts:()=>frSimple([["الكود","Code"],["الحساب","Account"],["مدين","Debit"],["دائن","Credit"],["الرصيد الدائن","Credit balance"]],acFlat().filter(x=>!x.n[9].length&&x.n[3]==="creditor").map(x=>{const [d,c]=acSums(x.n);return [x.n[0],t(x.n[1],x.n[2]),frMoney(d),frMoney(c),`<b>${frMoney(c-d)}</b>`]}),4),
 accounts_debtor_report:()=>frSimple([["العميل","Client"],["رقم الهوية","ID"],["إجمالي العقود","Contracts value"],["المحصّل","Collected"],["الرصيد المدين","Debit balance"]],[["منيرة الشهري","1042887331",18500,9250,9250],["شركة نجد للمقاولات","3001188220",17900,5000,12900],["شركة الوفاء الطبية","3009988112",21500,7000,14500],["فيصل القحطاني","1033441200",18500,3000,15500]].map(r=>[r[0],`<span class="num">${r[1]}</span>`,frMoney(r[2],0),frMoney(r[3],0),`<b>${frMoney(r[4],0)}</b>`]),4),
 accounts_creditor_report:()=>frSimple([["المورد / المكتب","Supplier / office"],["الدولة","Country"],["مدين","Debit"],["دائن","Credit"],["الرصيد الدائن","Credit balance"]],[["Manila Prime Agency","الفلبين",318400,642800,324400],["Jakarta Care Agency","إندونيسيا",204100,486200,282100],["Colombo Bridge Agency","سريلانكا",142900,331500,188600]].map(r=>[r[0],r[1],frMoney(r[2],0),frMoney(r[3],0),`<b>${frMoney(r[4],0)}</b>`]),4),
 bank_account_statement:()=>frSimple([["التاريخ","Date"],["البيان","Description"],["مرجع","Reference"],["إيداع","Deposit"],["سحب","Withdrawal"],["الرصيد","Balance"]],[["2026-08-01","رصيد افتتاحي","—",0,0,284200],["2026-08-03","تحصيل عقد CN-2026-0113","RB-2026-0218",19200,0,303400],["2026-08-11","سداد مكتب مانيلا","PB-2026-0118",0,64200,239200],["2026-08-14","تحصيل شركة نجد","RB-2026-0220",5000,0,244200],["2026-08-18","رسوم بنكية","BK-0912",0,180,244020]].map(r=>[`<span class="num">${r[0]}</span>`,r[1],`<span class="num">${r[2]}</span>`,r[3]?frMoney(r[3]):`<span class="empty">—</span>`,r[4]?frMoney(r[4]):`<span class="empty">—</span>`,`<b>${frMoney(r[5])}</b>`]),5),
 cash_movements:()=>frSimple([["التاريخ","Date"],["الحركة","Movement"],["السند","Bond"],["قبض","In"],["صرف","Out"],["الرصيد","Balance"]],[["2026-08-17","إعاشة إيواء","PB-2026-0117",0,420,231280],["2026-08-19","تحصيل منيرة الشهري","RB-2026-0221",9250,0,240530]].map(r=>[`<span class="num">${r[0]}</span>`,r[1],`<span class="num">${r[2]}</span>`,r[3]?frMoney(r[3]):`<span class="empty">—</span>`,r[4]?frMoney(r[4]):`<span class="empty">—</span>`,`<b>${frMoney(r[5])}</b>`]),5),
 catch_checks:()=>frSimple([["رقم الشيك","Cheque no."],["البنك","Bank"],["العميل","Client"],["تاريخ الاستحقاق","Due date"],["المبلغ","Amount"],["الحالة","Status"]],[["4412201","الراجحي","منيرة الشهري","2026-09-01",9250,"pending"],["8820114","الأهلي","شركة نجد","2026-09-10",12900,"collected"],["6612008","الإنماء","فيصل القحطاني","2026-08-14",15500,"refused"]].map(r=>[`<span class="num">${r[0]}</span>`,r[1],r[2],`<span class="num">${r[3]}</span>`,frMoney(r[4],0),r[5]==="collected"?`<span class="st tag g"><i></i>${t("محصّل","Collected")}</span>`:r[5]==="refused"?`<span class="st tag r"><i></i>${t("مرفوض","Refused")}</span>`:`<span class="st tag a"><i></i>${t("قيد التحصيل","Pending")}</span>`]),4),
 exchange_checks:()=>frSimple([["رقم الشيك","Cheque no."],["البنك","Bank"],["الجهة","Payee"],["تاريخ الاستحقاق","Due date"],["المبلغ","Amount"],["الحالة","Status"]],[["1120884","الراجحي","Manila Prime Agency","2026-09-05",64200,"pending"],["1120883","الراجحي","Jakarta Care Agency","2026-08-12",48600,"collected"]].map(r=>[`<span class="num">${r[0]}</span>`,r[1],r[2],`<span class="num">${r[3]}</span>`,frMoney(r[4],0),r[5]==="collected"?`<span class="st tag g"><i></i>${t("مصروف","Cleared")}</span>`:`<span class="st tag a"><i></i>${t("قيد الصرف","Pending")}</span>`]),4),
 cost_centers:()=>frSimple([["مركز التكلفة","Cost centre"],["الإيرادات","Revenue"],["المصروفات","Expenses"],["الصافي","Net"],["نسبة المصروف","Expense share"]],[["الاستقدام",2106600,1284600,822000,42],["التأجير",665800,318400,347400,11],["الإيواء",0,672200,-672200,22],["الإدارة",48200,804800,-756600,25]].map(r=>[`<b>${r[0]}</b>`,frMoney(r[1],0),frMoney(r[2],0),`<b class="${r[3]<0?"due":"ok"}">${frMoney(r[3],0)}</b>`,`<div class="mrate"><span class="track"><i style="width:${r[4]}%"></i></span><b class="num">${r[4]}%</b></div>`]),3),
 account_cost_centers:()=>frSimple([["الحساب","Account"],["مركز التكلفة","Cost centre"],["مدين","Debit"],["دائن","Credit"],["الصافي","Net"]],[["5101 / مصروف إعاشة الإيواء","الإيواء",284600,12400,272200],["5102 / تذاكر وسفر","الاستقدام",412800,18600,394200],["5201 / رواتب وأجور","الإدارة",1284000,42000,1242000]].map(r=>[r[0],r[1],frMoney(r[2],0),frMoney(r[3],0),`<b>${frMoney(r[4],0)}</b>`]),4),
 bill_sales_report:()=>frSimple([["رقم الفاتورة","Invoice no."],["التاريخ","Date"],["العميل","Client"],["الإجمالي","Total"],["الضريبة","VAT"],["المدفوع","Paid"],["المتبقي","Remaining"]],[["INV-9921","2026-06-30","منيرة الشهري",18500,2775,9250,9250],["INV-9920","2026-08-02","شركة نجد",17900,2685,5000,12900],["INV-9919","2026-05-18","نورة الحربي",19200,2880,19200,0]].map(r=>[`<b class="num">${r[0]}</b>`,`<span class="num">${r[1]}</span>`,r[2],frMoney(r[3],0),frMoney(r[4],0),frMoney(r[5],0),r[6]?`<b class="due">${frMoney(r[6],0)}</b>`:`<span class="ok">${t("مسدّد","Settled")}</span>`]),3),
 item_sale_movement:()=>frSimple([["الصنف","Item"],["التاريخ","Date"],["الكمية","Qty"],["السعر","Price"],["الإجمالي","Total"]],[["مستلزمات إيواء","2026-08-12",40,18,720],["قرطاسية","2026-08-14",120,6,720]].map(r=>[r[0],`<span class="num">${r[1]}</span>`,`<span class="num">${r[2]}</span>`,frMoney(r[3]),frMoney(r[4])]),4),
 item_purchase_movement:()=>frSimple([["الصنف","Item"],["المورد","Supplier"],["التاريخ","Date"],["الكمية","Qty"],["الإجمالي","Total"]],[["مستلزمات إيواء","مؤسسة الرياض","2026-08-05",200,3400],["قرطاسية","مكتبة جرير","2026-08-09",500,2800]].map(r=>[r[0],r[1],`<span class="num">${r[2]}</span>`,`<span class="num">${r[3]}</span>`,frMoney(r[4])]),4),
 item_total_sale:()=>frSimple([["الصنف","Item"],["عدد الحركات","Movements"],["إجمالي الكمية","Total qty"],["إجمالي القيمة","Total value"]],[["مستلزمات إيواء",14,480,8640],["قرطاسية",9,620,3720]].map(r=>[r[0],`<span class="num">${r[1]}</span>`,`<span class="num">${r[2]}</span>`,frMoney(r[3])]),3),
 group_depreciation:()=>frSimple([["مجموعة الأصول","Asset group"],["التكلفة","Cost"],["نسبة الإهلاك","Rate"],["إهلاك السنة","Year depreciation"],["القيمة الدفترية","Book value"]],[["مباني ومقار الإيواء",2400000,"5%",120000,2100000],["سيارات النقل",860000,"20%",172000,586000],["أجهزة وأثاث",318000,"25%",79500,174500]].map(r=>[r[0],frMoney(r[1],0),`<span class="num">${r[2]}</span>`,frMoney(r[3],0),`<b>${frMoney(r[4],0)}</b>`]),3),
 audit_budget:()=>frSimple([["البند","Item"],["المستوى","Level"],["الرصيد","Balance"],["النسبة من الإجمالي","Share"]],acTree.map(n=>{const b=acBalance(n);return [`<b>${n[0]} / ${t(n[1],n[2])}</b>`,`<span class="num">${n[4]}</span>`,frMoney(b,0),`<div class="mrate"><span class="track"><i style="width:${Math.min(100,Math.round(Math.abs(b)/3500000*100))}%"></i></span></div>`]}),2),
 audit_budget_monthly:()=>frSimple([["الشهر","Month"],["الإيرادات","Revenue"],["المصروفات","Expenses"],["الصافي","Net"]],[["يناير",214800,182400,32400],["فبراير",238600,196200,42400],["مارس",261400,204800,56600],["أبريل",249200,212600,36600],["مايو",284600,228400,56200],["يونيو",312400,241800,70600],["يوليو",348200,264200,84000],["أغسطس",412600,286800,125800]].map(r=>[r[0],frMoney(r[1],0),frMoney(r[2],0),`<b class="ok">${frMoney(r[3],0)}</b>`]),3),
 account_balance_differed_normal:()=>frSimple([["الكود","Code"],["الحساب","Account"],["الطبيعة","Nature"],["الرصيد","Balance"],["ملاحظة","Note"]],[["1104","مصروفات مقدمة","debtor",-4200],["2103","رواتب مستحقة","creditor",-1800]].map(r=>[`<span class="num">${r[0]}</span>`,`<b>${r[1]}</b>`,`<span class="acside ${r[2]}">${t(r[2]==="debtor"?"مدين":"دائن",r[2]==="debtor"?"Debtor":"Creditor")}</span>`,`<b class="due">${frMoney(r[3])}</b>`,`<span class="tag r">${t("مخالف لطبيعة الحساب","Against account nature")}</span>`]),3),
 fiscal_year_closing:()=>`<div class="card sec"><div class="sec-h">${I("archive")}<b>${t("إقفال السنة المالية 2026","Fiscal year closing 2026")}</b></div>
  <div class="depsteps">${[["مراجعة القيود المعلّقة","Review pending entries",1],["ترصيد الحسابات المؤقتة","Close temporary accounts",1],["ترحيل الأرباح المبقاة","Post retained earnings",0],["إقفال السنة وفتح 2027","Close year and open 2027",0]].map((s,i)=>`<div class="dstep ${s[2]?"done":i===2?"current":""}"><span class="ds-n">${s[2]?I("check"):`<b class="num">${i+1}</b>`}</span><div><b>${t(s[0],s[1])}</b><small>${s[2]?t("مكتمل","Completed"):i===2?t("قيد التنفيذ","In progress"):t("لم يبدأ","Not started")}</small></div>${i<3?'<span class="dl"></span>':""}</div>`).join("")}</div>
  <div class="formbar"><button class="btn btn-ghost">${I("printer")}${t("طباعة تقرير الإقفال","Print closing report")}</button><button class="btn btn-primary">${I("arrow-left")}${t("تنفيذ الخطوة التالية","Run next step")}</button></div></div>`,
 double_account_statement:()=>frSimple([["التاريخ","Date"],["البيان","Description"],["الحساب الأول","First account"],["الحساب المقابل","Counter account"],["المبلغ","Amount"]],[["2026-08-03","فاتورة عقد CN-2026-0113","110301","4101",19200],["2026-08-11","سداد مكتب مانيلا","210101","110201",64200]].map(r=>[`<span class="num">${r[0]}</span>`,r[1],`<span class="num">${r[2]}</span>`,`<span class="num">${r[3]}</span>`,frMoney(r[4])]),4)
};
function frSimple(cols,rows,totalCol){
 const nums=rows.map(r=>r[totalCol]).join("");void nums;
 return `<div class="card flush"><div class="tw"><table class="grp rep"><thead><tr>${cols.map(c=>`<th>${t(c[0],c[1])}</th>`).join("")}</tr></thead><tbody>
 ${rows.length?rows.map(r=>`<tr>${r.map(c=>`<td>${c}</td>`).join("")}</tr>`).join(""):`<tr><td colspan="${cols.length}"><div class="tempty">${I("inbox")}<b>${t("لا بيانات في هذه الفترة","No data for this period")}</b></div></td></tr>`}
 </tbody></table></div><div class="tfoot"><span>${t(`${rows.length} سجل`,`${rows.length} records`)}</span></div></div>`}
function frCard(acc){const [d,c]=acSums(acc),bal=acBalance(acc);
 return `<div class="pipe">${[[t("الحساب","Account"),acc[0]+" / "+t(acc[1],acc[2]),"file-text","run"],[t("إجمالي المدين","Total debit"),d.toLocaleString("en"),"arrow-down-left","run"],[t("إجمالي الدائن","Total credit"),c.toLocaleString("en"),"arrow-up-right","run"],[t("الرصيد","Balance"),bal.toLocaleString("en"),"scale",bal>=0?"post":"closed"]].map(x=>`<div class="pg ${x[3]}"><span class="pg-i">${I(x[2])}</span><span class="pg-b"><span class="pg-t">${x[0]}</span><span class="pg-m"><b class="${typeof x[1]==="string"&&x[1].includes("/")?"":"num"}" style="font-size:${String(x[1]).length>16?"14px":"20px"}">${x[1]}</b></span></span></div>`).join("")}</div>`}
const reportScreens={
 finReports:()=>{
  const q=FRQ.trim().toLowerCase();
  const list=frReports.filter(r=>(!FRGROUP||r[1]===FRGROUP)&&(!q||(r[2]+r[3]+r[0]).toLowerCase().includes(q)));
  return `
 <div class="page-head"><div><h1>${t("التقارير المالية","Financial reports")}</h1><p>${t("كل تقارير الوحدة المالية مجمّعة — الحسابات، الموازين، النقدية، مراكز التكلفة، المبيعات، والإقفال.","Every finance report in one place — accounts, balances, cash, cost centres, sales and closing.")}</p></div>
 <div class="actions"><button class="btn btn-ghost" data-go="finAccounts">${I("list-tree")}${t("شجرة الحسابات","Chart of accounts")}</button><button class="btn btn-ghost">${I("printer")}${t("طباعة القائمة","Print list")}</button></div></div>
 <div class="fbar">
  <div class="fb grow"><label>${t("بحث في التقارير","Search reports")}</label><div class="inp">${I("search")}<input data-frq value="${FRQ}" placeholder="${t("اسم التقرير","Report name")}"></div></div>
  <div class="fb"><label>${t("المجموعة","Group")}</label><select data-frgroup><option value="">${t("كل المجموعات","All groups")}</option>${frGroups.map(g=>`<option value="${g[0]}"${FRGROUP===g[0]?" selected":""}>${t(g[1],g[2])}</option>`).join("")}</select></div>
  <span class="empty">${t(`${list.length} تقريراً`,`${list.length} reports`)}</span>
 </div>
 ${frGroups.filter(g=>list.some(r=>r[1]===g[0])).map(g=>`
 <div class="card sec"><div class="sec-h">${I(g[3])}<b>${t(g[1],g[2])}</b><span class="ms">${t(list.filter(r=>r[1]===g[0]).length+" تقرير",list.filter(r=>r[1]===g[0]).length+" reports")}</span></div>
  <div class="repgrid">${list.filter(r=>r[1]===g[0]).map(r=>`<button class="repcard" data-frrep="${r[0]}"><span class="rc-i">${I(r[4])}</span><span class="rc-b"><b>${t(r[2],r[3])}</b><small>${r[6].length} ${t("فلاتر","filters")} · <code>${r[5]}</code></small></span>${I("chevron-left","rc-go")}</button>`).join("")}</div></div>`).join("")}`},
 finReport:()=>{
  const r=frReports.find(x=>x[0]===FRID)||frReports[0];
  const g=frGroups.find(x=>x[0]===r[1]);
  return `
 <div class="page-head compact"><div><a class="back" href="#" data-go="finReports" style="margin:0 0 8px">${I("arrow-right")}<span>${t("التقارير المالية","Financial reports")}</span></a><h1>${t(r[2],r[3])}</h1><p>${t(g[1],g[2])} · ${FRFROM} → ${FRTO}</p></div>
 <div class="actions"><button class="btn btn-ghost">${I("file-spreadsheet")}Excel</button><button class="btn btn-ghost">${I("file-text")}PDF</button><button class="btn btn-primary">${I("printer")}${t("طباعة","Print")}</button></div></div>
 <div class="fbar">${r[6].map(frFilter).join("")}<button class="btn btn-primary" data-frrun>${I("search")}${t("عرض التقرير","Run report")}</button></div>
 ${(frBody[r[0]]||(()=>frSimple([["البند","Item"],["القيمة","Value"]],[],1)))()}
 <p class="pnote">${I("info")}${t("مصدر الشاشة في النظام: ","System source: ")}<code>finance/${r[5]}</code></p>`}
};
Object.assign(T,{finReports:{ar:"التقارير المالية",en:"Financial reports"},finReport:{ar:"تقرير مالي",en:"Financial report"}});
Object.assign(screens,reportScreens);
(function(){const g=nav.find(x=>x.t.ar==="الإدارة");if(!g)return;const f=g.items.find(x=>x.id==="finance");if(f)f.kids=[
 {id:"finAccounts",ar:"شجرة الحسابات",en:"Chart of accounts"},
 {id:"finEntries",ar:"القيود اليومية",en:"Journal entries"},
 {id:"finBonds",ar:"سندات القبض والصرف",en:"Receipt & payment bonds"},
 {id:"finReports",ar:"التقارير المالية",en:"Financial reports"}];})();
document.addEventListener("click",e=>{
 const rp=e.target.closest("[data-frrep]");if(rp){FRID=rp.dataset.frrep;cur="finReport";render();window.scrollTo(0,0);return}
 if(e.target.closest("[data-frrun]")){clToast("تم تحديث التقرير","Report refreshed");return}
});
document.addEventListener("input",e=>{if(e.target.matches("[data-frq]")){FRQ=e.target.value;const p=e.target.selectionStart;render();const n=document.querySelector("[data-frq]");if(n){n.focus();n.setSelectionRange(p,p)}}});
document.addEventListener("change",e=>{
 if(e.target.matches("[data-frgroup]")){FRGROUP=e.target.value;render()}
 if(e.target.matches("[data-fracc]")){FRACC=e.target.value;render()}
 if(e.target.matches("[data-frfrom]")){FRFROM=e.target.value;render()}
 if(e.target.matches("[data-frto]")){FRTO=e.target.value;render()}
 if(e.target.matches("[data-frtype]")){FRTYPE=e.target.value;render()}
 if(e.target.matches("[data-frother]")){FROTHER=e.target.value;render()}
});

/* ===== clickerp-finance-ops.js ===== */
/* Finance ops — sales & purchase invoices, cash/banks/cheques, items, clients/suppliers, cost centres, settings
   grounded in packages/finance/resources/views/finance/{bill_sales,bill_purchases,back_purchases,banks,bank_accounts,boxes,
   account_transfer,catch-checks,exchange-checks,items,first_stocks,inventories,item_collects,clients,external_agents,
   cost_centers,cost_center_management,cost_centers_notifications,accounting_management,confirm_settings,zatca} */
const fiVat=15;
/* sales invoices: no, date, client, clientVat, lines[], discount, paid, status, zatca */
const fiSales=[
 ["INV-9921","2026-06-30","منيرة الشهري","—",[[t("عقد استقدام عاملة منزلية — CN-2026-0115","Recruitment contract CN-2026-0115"),"SRV-01",18500,1,0]],0,9250,"partial","cleared"],
 ["INV-9920","2026-08-02","شركة نجد للمقاولات","3001188220",[[t("عقد استقدام — CN-2026-0114","Recruitment contract CN-2026-0114"),"SRV-01",17900,1,500]],500,5000,"partial","cleared"],
 ["INV-9919","2026-05-18","نورة الحربي","—",[[t("عقد استقدام — CN-2026-0113","Recruitment contract CN-2026-0113"),"SRV-01",19200,1,0]],0,19200,"settled","cleared"],
 ["INV-9918","2026-08-11","شركة الوفاء الطبية","3009988112",[[t("عقد تأجير سائق — RC-2026-0044","Driver rental contract RC-2026-0044"),"SRV-02",1800,12,0]],0,7000,"partial","pending"],
 ["INV-9917","2026-08-19","عبدالله الدوسري","—",[[t("نقل كفالة عاملة منزلية","Sponsorship transfer"),"SRV-03",12500,1,0]],0,12500,"settled","cleared"],
 ["INV-9916","2026-08-20","مؤسسة البيان التجارية","3004411882",[[t("عقد استقدام سائق","Driver recruitment"),"SRV-01",23400,1,1000],[t("رسوم نقل داخلي","Internal transport fee"),"SRV-04",400,2,0]],0,0,"unpaid","draft"]];
const fiLineCalc=l=>{const ex=l[2]*l[3]-l[4];return {ex,tax:ex*fiVat/100,inc:ex*(1+fiVat/100)}};
const fiTotals=r=>{const sub=r[4].reduce((a,l)=>a+l[2]*l[3],0),ld=r[4].reduce((a,l)=>a+l[4],0);
 const ex=sub-ld-r[5],tax=ex*fiVat/100;return {sub,ld,disc:r[5],ex,tax,pay:ex+tax,paid:r[6],rem:ex+tax-r[6]}};
const fiPurch=[
 ["PINV-4412","2026-08-04","Manila Prime Agency",[[t("رسوم استقدام — 4 عاملات","Recruitment fees — 4 workers"),"OFF-01",6200,4,0]],0,12400,"partial"],
 ["PINV-4411","2026-08-09","مؤسسة الرياض للتوريدات",[[t("مستلزمات إيواء","Shelter supplies"),"ITM-01",17,200,200]],0,3400,"settled"],
 ["PINV-4410","2026-08-12","مكتبة جرير",[[t("قرطاسية ومطبوعات","Stationery & printing"),"ITM-02",5.6,500,0]],0,0,"unpaid"]];
const fiBackPurch=[["RET-0088","2026-08-14","مؤسسة الرياض للتوريدات",t("إرجاع 20 وحدة مستلزمات","Return 20 supply units"),340,"posted"],["RET-0087","2026-07-28","Manila Prime Agency",t("إلغاء رسوم عاملة ملغاة","Cancelled worker fee"),6200,"posted"]];
const fiBanks=[["بنك الراجحي","Alrajhi Bank","110201","SA03 8000 0000 6080 1016 7519",1284000,948700],["بنك الأهلي","AlAhli Bank","110202","SA44 1000 0001 2345 6789 0123",612400,498100]];
const fiBoxes=[["الصندوق الرئيسي","Main cashbox","1101",842500,611300],["صندوق فرع جدة","Jeddah branch box","110103",164200,121800],["صندوق فرع الدمام","Dammam branch box","110104",98400,72200]];
const fiTransfers=[["TRF-0221","2026-08-18",t("الصندوق الرئيسي","Main cashbox"),t("بنك الراجحي — 1122","Alrajhi — 1122"),120000,"posted"],["TRF-0220","2026-08-12",t("بنك الأهلي — 4408","AlAhli — 4408"),t("الصندوق الرئيسي","Main cashbox"),45000,"posted"],["TRF-0219","2026-08-06",t("صندوق فرع جدة","Jeddah box"),t("الصندوق الرئيسي","Main cashbox"),28400,"draft"]];
const fiCatch=[["4412201","الراجحي","منيرة الشهري","2026-09-01",9250,"pending"],["8820114","الأهلي","شركة نجد للمقاولات","2026-09-10",12900,"collected"],["6612008","الإنماء","فيصل القحطاني","2026-08-14",15500,"refused"],["2201447","الرياض","شركة الوفاء الطبية","2026-09-22",14500,"pending"]];
const fiExch=[["1120884","الراجحي","Manila Prime Agency","2026-09-05",64200,"pending"],["1120883","الراجحي","Jakarta Care Agency","2026-08-12",48600,"collected"],["1120882","الأهلي","مؤسسة الرياض للتوريدات","2026-08-30",3400,"pending"]];
const fiItems=[["ITM-01",t("مستلزمات إيواء","Shelter supplies"),t("تشغيلية","Operational"),t("حبة","Unit"),17,22,480,120,"active"],["ITM-02",t("قرطاسية ومطبوعات","Stationery & printing"),t("إدارية","Administrative"),t("حبة","Unit"),5.6,8,620,200,"active"],["ITM-03",t("أدوية وإسعافات","Medicine & first aid"),t("تشغيلية","Operational"),t("علبة","Box"),42,55,86,40,"active"],["ITM-04",t("زي موحد","Uniforms"),t("تشغيلية","Operational"),t("طقم","Set"),68,95,140,60,"low"]];
const fiStock=[["ST-0114",t("مستلزمات إيواء","Shelter supplies"),t("مخزن الرياض","Riyadh store"),480,120,360],["ST-0113",t("قرطاسية ومطبوعات","Stationery"),t("مخزن الرياض","Riyadh store"),620,200,420],["ST-0112",t("زي موحد","Uniforms"),t("مخزن جدة","Jeddah store"),140,60,80]];
const fiInv=[["INVT-0044","2026-08-15",t("مخزن الرياض","Riyadh store"),18,4,t("مكتمل","Completed"),"g"],["INVT-0043","2026-07-31",t("مخزن جدة","Jeddah store"),9,1,t("مكتمل","Completed"),"g"],["INVT-0045","2026-08-22",t("مخزن الدمام","Dammam store"),0,0,t("قيد الجرد","In progress"),"a"]];
const fiFinClients=[["FC-0912","منيرة الشهري","person","1042887331","110301",28400,9250,19150],["FC-0908","شركة نجد للمقاولات","company","3001188220","110301",41200,5000,36200],["FC-0904","شركة الوفاء الطبية","company","3009988112","110302",21500,7000,14500],["FC-0901","عبدالله الدوسري","person","1099220145","110303",12500,12500,0]];
const fiSuppliers=[["SP-0044","Manila Prime Agency",t("الفلبين","Philippines"),"210101",642800,318400,324400],["SP-0043","Jakarta Care Agency",t("إندونيسيا","Indonesia"),"210102",486200,204100,282100],["SP-0042","Colombo Bridge Agency",t("سريلانكا","Sri Lanka"),"210103",331500,142900,188600],["SP-0041",t("مؤسسة الرياض للتوريدات","Riyadh Supplies"),t("السعودية","Saudi Arabia"),"210104",42800,39400,3400]];
const fiAgents=[["AG-0012","Manila Prime Agency",t("الفلبين","Philippines"),"+63 917 000 1122",18,"active"],["AG-0011","Jakarta Care Agency",t("إندونيسيا","Indonesia"),"+62 812 555 0198",14,"active"],["AG-0010","Colombo Bridge Agency",t("سريلانكا","Sri Lanka"),"+94 77 220 4418",9,"suspended"]];
const fiCenters=[["CC-01",t("الاستقدام","Recruitment"),2106600,1284600,822000],["CC-02",t("التأجير","Rental"),665800,318400,347400],["CC-03",t("الإيواء","Shelter"),0,672200,-672200],["CC-04",t("الإدارة","Administration"),48200,504800,-456600]];
const fiCcAlerts=[["CC-03",t("تجاوز حد المصروف الشهري","Monthly expense cap exceeded"),t("672,200 من حد 600,000","672,200 of a 600,000 cap"),"r"],["CC-04",t("اقترب من الحد","Approaching cap"),t("504,800 من حد 520,000","504,800 of a 520,000 cap"),"a"]];
const fiPostRules=[[t("فواتير عقود الاستقدام","Recruitment contract invoices"),"110301","4101",t("تلقائي","Automatic")],[t("فواتير عقود التأجير","Rental contract invoices"),"110302","4102",t("تلقائي","Automatic")],[t("نقل الكفالة","Sponsorship transfer"),"110303","4103",t("تلقائي","Automatic")],[t("مصروف إعاشة الإيواء","Shelter living expense"),"5101","1101",t("تلقائي","Automatic")],[t("رسوم المكاتب الخارجية","External office fees"),"210101","110201",t("يدوي بموافقة","Manual with approval")]];
let FIQ="",FISALEID="INV-9921",FIPRINT=null,FITAB="sales";
const fiStatusTag=k=>({settled:["مسدّد","Settled","g"],partial:["جزئي","Partial","a"],unpaid:["غير مسدّد","Unpaid","r"],posted:["مرحّل","Posted","g"],draft:["مسودة","Draft","a"],active:["نشط","Active","g"],suspended:["موقوف","Suspended","r"],low:["حد أدنى","Low stock","a"],pending:["قيد التحصيل","Pending","a"],collected:["محصّل","Collected","g"],refused:["مرفوض","Refused","r"],cleared:["مشاركة مع الهيئة","Reported to ZATCA","g"]}[k]||[k,k,""]);
const fiTag=k=>{const s=fiStatusTag(k);return `<span class="st tag ${s[2]}"><i></i>${t(s[0],s[1])}</span>`};
const fiM=(v,d=2)=>`<span class="num">${(v<0?"-":"")+Math.abs(v).toLocaleString("en",{minimumFractionDigits:d,maximumFractionDigits:d})}</span>`;
const fiPipe=cards=>`<div class="pipe">${cards.map(x=>`<div class="pg ${x[3]||"run"}"><span class="pg-i">${I(x[2])}</span><span class="pg-b"><span class="pg-t">${x[0]}</span><span class="pg-m"><b class="num">${typeof x[1]==="number"?x[1].toLocaleString("en"):x[1]}</b></span></span></div>`).join("")}</div>`;
const fiBar=fields=>`<div class="fbar">${fields.map(f=>`<div class="fb${f[2]?" grow":""}"><label>${f[0]}</label>${f[1]}</div>`).join("")}</div>`;
const fiSearch=ph=>`<div class="inp">${I("search")}<input placeholder="${ph}"></div>`;
const fiSel=(opts)=>`<select>${opts.map(o=>`<option>${o}</option>`).join("")}</select>`;
const fiEye=(ent,title,back,kv,sub,rows,rowsCols,rowsTitle)=>`<button class="icon-btn pri" data-fishow='${JSON.stringify({ent,title,back,kv,sub,rows:rows||null,rowsCols:rowsCols||null,rowsTitle:rowsTitle||null}).replace(/'/g,"&#39;")}' title="${t("عرض","View")}">${I("eye")}</button>`;
const fiTable=(cols,rows,foot)=>`<div class="card flush"><div class="tw"><table class="grp rep"><thead><tr>${cols.map(c=>`<th${c[2]?' class="'+c[2]+'"':""}>${t(c[0],c[1])}</th>`).join("")}</tr></thead><tbody>
 ${rows.length?rows.map(r=>`<tr>${r.map(c=>`<td>${c}</td>`).join("")}</tr>`).join(""):`<tr><td colspan="${cols.length}"><div class="tempty">${I("inbox")}<b>${t("لا بيانات","No data")}</b></div></td></tr>`}
 </tbody>${foot?`<tfoot><tr>${foot.map(c=>`<th>${c}</th>`).join("")}</tr></tfoot>`:""}</table></div></div>`;
const finOps={
 finSales:()=>{
  const tot=fiSales.map(fiTotals);
  const pay=tot.reduce((a,x)=>a+x.pay,0),paid=tot.reduce((a,x)=>a+x.paid,0),tax=tot.reduce((a,x)=>a+x.tax,0);
  return `
 <div class="page-head"><div><h1>${t("فواتير البيع","Sales invoices")}</h1><p>${t("الفواتير الضريبية الصادرة — بنود وضريبة قيمة مضافة 15% ومشاركة مع هيئة الزكاة.","Issued tax invoices — line items, 15% VAT and ZATCA reporting.")}</p></div>
 <div class="actions"><button class="btn btn-ghost">${I("file-spreadsheet")}Excel</button><button class="btn btn-primary" data-go="finSaleAdd">${I("plus")}${t("فاتورة بيع جديدة","New sales invoice")}</button></div></div>
 ${fiPipe([[t("إجمالي الفواتير","Invoiced"),Math.round(pay),"receipt","run"],[t("ضريبة القيمة المضافة","VAT"),Math.round(tax),"percent","run"],[t("المحصّل","Collected"),Math.round(paid),"check-circle-2","post"],[t("المتبقي","Remaining"),Math.round(pay-paid),"alarm-clock","closed"]])}
 ${fiBar([[t("بحث","Search"),fiSearch(t("رقم الفاتورة أو العميل","Invoice no. or client")),1],[t("حالة السداد","Payment status"),fiSel([t("الكل","All"),t("مسدّد","Settled"),t("جزئي","Partial"),t("غير مسدّد","Unpaid")])],[t("حالة الهيئة","ZATCA status"),fiSel([t("الكل","All"),t("مشاركة","Reported"),t("غير مشاركة","Not reported")])],[t("من تاريخ","From"),'<input type="date">'],[t("إلى تاريخ","To"),'<input type="date">']])}
 ${fiTable([["رقم الفاتورة","Invoice no."],["التاريخ","Date"],["العميل","Client"],["البنود","Lines"],["قبل الضريبة","Excl. VAT"],["الضريبة","VAT"],["الإجمالي","Total"],["المدفوع","Paid"],["المتبقي","Remaining"],["السداد","Payment"],["الهيئة","ZATCA"],["إجراءات","Actions","act"]],
  fiSales.map((r,i)=>{const x=tot[i];return [`<b class="num">${r[0]}</b>`,`<span class="num">${r[1]}</span>`,`<div class="gcell"><b>${r[2]}</b>${r[3]!=="—"?`<small class="num">${t("ض.ر","VAT")} ${r[3]}</small>`:""}</div>`,`<span class="num">${r[4].length}</span>`,fiM(x.ex),fiM(x.tax),`<b>${fiM(x.pay)}</b>`,fiM(x.paid),x.rem?`<b class="due">${fiM(x.rem)}</b>`:`<span class="ok">${t("مسدّد","Settled")}</span>`,fiTag(r[7]),r[8]==="draft"?`<span class="tag a">${t("غير مشاركة","Not reported")}</span>`:r[8]==="pending"?`<span class="tag a">${t("قيد المشاركة","Reporting")}</span>`:`<span class="tag g">${t("مشاركة","Reported")}</span>`,
  `<div class="rowacts">${fiEye("sale",r[0],"finSales",[[t("رقم الفاتورة","Invoice no."),r[0]],[t("التاريخ","Date"),r[1]],[t("العميل","Client"),r[2]],[t("قبل الضريبة","Excl. VAT"),fiM(x.ex)],[t("الضريبة","VAT"),fiM(x.tax)],[t("الإجمالي","Total"),fiM(x.pay)],[t("المدفوع","Paid"),fiM(x.paid)],[t("المتبقي","Remaining"),fiM(x.rem)]],r[2],r[4].map(l=>[l[0],fiM(l[2]),l[3],fiM(l[4]),fiM(fiLineCalc(l).tax),fiM(fiLineCalc(l).inc)]),[t("الوصف","Description"),t("سعر الوحدة","Unit price"),t("الكمية","Qty"),t("الخصم","Discount"),t("الضريبة","VAT"),t("الإجمالي","Total")],t("بنود الفاتورة","Invoice lines"))}<button class="icon-btn pri" data-fiprint="${r[0]}" title="${t("الفاتورة الضريبية","Tax invoice")}">${I("printer")}</button><button class="icon-btn pri" title="${t("تعديل","Edit")}">${I("pencil")}</button><button class="icon-btn">${I("more-horizontal")}</button></div>`]}),
  [t("الإجمالي","Total"),"","","",fiM(tot.reduce((a,x)=>a+x.ex,0)),fiM(tax),fiM(pay),fiM(paid),fiM(pay-paid),"","",""])}
 <div class="tfoot card"><span>${t(`عرض ${fiSales.length} من 128 فاتورة`,`Showing ${fiSales.length} of 128 invoices`)}</span><div class="pager"><button>${I("chevron-right")}</button><button class="on">1</button><button>2</button><button>${I("chevron-left")}</button></div></div>`},
 finSaleAdd:()=>{
  const f=(lab,ctl,full)=>`<div class="fld${full?" full":""}"><label>${lab}</label>${ctl}</div>`;
  return `
 <div class="page-head"><div><a class="back" href="#" data-go="finSales" style="margin:0 0 8px">${I("arrow-right")}<span>${t("فواتير البيع","Sales invoices")}</span></a><h1>${t("فاتورة بيع جديدة","New sales invoice")}</h1><p>${t("تُرحَّل تلقائياً إلى حساب العميل وحساب الإيراد وضريبة القيمة المضافة.","Posted automatically to the client, revenue and VAT accounts.")}</p></div>
 <div class="actions"><button class="btn btn-ghost" data-go="finSales">${t("إلغاء","Cancel")}</button><button class="btn btn-ghost" data-fiprint="INV-9921">${I("eye")}${t("معاينة الطباعة","Preview print")}</button><button class="btn btn-primary">${I("check")}${t("حفظ وترحيل","Save & post")}</button></div></div>
 <div class="cvform">
  <aside class="cvopts">
   <div class="card"><div class="sec-h">${I("calculator")}<b>${t("ملخص الفاتورة","Invoice summary")}</b></div>
    ${[[t("الإجمالي قبل الخصم","Subtotal"),"18,900.00"],[t("الخصم","Discount"),"500.00"],[t("غير شامل الضريبة","Excl. VAT"),"18,400.00"],[t("ضريبة 15%","VAT 15%"),"2,760.00"]].map(x=>`<div class="actrow sm"><div><b>${x[0]}</b></div><span class="num">${x[1]}</span></div>`).join("")}
    <div class="actrow sm total"><div><b>${t("الإجمالي المستحق","Total payable")}</b></div><span class="num">21,160.00</span></div></div>
   <div class="card"><div class="sec-h">${I("git-branch")}<b>${t("الترحيل المحاسبي","Accounting posting")}</b></div>
    ${[[t("حساب العميل (مدين)","Client (debit)"),"110301"],[t("حساب الإيراد (دائن)","Revenue (credit)"),"4101"],[t("ضريبة القيمة المضافة (دائن)","VAT (credit)"),"2102"]].map(x=>`<div class="actrow sm"><div><b>${x[0]}</b></div><span class="num">${x[1]}</span></div>`).join("")}</div>
  </aside>
  <div class="cvfields">
   <div class="card sec"><div class="fs-h"><span class="fs-n num">1</span><b>${t("بيانات الفاتورة","Invoice data")}</b></div>
    <div class="fgrid">${[f(t("العميل","Client"),fiSel(fiFinClients.map(c=>c[1]))),f(t("رقم الفاتورة","Invoice no."),'<input type="text" value="INV-9922" dir="ltr">'),f(t("تاريخ الفاتورة","Invoice date"),'<input type="date" value="2026-08-24">'),f(t("تاريخ التوريد","Supply date"),'<input type="date" value="2026-08-24">'),f(t("نوع الفاتورة","Invoice type"),fiSel([t("فاتورة ضريبية","Tax invoice"),t("فاتورة ضريبية مبسطة","Simplified tax invoice")])),f(t("طريقة الدفع","Payment method"),fiSel([t("كاش","Cash"),t("تحويل بنكي","Bank transfer"),t("شبكة (مدى)","Mada"),t("آجل","Credit")]))].join("")}</div></div>
   <div class="card sec"><div class="fs-h"><span class="fs-n num">2</span><b>${t("بنود الفاتورة","Invoice lines")}</b></div>
    <div class="tw"><table class="rep"><thead><tr><th>${t("الوصف","Description")}</th><th>${t("الصنف/الخدمة","Item/service")}</th><th>${t("سعر الوحدة","Unit price")}</th><th>${t("الكمية","Qty")}</th><th>${t("الخصم","Discount")}</th><th>${t("ضريبة 15%","VAT 15%")}</th><th>${t("الإجمالي","Total")}</th><th></th></tr></thead><tbody>
     <tr><td><input type="text" value="${t("عقد استقدام عاملة منزلية","Domestic worker recruitment")}"></td><td>${fiSel(["SRV-01","SRV-02","SRV-03","ITM-01"])}</td><td><input type="text" value="18,500" dir="ltr"></td><td><input type="text" value="1" dir="ltr"></td><td><input type="text" value="500" dir="ltr"></td><td class="num">2,700.00</td><td class="num">20,700.00</td><td><button class="icon-btn">${I("trash-2")}</button></td></tr>
     <tr><td><input type="text" value="${t("رسوم نقل داخلي","Internal transport fee")}"></td><td>${fiSel(["SRV-04","SRV-01"])}</td><td><input type="text" value="400" dir="ltr"></td><td><input type="text" value="1" dir="ltr"></td><td><input type="text" value="0" dir="ltr"></td><td class="num">60.00</td><td class="num">460.00</td><td><button class="icon-btn">${I("trash-2")}</button></td></tr>
    </tbody></table></div>
    <div class="formbar"><button class="btn btn-ghost">${I("plus")}${t("إضافة بند","Add line")}</button></div></div>
   <div class="card sec"><div class="fs-h"><span class="fs-n num">3</span><b>${t("الخصم والملاحظات","Discount & notes")}</b></div>
    <div class="fgrid">${[f(t("خصم على مستوى الفاتورة","Invoice-level discount"),'<input type="text" placeholder="0" dir="ltr">'),f(t("المدفوع مقدماً","Paid upfront"),'<input type="text" placeholder="0" dir="ltr">')].join("")}</div>
    ${f(t("ملاحظات تظهر في الفاتورة","Notes shown on the invoice"),'<textarea rows="2"></textarea>',1)}</div>
   <div class="formbar"><button class="btn btn-ghost" data-go="finSales">${t("إلغاء","Cancel")}</button><button class="btn btn-primary">${I("check")}${t("حفظ وترحيل","Save & post")}</button></div>
  </div>
 </div>`},
 finPurchases:()=>{
  const tot=fiPurch.map(r=>{const ex=r[3].reduce((a,l)=>a+l[2]*l[3]-l[4],0);return {ex,tax:ex*fiVat/100,pay:ex*(1+fiVat/100),paid:r[5]}});
  return `
 <div class="page-head"><div><h1>${t("فواتير الشراء","Purchase invoices")}</h1><p>${t("فواتير الموردين والمكاتب الخارجية — تُرحَّل على حساب المورد وضريبة المدخلات.","Supplier and external-office invoices — posted to the supplier account and input VAT.")}</p></div>
 <div class="actions"><button class="btn btn-ghost" data-go="finReturns">${I("undo-2")}${t("المرتجعات","Returns")}</button><button class="btn btn-primary" data-fiaddent="purchase">${I("plus")}${t("فاتورة شراء","Purchase invoice")}</button></div></div>
 ${fiPipe([[t("إجمالي المشتريات","Purchases"),Math.round(tot.reduce((a,x)=>a+x.pay,0)),"shopping-cart","run"],[t("ضريبة المدخلات","Input VAT"),Math.round(tot.reduce((a,x)=>a+x.tax,0)),"percent","run"],[t("المسدّد","Paid"),Math.round(tot.reduce((a,x)=>a+x.paid,0)),"check-circle-2","post"],[t("المستحق للموردين","Due to suppliers"),Math.round(tot.reduce((a,x)=>a+x.pay-x.paid,0)),"alarm-clock","closed"]])}
 ${fiBar([[t("بحث","Search"),fiSearch(t("رقم الفاتورة أو المورد","Invoice no. or supplier")),1],[t("المورد","Supplier"),fiSel([t("الكل","All")].concat(fiSuppliers.map(s=>s[1])))],[t("حالة السداد","Payment status"),fiSel([t("الكل","All"),t("مسدّد","Settled"),t("جزئي","Partial"),t("غير مسدّد","Unpaid")])],[t("من تاريخ","From"),'<input type="date">'],[t("إلى تاريخ","To"),'<input type="date">']])}
 ${fiTable([["رقم الفاتورة","Invoice no."],["التاريخ","Date"],["المورد","Supplier"],["البنود","Lines"],["قبل الضريبة","Excl. VAT"],["الضريبة","VAT"],["الإجمالي","Total"],["المسدّد","Paid"],["المستحق","Due"],["الحالة","Status"],["إجراءات","Actions","act"]],
  fiPurch.map((r,i)=>{const x=tot[i];return [`<b class="num">${r[0]}</b>`,`<span class="num">${r[1]}</span>`,`<b>${r[2]}</b>`,`<span class="num">${r[3].length}</span>`,fiM(x.ex),fiM(x.tax),`<b>${fiM(x.pay)}</b>`,fiM(x.paid),x.pay-x.paid>0?`<b class="due">${fiM(x.pay-x.paid)}</b>`:`<span class="ok">${t("مسدّد","Settled")}</span>`,fiTag(r[6]),`<div class="rowacts">${fiEye("purchase",r[0],"finPurchases",[[t("رقم الفاتورة","Invoice no."),r[0]],[t("التاريخ","Date"),r[1]],[t("المورد","Supplier"),r[2]],[t("قبل الضريبة","Excl. VAT"),fiM(x.ex)],[t("الضريبة","VAT"),fiM(x.tax)],[t("الإجمالي","Total"),fiM(x.pay)],[t("المسدّد","Paid"),fiM(x.paid)],[t("المستحق","Due"),fiM(x.pay-x.paid)]],r[2],r[3].map(l=>[l[0],fiM(l[2]),l[3],fiM(l[4]),fiM((l[2]*l[3]-l[4])*fiVat/100),fiM((l[2]*l[3]-l[4])*(1+fiVat/100))]),[t("الوصف","Description"),t("سعر الوحدة","Unit price"),t("الكمية","Qty"),t("الخصم","Discount"),t("الضريبة","VAT"),t("الإجمالي","Total")],t("بنود الفاتورة","Invoice lines"))}<button class="icon-btn pri">${I("printer")}</button><button class="icon-btn">${I("more-horizontal")}</button></div>`]}),
  [t("الإجمالي","Total"),"","","",fiM(tot.reduce((a,x)=>a+x.ex,0)),fiM(tot.reduce((a,x)=>a+x.tax,0)),fiM(tot.reduce((a,x)=>a+x.pay,0)),fiM(tot.reduce((a,x)=>a+x.paid,0)),fiM(tot.reduce((a,x)=>a+x.pay-x.paid,0)),"",""])}`},
 finReturns:()=>`
 <div class="page-head"><div><a class="back" href="#" data-go="finPurchases" style="margin:0 0 8px">${I("arrow-right")}<span>${t("فواتير الشراء","Purchase invoices")}</span></a><h1>${t("مرتجع المشتريات","Purchase returns")}</h1><p>${t("إرجاع أصناف أو رسوم إلى المورد — يُخفَّض به رصيد المورد وضريبة المدخلات.","Returning items or fees to the supplier — reduces the supplier balance and input VAT.")}</p></div>
 <div class="actions"><button class="btn btn-ghost">${I("printer")}${t("طباعة","Print")}</button><button class="btn btn-primary" data-fiaddent="ret">${I("plus")}${t("مرتجع جديد","New return")}</button></div></div>
 ${fiPipe([[t("عدد المرتجعات","Returns"),fiBackPurch.length,"undo-2","run"],[t("إجمالي المرتجع","Returned value"),fiBackPurch.reduce((a,r)=>a+r[4],0),"banknote","closed"],[t("مرحّلة","Posted"),fiBackPurch.filter(r=>r[5]==="posted").length,"check-circle-2","post"],[t("ضريبة معدّلة","Adjusted VAT"),Math.round(fiBackPurch.reduce((a,r)=>a+r[4],0)*fiVat/100),"percent","run"]])}
 ${fiBar([[t("بحث","Search"),fiSearch(t("رقم المرتجع أو المورد","Return no. or supplier")),1],[t("المورد","Supplier"),fiSel([t("الكل","All")].concat(fiSuppliers.map(s=>s[1])))],[t("من تاريخ","From"),'<input type="date">'],[t("إلى تاريخ","To"),'<input type="date">']])}
 ${fiTable([["رقم المرتجع","Return no."],["التاريخ","Date"],["المورد","Supplier"],["سبب الإرجاع","Reason"],["المبلغ","Amount"],["الضريبة المعدّلة","Adjusted VAT"],["الحالة","Status"],["إجراءات","Actions","act"]],
  fiBackPurch.map(r=>[`<b class="num">${r[0]}</b>`,`<span class="num">${r[1]}</span>`,`<b>${r[2]}</b>`,`<span style="white-space:normal">${r[3]}</span>`,fiM(r[4]),fiM(r[4]*fiVat/100),fiTag(r[5]),`<div class="rowacts">${fiEye("ret",r[0],"finReturns",[[t("رقم المرتجع","Return no."),r[0]],[t("التاريخ","Date"),r[1]],[t("المورد","Supplier"),r[2]],[t("السبب","Reason"),r[3]],[t("المبلغ","Amount"),fiM(r[4])],[t("الضريبة المعدّلة","Adjusted VAT"),fiM(r[4]*fiVat/100)]],r[2])}<button class="icon-btn pri">${I("printer")}</button></div>`]),
  [t("الإجمالي","Total"),"","","",fiM(fiBackPurch.reduce((a,r)=>a+r[4],0)),fiM(fiBackPurch.reduce((a,r)=>a+r[4],0)*fiVat/100),"",""])}`,
 finBanks:()=>`
 <div class="page-head"><div><h1>${t("البنوك والحسابات البنكية","Banks & bank accounts")}</h1><p>${t("الحسابات البنكية بأرقام الآيبان وأرصدتها المرتبطة بشجرة الحسابات.","Bank accounts with IBANs and balances linked to the chart of accounts.")}</p></div>
 <div class="actions"><button class="btn btn-ghost" data-frrep="bank_account_statement">${I("file-text")}${t("كشف الحساب البنكي","Bank statement")}</button><button class="btn btn-primary" data-fiaddent="bank">${I("plus")}${t("إضافة حساب بنكي","Add bank account")}</button></div></div>
 ${fiPipe([[t("عدد الحسابات","Accounts"),fiBanks.length,"landmark","run"],[t("إجمالي الإيداعات","Deposits"),fiBanks.reduce((a,b)=>a+b[4],0),"arrow-down-left","post"],[t("إجمالي المسحوبات","Withdrawals"),fiBanks.reduce((a,b)=>a+b[5],0),"arrow-up-right","closed"],[t("الرصيد المتاح","Available balance"),fiBanks.reduce((a,b)=>a+b[4]-b[5],0),"scale","post"]])}
 ${fiBar([[t("بحث","Search"),fiSearch(t("اسم البنك أو رقم الحساب","Bank name or account no.")),1],[t("البنك","Bank"),fiSel([t("الكل","All")].concat(fiBanks.map(b=>b[0])))],[t("الفرع","Branch"),fiSel([t("كل الفروع","All branches"),t("الفرع الرئيسي","Main branch"),t("فرع جدة","Jeddah"),t("فرع الدمام","Dammam")])]])}
 ${fiTable([["البنك","Bank"],["الحساب المحاسبي","Account"],["رقم الآيبان","IBAN"],["الإيداعات","Deposits"],["المسحوبات","Withdrawals"],["الرصيد","Balance"],["إجراءات","Actions","act"]],
  fiBanks.map(b=>[`<div class="gcell"><b>${b[0]}</b><small dir="ltr">${b[1]}</small></div>`,`<span class="num">${b[2]}</span>`,`<span class="num ltr">${b[3]}</span>`,fiM(b[4],0),fiM(b[5],0),`<b>${fiM(b[4]-b[5])}</b>`,`<div class="rowacts">${fiEye("bank",b[0],"finBanks",[[t("البنك","Bank"),b[0]],[t("الحساب","Account"),b[2]],[t("الآيبان","IBAN"),b[3]],[t("الإيداعات","Deposits"),fiM(b[4],0)],[t("المسحوبات","Withdrawals"),fiM(b[5],0)],[t("الرصيد","Balance"),fiM(b[4]-b[5])]],b[1])}<button class="icon-btn pri" data-acstatement="${b[2]}" title="${t("كشف الحساب","Statement")}">${I("file-text")}</button><button class="icon-btn pri">${I("pencil")}</button><button class="icon-btn">${I("trash-2")}</button></div>`]),
  [t("الإجمالي","Total"),"","",fiM(fiBanks.reduce((a,b)=>a+b[4],0),0),fiM(fiBanks.reduce((a,b)=>a+b[5],0),0),fiM(fiBanks.reduce((a,b)=>a+b[4]-b[5],0)),""])}`,
 finBoxes:()=>`
 <div class="page-head"><div><h1>${t("الصناديق النقدية","Cashboxes")}</h1><p>${t("صناديق الفروع وأرصدتها من سندات القبض والصرف.","Branch cashboxes and their balances from receipt and payment bonds.")}</p></div>
 <div class="actions"><button class="btn btn-ghost" data-frrep="cash_movements">${I("banknote")}${t("حركة النقدية","Cash movements")}</button><button class="btn btn-primary" data-fiaddent="box">${I("plus")}${t("إضافة صندوق","Add cashbox")}</button></div></div>
 ${fiPipe([[t("عدد الصناديق","Cashboxes"),fiBoxes.length,"wallet","run"],[t("إجمالي القبض","Total in"),fiBoxes.reduce((a,b)=>a+b[3],0),"arrow-down-left","post"],[t("إجمالي الصرف","Total out"),fiBoxes.reduce((a,b)=>a+b[4],0),"arrow-up-right","closed"],[t("الرصيد النقدي","Cash balance"),fiBoxes.reduce((a,b)=>a+b[3]-b[4],0),"scale","post"]])}
 ${fiBar([[t("بحث","Search"),fiSearch(t("اسم الصندوق","Cashbox name")),1],[t("الفرع","Branch"),fiSel([t("كل الفروع","All branches"),t("الفرع الرئيسي","Main branch"),t("فرع جدة","Jeddah"),t("فرع الدمام","Dammam")])],[t("الحالة","Status"),fiSel([t("الكل","All"),t("نشط","Active"),t("مقفل","Closed")])]])}
 ${fiTable([["الصندوق","Cashbox"],["الحساب المحاسبي","Account"],["القبض","In"],["الصرف","Out"],["الرصيد","Balance"],["أمين الصندوق","Custodian"],["إجراءات","Actions","act"]],
  fiBoxes.map((b,i)=>[`<b>${t(b[0],b[1])}</b>`,`<span class="num">${b[2]}</span>`,fiM(b[3],0),fiM(b[4],0),`<b>${fiM(b[3]-b[4])}</b>`,[t("سارة القحطاني","Sara Q."),t("خالد العمري","Khaled A."),t("محمد الشهراني","Mohammed S.")][i]||"—",`<div class="rowacts">${fiEye("box",t(b[0],b[1]),"finBoxes",[[t("الصندوق","Cashbox"),t(b[0],b[1])],[t("الحساب","Account"),b[2]],[t("القبض","In"),fiM(b[3],0)],[t("الصرف","Out"),fiM(b[4],0)],[t("الرصيد","Balance"),fiM(b[3]-b[4])]],b[2])}<button class="icon-btn pri" data-acstatement="${b[2]}">${I("file-text")}</button><button class="icon-btn pri">${I("pencil")}</button><button class="icon-btn">${I("trash-2")}</button></div>`]),
  [t("الإجمالي","Total"),"",fiM(fiBoxes.reduce((a,b)=>a+b[3],0),0),fiM(fiBoxes.reduce((a,b)=>a+b[4],0),0),fiM(fiBoxes.reduce((a,b)=>a+b[3]-b[4],0)),"",""])}`,
 finTransfers:()=>`
 <div class="page-head"><div><h1>${t("التحويل بين الحسابات","Account transfers")}</h1><p>${t("تحويل الأموال بين الصناديق والحسابات البنكية بقيد مزدوج تلقائي.","Moving funds between cashboxes and bank accounts with an automatic double entry.")}</p></div>
 <div class="actions"><button class="btn btn-ghost">${I("printer")}${t("طباعة","Print")}</button><button class="btn btn-primary" data-fiaddent="transfer">${I("plus")}${t("تحويل جديد","New transfer")}</button></div></div>
 ${fiPipe([[t("عدد التحويلات","Transfers"),fiTransfers.length,"arrow-left-right","run"],[t("إجمالي المحوّل","Transferred"),fiTransfers.reduce((a,r)=>a+r[4],0),"banknote","run"],[t("مرحّلة","Posted"),fiTransfers.filter(r=>r[5]==="posted").length,"check-circle-2","post"],[t("مسودة","Draft"),fiTransfers.filter(r=>r[5]==="draft").length,"file-pen","closed"]])}
 ${fiBar([[t("بحث","Search"),fiSearch(t("رقم التحويل أو الحساب","Transfer no. or account")),1],[t("من حساب","From account"),fiSel([t("الكل","All"),t("الصندوق الرئيسي","Main cashbox"),t("بنك الراجحي — 1122","Alrajhi — 1122"),t("بنك الأهلي — 4408","AlAhli — 4408")])],[t("إلى حساب","To account"),fiSel([t("الكل","All"),t("الصندوق الرئيسي","Main cashbox"),t("بنك الراجحي — 1122","Alrajhi — 1122")])],[t("من تاريخ","From"),'<input type="date">']])}
 ${fiTable([["رقم التحويل","Transfer no."],["التاريخ","Date"],["من حساب","From"],["إلى حساب","To"],["المبلغ","Amount"],["الحالة","Status"],["إجراءات","Actions","act"]],
  fiTransfers.map(r=>[`<b class="num">${r[0]}</b>`,`<span class="num">${r[1]}</span>`,`<b>${r[2]}</b>`,`<b>${r[3]}</b>`,fiM(r[4],0),fiTag(r[5]),`<div class="rowacts">${fiEye("transfer",r[0],"finTransfers",[[t("رقم التحويل","Transfer no."),r[0]],[t("التاريخ","Date"),r[1]],[t("من حساب","From"),r[2]],[t("إلى حساب","To"),r[3]],[t("المبلغ","Amount"),fiM(r[4],0)]],r[2]+" → "+r[3])}<button class="icon-btn pri">${I("printer")}</button><button class="icon-btn">${I("more-horizontal")}</button></div>`]),
  [t("الإجمالي","Total"),"","","",fiM(fiTransfers.reduce((a,r)=>a+r[4],0),0),"",""])}`,
 finChecksIn:()=>`
 <div class="page-head"><div><h1>${t("الشيكات الواردة","Received cheques")}</h1><p>${t("شيكات العملاء — قبول أو رفض أو تحصيل، ويُرحَّل التحصيل على البنك.","Client cheques — accept, refuse or collect; collection posts to the bank.")}</p></div>
 <div class="actions"><button class="btn btn-ghost">${I("printer")}${t("طباعة","Print")}</button><button class="btn btn-primary" data-fiaddent="checkin">${I("plus")}${t("إضافة شيك","Add cheque")}</button></div></div>
 ${fiPipe([[t("قيد التحصيل","Pending"),fiCatch.filter(c=>c[5]==="pending").reduce((a,c)=>a+c[4],0),"clock","run"],[t("محصّلة","Collected"),fiCatch.filter(c=>c[5]==="collected").reduce((a,c)=>a+c[4],0),"check-circle-2","post"],[t("مرفوضة","Refused"),fiCatch.filter(c=>c[5]==="refused").reduce((a,c)=>a+c[4],0),"x-circle","closed"],[t("عدد الشيكات","Cheques"),fiCatch.length,"receipt","run"]])}
 ${fiBar([[t("بحث","Search"),fiSearch(t("رقم الشيك أو العميل","Cheque no. or client")),1],[t("البنك","Bank"),fiSel([t("الكل","All"),"الراجحي","الأهلي","الإنماء","الرياض"])],[t("الحالة","Status"),fiSel([t("الكل","All"),t("قيد التحصيل","Pending"),t("محصّل","Collected"),t("مرفوض","Refused")])],[t("الاستحقاق من","Due from"),'<input type="date">'],[t("إلى","To"),'<input type="date">']])}
 ${fiTable([["رقم الشيك","Cheque no."],["البنك","Bank"],["العميل","Client"],["تاريخ الاستحقاق","Due date"],["المبلغ","Amount"],["الحالة","Status"],["إجراءات","Actions","act"]],
  fiCatch.map(c=>[`<b class="num ltr">${c[0]}</b>`,c[1],`<b>${c[2]}</b>`,`<span class="num">${c[3]}</span>`,fiM(c[4],0),fiTag(c[5]),`<div class="rowacts">${c[5]==="pending"?`<button class="icon-btn pri" title="${t("تحصيل","Collect")}">${I("check")}</button><button class="icon-btn" title="${t("رفض","Refuse")}">${I("x")}</button>`:`${fiEye("checkin",c[0],"finChecksIn",[[t("رقم الشيك","Cheque no."),c[0]],[t("البنك","Bank"),c[1]],[t("العميل","Client"),c[2]],[t("الاستحقاق","Due"),c[3]],[t("المبلغ","Amount"),fiM(c[4],0)]],c[2])}`}<button class="icon-btn">${I("printer")}</button></div>`]),
  [t("الإجمالي","Total"),"","","",fiM(fiCatch.reduce((a,c)=>a+c[4],0),0),"",""])}`,
 finChecksOut:()=>`
 <div class="page-head"><div><h1>${t("الشيكات الصادرة","Issued cheques")}</h1><p>${t("شيكات الموردين والمكاتب الخارجية وحالة صرفها.","Cheques issued to suppliers and external offices, and their clearing status.")}</p></div>
 <div class="actions"><button class="btn btn-ghost">${I("printer")}${t("طباعة","Print")}</button><button class="btn btn-primary" data-fiaddent="checkout">${I("plus")}${t("إصدار شيك","Issue cheque")}</button></div></div>
 ${fiPipe([[t("قيد الصرف","Pending"),fiExch.filter(c=>c[5]==="pending").reduce((a,c)=>a+c[4],0),"clock","run"],[t("مصروفة","Cleared"),fiExch.filter(c=>c[5]==="collected").reduce((a,c)=>a+c[4],0),"check-circle-2","post"],[t("عدد الشيكات","Cheques"),fiExch.length,"receipt","run"],[t("أقرب استحقاق","Next due"),"2026-08-30","calendar","closed"]])}
 ${fiBar([[t("بحث","Search"),fiSearch(t("رقم الشيك أو الجهة","Cheque no. or payee")),1],[t("البنك","Bank"),fiSel([t("الكل","All"),"الراجحي","الأهلي"])],[t("الحالة","Status"),fiSel([t("الكل","All"),t("قيد الصرف","Pending"),t("مصروف","Cleared")])],[t("الاستحقاق من","Due from"),'<input type="date">']])}
 ${fiTable([["رقم الشيك","Cheque no."],["البنك","Bank"],["الجهة المستفيدة","Payee"],["تاريخ الاستحقاق","Due date"],["المبلغ","Amount"],["الحالة","Status"],["إجراءات","Actions","act"]],
  fiExch.map(c=>[`<b class="num ltr">${c[0]}</b>`,c[1],`<b>${c[2]}</b>`,`<span class="num">${c[3]}</span>`,fiM(c[4],0),c[5]==="collected"?`<span class="st tag g"><i></i>${t("مصروف","Cleared")}</span>`:`<span class="st tag a"><i></i>${t("قيد الصرف","Pending")}</span>`,`<div class="rowacts">${c[5]==="pending"?`<button class="icon-btn pri" title="${t("تأكيد الصرف","Confirm")}">${I("check")}</button><button class="icon-btn">${I("x")}</button>`:`${fiEye("checkout",c[0],"finChecksOut",[[t("رقم الشيك","Cheque no."),c[0]],[t("البنك","Bank"),c[1]],[t("الجهة","Payee"),c[2]],[t("الاستحقاق","Due"),c[3]],[t("المبلغ","Amount"),fiM(c[4],0)]],c[2])}`}<button class="icon-btn">${I("printer")}</button></div>`]),
  [t("الإجمالي","Total"),"","","",fiM(fiExch.reduce((a,c)=>a+c[4],0),0),"",""])}`,
 finItems:()=>`
 <div class="page-head"><div><h1>${t("الأصناف","Items")}</h1><p>${t("الأصناف بأسعار التكلفة والبيع وأرصدتها وحدودها الدنيا.","Items with cost and sale prices, balances and minimum levels.")}</p></div>
 <div class="actions"><button class="btn btn-ghost">${I("upload")}${t("استيراد Excel","Import Excel")}</button><button class="btn btn-ghost" data-go="finStock">${I("layers")}${t("أرصدة أول المدة","Opening stock")}</button><button class="btn btn-primary" data-fiaddent="item">${I("plus")}${t("إضافة صنف","Add item")}</button></div></div>
 ${fiPipe([[t("عدد الأصناف","Items"),fiItems.length,"package","run"],[t("قيمة المخزون بالتكلفة","Stock at cost"),Math.round(fiItems.reduce((a,i)=>a+i[4]*i[6],0)),"banknote","run"],[t("قيمة المخزون بالبيع","Stock at sale price"),Math.round(fiItems.reduce((a,i)=>a+i[5]*i[6],0)),"trending-up","post"],[t("أصناف تحت الحد الأدنى","Below minimum"),fiItems.filter(i=>i[8]==="low").length,"alert-triangle","closed"]])}
 ${fiBar([[t("بحث","Search"),fiSearch(t("كود الصنف أو اسمه","Item code or name")),1],[t("التصنيف","Category"),fiSel([t("الكل","All"),t("تشغيلية","Operational"),t("إدارية","Administrative")])],[t("المخزن","Store"),fiSel([t("الكل","All"),t("مخزن الرياض","Riyadh store"),t("مخزن جدة","Jeddah store"),t("مخزن الدمام","Dammam store")])],[t("الحالة","Status"),fiSel([t("الكل","All"),t("نشط","Active"),t("حد أدنى","Low stock")])]])}
 ${fiTable([["الكود","Code"],["الصنف","Item"],["التصنيف","Category"],["الوحدة","Unit"],["سعر التكلفة","Cost"],["سعر البيع","Sale price"],["الرصيد","Balance"],["الحد الأدنى","Minimum"],["قيمة المخزون","Stock value"],["الحالة","Status"],["إجراءات","Actions","act"]],
  fiItems.map(i=>[`<b class="num">${i[0]}</b>`,`<b>${i[1]}</b>`,i[2],i[3],fiM(i[4]),fiM(i[5]),`<span class="num">${i[6]}</span>`,`<span class="num">${i[7]}</span>`,fiM(i[4]*i[6],0),fiTag(i[8]),`<div class="rowacts">${fiEye("item",i[1],"finItems",[[t("الكود","Code"),i[0]],[t("الصنف","Item"),i[1]],[t("التصنيف","Category"),i[2]],[t("الوحدة","Unit"),i[3]],[t("سعر التكلفة","Cost"),fiM(i[4])],[t("سعر البيع","Sale price"),fiM(i[5])],[t("الرصيد","Balance"),i[6]],[t("الحد الأدنى","Minimum"),i[7]],[t("قيمة المخزون","Stock value"),fiM(i[4]*i[6],0)]],i[0])}<button class="icon-btn pri">${I("pencil")}</button><button class="icon-btn">${I("more-horizontal")}</button></div>`]),
  [t("الإجمالي","Total"),"","","","","","","",fiM(fiItems.reduce((a,i)=>a+i[4]*i[6],0),0),"",""])}`,
 finStock:()=>`
 <div class="page-head"><div><a class="back" href="#" data-go="finItems" style="margin:0 0 8px">${I("arrow-right")}<span>${t("الأصناف","Items")}</span></a><h1>${t("أرصدة أول المدة","Opening stock")}</h1><p>${t("الأرصدة الافتتاحية لكل صنف في كل مخزن مع حركته.","Opening balances per item and store, with movement.")}</p></div>
 <div class="actions"><button class="btn btn-ghost">${I("file-spreadsheet")}Excel</button><button class="btn btn-primary" data-fiaddent="stock">${I("plus")}${t("إضافة رصيد افتتاحي","Add opening balance")}</button></div></div>
 ${fiPipe([[t("عدد السجلات","Records"),fiStock.length,"layers","run"],[t("إجمالي الوارد","Total in"),fiStock.reduce((a,r)=>a+r[3],0),"arrow-down-left","post"],[t("إجمالي المنصرف","Total out"),fiStock.reduce((a,r)=>a+r[4],0),"arrow-up-right","closed"],[t("الرصيد الحالي","Current balance"),fiStock.reduce((a,r)=>a+r[5],0),"package","run"]])}
 ${fiBar([[t("بحث","Search"),fiSearch(t("الصنف أو المرجع","Item or reference")),1],[t("المخزن","Store"),fiSel([t("الكل","All"),t("مخزن الرياض","Riyadh store"),t("مخزن جدة","Jeddah store")])],[t("السنة المالية","Fiscal year"),fiSel(["2026","2025"])]])}
 ${fiTable([["المرجع","Reference"],["الصنف","Item"],["المخزن","Store"],["الوارد","In"],["المنصرف","Out"],["الرصيد","Balance"],["إجراءات","Actions","act"]],
  fiStock.map(r=>[`<b class="num">${r[0]}</b>`,`<b>${r[1]}</b>`,r[2],`<span class="num">${r[3]}</span>`,`<span class="num">${r[4]}</span>`,`<b class="num">${r[5]}</b>`,`<div class="rowacts">${fiEye("stock",r[0],"finStock",[[t("المرجع","Reference"),r[0]],[t("الصنف","Item"),r[1]],[t("المخزن","Store"),r[2]],[t("الوارد","In"),r[3]],[t("المنصرف","Out"),r[4]],[t("الرصيد","Balance"),r[5]]],r[1])}<button class="icon-btn pri">${I("pencil")}</button></div>`]),
  [t("الإجمالي","Total"),"","",fiStock.reduce((a,r)=>a+r[3],0),fiStock.reduce((a,r)=>a+r[4],0),fiStock.reduce((a,r)=>a+r[5],0),""])}`,
 finInventory:()=>`
 <div class="page-head"><div><h1>${t("الجرد","Inventory counts")}</h1><p>${t("الجرد الدوري للمخازن وفروقاته المرحّلة على المخزون.","Periodic store counts and their differences posted to inventory.")}</p></div>
 <div class="actions"><button class="btn btn-ghost">${I("printer")}${t("طباعة كشف الجرد","Print count sheet")}</button><button class="btn btn-primary" data-fiaddent="count">${I("plus")}${t("جرد جديد","New count")}</button></div></div>
 ${fiPipe([[t("عدد عمليات الجرد","Counts"),fiInv.length,"clipboard-list","run"],[t("مكتملة","Completed"),fiInv.filter(i=>i[6]==="g").length,"check-circle-2","post"],[t("قيد التنفيذ","In progress"),fiInv.filter(i=>i[6]==="a").length,"clock","closed"],[t("إجمالي الفروقات","Total differences"),fiInv.reduce((a,i)=>a+i[4],0),"alert-triangle","closed"]])}
 ${fiBar([[t("بحث","Search"),fiSearch(t("رقم الجرد أو المخزن","Count no. or store")),1],[t("المخزن","Store"),fiSel([t("الكل","All"),t("مخزن الرياض","Riyadh store"),t("مخزن جدة","Jeddah store"),t("مخزن الدمام","Dammam store")])],[t("الحالة","Status"),fiSel([t("الكل","All"),t("مكتمل","Completed"),t("قيد الجرد","In progress")])],[t("من تاريخ","From"),'<input type="date">']])}
 ${fiTable([["رقم الجرد","Count no."],["التاريخ","Date"],["المخزن","Store"],["أصناف مطابقة","Matched"],["فروقات","Differences"],["الحالة","Status"],["إجراءات","Actions","act"]],
  fiInv.map(r=>[`<b class="num">${r[0]}</b>`,`<span class="num">${r[1]}</span>`,`<b>${r[2]}</b>`,`<span class="num">${r[3]}</span>`,r[4]?`<b class="due num">${r[4]}</b>`:`<span class="num">0</span>`,`<span class="st tag ${r[6]}"><i></i>${r[5]}</span>`,`<div class="rowacts">${fiEye("count",r[0],"finInventory",[[t("رقم الجرد","Count no."),r[0]],[t("التاريخ","Date"),r[1]],[t("المخزن","Store"),r[2]],[t("أصناف مطابقة","Matched"),r[3]],[t("فروقات","Differences"),r[4]],[t("الحالة","Status"),r[5]]],r[2])}<button class="icon-btn pri">${I("printer")}</button><button class="icon-btn">${I("more-horizontal")}</button></div>`]),
  [t("الإجمالي","Total"),"","",fiInv.reduce((a,r)=>a+r[3],0),fiInv.reduce((a,r)=>a+r[4],0),"",""])}`,
 finClients:()=>`
 <div class="page-head"><div><h1>${t("العملاء الماليون","Financial clients")}</h1><p>${t("بطاقات العملاء المرتبطة بحسابات 110301 – 110303 وأرصدتهم المدينة.","Client cards linked to accounts 110301 – 110303 and their receivables.")}</p></div>
 <div class="actions"><button class="btn btn-ghost" data-frrep="accounts_debtor_report">${I("bar-chart-3")}${t("تقرير الأرصدة المدينة","Receivables report")}</button><button class="btn btn-primary" data-fiaddent="finclient">${I("plus")}${t("عميل مالي جديد","New financial client")}</button></div></div>
 ${fiPipe([[t("عدد العملاء","Clients"),fiFinClients.length,"users","run"],[t("إجمالي العقود","Contracts value"),fiFinClients.reduce((a,c)=>a+c[5],0),"file-signature","run"],[t("المحصّل","Collected"),fiFinClients.reduce((a,c)=>a+c[6],0),"check-circle-2","post"],[t("الأرصدة المدينة","Receivables"),fiFinClients.reduce((a,c)=>a+c[7],0),"alarm-clock","closed"]])}
 ${fiBar([[t("بحث","Search"),fiSearch(t("الاسم أو الهوية أو الكود","Name, ID or code")),1],[t("النوع","Type"),fiSel([t("الكل","All"),t("فرد","Individual"),t("شركة","Company")])],[t("الحساب","Account"),fiSel([t("الكل","All"),"110301","110302","110303"])],[t("الرصيد","Balance"),fiSel([t("الكل","All"),t("عليه رصيد","With balance"),t("مسدّد","Settled")])]])}
 ${fiTable([["الكود","Code"],["العميل","Client"],["النوع","Type"],["الهوية / السجل","ID / registry"],["الحساب","Account"],["إجمالي العقود","Contracts"],["المحصّل","Collected"],["الرصيد المدين","Receivable"],["إجراءات","Actions","act"]],
  fiFinClients.map(c=>[`<b class="num">${c[0]}</b>`,`<b>${c[1]}</b>`,c[2]==="company"?`<span class="tag n">${t("شركة","Company")}</span>`:`<span class="tag">${t("فرد","Individual")}</span>`,`<span class="num ltr">${c[3]}</span>`,`<span class="num">${c[4]}</span>`,fiM(c[5],0),fiM(c[6],0),c[7]?`<b class="due">${fiM(c[7],0)}</b>`:`<span class="ok">${t("مسدّد","Settled")}</span>`,`<div class="rowacts">${fiEye("finclient",c[1],"finClients",[[t("الكود","Code"),c[0]],[t("العميل","Client"),c[1]],[t("الهوية / السجل","ID"),c[3]],[t("الحساب","Account"),c[4]],[t("إجمالي العقود","Contracts"),fiM(c[5],0)],[t("المحصّل","Collected"),fiM(c[6],0)],[t("الرصيد المدين","Receivable"),fiM(c[7],0)]],c[0])}<button class="icon-btn pri" data-acstatement="${c[4]}" title="${t("كشف الحساب","Statement")}">${I("file-text")}</button><button class="icon-btn pri">${I("pencil")}</button><button class="icon-btn">${I("more-horizontal")}</button></div>`]),
  [t("الإجمالي","Total"),"","","","",fiM(fiFinClients.reduce((a,c)=>a+c[5],0),0),fiM(fiFinClients.reduce((a,c)=>a+c[6],0),0),fiM(fiFinClients.reduce((a,c)=>a+c[7],0),0),""])}`,
 finSuppliers:()=>`
 <div class="page-head"><div><h1>${t("الموردون والمكاتب الخارجية","Suppliers & external offices")}</h1><p>${t("حسابات الموردين 2101xx وأرصدتهم الدائنة المستحقة.","Supplier accounts 2101xx and their outstanding payables.")}</p></div>
 <div class="actions"><button class="btn btn-ghost" data-frrep="accounts_creditor_report">${I("bar-chart-3")}${t("تقرير الأرصدة الدائنة","Payables report")}</button><button class="btn btn-ghost" data-go="finAgents">${I("globe")}${t("الوكلاء الخارجيون","External agents")}</button><button class="btn btn-primary" data-fiaddent="supplier">${I("plus")}${t("مورد جديد","New supplier")}</button></div></div>
 ${fiPipe([[t("عدد الموردين","Suppliers"),fiSuppliers.length,"truck","run"],[t("إجمالي الدائن","Total credit"),fiSuppliers.reduce((a,s)=>a+s[4],0),"arrow-up-right","closed"],[t("إجمالي المسدّد","Total paid"),fiSuppliers.reduce((a,s)=>a+s[5],0),"check-circle-2","post"],[t("المستحق","Payable"),fiSuppliers.reduce((a,s)=>a+s[6],0),"alarm-clock","closed"]])}
 ${fiBar([[t("بحث","Search"),fiSearch(t("اسم المورد أو الكود","Supplier name or code")),1],[t("الدولة","Country"),fiSel([t("الكل","All"),t("الفلبين","Philippines"),t("إندونيسيا","Indonesia"),t("سريلانكا","Sri Lanka"),t("السعودية","Saudi Arabia")])],[t("النوع","Type"),fiSel([t("الكل","All"),t("مكتب استقدام","Recruitment office"),t("مورد محلي","Local supplier")])],[t("الرصيد","Balance"),fiSel([t("الكل","All"),t("عليه مستحق","With payable"),t("مسدّد","Settled")])]])}
 ${fiTable([["الكود","Code"],["المورد","Supplier"],["الدولة","Country"],["الحساب","Account"],["دائن","Credit"],["مدين","Debit"],["الرصيد المستحق","Payable"],["إجراءات","Actions","act"]],
  fiSuppliers.map(s=>[`<b class="num">${s[0]}</b>`,`<b>${s[1]}</b>`,s[2],`<span class="num">${s[3]}</span>`,fiM(s[4],0),fiM(s[5],0),`<b>${fiM(s[6],0)}</b>`,`<div class="rowacts">${fiEye("supplier",s[1],"finSuppliers",[[t("الكود","Code"),s[0]],[t("المورد","Supplier"),s[1]],[t("الدولة","Country"),s[2]],[t("الحساب","Account"),s[3]],[t("دائن","Credit"),fiM(s[4],0)],[t("مدين","Debit"),fiM(s[5],0)],[t("المستحق","Payable"),fiM(s[6],0)]],s[2])}<button class="icon-btn pri" data-acstatement="${s[3]}">${I("file-text")}</button><button class="icon-btn pri">${I("pencil")}</button><button class="icon-btn">${I("more-horizontal")}</button></div>`]),
  [t("الإجمالي","Total"),"","","",fiM(fiSuppliers.reduce((a,s)=>a+s[4],0),0),fiM(fiSuppliers.reduce((a,s)=>a+s[5],0),0),fiM(fiSuppliers.reduce((a,s)=>a+s[6],0),0),""])}`,
 finAgents:()=>`
 <div class="page-head"><div><a class="back" href="#" data-go="finSuppliers" style="margin:0 0 8px">${I("arrow-right")}<span>${t("الموردون","Suppliers")}</span></a><h1>${t("الوكلاء الخارجيون","External agents")}</h1><p>${t("وكلاء الاستقدام في الدول المصدّرة وحالة تعاملهم.","Recruitment agents in source countries and their standing.")}</p></div>
 <div class="actions"><button class="btn btn-ghost">${I("file-spreadsheet")}Excel</button><button class="btn btn-primary" data-fiaddent="agent">${I("plus")}${t("إضافة وكيل","Add agent")}</button></div></div>
 ${fiPipe([[t("عدد الوكلاء","Agents"),fiAgents.length,"globe","run"],[t("عقود جارية","Active contracts"),fiAgents.reduce((a,x)=>a+x[4],0),"file-signature","run"],[t("نشطون","Active"),fiAgents.filter(a=>a[5]==="active").length,"check-circle-2","post"],[t("موقوفون","Suspended"),fiAgents.filter(a=>a[5]==="suspended").length,"ban","closed"]])}
 ${fiBar([[t("بحث","Search"),fiSearch(t("اسم الوكيل أو الدولة","Agent name or country")),1],[t("الدولة","Country"),fiSel([t("الكل","All"),t("الفلبين","Philippines"),t("إندونيسيا","Indonesia"),t("سريلانكا","Sri Lanka")])],[t("الحالة","Status"),fiSel([t("الكل","All"),t("نشط","Active"),t("موقوف","Suspended")])]])}
 ${fiTable([["الكود","Code"],["الوكيل","Agent"],["الدولة","Country"],["الهاتف","Phone"],["عقود جارية","Active contracts"],["الحالة","Status"],["إجراءات","Actions","act"]],
  fiAgents.map(a=>[`<b class="num">${a[0]}</b>`,`<b>${a[1]}</b>`,a[2],`<span class="num ltr">${a[3]}</span>`,`<span class="num">${a[4]}</span>`,fiTag(a[5]),`<div class="rowacts">${fiEye("agent",a[1],"finAgents",[[t("الكود","Code"),a[0]],[t("الوكيل","Agent"),a[1]],[t("الدولة","Country"),a[2]],[t("الهاتف","Phone"),a[3]],[t("عقود جارية","Active contracts"),a[4]]],a[2])}<button class="icon-btn pri">${I("pencil")}</button><button class="icon-btn">${I("more-horizontal")}</button></div>`]))}`,
 finCenters:()=>{
  const tr=fiCenters.reduce((a,c)=>a+c[2],0),te=fiCenters.reduce((a,c)=>a+c[3],0);
  return `
 <div class="page-head"><div><h1>${t("مراكز التكلفة","Cost centres")}</h1><p>${t("توزيع الإيرادات والمصروفات على مراكز التكلفة مع حدود التنبيه.","Revenue and expense distribution across cost centres, with alert caps.")}</p></div>
 <div class="actions"><button class="btn btn-ghost" data-frrep="cost_centers">${I("bar-chart-3")}${t("تقرير مراكز التكلفة","Cost centre report")}</button><button class="btn btn-primary" data-fiaddent="center">${I("plus")}${t("مركز تكلفة","Cost centre")}</button></div></div>
 ${fiPipe([[t("إجمالي الإيرادات","Revenue"),tr,"trending-up","post"],[t("إجمالي المصروفات","Expenses"),te,"trending-down","closed"],[t("الصافي","Net"),tr-te,"scale",tr-te>=0?"post":"closed"],[t("تنبيهات نشطة","Active alerts"),fiCcAlerts.length,"bell","closed"]])}
 ${fiBar([[t("بحث","Search"),fiSearch(t("اسم المركز أو الكود","Centre name or code")),1],[t("الفترة","Period"),fiSel([t("هذا الشهر","This month"),t("هذا الربع","This quarter"),t("هذه السنة","This year")])],[t("الحالة","Status"),fiSel([t("الكل","All"),t("داخل الحد","Within cap"),t("تجاوز الحد","Over cap")])]])}
 ${fiTable([["الكود","Code"],["مركز التكلفة","Cost centre"],["الإيرادات","Revenue"],["المصروفات","Expenses"],["الصافي","Net"],["حصة المصروف","Expense share"],["إجراءات","Actions","act"]],
  fiCenters.map(c=>[`<b class="num">${c[0]}</b>`,`<b>${c[1]}</b>`,fiM(c[2],0),fiM(c[3],0),`<b class="${c[4]<0?"due":"ok"}">${fiM(c[4],0)}</b>`,`<div class="mrate"><span class="track"><i style="width:${Math.round(c[3]/te*100)}%"></i></span><b class="num">${Math.round(c[3]/te*100)}%</b></div>`,`<div class="rowacts">${fiEye("center",c[1],"finCenters",[[t("الكود","Code"),c[0]],[t("المركز","Centre"),c[1]],[t("الإيرادات","Revenue"),fiM(c[2],0)],[t("المصروفات","Expenses"),fiM(c[3],0)],[t("الصافي","Net"),fiM(c[4],0)]],c[0])}<button class="icon-btn pri">${I("pencil")}</button><button class="icon-btn">${I("settings-2")}</button></div>`]),
  [t("الإجمالي","Total"),"",fiM(tr,0),fiM(te,0),fiM(tr-te,0),"100%",""])}
 <div class="card sec"><div class="sec-h">${I("bell")}<b>${t("تنبيهات حدود الصرف","Spend cap alerts")}</b></div>
  <div class="alerts">${fiCcAlerts.map(a=>`<div class="alert ${a[3]}">${I("dot")}<div><b>${a[1]} — ${a[0]}</b><small>${a[2]}</small></div></div>`).join("")}</div></div>`},
 finSettings:()=>`
 <div class="page-head"><div><h1>${t("الإعدادات المحاسبية و ZATCA","Accounting & ZATCA settings")}</h1><p>${t("قواعد الترحيل التلقائي، إعدادات الاعتماد، والسنة المالية، وبيانات الفاتورة الإلكترونية.","Automatic posting rules, approvals, fiscal year and e-invoicing data.")}</p></div>
 <div class="actions"><button class="btn btn-ghost">${I("refresh-cw")}${t("اختبار الاتصال بالهيئة","Test ZATCA connection")}</button><button class="btn btn-primary">${I("check")}${t("حفظ الإعدادات","Save settings")}</button></div></div>
 <div class="card sec"><div class="sec-h">${I("git-branch")}<b>${t("قواعد الترحيل المحاسبي","Accounting posting rules")}</b><span class="ms">${t("الحساب المدين والدائن لكل عملية","Debit and credit account per operation")}</span></div>
  <div class="tw"><table class="rep"><thead><tr><th>${t("العملية","Operation")}</th><th>${t("حساب مدين","Debit account")}</th><th>${t("حساب دائن","Credit account")}</th><th>${t("الترحيل","Posting")}</th><th class="act"></th></tr></thead><tbody>
  ${fiPostRules.map(r=>`<tr><td><b>${r[0]}</b></td><td class="num">${r[1]}</td><td class="num">${r[2]}</td><td>${r[3]===t("تلقائي","Automatic")?`<span class="tag g">${r[3]}</span>`:`<span class="tag a">${r[3]}</span>`}</td><td class="act"><div class="rowacts"><button class="icon-btn pri">${I("pencil")}</button></div></td></tr>`).join("")}
 </tbody></table></div></div>
 <div class="grid-2">
  <div class="card sec"><div class="sec-h">${I("shield-check")}<b>${t("إعدادات الاعتماد","Approval settings")}</b></div>
   ${[[t("اعتماد القيود قبل الترحيل","Approve entries before posting"),1],[t("اعتماد سندات الصرف فوق 50,000","Approve payments over 50,000"),1],[t("اعتماد فواتير الشراء","Approve purchase invoices"),0],[t("منع تعديل قيد مرحّل","Lock posted entries"),1]].map(x=>`<div class="actrow"><div><b>${x[0]}</b></div><span class="sw ${x[1]?"on":""}"></span></div>`).join("")}</div>
  <div class="card sec"><div class="sec-h">${I("calendar-range")}<b>${t("السنة المالية","Fiscal year")}</b></div>
   ${[[t("السنة الحالية","Current year"),"2026"],[t("بداية السنة","Year start"),"2026-01-01"],[t("نهاية السنة","Year end"),"2026-12-31"],[t("حالة الإقفال","Closing status"),t("مفتوحة","Open")],[t("عدد القيود المرحّلة","Posted entries"),"1,284"]].map(x=>`<div class="actrow sm"><div><b>${x[0]}</b></div><span class="num">${x[1]}</span></div>`).join("")}
   <div class="formbar"><button class="btn btn-ghost" data-frrep="fiscal_year_closing">${I("archive")}${t("إجراءات الإقفال","Closing steps")}</button></div></div>
 </div>
 <div class="card sec"><div class="sec-h">${I("qr-code")}<b>${t("بيانات الفاتورة الإلكترونية (ZATCA)","E-invoicing data (ZATCA)")}</b></div>
  <div class="fgrid">${[[t("اسم المنشأة الضريبي","VAT name"),"شركة كليك آند مور للاستقدام"],[t("الرقم الضريبي","VAT number"),"300118822000003"],[t("رقم السجل التجاري","Commercial registration"),"1010448822"],[t("المدينة","City"),"الرياض"],[t("الحي","District"),"حي الملقا"],[t("الشارع","Street"),"طريق أنس بن مالك"],[t("رقم المبنى","Building no."),"4412"],[t("الرمز البريدي","Postal code"),"13521"]].map(x=>`<div class="fld"><label>${x[0]}</label><input type="text" value="${x[1]}"${/^[0-9]+$/.test(x[1])?' dir="ltr"':""}></div>`).join("")}</div>
  <div class="fgrid two">${[[t("مرحلة التكامل","Integration phase"),[t("المرحلة الثانية — الربط","Phase 2 — integration"),t("المرحلة الأولى","Phase 1")]],[t("بيئة الربط","Environment"),[t("الإنتاج","Production"),t("التجربة (Sandbox)","Sandbox")]]].map(x=>`<div class="fld"><label>${x[0]}</label>${fiSel(x[1])}</div>`).join("")}</div>
  <p class="pnote">${I("info")}${t("تظهر هذه البيانات في رأس الفاتورة الضريبية ورمز QR.","These values appear in the tax invoice header and QR code.")}</p></div>`
};
Object.assign(T,{finSales:{ar:"فواتير البيع",en:"Sales invoices"},finSaleAdd:{ar:"فاتورة بيع جديدة",en:"New sales invoice"},finPurchases:{ar:"فواتير الشراء",en:"Purchase invoices"},finReturns:{ar:"مرتجع المشتريات",en:"Purchase returns"},finBanks:{ar:"البنوك والحسابات البنكية",en:"Banks"},finBoxes:{ar:"الصناديق النقدية",en:"Cashboxes"},finTransfers:{ar:"التحويل بين الحسابات",en:"Account transfers"},finChecksIn:{ar:"الشيكات الواردة",en:"Received cheques"},finChecksOut:{ar:"الشيكات الصادرة",en:"Issued cheques"},finItems:{ar:"الأصناف",en:"Items"},finStock:{ar:"أرصدة أول المدة",en:"Opening stock"},finInventory:{ar:"الجرد",en:"Inventory counts"},finClients:{ar:"العملاء الماليون",en:"Financial clients"},finSuppliers:{ar:"الموردون",en:"Suppliers"},finAgents:{ar:"الوكلاء الخارجيون",en:"External agents"},finCenters:{ar:"مراكز التكلفة",en:"Cost centres"},finSettings:{ar:"الإعدادات المحاسبية",en:"Accounting settings"}});
Object.assign(screens,finOps);
(function(){const g=nav.find(x=>x.t.ar==="الإدارة");if(!g)return;const f=g.items.find(x=>x.id==="finance");if(f)f.kids=[
 {sec:1,ar:"الدفاتر",en:"Books"},
 {id:"finAccounts",ar:"شجرة الحسابات",en:"Chart of accounts"},
 {id:"finEntries",ar:"القيود اليومية",en:"Journal entries"},
 {id:"finBonds",ar:"سندات القبض والصرف",en:"Receipt & payment bonds"},
 {sec:1,ar:"الفواتير",en:"Invoices"},
 {id:"finSales",ar:"فواتير البيع",en:"Sales invoices"},
 {id:"finPurchases",ar:"فواتير الشراء",en:"Purchase invoices"},
 {id:"finReturns",ar:"مرتجع المشتريات",en:"Purchase returns"},
 {sec:1,ar:"النقدية",en:"Cash"},
 {id:"finBanks",ar:"البنوك والحسابات",en:"Banks"},
 {id:"finBoxes",ar:"الصناديق النقدية",en:"Cashboxes"},
 {id:"finTransfers",ar:"التحويل بين الحسابات",en:"Account transfers"},
 {id:"finChecksIn",ar:"الشيكات الواردة",en:"Received cheques"},
 {id:"finChecksOut",ar:"الشيكات الصادرة",en:"Issued cheques"},
 {sec:1,ar:"المخزون",en:"Inventory"},
 {id:"finItems",ar:"الأصناف",en:"Items"},
 {id:"finStock",ar:"أرصدة أول المدة",en:"Opening stock"},
 {id:"finInventory",ar:"الجرد",en:"Inventory counts"},
 {sec:1,ar:"الأطراف",en:"Parties"},
 {id:"finClients",ar:"العملاء الماليون",en:"Financial clients"},
 {id:"finSuppliers",ar:"الموردون",en:"Suppliers"},
 {id:"finAgents",ar:"الوكلاء الخارجيون",en:"External agents"},
 {sec:1,ar:"التحليل والإعداد",en:"Analysis & setup"},
 {id:"finCenters",ar:"مراكز التكلفة",en:"Cost centres"},
 {id:"finReports",ar:"التقارير المالية",en:"Financial reports"},
 {id:"finSettings",ar:"الإعدادات المحاسبية و ZATCA",en:"Accounting & ZATCA"}];})();
/* ZATCA tax invoice print — mirrors bill_sales/_document.blade.php */
function fiInvoiceDoc(r){
 const x=fiTotals(r);
 const row2=(ar,en,v)=>`<div class="ivf"><div class="ivf-l"><span>${ar}</span><span dir="ltr">${en}</span></div><div class="ivf-v">${v}</div></div>`;
 return `<article class="ivdoc">
  <header class="ivbrand"><img src="assets/logo-light-bg.png" alt="Click &amp; More"><div class="ivbrand-t"><b>شركة كليك آند مور للاستقدام</b><small dir="ltr">Click &amp; More Recruitment Co.</small></div></header>
  <div class="ivtitle"><h1>فاتورة ضريبية</h1><p dir="ltr">Tax Invoice</p></div>
  <section class="ivmeta">
   <div class="ivmeta-c ltr">${[["Invoice number",r[0]],["Invoice type","Tax invoice"],["Issue date",r[1]],["Supply date",r[1]]].map(m=>`<div class="ivmeta-i"><strong>${m[0]}</strong><span>${m[1]}</span></div>`).join("")}</div>
   <div class="ivqr">${r[8]==="cleared"?`<div class="ivqr-box">${I("qr-code")}<small>ZATCA QR</small></div>`:`<div class="ivqr-ph">لم تتم مشاركة الفاتورة<br>مع هيئة الزكاة</div>`}</div>
   <div class="ivmeta-c">${[["رقم الفاتورة",r[0]],["نوع الفاتورة","فاتورة ضريبية"],["تاريخ إصدار الفاتورة",r[1]],["حالة الهيئة",r[8]==="cleared"?"مشاركة":r[8]==="pending"?"قيد المشاركة":"غير مشاركة"]].map(m=>`<div class="ivmeta-i"><strong>${m[0]}</strong><span>${m[1]}</span></div>`).join("")}</div>
  </section>
  <section class="ivsec"><h2 class="ivsec-t"><span>هوية المورد</span><span dir="ltr">Seller Identification</span></h2>
   <div class="ivfields">${row2("اسم المورد","Seller name","شركة كليك آند مور للاستقدام")}${row2("الرقم الضريبي","VAT number",'<span class="num ltr">300118822000003</span>')}${row2("رقم السجل التجاري","Commercial registration",'<span class="num ltr">1010448822</span>')}${row2("العنوان","Address","طريق أنس بن مالك، مبنى 4412، حي الملقا، الرياض، 13521")}</div></section>
  <section class="ivsec"><h2 class="ivsec-t"><span>هوية العميل</span><span dir="ltr">Customer Identification</span></h2>
   <div class="ivfields">${row2("اسم العميل","Customer name",r[2])}${row2("رقم العميل","Customer number",'<span class="num ltr">FC-0912</span>')}${row2("الرقم الضريبي","VAT number",r[3]==="—"?"—":`<span class="num ltr">${r[3]}</span>`)}${row2("العنوان","Address","الرياض، المملكة العربية السعودية")}</div></section>
  <section class="ivlines"><div class="ivlines-t"><span>معلومات وبيانات السلعة أو الخدمة</span><span dir="ltr">Line items</span></div>
   <table><thead><tr>${[["وصف السلعة أو الخدمة","Description"],["سعر الوحدة","Unit price"],["الكمية","Quantity"],["الخصم","Discount"],["نسبة الضريبة","VAT rate"],["مبلغ الضريبة","VAT amount"],["الإجمالي قبل الضريبة","Exclusive of VAT"],["الإجمالي شامل الضريبة","Inclusive of VAT"]].map(h=>`<th>${h[0]}<br><span dir="ltr">${h[1]}</span></th>`).join("")}</tr></thead><tbody>
   ${r[4].map(l=>{const c=fiLineCalc(l);return `<tr><td class="desc">${l[0]}<small dir="ltr">#${l[1]}</small></td><td>${l[2].toLocaleString("en",{minimumFractionDigits:2})}</td><td>${l[3].toFixed(2)}</td><td>${l[4].toFixed(2)}</td><td>${fiVat.toFixed(2)}%</td><td>${c.tax.toLocaleString("en",{minimumFractionDigits:2})}</td><td>${c.ex.toLocaleString("en",{minimumFractionDigits:2})}</td><td>${c.inc.toLocaleString("en",{minimumFractionDigits:2})}</td></tr>`}).join("")}
   </tbody></table></section>
  <div class="ivsum"><table>${[["الإجمالي قبل الخصم","Subtotal before discount",x.sub],["الخصم على مستوى الفاتورة","Invoice discount",x.ld+x.disc],["المبلغ غير شامل ضريبة القيمة المضافة","Tax exclusive amount",x.ex],["إجمالي ضريبة القيمة المضافة","VAT total amount",x.tax],["إجمالي المبلغ المستحق","Total payable amount",x.pay]].map((s,i)=>`<tr${i===4?' class="grand"':""}><th>${s[0]}<br><span dir="ltr">${s[1]}</span></th><td dir="ltr">SAR ${s[2].toLocaleString("en",{minimumFractionDigits:2})}</td></tr>`).join("")}</table></div>
  <footer class="ivfoot"><span dir="ltr">UUID: 3cf1a8e2-4d7b-4f19-9c22-${r[0].slice(-4)}0a11</span><span>تم إنشاء المستند من النظام بتاريخ 2026-08-24 12:40</span></footer>
 </article>`}
document.addEventListener("click",e=>{
 const pr=e.target.closest("[data-fiprint]");
 if(pr){const r=fiSales.find(x=>x[0]===pr.dataset.fiprint)||fiSales[0];
  let ov=document.getElementById("fiOv");if(!ov){ov=document.createElement("div");ov.id="fiOv";ov.className="ov";document.body.appendChild(ov)}
  ov.innerHTML=`<div class="modal ivmodal"><div class="modal-h"><div><h3>${t("الفاتورة الضريبية","Tax invoice")}</h3><p class="ms">${r[0]} · ${r[2]}</p></div><div class="actions"><button class="btn btn-ghost" data-ficlose>${t("إغلاق","Close")}</button><button class="btn btn-primary">${I("printer")}${t("طباعة","Print")}</button></div></div><div class="modal-b ivwrap">${fiInvoiceDoc(r)}</div></div>`;
  ov.classList.remove("hidden");lucide.createIcons({attrs:{"stroke-width":1.8}});return}
 if(e.target.closest("[data-ficlose]")){const ov=document.getElementById("fiOv");if(ov)ov.classList.add("hidden");return}
 const fr=e.target.closest("[data-frrep]");if(fr&&typeof FRID!=="undefined"){FRID=fr.dataset.frrep;cur="finReport";render();window.scrollTo(0,0)}
});

/* ===== clickerp-finance-forms.js ===== */
/* Finance add/detail forms + cost-centre tree */
const fiEnt={"sale":{"ar":"فاتورة بيع","en":"Sales invoice","back":"finSales","secs":[[[["العميل","Client","sel",["منيرة الشهري","شركة نجد للمقاولات","شركة الوفاء الطبية"]],["رقم الفاتورة","Invoice no.","ltr","INV-9922"],["تاريخ الفاتورة","Invoice date","date","2026-08-24"],["نوع الفاتورة","Invoice type","sel",["فاتورة ضريبية","فاتورة ضريبية مبسطة"]]],"بيانات الفاتورة","Invoice data"],[[["الوصف","Description","txt","عقد استقدام عاملة منزلية"],["الصنف / الخدمة","Item / service","sel",["SRV-01","SRV-02","SRV-03","ITM-01"]],["الكمية","Qty","ltr","1"],["سعر الوحدة","Unit price","ltr","18,500"],["الخصم","Discount","ltr","500"],["نسبة الضريبة","VAT rate","sel",["15%","0% (معفى)"]]],"بنود الفاتورة","Invoice lines"],[[["خصم على مستوى الفاتورة","Invoice-level discount","ltr","0"],["المدفوع مقدماً","Paid upfront","ltr","0"],["طريقة الدفع","Payment method","sel",["كاش","تحويل بنكي","شبكة (مدى)","آجل"]]],"الخصم والسداد","Discount & payment"]]},"purchase":{"ar":"فاتورة شراء","en":"Purchase invoice","back":"finPurchases","secs":[[[["المورد","Supplier","sel",["Manila Prime Agency","Jakarta Care Agency","مؤسسة الرياض للتوريدات"]],["رقم فاتورة المورد","Supplier invoice no.","ltr","SUP-2026-114"],["تاريخ الفاتورة","Invoice date","date","2026-08-24"],["تاريخ الاستحقاق","Due date","date",""],["حساب المصروف / المخزون","Expense / stock account","sel",["5103 / تأشيرات ورسوم","1104 / مصروفات مقدمة","ITM-01 / مستلزمات إيواء"]],["طريقة الدفع","Payment method","sel",["آجل","تحويل بنكي","كاش"]]],"بيانات فاتورة الشراء","Purchase invoice data"],[[["الوصف","Description","txt","رسوم استقدام"],["الصنف / الخدمة","Item / service","sel",["OFF-01 / رسوم استقدام","ITM-01 / مستلزمات إيواء","ITM-02 / قرطاسية"]],["الكمية","Qty","ltr","1"],["سعر الوحدة","Unit price","ltr","6,200"],["الخصم","Discount","ltr","0"],["نسبة الضريبة","VAT rate","sel",["15%","0% (معفى)"]]],"بنود الفاتورة","Invoice lines"]]},"ret":{"ar":"مرتجع مشتريات","en":"Purchase return","back":"finReturns","secs":[[[["المورد","Supplier","sel",["مؤسسة الرياض للتوريدات","Manila Prime Agency"]],["فاتورة الشراء","Purchase invoice","sel",["PINV-4412","PINV-4411","PINV-4410"]],["تاريخ المرتجع","Return date","date","2026-08-24"],["المبلغ المرتجع","Returned amount","ltr","340"],["سبب الإرجاع","Reason","sel",["أصناف تالفة","كمية زائدة","إلغاء خدمة","خطأ في الفاتورة"]],["يُخفَّض من","Deduct from","sel",["رصيد المورد","إشعار دائن"]]],"بيانات المرتجع","Return data"]]},"bank":{"ar":"حساب بنكي","en":"Bank account","back":"finBanks","secs":[[[["البنك","Bank","sel",["بنك الراجحي","بنك الأهلي","بنك الرياض","بنك الإنماء"]],["اسم الحساب","Account name","txt","الحساب الجاري الرئيسي"],["رقم الحساب","Account number","ltr","608010167519"],["رقم الآيبان","IBAN","ltr","SA00 0000 0000 0000 0000 0000"],["الحساب المحاسبي","Ledger account","sel",["110201","110202","110203"]],["الفرع","Branch","sel",["الفرع الرئيسي","فرع جدة","فرع الدمام"]],["الرصيد الافتتاحي","Opening balance","ltr","0"],["العملة","Currency","sel",["ريال سعودي","دولار أمريكي"]]],"بيانات الحساب البنكي","Bank account data"]]},"box":{"ar":"صندوق نقدي","en":"Cashbox","back":"finBoxes","secs":[[[["اسم الصندوق","Cashbox name","txt","صندوق فرع الرياض"],["الحساب المحاسبي","Ledger account","sel",["1101","110103","110104"]],["الفرع","Branch","sel",["الفرع الرئيسي","فرع جدة","فرع الدمام"]],["أمين الصندوق","Custodian","sel",["سارة القحطاني","خالد العمري","محمد الشهراني"]],["الرصيد الافتتاحي","Opening balance","ltr","0"],["حد الصرف اليومي","Daily spend cap","ltr","20,000"]],"بيانات الصندوق","Cashbox data"]]},"transfer":{"ar":"تحويل بين الحسابات","en":"Account transfer","back":"finTransfers","secs":[[[["من حساب","From account","sel",["الصندوق الرئيسي","بنك الراجحي — 1122","بنك الأهلي — 4408"]],["إلى حساب","To account","sel",["بنك الراجحي — 1122","الصندوق الرئيسي","صندوق فرع جدة"]],["المبلغ","Amount","ltr","120,000"],["تاريخ التحويل","Transfer date","date","2026-08-24"],["مرجع التحويل","Reference","ltr","TRF-0222"],["البيان","Description","txt","تغذية الحساب البنكي"]],"بيانات التحويل","Transfer data"]]},"checkin":{"ar":"شيك وارد","en":"Received cheque","back":"finChecksIn","secs":[[[["العميل","Client","sel",["منيرة الشهري","شركة نجد للمقاولات","فيصل القحطاني"]],["رقم الشيك","Cheque number","ltr","4412202"],["البنك","Bank","sel",["الراجحي","الأهلي","الإنماء","الرياض"]],["تاريخ الاستحقاق","Due date","date",""],["المبلغ","Amount","ltr","9,250"],["حساب الإيداع","Deposit account","sel",["بنك الراجحي — 1122","بنك الأهلي — 4408"]],["الحالة المبدئية","Initial status","sel",["قيد التحصيل","محصّل"]]],"بيانات الشيك الوارد","Received cheque data"]]},"checkout":{"ar":"شيك صادر","en":"Issued cheque","back":"finChecksOut","secs":[[[["الجهة المستفيدة","Payee","sel",["Manila Prime Agency","Jakarta Care Agency","مؤسسة الرياض للتوريدات"]],["رقم الشيك","Cheque number","ltr","1120885"],["البنك المسحوب عليه","Drawn on bank","sel",["الراجحي","الأهلي"]],["تاريخ الاستحقاق","Due date","date",""],["المبلغ","Amount","ltr","64,200"],["حساب الصرف","Payment account","sel",["210101","210102","5103"]],["الحالة المبدئية","Initial status","sel",["قيد الصرف","مصروف"]]],"بيانات الشيك الصادر","Issued cheque data"]]},"item":{"ar":"صنف","en":"Item","back":"finItems","secs":[[[["كود الصنف","Item code","ltr","ITM-05"],["اسم الصنف","Item name","txt","مستلزمات نظافة"],["التصنيف","Category","sel",["تشغيلية","إدارية"]],["الوحدة","Unit","sel",["حبة","علبة","طقم","كرتون"]],["سعر التكلفة","Cost price","ltr","17"],["سعر البيع","Sale price","ltr","22"],["الحد الأدنى","Minimum level","ltr","60"],["المخزن الافتراضي","Default store","sel",["مخزن الرياض","مخزن جدة","مخزن الدمام"]],["حساب المخزون","Stock account","sel",["1203 / أجهزة وأثاث","5101 / مصروف إعاشة"]],["الحالة","Status","sel",["نشط","موقوف"]]],"بيانات الصنف","Item data"]]},"stock":{"ar":"رصيد افتتاحي","en":"Opening balance","back":"finStock","secs":[[[["الصنف","Item","sel",["مستلزمات إيواء","قرطاسية ومطبوعات","زي موحد"]],["المخزن","Store","sel",["مخزن الرياض","مخزن جدة","مخزن الدمام"]],["السنة المالية","Fiscal year","sel",["2026","2025"]],["الكمية الافتتاحية","Opening qty","ltr","480"],["تكلفة الوحدة","Unit cost","ltr","17"],["تاريخ الرصيد","Balance date","date","2026-01-01"]],"بيانات الرصيد الافتتاحي","Opening balance data"]]},"count":{"ar":"جرد","en":"Inventory count","back":"finInventory","secs":[[[["المخزن","Store","sel",["مخزن الرياض","مخزن جدة","مخزن الدمام"]],["تاريخ الجرد","Count date","date","2026-08-24"],["نوع الجرد","Count type","sel",["جرد كامل","جرد جزئي"]],["المسؤول","Responsible","sel",["سارة القحطاني","خالد العمري"]],["ترحيل الفروقات","Post differences","sel",["تلقائي بعد الاعتماد","يدوي"]],["حساب فروقات الجرد","Difference account","sel",["5203 / اتصالات وأنظمة","5101 / مصروف إعاشة"]]],"بيانات الجرد","Count data"]]},"finclient":{"ar":"عميل مالي","en":"Financial client","back":"finClients","secs":[[[["اسم العميل","Client name","txt","الاسم الثلاثي أو اسم المنشأة"],["النوع","Type","sel",["فرد","شركة"]],["رقم الهوية / السجل","ID / registry","ltr","1042887331"],["الرقم الضريبي","VAT number","ltr","300118822000003"],["الجوال","Phone","ltr","+966 5X XXX XXXX"],["الحساب المحاسبي","Ledger account","sel",["110301","110302","110303"]],["حد الائتمان","Credit limit","ltr","50,000"],["الفرع","Branch","sel",["الفرع الرئيسي","فرع جدة","فرع الدمام"]]],"بيانات العميل المالي","Financial client data"]]},"supplier":{"ar":"مورد","en":"Supplier","back":"finSuppliers","secs":[[[["اسم المورد","Supplier name","txt","اسم المكتب أو المؤسسة"],["الدولة","Country","sel",["الفلبين","إندونيسيا","سريلانكا","السعودية"]],["النوع","Type","sel",["مكتب استقدام","مورد محلي"]],["الحساب المحاسبي","Ledger account","sel",["210101","210102","210103","210104"]],["رقم الترخيص","Licence number","ltr","PH-4412-88"],["الهاتف","Phone","ltr","+63 917 000 1122"],["البريد","Email","txt","office@example.com"],["الرصيد الافتتاحي","Opening balance","ltr","0"]],"بيانات المورد","Supplier data"]]},"agent":{"ar":"وكيل خارجي","en":"External agent","back":"finAgents","secs":[[[["اسم الوكيل","Agent name","txt","اسم الوكيل"],["الدولة","Country","sel",["الفلبين","إندونيسيا","سريلانكا"]],["الهاتف","Phone","ltr","+63 917 000 1122"],["المكتب المرتبط","Linked office","sel",["Manila Prime Agency","Jakarta Care Agency"]],["نسبة العمولة","Commission rate","ltr","5%"],["الحالة","Status","sel",["نشط","موقوف"]]],"بيانات الوكيل","Agent data"]]},"center":{"ar":"مركز تكلفة","en":"Cost centre","back":"finCenters","secs":[[[["كود المركز","Centre code","ltr","CC-05"],["اسم المركز","Centre name","txt","اسم مركز التكلفة"],["المركز الأب","Parent centre","sel",["بدون (مركز رئيسي)","الاستقدام","التأجير","الإيواء","الإدارة"]],["الفرع","Branch","sel",["كل الفروع","الفرع الرئيسي","فرع جدة","فرع الدمام"]],["حد الصرف الشهري","Monthly cap","ltr","600,000"],["تنبيه عند نسبة","Alert at","sel",["80%","90%","100%"]]],"بيانات مركز التكلفة","Cost centre data"]]},"entry":{"ar":"قيد يومي","en":"Journal entry","back":"finEntries","secs":[[[["تاريخ القيد","Entry date","date","2026-08-24"],["رقم القيد","Entry no.","ltr","JV-2026-0413"],["البيان","Description","txt","بيان القيد"],["مركز التكلفة","Cost centre","sel",["بدون","الاستقدام","التأجير","الإيواء","الإدارة"]],["الحساب المدين","Debit account","sel",["1101","110201","110301","5101"]],["مبلغ المدين","Debit amount","ltr","0"],["الحساب الدائن","Credit account","sel",["4101","2102","210101","1101"]],["مبلغ الدائن","Credit amount","ltr","0"]],"بيانات القيد","Entry data"],[[["الحساب","Account","sel",["1101","110201","110301","4101","2102","5101"]],["مدين","Debit","ltr","0"],["دائن","Credit","ltr","0"],["البيان الفرعي","Line description","txt","بيان السطر"]],"أسطر القيد","Entry lines"]]},"bond":{"ar":"سند","en":"Bond","back":"finBonds","secs":[[[["نوع السند","Bond type","sel",["سند قبض","سند صرف"]],["التاريخ","Date","date","2026-08-24"],["الطرف","Party","sel",["منيرة الشهري","Manila Prime Agency","شركة نجد للمقاولات"]],["الحساب المقابل","Counter account","sel",["110301","210101","5101"]],["المبلغ","Amount","ltr","9,250"],["مصدر / جهة الصرف","Source","sel",["الصندوق الرئيسي","بنك الراجحي — 1122","شبكة (مدى)"]],["البيان","Description","txt","بيان السند"]],"بيانات السند","Bond data"]]},"account":{"ar":"حساب","en":"Account","back":"finAccounts","secs":[[[["الحساب الأب","Parent account","sel",["1101 / الصندوق الرئيسي","1102 / البنوك","1103 / العملاء","2101 / الموردون","5101 / مصروف إعاشة"]],["كود الحساب","Account code","ltr","110105"],["اسم الحساب","Account name","txt","اسم الحساب"],["الاسم بالإنجليزية","Name (EN)","txt","Account name"],["طبيعة الحساب","Nature","sel",["مدين","دائن"]],["نوع الحساب","Account type","sel",["أصول","خصوم","حقوق ملكية","إيرادات","مصروفات","عملاء","موردون"]],["المستوى","Level","sel",["3","4","5"]],["نوع العملية","Operation type","sel",["حساب ترحيل (يقبل قيود)","حساب رئيسي (لا يقبل قيود)"]],["مركز التكلفة","Cost centre","sel",["بدون","الاستقدام","التأجير","الإيواء","الإدارة"]],["الرصيد الافتتاحي","Opening balance","ltr","0"]],"بيانات الحساب","Account data"]]}};
let FIENT="sale",FISHOW=null;
const fiFld=(f)=>{const [ar,en,ty,val]=f;
 let ctl;
 if(ty==="sel")ctl=`<select>${val.map(o=>`<option>${o}</option>`).join("")}</select>`;
 else if(ty==="date")ctl=`<input type="date"${val?` value="${val}"`:""}>`;
 else if(ty==="ltr")ctl=`<input type="text" placeholder="${val}" dir="ltr">`;
 else ctl=`<input type="text" placeholder="${val}">`;
 return `<div class="fld"><label>${t(ar,en)}</label>${ctl}</div>`};
const finFormScreens={
 finAdd:()=>{
  const e=fiEnt[FIENT]||fiEnt.sale;
  return `
 <div class="page-head"><div><a class="back" href="#" data-go="${e.back}" style="margin:0 0 8px">${I("arrow-right")}<span>${t("رجوع","Back")}</span></a><h1>${t("إضافة "+e.ar,"Add "+e.en)}</h1><p>${t("تُرحَّل البيانات إلى شجرة الحسابات حسب قواعد الترحيل المحاسبي.","Data posts to the chart of accounts per the accounting posting rules.")}</p></div>
 <div class="actions"><button class="btn btn-ghost" data-go="${e.back}">${t("إلغاء","Cancel")}</button><button class="btn btn-primary" data-fisave="${e.back}">${I("check")}${t("حفظ","Save")}</button></div></div>
 <div class="cvform">
  <aside class="cvopts">
   <div class="card"><div class="sec-h">${I("list-checks")}<b>${t("أقسام النموذج","Form sections")}</b></div>
    <div class="steps">${e.secs.map((s,i)=>`<button class="step" data-fijump="fs${i}"><span class="stepn num">${i+1}</span><span>${t(s[1],s[2])}</span></button>`).join("")}</div></div>
   <div class="card"><div class="sec-h">${I("info")}<b>${t("ملاحظة","Note")}</b></div>
    <p class="pnote plain">${t("الحقول المطلوبة تُحدَّد من قواعد النظام، ويُنشأ القيد المحاسبي تلقائياً بعد الحفظ.","Required fields follow the system rules; the journal entry is created automatically after saving.")}</p></div>
  </aside>
  <div class="cvfields">
   ${e.secs.map((s,i)=>`<div class="card sec" id="fs${i}"><div class="fs-h"><span class="fs-n num">${i+1}</span><b>${t(s[1],s[2])}</b></div><div class="fgrid">${s[0].map(fiFld).join("")}</div></div>`).join("")}
   <div class="card sec"><div class="fs-h"><span class="fs-n num">${e.secs.length+1}</span><b>${t("ملاحظات ومرفقات","Notes & attachments")}</b></div>
    <div class="fld full"><label>${t("ملاحظات","Notes")}</label><textarea rows="2"></textarea></div>
    <div class="fld full"><label>${t("مرفقات","Attachments")}</label><div class="ctl file">${I("upload")}<span>${t("اختر الملفات — يمكن تحديد أكثر من ملف","Select files — multiple allowed")}</span></div></div></div>
   <div class="formbar"><button class="btn btn-ghost" data-go="${e.back}">${t("إلغاء","Cancel")}</button><button class="btn btn-primary" data-fisave="${e.back}">${I("check")}${t("حفظ","Save")}</button></div>
  </div>
 </div>`},
 finShow:()=>{
  const d=FISHOW||{ent:"sale",title:"—",back:"finSales",kv:[],rows:null};
  const e=fiEnt[d.ent]||fiEnt.sale;
  return `
 <div class="page-head compact"><div><a class="back" href="#" data-go="${d.back}" style="margin:0 0 8px">${I("arrow-right")}<span>${t("رجوع","Back")}</span></a><h1>${d.title}</h1><p>${t(e.ar,e.en)}${d.sub?" · "+d.sub:""}</p></div>
 <div class="actions"><button class="btn btn-ghost">${I("printer")}${t("طباعة","Print")}</button><button class="btn btn-primary" data-fiaddent="${d.ent}">${I("pencil")}${t("تعديل","Edit")}</button></div></div>
 ${d.stats?fiPipe(d.stats):""}
 <div class="card sec"><div class="sec-h">${I("file-text")}<b>${t("البيانات","Details")}</b></div>
  <div class="kvgrid">${d.kv.map(x=>`<div class="kv"><span>${x[0]}</span><b>${x[1]}</b></div>`).join("")}</div></div>
 ${d.rows?`<div class="card sec"><div class="sec-h">${I("list")}<b>${t(d.rowsTitle||"البنود","Lines")}</b></div>
  <div class="tw"><table class="rep"><thead><tr>${d.rowsCols.map(c=>`<th>${c}</th>`).join("")}</tr></thead><tbody>${d.rows.map(r=>`<tr>${r.map(c=>`<td>${c}</td>`).join("")}</tr>`).join("")}</tbody></table></div></div>`:""}
 <div class="card sec"><div class="sec-h">${I("history")}<b>${t("السجل","Activity log")}</b></div>
  <div class="tl">${[["2026-08-24 09:12",t("أُنشئ السجل","Record created"),t("قسم المالية","Finance")],["2026-08-24 09:14",t("تم الترحيل المحاسبي","Posted to the ledger"),t("النظام","System")]].map(x=>`<div class="tli"><span class="dot"></span><div><b>${x[1]}</b><small>${x[2]}</small><small class="num">${x[0]}</small></div></div>`).join("")}</div></div>`}
};
/* ---------- cost centre tree ---------- */
const ccTree=[
 ["CC-01","الاستقدام","Recruitment",2106600,1284600,1400000,[
  ["CC-0101","تأشيرات ورسوم","Visas & fees",0,659400,700000,[]],
  ["CC-0102","تذاكر وسفر","Tickets & travel",0,394200,420000,[]],
  ["CC-0103","عمولات المكاتب","Office commissions",0,231000,280000,[]]]],
 ["CC-02","التأجير","Rental",665800,318400,400000,[
  ["CC-0201","رواتب عاملات التأجير","Rental worker salaries",0,214800,260000,[]],
  ["CC-0202","نقل وتوصيل","Transport",0,103600,140000,[]]]],
 ["CC-03","الإيواء","Shelter",0,672200,600000,[
  ["CC-0301","إعاشة","Living expense",0,272200,240000,[]],
  ["CC-0302","إيجار المقار","Facility rent",0,264000,264000,[]],
  ["CC-0303","فحوصات طبية","Medical exams",0,136000,96000,[]]]],
 ["CC-04","الإدارة","Administration",48200,504800,520000,[
  ["CC-0401","رواتب إدارية","Admin salaries",0,384000,400000,[]],
  ["CC-0402","اتصالات وأنظمة","Telecom & systems",0,92200,80000,[]],
  ["CC-0403","مصروفات متنوعة","Misc expenses",0,28600,40000,[]]]]];
let CCOPEN={},CCQ="";
const ccSums=n=>{let r=n[3],e=n[4];const walk=k=>k.forEach(x=>{r+=x[3];e+=x[4];walk(x[6])});walk(n[6]);return [r,e]};
const ccMatch=n=>{const q=CCQ.trim().toLowerCase();return !q||(n[0]+n[1]+n[2]).toLowerCase().includes(q)||n[6].some(ccMatch)};
const ccNode=(n,depth)=>{
 const has=n[6].length,open=CCOPEN[n[0]]!==undefined?CCOPEN[n[0]]:depth===0;
 const [rev,exp]=ccSums(n),net=rev-exp,pct=n[5]?Math.round(exp/n[5]*100):0;
 const tone=pct>100?"r":pct>=90?"a":"g";
 return `<div class="ccnode">
  <div class="ccrow" style="--d:${depth}">
   ${has?`<button class="actog" data-cctoggle="${n[0]}">${I(open?"chevron-down":"chevron-left")}</button>`:'<span class="actog dot"></span>'}
   <span class="acicon ${has?"folder":"leaf"}">${I(has?"target":"dot")}</span>
   <button class="accode num" data-ccstat="${n[0]}">${n[0]}</button>
   <span class="acname">${t(n[1],n[2])}</span>
   <span class="cctrail">
    <span class="ccm"><small>${t("إيراد","Rev")}</small>${acMoney(rev)}</span>
    <span class="ccm"><small>${t("مصروف","Exp")}</small>${acMoney(exp)}</span>
    <span class="ccm net ${net<0?"neg":""}"><small>${t("الصافي","Net")}</small>${acMoney(net)}</span>
    <span class="ccbudget ${tone}"><span class="track"><i style="width:${Math.min(100,pct)}%"></i></span><b class="num">${pct}%</b><small>${t("من الحد","of cap")} ${n[5].toLocaleString("en")}</small></span>
    <span class="acacts"><button class="icon-btn pri" data-fiaddent="center" title="${t("إضافة مركز فرعي","Add sub-centre")}">${I("plus")}</button><button class="icon-btn pri" title="${t("تعديل","Edit")}">${I("pencil")}</button><button class="icon-btn" data-frrep="account_cost_centers" title="${t("تقرير المركز","Centre report")}">${I("bar-chart-3")}</button></span>
   </span>
  </div>
  ${has&&open?`<div class="acbranch">${n[6].filter(ccMatch).map(k=>ccNode(k,depth+1)).join("")}</div>`:""}
 </div>`};
finFormScreens.finCentersTree=()=>{
 const totals=ccTree.reduce((a,n)=>{const [r,e]=ccSums(n);return [a[0]+r,a[1]+e]},[0,0]);
 const over=ccTree.filter(n=>{const [,e]=ccSums(n);return e>n[5]}).length;
 return `
 <div class="page-head"><div><h1>${t("شجرة مراكز التكلفة","Cost centre tree")}</h1><p>${t("المراكز الرئيسية وفروعها — الإيراد والمصروف يُجمَّعان من المراكز الفرعية، والحد الشهري يُقارن بالمصروف.","Parent centres and their branches — revenue and expenses roll up, and the monthly cap is compared to spend.")}</p></div>
 <div class="actions"><button class="btn btn-ghost" data-go="finCenters">${I("table")}${t("عرض جدولي","Table view")}</button><button class="btn btn-ghost" data-frrep="cost_centers">${I("bar-chart-3")}${t("التقرير","Report")}</button><button class="btn btn-primary" data-fiaddent="center">${I("plus")}${t("مركز تكلفة","Cost centre")}</button></div></div>
 ${fiPipe([[t("مراكز رئيسية","Parent centres"),ccTree.length,"target","run"],[t("إجمالي الإيرادات","Revenue"),totals[0],"trending-up","post"],[t("إجمالي المصروفات","Expenses"),totals[1],"trending-down","closed"],[t("مراكز تجاوزت الحد","Over cap"),over,"alert-triangle","closed"]])}
 <div class="fbar"><div class="fb grow"><label>${t("بحث","Search")}</label><div class="inp">${I("search")}<input data-ccq value="${CCQ}" placeholder="${t("كود المركز أو اسمه","Centre code or name")}"></div></div>
  <div class="fb"><label>${t("الفترة","Period")}</label><select><option>${t("هذا الشهر","This month")}</option><option>${t("هذا الربع","This quarter")}</option><option>${t("هذه السنة","This year")}</option></select></div>
  <div class="fb"><label>${t("الفرع","Branch")}</label><select><option>${t("كل الفروع","All branches")}</option><option>${t("الفرع الرئيسي","Main branch")}</option><option>${t("فرع جدة","Jeddah")}</option></select></div>
  <button class="btn btn-ghost" data-ccexpand>${I("unfold-vertical")}${t("فتح الكل","Expand all")}</button>
  <button class="btn btn-ghost" data-cccollapse>${I("fold-vertical")}${t("طي الكل","Collapse all")}</button></div>
 <div class="card flush"><div class="card-h"><div><h3>${t("المراكز","Centres")}</h3><p>${t("الأخضر داخل الحد، البرتقالي قريب منه، الأحمر متجاوز.","Green within cap, amber close, red over.")}</p></div>
  <div class="acsum"><span>${t("إجمالي الإيراد","Total revenue")} ${acMoney(totals[0])}</span><span>${t("إجمالي المصروف","Total expense")} ${acMoney(totals[1])}</span></div></div>
  <div class="actree">${ccTree.filter(ccMatch).map(n=>ccNode(n,0)).join("")}</div></div>`;
};
Object.assign(T,{finAdd:{ar:"إضافة",en:"Add"},finShow:{ar:"عرض",en:"Details"},finCentersTree:{ar:"شجرة مراكز التكلفة",en:"Cost centre tree"}});
Object.assign(screens,finFormScreens);
(function(){const g=nav.find(x=>x.t.ar==="الإدارة");if(!g)return;const f=g.items.find(x=>x.id==="finance");if(!f)return;
 const i=f.kids.findIndex(k=>k.id==="finCenters");if(i>=0)f.kids.splice(i+1,0,{id:"finCentersTree",ar:"شجرة مراكز التكلفة",en:"Cost centre tree"});})();
document.addEventListener("click",e=>{
 const ad=e.target.closest("[data-fiaddent]");if(ad){FIENT=ad.dataset.fiaddent;cur="finAdd";render();window.scrollTo(0,0);return}
 const jp=e.target.closest("[data-fijump]");if(jp){const el=document.getElementById(jp.dataset.fijump);if(el)window.scrollTo({top:el.offsetTop-90,behavior:"smooth"});return}
 const sv=e.target.closest("[data-fisave]");if(sv){cur=sv.dataset.fisave;render();window.scrollTo(0,0);clToast("تم الحفظ والترحيل المحاسبي","Saved and posted");return}
 const sh=e.target.closest("[data-fishow]");if(sh){try{FISHOW=JSON.parse(sh.dataset.fishow)}catch(err){FISHOW=null}cur="finShow";render();window.scrollTo(0,0);return}
 const ct=e.target.closest("[data-cctoggle]");if(ct){const k=ct.dataset.cctoggle;CCOPEN[k]=CCOPEN[k]===undefined?false:!CCOPEN[k];render();return}
 if(e.target.closest("[data-ccexpand]")){const walk=ns=>ns.forEach(n=>{if(n[6].length){CCOPEN[n[0]]=true;walk(n[6])}});walk(ccTree);render();return}
 if(e.target.closest("[data-cccollapse]")){const walk=ns=>ns.forEach(n=>{if(n[6].length){CCOPEN[n[0]]=false;walk(n[6])}});walk(ccTree);render();return}
 const cs=e.target.closest("[data-ccstat]");if(cs){FRID="account_cost_centers";cur="finReport";render();window.scrollTo(0,0);return}
});
document.addEventListener("input",e=>{if(e.target.matches("[data-ccq]")){CCQ=e.target.value;const p=e.target.selectionStart;render();const n=document.querySelector("[data-ccq]");if(n){n.focus();n.setSelectionRange(p,p)}}});

/* ===== clickerp-rental-app.js ===== */
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

/* ===== clickerp-rental-print.js ===== */
/* Rental print documents — contract (with terms + signatures), invoice, receipt bond */
const rnAr=n=>{ // amount in Arabic words (whole SAR)
 const ones=["","واحد","اثنان","ثلاثة","أربعة","خمسة","ستة","سبعة","ثمانية","تسعة","عشرة","أحد عشر","اثنا عشر","ثلاثة عشر","أربعة عشر","خمسة عشر","ستة عشر","سبعة عشر","ثمانية عشر","تسعة عشر"];
 const tens=["","","عشرون","ثلاثون","أربعون","خمسون","ستون","سبعون","ثمانون","تسعون"];
 const huns=["","مئة","مئتان","ثلاثمئة","أربعمئة","خمسمئة","ستمئة","سبعمئة","ثمانمئة","تسعمئة"];
 const part=x=>{let s=[];if(x>=100){s.push(huns[Math.floor(x/100)]);x%=100}
  if(x>=20){const u=x%10;s.push(u?ones[u]+" و"+tens[Math.floor(x/10)]:tens[Math.floor(x/10)])}
  else if(x>0)s.push(ones[x]);return s.join(" و")};
 if(n===0)return "صفر";
 const th=Math.floor(n/1000),rest=n%1000;let out=[];
 if(th===1)out.push("ألف");else if(th===2)out.push("ألفان");else if(th>2&&th<11)out.push(part(th)+" آلاف");else if(th>10)out.push(part(th)+" ألفاً");
 if(rest)out.push(part(rest));
 return out.join(" و")+" ريال سعودي فقط";
};
const rnDocHead=(title,sub,no,date)=>`
 <header class="ps-head">
  <div class="ps-brand"><img src="assets/logo-light-bg.png" alt="Click &amp; More"><div><b>مؤسسة كليك آند مور للاستقدام</b><small class="ps-ph">سجل تجاري [يُستكمل من إعدادات النظام] · الرقم الضريبي [يُستكمل]</small><small class="ps-ph">العنوان [يُستكمل] · هاتف [يُستكمل] · البريد [يُستكمل]</small></div></div>
  <div class="ps-meta"><h1>${title}</h1><small>${sub}</small><div class="ps-nos"><span>رقم المستند: <b class="num">${no}</b></span><span>التاريخ: <b class="num">${date}</b></span></div></div>
 </header>`;
const rnDocFoot=`<footer class="ps-foot"><span>مؤسسة كليك آند مور للاستقدام — جميع الحقوق محفوظة</span><span class="ps-ph">[بيانات التواصل تُستكمل من إعدادات النظام]</span></footer>`;
const rnPrintDoc=(kind,r)=>{
 const inv=(typeof rnInv!=="undefined"?rnInv.find(x=>x[1]===r[0]):null)||["INV-RC-"+r[0].slice(-4),r[0],r[1],r[34],r[22],Math.round(r[22]*.15),0,r[23],"جزئي","Partial","a"];
 const rowsTbl=(head,body)=>`<table class="ps-t"><thead><tr>${head.map(h=>`<th>${h}</th>`).join("")}</tr></thead><tbody>${body}</tbody></table>`;
 const kv=(fs)=>`<table class="ps-kv">${fs.map(f=>`<tr><th>${f[0]}</th><td>${f[1]}</td></tr>`).join("")}</table>`;
 if(kind==="contract")return `
 <article class="printsheet">
  ${rnDocHead("عقد تأجير عاملة منزلية","عقد تأجير خدمات عاملة — نسخة معتمدة للطباعة والتوقيع",r[0],r[34])}
  <section class="ps-sec"><h2>أطراف العقد</h2>
   <div class="ps-two">
    <div class="ps-party"><span class="ps-tag">الطرف الأول</span><b>مؤسسة كليك آند مور للاستقدام</b>
     ${kv([["السجل التجاري",'<span class="ps-ph">[يُستكمل من إعدادات النظام]</span>'],["الرقم الضريبي",'<span class="ps-ph">[يُستكمل من إعدادات النظام]</span>'],["العنوان",'<span class="ps-ph">[يُستكمل]</span>'],["يمثلها","المسوّق المعتمد / "+r[27]]])}</div>
    <div class="ps-party"><span class="ps-tag">الطرف الثاني</span><b>${r[1]}</b>
     ${kv([[r[2]==="company"?"الرقم الضريبي":"رقم الهوية",`<span class="num">${r[4]}</span>`],["رقم الجوال",`<span class="num">${r[3]}</span>`],["المدينة",r[5]],["نوع العميل",r[2]==="company"?"شركة / مؤسسة":"فرد"]])}</div>
   </div>
   <p class="ps-p">اتفق الطرفان وهما بكامل الأهلية المعتبرة شرعاً ونظاماً على تأجير خدمات العاملة الموضحة بياناتها أدناه، وفق البنود والشروط الواردة في هذا العقد.</p>
  </section>
  <section class="ps-sec"><h2>بيانات العاملة</h2>
   ${rowsTbl(["الاسم","الاسم بالإنجليزية","رقم الجواز","الجنسية","المهنة","الديانة"],`<tr><td><b>${r[6]}</b></td><td class="num">${r[7]}</td><td class="num">${r[8]}</td><td>${r[9]}</td><td>${r[10]}</td><td>مسيحية</td></tr>`)}
  </section>
  <section class="ps-sec"><h2>بيانات العقد المالية والتشغيلية</h2>
   <div class="ps-two">
    ${kv([["الباقة",r[11]],["مدة العقد",`<span class="num">${r[12]}</span> شهر (<span class="num">${r[13]}</span> يوم)`],["بداية العقد",`<span class="num">${r[14]}</span>`],["نهاية العقد",`<span class="num">${r[15]}</span>`],["ساعات العمل اليومية",`<span class="num">${r[18]}</span> ساعة`],["أجرة الساعة",`<span class="num">${r[19]}</span> ريال`]])}
    ${kv([["راتب العاملة الشهري",`<span class="num">${r[20].toLocaleString("en")}</span> ريال`],["التكلفة الشهرية",`<span class="num">${r[21].toLocaleString("en")}</span> ريال`],["إجمالي قيمة العقد",`<span class="num">${r[22].toLocaleString("en")}</span> ريال`],["المدفوع",`<span class="num">${r[23].toLocaleString("en")}</span> ريال`],["المتبقي",`<span class="num">${r[24].toLocaleString("en")}</span> ريال`],["طريقة الدفع",r[29]+" — "+r[30]]])}
   </div>
   <p class="ps-total">إجمالي قيمة العقد كتابةً: <b>${rnAr(r[22])}</b></p>
   ${r[32]?`<p class="ps-note">نقل الكفالة: مبلغ النقل <b class="num">${r[32].toLocaleString("en")}</b> ريال، والراتب بعد النقل <b class="num">${r[33].toLocaleString("en")}</b> ريال.</p>`:""}
  </section>
  ${rnTermPages.map(pg=>`<section class="ps-sec ps-termpage${pg>1?" ps-break":""}"><h2>الشروط والأحكام — الصفحة ${["الأولى","الثانية","الثالثة"][pg-1]} <small class="ps-pg">صفحة ${pg} من ${rnTermPages.length}</small></h2>
   ${rnTermSections(pg).map(g=>`<div class="ps-termsec"><h3>${g.title}</h3><ol class="ps-terms">${g.items.map(x=>`<li value="${x[3]}">${x[4]}</li>`).join("")}</ol></div>`).join("")}
  </section>`).join("")}
  <section class="ps-sec ps-sign"><h2>التوقيعات</h2>
   <div class="ps-two">
    <div class="ps-sig"><span>الطرف الأول — مؤسسة كليك آند مور</span><i class="ps-line"></i><small>الاسم والتوقيع والختم</small>${r[26]==="both"?'<span class="ps-stamp">موقّع ومُعتمد</span>':""}</div>
    <div class="ps-sig"><span>الطرف الثاني — ${r[1]}</span><i class="ps-line"></i><small>الاسم والتوقيع · التاريخ</small>${["client","both"].includes(r[26])?`<span class="ps-stamp">موقّع في ${r[34]}</span>`:""}</div>
   </div>
   <p class="ps-note">حُرِّر هذا العقد من نسختين، بيد كل طرف نسخة للعمل بموجبها.</p>
  </section>
  ${rnDocFoot}
 </article>`;
 if(kind==="invoice"){const rem=inv[4]-inv[6]-inv[7],net=inv[4]-inv[5];
  return `
 <article class="printsheet">
  ${rnDocHead("فاتورة ضريبية","Tax Invoice — عقد تأجير",inv[0],inv[3])}
  <section class="ps-sec"><div class="ps-two">
   ${kv([["العميل",`<b>${r[1]}</b>`],[r[2]==="company"?"الرقم الضريبي":"رقم الهوية",`<span class="num">${r[4]}</span>`],["رقم الجوال",`<span class="num">${r[3]}</span>`],["المدينة",r[5]]])}
   ${kv([["رقم العقد",`<span class="num">${r[0]}</span>`],["الباقة",r[11]],["الفترة",`<span class="num">${r[14]} → ${r[15]}</span>`],["المسوّق / الفرع",r[27]+" — "+r[28]]])}
  </div></section>
  <section class="ps-sec"><h2>بنود الفاتورة</h2>
   ${rowsTbl(["#","الوصف","المدة","السعر قبل الضريبة","الضريبة 15%","الإجمالي"],`<tr><td class="num">1</td><td>تأجير خدمات عاملة منزلية — باقة ${r[11]}</td><td class="num">${r[13]} يوم</td><td class="num">${net.toLocaleString("en")}</td><td class="num">${inv[5].toLocaleString("en")}</td><td class="num">${inv[4].toLocaleString("en")}</td></tr>`)}
   <div class="ps-sum">
    <table class="ps-kv ps-tot">
     <tr><th>الإجمالي قبل الضريبة</th><td class="num">${net.toLocaleString("en")}</td></tr>
     <tr><th>الخصم</th><td class="num">${inv[6].toLocaleString("en")}</td></tr>
     <tr><th>ضريبة القيمة المضافة (15%)</th><td class="num">${inv[5].toLocaleString("en")}</td></tr>
     <tr class="ps-grand"><th>الإجمالي المستحق</th><td class="num">${inv[4].toLocaleString("en")}</td></tr>
     <tr><th>المدفوع</th><td class="num">${inv[7].toLocaleString("en")}</td></tr>
     <tr class="ps-due"><th>المتبقي</th><td class="num">${rem.toLocaleString("en")}</td></tr>
    </table>
    <div class="ps-qr"><span class="qrbox">${I("qr-code")}</span><small>رمز الفاتورة الضريبية<br>ZATCA QR</small></div>
   </div>
   <p class="ps-total">الإجمالي كتابةً: <b>${rnAr(inv[4])}</b></p>
  </section>
  <section class="ps-sec ps-sign"><div class="ps-two">
   <div class="ps-sig"><span>المحاسب</span><i class="ps-line"></i><small>الاسم والتوقيع</small></div>
   <div class="ps-sig"><span>العميل</span><i class="ps-line"></i><small>الاسم والتوقيع</small></div>
  </div></section>
  ${rnDocFoot}
 </article>`}
 return `
 <article class="printsheet ps-short">
  ${rnDocHead("سند قبض","Receipt Bond — دفعة عقد تأجير","BND-"+r[0].slice(-4),r[34])}
  <section class="ps-sec">
   ${kv([["استلمنا من السيد/السادة",`<b>${r[1]}</b>`],["مبلغاً وقدره",`<b class="num">${r[23].toLocaleString("en")}</b> ريال سعودي`],["وذلك عن","دفعة من قيمة عقد تأجير رقم "+r[0]+" — باقة "+r[11]],["طريقة السداد",r[29]],["الحساب / الصندوق",typeof rnAccounts!=="undefined"?rnAccounts[1]:"—"],["حالة السداد",r[30]],["المتبقي على العميل",`<b class="num">${r[24].toLocaleString("en")}</b> ريال`]])}
   <p class="ps-total">المبلغ كتابةً: <b>${rnAr(r[23])}</b></p>
  </section>
  <section class="ps-sec ps-sign"><div class="ps-two">
   <div class="ps-sig"><span>أمين الصندوق / المحاسب</span><i class="ps-line"></i><small>الاسم والتوقيع</small></div>
   <div class="ps-sig"><span>المستلم منه</span><i class="ps-line"></i><small>الاسم والتوقيع</small></div>
  </div></section>
  ${rnDocFoot}
 </article>`;
};
function rnOpenPrint(kind,id){
 const r=rnRows.find(x=>x[0]===id)||rnRows[0];
 let ov=document.getElementById("rnPrintOv");
 if(!ov){ov=document.createElement("div");ov.id="rnPrintOv";ov.className="printov";document.body.appendChild(ov)}
 const titles={contract:["عقد التأجير","Rental contract"],invoice:["الفاتورة الضريبية","Tax invoice"],bond:["سند القبض","Receipt bond"]}[kind];
 ov.innerHTML=`<div class="printwrap">
  <div class="printbar">
   <div class="pb-t">${I("printer")}<div><b>${t(titles[0],titles[1])}</b><small>${r[0]} · ${r[1]}</small></div></div>
   <div class="pb-tabs">${[["contract","عقد التأجير","Contract"],["invoice","الفاتورة","Invoice"],["bond","سند القبض","Receipt bond"]].map(x=>`<button class="${x[0]===kind?"on":""}" data-rnprint="${x[0]}:${r[0]}">${t(x[1],x[2])}</button>`).join("")}</div>
   <div class="pb-a"><button class="btn btn-ghost" data-rnprintclose>${I("x")}${t("إغلاق","Close")}</button><button class="btn btn-primary" data-rndoprint>${I("printer")}${t("طباعة","Print")}</button></div>
  </div>
  <div class="printscroll">${rnPrintDoc(kind,r)}</div>
 </div>`;
 ov.classList.remove("hidden");document.body.classList.add("printing");lucide.createIcons({attrs:{"stroke-width":1.8}});
}
document.addEventListener("click",e=>{
 const pr=e.target.closest("[data-rnprint]");if(pr){const [k,id]=pr.dataset.rnprint.split(":");rnOpenPrint(k,id);return}
 if(e.target.closest("[data-rnprintclose]")){const ov=document.getElementById("rnPrintOv");if(ov)ov.classList.add("hidden");document.body.classList.remove("printing");return}
 if(e.target.closest("[data-rndoprint]")){window.print();return}
 const tp=e.target.closest("[data-rntermpage]");if(tp){RNTERMPAGE=+tp.dataset.rntermpage;render();window.scrollTo(0,0);return}
 if(e.target.closest("[data-rntermsave]")){clToast("تم حفظ بنود العقد","Contract terms saved");return}
 const tb=e.target.closest("[data-rntab]");if(tb){RNTAB=tb.dataset.rntab;RNSTATUS="";render();return}
 const st=e.target.closest("[data-rnstat]");if(st){const k=st.dataset.rnstat;RNSTATUS=rnSt.some(x=>x[0]===k)?(RNSTATUS===k?"":k):"";render();return}
 const mf=e.target.closest("[data-rnmore]");if(mf){RNMORE=!RNMORE;render();return}
 const sh=e.target.closest("[data-rnshow]");if(sh){RNID=sh.dataset.rnshow;RNSTAB="over";cur="rentalShow";render();window.scrollTo(0,0);return}
 const stb=e.target.closest("[data-rnstab]");if(stb){RNSTAB=stb.dataset.rnstab;render();window.scrollTo(0,0);return}
 const ed=e.target.closest("[data-rnedit]");if(ed){RNEDIT=ed.dataset.rnedit;cur="rentalAdd";render();window.scrollTo(0,0);return}
 const at=e.target.closest("[data-rnaddtab]");if(at){RNADDTAB=at.dataset.rnaddtab;render();return}
 const jp=e.target.closest("[data-rnjump]");if(jp){const el=document.getElementById(jp.dataset.rnjump);if(el)window.scrollTo({top:el.offsetTop-90,behavior:"smooth"});return}
 const sv=e.target.closest("[data-rnsave]");if(sv){const back=RNEDIT;RNEDIT=null;if(back){RNID=back;cur="rentalShow";render();window.scrollTo(0,0);clToast("تم حفظ تعديلات العقد","Contract changes saved")}else{cur="rental";render();window.scrollTo(0,0);clToast("تم حفظ العقد وإصدار الفاتورة وسند القبض","Contract saved; invoice and receipt bond issued");rnOpenPrint("contract",RNID)}return}
 const mn=e.target.closest("[data-rnmenu]");if(mn){const id=mn.dataset.rnmenu;RNMENU=RNMENU===id?null:id;render();if(RNMENU)rnPlaceMenu(RNMENU);return}
 const ac=e.target.closest("[data-rnact]");
 if(ac){const [k,id]=ac.dataset.rnact.split(":");const r=rnRows.find(x=>x[0]===id);RNMENU=null;clKillMenus();
  if(k==="printer"){rnOpenPrint("contract",id);return}
  if(k==="receipt"){rnOpenPrint("invoice",id);return}
  if(k==="banknote"){rnOpenPrint("bond",id);return}
  if(k==="pen-line"){r[26]=r[26]==="unsigned"?"client":"both";render();clToast(r[26]==="both"?"تم توقيع العقد من الطرفين":"تم تسجيل توقيع العميل",r[26]==="both"?"Signed by both parties":"Client signature recorded");return}
  if(k==="calendar-plus"){rnExtendModal(r);return}
  if(k==="check-circle-2"){r[25]="completed";r[17]=0;render();clToast("تم إغلاق العقد","Contract closed");return}
  if(k==="arrow-left-right"){r[25]="moved";render();clToast("تم تحويل العقد إلى نقل كفالة","Converted to a sponsorship transfer");return}
  if(k==="x-circle"){r[25]="cancelled";render();clToast("تم إلغاء العقد قبل اكتمال المدة","Contract cancelled early");return}
  clToast("تم تنفيذ الإجراء","Action completed");return}
 const ec=e.target.closest("[data-rnextclose]"),ed2=e.target.closest("[data-rnextdone]");
 if(ec||ed2){const ov=document.getElementById("rnExtOv");if(ov)ov.classList.add("hidden");
  if(ed2){const r=rnRows.find(x=>x[0]===ed2.dataset.rnextdone);if(r){r[31]++;r[13]+=30;r[17]+=30;r[22]+=Math.round(r[22]/(r[13]-30))*30;render()}clToast("تم تمديد العقد 30 يوماً","Contract extended by 30 days")}return}
 if(RNMENU&&!e.target.closest(".kebab")&&!e.target.closest(".amenu")){RNMENU=null;clKillMenus();render()}
 const gg=e.target.closest("[data-go]");if(gg&&gg.dataset.go!=="rentalAdd")RNEDIT=null;
});
function rnPlaceMenu(id){requestAnimationFrame(()=>{const btn=document.querySelector(`[data-rnmenu="${id}"]`),menu=document.querySelector(`[data-rnmenu-for="${id}"]`);if(!btn||!menu)return;const b=btn.getBoundingClientRect();document.body.appendChild(menu);menu.classList.remove("hidden");menu.style.position="fixed";menu.style.zIndex="200";menu.style.insetInlineEnd="auto";const h=menu.offsetHeight,w=menu.offsetWidth;let top=b.bottom+6;if(top+h>innerHeight-8)top=Math.max(8,b.top-h-6);let left=document.body.dir==="rtl"?b.left:b.right-w;left=Math.min(Math.max(8,left),innerWidth-w-8);menu.style.top=top+"px";menu.style.left=left+"px";lucide.createIcons({attrs:{"stroke-width":1.8}})})}
function rnExtendModal(r){
 let ov=document.getElementById("rnExtOv");if(!ov){ov=document.createElement("div");ov.id="rnExtOv";ov.className="ov";document.body.appendChild(ov)}
 ov.innerHTML=`<div class="modal"><div class="modal-h"><div><h3>${t("تمديد عقد التأجير","Extend rental contract")}</h3><p class="ms">${r[0]} · ${r[1]} · ${r[6]}</p></div><button class="icon-btn" data-rnextclose>${I("x")}</button></div>
 <div class="modal-b">
  <div class="ovgrid four">${[["بداية العقد","Contract start",r[14]],["نهاية العقد الحالية","Current end",r[15]],["مدة العقد الحالية","Current duration",r[13]+" "+t("يوم","d")],["الأيام المستخدمة","Used days",String(r[16])],["الأيام المتبقية / التأخير","Remaining / overdue",String(r[17])],["قيمة العقد الحالية","Current value",r[22].toLocaleString("en")],["تكلفة اليوم التقريبية","Approx. day cost",String(Math.round(r[22]/r[13]))],["عدد مرات التمديد","Extensions",String(r[31])]].map(x=>`<div class="ovc"><span class="ovl">${t(x[0],x[1])}</span><b class="ovv num">${x[2]}</b></div>`).join("")}</div>
  <div class="fgrid two" style="margin-top:16px">
   <div class="fld"><label>${t("عدد أيام التمديد","Extension days")} <i class="rq">*</i><code>extension_days</code></label><select><option>30</option><option>60</option><option>90</option></select></div>
   <div class="fld"><label>${t("النهاية بعد التمديد","End after extension")}<code>new_end_date</code></label><input type="date" value="2026-10-28"></div>
   <div class="fld"><label>${t("تكلفة التمديد","Extension cost")}<code>extension_cost</code></label><input type="text" value="${(Math.round(r[22]/r[13])*30).toLocaleString("en")}" dir="ltr"></div>
   <div class="fld"><label>${t("طريقة الدفع","Payment method")}<code>payment_type</code></label><select>${rnPayTypes.map(x=>`<option${x===r[29]?" selected":""}>${x}</option>`).join("")}</select></div>
  </div>
  <p class="pnote">${I("info")}${t("يُصدر التمديد فاتورة إضافية وسند قبض جديد، ولا يُعدَّل تاريخ بداية العقد.","The extension issues an additional invoice and a new receipt bond; the contract start date is unchanged.")}</p>
 </div>
 <div class="modal-f"><button class="btn btn-ghost" data-rnextclose>${t("إلغاء","Cancel")}</button><button class="btn btn-primary" data-rnextdone="${r[0]}">${I("calendar-plus")}${t("تنفيذ التمديد","Apply extension")}</button></div></div>`;
 ov.classList.remove("hidden");lucide.createIcons({attrs:{"stroke-width":1.8}});
}
document.addEventListener("input",e=>{if(e.target.matches("[data-rnq]")){RNQ=e.target.value;const p=e.target.selectionStart;render();const n=document.querySelector("[data-rnq]");if(n){n.focus();n.setSelectionRange(p,p)}}});
document.addEventListener("change",e=>{
 if(e.target.matches("[data-rnstatus]")){RNSTATUS=e.target.value;render()}
 if(e.target.matches("[data-rnpkg]")){RNPKG=e.target.value;render()}
});

/* ===== clickerp-orders.js ===== */
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

/* ===== clickerp-nav2.js ===== */
/* Sidebar: dept rail + pages panel, with user-pinned main screens */
(()=>{
const all={};nav.forEach(g=>g.items.forEach(it=>all[it.id]=it));
const DEPTS=[
 {id:"ops",i:"briefcase",ar:"العمليات",en:"Operations",items:["contracts","orders","cvs","rental","housing","transfer"]},
 {id:"crm",i:"users",ar:"العملاء",en:"Customers",items:["clients","msgs","claims"]},
 {id:"finance",i:"wallet",ar:"المالية",en:"Finance",kids:all.finance.kids},
 {id:"hr",i:"user-cog",ar:"الموارد البشرية",en:"HR",kids:all.hr.kids},
 {id:"settings",i:"settings",ar:"إعدادات النظام",en:"Settings",kids:all.settings.kids}];
const pages=d=>d.kids?d.kids.filter(k=>!k.sec):d.items.map(id=>all[id]);
const deptOf=c=>DEPTS.find(d=>pages(d).some(p=>p.id===c)||d.id===c);
const PG={};DEPTS.forEach(d=>pages(d).forEach(p=>PG[p.id]={...p,i:p.i||d.i}));PG.reports=all.reports;
const PK="clickerp-pins";
let PINS;try{PINS=JSON.parse(localStorage.getItem(PK))}catch(e){}
if(!Array.isArray(PINS))PINS=["contracts","orders","cvs","clients","transfer","reports"];
PINS=PINS.filter(id=>PG[id]);
const savePins=()=>{try{localStorage.setItem(PK,JSON.stringify(PINS))}catch(e){}};
let DEPT="ops",LAST=null,SEL="ops";
const pinBtn=(id,pinned)=>`<span class="pin ${pinned?"is":""}" role="button" data-pin="${id}" title="${pinned?t("إلغاء التثبيت","Unpin"):t("تثبيت في الشاشات الرئيسية","Pin to main screens")}">${I(pinned?"pin-off":"pin")}</span>`;
// sec=true → pinned section. Active highlight lives in one place only: pinned list if pinned, else dept list.
const link=(p,c,ic,sec)=>{const pinned=PINS.includes(p.id),here=p.id===c,on=here&&(sec||!pinned);
 return `<a href="#" data-go="${p.id}" class="${on?"on":here?"here-soft":""}">${ic?I(p.i):'<i class="kd"></i>'}<span>${t(p.ar,p.en)}</span>${p.b?`<span class="badge num">${p.b}</span>`:""}${PG[p.id]?pinBtn(p.id,pinned):""}</a>`};
function rail(c){
 const side=document.querySelector("#app .side");if(!side)return;
 document.querySelectorAll(".modbar").forEach(e=>e.remove());
 let r=side.querySelector(".rail");
 if(!r){r=document.createElement("div");r.className="rail";side.prepend(r)}
 r.innerHTML=`<a href="#" data-go="dashboard" data-rail="all" class="rl-home ${SEL==="all"?"on":""}" title="${t("الرئيسية وكل الإدارات","Home & all departments")}">${I("layout-dashboard")}</a><span class="rl-sep"></span>`+
  DEPTS.map(d=>`<button data-rail="${d.id}" class="${d.id===SEL?"on":""}${deptOf(c)&&deptOf(c).id===d.id?" here":""}">${I(d.i)}<small>${t(d.ar,d.en)}</small></button>`).join("");
}
window.renderNav=function(c){
 const dd=deptOf(c);if(dd)DEPT=dd.id;if(c!==LAST){NAVEXP=null;LAST=c;SEL=dd?DEPT:"all"}
 rail(c);lucide.createIcons();
 const q=NAVQ.trim().toLowerCase();
 if(q){
  const hits=Object.values(PG).filter(p=>(p.ar+p.en).toLowerCase().includes(q));
  return hits.length?`<div class="nav-sec-t">${t("نتائج البحث","Results")}</div>`+hits.map(p=>link(p,c,1,1)).join(""):`<div class="nav-empty">${t("لا نتائج","No matches")}</div>`;
 }
 const quick=`<div class="nav-sec-t">${t("الشاشات الرئيسية","Main screens")}<small class="num">${PINS.length}</small></div>`+
  (PINS.length?PINS.map(id=>link(PG[id],c,1,1)).join(""):`<div class="pin-empty">${I("pin")}<span>${t("ثبّت الشاشات اللي بتستخدمها كتير من زر التثبيت جنب أي شاشة.","Pin the screens you use most with the pin button next to any screen.")}</span></div>`);
 if(SEL==="all")return quick+`<div class="nav-sec-t dept">${t("الإدارات","Departments")}<small class="num">${DEPTS.length}</small></div>`+DEPTS.map(d=>{
  const open=NAVEXP===d.id,here=!!pages(d).find(p=>p.id===c);
  return `<div class="nav-exp ${open?"open":""}"><button class="nav-eh ${here?"on":""}" data-navexp="${d.id}">${I(d.i)}<span>${t(d.ar,d.en)}</span><span class="cnt num">${pages(d).length}</span>${I("chevron-down","chev")}</button><div class="nav-kids">${(d.kids||pages(d)).map(k=>k.sec?`<div class="nav-kid-sec">${t(k.ar,k.en)}</div>`:link(k,c,0)).join("")}</div></div>`}).join("");
 const d=DEPTS.find(x=>x.id===SEL);
 const depts=`<div class="nav-sec-t dept">${I(d.i)}<span>${t(d.ar,d.en)}</span><small class="num">${pages(d).length}</small></div>`+(d.kids||pages(d)).map(k=>k.sec?`<div class="nav-kid-sec">${t(k.ar,k.en)}</div>`:link({...k,i:k.i||"dot"},c,!!k.i)).join("");
 return quick+depts;
};
const reNav=()=>{const nv=document.querySelector("#app .nav");if(!nv)return;const st=nv.scrollTop;nv.innerHTML=renderNav(cur);lucide.createIcons();nv.scrollTop=st};
document.addEventListener("click",e=>{const p=e.target.closest("[data-pin]");if(!p)return;e.preventDefault();e.stopImmediatePropagation();const id=p.dataset.pin;PINS=PINS.includes(id)?PINS.filter(x=>x!==id):[...PINS,id];savePins();reNav()},true);
document.addEventListener("click",e=>{const b=e.target.closest("[data-rail]");if(!b)return;e.preventDefault();SEL=b.dataset.rail;const ap=document.getElementById("app");ap.classList.remove("collapsed");if(innerWidth<=1280&&innerWidth>900){if(ap.classList.contains("peek")&&b.classList.contains("on")){ap.classList.remove("peek");return}ap.classList.add("peek")}const nv=ap.querySelector(".nav");nv.innerHTML=renderNav(cur);lucide.createIcons();nv.scrollTop=0});
document.addEventListener("click",e=>{const ap=document.getElementById("app");if(!ap||!ap.classList.contains("peek")||e.target.closest("[data-rail]"))return;if(e.target.closest(".side .nav [data-go]")||!e.target.closest(".side"))ap.classList.remove("peek")},true);
})();

/* ===== multipage boot ===== */
(function(){var P=window.CERP_PAGE;if(!P)return;var PAGES=window.CERP_PAGES||[];
if(P==="login"){renderLogin();return}
document.getElementById("login")&&document.getElementById("login").classList.add("hidden");
document.getElementById("app").classList.remove("hidden");cur=P;render();
document.addEventListener("click",function(e){var g=e.target.closest("[data-go]");if(g&&!e.target.closest("[data-pin]")&&PAGES.indexOf(g.dataset.go)>-1&&g.dataset.go!==P){e.preventDefault();e.stopImmediatePropagation();location.href=g.dataset.go+".html"}
if(e.target.closest("[data-logout]")){e.preventDefault();e.stopImmediatePropagation();location.href="login.html"}},true);})();
