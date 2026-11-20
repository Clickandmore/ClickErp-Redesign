# خريطة الشاشات ← كود ClickERP

المرجع لإعادة تصميم شاشات «مرجع» و«قريباً» بثيمنا. الوظيفة والحقول والحالات من الكود، والشكل من الثيم المعتمد بس.

- المشروع: `/Applications/MAMP/htdocs/ClickERP` (Laravel 8).
- اختصارات: `FV` = `packages/finance/resources/views/finance` · `HV` = `packages/hr/resources/views/hr` · `ING` = `resources/views/admin/ingaz`.
- الحالة: ✅ اتصممت · ⏳ لسه.

---

## العملاء وخدمة العملاء — ✅ v1.4

| شاشتنا | الكود |
|---|---|
| `claims` · `claimAdd` · `claimShow` · `claimTypes` | `ING/complaints/*` · `ComplaintController` · `routes/ingaz.php:503-520` · أنواع: `ING/complaint_types/*` |
| `absconding` · `abscShow` | `ING/RecruitmentContract/escape-report(s).blade.php` · `RecruitmentEscapeReportController` · تأجير: `ING/RentContract/_escape-report` + `RentalEscapeService` |
| `chats` | `v2/crm/conversations/index` (v2) · `ING/whatsapp/inbox` · `ING/website_visitors/index` |
| `campaigns` · `campaignNew` | `v2/crm/campaigns/index` · `OutboundMessageLog::STATUSES` · `NotificationController@indexWhatsApp` |

**الشكاوى**
- **الحالات:** قيد الانتظار، قيد المعالجة، تم الحل، مرفوضة.
- **الأولوية:** منخفضة، متوسطة، عالية، عاجلة (بشارة نابضة).
- **القنوات:** لوحة التحكم، الموقع، التطبيق، استيراد، بوابة المكتب الخارجي.
- **الأطراف:** عميل، عاملة، المكتب، مكتب خارجي، موظف، أخرى.
- **الفورم 5 خطوات:** التصنيف ← مقدم الشكوى ← المشتكى عليه (عاملة مسجلة/غير مسجلة) ← الربط (عقد/طلب/مكتب/بدون) ← التفاصيل والمرفقات.

**الهروب**
- الاستقدام: بلاغ واحد لكل عقد في مرحلة الوصول أو الاكتمال. المسترد = التكلفة ÷ مدة العقد × المدة المتبقية.
- التأجير: مفتوح ← عادت (بانتظار مراجعة الجاهزية) ← اعتُمدت الجاهزية، أو «مغلق دون عودة» بمستند مؤيد.
- **مش في الكود:** متابعة الجوازات. الموجود بس حقل «رقم البلاغ الرسمي».

**الرسائل**
- **حالات الإرسال:** قيد الإرسال، قبله المزوّد، أرسله المزوّد، تم التسليم، تمت القراءة، فشل، لم يُرسل.
- إعادة الإرسال مرة واحدة للرسائل الفاشلة.
- الرد بعد قفل نافذة الـ 24 ساعة يكون بقالب Meta معتمد بس.

---

## العمليات — ✅ v1.8 (`visa*` · `hbCollect`)

**تأشيرات الشركة** (`admin/visa_requests/*` + `v2/visas/*` · `VisaRequestController` · `docs/VISA_REQUESTS.md`)
- **الحالات:** تحت الإجراء، موافق عليه، مسدد، مكتمل، مرفوض، ملغي.
- **اللوحة:**
  - مؤشرات: بانتظار الموافقة، بانتظار السداد، بانتظار رقم التأشيرة، مستحقات وكلاء مفتوحة.
  - جداول: المتاح حسب المهنة والجنسية، قريبة الانتهاء، عقود متعثرة، مستحقات الوكلاء.
- **الطلبات:**
  - أعمدة: رمز الطلب، التاريخ، المهنة، الجنسية، الجنس، العدد، الرصيد، رقم التأشيرة، الحالة، الفرع.
  - الفورم: المهنة، الجنسية، الجنس، العدد، التاريخ، النوع، رقم المعاملة، ملاحظات.
