/* ClickERP — الدورة التجارية (v1.82): شريط واحد أعلى كل شاشة في الدورة يوضح الخطوة الحالية والخطوة التالية،
   ويكمّل الانتقالات اللي كانت ناقصة بين الشاشات: التحصيل من فاتورة البيع، الدفع للمورد، الشراء للمنتجات تحت حد الطلب،
   إيداع نقدية الوردية في البنك، وإقرار الضريبة. كل إجراء بيعرض القيد قبل الحفظ (الخطة §5.3). المرجع للتطوير: design/COMMERCE-CYCLE.md */
(window.CERP_EXT=window.CERP_EXT||[]).push(()=>{
const {X,B,FLD,SEL}=window.CXH;const CM=window.CM;const P=window.CERP_PAGE||"";
/* مراحل الدورة: [المفتاح, الاسم, الأيقونة, الشاشة, الشاشات اللي تبعها] */
const STEPS=[
 ["setup","التجهيز","settings-2","cmSettings",["cmSettings","cmStores","cmCats","cmUnits","cmImport","cmRegisters","cmDevicePair","cmCashiers","cmPayMethods","cmZatca","cmZatcaWizard"]],
 ["buy","الشراء والاستلام","shopping-bag","cmPurchases",["cmPurchases","cmPurchaseAdd"]],
 ["stock","المخزون","boxes","cmStock",["cmStock","cmDocs","cmDocShow","cmItems","cmItemAdd","cmItemCard","cmPrices","cmPriceEdit","cmPromos"]],
 ["sell","البيع","receipt","cmSales",["finQuotes","finQuoteAdd","finQuoteShow","cmSales","cmSaleAdd","cmSaleShow","cmPosInvoices","cmPosInvoiceShow","cmDash"]],
 ["collect","التحصيل والدفع","hand-coins","cmSaleShow",[]],
 ["return","المرتجعات","undo-2","cmReturns",["cmReturns","cmReturnAdd"]],
 ["close","الإقفال","lock","cmShifts",["cmShifts","cmShiftShow","cmSettle","cmCount","cmCountRun"]],
 ["report","التقارير والضريبة","bar-chart-3","cmReports",["cmReports"]]];
const cur=STEPS.findIndex(s=>s[4].includes(P));
/* الخطوة التالية لكل شاشة: [النص, الأيقونة, الإجراء] — الإجراء go:شاشة أو do:نافذة */
const NEXT={
 cmPurchaseAdd:[["دفع للمورد (سند صرف)","banknote","do:pay"],["كشف حساب المورد","file-text","go:finSuppliers"],["مرتجع مشتريات","undo-2","go:cmReturnAdd"]],
 cmPurchases:[["دفع للمورد (سند صرف)","banknote","do:pay"],["فاتورة شراء","plus","go:cmPurchaseAdd"]],
 cmStock:[["اطلب شراء المنتجات تحت حد الطلب","shopping-bag","do:reorder"],["جرد","scan-barcode","go:cmCount"]],
 cmItems:[["اطلب شراء المنتجات تحت حد الطلب","shopping-bag","do:reorder"]],
 cmDocs:[["الكميات في المخزن","layers","go:cmStock"]],
 finQuoteShow:[["فواتير البيع","receipt","go:cmSales"]],
 finQuotes:[["عرض سعر جديد","plus","go:finQuoteAdd"]],
 cmSaleAdd:[["بعد التأكيد: التسليم ثم التحصيل","info","go:cmSaleShow"]],
 cmSaleShow:[["تسجيل تحصيل (سند قبض)","hand-coins","do:collect"],["كشف حساب العميل","file-text","go:finClients"],["إنشاء مرتجع","undo-2","go:cmReturnAdd"]],
 cmSales:[["تسجيل تحصيل (سند قبض)","hand-coins","do:collect"],["فاتورة بيع","plus","go:cmSaleAdd"]],
 cmPosInvoices:[["ورديات الكاشير","clock","go:cmShifts"]],
 cmDash:[["افتح الكاشير","monitor-smartphone","href:pos.html"],["ورديات بانتظار الترحيل","lock","go:cmShifts"]],
 cmReturns:[["مرتجع جديد","plus","go:cmReturnAdd"]],
 cmReturnAdd:[["رد المبلغ للعميل (سند صرف)","banknote","do:refund"]],
 cmShiftShow:[["إيداع نقدية الوردية في البنك","landmark","do:deposit"],["تسوية مدى والبطاقات","credit-card","go:cmSettle"]],
 cmShifts:[["إيداع نقدية الورديات في البنك","landmark","do:deposit"]],
 cmSettle:[["البنوك والصناديق","landmark","go:finBanks"]],
 cmCountRun:[["الكميات في المخزن","layers","go:cmStock"]],
 cmReports:[["إقرار ضريبة القيمة المضافة","badge-check","go:finTaxReports"],["ميزان المراجعة","scale","go:finBalances"]],
 cmSettings:[["أضف المنتجات","package","go:cmItems"],["رصيد افتتاحي","file-plus","go:cmDocShow"]]};
if(cur<0&&!NEXT[P])return;
const bar=()=>{const nx=NEXT[P]||[];
 return `<div class="cmc-cycle" data-cmc-cycle=""><div class="cmc-steps" role="list" aria-label="الدورة التجارية">${STEPS.map((s,i)=>`<a role="listitem" href="#" data-go="${s[3]}" class="cmc-st${i===cur?" on":i<cur?" done":""}" title="${s[1]}">${X(s[2])}<span>${s[1]}</span></a>`).join(`<i class="cmc-ar">${X("chevron-left")}</i>`)}</div>
 ${nx.length?`<div class="cmc-next"><small>الخطوة التالية</small>${nx.map((n,j)=>{const[k,v]=n[2].split(":");const at=k==="go"?`data-go="${v}"`:k==="href"?"":`data-cmc-do="${v}"`;return k==="href"?`<a class="btn btn-${j?"ghost":"primary"} sm" href="${v}" target="_blank">${X(n[1])}${n[0]}</a>`:B((j?"ghost":"primary")+" sm",n[1],n[0],at)}).join("")}</div>`:""}</div>`};
const put=()=>{const pg=document.querySelector("#app .page");if(!pg||pg.querySelector("[data-cmc-cycle]"))return;const h=pg.querySelector(".page-head");if(!h)return;
 const d=document.createElement("div");d.innerHTML=bar();const cy=d.firstElementChild;h.after(cy);
 /* جاي من خطوة قبلها: بنقول من فين جت البيانات */
 let from="";try{if(P==="cmSaleAdd"&&sessionStorage.getItem("cmFromQuote"))from=`من عرض السعر <b class="cm-mono">${sessionStorage.getItem("cmFromQuote")}</b>: العميل والبنود والأسعار منقولة. اختر المخزن وراجع الرصيد المتاح ثم أكّد.`;
  if(P==="cmPurchaseAdd"&&sessionStorage.getItem("cmReorder"))from="من «اطلب شراء»: المنتجات تحت حد الطلب مضافة بكمية تكمّل الحد. اختر المورد وراجع الكميات والأسعار.";}catch(_){}
 if(from){const n=document.createElement("div");n.innerHTML=CM.note(from,"ok","corner-down-left");cy.after(n.firstElementChild)}if(window.lucide)lucide.createIcons({attrs:{"stroke-width":1.8}})};
const pg=document.querySelector("#app .page");if(pg){new MutationObserver(put).observe(pg,{childList:true});setTimeout(put,0)}

/* ---------- نوافذ الإجراءات: نموذج قصير + معاينة القيد + حفظ ---------- */
const A=CM.D.accounts;
const DLG={
 collect:{t:"تسجيل تحصيل — سند قبض",ic:"hand-coins",who:["العميل","مؤسسة الندى للتموينات — SI-2026-0412"],amt:5470.94,acc:[["الصندوق الرئيسي","1101 الصندوق الرئيسي"],["البنك الأهلي — 4408","110202 البنك الأهلي"],["مدى — وسيط","1131 وسيط مدى"]],
  je:(a,acc)=>[[acc,"",a,0],["1121 العملاء — مؤسسة الندى للتموينات","",0,a]],done:"سُجّل سند القبض RV-2026-0119 وقُيد على حساب العميل. الفاتورة: مدفوعة جزئياً → يتحدث في كشف حسابه.",no:"RV-2026-0119"},
 pay:{t:"دفع للمورد — سند صرف",ic:"banknote",who:["المورد","مؤسسة التوريدات الحديثة — PI-2026-0431"],amt:7154.4,acc:[["البنك الأهلي — 4408","110202 البنك الأهلي"],["مصرف الراجحي — 1122","110201 مصرف الراجحي"],["الصندوق الرئيسي","1101 الصندوق الرئيسي"]],
  je:(a,acc)=>[["2101 الموردون — مؤسسة التوريدات الحديثة","",a,0],[acc,"",0,a]],done:"سُجّل سند الصرف PV-2026-0088 وقُيد على حساب المورد.",no:"PV-2026-0088"},
 refund:{t:"رد مبلغ للعميل — سند صرف",ic:"banknote",who:["العميل","مؤسسة الندى للتموينات — RT-2026-0031"],amt:379.5,acc:[["الصندوق الرئيسي","1101 الصندوق الرئيسي"],["البنك الأهلي — 4408","110202 البنك الأهلي"]],
  je:(a,acc)=>[["1121 العملاء — مؤسسة الندى للتموينات","",a,0],[acc,"",0,a]],done:"سُجّل سند الصرف PV-2026-0089 لرد قيمة المرتجع.",no:"PV-2026-0089"},
 deposit:{t:"إيداع نقدية الوردية في البنك",ic:"landmark",who:["من","صندوق كاشير 1 — SH-A1-0097 (المعدود 4,365.00)"],amt:4365,acc:[["البنك الأهلي — 4408","110202 البنك الأهلي"],["مصرف الراجحي — 1122","110201 مصرف الراجحي"]],
  je:(a,acc)=>[[acc,"",a,0],["1103 صندوق كاشير 1","",0,a]],done:"سُجّل الإيداع (تحويل بين الحسابات TR-2026-0057). رصيد صندوق كاشير 1 = العهدة الافتتاحية فقط.",no:"TR-2026-0057"}};
let st=null;
const dlgH=()=>{const d=DLG[st.k],acc=d.acc.find(x=>x[0]===st.acc)||d.acc[0];const a=+st.amt||0;
 return `<div class="cmc-ov" data-cmc-ov=""><div class="card cmc-dlg" role="dialog" aria-modal="true"><div class="cm-sec-h">${X(d.ic)}<b>${d.t}</b><div class="cm-acts"><button class="icon-btn" data-cmc-x="" aria-label="إغلاق">${X("x")}</button></div></div>
 ${st.done?`<div class="cmc-done">${X("check-circle-2")}<div><b>تم — <span class="cm-mono">${d.no}</span></b><p>${d.done}</p><div class="cm-acts">${B("ghost sm","printer","طباعة السند",'data-cm-toast="جُهّز السند للطباعة"')}${B("primary sm","check","تم",'data-cmc-x=""')}</div></div></div>`:
 `<div class="cmc-who"><small>${d.who[0]}</small><b>${d.who[1]}</b></div>
 <div class="fgrid">${FLD("المبلغ",`<div class="inp"><input class="num" dir="ltr" data-cmc-amt="" value="${st.amt}"></div>`,1,st.k==="collect"?"المتبقي على الفاتورة "+CM.n(d.amt):"")}${FLD(st.k==="pay"||st.k==="refund"?"من حساب":"إلى حساب",`<select data-cmc-acc="">${d.acc.map(x=>`<option${x[0]===acc[0]?" selected":""}>${x[0]}</option>`).join("")}</select>`,1)}${FLD("التاريخ",'<input type="date" value="2026-11-20">',1)}${FLD("المرجع",'<div class="inp"><input placeholder="رقم التحويل أو الشيك (اختياري)"></div>')}</div>
 ${a>d.amt+0.001?CM.note(`المبلغ أكبر من ${st.k==="deposit"?"النقد المعدود":"المستحق"} (${CM.n(d.amt)}).`,"bad"):""}
 ${CM.jprev("القيد الذي سيُسجّل",d.je(a,acc[1]),"مرحّل بالحالة المعتمدة · يرتبط بالمستند")}
 <div class="cmi-foot"><span></span><div class="cm-acts">${B("ghost","","إلغاء",'data-cmc-x=""')}${B("primary","save","حفظ",'data-cmc-save=""'+(a<=0||a>d.amt+0.001?" disabled":""))}</div></div>`}</div></div>`};
const draw=()=>{let o=document.getElementById("cmcDlg");if(!st){if(o)o.remove();return}if(!o){o=document.createElement("div");o.id="cmcDlg";document.body.appendChild(o)}o.innerHTML=dlgH();if(window.lucide)lucide.createIcons({attrs:{"stroke-width":1.8}})};
if(!window.__cmCycle){window.__cmCycle=1;
 document.addEventListener("click",e=>{const c=s=>e.target.closest(s);let x;
  if((x=c("[data-cmc-do]"))){e.preventDefault();const k=x.dataset.cmcDo;
   if(k==="reorder"){try{sessionStorage.setItem("cmReorder","1")}catch(_){}CM.toast("جُهّزت فاتورة شراء بالمنتجات تحت حد الطلب (3 منتجات) — راجع الكميات والمورد","shopping-bag");setTimeout(()=>location.href="cmPurchaseAdd.html",700);return}
   st={k,amt:String(DLG[k].amt),acc:DLG[k].acc[0][0]};draw();return}
  if(c("[data-cmc-x]")||(e.target.id==="cmcDlg")||(e.target.matches&&e.target.matches("[data-cmc-ov]"))){st=null;draw();return}
  if(c("[data-cmc-save]")){st.done=1;draw();return}});
 document.addEventListener("input",e=>{if(e.target.matches("[data-cmc-amt]")&&st){st.amt=e.target.value;const p=e.target.selectionStart;draw();const n=document.querySelector("[data-cmc-amt]");if(n){n.focus();n.setSelectionRange(p,p)}}});
 document.addEventListener("change",e=>{if(e.target.matches("[data-cmc-acc]")&&st){st.acc=e.target.value;draw()}});
 document.addEventListener("keydown",e=>{if(e.key==="Escape"&&st){st=null;draw()}});
 /* ?cycle=collect|pay|refund|deposit — يفتح النافذة مباشرة (للروابط والاختبار) */
 const cq=new URLSearchParams(location.search).get("cycle");if(cq&&DLG[cq])setTimeout(()=>{st={k:cq,amt:String(DLG[cq].amt),acc:DLG[cq].acc[0][0]};draw()},500);
}
});
