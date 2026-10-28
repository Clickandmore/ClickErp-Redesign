# خادم MCP الخاص بـ ClickERP

يتيح لمساعدات الذكاء الاصطناعي (Codex، Claude، وغيرها مما يدعم MCP) قراءة بيانات النظام
**بصلاحيات موظف حقيقي**: المساعد يرى بالضبط ما يراه الموظف صاحب التوكن في لوحة التحكم،
داخل فرعه فقط، ولا يستطيع تعديل أي شيء.

## كيف يعمل

```
Codex / Claude  ──POST /mcp + Bearer token──►  AuthenticateMcpToken
                                                 │ يحوّل التوكن إلى موظف تحت حارس admin
                                                 ▼
                                              McpServer (JSON-RPC)
                                                 │ كل أداة تستعير صلاحية شاشة موجودة
                                                 ▼
                                              Models (BranchScope يعزل الفروع تلقائياً)
                                                 │
                                                 ▼
                                              mcp_audit_logs (سجل كل استدعاء)
```

| الملف | الدور |
|---|---|
| [config/mcp.php](../config/mcp.php) | قائمة الأدوات، والقيم الافتراضية للإعدادات |
| [routes/mcp.php](../routes/mcp.php) | المسار `POST /mcp` خارج مجموعة `web` (بلا جلسة ولا CSRF) |
| [AuthenticateMcpToken](../app/Http/Middleware/AuthenticateMcpToken.php) | التحقق من التوكن، رفض الموظف الموقوف، احترام وضع الصيانة |
| [McpServer](../app/Mcp/McpServer.php) | `initialize` و`tools/list` و`tools/call` و`ping` والسجل |
| [app/Mcp/Tools/](../app/Mcp/Tools/) | أداة لكل ملف |
| [SuperAdminMcpController](../app/Http/Controllers/admin/SuperAdminMcpController.php) | شاشة Super Admin: الإعدادات والتوكنات والسجل |
| [McpTokenCommand](../app/Console/Commands/McpTokenCommand.php) | إصدار التوكنات وعرضها وإلغاؤها من الطرفية |

النقل المستخدم هو Streamable HTTP **بلا جلسات وبلا SSE**: كل طلب مستقل، و`GET /mcp` يرجع 405.

## الصلاحيات — لا توجد صلاحيات MCP منفصلة

كل أداة تعلن عن **مسار إداري** تستعير حمايته (`gateRoute()`):

| الأداة | المسار المستعار | ماذا تعرض |
|---|---|---|
| `whoami` | — (لأي توكن صالح) | الموظف وفرعه وأدواره |
| `search_maids` | `maids.index` | البحث عن العاملات |
| `list_recruitment_contracts` | `RecruitmentContract.index` | قائمة العقود + العدد حسب الحالة |
| `get_recruitment_contract` | `RecruitmentContract.show` | تفاصيل عقد مع فاتورته وآخر ملاحظاته |
| `list_rental_contracts` | `RentContract.index` | عقود الإيجار + العدد حسب الحالة + المنتهية قريباً |
| `search_customers` | `clients.index` | العملاء (عزل الفرع يدوي كما في UserController لأن User بلا BranchScoped) |
| `list_complaints` | `complaints.index` | الشكاوى + العدد حسب الحالة والأولوية |
| `invoices_summary` | `invoices.index` | إجماليات الفواتير والضريبة والمدفوع وغير المدفوع |
| `finance_account_balance` | `finance.accounts.index` (حزمة finance) | رصيد حساب مع حساباته الفرعية في فترة |
| `finance_bonds_summary` | `finance.bonds-qabd.index` / `finance.bonds-sarf.index` (حزمة finance) | سندات القبض والصرف — كل نوع بصلاحيته |
| `activity_log` | `dash.activity.log` | سجل الأنشطة: من فعل ماذا، مع ملخصات. الحقول السرية (كلمات المرور، التوكنات) تُرسل `[مخفي]` |

أدوات حزمة finance تعلن `gatePackage() = 'finance'` فتُقرأ صلاحيتها من `EnsurePackageRoutePermission`.
مسار غير موجود في الـ router يُرفض دائماً.

- الصلاحية تُقرأ من `AdminRoutePermissionMatrix::resolve()` نفسها، فأي تعديل على صلاحية الشاشة
  ينعكس على الأداة تلقائياً.
- لو أُغلق موديول الشاشة من Super Admin تختفي الأداة.
- الأداة غير المسموحة لا تظهر في `tools/list` أصلاً، واستدعاؤها يرجع `isError` ويُسجَّل `denied`.
- مسار بلا إدخال في المصفوفة **يُرفض** (لا يُفتح) — خطأ في تعريف الأداة لا يتحول إلى ثغرة.

عزل الفروع يأتي من `BranchScope` على الموديلات، لأن الوسيط يسجّل الموظف تحت حارس `admin`.
لذلك الأداة الجديدة يجب أن تستعلم من موديل يستخدم `BranchScoped`، أو تطبّق
`BranchAccessService` يدوياً.

