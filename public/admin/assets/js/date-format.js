/*
 * توحيد صيغة التاريخ في النظام: يوم-شهر-سنة (dd-mm-yyyy) بغض النظر عن لغة الجهاز أو المتصفح.
 *
 * العرض فقط هو ما يتغير؛ القيم المرسلة للخادم تبقى كما هي (yyyy-mm-dd) فلا يتأثر أي حفظ أو تقرير.
 *
 * 1) حقول الإدخال: كل input[type=date] و input[type=datetime-local] يتحول إلى flatpickr مع altInput:
 *    الحقل الأصلي يبقى بنفس الاسم والمعرّف والقيمة (Y-m-d) ويصبح مخفياً، ويظهر بجانبه حقل عرض d-m-Y.
 *    - تعيين القيمة برمجياً ($(el).val('2026-10-15')) يحدّث العرض تلقائياً.
 *    - min/max/disabled/readonly/required تُزامَن حتى لو تغيّرت لاحقاً بالـ JS.
 *    - data-validation تنتقل لحقل العرض لأن المدقق يتجاهل الحقول المخفية.
 *    - الحقول التي تُضاف لاحقاً (نوافذ ajax، تكرار صفوف multifield) تُعالَج تلقائياً.
 *    للاستثناء: أضف data-raw-date للحقل أو لأي عنصر أب.
 *
 * 2) النصوص المعروضة: أي تاريخ yyyy-mm-dd داخل نص الصفحة (جداول، بطاقات، DataTables) يُعرض dd-mm-yyyy.
 *    لا تُمس قيم الحقول ولا السمات ولا السكربتات. خلايا الجداول تأخذ data-order بالقيمة الأصلية
 *    ليبقى ترتيب DataTables (غير الخادمي) زمنياً صحيحاً.
 *    للاستثناء: data-raw-date على العنصر.
 */
