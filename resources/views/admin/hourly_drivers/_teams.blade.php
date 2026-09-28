<div class="mb-3">
    <label for="primary_team_id" class="form-label">الفريق الأساسي</label>
    <select id="primary_team_id" name="primary_team_id" class="form-select">
        <option value="">بدون فريق أساسي</option>
        @foreach($teams as $team)<option value="{{ $team->id }}" {{ (string)old('primary_team_id', $primaryTeamId)===(string)$team->id ? 'selected' : '' }}>{{ $team->name }}{{ $team->is_active ? '' : ' (متوقف)' }}</option>@endforeach
    </select>
</div>
<div class="mb-3">
    <label for="secondary_team_ids" class="form-label">الفرق الثانوية</label>
    <select id="secondary_team_ids" name="secondary_team_ids[]" class="form-select" multiple>
        @foreach($teams as $team)<option value="{{ $team->id }}" {{ in_array($team->id, old('secondary_team_ids', session()->hasOldInput() ? [] : $secondaryTeamIds)) ? 'selected' : '' }}>{{ $team->name }}{{ $team->is_active ? '' : ' (متوقف)' }}</option>@endforeach
    </select>
    <small class="text-muted">يمكن اختيار أكثر من فريق ثانوي. الفريق الأساسي لا يتكرر ضمن الفرق الثانوية.</small>
</div>
