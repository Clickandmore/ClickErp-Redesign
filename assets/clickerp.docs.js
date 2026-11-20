/* ===== v1.63 — دليل التصميم (2): أنواع العرض · أنواع الجداول · التقارير · الطباعة والنماذج
   + مثال التقرير (guideReport) + مثال المستند المطبوع (guidePrint) + شاشة «نماذج الطباعة» (setPrint)
   ملف منفصل عن الحزمة: بيتحمّل بعد clickerp.bundle.js في صفحات الدليل ونماذج الطباعة بس. ===== */
(()=>{
if(typeof screens==="undefined"||!window.CXH)return;
const {X,TAG,FLD,HEAD,B}=window.CXH;
const MINE=["guideReport","guidePrint","setPrint"];
(window.CERP_PAGES=window.CERP_PAGES||[]).push(...MINE.filter(p=>window.CERP_PAGES.indexOf(p)<0));
const S={};
const ico=()=>{if(window.lucide)lucide.createIcons({attrs:{"stroke-width":1.8}})};
const n0=v=>Math.round(v).toLocaleString("en-US");
const n2=v=>(+v).toLocaleString("en-US",{minimumFractionDigits:2,maximumFractionDigits:2});
const DO=(d,n)=>`<div class="h18-dd"><div class="do"><b>${X("check-circle-2")}افعل</b><ul>${d.map(x=>`<li>${x}</li>`).join("")}</ul></div><div class="dont"><b>${X("x-circle")}لا تفعل</b><ul>${n.map(x=>`<li>${x}</li>`).join("")}</ul></div></div>`;
const REF=l=>`<div class="h18-ref"><span>شاشات مرجعية:</span>${l.map(x=>`<a href="#" data-go="${x[0]}">${x[1]}</a>`).join("")}</div>`;
const LEG=l=>`<ol class="h18-leg">${l.map((x,i)=>`<li><i class="h18-pin">${i+1}</i><div><b>${x[0]}</b><span>${x[1]}</span></div></li>`).join("")}</ol>`;
const SEC=(id,title,lead,body)=>`<section class="card h18-sec" id="g-${id}"><div class="h18-h"><span class="num">#</span><div><h2>${title}</h2><p>${lead}</p></div></div>${body}</section>`;
const EXB=(on)=>`<div class="h19-bar">${X("book-open")}<span><b>مثال من دليل التصميم</b> — ${on}. انسخ البنية والسلوك كما هما لأي شاشة من النوع نفسه.</span>${[["designGuide","الدليل"],["guideTable","الجدول"],["guideShow","العرض"],["guideAdd","الإضافة والتعديل"],["guideReport","التقرير"],["guidePrint","المستند المطبوع"],["setPrint","نماذج الطباعة"]].map(x=>`<a href="#" data-go="${x[0]}">${x[1]}</a>`).join("")}</div>`;
window.CERP_DOCS={EXB};

/* ---------- رسوم مصغّرة (wireframe) لكل نوع ---------- */
const WF={
 tabs:`<div class="wf wf-tabs"><i class="w-side"><s></s><s></s><s></s></i><i class="w-main"><u></u><s></s><s></s><s class="w-s"></s></i></div>`,
 req:`<div class="wf wf-req"><i class="w-chain"><s></s><s></s><s></s><s></s></i><i class="w-body"><s></s><s></s><s class="w-s"></s></i><i class="w-dec"><s class="w-g"></s><s class="w-r"></s></i></div>`,
 doc:`<div class="wf wf-doc"><i class="w-hd"><s></s><s class="w-q"></s></i><i class="w-lines"><s></s><s></s><s></s></i><i class="w-tot"><s></s></i></div>`,
 journey:`<div class="wf wf-jr"><i class="w-tl"><s></s><s></s><s></s><s class="w-o"></s><s class="w-o"></s></i><i class="w-body"><s></s><s></s></i></div>`,
 drawer:`<div class="wf wf-dr"><i class="w-list"><s></s><s></s><s class="w-on"></s><s></s></i><i class="w-pane"><s></s><s></s><s class="w-s"></s></i></div>`,
 profile:`<div class="wf wf-pf"><i class="w-hero"><s></s></i><i class="w-kpi"><s></s><s></s><s></s><s></s></i><i class="w-body"><s></s><s></s></i></div>`,
 list:`<div class="wf wf-list"><i class="w-chips"><s></s><s></s><s></s></i><i class="w-rows"><s></s><s></s><s class="w-r"></s><s></s></i></div>`,
 lines:`<div class="wf wf-lines"><i class="w-rows"><s></s><s></s><s class="w-add"></s></i><i class="w-tot"><s></s></i></div>`,
 tree:`<div class="wf wf-tree"><s class="w-l0"></s><s class="w-l1"></s><s class="w-l2"></s><s class="w-l2"></s><s class="w-l1"></s><s class="w-l0"></s></div>`,
 matrix:`<div class="wf wf-mx">${"<s></s>".repeat(24)}</div>`,
 cards:`<div class="wf wf-cards">${"<s></s>".repeat(6)}</div>`,
 kanban:`<div class="wf wf-kb"><i><s></s><s></s><s></s></i><i><s></s><s></s></i><i><s></s></i><i><s></s><s></s></i></div>`,
 cal:`<div class="wf wf-cal">${"<s></s>".repeat(21)}</div>`,
 report:`<div class="wf wf-rep"><i class="w-kpi"><s></s><s></s><s></s></i><i class="w-bars"><s style="height:60%"></s><s style="height:85%"></s><s style="height:40%"></s><s style="height:70%"></s></i><i class="w-rows"><s></s><s></s><s class="w-t"></s></i></div>`
};
const KIND=(k)=>`<div class="p20-kind"><div class="p20-wf">${WF[k[0]]}</div><div class="p20-kb"><b>${k[1]}</b><small><em>متى:</em> ${k[2]}</small><small><em>البنية:</em> ${k[3]}</small>${k[4]?`<small class="p20-rule">${X("check")}${k[4]}</small>`:""}<div class="h18-ref">${k[5].map(x=>`<a href="#" data-go="${x[0]}">${x[1]}</a>`).join("")}</div></div></div>`;

/* ---------- أقسام الدليل الجديدة ---------- */
const secShowKinds=()=>SEC("showkinds","أنواع شاشات العرض","ست أنواع تغطي كل شاشات العرض في النظام. حدّد النوع من سؤال واحد: ما الذي يأتي المستخدم ليفعله في هذه الشاشة؟",`<div class="p20-kinds">${[
 ["tabs","سجل كبير بتبويبات","سجل يعيش طويلاً ويُرجع إليه كثيراً: سيرة، عقد، عميل، موظف، عاملة.","رأس + بطاقة وإجراءات على الجانب + مراحل (إن وُجدت) + تبويبات تبدأ بـ «نظرة عامة» وتنتهي بـ «سجل النشاط».","هذا هو النمط الافتراضي — المثال الحي في «مثال: عرض».",[["guideShow","مثال: عرض"],["cvShow","تفاصيل السيرة"],["contractShow","تفاصيل العقد"],["hrEmployeeShow","ملف الموظف"]]],
 ["req","طلب أو معاملة تنتظر قراراً","شيء يمر على معتمدين وينتهي: طلب موظف، طلب صرف، شكوى، طلب نقل.","سلسلة الاعتماد أعلى الصفحة + المحتوى والفحص + شريط قرار ثابت (اعتماد · رفض بسبب · استيضاح) + الخط الزمني.","القرار يظهر لمن عليه الدور فقط، ويختفي بعد اتخاذه.",[["myRequestShow","تفاصيل الطلب"],["finRequestShow","طلب الصرف"],["claimShow","الشكوى"],["transferShow","طلب النقل"]]],
 ["doc","مستند مالي","فاتورة، سند قبض أو صرف، قيد، إشعار دائن: مستند له رقم وقيمة ولا يتغير بعد الترحيل.","المستند بشكل الورقة (رأس، أطراف، بنود، إجماليات) + شريط جانبي: الحالة، الترحيل، القيد الناتج، المرفقات، الإجراءات (طباعة · إرسال · إشعار دائن).","بعد الترحيل لا «تعديل»: التصحيح بمستند عكسي يُربط بالأصل.",[["finShow","عرض المستند"],["guidePrint","مثال: المستند المطبوع"],["finSales","الفواتير"]]],
 ["journey","رحلة زمنية","شيء يمر بمراحل على أيام أو شهور والسؤال «وين وصل؟»: رحلة العاملة، الرحلة، نقل الخدمات.","خط زمني رأسي هو المحتوى الرئيسي: المنجز بتاريخه، والحالي مميز ومعه المسؤول والمهلة، والقادم باهت.","المرحلة الحالية دائماً ظاهرة دون تمرير.",[["hrWorkerShow","رحلة العاملة"],["tripShow","تفاصيل الرحلة"],["transferOverview","حالات نقل الخدمات"]]],
 ["drawer","لوحة جانبية من القائمة","نظرة سريعة أو إجراء صغير دون ترك القائمة: مهمة، زيارة، بطاقة في لوحة.","القائمة تبقى يميناً واللوحة يساراً (480–640px) فيها الملخص والإجراء، و«فتح الصفحة الكاملة» لمن يحتاج المزيد.","اللوحة لا تحتوي تبويبات أكثر من 3 — إن احتجت أكثر فهي «سجل كبير».",[["tcTask","صفحة المهمة"],["hb-board","لوحة الطلبات"],["finSales","معاينة الفاتورة"]]],
 ["profile","ملف شخصي / لوحة سجل","صفحة شخص أو جهة أهم ما فيها الأرقام والتنبيهات: ملفي، المكتب الخارجي، الكوبون.","رأس عريض بالهوية + 4 أرقام + تنبيهات + آخر النشاط، ثم أقسام قصيرة (أو تبويبات إن كثرت).","",[["myProfile","الملف الشخصي"],["agentShow","المكتب الخارجي"],["couponShow","الكوبون"]]]
].map(KIND).join("")}</div>
 ${DO(["اختر النوع قبل الرسم واكتب اسمه في أول سطر من وصف الشاشة","«طباعة» زر ثانوي في رأس كل عرض له مستند مطبوع","الرجوع يعود للقائمة بنفس الفلاتر والصفحة"],["لا تخلط نوعين في صفحة واحدة (سجل بتبويبات + شريط قرار)","لا تفتح صفحة كاملة لما يكفيه لوحة جانبية","لا تكرر بيانات المستند المطبوع في تبويبات منفصلة — المستند نفسه هو العرض"])}`);

const secTableKinds=()=>SEC("tablekinds","أنواع الجداول","ثمانية أنواع. الجدول القياسي هو الافتراضي، والباقي له سبب واضح.",`<div class="p20-kinds">${[
 ["list","قائمة متابعة (القياسي)","استعراض سجلات كثيرة والبحث فيها والتصرف فيما يحتاج.","شرائح حالة بعدد + بحث وفلاتر + جدول 7 أعمدة + تحديد وإجراءات جماعية + ترقيم + إجمالي.","المثال الحي في «مثال: جدول».",[["guideTable","مثال: جدول"],["contracts","العقود"],["hrEmployees","الموظفون"]]],
 ["lines","جدول إدخال بنود","داخل نموذج: بنود فاتورة، أسطر قيد، بنود مخالصة.","صف لكل بند يُكتب فيه مباشرة + «إضافة بند» تحت آخر صف + إجماليات تتحدّث مع الكتابة + تحقق (المدين = الدائن).","Enter ينتقل للحقل التالي، والصف الفارغ الأخير لا يُحفظ.",[["finSaleAdd","فاتورة بيع"],["finAdd","إضافة قيد"],["hrEos","المخالصة"]]],
 ["tree","شجرة","بيانات هرمية: الدليل المحاسبي، مراكز التكلفة، الأقسام.","مستويات بإزاحة وأسهم فتح/طي + مجموع كل أب من أبنائه + بحث يفتح الفرع المطابق + «فتح الكل / طي الكل».","",[["finAccounts","الحسابات"],["finCentersTree","مراكز التكلفة"]]],
 ["matrix","مصفوفة أو جدول مجمّع","مقارنة صفوف بأعمدة كثيرة: الرواتب، الصلاحيات، الأرصدة، الحضور الشهري.","العمود الأول ثابت عند التمرير الأفقي + رؤوس ثابتة + مجاميع فرعية لكل مجموعة + مجموع عام.","هنا فقط يُسمح بأكثر من 7 أعمدة.",[["hrPayroll","الرواتب"],["setRoles","الأدوار والصلاحيات"],["finBalances","الأرصدة"]]],
 ["cards","بطاقات (معرض)","حين تكون الصورة أهم من الأرقام: السير الذاتية، العاملات المتاحات.","بطاقة بصورة + الاسم + 3 معلومات + الحالة + إجراء، مع تبديل «بطاقات / جدول» يحفظ اختيار المستخدم.","",[["cvs","السير الذاتية"],["rentalCvs","سير التأجير"]]],
 ["kanban","لوحة مراحل (كانبان)","عمل يتحرك بين مراحل ويُدار بالسحب: المهام، طلبات التأجير بالساعة.","عمود لكل مرحلة بعدده + بطاقة مختصرة (العنوان، المسؤول، المهلة) + السحب لتغيير المرحلة + لوحة جانبية للتفاصيل.","",[["tcBoard","لوحة المهام"],["hb-board","لوحة الطلبات"]]],
 ["cal","تقويم أو جدول زمني","المهم هو الوقت: الزيارات، الورديات، الحضور، الإجازات.","شبكة أيام أو ساعات + لون لكل حالة + الضغط على خانة يفتح لوحة جانبية + تبديل يوم/أسبوع/شهر.","",[["hb-schedule","الجدول والفرق"],["hrAttendance","الحضور"]]],
 ["report","جدول تقرير","قراءة وتحليل لا تصرّف: كشف حساب، أعمار ديون، أداء فرع.","أرقام ملخصة + رسم واحد + جدول مجمّع بمجاميع فرعية + الضغط على رقم يفتح السجلات خلفه + تصدير وطباعة.","التفصيل في قسم «التقارير» والمثال الحي في «مثال: تقرير».",[["guideReport","مثال: تقرير"],["finStatements","القوائم المالية"],["report","التقرير"]]]
].map(KIND).join("")}</div>
 <div class="h18-mini-t" style="margin-top:6px">قواعد مشتركة لكل الجداول</div>
 <div class="tw"><table class="h18-t"><tbody>${[["الأرقام","بخط الأرقام، محاذاة واحدة، فاصل الآلاف، والعملة في رأس العمود لا في كل خلية"],["التواريخ","YYYY-MM-DD، والمدة النسبية تحتها صغيرة عند الحاجة («منذ 3 أيام»)"],["الإجماليات","في تذييل الجدول، وتتبع الفلاتر — لا يُعرض إجمالي كل البيانات تحت قائمة مفلترة"],["الصف","الضغط عليه يفتح العرض؛ الأزرار داخله لا تفتحه"],["الفارغ","جملة تقول السبب + الإجراء («مسح الفلاتر»، «إضافة أول …»)"],["التحميل","هيكل رمادي بشكل الصفوف، لا دائرة دوّارة في منتصف الصفحة"],["الطباعة والتصدير","Excel يصدّر الأعمدة الظاهرة بالفلاتر الحالية، والطباعة بقالب «تقرير جدول» من نماذج الطباعة"]].map(r=>`<tr><td style="width:150px"><b>${r[0]}</b></td><td><small>${r[1]}</small></td></tr>`).join("")}</tbody></table></div>`);

const secReports=()=>SEC("reports","التقارير","التقرير يجيب عن سؤال واحد مكتوب في عنوانه. كل التقارير بنمط واحد من سبعة أجزاء.",`<div class="h18-two"><div class="h18-demo p20-repdemo">
  <div class="p20-rh"><b>العقود حسب المرحلة <i class="h18-pin">1</i></b><span class="p20-per">نوفمبر 2026 · كل الفروع <i class="h18-pin">2</i></span></div>
  <div class="p20-rk">${[["العقود","124"],["متأخرة","9"],["متوسط المدة","38 يوم"]].map(x=>`<div><small>${x[0]}</small><b class="num">${x[1]}</b></div>`).join("")}<i class="h18-pin">3</i></div>
  <div class="p20-rbars">${[["جديد",30],["مساند",22],["تفويض",18],["تفييز",26],["وصول",12]].map(x=>`<div><i style="height:${x[1]*3}px"></i><small>${x[0]}</small></div>`).join("")}<i class="h18-pin">4</i></div>
  <table class="p20-rt"><thead><tr><th>المرحلة <i class="h18-pin">5</i></th><th>العقود</th><th>متأخرة</th></tr></thead><tbody><tr><td>جديد</td><td class="num"><a>30</a></td><td class="num">1</td></tr><tr><td>التفييز</td><td class="num"><a>26</a> <i class="h18-pin">6</i></td><td class="num">5</td></tr><tr class="t"><td>الإجمالي</td><td class="num">124</td><td class="num">9</td></tr></tbody></table>
  <div class="p20-rf">${B("ghost sm","file-spreadsheet","Excel")}${B("ghost sm","printer","طباعة")}${B("ghost sm","bookmark","حفظ")} <i class="h18-pin">7</i></div>
 </div><div>${LEG([["السؤال","العنوان سؤال العمل نفسه: «العقود حسب المرحلة»، «أعمار ديون العملاء». سطر تحته يقول المصدر وما يُحسب."],["الفترة والنطاق","الفترة والفرع دائماً ظاهران كشرائح قابلة للإزالة، والفلاتر الأخرى بجانبهما. التقرير يُفتح بفترة افتراضية مفيدة (الشهر الحالي)."],["الأرقام الملخصة","3–4 أرقام فقط، ومع كل رقم المقارنة بالفترة السابقة (▲ ▼) عند وجود معنى لها."],["رسم واحد","أعمدة للمقارنة بين فئات، وخط للزمن، ولا دوائر لأكثر من 4 فئات. الرسم يتبع الفلاتر."],["الجدول","مجمّع حسب البعد المختار (المرحلة، الفرع، المكتب…) مع مجاميع فرعية وإجمالي. تبديل البعد لا يغيّر الفلاتر."],["التعمق","الضغط على أي رقم يفتح السجلات التي خلفه — الرقم لا يُعرض دون مصدره."],["الإخراج","Excel بالأعمدة الظاهرة، والطباعة بقالب «تقرير» (الرأس، الفترة، الفلاتر، رقم الصفحة)، و«حفظ في تقاريري» و«جدولة» للإرسال الدوري."]])}</div></div>
 <div class="h18-mini-t" style="margin-top:14px">أنواع التقارير في النظام</div>
 <div class="tw"><table class="h18-t"><thead><tr><th>النوع</th><th>السؤال</th><th>الشكل</th><th>أمثلة</th></tr></thead><tbody>${[["ملخص (لوحة)","كيف نحن الآن؟","أرقام + رسوم صغيرة + «يحتاج تصرفاً»","لوحة المعلومات · لوحة المالية · لوحة الموارد"],["تجميعي","كم … حسب …؟","رسم أعمدة + جدول مجمّع بمجاميع","العقود حسب المرحلة · التأشيرات · الكوبونات"],["مقارنة فترات","هل تحسّنا؟","عمود لكل فترة + الفرق ونسبته","المبيعات الشهرية · الأداء السنوي"],["تفصيلي","ما هي السجلات بالضبط؟","جدول قائمة بأعمدة يختارها المستخدم","العقود المتأخرة · الفواتير غير المسددة"],["كشف حساب","ما الحركة والرصيد؟","افتتاحي + حركة (مدين/دائن/رصيد جارٍ) + ختامي","كشف عميل · كشف حساب · كشف صندوق"],["أعمار","ما المتأخر وكم عمره؟","أعمدة 0–30 · 31–60 · 61–90 · +90 لكل طرف","أعمار ديون العملاء · الموردين"],["قوائم مالية","ما الوضع المالي؟","بنود هرمية بمستويات ومقارنة بالسنة السابقة","الميزانية · الدخل · ميزان المراجعة · الضريبة"]].map(r=>`<tr><td><b>${r[0]}</b></td><td>${r[1]}</td><td><small>${r[2]}</small></td><td><small>${r[3]}</small></td></tr>`).join("")}</tbody></table></div>
 ${DO(["افتح التقرير على بيانات فوراً بفترة افتراضية","اجعل كل رقم قابلاً للضغط إلى سجلاته","اكتب في رأس المطبوع الفترة والفلاتر ووقت الاستخراج ومن استخرجه","التقارير المالية تطابق القيود: نفس الرقم في كل مكان"],["لا تطلب من المستخدم «اختيار الفلاتر ثم عرض» قبل أي بيانات","لا تعرض أكثر من رسم واحد في تقرير تجميعي","لا تستخدم الدوائر لأكثر من 4 فئات","لا تضع إيرادات في لوحات التشغيل — المبالغ مكانها لوحة المالية"])}${REF([["guideReport","مثال: تقرير"],["reports","مركز التقارير"],["finStatements","القوائم المالية"],["finTaxReports","التقارير الضريبية"]])}`);

/* قسم الطباعة يُكمَّل لاحقاً (بعد حصر القوالب من الكود) */
let secPrint=()=>"";
window.__p20SetPrintSec=f=>{secPrint=f};

/* ---------- حقن الأقسام في صفحة الدليل ---------- */
const NEWSEC=[["showkinds","أنواع العرض","table"],["tablekinds","أنواع الجداول","inbox"],["reports","التقارير","inbox"],["print","الطباعة والنماذج","inbox"]];
if(screens.designGuide&&!screens.designGuide.__p20){const orig=screens.designGuide;
 screens.designGuide=()=>{const tpl=document.createElement("template");tpl.innerHTML=orig();
  const main=tpl.content.querySelector(".h18-main");
  if(main){const html={showkinds:secShowKinds(),tablekinds:secTableKinds(),reports:secReports(),print:secPrint()};
   NEWSEC.forEach(s=>{if(!html[s[0]])return;const before=main.querySelector("#g-"+s[2]);const w=document.createElement("template");w.innerHTML=html[s[0]];before?main.insertBefore(w.content,before):main.appendChild(w.content)});
   const secs=[...main.querySelectorAll(".h18-sec")];secs.forEach((s,i)=>{const n=s.querySelector(".h18-h>.num");if(n)n.textContent=i+1});
   const toc=tpl.content.querySelector(".h18-toc");if(toc){toc.querySelectorAll("a[data-h18-toc]").forEach(a=>a.remove());const hint=toc.querySelector(".fhint");
    secs.forEach((s,i)=>{const id=s.id.replace(/^g-/,"");const a=document.createElement("a");a.href="#"+s.id;a.dataset.h18Toc=id;a.innerHTML=`<span class="num">${i+1}</span>${s.querySelector("h2").textContent}`;toc.insertBefore(a,hint)});
    if(hint)hint.textContent="الإصدار v1.63 · 2026-11-20"}
   /* المرجع: نوع الشاشة → أضف التقرير والمستند المطبوع */
   const ex=tpl.content.querySelector(".h19-ex");if(ex&&!ex.querySelector('[data-go="guideReport"]'))ex.insertAdjacentHTML("beforeend",[["guideReport","bar-chart-3","مثال: تقرير","فترة وفلاتر، أرقام، رسم، جدول مجمّع بالتعمق، وطباعة"],["guidePrint","printer","مثال: المستند المطبوع","فاتورة وسند وعقد وكشف حساب بقالب واحد، A4 وحراري"],["setPrint","layout-template","نماذج الطباعة","شاشة الإعدادات: كل القوالب وترتيب الفاتورة بمعاينة حية"]].map(x=>`<a href="#" data-go="${x[0]}" class="card">${X(x[1])}<div><b>${x[2]}</b><small>${x[3]}</small></div>${X("arrow-left")}</a>`).join(""))}
  return tpl.innerHTML};
 screens.designGuide.__p20=1}

Object.assign(T,{guideReport:{ar:"مثال: تقرير",en:"Example: report"},guidePrint:{ar:"مثال: المستند المطبوع",en:"Example: printed document"},setPrint:{ar:"نماذج الطباعة",en:"Print templates"}});
window.__p20S=S;
})();

