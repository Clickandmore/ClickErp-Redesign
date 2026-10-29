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
| `recruitment_contracts_report` | `RecruitmentContract.index` | تقرير فترة: حسب المرحلة والجنسية والمسوّق والفرع، اتجاه شهري، الواصلة والمكتملة في الفترة، والجارية الآن |
| `rental_contracts_report` | `RentContract.index` | تقرير فترة: حسب الحالة، القيمة والمدفوع والمتبقي، الجنسية والمسوّق والفرع، المغلقة حسب نوع الإغلاق، السارية والمنتهية خلال 30 يوماً |
| `sponsorship_transfers_report` | `SponsorshipTransfer.index` | تقرير فترة (تاريخ الإنشاء): حسب الحالة وحالة الدفع والمصدر، المبالغ، المسوّق والفرع، المنقولة والفاشلة في الفترة |
| `shelter_report` | `ShelterContract.index` | لقطة الآن: داخل الإيواء وخارجه، الجنسية، الرغبة في العمل، الجاهزة للاستلام الفوري |
| `orders_report` | `orders.index` | تقرير فترة: استقدام/إيجار، حسب الحالة، الجارية والمتعاقد عليها وغير المكتملة، نسبة التحويل لعقد، المصدر (موقع/تطبيق أو موظف) |
| `maids_report` | `maids.index` | لقطة السير الآن حسب الحالة والجنسية والمكتب والمهنة، والسير المضافة في الفترة |
| `customers_report` | `clients.index` | الإجمالي، الجدد في الفترة حسب مصدر التسجيل والنوع والمدينة، من لهم عقد في الفترة، وأكثر 10 عملاء عقوداً |

أدوات التقارير تستخدم [ReportsOnPeriods](../app/Mcp/Tools/Concerns/ReportsOnPeriods.php): فترة موحّدة
(`today`، `this_week` من السبت، `this_month` افتراضياً، `last_month`، `this_year`، `last_year`، `all`، أو
`from`/`to`)، وكل حساب في قاعدة البيانات. التجميع الذي يحتاج JOIN يُبنى على استعلام مستقل مقيّد بمعرّفات
الاستعلام الأصلي (`countByJoin`) لأن scopes الموديلات تكتب `status` بلا اسم الجدول. العملاء بلا
`BranchScoped`، فكل أداة تستعلم من `users` تمر بـ `ScopesCustomersToBranch` (نفس عزل شاشة العملاء).

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

الرقعة `2026_10_30_100000_create_mcp_admin_access_table` تضيف تلقائياً كل موظف لديه اتصال
فعّال إلى قائمة الموظفين بمستوى «قراءة»، حتى لا ينقطع أحد عند تطبيق الوضع الافتراضي الجديد.

ثم من **Super Admin → زر MCP** (`/dev-super-admin/mcp`):

1. **الإعدادات:** فعّل الخادم، وفعّل OAuth إن كنت ستربط claude.ai أو ChatGPT.
2. **الموظفون:** أضف من سيستخدم المساعد (الوضع الافتراضي «المحددون فقط»).
3. **نظرة عامة:** تأكد أن «جاهزية التشغيل» كلها خضراء.
4. للربط اليدوي (Codex): **الاتصالات** ← إصدار توكن. يظهر **مرة واحدة** — انسخه فوراً.

### تبويبات الشاشة

| التبويب | ماذا فيه |
|---|---|
| نظرة عامة | اتصالات فعّالة، استدعاءات اليوم، رسم آخر 14 يوماً، أكثر الأدوات والموظفين، قائمة جاهزية التشغيل |
| الموظفون | من يستخدم المساعد: مفعّل/موقوف، «قراءة» أو «قراءة وإضافة»، كل الأدوات أو أدوات محددة، قطع الاتصالات |
| الأدوات | تشغيل/إيقاف كل أداة على مستوى النظام، ومفتاح الكتابة العام |
| الاتصالات | إصدار توكن يدوي، كل الاتصالات مع الإلغاء، والتطبيقات المسجلة عبر OAuth مع «إلغاء التطبيق» |
| السجل | فلترة بالموظف والأداة والنتيجة والتاريخ، صفحات، وتصدير CSV |
| الإعدادات | التشغيل، وضع الوصول، الإخفاء، حد الطلبات، مدة التوكن، مدة الاحتفاظ بالسجل |
| طريقة الربط | رابط الخادم وخطوات claude.ai وChatGPT وClaude Code وCodex |

### طبقات التحكم

كل طلب أداة يمر بها بالترتيب ([McpAccess](../app/Mcp/McpAccess.php) ثم `McpTool::isAllowedFor`):

