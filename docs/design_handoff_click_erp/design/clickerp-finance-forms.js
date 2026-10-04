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
