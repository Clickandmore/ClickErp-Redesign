/* ClickERP — التجارة (v1.75): الميزات وقوالب النشاط — مصدر واحد تقرأه لوحة التحكم (cm-core.js) وSuper Admin (superadmin/commerce.html).
   في الكود: CommerceSettings (مفاتيح الميزات) + config/commerce_templates.php (القوالب). القيم المحفوظة في localStorage «cerp.commerce.v1» للمعاينة. */
window.CM_FEAT=[
 /* [المفتاح, الاسم, المعنى, يتطلب, مرحلة: ""=متاح · "soon"=قريباً] */
 ["inventory","المخزون","المنتجات والمخازن والتكلفة","",""],
 ["pos","نقاط البيع","الكاشير والأجهزة","inventory",""],
 ["multi_uom","وحدات متعددة","حبة، كرتون، كيس، طن","inventory",""],
 ["weighted","البيع بالوزن","باركود الميزان","inventory",""],
 ["batches_expiry","التشغيلات والصلاحية","رقم تشغيلة وتاريخ انتهاء","inventory",""],
 ["serials","الرقم التسلسلي","IMEI وما شابه","inventory",""],
 ["variants","الخيارات","مقاس، لون","inventory","soon"],
 ["price_lists","قوائم الأسعار","جملة، تجزئة، لعميل","inventory",""],
 ["credit_sales","البيع الآجل","حد ائتمان في الكاشير","pos",""],
 ["delivery_notes","سند الصرف","تسليم منفصل عن الفاتورة","inventory",""],
 ["recipes","الوصفات","البيع يخصم الخامات","restaurant",""],
 ["restaurant","المطعم","طاولات، محلي/سفري/توصيل، إضافات","pos",""],
 ["kds","المطبخ","طابعة أو شاشة المطبخ","restaurant",""],
 ["promotions","العروض","خصومات تلقائية","pos",""],
 ["loyalty","الولاء","نقاط العملاء","pos","soon"]];
/* القوالب = config/commerce_templates.php — 1 مفعّل · 0 مقفول · "o" اختياري (مقفول افتراضياً) */
window.CM_TPL={
 grocery:{n:"بقالة",i:"shopping-basket",d:"تموينات وسوبرماركت صغير",launch:1,f:{inventory:1,pos:1,multi_uom:1,weighted:1,batches_expiry:1,serials:0,variants:0,price_lists:1,credit_sales:"o",delivery_notes:0,recipes:0,restaurant:0,kds:0,promotions:1,loyalty:0}},
 feed:{n:"أعلاف",i:"wheat",d:"أعلاف ومستلزمات مواشي، بيع بالطن والكيس",launch:1,f:{inventory:1,pos:1,multi_uom:1,weighted:1,batches_expiry:1,serials:0,variants:0,price_lists:1,credit_sales:1,delivery_notes:1,recipes:0,restaurant:0,kds:0,promotions:0,loyalty:0}},
 restaurant:{n:"مطاعم",i:"utensils-crossed",d:"مطعم أو مقهى بطاولات ومطبخ",launch:0,f:{inventory:1,pos:1,multi_uom:1,weighted:0,batches_expiry:1,serials:0,variants:0,price_lists:0,credit_sales:0,delivery_notes:0,recipes:1,restaurant:1,kds:1,promotions:1,loyalty:0}},
 retail:{n:"تجزئة عامة",i:"store",d:"محل ملابس أو إلكترونيات أو أدوات",launch:1,f:{inventory:1,pos:1,multi_uom:0,weighted:0,batches_expiry:0,serials:"o",variants:1,price_lists:1,credit_sales:0,delivery_notes:0,recipes:0,restaurant:0,kds:0,promotions:1,loyalty:0}},
 wholesale:{n:"جملة وتوزيع",i:"truck",d:"بيع بالكرتون لعملاء آجل وتسليم منفصل",launch:0,f:{inventory:1,pos:"o",multi_uom:1,weighted:0,batches_expiry:1,serials:0,variants:0,price_lists:1,credit_sales:1,delivery_notes:1,recipes:0,restaurant:0,kds:0,promotions:0,loyalty:0}},
 custom:{n:"مخصص",i:"sliders-horizontal",d:"تضبط كل ميزة بنفسك",launch:1,f:null}};
