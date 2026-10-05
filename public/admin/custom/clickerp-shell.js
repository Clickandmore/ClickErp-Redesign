(function () {
    'use strict';

    function normalise(value) {
        return (value || '').toLocaleLowerCase().trim();
    }

    function filterNavigation(value) {
        var sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;

        var query = normalise(value);
        var roots = sidebar.querySelectorAll('.main-menu > .menu-list > li:not(.divider)');
        var visibleCount = 0;

        roots.forEach(function (root) {
            var matches = !query || normalise(root.textContent).indexOf(query) !== -1;
            root.classList.toggle('ck-nav-hidden', !matches);
            if (matches) {
                visibleCount += 1;
                if (query) {
                    root.querySelectorAll('.collapse').forEach(function (submenu) {
                        submenu.classList.add('show');
                    });
                }
            }
        });

        sidebar.querySelectorAll('.ck-nav-group').forEach(function (group) {
            if (!query) {
                group.classList.remove('ck-nav-hidden');
                return;
            }

            var next = group.nextElementSibling;
            var hasVisibleItem = false;
            while (next && !next.classList.contains('ck-nav-group') && !next.classList.contains('divider')) {
                if (!next.classList.contains('ck-nav-hidden')) hasVisibleItem = true;
                next = next.nextElementSibling;
            }
            group.classList.toggle('ck-nav-hidden', !hasVisibleItem);
        });

        var empty = sidebar.querySelector('.ck-side-empty');
        if (!empty) {
            empty = document.createElement('div');
            empty.className = 'ck-side-empty';
            empty.textContent = document.documentElement.lang === 'ar' ? 'لا توجد نتائج في القائمة' : 'No navigation results';
            sidebar.querySelector('.main-menu').appendChild(empty);
        }
        empty.hidden = visibleCount > 0;

        document.querySelectorAll('[data-ck-nav-search]').forEach(function (input) {
            if (input.value !== value) input.value = value;
            var shell = input.closest('.ck-side-search');
            if (shell) shell.classList.toggle('has-value', Boolean(query));
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var inputs = document.querySelectorAll('[data-ck-nav-search]');
        inputs.forEach(function (input) {
            input.addEventListener('input', function () { filterNavigation(input.value); });
        });

        document.querySelectorAll('[data-ck-nav-clear]').forEach(function (button) {
            button.addEventListener('click', function () {
                filterNavigation('');
                var sidebarInput = document.querySelector('.ck-side-search [data-ck-nav-search]');
                if (sidebarInput) sidebarInput.focus();
            });
        });

        document.addEventListener('keydown', function (event) {
            if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
                var headerInput = document.querySelector('.ck-header-search [data-ck-nav-search]') || document.querySelector('[data-ck-nav-search]');
                if (!headerInput) return;
                event.preventDefault();
                headerInput.focus();
                headerInput.select();
            }
        });
    });
})();