(function () {
    'use strict';

    if (window.ClickDateFormat) {
        return;
    }

    var DISPLAY_DATE = 'd-m-Y';
    var DISPLAY_DATETIME = 'd-m-Y H:i';
    var ISO_IN_TEXT = /(^|[^\d\-\/])(\d{4})-(\d{2})-(\d{2})(?![\d\-])/g;
    var HAS_ISO = /\d{4}-\d{2}-\d{2}/;
    var FULL_ISO_CELL = /^\s*\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2}(:\d{2})?)?\s*$/;
    var SKIP_TAGS = { SCRIPT: 1, STYLE: 1, TEXTAREA: 1, INPUT: 1, NOSCRIPT: 1, TEMPLATE: 1, CODE: 1, PRE: 1 };
    var SKIP_SELECTOR = '[data-raw-date], [contenteditable=""], [contenteditable="true"], .flatpickr-calendar, .tox, .mytextarea';
    var nativeValue = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');

    var arabic = {
        weekdays: {
            shorthand: ['أحد', 'اثنين', 'ثلاثاء', 'أربعاء', 'خميس', 'جمعة', 'سبت'],
            longhand: ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت']
        },
        months: {
            shorthand: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'],
            longhand: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر']
        },
        firstDayOfWeek: 6,
        rangeSeparator: ' - ',
        time_24hr: true
    };

    function isArabicPage() {
        return (document.documentElement.getAttribute('lang') || '').toLowerCase().indexOf('ar') === 0;
    }

    function isSkipped(element) {
        return !element || (element.closest && element.closest(SKIP_SELECTOR) !== null);
    }

    // ── النصوص ─────────────────────────────────────────────────────────────

    function toDisplay(text) {
        return text.replace(ISO_IN_TEXT, function (match, before, year, month, day) {
            if (+month < 1 || +month > 12 || +day < 1 || +day > 31) {
                return match;
            }
            return before + day + '-' + month + '-' + year;
        });
    }

    function convertTextNode(node) {
        var parent = node.parentNode;
        if (!parent || parent.nodeType !== 1 || SKIP_TAGS[parent.tagName] || isSkipped(parent)) {
            return;
        }
        var original = node.nodeValue;
        if (!HAS_ISO.test(original)) {
            return;
        }
        var converted = toDisplay(original);
        if (converted === original) {
            return;
        }
        var cell = parent.closest('td, th');
        if (cell && !cell.hasAttribute('data-order') && FULL_ISO_CELL.test(cell.textContent)) {
            cell.setAttribute('data-order', cell.textContent.trim());
        }
        node.nodeValue = converted;
    }

    function convertTextIn(root) {
        if (!root) {
            return;
        }
        if (root.nodeType === 3) {
            convertTextNode(root);
            return;
        }
        if (root.nodeType !== 1 && root.nodeType !== 9 && root.nodeType !== 11) {
            return;
        }
        if (root.nodeType === 1 && (SKIP_TAGS[root.tagName] || isSkipped(root))) {
            return;
        }
        var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null);
        var nodes = [];
        while (walker.nextNode()) {
            if (HAS_ISO.test(walker.currentNode.nodeValue)) {
                nodes.push(walker.currentNode);
            }
        }
        nodes.forEach(convertTextNode);
    }

    // ── حقول الإدخال ───────────────────────────────────────────────────────

    function moveValidation(input, alt) {
        var moved = [];
        Array.prototype.slice.call(input.attributes).forEach(function (attribute) {
            if (attribute.name.indexOf('data-validation') === 0) {
                var value = attribute.value;
                // قاعدة date في المدقق تفحص صيغة yyyy-mm-dd؛ حقل العرض dd-mm-yyyy فلا تُنقل إليه.
                if (attribute.name === 'data-validation') {
                    value = value.split(/\s+/).filter(function (rule) { return rule && rule !== 'date'; }).join(' ');
                }
                if (value !== '' || attribute.name !== 'data-validation') {
                    alt.setAttribute(attribute.name, value);
                }
                moved.push([attribute.name, attribute.value]);
            }
        });
        moved.forEach(function (pair) {
            input.removeAttribute(pair[0]);
        });
        if (moved.length) {
            input.setAttribute('data-df-validation', JSON.stringify(moved));
        }
    }

    function syncState(input, fp) {
        var alt = fp.altInput;
        alt.disabled = input.disabled;
        alt.readOnly = input.readOnly;
        alt.required = input.required || input.hasAttribute('data-df-required');
        // تمييز الأخطاء الذي تضيفه الشاشات على الحقل الأصلي (المخفي) يظهر على حقل العرض.
        alt.classList.toggle('is-invalid', input.classList.contains('is-invalid'));
        alt.classList.toggle('is-valid', input.classList.contains('is-valid'));
        fp.set('clickOpens', !input.disabled && !input.readOnly);
        if (fp.isOpen) {
            fp.redraw();
        }
    }

    /*
     * min/max لا تُمرَّر لـ flatpickr كـ minDate/maxDate: flatpickr يمسح أي قيمة محفوظة خارج النطاق
     * (مثلاً تاريخ قديم في شاشة تعديل مع min=اليوم) فتضيع عند الحفظ. بدلاً من ذلك تُعطَّل أيام
     * التقويم خارج النطاق فقط (selectDate يتجاهل .flatpickr-disabled)، وتبقى القيمة الحالية كما هي.
     */
    function dayKey(date) {
        return date.getFullYear() * 10000 + (date.getMonth() + 1) * 100 + date.getDate();
    }

    function boundKey(value) {
        var match = /^(\d{4})-(\d{2})-(\d{2})/.exec(value || '');
        return match ? Number(match[1] + match[2] + match[3]) : null;
    }

    function markOutOfRange(input) {
        return function (selectedDates, dateStr, fp, dayElem) {
            var key = dayKey(dayElem.dateObj);
            var min = boundKey(input.getAttribute('min'));
            var max = boundKey(input.getAttribute('max'));
            if ((min !== null && key < min) || (max !== null && key > max)) {
                dayElem.classList.add('flatpickr-disabled');
            }
        };
    }

    function hookValue(input, fp) {
        var syncing = false;
        Object.defineProperty(input, 'value', {
            configurable: true,
            get: function () {
                return nativeValue.get.call(this);
            },
            set: function (value) {
                nativeValue.set.call(this, value);
                // flatpickr نفسه يكتب القيمة بعد كل اختيار؛ لا داعي لإعادة التحليل حينها.
                var current = fp.selectedDates.length ? fp.formatDate(fp.selectedDates[0], fp.config.dateFormat) : '';
                if (syncing || current === (value || '')) {
                    return;
                }
                syncing = true;
                try {
                    fp.setDate(value ? value : null, false);
                } finally {
                    syncing = false;
                }
            }
        });
    }

    function enhanceInput(input) {
        if (input._flatpickr || typeof window.flatpickr !== 'function' || isSkipped(input)) {
            return;
        }
        var type = (input.getAttribute('data-df-type') || input.getAttribute('type') || '').toLowerCase();
        if (type !== 'date' && type !== 'datetime-local') {
            return;
        }
        var withTime = type === 'datetime-local';
        var withSeconds = withTime && (/T\d{2}:\d{2}:\d{2}/.test(nativeValue.get.call(input))
            || (input.step && Number(input.step) < 60));

        input.setAttribute('data-df-type', type);
        if (input.required) {
            input.setAttribute('data-df-required', '');
        }

        var config = {
            altInput: true,
            altFormat: withTime ? (withSeconds ? 'd-m-Y H:i:S' : DISPLAY_DATETIME) : DISPLAY_DATE,
            dateFormat: withTime ? (withSeconds ? 'Y-m-d\\TH:i:S' : 'Y-m-d\\TH:i') : 'Y-m-d',
            enableTime: withTime,
            enableSeconds: !!withSeconds,
            time_24hr: true,
            allowInput: true,
            disableMobile: true,
            onDayCreate: markOutOfRange(input)
        };
        if (isArabicPage()) {
            config.locale = arabic;
        }
        // في الصفحات العربية الحقل غالباً على اليمين؛ محاذاة التقويم لحافته اليمنى تمنع خروجه من الشاشة.
        if ((document.documentElement.getAttribute('dir') || '').toLowerCase() === 'rtl') {
            config.position = 'auto right';
        }

        var fp = window.flatpickr(input, config);
        if (!fp || !fp.altInput) {
            return;
        }
        var alt = fp.altInput;
        alt.setAttribute('data-df-alt', '');
        alt.setAttribute('autocomplete', 'off');
        alt.classList.add('df-date-input');
        alt.placeholder = input.getAttribute('placeholder') || (withTime ? 'dd-mm-yyyy hh:mm' : 'dd-mm-yyyy');
        if (input.style.cssText) {
            alt.style.cssText = input.style.cssText;
        }
        moveValidation(input, alt);
        syncState(input, fp);
        hookValue(input, fp);

        // اختيار تاريخ من التقويم أو مسح الحقل يطلق change/input على الحقل الأصلي (flatpickr)؛
        // أما الكتابة اليدوية فتُعتمد عند مغادرة الحقل.
        alt.addEventListener('blur', function () {
            if (alt.value === '' && nativeValue.get.call(input) !== '') {
                fp.clear();
            }
        });

        new MutationObserver(function () {
            syncState(input, fp);
        }).observe(input, { attributes: true, attributeFilter: ['min', 'max', 'disabled', 'readonly', 'required', 'class'] });

        if (input.form && !input.form.hasAttribute('data-df-reset')) {
            input.form.setAttribute('data-df-reset', '');
            input.form.addEventListener('reset', function (event) {
                var form = event.target;
                setTimeout(function () {
                    form.querySelectorAll('[data-df-type]').forEach(function (field) {
                        if (field._flatpickr) {
                            field._flatpickr.setDate(nativeValue.get.call(field) || null, false);
                        }
                    });
                }, 0);
            });
        }
    }

    // نسخة مكررة من حقل محوَّل (تكرار صف بالـ JS): نحذف حقل العرض المنسوخ ونعيد الحقل الأصلي لحالته ثم نحوّله.
    function repairClone(root) {
        if (!root.querySelectorAll) {
            return;
        }
        var alts = root.matches && root.matches('[data-df-alt]') ? [root] : [];
        alts = alts.concat(Array.prototype.slice.call(root.querySelectorAll('[data-df-alt]')));
        alts.forEach(function (alt) {
            var original = alt.previousElementSibling;
            if (!original || !original._flatpickr || original._flatpickr.altInput !== alt) {
                alt.parentNode && alt.parentNode.removeChild(alt);
            }
        });
        var originals = root.matches && root.matches('[data-df-type]') ? [root] : [];
        originals = originals.concat(Array.prototype.slice.call(root.querySelectorAll('[data-df-type]')));
        originals.forEach(function (input) {
            if (input._flatpickr) {
                return;
            }
            input.setAttribute('type', input.getAttribute('data-df-type'));
            input.classList.remove('flatpickr-input');
            var moved = input.getAttribute('data-df-validation');
            if (moved) {
                JSON.parse(moved).forEach(function (pair) {
                    input.setAttribute(pair[0], pair[1]);
                });
                input.removeAttribute('data-df-validation');
            }
            if (input.hasAttribute('data-df-required')) {
                input.required = true;
            }
        });
    }

    function enhanceInputsIn(root) {
        if (!root || !root.querySelectorAll) {
            return;
        }
        repairClone(root);
        var selector = 'input[type="date"], input[type="datetime-local"], input[data-df-type]';
        if (root.matches && root.matches(selector)) {
            enhanceInput(root);
        }
        root.querySelectorAll(selector).forEach(enhanceInput);
    }

    // ── التشغيل ────────────────────────────────────────────────────────────

    function processRoot(root) {
        enhanceInputsIn(root);
        convertTextIn(root);
    }

    var pending = [];
    var scheduled = false;

    function flush() {
        scheduled = false;
        var roots = pending;
        pending = [];
        roots.forEach(function (node) {
            if (node.isConnected !== false) {
                processRoot(node);
            }
        });
    }

    function start() {
        processRoot(document.body);
        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === 1 || node.nodeType === 3) {
                        pending.push(node);
                    }
                });
            });
            if (pending.length && !scheduled) {
                scheduled = true;
                setTimeout(flush, 0);
            }
        }).observe(document.body, { childList: true, subtree: true });
    }

    // تقويم flatpickr يُلحق بـ<body> خارج نوافذ Bootstrap؛ حبس التركيز في المودال يمنع الكتابة في
    // حقول الساعة والدقيقة. إيقاف focusin القادم من التقويم يحل ذلك (نفس معالجة نوافذ TinyMCE).
    document.addEventListener('focusin', function (event) {
        if (event.target && event.target.closest && event.target.closest('.flatpickr-calendar')) {
            event.stopImmediatePropagation();
        }
    });

    window.ClickDateFormat = {
        toDisplay: toDisplay,
        refresh: function (root) {
            processRoot(root || document.body);
        }
    };

    // نصوص الصفحة تُحوَّل مبكراً (قبل تهيئة DataTables لتلتقط data-order)، أما الحقول فبعد سكربتات
    // الصفحة كي تربط أحداثها على input[type=date] قبل أن يصبح مخفياً.
    function onReady(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    }

    // السكربت يُحمَّل آخر <body> قبل سكربتات الصفحة، فالمحتوى الثابت موجود الآن ويُحوَّل فوراً.
    if (document.body) {
        convertTextIn(document.body);
    }

    onReady(function () {
        convertTextIn(document.body);
        // مؤقت بعد DOMContentLoaded: معالجات jQuery ready تُنفَّذ قبله (jQuery 3 يؤجلها بمؤقت سابق).
        setTimeout(start, 0);
    });
})();
