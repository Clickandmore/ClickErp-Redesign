/*
=======================================================
   Admin Tables JS - JavaScript عام للجداول الإدارية
   يحتوي على إصلاحات الـ dropdown وتحسينات الجداول
=======================================================
*/

$(document).ready(function() {
    // ضمان عدم تداخل الصفوف مع القائمة المنسدلة
    $(document).on('show.bs.dropdown', '.dropdown', function() {
        var $dropdown = $(this);
        var $dropdownMenu = $dropdown.find('.dropdown-menu');
        var $row = $dropdown.closest('tr');
        var isLastRow = $row.is(':last-child');
        var isOnlyChild = $row.is(':only-child');

        if (isOnlyChild) {
            $row.css('margin-bottom', '0');
        } else if (isLastRow) {
            $row.css('margin-bottom', '100px');
        } else {
            $row.css('margin-bottom', '0');
        }
    });

    // إزالة المساحة الإضافية عند إغلاق القائمة المنسدلة
    $(document).on('hide.bs.dropdown', '.dropdown', function() {
        var $dropdown = $(this);
        var $row = $dropdown.closest('tr');
        var isOnlyChild = $row.is(':only-child');

        if (isOnlyChild) {
            $row.css('margin-bottom', '0');
        } else {
            $row.css('margin-bottom', '');
        }
    });

    // حل جذري للقائمة المنسدلة
    let openDropdown = null;
    let isProcessingClick = false;

    // تفعيل Bootstrap dropdown للأزرار التي تحتاجه
    $('.actions-dropdown .dropdown-toggle').each(function() {
        if (!$(this).data('bs-toggle')) {
            $(this).attr('data-bs-toggle', 'dropdown');
        }
    });

    // معالج مخصص للقائمة المنسدلة
    $(document).on('click', '.actions-dropdown .dropdown-toggle', function(e) {
        // إذا كان الزر يستخدم Bootstrap dropdown، دعه يعمل
        if ($(this).attr('data-bs-toggle') === 'dropdown') {
            return; // دع Bootstrap يتعامل معه
        }

        e.preventDefault();
        e.stopPropagation();

        const $button = $(this);
        const $dropdown = $button.closest('.dropdown');
        const $menu = $dropdown.find('.dropdown-menu');
        const $row = $button.closest('tr');
        const $table = $button.closest('table');

        // إذا كانت هذه القائمة مفتوحة، أغلقها
        if (openDropdown && openDropdown[0] === $menu[0]) {
            closeDropdown();
            return;
        }

        // أغلق أي قائمة مفتوحة أخرى
        if (openDropdown) {
            closeDropdown();
        }

        // احسب موضع الزر
        const buttonOffset = $button.offset();
        const buttonHeight = $button.outerHeight();
        const menuHeight = 250; // ارتفاع تقديري للقائمة
        const windowHeight = $(window).height();
        const scrollTop = $(window).scrollTop();

        // حدد اتجاه القائمة (أعلى أم أسفل)
        let top;
        if (buttonOffset.top - scrollTop + buttonHeight + menuHeight > windowHeight) {
            // اعرض أعلى الزر
            top = buttonOffset.top - menuHeight;
        } else {
            // اعرض أسفل الزر
            top = buttonOffset.top + buttonHeight;
        }

        // أضف classes للتحكم في z-index
        $('body').addClass('dropdown-menu-open');
        $row.addClass('active-dropdown-row');

        // اعرض القائمة
        $menu.css({
            position: 'fixed',
            top: top + 'px',
            left: (buttonOffset.left - 100) + 'px', // محاذاة لليمين
            zIndex: 2147483647, // أعلى z-index ممكن
            display: 'block',
            minWidth: '200px'
        }).addClass('show').show();

        openDropdown = $menu;
    });

    // دالة لإغلاق القائمة
    function closeDropdown() {
        if (openDropdown) {
            openDropdown.removeClass('show').hide();
            openDropdown.closest('tr').removeClass('active-dropdown-row');
            $('body').removeClass('dropdown-menu-open');
            openDropdown = null;
        }
    }

    // أغلق القائمة عند النقر خارجها
    $(document).on('click', function(e) {
        if (!isProcessingClick && !$(e.target).closest('.actions-dropdown').length && openDropdown) {
            closeDropdown();
        }
    });

    // أغلق القائمة عند النقر على عنصر
    $(document).on('click', '.dropdown-item', function(e) {
        e.stopPropagation();

        // منع الإغلاق الفوري
        isProcessingClick = true;

        // انتظر قليلاً للسماح بتنفيذ الإجراء
        setTimeout(() => {
            closeDropdown();
            isProcessingClick = false;
        }, 300);
    });

    // منع إغلاق القائمة عند التمرير
    $(window).on('scroll', function() {
        // لا تفعل شيء - اترك القائمة مفتوحة
    });

    // منع إغلاق القائمة عند hover
    $(document).on('mouseenter mouseleave', '.dropdown-menu, .dropdown-item', function(e) {
        e.stopPropagation();
    });

    // إضافة مساحة إضافية للصف الواحد
    function adjustTableForSingleRow() {
        const $tables = $('.table');

        $tables.each(function() {
            const $table = $(this);
            const $tbody = $table.find('tbody');
            const rowCount = $tbody.find('tr').length;

            if (rowCount === 1) {
                // للصف الواحد: إضافة مساحة كبيرة أسفل
                $tbody.css({
                    'min-height': '300px',
                    'position': 'relative'
                });
                $tbody.find('tr:only-child').css({
                    'margin-bottom': '250px',
                    'position': 'relative'
                });
            } else if (rowCount === 2) {
                // للصفين: إضافة مساحة متوسطة
                $tbody.css({
                    'min-height': '200px',
                    'position': 'relative'
                });
                $tbody.find('tr:first-child').css({
                    'margin-bottom': '50px',
                    'position': 'relative'
                });
                $tbody.find('tr:last-child').css({
                    'margin-bottom': '150px',
                    'position': 'relative'
                });
            } else if (rowCount >= 3) {
                // للصفوف الكثيرة: إضافة مساحة للصف الأول والأخير
                $tbody.css({
                    'min-height': 'auto',
                    'position': 'relative'
                });
                $tbody.find('tr:first-child').css({
                    'margin-top': '20px',
                    'position': 'relative'
                });
                $tbody.find('tr:last-child').css({
                    'margin-bottom': '50px',
                    'position': 'relative'
                });
                $tbody.find('tr:not(:first-child):not(:last-child)').css({
                    'margin-bottom': 'auto',
                    'margin-top': 'auto',
                    'position': 'relative'
                });
            } else {
                // للصفوف الكثيرة: إزالة المساحة الإضافية
                $tbody.css({
                    'min-height': 'auto',
                    'position': 'relative'
                });
                $tbody.find('tr').css({
                    'margin-bottom': 'auto',
                    'position': 'relative'
                });
            }
        });
    }

    // استدعاء الدالة عند تحميل الصفحة
    adjustTableForSingleRow();

    // استدعاء الدالة عند تحديث DataTables
    $(document).on('draw.dt', function() {
        adjustTableForSingleRow();
    });

    // تحسين تجربة المستخدم للنماذج
    $(document).on('submit', 'form', function() {
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');

        if ($submitBtn.length) {
            $submitBtn.addClass('btn-loading').trigger('click');
        }
    });

    // إضافة تأثيرات للبطاقات
    $(document).on('mouseenter', '.card', function() {
        $(this).addClass('card-hover');
    });

    $(document).on('mouseleave', '.card', function() {
        $(this).removeClass('card-hover');
    });
});

