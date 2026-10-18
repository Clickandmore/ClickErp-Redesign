@extends('admin.layout.indexs.index')
@section('page-title','العهد النقدية للطاقم')
@push('css')
<style>
    /* العهد النقدية: ما حصّله الطاقم من العملاء نقدًا ولم يورّده للشركة بعد */
    .cc { --brand: #2794eb; --brand-ink: #1b6fb8; --brand-deep: #14507f; --teal: #00ac9a; --teal-deep: #068a7d; --ink: #16212e; --muted: #6b7a8c; --line: #e6ecf3; --soft: #f6f9fc; --amber: #e08a17; --coral: #e5484d; direction: rtl; }
    .cc-hero { position: relative; overflow: hidden; border-radius: 18px; padding: 1.3rem 1.4rem; margin-bottom: 1.25rem; color: #fff; background: linear-gradient(105deg, var(--brand-deep) 0%, var(--brand-ink) 45%, var(--brand) 80%, var(--teal) 140%); box-shadow: 0 10px 30px rgba(20, 80, 127, .12); }
    .cc-hero h3 { margin: 0; font-size: 1.3rem; font-weight: 700; display: flex; align-items: center; gap: .55rem; }
    .cc-hero p { margin: .35rem 0 0; font-size: .86rem; opacity: .9; max-width: 720px; }
    .cc-flow { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: 1rem; }
    .cc-flow span { display: inline-flex; align-items: center; gap: .4rem; background: rgba(255, 255, 255, .14); border: 1px solid rgba(255, 255, 255, .22); border-radius: 99px; padding: .3rem .75rem; font-size: .78rem; font-weight: 600; }
    .cc-flow span b { width: 20px; height: 20px; border-radius: 50%; display: grid; place-items: center; background: #fff; color: var(--brand-ink); font-size: .7rem; }
    .cc-flow i.fa-arrow-left { opacity: .7; align-self: center; font-size: .75rem; }

    .cc-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: .9rem; margin-bottom: 1.25rem; }
    .cc-stat { position: relative; background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: .95rem 1.1rem; display: flex; align-items: center; gap: .8rem; overflow: hidden; box-shadow: 0 6px 18px rgba(20, 80, 127, .05); }
    .cc-stat::before { content: ''; position: absolute; inset-block: 0; inset-inline-start: 0; width: 4px; background: var(--tone); }
    .cc-stat > i { width: 42px; height: 42px; flex: 0 0 42px; border-radius: 12px; display: grid; place-items: center; font-size: 1.05rem; color: var(--tone); background: var(--tint); }
    .cc-stat strong { display: block; font-size: 1.35rem; line-height: 1.2; color: var(--ink); font-variant-numeric: tabular-nums; }
    .cc-stat strong small { font-size: .72rem; color: var(--muted); font-weight: 600; }
    .cc-stat span { font-size: .8rem; color: var(--muted); }
    .cc-stat.is-main { background: linear-gradient(180deg, #fff8ec, #fff); }
    .cc-t-blue { --tone: #2794eb; --tint: #eaf5fe; }
    .cc-t-green { --tone: #17996f; --tint: #e8f7f1; }
    .cc-t-amber { --tone: #e08a17; --tint: #fef5e7; }
    .cc-t-violet { --tone: #6b63d6; --tint: #f0effc; }

    .cc-layout { display: grid; grid-template-columns: minmax(0, 1fr) 300px; gap: 1.25rem; align-items: start; }
    @media (max-width: 1199px) { .cc-layout { grid-template-columns: 1fr; } }
    .cc-card { background: #fff; border: 1px solid var(--line); border-radius: 16px; box-shadow: 0 6px 18px rgba(20, 80, 127, .05); margin-bottom: 1.25rem; }
    .cc-card-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; padding: .9rem 1.15rem; border-bottom: 1px solid var(--line); }
    .cc-card-head h5 { margin: 0; font-size: .98rem; font-weight: 700; color: var(--ink); display: flex; align-items: center; gap: .5rem; }
    .cc-card-head h5 i { color: var(--brand); }

    .cc-filter { display: flex; align-items: flex-end; gap: .65rem; flex-wrap: wrap; padding: .9rem 1.15rem; border-bottom: 1px solid var(--line); background: var(--soft); }
    .cc-filter label { display: block; font-size: .76rem; color: var(--muted); margin-bottom: .25rem; }
    .cc-filter .form-select, .cc-filter .form-control { min-width: 150px; border-radius: 10px; border-color: #dfe7ef; }
    .cc-filter .cc-check { display: inline-flex; align-items: center; gap: .4rem; font-size: .82rem; color: var(--ink); margin: 0 0 .5rem; }
    .cc-filter .btn { border-radius: 10px; font-weight: 700; }

    .cc-table { width: 100%; font-size: .85rem; border-collapse: collapse; }
    .cc-table th { font-size: .76rem; color: var(--muted); font-weight: 700; padding: .65rem 1rem; border-bottom: 1px solid var(--line); white-space: nowrap; text-align: start; background: #fbfcfe; }
    .cc-table td { padding: .85rem 1rem; border-bottom: 1px solid #eef2f6; vertical-align: top; }
    .cc-table tbody tr:hover > td { background: #fbfdff; }
    .cc-code { font-weight: 700; color: var(--brand-ink); text-decoration: none; font-variant-numeric: tabular-nums; }
    .cc-sub { display: block; font-size: .74rem; color: var(--muted); margin-top: .15rem; }
    .cc-ltr { direction: ltr; display: inline-block; font-variant-numeric: tabular-nums; }
    .cc-person { display: flex; align-items: center; gap: .55rem; min-width: 160px; }
    .cc-avatar { width: 34px; height: 34px; flex: 0 0 34px; border-radius: 50%; display: grid; place-items: center; font-size: .82rem; font-weight: 700; }
    .cc-avatar.is-worker { background: #f0effc; color: #4f47b8; }
    .cc-avatar.is-driver { background: #e6f7f5; color: var(--teal-deep); }
    .cc-person b { display: block; color: var(--ink); font-weight: 600; }
    .cc-money { min-width: 190px; }
    .cc-money-row { display: flex; justify-content: space-between; gap: .75rem; font-size: .8rem; color: var(--muted); }
    .cc-money-row b { color: var(--ink); font-variant-numeric: tabular-nums; }
    .cc-money-row.is-left b { color: #c2410c; font-size: .9rem; }
    .cc-bar { height: 6px; border-radius: 99px; background: #eef3f8; overflow: hidden; margin: .35rem 0; }
    .cc-bar span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, var(--teal-deep), var(--teal)); }
    .cc-badge { display: inline-flex; align-items: center; gap: .3rem; border-radius: 8px; padding: .18rem .55rem; font-size: .74rem; font-weight: 700; white-space: nowrap; border: 1px solid transparent; }
    .cc-b-amber { background: #fef5e7; color: #9a6206; border-color: #f7ddb2; }
    .cc-b-blue { background: #eaf5fe; color: #1b6fb8; border-color: #c4e2f8; }
    .cc-b-green { background: #e8f7f1; color: #0f7a5a; border-color: #bfe9d9; }
    .cc-remits { display: grid; gap: .3rem; margin-top: .5rem; }
    .cc-remit { display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; font-size: .76rem; color: var(--muted); background: var(--soft); border: 1px solid var(--line); border-radius: 8px; padding: .3rem .5rem; }
    .cc-remit a { font-weight: 700; color: var(--teal-deep); text-decoration: none; }
    .cc-remit b { color: var(--ink); font-variant-numeric: tabular-nums; }
    .cc-remit-form { margin-top: .55rem; }
    .cc-remit-form summary { display: inline-flex; align-items: center; gap: .4rem; cursor: pointer; list-style: none; background: var(--teal-deep); color: #fff; border-radius: 9px; padding: .38rem .75rem; font-size: .78rem; font-weight: 700; }
    .cc-remit-form summary::-webkit-details-marker { display: none; }
    .cc-remit-form[open] summary { background: #fff; color: var(--teal-deep); border: 1px solid #bfe9d9; }
    .cc-remit-form form { margin-top: .55rem; padding: .75rem; border: 1px solid #bfe9d9; border-radius: 12px; background: #f6fcfa; display: grid; gap: .5rem; min-width: 240px; }
    .cc-remit-form label { font-size: .76rem; color: var(--muted); margin: 0; }
    .cc-remit-form .form-control { border-radius: 9px; }
    .cc-remit-form .cc-confirm { display: flex; gap: .4rem; align-items: flex-start; font-size: .78rem; color: var(--ink); }
    .cc-remit-form .btn { border-radius: 9px; font-weight: 700; }

    .cc-holders { list-style: none; margin: 0; padding: .4rem 0; }
    .cc-holders li a { display: flex; align-items: center; gap: .6rem; padding: .55rem 1.15rem; text-decoration: none; color: inherit; }
    .cc-holders li a:hover, .cc-holders li a.active { background: #f3f9ff; }
    .cc-holders .cc-person { min-width: 0; flex: 1; }
    .cc-holders .cc-person b { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .84rem; }
    .cc-holders .cc-due { text-align: end; font-weight: 700; color: #c2410c; font-variant-numeric: tabular-nums; font-size: .86rem; white-space: nowrap; }
    .cc-note { display: flex; gap: .55rem; padding: .9rem 1.15rem; font-size: .8rem; color: var(--muted); }
    .cc-note i { color: var(--brand); margin-top: .15rem; }
    .cc-empty { text-align: center; padding: 2.4rem 1rem; color: var(--muted); }
    .cc-empty i { font-size: 1.8rem; color: #bfe9d9; display: block; margin-bottom: .55rem; }
    .cc-pager { padding: .75rem 1.15rem; }
    .cc-pager:empty { display: none; }
</style>
@endpush
@section('content')
@php
    $money = fn ($amount) => number_format((float) $amount, 2);
    $holderName = fn ($type, $id) => $type === 'worker' ? ($workers[$id] ?? '#'.$id) : ($drivers[$id] ?? '#'.$id);
    $holderLabel = fn ($type) => $type === 'worker' ? 'عاملة' : 'سائق';
    $filtered = request()->filled('holder_type') || request()->filled('holder_id');
@endphp
<div class="container-fluid cc">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <section class="cc-hero">
        <h3><i class="fa fa-hand-holding-dollar"></i> العهد النقدية لدى الطاقم</h3>
        <p>المبالغ التي حصّلتها العاملات والسائقون من العملاء نقدًا، وتبقى عهدة عليهم حتى يستلمها موظف مخوّل ويسجّل سند التوريد.</p>
        <div class="cc-flow" aria-label="مراحل العهدة">
            <span><b>1</b> العميل يدفع للطاقم نقدًا</span><i class="fa fa-arrow-left"></i>
            <span><b>2</b> يُسجَّل المبلغ مدفوعًا على الطلب</span><i class="fa fa-arrow-left"></i>
            <span><b>3</b> يبقى عهدة على المحصّل</span><i class="fa fa-arrow-left"></i>
            <span><b>4</b> الموظف يستلم ويسجّل سند التوريد</span>
        </div>
    </section>

    <div class="cc-stats">
        <div class="cc-stat cc-t-amber is-main"><i class="fa fa-sack-dollar"></i><div><strong>{{ $money($outstanding) }} <small>ر.س</small></strong><span>نقد متبقٍ لدى الطاقم</span></div></div>
        <div class="cc-stat cc-t-violet"><i class="fa fa-folder-open"></i><div><strong>{{ (int) $totals->open_count }}</strong><span>عهد مفتوحة لم تُورَّد بالكامل</span></div></div>
        <div class="cc-stat cc-t-blue"><i class="fa fa-hand-holding-dollar"></i><div><strong>{{ $money($totals->collected) }} <small>ر.س</small></strong><span>إجمالي المحصّل نقدًا</span></div></div>
        <div class="cc-stat cc-t-green"><i class="fa fa-building-columns"></i><div><strong>{{ $money($totals->remitted) }} <small>ر.س</small></strong><span>تم توريده للشركة</span></div></div>
    </div>

    <div class="cc-layout">
        <main class="cc-card">
            <header class="cc-card-head">
                <h5><i class="fa fa-receipt"></i> سندات التحصيل النقدي</h5>
                @if($filtered)<a href="{{ route('admin.hourly-cash.index', request()->only('all')) }}" class="cc-badge cc-b-blue text-decoration-none"><i class="fa fa-xmark"></i> إلغاء التصفية</a>@endif
            </header>
            <form class="cc-filter" method="GET">
                <div>
                    <label for="cc-holder-type">المحصّل</label>
                    <select id="cc-holder-type" name="holder_type" class="form-select">
                        <option value="">الكل</option>
                        <option value="worker" {{ request('holder_type') === 'worker' ? 'selected' : '' }}>العاملات</option>
                        <option value="driver" {{ request('holder_type') === 'driver' ? 'selected' : '' }}>السائقون</option>
                    </select>
                </div>
                <div>
                    <label for="cc-holder-id">رقم المحصّل</label>
                    <input id="cc-holder-id" class="form-control" type="number" name="holder_id" value="{{ request('holder_id') }}" min="1" placeholder="مثال: 4103">
                </div>
                <label class="cc-check"><input type="checkbox" name="all" value="1" {{ request('all') ? 'checked' : '' }}> عرض العهد المورّدة بالكامل أيضًا</label>
                <button class="btn btn-primary"><i class="fa fa-filter"></i> عرض</button>
            </form>

            @if($entries->isEmpty())
                <div class="cc-empty"><i class="fa fa-circle-check"></i> لا توجد عهد نقدية مطابقة{{ request('all') ? '' : '، كل المبالغ مورّدة' }}.<span class="cc-sub">السجلات القديمة قبل تفعيل العهد النقدية لا تُضاف تلقائيًا.</span></div>
            @else
                <div class="table-responsive">
                    <table class="cc-table">
                        <thead><tr><th>سند التحصيل</th><th>المحصّل</th><th>الطلب / الزيارة</th><th>المبالغ</th><th>الحالة</th><th>التوريد</th></tr></thead>
                        <tbody>
                        @foreach($entries as $entry)
                            @php
                                $cashLeft = round($entry->amount - $entry->remitted_amount, 2);
                                $share = $entry->amount > 0 ? min(100, round($entry->remitted_amount / $entry->amount * 100)) : 100;
                                [$stateClass, $stateIcon, $stateText] = $cashLeft <= 0 ? ['cc-b-green', 'fa-circle-check', 'مورّدة بالكامل'] : ($entry->remitted_amount > 0 ? ['cc-b-blue', 'fa-circle-half-stroke', 'مورّدة جزئيًا'] : ['cc-b-amber', 'fa-hourglass-half', 'لدى المحصّل']);
                                $name = $holderName($entry->holder_type, $entry->holder_id);
                            @endphp
                            <tr>
                                <td>
                                    <a class="cc-code" target="_blank" rel="noopener" href="{{ route('admin.hourly-cash.receipt', $entry->id) }}"><i class="fa fa-file-invoice"></i> HC-{{ str_pad($entry->payment_id, 8, '0', STR_PAD_LEFT) }}</a>
                                    <span class="cc-sub cc-ltr">{{ \Illuminate\Support\Carbon::parse($entry->created_at)->format('Y-m-d H:i') }}</span>
                                </td>
                                <td>
                                    <div class="cc-person">
                                        <span class="cc-avatar is-{{ $entry->holder_type }}"><i class="fa {{ $entry->holder_type === 'worker' ? 'fa-user-nurse' : 'fa-van-shuttle' }}"></i></span>
                                        <span><b>{{ $name }}</b><span class="cc-sub">{{ $holderLabel($entry->holder_type) }} · <span class="cc-ltr">#{{ $entry->holder_id }}</span></span></span>
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ route('admin.hourly-orders.show', $entry->order_id) }}" class="cc-code">طلب #{{ $orderNumbers[$entry->order_id] ?? $entry->order_id }}</a>
                                    <span class="cc-sub">زيارة <span class="cc-ltr">#{{ $entry->visit_id }}</span></span>
                                </td>
                                <td class="cc-money">
                                    <div class="cc-money-row"><span>محصّل</span><b>{{ $money($entry->amount) }}</b></div>
                                    <div class="cc-bar" role="progressbar" aria-valuenow="{{ $share }}" aria-valuemin="0" aria-valuemax="100" aria-label="نسبة التوريد"><span style="width: {{ $share }}%"></span></div>
                                    <div class="cc-money-row"><span>مورّد</span><b>{{ $money($entry->remitted_amount) }}</b></div>
                                    <div class="cc-money-row {{ $cashLeft > 0 ? 'is-left' : '' }}"><span>متبقٍ</span><b>{{ $money($cashLeft) }} ر.س</b></div>
                                </td>
                                <td><span class="cc-badge {{ $stateClass }}"><i class="fa {{ $stateIcon }}"></i> {{ $stateText }}</span></td>
                                <td>
                                    @if(!empty($remittances[$entry->id]))
                                        <div class="cc-remits">
                                            @foreach($remittances[$entry->id] as $remit)
                                                <div class="cc-remit">
                                                    <a target="_blank" rel="noopener" href="{{ route('admin.hourly-cash.remittance-receipt', $remit->id) }}"><i class="fa fa-file-circle-check"></i> HT-{{ $remit->id }}</a>
                                                    <b>{{ $money($remit->amount) }} ر.س</b>
                                                    @if($remit->reference)<span>· {{ $remit->reference }}</span>@endif
                                                    <span>· استلمه {{ $receivers[$remit->admin_id] ?? '#'.$remit->admin_id }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if($cashLeft > 0)
                                        <details class="cc-remit-form">
                                            <summary><i class="fa fa-hand-holding-dollar"></i> تأكيد استلام توريد</summary>
                                            <form method="POST" action="{{ route('admin.hourly-cash.remit', $entry->id) }}">
                                                @csrf
                                                <input type="hidden" name="request_token" value="{{ \Illuminate\Support\Str::uuid() }}">
                                                <div><label for="cc-amount-{{ $entry->id }}">المبلغ المستلم فعليًا (ر.س) — الحد {{ $money($cashLeft) }}</label><input id="cc-amount-{{ $entry->id }}" class="form-control" name="amount" type="number" min="0.01" max="{{ $cashLeft }}" step="0.01" required></div>
                                                <div><label for="cc-ref-{{ $entry->id }}">مرجع أو وصف سند التوريد</label><input id="cc-ref-{{ $entry->id }}" class="form-control" name="reference" maxlength="255" required></div>
                                                <label class="cc-confirm"><input type="checkbox" name="confirmed" value="1" required> أؤكد استلام المبلغ فعليًا من {{ $name }}.</label>
                                                <button class="btn btn-success btn-sm"><i class="fa fa-file-circle-check"></i> تسجيل سند التوريد</button>
                                            </form>
                                        </details>
                                    @elseif(empty($remittances[$entry->id]))
                                        <span class="cc-sub">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            <div class="cc-pager">{{ $entries->links() }}</div>
        </main>

        <aside>
            <div class="cc-card">
                <header class="cc-card-head"><h5><i class="fa fa-users"></i> أرصدة المحصّلين</h5></header>
                @if($holders->isEmpty())
                    <div class="cc-empty"><i class="fa fa-circle-check"></i> لا يوجد نقد متبقٍ لدى أي محصّل.</div>
                @else
                    <ul class="cc-holders">
                        @foreach($holders as $holder)
                            @php $active = request('holder_type') === $holder->holder_type && (string) request('holder_id') === (string) $holder->holder_id; @endphp
                            <li>
                                <a href="{{ route('admin.hourly-cash.index', ['holder_type' => $holder->holder_type, 'holder_id' => $holder->holder_id]) }}" class="{{ $active ? 'active' : '' }}" title="عرض عهد {{ $holderName($holder->holder_type, $holder->holder_id) }}">
                                    <div class="cc-person">
                                        <span class="cc-avatar is-{{ $holder->holder_type }}"><i class="fa {{ $holder->holder_type === 'worker' ? 'fa-user-nurse' : 'fa-van-shuttle' }}"></i></span>
                                        <span style="min-width:0"><b>{{ $holderName($holder->holder_type, $holder->holder_id) }}</b><span class="cc-sub">{{ $holderLabel($holder->holder_type) }} · {{ $holder->entries }} {{ $holder->entries == 1 ? 'عهدة' : 'عهد' }}</span></span>
                                    </div>
                                    <span class="cc-due">{{ $money($holder->due) }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <div class="cc-card">
                <p class="cc-note mb-0"><i class="fa fa-circle-info"></i><span>التوريد لا يزيد مدفوع العميل، فالدفع سُجّل عند التحصيل. واسترداد العميل لا يغلق عهدة المحصّل تلقائيًا؛ تبقى العهدة حتى يستلمها الموظف المخوّل ويسجّل التوريد.</span></p>
            </div>
        </aside>
    </div>
</div>
@endsection
