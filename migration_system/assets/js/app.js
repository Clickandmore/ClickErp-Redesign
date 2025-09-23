/**
 * نظام نقل البيانات - JavaScript الرئيسي
 * Data Migration System - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // تهيئة التطبيق
    initializeApp();
});

/**
 * تهيئة التطبيق
 */
function initializeApp() {
    // تهيئة التلميحات
    initializeTooltips();

    // تهيئة التنبيهات
    initializeAlerts();

    // تهيئة النماذج
    initializeForms();

    // تهيئة الجداول
    initializeTables();

    // تهيئة الإشعارات
    initializeNotifications();
}

/**
 * تهيئة التلميحات (Tooltips)
 */
function initializeTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * تهيئة التنبيهات القابلة للإغلاق
 */
function initializeAlerts() {
    // إخفاء التنبيهات تلقائياً بعد 5 ثواني
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(function(alert) {
            const closeButton = alert.querySelector('.btn-close');
            if (closeButton && !alert.classList.contains('alert-danger')) {
                closeButton.click();
            }
        });
    }, 5000);
}

/**
 * تهيئة النماذج
 */
function initializeForms() {
    // التحقق من صحة النماذج
    const forms = document.querySelectorAll('.needs-validation');

    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // تحسين حقول الرفع
    initializeFileUploads();
}

/**
 * تهيئة حقول رفع الملفات
 */
function initializeFileUploads() {
    const fileInputs = document.querySelectorAll('input[type="file"]');

    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // التحقق من حجم الملف
                const maxSize = 10 * 1024 * 1024; // 10MB
                if (file.size > maxSize) {
                    showNotification('حجم الملف كبير جداً. الحد الأقصى 10 ميجابايت.', 'error');
                    this.value = '';
                    return;
                }

                // التحقق من نوع الملف
                const allowedTypes = ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
                if (!allowedTypes.includes(file.type)) {
                    showNotification('نوع الملف غير مدعوم. يُسمح فقط بملفات CSV و Excel.', 'error');
                    this.value = '';
                    return;
                }

                showNotification(`تم اختيار الملف: ${file.name}`, 'success');
            }
        });
    });
}

/**
 * تهيئة الجداول
 */
function initializeTables() {
    // إضافة وظائف الترتيب والفلترة
    const tables = document.querySelectorAll('.table-sortable');

    tables.forEach(table => {
        addTableSorting(table);
    });

    // تحديد/إلغاء تحديد الكل
    const selectAllCheckboxes = document.querySelectorAll('.select-all');
    selectAllCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const targetTable = document.querySelector(this.dataset.target);
            const checkboxes = targetTable.querySelectorAll('tbody input[type="checkbox"]');

            checkboxes.forEach(cb => {
                cb.checked = this.checked;
            });
        });
    });
}

/**
 * إضافة ترتيب الجداول
 */
function addTableSorting(table) {
    const headers = table.querySelectorAll('th[data-sortable]');

    headers.forEach(header => {
        header.style.cursor = 'pointer';
        header.innerHTML += ' <i class="fas fa-sort text-muted"></i>';

        header.addEventListener('click', function() {
            const column = this.dataset.column;
            const currentOrder = this.dataset.order || 'asc';
            const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';

            // تحديث الرموز
            headers.forEach(h => {
                const icon = h.querySelector('i');
                icon.className = 'fas fa-sort text-muted';
            });

            const icon = this.querySelector('i');
            icon.className = newOrder === 'asc' ? 'fas fa-sort-up text-primary' : 'fas fa-sort-down text-primary';
            this.dataset.order = newOrder;

            // ترتيب الجدول
            sortTable(table, column, newOrder);
        });
    });
}

/**
 * ترتيب الجدول
 */
function sortTable(table, column, order) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    rows.sort((a, b) => {
        const aValue = a.querySelector(`[data-sort="${column}"]`)?.textContent || '';
        const bValue = b.querySelector(`[data-sort="${column}"]`)?.textContent || '';

        // تحويل الأرقام
        const aNum = parseFloat(aValue);
        const bNum = parseFloat(bValue);

        if (!isNaN(aNum) && !isNaN(bNum)) {
            return order === 'asc' ? aNum - bNum : bNum - aNum;
        }

        // ترتيب نصي
        return order === 'asc' ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
    });

    // إعادة ترتيب الصفوف
    rows.forEach(row => tbody.appendChild(row));
}

/**
 * تهيئة نظام الإشعارات
 */
function initializeNotifications() {
    // إنشاء حاوي الإشعارات إذا لم يكن موجوداً
    if (!document.getElementById('notifications-container')) {
        const container = document.createElement('div');
        container.id = 'notifications-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        `;
        document.body.appendChild(container);
    }
}

/**
 * عرض إشعار
 */
