{{--
    قائمة الأيقونات المشتركة لكل أزرار _picker في النموذج — تُضمَّن مرة واحدة لكل نموذج وتُنقل تحت الزر المضغوط.
    تُستخدم في: متطلبات الاستقدام، لماذا نحن.
    كلها Font Awesome Free solid وموجودة في نسخة لوحة التحكم (6.5) والموقع الخارجي (7.3).
--}}
@php
    $requirementIcons = [
        'fa-solid fa-right-to-bracket' => 'دخول',
        'fa-solid fa-circle-check' => 'تحقق',
        'fa-solid fa-check-double' => 'تأكيد',
        'fa-solid fa-list-check' => 'إجراءات',
        'fa-solid fa-list-ol' => 'خطوات',
        'fa-solid fa-clipboard-list' => 'قائمة',
        'fa-solid fa-clipboard-check' => 'مراجعة',
        'fa-solid fa-folder-open' => 'ملفات',
        'fa-solid fa-folder' => 'مجلد',
        'fa-solid fa-file-lines' => 'مستند',
        'fa-solid fa-file-signature' => 'توقيع مستند',
        'fa-solid fa-file-contract' => 'عقد',
        'fa-solid fa-file-circle-check' => 'مستند معتمد',
        'fa-solid fa-file-invoice-dollar' => 'فاتورة',
        'fa-solid fa-signature' => 'توقيع',
        'fa-solid fa-stamp' => 'ختم',
        'fa-solid fa-pen-to-square' => 'تعبئة بيانات',
        'fa-solid fa-handshake' => 'تعاقد',
        'fa-solid fa-passport' => 'جواز سفر',
        'fa-solid fa-id-card' => 'هوية',
        'fa-solid fa-id-badge' => 'بطاقة',
        'fa-solid fa-fingerprint' => 'بصمة',
        'fa-solid fa-user' => 'مستخدم',
        'fa-solid fa-user-check' => 'مستخدم معتمد',
        'fa-solid fa-user-tie' => 'موظف',
        'fa-solid fa-users' => 'مجموعة',
        'fa-solid fa-person-dress' => 'عاملة',
        'fa-solid fa-people-roof' => 'أسرة',
        'fa-solid fa-baby' => 'طفل',
        'fa-solid fa-house' => 'منزل',
        'fa-solid fa-house-user' => 'سكن',
        'fa-solid fa-bed' => 'إيواء',
        'fa-solid fa-credit-card' => 'دفع',
        'fa-solid fa-money-bill-wave' => 'رسوم',
        'fa-solid fa-sack-dollar' => 'تكلفة',
        'fa-solid fa-coins' => 'مبلغ',
        'fa-solid fa-wallet' => 'محفظة',
        'fa-solid fa-receipt' => 'إيصال',
        'fa-solid fa-percent' => 'ضريبة',
        'fa-solid fa-cart-shopping' => 'طلب',
        'fa-solid fa-briefcase' => 'عمل',
        'fa-solid fa-building' => 'مكتب',
        'fa-solid fa-building-columns' => 'جهة حكومية',
        'fa-solid fa-landmark' => 'بنك',
        'fa-solid fa-scale-balanced' => 'نظام',
        'fa-solid fa-gavel' => 'قانون',
        'fa-solid fa-shield-halved' => 'حماية',
        'fa-solid fa-lock' => 'أمان',
        'fa-solid fa-key' => 'مفتاح',
        'fa-solid fa-certificate' => 'شهادة',
        'fa-solid fa-award' => 'جائزة',
        'fa-solid fa-medal' => 'تميز',
        'fa-solid fa-star' => 'نجمة',
        'fa-solid fa-trophy' => 'ريادة',
        'fa-solid fa-rocket' => 'انطلاق',
        'fa-solid fa-thumbs-up' => 'موافقة',
        'fa-solid fa-face-smile' => 'رضا العملاء',
        'fa-solid fa-hand-holding-dollar' => 'أسعار تنافسية',
        'fa-solid fa-user-shield' => 'موثوقية',
        'fa-solid fa-user-graduate' => 'عمالة مدربة',
        'fa-solid fa-people-group' => 'فريق',
        'fa-solid fa-plane' => 'سفر',
        'fa-solid fa-plane-departure' => 'مغادرة',
        'fa-solid fa-plane-arrival' => 'وصول',
        'fa-solid fa-globe' => 'دول',
        'fa-solid fa-earth-asia' => 'آسيا',
        'fa-solid fa-map-location-dot' => 'خريطة',
        'fa-solid fa-location-dot' => 'موقع',
        'fa-solid fa-car' => 'سيارة',
        'fa-solid fa-truck-fast' => 'توصيل',
        'fa-solid fa-calendar-check' => 'موعد',
        'fa-solid fa-calendar-days' => 'تقويم',
        'fa-solid fa-clock' => 'وقت',
        'fa-solid fa-hourglass-half' => 'مدة',
        'fa-solid fa-stopwatch' => 'سرعة',
        'fa-solid fa-phone' => 'هاتف',
        'fa-solid fa-mobile-screen-button' => 'جوال',
        'fa-solid fa-headset' => 'خدمة عملاء',
        'fa-solid fa-comments' => 'محادثة',
        'fa-solid fa-envelope' => 'بريد',
        'fa-solid fa-bell' => 'تنبيه',
        'fa-solid fa-magnifying-glass' => 'بحث',
        'fa-solid fa-heart' => 'رعاية',
        'fa-solid fa-hand-holding-heart' => 'اهتمام',
        'fa-solid fa-kit-medical' => 'فحص طبي',
        'fa-solid fa-stethoscope' => 'طبيب',
        'fa-solid fa-notes-medical' => 'تقرير طبي',
        'fa-solid fa-syringe' => 'تطعيم',
        'fa-solid fa-graduation-cap' => 'تدريب',
        'fa-solid fa-language' => 'لغة',
        'fa-solid fa-gears' => 'معالجة',
        'fa-solid fa-rotate' => 'تجديد',
        'fa-solid fa-arrow-right-arrow-left' => 'نقل',
        'fa-solid fa-bolt' => 'فوري',
        'fa-solid fa-circle-info' => 'معلومة',
        'fa-solid fa-circle-question' => 'استفسار',
        'fa-solid fa-triangle-exclamation' => 'تنبيه مهم',
    ];