1. **الخادم مفعّل** — وإلا 404.
2. **الموظف مسموح** — وضع «المحددون فقط»: له سطر مفعّل في `mcp_admin_access`. وضع «الجميع»: ليس له
   سطر موقوف. غير المسموح: 403 على `/mcp`، وصفحة الموافقة في OAuth ترفض برسالة، والتجديد يتوقف.
3. **الأداة مفعّلة** في تبويب الأدوات، و**ضمن أدوات الموظف** إن حُددت له أدوات.
4. **أداة كتابة؟** تحتاج مفتاح الكتابة العام **و** مستوى «قراءة وإضافة» للموظف.
5. **صلاحية الشاشة** (`gateRoute`) — الحد الأعلى دائماً. شاشة Super Admin تضيّق ولا توسّع:
   اختيار أداة لموظف لا يملك صلاحية شاشتها لا يفتحها له.

الأداة المرفوضة في 3 أو 4 تُسجَّل `disabled`، وفي 5 تُسجَّل `denied`، والموظف غير المسموح `blocked`
(مرة في الدقيقة لكل توكن حتى لا تغرق إعادة المحاولات السجل).

**لماذا على الموظف لا على التوكن؟** روابط OAuth ينشئها الموظف بنفسه، وكل تجديد (كل ساعة تقريباً)
صف جديد في `mcp_access_tokens`. إعداد على التوكن كان سيحتاج نسخاً مع كل تجديد، ولا يمر عليك عند
ربط برنامج جديد. الصلاحية على الموظف تسري فوراً على كل برامجه الحالية والقادمة.

إصدار توكن يدوي من الشاشة يضيف الموظف للقائمة بمستوى «قراءة» إن لم يكن مضافاً.

| الإعداد | الافتراضي | ملاحظة |
|---|---|---|
| تفعيل الخادم | مغلق | عند الإغلاق `/mcp` يرجع 404 والتوكنات تبقى |
| وضع الوصول | المحددون فقط | `all` = أي موظف لديه توكن صالح (عدا الموقوفين في القائمة) |
| مفتاح الكتابة | مغلق | لا توجد أدوات كتابة في هذا الإصدار بعد |
| إخفاء الجواز والجوال | مفعّل | آخر 4 أرقام فقط، في الردود وفي السجل |
| حد الطلبات/دقيقة لكل توكن | 60 | يُطبَّق قبل التحقق من التوكن، فيحدّ التخمين أيضاً |
| مدة التوكن الافتراضية | 90 يوماً | 0 = بلا انتهاء — تجنّبه |
| الاحتفاظ بالسجل | 180 يوماً | 0 = بلا حذف |

الإعدادات تُحفظ في `dev_superadmin_settings` (المفتاح `mcp_server`) عبر
[McpSettings](../app/Support/McpSettings.php). **لا يُقرأ أي شيء من `.env`**: القيم في
[config/mcp.php](../config/mcp.php) ثابتة وهي الافتراضي قبل أول حفظ من الشاشة فقط (الخادم مغلق،
OAuth مغلق، المحددون فقط، الكتابة مغلقة). كل تبويب يحفظ مفاتيحه فقط (`persist` يدمج ولا يستبدل).
القيم مخزنة مؤقتاً 5 دقائق؛ الحفظ من الشاشة يمسحها فوراً.

> **ترقية سيرفر كان يفعّل MCP عبر `MCP_ENABLED=true` في `.env` دون حفظ من الشاشة:** بعد النشر يصبح
> الخادم مغلقاً. افتح الشاشة ← الإعدادات وفعّل الخادم وOAuth واحفظ مرة واحدة.

### الإلغاء والتنظيف

- **تغيير كلمة مرور الموظف** (من الملف الشخصي أو الاستعادة أو تعديل الموظف) يلغي كل اتصالاته
  (hook في `Admin::booted`). التحديث عبر `DB::table` مباشرة لا يمر بهذا الـ hook.
- **إلغاء تطبيق OAuth** يضع `revoked_at` على العميل ويقطع كل توكناته؛ لا يستطيع الربط مجدداً بنفس التسجيل.
- **`mcp:prune`** يعمل يومياً 03:45 ([Kernel](../app/Console/Kernel.php)) ويحذف:
  التوكنات الملغاة أو المنتهية منذ أكثر من 7 أيام، رموز التفويض المنتهية، العملاء الذين لم يكملوا ربطاً
  خلال أسبوع، وسجل الاستدعاءات الأقدم من مدة الاحتفاظ. للمعاينة: `php artisan mcp:prune --dry-run`.

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

`status`: `ok`، `denied` (بلا صلاحية شاشة)، `disabled` (الأداة موقوفة من Super Admin أو خارج أدوات
الموظف)، `blocked` (الموظف غير مفعّل)، `error`، أو `unknown` (أداة غير موجودة). المدخلات تُحفظ بعد
إخفاء الأرقام الحساسة (`McpAuditLog::sanitizeArguments`). الأسهل من SQL: تبويب **السجل** في الشاشة.