/* ===== مثال: تقرير (guideReport) — العقود حسب المرحلة: فترة وفلاتر وتجميع وتعمق وطباعة ===== */
(()=>{
if(typeof screens==="undefined"||!window.CXH||!window.__p20S)return;
const {X,TAG,HEAD,B}=window.CXH;const S=window.__p20S;
const ico=()=>{if(window.lucide)lucide.createIcons({attrs:{"stroke-width":1.8}})};
const toast=m=>{let el=document.querySelector(".toast");if(!el){el=document.createElement("div");el.className="toast";document.body.appendChild(el)}el.innerHTML=X("check-circle-2")+"<span>"+m+"</span>";ico();el.classList.add("show");clearTimeout(el.__t);el.__t=setTimeout(()=>el.classList.remove("show"),2400)};
/* بيانات ثابتة (مولّد بسيط) */
const STG=[["new","جديد"],["msaned","مساند"],["tafwed","تفويض"],["taffez","التفييز"],["tazker","التذكرة"],["arrival","الوصول"],["completed","مكتمل"]];
const BR=["الفرع الرئيسي","فرع جدة","فرع الدمام"],CO=["الفلبين","كينيا","أوغندا","الهند","بنغلاديش","سريلانكا"],OF=["PLATINUM","Manila Prime","Nairobi Star","Kampala Care"];
const NM=["منيرة الشهري","فهد العنزي","سلمى الحربي","ماجد القحطاني","نورة الدوسري","عبدالله المطيري","هيا الشمري","خالد الزهراني","ريم العتيبي","سعد الغامدي","لمى السبيعي","تركي البقمي"];
let seed=7;const rnd=()=>{seed=(seed*9301+49297)%233280;return seed/233280};
const ALL=[];for(let i=0;i<168;i++){const m=11-Math.floor(rnd()*12)%12,st=STG[Math.min(6,Math.floor(rnd()*7.6))][0];const d=Math.floor(8+rnd()*70);
 ALL.push({id:"CN-2026-"+String(400-i).padStart(4,"0"),cl:NM[Math.floor(rnd()*NM.length)],st,br:BR[Math.floor(rnd()*rnd()*3)],co:CO[Math.floor(rnd()*CO.length)],of:OF[Math.floor(rnd()*OF.length)],m,days:d,late:st!=="completed"&&rnd()<.16})}
const PER={m:["هذا الشهر",[11],[10]],q:["هذا الربع",[9,10,11],[6,7,8]],y:["هذه السنة",[0,1,2,3,4,5,6,7,8,9,10,11],[]]};
const DIM={st:["المرحلة",x=>x.st,k=>(STG.find(s=>s[0]===k)||[0,k])[1],STG.map(s=>s[0])],br:["الفرع",x=>x.br,k=>k,BR],co:["الدولة",x=>x.co,k=>k,CO],of:["المكتب الخارجي",x=>x.of,k=>k,OF]};
const R={per:"q",br:"",co:"",late:0,dim:"st",sort:"dim",drill:null,print:0};
const pick=ms=>ALL.filter(x=>ms.includes(x.m)&&(!R.br||x.br===R.br)&&(!R.co||x.co===R.co)&&(!R.late||x.late));
const kpi=(rows)=>({n:rows.length,late:rows.filter(x=>x.late).length,done:rows.filter(x=>x.st==="completed").length,avg:rows.length?Math.round(rows.reduce((a,x)=>a+x.days,0)/rows.length):0});
const delta=(a,b,inv)=>{if(!b&&b!==0||R.per==="y")return "";if(!b)return "";const p=Math.round((a-b)/b*100);if(!p)return `<em class="p21-d">= بلا تغيير</em>`;const good=inv?p<0:p>0;return `<em class="p21-d ${good?"g":"r"}">${p>0?"▲":"▼"} ${Math.abs(p)}%</em>`};
const groups=rows=>{const d=DIM[R.dim];let g=d[3].map(k=>{const r=rows.filter(x=>d[1](x)===k);return {k,l:d[2](k),r,...kpi(r)}}).filter(x=>x.n||R.dim==="st");
 if(R.sort==="n")g.sort((a,b)=>b.n-a.n);else if(R.sort==="late")g.sort((a,b)=>b.late-a.late);return g};
const chips=()=>{const c=[[`الفترة: ${PER[R.per][0]}`,""],R.br?[`الفرع: ${R.br}`,"br"]:[`كل الفروع`,""],R.co?[`الدولة: ${R.co}`,"co"]:null,R.late?["المتأخرة فقط","late"]:null].filter(Boolean);
 return `<div class="rc-fl p21-fl">${c.map(x=>`<span>${x[0]}${x[1]?`<button data-gr="rm:${x[1]}" title="إزالة">${X("x")}</button>`:""}</span>`).join("")}${R.br||R.co||R.late?`<button class="p21-clr" data-gr="reset:1">مسح الفلاتر</button>`:""}</div>`};
const body=()=>{const cur=pick(PER[R.per][1]),prv=pick(PER[R.per][2]),k=kpi(cur),kp=kpi(prv),g=groups(cur),mx=Math.max(1,...g.map(x=>x.n)),d=DIM[R.dim];
 return `${chips()}
 <div class="statgrid four p21-k">${[["العقود","file-text","n",k.n,kp.n,0],["المتأخرة","alarm-clock","r",k.late,kp.late,1],["المكتملة","check-circle-2","g",k.done,kp.done,0],["متوسط المدة (يوم)","timer","a",k.avg,kp.avg,1]].map(s=>`<button class="scard st6" data-gr="${s[0]==="المتأخرة"?"late:1":"none:1"}"><span class="sci ${s[2]}">${X(s[1])}</span><span class="sct">${s[0]}</span><b class="num">${s[3]}</b>${delta(s[3],s[4],s[5])}</button>`).join("")}</div>
 <div class="grid-2 p21-g"><div class="card sec"><div class="sec-h">${X("bar-chart-3")}<b>العقود حسب ${d[0]}</b><span class="ms">الأحمر = المتأخر منها</span></div>
  <div class="p21-chart">${g.map(x=>`<button class="p21-bar" data-gr="drill:${x.k}" title="${x.l}: ${x.n} عقد · ${x.late} متأخر"><span class="v num">${x.n}</span><i style="height:${Math.round(x.n/mx*150)}px"><u style="height:${x.n?Math.round(x.late/x.n*100):0}%"></u></i><small>${x.l}</small></button>`).join("")||'<div class="h13-empty">لا بيانات</div>'}</div></div>
  <div class="card sec"><div class="sec-h">${X("lightbulb")}<b>ما يلفت النظر</b></div><div class="p21-ins">${ins(g,k,kp)}</div></div></div>
 <div class="card flush fx-t"><div class="p21-th"><b>التفصيل حسب ${d[0]}</b><span class="ms">اضغط أي رقم لعرض العقود التي خلفه</span><select data-gr-sort=""><option value="dim"${R.sort==="dim"?" selected":""}>ترتيب ${d[0]}</option><option value="n"${R.sort==="n"?" selected":""}>الأكثر عقوداً</option><option value="late"${R.sort==="late"?" selected":""}>الأكثر تأخراً</option></select></div>
 ${k.n?`<div class="tw"><table><thead><tr><th>${d[0]}</th><th>العقود</th><th>النسبة</th><th>المتأخرة</th><th>المكتملة</th><th>متوسط المدة</th></tr></thead><tbody>
 ${g.map(x=>`<tr${x.late&&x.late/x.n>.25?' class="fx-late"':""}><td><b>${x.l}</b></td><td class="num">${x.n?`<a class="p21-a" data-gr="drill:${x.k}">${x.n}</a>`:"0"}</td><td><div class="p21-pc"><i style="width:${k.n?Math.round(x.n/k.n*100):0}%"></i><span class="num">${k.n?Math.round(x.n/k.n*100):0}%</span></div></td><td class="num">${x.late?`<a class="p21-a r" data-gr="drill:${x.k}:late">${x.late}</a>`:"—"}</td><td class="num">${x.done||"—"}</td><td class="num">${x.n?x.avg+" يوم":"—"}</td></tr>`).join("")}
 </tbody><tfoot><tr><td><b>الإجمالي</b></td><td class="num"><b>${k.n}</b></td><td class="num">100%</td><td class="num"><b>${k.late}</b></td><td class="num"><b>${k.done}</b></td><td class="num"><b>${k.avg} يوم</b></td></tr></tfoot></table></div>`
 :`<div class="h13-empty">${X("search-x")}<b>لا توجد عقود بهذه الفلاتر في ${PER[R.per][0]}</b><button class="btn btn-ghost sm" data-gr="reset:1">مسح الفلاتر</button></div>`}
 <div class="tfoot"><span>المصدر: عقود الاستقدام · استُخرج 2026-11-20 10:24 · مشرف النظام</span></div></div>
 ${R.drill?drill(cur):""}`};
const ins=(g,k,kp)=>{const out=[];const top=[...g].sort((a,b)=>b.late-a.late)[0];
 if(top&&top.late)out.push(["r","alert-triangle",`أكثر تأخير في «${top.l}»: ${top.late} من ${top.n} عقد`,`drill:${top.k}:late`]);
 const big=[...g].sort((a,b)=>b.n-a.n)[0];if(big&&big.n)out.push(["n","layers",`«${big.l}» فيها ${Math.round(big.n/Math.max(1,k.n)*100)}% من العقود`,`drill:${big.k}`]);
 if(R.per!=="y"&&kp.n){const p=Math.round((k.n-kp.n)/kp.n*100);out.push([p>=0?"g":"a",p>=0?"trending-up":"trending-down",`العقود ${p>=0?"زادت":"قلّت"} ${Math.abs(p)}% عن الفترة السابقة`,""])}
 out.push(["a","timer",`متوسط المدة ${k.avg} يوم${kp.avg&&R.per!=="y"?` (كان ${kp.avg})`:""}`,""]);
 return out.map(x=>`<div class="p21-i ${x[0]}">${X(x[1])}<span>${x[2]}</span>${x[3]?`<button class="btn btn-ghost sm" data-gr="${x[3]}">عرض</button>`:""}</div>`).join("")};
const drill=cur=>{const [k,late]=R.drill;const d=DIM[R.dim];const rows=cur.filter(x=>d[1](x)===k&&(!late||x.late));
 return `<div class="drawer-ov" data-gr-ov=""><aside class="drawer p21-dr"><div class="drawer-h"><div><h3>${d[2](k)}${late?" · المتأخرة":""}</h3><p class="num">${rows.length} عقد · ${PER[R.per][0]}${R.br?" · "+R.br:""}</p></div><button class="icon-btn" data-gr="close:1">${X("x")}</button></div>
 <div class="p21-dl">${rows.slice(0,40).map(x=>`<a href="#" data-go="guideShow" class="p21-dli"><div><b>${x.cl}</b><small class="num">${x.id} · ${x.co} · ${x.of}</small></div><div class="p21-dlr">${x.late?TAG("r","متأخر"):TAG(x.st==="completed"?"g":"a",(STG.find(s=>s[0]===x.st)||[])[1])}<small class="num">${x.days} يوم</small></div></a>`).join("")}</div>
 <div class="drawer-f"><span class="ms">${rows.length>40?"أول 40 من "+rows.length:""}</span>${B("primary sm","external-link","فتح في قائمة العقود بهذه الفلاتر",'data-go="guideTable"')}</div></aside></div>`};
S.guideReport=()=>`<div id="g-rep">${window.CERP_DOCS.EXB("نمط التقارير")}
 ${HEAD("العقود حسب المرحلة","كم عقداً في كل مرحلة، وأين التأخير؟ · المصدر: عقود الاستقدام · لا مبالغ — المبالغ في تقارير المالية",B("ghost","file-spreadsheet","Excel",'data-gr="xls:1"')+B("ghost","printer","طباعة",'data-gr="print:1"')+B("ghost","calendar-clock","جدولة",'data-gr="sched:1"')+B("primary","bookmark","حفظ في تقاريري",'data-gr="save:1"'))}
 <div class="fbar p21-fb"><div class="fb"><label>الفترة</label><div class="cx-seg2">${Object.keys(PER).map(p=>`<button class="${R.per===p?"on":""}" data-gr="per:${p}">${PER[p][0]}</button>`).join("")}</div></div>
  <div class="fb"><label>الفرع</label><select data-gr-f="br"><option value="">كل الفروع</option>${BR.map(b=>`<option${b===R.br?" selected":""}>${b}</option>`).join("")}</select></div>
  <div class="fb"><label>الدولة</label><select data-gr-f="co"><option value="">كل الدول</option>${CO.map(b=>`<option${b===R.co?" selected":""}>${b}</option>`).join("")}</select></div>
  <div class="fb"><label>التجميع حسب</label><div class="cx-seg2">${Object.keys(DIM).map(p=>`<button class="${R.dim===p?"on":""}" data-gr="dim:${p}">${DIM[p][0]}</button>`).join("")}</div></div></div>
 <div data-gr-body="">${body()}</div></div>${R.print&&window.__p20PRINTREP?window.__p20PRINTREP(R,pick(PER[R.per][1]),groups(pick(PER[R.per][1])),DIM[R.dim][0],PER[R.per][0]):""}`;
const rp=()=>{const el=document.getElementById("g-rep");if(el){el.parentNode.innerHTML=S.guideReport();ico()}};
if(!window.__p21){window.__p21=1;
 document.addEventListener("click",e=>{if(window.CERP_PAGE!=="guideReport")return;
  if(e.target.matches&&e.target.matches("[data-gr-ov]")){R.drill=null;rp();return}
  const b=e.target.closest("[data-gr]");if(!b)return;e.preventDefault();const [k,v,w]=b.dataset.gr.split(":");
  if(k==="per")R.per=v;else if(k==="dim"){R.dim=v;R.sort="dim"}else if(k==="rm")R[v]=v==="late"?0:"";else if(k==="reset"){R.br="";R.co="";R.late=0}
  else if(k==="late")R.late=R.late?0:1;else if(k==="drill")R.drill=[v,w==="late"];else if(k==="close")R.drill=null;
  else if(k==="print")R.print=1;else if(k==="pclose")R.print=0;else if(k==="pgo"){R.print=0;rp();toast("أُرسل للطابعة — تقرير بـ "+(document.querySelectorAll(".p22-sheet").length||1)+" صفحة");return}
  else if(k==="xls"){toast("صُدّر Excel بالأعمدة الظاهرة والفلاتر الحالية");return}else if(k==="save"){toast("حُفظ في «تقاريري» بالفترة والفلاتر والتجميع");return}else if(k==="sched"){toast("جدولة: يُرسل كل أحد 8 ص لبريدك — من «تقاريري» تعدّلها");return}
  else if(k==="none")return;
  rp()});
 document.addEventListener("change",e=>{if(window.CERP_PAGE!=="guideReport")return;const f=e.target.closest("[data-gr-f]");if(f){R[f.dataset.grF]=f.value;rp();return}if(e.target.matches("[data-gr-sort]")){R.sort=e.target.value;rp()}});
 document.addEventListener("keydown",e=>{if(window.CERP_PAGE==="guideReport"&&e.key==="Escape"&&(R.drill||R.print)){R.drill=null;R.print=0;rp()}});}
})();

