@if (in_array(app()->getLocale(), ['ar', 'en'], true))
    <script>
        (function () {
            const locale = @json(app()->getLocale());
            const dictionary = @json(trans('admin_hardcoded'));
            const entries = Object.entries(dictionary || {})
                .filter(([source, target]) => source && target)
                .sort((a, b) => b[0].length - a[0].length);

            if (!entries.length) {
                return;
            }

            const sourcePattern = locale === 'en' ? /[\u0600-\u06FF]/ : /[A-Za-z]{2,}/;
            const ignoredTags = new Set(['SCRIPT', 'STYLE', 'TEXTAREA', 'CODE', 'PRE', 'NOSCRIPT']);
            const translatableAttributes = ['placeholder', 'title', 'aria-label', 'data-placeholder'];

            function escapeRegExp(value) {
                return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            }

            const replacers = entries.map(([source, target]) => [new RegExp(escapeRegExp(source), 'g'), target]);

            function translate(value) {
                if (!value || !sourcePattern.test(value)) {
                    return value;
                }

                return replacers.reduce((result, [pattern, target]) => result.replace(pattern, target), value);
            }

            function shouldSkipElement(element) {
                return !element || ignoredTags.has(element.tagName);
            }

            function translateTextNode(node) {
                const translated = translate(node.nodeValue);
                if (translated !== node.nodeValue) {
                    node.nodeValue = translated;
                }
            }

            function translateElementAttributes(element) {
                translatableAttributes.forEach(function (attribute) {
                    if (!element.hasAttribute(attribute)) {
                        return;
                    }

                    const currentValue = element.getAttribute(attribute);
                    const translated = translate(currentValue);
                    if (translated !== currentValue) {
                        element.setAttribute(attribute, translated);
                    }
                });
            }

            function translateTree(root) {
                if (!root) {
                    return;
                }

                if (root.nodeType === Node.TEXT_NODE) {
                    translateTextNode(root);
                    return;
                }

                if (root.nodeType !== Node.ELEMENT_NODE) {
                    return;
                }

                translateElementAttributes(root);

                if (shouldSkipElement(root)) {
                    return;
                }

                const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT | NodeFilter.SHOW_ELEMENT, {
                    acceptNode(node) {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            return shouldSkipElement(node) ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT;
                        }

                        return node.parentElement && shouldSkipElement(node.parentElement)
                            ? NodeFilter.FILTER_REJECT
                            : NodeFilter.FILTER_ACCEPT;
                    }
                });

                let node;
                while ((node = walker.nextNode())) {
                    if (node.nodeType === Node.TEXT_NODE) {
                        translateTextNode(node);
                    } else if (node.nodeType === Node.ELEMENT_NODE) {
                        translateElementAttributes(node);
                    }
                }
            }

            function boot() {
                translateTree(document.body);
                installDataTablesLocalePatch();

                let pending = false;
                const observer = new MutationObserver(function (mutations) {
                    if (pending) {
                        return;
                    }

                    pending = true;
                    window.requestAnimationFrame(function () {
                        pending = false;
                        mutations.forEach(function (mutation) {
                            if (mutation.type === 'characterData') {
                                translateTextNode(mutation.target);
                                return;
                            }

                            mutation.addedNodes.forEach(translateTree);
                        });
                    });
                });

                observer.observe(document.body, {
                    childList: true,
                    characterData: true,
                    subtree: true
                });

                document.addEventListener('draw.dt', function () {
                    translateTree(document.body);
                }, true);

                if (window.jQuery) {
                    window.jQuery(document).ajaxComplete(function () {
                        window.requestAnimationFrame(function () {
                            translateTree(document.body);
                        });
                    });
                }
            }

            function localizedDataTableLanguage() {
                if (locale === 'en') {
                    return {
                        decimal: '',
                        emptyTable: 'No data available in table',
                        info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                        infoEmpty: 'Showing 0 to 0 of 0 entries',
                        infoFiltered: '(filtered from _MAX_ total entries)',
                        lengthMenu: 'Show _MENU_ entries',
                        loadingRecords: 'Loading...',
                        processing: 'Processing...',
                        search: 'Search:',
                        zeroRecords: 'No matching records found',
                        paginate: {
                            first: 'First',
                            last: 'Last',
                            next: 'Next',
                            previous: 'Previous'
                        }
                    };
                }

                return {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json'
                };
            }

            function installDataTablesLocalePatch() {
                if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.DataTable || window.__adminLocaleDataTablesPatched) {
                    return;
                }

                window.__adminLocaleDataTablesPatched = true;
                const originalDataTable = window.jQuery.fn.DataTable;

                window.jQuery.fn.DataTable = function (options) {
                    if (options && typeof options === 'object') {
                        options.language = Object.assign(
                            {},
                            options.language || {},
                            localizedDataTableLanguage()
                        );

                        if (locale === 'en' && options.language.url && String(options.language.url).includes('/ar.json')) {
                            delete options.language.url;
                        }
                    }

                    return originalDataTable.call(this, options);
                };

                Object.keys(originalDataTable).forEach(function (key) {
                    window.jQuery.fn.DataTable[key] = originalDataTable[key];
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', boot);
            } else {
                boot();
            }
        })();
    </script>
@endif