## إضافة أداة جديدة

1. أنشئ صنفاً في `app/Mcp/Tools/` يرث `McpTool`.
2. `gateRoute()` = اسم مسار موجود في `AdminRoutePermissionMatrix` يعرض نفس البيانات.
3. استخدم `$this->limit()` لكل قائمة، و`$this->mask()` لأي رقم هوية أو جواز أو جوال.
4. أضف الصنف إلى `tools` في [config/mcp.php](../config/mcp.php). تظهر تلقائياً في تبويب الأدوات،
   ومجموعتها تُستنتج من `gateRoute()` (أو تجاوز `category()`).
   أداة تضيف أو تعدّل بيانات: أرجع `true` من `isWriteTool()` فلا تظهر إلا مع مفتاح الكتابة ومستوى
   «قراءة وإضافة» للموظف.
5. أضف اختباراً في [tests/Feature/Mcp/](../tests/Feature/Mcp/) يثبت الصلاحية وعزل الفرع.

تنبيه: الأعمدة الرقمية (`arabia_order_number`، `arabia_worker_number`) لا تُقارن بنص بحث —
MySQL يحوّل النص إلى 0 فيطابق صفوفاً خاطئة. قارنها فقط إذا كان النص أرقاماً (`ctype_digit`).

## أدوات الإضافة (معاينة ثم تأكيد)

| الأداة | الصلاحية من | ماذا تفعل |
|---|---|---|
| `form_options` | `clients.store` أو `maids.create` | قوائم الاختيار (معرّف + اسم) لنموذج العميل أو السيرة، حتى لا يخمّن المساعد المعرّفات |
| `create_customer` | `clients.store` (`clients_create`) | إضافة عميل + حسابه في دليل الحسابات وعملاء المالية |
| `create_maid_cv` | `maids.create` (`maids_create`) | سيرة بنموذج «السيرة السريعة»، ثم توليد ملف السيرة إن كان القالب مفعّلاً |

**الشروط:** مفتاح الكتابة في تبويب الأدوات، ومستوى «قراءة وإضافة» للموظف، وصلاحية الشاشة. أدوات الكتابة
تعلن `destructiveHint` فيطلب Claude/ChatGPT موافقة المستخدم، **لكن الخادم لا يعتمد على ذلك**:

1. الاستدعاء بدون `confirmation_token` يتحقق بقواعد الشاشة كاملة ويرجع معاينة ورمزاً. لا يُكتب شيء.
2. الاستدعاء مع الرمز يُعيد التحقق على **المدخلات المحفوظة عند المعاينة** (المرسلة مع التأكيد تُتجاهل) ثم يحفظ.

الرمز ([PendingWrites](../app/Mcp/PendingWrites.php)) مربوط بالموظف والأداة، صالح 15 دقيقة، ويُستهلك
مرة واحدة؛ إعادة إرساله بعد النجاح ترجع النتيجة نفسها ولا تنشئ سجلاً مكرراً. حد الإضافة في الساعة لكل
موظف من تبويب الإعدادات (30 افتراضياً). الأساس المشترك في [McpWriteTool](../app/Mcp/Tools/McpWriteTool.php).

**مصدر واحد مع الشاشة:**
- العميل: [ClientRegistrationService](../app/Services/ingaz/ClientRegistrationService.php) — القواعد والرسائل
  ومنع التكرار وإنشاء الحساب المالي، يستخدمه `UserController::store` (وتعديل العميل يستخدم قواعده نفسها).
  العميل المضاف من المساعد `register_from = mcp` و`created_by` = الموظف. الأداة تطبّع الجوال (05… / 966…)
  إلى الصيغة التي تتحقق منها القواعد، كما تفعل الشاشة قبل الإرسال.
- السيرة: قواعد `StoreRapidCv` و`MaidAction::createRapidCv` و`MaidCvPdfGenerator::generateIfEnabled`،
  نفس ما يستخدمه `MaidController::store_rapid_cv`. `added_by` = الموظف، وسجل النشاط يسجّل الإضافة باسمه.
  الصورة الشخصية وملف السيرة يُرفعان من شاشة العاملة.

## خارج هذا الإصدار

- إنشاء العقود (مؤجل عمداً)، ملاحظة على عقد، مسودة طلب، إرسال واتساب.
- السيرة الكاملة (الخبرات، الراتب، العمر...) — الأداة الحالية تغطي حقول السيرة السريعة فقط.
- الترحيل والسفر، الموارد البشرية، CRM.

لن تُضاف أبداً: ترحيل القيود المالية، الحذف، تعديل الصلاحيات.