// دالة مساعدة لإظهار رسائل التأكيد
function showConfirmDialog(title, text, confirmText = 'تأكيد', cancelText = 'إلغاء') {
    return Swal.fire({
        title: title,
        text: text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        reverseButtons: true
    });
}

// دالة مساعدة لإظهار رسائل النجاح
function showSuccessMessage(title, text = '') {
    return Swal.fire({
        title: title,
        text: text,
        icon: 'success',
        confirmButtonText: 'حسناً',
        timer: 3000,
        timerProgressBar: true
    });
}

// دالة مساعدة لإظهار رسائل الخطأ
function showErrorMessage(title, text = '') {
    return Swal.fire({
        title: title,
        text: text,
        icon: 'error',
        confirmButtonText: 'حسناً'
    });
}

// دالة مساعدة لإعادة تحميل الجدول
function reloadDataTable() {
    if (typeof dataTable !== 'undefined' && dataTable) {
        dataTable.ajax.reload(null, false);
    }
}

// إدارة إصلاح RTL للقائمة الجانبية
function initRTLFix() {
    // التحقق من وجود مشكلة في القائمة الجانبية
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        const sidebarRect = sidebar.getBoundingClientRect();
        // إذا كانت القائمة في الجانب الخطأ، طبق الإصلاح
        if (sidebarRect.left < 0) {
            document.body.classList.add('rtl-fix');
        }
    }
}

// تطبيق الإصلاح عند تحميل الصفحة
$(document).ready(function() {
    initRTLFix();
});

// إزالة الإصلاح عند مغادرة الصفحة (إذا لزم الأمر)
$(window).on('beforeunload', function() {
    document.body.classList.remove('rtl-fix');
});

/*
=======================================================
   Clean Loader - النسخة النظيفة الموحدة
   بدون خطوط طويلة - فقط نقاط أنيقة
=======================================================
*/

