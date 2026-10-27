@php
    $liveMaids = $team->maidMembers->filter(fn ($m) => $m->maid !== null);
    $activeMaids = $liveMaids->where('is_active', true)->count();
@endphp
<div class="tab-pane" id="maids" role="tabpanel">
    <div class="row g-3">
        @if ($canEdit)
            <div class="col-lg-4">
                <div class="ht-card">
                    <div class="ht-head">
                        <div class="ht-head-icon"><i class="fa fa-user-plus"></i></div>
                        <div><h5>إضافة خادمات</h5><p>خادمات الإيجار بالساعة غير المرتبطات بالفريق</p></div>
                    </div>
                    <form method="POST" action="{{ route('admin.hourly-teams.maids', $team) }}">
                        @csrf
                        <div class="ht-body">
                            <div class="mb-3">
                                <label for="team-maid-ids" class="form-label">الخادمات</label>
                                <select id="team-maid-ids" name="maid_ids[]" class="form-select tm-multi" multiple required>
                                    @foreach ($maids as $maid)
                                        <option value="{{ $maid->id }}">{{ $maid->name }} — {{ $maid->passport_num }}{{ $maid->hourly_is_suspended ? ' (معلّقة)' : '' }}</option>
                                    @endforeach
                                </select>
                                @if ($maids->isEmpty())<div class="form-text">لا توجد خادمات إضافية متاحة للربط بهذا الفريق.</div>@endif
                            </div>
                            <label for="team-maid-role" class="form-label">نوع الارتباط</label>
                            <select id="team-maid-role" name="role" class="form-select">
                                <option value="primary">أساسي</option>
                                <option value="secondary">ثانوي</option>
                            </select>
                            <div class="form-text">الأساسي ينقل ارتباط العاملة الأساسي إلى هذا الفريق. الربط لا يغيّر حالة وصولها أو إيوائها أو إتاحتها.</div>
                        </div>
                        <div class="ht-foot"><button class="btn btn-primary rounded-3 flex-fill" {{ $team->is_active && $maids->isNotEmpty() ? '' : 'disabled' }}><i class="fa fa-plus ms-1"></i> إضافة إلى الفريق</button></div>
                    </form>
                </div>
            </div>
        @endif
        <div class="{{ $canEdit ? 'col-lg-8' : 'col-12' }}">
            <div class="ht-card">
                <div class="ht-head">
                    <div class="ht-head-icon"><i class="fa fa-users"></i></div>
                    <div><h5>خادمات الفريق</h5><p>نشطة {{ $activeMaids }} من {{ $liveMaids->count() }}</p></div>
                </div>
                <div class="ht-body">
                    @if ($liveMaids->isEmpty())
                        <div class="ht-empty"><i class="fa fa-users"></i> لا توجد خادمات مرتبطات بهذا الفريق.</div>
                    @else
                        <div class="table-responsive">
                            <table class="ht-table">
                                <thead><tr><th>العاملة</th><th>نوع الارتباط</th><th>الحالة</th>@if ($canEdit)<th>الإجراءات</th>@endif</tr></thead>
                                <tbody>
                                    @foreach ($liveMaids as $member)
                                        <tr>
                                            <td>
                                                <strong>{{ $member->maid->name }}</strong>
                                                <div class="small text-muted">الجواز <span class="ht-num" dir="ltr">{{ $member->maid->passport_num ?: '—' }}</span></div>
                                            </td>
                                            <td><span class="ht-chip {{ $member->role === 'primary' ? 'hd-chip-primary' : 'ht-chip-off' }}">{{ $member->role === 'primary' ? 'أساسي' : 'ثانوي' }}</span></td>
                                            <td>
                                                @if ($canEdit)
                                                    <div class="tm-status">
                                                        <button type="button" class="ht-toggle" role="switch" aria-checked="{{ $member->is_active ? 'true' : 'false' }}" aria-label="عضوية {{ $member->maid->name }} في الفريق"
                                                                data-status-url="{{ route('admin.hourly-teams.maid-member', [$team, $member->id]) }}"><span><i class="fa {{ $member->is_active ? 'fa-check' : 'fa-minus' }}"></i></span></button>
                                                        <span class="tm-status-text">{{ $member->is_active ? 'نشطة' : 'موقوفة' }}</span>
                                                    </div>
                                                @else
                                                    <span class="ht-chip {{ $member->is_active ? 'ht-chip-on' : 'ht-chip-off' }}">{{ $member->is_active ? 'نشطة' : 'موقوفة' }}</span>
                                                @endif
                                                @if ($member->maid->hourly_is_suspended)<div class="small text-warning mt-1"><i class="fa fa-triangle-exclamation ms-1"></i>العاملة معلّقة</div>@endif
                                            </td>
                                            @if ($canEdit)
                                                <td class="text-nowrap">
                                                    <form method="POST" action="{{ route('admin.hourly-teams.maid-member', [$team, $member->id]) }}" class="d-flex gap-1">
                                                        @csrf @method('PUT')
                                                        <button name="action" value="remove" class="btn btn-sm btn-outline-danger ht-icon-btn" title="إزالة من الفريق" data-confirm-title="إزالة العاملة" data-confirm="إزالة ارتباط العاملة بهذا الفريق؟" data-confirm-button="نعم، إزالة"><i class="fa fa-user-minus"></i></button>
                                                    </form>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