## التشغيل على السيرفر

```bash
php artisan migrate --force   # ينشئ جداول MCP وOAuth مع باقي الرقع المعلقة
```

ثم من **Super Admin → زر MCP** (`/dev-super-admin/mcp`):

1. فعّل الخادم واحفظ.
2. أصدر توكناً: اختر الموظف واكتب اسماً يميّز الجهاز. التوكن يظهر **مرة واحدة** — انسخه فوراً.
3. انسخ أمر الربط الجاهز من نفس الشاشة.

الشاشة تعرض أيضاً كل التوكنات (مع زر إلغاء) وآخر 50 استدعاء من سجل التدقيق.

| الإعداد | الافتراضي | ملاحظة |
|---|---|---|
| تفعيل الخادم | مغلق | عند الإغلاق `/mcp` يرجع 404 والتوكنات تبقى |
| إخفاء الجواز والجوال | مفعّل | آخر 4 أرقام فقط |
| حد الطلبات/دقيقة لكل توكن | 60 | يُطبَّق قبل التحقق من التوكن، فيحدّ التخمين أيضاً |
| مدة التوكن الافتراضية | 90 يوماً | 0 = بلا انتهاء — تجنّبه |

الإعدادات تُحفظ في `dev_superadmin_settings` (المفتاح `mcp_server`) عبر
[McpSettings](../app/Support/McpSettings.php)، وتتقدم على `.env`. متغيرات `.env`
(`MCP_ENABLED`، `MCP_RATE_LIMIT`، `MCP_MASK_SENSITIVE`، `MCP_TOKEN_DAYS`) هي الافتراضي
قبل أول حفظ من الشاشة فقط. القيم مخزنة مؤقتاً 5 دقائق؛ الحفظ من الشاشة يمسحها فوراً.

السيرفر يجب أن يكون على **HTTPS** — التوكن يُرسل في كل طلب.

## إدارة التوكنات من الطرفية (بديل للشاشة)

```bash
# إصدار (يظهر مرة واحدة فقط، ويُخزَّن hash)
php artisan mcp:token issue --admin=ahmed@company.sa --name="Codex - لابتوب أحمد"
php artisan mcp:token issue --admin=12 --days=30
php artisan mcp:token issue --admin=12 --days=0      # بلا انتهاء — تجنّبه

php artisan mcp:token list
php artisan mcp:token list --admin=12
php artisan mcp:token revoke --id=5
```

- أصدر التوكن لموظف **بالصلاحيات التي يحتاجها فعلاً**، لا لحساب المدير العام.
  موظف فرع → المساعد يرى فرعه فقط.
- إيقاف الموظف (`status = suspended`) يوقف كل توكناته فوراً.

## الربط من العملاء

```bash
# Codex (تأكد من الخيار بـ codex mcp add --help، أو استخدم config.toml بالأسفل)
export CLICKERP_TOKEN=cerp_mcp_...
codex mcp add clickerp --url https://erp.example.com/mcp --bearer-token-env-var CLICKERP_TOKEN

# Claude Code
claude mcp add --transport http clickerp https://erp.example.com/mcp \
  --header "Authorization: Bearer $CLICKERP_TOKEN"
```

أو مباشرة في `~/.codex/config.toml`:

```toml
[mcp_servers.clickerp]
url = "https://erp.example.com/mcp"
bearer_token_env_var = "CLICKERP_TOKEN"
```

اختبار سريع بدون أي عميل:

```bash
curl -s https://erp.example.com/mcp -H "Authorization: Bearer $CLICKERP_TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"whoami"}}'
```

claude.ai وChatGPT يربطان عبر OAuth بدون توكن — انظر القسم التالي.

## الربط بتسجيل الدخول (OAuth) — claude.ai وChatGPT وClaude Code

بدل لصق توكن، يضيف الموظف رابط `https://<الدومين>/mcp` في البرنامج، فتفتح له صفحة دخول
ClickERP، يسجّل دخوله بحسابه ويضغط «موافقة وربط». يُفعَّل من Super Admin ← MCP ←
«السماح بالربط عبر تسجيل الدخول» (يتطلب أن يكون الخادم نفسه مفعّلاً).

| البرنامج | أين يُضاف |
|---|---|
| claude.ai والتطبيق | Settings ← Connectors ← Add custom connector (في Team/Enterprise: مالك المنظمة) |
| ChatGPT | Settings ← Apps & Connectors ← Advanced ← Developer mode، ثم Create والمصادقة OAuth |
| Claude Code | الإضافة (القسم التالي) أو `claude mcp add --transport http clickerp <url>` ثم `/mcp` ← Authenticate |

**المسارات** ([McpOAuthController](../app/Http/Controllers/McpOAuthController.php)، [routes/mcp.php](../routes/mcp.php)):

