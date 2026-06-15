# خطة فصل ربط منصة الاستقدام على سيرفر داخل السعودية

## الهدف

حل شرط منصة الاستقدام الحكومية بوجود سيرفر داخل السعودية بدون نقل نظام ClickERP كاملًا إلى السعودية.

الفكرة هي فصل جزء الربط فقط في خدمة صغيرة داخل السعودية، ويظل النظام الرئيسي على السيرفر الحالي خارج السعودية. النظام الرئيسي يتعامل مع الخدمة السعودية كوسيط آمن، والخدمة السعودية هي التي تتصل بمنصة الاستقدام / Qiwa.

## ملخص القرار

نعم، يمكن تنفيذ الفكرة فنيًا، وهي الأنسب من ناحية التكلفة وتقليل المخاطر.

بدل نقل كل النظام وقاعدة البيانات والملفات إلى سيرفر سعودي، ننشئ خدمة مستقلة باسم مثل:

```text
Istiqdam Gateway
```

وتكون مسؤولة فقط عن:

- الاتصال بمنصة الاستقدام الحكومية.
- حفظ بيانات الاعتماد الرسمية.
- توليد وتجديد OAuth token.
- تنفيذ عمليات الاستقدام.
- حفظ سجل الطلبات والاستجابات والأخطاء.
- إرجاع النتائج للنظام الرئيسي.

## الشكل العام

```text
ClickERP الحالي خارج السعودية
        |
        | HTTPS + API Token / HMAC Signature
        v
Istiqdam Gateway داخل السعودية
        |
        | OAuth2 + API-KEY + Saudi Server IP
        v
منصة الاستقدام الحكومية / Qiwa
```

## سبب مناسبة الحل للمشروع الحالي

التكامل الحالي في المشروع مفصول نسبيًا بالفعل في الملفات التالية:

- `config/istiqdam.php`
- `app/Services/IstiqdasmService.php`
- `app/Http/Controllers/admin/IstiqdasmRealController.php`
- جدول `istiqdam_contracts`

لذلك لا نحتاج إعادة بناء النظام. المطلوب هو تحويل طبقة الخدمة الحالية من الاتصال المباشر بمنصة Qiwa إلى الاتصال بـ Gateway سعودي.

## الخدمات المطلوبة حسب ملف الربط

حسب ملف:

```text
docs/Istiqdam as a Service v1.2.pdf
```

الخدمات الأساسية هي:

- Authentication Service للحصول على OAuth2 token.
- Get Packages Service لجلب الباقات.
- Get Package Service لجلب تفاصيل باقة.
- Create Contract Service لإنشاء عقد.
- Get Contract Details Service لجلب تفاصيل عقد.
- Cancel Contract Service لإلغاء عقد.
- Download Contract Service لتحميل العقد PDF.
- Work Permit Assignment Service لإسناد تصريح العمل.

كل هذه الخدمات مناسبة لفصلها داخل Gateway لأنها REST/HTTPS ومتزامنة.

## مسؤوليات السيرفر السعودي

السيرفر داخل السعودية يجب أن يحتوي فقط على خدمة الربط، وليس النظام الكامل.

المسؤوليات:

- حفظ مفاتيح الربط الحكومية:
  - `Client ID`
  - `Client Secret`
  - `API Key`
  - `Scope`
- تنفيذ OAuth2 token generation.
- تخزين token في cache لمدة أقل من مدة صلاحيته.
- استقبال طلبات النظام الرئيسي.
- التحقق من صلاحية الطلبات القادمة من النظام الرئيسي.
- تنفيذ الطلبات على منصة الاستقدام.
- حفظ request/response logs.
- إرجاع response موحد للنظام الرئيسي.
- دعم retry عند الأخطاء المؤقتة.
- دعم idempotency لمنع تكرار إنشاء العقود.

## مسؤوليات النظام الرئيسي خارج السعودية

النظام الرئيسي يظل مسؤولًا عن:

