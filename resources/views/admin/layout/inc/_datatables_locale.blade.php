{{--
    لغة DataTables الافتراضية حسب لغة الواجهة، فلا تظهر عبارات إنجليزية (Show / No data available in table / Previous / Next) في الواجهة العربية.
    معظم الصفحات تحمّل dataTables.bundle.js بنفسها بعد هذا الملف، لذلك نلتقط لحظة تعريف $.fn.dataTable ونضبط الافتراضيات مرة واحدة.
    الصفحات التي تمرّر language/oLanguage خاصة بها تبقى كما هي لأن إعدادات الجدول تتقدّم على الافتراضيات.
--}}
@php
    $dt = fn (string $key) => __('dataTable.'.$key);
    $dataTableLanguage = [
        'sProcessing' => $dt('sProcessing'), 'sLengthMenu' => $dt('sLengthMenu'), 'sZeroRecords' => $dt('sZeroRecords'),
        'sEmptyTable' => $dt('sEmptyTable'), 'sInfo' => $dt('sInfo'), 'sInfoEmpty' => $dt('sInfoEmpty'), 'sInfoFiltered' => $dt('sInfoFiltered'),
        'sSearch' => $dt('sSearch'), 'sLoadingRecords' => $dt('sLoadingRecords'),
        'oPaginate' => ['sFirst' => $dt('sFirst'), 'sLast' => $dt('sLast'), 'sNext' => $dt('sNext'), 'sPrevious' => $dt('sPrevious')],
        'oAria' => ['sSortAscending' => $dt('sSortAscending'), 'sSortDescending' => $dt('sSortDescending')],
    ];
@endphp
<script>
(function ($) {
    if (!$) return;
    var language = {!! json_encode($dataTableLanguage, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};
    var apply = function (dataTable) { if (dataTable && dataTable.defaults) $.extend(true, dataTable.defaults.oLanguage, language); };
    if ($.fn.dataTable) return apply($.fn.dataTable);
    var current;
    Object.defineProperty($.fn, 'dataTable', {
        configurable: true, enumerable: true,
        get: function () { return current; },
        set: function (value) { current = value; apply(value); }, // يبقى الالتقاط دائمًا: بعض الصفحات تحمّل الحزمة أكثر من مرة
    });
})(window.jQuery);
</script>
