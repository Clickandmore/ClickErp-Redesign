# Click ERP — Design pages (HTML per screen)

Generated from `Click ERP Redesign.html`. One file per screen, all sharing one CSS and one JS bundle.

```
design/pages/
  index.html                 فهرس كل الشاشات
  login.html                 تسجيل الدخول
  <screen>.html              شاشة واحدة (59 شاشة) — markup جاهز مسبقاً
  screens.json               قائمة الشاشات (id / file / title)
  CHANGELOG.md               ما تغيّر في كل إصدار
  assets/
    clickerp.bundle.css      كل الأنماط في ملف واحد
    clickerp.bundle.js       التفاعل (تنقل، قوائم، تبويبات) — للمعاينة فقط
    image-slot.js, logo-*.png
```

## للمطورين
- افتح `index.html` في المتصفح. كل رابط في القائمة الجانبية ينقلك لملف الشاشة الخاص بها.
- كل ملف يحتوي على HTML الشاشة كاملاً داخل `#app`، فانسخ منه مباشرةً إلى Blade.
- الأنماط: استخدم `assets/clickerp.bundle.css` كما هو (يُستبدل كاملاً في كل إصدار)، ولا تعدّل عليه داخل المشروع. التعديلات الخاصة بالنظام تكون في ملف منفصل بعده.
- `clickerp.bundle.js` للمعاينة فقط وبيانات تجريبية، لا يُنقل للإنتاج.
- الأيقونات: Lucide (`<i data-lucide="...">`).
- قواعد التسليم في `docs/DESIGN-HANDOFF.md` ما زالت سارية (RTL أساسي، الحفاظ على تعبيرات Blade، `@permission`، بدون بيانات حقيقية).

## خطوات رفع إصدار جديد إلى GitHub
1. نزّل `pages.zip` من مشروع التصميم، وفكّ الضغط.
2. في المستودع `Clickandmore/ClickErp-Redesign`، اعمل فرع جديد: `design/vX.Y`.
3. استبدل محتوى `design/pages/` بالكامل بالملفات الجديدة (احذف القديم أولاً حتى تختفي الشاشات المحذوفة).
4. `git add design/pages && git commit -m "design: vX.Y" && git push`، ثم افتح Pull Request.
   - بدون git: من صفحة المستودع على GitHub ← `design/pages` ← **Add file → Upload files** واسحب الملفات.
5. راجع `CHANGELOG.md` في الـ PR لمعرفة الشاشات التي تغيّرت.

## السحب من GitHub إلى التصميم
اكتب في مشروع التصميم: **"اعمل sync"** — يتم سحب التغييرات من المستودع وتحديث الشاشات المرتبطة بها.
