@extends('admin.layout.indexs.index')

@section('page-title', 'زوار الموقع')

@section('style')
<style>
    :root{--visitors-bg:#0d101a;--visitors-panel:#141925;--visitors-muted:#8d96a8;--visitors-blue:#347fef;--visitors-cyan:#20c5d8;--visitors-amber:#f3ad1b;--visitors-pink:#ee4776}
    .visitors-page{min-height:calc(100vh - 120px);background:var(--visitors-bg);color:#edf2fa;border-radius:20px;overflow:hidden;position:relative}
    .visitors-toolbar{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:18px 22px;border-bottom:1px solid rgba(255,255,255,.08);background:#111521}
    .visitors-toolbar h3{color:#fff}.visitors-toolbar .text-muted{color:var(--visitors-muted)!important}
    .visitors-actions{display:flex;align-items:center;gap:8px}.visitors-icon-btn{width:40px;height:40px;border-radius:11px;border:1px solid rgba(255,255,255,.1);background:#181e2b;color:#aab3c2}
    .visitors-icon-btn.active{background:#263143;color:#fff;border-color:#40516b}
    .visitors-layout{display:grid;grid-template-columns:240px minmax(0,1fr);min-height:760px}
    .visitors-sidebar{padding:24px 20px;border-left:1px solid rgba(255,255,255,.08);background:#10141f}
    .online-total{font-size:36px;font-weight:800;color:#fff;line-height:1}.online-label{color:#aab3c2;margin-top:8px}
    .online-pulse{display:inline-block;width:10px;height:10px;border-radius:50%;background:#37d768;box-shadow:0 0 0 6px rgba(55,215,104,.13);margin-left:10px}
    .visitor-search{margin-top:25px;background:#181e2b;border:1px solid #293244!important;color:#fff!important}.visitor-search::placeholder{color:#778196}
    .visitor-legend{margin-top:28px}.visitor-legend-title{font-size:13px;color:#747f93;margin-bottom:13px}.legend-row{display:flex;align-items:center;gap:9px;color:#aab3c2;font-size:13px;margin-bottom:12px}
    .legend-dot{width:9px;height:9px;border-radius:50%}.legend-dot.pink{background:var(--visitors-pink)}.legend-dot.amber{background:var(--visitors-amber)}.legend-dot.cyan{background:var(--visitors-cyan)}.legend-dot.blue{background:var(--visitors-blue)}
    .visitors-stage{position:relative;min-height:760px;overflow:hidden;background:radial-gradient(circle at 50% 50%,rgba(42,52,75,.17),transparent 62%)}
    .radar{position:absolute;width:min(700px,82%);aspect-ratio:1;left:50%;top:50%;transform:translate(-50%,-50%)}
    .radar-ring{position:absolute;border:1.5px solid;border-radius:50%;left:50%;top:50%;transform:translate(-50%,-50%);pointer-events:none}
    .radar-ring.r1{width:25%;height:25%;border-color:var(--visitors-pink)}.radar-ring.r2{width:50%;height:50%;border-color:var(--visitors-amber)}.radar-ring.r3{width:75%;height:75%;border-color:var(--visitors-cyan)}.radar-ring.r4{width:100%;height:100%;border-color:var(--visitors-blue)}
    .radar-center{position:absolute;width:92px;height:92px;border-radius:50%;left:50%;top:50%;transform:translate(-50%,-50%);display:flex;align-items:center;justify-content:center;background:linear-gradient(145deg,#24334b,#111824);border:2px solid #33465f;box-shadow:0 0 36px rgba(32,197,216,.16)}
    .radar-center i{font-size:36px;color:#52bad3}
    .visitor-node{position:absolute;transform:translate(-50%,-50%);border:0;background:transparent;color:#fff;padding:0;cursor:pointer;z-index:3;text-align:center}
    .visitor-node:hover,.visitor-node:focus{z-index:8}.visitor-node:hover .visitor-avatar{transform:scale(1.1);border-color:#52bad3}
    .visitor-time{font-size:12px;color:#9da6b7;margin-bottom:3px;white-space:nowrap}
    .visitor-node-main{display:flex;align-items:center;direction:ltr}
    .visitor-tag{height:28px;min-width:78px;max-width:112px;padding:4px 10px;background:#354157;color:#d6dbe5;clip-path:polygon(0 0,88% 0,100% 50%,88% 100%,0 100%);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px}
    .visitor-avatar{width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#172031;border:3px solid #38516c;color:#98a5b8;font-size:20px;transition:.2s}
    .visitor-status{position:absolute;width:13px;height:13px;border-radius:50%;background:#38da69;border:2px solid #111724;right:0;bottom:1px}.visitor-message-badge{position:absolute;left:0;top:17px;min-width:19px;height:19px;border-radius:10px;background:#e63347;color:#fff;font-size:10px;line-height:19px}
    .visitors-empty{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);text-align:center;color:#778196;z-index:4}.visitors-empty i{font-size:54px;margin-bottom:15px;display:block}
    .visitors-list{display:none;padding:25px;overflow:auto}.visitors-list.active{display:block}.visitors-stage.hidden{display:none}
    .visitors-list .table{color:#dce2ec}.visitors-list .table>:not(caption)>*>*{border-color:#283142;background:transparent}.visitors-list .table-hover>tbody>tr:hover>*{color:#fff;background:#171d29}
    .visitors-sound-btn{border:1px solid #34516a;background:#172638;color:#7fd7e6;border-radius:10px;padding:9px 13px;font-size:13px}.visitors-sound-btn.enabled{background:#1763a6;color:#fff;border-color:#287fc8}
    .visitor-tabs{display:flex;gap:7px;padding:12px 20px;background:#0f141f;border-bottom:1px solid rgba(255,255,255,.08);overflow-x:auto}
    .visitor-tab{border:0;border-radius:9px;padding:9px 16px;background:transparent;color:#8f9aad;white-space:nowrap}.visitor-tab.active{background:#1d2a3d;color:#fff}.visitor-tab i{margin-left:6px}
    .visitor-tab-panel{display:none}.visitor-tab-panel.active{display:block}
    .records-panel{min-height:700px;padding:24px;background:#10141f}.records-header{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-bottom:20px}.records-title{color:#fff;margin:0}.records-filters{display:flex;gap:8px}
    .records-input,.records-select{background:#171e2b!important;border-color:#2b3547!important;color:#dce3ed!important}
    .records-table-wrap{border:1px solid #242e3e;border-radius:14px;overflow:hidden;background:#111722}.records-table{color:#dce3ed;margin:0}.records-table>:not(caption)>*>*{background:transparent;border-color:#273142;padding:14px}.records-table thead{background:#171e2b;color:#8f9bad}.records-table tbody tr:hover{background:#161d29}
    .record-status{display:inline-flex;align-items:center;gap:7px;padding:5px 9px;border-radius:14px;font-size:11px}.record-status.online{background:rgba(46,204,113,.12);color:#43dc82}.record-status.offline{background:rgba(143,154,173,.12);color:#929cad}
    .visitor-alert-toast{position:fixed;top:90px;left:24px;z-index:2200;display:none;align-items:center;gap:12px;min-width:280px;padding:14px 17px;border-radius:13px;background:#172638;color:#fff;border:1px solid #2e789c;box-shadow:0 15px 45px rgba(0,0,0,.3)}.visitor-alert-toast.show{display:flex;animation:visitorToastIn .25s ease}.visitor-alert-toast i{font-size:22px;color:#45d5ec}
    @keyframes visitorToastIn{from{opacity:0;transform:translateY(-12px)}to{opacity:1;transform:translateY(0)}}
    .visitor-detail-modal .modal-dialog{max-width:1180px}.visitor-detail-modal .modal-content{background:#121722;color:#e9eef7;border:1px solid #252d3d;border-radius:22px;overflow:hidden}
    .visitor-detail-modal .modal-header{padding:20px 24px;border-color:#2a3242;background:linear-gradient(135deg,#171e2c,#121722)}.visitor-detail-modal .btn-close{filter:invert(1);opacity:.8}
    .visitor-profile{display:flex;align-items:center;gap:14px}.detail-avatar{width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#172337;border:3px solid #345271;color:#9ca9ba;font-size:27px;position:relative}.detail-avatar:after{content:"";position:absolute;width:16px;height:16px;background:#39d968;border:3px solid #121722;border-radius:50%;bottom:0;right:0}
    .visitor-tech{color:#8d96a8;font-size:13px;margin-top:5px}.visitor-tech span{margin-left:12px}
    .score-wrap{margin-inline-start:auto;text-align:center}.score-number{font-size:25px;font-weight:800;color:#54c7df}.score-label{font-size:11px;color:#7f899b}
    .detail-tags{padding:12px 24px;border-bottom:1px solid #2a3242}.detail-tag{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border:1px solid #343d4e;border-radius:18px;color:#aab3c1;font-size:12px;margin-left:6px}
    .visitor-detail-body{padding:22px}.detail-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}.detail-card{min-height:108px;padding:16px;border-radius:10px;background:#0d111a;border:1px solid #1e2634;text-align:center}.detail-card i{font-size:24px;color:#7f899b;margin-bottom:10px}.detail-card-value{font-size:14px;color:#e0e5ed;overflow-wrap:anywhere}.detail-card-label{font-size:11px;color:#6f788a;margin-top:5px}
    .detail-lower{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px}.detail-panel{background:#0e131d;border:1px solid #222b3a;border-radius:13px;overflow:hidden}.detail-panel-title{padding:13px 16px;border-bottom:1px solid #252e3d;font-weight:700}
    .visitor-timeline{height:330px;overflow:auto;padding:16px}.timeline-item{position:relative;padding:0 28px 18px 0;border-right:1px solid #394253}.timeline-item:before{content:"";position:absolute;right:-6px;top:3px;width:11px;height:11px;border-radius:50%;background:#111722;border:2px solid #8a95a7}.timeline-time{font-size:11px;color:#778195}.timeline-title{font-size:13px;margin-top:3px}.timeline-url{font-size:11px;color:#478fe9;direction:ltr;text-align:right;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .detail-chat-messages{height:270px;overflow:auto;padding:15px}.detail-chat-row{display:flex;margin-bottom:9px}.detail-chat-row.visitor{justify-content:flex-start}.detail-chat-row.admin{justify-content:flex-end}.detail-chat-bubble{max-width:82%;padding:8px 11px;border-radius:12px;white-space:pre-wrap;overflow-wrap:anywhere;font-size:13px}.detail-chat-row.visitor .detail-chat-bubble{background:#202838}.detail-chat-row.admin .detail-chat-bubble{background:#277ccd;color:#fff}
    .detail-chat-form{display:flex;gap:8px;padding:12px;border-top:1px solid #252e3d}.detail-chat-input{background:#151c29!important;border-color:#303a4d!important;color:#fff!important}
    .proactive-btn{background:#2e85d5;border:0;color:#fff;border-radius:8px;padding:10px 16px}
    .tracking-panel{min-height:700px;padding:28px;background:#0f141f}.tracking-hero{display:flex;justify-content:space-between;align-items:center;gap:20px;padding:24px;border:1px solid #273247;border-radius:18px;background:linear-gradient(135deg,#182338,#101722);margin-bottom:22px}.tracking-hero-icon{width:62px;height:62px;border-radius:16px;display:flex;align-items:center;justify-content:center;background:rgba(52,127,239,.15);color:#5d9cff;font-size:25px}.tracking-hero-copy{display:flex;align-items:center;gap:16px}.tracking-hero h4{margin:0;color:#fff}.tracking-hero p{margin:6px 0 0;color:#929db0}
    .tracking-summary{display:flex;gap:12px}.tracking-summary-item{min-width:105px;padding:12px 16px;border-radius:13px;background:#0d1420;border:1px solid #263247;text-align:center}.tracking-summary-item strong{display:block;font-size:22px;color:#fff}.tracking-summary-item span{font-size:11px;color:#8995a9}
    .tracking-create{padding:20px;border:1px solid #293449;border-radius:16px;background:#131a27;margin-bottom:24px}.tracking-section-title{display:flex;align-items:center;gap:10px;color:#fff;margin-bottom:17px}.tracking-section-title i{color:#53b8d7}
    .tracking-form .form-control,.tracking-form .form-select{background:#0e1521;border-color:#303c51;color:#fff}.tracking-form .form-control::placeholder{color:#657187}.tracking-form .form-label{color:#aeb8c8;font-size:12px;margin-bottom:6px}
    .tracking-sites-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.tracking-site-card{background:#131a27;border:1px solid #28354a;border-radius:18px;overflow:hidden;box-shadow:0 12px 25px rgba(0,0,0,.12)}.tracking-site-head{display:flex;align-items:center;gap:12px;padding:17px 18px;border-bottom:1px solid #273247}.tracking-site-logo{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:#1c2a40;color:#61a1ff}.tracking-site-title{min-width:0;flex:1}.tracking-site-title strong{display:block;color:#fff}.tracking-site-title small{color:#7f8ba0}.tracking-status{padding:5px 9px;border-radius:20px;font-size:11px}.tracking-status.active{background:rgba(45,204,113,.13);color:#43db83}.tracking-status.inactive{background:rgba(143,154,173,.13);color:#929cad}
    .tracking-site-body{padding:18px}.domain-chips{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:15px}.domain-chip{direction:ltr;padding:5px 9px;border-radius:8px;background:#0e1521;border:1px solid #2a374c;color:#aeb9ca;font-size:11px}.embed-label{display:flex;justify-content:space-between;color:#8894a8;font-size:11px;margin-bottom:7px}.embed-code-wrap{position:relative}.embed-code{width:100%;min-height:78px;padding:12px 13px 44px!important;border:1px solid #2d3b51!important;border-radius:12px!important;background:#0a1019!important;color:#7fd7e6!important;direction:ltr;text-align:left;font-family:monospace;font-size:11px;resize:none}.copy-embed-code{position:absolute;bottom:8px;left:8px;border-radius:8px}
    .tracking-config{border-top:1px solid #273247}.tracking-config summary{padding:13px 18px;color:#93a0b4;cursor:pointer;list-style:none}.tracking-config summary::-webkit-details-marker{display:none}.tracking-config summary:after{content:"⌄";float:left}.tracking-config[open] summary:after{content:"⌃"}.tracking-config-form{padding:5px 18px 18px}.tracking-danger{padding:0 18px 16px}.tracking-empty{grid-column:1/-1;padding:55px 20px;text-align:center;border:1px dashed #31405a;border-radius:18px;color:#7f8ba0}.tracking-empty i{display:block;font-size:42px;margin-bottom:13px;color:#3f526d}
    @media(max-width:991px){.visitors-layout{grid-template-columns:1fr}.visitors-sidebar{border-left:0;border-bottom:1px solid rgba(255,255,255,.08)}.visitor-legend{display:flex;gap:15px;flex-wrap:wrap}.visitors-stage{min-height:650px}.detail-lower{grid-template-columns:1fr}.tracking-sites-grid{grid-template-columns:1fr}.tracking-hero{align-items:flex-start;flex-direction:column}}
    @media(max-width:575px){.visitors-page{border-radius:12px}.visitors-toolbar{align-items:flex-start}.visitors-layout{min-height:600px}.visitors-stage{min-height:580px}.radar{width:95%}.visitor-tag{display:none}.detail-grid{grid-template-columns:repeat(2,1fr)}.visitor-detail-body{padding:12px}.tracking-panel{padding:15px}.tracking-summary{width:100%;flex-wrap:wrap}.tracking-summary-item{flex:1}.tracking-hero-copy{align-items:flex-start}.tracking-create{padding:15px}}
</style>
@endsection

@section('content')
<div class="page-body">
    <div class="container-fluid">
        <div class="visitors-page">
            <div class="visitors-toolbar">
                <div>
                    <h3 class="mb-1" id="visitorSectionTitle"><i class="fas fa-satellite-dish me-2 text-info"></i>زوار الموقع المباشرون</h3>
                    <div class="text-muted small" id="visitorSectionSubtitle">مراقبة حركة الزوار وبدء المحادثات في الوقت الفعلي</div>
                </div>
                <div class="visitors-actions">
                    <button class="visitors-sound-btn" id="visitorSoundButton" data-live-action><i class="fas fa-volume-mute me-1"></i><span>تفعيل صوت الدخول</span></button>
                    <button class="visitors-icon-btn active" id="radarViewButton" data-live-action title="العرض الدائري"><i class="fas fa-bullseye"></i></button>
                    <button class="visitors-icon-btn" id="listViewButton" data-live-action title="عرض القائمة"><i class="fas fa-list"></i></button>
                    <button class="visitors-icon-btn" id="refreshVisitorsButton" title="تحديث"><i class="fas fa-sync-alt"></i></button>
                </div>
            </div>

            <div class="visitor-tabs">
                <button class="visitor-tab active" data-panel="liveVisitorsPanel" data-tab-name="live"><i class="fas fa-circle text-success"></i>الزوار المباشرون <span class="badge bg-primary ms-1" id="liveTabCount">0</span></button>
                <button class="visitor-tab" data-panel="visitsHistoryPanel" data-tab-name="history"><i class="fas fa-history"></i>سجل الزيارات</button>
                <button class="visitor-tab" data-panel="conversationsPanel" data-tab-name="messages"><i class="fas fa-inbox"></i>إدارة الرسائل</button>
                <button class="visitor-tab" data-panel="trackingCodesPanel" data-tab-name="tracking"><i class="fas fa-code"></i>أكواد التتبع <span class="badge bg-secondary ms-1">{{ $sites->count() }}</span></button>
            </div>

            <div class="visitor-tab-panel active" id="liveVisitorsPanel">
              <div class="visitors-layout">
                <aside class="visitors-sidebar">
                    <div><span class="online-pulse"></span><span class="online-label">زوار متصلون</span></div>
                    <div class="online-total mt-3" id="onlineVisitorsCount">0</div>
                    <input type="search" class="form-control visitor-search" id="visitorSearch" placeholder="بحث برقم الزائر...">
                    <div class="visitor-legend">
                        <div class="visitor-legend-title">ترتيب الزوار حسب وقت الجلسة</div>
                        <div class="legend-row"><span class="legend-dot pink"></span>أقل من دقيقة</div>
                        <div class="legend-row"><span class="legend-dot amber"></span>من دقيقة إلى 3 دقائق</div>
                        <div class="legend-row"><span class="legend-dot cyan"></span>من 3 إلى 7 دقائق</div>
                        <div class="legend-row"><span class="legend-dot blue"></span>أكثر من 7 دقائق</div>
                    </div>
                </aside>

                <section class="visitors-stage" id="visitorsStage">
                    <div class="radar" id="visitorsRadar">
                        <div class="radar-ring r4"></div><div class="radar-ring r3"></div>
                        <div class="radar-ring r2"></div><div class="radar-ring r1"></div>
                        <div class="radar-center"><i class="fas fa-building"></i></div>
                    </div>
                    <div class="visitors-empty" id="visitorsEmpty">
                        <i class="fas fa-user-clock"></i>
                        لا يوجد زوار متصلون الآن
                    </div>
                </section>

                <section class="visitors-list" id="visitorsList">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>الزائر</th><th>الجهاز</th><th>الصفحة الحالية</th><th>مدة الجلسة</th><th>الرسائل</th><th></th></tr></thead>
                            <tbody id="visitorsListBody"></tbody>
                        </table>
                    </div>
                </section>
              </div>
            </div>

            <div class="visitor-tab-panel records-panel" id="visitsHistoryPanel">
                <div class="records-header">
                    <div><h4 class="records-title">سجل زيارات الموقع</h4><div class="text-muted small mt-1">جميع الزوار والصفحات والمصادر والأجهزة المسجلة</div></div>
                    <div class="records-filters">
                        <input class="form-control records-input" id="historySearch" placeholder="بحث بالاسم، الرقم أو IP">
                        <select class="form-select records-select" id="historyDays">
                            <option value="1">اليوم</option><option value="7">آخر 7 أيام</option>
                            <option value="30" selected>آخر 30 يومًا</option><option value="90">آخر 90 يومًا</option>
                        </select>
                    </div>
                </div>
                <div class="records-table-wrap table-responsive">
                    <table class="table records-table align-middle">
                        <thead><tr><th>الزائر</th><th>الحالة</th><th>الجهاز</th><th>المصدر</th><th>IP</th><th>الصفحات</th><th>آخر زيارة</th><th></th></tr></thead>
                        <tbody id="visitsHistoryBody"><tr><td colspan="8" class="text-center py-5">جاري تحميل السجل...</td></tr></tbody>
                    </table>
                </div>
            </div>

            <div class="visitor-tab-panel records-panel" id="conversationsPanel">
                <div class="records-header">
                    <div><h4 class="records-title">إدارة الرسائل</h4><div class="text-muted small mt-1">صندوق مستقل لمتابعة محادثات المواقع والرد على العملاء</div></div>
                    <input class="form-control records-input" id="conversationSearch" style="max-width:300px" placeholder="بحث في المحادثات">
                </div>
                <div class="records-table-wrap table-responsive">
                    <table class="table records-table align-middle">
                        <thead><tr><th>المحادثة</th><th>الموقع</th><th>الحالة</th><th>آخر رسالة</th><th>مرسل الرسالة</th><th>الرسائل</th><th>آخر تحديث</th><th></th></tr></thead>
                        <tbody id="conversationsBody"><tr><td colspan="8" class="text-center py-5">جاري تحميل الرسائل...</td></tr></tbody>
                    </table>
                </div>
            </div>

            <div class="visitor-tab-panel tracking-panel" id="trackingCodesPanel">
                <div class="tracking-hero">
                    <div class="tracking-hero-copy">
                        <div class="tracking-hero-icon"><i class="fas fa-code"></i></div>
                        <div><h4>إدارة أكواد التتبع</h4><p>كود مستقل وآمن لكل موقع، مع تخصيص نافذة المحادثة والنطاقات المسموحة.</p></div>
                    </div>
                    <div class="tracking-summary">
                        <div class="tracking-summary-item"><strong>{{ $sites->count() }}</strong><span>إجمالي المواقع</span></div>
                        <div class="tracking-summary-item"><strong>{{ $sites->where('is_active', true)->count() }}</strong><span>أكواد فعالة</span></div>
                        <div class="tracking-summary-item"><strong>{{ $sites->sum('visitors_count') }}</strong><span>زوار خارجيون</span></div>
                    </div>
                </div>

                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                <div class="tracking-create">
                    <div class="tracking-section-title"><i class="fas fa-plus-circle"></i><strong>إضافة موقع جديد</strong></div>
                    <form class="tracking-form" method="POST" action="{{ route('website-visitors.sites.store', ['tab' => 'tracking']) }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">اسم الموقع</label><input class="form-control" name="name" value="{{ old('name') }}" required placeholder="مثال: الموقع الرئيسي"></div>
                            <div class="col-md-8"><label class="form-label">النطاقات المسموحة</label><input class="form-control" name="allowed_domains" value="{{ old('allowed_domains') }}" required placeholder="example.com, www.example.com, *.example.com"></div>
                            <div class="col-md-4"><label class="form-label">عنوان نافذة المحادثة</label><input class="form-control" name="widget_title" value="{{ old('widget_title', 'تحدث معنا') }}" required></div>
                            <div class="col-md-4"><label class="form-label">رسالة الترحيب</label><input class="form-control" name="welcome_message" value="{{ old('welcome_message', 'مرحبًا، كيف يمكننا مساعدتك؟') }}"></div>
                            <div class="col-md-2"><label class="form-label">اللون</label><input class="form-control form-control-color w-100" type="color" name="primary_color" value="{{ old('primary_color', '#277ccd') }}"></div>
                            <div class="col-md-2"><label class="form-label">موضع الأيقونة</label><select class="form-select" name="position"><option value="right">يمين</option><option value="left">يسار</option></select></div>
                            <div class="col-12 d-flex justify-content-between align-items-center">
                                <label class="text-light"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked> تفعيل الكود مباشرة</label>
                                <button class="btn btn-primary px-4" type="submit"><i class="fas fa-plus me-1"></i>إنشاء كود التتبع</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="tracking-section-title"><i class="fas fa-layer-group"></i><strong>المواقع المربوطة</strong></div>
                <div class="tracking-sites-grid">
                    @forelse($sites as $site)
                        <article class="tracking-site-card">
                            <div class="tracking-site-head">
                                <div class="tracking-site-logo"><i class="fas fa-globe"></i></div>
                                <div class="tracking-site-title"><strong>{{ $site->name }}</strong><small>{{ $site->visitors_count }} زائر مسجل</small></div>
                                <span class="tracking-status {{ $site->is_active ? 'active' : 'inactive' }}"><i class="fas fa-circle me-1"></i>{{ $site->is_active ? 'فعال' : 'متوقف' }}</span>
                            </div>
                            <div class="tracking-site-body">
                                <div class="domain-chips">
                                    @foreach($site->allowed_domains ?? [] as $domain)
                                        <span class="domain-chip">{{ $domain }}</span>
                                    @endforeach
                                </div>
                                <div class="embed-label"><span>كود التثبيت</span><span>ضعه قبل &lt;/body&gt;</span></div>
                                <div class="embed-code-wrap">
                                    <textarea class="embed-code" id="embedCode{{ $site->id }}" readonly>{{ $site->embedCode() }}</textarea>
                                    <button class="btn btn-sm btn-outline-info copy-embed-code" type="button" data-target="embedCode{{ $site->id }}"><i class="far fa-copy"></i> نسخ الكود</button>
                                </div>
                            </div>
                            <details class="tracking-config">
                                <summary><i class="fas fa-sliders-h me-2"></i>تعديل إعدادات الموقع والواجهة</summary>
                                <form class="tracking-form tracking-config-form" method="POST" action="{{ route('website-visitors.sites.update', ['site' => $site, 'tab' => 'tracking']) }}">
                                    @csrf @method('PUT')
                                    <div class="row g-3">
                                        <div class="col-md-4"><label class="form-label">اسم الموقع</label><input class="form-control" name="name" value="{{ $site->name }}" required></div>
                                        <div class="col-md-8"><label class="form-label">النطاقات</label><input class="form-control" name="allowed_domains" value="{{ implode(', ', $site->allowed_domains ?? []) }}" required></div>
                                        <div class="col-md-4"><label class="form-label">عنوان المحادثة</label><input class="form-control" name="widget_title" value="{{ $site->widget_title }}" required></div>
                                        <div class="col-md-4"><label class="form-label">رسالة الترحيب</label><input class="form-control" name="welcome_message" value="{{ $site->welcome_message }}"></div>
                                        <div class="col-md-2"><label class="form-label">اللون</label><input class="form-control form-control-color w-100" type="color" name="primary_color" value="{{ $site->primary_color }}"></div>
                                        <div class="col-md-2"><label class="form-label">الموضع</label><select class="form-select" name="position"><option value="right" @selected($site->position === 'right')>يمين</option><option value="left" @selected($site->position === 'left')>يسار</option></select></div>
                                        <div class="col-12 d-flex justify-content-between align-items-center"><label class="text-light"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($site->is_active)> الكود فعال</label><button class="btn btn-outline-primary" type="submit">حفظ التعديلات</button></div>
                                    </div>
                                </form>
                                <form method="POST" action="{{ route('website-visitors.sites.destroy', ['site' => $site, 'tab' => 'tracking']) }}" class="tracking-danger" onsubmit="return confirm('حذف هذا الكود وإيقافه نهائيًا؟')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit"><i class="far fa-trash-alt"></i> حذف الكود</button>
                                </form>
                            </details>
                        </article>
                    @empty
                        <div class="tracking-empty"><i class="fas fa-code"></i>لا توجد مواقع مربوطة حتى الآن.<br><small>استخدم النموذج بالأعلى لإنشاء أول كود تتبع.</small></div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<div class="visitor-alert-toast" id="visitorAlertToast">
    <i class="fas fa-user-plus"></i>
    <div><strong>زائر جديد دخل الموقع</strong><div class="small text-light" id="visitorAlertLabel"></div></div>
</div>

<div class="modal fade visitor-detail-modal" id="visitorDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="visitor-profile">
                    <div class="detail-avatar"><i class="fas fa-user"></i></div>
                    <div>
                        <h4 class="modal-title mb-0" id="detailVisitorLabel">بيانات الزائر</h4>
                        <div class="visitor-tech" id="detailVisitorTech"></div>
                    </div>
                </div>
                <div class="score-wrap">
                    <div class="score-number" id="detailLeadScore">0%</div>
                    <div class="score-label">فرصة التحويل</div>
                </div>
                <button type="button" class="btn-close ms-3" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="detail-tags" id="detailVisitorTags"></div>
            <div class="visitor-detail-body">
                <div class="detail-grid">
                    <div class="detail-card"><i class="far fa-user"></i><div class="detail-card-value" id="detailClientState">زائر غير مسجل</div><div class="detail-card-label">حالة العميل</div></div>
                    <div class="detail-card"><i class="far fa-clock"></i><div class="detail-card-value" id="detailSessionTime">0 ث</div><div class="detail-card-label">مدة الجلسة</div></div>
                    <div class="detail-card"><i class="far fa-comments"></i><div class="detail-card-value" id="detailMessageCount">0</div><div class="detail-card-label">عدد الرسائل</div></div>
                    <div class="detail-card"><i class="fas fa-map-marker-alt"></i><div class="detail-card-value" id="detailIpAddress">-</div><div class="detail-card-label">عنوان IP</div></div>
                    <div class="detail-card"><i class="fas fa-link"></i><div class="detail-card-value" id="detailSource">دخول مباشر</div><div class="detail-card-label">مصدر الزيارة</div></div>
                    <div class="detail-card"><i class="far fa-window-restore"></i><div class="detail-card-value" id="detailPageCount">0</div><div class="detail-card-label">الصفحات المشاهدة</div></div>
                </div>

                <div class="detail-lower">
                    <div class="detail-panel">
                        <div class="detail-panel-title"><i class="fas fa-route me-2 text-info"></i>مسار الزائر اليوم</div>
                        <div class="visitor-timeline" id="visitorTimeline"></div>
                    </div>
                    <div class="detail-panel">
                        <div class="detail-panel-title d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-comments me-2 text-primary"></i>سجل المحادثة</span>
                            <span class="badge bg-secondary" id="detailChatState">الدردشة مخفية عن الموقع</span>
                        </div>
                        <div class="detail-chat-messages" id="detailChatMessages"></div>
                        <form class="detail-chat-form d-none" id="detailChatForm">
                            <input type="text" class="form-control detail-chat-input" id="detailChatInput" maxlength="2000" placeholder="اكتب رسالتك للزائر..." autocomplete="off">
                            <button class="btn btn-primary" type="submit"><i class="fas fa-paper-plane"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
<script>
(function () {
    const dataUrl = @json(route('website-visitors.data'));
    const historyUrl = @json(route('website-visitors.history'));
    const conversationsUrl = @json(route('website-visitors.conversations'));
    const detailUrl = @json(route('website-visitors.show', ['visitor' => '__VISITOR__']));
    const sendUrl = @json(route('website-visitors.send', ['visitor' => '__VISITOR__']));
    const csrf = @json(csrf_token());
    let allVisitors = [];
    let activeVisitorId = null;
    let detailModal = null;
    let audioContext = null;
    let soundEnabled = localStorage.getItem('websiteVisitorSound') === 'enabled';
    let historyTimer = null;
    let conversationTimer = null;

    function escapeText(value) {
        return $('<div>').text(value == null ? '' : value).html();
    }

    function formatDuration(seconds) {
        seconds = Number(seconds || 0);
        if (seconds < 60) return seconds + ' ث';
        const minutes = Math.floor(seconds / 60);
        const remaining = seconds % 60;
        return minutes + ' د ' + remaining + ' ث';
    }

    function relativeTime(value) {
        if (!value) return '-';
        const seconds = Math.max(0, Math.floor((Date.now() - new Date(value).getTime()) / 1000));
        if (seconds < 10) return 'الآن';
        if (seconds < 60) return 'منذ ' + seconds + ' ثانية';
        return 'منذ ' + Math.floor(seconds / 60) + ' دقيقة';
    }

    function fullDate(value) {
        if (!value) return '-';
        return new Intl.DateTimeFormat('ar-SA', {
            year: 'numeric', month: 'short', day: 'numeric',
            hour: '2-digit', minute: '2-digit'
        }).format(new Date(value));
    }

    function updateSoundButton() {
        const button = $('#visitorSoundButton');
        button.toggleClass('enabled', soundEnabled);
        button.find('i').attr('class', soundEnabled ? 'fas fa-volume-up me-1' : 'fas fa-volume-mute me-1');
        button.find('span').text(soundEnabled ? 'صوت الدخول مفعّل' : 'تفعيل صوت الدخول');
    }

    function updateSectionChrome(panel) {
        const sections = {
            liveVisitorsPanel: ['fas fa-satellite-dish', 'زوار الموقع المباشرون', 'مراقبة حركة الزوار وبدء المحادثات في الوقت الفعلي'],
            visitsHistoryPanel: ['fas fa-history', 'سجل الزيارات', 'مراجعة مصادر الزيارات والصفحات والأجهزة المسجلة'],
            conversationsPanel: ['fas fa-inbox', 'إدارة الرسائل', 'صندوق مستقل لمتابعة محادثات العملاء والرد عليها'],
            trackingCodesPanel: ['fas fa-code', 'أكواد التتبع', 'إدارة المواقع المربوطة وتخصيص نافذة المحادثة']
        };
        const section = sections[panel] || sections.liveVisitorsPanel;
        $('#visitorSectionTitle').html('<i class="' + section[0] + ' me-2 text-info"></i>' + section[1]);
        $('#visitorSectionSubtitle').text(section[2]);
        $('[data-live-action]').toggle(panel === 'liveVisitorsPanel');
    }

    function playVisitorSound() {
        if (!soundEnabled) return;
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (!AudioContextClass) return;
        audioContext = audioContext || new AudioContextClass();
        if (audioContext.state === 'suspended') audioContext.resume();
        const now = audioContext.currentTime;
        [660, 880].forEach(function (frequency, index) {
            const oscillator = audioContext.createOscillator();
            const gain = audioContext.createGain();
            oscillator.type = 'sine';
            oscillator.frequency.value = frequency;
            gain.gain.setValueAtTime(0.0001, now + (index * .16));
            gain.gain.exponentialRampToValueAtTime(.2, now + (index * .16) + .02);
            gain.gain.exponentialRampToValueAtTime(.0001, now + (index * .16) + .14);
            oscillator.connect(gain).connect(audioContext.destination);
            oscillator.start(now + (index * .16));
            oscillator.stop(now + (index * .16) + .15);
        });
    }

    function notifyNewVisitors(visitors) {
        const currentIds = visitors.map(function (visitor) { return Number(visitor.id); });
        const saved = sessionStorage.getItem('knownWebsiteVisitorIds');
        if (saved !== null) {
            let knownIds = [];
            try { knownIds = JSON.parse(saved); } catch (error) {}
            const newVisitors = visitors.filter(function (visitor) {
                return !knownIds.includes(Number(visitor.id));
            });
            if (newVisitors.length) {
                playVisitorSound();
                $('#visitorAlertLabel').text(newVisitors.length === 1
                    ? newVisitors[0].label
                    : newVisitors.length + ' زوار جدد');
                $('#visitorAlertToast').addClass('show');
                window.setTimeout(function () { $('#visitorAlertToast').removeClass('show'); }, 4500);
            }
        }
        sessionStorage.setItem('knownWebsiteVisitorIds', JSON.stringify(currentIds));
    }

    function ringFor(seconds) {
        if (seconds < 60) return {radius: 12.5, color: 'pink'};
        if (seconds < 180) return {radius: 25, color: 'amber'};
        if (seconds < 420) return {radius: 37.5, color: 'cyan'};
        return {radius: 50, color: 'blue'};
    }

    function renderVisitors() {
        const query = $('#visitorSearch').val().trim().toLowerCase();
        const visitors = allVisitors.filter(function (visitor) {
            return !query || visitor.label.toLowerCase().includes(query) || String(visitor.id).includes(query);
        });
        $('#onlineVisitorsCount').text(allVisitors.length);
        $('#visitorsEmpty').toggle(visitors.length === 0);
        $('#visitorsRadar .visitor-node').remove();

        const ringGroups = {pink: [], amber: [], cyan: [], blue: []};
        visitors.forEach(function (visitor) {
            ringGroups[ringFor(visitor.session_seconds).color].push(visitor);
        });

        Object.keys(ringGroups).forEach(function (ringName, ringIndex) {
            const group = ringGroups[ringName];
            const radius = [12.5, 25, 37.5, 50][ringIndex];
            group.forEach(function (visitor, index) {
                const angle = ((360 / Math.max(group.length, 1)) * index) - 90 + (ringIndex * 23);
                const radians = angle * Math.PI / 180;
                const left = 50 + Math.cos(radians) * radius;
                const top = 50 + Math.sin(radians) * radius;
                const node = $('<button type="button" class="visitor-node open-visitor-detail"></button>');
                node.attr('data-id', visitor.id).css({left: left + '%', top: top + '%'});
                node.html(
                    '<div class="visitor-time">' + escapeText(formatDuration(visitor.session_seconds)) + '</div>' +
                    '<div class="visitor-node-main"><span class="visitor-tag">' + escapeText(visitor.label) + '</span>' +
                    '<span class="visitor-avatar"><i class="fas fa-user"></i><span class="visitor-status"></span></span></div>' +
                    (visitor.unread_messages_count ? '<span class="visitor-message-badge">' + visitor.unread_messages_count + '</span>' : '')
                );
                $('#visitorsRadar').append(node);
            });
        });

        $('#visitorsListBody').html(visitors.length ? visitors.map(function (visitor) {
            return '<tr><td><span class="online-pulse"></span><strong>' + escapeText(visitor.label) + '</strong></td>' +
                '<td>' + escapeText(visitor.device + ' · ' + visitor.browser) + '</td>' +
                '<td><div class="text-truncate" style="max-width:320px;direction:ltr">' + escapeText(visitor.page_title || visitor.current_url || '/') + '</div></td>' +
                '<td>' + escapeText(formatDuration(visitor.session_seconds)) + '</td>' +
                '<td>' + Number(visitor.messages_count || 0) + '</td>' +
                '<td><button class="btn btn-primary btn-sm open-visitor-detail" data-id="' + visitor.id + '">عرض التفاصيل</button></td></tr>';
        }).join('') : '<tr><td colspan="6" class="text-center text-muted py-5">لا يوجد زوار متصلون الآن</td></tr>');
    }

    function loadVisitors() {
        $('#refreshVisitorsButton i').addClass('fa-spin');
        $.getJSON(dataUrl).done(function (data) {
            notifyNewVisitors(data.visitors || []);
            allVisitors = data.visitors || [];
            $('#liveTabCount').text(allVisitors.length);
            renderVisitors();
        }).always(function () {
            $('#refreshVisitorsButton i').removeClass('fa-spin');
        });
    }

    function statusBadge(online) {
        return '<span class="record-status ' + (online ? 'online' : 'offline') + '"><i class="fas fa-circle"></i>' +
            (online ? 'متصل الآن' : 'غير متصل') + '</span>';
    }

    function loadHistory() {
        const params = {
            days: $('#historyDays').val(),
            search: $('#historySearch').val().trim()
        };
        $('#visitsHistoryBody').html('<tr><td colspan="8" class="text-center py-5">جاري تحميل السجل...</td></tr>');
        $.getJSON(historyUrl, params).done(function (data) {
            const visitors = data.visitors || [];
            $('#visitsHistoryBody').html(visitors.length ? visitors.map(function (visitor) {
                return '<tr><td><strong>' + escapeText(visitor.label) + '</strong>' +
                    (visitor.registered_client ? '<div class="small text-info">عميل مسجل</div>' : '<div class="small text-muted">زائر مجهول</div>') + '</td>' +
                    '<td>' + statusBadge(visitor.online) + '</td>' +
                    '<td>' + escapeText(visitor.device + ' · ' + visitor.operating_system + ' · ' + visitor.browser) + '</td>' +
                    '<td>' + escapeText(visitor.source) + '</td><td style="direction:ltr">' + escapeText(visitor.ip_address || '-') + '</td>' +
                    '<td><span class="badge bg-info text-dark">' + visitor.page_views_count + '</span></td>' +
                    '<td>' + escapeText(fullDate(visitor.last_seen_at)) + '</td>' +
                    '<td><button class="btn btn-outline-info btn-sm open-visitor-detail" data-id="' + visitor.id + '">التفاصيل</button></td></tr>';
            }).join('') : '<tr><td colspan="8" class="text-center text-muted py-5">لا توجد زيارات مطابقة</td></tr>');
        });
    }

    function loadConversations() {
        $('#conversationsBody').html('<tr><td colspan="8" class="text-center py-5">جاري تحميل الرسائل...</td></tr>');
        $.getJSON(conversationsUrl, {search: $('#conversationSearch').val().trim()}).done(function (data) {
            const conversations = data.conversations || [];
            $('#conversationsBody').html(conversations.length ? conversations.map(function (conversation) {
                return '<tr><td><strong>' + escapeText(conversation.label) + '</strong>' +
                    (conversation.phone ? '<div class="small text-muted">' + escapeText(conversation.phone) + '</div>' : '') + '</td>' +
                    '<td><span class="badge bg-dark">' + escapeText(conversation.site_name) + '</span></td>' +
                    '<td>' + statusBadge(conversation.online) + '</td>' +
                    '<td><div class="text-truncate" style="max-width:330px">' + escapeText(conversation.last_message || '-') + '</div></td>' +
                    '<td>' + (conversation.last_sender === 'admin' ? 'الموظف' : 'الزائر') + '</td>' +
                    '<td><span class="badge bg-primary">' + conversation.messages_count + '</span>' +
                    (conversation.unread_messages_count ? ' <span class="badge bg-danger">' + conversation.unread_messages_count + ' جديد</span>' : '') + '</td>' +
                    '<td>' + escapeText(fullDate(conversation.last_message_at)) + '</td>' +
                    '<td><button class="btn btn-primary btn-sm open-visitor-detail" data-id="' + conversation.id + '">فتح السجل</button></td></tr>';
            }).join('') : '<tr><td colspan="8" class="text-center text-muted py-5">لا توجد رسائل مسجلة</td></tr>');
        });
    }

    function renderMessages(messages) {
        const box = $('#detailChatMessages');
        if (!messages.length) {
            box.html('<div class="text-center text-muted py-5">لا توجد رسائل. ابدأ محادثة مع الزائر.</div>');
            return;
        }
        box.html(messages.map(function (message) {
            return '<div class="detail-chat-row ' + message.sender + '"><div class="detail-chat-bubble">' + escapeText(message.body) + '</div></div>';
        }).join(''));
        box.scrollTop(box[0].scrollHeight);
    }

    function loadDetails(silent) {
        if (!activeVisitorId) return;
        if (!silent) {
            $('#visitorTimeline').html('<div class="text-center text-muted py-5">جاري تحميل التفاصيل...</div>');
            $('#detailChatMessages').html('<div class="text-center text-muted py-5">جاري تحميل المحادثة...</div>');
        }
        $.getJSON(detailUrl.replace('__VISITOR__', activeVisitorId)).done(function (data) {
            const visitor = data.visitor;
            $('#detailVisitorLabel').text(visitor.label);
            $('#detailVisitorTech').html(
                '<span><i class="fab fa-' + (visitor.operating_system === 'Windows' ? 'windows' : visitor.operating_system === 'Android' ? 'android' : visitor.operating_system === 'iOS' || visitor.operating_system === 'macOS' ? 'apple' : 'linux') + '"></i> ' + escapeText(visitor.operating_system) + '</span>' +
                '<span><i class="fas fa-globe"></i> ' + escapeText(visitor.browser) + '</span>' +
                '<span><i class="fas fa-' + (visitor.device === 'هاتف' ? 'mobile-alt' : 'desktop') + '"></i> ' + escapeText(visitor.device) + '</span>'
            );
            $('#detailLeadScore').text(visitor.lead_score + '%');
            $('#detailVisitorTags').html(
                '<span class="detail-tag"><i class="fas fa-circle text-success"></i>' + (visitor.online ? 'متصل الآن' : 'غير متصل') + '</span>' +
                '<span class="detail-tag"><i class="fas fa-globe"></i>' + escapeText(visitor.site_name) + '</span>' +
                '<span class="detail-tag"><i class="fas fa-language"></i>' + escapeText(visitor.locale) + '</span>' +
                '<span class="detail-tag"><i class="fas fa-tv"></i>' + escapeText(visitor.screen) + '</span>' +
                '<span class="detail-tag"><i class="fas fa-clock"></i>' + escapeText(visitor.timezone) + '</span>'
            );
            $('#detailClientState').html(visitor.registered_client
                ? escapeText(visitor.name) + '<br><small class="text-info">' + escapeText(visitor.phone || visitor.email || '') + '</small>'
                : 'زائر غير مسجل');
            $('#detailSessionTime').text(formatDuration(visitor.session_seconds));
            $('#detailMessageCount').text(visitor.messages_count);
            $('#detailIpAddress').text(visitor.ip_address || '-');
            $('#detailSource').text(visitor.source || 'دخول مباشر');
            $('#detailPageCount').text(visitor.page_views_count);
            $('#detailChatForm').toggleClass('d-none', !visitor.chat_available);
            $('#detailChatState')
                .toggleClass('bg-secondary', !visitor.chat_available)
                .toggleClass('bg-success', visitor.chat_available)
                .text(visitor.chat_available ? 'المحادثة ظاهرة للزائر' : 'الدردشة مخفية عن الموقع');

            const views = data.page_views || [];
            $('#visitorTimeline').html(views.length ? views.map(function (view) {
                return '<div class="timeline-item"><div class="timeline-time">' + escapeText(relativeTime(view.visited_at)) + '</div>' +
                    '<div class="timeline-title">' + escapeText(view.title || view.path) + '</div>' +
                    '<div class="timeline-url" title="' + escapeText(view.url) + '">' + escapeText(view.url) + '</div></div>';
            }).join('') : '<div class="text-center text-muted py-5">لا يوجد سجل صفحات حتى الآن</div>');
            renderMessages(data.messages || []);
        });
    }

    $(document).on('click', '.open-visitor-detail', function () {
        activeVisitorId = $(this).data('id');
        detailModal = detailModal || new bootstrap.Modal(document.getElementById('visitorDetailModal'));
        detailModal.show();
        loadDetails(false);
    });

    $('#detailChatForm').on('submit', function (event) {
        event.preventDefault();
        const input = $('#detailChatInput');
        const body = input.val().trim();
        if (!body || !activeVisitorId) return;
        input.prop('disabled', true);
        $.ajax({
            url: sendUrl.replace('__VISITOR__', activeVisitorId),
            method: 'POST',
            data: {_token: csrf, body: body}
        }).done(function () {
            input.val('');
            loadDetails(true);
            loadVisitors();
            loadConversations();
        }).always(function () {
            input.prop('disabled', false).trigger('focus');
        });
    });

    $('#focusVisitorChat').on('click', function () { $('#detailChatInput').trigger('focus'); });
    $('.copy-embed-code').on('click', function () {
        const button = $(this);
        const field = document.getElementById(button.data('target'));
        const copied = navigator.clipboard && window.isSecureContext
            ? navigator.clipboard.writeText(field.value)
            : new Promise(function (resolve) {
                field.select();
                document.execCommand('copy');
                resolve();
            });
        copied.then(function () {
            button.html('<i class="fas fa-check"></i> تم النسخ');
            window.setTimeout(function () { button.html('<i class="far fa-copy"></i> نسخ الكود'); }, 1600);
        });
    });
    const requestedTab = new URLSearchParams(window.location.search).get('tab');
    if (requestedTab === 'tracking' || @json($errors->any())) {
        $('.visitor-tab').removeClass('active');
        $('.visitor-tab-panel').removeClass('active');
        $('.visitor-tab[data-tab-name="tracking"]').addClass('active');
        $('#trackingCodesPanel').addClass('active');
        updateSectionChrome('trackingCodesPanel');
    } else if (requestedTab === 'messages') {
        $('.visitor-tab').removeClass('active');
        $('.visitor-tab-panel').removeClass('active');
        $('.visitor-tab[data-tab-name="messages"]').addClass('active');
        $('#conversationsPanel').addClass('active');
        updateSectionChrome('conversationsPanel');
        loadConversations();
    }
    $('#visitorSearch').on('input', renderVisitors);
    $('#refreshVisitorsButton').on('click', function () {
        loadVisitors();
        if ($('#visitsHistoryPanel').hasClass('active')) loadHistory();
        if ($('#conversationsPanel').hasClass('active')) loadConversations();
    });
    $('#visitorSoundButton').on('click', function () {
        soundEnabled = !soundEnabled;
        localStorage.setItem('websiteVisitorSound', soundEnabled ? 'enabled' : 'disabled');
        updateSoundButton();
        if (soundEnabled) playVisitorSound();
    });
    $('.visitor-tab').on('click', function () {
        const panel = $(this).data('panel');
        $('.visitor-tab').removeClass('active');
        $(this).addClass('active');
        $('.visitor-tab-panel').removeClass('active');
        $('#' + panel).addClass('active');
        updateSectionChrome(panel);
        const tabName = $(this).data('tab-name');
        const url = new URL(window.location.href);
        if (tabName === 'live') url.searchParams.delete('tab');
        else url.searchParams.set('tab', tabName);
        window.history.replaceState({}, '', url);
        if (panel === 'visitsHistoryPanel') loadHistory();
        if (panel === 'conversationsPanel') loadConversations();
    });
    $('#historyDays').on('change', loadHistory);
    $('#historySearch').on('input', function () {
        window.clearTimeout(historyTimer);
        historyTimer = window.setTimeout(loadHistory, 350);
    });
    $('#conversationSearch').on('input', function () {
        window.clearTimeout(conversationTimer);
        conversationTimer = window.setTimeout(loadConversations, 350);
    });
    $('#radarViewButton').on('click', function () {
        $(this).addClass('active'); $('#listViewButton').removeClass('active');
        $('#visitorsStage').removeClass('hidden'); $('#visitorsList').removeClass('active');
    });
    $('#listViewButton').on('click', function () {
        $(this).addClass('active'); $('#radarViewButton').removeClass('active');
        $('#visitorsStage').addClass('hidden'); $('#visitorsList').addClass('active');
    });

    updateSoundButton();
    if (!requestedTab) updateSectionChrome('liveVisitorsPanel');
    loadVisitors();
    window.setInterval(loadVisitors, 5000);
    window.setInterval(function () {
        if ($('#visitorDetailModal').hasClass('show')) loadDetails(true);
    }, 4000);
})();
</script>
@endsection