@endphp

<div class="req-icon-panel" hidden>
    <div class="req-icon-panel__search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="search" class="form-control form-control-sm"
            placeholder="{{ __('icon_picker.search_icon') }}" autocomplete="off">
    </div>
    <div class="req-icon-panel__grid" role="listbox">
        <button type="button" class="req-icon-panel__item req-icon-panel__item--none" data-icon=""
            title="{{ __('icon_picker.no_icon') }}" data-keywords="{{ __('icon_picker.no_icon') }} none">
            <i class="fa-solid fa-ban"></i>
        </button>
        @foreach ($requirementIcons as $class => $label)
            <button type="button" class="req-icon-panel__item" data-icon="{{ $class }}"
                title="{{ $label }}" data-keywords="{{ $label }} {{ str_replace('fa-solid fa-', '', $class) }}">
                <i class="{{ $class }}"></i>
            </button>
        @endforeach
    </div>
    <p class="req-icon-panel__empty" hidden>{{ __('icon_picker.no_icons_found') }}</p>
</div>

<style>
    .req-icon-picker { position: relative; }
    .req-icon-picker__toggle {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        text-align: start;
    }
    .req-icon-picker__preview {
        display: grid;
        place-items: center;
        flex: none;
        width: 30px;
        height: 30px;
        margin-block: -4px;
        border-radius: 8px;
        background: rgba(15, 139, 136, 0.1);
        color: #0F8B88;
    }
    .req-icon-picker__label {
        flex: 1;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        direction: ltr;
        text-align: end;
        font-size: 0.85rem;
        color: #6c757d;
    }
    [dir="ltr"] .req-icon-picker__label { text-align: start; }
    .req-icon-picker__caret { flex: none; font-size: 0.7rem; color: #adb5bd; transition: transform 0.2s ease; }
    .req-icon-picker.is-open .req-icon-picker__caret { transform: rotate(180deg); }

    .req-icon-panel {
        position: absolute;
        z-index: 1080;
        top: calc(100% + 6px);
        inset-inline: 0;
        min-width: 300px;
        padding: 10px;
        border: 1px solid #e3e8ec;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 16px 40px rgba(13, 31, 45, 0.16);
    }
    .req-icon-panel__search { position: relative; margin-bottom: 8px; }
    .req-icon-panel__search i {
        position: absolute;
        top: 50%;
        inset-inline-start: 10px;
        transform: translateY(-50%);
        font-size: 0.8rem;
        color: #adb5bd;
        pointer-events: none;
    }
    .req-icon-panel__search input { padding-inline-start: 30px; }
    .req-icon-panel__grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(40px, 1fr));
        gap: 6px;
        max-height: 220px;
        overflow-y: auto;
        padding: 2px;
    }
    .req-icon-panel__item {
        display: grid;
        place-items: center;
        aspect-ratio: 1;
        border: 1px solid #edf0f2;
        border-radius: 9px;
        background: #f8fafb;
        color: #3d4b53;
        font-size: 1rem;
        transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }
    .req-icon-panel__item:hover,
    .req-icon-panel__item:focus-visible {
        border-color: #0F8B88;
        background: rgba(15, 139, 136, 0.08);
        color: #0F8B88;
        outline: none;
    }
    .req-icon-panel__item.is-selected {
        border-color: #0F8B88;
        background: #0F8B88;
        color: #fff;
    }
    .req-icon-panel__item--none { color: #dc3545; }
    .req-icon-panel__empty { margin: 8px 0 2px; text-align: center; font-size: 0.8rem; color: #6c757d; }
</style>

<script>
    (() => {
        // النموذج يُحمَّل عبر AJAX أكثر من مرة؛ نربط مستمعي document مرة واحدة فقط
        if (window.__reqIconPickerBound) {
            return;
        }
        window.__reqIconPickerBound = true;

        const panelFor = (picker) => picker.closest('form')?.querySelector('.req-icon-panel');

        const close = (panel) => {
            if (!panel || panel.hidden) {
                return;
            }
            panel.hidden = true;
            panel.closest('.req-icon-picker')?.classList.remove('is-open');
        };

        const open = (picker) => {
            const panel = panelFor(picker);
            if (!panel) {
                return;
            }

            const current = picker.querySelector('.req-icon-picker__input').value;
            const search = panel.querySelector('input[type="search"]');

            picker.appendChild(panel);
            picker.classList.add('is-open');
            panel.hidden = false;
            search.value = '';
            filter(panel, '');

            panel.querySelectorAll('.req-icon-panel__item').forEach((item) => {
                item.classList.toggle('is-selected', item.dataset.icon === current);
            });
            panel.querySelector('.is-selected')?.scrollIntoView({ block: 'nearest' });
            search.focus();
        };

        const filter = (panel, term) => {
            term = term.trim().toLowerCase();
            let visible = 0;

            panel.querySelectorAll('.req-icon-panel__item').forEach((item) => {
                const match = term === '' || item.dataset.keywords.toLowerCase().includes(term);
                item.hidden = !match;
                visible += match ? 1 : 0;
            });

            panel.querySelector('.req-icon-panel__empty').hidden = visible > 0;
        };

        document.addEventListener('click', (event) => {
            const toggle = event.target.closest('.req-icon-picker__toggle');
            if (toggle) {
                const picker = toggle.closest('.req-icon-picker');
                const panel = panelFor(picker);
                const wasOpenHere = panel && !panel.hidden && panel.parentElement === picker;

                close(panel);
                if (!wasOpenHere) {
                    open(picker);
                }
                return;
            }

            const item = event.target.closest('.req-icon-panel__item');
            if (item) {
                const picker = item.closest('.req-icon-picker');
                const icon = item.dataset.icon;
                const label = picker.querySelector('.req-icon-picker__label');

                picker.querySelector('.req-icon-picker__input').value = icon;
                picker.querySelector('.req-icon-picker__preview i').className = icon || 'fa-regular fa-image';
                label.textContent = icon || label.dataset.empty;

                close(item.closest('.req-icon-panel'));
                picker.querySelector('.req-icon-picker__toggle').focus();
                return;
            }

            if (!event.target.closest('.req-icon-panel')) {
                document.querySelectorAll('.req-icon-panel:not([hidden])').forEach(close);
            }
        });

        document.addEventListener('input', (event) => {
            const panel = event.target.closest('.req-icon-panel');
            if (panel && event.target.matches('input[type="search"]')) {
                filter(panel, event.target.value);
            }
        });

        document.addEventListener('keydown', (event) => {
            const panel = event.target.closest('.req-icon-panel');
            if (!panel) {
                return;
            }

            // Escape يغلق القائمة فقط دون إغلاق المودال، وEnter لا يرسل النموذج
            if (event.key === 'Escape') {
                event.preventDefault();
                event.stopPropagation();
                const picker = panel.closest('.req-icon-picker');
                close(panel);
                picker?.querySelector('.req-icon-picker__toggle').focus();
            } else if (event.key === 'Enter' && event.target.matches('input[type="search"]')) {
                event.preventDefault();
                panel.querySelector('.req-icon-panel__item:not([hidden])')?.click();
            }
        }, true);
    })();
</script>
