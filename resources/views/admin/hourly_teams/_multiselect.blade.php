{{--
    قائمة اختيار متعدد لموديول الساعات: تحوّل كل select[multiple] داخل .ht-page إلى قائمة بمربعات اختيار
    مع بحث وعدّاد و«تحديد الكل / إلغاء» ووسوم للعناصر المختارة.
    الـ select الأصلي يبقى هو مصدر القيمة (للإرسال والتحقق والسكربتات القائمة):
    - تغيير القيم برمجيًا: غيّر option.selected ثم أطلق حدث change على الـ select.
    - استبدال الخيارات (مثل الأحياء حسب المدينة) يُلتقط تلقائيًا.
    خصائص اختيارية على الـ select: data-placeholder (نص البحث)، data-empty (نص عند عدم وجود خيارات).
--}}
<style>
    .hms { position: relative; border: 1px solid #dee2e6; border-radius: 12px; background: #fff; overflow: hidden; }
    .hms:focus-within { border-color: #a5b4fc; box-shadow: 0 0 0 3px rgba(104, 97, 238, .15); }
    .hms.is-invalid { border-color: #dc3545; }
    .hms.is-invalid ~ .invalid-feedback { display: block; }
    .hms-native { position: absolute !important; inset-inline-start: 50%; bottom: 0; width: 1px !important; height: 1px !important; min-height: 0 !important; padding: 0 !important; border: 0 !important; opacity: 0; pointer-events: none; }
    .hms-tags { display: flex; flex-wrap: wrap; gap: .35rem; padding: .55rem .65rem 0; }
    .hms-tags:empty { display: none; }
    .hms-tag { display: inline-flex; align-items: center; gap: .3rem; max-width: 100%; padding: .2rem .35rem .2rem .6rem; border-radius: 999px; background: #eef2ff; color: #3730a3; font-size: .8rem; font-weight: 600; }
    .hms-tag span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .hms-tag button { width: 18px; height: 18px; border: 0; border-radius: 50%; background: rgba(55, 48, 163, .12); color: inherit; display: grid; place-items: center; font-size: .65rem; padding: 0; flex: 0 0 auto; }
    .hms-tag button:hover { background: #3730a3; color: #fff; }
    .hms-tag.is-more { background: #f1f5f9; color: #475569; padding-inline-end: .6rem; cursor: pointer; }
    .hms-bar { display: flex; align-items: center; gap: .5rem; padding: .55rem .65rem; border-bottom: 1px solid #f1f5f9; }
    .hms-search { position: relative; flex: 1 1 auto; }
    .hms-search i { position: absolute; top: 50%; inset-inline-start: .7rem; transform: translateY(-50%); color: #94a3b8; font-size: .8rem; pointer-events: none; }
    .hms-search input { width: 100%; height: 34px; border: 1px solid #e5e7eb; border-radius: 8px; padding-inline: 2rem .6rem; font-size: .85rem; background: #f8fafc; outline: 0; }
    .hms-search input:focus { background: #fff; border-color: #c7d2fe; }
    .hms-count { font-size: .75rem; font-weight: 700; color: #4338ca; background: #eef2ff; border-radius: 999px; padding: .15rem .55rem; white-space: nowrap; }
    .hms-count.is-zero { color: #64748b; background: #f1f5f9; }
    .hms-link { border: 0; background: none; padding: 0 .15rem; font-size: .78rem; color: #4f46e5; white-space: nowrap; }
    .hms-link:hover { text-decoration: underline; }
    .hms-link:disabled { color: #cbd5e1; text-decoration: none; }
    .hms-list { max-height: 230px; overflow-y: auto; padding: .3rem; }
    .hms-option { display: flex; align-items: center; gap: .6rem; margin: 0; padding: .45rem .55rem; border-radius: 8px; cursor: pointer; font-size: .88rem; color: #1e293b; user-select: none; }
    .hms-option:hover { background: #f8fafc; }
    .hms-option.is-checked { background: #f5f7ff; }
    .hms-option input { width: 1.05em; height: 1.05em; margin: 0; flex: 0 0 auto; cursor: pointer; accent-color: #6861ee; }
    .hms-option.is-disabled { opacity: .5; cursor: not-allowed; }
    .hms-option mark { padding: 0; background: #fef08a; color: inherit; border-radius: 2px; }
    .hms-empty { padding: 1rem; text-align: center; font-size: .82rem; color: #94a3b8; }

    /* الوضع المضغوط: حقل بحجم select ولوحة منسدلة فوق المحتوى */
    .hms-compact { overflow: visible; border-color: #dfe7ef; border-radius: 10px; }
    .hms-compact:focus-within { box-shadow: none; }
    .hms-compact.is-open { border-color: #a5b4fc; box-shadow: 0 0 0 3px rgba(104, 97, 238, .15); }
    .hms-trigger { display: flex; align-items: center; gap: .45rem; width: 100%; min-height: 40px; padding: 0 .8rem; border: 0; background: none; text-align: start; font-size: .85rem; color: #64748b; border-radius: inherit; }
    .hms-trigger:disabled { cursor: not-allowed; opacity: .6; }
    .hms-summary { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .hms-compact.has-value { border-color: #9fd0f4; background: #f2f9ff; }
    .hms-compact.has-value .hms-summary { color: #14507f; font-weight: 700; }
    .hms-badge { flex: 0 0 auto; font-size: .72rem; color: #fff; background: #6861ee; border-radius: 999px; padding: .05rem .45rem; }
    .hms-caret { flex: 0 0 auto; font-size: .7rem; color: #94a3b8; transition: transform .15s; }
    .hms-compact.is-open .hms-caret { transform: rotate(180deg); }
    .hms-panel { position: absolute; z-index: 40; top: calc(100% + 6px); inset-inline: 0; min-width: 270px; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 16px 40px rgba(15, 23, 42, .14); overflow: hidden; }
    .hms-panel[hidden] { display: none; }
    .hms-panel .hms-bar { flex-wrap: wrap; }
    .hms-panel .hms-search { flex-basis: 100%; }
    .hms-panel .hms-count { margin-inline-end: auto; }
</style>
<script>
(() => {
    if (window.HourlyMultiSelect) return;

    const escape = text => text.replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
    const normalize = text => text.toLowerCase().replace(/[ً-ْ]/g, '').replace(/[أإآ]/g, 'ا').replace(/ة/g, 'ه').replace(/ى/g, 'ي');
    const MAX_TAGS = 8;

    function enhance(select) {
        if (select.dataset.hmsReady) return;
        select.dataset.hmsReady = '1';

        // الوضع المضغوط (data-compact): حقل بحجم select يعرض ملخص الاختيار، وتفتح القائمة في لوحة منسدلة.
        // مناسب لأشرطة الفلاتر؛ عند إغلاق اللوحة بعد تغيير الاختيار يُطلق hms:commit على الـ select.
        const compact = select.hasAttribute('data-compact');
        const root = document.createElement('div');
        root.className = 'hms' + (compact ? ' hms-compact' : '');
        const body =
            '<div class="hms-bar">' +
                '<div class="hms-search"><i class="fa fa-magnifying-glass"></i><input type="search" autocomplete="off"></div>' +
                '<span class="hms-count"></span>' +
                '<button type="button" class="hms-link" data-act="all">تحديد الكل</button>' +
                '<button type="button" class="hms-link" data-act="none">إلغاء</button>' +
            '</div>' +
            '<div class="hms-list" role="group"></div>';
        root.innerHTML = compact
            ? '<button type="button" class="hms-trigger" aria-haspopup="true" aria-expanded="false"><span class="hms-summary"></span><b class="hms-badge" hidden></b><i class="fa fa-chevron-down hms-caret"></i></button>' +
              '<div class="hms-tags" hidden></div><div class="hms-panel" hidden>' + body + '</div>'
            : '<div class="hms-tags"></div>' + body;
        select.parentNode.insertBefore(root, select);
        root.appendChild(select);
        select.classList.add('hms-native');
        select.tabIndex = -1;
        select.setAttribute('aria-hidden', 'true');

        const tags = root.querySelector('.hms-tags'), list = root.querySelector('.hms-list'), search = root.querySelector('.hms-search input');
        const count = root.querySelector('.hms-count'), allBtn = root.querySelector('[data-act="all"]'), noneBtn = root.querySelector('[data-act="none"]');
        const trigger = root.querySelector('.hms-trigger'), panel = root.querySelector('.hms-panel');
        const label = select.id ? document.querySelector('label[for="' + select.id + '"]') : null;
        search.placeholder = compact ? 'ابحث…' : (select.dataset.placeholder || 'ابحث في الخيارات…');
        search.setAttribute('aria-label', 'بحث' + (label ? ' في ' + label.textContent.trim() : ''));
        list.setAttribute('aria-label', label ? label.textContent.trim() : 'الخيارات');
        let showAllTags = false;

        let openedWith = null;
        const picked = () => options().filter(o => o.selected).map(o => o.value).join(',');
        const setOpen = open => {
            if (!compact || open === !panel.hidden) return;
            panel.hidden = !open;
            root.classList.toggle('is-open', open);
            trigger.setAttribute('aria-expanded', String(open));
            if (open) { openedWith = picked(); search.focus(); return; }
            search.value = '';
            render();
            if (openedWith !== null && openedWith !== picked()) select.dispatchEvent(new CustomEvent('hms:commit', {bubbles: true}));
            openedWith = null;
        };
        if (compact) {
            trigger.addEventListener('click', () => setOpen(panel.hidden));
            document.addEventListener('click', e => { if (!root.contains(e.target)) setOpen(false); });
            root.addEventListener('keydown', e => { if (e.key === 'Escape' && !panel.hidden && !search.value) { setOpen(false); trigger.focus(); } });
            root.addEventListener('focusout', e => { if (e.relatedTarget && !root.contains(e.relatedTarget)) setOpen(false); });
        }
        if (label) label.addEventListener('click', e => { e.preventDefault(); compact ? setOpen(true) : search.focus(); });

        const options = () => Array.from(select.options).filter(o => !o.hidden && o.value !== '');
        const visible = () => {
            const q = normalize(search.value.trim());
            return options().filter(o => !q || normalize(o.textContent).includes(q));
        };
        const emit = () => select.dispatchEvent(new Event('change', {bubbles: true}));

        function render() {
            const all = options(), selected = all.filter(o => o.selected), q = search.value.trim(), shown = visible();
            root.classList.toggle('is-invalid', select.classList.contains('is-invalid'));
            count.textContent = selected.length + ' / ' + all.length;
            count.classList.toggle('is-zero', selected.length === 0);
            count.title = selected.length + ' محدد من ' + all.length;
            allBtn.textContent = q ? 'تحديد النتائج' : 'تحديد الكل';
            allBtn.disabled = select.disabled || !shown.some(o => !o.selected && !o.disabled);
            noneBtn.disabled = select.disabled || !selected.some(o => !o.disabled);
            search.disabled = select.disabled;
            if (compact) {
                // الملخص: النص الافتراضي بلا اختيار، وأسماء الاختيار إن كانت قليلة، وإلا أول اسم وعدد الباقي.
                const names = selected.map(o => o.textContent.trim());
                const summary = root.querySelector('.hms-summary'), badge = root.querySelector('.hms-badge');
                summary.textContent = !names.length ? (select.dataset.placeholder || 'الكل') : names.length <= 2 ? names.join('، ') : names[0] + '، ' + names[1];
                summary.title = names.join('، ');
                root.classList.toggle('has-value', names.length > 0);
                badge.hidden = names.length <= 2;
                badge.textContent = '+' + (names.length - 2);
                trigger.disabled = select.disabled;
            }

            tags.replaceChildren();
            const tagList = showAllTags ? selected : selected.slice(0, MAX_TAGS);
            tagList.forEach(option => {
                const tag = document.createElement('span');
                tag.className = 'hms-tag';
                tag.innerHTML = '<span></span>';
                tag.firstChild.textContent = option.textContent.trim();
                if (!option.disabled && !select.disabled) {
                    const remove = document.createElement('button');
                    remove.type = 'button';
                    remove.innerHTML = '<i class="fa fa-xmark"></i>';
                    remove.title = remove.ariaLabel = 'إزالة ' + option.textContent.trim();
                    remove.addEventListener('click', () => { option.selected = false; emit(); });
                    tag.append(remove);
                }
                tags.append(tag);
            });
            if (selected.length > MAX_TAGS) {
                const more = document.createElement('span');
                more.className = 'hms-tag is-more';
                more.textContent = showAllTags ? 'عرض أقل' : '+' + (selected.length - MAX_TAGS) + ' أخرى';
                more.addEventListener('click', () => { showAllTags = !showAllTags; render(); });
                tags.append(more);
            }

            list.replaceChildren();
            if (!all.length) { list.innerHTML = '<div class="hms-empty"></div>'; list.firstChild.textContent = select.dataset.empty || 'لا توجد خيارات متاحة.'; return; }
            if (!shown.length) { list.innerHTML = '<div class="hms-empty">لا توجد نتائج مطابقة.</div>'; return; }
            const pattern = q ? new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'ig') : null;
            shown.forEach(option => {
                const row = document.createElement('label');
                const disabled = option.disabled || select.disabled;
                row.className = 'hms-option' + (option.selected ? ' is-checked' : '') + (disabled ? ' is-disabled' : '');
                const box = document.createElement('input');
                box.type = 'checkbox';
                box.checked = option.selected;
                box.disabled = disabled;
                box.addEventListener('change', () => { option.selected = box.checked; emit(); });
                const text = document.createElement('span');
                const raw = escape(option.textContent.trim());
                text.innerHTML = pattern ? raw.replace(pattern, '<mark>$1</mark>') : raw;
                row.append(box, text);
                list.append(row);
            });
        }

        allBtn.addEventListener('click', () => { visible().forEach(o => { if (!o.disabled) o.selected = true; }); emit(); });
        noneBtn.addEventListener('click', () => { options().forEach(o => { if (!o.disabled) o.selected = false; }); emit(); });
        search.addEventListener('input', render);
        search.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const first = visible().find(o => !o.disabled);
                if (first) { first.selected = !first.selected; emit(); }
            } else if (e.key === 'Escape' && search.value) {
                e.stopPropagation();
                search.value = '';
                render();
            }
        });
        select.addEventListener('change', render);
        select.addEventListener('invalid', () => { root.classList.add('is-invalid'); search.focus(); });
        new MutationObserver(render).observe(select, {childList: true, subtree: true, attributes: true, attributeFilter: ['disabled', 'hidden', 'class']});
        select.form?.addEventListener('reset', () => setTimeout(render));
        render();
    }

    window.HourlyMultiSelect = {
        enhance,
        init: (scope = document) => scope.querySelectorAll('.ht-page select[multiple]:not([data-hms-ready]):not([data-native])').forEach(enhance),
    };
    const start = () => {
        window.HourlyMultiSelect.init();
        // النماذج المحمّلة لاحقًا (مثل نموذج باقة الخدمة داخل المودال عبر AJAX) تُحسَّن تلقائيًا عند إضافتها للصفحة.
        new MutationObserver(mutations => {
            if (mutations.some(m => [...m.addedNodes].some(n => n.nodeType === 1 && (n.matches('select[multiple]') || n.querySelector('select[multiple]'))))) window.HourlyMultiSelect.init();
        }).observe(document.body, {childList: true, subtree: true});
    };
    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', start) : start();
})();
</script>
