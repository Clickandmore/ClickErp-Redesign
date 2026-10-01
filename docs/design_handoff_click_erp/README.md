# Handoff: Click ERP Redesign (لوحة الإدارة)

## Overview
Full redesign of the Click ERP admin panel (Laravel + Blade, Arabic RTL first, English LTR supported). Covers: login, app shell (sidebar + topbar), role-aware dashboard, reports centre, recruitment contracts, orders & bookings, CVs, CRM, rental contracts, housing (shelter), sponsorship transfer, and the finance suite (17 screens, 18 forms, 23 reports, ZATCA invoice print).

Target repo: `Clickandmore/ClickErp-Redesign` — views in `source/resources/views/admin`, assets in `public/admin`.

## About the Design Files
Files in `design/` are **design references built in HTML/vanilla JS** — prototypes showing intended look and behaviour, not production code. Data is mocked inline. The task is to **recreate these designs inside the existing Laravel/Blade codebase** using its controllers, models, routes, DataTables, permissions and translations. Do not ship the prototype JS.

Open `design/Click ERP Redesign.html` in a browser to explore every screen (sidebar navigates; role switcher on login; AR/EN toggle in topbar).

## Fidelity
**High-fidelity.** Final colours, typography, spacing, states and copy. Recreate pixel-accurately. The CSS in `design/clickerp.css` is the source of truth for every value.

## Fastest implementation path (recommended order)
1. **Global CSS layer first.** Install `blade-export/blade/public/admin/custom/clickerp-redesign.css` after existing styles in the admin layout + Cairo font (see `blade-export/blade/README.md`). This restyles tables, filters, cards, inputs and buttons system-wide by targeting existing classes (`.stat-card`, `#dataTableObject`, `.form-control`, `.btn` …) with zero logic changes.
2. **Shell:** `admin/layout/inc/sidebar.blade.php` + `header.blade.php` (sidebar 264px, collapsible to 76px; sticky 64px topbar).
3. **Shared Blade components** (build once, reuse in every module): stat cards, status pills, segmented tabs with counts, filter bar, table card with footer/pager, row actions menu, detail hero + tab bar, key/value grid, modal, drawer, print overlay. See *Components*.
4. **Modules** in order of usage: Contracts → Orders → CVs → CRM → Rental → Housing → Transfer → Finance.
5. Keep all Blade expressions, field names, routes, `data-*`, filter IDs and `@AdminGuardHasPerm(...)` intact.

Use `design/github.md` → **Screen map** to find which Blade file each designed screen replaces, and **Permission mapping** for which PermKey gates each dashboard block.

## Design Tokens
```css
--navy:#16233f; --navy-2:#22345a; --navy-soft:#eef1f7;   /* primary / active */
--green:#7ab648; --green-d:#65a034; --green-soft:#eef6e6;  /* success, rental, complete */
--red:#d4453c; --red-soft:#fdeceb;                         /* overdue, failed, delete */
--amber:#c8862a; --amber-soft:#fdf3e3;                     /* in progress, warning */
--ink:#0d1626; --body:#2b384e; --muted:#5f6c80; --muted-2:#828d9e;
--line:#dfe4ee; --line-2:#eaeef5; --bg:#f5f7fa; --surface:#fff;
--r:12px; --r-s:8px;                                       /* radii; pills 999px; modal 16px */
--shadow:0 1px 2px rgba(16,26,46,.05),0 8px 24px -16px rgba(16,26,46,.24);
--ctl-h:42px; --ctl-bd:#d3dae7; --ring:0 0 0 3px rgba(22,35,63,.09);
--sw:264px (sidebar), 76px collapsed
```
- Sidebar bg `#f6f8fb`; table header bg `#eef2f8`, text `#1b2740`, bottom border `2px #c7d2e4`; selected row `#eef4ff`.
- **Typography:** Cairo 400–800 (AR), IBM Plex Sans 400–700 (EN / codes). Body 15px/1.6. Page title ~24px/700. Card title 16px/700. Labels 13px/700 ink. Table header 12.5px/800. Table cells 13.5–14px. Small/meta 12px. KPI number 34px/700, letter-spacing −1px.
- **Spacing:** page padding 28px 26px 56px, max-width 1500px; card padding 20px; grid gaps 10/12/16px; filter bar gap 14px 12px, padding 16px.
- **Controls:** height 42px (inputs/selects/buttons), login inputs 50px, icon buttons 40×40, radius 8px, focus = navy border + ring.

### Colour rules
Semantic colour is used sparingly: green = rental/complete/available, red = overdue/failure, amber = in progress/warning, navy = active/selected. Everything else neutral.

### RTL / numbers
- `<body dir="rtl">` default; use logical properties (`margin-inline-start`, `inset-inline-end`, `padding-inline`).
- All numbers, codes, phones, IBANs, dates in Latin digits wrapped in `.num` → `direction:ltr; unicode-bidi:isolate; font-variant-numeric:tabular-nums`. Latin inputs get `dir="ltr"`.

