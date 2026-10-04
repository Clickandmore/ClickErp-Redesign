repo: Clickandmore/ClickErp-Redesign
branch: main
path: source/resources/views/admin, public/admin

## Last sync
date: 2026-08-24T13:25:00Z

### Updated in this project
- Finance completed: 18 add/edit forms and a shared detail view wired into every list, cost-centre tree with spend caps, chart of accounts regrouped by root type
- Finance split into 17 single-purpose screens (one table each) with grouped sidebar sections: books, invoices, cash, inventory, parties, analysis & setup
- Finance ops screens added: sales invoices (+new invoice form and ZATCA tax-invoice print), purchases & returns, cash/banks/cheques, inventory & items, financial clients & suppliers, cost centres, accounting & ZATCA settings
- Finance module built: chart of accounts tree, journal entries, receipt/payment bonds, and a 23-report financial reports centre
- Blade export package added (export/blade): redesign CSS layer + grouped contract-stats partial, re-generatable on each  design update
- Rental contracts module built: 5 status tabs, 8-tab details, add/edit, packages & settings, invoices, extension, signing, plus A4 print views for contract, tax invoice and receipt bond
- Sponsorship transfer module built: all requests / trial / transferred / failed / web requests, details, add-edit, status overview, invoice and settlement
- Housing (shelter) module built: inside / outside / available-for-transfer / departure stage / deported / general shelter, plus details, add-edit and living-expense entry
- Price-offer actions removed from client and CV action menus (feature not in the live system)
- Contract stages regrouped: valid in-progress / valid after arrival / closed; edit-contract screen added
- Recruitment contracts rebuilt: 13 real stages, quick filters, bulk stage advance, 7-tab details with stage timeline
- Orders & bookings rebuilt from RecruitmentRequest/* with SLA timer, type tabs and request→contract conversion
- Admin sidebar items (finance, HR, settings) turned into dropdowns from real module folders
- Customers group rebuilt: all clients, follow-ups, site visitors, client archive
- Price offers removed (feature not available in the live system)
- Site visitors built on website_visitors/index.blade.php (live radar list, visits history, conversations, tracking codes)
- Built the full CRM module: client list, profile, add/edit, follow-ups, price offers, polls
- Client fields and columns lifted from admin/ingaz/clients (create/index/show)
- CRM sub-screens grounded in packages/crm views (follow_clients, price_offers, polls)
- Offers screen rebuilt on price_offers columns (client/date/total/status) + line-item model
- Polls rebuilt on polls + questions + poll_clients (choices/essay, send-poll modal)
- Dashboard reordered; attendance card rebuilt


## Screen map

| clickerp-finance.js / clickerp-finance-reports.js — المالية | packages/finance/resources/views/finance/{account,constraint,bond,account_statement,balances,income_list,audit_budget,cost_centers,…} |

| clickerp-rental-app.js + clickerp-rental-print.js — عقود التأجير وطباعتها | admin/ingaz/RentContract/{index,create,create_final,edit,show,settings,contract-section,client-section,maid-section}.blade.php |

| clickerp-transfer.js — نقل الكفالة وتفاصيله | admin/ingaz/SponsorshipTransfer/{index,trial,transferred,failed,web-requests,create,show,invoice-create,status-overview}.blade.php |

| clickerp-housing.js — إدارة الإيواء وتفاصيلها | admin/ingaz/ShelterContract/{index,outside,available-for-transfer,deportation-stage,deported,general-shelter,create,show}.blade.php |

| clickerp-orders.js — الطلبات وتفاصيل الطلب | admin/ingaz/RecruitmentRequest/index.blade.php, show.blade.php, RentContract/, SponsorshipTransfer/ |
| إدارة العملاء / ملف العميل / إضافة عميل | source/resources/views/admin/ingaz/clients/{index,create,edit,show}.blade.php |
| متابعة العملاء / عروض الأسعار / الاستبيانات | source/packages/crm/resources/views/crm/{follow_clients,price_offers,polls}/index.blade.php |

| شاشة المشروع | ملفات المستودع |
|---|---|
| Click ERP Redesign.html — تسجيل الدخول | source/resources/views/admin/auth/login.blade.php, auth/layout.blade.php, auth/forgot-password.blade.php |
| Click ERP Redesign.html — الشريط العلوي | source/resources/views/admin/layout/inc/header.blade.php |
| Click ERP Redesign.html — القائمة الجانبية | source/resources/views/admin/layout/inc/sidebar.blade.php |
| Click ERP Redesign.html — الرئيسية (حسب الدور) | source/resources/views/admin/dashboard-new.blade.php, dashboard.blade.php, dashboard-employee.blade.php |
| Click ERP Redesign.html — مركز التقارير / التحليلات | source/resources/views/admin/dashboard-new.blade.php, admin/partials/activity-feed.blade.php |
| Click ERP Redesign.html — قائمة العقود | source/resources/views/admin/ingaz/RecruitmentContract/, admin/istiqdam/contracts.blade.php |
| clickerp-cv.js — معاينة نموذج السيرة | admin/ingaz/maids/cv.blade.php, cv_preview.blade.php |
| clickerp-cv.js — قائمة السير | admin/ingaz/maids/index.blade.php (stats + 11 filters + 8 columns), rental-index.blade.php |
| clickerp-cv.js — إضافة سيرة (كاملة) | admin/ingaz/maids/create.blade.php, _experience_duration_js.blade.php, _language_rating.blade.php |
| clickerp-cv.js — إضافة سيرة (مختصرة) | admin/ingaz/maids/rapid_cv_add.blade.php, convert_page.blade.php |
| clickerp-cv.js — تفاصيل السيرة | admin/ingaz/maids/details.blade.php (hero + 6 stats + sections + attachments), cv_preview modal في index.blade.php |
| طبقة الأنماط الجديدة (clickerp.css) | public/admin/custom/, public/admin/assets/css/ |

## Permission mapping (قسم التصميم ← مفتاح الصلاحية)

| قسم | PermKey |
|---|---|
| بطاقة الحضور والساعة | DASHBOARD_VIEW |
| يحتاج قرارك / مؤشرات التشغيل | DASHBOARD_OPERATIONS_OVERVIEW |
| اتجاه العقود (الرسم) | DASHBOARD_ORDERS_STATISTICS |
| مراحل العقود | DASHBOARD_CONTRACTS_OVERVIEW |
| دائرة الجنسيات | DASHBOARD_NATIONALITIES_STATISTICS |
| الطلبات حسب الجنس / النوع | DASHBOARD_ORDERS_TYPES |
| مخزون المكاتب | DASHBOARD_OFFICES_STATISTICS |
| آخر الأنشطة | DASHBOARD_RECENT_ACTIVITIES |
| مواعيد وتذكيرات | DASHBOARD_CALENDAR_EVENTS |
| تفاويض وعقود الموظفين | DASHBOARD_EMPLOYEE_PERFORMANCE |
| بطاقات: عقود، طلبات، سير، عملاء، إيجار، إيواء، نقل كفالة | DASHBOARD_RECRUITMENT_CONTRACTS_COUNT, DASHBOARD_RECRUITMENT_ORDERS_COUNT, DASHBOARD_CV_COUNT, DASHBOARD_CLIENTS_COUNT, DASHBOARD_RENTAL_CONTRACTS_COUNT, DASHBOARD_SHELTER_COUNT, DASHBOARD_SPONSORSHIP_TRANSFERS_COUNT |
| مركز التقارير (تصدير) | REPORTS_EXPORT, REC_CONTRACTS_EXPORT, MAIDS_EXPORT |
| تبويبات السير | MAIDS_VIEW, MAIDS_RENTAL_VIEW, MAIDS_BACKOUT_VIEW, MAIDS_DELETED_VIEW |
| إضافة سيرة | MAIDS_CREATE |

## قواعد التسليم (من docs/DESIGN-HANDOFF.md)
- الحفاظ على تعبيرات Blade وأسماء الحقول والمسارات و`data-*` و`@permission`.
- RTL أساسي مع اختبار LTR.
- لا مكتبات خارجية جديدة بدون مبرر.
- لا بيانات عملاء حقيقية في اللقطات.