- **تفاصيل الطلب:**
  - مسار: الموافقة ← السداد (يُنشئ قيد دفعة مقدمة) ← الإكمال (رقم التأشيرة والتواريخ).
  - التبويبات: التفاصيل، السداد والقيود، السجل.
  - العقود المربوطة والمرفقات.
- **الرصيد:** مؤشرات + جدول الاستهلاك + مطابقة حساب الدفعة المقدمة 1597.
- **التقارير (8):** المدفوعات، الاستهلاك، الملغاة، قريبة الانتهاء، دورة الاستقدام، مستحقات الوكلاء، تكلفة العاملة، تقييم الوكلاء.
- **الحجز المسبق للتأجير:** عقد الشركة + عميل الإيجار. الحالات: حجز مسبق، تحوّل لعقد، ملغي.
- **الإعدادات:** 3 حسابات + مُدد التنبيهات (30، 7، 14، 2، 7، 14، 30) + أنواع التأشيرات.

**التحصيل والتعويضات (الساعات)**
- **التعويضات** (`admin/hourly/compensations`):
  - الأسباب: تأخير، تعذّر حضور، مستوى الخدمة، سلوك الطاقم، ضرر، أخرى.
  - الوجهات: محفظة، نقد، تحويل.
  - الحالات: أُضيف للمحفظة، بانتظار الصرف، صُرف.
- **العهد النقدية** (`cash-custody`):
  - المسار: تحصيل نقدي ← عهدة على المحصّل ← توريد بسند.
  - الأعمدة: المحصّل، المحصّل/المورّد/المتبقي.

---

## المالية — ✅ v1.5 (`finDash` · `finRequests*` · `finAssets*` · `finCommission*` · `finTax` · `finZatca`)

| شاشتنا | الكود |
|---|---|
| `finDash` | `FV/home.blade.php` · `HomeService` · `FV/operational_summary/index.blade.php` · `OperationalSummaryService` (نظام الاستقدام) |
| `finDashGen` | `FV/home.blade.php` من غير قسم `operations` · `HomeService` (daily) · `PerformanceDashboardController` (المتوقع مقابل الفعلي) — نظام عام |
| `finRequests` | `ING/financial_requests/*` · `FinancialRequestController` · `lang/ar/financial_requests.php` |
| `finAssets` | `FV/principles/*` + مواقع، نقل، إهلاك يدوي ومجموعات، بيع |
| `finCommissions` | `resources/views/commissions/*` · `routes/ingaz.php:1097-1121` |
| الضريبة و ZATCA | `FV/tax_setting` · `tax-declaration` · `tax-invoice-statement` · `FV/zatca/setting` |

**لوحة المالية**
- 4 مؤشرات: صافي السيولة، ذمم العملاء، التزامات الموردين، نتيجة الشهر.
- رسم 12 شهر (إيرادات/مصروفات) + حركة 14 يوم.
- الملخص التشغيلي، أرصدة البنوك، أكبر العملاء والموردين، التحصيل والمدفوعات، أحدث القيود.

**الطلبات المالية**
- **التبويبات:** غير المقفلة، الكل، غير مكتملة، مدفوعة/قيد الإقفال، المحذوفة.
- **الأنواع:** مساند، تأشيرة، إنجاز، إقامة، رخصة عمل، تأمين، راتب عاملة، استرداد عميل، أخرى. كل نوع له حقوله.
- **الحالات:** تجهيز، بانتظار الاعتماد، معتمد/بانتظار الدفع، تم السداد، طلب إقفال، تم إرفاق الإيصال، غير مكتمل، مقفلة.
- **التصعيد:** عادي، هام، شديد الأهمية.
- **المسار:** اعتماد ← سداد بإثبات تحويل ← طلب إقفال ← إقفال بإيصال وسند تلقائي.
- **ملاحظة:** العهد والسلف مش أنواع طلبات مالية، دي في الموارد البشرية.

**الأصول**
- الفورم: نوع التسجيل، الحساب، القيمة، العمر بالشهور، نوع الهالك (قسط ثابت/متناقص/بدون)، الخردة، حسابات المخصص والمصروف.
- التفاصيل: معلومات، الحركات، الإهلاك + تكهين وبيع.

