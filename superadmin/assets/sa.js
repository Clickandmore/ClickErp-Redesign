/* =====================================================================
   Super Admin — الإطار المشترك بنفس ماركب ثيم النظام
   (#app > aside.side > .rail + .side-head + nav.nav + .side-foot  ·  .main > header.top + main.page)
   الأنماط كلها من ../assets/clickerp.bundle.css — الملف ده بيبني الماركب بس.

   كل صفحة فيها <body data-page="…"> و<main class="sa-content"> بالمحتوى بس،
   والملف ده بيلفّها بالإطار من مصدر واحد (SA_NAV).

   إضافة شاشة جديدة:
     1) انسخ أقرب صفحة (مثلاً scheduler.html) وغيّر data-page والمحتوى.
     2) ضيف سطر في SA_NAV تحت المجموعة المناسبة.
     — شاشة فرعية (فورم/تفاصيل)؟ ضيفها في SA_PARENT بدل القائمة.

   المقابل في ClickERP: resources/views/admin/superadmin/partials/sidebar.blade.php ($saMainNav / $saToolNav)
   ===================================================================== */
(function () {
  'use strict';

  /* ---------- مصدر القائمة الوحيد: كل مجموعة = زر في الـ rail + قائمتها في الـ side ---------- */
  var SA_NAV = [
    { id: 'system', icon: 'server-cog', label: 'النظام', desc: 'العميل والاشتراك والموديلات والتشغيل', items: [
      { id: 'overview',     href: 'index.html',        icon: 'activity',        label: 'نظرة عامة', isNew: true },
      { id: 'subscription', href: 'subscription.html', icon: 'badge-check',     label: 'العميل والاشتراك', route: 'dev-superadmin.subscription' },
      { id: 'employees',    href: 'employees.html',    icon: 'users',           label: 'الموظفون', route: 'dev-superadmin.employees' },
      { id: 'modules',      href: 'modules.html',      icon: 'layout-grid',     label: 'الموديلات', route: 'dev-superadmin.modules' },
      { id: 'designs',      href: 'designs.html',      icon: 'panels-top-left', label: 'تصاميم لوحة التحكم', route: 'dev-superadmin.dashboard-designs' },
      { id: 'updates',      href: 'updates.html',      icon: 'refresh-cw',      label: 'تحديثات النظام', route: 'dev-superadmin.product-updates' },
      { id: 'backups',      href: 'backups.html',      icon: 'database',        label: 'النسخ الاحتياطية', route: 'dev-superadmin.database-backups' },
      { id: 'scheduler',    href: 'scheduler.html',    icon: 'clock',           label: 'المهام المجدولة', route: 'dev-superadmin.scheduler', alert: 'Scheduler متوقف' },
      { id: 'recovery',     href: 'recovery.html',     icon: 'shield-check',    label: 'الاستعادة والفحص', route: 'dev-superadmin.recovery', count: 2 }
    ]},
    { id: 'tools', icon: 'wrench', label: 'الأدوات', desc: 'أدوات الدعم والربط والتصحيح', items: [
      { id: 'test-accounts', href: 'test-accounts.html', icon: 'smartphone', label: 'حسابات اختبار التطبيقات', route: 'dev-superadmin.hourly-app-review-accounts.index' },
      { id: 'hourly',        href: 'hourly.html',        icon: 'hourglass',  label: 'إعدادات الساعات', route: 'dev-superadmin.hourly-rental-settings.index' },
      { id: 'moyasar',       href: 'moyasar.html',       icon: 'credit-card', label: 'بوابة ميسر', route: 'dev-superadmin.moyasar-settings' },
      { id: 'invoices',      href: 'invoices.html',      icon: 'file-pen',   label: 'تصحيح الفواتير', route: 'dev-superadmin.invoice-corrections.index' },
      { id: 'mcp',           href: 'mcp.html',           icon: 'sparkles',   label: 'المساعد الذكي (MCP)', route: 'dev-superadmin.mcp.index' }
    ]}
  ];

  /* شاشات فرعية: بتنوّر عنصر الأب وبتظهر في مسار التنقل */
  var SA_PARENT = {
    'update-form': { parent: 'updates', label: 'تحديث جديد' },
    'invoice-fix': { parent: 'invoices', label: 'تعديل فاتورة' }
  };

  /* بيانات النسخة (في النظام: app()->environment() و SystemModuleManager::productProfileKey()) */
  var SA_ENV = { env: 'بيئة الإنتاج', profile: 'نظام الاستقدام', client: 'مكتب الريادة للاستقدام', user: 'clickdev' };

  /* شعار الشركة من ثيم النظام */
  var LOGO_IMG = '../assets/logo-light-tight.png';

  var ic = function (n, c) { return '<i data-lucide="' + n + '"' + (c ? ' class="' + c + '"' : '') + '></i>'; };

  function find(id) {
    for (var g = 0; g < SA_NAV.length; g++)
      for (var i = 0; i < SA_NAV[g].items.length; i++)
        if (SA_NAV[g].items[i].id === id) return { group: SA_NAV[g], item: SA_NAV[g].items[i] };
    return null;
  }

  function list(group, activeId) {
    return '<div class="dp-head"><div class="dp-t"><b>' + group.label + '</b><small title="' + group.desc + '">' + group.desc + '</small></div></div>' +
      '<div class="dp-list">' + group.items.map(function (it) {
        var tail = it.count ? '<span class="badge sa-warn num">' + it.count + '</span>' : (it.alert ? '<span class="nal r dot" title="' + it.alert + '"></span>' : '');
        return '<a href="' + it.href + '"' + (it.id === activeId ? ' class="on" aria-current="page"' : '') + (it.route ? ' data-route="' + it.route + '"' : '') + '>' + ic(it.icon) + '<span>' + it.label + '</span>' + tail + '</a>';
      }).join('') + '</div>';
  }

  function buildShell() {
    var body = document.body;
    var page = body.getAttribute('data-page');
    var content = document.querySelector('main.sa-content');
    if (!page || !content) return;

    var sub = SA_PARENT[page];
    var activeId = sub ? sub.parent : page;
    var found = find(activeId) || { group: SA_NAV[0], item: null };
    var title = sub ? sub.label : (found.item ? found.item.label : '');

    var rail =
      '<div class="rail">' +
        '<a href="index.html" class="rl-first' + (page === 'overview' ? ' on here' : '') + '" title="نظرة عامة">' + ic('layout-dashboard') + '<small>الرئيسية</small></a>' +
        SA_NAV.map(function (g) {
          var on = g === found.group && page !== 'overview';
          return '<button type="button" data-sa-rail="' + g.id + '" class="' + (on ? 'on here' : '') + '" title="' + g.label + '">' + ic(g.icon) + '<small>' + g.label + '</small></button>';
        }).join('') +
      '</div>';

    var side =
      '<aside class="side">' + rail +
        '<div class="side-head"><a class="sb-brand" href="index.html" title="Click & More — Super Admin"><span class="sb-logo" role="img" aria-label="Click & More"><img src="' + LOGO_IMG + '" alt=""></span><span class="sb-name"><b>Click & More</b><small>لوحة Super Admin</small></span></a></div>' +
        '<nav class="nav" data-sa-list>' + list(found.group, activeId) + '</nav>' +
        '<div class="side-foot"><span class="av">SA</span><div class="sf-txt"><b>Super Admin</b><span>' + SA_ENV.user + ' · ' + SA_ENV.env + '</span></div><a class="icon-btn" href="login.html" title="خروج" style="margin-inline-start:auto;color:#8093ad">' + ic('log-out') + '</a></div>' +
      '</aside>';

    var crumb = 'Super Admin / ' + found.group.label + (sub && found.item ? ' / ' + found.item.label : '');
    var top =
      '<header class="top">' +
        '<button class="icon-btn" data-collapse title="تصغير القائمة">' + ic('panel-left') + '</button>' +
        '<div class="crumb"><span>' + crumb + '</span><b>' + title + '</b></div>' +
        '<div class="top-search"><div class="inp">' + ic('search') + '<input placeholder="ابحث في Super Admin… موظف، فاتورة، إعداد"><kbd>Ctrl K</kbd></div></div>' +
        '<div class="top-right">' +
          '<button class="pill" title="العميل">' + ic('building-2') + '<span>' + SA_ENV.client + '</span></button>' +
          '<button class="pill" title="البيئة · نوع البرنامج">' + ic('server') + '<span>' + SA_ENV.env + ' · ' + SA_ENV.profile + '</span></button>' +
          '<span class="sep"></span>' +
          '<a class="icon-btn" href="../dashboard.html" title="لوحة الإدارة">' + ic('external-link') + '</a>' +
          '<button class="icon-btn" title="التنبيهات">' + ic('bell') + '<span class="dot"></span></button>' +
          '<div class="lang"><button class="on">AR</button><button>EN</button></div>' +
          '<a class="icon-btn av-btn" href="login.html" title="خروج"><span class="av-sm">SA</span></a>' +
        '</div>' +
      '</header>';

    var app = document.createElement('div');
    app.id = 'app';
    app.innerHTML = side + '<div class="main">' + top + '</div>';
    content.parentNode.insertBefore(app, content);
    content.classList.add('page');
    app.querySelector('.main').appendChild(content);

    document.title = title + ' — Super Admin · Click ERP';

    /* الـ rail بيبدّل القائمة الجانبية من غير تنقّل (زي النظام) */
    app.querySelectorAll('[data-sa-rail]').forEach(function (b) {
      b.addEventListener('click', function () {
        var g = SA_NAV.filter(function (x) { return x.id === b.getAttribute('data-sa-rail'); })[0];
        app.querySelectorAll('.rail > *').forEach(function (r) { r.classList.toggle('on', r === b); });
        app.querySelector('[data-sa-list]').innerHTML = list(g, activeId);
        app.classList.remove('collapsed');
        icons();
      });
    });
    /* تصغير القائمة على الكمبيوتر، وفتحها كدرج على الموبايل */
    app.querySelector('[data-collapse]').addEventListener('click', function () {
      if (window.matchMedia('(max-width: 1000px)').matches) app.classList.toggle('navopen');
      else app.classList.toggle('collapsed');
    });
    app.addEventListener('click', function (e) { if (e.target === app) app.classList.remove('navopen'); });
  }

  function icons() { if (window.lucide) lucide.createIcons({ attrs: { 'stroke-width': 1.8 } }); }

  /* ---------- تفاعلات المعاينة (مش للإنتاج) ---------- */
  function toast(msg) {
    var t = document.createElement('div');
    t.className = 'sa-toast';
    t.innerHTML = ic('check-circle-2') + '<span>' + msg + '</span>';
    document.body.appendChild(t);
    icons();
    setTimeout(function () { t.remove(); }, 2200);
  }
  window.saToast = toast;

  function bind() {
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { var a = document.getElementById('app'); if (a) a.classList.remove('navopen'); } });

    document.addEventListener('click', function (e) {
      var sw = e.target.closest('.sa-switch');
      if (sw && !sw.disabled && !sw.classList.contains('is-locked')) { sw.classList.toggle('is-on'); sw.setAttribute('aria-checked', sw.classList.contains('is-on')); dirty(); }

      var choice = e.target.closest('.sa-choice');
      if (choice) { choice.parentNode.querySelectorAll('.sa-choice').forEach(function (c) { c.classList.toggle('is-on', c === choice); }); }

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
    dlg.innerHTML = '<form method="dialog"><div class="sa-dialog-body"><span class="sa-dialog-icon">' + ic('triangle-alert') + '</span><h2 class="sa-dialog-title">تأكيد الإجراء</h2><p class="sa-dialog-text"></p></div><div class="sa-dialog-actions"><button class="btn btn-ghost" value="cancel">إلغاء</button><button class="btn btn-primary" value="confirm">متابعة</button></div></form>';
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
  icons();
  bind();
})();