/* ===== المستندات المطبوعة: محرّك واحد لكل النماذج (فاتورة، إشعار، عرض سعر، سندات، كشف، عقود، إيصالات، تقرير) ===== */
(()=>{
if(typeof screens==="undefined"||!window.CXH||!window.__p20S)return;
const {X,TAG,HEAD,B,FLD}=window.CXH;const S=window.__p20S;
const ico=()=>{if(window.lucide)lucide.createIcons({attrs:{"stroke-width":1.8}})};
const esc=s=>String(s==null?"":s).replace(/[&<>"]/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;"}[c]));
const n2=v=>(+v).toLocaleString("en-US",{minimumFractionDigits:2,maximumFractionDigits:2});
const toast=m=>{let el=document.querySelector(".toast");if(!el){el=document.createElement("div");el.className="toast";document.body.appendChild(el)}el.innerHTML=X("check-circle-2")+"<span>"+m+"</span>";ico();el.classList.add("show");clearTimeout(el.__t);el.__t=setTimeout(()=>el.classList.remove("show"),2600)};
/* تفقيط مبسّط */
const ONES=["","واحد","اثنان","ثلاثة","أربعة","خمسة","ستة","سبعة","ثمانية","تسعة","عشرة","أحد عشر","اثنا عشر","ثلاثة عشر","أربعة عشر","خمسة عشر","ستة عشر","سبعة عشر","ثمانية عشر","تسعة عشر"];
const TENS=["","","عشرون","ثلاثون","أربعون","خمسون","ستون","سبعون","ثمانون","تسعون"],HUN=["","مائة","مائتان","ثلاثمائة","أربعمائة","خمسمائة","ستمائة","سبعمائة","ثمانمائة","تسعمائة"];
const w3=n=>{const h=Math.floor(n/100),r=n%100,p=[];if(h)p.push(HUN[h]);if(r){if(r<20)p.push(ONES[r]);else{const o=r%10,t=Math.floor(r/10);p.push(o?ONES[o]+" و"+TENS[t]:TENS[t])}}return p.join(" و")};
const words=v=>{let n=Math.floor(v),h=Math.round((v-n)*100);const th=Math.floor(n/1000),r=n%1000,p=[];
 if(th){p.push(th===1?"ألف":th===2?"ألفان":th<11?w3(th)+" آلاف":w3(th)+" ألفاً")}if(r)p.push(w3(r));
 return (p.join(" و")||"صفر")+" ريال سعودي"+(h?" و"+w3(h)+" هللة":"")+" فقط لا غير"};
/* QR شكلي ثابت */
const QR=(sz)=>{let s=11,c="";const N=25;for(let y=0;y<N;y++)for(let x=0;x<N;x++){const f=(x<7&&y<7)||(x>17&&y<7)||(x<7&&y>17);let on;if(f){const a=x>17?x-18:x,b=y>17?y-18:y;on=(a===0||a===6||b===0||b===6)||(a>1&&a<5&&b>1&&b<5)}else{s=(s*1103515245+12345)%2147483648;on=s%3===0}if(on)c+=`<rect x="${x}" y="${y}" width="1" height="1"/>`}
 return `<svg class="p22-qr" viewBox="0 0 ${N} ${N}" width="${sz}" height="${sz}" shape-rendering="crispEdges"><rect width="${N}" height="${N}" fill="#fff"/><g fill="#111">${c}</g></svg>`};
const LOGO=`<svg viewBox="0 0 64 64" aria-hidden="true"><rect width="64" height="64" rx="16" fill="currentColor"/><path d="M40 14c-9 2-15 9-15 18v18" stroke="#7ab648" stroke-width="6" fill="none" stroke-linecap="round"/><circle cx="40" cy="22" r="5" fill="#fff"/></svg>`;

/* ---------- الهوية المشتركة = document_* في «إعدادات النماذج» + rent_contract_* + sales document settings، مجمّعة في مكان واحد ---------- */
const ID0={name:"مكتب الريادة للاستقدام",en:"Al Riyada Recruitment Office",cr:"1010601958",vat:"300118822000003",lic:"4001772",addr:"الرياض · حي العليا · طريق الملك فهد · ص.ب 12211",phone:"920001234",mobile:"0551200300",email:"info@riyada.sa",web:"riyada.sa",foot:"شكراً لتعاملكم مع [اسم_المكتب] — للاستفسار 920001234",bank:[["مصرف الراجحي","SA03 8000 0000 6080 1016 7519"],["البنك الأهلي","SA44 1000 0000 1234 5678 9012"]],stamp:1,sign:1,zatcaLogo:1};
/* ---------- تعريف النماذج: الأقسام والأعمدة والإعدادات الافتراضية ---------- */
const SEC_L={head:"الرأس (الشعار والعنوان والرقم)",parties:"بيانات المورّد والعميل",meta:"بيانات المستند (التواريخ والسداد)",subject:"الوصف / الموضوع",lines:"جدول البنود",totals:"الإجماليات",words:"المبلغ كتابةً",qr:"رمز QR (هيئة الزكاة)",amount:"مربع المبلغ",accts:"الحسابات ومركز التكلفة",terms:"الشروط والأحكام",bank:"الحسابات البنكية",sign:"التوقيعات والختم",foot:"التذييل",client:"بيانات العميل (الطرف الثاني)",worker:"بيانات العاملة",dates:"المدة والتواريخ",pay:"جدول الدفعات",clauses:"بنود العقد",open:"الرصيد الافتتاحي",moves:"الحركات والرصيد الجاري",filters:"الفترة والفلاتر",table:"الجدول",body:"نص النموذج",orig:"الفاتورة الأصلية وسبب الإشعار",validity:"صلاحية العرض"};
const COL_L={item:"البند",desc:"الوصف",price:"السعر",qty:"الكمية",disc:"الخصم",sub:"الإجمالي قبل الضريبة",rate:"نسبة الضريبة",vat:"الضريبة",total:"الإجمالي"};
const DOCS={
 inv:{g:"fin",ic:"receipt",t:"فاتورة ضريبية مبسطة",en:"Simplified Tax Invoice",src:"admin/ingaz/invoices/print · «تحرير الفاتورة» في إعدادات النماذج",num:"INV-2026-00921",secs:["head","parties","meta","subject","lines","totals","words","qr","terms","bank","sign","foot"],off:["sign"],cols:["item","price","qty","disc","sub","rate","vat","total"],coff:["sub","rate"],papers:["a4","a5","80"],lines:1},
 tinv:{g:"fin",ic:"receipt-text",t:"فاتورة ضريبية",en:"Tax Invoice",src:"finance sales_documents/print (_document)",num:"INV-2026-00922",secs:["head","parties","meta","subject","lines","totals","words","qr","terms","bank","sign","foot"],off:[],cols:["item","desc","price","qty","disc","sub","rate","vat","total"],coff:["rate"],papers:["a4"],lines:1,b2b:1},
 cn:{g:"fin",ic:"file-minus",t:"إشعار دائن",en:"Credit Note",src:"recruitment-credit-notes.download · rental.credit-note.download",num:"CN-2026-00031",secs:["head","parties","meta","orig","lines","totals","words","qr","sign","foot"],off:[],cols:["item","price","qty","vat","total"],coff:[],papers:["a4","80"],lines:1,neg:1},
 quote:{g:"fin",ic:"file-signature",t:"عرض سعر",en:"Quotation",src:"finance sales_documents (quotation · classic/proposal/slides)",num:"QT-2026-00118",secs:["head","parties","meta","subject","lines","totals","words","validity","terms","bank","sign","foot"],off:["words"],cols:["item","desc","price","qty","disc","total"],coff:["disc"],papers:["a4"],lines:1,noQr:1},
 qabd:{g:"fin",ic:"hand-coins",t:"سند قبض",en:"Receipt Voucher",src:"finance/bond/qabd/print_qabd",num:"RV-2026-01452",secs:["head","amount","meta","subject","words","sign","foot"],off:[],papers:["a4","a5","80"],bond:"in"},
 sarf:{g:"fin",ic:"banknote",t:"سند صرف",en:"Payment Voucher",src:"finance/bond/sarf/print_sarf",num:"PV-2026-00387",secs:["head","amount","meta","subject","accts","words","sign","foot"],off:[],papers:["a4","a5"],bond:"out"},
 stmt:{g:"fin",ic:"scroll-text",t:"كشف حساب عميل",en:"Statement of Account",src:"finance/account_statement/print + finance/print-header",num:"",secs:["head","parties","filters","open","moves","totals","sign","foot"],off:["sign"],papers:["a4"],stmt:1},
 rec:{g:"con",ic:"file-text",t:"عقد استقدام",en:"Recruitment Contract",src:"admin/prints/contract · نص CKEditor في «إعدادات العقد» بمتغيرات @",num:"CN-2026-0142",secs:["head","parties","body","sign","foot"],off:[],papers:["a4"],tpl:1},
 rent:{g:"con",ic:"key-round",t:"عقد تأجير عمالة منزلية",en:"Domestic Worker Rental Contract",src:"admin/print/rental-contract-dynamic · بنود rent-contract-terms",num:"RC-2026-0077",secs:["head","client","worker","dates","pay","totals","clauses","sign","foot"],off:[],papers:["a4"],rent:1},
 trans:{g:"con",ic:"arrow-left-right",t:"عقد نقل خدمات عمالة منزلية",en:"Service Transfer Contract",src:"admin/print/sponsorship-transfer-contract-enhanced · transfer-contract-terms",num:"TR-2026-0213",secs:["head","client","worker","totals","clauses","sign","foot"],off:[],papers:["a4"],rent:1,trans:1},
 deliv:{g:"ops",ic:"package-check",t:"نموذج تسليم عاملة",en:"Worker Delivery Form",src:"RecruitmentContract/delivery_form_print · delivery_form_title / default_text",num:"CN-2026-0142",secs:["head","client","worker","body","sign","foot"],off:[],papers:["a4","a5"],form:"deliv"},
 shelter:{g:"ops",ic:"home",t:"إيصال إيواء — دخول",en:"Shelter Receipt",src:"ShelterContract/receipt_form_print · shelter_receipt_* · قوالب الإيواء المخصّصة",num:"SH-2026-0331",secs:["head","worker","dates","body","sign","foot"],off:[],papers:["a4","a5"],form:"shelter"},
 mreceipt:{g:"ops",ic:"user-check",t:"إيصال استلام العاملة (نقل خدمات)",en:"Worker Receipt",src:"sponsorship-transfer-maid-receipt (mPDF)",num:"TR-2026-0213",secs:["head","client","worker","totals","body","sign","foot"],off:[],papers:["a4"],form:"mrec",rent:1},
 report:{g:"rep",ic:"bar-chart-3",t:"تقرير / جدول",en:"Report",src:"v2/print/layout + rows-table · reports/print",num:"",secs:["head","filters","table","foot"],off:[],papers:["a4","a4l"],rep:1}
};
const GRP=[["fin","المالية والفواتير"],["con","العقود"],["ops","نماذج العمليات"],["rep","التقارير والقوائم"]];
const PAPER={a4:"A4 عمودي",a4l:"A4 أفقي",a5:"A5",["80"]:"حراري 80 مم"};
const defCfg=k=>{const d=DOCS[k];return {secs:d.secs.map(s=>[s,d.off.indexOf(s)<0]),cols:(d.cols||[]).map(c=>[c,(d.coff||[]).indexOf(c)<0]),paper:d.papers[0],lang:"ar",color:"#16233f",accent:"#7ab648",logo:"start",qr:"head",title:d.t,sub:d.en,hijri:1,terms:k==="quote"?"الأسعار شاملة ضريبة القيمة المضافة.\n- العرض صالح 15 يوماً من تاريخه.\n- مدة الاستقدام 60–90 يوماً من صدور التأشيرة.":"- الفاتورة صادرة إلكترونياً ومسجلة لدى هيئة الزكاة والضريبة والجمارك.\n- الاسترداد وفق سياسة الاسترداد المعتمدة في العقد.",prefix:(d.num||"").split("-")[0],copies:1,water:0,wide:0}};
const KEY="clickerp-print-cfg-v1";
let STORE={};try{STORE=JSON.parse(localStorage.getItem(KEY)||"{}")}catch(_){}
const cfg=k=>{if(!STORE[k])STORE[k]=defCfg(k);return STORE[k]};
const ID=()=>{if(!STORE.__id)STORE.__id=JSON.parse(JSON.stringify(ID0));return STORE.__id};
const save=()=>{try{localStorage.setItem(KEY,JSON.stringify(STORE))}catch(_){}};

/* ---------- بيانات العيّنة ---------- */
const LINES=[["خدمة استقدام عاملة منزلية","الفلبين · خبرة سابقة · عقد سنتين",1,15352.18,0],["الكشف الطبي والتأمين","مركز معتمد · قبل الوصول",1,434.78,0],["خدمة توصيل من المطار","الرياض · وصول نهاري",1,300,0]];
const calc=(neg)=>{const r=LINES.map(l=>{const sub=l[2]*l[3]-l[4],vat=sub*.15;return {l,sub,vat,tot:sub+vat}});const s=r.reduce((a,x)=>({sub:a.sub+x.sub,vat:a.vat+x.vat,tot:a.tot+x.tot,disc:a.disc+x.l[4]}),{sub:0,vat:0,tot:0,disc:0});return {r,s}};
const fill=(t,k)=>String(t||"").replace(/\[اسم_المكتب\]|\[اسم_الشركة\]/g,ID().name).replace(/\[اسم_العميل\]/g,"منيرة عبدالله الشهري").replace(/\[رقم_العقد\]/g,DOCS[k]?DOCS[k].num:"");
const termsHtml=t=>{const L=fill(t).split("\n").filter(x=>x.trim());let n=0;return L.map(x=>/[:：]$/.test(x.trim())?`<h5>${++n}. ${esc(x)}</h5>`:/^- /.test(x)?`<li>${esc(x.slice(2))}</li>`:`<p>${esc(x)}</p>`).join("").replace(/(<li>[\s\S]*?<\/li>)(?!<li>)/g,"$1").replace(/((?:<li>.*?<\/li>)+)/g,"<ul>$1</ul>")};

/* ---------- رسم المستند ---------- */
const DOC=(k,o)=>{o=o||{};const d=DOCS[k],c=o.cfg||cfg(k),id=ID(),bi=c.lang==="bi",th=c.paper==="80",calcd=calc();const on=s=>c.secs.some(x=>x[0]===s&&x[1]);
 const L=(ar,en)=>bi&&en?`${ar}<em>${en}</em>`:ar;const pin=n=>o.pins?`<i class="h18-pin p22-pin">${n}</i>`:"";
 const cols=(c.cols||[]).filter(x=>x[1]).map(x=>x[0]);
 const kv=l=>`<table class="p22-kv">${l.map(r=>`<tr>${r.map(x=>`<th>${x[0]}</th><td>${x[1]}</td>`).join("")}</tr>`).join("")}</table>`;
 const date=`2026-11-20${c.hijri?` <small>· 1448-06-10 هـ</small>`:""}`;
 const part={
 head:()=>`<header class="p22-head logo-${c.logo}">${pin(1)}<div class="p22-brand"><span class="p22-logo" style="color:${c.color}">${LOGO}</span><div><b>${esc(id.name)}</b>${bi||!th?`<small>${esc(id.en)}</small>`:""}${!th?`<small>س.ت ${id.cr} · الرقم الضريبي ${id.vat}</small>`:""}</div></div>
  <div class="p22-title"><h1 style="color:${c.color}">${esc(c.title)}</h1>${c.sub&&(bi||k==="inv"||k==="tinv")?`<h2>${esc(c.sub)}</h2>`:""}${d.num?`<div class="p22-no"><small>${d.bond?"رقم السند":d.tpl||d.rent?"رقم العقد":"الرقم"}</small><b>${d.num}</b></div>`:""}</div>
  ${c.qr==="head"&&on("qr")&&!d.noQr?`<div class="p22-qrw">${QR(th?110:92)}${id.zatcaLogo&&!th?'<small>هيئة الزكاة والضريبة والجمارك</small>':""}</div>`:""}</header>`,
 parties:()=>d.stmt?kv([[["العميل","منيرة عبدالله الشهري"],["رقم الحساب","110301-0142"]],[["الجوال","0551234880"],["الفرع","الفرع الرئيسي"]]]):th?kv([[["العميل","منيرة الشهري"]],[["الجوال","0551234880"]]]):`<div class="p22-parties">${pin(2)}<div><h4>${L("المورّد","Seller")}</h4><b>${esc(id.name)}</b><span>${esc(id.addr)}</span><span>الرقم الضريبي ${id.vat}</span></div><div><h4>${L("العميل","Buyer")}</h4><b>${d.b2b?"شركة نجد للمقاولات":"منيرة عبدالله الشهري"}</b><span>${d.b2b?"الرياض · حي الملقا · الرقم الضريبي 310245678900003":"الهوية 1088 •••• 21 · الرياض · حي النرجس"}</span><span>الجوال 0551234880</span></div></div>`,
 meta:()=>d.bond?kv([[["التاريخ",date],["طريقة "+(d.bond==="in"?"الاستلام":"الصرف"),"تحويل بنكي · مصرف الراجحي"]],[[d.bond==="in"?"استلمنا من":"صرفنا إلى",d.bond==="in"?"منيرة عبدالله الشهري":"Manila Prime Agency"],["المرجع",d.bond==="in"?"INV-2026-00921":"PO-2026-0044"]]]):`<div class="p22-meta">${pin(3)}${kv(th?[[["التاريخ",date]],[["الحالة","مدفوعة"]]]:[[["تاريخ الإصدار",date],["تاريخ التوريد","2026-11-20"]],[["حالة السداد",'<span class="p22-paid">مدفوعة بالكامل</span>'],["طريقة الدفع","شبكة (مدى)"]],[["رقم العقد","CN-2026-0142"],["الموظف","سارة القحطاني"]]])}</div>`,
 subject:()=>`<div class="p22-subj"><b>${d.bond?(d.bond==="in"?"وذلك عن":"وذلك مقابل"):"الوصف"}:</b> ${d.bond==="in"?"دفعة أولى من عقد الاستقدام CN-2026-0142":d.bond==="out"?"عمولة المكتب الخارجي عن عقدين — نوفمبر":"استقدام عاملة منزلية من الفلبين — عقد CN-2026-0142"}</div>`,
 orig:()=>kv([[["الفاتورة الأصلية","INV-2026-00921 · 2026-11-02"],["سبب الإشعار","إلغاء خدمة التوصيل بطلب العميل"]]]),
 lines:()=>{const rows=d.neg?[calcd.r[2]]:calcd.r;return `<div class="p22-lines">${pin(4)}<table><thead><tr><th>#</th>${cols.map(x=>`<th>${L(COL_L[x],{item:"Item",desc:"Description",price:"Price",qty:"Qty",disc:"Discount",sub:"Taxable",rate:"VAT %",vat:"VAT",total:"Total"}[x])}</th>`).join("")}</tr></thead><tbody>${rows.map((x,i)=>`<tr><td>${i+1}</td>${cols.map(cn=>`<td${cn!=="item"&&cn!=="desc"?' class="n"':""}>${{item:esc(x.l[0])+(cols.indexOf("desc")<0&&!th?`<small>${esc(x.l[1])}</small>`:""),desc:esc(x.l[1]),price:n2(x.l[3]),qty:x.l[2],disc:n2(x.l[4]),sub:n2(x.sub),rate:"15%",vat:n2(x.vat),total:n2(x.tot)}[cn]}</td>`).join("")}</tr>`).join("")}</tbody></table></div>`},
 totals:()=>{if(d.stmt)return `<div class="p22-tot">${kv([[["إجمالي المدين","18,500.00"]],[["إجمالي الدائن","12,000.00"]],[["الرصيد المستحق",'<b>6,500.00 ر.س</b>']]])}</div>`;
  if(d.rent)return `<div class="p22-tot">${kv(d.trans?[[["رسوم نقل الخدمات","6,956.52"]],[["الضريبة 15%","1,043.48"]],[["الإجمالي",'<b>8,000.00 ر.س</b>']]]:[[["التكلفة الشهرية","2,800.00"]],[["المدة","6 أشهر"]],[["الإجمالي شامل الضريبة",'<b>19,320.00 ر.س</b>']]])}</div>`;
  const s=d.neg?{sub:calcd.r[2].sub,vat:calcd.r[2].vat,tot:calcd.r[2].tot,disc:0}:calcd.s;
  return `<div class="p22-tot">${pin(5)}${kv([[[L("الإجمالي قبل الضريبة","Subtotal"),n2(s.sub)]],...(s.disc?[[["الخصم",n2(s.disc)]]]:[]),[[L("ضريبة القيمة المضافة 15%","VAT 15%"),n2(s.vat)]],[[L(d.neg?"إجمالي الإشعار":"الإجمالي شامل الضريبة","Total incl. VAT"),`<b>${n2(s.tot)} ر.س</b>`]],...(k==="inv"&&!th?[[["المدفوع",n2(s.tot)]],[["المتبقي","0.00"]]]:[])])}</div>`},
 words:()=>`<div class="p22-words">${pin(6)}<b>المبلغ كتابةً:</b> ${words(d.bond?(d.bond==="in"?5000:3450):d.neg?345:calcd.s.tot)}</div>`,
 qr:()=>c.qr==="foot"&&!d.noQr?`<div class="p22-qrf">${QR(th?120:96)}<small>امسح الرمز للتحقق من الفاتورة لدى هيئة الزكاة والضريبة والجمارك</small></div>`:"",
 amount:()=>`<div class="p22-amt" style="border-color:${c.color}"><small>${L("المبلغ","Amount")}</small><b>${d.bond==="in"?"5,000.00":"3,450.00"}</b><span>ريال سعودي</span></div>`,
 accts:()=>kv([[["الحساب المدين","2101 · ذمم المكاتب الخارجية"],["الحساب الدائن","1102 · مصرف الراجحي"]],[["مركز التكلفة","الاستقدام · الفرع الرئيسي"],["الحالة","معتمد"]]]),
 validity:()=>`<div class="p22-subj"><b>صلاحية العرض:</b> 15 يوماً حتى 2026-12-05</div>`,
 terms:()=>`<div class="p22-terms">${pin(7)}<h4>${L("الشروط والأحكام","Terms")}</h4>${termsHtml(c.terms)}</div>`,
 bank:()=>`<div class="p22-bank">${pin(8)}<h4>${L("الحسابات البنكية","Bank accounts")}</h4>${id.bank.map(b=>`<div><b>${esc(b[0])}</b><span dir="ltr">${esc(b[1])}</span></div>`).join("")}<small>باسم: ${esc(id.name)}</small></div>`,
 sign:()=>`<div class="p22-sign">${pin(9)}${(d.bond?[["المحاسب","فيصل الدوسري"],["المستلم",""],["المدير المالي",""]]:d.tpl||d.rent||d.form?[["الطرف الأول",id.name],["الطرف الثاني","منيرة عبدالله الشهري"]]:[["أعدّه","سارة القحطاني"],["العميل",""]]).map((s,i)=>`<div><small>${s[0]}</small><b>${esc(s[1])}</b><i></i>${i===0&&id.stamp?`<span class="p22-stamp" style="color:${c.color};border-color:${c.color}">${esc(id.name)}<br>س.ت ${id.cr}</span>`:""}</div>`).join("")}</div>`,
 foot:()=>`<footer class="p22-foot">${pin(10)}<span>${esc(fill(id.foot,k))}</span><span>${esc(id.addr)} · ${id.phone} · ${id.email}</span><span class="p22-pg">طُبع 2026-11-20 10:24 · مشرف النظام · صفحة 1 من 1</span></footer>`,
 client:()=>`<h4 class="p22-h">${L(d.rent&&!d.trans?"الطرف الثاني (العميل)":"بيانات العميل","Client")}</h4>`+kv([[["الاسم","منيرة عبدالله الشهري"],["رقم الهوية","1088 •••• 21"]],[["الجوال","0551234880"],["الجنسية","سعودية"]],[["العنوان الوطني","الرياض · حي النرجس · 3321"],["جهة العمل","وزارة التعليم"]]]),
 worker:()=>`<h4 class="p22-h">${L("بيانات العاملة","Worker")}</h4>`+kv([[["الاسم","MARIA SANTOS REYES"],["الجنسية","الفلبين"]],[["رقم الجواز","P4412873A"],["المهنة","عاملة منزلية"]]]),
 dates:()=>`<h4 class="p22-h">${L(d.form==="shelter"?"الدخول":"المدة والتواريخ","Dates")}</h4>`+kv(d.form==="shelter"?[[["تاريخ الدخول",date],["سبب الإيواء","انتظار نقل خدمات"]]]:[[["بداية العقد","2026-11-20"],["نهاية العقد","2027-05-19"]],[["ساعات العمل","8 ساعات يومياً"],["فترة التجربة","5 أيام"]]]),
 pay:()=>`<h4 class="p22-h">${L("جدول الدفعات","Payments")}</h4><div class="p22-lines"><table><thead><tr><th>#</th><th>القيمة</th><th>تاريخ الاستحقاق</th><th>الحالة</th></tr></thead><tbody>${[["9,660.00","2026-11-20","مدفوعة"],["9,660.00","2027-02-20","مستحقة"]].map((r,i)=>`<tr><td>${i+1}</td><td class="n">${r[0]}</td><td class="n">${r[1]}</td><td>${r[2]}</td></tr>`).join("")}</tbody></table></div>`,
 clauses:()=>`<div class="p22-terms"><h4>${L("بنود العقد","Clauses")}</h4>${(d.trans?["يلتزم الطرف الثاني بنقل خدمات العاملة خلال 7 أيام من سداد الرسوم.","تبدأ فترة التجربة من تاريخ استلام العاملة ومدتها 5 أيام.","تُسترد الرسوم كاملة إذا تعذّر النقل لسبب من الطرف الأول."]:["يلتزم الطرف الأول بتوفير عاملة لائقة صحياً طوال مدة العقد.","يلتزم الطرف الثاني بعدم تشغيل العاملة لدى الغير أو في غير المهنة المتفق عليها.","في حال تغيّب العاملة يُخصم 107.33 ر.س عن كل يوم من التكلفة."]).map((x,i)=>`<p><b>${i+1}.</b> ${x}</p>`).join("")}<small class="p22-src">البنود من «بنود العقد» — صفحة 1 من 3</small></div>`,
 body:()=>d.tpl?`<div class="p22-body"><p>إنه في يوم <b>الجمعة 2026-11-20</b> تم الاتفاق بين كل من: <b>${esc(id.name)}</b> (الطرف الأول) و<b>منيرة عبدالله الشهري</b> سعودية الجنسية، هوية رقم <b>1088 •••• 21</b> (الطرف الثاني) على استقدام عاملة منزلية من <b>الفلبين</b> حسب التأشيرة رقم <b>1302456781</b> لمدة <b>سنتين</b> براتب شهري <b>1,500 ر.س</b> وعدد ساعات عمل <b>8</b>.</p><p>البند الأول: يلتزم الطرف الأول بإنهاء إجراءات الاستقدام خلال <b>90</b> يوماً من تاريخ التعاقد…</p><small class="p22-src">النص من «إعدادات العقد» — المتغيرات (@client_name، @maid_nationality…) تُستبدل بالقيم</small></div>`
  :`<div class="p22-body"><p>${d.form==="deliv"?"أقر أنا الموقّع أدناه بأنني استلمت العاملة المذكورة بياناتها أعلاه بحالة صحية جيدة، ومعها جواز سفرها وأغراضها الشخصية، وذلك تنفيذاً للعقد رقم CN-2026-0142.":d.form==="shelter"?"تم استلام العاملة المذكورة بياناتها أعلاه في سكن الإيواء التابع للمكتب، ومعها: جواز السفر · الإقامة · حقيبة شخصية واحدة.":"أقر أنا الطرف الثاني باستلام العاملة المذكورة أعلاه وبدء فترة التجربة من تاريخه."}</p></div>`,
 open:()=>`<div class="p22-subj"><b>الرصيد الافتتاحي في 2026-10-01:</b> 0.00 ر.س</div>`,
 filters:()=>`<div class="p22-fl">${(o.repFl||["الفترة: 2026-10-01 → 2026-11-20","الفرع: الكل"]).map(x=>`<span>${esc(x)}</span>`).join("")}</div>`,
 moves:()=>`<div class="p22-lines"><table><thead><tr><th>التاريخ</th><th>المستند</th><th>البيان</th><th>مدين</th><th>دائن</th><th>الرصيد</th></tr></thead><tbody>${[["2026-11-02","INV-2026-00921","فاتورة عقد استقدام",18500,0],["2026-11-02","RV-2026-01401","سند قبض — عربون",0,5000],["2026-11-20","RV-2026-01452","سند قبض — دفعة",0,7000]].reduce((a,r)=>{const b=(a.b||0)+r[3]-r[4];a.h=(a.h||"")+`<tr><td class="n">${r[0]}</td><td class="n">${r[1]}</td><td>${r[2]}</td><td class="n">${r[3]?n2(r[3]):"—"}</td><td class="n">${r[4]?n2(r[4]):"—"}</td><td class="n"><b>${n2(b)}</b></td></tr>`;a.b=b;return a},{}).h}</tbody></table></div>`,
 table:()=>o.repTable||`<div class="p22-lines"><table><thead><tr><th>المرحلة</th><th>العقود</th><th>المتأخرة</th></tr></thead><tbody><tr><td>جديد</td><td class="n">30</td><td class="n">1</td></tr><tr><td>التفييز</td><td class="n">26</td><td class="n">5</td></tr></tbody><tfoot><tr><td>الإجمالي</td><td class="n">56</td><td class="n">6</td></tr></tfoot></table></div>`
 };
 const secs=c.secs.filter(x=>x[1]&&(!th||["head","parties","meta","lines","totals","qr","words","amount","foot","subject"].includes(x[0]))).map(x=>x[0]);
 const pg=c.paper==="a4l"||(c.wide&&d.rep)?"a4l":c.paper;
 return `<div class="p22-sheet pp-${pg}${bi?" bi":""}${th?" thermal":""}" style="--pc:${c.color};--pa:${c.accent}">${c.water?`<div class="p22-water">مسودة — غير صادرة</div>`:""}${secs.map(s=>`<section class="p22-s s-${s}" data-ps="${s}">${part[s]?part[s]():""}</section>`).join("")}</div>`};
window.__p22={DOC,DOCS,GRP,PAPER,cfg,ID,save,defCfg,SEC_L,COL_L,STORE:()=>STORE,setStore:v=>{STORE=v;save()}};

/* ---------- الطباعة الفعلية: نسخة من الورقة فقط ---------- */
const doPrint=el=>{let out=document.getElementById("p22-out");if(out)out.remove();out=document.createElement("div");out.id="p22-out";out.innerHTML=el.outerHTML;document.body.appendChild(out);document.body.classList.add("p22-printing");
 const done=()=>{document.body.classList.remove("p22-printing");out.remove();window.removeEventListener("afterprint",done)};window.addEventListener("afterprint",done);setTimeout(()=>{try{window.print()}catch(_){}setTimeout(done,500)},50)};
window.__p22.doPrint=doPrint;

/* ---------- معاينة طباعة التقرير (من مثال التقرير) ---------- */
window.__p20PRINTREP=(R,rows,groups,dim,per)=>{const tb=`<div class="p22-lines"><table><thead><tr><th>${dim}</th><th>العقود</th><th>المتأخرة</th><th>المكتملة</th><th>متوسط المدة</th></tr></thead><tbody>${groups.map(g=>`<tr><td>${g.l}</td><td class="n">${g.n}</td><td class="n">${g.late}</td><td class="n">${g.done}</td><td class="n">${g.n?g.avg+" يوم":"—"}</td></tr>`).join("")}</tbody><tfoot><tr><td>الإجمالي</td><td class="n">${rows.length}</td><td class="n">${rows.filter(x=>x.late).length}</td><td class="n">${rows.filter(x=>x.st==="completed").length}</td><td></td></tr></tfoot></table></div>`;
 const c=Object.assign({},cfg("report"),{title:"العقود حسب "+dim,sub:""});
 return `<div class="ov p22-pov"><div class="modal p22-pm"><div class="modal-h"><div><h3>معاينة الطباعة</h3><p class="ms">بقالب «تقرير / جدول» من نماذج الطباعة · ${PAPER[c.paper]}</p></div><div class="actions">${B("ghost","layout-template","تعديل القالب",'data-go="setPrint"')}${B("ghost","","إغلاق",'data-gr="pclose:1"')}${B("primary","printer","طباعة",'data-gr="pgo:1" data-p22-print=""')}</div></div><div class="p22-stage">${DOC("report",{cfg:c,repFl:[`الفترة: ${per}`,R.br?`الفرع: ${R.br}`:"كل الفروع",R.co?`الدولة: ${R.co}`:"كل الدول",R.late?"المتأخرة فقط":"",`التجميع: ${dim}`].filter(Boolean),repTable:tb})}</div></div></div>`};
document.addEventListener("click",e=>{const p=e.target.closest("[data-p22-print]");if(p){const sh=(p.closest(".p22-pm,.p22-wrap,#app")||document).querySelector(".p22-sheet");if(sh)doPrint(sh)}},true);
})();

