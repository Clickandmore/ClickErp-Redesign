{{--
    حساب مدة الخبرة من تاريخي البداية والنهاية (<input type="month"> = "YYYY-MM").
    مشترك بين شاشات: إضافة عاملة، تعديل عاملة، وإضافة عاملة من بوابة المكتب.
    الخادم يعيد الحساب في MaidExpertiseAction، وهذا للعرض الفوري فقط.
--}}
<script>
    (function($) {
        'use strict';

        const MONTH_PATTERN = /^(\d{4})-(\d{1,2})$/;

        function monthsBetween(start, end) {
            const from = MONTH_PATTERN.exec(start || '');
            const to = MONTH_PATTERN.exec(end || '');

            if (!from || !to) {
                return null;
            }

            const months = (parseInt(to[1], 10) - parseInt(from[1], 10)) * 12 +
                (parseInt(to[2], 10) - parseInt(from[2], 10));

            return months < 0 ? null : months;
        }

        // تصريف عربي سليم: سنة واحدة / سنتان / 3 سنوات / 11 سنة
        function countLabel(count, forms) {
            if (count === 1) {
                return forms[0];
            }
            if (count === 2) {
                return forms[1];
            }
            if (count >= 3 && count <= 10) {
                return count + ' ' + forms[2];
            }
            return count + ' ' + forms[3];
        }

        function durationLabel(months) {
            if (months === null || typeof months === 'undefined') {
                return '';
            }

            const years = Math.floor(months / 12);
            const remainingMonths = months % 12;
            const parts = [];

            if (years > 0) {
                parts.push(countLabel(years, ['سنة واحدة', 'سنتان', 'سنوات', 'سنة']));
            }
            if (remainingMonths > 0) {
                parts.push(countLabel(remainingMonths, ['شهر واحد', 'شهران', 'أشهر', 'شهرًا']));
            }

            return parts.length ? parts.join(' و ') : 'أقل من شهر';
        }

        window.experienceMonthsBetween = monthsBetween;
        window.experienceDurationLabel = durationLabel;

        window.syncExperienceRow = function($row) {
            const start = $row.find('.exp-start-date').val();
            const end = $row.find('.exp-end-date').val();
            const $label = $row.find('.exp-duration-label');
            const $years = $row.find('.exp-duration-years');
            const $error = $row.find('.exp-duration-error');

            $error.text('');

            if (!start || !end) {
                // سجلات قديمة أُدخلت قبل حقول التاريخ: نُبقي مدتها المخزّنة كما هي
                const storedYears = parseFloat($years.val());

                if (!Number.isNaN(storedYears) && storedYears > 0) {
                    $label.val(durationLabel(Math.round(storedYears * 12)));
                } else {
                    $label.val('');
                    $years.val('');
                }

                return;
            }

            const months = monthsBetween(start, end);

            if (months === null) {
                $label.val('');
                $years.val('');
                $error.text('نهاية الخبرة يجب أن تكون بعد بدايتها أو مساوية لها.');
                return;
            }

            $label.val(durationLabel(months));
            $years.val((months / 12).toFixed(2));
        };

        window.initializeExperienceRow = function($row) {
            window.syncExperienceRow($row);
        };

        function syncManualDuration($row, input) {
            if (!input.checkValidity() || input.value === '') {
                return;
            }

            const months = Math.round(Number(input.value) * 12);
            const $start = $row.find('.exp-start-date');
            const $end = $row.find('.exp-end-date');
            const now = new Date();
            const anchor = MONTH_PATTERN.exec($end.val() || $start.val() ||
                (now.getFullYear() + '-' + (now.getMonth() + 1)));
            const anchorMonth = Number(anchor[1]) * 12 + Number(anchor[2]) - 1;
            const useStart = !$end.val() && !!$start.val();
            const startMonth = useStart ? anchorMonth : anchorMonth - months;
            const endMonth = startMonth + months;
            const formatMonth = value => String(Math.floor(value / 12)).padStart(4, '0') + '-' +
                String(value % 12 + 1).padStart(2, '0');

            if (startMonth < 12 || endMonth >= 120000) {
                input.setCustomValidity('المدة المدخلة تتجاوز نطاق التاريخ المتاح.');
                return;
            }

            $start.val(formatMonth(startMonth));
            $end.val(formatMonth(endMonth));
            window.syncExperienceRow($row);
        }

        window.attachExperienceHandlers = function(context) {
            const $scope = context ? $(context) : $('#experience_section');

            $scope.find('tr.y-group').add($scope.filter('tr.y-group')).each(function() {
                const $row = $(this);

                $row.find('.exp-manual-years').off('.exp').on('input.exp change.exp', function() {
                    this.setCustomValidity('');
                    syncManualDuration($row, this);
                });

                $row.find('.exp-start-date, .exp-end-date')
                    .off('.exp')
                    .on('input.exp change.exp', function() {
                        $row.find('.exp-manual-years').val('').each(function() {
                            this.setCustomValidity('');
                        });
                        window.syncExperienceRow($row);
                    });

                window.initializeExperienceRow($row);
            });
        };
    })(jQuery);
</script>