## Components (classes in `clickerp.css`)
- **Buttons** `.btn` 44px, radius 8, 600 weight: `.btn-primary` navy, `.btn-green`, `.btn-ghost` (white + line border). Hover darkens / border `#cdd5e1`.
- **Segmented / tabs** `.seg` (white, 1px line, radius 8, 3px padding; active button navy bg white text). `.seg.tabs` = status tabs with count badge `<b>`.
- **Status pill** `.st` (12.5px/600, 3px 10px, radius 999, 6px dot) and `.tag` with `.g/.r/.a/.n` modifiers (soft bg + strong text).
- **Stat cards** `.pipe .pg` (icon tile 38px radius 11 + big number + share bar) and `.statgrid.slim .scard.slim`. Stats blocks are **collapsible, collapsed by default** so the table is visible immediately.
- **Quick filters** `.qf` / `.statbar .sb` pills (active = navy border + navy-soft bg).
- **Filter bar** `.fbar` with `.fb` fields (min-width 190px, label above).
- **Table card** `.card.flush` > `.tw` (horizontal scroll, sticky header, min-width 760px) + `.tfoot` pager. Grouped cells `table.grp` (labelled lines: client / worker / contract data). Sticky actions column `td.act`. Stage stripe `tr.stripe` (3px coloured start edge).
- **Row actions** `.rowacts` icon buttons + overflow menu `.menu` (250px, radius 12, shadow).
- **Detail page** `.hero` (88px avatar, name, meta, actions) + tab bar + `.kvgrid .kv` key/value grid + timeline (`.tli`).
- **Modal** `.ov > .modal` (radius 16, padding 26, max-height viewport−40, scroll body). **Drawer** `.drawer` 420px for notifications.
- **Print** `.printov` A4 documents: rental contract, ZATCA tax invoice (`.ivmodal`, with QR), receipt bond. `@media print` hides shell.
- Icons: **Lucide** (`lucide@0.451.0`) — names are in the JS (`I("file-text")` etc.).

## Screens
Each module JS file renders its screens; read the template strings for exact markup, copy and columns.

| Module | File | Screens |
|---|---|---|
| Login | clickerp.js | Staff / external agent sign-in (role switch), forgot password, support modal |
| Shell + Dashboard | clickerp.js | Sidebar (flat groups: عام، العمليات، العملاء، الإدارة; Finance/HR/Settings dropdowns), topbar (crumb, search ⌘K, lang, notifications drawer, user menu), role-aware dashboard (attendance card, needs-decision, KPIs, stages, charts), reports centre |
| Contracts | clickerp-contracts.js | List with 13 stages regrouped (valid in-progress / after arrival / closed), quick filters, bulk stage advance; 7-tab details with stage timeline; edit |
| Orders | clickerp-orders.js | Type tabs, live SLA timer, details, request → contract conversion (order = intent, contract = binding) |
| CVs | clickerp-cv.js | List (stats + 11 filters + 8 cols, grid/table toggle), full & quick add, details (hero, 6 stats, media gallery, attachments), CV preview |
| CRM | clickerp-crm.js | All clients, profile, add/edit, follow-ups, site visitors, archive, polls |
| Rental | clickerp-rental-app.js, -print.js | 5 status tabs, 8-tab details, add/edit, packages & settings, invoices, extension, signing, A4 prints |
| Housing | clickerp-housing.js | Inside / outside / available for transfer / departure / deported / general; details, add/edit, living expenses |
| Transfer | clickerp-transfer.js | All / trial / transferred / failed / web requests; details, progress, invoice & settlement |
| Finance | clickerp-finance*.js | Chart of accounts tree, journal entries, bonds, sales (+ZATCA print), purchases & returns, cash/banks/cheques, inventory, financial clients/suppliers, cost centres (tree + caps), settings, ZATCA config, 23 reports with totals footers |

## Interactions & Behaviour
- Navigation: sidebar `data-go` → screen; active item navy text + green-soft badge. Sidebar search filters items. Collapse button → icon-only 76px.
- Transitions: `.15s` on hover/border/background only. No page animations.
- Tables: sticky header, horizontal scroll on narrow widths, row checkbox → bulk action bar.
- Forms: label above field, required asterisk, inline error below field in red 12px, focus ring.
- Empty state `.tempty` (icon + text, 44px vertical padding).
- Responsive breakpoints: 1400 / 1280 / 1100 / 1024 / 900 (sidebar becomes off-canvas) / 760 (grids → 1–2 columns).
- AR/EN: toggles `dir` and font family; all strings exist as `t(ar,en)` pairs in the JS → move to `helperTrans()` keys.

## State
Server-rendered Blade + existing DataTables; client state only for: active tab, filters (keep existing IDs `#statusFilter`, `#globalSearchFilter`…), stats collapsed/expanded (persist in localStorage), sidebar collapsed, language.

## Assets
- `design/assets/logo-light-bg.png`, `logo-dark-card.png` — Click logos.
- Fonts: Google Fonts Cairo + IBM Plex Sans. Icons: Lucide.
- Photos in prototype are placeholders.

## Files
- `design/Click ERP Redesign.html` — entry point (open in browser).
- `design/clickerp.css` — all styles/tokens (source of truth).
- `design/clickerp*.js` — screens per module (markup, copy, columns, mock data).
- `design/github.md` — screen → Blade file map, permission map, handoff rules.
- `blade-export/blade/` — ready-to-install CSS layer + contract stats partial with install guide.
