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