**العمولات**
- **المراجعة:** كروت المسوقين، وتفاصيل كل مسوق فيها جدول العمليات (محتسبة/غير محتسبة).
- **الاعتماد 4 خطوات:** اختيار ← تحديد العمولة (مبلغ/نسبة) ← إضافة للرصيد ← سند صرف.
- **القواعد:**
  - خصم التأجير المرتجع.
  - الافتراضي لكل خدمة (تفويض، استقدام، نقل خدمة، تأجير).
  - حسابات الاستحقاق.
  - استثناءات المسوقين.
- **الصرف:** مركز الصرف + سند لكل مسوق (حساب الصندوق أو البنك، طريقة الدفع، مركز التكلفة).

**ZATCA**
- حالات الفاتورة: غير مطلوب، مرحل، معتمد، مرفوض، معلق.
- الإعدادات: بيانات المنشأة + البيئة (مطوّر/محاكاة/إنتاج) + شهادة التوقيع.

---


### v1.47 — المالية العامة (منيو `FV/../finance.blade.php`)
| الشاشة | المصدر |
|---|---|
| `finSettings` | `FV/confirm_settings` · `chart_settings` · `tax_setting` · `years` · `opening_balance` · `fiscal_year_closing` |
| `finStatements` | `FinancialPositionController` · `IncomeListController` · `ProfitLossController` · `TrailBalanceController` · `ClientTrialBalanceController` · `AuditBudgetController` · `AuditBudgetMonthlyController` |
| `finBalances` | `FV/balances` · `ObjService::balancesMovementReport` (Account/Client/Supplier/TreasuryAndBank BalancesController) |
| `finDaily` · `finDailyLines` | `CashMovementController` (view=reference · movement) · `DailyCashReferenceLineController` |
| `finTaxReports` | `SalesAnalysisController` · `PurchasesAnalysisController` · `TaxInvoiceStatementController` · `TaxDeclarationController` |
| `finEntriesPending` · `finBondsGen` · `finComPay` · `finGuide` · `finClientCats` · `finSupplierTypes` · `finPurchaseReq` | `constraints.pending` · `general_bond` · `commissions.finance` · `accounting_management` · `client_categories` · `supplier_type` · `procurement?kind=request` |

## الموارد البشرية — ✅ v1.6 (`hr*`)

المسارات `packages/hr/routes/hr.php` (`/hr`) و`employee.php` (`/employee`). ترتيب القائمة في الكود: الرئيسية، الاعدادات، الموظفين، الاجازات، السلف، العهد، الجزاءات، الاذونات، الحضور، رفع شيت الحضور، التقارير اليومية، حضور جماعي، المرتبات، مسير العاملات، المكافآت.

| شاشتنا | الكود | الأساس |
|---|---|---|
| لوحة الموارد البشرية | `HV/home.blade.php` (**مالهاش route حالياً**) | كروت: الأقسام، الموظفين، الأذونات، الجزاءات، التأخيرات، الغياب + رسوم للرواتب والأقسام والحضور |
| الموظفون | `HV/employees/*` | الفلاتر: القسم، الوظيفة، الفرع، الحالة، الجنسية، التعيين، العقد · الفورم 4 خطوات: أساسية، وظيفة، ملفات، راتب وبدلات وضرائب وتأمينات |
| العاملات | `hr.maids.index` / `employed-maids` | نفس جدول الموظفين + «توظيف العاملة» |
| الحضور | `HV/employeeAttendances/*` | 7 أنواع: حضور وانصراف، حضور، غياب، عطلة، تأخير، إجازة، إجازة رسمية · إدخال جماعي + رفع Excel |
| الطلبات | إجازات، أذونات، سلف، مكافآت | **مفيش شاشة موحّدة في الكود** — نصممها موحّدة بتبويبات |
| العهد | `HV/employeeCustodies` | الحالات: نشطة، مسترجعة، تالفة، مفقودة · مواقع العهد |
| الجزاءات | `sanctions` + `employeeSanctions` | طريقة الحساب: يوم، مبلغ ثابت، نسبة من الراتب · الخصم مع الراتب |
| الرواتب | `salaries` + `payrolls` (مسير العاملات) | المسير فيه جدول قابل للتعديل: أيام، راتب، حافز، خصومات، صافي |
| التقارير اليومية | `daily-work-reports` | الموظف، العنوان، التاريخ، الوصف، المرفقات · بدون اعتماد |
| **v1.50 — المنشآت** | المنشآت والكفالة · نوع الارتباط في الموظفين والمسير وحماية الأجور والتأمينات ومقيم ونطاقات | **«جديد»** — الكود فيه `employee_category` (موظف/عاملة) و`currentlyCompanySponsoredMaids` بس، ومفيش منشآت ولا نوع ارتباط للموظفين |
| **v1.49 — طلبات الموظفين** | طلباتي · طلب جديد · تفاصيل طلب · فريقي · أنواع الطلبات والاعتماد | **«جديد»** — الكود فيه شاشة لكل نوع للـ HR بس (`employeeVacations` · `employeePermissions` · `employeeAdvances` · `employee_rewards`) ومفيش مكان يقدّم منه الموظف ولا سلاسل اعتماد |
| **v1.48 — UPD-0931** | الوثائق والإقامات · تجديد الإقامة · مقيم · الخروج والعودة · العقود وقوى · العمل عن بعد · دفتر الإجازات · رفع البصمة · رحلة العاملة · حماية الأجور · التأمينات · القيود · المخالصة · التقييم · الامتثال · الإعدادات | **«جديد» — مش في الكود.** المرجع: كارت UPD-0931 وتقرير البحث. الإعدادات بتجمع `insurances` · `vacations` · `vacation-years` · `officialVacations` · `shifts` · `sanctions` · `department` · `jobs` · `allowances`، و`tax` اتشال |

