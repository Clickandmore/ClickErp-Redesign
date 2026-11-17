/* =====================================================================
   بوابة الوكيل (المكتب الخارجي) — الإطار المشترك بنفس ماركب ثيم النظام
   (#app > aside.side > .rail + .side-head + nav.nav + .side-foot  ·  .main > header.top + main.page)
   الأنماط كلها من ../assets/clickerp.bundle.css + assets/ag.css — هذا الملف يبني الماركب فقط.

   كل صفحة فيها <body data-page="…"> و<main class="ag-content"> بالمحتوى فقط،
   وهذا الملف يلفّها بالإطار من مصدر واحد (AG_NAV) — نفس أسلوب superadmin/assets/sa.js.

   اللغة: الوكلاء غالبًا يتحدثون الإنجليزية، فزر AR/EN يعمل فعليًا:
     - يقلب dir/lang للصفحة ويحفظ الاختيار (localStorage «ag-lang» أو ?lang=en).
     - عناوين الإطار كلها من القاموس AG_T.
     - أي عنصر في الصفحة عليه data-en="…" يتبدّل نصه للإنجليزية (الأيقونات داخله تبقى).

   المقابل في ClickERP:
     routes/agent.php (agent.*) · resources/views/agent/layout.blade.php (القائمة والرأس)
     resources/lang/{ar,en}/agent.php و agent_login.php (نصوص الإطار والدخول)
   ===================================================================== */