function showNotification(message, type = 'info', duration = 5000) {
    const container = document.getElementById('notifications-container');
    const notification = document.createElement('div');

    const typeClasses = {
        'success': 'alert-success',
        'error': 'alert-danger',
        'warning': 'alert-warning',
        'info': 'alert-info'
    };

    const typeIcons = {
        'success': 'fas fa-check-circle',
        'error': 'fas fa-exclamation-circle',
        'warning': 'fas fa-exclamation-triangle',
        'info': 'fas fa-info-circle'
    };

    notification.className = `alert ${typeClasses[type]} alert-dismissible fade show`;
    notification.innerHTML = `
        <i class="${typeIcons[type]} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    container.appendChild(notification);

    // إخفاء تلقائي
    if (duration > 0) {
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, duration);
    }
}

/**
 * تأكيد الإجراء
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        if (typeof callback === 'function') {
            callback();
        }
        return true;
    }
    return false;
}

/**
 * عرض شاشة التحميل
 */
function showLoading(element) {
    if (typeof element === 'string') {
        element = document.querySelector(element);
    }

    if (element) {
        element.classList.add('loading');
    }
}

/**
 * إخفاء شاشة التحميل
 */
function hideLoading(element) {
    if (typeof element === 'string') {
        element = document.querySelector(element);
    }

    if (element) {
        element.classList.remove('loading');
    }
}

/**
 * تنسيق حجم الملف
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';

    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * تنسيق الأرقام
 */
function formatNumber(num) {
    return new Intl.NumberFormat('ar-SA').format(num);
}

/**
 * تنسيق التاريخ
 */
function formatDate(dateString, options = {}) {
    const defaultOptions = {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    };

    const finalOptions = { ...defaultOptions, ...options };

    return new Intl.DateTimeFormat('ar-SA', finalOptions).format(new Date(dateString));
}

/**
 * طلب AJAX مُبسط
 */
function makeRequest(url, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };

    const finalOptions = { ...defaultOptions, ...options };

    return fetch(url, finalOptions)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .catch(error => {
            console.error('Request failed:', error);
            showNotification('حدث خطأ في الاتصال بالخادم', 'error');
            throw error;
        });
}

/**
 * تحديث العداد التلقائي
 */
function startCountdown(element, seconds, callback) {
    let remaining = seconds;

    const interval = setInterval(() => {
        element.textContent = remaining;
        remaining--;

        if (remaining < 0) {
            clearInterval(interval);
            if (typeof callback === 'function') {
                callback();
            }
        }
    }, 1000);

    return interval;
}

/**
 * نسخ النص للحافظة
 */
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showNotification('تم النسخ إلى الحافظة', 'success', 2000);
        }).catch(err => {
            console.error('Failed to copy: ', err);
            showNotification('فشل في النسخ', 'error');
        });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();

        try {
            document.execCommand('copy');
            showNotification('تم النسخ إلى الحافظة', 'success', 2000);
        } catch (err) {
            console.error('Fallback copy failed: ', err);
            showNotification('فشل في النسخ', 'error');
        }

        document.body.removeChild(textArea);
    }
}

/**
 * إعادة تحميل تلقائي للصفحة
 */
function enableAutoRefresh(interval = 30000, condition = null) {
    return setInterval(() => {
        if (condition === null || condition()) {
            location.reload();
        }
    }, interval);
}

/**
 * كشف تغييرات الصفحة
 */
function detectPageChanges() {
    let hasUnsavedChanges = false;

    // مراقبة النماذج
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('change', () => {
                hasUnsavedChanges = true;
            });
        });
    });

    // تحذير عند مغادرة الصفحة
    window.addEventListener('beforeunload', (e) => {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = 'لديك تغييرات غير محفوظة. هل تريد المغادرة؟';
        }
    });

    // إزالة التحذير عند الإرسال
    forms.forEach(form => {
        form.addEventListener('submit', () => {
            hasUnsavedChanges = false;
        });
    });
}

/**
 * التحقق من الاتصال بالإنترنت
 */
function checkConnectionStatus() {
    const updateStatus = (isOnline) => {
        const statusElement = document.getElementById('connection-status');
        if (statusElement) {
            statusElement.className = isOnline ? 'alert alert-success' : 'alert alert-warning';
            statusElement.textContent = isOnline ? 'متصل' : 'غير متصل';
        }

        if (!isOnline) {
            showNotification('فقد الاتصال بالإنترنت', 'warning');
        }
    };

    window.addEventListener('online', () => updateStatus(true));
    window.addEventListener('offline', () => updateStatus(false));

    // فحص أولي
    updateStatus(navigator.onLine);
}

/**
 * تهيئة البحث المباشر
 */
function initializeLiveSearch(inputSelector, resultsSelector, searchFunction) {
    const input = document.querySelector(inputSelector);
    const results = document.querySelector(resultsSelector);

    if (!input || !results) return;

    let searchTimeout;

    input.addEventListener('input', function() {
        clearTimeout(searchTimeout);

        searchTimeout = setTimeout(() => {
            const query = this.value.trim();

            if (query.length >= 2) {
                showLoading(results);
                searchFunction(query).then(data => {
                    hideLoading(results);
                    displaySearchResults(results, data);
                }).catch(error => {
                    hideLoading(results);
                    console.error('Search failed:', error);
                });
            } else {
                results.innerHTML = '';
            }
        }, 300);
    });
}

/**
 * عرض نتائج البحث
 */
function displaySearchResults(container, results) {
    if (!results || results.length === 0) {
        container.innerHTML = '<div class="text-muted p-3">لا توجد نتائج</div>';
        return;
    }

    const html = results.map(result => `
        <div class="search-result p-2 border-bottom">
            <div class="fw-bold">${result.title}</div>
            <div class="small text-muted">${result.description}</div>
        </div>
    `).join('');

    container.innerHTML = html;
}

// تصدير الوظائف للاستخدام العام
window.MigrationApp = {
    showNotification,
    confirmAction,
    showLoading,
    hideLoading,
    formatFileSize,
    formatNumber,
    formatDate,
    makeRequest,
    copyToClipboard,
    enableAutoRefresh,
    detectPageChanges,
    checkConnectionStatus,
    initializeLiveSearch
};

// بدء فحص الاتصال
checkConnectionStatus();
