#!/usr/bin/env python3
"""v1.75 — يولّد صفحات التجارة cm*.html من قالب شاشة مالية (نفس الإطار)، ويحمّل ملفات assets/commerce قبل الباندل.
التشغيل من جذر المستودع:  python3 design/tools/mk-commerce-pages.py"""
import re, os, json
ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
CSS = ["cm.css", "cm-items.css", "cm-stock.css", "cm-trade.css", "cm-pos.css"]
SCRIPTS = ["cm-features.js", "cm-core.js", "cm-items.js", "cm-stock.js", "cm-trade.js", "cm-pos.js"]
PAGES = {
 "cmDash": "لوحة التجارة اليوم", "cmItems": "الأصناف", "cmItemAdd": "صنف جديد", "cmItemCard": "كارت الصنف", "cmImport": "استيراد الأصناف من Excel",
 "cmCats": "التصنيفات", "cmUnits": "الوحدات", "cmPrices": "قوائم الأسعار", "cmPriceEdit": "محرر الأسعار",
 "cmStock": "أرصدة المخزون", "cmDocs": "مستندات المخزون", "cmDocShow": "مستند مخزون", "cmCount": "الجرد", "cmCountRun": "شاشة العدّ", "cmStores": "المخازن",
 "cmSales": "فواتير البيع", "cmSaleAdd": "فاتورة بيع جديدة", "cmSaleShow": "فاتورة بيع", "cmPurchases": "فواتير الشراء", "cmPurchaseAdd": "فاتورة شراء جديدة",
 "cmReturns": "المرتجعات", "cmReturnAdd": "مرتجع جديد",
 "cmShifts": "الورديات", "cmShiftShow": "تفاصيل الوردية", "cmPosInvoices": "فواتير نقاط البيع", "cmPosInvoiceShow": "فاتورة نقطة بيع",
 "cmRegisters": "نقاط البيع والأجهزة", "cmDevicePair": "ربط جهاز كاشير", "cmCashiers": "الكاشيرية", "cmPayMethods": "طرق الدفع", "cmPromos": "العروض",
 "cmSettle": "تسويات البطاقات", "cmZatca": "وحدات ZATCA للأجهزة", "cmZatcaWizard": "ربط جهاز بالهيئة", "cmReports": "تقارير التجارة", "cmSettings": "إعدادات التجارة والحسابات",
}
src = open(os.path.join(ROOT, "finSuppliers.html"), encoding="utf-8").read()
head, rest = src.split('<main class="page">', 1)
_, tail = rest.split("  </div>\n  <div class=\"drawer-ov hidden\" data-notif-ov=\"\">", 1)
tail = "  </div>\n  <div class=\"drawer-ov hidden\" data-notif-ov=\"\">" + tail
for pid, title in PAGES.items():
    h = head.replace("<title>الموردون — Click ERP</title>", f"<title>{title} — Click ERP</title>")
    h = h.replace('<link rel="stylesheet" href="assets/clickerp.bundle.css">', '<link rel="stylesheet" href="assets/clickerp.bundle.css">\n' + "\n".join(f'<link rel="stylesheet" href="assets/commerce/{c}">' for c in CSS))
    h = re.sub(r"<!-- Screen: [^>]*-->", f"<!-- Screen: {pid} · {title} · v1.75 · التجارة (جديد) — docs/modules/general modula/COMMERCE_POS_MASTER_PLAN.md -->", h)
    t = re.sub(r'window\.CERP_PAGE="finSuppliers";', f'window.CERP_PAGE="{pid}";', tail)
    t = t.replace('<script src="assets/clickerp.bundle.js"></script>', "\n".join(f'<script src="assets/commerce/{s}"></script>' for s in SCRIPTS) + '\n<script src="assets/clickerp.bundle.js"></script>')
    page = h + '<main class="page">\n    </main>\n' + t
    open(os.path.join(ROOT, pid + ".html"), "w", encoding="utf-8").write(page)
# screens.json
sj = os.path.join(ROOT, "screens.json")
data = json.load(open(sj, encoding="utf-8"))
lst = data if isinstance(data, list) else data.get("screens", data)
have = {x.get("id") for x in lst if isinstance(x, dict)}
for pid, title in PAGES.items():
    if pid not in have:
        lst.append({"id": pid, "file": pid + ".html", "title": title})
json.dump(data, open(sj, "w", encoding="utf-8"), ensure_ascii=False, indent=2)
print(len(PAGES), "pages")