(function () {
  'use strict';

  /* ---------- هوية الحساب (في النظام: auth('agent')->user() + agentOfficeLinks → recruitment_offices) ---------- */
  var AG_ME = {
    user: { ar: 'Rowena Villanueva', en: 'Rowena Villanueva' }, initials: 'RV',
    role: { ar: 'مديرة المكتب', en: 'Office manager' },
    office: { ar: 'PLATINUM BROTHERS', en: 'PLATINUM BROTHERS' }, officeShort: 'PB',
    city: { ar: 'مانيلا · الفلبين', en: 'Manila · Philippines' },
    /* مكتب الاستقدام الذي يعمل معه الوكيل (صاحب النسخة) */
    recruiter: { ar: 'مكتب الريادة للاستقدام', en: 'Al-Riyada Recruitment Office' }
  };

  /* ---------- مصدر القائمة الوحيد: كل مجموعة = زر في الـ rail + قائمتها في الـ side ---------- */
  var AG_NAV = [
    { id: 'workers', icon: 'id-card', label: { ar: 'العمالة', en: 'Workers' }, desc: { ar: 'السير الذاتية وإضافتها وطلبات تغيير الحالة', en: 'CVs, adding CVs and status-change requests' }, items: [
      { id: 'cvs',             href: 'cvs.html',             icon: 'contact',          label: { ar: 'السير الذاتية', en: 'CVs' }, route: 'agent.maids.index', count: 2, tone: 'red' },
      { id: 'cv-add',          href: 'cv-add.html',          icon: 'user-plus',        label: { ar: 'إضافة سيرة', en: 'Add CV' }, route: 'agent.maids.create' },
      { id: 'cv-import',       href: 'cv-import.html',       icon: 'file-spreadsheet', label: { ar: 'رفع سير من ملف', en: 'Import from file' }, route: 'agent.maids.bulk-import.create' },
      { id: 'status-requests', href: 'status-requests.html', icon: 'arrow-left-right', label: { ar: 'طلبات تغيير الحالة', en: 'Status requests' }, count: 1 }
    ]},
    { id: 'contracts', icon: 'file-text', label: { ar: 'العقود', en: 'Contracts' }, desc: { ar: 'العقود وإجراءات السفر والإرساليات والفواتير', en: 'Contracts, travel procedures, dispatches and invoices' }, items: [
      { id: 'contracts',  href: 'contracts.html',  icon: 'file-text',  label: { ar: 'العقود', en: 'Contracts' }, route: 'agent.contracts.index', count: 3 },
      { id: 'tracking',   href: 'tracking.html',   icon: 'route',      label: { ar: 'تتبع إجراءات الفلبين', en: 'Philippines tracking' }, route: 'agent.philippines-tracking.index' },
      { id: 'dispatches', href: 'dispatches.html', icon: 'send',       label: { ar: 'الإرساليات', en: 'Dispatches' }, route: 'agent.dispatches.index' },
      { id: 'invoices',   href: 'invoices.html',   icon: 'receipt',    label: { ar: 'الفواتير', en: 'Invoices' }, route: 'agent.invoices.index' }
    ]},
    { id: 'follow', icon: 'messages-square', label: { ar: 'المتابعة', en: 'Follow-up' }, desc: { ar: 'الشكاوى والإشعارات من مكتب الاستقدام', en: 'Complaints and notices from the recruitment office' }, items: [
      { id: 'complaints',    href: 'complaints.html',    icon: 'message-square-warning', label: { ar: 'الشكاوى', en: 'Complaints' }, route: 'agent.complaints.index', count: 1 },
      { id: 'notifications', href: 'notifications.html', icon: 'bell',                   label: { ar: 'الإشعارات', en: 'Notifications' }, route: 'agent.notifications.index', count: 4 }
    ]}
  ];

  /* الرئيسية: اختصارات لأكثر ما يفتحه الوكيل */
  var AG_HOME = { id: 'home', icon: 'layout-dashboard', label: { ar: 'الرئيسية', en: 'Home' }, desc: { ar: 'المطلوب منك الآن وأهم الاختصارات', en: 'What needs your action and main shortcuts' }, items: [
    { id: 'dashboard', href: 'index.html', icon: 'layout-dashboard', label: { ar: 'لوحة التحكم', en: 'Dashboard' }, route: 'agent.dashboard' },
    AG_NAV[0].items[0], AG_NAV[1].items[0], AG_NAV[1].items[1], AG_NAV[2].items[0], AG_NAV[2].items[1]
  ]};

  /* شاشات فرعية: تُنير عنصر الأب وتظهر في مسار التنقل */
  var AG_PARENT = {
    'contract':      { parent: 'contracts',  label: { ar: 'عقد رقم 10455', en: 'Contract #10455' } },
    'complaint':     { parent: 'complaints', label: { ar: 'شكوى رقم 318', en: 'Complaint #318' } },
    'complaint-new': { parent: 'complaints', label: { ar: 'شكوى جديدة', en: 'New complaint' } }
  };

  /* ---------- قاموس الإطار (من resources/lang/{ar,en}/agent.php حيث وُجد) ---------- */
  var AG_T = {
    home:      { ar: 'الرئيسية', en: 'Home' },
    dashboard: { ar: 'لوحة التحكم', en: 'Dashboard' },
    portal:    { ar: 'بوابة الوكيل', en: 'Agent Portal' },
    search:    { ar: 'ابحث بالاسم أو رقم الجواز أو رقم العقد…', en: 'Search by name, passport or contract #…' },
    collapse:  { ar: 'تصغير القائمة', en: 'Collapse menu' },
    notif:     { ar: 'الإشعارات', en: 'Notifications' },
    logout:    { ar: 'تسجيل الخروج', en: 'Logout' },
    office:    { ar: 'مكتبك', en: 'Your office' },
    recruiter: { ar: 'مكتب الاستقدام الذي تعمل معه', en: 'Recruitment office you work with' },
    agentOf:   { ar: 'وكيل لدى', en: 'Agent for' },
    site:      { ar: 'الموقع', en: 'Website' },
    done:      { ar: 'تم', en: 'Done' },
    confirm:   { ar: 'تأكيد الإجراء', en: 'Confirm action' },
    cancel:    { ar: 'إلغاء', en: 'Cancel' },
    proceed:   { ar: 'متابعة', en: 'Continue' },
    sent:      { ar: 'أُرسل الطلب إلى الإدارة للمراجعة', en: 'The request was sent to administration for review' }
  };

  var LANG = (function () {
    var q = new URLSearchParams(location.search).get('lang');
    if (q === 'en' || q === 'ar') { try { localStorage.setItem('ag-lang', q); } catch (e) {} return q; }
    try { return localStorage.getItem('ag-lang') || 'ar'; } catch (e) { return 'ar'; }
  })();
  function t(o) { return o == null ? '' : (typeof o === 'string' ? (AG_T[o] ? AG_T[o][LANG] : o) : o[LANG]); }
  window.agLang = function () { return LANG; };

  var ic = function (n, c) { return '<i data-lucide="' + n + '"' + (c ? ' class="' + c + '"' : '') + '></i>'; };

  function find(id) {
    for (var g = 0; g < AG_NAV.length; g++)
      for (var i = 0; i < AG_NAV[g].items.length; i++)
        if (AG_NAV[g].items[i].id === id) return { group: AG_NAV[g], item: AG_NAV[g].items[i] };
    return null;
  }

  function list(group, activeId) {
    return '<div class="dp-head"><div class="dp-t"><b>' + t(group.label) + '</b><small title="' + t(group.desc) + '">' + t(group.desc) + '</small></div></div>' +
      '<div class="dp-list">' + group.items.map(function (it) {
        var tail = it.count ? '<span class="badge ' + (it.tone === 'red' ? 'ag-red' : 'ag-warn') + ' num">' + it.count + '</span>' : '';
        return '<a href="' + it.href + '"' + (it.id === activeId ? ' class="on" aria-current="page"' : '') + (it.route ? ' data-route="' + it.route + '"' : '') + '>' + ic(it.icon) + '<span>' + t(it.label) + '</span>' + tail + '</a>';
      }).join('') + '</div>';
  }

  function buildShell() {
    var body = document.body;
    var page = body.getAttribute('data-page');
    var content = document.querySelector('main.ag-content');
    if (!page || !content) return;

    var sub = AG_PARENT[page];
    var activeId = sub ? sub.parent : page;
    var isHome = page === 'dashboard';
    var found = isHome ? { group: AG_HOME, item: AG_HOME.items[0] } : (find(activeId) || { group: AG_NAV[0], item: null });
    var title = isHome ? t('dashboard') : (sub ? t(sub.label) : (found.item ? t(found.item.label) : ''));

    var rail =
      '<div class="rail">' +
        '<button type="button" data-ag-rail="home" class="rl-first' + (isHome ? ' on here' : '') + '" title="' + t('dashboard') + '">' + ic('layout-dashboard') + '<small>' + t('home') + '</small></button>' +
        AG_NAV.map(function (g) {
          var on = g === found.group && !isHome;
          return '<button type="button" data-ag-rail="' + g.id + '" class="' + (on ? 'on here' : '') + '" title="' + t(g.label) + '">' + ic(g.icon) + '<small>' + t(g.label) + '</small></button>';
        }).join('') +
      '</div>';

    var side =
      '<aside class="side">' + rail +
        '<div class="side-head"><a class="sb-brand" href="index.html" title="' + t(AG_ME.office) + ' — ' + t('portal') + '"><span class="sb-logo ag-initials" role="img" aria-label="' + t(AG_ME.office) + '">' + AG_ME.officeShort + '</span><span class="sb-name"><b class="ag-latin">' + t(AG_ME.office) + '</b><small>' + t(AG_ME.city).split(' · ')[0] + ' · ' + t('agentOf') + ' ' + t(AG_ME.recruiter) + '</small></span></a></div>' +
        '<nav class="nav" data-ag-list>' + list(found.group, activeId) + '</nav>' +
        '<div class="side-foot"><span class="av">' + AG_ME.initials + '</span><div class="sf-txt"><b class="ag-latin">' + t(AG_ME.user) + '</b><span>' + t(AG_ME.role) + '</span></div><a class="icon-btn" href="login.html" title="' + t('logout') + '" style="margin-inline-start:auto;color:#8093ad">' + ic('log-out') + '</a></div>' +
      '</aside>';

    var crumb = t('portal') + ' / ' + (isHome ? t('home') : t(found.group.label) + (sub && found.item && t(found.item.label) !== t(found.group.label) ? ' / ' + t(found.item.label) : ''));
    var top =
      '<header class="top">' +
        '<button class="icon-btn" data-collapse title="' + t('collapse') + '">' + ic('panel-left') + '</button>' +
        '<div class="crumb"><span>' + crumb + '</span><b>' + title + '</b></div>' +
        '<div class="top-search"><div class="inp">' + ic('search') + '<input placeholder="' + t('search') + '"><kbd>Ctrl K</kbd></div></div>' +
        '<div class="top-right">' +
          '<button class="pill" title="' + t('office') + '">' + ic('globe') + '<span class="ag-latin">' + t(AG_ME.office) + ' · ' + t(AG_ME.city).split(' · ')[0] + '</span></button>' +
          '<button class="pill" title="' + t('recruiter') + '">' + ic('building-2') + '<span>' + t(AG_ME.recruiter) + '</span></button>' +
          '<span class="sep"></span>' +
          '<a class="icon-btn" href="notifications.html" title="' + t('notif') + '">' + ic('bell') + '<span class="dot"></span></a>' +
          '<div class="lang" data-ag-lang><button data-l="ar"' + (LANG === 'ar' ? ' class="on"' : '') + '>AR</button><button data-l="en"' + (LANG === 'en' ? ' class="on"' : '') + '>EN</button></div>' +
          '<a class="icon-btn av-btn" href="login.html" title="' + t('logout') + '"><span class="av-sm">' + AG_ME.initials + '</span></a>' +
        '</div>' +
      '</header>';

    var app = document.createElement('div');
    app.id = 'app';
    app.innerHTML = side + '<div class="main">' + top + '</div>';
    content.parentNode.insertBefore(app, content);
    content.classList.add('page');
    app.querySelector('.main').appendChild(content);

    document.title = title + ' — ' + t('portal') + ' · ' + t(AG_ME.office);

    /* الـ rail يبدّل القائمة الجانبية من غير تنقّل (مثل النظام) */
    app.querySelectorAll('[data-ag-rail]').forEach(function (b) {
      b.addEventListener('click', function () {
        var g = [AG_HOME].concat(AG_NAV).filter(function (x) { return x.id === b.getAttribute('data-ag-rail'); })[0];
        app.querySelectorAll('.rail > *').forEach(function (r) { r.classList.toggle('on', r === b); });
        app.querySelector('[data-ag-list]').innerHTML = list(g, activeId);
        app.classList.remove('collapsed');
        icons();
      });
    });
    app.querySelector('[data-collapse]').addEventListener('click', function () {
      if (window.matchMedia('(max-width: 1000px)').matches) app.classList.toggle('navopen');
      else app.classList.toggle('collapsed');
    });
    app.addEventListener('click', function (e) { if (e.target === app) app.classList.remove('navopen'); });
  }

  /* ---------- اللغة: dir + نصوص data-en ---------- */
  function applyLang() {
    var html = document.documentElement;
    html.lang = LANG; html.dir = LANG === 'en' ? 'ltr' : 'rtl'; document.body.dir = html.dir;
    document.querySelectorAll('[data-ag-lang] [data-l]').forEach(function (b) { b.classList.toggle('on', b.getAttribute('data-l') === LANG); });
    if (LANG !== 'en') return;
    document.querySelectorAll('.hm-new, .ag-new').forEach(function (el) { el.textContent = 'New'; });
    document.querySelectorAll('[data-en]').forEach(function (el) {
      var en = el.getAttribute('data-en');
      if (el.matches('input, textarea')) { el.placeholder = en; return; }
      /* بدّل أول نص فعلي فقط، حتى تبقى الأيقونات والعناصر الداخلية */
      var w = document.createTreeWalker(el, NodeFilter.SHOW_TEXT, { acceptNode: function (n) { return n.nodeValue.trim() ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT; } });
      var n = w.nextNode();
      if (n) n.nodeValue = n.nodeValue.replace(n.nodeValue.trim(), en); else el.appendChild(document.createTextNode(en));
    });
    document.querySelectorAll('[data-en-title]').forEach(function (el) { el.title = el.getAttribute('data-en-title'); });
    /* أسماء مراحل العقد بالإنجليزية كما في getContractStatus() + أسماء الأشهر */
    var STAGE = { 'عقود جديدة': 'New Contracts', 'عقود مساند': 'Msaned Contract', 'تفويض': 'Tafwed', 'التفييز': 'Taffez', 'تذكرة': 'Tazker',
      'مرحلة الوصول': 'Arrival', 'عقد مكتمل': 'Completed Contract', 'مرتجع': 'Back', 'الفلبين': 'Philippines' };
    var MON = { 'يناير': 'Jan', 'فبراير': 'Feb', 'مارس': 'Mar', 'أبريل': 'Apr', 'مايو': 'May', 'يونيو': 'Jun', 'يوليو': 'Jul', 'أغسطس': 'Aug', 'سبتمبر': 'Sep', 'أكتوبر': 'Oct', 'نوفمبر': 'Nov', 'ديسمبر': 'Dec' };
    var w2 = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT), n2, list2 = [];
    while ((n2 = w2.nextNode())) list2.push(n2);
    list2.forEach(function (tn) {
      var v = tn.nodeValue, k = v.trim();
      if (STAGE[k]) { tn.nodeValue = v.replace(k, STAGE[k]); return; }
      tn.nodeValue = v.replace(/(\d{1,2}) (يناير|فبراير|مارس|أبريل|مايو|يونيو|يوليو|أغسطس|سبتمبر|أكتوبر|نوفمبر|ديسمبر)/g, function (m, d, mo) { return d + ' ' + MON[mo]; })
        .replace(/ · (\d+) (يناير|فبراير|مارس|أبريل|مايو|يونيو|يوليو|أغسطس|سبتمبر|أكتوبر|نوفمبر|ديسمبر)/g, function (m, d, mo) { return ' · ' + d + ' ' + MON[mo]; });
    });
    /* أسهم الاتجاه */
    document.querySelectorAll('[data-lucide="arrow-right"]').forEach(function (i) { if (i.closest('.ag-back')) i.setAttribute('data-lucide', 'arrow-left'); });
    document.querySelectorAll('[data-lucide="chevron-left"]').forEach(function (i) { i.setAttribute('data-lucide', 'chevron-right'); });
    document.querySelectorAll('.btn [data-lucide="arrow-left"]').forEach(function (i) { i.setAttribute('data-lucide', 'arrow-right'); });
  }
  function setLang(l) {
    try { localStorage.setItem('ag-lang', l); } catch (e) {}
    var u = new URL(location.href); u.searchParams.delete('lang'); location.href = u.toString();
  }

  function icons() { if (window.lucide) lucide.createIcons({ attrs: { 'stroke-width': 1.8 } }); }

  /* ---------- تفاعلات المعاينة (ليست للإنتاج) ---------- */
  function toast(msg) {
    var el = document.createElement('div');
    el.className = 'ag-toast';
    el.innerHTML = ic('check-circle-2') + '<span>' + msg + '</span>';
    document.body.appendChild(el);
    icons();
    setTimeout(function () { el.remove(); }, 2400);
  }
  window.agToast = toast;

  function openDrawer(id) {
    var d = document.getElementById(id); if (!d) return;
    var s = document.createElement('div'); s.className = 'ag-scrim'; s.setAttribute('data-ag-close', '');
    document.body.appendChild(s); d.classList.add('is-open'); d._scrim = s;
  }
  function closeDrawers() {
    document.querySelectorAll('.ag-drawer.is-open').forEach(function (d) { d.classList.remove('is-open'); if (d._scrim) d._scrim.remove(); });
    document.querySelectorAll('dialog[open]').forEach(function (d) { d.close(); });
  }

  function bind() {
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { var a = document.getElementById('app'); if (a) a.classList.remove('navopen'); closeDrawers(); } });

    document.addEventListener('click', function (e) {
      var lb = e.target.closest('[data-ag-lang] button, .lang [data-l]');
      if (lb) { e.preventDefault(); if (lb.getAttribute('data-l') !== LANG) setLang(lb.getAttribute('data-l')); return; }

      var choice = e.target.closest('.ag-choice');
      if (choice) { choice.parentNode.querySelectorAll('.ag-choice').forEach(function (c) { c.classList.toggle('is-on', c === choice); }); }

      var seg = e.target.closest('.ag-seg button, .ag-chips .ag-chip');
      if (seg) { seg.parentNode.querySelectorAll('button').forEach(function (b) { b.classList.toggle('is-on', b === seg); }); }

      var tab = e.target.closest('[data-ag-tab]');
      if (tab) {
        e.preventDefault();
        var key = tab.getAttribute('data-ag-tab');
        var scope = tab.closest('[data-ag-tabs]') || document;
        scope.querySelectorAll('[data-ag-tab]').forEach(function (x) { x.classList.toggle('is-active', x === tab); x.classList.toggle('is-on', x === tab); });
        document.querySelectorAll('[data-ag-panel]').forEach(function (p) { p.hidden = p.getAttribute('data-ag-panel') !== key; });
        history.replaceState(null, '', location.pathname + location.search + '#' + key);
      }

      var pw = e.target.closest('[data-ag-pass]');
      if (pw) { var inp = pw.parentNode.querySelector('input'); inp.type = inp.type === 'password' ? 'text' : 'password'; }

      var op = e.target.closest('[data-ag-open]');
      if (op) { e.preventDefault(); openDrawer(op.getAttribute('data-ag-open')); icons(); }
      if (e.target.closest('[data-ag-close]')) { e.preventDefault(); closeDrawers(); }

      var dl = e.target.closest('[data-ag-dialog]');
      if (dl) {
        e.preventDefault();
        var d = document.getElementById(dl.getAttribute('data-ag-dialog'));
        if (d && d.showModal) {
          d.querySelectorAll('[data-ag-fill]').forEach(function (f) { var v = dl.getAttribute('data-' + f.getAttribute('data-ag-fill')); if (v != null) f.textContent = v; });
          var ty = dl.getAttribute('data-type'); if (ty) { var r = d.querySelector('input[name="type"][value="' + ty + '"]'); if (r) r.checked = true; }
          d.showModal();
        }
      }

      var done = e.target.closest('[data-ag-toast]');
      if (done) { e.preventDefault(); closeDrawers(); toast(done.getAttribute('data-ag-toast')); }
    });

    /* أي زر عليه data-ag-confirm يمر على نافذة التأكيد */
    var dlg = document.createElement('dialog');
    dlg.className = 'ag-dialog';
    dlg.innerHTML = '<form method="dialog"><div class="ag-dialog-body"><span class="ag-dialog-icon">' + ic('triangle-alert') + '</span><h2 class="ag-dialog-title">' + t('confirm') + '</h2><p class="ag-dialog-text"></p></div><div class="ag-dialog-actions"><button class="btn btn-ghost" value="cancel">' + t('cancel') + '</button><button class="btn btn-primary" value="confirm">' + t('proceed') + '</button></div></form>';
    document.body.appendChild(dlg);
    document.addEventListener('click', function (e) {
      var b = e.target.closest('[data-ag-confirm]');
      if (!b || typeof dlg.showModal !== 'function') return;
      e.preventDefault();
      dlg.querySelector('.ag-dialog-text').textContent = b.getAttribute(LANG === 'en' && b.hasAttribute('data-ag-confirm-en') ? 'data-ag-confirm-en' : 'data-ag-confirm');
      dlg.returnValue = '';
      dlg.showModal();
      dlg.addEventListener('close', function once() { dlg.removeEventListener('close', once); if (dlg.returnValue === 'confirm') toast(b.getAttribute('data-ag-done') || t('sent')); });
    });

    /* تحديد الكل + شريط الإجراء الجماعي */
    document.addEventListener('change', function (e) {
      var all = e.target.closest('[data-ag-checkall]');
      if (all) document.querySelectorAll('[data-ag-check]:not(:disabled)').forEach(function (c) { c.checked = all.checked; });
      if (all || e.target.closest('[data-ag-check]')) {
        var n = document.querySelectorAll('[data-ag-check]:checked').length;
        var bar = document.querySelector('.ag-bulkbar');
        if (bar) { bar.hidden = n === 0; var c = bar.querySelector('[data-ag-count]'); if (c) c.textContent = n; }
      }
    });

    /* قراءة بيانات السيرة من PDF — محاكاة: يملأ الحقول المعلّمة data-ag-x */
    document.addEventListener('click', function (e) {
      var x = e.target.closest('[data-ag-extract]');
      if (!x) return;
      e.preventDefault();
      var st = document.querySelector('[data-ag-extract-status]');
      if (st) st.textContent = LANG === 'en' ? 'Reading… may take a few seconds' : 'جارٍ القراءة… قد تستغرق بضع ثوانٍ';
      setTimeout(function () {
        document.querySelectorAll('[data-ag-x]').forEach(function (f) {
          if (f.tagName === 'SELECT') { f.value = f.getAttribute('data-ag-x'); } else f.value = f.getAttribute('data-ag-x');
          f.classList.add('ag-filled');
        });
        if (st) st.textContent = LANG === 'en' ? 'Data extracted successfully — 4 fields filled. Check them before saving.' : 'تم استخراج البيانات بنجاح — عُبّئت 4 حقول. راجعها قبل الحفظ.';
      }, 700);
    });

    var h = location.hash.slice(1);
    var tb = h && document.querySelector('[data-ag-tab="' + h + '"]');
    if (tb) tb.click();
  }

  applyLang();
  buildShell();
  icons();
  bind();
})();