---

## الإعدادات — ✅ v1.7 (`set*`)

| شاشتنا | الكود |
|---|---|
| بيانات الشركة واللغة (`setGeneral`) | `company-settings` · بيانات الشركة من `settings.section/general` · `translation-terms` |
| لوحة التحكم والأمان (`setPanel`) v1.65 | `settings.section/dashboard` (admin_logo · admin_login_background · dashboard_footer_url · import_tutorial_video_*) · `authentication.admin_login_otp` |
| إعدادات الموقع (`setSite`) | `settings.section/{general (الظهور والصيانة),social,branding (صور الموقع),content,policies,authentication (app/external_auth_mode),ordering,seo}` · `settings/index` (bank_transfer_data · sadad_transfer_data) · الإعلانات · كاش المواقع |
| الاستقدام والسير (`setRecruit` — في العمليات من v1.65) | `settings/index` (warranty_* · order_* deadlines) · `cv-options-settings` (حقول النموذج: كالمعتاد/اختياري/مخفي/إلزامي) · قالب السيرة · الجنسيات · مراحل العقود · مراحل التفويض |
| التأجير | `RentContract/settings` · بنود العقد · الباقات · `hourly_rental_settings` |
| المستخدمون والصلاحيات | `admin/auth/admin/*` · الأدوار · مصفوفة الصلاحيات (عرض/إضافة/تعديل/حذف لكل شاشة وفرع) |
| الفروع | `admin/branches/*` (فورم وساعات عمل) · إعدادات الفروع (مشاركة البيانات + مصفوفة نقل العاملات بين الفروع) |
| القوائم المرجعية | الدول، المدن، الجنسيات، الأديان، المهن، المهارات، اللغات، المؤهلات، الحالات الاجتماعية، المطارات، أماكن الإيواء، أنواع الشكاوى، مكاتب الاستقدام، الخطوات |
| الرسائل والإشعارات | قوالب الرسائل · إعدادات المزودين (البريد، SMS، واتساب: Meta/ClickChat/UltraMsg/Chatwoot) · التنبيهات الآلية · Firebase |
| التكاملات | مساند/تكامل · ميسر · ZATCA · Zoho SalesIQ · R2 · **مفيش صفحة موحّدة في الكود** |
| استيراد البيانات | `reports/import` + المراجعة والاعتماد (29 نوع) · استيراد السير بالجملة |
| المساعد الذكي | `mcp/guide` (للموظفين) · `superadmin/mcp` (اللوحة، الموظفين، الأدوات، التوكنات، السجل، الحماية) |
| الدعم | مركز المعرفة · تذاكر الدعم |
| المطوّر | ✅ v1.10 (`setDev` · `sa*` · `saLogin`) — من **ClickERP2** (`routes/ingaz.php` مجموعة `dev-super-admin` · `resources/views/admin/superadmin/*` · `config/superadmin_modules.php`) |

