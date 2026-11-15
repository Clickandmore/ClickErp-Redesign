# Click ERP — تغطية التصميم (Design Coverage)

آخر تحديث: 2026-11-15. الملف ده مرجع للوكيل والمطورين: إيه اللي متصمم، وفين.

- **عندنا (المعتمد):** `design/pages/` — v1.3، الثيم المعتمد. https://clickandmore.github.io/ClickErp-Redesign/design/pages/
- **الريادة (مرجع وظائف بس، مش ثيم):** `design/modules/riyada/` — 107 شاشة و293 حالة. https://clickandmore.github.io/ClickErp-Redesign/design/modules/riyada/
  - قائمة الوظائف: `design/modules/riyada/FEATURES.md`، والفهرس: `design/modules/riyada/screens.json`

القاعدة: الشكل من `design/pages`، والوظايف الناقصة تتاخد من الريادة بعد اعتمادها في شيت المقارنة.

## قرارات لسه مفتوحة (متتنفذش قبل الحسم)

- المسمّى: «نقل الكفالة» (عندنا) أم «نقل الخدمات» (الريادة)
- المخزون والأصناف: الأصناف / أرصدة أول المدة / الجرد لسه عندنا، وتقرير «حركة بيع الأصناف» لسه في الاتنين — القرار شيل المخزون من المالية
- تنبيه العقود بدون تذاكر: 45 يوم (عندنا) أم 60 (الريادة)
- المساعد الذكي، الحسابات التجريبية في الدخول، الكوبونات (مالهاش شاشة في الاتنين)
- بطاقة الحضور: في الرئيسية (عندنا) أم في الشريط العلوي (الريادة)

## الشاشات المتصممة

