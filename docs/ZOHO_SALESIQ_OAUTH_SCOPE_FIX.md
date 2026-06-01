# مشكلة OAuth Scope في Zoho SalesIQ

## المشكلة الحالية

```
HTTP 400: Invalid OAuthScope
```

تحصل على هذا الخطأ عند محاولة جلب المحادثات أو الزوار من Zoho SalesIQ.

## السبب

صلاحيات OAuth الحالية **محدودة جداً** ولا تتضمن صلاحيات SalesIQ:

- ✅ لديك صلاحيات `ZohoCRM.modules.contacts.READ`
- ❌ لكن لا توجد صلاحيات SalesIQ (conversations, visitors)

## الحل

### الخطوة 1: تفعيل SalesIQ في Zoho Account

1. اذهب إلى: https://crm.zoho.sa/
2. Setup → Marketplace
3. ابحث عن **"SalesIQ"** وفعّله
4. انتظر 5 دقائق للتفعيل الكامل

### الخطوة 2: إنشاء OAuth Code جديد

1. اذهب إلى: https://api-console.zoho.sa/
2. اختر **CRM** الخاص بك
3. اضغط **Generate Code**
4. اختر الـ Scopes التالية:

#### **في حقل Scopes:**
```
ZohoCRM.modules.contacts.READ,ZohoCRM.modules.leads.READ,ZohoCRM.modules.activities.READ,salesiq.conversations.READ,salesiq.visitors.READ
```

أو اختر:
- ✅ `ZohoCRM.modules.contacts.READ`
- ✅ `ZohoCRM.modules.leads.READ`
- ✅ `ZohoCRM.modules.activities.READ`
- ✅ `salesiq.conversations.READ`
- ✅ `salesiq.visitors.READ`

5. اضغط **Create**
6. انسخ الرمز (له صلاحية 10 دقائق فقط)

### الخطوة 3: استبدال الرمز

شغّل هذا الأمر فوراً:

```bash
php artisan zoho:exchange-code "PASTE_CODE_HERE"
```

ستحصل على:
- ✓ Access Token (ساعة واحدة)
- ✓ Refresh Token (دائم)

### الخطوة 4: تحديث `.env`

الأمر السابق سيعطيك الـ Refresh Token الجديد. 
قم بتحديثه في ملف `.env`:

```
ZOHO_SALESIQ_REFRESH_TOKEN=1000.xxxxxxxxxxxxxxxxxxxxxxxxxxxxx.xxxxxxxxxxxxxxxxxxxxx
```

ثم مسح الـ cache:

```bash
php artisan config:clear
```

## اختبار الاتصال

```bash
php artisan zoho:test
```

## المشاكل الشائعة

### 1. "Invalid OAuthScope" يستمر الظهور
- تأكد من تفعيل SalesIQ في Zoho CRM
- انتظر 10 دقائق كاملة بعد التفعيل
- جرب مرة أخرى

### 2. "Invalid URL"
- يحدث عندما لا تكون الـ API endpoint صحيحة
- غالباً يعني أن SalesIQ غير مفعّل في الـ Account

### 3. "invalid_code"
- الرمز انتهت صلاحيته (10 دقائق)
- أنشئ رمز جديد وشغّل الأمر فوراً

## دعم إضافي

إذا استمرت المشاكل، تحقق من:

1. **التحقق من Zoho Account:**
   - هل لديك خطة SalesIQ مفعلة؟
   - هل المستخدم له صلاحيات Admin في CRM؟

2. **التحقق من OAuth Client:**
   - هل Client ID و Client Secret صحيحة؟
   - هل OAuth client مفعل؟

3. **اتصل بـ Zoho Support:**
   - إذا كانت جميع الخطوات صحيحة ولا يزال هناك خطأ
