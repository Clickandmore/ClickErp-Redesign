# ClickERP — إضافة Claude Code

تربط Claude Code بخادم MCP الخاص بنظام ClickERP لشركتك. القراءة فقط، وبصلاحيات الموظف
الذي يسجّل دخوله وفرعه. شرح الخادم كاملاً في `docs/MCP_INTEGRATION.md` داخل مستودع ClickERP.

## التثبيت

داخل Claude Code:

```
/plugin marketplace add Clickandmore/clickerp-claude-plugin
/plugin install clickerp@clickerp
```

عند التثبيت يُطلب **رابط خادم ClickERP**: انسخه من لوحة شركتك ← Super Admin ← MCP،
مثل `https://clickerp.com.sa/mcp`.

ثم `/mcp` ← `plugin:clickerp:clickerp` ← **Authenticate**: تفتح صفحة دخول ClickERP في المتصفح،
سجّل دخولك ووافق. (يجب أن يكون «الربط بتسجيل الدخول» مفعّلاً في شاشة MCP.)

لتغيير الرابط لاحقاً: `/plugin configure clickerp@clickerp`.

## الأوامر

| الأمر | ماذا يفعل |
|---|---|
| `/clickerp:contracts-summary [من] [إلى]` | عدد عقود الاستقدام في كل مرحلة وأحدث العقود |
| `/clickerp:find-maid <وصف>` | البحث عن عاملة: `/clickerp:find-maid إثيوبية متاحة` |
| `/clickerp:contract-details <رقم أو عميل>` | تفاصيل عقد: المرحلة، الفاتورة، الضمان، الملاحظات |

ويمكن السؤال بالعربية مباشرة بدون أوامر: «كم عقد في مرحلة مساند؟».

## التجربة محلياً (للمطورين)

```
/plugin marketplace add /path/to/ClickERP/integrations/claude-code-plugin
/plugin install clickerp@clickerp
```

وتحقق من الملفات بـ `claude plugin validate integrations/claude-code-plugin`.