---

## مركز المهام — ✅ v1.10 (`tc*`)

**المصدر: ClickERP2** (فرع `feature/tech-company-finance`) — `packages/hr/resources/views/task_center/*` · `packages/hr/routes/hr.php` · `app/Support/TaskCenter.php` · `TaskCenterSettings.php`.
- موديل `task_center` متاح لنوع البرنامج «نظام عام» بس، ومقفول افتراضياً.
- **الأعمدة:** جديد، قيد التنفيذ، اختبار داخلي، جاهز للنشر، بانتظار قبول العميل، معلّق (بسبب)، مغلق (يختفي بعد 30 يوم).
- **الأنواع:** BUG، UPD، DATA، OPS، MTG، SUP، GEN (للشغل العام). **الأولوية:** طارئ، مهم، متوسط، منخفض.
- **الوقت:** مؤقت واحد لكل موظف · الوقت اليدوي محتاج اعتماد · مؤقت طويل من غير لقطات ← «بانتظار الموافقة».
- **المراقبة:** لقطات من المتصفح (مشاركة الشاشة) والمؤقت شغال بس · حذف لقطة بسبب بيخصم مدتها · جلسات RustDesk بموافقة الموظف (مهلة 15 دقيقة).
- **الصلاحيات المنفصلة:** اعتماد الوقت، اللقطات، طلب جلسة، متابعة الفريق، الإعدادات.

**مركز التحكم في النسخ (v1.27 «جديد»):** fleet · instance · servers · deploy · incidents — مبني على `SystemUpdateClient` + `Api/SystemUpdatesFeedController`. المقترح التقني في `superadmin/README.md`.

## Super Admin — ✅ v1.25 منطقة منفصلة `superadmin/` (كانت v1.10 `setDev` · `sa*`)

**الهيكل:** قالب ClickERP نفسه (`admin/superadmin/layouts/app` + `partials/sidebar|topbar`) وأنماط `public/css/dev-super-admin.css`. شرح الملفات في `superadmin/README.md`. الشاشات: login · index (جديد) · subscription · employees · modules · designs · updates · update-form · backups · scheduler · recovery · test-accounts · hourly · moyasar · invoices · invoice-fix · mcp.


**المصدر: ClickERP2** — `DevSuperAdminController` · `SuperAdminMcpController` · `SuperAdminInvoiceCorrectionController` · `DashboardDesignsController` · `DevSystemUpdatesController` · `MoyasarSettingsController`.
- **نوع البرنامج:** نظام الاستقدام / نظام عام — التغيير بعبارة تأكيد + كلمة مرور + سبب، ومش بيمسح بيانات. «نظام عام» بيقفل شاشات المالية الخاصة بالاستقدام (فواتير العقود، مساند، الوكلاء، الملخص التشغيلي) وبيخبي صلاحياتها.
- **الموديلات (14):** إيجار الساعات، الموديل التشغيلي العام، إدارة التشغيل، الفروع، الموقع الخارجي، روابط API، CRM، الموارد البشرية، المالية، حسابات شركات التقنية والتسويق، تأشيرات الشركة، مركز المهام، الكوبونات، العمولات.
- **الدخول:** اسم وكلمة مرور بس (مفيش رمز تحقق) — الرمز موجود بس في استعادة/حذف النسخ الاحتياطية.

---

## الشاشات الناقصة — ✅ v1.34

