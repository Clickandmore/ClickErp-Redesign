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
    @media(max-width:991px){.visitors-layout{grid-template-columns:1fr}.visitors-sidebar{border-left:0;border-bottom:1px solid rgba(255,255,255,.08)}.visitor-legend{display:flex;gap:15px;flex-wrap:wrap}.visitors-stage{min-height:650px}.detail-lower{grid-template-columns:1fr}}
    @media(max-width:575px){.visitors-page{border-radius:12px}.visitors-toolbar{align-items:flex-start}.visitors-layout{min-height:600px}.visitors-stage{min-height:580px}.radar{width:95%}.visitor-tag{display:none}.detail-grid{grid-template-columns:repeat(2,1fr)}.visitor-detail-body{padding:12px}}
</style>
@endsection

@section('content')
<div class="page-body">
    <div class="container-fluid">
        <div class="visitors-page">
            <div class="visitors-toolbar">
                <div>
                    <h3 class="mb-1"><i class="fas fa-satellite-dish me-2 text-info"></i>زوار الموقع المباشرون</h3>
                    <div class="text-muted small">مراقبة حركة الزوار وبدء المحادثات في الوقت الفعلي</div>
                </div>
                <div class="visitors-actions">
                    <button class="visitors-icon-btn active" id="radarViewButton" title="العرض الدائري"><i class="fas fa-bullseye"></i></button>
                    <button class="visitors-icon-btn" id="listViewButton" title="عرض القائمة"><i class="fas fa-list"></i></button>
                    <button class="visitors-icon-btn" id="refreshVisitorsButton" title="تحديث"><i class="fas fa-sync-alt"></i></button>
                </div>
            </div>

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
    </div>
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
                            <span><i class="fas fa-comments me-2 text-primary"></i>المحادثة</span>
                            <button type="button" class="proactive-btn btn-sm" id="focusVisitorChat">بدء دردشة استباقية</button>
                        </div>
                        <div class="detail-chat-messages" id="detailChatMessages"></div>
                        <form class="detail-chat-form" id="detailChatForm">
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
    const detailUrl = @json(route('website-visitors.show', ['visitor' => '__VISITOR__']));
    const sendUrl = @json(route('website-visitors.send', ['visitor' => '__VISITOR__']));
    const csrf = @json(csrf_token());
    let allVisitors = [];
    let activeVisitorId = null;
    let detailModal = null;

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
            allVisitors = data.visitors || [];
            renderVisitors();
        }).always(function () {
            $('#refreshVisitorsButton i').removeClass('fa-spin');
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
        }).always(function () {
            input.prop('disabled', false).trigger('focus');
        });
    });

    $('#focusVisitorChat').on('click', function () { $('#detailChatInput').trigger('focus'); });
    $('#visitorSearch').on('input', renderVisitors);
    $('#refreshVisitorsButton').on('click', loadVisitors);
    $('#radarViewButton').on('click', function () {
        $(this).addClass('active'); $('#listViewButton').removeClass('active');
        $('#visitorsStage').removeClass('hidden'); $('#visitorsList').removeClass('active');
    });
    $('#listViewButton').on('click', function () {
        $(this).addClass('active'); $('#radarViewButton').removeClass('active');
        $('#visitorsStage').addClass('hidden'); $('#visitorsList').addClass('active');
    });

    loadVisitors();
    window.setInterval(loadVisitors, 5000);
    window.setInterval(function () {
        if ($('#visitorDetailModal').hasClass('show')) loadDetails(true);
    }, 4000);
})();
</script>
@endsection
