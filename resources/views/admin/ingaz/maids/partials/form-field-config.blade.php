{{--
    تطبيق إعداد "حقول نموذج إضافة السيرة" (إعدادات السير الذاتية) على نموذجي الإضافة والتعديل.
    - المخفي: يُخفى غلافه دائمًا حتى لو أظهره منطق نوع السيرة، وفي الإضافة تُعطّل مدخلاته فلا تُرسل.
    - الاختياري/المخفي: تُزال صفة الإلزام كلما أعادها منطق نوع السيرة أو حالة الخبرة.
    - الإلزامي: يُفرض على حقل الملف، إلا إذا كان للعاملة ملف محفوظ ($existingFiles في التعديل).
    المتغير $disableHiddenInputs = true في نموذج الإضافة فقط؛ في التعديل تبقى القيم المحفوظة كما هي.
--}}
@php
    $maidFormFieldConfig = \App\Support\MaidFormFieldSettings::clientConfig();
@endphp
<style>
    #maidForm .maid-field-hidden {
        display: none !important;
    }
</style>
<script>
    (function() {
        var config = @json((object) $maidFormFieldConfig);
        var disableHidden = @json((bool) ($disableHiddenInputs ?? false));
        var existingFiles = @json((object) ($existingFiles ?? []));

        function getForm() {
            return document.getElementById('maidForm');
        }

        function columnWrapper(element, form) {
            for (var node = element; node && node !== form; node = node.parentElement) {
                for (var i = 0; i < node.classList.length; i++) {
                    if (/^col(-|$)/.test(node.classList[i])) {
                        return node;
                    }
                }
            }
            return element;
        }

        function wrappersFor(field, form) {
            var wrappers = [];
            var add = function(node) {
                if (node && wrappers.indexOf(node) === -1) {
                    wrappers.push(node);
                }
            };

            if (field.selector) {
                form.querySelectorAll(field.selector).forEach(function(element) {
                    if (field.wrapper === 'self') {
                        add(element);
                    } else if (field.wrapper) {
                        add(element.closest(field.wrapper));
                    } else {
                        add(columnWrapper(element, form));
                    }
                });
                return wrappers;
            }

            (field.names || []).forEach(function(name) {
                var selector = '[name="' + String(name).replace(/(["\\])/g, '\\$1') + '"]';
                form.querySelectorAll(selector).forEach(function(element) {
                    if (element.type !== 'hidden') {
                        add(columnWrapper(element, form));
                    }
                });
            });
            return wrappers;
        }

        function apply() {
            var form = getForm();
            if (!form) {
                return;
            }

            Object.keys(config).forEach(function(key) {
                var field = config[key];
                var hidden = field.state === 'hidden';
                var forceRequired = field.state === 'required' && !existingFiles[key];

                wrappersFor(field, form).forEach(function(wrapper) {
                    if (hidden && !wrapper.classList.contains('maid-field-hidden')) {
                        wrapper.classList.add('maid-field-hidden');
                    }

                    wrapper.querySelectorAll('input, select, textarea').forEach(function(input) {
                        if (field.state === 'required') {
                            if (input.type !== 'hidden' && input.required !== forceRequired) {
                                input.required = forceRequired;
                            }
                            return;
                        }
                        if (input.required) {
                            input.required = false;
                        }
                        if (hidden && disableHidden && !input.disabled) {
                            input.disabled = true;
                        }
                    });
                });
            });
        }

        window.maidFormFields = {
            state: function(key) {
                return config[key] ? config[key].state : 'default';
            },
            // الحقل لم يعد إلزاميًا في هذه الشركة (اختياري أو مخفي)
            relaxed: function(key) {
                return !!config[key] && config[key].state !== 'required';
            },
            apply: apply,
            // حقول الملفات المضبوطة "إلزامي دائمًا" ولم يُرفع لها ملف (مدقق الحقول العام لا يفحص الملفات).
            missingRequired: function() {
                var form = getForm();
                var errors = [];
                if (!form) {
                    return errors;
                }
                Object.keys(config).forEach(function(key) {
                    var field = config[key];
                    if (field.state !== 'required' || existingFiles[key]) {
                        return;
                    }
                    wrappersFor(field, form).forEach(function(wrapper) {
                        wrapper.querySelectorAll('input[type="file"]').forEach(function(input) {
                            if (input.disabled || (input.files && input.files.length)) {
                                input.classList.remove('is-invalid');
                                return;
                            }
                            var label = wrapper.querySelector('label');
                            var text = label ? label.cloneNode(true) : null;
                            if (text) {
                                text.querySelectorAll('small, span').forEach(function(node) {
                                    node.remove();
                                });
                            }
                            input.classList.add('is-invalid');
                            errors.push('يرجى إرفاق ' + (text ? text.textContent.replace(/\s+/g, ' ').trim() : 'الملف المطلوب'));
                        });
                    });
                });
                return errors;
            }
        };

        if (!Object.keys(config).length) {
            return;
        }

        function start() {
            var form = getForm();
            if (!form) {
                return;
            }
            apply();
            if (window.MutationObserver) {
                // منطق نوع السيرة وحالة الخبرة وإضافة صفوف الخبرة يعيد الإلزام؛ نعيد تطبيق الإعداد بعده.
                new MutationObserver(apply).observe(form, {
                    subtree: true,
                    childList: true,
                    attributes: true,
                    attributeFilter: ['required', 'disabled']
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', start);
        } else {
            start();
        }
    })();
</script>