| الموديول | المعرّف (id) | الشاشة | عندنا | الريادة | ملاحظة |
|---|---|---|---|---|---|
| الدخول والتحقق | login | تسجيل الدخول | `login.html` | `screens/auth-login.html` | الريادة: auth-login + 14 حالة (OTP، 2FA، اختيار الفرع...) |
| الرئيسية ولوحة التحكم | dashboard | لوحة التحكم | `dashboard.html` | `screens/dashboard.html` | الريادة فيها إضافات |
| الرئيسية ولوحة التحكم | favorites | المفضلة | — | `screens/favorites.html` | جديدة |
| الرئيسية ولوحة التحكم | myday | عملي اليوم | — | `screens/myday.html` | جديدة |
| التقارير والتحليلات | reports | مركز التقارير | `reports.html` | `screens/reports.html` | الريادة فيها إضافات |
| التقارير والتحليلات | report | تقرير | `report.html` | `screens/report.html` |  |
| عقود الاستقدام والفواتير | contracts | عقود الاستقدام | `contracts.html` | `screens/contracts.html` | الريادة فيها إضافات |
| عقود الاستقدام والفواتير | contractShow | تفاصيل العقد | `contractShow.html` | `screens/contractShow.html` | الريادة فيها إضافات |
| عقود الاستقدام والفواتير | contractAdd | إضافة عقد استقدام | `contractAdd.html` | `screens/contractAdd.html` |  |
| عقود الاستقدام والفواتير | invoices | فواتير العقود | `invoices.html` | `screens/invoices.html` | الريادة فيها إضافات |
| عقود الاستقدام والفواتير | contractEdit | تعديل العقد | `contractEdit.html` | `screens/contractEdit.html` |  |
| السير الذاتية | cvs | السير الذاتية | `cvs.html` | `screens/cvs.html` | الريادة فيها إضافات |
| السير الذاتية | cvAdd | إضافة سيرة ذاتية | `cvAdd.html` | `screens/cvAdd.html` |  |
| السير الذاتية | cvShow | تفاصيل السيرة | `cvShow.html` | `screens/cvShow.html` |  |
| السير الذاتية | cvEdit | تعديل السيرة | `cvEdit.html` | `screens/cvEdit.html` |  |
| العملاء (CRM) | clients | إدارة العملاء | `clients.html` | `screens/clients.html` | الريادة فيها إضافات |
| العملاء (CRM) | followups | متابعة العملاء | `followups.html` | `screens/followups.html` | الريادة فيها إضافات |
| العملاء (CRM) | polls | الاستبيانات | `polls.html` | `screens/polls.html` |  |
| العملاء (CRM) | visitors | زوار الموقع | `visitors.html` | `screens/visitors.html` |  |
| العملاء (CRM) | clArchive | أرشيف العملاء | `clArchive.html` | `screens/clArchive.html` | الريادة فيها إضافات |
| العملاء (CRM) | clientShow | ملف العميل | `clientShow.html` | `screens/clientShow.html` |  |
| العملاء (CRM) | clientAdd | إضافة عميل | `clientAdd.html` | `screens/clientAdd.html` |  |
| العملاء (CRM) | campaigns | الحملات والرسائل المرسلة | — | `screens/campaigns.html` | جديدة |
| العملاء (CRM) | chats | المحادثات | — | `screens/chats.html` | جديدة |
| الإيواء | housing | إدارة الإيواء | `housing.html` | `screens/housing.html` | الريادة فيها إضافات |
| الإيواء | housingShow | تفاصيل الإيواء | `housingShow.html` | `screens/housingShow.html` |  |
| الإيواء | housingAdd | عقد إيواء | `housingAdd.html` | `screens/housingAdd.html` |  |
| نقل الخدمات | transfer | نقل الكفالة | `transfer.html` | `screens/transfer.html` | الريادة فيها إضافات |
| نقل الخدمات | transferShow | تفاصيل طلب النقل | `transferShow.html` | `screens/transferShow.html` |  |
| نقل الخدمات | transferAdd | طلب نقل كفالة | `transferAdd.html` | `screens/transferAdd.html` |  |
| نقل الخدمات | transferOverview | نظرة عامة على الحالات | `transferOverview.html` | `screens/transferOverview.html` |  |
| المالية | finAccounts | شجرة الحسابات | `finAccounts.html` | `screens/finAccounts.html` | الريادة فيها إضافات |
| المالية | finEntries | القيود اليومية | `finEntries.html` | `screens/finEntries.html` | الريادة فيها إضافات |
| المالية | finBonds | سندات القبض والصرف | `finBonds.html` | `screens/finBonds.html` | الريادة فيها إضافات |
| المالية | finReports | التقارير المالية | `finReports.html` | `screens/finReports.html` | الريادة فيها إضافات |
| المالية | finReport | تقرير مالي | `finReport.html` | `screens/finReport.html` | الريادة فيها إضافات |
| المالية | finSales | فواتير البيع | `finSales.html` | `screens/finSales.html` | الريادة فيها إضافات |
| المالية | finSaleAdd | فاتورة بيع جديدة | `finSaleAdd.html` | `screens/finSaleAdd.html` |  |
| المالية | finPurchases | فواتير الشراء | `finPurchases.html` | `screens/finPurchases.html` | الريادة فيها إضافات |
| المالية | finReturns | مرتجع المشتريات | `finReturns.html` | `screens/finReturns.html` | الريادة فيها إضافات |
| المالية | finBanks | البنوك والحسابات البنكية | `finBanks.html` | `screens/finBanks.html` | الريادة فيها إضافات |
| المالية | finBoxes | الصناديق النقدية | `finBoxes.html` | `screens/finBoxes.html` |  |
| المالية | finTransfers | التحويل بين الحسابات | `finTransfers.html` | `screens/finTransfers.html` | الريادة فيها إضافات |
| المالية | finChecksIn | الشيكات الواردة | `finChecksIn.html` | `screens/finChecksIn.html` | الريادة فيها إضافات |
| المالية | finChecksOut | الشيكات الصادرة | `finChecksOut.html` | `screens/finChecksOut.html` | الريادة فيها إضافات |
| المالية | finItems | الأصناف | `finItems.html` | — | مخزون — قرار مفتوح |
| المالية | finStock | أرصدة أول المدة | `finStock.html` | — | مخزون — قرار مفتوح |
| المالية | finInventory | الجرد | `finInventory.html` | — | مخزون — قرار مفتوح |
| المالية | finClients | العملاء الماليون | `finClients.html` | `screens/finClients.html` | الريادة فيها إضافات |
| المالية | finSuppliers | الموردون | `finSuppliers.html` | `screens/finSuppliers.html` |  |
| المالية | finAgents | الوكلاء الخارجيون | `finAgents.html` | `screens/finAgents.html` |  |
| المالية | finCenters | مراكز التكلفة | `finCenters.html` | `screens/finCenters.html` |  |
| المالية | finSettings | الإعدادات المحاسبية | `finSettings.html` | `screens/finSettings.html` | الريادة فيها إضافات |
| المالية | finAdd | إضافة | `finAdd.html` | `screens/finAdd.html` |  |
| المالية | finShow | عرض | `finShow.html` | `screens/finShow.html` |  |
| المالية | finCentersTree | شجرة مراكز التكلفة | `finCentersTree.html` | `screens/finCentersTree.html` |  |
| المالية | finAssets | الأصول | — | `screens/finAssets.html` | جديدة |
| المالية | finCommissions | العمولات | — | `screens/finCommissions.html` | جديدة |
| المالية | finDash | لوحة المالية | — | `screens/finDash.html` | جديدة |
| المالية | finRequests | الطلبات المالية | — | `screens/finRequests.html` | جديدة |
| عقود التأجير | rental | عقود التأجير | `rental.html` | `screens/rental.html` | الريادة فيها إضافات |
| عقود التأجير | rentalShow | تفاصيل عقد التأجير | `rentalShow.html` | `screens/rentalShow.html` | الريادة فيها إضافات |
| عقود التأجير | rentalAdd | عقد تأجير | `rentalAdd.html` | `screens/rentalAdd.html` | الريادة فيها إضافات |
| عقود التأجير | rentalInvoices | فواتير التأجير | `rentalInvoices.html` | `screens/rentalInvoices.html` |  |
| عقود التأجير | rentalSettings | باقات التأجير | `rentalSettings.html` | `screens/rentalSettings.html` |  |
| عقود التأجير | rentalTerms | بنود عقد التأجير | `rentalTerms.html` | `screens/rentalTerms.html` |  |
| الطلبات والحجوزات | orders | الطلبات والحجوزات | `orders.html` | `screens/orders.html` | الريادة فيها إضافات |
| الطلبات والحجوزات | orderShow | تفاصيل الطلب | `orderShow.html` | `screens/orderShow.html` |  |
| التفاويض والرحلات والوكلاء | agentPortal | بوابة الوكيل | — | `screens/agentPortal.html` | جديدة |
| التفاويض والرحلات والوكلاء | agentShow | ملف المكتب | — | `screens/agentShow.html` | جديدة |
| التفاويض والرحلات والوكلاء | agents | الوكلاء الخارجيون | — | `screens/agents.html` | جديدة |
| التفاويض والرحلات والوكلاء | delAdd | إضافة تفويض | — | `screens/delAdd.html` | جديدة |
| التفاويض والرحلات والوكلاء | delShow | تفاصيل التفويض | — | `screens/delShow.html` | جديدة |
| التفاويض والرحلات والوكلاء | delegations | تفاويض الإنجاز | — | `screens/delegations.html` | جديدة |
| التفاويض والرحلات والوكلاء | tripShow | تفاصيل الرحلة | — | `screens/tripShow.html` | جديدة |
| التفاويض والرحلات والوكلاء | trips | الرحلات | — | `screens/trips.html` | جديدة |
| التأجير بالساعة | hb-alerts | البلاغات | — | `screens/hb-alerts.html` | جديدة |
| التأجير بالساعة | hb-board | لوحة الطلبات | — | `screens/hb-board.html` | جديدة |
| التأجير بالساعة | hb-clients | العملاء | — | `screens/hb-clients.html` | جديدة |
| التأجير بالساعة | hb-schedule | الجدول والفرق | — | `screens/hb-schedule.html` | جديدة |
| التأجير بالساعة | hb-services | الخدمات والباقات | — | `screens/hb-services.html` | جديدة |
| التأجير بالساعة | hb-tracking | التتبع والمسارات | — | `screens/hb-tracking.html` | جديدة |
| التأجير بالساعة | hb-visits | الزيارات | — | `screens/hb-visits.html` | جديدة |
| التأجير بالساعة | hb-zones | المناطق | — | `screens/hb-zones.html` | جديدة |
| التأجير بالساعة | hbNew | إنشاء طلب بالساعة | — | `screens/hbNew.html` | جديدة |
| التأجير بالساعة | hbVisit | تفاصيل الزيارة | — | `screens/hbVisit.html` | جديدة |
| خدمة العملاء (الشكاوى والهروب) | absconding | بلاغات الهروب | — | `screens/absconding.html` | جديدة |
| خدمة العملاء (الشكاوى والهروب) | claimShow | تفاصيل الشكوى | — | `screens/claimShow.html` | جديدة |
| خدمة العملاء (الشكاوى والهروب) | claims | الشكاوى | — | `screens/claims.html` | جديدة |
| الموارد البشرية | empShow | ملف الموظف | — | `screens/empShow.html` | جديدة |
| الموارد البشرية | hr-attendance | الحضور | — | `screens/hr-attendance.html` | جديدة |
| الموارد البشرية | hr-custody | العهد | — | `screens/hr-custody.html` | جديدة |
| الموارد البشرية | hr-daily | التقارير اليومية | — | `screens/hr-daily.html` | جديدة |
| الموارد البشرية | hr-employees | الموظفون | — | `screens/hr-employees.html` | جديدة |
| الموارد البشرية | hr-payroll | الرواتب | — | `screens/hr-payroll.html` | جديدة |
| الموارد البشرية | hr-requests | الطلبات | — | `screens/hr-requests.html` | جديدة |
| الموارد البشرية | hr-sanctions | الجزاءات | — | `screens/hr-sanctions.html` | جديدة |
| الموارد البشرية | hr-workers | العاملات | — | `screens/hr-workers.html` | جديدة |
| الإعدادات | set-ai | المساعد الذكي | — | `screens/set-ai.html` | جديدة |
| الإعدادات | set-branches | الفروع | — | `screens/set-branches.html` | جديدة |
| الإعدادات | set-general | العامة | — | `screens/set-general.html` | جديدة |
| الإعدادات | set-import | استيراد البيانات | — | `screens/set-import.html` | جديدة |
| الإعدادات | set-integrations | التكاملات | — | `screens/set-integrations.html` | جديدة |
| الإعدادات | set-messages | الرسائل والإشعارات | — | `screens/set-messages.html` | جديدة |
| الإعدادات | set-recruit | الاستقدام والسير | — | `screens/set-recruit.html` | جديدة |
| الإعدادات | set-rental | التأجير | — | `screens/set-rental.html` | جديدة |
| الإعدادات | set-site | موقع المكتب | — | `screens/set-site.html` | جديدة |
| الإعدادات | set-support | الدعم | — | `screens/set-support.html` | جديدة |
| الإعدادات | set-users | المستخدمون والصلاحيات | — | `screens/set-users.html` | جديدة |
| مكتبة المكوّنات والحالات | ui-kit | مكتبة المكونات | — | `screens/ui-kit.html` | جديدة |

الإجمالي: 110 شاشة — 60 عندنا، 50 في الريادة بس.

## مش متغطي في أي تصميم (معروف لحد دلوقتي)

- الكوبونات
- التأشيرات (لو ليها شاشات منفصلة عن التفاويض)
- أي روت في النظام مش موجود في الجدول ده — الوكيل يطلّعها من route:list
