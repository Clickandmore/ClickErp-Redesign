{{-- عمود فرع السطر. القيمة الفارغة تعني "فرع رأس القيد" ويملؤها الخادم. --}}
@php
    $allowedLineBranchIds = collect($lineBranchAllowedIds ?? $lineBranches->pluck('id'))
        ->map(fn ($id) => (int) $id);
@endphp
<td>
    <select name="detail_branch_id[]" class="form-control js-line-branch">
        <option value="">فرع القيد</option>
        @foreach ($lineBranches as $branch)
            @php
                $isSelectedLineBranch = (int) ($selectedLineBranchId ?? 0) === (int) $branch->id;
                $canChooseLineBranch = $allowedLineBranchIds->contains((int) $branch->id);
            @endphp
            <option value="{{ $branch->id }}" @selected($isSelectedLineBranch)
                @disabled(! $canChooseLineBranch && ! $isSelectedLineBranch)
                data-branch-allowed="{{ $canChooseLineBranch ? '1' : '0' }}">
                {{ $branch->name_ar ?: $branch->name_en }}
            </option>
        @endforeach
    </select>
</td>
