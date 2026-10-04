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