- واجهات المستخدم الحالية.
- بيانات العملاء والعقود المحلية.
- صلاحيات المستخدمين.
- ربط العقود داخل النظام المحاسبي والإداري.
- إرسال طلبات الاستقدام إلى Gateway.
- تخزين رقم العقد القادم من منصة الاستقدام.
- عرض حالة الربط للمستخدم.

ولا يجب أن يحتوي النظام الرئيسي على مفاتيح Qiwa بعد تطبيق الحل.

## تصميم API بين النظام الرئيسي والـ Gateway

يفضل أن تكون endpoints في Gateway مشابهة للخدمات الحالية:

```text
GET    /api/istiqdam/health
GET    /api/istiqdam/packages
GET    /api/istiqdam/packages/{id}
POST   /api/istiqdam/contracts
GET    /api/istiqdam/contracts/{contractId}
DELETE /api/istiqdam/contracts/{contractId}
GET    /api/istiqdam/contracts/{contractId}/download?type=pdf
POST   /api/istiqdam/contracts/{contractId}/work-permit
GET    /api/istiqdam/diagnose
```

## تأمين الاتصال بين السيرفرين

الاتصال بين ClickERP والـ Gateway يجب أن يكون خاصًا ومؤمنًا.

الحد الأدنى المطلوب:

- HTTPS إجباري.
- API token خاص بالنظام الرئيسي.
- توقيع الطلبات باستخدام HMAC.
- Timestamp داخل كل طلب لمنع replay attacks.
- Allowlist لعنوان IP الخاص بالسيرفر الرئيسي إذا كان IP ثابتًا.
- Rate limiting.
- Logging لكل محاولة فاشلة.

مثال headers داخلية:

```text
X-ClickERP-Client: clickerp-production
X-ClickERP-Timestamp: 2026-06-15T12:00:00Z
X-ClickERP-Signature: hmac_sha256(...)
Idempotency-Key: uuid-per-operation
```

## آلية منع تكرار العقود

إنشاء العقد أخطر عملية في التكامل، لأن إعادة إرسال الطلب قد تنشئ عقدًا مكررًا.

لذلك يجب استخدام `Idempotency-Key`.

الآلية:

1. النظام الرئيسي ينشئ key ثابت لكل محاولة إنشاء عقد.
2. Gateway يبحث عن هذا key قبل إرسال الطلب إلى منصة الاستقدام.
3. إذا كان الطلب منفذًا سابقًا، يرجع نفس النتيجة القديمة.
4. إذا لم يكن منفذًا، يرسل الطلب إلى منصة الاستقدام ويحفظ النتيجة.

## قاعدة بيانات مقترحة للـ Gateway

يمكن أن تكون قاعدة بيانات صغيرة جدًا.

الجداول المقترحة:

### جدول `gateway_requests`

- `id`
- `idempotency_key`
- `operation`
- `method`
- `endpoint`
- `request_payload`
- `response_payload`
- `status_code`
- `status`
- `error_message`
- `main_system_reference`
- `istiqdam_contract_id`
- `created_at`
- `updated_at`

### جدول `gateway_tokens`

- `id`
- `environment`
- `access_token`
- `expires_at`
- `created_at`
- `updated_at`

يمكن استخدام cache بدل جدول token إذا كان السيرفر مستقرًا، لكن وجود جدول أو cache persistent أفضل في الإنتاج.

## حالات العمليات المقترحة

في النظام الرئيسي والـ Gateway، يفضل توحيد الحالات:

```text
pending
sent_to_gateway
processing
created_on_istiqdam
failed
cancelled
sync_required
downloaded
work_permit_assigned
```

## التعديلات المطلوبة في ClickERP

### 1. إعدادات جديدة

إضافة إعدادات للتمييز بين الاتصال المباشر والاتصال عبر Gateway:

```env
ISTIQDAM_CONNECTION_MODE=gateway
ISTIQDAM_GATEWAY_URL=https://sa-gateway.example.com
ISTIQDAM_GATEWAY_CLIENT_ID=clickerp-production
ISTIQDAM_GATEWAY_SECRET=secure-secret
ISTIQDAM_GATEWAY_TIMEOUT=30
```

مع الاحتفاظ بإعدادات Qiwa لاستخدامها فقط في Gateway.

### 2. تعديل `IstiqdasmService`

بدل أن يستدعي Qiwa مباشرة، يكون لديه وضعان:

- `direct`: الاتصال الحالي بمنصة Qiwa.
- `gateway`: الاتصال بالخدمة السعودية.

في الإنتاج المتوقع نستخدم `gateway`.

### 3. عدم تغيير الواجهات قدر الإمكان

الـ Controller الحالي يمكن أن يبقى كما هو تقريبًا:

```text
app/Http/Controllers/admin/IstiqdasmRealController.php
```

لأن التغيير الأساسي سيكون داخل طبقة الخدمة:

```text
app/Services/IstiqdasmService.php
```

## مراحل التنفيذ

### المرحلة الأولى: التحليل والتأكيد

- تأكيد شرط منصة الاستقدام: هل المطلوب IP سعودي فقط أم استضافة كاملة داخل السعودية.
- تأكيد هل يوجد IP Whitelist.
- تأكيد هل مسموح استخدام وسيط Gateway.
- تأكيد endpoints الصحيحة للإنتاج.
- مراجعة فرق النسخ بين v1 و v2 في خدمة إنشاء العقد.

ملاحظة مهمة:

الكود الحالي يستخدم:

```text
/takamol/staging/external/v1/createcontractapiwrapper
```

بينما ملف v1.2 يعرض:

```text
/takamol/staging/external/v2/createcontractapiwrapper
```

يجب تأكيد endpoint الصحيح قبل الإنتاج.

### المرحلة الثانية: بناء Gateway

- إنشاء مشروع صغير على السيرفر السعودي.
- إضافة إعدادات Qiwa.
- تنفيذ OAuth token.
- تنفيذ endpoints المطلوبة.
- إضافة logging.
- إضافة HMAC verification.
- إضافة idempotency.

### المرحلة الثالثة: تعديل ClickERP

- إضافة إعدادات Gateway.
- تعديل `IstiqdasmService`.
- إضافة client داخلي للـ Gateway.
- توحيد شكل errors.
- الحفاظ على نفس response format قدر الإمكان حتى لا تتأثر الواجهات.

### المرحلة الرابعة: الاختبار على Staging/UAT

الاختبارات المطلوبة:

- اختبار الاتصال من السيرفر السعودي إلى Qiwa.
- اختبار الحصول على token.
- اختبار جلب الباقات.
- اختبار جلب تفاصيل باقة.
- اختبار إنشاء عقد تجريبي.
- اختبار عدم تكرار العقد عند إعادة إرسال نفس الطلب.
- اختبار إلغاء عقد.
- اختبار تحميل PDF.
- اختبار إسناد تصريح العمل.
- اختبار انقطاع الاتصال بين النظام الرئيسي والـ Gateway.
- اختبار انتهاء token وتجديده.
- اختبار credentials خاطئة.

### المرحلة الخامسة: التشغيل التدريجي

- تشغيل Gateway على بيئة UAT.
- تشغيل ClickERP بوضع `gateway` في بيئة تجريبية.
- تجربة عدد محدود من العمليات.
- مراقبة logs والأخطاء.
- الانتقال للإنتاج بعد اعتماد النتائج.

## آلية المزامنة

يفضل عدم الاعتماد فقط على الطلبات الفورية.

نحتاج Job دوري في النظام الرئيسي أو Gateway لتنفيذ:

- تحديث حالات العقود.
- تحديث بيانات work permits.
- جلب تفاصيل العقود غير المكتملة.
- إعادة محاولة العمليات الفاشلة بسبب timeout أو 5xx.

مثال:

```text
كل 10 دقائق:
- جلب العقود التي حالتها sync_required أو processing.
- سؤال Gateway عن آخر حالة.
- تحديث جدول istiqdam_contracts.
```

## التعامل مع الأخطاء

يجب تقسيم الأخطاء إلى:

- أخطاء تحقق من البيانات: لا يعاد إرسالها تلقائيًا.
- أخطاء صلاحيات أو credentials: تحتاج تدخل تقني.
- أخطاء رصيد أو نقاط: تظهر للإدارة.
- أخطاء timeout أو 5xx: يمكن retry.
- أخطاء تكرار أو حالة عقد غير صالحة: تحتاج مراجعة الحالة.

أكواد مهمة من ملف الربط:

- `3118`: Insufficient balance points
- `3106`: Payments sum not equals to package price
- `3111`: Package does not belong to establishment
- `3112`: Package not active
- `3100`: Invalid contract status
- `3105`: Not valid IQAMA provided
- `3102`: Laborer has already been assigned to Work Permit(s)
- `3108`: Laborer does not belong to establishment

## المخاطر وطريقة تقليلها

### خطر تكرار إنشاء العقود

الحل:

- استخدام `Idempotency-Key`.
- حفظ نتيجة كل عملية إنشاء عقد.
- عدم إعادة إرسال create contract تلقائيًا بدون تحقق.

### خطر تسريب بيانات الاعتماد

الحل:

- مفاتيح Qiwa تكون فقط على السيرفر السعودي.
- منع ظهور الأسرار في logs.
- تدوير المفاتيح دوريًا.
- استخدام secrets manager أو env محمية.

### خطر توقف Gateway

الحل:

- Health endpoint.
- Monitoring.
- Queue للعمليات المهمة.
- تنبيه عند الفشل.
- Backup يومي للـ logs المهمة.

### خطر اختلاف البيانات بين النظامين

الحل:

- Job مزامنة.
- حفظ آخر response من Qiwa.
- عرض حالة `sync_required` بوضوح للإدارة.

### خطر رفض المنصة لفكرة الوسيط

الحل:

- تأكيد رسمي قبل الإنتاج.
- توثيق أن السيرفر السعودي مملوك/مدار لصالح نفس المنشأة.
- استخدام IP سعودي ثابت ومصرح.

## متطلبات السيرفر السعودي

الحد الأدنى:

- VPS داخل السعودية.
- IP ثابت.
- SSL certificate.
- PHP/Laravel أو Node.js حسب اختيار التنفيذ.
- Database صغيرة مثل MySQL/PostgreSQL.
- Redis أو cache مناسب للـ token والـ rate limit.
- Firewall يسمح فقط باللازم.
- Monitoring و log rotation.

## توصية التنفيذ

أفضل مسار:

1. بناء Gateway صغير بـ Laravel لأن النظام الحالي Laravel وفريق الصيانة سيكون أسهل عليه.
2. نسخ منطق `IstiqdasmService` الحالي إلى Gateway مع تصحيح endpoints حسب ملف v1.2.
3. تعديل خدمة ClickERP الحالية لتستدعي Gateway.
4. تشغيل تجريبي على UAT.
5. تفعيل الإنتاج تدريجيًا.

## مخرجات التنفيذ المتوقعة

- خدمة Gateway داخل السعودية.
- إعدادات جديدة في ClickERP.
- Logs واضحة لكل عملية.
- حماية بين النظامين.
- عدم وجود مفاتيح Qiwa على السيرفر الخارجي.
- استمرار الواجهات الحالية بدون تغيير كبير.
- تقليل تكلفة النقل الكامل للسيرفر.

## القرار النهائي

فصل الربط في خدمة سعودية مستقلة هو حل عملي وآمن وأقل تكلفة من نقل كامل النظام.

ينصح بعدم نقل قاعدة بيانات ClickERP كاملة إلى السعودية في هذه المرحلة، والاكتفاء بخدمة Gateway صغيرة مسؤولة فقط عن تكامل منصة الاستقدام.
