@extends('agent.layout')
@php $isAr = app()->getLocale() === 'ar'; @endphp
@section('page-title', $isAr ? 'لوحة تتبع إجراءات الفلبين' : 'Philippines Tracking Board')

@push('style')
<style>
.tk-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px}
.tk-stat{background:#fff;border:1px solid var(--agent-line);border-radius:12px;padding:18px;display:flex;align-items:center;justify-content:space-between;transition:transform .2s,box-shadow .2s;position:relative;overflow:hidden}
.tk-stat:hover{transform:translateY(-3px);box-shadow:0 8px 20px rgba(0,0,0,.06)}
.tk-stat::before{content:'';position:absolute;top:0;left:0;width:4px;height:100%;background:var(--agent-primary)}
.tk-stat.s{--c:#10b981}.tk-stat.w{--c:#f59e0b}.tk-stat.i{--c:#06b6d4}.tk-stat::before{background:var(--c,var(--agent-primary))}
.tk-stat h4{font-size:12px;color:var(--agent-muted);margin:0 0 4px;font-weight:500}
.tk-stat .tv{font-size:26px;font-weight:700;color:#0f172a;margin:0;line-height:1.1}
.tk-stat i{font-size:28px;opacity:.15;color:#0f172a}

.tk-prog{width:100%;height:7px;background:#f1f5f9;border-radius:99px;overflow:hidden}
.tk-prog-fill{height:100%;background:linear-gradient(90deg,var(--agent-primary),#06b6d4);border-radius:99px;transition:width .6s ease}

.tk-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:99px;font-size:11px;font-weight:600;cursor:pointer;transition:all .15s;border:1px solid transparent}
.tk-badge.pending{background:#f1f5f9;color:#64748b;border-color:#cbd5e1}
.tk-badge.done{background:#ecfdf5;color:#047857;border-color:#a7f3d0}
.tk-badge.fail{background:#fef2f2;color:#b91c1c;border-color:#fca5a5}
.tk-badge:hover{transform:translateY(-1px);box-shadow:0 2px 6px rgba(0,0,0,.06)}

.tk-avatar{width:38px;height:38px;border-radius:10px;object-fit:cover;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.08)}
.tk-worker{display:flex;align-items:center;gap:10px}
.tk-worker strong{font-size:13px;color:#0f172a;display:block}
.tk-worker span{font-size:11px;color:var(--agent-muted)}

.tk-overlay{position:fixed;inset:0;background:rgba(15,23,42,.45);backdrop-filter:blur(4px);z-index:1050;display:none;align-items:center;justify-content:center}
.tk-overlay.show{display:flex}
.tk-modal{background:#fff;border-radius:16px;width:92%;max-width:620px;box-shadow:0 25px 50px rgba(0,0,0,.2);transform:scale(.95);transition:transform .25s cubic-bezier(.34,1.56,.64,1);overflow:hidden;max-height:90vh;display:flex;flex-direction:column}
.tk-overlay.show .tk-modal{transform:scale(1)}
.tk-mhead{background:linear-gradient(135deg,var(--agent-primary),var(--agent-primary-dark));color:#fff;padding:16px 20px;display:flex;align-items:center;justify-content:space-between}
.tk-mhead h3{margin:0;font-size:16px;font-weight:600}
.tk-mclose{background:0;border:0;color:rgba(255,255,255,.7);font-size:22px;cursor:pointer;line-height:1}
.tk-mclose:hover{color:#fff}
.tk-tabs{display:flex;gap:4px;border-bottom:1px solid var(--agent-line);padding:8px 16px 0;overflow-x:auto;flex-shrink:0}
.tk-tab{background:0;border:0;padding:8px 12px;font-size:12px;font-weight:600;color:var(--agent-muted);border-bottom:2px solid transparent;cursor:pointer;white-space:nowrap}
.tk-tab:hover{color:var(--agent-primary)}
.tk-tab.active{color:var(--agent-primary);border-bottom-color:var(--agent-primary)}
.tk-mbody{padding:20px;overflow-y:auto;flex:1}
.tk-pane{display:none}.tk-pane.active{display:block}
.tk-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.tk-fg{display:flex;flex-direction:column;gap:4px;margin-bottom:10px}
.tk-fg label{font-size:11px;font-weight:600;color:#475569}
.tk-fg select,.tk-fg input,.tk-fg textarea{padding:9px 10px;border:1px solid var(--agent-line);border-radius:8px;font-size:13px;color:#1e293b;background:#f8fafc}
.tk-fg select:focus,.tk-fg input:focus,.tk-fg textarea:focus{outline:0;border-color:var(--agent-primary);background:#fff;box-shadow:0 0 0 2px rgba(15,118,110,.1)}
.tk-mfoot{padding:14px 20px;border-top:1px solid var(--agent-line);background:#f8fafc;display:flex;justify-content:flex-end;gap:10px;flex-shrink:0}
@media(max-width:576px){.tk-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="tk-stats">
    <div class="tk-stat i"><div><h4>{{ $isAr ? 'إجمالي الخادمات' : 'Total Maids' }}</h4><p class="tv">{{ $stats['total'] }}</p></div><i class="fa fa-users"></i></div>
    <div class="tk-stat s"><div><h4>{{ $isAr ? 'لائق طبياً' : 'Medical FIT' }}</h4><p class="tv">{{ $stats['medical_fit'] }}</p></div><i class="fa fa-heart-pulse"></i></div>
    <div class="tk-stat w"><div><h4>{{ $isAr ? 'فحص معلق' : 'Pending' }}</h4><p class="tv">{{ $stats['medical_pending'] }}</p></div><i class="fa fa-hourglass-half"></i></div>
    <div class="tk-stat i"><div><h4>{{ $isAr ? 'OEC جاهز' : 'OEC Released' }}</h4><p class="tv">{{ $stats['oec_released'] }}</p></div><i class="fa fa-file-signature"></i></div>
    <div class="tk-stat s"><div><h4>{{ $isAr ? 'تذاكر محجوزة' : 'Tickets Booked' }}</h4><p class="tv">{{ $stats['ticket_purchased'] }}</p></div><i class="fa fa-plane-departure"></i></div>
</div>

<section class="agent-section">
    <form action="{{ route('agent.philippines-tracking.index') }}" method="GET" class="agent-filter">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ $isAr ? 'بحث بالاسم أو الجواز أو الكفيل...' : 'Search name, passport, sponsor...' }}">
        <select name="stage">
            <option value="">{{ $isAr ? 'كل المراحل' : 'All Stages' }}</option>
            <option value="medical_fit" {{ request('stage')==='medical_fit'?'selected':'' }}>{{ $isAr ? 'لائق طبياً' : 'Medical FIT' }}</option>
            <option value="medical_pending" {{ request('stage')==='medical_pending'?'selected':'' }}>{{ $isAr ? 'فحص معلق' : 'Pending' }}</option>
            <option value="oec_released" {{ request('stage')==='oec_released'?'selected':'' }}>OEC Released</option>
            <option value="ticket_purchased" {{ request('stage')==='ticket_purchased'?'selected':'' }}>{{ $isAr ? 'تذكرة محجوزة' : 'Ticket Booked' }}</option>
        </select>
        <button type="submit" class="agent-button"><i class="fa fa-search"></i> {{ $isAr ? 'بحث' : 'Filter' }}</button>
    </form>

    <div class="agent-table-wrap">
        <table class="agent-table">
            <thead><tr>
                <th>#</th>
                <th>{{ $isAr ? 'العاملة' : 'Worker' }}</th>
                <th>{{ $isAr ? 'الكفيل' : 'Sponsor' }}</th>
                <th>{{ $isAr ? 'الإنجاز' : 'Progress' }}</th>
                <th>{{ $isAr ? 'طبي' : 'Medical' }}</th>
                <th>TESDA</th>
                <th>OWWA</th>
                <th>OEC</th>
                <th>{{ $isAr ? 'أختام' : 'Stamps' }}</th>
                <th>{{ $isAr ? 'تذكرة' : 'Ticket' }}</th>
                <th></th>
            </tr></thead>
            <tbody>
            @forelse($maids as $maid)
                @php
                    $p = $maid->recruitmentProcedure;
                    $ac = $maid->contracts->first();
                    $pct = 0;
                    if($p){
                        if($p->medical_status==='fit') $pct+=20;
                        if($p->tesda_status==='certified') $pct+=10;
                        if($p->owwa_status==='paid'||$p->owwa_status==='active') $pct+=10;
                        if($p->oec_payment_status==='paid') $pct+=10;
                        if($p->oec_release_status==='released') $pct+=10;
                        if($p->embassy_stamping_status==='stamped') $pct+=15;
                        if($p->poea_stamping_status==='approved') $pct+=10;
                        if($p->ticket_purchase_status==='purchased') $pct+=15;
                    }
                @endphp
                <tr id="row-{{ $maid->id }}">
                    <td>{{ $maid->id }}</td>
                    <td><div class="tk-worker"><img src="{{ $maid->photo_url }}" class="tk-avatar" alt=""><div><strong>{{ $maid->name }}</strong><span>{{ $maid->passport_num ?: '-' }}</span></div></div></td>
                    <td>@if($ac)<strong>{{ $ac->user?->name ?: '-' }}</strong><div style="font-size:11px;color:var(--tracking-success)"><i class="fa fa-check-circle"></i> #{{ $ac->id }}</div>@else <span class="agent-badge warning">{{ $isAr?'غير محجوزة':'N/A' }}</span>@endif</td>
                    <td style="min-width:90px"><span class="pct-lbl" style="font-size:11px;font-weight:700">{{ $pct }}%</span><div class="tk-prog"><div class="tk-prog-fill" style="width:{{ $pct }}%"></div></div></td>
                    <td>@php $v=$p?->medical_status?:'pending'; @endphp <span class="tk-badge {{ $v==='fit'?'done':($v==='unfit'?'fail':'pending') }}" onclick="openModal({{ $maid->id }},'med')"><i class="fa fa-stethoscope"></i> {{ $v==='fit'?($isAr?'لائق':'FIT'):($v==='unfit'?($isAr?'غير لائق':'UNFIT'):($isAr?'معلق':'Pending')) }}</span></td>
                    <td>@php $v=$p?->tesda_status?:'pending'; @endphp <span class="tk-badge {{ $v==='certified'?'done':($v==='failed'?'fail':'pending') }}" onclick="openModal({{ $maid->id }},'skills')"><i class="fa fa-graduation-cap"></i> {{ $v==='certified'?($isAr?'معتمد':'OK'):($isAr?'معلق':'Pending') }}</span></td>
                    <td>@php $v=$p?->owwa_status?:'pending'; @endphp <span class="tk-badge {{ ($v==='paid'||$v==='active')?'done':'pending' }}" onclick="openModal({{ $maid->id }},'skills')"><i class="fa fa-id-card"></i> {{ ($v==='paid'||$v==='active')?($isAr?'نشط':'Active'):($isAr?'معلق':'Pending') }}</span></td>
                    <td>@php $v=$p?->oec_release_status?:'pending'; @endphp <span class="tk-badge {{ $v==='released'?'done':'pending' }}" onclick="openModal({{ $maid->id }},'oec')"><i class="fa fa-file-shield"></i> {{ $v==='released'?($isAr?'جاهز':'Released'):($isAr?'معلق':'Pending') }}</span></td>
                    <td>@php $ok=($p?->embassy_stamping_status==='stamped'&&$p?->poea_stamping_status==='approved'); @endphp <span class="tk-badge {{ $ok?'done':'pending' }}" onclick="openModal({{ $maid->id }},'stamp')"><i class="fa fa-passport"></i> {{ $ok?($isAr?'موثق':'Done'):($isAr?'معلق':'Pending') }}</span></td>
                    <td>@php $v=$p?->ticket_purchase_status?:'pending'; @endphp <span class="tk-badge {{ $v==='purchased'?'done':'pending' }}" onclick="openModal({{ $maid->id }},'ticket')"><i class="fa fa-plane-up"></i> {{ $v==='purchased'?($isAr?'محجوز':'Booked'):($isAr?'معلق':'Pending') }}</span></td>
                    <td><button type="button" class="agent-button" onclick="openModal({{ $maid->id }},'med')"><i class="fa fa-pen-to-square"></i> {{ $isAr ? 'تعديل' : 'Edit' }}</button></td>
                </tr>
            @empty
                <tr><td colspan="11" style="text-align:center;color:var(--agent-muted)">{{ $isAr ? 'لا توجد بيانات' : 'No records found' }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="agent-pagination">{{ $maids->links() }}</div>
</section>

{{-- Modal --}}
<div class="tk-overlay" id="tkOverlay" onclick="closeModal()">
<div class="tk-modal" onclick="event.stopPropagation()">
    <div class="tk-mhead"><h3><i class="fa fa-clipboard-list"></i> <span id="mName">-</span></h3><button class="tk-mclose" onclick="closeModal()">&times;</button></div>
    <div class="tk-tabs">
        <button type="button" class="tk-tab active" data-tab="med"><i class="fa fa-stethoscope"></i> {{ $isAr?'طبي':'Medical' }}</button>
        <button type="button" class="tk-tab" data-tab="skills"><i class="fa fa-graduation-cap"></i> TESDA/OWWA</button>
        <button type="button" class="tk-tab" data-tab="oec"><i class="fa fa-file-shield"></i> OEC</button>
        <button type="button" class="tk-tab" data-tab="stamp"><i class="fa fa-passport"></i> {{ $isAr?'أختام':'Stamps' }}</button>
        <button type="button" class="tk-tab" data-tab="ticket"><i class="fa fa-plane"></i> {{ $isAr?'سفر':'Travel' }}</button>
        <button type="button" class="tk-tab" data-tab="gen"><i class="fa fa-file-lines"></i> {{ $isAr?'عام':'General' }}</button>
    </div>
    <form id="tkForm">
        @csrf
        <input type="hidden" id="fMaidId">
        <div class="tk-mbody">
            <div class="tk-pane active" id="pane-med"><div class="tk-grid">
                <div class="tk-fg"><label>{{ $isAr?'حالة الفحص':'Medical Status' }}</label><select id="f_medical_status"><option value="pending">{{ $isAr?'معلق':'Pending' }}</option><option value="fit">{{ $isAr?'لائق (FIT)':'FIT' }}</option><option value="unfit">{{ $isAr?'غير لائق (UNFIT)':'UNFIT' }}</option></select></div>
                <div class="tk-fg"><label>{{ $isAr?'التاريخ':'Date' }}</label><input type="date" id="f_medical_date"></div>
            </div></div>
            <div class="tk-pane" id="pane-skills"><div class="tk-grid">
                <div class="tk-fg"><label>TESDA</label><select id="f_tesda_status"><option value="pending">{{ $isAr?'معلق':'Pending' }}</option><option value="certified">{{ $isAr?'معتمد':'Certified' }}</option><option value="failed">{{ $isAr?'راسب':'Failed' }}</option></select></div>
                <div class="tk-fg"><label>{{ $isAr?'تاريخ TESDA':'TESDA Date' }}</label><input type="date" id="f_tesda_date"></div>
                <div class="tk-fg"><label>OWWA</label><select id="f_owwa_status"><option value="pending">{{ $isAr?'معلق':'Pending' }}</option><option value="paid">{{ $isAr?'مدفوع':'Paid' }}</option><option value="active">{{ $isAr?'نشط':'Active' }}</option></select></div>
                <div class="tk-fg"><label>{{ $isAr?'تاريخ OWWA':'OWWA Date' }}</label><input type="date" id="f_owwa_date"></div>
            </div></div>
            <div class="tk-pane" id="pane-oec"><div class="tk-grid">
                <div class="tk-fg"><label>{{ $isAr?'دفع الرسوم':'Payment' }}</label><select id="f_oec_payment_status"><option value="pending">{{ $isAr?'غير مدفوع':'Pending' }}</option><option value="paid">{{ $isAr?'مدفوع':'Paid' }}</option></select></div>
                <div class="tk-fg"><label>{{ $isAr?'تاريخ الدفع':'Payment Date' }}</label><input type="date" id="f_oec_payment_date"></div>
                <div class="tk-fg"><label>{{ $isAr?'الإصدار':'Release' }}</label><select id="f_oec_release_status"><option value="pending">{{ $isAr?'تحت الإجراء':'Pending' }}</option><option value="released">{{ $isAr?'صدر':'Released' }}</option></select></div>
                <div class="tk-fg"><label>{{ $isAr?'تاريخ الإصدار':'Release Date' }}</label><input type="date" id="f_oec_release_date"></div>
            </div></div>
            <div class="tk-pane" id="pane-stamp"><div class="tk-grid">
                <div class="tk-fg"><label>{{ $isAr?'ختم السفارة':'Embassy' }}</label><select id="f_embassy_stamping_status"><option value="pending">{{ $isAr?'معلق':'Pending' }}</option><option value="stamped">{{ $isAr?'مختوم':'Stamped' }}</option></select></div>
                <div class="tk-fg"><label>{{ $isAr?'تاريخ السفارة':'Embassy Date' }}</label><input type="date" id="f_embassy_stamping_date"></div>
                <div class="tk-fg"><label>POEA / DMW</label><select id="f_poea_stamping_status"><option value="pending">{{ $isAr?'معلق':'Pending' }}</option><option value="approved">{{ $isAr?'موثق':'Approved' }}</option></select></div>
                <div class="tk-fg"><label>{{ $isAr?'تاريخ POEA':'POEA Date' }}</label><input type="date" id="f_poea_stamping_date"></div>
            </div></div>
            <div class="tk-pane" id="pane-ticket"><div class="tk-grid">
                <div class="tk-fg"><label>{{ $isAr?'سداد التذكرة':'Payment' }}</label><select id="f_ticket_payment_status"><option value="pending">{{ $isAr?'غير مسدد':'Pending' }}</option><option value="paid">{{ $isAr?'مسدد':'Paid' }}</option></select></div>
                <div class="tk-fg"><label>{{ $isAr?'تاريخ السداد':'Pay Date' }}</label><input type="date" id="f_ticket_payment_date"></div>
                <div class="tk-fg"><label>{{ $isAr?'حجز التذكرة':'Booking' }}</label><select id="f_ticket_purchase_status"><option value="pending">{{ $isAr?'لم تحجز':'Pending' }}</option><option value="purchased">{{ $isAr?'محجوزة':'Booked' }}</option></select></div>
                <div class="tk-fg"><label>{{ $isAr?'تاريخ السفر':'Flight Date' }}</label><input type="date" id="f_ticket_purchase_date"></div>
            </div></div>
            <div class="tk-pane" id="pane-gen">
                <div class="tk-fg"><label>{{ $isAr?'تاريخ التقديم':'Application Date' }}</label><input type="date" id="f_applied_at" style="width:50%"></div>
                <div class="tk-fg"><label>{{ $isAr?'ملاحظات':'Remarks' }}</label><textarea id="f_remarks" rows="3" placeholder="{{ $isAr?'ملاحظات إضافية...':'Notes...' }}"></textarea></div>
            </div>
        </div>
        <div class="tk-mfoot">
            <button type="button" class="agent-button" style="background:#64748b" onclick="closeModal()">{{ $isAr?'إلغاء':'Cancel' }}</button>
            <button type="submit" class="agent-button" id="tkSaveBtn" style="background:#10b981"><i class="fa fa-check"></i> {{ $isAr?'حفظ':'Save' }}</button>
        </div>
    </form>
</div>
</div>
@endsection

@push('scripts')
<script>
const ML=@json($maids->items());
const CSRF='{{ csrf_token() }}';

// Tabs
document.querySelectorAll('.tk-tab').forEach(t=>{
    t.addEventListener('click',()=>{
        document.querySelectorAll('.tk-tab').forEach(x=>x.classList.remove('active'));
        document.querySelectorAll('.tk-pane').forEach(x=>x.classList.remove('active'));
        t.classList.add('active');
        document.getElementById('pane-'+t.dataset.tab).classList.add('active');
    });
});

function fmtDate(d){if(!d)return '';const x=new Date(d);return isNaN(x)?'':[x.getFullYear(),String(x.getMonth()+1).padStart(2,'0'),String(x.getDate()).padStart(2,'0')].join('-')}

const FIELDS=['medical_status','medical_date','tesda_status','tesda_date','owwa_status','owwa_date','oec_payment_status','oec_payment_date','oec_release_status','oec_release_date','embassy_stamping_status','embassy_stamping_date','poea_stamping_status','poea_stamping_date','ticket_payment_status','ticket_payment_date','ticket_purchase_status','ticket_purchase_date','applied_at','remarks'];

function openModal(id,tab){
    const m=ML.find(x=>x.id===id);if(!m)return;
    document.getElementById('fMaidId').value=id;
    document.getElementById('mName').textContent=m.name+' ('+(m.passport_num||'')+')';
    const pr=m.recruitment_procedure||{};
    FIELDS.forEach(f=>{
        const el=document.getElementById('f_'+f);
        if(!el)return;
        const v=pr[f]||'';
        el.value=f.includes('date')||f==='applied_at'?fmtDate(v):v||el.querySelector('option')?.value||'';
    });
    // activate tab
    document.querySelectorAll('.tk-tab').forEach(x=>x.classList.remove('active'));
    document.querySelectorAll('.tk-pane').forEach(x=>x.classList.remove('active'));
    const tb=document.querySelector('.tk-tab[data-tab="'+tab+'"]');
    if(tb)tb.classList.add('active');
    document.getElementById('pane-'+tab).classList.add('active');
    document.getElementById('tkOverlay').classList.add('show');
}
function closeModal(){document.getElementById('tkOverlay').classList.remove('show')}

document.getElementById('tkForm').addEventListener('submit',function(e){
    e.preventDefault();
    const id=document.getElementById('fMaidId').value;
    const btn=document.getElementById('tkSaveBtn');
    const orig=btn.innerHTML;
    btn.disabled=true;btn.innerHTML='<i class="fa fa-spinner fa-spin"></i>';
    const body={};
    FIELDS.forEach(f=>{const el=document.getElementById('f_'+f);if(el)body[f]=el.value});
    fetch('/agent/philippines-tracking/'+id+'/bulk-update',{
        method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
        body:JSON.stringify(body)
    }).then(r=>r.json()).then(res=>{
        if(res.success){
            // update row
            const row=document.getElementById('row-'+id);
            if(row){
                row.querySelector('.pct-lbl').textContent=res.percentage+'%';
                row.querySelector('.tk-prog-fill').style.width=res.percentage+'%';
            }
            // sync local data
            const m=ML.find(x=>x.id===parseInt(id));
            if(m){if(!m.recruitment_procedure)m.recruitment_procedure={};FIELDS.forEach(f=>{const el=document.getElementById('f_'+f);if(el)m.recruitment_procedure[f]=el.value})}
            closeModal();
            location.reload();
        }else{alert(res.message||'Error')}
    }).catch(()=>alert('Network error')).finally(()=>{btn.disabled=false;btn.innerHTML=orig});
});
</script>
@endpush
