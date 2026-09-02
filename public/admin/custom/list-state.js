/**
 * حفظ واستعادة حالة جداول القوائم (الفلاتر + رقم الصفحة + الترتيب + عدد السجلات).
 *
 * الفكرة: صفحة القائمة تحفظ حالتها باستمرار في sessionStorage، وزر "العودة للصفحة
 * السابقة" في صفحة التفاصيل يضع علامة رجوع قبل الانتقال. عند تحميل صفحة القائمة
 * تُستعاد الحالة المحفوظة فقط إذا وُجدت علامة الرجوع، حتى لا تتأثر الزيارات العادية
 * القادمة من القائمة الجانبية.
 */
(function (window, document) {
    'use strict';

    var RETURN_FLAG = 'clickerp.listState.return';
    var STORE_PREFIX = 'clickerp.listState.';
    var RETURN_TTL_MS = 5 * 60 * 1000;

    function store() {
        try {
            var storage = window.sessionStorage;
            storage.getItem(RETURN_FLAG);
            return storage;
        } catch (error) {
            return null;
        }
    }

    function readJson(key) {
        var storage = store();
        if (!storage) {
            return null;
        }

        try {
            var raw = storage.getItem(STORE_PREFIX + key);
            return raw ? JSON.parse(raw) : null;
        } catch (error) {
            return null;
        }
    }

    function writeJson(key, value) {
        var storage = store();
        if (!storage) {
            return;
        }

        try {
            storage.setItem(STORE_PREFIX + key, JSON.stringify(value));
        } catch (error) {
            /* التخزين ممتلئ أو محظور - نتجاهل الحفظ */
        }
    }

    function markReturn() {
        var storage = store();
        if (!storage) {
            return;
        }

        try {
            storage.setItem(RETURN_FLAG, String(Date.now()));
        } catch (error) {
            /* تجاهل */
        }
    }

    function clearReturn() {
        var storage = store();
        if (!storage) {
            return;
        }

        try {
            storage.removeItem(RETURN_FLAG);
        } catch (error) {
            /* تجاهل */
        }
    }

    function consumeReturn() {
        var storage = store();
        if (!storage) {
            return false;
        }

        var raw = null;
        try {
            raw = storage.getItem(RETURN_FLAG);
            storage.removeItem(RETURN_FLAG);
        } catch (error) {
            return false;
        }

        var markedAt = parseInt(raw, 10);

        return !isNaN(markedAt) && (Date.now() - markedAt) < RETURN_TTL_MS;
    }

    function collectFilters(selectors) {
        var values = {};

        (selectors || []).forEach(function (selector) {
            var element = document.querySelector(selector);
            if (!element) {
                return;
            }

            values[selector] = element.type === 'checkbox' ? !!element.checked : element.value;
        });

        return values;
    }

    function applyFilters(values) {
        Object.keys(values || {}).forEach(function (selector) {
            var element = document.querySelector(selector);
            if (!element) {
                return;
            }

            if (element.type === 'checkbox') {
                element.checked = !!values[selector];
            } else {
                element.value = values[selector] === null || values[selector] === undefined
                    ? ''
                    : values[selector];
            }
        });
    }

    /**
     * تُستهلك علامة الرجوع مرة واحدة عند تحميل أول صفحة بعد الضغط على زر العودة،
     * حتى لا تُستعاد حالة قديمة في صفحة قائمة يزورها المستخدم لاحقاً.
     */
    var isReturnNavigation = consumeReturn();

    /**
     * ينشئ متحكم حالة لجدول واحد.
     *
     * options.key       مفتاح فريد للصفحة (مطلوب)
     * options.filters   مصفوفة محددات (selectors) لحقول الفلترة
     * options.onRestore دالة تُنفذ بعد استعادة الفلاتر (لتحديث الواجهة)
     */
    function create(options) {
        var settings = options || {};
        var key = settings.key;
        var filterSelectors = settings.filters || [];
        var isReturning = isReturnNavigation;

        var controller = {
            key: key,
            /** هل جرت استعادة فعلية للحالة في هذا التحميل؟ */
            restored: false,

            saveCallback: function (settings_, data) {
                writeJson(key, data);
            },

            loadCallback: function () {
                return isReturning ? readJson(key) : null;
            },

            saveParams: function (settings_, data) {
                data.listFilters = collectFilters(filterSelectors);
            },

            loadParams: function (settings_, data) {
                if (!data || !data.listFilters) {
                    return;
                }

                applyFilters(data.listFilters);
                controller.restored = true;

                if (typeof settings.onRestore === 'function') {
                    settings.onRestore(data.listFilters);
                }
            }
        };

        return controller;
    }

    /** يُستدعى من صفحات التفاصيل قبل الرجوع للقائمة */
    function back(event, fallbackUrl) {
        markReturn();

        var canGoBack = document.referrer && window.history.length > 1;

        if (canGoBack) {
            if (event && typeof event.preventDefault === 'function') {
                event.preventDefault();
            }
            window.history.back();
            return false;
        }

        if (fallbackUrl && !event) {
            window.location.href = fallbackUrl;
        }

        return true;
    }

    document.addEventListener('click', function (event) {
        var target = event.target;
        var trigger = target && target.closest ? target.closest('[data-list-back]') : null;

        if (!trigger) {
            return;
        }

        back(event, trigger.getAttribute('href'));
    });

    // عند الرجوع من ذاكرة المتصفح (bfcache) تبقى الحالة كما هي، فنلغي علامة الرجوع
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            isReturnNavigation = false;
            clearReturn();
        }
    });

    window.ListState = {
        create: create,
        back: back,
        markReturn: markReturn
    };
})(window, document);
