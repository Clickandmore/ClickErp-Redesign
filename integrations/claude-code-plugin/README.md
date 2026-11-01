# ClickERP — إضافة Claude Code

تربط Claude Code بخادم MCP الخاص بنظام ClickERP لشركتك، بصلاحيات الموظف الذي يسجّل دخوله وفرعه.
الإضافة والتعديل لا يتمان إلا لمن فعّلهما فريق ClickERP وبموافقته. شرح الخادم كاملاً في `docs/MCP_INTEGRATION.md` داخل مستودع ClickERP.

## التثبيت

داخل Claude Code:

```
/plugin marketplace add Clickandmore/clickerp-claude-plugin
/plugin install clickerp@clickerp
```

عند التثبيت يُطلب **رابط خادم ClickERP**: تجده في لوحة التحكم ← «المساعد الذكي» بعد أن يفعّل فريق ClickERP حسابك،
مثل `https://clickerp.com.sa/mcp`.

ثم `/mcp` ← `plugin:clickerp:clickerp` ← **Authenticate**: تفتح صفحة دخول ClickERP في المتصفح،
سجّل دخولك ووافق. (إن لم تفتح الصفحة فتواصل مع فريق ClickERP.)

لتغيير الرابط لاحقاً: `/plugin configure clickerp@clickerp`.

## الأوامر

| الأمر | ماذا يفعل |
|---|---|
| `/clickerp:contracts-summary [من] [إلى]` | عدد عقود الاستقدام في كل مرحلة وأحدث العقود |
| `/clickerp:find-maid <وصف>` | البحث عن عاملة: `/clickerp:find-maid إثيوبية متاحة` |
| `/clickerp:contract-details <رقم أو عميل>` | تفاصيل عقد: المرحلة، الفاتورة، الضمان، الملاحظات |
| `/clickerp:finance-summary [من] [إلى] [حساب]` | الفواتير والتحصيل وسندات القبض والصرف ورصيد حساب |
| `/clickerp:activity-report [فترة/موظف]` | من أضاف أو عدّل أو حذف ماذا، والعمليات اللافتة |

ويمكن السؤال بالعربية مباشرة بدون أوامر: «كم عقد في مرحلة مساند؟».

## التجربة محلياً (للمطورين)

```
/plugin marketplace add /path/to/ClickERP/integrations/claude-code-plugin
/plugin install clickerp@clickerp
```

وتحقق من الملفات بـ `claude plugin validate integrations/claude-code-plugin`.
