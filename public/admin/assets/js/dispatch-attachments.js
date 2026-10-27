(function () {
    'use strict';

    const selections = new WeakMap();
    const kinds = [
        { className: 'is-image', icon: 'fa-file-image', extensions: ['jpg', 'jpeg', 'png'] },
        { className: 'is-pdf', icon: 'fa-file-pdf', extensions: ['pdf'] },
        { className: 'is-word', icon: 'fa-file-word', extensions: ['doc', 'docx'] },
        { className: 'is-excel', icon: 'fa-file-excel', extensions: ['xls', 'xlsx'] },
    ];

    function kindOf(fileName) {
        const extension = (fileName.split('.').pop() || '').toLowerCase();
        return kinds.find(function (kind) { return kind.extensions.includes(extension); })
            || { className: 'is-other', icon: 'fa-file' };
    }

    function formatSize(bytes) {
        const options = { maximumFractionDigits: 1 };
        return bytes >= 1024 * 1024
            ? (bytes / (1024 * 1024)).toLocaleString('ar-SA', options) + ' ميجابايت'
            : (bytes / 1024).toLocaleString('ar-SA', options) + ' كيلوبايت';
    }

    function update(input, files) {
        const transfer = new DataTransfer();
        files.forEach(function (file) { transfer.items.add(file); });
        input.files = transfer.files;
        selections.set(input, files);

        const wrapper = input.closest('[data-dispatch-attachments]');
        const maxFiles = Number(wrapper.dataset.maxFiles || 10);
        const maxBytes = Number(wrapper.dataset.maxMb || 10) * 1024 * 1024;
        const list = wrapper.querySelector('[data-selected-attachments]');
        const counter = wrapper.querySelector('[data-attachments-counter]');
        const warning = wrapper.querySelector('[data-attachments-warning]');
        const warnings = [];

        list.replaceChildren();
        list.hidden = files.length === 0;
        files.forEach(function (file, index) {
            const kind = kindOf(file.name);
            const tooLarge = file.size > maxBytes;
            const row = document.createElement('li');
            row.className = 'dispatch-attachments__item' + (tooLarge ? ' is-invalid' : '');

            const icon = document.createElement('span');
            icon.className = 'dispatch-attachments__file-icon ' + kind.className;
            icon.innerHTML = '<i class="far ' + kind.icon + '"></i>';

            const body = document.createElement('span');
            body.className = 'dispatch-attachments__file-body';
            const name = document.createElement('span');
            name.className = 'dispatch-attachments__file-name';
            name.textContent = file.name;
            name.title = file.name;
            const size = document.createElement('span');
            size.className = 'dispatch-attachments__file-size';
            size.textContent = formatSize(file.size) + (tooLarge ? ' — يتجاوز الحد المسموح' : '');
            body.append(name, size);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn btn-sm btn-outline-danger dispatch-attachments__remove';
            remove.innerHTML = '<i class="fas fa-times"></i>';
            remove.title = 'إزالة';
            remove.setAttribute('aria-label', 'إزالة ' + file.name);
            remove.dataset.removeAttachment = String(index);

            row.append(icon, body, remove);
            list.append(row);
            if (tooLarge) warnings.push('الملف «' + file.name + '» أكبر من ' + wrapper.dataset.maxMb + ' ميجابايت.');
        });

        if (files.length > maxFiles) {
            warnings.unshift('تم اختيار ' + files.length + ' ملفات، والحد الأقصى ' + maxFiles + ' ملفات في كل حفظ.');
        }
        warning.textContent = warnings.join(' ');
        warning.hidden = warnings.length === 0;

        counter.hidden = files.length === 0;
        counter.textContent = files.length + ' / ' + maxFiles + ' ملفات';
        counter.classList.toggle('text-danger', files.length > maxFiles);
    }

    document.addEventListener('change', function (event) {
        const input = event.target;
        if (!input.matches('.dispatch-attachments-input')) return;
        input.closest('.dispatch-attachments__zone')?.classList.remove('is-dragover');
        const files = (selections.get(input) || []).slice();
        Array.from(input.files).forEach(function (file) {
            const duplicate = files.some(function (existing) {
                return existing.name === file.name && existing.size === file.size && existing.lastModified === file.lastModified;
            });
            if (!duplicate) files.push(file);
        });
        update(input, files);
    });

    ['dragenter', 'dragover'].forEach(function (type) {
        document.addEventListener(type, function (event) {
            const zone = event.target.closest && event.target.closest('.dispatch-attachments__zone');
            if (zone) zone.classList.add('is-dragover');
        });
    });
    ['dragleave', 'drop'].forEach(function (type) {
        document.addEventListener(type, function (event) {
            const zone = event.target.closest && event.target.closest('.dispatch-attachments__zone');
            if (zone && (type === 'drop' || !zone.contains(event.relatedTarget))) zone.classList.remove('is-dragover');
        });
    });

    document.addEventListener('click', function (event) {
        const button = event.target.closest('[data-remove-attachment]');
        if (!button) return;
        const input = button.closest('[data-dispatch-attachments]').querySelector('.dispatch-attachments-input');
        const files = (selections.get(input) || Array.from(input.files)).slice();
        files.splice(Number(button.dataset.removeAttachment), 1);
        update(input, files);
    });

    document.addEventListener('reset', function (event) {
        event.target.querySelectorAll('.dispatch-attachments-input').forEach(function (input) { update(input, []); });
    });
})();
