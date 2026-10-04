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
