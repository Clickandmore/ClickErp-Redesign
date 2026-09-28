{{--
    مودال «الفرق»: الفرق المرتبطة بالمنطقة مع بحث وتقسيم صفحات، وإضافة/إزالة ثم حفظ لمن يملك صلاحية التعديل.
    يُفتح بزر يحمل data-teams (JSON: id, name, url, teams[{id, name, active, coverage, manage_url}]).
    يتطلب: $teams (الخيارات المتاحة)، $canEdit
--}}
<style>
    #teamsModal .tm-toolbar { display: flex; justify-content: space-between; align-items: center; gap: .75rem; flex-wrap: wrap; margin-bottom: 1rem; }
    #teamsModal .tm-toolbar label { display: flex; align-items: center; gap: .45rem; margin: 0; font-size: .85rem; color: var(--ht-muted); white-space: nowrap; }
    #teamsModal .tm-toolbar .form-select { width: 80px; }
    #teamsModal .tm-toolbar input[type="search"] { width: 220px; }
    #teamsModal .tm-table thead th { background: var(--ht-soft); }
    #teamsModal .tm-table tr.is-removed td { opacity: .45; text-decoration: line-through; }
    #teamsModal .tm-table tr.is-added td:first-child { box-shadow: inset -3px 0 0 #22c55e; }
    #teamsModal .tm-foot { display: flex; justify-content: space-between; align-items: center; gap: .75rem; flex-wrap: wrap; margin-top: 1rem; font-size: .85rem; color: var(--ht-muted); }
    #teamsModal .tm-pages { display: flex; gap: .35rem; }
    #teamsModal .tm-pages button { width: 34px; height: 34px; border-radius: 50%; border: 0; background: var(--ht-soft); color: var(--ht-ink); }
    #teamsModal .tm-pages button.active { background: #6861ee; color: #fff; }
    #teamsModal .tm-pages button:disabled { opacity: .45; }
    #teamsModal .tm-add { display: flex; gap: .5rem; padding: .75rem; border: 1px dashed var(--ht-line); border-radius: 12px; background: var(--ht-soft); margin-bottom: 1rem; }
</style>

