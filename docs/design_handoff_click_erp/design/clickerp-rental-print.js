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