class CleanLoader {
    /**
     * إظهار loader نظيف في عنصر محدد
     * @param {string} targetSelector - محدد العنصر المستهدف
     * @param {object} options - خيارات التخصيص
     */
    static show(targetSelector, options = {}) {
        const target = document.querySelector(targetSelector);
        if (!target) return;

        const config = {
            text: options.text || 'جاري التحميل...',
            subtitle: options.subtitle || 'يرجى الانتظار',
            size: options.size || 'normal', // normal, small, xs
            color: options.color || 'default',
            icon: options.icon || 'database',
            ...options
        };

        const loaderClass = config.size === 'small' ? 'clean-loader-small' :
                           config.size === 'xs' ? 'clean-loader-xs' : 'clean-loader';

        const dotClass = config.size === 'small' ? 'loader-dot-small' :
                        config.size === 'xs' ? 'loader-dot-xs' : 'loader-dot';

        const colorClass = config.color !== 'default' ? ` ${config.color}` : '';

        const overlay = document.createElement('div');
        overlay.className = 'clean-loading-overlay';
        overlay.id = 'cleanLoaderOverlay';
        overlay.innerHTML = `
            <div class="text-center">
                <div class="${loaderClass}${colorClass} mb-3">
                    <div class="${dotClass}"></div>
                    <div class="${dotClass}"></div>
                    <div class="${dotClass}"></div>
                    ${config.size === 'normal' ? `<div class="${dotClass}"></div>` : ''}
                </div>
                <div class="clean-loading-text">
                    <i class="fas fa-${config.icon} me-2"></i>
                    ${config.text}
                </div>
                ${config.subtitle ? `<div class="clean-loading-subtitle">${config.subtitle}</div>` : ''}
            </div>
        `;

        target.style.position = 'relative';
        target.appendChild(overlay);
    }

    /**
     * إخفاء الـ loader
     * @param {string} targetSelector - محدد العنصر المستهدف
     */
    static hide(targetSelector) {
        const target = document.querySelector(targetSelector);
        if (!target) return;

        const overlay = target.querySelector('#cleanLoaderOverlay');
        if (overlay) {
            overlay.remove();
        }
    }

    /**
     * إظهار loader في الصفحة كاملة
     * @param {object} options - خيارات التخصيص
     */
    static showFullPage(options = {}) {
        const overlay = document.createElement('div');
        overlay.id = 'cleanLoaderFullPage';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
        `;

        overlay.innerHTML = `
            <div class="text-center">
                <div class="clean-loader mb-3">
                    <div class="loader-dot"></div>
                    <div class="loader-dot"></div>
                    <div class="loader-dot"></div>
                    <div class="loader-dot"></div>
                </div>
                <div class="clean-loading-text">
                    <i class="fas fa-${options.icon || 'spinner'} me-2"></i>
                    ${options.text || 'جاري التحميل...'}
                </div>
                ${options.subtitle ? `<div class="clean-loading-subtitle">${options.subtitle}</div>` : ''}
            </div>
        `;

        document.body.appendChild(overlay);
    }

    /**
     * إخفاء loader الصفحة كاملة
     */
    static hideFullPage() {
        const overlay = document.getElementById('cleanLoaderFullPage');
        if (overlay) {
            overlay.remove();
        }
    }

    /**
     * إظهار loader في button - بدون خطوط طويلة
     * @param {string} buttonSelector - محدد الزر
     * @param {string} text - النص المطلوب عرضه
     */
    static showInButton(buttonSelector, text = 'جاري المعالجة...') {
        const button = document.querySelector(buttonSelector);
        if (!button) return;

        button.disabled = true;
        button.dataset.originalContent = button.innerHTML;
        button.innerHTML = `
            <div class="clean-loader-xs me-2" style="display: inline-flex;">
                <div class="loader-dot-xs"></div>
                <div class="loader-dot-xs"></div>
                <div class="loader-dot-xs"></div>
            </div>
            ${text}
        `;
    }

    /**
     * إخفاء loader من button
     * @param {string} buttonSelector - محدد الزر
     */
    static hideFromButton(buttonSelector) {
        const button = document.querySelector(buttonSelector);
        if (!button) return;

        button.disabled = false;
        if (button.dataset.originalContent) {
            button.innerHTML = button.dataset.originalContent;
            delete button.dataset.originalContent;
        }
    }

    /**
     * تحديث DataTable processing message - بدون خطوط طويلة
     */
    static getCleanProcessingMessage() {
        return '<div class="d-flex justify-content-center align-items-center p-4">' +
               '<div class="clean-loader-small me-3">' +
               '<div class="loader-dot-small"></div>' +
               '<div class="loader-dot-small"></div>' +
               '<div class="loader-dot-small"></div>' +
               '</div>' +
               '<div class="text-primary fw-bold">جاري المعالجة...</div>' +
               '</div>';
    }
}

// jQuery Plugin
if (typeof jQuery !== 'undefined') {
    jQuery.fn.cleanLoader = function(action, options = {}) {
        if (action === 'show') {
            CleanLoader.show(`#${this.attr('id')}`, options);
        } else if (action === 'hide') {
            CleanLoader.hide(`#${this.attr('id')}`);
        }
        return this;
    };
}

// تصدير للاستخدام العام
window.CleanLoader = CleanLoader;
