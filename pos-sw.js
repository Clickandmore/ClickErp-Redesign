/* ClickERP — كاشير الويب (v1.80): بيحفظ واجهة التطبيق على الجهاز عشان يفتح بسرعة وحتى لو الإنترنت ضعيف.
   الفواتير نفسها بتصدر من السيرفر (تحتاج اتصال)؛ في النظام الحقيقي /api/pos/v1 ما بيتخزّنش هنا. */
const C="clickerp-pos-v1.80";
const SHELL=["pos.html","pos.webmanifest","assets/clickerp.bundle.css","assets/commerce/pos.css","assets/commerce/pos.js","assets/pos/icon-192.png","assets/pos/icon-512.png"];
self.addEventListener("install",e=>{e.waitUntil(caches.open(C).then(c=>c.addAll(SHELL)).then(()=>self.skipWaiting()))});
self.addEventListener("activate",e=>{e.waitUntil(caches.keys().then(ks=>Promise.all(ks.filter(k=>k!==C).map(k=>caches.delete(k)))).then(()=>self.clients.claim()))});
self.addEventListener("fetch",e=>{const u=new URL(e.request.url);if(e.request.method!=="GET"||u.pathname.includes("/api/"))return;
 /* الواجهة: من الشبكة الأول (عشان التحديثات توصل)، ولو مفيش اتصال من النسخة المحفوظة */
 e.respondWith(fetch(e.request).then(r=>{const cp=r.clone();if(r.ok||r.type==="opaque")caches.open(C).then(c=>c.put(e.request,cp));return r}).catch(()=>caches.match(e.request,{ignoreSearch:true})))});