| شاشتنا | الكود |
|---|---|
| `rcExtensions` | `ContractExtensionRequestController` · `ING/ContractExtension/*` |
| `rcRenewals` | `RenewContractController` · `ING/RenewContracts/*` · `RequestsStatusEnum` |
| `rcReturns` · `rcReturnShow` | `admin/ContractReturnRequestController` · `views/admin/contract-return-requests/*` · `RecruitmentContractWarrantyReturnService` · `ReturnRequestController` · `ING/return_request/*` |
| `rcArchive` | `ContractArchiveController` · `ContractArchiveService` · `ING/contract-archive/*` |
| `rcFinance` · `rcFinanceShow` | `RecruitmentContractFinanceController` · `Support/RecruitmentAgentBalance` · `ING/recruitment_contract_finance/*` |
| `rcRefunds` | `PostArrivalRefundController` · `PostArrivalRefundService` · `ING/post_arrival_refunds/*` |
| `musanedTransfers` | `MusanedTransferController` · `MusanedInvoiceReportController` · `ING/musaned_transfers/*` |
| `cvShares` | `MaidCvShareController` · `MaidCvShareService` · `ING/maid-cv-shares/*` |
| `cvReview` | `MaidController` (pendingSubmissions · status-change · visibilityBatches · deletedCvs) · `MaidStatusChangeRequest` |
| `dispatches` | `RecruitmentContractExternalOfficeDispatchController` · `ContractExternalOfficeDispatch` |
| `supplierPayments` | `RecruitmentSupplierPaymentController` · `ING/recruitment_supplier_payments/*` |
| `arrivals` | `ReportController::arrivals` · `ReportExportService` · `ING/reports/arrivals` |
| `selectWorkers` | `SelectWorkerController` · `SelectWorkerSettingsController` · `site/pages/select_worker` |
| `inbound` · `inboundShow` | `SponsorshipTransferController::webRequests` · `RecruitmentRequestController` · `KnownServiceRequestController` · `ContactController` · `ProfessionalApplicationController` |
| `rentalOrders` · `rentalOrderShow` | `RentalOrderController` · `ING/rental-orders/*` · `OrderEnum` |
| `rentalRecruit` | `RecruitmentContractController::companyIndex` · `Contract::scopeForCompanyRecruitment` |
| `transferTerms` | `TransferContractTermsController` · `ING/TransferContractTerms/*` |
| `couponsHourly` | `HourlyCouponController` · `admin/coupons/hourly` |
| `crmDispatch` · `waTemplates` | `NotificationController` (index · indexWhatsApp) · `WhatsAppInboxController::createTemplate` · `ClickChatTemplateStatus` |
| `setCountry` | `CountryPoliceController` · `PoliceSettingController` · `ING/country_polices/*` · `ING/police_settings/*` (سياسات الدول، مش شرطة) |
| `hbDrivers` · `hbRenewals` | `HourlyDriverController` · `Hourly/RenewalController` · `RenewalEligibility` |
| `hbExceptions` | `MissedVisitRecoveryController` · `EarlyFinishController` · `ServiceTimeController` · `OrderCompensationController` |
| `hbSos` · `hbMerge` | `HourlySosController` · `ServiceIncidentController` · `Hourly/CustomerController` (duplicates · merge) |
| `finCreditNotes` · `finCreditNoteAdd` | `InvoiceController` (credit-notes) · `RentalContractInvoiceController` · `InvoiceDraftController` · `SuperAdminInvoiceCorrectionController` |
| `finNetSales` · `finMoyasar` | `MusanedInvoiceReportController` · `MoyasarPaymentsReportController` |
| `myProfile` · `notifCenter` · `noPerm` | `routes/admin.php` profile · change-password · two-factor · `routes/web.php` notifications · `admin/no-permissions` |
| `cms*` | `config/home_sections.php` · `HomeSectionSettingsController` · `ING/blogs · offers · fast_news · home_hero · sliders · home_sections · why_us · why_choose_us · what_do · about_us · musaned_section · find_us · steps · feature · testimonials · partenrs · questions` |
| `agent/*` | `routes/agent.php` · `app/Http/Controllers/Agent/*` · `resources/views/agent/*` · `routes/office.php` |

## التجارة: المخزون والفواتير التجارية ونقاط البيع — ✅ v1.75 (`cm*` · `pos.html` · `superadmin/commerce.html`) «جديد»

موديول جديد مش موجود في ClickERP لسه. المرجع المعتمد: `docs/modules/general modula/COMMERCE_POS_MASTER_PLAN.md` (الخطة) + «موجز التصميم» (docx في نفس الفولدر). لما يختلفوا، الخطة هي المعتمدة.