| المسار | الغرض |
|---|---|
| `GET /.well-known/oauth-protected-resource` | RFC 9728 — يدلّ على خادم التفويض. الرد 401 من `/mcp` يشير إليه في `WWW-Authenticate` |
| `GET /.well-known/oauth-authorization-server` | RFC 8414 — روابط التفويض والتوكن والتسجيل |
| `POST /oauth/register` | RFC 7591 — تسجيل عميل ديناميكي (claude.ai وChatGPT يسجّلان أنفسهما) |
| `GET/POST /oauth/authorize` | صفحة موافقة الموظف (جلسة لوحة التحكم). بلا دخول ← صفحة الدخول ثم يرجع |
| `POST /oauth/token` | استبدال الرمز (PKCE S256 إلزامي) وتجديد التوكن |

**ضوابط الأمان:**
- روابط الرجوع المسموحة: `https` على `claude.ai`، `claude.com`، `chatgpt.com`، `chat.openai.com`
  (`mcp.oauth.redirect_hosts` في [config/mcp.php](../config/mcp.php))، و`localhost`/`127.0.0.1` لعملاء سطح المكتب.
  أي رابط آخر يُرفض عند التسجيل، ورابط غير مطابق عند التفويض يعرض صفحة خطأ ولا يُعاد التوجيه إليه.
- رمز التفويض: 10 دقائق، استخدام واحد، مربوط بالعميل ورابط الرجوع وPKCE.
- access token ساعة واحدة، وrefresh token بمدة «مدة التوكن» في الإعدادات (365 يوماً إذا كانت 0)،
  ويُدوَّر مع كل تجديد. إيقاف الموظف يوقف التوكن والتجديد فوراً.
- كل ربط يظهر في جدول التوكنات بمصدر «ربط بتسجيل الدخول» ويُلغى من هناك؛ الموافقة والرفض في سجل التدقيق.
- الروابط في الاكتشاف تُبنى على `https` إذا كان `APP_URL` على https، حتى خلف Cloudflare.
  **تأكد أن `APP_URL` في `.env` هو الدومين الصحيح بـ https** — claude.ai وChatGPT يرفضان الروابط غير المطابقة.

## إضافة Claude Code (Plugin)

في [integrations/claude-code-plugin/](../integrations/claude-code-plugin/): marketplace فيه إضافة `clickerp`
تحمل إعداد الخادم وثلاثة أوامر (`/clickerp:contracts-summary`، `/clickerp:find-maid`،
`/clickerp:contract-details`). رابط الخادم إعداد للمستخدم (`userConfig.server_url`) يُطلب عند التثبيت،
فالإضافة نفسها تعمل لكل العملاء. الشرح للمستخدم في [README الإضافة](../integrations/claude-code-plugin/README.md).

```bash
claude plugin validate integrations/claude-code-plugin          # الـ marketplace
claude plugin validate integrations/claude-code-plugin/plugins/clickerp
```

للتوزيع على الموظفين: انسخ المجلد إلى مستودع GitHub مستقل (مثل `Clickandmore/clickerp-claude-plugin`)
لأن مستودع ClickERP خاص؛ الموظف يضيفه بـ `/plugin marketplace add Clickandmore/clickerp-claude-plugin`.

## مراجعة الاستخدام

```sql
SELECT a.name, l.tool, l.status, l.arguments, l.duration_ms, l.created_at
FROM mcp_audit_logs l LEFT JOIN admins a ON a.id = l.admin_id
ORDER BY l.id DESC LIMIT 50;
```

`status`: `ok` أو `denied` (بلا صلاحية) أو `error` أو `unknown` (أداة غير موجودة).

## إضافة أداة جديدة

1. أنشئ صنفاً في `app/Mcp/Tools/` يرث `McpTool`.
2. `gateRoute()` = اسم مسار موجود في `AdminRoutePermissionMatrix` يعرض نفس البيانات.
3. استخدم `$this->limit()` لكل قائمة، و`$this->mask()` لأي رقم هوية أو جواز أو جوال.
4. أضف الصنف إلى `tools` في [config/mcp.php](../config/mcp.php).
5. أضف اختباراً في [tests/Feature/Mcp/](../tests/Feature/Mcp/) يثبت الصلاحية وعزل الفرع.

تنبيه: الأعمدة الرقمية (`arabia_order_number`، `arabia_worker_number`) لا تُقارن بنص بحث —
MySQL يحوّل النص إلى 0 فيطابق صفوفاً خاطئة. قارنها فقط إذا كان النص أرقاماً (`ctype_digit`).

## خارج هذا الإصدار

- أدوات الكتابة (ملاحظة على عقد، مسودة طلب) — تحتاج تأكيداً من الموظف قبل التنفيذ.
- الإيواء والترحيل والسفر، الموارد البشرية، CRM.

لن تُضاف أبداً: ترحيل القيود المالية، الحذف، تعديل الصلاحيات.