/* ===== مثال: المستند المطبوع (guidePrint) + شاشة نماذج الطباعة (setPrint) ===== */
(()=>{
if(typeof screens==="undefined"||!window.CXH||!window.__p22)return;
const {X,TAG,HEAD,B,FLD}=window.CXH;const S=window.__p20S;const P=window.__p22;
const ico=()=>{if(window.lucide)lucide.createIcons({attrs:{"stroke-width":1.8}})};
const esc=s=>String(s==null?"":s).replace(/[&<>"]/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;"}[c]));
const toast=m=>{let el=document.querySelector(".toast");if(!el){el=document.createElement("div");el.className="toast";document.body.appendChild(el)}el.innerHTML=X("check-circle-2")+"<span>"+m+"</span>";ico();el.classList.add("show");clearTimeout(el.__t);el.__t=setTimeout(()=>el.classList.remove("show"),2600)};
const NEW='<span class="tag b h8-nw">جديد</span>';

/* ---------- مثال المستند المطبوع ---------- */
const G={k:"inv",pins:1,paper:"",lang:""};
const PINS=[["الرأس","الشعار واسم المنشأة وسجلها ورقمها الضريبي يميناً، والعنوان ونوع المستند ورقمه في الوسط، وQR يساراً. العنوان بلغة المستند والإنجليزية تحته."],["الأطراف","المورّد والعميل متقابلان بنفس الترتيب في كل المستندات. البيانات الحساسة مقنّعة (الهوية •••• )."],["بيانات المستند","التاريخ بالميلادي والهجري، وحالة السداد بلون، والمرجع (رقم العقد) ليربط الورقة بسجلها."],["البنود","أعمدة يختارها القالب، والأرقام بمحاذاة واحدة وخانتين عشريتين، والوصف تحت اسم البند إن لم يكن له عمود."],["الإجماليات","يساراً تحت البنود، والإجمالي النهائي أعرض خطاً وبالعملة. المدفوع والمتبقي للفواتير."],["المبلغ كتابةً","مطلوب في السندات، واختياري في الفواتير."],["الشروط","نص من القالب: السطر المنتهي بـ «:» عنوان مرقّم، والمبدوء بـ «- » نقطة. يقبل [اسم_المكتب] وأمثاله."],["الحسابات البنكية","من الهوية المشتركة، لا مكتوبة داخل الشروط — تتغيّر مرة واحدة لكل المستندات."],["التوقيعات والختم","خانات بأسماء الأدوار، والختم الإلكتروني فوق خانة المنشأة إن فُعّل."],["التذييل","سطر الشكر، والعنوان والتواصل، ووقت الطباعة ومن طبع، ورقم الصفحة."]];
S.guidePrint=()=>{try{const q=sessionStorage.getItem("p23k");if(q&&P.DOCS[q]){G.k=q;G.paper="";sessionStorage.removeItem("p23k")}}catch(_){}const d=P.DOCS[G.k],c=Object.assign({},P.cfg(G.k));if(G.paper&&d.papers.includes(G.paper))c.paper=G.paper;if(G.lang)c.lang=G.lang;
 return `<div id="g-prt" class="p22-wrap">${window.CERP_DOCS.EXB("نمط المستند المطبوع")}
 ${HEAD("المستند المطبوع","كل ما يُطبع من النظام بقالب واحد بعشرة أجزاء ثابتة الترتيب. اختر المستند لترى الأجزاء نفسها في كل نوع، والإعدادات تُعدّل من «نماذج الطباعة».",B("ghost","layout-template","تعديل هذا القالب",`data-go="setPrint" data-p23-goto="${G.k}"`)+B("primary","printer","طباعة",'data-p22-print=""'))}
 <div class="p23-lay"><aside class="card p23-side">
  ${P.GRP.map(g=>`<div class="p23-g"><small>${g[1]}</small>${Object.keys(P.DOCS).filter(k=>P.DOCS[k].g===g[0]).map(k=>`<button class="${k===G.k?"on":""}" data-gp="k:${k}">${X(P.DOCS[k].ic)}<span>${P.DOCS[k].t}</span></button>`).join("")}</div>`).join("")}
  <div class="p23-opt"><label>الورق</label><div class="cx-seg2">${d.papers.map(p=>`<button class="${c.paper===p?"on":""}" data-gp="paper:${p}">${P.PAPER[p]}</button>`).join("")}</div>
  <label>اللغة</label><div class="cx-seg2">${[["ar","عربي"],["bi","عربي + إنجليزي"]].map(l=>`<button class="${c.lang===l[0]?"on":""}" data-gp="lang:${l[0]}">${l[1]}</button>`).join("")}</div>
  <label class="p23-sw"><span class="sw${G.pins?" on":""}" data-gp="pins:1"></span>إظهار أرقام الشرح</label></div>
 </aside>
 <div class="p23-stage">${P.DOC(G.k,{cfg:c,pins:G.pins})}<small class="p23-src">${X("code-2")}المصدر في ClickERP: ${esc(d.src)}</small></div>
 <aside class="p23-leg"><div class="card sec"><div class="sec-h">${X("list-ordered")}<b>الأجزاء العشرة</b></div><div style="padding:0 16px 16px">
  <ol class="h18-leg">${PINS.map((x,i)=>`<li><i class="h18-pin">${i+1}</i><div><b>${x[0]}</b><span>${x[1]}</span></div></li>`).join("")}</ol></div></div>
  <div class="card sec"><div class="sec-h">${X("ruler")}<b>القياسات</b></div><div style="padding:0 16px 14px"><table class="h18-t"><tbody>${[["A4","هوامش 12 مم · خط 10.5pt · البنود حتى 12 سطراً في الصفحة الأولى"],["A5","للسندات والإيصالات · خط 9.5pt"],["حراري 80 مم","نقطة البيع والتأجير بالساعة: عمود واحد، بلا أطراف مفصّلة، QR أسفل"],["الرأس والتذييل","يتكرران في كل صفحة، ورقم الصفحة «1 من 3»"]].map(r=>`<tr><td><b>${r[0]}</b></td><td><small>${r[1]}</small></td></tr>`).join("")}</tbody></table></div></div></aside>
 </div></div>`};

/* ---------- نماذج الطباعة (الإعدادات) ---------- */
const E={tab:"tpl",k:"inv",dirty:0,sec:"layout"};
const VARS=["[اسم_المكتب]","[اسم_العميل]","[رقم_العقد]","[تاريخ_العقد]","[اسم_العاملة]","[جنسية_العاملة]","[مدة_العقد]","[التكلفة_الشهرية]"];
const mark=()=>{E.dirty=1;const d=document.querySelector("[data-p23-dirty]");if(d)d.hidden=false};
const tplList=()=>`<aside class="card p23-side p23-list"><div class="p23-q"><div class="inp">${X("search")}<input placeholder="ابحث عن نموذج" data-p23-q=""></div></div>${P.GRP.map(g=>`<div class="p23-g"><small>${g[1]}</small>${Object.keys(P.DOCS).filter(k=>P.DOCS[k].g===g[0]).map(k=>{const c=P.cfg(k);return `<button class="${k===E.k?"on":""}" data-sp="k:${k}" data-p23-name="${P.DOCS[k].t}">${X(P.DOCS[k].ic)}<span>${P.DOCS[k].t}<small>${P.PAPER[c.paper]}${c.lang==="bi"?" · ثنائي اللغة":""}</small></span></button>`}).join("")}</div>`).join("")}</aside>`;
const secEditor=()=>{const k=E.k,d=P.DOCS[k],c=P.cfg(k);
 const tabs=[["layout","rows-3","الأجزاء والترتيب"],...(d.cols?[["cols","columns-3","أعمدة البنود"]]:[]),["look","palette","الشكل والورق"],["text","type","العناوين والنصوص"]];
 const body={
  layout:()=>`<p class="p23-hint">رتّب أجزاء المستند بالأسهم، وأوقف ما لا تريد طباعته. الرأس والتذييل ثابتان.</p><div class="p23-secs">${c.secs.map((s,i)=>{const fixed=s[0]==="head"||s[0]==="foot";return `<div class="p23-row${s[1]?"":" off"}"><span class="p23-ord num">${i+1}</span><b>${P.SEC_L[s[0]]||s[0]}</b>${s[0]==="bank"||s[0]==="sign"&&k!=="qabd"?NEW:""}<span class="p23-ra"><button class="icon-btn" data-sp="up:${i}" ${i<2||fixed||i===c.secs.length-1?"disabled":""} title="لأعلى">${X("arrow-up")}</button><button class="icon-btn" data-sp="dn:${i}" ${fixed||i>=c.secs.length-2?"disabled":""} title="لأسفل">${X("arrow-down")}</button><span class="sw${s[1]?" on":""}${fixed?" dis":""}" data-sp="${fixed?"":"tg:"+i}" title="${fixed?"ثابت":"إظهار / إخفاء"}"></span></span></div>`}).join("")}</div>`,
  cols:()=>`<p class="p23-hint">الأعمدة الظاهرة في جدول البنود ${NEW}. الضريبة والإجمالي إلزاميان في الفاتورة الضريبية.</p><div class="p23-cols">${c.cols.map((x,i)=>{const req=["item","total"].includes(x[0])||(k!=="quote"&&x[0]==="vat");return `<label class="p23-col${x[1]?" on":""}${req?" req":""}"><input type="checkbox" data-sp-col="${i}"${x[1]?" checked":""}${req?" disabled":""}>${P.COL_L[x[0]]}${req?"<small>إلزامي</small>":""}</label>`}).join("")}</div><div class="cx-info" style="margin-top:12px">${X("info")}<span>${c.cols.filter(x=>x[1]).length} أعمدة ظاهرة. فوق 8 أعمدة يُفضّل A4 أفقي أو إخفاء «الإجمالي قبل الضريبة».</span></div>`,
  look:()=>`<div class="fgrid two">${FLD("الورق",`<div class="cx-seg2">${d.papers.map(p=>`<button class="${c.paper===p?"on":""}" data-sp="paper:${p}">${P.PAPER[p]}</button>`).join("")}</div>`,0,d.papers.length>1?"":"هذا المستند A4 فقط")}${FLD("اللغة "+NEW,`<div class="cx-seg2">${[["ar","عربي"],["bi","عربي + إنجليزي"]].map(l=>`<button class="${c.lang===l[0]?"on":""}" data-sp="lang:${l[0]}">${l[1]}</button>`).join("")}</div>`)}
   ${FLD("مكان الشعار",`<div class="cx-seg2">${[["start","يمين"],["center","وسط"]].map(l=>`<button class="${c.logo===l[0]?"on":""}" data-sp="logo:${l[0]}">${l[1]}</button>`).join("")}</div>`)}${d.noQr||!c.secs.some(s=>s[0]==="qr")?"":FLD("مكان رمز QR "+NEW,`<div class="cx-seg2">${[["head","في الرأس"],["foot","في الأسفل"]].map(l=>`<button class="${c.qr===l[0]?"on":""}" data-sp="qr:${l[0]}">${l[1]}</button>`).join("")}</div>`)}
   ${FLD("اللون الأساسي",`<div class="p23-colors">${["#16233f","#1d2b53","#0f5b4a","#7a2e2e","#3d2f6b","#222222"].map(x=>`<button style="background:${x}" class="${c.color===x?"on":""}" data-sp="color:${x}" title="${x}"></button>`).join("")}<input type="color" value="${c.color}" data-sp-color=""></div>`)}${FLD("خيارات",`<label class="p23-sw"><span class="sw${c.hijri?" on":""}" data-sp="hijri:1"></span>التاريخ الهجري بجانب الميلادي</label><label class="p23-sw"><span class="sw${c.water?" on":""}" data-sp="water:1"></span>علامة «مسودة» على غير الصادر</label>`)}</div>`,
  text:()=>`<div class="fgrid two">${FLD("عنوان المستند",`<div class="inp"><input data-sp-in="title" value="${esc(c.title)}"></div>`,1,"invoice_title")}${FLD("العنوان الفرعي",`<div class="inp"><input data-sp-in="sub" value="${esc(c.sub)}" dir="auto"></div>`,0,"invoice_subtitle — يظهر بالإنجليزية")}</div>
   ${c.secs.some(s=>s[0]==="terms")?`<div class="fld"><label>الشروط والأحكام</label><textarea rows="6" data-sp-in="terms">${esc(c.terms)}</textarea><small class="fhint">السطر المنتهي بـ «:» عنوان مرقّم · السطر المبدوء بـ «- » نقطة</small></div><div class="p23-vars"><small>إدراج متغير:</small>${VARS.map(v=>`<button data-sp="var:${v}">${v}</button>`).join("")}</div>`:`<div class="cx-info">${X("info")}<span>${d.tpl?"نص العقد يُحرَّر في «إعدادات العقد» بمتغيرات @ — يُنقل إلى هنا في الخطوة القادمة.":d.rent?"البنود تُدار من «بنود العقد» (صفحات 1–3) ومرتبطة بهذا القالب.":"لا توجد نصوص حرة في هذا المستند."}</span></div>`}`
 };
 return `<div class="card p23-ed"><div class="p23-edh"><span class="p23-ic">${X(d.ic)}</span><div><b>${d.t}</b><small>${esc(d.src)}</small></div>${B("ghost sm","rotate-ccw","افتراضي هذا النموذج",'data-sp="reset:1"')}</div>
  <div class="vtabs p23-vt">${tabs.map(t=>`<button class="${E.sec===t[0]?"on":""}" data-sp="sec:${t[0]}">${X(t[1])}<span>${t[2]}</span></button>`).join("")}</div>
  <div class="p23-eb">${(body[E.sec]||body.layout)()}</div></div>`};
const idTab=()=>{const id=P.ID();return `<div class="p23-two"><div class="card sec"><div class="sec-h">${X("building-2")}<b>بيانات المنشأة</b><span class="ms">تظهر في رأس وتذييل كل المستندات</span></div><div style="padding:0 18px 18px">
 <div class="p23-logo"><span class="p22-logo" style="color:var(--navy)"><svg viewBox="0 0 64 64"><rect width="64" height="64" rx="16" fill="currentColor"/><path d="M40 14c-9 2-15 9-15 18v18" stroke="#7ab648" stroke-width="6" fill="none" stroke-linecap="round"/><circle cx="40" cy="22" r="5" fill="#fff"/></svg></span><div><b>الشعار</b><small>PNG أو SVG بخلفية شفافة · 600×600 على الأقل</small></div>${B("ghost sm","upload","تغيير")}</div>
 <div class="fgrid two">${[["name","اسم المنشأة (عربي)","document_company_name"],["en","اسم المنشأة (إنجليزي)","company_name_en"],["cr","السجل التجاري","commercial_register"],["vat","الرقم الضريبي","tax_number · يُستخدم في QR"],["lic","رقم الترخيص","rent_contract_license_number"],["phone","الهاتف","phone"],["mobile","الجوال","other_phone"],["email","البريد","email"]].map(f=>FLD(f[1],`<div class="inp"><input data-sp-id="${f[0]}" value="${esc(id[f[0]])}"${/cr|vat|lic|phone|mobile|email/.test(f[0])?' dir="ltr" class="num"':""}></div>`,["name","vat","cr"].includes(f[0]),f[2])).join("")}</div>
 ${FLD("العنوان",`<div class="inp"><input data-sp-id="addr" value="${esc(id.addr)}"></div>`,0,"location · العنوان الوطني")}
 ${FLD("سطر التذييل",`<div class="inp"><input data-sp-id="foot" value="${esc(id.foot)}"></div>`,0,"document_footer_note · يقبل [اسم_المكتب]")}</div></div>
 <div style="display:flex;flex-direction:column;gap:16px"><div class="card sec"><div class="sec-h">${X("landmark")}<b>الحسابات البنكية</b>${NEW}<span class="ms">بدل كتابتها داخل الشروط</span></div><div style="padding:0 18px 16px">${id.bank.map((b,i)=>`<div class="p23-bk"><div class="inp"><input data-sp-bk="${i}:0" value="${esc(b[0])}"></div><div class="inp"><input data-sp-bk="${i}:1" value="${esc(b[1])}" dir="ltr" class="num"></div><button class="icon-btn" data-sp="bkdel:${i}" title="حذف">${X("trash-2")}</button></div>`).join("")}${B("ghost sm","plus","إضافة حساب",'data-sp="bkadd:1"')}</div></div>
 <div class="card sec"><div class="sec-h">${X("stamp")}<b>الختم والتوقيع والشعارات</b></div><div style="padding:0 18px 16px">${[["stamp","الختم الإلكتروني فوق خانة المنشأة",1],["sign","توقيع المدير المعتمد (صورة)",1],["zatcaLogo","شعار هيئة الزكاة بجانب QR",0]].map(s=>`<label class="p23-sw"><span class="sw${id[s[0]]?" on":""}" data-sp="idt:${s[0]}"></span>${s[1]} ${s[2]?NEW:""}</label>`).join("")}</div></div>
 <div class="card sec"><div class="sec-h">${X("eye")}<b>معاينة الرأس</b></div><div class="p23-mini">${P.DOC("inv",{cfg:Object.assign({},P.cfg("inv"),{secs:[["head",1],["foot",1]]})})}</div></div></div></div>`};
const numTab=()=>`<div class="card flush fx-t"><div class="p21-th"><b>الترقيم والبادئات</b><span class="ms">الرقم التالي لا يقل عن آخر رقم مستخدم · المسودات بترقيم منفصل</span></div><div class="tw"><table><thead><tr><th>المستند</th><th>البادئة</th><th>الرقم التالي</th><th>عدد الخانات</th><th>يبدأ من جديد</th><th>مثال</th></tr></thead><tbody>${Object.keys(P.DOCS).filter(k=>P.DOCS[k].num).map((k,i)=>{const c=P.cfg(k),nx=[921,922,31,118,1452,387,142,77,213,142,331,213][i]||1;return `<tr><td><b>${P.DOCS[k].t}</b></td><td><div class="inp" style="width:90px"><input value="${esc(c.prefix)}" data-sp-pre="${k}" dir="ltr"></div></td><td class="num">${nx+1}</td><td class="num">${["inv","tinv","cn","quote"].includes(k)?5:4}</td><td><select style="width:auto"><option>كل سنة</option><option>أبداً</option></select></td><td class="num" dir="ltr">${esc(c.prefix)}-2026-${String(nx+1).padStart(["inv","tinv","cn","quote"].includes(k)?5:4,"0")}</td></tr>`}).join("")}</tbody></table></div></div>`;
const diffTab=()=>`<div class="card sec"><div class="sec-h">${X("git-compare")}<b>ما الذي تغيّر عن النظام الحالي</b></div><div style="padding:0 18px 18px"><div class="tw"><table class="h18-t"><thead><tr><th>في ClickERP الآن</th><th>في التصميم الجديد</th></tr></thead><tbody>${[["إعدادات المستندات في 6 شاشات: إعدادات النماذج، إعدادات عقد التأجير، إعدادات المستندات المالية، قوالب عروض الأسعار، إعدادات العقد، قالب السيرة","شاشة واحدة «نماذج الطباعة»: الهوية المشتركة مرة واحدة، وتحتها كل النماذج"],["بيانات المنشأة مكررة (document_company_name و rent_contract_company_name و company_name) وقد تختلف بين المستندات","حقل واحد لكل معلومة يُستخدم في كل المستندات"],["الحسابات البنكية مكتوبة داخل نص الشروط","قائمة حسابات بنكية مستقلة، وجزء «الحسابات البنكية» يُظهر/يُخفى لكل مستند"],["لا يمكن اختيار أعمدة البنود ولا ترتيب الأجزاء","ترتيب الأجزاء بالأسهم، وإخفاء أي جزء، واختيار الأعمدة"],["الورق A4 والعربية فقط","A4 · A4 أفقي · A5 · حراري 80 مم، وعربي أو ثنائي اللغة"],["عقد نقل الخدمات فيه رقم ترخيص وسجل مكتوب في الكود","يأخذ من الهوية المشتركة مثل باقي المستندات"],["جدول print_setting القديم بلا شاشة","يُحذف بعد نقل الاستخدام الوحيد (إيصال دفع التأجير بالساعة)"],["الختم والتوقيع غير موجودين","ختم إلكتروني وتوقيع معتمد اختياريان"]].map(r=>`<tr><td><small>${r[0]}</small></td><td><small>${r[1]}</small></td></tr>`).join("")}</tbody></table></div></div></div>`;
S.setPrint=()=>{try{const g=sessionStorage.getItem("p23goto");if(g&&P.DOCS[g]){E.k=g;E.tab="tpl";sessionStorage.removeItem("p23goto")}}catch(_){}
 const c=P.cfg(E.k);
 return `<div id="g-sp" class="p22-wrap">${window.CERP_DOCS.EXB("شاشة الإعدادات «نماذج الطباعة»")}
 ${HEAD("نماذج الطباعة","كل ما يُطبع من النظام في مكان واحد: بيانات المنشأة مرة واحدة، وترتيب كل نموذج وأعمدته وورقه ولغته، بمعاينة حية.",`<span class="p23-dirty" data-p23-dirty=""${E.dirty?"":" hidden"}>${X("circle-dot")}تغييرات غير محفوظة</span>`+B("ghost","undo-2","تجاهل",'data-sp="discard:1"')+B("ghost","eye","مثال المستند المطبوع",'data-go="guidePrint"')+B("primary","save","حفظ النماذج",'data-sp="save:1"'))}
 <div class="vtabs p23-tabs">${[["tpl","files","النماذج",Object.keys(P.DOCS).length],["id","building-2","الهوية المشتركة"],["num","hash","الترقيم والبادئات"],["diff","git-compare","ما تغيّر"]].map(t=>`<button class="${E.tab===t[0]?"on":""}" data-sp="tab:${t[0]}">${X(t[1])}<span>${t[2]}</span>${t[3]?`<em class="num">${t[3]}</em>`:""}</button>`).join("")}</div>
 ${E.tab==="tpl"?`<div class="p23-lay3">${tplList()}${secEditor()}<div class="p23-pv"><div class="p23-pvh"><b>المعاينة</b><span class="ms">${P.PAPER[c.paper]} · بيانات عيّنة</span>${B("ghost sm","printer","طباعة تجريبية",'data-p22-print=""')}</div><div class="p23-stage sm">${P.DOC(E.k)}</div></div></div>`:E.tab==="id"?idTab():E.tab==="num"?numTab():diffTab()}
 </div>`};
const rp=()=>{const el=document.getElementById(window.CERP_PAGE==="setPrint"?"g-sp":"g-prt");if(!el)return;const y=window.scrollY,eb=document.querySelector(".p23-eb"),ebs=eb?eb.scrollTop:0;el.outerHTML=window.CERP_PAGE==="setPrint"?S.setPrint():S.guidePrint();ico();window.scrollTo(0,y);const e2=document.querySelector(".p23-eb");if(e2)e2.scrollTop=ebs};
const pv=()=>{const st=document.querySelector(".p23-pv .p23-stage");if(st){st.innerHTML=P.DOC(E.k)}const m=document.querySelector(".p23-mini");if(m)m.innerHTML=P.DOC("inv",{cfg:Object.assign({},P.cfg("inv"),{secs:[["head",1],["foot",1]]})})};
if(!window.__p23){window.__p23=1;
 document.addEventListener("click",e=>{const gt=e.target.closest("[data-p23-goto]");if(gt)try{sessionStorage.setItem("p23goto",gt.dataset.p23Goto)}catch(_){}
  if(window.CERP_PAGE==="guidePrint"){const b=e.target.closest("[data-gp]");if(!b)return;e.preventDefault();const [k,v]=b.dataset.gp.split(":");if(k==="k"){G.k=v;G.paper="";}else if(k==="pins")G.pins=G.pins?0:1;else G[k]=v;rp();return}
  if(window.CERP_PAGE!=="setPrint")return;const b=e.target.closest("[data-sp]");if(!b||!b.dataset.sp)return;e.preventDefault();
  const i=b.dataset.sp.indexOf(":"),k=b.dataset.sp.slice(0,i),v=b.dataset.sp.slice(i+1);const c=P.cfg(E.k),id=P.ID();
  if(k==="tab"){E.tab=v}else if(k==="k"){E.k=v;if(E.sec==="cols"&&!P.DOCS[v].cols)E.sec="layout"}else if(k==="sec"){E.sec=v}
  else if(k==="up"||k==="dn"){const a=+v,b2=k==="up"?a-1:a+1;[c.secs[a],c.secs[b2]]=[c.secs[b2],c.secs[a]];mark()}
  else if(k==="tg"){c.secs[+v][1]=!c.secs[+v][1];mark()}
  else if(["paper","lang","logo","qr","color"].includes(k)){c[k]=v;mark()}
  else if(k==="hijri"||k==="water"){c[k]=c[k]?0:1;mark()}
  else if(k==="var"){const ta=document.querySelector('[data-sp-in="terms"]');if(ta){const p=ta.selectionStart||ta.value.length;ta.value=ta.value.slice(0,p)+v+ta.value.slice(p);c.terms=ta.value;mark();pv();ta.focus();ta.selectionStart=ta.selectionEnd=p+v.length;return}}
  else if(k==="reset"){const S2=P.STORE();S2[E.k]=P.defCfg(E.k);mark();toast("رجع «"+P.DOCS[E.k].t+"» للافتراضي — احفظ لتثبيته")}
  else if(k==="idt"){id[v]=id[v]?0:1;mark()}
  else if(k==="bkadd"){id.bank.push(["",""]);mark()}else if(k==="bkdel"){id.bank.splice(+v,1);mark()}
  else if(k==="save"){P.save();E.dirty=0;toast("حُفظت النماذج — تُطبَّق على كل مستند يُطبع من الآن");}
  else if(k==="discard"){try{P.setStore(JSON.parse(localStorage.getItem("clickerp-print-cfg-v1")||"{}"))}catch(_){P.setStore({})}E.dirty=0;toast("أُلغيت التغييرات غير المحفوظة")}
  rp()});
 document.addEventListener("input",e=>{if(window.CERP_PAGE!=="setPrint")return;const t=e.target;const c=P.cfg(E.k),id=P.ID();
  if(t.dataset.spIn){c[t.dataset.spIn]=t.value;mark();pv()}else if(t.dataset.spId){id[t.dataset.spId]=t.value;mark();pv()}else if(t.dataset.spBk){const [a,b]=t.dataset.spBk.split(":");id.bank[+a][+b]=t.value;mark()}
  else if(t.dataset.spPre){P.cfg(t.dataset.spPre).prefix=t.value;mark()}else if(t.matches("[data-sp-color]")){c.color=t.value;mark();pv()}
  else if(t.matches("[data-p23-q]")){const q=t.value.trim();document.querySelectorAll("[data-p23-name]").forEach(x=>{x.hidden=q&&x.dataset.p23Name.indexOf(q)<0})}});
 document.addEventListener("change",e=>{if(window.CERP_PAGE!=="setPrint")return;const t=e.target;if(t.dataset.spCol!=null){P.cfg(E.k).cols[+t.dataset.spCol][1]=t.checked;mark();rp()}});
 window.addEventListener("beforeunload",e=>{if(window.CERP_PAGE==="setPrint"&&E.dirty){e.preventDefault();e.returnValue=""}});}
})();