- **مكانه (v1.79):** إدارتين في نسخ «عام» بنوع «تجاري» بس: `pos` «المبيعات» (والعملاء وعروض الأسعار جواه) · `stock` «المنتجات». الطباعة من `CM.print` بقالب printsheet/ps-* (A4 وحراري 80 مم). «خدمي» = المالية بس (ومعها عروض الأسعار `finQuotes` من `SalesDocument` في ClickERP2). «العمليات» مش موجودة في العام.
- **الملفات:** `assets/commerce/cm-features.js` (الميزات والقوالب — مصدر واحد للوحة وSuper Admin) · `cm-core.js` (شارات الحالة، تنسيق الكميات والتكلفة، معاينة رؤية التكلفة والفرع والحالات، بيانات العيّنة) · `cm-items.js` · `cm-stock.js` · `cm-trade.js` · `cm-pos.js` · `pos.js`. الصفحات بتتولد بـ `python3 design/tools/mk-commerce-pages.py`.
- **الخطّاف في الباندل:** `window.CERP_EXT` بيتشغّل قبل أول رسم، فملفات التجارة بتسجّل شاشاتها من غير ما تدخل الباندل.

| شاشتنا | الكود المقترح (الخطة) |
|---|---|
| `superadmin/commerce.html` | `dev-superadmin.commerce` · `CommerceSettings` (`fn_commerce_settings`) · `config/commerce_templates.php` · `config/superadmin_modules.php` (commerce_inventory ← commerce_pos ← commerce_restaurant) |
| `cmItems` · `cmItemAdd` · `cmItemCard` · `cmImport` | `fn_items` (+ name_en, base_unit_id, tracking, is_weighted, cogs_account_id, is_active, commerce_kind) · `fn_item_units` · `fn_item_barcodes` · `fn_item_components` · `ItemCatalogService` |
| `cmCats` · `cmUnits` · `cmPrices` · `cmPriceEdit` | `fn_categories` (+ الحسابات والضريبة و pos_visible و kitchen_station_id) · `fn_units` · `fn_price_lists` · `fn_price_list_items` |
| `cmStock` · `cmDocs` · `cmDocShow` · `cmCount` · `cmCountRun` · `cmStores` | `fn_stock_ledger` · `fn_stock_balances` · `fn_stock_documents(_lines)` · `fn_stocks` (+ cost_center_id, inventory_account_id, is_default, allow_negative) · `StockLedgerService` · `CostingService` · `StockDocumentService` |
| `cmSales` · `cmSaleAdd` · `cmSaleShow` · `cmPurchases` · `cmPurchaseAdd` · `cmReturns` · `cmReturnAdd` | `fn_sales`/`fn_purchases` بـ `origin = 'commerce'` · `CommercialSaleService` · `CommercialPurchaseService` · `CommercialReturnService` · ZATCA عبر الوحدة الحالية |
| `cmDash` · `cmShifts` · `cmShiftShow` · `cmPosInvoices` · `cmPosInvoiceShow` · `cmRegisters` · `cmDevicePair` · `cmCashiers` · `cmPayMethods` · `cmPromos` · `cmSettle` | `fn_pos_*` · `fn_payment_methods` · `fn_promotions` · `fn_card_settlements` · `PosSessionService` · `PosShiftPostingService` · `PosOrderIngestService` |
| `cmZatca` · `cmZatcaWizard` | `fn_zatca_egs_units` · `fn_zatca_submissions` · `EgsOnboardingService` · `EgsReportingService` · `commerce:zatca-report` |
| `cmReports` · `cmSettings` | `config/v2_reports_catalog.php` (قسم التجارة) · `CommerceAccountSettings` · `commerce:provision-accounts` · `commerce:import-legacy-stock` |
| `pos.html` (تطبيق الكاشير) | Flutter أوفلاين · `/api/pos/v1` بتوكن جهاز · `pos.device` middleware |

**قواعد ثابتة في كل الشاشات:** شارة حالة بلون ثابت لكل معنى (مسودة رمادي · بانتظار كهرماني · منتهي أخضر · مشكلة أحمر · ملغى مشطوب). «اعتماد» و«ترحيل» زرين منفصلين، ومعاينة القيد قبل الترحيل، والمرحّل للقراءة بـ «عكس» بدل «حذف». الكمية لحد 4 خانات من غير أصفار زايدة، والتكلفة خانتين والكاملة عند المرور، والسالب بلون تحذير. صلاحية «رؤية التكلفة» بتخفي التكلفة والربح والقيمة من كل الشاشات.
