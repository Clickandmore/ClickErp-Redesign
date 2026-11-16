/* =====================================================================
   Super Admin — الهيكل المشترك (sidebar + topbar) + تفاعلات المعاينة
   كل صفحة فيها <body data-page="…"> و<main class="sa-content"> بس،
   والملف ده بيلفّها بالقائمة والشريط العلوي من مصدر واحد (SA_NAV).

   إضافة شاشة جديدة:
     1) انسخ أي صفحة (مثلاً scheduler.html) وغيّر data-page والمحتوى.
     2) ضيف سطر في SA_NAV تحت المجموعة المناسبة.
     — لو الشاشة فرعية (فورم/تفاصيل) ضيفها في SA_PARENT بدل القائمة.

   المقابل في ClickERP: resources/views/admin/superadmin/partials/sidebar.blade.php ($saMainNav / $saToolNav)
   ===================================================================== */
(function () {
  'use strict';

  /* ---------- مصدر القائمة الوحيد ---------- */
  var SA_NAV = [
    { group: 'النظام', items: [
      { id: 'overview',     href: 'index.html',        icon: 'activity',         label: 'نظرة عامة', isNew: true, dot: true },
      { id: 'subscription', href: 'subscription.html', icon: 'badge-check',      label: 'العميل والاشتراك', route: 'dev-superadmin.subscription' },
      { id: 'employees',    href: 'employees.html',    icon: 'users',            label: 'الموظفون', route: 'dev-superadmin.employees' },
      { id: 'modules',      href: 'modules.html',      icon: 'layout-grid',      label: 'الموديلات', route: 'dev-superadmin.modules' },
      { id: 'designs',      href: 'designs.html',      icon: 'panels-top-left',  label: 'تصاميم لوحة التحكم', route: 'dev-superadmin.dashboard-designs' },
      { id: 'updates',      href: 'updates.html',      icon: 'refresh-cw',       label: 'تحديثات النظام', route: 'dev-superadmin.product-updates' },
      { id: 'backups',      href: 'backups.html',      icon: 'database',         label: 'النسخ الاحتياطية', route: 'dev-superadmin.database-backups' },
      { id: 'scheduler',    href: 'scheduler.html',    icon: 'clock',            label: 'المهام المجدولة', route: 'dev-superadmin.scheduler', dot: true },
      { id: 'recovery',     href: 'recovery.html',     icon: 'shield-check',     label: 'الاستعادة والفحص', route: 'dev-superadmin.recovery', count: 2 }
    ]},
    { group: 'أدوات', items: [
      { id: 'test-accounts', href: 'test-accounts.html', icon: 'smartphone',   label: 'حسابات اختبار التطبيقات', route: 'dev-superadmin.hourly-app-review-accounts.index' },
      { id: 'hourly',        href: 'hourly.html',        icon: 'hourglass',    label: 'إعدادات الساعات', route: 'dev-superadmin.hourly-rental-settings.index' },
      { id: 'moyasar',       href: 'moyasar.html',       icon: 'link',         label: 'بوابة ميسر', route: 'dev-superadmin.moyasar-settings' },
      { id: 'invoices',      href: 'invoices.html',      icon: 'file-pen',     label: 'تصحيح الفواتير', route: 'dev-superadmin.invoice-corrections.index' },
      { id: 'mcp',           href: 'mcp.html',           icon: 'plug-zap',     label: 'المساعد الذكي (MCP)', route: 'dev-superadmin.mcp.index' }
    ]}
  ];

  /* شاشات فرعية: بتنوّر عنصر الأب في القائمة وبتظهر في مسار التنقل تحته */
  var SA_PARENT = {
    'update-form': { parent: 'updates', label: 'تحديث جديد' },
    'invoice-fix': { parent: 'invoices', label: 'تعديل فاتورة' }
  };

  /* بيانات النسخة المعروضة في الشريط العلوي (في النظام: من SystemModuleManager و app()->environment()) */
  var SA_ENV = { env: 'بيئة الإنتاج', profile: 'نظام الاستقدام' };

  /* شعار Click & More (نفس partials/logo-mark.blade.php) */
  var LOGO = '<svg viewBox="0 0 100 100" aria-hidden="true"><g fill="none" stroke="#1B2A4A" stroke-linecap="round"><path d="M68.51 29.43A32 32 0 1 0 66.63 72.63" stroke-width="15"/><path d="M58.55 37.79A19 19 0 1 0 57.43 63.43" stroke-width="4"/><path d="M51.66 43.57A10 10 0 1 0 51.07 57.07" stroke-width="4"/></g><path d="M50 53L94 66L72 73L65 95Z" fill="#7AB948"/></svg>';
  window.SA_LOGO = LOGO;
  /* شعار الشركة من ثيم النظام (assets/ في جذر المستودع) */
  var LOGO_IMG = '../assets/logo-light-tight.png';

  var ic = function (n) { return '<i data-lucide="' + n + '"></i>'; };

  function findItem(id) {
    for (var g = 0; g < SA_NAV.length; g++)
      for (var i = 0; i < SA_NAV[g].items.length; i++)
        if (SA_NAV[g].items[i].id === id) return { group: SA_NAV[g].group, item: SA_NAV[g].items[i] };
    return null;
  }

  function buildShell() {
    var body = document.body;
    var page = body.getAttribute('data-page');
    var main = document.querySelector('main.sa-content');
    if (!page || !main) return;

    var sub = SA_PARENT[page];
    var activeId = sub ? sub.parent : page;
    var found = findItem(activeId);

    var nav = SA_NAV.map(function (g, gi) {
      var links = g.items.map(function (it) {
        var on = it.id === activeId;
        var tail = it.count ? '<span class="sa-nav-count num">' + it.count + '</span>' : (it.dot ? '<span class="sa-nav-dot" title="محتاج تصرّف"></span>' : '');
        return '<a class="sa-nav-link' + (on ? ' is-active' : '') + '" href="' + it.href + '"' + (on ? ' aria-current="page"' : '') + (it.route ? ' data-route="' + it.route + '"' : '') + '>' + ic(it.icon) + '<span>' + it.label + '</span>' + tail + '</a>';
      }).join('');
      return (gi ? '<p class="sa-nav-label">' + g.group + '</p>' : '') + '<nav class="sa-nav" aria-label="' + g.group + '">' + links + '</nav>';
    }).join('');

    var sidebar =
      '<aside class="sa-sidebar sa-pattern" id="saSidebar" aria-label="القائمة الرئيسية">' +
        '<div class="sa-sidebar-brand"><a class="sa-brand" href="index.html" title="Click & More — Super Admin"><img src="' + LOGO_IMG + '" alt="Click & More"><span class="sa-brand-sub">Super Admin</span></a></div>' +
        nav +
        '<div class="sa-sidebar-foot">' +
          '<a class="sa-nav-link" href="../dashboard.html">' + ic('external-link') + '<span>لوحة الإدارة</span></a>' +
          '<a class="sa-nav-link sa-nav-link--danger" href="login.html">' + ic('log-out') + '<span>خروج</span></a>' +
        '</div>' +
      '</aside><div class="sa-backdrop" data-sa-nav-close></div>';

    var title = body.getAttribute('data-title') || (document.querySelector('.sa-page-title') || {}).textContent || '';
    var crumbs = '<li><a href="index.html">الرئيسية</a></li><li>' + (found ? found.group : 'النظام') + '</li>';
    if (sub && found) crumbs += '<li><a href="' + found.item.href + '">' + found.item.label + '</a></li><li aria-current="page">' + sub.label + '</li>';
    else crumbs += '<li aria-current="page">' + (found ? found.item.label : title.trim()) + '</li>';

    var topbar =
      '<header class="sa-topbar">' +
        '<div class="sa-topbar-start"><button class="btn btn-icon sa-menu-btn" type="button" data-sa-nav-toggle aria-controls="saSidebar" aria-expanded="false" aria-label="القائمة">' + ic('menu') + '</button>' +
        '<ol class="sa-breadcrumb" aria-label="مسار التنقل">' + crumbs + '</ol></div>' +
        '<div class="sa-topbar-end"><span class="sa-badge sa-badge--env">' + ic('server') + SA_ENV.env + '</span><span class="sa-badge sa-badge--brand">' + ic('package') + SA_ENV.profile + '</span><span class="sa-avatar" title="Super Admin">SA</span></div>' +
      '</header>';

    var app = document.createElement('div');
    app.className = 'sa-app';
    app.id = 'saApp';
    app.innerHTML = sidebar + '<div class="sa-main">' + topbar + '</div>';
    main.parentNode.insertBefore(app, main);
    app.querySelector('.sa-main').appendChild(main);

    document.title = (found && !sub ? found.item.label : (sub ? sub.label : title.trim())) + ' — Super Admin · Click ERP';
  }

  /* ---------- تفاعلات المعاينة (مش للإنتاج) ---------- */
  function toast(msg) {
    var t = document.createElement('div');
    t.className = 'sa-toast';
    t.innerHTML = ic('check-circle-2') + '<span>' + msg + '</span>';
    document.body.appendChild(t);
    if (window.lucide) lucide.createIcons({ attrs: { 'stroke-width': 1.8 } });
    setTimeout(function () { t.remove(); }, 2200);
  }
  window.saToast = toast;

  function bind() {
    var app = document.getElementById('saApp');
    var toggle = document.querySelector('[data-sa-nav-toggle]');
    function setNav(open) { if (!app) return; app.classList.toggle('is-nav-open', open); if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false'); }
    if (toggle) toggle.addEventListener('click', function () { setNav(!app.classList.contains('is-nav-open')); });
    document.querySelectorAll('[data-sa-nav-close]').forEach(function (el) { el.addEventListener('click', function () { setNav(false); }); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setNav(false); });

    /* مفاتيح التشغيل */
    document.addEventListener('click', function (e) {
      var sw = e.target.closest('.sa-switch');
      if (sw && !sw.disabled && !sw.classList.contains('is-locked')) { sw.classList.toggle('is-on'); sw.setAttribute('aria-checked', sw.classList.contains('is-on')); dirty(); }

      var choice = e.target.closest('.sa-choice');
      if (choice) { var grp = choice.parentNode; grp.querySelectorAll('.sa-choice').forEach(function (c) { c.classList.toggle('is-on', c === choice); }); }

      var seg = e.target.closest('.sa-seg button, .sa-chips .sa-chip');
      if (seg) { seg.parentNode.querySelectorAll('button').forEach(function (b) { b.classList.toggle('is-on', b === seg); }); }

      var tab = e.target.closest('[data-sa-tab]');
      if (tab) {
        e.preventDefault();
        var key = tab.getAttribute('data-sa-tab');
        tab.closest('.sa-tabs').querySelectorAll('[data-sa-tab]').forEach(function (t) { t.classList.toggle('is-active', t === tab); });
        document.querySelectorAll('[data-sa-panel]').forEach(function (p) { p.hidden = p.getAttribute('data-sa-panel') !== key; });
        history.replaceState(null, '', '#' + key);
      }

      var pw = e.target.closest('[data-sa-pass]');
      if (pw) { var inp = pw.parentNode.querySelector('input'); inp.type = inp.type === 'password' ? 'text' : 'password'; }

      var done = e.target.closest('[data-sa-toast]');
      if (done) { e.preventDefault(); toast(done.getAttribute('data-sa-toast')); }
    });

    /* أي زر عليه data-sa-confirm بيعدي على نافذة التأكيد (نفس سلوك layouts/app.blade.php) */
    var dlg = document.createElement('dialog');
    dlg.className = 'sa-dialog';
    dlg.innerHTML = '<form method="dialog"><div class="sa-dialog-body"><span class="sa-dialog-icon">' + ic('triangle-alert') + '</span><h2 class="sa-dialog-title">تأكيد الإجراء</h2><p class="sa-dialog-text"></p></div><div class="sa-dialog-actions"><button class="btn" value="cancel">إلغاء</button><button class="btn btn-primary" value="confirm">متابعة</button></div></form>';
    document.body.appendChild(dlg);
    document.addEventListener('click', function (e) {
      var b = e.target.closest('[data-sa-confirm]');
      if (!b || typeof dlg.showModal !== 'function') return;
      e.preventDefault();
      dlg.querySelector('.sa-dialog-text').textContent = b.getAttribute('data-sa-confirm');
      dlg.returnValue = '';
      dlg.showModal();
      dlg.addEventListener('close', function once() { dlg.removeEventListener('close', once); if (dlg.returnValue === 'confirm') toast('تم — العملية اتسجلت في سجل Super Admin'); });
    });

    /* شريط «تغييرات غير محفوظة» يظهر بعد أول تعديل */
    var bar = document.querySelector('.sa-savebar');
    function dirty() { if (bar) bar.hidden = false; }
    document.querySelectorAll('.sa-content input, .sa-content select, .sa-content textarea').forEach(function (el) { el.addEventListener('input', dirty); el.addEventListener('change', dirty); });

    /* فتح التبويب من الرابط (#tab) */
    var h = location.hash.slice(1);
    var t = h && document.querySelector('[data-sa-tab="' + h + '"]');
    if (t) t.click();
  }

  buildShell();
  if (window.lucide) lucide.createIcons({ attrs: { 'stroke-width': 1.8 } });
  bind();
})();
