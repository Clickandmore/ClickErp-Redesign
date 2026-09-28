<div class="tab-pane" id="maids" role="tabpanel">
    <div class="ht-card"><div class="ht-head"><h5>خادمات الفريق</h5></div><div class="ht-body">
        @if($canEdit)
        <form method="POST" action="{{ route('admin.hourly-teams.maids',$team) }}" class="row g-3 mb-4">
            @csrf
            <div class="col-md-7"><label for="team-maid-ids" class="form-label">إضافة خادمات ساعات</label>
                <select id="team-maid-ids" name="maid_ids[]" class="form-select tm-multi" multiple required>
                    @foreach($maids as $maid)<option value="{{ $maid->id }}">{{ $maid->name }} — {{ $maid->passport_num }}{{ $maid->hourly_is_suspended ? ' (معلّقة)' : '' }}</option>@endforeach
                </select>
                @if($maids->isEmpty())<div class="text-muted small mt-2">لا توجد خادمات إضافية متاحة للربط بهذا الفريق.</div>@endif
            </div>
            <div class="col-md-3"><label for="team-maid-role" class="form-label">نوع الارتباط</label><select id="team-maid-role" name="role" class="form-select"><option value="primary">فريق أساسي</option><option value="secondary">فريق ثانوي</option></select></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary" {{ $team->is_active && $maids->isNotEmpty() ? '' : 'disabled' }}>إضافة للفريق</button></div>
            <p class="text-muted small">اختيار فريق أساسي جديد ينقل الارتباط الأساسي إليه. ربط العاملة بالفريق لا يغيّر حالة وصولها أو إيوائها أو إتاحتها.</p>
        </form>
        @endif
        <div class="table-responsive"><table class="ht-table"><thead><tr><th>العاملة</th><th>الجواز</th><th>نوع الارتباط</th><th>حالة العضوية</th><th>الإجراءات</th></tr></thead><tbody>
            @forelse($team->maidMembers->filter(fn($member)=>$member->maid!==null) as $member)
            <tr><td>{{ $member->maid->name }}</td><td>{{ $member->maid->passport_num }}</td><td>{{ $member->role==='primary' ? 'أساسي' : 'ثانوي' }}</td><td>{{ $member->is_active ? 'نشطة' : 'متوقفة' }}</td><td>
                @if($canEdit)
                <form method="POST" action="{{ route('admin.hourly-teams.maid-member',[$team,$member->id]) }}" class="d-inline">@csrf @method('PUT')<button name="action" value="{{ $member->is_active ? 'deactivate' : 'activate' }}" class="btn btn-sm btn-outline-primary">{{ $member->is_active ? 'إيقاف العضوية' : 'تفعيل العضوية' }}</button></form>
                <form method="POST" action="{{ route('admin.hourly-teams.maid-member',[$team,$member->id]) }}" class="d-inline" data-confirm-title="إزالة العاملة" data-confirm="إزالة ارتباط العاملة بهذا الفريق؟" data-confirm-button="نعم، إزالة">@csrf @method('PUT')<button name="action" value="remove" class="btn btn-sm btn-outline-danger">إزالة من الفريق</button></form>
                @endif
            </td></tr>
            @empty<tr><td colspan="5" class="text-center py-4">لا توجد خادمات مرتبطات بهذا الفريق.</td></tr>@endforelse
        </tbody></table></div>
    </div></div>
</div>