<div class="modal fade ht-page" id="teamsModal" tabindex="-1" aria-labelledby="teamsModalLabel" aria-hidden="true" dir="rtl">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header">
                <h5 class="modal-title" id="teamsModalLabel">فرق المنطقة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <div id="tm-feedback" class="alert border-0 rounded-3 d-none" role="status"></div>

                @if ($canEdit)
                    <div class="tm-add">
                        <select class="form-select" id="tm-add-select" aria-label="اختر فريقًا لإضافته">
                            <option value="">اختر فريقًا لإضافته إلى المنطقة</option>
                            @foreach ($teams as $team)
                                <option value="{{ $team->id }}" data-name="{{ $team->name }}" data-active="{{ $team->is_active ? 1 : 0 }}" data-url="{{ route('admin.hourly-teams.manage', $team) }}#areas">{{ $team->name }}{{ $team->is_active ? '' : ' (متوقف)' }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-primary rounded-3 text-nowrap" id="tm-add"><i class="fa fa-plus ms-1"></i> إضافة</button>
                    </div>
                @endif

                <div class="tm-toolbar">
                    <label>أظهر
                        <select class="form-select form-select-sm" id="tm-per-page">
                            @foreach ([10, 25, 50] as $size)<option value="{{ $size }}">{{ $size }}</option>@endforeach
                        </select>
                        مدخلات
                    </label>
                    <label>ابحث: <input type="search" class="form-control form-control-sm" id="tm-search" aria-label="بحث في الفرق"></label>
                </div>

                <div class="table-responsive">
                    <table class="ht-table tm-table">
                        <thead>
                            <tr>
                                <th style="width: 90px"><button type="button" class="ht-sort btn btn-link p-0" data-sort="id"># <i class="fa fa-sort"></i></button></th>
                                <th><button type="button" class="ht-sort btn btn-link p-0" data-sort="name">الفريق <i class="fa fa-sort"></i></button></th>
                                <th>التغطية</th>
                                @if ($canEdit)<th style="width: 60px"></th>@endif
                            </tr>
                        </thead>
                        <tbody id="tm-rows"></tbody>
                    </table>
                </div>

                <div class="tm-foot">
                    <span id="tm-info"></span>
                    <div class="tm-pages" id="tm-pages"></div>
                </div>
            </div>
            <div class="modal-footer">
                @if ($canEdit)
                    <button type="button" class="btn btn-primary rounded-3 px-4" id="tm-save"><i class="fa fa-floppy-disk ms-1"></i> حفظ</button>
                @endif
                <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
(() => {
    const modal = document.getElementById('teamsModal');
    if (!modal) return;
    const $ = id => document.getElementById(id);
    const canEdit = @json($canEdit);
    let record = null, trigger = null, rows = [], page = 1, sort = {key: 'name', dir: 1};

    function feedback(text, type = 'info') {
        const el = $('tm-feedback');
        el.className = text ? 'alert border-0 rounded-3 alert-' + type : 'alert border-0 rounded-3 d-none';
        el.textContent = text || '';
    }
    const dirty = () => rows.some(r => r.added || r.removed);

    function render() {
        const query = $('tm-search').value.trim().toLowerCase();
        const perPage = Number($('tm-per-page').value);
        const list = rows.filter(r => !query || r.name.toLowerCase().includes(query) || String(r.id).includes(query))
            .sort((a, b) => (a[sort.key] > b[sort.key] ? 1 : a[sort.key] < b[sort.key] ? -1 : 0) * sort.dir);
        const pages = Math.max(1, Math.ceil(list.length / perPage));
        page = Math.min(page, pages);
        const start = (page - 1) * perPage, shown = list.slice(start, start + perPage);

        const body = $('tm-rows');
        body.replaceChildren();
        if (!shown.length) {
            const tr = body.insertRow(), td = tr.insertCell();
            td.colSpan = canEdit ? 4 : 3;
            td.className = 'text-center text-muted py-4';
            td.textContent = rows.length ? 'لا توجد نتائج مطابقة.' : 'لا توجد فرق مرتبطة بهذه المنطقة.';
        }
        shown.forEach(team => {
            const tr = body.insertRow();
            tr.className = team.removed ? 'is-removed' : (team.added ? 'is-added' : '');
            tr.insertCell().textContent = team.id;
            const name = tr.insertCell();
            const link = Object.assign(document.createElement('a'), {href: team.manage_url, textContent: team.name});
            name.append(link);
            if (!team.active) name.append(Object.assign(document.createElement('span'), {className: 'text-muted small me-1', textContent: ' (متوقف)'}));
            const coverage = tr.insertCell();
            coverage.append(Object.assign(document.createElement('span'), {
                className: 'ht-chip ' + (team.added ? 'hd-chip-primary' : (team.coverage ? 'ht-chip-on' : 'ht-chip-off')),
                textContent: team.added ? 'جديد — يُحفظ' : (team.coverage ? 'مفعلة' : 'متوقفة'),
            }));
            if (canEdit) {
                const action = tr.insertCell();
                const button = Object.assign(document.createElement('button'), {type: 'button', className: 'btn btn-sm ht-icon-btn ' + (team.removed ? 'btn-outline-success' : 'btn-outline-danger'), title: team.removed ? 'تراجع عن الإزالة' : 'إزالة من المنطقة'});
                button.innerHTML = '<i class="fa ' + (team.removed ? 'fa-rotate-left' : 'fa-xmark') + '"></i>';
                button.addEventListener('click', () => {
                    if (team.added) rows = rows.filter(r => r !== team); else team.removed = !team.removed;
                    render();
                });
                action.append(button);
            }
        });

        $('tm-info').textContent = list.length
            ? 'إظهار ' + (start + 1) + ' إلى ' + (start + shown.length) + ' من أصل ' + list.length + ' مدخل'
            : 'إظهار 0 مدخل';
        const pager = $('tm-pages');
        pager.replaceChildren();
        const pageButton = (label, target, opts = {}) => {
            const b = Object.assign(document.createElement('button'), {type: 'button', innerHTML: label, disabled: !!opts.disabled, className: opts.active ? 'active' : ''});
            b.addEventListener('click', () => { page = target; render(); });
            pager.append(b);
        };
        pageButton('<i class="fa fa-angle-right"></i>', page - 1, {disabled: page === 1});
        for (let p = 1; p <= pages; p++) pageButton(String(p), p, {active: p === page});
        pageButton('<i class="fa fa-angle-left"></i>', page + 1, {disabled: page === pages});

        modal.querySelectorAll('[data-sort]').forEach(b => b.querySelector('i').className = 'fa ' + (b.dataset.sort === sort.key ? (sort.dir === 1 ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort'));
        if (canEdit) {
            const linked = new Set(rows.map(r => r.id));
            Array.from($('tm-add-select').options).forEach(o => { if (o.value) o.hidden = linked.has(Number(o.value)); });
        }
    }

    modal.addEventListener('show.bs.modal', event => {
        if (!event.relatedTarget || !event.relatedTarget.dataset.teams) return;
        trigger = event.relatedTarget;
        record = JSON.parse(trigger.dataset.teams);
        rows = record.teams.map(t => Object.assign({}, t));
        page = 1;
        $('teamsModalLabel').textContent = 'فرق منطقة ' + record.name;
        $('tm-search').value = '';
        if (canEdit) $('tm-add-select').value = '';
        feedback('');
        render();
    });
    modal.addEventListener('hide.bs.modal', event => {
        if (canEdit && dirty() && !confirm('توجد تغييرات غير محفوظة على فرق المنطقة. إغلاق دون حفظ؟')) event.preventDefault();
    });
    $('tm-search').addEventListener('input', () => { page = 1; render(); });
    $('tm-per-page').addEventListener('change', () => { page = 1; render(); });
    modal.querySelectorAll('[data-sort]').forEach(b => b.addEventListener('click', () => {
        sort = {key: b.dataset.sort, dir: sort.key === b.dataset.sort ? -sort.dir : 1};
        render();
    }));

    if (!canEdit) return;

    $('tm-add').addEventListener('click', () => {
        const option = $('tm-add-select').selectedOptions[0];
        if (!option || !option.value) { feedback('اختر فريقًا أولًا.', 'warning'); return; }
        if (option.dataset.active !== '1') { feedback('لا يمكن ربط فريق متوقف بالمنطقة.', 'warning'); return; }
        rows.push({id: Number(option.value), name: option.dataset.name, active: true, coverage: true, manage_url: option.dataset.url, added: true});
        $('tm-add-select').value = '';
        feedback('');
        render();
    });

    $('tm-save').addEventListener('click', async () => {
        const button = $('tm-save');
        button.disabled = true;
        try {
            const response = await fetch(record.url, {
                method: 'PUT', credentials: 'same-origin',
                headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token())},
                body: JSON.stringify({team_ids: rows.filter(r => !r.removed).map(r => r.id)}),
            });
            let data = {};
            try { data = await response.json(); } catch (e) {}
            if (!response.ok) throw new Error(data.errors ? Object.values(data.errors).flat()[0] : 'تعذر حفظ فرق المنطقة.');
            if (!Array.isArray(data.teams)) throw new Error('يرجى تسجيل الدخول مجددًا.');
            record.teams = data.teams;
            trigger.dataset.teams = JSON.stringify(record);
            rows = data.teams.map(t => Object.assign({}, t));
            const count = trigger.querySelector('[data-teams-count]');
            if (count) count.textContent = data.teams.length;
            feedback(data.message, 'success');
            render();
        } catch (e) {
            feedback(e.message, 'danger');
        } finally {
            button.disabled = false;
        }
    });
})();
</script>
@endpush