/* ---------- قسم «الطباعة والنماذج» في الدليل ---------- */
(()=>{if(!window.__p20SetPrintSec||!window.__p22)return;const {X,B}=window.CXH;const P=window.__p22;
const DO=(d,n)=>`<div class="h18-dd"><div class="do"><b>${X("check-circle-2")}افعل</b><ul>${d.map(x=>`<li>${x}</li>`).join("")}</ul></div><div class="dont"><b>${X("x-circle")}لا تفعل</b><ul>${n.map(x=>`<li>${x}</li>`).join("")}</ul></div></div>`;
window.__p20SetPrintSec(()=>`<section class="card h18-sec" id="g-print"><div class="h18-h"><span class="num">#</span><div><h2>الطباعة والنماذج</h2><p>كل مستند يخرج من النظام (فاتورة، سند، عقد، إيصال، كشف، تقرير) يُبنى من قالب واحد بعشرة أجزاء، وكل إعداداته في شاشة واحدة «نماذج الطباعة».</p></div></div>
 <div class="h18-two"><div class="p20-pvmini">${P.DOC("inv",{pins:1})}</div><div>
 <ol class="h18-leg">${[["الرأس","الشعار والمنشأة · العنوان ورقم المستند · QR"],["الأطراف","المورّد ثم العميل، بنفس الترتيب دائماً"],["بيانات المستند","التاريخ ميلادي وهجري · حالة السداد · المرجع"],["البنود","أعمدة يختارها القالب"],["الإجماليات","قبل الضريبة · الضريبة · الإجمالي · المدفوع والمتبقي"],["المبلغ كتابةً","إلزامي في السندات"],["الشروط","نص بالقالب ومتغيرات [اسم_المكتب]"],["الحسابات البنكية","من الهوية المشتركة"],["التوقيعات والختم","بأسماء الأدوار"],["التذييل","الشكر والتواصل ووقت الطباعة ورقم الصفحة"]].map((x,i)=>`<li><i class="h18-pin">${i+1}</i><div><b>${x[0]}</b><span>${x[1]}</span></div></li>`).join("")}</ol>
 <div style="display:flex;gap:8px;margin-top:14px">${B("primary sm","printer","مثال: المستند المطبوع",'data-go="guidePrint"')}${B("ghost sm","layout-template","نماذج الطباعة",'data-go="setPrint"')}</div></div></div>
 <div class="h18-mini-t" style="margin-top:16px">كل ما يُطبع من النظام (${Object.keys(P.DOCS).length} نماذج)</div>
 <div class="tw"><table class="h18-t"><thead><tr><th>المستند</th><th>الورق</th><th>أجزاء خاصة</th><th>المصدر في ClickERP</th></tr></thead><tbody>${P.GRP.map(g=>`<tr class="p20-gr"><td colspan="4"><b>${g[1]}</b></td></tr>`+Object.keys(P.DOCS).filter(k=>P.DOCS[k].g===g[0]).map(k=>{const d=P.DOCS[k];const sp=d.secs.filter(s=>!["head","foot","sign","parties","meta","totals"].includes(s)).map(s=>P.SEC_L[s].replace(/ \(.*\)/,"")).slice(0,4).join(" · ");return `<tr><td><a href="#" data-go="guidePrint" data-p23-k="${k}">${d.t}</a></td><td><small>${d.papers.map(p=>P.PAPER[p]).join(" · ")}</small></td><td><small>${sp}</small></td><td><small dir="ltr" class="p20-src">${d.src}</small></td></tr>`}).join("")).join("")}</tbody></table></div>
 <div class="h18-two" style="margin-top:14px"><div><div class="h18-mini-t">قواعد الطباعة</div><div class="tw"><table class="h18-t"><tbody>${[["الطريقة","طباعة المتصفح (HTML) هي الأصل — dompdf لا يشكّل العربية جيداً. ملف PDF يُولَّد بمتصفح بلا واجهة عند الإرسال بالبريد أو واتساب"],["اللغة","عربي، أو ثنائي (العنوان والرؤوس بالإنجليزية تحت العربية). لا مستند إنجليزي فقط للعملاء"],["الأرقام","أرقام إنجليزية 0–9، فاصل الآلاف، خانتان عشريتان، والعملة «ر.س» مع الإجمالي"],["البيانات الحساسة","رقم الهوية والإقامة مقنّعان في الإيصالات، كاملان في العقود فقط"],["الصفحات","الرأس والتذييل يتكرران، ورؤوس الجدول تتكرر، ولا يُقطع صف بين صفحتين"],["الحالة","«مسودة — غير صادرة» علامة مائية على غير المرحّل، و«ملغاة» على الملغى، وQR فقط للصادر"],["من طبع","وقت الطباعة واسم المستخدم في التذييل، وكل طباعة تُسجَّل في سجل النشاط"]].map(r=>`<tr><td style="width:120px"><b>${r[0]}</b></td><td><small>${r[1]}</small></td></tr>`).join("")}</tbody></table></div></div>
 <div>${DO(["اقرأ بيانات المنشأة من «الهوية المشتركة» فقط","اجعل زر «طباعة» يفتح معاينة بالقالب، والطباعة منها","أضف أي مستند جديد كنموذج في «نماذج الطباعة» بأجزائه","التقارير والجداول تُطبع بقالب «تقرير / جدول»: الفترة والفلاتر في الرأس"],["لا تكتب اسم المنشأة أو رقم الترخيص داخل الكود أو القالب","لا تضع الحسابات البنكية داخل نص الشروط","لا تطبع الواجهة نفسها (أزرار وقوائم) بـ Ctrl+P","لا تُصدر QR لمستند غير صادر"])}</div></div></section>`);
document.addEventListener("click",e=>{const a=e.target.closest("[data-p23-k]");if(a)try{sessionStorage.setItem("p23k",a.dataset.p23K)}catch(_){}},true);
})();

/* ---------- تشغيل: لو الصفحة الحالية من صفحات الملف ده، ارسمها ---------- */
(()=>{if(typeof screens==="undefined"||!window.__p20S)return;const S=window.__p20S;Object.keys(S).forEach(k=>{screens[k]=S[k]});
 const P0=window.CERP_PAGE;if(P0&&(S[P0]||P0==="designGuide")&&typeof render==="function"){try{cur=P0}catch(_){}render();
  if(P0==="designGuide"&&location.hash){const el=document.querySelector(location.hash);if(el)setTimeout(()=>el.scrollIntoView({block:"start"}),60)}}})();
