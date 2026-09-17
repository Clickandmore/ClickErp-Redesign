<div class="card mb-3" id="shelter-cost-statement"><div class="card-body">
    <h4>كشف حساب إيواء العاملة</h4>
    <p class="text-muted">يشمل حركات دفتر الإيواء للعاملة عبر عقودها. المدين يزيد أصل الإيواء، والدائن يخفضه. كشف المورد يشمل تعاملات المكتب كافة.</p>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead><tr><th>التاريخ</th><th>القيد</th><th>العقد</th><th>الحركة</th><th>مدين (ريال)</th><th>دائن (ريال)</th><th>الرصيد (ريال)</th></tr></thead>
            <tbody>
                @php($shelterRunningBalance = 0)
                @forelse($shelterStatement as $entry)
                    @php($shelterRunningBalance = round($shelterRunningBalance + (float) $entry->amount, 2))
                    <tr>
                        <td>{{ substr((string) ($entry->constraint?->date ?: $entry->created_at), 0, 10) }}</td>
                        <td>@if($entry->constraint)<a href="{{ route('finance.constraints.show', $entry->constraint_id) }}">#{{ $entry->constraint->code ?: $entry->constraint_id }}</a>@else غير مثبت @endif</td>
                        <td>#{{ $entry->contract_id }}</td>
                        <td>{{ $entry->kindLabel() }}@if($entry->notes)<div class="text-muted">{{ $entry->notes }}</div>@endif</td>
                        <td>{{ number_format(max(0, (float) $entry->amount), 2) }}</td>
                        <td>{{ number_format(max(0, -(float) $entry->amount), 2) }}</td>
                        <td>{{ number_format($shelterRunningBalance, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center">لا توجد حركات مسجلة في دفتر إيواء العاملة.</td></tr>
                @endforelse
            </tbody>
            <tfoot><tr><th colspan="4">الإجمالي</th><th>{{ number_format($shelterStatement->where('amount', '>', 0)->sum('amount'), 2) }}</th><th>{{ number_format(-$shelterStatement->where('amount', '<', 0)->sum('amount'), 2) }}</th><th>{{ number_format($shelterRunningBalance, 2) }}</th></tr></tfoot>
        </table>
    </div>
</div></div>
