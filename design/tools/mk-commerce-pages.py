#!/usr/bin/env python3
"""v1.75 — يولّد صفحات التجارة cm*.html من قالب شاشة مالية (نفس الإطار)، ويحمّل ملفات assets/commerce قبل الباندل.
التشغيل من جذر المستودع:  python3 design/tools/mk-commerce-pages.py"""
import re, os, json
ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
CSS = ["cm.css", "cm-items.css", "cm-stock.css", "cm-trade.css", "cm-pos.css"]
SCRIPTS = ["cm-features.js", "cm-core.js", "cm-items.js", "cm-stock.js", "cm-trade.js", "cm-pos.js"]
PAGES = {
 "cmDash": "مبيعات اليوم", "cmItems": "المنتجات", "cmItemAdd": "منتج جديد", "cmItemCard": "كارت المنتج", "cmImport": "استيراد المنتجات من Excel",
 "cmCats": "أقسام المنتجات", "cmUnits": "الوحدات", "cmPrices": "الأسعار", "cmPriceEdit": "محرر الأسعار",
 "cmStock": "الكميات في المخزن", "cmDocs": "إدخال وإخراج بضاعة", "cmDocShow": "إدخال أو إخراج بضاعة", "cmCount": "الجرد", "cmCountRun": "شاشة العدّ", "cmStores": "قائمة المخازن",
 "cmSales": "فواتير البيع", "cmSaleAdd": "فاتورة بيع", "cmSaleShow": "فاتورة بيع", "cmPurchases": "شراء بضاعة", "cmPurchaseAdd": "فاتورة شراء",
 "cmReturns": "المرتجعات", "cmReturnAdd": "مرتجع جديد",
 "cmShifts": "ورديات الكاشير", "cmShiftShow": "تفاصيل الوردية", "cmPosInvoices": "فواتير الكاشير", "cmPosInvoiceShow": "فاتورة كاشير",
 "cmRegisters": "أجهزة الكاشير", "cmDevicePair": "ربط جهاز كاشير", "cmCashiers": "الكاشيرية", "cmPayMethods": "طرق الدفع", "cmPromos": "العروض والخصومات",
 "cmSettle": "تسوية مدى والبطاقات", "cmZatca": "ربط الأجهزة بالزكاة", "cmZatcaWizard": "ربط جهاز بالهيئة", "cmReports": "تقارير البيع والمخزون", "cmSettings": "إعدادات المخزون والحسابات",
}
src = open(os.path.join(ROOT, "finSuppliers.html"), encoding="utf-8").read()
head, rest = src.split('<main class="page">', 1)
_, tail = rest.split("  </div>\n  <div class=\"drawer-ov hidden\" data-notif-ov=\"\">", 1)
tail = "  </div>\n  <div class=\"drawer-ov hidden\" data-notif-ov=\"\">" + tail
# v1.77 — عروض الأسعار في المالية: نفس الأساس (cm-core) + ملف المالية
FIN = {"finQuotes": "عروض الأسعار", "finQuoteAdd": "عرض سعر جديد", "finQuoteShow": "تفاصيل عرض السعر"}
ALL = dict(PAGES); ALL.update(FIN)
for pid, title in ALL.items():
    css = CSS + (["../finance/fq.css"] if pid in FIN else [])
    scripts = ["cm-features.js", "cm-core.js", "../finance/fq.js"] if pid in FIN else SCRIPTS
    h = head.replace("<title>الموردون — Click ERP</title>", f"<title>{title} — Click ERP</title>")
    h = h.replace('<link rel="stylesheet" href="assets/clickerp.bundle.css">', '<link rel="stylesheet" href="assets/clickerp.bundle.css">\n' + "\n".join(f'<link rel="stylesheet" href="assets/commerce/{c}">' for c in css))
    h = re.sub(r"<!-- Screen: [^>]*-->", f"<!-- Screen: {pid} · {title} · v1.75 · الأقسام التجارية (جديد) — docs/modules/general modula/COMMERCE_POS_MASTER_PLAN.md -->", h)
    t = re.sub(r'window\.CERP_PAGE="finSuppliers";', f'window.CERP_PAGE="{pid}";', tail)
    t = t.replace('<script src="assets/clickerp.bundle.js"></script>', "\n".join(f'<script src="assets/commerce/{s}"></script>' for s in scripts) + '\n<script src="assets/clickerp.bundle.js"></script>')
    page = h + '<main class="page">\n    </main>\n' + t
    open(os.path.join(ROOT, pid + ".html"), "w", encoding="utf-8").write(page)
# screens.json
sj = os.path.join(ROOT, "screens.json")
data = json.load(open(sj, encoding="utf-8"))
lst = data if isinstance(data, list) else data.get("screens", data)
have = {x.get("id") for x in lst if isinstance(x, dict)}
for pid, title in ALL.items():
    if pid not in have:
        lst.append({"id": pid, "file": pid + ".html", "title": title})
json.dump(data, open(sj, "w", encoding="utf-8"), ensure_ascii=False, indent=2)
print(len(PAGES), "pages")
